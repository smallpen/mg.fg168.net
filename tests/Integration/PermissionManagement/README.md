# 權限管理整合測試文件

## 概述

本目錄包含權限管理功能的完整整合測試套件，涵蓋功能測試、瀏覽器自動化測試、效能測試、安全性測試等多個方面。

## 測試覆蓋範圍

### 需求覆蓋

本測試套件完全覆蓋權限管理規格中的所有需求：

- **需求 1**: 權限列表顯示功能
- **需求 2**: 權限搜尋和篩選功能
- **需求 3**: 權限建立功能
- **需求 4**: 權限編輯功能
- **需求 5**: 權限依賴關係管理
- **需求 6**: 權限分組管理
- **需求 7**: 權限使用情況分析
- **需求 8**: 權限刪除功能
- **需求 9**: 權限匯入匯出功能
- **需求 10**: 權限模板功能
- **需求 11**: 權限審計功能
- **需求 12**: 權限驗證測試功能

### 測試類型

1. **功能整合測試** (`PermissionManagementIntegrationTest.php`)
   - 完整工作流程測試
   - 權限 CRUD 操作測試
   - 依賴關係管理測試
   - 循環依賴檢查測試
   - 存取控制測試
   - 匯入匯出功能測試
   - 使用情況分析測試
   - 安全性控制測試
   - 審計功能測試
   - 模板功能測試
   - 效能測試

2. **瀏覽器自動化測試** (`PermissionManagementBrowserTest.php`)
   - 端到端使用者流程測試
   - 響應式設計測試
   - 無障礙功能測試
   - 即時互動功能測試
   - 錯誤處理測試

3. **測試套件管理** (`PermissionManagementTestSuite.php`)
   - 統一測試執行
   - 效能指標收集
   - 測試報告生成
   - 並發測試
   - 安全性測試

## 測試執行

### 快速執行

使用整合測試執行腳本：

```bash
# 執行所有測試
php tests/Integration/PermissionManagement/run-permission-management-tests.php --all

# 執行特定類型的測試
php tests/Integration/PermissionManagement/run-permission-management-tests.php --functional
php tests/Integration/PermissionManagement/run-permission-management-tests.php --browser
php tests/Integration/PermissionManagement/run-permission-management-tests.php --performance
php tests/Integration/PermissionManagement/run-permission-management-tests.php --security

# 顯示詳細輸出
php tests/Integration/PermissionManagement/run-permission-management-tests.php --all --verbose
```

### 使用 Docker 執行

```bash
# 在 Docker 容器中執行測試
docker-compose exec app php tests/Integration/PermissionManagement/run-permission-management-tests.php --all

# 執行特定測試
docker-compose exec app php artisan test tests/Integration/PermissionManagement/PermissionManagementIntegrationTest.php
```

### 分別執行

#### 1. 功能整合測試

```bash
# 執行所有功能整合測試
docker-compose exec app php artisan test tests/Integration/PermissionManagement/PermissionManagementIntegrationTest.php

# 執行特定測試方法
docker-compose exec app php artisan test tests/Integration/PermissionManagement/PermissionManagementIntegrationTest.php --filter=test_complete_permission_management_workflow
```

#### 2. 瀏覽器自動化測試

```bash
# 執行所有瀏覽器測試
docker-compose exec app php artisan test tests/Integration/PermissionManagement/PermissionManagementBrowserTest.php

# 執行特定瀏覽器測試
docker-compose exec app php artisan test tests/Integration/PermissionManagement/PermissionManagementBrowserTest.php --filter=test_complete_permission_management_workflow_browser
```

#### 3. 測試套件

```bash
# 執行完整測試套件
docker-compose exec app php artisan test tests/Integration/PermissionManagement/PermissionManagementTestSuite.php
```

## 測試環境設定

### 1. 資料庫設定

確保測試資料庫已正確設定：

```bash
# 複製環境設定檔
cp .env.testing.example .env.testing

# 設定測試資料庫
docker-compose exec app php artisan config:cache --env=testing
docker-compose exec app php artisan migrate --env=testing
```

### 2. 瀏覽器測試設定

#### 使用 Playwright MCP

確保 Playwright MCP server 已正確配置：

