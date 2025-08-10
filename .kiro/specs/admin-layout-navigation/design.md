# 管理後台佈局和導航系統設計文件

## 概述

管理後台佈局和導航系統是整個管理系統的基礎架構，採用現代化的響應式設計、模組化元件架構和使用者體驗優先的設計理念，為所有管理功能提供統一且直觀的操作介面。

## 架構設計

### 整體架構

```
┌─────────────────────────────────────────────────────────────┐
│                    AdminLayout Component                    │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────────┐  ┌─────────────────────────────────────┐ │
│  │   TopNavBar     │  │           MainContent               │ │
│  │   Component     │  │           Component                 │ │
│  └─────────────────┘  └─────────────────────────────────────┘ │
│  ┌─────────────────┐  ┌─────────────────────────────────────┐ │
│  │   Sidebar       │  │         Page Content                │ │
│  │   Component     │  │         (Dynamic)                   │ │
│  │                 │  │                                     │ │
│  └─────────────────┘  └─────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### 元件層級架構

```
AdminLayout
├── TopNavBar
│   ├── MenuToggle
│   ├── Breadcrumb
│   ├── GlobalSearch
│   ├── NotificationCenter
│   ├── ThemeToggle
│   ├── LanguageSelector
│   └── UserMenu
├── Sidebar
│   ├── NavigationMenu
│   ├── MenuSearch
│   └── MenuCollapse
├── MainContent
│   ├── PageHeader
│   ├── ContentArea
│   └── PageFooter
└── GlobalComponents
    ├── LoadingOverlay
    ├── ToastNotifications
    └── ModalContainer
```

## 元件設計

### 1. AdminLayout 主佈局元件

**檔案位置**: `app/Livewire/Admin/Layout/AdminLayout.php`

```php
class AdminLayout extends Component
{
    // 佈局狀態
    public bool $sidebarCollapsed = false;
    public bool $sidebarMobile = false;
    public string $currentTheme = 'light';
    public string $currentLocale = 'zh_TW';
    
    // 頁面資訊
    public string $pageTitle = '';
    public array $breadcrumbs = [];
    public array $pageActions = [];
    
    // 計算屬性
    public function getLayoutClassesProperty(): string
    public function getIsMobileProperty(): bool
    public function getCurrentUserProperty(): ?User
    
    // 佈局操作
    public function toggleSidebar(): void
    public function toggleMobileSidebar(): void
    public function setTheme(string $theme): void
    public function setLocale(string $locale): void
    
    // 頁面管理
    public function setPageTitle(string $title): void
    public function setBreadcrumbs(array $breadcrumbs): void
    public function addPageAction(array $action): void
    
    // 事件監聽
    #[On('theme-changed')]
    public function handleThemeChange(string $theme): void
    
    #[On('locale-changed')]
    public function handleLocaleChange(string $locale): void
}
```

### 2. TopNavBar 頂部導航元件

**檔案位置**: `app/Livewire/Admin/Layout/TopNavBar.php`

```php
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
    
    // 計算屬性
    public function getSearchResultsProperty(): Collection
    public function getNotificationsProperty(): Collection
    public function getCurrentUserProperty(): User
    
    // 搜尋操作
    public function updatedGlobalSearch(): void
    public function selectSearchResult(string $type, int $id): void
    public function clearSearch(): void
    
    // 通知操作
    public function toggleNotifications(): void
    public function markAsRead(int $notificationId): void
    public function markAllAsRead(): void
    
    // 使用者操作
    public function logout(): void
    public function goToProfile(): void
    
    // 即時更新
    #[On('notification-received')]
    public function handleNewNotification(array $notification): void
}
```

### 3. Sidebar 側邊選單元件

**檔案位置**: `app/Livewire/Admin/Layout/Sidebar.php`

```php
class Sidebar extends Component
{
    // 選單狀態
    public bool $collapsed = false;
    public array $expandedMenus = [];
    public string $activeMenu = '';
    public string $menuSearch = '';
    
    // 計算屬性
    public function getMenuItemsProperty(): Collection
    public function getFilteredMenusProperty(): Collection
    public function getCurrentRouteProperty(): string
    
    // 選單操作
    public function toggleMenu(string $menuKey): void
    public function setActiveMenu(string $menuKey): void
    public function toggleCollapse(): void
    
