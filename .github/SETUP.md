# GitHub 儲存庫設定指南

## 🔐 GitHub Secrets 設定

為了啟用完整的 CI/CD 功能，請在 GitHub 儲存庫中設定以下 Secrets：

### 前往設定頁面
1. 前往 GitHub 儲存庫：https://github.com/smallpen/mg.fg168.net
2. 點擊 "Settings" 標籤
3. 在左側選單中點擊 "Secrets and variables" > "Actions"
4. 點擊 "New repository secret" 來添加每個 Secret

### 📋 必要的 Secrets

#### 測試環境部署 Secrets
```
STAGING_HOST=your-staging-server-ip
STAGING_USERNAME=your-ssh-username
STAGING_SSH_KEY=your-private-ssh-key
STAGING_PORT=22
STAGING_PATH=/path/to/staging/deployment
STAGING_URL=https://staging.yourdomain.com
STAGING_MYSQL_ROOT_PASSWORD=your-staging-mysql-root-password
STAGING_DB_PASSWORD=your-staging-db-password
STAGING_REDIS_PASSWORD=your-staging-redis-password
```

#### 生產環境部署 Secrets
```
PRODUCTION_HOST=your-production-server-ip
PRODUCTION_USERNAME=your-ssh-username
PRODUCTION_SSH_KEY=your-private-ssh-key
PRODUCTION_PORT=22
PRODUCTION_PATH=/path/to/production/deployment
PRODUCTION_URL=https://yourdomain.com
MYSQL_ROOT_PASSWORD=your-production-mysql-root-password
```

#### 通知 Secrets（可選）
```
SLACK_WEBHOOK=https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK
```

## 🚀 CI/CD 工作流程說明

### 自動觸發條件
- **develop 分支推送** → 自動部署到測試環境
- **main 分支推送** → 自動部署到生產環境
- **Pull Request** → 執行程式碼品質檢查和測試

### 工作流程步驟
1. **程式碼品質檢查**
   - PHP CS Fixer 程式碼風格檢查
   - PHPStan 靜態分析
   - 自動化測試執行

2. **安全性掃描**
   - Composer 依賴安全性檢查

3. **Docker 建置測試**
   - 驗證 Docker 映像可正常建置

4. **自動部署**（僅限指定分支）
   - 測試環境：develop 分支
   - 生產環境：main 分支

## 📝 部署前檢查清單

### 伺服器準備
- [ ] 安裝 Docker 和 Docker Compose
- [ ] 設定 SSH 金鑰認證
- [ ] 建立部署目錄
- [ ] 設定防火牆規則

### 環境變數設定
- [ ] 建立 `.env.staging` 檔案（測試環境）
- [ ] 建立 `.env.production` 檔案（生產環境）
- [ ] 設定資料庫連線資訊
- [ ] 設定 Redis 連線資訊

### 秘密檔案設定
在部署伺服器上建立 `secrets/` 目錄並添加以下檔案：
- [ ] `mysql_root_password.txt`
- [ ] `mysql_password.txt`
- [ ] `redis_password.txt`
- [ ] `app_key.txt`

### SSL 憑證設定
- [ ] 取得 SSL 憑證
- [ ] 將憑證放置在 `ssl_certs` volume 中
- [ ] 更新 Nginx 配置中的憑證路徑

## 🔧 手動部署指令

如果需要手動部署，可以使用以下指令：

### 測試環境
```bash
./scripts/deploy.sh staging
```

### 生產環境
```bash
./scripts/deploy.sh production
```

### 回滾
```bash
./scripts/deploy.sh production --rollback
```

## 📊 監控和健康檢查

部署完成後，可以透過以下端點檢查系統狀態：

- **基本健康檢查**: `GET /health`
- **詳細系統狀態**: `GET /health/detailed`
- **效能指標**: `GET /health/metrics`
- **資料庫狀態**: `GET /health/database`
- **Redis 狀態**: `GET /health/redis`

## 🆘 故障排除

### CI/CD 失敗
1. 檢查 GitHub Actions 日誌
2. 確認所有 Secrets 已正確設定
3. 驗證伺服器連線和權限

### 部署失敗
1. 檢查伺服器磁碟空間
2. 確認 Docker 服務運行正常
3. 檢查環境變數和秘密檔案

### 應用程式錯誤
1. 檢查應用程式日誌：`storage/logs/laravel.log`
2. 檢查 Docker 容器日誌：`docker-compose logs`
3. 執行健康檢查端點診斷問題

## 📞 支援

如有問題，請建立 GitHub Issue 或聯繫維護團隊。