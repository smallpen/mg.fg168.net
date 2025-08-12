<?php

namespace App\Livewire\Admin\Layout;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Services\SearchService;
use Illuminate\Support\Collection;

/**
 * 全域搜尋元件
 * 
 * 提供管理後台的全域搜尋功能，包括：
 * - 即時搜尋
 * - 搜尋結果分類顯示
 * - 鍵盤快捷鍵支援
 * - 搜尋建議
 * - 搜尋歷史
 */
class GlobalSearch extends Component
{
    // 搜尋狀態
    public string $query = '';
    public array $results = [];
    public bool $isOpen = false;
    public string $selectedCategory = 'all';
    public int $selectedIndex = -1;
    
    // 搜尋配置
    public bool $showSuggestions = true;
    public bool $showHistory = true;
    public int $maxResults = 20;
    
    // 搜尋建議和歷史
    public array $suggestions = [];
    public array $searchHistory = [];
    
    // 鍵盤導航
    public bool $keyboardNavigation = true;
    
    // 搜尋服務
    protected SearchService $searchService;
    
    /**
     * 元件初始化
     */
    public function boot(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }
    
    /**
     * 元件掛載
     */
    public function mount()
    {
        $this->loadSearchHistory();
    }
    
    /**
     * 計算屬性：取得搜尋結果
     */
    public function getSearchResultsProperty(): array
    {
        if (empty($this->query) || strlen($this->query) < 2) {
            return [];
        }
        
        $options = [
            'limit' => $this->maxResults,
            'categories' => $this->selectedCategory === 'all' 
                ? ['pages', 'users', 'roles', 'permissions']
                : [$this->selectedCategory],
        ];
        
        return $this->searchService->globalSearch($this->query, auth()->user(), $options);
    }
    
    /**
     * 計算屬性：取得可用分類
     */
    public function getCategoriesProperty(): array
    {
        return [
            'all' => '全部',
            'pages' => '頁面',
            'users' => '使用者',
            'roles' => '角色',
            'permissions' => '權限',
        ];
    }
    
    /**
     * 計算屬性：取得扁平化的搜尋結果
     */
    public function getFlatResultsProperty(): array
    {
        $flatResults = [];
        $results = $this->getSearchResultsProperty();
        
        foreach ($results as $category => $data) {
            foreach ($data['items'] as $item) {
                $flatResults[] = $item;
            }
        }
        
        return $flatResults;
    }
    
    /**
     * 開啟搜尋框
     */
    public function open(): void
    {
        $this->isOpen = true;
        $this->selectedIndex = -1;
        
        if (empty($this->query)) {
            $this->loadSuggestions();
        }
        
        $this->dispatch('search-opened');
    }
    
    /**
     * 關閉搜尋框
     */
    public function close(): void
    {
        $this->isOpen = false;
        $this->selectedIndex = -1;
        $this->dispatch('search-closed');
    }
    
    /**
     * 切換搜尋框
     */
    public function toggle(): void
    {
        if ($this->isOpen) {
            $this->close();
        } else {
            $this->open();
        }
    }
    
    /**
     * 搜尋查詢更新時觸發
     */
    public function updatedQuery(): void
    {
        $this->selectedIndex = -1;
        
        if (strlen($this->query) >= 2) {
            $this->search();
            $this->loadSuggestions();
        } else {
            $this->results = [];
            $this->suggestions = [];
        }
    }
    
    /**
     * 執行搜尋
     */
    public function search(): void
    {
        if (strlen($this->query) < 2) {
            $this->results = [];
            return;
        }
        
        $this->results = $this->getSearchResultsProperty();
        
        // 如果有結果，重置選中索引
        if (!empty($this->results)) {
            $this->selectedIndex = -1;
        }
    }
    
    /**
     * 選擇搜尋結果
     */
    public function selectResult(string $type, int $id, ?string $url = null)
    {
        // 記錄到搜尋歷史
        $this->addToSearchHistory($this->query);
        
        // 清除搜尋
        $this->clearSearch();
        
        // 導航到結果頁面
        if ($url) {
            return redirect($url);
        }
        
        // 根據類型導航
        switch ($type) {
            case 'user':
                return redirect()->route('admin.users.show', $id);
            case 'role':
                return redirect()->route('admin.roles.show', $id);
            case 'permission':
                return redirect()->route('admin.permissions.show', $id);
            case 'page':
                return redirect()->route($id);
            default:
                break;
        }
    }
    
    /**
     * 選擇建議
     */
    public function selectSuggestion(string $text): void
    {
        $this->query = $text;
        $this->search();
    }
    
    /**
     * 清除搜尋
     */
    public function clearSearch(): void
    {
        $this->query = '';
        $this->results = [];
        $this->suggestions = [];
        $this->selectedIndex = -1;
        $this->close();
    }
    
