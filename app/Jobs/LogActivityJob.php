<?php

namespace App\Jobs;

use App\Models\Activity;
use App\Services\PerformanceMonitoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 非同步活動記錄工作
 */
class LogActivityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 工作最大嘗試次數
     *
     * @var int
     */
    public $tries = 5;

    /**
     * 工作超時時間（秒）
     *
     * @var int
     */
    public $timeout = 60;

    /**
     * 重試延遲時間（秒）
     *
     * @var array
     */
    public $backoff = [1, 5, 15, 30, 60];

    /**
     * 活動資料
     *
     * @var array
     */
    protected array $activityData;

    /**
     * 重試策略
     *
     * @var string
     */
    protected string $retryStrategy;

    /**
     * 建立新的工作實例
     *
     * @param array $activityData
     * @param string $retryStrategy
     */
    public function __construct(array $activityData, string $retryStrategy = 'exponential')
    {
        $this->activityData = $activityData;
        $this->retryStrategy = $retryStrategy;
    }

    /**
     * 執行工作
     *
     * @return void
     */
    public function handle(): void
    {
        $startTime = microtime(true);
        $attemptNumber = $this->attempts();
        
        try {
            Log::channel('activity')->debug('開始處理活動記錄', [
                'type' => $this->activityData['type'],
                'attempt' => $attemptNumber,
                'job_id' => $this->job->getJobId(),
            ]);

            // 驗證資料完整性
            $validatedData = $this->validateActivityData($this->activityData);
            
            // 檢查重複記錄
            if ($this->isDuplicateActivity($validatedData)) {
                Log::channel('activity')->info('跳過重複的活動記錄', [
                    'type' => $validatedData['type'],
                    'user_id' => $validatedData['user_id'] ?? null,
                ]);
                return;
            }
            
            // 建立活動記錄
            DB::beginTransaction();
            
            $activity = Activity::create($validatedData);
            
            DB::commit();
            
            $processingTime = (microtime(true) - $startTime) * 1000;
            
            // 記錄成功
            $this->recordSuccess($activity, $processingTime, $attemptNumber);
            
            Log::channel('activity')->info('活動記錄處理成功', [
                'activity_id' => $activity->id,
                'type' => $activity->type,
                'user_id' => $activity->user_id,
                'processing_time_ms' => $processingTime,
                'attempt' => $attemptNumber,
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            $processingTime = (microtime(true) - $startTime) * 1000;
            
            // 記錄失敗
            $this->recordFailure($e, $processingTime, $attemptNumber);
            
            // 決定是否重試
            if ($this->shouldRetry($e, $attemptNumber)) {
                $this->scheduleRetry($e, $attemptNumber);
                return;
            }
            
            Log::error('活動記錄處理失敗', [
                'error' => $e->getMessage(),
                'activity_type' => $this->activityData['type'] ?? 'unknown',
                'attempt' => $attemptNumber,
                'processing_time_ms' => $processingTime,
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }

    /**
     * 工作失敗時的處理
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('活動記錄工作永久失敗', [
            'error' => $exception->getMessage(),
            'activity_type' => $this->activityData['type'] ?? 'unknown',
            'activity_data' => $this->activityData,
            'total_attempts' => $this->attempts(),
            'job_id' => $this->job?->getJobId(),
        ]);
        
        // 記錄永久失敗統計
        $this->recordPermanentFailure($exception);
        
        // 嘗試儲存到備用儲存
        $this->saveToBackupStorage($exception);
        
        // 發送失敗通知（如果是高風險活動）
        $this->sendFailureNotification($exception);
    }

    /**
     * 計算重試延遲時間
     *
     * @return int
     */
    public function retryAfter(): int
    {
        $attempt = $this->attempts();
        
        return match ($this->retryStrategy) {
            'linear' => $attempt * 10,
            'exponential' => min(pow(2, $attempt - 1) * 5, 300),
            'fixed' => 30,
            'custom' => $this->backoff[$attempt - 1] ?? 60,
            default => min(pow(2, $attempt - 1) * 5, 300),
        };
    }

    /**
     * 驗證活動資料
     *
     * @param array $data
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function validateActivityData(array $data): array
    {
        // 必要欄位檢查
        if (empty($data['type'])) {
            throw new \InvalidArgumentException('活動類型不能為空');
        }
        
        if (empty($data['description'])) {
            throw new \InvalidArgumentException('活動描述不能為空');
        }
        
        // 資料清理和預設值
        $validated = [
            'type' => trim($data['type']),
            'description' => trim($data['description']),
            'module' => $data['module'] ?? null,
            'user_id' => isset($data['user_id']) && is_numeric($data['user_id']) ? (int) $data['user_id'] : null,
            'subject_id' => $data['subject_id'] ?? null,
            'subject_type' => $data['subject_type'] ?? null,
            'properties' => $data['properties'] ?? null,
            'ip_address' => $data['ip_address'] ?? '127.0.0.1',
            'user_agent' => $data['user_agent'] ?? null,
            'result' => $data['result'] ?? 'success',
            'risk_level' => max(1, min(10, (int) ($data['risk_level'] ?? 1))),
            'signature' => $data['signature'] ?? null,
            'created_at' => $data['created_at'] ?? now(),
            'updated_at' => $data['updated_at'] ?? now(),
        ];
        
        // 資料長度檢查
        if (strlen($validated['type']) > 100) {
            throw new \InvalidArgumentException('活動類型長度不能超過 100 字元');
        }
        
        if (strlen($validated['description']) > 500) {
            throw new \InvalidArgumentException('活動描述長度不能超過 500 字元');
        }
        
        return $validated;
    }

    /**
     * 檢查是否為重複活動
     *
     * @param array $data
     * @return bool
     */
    protected function isDuplicateActivity(array $data): bool
    {
        // 生成唯一識別碼
        $identifier = md5(json_encode([
            'type' => $data['type'],
            'user_id' => $data['user_id'],
            'subject_id' => $data['subject_id'],
            'subject_type' => $data['subject_type'],
            'ip_address' => $data['ip_address'],
            'created_minute' => $data['created_at']->format('Y-m-d H:i'), // 精確到分鐘
        ]));
        
        $cacheKey = "activity_duplicate_check:{$identifier}";
        
        if (Cache::has($cacheKey)) {
            return true;
        }
        
        // 設定 5 分鐘的重複檢查快取
        Cache::put($cacheKey, true, 300);
        
        return false;
    }

    /**
     * 決定是否應該重試
     *
     * @param \Exception $exception
     * @param int $attemptNumber
     * @return bool
     */
    protected function shouldRetry(\Exception $exception, int $attemptNumber): bool
    {
        // 超過最大重試次數
        if ($attemptNumber >= $this->tries) {
            return false;
        }
        
        // 不可重試的錯誤類型
        $nonRetryableErrors = [
            \InvalidArgumentException::class,
            \Illuminate\Database\QueryException::class, // 某些資料庫錯誤
        ];
        
        foreach ($nonRetryableErrors as $errorClass) {
            if ($exception instanceof $errorClass) {
                // 檢查特定的資料庫錯誤
                if ($exception instanceof \Illuminate\Database\QueryException) {
                    $errorCode = $exception->getCode();
                    // 重複鍵錯誤、資料過長等不重試
                    if (in_array($errorCode, [1062, 1406, 1264])) {
                        return false;
                    }
                }
            }
        }
        
        // 可重試的錯誤類型
        $retryableErrors = [
            'Connection refused',
            'Deadlock found',
            'Lock wait timeout',
            'Too many connections',
            'Server has gone away',
        ];
        
        $errorMessage = $exception->getMessage();
        foreach ($retryableErrors as $retryableError) {
            if (str_contains($errorMessage, $retryableError)) {
                return true;
            }
        }
        
        // 預設重試策略
        return true;
    }

    /**
     * 安排重試
     *
     * @param \Exception $exception
     * @param int $attemptNumber
     * @return void
     */
    protected function scheduleRetry(\Exception $exception, int $attemptNumber): void
    {
        $delay = $this->retryAfter();
        
        Log::channel('activity')->warning('活動記錄處理失敗，安排重試', [
            'error' => $exception->getMessage(),
            'attempt' => $attemptNumber,
            'next_attempt' => $attemptNumber + 1,
            'retry_delay_seconds' => $delay,
            'activity_type' => $this->activityData['type'] ?? 'unknown',
        ]);
        
        // 記錄重試統計
        $this->recordRetryAttempt($exception, $attemptNumber, $delay);
        
        // 釋放工作以便重試
        $this->release($delay);
    }

    /**
     * 記錄成功統計
     *
     * @param Activity $activity
     * @param float $processingTime
     * @param int $attemptNumber
     * @return void
     */
    protected function recordSuccess(Activity $activity, float $processingTime, int $attemptNumber): void
    {
        // 更新成功統計
        $cacheKey = 'activity_job_stats:' . date('Y-m-d-H');
        $stats = Cache::get($cacheKey, [
            'total_jobs' => 0,
            'successful_jobs' => 0,
            'failed_jobs' => 0,
            'total_processing_time' => 0,
            'retry_attempts' => 0,
        ]);
        
        $stats['total_jobs']++;
        $stats['successful_jobs']++;
        $stats['total_processing_time'] += $processingTime;
        if ($attemptNumber > 1) {
            $stats['retry_attempts'] += ($attemptNumber - 1);
        }
        
        Cache::put($cacheKey, $stats, 3600); // 1小時
        
        // 更新效能指標
        try {
            $performanceService = app(PerformanceMonitoringService::class);
            $performanceService->recordMetric('activity_job_processing_time', $processingTime, [
                'activity_type' => $activity->type,
                'attempt_number' => $attemptNumber,
                'success' => true,
            ]);
        } catch (\Exception $e) {
            Log::warning('無法記錄效能指標', ['error' => $e->getMessage()]);
        }
    }

    /**
     * 記錄失敗統計
     *
     * @param \Exception $exception
     * @param float $processingTime
     * @param int $attemptNumber
     * @return void
     */
    protected function recordFailure(\Exception $exception, float $processingTime, int $attemptNumber): void
    {
        $cacheKey = 'activity_job_failures:' . date('Y-m-d-H');
        $failures = Cache::get($cacheKey, []);
        
        $failures[] = [
            'timestamp' => now()->toISOString(),
            'error' => $exception->getMessage(),
            'error_type' => get_class($exception),
            'activity_type' => $this->activityData['type'] ?? 'unknown',
            'attempt_number' => $attemptNumber,
            'processing_time_ms' => $processingTime,
        ];
        
        Cache::put($cacheKey, $failures, 3600); // 1小時
    }

    /**
     * 記錄重試嘗試
     *
     * @param \Exception $exception
     * @param int $attemptNumber
     * @param int $delay
     * @return void
     */
    protected function recordRetryAttempt(\Exception $exception, int $attemptNumber, int $delay): void
    {
        $cacheKey = 'activity_job_retries:' . date('Y-m-d');
        $retries = Cache::get($cacheKey, []);
        
        $retries[] = [
            'timestamp' => now()->toISOString(),
            'error' => $exception->getMessage(),
            'activity_type' => $this->activityData['type'] ?? 'unknown',
            'attempt_number' => $attemptNumber,
            'retry_delay' => $delay,
            'job_id' => $this->job?->getJobId(),
        ];
        
        Cache::put($cacheKey, $retries, 86400); // 24小時
    }

    /**
     * 記錄永久失敗
     *
     * @param \Throwable $exception
     * @return void
     */
    protected function recordPermanentFailure(\Throwable $exception): void
    {
        $cacheKey = 'activity_job_permanent_failures:' . date('Y-m-d');
        $failures = Cache::get($cacheKey, []);
        
        $failures[] = [
            'timestamp' => now()->toISOString(),
            'error' => $exception->getMessage(),
            'error_type' => get_class($exception),
            'activity_data' => $this->activityData,
            'total_attempts' => $this->attempts(),
            'job_id' => $this->job?->getJobId(),
        ];
        
        Cache::put($cacheKey, $failures, 86400); // 24小時
    }

    /**
     * 儲存到備用儲存
     *
     * @param \Throwable $exception
     * @return void
     */
    protected function saveToBackupStorage(\Throwable $exception): void
    {
        try {
            $backupFile = storage_path('logs/failed_activity_' . date('Y-m-d-H-i-s') . '_' . uniqid() . '.json');
            
            $backupData = [
                'timestamp' => now()->toISOString(),
                'error' => $exception->getMessage(),
                'error_type' => get_class($exception),
                'activity_data' => $this->activityData,
                'job_info' => [
                    'job_id' => $this->job?->getJobId(),
                    'queue' => $this->job?->getQueue(),
                    'attempts' => $this->attempts(),
                ],
            ];
            
            file_put_contents($backupFile, json_encode($backupData, JSON_PRETTY_PRINT));
            
            Log::info('失敗的活動記錄已儲存到備用檔案', [
                'backup_file' => $backupFile,
                'activity_type' => $this->activityData['type'] ?? 'unknown',
            ]);
            
        } catch (\Exception $e) {
            Log::error('無法儲存失敗的活動記錄到備用檔案', [
                'error' => $e->getMessage(),
                'activity_type' => $this->activityData['type'] ?? 'unknown',
            ]);
        }
    }

    /**
     * 發送失敗通知
     *
     * @param \Throwable $exception
     * @return void
     */
    protected function sendFailureNotification(\Throwable $exception): void
    {
        // 只對高風險活動發送通知
        $riskLevel = $this->activityData['risk_level'] ?? 1;
        
        if ($riskLevel >= 7) {
            try {
                // 這裡可以整合通知系統
                Log::critical('高風險活動記錄處理永久失敗', [
                    'activity_type' => $this->activityData['type'] ?? 'unknown',
                    'risk_level' => $riskLevel,
                    'error' => $exception->getMessage(),
                    'user_id' => $this->activityData['user_id'] ?? null,
                ]);
                
                // TODO: 發送郵件或其他通知
                
            } catch (\Exception $e) {
                Log::error('無法發送失敗通知', ['error' => $e->getMessage()]);
            }
        }
    }
}