# 開發者指南

## 概述

本指南為開發者提供管理後台佈局和導航系統的深入技術資訊，包括架構設計、擴展方法、最佳實踐和故障排除。

## 系統架構

### 技術堆疊

- **後端框架**: Laravel 10.x
- **前端框架**: Livewire 3.x
- **CSS 框架**: Tailwind CSS 3.x
- **JavaScript 框架**: Alpine.js 3.x
- **圖示庫**: Heroicons
- **字型**: Inter

### 目錄結構

```
app/
├── Livewire/Admin/Layout/          # Livewire 佈局元件
│   ├── AdminLayout.php
│   ├── TopNavBar.php
│   ├── Sidebar.php
│   ├── NotificationCenter.php
│   ├── GlobalSearch.php
│   └── ThemeToggle.php
├── Services/                       # 服務層
│   ├── NavigationService.php
│   ├── NotificationService.php
│   └── SearchService.php
├── Models/                         # 資料模型
│   ├── Notification.php
│   └── UserPreference.php
└── Http/Middleware/                # 中介軟體
    ├── AdminLayoutMiddleware.php
    └── SessionSecurityMiddleware.php

resources/
├── views/livewire/admin/layout/    # Livewire 視圖
│   ├── admin-layout.blade.php
│   ├── top-nav-bar.blade.php
│   ├── sidebar.blade.php
│   ├── notification-center.blade.php
│   ├── global-search.blade.php
│   └── theme-toggle.blade.php
├── css/                           # 樣式檔案
│   ├── admin-layout.css
│   ├── themes/
│   │   ├── light.css
│   │   └── dark.css
│   └── components/
│       ├── sidebar.css
│       ├── topnav.css
│       └── notifications.css
├── js/                            # JavaScript 檔案
│   ├── admin-layout.js
│   ├── theme-controller.js
│   ├── keyboard-shortcuts.js
│   └── responsive-handler.js
└── lang/                          # 多語言檔案
    ├── zh_TW/admin-layout.php
    └── en/admin-layout.php

config/
├── admin.php                      # 管理後台配置
├── themes.php                     # 主題配置
└── navigation.php                 # 導航配置

database/
├── migrations/                    # 資料庫遷移
│   ├── create_notifications_table.php
│   └── create_user_preferences_table.php
└── seeders/                       # 資料填充
    └── AdminNavigationSeeder.php
```

## 核心概念

### 1. 元件化設計

系統採用高度模組化的元件設計，每個元件負責特定功能：

```php
// 元件基礎結構
abstract class BaseLayoutComponent extends Component
{
    // 共用屬性
    protected User $user;
    protected array $permissions;
    
    // 初始化方法
    public function mount(): void
    {
        $this->user = auth()->user();
        $this->permissions = $this->user->getAllPermissions()->pluck('name')->toArray();
    }
    
    // 權限檢查
    protected function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions);
    }
    
    // 事件處理
    protected function dispatchLayoutEvent(string $event, array $data = []): void
    {
        $this->dispatch($event, $data);
    }
}
```

### 2. 服務導向架構

使用服務層分離業務邏輯：

```php
// 服務註冊 (AppServiceProvider.php)
public function register(): void
{
    $this->app->singleton(NavigationService::class);
    $this->app->singleton(NotificationService::class);
    $this->app->singleton(SearchService::class);
}

// 服務注入使用
class AdminLayout extends Component
{
    public function __construct(
        protected NavigationService $navigationService,
        protected NotificationService $notificationService
    ) {}
}
```

### 3. 事件驅動通訊

元件間使用事件系統進行通訊：

```php
// 事件觸發
$this->dispatch('sidebar-toggled', [
    'collapsed' => $this->sidebarCollapsed,
    'timestamp' => now()
]);

// 事件監聽
#[On('sidebar-toggled')]
public function handleSidebarToggle(array $data): void
{
    // 處理側邊選單狀態變更
}
```

## 擴展開發

### 1. 建立自訂佈局元件

