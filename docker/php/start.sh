#!/bin/sh

echo "🚀 啟動 Laravel Admin 生產環境..."

# 設定 APP_KEY 從 secrets 檔案
if [ -f /run/secrets/app_key ]; then
    export APP_KEY=$(cat /run/secrets/app_key)
    echo "✅ 從 secrets 載入 APP_KEY"
elif [ -n "$APP_KEY_FILE" ] && [ -f "$APP_KEY_FILE" ]; then
    export APP_KEY=$(cat "$APP_KEY_FILE")
    echo "✅ 從環境變數指定的檔案載入 APP_KEY"
fi

# 設定資料庫密碼從 secrets 檔案
if [ -f /run/secrets/mysql_password ]; then
    export DB_PASSWORD=$(cat /run/secrets/mysql_password)
    echo "✅ 從 secrets 載入資料庫密碼"
elif [ -n "$DB_PASSWORD_FILE" ] && [ -f "$DB_PASSWORD_FILE" ]; then
    export DB_PASSWORD=$(cat "$DB_PASSWORD_FILE")
    echo "✅ 從環境變數指定的檔案載入資料庫密碼"
fi

# 設定 Redis 密碼從 secrets 檔案
if [ -f /run/secrets/redis_password ]; then
    export REDIS_PASSWORD=$(cat /run/secrets/redis_password)
    echo "✅ 從 secrets 載入 Redis 密碼"
elif [ -n "$REDIS_PASSWORD_FILE" ] && [ -f "$REDIS_PASSWORD_FILE" ]; then
    export REDIS_PASSWORD=$(cat "$REDIS_PASSWORD_FILE")
    echo "✅ 從環境變數指定的檔案載入 Redis 密碼"
elif [ -f /var/www/html/secrets/redis_password.txt ]; then
    export REDIS_PASSWORD=$(cat /var/www/html/secrets/redis_password.txt)
    echo "✅ 從本地 secrets 檔案載入 Redis 密碼"
fi

# 執行環境變數設定腳本
echo "🔧 設定環境變數..."
/var/www/html/docker/php/env-setup.sh

# 建立 Supervisor 環境變數檔案
ENV_FILE="/tmp/supervisor.env"
cat > $ENV_FILE << EOF
export APP_NAME="${APP_NAME:-Laravel Admin}"
export APP_ENV="${APP_ENV:-production}"
export APP_KEY="${APP_KEY:-}"
export APP_DEBUG="${APP_DEBUG:-false}"
export APP_URL="${APP_URL:-http://localhost}"
export LOG_CHANNEL="${LOG_CHANNEL:-stack}"
export LOG_DEPRECATIONS_CHANNEL="${LOG_DEPRECATIONS_CHANNEL:-null}"
export LOG_LEVEL="${LOG_LEVEL:-error}"
export DB_CONNECTION="${DB_CONNECTION:-mysql}"
export DB_HOST="${DB_HOST:-mysql}"
export DB_PORT="${DB_PORT:-3306}"
export DB_DATABASE="${DB_DATABASE:-laravel_admin}"
export DB_USERNAME="${DB_USERNAME:-laravel}"
export DB_PASSWORD="${DB_PASSWORD:-}"
export BROADCAST_DRIVER="${BROADCAST_DRIVER:-log}"
export CACHE_DRIVER="${CACHE_DRIVER:-redis}"
export FILESYSTEM_DISK="${FILESYSTEM_DISK:-local}"
export QUEUE_CONNECTION="${QUEUE_CONNECTION:-redis}"
export SESSION_DRIVER="${SESSION_DRIVER:-redis}"
export SESSION_LIFETIME="${SESSION_LIFETIME:-120}"
export MEMCACHED_HOST="${MEMCACHED_HOST:-127.0.0.1}"
export REDIS_HOST="${REDIS_HOST:-redis}"
export REDIS_PASSWORD="${REDIS_PASSWORD:-}"
export REDIS_PORT="${REDIS_PORT:-6379}"
export MAIL_MAILER="${MAIL_MAILER:-smtp}"
export MAIL_HOST="${MAIL_HOST:-mailhog}"
export MAIL_PORT="${MAIL_PORT:-1025}"
export MAIL_USERNAME="${MAIL_USERNAME:-null}"
export MAIL_PASSWORD="${MAIL_PASSWORD:-null}"
export MAIL_ENCRYPTION="${MAIL_ENCRYPTION:-null}"
export MAIL_FROM_ADDRESS="${MAIL_FROM_ADDRESS:-hello@example.com}"
export AWS_ACCESS_KEY_ID="${AWS_ACCESS_KEY_ID:-}"
export AWS_SECRET_ACCESS_KEY="${AWS_SECRET_ACCESS_KEY:-}"
export AWS_DEFAULT_REGION="${AWS_DEFAULT_REGION:-us-east-1}"
export AWS_BUCKET="${AWS_BUCKET:-}"
export PUSHER_APP_ID="${PUSHER_APP_ID:-}"
export PUSHER_APP_KEY="${PUSHER_APP_KEY:-}"
export PUSHER_APP_SECRET="${PUSHER_APP_SECRET:-}"
export PUSHER_HOST="${PUSHER_HOST:-}"
export PUSHER_PORT="${PUSHER_PORT:-443}"
export PUSHER_SCHEME="${PUSHER_SCHEME:-https}"
export PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER:-mt1}"
export VITE_PUSHER_APP_KEY="${VITE_PUSHER_APP_KEY:-}"
export VITE_PUSHER_HOST="${VITE_PUSHER_HOST:-}"
export VITE_PUSHER_PORT="${VITE_PUSHER_PORT:-443}"
export VITE_PUSHER_SCHEME="${VITE_PUSHER_SCHEME:-https}"
export VITE_PUSHER_APP_CLUSTER="${VITE_PUSHER_APP_CLUSTER:-mt1}"
EOF

echo "✅ 環境變數檔案已建立"

# 等待資料庫和 Redis 準備就緒
echo "⏳ 等待服務準備就緒..."
sleep 5

# 清除 Laravel 配置快取並重新生成
echo "🧹 清理並重新生成 Laravel 配置快取..."
source $ENV_FILE
php /var/www/html/artisan config:clear
php /var/www/html/artisan config:cache

echo "🎉 啟動 Supervisor..."

# 啟動 Supervisor
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf