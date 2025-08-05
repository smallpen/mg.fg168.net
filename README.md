# Laravel 管理系統

一個功能完整的 Laravel 管理後台系統，具備使用者管理、角色權限控制、系統監控等功能。

## 功能特色

- 🔐 完整的認證和授權系統
- 👥 使用者管理和角色權限控制
- 📊 系統監控和健康檢查
- 🔄 自動備份和恢復
- 🚀 CI/CD 自動化部署
- 📱 響應式設計界面
- 🛡️ 安全性最佳實踐

## 技術架構

- **後端**: Laravel 10.x
- **前端**: Livewire 3.x + Alpine.js
- **資料庫**: MySQL 8.0
- **快取**: Redis 7.x
- **容器化**: Docker + Docker Compose
- **CI/CD**: GitHub Actions

## 快速開始

### 開發環境

1. 複製專案
```bash
git clone <repository-url>
cd mg.fg168.net
```

2. 安裝依賴
```bash
composer install
npm install
```

3. 環境設定
```bash
cp .env.example .env
php artisan key:generate
```

4. 資料庫設定
```bash
php artisan migrate
php artisan db:seed
```

5. 編譯前端資源
```bash
npm run dev
```

6. 啟動開發伺服器
```bash
php artisan serve
```

### Docker 開發環境

```bash
# 啟動開發環境
docker-compose up -d

# 執行遷移
docker-compose exec app php artisan migrate

# 執行種子
docker-compose exec app php artisan db:seed
```

## 部署

### 測試環境部署

```bash
# 使用部署腳本
./scripts/deploy.sh staging

# 或使用 Docker Compose
docker-compose -f docker-compose.staging.yml up -d
```

### 生產環境部署

1. 設定秘密檔案（參考 `secrets/README.md`）
2. 執行部署腳本
```bash
./scripts/deploy.sh production
```

### CI/CD 自動部署

專案已配置 GitHub Actions 自動化部署：
- `develop` 分支推送時自動部署到測試環境
- `main` 分支推送時自動部署到生產環境

## 監控和維護

### 健康檢查端點

- `/health` - 基本健康檢查
- `/health/detailed` - 詳細系統狀態
- `/health/metrics` - 效能指標
- `/health/database` - 資料庫狀態
- `/health/redis` - Redis 狀態

### 日誌檔案

- `storage/logs/laravel.log` - 應用程式日誌
- `storage/logs/admin_activity.log` - 管理員活動日誌
- `storage/logs/security.log` - 安全事件日誌
- `storage/logs/performance.log` - 效能監控日誌
- `storage/logs/backup.log` - 備份操作日誌

### 備份

```bash
# 手動執行完整備份
docker-compose exec app php artisan backup:run

# 列出可用備份
docker-compose exec app php artisan backup:list

# 恢復備份
docker-compose exec app php artisan backup:restore {backup-name}
```

## 開發指南

### 程式碼風格

專案使用 PHP CS Fixer 和 PHPStan 進行程式碼品質檢查：

```bash
# 檢查程式碼風格
./vendor/bin/php-cs-fixer fix --dry-run --diff

# 修復程式碼風格
./vendor/bin/php-cs-fixer fix

# 靜態分析
./vendor/bin/phpstan analyse
```

### 測試

```bash
# 執行所有測試
php artisan test

# 執行特定測試
php artisan test --filter UserTest

# 產生測試覆蓋率報告
php artisan test --coverage
```

## 安全性

- 所有敏感資料使用環境變數或秘密檔案管理
- 實作 CSRF 保護和 XSS 防護
- 使用 HTTPS 和安全標頭
- 定期安全性掃描和依賴更新
- 完整的審計日誌記錄

## 授權

此專案採用 MIT 授權條款。

## 貢獻

歡迎提交 Pull Request 或建立 Issue 來改善此專案。

## 支援

如有問題或需要協助，請建立 Issue 或聯繫維護團隊。