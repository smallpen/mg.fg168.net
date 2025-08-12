# 佈局系統使用文檔

## 概述

管理後台佈局系統提供了一個統一、響應式的使用者介面框架，支援多種裝置和螢幕尺寸。

## 佈局架構

### 主要元件結構

```
AdminLayout (主佈局)
├── TopNavBar (頂部導航列)
│   ├── MenuToggle (選單切換按鈕)
│   ├── Breadcrumb (麵包屑導航)
│   ├── GlobalSearch (全域搜尋)
│   ├── NotificationCenter (通知中心)
│   ├── ThemeToggle (主題切換)
│   └── UserMenu (使用者選單)
├── Sidebar (側邊選單)
│   ├── NavigationMenu (導航選單)
│   └── MenuSearch (選單搜尋)
└── MainContent (主要內容區域)
```

## 響應式設計

### 桌面版佈局 (≥1024px)

- **左側選單**：固定寬度 280px，可收合為 64px
- **頂部導航**：完整功能顯示
- **主要內容**：自適應寬度
- **互動方式**：滑鼠點擊、鍵盤導航

```css
.desktop-layout {
  display: grid;
  grid-template-columns: 280px 1fr;
  grid-template-rows: 64px 1fr;
  grid-template-areas: 
    "sidebar header"
    "sidebar main";
}
```

### 平板版佈局 (768px-1023px)

- **左側選單**：預設收合為圖示模式，可展開
- **頂部導航**：部分功能隱藏或合併
- **主要內容**：佔滿剩餘空間
- **互動方式**：觸控和滑鼠混合

```css
.tablet-layout {
  display: grid;
  grid-template-columns: 64px 1fr;
  grid-template-rows: 64px 1fr;
}

.tablet-layout .sidebar.expanded {
  width: 280px;
}
```

### 手機版佈局 (<768px)

- **左側選單**：抽屜模式，預設隱藏
- **頂部導航**：簡化顯示，重要功能保留
- **主要內容**：全寬顯示
- **互動方式**：觸控優先

```css
.mobile-layout {
  display: grid;
  grid-template-columns: 1fr;
  grid-template-rows: 56px 1fr;
}

.mobile-layout .sidebar {
  position: fixed;
  transform: translateX(-100%);
  transition: transform 0.3s ease;
}

.mobile-layout .sidebar.open {
  transform: translateX(0);
}
```

## 佈局狀態管理

### 側邊選單狀態

```php
// AdminLayout.php
public bool $sidebarCollapsed = false;
public bool $sidebarMobile = false;

// 切換側邊選單
public function toggleSidebar(): void
{
    $this->sidebarCollapsed = !$this->sidebarCollapsed;
    $this->dispatch('sidebar-toggled', $this->sidebarCollapsed);
}

// 切換手機版選單
public function toggleMobileSidebar(): void
{
    $this->sidebarMobile = !$this->sidebarMobile;
}
```

### 主題狀態

```php
// ThemeToggle.php
public string $currentTheme = 'light';

public function setTheme(string $theme): void
{
    $this->currentTheme = $theme;
    $this->saveThemePreference($theme);
    $this->dispatch('theme-changed', $theme);
}
```

## 佈局自訂

### 擴展主佈局

```php
// 建立自訂佈局元件
namespace App\Livewire\Admin\Layout;

class CustomAdminLayout extends AdminLayout
{
    // 新增自訂屬性
    public bool $showRightPanel = false;
    
    // 覆寫渲染方法
    public function render()
    {
        return view('livewire.admin.layout.custom-admin-layout')
            ->extends('layouts.admin')
            ->section('content');
    }
}
```

### 自訂頁面佈局

```blade
{{-- resources/views/livewire/admin/custom-page.blade.php --}}
<div class="admin-page">
    <div class="page-header">
        <h1 class="page-title">{{ $pageTitle }}</h1>
        <div class="page-actions">
            @foreach($pageActions as $action)
                <button class="btn btn-{{ $action['type'] }}">
                    {{ $action['label'] }}
                </button>
            @endforeach
        </div>
    </div>
    
    <div class="page-content">
        {{ $slot }}
    </div>
</div>
```

