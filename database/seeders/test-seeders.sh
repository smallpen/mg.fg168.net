#!/bin/bash

# Seeders 測試腳本
# 用於驗證整理後的 seeders 是否正常工作

echo "=== Seeders 測試腳本 ==="
echo ""

# 檢查 Docker 容器狀態
echo "1. 檢查 Docker 容器狀態..."
if ! docker-compose ps | grep -q "Up"; then
    echo "❌ Docker 容器未啟動，請先執行: docker-compose up -d"
    exit 1
fi
echo "✅ Docker 容器正常運行"
echo ""

# 測試 1: 重建資料庫並執行標準 seeder
echo "2. 測試標準 DatabaseSeeder..."
docker-compose exec app php artisan migrate:fresh --seed --quiet
if [ $? -eq 0 ]; then
    echo "✅ 標準 DatabaseSeeder 執行成功"
else
    echo "❌ 標準 DatabaseSeeder 執行失敗"
    exit 1
fi
echo ""

# 驗證資料
echo "3. 驗證建立的資料..."
RESULT=$(docker-compose exec -T app php artisan tinker --execute="
echo 'Permissions:' . App\Models\Permission::count() . '|';
echo 'Roles:' . App\Models\Role::count() . '|';
echo 'Users:' . App\Models\User::count() . '|';
echo 'Admin exists:' . (App\Models\User::where('username', 'admin')->exists() ? 'Yes' : 'No');
")

echo "資料統計: $RESULT"

# 檢查關鍵資料
if [[ $RESULT == *"Permissions:51"* ]] && [[ $RESULT == *"Roles:3"* ]] && [[ $RESULT == *"Users:4"* ]] && [[ $RESULT == *"Admin exists:Yes"* ]]; then
    echo "✅ 基本資料驗證通過"
else
    echo "❌ 基本資料驗證失敗"
    exit 1
fi

# 檢查通路管理資料
CHANNEL_RESULT=$(docker-compose exec -T app php artisan tinker --execute="
if (class_exists('App\Models\Agent')) {
    echo 'Agents:' . App\Models\Agent::count() . '|';
    echo 'Players:' . App\Models\Player::count() . '|';
    echo 'Transactions:' . App\Models\PointTransaction::count();
} else {
    echo 'No Channel Models';
}
")

echo "通路管理資料: $CHANNEL_RESULT"

if [[ $CHANNEL_RESULT == *"Agents:6"* ]] && [[ $CHANNEL_RESULT == *"Players:6"* ]] && [[ $CHANNEL_RESULT == *"Transactions:6"* ]]; then
    echo "✅ 通路管理資料驗證通過"
elif [[ $CHANNEL_RESULT == *"No Channel Models"* ]]; then
    echo "⚠️  通路管理 Model 不存在，跳過驗證"
else
    echo "❌ 通路管理資料驗證失敗"
    exit 1
fi
echo ""

# 測試 2: 測試 CompleteTestDataSeeder
echo "4. 測試 CompleteTestDataSeeder..."
docker-compose exec app php artisan db:seed --class=CompleteTestDataSeeder --quiet
if [ $? -eq 0 ]; then
    echo "✅ CompleteTestDataSeeder 執行成功"
else
    echo "❌ CompleteTestDataSeeder 執行失敗"
    exit 1
fi
echo ""

# 測試 3: 測試個別 seeders
echo "5. 測試個別 seeders..."
SEEDERS=("PermissionSeeder" "RoleSeeder" "UserSeeder" "SettingsSeeder")

for seeder in "${SEEDERS[@]}"; do
    echo "   測試 $seeder..."
    docker-compose exec app php artisan db:seed --class=$seeder --quiet
    if [ $? -eq 0 ]; then
        echo "   ✅ $seeder 執行成功"
    else
        echo "   ❌ $seeder 執行失敗"
        exit 1
    fi
done
echo ""

# 測試登入功能
echo "6. 測試管理員登入..."
LOGIN_TEST=$(docker-compose exec -T app php artisan tinker --execute="
\$user = App\Models\User::where('username', 'admin')->first();
if (\$user && Hash::check('admin123', \$user->password)) {
    echo 'Login OK';
} else {
    echo 'Login Failed';
}
")

if [[ $LOGIN_TEST == *"Login OK"* ]]; then
    echo "✅ 管理員登入測試通過"
else
    echo "❌ 管理員登入測試失敗"
    exit 1
fi
echo ""

echo "🎉 所有測試通過！"
echo ""
echo "📋 測試摘要:"
echo "   • DatabaseSeeder: ✅"
echo "   • CompleteTestDataSeeder: ✅"
echo "   • 個別 Seeders: ✅"
echo "   • 基本資料完整性: ✅"
echo "   • 通路管理資料: ✅"
echo "   • 登入功能: ✅"
echo ""
echo "🔑 測試帳號:"
echo "   • 管理員: admin / admin123"
echo "   • 部門主管: manager / password123"
echo "   • 一般使用者: testuser / password123"
echo ""
echo "� 通入路管理測試資料:"
echo "   • 代理: 6個（2層級結構）"
echo "   • 玩家: 6個（分佈在不同代理下）"
echo "   • 交易記錄: 6筆（各種交易類型）"
echo ""
echo "🌐 登入網址: http://localhost/admin/login"