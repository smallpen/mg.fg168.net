# 管理後台佈局系統使用指南

## 概述

管理後台佈局系統是一個完整的響應式佈局解決方案，提供統一的使用者介面框架、導航體驗和互動模式。

## 主要功能

### 1. 響應式佈局架構

- **桌面版 (≥1024px)**: 三欄式佈局，側邊欄固定寬度 280px
- **平板版 (768px-1023px)**: 兩欄式佈局，側邊欄可收合
- **手機版 (<768px)**: 單欄式佈局，側邊欄以抽屜模式顯示

### 2. 佈局狀態管理

- 側邊欄收合/展開狀態
- 行動版選單開啟/關閉
- 主題切換（亮色/暗色/自動）
- 語言切換支援

### 3. 動畫和過渡效果

- 平滑的佈局切換動畫
- 側邊欄展開/收合動畫
- 主題切換過渡效果
- 載入狀態指示器

## 使用方式

### 基本使用

```blade
<!-- 在 Blade 模板中使用 -->
<livewire:admin.layout.admin-layout>
    <!-- 您的頁面內容 -->
    <div class="space-y-6">
        <h1>頁面標題</h1>
        <p>頁面內容</p>
    </div>
</livewire:admin.layout.admin-layout>
```

### 設定頁面資訊

```php
// 在控制器或 Livewire 元件中
public function mount()
{
    $this->dispatch('setPageTitle', '使用者管理');
    $this->dispatch('setBreadcrumbs', [
        ['label' => '首頁', 'url' => route('admin.dashboard')],
        ['label' => '使用者管理', 'url' => route('admin.users.index')],
        ['label' => '使用者列表', 'url' => null]
    ]);
}
```

### 新增頁面操作按鈕

```php
$this->dispatch('addPageAction', [
    'label' => '新增使用者',
    'url' => route('admin.users.create'),
    'icon' => 'plus',
    'class' => 'btn-primary'
]);
```

## 元件架構

### AdminLayout 主佈局元件

**檔案位置**: `app/Livewire/Admin/Layout/AdminLayout.php`

**主要屬性**:
- `sidebarCollapsed`: 側邊欄收合狀態
- `sidebarMobile`: 行動版側邊欄狀態
- `currentTheme`: 當前主題
- `currentLocale`: 當前語言
- `pageTitle`: 頁面標題
- `breadcrumbs`: 麵包屑導航
- `pageActions`: 頁面操作按鈕

**主要方法**:
- `toggleSidebar()`: 切換側邊欄
- `toggleMobileSidebar()`: 切換行動版側邊欄
- `setTheme(string $theme)`: 設定主題
- `setLocale(string $locale)`: 設定語言

### Sidebar 側邊選單元件

**檔案位置**: `app/Livewire/Admin/Layout/Sidebar.php`

負責顯示導航選單，支援：
- 多層級選單結構
- 權限控制
- 當前頁面高亮
- 選單搜尋功能

### TopBar 頂部導航元件

**檔案位置**: `app/Livewire/Admin/Layout/TopBar.php`

提供頂部導航功能：
- 選單切換按鈕
- 麵包屑導航
- 使用者選單
- 通知中心
- 全域搜尋

### ThemeToggle 主題切換元件

**檔案位置**: `app/Livewire/Admin/Layout/ThemeToggle.php`

支援主題切換：
- 亮色主題
- 暗色主題
- 自動模式（跟隨系統）

## CSS 架構

### 主要 CSS 檔案

**檔案位置**: `resources/css/admin-layout.css`

包含：
- CSS 變數定義
- 響應式佈局樣式
- 動畫和過渡效果
- 主題系統樣式
- 無障礙功能樣式

### CSS 變數

```css
:root {
  /* 佈局尺寸 */
  --sidebar-width-desktop: 18rem;
  --sidebar-width-tablet: 16rem;
  --sidebar-width-mobile: 20rem;
  --sidebar-width-collapsed: 4rem;
  --topbar-height: 4rem;
  
  /* 動畫時間 */
  --transition-fast: 0.15s;
  --transition-normal: 0.3s;
  --transition-slow: 0.5s;
  
  /* 動畫曲線 */
  --ease-in-out: cubic-bezier(0.4, 0, 0.2, 1);
  --ease-out: cubic-bezier(0, 0, 0.2, 1);
  --ease-in: cubic-bezier(0.4, 0, 1, 1);
}
```

