# 元件 API 參考文檔

## 概述

本文檔詳細說明管理後台佈局和導航系統中所有 Livewire 元件的 API、屬性、方法和事件。

## AdminLayout 主佈局元件

**檔案位置**: `app/Livewire/Admin/Layout/AdminLayout.php`

### 公開屬性

```php
// 佈局狀態
public bool $sidebarCollapsed = false;      // 側邊選單是否收合
public bool $sidebarMobile = false;         // 手機版選單是否開啟
public string $currentTheme = 'light';      // 當前主題
public string $currentLocale = 'zh_TW';     // 當前語言

// 頁面資訊
public string $pageTitle = '';              // 頁面標題
public array $breadcrumbs = [];             // 麵包屑導航
public array $pageActions = [];             // 頁面操作按鈕
```

### 計算屬性

```php
public function getLayoutClassesProperty(): string
// 回傳: 佈局 CSS 類別字串
// 用途: 根據當前狀態生成佈局樣式類別

public function getIsMobileProperty(): bool
// 回傳: 是否為手機版檢視
// 用途: 判斷當前是否為手機版佈局

public function getCurrentUserProperty(): ?User
// 回傳: 當前登入使用者
// 用途: 取得當前使用者資訊
```

### 公開方法

```php
public function toggleSidebar(): void
// 用途: 切換側邊選單收合狀態
// 觸發事件: 'sidebar-toggled'

public function toggleMobileSidebar(): void
// 用途: 切換手機版選單開啟狀態
// 觸發事件: 'mobile-sidebar-toggled'

public function setTheme(string $theme): void
// 參數: $theme - 主題名稱 ('light', 'dark', 'auto')
// 用途: 設定當前主題
// 觸發事件: 'theme-changed'

public function setLocale(string $locale): void
// 參數: $locale - 語言代碼 ('zh_TW', 'en')
// 用途: 設定當前語言
// 觸發事件: 'locale-changed'

public function setPageTitle(string $title): void
// 參數: $title - 頁面標題
// 用途: 設定當前頁面標題

public function setBreadcrumbs(array $breadcrumbs): void
// 參數: $breadcrumbs - 麵包屑陣列
// 格式: [['label' => '標籤', 'url' => '網址'], ...]
// 用途: 設定麵包屑導航

public function addPageAction(array $action): void
// 參數: $action - 操作按鈕配置
// 格式: ['label' => '標籤', 'type' => '類型', 'action' => '動作']
// 用途: 新增頁面操作按鈕
```

### 事件監聽

```php
#[On('theme-changed')]
public function handleThemeChange(string $theme): void
// 監聽: 主題變更事件
// 用途: 處理主題變更後的邏輯

#[On('locale-changed')]
public function handleLocaleChange(string $locale): void
// 監聽: 語言變更事件
// 用途: 處理語言變更後的邏輯
```

### 使用範例

```blade
<livewire:admin.layout.admin-layout 
    :page-title="'使用者管理'"
    :breadcrumbs="[
        ['label' => '首頁', 'url' => route('admin.dashboard')],
        ['label' => '使用者管理', 'url' => null]
    ]"
/>
```

## TopNavBar 頂部導航元件

**檔案位置**: `app/Livewire/Admin/Layout/TopNavBar.php`

### 公開屬性

```php
// 搜尋功能
public string $globalSearch = '';           // 搜尋關鍵字
public array $searchResults = [];           // 搜尋結果
public bool $showSearchResults = false;     // 是否顯示搜尋結果

// 通知功能
public int $unreadNotifications = 0;        // 未讀通知數量
public array $recentNotifications = [];     // 最近通知列表
public bool $showNotifications = false;     // 是否顯示通知面板
```

### 計算屬性

```php
public function getSearchResultsProperty(): Collection
// 回傳: 格式化的搜尋結果集合
// 用途: 取得分類後的搜尋結果

public function getNotificationsProperty(): Collection
// 回傳: 使用者通知集合
// 用途: 取得當前使用者的通知列表

public function getCurrentUserProperty(): User
// 回傳: 當前登入使用者
// 用途: 取得使用者資訊用於顯示
```

### 公開方法

```php
public function updatedGlobalSearch(): void
// 觸發時機: globalSearch 屬性更新時
// 用途: 執行即時搜尋

public function selectSearchResult(string $type, int $id): void
// 參數: $type - 結果類型, $id - 項目 ID
// 用途: 選擇搜尋結果並導航

public function clearSearch(): void
// 用途: 清除搜尋內容和結果

public function toggleNotifications(): void
// 用途: 切換通知面板顯示狀態

public function markAsRead(int $notificationId): void
// 參數: $notificationId - 通知 ID
// 用途: 標記特定通知為已讀

public function markAllAsRead(): void
// 用途: 標記所有通知為已讀

public function logout(): void
// 用途: 使用者登出

public function goToProfile(): void
// 用途: 導航到個人資料頁面
```

