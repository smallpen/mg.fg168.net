# Livewire 表單重置維護指南

## 概述

本指南提供 Livewire 表單重置修復的持續維護和監控機制，確保修復效果的長期穩定性和系統的持續健康運行。

## 監控系統架構

### 監控組件

1. **健康檢查系統**
   - 定期執行系統健康檢查
   - 監控 Livewire 元件狀態
   - 檢查表單重置功能完整性
   - 追蹤系統效能指標

2. **警報系統**
   - 自動檢測問題並發送警報
   - 支援多種通知管道
   - 可配置的警報規則
   - 警報歷史記錄

3. **維護系統**
   - 自動化維護任務
   - 問題修復機制
   - 系統優化功能
   - 完整性檢查

4. **報告系統**
   - 每日健康摘要
   - 週期性狀態報告
   - 維護活動記錄
   - 趨勢分析

## 安裝和設定

### 1. 初始設定

執行監控系統設定：

```bash
# 設定完整的監控系統
./scripts/livewire-monitoring-setup.sh --setup-all

# 或分別設定各個組件
./scripts/livewire-monitoring-setup.sh --setup-alerts
./scripts/livewire-monitoring-setup.sh --install-cron
./scripts/livewire-monitoring-setup.sh --setup-logs
```

### 2. 驗證安裝

檢查監控系統狀態：

```bash
# 檢查監控系統狀態
./scripts/livewire-monitoring-setup.sh --status

# 執行手動健康檢查
./scripts/livewire-health-check.sh production
```

### 3. 配置調整

編輯監控配置檔案：

```bash
# 編輯主要配置
vim monitoring/config.json

# 編輯警報配置
vim monitoring/alert-config.json
```

## 監控功能

### 健康檢查

健康檢查系統會定期檢查以下項目：

#### 容器健康狀態
- Docker 容器運行狀態
- 容器健康檢查結果
- 資源使用情況

#### Livewire 元件狀態
- 元件發現和載入
- 關鍵元件可用性
- 元件配置正確性

#### 資料庫連線
- 連線可用性
- 查詢效能
- 資料完整性

#### 應用程式回應
- 關鍵頁面可存取性
- 回應時間監控
- HTTP 狀態碼檢查

#### 表單重置功能
- 修復標記存在性
- wire:model.defer 使用情況
- dispatch refresh 機制

### 執行健康檢查

```bash
# 執行完整健康檢查
./scripts/livewire-health-check.sh production

# 靜默模式（用於自動化）
./scripts/livewire-health-check.sh production true

# 檢查特定環境
./scripts/livewire-health-check.sh staging
```

### 健康檢查結果

健康檢查會產生以下狀態：

- **healthy**: 所有檢查都通過
- **degraded**: 有警告但系統可運行
- **unhealthy**: 發現嚴重問題需要處理

## 警報系統

### 警報規則

系統預設包含以下警報規則：

1. **容器不健康**
   - 觸發條件：容器健康檢查失敗
   - 嚴重程度：嚴重
   - 處理建議：檢查容器日誌並重啟

2. **資料庫連線失敗**
   - 觸發條件：無法連接資料庫
   - 嚴重程度：嚴重
   - 處理建議：檢查資料庫服務狀態

3. **高錯誤率**
   - 觸發條件：錯誤數量超過閾值
   - 嚴重程度：警告
   - 處理建議：檢查應用程式日誌

4. **回應時間過慢**
   - 觸發條件：回應時間超過 5 秒
   - 嚴重程度：警告
   - 處理建議：檢查系統效能

5. **磁碟空間不足**
   - 觸發條件：磁碟使用率超過 90%
   - 嚴重程度：嚴重
   - 處理建議：清理舊檔案或擴展儲存

### 警報通知

警報可以透過以下管道發送：

- **日誌記錄**: 記錄到警報日誌檔案
- **電子郵件**: 發送到指定郵箱（需配置）
- **Slack**: 發送到 Slack 頻道（需配置）
- **檔案系統**: 建立警報檔案供其他系統讀取

### 配置警報

編輯 `monitoring/config.json` 設定警報：

