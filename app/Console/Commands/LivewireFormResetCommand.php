<?php

namespace App\Console\Commands;

use App\Services\LivewireFormReset\FixExecutor;
use App\Services\LivewireFormReset\BatchProcessor;
use App\Services\LivewireFormReset\ProgressMonitor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Livewire 表單重置修復命令
 * 
 * 提供命令列介面來執行批次修復操作
 */
class LivewireFormResetCommand extends Command
{
    /**
     * 命令名稱和簽名
     */
    protected $signature = 'livewire:fix-form-reset 
                            {--mode=batch : 執行模式 (single|batch|priority)}
                            {--component= : 指定要修復的元件名稱 (僅限 single 模式)}
                            {--parallel : 啟用並行處理}
                            {--dry-run : 乾跑模式，不實際修改檔案}
                            {--report : 生成詳細報告}
                            {--monitor : 啟用進度監控}
                            {--priority= : 指定優先級 (very_high|high|medium|low|very_low)}
                            {--batch-size=10 : 批次大小}
                            {--max-parallel=3 : 最大並行數}
                            {--delay=0 : 批次間延遲（秒）}
                            {--retry=2 : 重試次數}
                            {--output-format=table : 輸出格式 (table|json|csv)}';

    /**
     * 命令描述
     */
    protected $description = '修復 Livewire 元件中的表單重置功能問題';

    /**
     * 修復執行器
     */
    protected FixExecutor $executor;

    /**
     * 批次處理器
     */
    protected BatchProcessor $batchProcessor;

    /**
     * 進度監控器
     */
    protected ProgressMonitor $progressMonitor;

