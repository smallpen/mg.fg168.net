# 測試與偵錯工具指南

## 重要提醒

本專案已安裝並配置了 Playwright 和 MySQL MCP server，在進行開發、測試和偵錯時應優先使用這些工具。

## Playwright 測試工具

### 功能概述
Playwright 是一個強大的端到端測試工具，支援多種瀏覽器自動化操作。

### 主要用途
- **端到端測試**: 模擬真實使用者操作流程
- **UI 元件測試**: 測試 Livewire 元件的互動行為
- **瀏覽器自動化**: 自動化表單填寫、點擊、導航等操作
- **截圖和錄製**: 生成測試報告和除錯資訊

### 常用操作

#### 導航和頁面操作
```javascript
// 導航到指定頁面
await playwright.navigate('http://localhost/admin/users');

// 截圖
await playwright.screenshot({ name: 'user-list-page' });

// 取得頁面內容
await playwright.getVisibleText();
await playwright.getVisibleHtml();
```

#### 表單操作
```javascript
// 填寫表單
await playwright.fill('input[name="username"]', 'testuser');
await playwright.fill('input[name="email"]', 'test@example.com');

// 點擊按鈕
await playwright.click('button[type="submit"]');

// 選擇下拉選項
await playwright.select('select[name="role"]', 'admin');
```

#### 等待和驗證
```javascript
// 等待元素出現
await playwright.click('a[href="/admin/users/create"]');

// 執行 JavaScript
await playwright.evaluate('console.log("Test completed")');
```

### 測試場景建議

#### 使用者管理測試
- 建立新使用者流程
- 編輯使用者資料
- 刪除使用者確認對話框
- 角色指派功能
- 搜尋和篩選功能

#### Livewire 元件測試
- 即時驗證功能
- 動態表單更新
- 模態對話框操作
- 資料表格互動

## MySQL MCP Server

### 功能概述
MySQL MCP server 提供直接的資料庫查詢和操作能力，用於資料驗證和偵錯。

### 主要用途
- **資料驗證**: 確認測試後的資料狀態
- **資料庫偵錯**: 檢查資料完整性和關聯
- **效能分析**: 查看查詢執行計畫
- **資料清理**: 測試前後的資料準備和清理

### 常用操作

#### 資料庫結構查詢
```sql
-- 列出所有資料庫
SHOW DATABASES;

-- 列出資料表
SHOW TABLES;

-- 查看資料表結構
DESCRIBE users;
DESCRIBE roles;
DESCRIBE user_roles;
```

#### 資料驗證查詢
```sql
-- 檢查使用者資料
SELECT id, username, name, email, is_active, created_at 
FROM users 
WHERE username = 'testuser';

-- 檢查角色關聯
SELECT u.username, r.name as role_name, r.display_name
FROM users u
JOIN user_roles ur ON u.id = ur.user_id
JOIN roles r ON ur.role_id = r.id
WHERE u.username = 'testuser';

-- 檢查軟刪除狀態
SELECT id, username, name, deleted_at, is_active
FROM users
WHERE deleted_at IS NOT NULL;
```

#### 測試資料準備
```sql
-- 建立測試使用者
INSERT INTO users (username, name, email, password, is_active) 
VALUES ('testuser', '測試使用者', 'test@example.com', '$2y$10$...', 1);

-- 清理測試資料
DELETE FROM users WHERE username LIKE 'test%';
DELETE FROM user_roles WHERE user_id NOT IN (SELECT id FROM users);
```

## 開發工作流程建議

### 1. 功能開發階段
1. 使用 MySQL MCP server 檢查資料庫結構
2. 確認相關資料表和關聯設計
3. 實作功能程式碼
4. 使用 MySQL 驗證資料操作正確性

### 2. 測試階段
1. 撰寫單元測試（PHPUnit）
2. 使用 Playwright 建立端到端測試
3. 用 MySQL MCP server 驗證測試後的資料狀態
4. 截圖記錄測試結果

### 3. 偵錯階段
1. 使用 Playwright 重現問題場景
2. 透過 MySQL 查詢檢查資料狀態
3. 使用 Playwright 的 console logs 功能查看前端錯誤
4. 結合資料庫查詢和瀏覽器操作進行問題定位

## 最佳實踐

### Playwright 使用建議
- 優先使用 CSS 選擇器而非 XPath
- 適當使用等待機制避免競態條件
- 為重要操作建立截圖記錄
- 使用有意義的測試資料

### MySQL 查詢建議
- 使用 EXPLAIN 分析查詢效能
- 注意軟刪除資料的處理
- 檢查外鍵約束和資料完整性
- 適當使用索引優化查詢

### 整合使用建議
- 測試前使用 MySQL 準備測試資料
- 測試中使用 Playwright 執行操作
- 測試後使用 MySQL 驗證結果
- 清理測試資料避免影響後續測試

## 故障排除

### Playwright 常見問題
- 瀏覽器啟動失敗：檢查 Docker 容器狀態
- 元素找不到：使用更具體的選擇器
- 操作超時：增加等待時間或檢查頁面載入狀態

### MySQL 連線問題
- 連線失敗：確認 Docker 服務正常運行
- 權限錯誤：檢查資料庫使用者權限
- 查詢錯誤：驗證 SQL 語法和資料表結構

遵循這些指南可以提高開發效率，確保測試品質，並快速定位和解決問題。