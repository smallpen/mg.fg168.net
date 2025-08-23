<?php

namespace App\Providers;

use App\Repositories\SettingsRepository;
use App\Repositories\SettingsRepositoryInterface;
use App\Services\SettingsCacheService;
use App\Services\SettingsPerformanceService;
use App\Services\SettingsBatchProcessor;
use Illuminate\Support\ServiceProvider;

/**
 * 設定效能服務提供者
 * 
 * 註冊設定效能相關的服務
 */
class SettingsPerformanceServiceProvider extends ServiceProvider
{
    /**
     * 註冊服務
     *
     * @return void
     */
    public function register(): void
    {
        // 註冊快取服務
        $this->app->singleton(SettingsCacheService::class, function ($app) {
            $service = new SettingsCacheService();
            
            // 從配置檔案載入快取配置
            $config = config('system-settings.cache', []);
            if (!empty($config)) {
                $service->setConfig($config);
            }
            
            return $service;
        });

        // 註冊效能服務（延遲解析以避免循環依賴）
        $this->app->singleton(SettingsPerformanceService::class, function ($app) {
            // 不在構造函數中注入 repository，而是在需要時動態獲取
            return new SettingsPerformanceService(
                $app->make(SettingsRepositoryInterface::class)
            );
        });

        // 註冊批量處理服務
        $this->app->singleton(SettingsBatchProcessor::class, function ($app) {
            return new SettingsBatchProcessor(
                $app->make(SettingsRepositoryInterface::class),
                $app->make(SettingsCacheService::class)
            );
        });
    }

    /**
     * 啟動服務
     *
     * @return void
     */
    public function boot(): void
    {
        // 註冊服務注入（手動注入以避免循環依賴）
        // 這些服務將在需要時手動注入，而不是自動注入

        // 註冊 Artisan 指令
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\OptimizeSettingsPerformance::class,
            ]);
        }

        // 註冊中介軟體
        $this->registerMiddleware();

        // 註冊排程任務
        $this->registerScheduledTasks();
    }

    /**
     * 註冊中介軟體
     *
     * @return void
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];
        
        // 註冊效能監控中介軟體
        $router->aliasMiddleware('settings.performance', \App\Http\Middleware\SettingsPerformanceMonitor::class);
        
        // 將中介軟體添加到設定相關的路由群組
        $router->middlewareGroup('settings', [
            'settings.performance',
        ]);
    }

    /**
     * 註冊排程任務
     *
     * @return void
     */
    protected function registerScheduledTasks(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);
            
            // 每日清理過期的效能指標
            $schedule->command('settings:optimize --action=metrics --cleanup-days=30 --force')
                     ->daily()
                     ->at('02:00')
                     ->description('清理過期的設定效能指標');
            
            // 每週執行完整的效能優化
            $schedule->command('settings:optimize --action=all --force')
                     ->weekly()
                     ->sundays()
                     ->at('03:00')
                     ->description('執行完整的設定效能優化');
            
            // 每小時預熱重要的快取
            $schedule->call(function () {
                $cacheService = app(SettingsCacheService::class);
                $performanceService = app(SettingsPerformanceService::class);
                
                // 預熱核心設定分類
                $coreCategories = ['basic', 'security', 'cache'];
                $performanceService->warmupCache($coreCategories);
            })->hourly()->description('預熱核心設定快取');
        });
    }

    /**
     * 取得提供的服務
     *
     * @return array
     */
    public function provides(): array
    {
        return [
            SettingsCacheService::class,
            SettingsPerformanceService::class,
            SettingsBatchProcessor::class,
        ];
    }
}