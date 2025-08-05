#!/bin/bash

# Laravel Admin 系統部署腳本
# 用於自動化部署到測試和生產環境

set -e  # 遇到錯誤立即退出

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

# 顯示使用說明
show_usage() {
    echo "使用方法: $0 [環境] [選項]"
    echo ""
    echo "環境:"
    echo "  staging     部署到測試環境"
    echo "  production  部署到生產環境"
    echo ""
    echo "選項:"
    echo "  --skip-backup    跳過備份步驟"
    echo "  --skip-tests     跳過測試執行"
    echo "  --force          強制部署（跳過確認）"
    echo "  --rollback       回滾到上一個版本"
    echo "  --help           顯示此說明"
    echo ""
    echo "範例:"
    echo "  $0 staging"
    echo "  $0 production --skip-backup"
    echo "  $0 staging --rollback"
}

# 檢查必要工具
check_requirements() {
    log_info "檢查必要工具..."
    
    local tools=("docker" "docker-compose" "git" "curl")
    for tool in "${tools[@]}"; do
        if ! command -v $tool &> /dev/null; then
            log_error "$tool 未安裝或不在 PATH 中"
            exit 1
        fi
    done
    
    log_success "所有必要工具已安裝"
}

# 載入環境變數
load_env() {
    local env_file=".env.$1"
    
    if [ -f "$env_file" ]; then
        log_info "載入環境變數檔案: $env_file"
        export $(cat $env_file | grep -v '^#' | xargs)
    else
        log_warning "環境變數檔案 $env_file 不存在"
    fi
}

# 執行測試
run_tests() {
    if [ "$SKIP_TESTS" = true ]; then
        log_warning "跳過測試執行"
        return
    fi
    
    log_info "執行測試套件..."
    
    # 建立測試資料庫
    docker-compose -f docker-compose.test.yml up -d mysql redis
    sleep 10
    
    # 執行測試
    docker-compose -f docker-compose.test.yml run --rm app php artisan test
    
    if [ $? -eq 0 ]; then
        log_success "所有測試通過"
    else
        log_error "測試失敗，停止部署"
        docker-compose -f docker-compose.test.yml down
        exit 1
    fi
    
    # 清理測試環境
    docker-compose -f docker-compose.test.yml down
}

# 建立備份
create_backup() {
    if [ "$SKIP_BACKUP" = true ]; then
        log_warning "跳過備份步驟"
        return
    fi
    
    local env=$1
    local backup_dir="backups/backup_$(date +%Y%m%d_%H%M%S)"
    
    log_info "建立備份到 $backup_dir..."
    
    mkdir -p $backup_dir
    
    # 備份資料庫
    if [ "$env" = "production" ]; then
        docker-compose -f docker-compose.prod.yml exec -T mysql mysqldump \
            -u root -p$MYSQL_ROOT_PASSWORD laravel_admin > $backup_dir/database.sql
    else
        docker-compose -f docker-compose.staging.yml exec -T mysql mysqldump \
            -u root -p$STAGING_MYSQL_ROOT_PASSWORD laravel_admin_staging > $backup_dir/database.sql
    fi
    
    # 備份應用程式檔案
    tar -czf $backup_dir/storage.tar.gz storage/
    cp .env $backup_dir/ 2>/dev/null || true
    
    # 記錄當前 Git 提交
    git rev-parse HEAD > $backup_dir/git_commit.txt
    
    log_success "備份完成: $backup_dir"
    echo $backup_dir > .last_backup
}

# 部署到測試環境
deploy_staging() {
    log_info "開始部署到測試環境..."
    
    # 更新程式碼
    log_info "更新程式碼..."
    git fetch origin
    git reset --hard origin/develop
    
    # 建置和啟動服務
    log_info "建置 Docker 映像..."
    docker-compose -f docker-compose.staging.yml build --no-cache
    
    log_info "啟動服務..."
    docker-compose -f docker-compose.staging.yml down
    docker-compose -f docker-compose.staging.yml up -d
    
    # 等待服務啟動
    log_info "等待服務啟動..."
    sleep 30
    
    # 執行遷移和快取清理
    log_info "執行資料庫遷移..."
    docker-compose -f docker-compose.staging.yml exec -T app php artisan migrate --force
    
    log_info "清理和快取設定..."
    docker-compose -f docker-compose.staging.yml exec -T app php artisan config:cache
    docker-compose -f docker-compose.staging.yml exec -T app php artisan route:cache
    docker-compose -f docker-compose.staging.yml exec -T app php artisan view:cache
    docker-compose -f docker-compose.staging.yml exec -T app php artisan queue:restart
    
    log_success "測試環境部署完成"
}

# 部署到生產環境
deploy_production() {
    log_info "開始部署到生產環境..."
    
    # 確認部署
    if [ "$FORCE_DEPLOY" != true ]; then
        echo -n "確定要部署到生產環境嗎？ (y/N): "
        read -r response
        if [[ ! "$response" =~ ^[Yy]$ ]]; then
            log_info "部署已取消"
            exit 0
        fi
    fi
    
    # 更新程式碼
    log_info "更新程式碼..."
    git fetch origin
    git reset --hard origin/main
    
    # 零停機部署
    log_info "執行零停機部署..."
    
    # 建置新映像
    docker-compose -f docker-compose.prod.yml build --no-cache app
    
    # 啟動新容器（擴展到 2 個實例）
    docker-compose -f docker-compose.prod.yml up -d --scale app=2 app
    
    # 等待新容器準備就緒
    log_info "等待新容器準備就緒..."
    sleep 45
    
    # 執行遷移
    log_info "執行資料庫遷移..."
    docker-compose -f docker-compose.prod.yml exec -T app php artisan migrate --force
    
    # 清理快取
    log_info "清理和快取設定..."
    docker-compose -f docker-compose.prod.yml exec -T app php artisan config:cache
    docker-compose -f docker-compose.prod.yml exec -T app php artisan route:cache
    docker-compose -f docker-compose.prod.yml exec -T app php artisan view:cache
    docker-compose -f docker-compose.prod.yml exec -T app php artisan queue:restart
    
    # 重新載入 Nginx
    docker-compose -f docker-compose.prod.yml exec nginx nginx -s reload
    
    # 縮減到單一實例
    docker-compose -f docker-compose.prod.yml up -d --scale app=1 app
    
    # 清理舊映像
    docker image prune -f
    
    log_success "生產環境部署完成"
}

