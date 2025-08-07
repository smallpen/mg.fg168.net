# Laravel Admin 系統部署最佳實踐

## 概覽

本文件提供 Laravel Admin 系統部署的最佳實踐，特別針對避免常見問題和確保部署穩定性。

## 部署前準備清單

### 1. 環境檢查

- [ ] 確認 Docker 和 Docker Compose 版本符合需求
- [ ] 檢查可用磁碟空間（至少 10GB）
- [ ] 驗證網路連線正常
- [ ] 確認防火牆設定正確

### 2. 檔案檢查

- [ ] `.env` 檔案已正確設定
- [ ] 所有 `secrets/*.txt` 檔案已建立且包含有效內容
- [ ] `docker-compose.prod.yml` 檔案存在且正確
- [ ] SSL 憑證檔案已準備（生產環境）

### 3. 設定檢查

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] 資料庫密碼與 secrets 檔案一致
- [ ] Redis 密碼與 secrets 檔案一致
- [ ] APP_KEY 已正確生成

## 部署流程最佳實踐

### 1. 使用自動化腳本

**推薦做法**：
```bash
# 執行完整的自動化部署
./quick-deploy.sh prod --build
```

**避免做法**：
```bash
# 手動執行每個步驟（容易出錯）
docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml up -d
# ... 其他手動步驟
```

### 2. 部署前檢查

**推薦做法**：
```bash
# 總是執行部署前檢查
./scripts/pre-deploy-check.sh
```

**避免做法**：
```bash
# 跳過檢查直接部署
./quick-deploy.sh prod --skip-checks
```

### 3. 套件管理

**推薦做法**：
```bash
# 在 Dockerfile 中確保清除套件發現快取
RUN rm -f bootstrap/cache/packages.php bootstrap/cache/services.php \
    && php artisan package:discover --ansi
```

**避免做法**：
```bash
# 不清除快取，可能導致開發套件在生產環境中被載入
RUN composer install --no-dev --optimize-autoloader
```

## 常見問題預防

### 1. Laravel Dusk Service Provider 問題

**問題描述**：
容器健康檢查失敗，錯誤訊息：`Class "Laravel\Dusk\DuskServiceProvider" not found`

**預防措施**：
1. 在 Dockerfile 中清除套件發現快取
2. 在容器啟動時重新發現套件
3. 確保使用 `composer install --no-dev`

**實作方式**：
```dockerfile
# Dockerfile.prod
RUN composer install --no-dev --optimize-autoloader --no-interaction
RUN rm -f bootstrap/cache/packages.php bootstrap/cache/services.php \
    && php artisan package:discover --ansi
```

```bash
# start.sh
rm -f /var/www/html/bootstrap/cache/packages.php /var/www/html/bootstrap/cache/services.php
php /var/www/html/artisan package:discover --ansi
```

### 2. 快取問題

**問題描述**：
配置或路由快取導致的異常行為

**預防措施**：
1. 部署時總是清除所有快取
2. 在生產環境中重新建立快取
3. 確保快取目錄權限正確

**實作方式**：
```bash
# 清除所有快取
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# 重新建立快取（生產環境）
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3. 權限問題

**問題描述**：
檔案權限不正確導致的寫入失敗

**預防措施**：
1. 在 Dockerfile 中設定正確權限
2. 確保 storage 和 bootstrap/cache 目錄可寫
3. 使用正確的使用者執行容器

**實作方式**：
```dockerfile
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache
```

### 4. 資料庫連線問題

**問題描述**：
應用程式無法連接到資料庫

**預防措施**：
1. 確保密碼在 .env 和 secrets 檔案中一致
2. 等待資料庫完全啟動後再執行遷移
3. 使用健康檢查確認資料庫狀態

**實作方式**：
```bash
# 等待資料庫準備就緒
sleep 15

