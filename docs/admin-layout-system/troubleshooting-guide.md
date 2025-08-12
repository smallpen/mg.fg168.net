# 故障排除指南

## 概述

本指南提供管理後台佈局和導航系統常見問題的診斷和解決方案，幫助開發者和使用者快速解決遇到的問題。

## 常見問題分類

### 🔐 登入和認證問題

#### 問題：無法登入管理後台

**症狀**：
- 輸入正確帳號密碼後仍無法登入
- 登入後立即被重定向到登入頁面
- 顯示「權限不足」錯誤

**可能原因**：
1. 使用者帳號未啟用
2. 缺少管理後台存取權限
3. Session 配置問題
4. 中介軟體配置錯誤

**解決方案**：

```bash
# 1. 檢查使用者狀態
docker-compose exec app php artisan tinker
>>> $user = User::where('email', 'admin@example.com')->first();
>>> $user->is_active; // 應該為 true
>>> $user->hasPermissionTo('admin.access'); // 應該為 true
```

```php
// 2. 檢查中介軟體配置 (routes/admin.php)
Route::middleware(['auth', 'admin'])->group(function () {
    // 管理後台路由
});

// 3. 檢查 AdminMiddleware
class AdminMiddleware
{
    public function handle($request, Closure $next)
    {
        if (!auth()->user()->hasPermissionTo('admin.access')) {
            abort(403, '權限不足');
        }
        
        return $next($request);
    }
}
```

```bash
# 4. 清除 Session 和快取
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan session:flush
```

#### 問題：Session 頻繁過期

**症狀**：
- 使用過程中頻繁要求重新登入
- 操作時出現「Session 已過期」訊息

**可能原因**：
1. Session 生命週期設定過短
2. Session 驅動配置問題
3. 跨域 Cookie 問題

**解決方案**：

```php
// config/session.php
'lifetime' => env('SESSION_LIFETIME', 120), // 增加到 120 分鐘
'expire_on_close' => false,
'encrypt' => true,
'files' => storage_path('framework/sessions'),
'connection' => null,
'table' => 'sessions',
'store' => null,
'lottery' => [2, 100],
'cookie' => env('SESSION_COOKIE', 'laravel_session'),
'path' => '/',
'domain' => env('SESSION_DOMAIN', null),
'secure' => env('SESSION_SECURE_COOKIE', false),
'http_only' => true,
'same_site' => 'lax',
```

### 🎨 佈局和顯示問題

#### 問題：側邊選單不顯示或顯示異常

**症狀**：
- 側邊選單完全不顯示
- 選單項目缺失
- 選單樣式錯亂

**可能原因**：
1. 權限配置問題
2. 選單快取問題
3. CSS 載入失敗
4. JavaScript 錯誤

**解決方案**：

```bash
# 1. 檢查權限設定
docker-compose exec app php artisan permission:show
```

```php
// 2. 清除選單快取
$navigationService = app(\App\Services\NavigationService::class);
$navigationService->clearMenuCache();
```

```bash
# 3. 檢查前端資源
docker-compose exec app npm run build
```

```javascript
// 4. 檢查瀏覽器控制台錯誤
// 開啟開發者工具 (F12) 查看 Console 和 Network 標籤
```

#### 問題：響應式佈局在手機上異常

**症狀**：
- 手機版選單無法開啟
- 佈局元素重疊
- 觸控操作無響應

**可能原因**：
1. CSS 媒體查詢問題
2. JavaScript 事件監聽器失效
3. 視窗大小檢測錯誤

**解決方案**：

```css
/* 檢查 CSS 媒體查詢 */
@media (max-width: 767px) {
    .sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }
    
    .sidebar.open {
        transform: translateX(0);
    }
}
```

```javascript
// 檢查 JavaScript 事件監聽
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuBtn = document.querySelector('[data-mobile-menu-toggle]');
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function() {
            // 切換選單邏輯
        });
    }
});
```

### 🔍 搜尋功能問題

#### 問題：全域搜尋無結果或結果不準確

**症狀**：
- 搜尋已知存在的項目無結果
- 搜尋結果不相關
- 搜尋速度過慢

**可能原因**：
1. 搜尋索引未建立或過期
2. 權限過濾過於嚴格
3. 搜尋查詢邏輯錯誤

