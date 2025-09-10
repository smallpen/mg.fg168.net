# 手動下載 Playwright 瀏覽器指南

## 🎯 為什麼要手動下載？

`npx playwright install` 會下載 Chromium、Firefox 和 WebKit，總共約 300-500MB，在網路較慢時會很耗時。

## 🚀 快速解決方案

### 選項 1: 只下載 Chromium（最小安裝）
```powershell
npx playwright install chromium
```
這只會下載 Chromium 瀏覽器，大約 150MB。

### 選項 2: 使用系統瀏覽器
```powershell
# 設定使用系統已安裝的 Chrome
$env:PLAYWRIGHT_BROWSERS_PATH = "0"
```

### 選項 3: 離線安裝
如果你有其他電腦已經下載過：

1. **找到瀏覽器快取位置**：
   ```powershell
   # Windows 預設位置
   %USERPROFILE%\AppData\Local\ms-playwright
   ```

2. **複製整個資料夾**到目標電腦的相同位置

3. **設定環境變數**：
   ```powershell
   $env:PLAYWRIGHT_BROWSERS_PATH = "C:\path\to\browsers"
   ```

## 🔧 測試是否需要瀏覽器

先執行我們的快速測試：
```powershell
.\quick-playwright-test.ps1
```

如果 MCP server 可以啟動，那麼：
- ✅ **連接測試**不需要瀏覽器
- ✅ **基本功能**可以正常使用
- ⚠️ **實際網頁操作**時才需要瀏覽器

## 📋 建議流程

1. **先測試 MCP 連接**（不下載瀏覽器）
2. **確認基本功能正常**
3. **需要時再下載特定瀏覽器**

這樣可以節省大量時間和頻寬！