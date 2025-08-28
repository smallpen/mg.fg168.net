<?php

namespace App\Services\LivewireFormReset;

use App\Services\LivewireFormReset\FixExecutor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Exception;

/**
 * 批次處理器
 * 
 * 負責按優先級分批處理 Livewire 元件修復，
 * 提供進度監控、失敗重試和錯誤恢復機制
 */
class BatchProcessor
{
    /**
     * 修復執行器
     */
    protected FixExecutor $executor;

    /**
     * 元件分類器
     */
    protected ComponentClassifier $classifier;

    /**
     * 批次處理狀態
     */
    protected array $batchState = [
        'status' => 'idle', // idle, running, paused, completed, failed
        'current_batch' => 0,
        'total_batches' => 0,
        'processed_components' => 0,
        'total_components' => 0,
        'successful_batches' => 0,
        'failed_batches' => 0,
        'start_time' => null,
        'end_time' => null,
        'estimated_completion' => null,
        'current_priority_level' => null,
        'batch_results' => [],
        'errors' => [],
        'retry_queue' => [],
    ];

    /**
     * 優先級配置
     */
    protected array $priorityConfig = [
        'very_high' => [
            'min_score' => 8.0,
            'max_score' => 10.0,
            'batch_size' => 5,
            'max_parallel' => 2,
            'retry_attempts' => 3,
            'retry_delay' => 30,
        ],
        'high' => [
            'min_score' => 6.0,
            'max_score' => 8.0,
            'batch_size' => 8,
            'max_parallel' => 3,
            'retry_attempts' => 2,
            'retry_delay' => 60,
        ],
        'medium' => [
            'min_score' => 4.0,
            'max_score' => 6.0,
            'batch_size' => 10,
            'max_parallel' => 4,
            'retry_attempts' => 2,
            'retry_delay' => 120,
        ],
        'low' => [
            'min_score' => 2.0,
            'max_score' => 4.0,
            'batch_size' => 15,
            'max_parallel' => 5,
            'retry_attempts' => 1,
            'retry_delay' => 300,
        ],
        'very_low' => [
            'min_score' => 0.0,
            'max_score' => 2.0,
            'batch_size' => 20,
            'max_parallel' => 5,
            'retry_attempts' => 1,
            'retry_delay' => 600,
        ],
    ];

    /**
     * 處理選項
     */
    protected array $processingOptions = [
        'pause_on_error' => false,
        'max_consecutive_failures' => 5,
        'batch_delay' => 10, // 批次間延遲（秒）
        'enable_notifications' => true,
        'save_progress' => true,
        'progress_cache_key' => 'livewire_form_reset_progress',
    ];

    /**
     * 建構函式
     */
    public function __construct(
        FixExecutor $executor = null,
        ComponentClassifier $classifier = null
    ) {
        $this->executor = $executor ?? new FixExecutor();
        $this->classifier = $classifier ?? new ComponentClassifier();
    }

    /**
     * 按優先級處理所有元件
     */
    public function processByPriority(array $options = []): array
    {
        try {
            $this->initializeBatchProcessing($options);
            
            // 掃描和分類元件
            $this->logInfo('開始掃描和分類元件...');
            $components = $this->scanAndClassifyComponents($options);
            
            if ($components->isEmpty()) {
                $this->logInfo('未找到需要處理的元件');
                return $this->getBatchSummary();
            }

            // 按優先級分組
            $priorityGroups = $this->groupComponentsByPriority($components);
            $this->batchState['total_components'] = $components->count();
            
            // 按優先級順序處理
            foreach ($this->priorityConfig as $priorityLevel => $config) {
                if (!isset($priorityGroups[$priorityLevel]) || $priorityGroups[$priorityLevel]->isEmpty()) {
                    continue;
                }

                $this->batchState['current_priority_level'] = $priorityLevel;
                $this->logInfo("開始處理 {$priorityLevel} 優先級元件 ({$priorityGroups[$priorityLevel]->count()} 個)");
                
                $result = $this->processPriorityGroup($priorityGroups[$priorityLevel], $config, $options);
                
                if (!$result['success'] && $this->processingOptions['pause_on_error']) {
                    $this->logError("處理 {$priorityLevel} 優先級時發生錯誤，暫停處理");
                    break;
                }
            }

            // 處理重試佇列
            $this->processRetryQueue();

            $this->finalizeBatchProcessing();
            
            return $this->getBatchSummary();

        } catch (Exception $e) {
            $this->handleBatchError($e);
            return $this->getBatchSummary();
        }
    }

