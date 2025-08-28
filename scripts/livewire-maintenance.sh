#!/bin/bash

# Livewire 表單重置維護腳本
# 執行定期維護任務和問題修復

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
    echo "  --clean-cache      清理快取"
    echo "  --fix-permissions  修復權限"
    echo "  --optimize         執行優化"
    echo "  --check-integrity  檢查完整性"
    echo "  --repair           執行修復"
    echo "  --full-maintenance 執行完整維護"
    echo "  --help             顯示此說明"
    echo ""
    echo "範例:"
    echo "  $0 production --full-maintenance"
    echo "  $0 staging --clean-cache"
    echo "  $0 production --repair"
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

# 清理快取
clean_cache() {
    local env=$1
    local compose_file=$(get_compose_file "$env")
    
    log_info "清理應用程式快取..."
    
    # 清理 Laravel 快取
    docker-compose -f "$compose_file" exec -T app php artisan cache:clear
    docker-compose -f "$compose_file" exec -T app php artisan config:clear
    docker-compose -f "$compose_file" exec -T app php artisan route:clear
    docker-compose -f "$compose_file" exec -T app php artisan view:clear
    
    # 清理套件發現快取
    log_info "清理套件發現快取..."
    docker-compose -f "$compose_file" exec -T app rm -f bootstrap/cache/packages.php bootstrap/cache/services.php
    docker-compose -f "$compose_file" exec -T app php artisan package:discover --ansi
    
    # 重新發現 Livewire 元件
    log_info "重新發現 Livewire 元件..."
    docker-compose -f "$compose_file" exec -T app php artisan livewire:discover
    
    # 清理 Redis 快取
    log_info "清理 Redis 快取..."
    docker-compose -f "$compose_file" exec -T app php artisan tinker --execute="
        use Illuminate\Support\Facades\Redis;
        Redis::flushdb();
        echo 'Redis cache cleared';
    " > /dev/null 2>&1 || log_warning "Redis 快取清理失敗"
    
    # 重新建立快取（生產環境）
    if [ "$env" = "production" ] || [ "$env" = "prod" ]; then
        log_info "重新建立生產環境快取..."
        docker-compose -f "$compose_file" exec -T app php artisan config:cache
        docker-compose -f "$compose_file" exec -T app php artisan route:cache
        docker-compose -f "$compose_file" exec -T app php artisan view:cache
        docker-compose -f "$compose_file" exec -T app php artisan event:cache
    fi
    
    log_success "快取清理完成"
}

# 修復檔案權限
fix_permissions() {
    local env=$1
    local compose_file=$(get_compose_file "$env")
    
    log_info "修復檔案權限..."
    
    # 修復 storage 目錄權限
    docker-compose -f "$compose_file" exec -T app chown -R www-data:www-data storage
    docker-compose -f "$compose_file" exec -T app chmod -R 775 storage
    
    # 修復 bootstrap/cache 目錄權限
    docker-compose -f "$compose_file" exec -T app chown -R www-data:www-data bootstrap/cache
    docker-compose -f "$compose_file" exec -T app chmod -R 775 bootstrap/cache
    
    # 修復日誌檔案權限
    docker-compose -f "$compose_file" exec -T app find storage/logs -type f -exec chmod 664 {} \;
    
    log_success "檔案權限修復完成"
}

