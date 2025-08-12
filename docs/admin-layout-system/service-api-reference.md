# 服務層 API 參考文檔

## 概述

本文檔詳細說明管理後台佈局和導航系統中所有服務類別的 API 介面、方法參數和回傳值。

## NavigationService 導航服務

**檔案位置**: `app/Services/NavigationService.php`

### 類別概述

NavigationService 負責管理系統導航選單的建構、權限過濾和快取機制。

```php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class NavigationService
{
    protected array $menuStructure;
    protected array $permissions;
    
    public function __construct()
    {
        $this->menuStructure = config('navigation.menu_items', []);
    }
}
```

### 公開方法

#### getMenuStructure()

取得完整的選單結構陣列。

```php
public function getMenuStructure(User $user = null): array
```

**參數**:
- `$user` (User|null): 使用者物件，用於權限過濾。如為 null 則使用當前認證使用者。

**回傳值**: 
- `array`: 完整的選單結構陣列

**範例**:
```php
$navigationService = app(NavigationService::class);
$menu = $navigationService->getMenuStructure(auth()->user());

// 回傳格式
[
    'dashboard' => [
        'label' => '儀表板',
        'icon' => 'home',
        'route' => 'admin.dashboard',
        'permission' => 'admin.dashboard.view',
        'order' => 1,
        'active' => false,
        'children' => []
    ],
    // ... 其他選單項目
]
```

#### filterMenuByPermissions()

根據使用者權限過濾選單項目。

```php
public function filterMenuByPermissions(array $menu, User $user): array
```

**參數**:
- `$menu` (array): 原始選單陣列
- `$user` (User): 使用者物件

**回傳值**: 
- `array`: 過濾後的選單陣列

**範例**:
```php
$originalMenu = config('navigation.menu_items');
$filteredMenu = $navigationService->filterMenuByPermissions($originalMenu, $user);
```

#### getCurrentBreadcrumbs()

根據當前路由生成麵包屑導航。

```php
public function getCurrentBreadcrumbs(string $route = null): array
```

**參數**:
- `$route` (string|null): 路由名稱。如為 null 則使用當前路由。

**回傳值**: 
- `array`: 麵包屑陣列

**範例**:
```php
$breadcrumbs = $navigationService->getCurrentBreadcrumbs('admin.users.edit');

// 回傳格式
[
    ['label' => '首頁', 'url' => route('admin.dashboard')],
    ['label' => '使用者管理', 'url' => route('admin.users.index')],
    ['label' => '編輯使用者', 'url' => null]
]
```

#### getQuickActions()

取得使用者可用的快速操作列表。

```php
public function getQuickActions(User $user): array
```

**參數**:
- `$user` (User): 使用者物件

**回傳值**: 
- `array`: 快速操作陣列

**範例**:
```php
$quickActions = $navigationService->getQuickActions($user);

// 回傳格式
[
    [
        'label' => '建立使用者',
        'icon' => 'user-plus',
        'route' => 'admin.users.create',
        'permission' => 'admin.users.create',
        'type' => 'primary'
    ],
    // ... 其他快速操作
]
```

#### buildMenuTree()

建立階層式選單樹狀結構。

```php
public function buildMenuTree(array $items, ?int $parentId = null): array
```

**參數**:
- `$items` (array): 選單項目陣列
- `$parentId` (int|null): 父項目 ID

**回傳值**: 
- `array`: 樹狀結構選單陣列

#### cacheMenuStructure()

快取使用者的選單結構。

```php
public function cacheMenuStructure(User $user): void
```

**參數**:
- `$user` (User): 使用者物件

**用途**: 將使用者的選單結構快取到 Redis 或檔案快取中，提高後續存取效能。

#### clearMenuCache()

清除選單快取。

```php
public function clearMenuCache(User $user = null): void
```

**參數**:
- `$user` (User|null): 特定使用者。如為 null 則清除所有選單快取。

**用途**: 當選單結構或使用者權限變更時，清除相關快取。

## NotificationService 通知服務

**檔案位置**: `app/Services/NotificationService.php`

### 類別概述

NotificationService 負責管理系統通知的建立、讀取、刪除和推送功能。

