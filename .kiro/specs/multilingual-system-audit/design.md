# 多語系系統審視與修復設計文件

## 概述

本設計文件詳細說明如何對現有的多語系系統進行全面審視和修復，確保所有功能頁面在不同語系下都能正常運作。設計採用系統化的方法，從語言檔案檢查到自動化測試，提供完整的解決方案。

## 架構設計

### 多語系架構概覽

```
多語系系統架構
├── 語言檔案層 (Language Files)
│   ├── lang/zh_TW/*.php (正體中文)
│   ├── lang/en/*.php (英文)
│   └── 語言檔案驗證器
├── 中介軟體層 (Middleware)
│   ├── SetLocale (語言設定)
│   └── 語言偏好處理
├── 元件層 (Components)
│   ├── LanguageSelector (語言選擇器)
│   ├── ThemeToggle (主題切換)
│   └── 多語系表單元件
├── 視圖層 (Views)
│   ├── 登入頁面
│   ├── 管理後台頁面
│   └── 錯誤頁面
└── 測試層 (Testing)
    ├── 語言檔案完整性測試
    ├── 頁面多語系功能測試
    └── 自動化語言切換測試
```

## 元件設計

### 1. 語言檔案驗證器 (LanguageFileValidator)

**目的：** 檢查語言檔案的完整性和一致性

**功能：**
- 比較不同語言檔案的鍵值完整性
- 識別缺少的翻譯鍵
- 檢測硬編碼文字
- 生成語言檔案報告

**介面：**
```php
class LanguageFileValidator
{
    public function validateCompleteness(): ValidationResult
    public function findMissingKeys(): array
    public function detectHardcodedText(): array
    public function generateReport(): LanguageReport
}
```

### 2. 多語系測試套件 (MultilingualTestSuite)

**目的：** 自動化測試多語系功能

**功能：**
- 測試語言切換功能
- 驗證頁面內容翻譯
- 檢查語言偏好持久化
- 測試錯誤處理機制

**測試流程：**
```
測試流程
├── 登入頁面測試
│   ├── 語言切換測試
│   ├── 主題切換按鈕翻譯測試
│   └── 表單驗證訊息測試
├── 管理後台測試
│   ├── 導航選單翻譯測試
│   ├── 資料表格翻譯測試
│   ├── 操作按鈕翻譯測試
│   └── 訊息通知翻譯測試
└── 語言偏好測試
    ├── Session 儲存測試
    ├── 使用者偏好儲存測試
    └── 瀏覽器語言偵測測試
```

### 3. 語言檔案管理工具 (LanguageFileManager)

**目的：** 管理和維護語言檔案

**功能：**
- 同步語言檔案鍵值
- 匯出/匯入翻譯內容
- 翻譯進度追蹤
- 自動化翻譯建議

## 資料模型

### 語言偏好設定模型

```php
// 使用者表格擴展
Schema::table('users', function (Blueprint $table) {
    $table->string('locale', 10)->default('zh_TW')->after('email');
    $table->timestamp('locale_updated_at')->nullable()->after('locale');
});

// 語言設定快取
Cache::put("user_locale_{$userId}", $locale, 3600);
```

### 語言檔案結構標準化

```php
// 標準語言檔案結構
return [
    'common' => [
        'actions' => [...],
        'messages' => [...],
        'validation' => [...],
    ],
    'pages' => [
        'login' => [...],
        'dashboard' => [...],
        'users' => [...],
    ],
    'components' => [
        'theme_toggle' => [...],
        'language_selector' => [...],
    ],
];
```

## 錯誤處理設計

### 語言回退機制

```php
class LanguageFallbackHandler
{
    private array $fallbackChain = ['zh_TW', 'en'];
    
    public function translate(string $key, array $replace = []): string
    {
        foreach ($this->fallbackChain as $locale) {
            if ($translation = $this->getTranslation($key, $locale)) {
                return $this->replaceParameters($translation, $replace);
            }
        }
        
        // 最後回退：返回鍵值本身
        return $key;
    }
}
```

### 錯誤日誌記錄

```php
// 語言相關錯誤記錄
Log::channel('multilingual')->warning('Missing translation key', [
    'key' => $translationKey,
    'locale' => app()->getLocale(),
    'page' => request()->url(),
    'user_id' => auth()->id(),
]);
```

