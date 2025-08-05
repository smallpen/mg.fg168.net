# GitHub Actions 秘密設定指南

本文件說明如何在 GitHub Repository 中設定 CI/CD 管道所需的秘密變數。

## 設定步驟

1. 前往 GitHub Repository
2. 點擊 `Settings` 標籤
3. 在左側選單中選擇 `Secrets and variables` > `Actions`
4. 點擊 `New repository secret` 按鈕
5. 輸入秘密名稱和值

## 必要的秘密變數

### 測試環境 (Staging)

```
STAGING_HOST=your-staging-server.com
STAGING_USERNAME=deploy
STAGING_SSH_KEY=-----BEGIN OPENSSH PRIVATE KEY-----
...你的 SSH 私鑰內容...
-----END OPENSSH PRIVATE KEY-----
STAGING_PORT=22
STAGING_PATH=/var/www/laravel-admin-staging
STAGING_URL=https://staging.your-domain.com
STAGING_DB_PASSWORD=your_staging_db_password
STAGING_MYSQL_ROOT_PASSWORD=your_staging_mysql_root_password
STAGING_REDIS_PASSWORD=your_staging_redis_password
```

### 生產環境 (Production)

```
PRODUCTION_HOST=your-production-server.com
PRODUCTION_USERNAME=deploy
PRODUCTION_SSH_KEY=-----BEGIN OPENSSH PRIVATE KEY-----
...你的 SSH 私鑰內容...
-----END OPENSSH PRIVATE KEY-----
PRODUCTION_PORT=22
PRODUCTION_PATH=/var/www/laravel-admin
PRODUCTION_URL=https://your-domain.com
MYSQL_ROOT_PASSWORD=your_production_mysql_root_password
REDIS_PASSWORD=your_production_redis_password
```

### 通知設定

```
SLACK_WEBHOOK=https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK
```

### 程式碼品質工具 (可選)

```
CODECOV_TOKEN=your_codecov_token
SONARCLOUD_TOKEN=your_sonarcloud_token
```

## SSH 金鑰設定

### 1. 生成 SSH 金鑰對

在本地機器上執行：

```bash
ssh-keygen -t ed25519 -C "github-actions@your-domain.com" -f ~/.ssh/github_actions_key
```

### 2. 將公鑰添加到伺服器

將公鑰內容 (`~/.ssh/github_actions_key.pub`) 添加到目標伺服器的 `~/.ssh/authorized_keys` 檔案中：

```bash
# 在目標伺服器上執行
echo "ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIGitHub-actions-public-key-content github-actions@your-domain.com" >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
```

### 3. 將私鑰添加到 GitHub Secrets

將私鑰內容 (`~/.ssh/github_actions_key`) 複製並貼上到 GitHub Secrets 中：

- `STAGING_SSH_KEY`: 測試環境的 SSH 私鑰
- `PRODUCTION_SSH_KEY`: 生產環境的 SSH 私鑰

## 伺服器準備

### 1. 安裝必要軟體

在目標伺服器上安裝：

```bash
# Ubuntu/Debian
sudo apt update
sudo apt install -y docker.io docker-compose git curl

# 啟動 Docker 服務
sudo systemctl start docker
sudo systemctl enable docker

# 將部署使用者添加到 docker 群組
sudo usermod -aG docker $USER
```

### 2. 建立部署目錄

```bash
# 測試環境
sudo mkdir -p /var/www/laravel-admin-staging
sudo chown $USER:$USER /var/www/laravel-admin-staging

# 生產環境
sudo mkdir -p /var/www/laravel-admin
sudo chown $USER:$USER /var/www/laravel-admin
```

### 3. 複製專案檔案

```bash
# 測試環境
cd /var/www/laravel-admin-staging
git clone https://github.com/your-username/laravel-admin-system.git .
git checkout develop

# 生產環境
cd /var/www/laravel-admin
git clone https://github.com/your-username/laravel-admin-system.git .
git checkout main
```

### 4. 設定環境變數

建立環境變數檔案：

```bash
# 測試環境
cp .env.example .env.staging
# 編輯 .env.staging 檔案，設定測試環境的資料庫密碼等

# 生產環境
cp .env.example .env.production
# 編輯 .env.production 檔案，設定生產環境的資料庫密碼等
```

## 環境變數檔案範例

### .env.staging

```env
APP_NAME="Laravel Admin System (Staging)"
APP_ENV=staging
APP_DEBUG=false
APP_URL=https://staging.your-domain.com

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_admin_staging
DB_USERNAME=laravel_staging
DB_PASSWORD=your_staging_db_password

REDIS_HOST=redis
REDIS_PASSWORD=your_staging_redis_password
REDIS_PORT=6379

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
```

### .env.production

```env
APP_NAME="Laravel Admin System"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_admin
DB_USERNAME=laravel
DB_PASSWORD=your_production_db_password

REDIS_HOST=redis
REDIS_PASSWORD=your_production_redis_password
REDIS_PORT=6379

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-server.com
MAIL_PORT=587
MAIL_USERNAME=your-email@your-domain.com
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=tls
```

## 測試部署

### 1. 手動測試

在設定完成後，可以手動觸發 GitHub Actions 來測試部署：

1. 前往 GitHub Repository 的 `Actions` 標籤
2. 選擇 `CI/CD Pipeline` 工作流程
3. 點擊 `Run workflow` 按鈕
4. 選擇分支並執行

### 2. 檢查部署結果

部署完成後，檢查以下項目：

- [ ] 應用程式可以正常存取
- [ ] 健康檢查端點回應正常
- [ ] 資料庫連線正常
- [ ] 快取系統運作正常
- [ ] 日誌檔案沒有錯誤

## 故障排除

### 常見問題

1. **SSH 連線失敗**
   - 檢查 SSH 金鑰格式是否正確
   - 確認公鑰已正確添加到伺服器
   - 檢查伺服器防火牆設定

2. **Docker 權限問題**
   - 確認部署使用者已添加到 docker 群組
   - 重新登入或重啟 SSH 連線

3. **資料庫連線失敗**
   - 檢查資料庫密碼是否正確
   - 確認 Docker 容器之間的網路連線

4. **檔案權限問題**
   - 檢查 storage 和 bootstrap/cache 目錄權限
   - 確認 www-data 使用者有寫入權限

### 日誌檢查

```bash
# 檢查 Docker 容器日誌
docker-compose logs app
docker-compose logs nginx
docker-compose logs mysql

# 檢查 Laravel 日誌
tail -f storage/logs/laravel.log

# 檢查 Nginx 日誌
docker-compose exec nginx tail -f /var/log/nginx/error.log
```

## 安全建議

1. **定期更新 SSH 金鑰**
2. **使用強密碼**
3. **限制 SSH 存取 IP**
4. **定期備份資料**
5. **監控系統日誌**
6. **使用 HTTPS**
7. **定期更新系統和套件**