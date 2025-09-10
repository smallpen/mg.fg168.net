# MCP Servers 使用指南

## 🎉 連接狀態

✅ **Playwright MCP Server** - 已正常連接  
✅ **MySQL MCP Server** - 已正常連接  

## 📋 可用工具

### MySQL MCP Server 工具
- `list_databases` - 列出所有資料庫
- `list_tables` - 列出指定資料庫的資料表
- `describe_table` - 查看資料表結構
- `execute_query` - 執行 SQL 查詢

### Playwright MCP Server 工具
- `playwright_navigate` - 導航到指定 URL
- `playwright_screenshot` - 截圖
- `playwright_get_visible_text` - 取得頁面可見文字
- `playwright_get_visible_html` - 取得頁面 HTML
- `playwright_fill` - 填寫表單欄位
- `playwright_click` - 點擊元素
- `playwright_select` - 選擇下拉選項
- `playwright_evaluate` - 執行 JavaScript
- `playwright_console_logs` - 查看 console 日誌
- `playwright_close` - 關閉瀏覽器

## 🚀 快速開始

### 1. 測試資料庫連接
```javascript
// 列出所有資料表
mcp_mysql_list_tables({ database: "laravel_admin" })

// 查看使用者資料表結構
mcp_mysql_describe_table({ table: "users", database: "laravel_admin" })

// 查詢使用者數量
mcp_mysql_execute_query({ 
  query: "SELECT COUNT(*) as count FROM users", 
  database: "laravel_admin" 
})
```

### 2. 測試網頁功能
```javascript
// 導航到登入頁面
mcp_playwright_playwright_navigate({ 
  url: "http://localhost/admin/login", 
  headless: true 
})

// 截圖
mcp_playwright_playwright_screenshot({ 
  name: "login-page", 
  savePng: true 
})

// 取得頁面內容
mcp_playwright_playwright_get_visible_text()
```

## 🔧 常用測試場景

### 登入流程測試
```javascript
// 1. 導航到登入頁面
mcp_playwright_playwright_navigate({ url: "http://localhost/admin/login" })

// 2. 填寫登入表單
mcp_playwright_playwright_fill({ selector: 'input[name="username"]', value: 'admin' })
mcp_playwright_playwright_fill({ selector: 'input[name="password"]', value: 'admin123' })

// 3. 提交表單
mcp_playwright_playwright_click({ selector: 'button[type="submit"]' })

// 4. 驗證登入成功
mcp_mysql_execute_query({
  query: "SELECT username, last_login_at FROM users WHERE username = 'admin'",
  database: "laravel_admin"
})
```

### 使用者管理測試
```javascript
// 1. 檢查現有使用者
mcp_mysql_execute_query({
  query: "SELECT id, username, name, email FROM users ORDER BY created_at DESC LIMIT 5",
  database: "laravel_admin"
})

// 2. 導航到使用者列表
mcp_playwright_playwright_navigate({ url: "http://localhost/admin/users" })

// 3. 截圖記錄
mcp_playwright_playwright_screenshot({ name: "user-list", savePng: true })
```

## 📊 資料庫查詢範例

### 檢查系統狀態
```sql
-- 使用者統計
SELECT COUNT(*) as total_users, 
       SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users
FROM users;

-- 角色分佈
SELECT r.name, r.display_name, COUNT(ur.user_id) as user_count
FROM roles r
LEFT JOIN user_roles ur ON r.id = ur.role_id
GROUP BY r.id, r.name, r.display_name;

-- 權限統計
SELECT module, COUNT(*) as permission_count
FROM permissions
GROUP BY module
ORDER BY module;
```

### 檢查測試資料
```sql
-- 檢查管理員帳號
SELECT username, name, email, is_active, created_at
FROM users
WHERE username = 'admin';

-- 檢查角色權限
SELECT r.name as role_name, COUNT(rp.permission_id) as permission_count
FROM roles r
LEFT JOIN role_permissions rp ON r.id = rp.role_id
GROUP BY r.id, r.name;
```

## 🎭 Playwright 操作範例

### 表單操作
```javascript
// 填寫複雜表單
mcp_playwright_playwright_fill({ selector: '#username', value: 'testuser' })
mcp_playwright_playwright_fill({ selector: '#name', value: '測試使用者' })
mcp_playwright_playwright_fill({ selector: '#email', value: 'test@example.com' })
mcp_playwright_playwright_select({ selector: '#role', value: 'user' })

// 提交並等待
mcp_playwright_playwright_click({ selector: 'button[type="submit"]' })
```

### 頁面驗證
```javascript
// 檢查頁面標題
mcp_playwright_playwright_evaluate({ 
  script: 'document.title' 
})

// 檢查特定元素是否存在
mcp_playwright_playwright_evaluate({ 
  script: 'document.querySelector(".alert-success") ? "success" : "not found"' 
})

// 取得表格資料
mcp_playwright_playwright_evaluate({ 
  script: 'Array.from(document.querySelectorAll("table tbody tr")).length' 
})
```

## 🔍 偵錯技巧

### 查看錯誤訊息
```javascript
// 查看 console 錯誤
mcp_playwright_playwright_console_logs({ type: "error" })

// 查看所有 console 訊息
mcp_playwright_playwright_console_logs({ type: "all", limit: 10 })
```

### 資料一致性檢查
```javascript
// 前端操作後檢查資料庫
mcp_playwright_playwright_click({ selector: '.delete-button' })

// 確認資料是否被軟刪除
mcp_mysql_execute_query({
  query: "SELECT deleted_at FROM users WHERE id = 1",
  database: "laravel_admin"
})
```

## ⚠️ 注意事項

1. **測試資料**: 確保測試前已執行 `docker-compose exec app php artisan db:seed`
2. **Docker 服務**: 確保所有 Docker 容器正在運行
3. **權限問題**: 某些操作需要管理員權限
4. **瀏覽器資源**: 記得適時關閉 Playwright 瀏覽器實例
5. **資料庫連接**: MySQL MCP 使用連接池，通常不需要手動關閉

## 🛠️ 故障排除

### MCP Server 無法啟動
```bash
# 檢查 Docker 服務
docker-compose ps

# 重啟服務
docker-compose restart

# 檢查資料庫連接
docker-compose exec mysql mysql -u laravel -psecret -e "SELECT 1"
```

### 權限錯誤
```bash
# 重建測試資料
docker-compose exec app php artisan migrate:fresh --seed
```

現在你可以開始使用 MCP servers 進行開發和測試了！🎉