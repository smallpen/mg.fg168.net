# 登入頁面多語系測試指南

## 概述

本指南說明如何執行登入頁面的多語系功能測試，使用 Playwright 和 MySQL MCP 工具進行完整的端到端測試。

## 測試範圍

### 1. 語言切換功能測試
- 測試登入頁面在不同語言間的切換
- 驗證頁面內容是否正確翻譯
- 檢查語言切換的響應時間和使用者體驗

### 2. 主題切換按鈕翻譯測試
- 驗證主題切換按鈕在不同語言下的文字顯示
- 測試主題切換後按鈕文字的更新
- 確保主題切換功能在所有語言下正常運作

### 3. 表單驗證訊息多語系測試
- 測試空欄位驗證訊息的翻譯
- 驗證欄位長度驗證訊息的多語系顯示
- 檢查即時驗證訊息的語言正確性

### 4. 頁面標題多語系測試
- 驗證瀏覽器標題列的翻譯
- 檢查頁面內 H2 標題的翻譯
- 確保標題在語言切換後正確更新

### 5. 語言偏好持久化測試
- 測試語言選擇是否在頁面重新載入後保持
- 驗證 Session 中的語言偏好儲存
- 檢查使用者語言偏好的資料庫儲存

### 6. 登入後語言保持測試
- 測試成功登入後語言是否保持
- 驗證儀表板頁面使用正確的語言
- 檢查使用者偏好設定的更新

### 7. 錯誤訊息多語系測試
- 測試登入失敗時的錯誤訊息翻譯
- 驗證不同類型錯誤的多語系顯示
- 檢查錯誤訊息的語言一致性

### 8. 語言切換使用者體驗測試
- 測試語言切換的響應時間
- 驗證快速連續切換的穩定性
- 檢查語言切換動畫效果

## 支援的語言

- **正體中文 (zh_TW)**: 🇹🇼 繁體中文
- **英文 (en)**: 🇺🇸 English

## 測試環境需求

### 必要條件
1. **Docker 環境**: 確保 Docker 和 Docker Compose 已安裝並運行
2. **測試資料**: 管理員帳號 (username: admin, password: password123)
3. **語言檔案**: 完整的中英文語言檔案
4. **MCP 工具**: Playwright 和 MySQL MCP server 已配置

### 環境檢查
```bash
# 檢查 Docker 狀態
docker-compose ps

# 檢查測試資料
docker-compose exec app php artisan tinker --execute="
echo App\Models\User::where('username', 'admin')->exists() ? '✅ 管理員存在' : '❌ 管理員不存在';
"

# 檢查語言檔案
ls -la lang/zh_TW/auth.php lang/en/auth.php
```

## 執行測試

### 快速執行
```bash
# 使用提供的腳本執行所有測試
./run-multilingual-login-tests.sh
```

### 手動執行
```bash
# 1. 啟動 Docker 環境
docker-compose up -d

# 2. 確保測試資料存在
docker-compose exec app php artisan db:seed

# 3. 建立測試目錄
mkdir -p storage/screenshots/multilingual storage/logs

# 4. 執行測試腳本
php execute-multilingual-login-tests.php
```

## 測試結果

### 輸出檔案
- **JSON 報告**: `storage/logs/multilingual_login_test_report_*.json`
- **HTML 報告**: `storage/logs/multilingual_login_test_report_*.html`
- **截圖**: `storage/screenshots/multilingual/`

### 報告內容
- 測試執行摘要
- 各項測試的通過/失敗狀態
- 詳細的測試步驟記錄
- 每個測試階段的截圖
- 效能指標（響應時間等）

## 測試案例詳細說明

### 1. 語言切換測試
```
測試步驟:
1. 導航到登入頁面
2. 切換到英文
3. 驗證頁面內容翻譯
4. 切換到中文
5. 驗證頁面內容翻譯
6. 檢查切換響應時間

預期結果:
- 頁面內容正確翻譯
- 語言切換響應時間 < 2 秒
- 所有 UI 元素都使用正確語言
```