```php
// app/Livewire/Admin/Layout/CustomLayout.php
namespace App\Livewire\Admin\Layout;

class CustomLayout extends AdminLayout
{
    // 新增自訂屬性
    public bool $showRightPanel = false;
    public array $customWidgets = [];
    
    // 覆寫初始化
    public function mount(): void
    {
        parent::mount();
        $this->loadCustomWidgets();
    }
    
    // 自訂方法
    public function toggleRightPanel(): void
    {
        $this->showRightPanel = !$this->showRightPanel;
        $this->dispatch('right-panel-toggled', $this->showRightPanel);
    }
    
    // 載入自訂小工具
    protected function loadCustomWidgets(): void
    {
        $this->customWidgets = config('admin.custom_widgets', []);
    }
    
    // 覆寫渲染
    public function render()
    {
        return view('livewire.admin.layout.custom-layout')
            ->extends('layouts.admin')
            ->section('content');
    }
}
```

### 2. 建立自訂導航項目

```php
// config/navigation.php
return [
    'menu_items' => [
        'dashboard' => [
            'label' => '儀表板',
            'icon' => 'home',
            'route' => 'admin.dashboard',
            'permission' => 'admin.dashboard.view',
            'order' => 1,
        ],
        'custom_module' => [
            'label' => '自訂模組',
            'icon' => 'cog',
            'route' => 'admin.custom',
            'permission' => 'admin.custom.view',
            'order' => 10,
            'children' => [
                'custom_list' => [
                    'label' => '項目列表',
                    'route' => 'admin.custom.index',
                    'permission' => 'admin.custom.list',
                ],
                'custom_create' => [
                    'label' => '建立項目',
                    'route' => 'admin.custom.create',
                    'permission' => 'admin.custom.create',
                ],
            ],
        ],
    ],
];

// NavigationService 擴展
class NavigationService
{
    public function addMenuItem(string $key, array $config): void
    {
        $menuItems = config('navigation.menu_items', []);
        $menuItems[$key] = $config;
        
        config(['navigation.menu_items' => $menuItems]);
        $this->clearMenuCache();
    }
    
    public function removeMenuItem(string $key): void
    {
        $menuItems = config('navigation.menu_items', []);
        unset($menuItems[$key]);
        
        config(['navigation.menu_items' => $menuItems]);
        $this->clearMenuCache();
    }
}
```

### 3. 建立自訂通知類型

```php
// app/Notifications/CustomAdminNotification.php
namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;

class CustomAdminNotification extends Notification
{
    public function __construct(
        protected string $title,
        protected string $message,
        protected string $type = 'info',
        protected array $data = []
    ) {}
    
    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }
    
    public function toDatabase($notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'data' => $this->data,
            'created_at' => now(),
        ];
    }
    
    public function toBroadcast($notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
        ];
    }
}

// 使用範例
$user->notify(new CustomAdminNotification(
    '系統更新',
    '系統已成功更新到版本 2.1.0',
    'success',
    ['version' => '2.1.0']
));
```

### 4. 擴展搜尋功能

```php
// app/Services/SearchService.php 擴展
class SearchService
{
    protected array $searchProviders = [];
    
    public function registerSearchProvider(string $name, SearchProviderInterface $provider): void
    {
        $this->searchProviders[$name] = $provider;
    }
    
    public function globalSearch(string $query, User $user): array
    {
        $results = [];
        
        foreach ($this->searchProviders as $name => $provider) {
            if ($provider->canSearch($user)) {
                $providerResults = $provider->search($query, $user);
                $results[$name] = $this->formatResults($providerResults, $name);
            }
        }
        
        return $results;
    }
}

// 搜尋提供者介面
interface SearchProviderInterface
{
    public function canSearch(User $user): bool;
    public function search(string $query, User $user): Collection;
    public function getDisplayName(): string;
    public function getIcon(): string;
}

// 自訂搜尋提供者
class CustomModuleSearchProvider implements SearchProviderInterface
{
    public function canSearch(User $user): bool
    {
        return $user->can('admin.custom.search');
    }
    
    public function search(string $query, User $user): Collection
    {
        return CustomModel::where('name', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->limit(10)
            ->get();
    }
    
    public function getDisplayName(): string
    {
        return '自訂模組';
    }
    
    public function getIcon(): string
    {
        return 'cog';
    }
}
```

