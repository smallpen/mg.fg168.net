#!/bin/bash

# Docker 容器健康檢查腳本
# 此腳本用於 Docker 容器的健康檢查，確保應用程式正常運行

set -e

# 設定變數
HEALTH_URL="${HEALTH_URL:-http://localhost/api/health}"
TIMEOUT="${TIMEOUT:-10}"
MAX_RETRIES="${MAX_RETRIES:-3}"

# 顏色輸出
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 日誌函數
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# 檢查 HTTP 健康端點
check_http_health() {
    local url=$1
    local timeout=$2
    
    log_info "檢查 HTTP 健康端點: $url"
    
    # 使用 curl 檢查健康端點
    if command -v curl >/dev/null 2>&1; then
        response=$(curl -s -w "%{http_code}" -o /tmp/health_response --max-time "$timeout" "$url" || echo "000")
        
        if [ "$response" = "200" ]; then
            log_info "HTTP 健康檢查通過 (狀態碼: $response)"
            return 0
        else
            log_error "HTTP 健康檢查失敗 (狀態碼: $response)"
            if [ -f /tmp/health_response ]; then
                log_error "回應內容: $(cat /tmp/health_response)"
            fi
            return 1
        fi
    else
        log_warn "curl 不可用，跳過 HTTP 健康檢查"
        return 0
    fi
}

# 檢查 PHP-FPM 程序
check_php_fpm() {
    log_info "檢查 PHP-FPM 程序"
    
    if pgrep -f "php-fpm" >/dev/null; then
        log_info "PHP-FPM 程序正在運行"
        return 0
    else
        log_error "PHP-FPM 程序未運行"
        return 1
    fi
}

# 檢查 Nginx 程序（如果適用）
check_nginx() {
    if command -v nginx >/dev/null 2>&1; then
        log_info "檢查 Nginx 程序"
        
        if pgrep -f "nginx" >/dev/null; then
            log_info "Nginx 程序正在運行"
            return 0
        else
            log_error "Nginx 程序未運行"
            return 1
        fi
    else
        log_info "Nginx 不存在，跳過檢查"
        return 0
    fi
}

# 檢查重要檔案和目錄
check_filesystem() {
    log_info "檢查檔案系統"
    
    local errors=0
    
    # 檢查重要目錄是否存在且可寫
    local dirs=(
        "/var/www/html/storage/logs"
        "/var/www/html/storage/framework/cache"
        "/var/www/html/storage/framework/sessions"
        "/var/www/html/storage/framework/views"
    )
    
    for dir in "${dirs[@]}"; do
        if [ -d "$dir" ] && [ -w "$dir" ]; then
            log_info "目錄 $dir 存在且可寫"
        else
            log_error "目錄 $dir 不存在或不可寫"
            errors=$((errors + 1))
        fi
    done
    
    # 檢查重要檔案是否存在
    local files=(
        "/var/www/html/.env"
        "/var/www/html/artisan"
    )
    
    for file in "${files[@]}"; do
        if [ -f "$file" ]; then
            log_info "檔案 $file 存在"
        else
            log_error "檔案 $file 不存在"
            errors=$((errors + 1))
        fi
    done
    
    if [ $errors -eq 0 ]; then
        return 0
    else
        return 1
    fi
}

# 檢查磁碟空間
check_disk_space() {
    log_info "檢查磁碟空間"
    
    local usage=$(df /var/www/html | awk 'NR==2 {print $5}' | sed 's/%//')
    local threshold=90
    
    if [ "$usage" -lt "$threshold" ]; then
        log_info "磁碟使用率: ${usage}% (正常)"
        return 0
    else
        log_error "磁碟使用率過高: ${usage}% (閾值: ${threshold}%)"
        return 1
    fi
}

# 主要健康檢查函數
main_health_check() {
    local checks_passed=0
    local total_checks=0
    
    log_info "開始 Docker 容器健康檢查"
    log_info "==============================="
    
    # 執行各項檢查
    local checks=(
        "check_php_fpm"
        "check_nginx"
        "check_filesystem"
        "check_disk_space"
        "check_http_health $HEALTH_URL $TIMEOUT"
    )
    
    for check in "${checks[@]}"; do
        total_checks=$((total_checks + 1))
        
        if eval "$check"; then
            checks_passed=$((checks_passed + 1))
        fi
        
        echo "" # 空行分隔
    done
    
    # 輸出結果
    log_info "==============================="
    log_info "健康檢查完成: $checks_passed/$total_checks 項檢查通過"
    
    if [ $checks_passed -eq $total_checks ]; then
        log_info "容器健康狀態: 健康"
        return 0
    else
        log_error "容器健康狀態: 不健康"
        return 1
    fi
}

# 重試機制
retry_health_check() {
    local retries=0
    
    while [ $retries -lt $MAX_RETRIES ]; do
        if main_health_check; then
            return 0
        fi
        
        retries=$((retries + 1))
        if [ $retries -lt $MAX_RETRIES ]; then
            log_warn "健康檢查失敗，等待 5 秒後重試 ($retries/$MAX_RETRIES)"
            sleep 5
        fi
    done
    
    log_error "健康檢查在 $MAX_RETRIES 次重試後仍然失敗"
    return 1
}

# 清理函數
cleanup() {
    rm -f /tmp/health_response
}

# 設定清理陷阱
trap cleanup EXIT

# 執行健康檢查
if retry_health_check; then
    exit 0
else
    exit 1
fi