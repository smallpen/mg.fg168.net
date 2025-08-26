<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * 設定存取控制中介軟體
 * 
 * 實作設定管理的權限檢查、IP 限制和安全控制
 */
class SettingsAccessControl
{
    /**
     * 允許的 IP 位址快取鍵值
     */
    protected const ALLOWED_IPS_CACHE_KEY = 'settings_allowed_ips';
    
    /**
     * IP 限制快取時間（秒）
     */
    protected const IP_CACHE_TTL = 3600;
    
    /**
     * 失敗嘗試快取前綴
     */
    protected const FAILED_ATTEMPTS_PREFIX = 'settings_failed_attempts_';
    
    /**
     * 最大失敗嘗試次數
     */
    protected const MAX_FAILED_ATTEMPTS = 5;
    
    /**
     * 鎖定時間（分鐘）
     */
    protected const LOCKOUT_MINUTES = 30;

    /**
     * 處理傳入的請求
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $permission 需要的權限
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        // 檢查使用者是否已登入
        if (!Auth::check()) {
            $this->logSecurityEvent('unauthenticated_access', $request);
            return $this->handleUnauthorized($request, '請先登入系統');
        }

        $user = Auth::user();
        $clientIp = $request->ip();

        // 檢查 IP 限制
        if (!$this->isIpAllowed($clientIp)) {
            $this->logSecurityEvent('ip_restricted', $request, $user->id, [
                'blocked_ip' => $clientIp,
                'allowed_ips' => $this->getAllowedIps()
            ]);
            
            $this->recordFailedAttempt($clientIp);
            return $this->handleForbidden($request, '您的 IP 位址不被允許存取設定管理功能');
        }

        // 檢查是否被鎖定
        if ($this->isIpLocked($clientIp)) {
            $this->logSecurityEvent('ip_locked', $request, $user->id, [
                'locked_ip' => $clientIp,
                'lockout_remaining' => $this->getLockoutRemainingTime($clientIp)
            ]);
            
            return $this->handleForbidden($request, '由於多次失敗嘗試，您的 IP 已被暫時鎖定');
        }

        // 檢查基本設定權限
        if (!$user->hasPermission('settings.view')) {
            $this->logSecurityEvent('insufficient_permission', $request, $user->id, [
                'required_permission' => 'settings.view',
                'user_permissions' => $user->getAllPermissions()->pluck('name')->toArray()
            ]);
            
            $this->recordFailedAttempt($clientIp);
            return $this->handleForbidden($request, '您沒有設定管理權限');
        }

        // 檢查特定權限（如果指定）
        if ($permission && !$user->hasPermission($permission)) {
            $this->logSecurityEvent('specific_permission_denied', $request, $user->id, [
                'required_permission' => $permission,
                'user_permissions' => $user->getAllPermissions()->pluck('name')->toArray()
            ]);
            
            $this->recordFailedAttempt($clientIp);
            return $this->handleForbidden($request, "您沒有 '{$permission}' 權限");
        }

        // 檢查敏感設定的額外權限
        if ($this->isSensitiveSettingsRequest($request)) {
            if (!$user->hasPermission('settings.edit')) {
                $this->logSecurityEvent('sensitive_settings_denied', $request, $user->id, [
                    'requested_action' => $this->getRequestedAction($request)
                ]);
                
                $this->recordFailedAttempt($clientIp);
                return $this->handleForbidden($request, '您沒有管理敏感設定的權限');
            }
        }

        // 檢查系統設定的超級管理員權限
        if ($this->isSystemSettingsRequest($request)) {
            if (!$user->hasRole('super_admin')) {
                $this->logSecurityEvent('system_settings_denied', $request, $user->id, [
                    'user_roles' => $user->roles->pluck('name')->toArray()
                ]);
                
                $this->recordFailedAttempt($clientIp);
                return $this->handleForbidden($request, '只有超級管理員可以修改系統設定');
            }
        }

        // 檢查備份操作權限
        if ($this->isBackupRequest($request)) {
            if (!$user->hasPermission('settings.backup')) {
                $this->logSecurityEvent('backup_permission_denied', $request, $user->id);
                
                $this->recordFailedAttempt($clientIp);
                return $this->handleForbidden($request, '您沒有設定備份權限');
            }
        }

        // 記錄成功存取
        $this->logSecurityEvent('access_granted', $request, $user->id, [
            'permission' => $permission,
            'action' => $this->getRequestedAction($request)
        ]);

        // 清除失敗嘗試記錄
        $this->clearFailedAttempts($clientIp);

        // 設定安全標頭
        $response = $next($request);
        return $this->addSecurityHeaders($response);
    }

    /**
     * 檢查 IP 是否被允許
     */
    protected function isIpAllowed(string $ip): bool
    {
        $allowedIps = $this->getAllowedIps();
        
        // 如果沒有設定 IP 限制，允許所有 IP
        if (empty($allowedIps)) {
            return true;
        }

        // 檢查精確匹配
        if (in_array($ip, $allowedIps)) {
            return true;
        }

        // 檢查 CIDR 範圍匹配
        foreach ($allowedIps as $allowedIp) {
            if ($this->ipInRange($ip, $allowedIp)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 取得允許的 IP 清單
     */
    protected function getAllowedIps(): array
    {
        return Cache::remember(self::ALLOWED_IPS_CACHE_KEY, self::IP_CACHE_TTL, function () {
            // 從設定中取得允許的 IP 清單
            $settingsRepo = app(\App\Repositories\SettingsRepositoryInterface::class);
            $allowedIpsSetting = $settingsRepo->getSetting('security.allowed_ips');
            
            if (!$allowedIpsSetting || empty($allowedIpsSetting->value)) {
                return [];
            }

            $ips = is_array($allowedIpsSetting->value) 
                ? $allowedIpsSetting->value 
                : explode(',', $allowedIpsSetting->value);

            return array_map('trim', $ips);
        });
    }

    /**
     * 檢查 IP 是否在指定範圍內
     */
    protected function ipInRange(string $ip, string $range): bool
    {
        // 如果不包含 /，視為單一 IP
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }

        // CIDR 範圍檢查
        list($subnet, $bits) = explode('/', $range);
        
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || 
            !filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet &= $mask;

        return ($ip & $mask) === $subnet;
    }

    /**
     * 檢查 IP 是否被鎖定
     */
    protected function isIpLocked(string $ip): bool
    {
        $lockKey = self::FAILED_ATTEMPTS_PREFIX . 'lock_' . $ip;
        return Cache::has($lockKey);
    }

    /**
     * 取得鎖定剩餘時間
     */
    protected function getLockoutRemainingTime(string $ip): int
    {
        $lockKey = self::FAILED_ATTEMPTS_PREFIX . 'lock_' . $ip;
        $lockTime = Cache::get($lockKey);
        
        if (!$lockTime) {
            return 0;
        }

        $remainingTime = (self::LOCKOUT_MINUTES * 60) - (time() - $lockTime);
        return max(0, $remainingTime);
    }

    /**
     * 記錄失敗嘗試
     */
    protected function recordFailedAttempt(string $ip): void
    {
        $key = self::FAILED_ATTEMPTS_PREFIX . $ip;
        $attempts = Cache::get($key, 0) + 1;
        
        Cache::put($key, $attempts, now()->addHour());

        // 如果達到最大嘗試次數，鎖定 IP
        if ($attempts >= self::MAX_FAILED_ATTEMPTS) {
            $lockKey = self::FAILED_ATTEMPTS_PREFIX . 'lock_' . $ip;
            Cache::put($lockKey, time(), now()->addMinutes(self::LOCKOUT_MINUTES));
            
            $this->logSecurityEvent('ip_locked_due_to_failed_attempts', request(), null, [
                'ip' => $ip,
                'attempts' => $attempts,
                'lockout_minutes' => self::LOCKOUT_MINUTES
            ]);
        }
    }

    /**
     * 清除失敗嘗試記錄
     */
    protected function clearFailedAttempts(string $ip): void
    {
        $key = self::FAILED_ATTEMPTS_PREFIX . $ip;
        Cache::forget($key);
    }

    /**
     * 檢查是否為敏感設定請求
     */
    protected function isSensitiveSettingsRequest(Request $request): bool
    {
        // 檢查請求參數中是否包含敏感設定
        $sensitivePatterns = [
            '*password*',
            '*secret*',
            '*key*',
            '*token*',
            '*api_*',
            'security.*',
            'integration.*_client_secret',
            'integration.*_secret_key',
            'notification.smtp_password',
        ];

        $requestData = $request->all();
        
        foreach ($requestData as $key => $value) {
            foreach ($sensitivePatterns as $pattern) {
                if (fnmatch($pattern, $key)) {
                    return true;
                }
            }
        }

        // 檢查路由是否涉及敏感操作
        $routeName = $request->route()?->getName();
        $sensitiveRoutes = [
            'admin.settings.security',
            'admin.settings.integration',
            'admin.settings.backup',
            'admin.settings.export',
            'admin.settings.import',
        ];

        return in_array($routeName, $sensitiveRoutes);
    }

    /**
     * 檢查是否為系統設定請求
     */
    protected function isSystemSettingsRequest(Request $request): bool
    {
        $requestData = $request->all();
        
        foreach ($requestData as $key => $value) {
            // 檢查是否為系統級設定
            if (strpos($key, 'app.') === 0 || 
                strpos($key, 'system.') === 0 ||
                in_array($key, [
                    'security.force_https',
                    'security.session_lifetime',
                    'maintenance.maintenance_mode',
                ])) {
                return true;
            }
        }

        return false;
    }

    /**
     * 檢查是否為備份請求
     */
    protected function isBackupRequest(Request $request): bool
    {
        $routeName = $request->route()?->getName();
        $backupRoutes = [
            'admin.settings.backup.create',
            'admin.settings.backup.restore',
            'admin.settings.backup.download',
            'admin.settings.backup.delete',
        ];

        return in_array($routeName, $backupRoutes) || 
               $request->has('backup_action') ||
               str_contains($request->path(), 'backup');
    }

    /**
     * 取得請求的動作
     */
    protected function getRequestedAction(Request $request): string
    {
        $method = $request->method();
        $routeName = $request->route()?->getName() ?? 'unknown';
        
        if ($request->has('backup_action')) {
            return 'backup_' . $request->input('backup_action');
        }

        if (str_contains($routeName, 'backup')) {
            return 'backup_operation';
        }

        if (str_contains($routeName, 'export')) {
            return 'export_settings';
        }

        if (str_contains($routeName, 'import')) {
            return 'import_settings';
        }

        return strtolower($method) . '_settings';
    }

    /**
     * 處理未授權請求
     */
    protected function handleUnauthorized(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'error' => 'unauthorized'
            ], 401);
        }

