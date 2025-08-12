<?php

namespace App\Livewire\Admin\Layout;

use App\Services\NavigationService;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Route;

/**
 * 麵包屑導航元件
 * 
 * 提供動態麵包屑生成、路徑壓縮、點擊導航和響應式設計功能
 */
class Breadcrumb extends Component
{
    /**
     * 麵包屑項目
     */
    public array $breadcrumbs = [];
    
    /**
     * 是否壓縮路徑（當路徑超過4層時）
     */
    public bool $compressed = false;
    
    /**
     * 最大顯示層級數
     */
    public int $maxLevels = 4;
    
    /**
     * 當前路由名稱
     */
    public string $currentRoute = '';
    
    /**
     * 導航服務
     */
    protected NavigationService $navigationService;
    
    /**
     * 初始化元件
     */
    public function mount(): void
    {
        $this->navigationService = app(NavigationService::class);
        $this->currentRoute = Route::currentRouteName() ?? '';
        $this->loadBreadcrumbs();
    }
    
    /**
     * 載入麵包屑資料
     */
    public function loadBreadcrumbs(): void
    {
        if (!isset($this->navigationService)) {
            $this->navigationService = app(NavigationService::class);
        }
        
        $this->breadcrumbs = $this->navigationService->getCurrentBreadcrumbs($this->currentRoute);
        $this->compressed = count($this->breadcrumbs) > $this->maxLevels;
    }
    
    /**
     * 取得顯示的麵包屑項目（處理壓縮邏輯）
     */
    public function getDisplayBreadcrumbsProperty(): array
    {
        if (!$this->compressed) {
            return $this->breadcrumbs;
        }
        
        // 壓縮邏輯：顯示第一個、省略號、最後兩個
        $breadcrumbs = $this->breadcrumbs;
        $total = count($breadcrumbs);
        
        if ($total <= $this->maxLevels) {
            return $breadcrumbs;
        }
        
        $compressed = [];
        
        // 第一個項目
        $compressed[] = $breadcrumbs[0];
        
        // 省略號標記
        if ($total > 3) {
            $compressed[] = [
                'title' => '...',
                'route' => null,
                'active' => false,
                'ellipsis' => true,
            ];
        }
        
        // 最後兩個項目
        $compressed[] = $breadcrumbs[$total - 2];
        $compressed[] = $breadcrumbs[$total - 1];
        
        return $compressed;
    }
    
    /**
     * 取得完整的麵包屑項目（用於下拉選單）
     */
    public function getFullBreadcrumbsProperty(): array
    {
        return $this->breadcrumbs;
    }
    
    /**
     * 處理麵包屑點擊導航
     */
    public function navigateTo(string $route): void
    {
        if (empty($route)) {
            return;
        }
        
        // 檢查路由是否存在
        if (!Route::has($route)) {
            $this->dispatch('breadcrumb-error', message: '路由不存在：' . $route);
            return;
        }
        
        // 導航到指定路由
        $this->redirect(route($route));
    }
    
    /**
     * 展開壓縮的麵包屑
     */
    public function expandBreadcrumbs(): void
    {
        $this->compressed = false;
    }
    
    /**
     * 壓縮麵包屑
     */
    public function compressBreadcrumbs(): void
    {
        $this->compressed = count($this->breadcrumbs) > $this->maxLevels;
    }
    
    /**
     * 重新載入麵包屑（當路由變更時）
     */
    public function refreshBreadcrumbs(string $routeName = null): void
    {
        if ($routeName) {
            $this->currentRoute = $routeName;
        }
        $this->loadBreadcrumbs();
    }
    
    /**
     * 取得麵包屑的 JSON-LD 結構化資料
     */
    public function getBreadcrumbJsonLdProperty(): string
    {
        $items = [];
        
        foreach ($this->breadcrumbs as $index => $breadcrumb) {
            if (isset($breadcrumb['ellipsis']) && $breadcrumb['ellipsis']) {
                continue;
            }
            
            $item = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $breadcrumb['title'],
            ];
            
            if (!empty($breadcrumb['route'])) {
                $item['item'] = route($breadcrumb['route']);
            }
            
            $items[] = $item;
        }
        
        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
        
        return json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    
    /**
     * 檢查是否為行動裝置
     */
    public function getIsMobileProperty(): bool
    {
        $userAgent = request()->header('User-Agent', '');
        return preg_match('/Mobile|Android|iPhone|iPad/', $userAgent);
    }
    
    /**
     * 監聽麵包屑重新整理事件
     */
    #[On('breadcrumb-refresh')]
    public function handleBreadcrumbRefresh(): void
    {
        $this->loadBreadcrumbs();
    }
    
    /**
     * 監聽路由變更事件
     */
    #[On('route-changed')]
    public function handleRouteChange(string $routeName): void
    {
        $this->refreshBreadcrumbs($routeName);
    }
    
    /**
     * 渲染元件
     */
    public function render()
    {
        // 將 JSON-LD 結構化資料推送到頁面頭部
        $this->pushJsonLdToHead();
        
        return view('livewire.admin.layout.breadcrumb');
    }
    
    /**
     * 將 JSON-LD 結構化資料推送到頁面頭部
     */
    protected function pushJsonLdToHead(): void
    {
        $jsonLd = $this->getBreadcrumbJsonLdProperty();
        
        // 使用 Livewire 的 push 方法將腳本推送到頁面頭部
        $this->dispatch('push-to-head', [
            'type' => 'script',
            'attributes' => ['type' => 'application/ld+json'],
            'content' => $jsonLd
        ]);
    }
}