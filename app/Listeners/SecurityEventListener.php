<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Services\AuditLogService;

/**
 * 安全事件監聽器
 * 
 * 監聽認證相關的安全事件並記錄日誌
 */
class SecurityEventListener
{
    protected AuditLogService $auditService;

    /**
     * 建立事件監聽器實例
     */
    public function __construct(AuditLogService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * 處理使用者登入事件
     */
    public function handleLogin(Login $event): void
    {
        $this->auditService->logLoginAttempt(
            $event->user->username,
            true,
            'successful_login'
        );

        $this->auditService->logUserManagementAction('user_login', [
            'login_time' => now()->toISOString(),
            'guard' => $event->guard,
        ], $event->user);
    }

    /**
     * 處理登入失敗事件
     */
    public function handleFailed(Failed $event): void
    {
        $username = $event->credentials['username'] ?? 
                   $event->credentials['email'] ?? 
                   'unknown';

        $this->auditService->logLoginAttempt(
            $username,
            false,
            'invalid_credentials'
        );

        $this->auditService->logSecurityEvent('login_failed', 'medium', [
            'attempted_username' => $username,
            'guard' => $event->guard,
            'credentials_provided' => array_keys($event->credentials),
        ]);
    }

    /**
     * 處理使用者登出事件
     */
    public function handleLogout(Logout $event): void
    {
        if ($event->user) {
            $this->auditService->logUserManagementAction('user_logout', [
                'logout_time' => now()->toISOString(),
                'guard' => $event->guard,
            ], $event->user);
        }
    }

    /**
     * 處理帳號鎖定事件
     */
    public function handleLockout(Lockout $event): void
    {
        $this->auditService->logSecurityEvent('account_lockout', 'high', [
            'request_data' => $event->request->only(['username', 'email']),
            'ip_address' => $event->request->ip(),
            'user_agent' => $event->request->userAgent(),
        ]);
    }

    /**
     * 註冊監聽器的事件
     */
    public function subscribe($events): void
    {
        $events->listen(
            Login::class,
            [SecurityEventListener::class, 'handleLogin']
        );

        $events->listen(
            Failed::class,
            [SecurityEventListener::class, 'handleFailed']
        );

        $events->listen(
            Logout::class,
            [SecurityEventListener::class, 'handleLogout']
        );

        $events->listen(
            Lockout::class,
            [SecurityEventListener::class, 'handleLockout']
        );
    }
}