# Laravel Admin 系統生產環境部署指南

## 概述

本指南說明如何使用 Docker secrets 安全地部署 Laravel Admin 系統到生產環境。

## 方案二：使用 Docker Secrets（推薦）

這個方案使用 Docker secrets 來安全地管理敏感資訊，如資料庫密碼、Redis 密碼和應用程式金鑰。

### 架構說明

- **應用程式容器**: 使用 `app-start.sh` 腳本從 Docker secrets 讀取密碼並設定環境變數
- **Queue Worker**: 使用 `queue-start.sh` 腳本處理背景任務
- **Redis**: 使用 `redis-start.sh` 腳本從 secrets 設定密碼
- **MySQL**: 直接使用 Docker secrets 設定密碼

### 部署步驟

#### 1. 確認 Secrets 檔案

確保以下檔案存在且包含正確的密碼：

```bash
secrets/
├── mysql_root_password.txt    # MySQL root 密碼
├── mysql_password.txt         # MySQL 使用者密碼
├── redis_password.txt         # Redis 密碼
└── app_key.txt               # Laravel 應用程式金鑰
```

#### 2. 執行部署

使用提供的部署腳本：

```bash
./deploy-prod.sh
```

或手動執行：

```bash
# 停止現有容器
docker-compose -f docker-compose.prod.yml down

# 建置並啟動
docker-compose -f docker-compose.prod.yml up -d --build
```

#### 3. 驗證部署

使用除錯腳本檢查狀態：

```bash
./debug-prod.sh
```

### 故障排除

#### Redis 驗證錯誤

如果遇到 `NOAUTH Authentication required` 錯誤：

1. 檢查 `secrets/redis_password.txt` 檔案是否存在且包含正確密碼
2. 確認 Redis 容器正常啟動：
   ```bash
   docker-compose -f docker-compose.prod.yml logs redis
   ```
3. 測試 Redis 連線：
   ```bash
   docker exec laravel_admin_redis_prod redis-cli -a "$(cat secrets/redis_password.txt)" ping
   ```

#### 應用程式無法連接 Redis

1. 檢查應用程式容器日誌：
   ```bash
   docker-compose -f docker-compose.prod.yml logs app
   ```
2. 確認環境變數設定：
   ```bash
   docker exec laravel_admin_app_prod env | grep REDIS
   ```
3. 手動測試連線：
   ```bash
   docker exec laravel_admin_app_prod php artisan tinker --execute="Redis::ping()"
   ```

### 有用的命令

```bash
# 查看所有容器狀態
docker-compose -f docker-compose.prod.yml ps

# 查看即時日誌
docker-compose -f docker-compose.prod.yml logs -f

# 進入應用程式容器
docker exec -it laravel_admin_app_prod bash

# 重啟特定服務
docker-compose -f docker-compose.prod.yml restart app

# 清除 Laravel 快取
docker exec laravel_admin_app_prod php artisan cache:clear
docker exec laravel_admin_app_prod php artisan config:clear

# 重新快取配置
docker exec laravel_admin_app_prod php artisan config:cache
```

### 安全注意事項

1. **Secrets 檔案權限**: 確保 secrets 目錄和檔案有適當的權限
2. **網路安全**: 生產環境應使用防火牆限制對容器的直接存取
3. **定期更新**: 定期更新密碼和應用程式金鑰
4. **備份**: 定期備份 secrets 檔案和資料庫

### 監控和維護

1. **日誌監控**: 定期檢查應用程式和系統日誌
2. **效能監控**: 監控 Redis 和 MySQL 的記憶體使用情況
3. **磁碟空間**: 監控 Docker volumes 的磁碟使用情況
4. **自動備份**: 設定自動備份任務

## 檔案說明

- `docker-compose.prod.yml`: 生產環境 Docker Compose 配置
- `docker/scripts/app-start.sh`: 應用程式容器啟動腳本
- `docker/scripts/queue-start.sh`: Queue Worker 啟動腳本
- `docker/scripts/redis-start.sh`: Redis 啟動腳本
- `deploy-prod.sh`: 自動部署腳本
- `debug-prod.sh`: 除錯診斷腳本
- `.env.production`: 生產環境配置檔案（不包含敏感資訊）