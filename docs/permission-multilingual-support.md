# 權限管理多語言支援文件
# Permission Management Multilingual Support Documentation

## 概述 / Overview

本文件說明權限管理系統的多語言支援實作，包含語言檔案結構、使用方法和最佳實踐。

This document explains the multilingual support implementation for the permission management system, including language file structure, usage methods, and best practices.

## 語言檔案結構 / Language File Structure

### 主要語言檔案 / Main Language Files

```
lang/
├── en/                          # 英文語言檔案 / English language files
│   ├── permissions.php          # 主要權限翻譯 / Main permission translations
│   ├── permission_errors.php    # 錯誤訊息 / Error messages
│   ├── permission_messages.php  # 成功訊息 / Success messages
│   ├── permission_validation.php # 驗證訊息 / Validation messages
│   ├── permission_ui.php        # UI 元件翻譯 / UI component translations
│   └── layout.php              # 佈局相關翻譯 / Layout translations
└── zh_TW/                      # 繁體中文語言檔案 / Traditional Chinese files
    ├── permissions.php          # 主要權限翻譯 / Main permission translations
    ├── permission_errors.php    # 錯誤訊息 / Error messages
    ├── permission_messages.php  # 成功訊息 / Success messages
    ├── permission_validation.php # 驗證訊息 / Validation messages
    ├── permission_ui.php        # UI 元件翻譯 / UI component translations
    └── layout.php              # 佈局相關翻譯 / Layout translations
```

### 語言檔案內容分類 / Language File Content Categories

#### 1. permissions.php - 主要翻譯檔案 / Main Translation File
- 頁面標題和標頭 / Page titles and headings
- 導航和選單項目 / Navigation and menu items
- 表單標籤和佔位符 / Form labels and placeholders
- 權限類型和模組 / Permission types and modules
- 表格標頭 / Table headers
- 操作按鈕 / Action buttons
- 搜尋和篩選 / Search and filters

#### 2. permission_errors.php - 錯誤訊息檔案 / Error Messages File
- CRUD 操作錯誤 / CRUD operation errors
- 驗證錯誤 / Validation errors
- 授權錯誤 / Authorization errors
- 系統錯誤 / System errors
- 依賴關係錯誤 / Dependency errors

#### 3. permission_messages.php - 成功訊息檔案 / Success Messages File
- 操作成功訊息 / Operation success messages
- 通知訊息 / Notification messages
- 系統狀態訊息 / System status messages

#### 4. permission_validation.php - 驗證訊息檔案 / Validation Messages File
- 表單驗證規則訊息 / Form validation rule messages
- 自定義驗證訊息 / Custom validation messages
- 批量操作驗證 / Bulk operation validation

#### 5. permission_ui.php - UI 元件翻譯檔案 / UI Component Translation File
- 元件標題 / Component titles
- 按鈕和操作 / Buttons and actions
- 狀態指示器 / Status indicators
- 工具提示 / Tooltips
- 無障礙標籤 / Accessibility labels

## 使用方法 / Usage Methods

### 1. 在 Blade 模板中使用 / Using in Blade Templates

#### 基本翻譯函數 / Basic Translation Functions
```blade
{{-- 基本翻譯 / Basic translation --}}
{{ __('permissions.titles.permission_management') }}

{{-- 帶參數的翻譯 / Translation with parameters --}}
{{ __('permission_messages.crud.created', ['name' => $permission->name]) }}

{{-- 指定語言的翻譯 / Translation with specific locale --}}
{{ __('permissions.actions.create', [], 'zh_TW') }}
```

#### 自定義 Blade 指令 / Custom Blade Directives
```blade
{{-- 權限翻譯指令 / Permission translation directive --}}
@permission('titles.permission_management')

{{-- 權限錯誤訊息指令 / Permission error message directive --}}
@permissionError('validation.name_required')

{{-- 權限成功訊息指令 / Permission success message directive --}}
@permissionMessage('crud.created', ['name' => 'users.create'])

{{-- 權限 UI 翻譯指令 / Permission UI translation directive --}}
@permissionUI('buttons.create_new')

{{-- 權限類型翻譯指令 / Permission type translation directive --}}
@permissionType('create')

{{-- 模組翻譯指令 / Module translation directive --}}
@permissionModule('users')

{{-- 狀態翻譯指令 / Status translation directive --}}
@permissionStatus('active')

{{-- 日期時間格式化指令 / DateTime formatting directive --}}
@permissionDateTime($permission->created_at)

{{-- 數字格式化指令 / Number formatting directive --}}
@permissionNumber($count)
```

### 2. 在 Livewire 元件中使用 / Using in Livewire Components

