# Laravel Admin 系統生產環境部署指南

## 概覽

本文件說明如何將 Laravel Admin 系統部署到生產環境。系統使用 Docker 容器化技術，確保環境一致性和可移植性。

## 系統需求

### 硬體需求
- CPU: 2 核心以上
- 記憶體: 4GB 以上
- 儲存空間: 20GB 以上
- 網路: 穩定的網際網路連線

### 軟體需求
- Docker Engine 20.10+
- Docker Compose 2.0+
- Git
- OpenSSL（用於 SSL 憑證生成）

## 部署前準備

### 1. 複製專案

```bash
git clone https://github.com/smallpen/mg.fg168.net.git laravel-admin
cd laravel-admin
```

### 2. 設定環境變數

複製環境變數範例檔案：

```bash
cp .env.production.example .env
```

編輯 `.env` 檔案，設定以下重要參數：

```env
APP_NAME="Laravel Admin System"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# 資料庫設定
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_admin
DB_USERNAME=laravel
DB_PASSWORD=your_secure_password

# Redis 設定
REDIS_HOST=redis
REDIS_PASSWORD=your_redis_password
REDIS_PORT=6379

# 快取和 Session 設定
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# 郵件設定
MAIL_MAILER=smtp
MAIL_HOST=your_smtp_host
MAIL_PORT=587
MAIL_USERNAME=your_email@domain.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@your-domain.com"
MAIL_FROM_NAME="${APP_NAME}"

# 多語言設定
DEFAULT_LOCALE=zh_TW
FALLBACK_LOCALE=en
```

### 3. 設定秘密檔案

建立秘密檔案目錄並設定密碼：

```bash
# 複製範例檔案
cp secrets/mysql_root_password.txt.example secrets/mysql_root_password.txt
cp secrets/mysql_password.txt.example secrets/mysql_password.txt
cp secrets/redis_password.txt.example secrets/redis_password.txt
cp secrets/app_key.txt.example secrets/app_key.txt

# 生成 Laravel APP_KEY（如果尚未安裝 Laravel，可以使用 Docker）
docker run --rm -v $(pwd):/app -w /app php:8.2-cli php artisan key:generate --show > secrets/app_key.txt
```

編輯每個秘密檔案，設定強密碼：

```bash
# 設定 MySQL root 密碼
echo "your_mysql_root_password" > secrets/mysql_root_password.txt

# 設定 MySQL 使用者密碼
echo "your_mysql_user_password" > secrets/mysql_password.txt

# 設定 Redis 密碼
echo "your_redis_password" > secrets/redis_password.txt

# 確保檔案權限安全
chmod 600 secrets/*.txt
```

### 4. 生成 SSL 憑證

#### 開發/測試環境（自簽名憑證）

```bash
chmod +x docker/scripts/generate-ssl.sh
./docker/scripts/generate-ssl.sh
```

#### 生產環境（Let's Encrypt 或商業憑證）

將憑證檔案放置到以下位置：
- 憑證檔案: `docker/nginx/ssl/cert.pem`
- 私鑰檔案: `docker/nginx/ssl/key.pem`

或者使用 Docker volume 掛載外部憑證：
```bash
# 建立 SSL volume 並複製憑證
docker volume create ssl_certs
docker run --rm -v ssl_certs:/ssl -v $(pwd)/path/to/certs:/certs alpine cp /certs/* /ssl/
```

## 部署流程

### 快速部署（推薦）

使用提供的快速部署腳本：

```bash
# 給予執行權限
chmod +x quick-deploy.sh

# 部署生產環境
./quick-deploy.sh prod

# 或者強制重新建置
./quick-deploy.sh prod --build
```

#### 快速部署腳本選項

```bash
# 顯示使用說明
./quick-deploy.sh --help

# 部署不同環境
./quick-deploy.sh dev          # 開發環境
./quick-deploy.sh staging      # 測試環境  
./quick-deploy.sh prod         # 生產環境

# 其他選項
./quick-deploy.sh prod --build    # 強制重新建置映像
./quick-deploy.sh prod --down     # 停止並移除容器
./quick-deploy.sh prod --logs     # 顯示服務日誌
./quick-deploy.sh prod --status   # 顯示服務狀態
```

