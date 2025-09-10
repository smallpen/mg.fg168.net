#!/bin/bash

# 通路管理系統驗收測試腳本
# 版本: 1.0
# 作者: 系統管理員
# 日期: 2024-12-09

set -e

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# 日誌函數
log_info() {
    echo -e "${BLUE}[INFO]${NC} $(date '+%Y-%m-%d %H:%M:%S') - $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $(date '+%Y-%m-%d %H:%M:%S') - $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $(date '+%Y-%m-%d %H:%M:%S') - $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $(date '+%Y-%m-%d %H:%M:%S') - $1"
}

# 配置變數
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
TEST_LOG="/tmp/channel-management-acceptance-test-$(date +%Y%m%d_%H%M%S).log"

# 測試結果統計
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0
WARNINGS=0

# 重定向輸出到日誌檔案
exec > >(tee -a "$TEST_LOG")
exec 2>&1

log_info "開始通路管理系統驗收測試"
log_info "測試日誌: $TEST_LOG"

# 測試結果記錄函數
record_test_result() {
    local test_name="$1"
    local result="$2"
    local message="$3"
    
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    
    if [ "$result" = "PASS" ]; then
        PASSED_TESTS=$((PASSED_TESTS + 1))
        log_success "✓ $test_name: $message"
    elif [ "$result" = "FAIL" ]; then
        FAILED_TESTS=$((FAILED_TESTS + 1))
        log_error "✗ $test_name: $message"
    elif [ "$result" = "WARN" ]; then
        WARNINGS=$((WARNINGS + 1))
        log_warning "⚠ $test_name: $message"
    fi
}

# 1. 系統基礎設施測試
test_infrastructure() {
    log_info "========================================="
    log_info "1. 系統基礎設施測試"
    log_info "========================================="
    
    cd "$PROJECT_ROOT"
    
    # 測試 Docker 服務狀態
    if docker-compose ps | grep -q "Up"; then
        record_test_result "Docker 服務" "PASS" "所有容器正常運行"
    else
        record_test_result "Docker 服務" "FAIL" "部分容器未運行"
        return 1
    fi
    
    # 測試資料庫連線
    if docker-compose exec -T app php artisan migrate:status > /dev/null 2>&1; then
        record_test_result "資料庫連線" "PASS" "資料庫連線正常"
    else
        record_test_result "資料庫連線" "FAIL" "資料庫連線失敗"
        return 1
    fi
    
    # 測試 Redis 連線
    if docker-compose exec -T redis redis-cli ping | grep -q "PONG"; then
        record_test_result "Redis 連線" "PASS" "Redis 連線正常"
    else
        record_test_result "Redis 連線" "FAIL" "Redis 連線失敗"
    fi
    
    # 測試網頁服務
    HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/health || echo "000")
    if [ "$HTTP_STATUS" = "200" ]; then
        record_test_result "網頁服務" "PASS" "HTTP 服務正常 (狀態碼: $HTTP_STATUS)"
    else
        record_test_result "網頁服務" "FAIL" "HTTP 服務異常 (狀態碼: $HTTP_STATUS)"
    fi
}

# 2. 資料庫結構測試
test_database_structure() {
    log_info "========================================="
    log_info "2. 資料庫結構測試"
    log_info "========================================="
    
    # 載入環境變數
    source "$PROJECT_ROOT/.env"
    
    # 檢查必要表格
    REQUIRED_TABLES=("agents" "players" "point_transactions" "users" "roles" "permissions")
    
    for table in "${REQUIRED_TABLES[@]}"; do
        TABLE_EXISTS=$(docker-compose exec -T db mysql -u"$DB_USERNAME" -p"$DB_PASSWORD" -D"$DB_DATABASE" -e "SHOW TABLES LIKE '$table';" | grep -c "$table" || echo "0")
        
        if [ "$TABLE_EXISTS" -eq 1 ]; then
            record_test_result "表格 $table" "PASS" "表格存在"
        else
            record_test_result "表格 $table" "FAIL" "表格不存在"
        fi
    done
    
    # 檢查通路管理表格的關鍵欄位
    AGENT_COLUMNS=$(docker-compose exec -T db mysql -u"$DB_USERNAME" -p"$DB_PASSWORD" -D"$DB_DATABASE" -e "DESCRIBE agents;" | grep -E "(parent_id|level|prefix|total_points|allocated_points|remaining_points)" | wc -l)
    
    if [ "$AGENT_COLUMNS" -ge 6 ]; then
        record_test_result "代理表格結構" "PASS" "包含所有必要欄位"
    else
        record_test_result "代理表格結構" "FAIL" "缺少必要欄位"
    fi
    
    PLAYER_COLUMNS=$(docker-compose exec -T db mysql -u"$DB_USERNAME" -p"$DB_PASSWORD" -D"$DB_DATABASE" -e "DESCRIBE players;" | grep -E "(agent_id|points)" | wc -l)
    
    if [ "$PLAYER_COLUMNS" -ge 2 ]; then
        record_test_result "玩家表格結構" "PASS" "包含所有必要欄位"
    else
        record_test_result "玩家表格結構" "FAIL" "缺少必要欄位"
    fi
}

