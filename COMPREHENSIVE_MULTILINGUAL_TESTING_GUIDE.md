# 全面多語系功能測試指南

## 概述

本指南詳細說明如何執行全面的多語系功能測試，確保系統在不同語言環境下都能正常運作。測試涵蓋語言切換、語言偏好持久化、錯誤處理和回退機制等關鍵功能。

## 測試範圍

### 1. 語言切換功能測試
- **目標**: 驗證所有主要頁面的語言切換功能
- **涵蓋頁面**:
  - 登入頁面 (`/admin/login`)
  - 儀表板 (`/admin/dashboard`)
  - 使用者管理 (`/admin/users`)
  - 角色管理 (`/admin/roles`)
  - 權限管理 (`/admin/permissions`)
  - 系統設定 (`/admin/settings`)

### 2. 語言偏好持久化測試
- **目標**: 驗證語言偏好在不同情況下的保持
- **測試情況**:
  - Session 儲存
  - 使用者資料庫記錄
  - 瀏覽器重新啟動後的保持
  - 不同瀏覽器的相容性

### 3. 語言回退機制測試
- **目標**: 驗證錯誤情況下的回退處理
- **測試場景**:
  - 缺少翻譯鍵
  - 無效語言參數
  - 語言檔案損壞
  - 部分翻譯情況

### 4. 錯誤處理和日誌記錄測試
- **目標**: 驗證錯誤處理機制和日誌記錄
- **測試內容**:
  - 錯誤情況的優雅處理
  - 相關錯誤日誌的記錄
  - 使用者體驗的保持

## 執行方式

### 方式一：使用 MCP 整合測試（推薦）

```bash
# 確保 Docker 環境運行
docker-compose up -d

# 確保測試資料存在
docker-compose exec app php artisan db:seed

# 執行全面多語系測試
php run-comprehensive-multilingual-tests.php
```

### 方式二：使用 PHPUnit 測試

```bash
# 執行多語系相關的 PHPUnit 測試
docker-compose exec app php artisan test tests/Integration/ComprehensiveMultilingualTest.php

# 執行所有多語系測試
docker-compose exec app php artisan test --filter=Multilingual
```

### 方式三：手動測試

按照本文檔的「手動測試步驟」章節進行逐步測試。

## 測試前準備

### 1. 環境檢查

```bash
# 檢查 Docker 服務狀態
docker-compose ps

# 檢查資料庫連線
docker-compose exec app php artisan migrate:status

# 檢查測試資料
docker-compose exec app php artisan tinker --execute="
echo 'Users: ' . User::count();
echo 'Roles: ' . Role::count();
echo 'Permissions: ' . Permission::count();
"
```

### 2. 語言檔案檢查

```bash
# 檢查語言檔案是否存在
ls -la lang/zh_TW/
ls -la lang/en/

# 檢查關鍵語言檔案
cat lang/zh_TW/auth.php
cat lang/en/auth.php
```

### 3. 測試資料準備

```bash
# 重建測試資料（如果需要）
docker-compose exec app php artisan migrate:fresh --seed

# 驗證管理員帳號
docker-compose exec app php artisan tinker --execute="
\$admin = User::where('username', 'admin')->first();
echo \$admin ? 'Admin exists' : 'Admin not found';
"
```

## 詳細測試步驟

### 1. 登入頁面語言切換測試

#### 測試步驟：
1. 開啟瀏覽器並導航到 `http://localhost/admin/login`
2. 檢查預設語言顯示（應為正體中文）
3. 點擊語言選擇器，切換到英文
4. 驗證頁面內容是否更新為英文
5. 檢查以下元素的翻譯：
   - 頁面標題
   - 登入表單標籤
   - 按鈕文字
   - 主題切換按鈕
   - 錯誤訊息（故意輸入錯誤資料測試）

#### 預期結果：
- 語言切換後頁面立即更新
- 所有文字元素都正確翻譯
- URL 包含正確的 locale 參數
- 語言選擇器顯示當前選擇的語言

#### 驗證方法：
```javascript
// 使用瀏覽器開發者工具檢查
console.log(document.documentElement.lang); // 應該是 'en' 或 'zh-TW'
console.log(window.location.search); // 應該包含 '?locale=en'
```

### 2. 管理後台頁面測試

