<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Services\LanguageFileCache;
use App\Services\LanguagePerformanceMonitor;
use App\Services\MultilingualLogger;

/**
 * 語言效能服務提供者
 * 
 * 註冊語言效能相關服務和監控功能
 */
class LanguagePerformanceServiceProvider extends ServiceProvider
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

        // 註冊語言檔案快取服務
        $this->app->singleton(LanguageFileCache::class, function ($app) {
            return new LanguageFileCache($app->make(MultilingualLogger::class));
        });

        // 註冊語言效能監控服務
        $this->app->singleton(LanguagePerformanceMonitor::class, function ($app) {
            return new LanguagePerformanceMonitor($app->make(MultilingualLogger::class));
        });
    }

    /**
     * 啟動服務
     */
    public function boot(): void
    {
        // 註冊翻譯函數的效能監控
        $this->registerTranslationMonitoring();
        
        // 註冊視圖共享變數
        $this->registerViewComposers();
        
        // 註冊快取事件監聽器
        $this->registerCacheEventListeners();
    }

    /**
     * 註冊翻譯函數效能監控
     */
    private function registerTranslationMonitoring(): void
    {
        // 擴展翻譯器以加入效能監控
        $this->app->extend('translator', function ($translator, $app) {
            $monitor = $app->make(LanguagePerformanceMonitor::class);
            
            // 包裝 get 方法以監控效能
            $originalGet = $translator->get(...);
            
            $translator->macro('getWithMonitoring', function ($key, $replace = [], $locale = null) use ($originalGet, $monitor) {
                $startTime = microtime(true);
                $currentLocale = $locale ?? app()->getLocale();
                
                try {
                    $result = $originalGet($key, $replace, $locale);
                    $queryTime = (microtime(true) - $startTime) * 1000;
                    
                    // 檢查是否找到翻譯
                    $found = $result !== $key;
                    $usedFallback = false;
                    
                    // 如果結果包含回退標記，表示使用了回退
                    if (is_string($result) && str_contains($result, '[FALLBACK]')) {
                        $usedFallback = true;
                        $result = str_replace('[FALLBACK]', '', $result);
                    }
                    
                    // 記錄效能指標
                    $monitor->recordTranslationPerformance(
                        $key,
                        $currentLocale,
                        $queryTime,
                        $found,
                        $usedFallback
                    );
                    
                    return $result;
                    
                } catch (\Exception $e) {
                    $queryTime = (microtime(true) - $startTime) * 1000;
                    
                    // 記錄錯誤
                    $monitor->recordTranslationPerformance(
                        $key,
                        $currentLocale,
                        $queryTime,
                        false,
                        false
                    );
                    
                    throw $e;
                }
            });
            
            return $translator;
        });
    }

    /**
     * 註冊視圖組合器
     */
    private function registerViewComposers(): void
    {
        // 為管理後台視圖提供效能指標
        View::composer('admin.*', function ($view) {
            if (config('multilingual.performance.cache.enabled', true)) {
                $monitor = app(LanguagePerformanceMonitor::class);
                $metrics = $monitor->getRealTimeMetrics();
                
                $view->with('languagePerformanceMetrics', $metrics);
            }
        });
    }

    /**
     * 註冊快取事件監聽器
     */
    private function registerCacheEventListeners(): void
    {
        // 監聽快取事件
        $this->app['events']->listen('cache:hit', function ($key, $value) {
            if (str_starts_with($key, 'lang_file_cache:')) {
                $monitor = app(LanguagePerformanceMonitor::class);
                $parts = explode(':', $key);
                $locale = $parts[1] ?? 'unknown';
                
                $monitor->recordCachePerformance('hit', $locale);
            }
        });
        
        $this->app['events']->listen('cache:missed', function ($key) {
            if (str_starts_with($key, 'lang_file_cache:')) {
                $monitor = app(LanguagePerformanceMonitor::class);
                $parts = explode(':', $key);
                $locale = $parts[1] ?? 'unknown';
                
                $monitor->recordCachePerformance('miss', $locale);
            }
        });
        
        $this->app['events']->listen('cache:write', function ($key, $value, $minutes) {
            if (str_starts_with($key, 'lang_file_cache:')) {
                $monitor = app(LanguagePerformanceMonitor::class);
                $parts = explode(':', $key);
                $locale = $parts[1] ?? 'unknown';
                
                $monitor->recordCachePerformance('set', $locale);
            }
        });
        
        $this->app['events']->listen('cache:delete', function ($key) {
            if (str_starts_with($key, 'lang_file_cache:')) {
                $monitor = app(LanguagePerformanceMonitor::class);
                $parts = explode(':', $key);
                $locale = $parts[1] ?? 'unknown';
                
                $monitor->recordCachePerformance('delete', $locale);
            }
        });
    }

    /**
     * 取得提供的服務
     */
    public function provides(): array
    {
        return [
            MultilingualLogger::class,
            LanguageFileCache::class,
            LanguagePerformanceMonitor::class,
        ];
    }
}
