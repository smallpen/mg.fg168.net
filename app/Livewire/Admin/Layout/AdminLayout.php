<?php

namespace App\Livewire\Admin\Layout;

use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;
use App\Services\AccessibilityService;

/**
 * 管理後台主要佈局元件
 * 
 * 這個元件負責管理整個管理後台的佈局結構，包括：
 * - 響應式佈局容器和網格系統
 * - 側邊導航選單的顯示/隱藏狀態
 * - 行動版選單管理
 * - 佈局狀態管理和動畫過渡效果
 * - 主題系統整合
 */
class AdminLayout extends Component
{
    // 佈局狀態屬性
    public bool $sidebarCollapsed = false;
    public bool $sidebarMobile = false;
    public string $currentTheme = 'light';
    public string $currentLocale = 'zh_TW';
    
    // 頁面資訊屬性
    public string $pageTitle = '';
    public array $breadcrumbs = [];
    public array $pageActions = [];
    
    // 響應式狀態
    public bool $isMobile = false;
    public bool $isTablet = false;
    public bool $isDesktop = true;
    public int $viewportWidth = 1024;
    
    // 觸控手勢狀態
    public bool $touchGesturesEnabled = true;
    public bool $swipeToOpenSidebar = true;
    
    // 無障礙功能狀態
    public array $accessibilityPreferences = [];
    
    protected AccessibilityService $accessibilityService;
    
    /**
     * 注入依賴服務
     */
    public function boot(AccessibilityService $accessibilityService)
    {
        $this->accessibilityService = $accessibilityService;
    }

    /**
     * 元件掛載時執行
     */
    public function mount()
    {
        // 從 session 中恢復佈局狀態
        $this->sidebarCollapsed = session('sidebar_collapsed', false);
        $this->currentTheme = auth()->user()->theme_preference ?? 'light';
        $this->currentLocale = auth()->user()->locale ?? 'zh_TW';
        
        // 載入無障礙偏好設定
        $this->accessibilityPreferences = $this->accessibilityService->getUserAccessibilityPreferences();
        
        // 初始化頁面資訊
        $this->pageTitle = $this->getDefaultPageTitle();
        $this->breadcrumbs = $this->getDefaultBreadcrumbs();
    }
    
    // 計算屬性
    public function getLayoutClassesProperty(): string
    {
        $classes = ['min-h-screen', 'bg-gray-50', 'dark:bg-gray-900'];
        
        if ($this->isMobile) {
            $classes[] = 'mobile-layout';
        } elseif ($this->isTablet) {
            $classes[] = 'tablet-layout';
        } else {
            $classes[] = 'desktop-layout';
        }
        
        if ($this->sidebarCollapsed) {
            $classes[] = 'sidebar-collapsed';
        }
        
        // 新增無障礙 CSS 類別
        $accessibilityClasses = $this->accessibilityService->getAccessibilityClasses();
        if ($accessibilityClasses) {
            $classes[] = $accessibilityClasses;
        }
        
        return implode(' ', $classes);
    }
    
    public function getIsMobileProperty(): bool
    {
        return $this->isMobile;
    }
    
    public function getCurrentUserProperty(): ?\App\Models\User
    {
        return Auth::user();
    }
    
    // 佈局操作方法
    public function toggleSidebar(): void
    {
        $this->sidebarCollapsed = !$this->sidebarCollapsed;
        $this->saveSidebarState();
        
        // 發送事件通知其他元件
        $this->dispatch('sidebar-toggled', collapsed: $this->sidebarCollapsed);
    }
    
    public function toggleMobileSidebar(): void
    {
        $this->sidebarMobile = !$this->sidebarMobile;
        
        // 發送事件通知其他元件
        $this->dispatch('mobile-sidebar-toggled', open: $this->sidebarMobile);
    }
    
