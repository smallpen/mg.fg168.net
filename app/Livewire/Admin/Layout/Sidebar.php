<?php

namespace App\Livewire\Admin\Layout;

use App\Services\NavigationService;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Support\Collection;

/**
 * 側邊導航選單元件
 * 
 * 負責顯示管理後台的主要導航選單，包括：
 * - 多層級選單結構顯示
 * - 選單展開/收合功能
 * - 選單項目權限控制
 * - 選單搜尋功能
 * - 響應式收合模式
 */
class Sidebar extends Component
{
    // 選單狀態
    public bool $collapsed = false;
    public array $expandedMenus = [];
    public string $activeMenu = '';
    public string $menuSearch = '';
    
    // 搜尋狀態
    public bool $showSearch = false;
    public array $searchResults = [];
    
    /**
     * 元件初始化
     */
    public function mount()
    {
        $this->initializeMenuState();
    }
    
    /**
     * 初始化選單狀態
     */
    protected function initializeMenuState(): void
    {
        $currentRoute = request()->route()?->getName();
        if ($currentRoute) {
            $this->setActiveMenuFromRoute($currentRoute);
        }
        
        // 從 session 恢復收合狀態
        $this->collapsed = session('sidebar.collapsed', false);
        $this->expandedMenus = session('sidebar.expanded', []);
    }
    
    /**
     * 計算屬性：取得選單項目
     */
    public function getMenuItemsProperty(): Collection
    {
        $user = auth()->user();
        $navigationService = app(NavigationService::class);
        $menuItems = $navigationService->getUserMenuStructure($user);
        
        return collect($menuItems);
    }
    
    /**
     * 計算屬性：取得過濾後的選單（搜尋）
     */
    public function getFilteredMenusProperty(): Collection
    {
        if (empty($this->menuSearch)) {
            return $this->menuItems;
        }
        
        return $this->searchMenuItems($this->menuItems, $this->menuSearch);
    }
    
    /**
     * 計算屬性：取得當前路由
     */
    public function getCurrentRouteProperty(): string
    {
        return request()->route()?->getName() ?? '';
    }
    
    /**
     * 選單展開/收合切換
     */
    public function toggleMenu(string $menuKey): void
    {
        if (in_array($menuKey, $this->expandedMenus)) {
            $this->expandedMenus = array_diff($this->expandedMenus, [$menuKey]);
        } else {
            $this->expandedMenus[] = $menuKey;
        }
        
        // 儲存到 session
        session(['sidebar.expanded' => $this->expandedMenus]);
    }
    
    /**
     * 設定活躍選單
     */
    public function setActiveMenu(string $menuKey): void
    {
        $this->activeMenu = $menuKey;
    }
    
    /**
     * 側邊欄收合切換
     */
    public function toggleCollapse(): void
    {
        $this->collapsed = !$this->collapsed;
        
        // 儲存到 session
        session(['sidebar.collapsed' => $this->collapsed]);
        
        // 觸發佈局更新事件
        $this->dispatch('sidebar-toggled', collapsed: $this->collapsed);
    }
    
    /**
     * 選單搜尋更新
     */
    public function updatedMenuSearch(): void
    {
        if (empty($this->menuSearch)) {
            $this->showSearch = false;
            $this->searchResults = [];
        } else {
            $this->showSearch = true;
            $this->performSearch();
        }
    }
    
    /**
     * 清除選單搜尋
     */
    public function clearMenuSearch(): void
    {
        $this->menuSearch = '';
        $this->showSearch = false;
        $this->searchResults = [];
    }
    
    /**
     * 執行搜尋
     */
    protected function performSearch(): void
    {
        $this->searchResults = $this->searchMenuItems($this->menuItems, $this->menuSearch)->toArray();
    }
    