# 3. 權限系統測試
test_permission_system() {
    log_info "========================================="
    log_info "3. 權限系統測試"
    log_info "========================================="
    
    # 檢查權限數量
    PERMISSION_COUNT=$(docker-compose exec -T app php artisan tinker --execute="echo Permission::count();" 2>/dev/null || echo "0")
    
    if [ "$PERMISSION_COUNT" -ge 35 ]; then
        record_test_result "系統權限" "PASS" "權限數量正確 ($PERMISSION_COUNT 個)"
    else
        record_test_result "系統權限" "FAIL" "權限數量不足 ($PERMISSION_COUNT 個，預期 35 個)"
    fi
    
    # 檢查角色數量
    ROLE_COUNT=$(docker-compose exec -T app php artisan tinker --execute="echo Role::count();" 2>/dev/null || echo "0")
    
    if [ "$ROLE_COUNT" -ge 3 ]; then
        record_test_result "系統角色" "PASS" "角色數量正確 ($ROLE_COUNT 個)"
    else
        record_test_result "系統角色" "FAIL" "角色數量不足 ($ROLE_COUNT 個，預期至少 3 個)"
    fi
    
    # 檢查管理員使用者
    ADMIN_EXISTS=$(docker-compose exec -T app php artisan tinker --execute="echo User::where('username', 'admin')->exists() ? 'true' : 'false';" 2>/dev/null || echo "false")
    
    if [ "$ADMIN_EXISTS" = "true" ]; then
        record_test_result "管理員帳號" "PASS" "管理員帳號存在"
    else
        record_test_result "管理員帳號" "FAIL" "管理員帳號不存在"
    fi
}

