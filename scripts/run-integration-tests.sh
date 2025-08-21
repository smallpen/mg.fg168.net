#!/bin/bash

# 角色管理整合測試執行腳本
# 使用方法: ./scripts/run-integration-tests.sh [test-type] [options]

set -e

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 預設配置
TEST_TYPE="all"
COVERAGE=false
PARALLEL=false
VERBOSE=false
CLEANUP=true
ENVIRONMENT="testing"

# 顯示使用說明
show_help() {
    echo "角色管理整合測試執行腳本"
    echo ""
    echo "使用方法: $0 [選項]"
    echo ""
    echo "選項:"
    echo "  -t, --type TYPE        測試類型 (all|integration|performance|browser|security)"
    echo "  -c, --coverage         生成測試覆蓋率報告"
    echo "  -p, --parallel         並行執行測試"
    echo "  -v, --verbose          詳細輸出"
    echo "  --no-cleanup          不清理測試資料"
    echo "  -e, --env ENV         測試環境 (testing|integration)"
    echo "  -h, --help            顯示此說明"
    echo ""
    echo "範例:"
    echo "  $0 -t integration -c    # 執行整合測試並生成覆蓋率報告"
    echo "  $0 -t performance -v    # 執行效能測試並顯示詳細輸出"
    echo "  $0 -t browser          # 執行瀏覽器測試"
    echo "  $0 --parallel          # 並行執行所有測試"
}

# 解析命令列參數
while [[ $# -gt 0 ]]; do
    case $1 in
        -t|--type)
            TEST_TYPE="$2"
            shift 2
            ;;
        -c|--coverage)
            COVERAGE=true
            shift
            ;;
        -p|--parallel)
            PARALLEL=true
            shift
            ;;
        -v|--verbose)
            VERBOSE=true
            shift
            ;;
        --no-cleanup)
            CLEANUP=false
            shift
            ;;
        -e|--env)
            ENVIRONMENT="$2"
            shift 2
            ;;
        -h|--help)
            show_help
            exit 0
            ;;
        *)
            echo "未知選項: $1"
            show_help
            exit 1
            ;;
    esac
done

# 檢查 Docker 是否運行
check_docker() {
    if ! docker info > /dev/null 2>&1; then
        echo -e "${RED}錯誤: Docker 未運行，請先啟動 Docker${NC}"
        exit 1
    fi
}

# 設定測試環境
setup_environment() {
    echo -e "${BLUE}設定測試環境...${NC}"
    
    # 複製環境檔案
    if [ "$ENVIRONMENT" = "integration" ]; then
        cp .env.testing.integration .env
    else
        cp .env.testing .env
    fi
    
    # 生成應用程式金鑰
    php artisan key:generate --force
    
    # 清除快取
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    
    echo -e "${GREEN}環境設定完成${NC}"
}

# 準備資料庫
prepare_database() {
    echo -e "${BLUE}準備測試資料庫...${NC}"
    
    # 執行遷移
    php artisan migrate:fresh --force
    
    # 如果需要，執行基礎資料填充
    if [ "$ENVIRONMENT" = "integration" ]; then
        php artisan db:seed --class=TestDataSeeder --force
    fi
    
    echo -e "${GREEN}資料庫準備完成${NC}"
}

# 執行整合測試
run_integration_tests() {
    echo -e "${BLUE}執行整合測試...${NC}"
    
    local cmd="docker-compose exec app php artisan test --testsuite='Role Management Integration'"
    
    if [ "$COVERAGE" = true ]; then
        cmd="$cmd --coverage --coverage-html=coverage/integration --coverage-clover=coverage/integration-clover.xml"
    fi
    
    if [ "$VERBOSE" = true ]; then
        cmd="$cmd --verbose"
    fi
    
    if [ "$PARALLEL" = true ]; then
        cmd="$cmd --parallel"
    fi
    
    eval $cmd
}

# 執行效能測試
run_performance_tests() {
    echo -e "${BLUE}執行效能測試...${NC}"
    
    local cmd="docker-compose exec app php artisan test --testsuite='Role Management Performance'"
    
    if [ "$VERBOSE" = true ]; then
        cmd="$cmd --verbose"
    fi
    
    # 效能測試通常不並行執行
    eval $cmd
}

