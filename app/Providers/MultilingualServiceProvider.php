<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\Translator;
use App\Services\MultilingualLogger;
use App\Services\LanguageFallbackHandler;

/**
 * 多語系服務提供者
 * 
 * 註冊多語系相關服務並擴展 Laravel 的翻譯系統以支援日誌記錄
 */
class MultilingualServiceProvider extends ServiceProvider
{
    /**
     * 註冊服務
     */
    public function register(): void
    {
        // 註冊多語系日誌記錄器
        $this->app->singleton(MultilingualLogger::class, function ($app) {
            return new MultilingualLogger();
        });

        // 註冊語言回退處理器
        $this->app->singleton(LanguageFallbackHandler::class, function ($app) {
            return new LanguageFallbackHandler();
        });
    }

    /**
     * 啟動服務
     */
    public function boot(): void
    {
        // 擴展翻譯器以支援日誌記錄
        $this->extendTranslator();
        
        // 註冊多語系配置
        $this->registerMultilingualConfig();
        
        // 註冊 Artisan 命令
        if ($this->app->runningInConsole()) {
            $this->registerCommands();
        }
    }

    /**
     * 擴展翻譯器
     */
    private function extendTranslator(): void
    {
        $this->app->extend('translator', function (Translator $translator, $app) {
            $logger = $app->make(MultilingualLogger::class);
            
            // 覆寫 get 方法以記錄缺少的翻譯鍵
            $originalGet = $translator->get(...);
            
            $translator->macro('getWithLogging', function (string $key, array $replace = [], ?string $locale = null, bool $fallback = true) use ($translator, $logger, $originalGet) {
                $locale = $locale ?: $translator->getLocale();
                
                // 記錄翻譯檔案載入時間
                $startTime = microtime(true);
                
                try {
                    // 檢查翻譯是否存在
                    $translation = $originalGet($key, $replace, $locale, $fallback);
                    
                    $loadTime = (microtime(true) - $startTime) * 1000; // 轉換為毫秒
                    
                    // 如果翻譯等於鍵值本身，表示翻譯不存在
                    if ($translation === $key) {
                        $logger->logMissingTranslationKey($key, $locale, [
                            'replace_params' => array_keys($replace),
                            'fallback_enabled' => $fallback
                        ]);
                    }
                    
                    // 記錄效能資訊（如果載入時間超過閾值）
                    if ($loadTime > config('multilingual.performance_log_threshold', 50)) {
                        $file = explode('.', $key)[0] ?? 'unknown';
                        $logger->logLanguageFilePerformance($file, $locale, $loadTime, false);
                    }
                    
                    return $translation;
                    
                } catch (\Exception $e) {
                    // 記錄翻譯載入錯誤
                    $file = explode('.', $key)[0] ?? 'unknown';
                    $logger->logLanguageFileLoadError($file, $locale, $e->getMessage(), [
                        'key' => $key,
                        'exception' => $e->getTraceAsString()
                    ]);
                    
                    return $key;
                }
            });
            
            return $translator;
        });
    }

    /**
     * 註冊多語系配置
     */
    private function registerMultilingualConfig(): void
    {
        // 發布配置檔案
        $this->publishes([
            __DIR__.'/../../config/multilingual.php' => config_path('multilingual.php'),
        ], 'multilingual-config');
        
        // 合併配置
        $this->mergeConfigFrom(
            __DIR__.'/../../config/multilingual.php',
            'multilingual'
        );
    }

    /**
     * 註冊 Artisan 命令
     */
    private function registerCommands(): void
    {
        $this->commands([
            \App\Console\Commands\MultilingualStatsCommand::class,
            \App\Console\Commands\MultilingualValidateCommand::class,
            \App\Console\Commands\MultilingualClearCacheCommand::class,
        ]);
    }

    /**
     * 取得提供的服務
     */
    public function provides(): array
    {
        return [
            MultilingualLogger::class,
            LanguageFallbackHandler::class,
        ];
    }
}