### 事件監聽

```php
#[On('notification-received')]
public function handleNewNotification(array $notification): void
// 監聽: 新通知接收事件
// 用途: 處理即時通知更新
```

## Sidebar 側邊選單元件

**檔案位置**: `app/Livewire/Admin/Layout/Sidebar.php`

### 公開屬性

```php
public bool $collapsed = false;             // 選單是否收合
public array $expandedMenus = [];           // 展開的選單項目
public string $activeMenu = '';             // 當前啟用選單
public string $menuSearch = '';             // 選單搜尋關鍵字
```

### 計算屬性

```php
public function getMenuItemsProperty(): Collection
// 回傳: 選單項目集合
// 用途: 取得根據權限過濾的選單結構

public function getFilteredMenusProperty(): Collection
// 回傳: 過濾後的選單集合
// 用途: 根據搜尋關鍵字過濾選單

public function getCurrentRouteProperty(): string
// 回傳: 當前路由名稱
// 用途: 判斷選單項目的啟用狀態
```

### 公開方法

```php
public function toggleMenu(string $menuKey): void
// 參數: $menuKey - 選單鍵值
// 用途: 切換子選單展開/收合狀態

public function setActiveMenu(string $menuKey): void
// 參數: $menuKey - 選單鍵值
// 用途: 設定當前啟用的選單項目

public function toggleCollapse(): void
// 用途: 切換選單收合狀態

public function updatedMenuSearch(): void
// 觸發時機: menuSearch 屬性更新時
// 用途: 執行選單搜尋過濾

public function clearMenuSearch(): void
// 用途: 清除選單搜尋內容
```

### 受保護方法

```php
protected function hasMenuPermission(array $menu): bool
// 參數: $menu - 選單項目配置
// 回傳: 是否有權限存取
// 用途: 檢查使用者對選單項目的權限

protected function filterMenuByPermissions(Collection $menus): Collection
// 參數: $menus - 選單集合
// 回傳: 過濾後的選單集合
// 用途: 根據使用者權限過濾選單
```

## NotificationCenter 通知中心元件

**檔案位置**: `app/Livewire/Admin/Layout/NotificationCenter.php`

### 公開屬性

```php
public bool $isOpen = false;                // 通知面板是否開啟
public string $filter = 'all';             // 通知過濾器
public int $perPage = 10;                   // 每頁顯示數量
```

### 計算屬性

```php
public function getNotificationsProperty(): LengthAwarePaginator
// 回傳: 分頁的通知集合
// 用途: 取得過濾和分頁後的通知列表

public function getUnreadCountProperty(): int
// 回傳: 未讀通知數量
// 用途: 顯示通知徽章數字

public function getNotificationTypesProperty(): array
// 回傳: 通知類型陣列
// 用途: 取得可用的通知類型過濾選項
```

### 公開方法

```php
public function toggle(): void
// 用途: 切換通知面板開啟/關閉狀態

public function markAsRead(int $notificationId): void
// 參數: $notificationId - 通知 ID
// 用途: 標記特定通知為已讀

public function markAllAsRead(): void
// 用途: 標記所有通知為已讀

public function deleteNotification(int $notificationId): void
// 參數: $notificationId - 通知 ID
// 用途: 刪除特定通知

public function setFilter(string $filter): void
// 參數: $filter - 過濾器類型 ('all', 'unread', 'security')
// 用途: 設定通知過濾器

public function showBrowserNotification(array $notification): void
// 參數: $notification - 通知資料
// 用途: 顯示瀏覽器原生通知
```

### 事件監聽

```php
#[On('notification-received')]
public function addNotification(array $notification): void
// 監聽: 新通知接收事件
// 用途: 新增新通知到列表
```

## GlobalSearch 全域搜尋元件

**檔案位置**: `app/Livewire/Admin/Layout/GlobalSearch.php`

### 公開屬性

```php
public string $query = '';                  // 搜尋查詢字串
public array $results = [];                 // 搜尋結果
public bool $isOpen = false;                // 搜尋面板是否開啟
public string $selectedCategory = 'all';    // 選中的分類
```

### 受保護屬性

```php
protected array $searchableModels = [
    'users' => User::class,
    'roles' => Role::class,
    'permissions' => Permission::class,
    'activities' => Activity::class,
];
```

