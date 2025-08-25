# 活動記錄功能整合測試

本目錄包含活動記錄功能的完整整合測試套件，涵蓋所有需求的測試場景。

## 測試概述

整合測試分為四個主要類別：

### 1. 基本功能整合測試 (`ActivityLogIntegrationTest.php`)
- **完整活動記錄流程測試**：測試從使用者登入到各種操作的完整記錄流程
- **安全事件檢測和警報**：測試登入失敗、可疑 IP、批量操作等安全事件的檢測
- **權限控制測試**：測試不同權限使用者的存取控制和資料過濾
- **資料完整性測試**：測試數位簽章、敏感資料過濾、完整性驗證
- **匯出和備份功能**：測試各種格式的資料匯出和備份功能
- **保留政策測試**：測試活動記錄的自動清理和保留政策

### 2. 瀏覽器自動化測試 (`ActivityLogBrowserTest.php`)
- **活動記錄列表頁面流程**：使用 Playwright 測試完整的 UI 操作流程
- **即時監控功能**：測試即時活動監控和警報顯示
- **權限控制 UI**：測試不同權限使用者看到的 UI 元素差異
- **統計圖表功能**：測試活動統計和圖表的互動功能
- **響應式設計**：測試行動裝置和平板的顯示效果
- **效能和載入時間**：測試頁面載入效能和使用者體驗

### 3. 效能和負載測試 (`ActivityLogPerformanceTest.php`)
- **大量資料寫入效能**：測試批量和非同步記錄的效能表現
- **查詢效能測試**：測試各種查詢場景的響應時間
- **搜尋功能效能**：測試全文搜尋和複雜篩選的效能
- **統計查詢效能**：測試統計資料計算的效能
- **安全分析效能**：測試風險分析和異常檢測的效能
- **快取效能測試**：測試快取機制的效能提升效果
- **記憶體使用測試**：測試大量資料處理時的記憶體使用情況
- **並發存取測試**：測試多使用者同時存取的效能表現

### 4. MCP 整合測試 (`ActivityLogMcpIntegrationTest.php`)
- **Playwright + MySQL MCP 整合**：使用 MCP 工具進行端到端測試
- **完整流程驗證**：結合瀏覽器操作和資料庫驗證
- **安全事件流程**：測試安全事件的觸發、檢測和處理流程
- **權限控制驗證**：使用 MCP 工具驗證權限控制的正確性
- **效能要求驗證**：使用 MCP 工具測試效能要求
- **資料完整性驗證**：結合 UI 操作和資料庫檢查驗證資料完整性

## 測試執行

### 快速執行所有測試
```bash
php tests/Integration/run-activity-log-integration-tests.php
```

### 執行特定測試類別
```bash
# 基本功能測試
docker-compose exec app php artisan test tests/Integration/ActivityLogIntegrationTest.php

# 瀏覽器自動化測試
docker-compose exec app php artisan test tests/Integration/ActivityLogBrowserTest.php

# 效能測試
docker-compose exec app php artisan test tests/Integration/ActivityLogPerformanceTest.php

# MCP 整合測試
docker-compose exec app php artisan test tests/Integration/ActivityLogMcpIntegrationTest.php
```

### 執行特定測試方法
```bash
docker-compose exec app php artisan test tests/Integration/ActivityLogIntegrationTest.php::test_complete_activity_logging_flow
```

## 測試環境準備

### 1. 資料庫準備
```bash
# 重建測試資料庫
docker-compose exec app php artisan migrate:fresh --seed --env=testing

# 確保測試資料完整
docker-compose exec app php artisan db:seed --class=TestDataSeeder --env=testing
```

### 2. MCP 服務準備
確保以下 MCP 服務正在運行：
- **Playwright MCP Server**：用於瀏覽器自動化測試
- **MySQL MCP Server**：用於資料庫操作和驗證

### 3. 測試配置
測試配置定義在 `ActivityLogTestConfig.php` 中，包含：
- 效能閾值設定
- 測試資料大小配置
- 安全測試參數
- MCP 服務配置
- 瀏覽器測試設定

## 效能要求

測試會驗證以下效能要求：

### 寫入效能
- 批量寫入 10,000 筆記錄應在 30 秒內完成
- 非同步記錄 5,000 筆應在 5 秒內完成
- 單筆記錄寫入應在 100 毫秒內完成

### 查詢效能
- 基本查詢應在 1 秒內完成
- 複雜查詢應在 2 秒內完成
- 搜尋查詢應在 2 秒內完成
- 統計查詢應在 3 秒內完成

### 頁面載入效能
- 頁面載入應在 3 秒內完成
- 搜尋響應應在 1 秒內完成
- 分頁載入應在 0.5 秒內完成

