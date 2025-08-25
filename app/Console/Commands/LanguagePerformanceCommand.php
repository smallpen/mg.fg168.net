<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LanguageFileCache;
use App\Services\LanguagePerformanceMonitor;
use App\Services\MultilingualLogger;

/**
 * 語言效能管理命令
 * 
 * 提供語言檔案快取管理和效能監控功能
 */
class LanguagePerformanceCommand extends Command
{
    /**
     * 命令名稱和參數
     *
     * @var string
     */
    protected $signature = 'language:performance 
                            {action : 動作 (warmup|clear-cache|stats|alerts|clear-data)}
                            {--locale= : 特定語言代碼}
                            {--group= : 特定語言檔案群組}
                            {--hours=24 : 統計時間範圍（小時）}
                            {--format=table : 輸出格式 (table|json)}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '管理語言檔案快取和效能監控';

    /**
     * 語言檔案快取服務
     */
    private LanguageFileCache $cache;

    /**
     * 語言效能監控服務
     */
    private LanguagePerformanceMonitor $monitor;

    /**
     * 多語系日誌記錄器
     */
    private MultilingualLogger $logger;

    /**
     * 建構函數
     */
    public function __construct(
        LanguageFileCache $cache,
        LanguagePerformanceMonitor $monitor,
        MultilingualLogger $logger
    ) {
        parent::__construct();
        $this->cache = $cache;
        $this->monitor = $monitor;
        $this->logger = $logger;
    }

