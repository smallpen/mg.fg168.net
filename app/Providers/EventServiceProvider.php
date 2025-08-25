<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Listeners\SecurityEventListener;
use App\Listeners\SystemEventListener;
use App\Listeners\RoleStatisticsCacheListener;
use App\Listeners\ActivityNotificationListener;
use App\Services\RoleStatisticsCacheManager;
use App\Models\Role;
use App\Models\Permission;
use App\Events\ActivityLogged;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;

class EventServiceProvider extends ServiceProvider
{
    /**
     * 應用程式的事件監聽器對應
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
            SystemEventListener::class . '@handleRegistered',
        ],
        Login::class => [
            SecurityEventListener::class . '@handleLogin',
            SystemEventListener::class . '@handleLogin',
        ],
        Failed::class => [
            SecurityEventListener::class . '@handleFailed',
            SystemEventListener::class . '@handleLoginFailed',
        ],
        Logout::class => [
            SecurityEventListener::class . '@handleLogout',
            SystemEventListener::class . '@handleLogout',
        ],
        Lockout::class => [
            SecurityEventListener::class . '@handleLockout',
        ],
        PasswordReset::class => [
            SystemEventListener::class . '@handlePasswordReset',
        ],
        JobFailed::class => [
            SystemEventListener::class . '@handleJobFailed',
        ],
        JobProcessed::class => [
            SystemEventListener::class . '@handleJobProcessed',
        ],
        ActivityLogged::class => [
            ActivityNotificationListener::class,
        ],
    ];

    /**
     * 註冊任何事件
     */
    public function boot(): void
    {
        // 註冊角色統計快取清除監聽器
        $this->registerRoleStatisticsCacheListeners();
    }

    /**
     * 註冊角色統計快取監聽器
     */
    private function registerRoleStatisticsCacheListeners(): void
    {
        $listener = app(RoleStatisticsCacheListener::class);

        // 角色模型事件
        Role::created(function ($role) use ($listener) {
            $listener->handleRoleCreated($role);
        });

        Role::updated(function ($role) use ($listener) {
            $listener->handleRoleUpdated($role);
        });

        Role::deleted(function ($role) use ($listener) {
            $listener->handleRoleDeleted($role);
        });

        // 權限模型事件
        Permission::updated(function ($permission) use ($listener) {
            $listener->handlePermissionUpdated($permission);
        });

        Permission::created(function ($permission) {
            app(RoleStatisticsCacheManager::class)->clearSystemCache();
        });

        Permission::deleted(function ($permission) {
            app(RoleStatisticsCacheManager::class)->clearSystemCache();
        });
    }

    /**
     * 判斷事件和監聽器是否應該自動發現
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}