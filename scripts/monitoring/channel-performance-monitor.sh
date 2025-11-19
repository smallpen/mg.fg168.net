#!/bin/bash

# 通路管理系統效能監控腳本
# 版本: 1.0.0
# 用途: 監控通路管理系統的效能指標

set -e

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'

# 配置
MONITOR_LOG_DIR="storage/logs/monitoring"
PERFORMANCE_LOG_FILE="$MONITOR_LOG_DIR/performance_$(date +%Y%m%d).log"
ALERT_LOG_FILE="$MONITOR_LOG_DIR/alerts_$(date +%Y%m%d).log"
METRICS_FILE="$MONITOR_LOG_DIR/metrics_$(date +%Y%m%d_%H%M%S).json"

# 閾值設定
CPU_THRESHOLD=80
MEMORY_THRESHOLD=85
DISK_THRESHOLD=90
DB_CONNECTION_THRESHOLD=80
RESPONSE_TIME_THRESHOLD=2000
SLOW_QUERY_THRESHOLD=1000

# 日誌函數
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] [INFO] $1" >> $PERFORMANCE_LOG_FILE
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] [WARNING] $1" >> $PERFORMANCE_LOG_FILE
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] [WARNING] $1" >> $ALERT_LOG_FILE
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] [ERROR] $1" >> $PERFORMANCE_LOG_FILE
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] [ERROR] $1" >> $ALERT_LOG_FILE
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] [SUCCESS] $1" >> $PERFORMANCE_LOG_FILE
}

log_metric() {
    echo -e "${CYAN}[METRIC]${NC} $1"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] [METRIC] $1" >> $PERFORMANCE_LOG_FILE
}

# 初始化監控目錄
init_monitoring() {
    mkdir -p $MONITOR_LOG_DIR
    
    if [ ! -f "$PERFORMANCE_LOG_FILE" ]; then
        echo "# 通路管理系統效能監控日誌" > $PERFORMANCE_LOG_FILE
        echo "# 開始時間: $(date)" >> $PERFORMANCE_LOG_FILE
        echo "" >> $PERFORMANCE_LOG_FILE
    fi
}

# 檢查系統資源
check_system_resources() {
    log_info "檢查系統資源使用情況..."
    
    # CPU 使用率
    CPU_USAGE=$(docker-compose exec -T app top -bn1 | grep "Cpu(s)" | awk '{print $2}' | sed 's/%us,//')
    CPU_USAGE_NUM=$(echo $CPU_USAGE | sed 's/%//')
    
    if (( $(echo "$CPU_USAGE_NUM > $CPU_THRESHOLD" | bc -l) )); then
        log_warning "CPU 使用率過高: $CPU_USAGE (閾值: $CPU_THRESHOLD%)"
    else
        log_metric "CPU 使用率: $CPU_USAGE"
    fi
    
    # 記憶體使用率
    MEMORY_INFO=$(docker-compose exec -T app free | awk 'NR==2{printf "%.0f %.0f %.0f", $3*100/$2, $3, $2}')
    MEMORY_USAGE=$(echo $MEMORY_INFO | awk '{print $1}')
    MEMORY_USED=$(echo $MEMORY_INFO | awk '{print $2}')
    MEMORY_TOTAL=$(echo $MEMORY_INFO | awk '{print $3}')
    
    if (( $(echo "$MEMORY_USAGE > $MEMORY_THRESHOLD" | bc -l) )); then
        log_warning "記憶體使用率過高: $MEMORY_USAGE% (閾值: $MEMORY_THRESHOLD%)"
    else
        log_metric "記憶體使用率: $MEMORY_USAGE% (使用: ${MEMORY_USED}KB / 總計: ${MEMORY_TOTAL}KB)"
    fi
    
    # 磁碟使用率
    DISK_USAGE=$(docker-compose exec -T app df -h / | awk 'NR==2{print $5}' | sed 's/%//')
    
    if (( DISK_USAGE > DISK_THRESHOLD )); then
        log_warning "磁碟使用率過高: $DISK_USAGE% (閾值: $DISK_THRESHOLD%)"
    else
        log_metric "磁碟使用率: $DISK_USAGE%"
    fi
}

