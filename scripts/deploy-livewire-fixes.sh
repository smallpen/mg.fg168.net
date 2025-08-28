#!/bin/bash

# Livewire 表單重置修復部署腳本
# 專門用於部署 Livewire 表單重置修復

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

# 顯示使用說明
show_usage() {
    echo "使用方法: $0 [環境] [選項]"
    echo ""
    echo "環境:"
    echo "  dev         開發環境"
    echo "  staging     測試環境"
    echo "  production  生產環境"
    echo ""
    echo "選項:"
    echo "  --zero-downtime    執行零停機部署"
    echo "  --skip-backup      跳過備份步驟"
    echo "  --skip-tests       跳過測試執行"
    echo "  --force            強制部署（跳過確認）"
    echo "  --rollback         回滾到上一個版本"
    echo "  --help             顯示此說明"
    echo ""
    echo "範例:"
    echo "  $0 staging"
    echo "  $0 production --zero-downtime"
    echo "  $0 production --rollback"
}

# 取得 compose 檔案
get_compose_file() {
    local env=$1
    
    case $env in
        "dev"|"development")
            echo "docker-compose.yml"
            ;;
        "staging"|"test")
            echo "docker-compose.staging.yml"
            ;;
        "prod"|"production")
            echo "docker-compose.prod.yml"
            ;;
        *)
            echo "docker-compose.yml"
            ;;
    esac
}

# 執行部署前檢查
run_pre_deploy_checks() {
    local env=$1
    
    log_info "執行部署前檢查..."
    
    # 執行 Livewire 專用檢查
    if [ -f "scripts/livewire-form-reset-pre-deploy-check.sh" ]; then
        log_info "執行 Livewire 表單重置專用檢查..."
        if ! ./scripts/livewire-form-reset-pre-deploy-check.sh; then
            log_error "Livewire 專用檢查失敗"
            return 1
        fi
    fi
    
    # 執行一般系統檢查
    if [ -f "scripts/pre-deploy-check.sh" ]; then
        log_info "執行一般系統檢查..."
        if ! ./scripts/pre-deploy-check.sh; then
            log_error "系統檢查失敗"
            return 1
        fi
    fi
    
    log_success "部署前檢查完成"
}

# 建立部署備份
create_deployment_backup() {
    if [ "$SKIP_BACKUP" = true ]; then
        log_warning "跳過備份步驟"
        return
    fi
    
    local env=$1
    local backup_dir="backups/livewire-form-reset-backup-$(date +%Y%m%d_%H%M%S)"
    
    log_info "建立部署備份到 $backup_dir..."
    
    mkdir -p "$backup_dir"
    
    # 備份 Livewire 元件
    log_info "備份 Livewire 元件..."
    if [ -d "app/Livewire" ]; then
        cp -r app/Livewire "$backup_dir/"
    fi
    
    # 備份視圖檔案
    log_info "備份視圖檔案..."
    if [ -d "resources/views/livewire" ]; then
        cp -r resources/views/livewire "$backup_dir/"
    fi
    
    # 備份配置檔案
    log_info "備份配置檔案..."
    if [ -f "config/livewire.php" ]; then
        cp config/livewire.php "$backup_dir/"
    fi
    
    # 備份資料庫（如果容器正在運行）
    local compose_file=$(get_compose_file "$env")
    if docker-compose -f "$compose_file" ps mysql | grep -q "Up"; then
        log_info "備份資料庫..."
        docker-compose -f "$compose_file" exec -T mysql mysqldump \
            -u root -p$(cat secrets/mysql_root_password.txt) \
            --single-transaction --routines --triggers \
            laravel_admin > "$backup_dir/database.sql" 2>/dev/null || true
    fi
    
    # 記錄當前 Git 提交
    git rev-parse HEAD > "$backup_dir/git_commit.txt"
    
    # 記錄部署資訊
    cat > "$backup_dir/deployment_info.txt" << EOF
Deployment Date: $(date)
Environment: $env
Git Commit: $(git rev-parse HEAD)
Git Branch: $(git branch --show-current)
User: $(whoami)
Host: $(hostname)
EOF
    
    log_success "備份完成: $backup_dir"
    echo "$backup_dir" > .last_livewire_backup
}

