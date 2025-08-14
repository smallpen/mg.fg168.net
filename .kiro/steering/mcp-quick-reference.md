# MCP 工具快速參考

## 常用 MySQL 查詢命令

### 基本資料庫操作
```javascript
// 列出所有資料庫
mcp_mysql_list_databases()

// 列出資料表（預設使用 laravel_admin 資料庫）
mcp_mysql_list_tables({ database: "laravel_admin" })

// 查看資料表結構
mcp_mysql_describe_table({ table: "users", database: "laravel_admin" })
```

### 使用者管理相關查詢
```javascript
// 查看所有使用者
mcp_mysql_execute_query({ 
  query: "SELECT id, username, name, email, is_active, created_at FROM users ORDER BY created_at DESC LIMIT 10",
  database: "laravel_admin"
})

// 查看使用者角色關聯
mcp_mysql_execute_query({
  query: `SELECT u.username, u.name, r.name as role_name, r.display_name 
           FROM users u 
           JOIN user_roles ur ON u.id = ur.user_id 
           JOIN roles r ON ur.role_id = r.id 
           WHERE u.username = 'admin'`,
  database: "laravel_admin"
})

// 檢查軟刪除的使用者
mcp_mysql_execute_query({
  query: "SELECT username, name, deleted_at, is_active FROM users WHERE deleted_at IS NOT NULL",
  database: "laravel_admin"
})
```

## 常用 Playwright 操作命令

### 基本瀏覽器操作
```javascript
// 導航到頁面
mcp_playwright_playwright_navigate({ 
  url: "http://localhost/admin/login",
  headless: true 
})

// 截圖
mcp_playwright_playwright_screenshot({ 
  name: "login-page",
  savePng: true 
})

// 取得頁面文字內容
mcp_playwright_playwright_get_visible_text()

// 取得頁面 HTML
mcp_playwright_playwright_get_visible_html({ 
  removeScripts: true,
  maxLength: 10000 
})
```

### 表單操作
```javascript
// 填寫輸入欄位
mcp_playwright_playwright_fill({ 
  selector: 'input[name="username"]', 
  value: 'admin' 
})

// 點擊按鈕
mcp_playwright_playwright_click({ 
  selector: 'button[type="submit"]' 
})

// 選擇下拉選項
mcp_playwright_playwright_select({ 
  selector: 'select[name="role"]', 
  value: 'admin' 
})
```

### 進階操作
```javascript
// 執行 JavaScript
mcp_playwright_playwright_evaluate({ 
  script: 'console.log("Current URL:", window.location.href)' 
})

// 查看 console 日誌
mcp_playwright_playwright_console_logs({ 
  type: "error",
  limit: 10 
})

// 關閉瀏覽器
mcp_playwright_playwright_close()
```

## 整合測試範例

### 測試使用者登入流程
```javascript
// 1. 導航到登入頁面
mcp_playwright_playwright_navigate({ url: "http://localhost/admin/login" })

// 2. 填寫登入表單
mcp_playwright_playwright_fill({ selector: 'input[name="username"]', value: 'admin' })
mcp_playwright_playwright_fill({ selector: 'input[name="password"]', value: 'password123' })

// 3. 提交表單
mcp_playwright_playwright_click({ selector: 'button[type="submit"]' })

// 4. 截圖記錄結果
mcp_playwright_playwright_screenshot({ name: "after-login", savePng: true })

// 5. 驗證資料庫中的登入記錄
mcp_mysql_execute_query({
  query: "SELECT username, last_login_at FROM users WHERE username = 'admin'",
  database: "laravel_admin"
})
```

### 測試使用者建立流程
```javascript
// 1. 導航到使用者建立頁面
mcp_playwright_playwright_navigate({ url: "http://localhost/admin/users/create" })

// 2. 填寫使用者資料
mcp_playwright_playwright_fill({ selector: 'input[name="username"]', value: 'testuser' })
mcp_playwright_playwright_fill({ selector: 'input[name="name"]', value: '測試使用者' })
mcp_playwright_playwright_fill({ selector: 'input[name="email"]', value: 'test@example.com' })

// 3. 提交表單
mcp_playwright_playwright_click({ selector: 'button[type="submit"]' })

// 4. 驗證使用者是否建立成功
mcp_mysql_execute_query({
  query: "SELECT * FROM users WHERE username = 'testuser'",
  database: "laravel_admin"
})
```

## 偵錯技巧

### 查看錯誤訊息
```javascript
// 查看瀏覽器 console 錯誤
mcp_playwright_playwright_console_logs({ type: "error" })

// 查看所有 console 訊息
mcp_playwright_playwright_console_logs({ type: "all", limit: 20 })
```

### 資料狀態檢查
```javascript
// 檢查特定使用者的完整資訊
mcp_mysql_execute_query({
  query: `SELECT u.*, 
                 GROUP_CONCAT(r.name) as roles,
                 COUNT(ur.role_id) as role_count
          FROM users u
          LEFT JOIN user_roles ur ON u.id = ur.user_id
          LEFT JOIN roles r ON ur.role_id = r.id
          WHERE u.username = 'testuser'
          GROUP BY u.id`,
  database: "laravel_admin"
})
```

### 清理測試資料
```javascript
// 刪除測試使用者（注意：僅在測試環境使用）
mcp_mysql_execute_query({
  query: "DELETE FROM users WHERE username LIKE 'test%'",
  database: "laravel_admin"
})
```

## 注意事項

1. **資料庫安全**: 僅在開發/測試環境使用 MySQL MCP
2. **瀏覽器資源**: 記得關閉 Playwright 瀏覽器實例
3. **測試隔離**: 使用唯一的測試資料避免衝突
4. **錯誤處理**: 適當處理網路和資料庫連線錯誤