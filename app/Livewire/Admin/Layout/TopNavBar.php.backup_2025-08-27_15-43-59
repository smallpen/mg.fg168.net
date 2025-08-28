<?php

namespace App\Livewire\Admin\Layout;

use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Collection;

/**
 * 頂部導航列元件
 * 
 * 負責顯示管理後台的頂部導航列，包括：
 * - 選單收合按鈕
 * - 麵包屑導航
 * - 全域搜尋功能
 * - 通知中心
 * - 主題切換
 * - 語言選擇
 * - 使用者選單
 */
class TopNavBar extends Component
{
    // 搜尋功能
    public string $globalSearch = '';
    public array $searchResults = [];
    public bool $showSearchResults = false;
    
    // 通知功能
    public int $unreadNotifications = 0;
    public array $recentNotifications = [];
    public bool $showNotifications = false;
    
    // 使用者選單
    public bool $showUserMenu = false;
    
    // 麵包屑
    public array $breadcrumbs = [];
    
    // 頁面標題
    public string $pageTitle = '管理後台';
    
    /**
     * 元件初始化
     */
    public function mount()
    {
        $this->loadNotifications();
        $this->initializeBreadcrumbs();
    }
    

    
    /**
     * 計算屬性：取得通知列表
     */
    public function getNotificationsProperty(): Collection
    {
        return collect($this->recentNotifications);
    }
    
    /**
     * 計算屬性：取得當前使用者
     */
    public function getCurrentUserProperty()
    {
        return auth()->user();
    }
    
    /**
     * 切換側邊欄
     */
    public function toggleSidebar(): void
    {
        // 檢查是否為手機版
        if (request()->header('X-Mobile') || request()->header('User-Agent') && preg_match('/Mobile|Android|iPhone/', request()->header('User-Agent'))) {
            $this->dispatch('mobile-sidebar-toggle');
        } else {
            $this->dispatch('sidebar-toggle');
        }
    }
    
    /**
     * 執行全域搜尋
     */
    public function updatedGlobalSearch(): void
    {
        if (empty($this->globalSearch)) {
            $this->searchResults = [];
            $this->showSearchResults = false;
            return;
        }
        
        $this->performSearch();
    }
    
    /**
     * 執行搜尋
     */
    public function performSearch(): void
    {
        $query = trim($this->globalSearch);
        
        if (empty($query)) {
            $this->searchResults = [];
            $this->showSearchResults = false;
            return;
        }
        
        $this->searchResults = $this->getSearchResults($query);
        $this->showSearchResults = true;
    }
    
    /**
     * 清除搜尋
     */
    public function clearSearch(): void
    {
        $this->globalSearch = '';
        $this->searchResults = [];
        $this->showSearchResults = false;
    }
    
    /**
     * 切換使用者選單
     */
    public function toggleUserMenu(): void
    {
        $this->showUserMenu = !$this->showUserMenu;
        
        // 關閉其他選單
        if ($this->showUserMenu) {
            $this->showNotifications = false;
        }
    }
    

    
    /**
     * 切換通知面板
     */
    public function toggleNotifications(): void
    {
        $this->showNotifications = !$this->showNotifications;
        
        // 關閉其他選單
        if ($this->showNotifications) {
            $this->dispatch('close-other-menus', except: 'notifications');
        }
    }
    
    /**
     * 標記通知為已讀
     */
    public function markAsRead(int $notificationId): void
    {
        // 實作標記通知為已讀的邏輯
        $this->recentNotifications = array_map(function ($notification) use ($notificationId) {
            if ($notification['id'] === $notificationId) {
                $notification['read'] = true;
            }
            return $notification;
        }, $this->recentNotifications);
        
        $this->updateUnreadCount();
        
        $this->dispatch('notification-read', notificationId: $notificationId);
    }
    
    /**
     * 標記所有通知為已讀
     */
    public function markAllAsRead(): void
    {
        $this->recentNotifications = array_map(function ($notification) {
            $notification['read'] = true;
            return $notification;
        }, $this->recentNotifications);
        
        $this->unreadNotifications = 0;
        
        $this->dispatch('all-notifications-read');
        
        session()->flash('success', '所有通知已標記為已讀');
    }
    
