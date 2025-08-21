# 系統設定配置系統使用指南

## 概述

系統設定配置系統提供了一個集中化的方式來管理應用程式的各種設定，包含分類組織、驗證規則、依賴關係和輸入元件映射。

## 配置檔案結構

### 主要配置檔案

- `config/system-settings.php` - 主要的系統設定配置檔案

### 配置檔案包含以下部分：

1. **設定分類** (`categories`) - 定義設定的分類和組織
2. **設定項目** (`settings`) - 定義所有設定項目的詳細配置
3. **輸入類型映射** (`input_types`) - 定義不同設定類型對應的輸入元件
4. **依賴關係** (`dependencies`) - 定義設定之間的依賴關係
5. **可測試設定** (`testable_settings`) - 定義哪些設定可以進行連線測試
6. **預覽設定** (`preview_settings`) - 定義哪些設定支援即時預覽

## 使用方法

### 1. 使用 SystemSettingsHelper

```php
use App\Helpers\SystemSettingsHelper;

// 取得所有分類
$categories = SystemSettingsHelper::getCategories();

// 取得指定分類的設定
$basicSettings = SystemSettingsHelper::getSettingsByCategory('basic');

// 取得設定配置
$config = SystemSettingsHelper::getSettingConfig('app.name');

// 取得預設值
$defaultValue = SystemSettingsHelper::getDefaultValue('app.name');

// 驗證設定值
$result = SystemSettingsHelper::validateSetting('app.name', 'My App');

// 檢查設定是否需要加密
$isEncrypted = SystemSettingsHelper::isEncrypted('notification.smtp_password');

// 取得設定的依賴關係
$dependencies = SystemSettingsHelper::getDependencies('notification.smtp_host');
```

### 2. 使用 Facade

```php
use SystemSettings;

// 與 Helper 相同的方法，但使用 Facade
$categories = SystemSettings::getCategories();
$config = SystemSettings::getSettingConfig('app.name');
```

### 3. 使用 Blade 指令

```blade
{{-- 取得設定值 --}}
@setting('app.name')

{{-- 取得設定預設值 --}}
@settingDefault('app.name')

{{-- 取得設定顯示值 --}}
@settingDisplay('security.force_https', true)

{{-- 條件判斷設定值 --}}
@setting('maintenance.maintenance_mode', true)
    <div class="maintenance-notice">系統維護中</div>
@endsetting

{{-- 判斷設定是否啟用 --}}
@settingEnabled('notification.email_enabled')
    <div class="email-notifications">郵件通知已啟用</div>
@endsettingEnabled

{{-- 判斷設定是否停用 --}}
@settingDisabled('maintenance.maintenance_mode')
    <div class="normal-content">正常內容</div>
@endsettingDisabled
```

## 新增設定項目

### 1. 在配置檔案中新增設定

```php
// config/system-settings.php
'settings' => [
    'my_new_setting' => [
        'category' => 'basic',
        'type' => 'text',
        'default' => 'default_value',
        'validation' => 'required|string|max:255',
        'description' => '我的新設定',
        'help' => '這是一個新的設定項目',
        'order' => 10,
    ],
],
```

### 2. 設定類型

支援的設定類型：

- `text` - 文字輸入
- `textarea` - 多行文字
- `number` - 數字輸入
- `email` - 電子郵件
- `password` - 密碼（會加密儲存）
- `boolean` - 布林值（開關）
- `select` - 下拉選單
- `color` - 顏色選擇器
- `file` - 檔案上傳

### 3. 驗證規則

使用 Laravel 的驗證規則語法：

```php
'validation' => 'required|string|max:100',
'validation' => 'required|integer|min:1|max:100',
'validation' => 'required|email',
'validation' => 'required|boolean',
```

### 4. 設定依賴關係

```php
// 方法 1：在設定項目中定義
'my_dependent_setting' => [
    'depends_on' => [
        'parent_setting' => true,
        'another_setting' => 'specific_value',
    ],
],

// 方法 2：在全域依賴中定義
'dependencies' => [
    'my_dependent_setting' => ['parent_setting'],
],
```

## 驗證配置

使用 Artisan 命令驗證配置的正確性：

```bash
# 基本驗證
php artisan settings:validate

# 顯示詳細摘要
php artisan settings:validate --summary

# 嘗試自動修復問題
php artisan settings:validate --fix
```

## 測試

執行單元測試確保配置系統正常工作：

```bash
php artisan test tests/Unit/SystemSettingsHelperTest.php
```

## 最佳實踐

### 1. 設定命名

- 使用點號分隔的命名空間：`category.setting_name`
- 使用描述性的名稱：`notification.smtp_host` 而非 `notif.host`

### 2. 分類組織

- 將相關設定歸類到同一分類
- 使用有意義的分類名稱和圖示
- 設定適當的排序順序

### 3. 驗證規則

- 為所有設定提供適當的驗證規則
- 使用 Laravel 標準驗證規則
- 考慮設定的實際使用場景

### 4. 依賴關係

- 明確定義設定之間的依賴關係
- 避免循環依賴
- 使用描述性的依賴條件

### 5. 安全性

- 標記敏感設定為加密：`'encrypted' => true`
- 使用適當的驗證規則防止惡意輸入
- 考慮設定變更的影響範圍

## 故障排除

### 常見問題

1. **設定無法載入**
   - 檢查配置檔案語法
   - 確認服務提供者已註冊
   - 清除配置快取：`php artisan config:clear`

2. **驗證失敗**
   - 檢查驗證規則語法
   - 確認依賴設定存在
   - 使用驗證命令檢查配置

3. **依賴關係錯誤**
   - 檢查循環依賴
   - 確認依賴的設定存在
   - 驗證依賴條件的邏輯

### 除錯工具

```php
// 檢查設定配置
$config = SystemSettingsHelper::getSettingConfig('setting.key');
dd($config);

// 檢查依賴關係
$deps = SystemSettingsHelper::getDependencies('setting.key');
dd($deps);

// 測試驗證
$result = SystemSettingsHelper::validateSetting('setting.key', 'test_value');
dd($result);
```

這個配置系統為後續的設定管理功能提供了堅實的基礎，確保所有設定都有適當的驗證、依賴檢查和類型安全。