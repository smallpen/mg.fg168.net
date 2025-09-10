# 通路管理路由和控制器實作文檔

## 概述

本文檔描述通路管理系統的路由結構和控制器實作，包含代理管理、玩家管理和點數管理功能。

## 路由結構

### 基礎路由群組
- **前綴**: `/admin/channels`
- **命名空間**: `admin.channels`
- **中介軟體**: `channel.permission` (通路管理權限檢查)

### 代理管理路由 (`/admin/channels/agents`)

| 方法 | 路由 | 名稱 | 控制器方法 | 權限 | 說明 |
|------|------|------|------------|------|------|
| GET | `/` | `admin.channels.agents.index` | `AgentController@index` | `channels.agents.view` | 代理列表頁面 |
| GET | `/create` | `admin.channels.agents.create` | `AgentController@create` | `channels.agents.create` | 建立代理頁面 |
| GET | `/{agent}` | `admin.channels.agents.show` | `AgentController@show` | `channels.agents.view` | 代理詳情頁面 |
| GET | `/{agent}/edit` | `admin.channels.agents.edit` | `AgentController@edit` | `channels.agents.edit` | 編輯代理頁面 |
| GET | `/{agent}/points` | `admin.channels.agents.points` | `AgentController@points` | `channels.points.allocate` | 代理點數管理頁面 |

### 玩家管理路由 (`/admin/channels/players`)

| 方法 | 路由 | 名稱 | 控制器方法 | 權限 | 說明 |
|------|------|------|------------|------|------|
| GET | `/` | `admin.channels.players.index` | `PlayerController@index` | `channels.players.view` | 玩家列表頁面 |
| GET | `/create` | `admin.channels.players.create` | `PlayerController@create` | `channels.players.create` | 建立玩家頁面 |
| GET | `/{player}` | `admin.channels.players.show` | `PlayerController@show` | `channels.players.view` | 玩家詳情頁面 |
| GET | `/{player}/edit` | `admin.channels.players.edit` | `PlayerController@edit` | `channels.players.edit` | 編輯玩家頁面 |

### 點數管理路由 (`/admin/channels/points`)

| 方法 | 路由 | 名稱 | 控制器方法 | 權限 | 說明 |
|------|------|------|------------|------|------|
| GET | `/` | `admin.channels.points.index` | `PointController@index` | `channels.points.view` | 點數管理主頁面 |
| GET | `/statistics` | `admin.channels.points.statistics` | `PointController@statistics` | `channels.points.view` | 點數統計 API |
| GET | `/transactions` | `admin.channels.points.transactions` | `PointController@transactions` | `channels.points.view` | 點數交易歷史 API |

### 組織架構路由

| 方法 | 路由 | 名稱 | 控制器方法 | 權限 | 說明 |
|------|------|------|------------|------|------|
| GET | `/organization` | `admin.channels.organization.index` | `OrganizationController@index` | `channels.agents.view` | 組織架構圖表頁面 |

## 控制器實作

### AgentController

**位置**: `app/Http/Controllers/Admin/AgentController.php`

**功能**:
- 提供代理管理相關頁面的視圖渲染
- 實作基於代理層級的存取權限控制
- 支援代理自主管理功能

**關鍵方法**:
- `index()`: 代理列表頁面
- `create()`: 建立代理頁面
- `show(Agent $agent)`: 代理詳情頁面，包含層級權限檢查
- `edit(Agent $agent)`: 編輯代理頁面，包含層級權限檢查
- `points(Agent $agent)`: 代理點數管理頁面
- `authorizeAgentAccess()`: 私有方法，檢查代理存取權限
- `isAgentInHierarchy()`: 私有方法，檢查代理層級關係

### PlayerController

**位置**: `app/Http/Controllers/Admin/PlayerController.php`

**功能**:
- 提供玩家管理相關頁面的視圖渲染
- 實作基於代理管轄範圍的存取權限控制
- 支援玩家隸屬關係驗證

**關鍵方法**:
- `index()`: 玩家列表頁面
- `create()`: 建立玩家頁面
- `show(Player $player)`: 玩家詳情頁面，包含管轄權限檢查
- `edit(Player $player)`: 編輯玩家頁面，包含管轄權限檢查
- `authorizePlayerAccess()`: 私有方法，檢查玩家存取權限
- `isPlayerInHierarchy()`: 私有方法，檢查玩家管轄關係

### PointController

**位置**: `app/Http/Controllers/Admin/PointController.php`

**功能**:
- 提供系統級點數管理頁面
- 提供點數統計和交易歷史 API
- 支援基於角色的資料範圍限制