    // 搜尋功能
    public function updatedMenuSearch(): void
    public function clearMenuSearch(): void
    
    // 權限檢查
    protected function hasMenuPermission(array $menu): bool
    protected function filterMenuByPermissions(Collection $menus): Collection
}
```

### 4. NotificationCenter 通知中心元件

**檔案位置**: `app/Livewire/Admin/Layout/NotificationCenter.php`

```php
class NotificationCenter extends Component
{
    // 通知狀態
    public bool $isOpen = false;
    public string $filter = 'all'; // all, unread, security
    public int $perPage = 10;
    
    // 計算屬性
    public function getNotificationsProperty(): LengthAwarePaginator
    public function getUnreadCountProperty(): int
    public function getNotificationTypesProperty(): array
    
    // 通知操作
    public function toggle(): void
    public function markAsRead(int $notificationId): void
    public function markAllAsRead(): void
    public function deleteNotification(int $notificationId): void
    public function setFilter(string $filter): void
    
    // 即時通知
    #[On('notification-received')]
    public function addNotification(array $notification): void
    
    public function showBrowserNotification(array $notification): void
}
```

### 5. GlobalSearch 全域搜尋元件

**檔案位置**: `app/Livewire/Admin/Layout/GlobalSearch.php`

```php
class GlobalSearch extends Component
{
    // 搜尋狀態
    public string $query = '';
    public array $results = [];
    public bool $isOpen = false;
    public string $selectedCategory = 'all';
    
    // 搜尋配置
    protected array $searchableModels = [
        'users' => User::class,
        'roles' => Role::class,
        'permissions' => Permission::class,
        'activities' => Activity::class,
    ];
    
    // 計算屬性
    public function getSearchResultsProperty(): array
    public function getCategoriesProperty(): array
    
    // 搜尋操作
    public function updatedQuery(): void
    public function search(): void
    public function selectResult(string $type, int $id): void
    public function clearSearch(): void
    
    // 鍵盤操作
    public function handleKeydown(string $key): void
    
    // 搜尋邏輯
    protected function searchInModel(string $model, string $query): Collection
    protected function formatResults(Collection $results, string $type): array
}
```

### 6. ThemeToggle 主題切換元件

**檔案位置**: `app/Livewire/Admin/Layout/ThemeToggle.php`

```php
class ThemeToggle extends Component
{
    // 主題狀態
    public string $currentTheme = 'light';
    public array $availableThemes = ['light', 'dark', 'auto'];
    
    // 計算屬性
    public function getThemeIconProperty(): string
    public function getThemeNameProperty(): string
    
    // 主題操作
    public function setTheme(string $theme): void
    public function toggleTheme(): void
    public function detectSystemTheme(): string
    
    // 主題應用
    protected function applyTheme(string $theme): void
    protected function saveThemePreference(string $theme): void
    
    // 事件處理
    #[On('system-theme-changed')]
    public function handleSystemThemeChange(string $theme): void
}
```

## 資料存取層設計

### NavigationService

```php
class NavigationService
{
    public function getMenuStructure(): array
    public function filterMenuByPermissions(array $menu, User $user): array
    public function getCurrentBreadcrumbs(string $route): array
    public function getQuickActions(User $user): array
    public function buildMenuTree(array $items, ?int $parentId = null): array
    public function getMenuPermissions(): array
    public function cacheMenuStructure(User $user): void
    public function clearMenuCache(): void
}
```

### NotificationService

```php
class NotificationService
{
    public function getUserNotifications(User $user, array $filters = []): LengthAwarePaginator
    public function createNotification(array $data): Notification
    public function markAsRead(int $notificationId, User $user): bool
    public function markAllAsRead(User $user): int
    public function deleteNotification(int $notificationId, User $user): bool
    public function getUnreadCount(User $user): int
    public function sendBrowserNotification(User $user, array $data): void
    public function cleanupOldNotifications(int $daysToKeep = 30): int
}
```

### SearchService

```php
class SearchService
{
    public function globalSearch(string $query, User $user): array
    public function searchInModule(string $module, string $query, User $user): Collection
    public function getSearchSuggestions(string $query): array
    public function logSearchQuery(string $query, User $user): void
    public function getPopularSearches(): array
    public function buildSearchIndex(): void
    public function updateSearchIndex(string $model, int $id): void
}
```

## 使用者介面設計

### 桌面版佈局 (≥1024px)

```
┌─────────────────────────────────────────────────────────────┐
│ [☰] 首頁 > 使用者管理 > 使用者列表    [🔍] [🔔] [🌙] [👤]  │
├─────────────────────────────────────────────────────────────┤
│ ┌─────────────┐ ┌─────────────────────────────────────────┐ │
│ │ 📊 儀表板    │ │                                         │ │
│ │ 👥 使用者管理 │ │                                         │ │
│ │   • 使用者列表│ │            主要內容區域                  │ │
│ │   • 建立使用者│ │                                         │ │
│ │ 🛡️ 角色管理  │ │                                         │ │
│ │   • 角色列表 │ │                                         │ │
│ │   • 權限設定 │ │                                         │ │
│ │ ⚙️ 系統設定  │ │                                         │ │
│ │ 📋 活動記錄  │ │                                         │ │
│ └─────────────┘ └─────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### 平板版佈局 (768px-1023px)

