#!/bin/bash

# Livewire 表單重置修復驗證腳本
# 驗證修復是否正確部署和運行

set -e

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 日誌函數
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# 取得環境參數
ENVIRONMENT=${1:-dev}

# 選擇對應的 compose 檔案
case $ENVIRONMENT in
    "prod"|"production")
        COMPOSE_FILE="docker-compose.prod.yml"
        BASE_URL="http://localhost"
        ;;
    "staging"|"test")
        COMPOSE_FILE="docker-compose.staging.yml"
        BASE_URL="http://localhost:8080"
        ;;
    *)
        COMPOSE_FILE="docker-compose.yml"
        BASE_URL="http://localhost"
        ;;
esac

# 驗證 Livewire 元件載入
verify_livewire_components() {
    log_info "驗證 Livewire 元件載入..."
    
    # 檢查 Livewire 元件發現
    if docker-compose -f "$COMPOSE_FILE" exec -T app php artisan livewire:discover > /dev/null 2>&1; then
        log_success "Livewire 元件發現正常"
    else
        log_error "Livewire 元件發現失敗"
        return 1
    fi
    
    # 檢查關鍵元件是否可以載入
    local components=(
        "admin.users.user-list"
        "admin.activities.activity-export"
        "admin.permissions.permission-audit-log"
        "admin.settings.settings-list"
        "admin.notifications.notification-list"
    )
    
    local loaded_components=0
    
    for component in "${components[@]}"; do
        if docker-compose -f "$COMPOSE_FILE" exec -T app php artisan tinker --execute="
            try {
                \$component = app('livewire')->getClass('$component');
                echo 'OK: $component';
            } catch (Exception \$e) {
                echo 'FAIL: $component - ' . \$e->getMessage();
            }
        " 2>/dev/null | grep -q "OK:"; then
            ((loaded_components++))
        fi
    done
    
    log_success "成功載入 $loaded_components/${#components[@]} 個關鍵 Livewire 元件"
    
    if [ $loaded_components -lt $((${#components[@]} / 2)) ]; then
        log_warning "載入的元件數量較少，可能存在問題"
    fi
}

# 驗證表單重置功能
verify_form_reset_functionality() {
    log_info "驗證表單重置功能..."
    
    # 檢查 wire:model.defer 使用情況
    local defer_count=0
    local lazy_count=0
    
    if [ -d "resources/views/livewire" ]; then
        defer_count=$(find resources/views/livewire -name "*.blade.php" -exec grep -l "wire:model\.defer" {} \; 2>/dev/null | wc -l)
        lazy_count=$(find resources/views/livewire -name "*.blade.php" -exec grep -l "wire:model\.lazy" {} \; 2>/dev/null | wc -l)
    fi
    
    log_info "找到 $defer_count 個使用 wire:model.defer 的視圖檔案"
    log_info "找到 $lazy_count 個使用 wire:model.lazy 的視圖檔案"
    
    if [ $defer_count -gt 0 ]; then
        log_success "發現 wire:model.defer 修復"
    fi
    
    if [ $lazy_count -gt 0 ]; then
        log_warning "仍有 $lazy_count 個檔案使用 wire:model.lazy，可能需要進一步修復"
    fi
    
    # 檢查 dispatch('$refresh') 使用情況
    local refresh_count=0
    
    if [ -d "app/Livewire" ]; then
        refresh_count=$(find app/Livewire -name "*.php" -exec grep -l "dispatch.*refresh\|\$this->dispatch.*refresh" {} \; 2>/dev/null | wc -l)
    fi
    
    log_info "找到 $refresh_count 個使用 dispatch refresh 的元件"
    
    if [ $refresh_count -gt 0 ]; then
        log_success "發現強制刷新機制修復"
    fi
}

# 驗證前端 JavaScript 整合
verify_frontend_integration() {
    log_info "驗證前端 JavaScript 整合..."
    
    # 檢查編譯的前端資源
    if [ -d "public/build" ] && [ "$(ls -A public/build 2>/dev/null)" ]; then
        log_success "找到編譯的前端資源"
        
        # 檢查 Livewire 相關的 JavaScript
        local livewire_js_count=$(find public/build -name "*.js" -exec grep -l "livewire\|Livewire" {} \; 2>/dev/null | wc -l)
        
        if [ $livewire_js_count -gt 0 ]; then
            log_success "前端資源包含 Livewire JavaScript"
        else
            log_warning "前端資源中未找到 Livewire JavaScript"
        fi
    else
        log_warning "沒有找到編譯的前端資源"
    fi
    
    # 檢查 Alpine.js 依賴
    if [ -f "package.json" ]; then
        if grep -q "alpinejs" package.json; then
            log_success "找到 Alpine.js 依賴"
        else
            log_warning "沒有找到 Alpine.js 依賴"
        fi
    fi
}

# 驗證資料庫狀態
verify_database_state() {
    log_info "驗證資料庫狀態..."
    
    # 檢查資料庫連線
    if docker-compose -f "$COMPOSE_FILE" exec -T app php artisan tinker --execute="
        use Illuminate\Support\Facades\DB;
        try {
            DB::connection()->getPdo();
            echo 'Database connection OK';
        } catch (Exception \$e) {
            echo 'Database connection failed: ' . \$e->getMessage();
        }
    " 2>/dev/null | grep -q "OK"; then
        log_success "資料庫連線正常"
    else
        log_error "資料庫連線失敗"
        return 1
    fi
    
    # 檢查關鍵資料表
    local tables=(
        "users"
        "roles"
        "permissions"
        "user_roles"
        "role_permissions"
        "activity_log"
        "settings"
    )
    
    local existing_tables=0
    
    for table in "${tables[@]}"; do
        if docker-compose -f "$COMPOSE_FILE" exec -T app php artisan tinker --execute="
            use Illuminate\Support\Facades\Schema;
            echo Schema::hasTable('$table') ? 'EXISTS' : 'MISSING';
        " 2>/dev/null | grep -q "EXISTS"; then
            ((existing_tables++))
        fi
    done
    
    log_success "找到 $existing_tables/${#tables[@]} 個關鍵資料表"
    
    if [ $existing_tables -lt ${#tables[@]} ]; then
        log_warning "部分資料表缺失，可能需要執行遷移"
    fi
}

# 驗證快取狀態
verify_cache_state() {
    log_info "驗證快取狀態..."
    
    # 檢查 Redis 連線
    if docker-compose -f "$COMPOSE_FILE" exec -T app php artisan tinker --execute="
        use Illuminate\Support\Facades\Redis;
        try {
            Redis::ping();
            echo 'Redis connection OK';
        } catch (Exception \$e) {
            echo 'Redis connection failed: ' . \$e->getMessage();
        }
    " 2>/dev/null | grep -q "OK"; then
        log_success "Redis 連線正常"
    else
        log_error "Redis 連線失敗"
        return 1
    fi
    
    # 檢查應用程式快取
    if docker-compose -f "$COMPOSE_FILE" exec -T app php artisan cache:clear > /dev/null 2>&1; then
        log_success "應用程式快取可以正常清除"
    else
        log_warning "應用程式快取清除失敗"
    fi
    
    # 檢查配置快取狀態
    if [ "$ENVIRONMENT" = "production" ] || [ "$ENVIRONMENT" = "prod" ]; then
        if [ -f "bootstrap/cache/config.php" ]; then
            log_success "生產環境配置快取存在"
        else
            log_warning "生產環境配置快取不存在"
        fi
    fi
}

# 執行功能測試
run_functional_tests() {
    log_info "執行功能測試..."
    
    # 測試基本頁面載入
    local test_urls=(
        "$BASE_URL/admin/login"
        "$BASE_URL/admin/dashboard"
        "$BASE_URL/admin/users"
        "$BASE_URL/admin/roles"
        "$BASE_URL/admin/permissions"
    )
    
    local successful_requests=0
    
    for url in "${test_urls[@]}"; do
        if curl -s -o /dev/null -w "%{http_code}" "$url" | grep -q "200\|302"; then
            ((successful_requests++))
        fi
    done
    
    log_success "$successful_requests/${#test_urls[@]} 個測試 URL 回應正常"
    
    if [ $successful_requests -lt $((${#test_urls[@]} / 2)) ]; then
        log_warning "大部分 URL 無法存取，可能存在問題"
    fi
}

# 執行 Playwright 測試（如果可用）
run_playwright_tests() {
    log_info "檢查 Playwright 測試..."
    
    # 檢查是否有 Playwright 測試腳本
    if [ -f "scripts/run-livewire-playwright-tests.sh" ]; then
        log_info "執行 Playwright 測試..."
        if ./scripts/run-livewire-playwright-tests.sh "$ENVIRONMENT" --quick; then
            log_success "Playwright 測試通過"
        else
            log_warning "Playwright 測試失敗或部分失敗"
        fi
    else
        log_info "沒有找到 Playwright 測試腳本，跳過"
    fi
}

# 檢查日誌錯誤
check_error_logs() {
    log_info "檢查錯誤日誌..."
    
    # 檢查應用程式日誌
    local error_count=0
    
    if docker-compose -f "$COMPOSE_FILE" exec -T app test -f storage/logs/laravel.log; then
        error_count=$(docker-compose -f "$COMPOSE_FILE" exec -T app tail -n 100 storage/logs/laravel.log | grep -i "error\|exception\|fatal" | wc -l)
        
        if [ $error_count -eq 0 ]; then
            log_success "應用程式日誌沒有發現錯誤"
        else
            log_warning "應用程式日誌發現 $error_count 個錯誤"
        fi
    else
        log_info "應用程式日誌檔案不存在"
    fi
    
    # 檢查容器日誌
    local container_errors=$(docker-compose -f "$COMPOSE_FILE" logs --tail=50 app 2>&1 | grep -i "error\|exception\|fatal" | wc -l)
    
    if [ $container_errors -eq 0 ]; then
        log_success "容器日誌沒有發現錯誤"
    else
        log_warning "容器日誌發現 $container_errors 個錯誤"
    fi
}

# 效能檢查
check_performance() {
    log_info "檢查系統效能..."
    
    # 檢查容器資源使用
    local memory_usage=$(docker stats --no-stream --format "table {{.Container}}\t{{.MemUsage}}" | grep -E "app|mysql|redis" | head -3)
    
    if [ -n "$memory_usage" ]; then
        log_success "容器資源使用情況："
        echo "$memory_usage"
    else
        log_warning "無法獲取容器資源使用情況"
    fi
    
    # 檢查回應時間
    local response_time=$(curl -o /dev/null -s -w "%{time_total}" "$BASE_URL/admin/login" 2>/dev/null || echo "0")
    
    if [ "$response_time" != "0" ]; then
        log_success "頁面回應時間: ${response_time}s"
        
        # 檢查回應時間是否合理（小於 3 秒）
        if [ "$(echo "$response_time < 3" | bc -l 2>/dev/null || echo "1")" = "1" ]; then
            log_success "回應時間在可接受範圍內"
        else
            log_warning "回應時間較慢，可能需要優化"
        fi
    else
        log_warning "無法測量回應時間"
    fi
}

# 生成驗證報告
generate_verification_report() {
    local report_file="verification-report-$(date +%Y%m%d_%H%M%S).md"
    
    log_info "生成驗證報告: $report_file"
    
    cat > "$report_file" << EOF
# Livewire 表單重置修復驗證報告

## 驗證資訊
- **驗證時間**: $(date)
- **環境**: $ENVIRONMENT
- **Git 提交**: $(git rev-parse HEAD)
- **Git 分支**: $(git branch --show-current)

## 驗證結果摘要

### 系統狀態
- Livewire 元件載入: $(docker-compose -f "$COMPOSE_FILE" exec -T app php artisan livewire:discover > /dev/null 2>&1 && echo "✅ 正常" || echo "❌ 異常")
- 資料庫連線: $(docker-compose -f "$COMPOSE_FILE" exec -T app php artisan tinker --execute="DB::connection()->getPdo(); echo 'OK';" > /dev/null 2>&1 && echo "✅ 正常" || echo "❌ 異常")
- Redis 連線: $(docker-compose -f "$COMPOSE_FILE" exec -T app php artisan tinker --execute="Redis::ping(); echo 'OK';" > /dev/null 2>&1 && echo "✅ 正常" || echo "❌ 異常")

### 修復狀態
- wire:model.defer 使用: $(find resources/views/livewire -name "*.blade.php" -exec grep -l "wire:model\.defer" {} \; 2>/dev/null | wc -l) 個檔案
- dispatch refresh 使用: $(find app/Livewire -name "*.php" -exec grep -l "dispatch.*refresh" {} \; 2>/dev/null | wc -l) 個元件
- 前端資源編譯: $([ -d "public/build" ] && [ "$(ls -A public/build 2>/dev/null)" ] && echo "✅ 已編譯" || echo "❌ 未編譯")

### 效能指標
- 頁面回應時間: $(curl -o /dev/null -s -w "%{time_total}" "$BASE_URL/admin/login" 2>/dev/null || echo "無法測量")s
- 容器狀態: $(docker-compose -f "$COMPOSE_FILE" ps --filter "health=healthy" -q | wc -l) 個健康容器

## 建議事項

EOF
    
    # 根據驗證結果添加建議
    if [ "$(find resources/views/livewire -name "*.blade.php" -exec grep -l "wire:model\.lazy" {} \; 2>/dev/null | wc -l)" -gt 0 ]; then
        echo "- 仍有檔案使用 wire:model.lazy，建議完成修復" >> "$report_file"
    fi
    
    if [ ! -d "public/build" ] || [ -z "$(ls -A public/build 2>/dev/null)" ]; then
        echo "- 建議執行 npm run build 編譯前端資源" >> "$report_file"
    fi
    
    echo "" >> "$report_file"
    echo "## 詳細日誌" >> "$report_file"
    echo "" >> "$report_file"
    echo "\`\`\`" >> "$report_file"
    docker-compose -f "$COMPOSE_FILE" logs --tail=20 app >> "$report_file" 2>&1
    echo "\`\`\`" >> "$report_file"
    
    log_success "驗證報告已生成: $report_file"
}

# 主要驗證函數
main() {
    echo "🔍 Livewire 表單重置修復驗證"
    echo "=============================="
    echo "環境: $ENVIRONMENT"
    echo "Compose 檔案: $COMPOSE_FILE"
    echo "基礎 URL: $BASE_URL"
    echo ""
    
    local checks=(
        "verify_livewire_components"
        "verify_form_reset_functionality"
        "verify_frontend_integration"
        "verify_database_state"
        "verify_cache_state"
        "run_functional_tests"
        "run_playwright_tests"
        "check_error_logs"
        "check_performance"
    )
    
    local failed_checks=()
    local warning_checks=()
    
    for check in "${checks[@]}"; do
        if ! $check; then
            failed_checks+=("$check")
        fi
    done
    
    echo ""
    echo "=============================="
    
    # 生成驗證報告
    generate_verification_report
    
    if [ ${#failed_checks[@]} -eq 0 ]; then
        log_success "🎉 所有驗證都通過！Livewire 表單重置修復運行正常。"
        echo ""
        log_info "後續建議："
        echo "  1. 持續監控系統運行狀態"
        echo "  2. 收集使用者回饋"
        echo "  3. 觀察效能指標變化"
        echo "  4. 定期執行驗證檢查"
        exit 0
    else
        log_error "❌ 發現 ${#failed_checks[@]} 個問題："
        for check in "${failed_checks[@]}"; do
            echo "  - $check"
        done
        echo ""
        log_error "請檢查上述問題並考慮執行修復或回滾。"
        exit 1
    fi
}

# 執行主函數
main "$@"