# 健康檢查
health_check() {
    local env=$1
    local url
    
    if [ "$env" = "production" ]; then
        url="${PRODUCTION_URL:-http://localhost}/health"
    else
        url="${STAGING_URL:-http://localhost:8080}/health"
    fi
    
    log_info "執行健康檢查: $url"
    
    local max_attempts=10
    local attempt=1
    
    while [ $attempt -le $max_attempts ]; do
        log_info "健康檢查嘗試 $attempt/$max_attempts..."
        
        if curl -f -s "$url" > /dev/null; then
            log_success "健康檢查通過"
            return 0
        fi
        
        sleep 10
        ((attempt++))
    done
    
    log_error "健康檢查失敗"
    return 1
}

# 回滾功能
rollback() {
    local env=$1
    
    if [ ! -f .last_backup ]; then
        log_error "找不到備份記錄，無法回滾"
        exit 1
    fi
    
    local backup_dir=$(cat .last_backup)
    
    if [ ! -d "$backup_dir" ]; then
        log_error "備份目錄不存在: $backup_dir"
        exit 1
    fi
    
    log_info "回滾到備份: $backup_dir"
    
    # 確認回滾
    echo -n "確定要回滾嗎？這將覆蓋當前資料！ (y/N): "
    read -r response
    if [[ ! "$response" =~ ^[Yy]$ ]]; then
        log_info "回滾已取消"
        exit 0
    fi
    
    # 停止服務
    if [ "$env" = "production" ]; then
        docker-compose -f docker-compose.prod.yml down
    else
        docker-compose -f docker-compose.staging.yml down
    fi
    
    # 恢復程式碼
    if [ -f "$backup_dir/git_commit.txt" ]; then
        local commit=$(cat $backup_dir/git_commit.txt)
        log_info "恢復到 Git 提交: $commit"
        git reset --hard $commit
    fi
    
    # 恢復檔案
    if [ -f "$backup_dir/storage.tar.gz" ]; then
        log_info "恢復儲存檔案..."
        tar -xzf $backup_dir/storage.tar.gz
    fi
    
    if [ -f "$backup_dir/.env" ]; then
        log_info "恢復環境設定..."
        cp $backup_dir/.env .env
    fi
    
    # 重新啟動服務
    if [ "$env" = "production" ]; then
        docker-compose -f docker-compose.prod.yml up -d
    else
        docker-compose -f docker-compose.staging.yml up -d
    fi
    
    # 等待服務啟動
    sleep 30
    
    # 恢復資料庫
    if [ -f "$backup_dir/database.sql" ]; then
        log_info "恢復資料庫..."
        if [ "$env" = "production" ]; then
            docker-compose -f docker-compose.prod.yml exec -T mysql mysql \
                -u root -p$MYSQL_ROOT_PASSWORD laravel_admin < $backup_dir/database.sql
        else
            docker-compose -f docker-compose.staging.yml exec -T mysql mysql \
                -u root -p$STAGING_MYSQL_ROOT_PASSWORD laravel_admin_staging < $backup_dir/database.sql
        fi
    fi
    
    log_success "回滾完成"
}

# 主要執行邏輯
main() {
    local environment=""
    local SKIP_BACKUP=false
    local SKIP_TESTS=false
    local FORCE_DEPLOY=false
    local ROLLBACK=false
    
    # 解析參數
    while [[ $# -gt 0 ]]; do
        case $1 in
            staging|production)
                environment="$1"
                shift
                ;;
            --skip-backup)
                SKIP_BACKUP=true
                shift
                ;;
            --skip-tests)
                SKIP_TESTS=true
                shift
                ;;
            --force)
                FORCE_DEPLOY=true
                shift
                ;;
            --rollback)
                ROLLBACK=true
                shift
                ;;
            --help)
                show_usage
                exit 0
                ;;
            *)
                log_error "未知參數: $1"
                show_usage
                exit 1
                ;;
        esac
    done
    
    # 檢查環境參數
    if [ -z "$environment" ]; then
        log_error "請指定部署環境 (staging 或 production)"
        show_usage
        exit 1
    fi
    
    # 檢查必要工具
    check_requirements
    
    # 載入環境變數
    load_env $environment
    
    # 執行回滾
    if [ "$ROLLBACK" = true ]; then
        rollback $environment
        exit 0
    fi
    
    # 執行測試
    run_tests
    
    # 建立備份
    create_backup $environment
    
    # 執行部署
    if [ "$environment" = "production" ]; then
        deploy_production
    else
        deploy_staging
    fi
    
    # 健康檢查
    if health_check $environment; then
        log_success "🎉 $environment 環境部署成功！"
    else
        log_error "❌ $environment 環境部署失敗，請檢查日誌"
        exit 1
    fi
}

# 執行主函數
main "$@"