```json
{
  "mcpServers": {
    "playwright": {
      "command": "uvx",
      "args": ["mcp-playwright"],
      "env": {
        "PLAYWRIGHT_HEADLESS": "true"
      }
    }
  }
}
```

#### 使用 Laravel Dusk（可選）

```bash
# 安裝 Laravel Dusk
docker-compose exec app composer require --dev laravel/dusk
docker-compose exec app php artisan dusk:install
docker-compose exec app php artisan dusk:chrome-driver
```

### 3. MySQL MCP 設定

確保 MySQL MCP server 已正確配置：

```json
{
  "mcpServers": {
    "mysql": {
      "command": "uvx",
      "args": ["mcp-mysql"],
      "env": {
        "MYSQL_HOST": "db",
        "MYSQL_PORT": "3306",
        "MYSQL_USER": "laravel_admin",
        "MYSQL_PASSWORD": "your_password",
        "MYSQL_DATABASE": "laravel_admin"
      }
    }
  }
}
```

## 測試結果解讀

### 效能指標

測試會檢查以下效能指標：

- **權限列表載入時間**: < 2 秒
- **搜尋響應時間**: < 1 秒
- **篩選響應時間**: < 1 秒
- **依賴關係解析時間**: < 3 秒
- **匯入匯出處理時間**: < 5 秒
- **大量資料處理時間**: < 10 秒

### 功能驗證

測試會驗證以下功能：

- **CRUD 操作**: 建立、讀取、更新、刪除權限
- **依賴關係**: 正確的依賴關係建立和解析
- **循環依賴檢查**: 防止循環依賴的建立
- **存取控制**: 不同使用者的權限檢查
- **資料完整性**: 資料的一致性和完整性
- **安全性**: 輸入驗證和 SQL 注入防護

### 瀏覽器測試

測試會驗證以下使用者體驗：

- **響應式設計**: 不同螢幕尺寸的適應性
- **無障礙功能**: 鍵盤導航和螢幕閱讀器支援
- **即時互動**: 搜尋和篩選的即時更新
- **錯誤處理**: 使用者友善的錯誤訊息

## 測試報告

### 自動生成報告

執行測試後，會自動生成詳細的測試報告：

```
storage/logs/permission-management-test-report-YYYY-MM-DD-HH-mm-ss.json
storage/logs/permission-management-test-report-YYYY-MM-DD-HH-mm-ss.html
```

報告包含：

- **測試摘要**: 總測試數、通過率、執行時間
- **詳細結果**: 每個測試的執行狀態和錯誤訊息
- **效能指標**: 各項操作的執行時間
- **安全性檢查**: 安全性測試結果

### 查看報告

```bash
# 查看最新的 JSON 報告
cat storage/logs/permission-management-test-report-*.json | tail -1

# 在瀏覽器中開啟 HTML 報告
open storage/logs/permission-management-test-report-*.html
```

## 故障排除

### 常見問題

#### 1. 資料庫連線錯誤

```bash
# 檢查資料庫容器狀態
docker-compose ps db

# 檢查資料庫連線
docker-compose exec app php artisan migrate:status --env=testing

# 重新建立測試資料庫
docker-compose exec app php artisan migrate:fresh --env=testing
```

#### 2. 瀏覽器測試失敗

```bash
# 檢查 Playwright MCP 狀態
# 在 Kiro 中檢查 MCP 連線狀態

# 檢查 Chrome 驅動程式（如使用 Dusk）
docker-compose exec app php artisan dusk:chrome-driver --detect

# 更新瀏覽器驅動程式
docker-compose exec app php artisan dusk:chrome-driver
```

#### 3. 權限錯誤

```bash
# 檢查檔案權限
docker-compose exec app chown -R www-data:www-data storage/
docker-compose exec app chmod -R 755 storage/

# 清除快取
docker-compose exec app php artisan cache:clear --env=testing
docker-compose exec app php artisan config:clear --env=testing
```

#### 4. 記憶體不足

```bash
# 增加 PHP 記憶體限制
docker-compose exec app php -d memory_limit=512M artisan test

# 檢查 Docker 容器資源限制
docker stats
```

### 除錯技巧

#### 1. 啟用詳細輸出

```bash
# 使用 --verbose 參數
php run-permission-management-tests.php --all --verbose

# 使用 PHPUnit 的詳細模式
docker-compose exec app php artisan test --verbose
```

#### 2. 單獨執行失敗的測試