```php
namespace App\Services;

use App\Models\User;
use App\Models\Notification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class NotificationService
{
    protected array $notificationTypes = [
        'info', 'success', 'warning', 'error', 'security'
    ];
}
```

### 公開方法

#### getUserNotifications()

取得使用者的通知列表（分頁）。

```php
public function getUserNotifications(User $user, array $filters = []): LengthAwarePaginator
```

**參數**:
- `$user` (User): 使用者物件
- `$filters` (array): 過濾條件

**過濾條件**:
```php
[
    'type' => 'security',        // 通知類型
    'read' => false,             // 是否已讀
    'priority' => 'high',        // 優先級
    'date_from' => '2025-01-01', // 開始日期
    'date_to' => '2025-01-31',   // 結束日期
    'per_page' => 15             // 每頁數量
]
```

**回傳值**: 
- `LengthAwarePaginator`: 分頁的通知集合

**範例**:
```php
$notifications = $notificationService->getUserNotifications($user, [
    'type' => 'security',
    'read' => false,
    'per_page' => 10
]);

foreach ($notifications as $notification) {
    echo $notification->title;
}
```

#### createNotification()

建立新通知。

```php
public function createNotification(array $data): Notification
```

**參數**:
- `$data` (array): 通知資料

**通知資料格式**:
```php
[
    'user_id' => 1,                    // 接收者 ID
    'type' => 'security',              // 通知類型
    'title' => '安全警報',             // 通知標題
    'message' => '檢測到異常登入',      // 通知內容
    'priority' => 'high',              // 優先級 (low, normal, high)
    'data' => [                        // 額外資料
        'ip' => '192.168.1.100',
        'user_agent' => 'Chrome/96.0'
    ],
    'action_url' => '/admin/security', // 操作連結
    'expires_at' => '2025-02-01'       // 過期時間
]
```

**回傳值**: 
- `Notification`: 建立的通知物件

**範例**:
```php
$notification = $notificationService->createNotification([
    'user_id' => $user->id,
    'type' => 'security',
    'title' => '異常登入嘗試',
    'message' => '檢測到來自未知 IP 的登入嘗試',
    'priority' => 'high',
    'data' => ['ip' => request()->ip()]
]);
```

#### markAsRead()

標記特定通知為已讀。

```php
public function markAsRead(int $notificationId, User $user): bool
```

**參數**:
- `$notificationId` (int): 通知 ID
- `$user` (User): 使用者物件

**回傳值**: 
- `bool`: 操作是否成功

**範例**:
```php
$success = $notificationService->markAsRead(123, $user);
if ($success) {
    echo '通知已標記為已讀';
}
```

#### markAllAsRead()

標記使用者的所有通知為已讀。

```php
public function markAllAsRead(User $user): int
```

**參數**:
- `$user` (User): 使用者物件

**回傳值**: 
- `int`: 標記的通知數量

**範例**:
```php
$count = $notificationService->markAllAsRead($user);
echo "已標記 {$count} 個通知為已讀";
```

#### deleteNotification()

刪除特定通知。

```php
public function deleteNotification(int $notificationId, User $user): bool
```

**參數**:
- `$notificationId` (int): 通知 ID
- `$user` (User): 使用者物件

**回傳值**: 
- `bool`: 操作是否成功

#### getUnreadCount()

取得使用者的未讀通知數量。

```php
public function getUnreadCount(User $user): int
```

**參數**:
- `$user` (User): 使用者物件

**回傳值**: 
- `int`: 未讀通知數量

**範例**:
```php
$unreadCount = $notificationService->getUnreadCount($user);
echo "您有 {$unreadCount} 個未讀通知";
```

#### sendBrowserNotification()

發送瀏覽器原生通知。

```php
public function sendBrowserNotification(User $user, array $data): void
```

**參數**:
- `$user` (User): 使用者物件
- `$data` (array): 通知資料

**通知資料格式**:
```php
[
    'title' => '新訊息',
    'body' => '您有一個新的系統通知',
    'icon' => '/images/notification-icon.png',
    'badge' => '/images/badge.png',
    'tag' => 'notification-123',
    'requireInteraction' => true
]
```

