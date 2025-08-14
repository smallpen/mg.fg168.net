# 開發環境 Makefile
# 提供便捷的開發命令

.PHONY: help setup setup-fresh setup-users check test clean

# 預設目標
help: ## 顯示幫助訊息
	@echo "🚀 開發環境快速命令"
	@echo ""
	@echo "可用命令:"
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)
	@echo ""
	@echo "範例:"
	@echo "  make setup        # 建立開發資料"
	@echo "  make setup-fresh  # 完全重建資料庫"
	@echo "  make check        # 檢查資料狀態"
	@echo "  make test         # 執行測試"

setup: ## 建立/更新開發測試資料
	@echo "🌱 建立開發測試資料..."
	@docker-compose exec app php artisan dev:setup --force

setup-fresh: ## 清空資料庫並重新建立所有資料
	@echo "🔄 完全重建資料庫..."
	@docker-compose exec app php artisan dev:setup --fresh --force

setup-users: ## 只重建使用者資料
	@echo "👥 重建使用者資料..."
	@docker-compose exec app php artisan dev:setup --users-only --force

check: ## 檢查開發資料狀態
	@echo "📊 檢查開發資料狀態..."
	@docker-compose exec app php artisan dev:check --detailed

check-simple: ## 簡單檢查資料狀態
	@docker-compose exec app php artisan dev:check

test: ## 執行使用者管理相關測試
	@echo "🧪 執行測試..."
	@docker-compose exec app php artisan test tests/Feature/Livewire/Admin/Users/

test-all: ## 執行所有測試
	@echo "🧪 執行所有測試..."
	@docker-compose exec app php artisan test

clean: ## 清理快取和編譯檔案
	@echo "🧹 清理快取..."
	@docker-compose exec app php artisan cache:clear
	@docker-compose exec app php artisan config:clear
	@docker-compose exec app php artisan route:clear
	@docker-compose exec app php artisan view:clear

migrate: ## 執行資料庫遷移
	@echo "📊 執行資料庫遷移..."
	@docker-compose exec app php artisan migrate

migrate-fresh: ## 重新執行所有遷移
	@echo "🔄 重新執行所有遷移..."
	@docker-compose exec app php artisan migrate:fresh

seed: ## 執行基本種子檔案
	@echo "🌱 執行基本種子檔案..."
	@docker-compose exec app php artisan db:seed

seed-dev: ## 執行開發種子檔案
	@echo "🌱 執行開發種子檔案..."
	@docker-compose exec app php artisan db:seed --class=DevelopmentSeeder

tinker: ## 進入 Laravel Tinker
	@echo "🔧 進入 Laravel Tinker..."
	@docker-compose exec app php artisan tinker

logs: ## 查看應用程式日誌
	@echo "📋 查看應用程式日誌..."
	@docker-compose exec app tail -f storage/logs/laravel.log

docker-up: ## 啟動 Docker 容器
	@echo "🐳 啟動 Docker 容器..."
	@docker-compose up -d

docker-down: ## 停止 Docker 容器
	@echo "🐳 停止 Docker 容器..."
	@docker-compose down

docker-restart: ## 重啟 Docker 容器
	@echo "🐳 重啟 Docker 容器..."
	@docker-compose restart

docker-logs: ## 查看 Docker 日誌
	@echo "📋 查看 Docker 日誌..."
	@docker-compose logs -f

# 組合命令
full-reset: migrate-fresh seed-dev ## 完全重置：遷移 + 開發資料
	@echo "✅ 完全重置完成！"

quick-start: docker-up setup ## 快速啟動：Docker + 開發資料
	@echo "✅ 快速啟動完成！"
	@echo "🌐 管理後台: http://localhost/admin/login"
	@echo "👤 測試帳號: admin / password123"

# 開發工作流程
dev-reset: clean migrate-fresh seed-dev ## 開發重置：清理 + 遷移 + 資料
	@echo "✅ 開發環境重置完成！"

# 測試工作流程
test-setup: setup-fresh test ## 測試設定：重建資料 + 執行測試
	@echo "✅ 測試設定完成！"