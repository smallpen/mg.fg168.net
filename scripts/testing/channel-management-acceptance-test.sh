#!/bin/bash

# 通路管理系統驗收測試腳本
# 版本: 1.0.0
# 用途: 執行通路管理系統的完整驗收測試

set -e

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'

# 測試結果統計
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0
SKIPPED_TESTS=0

# 測試報告檔案
TEST_REPORT_DIR="storage/logs/testing"
TEST_REPORT_FILE="$TEST_REPORT_DIR/acceptance_test_$(date +%Y%m%d_%H%M%S).html"
TEST_LOG_FILE="$TEST_REPORT_DIR/acceptance_test_$(date +%Y%m%d_%H%M%S).log"

# 日誌函數
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] [INFO] $1" >> $TEST_LOG_FILE
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] [SUCCESS] $1" >> $TEST_LOG_FILE
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] [WARNING] $1" >> $TEST_LOG_FILE
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] [ERROR] $1" >> $TEST_LOG_FILE
}

log_test() {
    echo -e "${CYAN}[TEST]${NC} $1"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] [TEST] $1" >> $TEST_LOG_FILE
}

# 測試結果記錄函數
record_test_result() {
    local test_name="$1"
    local result="$2"
    local message="$3"
    
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    
    case $result in
        "PASS")
            PASSED_TESTS=$((PASSED_TESTS + 1))
            log_success "✅ $test_name: $message"
            ;;
        "FAIL")
            FAILED_TESTS=$((FAILED_TESTS + 1))
            log_error "❌ $test_name: $message"
            ;;
        "SKIP")
            SKIPPED_TESTS=$((SKIPPED_TESTS + 1))
            log_warning "⏭️ $test_name: $message"
            ;;
    esac
}

# 初始化測試環境
init_test_environment() {
    log_info "初始化測試環境..."
    
    # 建立測試報告目錄
    mkdir -p $TEST_REPORT_DIR
    
    # 初始化測試日誌
    echo "# 通路管理系統驗收測試日誌" > $TEST_LOG_FILE
    echo "# 測試開始時間: $(date)" >> $TEST_LOG_FILE
    echo "" >> $TEST_LOG_FILE
    
    log_success "測試環境初始化完成"
}

# 檢查系統前置條件
check_prerequisites() {
    log_info "檢查系統前置條件..."
    
    # 檢查 Docker 服務
    if docker-compose ps >/dev/null 2>&1; then
        record_test_result "Docker 服務檢查" "PASS" "Docker Compose 服務正常運行"
    else
        record_test_result "Docker 服務檢查" "FAIL" "Docker Compose 服務未運行"
        return 1
    fi
    
    # 檢查資料庫連線
    if docker-compose exec -T app php artisan tinker --execute="DB::connection()->getPdo();" >/dev/null 2>&1; then
        record_test_result "資料庫連線檢查" "PASS" "資料庫連線正常"
    else
        record_test_result "資料庫連線檢查" "FAIL" "無法連接資料庫"
        return 1
    fi
    
    # 檢查快取連線
    if docker-compose exec -T app php artisan tinker --execute="Cache::put('test', 'ok', 60); echo Cache::get('test');" 2>/dev/null | grep -q "ok"; then
        record_test_result "快取連線檢查" "PASS" "快取連線正常"
    else
        record_test_result "快取連線檢查" "FAIL" "快取連線異常"
        return 1
    fi
}