```json
{
  "alerts": {
    "enabled": true,
    "channels": ["log", "email"],
    "email": {
      "enabled": true,
      "smtp_host": "smtp.example.com",
      "smtp_port": 587,
      "username": "alerts@example.com",
      "password": "your_password",
      "to": ["admin@example.com", "dev@example.com"]
    },
    "slack": {
      "enabled": true,
      "webhook_url": "https://hooks.slack.com/services/..."
    }
  }
}
```

## 維護系統

### 自動化維護

系統提供以下維護功能：

#### 快取清理
```bash
# 清理所有快取
./scripts/livewire-maintenance.sh production --clean-cache
```

#### 權限修復
```bash
# 修復檔案權限
./scripts/livewire-maintenance.sh production --fix-permissions
```

#### 系統優化
```bash
# 執行系統優化
./scripts/livewire-maintenance.sh production --optimize
```

#### 完整性檢查
```bash
# 檢查系統完整性
./scripts/livewire-maintenance.sh production --check-integrity
```

#### 系統修復
```bash
# 執行自動修復
./scripts/livewire-maintenance.sh production --repair
```

#### 完整維護
```bash
# 執行完整維護（包含所有步驟）
./scripts/livewire-maintenance.sh production --full-maintenance
```

### 維護排程

系統會自動執行以下維護任務：

- **每 5 分鐘**: 健康檢查
- **每小時**: 日誌分析
- **每日**: 系統優化和清理
- **每週**: 完整維護和報告生成

### 維護報告

每次維護都會生成詳細報告：

```
monitoring/reports/maintenance-report-YYYYMMDD_HHMMSS.md
```

報告包含：
- 維護步驟執行結果
- 系統狀態摘要
- 發現的問題和建議
- 效能指標變化

## 報告系統

### 每日健康摘要

系統每日自動生成健康摘要：

```bash
# 手動生成每日摘要
./scripts/generate-daily-health-summary.sh
```

摘要內容包括：
- 健康檢查統計
- 警報統計
- 系統狀態指標
- 趨勢分析

### 健康檢查報告

每次健康檢查都會生成 JSON 格式報告：

```json
{
  "timestamp": "2024-01-15T10:30:00Z",
  "environment": "production",
  "status": "healthy",
  "git_commit": "abc123",
  "issues": [],
  "warnings": [],
  "checks": {
    "containers": "4 healthy",
    "database": "connected",
    "redis": "connected",
    "disk_usage": "65%",
    "response_time": "0.8s"
  }
}
```

### 報告存取

報告檔案位置：
- 健康檢查報告: `health-reports/`
- 維護報告: `monitoring/reports/`
- 每日摘要: `monitoring/reports/daily-summary-*.md`
- 警報記錄: `monitoring/alerts/`

## 問題排除

### 常見問題和解決方案

#### 1. 健康檢查失敗

**症狀**: 健康檢查返回 unhealthy 狀態

**排除步驟**:
```bash
# 檢查詳細健康檢查結果
./scripts/livewire-health-check.sh production

# 檢查容器狀態
docker-compose -f docker-compose.prod.yml ps

# 檢查應用程式日誌
docker-compose -f docker-compose.prod.yml logs app --tail=50

# 執行系統修復
./scripts/livewire-maintenance.sh production --repair
```

#### 2. Livewire 元件載入失敗

**症狀**: Livewire 元件發現失敗或載入錯誤

**排除步驟**:
```bash
# 清理快取並重新發現元件
./scripts/livewire-maintenance.sh production --clean-cache

# 檢查元件檔案完整性
./scripts/livewire-maintenance.sh production --check-integrity

# 手動重新發現元件
docker-compose -f docker-compose.prod.yml exec app php artisan livewire:discover
```

#### 3. 資料庫連線問題

**症狀**: 資料庫連線檢查失敗

**排除步驟**:
```bash
# 檢查資料庫容器狀態
docker-compose -f docker-compose.prod.yml ps mysql

# 檢查資料庫日誌
docker-compose -f docker-compose.prod.yml logs mysql --tail=50

# 測試資料庫連線
docker-compose -f docker-compose.prod.yml exec app php artisan tinker --execute="DB::connection()->getPdo(); echo 'OK';"

# 重啟資料庫服務
docker-compose -f docker-compose.prod.yml restart mysql
```

#### 4. 高錯誤率警報

**症狀**: 系統產生大量錯誤警報