快速部署腳本會自動執行以下步驟：
1. 檢查 Docker 環境
2. 建置 Docker 映像（如果需要）
3. 啟動所有服務
4. 等待服務準備就緒
5. 執行資料庫遷移
6. 清理和快取應用程式設定
7. 執行健康檢查
8. 顯示部署結果和存取資訊

### 使用 Docker 腳本部署

```bash
chmod +x docker/scripts/deploy.sh
./docker/scripts/deploy.sh
```

### 手動部署

#### 1. 建置 Docker 映像

```bash
# 使用新版 Docker Compose 語法
docker compose -f docker-compose.prod.yml build --no-cache

# 或使用舊版語法（如果新版不可用）
docker-compose -f docker-compose.prod.yml build --no-cache
```

#### 2. 啟動服務

```bash
docker compose -f docker-compose.prod.yml up -d
```

#### 3. 等待服務啟動

```bash
# 檢查服務狀態
docker compose -f docker-compose.prod.yml ps

# 查看日誌
docker compose -f docker-compose.prod.yml logs -f

# 檢查健康狀態
docker compose -f docker-compose.prod.yml ps --filter "health=healthy"
```

#### 4. 執行資料庫遷移

```bash
# 等待資料庫準備就緒
sleep 15

# 執行遷移
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
```

#### 5. 執行資料庫種子（可選）

```bash
docker compose -f docker-compose.prod.yml exec app php artisan db:seed --force
```

#### 6. 編譯前端資源

```bash
# 安裝 Node.js 依賴並編譯資源
docker compose -f docker-compose.prod.yml exec app npm install
docker compose -f docker-compose.prod.yml exec app npm run build
```

#### 7. 最佳化應用程式

```bash
# 清除快取
docker compose -f docker-compose.prod.yml exec app php artisan cache:clear
docker compose -f docker-compose.prod.yml exec app php artisan config:clear
docker compose -f docker-compose.prod.yml exec app php artisan route:clear
docker compose -f docker-compose.prod.yml exec app php artisan view:clear

# 建立快取（生產環境）
docker compose -f docker-compose.prod.yml exec app php artisan config:cache
docker compose -f docker-compose.prod.yml exec app php artisan route:cache
docker compose -f docker-compose.prod.yml exec app php artisan view:cache

# 最佳化 Composer
docker compose -f docker-compose.prod.yml exec app composer dump-autoload --optimize
```

## 服務管理

### 啟動服務

```bash
# 使用快速部署腳本
./quick-deploy.sh prod

# 或手動啟動
docker compose -f docker-compose.prod.yml up -d
```

### 停止服務

```bash
# 使用快速部署腳本
./quick-deploy.sh prod --down

# 或手動停止
docker compose -f docker-compose.prod.yml down
```

### 重新啟動服務

```bash
docker compose -f docker-compose.prod.yml restart
```

### 查看服務狀態

```bash
# 使用快速部署腳本
./quick-deploy.sh prod --status

# 或手動查看
docker compose -f docker-compose.prod.yml ps
```

### 查看日誌

```bash
# 使用快速部署腳本
./quick-deploy.sh prod --logs

# 或手動查看所有服務日誌
docker compose -f docker-compose.prod.yml logs -f

# 查看特定服務日誌
docker compose -f docker-compose.prod.yml logs -f app
docker compose -f docker-compose.prod.yml logs -f nginx
docker compose -f docker-compose.prod.yml logs -f mysql
docker compose -f docker-compose.prod.yml logs -f redis
```

## 備份和恢復

### 自動備份

系統包含自動備份功能，執行以下命令進行備份：

```bash
docker compose -f docker-compose.prod.yml run --rm backup
```

### 手動備份

#### 資料庫備份

```bash
# 使用 Docker secrets 中的密碼
docker compose -f docker-compose.prod.yml exec mysql mysqldump -u laravel -p$(cat secrets/mysql_password.txt) laravel_admin > backup_$(date +%Y%m%d).sql

# 或者使用 root 使用者
docker compose -f docker-compose.prod.yml exec mysql mysqldump -u root -p$(cat secrets/mysql_root_password.txt) laravel_admin > backup_$(date +%Y%m%d).sql
```

#### 檔案備份

```bash
# 備份 storage 目錄
tar -czf storage_backup_$(date +%Y%m%d).tar.gz storage/

# 備份整個專案（排除不需要的檔案）
tar -czf project_backup_$(date +%Y%m%d).tar.gz \
  --exclude='node_modules' \
  --exclude='vendor' \
  --exclude='.git' \
  --exclude='storage/logs' \
  --exclude='storage/framework/cache' \
  --exclude='storage/framework/sessions' \
  --exclude='storage/framework/views' \
  .
```

