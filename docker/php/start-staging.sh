#!/bin/sh

# 測試環境啟動腳本

echo "🚀 啟動 Laravel Admin 測試環境..."

# 等待資料庫準備就緒
echo "⏳ 等待 MySQL 資料庫準備就緒..."
while ! mysqladmin ping -h mysql -u root -p${STAGING_MYSQL_ROOT_PASSWORD} --silent; do
    echo "等待 MySQL 連線..."
    sleep 2
done
echo "✅ MySQL 資料庫已準備就緒"

# 等待 Redis 準備就緒
echo "⏳ 等待 Redis 準備就緒..."
while ! redis-cli -h redis -a ${STAGING_REDIS_PASSWORD} ping > /dev/null 2>&1; do
    echo "等待 Redis 連線..."
    sleep 2
done
echo "✅ Redis 已準備就緒"

# 設定應用程式權限
echo "🔧 設定檔案權限..."
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html/storage
chmod -R 755 /var/www/html/bootstrap/cache

# 建立符號連結
echo "🔗 建立儲存符號連結..."
php artisan storage:link --force

# 清理快取
echo "🧹 清理應用程式快取..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# 執行資料庫遷移
echo "📊 執行資料庫遷移..."
php artisan migrate --force

# 執行資料庫種子 (僅在資料庫為空時)
echo "🌱 檢查是否需要執行資料庫種子..."
USER_COUNT=$(php artisan tinker --execute="echo App\Models\User::count();")
if [ "$USER_COUNT" -eq "0" ]; then
    echo "執行資料庫種子..."
    php artisan db:seed --force
else
    echo "資料庫已有資料，跳過種子執行"
fi

# 快取設定檔案
echo "⚡ 快取設定檔案..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 最佳化 Composer 自動載入
echo "🎯 最佳化 Composer 自動載入..."
composer dump-autoload --optimize

# 設定 cron 任務
echo "⏰ 設定排程任務..."
echo "* * * * * www-data cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1" >> /etc/crontabs/root

# 啟動 cron 服務
crond -b

# 檢查應用程式健康狀態
echo "🏥 檢查應用程式健康狀態..."
php artisan tinker --execute="
try {
    \DB::connection()->getPdo();
    echo '✅ 資料庫連線正常';
} catch (Exception \$e) {
    echo '❌ 資料庫連線失敗: ' . \$e->getMessage();
    exit(1);
}

try {
    \Cache::put('health_check', 'ok', 60);
    if (\Cache::get('health_check') === 'ok') {
        echo '✅ 快取系統正常';
    } else {
        echo '❌ 快取系統異常';
        exit(1);
    }
} catch (Exception \$e) {
    echo '❌ 快取系統失敗: ' . \$e->getMessage();
    exit(1);
}
"

echo "🎉 測試環境啟動完成！"
echo "📊 系統資訊："
echo "   - PHP 版本: $(php -v | head -n1)"
echo "   - Laravel 版本: $(php artisan --version)"
echo "   - 環境: $(php artisan tinker --execute='echo app()->environment();')"
echo "   - 除錯模式: $(php artisan tinker --execute='echo config(\"app.debug\") ? \"開啟\" : \"關閉\";')"

# 啟動 PHP-FPM
echo "🚀 啟動 PHP-FPM..."
exec php-fpm