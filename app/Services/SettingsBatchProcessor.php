<?php

namespace App\Services;

use App\Events\SettingsBatchUpdated;
use App\Models\Setting;
use App\Models\SettingPerformanceMetric;
use App\Repositories\SettingsRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

/**
 * 設定批量處理服務
 * 
 * 提供大規模設定操作的批量處理功能，包括分批處理、佇列處理和進度追蹤
 */
class SettingsBatchProcessor
{
    /**
     * 設定資料存取介面
     */
    protected SettingsRepositoryInterface $settingsRepository;

    /**
     * 快取服務
     */
    protected SettingsCacheService $cacheService;

    /**
     * 預設批量大小
     */
    protected int $defaultBatchSize = 100;

    /**
     * 最大批量大小
     */
    protected int $maxBatchSize = 1000;

    /**
     * 處理狀態
     */
    protected array $processingStatus = [];

    /**
     * 建構函式
     */
    public function __construct(
        SettingsRepositoryInterface $settingsRepository,
        SettingsCacheService $cacheService
    ) {
        $this->settingsRepository = $settingsRepository;
        $this->cacheService = $cacheService;
    }

    /**
     * 批量更新設定
     * 
     * @param array $settings 設定陣列 ['key' => 'value']
     * @param array $options 處理選項
     * @return array 處理結果
     */
    public function batchUpdate(array $settings, array $options = []): array
    {
        $startTime = microtime(true);
        $batchId = $this->generateBatchId();
        
        $defaultOptions = [
            'batch_size' => $this->defaultBatchSize,
            'use_queue' => false,
            'validate' => true,
            'use_transaction' => true,
            'progress_callback' => null,
            'error_threshold' => 0.1, // 10% 錯誤率閾值
            'retry_failed' => true,
            'max_retries' => 3,
        ];
        
        $options = array_merge($defaultOptions, $options);
        $options['batch_size'] = min($options['batch_size'], $this->maxBatchSize);

        $results = [
            'batch_id' => $batchId,
            'success' => false,
            'total' => count($settings),
            'processed' => 0,
            'updated' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => [],
            'execution_time' => 0,
            'batches_processed' => 0,
        ];

        try {
            // 初始化處理狀態
            $this->initializeProcessingStatus($batchId, $results);

            // 驗證設定（如果啟用）
            if ($options['validate']) {
                $validationResults = $this->validateBatchSettings($settings);
                if (!empty($validationResults['errors'])) {
                    $results['errors'] = $validationResults['errors'];
                    $results['skipped'] = $validationResults['invalid_count'];
                    
                    // 移除無效設定
                    $settings = $validationResults['valid_settings'];
                    $results['total'] = count($settings);
                }
            }

            // 如果使用佇列處理
            if ($options['use_queue'] && count($settings) > $options['batch_size']) {
                return $this->queueBatchUpdate($batchId, $settings, $options);
            }

            // 分批處理設定
            $chunks = array_chunk($settings, $options['batch_size'], true);
            $results['total_batches'] = count($chunks);

            foreach ($chunks as $chunkIndex => $chunk) {
                $chunkResults = $this->processBatch(
                    $batchId,
                    $chunk,
                    $chunkIndex,
                    $options
                );

                // 更新結果
                $results['processed'] += $chunkResults['processed'];
                $results['updated'] += $chunkResults['updated'];
                $results['failed'] += $chunkResults['failed'];
                $results['errors'] = array_merge($results['errors'], $chunkResults['errors']);
                $results['batches_processed']++;

                // 更新處理狀態
                $this->updateProcessingStatus($batchId, $results);

                // 執行進度回調
                if ($options['progress_callback'] && is_callable($options['progress_callback'])) {
                    $options['progress_callback']($results, $chunkIndex + 1, count($chunks));
                }

                // 檢查錯誤率
                $errorRate = $results['processed'] > 0 ? $results['failed'] / $results['processed'] : 0;
                if ($errorRate > $options['error_threshold']) {
                    Log::warning('批量處理錯誤率過高，停止處理', [
                        'batch_id' => $batchId,
                        'error_rate' => $errorRate,
                        'threshold' => $options['error_threshold'],
                    ]);
                    break;
                }
            }

            // 重試失敗的項目（如果啟用）
            if ($options['retry_failed'] && $results['failed'] > 0) {
                $retryResults = $this->retryFailedItems($batchId, $options);
                $results['updated'] += $retryResults['updated'];
                $results['failed'] -= $retryResults['updated'];
            }

            $results['success'] = $results['failed'] === 0;
            $results['execution_time'] = (microtime(true) - $startTime) * 1000;

            // 清除快取
            $this->cacheService->flush(SettingsCacheService::class . '*');

            // 觸發批量更新事件
            $affectedCategories = $this->getAffectedCategories(array_keys($settings));
            event(new SettingsBatchUpdated($settings, $affectedCategories));

            // 記錄效能指標
            $this->recordBatchMetric($batchId, $results);

            // 清理處理狀態
            $this->cleanupProcessingStatus($batchId);

            return $results;

        } catch (\Exception $e) {
            $results['errors'][] = $e->getMessage();
            $results['execution_time'] = (microtime(true) - $startTime) * 1000;

            Log::error('批量更新設定失敗', [
                'batch_id' => $batchId,
                'settings_count' => count($settings),
                'error' => $e->getMessage(),
                'execution_time' => $results['execution_time'],
            ]);

            // 記錄錯誤指標
            SettingPerformanceMetric::record(
                'error',
                'batch_update_error',
                $results['execution_time'],
                'ms',
                ['batch_id' => $batchId, 'error' => $e->getMessage()]
            );

            throw $e;
        }
    }

