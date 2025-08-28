#!/bin/bash

# Livewire 表單重置監控設定腳本
# 設定持續監控和自動化維護機制

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
    echo "使用方法: $0 [選項]"
    echo ""
    echo "選項:"
    echo "  --install-cron     安裝 cron 任務"
    echo "  --remove-cron      移除 cron 任務"
    echo "  --setup-alerts     設定警報系統"
    echo "  --setup-logs       設定日誌輪轉"
    echo "  --setup-all        設定所有監控功能"
    echo "  --status           顯示監控狀態"
    echo "  --help             顯示此說明"
    echo ""
    echo "範例:"
    echo "  $0 --setup-all"
    echo "  $0 --install-cron"
    echo "  $0 --status"
}

# 建立監控目錄結構
create_monitoring_directories() {
    log_info "建立監控目錄結構..."
    
    local directories=(
        "monitoring/logs"
        "monitoring/reports"
        "monitoring/alerts"
        "monitoring/scripts"
        "health-reports"
        "backups/monitoring"
    )
    
    for dir in "${directories[@]}"; do
        mkdir -p "$dir"
        log_success "建立目錄: $dir"
    done
}

# 建立監控配置檔案
create_monitoring_config() {
    log_info "建立監控配置檔案..."
    
    # 主要監控配置
    cat > "monitoring/config.json" << 'EOF'
{
  "monitoring": {
    "enabled": true,
    "check_interval": 300,
    "environments": ["production", "staging"],
    "alert_threshold": {
      "error_count": 5,
      "response_time": 3.0,
      "disk_usage": 85,
      "memory_usage": 90
    }
  },
  "health_checks": {
    "containers": true,
    "livewire_components": true,
    "database": true,
    "redis": true,
    "application_response": true,
    "error_logs": true,
    "system_resources": true,
    "form_reset_functionality": true
  },
  "alerts": {
    "enabled": true,
    "channels": ["log", "email"],
    "email": {
      "enabled": false,
      "smtp_host": "",
      "smtp_port": 587,
      "username": "",
      "password": "",
      "to": ["admin@example.com"]
    },
    "slack": {
      "enabled": false,
      "webhook_url": ""
    }
  },
  "reports": {
    "daily_summary": true,
    "weekly_report": true,
    "retention_days": 30
  }
}
EOF
    
    log_success "建立監控配置: monitoring/config.json"
    
    # 警報配置
    cat > "monitoring/alert-config.json" << 'EOF'
{
  "alert_rules": [
    {
      "name": "container_unhealthy",
      "condition": "container_health != 'healthy'",
      "severity": "critical",
      "message": "容器健康檢查失敗"
    },
    {
      "name": "database_connection_failed",
      "condition": "database_status != 'connected'",
      "severity": "critical",
      "message": "資料庫連線失敗"
    },
    {
      "name": "high_error_rate",
      "condition": "error_count > 10",
      "severity": "warning",
      "message": "錯誤率過高"
    },
    {
      "name": "slow_response_time",
      "condition": "response_time > 5.0",
      "severity": "warning",
      "message": "回應時間過慢"
    },
    {
      "name": "disk_space_low",
      "condition": "disk_usage > 90",
      "severity": "critical",
      "message": "磁碟空間不足"
    },
    {
      "name": "livewire_component_failed",
      "condition": "livewire_status != 'ok'",
      "severity": "warning",
      "message": "Livewire 元件載入失敗"
    }
  ]
}
EOF
    
    log_success "建立警報配置: monitoring/alert-config.json"
}