#### cleanupOldNotifications()

清理舊通知。

```php
public function cleanupOldNotifications(int $daysToKeep = 30): int
```

**參數**:
- `$daysToKeep` (int): 保留天數，預設 30 天

**回傳值**: 
- `int`: 清理的通知數量

## SearchService 搜尋服務

**檔案位置**: `app/Services/SearchService.php`

### 類別概述

SearchService 負責管理全域搜尋功能，包括多模組搜尋、搜尋建議和搜尋索引管理。

```php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

class SearchService
{
    protected array $searchableModels = [
        'users' => \App\Models\User::class,
        'roles' => \App\Models\Role::class,
        'permissions' => \App\Models\Permission::class,
        'activities' => \App\Models\Activity::class,
    ];
    
    protected array $searchProviders = [];
}
```

### 公開方法

#### globalSearch()

執行全域搜尋。

```php
public function globalSearch(string $query, User $user): array
```

**參數**:
- `$query` (string): 搜尋查詢字串
- `$user` (User): 使用者物件（用於權限檢查）

**回傳值**: 
- `array`: 分類的搜尋結果陣列

**回傳格式**:
```php
[
    'pages' => [
        [
            'title' => '使用者管理',
            'description' => '管理系統使用者',
            'url' => '/admin/users',
            'icon' => 'users',
            'type' => 'page'
        ]
    ],
    'users' => [
        [
            'id' => 1,
            'title' => 'John Doe',
            'description' => 'john@example.com',
            'url' => '/admin/users/1',
            'avatar' => '/images/avatars/1.jpg',
            'type' => 'user'
        ]
    ],
    'roles' => [...],
    'permissions' => [...],
    'activities' => [...]
]
```

**範例**:
```php
$results = $searchService->globalSearch('使用者管理', $user);

foreach ($results as $category => $items) {
    echo "分類: {$category}\n";
    foreach ($items as $item) {
        echo "- {$item['title']}\n";
    }
}
```

#### searchInModule()

在特定模組中執行搜尋。

```php
public function searchInModule(string $module, string $query, User $user): Collection
```

**參數**:
- `$module` (string): 模組名稱 ('users', 'roles', 'permissions', 'activities')
- `$query` (string): 搜尋查詢字串
- `$user` (User): 使用者物件

**回傳值**: 
- `Collection`: 搜尋結果集合

**範例**:
```php
$users = $searchService->searchInModule('users', 'john', $user);
$roles = $searchService->searchInModule('roles', '管理員', $user);
```

#### getSearchSuggestions()

取得搜尋建議。

```php
public function getSearchSuggestions(string $query): array
```

**參數**:
- `$query` (string): 部分搜尋查詢

**回傳值**: 
- `array`: 搜尋建議陣列

**範例**:
```php
$suggestions = $searchService->getSearchSuggestions('使用');

// 回傳格式
[
    '使用者管理',
    '使用者列表',
    '使用者角色',
    '使用者權限'
]
```

#### logSearchQuery()

記錄搜尋查詢。

```php
public function logSearchQuery(string $query, User $user): void
```

**參數**:
- `$query` (string): 搜尋查詢字串
- `$user` (User): 使用者物件

**用途**: 記錄搜尋查詢用於分析和改進搜尋功能。

#### getPopularSearches()

取得熱門搜尋關鍵字。

```php
public function getPopularSearches(int $limit = 10): array
```

**參數**:
- `$limit` (int): 回傳數量限制

**回傳值**: 
- `array`: 熱門搜尋陣列

**範例**:
```php
$popularSearches = $searchService->getPopularSearches(5);

// 回傳格式
[
    ['query' => '使用者管理', 'count' => 150],
    ['query' => '角色權限', 'count' => 120],
    ['query' => '系統設定', 'count' => 100],
    ['query' => '活動記錄', 'count' => 80],
    ['query' => '安全設定', 'count' => 60]
]
```

#### buildSearchIndex()

建立搜尋索引。

```php
public function buildSearchIndex(): void
```

**用途**: 建立或重建全域搜尋索引，提高搜尋效能。

