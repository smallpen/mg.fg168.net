# 管理後台無障礙功能文件

## 概述

本管理後台系統實作了完整的無障礙功能，符合 WCAG 2.1 AA 級標準，為所有使用者提供平等的存取體驗。

## 功能特色

### 1. 鍵盤導航支援

#### 全域快捷鍵
- `Alt + M`: 開啟/關閉主選單
- `Alt + S`: 聚焦搜尋框
- `Alt + N`: 開啟通知中心
- `Alt + U`: 開啟使用者選單
- `Alt + T`: 切換主題
- `Alt + H`: 回到首頁
- `Alt + A`: 開啟無障礙設定

#### 一般導航
- `Tab`: 移動到下一個可聚焦元素
- `Shift + Tab`: 移動到上一個可聚焦元素
- `Enter`: 啟動連結或按鈕
- `Space`: 啟動按鈕或核取方塊
- `Escape`: 關閉對話框或下拉選單

#### 選單導航
- `↑ ↓`: 在選單項目間移動
- `→`: 展開子選單
- `←`: 收合子選單
- `Home`: 移動到第一個項目
- `End`: 移動到最後一個項目

### 2. ARIA 標籤和語義化標記

#### 頁面地標
- `role="banner"`: 頁面標題區域
- `role="navigation"`: 主要導航選單
- `role="main"`: 主要內容區域
- `role="search"`: 搜尋功能
- `role="complementary"`: 輔助資訊區域
- `role="contentinfo"`: 頁面資訊區域

#### 互動元素
- `aria-label`: 提供元素的無障礙標籤
- `aria-expanded`: 指示可展開元素的狀態
- `aria-haspopup`: 指示元素有彈出選單
- `aria-controls`: 指示元素控制的目標
- `aria-describedby`: 連結到描述性文字
- `aria-live`: 即時更新區域
- `aria-atomic`: 指示是否完整讀取更新內容

### 3. 螢幕閱讀器支援

#### 自動宣告功能
- 頁面變更通知
- 載入狀態更新
- 表單驗證錯誤
- 操作結果回饋
- 通知訊息
- 選單狀態變更
- 搜尋結果更新

#### 結構化內容
- 標題層級結構
- 表格標題和說明
- 表單標籤關聯
- 清單和項目標記
- 地標區域識別

### 4. 高對比模式

#### 視覺增強
- 增強文字和背景對比度（150%）
- 明確的邊框和分隔線
- 高對比色彩配置
- 支援亮色和暗色主題

#### 自動偵測
- 支援系統偏好設定 `prefers-contrast: high`
- 使用者可手動切換
- 設定持久化儲存

### 5. 焦點管理和跳轉連結

#### 跳轉連結
- 跳至主要內容 (`Alt + C`)
- 跳至導航選單 (`Alt + N`)
- 跳至搜尋功能 (`Alt + S`)
- 跳至使用者選單 (`Alt + U`)

#### 焦點管理
- 智慧焦點設定
- 焦點歷史記錄
- 焦點陷阱（模態框、下拉選單）
- 焦點恢復機制
- 視覺焦點指示器

## 元件架構

### AccessibilitySettings 元件
負責無障礙偏好設定的管理介面。

**功能：**
- 視覺設定（高對比、大字體、減少動畫）
- 導航設定（鍵盤導航、跳轉連結、焦點指示器）
- 螢幕閱讀器設定
- 鍵盤快捷鍵說明
- 設定重設功能

### FocusManager 元件
管理頁面焦點狀態和鍵盤導航。

**功能：**
- 焦點設定和恢復
- 焦點陷阱管理
- 鍵盤事件處理
- 方向鍵導航
- 容器內導航

### ScreenReaderSupport 元件
提供螢幕閱讀器使用者所需的輔助功能。

**功能：**
- 即時訊息宣告
- 頁面變更通知
- 載入狀態宣告
- 表單錯誤宣告
- 操作結果宣告
- 地標說明

### SkipLinks 元件
提供快速跳轉到頁面主要區域的功能。

**功能：**
- 跳轉連結顯示/隱藏
- 鍵盤快捷鍵支援
- 目標元素高亮
- 滾動到目標位置

## 服務類別

### AccessibilityService
核心無障礙功能服務。

**方法：**
- `getUserAccessibilityPreferences()`: 取得使用者偏好設定
- `saveUserAccessibilityPreferences()`: 儲存使用者偏好設定
- `generateAriaLabel()`: 產生 ARIA 標籤
- `getKeyboardShortcuts()`: 取得鍵盤快捷鍵說明
- `getAccessibilityClasses()`: 產生無障礙 CSS 類別

