#!/bin/sh

# Laravel 權限修復腳本
# 確保 storage 和 bootstrap/cache 目錄有正確的權限

set -e

echo "修復 Laravel 檔案權限..."

# 修復 storage 目錄權限
if [ -d "/var/www/html/storage" ]; then
    chown -R www-data:www-data /var/www/html/storage
    chmod -R 775 /var/www/html/storage
    echo "✓ storage 目錄權限已修復"
fi

# 修復 bootstrap/cache 目錄權限
if [ -d "/var/www/html/bootstrap/cache" ]; then
    chown -R www-data:www-data /var/www/html/bootstrap/cache
    chmod -R 775 /var/www/html/bootstrap/cache
    echo "✓ bootstrap/cache 目錄權限已修復"
fi

# 確保 .env 檔案可讀
if [ -f "/var/www/html/.env" ]; then
    chown www-data:www-data /var/www/html/.env
    chmod 644 /var/www/html/.env
    echo "✓ .env 檔案權限已修復"
fi

echo "權限修復完成！"