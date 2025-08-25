<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MultilingualLogger;
use Illuminate\Support\Facades\Cache;

/**
 * 多語系統計命令
 * 
 * 顯示多語系功能的使用統計資訊
 */
class MultilingualStatsCommand extends Command
{
    /**
     * 命令名稱和簽名
     */
    protected $signature = 'multilingual:stats 
                            {--days=7 : 顯示過去幾天的統計}
                            {--clear : 清除統計資料}
                            {--export= : 匯出統計資料到檔案}';

    /**
     * 命令描述
     */
    protected $description = '顯示多語系功能的使用統計資訊';

    /**
     * 多語系日誌記錄器
     */
    private MultilingualLogger $logger;

    /**
     * 建構函數
     */
    public function __construct(MultilingualLogger $logger)
    {
        parent::__construct();
        $this->logger = $logger;
    }

    /**
     * 執行命令
     */
    public function handle(): int
    {
        if ($this->option('clear')) {
            return $this->clearStatistics();
        }

        if ($this->option('export')) {
            return $this->exportStatistics();
        }

        return $this->showStatistics();
    }

    /**
     * 顯示統計資訊
     */
    private function showStatistics(): int
    {
        $days = (int) $this->option('days');
        
        $this->info("多語系統計資訊（過去 {$days} 天）");
        $this->line('');

        // 取得基本統計
        $stats = $this->logger->getStatistics();
        
        // 顯示當日統計
        $this->displayDailyStats($stats['daily_stats']);
        
        // 顯示歷史統計
        $this->displayHistoricalStats($days);
        
        // 顯示語言使用分佈
        $this->displayLanguageDistribution();
        
        // 顯示最常缺少的翻譯鍵
        $this->displayMostMissingKeys();
        
        // 顯示效能統計
        $this->displayPerformanceStats();

        return Command::SUCCESS;
    }

    /**
     * 顯示當日統計
     */
    private function displayDailyStats(array $dailyStats): void
    {
        $this->info('📊 今日統計');
        $this->table(
            ['項目', '數量'],
            [
                ['缺少翻譯鍵', $dailyStats['missing_keys']],
                ['回退使用次數', $dailyStats['fallback_usage']],
                ['語言切換次數', $dailyStats['language_switches']],
            ]
        );
        $this->line('');
    }

    /**
     * 顯示歷史統計
     */
    private function displayHistoricalStats(int $days): void
    {
        $this->info('📈 歷史統計');
        
        $historicalData = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $missingKeys = Cache::get("multilingual_stats:missing_keys_daily:{$date}", 0);
            $fallbackUsage = Cache::get("multilingual_stats:fallback_daily:{$date}", 0);
            $languageSwitches = Cache::get("multilingual_stats:switch_daily:{$date}", 0);
            
            $historicalData[] = [
                $date,
                $missingKeys,
                $fallbackUsage,
                $languageSwitches,
            ];
        }
        
        $this->table(
            ['日期', '缺少翻譯鍵', '回退使用', '語言切換'],
            $historicalData
        );
        $this->line('');
    }

    /**
     * 顯示語言使用分佈
     */
    private function displayLanguageDistribution(): void
    {
        $this->info('🌐 語言使用分佈');
        
        $locales = config('multilingual.supported_locales', []);
        $distribution = [];
        
        foreach ($locales as $locale => $config) {
            $switchCount = 0;
            $fallbackCount = 0;
            
            // 計算語言切換次數
            foreach ($locales as $fromLocale => $fromConfig) {
                $cacheKey = "multilingual_stats:switch:{$fromLocale}:{$locale}:*";
                // 這裡簡化處理，實際應用中可能需要更複雜的統計邏輯
                $switchCount += Cache::get("multilingual_stats:switch:{$fromLocale}:{$locale}:user_action", 0);
            }
            
            // 計算回退使用次數
            $fallbackCount = Cache::get("multilingual_stats:fallback:*:{$locale}", 0);
            
            $distribution[] = [
                $config['name'] ?? $locale,
                $switchCount,
                $fallbackCount,
            ];
        }
        
        $this->table(
            ['語言', '切換次數', '回退次數'],
            $distribution
        );
        $this->line('');
    }

    /**
     * 顯示最常缺少的翻譯鍵
     */
    private function displayMostMissingKeys(): void
    {
        $this->info('🔍 最常缺少的翻譯鍵（Top 10）');
        
        // 這裡簡化處理，實際應用中需要更複雜的統計邏輯
        $this->comment('此功能需要更詳細的快取鍵統計實作');
        $this->line('');
    }

    /**
     * 顯示效能統計
     */
    private function displayPerformanceStats(): void
    {
        $this->info('⚡ 效能統計');
        
        $threshold = config('multilingual.performance.log_threshold', 100);
        $this->comment("載入時間閾值: {$threshold}ms");
        
        // 顯示快取統計
        $cacheEnabled = config('multilingual.performance.cache.enabled', true);
        $cacheTtl = config('multilingual.performance.cache.ttl', 3600);
        
        $this->table(
            ['項目', '值'],
            [
                ['快取啟用', $cacheEnabled ? '是' : '否'],
                ['快取 TTL', $cacheTtl . ' 秒'],
                ['效能閾值', $threshold . ' ms'],
            ]
        );
        $this->line('');
    }

    /**
     * 清除統計資料
     */
    private function clearStatistics(): int
    {
        $days = (int) $this->option('days');
        
        if ($this->confirm("確定要清除過去 {$days} 天的統計資料嗎？")) {
            $this->logger->clearStatistics($days);
            $this->info('✅ 統計資料已清除');
            return Command::SUCCESS;
        }
        
        $this->comment('操作已取消');
        return Command::SUCCESS;
    }

    /**
     * 匯出統計資料
     */
    private function exportStatistics(): int
    {
        $exportPath = $this->option('export');
        $days = (int) $this->option('days');
        
        $this->info("正在匯出過去 {$days} 天的統計資料到 {$exportPath}...");
        
        $stats = $this->logger->getStatistics();
        
        // 收集歷史資料
        $historicalData = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $historicalData[$date] = [
                'missing_keys' => Cache::get("multilingual_stats:missing_keys_daily:{$date}", 0),
                'fallback_usage' => Cache::get("multilingual_stats:fallback_daily:{$date}", 0),
                'language_switches' => Cache::get("multilingual_stats:switch_daily:{$date}", 0),
            ];
        }
        
        $exportData = [
            'export_date' => now()->toISOString(),
            'period_days' => $days,
            'current_stats' => $stats,
            'historical_data' => $historicalData,
            'config' => [
                'supported_locales' => config('multilingual.supported_locales'),
                'performance_threshold' => config('multilingual.performance.log_threshold'),
                'cache_enabled' => config('multilingual.performance.cache.enabled'),
            ],
        ];
        
        try {
            file_put_contents($exportPath, json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info('✅ 統計資料已匯出到 ' . $exportPath);
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ 匯出失敗: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}