## 主題開發

### 1. 建立自訂主題

```css
/* resources/css/themes/corporate.css */
[data-theme="corporate"] {
  /* 企業主題色彩 */
  --color-primary: #1E40AF;
  --color-primary-dark: #1E3A8A;
  --color-primary-light: #3B82F6;
  
  --color-secondary: #64748B;
  --color-accent: #F59E0B;
  
  /* 企業風格背景 */
  --bg-primary: #FFFFFF;
  --bg-secondary: #F8FAFC;
  --bg-tertiary: #E2E8F0;
  --bg-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  
  /* 企業風格字型 */
  --font-family-primary: 'Roboto', sans-serif;
  --font-family-heading: 'Roboto Slab', serif;
  
  /* 企業風格圓角 */
  --radius-sm: 2px;
  --radius-md: 4px;
  --radius-lg: 6px;
  --radius-xl: 8px;
  
  /* 企業風格陰影 */
  --shadow-corporate: 0 2px 4px rgba(0, 0, 0, 0.1);
  --shadow-corporate-lg: 0 8px 16px rgba(0, 0, 0, 0.15);
}

/* 企業主題特殊樣式 */
[data-theme="corporate"] .sidebar {
  background: var(--bg-gradient);
  color: white;
}

[data-theme="corporate"] .sidebar .menu-item {
  border-radius: var(--radius-sm);
  margin-bottom: 2px;
}

[data-theme="corporate"] .card {
  box-shadow: var(--shadow-corporate);
  border: none;
}

[data-theme="corporate"] .btn-primary {
  background: var(--color-primary);
  box-shadow: var(--shadow-corporate);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
```

### 2. 註冊自訂主題

```php
// config/themes.php
return [
    'available_themes' => [
        'light' => [
            'name' => '亮色主題',
            'icon' => 'sun',
            'css_file' => null,
        ],
        'dark' => [
            'name' => '暗色主題',
            'icon' => 'moon',
            'css_file' => null,
        ],
        'corporate' => [
            'name' => '企業主題',
            'icon' => 'building-office',
            'css_file' => 'themes/corporate.css',
            'preview_image' => 'images/themes/corporate-preview.png',
        ],
    ],
    
    'theme_settings' => [
        'corporate' => [
            'sidebar_style' => 'gradient',
            'header_style' => 'minimal',
            'card_style' => 'elevated',
        ],
    ],
];
```

## 效能優化

### 1. 快取策略

```php
// app/Services/CacheService.php
class CacheService
{
    protected const CACHE_TTL = 3600; // 1 小時
    
    public function cacheUserMenu(User $user): void
    {
        $cacheKey = "user_menu_{$user->id}_{$user->updated_at->timestamp}";
        
        Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            return $this->buildUserMenu($user);
        });
    }
    
    public function cacheUserNotifications(User $user): void
    {
        $cacheKey = "user_notifications_{$user->id}";
        
        Cache::remember($cacheKey, 300, function () use ($user) { // 5 分鐘
            return $user->notifications()
                ->where('read_at', null)
                ->latest()
                ->limit(10)
                ->get();
        });
    }
    
    public function invalidateUserCache(User $user): void
    {
        Cache::forget("user_menu_{$user->id}_*");
        Cache::forget("user_notifications_{$user->id}");
        Cache::forget("user_permissions_{$user->id}");
    }
}
```

### 2. 懶載入實作

```php
// AdminLayout.php
class AdminLayout extends Component
{
    public bool $loadNotifications = false;
    public bool $loadSearchResults = false;
    
    public function loadNotifications(): void
    {
        if (!$this->loadNotifications) {
            $this->loadNotifications = true;
            $this->dispatch('load-notifications');
        }
    }
    
    public function loadSearchResults(): void
    {
        if (!$this->loadSearchResults) {
            $this->loadSearchResults = true;
            $this->dispatch('load-search-results');
        }
    }
}
```

