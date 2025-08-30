# Livewire 分頁功能修復指南

## 問題概述

在 Laravel Livewire 3.0 中，自定義分頁功能常見的兩個問題：
1. **分頁導航錯誤**：點擊分頁按鈕後 URL 錯誤或功能失效
2. **狀態不持久化**：每頁顯示筆數等設定在重新載入後丟失

## 問題診斷

### 症狀 1：分頁按鈕點擊無效或 URL 錯誤
- 分頁按鈕指向 `http://localhost/livewire/update?page=2`
- 點擊後頁面不變或出現錯誤
- 瀏覽器控制台出現 JavaScript 錯誤

### 症狀 2：狀態不持久化
- 設定每頁顯示 10 筆，重新載入後變回 25 筆
- URL 中沒有保存篩選條件和分頁設定
- 無法分享或書籤特定的分頁狀態

## 標準修復方案

### 1. 修復分頁導航（視圖檔案）

**錯誤的做法：**
```blade
{{-- ❌ 使用 Laravel 原生分頁連結 --}}
<a href="{{ $permissions->nextPageUrl() }}">下一頁</a>
<a href="{{ $permissions->getUrlRange(1, $permissions->lastPage())[$page] }}">{{ $page }}</a>
```

**正確的做法：**
```blade
{{-- ✅ 使用 Livewire 方法 --}}
<button wire:click="nextPage">下一頁</button>
<button wire:click="gotoPage({{ $page }})">{{ $page }}</button>
```

**完整的分頁導航模板：**
```blade
{{-- 分頁導航 --}}
@if($permissions->hasPages())
    <div class="flex-shrink-0">
        <div class="flex items-center justify-between">
            {{-- 手機版分頁 --}}
            <div class="flex-1 flex justify-between sm:hidden">
                @if ($permissions->onFirstPage())
                    <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default leading-5 rounded-md">
                        上一頁
                    </span>
                @else
                    <button wire:click="previousPage" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-md hover:text-gray-500 transition-colors">
                        上一頁
                    </button>
                @endif

                @if ($permissions->hasMorePages())
                    <button wire:click="nextPage" class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-md hover:text-gray-500 transition-colors">
                        下一頁
                    </button>
                @else
                    <span class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default leading-5 rounded-md">
                        下一頁
                    </span>
                @endif
            </div>

            {{-- 桌面版分頁 --}}
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700 leading-5 dark:text-gray-400">
                        顯示第
                        <span class="font-medium">{{ $permissions->firstItem() ?? 0 }}</span>
                        到
                        <span class="font-medium">{{ $permissions->lastItem() ?? 0 }}</span>
                        筆，共
                        <span class="font-medium">{{ $permissions->total() }}</span>
                        筆結果
                    </p>
                </div>

                <div>
                    <span class="relative z-0 inline-flex shadow-sm rounded-md">
                        {{-- 上一頁按鈕 --}}
                        @if ($permissions->onFirstPage())
                            <span class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default rounded-l-md leading-5">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        @else
                            <button wire:click="previousPage" class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-l-md leading-5 hover:text-gray-400 transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        @endif

                        {{-- 頁碼按鈕 --}}
                        @for ($page = 1; $page <= $permissions->lastPage(); $page++)
                            @if ($page == $permissions->currentPage())
                                <span aria-current="page">
                                    <span class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-white bg-blue-600 border border-blue-600 cursor-default leading-5">{{ $page }}</span>
                                </span>
                            @else
                                <button wire:click="gotoPage({{ $page }})" class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 hover:text-gray-500 transition-colors">
                                    {{ $page }}
                                </button>
                            @endif
                        @endfor

                        {{-- 下一頁按鈕 --}}
                        @if ($permissions->hasMorePages())
                            <button wire:click="nextPage" class="relative inline-flex items-center px-2 py-2 -ml-px text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-r-md leading-5 hover:text-gray-400 transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        @else
                            <span class="relative inline-flex items-center px-2 py-2 -ml-px text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default rounded-r-md leading-5">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        @endif
                    </span>
                </div>
            </div>
        </div>
    </div>
@endif
```

### 2. 實作狀態持久化（Livewire 元件）

**步驟 1：添加 queryString 屬性**
```php
class YourListComponent extends Component
{
    use WithPagination;
    
    // 分頁相關屬性
    public int $perPage = 25;
    public array $perPageOptions = [10, 25, 50, 100];
    
    // 搜尋和篩選屬性
    public string $search = '';
    public string $moduleFilter = 'all';
    public string $typeFilter = 'all';
    
    // URL 查詢字串屬性（用於狀態持久化）
    protected $queryString = [
        'search' => ['except' => ''],
        'moduleFilter' => ['except' => 'all'],
        'typeFilter' => ['except' => 'all'],
        'perPage' => ['except' => 25],
        // 注意：不要添加 'page'，Livewire 會自動處理
    ];
}
```

**步驟 2：添加分頁方法**
```php
/**
 * 前往指定頁面
 */
public function gotoPage(int $page): void
{
    $this->setPage($page);
}

/**
 * 每頁顯示筆數更新時重置分頁
 */
public function updatedPerPage(): void
{
    try {
        // 驗證 perPage 值
        if (!in_array($this->perPage, $this->perPageOptions)) {
            $this->perPage = 25; // 重置為預設值
        }
        
        $this->resetPage();
        $this->clearCache(); // 如果有快取機制
        
        // 發送更新事件
        $this->dispatch('per-page-updated', perPage: $this->perPage);
        
    } catch (\Exception $e) {
        logger()->error('Error updating perPage', [
            'error' => $e->getMessage(),
            'perPage' => $this->perPage
        ]);
        
        // 重置為預設值
        $this->perPage = 25;
        $this->resetPage();
    }
}
```

