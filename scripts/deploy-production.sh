#!/bin/bash

# Laravel Admin 系統生產環境部署腳本

echo "🚀 開始生產環境部署..."

# 1. 建置前端資源
echo "📦 建置前端資源..."
docker-compose run --rm node npm run build

# 2. 發布 Livewire 資源
echo "📋 發布 Livewire 資源..."
docker-compose exec app php artisan livewire:publish --assets

# 3. 清除所有快取
echo "🧹 清除快取..."
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan view:clear
docker-compose exec app php artisan route:clear

# 4. 優化生產環境
echo "⚡ 優化生產環境..."
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache

# 5. 執行資料庫遷移（如果需要）
echo "🗄️ 檢查資料庫遷移..."
docker-compose exec app php artisan migrate --force

# 6. 重新啟動服務
echo "🔄 重新啟動服務..."
docker-compose restart app nginx

echo "✅ 生產環境部署完成！"
echo "🌐 應用程式現在可以在生產環境中使用"