    public function setTheme(string $theme): void
    {
        $this->currentTheme = $theme;
        
        // 儲存使用者主題偏好
        if ($user = auth()->user()) {
            $user->update(['theme_preference' => $theme]);
        }
        
        // 發送主題變更事件
        $this->dispatch('theme-changed', theme: $theme);
    }
    
    public function setLocale(string $locale): void
    {
        $this->currentLocale = $locale;
        
        // 儲存使用者語言偏好
        if ($user = auth()->user()) {
            $user->update(['locale' => $locale]);
        }
        
        // 發送語言變更事件
        $this->dispatch('locale-changed', locale: $locale);
    }
    
    // 頁面管理方法
    public function setPageTitle(string $title): void
    {
        $this->pageTitle = $title;
    }
    
    public function setBreadcrumbs(array $breadcrumbs): void
    {
        $this->breadcrumbs = $breadcrumbs;
    }
    
    public function addPageAction(array $action): void
    {
        $this->pageActions[] = $action;
    }
    
    // 事件監聽方法
    #[On('theme-changed')]
    public function handleThemeChange(string $theme): void
    {
        $this->currentTheme = $theme;
    }
    
    #[On('locale-changed')]
    public function handleLocaleChange(string $locale): void
    {
        $this->currentLocale = $locale;
    }
    
    #[On('accessibility-preference-changed')]
    public function handleAccessibilityPreferenceChange(array $data): void
    {
        $this->accessibilityPreferences = $data['preferences'] ?? [];
        
        // 重新載入頁面以應用新的無障礙設定
        $this->dispatch('accessibility-settings-applied', [
            'preferences' => $this->accessibilityPreferences
        ]);
    }
    
    #[On('accessibility-preferences-reset')]
    public function handleAccessibilityPreferencesReset(array $data): void
    {
        $this->accessibilityPreferences = $data['preferences'] ?? [];
        
        // 重新載入頁面以應用重設的無障礙設定
        $this->dispatch('accessibility-settings-reset', [
            'preferences' => $this->accessibilityPreferences
        ]);
    }
    
    #[On('sidebar-toggle')]
    public function handleSidebarToggle(): void
    {
        $this->toggleSidebar();
    }
    
    #[On('viewport-changed')]
    public function handleViewportChange(array $viewport): void
    {
        $this->isMobile = $viewport['isMobile'] ?? false;
        $this->isTablet = $viewport['isTablet'] ?? false;
        $this->isDesktop = $viewport['isDesktop'] ?? true;
        $this->viewportWidth = $viewport['width'] ?? 1024;
        
        // 根據裝置類型調整側邊欄狀態
        if ($this->isMobile) {
            // 行動裝置：關閉側邊欄覆蓋
            $this->sidebarMobile = false;
        } elseif ($this->isTablet) {
            // 平板：預設收合側邊欄
            if (!session()->has('tablet_sidebar_state')) {
                $this->sidebarCollapsed = true;
                $this->saveSidebarState();
            }
        }
        
        // 發送響應式變更事件
        $this->dispatch('responsive-layout-changed', [
            'isMobile' => $this->isMobile,
            'isTablet' => $this->isTablet,
            'isDesktop' => $this->isDesktop,
            'viewportWidth' => $this->viewportWidth
        ]);
    }
    
    #[On('swipe-gesture')]
    public function handleSwipeGesture(array $gesture): void
    {
        if (!$this->touchGesturesEnabled) {
            return;
        }
        
        $direction = $gesture['direction'] ?? '';
        $velocity = $gesture['velocity'] ?? 0;
        
        // 只在行動裝置上處理滑動手勢
        if (!$this->isMobile) {
            return;
        }
        
        switch ($direction) {
            case 'right':
                // 向右滑動：開啟側邊欄
                if ($this->swipeToOpenSidebar && $gesture['startX'] < 50 && $velocity > 0.3) {
                    $this->sidebarMobile = true;
                }
                break;
                
            case 'left':
                // 向左滑動：關閉側邊欄
                if ($this->sidebarMobile && $velocity > 0.3) {
                    $this->sidebarMobile = false;
                }
                break;
        }
    }
    
