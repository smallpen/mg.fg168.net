# 使用者管理整合測試文件

## 概述

本目錄包含使用者管理功能的完整整合測試套件，涵蓋功能測試、效能測試、瀏覽器自動化測試等多個方面。

## 測試覆蓋範圍

### 需求覆蓋

本測試套件完全覆蓋以下需求：

- **需求 1.1-1.2**: 使用者列表顯示和分頁功能
- **需求 2.1-2.2**: 即時搜尋功能和結果處理
- **需求 3.1-3.2**: 狀態篩選功能
- **需求 4.1-4.2**: 角色篩選功能
- **需求 5.1-5.6**: 使用者操作功能（檢視、編輯、刪除、批量操作）
- **需求 6.1-6.3**: 響應式設計（桌面、平板、手機）
- **需求 7.1-7.3**: 效能要求（載入時間、響應時間、大量資料處理）
- **需求 8.1-8.3**: 多語言支援
- **需求 9.1-9.4**: 安全性和權限控制

### 測試類型

1. **功能整合測試** (`UserManagementIntegrationTest.php`)
   - 完整工作流程測試
   - 權限控制測試
   - 資料完整性測試
   - 錯誤處理測試
   - 多語言支援測試
   - 安全性測試

2. **效能測試** (`UserManagementPerformanceTest.php`)
   - 頁面載入效能測試
   - 搜尋響應時間測試
   - 篩選和排序效能測試
   - 大量資料處理測試
   - 快取效能測試
   - 記憶體使用測試

3. **瀏覽器自動化測試** (`UserManagementBrowserTest.php`)
   - 端到端使用者流程測試
   - 響應式設計實際測試
   - 鍵盤導航測試
   - 無障礙功能測試
   - 即時更新測試

## 測試執行

### 快速執行

使用整合測試執行腳本：

```bash
php tests/Integration/run-user-management-tests.php
```

### 分別執行

#### 1. 功能整合測試

```bash
# 執行所有功能整合測試
php artisan test tests/Feature/Integration/UserManagementIntegrationTest.php

# 執行特定測試方法
php artisan test tests/Feature/Integration/UserManagementIntegrationTest.php --filter=test_complete_user_management_workflow
```

#### 2. 效能測試

```bash
# 執行所有效能測試
php artisan test tests/Feature/Performance/UserManagementPerformanceTest.php

# 執行特定效能測試
php artisan test tests/Feature/Performance/UserManagementPerformanceTest.php --filter=test_initial_page_load_performance
```

#### 3. 瀏覽器測試

```bash
# 執行所有瀏覽器測試（需要安裝 Laravel Dusk）
php artisan dusk tests/Browser/UserManagementBrowserTest.php

# 執行特定瀏覽器測試
php artisan dusk tests/Browser/UserManagementBrowserTest.php --filter=test_complete_user_management_workflow
```

### 測試環境設定

#### 1. 資料庫設定

確保測試資料庫已正確設定：

```bash
# 複製環境設定檔
cp .env.testing.example .env.testing

# 設定測試資料庫
php artisan config:cache --env=testing
php artisan migrate --env=testing
```

#### 2. 瀏覽器測試設定

安裝 Laravel Dusk（如需執行瀏覽器測試）：

```bash
composer require --dev laravel/dusk
php artisan dusk:install
php artisan dusk:chrome-driver
```

#### 3. 效能測試環境

確保測試環境具備足夠的資源：

- 記憶體：至少 512MB
- 磁碟空間：至少 1GB
- 資料庫：MySQL 8.0 或更高版本

## 測試結果解讀

### 效能指標

測試會檢查以下效能指標：

- **頁面載入時間**: < 2 秒
- **搜尋響應時間**: < 1 秒
- **篩選響應時間**: < 1 秒
- **排序響應時間**: < 1 秒
- **批量操作時間**: < 2 秒
- **記憶體使用量**: < 50 MB

### 響應式設計測試

測試會驗證以下解析度的顯示效果：

- **桌面**: ≥1024px - 完整表格顯示
- **平板**: 768px-1023px - 適應性調整
- **手機**: <768px - 卡片式佈局

