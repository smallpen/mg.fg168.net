# 系統設定整合測試文檔

## 概述

本目錄包含系統設定功能的完整整合測試套件，涵蓋功能測試、效能測試、瀏覽器自動化測試、MCP 工具整合測試等多個方面。

## 測試覆蓋範圍

### 功能測試覆蓋

- ✅ **完整的設定管理流程**
  - 設定列表顯示和分頁
  - 設定搜尋和篩選
  - 設定編輯和驗證
  - 設定重設功能
  - 設定變更歷史

- ✅ **設定備份和還原功能**
  - 備份建立和命名
  - 備份列表和管理
  - 備份還原和驗證
  - 備份比較功能

- ✅ **設定匯入匯出功能**
  - 設定匯出（JSON 格式）
  - 設定匯入和衝突處理
  - 批量設定操作
  - 匯入預覽和驗證

- ✅ **不同權限使用者的存取控制**
  - 管理員完整權限
  - 編輯者部分權限
  - 檢視者唯讀權限
  - 一般使用者無權限

- ✅ **瀏覽器自動化測試**
  - 端到端使用者流程
  - 響應式設計測試
  - 鍵盤導航測試
  - 無障礙功能測試

## 測試架構

### 測試類別結構

```
tests/Integration/
├── SystemSettingsIntegrationTest.php      # 主要功能整合測試
├── SystemSettingsBrowserTest.php          # Laravel Dusk 瀏覽器測試
├── SystemSettingsPlaywrightTest.php       # Playwright 整合測試框架
├── SystemSettingsMcpTest.php              # MCP 工具整合測試
├── SystemSettingsTestConfig.php           # 測試配置和常數
├── execute-mcp-tests.php                  # MCP 測試執行腳本
├── run-system-settings-tests.php          # 主要測試執行腳本
└── README.md                              # 本文檔
```

### 測試工具整合

1. **Laravel 內建測試框架**
   - PHPUnit 功能測試
   - 資料庫事務和回滾
   - 模型工廠和假資料

2. **Laravel Dusk**
   - 瀏覽器自動化測試
   - JavaScript 互動測試
   - 截圖和錄影功能

3. **Playwright MCP**
   - 跨瀏覽器測試
   - 進階瀏覽器操作
   - 效能監控

4. **MySQL MCP**
   - 資料庫狀態驗證
   - 資料完整性檢查
   - 查詢效能分析

## 測試執行

### 快速執行

執行所有整合測試：

```bash
# 使用 Docker 環境
docker-compose exec app php tests/Integration/run-system-settings-tests.php

# 或直接執行
php tests/Integration/run-system-settings-tests.php
```

### 分別執行測試

#### 1. 功能整合測試

```bash
# 執行所有功能測試
docker-compose exec app php artisan test tests/Integration/SystemSettingsIntegrationTest.php

# 執行特定測試方法
docker-compose exec app php artisan test tests/Integration/SystemSettingsIntegrationTest.php --filter=test_complete_settings_management_workflow
```

#### 2. 瀏覽器自動化測試

```bash
# 執行 Laravel Dusk 測試
docker-compose exec app php artisan dusk tests/Browser/SystemSettingsBrowserTest.php

# 執行特定瀏覽器測試
docker-compose exec app php artisan dusk tests/Browser/SystemSettingsBrowserTest.php --filter=test_settings_page_basic_display
```

#### 3. MCP 工具整合測試

```bash
# 執行 MCP 整合測試
php tests/Integration/execute-mcp-tests.php

# 執行特定 MCP 測試
docker-compose exec app php artisan test tests/Integration/SystemSettingsMcpTest.php
```

### 測試環境設定

#### 1. 基本環境要求

- PHP 8.1+
- Laravel 10+
- MySQL 8.0+
- Node.js 18+ (用於前端資源)
- Docker 和 Docker Compose

#### 2. 測試資料庫設定