#### 測試步驟：
1. 使用管理員帳號登入系統
2. 依次訪問以下頁面並測試語言切換：
   - 儀表板 (`/admin/dashboard`)
   - 使用者管理 (`/admin/users`)
   - 角色管理 (`/admin/roles`)
   - 權限管理 (`/admin/permissions`)
   - 系統設定 (`/admin/settings`)

3. 在每個頁面執行以下測試：
   - 切換語言並驗證頁面更新
   - 檢查導航選單翻譯
   - 檢查頁面標題翻譯
   - 檢查按鈕和連結翻譯
   - 檢查表格標題翻譯
   - 檢查表單標籤翻譯

#### 預期結果：
- 所有頁面的語言切換都正常運作
- 導航選單在所有頁面都正確翻譯
- 頁面內容與選擇的語言一致
- 跨頁面導航時語言設定保持

### 3. 語言偏好持久化測試

#### 測試步驟：
1. 在登入頁面切換到英文
2. 登入系統
3. 檢查資料庫中使用者的語言偏好：
   ```sql
   SELECT username, locale FROM users WHERE username = 'admin';
   ```
4. 登出系統
5. 重新登入
6. 驗證系統是否自動使用英文介面

#### 預期結果：
- 語言偏好正確儲存到資料庫
- 重新登入後自動使用儲存的語言偏好
- Session 和資料庫記錄保持一致

### 4. 瀏覽器相容性測試

#### 測試步驟：
1. 在不同瀏覽器中重複上述測試：
   - Chrome/Chromium
   - Firefox
   - Safari/WebKit
2. 測試瀏覽器語言偏好偵測：
   - 清除所有 cookies 和 session
   - 設定瀏覽器語言偏好
   - 訪問系統並檢查預設語言

#### 預期結果：
- 所有瀏覽器都正常支援語言切換
- 瀏覽器語言偏好正確偵測
- 跨瀏覽器的一致性體驗

### 5. 錯誤處理測試

#### 測試步驟：
1. 測試無效語言參數：
   - 訪問 `http://localhost/admin/login?locale=invalid`
   - 訪問 `http://localhost/admin/login?locale=zh_CN`
   - 訪問 `http://localhost/admin/login?locale=`

2. 測試語言檔案問題：
   - 暫時重新命名語言檔案
   - 檢查系統回退行為

3. 檢查錯誤日誌：
   ```bash
   # 檢查 Laravel 日誌
   docker-compose exec app tail -f storage/logs/laravel.log
   
   # 檢查多語系專用日誌（如果有）
   docker-compose exec app tail -f storage/logs/multilingual.log
   ```

#### 預期結果：
- 無效參數時回退到預設語言
- 系統不會因語言問題而崩潰
- 相關錯誤被正確記錄到日誌
- 使用者體驗不受影響

### 6. 效能測試

#### 測試步驟：
1. 測量語言切換響應時間：
   ```javascript
   // 在瀏覽器控制台執行
   const startTime = performance.now();
   // 執行語言切換操作
   const endTime = performance.now();
   console.log(`語言切換耗時: ${endTime - startTime} 毫秒`);
   ```

2. 測試多次語言切換的穩定性：
   - 快速切換語言 10 次
   - 檢查記憶體使用量變化
   - 檢查是否有記憶體洩漏

#### 預期結果：
- 語言切換響應時間 < 500ms
- 多次切換後系統保持穩定
- 記憶體使用量在合理範圍內

## 測試結果驗證

### 1. 自動化驗證

執行測試腳本後，檢查生成的測試報告：

```bash
# 檢查測試結果
cat storage/test-results/comprehensive-multilingual-results.json

# 檢查測試通過率
php -r "
\$report = json_decode(file_get_contents('storage/test-results/comprehensive-multilingual-results.json'), true);
echo '通過率: ' . (\$report['summary']['pass_rate'] * 100) . '%\n';
"
```

### 2. 手動驗證清單

- [ ] 登入頁面語言切換正常
- [ ] 所有管理頁面語言切換正常
- [ ] 語言偏好正確儲存和載入
- [ ] 錯誤情況下的回退機制正常
- [ ] 瀏覽器相容性良好
- [ ] 效能表現符合要求
- [ ] 錯誤日誌記錄完整

### 3. 品質標準

- **通過率**: ≥ 90%
- **語言切換響應時間**: < 500ms
- **頁面載入時間**: < 2s
- **錯誤處理**: 100% 覆蓋
- **瀏覽器相容性**: 支援主流瀏覽器