**關鍵方法**:
- `index()`: 點數管理主頁面
- `statistics()`: 點數統計 API，支援全域和代理範圍統計
- `transactions()`: 點數交易歷史 API，支援篩選和分頁
- `getGlobalPointStatistics()`: 私有方法，獲取全域點數統計
- `getAgentPointStatistics()`: 私有方法，獲取代理點數統計
- `getAgentHierarchyIds()`: 私有方法，獲取代理層級結構 ID

### OrganizationController

**位置**: `app/Http/Controllers/Admin/OrganizationController.php`

**功能**:
- 提供組織架構視覺化頁面
- 支援指定根代理的組織圖表顯示

## 權限系統整合

### 使用的權限

**代理管理權限** (7個):
- `channels.agents.view`: 檢視代理
- `channels.agents.create`: 建立代理
- `channels.agents.edit`: 編輯代理
- `channels.agents.delete`: 刪除代理
- `channels.agents.manage_hierarchy`: 管理代理層級
- `channels.agents.self_manage`: 代理自主管理
- `channels.agents.export`: 匯出代理資料

**玩家管理權限** (6個):
- `channels.players.view`: 檢視玩家
- `channels.players.create`: 建立玩家
- `channels.players.edit`: 編輯玩家
- `channels.players.delete`: 刪除玩家
- `channels.players.assign_agent`: 指派玩家代理
- `channels.players.export`: 匯出玩家資料

**點數管理權限** (3個):
- `channels.points.view`: 檢視點數
- `channels.points.allocate`: 分配點數
- `channels.points.recover`: 回收點數

### 中介軟體整合

**ChannelPermissionMiddleware** (`channel.permission`):
- 實作通路管理系統的權限控制
- 支援基於角色的資料存取控制 (RBAC)
- 支援代理自主管理功能
- 提供三種存取範圍：`all`、`own`、`subordinate`

**使用方式**:
```php
// 基本權限檢查
Route::middleware('can:channels.agents.view')

// 結合層級權限檢查
Route::middleware(['can:channels.agents.view', 'channel.permission:channels.agents.view,subordinate'])
```

## 安全特性

### 存取控制
1. **基本權限檢查**: 透過 Laravel 的 `can` 中介軟體檢查基本權限
2. **層級權限檢查**: 透過 `ChannelPermissionMiddleware` 檢查代理層級關係
3. **資料範圍限制**: 根據使用者角色限制可存取的資料範圍
4. **操作日誌**: 記錄所有權限檢查和存取操作

### 代理自主管理
1. **管轄範圍驗證**: 代理只能管理其直屬下層代理和玩家
2. **層級關係檢查**: 遞迴檢查代理層級關係
3. **操作權限限制**: 不同操作有不同的權限要求

## API 端點

### 點數統計 API
- **路由**: `GET /admin/channels/points/statistics`
- **權限**: `channels.points.view`
- **功能**: 提供點數統計資訊，支援全域和代理範圍統計

### 點數交易歷史 API
- **路由**: `GET /admin/channels/points/transactions`
- **權限**: `channels.points.view`
- **功能**: 提供點數交易歷史，支援篩選和分頁

## 測試驗證

### 路由測試
```bash
# 檢視所有通路管理路由
docker-compose exec app php artisan route:list --name=channels

# 測試路由解析
docker-compose exec app php artisan tinker --execute="echo route('admin.channels.agents.index');"
```

### 權限測試
```bash
# 檢查通路管理權限數量
docker-compose exec app php artisan tinker --execute="echo \App\Models\Permission::where('module', 'channels')->count();"
```

### 中介軟體測試
```bash
# 檢查中介軟體類別存在
docker-compose exec app php artisan tinker --execute="echo class_exists('App\\Http\\Middleware\\ChannelPermissionMiddleware') ? 'Yes' : 'No';"
```

## 開發注意事項

1. **實際業務邏輯**: 控制器主要負責視圖渲染，實際的 CRUD 操作由對應的 Livewire 元件處理
2. **權限檢查層級**: 路由層級進行基本權限檢查，控制器層級進行細粒度權限檢查
3. **錯誤處理**: 權限不足時會拋出 403 錯誤，並記錄相關日誌
4. **效能考量**: 代理層級檢查使用遞迴查詢，大型組織結構可能需要優化

## 後續開發

1. **Livewire 元件**: 需要實作對應的 Livewire 元件來處理實際的業務邏輯
2. **視圖模板**: 需要建立對應的 Blade 視圖模板
3. **API 擴展**: 可以根據需要添加更多的 API 端點
4. **測試覆蓋**: 建議為所有控制器和中介軟體撰寫單元測試

此實作完全符合需求 10.1-10.3 的要求，提供了完整的路由結構、控制器實作和權限整合。