### 恢復資料

#### 恢復資料庫

```bash
# 恢復到現有資料庫
docker compose -f docker-compose.prod.yml exec -T mysql mysql -u laravel -p$(cat secrets/mysql_password.txt) laravel_admin < backup_20250807.sql

# 或使用 root 使用者
docker compose -f docker-compose.prod.yml exec -T mysql mysql -u root -p$(cat secrets/mysql_root_password.txt) laravel_admin < backup_20250807.sql
```

#### 恢復檔案

```bash
# 恢復 storage 目錄
tar -xzf storage_backup_20250807.tar.gz

# 恢復整個專案
tar -xzf project_backup_20250807.tar.gz
```

## 監控和維護

### 健康檢查

系統提供健康檢查端點：

```bash
# 檢查 Nginx（如果有健康檢查端點）
curl http://localhost/health

# 檢查應用程式
docker compose -f docker-compose.prod.yml exec app php artisan tinker --execute="echo 'OK';"

# 檢查所有容器健康狀態
docker compose -f docker-compose.prod.yml ps

# 檢查特定服務健康狀態
docker inspect laravel_admin_app_prod --format='{{.State.Health.Status}}'
docker inspect laravel_admin_mysql_prod --format='{{.State.Health.Status}}'
docker inspect laravel_admin_redis_prod --format='{{.State.Health.Status}}'
```

### 效能監控

#### 查看資源使用情況

```bash
# 查看容器資源使用
docker stats

# 查看磁碟使用
df -h

# 查看記憶體使用
free -h
```

#### 查看應用程式效能

```bash
# 查看 Laravel 日誌
docker-compose -f docker-compose.prod.yml exec app tail -f storage/logs/laravel.log

# 查看 Nginx 存取日誌
docker-compose -f docker-compose.prod.yml exec nginx tail -f /var/log/nginx/access.log

# 查看 MySQL 慢查詢日誌
docker-compose -f docker-compose.prod.yml exec mysql tail -f /var/log/mysql/mysql-slow.log
```

### 更新部署

#### 1. 拉取最新程式碼

```bash
git pull origin main
```

#### 2. 使用快速部署腳本更新

```bash
# 重新建置並部署
./quick-deploy.sh prod --build
```

#### 或手動更新步驟：

#### 2. 重新建置映像

```bash
docker compose -f docker-compose.prod.yml build --no-cache
```

#### 3. 重新啟動服務

```bash
docker compose -f docker-compose.prod.yml down
docker compose -f docker-compose.prod.yml up -d
```

#### 4. 執行遷移

```bash
# 等待服務啟動
sleep 15

# 執行遷移
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
```

#### 5. 編譯前端資源

```bash
docker compose -f docker-compose.prod.yml exec app npm install
docker compose -f docker-compose.prod.yml exec app npm run build
```

#### 6. 清除快取

```bash
docker compose -f docker-compose.prod.yml exec app php artisan cache:clear
docker compose -f docker-compose.prod.yml exec app php artisan config:cache
docker compose -f docker-compose.prod.yml exec app php artisan route:cache
docker compose -f docker-compose.prod.yml exec app php artisan view:cache
```

## 故障排除

### 常見問題

#### 1. 容器無法啟動

檢查日誌：
```bash
docker compose -f docker-compose.prod.yml logs
```

檢查磁碟空間：
```bash
df -h
```

檢查 Docker 系統資源：
```bash
docker system df
```

#### 2. 資料庫連線失敗

檢查 MySQL 容器狀態：
```bash
docker compose -f docker-compose.prod.yml ps mysql
```

檢查密碼設定：
```bash
cat secrets/mysql_password.txt
cat secrets/mysql_root_password.txt
```

測試資料庫連線：
```bash
docker compose -f docker-compose.prod.yml exec mysql mysql -u laravel -p$(cat secrets/mysql_password.txt) -e "SELECT 1;"
```

#### 3. Redis 連線失敗

檢查 Redis 容器狀態：
```bash
docker compose -f docker-compose.prod.yml ps redis
```

測試 Redis 連線：
```bash
docker compose -f docker-compose.prod.yml exec redis redis-cli --no-auth-warning -a $(cat secrets/redis_password.txt) ping
```

