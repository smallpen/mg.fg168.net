# 通路管理權限系統文檔

## 概述

通路管理權限系統提供了完整的權限控制和資料存取限制機制，確保不同角色的使用者只能存取和操作其權限範圍內的資料。

## 權限結構

### 代理管理權限 (7個)

| 權限名稱 | 顯示名稱 | 說明 |
|---------|---------|------|
| `channels.agents.view` | 檢視代理 | 可以檢視代理列表和詳細資訊 |
| `channels.agents.create` | 建立代理 | 可以建立新的代理 |
| `channels.agents.edit` | 編輯代理 | 可以編輯代理資訊 |
| `channels.agents.delete` | 刪除代理 | 可以刪除代理 |
| `channels.agents.manage_hierarchy` | 管理代理層級 | 可以管理代理的層級結構和上下層關係 |
| `channels.agents.self_manage` | 代理自主管理 | 代理可以管理自己的下層代理和玩家 |
| `channels.agents.export` | 匯出代理資料 | 可以匯出代理網絡結構和統計資料 |

### 玩家管理權限 (5個)

| 權限名稱 | 顯示名稱 | 說明 |
|---------|---------|------|
| `channels.players.view` | 檢視玩家 | 可以檢視玩家列表和詳細資訊 |
| `channels.players.create` | 建立玩家 | 可以建立新的玩家 |
| `channels.players.edit` | 編輯玩家 | 可以編輯玩家資訊 |
| `channels.players.delete` | 刪除玩家 | 可以刪除玩家 |
| `channels.players.assign_agent` | 指派玩家代理 | 可以變更玩家的隸屬代理 |

### 點數管理權限 (3個)

| 權限名稱 | 顯示名稱 | 說明 |
|---------|---------|------|
| `channels.points.view` | 檢視點數 | 可以檢視點數分配和交易記錄 |
| `channels.points.allocate` | 分配點數 | 可以分配點數給代理或玩家 |
| `channels.points.recover` | 回收點數 | 可以從代理或玩家回收點數 |

## 核心組件

### 1. ChannelPermissionMiddleware

通路管理權限檢查中介軟體，提供以下功能：

- 基本權限檢查
- 資料存取範圍控制
- 代理自主管理權限驗證
- 詳細的存取日誌記錄

#### 使用方式

```php
// 在路由中使用
Route::middleware(['channel.permission:channels.agents.view,all'])->group(function () {
    // 系統管理員才能存取的路由
});

Route::middleware(['channel.permission:channels.agents.view,subordinate'])->group(function () {
    // 代理可以存取下層資料的路由
});

Route::middleware(['channel.permission:channels.agents.view,own'])->group(function () {
    // 只能存取自己資料的路由
});
```

#### 存取範圍說明

- `all`: 可以存取所有資料（僅限系統管理員）
- `subordinate`: 可以存取下層資料（代理自主管理）
- `own`: 只能存取自己的資料

### 2. ChannelAccessControlService

通路管理存取控制服務，提供以下功能：

- 基於使用者權限的查詢範圍限制
- 代理和玩家的存取權限檢查
- 下層資料的遞迴權限驗證
- 可管理資料的範圍查詢

#### 主要方法

```php
// 限制代理查詢範圍
$service->scopeAgentsForUser($query, $user);

// 限制玩家查詢範圍
$service->scopePlayersForUser($query, $user);

// 檢查代理存取權限
$service->canAccessAgent($agent, $user);

// 檢查玩家存取權限
$service->canAccessPlayer($player, $user);

// 檢查是否可以建立下層代理
$service->canCreateSubAgent($parentAgent, $user);

// 檢查是否可以建立玩家
$service->canCreatePlayer($agent, $user);

// 取得使用者對應的代理記錄
$service->getUserAgent($user);

// 取得可管理的代理選項
$service->getManageableAgents($user);
```

### 3. HasChannelAccessControl Trait

為模型提供存取控制功能的特徵：

```php
// 在模型中使用
class Agent extends Model
{
    use HasChannelAccessControl;
}

// 使用方式
$agents = Agent::accessibleByUser($user)->get();
$canEdit = $agent->isEditableByUser($user);
$canDelete = $agent->isDeletableByUser($user);
```

### 4. AgentPolicy 和 PlayerPolicy

Laravel 策略類別，提供細粒度的權限檢查：

```php
// 在控制器中使用
$this->authorize('view', $agent);
$this->authorize('createSubAgent', [Agent::class, $parentAgent]);
$this->authorize('allocatePoints', $agent);

// 在 Blade 模板中使用
@can('update', $agent)
    <button>編輯代理</button>
@endcan

@can('createForAgent', [App\Models\Player::class, $agent])
    <button>建立玩家</button>
@endcan
```