    /**
     * 執行命令
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        try {
            return match ($action) {
                'warmup' => $this->handleWarmup(),
                'clear-cache' => $this->handleClearCache(),
                'stats' => $this->handleStats(),
                'alerts' => $this->handleAlerts(),
                'clear-data' => $this->handleClearData(),
                default => $this->handleInvalidAction($action),
            };
        } catch (\Exception $e) {
            $this->error("執行命令時發生錯誤: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    /**
     * 處理快取預熱
     */
    private function handleWarmup(): int
    {
        $this->info('開始預熱語言檔案快取...');
        
        $locale = $this->option('locale');
        $group = $this->option('group');
        
        $locales = $locale ? [$locale] : null;
        $groups = $group ? [$group] : null;
        
        $startTime = microtime(true);
        $results = $this->cache->warmupCache($locales, $groups);
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        $this->info("快取預熱完成！耗時: {$duration}ms");
        $this->info("成功: {$results['success']} 個檔案");
        $this->info("失敗: {$results['failed']} 個檔案");
        
        if ($this->option('format') === 'json') {
            $this->line(json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->displayWarmupDetails($results['details']);
        }
        
        return Command::SUCCESS;
    }

    /**
     * 處理清除快取
     */
    private function handleClearCache(): int
    {
        $locale = $this->option('locale');
        $group = $this->option('group');
        
        $this->info('清除語言檔案快取...');
        
        $cleared = $this->cache->clearCache($locale, $group);
        
        $scope = '';
        if ($locale && $group) {
            $scope = "語言 {$locale} 的 {$group} 群組";
        } elseif ($locale) {
            $scope = "語言 {$locale}";
        } elseif ($group) {
            $scope = "群組 {$group}";
        } else {
            $scope = "所有語言檔案";
        }
        
        $this->info("已清除 {$scope} 的快取，共 {$cleared} 個項目");
        
        return Command::SUCCESS;
    }

    /**
     * 處理統計資料顯示
     */
    private function handleStats(): int
    {
        $hours = (int) $this->option('hours');
        $format = $this->option('format');
        
        $this->info("取得過去 {$hours} 小時的效能統計...");
        
        // 取得各類型統計
        $fileLoadStats = $this->monitor->getPerformanceStats('file_load', $hours);
        $translationStats = $this->monitor->getPerformanceStats('translation', $hours);
        $cacheStats = $this->monitor->getPerformanceStats('cache', $hours);
        $realtimeMetrics = $this->monitor->getRealTimeMetrics();
        $cacheInfo = $this->cache->getCacheStats();
        
        if ($format === 'json') {
            $allStats = [
                'file_load' => $fileLoadStats,
                'translation' => $translationStats,
                'cache' => $cacheStats,
                'realtime' => $realtimeMetrics,
                'cache_info' => $cacheInfo,
            ];
            $this->line(json_encode($allStats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->displayStats($fileLoadStats, $translationStats, $cacheStats, $realtimeMetrics, $cacheInfo);
        }
        
        return Command::SUCCESS;
    }

    /**
     * 處理警報顯示
     */
    private function handleAlerts(): int
    {
        $this->info('取得活躍的效能警報...');
        
        $metrics = $this->monitor->getRealTimeMetrics();
        $alerts = $metrics['alerts'];
        
        if (empty($alerts)) {
            $this->info('目前沒有活躍的效能警報');
            return Command::SUCCESS;
        }
        
        if ($this->option('format') === 'json') {
            $this->line(json_encode($alerts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->displayAlerts($alerts);
        }
        
        return Command::SUCCESS;
    }

    /**
     * 處理清除效能資料
     */
    private function handleClearData(): int
    {
        $hours = (int) $this->option('hours');
        
        $this->info("清除 {$hours} 小時前的效能資料...");
        
        $cleared = $this->monitor->clearPerformanceData($hours);
        
        $this->info("已清除 {$cleared} 個效能資料項目");
        
        return Command::SUCCESS;
    }

    /**
     * 處理無效動作
     */
    private function handleInvalidAction(string $action): int
    {
        $this->error("無效的動作: {$action}");
        $this->info('可用的動作: warmup, clear-cache, stats, alerts, clear-data');
        return Command::FAILURE;
    }

    /**
     * 顯示預熱詳細資訊
     */
    private function displayWarmupDetails(array $details): void
    {
        $headers = ['語言', '群組', '狀態', '載入時間(ms)', '鍵數量'];
        $rows = [];
        
        foreach ($details as $detail) {
            $rows[] = [
                $detail['locale'],
                $detail['group'],
                $detail['status'] === 'success' ? '✓ 成功' : '✗ ' . $detail['status'],
                $detail['load_time_ms'] ?? 'N/A',
                $detail['keys_count'] ?? 'N/A',
            ];
        }
        
        $this->table($headers, $rows);
    }

    /**
     * 顯示統計資料
     */
    private function displayStats(
        array $fileLoadStats,
        array $translationStats,
        array $cacheStats,
        array $realtimeMetrics,
        array $cacheInfo
    ): void {
        // 檔案載入統計
        $this->info('📁 語言檔案載入統計');
        $this->displaySummaryTable($fileLoadStats['summary']);
        
        $this->newLine();
        
        // 翻譯查詢統計
        $this->info('🔍 翻譯查詢統計');
        $this->displaySummaryTable($translationStats['summary']);
        
        $this->newLine();
        
        // 快取統計
        $this->info('💾 快取統計');
        $this->displayCacheStats($cacheInfo);
        
        $this->newLine();
        
        // 即時指標
        $this->info('⚡ 即時指標');
        $this->displayRealtimeMetrics($realtimeMetrics);
    }

    /**
     * 顯示摘要統計表格
     */
    private function displaySummaryTable(array $summary): void
    {
        $headers = ['指標', '數值'];
        $rows = [
            ['總請求數', number_format($summary['total_requests'])],
            ['平均時間', $summary['avg_time_ms'] . ' ms'],
            ['最小時間', $summary['min_time_ms'] . ' ms'],
            ['最大時間', $summary['max_time_ms'] . ' ms'],
            ['成功率', $summary['success_rate_percent'] . '%'],
            ['錯誤率', $summary['error_rate_percent'] . '%'],
        ];
        
        $this->table($headers, $rows);
    }

    /**
     * 顯示快取統計
     */
    private function displayCacheStats(array $cacheInfo): void
    {
        $headers = ['項目', '數值'];
        $rows = [
            ['快取啟用', $cacheInfo['cache_enabled'] ? '是' : '否'],
            ['快取 TTL', $cacheInfo['cache_ttl'] . ' 秒'],
            ['總檔案數', $cacheInfo['total_possible']],
            ['已快取檔案', $cacheInfo['cached_files']],
            ['快取命中率', $cacheInfo['cache_hit_rate'] . '%'],
        ];
        
        $this->table($headers, $rows);
    }

    /**
     * 顯示即時指標
     */
    private function displayRealtimeMetrics(array $metrics): void
    {
        $this->info('當前小時統計:');
        
        foreach (['file_load', 'translation', 'cache'] as $type) {
            if (!empty($metrics[$type])) {
                $this->info("  {$type}: " . json_encode($metrics[$type], JSON_UNESCAPED_UNICODE));
            }
        }
    }

    /**
     * 顯示警報
     */
    private function displayAlerts(array $alerts): void
    {
        $headers = ['類型', '嚴重性', '時間', '詳細資訊'];
        $rows = [];
        
        foreach ($alerts as $alert) {
            $severity = match ($alert['severity']) {
                'high' => '🔴 高',
                'medium' => '🟡 中',
                'low' => '🟢 低',
                default => $alert['severity'],
            };
            
            $details = [];
            foreach ($alert['data'] as $key => $value) {
                if (is_numeric($value)) {
                    $value = is_float($value) ? round($value, 2) : $value;
                }
                $details[] = "{$key}: {$value}";
            }
            
            $rows[] = [
                $alert['type'],
                $severity,
                \Carbon\Carbon::parse($alert['timestamp'])->format('Y-m-d H:i:s'),
                implode(', ', array_slice($details, 0, 3)) . (count($details) > 3 ? '...' : ''),
            ];
        }
        
        $this->table($headers, $rows);
    }
}