```
┌─────────────────────────────────────────────────────────────┐
│ [☰] 首頁 > 使用者管理        [🔍] [🔔] [🌙] [👤]           │
├─────────────────────────────────────────────────────────────┤
│ ┌───┐ ┌─────────────────────────────────────────────────────┐ │
│ │📊 │ │                                                     │ │
│ │👥 │ │                                                     │ │
│ │🛡️ │ │              主要內容區域                            │ │
│ │⚙️ │ │                                                     │ │
│ │📋 │ │                                                     │ │
│ └───┘ └─────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### 手機版佈局 (<768px)

```
┌─────────────────────────────────────────────────────────────┐
│ [☰] 使用者列表                              [🔔] [👤]       │
├─────────────────────────────────────────────────────────────┤
│                                                            │
│                                                            │
│                    主要內容區域                             │
│                                                            │
│                                                            │
└─────────────────────────────────────────────────────────────┘

側邊選單 (抽屜模式):
┌─────────────────┐
│ 📊 儀表板        │
│ 👥 使用者管理     │
│   • 使用者列表   │
│   • 建立使用者   │
│ 🛡️ 角色管理     │
│ ⚙️ 系統設定     │
│ 📋 活動記錄     │
└─────────────────┘
```

### 通知中心介面

```
┌─────────────────────────────────────────────────────────────┐
│ 通知中心                                    [全部標記已讀]   │
├─────────────────────────────────────────────────────────────┤
│ [全部] [未讀] [安全事件]                                     │
├─────────────────────────────────────────────────────────────┤
│ 🔴 系統安全警報                                  2分鐘前     │
│    檢測到異常登入嘗試，來源 IP: 192.168.1.100               │
│                                                            │
│ 🟡 使用者操作通知                               15分鐘前     │
│    管理員 John 建立了新角色 "編輯者"                         │
│                                                            │
│ 🟢 系統更新通知                                 1小時前      │
│    系統已成功更新到版本 2.1.0                               │
│                                                            │
│ 📊 統計報告                                     3小時前      │
│    每日使用者活動報告已生成                                  │
├─────────────────────────────────────────────────────────────┤
│                      [查看全部通知]                         │
└─────────────────────────────────────────────────────────────┘
```

### 全域搜尋介面

```
┌─────────────────────────────────────────────────────────────┐
│ 🔍 搜尋任何內容...                                          │
├─────────────────────────────────────────────────────────────┤
│ 頁面和功能                                                  │
│ 📄 使用者管理 > 使用者列表                                   │
│ 📄 角色管理 > 權限設定                                       │
│                                                            │
│ 使用者 (3)                                                 │
│ 👤 John Doe (john@example.com)                            │
│ 👤 Jane Smith (jane@example.com)                          │
│ 👤 Bob Wilson (bob@example.com)                           │
│                                                            │
│ 角色 (2)                                                   │
│ 🛡️ 管理員 (15 個權限)                                       │
│ 🛡️ 編輯者 (8 個權限)                                        │
│                                                            │
│ 最近搜尋: 使用者列表, 權限設定, 活動記錄                      │
└─────────────────────────────────────────────────────────────┘
```

## 主題系統設計

### CSS 變數架構

```css
:root {
  /* 亮色主題 */
  --color-primary: #3B82F6;
  --color-primary-dark: #2563EB;
  --color-secondary: #6B7280;
  --color-success: #10B981;
  --color-warning: #F59E0B;
  --color-danger: #EF4444;
  
  --bg-primary: #FFFFFF;
  --bg-secondary: #F9FAFB;
  --bg-tertiary: #F3F4F6;
  
  --text-primary: #111827;
  --text-secondary: #6B7280;
  --text-tertiary: #9CA3AF;
  
  --border-primary: #E5E7EB;
  --border-secondary: #D1D5DB;
  
  --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
  --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
}