    /**
     * 處理特定優先級的元件組
     */
    public function processPriorityGroup(Collection $components, array $config, array $options = []): array
    {
        try {
            $batches = $components->chunk($config['batch_size']);
            $this->batchState['total_batches'] += $batches->count();
            
            $groupResults = [
                'success' => true,
                'processed_batches' => 0,
                'successful_batches' => 0,
                'failed_batches' => 0,
                'batch_results' => [],
            ];

            foreach ($batches as $batchIndex => $batch) {
                $this->batchState['current_batch']++;
                
                $this->logInfo("處理批次 {$this->batchState['current_batch']}/{$this->batchState['total_batches']} (優先級: {$this->batchState['current_priority_level']})");
                
                $batchResult = $this->processBatch($batch, $config, $options);
                $groupResults['batch_results'][] = $batchResult;
                $groupResults['processed_batches']++;
                
                if ($batchResult['success']) {
                    $groupResults['successful_batches']++;
                    $this->batchState['successful_batches']++;
                } else {
                    $groupResults['failed_batches']++;
                    $this->batchState['failed_batches']++;
                    
                    // 檢查是否需要暫停
                    if ($this->shouldPauseProcessing($batchResult)) {
                        $groupResults['success'] = false;
                        break;
                    }
                }

                // 批次間延遲
                if ($this->processingOptions['batch_delay'] > 0) {
                    sleep($this->processingOptions['batch_delay']);
                }

                // 儲存進度
                if ($this->processingOptions['save_progress']) {
                    $this->saveProgress();
                }

                // 發送進度通知
                if ($this->processingOptions['enable_notifications']) {
                    $this->sendProgressNotification();
                }
            }

            return $groupResults;

        } catch (Exception $e) {
            $this->logError("處理優先級組時發生錯誤: {$this->batchState['current_priority_level']}", $e);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'processed_batches' => 0,
                'successful_batches' => 0,
                'failed_batches' => 0,
                'batch_results' => [],
            ];
        }
    }

    /**
     * 處理單個批次
     */
    public function processBatch(Collection $batch, array $config, array $options = []): array
    {
        $batchStartTime = microtime(true);
        
        try {
            $batchResult = [
                'batch_id' => $this->batchState['current_batch'],
                'priority_level' => $this->batchState['current_priority_level'],
                'component_count' => $batch->count(),
                'success' => true,
                'processed_components' => 0,
                'successful_fixes' => 0,
                'failed_fixes' => 0,
                'execution_time' => 0,
                'component_results' => [],
                'errors' => [],
            ];

            // 根據配置決定處理方式
            if ($config['max_parallel'] > 1) {
                $componentResults = $this->processComponentsParallel($batch, $config, $options);
            } else {
                $componentResults = $this->processComponentsSequential($batch, $config, $options);
            }

            // 統計結果
            foreach ($componentResults as $result) {
                $batchResult['component_results'][] = $result;
                $batchResult['processed_components']++;
                $this->batchState['processed_components']++;

                if ($result['status'] === 'success') {
                    $batchResult['successful_fixes']++;
                } else {
                    $batchResult['failed_fixes']++;
                    $batchResult['errors'][] = $result['error'] ?? 'Unknown error';
                    
                    // 添加到重試佇列
                    $this->addToRetryQueue($result, $config);
                }
            }

            $batchResult['execution_time'] = round((microtime(true) - $batchStartTime) * 1000, 2);
            $batchResult['success'] = $batchResult['failed_fixes'] === 0;

            $this->batchState['batch_results'][] = $batchResult;
            
            $this->logInfo("批次 {$batchResult['batch_id']} 完成: {$batchResult['successful_fixes']}/{$batchResult['component_count']} 成功");

            return $batchResult;

        } catch (Exception $e) {
            $this->logError("處理批次時發生錯誤: {$this->batchState['current_batch']}", $e);
            
            return [
                'batch_id' => $this->batchState['current_batch'],
                'priority_level' => $this->batchState['current_priority_level'],
                'component_count' => $batch->count(),
                'success' => false,
                'error' => $e->getMessage(),
                'execution_time' => round((microtime(true) - $batchStartTime) * 1000, 2),
                'processed_components' => 0,
                'successful_fixes' => 0,
                'failed_fixes' => $batch->count(),
                'component_results' => [],
                'errors' => [$e->getMessage()],
            ];
        }
    }

    /**
     * 順序處理元件
     */
    protected function processComponentsSequential(Collection $components, array $config, array $options = []): array
    {
        $results = [];
        
        foreach ($components as $componentInfo) {
            try {
                $result = $this->executor->executeSingleFix($componentInfo, $options);
                $results[] = $result;
                
            } catch (Exception $e) {
                $results[] = [
                    'component' => $componentInfo['class_name'],
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                    'execution_time' => 0,
                ];
            }
        }
        
        return $results;
    }

    /**
     * 並行處理元件
     */
    protected function processComponentsParallel(Collection $components, array $config, array $options = []): array
    {
        // 將元件分組進行並行處理
        $parallelGroups = $components->chunk($config['max_parallel']);
        $allResults = [];
        
        foreach ($parallelGroups as $group) {
            $queueResult = $this->executor->queueFixTasks($group, $options);
            
            // 等待並行任務完成（簡化實作）
            sleep(30); // 實際應該監控佇列狀態
            
            // 收集結果（這裡需要根據實際佇列系統實作）
            foreach ($group as $componentInfo) {
                $allResults[] = [
                    'component' => $componentInfo['class_name'],
                    'status' => 'queued', // 實際狀態需要從佇列系統查詢
                    'execution_time' => 0,
                ];
            }
        }
        
        return $allResults;
    }

    /**
     * 處理重試佇列
     */
    public function processRetryQueue(): array
    {
        if (empty($this->batchState['retry_queue'])) {
            return ['processed' => 0, 'successful' => 0, 'failed' => 0];
        }

        $this->logInfo("開始處理重試佇列 ({$this->getRetryQueueCount()} 個元件)");
        
        $retryResults = [
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'results' => [],
        ];

        foreach ($this->batchState['retry_queue'] as $index => $retryItem) {
            if ($retryItem['attempts'] >= $retryItem['max_attempts']) {
                continue; // 已達最大重試次數
            }

            // 檢查重試延遲
            if (time() < $retryItem['next_retry_time']) {
                continue; // 還未到重試時間
            }

            try {
                $attemptNumber = $retryItem['attempts'] + 1;
                $this->logInfo("重試修復元件: {$retryItem['component']['class_name']} (第 {$attemptNumber} 次)");
                
                $result = $this->executor->executeSingleFix($retryItem['component'], $retryItem['options']);
                
                $retryResults['processed']++;
                $retryResults['results'][] = $result;

                if ($result['status'] === 'success') {
                    $retryResults['successful']++;
                    // 從重試佇列中移除
                    unset($this->batchState['retry_queue'][$index]);
                } else {
                    $retryResults['failed']++;
                    // 更新重試資訊
                    $this->batchState['retry_queue'][$index]['attempts']++;
                    $this->batchState['retry_queue'][$index]['next_retry_time'] = time() + $retryItem['retry_delay'];
                    $this->batchState['retry_queue'][$index]['last_error'] = $result['error'] ?? 'Unknown error';
                }

            } catch (Exception $e) {
                $retryResults['processed']++;
                $retryResults['failed']++;
                
                // 更新重試資訊
                $this->batchState['retry_queue'][$index]['attempts']++;
                $this->batchState['retry_queue'][$index]['next_retry_time'] = time() + $retryItem['retry_delay'];
                $this->batchState['retry_queue'][$index]['last_error'] = $e->getMessage();
                
                $this->logError("重試修復失敗: {$retryItem['component']['class_name']}", $e);
            }
        }

        // 清理已完成的重試項目
        $this->batchState['retry_queue'] = array_values(array_filter($this->batchState['retry_queue']));

        $this->logInfo("重試佇列處理完成: {$retryResults['successful']}/{$retryResults['processed']} 成功");
        
        return $retryResults;
    }

    /**
     * 建立進度監控和通知
     */
    public function getProgressMonitoring(): array
    {
        $progress = $this->calculateProgress();
        
        return [
            'current_status' => $this->batchState['status'],
            'overall_progress' => $progress,
            'current_batch' => $this->batchState['current_batch'],
            'total_batches' => $this->batchState['total_batches'],
            'current_priority' => $this->batchState['current_priority_level'],
            'processed_components' => $this->batchState['processed_components'],
            'total_components' => $this->batchState['total_components'],
            'success_rate' => $this->calculateSuccessRate(),
            'estimated_completion' => $this->batchState['estimated_completion'],
            'retry_queue_size' => $this->getRetryQueueCount(),
            'recent_errors' => $this->getRecentErrors(5),
        ];
    }

    /**
     * 實作失敗重試和錯誤恢復機制
     */
    public function implementErrorRecovery(): array
    {
        $recovery = [
            'actions_taken' => [],
            'recovered_components' => 0,
            'unrecoverable_components' => 0,
            'recommendations' => [],
        ];

        // 分析錯誤模式
        $errorPatterns = $this->analyzeErrorPatterns();
        
        foreach ($errorPatterns as $pattern => $components) {
            $recoveryAction = $this->getRecoveryAction($pattern);
            
            if ($recoveryAction) {
                $recovery['actions_taken'][] = [
                    'pattern' => $pattern,
                    'action' => $recoveryAction,
                    'affected_components' => count($components),
                ];
                
                // 執行恢復動作
                $recoveredCount = $this->executeRecoveryAction($recoveryAction, $components);
                $recovery['recovered_components'] += $recoveredCount;
                $recovery['unrecoverable_components'] += count($components) - $recoveredCount;
            }
        }

        // 產生建議
        $recovery['recommendations'] = $this->generateRecoveryRecommendations($errorPatterns);

        return $recovery;
    }

    /**
     * 掃描和分類元件
     */
    protected function scanAndClassifyComponents(array $options = []): Collection
    {
        $components = $this->executor->scanAllComponents($options);
        return $this->classifier->classifyComponents($components);
    }

    /**
     * 按優先級分組元件
     */
    protected function groupComponentsByPriority(Collection $components): array
    {
        $groups = [];
        
        foreach ($this->priorityConfig as $level => $config) {
            $groups[$level] = $components->filter(function ($component) use ($config) {
                $score = $component['classification']['priority_score'] ?? 0;
                return $score >= $config['min_score'] && $score < $config['max_score'];
            });
        }
        
        return $groups;
    }

    /**
     * 初始化批次處理
     */
    protected function initializeBatchProcessing(array $options = []): void
    {
        $this->processingOptions = array_merge($this->processingOptions, $options);
        
        $this->batchState = [
            'status' => 'running',
            'current_batch' => 0,
            'total_batches' => 0,
            'processed_components' => 0,
            'total_components' => 0,
            'successful_batches' => 0,
            'failed_batches' => 0,
            'start_time' => now()->toISOString(),
            'end_time' => null,
            'estimated_completion' => null,
            'current_priority_level' => null,
            'batch_results' => [],
            'errors' => [],
            'retry_queue' => [],
        ];
        
        $this->logInfo('開始批次處理 Livewire 表單重置修復');
    }

    /**
     * 完成批次處理
     */
    protected function finalizeBatchProcessing(): void
    {
        $this->batchState['status'] = 'completed';
        $this->batchState['end_time'] = now()->toISOString();
        $this->batchState['current_priority_level'] = null;
        
        $this->logInfo('批次處理完成');
        
        // 清理進度快取
        if ($this->processingOptions['save_progress']) {
            Cache::forget($this->processingOptions['progress_cache_key']);
        }
    }

    /**
     * 處理批次錯誤
     */
    protected function handleBatchError(Exception $e): void
    {
        $this->batchState['status'] = 'failed';
        $this->batchState['end_time'] = now()->toISOString();
        $this->batchState['errors'][] = [
            'type' => 'batch_error',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'timestamp' => now()->toISOString(),
        ];
        
        $this->logError('批次處理發生錯誤', $e);
    }

    /**
     * 檢查是否應該暫停處理
     */
    protected function shouldPauseProcessing(array $batchResult): bool
    {
        // 檢查連續失敗次數
        $recentFailures = collect($this->batchState['batch_results'])
            ->reverse()
            ->take($this->processingOptions['max_consecutive_failures'])
            ->filter(fn($result) => !$result['success'])
            ->count();
        
        return $recentFailures >= $this->processingOptions['max_consecutive_failures'];
    }

    /**
     * 添加到重試佇列
     */
    protected function addToRetryQueue(array $result, array $config): void
    {
        $this->batchState['retry_queue'][] = [
            'component' => $result['component'] ?? [],
            'options' => [],
            'attempts' => 0,
            'max_attempts' => $config['retry_attempts'],
            'retry_delay' => $config['retry_delay'],
            'next_retry_time' => time() + $config['retry_delay'],
            'last_error' => $result['error'] ?? 'Unknown error',
            'added_at' => now()->toISOString(),
        ];
    }

    /**
     * 儲存進度
     */
    protected function saveProgress(): void
    {
        Cache::put(
            $this->processingOptions['progress_cache_key'],
            $this->batchState,
            now()->addHours(24)
        );
    }

    /**
     * 發送進度通知
     */
    protected function sendProgressNotification(): void
    {
        $progress = $this->calculateProgress();
        
        Event::dispatch('livewire.form.reset.progress', [
            'progress' => $progress,
            'current_batch' => $this->batchState['current_batch'],
            'total_batches' => $this->batchState['total_batches'],
            'current_priority' => $this->batchState['current_priority_level'],
        ]);
    }

    /**
     * 計算進度百分比
     */
    protected function calculateProgress(): float
    {
        if ($this->batchState['total_components'] === 0) {
            return 0.0;
        }
        
        return round(($this->batchState['processed_components'] / $this->batchState['total_components']) * 100, 2);
    }

    /**
     * 計算成功率
     */
    protected function calculateSuccessRate(): float
    {
        if ($this->batchState['processed_components'] === 0) {
            return 0.0;
        }
        
        $successfulComponents = array_sum(array_column($this->batchState['batch_results'], 'successful_fixes'));
        return round(($successfulComponents / $this->batchState['processed_components']) * 100, 2);
    }

    /**
     * 取得重試佇列數量
     */
    protected function getRetryQueueCount(): int
    {
        return count($this->batchState['retry_queue']);
    }

    /**
     * 取得最近錯誤
     */
    protected function getRecentErrors(int $limit = 5): array
    {
        return array_slice($this->batchState['errors'], -$limit);
    }

    /**
     * 分析錯誤模式
     */
    protected function analyzeErrorPatterns(): array
    {
        $patterns = [];
        
        foreach ($this->batchState['batch_results'] as $batchResult) {
            foreach ($batchResult['errors'] as $error) {
                // 簡化的錯誤模式識別
                if (strpos($error, 'syntax') !== false) {
                    $patterns['syntax_error'][] = $error;
                } elseif (strpos($error, 'permission') !== false) {
                    $patterns['permission_error'][] = $error;
                } elseif (strpos($error, 'file not found') !== false) {
                    $patterns['file_not_found'][] = $error;
                } else {
                    $patterns['unknown'][] = $error;
                }
            }
        }
        
        return $patterns;
    }

    /**
     * 取得恢復動作
     */
    protected function getRecoveryAction(string $pattern): ?string
    {
        $actions = [
            'syntax_error' => 'validate_and_fix_syntax',
            'permission_error' => 'fix_file_permissions',
            'file_not_found' => 'recreate_missing_files',
        ];
        
        return $actions[$pattern] ?? null;
    }

    /**
     * 執行恢復動作
     */
    protected function executeRecoveryAction(string $action, array $components): int
    {
        // 這裡應該實作具體的恢復邏輯
        $recoveredCount = 0;
        
        switch ($action) {
            case 'validate_and_fix_syntax':
                // 實作語法驗證和修復
                break;
                
            case 'fix_file_permissions':
                // 實作檔案權限修復
                break;
                
            case 'recreate_missing_files':
                // 實作遺失檔案重建
                break;
        }
        
        return $recoveredCount;
    }

    /**
     * 產生恢復建議
     */
    protected function generateRecoveryRecommendations(array $errorPatterns): array
    {
        $recommendations = [];
        
        foreach ($errorPatterns as $pattern => $errors) {
            switch ($pattern) {
                case 'syntax_error':
                    $recommendations[] = '建議執行程式碼語法檢查和自動修復';
                    break;
                    
                case 'permission_error':
                    $recommendations[] = '檢查檔案系統權限設定';
                    break;
                    
                case 'file_not_found':
                    $recommendations[] = '驗證專案檔案完整性';
                    break;
                    
                default:
                    $recommendations[] = '需要人工檢查和處理未知錯誤';
            }
        }
        
        return array_unique($recommendations);
    }

    /**
     * 取得批次摘要
     */
    protected function getBatchSummary(): array
    {
        return [
            'status' => $this->batchState['status'],
            'total_components' => $this->batchState['total_components'],
            'processed_components' => $this->batchState['processed_components'],
            'successful_batches' => $this->batchState['successful_batches'],
            'failed_batches' => $this->batchState['failed_batches'],
            'success_rate' => $this->calculateSuccessRate(),
            'execution_time' => $this->calculateExecutionTime(),
            'retry_queue_size' => $this->getRetryQueueCount(),
            'error_count' => count($this->batchState['errors']),
            'batch_results' => $this->batchState['batch_results'],
        ];
    }

    /**
     * 計算執行時間
     */
    protected function calculateExecutionTime(): int
    {
        if (!$this->batchState['start_time']) {
            return 0;
        }
        
        $endTime = $this->batchState['end_time'] ?? now()->toISOString();
        return strtotime($endTime) - strtotime($this->batchState['start_time']);
    }

    /**
     * 記錄資訊
     */
    protected function logInfo(string $message): void
    {
        Log::info("[BatchProcessor] {$message}");
    }

    /**
     * 記錄錯誤
     */
    protected function logError(string $message, Exception $e = null): void
    {
        Log::error("[BatchProcessor] {$message}", [
            'exception' => $e?->getMessage(),
            'trace' => $e?->getTraceAsString(),
        ]);
    }
}