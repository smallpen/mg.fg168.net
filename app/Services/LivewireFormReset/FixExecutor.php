<?php

namespace App\Services\LivewireFormReset;

use App\Services\LivewireFormReset\Contracts\FormResetFixInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use App\Jobs\LivewireFormResetJob;
use Exception;

/**
 * 修復執行器
 * 
 * 負責批次執行 Livewire 表單重置修復操作，
 * 支援任務佇列、並行處理和結果追蹤
 */
class FixExecutor
{
    /**
     * 元件掃描器
     */
    protected LivewireComponentScanner $scanner;

    /**
     * 問題識別器
     */
    protected IssueIdentifier $identifier;

    /**
     * 元件分類器
     */
    protected ComponentClassifier $classifier;

    /**
     * 修復策略對應表
     */
    protected array $fixStrategies = [
        'StandardFormResetFix' => StandardFormResetFix::class,
        'ListFilterResetFix' => ListFilterResetFix::class,
        'ModalFormResetFix' => ModalFormResetFix::class,
        'MonitoringControlFix' => MonitoringControlFix::class,
    ];

    /**
     * 執行狀態
     */
    protected array $executionState = [
        'status' => 'idle', // idle, running, completed, failed
        'total_components' => 0,
        'processed_components' => 0,
        'successful_fixes' => 0,
        'failed_fixes' => 0,
        'start_time' => null,
        'end_time' => null,
        'current_component' => null,
        'errors' => [],
        'results' => [],
    ];

    /**
     * 任務佇列配置
     */
    protected array $queueConfig = [
        'queue_name' => 'livewire-form-reset',
        'max_concurrent_jobs' => 3,
        'timeout' => 300, // 5 分鐘
        'retry_attempts' => 2,
        'retry_delay' => 60, // 1 分鐘
    ];

    /**
     * 建構函式
     */
    public function __construct(
        LivewireComponentScanner $scanner = null,
        IssueIdentifier $identifier = null,
        ComponentClassifier $classifier = null
    ) {
        $this->scanner = $scanner ?? new LivewireComponentScanner();
        $this->identifier = $identifier ?? new IssueIdentifier();
        $this->classifier = $classifier ?? new ComponentClassifier();
    }

    /**
     * 執行完整修復流程
     */
    public function executeFullFix(array $options = []): array
    {
        try {
            $this->initializeExecution();
            
            // 1. 掃描所有元件
            $this->logInfo('開始掃描 Livewire 元件...');
            $components = $this->scanAllComponents($options);
            
            if ($components->isEmpty()) {
                $this->logInfo('未找到需要修復的元件');
                return $this->getExecutionSummary();
            }

            $this->executionState['total_components'] = $components->count();
            $this->logInfo("找到 {$components->count()} 個元件需要處理");

            // 2. 分類和排序元件
            $this->logInfo('分析元件類型和優先級...');
            $classifiedComponents = $this->classifyAndPrioritizeComponents($components);

            // 3. 根據執行模式處理元件
            $executionMode = $options['execution_mode'] ?? 'sequential';
            
            if ($executionMode === 'parallel') {
                $results = $this->executeParallelFix($classifiedComponents, $options);
            } else {
                $results = $this->executeSequentialFix($classifiedComponents, $options);
            }

            $this->finalizeExecution();
            
            return $this->getExecutionSummary();

        } catch (Exception $e) {
            $this->handleExecutionError($e);
            return $this->getExecutionSummary();
        }
    }

    /**
     * 執行單個元件修復
     */
    public function executeSingleFix(array $componentInfo, array $options = []): array
    {
        try {
            $this->logInfo("開始修復元件: {$componentInfo['class_name']}");
            
            // 分類元件
            $classification = $this->classifier->classifyComponent($componentInfo);
            $componentInfo['classification'] = $classification;

            // 識別問題
            $issues = $this->identifier->identifyAllIssues($componentInfo);
            
            if (empty($issues)) {
                $this->logInfo("元件 {$componentInfo['class_name']} 無需修復");
                return [
                    'component' => $componentInfo['class_name'],
                    'status' => 'no_issues',
                    'issues_found' => 0,
                    'fixes_applied' => 0,
                    'execution_time' => 0,
                ];
            }

            // 執行修復
            $result = $this->fixComponent($componentInfo, $issues, $options);
            
            return $result;

        } catch (Exception $e) {
            $this->logError("修復元件失敗: {$componentInfo['class_name']}", $e);
            
            return [
                'component' => $componentInfo['class_name'],
                'status' => 'failed',
                'error' => $e->getMessage(),
                'issues_found' => count($issues ?? []),
                'fixes_applied' => 0,
                'execution_time' => 0,
            ];
        }
    }

