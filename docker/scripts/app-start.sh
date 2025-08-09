#!/bin/bash

# Laravel 應用程式啟動腳本 - 簡化版本

set -e

echo "正在啟動 Laravel 應用程式..."

# 讀取 secrets 並設定環境變數（如果存在）
if [ -f "/run/secrets/mysql_password" ]; then
    export DB_PASSWORD=$(cat /run/secrets/mysql_password)
    echo "✓ 已從 secrets 設定資料庫密碼"
fi

if [ -f "/run/secrets/app_key" ]; then
    export APP_KEY=$(cat /run/secrets/app_key)
    echo "✓ 已從 secrets 設定應用程式金鑰"
fi

# Redis 密碼直接從 .env 檔案讀取，不再使用 secrets
echo "✓ Redis 密碼從 .env 檔案讀取"

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