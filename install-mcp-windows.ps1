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
Write-Host "📝 下一步：" -ForegroundColor Cyan
Write-Host "   1. 更新 .kiro/settings/mcp.json 配置" -ForegroundColor White
Write-Host "   2. 重新啟動 Kiro IDE" -ForegroundColor White
Write-Host "   3. 檢查 MCP SERVERS 區塊狀態" -ForegroundColor White