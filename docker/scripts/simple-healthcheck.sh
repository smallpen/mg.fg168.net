#!/bin/bash

# 最簡單的健康檢查腳本
set -e

# 檢查 Laravel 應用程式是否能正常執行
if php /var/www/html/artisan tinker --execute="echo 'OK';" >/dev/null 2>&1; then
    echo "✓ Laravel 應用程式正常"
    exit 0
else
    echo "✗ Laravel 應用程式異常"
    exit 1
fi