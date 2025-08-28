#!/bin/bash

# Laravel Admin 系統生產環境除錯腳本

set -e

echo "🔍 Laravel Admin 系統生產環境除錯資訊"
echo "========================================"

# 檢查容器狀態
echo ""
echo "📊 容器狀態:"
docker-compose -f docker-compose.prod.yml ps

# 檢查 secrets 檔案
echo ""
echo "🔐 Secrets 檔案狀態:"
SECRETS_DIR="./secrets"
for file in "$SECRETS_DIR"/*.txt; do
    if [ -f "$file" ]; then
        filename=$(basename "$file")
        size=$(stat -c%s "$file")
        echo "✓ $filename ($size bytes)"
    fi
done

# 檢查應用程式容器環境變數
echo ""
echo "🌍 應用程式容器環境變數:"
docker exec laravel_admin_app_prod env | grep -E "(REDIS|DB|APP_)" | sort || echo "無法取得環境變數"

# 檢查 Redis 連線
echo ""
echo "🔴 Redis 連線測試:"
if docker exec laravel_admin_redis_prod redis-cli -a "$(cat secrets/redis_password.txt)" ping 2>/dev/null; then
    echo "✅ Redis 伺服器回應正常"
else
    echo "❌ Redis 伺服器無回應"
fi

# 從應用程式容器測試 Redis
echo ""
echo "🔴 從應用程式測試 Redis:"
docker exec laravel_admin_app_prod php artisan tinker --execute="
try {
    \$result = Redis::ping();
    echo 'Redis ping 結果: ' . (\$result ? 'PONG' : 'No response') . PHP_EOL;
    echo 'Redis 連線成功' . PHP_EOL;
} catch (Exception \$e) {
    echo 'Redis 連線錯誤: ' . \$e->getMessage() . PHP_EOL;
}
" 2>&1 || echo "無法執行 Redis 測試"

# 檢查資料庫連線
echo ""
echo "🗄️ 資料庫連線測試:"
docker exec laravel_admin_app_prod php artisan tinker --execute="
try {
    \$pdo = DB::connection()->getPdo();
    echo '資料庫連線成功' . PHP_EOL;
    echo '資料庫版本: ' . \$pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . PHP_EOL;
} catch (Exception \$e) {
    echo '資料庫連線錯誤: ' . \$e->getMessage() . PHP_EOL;
}
" 2>&1 || echo "無法執行資料庫測試"

# 檢查 Laravel 配置
echo ""
echo "⚙️ Laravel 配置:"
docker exec laravel_admin_app_prod php artisan config:show database.redis.default 2>/dev/null || echo "無法取得 Redis 配置"

# 顯示最近的日誌
echo ""
echo "📋 最近的應用程式日誌:"
docker-compose -f docker-compose.prod.yml logs --tail=20 app 2>/dev/null || echo "無法取得應用程式日誌"

echo ""
echo "📋 最近的 Redis 日誌:"
docker-compose -f docker-compose.prod.yml logs --tail=10 redis 2>/dev/null || echo "無法取得 Redis 日誌"

echo ""
echo "🔍 除錯完成"