### 計算屬性

```php
public function getSearchResultsProperty(): array
// 回傳: 格式化的搜尋結果陣列
// 用途: 取得分類和格式化的搜尋結果

public function getCategoriesProperty(): array
// 回傳: 搜尋分類陣列
// 用途: 取得可搜尋的分類列表
```

### 公開方法

```php
public function updatedQuery(): void
// 觸發時機: query 屬性更新時
// 用途: 執行即時搜尋

public function search(): void
// 用途: 執行搜尋操作

public function selectResult(string $type, int $id): void
// 參數: $type - 結果類型, $id - 項目 ID
// 用途: 選擇搜尋結果並導航

public function clearSearch(): void
// 用途: 清除搜尋內容和結果

public function handleKeydown(string $key): void
// 參數: $key - 按鍵代碼
// 用途: 處理鍵盤快捷鍵
```

### 受保護方法

```php
protected function searchInModel(string $model, string $query): Collection
// 參數: $model - 模型類別, $query - 搜尋查詢
// 回傳: 搜尋結果集合
// 用途: 在特定模型中執行搜尋

protected function formatResults(Collection $results, string $type): array
// 參數: $results - 搜尋結果, $type - 結果類型
// 回傳: 格式化的結果陣列
// 用途: 格式化搜尋結果用於顯示
```

## ThemeToggle 主題切換元件

**檔案位置**: `app/Livewire/Admin/Layout/ThemeToggle.php`

### 公開屬性

```php
public string $currentTheme = 'light';      // 當前主題
public array $availableThemes = [           // 可用主題列表
    'light', 'dark', 'auto'
];
```

### 計算屬性

```php
public function getThemeIconProperty(): string
// 回傳: 當前主題對應的圖示名稱
// 用途: 顯示主題切換按鈕圖示

public function getThemeNameProperty(): string
// 回傳: 當前主題的顯示名稱
// 用途: 顯示主題名稱
```

### 公開方法

```php
public function setTheme(string $theme): void
// 參數: $theme - 主題名稱
// 用途: 設定當前主題
// 觸發事件: 'theme-changed'

public function toggleTheme(): void
// 用途: 循環切換主題

public function detectSystemTheme(): string
// 回傳: 系統主題 ('light' 或 'dark')
// 用途: 檢測系統主題偏好
```

### 受保護方法

```php
protected function applyTheme(string $theme): void
// 參數: $theme - 主題名稱
// 用途: 應用主題到 DOM

protected function saveThemePreference(string $theme): void
// 參數: $theme - 主題名稱
// 用途: 儲存使用者主題偏好
```

### 事件監聽

```php
#[On('system-theme-changed')]
public function handleSystemThemeChange(string $theme): void
// 監聽: 系統主題變更事件
// 用途: 處理系統主題變更
```

## 服務類別 API

### NavigationService

**檔案位置**: `app/Services/NavigationService.php`

```php
public function getMenuStructure(): array
// 回傳: 選單結構陣列
// 用途: 取得完整的選單結構

public function filterMenuByPermissions(array $menu, User $user): array
// 參數: $menu - 選單陣列, $user - 使用者物件
// 回傳: 過濾後的選單陣列
// 用途: 根據使用者權限過濾選單

public function getCurrentBreadcrumbs(string $route): array
// 參數: $route - 路由名稱
// 回傳: 麵包屑陣列
// 用途: 根據當前路由生成麵包屑

public function getQuickActions(User $user): array
// 參數: $user - 使用者物件
// 回傳: 快速操作陣列
// 用途: 取得使用者可用的快速操作

public function buildMenuTree(array $items, ?int $parentId = null): array
// 參數: $items - 選單項目陣列, $parentId - 父項目 ID
// 回傳: 樹狀結構選單陣列
// 用途: 建立階層式選單結構

public function cacheMenuStructure(User $user): void
// 參數: $user - 使用者物件
// 用途: 快取使用者的選單結構

public function clearMenuCache(): void
// 用途: 清除選單快取
```

### NotificationService

**檔案位置**: `app/Services/NotificationService.php`

