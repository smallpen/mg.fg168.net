#!/bin/bash

# 開發環境快速設定腳本
# 使用方法: ./dev-setup.sh [選項]

set -e

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 顯示標題
echo -e "${BLUE}🚀 開發環境快速設定工具${NC}"
echo ""

# 檢查 Docker 是否運行
if ! docker-compose ps | grep -q "Up"; then
    echo -e "${YELLOW}⚠️  Docker 容器未運行，正在啟動...${NC}"
    docker-compose up -d
    echo -e "${GREEN}✅ Docker 容器已啟動${NC}"
    echo ""
fi

# 解析命令行參數
FRESH=false
USERS_ONLY=false
CHECK_ONLY=false
FORCE=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --fresh)
            FRESH=true
            shift
            ;;
        --users-only)
            USERS_ONLY=true
            shift
            ;;
        --check)
            CHECK_ONLY=true
            shift
            ;;
        --force)
            FORCE=true
            shift
            ;;
        -h|--help)
            echo "使用方法: $0 [選項]"
            echo ""
            echo "選項:"
            echo "  --fresh      清空資料庫並重新建立所有資料"
            echo "  --users-only 只重建使用者資料"
            echo "  --check      只檢查當前資料狀態"
            echo "  --force      強制執行，不詢問確認"
            echo "  -h, --help   顯示此幫助訊息"
            echo ""
            echo "範例:"
            echo "  $0                    # 建立/更新開發資料"
            echo "  $0 --fresh --force   # 完全重建資料庫"
            echo "  $0 --users-only      # 只重建使用者"
            echo "  $0 --check           # 檢查資料狀態"
            exit 0
            ;;
        *)
            echo -e "${RED}❌ 未知選項: $1${NC}"
            echo "使用 $0 --help 查看幫助"
            exit 1
            ;;
    esac
done

# 如果只是檢查，直接執行檢查命令
if [ "$CHECK_ONLY" = true ]; then
    echo -e "${BLUE}📊 檢查開發資料狀態...${NC}"
    docker-compose exec app php artisan dev:check --detailed
    exit 0
fi

# 構建命令
CMD="docker-compose exec app php artisan dev:setup"

if [ "$FRESH" = true ]; then
    CMD="$CMD --fresh"
    echo -e "${YELLOW}⚠️  將會清空整個資料庫並重新建立！${NC}"
elif [ "$USERS_ONLY" = true ]; then
    CMD="$CMD --users-only"
    echo -e "${BLUE}👥 將會重建使用者資料${NC}"
else
    echo -e "${BLUE}🌱 將會建立/更新開發資料${NC}"
fi

if [ "$FORCE" = true ]; then
    CMD="$CMD --force"
else
    echo ""
    read -p "確定要繼續嗎？ (y/N): " -n 1 -r
    echo ""
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo -e "${YELLOW}操作已取消${NC}"
        exit 0
    fi
fi

# 執行命令
echo -e "${BLUE}🔄 正在執行設定...${NC}"
eval $CMD

# 顯示完成訊息
echo ""
echo -e "${GREEN}✅ 設定完成！${NC}"
echo ""
echo -e "${BLUE}📋 快速測試：${NC}"
echo "  🌐 管理後台: http://localhost/admin/login"
echo "  👤 測試帳號: admin / password123"
echo ""
echo -e "${BLUE}🔍 檢查資料：${NC}"
echo "  ./dev-setup.sh --check"
echo ""
echo -e "${BLUE}🔄 重建資料：${NC}"
echo "  ./dev-setup.sh --fresh --force"
echo ""