**解決方案**：

```bash
# 1. 重建搜尋索引
docker-compose exec app php artisan search:rebuild
```

```php
// 2. 檢查搜尋權限
class GlobalSearch extends Component
{
    protected function searchInModel(string $model, string $query): Collection
    {
        $modelClass = $this->searchableModels[$model] ?? null;
        
        if (!$modelClass || !$this->canSearchModel($model)) {
            return collect();
        }
        
        return $modelClass::search($query)->take(10)->get();
    }
    
    protected function canSearchModel(string $model): bool
    {
        $permissions = [
            'users' => 'admin.users.view',
            'roles' => 'admin.roles.view',
            'permissions' => 'admin.permissions.view',
        ];
        
        return auth()->user()->can($permissions[$model] ?? 'admin.access');
    }
}
```

```bash
# 3. 檢查搜尋效能
docker-compose exec app php artisan debugbar:clear
# 啟用 Laravel Debugbar 查看查詢效能
```

### 🔔 通知系統問題

#### 問題：通知不顯示或無法接收

**症狀**：
- 通知中心顯示空白
- 新通知不出現
- 瀏覽器通知無效

**可能原因**：
1. 通知權限未授權
2. WebSocket 連接問題
3. 瀏覽器通知被阻擋

**解決方案**：

```javascript
// 1. 檢查瀏覽器通知權限
if ('Notification' in window) {
    if (Notification.permission === 'default') {
        Notification.requestPermission().then(permission => {
            console.log('通知權限:', permission);
        });
    }
}
```

```php
// 2. 檢查通知建立
$notification = auth()->user()->notifications()->create([
    'type' => 'test',
    'data' => [
        'title' => '測試通知',
        'message' => '這是一個測試通知'
    ]
]);

// 檢查是否成功建立
dd($notification);
```

```bash
# 3. 檢查佇列工作者
docker-compose exec app php artisan queue:work --verbose
```

### 🎨 主題系統問題

#### 問題：主題切換無效或樣式錯亂

**症狀**：
- 點擊主題切換按鈕無反應
- 主題切換後樣式不正確
- 主題偏好無法儲存

**可能原因**：
1. CSS 變數不支援
2. JavaScript 事件處理錯誤
3. 主題檔案載入失敗

**解決方案**：

```javascript
// 1. 檢查 CSS 變數支援
if (window.CSS && CSS.supports('color', 'var(--primary-color)')) {
    console.log('支援 CSS 變數');
} else {
    console.log('不支援 CSS 變數，需要 polyfill');
}
```

```css
/* 2. 檢查主題 CSS 變數定義 */
:root {
    --color-primary: #3B82F6;
    --bg-primary: #FFFFFF;
}

[data-theme="dark"] {
    --color-primary: #60A5FA;
    --bg-primary: #111827;
}

/* 確保所有元素都使用變數 */
.btn-primary {
    background-color: var(--color-primary);
    color: var(--text-inverse);
}
```

```php
// 3. 檢查主題偏好儲存
class ThemeToggle extends Component
{
    public function setTheme(string $theme): void
    {
        // 驗證主題是否有效
        if (!in_array($theme, $this->availableThemes)) {
            $this->addError('theme', '無效的主題設定');
            return;
        }
        
        $this->currentTheme = $theme;
        
        // 儲存到資料庫
        auth()->user()->update(['theme_preference' => $theme]);
        
        // 儲存到 Session
        session(['theme' => $theme]);
        
        $this->dispatch('theme-changed', $theme);
    }
}
```

## 診斷工具

### 系統診斷命令

建立自訂 Artisan 命令進行系統診斷：

