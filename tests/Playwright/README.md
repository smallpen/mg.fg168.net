# Livewire 表單重置功能測試框架

## 概述

這個測試框架專門為 Livewire 表單重置功能設計，提供完整的前端測試解決方案，包括：

- 標準化的表單填寫和重置測試流程
- 可重複使用的測試輔助函數
- 截圖對比和狀態驗證機制
- 響應式設計測試
- 效能測試和監控
- 自動化測試報告生成

## 檔案結構

```
tests/Playwright/
├── FormResetTestSuite.js           # 主要測試套件類別
├── FormResetTestRunner.js          # 完整測試執行器
├── FormResetFrameworkValidation.js # 框架驗證測試
├── UserListResetTest.js           # UserList 元件測試範例
├── helpers/
│   └── ComponentTestHelpers.js    # 元件測試輔助函數
├── config/
│   └── TestConfigurations.js      # 測試配置檔案
├── utils/
│   └── ScreenshotComparison.js    # 截圖對比工具
├── screenshots/                   # 截圖儲存目錄
│   ├── baselines/                 # 基準截圖
│   ├── states/                    # 狀態截圖
│   └── components/                # 元件截圖
└── README.md                      # 說明文件
```

## 核心功能

### 1. FormResetTestSuite 主要測試套件

提供基礎的測試功能：

```javascript
const testSuite = new FormResetTestSuite(page);

// Livewire 登入
await testSuite.livewireLogin('admin', 'password123');

// 等待 Livewire 元件載入
await testSuite.waitForLivewireComponent('#search');

// 填寫 Livewire 表單
await testSuite.fillLivewireForm({
    '#search': 'test value',
    '#roleFilter': 'admin'
});

// 驗證表單欄位
const validation = await testSuite.validateFormFields({
    '#search': 'test value'
});

// 執行表單重置
const resetResult = await testSuite.executeFormReset(
    'button[wire:click="resetFilters"]',
    { '#search': '' }
);

// 截圖
await testSuite.takeScreenshot('test-state');
```

### 2. ComponentTestHelpers 元件測試輔助

針對不同類型的元件提供專用測試方法：

```javascript
const helpers = new ComponentTestHelpers(page, testSuite);

// 列表篩選器測試
const result = await helpers.testListFilterComponent(config);

// 模態表單測試
const result = await helpers.testModalFormComponent(config);

// 設定表單測試
const result = await helpers.testSettingsFormComponent(config);

// 監控控制項測試
const result = await helpers.testMonitoringControlComponent(config);

// 響應式測試
const result = await helpers.testResponsiveReset(config);

// 效能測試
const result = await helpers.testResetPerformance(config);
```

### 3. ScreenshotComparison 截圖對比

提供視覺測試和截圖對比功能：

```javascript
const comparison = new ScreenshotComparison(page);

// 建立視覺測試序列
const sequence = await comparison.createVisualTestSequence(
    'test-name',
    async () => {
        // 執行測試操作
        return { success: true };
    },
    {
        formSelectors: { search: '#search' },
        componentSelector: '.livewire-component'
    }
);

// 響應式截圖序列
const responsive = await comparison.createResponsiveScreenshotSequence(
    'responsive-test',
    viewports,
    testFunction
);
```

## 使用方法

### 1. 執行框架驗證測試

首先確保測試框架正常工作：

```bash
# 使用 Playwright 執行框架驗證
npx playwright test tests/Playwright/FormResetFrameworkValidation.js
```

### 2. 執行完整測試套件

執行所有 Livewire 元件的表單重置測試：

```bash
# 執行完整測試套件
npx playwright test tests/Playwright/FormResetTestRunner.js
```

### 3. 執行特定元件測試

執行單一元件的測試：

```bash
# 執行 UserList 元件測試
npx playwright test tests/Playwright/UserListResetTest.js
```

### 4. 自訂測試配置

在 `config/TestConfigurations.js` 中新增或修改元件配置：

