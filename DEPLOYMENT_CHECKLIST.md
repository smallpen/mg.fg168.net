# 部署檢查清單

在執行生產環境部署前，請確認以下項目已完成。

## 部署前檢查

### 1. 環境檔案
- [ ] `.env.production` 檔案存在且配置正確
- [ ] APP_KEY 已設定且有效
- [ ] 資料庫連線資訊正確
- [ ] Redis 連線資訊正確

### 2. Secrets 檔案
- [ ] `secrets/app_key.txt` 存在且包含有效的 APP_KEY
- [ ] `secrets/mysql_password.txt` 存在且包含資料庫密碼
- [ ] `secrets/mysql_root_password.txt` 存在且包含 root 密碼
- [ ] `secrets/redis_password.txt` 存在且包含 Redis 密碼

### 3. Docker 配置
- [ ] `docker-compose.prod.yml` 配置正確
- [ ] MySQL 初始化腳本 `docker/mysql/init.sql` 存在
- [ ] 權限修復腳本 `docker/scripts/fix-permissions.sh` 存在且可執行

### 4. 系統需求
- [ ] Docker 和 Docker Compose 已安裝
- [ ] 足夠的磁碟空間（至少 10GB）
- [ ] 網路連線正常
- [ ] 必要的連接埠（80, 443）可用

## 部署步驟

### 1. 執行部署前檢查
```bash
./scripts/pre-deploy-check.sh
```

### 2. 停止現有服務（如果存在）
```bash
./quick-deploy.sh prod --down
```

### 3. 執行部署
```bash
# 首次部署或需要重新建置
./quick-deploy.sh prod --build

# 一般部署
./quick-deploy.sh prod
```

### 4. 驗證部署
```bash
./scripts/post-deploy-verify.sh prod
```

## 部署後檢查

### 1. 服務狀態
- [ ] 所有容器都在運行且健康
- [ ] 沒有容器處於重啟循環狀態

```bash
docker compose -f docker-compose.prod.yml ps
```

### 2. 應用程式功能
- [ ] 網站可以正常存取
- [ ] 健康檢查端點回應正常
- [ ] 資料庫連線正常
- [ ] Redis 連線正常

```bash
curl http://localhost/health
```

### 3. 日誌檢查
- [ ] 沒有嚴重錯誤訊息
- [ ] 應用程式日誌正常

```bash
docker compose -f docker-compose.prod.yml logs app --tail=50
```

### 4. 效能檢查
- [ ] 回應時間在可接受範圍內
- [ ] 記憶體使用量正常
- [ ] CPU 使用量正常

```bash
docker stats --no-stream
```

## 回滾計劃

如果部署失敗，執行以下步驟：

### 1. 停止新部署
```bash
./quick-deploy.sh prod --down
```

### 2. 檢查問題
```bash
# 檢查日誌
docker compose -f docker-compose.prod.yml logs

# 檢查容器狀態
docker compose -f docker-compose.prod.yml ps
```

### 3. 修復問題
參考 [故障排除指南](TROUBLESHOOTING.md) 解決問題。

### 4. 重新部署
```bash
./quick-deploy.sh prod --build
```

## 維護任務

### 定期檢查（每週）
- [ ] 檢查磁碟空間使用量
- [ ] 檢查日誌檔案大小
- [ ] 檢查容器健康狀態
- [ ] 檢查系統效能指標

### 定期維護（每月）
- [ ] 清理舊的 Docker 映像
- [ ] 備份重要資料
- [ ] 更新系統套件
- [ ] 檢查安全性更新

```bash
# 清理 Docker 資源
docker system prune -f

# 檢查磁碟使用量
df -h

# 檢查記憶體使用量
free -h
```

## 緊急聯絡

如果遇到嚴重問題：

1. 立即停止服務：`./quick-deploy.sh prod --down`
2. 檢查日誌並記錄錯誤訊息
3. 參考故障排除指南
4. 如需協助，提供完整的錯誤資訊和系統狀態

## 簽核

部署完成後，請確認：

- [ ] 技術負責人已驗證部署
- [ ] 功能測試已通過
- [ ] 效能測試已通過
- [ ] 安全檢查已完成
- [ ] 文件已更新

**部署人員：** _______________  
**日期：** _______________  
**版本：** _______________  
**簽名：** _______________