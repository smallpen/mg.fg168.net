#!/bin/bash

# Livewire 表單重置修復部署前檢查腳本
# 專門檢查 Livewire 表單重置相關的部署準備狀態

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

# 檢查 Livewire 元件修復狀態
check_livewire_components() {
    log_info "檢查 Livewire 元件修復狀態..."
    
    local components=(
        "app/Livewire/Admin/Users/UserList.php"
        "app/Livewire/Admin/Activities/ActivityExport.php"
        "app/Livewire/Admin/Permissions/PermissionAuditLog.php"
        "app/Livewire/Admin/Settings/SettingsList.php"
        "app/Livewire/Admin/Notifications/NotificationList.php"
        "app/Livewire/Admin/Permissions/PermissionTemplateManager.php"
        "app/Livewire/Admin/Permissions/PermissionForm.php"
        "app/Livewire/Admin/Users/UserDeleteModal.php"
        "app/Livewire/Admin/Permissions/PermissionDeleteModal.php"
        "app/Livewire/Admin/Settings/RetentionPolicyManager.php"
        "app/Livewire/Admin/Dashboard/PerformanceMonitor.php"
        "app/Livewire/Admin/Dashboard/SystemMonitor.php"
        "app/Livewire/Admin/Activities/RecentActivity.php"
        "app/Livewire/Admin/Settings/SettingChangeHistory.php"
    )
    
    local missing_components=()
    local fixed_components=0
    
    for component in "${components[@]}"; do
        if [ ! -f "$component" ]; then
            missing_components+=("$component")
        else
            # 檢查是否包含修復標記
            if grep -q "wire:model\.defer\|dispatch.*refresh\|livewire-form-reset-fix" "$component" 2>/dev/null; then
                ((fixed_components++))
            fi
        fi
    done
    
    if [ ${#missing_components[@]} -gt 0 ]; then
        log_error "缺少以下 Livewire 元件："
        for component in "${missing_components[@]}"; do
            echo "  - $component"
        done
        return 1
    fi
    
    log_success "找到 ${#components[@]} 個 Livewire 元件，其中 $fixed_components 個已包含修復"
    
    if [ $fixed_components -lt $((${#components[@]} / 2)) ]; then
        log_warning "修復的元件數量較少，請確認修復是否完整"
    fi
}

# 檢查視圖檔案修復狀態
check_view_files() {
    log_info "檢查視圖檔案修復狀態..."
    
    local view_files=(
        "resources/views/livewire/admin/users/user-list.blade.php"
        "resources/views/livewire/admin/activities/activity-export.blade.php"
        "resources/views/livewire/admin/permissions/permission-audit-log.blade.php"
        "resources/views/livewire/admin/settings/settings-list.blade.php"
        "resources/views/livewire/admin/notifications/notification-list.blade.php"
        "resources/views/livewire/admin/permissions/permission-template-manager.blade.php"
        "resources/views/livewire/admin/permissions/permission-form.blade.php"
        "resources/views/livewire/admin/users/user-delete-modal.blade.php"
        "resources/views/livewire/admin/permissions/permission-delete-modal.blade.php"
        "resources/views/livewire/admin/settings/retention-policy-manager.blade.php"
        "resources/views/livewire/admin/dashboard/performance-monitor.blade.php"
        "resources/views/livewire/admin/dashboard/system-monitor.blade.php"
        "resources/views/livewire/admin/activities/recent-activity.blade.php"
        "resources/views/livewire/admin/settings/setting-change-history.blade.php"
    )
    
    local missing_views=()
    local fixed_views=0
    
    for view in "${view_files[@]}"; do
        if [ ! -f "$view" ]; then
            missing_views+=("$view")
        else
            # 檢查是否包含修復標記
            if grep -q "wire:model\.defer\|wire:key\|livewire.*reset.*event" "$view" 2>/dev/null; then
                ((fixed_views++))
            fi
        fi
    done
    
    if [ ${#missing_views[@]} -gt 0 ]; then
        log_warning "缺少以下視圖檔案（可能使用內聯視圖）："
        for view in "${missing_views[@]}"; do
            echo "  - $view"
        done
    fi
    
    log_success "檢查了 $((${#view_files[@]} - ${#missing_views[@]})) 個視圖檔案，其中 $fixed_views 個已包含修復"
}

# 檢查測試檔案
check_test_files() {
    log_info "檢查 Livewire 表單重置測試檔案..."
    
    local test_files=(
        "tests/Feature/Livewire/FormResetTest.php"
        "tests/Browser/LivewireFormResetTest.php"
        "tests/Unit/Livewire/FormResetFixTest.php"
        "tests/Performance/LivewireFormResetPerformanceTest.php"
    )
    
    local existing_tests=0
    
    for test in "${test_files[@]}"; do
        if [ -f "$test" ]; then
            ((existing_tests++))
        fi
    done
    
    if [ $existing_tests -eq 0 ]; then
        log_warning "沒有找到 Livewire 表單重置專用測試檔案"
    else
        log_success "找到 $existing_tests 個 Livewire 表單重置測試檔案"
    fi
    
    # 檢查是否有一般的 Livewire 測試
    local general_livewire_tests=$(find tests/ -name "*Livewire*Test.php" 2>/dev/null | wc -l)
    if [ $general_livewire_tests -gt 0 ]; then
        log_success "找到 $general_livewire_tests 個一般 Livewire 測試檔案"
    fi
}

# 檢查修復工具類別
check_fix_tools() {
    log_info "檢查修復工具類別..."
    
    local tool_files=(
        "app/Services/LivewireFormReset/ComponentScanner.php"
        "app/Services/LivewireFormReset/IssueIdentifier.php"
        "app/Services/LivewireFormReset/FixExecutor.php"
        "app/Services/LivewireFormReset/BatchProcessor.php"
        "app/Services/LivewireFormReset/ProgressMonitor.php"
        "app/Services/LivewireFormReset/FormResetTestSuite.php"
        "app/Services/LivewireFormReset/PerformanceTestSuite.php"
    )
    
    local existing_tools=0
    
    for tool in "${tool_files[@]}"; do
        if [ -f "$tool" ]; then
            ((existing_tools++))
        fi
    done
    
    if [ $existing_tools -eq 0 ]; then
        log_warning "沒有找到修復工具類別，可能使用不同的實作方式"
    else
        log_success "找到 $existing_tools 個修復工具類別"
    fi
}

# 檢查文檔檔案
check_documentation() {
    log_info "檢查相關文檔檔案..."
    
    local doc_files=(
        "docs/livewire-form-reset-best-practices.md"
        "docs/livewire-form-reset-deployment-guide.md"
        "docs/livewire-troubleshooting-guide.md"
        "docs/livewire-development-standards.md"
    )
    
    local existing_docs=0
    
    for doc in "${doc_files[@]}"; do
        if [ -f "$doc" ]; then
            ((existing_docs++))
        fi
    done
    
    if [ $existing_docs -eq 0 ]; then
        log_warning "沒有找到 Livewire 表單重置相關文檔"
    else
        log_success "找到 $existing_docs 個相關文檔檔案"
    fi
}

# 檢查 Livewire 配置
check_livewire_config() {
    log_info "檢查 Livewire 配置..."
    
    if [ ! -f "config/livewire.php" ]; then
        log_error "找不到 Livewire 配置檔案"
        return 1
    fi
    
    # 檢查重要配置項目
    local config_checks=(
        "class_namespace.*App.*Livewire"
        "view_path.*livewire"
        "asset_url"
        "middleware_group"
    )
    
    local config_issues=0
    
    for check in "${config_checks[@]}"; do
        if ! grep -q "$check" config/livewire.php; then
            ((config_issues++))
        fi
    done
    
    if [ $config_issues -gt 0 ]; then
        log_warning "Livewire 配置可能需要檢查，發現 $config_issues 個潛在問題"
    else
        log_success "Livewire 配置檢查通過"
    fi
}

# 檢查 JavaScript 依賴
check_javascript_dependencies() {
    log_info "檢查 JavaScript 依賴..."
    
    if [ ! -f "package.json" ]; then
        log_warning "找不到 package.json 檔案"
        return
    fi
    
    # 檢查 Alpine.js 和相關依賴
    local js_deps=(
        "alpinejs"
        "@alpinejs/focus"
        "@alpinejs/persist"
    )
    
    local missing_deps=()
    
    for dep in "${js_deps[@]}"; do
        if ! grep -q "\"$dep\"" package.json; then
            missing_deps+=("$dep")
        fi
    done
    
    if [ ${#missing_deps[@]} -gt 0 ]; then
        log_warning "缺少以下 JavaScript 依賴："
        for dep in "${missing_deps[@]}"; do
            echo "  - $dep"
        done
    else
        log_success "JavaScript 依賴檢查通過"
    fi
    
    # 檢查編譯的資源
    if [ -d "public/build" ] && [ "$(ls -A public/build 2>/dev/null)" ]; then
        log_success "找到編譯的前端資源"
    else
        log_warning "沒有找到編譯的前端資源，可能需要執行 npm run build"
    fi
}

# 檢查資料庫遷移
check_database_migrations() {
    log_info "檢查資料庫遷移..."
    
    # 檢查是否有相關的遷移檔案
    local migration_files=$(find database/migrations/ -name "*livewire*" -o -name "*form_reset*" 2>/dev/null | wc -l)
    
    if [ $migration_files -gt 0 ]; then
        log_success "找到 $migration_files 個相關的遷移檔案"
    else
        log_info "沒有找到專用的遷移檔案（可能不需要資料庫變更）"
    fi
    
    # 檢查遷移狀態（如果 Docker 容器正在運行）
    if docker-compose ps app | grep -q "Up"; then
        log_info "檢查遷移狀態..."
        if docker-compose exec -T app php artisan migrate:status > /dev/null 2>&1; then
            log_success "資料庫遷移狀態正常"
        else
            log_warning "無法檢查資料庫遷移狀態"
        fi
    fi
}

# 檢查快取狀態
check_cache_status() {
    log_info "檢查快取狀態..."
    
    local cache_files=(
        "bootstrap/cache/packages.php"
        "bootstrap/cache/services.php"
        "bootstrap/cache/config.php"
        "bootstrap/cache/routes-v7.php"
    )
    
    local cached_files=0
    
    for cache_file in "${cache_files[@]}"; do
        if [ -f "$cache_file" ]; then
            ((cached_files++))
        fi
    done
    
    if [ $cached_files -gt 0 ]; then
        log_warning "發現 $cached_files 個快取檔案，部署時將會清除"
        
        # 特別檢查套件發現快取
        if [ -f "bootstrap/cache/packages.php" ]; then
            if grep -q "DuskServiceProvider\|TestCase" "bootstrap/cache/packages.php" 2>/dev/null; then
                log_warning "套件發現快取包含開發套件，將在部署時清除"
            fi
        fi
    else
        log_success "沒有舊的快取檔案"
    fi
}

# 執行 Livewire 特定檢查
run_livewire_checks() {
    log_info "執行 Livewire 特定檢查..."
    
    # 如果 Docker 容器正在運行，執行更詳細的檢查
    if docker-compose ps app | grep -q "Up"; then
        log_info "檢查 Livewire 元件發現..."
        if docker-compose exec -T app php artisan livewire:discover > /dev/null 2>&1; then
            log_success "Livewire 元件發現正常"
        else
            log_warning "Livewire 元件發現可能有問題"
        fi
        
        log_info "檢查 Livewire 配置..."
        if docker-compose exec -T app php artisan config:show livewire > /dev/null 2>&1; then
            log_success "Livewire 配置可以正常載入"
        else
            log_warning "Livewire 配置載入可能有問題"
        fi
    else
        log_info "Docker 容器未運行，跳過動態檢查"
    fi
}

# 主要檢查函數
main() {
    echo "🔍 Livewire 表單重置修復部署前檢查"
    echo "========================================"
    echo ""
    
    local checks=(
        "check_livewire_components"
        "check_view_files"
        "check_test_files"
        "check_fix_tools"
        "check_documentation"
        "check_livewire_config"
        "check_javascript_dependencies"
        "check_database_migrations"
        "check_cache_status"
        "run_livewire_checks"
    )
    
    local failed_checks=()
    local warning_checks=()
    
    for check in "${checks[@]}"; do
        if ! $check; then
            failed_checks+=("$check")
        fi
    done
    
    echo ""
    echo "========================================"
    
    if [ ${#failed_checks[@]} -eq 0 ]; then
        log_success "🎉 所有 Livewire 表單重置檢查都通過！"
        echo ""
        log_info "建議的後續步驟："
        echo "  1. 執行一般系統檢查: ./scripts/pre-deploy-check.sh"
        echo "  2. 執行修復驗證: ./scripts/livewire-form-reset-verification.sh"
        echo "  3. 執行部署: ./scripts/deploy-livewire-fixes.sh [environment]"
        exit 0
    else
        log_error "❌ 發現 ${#failed_checks[@]} 個問題需要解決："
        for check in "${failed_checks[@]}"; do
            echo "  - $check"
        done
        echo ""
        log_error "請解決上述問題後再進行部署。"
        exit 1
    fi
}

# 執行主函數
main "$@"