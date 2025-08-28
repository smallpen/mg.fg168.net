<?php

namespace App\Livewire\Admin\Security;

use App\Services\SessionSecurityService;
use App\Services\NotificationService;
use Livewire\Component;
use Livewire\Attributes\On;

/**
 * 異常活動檢測元件
 * 
 * 負責檢測和處理異常的使用者活動
 */
class SuspiciousActivityDetector extends Component
{
    /**
     * 是否顯示安全警告
     */
    public bool $showSecurityAlert = false;
    
    /**
     * 檢測到的異常活動類型
     */
    public array $detectedActivities = [];
    
    /**
     * 是否需要重新驗證
     */
    public bool $requiresReauth = false;
    
    /**
     * Session 安全服務
     */
    protected SessionSecurityService $sessionService;
    
    /**
     * 通知服務
     */
    protected NotificationService $notificationService;
    
    /**
     * 初始化元件
     */
    public function mount()
    {
        $this->sessionService = app(SessionSecurityService::class);
        $this->notificationService = app(NotificationService::class);
        
        $this->checkForSuspiciousActivity();
    }
    
    /**
     * 檢查異常活動
     */
    public function checkForSuspiciousActivity(): void
    {
        if (!auth()->check()) {
            return;
        }
        
        $user = auth()->user();
        $securityCheck = $this->sessionService->checkSessionSecurity($user);
        
        if ($securityCheck['suspicious_activity']) {
            $this->handleSuspiciousActivity($user);
        }
    }
    
    /**
     * 處理異常活動
     */
    protected function handleSuspiciousActivity($user): void
    {
        // 記錄異常活動
        $this->detectedActivities = $this->getDetectedActivities();
        
        // 根據風險等級決定處理方式
        $riskLevel = $this->assessRiskLevel();
        
        switch ($riskLevel) {
            case 'high':
                $this->handleHighRiskActivity($user);
                break;
                
            case 'medium':
                $this->handleMediumRiskActivity($user);
                break;
                
            case 'low':
                $this->handleLowRiskActivity($user);
                break;
        }
    }
    
    /**
     * 取得檢測到的異常活動
     */
    protected function getDetectedActivities(): array
    {
        $activities = [];
        
        // 檢查 IP 地址變化
        $currentIp = request()->ip();
        $lastIp = session('last_ip');
        
        if ($lastIp && $lastIp !== $currentIp) {
            $activities[] = [
                'type' => 'ip_change',
                'message' => "IP 地址已變更：{$lastIp} → {$currentIp}",
                'risk_level' => 'medium',
                'timestamp' => now(),
            ];
        }
        
        // 檢查 User Agent 變化
        $currentUserAgent = request()->userAgent();
        $lastUserAgent = session('last_user_agent');
        
        if ($lastUserAgent && $lastUserAgent !== $currentUserAgent) {
            $activities[] = [
                'type' => 'user_agent_change',
                'message' => '瀏覽器或裝置已變更',
                'risk_level' => 'high',
                'timestamp' => now(),
            ];
        }
        
        // 檢查異常登入時間
        $currentHour = now()->hour;
        if ($currentHour < 6 || $currentHour > 23) {
            $activities[] = [
                'type' => 'unusual_time',
                'message' => "異常時間登入：{$currentHour}:00",
                'risk_level' => 'low',
                'timestamp' => now(),
            ];
        }
        
        // 檢查多次失敗的操作
        $failedAttempts = session('failed_attempts', 0);
        if ($failedAttempts > 3) {
            $activities[] = [
                'type' => 'multiple_failures',
                'message' => "多次操作失敗：{$failedAttempts} 次",
                'risk_level' => 'medium',
                'timestamp' => now(),
            ];
        }
        
        return $activities;
    }
    
    /**
     * 評估風險等級
     */
    protected function assessRiskLevel(): string
    {
        $highRiskCount = 0;
        $mediumRiskCount = 0;
        
        foreach ($this->detectedActivities as $activity) {
            switch ($activity['risk_level']) {
                case 'high':
                    $highRiskCount++;
                    break;
                case 'medium':
                    $mediumRiskCount++;
                    break;
            }
        }
        
        if ($highRiskCount > 0 || $mediumRiskCount > 2) {
            return 'high';
        } elseif ($mediumRiskCount > 0) {
            return 'medium';
        } else {
            return 'low';
        }
    }
    
