<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Setting;
use App\Observers\ActivityObserver;
use App\Observers\UserActivityObserver;
use App\Observers\RoleActivityObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

/**
 * 活動記錄服務提供者
 * 
 * 註冊活動記錄相關的觀察者和服務
 */
class ActivityLogServiceProvider extends ServiceProvider
{
    /**
     * 註冊服務
     *
     * @return void
     */
    public function register(): void
    {
        // 註冊活動記錄服務為單例
        $this->app->singleton(\App\Services\ActivityLogger::class);
        
        // 註冊活動記錄匯出服務為單例
        $this->app->singleton(\App\Services\ActivityExportService::class);
    }

    /**
     * 啟動服務
     *
     * @return void
     */
    public function boot(): void
    {
        // 註冊模型觀察者
        $this->registerModelObservers();
        
        // 註冊系統事件監聽器
        $this->registerSystemEventListeners();
    }

    /**
     * 註冊模型觀察者
     *
     * @return void
     */
    protected function registerModelObservers(): void
    {
        // 註冊使用者活動觀察者
        User::observe(UserActivityObserver::class);
        
        // 註冊角色活動觀察者
        Role::observe(RoleActivityObserver::class);
        
        // 註冊通用活動觀察者（用於其他模型）
        Permission::observe(ActivityObserver::class);
        
        // 注意：Setting 模型不使用 ActivityObserver，因為它有自己的變更記錄機制
    }

    /**
     * 註冊系統事件監聽器
     *
     * @return void
     */
    protected function registerSystemEventListeners(): void
    {
        // 移除不當的系統啟動和關閉事件監聽器
        // 這些事件應該只在真正的系統啟動和關閉時觸發，而不是每次 HTTP 請求
        // 如果需要記錄系統啟動，應該在部署腳本或系統初始化時手動記錄
        
        // 註冊真正重要的系統事件監聽器
        // 例如：維護模式切換、重要配置變更等

        // 註冊快取事件監聽器（可配置）
        // 只在除錯模式且明確啟用快取事件記錄時才註冊
        if (config('app.debug') && config('activity-log.system_events.log_cache_events', false)) {
            Event::listen(\Illuminate\Cache\Events\CacheHit::class, function ($event) {
                $systemEventListener = app(\App\Listeners\SystemEventListener::class);
                $systemEventListener->handleCacheHit($event);
            });

            Event::listen(\Illuminate\Cache\Events\CacheMissed::class, function ($event) {
                $systemEventListener = app(\App\Listeners\SystemEventListener::class);
                $systemEventListener->handleCacheMissed($event);
            });

            Event::listen(\Illuminate\Cache\Events\KeyForgotten::class, function ($event) {
                $systemEventListener = app(\App\Listeners\SystemEventListener::class);
                $systemEventListener->handleCacheKeyForgotten($event);
            });
        }

        // 註冊資料庫查詢事件監聽器（僅在除錯模式下）
        if (config('app.debug')) {
            Event::listen(\Illuminate\Database\Events\QueryExecuted::class, function ($event) {
                $systemEventListener = app(\App\Listeners\SystemEventListener::class);
                $systemEventListener->handleQueryExecuted($event);
            });
        }
    }
}