# 執行測試
run_tests() {
    if [ "$SKIP_TESTS" = true ]; then
        log_warning "跳過測試執行"
        return
    fi
    
    local env=$1
    local compose_file=$(get_compose_file "$env")
    
    log_info "執行 Livewire 表單重置測試..."
    
    # 確保測試環境正在運行
    if [ "$env" != "production" ]; then
        log_info "啟動測試環境..."
        docker-compose -f "$compose_file" up -d
        sleep 15
    fi
    
    # 執行 PHPUnit 測試
    log_info "執行 PHPUnit 測試..."
    if docker-compose -f "$compose_file" exec -T app php artisan test --testsuite=Feature --filter=Livewire; then
        log_success "PHPUnit 測試通過"
    else
        log_warning "PHPUnit 測試失敗或沒有找到相關測試"
    fi
    
    # 執行 Playwright 測試（如果可用）
    if [ -f "scripts/run-livewire-playwright-tests.sh" ]; then
        log_info "執行 Playwright 測試..."
        if ./scripts/run-livewire-playwright-tests.sh "$env"; then
            log_success "Playwright 測試通過"
        else
            log_warning "Playwright 測試失敗"
        fi
    fi
    
    log_success "測試執行完成"
}

# 部署 Livewire 修復
deploy_livewire_fixes() {
    local env=$1
    local compose_file=$(get_compose_file "$env")
    local zero_downtime=$2
    
    log_info "開始部署 Livewire 表單重置修復到 $env 環境..."
    
    if [ "$zero_downtime" = true ]; then
        deploy_zero_downtime "$env" "$compose_file"
    else
        deploy_standard "$env" "$compose_file"
    fi
}

# 標準部署
deploy_standard() {
    local env=$1
    local compose_file=$2
    
    log_info "執行標準部署..."
    
    # 停止現有服務
    log_info "停止現有服務..."
    docker-compose -f "$compose_file" down
    
    # 建置新映像
    log_info "建置 Docker 映像..."
    docker-compose -f "$compose_file" build --no-cache app
    
    # 啟動服務
    log_info "啟動服務..."
    docker-compose -f "$compose_file" up -d
    
    # 等待服務準備就緒
    log_info "等待服務準備就緒..."
    sleep 30
    
    # 執行部署後處理
    post_deploy_processing "$env" "$compose_file"
}

# 零停機部署
deploy_zero_downtime() {
    local env=$1
    local compose_file=$2
    
    log_info "執行零停機部署..."
    
    # 建置新映像
    log_info "建置新 Docker 映像..."
    docker-compose -f "$compose_file" build --no-cache app
    
    # 擴展到多個實例
    log_info "擴展應用程式實例..."
    docker-compose -f "$compose_file" up -d --scale app=2 app
    
    # 等待新實例準備就緒
    log_info "等待新實例準備就緒..."
    sleep 45
    
    # 執行部署後處理
    post_deploy_processing "$env" "$compose_file"
    
    # 重新載入 Nginx（如果存在）
    if docker-compose -f "$compose_file" ps nginx | grep -q "Up"; then
        log_info "重新載入 Nginx 配置..."
        docker-compose -f "$compose_file" exec nginx nginx -s reload
    fi
    
    # 縮減到單一實例
    log_info "縮減到單一實例..."
    docker-compose -f "$compose_file" up -d --scale app=1 app
    
    # 清理舊映像
    log_info "清理舊 Docker 映像..."
    docker image prune -f
}

