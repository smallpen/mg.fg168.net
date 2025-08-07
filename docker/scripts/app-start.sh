#!/bin/bash

# Laravel 應用程式啟動腳本 - 處理 Docker secrets

set -e

echo "正在啟動 Laravel 應用程式..."

# 讀取 secrets 並設定環境變數
if [ -f "/run/secrets/redis_password" ]; then
    export REDIS_PASSWORD=$(cat /run/secrets/redis_password)
    echo "✓ 已設定 Redis 密碼"
else
    echo "⚠ 警告: 找不到 Redis 密碼 secrets"
fi

if [ -f "/run/secrets/mysql_password" ]; then
    export DB_PASSWORD=$(cat /run/secrets/mysql_password)
    echo "✓ 已設定資料庫密碼"
else
    echo "⚠ 警告: 找不到資料庫密碼 secrets"
fi

if [ -f "/run/secrets/app_key" ]; then
    export APP_KEY=$(cat /run/secrets/app_key)
    echo "✓ 已設定應用程式金鑰"
else
    echo "⚠ 警告: 找不到應用程式金鑰 secrets"
fi

# 等待資料庫和 Redis 準備就緒
echo "等待服務準備就緒..."
sleep 10

# 清除配置快取
echo "清除快取..."
php artisan config:clear || true
php artisan cache:clear || true

# 執行資料庫遷移（如果需要）
echo "檢查資料庫遷移..."
php artisan migrate --force || echo "遷移失敗或已是最新版本"

# 快取配置
echo "快取配置..."
php artisan config:cache
php artisan route:cache || true
php artisan view:cache || true

# 建立儲存連結
php artisan storage:link || true

echo "✓ Laravel 應用程式初始化完成"

# 啟動 PHP-FPM
echo "啟動 PHP-FPM..."
exec php-fpm