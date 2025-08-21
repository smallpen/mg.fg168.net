<?php

namespace App\Providers;

use App\Services\RoleCacheService;
use App\Services\RoleOptimizationService;
use App\Listeners\ClearRoleCacheListener;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Events\QueryExecuted;

/**
 * 角色優化服務提供者
 * 
 * 註冊角色快取和優化相關服務
 */
class RoleOptimizationServiceProvider extends ServiceProvider
{
    /**
     * 註冊服務
     */
    public function register(): void
    {
        // 註冊角色快取服務為單例
        $this->app->singleton(RoleCacheService::class, function ($app) {
            return new RoleCacheService();
        });

        // 註冊角色優化服務為單例
        $this->app->singleton(RoleOptimizationService::class, function ($app) {
            return new RoleOptimizationService();
        });
    }

    /**
     * 啟動服務
     */
    public function boot(): void
    {
        // 註冊事件監聽器
        $this->registerEventListeners();

        // 註冊 Artisan 命令
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\RoleCacheCommand::class,
            ]);
        }

        // 在應用啟動時預熱快取（僅在生產環境）
        if ($this->app->environment('production')) {
            $this->app->booted(function () {
                $this->warmupCacheIfNeeded();
            });
        }
    }

    /**
     * 註冊事件監聽器
     */
    private function registerEventListeners(): void
    {
        // 監聽資料庫查詢事件（僅在啟用快取時）
        if (config('cache.default') !== 'array') {
            Event::listen(QueryExecuted::class, ClearRoleCacheListener::class);
        }

        // 監聽 Eloquent 模型事件
        $modelEvents = ['created', 'updated', 'deleted', 'restored'];
        $models = [
            \App\Models\Role::class,
            \App\Models\Permission::class,
            \App\Models\User::class,
        ];

        foreach ($models as $model) {
            foreach ($modelEvents as $event) {
                Event::listen("eloquent.{$event}: {$model}", ClearRoleCacheListener::class);
            }
        }

        // 監聽樞紐表事件
        Event::listen('eloquent.pivotAttached: App\Models\Role', ClearRoleCacheListener::class);
        Event::listen('eloquent.pivotDetached: App\Models\Role', ClearRoleCacheListener::class);
        Event::listen('eloquent.pivotUpdated: App\Models\Role', ClearRoleCacheListener::class);
    }

    /**
     * 在需要時預熱快取
     */
    private function warmupCacheIfNeeded(): void
    {
        try {
            $cacheService = $this->app->make(RoleCacheService::class);
            
            // 檢查是否需要預熱快取（例如：快取為空時）
            $cacheKey = 'role_management:cache_warmed_at';
            $lastWarmedAt = cache($cacheKey);
            
            // 如果超過 1 小時沒有預熱，或者從未預熱過
            if (!$lastWarmedAt || now()->diffInHours($lastWarmedAt) > 1) {
                $cacheService->warmupCache();
                cache([$cacheKey => now()], now()->addHours(1));
            }
            
        } catch (\Exception $e) {
            // 預熱失敗不應該影響應用啟動
            logger()->warning('角色快取預熱失敗', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 取得提供的服務
     */
    public function provides(): array
    {
        return [
            RoleCacheService::class,
            RoleOptimizationService::class,
        ];
    }
}