    /**
     * 搜尋選單項目
     */
    protected function searchMenuItems(Collection $items, string $query): Collection
    {
        $results = collect();
        
        foreach ($items as $item) {
            // 檢查標題是否符合搜尋條件
            if (str_contains(strtolower($item['title'] ?? ''), strtolower($query))) {
                $results->push($item);
            }
            
            // 如果有子選單，遞迴搜尋
            if (isset($item['children'])) {
                $childResults = $this->searchMenuItems(collect($item['children']), $query);
                
                // 將符合的子項目加入結果，並標記父項目
                foreach ($childResults as $child) {
                    $child['parent'] = $item['title'] ?? '';
                    $results->push($child);
                }
            }
        }
        
        return $results;
    }
    
    /**
     * 從路由設定活躍選單
     */
    protected function setActiveMenuFromRoute(string $routeName): void
    {
        $this->findAndSetActiveMenu($this->menuItems, $routeName);
    }
    
    /**
     * 遞迴尋找並設定活躍選單
     */
    protected function findAndSetActiveMenu(Collection $items, string $routeName): bool
    {
        foreach ($items as $item) {
            // 檢查當前項目
            if (isset($item['route']) && $this->isActiveRoute($item['route'])) {
                $this->activeMenu = $item['key'] ?? '';
                
                // 如果是子選單項目，展開父選單
                $parentKey = $this->findParentKey($item['key'] ?? '');
                if ($parentKey && !in_array($parentKey, $this->expandedMenus)) {
                    $this->expandedMenus[] = $parentKey;
                }
                
                return true;
            }
            
            // 檢查子選單
            if (isset($item['children'])) {
                if ($this->findAndSetActiveMenu(collect($item['children']), $routeName)) {
                    // 展開父選單
                    if (!in_array($item['key'] ?? '', $this->expandedMenus)) {
                        $this->expandedMenus[] = $item['key'] ?? '';
                    }
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * 尋找父選單鍵值
     */
    protected function findParentKey(string $childKey): ?string
    {
        foreach ($this->menuItems as $item) {
            if (isset($item['children'])) {
                foreach ($item['children'] as $child) {
                    if (($child['key'] ?? '') === $childKey) {
                        return $item['key'] ?? null;
                    }
                }
            }
        }
        
        return null;
    }
    
    /**
     * 檢查路由是否為當前活躍路由
     */
    public function isActiveRoute(string $route): bool
    {
        return request()->routeIs($route) || request()->routeIs($route . '.*');
    }
    
    /**
     * 檢查選單是否展開
     */
    public function isMenuExpanded(string $menuKey): bool
    {
        return in_array($menuKey, $this->expandedMenus);
    }
    
    /**
     * 檢查選單是否活躍
     */
    public function isMenuActive(array $menuItem): bool
    {
        // 檢查當前項目
        if (isset($menuItem['route']) && $this->isActiveRoute($menuItem['route'])) {
            return true;
        }
        
        // 檢查子選單
        if (isset($menuItem['children'])) {
            foreach ($menuItem['children'] as $child) {
                if (isset($child['route']) && $this->isActiveRoute($child['route'])) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * 取得圖示 SVG
     */
    public function getIcon(string $iconName): string
    {
        $icons = [
            'chart-bar' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>',
            
            'users' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>',
            
            'shield-check' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>',
            
            'key' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 16.5H9v1.5H7.5V20H3v-5.243L11.257 6.743A6 6 0 0121 9z"></path>',
            
            'cog' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>',
            
            'clipboard-list' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>',
            
            'monitor' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>',
            
            'activity' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>',
            
            'search' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>',
        ];
        
        return $icons[$iconName] ?? $icons['chart-bar'];
    }
    
    /**
     * 監聽佈局變更事件
     */
    #[On('layout-changed')]
    public function handleLayoutChange(bool $collapsed): void
    {
        $this->collapsed = $collapsed;
    }
    
    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.layout.sidebar', [
            'menuItems' => $this->filteredMenus,
            'searchResults' => $this->searchResults,
        ]);
    }
}