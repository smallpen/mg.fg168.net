#!/bin/bash
# Laravel Admin 系統生產環境部署腳本

set -e

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 函數：顯示訊息
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# 檢查必要檔案
check_requirements() {
    log_info "檢查部署需求..."
    
    if [ ! -f "docker-compose.prod.yml" ]; then
        log_error "找不到 docker-compose.prod.yml 檔案"
        exit 1
    fi
    
    if [ ! -f "secrets/mysql_root_password.txt" ]; then
        log_error "找不到 MySQL root 密碼檔案"
        exit 1
    fi
    
    if [ ! -f "secrets/mysql_password.txt" ]; then
        log_error "找不到 MySQL 使用者密碼檔案"
        exit 1
    fi
    
    if [ ! -f "secrets/redis_password.txt" ]; then
        log_error "找不到 Redis 密碼檔案"
        exit 1
    fi
    
    if [ ! -f "secrets/app_key.txt" ]; then
        log_error "找不到 Laravel APP_KEY 檔案"
        exit 1
    fi
    
    log_info "需求檢查完成"
}

# 建立必要目錄
create_directories() {
    log_info "建立必要目錄..."
    
    mkdir -p storage/logs
    mkdir -p storage/framework/cache
    mkdir -p storage/framework/sessions
    mkdir -p storage/framework/views
    mkdir -p bootstrap/cache
    
    log_info "目錄建立完成"
}

# 設定檔案權限
set_permissions() {
    log_info "設定檔案權限..."
    
    chmod -R 755 storage
    chmod -R 755 bootstrap/cache
    
    log_info "權限設定完成"
}

# 建置 Docker 映像
build_images() {
    log_info "建置 Docker 映像..."
    
    docker-compose -f docker-compose.prod.yml build --no-cache
    
    log_info "映像建置完成"
}

# 啟動服務
start_services() {
    log_info "啟動服務..."
    
    docker-compose -f docker-compose.prod.yml up -d
    
    log_info "等待服務啟動..."
    sleep 30
    
    log_info "服務啟動完成"
}

# 執行資料庫遷移
run_migrations() {
    log_info "執行資料庫遷移..."
    
    docker-compose -f docker-compose.prod.yml exec -T app php artisan migrate --force
    
    log_info "資料庫遷移完成"
}

# 執行資料庫種子
run_seeders() {
    log_info "執行資料庫種子..."
    
    docker-compose -f docker-compose.prod.yml exec -T app php artisan db:seed --force
    
    log_info "資料庫種子完成"
}

# 清除和快取配置
optimize_application() {
    log_info "最佳化應用程式..."
    
    # 清除快取
    docker-compose -f docker-compose.prod.yml exec -T app php artisan cache:clear
    docker-compose -f docker-compose.prod.yml exec -T app php artisan config:clear
    docker-compose -f docker-compose.prod.yml exec -T app php artisan route:clear
    docker-compose -f docker-compose.prod.yml exec -T app php artisan view:clear
    
    # 建立快取
    docker-compose -f docker-compose.prod.yml exec -T app php artisan config:cache
    docker-compose -f docker-compose.prod.yml exec -T app php artisan route:cache
    docker-compose -f docker-compose.prod.yml exec -T app php artisan view:cache
    
    # 最佳化 Composer 自動載入
    docker-compose -f docker-compose.prod.yml exec -T app composer dump-autoload --optimize
    
    log_info "應用程式最佳化完成"
}

# 健康檢查
health_check() {
    log_info "執行健康檢查..."
    
    # 檢查 Nginx
    if curl -f http://localhost/health > /dev/null 2>&1; then
        log_info "Nginx 健康檢查通過"
    else
        log_warn "Nginx 健康檢查失敗"
    fi
    
    # 檢查應用程式
    if docker-compose -f docker-compose.prod.yml exec -T app php artisan tinker --execute="echo 'OK';" > /dev/null 2>&1; then
        log_info "應用程式健康檢查通過"
    else
        log_warn "應用程式健康檢查失敗"
    fi
    
    log_info "健康檢查完成"
}

# 顯示服務狀態
show_status() {
    log_info "服務狀態："
    docker-compose -f docker-compose.prod.yml ps
}

# 主要部署流程
main() {
    log_info "開始 Laravel Admin 系統生產環境部署"
    
    check_requirements
    create_directories
    set_permissions
    build_images
    start_services
    run_migrations
    run_seeders
    optimize_application
    health_check
    show_status
    
    log_info "部署完成！"
    log_info "應用程式現在可以通過 http://localhost 或 https://localhost 存取"
}

# 執行主要流程
main "$@"