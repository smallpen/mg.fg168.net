<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

/**
 * Session 安全管理服務
 * 
 * 負責處理使用者 Session 的安全性管理，包括：
 * - Session 過期檢查
 * - 異常活動檢測
 * - 多裝置 Session 管理
 * - 自動登出功能
 */
class SessionSecurityService
{
    /**
     * Session 過期時間（分鐘）
     */
    const SESSION_LIFETIME = 120; // 2 小時
    
    /**
     * 閒置過期時間（分鐘）
     */
    const IDLE_TIMEOUT = 30; // 30 分鐘
    
    /**
     * 檢查 Session 安全性
     * 
     * @param User $user
     * @return array
     */
    public function checkSessionSecurity(User $user): array
    {
        return [
            'is_expired' => $this->isSessionExpired(),
            'needs_refresh' => $this->needsRefresh(),
            'suspicious_activity' => $this->detectSuspiciousActivity($user),
            'concurrent_sessions' => $this->getConcurrentSessions($user),
            'idle_timeout_warning' => $this->shouldShowIdleWarning(),
        ];
    }
    
    /**
     * 檢查 Session 是否過期
     * 
     * @return bool
     */
    public function isSessionExpired(): bool
    {
        $lastActivity = session('last_activity');
        
        if (!$lastActivity) {
            return false;
        }
        
        $idleTime = now()->diffInMinutes(Carbon::createFromTimestamp($lastActivity));
        
        return $idleTime > self::IDLE_TIMEOUT;
    }
    
    /**
     * 檢查是否需要刷新 Session
     * 
     * @return bool
     */
    public function needsRefresh(): bool
    {
        $lastActivity = session('last_activity');
        
        if (!$lastActivity) {
            return false;
        }
        
        $idleTime = now()->diffInMinutes(Carbon::createFromTimestamp($lastActivity));
        
        // 在過期前 5 分鐘提醒刷新
        return $idleTime >= (self::IDLE_TIMEOUT - 5) && $idleTime < self::IDLE_TIMEOUT;
    }
    
    /**
     * 檢測異常活動
     * 
     * @param User $user
     * @return bool
     */
    public function detectSuspiciousActivity(User $user): bool
    {
        $currentIp = request()->ip();
        $currentUserAgent = request()->userAgent();
        
        // 檢查 IP 地址變化
        $lastIp = session('last_ip');
        if ($lastIp && $lastIp !== $currentIp) {
            $this->logSecurityEvent($user, 'ip_change', [
                'old_ip' => $lastIp,
                'new_ip' => $currentIp,
            ]);
            
            return true;
        }
        
        // 檢查 User Agent 變化
        $lastUserAgent = session('last_user_agent');
        if ($lastUserAgent && $lastUserAgent !== $currentUserAgent) {
            $this->logSecurityEvent($user, 'user_agent_change', [
                'old_user_agent' => $lastUserAgent,
                'new_user_agent' => $currentUserAgent,
            ]);
            
            return true;
        }
        
        // 檢查異常登入時間
        $currentHour = now()->hour;
        if ($currentHour < 6 || $currentHour > 23) {
            $this->logSecurityEvent($user, 'unusual_login_time', [
                'login_hour' => $currentHour,
            ]);
        }
        
        return false;
    }
    
    /**
     * 取得並發 Session 數量
     * 
     * @param User $user
     * @return array
     */
    public function getConcurrentSessions(User $user): array
    {
        // 從 sessions 表格查詢使用者的活躍 Session
        $sessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('last_activity', '>', now()->subMinutes(self::IDLE_TIMEOUT)->timestamp)
            ->get();
        
        $sessionData = [];
        
        foreach ($sessions as $session) {
            $payload = $this->decodeSessionPayload($session->payload);
            
            $sessionData[] = [
                'id' => $session->id,
                'ip_address' => $session->ip_address,
                'user_agent' => $session->user_agent,
                'last_activity' => Carbon::createFromTimestamp($session->last_activity),
                'is_current' => $session->id === session()->getId(),
                'location' => $this->getLocationFromIp($session->ip_address),
            ];
        }
        
        return $sessionData;
    }
    