## 佈局事件系統

### 可監聽的事件

```javascript
// 側邊選單切換事件
document.addEventListener('sidebar-toggled', function(event) {
    const isCollapsed = event.detail;
    // 處理選單狀態變更
});

// 主題變更事件
document.addEventListener('theme-changed', function(event) {
    const theme = event.detail;
    // 處理主題變更
});

// 響應式斷點變更事件
document.addEventListener('breakpoint-changed', function(event) {
    const breakpoint = event.detail; // 'mobile', 'tablet', 'desktop'
    // 處理斷點變更
});
```

### 觸發自訂事件

```php
// 在 Livewire 元件中觸發事件
$this->dispatch('layout-updated', [
    'section' => 'header',
    'data' => $headerData
]);
```

## 效能優化

### 懶載入策略

```blade
{{-- 延遲載入非關鍵元件 --}}
<livewire:admin.dashboard.stats-chart lazy />

{{-- 條件載入 --}}
@if($showAdvancedFeatures)
    <livewire:admin.advanced.feature-panel />
@endif
```

### 快取策略

```php
// 選單結構快取
public function getMenuStructure(): array
{
    return Cache::remember(
        "admin_menu_{$this->user->id}",
        3600,
        fn() => $this->buildMenuStructure()
    );
}
```

## 無障礙功能

### 鍵盤導航

- `Tab` / `Shift+Tab`：在可聚焦元素間導航
- `Enter` / `Space`：啟動按鈕或連結
- `Escape`：關閉下拉選單或對話框
- `Arrow Keys`：在選單項目間導航

### 螢幕閱讀器支援

```blade
{{-- ARIA 標籤範例 --}}
<nav aria-label="主要導航" role="navigation">
    <ul role="menubar">
        <li role="none">
            <a href="#" role="menuitem" aria-expanded="false">
                使用者管理
            </a>
        </li>
    </ul>
</nav>
```

### 高對比模式

```css
@media (prefers-contrast: high) {
    :root {
        --color-primary: #000000;
        --color-secondary: #ffffff;
        --border-width: 2px;
    }
}
```

## 故障排除

### 常見問題

1. **選單不顯示**
   - 檢查使用者權限設定
   - 確認選單配置檔案正確
   - 檢查快取是否需要清除

2. **響應式佈局異常**
   - 檢查 CSS 斷點設定
   - 確認 JavaScript 事件監聽器正常
   - 檢查瀏覽器相容性

3. **主題切換失效**
   - 檢查 CSS 變數定義
   - 確認主題配置檔案
   - 檢查 localStorage 權限

### 除錯工具

```javascript
// 佈局除錯資訊
window.AdminLayout = {
    getCurrentBreakpoint() {
        return window.innerWidth >= 1024 ? 'desktop' : 
               window.innerWidth >= 768 ? 'tablet' : 'mobile';
    },
    
    getSidebarState() {
        return {
            collapsed: document.body.classList.contains('sidebar-collapsed'),
            mobile: document.body.classList.contains('sidebar-mobile')
        };
    },
    
    getTheme() {
        return document.documentElement.getAttribute('data-theme');
    }
};
```

## 最佳實踐

1. **效能優化**
   - 使用懶載入載入非關鍵元件
   - 實作適當的快取策略
   - 優化圖片和資源載入

2. **使用者體驗**
   - 保持一致的互動模式
   - 提供清晰的視覺回饋
   - 確保快速的響應時間

3. **維護性**
   - 遵循元件化設計原則
   - 保持程式碼的可讀性
   - 建立完整的測試覆蓋

4. **無障礙性**
   - 遵循 WCAG 2.1 AA 標準
   - 提供鍵盤導航支援
   - 確保螢幕閱讀器相容性