#!/bin/bash

# Laravel Admin 系統部署後驗證腳本
# 確保部署成功且所有服務正常運行

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

# 設定環境
ENVIRONMENT=${1:-prod}
COMPOSE_FILE="docker-compose.${ENVIRONMENT}.yml"

# 檢查 Docker Compose 指令
if docker compose version &> /dev/null; then
    COMPOSE_CMD="docker compose"
else
    COMPOSE_CMD="docker-compose"
fi

# 檢查容器狀態
check_container_status() {
    log_info "檢查容器狀態..."
    
    local containers=(
        "laravel_admin_app_${ENVIRONMENT}"
        "laravel_admin_nginx_${ENVIRONMENT}"
        "laravel_admin_mysql_${ENVIRONMENT}"
        "laravel_admin_redis_${ENVIRONMENT}"
    )
    
    local failed_containers=()
    
    for container in "${containers[@]}"; do
        if ! docker ps -q -f name="$container" | grep -q .; then
            failed_containers+=("$container")
        fi
    done
    
    if [ ${#failed_containers[@]} -eq 0 ]; then
        log_success "所有容器都在運行"
    else
        log_error "以下容器未運行："
        for container in "${failed_containers[@]}"; do
            echo "  - $container"
        done
        return 1
    fi
}

# 檢查容器健康狀態
check_container_health() {
    log_info "檢查容器健康狀態..."
    
    local containers=(
        "laravel_admin_app_${ENVIRONMENT}"
        "laravel_admin_mysql_${ENVIRONMENT}"
        "laravel_admin_redis_${ENVIRONMENT}"
    )
    
    local unhealthy_containers=()
    
    for container in "${containers[@]}"; do
        local health_status=$(docker inspect "$container" --format='{{.State.Health.Status}}' 2>/dev/null || echo "no-healthcheck")
        
        if [ "$health_status" = "unhealthy" ]; then
            unhealthy_containers+=("$container")
        elif [ "$health_status" = "starting" ]; then
            log_warning "$container 健康檢查仍在啟動中..."
        elif [ "$health_status" = "healthy" ]; then
            log_success "$container 健康狀態正常"
        fi
    done
    
    if [ ${#unhealthy_containers[@]} -gt 0 ]; then
        log_error "以下容器健康檢查失敗："
        for container in "${unhealthy_containers[@]}"; do
            echo "  - $container"
            # 顯示健康檢查日誌
            docker inspect "$container" --format='{{range .State.Health.Log}}{{.Output}}{{end}}' | head -5
        done
        return 1
    fi
}

# 檢查應用程式連線
check_app_connectivity() {
    log_info "檢查應用程式連線..."
    
    # 測試 Laravel Artisan 指令
    if $COMPOSE_CMD -f "$COMPOSE_FILE" exec -T app php artisan --version > /dev/null 2>&1; then
        log_success "Laravel Artisan 指令正常"
    else
        log_error "Laravel Artisan 指令失敗"
        return 1
    fi
    
    # 測試健康檢查指令
    if $COMPOSE_CMD -f "$COMPOSE_FILE" exec -T app php artisan tinker --execute="echo 'OK';" > /dev/null 2>&1; then
        log_success "應用程式健康檢查通過"
    else
        log_error "應用程式健康檢查失敗"
        return 1
    fi
}

# 檢查資料庫連線
check_database_connectivity() {
    log_info "檢查資料庫連線..."
    
    # 測試資料庫連線
    if $COMPOSE_CMD -f "$COMPOSE_FILE" exec -T app php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database connected';" > /dev/null 2>&1; then
        log_success "資料庫連線正常"
    else
        log_error "資料庫連線失敗"
        return 1
    fi
    
    # 檢查資料表是否存在
    local table_count=$($COMPOSE_CMD -f "$COMPOSE_FILE" exec -T app php artisan tinker --execute="echo DB::select('SHOW TABLES') ? count(DB::select('SHOW TABLES')) : 0;" 2>/dev/null | tail -1 || echo "0")
    
    if [ "$table_count" -gt 0 ]; then
        log_success "資料庫包含 $table_count 個資料表"
    else
        log_warning "資料庫中沒有資料表，可能需要執行遷移"
    fi
}

# 檢查 Redis 連線
check_redis_connectivity() {
    log_info "檢查 Redis 連線..."
    
    # 測試 Redis 連線
    if $COMPOSE_CMD -f "$COMPOSE_FILE" exec -T app php artisan tinker --execute="Redis::ping(); echo 'Redis connected';" > /dev/null 2>&1; then
        log_success "Redis 連線正常"
    else
        log_error "Redis 連線失敗"
        return 1
    fi
}

# 檢查網頁服務
check_web_service() {
    log_info "檢查網頁服務..."
    
    # 檢查 Nginx 容器
    if docker ps -q -f name="laravel_admin_nginx_${ENVIRONMENT}" | grep -q .; then
        log_success "Nginx 容器正在運行"
    else
        log_error "Nginx 容器未運行"
        return 1
    fi
    
    # 測試 HTTP 連線
    local http_status=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/ 2>/dev/null || echo "000")
    
    if [ "$http_status" = "200" ]; then
        log_success "HTTP 服務正常 (狀態碼: $http_status)"
    elif [ "$http_status" = "000" ]; then
        log_warning "無法連接到 HTTP 服務，請檢查端口是否正確開放"
    else
        log_warning "HTTP 服務回應異常 (狀態碼: $http_status)"
    fi
}

# 檢查日誌錯誤
check_logs_for_errors() {
    log_info "檢查最近的日誌錯誤..."
    
    # 檢查應用程式日誌
    local app_errors=$($COMPOSE_CMD -f "$COMPOSE_FILE" logs app --tail=50 2>/dev/null | grep -i "error\|exception\|fatal" | wc -l || echo "0")
    
    if [ "$app_errors" -gt 0 ]; then
        log_warning "應用程式日誌中發現 $app_errors 個錯誤訊息"
        log_info "最近的錯誤："
        $COMPOSE_CMD -f "$COMPOSE_FILE" logs app --tail=10 | grep -i "error\|exception\|fatal" | head -3
    else
        log_success "應用程式日誌中沒有發現錯誤"
    fi
    
    # 檢查 Nginx 日誌
    local nginx_errors=$($COMPOSE_CMD -f "$COMPOSE_FILE" logs nginx --tail=50 2>/dev/null | grep -i "error" | wc -l || echo "0")
    
    if [ "$nginx_errors" -gt 0 ]; then
        log_warning "Nginx 日誌中發現 $nginx_errors 個錯誤訊息"
    else
        log_success "Nginx 日誌中沒有發現錯誤"
    fi
}

# 檢查套件發現快取
check_package_discovery() {
    log_info "檢查套件發現快取..."
    
    # 檢查是否有 DuskServiceProvider 問題
    local dusk_error=$($COMPOSE_CMD -f "$COMPOSE_FILE" exec -T app php artisan tinker --execute="echo 'OK';" 2>&1 | grep -i "DuskServiceProvider" | wc -l || echo "0")
    
    if [ "$dusk_error" -gt 0 ]; then
        log_error "發現 DuskServiceProvider 錯誤，套件發現快取可能有問題"
        return 1
    else
        log_success "套件發現快取正常"
    fi
    
    # 檢查套件發現快取檔案
    local packages_cache=$($COMPOSE_CMD -f "$COMPOSE_FILE" exec -T app test -f bootstrap/cache/packages.php && echo "exists" || echo "missing")
    local services_cache=$($COMPOSE_CMD -f "$COMPOSE_FILE" exec -T app test -f bootstrap/cache/services.php && echo "exists" || echo "missing")
    
    if [ "$packages_cache" = "exists" ] && [ "$services_cache" = "exists" ]; then
        log_success "套件發現快取檔案存在"
    else
        log_warning "套件發現快取檔案缺失，可能需要重新生成"
    fi
}

# 檢查磁碟使用量
check_disk_usage() {
    log_info "檢查磁碟使用量..."
    
    # 檢查 Docker 系統使用量
    local docker_usage=$(docker system df --format "table {{.Type}}\t{{.TotalCount}}\t{{.Size}}" 2>/dev/null || echo "無法取得 Docker 使用量")
    
    if [ "$docker_usage" != "無法取得 Docker 使用量" ]; then
        log_info "Docker 系統使用量："
        echo "$docker_usage"
    fi
    
    # 檢查容器日誌大小
    local log_size=$(docker logs laravel_admin_app_${ENVIRONMENT} 2>/dev/null | wc -c || echo "0")
    local log_size_mb=$((log_size / 1024 / 1024))
    
    if [ "$log_size_mb" -gt 100 ]; then
        log_warning "應用程式日誌大小較大 (${log_size_mb}MB)，建議定期清理"
    else
        log_success "應用程式日誌大小正常 (${log_size_mb}MB)"
    fi
}

# 顯示系統資訊
show_system_info() {
    log_info "系統資訊："
    
    echo "環境: $ENVIRONMENT"
    echo "Compose 檔案: $COMPOSE_FILE"
    echo "Compose 指令: $COMPOSE_CMD"
    echo ""
    
    # 顯示容器狀態
    log_info "容器狀態："
    $COMPOSE_CMD -f "$COMPOSE_FILE" ps
    echo ""
    
    # 顯示服務端點
    log_info "服務端點："
    case $ENVIRONMENT in
        "dev"|"development")
            echo "  應用程式: http://localhost"
            ;;
        "staging"|"test")
            echo "  應用程式: http://localhost:8080"
            ;;
        "prod"|"production")
            echo "  應用程式: http://localhost"
            echo "  HTTPS: https://localhost"
            ;;
    esac
}

