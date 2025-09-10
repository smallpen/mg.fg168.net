#!/bin/bash

# 通路管理資料庫遷移腳本
# 版本: 1.0.0
# 用途: 安全地執行通路管理相關的資料庫遷移

set -e

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

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

# 檢查資料庫連線
check_database_connection() {
    log_info "檢查資料庫連線..."
    
    if ! docker-compose exec -T app php artisan tinker --execute="DB::connection()->getPdo();" >/dev/null 2>&1; then
        log_error "無法連接資料庫，請檢查資料庫服務狀態"
        exit 1
    fi
    
    log_success "資料庫連線正常"
}

# 備份資料庫
backup_database() {
    log_info "備份資料庫..."
    
    BACKUP_DIR="backups/migration-$(date +%Y%m%d_%H%M%S)"
    mkdir -p $BACKUP_DIR
    
    # 完整資料庫備份
    docker-compose exec -T db mysqldump \
        -u root -p$MYSQL_ROOT_PASSWORD \
        --single-transaction \
        --routines \
        --triggers \
        --complete-insert \
        laravel_admin > $BACKUP_DIR/full_backup.sql
    
    # 只備份結構（用於快速恢復）
    docker-compose exec -T db mysqldump \
        -u root -p$MYSQL_ROOT_PASSWORD \
        --no-data \
        --routines \
        --triggers \
        laravel_admin > $BACKUP_DIR/schema_backup.sql
    
    log_success "資料庫備份完成: $BACKUP_DIR"
    echo "BACKUP_DIR=$BACKUP_DIR" > /tmp/migration_backup_path
}

# 檢查遷移狀態
check_migration_status() {
    log_info "檢查遷移狀態..."
    
    # 顯示當前遷移狀態
    docker-compose exec app php artisan migrate:status
    
    # 檢查是否有待執行的遷移
    PENDING_COUNT=$(docker-compose exec -T app php artisan migrate:status | grep -c "Ran?" || echo "0")
    
    if [ "$PENDING_COUNT" -gt 0 ]; then
        log_warning "發現 $PENDING_COUNT 個待執行的遷移"
        return 0
    else
        log_info "所有遷移都已執行"
        return 1
    fi
}

# 執行遷移（乾跑模式）
dry_run_migrations() {
    log_info "執行遷移乾跑檢查..."
    
    # 檢查遷移檔案語法
    docker-compose exec app php artisan migrate:status --pretend
    
    log_success "遷移乾跑檢查通過"
}

# 執行實際遷移
run_migrations() {
    log_info "執行資料庫遷移..."
    
    # 設定遷移超時時間
    docker-compose exec app php artisan migrate --force --timeout=300
    
    log_success "資料庫遷移完成"
}

# 驗證遷移結果
verify_migrations() {
    log_info "驗證遷移結果..."
    
    # 檢查關鍵資料表是否存在
    TABLES_TO_CHECK=("agents" "players" "point_transactions")
    
    for table in "${TABLES_TO_CHECK[@]}"; do
        if docker-compose exec -T db mysql -u root -p$MYSQL_ROOT_PASSWORD laravel_admin -e "DESCRIBE $table;" >/dev/null 2>&1; then
            log_success "✅ 資料表 $table 存在"
        else
            log_error "❌ 資料表 $table 不存在"
            return 1
        fi
    done
    
    # 檢查索引是否正確建立
    log_info "檢查索引..."
    docker-compose exec -T db mysql -u root -p$MYSQL_ROOT_PASSWORD laravel_admin -e "
    SELECT 
        TABLE_NAME,
        INDEX_NAME,
        COLUMN_NAME
    FROM information_schema.statistics 
    WHERE table_schema = 'laravel_admin' 
        AND table_name IN ('agents', 'players', 'point_transactions')
    ORDER BY TABLE_NAME, INDEX_NAME;
    "
    
    # 檢查外鍵約束
    log_info "檢查外鍵約束..."
    docker-compose exec -T db mysql -u root -p$MYSQL_ROOT_PASSWORD laravel_admin -e "
    SELECT 
        TABLE_NAME,
        COLUMN_NAME,
        CONSTRAINT_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
    FROM information_schema.KEY_COLUMN_USAGE 
    WHERE table_schema = 'laravel_admin' 
        AND REFERENCED_TABLE_NAME IS NOT NULL
        AND TABLE_NAME IN ('agents', 'players', 'point_transactions');
    "
    
    log_success "遷移結果驗證通過"
}

# 執行資料完整性檢查
check_data_integrity() {
    log_info "執行資料完整性檢查..."
    
    # 檢查是否有孤立記錄
    docker-compose exec app php artisan tinker --execute="
    // 檢查孤立的代理記錄
    \$orphanAgents = App\Models\Agent::whereNotNull('parent_id')
        ->whereNotExists(function(\$query) {
            \$query->select(DB::raw(1))
                   ->from('agents as parent')
                   ->whereRaw('parent.id = agents.parent_id');
        })->count();
    
    if (\$orphanAgents > 0) {
        echo \"❌ 發現 \$orphanAgents 個孤立代理記錄\";
    } else {
        echo \"✅ 代理關聯檢查通過\";
    }
    
    // 檢查孤立的玩家記錄
    \$orphanPlayers = App\Models\Player::whereNotExists(function(\$query) {
        \$query->select(DB::raw(1))
               ->from('agents')
               ->whereRaw('agents.id = players.agent_id');
    })->count();
    
    if (\$orphanPlayers > 0) {
        echo \"❌ 發現 \$orphanPlayers 個孤立玩家記錄\";
    } else {
        echo \"✅ 玩家關聯檢查通過\";
    }
    
    // 檢查點數一致性
    \$agents = App\Models\Agent::all();
    \$pointErrors = 0;
    
    foreach (\$agents as \$agent) {
        \$calculated = \$agent->total_points - \$agent->allocated_points;
        if (abs(\$calculated - \$agent->remaining_points) > 0.01) {
            \$pointErrors++;
        }
    }
    
    if (\$pointErrors > 0) {
        echo \"❌ 發現 \$pointErrors 個代理點數不一致\";
    } else {
        echo \"✅ 點數一致性檢查通過\";
    }
    "
    
    log_success "資料完整性檢查完成"
}

