# 測試資料管理指南

## 重要提醒

⚠️ **在進行任何測試之前，必須先確保測試資料已正確建立！**

由於開發過程中經常需要重建資料庫或清除資料，導致測試時出現「User 帳號不存在」等問題。請務必遵循以下流程。

## 測試前必要步驟

### 1. 資料庫初始化流程

```bash
# 1. 確保 Docker 容器正在運行
docker-compose up -d

# 2. 執行資料庫遷移（如果需要重建）
docker-compose exec app php artisan migrate:fresh

# 3. 執行 Seeder 建立基礎測試資料
docker-compose exec app php artisan db:seed

# 4. 驗證資料是否建立成功
docker-compose exec app php artisan tinker
```

### 2. 驗證測試資料

在 Tinker 中執行以下命令確認資料：

```php
// 檢查使用者是否存在
User::count();
User::where('username', 'admin')->first();

// 檢查角色是否存在
Role::count();
Role::all()->pluck('name');

// 檢查權限是否存在
Permission::count();
```

## 標準測試資料結構

### 必須存在的基礎資料

#### 1. 管理員使用者
```php
// 預設管理員帳號
username: 'admin'
password: 'password123'
email: 'admin@example.com'
is_active: true
```

#### 2. 基本角色
```php
// 必須存在的角色
- 'super_admin' (超級管理員) - 擁有所有 36 個權限
- 'admin' (管理員) - 擁有所有 36 個權限 (避免權限不足問題)
- 'user' (一般使用者) - 擁有 3 個基本權限 (dashboard.view, profile.view, profile.edit)
```

#### 3. 系統權限（共 36 個）
```php
// 使用者管理權限 (5個)
- 'users.view' - 檢視使用者
- 'users.create' - 建立使用者
- 'users.edit' - 編輯使用者
- 'users.delete' - 刪除使用者
- 'users.assign_roles' - 指派使用者角色

// 角色管理權限 (5個)
- 'roles.view' - 檢視角色
- 'roles.create' - 建立角色
- 'roles.edit' - 編輯角色
- 'roles.delete' - 刪除角色
- 'roles.manage_permissions' - 管理角色權限

// 權限管理權限 (4個)
- 'permissions.view' - 檢視權限
- 'permissions.create' - 建立權限
- 'permissions.edit' - 編輯權限
- 'permissions.delete' - 刪除權限

// 儀表板權限 (2個)
- 'dashboard.view' - 檢視儀表板
- 'dashboard.stats' - 檢視統計資訊

// 系統管理權限 (3個)
- 'system.settings' - 系統設定
- 'system.logs' - 檢視系統日誌
- 'system.maintenance' - 系統維護

// 個人資料權限 (2個)
- 'profile.view' - 檢視個人資料
- 'profile.edit' - 編輯個人資料

// 活動日誌權限 (3個)
- 'activity_logs.view' - 檢視活動日誌
- 'activity_logs.export' - 匯出活動日誌
- 'activity_logs.delete' - 刪除活動日誌

// 通知權限 (5個)
- 'notifications.view' - 檢視通知
- 'notifications.create' - 建立通知
- 'notifications.edit' - 編輯通知
- 'notifications.delete' - 刪除通知
- 'notifications.send' - 發送通知

// 設定管理權限 (4個)
- 'settings.view' - 檢視設定
- 'settings.edit' - 編輯設定
- 'settings.backup' - 備份設定
- 'settings.reset' - 重置設定

// 安全管理權限 (3個)
- 'security.view' - 檢視安全資訊
- 'security.incidents' - 管理安全事件
- 'security.audit' - 安全稽核
```

## 自動化測試資料檢查

### 建立測試前檢查腳本

建議在每次測試前執行以下檢查：

```bash
#!/bin/bash
# test-data-check.sh

echo "檢查測試資料..."

# 檢查管理員使用者是否存在
ADMIN_EXISTS=$(docker-compose exec -T app php artisan tinker --execute="echo User::where('username', 'admin')->exists() ? 'true' : 'false';")

if [[ "$ADMIN_EXISTS" != *"true"* ]]; then
    echo "❌ 管理員使用者不存在，正在建立測試資料..."
    docker-compose exec app php artisan db:seed
    echo "✅ 測試資料建立完成"
else
    echo "✅ 測試資料已存在"
fi
```