    /**
     * 建立修復任務佇列
     */
    public function queueFixTasks(Collection $components, array $options = []): array
    {
        $queuedJobs = [];
        
        foreach ($components as $componentInfo) {
            try {
                $job = new LivewireFormResetJob($componentInfo, $options);
                
                $jobId = Queue::push(
                    $job,
                    $this->queueConfig['queue_name']
                );

                $queuedJobs[] = [
                    'job_id' => $jobId,
                    'component' => $componentInfo['class_name'],
                    'priority' => $componentInfo['classification']['priority_score'] ?? 0,
                    'estimated_time' => $componentInfo['classification']['estimated_fix_time'] ?? 30,
                    'queued_at' => now()->toISOString(),
                ];

                $this->logInfo("已將元件 {$componentInfo['class_name']} 加入修復佇列");

            } catch (Exception $e) {
                $this->logError("無法將元件 {$componentInfo['class_name']} 加入佇列", $e);
                
                $queuedJobs[] = [
                    'component' => $componentInfo['class_name'],
                    'status' => 'queue_failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'total_queued' => count($queuedJobs),
            'queue_name' => $this->queueConfig['queue_name'],
            'jobs' => $queuedJobs,
            'estimated_total_time' => array_sum(array_column($queuedJobs, 'estimated_time')),
        ];
    }

    /**
     * 取得修復結果追蹤
     */
    public function getFixResultTracking(): array
    {
        return [
            'execution_state' => $this->executionState,
            'queue_status' => $this->getQueueStatus(),
            'performance_metrics' => $this->getPerformanceMetrics(),
            'error_summary' => $this->getErrorSummary(),
        ];
    }

    /**
     * 產生修復報告
     */
    public function generateFixReport(): array
    {
        $summary = $this->getExecutionSummary();
        
        return [
            'report_generated_at' => now()->toISOString(),
            'execution_summary' => $summary,
            'detailed_results' => $this->executionState['results'],
            'performance_analysis' => $this->analyzePerformance(),
            'recommendations' => $this->generateRecommendations(),
            'next_steps' => $this->suggestNextSteps(),
        ];
    }

    /**
     * 掃描所有元件
     */
    protected function scanAllComponents(array $options = []): Collection
    {
        $scanOptions = array_merge([
            'include_patterns' => ['*'],
            'exclude_patterns' => ['Test*', '*Test'],
            'min_complexity' => 0,
            'require_reset_methods' => false,
        ], $options['scan_options'] ?? []);

        $components = $this->scanner->scanAllComponents($scanOptions);
        
        // 篩選需要修復的元件
        return $components->filter(function ($componentInfo) use ($scanOptions) {
            // 檢查是否有重置相關方法或問題
            $hasResetMethods = $componentInfo['has_reset_functionality'] ?? false;
            $hasIssues = !empty($this->identifier->identifyAllIssues($componentInfo));
            
            if ($scanOptions['require_reset_methods'] && !$hasResetMethods) {
                return false;
            }
            
            return $hasResetMethods || $hasIssues;
        });
    }

    /**
     * 分類和排序元件
     */
    protected function classifyAndPrioritizeComponents(Collection $components): Collection
    {
        return $this->classifier->classifyComponents($components);
    }

    /**
     * 執行順序修復
     */
    protected function executeSequentialFix(Collection $components, array $options = []): array
    {
        $results = [];
        
        foreach ($components as $componentInfo) {
            $this->executionState['current_component'] = $componentInfo['class_name'];
            
            try {
                $result = $this->fixComponent($componentInfo, [], $options);
                $results[] = $result;
                
                if ($result['status'] === 'success') {
                    $this->executionState['successful_fixes']++;
                } else {
                    $this->executionState['failed_fixes']++;
                }
                
                $this->executionState['processed_components']++;
                
                // 可選的延遲，避免系統負載過高
                if (isset($options['delay_between_fixes'])) {
                    sleep($options['delay_between_fixes']);
                }

            } catch (Exception $e) {
                $this->handleComponentError($componentInfo, $e);
                $this->executionState['failed_fixes']++;
                $this->executionState['processed_components']++;
            }
        }
        
        $this->executionState['results'] = $results;
        return $results;
    }

    /**
     * 執行並行修復
     */
    protected function executeParallelFix(Collection $components, array $options = []): array
    {
        // 將元件分組以進行並行處理
        $batches = $components->chunk($this->queueConfig['max_concurrent_jobs']);
        $allResults = [];
        
        foreach ($batches as $batch) {
            $batchResults = $this->queueFixTasks($batch, $options);
            $allResults = array_merge($allResults, $batchResults['jobs']);
            
            // 等待當前批次完成
            $this->waitForBatchCompletion($batchResults['jobs']);
        }
        
        $this->executionState['results'] = $allResults;
        return $allResults;
    }

    /**
     * 修復單個元件
     */
    protected function fixComponent(array $componentInfo, array $issues = [], array $options = []): array
    {
        $startTime = microtime(true);
        
        try {
            // 如果沒有提供問題列表，則重新識別
            if (empty($issues)) {
                $issues = $this->identifier->identifyAllIssues($componentInfo);
            }
            
            if (empty($issues)) {
                return [
                    'component' => $componentInfo['class_name'],
                    'status' => 'no_issues',
                    'issues_found' => 0,
                    'fixes_applied' => 0,
                    'execution_time' => round((microtime(true) - $startTime) * 1000, 2),
                ];
            }

            // 取得修復策略
            $fixer = $this->getFixerForComponent($componentInfo);
            
            if (!$fixer) {
                throw new Exception("無法找到適合的修復策略");
            }

            // 設定元件資訊並執行修復
            $fixer->setComponentInfo($componentInfo);
            $fixResult = $fixer->applyStandardFix();
            
            if (!$fixResult) {
                throw new Exception("修復執行失敗");
            }

            // 驗證修復結果
            $validationResult = $fixer->validateFix();
            
            $result = [
                'component' => $componentInfo['class_name'],
                'status' => $validationResult ? 'success' : 'validation_failed',
                'issues_found' => count($issues),
                'fixes_applied' => $fixer->getProgress()['completed_steps'] ?? 0,
                'execution_time' => round((microtime(true) - $startTime) * 1000, 2),
                'fixer_report' => $fixer->generateReport(),
                'validation_passed' => $validationResult,
            ];

            if (!$validationResult) {
                $result['validation_errors'] = $fixer->getProgress()['errors'] ?? [];
            }

            $this->logInfo("成功修復元件: {$componentInfo['class_name']}");
            
            return $result;

        } catch (Exception $e) {
            $this->logError("修復元件失敗: {$componentInfo['class_name']}", $e);
            
            return [
                'component' => $componentInfo['class_name'],
                'status' => 'failed',
                'error' => $e->getMessage(),
                'issues_found' => count($issues),
                'fixes_applied' => 0,
                'execution_time' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        }
    }

    /**
     * 取得適合的修復器
     */
    protected function getFixerForComponent(array $componentInfo): ?FormResetFixInterface
    {
        $classification = $componentInfo['classification'] ?? [];
        $strategyName = $classification['fix_strategy'] ?? 'StandardFormResetFix';
        
        if (!isset($this->fixStrategies[$strategyName])) {
            $strategyName = 'StandardFormResetFix';
        }
        
        $strategyClass = $this->fixStrategies[$strategyName];
        
        try {
            $fixer = new $strategyClass();
            
            // 檢查是否支援此元件
            if ($fixer->supports($componentInfo)) {
                return $fixer;
            }
            
            // 如果不支援，回退到標準修復
            return new StandardFormResetFix();
            
        } catch (Exception $e) {
            $this->logError("無法建立修復器: {$strategyName}", $e);
            return new StandardFormResetFix();
        }
    }

    /**
     * 初始化執行狀態
     */
    protected function initializeExecution(): void
    {
        $this->executionState = [
            'status' => 'running',
            'total_components' => 0,
            'processed_components' => 0,
            'successful_fixes' => 0,
            'failed_fixes' => 0,
            'start_time' => now()->toISOString(),
            'end_time' => null,
            'current_component' => null,
            'errors' => [],
            'results' => [],
        ];
        
        $this->logInfo('開始執行 Livewire 表單重置修復');
    }

    /**
     * 完成執行
     */
    protected function finalizeExecution(): void
    {
        $this->executionState['status'] = 'completed';
        $this->executionState['end_time'] = now()->toISOString();
        $this->executionState['current_component'] = null;
        
        $this->logInfo('Livewire 表單重置修復執行完成');
    }

    /**
     * 處理執行錯誤
     */
    protected function handleExecutionError(Exception $e): void
    {
        $this->executionState['status'] = 'failed';
        $this->executionState['end_time'] = now()->toISOString();
        $this->executionState['errors'][] = [
            'type' => 'execution_error',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'timestamp' => now()->toISOString(),
        ];
        
        $this->logError('執行過程中發生錯誤', $e);
    }

    /**
     * 處理元件錯誤
     */
    protected function handleComponentError(array $componentInfo, Exception $e): void
    {
        $this->executionState['errors'][] = [
            'type' => 'component_error',
            'component' => $componentInfo['class_name'],
            'message' => $e->getMessage(),
            'timestamp' => now()->toISOString(),
        ];
        
        $this->logError("處理元件時發生錯誤: {$componentInfo['class_name']}", $e);
    }

    /**
     * 等待批次完成
     */
    protected function waitForBatchCompletion(array $jobs): void
    {
        // 這裡可以實作等待邏輯，監控佇列中的任務狀態
        // 暫時使用簡單的延遲
        $maxWaitTime = $this->queueConfig['timeout'];
        $checkInterval = 10; // 10 秒檢查一次
        $waitedTime = 0;
        
        while ($waitedTime < $maxWaitTime) {
            // 檢查佇列狀態（這裡需要根據實際的佇列系統實作）
            $pendingJobs = $this->getPendingJobsCount();
            
            if ($pendingJobs === 0) {
                break;
            }
            
            sleep($checkInterval);
            $waitedTime += $checkInterval;
        }
    }

    /**
     * 取得佇列狀態
     */
    protected function getQueueStatus(): array
    {
        // 這裡需要根據實際的佇列系統實作
        return [
            'queue_name' => $this->queueConfig['queue_name'],
            'pending_jobs' => $this->getPendingJobsCount(),
            'failed_jobs' => $this->getFailedJobsCount(),
            'processed_jobs' => $this->getProcessedJobsCount(),
        ];
    }

    /**
     * 取得待處理任務數量
     */
    protected function getPendingJobsCount(): int
    {
        // 實際實作需要查詢佇列系統
        return 0;
    }

    /**
     * 取得失敗任務數量
     */
    protected function getFailedJobsCount(): int
    {
        // 實際實作需要查詢佇列系統
        return 0;
    }

    /**
     * 取得已處理任務數量
     */
    protected function getProcessedJobsCount(): int
    {
        // 實際實作需要查詢佇列系統
        return 0;
    }

    /**
     * 取得效能指標
     */
    protected function getPerformanceMetrics(): array
    {
        $startTime = $this->executionState['start_time'];
        $endTime = $this->executionState['end_time'] ?? now()->toISOString();
        
        $totalTime = strtotime($endTime) - strtotime($startTime);
        $processedComponents = $this->executionState['processed_components'];
        
        return [
            'total_execution_time' => $totalTime,
            'average_time_per_component' => $processedComponents > 0 ? round($totalTime / $processedComponents, 2) : 0,
            'components_per_minute' => $totalTime > 0 ? round(($processedComponents * 60) / $totalTime, 2) : 0,
            'success_rate' => $processedComponents > 0 ? round(($this->executionState['successful_fixes'] / $processedComponents) * 100, 2) : 0,
        ];
    }

    /**
     * 取得錯誤摘要
     */
    protected function getErrorSummary(): array
    {
        $errors = $this->executionState['errors'];
        
        return [
            'total_errors' => count($errors),
            'error_types' => array_count_values(array_column($errors, 'type')),
            'recent_errors' => array_slice($errors, -5), // 最近 5 個錯誤
        ];
    }

    /**
     * 取得執行摘要
     */
    protected function getExecutionSummary(): array
    {
        return [
            'status' => $this->executionState['status'],
            'total_components' => $this->executionState['total_components'],
            'processed_components' => $this->executionState['processed_components'],
            'successful_fixes' => $this->executionState['successful_fixes'],
            'failed_fixes' => $this->executionState['failed_fixes'],
            'execution_time' => $this->calculateExecutionTime(),
            'performance_metrics' => $this->getPerformanceMetrics(),
            'error_count' => count($this->executionState['errors']),
        ];
    }

    /**
     * 計算執行時間
     */
    protected function calculateExecutionTime(): int
    {
        if (!$this->executionState['start_time']) {
            return 0;
        }
        
        $endTime = $this->executionState['end_time'] ?? now()->toISOString();
        return strtotime($endTime) - strtotime($this->executionState['start_time']);
    }

    /**
     * 分析效能
     */
    protected function analyzePerformance(): array
    {
        $metrics = $this->getPerformanceMetrics();
        $results = $this->executionState['results'];
        
        return [
            'overall_metrics' => $metrics,
            'component_type_performance' => $this->analyzeComponentTypePerformance($results),
            'bottlenecks' => $this->identifyBottlenecks($results),
            'optimization_suggestions' => $this->generateOptimizationSuggestions($metrics),
        ];
    }

    /**
     * 分析元件類型效能
     */
    protected function analyzeComponentTypePerformance(array $results): array
    {
        $typePerformance = [];
        
        foreach ($results as $result) {
            $componentType = $result['fixer_report']['component']['type'] ?? 'UNKNOWN';
            
            if (!isset($typePerformance[$componentType])) {
                $typePerformance[$componentType] = [
                    'count' => 0,
                    'total_time' => 0,
                    'success_count' => 0,
                    'avg_time' => 0,
                    'success_rate' => 0,
                ];
            }
            
            $typePerformance[$componentType]['count']++;
            $typePerformance[$componentType]['total_time'] += $result['execution_time'] ?? 0;
            
            if ($result['status'] === 'success') {
                $typePerformance[$componentType]['success_count']++;
            }
        }
        
        // 計算平均值和成功率
        foreach ($typePerformance as $type => &$performance) {
            $performance['avg_time'] = round($performance['total_time'] / $performance['count'], 2);
            $performance['success_rate'] = round(($performance['success_count'] / $performance['count']) * 100, 2);
        }
        
        return $typePerformance;
    }

    /**
     * 識別瓶頸
     */
    protected function identifyBottlenecks(array $results): array
    {
        $bottlenecks = [];
        
        // 找出執行時間最長的元件
        $slowestComponents = collect($results)
            ->sortByDesc('execution_time')
            ->take(5)
            ->map(fn($result) => [
                'component' => $result['component'],
                'execution_time' => $result['execution_time'],
                'status' => $result['status'],
            ])
            ->toArray();
        
        if (!empty($slowestComponents)) {
            $bottlenecks['slowest_components'] = $slowestComponents;
        }
        
        // 找出失敗率最高的元件類型
        $failuresByType = collect($results)
            ->filter(fn($result) => $result['status'] !== 'success')
            ->groupBy(fn($result) => $result['fixer_report']['component']['type'] ?? 'UNKNOWN')
            ->map(fn($failures) => $failures->count())
            ->sortDesc()
            ->toArray();
        
        if (!empty($failuresByType)) {
            $bottlenecks['high_failure_types'] = $failuresByType;
        }
        
        return $bottlenecks;
    }

    /**
     * 產生優化建議
     */
    protected function generateOptimizationSuggestions(array $metrics): array
    {
        $suggestions = [];
        
        if ($metrics['success_rate'] < 80) {
            $suggestions[] = '成功率較低，建議檢查修復策略的適用性';
        }
        
        if ($metrics['average_time_per_component'] > 120) {
            $suggestions[] = '平均修復時間較長，考慮優化修復邏輯或使用並行處理';
        }
        
        if ($this->executionState['failed_fixes'] > 0) {
            $suggestions[] = '存在修復失敗的元件，建議檢查錯誤日誌並改進錯誤處理';
        }
        
        return $suggestions;
    }

    /**
     * 建議後續步驟
     */
    protected function suggestNextSteps(): array
    {
        $steps = [];
        
        if ($this->executionState['failed_fixes'] > 0) {
            $steps[] = '檢查並手動修復失敗的元件';
        }
        
        if ($this->executionState['successful_fixes'] > 0) {
            $steps[] = '執行測試驗證修復效果';
            $steps[] = '更新文檔和最佳實踐指南';
        }
        
        $steps[] = '建立持續監控機制';
        $steps[] = '定期執行修復檢查';
        
        return $steps;
    }

    /**
     * 記錄資訊
     */
    protected function logInfo(string $message): void
    {
        Log::info("[FixExecutor] {$message}");
    }

    /**
     * 產生建議
     */
    protected function generateRecommendations(): array
    {
        $recommendations = [];
        
        $metrics = $this->getPerformanceMetrics();
        
        if ($metrics['success_rate'] < 80) {
            $recommendations[] = '成功率較低，建議檢查修復策略的適用性';
        }
        
        if ($metrics['average_time_per_component'] > 120) {
            $recommendations[] = '平均修復時間較長，考慮優化修復邏輯或使用並行處理';
        }
        
        if ($this->executionState['failed_fixes'] > 0) {
            $recommendations[] = '存在修復失敗的元件，建議檢查錯誤日誌並改進錯誤處理';
        }
        
        if (count($this->executionState['errors']) > 0) {
            $recommendations[] = '發現執行錯誤，建議檢查系統環境和依賴';
        }
        
        return $recommendations;
    }

    /**
     * 記錄錯誤
     */
    protected function logError(string $message, Exception $e = null): void
    {
        Log::error("[FixExecutor] {$message}", [
            'exception' => $e?->getMessage(),
            'trace' => $e?->getTraceAsString(),
        ]);
    }
}