```php
// app/Console/Commands/DiagnoseAdminSystem.php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DiagnoseAdminSystem extends Command
{
    protected $signature = 'admin:diagnose {--fix : 嘗試自動修復問題}';
    protected $description = '診斷管理後台系統問題';

    public function handle()
    {
        $this->info('🔍 開始診斷管理後台系統...');
        
        $issues = [];
        
        // 檢查資料庫連接
        $issues = array_merge($issues, $this->checkDatabase());
        
        // 檢查權限系統
        $issues = array_merge($issues, $this->checkPermissions());
        
        // 檢查快取系統
        $issues = array_merge($issues, $this->checkCache());
        
        // 檢查檔案權限
        $issues = array_merge($issues, $this->checkFilePermissions());
        
        // 檢查前端資源
        $issues = array_merge($issues, $this->checkAssets());
        
        if (empty($issues)) {
            $this->info('✅ 系統檢查完成，未發現問題');
        } else {
            $this->error('❌ 發現以下問題：');
            foreach ($issues as $issue) {
                $this->line("  - {$issue}");
            }
            
            if ($this->option('fix')) {
                $this->info('🔧 嘗試自動修復...');
                $this->autoFix($issues);
            }
        }
    }
    
    protected function checkDatabase(): array
    {
        $issues = [];
        
        try {
            DB::connection()->getPdo();
            $this->line('✅ 資料庫連接正常');
        } catch (\Exception $e) {
            $issues[] = "資料庫連接失敗: {$e->getMessage()}";
        }
        
        // 檢查必要資料表
        $requiredTables = ['users', 'roles', 'permissions', 'notifications'];
        foreach ($requiredTables as $table) {
            if (!Schema::hasTable($table)) {
                $issues[] = "缺少資料表: {$table}";
            }
        }
        
        return $issues;
    }
    
    protected function checkPermissions(): array
    {
        $issues = [];
        
        // 檢查是否有管理權限
        $adminPermissions = DB::table('permissions')
            ->where('name', 'like', 'admin.%')
            ->count();
            
        if ($adminPermissions === 0) {
            $issues[] = '缺少管理權限，請執行 php artisan db:seed --class=PermissionSeeder';
        } else {
            $this->line("✅ 找到 {$adminPermissions} 個管理權限");
        }
        
        return $issues;
    }
    
    protected function checkCache(): array
    {
        $issues = [];
        
        try {
            Cache::put('admin_test', 'test', 60);
            $value = Cache::get('admin_test');
            
            if ($value === 'test') {
                $this->line('✅ 快取系統正常');
                Cache::forget('admin_test');
            } else {
                $issues[] = '快取系統異常';
            }
        } catch (\Exception $e) {
            $issues[] = "快取錯誤: {$e->getMessage()}";
        }
        
        return $issues;
    }
    
    protected function checkFilePermissions(): array
    {
        $issues = [];
        
        $paths = [
            storage_path('logs'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
        ];
        
        foreach ($paths as $path) {
            if (!is_writable($path)) {
                $issues[] = "路徑無法寫入: {$path}";
            }
        }
        
        if (empty($issues)) {
            $this->line('✅ 檔案權限正常');
        }
        
        return $issues;
    }
    
    protected function checkAssets(): array
    {
        $issues = [];
        
        $assetPaths = [
            public_path('build/assets'),
            public_path('css'),
            public_path('js'),
        ];
        
        foreach ($assetPaths as $path) {
            if (!file_exists($path)) {
                $issues[] = "前端資源路徑不存在: {$path}";
            }
        }
        
        return $issues;
    }
    
    protected function autoFix(array $issues): void
    {
        foreach ($issues as $issue) {
            if (str_contains($issue, '檔案權限')) {
                $this->call('admin:fix-permissions');
            } elseif (str_contains($issue, '快取')) {
                $this->call('cache:clear');
            } elseif (str_contains($issue, '前端資源')) {
                $this->call('admin:build-assets');
            }
        }
    }
}
```

### 瀏覽器除錯工具

建立前端除錯工具：