```blade
{{-- admin-layout.blade.php --}}
<div class="admin-layout">
    <livewire:admin.layout.top-nav-bar />
    <livewire:admin.layout.sidebar />
    
    {{-- 懶載入通知中心 --}}
    @if($loadNotifications)
        <livewire:admin.layout.notification-center />
    @endif
    
    {{-- 懶載入搜尋結果 --}}
    @if($loadSearchResults)
        <livewire:admin.layout.global-search />
    @endif
    
    <main class="main-content">
        {{ $slot }}
    </main>
</div>
```

### 3. 前端優化

```javascript
// resources/js/admin-layout.js
class AdminLayoutOptimizer {
    constructor() {
        this.debounceTimers = new Map();
        this.intersectionObserver = null;
        this.init();
    }
    
    init() {
        this.setupIntersectionObserver();
        this.setupDebouncing();
        this.setupVirtualScrolling();
    }
    
    // 防抖處理
    debounce(key, callback, delay = 300) {
        if (this.debounceTimers.has(key)) {
            clearTimeout(this.debounceTimers.get(key));
        }
        
        const timer = setTimeout(callback, delay);
        this.debounceTimers.set(key, timer);
    }
    
    // 交集觀察器用於懶載入
    setupIntersectionObserver() {
        this.intersectionObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const element = entry.target;
                    const loadAction = element.dataset.loadAction;
                    
                    if (loadAction) {
                        Livewire.find(element.closest('[wire\\:id]').getAttribute('wire:id'))
                            .call(loadAction);
                        
                        this.intersectionObserver.unobserve(element);
                    }
                }
            });
        });
    }
    
    // 虛擬滾動
    setupVirtualScrolling() {
        const longLists = document.querySelectorAll('[data-virtual-scroll]');
        
        longLists.forEach(list => {
            new VirtualScrollList(list, {
                itemHeight: 48,
                buffer: 5,
                renderItem: (item, index) => this.renderListItem(item, index)
            });
        });
    }
}

// 初始化優化器
document.addEventListener('DOMContentLoaded', () => {
    window.adminLayoutOptimizer = new AdminLayoutOptimizer();
});
```

## 測試策略

### 1. 單元測試

```php
// tests/Unit/Services/NavigationServiceTest.php
class NavigationServiceTest extends TestCase
{
    protected NavigationService $navigationService;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->navigationService = app(NavigationService::class);
    }
    
    /** @test */
    public function it_can_build_menu_structure()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('admin.dashboard.view');
        
        $menu = $this->navigationService->getMenuStructure($user);
        
        $this->assertIsArray($menu);
        $this->assertArrayHasKey('dashboard', $menu);
    }
    
    /** @test */
    public function it_filters_menu_by_permissions()
    {
        $user = User::factory()->create();
        // 不給予任何權限
        
        $menu = $this->navigationService->getMenuStructure($user);
        
        $this->assertEmpty($menu);
    }
    
    /** @test */
    public function it_caches_menu_structure()
    {
        $user = User::factory()->create();
        
        // 第一次呼叫
        $menu1 = $this->navigationService->getMenuStructure($user);
        
        // 第二次呼叫應該從快取取得
        $menu2 = $this->navigationService->getMenuStructure($user);
        
        $this->assertEquals($menu1, $menu2);
    }
}
```

### 2. 功能測試

```php
// tests/Feature/AdminLayoutTest.php
class AdminLayoutTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function admin_layout_renders_correctly()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('admin.dashboard.view');
        
        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertStatus(200)
            ->assertSeeLivewire('admin.layout.admin-layout')
            ->assertSeeLivewire('admin.layout.sidebar')
            ->assertSeeLivewire('admin.layout.top-nav-bar');
    }
    
    /** @test */
    public function sidebar_can_be_toggled()
    {
        $user = User::factory()->create();
        
        $this->actingAs($user)
            ->livewire(AdminLayout::class)
            ->call('toggleSidebar')
            ->assertSet('sidebarCollapsed', true)
            ->assertDispatched('sidebar-toggled');
    }
    
    /** @test */
    public function theme_can_be_changed()
    {
        $user = User::factory()->create();
        
        $this->actingAs($user)
            ->livewire(ThemeToggle::class)
            ->call('setTheme', 'dark')
            ->assertSet('currentTheme', 'dark')
            ->assertDispatched('theme-changed');
            
        $this->assertEquals('dark', $user->fresh()->theme_preference);
    }
}
```