```bash
# 複製測試環境配置
cp .env.testing.example .env.testing

# 設定測試資料庫連線
# 編輯 .env.testing 檔案
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_admin_test
DB_USERNAME=root
DB_PASSWORD=

# 執行測試遷移
docker-compose exec app php artisan migrate --env=testing
docker-compose exec app php artisan db:seed --env=testing
```

#### 3. Laravel Dusk 設定

```bash
# 安裝 Laravel Dusk
composer require --dev laravel/dusk

# 安裝 Dusk
docker-compose exec app php artisan dusk:install

# 安裝 Chrome 驅動程式
docker-compose exec app php artisan dusk:chrome-driver
```

#### 4. MCP 工具設定

建立 MCP 配置檔案 `.kiro/settings/mcp.json`：

```json
{
  "mcpServers": {
    "playwright": {
      "command": "uvx",
      "args": ["playwright-mcp-server@latest"],
      "env": {
        "PLAYWRIGHT_HEADLESS": "true"
      },
      "disabled": false,
      "autoApprove": [
        "mcp_playwright_playwright_navigate",
        "mcp_playwright_playwright_screenshot",
        "mcp_playwright_playwright_click",
        "mcp_playwright_playwright_fill",
        "mcp_playwright_playwright_get_visible_text",
        "mcp_playwright_playwright_get_visible_html"
      ]
    },
    "mysql": {
      "command": "uvx",
      "args": ["mysql-mcp-server@latest"],
      "env": {
        "MYSQL_HOST": "localhost",
        "MYSQL_PORT": "3306",
        "MYSQL_USER": "root",
        "MYSQL_PASSWORD": "",
        "MYSQL_DATABASE": "laravel_admin_test"
      },
      "disabled": false,
      "autoApprove": [
        "mcp_mysql_execute_query",
        "mcp_mysql_list_tables",
        "mcp_mysql_describe_table"
      ]
    }
  }
}
```

## 測試場景

### 場景 1: 基本設定管理工作流程

**目標**: 測試設定的完整 CRUD 操作

**步驟**:
1. 管理員登入系統
2. 導航到設定頁面
3. 檢視設定列表
4. 搜尋特定設定
5. 按分類篩選設定
6. 編輯設定值
7. 驗證設定變更
8. 重設設定到預設值
9. 檢查變更歷史
10. 登出系統

**預期結果**: 所有操作成功完成，資料庫狀態正確

### 場景 2: 設定備份和還原

**目標**: 測試設定備份和還原功能的完整性

**步驟**:
1. 管理員登入系統
2. 建立設定備份
3. 修改多個設定值
4. 從備份還原設定
5. 驗證設定已正確還原
6. 檢查備份歷史記錄

**預期結果**: 備份和還原功能正常，資料完整性保持

### 場景 3: 設定匯入匯出

**目標**: 測試設定的匯入匯出功能

**步驟**:
1. 匯出現有設定到 JSON 檔案
2. 修改部分設定
3. 匯入之前匯出的設定
4. 處理匯入衝突
5. 驗證匯入結果

**預期結果**: 匯入匯出功能正常，衝突處理正確

### 場景 4: 使用者權限控制

**目標**: 驗證不同使用者角色的權限控制

**步驟**:
1. 測試管理員完整權限
2. 測試編輯者部分權限
3. 測試檢視者唯讀權限
4. 測試一般使用者無權限存取
5. 驗證存取日誌記錄

**預期結果**: 權限控制正確，未授權存取被阻止

### 場景 5: 響應式設計和無障礙

**目標**: 測試不同裝置和無障礙功能

**步驟**:
1. 測試桌面版本顯示
2. 測試平板版本顯示
3. 測試手機版本顯示
4. 測試鍵盤導航
5. 測試螢幕閱讀器相容性

**預期結果**: 所有裝置正常顯示，無障礙功能可用

## 效能測試

### 效能指標基準

