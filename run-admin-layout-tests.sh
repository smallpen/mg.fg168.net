#!/bin/bash

# 管理後台佈局和導航系統整合測試執行腳本
# 
# 此腳本用於執行所有相關的整合測試，確保系統的完整性和一致性

set -e

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 函數：輸出帶顏色的訊息
print_message() {
    local color=$1
    local message=$2
    echo -e "${color}${message}${NC}"
}

# 函數：輸出標題
print_header() {
    echo
    echo "╔══════════════════════════════════════════════════════════════════════════════╗"
    echo "║                    管理後台佈局和導航系統 - 整合測試套件                      ║"
    echo "╠══════════════════════════════════════════════════════════════════════════════╣"
    echo "║ 測試範圍:                                                                    ║"
    echo "║ • 完整佈局導航流程測試                                                        ║"
    echo "║ • 響應式設計在不同裝置的表現                                                  ║"
    echo "║ • 主題切換和多語言功能                                                        ║"
    echo "║ • 鍵盤導航和無障礙功能                                                        ║"
    echo "║ • 瀏覽器自動化測試                                                            ║"
    echo "║                                                                              ║"
    echo "║ 執行時間: $(date '+%Y-%m-%d %H:%M:%S')                                                    ║"
    echo "╚══════════════════════════════════════════════════════════════════════════════╝"
    echo
}

# 函數：檢查 Docker 環境
check_docker_environment() {
    print_message $BLUE "🔍 檢查 Docker 環境..."
    
    if ! command -v docker-compose &> /dev/null; then
        print_message $RED "❌ Docker Compose 未安裝或不在 PATH 中"
        exit 1
    fi
    
    if ! docker-compose ps | grep -q "app.*Up"; then
        print_message $YELLOW "⚠️  Docker 容器未運行，正在啟動..."
        docker-compose up -d
        sleep 10
    fi
    
    print_message $GREEN "✅ Docker 環境檢查完成"
}

# 函數：準備測試環境
prepare_test_environment() {
    print_message $BLUE "🛠️  準備測試環境..."
    
    # 清除快取
    docker-compose exec app php artisan cache:clear
    docker-compose exec app php artisan config:clear
    docker-compose exec app php artisan view:clear
    
    # 執行資料庫遷移
    docker-compose exec app php artisan migrate:fresh --seed --env=testing
    
    # 建立測試報告目錄
    docker-compose exec app mkdir -p storage/app/test-reports
    
    print_message $GREEN "✅ 測試環境準備完成"
}

# 函數：執行單元測試
run_unit_tests() {
    print_message $BLUE "🧪 執行單元測試..."
    
    local unit_tests=(
        "tests/Unit/AdminLayoutUnitTest.php"
        "tests/Unit/NavigationServiceTest.php"
        "tests/Unit/ThemeToggleUnitTest.php"
    )
    
    for test in "${unit_tests[@]}"; do
        if [ -f "$test" ]; then
            print_message $YELLOW "  執行: $test"
            if docker-compose exec app php artisan test "$test" --stop-on-failure; then
                print_message $GREEN "  ✅ $test 通過"
            else
                print_message $RED "  ❌ $test 失敗"
                return 1
            fi
        fi
    done
    
    print_message $GREEN "✅ 單元測試完成"
}

# 函數：執行功能測試
run_feature_tests() {
    print_message $BLUE "🔧 執行功能測試..."
    
    local feature_tests=(
        "tests/Feature/AccessibilityTest.php"
        "tests/Feature/AccessibilityIntegrationTest.php"
        "tests/Feature/KeyboardShortcutTest.php"
        "tests/Feature/ThemeToggleTest.php"
        "tests/Feature/MultiLanguageSupportTest.php"
        "tests/Feature/NotificationCenterTest.php"
        "tests/Feature/GlobalSearchTest.php"
        "tests/Feature/LoadingStateManagementTest.php"
    )
    
    for test in "${feature_tests[@]}"; do
        if [ -f "$test" ]; then
            print_message $YELLOW "  執行: $test"
            if docker-compose exec app php artisan test "$test" --stop-on-failure; then
                print_message $GREEN "  ✅ $test 通過"
            else
                print_message $RED "  ❌ $test 失敗"
                return 1
            fi
        fi
    done
    
    print_message $GREEN "✅ 功能測試完成"
}

# 函數：執行瀏覽器測試
run_browser_tests() {
    print_message $BLUE "🌐 執行瀏覽器測試..."
    
    # 檢查 Chrome 是否可用
    if ! docker-compose exec app which google-chrome &> /dev/null; then
        print_message $YELLOW "⚠️  正在安裝 Chrome..."
        docker-compose exec app apt-get update
        docker-compose exec app apt-get install -y google-chrome-stable
    fi
    
    local browser_tests=(
        "AdminLayoutNavigationIntegrationTest"
        "ResponsiveDesignTest"
        "ThemeAndLanguageTest"
        "KeyboardNavigationIntegrationTest"
        "AdminDashboardTest"
    )
    
    for test in "${browser_tests[@]}"; do
        print_message $YELLOW "  執行: $test"
        if docker-compose exec app php artisan dusk --filter="$test"; then
            print_message $GREEN "  ✅ $test 通過"
        else
            print_message $RED "  ❌ $test 失敗"
            
            # 顯示螢幕截圖路徑（如果存在）
            screenshot_path="tests/Browser/screenshots"
            if [ -d "$screenshot_path" ]; then
                print_message $YELLOW "  📸 螢幕截圖已儲存至: $screenshot_path"
            fi
            
            return 1
        fi
    done
    
    print_message $GREEN "✅ 瀏覽器測試完成"
}

# 函數：執行完整測試套件
run_complete_test_suite() {
    print_message $BLUE "🎯 執行完整測試套件..."
    
    if docker-compose exec app php artisan dusk --filter="AdminLayoutNavigationTestSuite"; then
        print_message $GREEN "✅ 完整測試套件通過"
    else
        print_message $RED "❌ 完整測試套件失敗"
        return 1
    fi
}

# 函數：生成測試報告
generate_test_report() {
    print_message $BLUE "📊 生成測試報告..."
    
    # 執行 PHP 測試報告生成器
    if docker-compose exec app php tests/Browser/run-integration-tests.php; then
        print_message $GREEN "✅ 測試報告生成完成"
        
        # 複製報告到主機
        docker-compose cp app:/var/www/html/storage/app/test-reports ./test-reports
        
        print_message $BLUE "📁 測試報告已複製到: ./test-reports/"
        print_message $BLUE "🌐 HTML 報告: ./test-reports/integration-test-report.html"
        print_message $BLUE "📄 JSON 報告: ./test-reports/integration-test-report.json"
    else
        print_message $YELLOW "⚠️  測試報告生成失敗，但測試可能已完成"
    fi
}

# 函數：清理測試環境
cleanup_test_environment() {
    print_message $BLUE "🧹 清理測試環境..."
    
    # 清除測試資料
    docker-compose exec app php artisan migrate:fresh --env=testing
    
    # 清除快取
    docker-compose exec app php artisan cache:clear
    docker-compose exec app php artisan config:clear
    
    print_message $GREEN "✅ 測試環境清理完成"
}

# 函數：顯示使用說明
show_usage() {
    echo "使用方法: $0 [選項]"
    echo
    echo "選項:"
    echo "  --unit          只執行單元測試"
    echo "  --feature       只執行功能測試"
    echo "  --browser       只執行瀏覽器測試"
    echo "  --suite         只執行完整測試套件"
    echo "  --report        只生成測試報告"
    echo "  --cleanup       只清理測試環境"
    echo "  --help          顯示此說明"
    echo
    echo "範例:"
    echo "  $0                    # 執行所有測試"
    echo "  $0 --browser          # 只執行瀏覽器測試"
    echo "  $0 --unit --feature   # 執行單元測試和功能測試"
}

# 主函數
main() {
    local run_unit=false
    local run_feature=false
    local run_browser=false
    local run_suite=false
    local run_report=false
    local run_cleanup=false
    local run_all=true
    
    # 解析命令列參數
    while [[ $# -gt 0 ]]; do
        case $1 in
            --unit)
                run_unit=true
                run_all=false
                shift
                ;;
            --feature)
                run_feature=true
                run_all=false
                shift
                ;;
            --browser)
                run_browser=true
                run_all=false
                shift
                ;;
            --suite)
                run_suite=true
                run_all=false
                shift
                ;;
            --report)
                run_report=true
                run_all=false
                shift
                ;;
            --cleanup)
                run_cleanup=true
                run_all=false
                shift
                ;;
            --help)
                show_usage
                exit 0
                ;;
            *)
                print_message $RED "未知選項: $1"
                show_usage
                exit 1
                ;;
        esac
    done
    
    # 顯示標題
    print_header
    
    # 記錄開始時間
    start_time=$(date +%s)
    
    # 檢查 Docker 環境
    check_docker_environment
    
    # 準備測試環境
    prepare_test_environment
    
    # 執行測試
    if [ "$run_all" = true ]; then
        run_unit_tests
        run_feature_tests
        run_browser_tests
        run_complete_test_suite
        generate_test_report
    else
        [ "$run_unit" = true ] && run_unit_tests
        [ "$run_feature" = true ] && run_feature_tests
        [ "$run_browser" = true ] && run_browser_tests
        [ "$run_suite" = true ] && run_complete_test_suite
        [ "$run_report" = true ] && generate_test_report
        [ "$run_cleanup" = true ] && cleanup_test_environment
    fi
    
    # 計算執行時間
    end_time=$(date +%s)
    execution_time=$((end_time - start_time))
    
    # 顯示完成訊息
    echo
    echo "╔══════════════════════════════════════════════════════════════════════════════╗"
    echo "║                              測試執行完成                                    ║"
    echo "╠══════════════════════════════════════════════════════════════════════════════╣"
    echo "║ 總執行時間: ${execution_time} 秒                                                        ║"
    echo "║ 完成時間: $(date '+%Y-%m-%d %H:%M:%S')                                                    ║"
    echo "╚══════════════════════════════════════════════════════════════════════════════╝"
    
    print_message $GREEN "🎉 所有測試已完成！"
}

# 執行主函數
main "$@"