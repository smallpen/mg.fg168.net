# 故障排除指南

本文件包含常見問題的解決方案和除錯步驟。

## 常見問題

### 1. 資料庫連線問題

**錯誤訊息：** `SQLSTATE[HY000] [1045] Access denied for user 'db_user'@'172.20.0.5'`

**解決方案：**
```bash
# 檢查資料庫使用者是否存在
docker compose -f docker-compose.prod.yml exec mysql mysql -u root -p1qaz1234 -e "SELECT User, Host FROM mysql.user WHERE User='db_user';"

# 如果使用者不存在，手動建立
docker compose -f docker-compose.prod.yml exec mysql mysql -u root -p1qaz1234 -e "CREATE USER IF NOT EXISTS 'db_user'@'%' IDENTIFIED BY '1qaz1234'; GRANT ALL PRIVILEGES ON mg_db.* TO 'db_user'@'%'; FLUSH PRIVILEGES;"
```

### 2. Redis 認證問題

**錯誤訊息：** `NOAUTH Authentication required`

**解決方案：**
```bash
# 檢查 Redis 密碼設定
docker compose -f docker-compose.prod.yml exec app cat /var/www/html/.env | grep REDIS

# 測試 Redis 連線
docker compose -f docker-compose.prod.yml exec app php artisan tinker --execute="use Illuminate\Support\Facades\Redis; echo Redis::ping();"
```

### 3. 檔案權限問題

**錯誤訊息：** `file_put_contents(...): Failed to open stream: Permission denied`

**解決方案：**
```bash
# 修復權限
docker compose -f docker-compose.prod.yml exec app bash /scripts/fix-permissions.sh

# 或手動修復
docker compose -f docker-compose.prod.yml exec app chown -R www-data:www-data /var/www/html/storage
docker compose -f docker-compose.prod.yml exec app chown -R www-data:www-data /var/www/html/bootstrap/cache
```

### 4. APP_KEY 未設定

**錯誤訊息：** `No application encryption key has been specified`

**解決方案：**
```bash
# 檢查 APP_KEY
docker compose -f docker-compose.prod.yml exec app cat /var/www/html/.env | grep APP_KEY

# 如果需要，更新 .env.production 檔案中的 APP_KEY
# APP_KEY=base64:s00TW1L0A+FfAVjWBdE5j3VSdlLnWoliPYqXTxU7MEM=
```

### 5. 路由快取問題

**錯誤訊息：** `Unable to prepare route [...] for serialization`

**解決方案：**
```bash
# 清除路由快取
docker compose -f docker-compose.prod.yml exec app php artisan route:clear

# 重新快取路由（生產環境）
docker compose -f docker-compose.prod.yml exec app php artisan route:cache
```

## 除錯指令

### 檢查容器狀態
```bash
docker compose -f docker-compose.prod.yml ps
```

### 檢查容器日誌
```bash
# 檢查應用程式日誌
docker compose -f docker-compose.prod.yml logs app --tail=50

# 檢查 MySQL 日誌
docker compose -f docker-compose.prod.yml logs mysql --tail=50

# 檢查 Redis 日誌
docker compose -f docker-compose.prod.yml logs redis --tail=50

# 檢查 Nginx 日誌
docker compose -f docker-compose.prod.yml logs nginx --tail=50
```

### 進入容器除錯
```bash
# 進入應用程式容器
docker compose -f docker-compose.prod.yml exec app bash

# 進入 MySQL 容器
docker compose -f docker-compose.prod.yml exec mysql bash

# 進入 Redis 容器
docker compose -f docker-compose.prod.yml exec redis sh
```

### 測試連線
```bash
# 測試資料庫連線
docker compose -f docker-compose.prod.yml exec app php artisan tinker --execute="use Illuminate\Support\Facades\DB; DB::connection()->getPdo(); echo 'Database OK';"

# 測試 Redis 連線
docker compose -f docker-compose.prod.yml exec app php artisan tinker --execute="use Illuminate\Support\Facades\Redis; echo Redis::ping();"

# 測試應用程式健康狀態
curl http://localhost/health
```

### 清除快取
```bash
# 清除所有快取
docker compose -f docker-compose.prod.yml exec app php artisan config:clear
docker compose -f docker-compose.prod.yml exec app php artisan route:clear
docker compose -f docker-compose.prod.yml exec app php artisan view:clear
docker compose -f docker-compose.prod.yml exec app php artisan cache:clear

# 重新快取（生產環境）
docker compose -f docker-compose.prod.yml exec app php artisan config:cache
docker compose -f docker-compose.prod.yml exec app php artisan route:cache
docker compose -f docker-compose.prod.yml exec app php artisan view:cache
```

## 完整重新部署

如果遇到嚴重問題，可以執行完整重新部署：

```bash
# 停止並移除所有容器
./quick-deploy.sh prod --down

# 清理 Docker 資源（謹慎使用）
docker system prune -f

# 重新建置並部署
./quick-deploy.sh prod --build
```

## 監控和維護

### 定期檢查
```bash
# 執行部署後驗證
./scripts/post-deploy-verify.sh prod

# 檢查磁碟使用量
df -h

# 檢查記憶體使用量
free -h

# 檢查 Docker 資源使用量
docker stats --no-stream
```

### 日誌輪轉
確保設定適當的日誌輪轉以避免磁碟空間不足：

```bash
# 檢查日誌大小
docker compose -f docker-compose.prod.yml exec app du -sh /var/www/html/storage/logs/

# 清理舊日誌（如需要）
docker compose -f docker-compose.prod.yml exec app find /var/www/html/storage/logs/ -name "*.log" -mtime +7 -delete
```

## 聯絡支援

如果問題仍然存在，請提供以下資訊：

1. 錯誤訊息的完整內容
2. 容器狀態：`docker compose -f docker-compose.prod.yml ps`
3. 相關日誌：`docker compose -f docker-compose.prod.yml logs [service_name] --tail=100`
4. 系統資訊：`uname -a` 和 `docker version`