```javascript
// resources/js/debug-tools.js
class AdminDebugTools {
    constructor() {
        this.enabled = window.APP_DEBUG || false;
        this.panel = null;
        
        if (this.enabled) {
            this.init();
        }
    }
    
    init() {
        this.createDebugPanel();
        this.addKeyboardShortcuts();
        this.monitorLivewireEvents();
        this.monitorPerformance();
    }
    
    createDebugPanel() {
        this.panel = document.createElement('div');
        this.panel.id = 'admin-debug-panel';
        this.panel.innerHTML = `
            <div class="debug-header">
                <h3>Admin Debug Tools</h3>
                <div class="debug-controls">
                    <button onclick="adminDebug.clearCache()">清除快取</button>
                    <button onclick="adminDebug.exportLogs()">匯出日誌</button>
                    <button onclick="adminDebug.runDiagnostics()">執行診斷</button>
                    <button onclick="adminDebug.toggle()">隱藏</button>
                </div>
            </div>
            <div class="debug-content">
                <div class="debug-section">
                    <h4>系統資訊</h4>
                    <div id="system-info"></div>
                </div>
                <div class="debug-section">
                    <h4>Livewire 事件</h4>
                    <div id="livewire-events"></div>
                </div>
                <div class="debug-section">
                    <h4>效能監控</h4>
                    <div id="performance-metrics"></div>
                </div>
                <div class="debug-section">
                    <h4>錯誤日誌</h4>
                    <div id="error-logs"></div>
                </div>
            </div>
        `;
        
        document.body.appendChild(this.panel);
        this.updateSystemInfo();
    }
    
    addKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl + Shift + D 開啟除錯面板
            if (e.ctrlKey && e.shiftKey && e.key === 'D') {
                this.toggle();
            }
            
            // Ctrl + Shift + C 清除快取
            if (e.ctrlKey && e.shiftKey && e.key === 'C') {
                this.clearCache();
            }
        });
    }
    
    monitorLivewireEvents() {
        document.addEventListener('livewire:init', () => {
            Livewire.hook('message.sent', (message, component) => {
                this.logEvent('Livewire 請求', {
                    component: component.name,
                    method: message.method,
                    params: message.params
                });
            });
            
            Livewire.hook('message.received', (message, component) => {
                this.logEvent('Livewire 回應', {
                    component: component.name,
                    updates: message.response.effects
                });
            });
        });
    }
    
    monitorPerformance() {
        // 監控頁面載入時間
        window.addEventListener('load', () => {
            const loadTime = performance.now();
            this.logMetric('頁面載入時間', `${loadTime.toFixed(2)}ms`);
        });
        
        // 監控記憶體使用
        if ('memory' in performance) {
            setInterval(() => {
                const memory = performance.memory;
                this.logMetric('記憶體使用', {
                    used: `${(memory.usedJSHeapSize / 1024 / 1024).toFixed(2)}MB`,
                    total: `${(memory.totalJSHeapSize / 1024 / 1024).toFixed(2)}MB`
                });
            }, 5000);
        }
    }
    
    updateSystemInfo() {
        const info = {
            'User Agent': navigator.userAgent,
            'Screen Size': `${screen.width}x${screen.height}`,
            'Viewport Size': `${window.innerWidth}x${window.innerHeight}`,
            'Color Depth': `${screen.colorDepth} bits`,
            'Language': navigator.language,
            'Online': navigator.onLine ? '是' : '否',
            'Cookies Enabled': navigator.cookieEnabled ? '是' : '否'
        };
        
        const infoElement = document.getElementById('system-info');
        infoElement.innerHTML = Object.entries(info)
            .map(([key, value]) => `<div><strong>${key}:</strong> ${value}</div>`)
            .join('');
    }
    
    logEvent(type, data) {
        const eventsElement = document.getElementById('livewire-events');
        const eventElement = document.createElement('div');
        eventElement.className = 'debug-event';
        eventElement.innerHTML = `
            <span class="timestamp">${new Date().toLocaleTimeString()}</span>
            <span class="type">${type}</span>
            <span class="data">${JSON.stringify(data)}</span>
        `;
        
        eventsElement.insertBefore(eventElement, eventsElement.firstChild);
        
        // 保持最新的 20 條記錄
        while (eventsElement.children.length > 20) {
            eventsElement.removeChild(eventsElement.lastChild);
        }
    }
    
    logMetric(name, value) {
        const metricsElement = document.getElementById('performance-metrics');
        const metricElement = document.createElement('div');
        metricElement.innerHTML = `<strong>${name}:</strong> ${JSON.stringify(value)}`;
        
        // 更新或新增指標
        const existing = Array.from(metricsElement.children)
            .find(el => el.textContent.startsWith(name));
            
        if (existing) {
            existing.innerHTML = metricElement.innerHTML;
        } else {
            metricsElement.appendChild(metricElement);
        }
    }
    
    clearCache() {
        // 清除 localStorage
        localStorage.clear();
        
        // 清除 sessionStorage
        sessionStorage.clear();
        
        // 觸發 Livewire 快取清除
        if (window.Livewire) {
            Livewire.dispatch('clear-cache');
        }
        
        this.logEvent('快取清除', '所有快取已清除');
        alert('快取已清除');
    }
    
    exportLogs() {
        const logs = {
            timestamp: new Date().toISOString(),
            events: Array.from(document.querySelectorAll('#livewire-events .debug-event'))
                .map(el => el.textContent),
            metrics: Array.from(document.querySelectorAll('#performance-metrics div'))
                .map(el => el.textContent),
            errors: Array.from(document.querySelectorAll('#error-logs div'))
                .map(el => el.textContent)
        };
        
        const blob = new Blob([JSON.stringify(logs, null, 2)], {
            type: 'application/json'
        });
        
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `admin-debug-${Date.now()}.json`;
        a.click();
        
        URL.revokeObjectURL(url);
    }
    
    runDiagnostics() {
        const diagnostics = {
            livewire: !!window.Livewire,
            alpine: !!window.Alpine,
            jquery: !!window.$,
            bootstrap: !!window.bootstrap,
            tailwind: !!document.querySelector('[class*="tw-"]'),
            localStorage: this.testLocalStorage(),
            sessionStorage: this.testSessionStorage(),
            cookies: navigator.cookieEnabled,
            webgl: this.testWebGL(),
            serviceWorker: 'serviceWorker' in navigator
        };
        
        console.table(diagnostics);
        alert('診斷結果已輸出到控制台');
    }
    
    testLocalStorage() {
        try {
            localStorage.setItem('test', 'test');
            localStorage.removeItem('test');
            return true;
        } catch (e) {
            return false;
        }
    }
    
    testSessionStorage() {
        try {
            sessionStorage.setItem('test', 'test');
            sessionStorage.removeItem('test');
            return true;
        } catch (e) {
            return false;
        }
    }
    
    testWebGL() {
        try {
            const canvas = document.createElement('canvas');
            return !!(canvas.getContext('webgl') || canvas.getContext('experimental-webgl'));
        } catch (e) {
            return false;
        }
    }
    
    toggle() {
        if (this.panel) {
            this.panel.style.display = this.panel.style.display === 'none' ? 'block' : 'none';
        }
    }
}

// 初始化除錯工具
if (window.APP_DEBUG) {
    window.adminDebug = new AdminDebugTools();
}
```