```php
public function getUserNotifications(User $user, array $filters = []): LengthAwarePaginator
// 參數: $user - 使用者物件, $filters - 過濾條件
// 回傳: 分頁的通知集合
// 用途: 取得使用者通知列表

public function createNotification(array $data): Notification
// 參數: $data - 通知資料
// 回傳: 通知物件
// 用途: 建立新通知

public function markAsRead(int $notificationId, User $user): bool
// 參數: $notificationId - 通知 ID, $user - 使用者物件
// 回傳: 操作是否成功
// 用途: 標記通知為已讀

public function markAllAsRead(User $user): int
// 參數: $user - 使用者物件
// 回傳: 標記的通知數量
// 用途: 標記所有通知為已讀

public function deleteNotification(int $notificationId, User $user): bool
// 參數: $notificationId - 通知 ID, $user - 使用者物件
// 回傳: 操作是否成功
// 用途: 刪除通知

public function getUnreadCount(User $user): int
// 參數: $user - 使用者物件
// 回傳: 未讀通知數量
// 用途: 取得未讀通知數量

public function sendBrowserNotification(User $user, array $data): void
// 參數: $user - 使用者物件, $data - 通知資料
// 用途: 發送瀏覽器通知

public function cleanupOldNotifications(int $daysToKeep = 30): int
// 參數: $daysToKeep - 保留天數
// 回傳: 清理的通知數量
// 用途: 清理舊通知
```

### SearchService

**檔案位置**: `app/Services/SearchService.php`

```php
public function globalSearch(string $query, User $user): array
// 參數: $query - 搜尋查詢, $user - 使用者物件
// 回傳: 搜尋結果陣列
// 用途: 執行全域搜尋

public function searchInModule(string $module, string $query, User $user): Collection
// 參數: $module - 模組名稱, $query - 搜尋查詢, $user - 使用者物件
// 回傳: 搜尋結果集合
// 用途: 在特定模組中搜尋

public function getSearchSuggestions(string $query): array
// 參數: $query - 搜尋查詢
// 回傳: 搜尋建議陣列
// 用途: 取得搜尋建議

public function logSearchQuery(string $query, User $user): void
// 參數: $query - 搜尋查詢, $user - 使用者物件
// 用途: 記錄搜尋查詢

public function getPopularSearches(): array
// 回傳: 熱門搜尋陣列
// 用途: 取得熱門搜尋關鍵字

public function buildSearchIndex(): void
// 用途: 建立搜尋索引

public function updateSearchIndex(string $model, int $id): void
// 參數: $model - 模型名稱, $id - 項目 ID
// 用途: 更新搜尋索引
```

## 事件系統

### 可監聽的事件

```php
// 佈局相關事件
'sidebar-toggled'           // 側邊選單切換
'mobile-sidebar-toggled'    // 手機版選單切換
'theme-changed'             // 主題變更
'locale-changed'            // 語言變更

// 通知相關事件
'notification-received'     // 接收新通知
'notification-read'         // 通知已讀
'notification-deleted'      // 通知刪除

// 搜尋相關事件
'search-performed'          // 執行搜尋
'search-result-selected'    // 選擇搜尋結果

// 使用者相關事件
'user-logged-out'          // 使用者登出
'session-expired'          // Session 過期
```

### 事件資料格式

```php
// theme-changed 事件
[
    'theme' => 'dark',
    'previousTheme' => 'light',
    'timestamp' => '2025-01-08T10:30:00Z'
]

// notification-received 事件
[
    'id' => 123,
    'type' => 'security',
    'title' => '異常登入嘗試',
    'message' => '檢測到來自未知 IP 的登入嘗試',
    'priority' => 'high',
    'timestamp' => '2025-01-08T10:30:00Z'
]

// search-performed 事件
[
    'query' => '使用者管理',
    'results_count' => 15,
    'categories' => ['users', 'roles'],
    'timestamp' => '2025-01-08T10:30:00Z'
]
```

## 錯誤處理

### 常見錯誤代碼

```php
// 權限相關錯誤
'PERMISSION_DENIED'         // 權限不足
'UNAUTHORIZED_ACCESS'       // 未授權存取

// 資料相關錯誤
'INVALID_THEME'            // 無效主題
'INVALID_LOCALE'           // 無效語言
'NOTIFICATION_NOT_FOUND'   // 通知不存在

// 系統相關錯誤
'SESSION_EXPIRED'          // Session 過期
'CACHE_ERROR'              // 快取錯誤
'SEARCH_INDEX_ERROR'       // 搜尋索引錯誤
```

### 錯誤處理範例

```php
try {
    $this->setTheme($theme);
} catch (InvalidThemeException $e) {
    $this->addError('theme', '無效的主題設定');
    Log::warning('Invalid theme attempted', [
        'theme' => $theme,
        'user_id' => auth()->id()
    ]);
}
```

這個 API 參考文檔提供了所有元件和服務的詳細介面說明，開發者可以根據這些資訊正確使用和擴展系統功能。