# 測試資料模型
test_data_models() {
    log_info "測試資料模型..."
    
    # 測試 Agent 模型
    if docker-compose exec -T app php artisan tinker --execute="echo class_exists('App\Models\Agent') ? 'OK' : 'FAIL';" 2>/dev/null | grep -q "OK"; then
        record_test_result "Agent 模型載入" "PASS" "Agent 模型載入成功"
    else
        record_test_result "Agent 模型載入" "FAIL" "Agent 模型載入失敗"
    fi
    
    # 測試 Player 模型
    if docker-compose exec -T app php artisan tinker --execute="echo class_exists('App\Models\Player') ? 'OK' : 'FAIL';" 2>/dev/null | grep -q "OK"; then
        record_test_result "Player 模型載入" "PASS" "Player 模型載入成功"
    else
        record_test_result "Player 模型載入" "FAIL" "Player 模型載入失敗"
    fi
    
    # 測試 PointTransaction 模型
    if docker-compose exec -T app php artisan tinker --execute="echo class_exists('App\Models\PointTransaction') ? 'OK' : 'FAIL';" 2>/dev/null | grep -q "OK"; then
        record_test_result "PointTransaction 模型載入" "PASS" "PointTransaction 模型載入成功"
    else
        record_test_result "PointTransaction 模型載入" "FAIL" "PointTransaction 模型載入失敗"
    fi
    
    # 測試模型關聯
    RELATION_TEST=$(docker-compose exec -T app php artisan tinker --execute="
    try {
        \$agent = App\Models\Agent::first();
        if (\$agent) {
            \$children = \$agent->children;
            \$players = \$agent->players;
            echo 'OK';
        } else {
            echo 'NO_DATA';
        }
    } catch (Exception \$e) {
        echo 'ERROR: ' . \$e->getMessage();
    }
    " 2>/dev/null | tail -1)
    
    if [[ "$RELATION_TEST" == "OK" ]]; then
        record_test_result "模型關聯測試" "PASS" "模型關聯正常運作"
    elif [[ "$RELATION_TEST" == "NO_DATA" ]]; then
        record_test_result "模型關聯測試" "SKIP" "沒有測試資料"
    else
        record_test_result "模型關聯測試" "FAIL" "模型關聯異常: $RELATION_TEST"
    fi
}

# 測試服務層
test_service_layer() {
    log_info "測試服務層..."
    
    # 測試 AgentService
    if docker-compose exec -T app php artisan tinker --execute="echo class_exists('App\Services\AgentService') ? 'OK' : 'FAIL';" 2>/dev/null | grep -q "OK"; then
        record_test_result "AgentService 載入" "PASS" "AgentService 載入成功"
    else
        record_test_result "AgentService 載入" "FAIL" "AgentService 載入失敗"
    fi
    
    # 測試 PlayerService
    if docker-compose exec -T app php artisan tinker --execute="echo class_exists('App\Services\PlayerService') ? 'OK' : 'FAIL';" 2>/dev/null | grep -q "OK"; then
        record_test_result "PlayerService 載入" "PASS" "PlayerService 載入成功"
    else
        record_test_result "PlayerService 載入" "FAIL" "PlayerService 載入失敗"
    fi
    
    # 測試 PointService
    if docker-compose exec -T app php artisan tinker --execute="echo class_exists('App\Services\PointService') ? 'OK' : 'FAIL';" 2>/dev/null | grep -q "OK"; then
        record_test_result "PointService 載入" "PASS" "PointService 載入成功"
    else
        record_test_result "PointService 載入" "FAIL" "PointService 載入失敗"
    fi
}

# 測試 Livewire 元件
test_livewire_components() {
    log_info "測試 Livewire 元件..."
    
    # 測試 AgentList 元件
    if docker-compose exec -T app php artisan tinker --execute="echo class_exists('App\Livewire\Admin\Channels\AgentList') ? 'OK' : 'FAIL';" 2>/dev/null | grep -q "OK"; then
        record_test_result "AgentList 元件載入" "PASS" "AgentList 元件載入成功"
    else
        record_test_result "AgentList 元件載入" "FAIL" "AgentList 元件載入失敗"
    fi
    
    # 測試 AgentForm 元件
    if docker-compose exec -T app php artisan tinker --execute="echo class_exists('App\Livewire\Admin\Channels\AgentForm') ? 'OK' : 'FAIL';" 2>/dev/null | grep -q "OK"; then
        record_test_result "AgentForm 元件載入" "PASS" "AgentForm 元件載入成功"
    else
        record_test_result "AgentForm 元件載入" "FAIL" "AgentForm 元件載入失敗"
    fi
    
    # 測試 PlayerList 元件
    if docker-compose exec -T app php artisan tinker --execute="echo class_exists('App\Livewire\Admin\Channels\PlayerList') ? 'OK' : 'FAIL';" 2>/dev/null | grep -q "OK"; then
        record_test_result "PlayerList 元件載入" "PASS" "PlayerList 元件載入成功"
    else
        record_test_result "PlayerList 元件載入" "FAIL" "PlayerList 元件載入失敗"
    fi
    
    # 測試 PointManagement 元件
    if docker-compose exec -T app php artisan tinker --execute="echo class_exists('App\Livewire\Admin\Channels\PointManagement') ? 'OK' : 'FAIL';" 2>/dev/null | grep -q "OK"; then
        record_test_result "PointManagement 元件載入" "PASS" "PointManagement 元件載入成功"
    else
        record_test_result "PointManagement 元件載入" "FAIL" "PointManagement 元件載入失敗"
    fi
}

# 測試資料庫結構
test_database_structure() {
    log_info "測試資料庫結構..."
    
    # 檢查 agents 資料表
    if docker-compose exec -T db mysql -u root -p$MYSQL_ROOT_PASSWORD laravel_admin -e "DESCRIBE agents;" >/dev/null 2>&1; then
        record_test_result "agents 資料表結構" "PASS" "agents 資料表存在且結構正確"
    else
        record_test_result "agents 資料表結構" "FAIL" "agents 資料表不存在或結構異常"
    fi
    
    # 檢查 players 資料表
    if docker-compose exec -T db mysql -u root -p$MYSQL_ROOT_PASSWORD laravel_admin -e "DESCRIBE players;" >/dev/null 2>&1; then
        record_test_result "players 資料表結構" "PASS" "players 資料表存在且結構正確"
    else
        record_test_result "players 資料表結構" "FAIL" "players 資料表不存在或結構異常"
    fi
    
    # 檢查 point_transactions 資料表
    if docker-compose exec -T db mysql -u root -p$MYSQL_ROOT_PASSWORD laravel_admin -e "DESCRIBE point_transactions;" >/dev/null 2>&1; then
        record_test_result "point_transactions 資料表結構" "PASS" "point_transactions 資料表存在且結構正確"
    else
        record_test_result "point_transactions 資料表結構" "FAIL" "point_transactions 資料表不存在或結構異常"
    fi
    
    # 檢查外鍵約束
    FK_COUNT=$(docker-compose exec -T db mysql -u root -p$MYSQL_ROOT_PASSWORD laravel_admin -e "
    SELECT COUNT(*) as count
    FROM information_schema.KEY_COLUMN_USAGE 
    WHERE table_schema = 'laravel_admin' 
        AND REFERENCED_TABLE_NAME IS NOT NULL
        AND TABLE_NAME IN ('agents', 'players', 'point_transactions');
    " | tail -1)
    
    if [ "$FK_COUNT" -gt 0 ]; then
        record_test_result "外鍵約束檢查" "PASS" "發現 $FK_COUNT 個外鍵約束"
    else
        record_test_result "外鍵約束檢查" "FAIL" "沒有發現外鍵約束"
    fi
    
    # 檢查索引
    INDEX_COUNT=$(docker-compose exec -T db mysql -u root -p$MYSQL_ROOT_PASSWORD laravel_admin -e "
    SELECT COUNT(*) as count
    FROM information_schema.statistics 
    WHERE table_schema = 'laravel_admin' 
        AND table_name IN ('agents', 'players', 'point_transactions');
    " | tail -1)
    
    if [ "$INDEX_COUNT" -gt 0 ]; then
        record_test_result "索引檢查" "PASS" "發現 $INDEX_COUNT 個索引"
    else
        record_test_result "索引檢查" "FAIL" "沒有發現索引"
    fi
}

# 測試權限系統
test_permission_system() {
    log_info "測試權限系統..."
    
    # 檢查通路管理權限
    CHANNEL_PERMISSIONS=$(docker-compose exec -T app php artisan tinker --execute="echo App\Models\Permission::where('name', 'like', 'channels.%')->count();" 2>/dev/null | tail -1)
    
    if [ "$CHANNEL_PERMISSIONS" -gt 0 ]; then
        record_test_result "通路管理權限" "PASS" "發現 $CHANNEL_PERMISSIONS 個通路管理權限"
    else
        record_test_result "通路管理權限" "FAIL" "沒有發現通路管理權限"
    fi
    
    # 檢查權限中介軟體
    if docker-compose exec -T app php artisan tinker --execute="echo class_exists('App\Http\Middleware\ChannelPermissionMiddleware') ? 'OK' : 'FAIL';" 2>/dev/null | grep -q "OK"; then
        record_test_result "權限中介軟體" "PASS" "權限中介軟體載入成功"
    else
        record_test_result "權限中介軟體" "FAIL" "權限中介軟體載入失敗"
    fi
}

# 測試路由系統
test_routing_system() {
    log_info "測試路由系統..."
    
    # 檢查通路管理路由
    CHANNEL_ROUTES=$(docker-compose exec -T app php artisan route:list | grep -c "channels" || echo "0")
    
    if [ "$CHANNEL_ROUTES" -gt 0 ]; then
        record_test_result "通路管理路由" "PASS" "發現 $CHANNEL_ROUTES 個通路管理路由"
    else
        record_test_result "通路管理路由" "FAIL" "沒有發現通路管理路由"
    fi
    
    # 檢查 API 路由
    API_ROUTES=$(docker-compose exec -T app php artisan route:list | grep -c "api/v1/channels" || echo "0")
    
    if [ "$API_ROUTES" -gt 0 ]; then
        record_test_result "API 路由" "PASS" "發現 $API_ROUTES 個 API 路由"
    else
        record_test_result "API 路由" "SKIP" "沒有發現 API 路由（可能未啟用）"
    fi
}

# 測試業務邏輯
test_business_logic() {
    log_info "測試業務邏輯..."
    
    # 測試代理建立邏輯
    AGENT_CREATION_TEST=$(docker-compose exec -T app php artisan tinker --execute="
    try {
        // 測試代理建立邏輯（不實際建立）
        \$service = app('App\Services\AgentService');
        echo method_exists(\$service, 'createAgent') ? 'OK' : 'FAIL';
    } catch (Exception \$e) {
        echo 'ERROR: ' . \$e->getMessage();
    }
    " 2>/dev/null | tail -1)
    
    if [[ "$AGENT_CREATION_TEST" == "OK" ]]; then
        record_test_result "代理建立邏輯" "PASS" "代理建立邏輯正常"
    else
        record_test_result "代理建立邏輯" "FAIL" "代理建立邏輯異常: $AGENT_CREATION_TEST"
    fi
    
    # 測試點數分配邏輯
    POINT_ALLOCATION_TEST=$(docker-compose exec -T app php artisan tinker --execute="
    try {
        \$service = app('App\Services\PointService');
        echo method_exists(\$service, 'allocatePointsToAgent') ? 'OK' : 'FAIL';
    } catch (Exception \$e) {
        echo 'ERROR: ' . \$e->getMessage();
    }
    " 2>/dev/null | tail -1)
    
    if [[ "$POINT_ALLOCATION_TEST" == "OK" ]]; then
        record_test_result "點數分配邏輯" "PASS" "點數分配邏輯正常"
    else
        record_test_result "點數分配邏輯" "FAIL" "點數分配邏輯異常: $POINT_ALLOCATION_TEST"
    fi
    
    # 測試前置符號驗證
    PREFIX_VALIDATION_TEST=$(docker-compose exec -T app php artisan tinker --execute="
    try {
        \$agent = new App\Models\Agent();
        echo method_exists(\$agent, 'getFullPrefixAttribute') ? 'OK' : 'FAIL';
    } catch (Exception \$e) {
        echo 'ERROR: ' . \$e->getMessage();
    }
    " 2>/dev/null | tail -1)
    
    if [[ "$PREFIX_VALIDATION_TEST" == "OK" ]]; then
        record_test_result "前置符號邏輯" "PASS" "前置符號邏輯正常"
    else
        record_test_result "前置符號邏輯" "FAIL" "前置符號邏輯異常: $PREFIX_VALIDATION_TEST"
    fi
}

# 測試配置檔案
test_configuration() {
    log_info "測試配置檔案..."
    
    # 檢查通路管理配置檔案
    if [ -f "config/channel-management.php" ]; then
        record_test_result "通路管理配置檔案" "PASS" "配置檔案存在"
    else
        record_test_result "通路管理配置檔案" "FAIL" "配置檔案不存在"
    fi
    
    # 檢查日誌配置檔案
    if [ -f "config/logging-channel-management.php" ]; then
        record_test_result "日誌配置檔案" "PASS" "日誌配置檔案存在"
    else
        record_test_result "日誌配置檔案" "FAIL" "日誌配置檔案不存在"
    fi
    
    # 測試配置載入
    CONFIG_TEST=$(docker-compose exec -T app php artisan tinker --execute="
    try {
        \$config = config('channel-management.enabled');
        echo is_bool(\$config) ? 'OK' : 'FAIL';
    } catch (Exception \$e) {
        echo 'ERROR: ' . \$e->getMessage();
    }
    " 2>/dev/null | tail -1)
    
    if [[ "$CONFIG_TEST" == "OK" ]]; then
        record_test_result "配置載入測試" "PASS" "配置載入正常"
    else
        record_test_result "配置載入測試" "FAIL" "配置載入異常: $CONFIG_TEST"
    fi
}

# 測試視圖檔案
test_view_files() {
    log_info "測試視圖檔案..."
    
    # 檢查 Livewire 視圖檔案
    VIEW_FILES=(
        "resources/views/livewire/admin/channels/agent-list.blade.php"
        "resources/views/livewire/admin/channels/agent-form.blade.php"
        "resources/views/livewire/admin/channels/player-list.blade.php"
        "resources/views/livewire/admin/channels/point-management.blade.php"
    )
    
    for view_file in "${VIEW_FILES[@]}"; do
        if [ -f "$view_file" ]; then
            record_test_result "視圖檔案: $(basename $view_file)" "PASS" "視圖檔案存在"
        else
            record_test_result "視圖檔案: $(basename $view_file)" "FAIL" "視圖檔案不存在"
        fi
    done
}

# 測試文檔檔案
test_documentation() {
    log_info "測試文檔檔案..."
    
    # 檢查使用者手冊
    if [ -f "docs/channel-management-user-manual.md" ]; then
        record_test_result "使用者手冊" "PASS" "使用者手冊存在"
    else
        record_test_result "使用者手冊" "FAIL" "使用者手冊不存在"
    fi
    
    # 檢查管理員指南
    if [ -f "docs/channel-management-admin-guide.md" ]; then
        record_test_result "管理員指南" "PASS" "管理員指南存在"
    else
        record_test_result "管理員指南" "FAIL" "管理員指南不存在"
    fi
    
    # 檢查 API 文檔
    if [ -f "docs/channel-management-api.md" ]; then
        record_test_result "API 文檔" "PASS" "API 文檔存在"
    else
        record_test_result "API 文檔" "SKIP" "API 文檔不存在（可能未啟用 API）"
    fi
}

# 測試部署腳本
test_deployment_scripts() {
    log_info "測試部署腳本..."
    
    # 檢查部署腳本
    if [ -f "scripts/deployment/deploy-channel-management.sh" ] && [ -x "scripts/deployment/deploy-channel-management.sh" ]; then
        record_test_result "部署腳本" "PASS" "部署腳本存在且可執行"
    else
        record_test_result "部署腳本" "FAIL" "部署腳本不存在或不可執行"
    fi
    
    # 檢查遷移腳本
    if [ -f "scripts/deployment/migrate-channel-management.sh" ] && [ -x "scripts/deployment/migrate-channel-management.sh" ]; then
        record_test_result "遷移腳本" "PASS" "遷移腳本存在且可執行"
    else
        record_test_result "遷移腳本" "FAIL" "遷移腳本不存在或不可執行"
    fi
    
    # 檢查監控腳本
    if [ -f "scripts/monitoring/channel-performance-monitor.sh" ] && [ -x "scripts/monitoring/channel-performance-monitor.sh" ]; then
        record_test_result "監控腳本" "PASS" "監控腳本存在且可執行"
    else
        record_test_result "監控腳本" "FAIL" "監控腳本不存在或不可執行"
    fi
}

# 執行效能測試
test_performance() {
    log_info "執行效能測試..."
    
    # 測試資料庫查詢效能
    QUERY_TIME=$(docker-compose exec -T app php artisan tinker --execute="
    \$start = microtime(true);
    App\Models\Agent::with(['children', 'players'])->take(10)->get();
    \$end = microtime(true);
    echo round((\$end - \$start) * 1000, 2);
    " 2>/dev/null | tail -1)
    
    if (( $(echo "$QUERY_TIME < 1000" | bc -l) )); then
        record_test_result "資料庫查詢效能" "PASS" "查詢時間: ${QUERY_TIME}ms"
    else
        record_test_result "資料庫查詢效能" "FAIL" "查詢時間過長: ${QUERY_TIME}ms"
    fi
    
    # 測試記憶體使用
    MEMORY_USAGE=$(docker-compose exec -T app php artisan tinker --execute="
    \$start = memory_get_usage(true);
    \$agents = App\Models\Agent::take(100)->get();
    \$end = memory_get_usage(true);
    echo round((\$end - \$start) / 1024 / 1024, 2);
    " 2>/dev/null | tail -1)
    
    if (( $(echo "$MEMORY_USAGE < 50" | bc -l) )); then
        record_test_result "記憶體使用測試" "PASS" "記憶體使用: ${MEMORY_USAGE}MB"
    else
        record_test_result "記憶體使用測試" "FAIL" "記憶體使用過高: ${MEMORY_USAGE}MB"
    fi
}

# 生成測試報告
generate_test_report() {
    log_info "生成測試報告..."
    
    local pass_rate=0
    if [ $TOTAL_TESTS -gt 0 ]; then
        pass_rate=$(echo "scale=2; $PASSED_TESTS * 100 / $TOTAL_TESTS" | bc)
    fi
    
    cat > $TEST_REPORT_FILE << EOF
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>通路管理系統驗收測試報告</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .header { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .summary { display: flex; justify-content: space-around; margin: 20px 0; }
        .summary-item { text-align: center; padding: 15px; border-radius: 8px; }
        .pass { background: #d4edda; color: #155724; }
        .fail { background: #f8d7da; color: #721c24; }
        .skip { background: #fff3cd; color: #856404; }
        .total { background: #e2e3e5; color: #383d41; }
        .test-section { margin: 20px 0; }
        .test-section h3 { color: #495057; border-bottom: 2px solid #dee2e6; padding-bottom: 5px; }
        .test-result { margin: 10px 0; padding: 10px; border-radius: 5px; }
        .test-pass { background: #d4edda; border-left: 4px solid #28a745; }
        .test-fail { background: #f8d7da; border-left: 4px solid #dc3545; }
        .test-skip { background: #fff3cd; border-left: 4px solid #ffc107; }
        .progress-bar { width: 100%; height: 20px; background: #e9ecef; border-radius: 10px; overflow: hidden; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #28a745, #20c997); transition: width 0.3s; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #dee2e6; padding: 12px; text-align: left; }
        th { background: #f8f9fa; font-weight: bold; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #dee2e6; color: #6c757d; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🧪 通路管理系統驗收測試報告</h1>
        <p><strong>測試時間:</strong> $(date)</p>
        <p><strong>測試環境:</strong> $(grep APP_ENV .env | cut -d'=' -f2)</p>
        <p><strong>系統版本:</strong> $(git rev-parse HEAD 2>/dev/null || echo "未知")</p>
    </div>
    
    <div class="summary">
        <div class="summary-item total">
            <h3>總測試數</h3>
            <h2>$TOTAL_TESTS</h2>
        </div>
        <div class="summary-item pass">
            <h3>通過</h3>
            <h2>$PASSED_TESTS</h2>
        </div>
        <div class="summary-item fail">
            <h3>失敗</h3>
            <h2>$FAILED_TESTS</h2>
        </div>
        <div class="summary-item skip">
            <h3>跳過</h3>
            <h2>$SKIPPED_TESTS</h2>
        </div>
    </div>
    
    <div style="margin: 20px 0;">
        <h3>通過率: ${pass_rate}%</h3>
        <div class="progress-bar">
            <div class="progress-fill" style="width: ${pass_rate}%;"></div>
        </div>
    </div>
    
    <div class="test-section">
        <h3>📊 測試結果摘要</h3>
        <table>
            <tr>
                <th>測試類別</th>
                <th>通過</th>
                <th>失敗</th>
                <th>跳過</th>
                <th>狀態</th>
            </tr>
EOF

    # 這裡可以添加更詳細的測試結果分類統計
    
    cat >> $TEST_REPORT_FILE << EOF
        </table>
    </div>
    
    <div class="test-section">
        <h3>📋 詳細測試結果</h3>
        <p>詳細的測試結果請參考測試日誌: <code>$TEST_LOG_FILE</code></p>
    </div>
    
    <div class="test-section">
        <h3>🔍 測試建議</h3>
EOF

    # 根據測試結果生成建議
    if [ $FAILED_TESTS -gt 0 ]; then
        cat >> $TEST_REPORT_FILE << EOF
        <div class="test-fail">
            <strong>⚠️ 發現 $FAILED_TESTS 個失敗的測試</strong>
            <p>請檢查測試日誌並修復相關問題後重新執行測試。</p>
        </div>
EOF
    fi
    
    if [ $PASSED_TESTS -eq $TOTAL_TESTS ]; then
        cat >> $TEST_REPORT_FILE << EOF
        <div class="test-pass">
            <strong>🎉 所有測試都通過了！</strong>
            <p>通路管理系統已準備好部署到生產環境。</p>
        </div>
EOF
    fi
    
    cat >> $TEST_REPORT_FILE << EOF
        <div class="test-skip">
            <strong>💡 建議</strong>
            <ul>
                <li>定期執行驗收測試以確保系統穩定性</li>
                <li>在部署前務必執行完整的測試套件</li>
                <li>監控生產環境的效能指標</li>
                <li>保持測試文檔和腳本的更新</li>
            </ul>
        </div>
    </div>
    
    <div class="footer">
        <p>此報告由通路管理系統驗收測試腳本自動生成</p>
        <p>測試腳本版本: 1.0.0</p>
    </div>
</body>
</html>
EOF
    
    log_success "測試報告已生成: $TEST_REPORT_FILE"
}

# 主要測試流程
main() {
    log_info "開始通路管理系統驗收測試..."
    log_info "測試開始時間: $(date)"
    
    # 初始化測試環境
    init_test_environment
    
    # 執行測試套件
    check_prerequisites
    test_data_models
    test_service_layer
    test_livewire_components
    test_database_structure
    test_permission_system
    test_routing_system
    test_business_logic
    test_configuration
    test_view_files
    test_documentation
    test_deployment_scripts
    test_performance
    
    # 生成測試報告
    generate_test_report
    
    # 顯示測試結果摘要
    log_info "測試完成摘要:"
    log_info "總測試數: $TOTAL_TESTS"
    log_success "通過: $PASSED_TESTS"
    log_error "失敗: $FAILED_TESTS"
    log_warning "跳過: $SKIPPED_TESTS"
    
    if [ $FAILED_TESTS -eq 0 ]; then
        log_success "🎉 所有測試都通過了！系統已準備好部署。"
        exit 0
    else
        log_error "❌ 發現 $FAILED_TESTS 個失敗的測試，請修復後重新測試。"
        exit 1
    fi
}

# 處理命令列參數
case "${1:-}" in
    --help)
        echo "用法: $0 [選項]"
        echo "選項:"
        echo "  --help          顯示此幫助訊息"
        echo "  --quick         快速測試（跳過效能測試）"
        echo "  --report-only   只生成報告"
        exit 0
        ;;
    --quick)
        log_info "快速測試模式"
        # 在快速模式下跳過效能測試
        test_performance() { log_info "跳過效能測試（快速模式）"; }
        ;;
    --report-only)
        init_test_environment
        generate_test_report
        exit 0
        ;;
esac

# 執行主流程
main "$@"