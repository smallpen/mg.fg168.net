<?php

namespace App\Livewire\Admin\Security;

use App\Services\SessionSecurityService;
use Livewire\Component;
use Livewire\Attributes\On;

/**
 * Session 過期警告元件
 * 
 * 負責監控使用者 Session 狀態並顯示過期警告
 */
class SessionExpiryWarning extends Component
{
    /**
     * 是否顯示警告對話框
     */
    public bool $showWarning = false;
    
    /**
     * 剩餘時間（秒）
     */
    public int $remainingTime = 0;
    
    /**
     * 是否正在延長 Session
     */
    public bool $extending = false;
    
    /**
     * Session 安全服務
     */
    protected SessionSecurityService $sessionService;
    
    /**
     * 初始化元件
     */
    public function mount()
    {
        $this->sessionService = app(SessionSecurityService::class);
        $this->checkSessionStatus();
    }
    
    /**
     * 檢查 Session 狀態
     */
    public function checkSessionStatus(): void
    {
        if (!auth()->check()) {
            return;
        }
        
        $user = auth()->user();
        $securityCheck = $this->sessionService->checkSessionSecurity($user);
        
        // 如果 Session 已過期，強制登出
        if ($securityCheck['is_expired']) {
            $this->forceLogout('Session 已過期');
            return;
        }
        
        // 如果需要顯示警告
        if ($securityCheck['idle_timeout_warning']) {
            $this->showWarning = true;
            $this->calculateRemainingTime();
        }
    }
    
    /**
     * 計算剩餘時間
     */
    protected function calculateRemainingTime(): void
    {
        $lastActivity = session('last_activity', now()->timestamp);
        $idleTime = now()->timestamp - $lastActivity;
        $maxIdleTime = SessionSecurityService::IDLE_TIMEOUT * 60; // 轉換為秒
        
        $this->remainingTime = max(0, $maxIdleTime - $idleTime);
    }
    
    /**
     * 延長 Session
     */
    public function extendSession(): void
    {
        $this->extending = true;
        
        try {
            $this->sessionService->refreshSession();
            
            $this->showWarning = false;
            $this->remainingTime = 0;
            
            $this->dispatch('session-extended');
            $this->dispatch('toast', [
                'type' => 'success',
                'message' => 'Session 已成功延長'
            ]);
            
        } catch (\Exception $e) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Session 延長失敗，請重新登入'
            ]);
            
            $this->forceLogout('Session 延長失敗');
        } finally {
            $this->extending = false;
        }
    }
    
    /**
     * 立即登出
     */
    public function logoutNow(): void
    {
        $this->forceLogout('使用者主動登出');
    }
    
    /**
     * 強制登出
     */
    protected function forceLogout(string $reason): void
    {
        if (auth()->check()) {
            activity()
                ->causedBy(auth()->user())
                ->withProperties([
                    'reason' => $reason,
                    'ip' => request()->ip(),
                ])
                ->log('使用者登出');
        }
        
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();
        
        $this->redirect('/admin/login');
    }
    
    /**
     * 關閉警告（不延長 Session）
     */
    public function dismissWarning(): void
    {
        $this->showWarning = false;
    }
    
    /**
     * 監聽使用者活動
     */
    #[On('user-activity')]
    public function handleUserActivity(): void
    {
        // 更新最後活動時間
        session(['last_activity' => now()->timestamp]);
        
        // 如果正在顯示警告，重新檢查狀態
        if ($this->showWarning) {
            $this->checkSessionStatus();
        }
    }
    
    /**
     * 定期檢查 Session 狀態
     */
    #[On('check-session-status')]
    public function periodicCheck(): void
    {
        $this->checkSessionStatus();
    }
    
    /**
     * 格式化剩餘時間
     */
    public function getFormattedRemainingTimeProperty(): string
    {
        $minutes = floor($this->remainingTime / 60);
        $seconds = $this->remainingTime % 60;
        
        return sprintf('%02d:%02d', $minutes, $seconds);
    }
    
    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.security.session-expiry-warning');
    }
}