## CSS 樣式

### 無障礙專用樣式
- `.sr-only`: 螢幕閱讀器專用內容
- `.high-contrast`: 高對比模式
- `.large-text`: 大字體模式
- `.reduced-motion`: 減少動畫模式
- `.enhanced-focus`: 增強焦點指示器
- `.skip-target-highlight`: 跳轉目標高亮

### 響應式調整
- 行動裝置焦點指示器加粗
- 觸控裝置按鈕尺寸調整
- 小螢幕字體大小優化

## 使用指南

### 開發者指南

#### 1. 新增無障礙功能
```php
// 在 Livewire 元件中使用無障礙服務
use App\Services\AccessibilityService;

class MyComponent extends Component
{
    protected AccessibilityService $accessibilityService;
    
    public function boot(AccessibilityService $accessibilityService)
    {
        $this->accessibilityService = $accessibilityService;
    }
    
    public function render()
    {
        return view('my-component', [
            'ariaLabel' => $this->accessibilityService->generateAriaLabel('my_context')
        ]);
    }
}
```

#### 2. 在視圖中使用 ARIA 標籤
```blade
<button aria-label="{{ $ariaLabel }}" 
        aria-expanded="false"
        aria-haspopup="true">
    按鈕文字
</button>
```

#### 3. 觸發螢幕閱讀器宣告
```php
// 在 Livewire 元件中
$this->dispatch('screen-reader-announce', [
    'message' => '操作完成',
    'priority' => 'polite'
]);
```

### 使用者指南

#### 1. 開啟無障礙設定
- 點擊頂部導航列的無障礙圖示
- 或使用快捷鍵 `Alt + A`

#### 2. 調整視覺設定
- **高對比模式**: 增強文字和背景對比度
- **大字體模式**: 增大文字大小
- **減少動畫**: 停用或減少動畫效果

#### 3. 使用鍵盤導航
- 使用 `Tab` 鍵在元素間移動
- 使用方向鍵在選單中導航
- 使用快捷鍵快速存取功能

#### 4. 使用跳轉連結
- 按 `Tab` 鍵顯示跳轉連結
- 點擊或按 `Enter` 跳轉到目標區域

## 測試

### 自動化測試
系統包含完整的無障礙功能測試：

```bash
# 執行無障礙功能測試
docker-compose exec app php artisan test --filter=AccessibilityTest
```

### 手動測試檢查清單

#### 鍵盤導航測試
- [ ] 所有互動元素都可以透過鍵盤存取
- [ ] Tab 順序符合邏輯
- [ ] 焦點指示器清晰可見
- [ ] 快捷鍵正常運作

#### 螢幕閱讀器測試
- [ ] 頁面結構清晰
- [ ] ARIA 標籤正確
- [ ] 即時更新正常宣告
- [ ] 表單標籤關聯正確

#### 視覺測試
- [ ] 高對比模式正常運作
- [ ] 大字體模式正常顯示
- [ ] 焦點指示器在所有主題下都可見
- [ ] 色彩對比度符合標準

## 瀏覽器支援

### 支援的螢幕閱讀器
- NVDA (Windows)
- JAWS (Windows)
- VoiceOver (macOS/iOS)
- TalkBack (Android)
- Orca (Linux)

### 支援的瀏覽器
- Chrome 88+
- Firefox 85+
- Safari 14+
- Edge 88+

## 標準符合性

本系統符合以下無障礙標準：
- WCAG 2.1 AA 級
- Section 508
- EN 301 549
- JIS X 8341

## 持續改進

### 回饋機制
使用者可以透過以下方式提供無障礙功能回饋：
- 系統內建回饋表單
- 電子郵件聯絡
- 使用者測試會議

### 定期審查
- 每季進行無障礙功能審查
- 年度第三方無障礙評估
- 持續監控使用者回饋

## 相關資源

### 文件連結
- [WCAG 2.1 指南](https://www.w3.org/WAI/WCAG21/quickref/)
- [ARIA 最佳實踐](https://www.w3.org/WAI/ARIA/apg/)
- [鍵盤導航模式](https://www.w3.org/WAI/ARIA/apg/practices/keyboard-interface/)

### 工具推薦
- [axe DevTools](https://www.deque.com/axe/devtools/)
- [WAVE Web Accessibility Evaluator](https://wave.webaim.org/)
- [Lighthouse Accessibility Audit](https://developers.google.com/web/tools/lighthouse)