    /**
     * 設定分類篩選
     */
    public function setCategory(string $category): void
    {
        $this->selectedCategory = $category;
        $this->selectedIndex = -1;
        
        if (!empty($this->query)) {
            $this->search();
        }
    }
    
    /**
     * 處理鍵盤事件
     */
    public function handleKeydown(string $key): void
    {
        if (!$this->keyboardNavigation) {
            return;
        }
        
        $flatResults = $this->getFlatResultsProperty();
        $maxIndex = max(count($flatResults) - 1, -1);
        
        switch ($key) {
            case 'ArrowDown':
                if ($maxIndex >= 0) {
                    $this->selectedIndex = min($this->selectedIndex + 1, $maxIndex);
                }
                break;
                
            case 'ArrowUp':
                $this->selectedIndex = max($this->selectedIndex - 1, -1);
                break;
                
            case 'Enter':
                if ($this->selectedIndex >= 0 && isset($flatResults[$this->selectedIndex])) {
                    $result = $flatResults[$this->selectedIndex];
                    $this->selectResult($result['type'], $result['id'], $result['url'] ?? null);
                }
                break;
                
            case 'Escape':
                $this->clearSearch();
                break;
        }
    }
    
    /**
     * 處理全域鍵盤快捷鍵
     */
    #[On('global-shortcut')]
    public function handleGlobalShortcut(string $shortcut): void
    {
        switch ($shortcut) {
            case 'ctrl+k':
            case 'cmd+k':
                $this->toggle();
                break;
                
            case 'ctrl+shift+f':
            case 'cmd+shift+f':
                $this->open();
                break;
        }
    }
    
    /**
     * 載入搜尋建議
     */
    protected function loadSuggestions(): void
    {
        if (!$this->showSuggestions) {
            return;
        }
        
        if (strlen($this->query) >= 2) {
            $this->suggestions = $this->searchService->getSearchSuggestions($this->query);
        } else {
            // 顯示熱門搜尋
            $popular = $this->searchService->getPopularSearches();
            $this->suggestions = array_map(function ($search) {
                return [
                    'text' => $search,
                    'type' => 'popular',
                    'icon' => 'fire',
                ];
            }, array_slice($popular, 0, 5));
        }
    }
    
    /**
     * 載入搜尋歷史
     */
    protected function loadSearchHistory(): void
    {
        if (!$this->showHistory) {
            return;
        }
        
        $userId = auth()->id();
        $this->searchHistory = session()->get("search_history_{$userId}", []);
    }
    
    /**
     * 新增到搜尋歷史
     */
    protected function addToSearchHistory(string $query): void
    {
        if (!$this->showHistory || empty($query)) {
            return;
        }
        
        $userId = auth()->id();
        $history = session()->get("search_history_{$userId}", []);
        
        // 移除重複項目
        $history = array_filter($history, function ($item) use ($query) {
            return $item !== $query;
        });
        
        // 新增到開頭
        array_unshift($history, $query);
        
        // 限制歷史記錄數量
        $history = array_slice($history, 0, 10);
        
        session()->put("search_history_{$userId}", $history);
        $this->searchHistory = $history;
    }
    
    /**
     * 清除搜尋歷史
     */
    public function clearSearchHistory(): void
    {
        $userId = auth()->id();
        session()->forget("search_history_{$userId}");
        $this->searchHistory = [];
    }
    
    /**
     * 取得結果總數
     */
    public function getTotalResultsCount(): int
    {
        $total = 0;
        $results = $this->getSearchResultsProperty();
        
        foreach ($results as $category) {
            $total += $category['total'] ?? count($category['items']);
        }
        
        return $total;
    }
    
    /**
     * 檢查是否有結果
     */
    public function hasResults(): bool
    {
        return !empty($this->getSearchResultsProperty());
    }
    
    /**
     * 檢查是否顯示空狀態
     */
    public function shouldShowEmptyState(): bool
    {
        return !empty($this->query) && strlen($this->query) >= 2 && !$this->hasResults();
    }
    
    /**
     * 取得空狀態訊息
     */
    public function getEmptyStateMessage(): string
    {
        return "找不到與「{$this->query}」相關的結果";
    }
    
    /**
     * 取得搜尋提示
     */
    public function getSearchPlaceholder(): string
    {
        return '搜尋使用者、角色、權限或頁面... (Ctrl+K)';
    }
    
    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.layout.global-search', [
            'searchResults' => $this->getSearchResultsProperty(),
            'categories' => $this->getCategoriesProperty(),
            'flatResults' => $this->getFlatResultsProperty(),
            'totalResults' => $this->getTotalResultsCount(),
            'hasResults' => $this->hasResults(),
            'showEmptyState' => $this->shouldShowEmptyState(),
            'emptyStateMessage' => $this->getEmptyStateMessage(),
            'searchPlaceholder' => $this->getSearchPlaceholder(),
        ]);
    }
}