### 記憶體使用
- 最大記憶體使用量不超過 100 MB
- 查詢記憶體使用不超過 50 MB

## 安全測試

### 登入安全
- 測試多次登入失敗的檢測和警報
- 測試可疑 IP 位址的識別
- 測試異常活動模式的檢測

### 資料安全
- 測試敏感資料的過濾和遮蔽
- 測試數位簽章的生成和驗證
- 測試資料完整性檢查

### 存取控制
- 測試不同權限使用者的存取限制
- 測試 API 存取權限控制
- 測試 UI 元素的權限控制

## 測試報告

### 報告格式
測試執行後會生成以下格式的報告：
- **JSON 格式**：詳細的測試結果和效能數據
- **HTML 格式**：可視化的測試報告
- **控制台輸出**：即時的測試進度和結果

### 報告內容
- 測試摘要（通過/失敗數量、成功率）
- 詳細的測試結果
- 效能測試數據
- 錯誤詳情和堆疊追蹤
- 環境資訊

### 報告位置
- 詳細報告：`storage/logs/activity-log-integration-test-report.json`
- 效能報告：`storage/logs/performance-test-results.json`
- 錯誤日誌：`storage/logs/laravel.log`

## 故障排除

### 常見問題

#### 1. 測試資料不存在
```bash
# 解決方案：重新建立測試資料
docker-compose exec app php artisan migrate:fresh --seed --env=testing
```

#### 2. MCP 服務無法連接
```bash
# 檢查 Playwright MCP 服務
curl -s http://localhost:3000/health

# 檢查 MySQL MCP 服務
mysql -h localhost -u root -e "SELECT 1"
```

#### 3. 效能測試失敗
- 檢查系統資源使用情況
- 調整測試配置中的效能閾值
- 確保測試環境沒有其他高負載程序

#### 4. 權限測試失敗
```bash
# 檢查權限資料是否正確
docker-compose exec app php artisan tinker --execute="
echo 'Users: ' . User::count();
echo 'Roles: ' . Role::count();
echo 'Permissions: ' . Permission::count();
"
```

### 測試環境要求

#### 硬體要求
- **記憶體**：至少 4GB RAM
- **儲存空間**：至少 2GB 可用空間
- **CPU**：至少 2 核心

#### 軟體要求
- **PHP**：8.1 或更高版本
- **MySQL**：8.0 或更高版本
- **Node.js**：16 或更高版本（用於 Playwright）
- **Docker**：20.10 或更高版本

## 測試最佳實踐

### 1. 測試隔離
- 每個測試都使用獨立的測試資料
- 測試後自動清理資料
- 避免測試間的相互影響

### 2. 資料驗證
- 測試前後都要驗證資料狀態
- 使用 MCP 工具進行跨系統驗證
- 確保資料完整性和一致性

### 3. 錯誤處理
- 適當處理測試失敗情況
- 提供詳細的錯誤資訊
- 支援測試重試機制

### 4. 效能監控
- 監控測試執行時間
- 記錄資源使用情況
- 設定合理的效能閾值

## 持續整合

### CI/CD 整合
測試可以整合到 CI/CD 流程中：

```yaml
# .github/workflows/integration-tests.yml
name: Activity Log Integration Tests

on: [push, pull_request]

jobs:
  integration-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup Test Environment
        run: |
          docker-compose up -d
          docker-compose exec app php artisan migrate:fresh --seed --env=testing
      
      - name: Run Integration Tests
        run: |
          php tests/Integration/run-activity-log-integration-tests.php
      
      - name: Upload Test Reports
        uses: actions/upload-artifact@v2
        with:
          name: test-reports
          path: storage/logs/test-reports/
```

### 測試排程
可以設定定期執行整合測試：

```bash
# 每日執行完整測試套件
0 2 * * * cd /path/to/project && php tests/Integration/run-activity-log-integration-tests.php

# 每小時執行快速測試
0 * * * * cd /path/to/project && docker-compose exec app php artisan test tests/Integration/ActivityLogIntegrationTest.php::test_complete_activity_logging_flow
```

## 貢獻指南

### 新增測試
1. 在適當的測試類別中新增測試方法
2. 遵循現有的測試命名規範
3. 確保測試具有適當的文檔說明
4. 更新測試配置（如需要）

### 修改測試
1. 確保修改不會影響其他測試
2. 更新相關的測試文檔
3. 驗證修改後的測試仍能正常執行

### 報告問題
1. 提供詳細的錯誤資訊
2. 包含測試環境資訊
3. 提供重現步驟
4. 附上相關的日誌檔案

這個整合測試套件確保活動記錄功能的所有需求都得到充分測試，包括功能性、效能、安全性和使用者體驗等各個方面。