### 響應式斷點

```css
/* 手機版 */
@media (max-width: 767px) { }

/* 平板版 */
@media (min-width: 768px) and (max-width: 1023px) { }

/* 桌面版 */
@media (min-width: 1024px) { }
```

## JavaScript 功能

### AdminLayoutManager 類別

**檔案位置**: `resources/js/admin-layout.js`

提供：
- 響應式設計檢測
- 鍵盤快捷鍵處理
- 主題切換邏輯
- 觸控手勢支援
- 無障礙功能

### 鍵盤快捷鍵

- `Ctrl/Cmd + B`: 切換側邊欄
- `Alt + T`: 切換主題
- `ESC`: 關閉選單和對話框
- `Ctrl/Cmd + K`: 開啟全域搜尋
- `F1`: 開啟說明

### 事件系統

```javascript
// 監聽視窗大小變更
window.addEventListener('admin:viewport-changed', (e) => {
    const { isMobile, isTablet } = e.detail;
    // 處理視窗變更
});

// 監聽主題變更
window.addEventListener('admin:theme-changed', (e) => {
    const { theme } = e.detail;
    // 處理主題變更
});
```

## 測試

### 功能測試

**檔案位置**: `tests/Feature/Livewire/Admin/Layout/AdminLayoutTest.php`

測試內容：
- 元件渲染
- 狀態管理
- 事件處理
- 響應式適應

### 單元測試

**檔案位置**: `tests/Unit/AdminLayoutUnitTest.php`

測試內容：
- CSS 類別生成
- 計算屬性
- 方法邏輯
- 狀態變更

### 執行測試

```bash
# 在 Docker 容器中執行
docker-compose exec app php artisan test tests/Feature/Livewire/Admin/Layout/AdminLayoutTest.php
docker-compose exec app php artisan test tests/Unit/AdminLayoutUnitTest.php
```

## 自訂和擴展

### 新增選單項目

在 `Sidebar.php` 中修改 `$menuItems` 陣列：

```php
protected array $menuItems = [
    [
        'name' => '新功能',
        'route' => 'admin.new-feature.index',
        'icon' => 'new-icon',
        'permission' => 'new-feature.view',
    ],
    // ...
];
```

### 自訂主題

修改 CSS 變數來自訂主題：

```css
:root {
  --color-primary: #your-color;
  --bg-primary: #your-bg-color;
  /* ... */
}
```

### 新增動畫效果

使用預定義的 CSS 類別：

```css
.your-element {
  transition: all var(--transition-normal) var(--ease-in-out);
}
```

## 無障礙功能

### 鍵盤導航

- 所有互動元素都支援鍵盤存取
- 提供跳轉到主內容的連結
- 焦點指示器清晰可見

### 螢幕閱讀器支援

- 適當的 ARIA 標籤
- 語義化 HTML 結構
- 狀態變更通知

### 高對比模式

- 支援系統高對比模式
- 增強的邊框和陰影
- 清晰的色彩對比

## 效能優化

### 懶載入

- 非關鍵元件延遲載入
- 圖片延遲載入支援

### 快取策略

- 選單結構快取
- 使用者偏好快取
- Service Worker 支援

### 資源優化

- CSS 和 JavaScript 壓縮
- 關鍵 CSS 內聯
- 非關鍵資源延遲載入

## 故障排除

### 常見問題

1. **側邊欄不顯示**
   - 檢查權限設定
   - 確認路由定義正確

2. **主題切換無效**
   - 檢查 JavaScript 載入
   - 確認 CSS 變數定義

3. **響應式佈局異常**
   - 檢查 CSS 媒體查詢
   - 確認 JavaScript 事件監聽

### 除錯工具

- 瀏覽器開發者工具
- Laravel Telescope
- Livewire 除錯模式

## 更新日誌

### v1.0.0
- 初始版本發布
- 基礎佈局架構
- 響應式設計支援
- 主題系統
- 無障礙功能

## 貢獻指南

1. Fork 專案
2. 建立功能分支
3. 撰寫測試
4. 提交 Pull Request

## 授權

MIT License