# 部署後處理
post_deploy_processing() {
    local env=$1
    local compose_file=$2
    
    log_info "執行部署後處理..."
    
    # 等待資料庫準備就緒
    log_info "等待資料庫準備就緒..."
    sleep 10
    
    # 執行資料庫遷移
    log_info "執行資料庫遷移..."
    docker-compose -f "$compose_file" exec -T app php artisan migrate --force
    
    # 清除所有快取
    log_info "清除應用程式快取..."
    docker-compose -f "$compose_file" exec -T app php artisan optimize:clear
    
    # 清除套件發現快取並重新發現
    log_info "重新發現 Livewire 元件..."
    docker-compose -f "$compose_file" exec -T app rm -f bootstrap/cache/packages.php bootstrap/cache/services.php
    docker-compose -f "$compose_file" exec -T app php artisan package:discover --ansi
    
    # 發現 Livewire 元件
    log_info "發現 Livewire 元件..."
    docker-compose -f "$compose_file" exec -T app php artisan livewire:discover
    
    # 編譯前端資源（如果需要）
    if [ -f "package.json" ]; then
        log_info "編譯前端資源..."
        docker-compose -f "$compose_file" exec -T app npm install --production
        docker-compose -f "$compose_file" exec -T app npm run build
    fi
    
    # 生產環境快取優化
    if [ "$env" = "production" ] || [ "$env" = "prod" ]; then
        log_info "執行生產環境快取優化..."
        docker-compose -f "$compose_file" exec -T app php artisan config:cache
        docker-compose -f "$compose_file" exec -T app php artisan route:cache
        docker-compose -f "$compose_file" exec -T app php artisan view:cache
        docker-compose -f "$compose_file" exec -T app php artisan event:cache
    fi
    
    # 重新啟動佇列工作者
    log_info "重新啟動佇列工作者..."
    docker-compose -f "$compose_file" exec -T app php artisan queue:restart
    
    log_success "部署後處理完成"
}

# 執行部署後驗證
run_post_deploy_verification() {
    local env=$1
    
    log_info "執行部署後驗證..."
    
    # 執行一般驗證
    if [ -f "scripts/post-deploy-verify.sh" ]; then
        log_info "執行一般系統驗證..."
        if ./scripts/post-deploy-verify.sh "$env"; then
            log_success "系統驗證通過"
        else
            log_error "系統驗證失敗"
            return 1
        fi
    fi
    
    # 執行 Livewire 專用驗證
    if [ -f "scripts/livewire-form-reset-verification.sh" ]; then
        log_info "執行 Livewire 表單重置驗證..."
        if ./scripts/livewire-form-reset-verification.sh "$env"; then
            log_success "Livewire 驗證通過"
        else
            log_error "Livewire 驗證失敗"
            return 1
        fi
    fi
    
    # 執行健康檢查
    if [ -f "scripts/livewire-health-check.sh" ]; then
        log_info "執行健康檢查..."
        if ./scripts/livewire-health-check.sh "$env"; then
            log_success "健康檢查通過"
        else
            log_warning "健康檢查發現問題"
        fi
    fi
    
    log_success "部署後驗證完成"
}

# 回滾功能
rollback_deployment() {
    local env=$1
    
    if [ ! -f ".last_livewire_backup" ]; then
        log_error "找不到備份記錄，無法回滾"
        exit 1
    fi
    
    local backup_dir=$(cat .last_livewire_backup)
    
    if [ ! -d "$backup_dir" ]; then
        log_error "備份目錄不存在: $backup_dir"
        exit 1
    fi
    
    log_info "回滾 Livewire 表單重置修復到備份: $backup_dir"
    
    # 確認回滾
    if [ "$FORCE_DEPLOY" != true ]; then
        echo -n "確定要回滾嗎？這將覆蓋當前的修復！ (y/N): "
        read -r response
        if [[ ! "$response" =~ ^[Yy]$ ]]; then
            log_info "回滾已取消"
            exit 0
        fi
    fi
    
    local compose_file=$(get_compose_file "$env")
    
    # 停止服務
    log_info "停止服務..."
    docker-compose -f "$compose_file" down
    
    # 恢復程式碼
    if [ -f "$backup_dir/git_commit.txt" ]; then
        local commit=$(cat "$backup_dir/git_commit.txt")
        log_info "恢復到 Git 提交: $commit"
        git reset --hard "$commit"
    fi
    
    # 恢復 Livewire 元件
    if [ -d "$backup_dir/Livewire" ]; then
        log_info "恢復 Livewire 元件..."
        rm -rf app/Livewire
        cp -r "$backup_dir/Livewire" app/
    fi
    
    # 恢復視圖檔案
    if [ -d "$backup_dir/livewire" ]; then
        log_info "恢復視圖檔案..."
        rm -rf resources/views/livewire
        cp -r "$backup_dir/livewire" resources/views/
    fi
    
    # 恢復配置檔案
    if [ -f "$backup_dir/livewire.php" ]; then
        log_info "恢復 Livewire 配置..."
        cp "$backup_dir/livewire.php" config/
    fi
    
    # 重新啟動服務
    log_info "重新啟動服務..."
    docker-compose -f "$compose_file" up -d
    
    # 等待服務啟動
    sleep 30
    
    # 恢復資料庫
    if [ -f "$backup_dir/database.sql" ]; then
        log_info "恢復資料庫..."
        docker-compose -f "$compose_file" exec -T mysql mysql \
            -u root -p$(cat secrets/mysql_root_password.txt) \
            laravel_admin < "$backup_dir/database.sql"
    fi
    
    # 清除快取
    log_info "清除快取..."
    docker-compose -f "$compose_file" exec -T app php artisan optimize:clear
    docker-compose -f "$compose_file" exec -T app php artisan livewire:discover
    
    log_success "回滾完成"
}

