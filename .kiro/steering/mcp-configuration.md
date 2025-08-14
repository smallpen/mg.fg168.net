# MCP (Model Context Protocol) 配置指南

## 已安裝的 MCP Servers

本專案已配置以下 MCP servers，在開發過程中應優先使用：

> **配置參考**: 請參考 `.kiro/steering/mcp-config-example.json` 檔案中的完整配置範例

### 1. Playwright MCP Server
- **用途**: 瀏覽器自動化和端到端測試
- **主要功能**: 頁面導航、元素操作、截圖、表單填寫
- **適用場景**: UI 測試、使用者流程驗證、視覺回歸測試

### 2. MySQL MCP Server  
- **用途**: 資料庫查詢和資料驗證
- **主要功能**: SQL 查詢執行、資料表結構檢視、資料操作
- **適用場景**: 資料驗證、偵錯、測試資料準備

## 使用優先級

### 開發階段工具選擇

#### 資料庫相關操作
1. **優先使用**: MySQL MCP Server
2. **次要選擇**: Docker 容器內的 mysql 命令
3. **避免使用**: 直接的 SQL 檔案操作

#### 前端測試和驗證
1. **優先使用**: Playwright MCP Server
2. **次要選擇**: 手動瀏覽器測試
3. **補充工具**: Laravel Dusk（如有需要）

#### 整合測試
1. **組合使用**: Playwright + MySQL MCP
2. **流程**: Playwright 執行操作 → MySQL 驗證資料
3. **記錄**: 截圖 + 資料查詢結果

## 實際應用範例

### 使用者管理功能測試

#### 1. 建立使用者測試
```javascript
// 使用 Playwright 填寫表單
await playwright.navigate('/admin/users/create');
await playwright.fill('input[name="username"]', 'newuser');
await playwright.fill('input[name="name"]', '新使用者');
await playwright.fill('input[name="email"]', 'newuser@example.com');
await playwright.click('button[type="submit"]');
```

```sql
-- 使用 MySQL 驗證資料
SELECT id, username, name, email, created_at 
FROM users 
WHERE username = 'newuser';
```

#### 2. 角色指派測試
```javascript
// Playwright 操作
await playwright.click('button[data-action="assign-role"]');
await playwright.select('select[name="role"]', 'admin');
await playwright.click('button[data-action="save"]');
```

```sql
-- MySQL 驗證
SELECT u.username, r.name as role_name
FROM users u
JOIN user_roles ur ON u.id = ur.user_id  
JOIN roles r ON ur.role_id = r.id
WHERE u.username = 'newuser';
```

#### 3. 軟刪除測試
```javascript
// Playwright 執行刪除
await playwright.click('button[data-action="delete-user"]');
await playwright.click('button[data-confirm="yes"]');
```

```sql
-- MySQL 檢查軟刪除狀態
SELECT username, is_active, deleted_at
FROM users 
WHERE username = 'newuser';
```

## 偵錯工作流程

### 1. 問題重現
- 使用 Playwright 重現使用者操作步驟
- 記錄每個步驟的截圖
- 捕獲 console 錯誤訊息

### 2. 資料狀態檢查
- 使用 MySQL 查詢相關資料表
- 檢查資料完整性和關聯
- 確認觸發器和約束是否正常

### 3. 問題定位
- 對比預期資料和實際資料
- 檢查前端 JavaScript 錯誤
- 驗證後端邏輯執行結果

### 4. 修復驗證
- 修復程式碼後重新執行 Playwright 測試
- 使用 MySQL 確認資料修復正確
- 建立回歸測試防止問題再次發生

## 效能考量

### Playwright 最佳化
- 重複使用瀏覽器實例
- 適當設定等待超時時間
- 使用無頭模式提高執行速度
- 並行執行獨立測試

### MySQL 查詢最佳化
- 使用適當的索引
- 限制查詢結果數量
- 避免複雜的 JOIN 操作
- 使用 EXPLAIN 分析查詢計畫

## 安全注意事項

### 測試資料管理
- 使用專用的測試資料庫
- 定期清理測試資料
- 避免在生產環境執行測試
- 保護敏感測試資料

### 權限控制
- 限制 MCP server 的資料庫權限
- 使用唯讀帳號進行查詢驗證
- 避免在測試中執行危險操作
- 記錄所有資料庫操作

## 故障排除

### MCP Server 連線問題
1. 檢查 Docker 容器狀態
2. 驗證網路連線
3. 確認服務埠號正確
4. 檢查防火牆設定

### 工具整合問題
1. 確認 MCP 配置檔案正確
2. 檢查工具版本相容性
3. 驗證環境變數設定
4. 查看錯誤日誌

遵循這些配置指南可以確保 MCP 工具的有效使用，提高開發和測試效率。