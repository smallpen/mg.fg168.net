# Playwright MCP 替代方案

## 🎯 當前狀況

- ✅ **MySQL MCP Server** - 正常工作
- ❌ **Playwright MCP Server** - 連接失敗（已暫時禁用）

## 🚀 替代解決方案

### 方案 1: 使用 WSL 中的 Playwright（推薦）

既然我們的開發環境在 WSL 中，可以直接在 WSL 中使用 Playwright：

```bash
# 在 WSL 中安裝 Playwright
npm install playwright
npx playwright install

# 建立測試腳本
```

### 方案 2: 建立簡單的瀏覽器測試腳本

```javascript
// browser-test.js - 在 WSL 中使用
const { chromium } = require('playwright');

async function testLogin() {
    const browser = await chromium.launch({ headless: false });
    const page = await browser.newPage();
    
    try {
        // 導航到登入頁面
        await page.goto('http://localhost/admin/login');
        
        // 填寫表單
        await page.fill('input[name="username"]', 'admin');
        await page.fill('input[name="password"]', 'admin123');
        
        // 提交表單
        await page.click('button[type="submit"]');
        
        // 等待重定向
        await page.waitForURL('**/admin/dashboard');
        
        console.log('✅ 登入測試成功');
        
        // 截圖
        await page.screenshot({ path: 'login-success.png' });
        
    } catch (error) {
        console.log('❌ 登入測試失敗:', error.message);
    } finally {
        await browser.close();
    }
}

testLogin();
```

### 方案 3: 使用 Docker 中的 Playwright

```bash
# 在專案中添加 Playwright 到 Docker
docker-compose exec app npm install playwright
docker-compose exec app npx playwright install
```

## 🔧 當前可用的工具

### MySQL MCP 功能（完全可用）

```javascript
// 檢查使用者
mcp_mysql_execute_query({
  query: "SELECT id, username, name, email FROM users LIMIT 5",
  database: "laravel_admin"
})

// 檢查資料表
mcp_mysql_list_tables({ database: "laravel_admin" })

// 檢查資料表結構
mcp_mysql_describe_table({ table: "users", database: "laravel_admin" })
```

### WSL 中的直接命令

```bash
# 在 WSL 中直接使用 Docker 命令
docker-compose exec app php artisan test
docker-compose exec app php artisan migrate:status

# 使用 curl 測試 API
curl -X POST http://localhost/admin/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'
```

## 📋 建議的開發流程

### 1. 資料庫操作 - 使用 MySQL MCP
- 查詢資料
- 驗證資料完整性
- 檢查測試資料

### 2. 前端測試 - 使用 WSL Playwright
- 建立獨立的測試腳本
- 在 WSL 中直接執行
- 生成截圖和報告

### 3. API 測試 - 使用 curl 或 Postman
- 測試 API 端點
- 驗證回應格式
- 檢查錯誤處理

## 🎉 優勢

1. **MySQL MCP 完全可用** - 資料庫操作無問題
2. **WSL 環境一致** - 與開發環境相同
3. **更好的控制** - 可以自訂測試腳本
4. **更快的執行** - 不需要跨環境調用

## 🚀 立即可用的測試

現在你可以立即使用：

```javascript
// 測試資料庫連接
mcp_mysql_execute_query({
  query: "SELECT COUNT(*) as user_count FROM users",
  database: "laravel_admin"
})

// 檢查管理員帳號
mcp_mysql_execute_query({
  query: "SELECT username, name, email, is_active FROM users WHERE username = 'admin'",
  database: "laravel_admin"
})
```

這樣你就可以開始進行資料庫相關的開發和測試了！