```bash
# 執行特定測試方法
docker-compose exec app php artisan test --filter=test_permission_dependency_management

# 停止在第一個失敗
docker-compose exec app php artisan test --stop-on-failure
```

#### 3. 查看測試日誌

```bash
# 查看 Laravel 日誌
docker-compose exec app tail -f storage/logs/laravel.log

# 查看測試專用日誌
docker-compose exec app tail -f storage/logs/testing.log
```

#### 4. 使用 MCP 工具除錯

```bash
# 使用 MySQL MCP 檢查資料狀態
# 在 Kiro 中執行 MySQL 查詢

# 使用 Playwright MCP 檢查頁面狀態
# 在 Kiro 中執行瀏覽器操作
```

## 持續整合

### GitHub Actions

在 `.github/workflows/permission-management-tests.yml` 中設定自動化測試：

```yaml
name: Permission Management Integration Tests

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: laravel_admin_testing
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
        extensions: pdo, pdo_mysql, mbstring, json
        
    - name: Install dependencies
      run: composer install --no-dev --optimize-autoloader
      
    - name: Setup environment
      run: |
        cp .env.testing.example .env.testing
        php artisan key:generate --env=testing
        
    - name: Run database migrations
      run: php artisan migrate --env=testing
      
    - name: Run integration tests
      run: php tests/Integration/PermissionManagement/run-permission-management-tests.php --all
      
    - name: Upload test report
      uses: actions/upload-artifact@v3
      if: always()
      with:
        name: test-report
        path: storage/logs/permission-management-test-report-*.html
```

### 本地開發

設定 Git hooks 在提交前執行測試：

```bash
# 建立 pre-commit hook
cat > .git/hooks/pre-commit << 'EOF'
#!/bin/bash
echo "執行權限管理整合測試..."
docker-compose exec app php tests/Integration/PermissionManagement/run-permission-management-tests.php --functional
if [ $? -ne 0 ]; then
    echo "測試失敗，提交被取消"
    exit 1
fi
EOF

chmod +x .git/hooks/pre-commit
```

## 測試維護

### 定期更新

1. **每週執行完整測試套件**
2. **每月檢查效能指標趨勢**
3. **每季更新測試資料和場景**
4. **每半年檢視測試覆蓋率**

### 測試資料管理

```bash
# 重置測試資料庫
docker-compose exec app php artisan migrate:fresh --env=testing --seed

# 清除測試快取
docker-compose exec app php artisan cache:clear --env=testing

# 清除測試日誌
rm storage/logs/permission-management-test-report-*.html
rm storage/logs/permission-management-test-report-*.json
```

### 效能基準更新

定期檢查和更新效能基準：

1. 記錄每次測試的效能指標
2. 分析效能趨勢
3. 根據硬體升級調整基準
4. 優化慢速測試

## 貢獻指南

### 新增測試

1. 在適當的測試類別中新增測試方法
2. 遵循現有的命名慣例（`test_` 前綴）
3. 新增適當的文檔註解
4. 確保測試具有良好的隔離性
5. 更新相關的測試文檔

### 測試最佳實踐

1. **測試隔離**: 每個測試都應該獨立執行
2. **資料清理**: 使用 `RefreshDatabase` trait
3. **明確斷言**: 使用具體的斷言方法
4. **錯誤處理**: 測試預期的錯誤情況
5. **效能考量**: 避免不必要的資料庫操作
6. **可讀性**: 使用清楚的測試名稱和註解

### 程式碼審查

提交測試程式碼時，請確保：

1. 測試覆蓋所有相關需求
2. 測試名稱清楚描述測試目的
3. 測試邏輯簡潔明瞭
4. 包含適當的註解和文檔
5. 遵循專案的程式碼風格
6. 通過所有現有測試

## 相關資源

- [權限管理需求文件](../../../.kiro/specs/permission-management/requirements.md)
- [權限管理設計文件](../../../.kiro/specs/permission-management/design.md)
- [權限管理任務列表](../../../.kiro/specs/permission-management/tasks.md)
- [MCP 工具快速參考](../../../.kiro/steering/mcp-quick-reference.md)
- [Docker 環境開發規範](../../../.kiro/steering/docker-environment.md)

---

如有任何問題或建議，請聯繫開發團隊或在專案 issue 中提出。