# 執行系統優化
optimize_system() {
    local env=$1
    local compose_file=$(get_compose_file "$env")
    
    log_info "執行系統優化..."
    
    # Composer 優化
    log_info "優化 Composer 自動載入..."
    docker-compose -f "$compose_file" exec -T app composer dump-autoload --optimize
    
    # 清理未使用的 Docker 資源
    log_info "清理 Docker 資源..."
    docker system prune -f > /dev/null 2>&1 || true
    
    # 優化資料庫（如果是 MySQL）
    log_info "優化資料庫..."
    docker-compose -f "$compose_file" exec -T mysql mysqlcheck --optimize --all-databases \
        -u root -p$(cat secrets/mysql_root_password.txt 2>/dev/null || echo '') > /dev/null 2>&1 || \
        log_warning "資料庫優化失敗或跳過"
    
    # 清理舊日誌檔案
    log_info "清理舊日誌檔案..."
    docker-compose -f "$compose_file" exec -T app find storage/logs -name "*.log" -mtime +30 -delete 2>/dev/null || true
    
    # 清理臨時檔案
    log_info "清理臨時檔案..."
    docker-compose -f "$compose_file" exec -T app find storage/framework/cache -name "*.php" -mtime +7 -delete 2>/dev/null || true
    docker-compose -f "$compose_file" exec -T app find storage/framework/sessions -name "sess_*" -mtime +1 -delete 2>/dev/null || true
    
    log_success "系統優化完成"
}