# 測試資料庫連線
php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database connected';"
```

## 部署後驗證

### 1. 自動驗證

**推薦做法**：
```bash
# 執行完整的部署後驗證
./scripts/post-deploy-verify.sh prod
```

### 2. 手動驗證清單

- [ ] 所有容器都在運行且健康
- [ ] 應用程式可以正常存取
- [ ] 資料庫連線正常
- [ ] Redis 連線正常
- [ ] 日誌中沒有錯誤訊息
- [ ] 健康檢查端點回應正常

### 3. 效能驗證

- [ ] 頁面載入時間合理
- [ ] 資料庫查詢效能正常
- [ ] 記憶體使用量在預期範圍內
- [ ] CPU 使用量正常

## 監控和維護

### 1. 日誌監控

**設定日誌輪轉**：
```bash
# 定期清理大型日誌檔案
docker logs laravel_admin_app_prod --tail=1000 > app.log
docker logs laravel_admin_app_prod --since="24h" > app_recent.log
```

**監控關鍵錯誤**：
```bash
# 監控應用程式錯誤
docker logs laravel_admin_app_prod | grep -i "error\|exception\|fatal"

# 監控 Nginx 錯誤
docker logs laravel_admin_nginx_prod | grep -i "error"
```

### 2. 健康檢查

**定期執行健康檢查**：
```bash
# 設定 cron job 定期檢查
0 */6 * * * /path/to/project/scripts/post-deploy-verify.sh prod > /var/log/health-check.log 2>&1
```

**監控容器狀態**：
```bash
# 檢查不健康的容器
docker ps --filter "health=unhealthy"

# 檢查容器重啟次數
docker ps --format "table {{.Names}}\t{{.Status}}"
```

### 3. 備份策略

**自動備份**：
```bash
# 設定每日備份
0 2 * * * /path/to/project/docker/scripts/backup.sh > /var/log/backup.log 2>&1
```

**備份驗證**：
```bash
# 定期測試備份恢復
# 在測試環境中恢復備份並驗證完整性
```

## 更新和升級

### 1. 應用程式更新

**安全更新流程**：
1. 在測試環境中測試更新
2. 建立完整備份
3. 執行部署前檢查
4. 使用藍綠部署或滾動更新
5. 執行部署後驗證
6. 監控系統穩定性

### 2. 依賴套件更新

**定期更新**：
```bash
# 更新 Composer 依賴
composer update --no-dev

# 更新 npm 依賴
npm update

# 重新建置映像
./quick-deploy.sh prod --build
```

### 3. 系統更新

**Docker 映像更新**：
```bash
# 拉取最新的基礎映像
docker pull php:8.2-fpm-alpine
docker pull nginx:alpine
docker pull mysql:8.0
docker pull redis:7-alpine

# 重新建置應用程式映像
./quick-deploy.sh prod --build
```

## 安全最佳實踐

### 1. 密碼管理

- 使用強密碼（至少 16 個字元）
- 定期更換密碼
- 不要在版本控制中儲存密碼
- 使用 Docker secrets 管理敏感資訊

### 2. 網路安全

- 使用 HTTPS（生產環境）
- 設定適當的防火牆規則
- 限制不必要的端口存取
- 使用內部網路進行服務間通訊

### 3. 容器安全

- 使用非 root 使用者執行應用程式
- 定期更新基礎映像
- 掃描映像漏洞
- 限制容器權限

## 故障排除指南

### 1. 容器無法啟動

**檢查步驟**：
1. 查看容器日誌
2. 檢查磁碟空間
3. 驗證配置檔案
4. 檢查端口衝突

### 2. 健康檢查失敗

**檢查步驟**：
1. 執行手動健康檢查指令
2. 查看應用程式日誌
3. 檢查套件發現快取
4. 驗證環境變數

### 3. 效能問題

**檢查步驟**：
1. 監控資源使用量
2. 分析慢查詢日誌
3. 檢查快取配置
4. 優化資料庫索引

## 總結

遵循這些最佳實踐可以：

1. **提高部署成功率**：透過自動化檢查和驗證
2. **減少停機時間**：透過預防性措施和快速故障排除
3. **確保系統穩定性**：透過持續監控和維護
4. **提升安全性**：透過安全配置和定期更新
5. **簡化維護工作**：透過標準化流程和自動化工具

記住：**預防勝於治療**。投資時間在部署前的準備和檢查，可以避免大部分的生產環境問題。