    /**
     * 處理單一批次
     * 
     * @param string $batchId 批次 ID
     * @param array $chunk 設定塊
     * @param int $chunkIndex 塊索引
     * @param array $options 處理選項
     * @return array 處理結果
     */
    protected function processBatch(string $batchId, array $chunk, int $chunkIndex, array $options): array
    {
        $startTime = microtime(true);
        $results = [
            'processed' => 0,
            'updated' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        try {
            // 開始事務（如果啟用）
            if ($options['use_transaction']) {
                DB::beginTransaction();
            }

            foreach ($chunk as $key => $value) {
                try {
                    $results['processed']++;
                    
                    if ($this->settingsRepository->updateSetting($key, $value)) {
                        $results['updated']++;
                        
                        // 更新快取
                        $setting = $this->settingsRepository->getSetting($key);
                        if ($setting) {
                            $this->cacheService->set($key, $setting);
                        }
                    } else {
                        $results['failed']++;
                        $results['errors'][] = "更新設定失敗: {$key}";
                    }
                    
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "處理設定 {$key} 時發生錯誤: " . $e->getMessage();
                    
                    Log::warning('批次處理單項失敗', [
                        'batch_id' => $batchId,
                        'chunk_index' => $chunkIndex,
                        'setting_key' => $key,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // 提交事務
            if ($options['use_transaction']) {
                DB::commit();
            }

            // 記錄批次效能
            $executionTime = (microtime(true) - $startTime) * 1000;
            SettingPerformanceMetric::record(
                'batch_chunk',
                'process_batch_chunk',
                $executionTime,
                'ms',
                [
                    'batch_id' => $batchId,
                    'chunk_index' => $chunkIndex,
                    'chunk_size' => count($chunk),
                    'updated' => $results['updated'],
                    'failed' => $results['failed'],
                ]
            );

            return $results;

        } catch (\Exception $e) {
            if ($options['use_transaction']) {
                DB::rollBack();
            }

            $results['failed'] = count($chunk);
            $results['errors'][] = "批次處理失敗: " . $e->getMessage();

            Log::error('批次處理失敗', [
                'batch_id' => $batchId,
                'chunk_index' => $chunkIndex,
                'chunk_size' => count($chunk),
                'error' => $e->getMessage(),
            ]);

            return $results;
        }
    }

    /**
     * 佇列批量更新
     * 
     * @param string $batchId 批次 ID
     * @param array $settings 設定陣列
     * @param array $options 處理選項
     * @return array 處理結果
     */
    protected function queueBatchUpdate(string $batchId, array $settings, array $options): array
    {
        $chunks = array_chunk($settings, $options['batch_size'], true);
        $jobIds = [];

        foreach ($chunks as $chunkIndex => $chunk) {
            $job = new \App\Jobs\ProcessSettingsBatch($batchId, $chunk, $chunkIndex, $options);
            $jobIds[] = Queue::push($job);
        }

        return [
            'batch_id' => $batchId,
            'success' => true,
            'queued' => true,
            'total' => count($settings),
            'total_batches' => count($chunks),
            'job_ids' => $jobIds,
            'message' => '批量更新已加入佇列處理',
        ];
    }

    /**
     * 重試失敗的項目
     * 
     * @param string $batchId 批次 ID
     * @param array $options 處理選項
     * @return array 重試結果
     */
    protected function retryFailedItems(string $batchId, array $options): array
    {
        $results = ['updated' => 0, 'still_failed' => 0];
        
        // 這裡應該從處理狀態中取得失敗的項目
        // 為了簡化，這裡只是一個示例實作
        
        return $results;
    }

    /**
     * 驗證批量設定
     * 
     * @param array $settings 設定陣列
     * @return array 驗證結果
     */
    protected function validateBatchSettings(array $settings): array
    {
        $validSettings = [];
        $errors = [];
        $invalidCount = 0;

        foreach ($settings as $key => $value) {
            try {
                if ($this->settingsRepository->validateSetting($key, $value)) {
                    $validSettings[$key] = $value;
                } else {
                    $errors[] = "設定值驗證失敗: {$key}";
                    $invalidCount++;
                }
            } catch (\Exception $e) {
                $errors[] = "驗證設定 {$key} 時發生錯誤: " . $e->getMessage();
                $invalidCount++;
            }
        }

        return [
            'valid_settings' => $validSettings,
            'errors' => $errors,
            'invalid_count' => $invalidCount,
        ];
    }

    /**
     * 取得受影響的分類
     * 
     * @param array $keys 設定鍵值陣列
     * @return array 分類陣列
     */
    protected function getAffectedCategories(array $keys): array
    {
        $categories = [];
        
        foreach ($keys as $key) {
            $setting = $this->settingsRepository->getSetting($key);
            if ($setting && !in_array($setting->category, $categories)) {
                $categories[] = $setting->category;
            }
        }

        return $categories;
    }

    /**
     * 生成批次 ID
     * 
     * @return string 批次 ID
     */
    protected function generateBatchId(): string
    {
        return 'batch_' . uniqid() . '_' . time();
    }

    /**
     * 初始化處理狀態
     * 
     * @param string $batchId 批次 ID
     * @param array $initialStatus 初始狀態
     * @return void
     */
    protected function initializeProcessingStatus(string $batchId, array $initialStatus): void
    {
        $this->processingStatus[$batchId] = array_merge($initialStatus, [
            'started_at' => now(),
            'status' => 'processing',
        ]);
    }

    /**
     * 更新處理狀態
     * 
     * @param string $batchId 批次 ID
     * @param array $status 狀態更新
     * @return void
     */
    protected function updateProcessingStatus(string $batchId, array $status): void
    {
        if (isset($this->processingStatus[$batchId])) {
            $this->processingStatus[$batchId] = array_merge(
                $this->processingStatus[$batchId],
                $status,
                ['updated_at' => now()]
            );
        }
    }

    /**
     * 清理處理狀態
     * 
     * @param string $batchId 批次 ID
     * @return void
     */
    protected function cleanupProcessingStatus(string $batchId): void
    {
        unset($this->processingStatus[$batchId]);
    }

    /**
     * 取得處理狀態
     * 
     * @param string $batchId 批次 ID
     * @return array|null 處理狀態
     */
    public function getProcessingStatus(string $batchId): ?array
    {
        return $this->processingStatus[$batchId] ?? null;
    }

    /**
     * 記錄批次效能指標
     * 
     * @param string $batchId 批次 ID
     * @param array $results 處理結果
     * @return void
     */
    protected function recordBatchMetric(string $batchId, array $results): void
    {
        try {
            SettingPerformanceMetric::record(
                'batch_operation',
                'batch_update_complete',
                $results['execution_time'],
                'ms',
                [
                    'batch_id' => $batchId,
                    'total' => $results['total'],
                    'updated' => $results['updated'],
                    'failed' => $results['failed'],
                    'batches_processed' => $results['batches_processed'],
                    'success_rate' => $results['total'] > 0 ? round(($results['updated'] / $results['total']) * 100, 2) : 0,
                ]
            );
        } catch (\Exception $e) {
            Log::warning('記錄批次效能指標失敗', [
                'batch_id' => $batchId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 批量刪除設定
     * 
     * @param array $keys 設定鍵值陣列
     * @param array $options 處理選項
     * @return array 處理結果
     */
    public function batchDelete(array $keys, array $options = []): array
    {
        $startTime = microtime(true);
        $batchId = $this->generateBatchId();
        
        $defaultOptions = [
            'batch_size' => $this->defaultBatchSize,
            'use_transaction' => true,
            'skip_system_settings' => true,
        ];
        
        $options = array_merge($defaultOptions, $options);

        $results = [
            'batch_id' => $batchId,
            'success' => false,
            'total' => count($keys),
            'deleted' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => [],
            'execution_time' => 0,
        ];

        try {
            // 分批處理
            $chunks = array_chunk($keys, $options['batch_size']);

            foreach ($chunks as $chunk) {
                if ($options['use_transaction']) {
                    DB::beginTransaction();
                }

                try {
                    foreach ($chunk as $key) {
                        $setting = $this->settingsRepository->getSetting($key);
                        
                        if (!$setting) {
                            $results['skipped']++;
                            continue;
                        }

                        if ($options['skip_system_settings'] && $setting->is_system) {
                            $results['skipped']++;
                            continue;
                        }

                        if ($this->settingsRepository->deleteSetting($key)) {
                            $results['deleted']++;
                            $this->cacheService->forget($key);
                        } else {
                            $results['failed']++;
                            $results['errors'][] = "刪除設定失敗: {$key}";
                        }
                    }

                    if ($options['use_transaction']) {
                        DB::commit();
                    }

                } catch (\Exception $e) {
                    if ($options['use_transaction']) {
                        DB::rollBack();
                    }
                    
                    $results['failed'] += count($chunk);
                    $results['errors'][] = "批次刪除失敗: " . $e->getMessage();
                }
            }

            $results['success'] = $results['failed'] === 0;
            $results['execution_time'] = (microtime(true) - $startTime) * 1000;

            return $results;

        } catch (\Exception $e) {
            $results['errors'][] = $e->getMessage();
            $results['execution_time'] = (microtime(true) - $startTime) * 1000;

            Log::error('批量刪除設定失敗', [
                'batch_id' => $batchId,
                'keys_count' => count($keys),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * 設定預設批量大小
     * 
     * @param int $size 批量大小
     * @return self
     */
    public function setDefaultBatchSize(int $size): self
    {
        $this->defaultBatchSize = max(1, min($size, $this->maxBatchSize));
        return $this;
    }

    /**
     * 取得預設批量大小
     * 
     * @return int 批量大小
     */
    public function getDefaultBatchSize(): int
    {
        return $this->defaultBatchSize;
    }
}