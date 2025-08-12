# 焦點管理器使用指南

## 概述

焦點管理器 (FocusManager) 是一個 Livewire 3.0 元件，專門用於管理網頁應用程式中的鍵盤焦點和無障礙導航。它提供了完整的焦點控制功能，包括焦點陷阱、鍵盤導航、跳轉連結等。

## 主要功能

### 1. 焦點控制
- 程式化設定焦點到指定元素
- 焦點歷史記錄管理
- 自動滾動到聚焦元素

### 2. 焦點陷阱 (Focus Trap)
- 模態框焦點陷阱
- 下拉選單焦點陷阱
- 自訂容器焦點陷阱

### 3. 鍵盤導航
- Tab 鍵循環導航
- 方向鍵選單導航
- 表格鍵盤導航
- Escape 鍵關閉功能

### 4. 無障礙支援
- 跳轉連結 (Skip Links)
- ARIA 屬性支援
- 螢幕閱讀器友善

## 安裝和設定

### 1. 確認 Livewire 3.0 配置

確保 `config/livewire.php` 中的設定正確：

```php
'class_namespace' => 'App\\Livewire',
'view_path' => resource_path('views/livewire'),
```

### 2. 包含焦點管理器元件

在需要焦點管理的頁面中包含元件：

```blade
<livewire:admin.layout.focus-manager />
```

## 基本使用方法

### 1. 設定焦點

#### 透過 Livewire 元件
```php
// 在 Livewire 元件中
$this->dispatch('set-focus', ['elementId' => 'target-element']);
```

#### 透過 JavaScript
```javascript
// 直接設定焦點
document.getElementById('target-element').focus();

// 或使用全域焦點管理器
if (window.focusManager) {
    window.focusManager.setFocus('target-element');
}
```

### 2. 啟用焦點陷阱

#### 模態框範例
```php
// 開啟模態框時
$this->dispatch('modal-opened', ['modalId' => 'my-modal']);

// 關閉模態框時
$this->dispatch('modal-closed');
```

#### HTML 結構
```html
<div class="modal" id="my-modal" role="dialog">
    <div class="modal-content">
        <button class="modal-close" data-close>關閉</button>
        <input type="text" placeholder="第一個可聚焦元素">
        <button>確認</button>
    </div>
</div>
```

### 3. 跳轉連結實作

```html
<div class="skip-links">
    <a href="#main-content" 
       class="skip-link"
       onclick="skipToElement('main-content')">
        跳轉到主要內容
    </a>
    <a href="#navigation" 
       class="skip-link"
       onclick="skipToElement('navigation')">
        跳轉到導航選單
    </a>
</div>

<script>
function skipToElement(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.focus();
        element.scrollIntoView({ behavior: 'smooth' });
    }
}
</script>
```

## 進階功能

### 1. 自訂鍵盤導航

```javascript
// 監聽特定鍵盤事件
document.addEventListener('keydown', function(event) {
    if (event.key === 'F6') {
        // 切換到下一個區域
        switchToNextRegion();
    }
});
```

### 2. 選單導航實作

```html
<ul class="nav-menu" role="menubar">
    <li role="none">
        <a href="#" role="menuitem" tabindex="0">首頁</a>
    </li>
    <li role="none">
        <a href="#" role="menuitem" tabindex="-1">選單項目</a>
        <ul class="submenu" role="menu">
            <li role="none">
                <a href="#" role="menuitem" tabindex="-1">子項目 1</a>
            </li>
        </ul>
    </li>
</ul>
```

### 3. 表格導航實作

```html
<table role="table">
    <thead>
        <tr role="row">
            <th role="columnheader" tabindex="0">標題 1</th>
            <th role="columnheader" tabindex="-1">標題 2</th>
        </tr>
    </thead>
    <tbody>
        <tr role="row">
            <td tabindex="-1">資料 1</td>
            <td tabindex="-1">資料 2</td>
        </tr>
    </tbody>
</table>
```

## CSS 樣式指南

### 1. 跳轉連結樣式

