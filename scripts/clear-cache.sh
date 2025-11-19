#!/bin/bash

echo "正在清除 Laravel 快取..."

# 清除所有快取
docker exec laravel_admin_app_prod php artisan optimize:clear

# 重新產生自動載入檔案
docker exec laravel_admin_app_prod composer dump-autoload

echo "快取清除完成！"