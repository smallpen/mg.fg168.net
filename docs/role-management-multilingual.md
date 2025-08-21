# 角色管理多語言支援文件

## 概述

角色管理系統提供完整的多語言支援，包括角色名稱、權限名稱、模組名稱、錯誤訊息和成功訊息的本地化。

## 支援的語言

- **英文 (en)**: 預設語言
- **正體中文 (zh_TW)**: 繁體中文支援

## 語言檔案結構

```
lang/
├── en/
│   ├── role_management.php    # 角色管理主要翻譯
│   └── role_errors.php        # 錯誤訊息翻譯
└── zh_TW/
    ├── role_management.php    # 角色管理主要翻譯
    └── role_errors.php        # 錯誤訊息翻譯
```

## 使用方法

### 1. 使用 RoleLocalizationHelper 類別

```php
use App\Helpers\RoleLocalizationHelper;

// 取得權限顯示名稱
$permissionName = RoleLocalizationHelper::getPermissionDisplayName('roles.view');
// 英文: "View Roles"
// 中文: "檢視角色"

// 取得角色顯示名稱
$roleName = RoleLocalizationHelper::getRoleDisplayName('admin');
// 英文: "Administrator"
// 中文: "管理員"

// 取得模組顯示名稱
$moduleName = RoleLocalizationHelper::getModuleDisplayName('roles');
// 英文: "Role Management"
// 中文: "角色管理"

// 取得錯誤訊息
$errorMessage = RoleLocalizationHelper::getErrorMessage('crud.role_not_found');
// 英文: "The specified role was not found"
// 中文: "找不到指定的角色"

// 取得成功訊息
$successMessage = RoleLocalizationHelper::getSuccessMessage('created', ['name' => 'Test Role']);
// 英文: "Role "Test Role" has been successfully created"
// 中文: "角色 "Test Role" 已成功建立"
```

### 2. 使用 Blade 指令

```blade
{{-- 角色名稱 --}}
@roleDisplayName('admin')

{{-- 角色描述 --}}
@roleDescription('admin')

{{-- 權限名稱 --}}
@permissionDisplayName('roles.view')

{{-- 權限描述 --}}
@permissionDescription('roles.view')

{{-- 模組名稱 --}}
@moduleDisplayName('roles')

{{-- 錯誤訊息 --}}
@roleErrorMessage('crud.role_not_found')

{{-- 成功訊息 --}}
@roleSuccessMessage('created', ['name' => $role->name])

{{-- 本地化日期 --}}
@localizedDate($role->created_at)
```

### 3. 使用 Blade 元件

```blade
{{-- 顯示角色名稱 --}}
<x-role-localization type="role" name="admin" />

{{-- 顯示角色名稱和描述 --}}
<x-role-localization type="role" name="admin" :show-description="true" />

{{-- 顯示權限名稱 --}}
<x-role-localization type="permission" name="roles.view" />

{{-- 顯示模組名稱 --}}
<x-role-localization type="module" name="roles" />
```

### 4. 在 Livewire 元件中使用

```php
<?php

namespace App\Livewire\Admin\Roles;

use Livewire\Component;
use App\Helpers\RoleLocalizationHelper;

class RoleList extends Component
{
    public function getLocalizedPermissionName($permissionName)
    {
        return RoleLocalizationHelper::getPermissionDisplayName($permissionName);
    }
    
    public function showSuccessMessage($messageKey, $parameters = [])
    {
        $message = RoleLocalizationHelper::getSuccessMessage($messageKey, $parameters);
        session()->flash('success', $message);
    }
    
    public function showErrorMessage($errorKey, $parameters = [])
    {
        $message = RoleLocalizationHelper::getErrorMessage($errorKey, $parameters);
        session()->flash('error', $message);
    }
}
```

### 5. 前端 JavaScript 支援

```javascript
// 載入角色本地化模組
import RoleLocalization from '/js/role-localization.js';

const roleLocalization = new RoleLocalization();

// 取得本地化名稱
const permissionName = roleLocalization.getPermissionDisplayName('roles.view');
const roleName = roleLocalization.getRoleDisplayName('admin');
const moduleName = roleLocalization.getModuleDisplayName('roles');

// 取得訊息
const errorMessage = roleLocalization.getErrorMessage('crud.role_not_found');
const successMessage = roleLocalization.getSuccessMessage('created', {name: 'Test Role'});

// 更新頁面元素
roleLocalization.updatePageElements();
```

