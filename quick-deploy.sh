#!/bin/bash

# Laravel Admin 系統快速部署腳本
# 使用新的 docker compose 語法

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
    echo "  dev         開發環境 (預設)"
    echo "  staging     測試環境"
    echo "  prod        生產環境"
    echo ""
    echo "選項:"
    echo "  --build       強制重新建置映像"
    echo "  --down        停止並移除容器"
    echo "  --logs        顯示服務日誌"
    echo "  --status      顯示服務狀態"
    echo "  --skip-checks 跳過部署前檢查"
    echo "  --help      顯示此說明"
    echo ""
    echo "範例:"
    echo "  $0                    # 啟動開發環境"
    echo "  $0 prod --build       # 重新建置並啟動生產環境"
    echo "  $0 staging --down     # 停止測試環境"
    echo "  $0 --logs             # 顯示開發環境日誌"
}

# 檢查 Docker 和 Docker Compose
check_docker() {
    log_info "檢查 Docker 環境..."
    
    if ! command -v docker &> /dev/null; then
        log_error "Docker 未安裝或不在 PATH 中"
        exit 1
    fi
    
    # 檢查 Docker Compose 版本
    if docker compose version &> /dev/null; then
        log_success "Docker Compose 可用 (新版語法)"
    elif docker-compose --version &> /dev/null; then
        log_warning "檢測到舊版 docker-compose，建議升級到新版 Docker"
        log_warning "將使用 docker-compose 指令"
        COMPOSE_CMD="docker-compose"
    else
        log_error "Docker Compose 不可用"
        exit 1
    fi
    
    # 預設使用新版語法
    COMPOSE_CMD=${COMPOSE_CMD:-"docker compose"}
}

# 選擇 compose 檔案
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

# 建置服務
build_services() {
    local compose_file=$1
    local force_build=$2
    
    log_info "建置 Docker 映像..."
    
    if [ "$force_build" = true ]; then
        $COMPOSE_CMD -f "$compose_file" build --no-cache
    else
        $COMPOSE_CMD -f "$compose_file" build
    fi
    
    log_success "映像建置完成"
}

# 啟動服務
start_services() {
    local compose_file=$1
    
    log_info "啟動服務..."
    $COMPOSE_CMD -f "$compose_file" up -d
    
    log_info "等待服務啟動..."
    sleep 10
    
    log_success "服務啟動完成"
}

# 停止服務
stop_services() {
    local compose_file=$1
    
    log_info "停止並移除容器..."
    $COMPOSE_CMD -f "$compose_file" down
    
    log_success "服務已停止"
}

# 顯示日誌
show_logs() {
    local compose_file=$1
    
    log_info "顯示服務日誌..."
    $COMPOSE_CMD -f "$compose_file" logs -f
}

# 顯示狀態
show_status() {
    local compose_file=$1
    
    log_info "服務狀態："
    $COMPOSE_CMD -f "$compose_file" ps
    
    echo ""
    log_info "映像資訊："
    $COMPOSE_CMD -f "$compose_file" images
}

# 執行應用程式初始化
init_application() {
    local compose_file=$1
    local env=$2
    
    log_info "初始化應用程式..."
    
    # 等待資料庫準備就緒
    log_info "等待資料庫準備就緒..."
    sleep 15
    
    # 修復檔案權限
    log_info "修復檔案權限..."
    $COMPOSE_CMD -f "$compose_file" exec -T app sh /scripts/fix-permissions.sh
    
    # 執行遷移
    log_info "執行資料庫遷移..."
    $COMPOSE_CMD -f "$compose_file" exec -T app php artisan migrate --force
    
    # 清理快取
    log_info "清理應用程式快取..."
    $COMPOSE_CMD -f "$compose_file" exec -T app php artisan config:clear
    $COMPOSE_CMD -f "$compose_file" exec -T app php artisan route:clear
    $COMPOSE_CMD -f "$compose_file" exec -T app php artisan view:clear
    $COMPOSE_CMD -f "$compose_file" exec -T app php artisan cache:clear
    
    # 清除套件發現快取並重新發現套件（確保只載入對應環境的套件）
    log_info "重新發現套件..."
    $COMPOSE_CMD -f "$compose_file" exec -T app rm -f bootstrap/cache/packages.php bootstrap/cache/services.php
    $COMPOSE_CMD -f "$compose_file" exec -T app php artisan package:discover --ansi
    
    # 快取設定（生產環境）
    if [ "$env" = "prod" ] || [ "$env" = "production" ]; then
        log_info "快取設定檔案..."
        $COMPOSE_CMD -f "$compose_file" exec -T app php artisan config:cache
        $COMPOSE_CMD -f "$compose_file" exec -T app php artisan route:cache
        $COMPOSE_CMD -f "$compose_file" exec -T app php artisan view:cache
    fi
    
    log_success "應用程式初始化完成"
}

