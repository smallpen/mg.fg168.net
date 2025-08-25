<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * 模型的政策對應
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\Activity::class => \App\Policies\ActivityPolicy::class,
    ];

    /**
     * 註冊任何認證/授權服務
     */
    public function boot(): void
    {
        // 定義動態權限檢查 Gate
        Gate::before(function ($user, $ability) {
            // 超級管理員擁有所有權限
            if ($user->isSuperAdmin()) {
                return true;
            }
            
            // 檢查使用者是否擁有特定權限
            if ($user->hasPermission($ability)) {
                return true;
            }
            
            return null; // 讓其他 Gate 繼續檢查
        });
    }
}