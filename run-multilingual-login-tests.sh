#!/bin/bash

# 登入頁面多語系測試執行腳本
# 
# 此腳本會執行登入頁面的多語系功能測試
# 使用 Playwright 和 MySQL MCP 工具進行端到端測試

echo "🌐 準備執行登入頁面多語系測試"
echo "================================"

# 檢查 Docker 環境
echo "📋 檢查 Docker 環境..."
if ! docker-compose ps | grep -q "Up"; then
    echo "❌ Docker 容器未運行，正在啟動..."
    docker-compose up -d
    sleep 10
fi

# 檢查測試資料
echo "📋 檢查測試資料..."
docker-compose exec -T app php artisan tinker --execute="
if (App\Models\User::where('username', 'admin')->exists()) {
    echo '✅ 測試資料存在';
} else {
    echo '❌ 測試資料不存在，請執行: docker-compose exec app php artisan db:seed';
    exit(1);
}
"

# 確保語言檔案存在
echo "📋 檢查語言檔案..."
if [ ! -f "lang/zh_TW/auth.php" ] || [ ! -f "lang/en/auth.php" ]; then
    echo "❌ 語言檔案不完整"
    exit 1
fi

echo "✅ 語言檔案檢查完成"

# 建立測試目錄
mkdir -p storage/screenshots/multilingual
mkdir -p storage/logs

# 執行多語系測試
echo "🚀 開始執行多語系測試..."
php execute-multilingual-login-tests.php

echo ""
echo "📊 測試完成！"
echo "請查看以下位置的測試報告："
echo "- JSON 報告: storage/logs/multilingual_login_test_report_*.json"
echo "- HTML 報告: storage/logs/multilingual_login_test_report_*.html"
echo "- 截圖: storage/screenshots/multilingual/"