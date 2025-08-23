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
        
        // 如果 Setting 模型存在，也註冊觀察者
        if (class_exists(Setting::class)) {
            Setting::observe(ActivityObserver::class);
        }
    }

    /**
     * 註冊系統事件監聽器
     *
     * @return void
     */
    protected function registerSystemEventListeners(): void
    {
        // 註冊應用程式啟動事件
        Event::listen('bootstrapped: Illuminate\Foundation\Bootstrap\BootProviders', function () {
            if (app()->runningInConsole()) {
                return;
            }
            
            $systemEventListener = app(\App\Listeners\SystemEventListener::class);
            $systemEventListener->logSystemStartup();
        });

        // 註冊應用程式關閉事件
        register_shutdown_function(function () {
            if (app()->runningInConsole()) {
                return;
            }
            
            try {
                $systemEventListener = app(\App\Listeners\SystemEventListener::class);
                $systemEventListener->logSystemShutdown();
            } catch (\Exception $e) {
                // 忽略關閉時的錯誤，避免影響正常關閉流程
            }
        });

        // 註冊快取事件監聽器
        if (config('app.debug')) {
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