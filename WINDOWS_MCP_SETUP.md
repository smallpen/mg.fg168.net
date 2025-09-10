# Windows 端 MCP Servers 安裝指南

## 🎯 目標
在 Windows 端直接安裝 MCP servers，避免 WSL 跨環境調用問題。

## 📋 前置需求

### 1. 安裝 Node.js (Windows 版本)
如果還沒安裝，請下載並安裝：
- 前往 https://nodejs.org/
- 下載 LTS 版本 (推薦 18.x 或 20.x)
- 執行安裝程式

### 2. 驗證 Node.js 安裝
在 Windows PowerShell 或 CMD 中執行：
```cmd
node --version
npm --version
```

## 🚀 安裝 MCP Servers

### 步驟 1: 安裝 Playwright MCP Server
在 Windows PowerShell 中執行：
```powershell
npm install -g playwright-mcp-server
```

### 步驟 2: 安裝 MySQL MCP Server
```powershell
npm install -g mysql-mcp-server
```

### 步驟 3: 驗證安裝
```powershell
# 檢查 Playwright MCP Server
playwright-mcp-server --help

# 檢查 MySQL MCP Server (需要環境變數)
$env:MYSQL_HOST="127.0.0.1"
$env:MYSQL_PORT="3306"
$env:MYSQL_USER="laravel"
$env:MYSQL_PASSWORD="secret"
$env:MYSQL_DATABASE="laravel_admin"
mysql-mcp-server --help
```

## 🔧 更新 MCP 配置

安裝完成後，需要更新 `.kiro/settings/mcp.json` 配置：

```json
{
  "mcpServers": {
    "playwright": {
      "command": "playwright-mcp-server",
      "args": [],
      "disabled": false,
      "autoApprove": [
        "playwright_navigate",
        "playwright_screenshot",
        "playwright_get_visible_text",
        "playwright_get_visible_html",
        "playwright_fill",
        "playwright_click",
        "playwright_select",
        "playwright_evaluate",
        "playwright_console_logs",
        "playwright_close"
      ]
    },
    "mysql": {
      "command": "mysql-mcp-server",
      "args": [],
      "env": {
        "MYSQL_HOST": "127.0.0.1",
        "MYSQL_PORT": "3306",
        "MYSQL_USER": "laravel",
        "MYSQL_PASSWORD": "secret",
        "MYSQL_DATABASE": "laravel_admin"
      },
      "disabled": false,
      "autoApprove": [
        "list_databases",
        "list_tables",
        "describe_table",
        "execute_query"
      ]
    }
  }
}
```

## 🔍 故障排除

### 問題 1: npm 安裝失敗
**解決方案**：
```powershell
# 清除 npm 快取
npm cache clean --force

# 使用管理員權限重新安裝
npm install -g playwright-mcp-server --force
npm install -g mysql-mcp-server --force
```

### 問題 2: 找不到命令
**解決方案**：
1. 檢查 npm 全域安裝路徑：
   ```powershell
   npm config get prefix
   ```
2. 確保該路徑在 Windows PATH 環境變數中

### 問題 3: MySQL 連接失敗
**解決方案**：
1. 確保 Docker 容器正在運行
2. 檢查 MySQL 連接參數是否正確
3. 測試連接：
   ```powershell
   # 在 WSL 中測試
   wsl docker-compose exec mysql mysql -u laravel -psecret -e "SELECT 1"
   ```

## 📝 安裝腳本

為了方便，我建立了一個自動安裝腳本：

### install-mcp-windows.ps1
```powershell
# Windows MCP Servers 安裝腳本
Write-Host "🚀 開始安裝 MCP Servers..." -ForegroundColor Green

# 檢查 Node.js
try {
    $nodeVersion = node --version
    Write-Host "✅ Node.js 版本: $nodeVersion" -ForegroundColor Green
} catch {
    Write-Host "❌ 請先安裝 Node.js: https://nodejs.org/" -ForegroundColor Red
    exit 1
}

# 安裝 Playwright MCP Server
Write-Host "📦 安裝 Playwright MCP Server..." -ForegroundColor Yellow
try {
    npm install -g playwright-mcp-server
    Write-Host "✅ Playwright MCP Server 安裝成功" -ForegroundColor Green
} catch {
    Write-Host "❌ Playwright MCP Server 安裝失敗" -ForegroundColor Red
}

# 安裝 MySQL MCP Server
Write-Host "📦 安裝 MySQL MCP Server..." -ForegroundColor Yellow
try {
    npm install -g mysql-mcp-server
    Write-Host "✅ MySQL MCP Server 安裝成功" -ForegroundColor Green
} catch {
    Write-Host "❌ MySQL MCP Server 安裝失敗" -ForegroundColor Red
}

# 驗證安裝
Write-Host "🔍 驗證安裝..." -ForegroundColor Yellow

try {
    playwright-mcp-server --help | Out-Null
    Write-Host "✅ Playwright MCP Server 可正常執行" -ForegroundColor Green
} catch {
    Write-Host "❌ Playwright MCP Server 執行失敗" -ForegroundColor Red
}

# 設定環境變數並測試 MySQL MCP Server
$env:MYSQL_HOST="127.0.0.1"
$env:MYSQL_PORT="3306"
$env:MYSQL_USER="laravel"
$env:MYSQL_PASSWORD="secret"
$env:MYSQL_DATABASE="laravel_admin"

try {
    mysql-mcp-server --help | Out-Null
    Write-Host "✅ MySQL MCP Server 可正常執行" -ForegroundColor Green
} catch {
    Write-Host "❌ MySQL MCP Server 執行失敗" -ForegroundColor Red
}

Write-Host "🎉 安裝完成！請更新 .kiro/settings/mcp.json 配置檔案" -ForegroundColor Green
```

## 🔄 下一步

1. **執行安裝腳本**或手動安裝 MCP servers
2. **更新 MCP 配置檔案**
3. **重新啟動 Kiro IDE**
4. **檢查 MCP SERVERS 區塊狀態**

安裝完成後，MCP servers 將直接在 Windows 環境中運行，避免 WSL 跨環境調用的問題。