# 檢查資料庫效能
check_database_performance() {
    log_info "檢查資料庫效能..."
    
    # 資料庫連線數
    DB_CONNECTIONS=$(docker-compose exec -T db mysql -u root -p$MYSQL_ROOT_PASSWORD -e "SHOW STATUS LIKE 'Threads_connected';" | grep Threads_connected | awk '{print $2}')
    MAX_CONNECTIONS=$(docker-compose exec -T db mysql -u root -p$MYSQL_ROOT_PASSWORD -e "SHOW VARIABLES LIKE 'max_connections';" | grep max_connections | awk '{print $2}')
    CONNECTION_USAGE=$(echo "scale=2; $DB_CONNECTIONS * 100 / $MAX_CONNECTIONS" | bc)
    
    if (( $(echo "$CONNECTION_USAGE > $DB_CONNECTION_THRESHOLD" | bc -l) )); then
        log_warning "資料庫連線使用率過高: $CONNECTION_USAGE% ($DB_CONNECTIONS/$MAX_CONNECTIONS)"
    else
        log_metric "資料庫連線使用率: $CONNECTION_USAGE% ($DB_CONNECTIONS/$MAX_CONNECTIONS)"
    fi
    
    # 慢查詢統計
    SLOW_QUERIES=$(docker-compose exec -T db mysql -u root -p$MYSQL_ROOT_PASSWORD -e "SHOW STATUS LIKE 'Slow_queries';" | grep Slow_queries | awk '{print $2}')
    log_metric "慢查詢數量: $SLOW_QUERIES"
    
    # 查詢快取命中率
    QCACHE_HITS=$(docker-compose exec -T db mysql -u root -p$MYSQL_ROOT_PASSWORD -e "SHOW STATUS LIKE 'Qcache_hits';" | grep Qcache_hits | awk '{print $2}')
    QCACHE_INSERTS=$(docker-compose exec -T db mysql -u root -p$MYSQL_ROOT_PASSWORD -e "SHOW STATUS LIKE 'Qcache_inserts';" | grep Qcache_inserts | awk '{print $2}')
    
    if [ "$QCACHE_HITS" -gt 0 ] && [ "$QCACHE_INSERTS" -gt 0 ]; then
        CACHE_HIT_RATE=$(echo "scale=2; $QCACHE_HITS * 100 / ($QCACHE_HITS + $QCACHE_INSERTS)" | bc)
        log_metric "查詢快取命中率: $CACHE_HIT_RATE%"
    fi
    
    # 檢查通路管理相關資料表大小
    log_info "檢查通路管理資料表統計..."
    docker-compose exec -T db mysql -u root -p$MYSQL_ROOT_PASSWORD laravel_admin -e "
    SELECT 
        table_name AS '資料表',
        table_rows AS '記錄數',
        ROUND(((data_length + index_length) / 1024 / 1024), 2) AS '大小(MB)',
        ROUND((data_length / 1024 / 1024), 2) AS '資料(MB)',
        ROUND((index_length / 1024 / 1024), 2) AS '索引(MB)'
    FROM information_schema.tables 
    WHERE table_schema = 'laravel_admin' 
        AND table_name IN ('agents', 'players', 'point_transactions')
    ORDER BY (data_length + index_length) DESC;
    " | while read line; do
        log_metric "資料表統計: $line"
    done
}