```javascript
const TestConfigurations = {
    myComponent: {
        componentUrl: 'http://localhost/admin/my-component',
        searchSelector: '#search',
        filterSelectors: {
            type: '#type_filter'
        },
        resetButtonSelector: 'button[wire:click="resetFilters"]',
        expectedResetValues: {
            '#search': '',
            '#type_filter': 'all'
        }
    }
};
```

## 測試配置

### 元件配置範例

```javascript
{
    componentUrl: 'http://localhost/admin/users',           // 元件頁面 URL
    searchSelector: '#search',                             // 搜尋欄位選擇器
    filterSelectors: {                                     // 篩選器選擇器
        role: '#roleFilter',
        status: '#statusFilter'
    },
    resetButtonSelector: 'button[wire:click="resetFilters"]', // 重置按鈕選擇器
    expectedResetValues: {                                 // 重置後的預期值
        '#search': '',
        '#roleFilter': 'all',
        '#statusFilter': 'all'
    },
    mobileSelectors: {                                     // 手機版選擇器
        search: '#search-mobile',
        role: '#roleFilter-mobile'
    },
    mobileResetSelector: 'button[wire:key="mobile-reset-button"]'
}
```

### 響應式測試視窗

```javascript
const viewports = [
    { name: 'Desktop', width: 1280, height: 720 },
    { name: 'Tablet', width: 768, height: 1024 },
    { name: 'Mobile', width: 375, height: 667 }
];
```

## 測試報告

測試完成後會自動生成詳細報告：

```
=== 測試摘要 ===
總測試數: 15
通過測試: 13
失敗測試: 2
成功率: 86.67%

=== 截圖記錄 ===
before-reset: before-reset-2024-01-15T10-30-00-000Z.png
after-reset: after-reset-2024-01-15T10-30-05-000Z.png

=== 失敗測試詳情 ===
❌ permission_form_reset_test: {...}
```

## 最佳實踐

### 1. 測試前準備

- 確保測試資料庫有完整的測試資料
- 確認所有 Livewire 元件正常載入
- 檢查網路連線和服務狀態

### 2. 測試設計

- 使用描述性的測試名稱
- 包含前置條件和後置驗證
- 適當使用等待時間避免競態條件
- 記錄重要的測試步驟

### 3. 錯誤處理

- 包含適當的錯誤處理邏輯
- 提供有意義的錯誤訊息
- 在測試失敗時截圖記錄狀態

### 4. 效能考量

- 重複使用瀏覽器實例
- 適當設定超時時間
- 定期清理舊截圖檔案

## 故障排除

### 常見問題

1. **登入失敗**
   - 檢查測試使用者是否存在
   - 確認密碼正確
   - 檢查 Livewire 是否正常載入

2. **元件載入超時**
   - 增加等待時間
   - 檢查選擇器是否正確
   - 確認頁面完全載入

3. **表單重置失敗**
   - 檢查重置按鈕選擇器
   - 確認 Livewire 事件正常觸發
   - 驗證預期重置值是否正確

4. **截圖失敗**
   - 確認截圖目錄存在且可寫入
   - 檢查磁碟空間
   - 驗證頁面是否正常載入

### 偵錯技巧

```javascript
// 啟用詳細日誌
console.log('🔍 偵錯資訊:', await page.evaluate('window.Livewire.all()'));

// 檢查元素是否存在
const element = await page.$('#search');
console.log('元素存在:', !!element);

// 查看 console 錯誤
const logs = await page.evaluate(() => console.log('Current state'));
```

## 擴展功能

### 新增自訂測試類型

```javascript
class CustomTestHelpers extends ComponentTestHelpers {
    async testCustomComponent(config) {
        // 自訂測試邏輯
        return {
            componentType: 'Custom',
            success: true
        };
    }
}
```

### 整合其他測試工具

```javascript
// 整合 MySQL 驗證
const mysqlResult = await mysql.executeQuery({
    query: "SELECT * FROM users WHERE username = 'test'",
    database: "laravel_admin"
});
```

這個測試框架提供了完整的 Livewire 表單重置功能測試解決方案，可以確保所有修復的元件都能正常工作。