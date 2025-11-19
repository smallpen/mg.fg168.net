#!/bin/bash

# 通路管理系統部署腳本
# 版本: 1.0
# 作者: 系統管理員
# 日期: 2024-12-09

set -e  # 遇到錯誤立即退出

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

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
BACKUP_DIR="/var/backups/channel-management"
DEPLOY_LOG="/var/log/channel-management-deploy.log"

# 確保日誌目錄存在
mkdir -p "$(dirname "$DEPLOY_LOG")"
mkdir -p "$BACKUP_DIR"

# 重定向輸出到日誌檔案
exec > >(tee -a "$DEPLOY_LOG")
exec 2>&1

log_info "開始通路管理系統部署程序"
log_info "專案根目錄: $PROJECT_ROOT"
log_info "備份目錄: $BACKUP_DIR"

# 檢查必要條件
check_prerequisites() {
    log_info "檢查部署必要條件..."
    
    # 檢查 Docker
    if ! command -v docker &> /dev/null; then
        log_error "Docker 未安裝或不在 PATH 中"
        exit 1
    fi
    
    # 檢查 Docker Compose
    if ! command -v docker-compose &> /dev/null; then
        log_error "Docker Compose 未安裝或不在 PATH 中"
        exit 1
    fi
    
    # 檢查專案目錄
    if [ ! -f "$PROJECT_ROOT/docker-compose.yml" ]; then
        log_error "找不到 docker-compose.yml 檔案"
        exit 1
    fi
    
    # 檢查環境檔案
    if [ ! -f "$PROJECT_ROOT/.env" ]; then
        log_error "找不到 .env 檔案，請先複製 .env.example"
        exit 1
    fi
    
    log_success "必要條件檢查完成"
}

# 備份現有系統
backup_system() {
    log_info "開始備份現有系統..."
    
    DATE=$(date +%Y%m%d_%H%M%S)
    BACKUP_FILE="$BACKUP_DIR/pre_deploy_backup_$DATE"
    
    # 檢查是否有運行中的容器
    if docker-compose -f "$PROJECT_ROOT/docker-compose.yml" ps | grep -q "Up"; then
        log_info "備份資料庫..."
        
        # 讀取資料庫配置
        source "$PROJECT_ROOT/.env"
        
        # 備份資料庫
        docker-compose -f "$PROJECT_ROOT/docker-compose.yml" exec -T db mysqldump \
            -u"$DB_USERNAME" -p"$DB_PASSWORD" \
            --single-transaction --routines --triggers \
            "$DB_DATABASE" > "${BACKUP_FILE}_database.sql"
        
        if [ $? -eq 0 ]; then
            gzip "${BACKUP_FILE}_database.sql"
            log_success "資料庫備份完成: ${BACKUP_FILE}_database.sql.gz"
        else
            log_error "資料庫備份失敗"
            exit 1
        fi
    else
        log_warning "沒有運行中的資料庫容器，跳過資料庫備份"
    fi
    
    # 備份應用程式檔案
    log_info "備份應用程式檔案..."
    tar -czf "${BACKUP_FILE}_files.tar.gz" \
        -C "$PROJECT_ROOT" \
        --exclude='storage/logs/*' \
        --exclude='storage/framework/cache/*' \
        --exclude='storage/framework/sessions/*' \
        --exclude='storage/framework/views/*' \
        --exclude='node_modules' \
        --exclude='.git' \
        .
    
    if [ $? -eq 0 ]; then
        log_success "檔案備份完成: ${BACKUP_FILE}_files.tar.gz"
    else
        log_error "檔案備份失敗"
        exit 1
    fi
    
    # 備份環境配置
    cp "$PROJECT_ROOT/.env" "${BACKUP_FILE}_env.backup"
    log_success "環境配置備份完成: ${BACKUP_FILE}_env.backup"
}

# 更新程式碼
update_code() {
    log_info "更新程式碼..."
    
    cd "$PROJECT_ROOT"
    
    # 檢查 Git 狀態
    if [ -d ".git" ]; then
        log_info "從 Git 更新程式碼..."
        
        # 儲存本地變更
        git stash push -m "Pre-deploy stash $(date)"
        
        # 拉取最新程式碼
        git pull origin main
        
        if [ $? -eq 0 ]; then
            log_success "程式碼更新完成"
        else
            log_error "程式碼更新失敗"
            exit 1
        fi
    else
        log_warning "不是 Git 倉庫，跳過程式碼更新"
    fi
}

# 建置和啟動服務
build_and_start() {
    log_info "建置和啟動服務..."
    
    cd "$PROJECT_ROOT"
    
    # 停止現有服務
    log_info "停止現有服務..."
    docker-compose down
    
    # 建置映像
    log_info "建置 Docker 映像..."
    docker-compose build --no-cache
    
    if [ $? -ne 0 ]; then
        log_error "Docker 映像建置失敗"
        exit 1
    fi
    
    # 啟動服務
    log_info "啟動服務..."
    docker-compose up -d
    
    if [ $? -ne 0 ]; then
        log_error "服務啟動失敗"
        exit 1
    fi
    
    # 等待服務啟動
    log_info "等待服務啟動..."
    sleep 30
    
    # 檢查服務狀態
    if docker-compose ps | grep -q "Exit"; then
        log_error "部分服務啟動失敗"
        docker-compose ps
        exit 1
    fi
    
    log_success "服務啟動完成"
}

# 安裝依賴
install_dependencies() {
    log_info "安裝 PHP 依賴..."
    
    docker-compose exec -T app composer install \
        --optimize-autoloader \
        --no-dev \
        --no-interaction
    
    if [ $? -ne 0 ]; then
        log_error "Composer 依賴安裝失敗"
        exit 1
    fi
    
    log_success "PHP 依賴安裝完成"
}