# 執行瀏覽器測試
run_browser_tests() {
    echo -e "${BLUE}執行瀏覽器測試...${NC}"
    
    # 檢查 Chrome Driver
    if [ ! -f "vendor/laravel/dusk/bin/chromedriver-linux" ]; then
        echo -e "${YELLOW}安裝 Chrome Driver...${NC}"
        docker-compose exec app php artisan dusk:chrome-driver
    fi
    
    # 啟動應用程式伺服器
    echo -e "${YELLOW}啟動應用程式伺服器...${NC}"
    docker-compose exec -d app php artisan serve --host=0.0.0.0 --port=8000
    
    # 等待伺服器啟動
    sleep 5
    
    local cmd="docker-compose exec app php artisan dusk --testsuite='Role Management Browser'"
    
    if [ "$VERBOSE" = true ]; then
        cmd="$cmd --verbose"
    fi
    
    eval $cmd
}

# 執行安全性測試
run_security_tests() {
    echo -e "${BLUE}執行安全性測試...${NC}"
    
    # 安全性檢查
    docker-compose exec app composer audit
    
    # 靜態分析
    if [ -f "phpstan.neon" ]; then
        docker-compose exec app vendor/bin/phpstan analyse app/Livewire/Admin/Roles app/Services app/Repositories
    fi
}

# 生成測試報告
generate_reports() {
    echo -e "${BLUE}生成測試報告...${NC}"
    
    # 建立報告目錄
    mkdir -p reports
    
    # 複製覆蓋率報告
    if [ "$COVERAGE" = true ] && [ -d "coverage" ]; then
        echo -e "${GREEN}覆蓋率報告已生成: coverage/integration/index.html${NC}"
    fi
    
    # 生成測試摘要
    cat > reports/test-summary.md << EOF
# 角色管理整合測試報告

## 測試執行時間
- 開始時間: $(date -d "@$START_TIME" '+%Y-%m-%d %H:%M:%S')
- 結束時間: $(date '+%Y-%m-%d %H:%M:%S')
- 執行時長: $(($(date +%s) - START_TIME)) 秒

## 測試類型
- 測試類型: $TEST_TYPE
- 並行執行: $PARALLEL
- 生成覆蓋率: $COVERAGE
- 詳細輸出: $VERBOSE

## 測試環境
- 環境: $ENVIRONMENT
- PHP 版本: $(php -v | head -n1)
- Laravel 版本: $(php artisan --version)

## 檔案位置
- 覆蓋率報告: coverage/integration/index.html
- 測試日誌: storage/logs/laravel.log
EOF

    echo -e "${GREEN}測試報告已生成: reports/test-summary.md${NC}"
}

# 清理測試資料
cleanup() {
    if [ "$CLEANUP" = true ]; then
        echo -e "${BLUE}清理測試資料...${NC}"
        
        # 清除測試資料庫
        php artisan migrate:fresh --force
        
        # 清除快取
        php artisan cache:clear
        
        # 清除測試檔案
        rm -rf tests/Browser/screenshots/*
        rm -rf tests/Browser/console/*
        
        echo -e "${GREEN}清理完成${NC}"
    fi
}

# 主要執行流程
main() {
    START_TIME=$(date +%s)
    
    echo -e "${GREEN}開始執行角色管理整合測試${NC}"
    echo -e "${BLUE}測試類型: $TEST_TYPE${NC}"
    
    # 檢查 Docker
    check_docker
    
    # 設定環境
    setup_environment
    
    # 準備資料庫
    prepare_database
    
    # 根據測試類型執行測試
    case $TEST_TYPE in
        "integration")
            run_integration_tests
            ;;
        "performance")
            run_performance_tests
            ;;
        "browser")
            run_browser_tests
            ;;
        "security")
            run_security_tests
            ;;
        "all")
            run_integration_tests
            run_performance_tests
            run_browser_tests
            run_security_tests
            ;;
        *)
            echo -e "${RED}錯誤: 未知的測試類型 '$TEST_TYPE'${NC}"
            show_help
            exit 1
            ;;
    esac
    
    # 生成報告
    generate_reports
    
    # 清理
    cleanup
    
    echo -e "${GREEN}測試執行完成！${NC}"
}

# 錯誤處理
trap 'echo -e "${RED}測試執行失敗！${NC}"; cleanup; exit 1' ERR

# 執行主程式
main