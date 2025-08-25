# 多語系系統使用指南

## 概述

本系統支援正體中文（zh_TW）和英文（en）雙語介面，提供完整的多語系功能，包括語言切換、語言偏好記憶、以及語言回退機制。

## 功能特色

### 1. 語言切換功能
- 支援即時語言切換，無需重新載入頁面
- 語言選擇器位於頁面右上角
- 支援鍵盤導航和無障礙功能

### 2. 語言偏好記憶
- 已登入使用者：語言偏好儲存在使用者資料中
- 未登入使用者：語言偏好儲存在 Session 中
- 首次訪問：自動偵測瀏覽器語言偏好

### 3. 語言回退機制
- 當翻譯鍵不存在時，自動回退到預設語言
- 回退順序：當前語言 → 正體中文 → 英文 → 鍵值本身
- 錯誤記錄：缺少的翻譯鍵會記錄到系統日誌

## 使用者操作指南

### 切換語言

#### 方法一：使用語言選擇器
1. 點擊頁面右上角的語言選擇器
2. 從下拉選單中選擇所需語言
3. 頁面會立即更新為選擇的語言

#### 方法二：使用鍵盤快捷鍵
1. 按 `Tab` 鍵導航到語言選擇器
2. 按 `Enter` 或 `Space` 開啟選單
3. 使用方向鍵選擇語言
4. 按 `Enter` 確認選擇

### 語言偏好設定

#### 已登入使用者
- 語言偏好會自動儲存到使用者帳號
- 下次登入時會自動使用上次選擇的語言
- 可在個人資料頁面中修改預設語言

#### 未登入使用者
- 語言偏好儲存在瀏覽器 Session 中
- 關閉瀏覽器後需要重新選擇
- 建議註冊帳號以永久保存語言偏好

## 管理員功能

### 語言檔案管理

#### 檢查語言檔案完整性
```bash
# 執行語言檔案完整性檢查
docker-compose exec app php artisan lang:check

# 生成語言檔案報告
docker-compose exec app php artisan lang:report
```

#### 同步語言檔案
```bash
# 同步所有語言檔案
docker-compose exec app php artisan lang:sync

# 同步特定語言檔案
docker-compose exec app php artisan lang:sync --locale=en
```

### 翻譯管理

#### 新增翻譯鍵
1. 在 `lang/zh_TW/` 目錄中新增翻譯鍵
2. 在對應的 `lang/en/` 檔案中新增相同鍵值
3. 執行語言檔案檢查確認完整性

#### 修改翻譯內容
1. 編輯對應的語言檔案
2. 清除語言快取：`php artisan cache:clear`
3. 測試修改後的翻譯顯示

### 監控和維護

#### 查看語言相關日誌
```bash
# 查看多語系日誌
docker-compose exec app tail -f storage/logs/multilingual.log

# 搜尋缺少翻譯鍵的錯誤
docker-compose exec app grep "Missing translation" storage/logs/multilingual.log
```

#### 效能監控
- 語言檔案載入時間應 < 100ms
- 語言切換響應時間應 < 500ms
- 記憶體使用：語言檔案快取 < 5MB

## 開發者指南

### 在程式碼中使用翻譯

#### Blade 模板中
```blade
<!-- 基本翻譯 -->
{{ __('common.actions.save') }}

<!-- 帶參數的翻譯 -->
{{ __('messages.welcome', ['name' => $user->name]) }}

<!-- 複數形式翻譯 -->
{{ trans_choice('messages.items', $count) }}
```

#### PHP 程式碼中
```php
// 基本翻譯
$message = __('common.messages.success');

// 帶參數的翻譯
$message = __('messages.user_created', ['name' => $user->name]);

// 在 Controller 中
return redirect()->back()->with('success', __('messages.saved'));
```

#### JavaScript 中
```javascript
// 使用 Laravel 的 @json 指令傳遞翻譯
const translations = @json(__('js'));

// 或使用專門的翻譯函數
function __(key, replace = {}) {
    let translation = translations[key] || key;
    
    Object.keys(replace).forEach(key => {
        translation = translation.replace(`:${key}`, replace[key]);
    });
    
    return translation;
}
```

### 新增語言檔案

#### 建立新的語言檔案
1. 在 `lang/zh_TW/` 和 `lang/en/` 目錄中建立相同名稱的檔案
2. 使用相同的陣列結構和鍵值
3. 提供對應語言的翻譯內容