    /**
     * 關閉所有下拉選單
     */
    public function closeAllMenus(): void
    {
        $this->showNotifications = false;
        $this->showUserMenu = false;
        $this->showSearchResults = false;
        $this->dispatch('close-other-menus');
    }
    
    /**
     * 監聽關閉其他選單事件
     */
    #[On('close-other-menus')]
    public function handleCloseOtherMenus(string $except = ''): void
    {
        if ($except !== 'notifications') {
            $this->showNotifications = false;
        }
    }
    
    /**
     * 處理新通知事件
     */
    #[On('notification-received')]
    public function handleNewNotification(array $notification): void
    {
        array_unshift($this->recentNotifications, $notification);
        
        // 只保留最近 10 筆通知
        $this->recentNotifications = array_slice($this->recentNotifications, 0, 10);
        
        $this->updateUnreadCount();
        
        // 顯示瀏覽器通知
        $this->dispatch('show-browser-notification', [
            'title' => $notification['title'],
            'body' => $notification['message'],
            'icon' => '/favicon.ico'
        ]);
    }
    
    /**
     * 處理主題變更事件
     */
    #[On('theme-changed')]
    public function handleThemeChange(string $theme): void
    {
        // 可以在這裡處理主題變更相關的邏輯
    }
    
    /**
     * 處理語言變更事件
     */
    #[On('locale-changed')]
    public function handleLocaleChange(string $locale): void
    {
        // 通知麵包屑元件重新載入
        $this->dispatch('breadcrumb-refresh');
    }
    
    /**
     * 設定頁面標題
     */
    #[On('page-title-changed')]
    public function setPageTitle(string $title): void
    {
        $this->pageTitle = $title;
        // 通知麵包屑元件重新載入
        $this->dispatch('breadcrumb-refresh');
    }
    
    /**
     * 處理麵包屑變更事件
     */
    #[On('breadcrumbs-changed')]
    public function handleBreadcrumbsChange(array $breadcrumbs): void
    {
        $this->breadcrumbs = $breadcrumbs;
    }
    

    
    /**
     * 載入通知
     */
    protected function loadNotifications(): void
    {
        // 這裡可以從資料庫載入實際的通知
        // 目前使用模擬資料
        $this->recentNotifications = [
            [
                'id' => 1,
                'title' => '系統更新',
                'message' => '系統已成功更新到版本 2.1.0',
                'type' => 'info',
                'read' => false,
                'created_at' => now()->subMinutes(5),
            ],
            [
                'id' => 2,
                'title' => '新使用者註冊',
                'message' => '使用者 John Doe 已完成註冊',
                'type' => 'success',
                'read' => false,
                'created_at' => now()->subMinutes(15),
            ],
        ];
        
        $this->updateUnreadCount();
    }
    
    /**
     * 更新未讀通知數量
     */
    protected function updateUnreadCount(): void
    {
        $this->unreadNotifications = count(array_filter($this->recentNotifications, function ($notification) {
            return !$notification['read'];
        }));
    }
    
    /**
     * 初始化麵包屑
     */
    protected function initializeBreadcrumbs(): void
    {
        $this->breadcrumbs = [
            [
                'title' => '管理後台',
                'route' => 'admin.dashboard',
                'active' => true
            ]
        ];
    }
    
    /**
     * 獲取搜尋結果
     */
    protected function getSearchResults(string $query): array
    {
        $results = [];
        
        // 搜尋頁面
        $pages = [
            ['title' => '使用者管理', 'route' => 'admin.users.index', 'type' => 'page'],
            ['title' => '角色管理', 'route' => 'admin.roles.index', 'type' => 'page'],
            ['title' => '權限管理', 'route' => 'admin.permissions.index', 'type' => 'page'],
            ['title' => '系統設定', 'route' => 'admin.settings.index', 'type' => 'page'],
        ];
        
        foreach ($pages as $page) {
            if (str_contains($page['title'], $query)) {
                $results[] = $page;
            }
        }
        
        return $results;
    }
    
    /**
     * 檢查新通知
     */
    public function checkNewNotifications(): void
    {
        // 這裡可以實作檢查新通知的邏輯
        // 目前只是重新載入通知
        $this->loadNotifications();
    }
    
    /**
     * 同步通知
     */
    public function syncNotifications(): void
    {
        // 這裡可以實作同步通知的邏輯
        $this->loadNotifications();
    }
    

    

    
    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.layout.top-nav-bar');
    }
}