# 4. 通路管理核心功能測試
test_channel_management_core() {
    log_info "========================================="
    log_info "4. 通路管理核心功能測試"
    log_info "========================================="
    
    # 測試代理服務
    SERVICE_TEST=$(docker-compose exec -T app php artisan tinker --execute="
    try {
        \$service = app(App\\Services\\AgentService::class);
        echo 'AgentService loaded successfully';
    } catch (Exception \$e) {
        echo 'Error: ' . \$e->getMessage();
    }
    " 2>/dev/null || echo "Error loading service")
    
    if [[ "$SERVICE_TEST" == *"successfully"* ]]; then
        record_test_result "代理服務" "PASS" "AgentService 載入成功"
    else
        record_test_result "代理服務" "FAIL" "AgentService 載入失敗: $SERVICE_TEST"
    fi
    
    # 測試玩家服務
    SERVICE_TEST=$(docker-compose exec -T app php artisan tinker --execute="
    try {
        \$service = app(App\\Services\\PlayerService::class);
        echo 'PlayerService loaded successfully';
    } catch (Exception \$e) {
        echo 'Error: ' . \$e->getMessage();
    }
    " 2>/dev/null || echo "Error loading service")
    
    if [[ "$SERVICE_TEST" == *"successfully"* ]]; then
        record_test_result "玩家服務" "PASS" "PlayerService 載入成功"
    else
        record_test_result "玩家服務" "FAIL" "PlayerService 載入失敗: $SERVICE_TEST"
    fi
    
    # 測試點數服務
    SERVICE_TEST=$(docker-compose exec -T app php artisan tinker --execute="
    try {
        \$service = app(App\\Services\\PointService::class);
        echo 'PointService loaded successfully';
    } catch (Exception \$e) {
        echo 'Error: ' . \$e->getMessage();
    }
    " 2>/dev/null || echo "Error loading service")
    
    if [[ "$SERVICE_TEST" == *"successfully"* ]]; then
        record_test_result "點數服務" "PASS" "PointService 載入成功"
    else
        record_test_result "點數服務" "FAIL" "PointService 載入失敗: $SERVICE_TEST"
    fi
}

# 5. Livewire 元件測試
test_livewire_components() {
    log_info "========================================="
    log_info "5. Livewire 元件測試"
    log_info "========================================="
    
    # 檢查 Livewire 元件是否存在
    LIVEWIRE_COMPONENTS=(
        "App\\Livewire\\Admin\\Channels\\AgentList"
        "App\\Livewire\\Admin\\Channels\\AgentForm"
        "App\\Livewire\\Admin\\Channels\\PlayerList"
        "App\\Livewire\\Admin\\Channels\\PlayerForm"
        "App\\Livewire\\Admin\\Channels\\PointManagement"
    )
    
    for component in "${LIVEWIRE_COMPONENTS[@]}"; do
        if [ -f "$PROJECT_ROOT/app/Livewire/Admin/Channels/$(basename ${component}).php" ]; then
            record_test_result "Livewire 元件 $(basename ${component})" "PASS" "元件檔案存在"
        else
            record_test_result "Livewire 元件 $(basename ${component})" "FAIL" "元件檔案不存在"
        fi
    done
    
    # 檢查視圖檔案
    LIVEWIRE_VIEWS=(
        "agent-list.blade.php"
        "agent-form.blade.php"
        "player-list.blade.php"
        "player-form.blade.php"
        "point-management.blade.php"
    )
    
    for view in "${LIVEWIRE_VIEWS[@]}"; do
        if [ -f "$PROJECT_ROOT/resources/views/livewire/admin/channels/$view" ]; then
            record_test_result "Livewire 視圖 $view" "PASS" "視圖檔案存在"
        else
            record_test_result "Livewire 視圖 $view" "FAIL" "視圖檔案不存在"
        fi
    done
}

# 6. 路由測試
test_routes() {
    log_info "========================================="
    log_info "6. 路由測試"
    log_info "========================================="
    
    # 檢查通路管理路由
    ROUTE_CHECK=$(docker-compose exec -T app php artisan route:list | grep -c "channels" || echo "0")
    
    if [ "$ROUTE_CHECK" -gt 0 ]; then
        record_test_result "通路管理路由" "PASS" "找到 $ROUTE_CHECK 個通路管理路由"
    else
        record_test_result "通路管理路由" "FAIL" "未找到通路管理路由"
    fi
    
    # 測試主要路由是否可存取（需要登入）
    ADMIN_LOGIN_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/admin/login || echo "000")
    
    if [ "$ADMIN_LOGIN_STATUS" = "200" ]; then
        record_test_result "管理員登入頁面" "PASS" "登入頁面可存取"
    else
        record_test_result "管理員登入頁面" "FAIL" "登入頁面無法存取 (狀態碼: $ADMIN_LOGIN_STATUS)"
    fi
}

# 7. 配置檔案測試
test_configuration() {
    log_info "========================================="
    log_info "7. 配置檔案測試"
    log_info "========================================="
    
    # 檢查通路管理配置檔案
    if [ -f "$PROJECT_ROOT/config/channel-management.php" ]; then
        record_test_result "通路管理配置" "PASS" "配置檔案存在"
    else
        record_test_result "通路管理配置" "FAIL" "配置檔案不存在"
    fi
    
    # 檢查日誌配置
    LOG_CONFIG_CHECK=$(grep -c "channel_management" "$PROJECT_ROOT/config/logging.php" || echo "0")
    
    if [ "$LOG_CONFIG_CHECK" -gt 0 ]; then
        record_test_result "日誌配置" "PASS" "通路管理日誌配置存在"
    else
        record_test_result "日誌配置" "FAIL" "通路管理日誌配置不存在"
    fi
    
    # 檢查環境變數
    if grep -q "CHANNEL_" "$PROJECT_ROOT/.env"; then
        record_test_result "環境變數" "PASS" "通路管理環境變數已配置"
    else
        record_test_result "環境變數" "WARN" "未找到通路管理環境變數"
    fi
}

# 8. 文檔測試
test_documentation() {
    log_info "========================================="
    log_info "8. 文檔測試"
    log_info "========================================="
    
    # 檢查使用者手冊
    if [ -f "$PROJECT_ROOT/docs/channel-management-user-manual.md" ]; then
        record_test_result "使用者手冊" "PASS" "使用者手冊存在"
    else
        record_test_result "使用者手冊" "FAIL" "使用者手冊不存在"
    fi
    
    # 檢查管理員指南
    if [ -f "$PROJECT_ROOT/docs/channel-management-admin-guide.md" ]; then
        record_test_result "管理員指南" "PASS" "管理員指南存在"
    else
        record_test_result "管理員指南" "FAIL" "管理員指南不存在"
    fi
    
    # 檢查部署腳本
    if [ -f "$PROJECT_ROOT/scripts/deployment/deploy-channel-management.sh" ]; then
        record_test_result "部署腳本" "PASS" "部署腳本存在"
    else
        record_test_result "部署腳本" "FAIL" "部署腳本不存在"
    fi
}

# 9. 安全性測試
test_security() {
    log_info "========================================="
    log_info "9. 安全性測試"
    log_info "========================================="
    
    # 檢查 APP_DEBUG 設定
    if grep -q "APP_DEBUG=false" "$PROJECT_ROOT/.env"; then
        record_test_result "除錯模式" "PASS" "生產環境除錯模式已關閉"
    else
        record_test_result "除錯模式" "WARN" "建議在生產環境關閉除錯模式"
    fi
    
    # 檢查 APP_KEY 是否設定
    if grep -q "APP_KEY=base64:" "$PROJECT_ROOT/.env"; then
        record_test_result "應用程式金鑰" "PASS" "應用程式金鑰已設定"
    else
        record_test_result "應用程式金鑰" "FAIL" "應用程式金鑰未設定"
    fi
    
    # 檢查檔案權限
    STORAGE_PERMISSIONS=$(stat -c "%a" "$PROJECT_ROOT/storage" 2>/dev/null || echo "000")
    
    if [ "$STORAGE_PERMISSIONS" = "775" ] || [ "$STORAGE_PERMISSIONS" = "755" ]; then
        record_test_result "檔案權限" "PASS" "storage 目錄權限正確 ($STORAGE_PERMISSIONS)"
    else
        record_test_result "檔案權限" "WARN" "storage 目錄權限可能需要調整 ($STORAGE_PERMISSIONS)"
    fi
}

# 10. 效能測試
test_performance() {
    log_info "========================================="
    log_info "10. 效能測試"
    log_info "========================================="
    
    # 測試首頁載入時間
    START_TIME=$(date +%s%N)
    curl -s http://localhost/admin/login > /dev/null
    END_TIME=$(date +%s%N)
    LOAD_TIME=$(( (END_TIME - START_TIME) / 1000000 ))  # 轉換為毫秒
    
    if [ "$LOAD_TIME" -lt 2000 ]; then
        record_test_result "頁面載入效能" "PASS" "登入頁面載入時間: ${LOAD_TIME}ms"
    elif [ "$LOAD_TIME" -lt 5000 ]; then
        record_test_result "頁面載入效能" "WARN" "登入頁面載入時間較慢: ${LOAD_TIME}ms"
    else
        record_test_result "頁面載入效能" "FAIL" "登入頁面載入時間過慢: ${LOAD_TIME}ms"
    fi
    
    # 檢查快取配置
    CACHE_DRIVER=$(docker-compose exec -T app php artisan tinker --execute="echo config('cache.default');" 2>/dev/null || echo "unknown")
    
    if [ "$CACHE_DRIVER" = "redis" ]; then
        record_test_result "快取配置" "PASS" "使用 Redis 快取"
    elif [ "$CACHE_DRIVER" = "file" ]; then
        record_test_result "快取配置" "WARN" "使用檔案快取，建議使用 Redis"
    else
        record_test_result "快取配置" "FAIL" "快取配置異常: $CACHE_DRIVER"
    fi
}

# 生成測試報告
generate_test_report() {
    log_info "========================================="
    log_info "生成測試報告"
    log_info "========================================="
    
    REPORT_FILE="/tmp/channel-management-test-report-$(date +%Y%m%d_%H%M%S).html"
    
    cat > "$REPORT_FILE" << EOF
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>通路管理系統驗收測試報告</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { background: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .summary { display: flex; gap: 20px; margin-bottom: 20px; }
        .stat-box { background: #fff; border: 1px solid #ddd; padding: 15px; border-radius: 5px; text-align: center; flex: 1; }
        .pass { color: #28a745; }
        .fail { color: #dc3545; }
        .warn { color: #ffc107; }
        .test-section { margin-bottom: 30px; }
        .test-section h3 { background: #e9ecef; padding: 10px; margin: 0; }
        .test-results { background: #f8f9fa; padding: 15px; }
        .test-item { margin: 5px 0; padding: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>通路管理系統驗收測試報告</h1>
        <p>測試時間: $(date '+%Y-%m-%d %H:%M:%S')</p>
        <p>測試環境: $(hostname)</p>
    </div>
    
    <div class="summary">
        <div class="stat-box">
            <h3>總測試數</h3>
            <div style="font-size: 2em; font-weight: bold;">$TOTAL_TESTS</div>
        </div>
        <div class="stat-box">
            <h3 class="pass">通過</h3>
            <div style="font-size: 2em; font-weight: bold; color: #28a745;">$PASSED_TESTS</div>
        </div>
        <div class="stat-box">
            <h3 class="fail">失敗</h3>
            <div style="font-size: 2em; font-weight: bold; color: #dc3545;">$FAILED_TESTS</div>
        </div>
        <div class="stat-box">
            <h3 class="warn">警告</h3>
            <div style="font-size: 2em; font-weight: bold; color: #ffc107;">$WARNINGS</div>
        </div>
    </div>
    
    <div class="test-section">
        <h3>測試結果詳情</h3>
        <div class="test-results">
            <pre>$(cat "$TEST_LOG" | grep -E "\[SUCCESS\]|\[ERROR\]|\[WARNING\]" | sed 's/\x1b\[[0-9;]*m//g')</pre>
        </div>
    </div>
    
    <div class="test-section">
        <h3>系統資訊</h3>
        <div class="test-results">
            <p><strong>Docker 服務狀態:</strong></p>
            <pre>$(docker-compose ps 2>/dev/null || echo "無法取得 Docker 狀態")</pre>
            
            <p><strong>資料庫遷移狀態:</strong></p>
            <pre>$(docker-compose exec -T app php artisan migrate:status 2>/dev/null || echo "無法取得遷移狀態")</pre>
        </div>
    </div>
    
    <div class="test-section">
        <h3>建議事項</h3>
        <div class="test-results">
            <ul>
EOF

    # 根據測試結果添加建議
    if [ "$FAILED_TESTS" -gt 0 ]; then
        echo "                <li class=\"fail\">發現 $FAILED_TESTS 個失敗的測試，請檢查並修復相關問題</li>" >> "$REPORT_FILE"
    fi
    
    if [ "$WARNINGS" -gt 0 ]; then
        echo "                <li class=\"warn\">發現 $WARNINGS 個警告，建議檢查並優化相關配置</li>" >> "$REPORT_FILE"
    fi
    
    if [ "$FAILED_TESTS" -eq 0 ] && [ "$WARNINGS" -eq 0 ]; then
        echo "                <li class=\"pass\">所有測試通過，系統準備就緒</li>" >> "$REPORT_FILE"
    fi

    cat >> "$REPORT_FILE" << EOF
            </ul>
        </div>
    </div>
    
    <div class="test-section">
        <h3>完整測試日誌</h3>
        <div class="test-results">
            <p>完整測試日誌位於: <code>$TEST_LOG</code></p>
        </div>
    </div>
</body>
</html>
EOF

    log_success "測試報告已生成: $REPORT_FILE"
}

# 主要測試流程
main() {
    log_info "========================================="
    log_info "通路管理系統驗收測試開始"
    log_info "========================================="
    
    # 載入環境變數
    if [ -f "$PROJECT_ROOT/.env" ]; then
        source "$PROJECT_ROOT/.env"
    else
        log_error "找不到 .env 檔案"
        exit 1
    fi
    
    # 執行所有測試
    test_infrastructure
    test_database_structure
    test_permission_system
    test_channel_management_core
    test_livewire_components
    test_routes
    test_configuration
    test_documentation
    test_security
    test_performance
    
    # 生成報告
    generate_test_report
    
    # 顯示測試摘要
    log_info "========================================="
    log_info "測試摘要"
    log_info "========================================="
    log_info "總測試數: $TOTAL_TESTS"
    log_success "通過: $PASSED_TESTS"
    log_error "失敗: $FAILED_TESTS"
    log_warning "警告: $WARNINGS"
    
    # 計算成功率
    if [ "$TOTAL_TESTS" -gt 0 ]; then
        SUCCESS_RATE=$(( PASSED_TESTS * 100 / TOTAL_TESTS ))
        log_info "成功率: $SUCCESS_RATE%"
    fi
    
    log_info "詳細測試日誌: $TEST_LOG"
    log_info "HTML 測試報告: $REPORT_FILE"
    
    # 根據測試結果設定退出碼
    if [ "$FAILED_TESTS" -gt 0 ]; then
        log_error "驗收測試失敗，請修復失敗的測試項目"
        exit 1
    elif [ "$WARNINGS" -gt 0 ]; then
        log_warning "驗收測試完成，但有警告項目需要注意"
        exit 0
    else
        log_success "所有驗收測試通過，系統準備就緒！"
        exit 0
    fi
}

# 錯誤處理
trap 'log_error "測試過程中發生錯誤"' ERR

# 執行主要流程
main "$@"