### 3. 瀏覽器測試

```php
// tests/Browser/AdminLayoutTest.php
class AdminLayoutTest extends DuskTestCase
{
    /** @test */
    public function user_can_navigate_using_sidebar()
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['admin.dashboard.view', 'admin.users.view']);
        
        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/admin')
                ->assertSee('儀表板')
                ->click('@sidebar-users-menu')
                ->waitForText('使用者管理')
                ->assertPathIs('/admin/users');
        });
    }
    
    /** @test */
    public function responsive_layout_works_correctly()
    {
        $user = User::factory()->create();
        
        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/admin')
                ->resize(768, 1024) // 平板尺寸
                ->assertVisible('@sidebar-collapse-btn')
                ->resize(375, 667) // 手機尺寸
                ->assertVisible('@mobile-menu-btn')
                ->click('@mobile-menu-btn')
                ->waitFor('@mobile-sidebar')
                ->assertVisible('@mobile-sidebar');
        });
    }
}
```

## 故障排除

### 1. 常見問題診斷

```php
// app/Console/Commands/DiagnoseAdminLayout.php
class DiagnoseAdminLayout extends Command
{
    protected $signature = 'admin:diagnose';
    protected $description = '診斷管理後台佈局系統';
    
    public function handle()
    {
        $this->info('開始診斷管理後台佈局系統...');
        
        // 檢查權限設定
        $this->checkPermissions();
        
        // 檢查快取狀態
        $this->checkCache();
        
        // 檢查資料庫連接
        $this->checkDatabase();
        
        // 檢查檔案權限
        $this->checkFilePermissions();
        
        $this->info('診斷完成！');
    }
    
    protected function checkPermissions()
    {
        $this->info('檢查權限設定...');
        
        $permissions = Permission::where('name', 'like', 'admin.%')->count();
        $this->line("找到 {$permissions} 個管理權限");
        
        if ($permissions === 0) {
            $this->error('未找到管理權限，請執行權限填充');
        }
    }
    
    protected function checkCache()
    {
        $this->info('檢查快取狀態...');
        
        try {
            Cache::put('admin_test', 'test', 60);
            $value = Cache::get('admin_test');
            
            if ($value === 'test') {
                $this->line('快取系統正常');
            } else {
                $this->error('快取系統異常');
            }
        } catch (Exception $e) {
            $this->error("快取錯誤: {$e->getMessage()}");
        }
    }
    
    protected function checkDatabase()
    {
        $this->info('檢查資料庫連接...');
        
        try {
            DB::connection()->getPdo();
            $this->line('資料庫連接正常');
            
            // 檢查必要資料表
            $tables = ['users', 'permissions', 'roles', 'notifications'];
            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    $this->line("資料表 {$table} 存在");
                } else {
                    $this->error("資料表 {$table} 不存在");
                }
            }
        } catch (Exception $e) {
            $this->error("資料庫連接錯誤: {$e->getMessage()}");
        }
    }
    
    protected function checkFilePermissions()
    {
        $this->info('檢查檔案權限...');
        
        $paths = [
            storage_path('logs'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
        ];
        
        foreach ($paths as $path) {
            if (is_writable($path)) {
                $this->line("路徑 {$path} 可寫入");
            } else {
                $this->error("路徑 {$path} 無法寫入");
            }
        }
    }
}
```

### 2. 除錯工具

