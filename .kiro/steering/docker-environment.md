# Docker 環境開發規範

## 重要提醒

本專案使用 Docker 容器化開發環境，所有 Laravel Artisan 命令和 PHP 相關操作都必須在 Docker 容器中執行。

## 命令執行規範

### 必須使用的命令格式

所有 Laravel 相關命令都必須透過 Docker Compose 在 app 容器中執行：

```bash
# 正確的命令格式
docker-compose exec app php artisan [command]

# 錯誤的命令格式（不要直接執行）
php artisan [command]
```

### 常用命令範例

#### 測試相關
```bash
# 執行所有測試
docker-compose exec app php artisan test

# 執行特定測試檔案
docker-compose exec app php artisan test tests/Feature/SomeTest.php

# 執行測試並停止在第一個失敗
docker-compose exec app php artisan test --stop-on-failure

# 執行測試並顯示覆蓋率
docker-compose exec app php artisan test --coverage
```

#### 資料庫相關
```bash
# 執行遷移
docker-compose exec app php artisan migrate

# 回滾遷移
docker-compose exec app php artisan migrate:rollback

# 重新整理資料庫
docker-compose exec app php artisan migrate:fresh

# 執行 Seeder
docker-compose exec app php artisan db:seed
```

#### 程式碼生成
```bash
# 建立 Model
docker-compose exec app php artisan make:model ModelName

# 建立 Controller
docker-compose exec app php artisan make:controller ControllerName

# 建立 Livewire 元件
docker-compose exec app php artisan make:livewire ComponentName

# 建立 Migration
docker-compose exec app php artisan make:migration create_table_name
```

#### 快取和優化
```bash
# 清除快取
docker-compose exec app php artisan cache:clear

# 清除配置快取
docker-compose exec app php artisan config:clear

# 清除路由快取
docker-compose exec app php artisan route:clear

# 清除視圖快取
docker-compose exec app php artisan view:clear
```

#### 其他常用命令
```bash
# 查看路由列表
docker-compose exec app php artisan route:list

# 查看 Artisan 命令列表
docker-compose exec app php artisan list

# 進入 Tinker
docker-compose exec app php artisan tinker

# 查看佇列工作
docker-compose exec app php artisan queue:work
```

## 為什麼必須在容器中執行？

1. **環境一致性** - 確保開發、測試和生產環境的一致性
2. **依賴完整性** - 容器包含所有必要的 PHP 擴展和依賴
3. **資料庫連接** - 正確連接到容器化的資料庫服務
4. **配置正確性** - 使用容器中的環境變數和配置檔案
5. **檔案權限** - 避免檔案權限問題
6. **網路連接** - 正確的服務間網路連接

## 開發工作流程

### 啟動開發環境
```bash
# 啟動所有服務
docker-compose up -d

# 查看服務狀態
docker-compose ps
```

### 日常開發命令
```bash
# 進入 app 容器的 bash
docker-compose exec app bash

# 在容器內執行命令（進入容器後）
php artisan test
php artisan migrate
```

### 停止開發環境
```bash
# 停止所有服務
docker-compose down

# 停止並移除 volumes（謹慎使用）
docker-compose down -v
```

## 故障排除

### 常見問題

1. **權限問題**
   ```bash
   # 修復檔案權限
   docker-compose exec app chown -R www-data:www-data /var/www/html/storage
   docker-compose exec app chown -R www-data:www-data /var/www/html/bootstrap/cache
   ```

2. **容器未啟動**
   ```bash
   # 檢查容器狀態
   docker-compose ps
   
   # 查看容器日誌
   docker-compose logs app
   ```

3. **資料庫連接問題**
   ```bash
   # 檢查資料庫容器
   docker-compose logs db
   
   # 測試資料庫連接
   docker-compose exec app php artisan migrate:status
   ```

## 重要注意事項

- ⚠️ **絕對不要**直接在主機上執行 `php artisan` 命令
- ⚠️ **絕對不要**直接在主機上執行 `composer` 命令
- ⚠️ **絕對不要**直接在主機上執行 `npm` 命令（除非特別說明）
- ✅ **總是**使用 `docker-compose exec app` 前綴
- ✅ **總是**確認容器正在運行
- ✅ **總是**在容器環境中進行開發和測試

## 效能提示

- 使用 `docker-compose exec app bash` 進入容器後執行多個命令，避免重複的容器啟動開銷
- 定期清理未使用的 Docker 映像和容器以節省磁碟空間
- 使用 `.dockerignore` 檔案排除不必要的檔案，加快建置速度

遵循這些規範可以確保開發環境的穩定性和一致性，避免因環境差異導致的問題。