**排除步驟**:
```bash
# 分析錯誤日誌
./monitoring/scripts/analyze-logs.sh production

# 檢查最近的錯誤
docker-compose -f docker-compose.prod.yml exec app tail -n 100 storage/logs/laravel.log | grep -i error

# 執行完整維護
./scripts/livewire-maintenance.sh production --full-maintenance
```

#### 5. 效能問題

**症狀**: 回應時間過慢或資源使用率過高

**排除步驟**:
```bash
# 檢查系統資源使用
docker stats --no-stream

# 執行系統優化
./scripts/livewire-maintenance.sh production --optimize

# 檢查磁碟空間
df -h

# 清理舊檔案
find . -name "*.log" -mtime +30 -delete
```

### 緊急回應程序

#### 嚴重問題回應

1. **立即評估**
   - 檢查系統可用性
   - 確認問題範圍
   - 評估業務影響

2. **緊急修復**
   ```bash
   # 執行緊急修復
   ./scripts/livewire-maintenance.sh production --repair
   
   # 如果修復失敗，考慮回滾
   ./scripts/deploy-livewire-fixes.sh production --rollback
   ```

3. **通知相關人員**
   - 技術團隊
   - 業務負責人
   - 使用者（如需要）

4. **記錄和分析**
   - 記錄問題詳情
   - 分析根本原因
   - 制定預防措施

## 效能監控

### 關鍵指標

系統監控以下效能指標：

1. **回應時間**
   - 頁面載入時間
   - API 回應時間
   - 資料庫查詢時間

2. **資源使用**
   - CPU 使用率
   - 記憶體使用率
   - 磁碟使用率
   - 網路流量

3. **錯誤率**
   - HTTP 錯誤數量
   - 應用程式異常
   - 資料庫錯誤

4. **可用性**
   - 服務正常運行時間
   - 健康檢查通過率
   - 功能可用性

### 效能基準

建議的效能基準：

- **頁面回應時間**: < 2 秒
- **API 回應時間**: < 1 秒
- **資料庫查詢時間**: < 100ms
- **CPU 使用率**: < 80%
- **記憶體使用率**: < 85%
- **磁碟使用率**: < 80%
- **錯誤率**: < 1%
- **可用性**: > 99.9%

### 效能優化建議

1. **定期清理**
   - 清理舊日誌檔案
   - 清理臨時檔案
   - 清理未使用的快取

2. **資料庫優化**
   - 定期執行 OPTIMIZE TABLE
   - 檢查和優化查詢
   - 監控慢查詢

3. **快取策略**
   - 適當使用 Redis 快取
   - 配置 OPcache
   - 使用 Laravel 快取

4. **資源管理**
   - 監控容器資源使用
   - 適時擴展資源
   - 優化 Docker 映像

## 最佳實踐

### 監控最佳實踐

1. **定期檢查**
   - 每日檢查健康摘要
   - 每週檢查維護報告
   - 每月檢查趨勢分析

2. **警報管理**
   - 設定合理的警報閾值
   - 避免警報疲勞
   - 定期檢查警報規則

3. **文檔維護**
   - 保持文檔更新
   - 記錄問題和解決方案
   - 分享經驗和知識

4. **團隊協作**
   - 建立清晰的責任分工
   - 定期進行監控演練
   - 培訓團隊成員

### 維護最佳實踐

1. **預防性維護**
   - 定期執行系統優化
   - 主動識別潛在問題
   - 保持系統更新

2. **變更管理**
   - 記錄所有維護活動
   - 測試維護程序
   - 準備回滾計劃

3. **容量規劃**
   - 監控資源使用趨勢
   - 預測未來需求
   - 及時擴展資源

4. **災難恢復**
   - 定期備份重要資料
   - 測試恢復程序
   - 制定應急計劃

## 總結

Livewire 表單重置維護系統提供了完整的監控、警報、維護和報告功能，確保修復效果的長期穩定性。透過自動化監控和維護，可以：

1. **提前發現問題**: 透過持續監控及早發現潛在問題
2. **自動化修復**: 透過自動化維護減少手動干預
3. **提高可靠性**: 透過預防性維護提高系統穩定性
4. **優化效能**: 透過定期優化保持系統效能
5. **降低維護成本**: 透過自動化減少維護工作量

遵循本指南的建議和最佳實踐，可以確保 Livewire 表單重置修復的長期成功和系統的持續健康運行。