[data-theme="dark"] {
  /* 暗色主題 */
  --color-primary: #60A5FA;
  --color-primary-dark: #3B82F6;
  
  --bg-primary: #111827;
  --bg-secondary: #1F2937;
  --bg-tertiary: #374151;
  
  --text-primary: #F9FAFB;
  --text-secondary: #D1D5DB;
  --text-tertiary: #9CA3AF;
  
  --border-primary: #374151;
  --border-secondary: #4B5563;
}
```

### 主題切換動畫

```css
* {
  transition: background-color 0.3s ease, 
              color 0.3s ease, 
              border-color 0.3s ease;
}

.theme-transition {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
```

## 響應式設計

### 斷點定義

```css
/* 手機 */
@media (max-width: 767px) {
  .sidebar {
    transform: translateX(-100%);
    position: fixed;
    z-index: 50;
  }
  
  .sidebar.open {
    transform: translateX(0);
  }
  
  .main-content {
    margin-left: 0;
  }
}

/* 平板 */
@media (min-width: 768px) and (max-width: 1023px) {
  .sidebar {
    width: 64px;
  }
  
  .sidebar.expanded {
    width: 280px;
  }
  
  .main-content {
    margin-left: 64px;
  }
}

/* 桌面 */
@media (min-width: 1024px) {
  .sidebar {
    width: 280px;
    position: relative;
  }
  
  .sidebar.collapsed {
    width: 64px;
  }
  
  .main-content {
    margin-left: 280px;
  }
}
```

## 效能優化

### 元件懶載入

```php
// 延遲載入非關鍵元件
class AdminLayout extends Component
{
    public function render()
    {
        return view('livewire.admin.layout.admin-layout', [
            'sidebar' => $this->loadSidebar(),
            'notifications' => lazy(fn() => $this->loadNotifications()),
            'searchResults' => lazy(fn() => $this->loadSearchResults()),
        ]);
    }
}
```

### 選單快取策略

```php
class NavigationService
{
    public function getMenuStructure(User $user): array
    {
        return Cache::remember(
            "menu_structure_{$user->id}_{$user->roles->pluck('id')->implode('_')}",
            3600,
            fn() => $this->buildMenuStructure($user)
        );
    }
    
    public function clearUserMenuCache(User $user): void
    {
        Cache::forget("menu_structure_{$user->id}_*");
    }
}
```

### 前端資源優化

```javascript
// 關鍵 CSS 內聯
// 非關鍵 CSS 延遲載入
// JavaScript 模組化載入

// Service Worker 快取策略
self.addEventListener('fetch', event => {
  if (event.request.url.includes('/admin/assets/')) {
    event.respondWith(
      caches.match(event.request)
        .then(response => response || fetch(event.request))
    );
  }
});
```

## 安全性設計

### Session 管理

```php
class SessionSecurityService
{
    public function checkSessionSecurity(User $user): array
    {
        return [
            'is_expired' => $this->isSessionExpired(),
            'needs_refresh' => $this->needsRefresh(),
            'suspicious_activity' => $this->detectSuspiciousActivity($user),
            'concurrent_sessions' => $this->getConcurrentSessions($user),
        ];
    }
    
    public function refreshSession(): void
    {
        session()->regenerate();
        $this->updateLastActivity();
    }
    
    public function terminateOtherSessions(User $user): void
    {
        // 終止使用者的其他 Session
    }
}
```

### CSRF 保護

```php
// 所有 Livewire 元件自動包含 CSRF 保護
// 額外的 API 端點需要手動驗證

class AdminMiddleware
{
    public function handle($request, Closure $next)
    {
        // 檢查管理員權限
        // 驗證 Session 安全性
        // 記錄存取日誌
        
        return $next($request);
    }
}
```

這個設計文件涵蓋了管理後台佈局和導航系統的所有技術細節，為後續的實作提供了完整的指導。