## 測試策略

### 1. 單元測試

**語言檔案測試：**
- 測試語言檔案載入
- 測試翻譯鍵存在性
- 測試參數替換功能

**中介軟體測試：**
- 測試語言偵測邏輯
- 測試語言設定持久化
- 測試回退機制

### 2. 整合測試

**頁面渲染測試：**
- 測試頁面在不同語言下的渲染
- 測試動態內容翻譯
- 測試 JavaScript 翻譯

**使用者流程測試：**
- 測試完整的語言切換流程
- 測試跨頁面語言一致性
- 測試語言偏好記憶功能

### 3. 端到端測試 (使用 Playwright)

**自動化測試腳本：**
```javascript
// 語言切換測試
async function testLanguageSwitching() {
    await page.goto('/admin/login');
    
    // 測試登入頁面語言切換
    await page.click('.language-selector');
    await page.click('[data-locale="en"]');
    await expect(page.locator('h2')).toContainText('Login');
    
    // 測試主題切換按鈕翻譯
    await expect(page.locator('[data-theme-toggle]')).toContainText('Dark Mode');
    
    // 登入並測試管理後台
    await login(page);
    await testAdminPagesTranslation(page);
}
```

## 實作計劃

### 階段 1：語言檔案審視和修復

1. **語言檔案完整性檢查**
   - 建立語言檔案驗證工具
   - 比較中英文語言檔案
   - 識別缺少的翻譯鍵

2. **硬編碼文字修復**
   - 掃描視圖檔案中的硬編碼文字
   - 將硬編碼文字移至語言檔案
   - 更新視圖檔案使用翻譯函數

### 階段 2：登入頁面多語系修復

1. **登入頁面標題修復**
   - 修改頁面標題使用語言檔案
   - 更新主題切換按鈕翻譯
   - 確保所有文字都來自語言檔案

2. **表單驗證訊息**
   - 檢查表單驗證訊息翻譯
   - 確保錯誤訊息正確顯示
   - 測試不同語言下的驗證流程

### 階段 3：管理後台頁面審視

1. **導航和選單翻譯**
   - 檢查所有導航選單翻譯
   - 確保麵包屑導航翻譯正確
   - 驗證下拉選單翻譯

2. **資料表格和操作按鈕**
   - 檢查表格欄位標題翻譯
   - 確保操作按鈕翻譯正確
   - 驗證分頁控制項翻譯

### 階段 4：自動化測試實作

1. **建立測試套件**
   - 實作多語系測試基礎類別
   - 建立頁面翻譯測試方法
   - 設定測試資料和環境

2. **端到端測試**
   - 使用 Playwright 建立自動化測試
   - 測試完整的語言切換流程
   - 驗證語言偏好持久化

### 階段 5：錯誤處理和優化

1. **回退機制實作**
   - 實作語言回退處理器
   - 加入錯誤日誌記錄
   - 優化語言載入效能

2. **使用者體驗優化**
   - 改善語言切換動畫
   - 優化語言選擇器介面
   - 加入語言切換確認機制

## 品質保證

### 程式碼品質標準

- 所有新增的翻譯鍵都必須在兩種語言中定義
- 視圖檔案不得包含硬編碼的顯示文字
- 所有語言相關功能都必須有對應的測試
- 錯誤處理必須包含適當的日誌記錄

### 測試覆蓋率要求

- 語言檔案驗證：100% 覆蓋率
- 多語系中介軟體：95% 覆蓋率
- 語言切換功能：90% 覆蓋率
- 端到端測試：涵蓋所有主要頁面

### 效能要求

- 語言切換響應時間：< 500ms
- 語言檔案載入時間：< 100ms
- 記憶體使用：語言檔案快取 < 5MB
- 資料庫查詢：語言偏好查詢 < 10ms

## 維護和監控

### 持續監控

- 監控缺少翻譯鍵的錯誤
- 追蹤語言切換使用統計
- 監控語言檔案載入效能
- 記錄使用者語言偏好分佈

### 維護流程

- 定期檢查語言檔案完整性
- 更新翻譯內容的版本控制
- 建立翻譯內容審核流程
- 設定自動化翻譯品質檢查

這個設計提供了完整的多語系系統審視和修復方案，確保系統在不同語言環境下都能提供一致且優質的使用者體驗。