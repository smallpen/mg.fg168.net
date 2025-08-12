<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

/**
 * 管理後台路由服務提供者
 * 
 * 負責註冊管理後台相關的路由和中介軟體
 */
class AdminRouteServiceProvider extends ServiceProvider
{
    /**
     * 管理後台路由的命名空間
     */
    public const HOME = '/admin/dashboard';

    /**
     * 註冊服務
     */
    public function register(): void
    {
        //
    }

    /**
     * 啟動服務
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        // 註冊管理後台 API 路由
        Route::middleware('admin')
            ->prefix('admin/api')
            ->name('admin.api.')
            ->group(base_path('routes/admin-api.php'));
    }

    /**
     * 配置速率限制
     */
    protected function configureRateLimiting(): void
    {
        // 管理後台一般請求速率限制
        RateLimiter::for('admin', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        // 管理後台 API 請求速率限制
        RateLimiter::for('admin-api', function (Request $request) {
            return Limit::perMinute(300)->by($request->user()?->id ?: $request->ip());
        });

        // 管理後台登入嘗試速率限制
        RateLimiter::for('admin-login', function (Request $request) {
            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perMinute(3)->by($request->input('email')),
            ];
        });

        // 管理後台搜尋請求速率限制
        RateLimiter::for('admin-search', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // 管理後台檔案上傳速率限制
        RateLimiter::for('admin-upload', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // 管理後台敏感操作速率限制
        RateLimiter::for('admin-sensitive', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });
    }
}
