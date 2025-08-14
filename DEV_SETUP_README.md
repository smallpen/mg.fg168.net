# 開發環境測試資料快速設定指南

## 🚀 概述

本專案提供了多種便捷的方式來快速建立和管理開發環境的測試資料，解決開發過程中測試資料經常被清除的問題。

## 📋 可用工具

### 1. Artisan 命令
- `php artisan dev:setup` - 建立/更新開發測試資料
- `php artisan dev:check` - 檢查當前資料狀態

### 2. Shell 腳本
- `./dev-setup.sh` - 便捷的 shell 腳本

### 3. Makefile 命令
- `make setup` - 建立開發資料
- `make check` - 檢查資料狀態
- `make help` - 查看所有可用命令

## 🎯 快速開始

### 方法 1：使用 Artisan 命令（推薦）

```bash
# 建立/更新開發資料（保留現有資料）
docker-compose exec app php artisan dev:setup

# 完全重建資料庫
docker-compose exec app php artisan dev:setup --fresh --force

# 只重建使用者資料
docker-compose exec app php artisan dev:setup --users-only --force

# 檢查資料狀態
docker-compose exec app php artisan dev:check
```

### 方法 2：使用 Shell 腳本

```bash
# 建立開發資料
./dev-setup.sh

# 完全重建
./dev-setup.sh --fresh --force

# 只重建使用者
./dev-setup.sh --users-only

# 檢查資料狀態
./dev-setup.sh --check
```

### 方法 3：使用 Makefile

```bash
# 建立開發資料
make setup

# 完全重建
make setup-fresh

# 只重建使用者
make setup-users

# 檢查資料狀態
make check

# 查看所有命令
make help
```

## 👥 測試帳號

### 管理員帳號
- **超級管理員**: `superadmin` / `password123`
- **系統管理員**: `admin` / `password123`
- **部門經理**: `manager` / `password123`

### 一般使用者
- **啟用使用者**: `active_user` / `password123`
- **停用使用者**: `inactive_user` / `password123` (已停用)
- **內容編輯**: `editor` / `password123`

### 多語言測試
- **John Doe**: `john_doe` / `password123` (英文介面)
- **王小明**: `wang_ming` / `password123` (中文介面)
- **李小華**: `li_hua` / `password123` (中文介面)

### 特殊測試帳號
- **多重角色**: `multi_role` / `password123` (擁有多個角色)
- **無角色**: `no_role` / `password123` (沒有任何角色)

## 🔍 測試功能

### 搜尋測試
可以搜尋以下關鍵字來測試搜尋功能：
- `john`, `jane`, `bob` (英文名稱)
- `王`, `李`, `陳` (中文姓氏)
- `admin`, `user`, `manager` (使用者名稱)
- `example.com` (電子郵件)

### 篩選測試
- **狀態篩選**: 啟用/停用使用者
- **角色篩選**: 超級管理員/管理員/一般使用者

### 分頁測試
- 總共 14 個使用者，可測試分頁功能
- 每頁顯示 15 筆，剛好一頁顯示完

### 批量操作測試
- 選擇多個使用者進行批量啟用/停用
- 測試全選功能

## 🛠️ 常用開發工作流程

### 日常開發
```bash
# 1. 啟動 Docker（如果還沒啟動）
docker-compose up -d

# 2. 建立測試資料
make setup

# 3. 開始開發...

# 4. 如果資料被清除，快速重建
make setup-users
```

### 功能測試
```bash
# 1. 完全重建環境
make setup-fresh

# 2. 執行測試
make test

# 3. 檢查資料狀態
make check
```

### 問題排查
```bash
# 檢查資料狀態
make check

# 查看詳細資訊
docker-compose exec app php artisan dev:check --detailed

# 進入 Tinker 手動檢查
make tinker
>>> User::count()
>>> User::with('roles')->get()
```

## 📊 資料統計

建立的測試資料包含：
- **14 個使用者** (11 個啟用，3 個停用)
- **3 個角色** (超級管理員、管理員、一般使用者)
- **21 個權限** (涵蓋各個功能模組)
- **多種語言設定** (中文、英文)
- **不同主題偏好** (亮色、暗色)

## 🔧 自訂設定

### 修改測試資料
編輯 `database/seeders/DevelopmentSeeder.php` 來自訂測試資料：

```php
// 新增自訂使用者
[
    'username' => 'custom_user',
    'name' => '自訂使用者',
    'email' => 'custom@example.com',
    'password' => Hash::make('password123'),
    'theme_preference' => 'light',
    'locale' => 'zh_TW',
    'is_active' => true,
    'roles' => ['user']
],
```

### 新增快速命令
在 `Makefile` 中新增自訂命令：

```makefile
my-setup: ## 我的自訂設定
	@echo "🎯 執行自訂設定..."
	@docker-compose exec app php artisan dev:setup --users-only --force
	@docker-compose exec app php artisan cache:clear
```

## 🚨 注意事項

1. **僅限開發環境**: 這些工具只能在開發環境中使用，生產環境會被阻止
2. **密碼安全**: 所有測試帳號都使用預設密碼，生產環境請務必修改
3. **資料備份**: `--fresh` 選項會清空整個資料庫，請謹慎使用
4. **Docker 依賴**: 所有命令都需要 Docker 容器正在運行

## 🆘 故障排除

### 常見問題

**Q: 命令執行失敗，提示找不到類別**
```bash
# 清除快取後重試
make clean
make setup
```

**Q: Docker 容器沒有運行**
```bash
# 啟動容器
make docker-up
# 或
docker-compose up -d
```

**Q: 資料沒有正確建立**
```bash
# 檢查資料狀態
make check
# 完全重建
make setup-fresh
```

**Q: 權限錯誤**
```bash
# 檢查檔案權限
chmod +x dev-setup.sh
# 檢查 Docker 權限
docker-compose exec app whoami
```

## 📞 支援

如果遇到問題，可以：
1. 查看 `make help` 獲取所有可用命令
2. 使用 `make check` 檢查當前狀態
3. 查看 Docker 日誌：`make docker-logs`
4. 進入容器檢查：`docker-compose exec app bash`

---

**快速連結**:
- 🌐 管理後台: http://localhost/admin/login
- 👤 測試帳號: admin / password123
- 📊 檢查資料: `make check`
- 🔄 重建資料: `make setup-fresh`