## 角色權限配置

### 系統管理員 (admin)
- 擁有所有通路管理權限
- 可以存取所有代理和玩家資料
- 可以執行所有管理操作

### 代理 (agent)
- 擁有基本檢視權限
- 擁有 `channels.agents.self_manage` 權限
- 只能存取和管理自己管轄範圍內的資料
- 可以建立下層代理和玩家
- 可以分配和回收點數

### 一般使用者
- 沒有通路管理權限
- 無法存取任何代理或玩家資料

## 資料存取控制邏輯

### 代理資料存取

1. **系統管理員**: 可以存取所有代理
2. **代理使用者**: 只能存取自己和所有下層代理
3. **一般使用者**: 無法存取任何代理

### 玩家資料存取

1. **系統管理員**: 可以存取所有玩家
2. **代理使用者**: 只能存取隸屬於自己管轄範圍內代理的玩家
3. **一般使用者**: 無法存取任何玩家

### 使用者-代理關聯

系統透過以下方式建立使用者與代理的關聯：

1. **Email 匹配**: 使用者 email 與代理 email 相同
2. **Username 匹配**: 使用者 username 與代理 username 相同
3. **自定義關聯**: 可以透過額外的欄位建立關聯

## 安全特性

### 1. 多層權限檢查
- 基本權限驗證
- 資料存取範圍檢查
- 業務邏輯權限驗證

### 2. 詳細日誌記錄
- 記錄所有存取嘗試
- 區分成功和失敗的存取
- 包含完整的上下文資訊

### 3. 防護機制
- 自動登出非啟用使用者
- 阻止越權存取
- 提供適當的錯誤訊息

## 測試

### 單元測試
- `ChannelPermissionMiddlewareTest`: 測試中介軟體功能
- `ChannelAccessControlServiceTest`: 測試存取控制服務

### 功能測試
- `ChannelPermissionTest`: 測試完整的權限控制流程
- `ChannelPermissionBasicTest`: 測試基本功能

### 測試覆蓋範圍
- 權限檢查邏輯
- 資料存取限制
- 代理自主管理
- 錯誤處理機制

## 使用範例

### 在控制器中使用

```php
class AgentController extends Controller
{
    public function index()
    {
        // 使用中介軟體檢查權限
        $this->middleware('channel.permission:channels.agents.view,subordinate');
        
        // 使用服務限制查詢範圍
        $agents = Agent::accessibleByUser()->paginate(25);
        
        return view('admin.channels.agents.index', compact('agents'));
    }
    
    public function show(Agent $agent)
    {
        // 使用策略檢查權限
        $this->authorize('view', $agent);
        
        return view('admin.channels.agents.show', compact('agent'));
    }
}
```

### 在 Livewire 元件中使用

```php
class AgentList extends Component
{
    public function mount()
    {
        // 檢查基本權限
        $this->authorize('viewAny', Agent::class);
    }
    
    public function render()
    {
        // 使用存取控制服務限制查詢
        $agents = Agent::accessibleByUser()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', "%{$this->search}%");
            })
            ->paginate($this->perPage);
            
        return view('livewire.admin.channels.agent-list', compact('agents'));
    }
}
```

## 部署注意事項

1. **執行權限 Seeder**: 確保所有通路管理權限已建立
2. **角色配置**: 為相應角色分配適當的權限
3. **使用者-代理關聯**: 建立使用者與代理的關聯關係
4. **中介軟體註冊**: 確保中介軟體已在 Kernel 中註冊
5. **策略註冊**: 確保策略已在 AuthServiceProvider 中註冊

## 故障排除

### 常見問題

1. **權限不存在**: 執行 `php artisan db:seed --class=PermissionSeeder`
2. **存取被拒絕**: 檢查使用者角色和權限配置
3. **資料範圍錯誤**: 檢查使用者-代理關聯設定
4. **中介軟體不生效**: 檢查路由中介軟體配置

### 除錯工具

```php
// 檢查使用者權限
$user->hasPermission('channels.agents.view');

// 檢查使用者角色
$user->hasRole('admin');

// 檢查代理關聯
$accessControl = app(ChannelAccessControlService::class);
$userAgent = $accessControl->getUserAgent($user);

// 檢查存取權限
$canAccess = $accessControl->canAccessAgent($agent, $user);
```

這個權限系統提供了完整的通路管理功能權限控制，確保系統的安全性和資料的正確存取限制。