    // 私有輔助方法
    private function saveSidebarState(): void
    {
        session(['sidebar_collapsed' => $this->sidebarCollapsed]);
    }
    
    private function getDefaultPageTitle(): string
    {
        return __('管理後台');
    }
    
    private function getDefaultBreadcrumbs(): array
    {
        return [
            ['label' => __('首頁'), 'url' => route('admin.dashboard')]
        ];
    }
    
    /**
     * 取得佈局的 CSS 類別組合
     */
    public function getLayoutClasses(): array
    {
        return [
            'container' => $this->getContainerClasses(),
            'sidebar' => $this->getSidebarClasses(),
            'main' => $this->getMainContentClasses(),
            'overlay' => $this->getOverlayClasses(),
        ];
    }
    
    /**
     * 取得容器的 CSS 類別
     */
    private function getContainerClasses(): string
    {
        $classes = [
            'min-h-screen',
            'bg-gray-50',
            'dark:bg-gray-900',
            'transition-colors',
            'duration-300',
            'ease-in-out'
        ];
        
        // 響應式佈局類別
        if ($this->isMobile) {
            $classes[] = 'layout-mobile';
        } elseif ($this->isTablet) {
            $classes[] = 'layout-tablet';
        } else {
            $classes[] = 'layout-desktop';
        }
        
        // 側邊欄狀態類別
        if ($this->sidebarCollapsed) {
            $classes[] = 'sidebar-collapsed';
        }
        
        return implode(' ', $classes);
    }
    
    /**
     * 取得側邊欄的 CSS 類別
     */
    private function getSidebarClasses(): string
    {
        $baseClasses = [
            'bg-white',
            'dark:bg-gray-800',
            'shadow-lg',
            'transform',
            'transition-all',
            'duration-300',
            'ease-in-out',
            'overflow-hidden'
        ];
        
        if ($this->isMobile) {
            // 行動裝置：抽屜模式
            $baseClasses = array_merge($baseClasses, [
                'mobile-drawer',
                'fixed',
                'inset-y-0',
                'left-0',
                'z-50',
                'w-80'
            ]);
            
            if ($this->sidebarMobile) {
                $baseClasses[] = 'open';
            }
        } elseif ($this->isTablet) {
            // 平板：可收合模式
            $baseClasses = array_merge($baseClasses, [
                'tablet-sidebar',
                'fixed',
                'inset-y-0',
                'left-0',
                'z-40'
            ]);
            
            if (!$this->sidebarCollapsed) {
                $baseClasses[] = 'expanded';
            }
        } else {
            // 桌面：標準模式
            $baseClasses = array_merge($baseClasses, [
                'fixed',
                'inset-y-0',
                'left-0',
                'z-40'
            ]);
            
            $baseClasses[] = $this->sidebarCollapsed ? 'w-16' : 'w-72';
        }
        
        return implode(' ', $baseClasses);
    }
    
    /**
     * 取得主內容區域的 CSS 類別
     */
    private function getMainContentClasses(): string
    {
        $classes = [
            'flex-1',
            'flex',
            'flex-col',
            'min-h-screen',
            'transition-all',
            'duration-300',
            'ease-in-out',
            'relative'
        ];
        
        if ($this->isMobile) {
            // 行動裝置：無左邊距，全寬顯示
            $classes[] = 'ml-0';
            $classes[] = 'w-full';
        } elseif ($this->isTablet) {
            // 平板：根據收合狀態調整左邊距
            $classes[] = $this->sidebarCollapsed ? 'ml-16' : 'ml-60';
        } else {
            // 桌面：根據收合狀態調整左邊距
            $classes[] = $this->sidebarCollapsed ? 'ml-16' : 'ml-72';
        }
        
        return implode(' ', $classes);
    }
    