    /**
     * 處理高風險活動
     */
    protected function handleHighRiskActivity($user): void
    {
        // 要求重新驗證
        $this->requiresReauth = true;
        $this->showSecurityAlert = true;
        
        // 發送安全通知
        $this->notificationService->createNotification([
            'user_id' => $user->id,
            'type' => 'security_alert',
            'title' => '安全警報：檢測到高風險活動',
            'message' => '您的帳號檢測到異常活動，請立即驗證身份。',
            'priority' => 'high',
            'data' => [
                'activities' => $this->detectedActivities,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ],
        ]);
        
        // 記錄安全事件
        activity()
            ->causedBy($user)
            ->withProperties([
                'risk_level' => 'high',
                'activities' => $this->detectedActivities,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('檢測到高風險異常活動');
    }
    
    /**
     * 處理中風險活動
     */
    protected function handleMediumRiskActivity($user): void
    {
        $this->showSecurityAlert = true;
        
        // 發送通知但不強制重新驗證
        $this->notificationService->createNotification([
            'user_id' => $user->id,
            'type' => 'security_warning',
            'title' => '安全提醒：檢測到可疑活動',
            'message' => '您的帳號檢測到一些異常活動，請注意帳號安全。',
            'priority' => 'medium',
            'data' => [
                'activities' => $this->detectedActivities,
                'ip' => request()->ip(),
            ],
        ]);
        
        // 記錄安全事件
        activity()
            ->causedBy($user)
            ->withProperties([
                'risk_level' => 'medium',
                'activities' => $this->detectedActivities,
                'ip' => request()->ip(),
            ])
            ->log('檢測到中風險異常活動');
    }
    
    /**
     * 處理低風險活動
     */
    protected function handleLowRiskActivity($user): void
    {
        // 僅記錄，不顯示警告
        activity()
            ->causedBy($user)
            ->withProperties([
                'risk_level' => 'low',
                'activities' => $this->detectedActivities,
                'ip' => request()->ip(),
            ])
            ->log('檢測到低風險異常活動');
    }
    
    /**
     * 確認安全警告
     */
    public function acknowledgeAlert(): void
    {
        if (!$this->requiresReauth) {
            $this->showSecurityAlert = false;
            
            // 更新 Session 資訊
            session([
                'last_ip' => request()->ip(),
                'last_user_agent' => request()->userAgent(),
                'security_acknowledged' => now()->timestamp,
            ]);
            
            $this->dispatch('toast', [
                'type' => 'info',
                'message' => '安全警告已確認'
            ]);
        }
    }
    
    /**
     * 重新驗證身份
     */
    public function reAuthenticate(): void
    {
        // 重定向到重新驗證頁面
        $this->redirect('/admin/reauth');
    }
    
    /**
     * 立即登出
     */
    public function logoutImmediately(): void
    {
        if (auth()->check()) {
            $user = auth()->user();
            
            // 記錄安全登出
            activity()
                ->causedBy($user)
                ->withProperties([
                    'reason' => 'security_logout',
                    'activities' => $this->detectedActivities,
                    'ip' => request()->ip(),
                ])
                ->log('因安全原因登出');
            
            // 終止所有 Session
            $this->sessionService->forceLogout($user, 'security');
        }
        
        $this->redirect('/admin/login');
    }
    
    /**
     * 監聽安全事件
     */
    #[On('security-check')]
    public function handleSecurityCheck(): void
    {
        $this->checkForSuspiciousActivity();
    }
    
    /**
     * 重置失敗嘗試計數
     */
    public function resetFailedAttempts(): void
    {
        session()->forget('failed_attempts');
    }
    
    /**
     * 增加失敗嘗試計數
     */
    public function incrementFailedAttempts(): void
    {
        $attempts = session('failed_attempts', 0) + 1;
        session(['failed_attempts' => $attempts]);
        
        // 如果失敗次數過多，觸發安全檢查
        if ($attempts > 3) {
            $this->checkForSuspiciousActivity();
        }
    }
    
    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.security.suspicious-activity-detector');
    }
}