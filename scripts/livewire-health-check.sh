#!/bin/bash

# Livewire 表單重置健康檢查腳本
# 持續監控 Livewire 表單重置功能的健康狀態

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
ENVIRONMENT=${1:-production}
SILENT_MODE=${2:-false}

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

# 健康檢查結果
HEALTH_STATUS="healthy"
HEALTH_ISSUES=()
HEALTH_WARNINGS=()

# 記錄健康檢查結果
log_health_result() {
    local level=$1
    local message=$2
    
    case $level in
        "error")
            HEALTH_STATUS="unhealthy"
            HEALTH_ISSUES+=("$message")
            log_error "$message"
            ;;
        "warning")
            if [ "$HEALTH_STATUS" = "healthy" ]; then
                HEALTH_STATUS="degraded"
            fi
            HEALTH_WARNINGS+=("$message")
            log_warning "$message"
            ;;
        "success")
            if [ "$SILENT_MODE" != "true" ]; then
                log_success "$message"
            fi
            ;;
    esac
}

# 檢查容器健康狀態
check_container_health() {
    if [ "$SILENT_MODE" != "true" ]; then
        log_info "檢查容器健康狀態..."
    fi
    
    local containers=("app" "mysql" "redis" "nginx")
    local unhealthy_containers=()
    
    for container in "${containers[@]}"; do
        local container_status=$(docker-compose -f "$COMPOSE_FILE" ps "$container" --format "{{.State}}" 2>/dev/null || echo "not_found")
        
        case $container_status in
            "running")
                # 檢查健康檢查狀態（如果有）
                local health_status=$(docker inspect "$(docker-compose -f "$COMPOSE_FILE" ps -q "$container" 2>/dev/null)" --format='{{.State.Health.Status}}' 2>/dev/null || echo "no_healthcheck")
                
                if [ "$health_status" = "unhealthy" ]; then
                    unhealthy_containers+=("$container")
                fi
                ;;
            "not_found"|"")
                log_health_result "warning" "容器 $container 不存在或未運行"
                ;;
            *)
                unhealthy_containers+=("$container")
                ;;
        esac
    done
    
    if [ ${#unhealthy_containers[@]} -eq 0 ]; then
        log_health_result "success" "所有容器健康狀態正常"
    else
        log_health_result "error" "發現不健康的容器: ${unhealthy_containers[*]}"
    fi
}

# 檢查 Livewire 元件狀態
check_livewire_components() {
    if [ "$SILENT_MODE" != "true" ]; then
        log_info "檢查 Livewire 元件狀態..."
    fi
    
    # 檢查 Livewire 元件發現
    if docker-compose -f "$COMPOSE_FILE" exec -T app php artisan livewire:discover > /dev/null 2>&1; then
        log_health_result "success" "Livewire 元件發現正常"
    else
        log_health_result "error" "Livewire 元件發現失敗"
    fi
    
    # 檢查關鍵元件載入
    local critical_components=(
        "admin.users.user-list"
        "admin.activities.activity-export"
        "admin.permissions.permission-audit-log"
    )
    
    local failed_components=()
    
    for component in "${critical_components[@]}"; do
        if ! docker-compose -f "$COMPOSE_FILE" exec -T app php artisan tinker --execute="
            try {
                app('livewire')->getClass('$component');
                echo 'OK';
            } catch (Exception \$e) {
                echo 'FAIL';
            }
        " 2>/dev/null | grep -q "OK"; then
            failed_components+=("$component")
        fi
    done
    
    if [ ${#failed_components[@]} -eq 0 ]; then
        log_health_result "success" "關鍵 Livewire 元件載入正常"
    else
        log_health_result "error" "關鍵元件載入失敗: ${failed_components[*]}"
    fi
}

# 檢查資料庫連線
check_database_connection() {
    if [ "$SILENT_MODE" != "true" ]; then
        log_info "檢查資料庫連線..."
    fi
    
    if docker-compose -f "$COMPOSE_FILE" exec -T app php artisan tinker --execute="
        use Illuminate\Support\Facades\DB;
        try {
            DB::connection()->getPdo();
            echo 'OK';
        } catch (Exception \$e) {
            echo 'FAIL: ' . \$e->getMessage();
        }
    " 2>/dev/null | grep -q "OK"; then
        log_health_result "success" "資料庫連線正常"
    else
        log_health_result "error" "資料庫連線失敗"
    fi
    
    # 檢查資料庫查詢效能
    local query_time=$(docker-compose -f "$COMPOSE_FILE" exec -T app php artisan tinker --execute="
        use Illuminate\Support\Facades\DB;
        \$start = microtime(true);
        DB::table('users')->count();
        \$end = microtime(true);
        echo round((\$end - \$start) * 1000, 2);
    " 2>/dev/null || echo "999")
    
    if [ "$(echo "$query_time < 100" | bc -l 2>/dev/null || echo "0")" = "1" ]; then
        log_health_result "success" "資料庫查詢效能正常 (${query_time}ms)"
    elif [ "$(echo "$query_time < 500" | bc -l 2>/dev/null || echo "0")" = "1" ]; then
        log_health_result "warning" "資料庫查詢效能較慢 (${query_time}ms)"
    else
        log_health_result "error" "資料庫查詢效能異常 (${query_time}ms)"
    fi
}

# 檢查 Redis 連線
check_redis_connection() {
    if [ "$SILENT_MODE" != "true" ]; then
        log_info "檢查 Redis 連線..."
    fi
    
    if docker-compose -f "$COMPOSE_FILE" exec -T app php artisan tinker --execute="
        use Illuminate\Support\Facades\Redis;
        try {
            Redis::ping();
            echo 'OK';
        } catch (Exception \$e) {
            echo 'FAIL: ' . \$e->getMessage();
        }
    " 2>/dev/null | grep -q "OK"; then
        log_health_result "success" "Redis 連線正常"
    else
        log_health_result "error" "Redis 連線失敗"
    fi
    
    # 檢查 Redis 記憶體使用
    local redis_memory=$(docker-compose -f "$COMPOSE_FILE" exec -T redis redis-cli --no-auth-warning -a "$(cat secrets/redis_password.txt 2>/dev/null || echo '')" info memory 2>/dev/null | grep "used_memory_human" | cut -d: -f2 | tr -d '\r' || echo "unknown")
    
    if [ "$redis_memory" != "unknown" ]; then
        log_health_result "success" "Redis 記憶體使用: $redis_memory"
    else
        log_health_result "warning" "無法獲取 Redis 記憶體使用情況"
    fi
}

# 檢查應用程式回應
check_application_response() {
    if [ "$SILENT_MODE" != "true" ]; then
        log_info "檢查應用程式回應..."
    fi
    
    # 檢查關鍵頁面
    local test_urls=(
        "$BASE_URL/admin/login"
        "$BASE_URL/admin/dashboard"
        "$BASE_URL/admin/users"
    )
    
    local failed_urls=()
    local slow_urls=()
    
    for url in "${test_urls[@]}"; do
        local response_time=$(curl -o /dev/null -s -w "%{time_total}:%{http_code}" "$url" 2>/dev/null || echo "999:000")
        local time=$(echo "$response_time" | cut -d: -f1)
        local code=$(echo "$response_time" | cut -d: -f2)
        
        if [ "$code" = "200" ] || [ "$code" = "302" ]; then
            if [ "$(echo "$time < 2" | bc -l 2>/dev/null || echo "0")" = "1" ]; then
                log_health_result "success" "URL $url 回應正常 (${time}s)"
            else
                slow_urls+=("$url (${time}s)")
            fi
        else
            failed_urls+=("$url (HTTP $code)")
        fi
    done
    
    if [ ${#failed_urls[@]} -gt 0 ]; then
        log_health_result "error" "URL 回應失敗: ${failed_urls[*]}"
    fi
    
    if [ ${#slow_urls[@]} -gt 0 ]; then
        log_health_result "warning" "URL 回應較慢: ${slow_urls[*]}"
    fi
}

# 檢查錯誤日誌
check_error_logs() {
    if [ "$SILENT_MODE" != "true" ]; then
        log_info "檢查錯誤日誌..."
    fi
    
    # 檢查最近的應用程式錯誤
    local recent_errors=0
    
    if docker-compose -f "$COMPOSE_FILE" exec -T app test -f storage/logs/laravel.log; then
        recent_errors=$(docker-compose -f "$COMPOSE_FILE" exec -T app tail -n 100 storage/logs/laravel.log | grep -i "error\|exception\|fatal" | grep "$(date +%Y-%m-%d)" | wc -l)
    fi
    
    if [ $recent_errors -eq 0 ]; then
        log_health_result "success" "今日沒有發現應用程式錯誤"
    elif [ $recent_errors -lt 5 ]; then
        log_health_result "warning" "今日發現 $recent_errors 個應用程式錯誤"
    else
        log_health_result "error" "今日發現 $recent_errors 個應用程式錯誤（異常）"
    fi
    
    # 檢查容器錯誤
    local container_errors=$(docker-compose -f "$COMPOSE_FILE" logs --since="1h" app 2>&1 | grep -i "error\|exception\|fatal" | wc -l)
    
    if [ $container_errors -eq 0 ]; then
        log_health_result "success" "過去 1 小時沒有容器錯誤"
    elif [ $container_errors -lt 3 ]; then
        log_health_result "warning" "過去 1 小時發現 $container_errors 個容器錯誤"
    else
        log_health_result "error" "過去 1 小時發現 $container_errors 個容器錯誤（異常）"
    fi
}

# 檢查系統資源
check_system_resources() {
    if [ "$SILENT_MODE" != "true" ]; then
        log_info "檢查系統資源..."
    fi
    
    # 檢查磁碟空間
    local disk_usage=$(df . | tail -1 | awk '{print $5}' | sed 's/%//')
    
    if [ $disk_usage -lt 80 ]; then
        log_health_result "success" "磁碟使用率正常 ($disk_usage%)"
    elif [ $disk_usage -lt 90 ]; then
        log_health_result "warning" "磁碟使用率較高 ($disk_usage%)"
    else
        log_health_result "error" "磁碟使用率過高 ($disk_usage%)"
    fi
    
    # 檢查記憶體使用（如果可用）
    if command -v free > /dev/null; then
        local memory_usage=$(free | grep Mem | awk '{printf "%.0f", $3/$2 * 100.0}')
        
        if [ $memory_usage -lt 80 ]; then
            log_health_result "success" "記憶體使用率正常 ($memory_usage%)"
        elif [ $memory_usage -lt 90 ]; then
            log_health_result "warning" "記憶體使用率較高 ($memory_usage%)"
        else
            log_health_result "error" "記憶體使用率過高 ($memory_usage%)"
        fi
    fi
}

# 檢查表單重置功能
check_form_reset_functionality() {
    if [ "$SILENT_MODE" != "true" ]; then
        log_info "檢查表單重置功能..."
    fi
    
    # 檢查修復標記
    local defer_count=$(find resources/views/livewire -name "*.blade.php" -exec grep -l "wire:model\.defer" {} \; 2>/dev/null | wc -l)
    local refresh_count=$(find app/Livewire -name "*.php" -exec grep -l "dispatch.*refresh" {} \; 2>/dev/null | wc -l)
    
    if [ $defer_count -gt 0 ] && [ $refresh_count -gt 0 ]; then
        log_health_result "success" "表單重置修復標記正常 (defer: $defer_count, refresh: $refresh_count)"
    elif [ $defer_count -gt 0 ] || [ $refresh_count -gt 0 ]; then
        log_health_result "warning" "部分表單重置修復標記存在 (defer: $defer_count, refresh: $refresh_count)"
    else
        log_health_result "error" "沒有找到表單重置修復標記"
    fi
    
    # 檢查是否還有未修復的 wire:model.lazy
    local lazy_count=$(find resources/views/livewire -name "*.blade.php" -exec grep -l "wire:model\.lazy" {} \; 2>/dev/null | wc -l)
    
    if [ $lazy_count -eq 0 ]; then
        log_health_result "success" "沒有發現未修復的 wire:model.lazy"
    else
        log_health_result "warning" "發現 $lazy_count 個未修復的 wire:model.lazy"
    fi
}

# 生成健康檢查報告
generate_health_report() {
    local timestamp=$(date +%Y%m%d_%H%M%S)
    local report_file="health-reports/health-check-$timestamp.json"
    
    # 建立報告目錄
    mkdir -p health-reports
    
    # 生成 JSON 報告
    cat > "$report_file" << EOF
{
  "timestamp": "$(date -Iseconds)",
  "environment": "$ENVIRONMENT",
  "status": "$HEALTH_STATUS",
  "git_commit": "$(git rev-parse HEAD 2>/dev/null || echo 'unknown')",
  "issues": [
$(printf '    "%s"' "${HEALTH_ISSUES[@]}" | sed 's/$/,/' | sed '$s/,$//')
  ],
  "warnings": [
$(printf '    "%s"' "${HEALTH_WARNINGS[@]}" | sed 's/$/,/' | sed '$s/,$//')
  ],
  "checks": {
    "containers": "$(docker-compose -f "$COMPOSE_FILE" ps --filter "health=healthy" -q | wc -l) healthy",
    "database": "$(docker-compose -f "$COMPOSE_FILE" exec -T app php artisan tinker --execute="DB::connection()->getPdo(); echo 'OK';" 2>/dev/null | grep -q "OK" && echo "connected" || echo "failed")",
    "redis": "$(docker-compose -f "$COMPOSE_FILE" exec -T app php artisan tinker --execute="Redis::ping(); echo 'OK';" 2>/dev/null | grep -q "OK" && echo "connected" || echo "failed")",
    "disk_usage": "$(df . | tail -1 | awk '{print $5}')",
    "response_time": "$(curl -o /dev/null -s -w "%{time_total}" "$BASE_URL/admin/login" 2>/dev/null || echo "unknown")s"
  }
}
EOF
    
    if [ "$SILENT_MODE" != "true" ]; then
        log_info "健康檢查報告已生成: $report_file"
    fi
    
    # 保留最近 30 天的報告
    find health-reports -name "health-check-*.json" -mtime +30 -delete 2>/dev/null || true
}

# 發送警報（如果配置了）
send_alerts() {
    if [ "$HEALTH_STATUS" = "unhealthy" ] && [ -f "scripts/send-alert.sh" ]; then
        if [ "$SILENT_MODE" != "true" ]; then
            log_info "發送健康檢查警報..."
        fi
        
        local alert_message="Livewire 健康檢查失敗 - 環境: $ENVIRONMENT, 問題: ${#HEALTH_ISSUES[@]} 個"
        ./scripts/send-alert.sh "health-check" "$alert_message" "${HEALTH_ISSUES[*]}"
    fi
}

# 主要健康檢查函數
main() {
    if [ "$SILENT_MODE" != "true" ]; then
        echo "🏥 Livewire 表單重置健康檢查"
        echo "=============================="
        echo "環境: $ENVIRONMENT"
        echo "時間: $(date)"
        echo ""
    fi
    
    local checks=(
        "check_container_health"
        "check_livewire_components"
        "check_database_connection"
        "check_redis_connection"
        "check_application_response"
        "check_error_logs"
        "check_system_resources"
        "check_form_reset_functionality"
    )
    
    for check in "${checks[@]}"; do
        $check
    done
    
    # 生成報告
    generate_health_report
    
    # 發送警報
    send_alerts
    
    if [ "$SILENT_MODE" != "true" ]; then
        echo ""
        echo "=============================="
        
        case $HEALTH_STATUS in
            "healthy")
                log_success "🎉 系統健康狀態良好！"
                ;;
            "degraded")
                log_warning "⚠️ 系統運行正常但有警告 (${#HEALTH_WARNINGS[@]} 個)"
                ;;
            "unhealthy")
                log_error "❌ 系統健康檢查失敗 (${#HEALTH_ISSUES[@]} 個問題)"
                ;;
        esac
        
        echo ""
        log_info "健康檢查摘要："
        echo "  狀態: $HEALTH_STATUS"
        echo "  問題: ${#HEALTH_ISSUES[@]} 個"
        echo "  警告: ${#HEALTH_WARNINGS[@]} 個"
        echo "  報告: health-reports/health-check-$(date +%Y%m%d_%H%M%S).json"
    fi
    
    # 設定退出碼
    case $HEALTH_STATUS in
        "healthy")
            exit 0
            ;;
        "degraded")
            exit 1
            ;;
        "unhealthy")
            exit 2
            ;;
    esac
}

# 執行主函數
main "$@"