```css
.skip-link {
    position: absolute;
    left: -10000px;
    top: auto;
    width: 1px;
    height: 1px;
    overflow: hidden;
}

.skip-link:focus {
    position: static;
    width: auto;
    height: auto;
    left: auto;
    background: #000;
    color: #fff;
    padding: 8px 16px;
    text-decoration: none;
    border-radius: 4px;
    z-index: 1000;
}
```

### 2. 焦點指示器

```css
/* 自訂焦點樣式 */
button:focus,
input:focus,
select:focus,
textarea:focus,
a:focus {
    outline: 2px solid #007bff;
    outline-offset: 2px;
}

/* 高對比度焦點指示器 */
@media (prefers-contrast: high) {
    *:focus {
        outline: 3px solid #000;
        outline-offset: 2px;
    }
}
```

### 3. 模態框樣式

```css
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
}

.modal {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    z-index: 1001;
}
```

## 事件系統

### 1. 可監聽的事件

```php
// 在 Livewire 元件中監聽事件
#[On('modal-opened')]
public function handleModalOpened(array $data) {
    // 處理模態框開啟
}

#[On('modal-closed')]
public function handleModalClosed() {
    // 處理模態框關閉
}

#[On('skip-to-element')]
public function handleSkipToElement(array $data) {
    // 處理跳轉到元素
}
```

### 2. 觸發事件

```php
// 觸發焦點相關事件
$this->dispatch('set-focus', ['elementId' => 'target']);
$this->dispatch('enable-focus-trap', ['containerId' => 'modal']);
$this->dispatch('disable-focus-trap');
$this->dispatch('scroll-to-element', ['elementId' => 'target']);
```

## 最佳實踐

### 1. HTML 結構
- 使用語意化的 HTML 標籤
- 正確設定 ARIA 屬性
- 合理的 tabindex 值設定

### 2. 鍵盤導航
- 提供清晰的焦點指示器
- 支援標準鍵盤快捷鍵
- 實作跳轉連結

### 3. 無障礙設計
- 確保所有互動元素都可以透過鍵盤存取
- 提供適當的 ARIA 標籤和描述
- 支援螢幕閱讀器

### 4. 效能考量
- 避免過度的 DOM 查詢
- 適當使用事件委派
- 清理不需要的事件監聽器

## 故障排除

### 1. 常見問題

**問題：焦點無法設定到指定元素**
```javascript
// 檢查元素是否存在且可聚焦
const element = document.getElementById('target');
if (element && element.tabIndex !== -1) {
    element.focus();
}
```

**問題：焦點陷阱無法正常運作**
```javascript
// 確保容器內有可聚焦的元素
const container = document.getElementById('modal');
const focusableElements = container.querySelectorAll(
    'a[href], button, input, textarea, select, [tabindex]:not([tabindex="-1"])'
);
```

**問題：鍵盤導航衝突**
```javascript
// 檢查事件是否被正確處理
document.addEventListener('keydown', function(event) {
    console.log('Key pressed:', event.key);
    // 確保沒有其他處理器阻止事件
});
```

### 2. 除錯工具

```javascript
// 焦點除錯工具
function debugFocus() {
    console.log('Current focus:', document.activeElement);
    console.log('Focus history:', window.focusManager?.focusHistory);
}

// 在控制台中呼叫
debugFocus();
```

## 測試

### 1. 手動測試檢查清單
- [ ] Tab 鍵導航順序正確
- [ ] 所有互動元素都可以透過鍵盤存取
- [ ] 焦點指示器清晰可見
- [ ] 模態框焦點陷阱正常運作
- [ ] 跳轉連結功能正常
- [ ] Escape 鍵可以關閉模態框

### 2. 自動化測試
```php
// 在 Laravel 測試中
public function test_focus_manager_sets_focus_correctly()
{
    $component = Livewire::test(FocusManager::class);
    
    $component->call('setFocus', 'test-element')
              ->assertDispatched('set-focus', ['elementId' => 'test-element']);
}
```

## 瀏覽器支援

- Chrome 88+
- Firefox 85+
- Safari 14+
- Edge 88+

## 相關資源

- [WCAG 2.1 無障礙指南](https://www.w3.org/WAI/WCAG21/quickref/)
- [ARIA 最佳實踐](https://www.w3.org/WAI/ARIA/apg/)
- [Livewire 3.0 文檔](https://livewire.laravel.com/)