**步驟 3：從 URL 參數初始化狀態**
```php
/**
 * 元件掛載時執行
 */
public function mount(): void
{
    // 檢查權限
    if (!auth()->user()->hasPermission('your.permission')) {
        abort(403);
    }

    // 從 URL 參數初始化狀態
    $this->initializeFromQueryString();
}

/**
 * 從 URL 查詢字串初始化狀態
 */
private function initializeFromQueryString(): void
{
    $request = request();
    
    $this->search = $request->get('search', '');
    $this->moduleFilter = $request->get('moduleFilter', 'all');
    $this->typeFilter = $request->get('typeFilter', 'all');
    
    // 驗證並設定 perPage
    $requestedPerPage = (int) $request->get('perPage', 25);
    if (in_array($requestedPerPage, $this->perPageOptions)) {
        $this->perPage = $requestedPerPage;
    }
}
```

### 3. 每頁顯示筆數選擇器

```blade
{{-- 每頁顯示筆數選擇器 --}}
<div class="flex items-center space-x-3">
    <label for="perPage" class="text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
        每頁顯示：
    </label>
    <select 
        id="perPage"
        wire:model.live="perPage"
        class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent min-w-[80px]"
    >
        @foreach($perPageOptions as $option)
            <option value="{{ $option }}">{{ $option }} 筆</option>
        @endforeach
    </select>
    <span class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
        共 {{ $permissions->total() }} 筆
    </span>
</div>
```

## 常見錯誤和解決方案

### 錯誤 1：ReflectionProperty 錯誤
```
ReflectionProperty->__construct(Object(App\Livewire\...), 'page')
```

**原因：** 在 `$queryString` 中添加了 `'page'` 屬性，但元件中沒有對應的公開屬性。

**解決方案：** 從 `$queryString` 中移除 `'page'`，Livewire 會自動處理分頁參數。

### 錯誤 2：分頁按鈕 URL 錯誤
**原因：** 使用了 Laravel 原生的分頁連結而不是 Livewire 方法。

**解決方案：** 將所有 `<a href="...">` 改為 `<button wire:click="...">`。

### 錯誤 3：狀態不同步
**原因：** 快取機制干擾或沒有正確處理狀態更新。

**解決方案：** 
- 在狀態更新方法中調用 `$this->clearCache()`
- 確保 `updatedPerPage()` 方法中調用 `$this->resetPage()`

## 測試檢查清單

### 基本功能測試
- [ ] 每頁顯示筆數選擇器正常工作
- [ ] 分頁按鈕可以正確導航
- [ ] 上一頁/下一頁按鈕正常
- [ ] 頁碼按鈕可以直接跳轉

### 狀態持久化測試
- [ ] 變更每頁顯示筆數後 URL 包含 `?perPage=X`
- [ ] 點擊分頁後 URL 包含 `?page=X`
- [ ] 重新載入頁面時狀態正確恢復
- [ ] 直接訪問帶參數的 URL 時狀態正確

### 用戶體驗測試
- [ ] 分頁資訊使用中文顯示
- [ ] 按鈕和選擇器間距適當
- [ ] 響應式設計在手機版正常
- [ ] 沒有 JavaScript 錯誤

## 自動化測試腳本

```javascript
// 在瀏覽器控制台中執行的測試腳本
async function testPagination() {
    console.log('=== 分頁功能測試 ===');
    
    // 1. 測試每頁顯示筆數
    const perPageSelector = document.querySelector('#perPage');
    if (perPageSelector) {
        perPageSelector.value = '10';
        perPageSelector.dispatchEvent(new Event('change', { bubbles: true }));
        console.log('✓ 每頁顯示筆數變更為 10 筆');
        
        await new Promise(resolve => setTimeout(resolve, 2000));
        
        // 檢查 URL
        if (window.location.href.includes('perPage=10')) {
            console.log('✓ URL 正確包含 perPage 參數');
        } else {
            console.log('✗ URL 缺少 perPage 參數');
        }
    }
    
    // 2. 測試分頁導航
    const page2Button = document.querySelector('button[wire\\:click="gotoPage(2)"]');
    if (page2Button) {
        page2Button.click();
        console.log('✓ 點擊第二頁按鈕');
        
        await new Promise(resolve => setTimeout(resolve, 2000));
        
        // 檢查 URL 和狀態
        if (window.location.href.includes('page=2')) {
            console.log('✓ URL 正確包含 page 參數');
        }
        
        const currentPageSpan = document.querySelector('span[aria-current="page"] span');
        if (currentPageSpan?.textContent === '2') {
            console.log('✓ 當前頁碼顯示正確');
        }
    }
    
    console.log('=== 測試完成 ===');
}

// 執行測試
testPagination();
```

## 適用範圍

此修復方案適用於所有使用 Livewire 3.0 的列表頁面，包括：
- 使用者管理列表
- 角色管理列表  
- 權限管理列表
- 活動記錄列表
- 通知列表
- 設定列表
- 其他需要分頁功能的 Livewire 元件

## 注意事項

1. **Livewire 版本**：此方案適用於 Livewire 3.0+
2. **命名空間**：確保使用正確的 Livewire 命名空間 `App\Livewire`
3. **權限檢查**：記得在 `mount()` 方法中添加適當的權限檢查
4. **錯誤處理**：在狀態更新方法中添加 try-catch 錯誤處理
5. **快取清理**：狀態更新時記得清除相關快取

遵循此指南可以確保 Livewire 分頁功能的穩定性和良好的用戶體驗。