```php
<?php

namespace App\Livewire\Admin\Permissions;

use Livewire\Component;
use App\Helpers\PermissionLanguageHelper;

class PermissionList extends Component
{
    public function render()
    {
        return view('livewire.admin.permissions.permission-list', [
            'title' => PermissionLanguageHelper::permission('titles.permission_list'),
            'createButton' => PermissionLanguageHelper::ui('buttons.create_new'),
            'permissionTypes' => PermissionLanguageHelper::getAllTypes(),
            'permissionModules' => PermissionLanguageHelper::getAllModules(),
        ]);
    }

    public function createPermission()
    {
        // 創建權限邏輯 / Create permission logic
        
        session()->flash('success', 
            PermissionLanguageHelper::message('crud.created', ['name' => $name])
        );
    }

    public function deletePermission($id)
    {
        try {
            // 刪除邏輯 / Delete logic
            
            session()->flash('success', 
                PermissionLanguageHelper::message('crud.deleted', ['name' => $name])
            );
        } catch (\Exception $e) {
            session()->flash('error', 
                PermissionLanguageHelper::error('crud.permission_deletion_failed')
            );
        }
    }
}
```

### 3. 在控制器中使用 / Using in Controllers

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\PermissionLanguageHelper;

class PermissionController extends Controller
{
    public function index()
    {
        return view('admin.permissions.index', [
            'pageTitle' => PermissionLanguageHelper::permission('titles.permission_list'),
            'breadcrumbs' => [
                PermissionLanguageHelper::ui('breadcrumbs.home'),
                PermissionLanguageHelper::ui('breadcrumbs.admin'),
                PermissionLanguageHelper::ui('breadcrumbs.permissions'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        // 驗證和儲存邏輯 / Validation and storage logic
        
        return redirect()->route('admin.permissions.index')
            ->with('success', PermissionLanguageHelper::message('crud.created', [
                'name' => $permission->name
            ]));
    }
}
```

### 4. 在 JavaScript 中使用 / Using in JavaScript

```javascript
// 在 Blade 模板中傳遞翻譯到 JavaScript
// Pass translations to JavaScript in Blade templates
<script>
window.permissionTranslations = {
    confirmDelete: '@permissionUI("modals.confirm_delete")',
    deleteWarning: '@permission("confirmations.delete_warning")',
    processing: '@permissionUI("progress.processing")',
    completed: '@permissionUI("status_indicators.completed")',
    failed: '@permissionUI("status_indicators.failed")'
};
</script>

// 在 JavaScript 中使用翻譯
// Use translations in JavaScript
function confirmDeletePermission(permissionName) {
    if (confirm(window.permissionTranslations.confirmDelete + ': ' + permissionName)) {
        showProcessingMessage();
        // 執行刪除邏輯 / Execute delete logic
    }
}

function showProcessingMessage() {
    showNotification(window.permissionTranslations.processing);
}
```

## 語言助手類別 / Language Helper Class

### PermissionLanguageHelper 類別方法 / PermissionLanguageHelper Class Methods

```php
// 基本翻譯方法 / Basic translation methods
PermissionLanguageHelper::trans('key', $replace, $locale);
PermissionLanguageHelper::permission('key', $replace, $locale);
PermissionLanguageHelper::error('key', $replace, $locale);
PermissionLanguageHelper::message('key', $replace, $locale);
PermissionLanguageHelper::validation('key', $replace, $locale);
PermissionLanguageHelper::ui('key', $replace, $locale);

// 特定類型翻譯方法 / Specific type translation methods
PermissionLanguageHelper::type('create', $locale);
PermissionLanguageHelper::module('users', $locale);
PermissionLanguageHelper::status('active', $locale);

// 批量取得方法 / Bulk retrieval methods
PermissionLanguageHelper::getAllTypes($locale);
PermissionLanguageHelper::getAllModules($locale);
PermissionLanguageHelper::getAllStatuses($locale);

// 工具方法 / Utility methods
PermissionLanguageHelper::isChineseLocale();
PermissionLanguageHelper::formatDateTime($datetime, $locale);
PermissionLanguageHelper::formatNumber($number, $locale);
```

## 最佳實踐 / Best Practices

### 1. 翻譯鍵值命名規範 / Translation Key Naming Conventions

```php
// 使用點號分隔的階層結構 / Use dot-separated hierarchical structure
'titles.permission_management'
'form.name_placeholder'
'table.created_at'
'actions.create'

// 使用描述性的鍵值名稱 / Use descriptive key names
'buttons.create_new'          // 好 / Good
'btn1'                        // 不好 / Bad

'messages.created_successfully' // 好 / Good
'msg1'                         // 不好 / Bad
```

### 2. 參數化翻譯 / Parameterized Translations

```php
// 使用命名參數 / Use named parameters
'crud.created' => '權限「:name」已成功建立。',
'bulk.partial_success' => '批量操作完成：成功 :success 個，失敗 :failed 個。',

// 在程式碼中使用 / Use in code
PermissionLanguageHelper::message('crud.created', ['name' => $permission->name]);
PermissionLanguageHelper::message('bulk.partial_success', [
    'success' => $successCount,
    'failed' => $failedCount
]);
```

### 3. 條件式翻譯 / Conditional Translations

```php
// 根據語言環境選擇不同的格式 / Choose different formats based on locale
public function getFormattedDate($date)
{
    if (PermissionLanguageHelper::isChineseLocale()) {
        return $date->format('Y年m月d日');
    }
    
    return $date->format('M d, Y');
}
```

### 4. 快取翻譯 / Caching Translations

```php
// 在視圖組合器中快取常用翻譯 / Cache common translations in view composers
view()->composer('admin.permissions.*', function ($view) {
    $view->with([
        'permissionTypes' => Cache::remember('permission_types_' . app()->getLocale(), 3600, function () {
            return PermissionLanguageHelper::getAllTypes();
        }),
    ]);
});
```

## 新增語言支援 / Adding New Language Support

### 1. 建立新語言目錄 / Create New Language Directory

```bash
mkdir lang/ja  # 日文 / Japanese
mkdir lang/ko  # 韓文 / Korean
mkdir lang/fr  # 法文 / French
```

### 2. 複製並翻譯語言檔案 / Copy and Translate Language Files

```bash
# 複製英文檔案作為基礎 / Copy English files as base
cp lang/en/permissions.php lang/ja/permissions.php
cp lang/en/permission_errors.php lang/ja/permission_errors.php
# ... 其他檔案 / ... other files

# 然後翻譯內容 / Then translate content
```

### 3. 更新語言助手 / Update Language Helper

```php
// 在 PermissionLanguageHelper 中新增語言檢查
// Add language check in PermissionLanguageHelper
public static function isJapaneseLocale(): bool
{
    return in_array(App::getLocale(), ['ja', 'ja_JP']);
}
```

## 測試多語言功能 / Testing Multilingual Features

### 1. 單元測試 / Unit Tests

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Helpers\PermissionLanguageHelper;

class PermissionLanguageTest extends TestCase
{
    public function test_english_translations()
    {
        app()->setLocale('en');
        
        $this->assertEquals(
            'Permission Management',
            PermissionLanguageHelper::permission('titles.permission_management')
        );
    }

    public function test_chinese_translations()
    {
        app()->setLocale('zh_TW');
        
        $this->assertEquals(
            '權限管理',
            PermissionLanguageHelper::permission('titles.permission_management')
        );
    }

    public function test_parameterized_translations()
    {
        app()->setLocale('zh_TW');
        
        $message = PermissionLanguageHelper::message('crud.created', ['name' => 'test.permission']);
        
        $this->assertStringContains('test.permission', $message);
        $this->assertStringContains('已成功建立', $message);
    }
}
```

### 2. 功能測試 / Feature Tests

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class PermissionMultilingualTest extends TestCase
{
    public function test_permission_list_displays_in_chinese()
    {
        app()->setLocale('zh_TW');
        
        $response = $this->get('/admin/permissions');
        
        $response->assertSee('權限管理');
        $response->assertSee('建立權限');
    }

    public function test_permission_list_displays_in_english()
    {
        app()->setLocale('en');
        
        $response = $this->get('/admin/permissions');
        
        $response->assertSee('Permission Management');
        $response->assertSee('Create Permission');
    }
}
```

## 故障排除 / Troubleshooting

### 常見問題 / Common Issues

1. **翻譯不顯示 / Translations Not Showing**
   - 檢查語言檔案路徑是否正確 / Check language file paths
   - 確認語言環境設定 / Verify locale settings
   - 清除快取 / Clear cache: `php artisan config:clear`

2. **參數替換不工作 / Parameter Replacement Not Working**
   - 檢查參數名稱是否正確 / Check parameter names
   - 確認使用冒號語法 `:parameter` / Ensure colon syntax `:parameter`

3. **自定義 Blade 指令不工作 / Custom Blade Directives Not Working**
   - 確認服務提供者已註冊 / Ensure service provider is registered
   - 清除視圖快取 / Clear view cache: `php artisan view:clear`

### 除錯技巧 / Debugging Tips

```php
// 檢查當前語言環境 / Check current locale
dd(app()->getLocale());

// 檢查翻譯是否存在 / Check if translation exists
dd(__('permissions.titles.permission_management'));

// 檢查所有可用翻譯 / Check all available translations
dd(trans('permissions'));
```

## 結論 / Conclusion

本多語言支援系統提供了完整的權限管理翻譯解決方案，支援英文和繁體中文，並可輕鬆擴展到其他語言。透過結構化的語言檔案、助手類別和自定義 Blade 指令，開發者可以輕鬆實作多語言功能。

This multilingual support system provides a complete translation solution for permission management, supporting English and Traditional Chinese, with easy extensibility to other languages. Through structured language files, helper classes, and custom Blade directives, developers can easily implement multilingual features.