## MCP 測試整合流程

### 使用 Playwright 和 MySQL MCP 的標準流程

#### 1. 測試開始前
```javascript
// 使用 MySQL MCP 檢查測試資料
const userCheck = await mysql.executeQuery({
    query: "SELECT COUNT(*) as count FROM users WHERE username = 'admin'",
    database: "laravel_admin"
});

if (userCheck.results[0].count === 0) {
    throw new Error("測試資料不存在，請先執行 db:seed");
}
```

#### 2. 登入測試
```javascript
// 確保有測試資料後再進行登入測試
await playwright.navigate('http://localhost/admin/login');
await playwright.fill('input[name="username"]', 'admin');
await playwright.fill('input[name="password"]', 'password123');
await playwright.click('button[type="submit"]');
```

#### 3. 測試後驗證
```javascript
// 驗證登入是否成功
const loginCheck = await mysql.executeQuery({
    query: "SELECT last_login_at FROM users WHERE username = 'admin'",
    database: "laravel_admin"
});
```

## 開發工作流程建議

### 每日開發流程
1. **啟動開發環境**
   ```bash
   docker-compose up -d
   ```

2. **檢查並建立測試資料**
   ```bash
   docker-compose exec app php artisan db:seed --class=TestDataSeeder
   ```

3. **開始開發和測試**
   - 使用 MCP 工具進行測試
   - 確保測試資料完整性

### 重建資料庫後的流程
1. **執行遷移**
   ```bash
   docker-compose exec app php artisan migrate:fresh
   ```

2. **重建測試資料**
   ```bash
   docker-compose exec app php artisan db:seed
   ```

3. **驗證資料完整性**
   ```bash
   docker-compose exec app php artisan tinker --execute="
   echo 'Users: ' . User::count();
   echo 'Roles: ' . Role::count();
   echo 'Permissions: ' . Permission::count();
   "
   ```

## 常見問題解決

### 問題：測試時出現「使用者不存在」錯誤
**解決方案：**
```bash
# 1. 檢查資料庫連線
docker-compose exec app php artisan migrate:status

# 2. 重新建立測試資料
docker-compose exec app php artisan db:seed

# 3. 驗證資料
docker-compose exec app php artisan tinker --execute="User::where('username', 'admin')->first()"
```

### 問題：角色或權限不存在
**解決方案：**
```bash
# 執行完整的 Seeder
docker-compose exec app php artisan db:seed --class=RoleSeeder
docker-compose exec app php artisan db:seed --class=PermissionSeeder
docker-compose exec app php artisan db:seed --class=UserSeeder
```

### 問題：測試資料不一致
**解決方案：**
```bash
# 完全重建資料庫和測試資料
docker-compose exec app php artisan migrate:fresh --seed
```

## 最佳實踐

1. **測試隔離**: 每個測試使用獨立的測試資料
2. **資料清理**: 測試後清理臨時建立的資料
3. **資料驗證**: 測試前後都要驗證資料狀態
4. **錯誤處理**: 適當處理測試資料不存在的情況
5. **文檔更新**: 保持測試資料結構文檔的更新
6. **權限同步**: ⚠️ **開發新功能時必須同步更新 PermissionSeeder，避免權限不足問題**

## 自動化建議

考慮在 `composer.json` 中加入測試前腳本：

```json
{
    "scripts": {
        "test-setup": [
            "docker-compose exec app php artisan migrate:fresh",
            "docker-compose exec app php artisan db:seed",
            "echo '測試資料準備完成'"
        ],
        "test": [
            "@test-setup",
            "docker-compose exec app php artisan test"
        ]
    }
}
```

遵循這些指南可以避免因測試資料不存在而導致的測試失敗，確保開發和測試流程的順暢進行。