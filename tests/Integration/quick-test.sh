#!/bin/bash

# 使用者管理整合測試快速執行腳本
# 用於快速驗證整合測試的基本功能

echo "🚀 開始執行使用者管理整合測試..."
echo "=================================="

# 設定測試環境
export APP_ENV=testing

# 1. 執行基本整合測試
echo "📋 執行基本整合測試..."
docker-compose exec app php artisan test tests/Feature/Integration/UserManagementBasicTest.php --stop-on-failure

if [ $? -eq 0 ]; then
    echo "✅ 基本整合測試通過"
else
    echo "❌ 基本整合測試失敗"
    exit 1
fi

# 2. 執行測試套件資訊驗證
echo "📊 驗證測試套件資訊..."
docker-compose exec app php artisan test tests/Integration/UserManagementTestSuite.php --filter=test_suite_information

if [ $? -eq 0 ]; then
    echo "✅ 測試套件資訊驗證通過"
else
    echo "❌ 測試套件資訊驗證失敗"
fi

# 3. 檢查測試檔案是否存在
echo "📁 檢查測試檔案..."
files=(
    "tests/Feature/Integration/UserManagementIntegrationTest.php"
    "tests/Feature/Performance/UserManagementPerformanceTest.php"
    "tests/Browser/UserManagementBrowserTest.php"
    "tests/Integration/UserManagementTestSuite.php"
    "tests/Integration/run-user-management-tests.php"
)

for file in "${files[@]}"; do
    if [ -f "$file" ]; then
        echo "✅ $file 存在"
    else
        echo "❌ $file 不存在"
    fi
done

echo "=================================="
echo "✅ 快速測試完成！"
echo ""
echo "📝 完整測試執行方式："
echo "   php tests/Integration/run-user-management-tests.php"
echo ""
echo "📋 個別測試執行方式："
echo "   docker-compose exec app php artisan test tests/Feature/Integration/UserManagementIntegrationTest.php"
echo "   docker-compose exec app php artisan test tests/Feature/Performance/UserManagementPerformanceTest.php"
echo "   docker-compose exec app php artisan dusk tests/Browser/UserManagementBrowserTest.php"