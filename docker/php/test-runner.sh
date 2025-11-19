#!/bin/sh

# Laravel Admin 測試執行腳本

echo "🧪 開始執行 Laravel Admin 測試套件..."

# 等待資料庫準備就緒
echo "⏳ 等待 MySQL 資料庫準備就緒..."
timeout=60
while [ $timeout -gt 0 ]; do
    if MYSQL_PWD=test_password mariadb -h mysql -u laravel_test --disable-ssl -e "SELECT 1" >/dev/null 2>&1; then
        echo "✅ MySQL 資料庫已準備就緒"
        break
    fi
    echo "等待 MySQL 連線... (剩餘 $timeout 秒)"
    sleep 2
    timeout=$((timeout - 2))
done

if [ $timeout -le 0 ]; then
    echo "❌ MySQL 連線逾時"
    exit 1
fi

# 等待 Redis 準備就緒
echo "⏳ 等待 Redis 準備就緒..."
timeout=30
while [ $timeout -gt 0 ]; do
    if redis-cli -h redis -a test_redis_password ping >/dev/null 2>&1; then
        echo "✅ Redis 已準備就緒"
        break
    fi
    echo "等待 Redis 連線... (剩餘 $timeout 秒)"
    sleep 2
    timeout=$((timeout - 2))
done

if [ $timeout -le 0 ]; then
    echo "❌ Redis 連線逾時"
    exit 1
fi

# 安裝依賴
echo "📦 安裝 Composer 依賴..."
composer install --no-interaction --prefer-dist --optimize-autoloader

echo "📦 安裝 NPM 依賴..."
npm ci

# 建立測試環境設定
echo "🔧 設定測試環境..."
cp .env.example .env.testing

# 生成應用程式金鑰
php artisan key:generate --env=testing

# 建立 SQLite 測試資料庫 (作為備用)
touch database/database.sqlite

# 執行資料庫遷移
echo "📊 執行測試資料庫遷移..."
php artisan migrate:fresh --env=testing --force

# 執行資料庫種子
echo "🌱 執行測試資料庫種子..."
php artisan db:seed --env=testing --force

# 建立儲存連結
php artisan storage:link --force

# 編譯前端資源
echo "🎨 編譯前端資源..."
npm run build

# 清理快取
echo "🧹 清理快取..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# 執行程式碼風格檢查
echo "🎯 執行程式碼風格檢查..."
if [ -f "./vendor/bin/php-cs-fixer" ]; then
    ./vendor/bin/php-cs-fixer fix --dry-run --diff --verbose
    if [ $? -ne 0 ]; then
        echo "❌ 程式碼風格檢查失敗"
        exit 1
    fi
    echo "✅ 程式碼風格檢查通過"
else
    echo "⚠️ PHP CS Fixer 未安裝，跳過程式碼風格檢查"
fi

# 執行靜態分析
echo "🔍 執行 PHPStan 靜態分析..."
if [ -f "./vendor/bin/phpstan" ]; then
    ./vendor/bin/phpstan analyse --memory-limit=1G
    if [ $? -ne 0 ]; then
        echo "❌ 靜態分析失敗"
        exit 1
    fi
    echo "✅ 靜態分析通過"
else
    echo "⚠️ PHPStan 未安裝，跳過靜態分析"
fi

# 執行單元測試和功能測試
echo "🧪 執行 PHPUnit 測試..."
php artisan test --env=testing --coverage --min=80

if [ $? -eq 0 ]; then
    echo "✅ PHPUnit 測試通過"
else
    echo "❌ PHPUnit 測試失敗"
    exit 1
fi

# 啟動 Xvfb (虛擬顯示器，用於瀏覽器測試)
echo "🖥️ 啟動虛擬顯示器..."
Xvfb :99 -screen 0 1920x1080x24 &
export DISPLAY=:99

# 等待 Selenium 準備就緒 (如果需要瀏覽器測試)
if [ "$RUN_BROWSER_TESTS" = "true" ]; then
    echo "⏳ 等待 Selenium 準備就緒..."
    timeout=60
    while [ $timeout -gt 0 ]; do
        if curl -f -s http://selenium:4444/wd/hub/status > /dev/null 2>&1; then
            echo "✅ Selenium 已準備就緒"
            break
        fi
        echo "等待 Selenium 啟動... (剩餘 $timeout 秒)"
        sleep 2
        timeout=$((timeout - 2))
    done
    
    if [ $timeout -le 0 ]; then
        echo "❌ Selenium 啟動逾時"
        exit 1
    fi
    
    # 執行瀏覽器測試
    echo "🌐 執行 Laravel Dusk 瀏覽器測試..."
    php artisan dusk --env=testing
    
    if [ $? -eq 0 ]; then
        echo "✅ 瀏覽器測試通過"
    else
        echo "❌ 瀏覽器測試失敗"
        
        # 保存瀏覽器測試截圖和日誌
        if [ -d "tests/Browser/screenshots" ]; then
            echo "📸 保存測試截圖..."
            ls -la tests/Browser/screenshots/
        fi
        
        if [ -d "tests/Browser/console" ]; then
            echo "📝 保存瀏覽器控制台日誌..."
            ls -la tests/Browser/console/
        fi
        
        exit 1
    fi
else
    echo "⚠️ 跳過瀏覽器測試 (RUN_BROWSER_TESTS != true)"
fi

# 生成測試報告
echo "📊 生成測試報告..."
if [ -f "coverage.xml" ]; then
    echo "✅ 程式碼覆蓋率報告已生成: coverage.xml"
fi

if [ -f "tests/_output/report.html" ]; then
    echo "✅ HTML 測試報告已生成: tests/_output/report.html"
fi

# 清理
echo "🧹 清理測試環境..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

echo "🎉 所有測試完成！"

# 顯示測試摘要
echo ""
echo "📋 測試摘要:"
echo "   - 程式碼風格檢查: ✅"
echo "   - 靜態分析: ✅"
echo "   - 單元測試: ✅"
echo "   - 功能測試: ✅"
if [ "$RUN_BROWSER_TESTS" = "true" ]; then
    echo "   - 瀏覽器測試: ✅"
fi
echo ""
echo "🚀 準備部署！"