<?php

namespace App\Livewire\Admin\Security;

use App\Services\SessionSecurityService;
use Livewire\Component;
use Livewire\Attributes\On;

/**
 * 安全性控制中心元件
 * 
 * 整合所有安全性控制功能的主要元件
 */
class SecurityControlCenter extends Component
{
    /**
     * 當前使用者
     */
    public $user;
    
    /**
     * 安全性狀態
     */
    public array $securityStatus = [];
    
    /**
     * 是否正在載入
     */
    public bool $loading = true;
    
    /**
     * Session 安全服務
     */
    protected SessionSecurityService $sessionService;
    
    /**
     * 初始化元件
     */
    public function mount()
    {
        $this->user = auth()->user();
        $this->sessionService = app(SessionSecurityService::class);
        
        $this->loadSecurityStatus();
    }
    
    /**
     * 載入安全性狀態
     */
    public function loadSecurityStatus(): void
    {
        $this->loading = true;
        
        try {
            if (!$this->user) {
                return;
            }
            
            // 取得 Session 安全狀態
            $sessionSecurity = $this->sessionService->checkSessionSecurity($this->user);
            
            // 取得 Session 統計
            $sessionStats = $this->sessionService->getSessionStats();
            
            // 取得使用者的並發 Session
            $concurrentSessions = $this->sessionService->getConcurrentSessions($this->user);
            
            $this->securityStatus = [
                'session' => [
                    'is_expired' => $sessionSecurity['is_expired'],
                    'needs_refresh' => $sessionSecurity['needs_refresh'],
                    'idle_timeout_warning' => $sessionSecurity['idle_timeout_warning'],
                    'last_activity' => session('last_activity'),
                ],
                'suspicious_activity' => [
                    'detected' => $sessionSecurity['suspicious_activity'],
                    'ip_changed' => $this->checkIpChange(),
                    'user_agent_changed' => $this->checkUserAgentChange(),
                    'unusual_time' => $this->checkUnusualTime(),
                ],
                'concurrent_sessions' => [
                    'count' => count($concurrentSessions),
                    'sessions' => $concurrentSessions,
                    'max_allowed' => config('session.max_concurrent', 5),
                ],
                'system' => [
                    'maintenance_mode' => app()->isDownForMaintenance(),
                    'session_stats' => $sessionStats,
                    'security_level' => $this->calculateSecurityLevel(),
                ],
            ];
            
        } catch (\Exception $e) {
            $this->securityStatus = [
                'error' => '無法載入安全性狀態：' . $e->getMessage()
            ];
        } finally {
            $this->loading = false;
        }
    }
    
    /**
     * 檢查 IP 地址變化
     */
    protected function checkIpChange(): bool
    {
        $currentIp = request()->ip();
        $lastIp = session('last_ip');
        
        return $lastIp && $lastIp !== $currentIp;
    }
    
    /**
     * 檢查 User Agent 變化
     */
    protected function checkUserAgentChange(): bool
    {
        $currentUserAgent = request()->userAgent();
        $lastUserAgent = session('last_user_agent');
        
        return $lastUserAgent && $lastUserAgent !== $currentUserAgent;
    }
    
    /**
     * 檢查異常時間
     */
    protected function checkUnusualTime(): bool
    {
        $currentHour = now()->hour;
        return $currentHour < 6 || $currentHour > 23;
    }
    
    /**
     * 計算安全等級
     */
    protected function calculateSecurityLevel(): string
    {
        $score = 100;
        
        // Session 相關扣分
        if ($this->securityStatus['session']['needs_refresh'] ?? false) {
            $score -= 10;
        }
        
        if ($this->securityStatus['session']['idle_timeout_warning'] ?? false) {
            $score -= 15;
        }
        
        // 異常活動扣分
        if ($this->securityStatus['suspicious_activity']['detected'] ?? false) {
            $score -= 20;
        }
        
        if ($this->securityStatus['suspicious_activity']['ip_changed'] ?? false) {
            $score -= 15;
        }
        
        if ($this->securityStatus['suspicious_activity']['user_agent_changed'] ?? false) {
            $score -= 25;
        }
        
        // 並發 Session 扣分
        $sessionCount = $this->securityStatus['concurrent_sessions']['count'] ?? 0;
        $maxAllowed = $this->securityStatus['concurrent_sessions']['max_allowed'] ?? 5;
        
        if ($sessionCount > $maxAllowed) {
            $score -= 20;
        } elseif ($sessionCount > ($maxAllowed * 0.8)) {
            $score -= 10;
        }
        
        // 根據分數返回等級
        if ($score >= 90) {
            return 'high';
        } elseif ($score >= 70) {
            return 'medium';
        } else {
            return 'low';
        }
    }
    
