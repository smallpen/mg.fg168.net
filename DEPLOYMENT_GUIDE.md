# 系統部署指南

## 概述

本指南說明如何部署和初始化 Laravel Admin System，確保系統在部署後可以立即使用。

## 快速部署

### 1. 全新部署（推薦）

```bash
# 一鍵部署系統（包含資料庫重建）
php artisan system:deploy --fresh --force

# 或者互動式部署
php artisan system:deploy --fresh
```

### 2. 更新部署

```bash
# 執行遷移和資料更新
php artisan system:deploy

# 強制執行（不詢問確認）
php artisan system:deploy --force
```

### 3. 資料完整性檢查

```bash
# 只檢查資料完整性，不執行部署
php artisan system:deploy --check-only
```

## 手動部署步驟

如果需要手動控制部署過程，可以按以下步驟執行：

### 1. 資料庫遷移

```bash
# 全新安裝
php artisan migrate:fresh

# 或者更新現有資料庫
php artisan migrate --force
```

### 2. 初始化系統資料

```bash
# 生產環境部署
php artisan db:seed --class=DeploymentSeeder --force

# 或者分步執行
php artisan db:seed --class=ProductionSeeder --force
php artisan db:seed --class=DataIntegritySeeder
```

### 3. 清除和優化快取

```bash
# 清除快取
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 生產環境優化
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 預設資料結構

### 管理員帳號

- **使用者名稱**: `admin`
- **密碼**: `admin123`
- **電子郵件**: `admin@system.local`
- **角色**: 系統管理員（擁有所有權限）

### 系統角色

1. **系統管理員 (admin)**
   - 擁有所有 35 個系統權限
   - 可以管理所有功能模組

2. **部門主管 (manager)**
   - 擁有部分管理權限
   - 可以管理使用者和檢視報告

3. **一般使用者 (user)**
   - 擁有基本操作權限
   - 可以檢視儀表板和管理個人資料

### 權限模組

系統包含以下 10 個權限模組，共 35 個權限：

- **儀表板** (2個): 檢視儀表板、統計資訊
- **使用者管理** (6個): 檢視、建立、編輯、刪除、角色指派、匯出
- **角色管理** (5個): 檢視、建立、編輯、刪除、權限管理
- **權限管理** (4個): 檢視、建立、編輯、刪除
- **個人資料** (2個): 檢視、編輯
- **活動日誌** (3個): 檢視、匯出、刪除
- **通知管理** (5個): 檢視、建立、編輯、刪除、發送
- **系統設定** (4個): 檢視、編輯、備份、重置
- **系統管理** (4個): 日誌、維護、監控、安全

## 部署後檢查清單

### 1. 立即執行

- [ ] 登入管理後台 (`/admin/login`)
- [ ] 使用預設帳號登入 (`admin` / `admin123`)
- [ ] **立即修改預設密碼**
- [ ] 檢查系統設定頁面
- [ ] 驗證所有功能模組可正常存取

### 2. 安全設定

- [ ] 建立專屬的管理員帳號
- [ ] 停用或刪除預設 `admin` 帳號
- [ ] 設定適當的檔案權限
- [ ] 配置 HTTPS（生產環境）
- [ ] 設定防火牆規則

### 3. 功能配置

- [ ] 配置郵件服務設定
- [ ] 設定檔案上傳限制
- [ ] 檢查日誌輪轉設定
- [ ] 配置備份策略
- [ ] 設定監控和警報

## 環境特定部署

### 開發環境

```bash
# 包含測試資料的完整部署
php artisan migrate:fresh --seed
```

### 測試環境

```bash
# 使用生產資料結構但包含測試資料
php artisan system:deploy --fresh
php artisan db:seed --class=DevelopmentSeeder
```

### 生產環境

```bash
# 僅包含必要資料的精簡部署
php artisan system:deploy --fresh --force
```

## 故障排除

### 常見問題

1. **權限不足錯誤**
   ```bash
   # 重新執行權限種子
   php artisan db:seed --class=PermissionSeeder --force
   php artisan db:seed --class=RoleSeeder --force
   ```

2. **管理員帳號無法登入**
   ```bash
   # 重新建立管理員帳號
   php artisan db:seed --class=UserSeeder --force
   ```

3. **系統設定遺失**
   ```bash
   # 重新載入系統設定
   php artisan db:seed --class=SettingsSeeder --force
   ```

### 資料完整性檢查

```bash
# 執行完整的資料檢查
php artisan system:deploy --check-only
```

### 重新部署

如果遇到嚴重問題，可以重新部署：

```bash
# 完全重新部署（會刪除所有資料）
php artisan system:deploy --fresh --force
```

## 備份和恢復

### 部署前備份

```bash
# 備份資料庫
mysqldump -u username -p database_name > backup.sql

# 備份檔案
tar -czf files_backup.tar.gz storage/ public/uploads/
```

### 恢復資料

```bash
# 恢復資料庫
mysql -u username -p database_name < backup.sql

# 恢復檔案
tar -xzf files_backup.tar.gz
```

## 效能優化

### 生產環境優化

```bash
# 快取優化
php artisan config:cache
php artisan route:cache
php artisan view:cache

# OPcache 設定（php.ini）
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
```

### 資料庫優化

```bash
# 建立索引（如果需要）
php artisan migrate

# 分析資料庫效能
EXPLAIN SELECT * FROM users WHERE username = 'admin';
```

## 監控和維護

### 日誌監控

- 檢查 `storage/logs/laravel.log`
- 監控錯誤和警告訊息
- 設定日誌輪轉

### 定期維護

- 定期更新系統依賴
- 檢查安全更新
- 監控系統效能
- 備份重要資料

## 支援和文檔

- 系統管理文檔：`/admin/help`
- API 文檔：`/admin/api/docs`
- 錯誤報告：檢查系統日誌
- 技術支援：聯繫系統管理員

---

**重要提醒**: 在生產環境部署後，請立即修改預設密碼並進行安全設定！