#!/bin/bash

# Laravel Admin 系統部署前檢查腳本
# 確保部署環境正確設定，避免常見問題

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

# 檢查必要檔案
check_required_files() {
    log_info "檢查必要檔案..."
    
    local required_files=(
        ".env"
        "docker-compose.prod.yml"
        "secrets/mysql_root_password.txt"
        "secrets/mysql_password.txt"
        "secrets/redis_password.txt"
        "secrets/app_key.txt"
    )
    
    local missing_files=()
    
    for file in "${required_files[@]}"; do
        if [ ! -f "$file" ]; then
            missing_files+=("$file")
        fi
    done
    
    if [ ${#missing_files[@]} -eq 0 ]; then
        log_success "所有必要檔案都存在"
    else
        log_error "缺少以下必要檔案："
        for file in "${missing_files[@]}"; do
            echo "  - $file"
        done
        return 1
    fi
}

# 檢查環境變數設定
check_env_config() {
    log_info "檢查環境變數設定..."
    
    if [ ! -f ".env" ]; then
        log_error ".env 檔案不存在"
        return 1
    fi
    
    # 檢查重要的環境變數
    local required_vars=(
        "APP_NAME"
        "APP_ENV"
        "APP_KEY"
        "DB_DATABASE"
        "DB_USERNAME"
        "DB_PASSWORD"
        "REDIS_PASSWORD"
    )
    
    local missing_vars=()
    
    for var in "${required_vars[@]}"; do
        if ! grep -q "^${var}=" .env; then
            missing_vars+=("$var")
        fi
    done
    
    if [ ${#missing_vars[@]} -eq 0 ]; then
        log_success "環境變數設定完整"
    else
        log_error "缺少以下環境變數："
        for var in "${missing_vars[@]}"; do
            echo "  - $var"
        done
        return 1
    fi
    
    # 檢查 APP_ENV 是否設定為 production
    local app_env=$(grep "^APP_ENV=" .env | cut -d'=' -f2)
    if [ "$app_env" != "production" ]; then
        log_warning "APP_ENV 設定為 '$app_env'，建議在生產環境中設定為 'production'"
    fi
    
    # 檢查 APP_DEBUG 是否設定為 false
    local app_debug=$(grep "^APP_DEBUG=" .env | cut -d'=' -f2)
    if [ "$app_debug" != "false" ]; then
        log_warning "APP_DEBUG 設定為 '$app_debug'，建議在生產環境中設定為 'false'"
    fi
}

# 檢查秘密檔案內容
check_secrets() {
    log_info "檢查秘密檔案內容..."
    
    local secret_files=(
        "secrets/mysql_root_password.txt"
        "secrets/mysql_password.txt"
        "secrets/redis_password.txt"
        "secrets/app_key.txt"
    )
    
    for file in "${secret_files[@]}"; do
        if [ -f "$file" ]; then
            local content=$(cat "$file" | tr -d '\n\r')
            if [ -z "$content" ]; then
                log_error "$file 檔案為空"
                return 1
            elif [ ${#content} -lt 8 ]; then
                log_warning "$file 內容過短（少於 8 個字元），建議使用更強的密碼"
            fi
        fi
    done
    
    log_success "秘密檔案內容檢查完成"
}

# 檢查 Docker 環境
check_docker() {
    log_info "檢查 Docker 環境..."
    
    if ! command -v docker &> /dev/null; then
        log_error "Docker 未安裝或不在 PATH 中"
        return 1
    fi
    
    if ! docker info &> /dev/null; then
        log_error "Docker daemon 未運行或無權限存取"
        return 1
    fi
    
    # 檢查 Docker Compose
    if docker compose version &> /dev/null; then
        log_success "Docker Compose 可用 (新版語法)"
    elif docker-compose --version &> /dev/null; then
        log_warning "檢測到舊版 docker-compose，建議升級到新版 Docker"
    else
        log_error "Docker Compose 不可用"
        return 1
    fi
    
    log_success "Docker 環境檢查通過"
}

# 檢查磁碟空間
check_disk_space() {
    log_info "檢查磁碟空間..."
    
    local available_space=$(df . | tail -1 | awk '{print $4}')
    local available_gb=$((available_space / 1024 / 1024))
    
    if [ $available_gb -lt 5 ]; then
        log_error "可用磁碟空間不足 (${available_gb}GB)，建議至少有 5GB 可用空間"
        return 1
    elif [ $available_gb -lt 10 ]; then
        log_warning "可用磁碟空間較少 (${available_gb}GB)，建議至少有 10GB 可用空間"
    else
        log_success "磁碟空間充足 (${available_gb}GB)"
    fi
}

# 檢查網路連線
check_network() {
    log_info "檢查網路連線..."
    
    # 檢查是否能連接到 Docker Hub
    if curl -s --connect-timeout 5 https://registry-1.docker.io/v2/ > /dev/null; then
        log_success "網路連線正常"
    else
        log_warning "無法連接到 Docker Hub，可能會影響映像下載"
    fi
}

# 檢查現有容器
check_existing_containers() {
    log_info "檢查現有容器..."
    
    local containers=(
        "laravel_admin_app_prod"
        "laravel_admin_nginx_prod"
        "laravel_admin_mysql_prod"
        "laravel_admin_redis_prod"
    )
    
    local running_containers=()
    
    for container in "${containers[@]}"; do
        if docker ps -q -f name="$container" | grep -q .; then
            running_containers+=("$container")
        fi
    done
    
    if [ ${#running_containers[@]} -eq 0 ]; then
        log_success "沒有衝突的容器正在運行"
    else
        log_warning "發現以下容器正在運行，部署時將會重新啟動："
        for container in "${running_containers[@]}"; do
            echo "  - $container"
        done
    fi
}

# 檢查 Composer 依賴
check_composer_dependencies() {
    log_info "檢查 Composer 依賴..."
    
    if [ ! -f "composer.json" ]; then
        log_error "找不到 composer.json 檔案"
        return 1
    fi
    
    # 檢查是否有開發依賴在 require 區段中（這可能導致生產環境問題）
    local dev_packages_in_require=$(grep -A 50 '"require"' composer.json | grep -E '"laravel/dusk"|"phpunit/phpunit"|"mockery/mockery"' || true)
    
    if [ -n "$dev_packages_in_require" ]; then
        log_warning "發現開發套件在 require 區段中，這可能導致生產環境問題"
        echo "$dev_packages_in_require"
    else
        log_success "Composer 依賴設定正確"
    fi
}

# 檢查 Laravel 快取檔案
check_laravel_cache() {
    log_info "檢查 Laravel 快取檔案..."
    
    local cache_files=(
        "bootstrap/cache/packages.php"
        "bootstrap/cache/services.php"
        "bootstrap/cache/config.php"
        "bootstrap/cache/routes-v7.php"
    )
    
    local existing_cache_files=()
    
    for file in "${cache_files[@]}"; do
        if [ -f "$file" ]; then
            existing_cache_files+=("$file")
        fi
    done
    
    if [ ${#existing_cache_files[@]} -gt 0 ]; then
        log_warning "發現以下快取檔案，部署時將會清除："
        for file in "${existing_cache_files[@]}"; do
            echo "  - $file"
        done
        
        # 特別檢查套件發現快取是否包含開發套件
        if [ -f "bootstrap/cache/packages.php" ]; then
            if grep -q "DuskServiceProvider" "bootstrap/cache/packages.php" 2>/dev/null; then
                log_warning "套件發現快取包含開發套件 (DuskServiceProvider)，將在部署時清除"
            fi
        fi
    else
        log_success "沒有舊的快取檔案"
    fi
}

# 主要檢查函數
main() {
    echo "🔍 Laravel Admin 系統部署前檢查"
    echo "=================================="
    echo ""
    
    local checks=(
        "check_docker"
        "check_required_files"
        "check_env_config"
        "check_secrets"
        "check_disk_space"
        "check_network"
        "check_existing_containers"
        "check_composer_dependencies"
        "check_laravel_cache"
    )
    
    local failed_checks=()
    local warning_checks=()
    
    for check in "${checks[@]}"; do
        if ! $check; then
            failed_checks+=("$check")
        fi
    done
    
    echo ""
    echo "=================================="
    
    if [ ${#failed_checks[@]} -eq 0 ]; then
        log_success "🎉 所有檢查都通過！系統已準備好進行部署。"
        echo ""
        log_info "建議的部署指令："
        echo "  ./quick-deploy.sh prod --build"
        exit 0
    else
        log_error "❌ 發現 ${#failed_checks[@]} 個問題需要解決："
        for check in "${failed_checks[@]}"; do
            echo "  - $check"
        done
        echo ""
        log_error "請解決上述問題後再進行部署。"
        exit 1
    fi
}

# 執行主函數
main "$@"