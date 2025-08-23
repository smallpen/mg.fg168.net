<?php

namespace App\Jobs;

use App\Models\SettingPerformanceMetric;
use App\Repositories\SettingsRepositoryInterface;
use App\Services\SettingsCacheService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 設定批次處理工作
 * 
 * 用於在佇列中處理大量設定更新操作
 */
class ProcessSettingsBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 批次 ID
     */
    public string $batchId;

    /**
     * 設定資料塊
     */
    public array $settingsChunk;

    /**
     * 塊索引
     */
    public int $chunkIndex;

    /**
     * 處理選項
     */
    public array $options;

    /**
     * 工作超時時間（秒）
     */
    public int $timeout = 300;

    /**
     * 最大重試次數
     */
    public int $tries = 3;

    /**
     * 重試延遲（秒）
     */
    public int $retryAfter = 60;

    /**
     * 建立新的工作實例
     *
     * @param string $batchId 批次 ID
     * @param array $settingsChunk 設定資料塊
     * @param int $chunkIndex 塊索引
     * @param array $options 處理選項
     */
    public function __construct(string $batchId, array $settingsChunk, int $chunkIndex, array $options = [])
    {
        $this->batchId = $batchId;
        $this->settingsChunk = $settingsChunk;
        $this->chunkIndex = $chunkIndex;
        $this->options = $options;
        
        // 設定佇列名稱
        $this->onQueue($options['queue'] ?? 'settings');
    }

    /**
     * 執行工作
     *
     * @param SettingsRepositoryInterface $settingsRepository
     * @param SettingsCacheService $cacheService
     * @return void
     */
    public function handle(
        SettingsRepositoryInterface $settingsRepository,
        SettingsCacheService $cacheService
    ): void {
        $startTime = microtime(true);
        
        Log::info('開始處理設定批次', [
            'batch_id' => $this->batchId,
            'chunk_index' => $this->chunkIndex,
            'chunk_size' => count($this->settingsChunk),
            'job_id' => $this->job->getJobId(),
        ]);

        $results = [
            'batch_id' => $this->batchId,
            'chunk_index' => $this->chunkIndex,
            'processed' => 0,
            'updated' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        try {
            // 開始事務（如果啟用）
            if ($this->options['use_transaction'] ?? true) {
                DB::beginTransaction();
            }

            // 處理設定塊中的每個設定
            foreach ($this->settingsChunk as $key => $value) {
                try {
                    $results['processed']++;
                    
                    // 驗證設定（如果啟用）
                    if (($this->options['validate'] ?? true) && !$settingsRepository->validateSetting($key, $value)) {
                        $results['failed']++;
                        $results['errors'][] = "設定值驗證失敗: {$key}";
                        continue;
                    }

                    // 更新設定
                    if ($settingsRepository->updateSetting($key, $value)) {
                        $results['updated']++;
                        
                        // 更新快取
                        $setting = $settingsRepository->getSetting($key);
                        if ($setting) {
                            $cacheService->set($key, $setting);
                        }
                        
                        Log::debug('設定更新成功', [
                            'batch_id' => $this->batchId,
                            'chunk_index' => $this->chunkIndex,
                            'setting_key' => $key,
                        ]);
                    } else {
                        $results['failed']++;
                        $results['errors'][] = "更新設定失敗: {$key}";
                        
                        Log::warning('設定更新失敗', [
                            'batch_id' => $this->batchId,
                            'chunk_index' => $this->chunkIndex,
                            'setting_key' => $key,
                        ]);
                    }
                    
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "處理設定 {$key} 時發生錯誤: " . $e->getMessage();
                    
                    Log::error('設定處理錯誤', [
                        'batch_id' => $this->batchId,
                        'chunk_index' => $this->chunkIndex,
                        'setting_key' => $key,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // 提交事務
            if ($this->options['use_transaction'] ?? true) {
                DB::commit();
            }

            $executionTime = (microtime(true) - $startTime) * 1000;
            $results['execution_time'] = $executionTime;

            // 記錄成功處理的效能指標
            SettingPerformanceMetric::record(
                'queue_batch',
                'process_settings_batch_success',
                $executionTime,
                'ms',
                [
                    'batch_id' => $this->batchId,
                    'chunk_index' => $this->chunkIndex,
                    'chunk_size' => count($this->settingsChunk),
                    'updated' => $results['updated'],
                    'failed' => $results['failed'],
                    'job_id' => $this->job->getJobId(),
                ]
            );

            Log::info('設定批次處理完成', array_merge($results, [
                'job_id' => $this->job->getJobId(),
            ]));

            // 如果有回調，執行回調
            if (isset($this->options['completion_callback']) && is_callable($this->options['completion_callback'])) {
                $this->options['completion_callback']($results);
            }

        } catch (\Exception $e) {
            // 回滾事務
            if (($this->options['use_transaction'] ?? true) && DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            $executionTime = (microtime(true) - $startTime) * 1000;

            // 記錄錯誤
            Log::error('設定批次處理失敗', [
                'batch_id' => $this->batchId,
                'chunk_index' => $this->chunkIndex,
                'chunk_size' => count($this->settingsChunk),
                'error' => $e->getMessage(),
                'execution_time' => $executionTime,
                'job_id' => $this->job->getJobId(),
                'attempt' => $this->attempts(),
            ]);

            // 記錄錯誤效能指標
            SettingPerformanceMetric::record(
                'queue_error',
                'process_settings_batch_error',
                $executionTime,
                'ms',
                [
                    'batch_id' => $this->batchId,
                    'chunk_index' => $this->chunkIndex,
                    'error' => $e->getMessage(),
                    'job_id' => $this->job->getJobId(),
                    'attempt' => $this->attempts(),
                ]
            );

            // 重新拋出例外以觸發重試機制
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
        Log::error('設定批次處理工作最終失敗', [
            'batch_id' => $this->batchId,
            'chunk_index' => $this->chunkIndex,
            'chunk_size' => count($this->settingsChunk),
            'error' => $exception->getMessage(),
            'job_id' => $this->job?->getJobId(),
            'attempts' => $this->attempts(),
        ]);

        // 記錄最終失敗的效能指標
        SettingPerformanceMetric::record(
            'queue_failed',
            'process_settings_batch_failed',
            0,
            'count',
            [
                'batch_id' => $this->batchId,
                'chunk_index' => $this->chunkIndex,
                'chunk_size' => count($this->settingsChunk),
                'error' => $exception->getMessage(),
                'job_id' => $this->job?->getJobId(),
                'final_attempt' => $this->attempts(),
            ]
        );

        // 如果有失敗回調，執行回調
        if (isset($this->options['failure_callback']) && is_callable($this->options['failure_callback'])) {
            $this->options['failure_callback']([
                'batch_id' => $this->batchId,
                'chunk_index' => $this->chunkIndex,
                'error' => $exception->getMessage(),
                'attempts' => $this->attempts(),
            ]);
        }
    }

    /**
     * 計算重試延遲時間
     *
     * @return int 延遲秒數
     */
    public function retryAfter(): int
    {
        // 指數退避：第一次重試 60 秒，第二次 120 秒，第三次 240 秒
        return $this->retryAfter * pow(2, $this->attempts() - 1);
    }

    /**
     * 取得工作的唯一 ID
     *
     * @return string
     */
    public function uniqueId(): string
    {
        return "settings_batch_{$this->batchId}_{$this->chunkIndex}";
    }

    /**
     * 取得工作標籤
     *
     * @return array
     */
    public function tags(): array
    {
        return [
            'settings',
            'batch',
            "batch:{$this->batchId}",
            "chunk:{$this->chunkIndex}",
        ];
    }

    /**
     * 判斷工作是否應該重試
     *
     * @param \Throwable $exception
     * @return bool
     */
    public function shouldRetry(\Throwable $exception): bool
    {
        // 某些錯誤不應該重試
        $nonRetryableErrors = [
            \Illuminate\Validation\ValidationException::class,
            \InvalidArgumentException::class,
        ];

        foreach ($nonRetryableErrors as $errorClass) {
            if ($exception instanceof $errorClass) {
                return false;
            }
        }

        return true;
    }

    /**
     * 取得工作的記憶體限制
     *
     * @return string
     */
    public function memory(): string
    {
        // 根據批次大小動態調整記憶體限制
        $chunkSize = count($this->settingsChunk);
        
        if ($chunkSize > 500) {
            return '512M';
        } elseif ($chunkSize > 100) {
            return '256M';
        } else {
            return '128M';
        }
    }

    /**
     * 取得工作的超時時間
     *
     * @return int
     */
    public function timeout(): int
    {
        // 根據批次大小動態調整超時時間
        $chunkSize = count($this->settingsChunk);
        $baseTimeout = 60; // 基礎超時時間 60 秒
        
        // 每個設定項目增加 1 秒超時時間
        return $baseTimeout + $chunkSize;
    }
}