### 2. 主題切換按鈕測試
```
測試步驟:
1. 在中文模式下檢查主題按鈕文字
2. 點擊主題切換按鈕
3. 驗證按鈕文字更新
4. 切換到英文模式
5. 重複上述步驟

預期結果:
- 中文: "深色模式" ↔ "淺色模式"
- 英文: "Dark Mode" ↔ "Light Mode"
- 主題切換功能正常
```

### 3. 表單驗證測試
```
測試步驟:
1. 清空表單欄位
2. 嘗試提交表單
3. 檢查驗證錯誤訊息
4. 輸入過短的值
5. 檢查長度驗證訊息
6. 在不同語言下重複測試

預期結果:
- 中文: "請輸入使用者名稱", "請輸入密碼"
- 英文: "Please enter your username", "Please enter your password"
- 長度驗證訊息正確翻譯
```

## 故障排除

### 常見問題

#### 1. 測試資料不存在
```bash
# 解決方案: 執行 Seeder
docker-compose exec app php artisan db:seed
```

#### 2. 語言檔案缺失
```bash
# 檢查語言檔案
ls -la lang/zh_TW/ lang/en/

# 如果缺失，請確保已完成前面的任務
```

#### 3. MCP 工具連線失敗
```bash
# 檢查 Docker 容器狀態
docker-compose ps

# 重啟容器
docker-compose restart
```

#### 4. 權限問題
```bash
# 修復目錄權限
chmod -R 755 storage/
mkdir -p storage/screenshots/multilingual storage/logs
```

### 測試失敗分析

#### 語言切換失敗
- 檢查語言選擇器是否存在
- 驗證語言檔案完整性
- 確認 SetLocale 中介軟體正常運作

#### 翻譯內容不正確
- 檢查語言檔案中的翻譯鍵
- 驗證視圖檔案使用 `__()` 函數
- 確認語言檔案語法正確

#### 持久化失敗
- 檢查 Session 配置
- 驗證使用者表格的 locale 欄位
- 確認中介軟體執行順序

## 效能基準

### 響應時間標準
- **語言切換**: < 2000ms
- **頁面載入**: < 3000ms
- **表單驗證**: < 500ms
- **主題切換**: < 1000ms

### 成功率標準
- **整體測試成功率**: > 95%
- **關鍵功能測試**: 100%
- **翻譯完整性**: 100%

## 維護指南

### 新增語言支援
1. 建立新的語言檔案目錄
2. 複製並翻譯所有語言檔案
3. 更新 `supportedLocales` 陣列
4. 新增對應的測試案例

### 更新測試案例
1. 修改 `execute-multilingual-login-tests.php`
2. 更新預期的翻譯內容
3. 調整測試步驟和驗證邏輯
4. 更新文檔說明

### 測試資料維護
1. 定期檢查測試資料完整性
2. 更新語言檔案內容
3. 同步資料庫結構變更
4. 維護測試環境配置

## 相關檔案

### 測試檔案
- `tests/MCP/LoginPageMultilingualTest.php` - PHPUnit 測試類別
- `execute-multilingual-login-tests.php` - MCP 測試執行腳本
- `run-multilingual-login-tests.sh` - 測試執行腳本

### 語言檔案
- `lang/zh_TW/auth.php` - 中文認證翻譯
- `lang/en/auth.php` - 英文認證翻譯
- `lang/zh_TW/theme.php` - 中文主題翻譯
- `lang/en/theme.php` - 英文主題翻譯

### 視圖檔案
- `resources/views/admin/auth/login.blade.php` - 登入頁面
- `resources/views/livewire/admin/auth/login-form.blade.php` - 登入表單元件

### 元件檔案
- `app/Livewire/Admin/Auth/LoginForm.php` - 登入表單 Livewire 元件
- `app/Livewire/Admin/Layout/LanguageSelector.php` - 語言選擇器元件

這個測試系統確保登入頁面的多語系功能在所有支援的語言下都能正常運作，提供一致且優質的使用者體驗。