# 建立監控腳本
create_monitoring_scripts() {
    log_info "建立監控腳本..."
    
    # 主要監控腳本
    cat > "monitoring/scripts/monitor.sh" << 'EOF'
#!/bin/bash

# 主要監控腳本
# 定期執行健康檢查並處理結果

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"
cd "$PROJECT_DIR"

# 載入配置
CONFIG_FILE="monitoring/config.json"
if [ ! -f "$CONFIG_FILE" ]; then
    echo "錯誤: 找不到配置檔案 $CONFIG_FILE"
    exit 1
fi

# 取得環境參數
ENVIRONMENT=${1:-production}

# 執行健康檢查
echo "$(date): 開始健康檢查 - 環境: $ENVIRONMENT" >> monitoring/logs/monitor.log

if ./scripts/livewire-health-check.sh "$ENVIRONMENT" true; then
    echo "$(date): 健康檢查通過" >> monitoring/logs/monitor.log
else
    echo "$(date): 健康檢查失敗" >> monitoring/logs/monitor.log
    
    # 發送警報
    if [ -f "scripts/send-alert.sh" ]; then
        ./scripts/send-alert.sh "health-check-failed" "Livewire 健康檢查失敗" "$ENVIRONMENT"
    fi
fi

# 清理舊日誌
find monitoring/logs -name "*.log" -mtime +7 -delete 2>/dev/null || true
find health-reports -name "*.json" -mtime +30 -delete 2>/dev/null || true
EOF
    
    chmod +x "monitoring/scripts/monitor.sh"
    log_success "建立監控腳本: monitoring/scripts/monitor.sh"
    
    # 警報發送腳本
    cat > "scripts/send-alert.sh" << 'EOF'
#!/bin/bash

# 警報發送腳本
# 發送各種類型的警報通知

ALERT_TYPE=$1
ALERT_MESSAGE=$2
ALERT_DETAILS=$3

TIMESTAMP=$(date)
LOG_FILE="monitoring/logs/alerts.log"

# 記錄警報
echo "[$TIMESTAMP] $ALERT_TYPE: $ALERT_MESSAGE - $ALERT_DETAILS" >> "$LOG_FILE"

# 發送到標準輸出
echo "🚨 警報: $ALERT_MESSAGE"
echo "類型: $ALERT_TYPE"
echo "詳情: $ALERT_DETAILS"
echo "時間: $TIMESTAMP"

# 如果配置了 email 或其他通知方式，在這裡添加
# 例如: 發送 email、Slack 通知等

# 建立警報檔案供其他系統讀取
ALERT_FILE="monitoring/alerts/alert-$(date +%Y%m%d_%H%M%S).json"
cat > "$ALERT_FILE" << EOL
{
  "timestamp": "$TIMESTAMP",
  "type": "$ALERT_TYPE",
  "message": "$ALERT_MESSAGE",
  "details": "$ALERT_DETAILS",
  "environment": "${ENVIRONMENT:-unknown}",
  "severity": "warning"
}
EOL

# 清理舊警報檔案
find monitoring/alerts -name "*.json" -mtime +7 -delete 2>/dev/null || true
EOF
    
    chmod +x "scripts/send-alert.sh"
    log_success "建立警報腳本: scripts/send-alert.sh"
    
    # 日誌分析腳本
    cat > "monitoring/scripts/analyze-logs.sh" << 'EOF'
#!/bin/bash

# 日誌分析腳本
# 分析應用程式日誌並生成報告

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"
cd "$PROJECT_DIR"

ENVIRONMENT=${1:-production}
COMPOSE_FILE="docker-compose.prod.yml"

if [ "$ENVIRONMENT" = "staging" ]; then
    COMPOSE_FILE="docker-compose.staging.yml"
elif [ "$ENVIRONMENT" = "dev" ]; then
    COMPOSE_FILE="docker-compose.yml"
fi

echo "分析 $ENVIRONMENT 環境日誌..."

# 分析應用程式日誌
if docker-compose -f "$COMPOSE_FILE" exec -T app test -f storage/logs/laravel.log; then
    echo "=== 應用程式錯誤統計 ===" > "monitoring/reports/log-analysis-$(date +%Y%m%d).txt"
    docker-compose -f "$COMPOSE_FILE" exec -T app tail -n 1000 storage/logs/laravel.log | \
        grep -i "error\|exception\|fatal" | \
        awk '{print $1 " " $2}' | sort | uniq -c | sort -nr >> "monitoring/reports/log-analysis-$(date +%Y%m%d).txt"
    
    echo "" >> "monitoring/reports/log-analysis-$(date +%Y%m%d).txt"
    echo "=== Livewire 相關錯誤 ===" >> "monitoring/reports/log-analysis-$(date +%Y%m%d).txt"
    docker-compose -f "$COMPOSE_FILE" exec -T app tail -n 1000 storage/logs/laravel.log | \
        grep -i "livewire" >> "monitoring/reports/log-analysis-$(date +%Y%m%d).txt"
fi

echo "日誌分析完成: monitoring/reports/log-analysis-$(date +%Y%m%d).txt"
EOF
    
    chmod +x "monitoring/scripts/analyze-logs.sh"
    log_success "建立日誌分析腳本: monitoring/scripts/analyze-logs.sh"
}