# 檢查應用程式效能
check_application_performance() {
    log_info "檢查應用程式效能..."
    
    # 檢查 Laravel 佇列狀態
    QUEUE_SIZE=$(docker-compose exec -T app php artisan queue:monitor 2>/dev/null | grep -o '[0-9]\+' | head -1 || echo "0")
    log_metric "佇列任務數量: $QUEUE_SIZE"
    
    # 檢查快取狀態
    CACHE_STATUS=$(docker-compose exec -T app php artisan tinker --execute="
    try {
        Cache::put('monitor_test', 'ok', 60);
        echo Cache::get('monitor_test') === 'ok' ? 'OK' : 'FAIL';
    } catch (Exception \$e) {
        echo 'ERROR: ' . \$e->getMessage();
    }
    " 2>/dev/null | tail -1)
    
    if [ "$CACHE_STATUS" = "OK" ]; then
        log_metric "快取狀態: 正常"
    else
        log_warning "快取狀態異常: $CACHE_STATUS"
    fi
    
    # 檢查 Redis 記憶體使用
    REDIS_MEMORY=$(docker-compose exec -T redis redis-cli info memory | grep used_memory_human | cut -d: -f2 | tr -d '\r')
    log_metric "Redis 記憶體使用: $REDIS_MEMORY"
    
    # 檢查通路管理模型載入時間
    MODEL_LOAD_TIME=$(docker-compose exec -T app php artisan tinker --execute="
    \$start = microtime(true);
    \$agents = App\Models\Agent::take(10)->get();
    \$end = microtime(true);
    echo round((\$end - \$start) * 1000, 2);
    " 2>/dev/null | tail -1)
    
    if (( $(echo "$MODEL_LOAD_TIME > $RESPONSE_TIME_THRESHOLD" | bc -l) )); then
        log_warning "模型載入時間過長: ${MODEL_LOAD_TIME}ms (閾值: ${RESPONSE_TIME_THRESHOLD}ms)"
    else
        log_metric "模型載入時間: ${MODEL_LOAD_TIME}ms"
    fi
}

# 檢查通路管理特定指標
check_channel_management_metrics() {
    log_info "檢查通路管理系統指標..."
    
    # 代理統計
    AGENT_STATS=$(docker-compose exec -T app php artisan tinker --execute="
    \$total = App\Models\Agent::count();
    \$active = App\Models\Agent::where('is_active', true)->count();
    \$levels = App\Models\Agent::distinct()->pluck('level')->sort()->toArray();
    echo json_encode([
        'total' => \$total,
        'active' => \$active,
        'inactive' => \$total - \$active,
        'max_level' => max(\$levels),
        'levels' => count(\$levels)
    ]);
    " 2>/dev/null | tail -1)
    
    log_metric "代理統計: $AGENT_STATS"
    
    # 玩家統計
    PLAYER_STATS=$(docker-compose exec -T app php artisan tinker --execute="
    \$total = App\Models\Player::count();
    \$active = App\Models\Player::where('is_active', true)->count();
    echo json_encode([
        'total' => \$total,
        'active' => \$active,
        'inactive' => \$total - \$active
    ]);
    " 2>/dev/null | tail -1)
    
    log_metric "玩家統計: $PLAYER_STATS"
    
    # 點數統計
    POINT_STATS=$(docker-compose exec -T app php artisan tinker --execute="
    \$totalPoints = App\Models\Agent::sum('total_points') ?? 0;
    \$allocatedPoints = App\Models\Agent::sum('allocated_points') ?? 0;
    \$remainingPoints = App\Models\Agent::sum('remaining_points') ?? 0;
    \$playerPoints = App\Models\Player::sum('points') ?? 0;
    \$transactionCount = App\Models\PointTransaction::count();
    echo json_encode([
        'total_points' => \$totalPoints,
        'allocated_points' => \$allocatedPoints,
        'remaining_points' => \$remainingPoints,
        'player_points' => \$playerPoints,
        'transaction_count' => \$transactionCount
    ]);
    " 2>/dev/null | tail -1)
    
    log_metric "點數統計: $POINT_STATS"
    
    # 今日交易統計
    TODAY_TRANSACTIONS=$(docker-compose exec -T app php artisan tinker --execute="
    \$today = App\Models\PointTransaction::whereDate('created_at', today())->count();
    \$todayAmount = App\Models\PointTransaction::whereDate('created_at', today())->sum('amount') ?? 0;
    echo json_encode([
        'count' => \$today,
        'total_amount' => \$todayAmount
    ]);
    " 2>/dev/null | tail -1)
    
    log_metric "今日交易統計: $TODAY_TRANSACTIONS"
}

# 檢查錯誤和異常
check_errors_and_exceptions() {
    log_info "檢查錯誤和異常..."
    
    # 檢查 Laravel 錯誤日誌
    ERROR_COUNT=$(docker-compose exec -T app grep -c "ERROR" storage/logs/laravel.log 2>/dev/null || echo "0")
    log_metric "Laravel 錯誤數量: $ERROR_COUNT"
    
    # 檢查最近的錯誤
    if [ "$ERROR_COUNT" -gt 0 ]; then
        RECENT_ERRORS=$(docker-compose exec -T app tail -5 storage/logs/laravel.log | grep "ERROR" || echo "無最近錯誤")
        log_warning "最近錯誤: $RECENT_ERRORS"
    fi
    
    # 檢查 Nginx 錯誤
    NGINX_ERRORS=$(docker-compose logs nginx 2>&1 | grep -c "error" || echo "0")
    log_metric "Nginx 錯誤數量: $NGINX_ERRORS"
    
    # 檢查 MySQL 錯誤
    MYSQL_ERRORS=$(docker-compose logs db 2>&1 | grep -c "ERROR" || echo "0")
    log_metric "MySQL 錯誤數量: $MYSQL_ERRORS"
}

# 生成效能報告
generate_performance_report() {
    log_info "生成效能報告..."
    
    REPORT_FILE="storage/logs/monitoring/performance_report_$(date +%Y%m%d_%H%M%S).html"
    
    cat > $REPORT_FILE << EOF
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>通路管理系統效能報告</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { background: #f4f4f4; padding: 20px; border-radius: 5px; }
        .metric { margin: 10px 0; padding: 10px; border-left: 4px solid #007cba; }
        .warning { border-left-color: #ff9800; background: #fff3cd; }
        .error { border-left-color: #dc3545; background: #f8d7da; }
        .success { border-left-color: #28a745; background: #d4edda; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="header">
        <h1>通路管理系統效能報告</h1>
        <p>生成時間: $(date)</p>
        <p>監控週期: $(date -d '1 hour ago') ~ $(date)</p>
    </div>
    
    <h2>系統資源使用情況</h2>
    <div class="metric">
        <strong>CPU 使用率:</strong> $CPU_USAGE<br>
        <strong>記憶體使用率:</strong> $MEMORY_USAGE%<br>
        <strong>磁碟使用率:</strong> $DISK_USAGE%
    </div>
    
    <h2>資料庫效能</h2>
    <div class="metric">
        <strong>連線使用率:</strong> $CONNECTION_USAGE% ($DB_CONNECTIONS/$MAX_CONNECTIONS)<br>
        <strong>慢查詢數量:</strong> $SLOW_QUERIES
    </div>
    
    <h2>應用程式效能</h2>
    <div class="metric">
        <strong>佇列任務數量:</strong> $QUEUE_SIZE<br>
        <strong>快取狀態:</strong> $CACHE_STATUS<br>
        <strong>Redis 記憶體使用:</strong> $REDIS_MEMORY<br>
        <strong>模型載入時間:</strong> ${MODEL_LOAD_TIME}ms
    </div>
    
    <h2>通路管理系統指標</h2>
    <div class="metric">
        <strong>代理統計:</strong> $AGENT_STATS<br>
        <strong>玩家統計:</strong> $PLAYER_STATS<br>
        <strong>點數統計:</strong> $POINT_STATS<br>
        <strong>今日交易:</strong> $TODAY_TRANSACTIONS
    </div>
    
    <h2>錯誤統計</h2>
    <div class="metric">
        <strong>Laravel 錯誤:</strong> $ERROR_COUNT<br>
        <strong>Nginx 錯誤:</strong> $NGINX_ERRORS<br>
        <strong>MySQL 錯誤:</strong> $MYSQL_ERRORS
    </div>
    
    <h2>建議</h2>
    <div class="metric">
EOF

    # 根據監控結果生成建議
    if (( $(echo "$CPU_USAGE_NUM > $CPU_THRESHOLD" | bc -l) )); then
        echo "        <p class=\"warning\">⚠️ CPU 使用率過高，建議檢查是否有異常程序或考慮擴展資源</p>" >> $REPORT_FILE
    fi
    
    if (( $(echo "$MEMORY_USAGE > $MEMORY_THRESHOLD" | bc -l) )); then
        echo "        <p class=\"warning\">⚠️ 記憶體使用率過高，建議優化應用程式或增加記憶體</p>" >> $REPORT_FILE
    fi
    
    if (( DISK_USAGE > DISK_THRESHOLD )); then
        echo "        <p class=\"warning\">⚠️ 磁碟使用率過高，建議清理日誌檔案或擴展儲存空間</p>" >> $REPORT_FILE
    fi
    
    if [ "$ERROR_COUNT" -gt 10 ]; then
        echo "        <p class=\"error\">❌ 錯誤數量過多，建議檢查應用程式日誌</p>" >> $REPORT_FILE
    fi
    
    cat >> $REPORT_FILE << EOF
        <p class="success">✅ 定期監控系統效能，及時發現和解決問題</p>
        <p class="success">✅ 建議設定自動化監控和警報機制</p>
    </div>
    
    <footer style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; color: #666;">
        <p>此報告由通路管理系統效能監控腳本自動生成</p>
    </footer>
</body>
</html>
EOF
    
    log_success "效能報告已生成: $REPORT_FILE"
}

# 生成 JSON 格式的指標資料
generate_metrics_json() {
    log_info "生成 JSON 格式指標資料..."
    
    cat > $METRICS_FILE << EOF
{
    "timestamp": "$(date -Iseconds)",
    "system": {
        "cpu_usage": "$CPU_USAGE",
        "memory_usage": $MEMORY_USAGE,
        "disk_usage": $DISK_USAGE
    },
    "database": {
        "connections": $DB_CONNECTIONS,
        "max_connections": $MAX_CONNECTIONS,
        "connection_usage": $CONNECTION_USAGE,
        "slow_queries": $SLOW_QUERIES
    },
    "application": {
        "queue_size": $QUEUE_SIZE,
        "cache_status": "$CACHE_STATUS",
        "redis_memory": "$REDIS_MEMORY",
        "model_load_time": $MODEL_LOAD_TIME
    },
    "channel_management": {
        "agents": $AGENT_STATS,
        "players": $PLAYER_STATS,
        "points": $POINT_STATS,
        "today_transactions": $TODAY_TRANSACTIONS
    },
    "errors": {
        "laravel_errors": $ERROR_COUNT,
        "nginx_errors": $NGINX_ERRORS,
        "mysql_errors": $MYSQL_ERRORS
    }
}
EOF
    
    log_success "JSON 指標資料已生成: $METRICS_FILE"
}

# 發送警報（如果需要）
send_alerts() {
    if [ -f "$ALERT_LOG_FILE" ] && [ -s "$ALERT_LOG_FILE" ]; then
        log_info "發現警報事件，準備發送通知..."
        
        # 這裡可以整合郵件、Slack 或其他通知系統
        # 例如：
        # mail -s "通路管理系統警報" admin@example.com < $ALERT_LOG_FILE
        
        log_warning "請檢查警報日誌: $ALERT_LOG_FILE"
    fi
}

# 清理舊的監控資料
cleanup_old_data() {
    log_info "清理舊的監控資料..."
    
    # 清理 30 天前的日誌檔案
    find $MONITOR_LOG_DIR -name "*.log" -mtime +30 -delete 2>/dev/null || true
    
    # 清理 7 天前的指標檔案
    find $MONITOR_LOG_DIR -name "metrics_*.json" -mtime +7 -delete 2>/dev/null || true
    
    # 清理 3 天前的 HTML 報告
    find $MONITOR_LOG_DIR -name "performance_report_*.html" -mtime +3 -delete 2>/dev/null || true
    
    log_success "舊資料清理完成"
}

# 主要監控流程
main() {
    log_info "開始通路管理系統效能監控..."
    log_info "監控時間: $(date)"
    
    # 初始化
    init_monitoring
    
    # 執行監控檢查
    check_system_resources
    check_database_performance
    check_application_performance
    check_channel_management_metrics
    check_errors_and_exceptions
    
    # 生成報告
    generate_performance_report
    generate_metrics_json
    
    # 處理警報
    send_alerts
    
    # 清理舊資料
    cleanup_old_data
    
    log_success "🎯 效能監控完成！"
    log_info "監控報告: $REPORT_FILE"
    log_info "指標資料: $METRICS_FILE"
}

# 處理命令列參數
case "${1:-}" in
    --help)
        echo "用法: $0 [選項]"
        echo "選項:"
        echo "  --help          顯示此幫助訊息"
        echo "  --report-only   只生成報告，不執行完整監控"
        echo "  --json-only     只生成 JSON 指標資料"
        echo "  --cleanup       只執行清理作業"
        exit 0
        ;;
    --report-only)
        init_monitoring
        generate_performance_report
        exit 0
        ;;
    --json-only)
        init_monitoring
        check_system_resources
        check_database_performance
        check_application_performance
        check_channel_management_metrics
        generate_metrics_json
        exit 0
        ;;
    --cleanup)
        init_monitoring
        cleanup_old_data
        exit 0
        ;;
esac

# 執行主流程
main "$@"