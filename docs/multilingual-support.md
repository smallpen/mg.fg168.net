# 多語言支援文檔

## 概述

本系統實作了完整的多語言支援功能，支援正體中文（zh_TW）和英文（en）兩種語言。

## 功能特色

### 1. 介面文字本地化
- 所有使用者介面文字都支援多語言
- 包含按鈕、標籤、訊息、錯誤提示等
- 使用 Laravel 的語言檔案系統

### 2. 日期時間格式本地化
- 根據語言自動調整日期時間顯示格式
- 支援相對時間顯示（如：1小時前、1 hour ago）
- 整合 Carbon 本地化功能

### 3. 狀態和角色名稱本地化
- 使用者狀態（啟用/停用）本地化
- 角色名稱和描述本地化
- 支援動態翻譯

### 4. 語言切換功能
- 提供語言選擇器元件
- 支援即時語言切換
- 記住使用者語言偏好

## 技術實作

### 語言檔案結構
```
resources/lang/
├── zh_TW/
│   ├── admin.php      # 管理介面翻譯
│   ├── auth.php       # 認證相關翻譯
│   ├── layout.php     # 佈局翻譯
│   └── validation.php # 驗證訊息翻譯
└── en/
    ├── admin.php
    ├── auth.php
    ├── layout.php
    └── validation.php
```

### 核心元件

#### 1. SetLocale 中介軟體
- 自動偵測使用者語言偏好
- 支援 URL 參數、Session、使用者設定、瀏覽器語言
- 設定 Carbon 本地化

#### 2. DateTimeHelper 輔助類別
```php
use App\Helpers\DateTimeHelper;

// 基本格式化
DateTimeHelper::format($datetime);

// 指定格式
DateTimeHelper::format($datetime, 'short');
DateTimeHelper::format($datetime, 'relative');
```

#### 3. LanguageSelector Livewire 元件
```php
// 切換語言
$this->switchLanguage('en');

// 取得支援的語言
$this->supportedLocales
```

#### 4. Blade 指令
```blade
{{-- 日期時間格式化 --}}
@datetime($user->created_at)

{{-- 相對時間 --}}
@timeago($user->updated_at)

{{-- 僅日期 --}}
@dateonly($user->created_at)

{{-- 狀態本地化 --}}
@status($user->is_active)

{{-- 角色名稱本地化 --}}
@rolename($role)
```

### 模型本地化屬性

#### User 模型
```php
// 格式化的建立時間
$user->formatted_created_at

// 相對時間
$user->created_at_relative

// 本地化狀態
$user->localized_status
```

#### Role 模型
```php
// 本地化角色名稱
$role->localized_display_name

// 本地化角色描述
$role->localized_description
```

## 使用方式

### 1. 在 Blade 模板中使用翻譯
```blade
{{-- 基本翻譯 --}}
{{ __('admin.users.title') }}

{{-- 帶參數的翻譯 --}}
{{ __('admin.users.user_deleted_permanently', ['username' => $user->username]) }}

{{-- 複數形式 --}}
{{ trans_choice('admin.users.users_count', $count) }}
```

### 2. 在 PHP 程式碼中使用翻譯
```php
// 基本翻譯
$message = __('admin.users.create');

// 帶參數的翻譯
$message = __('admin.users.user_activated', ['name' => $user->name]);

// 檢查翻譯是否存在
if (Lang::has('admin.users.custom_message')) {
    $message = __('admin.users.custom_message');
}
```

### 3. 在 JavaScript 中使用語言切換
```javascript
// 使用全域語言切換器
window.languageSwitcher.switchLanguage('en');

// 取得目前語言
const currentLocale = window.languageSwitcher.getCurrentLocale();

// 檢查語言支援
const isSupported = window.languageSwitcher.isLocaleSupported('zh_TW');
```

### 4. 新增新的翻譯
1. 在 `resources/lang/zh_TW/admin.php` 中新增正體中文翻譯
2. 在 `resources/lang/en/admin.php` 中新增對應的英文翻譯
3. 在程式碼中使用 `__('admin.new_key')` 呼叫

### 5. 新增新的角色本地化
```php
// 在語言檔案中新增
'roles' => [
    'names' => [
        'new_role' => '新角色名稱',
    ],
    'descriptions' => [
        'new_role' => '新角色的描述',
    ],
],
```

## 設定選項

### 應用程式設定 (config/app.php)
```php
'locale' => 'zh_TW',           // 預設語言
'fallback_locale' => 'en',     // 備用語言
'timezone' => 'Asia/Taipei',   // 時區設定
```

### 支援的語言
- `zh_TW`: 正體中文
- `en`: 英文

## 測試

執行多語言支援測試：
```bash
docker-compose exec app php artisan test tests/Feature/MultilingualSupportTest.php
```

## 最佳實踐

### 1. 翻譯鍵命名規範
- 使用點號分隔的階層結構
- 使用描述性的鍵名
- 保持一致的命名風格

### 2. 翻譯內容指南
- 保持翻譯的一致性
- 考慮文化差異
- 使用適當的敬語和語調

### 3. 效能考量
- 語言檔案會被快取
- 避免在迴圈中重複呼叫翻譯函數
- 使用計算屬性快取翻譯結果

### 4. 維護建議
- 定期檢查翻譯的完整性
- 使用翻譯管理工具
- 建立翻譯審核流程

## 故障排除

### 常見問題

1. **翻譯不顯示**
   - 檢查語言檔案是否存在
   - 確認翻譯鍵是否正確
   - 清除語言快取：`php artisan cache:clear`

2. **日期格式不正確**
   - 檢查 Carbon 語言包是否安裝
   - 確認時區設定是否正確
   - 檢查 DateTimeHelper 的格式定義

3. **語言切換不生效**
   - 檢查 SetLocale 中介軟體是否註冊
   - 確認 session 設定是否正確
   - 檢查使用者權限設定

## 擴展支援

### 新增新語言
1. 建立新的語言目錄：`resources/lang/新語言代碼/`
2. 複製現有語言檔案並翻譯
3. 更新 SetLocale 中介軟體的支援語言列表
4. 更新 LanguageSelector 元件的語言選項
5. 新增對應的 Carbon 語言對應

### 整合第三方翻譯服務
- 可以整合 Google Translate API
- 支援自動翻譯建議
- 實作翻譯記憶庫功能