| 指標 | 基準值 | 描述 |
|------|--------|------|
| 頁面載入時間 | < 2 秒 | 設定頁面初始載入時間 |
| 設定更新時間 | < 1 秒 | 單個設定更新響應時間 |
| 搜尋響應時間 | < 500 毫秒 | 設定搜尋結果返回時間 |
| 備份建立時間 | < 5 秒 | 完整設定備份建立時間 |
| 匯入處理時間 | < 3 秒 | 設定檔案匯入處理時間 |
| 記憶體使用量 | < 50 MB | 測試過程中的記憶體峰值 |

### 效能測試執行

```bash
# 執行效能測試
docker-compose exec app php artisan test tests/Integration/SystemSettingsIntegrationTest.php --filter=performance

# 生成效能報告
php tests/Integration/run-system-settings-tests.php --performance-only
```

## 測試報告

### 自動生成報告

測試執行完成後會自動生成以下報告：

1. **HTML 測試報告**
   - 路徑: `storage/logs/system-settings-test-report-YYYY-MM-DD-HH-mm-ss.html`
   - 包含: 測試摘要、詳細結果、截圖、效能指標

2. **JSON 測試資料**
   - 路徑: `storage/logs/mcp-test-report-YYYY-MM-DD-HH-mm-ss.json`
   - 包含: 結構化測試資料，用於進一步分析

3. **測試日誌**
   - 路徑: `storage/logs/integration-tests/`
   - 包含: 詳細執行日誌、錯誤訊息、除錯資訊

### 報告內容

- **測試摘要統計**
  - 總測試數量
  - 通過/失敗/跳過測試數
  - 執行時間統計
  - 成功率百分比

- **詳細測試結果**
  - 每個測試的執行狀態
  - 失敗測試的錯誤訊息
  - 測試執行時間
  - 相關截圖和日誌

- **效能分析**
  - 各項效能指標
  - 與基準值的比較
  - 效能趨勢分析
  - 優化建議

- **資料庫驗證**
  - 資料完整性檢查
  - 約束驗證結果
  - 索引效能分析
  - 查詢執行計畫

## 故障排除

### 常見問題

#### 1. 測試資料庫連線失敗

**症狀**: 測試執行時出現資料庫連線錯誤

**解決方案**:
```bash
# 檢查測試資料庫配置
docker-compose exec app php artisan config:show database.connections.testing

# 確認資料庫服務運行
docker-compose ps db

# 重新建立測試資料庫
docker-compose exec db mysql -u root -p -e "DROP DATABASE IF EXISTS laravel_admin_test; CREATE DATABASE laravel_admin_test;"

# 重新執行遷移
docker-compose exec app php artisan migrate:fresh --env=testing
```

#### 2. Dusk 瀏覽器測試失敗

**症狀**: Chrome 驅動程式錯誤或瀏覽器無法啟動

**解決方案**:
```bash
# 更新 Chrome 驅動程式
docker-compose exec app php artisan dusk:chrome-driver

# 檢查 Chrome 版本相容性
docker-compose exec app google-chrome --version

# 清除 Dusk 快取
docker-compose exec app php artisan dusk:purge
```

#### 3. MCP 工具不可用

**症狀**: MCP 測試被跳過或執行失敗

**解決方案**:
```bash
# 檢查 MCP 配置
cat .kiro/settings/mcp.json

# 安裝 uv 和 uvx
pip install uv

# 測試 MCP 服務連線
uvx playwright-mcp-server@latest --help
uvx mysql-mcp-server@latest --help
```

#### 4. 權限錯誤

**症狀**: 檔案寫入權限錯誤

**解決方案**:
```bash
# 修復檔案權限
sudo chown -R www-data:www-data storage/
sudo chown -R www-data:www-data bootstrap/cache/
sudo chmod -R 755 storage/
sudo chmod -R 755 bootstrap/cache/

# 清除快取
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
```

#### 5. 記憶體不足

**症狀**: 測試執行時記憶體溢出

**解決方案**:
```bash
# 增加 PHP 記憶體限制
echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/memory.ini

# 或在執行時指定
php -d memory_limit=512M tests/Integration/run-system-settings-tests.php
```

### 除錯技巧

#### 1. 啟用詳細輸出