## 效能問題診斷

### 慢查詢檢測

```php
// config/database.php
'connections' => [
    'mysql' => [
        // ... 其他配置
        'options' => [
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET sql_mode="STRICT_TRANS_TABLES"',
        ],
        'dump' => [
            'dump_binary_path' => '/usr/bin',
        ],
        'slow_query_log' => true,
        'long_query_time' => 2, // 記錄超過 2 秒的查詢
    ],
],
```

### 記憶體使用監控

```php
// app/Http/Middleware/MemoryMonitorMiddleware.php
class MemoryMonitorMiddleware
{
    public function handle($request, Closure $next)
    {
        $startMemory = memory_get_usage(true);
        $startTime = microtime(true);
        
        $response = $next($request);
        
        $endMemory = memory_get_usage(true);
        $endTime = microtime(true);
        
        $memoryUsed = $endMemory - $startMemory;
        $timeUsed = ($endTime - $startTime) * 1000;
        
        if ($memoryUsed > 50 * 1024 * 1024) { // 超過 50MB
            Log::warning('High memory usage detected', [
                'route' => $request->route()->getName(),
                'memory_used' => $memoryUsed,
                'time_used' => $timeUsed,
                'user_id' => auth()->id()
            ]);
        }
        
        return $response;
    }
}
```

## 聯絡支援

如果以上解決方案無法解決您的問題，請聯絡技術支援：

1. **收集問題資訊**：
   - 錯誤訊息截圖
   - 瀏覽器控制台錯誤
   - 操作步驟重現
   - 系統環境資訊

2. **執行診斷命令**：
   ```bash
   docker-compose exec app php artisan admin:diagnose
   ```

3. **匯出除錯日誌**：
   - 使用瀏覽器除錯工具匯出日誌
   - 提供 Laravel 日誌檔案

4. **提供系統資訊**：
   - Laravel 版本
   - PHP 版本
   - 瀏覽器版本
   - 作業系統版本

透過這些診斷工具和解決方案，大部分問題都能得到快速解決。