# 設定 Cron 任務
setup_cron_jobs() {
    log_info "設定 Cron 任務..."
    
    local cron_file="/tmp/livewire-monitoring-cron"
    local project_path=$(pwd)
    
    # 建立 cron 任務
    cat > "$cron_file" << EOF
# Livewire 表單重置監控任務
# 每 5 分鐘執行健康檢查
*/5 * * * * cd $project_path && ./monitoring/scripts/monitor.sh production >> monitoring/logs/cron.log 2>&1

# 每小時分析日誌
0 * * * * cd $project_path && ./monitoring/scripts/analyze-logs.sh production >> monitoring/logs/cron.log 2>&1

# 每日生成健康報告摘要
0 6 * * * cd $project_path && ./scripts/generate-daily-health-summary.sh >> monitoring/logs/cron.log 2>&1

# 每週清理舊檔案
0 2 * * 0 cd $project_path && find monitoring/logs -name "*.log" -mtime +30 -delete && find health-reports -name "*.json" -mtime +60 -delete
EOF
    
    # 安裝 cron 任務
    if crontab -l > /dev/null 2>&1; then
        # 備份現有 crontab
        crontab -l > "/tmp/crontab-backup-$(date +%Y%m%d_%H%M%S)"
        
        # 移除舊的 Livewire 監控任務
        crontab -l | grep -v "Livewire 表單重置監控任務" | grep -v "livewire-monitoring" > "/tmp/current-cron"
        
        # 添加新任務
        cat "/tmp/current-cron" "$cron_file" | crontab -
    else
        # 直接安裝新任務
        crontab "$cron_file"
    fi
    
    rm -f "$cron_file"
    log_success "Cron 任務已安裝"
    
    # 顯示安裝的任務
    log_info "已安裝的監控任務："
    crontab -l | grep -A 10 "Livewire 表單重置監控任務"
}

# 移除 Cron 任務
remove_cron_jobs() {
    log_info "移除 Cron 任務..."
    
    if crontab -l > /dev/null 2>&1; then
        # 備份現有 crontab
        crontab -l > "/tmp/crontab-backup-$(date +%Y%m%d_%H%M%S)"
        
        # 移除 Livewire 監控任務
        crontab -l | grep -v "Livewire 表單重置監控任務" | \
                     grep -v "monitor.sh" | \
                     grep -v "analyze-logs.sh" | \
                     grep -v "generate-daily-health-summary.sh" | \
                     crontab -
        
        log_success "Cron 任務已移除"
    else
        log_warning "沒有找到現有的 crontab"
    fi
}