    /**
     * 建構函式
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->executor = new FixExecutor();
        $this->batchProcessor = new BatchProcessor();
        $this->progressMonitor = new ProgressMonitor();
    }

    /**
     * 執行命令
     */
    public function handle(): int
    {
        $this->info('🔧 Livewire 表單重置修復工具');
        $this->info('=====================================');

        try {
            $mode = $this->option('mode');
            
            switch ($mode) {
                case 'single':
                    return $this->handleSingleMode();
                    
                case 'batch':
                    return $this->handleBatchMode();
                    
                case 'priority':
                    return $this->handlePriorityMode();
                    
                default:
                    $this->error("不支援的執行模式: {$mode}");
                    return 1;
            }

        } catch (\Exception $e) {
            $this->error("執行過程中發生錯誤: {$e->getMessage()}");
            Log::error('LivewireFormResetCommand 執行失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }

    /**
     * 處理單一元件模式
     */
    protected function handleSingleMode(): int
    {
        $componentName = $this->option('component');
        
        if (!$componentName) {
            $this->error('單一元件模式需要指定 --component 參數');
            return 1;
        }

        $this->info("🔍 搜尋元件: {$componentName}");
        
        // 這裡需要實作根據名稱搜尋元件的邏輯
        $componentInfo = $this->findComponentByName($componentName);
        
        if (!$componentInfo) {
            $this->error("找不到元件: {$componentName}");
            return 1;
        }

        $this->info("📝 開始修復元件: {$componentInfo['class_name']}");
        
        if ($this->option('dry-run')) {
            $this->warn('🔍 乾跑模式：不會實際修改檔案');
        }

        $options = [
            'dry_run' => $this->option('dry-run'),
        ];

        $result = $this->executor->executeSingleFix($componentInfo, $options);
        
        $this->displaySingleResult($result);
        
        return $result['status'] === 'success' ? 0 : 1;
    }

    /**
     * 處理批次模式
     */
    protected function handleBatchMode(): int
    {
        $this->info('🔄 開始批次修復模式');
        
        $sessionId = null;
        if ($this->option('monitor')) {
            $sessionId = $this->progressMonitor->startMonitoringSession([
                'command_options' => $this->options(),
            ]);
            $this->info("📊 監控會話已啟動: {$sessionId}");
        }

        $options = [
            'execution_mode' => $this->option('parallel') ? 'parallel' : 'sequential',
            'dry_run' => $this->option('dry-run'),
            'batch_size' => (int) $this->option('batch-size'),
            'max_parallel' => (int) $this->option('max-parallel'),
            'delay_between_fixes' => (int) $this->option('delay'),
            'retry_attempts' => (int) $this->option('retry'),
        ];

        if ($this->option('dry-run')) {
            $this->warn('🔍 乾跑模式：不會實際修改檔案');
        }

        $result = $this->executor->executeFullFix($options);
        
        $this->displayBatchResult($result);
        
        if ($this->option('monitor') && $sessionId) {
            $report = $this->progressMonitor->endMonitoringSession();
            
            if ($this->option('report')) {
                $this->displayDetailedReport($report);
            }
        }

        return $result['status'] === 'completed' ? 0 : 1;
    }

    /**
     * 處理優先級模式
     */
    protected function handlePriorityMode(): int
    {
        $this->info('⭐ 開始優先級批次處理模式');
        
        $sessionId = null;
        if ($this->option('monitor')) {
            $sessionId = $this->progressMonitor->startMonitoringSession([
                'command_options' => $this->options(),
            ]);
            $this->info("📊 監控會話已啟動: {$sessionId}");
        }

        $options = [
            'pause_on_error' => false,
            'max_consecutive_failures' => 5,
            'batch_delay' => (int) $this->option('delay'),
            'enable_notifications' => true,
            'save_progress' => true,
            'dry_run' => $this->option('dry-run'),
        ];

        if ($this->option('dry-run')) {
            $this->warn('🔍 乾跑模式：不會實際修改檔案');
        }

        $priority = $this->option('priority');
        if ($priority) {
            $this->info("🎯 僅處理 {$priority} 優先級元件");
            $options['priority_filter'] = $priority;
        }

        $result = $this->batchProcessor->processByPriority($options);
        
        $this->displayPriorityResult($result);
        
        if ($this->option('monitor') && $sessionId) {
            $report = $this->progressMonitor->endMonitoringSession();
            
            if ($this->option('report')) {
                $this->displayDetailedReport($report);
            }
        }

        return $result['status'] === 'completed' ? 0 : 1;
    }

    /**
     * 根據名稱搜尋元件
     */
    protected function findComponentByName(string $componentName): ?array
    {
        // 這裡需要實作搜尋邏輯
        // 暫時返回 null，實際實作需要掃描元件並找到匹配的
        return null;
    }

    /**
     * 顯示單一結果
     */
    protected function displaySingleResult(array $result): void
    {
        $this->newLine();
        $this->info('📋 修復結果');
        $this->info('===========');
        
        $status = $result['status'];
        $statusIcon = $status === 'success' ? '✅' : '❌';
        
        $this->line("{$statusIcon} 元件: {$result['component']}");
        $this->line("📊 狀態: {$status}");
        $this->line("🔍 發現問題: {$result['issues_found']}");
        $this->line("🔧 應用修復: {$result['fixes_applied']}");
        $this->line("⏱️  執行時間: {$result['execution_time']}ms");
        
        if (isset($result['error'])) {
            $this->error("❌ 錯誤: {$result['error']}");
        }
        
        if (isset($result['validation_passed'])) {
            $validationIcon = $result['validation_passed'] ? '✅' : '❌';
            $this->line("{$validationIcon} 驗證: " . ($result['validation_passed'] ? '通過' : '失敗'));
        }
    }

    /**
     * 顯示批次結果
     */
    protected function displayBatchResult(array $result): void
    {
        $this->newLine();
        $this->info('📋 批次修復結果');
        $this->info('================');
        
        $headers = ['指標', '數值'];
        $rows = [
            ['狀態', $result['status']],
            ['總元件數', $result['total_components']],
            ['已處理', $result['processed_components']],
            ['成功修復', $result['successful_fixes']],
            ['修復失敗', $result['failed_fixes']],
            ['成功率', $result['performance_metrics']['success_rate'] . '%'],
            ['執行時間', $this->formatDuration($result['execution_time'])],
            ['平均時間/元件', round($result['performance_metrics']['average_time_per_component'], 2) . 's'],
            ['錯誤數量', $result['error_count']],
        ];
        
        $this->table($headers, $rows);
        
        if ($result['error_count'] > 0) {
            $this->warn("⚠️  發現 {$result['error_count']} 個錯誤，請檢查日誌");
        }
    }

    /**
     * 顯示優先級結果
     */
    protected function displayPriorityResult(array $result): void
    {
        $this->newLine();
        $this->info('📋 優先級批次處理結果');
        $this->info('======================');
        
        $headers = ['指標', '數值'];
        $rows = [
            ['狀態', $result['status']],
            ['總元件數', $result['total_components']],
            ['已處理', $result['processed_components']],
            ['成功批次', $result['successful_batches']],
            ['失敗批次', $result['failed_batches']],
            ['成功率', $result['success_rate'] . '%'],
            ['執行時間', $this->formatDuration($result['execution_time'])],
            ['重試佇列', $result['retry_queue_size']],
            ['錯誤數量', $result['error_count']],
        ];
        
        $this->table($headers, $rows);
        
        // 顯示批次詳情
        if (!empty($result['batch_results'])) {
            $this->newLine();
            $this->info('📊 批次詳情');
            
            $batchHeaders = ['批次ID', '優先級', '元件數', '成功', '失敗', '執行時間'];
            $batchRows = [];
            
            foreach ($result['batch_results'] as $batch) {
                $batchRows[] = [
                    $batch['batch_id'],
                    $batch['priority_level'],
                    $batch['component_count'],
                    $batch['successful_fixes'],
                    $batch['failed_fixes'],
                    round($batch['execution_time'], 2) . 'ms',
                ];
            }
            
            $this->table($batchHeaders, $batchRows);
        }
    }

    /**
     * 顯示詳細報告
     */
    protected function displayDetailedReport(array $report): void
    {
        $this->newLine();
        $this->info('📊 詳細監控報告');
        $this->info('================');
        
        $summary = $report['executive_summary'];
        
        $headers = ['指標', '數值'];
        $rows = [
            ['會話持續時間', $this->formatDuration($summary['session_duration'])],
            ['總元件數', $summary['total_components']],
            ['已處理元件', $summary['processed_components']],
            ['成功率', round($summary['success_rate'], 2) . '%'],
            ['平均處理時間', round($summary['avg_processing_time'], 2) . 'ms'],
            ['峰值記憶體', $this->formatBytes($summary['peak_memory_usage'])],
            ['總錯誤數', $summary['total_errors']],
        ];
        
        $this->table($headers, $rows);
        
        if ($this->option('output-format') === 'json') {
            $this->newLine();
            $this->line('📄 完整報告 (JSON):');
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * 格式化持續時間
     */
    protected function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}秒";
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $remainingSeconds = $seconds % 60;
            return "{$minutes}分{$remainingSeconds}秒";
        } else {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            return "{$hours}小時{$minutes}分";
        }
    }

    /**
     * 格式化位元組
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
}