# 執行資料庫遷移
run_migrations() {
    log_info "執行資料庫遷移..."
    
    # 檢查遷移狀態
    docker-compose exec -T app php artisan migrate:status
    
    # 執行遷移
    docker-compose exec -T app php artisan migrate --force
    
    if [ $? -ne 0 ]; then
        log_error "資料庫遷移失敗"
        exit 1
    fi
    
    log_success "資料庫遷移完成"
}

# 執行 Seeder
run_seeders() {
    log_info "執行資料庫 Seeder..."
    
    # 檢查是否需要執行 Seeder
    PERMISSION_COUNT=$(docker-compose exec -T app php artisan tinker --execute="echo Permission::count();")
    
    if [ "$PERMISSION_COUNT" -lt 35 ]; then
        log_info "權限數量不足，執行 PermissionSeeder..."
        docker-compose exec -T app php artisan db:seed --class=PermissionSeeder --force
    fi
    
    ROLE_COUNT=$(docker-compose exec -T app php artisan tinker --execute="echo Role::count();")
    
    if [ "$ROLE_COUNT" -lt 3 ]; then
        log_info "角色數量不足，執行 RoleSeeder..."
        docker-compose exec -T app php artisan db:seed --class=RoleSeeder --force
    fi
    
    # 檢查是否有管理員使用者
    ADMIN_COUNT=$(docker-compose exec -T app php artisan tinker --execute="echo User::where('username', 'admin')->count();")
    
    if [ "$ADMIN_COUNT" -eq 0 ]; then
        log_info "沒有管理員使用者，執行 UserSeeder..."
        docker-compose exec -T app php artisan db:seed --class=UserSeeder --force
    fi
    
    log_success "Seeder 執行完成"
}

# 優化應用程式
optimize_application() {
    log_info "優化應用程式..."
    
    # 生成應用程式金鑰（如果需要）
    docker-compose exec -T app php artisan key:generate --force
    
    # 建立符號連結
    docker-compose exec -T app php artisan storage:link
    
    # 清除快取
    docker-compose exec -T app php artisan cache:clear
    docker-compose exec -T app php artisan config:clear
    docker-compose exec -T app php artisan route:clear
    docker-compose exec -T app php artisan view:clear
    
    # 重建快取
    docker-compose exec -T app php artisan config:cache
    docker-compose exec -T app php artisan route:cache
    docker-compose exec -T app php artisan view:cache
    
    # 優化 Composer 自動載入
    docker-compose exec -T app composer dump-autoload --optimize
    
    log_success "應用程式優化完成"
}

# 執行健康檢查
health_check() {
    log_info "執行健康檢查..."
    
    # 檢查應用程式狀態
    docker-compose exec -T app php artisan about
    
    # 檢查資料庫連線
    docker-compose exec -T app php artisan migrate:status > /dev/null
    
    if [ $? -ne 0 ]; then
        log_error "資料庫連線檢查失敗"
        exit 1
    fi
    
    # 檢查 Redis 連線
    docker-compose exec -T redis redis-cli ping > /dev/null
    
    if [ $? -ne 0 ]; then
        log_error "Redis 連線檢查失敗"
        exit 1
    fi
    
    # 檢查網頁服務
    sleep 5
    HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/health || echo "000")
    
    if [ "$HTTP_STATUS" != "200" ]; then
        log_warning "HTTP 健康檢查返回狀態碼: $HTTP_STATUS"
    else
        log_success "HTTP 健康檢查通過"
    fi
    
    log_success "健康檢查完成"
}

# 清理舊備份
cleanup_old_backups() {
    log_info "清理舊備份檔案..."
    
    # 保留最近 7 天的備份
    find "$BACKUP_DIR" -name "pre_deploy_backup_*" -mtime +7 -delete
    
    log_success "舊備份清理完成"
}

# 部署後通知
post_deploy_notification() {
    log_info "發送部署完成通知..."
    
    DEPLOY_TIME=$(date '+%Y-%m-%d %H:%M:%S')
    
    # 這裡可以添加發送郵件或 Slack 通知的邏輯
    # 例如：
    # curl -X POST -H 'Content-type: application/json' \
    #     --data '{"text":"通路管理系統部署完成 - '$DEPLOY_TIME'"}' \
    #     YOUR_SLACK_WEBHOOK_URL
    
    log_success "部署完成通知已發送"
}

# 主要部署流程
main() {
    log_info "========================================="
    log_info "通路管理系統部署開始"
    log_info "========================================="
    
    check_prerequisites
    backup_system
    update_code
    build_and_start
    install_dependencies
    run_migrations
    run_seeders
    optimize_application
    health_check
    cleanup_old_backups
    post_deploy_notification
    
    log_success "========================================="
    log_success "通路管理系統部署完成！"
    log_success "部署時間: $(date '+%Y-%m-%d %H:%M:%S')"
    log_success "========================================="
    
    # 顯示系統狀態
    echo ""
    log_info "系統狀態："
    docker-compose ps
    
    echo ""
    log_info "可以透過以下方式存取系統："
    log_info "- 網頁介面: http://localhost"
    log_info "- 管理後台: http://localhost/admin"
    log_info "- 預設管理員: admin / admin123"
    
    echo ""
    log_info "部署日誌已儲存至: $DEPLOY_LOG"
}

# 錯誤處理
trap 'log_error "部署過程中發生錯誤，請檢查日誌: $DEPLOY_LOG"' ERR

# 執行主要流程
main "$@"