    /**
     * 取得遮罩層的 CSS 類別
     */
    private function getOverlayClasses(): string
    {
        $classes = [
            'mobile-drawer-overlay',
            'fixed',
            'inset-0',
            'z-40',
            'bg-black',
            'bg-opacity-50',
            'transition-all',
            'duration-300',
            'ease-in-out'
        ];
        
        if ($this->isMobile && $this->sidebarMobile) {
            $classes[] = 'active';
        } else {
            $classes[] = 'opacity-0';
            $classes[] = 'invisible';
        }
        
        return implode(' ', $classes);
    }
    
    /**
     * 啟用/停用觸控手勢
     */
    public function toggleTouchGestures(): void
    {
        $this->touchGesturesEnabled = !$this->touchGesturesEnabled;
        
        // 儲存使用者偏好
        if ($user = auth()->user()) {
            $user->update(['touch_gestures_enabled' => $this->touchGesturesEnabled]);
        }
        
        $this->dispatch('touch-gestures-toggled', enabled: $this->touchGesturesEnabled);
    }
    
    /**
     * 設定滑動開啟側邊欄功能
     */
    public function setSwipeToOpenSidebar(bool $enabled): void
    {
        $this->swipeToOpenSidebar = $enabled;
        
        // 儲存使用者偏好
        if ($user = auth()->user()) {
            $user->update(['swipe_to_open_sidebar' => $enabled]);
        }
    }
    
    /**
     * 取得響應式佈局資訊
     */
    public function getResponsiveInfo(): array
    {
        return [
            'isMobile' => $this->isMobile,
            'isTablet' => $this->isTablet,
            'isDesktop' => $this->isDesktop,
            'viewportWidth' => $this->viewportWidth,
            'sidebarCollapsed' => $this->sidebarCollapsed,
            'sidebarMobile' => $this->sidebarMobile,
            'touchGesturesEnabled' => $this->touchGesturesEnabled,
            'swipeToOpenSidebar' => $this->swipeToOpenSidebar
        ];
    }
    
    /**
     * 取得側邊欄寬度（像素）
     */
    public function getSidebarWidth(): int
    {
        if ($this->isMobile) {
            return 280;
        } elseif ($this->isTablet) {
            return $this->sidebarCollapsed ? 64 : 240;
        } else {
            return $this->sidebarCollapsed ? 64 : 280;
        }
    }
    
    /**
     * 取得頂部導航列高度（像素）
     */
    public function getTopbarHeight(): int
    {
        return 64;
    }

    
    /**
     * 更新側邊欄寬度
     */
    public function updateSidebarWidth(int $width): void
    {
        // 儲存自訂側邊欄寬度到使用者偏好
        if ($user = auth()->user()) {
            $user->update(['sidebar_width' => $width]);
        }
        
        // 發送側邊欄寬度變更事件
        $this->dispatch('sidebar-width-changed', width: $width);
    }
    
    /**
     * 取得載入狀態
     */
    public function getIsLoadingProperty(): bool
    {
        return false; // 可以根據實際需求實作載入狀態邏輯
    }
    
    /**
     * 取得減少動畫偏好
     */
    public function getReducedMotionProperty(): bool
    {
        return $this->accessibilityPreferences['reduced_motion'] ?? false;
    }
    
    /**
     * 預載入關鍵元件
     */
    public function preloadCriticalComponents(): void
    {
        // 觸發關鍵元件的預載入
        $this->dispatch('preload-critical-components');
    }

    /**
     * 取得效能優化設定
     */
    public function getPerformanceSettingsProperty(): array
    {
        return [
            'lazy_loading_enabled' => true,
            'image_optimization' => true,
            'component_caching' => true,
            'service_worker_enabled' => true,
            'performance_monitoring' => auth()->user()->hasPermission('admin.performance.monitor'),
        ];
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.layout.admin-layout');
    }
}