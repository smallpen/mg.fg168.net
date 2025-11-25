#!/bin/bash

# 通路管理系統資料庫遷移檢查腳本
# 版本: 1.0
# 作者: 系統管理員
# 日期: 2024-12-09

set -e

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

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

log_info "開始檢查通路管理系統資料庫遷移狀態"

# 檢查 Docker 服務狀態
check_docker_services() {
    log_info "檢查 Docker 服務狀態..."
    
    cd "$PROJECT_ROOT"
    
    if ! docker-compose ps | grep -q "Up"; then
        log_error "Docker 服務未運行，請先啟動服務"
        exit 1
    fi
    
    log_success "Docker 服務運行正常"
}

# 檢查資料庫連線
check_database_connection() {
    log_info "檢查資料庫連線..."
    
    if docker-compose exec -T app php artisan migrate:status > /dev/null 2>&1; then
        log_success "資料庫連線正常"
    else
        log_error "資料庫連線失敗"
        exit 1
    fi
}

# 檢查遷移狀態
check_migration_status() {
    log_info "檢查資料庫遷移狀態..."
    
    echo "========================================="
    echo "資料庫遷移狀態："
    echo "========================================="
    
    docker-compose exec -T app php artisan migrate:status
    
    echo ""
    
    # 檢查是否有待執行的遷移
    PENDING_MIGRATIONS=$(docker-compose exec -T app php artisan migrate:status | grep -c "Pending" || echo "0")
    
    if [ "$PENDING_MIGRATIONS" -gt 0 ]; then
        log_warning "發現 $PENDING_MIGRATIONS 個待執行的遷移"
        echo ""
        echo "待執行的遷移："
        docker-compose exec -T app php artisan migrate:status | grep "Pending"
        echo ""
        
        read -p "是否要執行這些遷移？(y/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            log_info "執行待執行的遷移..."
            docker-compose exec -T app php artisan migrate --force
            log_success "遷移執行完成"
        else
            log_info "跳過遷移執行"
        fi
    else
        log_success "所有遷移都已執行"
    fi
}

# 檢查通路管理相關表格
check_channel_tables() {
    log_info "檢查通路管理相關表格..."
    
    echo "========================================="
    echo "通路管理表格狀態："
    echo "========================================="
    
    # 檢查必要的表格是否存在
    REQUIRED_TABLES=("agents" "players" "point_transactions")
    
    for table in "${REQUIRED_TABLES[@]}"; do
        TABLE_EXISTS=$(docker-compose exec -T db mysql -u"$DB_USERNAME" -p"$DB_PASSWORD" -D"$DB_DATABASE" -e "SHOW TABLES LIKE '$table';" | grep -c "$table" || echo "0")
        
        if [ "$TABLE_EXISTS" -eq 1 ]; then
            log_success "表格 '$table' 存在"
            
            # 檢查表格記錄數量
            RECORD_COUNT=$(docker-compose exec -T db mysql -u"$DB_USERNAME" -p"$DB_PASSWORD" -D"$DB_DATABASE" -e "SELECT COUNT(*) FROM $table;" | tail -n 1)
            echo "  - 記錄數量: $RECORD_COUNT"
            
        else
            log_error "表格 '$table' 不存在"
        fi
    done
    
    echo ""
}

# 檢查表格結構
check_table_structure() {
    log_info "檢查表格結構..."
    
    echo "========================================="
    echo "代理表格 (agents) 結構："
    echo "========================================="
    docker-compose exec -T db mysql -u"$DB_USERNAME" -p"$DB_PASSWORD" -D"$DB_DATABASE" -e "DESCRIBE agents;"
    
    echo ""
    echo "========================================="
    echo "玩家表格 (players) 結構："
    echo "========================================="
    docker-compose exec -T db mysql -u"$DB_USERNAME" -p"$DB_PASSWORD" -D"$DB_DATABASE" -e "DESCRIBE players;"
    
    echo ""
    echo "========================================="
    echo "點數交易表格 (point_transactions) 結構："
    echo "========================================="
    docker-compose exec -T db mysql -u"$DB_USERNAME" -p"$DB_PASSWORD" -D"$DB_DATABASE" -e "DESCRIBE point_transactions;"
    
    echo ""
}

# 檢查索引
check_indexes() {
    log_info "檢查表格索引..."
    
    echo "========================================="
    echo "代理表格索引："
    echo "========================================="
    docker-compose exec -T db mysql -u"$DB_USERNAME" -p"$DB_PASSWORD" -D"$DB_DATABASE" -e "SHOW INDEX FROM agents;"
    
    echo ""
    echo "========================================="
    echo "玩家表格索引："
    echo "========================================="
    docker-compose exec -T db mysql -u"$DB_USERNAME" -p"$DB_PASSWORD" -D"$DB_DATABASE" -e "SHOW INDEX FROM players;"
    
    echo ""
    echo "========================================="
    echo "點數交易表格索引："
    echo "========================================="
    docker-compose exec -T db mysql -u"$DB_USERNAME" -p"$DB_PASSWORD" -D"$DB_DATABASE" -e "SHOW INDEX FROM point_transactions;"
    
    echo ""
}

# 檢查外鍵約束
check_foreign_keys() {
    log_info "檢查外鍵約束..."
    
    echo "========================================="
    echo "外鍵約束："
    echo "========================================="
    
    docker-compose exec -T db mysql -u"$DB_USERNAME" -p"$DB_PASSWORD" -D"$DB_DATABASE" -e "
    SELECT 
        TABLE_NAME,
        COLUMN_NAME,
        CONSTRAINT_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE REFERENCED_TABLE_SCHEMA = '$DB_DATABASE'
    AND TABLE_NAME IN ('agents', 'players', 'point_transactions')
    ORDER BY TABLE_NAME, COLUMN_NAME;
    "
    
    echo ""
}

