<?php

namespace App\Livewire\Admin\Layout;

use Livewire\Component;

/**
 * 管理後台主要佈局元件
 * 
 * 這個元件負責管理整個管理後台的佈局結構，包括：
 * - 側邊導航選單的顯示/隱藏狀態
 * - 響應式佈局適應
 * - 佈局狀態管理
 */
class AdminLayout extends Component
{
    /**
     * 側邊欄是否開啟
     */
    public bool $sidebarOpen = true;
    
    /**
     * 是否為行動裝置模式
     */
    public bool $isMobile = false;
    
    /**
     * 監聽的事件
     */
    protected $listeners = [
        'toggleSidebar' => 'toggleSidebar',
        'closeSidebar' => 'closeSidebar',
        'openSidebar' => 'openSidebar',
        'setMobileMode' => 'setMobileMode',
    ];
    
    /**
     * 元件掛載時執行
     */
    public function mount()
    {
        // 從 session 中恢復側邊欄狀態
        $this->sidebarOpen = session('sidebar_open', true);
    }
    
    /**
     * 切換側邊欄顯示狀態
     */
    public function toggleSidebar()
    {
        $this->sidebarOpen = !$this->sidebarOpen;
        $this->saveSidebarState();
        
        // 發送事件通知其他元件
        $this->dispatch('sidebarToggled', ['open' => $this->sidebarOpen]);
    }
    
    /**
     * 關閉側邊欄
     */
    public function closeSidebar()
    {
        $this->sidebarOpen = false;
        $this->saveSidebarState();
        $this->dispatch('sidebarToggled', ['open' => $this->sidebarOpen]);
    }
    
    /**
     * 開啟側邊欄
     */
    public function openSidebar()
    {
        $this->sidebarOpen = true;
        $this->saveSidebarState();
        $this->dispatch('sidebarToggled', ['open' => $this->sidebarOpen]);
    }
    
    /**
     * 設定行動裝置模式
     */
    public function setMobileMode(bool $isMobile)
    {
        $this->isMobile = $isMobile;
        
        // 在行動裝置模式下，預設關閉側邊欄
        if ($isMobile && $this->sidebarOpen) {
            $this->sidebarOpen = false;
            $this->saveSidebarState();
        }
    }
    
    /**
     * 儲存側邊欄狀態到 session
     */
    private function saveSidebarState()
    {
        session(['sidebar_open' => $this->sidebarOpen]);
    }
    
    /**
     * 取得佈局的 CSS 類別
     */
    public function getLayoutClasses()
    {
        return [
            'sidebar' => $this->getSidebarClasses(),
            'main' => $this->getMainContentClasses(),
            'overlay' => $this->getOverlayClasses(),
        ];
    }
    
    /**
     * 取得側邊欄的 CSS 類別
     */
    private function getSidebarClasses()
    {
        $classes = [
            'fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-gray-800 shadow-lg transform transition-transform duration-300 ease-in-out',
        ];
        
        if ($this->isMobile) {
            // 行動裝置模式：側邊欄覆蓋在內容上方
            $classes[] = $this->sidebarOpen ? 'translate-x-0' : '-translate-x-full';
        } else {
            // 桌面模式：側邊欄推擠內容
            $classes[] = $this->sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0';
        }
        
        return implode(' ', $classes);
    }
    
    /**
     * 取得主內容區域的 CSS 類別
     */
    private function getMainContentClasses()
    {
        $classes = [
            'flex-1 flex flex-col min-h-screen transition-all duration-300 ease-in-out',
        ];
        
        if (!$this->isMobile) {
            // 桌面模式：根據側邊欄狀態調整左邊距
            $classes[] = $this->sidebarOpen ? 'lg:ml-64' : 'lg:ml-0';
        }
        
        return implode(' ', $classes);
    }
    
    /**
     * 取得遮罩層的 CSS 類別
     */
    private function getOverlayClasses()
    {
        if (!$this->isMobile || !$this->sidebarOpen) {
            return 'hidden';
        }
        
        return 'fixed inset-0 z-40 bg-black bg-opacity-50 transition-opacity duration-300 ease-in-out';
    }
    
    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.layout.admin-layout');
    }
}