**範例**:
```php
// 在 Artisan 命令中使用
$searchService->buildSearchIndex();
echo '搜尋索引建立完成';
```

#### updateSearchIndex()

更新特定項目的搜尋索引。

```php
public function updateSearchIndex(string $model, int $id): void
```

**參數**:
- `$model` (string): 模型名稱
- `$id` (int): 項目 ID

**用途**: 當資料更新時，同步更新搜尋索引。

**範例**:
```php
// 在模型觀察者中使用
public function updated(User $user)
{
    app(SearchService::class)->updateSearchIndex('users', $user->id);
}
```

## ThemeService 主題服務

**檔案位置**: `app/Services/ThemeService.php`

### 類別概述

ThemeService 負責管理主題系統，包括主題切換、使用者偏好儲存和主題資源管理。

```php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Storage;

class ThemeService
{
    protected array $availableThemes;
    protected string $defaultTheme;
    
    public function __construct()
    {
        $this->availableThemes = config('themes.available_themes', []);
        $this->defaultTheme = config('themes.default_theme', 'light');
    }
}
```

### 公開方法

#### getAvailableThemes()

取得可用的主題列表。

```php
public function getAvailableThemes(): array
```

**回傳值**: 
- `array`: 可用主題陣列

#### getUserTheme()

取得使用者的主題偏好。

```php
public function getUserTheme(User $user): string
```

**參數**:
- `$user` (User): 使用者物件

**回傳值**: 
- `string`: 主題名稱

#### setUserTheme()

設定使用者的主題偏好。

```php
public function setUserTheme(User $user, string $theme): bool
```

**參數**:
- `$user` (User): 使用者物件
- `$theme` (string): 主題名稱

**回傳值**: 
- `bool`: 操作是否成功

#### getThemeAssets()

取得主題相關的 CSS 和 JS 資源。

```php
public function getThemeAssets(string $theme): array
```

**參數**:
- `$theme` (string): 主題名稱

**回傳值**: 
- `array`: 主題資源陣列

**回傳格式**:
```php
[
    'css' => [
        '/css/themes/dark.css',
        '/css/themes/dark-components.css'
    ],
    'js' => [
        '/js/themes/dark-theme.js'
    ]
]
```

## 錯誤處理

### 異常類別

所有服務都使用自訂異常類別來處理錯誤：

```php
// app/Exceptions/NavigationException.php
class NavigationException extends Exception {}

// app/Exceptions/NotificationException.php
class NotificationException extends Exception {}

// app/Exceptions/SearchException.php
class SearchException extends Exception {}

// app/Exceptions/ThemeException.php
class ThemeException extends Exception {}
```

### 錯誤代碼

```php
// 導航服務錯誤
const NAVIGATION_MENU_NOT_FOUND = 'NAV_001';
const NAVIGATION_PERMISSION_DENIED = 'NAV_002';
const NAVIGATION_CACHE_ERROR = 'NAV_003';

// 通知服務錯誤
const NOTIFICATION_NOT_FOUND = 'NOT_001';
const NOTIFICATION_PERMISSION_DENIED = 'NOT_002';
const NOTIFICATION_SEND_FAILED = 'NOT_003';

// 搜尋服務錯誤
const SEARCH_INDEX_ERROR = 'SEA_001';
const SEARCH_PERMISSION_DENIED = 'SEA_002';
const SEARCH_QUERY_INVALID = 'SEA_003';

// 主題服務錯誤
const THEME_NOT_FOUND = 'THM_001';
const THEME_INVALID = 'THM_002';
const THEME_ASSETS_MISSING = 'THM_003';
```

### 使用範例

```php
try {
    $menu = $navigationService->getMenuStructure($user);
} catch (NavigationException $e) {
    Log::error('Navigation error: ' . $e->getMessage(), [
        'code' => $e->getCode(),
        'user_id' => $user->id
    ]);
    
    // 回傳預設選單或錯誤訊息
    return response()->json(['error' => '選單載入失敗'], 500);
}
```

這個服務層 API 參考文檔提供了所有服務類別的詳細介面說明，開發者可以根據這些資訊正確使用和擴展服務功能。