# 主要執行邏輯
main() {
    local environment=""
    local ZERO_DOWNTIME=false
    local SKIP_BACKUP=false
    local SKIP_TESTS=false
    local FORCE_DEPLOY=false
    local ROLLBACK=false
    
    # 解析參數
    while [[ $# -gt 0 ]]; do
        case $1 in
            dev|development|staging|test|prod|production)
                environment="$1"
                shift
                ;;
            --zero-downtime)
                ZERO_DOWNTIME=true
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
        log_error "請指定部署環境"
        show_usage
        exit 1
    fi
    
    echo "🚀 Livewire 表單重置修復部署"
    echo "=============================="
    echo "環境: $environment"
    echo "零停機部署: $ZERO_DOWNTIME"
    echo "跳過備份: $SKIP_BACKUP"
    echo "跳過測試: $SKIP_TESTS"
    echo ""
    
    # 執行回滾
    if [ "$ROLLBACK" = true ]; then
        rollback_deployment "$environment"
        exit 0
    fi
    
    # 確認部署（生產環境）
    if [ "$environment" = "production" ] && [ "$FORCE_DEPLOY" != true ]; then
        echo -n "確定要部署 Livewire 表單重置修復到生產環境嗎？ (y/N): "
        read -r response
        if [[ ! "$response" =~ ^[Yy]$ ]]; then
            log_info "部署已取消"
            exit 0
        fi
    fi
    
    # 執行部署流程
    local start_time=$(date +%s)
    
    # 1. 部署前檢查
    if ! run_pre_deploy_checks "$environment"; then
        log_error "部署前檢查失敗，停止部署"
        exit 1
    fi
    
    # 2. 建立備份
    create_deployment_backup "$environment"
    
    # 3. 執行測試
    run_tests "$environment"
    
    # 4. 執行部署
    deploy_livewire_fixes "$environment" "$ZERO_DOWNTIME"
    
    # 5. 部署後驗證
    if ! run_post_deploy_verification "$environment"; then
        log_error "部署後驗證失敗"
        log_warning "考慮執行回滾: $0 $environment --rollback"
        exit 1
    fi
    
    local end_time=$(date +%s)
    local duration=$((end_time - start_time))
    
    log_success "🎉 Livewire 表單重置修復部署完成！"
    echo ""
    log_info "部署摘要："
    echo "  環境: $environment"
    echo "  耗時: ${duration} 秒"
    echo "  備份: $(cat .last_livewire_backup 2>/dev/null || echo '無')"
    echo "  Git 提交: $(git rev-parse --short HEAD)"
    echo ""
    log_info "後續建議："
    echo "  1. 監控系統運行狀態"
    echo "  2. 檢查使用者回饋"
    echo "  3. 觀察效能指標"
    echo "  4. 如有問題，執行回滾: $0 $environment --rollback"
}

# 執行主函數
main "$@"