## API 端點

### 取得所有翻譯資料

```http
GET /api/role-translations
```

回應範例：
```json
{
  "permission_names": {
    "roles.view": "View Roles",
    "roles.create": "Create Roles"
  },
  "role_names": {
    "admin": "Administrator",
    "user": "User"
  },
  "modules": {
    "roles": "Role Management",
    "users": "User Management"
  },
  "messages": {
    "created": "Role \":name\" has been successfully created"
  },
  "errors": {
    "crud": {
      "role_not_found": "The specified role was not found"
    }
  }
}
```

### 取得特定類型的翻譯

```http
GET /api/role-translations/{type}
```

支援的類型：
- `permissions`: 權限相關翻譯
- `roles`: 角色相關翻譯
- `modules`: 模組相關翻譯
- `errors`: 錯誤訊息翻譯
- `messages`: 成功訊息翻譯

## 語言切換

### 1. URL 參數方式

```
/admin/roles?locale=zh_TW
/admin/roles?locale=en
```

### 2. 中介軟體自動偵測

系統會按以下優先順序決定語言：
1. URL 參數中的 `locale`
2. Session 中儲存的語言偏好
3. 使用者設定的偏好語言
4. 瀏覽器 Accept-Language 標頭
5. 應用程式預設語言

### 3. 程式化切換

```php
// 設定語言
App::setLocale('zh_TW');

// 檢查是否為中文環境
if (RoleLocalizationHelper::isChineseLocale()) {
    // 中文特定邏輯
}
```

## 新增翻譯

### 1. 新增權限翻譯

在 `lang/{locale}/role_management.php` 中的 `permission_names` 陣列新增：

```php
'permission_names' => [
    'new_permission.action' => 'New Permission Action',
    // ...
],

'permission_descriptions' => [
    'new_permission.action' => 'Description of the new permission',
    // ...
],
```

### 2. 新增角色翻譯

```php
'role_names' => [
    'new_role' => 'New Role Name',
    // ...
],

'role_descriptions' => [
    'new_role' => 'Description of the new role',
    // ...
],
```

### 3. 新增錯誤訊息

在 `lang/{locale}/role_errors.php` 中新增：

```php
'new_category' => [
    'new_error' => 'New error message',
    // ...
],
```

## 快取管理

### 清除翻譯快取

```http
DELETE /api/role-translations/cache
```

或使用 Artisan 命令：

```bash
php artisan cache:clear
```

## 最佳實踐

### 1. 一致性

- 使用一致的命名慣例
- 保持翻譯的語調和風格統一
- 確保所有語言版本的完整性

### 2. 效能

- 翻譯資料會自動快取
- 避免在迴圈中重複呼叫翻譯函數
- 使用批量翻譯方法處理大量資料

### 3. 維護

- 定期檢查翻譯的準確性
- 新增功能時同時更新所有語言版本
- 使用版本控制追蹤翻譯變更

### 4. 測試

- 為每種語言編寫測試
- 測試語言切換功能
- 驗證參數替換功能

## 故障排除

### 常見問題

1. **翻譯不顯示**
   - 檢查語言檔案是否存在
   - 確認檔案語法正確
   - 清除快取

2. **語言切換無效**
   - 檢查中介軟體是否正確註冊
   - 確認路由配置
   - 檢查 Session 設定

3. **參數替換失敗**
   - 確認參數名稱正確
   - 檢查參數格式（使用 `:parameter` 格式）
   - 驗證傳入的參數陣列

### 除錯工具

```php
// 檢查目前語言
echo App::getLocale();

// 檢查翻譯檔案是否載入
var_dump(RoleLocalizationHelper::getAllPermissionNames());

// 測試特定翻譯
echo RoleLocalizationHelper::getPermissionDisplayName('roles.view');
```

## 範例專案

參考 `resources/views/examples/role-localization-example.blade.php` 檔案查看完整的使用範例。