    /**
     * 檢查是否應該顯示閒置警告
     * 
     * @return bool
     */
    public function shouldShowIdleWarning(): bool
    {
        $lastActivity = session('last_activity');
        
        if (!$lastActivity) {
            return false;
        }
        
        $idleTime = now()->diffInMinutes(Carbon::createFromTimestamp($lastActivity));
        
        // 在過期前 5 分鐘顯示警告
        return $idleTime >= (self::IDLE_TIMEOUT - 5) && $idleTime < self::IDLE_TIMEOUT;
    }
    
    /**
     * 刷新 Session
     * 
     * @return void
     */
    public function refreshSession(): void
    {
        // 更新最後活動時間
        session(['last_activity' => now()->timestamp]);
        
        // 更新 IP 和 User Agent
        session([
            'last_ip' => request()->ip(),
            'last_user_agent' => request()->userAgent(),
        ]);
        
        // 重新生成 Session ID
        session()->regenerate();
        
        // 記錄 Session 刷新
        if (auth()->check()) {
            activity()
                ->causedBy(auth()->user())
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('Session 已刷新');
        }
    }
    
    /**
     * 終止其他裝置的 Session
     * 
     * @param User $user
     * @param string $password
     * @return bool
     */
    public function terminateOtherSessions(User $user, string $password): bool
    {
        // 驗證密碼
        if (!Hash::check($password, $user->password)) {
            return false;
        }
        
        $currentSessionId = session()->getId();
        
        // 刪除其他 Session
        $deletedCount = DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->delete();
        
        // 記錄操作
        activity()
            ->causedBy($user)
            ->withProperties([
                'terminated_sessions' => $deletedCount,
                'ip' => request()->ip(),
            ])
            ->log('已終止其他裝置的 Session');
        
        return true;
    }
    
    /**
     * 強制登出使用者
     * 
     * @param User $user
     * @param string $reason
     * @return void
     */
    public function forceLogout(User $user, string $reason = 'security'): void
    {
        // 刪除所有 Session
        DB::table('sessions')
            ->where('user_id', $user->id)
            ->delete();
        
        // 記錄強制登出
        activity()
            ->causedBy($user)
            ->withProperties([
                'reason' => $reason,
                'ip' => request()->ip(),
            ])
            ->log('使用者被強制登出');
        
        // 如果是當前使用者，執行登出
        if (auth()->id() === $user->id) {
            auth()->logout();
            session()->invalidate();
            session()->regenerateToken();
        }
    }
    
    /**
     * 記錄安全事件
     * 
     * @param User $user
     * @param string $event
     * @param array $properties
     * @return void
     */
    protected function logSecurityEvent(User $user, string $event, array $properties = []): void
    {
        activity()
            ->causedBy($user)
            ->withProperties(array_merge($properties, [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now()->toISOString(),
            ]))
            ->log("安全事件: {$event}");
    }
    
    /**
     * 解碼 Session 資料
     * 
     * @param string $payload
     * @return array
     */
    protected function decodeSessionPayload(string $payload): array
    {
        try {
            return unserialize(base64_decode($payload));
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * 從 IP 地址取得位置資訊
     * 
     * @param string $ip
     * @return string
     */
    protected function getLocationFromIp(string $ip): string
    {
        // 這裡可以整合 IP 地理位置服務
        // 目前返回簡單的本地/外部判斷
        
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return '本機';
        }
        
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return '外部網路';
        }
        
        return '內部網路';
    }
    
    /**
     * 清理過期的 Session
     * 
     * @return int 清理的 Session 數量
     */
    public function cleanupExpiredSessions(): int
    {
        $expiredTime = now()->subMinutes(self::SESSION_LIFETIME)->timestamp;
        
        return DB::table('sessions')
            ->where('last_activity', '<', $expiredTime)
            ->delete();
    }
    
    /**
     * 取得 Session 統計資訊
     * 
     * @return array
     */
    public function getSessionStats(): array
    {
        $activeThreshold = now()->subMinutes(self::IDLE_TIMEOUT)->timestamp;
        
        return [
            'total_sessions' => DB::table('sessions')->count(),
            'active_sessions' => DB::table('sessions')
                ->where('last_activity', '>', $activeThreshold)
                ->count(),
            'authenticated_sessions' => DB::table('sessions')
                ->whereNotNull('user_id')
                ->where('last_activity', '>', $activeThreshold)
                ->count(),
            'guest_sessions' => DB::table('sessions')
                ->whereNull('user_id')
                ->where('last_activity', '>', $activeThreshold)
                ->count(),
        ];
    }
}