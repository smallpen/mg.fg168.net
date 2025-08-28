<?php

namespace App\Livewire\Admin\Security;

use App\Services\SessionSecurityService;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Hash;

/**
 * 多裝置 Session 管理元件
 * 
 * 負責管理使用者在多個裝置上的 Session
 */
class MultiDeviceSessionManager extends Component
{
    /**
     * 是否顯示 Session 管理對話框
     */
    public bool $showSessionManager = false;
    
    /**
     * 使用者的所有 Session
     */
    public array $sessions = [];
    
    /**
     * 密碼確認
     */
    public string $password = '';
    
    /**
     * 是否正在載入
     */
    public bool $loading = false;
    
    /**
     * 錯誤訊息
     */
    public string $errorMessage = '';
    
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
    }
    
    /**
     * 開啟 Session 管理器
     */
    public function openSessionManager(): void
    {
        if (!auth()->check()) {
            return;
        }
        
        $this->loadSessions();
        $this->showSessionManager = true;
        $this->password = '';
        $this->errorMessage = '';
    }
    
    /**
     * 載入使用者的所有 Session
     */
    public function loadSessions(): void
    {
        if (!auth()->check()) {
            return;
        }
        
        $user = auth()->user();
        $this->sessions = $this->sessionService->getConcurrentSessions($user);
    }
    
    /**
     * 關閉 Session 管理器
     */
    public function closeSessionManager(): void
    {
        $this->showSessionManager = false;
        $this->password = '';
        $this->errorMessage = '';
    }
    
    /**
     * 終止指定的 Session
     */
    public function terminateSession(string $sessionId): void
    {
        if (!auth()->check()) {
            return;
        }
        
        $this->loading = true;
        $this->errorMessage = '';
        
        try {
            // 驗證密碼
            if (!$this->validatePassword()) {
                return;
            }
            
            $user = auth()->user();
            
            // 不能終止當前 Session
            if ($sessionId === session()->getId()) {
                $this->errorMessage = '無法終止當前 Session';
                return;
            }
            
            // 終止指定 Session
            \DB::table('sessions')
                ->where('id', $sessionId)
                ->where('user_id', $user->id)
                ->delete();
            
            // 記錄操作
            activity()
                ->causedBy($user)
                ->withProperties([
                    'terminated_session_id' => $sessionId,
                    'ip' => request()->ip(),
                ])
                ->log('終止了一個 Session');
            
            // 重新載入 Session 列表
            $this->loadSessions();
            
            $this->dispatch('toast', [
                'type' => 'success',
                'message' => 'Session 已成功終止'
            ]);
            
        } catch (\Exception $e) {
            $this->errorMessage = 'Session 終止失敗：' . $e->getMessage();
        } finally {
            $this->loading = false;
        }
    }
    
    /**
     * 終止所有其他 Session
     */
    public function terminateAllOtherSessions(): void
    {
        if (!auth()->check()) {
            return;
        }
        
        $this->loading = true;
        $this->errorMessage = '';
        
        try {
            // 驗證密碼
            if (!$this->validatePassword()) {
                return;
            }
            
            $user = auth()->user();
            
            // 使用服務終止其他 Session
            $success = $this->sessionService->terminateOtherSessions($user, $this->password);
            
            if ($success) {
                // 重新載入 Session 列表
                $this->loadSessions();
                
                $this->dispatch('toast', [
                    'type' => 'success',
                    'message' => '所有其他 Session 已成功終止'
                ]);
                
                // 關閉對話框
                $this->closeSessionManager();
                
            } else {
                $this->errorMessage = '密碼驗證失敗';
            }
            
        } catch (\Exception $e) {
            $this->errorMessage = 'Session 終止失敗：' . $e->getMessage();
        } finally {
            $this->loading = false;
        }
    }
    
    /**
     * 驗證密碼
     */
    protected function validatePassword(): bool
    {
        if (empty($this->password)) {
            $this->errorMessage = '請輸入密碼以確認操作';
            return false;
        }
        
        $user = auth()->user();
        if (!Hash::check($this->password, $user->password)) {
            $this->errorMessage = '密碼不正確';
            return false;
        }
        
        return true;
    }
    
    /**
     * 刷新 Session 列表
     */
    public function refreshSessions(): void
    {
        $this->loadSessions();
        
        $this->dispatch('toast', [
            'type' => 'info',
            'message' => 'Session 列表已更新'
        ]);
    }
    
    /**
     * 取得 Session 的裝置類型
     */
    public function getDeviceType(string $userAgent): string
    {
        $userAgent = strtolower($userAgent);
        
        if (strpos($userAgent, 'mobile') !== false || strpos($userAgent, 'android') !== false) {
            return 'mobile';
        } elseif (strpos($userAgent, 'tablet') !== false || strpos($userAgent, 'ipad') !== false) {
            return 'tablet';
        } else {
            return 'desktop';
        }
    }
    
    /**
     * 取得瀏覽器名稱
     */
    public function getBrowserName(string $userAgent): string
    {
        $userAgent = strtolower($userAgent);
        
        if (strpos($userAgent, 'chrome') !== false) {
            return 'Chrome';
        } elseif (strpos($userAgent, 'firefox') !== false) {
            return 'Firefox';
        } elseif (strpos($userAgent, 'safari') !== false) {
            return 'Safari';
        } elseif (strpos($userAgent, 'edge') !== false) {
            return 'Edge';
        } else {
            return '未知瀏覽器';
        }
    }
    
    /**
     * 取得裝置圖示
     */
    public function getDeviceIcon(string $userAgent): string
    {
        $deviceType = $this->getDeviceType($userAgent);
        
        switch ($deviceType) {
            case 'mobile':
                return 'heroicon-o-device-phone-mobile';
            case 'tablet':
                return 'heroicon-o-device-tablet';
            default:
                return 'heroicon-o-computer-desktop';
        }
    }
    
    /**
     * 格式化最後活動時間
     */
    public function formatLastActivity($lastActivity): string
    {
        return $lastActivity->diffForHumans();
    }
    
    /**
     * 檢查是否為當前 Session
     */
    public function isCurrentSession(string $sessionId): bool
    {
        return $sessionId === session()->getId();
    }
    
    /**
     * 取得 Session 統計
     */
    public function getSessionStatsProperty(): array
    {
        return [
            'total' => count($this->sessions),
            'current' => 1,
            'others' => count($this->sessions) - 1,
        ];
    }
    
    /**
     * 監聽 Session 更新事件
     */
    #[On('session-updated')]
    public function handleSessionUpdate(): void
    {
        if ($this->showSessionManager) {
            $this->loadSessions();
        }
    }
    
    /**
     * 監聽新 Session 事件
     */
    #[On('new-session-detected')]
    public function handleNewSession(array $sessionData): void
    {
        // 顯示新 Session 通知
        $this->dispatch('toast', [
            'type' => 'warning',
            'message' => '檢測到新的登入 Session',
            'duration' => 5000,
        ]);
        
        // 如果 Session 管理器開啟，刷新列表
        if ($this->showSessionManager) {
            $this->loadSessions();
        }
    }
    
    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.security.multi-device-session-manager');
    }
}