        return redirect()->route('admin.login')
                       ->with('error', $message);
    }

    /**
     * 處理禁止存取請求
     */
    protected function handleForbidden(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'error' => 'forbidden'
            ], 403);
        }

        if ($request->header('X-Livewire')) {
            abort(403, $message);
        }

        // 顯示權限不足頁面而不是重定向到儀表板
        return response()->view('admin.errors.403', [
            'message' => $message,
            'title' => '權限不足',
            'description' => '您沒有足夠的權限存取此功能。如需協助，請聯繫系統管理員。',
            'back_url' => route('admin.dashboard')
        ], 403);
    }

    /**
     * 記錄安全事件
     */
    protected function logSecurityEvent(string $event, Request $request, ?int $userId = null, array $context = []): void
    {
        $logData = [
            'event' => $event,
            'url' => $request->url(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => $userId,
            'timestamp' => now()->toISOString(),
            'context' => $context,
        ];

        // 根據事件類型選擇日誌等級
        $logLevel = $this->getLogLevel($event);
        
        Log::log($logLevel, "Settings security event: {$event}", $logData);

        // 如果是高風險事件，同時記錄到安全日誌
        if (in_array($event, ['ip_restricted', 'ip_locked', 'sensitive_settings_denied', 'system_settings_denied'])) {
            Log::channel('security')->warning("High-risk settings access attempt: {$event}", $logData);
        }
    }

    /**
     * 取得日誌等級
     */
    protected function getLogLevel(string $event): string
    {
        $warningEvents = [
            'unauthenticated_access',
            'ip_restricted',
            'ip_locked',
            'insufficient_permission',
            'sensitive_settings_denied',
            'system_settings_denied',
            'backup_permission_denied',
        ];

        return in_array($event, $warningEvents) ? 'warning' : 'info';
    }

    /**
     * 添加安全標頭
     */
    protected function addSecurityHeaders(Response $response): Response
    {
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // 對於設定頁面，添加適當的 CSP（開發環境友好）
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' 'unsafe-eval' " . (app()->environment('local') ? 'http://localhost:5173 ws://localhost:5173' : '') . "; " .
               "style-src 'self' 'unsafe-inline' " . (app()->environment('local') ? 'http://localhost:5173' : '') . " https://fonts.bunny.net; " .
               "img-src 'self' data: https:; " .
               "font-src 'self' data: https://fonts.bunny.net; " .
               "connect-src 'self' " . (app()->environment('local') ? 'http://localhost:5173 ws://localhost:5173' : '') . "; " .
               "frame-ancestors 'none';";
        
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }

    /**
     * 清除 IP 限制快取
     */
    public static function clearIpCache(): void
    {
        Cache::forget(self::ALLOWED_IPS_CACHE_KEY);
    }

    /**
     * 手動解鎖 IP
     */
    public static function unlockIp(string $ip): void
    {
        $lockKey = self::FAILED_ATTEMPTS_PREFIX . 'lock_' . $ip;
        $attemptsKey = self::FAILED_ATTEMPTS_PREFIX . $ip;
        
        Cache::forget($lockKey);
        Cache::forget($attemptsKey);
    }

    /**
     * 取得被鎖定的 IP 清單
     */
    public static function getLockedIps(): array
    {
        // 這需要實作一個更複雜的機制來追蹤所有被鎖定的 IP
        // 暫時返回空陣列，實際實作可能需要使用資料庫或其他持久化存儲
        return [];
    }
}