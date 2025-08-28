#!/bin/bash

# Laravel Admin 系統生產環境部署腳本

set -e

echo "🚀 開始部署 Laravel Admin 系統到生產環境..."

# 檢查必要的 secrets 檔案
echo "📋 檢查 secrets 檔案..."
SECRETS_DIR="./secrets"
REQUIRED_SECRETS=("mysql_root_password.txt" "mysql_password.txt" "redis_password.txt" "app_key.txt")

for secret in "${REQUIRED_SECRETS[@]}"; do
    if [ ! -f "$SECRETS_DIR/$secret" ]; then
        echo "❌ 錯誤: 找不到必要的 secrets 檔案: $secret"
        echo "請確保 $SECRETS_DIR/$secret 檔案存在"
        exit 1
    else
        echo "✓ $secret"
    fi
done

# 停止現有容器
echo "🛑 停止現有容器..."
docker-compose -f docker-compose.prod.yml down || true

# 清理未使用的映像檔
echo "🧹 清理未使用的 Docker 映像檔..."
docker system prune -f || true

# 建置並啟動容器
echo "🔨 建置並啟動容器..."
docker-compose -f docker-compose.prod.yml up -d --build

# 等待服務啟動
echo "⏳ 等待服務啟動..."
sleep 30

# 檢查容器狀態
echo "📊 檢查容器狀態..."
docker-compose -f docker-compose.prod.yml ps

# 測試 Redis 連線
echo "🔍 測試 Redis 連線..."
if docker exec laravel_admin_app_prod php artisan tinker --execute="echo Redis::ping() ? 'Redis 連線成功' : 'Redis 連線失敗';" 2>/dev/null; then
    echo "✅ Redis 連線測試成功"
else
    echo "❌ Redis 連線測試失敗"
    echo "檢查容器日誌:"
    docker-compose -f docker-compose.prod.yml logs app
    exit 1
fi

# 測試資料庫連線
echo "🔍 測試資料庫連線..."
if docker exec laravel_admin_app_prod php artisan tinker --execute="DB::connection()->getPdo(); echo '資料庫連線成功';" 2>/dev/null; then
    echo "✅ 資料庫連線測試成功"
else
    echo "❌ 資料庫連線測試失敗"
    echo "檢查容器日誌:"
    docker-compose -f docker-compose.prod.yml logs mysql
    exit 1
fi

# 顯示應用程式狀態
echo "📈 應用程式狀態:"
docker exec laravel_admin_app_prod php artisan --version || true

echo ""
echo "🎉 部署完成！"
echo ""
echo "📝 有用的命令:"
echo "  查看日誌: docker-compose -f docker-compose.prod.yml logs -f"
echo "  進入應用程式容器: docker exec -it laravel_admin_app_prod bash"
echo "  重啟服務: docker-compose -f docker-compose.prod.yml restart"
echo "  停止服務: docker-compose -f docker-compose.prod.yml down"
echo ""