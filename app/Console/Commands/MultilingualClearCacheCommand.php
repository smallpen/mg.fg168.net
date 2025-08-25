<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LanguageFallbackHandler;
use App\Services\MultilingualLogger;
use Illuminate\Support\Facades\Cache;

/**
 * 多語系快取清除命令
 * 
 * 清除多語系相關的快取資料
 */
class MultilingualClearCacheCommand extends Command
{
    /**
     * 命令名稱和簽名
     */
    protected $signature = 'multilingual:clear-cache 
                            {--locale= : 只清除指定語言的快取}
                            {--file= : 只清除指定檔案的快取}
                            {--stats : 清除統計快取}
                            {--all : 清除所有多語系快取}';

    /**
     * 命令描述
     */
    protected $description = '清除多語系相關的快取資料';

    /**
     * 語言回退處理器
     */
    private LanguageFallbackHandler $fallbackHandler;

    /**
     * 多語系日誌記錄器
     */
    private MultilingualLogger $logger;

    /**
     * 建構函數
     */
    public function __construct(LanguageFallbackHandler $fallbackHandler, MultilingualLogger $logger)
    {
        parent::__construct();
        $this->fallbackHandler = $fallbackHandler;
        $this->logger = $logger;
    }

    /**
     * 執行命令
     */
    public function handle(): int
    {
        $this->info('🧹 開始清除多語系快取...');
        $this->line('');

        $locale = $this->option('locale');
        $file = $this->option('file');
        $clearStats = $this->option('stats');
        $clearAll = $this->option('all');

        $clearedCount = 0;

        try {
            if ($clearAll) {
                $clearedCount += $this->clearAllCache();
            } else {
                if ($locale || $file) {
                    $clearedCount += $this->clearLanguageFileCache($locale, $file);
                }
                
                if ($clearStats) {
                    $clearedCount += $this->clearStatisticsCache();
                }
                
                if (!$locale && !$file && !$clearStats) {
                    // 預設行為：清除語言檔案快取
                    $clearedCount += $this->clearLanguageFileCache();
                }
            }

            $this->info("✅ 已清除 {$clearedCount} 個快取項目");
            
            // 記錄快取清除事件
            $this->logger->logLanguagePreferenceUpdate('cache_cleared', 'command', [
                'cleared_count' => $clearedCount,
                'options' => [
                    'locale' => $locale,
                    'file' => $file,
                    'stats' => $clearStats,
                    'all' => $clearAll,
                ],
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ 清除快取時發生錯誤: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * 清除所有多語系快取
     */
    private function clearAllCache(): int
    {
        $this->comment('清除所有多語系快取...');
        
        $count = 0;
        $count += $this->clearLanguageFileCache();
        $count += $this->clearStatisticsCache();
        $count += $this->clearTranslationCache();
        
        return $count;
    }

    /**
     * 清除語言檔案快取
     */
    private function clearLanguageFileCache(?string $locale = null, ?string $file = null): int
    {
        if ($locale && $file) {
            $this->comment("清除 {$locale}/{$file} 的快取...");
        } elseif ($locale) {
            $this->comment("清除 {$locale} 語言的快取...");
        } elseif ($file) {
            $this->comment("清除 {$file} 檔案的快取...");
        } else {
            $this->comment('清除所有語言檔案快取...');
        }

        $this->fallbackHandler->clearCache($locale, $file);
        
        // 計算清除的項目數（簡化計算）
        $locales = $locale ? [$locale] : array_keys(config('multilingual.supported_locales', ['zh_TW', 'en']));
        $files = $file ? [$file] : config('multilingual.validation.required_files', []);
        
        return count($locales) * count($files);
    }

    /**
     * 清除統計快取
     */
    private function clearStatisticsCache(): int
    {
        $this->comment('清除統計快取...');
        
        $patterns = [
            'multilingual_stats:missing_keys:*',
            'multilingual_stats:fallback:*',
            'multilingual_stats:switch:*',
            'multilingual_stats:*_daily:*',
        ];
        
        $count = 0;
        $retentionDays = config('multilingual.statistics.retention_days', 90);
        
        // 清除每日統計
        for ($i = 0; $i <= $retentionDays; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            
            $keys = [
                "multilingual_stats:missing_keys_daily:{$date}",
                "multilingual_stats:fallback_daily:{$date}",
                "multilingual_stats:switch_daily:{$date}",
            ];
            
            foreach ($keys as $key) {
                if (Cache::forget($key)) {
                    $count++;
                }
            }
        }
        
        // 清除其他統計快取
        $locales = array_keys(config('multilingual.supported_locales', ['zh_TW', 'en']));
        foreach ($locales as $locale) {
            foreach ($locales as $targetLocale) {
                $sources = ['url_parameter', 'session', 'user_preference', 'browser', 'default'];
                foreach ($sources as $source) {
                    $key = "multilingual_stats:switch:{$locale}:{$targetLocale}:{$source}";
                    if (Cache::forget($key)) {
                        $count++;
                    }
                }
                
                $key = "multilingual_stats:fallback:{$locale}:{$targetLocale}";
                if (Cache::forget($key)) {
                    $count++;
                }
            }
        }
        
        return $count;
    }

    /**
     * 清除翻譯快取
     */
    private function clearTranslationCache(): int
    {
        $this->comment('清除翻譯快取...');
        
        // 清除 Laravel 內建的翻譯快取
        \Artisan::call('cache:clear');
        
        // 清除自定義翻譯快取
        $count = 0;
        $locales = array_keys(config('multilingual.supported_locales', ['zh_TW', 'en']));
        $files = config('multilingual.validation.required_files', []);
        
        foreach ($locales as $locale) {
            foreach ($files as $file) {
                $key = "translation_cache:{$locale}:{$file}";
                if (Cache::forget($key)) {
                    $count++;
                }
            }
        }
        
        return $count;
    }
}