    /**
     * 刷新安全性狀態
     */
    public function refreshSecurityStatus(): void
    {
        $this->loadSecurityStatus();
        
        $this->dispatch('toast', [
            'type' => 'info',
            'message' => '安全性狀態已更新'
        ]);
    }
    
    /**
     * 處理安全警告
     */
    public function handleSecurityWarning(string $type): void
    {
        switch ($type) {
            case 'session_expiry':
                $this->dispatch('show-session-expiry-warning');
                break;
                
            case 'suspicious_activity':
                $this->dispatch('show-suspicious-activity-alert');
                break;
                
            case 'concurrent_sessions':
                $this->dispatch('show-session-manager');
                break;
                
            case 'maintenance_mode':
                $this->dispatch('show-maintenance-mode');
                break;
        }
    }
    
    /**
     * 執行安全操作
     */
    public function executeSecurityAction(string $action): void
    {
        try {
            switch ($action) {
                case 'refresh_session':
                    $this->sessionService->refreshSession();
                    $this->dispatch('toast', [
                        'type' => 'success',
                        'message' => 'Session 已刷新'
                    ]);
                    break;
                    
                case 'force_logout':
                    $this->sessionService->forceLogout($this->user, 'manual');
                    break;
                    
                case 'cleanup_sessions':
                    $cleaned = $this->sessionService->cleanupExpiredSessions();
                    $this->dispatch('toast', [
                        'type' => 'success',
                        'message' => "已清理 {$cleaned} 個過期 Session"
                    ]);
                    break;
                    
                default:
                    $this->dispatch('toast', [
                        'type' => 'error',
                        'message' => '未知的安全操作'
                    ]);
            }
            
            // 重新載入狀態
            $this->loadSecurityStatus();
            
        } catch (\Exception $e) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => '操作失敗：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 取得安全等級顏色
     */
    public function getSecurityLevelColorProperty(): string
    {
        $level = $this->securityStatus['system']['security_level'] ?? 'medium';
        
        switch ($level) {
            case 'high':
                return 'text-green-600';
            case 'medium':
                return 'text-yellow-600';
            case 'low':
                return 'text-red-600';
            default:
                return 'text-gray-600';
        }
    }
    
    /**
     * 取得安全等級文字
     */
    public function getSecurityLevelTextProperty(): string
    {
        $level = $this->securityStatus['system']['security_level'] ?? 'medium';
        
        switch ($level) {
            case 'high':
                return '高';
            case 'medium':
                return '中';
            case 'low':
                return '低';
            default:
                return '未知';
        }
    }
    
    /**
     * 檢查是否有安全警告
     */
    public function hasSecurityWarningsProperty(): bool
    {
        return ($this->securityStatus['session']['needs_refresh'] ?? false) ||
               ($this->securityStatus['session']['idle_timeout_warning'] ?? false) ||
               ($this->securityStatus['suspicious_activity']['detected'] ?? false) ||
               (($this->securityStatus['concurrent_sessions']['count'] ?? 0) > 
                ($this->securityStatus['concurrent_sessions']['max_allowed'] ?? 5));
    }
    
    /**
     * 取得安全警告數量
     */
    public function getSecurityWarningCountProperty(): int
    {
        $count = 0;
        
        if ($this->securityStatus['session']['needs_refresh'] ?? false) $count++;
        if ($this->securityStatus['session']['idle_timeout_warning'] ?? false) $count++;
        if ($this->securityStatus['suspicious_activity']['detected'] ?? false) $count++;
        if (($this->securityStatus['concurrent_sessions']['count'] ?? 0) > 
            ($this->securityStatus['concurrent_sessions']['max_allowed'] ?? 5)) $count++;
        
        return $count;
    }
    
    /**
     * 監聽安全事件
     */
    #[On('security-event')]
    public function handleSecurityEvent(array $event): void
    {
        // 記錄安全事件
        activity()
            ->causedBy($this->user)
            ->withProperties($event)
            ->log('安全事件：' . ($event['type'] ?? 'unknown'));
        
        // 重新載入狀態
        $this->loadSecurityStatus();
    }
    
    /**
     * 監聽 Session 更新
     */
    #[On('session-updated')]
    public function handleSessionUpdate(): void
    {
        $this->loadSecurityStatus();
    }
    
    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.security.security-control-center');
    }
}