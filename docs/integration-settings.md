# 整合設定管理功能

## 概述

整合設定管理功能提供了一個統一的介面來管理第三方服務的整合設定，包含社群媒體登入、雲端儲存、支付閘道等服務的配置。

## 主要功能

### 1. 分析工具設定
- Google Analytics 追蹤 ID 管理
- Google Tag Manager 容器 ID 管理
- 支援格式驗證

### 2. 社群媒體登入設定
- **Google OAuth**: 支援 Google 社群登入
- **Facebook OAuth**: 支援 Facebook 社群登入  
- **GitHub OAuth**: 支援 GitHub 社群登入
- 自動依賴驗證（啟用服務時必須提供必要的 Client ID 和 Secret）

### 3. 雲端儲存設定
- **AWS S3**: Amazon S3 雲端儲存整合
  - Access Key 和 Secret Key 管理
  - 區域和儲存桶設定
- **Google Drive**: Google Drive 雲端儲存整合
  - Client ID 和 Client Secret 管理

### 4. 支付閘道設定
- **Stripe**: Stripe 支付閘道整合
  - 可公開金鑰和秘密金鑰管理
  - Webhook 密鑰設定
- **PayPal**: PayPal 支付閘道整合
  - Client ID 和 Client Secret 管理
  - 沙盒/正式環境模式切換

### 5. 自訂 API 金鑰管理
- 新增、編輯、刪除自訂 API 金鑰
- 支援金鑰描述和分類
- 防止重複名稱

## 安全功能

### 1. 資料加密
- 所有敏感資料（API 金鑰、密碼、秘密等）自動加密儲存
- 使用 Laravel 內建的加密服務
- 支援加密資料的安全顯示（遮罩處理）

### 2. 加密規則
系統會自動識別以下類型的設定並進行加密：
- 包含 `secret` 的設定鍵
- 包含 `password` 的設定鍵
- 包含 `key` 的設定鍵
- 包含 `token` 的設定鍵
- `client_secret` 相關設定
- `webhook_secret` 相關設定
- `api_keys` 設定

### 3. 資料驗證
- 格式驗證（如 Google Analytics ID 格式）
- 依賴關係驗證（啟用服務時檢查必要欄位）
- API 金鑰格式驗證

## 整合測試功能

### 支援的測試類型
1. **Google OAuth**: 測試 Google OAuth 端點連線
2. **Facebook OAuth**: 測試 Facebook Graph API 連線
3. **GitHub OAuth**: 測試 GitHub API 端點連線
4. **AWS S3**: 驗證 AWS S3 設定完整性
5. **Google Drive**: 測試 Google Drive API 端點
6. **Stripe**: 測試 Stripe API 連線
7. **PayPal**: 測試 PayPal OAuth 連線

### 測試結果
- 即時顯示測試結果
- 成功/失敗狀態指示
- 詳細錯誤訊息
- 載入狀態指示器

## 使用方式

### 1. 存取整合設定
- 導航至 `管理後台 > 系統設定 > 整合設定`
- 需要 `system.settings` 權限

### 2. 設定第三方服務
1. 選擇對應的分頁（分析工具、社群登入、雲端儲存、支付閘道、API 金鑰）
2. 啟用所需的服務
3. 填寫必要的設定資訊
4. 點擊「測試連線」驗證設定
5. 儲存設定

### 3. 管理 API 金鑰
1. 切換至「API 金鑰」分頁
2. 點擊「新增 API 金鑰」
3. 填寫金鑰名稱、值和描述
4. 儲存後金鑰會自動加密

## 技術實作

### 1. 核心元件
- `IntegrationSettings.php`: 主要 Livewire 元件
- `EncryptionService.php`: 加密服務
- `SettingsRepository.php`: 設定資料存取層

### 2. 設定儲存
- 所有設定儲存在 `settings` 資料表
- 敏感資料自動加密
- 支援設定變更歷史記錄

### 3. 快取機制
- 設定資料自動快取
- 更新時自動清除相關快取
- 支援分類快取

### 4. 驗證機制
- 前端即時驗證
- 後端資料驗證
- 依賴關係檢查

## 擴展性

### 新增整合服務
1. 在 `config/system-settings.php` 中新增設定定義
2. 在 `IntegrationSettings.php` 中新增對應的屬性和方法
3. 在視圖中新增對應的 UI 元素
4. 實作測試方法

### 自訂加密規則
在 `SettingsRepository.php` 的 `shouldEncryptSetting()` 方法中新增自訂規則。

## 注意事項

1. **權限管理**: 確保只有授權使用者可以存取整合設定
2. **資料備份**: 定期備份設定資料，特別是加密的敏感資料
3. **金鑰輪換**: 定期更新 API 金鑰和秘密
4. **測試環境**: 在正式環境部署前，先在測試環境驗證所有整合
5. **監控**: 監控整合服務的連線狀態和錯誤率

## 故障排除

### 常見問題
1. **加密失敗**: 檢查 Laravel APP_KEY 是否正確設定
2. **測試連線失敗**: 檢查網路連線和防火牆設定
3. **設定無法儲存**: 檢查資料庫連線和權限
4. **解密失敗**: 可能是 APP_KEY 變更導致，需要重新設定

### 日誌檢查
- 查看 `storage/logs/laravel.log` 中的錯誤訊息
- 檢查整合測試的詳細錯誤資訊
- 監控設定變更歷史記錄