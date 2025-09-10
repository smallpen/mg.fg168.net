#!/bin/bash

# 部署後驗證腳本
# 驗證所有服務是否正常運行

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
ENVIRONMENT=${1:-dev}

# 選擇對應的 compose 檔案
case $ENVIRONMENT in
    "prod"|"production")
        COMPOSE_FILE="docker-compose.prod.yml"
        ;;
    "staging"|"test")
        COMPOSE_FILE="docker-compose.staging.yml"
        ;;
    *)
        COMPOSE_FILE="docker-compose.yml"
        ;;
esac

log_info "驗證 $ENVIRONMENT 環境部署狀態..."
echo ""

# 檢查容器狀態
log_info "檢查容器狀態..."
UNHEALTHY_CONTAINERS=$(docker compose -f "$COMPOSE_FILE" ps --filter "health=unhealthy" -q)
if [ -n "$UNHEALTHY_CONTAINERS" ]; then
    log_error "發現不健康的容器："
    docker compose -f "$COMPOSE_FILE" ps --filter "health=unhealthy"
    exit 1
else
    log_success "所有容器狀態正常"
fi

# 檢查資料庫連線
log_info "檢查資料庫連線..."
if docker compose -f "$COMPOSE_FILE" exec -T app php artisan tinker --execute="use Illuminate\Support\Facades\DB; DB::connection()->getPdo(); echo 'Database OK';" > /dev/null 2>&1; then
    log_success "資料庫連線正常"
else
    log_error "資料庫連線失敗"
    exit 1
fi

# 檢查 Redis 連線
log_info "檢查 Redis 連線..."
if docker compose -f "$COMPOSE_FILE" exec -T app php artisan tinker --execute="use Illuminate\Support\Facades\Redis; Redis::ping(); echo 'Redis OK';" > /dev/null 2>&1; then
    log_success "Redis 連線正常"
else
    log_error "Redis 連線失敗"
    exit 1
fi

# 檢查應用程式健康狀態
log_info "檢查應用程式健康狀態..."
if [ "$ENVIRONMENT" = "prod" ] || [ "$ENVIRONMENT" = "production" ]; then
    HEALTH_URL="http://localhost/health"
elif [ "$ENVIRONMENT" = "staging" ] || [ "$ENVIRONMENT" = "test" ]; then
    HEALTH_URL="http://localhost:8080/health"
else
    HEALTH_URL="http://localhost/health"
fi

HEALTH_STATUS=$(curl -s "$HEALTH_URL" 2>/dev/null || echo "failed")
if [ "$HEALTH_STATUS" = "healthy" ]; then
    log_success "應用程式健康檢查通過"
else
    log_warning "應用程式健康檢查失敗或端點不可用"
fi

# 檢查檔案權限
log_info "檢查檔案權限..."
if docker compose -f "$COMPOSE_FILE" exec -T app test -w /var/www/html/storage; then
    log_success "storage 目錄權限正常"
else
    log_error "storage 目錄權限異常"
    exit 1
fi

if docker compose -f "$COMPOSE_FILE" exec -T app test -w /var/www/html/bootstrap/cache; then
    log_success "bootstrap/cache 目錄權限正常"
else
    log_error "bootstrap/cache 目錄權限異常"
    exit 1
fi

echo ""
log_success "🎉 所有驗證都通過！$ENVIRONMENT 環境運行正常。"

# 顯示服務資訊
echo ""
log_info "服務狀態摘要："
docker compose -f "$COMPOSE_FILE" ps --format "table {{.Name}}\t{{.Status}}\t{{.Ports}}"

exit 0