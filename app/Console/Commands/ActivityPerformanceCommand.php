<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ActivityPerformanceManager;
use App\Services\ActivityPartitionService;
use App\Services\ActivityCacheService;
use App\Services\ActivityCompressionService;
use App\Services\ActivityQueryOptimizer;
use App\Services\DistributedActivityLogger;

/**
 * 活動記錄效能管理命令
 */
class ActivityPerformanceCommand extends Command
{
    /**
     * 命令名稱和簽名
     */
    protected $signature = 'activity:performance 
                            {action : 執行的動作 (optimize|report|maintenance|health|partition|cache|compress)}
                            {--days=7 : 報告天數}
                            {--force : 強制執行}
                            {--dry-run : 僅顯示將要執行的操作}';

    /**
     * 命令描述
     */
    protected $description = '管理活動記錄系統的效能優化功能';

    /**
     * 效能管理服務
     */
    protected ActivityPerformanceManager $performanceManager;

    /**
     * 建構函數
     */
    public function __construct(ActivityPerformanceManager $performanceManager)
    {
        parent::__construct();
        $this->performanceManager = $performanceManager;
    }

    /**
     * 執行命令
     */
    public function handle(): int
    {
        $action = $this->argument('action');
        
        $this->info("執行活動記錄效能操作: {$action}");
        
        try {
            return match ($action) {
                'optimize' => $this->handleOptimize(),
                'report' => $this->handleReport(),
                'maintenance' => $this->handleMaintenance(),
                'health' => $this->handleHealth(),
                'partition' => $this->handlePartition(),
                'cache' => $this->handleCache(),
                'compress' => $this->handleCompress(),
                default => $this->handleUnknownAction($action),
            };
            
        } catch (\Exception $e) {
            $this->error("執行失敗: {$e->getMessage()}");
            
            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            }
            
            return 1;
        }
    }

    /**
     * 處理最佳化操作
     */
    protected function handleOptimize(): int
    {
        $this->info('開始執行完整效能最佳化...');
        
        if ($this->option('dry-run')) {
            $this->warn('這是乾跑模式，不會實際執行操作');
            return 0;
        }
        
        $results = $this->performanceManager->performFullOptimization();
        
        $this->displayOptimizationResults($results);
        
        return empty($results['errors']) ? 0 : 1;
    }

    /**
     * 處理報告操作
     */
    protected function handleReport(): int
    {
        $days = (int) $this->option('days');
        
        $this->info("生成 {$days} 天的效能報告...");
        
        $report = $this->performanceManager->getPerformanceReport($days);
        
        $this->displayPerformanceReport($report);
        
        return 0;
    }

    /**
     * 處理維護操作
     */
    protected function handleMaintenance(): int
    {
        $this->info('執行自動維護...');
        
        if ($this->option('dry-run')) {
            $this->warn('這是乾跑模式，不會實際執行操作');
            return 0;
        }
        
        // 執行各種維護任務
        $results = [];
        
        // 分區維護
        if (config('activity-performance.partitioning.enabled')) {
            $partitionService = app(ActivityPartitionService::class);
            $results['partition'] = $partitionService->autoMaintenance();
        }
        
        // 快取維護
        if (config('activity-performance.caching.enabled')) {
            $cacheService = app(ActivityCacheService::class);
            $results['cache'] = $cacheService->optimizeCache();
        }
        
        // 壓縮維護
        if (config('activity-performance.compression.enabled')) {
            $compressionService = app(ActivityCompressionService::class);
            $results['compression'] = $compressionService->autoMaintenance();
        }
        
        $this->displayMaintenanceResults($results);
        
        return 0;
    }

    /**
     * 處理健康檢查操作
     */
    protected function handleHealth(): int
    {
        $this->info('檢查系統健康狀態...');
        
        $health = $this->performanceManager->monitorSystemHealth();
        
        $this->displayHealthReport($health);
        
        return $health['overall_status'] === 'healthy' ? 0 : 1;
    }

    /**
     * 處理分區操作
     */
    protected function handlePartition(): int
    {
        $this->info('執行分區管理...');
        
        $partitionService = app(ActivityPartitionService::class);
        
        if ($this->option('dry-run')) {
            $stats = $partitionService->getPartitionStats();
            $this->displayPartitionStats($stats);
            return 0;
        }
        
        $results = $partitionService->autoMaintenance();
        $this->displayPartitionResults($results);
        
        return 0;
    }

    /**
     * 處理快取操作
     */
    protected function handleCache(): int
    {
        $this->info('執行快取管理...');
        
        $cacheService = app(ActivityCacheService::class);
        
        if ($this->option('dry-run')) {
            $stats = $cacheService->getCacheStats();
            $this->displayCacheStats($stats);
            return 0;
        }
        
        // 預熱快取
        $warmupResults = $cacheService->warmupCache();
        $this->info("快取預熱完成: {$warmupResults['warmed_queries']} 個查詢");
        
        // 最佳化快取
        $optimizeResults = $cacheService->optimizeCache();
        $this->info("快取最佳化完成: 清理了 {$optimizeResults['cleaned_expired']} 個過期項目");
        
        return 0;
    }

    /**
     * 處理壓縮操作
     */
    protected function handleCompress(): int
    {
        $this->info('執行壓縮和歸檔...');
        
        $compressionService = app(ActivityCompressionService::class);
        
        if ($this->option('dry-run')) {
            $stats = $compressionService->getCompressionStats();
            $this->displayCompressionStats($stats);
            return 0;
        }
        
        $results = $compressionService->autoMaintenance();
        $this->displayCompressionResults($results);
        
        return 0;
    }

    /**
     * 處理未知操作
     */
    protected function handleUnknownAction(string $action): int
    {
        $this->error("未知的操作: {$action}");
        $this->info('可用的操作: optimize, report, maintenance, health, partition, cache, compress');
        
        return 1;
    }

    /**
     * 顯示最佳化結果
     */
    protected function displayOptimizationResults(array $results): void
    {
        $this->info("最佳化完成，耗時: {$results['total_time']}ms");
        
        if (!empty($results['partition_optimization'])) {
            $partition = $results['partition_optimization'];
            $this->info("分區最佳化: 建立 {$partition['created_partitions']} 個分區");
        }
        
        if (!empty($results['cache_optimization'])) {
            $cache = $results['cache_optimization'];
            $this->info("快取最佳化: 清理 {$cache['cleaned_expired']} 個過期項目");
        }
        
        if (!empty($results['errors'])) {
            $this->error('發生錯誤:');
            foreach ($results['errors'] as $error) {
                $this->error("  - {$error}");
            }
        }
    }

    /**
     * 顯示效能報告
     */
    protected function displayPerformanceReport(array $report): void
    {
        $this->info("效能報告 ({$report['period']['from']} 至 {$report['period']['to']})");
        $this->newLine();
        
        // 系統概覽
        if (isset($report['overview'])) {
            $overview = $report['overview'];
            $this->info('系統概覽:');
            $this->info("  總活動數: {$overview['total_activities']}");
            $this->info("  今日活動: {$overview['activities_today']}");
            $this->info("  本週活動: {$overview['activities_this_week']}");
        }
        
        // 快取統計
        if (isset($report['caching'])) {
            $caching = $report['caching'];
            $this->newLine();
            $this->info('快取統計:');
            $this->info("  命中率: {$caching['hit_rate']}%");
            $this->info("  總查詢: {$caching['total_queries']}");
        }
        
        // 查詢效能
        if (isset($report['query_performance'])) {
            $query = $report['query_performance'];
            $this->newLine();
            $this->info('查詢效能:');
            $this->info("  平均執行時間: {$query['basic_stats']->avg_execution_time}ms");
            $this->info("  慢查詢率: {$query['slow_query_rate']}%");
        }
        
        // 建議
        if (!empty($report['recommendations'])) {
            $this->newLine();
            $this->info('效能建議:');
            foreach ($report['recommendations'] as $recommendation) {
                $priority = $recommendation['priority'] === 'high' ? '🔴' : '🟡';
                $this->info("  {$priority} {$recommendation['message']}");
            }
        }
    }

    /**
     * 顯示健康報告
     */
    protected function displayHealthReport(array $health): void
    {
        $status = $health['overall_status'];
        $statusIcon = match ($status) {
            'healthy' => '✅',
            'warning' => '⚠️',
            'critical' => '🔴',
            default => '❓',
        };
        
        $this->info("系統健康狀態: {$statusIcon} {$status}");
        $this->newLine();
        
        // 組件狀態
        if (!empty($health['components'])) {
            $this->info('組件狀態:');
            foreach ($health['components'] as $component => $componentHealth) {
                $componentStatus = $componentHealth['status'] ?? 'unknown';
                $componentIcon = $componentStatus === 'healthy' ? '✅' : '❌';
                $this->info("  {$componentIcon} {$component}: {$componentStatus}");
            }
        }
        
        // 警報
        if (!empty($health['alerts'])) {
            $this->newLine();
            $this->warn('系統警報:');
            foreach ($health['alerts'] as $alert) {
                $this->warn("  - {$alert}");
            }
        }
        
        // 建議
        if (!empty($health['recommendations'])) {
            $this->newLine();
            $this->info('建議:');
            foreach ($health['recommendations'] as $recommendation) {
                $this->info("  - {$recommendation}");
            }
        }
    }

    /**
     * 其他顯示方法的簡化實作
     */
    protected function displayMaintenanceResults(array $results): void
    {
        $this->info('維護完成');
        foreach ($results as $type => $result) {
            $this->info("  {$type}: 完成");
        }
    }

    protected function displayPartitionStats(array $stats): void
    {
        $this->info("分區統計: 總共 {$stats['total_partitions']} 個分區");
    }

    protected function displayPartitionResults(array $results): void
    {
        $this->info("分區維護完成: 建立 {$results['created_partitions']} 個分區");
    }

    protected function displayCacheStats(array $stats): void
    {
        $this->info("快取統計: 命中率 {$stats['hit_rate']}%");
    }

    protected function displayCompressionStats(array $stats): void
    {
        $this->info("壓縮統計: {$stats['compressed_records']} 個記錄已壓縮");
    }

    protected function displayCompressionResults(array $results): void
    {
        $compression = $results['compression'] ?? [];
        $archive = $results['archive'] ?? [];
        
        $this->info("壓縮完成: {$compression['compressed_records']} 個記錄");
        $this->info("歸檔完成: {$archive['archived_records']} 個記錄");
    }
}