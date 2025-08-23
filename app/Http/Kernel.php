<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * 應用程式的全域 HTTP 中介軟體堆疊
     *
     * 這些中介軟體會在每個請求期間執行
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        // \App\Http\Middleware\TrustHosts::class,
        \App\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * 應用程式的路由中介軟體群組
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\ApplyBasicSettings::class,
            \App\Http\Middleware\ActivityLoggingMiddleware::class,
        ],

        'api' => [
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\ApiActivityLoggingMiddleware::class,
        ],

        'admin' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\ApplyBasicSettings::class,
            \App\Http\Middleware\ApplySecuritySettings::class,
            \App\Http\Middleware\Authenticate::class,
            \App\Http\Middleware\AdminLayoutMiddleware::class,
            \App\Http\Middleware\CheckAdminPermission::class,
            \App\Http\Middleware\SecurityCheckMiddleware::class,
            \App\Http\Middleware\ActivityLoggingMiddleware::class,
        ],
    ];

    /**
     * 應用程式的中介軟體別名
     *
     * 別名可以用來為中介軟體指派簡短的名稱
     *
     * @var array<string, class-string|string>
     */
    protected $middlewareAliases = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'precognitive' => \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
        'signed' => \App\Http\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'admin' => \App\Http\Middleware\CheckAdminPermission::class,
        'admin.layout' => \App\Http\Middleware\AdminLayoutMiddleware::class,
        'admin.permission' => \App\Http\Middleware\CheckAdminPermission::class,
        'security.check' => \App\Http\Middleware\SecurityCheckMiddleware::class,
        'role.localization' => \App\Http\Middleware\RoleLocalizationMiddleware::class,
        'role.security' => \App\Http\Middleware\RoleSecurityMiddleware::class,
        'permission.security' => \App\Http\Middleware\PermissionSecurityMiddleware::class,
        'settings.access' => \App\Http\Middleware\SettingsAccessControl::class,
        'settings.performance' => \App\Http\Middleware\SettingsPerformanceMonitor::class,
        'activity.logging' => \App\Http\Middleware\ActivityLoggingMiddleware::class,
        'api.activity.logging' => \App\Http\Middleware\ApiActivityLoggingMiddleware::class,
    ];
}