#### 語言檔案結構範例
```php
<?php
// lang/zh_TW/example.php
return [
    'title' => '範例頁面',
    'actions' => [
        'create' => '建立',
        'edit' => '編輯',
        'delete' => '刪除',
    ],
    'messages' => [
        'success' => '操作成功',
        'error' => '操作失敗',
    ],
];
```

```php
<?php
// lang/en/example.php
return [
    'title' => 'Example Page',
    'actions' => [
        'create' => 'Create',
        'edit' => 'Edit',
        'delete' => 'Delete',
    ],
    'messages' => [
        'success' => 'Operation successful',
        'error' => 'Operation failed',
    ],
];
```

## 測試指南

### 手動測試

#### 語言切換測試
1. 在不同頁面測試語言切換功能
2. 確認所有文字都正確翻譯
3. 檢查語言偏好是否正確儲存

#### 翻譯完整性測試
1. 瀏覽所有頁面和功能
2. 記錄任何未翻譯的文字
3. 檢查是否有顯示翻譯鍵而非翻譯內容的情況

### 自動化測試

#### 執行多語系測試套件
```bash
# 執行所有多語系測試
docker-compose exec app php artisan test --testsuite=Multilingual

# 執行特定的語言切換測試
docker-compose exec app php artisan test tests/Feature/MultilingualTest.php
```

#### 使用 Playwright 進行端到端測試
```bash
# 執行多語系 Playwright 測試
php execute-multilingual-login-tests.php
php run-comprehensive-multilingual-tests.php
```

## 故障排除

### 常見問題

#### 問題：語言切換後部分文字未翻譯
**可能原因：**
- 翻譯鍵不存在於目標語言檔案中
- 程式碼中使用了硬編碼文字
- 語言檔案快取未更新

**解決方案：**
1. 檢查語言檔案是否包含相關翻譯鍵
2. 清除語言快取：`php artisan cache:clear`
3. 執行語言檔案完整性檢查

#### 問題：語言偏好未正確儲存
**可能原因：**
- Session 配置問題
- 資料庫連線問題
- 中介軟體未正確執行

**解決方案：**
1. 檢查 Session 配置和儲存
2. 確認資料庫連線正常
3. 檢查 SetLocale 中介軟體是否正確註冊

#### 問題：首次訪問語言偵測不正確
**可能原因：**
- 瀏覽器語言標頭解析錯誤
- 支援語言清單配置問題

**解決方案：**
1. 檢查 `Accept-Language` 標頭解析邏輯
2. 確認 `config/app.php` 中的語言設定
3. 測試不同瀏覽器的語言偵測

### 除錯工具

#### 語言檔案驗證器
```bash
# 檢查語言檔案完整性
docker-compose exec app php artisan tinker
>>> app(App\Services\LanguageFileValidator::class)->validateCompleteness()
```

#### 翻譯鍵追蹤
```php
// 在 AppServiceProvider 中加入除錯程式碼
if (app()->environment('local')) {
    app('translator')->setFallback('key-not-found');
}
```

## 效能優化

### 語言檔案快取
- 語言檔案會自動快取以提高效能
- 修改語言檔案後需清除快取
- 生產環境建議預先編譯語言檔案

### 記憶體優化
- 只載入當前語言的翻譯檔案
- 使用延遲載入避免載入不必要的翻譯
- 定期清理過期的語言快取

### 網路優化
- 語言切換使用 AJAX 避免頁面重新載入
- 預載入常用語言的翻譯檔案
- 使用 CDN 快取語言資源

## 最佳實踐

### 翻譯內容
1. **保持一致性**：使用統一的術語和風格
2. **考慮文化差異**：適應不同文化的表達習慣
3. **簡潔明瞭**：避免過長或複雜的翻譯
4. **定期審核**：定期檢查和更新翻譯內容

### 程式碼實踐
1. **避免硬編碼**：所有顯示文字都應使用翻譯函數
2. **合理分組**：將相關翻譯組織在同一檔案中
3. **命名規範**：使用清晰的翻譯鍵命名
4. **參數化翻譯**：使用參數而非字串拼接

### 維護流程
1. **版本控制**：翻譯檔案納入版本控制
2. **審核流程**：建立翻譯內容審核機制
3. **測試覆蓋**：確保新功能包含多語系測試
4. **文檔更新**：及時更新相關文檔

這個指南提供了完整的多語系系統使用和維護資訊，幫助使用者、管理員和開發者有效地使用和維護多語系功能。