<?php

namespace App\Jobs;

use App\Models\Activity;
use App\Services\PerformanceMonitoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * 批量非同步活動記錄工作
 */
class LogActivitiesBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 工作最大嘗試次數
     *
     * @var int
     */
    public $tries = 3;

    /**
     * 工作超時時間（秒）
     *
     * @var int
     */
    public $timeout = 300;

    /**
     * 活動資料陣列
     *
     * @var array
     */
    protected array $activitiesData;

    /**
     * 批量處理大小
     *
     * @var int
     */
    protected int $chunkSize = 50;

    /**
     * 建立新的工作實例
     *
     * @param array $activitiesData
     */
    public function __construct(array $activitiesData)
    {
        $this->activitiesData = $activitiesData;
    }

    /**
     * 執行工作
     *
     * @return void
     */
    public function handle(): void
    {
        $startTime = microtime(true);
        $totalCount = count($this->activitiesData);
        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        try {
            Log::channel('activity')->info('開始批量處理活動記錄', [
                'total_count' => $totalCount,
                'job_id' => $this->job->getJobId(),
                'queue' => $this->job->getQueue(),
            ]);

            // 分批處理以避免記憶體和鎖定問題
            foreach (array_chunk($this->activitiesData, $this->chunkSize) as $chunkIndex => $chunk) {
                $chunkStartTime = microtime(true);
                
                try {
                    DB::beginTransaction();
                    
                    // 驗證資料完整性
                    $validatedChunk = $this->validateChunk($chunk);
                    
                    // 批量插入
                    Activity::insert($validatedChunk);
                    
                    DB::commit();
                    
                    $successCount += count($validatedChunk);
                    
                    // 記錄分塊處理時間
                    $chunkTime = (microtime(true) - $chunkStartTime) * 1000;
                    
                    Log::channel('activity')->debug('批量分塊處理完成', [
                        'chunk_index' => $chunkIndex,
                        'chunk_size' => count($validatedChunk),
                        'processing_time_ms' => $chunkTime,
                    ]);
                    
                } catch (\Exception $e) {
                    DB::rollBack();
                    
                    $failedCount += count($chunk);
                    $errors[] = [
                        'chunk_index' => $chunkIndex,
                        'error' => $e->getMessage(),
                        'chunk_size' => count($chunk),
                    ];
                    
                    Log::warning('批量分塊處理失敗', [
                        'chunk_index' => $chunkIndex,
                        'error' => $e->getMessage(),
                        'chunk_size' => count($chunk),
                    ]);
                    
                    // 嘗試單筆處理失敗的分塊
                    $this->processSingleItems($chunk, $successCount, $failedCount, $errors);
                }
                
                // 記憶體管理
                if ($chunkIndex % 10 === 0) {
                    $this->reportProgress($chunkIndex, $successCount, $failedCount);
                    gc_collect_cycles();
                }
            }

            $processingTime = (microtime(true) - $startTime) * 1000;
            
            // 記錄處理結果
            $this->recordProcessingResult($totalCount, $successCount, $failedCount, $processingTime, $errors);
            
            // 更新效能指標
            $this->updatePerformanceMetrics($processingTime, $successCount, $failedCount);
            
            Log::channel('activity')->info('批量活動記錄處理完成', [
                'total_count' => $totalCount,
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'processing_time_ms' => $processingTime,
                'success_rate' => $totalCount > 0 ? ($successCount / $totalCount) * 100 : 0,
            ]);
            
            // 如果有失敗的記錄，拋出異常以觸發重試機制
            if ($failedCount > 0 && $failedCount === $totalCount) {
                throw new \Exception("所有批量記錄處理失敗");
            }
            
        } catch (\Exception $e) {
            $processingTime = (microtime(true) - $startTime) * 1000;
            
            Log::error('批量活動記錄處理發生嚴重錯誤', [
                'error' => $e->getMessage(),
                'total_count' => $totalCount,
                'success_count' => $successCount,
                'failed_count' => $failedCount,
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
        $totalCount = count($this->activitiesData);
        
        Log::error('批量活動記錄工作永久失敗', [
            'error' => $exception->getMessage(),
            'total_count' => $totalCount,
            'attempts' => $this->attempts(),
            'job_id' => $this->job?->getJobId(),
            'queue' => $this->job?->getQueue(),
        ]);
        
        // 記錄失敗統計
        $this->recordFailureStats($totalCount, $exception);
        
        // 嘗試將失敗的活動記錄到備用儲存
        $this->saveToBackupStorage($this->activitiesData, $exception);
    }

    /**
     * 驗證分塊資料
     *
     * @param array $chunk
     * @return array
     */
    protected function validateChunk(array $chunk): array
    {
        $validated = [];
        
        foreach ($chunk as $activity) {
            // 基本欄位驗證
            if (empty($activity['type']) || empty($activity['description'])) {
                Log::warning('跳過無效的活動記錄', ['activity' => $activity]);
                continue;
            }
            
            // 確保必要欄位存在
            $activity['created_at'] = $activity['created_at'] ?? now();
            $activity['updated_at'] = $activity['updated_at'] ?? now();
            $activity['ip_address'] = $activity['ip_address'] ?? '127.0.0.1';
            $activity['result'] = $activity['result'] ?? 'success';
            $activity['risk_level'] = $activity['risk_level'] ?? 1;
            
            $validated[] = $activity;
        }
        
        return $validated;
    }

    /**
     * 單筆處理失敗的項目
     *
     * @param array $chunk
     * @param int &$successCount
     * @param int &$failedCount
     * @param array &$errors
     * @return void
     */
    protected function processSingleItems(array $chunk, int &$successCount, int &$failedCount, array &$errors): void
    {
        foreach ($chunk as $index => $activity) {
            try {
                Activity::create($activity);
                $successCount++;
            } catch (\Exception $e) {
                $failedCount++;
                $errors[] = [
                    'item_index' => $index,
                    'error' => $e->getMessage(),
                    'activity_type' => $activity['type'] ?? 'unknown',
                ];
                
                Log::warning('單筆活動記錄處理失敗', [
                    'activity_type' => $activity['type'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * 報告處理進度
     *
     * @param int $chunkIndex
     * @param int $successCount
     * @param int $failedCount
     * @return void
     */
    protected function reportProgress(int $chunkIndex, int $successCount, int $failedCount): void
    {
        $totalProcessed = $successCount + $failedCount;
        $totalCount = count($this->activitiesData);
        $progressPercent = $totalCount > 0 ? ($totalProcessed / $totalCount) * 100 : 0;
        
        Log::channel('activity')->info('批量處理進度報告', [
            'chunk_index' => $chunkIndex,
            'progress_percent' => round($progressPercent, 2),
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
        ]);
    }

    /**
     * 記錄處理結果
     *
     * @param int $totalCount
     * @param int $successCount
     * @param int $failedCount
     * @param float $processingTime
     * @param array $errors
     * @return void
     */
    protected function recordProcessingResult(int $totalCount, int $successCount, int $failedCount, float $processingTime, array $errors): void
    {
        $cacheKey = 'activity_batch_stats:' . date('Y-m-d-H');
        $stats = Cache::get($cacheKey, [
            'total_batches' => 0,
            'total_activities' => 0,
            'total_success' => 0,
            'total_failed' => 0,
            'total_processing_time' => 0,
            'error_summary' => [],
        ]);
        
        $stats['total_batches']++;
        $stats['total_activities'] += $totalCount;
        $stats['total_success'] += $successCount;
        $stats['total_failed'] += $failedCount;
        $stats['total_processing_time'] += $processingTime;
        
        // 統計錯誤類型
        foreach ($errors as $error) {
            $errorType = $this->categorizeError($error['error']);
            $stats['error_summary'][$errorType] = ($stats['error_summary'][$errorType] ?? 0) + 1;
        }
        
        Cache::put($cacheKey, $stats, 3600); // 1小時
    }

    /**
     * 更新效能指標
     *
     * @param float $processingTime
     * @param int $successCount
     * @param int $failedCount
     * @return void
     */
    protected function updatePerformanceMetrics(float $processingTime, int $successCount, int $failedCount): void
    {
        try {
            $performanceService = app(PerformanceMonitoringService::class);
            
            // 記錄批量處理效能
            $performanceService->recordMetric('batch_processing_time', $processingTime, [
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'batch_size' => count($this->activitiesData),
            ]);
            
            // 記錄成功率
            $totalCount = $successCount + $failedCount;
            if ($totalCount > 0) {
                $successRate = ($successCount / $totalCount) * 100;
                $performanceService->recordMetric('batch_success_rate', $successRate);
            }
            
        } catch (\Exception $e) {
            Log::warning('無法更新效能指標', ['error' => $e->getMessage()]);
        }
    }

    /**
     * 記錄失敗統計
     *
     * @param int $totalCount
     * @param \Throwable $exception
     * @return void
     */
    protected function recordFailureStats(int $totalCount, \Throwable $exception): void
    {
        $cacheKey = 'activity_batch_failures:' . date('Y-m-d');
        $failures = Cache::get($cacheKey, []);
        
        $failures[] = [
            'timestamp' => now()->toISOString(),
            'total_count' => $totalCount,
            'error' => $exception->getMessage(),
            'error_type' => get_class($exception),
            'job_id' => $this->job?->getJobId(),
        ];
        
        Cache::put($cacheKey, $failures, 86400); // 24小時
    }

    /**
     * 儲存到備用儲存
     *
     * @param array $activities
     * @param \Throwable $exception
     * @return void
     */
    protected function saveToBackupStorage(array $activities, \Throwable $exception): void
    {
        try {
            $backupFile = storage_path('logs/failed_activities_' . date('Y-m-d-H-i-s') . '.json');
            
            $backupData = [
                'timestamp' => now()->toISOString(),
                'error' => $exception->getMessage(),
                'activities' => $activities,
                'job_info' => [
                    'job_id' => $this->job?->getJobId(),
                    'queue' => $this->job?->getQueue(),
                    'attempts' => $this->attempts(),
                ],
            ];
            
            file_put_contents($backupFile, json_encode($backupData, JSON_PRETTY_PRINT));
            
            Log::info('失敗的活動記錄已儲存到備用檔案', [
                'backup_file' => $backupFile,
                'activity_count' => count($activities),
            ]);
            
        } catch (\Exception $e) {
            Log::error('無法儲存失敗的活動記錄到備用檔案', [
                'error' => $e->getMessage(),
                'activity_count' => count($activities),
            ]);
        }
    }

    /**
     * 分類錯誤類型
     *
     * @param string $errorMessage
     * @return string
     */
    protected function categorizeError(string $errorMessage): string
    {
        if (str_contains($errorMessage, 'Duplicate entry')) {
            return 'duplicate_entry';
        }
        
        if (str_contains($errorMessage, 'Data too long')) {
            return 'data_too_long';
        }
        
        if (str_contains($errorMessage, 'Connection')) {
            return 'connection_error';
        }
        
        if (str_contains($errorMessage, 'Deadlock')) {
            return 'deadlock';
        }
        
        return 'unknown_error';
    }
}