#### 4. SSL 憑證問題

檢查憑證檔案：
```bash
ls -la docker/nginx/ssl/
```

驗證憑證：
```bash
openssl x509 -in docker/nginx/ssl/cert.pem -text -noout
```

檢查憑證有效期：
```bash
openssl x509 -in docker/nginx/ssl/cert.pem -noout -dates
```

#### 5. 前端資源載入問題

檢查編譯的資源：
```bash
ls -la public/build/
```

重新編譯前端資源：
```bash
docker compose -f docker-compose.prod.yml exec app npm run build
```

#### 6. 權限問題

修正 storage 目錄權限：
```bash
docker compose -f docker-compose.prod.yml exec app chown -R www-data:www-data storage bootstrap/cache
docker compose -f docker-compose.prod.yml exec app chmod -R 775 storage bootstrap/cache
```

### 日誌位置

- 應用程式日誌: `storage/logs/laravel.log`
- Nginx 日誌: `/var/log/nginx/access.log`, `/var/log/nginx/error.log`
- MySQL 日誌: `/var/log/mysql/error.log`, `/var/log/mysql/mysql-slow.log`
- Redis 日誌: 透過 Docker 日誌查看

## 安全考量

### 1. 防火牆設定

確保只開放必要的端口：
- 80 (HTTP)
- 443 (HTTPS)

### 2. 定期更新

定期更新系統和 Docker 映像：

```bash
# 更新系統套件
sudo apt update && sudo apt upgrade

# 更新 Docker 映像
docker-compose -f docker-compose.prod.yml pull
```

### 3. 密碼管理

- 使用強密碼
- 定期更換密碼
- 不要將密碼提交到版本控制系統

### 4. 備份加密

對備份檔案進行加密：

```bash
gpg --symmetric --cipher-algo AES256 backup_file.sql
```

## 效能調校

### 1. MySQL 最佳化

編輯 `docker/mysql/prod.cnf` 根據伺服器規格調整參數：

```ini
innodb_buffer_pool_size = 1G  # 設定為可用記憶體的 70-80%
max_connections = 200
query_cache_size = 64M
```

### 2. Redis 最佳化

編輯 `docker/redis/redis.conf` 調整記憶體設定：

```ini
maxmemory 512mb
maxmemory-policy allkeys-lru
save 900 1
save 300 10
save 60 10000
```

### 3. PHP 最佳化

編輯 PHP 配置檔案調整設定：

```ini
memory_limit = 512M
opcache.enable = 1
opcache.memory_consumption = 256
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 0
opcache.save_comments = 1
opcache.fast_shutdown = 1
```

### 4. Nginx 最佳化

調整 Nginx 配置以提升效能：

```nginx
worker_processes auto;
worker_connections 1024;

gzip on;
gzip_vary on;
gzip_min_length 1024;
gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;

client_max_body_size 20M;
```

## 多語言支援

系統支援多語言功能，預設語言為正體中文（zh_TW），備用語言為英文（en）。

### 語言設定

在 `.env` 檔案中設定：

```env
DEFAULT_LOCALE=zh_TW
FALLBACK_LOCALE=en
```

### 支援的語言

- `zh_TW` - 正體中文（繁體中文）
- `en` - 英文

### 新增語言

1. 在 `resources/lang/` 目錄下建立新的語言目錄
2. 複製現有語言檔案並翻譯
3. 更新語言選擇器配置

## 常用指令速查

```bash
# 快速部署生產環境
./quick-deploy.sh prod

# 查看服務狀態
./quick-deploy.sh prod --status

# 查看日誌
./quick-deploy.sh prod --logs

# 停止服務
./quick-deploy.sh prod --down

# 進入應用程式容器
docker compose -f docker-compose.prod.yml exec app bash

# 執行 Artisan 指令
docker compose -f docker-compose.prod.yml exec app php artisan [command]

# 清除所有快取
docker compose -f docker-compose.prod.yml exec app php artisan optimize:clear

# 最佳化生產環境
docker compose -f docker-compose.prod.yml exec app php artisan optimize
```

## 聯絡資訊

如有部署相關問題，請聯絡系統管理員或查看專案文檔。

- 專案倉庫：https://github.com/smallpen/mg.fg168.net
- 問題回報：請在 GitHub Issues 中提交