# 檢查系統完整性
check_integrity() {
    local env=$1
    local compose_file=$(get_compose_file "$env")
    
    log_info "檢查系統完整性..."
    
    local issues=()
    
    # 檢查關鍵檔案
    log_info "檢查關鍵檔案..."
    local critical_files=(
        "app/Livewire"
        "resources/views/livewire"
        "config/livewire.php"
        "composer.json"
        "package.json"
    )
    
    for file in "${critical_files[@]}"; do
        if [ ! -e "$file" ]; then
            issues+=("缺少關鍵檔案: $file")
        fi
    done
    
    # 檢查 Livewire 元件完整性
    log_info "檢查 Livewire 元件完整性..."
    if ! docker-compose -f "$compose_file" exec -T app php artisan livewire:discover > /dev/null 2>&1; then
        issues+=("Livewire 元件發現失敗")
    fi
    
    # 檢查資料庫完整性
    log_info "檢查資料庫完整性..."
    if ! docker-compose -f "$compose_file" exec -T app php artisan migrate:status > /dev/null 2>&1; then
        issues+=("資料庫遷移狀態異常")
    fi
    
    # 檢查關鍵資料表
    local tables=("users" "roles" "permissions" "user_roles" "role_permissions")
    for table in "${tables[@]}"; do
        if ! docker-compose -f "$compose_file" exec -T app php artisan tinker --execute="
            use Illuminate\Support\Facades\Schema;
            echo Schema::hasTable('$table') ? 'EXISTS' : 'MISSING';
        " 2>/dev/null | grep -q "EXISTS"; then
            issues+=("資料表缺失: $table")
        fi
    done
    
    # 檢查配置完整性
    log_info "檢查配置完整性..."
    local required_configs=("APP_KEY" "DB_DATABASE" "DB_USERNAME" "DB_PASSWORD" "REDIS_PASSWORD")
    for config in "${required_configs[@]}"; do
        if ! grep -q "^$config=" .env 2>/dev/null; then
            issues+=("缺少配置: $config")
        fi
    done
    
    # 檢查前端資源
    log_info "檢查前端資源..."
    if [ ! -d "public/build" ] || [ -z "$(ls -A public/build 2>/dev/null)" ]; then
        issues+=("前端資源未編譯")
    fi
    
    # 報告結果
    if [ ${#issues[@]} -eq 0 ]; then
        log_success "系統完整性檢查通過"
        return 0
    else
        log_error "發現 ${#issues[@]} 個完整性問題："
        for issue in "${issues[@]}"; do
            echo "  - $issue"
        done
        return 1
    fi
}

# 執行系統修復
repair_system() {
    local env=$1
    local compose_file=$(get_compose_file "$env")
    
    log_info "執行系統修復..."
    
    # 修復 Livewire 元件
    log_info "修復 Livewire 元件..."
    docker-compose -f "$compose_file" exec -T app php artisan livewire:discover || {
        log_warning "Livewire 元件發現失敗，嘗試清理快取後重試"
        clean_cache "$env"
        docker-compose -f "$compose_file" exec -T app php artisan livewire:discover
    }
    
    # 修復資料庫
    log_info "修復資料庫..."
    docker-compose -f "$compose_file" exec -T app php artisan migrate --force || {
        log_warning "資料庫遷移失敗"
    }
    
    # 修復檔案權限
    fix_permissions "$env"
    
    # 重新編譯前端資源（如果需要）
    if [ ! -d "public/build" ] || [ -z "$(ls -A public/build 2>/dev/null)" ]; then
        log_info "重新編譯前端資源..."
        if [ -f "package.json" ]; then
            docker-compose -f "$compose_file" exec -T app npm install --production
            docker-compose -f "$compose_file" exec -T app npm run build
        fi
    fi
    
    # 重新啟動服務
    log_info "重新啟動關鍵服務..."
    docker-compose -f "$compose_file" restart app
    
    # 等待服務準備就緒
    sleep 10
    
    # 驗證修復結果
    log_info "驗證修復結果..."
    if check_integrity "$env"; then
        log_success "系統修復完成且驗證通過"
    else
        log_warning "系統修復完成但仍有問題需要手動處理"
    fi
}

# 執行完整維護
full_maintenance() {
    local env=$1
    
    log_info "開始執行完整維護..."
    
    local start_time=$(date +%s)
    
    # 建立維護備份
    log_info "建立維護備份..."
    local backup_dir="backups/maintenance-backup-$(date +%Y%m%d_%H%M%S)"
    mkdir -p "$backup_dir"
    
    # 備份關鍵檔案
    cp -r app/Livewire "$backup_dir/" 2>/dev/null || true
    cp -r resources/views/livewire "$backup_dir/" 2>/dev/null || true
    cp config/livewire.php "$backup_dir/" 2>/dev/null || true
    
    # 記錄維護資訊
    cat > "$backup_dir/maintenance_info.txt" << EOF
Maintenance Date: $(date)
Environment: $env
Git Commit: $(git rev-parse HEAD 2>/dev/null || echo 'unknown')
User: $(whoami)
Host: $(hostname)
EOF
    
    # 執行維護步驟
    local maintenance_steps=(
        "clean_cache"
        "fix_permissions"
        "optimize_system"
        "check_integrity"
    )
    
    local failed_steps=()
    
    for step in "${maintenance_steps[@]}"; do
        log_info "執行維護步驟: $step"
        if ! $step "$env"; then
            failed_steps+=("$step")
            log_warning "維護步驟失敗: $step"
        fi
    done
    
    # 如果有失敗的步驟，嘗試修復
    if [ ${#failed_steps[@]} -gt 0 ]; then
        log_warning "發現 ${#failed_steps[@]} 個失敗的維護步驟，嘗試修復..."
        repair_system "$env"
    fi
    
    # 執行最終健康檢查
    log_info "執行最終健康檢查..."
    if [ -f "scripts/livewire-health-check.sh" ]; then
        ./scripts/livewire-health-check.sh "$env" true
    fi
    
    local end_time=$(date +%s)
    local duration=$((end_time - start_time))
    
    # 生成維護報告
    local report_file="monitoring/reports/maintenance-report-$(date +%Y%m%d_%H%M%S).md"
    mkdir -p "monitoring/reports"
    
    cat > "$report_file" << EOF
# Livewire 表單重置維護報告

## 維護資訊
- **維護時間**: $(date)
- **環境**: $env
- **耗時**: ${duration} 秒
- **備份位置**: $backup_dir

## 維護步驟
EOF
    
    for step in "${maintenance_steps[@]}"; do
        if [[ " ${failed_steps[*]} " =~ " ${step} " ]]; then
            echo "- ❌ $step (失敗)" >> "$report_file"
        else
            echo "- ✅ $step (成功)" >> "$report_file"
        fi
    done
    
    cat >> "$report_file" << EOF

## 系統狀態
- Git 提交: $(git rev-parse --short HEAD 2>/dev/null || echo 'unknown')
- 磁碟使用: $(df . | tail -1 | awk '{print $5}')
- 容器狀態: $(docker-compose -f "$(get_compose_file "$env")" ps --filter "health=healthy" -q | wc -l) 個健康容器

## 建議事項
EOF
    
    if [ ${#failed_steps[@]} -gt 0 ]; then
        echo "- 手動檢查失敗的維護步驟: ${failed_steps[*]}" >> "$report_file"
    fi
    
    if [ "$(df . | tail -1 | awk '{print $5}' | sed 's/%//')" -gt 80 ]; then
        echo "- 磁碟使用率較高，建議清理舊檔案" >> "$report_file"
    fi
    
    echo "- 持續監控系統運行狀態" >> "$report_file"
    
    log_success "完整維護完成！"
    log_info "維護報告: $report_file"
    log_info "備份位置: $backup_dir"
    
    if [ ${#failed_steps[@]} -eq 0 ]; then
        log_success "🎉 所有維護步驟都成功完成！"
    else
        log_warning "⚠️ 部分維護步驟失敗，請檢查維護報告"
    fi
}

# 記錄維護日誌
log_maintenance() {
    local action=$1
    local env=$2
    local status=$3
    local details=$4
    
    local log_file="monitoring/logs/maintenance.log"
    mkdir -p "monitoring/logs"
    
    echo "[$(date)] $action - $env - $status - $details" >> "$log_file"
}

# 主要執行邏輯
main() {
    local environment=""
    local action=""
    
    # 解析參數
    while [[ $# -gt 0 ]]; do
        case $1 in
            dev|development|staging|test|prod|production)
                environment="$1"
                shift
                ;;
            --clean-cache)
                action="clean-cache"
                shift
                ;;
            --fix-permissions)
                action="fix-permissions"
                shift
                ;;
            --optimize)
                action="optimize"
                shift
                ;;
            --check-integrity)
                action="check-integrity"
                shift
                ;;
            --repair)
                action="repair"
                shift
                ;;
            --full-maintenance)
                action="full-maintenance"
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
    
    # 檢查參數
    if [ -z "$environment" ]; then
        log_error "請指定環境"
        show_usage
        exit 1
    fi
    
    if [ -z "$action" ]; then
        log_error "請指定維護動作"
        show_usage
        exit 1
    fi
    
    echo "🔧 Livewire 表單重置維護"
    echo "======================="
    echo "環境: $environment"
    echo "動作: $action"
    echo "時間: $(date)"
    echo ""
    
    # 記錄維護開始
    log_maintenance "$action" "$environment" "started" "維護開始"
    
    local start_time=$(date +%s)
    local success=true
    
    # 執行對應動作
    case $action in
        "clean-cache")
            clean_cache "$environment" || success=false
            ;;
        "fix-permissions")
            fix_permissions "$environment" || success=false
            ;;
        "optimize")
            optimize_system "$environment" || success=false
            ;;
        "check-integrity")
            check_integrity "$environment" || success=false
            ;;
        "repair")
            repair_system "$environment" || success=false
            ;;
        "full-maintenance")
            full_maintenance "$environment" || success=false
            ;;
    esac
    
    local end_time=$(date +%s)
    local duration=$((end_time - start_time))
    
    # 記錄維護結果
    if [ "$success" = true ]; then
        log_maintenance "$action" "$environment" "completed" "維護成功完成，耗時 ${duration} 秒"
        log_success "🎉 維護任務完成！耗時 ${duration} 秒"
    else
        log_maintenance "$action" "$environment" "failed" "維護失敗，耗時 ${duration} 秒"
        log_error "❌ 維護任務失敗！耗時 ${duration} 秒"
        exit 1
    fi
}

# 執行主函數
main "$@"