### 權限控制測試

測試會驗證以下權限場景：

- 超級管理員：完整權限
- 管理員：使用者管理權限
- 一般使用者：無管理權限
- 未登入使用者：重導向到登入頁面

## 測試報告

### 自動生成報告

執行整合測試腳本後，會自動生成 HTML 格式的測試報告：

```
storage/logs/user-management-test-report-YYYY-MM-DD-HH-mm-ss.html
```

報告包含：

- 測試摘要和統計
- 詳細測試結果
- 效能指標分析
- 錯誤和失敗詳情

### 手動查看結果

```bash
# 查看最新的測試報告
ls -la storage/logs/user-management-test-report-*.html | tail -1

# 在瀏覽器中開啟報告
open storage/logs/user-management-test-report-*.html
```

## 故障排除

### 常見問題

#### 1. 資料庫連線錯誤

```bash
# 檢查測試資料庫設定
php artisan config:show database.connections.testing

# 重新執行遷移
php artisan migrate:fresh --env=testing
```

#### 2. 瀏覽器測試失敗

```bash
# 更新 Chrome 驅動程式
php artisan dusk:chrome-driver

# 檢查瀏覽器版本相容性
google-chrome --version
```

#### 3. 效能測試超時

```bash
# 增加 PHP 執行時間限制
php -d max_execution_time=300 artisan test tests/Feature/Performance/UserManagementPerformanceTest.php

# 檢查系統資源使用情況
top
free -h
```

#### 4. 權限錯誤

```bash
# 檢查檔案權限
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/

# 清除快取
php artisan cache:clear
php artisan config:clear
```

### 除錯技巧

#### 1. 啟用詳細輸出

```bash
# 使用 --verbose 參數
php artisan test tests/Feature/Integration/UserManagementIntegrationTest.php --verbose

# 使用 --debug 參數（Dusk）
php artisan dusk tests/Browser/UserManagementBrowserTest.php --debug
```

#### 2. 單獨執行失敗的測試

```bash
# 執行特定測試方法
php artisan test --filter=test_permission_based_access_control

# 停止在第一個失敗
php artisan test --stop-on-failure
```

#### 3. 查看測試日誌

```bash
# 查看 Laravel 日誌
tail -f storage/logs/laravel.log

# 查看測試專用日誌
tail -f storage/logs/testing.log
```

## 持續整合

### GitHub Actions

在 `.github/workflows/user-management-tests.yml` 中設定自動化測試：

```yaml
name: User Management Integration Tests

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
        
    - name: Install dependencies
      run: composer install
      
    - name: Run integration tests
      run: php tests/Integration/run-user-management-tests.php
      
    - name: Upload test report
      uses: actions/upload-artifact@v2
      with:
        name: test-report
        path: storage/logs/user-management-test-report-*.html
```

### 本地開發

設定 Git hooks 在提交前執行測試：

```bash
# 建立 pre-commit hook
cat > .git/hooks/pre-commit << 'EOF'
#!/bin/bash
echo "執行使用者管理整合測試..."
php tests/Integration/run-user-management-tests.php
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
php artisan migrate:fresh --env=testing --seed

# 清除測試快取
php artisan cache:clear --env=testing

# 清除測試日誌
rm storage/logs/user-management-test-report-*.html
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
2. 遵循現有的命名慣例
3. 新增適當的文檔註解
4. 確保測試具有良好的隔離性

### 測試最佳實踐

1. **測試隔離**: 每個測試都應該獨立執行
2. **資料清理**: 使用 `RefreshDatabase` trait
3. **明確斷言**: 使用具體的斷言方法
4. **錯誤處理**: 測試預期的錯誤情況
5. **效能考量**: 避免不必要的資料庫操作

### 程式碼審查

提交測試程式碼時，請確保：

1. 測試覆蓋所有相關需求
2. 測試名稱清楚描述測試目的
3. 測試邏輯簡潔明瞭
4. 包含適當的註解和文檔
5. 遵循專案的程式碼風格

---

如有任何問題或建議，請聯繫開發團隊或在專案 issue 中提出。