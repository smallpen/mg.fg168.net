#!/bin/bash

# Laravel Queue Worker 啟動腳本 - 處理 Docker secrets

set -e

echo "正在啟動 Laravel Queue Worker..."

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

# 等待主應用程式準備就緒
echo "等待主應用程式準備就緒..."
sleep 30

# 清除快取
echo "清除快取..."
php artisan config:clear || true
php artisan cache:clear || true

# 快取配置
echo "快取配置..."
php artisan config:cache

echo "✓ Queue Worker 初始化完成"

# 測試環境變數是否正確設定
echo "測試環境變數:"
echo "REDIS_PASSWORD: ${REDIS_PASSWORD:0:4}****"
echo "DB_PASSWORD: ${DB_PASSWORD:0:4}****"
echo "APP_KEY: ${APP_KEY:0:10}****"

# 啟動 Queue Worker，確保環境變數傳遞
echo "啟動 Queue Worker..."
exec php artisan queue:work --sleep=3 --tries=3 --max-time=3600 --memory=256 --verbose