# Seeders 目錄說明

## 核心 Seeders（保留）

### 1. DatabaseSeeder.php
- **用途**: 主要入口點，根據環境自動選擇適當的 seeder 策略
- **功能**: 
  - 生產環境：只建立必要的基礎資料
  - 開發環境：建立完整的測試資料

### 2. PermissionSeeder.php
- **用途**: 建立系統所有權限（共 49 個權限）
- **模組**: dashboard, users, roles, permissions, profile, activity_logs, notifications, settings, system, channels
- **特點**: 包含完整的通路管理系統權限

### 3. RoleSeeder.php
- **用途**: 建立系統角色並指派權限
- **角色**: 
  - admin（系統管理員）- 擁有所有權限
  - manager（部門主管）- 部分管理權限
  - user（一般使用者）- 基本權限

### 4. UserSeeder.php
- **用途**: 建立系統必要的預設管理員帳號
- **帳號**: admin / admin123
- **特點**: 確保系統部署後可以立即使用

### 5. SettingsSeeder.php
- **用途**: 建立系統設定
- **功能**: 載入預設的系統配置

### 6. CompleteTestDataSeeder.php
- **用途**: 一站式完整測試資料建立
- **功能**: 
  - 整合所有核心 seeders 的功能
  - 建立豐富的測試使用者
  - 建立完整的通路管理測試資料（代理、玩家、點數交易）
  - 提供完整的測試環境
- **適用**: 開發和測試環境

## 使用方式

### 標準部署（推薦）
```bash
# 使用主 seeder，自動根據環境選擇策略
docker-compose exec app php artisan db:seed
```

### 完整測試資料（開發用）
```bash
# 直接使用完整測試資料 seeder
docker-compose exec app php artisan db:seed --class=CompleteTestDataSeeder
```

### 重建資料庫
```bash
# 重建資料庫並執行 seeders
docker-compose exec app php artisan migrate:fresh --seed
```

## 測試帳號

### 管理員帳號
- **使用者名稱**: admin
- **密碼**: admin123
- **權限**: 所有權限（51個）
- **用途**: 系統管理和完整功能測試

### 測試帳號（CompleteTestDataSeeder）
- **部門主管**: manager / password123
- **一般使用者**: testuser / password123
- **停用使用者**: inactive_user / password123

### 通路管理測試資料（CompleteTestDataSeeder）

#### 代理結構
- **總代理A** (A_agent001)
  - 總點數: 1,000,000
  - 剩餘點數: 400,000
  - 下層代理: 代理A1, 代理A2
- **總代理B** (B_agent002)
  - 總點數: 800,000
  - 剩餘點數: 300,000
  - 下層代理: 代理B1, 代理B2（已停用）

#### 玩家資料
- **6個測試玩家**，分佈在不同代理下
- **總點數**: 200,000
- **包含啟用和停用狀態**的測試場景

#### 交易記錄
- **6筆測試交易**，包含：
  - 代理點數分配
  - 玩家點數分配
  - 玩家消費記錄
  - 系統調整記錄

## 📊 完整資料統計

### 系統核心資料
- **權限**: 51 個（涵蓋 10 個功能模組）
- **角色**: 3 個（admin, manager, user）
- **使用者**: 4 個（包含各種測試場景）
- **系統設定**: 完整的預設配置

### 通路管理資料
- **代理**: 6 個（2層級結構，包含啟用/停用狀態）
- **玩家**: 6 個（分佈在不同代理下，包含各種點數狀態）
- **交易記錄**: 6 筆（涵蓋所有交易類型）

### 權限模組分佈
- **activity_logs**: 3 個權限
- **channels**: 16 個權限（通路管理）
- **dashboard**: 2 個權限
- **notifications**: 5 個權限
- **permissions**: 4 個權限
- **profile**: 2 個權限
- **roles**: 5 個權限
- **settings**: 4 個權限
- **system**: 4 個權限
- **users**: 6 個權限

## 已移除的 Seeders

以下 seeders 已被移除，因為功能重複或不再需要：

- ActivityNotificationTemplateSeeder.php
- ActivityRetentionPolicySeeder.php
- ActivitySeeder.php
- ActivityTestSeeder.php
- AgentSeeder.php
- ChannelManagementTestSeeder.php
- ChannelSeeder.php
- DataIntegritySeeder.php
- DeploymentSeeder.php
- DevelopmentSeeder.php
- MonitorRuleSeeder.php
- NotificationRuleSeeder.php
- NotificationSeeder.php
- NotificationTemplateSeeder.php
- PermissionDependencySeeder.php
- PermissionTemplateSeeder.php
- PlayerSeeder.php
- PointTransactionSeeder.php
- ProductionSeeder.php
- UpdatePermissionTypesSeeder.php

## 測試驗證

### 自動化測試腳本
```bash
# 執行完整的 seeders 測試
./database/seeders/test-seeders.sh
```

測試腳本會驗證：
- DatabaseSeeder 正常執行
- CompleteTestDataSeeder 正常執行  
- 個別 seeders 正常執行
- 資料完整性檢查
- 管理員登入功能測試

### 手動驗證
```bash
# 檢查資料統計
docker-compose exec app php artisan tinker --execute="
echo 'Permissions: ' . App\Models\Permission::count();
echo 'Roles: ' . App\Models\Role::count(); 
echo 'Users: ' . App\Models\User::count();
"

# 檢查管理員帳號
docker-compose exec app php artisan tinker --execute="
\$admin = App\Models\User::where('username', 'admin')->first();
echo 'Admin: ' . \$admin->name . ' (' . \$admin->email . ')';
echo 'Roles: ' . \$admin->roles->pluck('display_name')->join(', ');
"
```

## 注意事項

1. **環境安全**: 生產環境會自動使用精簡的資料建立策略
2. **密碼安全**: 請在生產環境中立即修改預設密碼
3. **權限完整性**: 所有權限都在 PermissionSeeder 中統一管理
4. **測試隔離**: 使用 CompleteTestDataSeeder 可以快速重建完整測試環境
5. **自動化測試**: 使用提供的測試腳本確保 seeders 正常工作