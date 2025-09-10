# 代理管理導航功能測試指南

## 功能概述

代理管理列表已經更新，現在支援以下功能：

### 1. 階層式導航
- **預設顯示**：第一層代理
- **點擊代理名稱**：檢視該代理的下層代理
- **點擊下層統計**：檢視下層代理或直屬玩家
- **麵包屑導航**：顯示當前位置和路徑

### 2. 智慧建立代理按鈕
- **根層級**：顯示「建立第一層代理」
- **代理層級**：顯示「建立下層代理」，自動設定上層代理
- **玩家檢視**：隱藏建立代理按鈕

### 3. 雙重檢視模式
- **代理檢視**：顯示代理列表
- **玩家檢視**：顯示選定代理的直屬玩家

## 測試步驟

### 基本導航測試

1. **訪問代理管理頁面**
   ```
   http://localhost/admin/channels/agents
   ```

2. **檢查初始狀態**
   - 應該顯示第一層代理
   - 建立代理按鈕顯示「建立第一層代理」
   - 沒有麵包屑導航

3. **點擊代理名稱**
   - 應該進入該代理的下層檢視
   - 麵包屑顯示路徑
   - 建立代理按鈕變為「建立下層代理」

4. **點擊下層統計**
   - 點擊代理數量：檢視下層代理
   - 點擊玩家數量：檢視直屬玩家

### 建立代理功能測試

1. **從根層級建立代理**
   - 點擊「建立第一層代理」
   - 表單應該顯示前置符號選擇
   - 不應該有上層代理選擇

2. **從代理層級建立下層代理**
   - 進入某個代理的檢視
   - 點擊「建立下層代理」
   - 表單應該自動設定上層代理
   - 不應該顯示前置符號選擇

### URL 狀態持久化測試

1. **檢查 URL 參數**
   - 進入代理檢視後，URL 應包含 `currentAgentId` 參數
   - 切換到玩家檢視後，URL 應包含 `viewMode=players` 參數

2. **重新載入頁面**
   - 重新載入後應該保持相同的檢視狀態
   - 麵包屑和按鈕狀態應該正確

### 權限測試

1. **檢查權限控制**
   - 沒有 `channels.agents.create` 權限時不顯示建立按鈕
   - 沒有 `channels.players.view` 權限時無法檢視玩家

## 預期行為

### 麵包屑導航
```
第一層代理 > 代理A > 代理B > 直屬玩家
```

### 建立代理按鈕文字
- 根層級：「建立第一層代理」
- 代理層級：「建立下層代理」
- 玩家檢視：不顯示

### URL 範例
```
# 根層級
/admin/channels/agents

# 檢視代理 ID 5 的下層代理
/admin/channels/agents?currentAgentId=5&viewMode=agents

# 檢視代理 ID 5 的直屬玩家
/admin/channels/agents?currentAgentId=5&viewMode=players
```

## 故障排除

### 常見問題

1. **點擊代理名稱沒有反應**
   - 檢查 JavaScript 控制台是否有錯誤
   - 確認 Livewire 已正確載入

2. **建立代理按鈕沒有自動設定上層代理**
   - 檢查 URL 參數是否正確傳遞
   - 確認 AgentForm 的 mount 方法接收 parentId 參數

3. **麵包屑導航不正確**
   - 檢查 Agent 模型的 agent_path 屬性
   - 確認代理關聯關係正確

### 除錯工具

1. **瀏覽器開發者工具**
   - 檢查網路請求
   - 查看 JavaScript 控制台錯誤

2. **Laravel 日誌**
   ```bash
   docker-compose exec app tail -f storage/logs/laravel.log
   ```

3. **Livewire 除錯**
   - 在 Livewire 元件中添加 `dd()` 或 `\Log::info()` 來除錯

## 資料庫需求

確保以下資料表和欄位存在：

### agents 資料表
- `id`, `name`, `username`, `account`, `email`
- `prefix`, `level`, `parent_id`
- `total_points`, `allocated_points`, `remaining_points`
- `is_active`, `created_by`, `notes`
- `created_at`, `updated_at`, `deleted_at`

### players 資料表
- `id`, `name`, `username`, `account`, `email`
- `agent_id`, `total_points`, `available_points`
- `is_active`, `created_by`, `notes`
- `created_at`, `updated_at`, `deleted_at`

## 效能考量

1. **查詢最佳化**
   - 使用 `with()` 預載入關聯
   - 使用 `withCount()` 計算下層統計

2. **分頁效能**
   - 限制每頁顯示筆數
   - 使用索引優化查詢

3. **快取策略**
   - 考慮快取代理層級結構
   - 快取統計資訊

這個新的代理管理系統提供了更直觀的階層式導航體驗，讓使用者可以輕鬆瀏覽多層級的代理結構。