# 健康檢查
health_check() {
    local compose_file=$1
    
    log_info "執行健康檢查..."
    
    # 檢查容器狀態
    local unhealthy_containers=$($COMPOSE_CMD -f "$compose_file" ps --filter "health=unhealthy" -q)
    
    if [ -n "$unhealthy_containers" ]; then
        log_warning "發現不健康的容器"
        $COMPOSE_CMD -f "$compose_file" ps --filter "health=unhealthy"
    else
        log_success "所有容器狀態正常"
    fi
    
    # 檢查應用程式
    if $COMPOSE_CMD -f "$compose_file" exec -T app php artisan tinker --execute="echo 'OK';" > /dev/null 2>&1; then
        log_success "應用程式健康檢查通過"
    else
        log_warning "應用程式健康檢查失敗"
    fi
}

# 主要執行邏輯
main() {
    local environment="dev"
    local force_build=false
    local action="start"
    local skip_checks=false
    
    # 解析參數
    while [[ $# -gt 0 ]]; do
        case $1 in
            dev|development|staging|test|prod|production)
                environment="$1"
                shift
                ;;
            --build)
                force_build=true
                shift
                ;;
            --down)
                action="stop"
                shift
                ;;
            --logs)
                action="logs"
                shift
                ;;
            --status)
                action="status"
                shift
                ;;
            --skip-checks)
                skip_checks=true
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
    
    # 檢查 Docker 環境
    check_docker
    
    # 取得 compose 檔案
    local compose_file=$(get_compose_file "$environment")
    
    if [ ! -f "$compose_file" ]; then
        log_error "找不到 compose 檔案: $compose_file"
        exit 1
    fi
    
    log_info "使用環境: $environment"
    log_info "使用檔案: $compose_file"
    log_info "使用指令: $COMPOSE_CMD"
    
    # 執行對應動作
    case $action in
        "start")
            # 執行部署前檢查（除非跳過）
            if [ "$skip_checks" = false ] && [ -f "scripts/pre-deploy-check.sh" ]; then
                log_info "執行部署前檢查..."
                if ! ./scripts/pre-deploy-check.sh; then
                    log_error "部署前檢查失敗，請解決問題後再試"
                    log_info "如要跳過檢查，請使用 --skip-checks 參數"
                    exit 1
                fi
                echo ""
            fi
            
            if [ "$force_build" = true ]; then
                build_services "$compose_file" true
            fi
            start_services "$compose_file"
            init_application "$compose_file" "$environment"
            health_check "$compose_file"
            show_status "$compose_file"
            
            # 執行部署後驗證
            if [ -f "scripts/post-deploy-verify.sh" ]; then
                log_info "執行部署後驗證..."
                echo ""
                if ./scripts/post-deploy-verify.sh "$environment"; then
                    log_success "🎉 $environment 環境部署完成且驗證通過！"
                else
                    log_warning "部署完成但驗證發現問題，請檢查上述訊息"
                fi
            else
                log_success "🎉 $environment 環境部署完成！"
            fi
            
            echo ""
            # 顯示存取資訊
            case $environment in
                "dev"|"development")
                    log_info "應用程式網址: http://localhost"
                    ;;
                "staging"|"test")
                    log_info "應用程式網址: http://localhost:8080"
                    ;;
                "prod"|"production")
                    log_info "應用程式網址: http://localhost"
                    log_info "HTTPS 網址: https://localhost"
                    ;;
            esac
            ;;
        "stop")
            stop_services "$compose_file"
            ;;
        "logs")
            show_logs "$compose_file"
            ;;
        "status")
            show_status "$compose_file"
            ;;
    esac
}

# 執行主函數
main "$@"