# 設定日誌輪轉
setup_log_rotation() {
    log_info "設定日誌輪轉..."
    
    # 建立 logrotate 配置
    local logrotate_config="/tmp/livewire-monitoring-logrotate"
    
    cat > "$logrotate_config" << EOF
$(pwd)/monitoring/logs/*.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    create 644 $(whoami) $(whoami)
    postrotate
        # 重新啟動相關服務（如果需要）
    endscript
}

$(pwd)/health-reports/*.json {
    weekly
    rotate 12
    compress
    delaycompress
    missingok
    notifempty
}
EOF
    
    # 如果系統支援 logrotate，安裝配置
    if command -v logrotate > /dev/null; then
        sudo cp "$logrotate_config" "/etc/logrotate.d/livewire-monitoring" 2>/dev/null || {
            log_warning "無法安裝系統級 logrotate 配置，將使用 cron 清理"
        }
    else
        log_warning "系統不支援 logrotate，將使用 cron 清理舊日誌"
    fi
    
    rm -f "$logrotate_config"
    log_success "日誌輪轉設定完成"
}

# 建立每日健康摘要腳本
create_daily_summary_script() {
    log_info "建立每日健康摘要腳本..."
    
    cat > "scripts/generate-daily-health-summary.sh" << 'EOF'
#!/bin/bash

# 每日健康摘要生成腳本

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_DIR"

DATE=$(date +%Y-%m-%d)
SUMMARY_FILE="monitoring/reports/daily-summary-$DATE.md"

echo "# Livewire 表單重置每日健康摘要" > "$SUMMARY_FILE"
echo "日期: $DATE" >> "$SUMMARY_FILE"
echo "" >> "$SUMMARY_FILE"

# 統計健康檢查結果
echo "## 健康檢查統計" >> "$SUMMARY_FILE"
if [ -d "health-reports" ]; then
    local healthy_count=$(find health-reports -name "health-check-$(date +%Y%m%d)*.json" -exec grep -l '"status": "healthy"' {} \; | wc -l)
    local degraded_count=$(find health-reports -name "health-check-$(date +%Y%m%d)*.json" -exec grep -l '"status": "degraded"' {} \; | wc -l)
    local unhealthy_count=$(find health-reports -name "health-check-$(date +%Y%m%d)*.json" -exec grep -l '"status": "unhealthy"' {} \; | wc -l)
    
    echo "- 健康: $healthy_count 次" >> "$SUMMARY_FILE"
    echo "- 降級: $degraded_count 次" >> "$SUMMARY_FILE"
    echo "- 不健康: $unhealthy_count 次" >> "$SUMMARY_FILE"
fi

# 統計警報
echo "" >> "$SUMMARY_FILE"
echo "## 警報統計" >> "$SUMMARY_FILE"
if [ -d "monitoring/alerts" ]; then
    local alert_count=$(find monitoring/alerts -name "alert-$(date +%Y%m%d)*.json" | wc -l)
    echo "- 今日警報: $alert_count 個" >> "$SUMMARY_FILE"
    
    if [ $alert_count -gt 0 ]; then
        echo "" >> "$SUMMARY_FILE"
        echo "### 警報詳情" >> "$SUMMARY_FILE"
        find monitoring/alerts -name "alert-$(date +%Y%m%d)*.json" -exec cat {} \; | \
            jq -r '"\(.timestamp): \(.message)"' >> "$SUMMARY_FILE" 2>/dev/null || true
    fi
fi

# 系統狀態
echo "" >> "$SUMMARY_FILE"
echo "## 系統狀態" >> "$SUMMARY_FILE"
echo "- Git 提交: $(git rev-parse --short HEAD 2>/dev/null || echo 'unknown')" >> "$SUMMARY_FILE"
echo "- 磁碟使用: $(df . | tail -1 | awk '{print $5}')" >> "$SUMMARY_FILE"

echo "每日摘要已生成: $SUMMARY_FILE"
EOF
    
    chmod +x "scripts/generate-daily-health-summary.sh"
    log_success "建立每日摘要腳本: scripts/generate-daily-health-summary.sh"
}

# 顯示監控狀態
show_monitoring_status() {
    echo "📊 Livewire 監控系統狀態"
    echo "========================"
    echo ""
    
    # 檢查目錄結構
    log_info "目錄結構:"
    local directories=("monitoring" "health-reports" "monitoring/logs" "monitoring/reports" "monitoring/alerts")
    for dir in "${directories[@]}"; do
        if [ -d "$dir" ]; then
            echo "  ✅ $dir"
        else
            echo "  ❌ $dir (缺失)"
        fi
    done
    
    echo ""
    
    # 檢查配置檔案
    log_info "配置檔案:"
    local configs=("monitoring/config.json" "monitoring/alert-config.json")
    for config in "${configs[@]}"; do
        if [ -f "$config" ]; then
            echo "  ✅ $config"
        else
            echo "  ❌ $config (缺失)"
        fi
    done
    
    echo ""
    
    # 檢查腳本
    log_info "監控腳本:"
    local scripts=("monitoring/scripts/monitor.sh" "scripts/send-alert.sh" "scripts/livewire-health-check.sh")
    for script in "${scripts[@]}"; do
        if [ -f "$script" ] && [ -x "$script" ]; then
            echo "  ✅ $script"
        else
            echo "  ❌ $script (缺失或無執行權限)"
        fi
    done
    
    echo ""
    
    # 檢查 Cron 任務
    log_info "Cron 任務:"
    if crontab -l 2>/dev/null | grep -q "monitor.sh"; then
        echo "  ✅ 監控任務已安裝"
        local cron_count=$(crontab -l 2>/dev/null | grep -c "monitor.sh\|analyze-logs.sh\|generate-daily-health-summary.sh")
        echo "  📊 共 $cron_count 個相關任務"
    else
        echo "  ❌ 監控任務未安裝"
    fi
    
    echo ""
    
    # 檢查最近的健康檢查
    log_info "最近的健康檢查:"
    if [ -d "health-reports" ]; then
        local latest_report=$(find health-reports -name "health-check-*.json" -type f -printf '%T@ %p\n' 2>/dev/null | sort -n | tail -1 | cut -d' ' -f2-)
        if [ -n "$latest_report" ]; then
            local report_time=$(stat -c %y "$latest_report" 2>/dev/null | cut -d. -f1)
            local report_status=$(grep '"status"' "$latest_report" 2>/dev/null | cut -d'"' -f4)
            echo "  📅 最後檢查: $report_time"
            echo "  📊 狀態: $report_status"
        else
            echo "  ❌ 沒有找到健康檢查報告"
        fi
    else
        echo "  ❌ 健康檢查報告目錄不存在"
    fi
    
    echo ""
    
    # 檢查警報
    log_info "警報統計:"
    if [ -d "monitoring/alerts" ]; then
        local today_alerts=$(find monitoring/alerts -name "alert-$(date +%Y%m%d)*.json" 2>/dev/null | wc -l)
        local week_alerts=$(find monitoring/alerts -name "alert-*.json" -mtime -7 2>/dev/null | wc -l)
        echo "  📊 今日警報: $today_alerts 個"
        echo "  📊 本週警報: $week_alerts 個"
    else
        echo "  ❌ 警報目錄不存在"
    fi
}

# 主要執行邏輯
main() {
    local action=""
    
    # 解析參數
    while [[ $# -gt 0 ]]; do
        case $1 in
            --install-cron)
                action="install-cron"
                shift
                ;;
            --remove-cron)
                action="remove-cron"
                shift
                ;;
            --setup-alerts)
                action="setup-alerts"
                shift
                ;;
            --setup-logs)
                action="setup-logs"
                shift
                ;;
            --setup-all)
                action="setup-all"
                shift
                ;;
            --status)
                action="status"
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
    
    # 檢查動作參數
    if [ -z "$action" ]; then
        log_error "請指定動作"
        show_usage
        exit 1
    fi
    
    echo "🔧 Livewire 監控系統設定"
    echo "======================="
    echo ""
    
    case $action in
        "setup-all")
            create_monitoring_directories
            create_monitoring_config
            create_monitoring_scripts
            create_daily_summary_script
            setup_cron_jobs
            setup_log_rotation
            log_success "🎉 監控系統設定完成！"
            ;;
        "install-cron")
            setup_cron_jobs
            ;;
        "remove-cron")
            remove_cron_jobs
            ;;
        "setup-alerts")
            create_monitoring_directories
            create_monitoring_config
            create_monitoring_scripts
            log_success "警報系統設定完成"
            ;;
        "setup-logs")
            setup_log_rotation
            ;;
        "status")
            show_monitoring_status
            ;;
    esac
}

# 執行主函數
main "$@"