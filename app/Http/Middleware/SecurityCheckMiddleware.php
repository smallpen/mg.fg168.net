<?php

namespace App\Http\Middleware;

use App\Services\SessionSecurityService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * 安全性檢查中介軟體
 * 
 * 在每個請求中執行安全性檢查
 */
class SecurityCheckMiddleware
{
    /**
     * Session 安全服務
     */
    protected SessionSecurityService $sessionService;
    
    /**
     * 建構函式
     */
    public function __construct(SessionSecurityService $sessionService)
    {
        $this->sessionService = $sessionService;
    }
    
    /**
     * 處理傳入的請求
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // 如果使用者未登入，跳過安全檢查
        if (!Auth::check()) {
            return $next($request);
        }
        
        $user = Auth::user();
        
        // 執行安全性檢查
        $securityCheck = $this->sessionService->checkSessionSecurity($user);
        
        // 如果 Session 已過期，強制登出
        if ($securityCheck['is_expired']) {
            $this->handleSessionExpiry($request, $user);
            return $this->redirectToLogin($request, 'Session 已過期，請重新登入');
        }
        
        // 檢查異常活動
        if ($securityCheck['suspicious_activity']) {
            $this->handleSuspiciousActivity($request, $user, $securityCheck);
        }
        
        // 更新最後活動時間
        $this->updateLastActivity($request);
        
        // 檢查並發 Session 限制
        $this->checkConcurrentSessions($user);
        
        // 設定安全相關的視圖資料
        $this->shareSecurityData($request, $user, $securityCheck);
        
        return $next($request);
    }
    
    /**
     * 處理 Session 過期
     */
    protected function handleSessionExpiry(Request $request, $user): void
    {
        // 記錄 Session 過期事件
        activity()
            ->causedBy($user)
            ->withProperties([
                'reason' => 'session_expired',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
            ])
            ->log('Session 過期');
        
        // 清除 Session
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
    
    /**
     * 處理異常活動
     */
    protected function handleSuspiciousActivity(Request $request, $user, array $securityCheck): void
    {
        // 取得風險等級
        $riskLevel = $this->assessRiskLevel($request, $user);
        
        // 記錄異常活動
        activity()
            ->causedBy($user)
            ->withProperties([
                'risk_level' => $riskLevel,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'previous_ip' => session('last_ip'),
                'previous_user_agent' => session('last_user_agent'),
            ])
            ->log('檢測到異常活動');
        
        // 根據風險等級採取行動
        switch ($riskLevel) {
            case 'high':
                // 高風險：要求重新驗證
                session(['requires_reauth' => true]);
                break;
                
            case 'medium':
                // 中風險：記錄並通知
                session(['security_warning' => true]);
                break;
                
            case 'low':
                // 低風險：僅記錄
                break;
        }
    }
    
    /**
     * 評估風險等級
     */
    protected function assessRiskLevel(Request $request, $user): string
    {
        $riskScore = 0;
        
        // IP 地址變化
        $currentIp = $request->ip();
        $lastIp = session('last_ip');
        
        if ($lastIp && $lastIp !== $currentIp) {
            $riskScore += 30;
        }
        
        // User Agent 變化
        $currentUserAgent = $request->userAgent();
        $lastUserAgent = session('last_user_agent');
        
        if ($lastUserAgent && $lastUserAgent !== $currentUserAgent) {
            $riskScore += 50;
        }
        
        // 異常時間登入
        $currentHour = now()->hour;
        if ($currentHour < 6 || $currentHour > 23) {
            $riskScore += 10;
        }
        
        // 多次失敗嘗試
        $failedAttempts = session('failed_attempts', 0);
        if ($failedAttempts > 3) {
            $riskScore += 20;
        }
        
        // 地理位置變化（如果有 IP 地理位置服務）
        // $riskScore += $this->checkGeolocationChange($currentIp, $lastIp);
        
        // 根據分數返回風險等級
        if ($riskScore >= 50) {
            return 'high';
        } elseif ($riskScore >= 20) {
            return 'medium';
        } else {
            return 'low';
        }
    }
    
    /**
     * 更新最後活動時間
     */
    protected function updateLastActivity(Request $request): void
    {
        session([
            'last_activity' => now()->timestamp,
            'last_ip' => $request->ip(),
            'last_user_agent' => $request->userAgent(),
        ]);
    }
    
    /**
     * 檢查並發 Session 限制
     */
    protected function checkConcurrentSessions($user): void
    {
        $maxConcurrentSessions = config('session.max_concurrent', 5);
        $concurrentSessions = $this->sessionService->getConcurrentSessions($user);
        
        if (count($concurrentSessions) > $maxConcurrentSessions) {
            // 記錄超出限制事件
            activity()
                ->causedBy($user)
                ->withProperties([
                    'concurrent_sessions' => count($concurrentSessions),
                    'max_allowed' => $maxConcurrentSessions,
                ])
                ->log('超出並發 Session 限制');
            
            // 可以選擇終止最舊的 Session 或發出警告
            session(['concurrent_session_warning' => true]);
        }
    }
    
    /**
     * 重定向到登入頁面
     */
    protected function redirectToLogin(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'redirect' => route('admin.login')
            ], 401);
        }
        
        return redirect()->route('admin.login')
            ->with('error', $message);
    }
    
    /**
     * 檢查是否需要重新驗證
     */
    protected function requiresReauth(Request $request): bool
    {
        return session('requires_reauth', false);
    }
    
    /**
     * 檢查維護模式例外
     */
    protected function isMaintenanceModeException(Request $request): bool
    {
        // 檢查當前 IP 是否在維護模式允許清單中
        if (app()->isDownForMaintenance()) {
            $maintenanceData = app()->maintenanceMode()->data();
            $allowedIps = $maintenanceData['allowed'] ?? [];
            
            return in_array($request->ip(), $allowedIps);
        }
        
        return false;
    }
    
    /**
     * 記錄安全事件
     */
    protected function logSecurityEvent(string $event, array $properties = []): void
    {
        activity()
            ->withProperties(array_merge($properties, [
                'timestamp' => now()->toISOString(),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]))
            ->log("安全事件: {$event}");
    }
    
    /**
     * 共享安全相關資料到視圖
     */
    protected function shareSecurityData(Request $request, $user, array $securityCheck): void
    {
        // 計算 Session 剩餘時間
        $sessionLifetime = config('session.lifetime') * 60; // 轉換為秒
        $lastActivity = session('last_activity', now()->timestamp);
        $sessionRemainingTime = $sessionLifetime - (now()->timestamp - $lastActivity);
        
        // 取得安全警告
        $securityWarnings = $this->getSecurityWarnings($securityCheck);
        
        // 取得 Session 資訊
        $sessionInfo = [
            'id' => session()->getId(),
            'lifetime' => $sessionLifetime,
            'remaining_time' => max(0, $sessionRemainingTime),
            'last_activity' => $lastActivity,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];
        
        // 共享到視圖
        \Illuminate\Support\Facades\View::share([
            'securityStatus' => [
                'session_info' => $sessionInfo,
                'security_warnings' => $securityWarnings,
                'requires_reauth' => session('requires_reauth', false),
                'concurrent_sessions' => $securityCheck['concurrent_sessions'] ?? 0,
                'suspicious_activity' => $securityCheck['suspicious_activity'] ?? false,
                'last_login' => $user->last_login_at?->format('Y-m-d H:i:s'),
                'login_count' => $user->login_count ?? 0,
            ]
        ]);
    }
    
    /**
     * 取得安全警告訊息
     */
    protected function getSecurityWarnings(array $securityCheck): array
    {
        $warnings = [];
        
        if ($securityCheck['needs_refresh']) {
            $warnings[] = [
                'type' => 'session_refresh',
                'message' => 'Session 即將過期，請重新整理頁面',
                'level' => 'warning'
            ];
        }
        
        if ($securityCheck['suspicious_activity']) {
            $warnings[] = [
                'type' => 'suspicious_activity',
                'message' => '檢測到異常活動，請注意帳號安全',
                'level' => 'danger'
            ];
        }
        
        if (session('requires_reauth')) {
            $warnings[] = [
                'type' => 'reauth_required',
                'message' => '基於安全考量，請重新驗證身份',
                'level' => 'danger'
            ];
        }
        
        if (session('concurrent_session_warning')) {
            $warnings[] = [
                'type' => 'concurrent_sessions',
                'message' => '您的帳號在多個裝置上登入',
                'level' => 'info'
            ];
        }
        
        return $warnings;
    }
    
    /**
     * 檢查 Session 是否需要重新整理
     */
    protected function needsSessionRefresh(): bool
    {
        $sessionLifetime = config('session.lifetime') * 60;
        $lastActivity = session('last_activity', now()->timestamp);
        $timeSinceLastActivity = now()->timestamp - $lastActivity;
        
        // 如果超過 Session 生命週期的 80%，建議重新整理
        return $timeSinceLastActivity > ($sessionLifetime * 0.8);
    }
    
    /**
     * 強制重新驗證
     */
    protected function forceReauthentication(Request $request, $user): void
    {
        // 設定重新驗證標記
        session(['requires_reauth' => true]);
        
        // 記錄強制重新驗證事件
        activity()
            ->causedBy($user)
            ->withProperties([
                'reason' => 'security_check_failed',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ])
            ->log('強制重新驗證');
    }
}