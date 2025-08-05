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
git clone <repository-url> laravel-admin
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

DB_DATABASE=laravel_admin
DB_USERNAME=laravel
DB_PASSWORD=your_secure_password

REDIS_PASSWORD=your_redis_password

MAIL_HOST=your_smtp_host
MAIL_USERNAME=your_email@domain.com
MAIL_PASSWORD=your_email_password
```

### 3. 設定秘密檔案

建立秘密檔案目錄並設定密碼：

```bash
# 複製範例檔案
cp secrets/mysql_root_password.txt.example secrets/mysql_root_password.txt
cp secrets/mysql_password.txt.example secrets/mysql_password.txt
cp secrets/redis_password.txt.example secrets/redis_password.txt
cp secrets/app_key.txt.example secrets/app_key.txt

# 生成 Laravel APP_KEY
php artisan key:generate --show > secrets/app_key.txt
```

編輯每個秘密檔案，設定強密碼：

```bash
# 編輯密碼檔案
nano secrets/mysql_root_password.txt
nano secrets/mysql_password.txt
nano secrets/redis_password.txt
```

### 4. 生成 SSL 憑證

#### 開發/測試環境（自簽名憑證）

```bash
./docker/scripts/generate-ssl.sh
```

#### 生產環境（Let's Encrypt 或商業憑證）

將憑證檔案放置到以下位置：
- 憑證檔案: `docker/ssl/cert.pem`
- 私鑰檔案: `docker/ssl/key.pem`

## 部署流程

### 自動部署（推薦）

使用提供的部署腳本：

```bash
./docker/scripts/deploy.sh
```

### 手動部署

#### 1. 建置 Docker 映像

```bash
docker-compose -f docker-compose.prod.yml build --no-cache
```

#### 2. 啟動服務

```bash
docker-compose -f docker-compose.prod.yml up -d
```

#### 3. 等待服務啟動

```bash
# 檢查服務狀態
docker-compose -f docker-compose.prod.yml ps

# 查看日誌
docker-compose -f docker-compose.prod.yml logs -f
```

#### 4. 執行資料庫遷移

```bash
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force
```

#### 5. 執行資料庫種子

```bash
docker-compose -f docker-compose.prod.yml exec app php artisan db:seed --force
```

#### 6. 最佳化應用程式

```bash
# 清除快取
docker-compose -f docker-compose.prod.yml exec app php artisan cache:clear
docker-compose -f docker-compose.prod.yml exec app php artisan config:clear
docker-compose -f docker-compose.prod.yml exec app php artisan route:clear
docker-compose -f docker-compose.prod.yml exec app php artisan view:clear

# 建立快取
docker-compose -f docker-compose.prod.yml exec app php artisan config:cache
docker-compose -f docker-compose.prod.yml exec app php artisan route:cache
docker-compose -f docker-compose.prod.yml exec app php artisan view:cache

# 最佳化 Composer
docker-compose -f docker-compose.prod.yml exec app composer dump-autoload --optimize
```

## 服務管理

### 啟動服務

```bash
docker-compose -f docker-compose.prod.yml up -d
```

### 停止服務

```bash
docker-compose -f docker-compose.prod.yml down
```

### 重新啟動服務

```bash
docker-compose -f docker-compose.prod.yml restart
```

### 查看服務狀態

```bash
docker-compose -f docker-compose.prod.yml ps
```

### 查看日誌

```bash
# 查看所有服務日誌
docker-compose -f docker-compose.prod.yml logs -f

# 查看特定服務日誌
docker-compose -f docker-compose.prod.yml logs -f app
docker-compose -f docker-compose.prod.yml logs -f nginx
docker-compose -f docker-compose.prod.yml logs -f mysql
docker-compose -f docker-compose.prod.yml logs -f redis
```

## 備份和恢復

### 自動備份

系統包含自動備份功能，執行以下命令進行備份：

```bash
docker-compose -f docker-compose.prod.yml run --rm backup
```

### 手動備份

#### 資料庫備份

```bash
docker-compose -f docker-compose.prod.yml exec mysql mysqldump -u root -p laravel_admin > backup_$(date +%Y%m%d).sql
```

#### 檔案備份

```bash
tar -czf storage_backup_$(date +%Y%m%d).tar.gz storage/
```

### 恢復資料

#### 恢復資料庫

```bash
docker-compose -f docker-compose.prod.yml exec -T mysql mysql -u root -p laravel_admin < backup_20231201.sql
```

#### 恢復檔案

```bash
tar -xzf storage_backup_20231201.tar.gz
```

## 監控和維護

### 健康檢查

系統提供健康檢查端點：

```bash
# 檢查 Nginx
curl http://localhost/health

# 檢查應用程式
docker-compose -f docker-compose.prod.yml exec app php artisan tinker --execute="echo 'OK';"
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

#### 2. 重新建置映像

```bash
docker-compose -f docker-compose.prod.yml build --no-cache
```

#### 3. 重新啟動服務

```bash
docker-compose -f docker-compose.prod.yml down
docker-compose -f docker-compose.prod.yml up -d
```

#### 4. 執行遷移

```bash
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force
```

#### 5. 清除快取

```bash
docker-compose -f docker-compose.prod.yml exec app php artisan cache:clear
docker-compose -f docker-compose.prod.yml exec app php artisan config:cache
docker-compose -f docker-compose.prod.yml exec app php artisan route:cache
docker-compose -f docker-compose.prod.yml exec app php artisan view:cache
```

## 故障排除

### 常見問題

#### 1. 容器無法啟動

檢查日誌：
```bash
docker-compose -f docker-compose.prod.yml logs
```

檢查磁碟空間：
```bash
df -h
```

#### 2. 資料庫連線失敗

檢查 MySQL 容器狀態：
```bash
docker-compose -f docker-compose.prod.yml ps mysql
```

檢查密碼設定：
```bash
cat secrets/mysql_password.txt
```

#### 3. Redis 連線失敗

檢查 Redis 容器狀態：
```bash
docker-compose -f docker-compose.prod.yml ps redis
```

測試 Redis 連線：
```bash
docker-compose -f docker-compose.prod.yml exec redis redis-cli ping
```

#### 4. SSL 憑證問題

檢查憑證檔案：
```bash
ls -la docker/ssl/
```

驗證憑證：
```bash
openssl x509 -in docker/ssl/cert.pem -text -noout
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
```

### 3. PHP 最佳化

編輯 `docker/php/php.prod.ini` 調整 PHP 設定：

```ini
memory_limit = 512M
opcache.memory_consumption = 256
opcache.max_accelerated_files = 20000
```

## 聯絡資訊

如有部署相關問題，請聯絡系統管理員或查看專案文檔。