```javascript
// resources/js/debug-tools.js
class AdminLayoutDebugger {
    constructor() {
        this.enabled = window.APP_DEBUG || false;
        this.logs = [];
        
        if (this.enabled) {
            this.init();
        }
    }
    
    init() {
        this.createDebugPanel();
        this.interceptLivewireEvents();
        this.monitorPerformance();
    }
    
    createDebugPanel() {
        const panel = document.createElement('div');
        panel.id = 'admin-debug-panel';
        panel.innerHTML = `
            <div class="debug-header">
                <h3>Admin Layout Debug</h3>
                <button onclick="this.parentElement.parentElement.style.display='none'">×</button>
            </div>
            <div class="debug-content">
                <div class="debug-section">
                    <h4>Livewire Events</h4>
                    <div id="livewire-events"></div>
                </div>
                <div class="debug-section">
                    <h4>Performance</h4>
                    <div id="performance-metrics"></div>
                </div>
                <div class="debug-section">
                    <h4>Cache Status</h4>
                    <div id="cache-status"></div>
                </div>
            </div>
        `;
        
        document.body.appendChild(panel);
    }
    
    interceptLivewireEvents() {
        document.addEventListener('livewire:init', () => {
            Livewire.hook('message.sent', (message, component) => {
                this.log('Livewire Event', {
                    type: 'sent',
                    component: component.name,
                    message: message
                });
            });
            
            Livewire.hook('message.received', (message, component) => {
                this.log('Livewire Event', {
                    type: 'received',
                    component: component.name,
                    message: message
                });
            });
        });
    }
    
    monitorPerformance() {
        // 監控頁面載入時間
        window.addEventListener('load', () => {
            const loadTime = performance.now();
            this.log('Performance', {
                type: 'page_load',
                time: `${loadTime.toFixed(2)}ms`
            });
        });
        
        // 監控 Livewire 請求時間
        document.addEventListener('livewire:init', () => {
            let requestStart;
            
            Livewire.hook('message.sent', () => {
                requestStart = performance.now();
            });
            
            Livewire.hook('message.received', () => {
                const requestTime = performance.now() - requestStart;
                this.log('Performance', {
                    type: 'livewire_request',
                    time: `${requestTime.toFixed(2)}ms`
                });
            });
        });
    }
    
    log(category, data) {
        const logEntry = {
            timestamp: new Date().toISOString(),
            category,
            data
        };
        
        this.logs.push(logEntry);
        console.log(`[Admin Debug] ${category}:`, data);
        
        // 更新除錯面板
        this.updateDebugPanel(category, logEntry);
    }
    
    updateDebugPanel(category, logEntry) {
        const targetElement = document.getElementById(
            category.toLowerCase().replace(' ', '-') + 's'
        );
        
        if (targetElement) {
            const logElement = document.createElement('div');
            logElement.className = 'debug-log-entry';
            logElement.innerHTML = `
                <span class="timestamp">${logEntry.timestamp}</span>
                <span class="data">${JSON.stringify(logEntry.data)}</span>
            `;
            
            targetElement.appendChild(logElement);
            
            // 保持最新的 10 條記錄
            while (targetElement.children.length > 10) {
                targetElement.removeChild(targetElement.firstChild);
            }
        }
    }
    
    exportLogs() {
        const blob = new Blob([JSON.stringify(this.logs, null, 2)], {
            type: 'application/json'
        });
        
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `admin-debug-${Date.now()}.json`;
        a.click();
        
        URL.revokeObjectURL(url);
    }
}

// 初始化除錯工具
if (window.APP_DEBUG) {
    window.adminDebugger = new AdminLayoutDebugger();
}
```

## 部署指南

### 1. 生產環境優化

```bash
# 清除和優化快取
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 優化 Composer 自動載入
composer install --optimize-autoloader --no-dev

# 編譯前端資源
npm run build

# 設定檔案權限
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 2. 監控設定

```php
// config/logging.php
'channels' => [
    'admin_layout' => [
        'driver' => 'daily',
        'path' => storage_path('logs/admin-layout.log'),
        'level' => 'info',
        'days' => 14,
    ],
],

// 在元件中使用
Log::channel('admin_layout')->info('User accessed admin dashboard', [
    'user_id' => auth()->id(),
    'ip' => request()->ip(),
    'user_agent' => request()->userAgent(),
]);
```

這個開發者指南提供了完整的技術資訊，幫助開發者理解、擴展和維護管理後台佈局和導航系統。