```bash
# Laravel 測試詳細輸出
docker-compose exec app php artisan test --verbose

# Dusk 除錯模式
docker-compose exec app php artisan dusk --debug
```

#### 2. 檢視測試日誌

```bash
# 查看 Laravel 日誌
tail -f storage/logs/laravel.log

# 查看測試專用日誌
tail -f storage/logs/integration-tests/mcp-integration-test.log

# 查看 Dusk 日誌
ls -la tests/Browser/screenshots/
ls -la tests/Browser/console/
```

#### 3. 單獨執行失敗的測試

```bash
# 執行特定測試方法
docker-compose exec app php artisan test --filter=test_complete_settings_workflow_with_playwright

# 停止在第一個失敗
docker-compose exec app php artisan test --stop-on-failure

# 重新執行失敗的測試
docker-compose exec app php artisan test --retry
```

## 持續整合

### GitHub Actions 配置

建立 `.github/workflows/system-settings-tests.yml`：

```yaml
name: System Settings Integration Tests

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  integration-tests:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: laravel_admin_test
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
        extensions: mbstring, dom, fileinfo, mysql, gd
        
    - name: Install dependencies
      run: |
        composer install --no-progress --no-suggest --prefer-dist --optimize-autoloader
        npm install && npm run build
        
    - name: Prepare environment
      run: |
        cp .env.testing.example .env.testing
        php artisan key:generate --env=testing
        php artisan migrate --env=testing
        
    - name: Run integration tests
      run: php tests/Integration/run-system-settings-tests.php
      
    - name: Upload test artifacts
      uses: actions/upload-artifact@v3
      if: always()
      with:
        name: test-results
        path: |
          storage/logs/system-settings-test-report-*.html
          storage/logs/integration-tests/
          tests/Browser/screenshots/
```

### 本地開發 Git Hooks

設定 pre-commit hook：

```bash
#!/bin/bash
# .git/hooks/pre-commit

echo "執行系統設定整合測試..."

# 執行快速測試
php artisan test tests/Integration/SystemSettingsIntegrationTest.php --filter=test_basic

if [ $? -ne 0 ]; then
    echo "❌ 基本測試失敗，提交被取消"
    exit 1
fi

echo "✅ 測試通過，允許提交"
exit 0
```

## 測試維護

### 定期維護任務

1. **每週**
   - 執行完整測試套件
   - 檢查測試覆蓋率
   - 更新測試資料

2. **每月**
   - 檢查效能指標趨勢
   - 更新瀏覽器驅動程式
   - 檢視失敗測試統計

3. **每季**
   - 更新測試場景
   - 檢視測試架構
   - 優化測試執行時間

4. **每半年**
   - 全面檢視測試策略
   - 更新測試工具版本
   - 評估新測試技術

### 測試資料管理

```bash
# 重置測試環境
docker-compose exec app php artisan migrate:fresh --env=testing --seed

# 清除測試快取
docker-compose exec app php artisan cache:clear --env=testing

# 清除測試檔案
rm -rf storage/logs/integration-tests/*
rm -rf tests/Browser/screenshots/*
rm -rf storage/app/screenshots/integration-tests/*
```

## 貢獻指南

### 新增測試

1. **功能測試**: 在 `SystemSettingsIntegrationTest.php` 中新增測試方法
2. **瀏覽器測試**: 在 `SystemSettingsBrowserTest.php` 中新增測試方法
3. **MCP 測試**: 在 `SystemSettingsMcpTest.php` 中新增測試方法

### 測試命名慣例

- 測試方法名稱: `test_功能描述_with_條件`
- 測試資料: 使用 `test.` 前綴
- 截圖檔案: `功能-狀態-時間戳.png`

### 程式碼審查檢查清單

- [ ] 測試覆蓋所有相關需求
- [ ] 測試具有良好的隔離性
- [ ] 測試名稱清楚描述測試目的
- [ ] 包含適當的斷言和驗證
- [ ] 遵循專案的程式碼風格
- [ ] 包含必要的註解和文檔

---

如有任何問題或建議，請聯繫開發團隊或在專案 issue 中提出。