# 主要驗證函數
main() {
    echo "✅ Laravel Admin 系統部署後驗證"
    echo "=================================="
    echo ""
    
    # 檢查 compose 檔案是否存在
    if [ ! -f "$COMPOSE_FILE" ]; then
        log_error "找不到 compose 檔案: $COMPOSE_FILE"
        exit 1
    fi
    
    local checks=(
        "check_container_status"
        "check_container_health"
        "check_app_connectivity"
        "check_database_connectivity"
        "check_redis_connectivity"
        "check_web_service"
        "check_package_discovery"
        "check_logs_for_errors"
        "check_disk_usage"
    )
    
    local failed_checks=()
    local warning_count=0
    
    for check in "${checks[@]}"; do
        if ! $check; then
            failed_checks+=("$check")
        fi
    done
    
    echo ""
    echo "=================================="
    
    show_system_info
    
    echo ""
    
    if [ ${#failed_checks[@]} -eq 0 ]; then
        log_success "🎉 所有驗證都通過！系統部署成功且運行正常。"
        exit 0
    else
        log_error "❌ 發現 ${#failed_checks[@]} 個問題："
        for check in "${failed_checks[@]}"; do
            echo "  - $check"
        done
        echo ""
        log_error "請檢查上述問題並進行修復。"
        exit 1
    fi
}

# 顯示使用說明
show_usage() {
    echo "使用方法: $0 [環境]"
    echo ""
    echo "環境:"
    echo "  dev         開發環境"
    echo "  staging     測試環境"
    echo "  prod        生產環境 (預設)"
    echo ""
    echo "範例:"
    echo "  $0              # 驗證生產環境"
    echo "  $0 staging      # 驗證測試環境"
}

# 解析參數
if [ "$1" = "--help" ] || [ "$1" = "-h" ]; then
    show_usage
    exit 0
fi

# 執行主函數
main "$@"