# 檢查資料完整性
check_data_integrity() {
    log_info "檢查資料完整性..."
    
    echo "========================================="
    echo "資料完整性檢查："
    echo "========================================="
    
    # 檢查代理層級一致性
    LEVEL_INCONSISTENCY=$(docker-compose exec -T db mysql -u"$DB_USERNAME" -p"$DB_PASSWORD" -D"$DB_DATABASE" -e "
    SELECT COUNT(*) FROM agents a1 
    JOIN agents a2 ON a1.parent_id = a2.id 
    WHERE a1.level != a2.level + 1;
    " | tail -n 1)
    
    if [ "$LEVEL_INCONSISTENCY" -eq 0 ]; then
        log_success "代理層級一致性檢查通過"
    else
        log_error "發現 $LEVEL_INCONSISTENCY 個代理層級不一致的記錄"
    fi
    
    # 檢查點數一致性
    POINT_INCONSISTENCY=$(docker-compose exec -T db mysql -u"$DB_USERNAME" -p"$DB_PASSWORD" -D"$DB_DATABASE" -e "
    SELECT COUNT(*) FROM agents 
    WHERE total_points != allocated_points + remaining_points;
    " | tail -n 1)
    
    if [ "$POINT_INCONSISTENCY" -eq 0 ]; then
        log_success "代理點數一致性檢查通過"
    else
        log_error "發現 $POINT_INCONSISTENCY 個代理點數不一致的記錄"
    fi
    
    # 檢查孤立記錄
    ORPHAN_AGENTS=$(docker-compose exec -T db mysql -u"$DB_USERNAME" -p"$DB_PASSWORD" -D"$DB_DATABASE" -e "
    SELECT COUNT(*) FROM agents a1 
    WHERE parent_id IS NOT NULL 
    AND NOT EXISTS (SELECT 1 FROM agents a2 WHERE a2.id = a1.parent_id);
    " | tail -n 1)
    
    if [ "$ORPHAN_AGENTS" -eq 0 ]; then
        log_success "代理關聯完整性檢查通過"
    else
        log_error "發現 $ORPHAN_AGENTS 個孤立的代理記錄"
    fi
    
    ORPHAN_PLAYERS=$(docker-compose exec -T db mysql -u"$DB_USERNAME" -p"$DB_PASSWORD" -D"$DB_DATABASE" -e "
    SELECT COUNT(*) FROM players p 
    WHERE NOT EXISTS (SELECT 1 FROM agents a WHERE a.id = p.agent_id);
    " | tail -n 1)
    
    if [ "$ORPHAN_PLAYERS" -eq 0 ]; then
        log_success "玩家關聯完整性檢查通過"
    else
        log_error "發現 $ORPHAN_PLAYERS 個孤立的玩家記錄"
    fi
    
    echo ""
}

# 生成遷移報告
generate_report() {
    log_info "生成遷移檢查報告..."
    
    REPORT_FILE="/tmp/migration-check-report-$(date +%Y%m%d_%H%M%S).txt"
    
    {
        echo "通路管理系統資料庫遷移檢查報告"
        echo "生成時間: $(date '+%Y-%m-%d %H:%M:%S')"
        echo "========================================="
        echo ""
        
        echo "遷移狀態："
        docker-compose exec -T app php artisan migrate:status
        echo ""
        
        echo "表格統計："
        for table in "agents" "players" "point_transactions"; do
            count=$(docker-compose exec -T db mysql -u"$DB_USERNAME" -p"$DB_PASSWORD" -D"$DB_DATABASE" -e "SELECT COUNT(*) FROM $table;" | tail -n 1)
            echo "$table: $count 筆記錄"
        done
        echo ""
        
        echo "資料完整性檢查結果："
        echo "- 代理層級一致性: $([ "$LEVEL_INCONSISTENCY" -eq 0 ] && echo "通過" || echo "失敗")"
        echo "- 代理點數一致性: $([ "$POINT_INCONSISTENCY" -eq 0 ] && echo "通過" || echo "失敗")"
        echo "- 代理關聯完整性: $([ "$ORPHAN_AGENTS" -eq 0 ] && echo "通過" || echo "失敗")"
        echo "- 玩家關聯完整性: $([ "$ORPHAN_PLAYERS" -eq 0 ] && echo "通過" || echo "失敗")"
        
    } > "$REPORT_FILE"
    
    log_success "遷移檢查報告已生成: $REPORT_FILE"
    
    # 顯示報告內容
    echo ""
    echo "========================================="
    echo "檢查報告摘要："
    echo "========================================="
    cat "$REPORT_FILE"
}

# 主要檢查流程
main() {
    log_info "========================================="
    log_info "通路管理系統資料庫遷移檢查開始"
    log_info "========================================="
    
    # 載入環境變數
    if [ -f "$PROJECT_ROOT/.env" ]; then
        source "$PROJECT_ROOT/.env"
    else
        log_error "找不到 .env 檔案"
        exit 1
    fi
    
    check_docker_services
    check_database_connection
    check_migration_status
    check_channel_tables
    
    # 詢問是否要執行詳細檢查
    echo ""
    read -p "是否要執行詳細的表格結構和完整性檢查？(y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        check_table_structure
        check_indexes
        check_foreign_keys
        check_data_integrity
    fi
    
    generate_report
    
    log_success "========================================="
    log_success "資料庫遷移檢查完成！"
    log_success "========================================="
}

# 錯誤處理
trap 'log_error "檢查過程中發生錯誤"' ERR

# 執行主要流程
main "$@"