# 回滾遷移（如果需要）
rollback_migrations() {
    log_warning "開始回滾遷移..."
    
    # 讀取備份路徑
    if [ -f "/tmp/migration_backup_path" ]; then
        BACKUP_DIR=$(cat /tmp/migration_backup_path | cut -d'=' -f2)
        
        if [ -f "$BACKUP_DIR/full_backup.sql" ]; then
            log_info "從備份恢復資料庫: $BACKUP_DIR/full_backup.sql"
            
            # 停止應用程式
            docker-compose stop app
            
            # 恢復資料庫
            cat $BACKUP_DIR/full_backup.sql | docker-compose exec -T db mysql -u root -p$MYSQL_ROOT_PASSWORD laravel_admin
            
            # 重啟應用程式
            docker-compose start app
            
            log_success "資料庫回滾完成"
        else
            log_error "找不到備份檔案，無法回滾"
            return 1
        fi
    else
        log_error "找不到備份路徑資訊"
        return 1
    fi
}

# 生成遷移報告
generate_migration_report() {
    log_info "生成遷移報告..."
    
    REPORT_FILE="migration_report_$(date +%Y%m%d_%H%M%S).txt"
    
    cat > $REPORT_FILE << EOF
通路管理資料庫遷移報告
====================

遷移時間: $(date)
遷移版本: $(git rev-parse HEAD 2>/dev/null || echo "未知")

遷移前狀態:
$(cat /tmp/migration_before_status 2>/dev/null || echo "無記錄")

遷移後狀態:
$(docker-compose exec -T app php artisan migrate:status)

資料表統計:
- agents: $(docker-compose exec -T app php artisan tinker --execute="echo App\Models\Agent::count();" 2>/dev/null | tail -1) 筆記錄
- players: $(docker-compose exec -T app php artisan tinker --execute="echo App\Models\Player::count();" 2>/dev/null | tail -1) 筆記錄
- point_transactions: $(docker-compose exec -T app php artisan tinker --execute="echo App\Models\PointTransaction::count();" 2>/dev/null | tail -1) 筆記錄

資料表結構:
$(docker-compose exec -T db mysql -u root -p$MYSQL_ROOT_PASSWORD laravel_admin -e "
SELECT 
    TABLE_NAME as '資料表',
    TABLE_ROWS as '記錄數',
    ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) as '大小(MB)'
FROM information_schema.tables 
WHERE table_schema = 'laravel_admin' 
    AND table_name IN ('agents', 'players', 'point_transactions')
ORDER BY TABLE_NAME;
")

索引資訊:
$(docker-compose exec -T db mysql -u root -p$MYSQL_ROOT_PASSWORD laravel_admin -e "
SELECT 
    TABLE_NAME as '資料表',
    INDEX_NAME as '索引名稱',
    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as '欄位'
FROM information_schema.statistics 
WHERE table_schema = 'laravel_admin' 
    AND table_name IN ('agents', 'players', 'point_transactions')
GROUP BY TABLE_NAME, INDEX_NAME
ORDER BY TABLE_NAME, INDEX_NAME;
")

遷移狀態: 成功 ✅
EOF
    
    log_success "遷移報告已生成: $REPORT_FILE"
}

# 主要遷移流程
main() {
    log_info "開始通路管理資料庫遷移..."
    log_info "遷移時間: $(date)"
    
    # 記錄遷移前狀態
    docker-compose exec -T app php artisan migrate:status > /tmp/migration_before_status
    
    # 執行遷移步驟
    check_database_connection
    backup_database
    
    if check_migration_status; then
        dry_run_migrations
        
        # 詢問是否繼續
        if [ "${1:-}" != "--force" ]; then
            read -p "是否繼續執行遷移？(y/N): " -n 1 -r
            echo
            if [[ ! $REPLY =~ ^[Yy]$ ]]; then
                log_info "遷移已取消"
                exit 0
            fi
        fi
        
        run_migrations
        verify_migrations
        check_data_integrity
        generate_migration_report
        
        log_success "🎉 通路管理資料庫遷移完成！"
    else
        log_info "沒有待執行的遷移，跳過遷移步驟"
        generate_migration_report
    fi
}

# 錯誤處理
handle_error() {
    log_error "遷移過程中發生錯誤"
    
    if [ "${1:-}" == "--auto-rollback" ]; then
        log_warning "自動回滾已啟用，開始回滾..."
        rollback_migrations
    else
        log_info "如需回滾，請執行: $0 --rollback"
    fi
    
    exit 1
}

# 處理命令列參數
case "${1:-}" in
    --rollback)
        log_info "執行遷移回滾..."
        rollback_migrations
        exit 0
        ;;
    --force)
        log_info "強制執行模式"
        ;;
    --auto-rollback)
        log_info "自動回滾模式"
        trap 'handle_error --auto-rollback' ERR
        ;;
    --help)
        echo "用法: $0 [選項]"
        echo "選項:"
        echo "  --force         強制執行，不詢問確認"
        echo "  --rollback      回滾到遷移前狀態"
        echo "  --auto-rollback 發生錯誤時自動回滾"
        echo "  --help          顯示此幫助訊息"
        exit 0
        ;;
    *)
        trap 'handle_error' ERR
        ;;
esac

# 執行主流程
main "$@"