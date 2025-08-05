<?php

namespace App\Http\Livewire\Admin\Layout;

use Livewire\Component;

/**
 * 頂部導航列元件
 * 
 * 負責顯示管理後台的頂部導航列，包括：
 * - 選單切換按鈕
 * - 頁面標題顯示
 * - 使用者資訊和操作選單
 * - 快速操作按鈕
 */
class TopBar extends Component
{
    /**
     * 頁面標題
     */
    public string $pageTitle = '管理後台';
    
    /**
     * 使用者選單是否開啟
     */
    public bool $userMenuOpen = false;
    
    /**
     * 通知選單是否開啟
     */
    public bool $notificationMenuOpen = false;
    
    /**
     * 監聽的事件
     */
    protected $listeners = [
        'setPageTitle' => 'setPageTitle',
    ];
    
    /**
     * 設定頁面標題
     */
    public function setPageTitle(string $title)
    {
        $this->pageTitle = $title;
    }
    
    /**
     * 切換側邊欄
     */
    public function toggleSidebar()
    {
        $this->emit('toggleSidebar');
    }
    
    /**
     * 切換使用者選單
     */
    public function toggleUserMenu()
    {
        $this->userMenuOpen = !$this->userMenuOpen;
        
        // 關閉其他選單
        if ($this->userMenuOpen) {
            $this->notificationMenuOpen = false;
        }
    }
    
    /**
     * 切換通知選單
     */
    public function toggleNotificationMenu()
    {
        $this->notificationMenuOpen = !$this->notificationMenuOpen;
        
        // 關閉其他選單
        if ($this->notificationMenuOpen) {
            $this->userMenuOpen = false;
        }
    }
    
    /**
     * 關閉所有下拉選單
     */
    public function closeAllMenus()
    {
        $this->userMenuOpen = false;
        $this->notificationMenuOpen = false;
    }
    
    /**
     * 取得使用者頭像縮寫
     */
    public function getUserInitials(): string
    {
        $user = auth()->user();
        $name = $user->name ?? $user->username ?? 'U';
        
        // 取得姓名的第一個字元
        return mb_substr($name, 0, 1);
    }
    
    /**
     * 取得使用者顯示名稱
     */
    public function getUserDisplayName(): string
    {
        $user = auth()->user();
        return $user->name ?? $user->username ?? '使用者';
    }
    
    /**
     * 取得使用者電子郵件
     */
    public function getUserEmail(): string
    {
        return auth()->user()->email ?? '';
    }
    
    /**
     * 取得未讀通知數量
     */
    public function getUnreadNotificationCount(): int
    {
        // 這裡可以實作通知系統的邏輯
        // 目前回傳 0 作為預設值
        return 0;
    }
    
    /**
     * 標記所有通知為已讀
     */
    public function markAllNotificationsAsRead()
    {
        // 實作標記所有通知為已讀的邏輯
        $this->emit('notificationsMarkedAsRead');
        
        // 顯示成功訊息
        session()->flash('success', '所有通知已標記為已讀');
    }
    
    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.layout.top-bar', [
            'userInitials' => $this->getUserInitials(),
            'userDisplayName' => $this->getUserDisplayName(),
            'userEmail' => $this->getUserEmail(),
            'unreadNotificationCount' => $this->getUnreadNotificationCount(),
        ]);
    }
}