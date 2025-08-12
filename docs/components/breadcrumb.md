# 麵包屑導航元件 (Breadcrumb Component)

## 概述

麵包屑導航元件提供了一個直觀的導航路徑顯示，幫助使用者了解當前頁面在網站結構中的位置，並提供快速返回上層頁面的功能。

## 功能特色

### 核心功能
- **動態麵包屑生成**：根據當前路由自動生成麵包屑路徑
- **路徑壓縮邏輯**：當路徑超過 4 層時自動壓縮顯示
- **點擊導航**：支援點擊麵包屑項目進行頁面導航
- **響應式設計**：適應不同螢幕尺寸的顯示需求

### 進階功能
- **省略號下拉選單**：壓縮模式下可展開查看隱藏的路徑項目
- **JSON-LD 結構化資料**：提供搜尋引擎優化支援
- **無障礙功能**：完整的 ARIA 標籤和鍵盤導航支援
- **行動版優化**：提供簡化的返回按鈕介面

## 使用方法

### 基本使用

```blade
<!-- 在 Blade 模板中使用 -->
<livewire:admin.layout.breadcrumb />
```

### 在 TopNavBar 中整合

```blade
<!-- 已整合在 TopNavBar 元件中 -->
<div class="ml-4 flex-1 min-w-0">
    <livewire:admin.layout.breadcrumb />
</div>
```

## 元件 API

### 公開屬性

| 屬性名稱 | 類型 | 預設值 | 說明 |
|---------|------|--------|------|
| `breadcrumbs` | array | `[]` | 麵包屑項目陣列 |
| `compressed` | bool | `false` | 是否啟用路徑壓縮 |
| `maxLevels` | int | `4` | 最大顯示層級數 |
| `currentRoute` | string | `''` | 當前路由名稱 |

### 計算屬性

| 屬性名稱 | 回傳類型 | 說明 |
|---------|----------|------|
| `displayBreadcrumbs` | array | 處理壓縮邏輯後的顯示項目 |
| `fullBreadcrumbs` | array | 完整的麵包屑項目（用於下拉選單） |
| `breadcrumbJsonLd` | string | JSON-LD 結構化資料 |
| `isMobile` | bool | 是否為行動裝置 |

### 公開方法

| 方法名稱 | 參數 | 說明 |
|---------|------|------|
| `navigateTo(string $route)` | 路由名稱 | 導航到指定路由 |
| `expandBreadcrumbs()` | 無 | 展開壓縮的麵包屑 |
| `compressBreadcrumbs()` | 無 | 壓縮麵包屑顯示 |
| `refreshBreadcrumbs(string $routeName = null)` | 路由名稱（可選） | 重新載入麵包屑 |

### 事件監聽

| 事件名稱 | 參數 | 說明 |
|---------|------|------|
| `breadcrumb-refresh` | 無 | 重新整理麵包屑 |
| `route-changed` | `routeName` | 路由變更時更新麵包屑 |

### 事件觸發

| 事件名稱 | 參數 | 說明 |
|---------|------|------|
| `breadcrumb-error` | `message` | 導航錯誤時觸發 |
| `push-to-head` | `script` | 推送 JSON-LD 到頁面頭部 |

## 麵包屑項目結構

每個麵包屑項目包含以下屬性：

```php
[
    'title' => '頁面標題',        // 顯示文字
    'route' => 'route.name',     // 路由名稱（可為 null）
    'active' => false,           // 是否為當前頁面
    'ellipsis' => false,         // 是否為省略號項目（可選）
]
```

## 樣式自訂

### CSS 類別

主要的 CSS 類別包括：

- `.breadcrumb-container`：麵包屑容器
- `.breadcrumb-nav`：導航元素
- `.breadcrumb-list`：麵包屑列表
- `.breadcrumb-item`：單個麵包屑項目
- `.breadcrumb-link`：可點擊的麵包屑連結
- `.breadcrumb-current`：當前頁面項目
- `.breadcrumb-separator`：分隔符號
- `.breadcrumb-mobile`：行動版容器

### 響應式斷點

- **桌面版** (≥641px)：顯示完整麵包屑列表
- **行動版** (<641px)：顯示簡化的返回按鈕和當前頁面

## 無障礙功能

### ARIA 標籤

- `aria-label="麵包屑導航"`：導航區域標籤
- `aria-current="page"`：標記當前頁面
- `aria-label="顯示隱藏的麵包屑項目"`：省略號按鈕說明

### 鍵盤導航

- 支援 Tab 鍵在可點擊項目間導航
- 支援 Enter 鍵啟動導航
- 支援 Escape 鍵關閉下拉選單

## 效能優化

### 快取機制

麵包屑資料透過 NavigationService 進行快取，減少重複計算：

```php
// 快取鍵格式
"menu_structure_{$user->id}_{$roleIds}"
```

### 延遲載入

JSON-LD 結構化資料在元件渲染時動態生成，避免不必要的計算。

## 測試

### 單元測試

```bash
# 執行麵包屑元件測試
php artisan test tests/Feature/Admin/Layout/BreadcrumbTest.php
```

### 整合測試

```bash
# 執行整合測試
php artisan test tests/Feature/Admin/Layout/BreadcrumbIntegrationTest.php
```

## 故障排除

### 常見問題

1. **麵包屑不顯示**
   - 檢查 NavigationService 是否正確註冊
   - 確認當前路由是否在選單結構中定義

2. **路徑壓縮不正常**
   - 檢查 `maxLevels` 設定
   - 確認麵包屑項目數量是否超過限制

3. **點擊導航失效**
   - 檢查路由是否存在
   - 確認使用者是否有相應權限

### 除錯模式

可以在瀏覽器控制台中檢查麵包屑狀態：

```javascript
// 檢查當前麵包屑資料
console.log(Livewire.find('breadcrumb-component-id').breadcrumbs);
```

## 相關文件

- [NavigationService 文件](../services/navigation-service.md)
- [TopNavBar 元件文件](./top-nav-bar.md)
- [管理後台佈局系統](../admin-layout-system.md)