## 常見問題排除

### 1. 語言切換不生效

**可能原因**:
- 語言檔案缺失或格式錯誤
- 中介軟體配置問題
- 快取問題

**解決方法**:
```bash
# 檢查語言檔案
ls -la lang/zh_TW/ lang/en/

# 清除快取
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear

# 檢查中介軟體配置
docker-compose exec app php artisan route:list | grep SetLocale
```

### 2. 語言偏好不持久

**可能原因**:
- 資料庫連線問題
- Session 配置問題
- 使用者模型問題

**解決方法**:
```bash
# 檢查資料庫連線
docker-compose exec app php artisan migrate:status

# 檢查 Session 配置
docker-compose exec app php artisan config:show session

# 檢查使用者模型
docker-compose exec app php artisan tinker --execute="
\$user = User::find(1);
var_dump(\$user->locale);
"
```

### 3. 翻譯內容不正確

**可能原因**:
- 語言檔案內容錯誤
- 翻譯鍵不存在
- 參數替換問題

**解決方法**:
```bash
# 檢查特定翻譯鍵
docker-compose exec app php artisan tinker --execute="
App::setLocale('zh_TW');
echo __('auth.failed');
App::setLocale('en');
echo __('auth.failed');
"

# 檢查語言檔案語法
docker-compose exec app php -l lang/zh_TW/auth.php
docker-compose exec app php -l lang/en/auth.php
```

## 測試報告範例

### 成功的測試報告

```json
{
    "test_name": "全面多語系功能測試",
    "timestamp": "2024-01-15 14:30:00",
    "summary": {
        "total_tests": 25,
        "passed_tests": 24,
        "failed_tests": 1,
        "pass_rate": 0.96,
        "language_switching_passed": true,
        "language_persistence_passed": true,
        "fallback_mechanism_passed": true,
        "error_handling_passed": false
    },
    "errors": [
        "錯誤處理測試: 日誌記錄功能需要改進"
    ],
    "recommendations": [
        "改進錯誤日誌記錄的詳細程度",
        "考慮加入更多的錯誤處理場景"
    ]
}
```

### 需要改進的測試報告

```json
{
    "test_name": "全面多語系功能測試",
    "timestamp": "2024-01-15 14:30:00",
    "summary": {
        "total_tests": 25,
        "passed_tests": 18,
        "failed_tests": 7,
        "pass_rate": 0.72,
        "language_switching_passed": false,
        "language_persistence_passed": true,
        "fallback_mechanism_passed": false,
        "error_handling_passed": false
    },
    "errors": [
        "使用者管理頁面語言切換測試 (en): 表格標題未翻譯",
        "角色管理頁面語言切換測試 (zh_TW): 操作按鈕硬編碼",
        "回退機制測試 - 缺少翻譯鍵: 未正確回退到預設語言"
    ],
    "recommendations": [
        "修復使用者管理頁面的表格標題翻譯",
        "將角色管理頁面的操作按鈕文字移至語言檔案",
        "改進語言回退機制的實現",
        "加強錯誤處理和日誌記錄功能"
    ]
}
```

## 持續改進

### 1. 定期執行測試

建議在以下情況執行全面多語系測試：
- 新功能開發完成後
- 語言檔案更新後
- 系統重大更新前
- 定期品質檢查（每月）

### 2. 測試自動化

考慮將測試整合到 CI/CD 流程中：

```yaml
# .github/workflows/multilingual-tests.yml
name: Multilingual Tests

on:
  push:
    paths:
      - 'lang/**'
      - 'resources/views/**'
      - 'app/Http/Middleware/SetLocale.php'

jobs:
  multilingual-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
      - name: Install dependencies
        run: composer install
      - name: Run multilingual tests
        run: php artisan test --filter=Multilingual
```

### 3. 監控和警報

設定監控系統來追蹤多語系功能的健康狀況：
- 語言切換成功率
- 翻譯錯誤頻率
- 使用者語言偏好分佈
- 效能指標

## 結論

全面的多語系功能測試是確保系統國際化品質的關鍵步驟。通過系統化的測試方法和持續的監控改進，可以為不同語言的使用者提供一致且優質的體驗。

定期執行這些測試並根據結果持續改進，將有助於維持系統的多語系功能品質，並及早發現和解決潛在問題。