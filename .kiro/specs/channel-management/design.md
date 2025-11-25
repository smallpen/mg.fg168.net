# 通路管理系統設計文件

## 概述

通路管理系統是一個多層級代理和玩家管理平台，支援無限層級的代理結構、前置符號系統和完整的點數管理機制。系統設計遵循 Laravel 最佳實踐，使用 Livewire 3.0 構建響應式用戶介面，並整合現有的權限管理系統。

## 架構設計

### 系統架構圖

```
┌─────────────────────────────────────────────────────────────┐
│                    通路管理系統架構                              │
├─────────────────────────────────────────────────────────────┤
│  前端層 (Frontend Layer)                                     │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐ │
│  │   代理管理介面    │  │   玩家管理介面    │  │   點數管理介面    │ │
│  │  (Livewire)     │  │  (Livewire)     │  │  (Livewire)     │ │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘ │
├─────────────────────────────────────────────────────────────┤
│  應用層 (Application Layer)                                  │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐ │
│  │   代理服務       │  │   玩家服務       │  │   點數服務       │ │
│  │  AgentService   │  │ PlayerService   │  │ PointService    │ │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘ │
├─────────────────────────────────────────────────────────────┤
│  領域層 (Domain Layer)                                       │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐ │
│  │   代理模型       │  │   玩家模型       │  │   點數模型       │ │
│  │   Agent Model   │  │  Player Model   │  │  Point Model    │ │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘ │
├─────────────────────────────────────────────────────────────┤
│  基礎設施層 (Infrastructure Layer)                            │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐ │
│  │   資料庫存儲     │  │   快取系統       │  │   日誌系統       │ │
│  │   MySQL         │  │   Redis         │  │   Laravel Log   │ │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### 核心組件關係

```
代理 (Agent)
├── 擁有前置符號 (prefix)
├── 擁有層級 (level)
├── 擁有點數 (points)
├── 可建立下層代理 (sub-agents)
└── 可建立隸屬玩家 (players)

玩家 (Player)
├── 隸屬於代理 (belongs to agent)
├── 繼承前置符號 (inherits prefix)
└── 擁有點數 (points)

點數系統 (Point System)
├── 代理點數管理
├── 玩家點數管理
├── 點數轉移機制
└── 點數稽核系統
```

## 資料模型設計

### 1. 代理模型 (Agent)

```php
// app/Models/Agent.php
class Agent extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',                 // 代理姓名
        'username',             // 原始帳號
        'account',              // 完整帳號 (prefix + username)
        'email',                // 電子郵件
        'phone',                // 電話號碼
        'prefix',               // 前置符號 (僅第一層代理)
        'level',                // 代理層級
        'parent_id',            // 上層代理ID
        'total_points',         // 總點數
        'allocated_points',     // 已分配點數
        'remaining_points',     // 剩餘點數
        'is_active',            // 是否啟用
        'created_by',           // 建立者
        'notes',                // 備註
    ];

    protected $casts = [
        'total_points' => 'decimal:2',
        'allocated_points' => 'decimal:2',
        'remaining_points' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // 關聯關係
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Agent::class, 'parent_id');
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class);
    }

    // 業務邏輯方法
    public function getFullPrefixAttribute(): string
    {
        return $this->level === 1 ? $this->prefix : $this->parent->full_prefix;
    }

    public function getAllDescendants(): Collection
    {
        return $this->children->flatMap(function ($child) {
            return collect([$child])->merge($child->getAllDescendants());
        });
    }

    public function canAllocatePoints(float $amount): bool
    {
        return $this->remaining_points >= $amount;
    }

    public function allocatePoints(float $amount): void
    {
        if (!$this->canAllocatePoints($amount)) {
            throw new InsufficientPointsException();
        }

        $this->increment('allocated_points', $amount);
        $this->decrement('remaining_points', $amount);
    }

    public function recoverPoints(float $amount): void
    {
        $this->decrement('allocated_points', $amount);
        $this->increment('remaining_points', $amount);
    }
}
```

### 2. 玩家模型 (Player)

```php
// app/Models/Player.php
class Player extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',                 // 玩家姓名
        'username',             // 原始帳號
        'account',              // 完整帳號 (prefix + username)
        'email',                // 電子郵件
        'phone',                // 電話號碼
        'agent_id',             // 隸屬代理ID
        'points',               // 玩家點數
        'is_active',            // 是否啟用
        'created_by',           // 建立者
        'notes',                // 備註
    ];

    protected $casts = [
        'points' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // 關聯關係
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class);
    }

    // 業務邏輯方法
    public function getAgentPathAttribute(): Collection
    {
        $path = collect([$this->agent]);
        $current = $this->agent;

        while ($current->parent) {
            $current = $current->parent;
            $path->prepend($current);
        }

        return $path;
    }

    public function canDeductPoints(float $amount): bool
    {
        return $this->points >= $amount;
    }

    public function addPoints(float $amount): void
    {
        $this->increment('points', $amount);
    }

    public function deductPoints(float $amount): void
    {
        if (!$this->canDeductPoints($amount)) {
            throw new InsufficientPointsException();
        }

        $this->decrement('points', $amount);
    }
}
```

### 3. 點數交易模型 (PointTransaction)

```php
// app/Models/PointTransaction.php
class PointTransaction extends Model
{
    use HasFactory, LogsActivity;

    const TYPE_AGENT_ALLOCATION = 'agent_allocation';
    const TYPE_AGENT_RECOVERY = 'agent_recovery';
    const TYPE_PLAYER_ALLOCATION = 'player_allocation';
    const TYPE_PLAYER_RECOVERY = 'player_recovery';
    const TYPE_PLAYER_CONSUMPTION = 'player_consumption';
    const TYPE_SYSTEM_ADJUSTMENT = 'system_adjustment';

    protected $fillable = [
        'agent_id',             // 代理ID (可為空)
        'player_id',            // 玩家ID (可為空)
        'type',                 // 交易類型
        'amount',               // 交易金額
        'balance_before',       // 交易前餘額
        'balance_after',        // 交易後餘額
        'description',          // 交易描述
        'reference_id',         // 參考ID
        'created_by',           // 操作者
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    // 關聯關係
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
```

## 服務層設計

### 1. 代理服務 (AgentService)

```php
// app/Services/AgentService.php
class AgentService
{
    public function __construct(
        private PointService $pointService,
        private ActivityLogger $activityLogger
    ) {}

    public function createAgent(array $data): Agent
    {
        DB::beginTransaction();
        
        try {
            // 處理前置符號
            if ($data['level'] === 1) {
                $this->validatePrefix($data['prefix']);
                $data['account'] = $data['prefix'] . $data['username'];
            } else {
                $parent = Agent::findOrFail($data['parent_id']);
                $data['account'] = $parent->full_prefix . $data['username'];
                $data['level'] = $parent->level + 1;
            }

            // 建立代理
            $agent = Agent::create($data);

            // 分配點數
            if (isset($data['initial_points']) && $data['initial_points'] > 0) {
                $this->pointService->allocatePointsToAgent(
                    $agent,
                    $data['initial_points'],
                    $data['parent_id'] ? Agent::find($data['parent_id']) : null
                );
            }

            $this->activityLogger->log('agent_created', $agent);

            DB::commit();
            return $agent;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateAgent(Agent $agent, array $data): Agent
    {
        DB::beginTransaction();
        
        try {
            // 處理帳號變更
            if (isset($data['username']) && $data['username'] !== $agent->username) {
                $data['account'] = $agent->full_prefix . $data['username'];
                
                // 更新所有下層代理和玩家的帳號
                $this->updateDescendantAccounts($agent);
            }

            $agent->update($data);
            $this->activityLogger->log('agent_updated', $agent);

            DB::commit();
            return $agent;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteAgent(Agent $agent): bool
    {
        if ($agent->children()->exists() || $agent->players()->exists()) {
            throw new AgentHasDependenciesException();
        }

        DB::beginTransaction();
        
        try {
            // 回收剩餘點數
            if ($agent->remaining_points > 0 && $agent->parent) {
                $this->pointService->recoverPointsFromAgent(
                    $agent,
                    $agent->remaining_points,
                    $agent->parent
                );
            }

            $agent->delete();
            $this->activityLogger->log('agent_deleted', $agent);

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function validatePrefix(string $prefix): void
    {
        if (!preg_match('/^[a-z]$/', $prefix)) {
            throw new InvalidPrefixException('前置符號必須是 a-z 的單一字母');
        }

        if (Agent::where('prefix', $prefix)->where('level', 1)->exists()) {
            throw new PrefixAlreadyExistsException('前置符號已被使用');
        }
    }

    private function updateDescendantAccounts(Agent $agent): void
    {
        // 更新所有下層代理的帳號
        $descendants = $agent->getAllDescendants();
        foreach ($descendants as $descendant) {
            $descendant->update([
                'account' => $descendant->parent->full_prefix . $descendant->username
            ]);
        }

        // 更新所有隸屬玩家的帳號
        $players = $agent->players;
        foreach ($players as $player) {
            $player->update([
                'account' => $agent->full_prefix . $player->username
            ]);
        }
    }
}
```

### 2. 玩家服務 (PlayerService)

```php
// app/Services/PlayerService.php
class PlayerService
{
    public function __construct(
        private PointService $pointService,
        private ActivityLogger $activityLogger
    ) {}

    public function createPlayer(array $data): Player
    {
        DB::beginTransaction();
        
        try {
            $agent = Agent::findOrFail($data['agent_id']);
            
            // 設定完整帳號
            $data['account'] = $agent->full_prefix . $data['username'];

            // 建立玩家
            $player = Player::create($data);

            // 分配點數
            if (isset($data['initial_points']) && $data['initial_points'] > 0) {
                $this->pointService->allocatePointsToPlayer(
                    $player,
                    $data['initial_points'],
                    $agent
                );
            }

            $this->activityLogger->log('player_created', $player);

            DB::commit();
            return $player;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updatePlayer(Player $player, array $data): Player
    {
        DB::beginTransaction();
        
        try {
            // 處理代理變更
            if (isset($data['agent_id']) && $data['agent_id'] !== $player->agent_id) {
                $newAgent = Agent::findOrFail($data['agent_id']);
                $data['account'] = $newAgent->full_prefix . $player->username;
                
                // 轉移點數
                $this->pointService->transferPlayerToNewAgent($player, $newAgent);
            }

            // 處理帳號變更
            if (isset($data['username']) && $data['username'] !== $player->username) {
                $data['account'] = $player->agent->full_prefix . $data['username'];
            }

            $player->update($data);
            $this->activityLogger->log('player_updated', $player);

            DB::commit();
            return $player;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deletePlayer(Player $player): bool
    {
        DB::beginTransaction();
        
        try {
            // 回收剩餘點數
            if ($player->points > 0) {
                $this->pointService->recoverPointsFromPlayer(
                    $player,
                    $player->points,
                    $player->agent
                );
            }

            $player->delete();
            $this->activityLogger->log('player_deleted', $player);

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
```

### 3. 點數服務 (PointService)

```php
// app/Services/PointService.php
class PointService
{
    public function allocatePointsToAgent(Agent $agent, float $amount, ?Agent $fromAgent = null): void
    {
        if ($fromAgent && !$fromAgent->canAllocatePoints($amount)) {
            throw new InsufficientPointsException();
        }

        DB::beginTransaction();
        
        try {
            $balanceBefore = $agent->total_points;
            
            // 更新代理點數
            $agent->increment('total_points', $amount);
            $agent->increment('remaining_points', $amount);

            // 從上層代理扣除點數
            if ($fromAgent) {
                $fromAgent->allocatePoints($amount);
                
                // 記錄上層代理的點數異動
                PointTransaction::create([
                    'agent_id' => $fromAgent->id,
                    'type' => PointTransaction::TYPE_AGENT_ALLOCATION,
                    'amount' => -$amount,
                    'balance_before' => $fromAgent->remaining_points + $amount,
                    'balance_after' => $fromAgent->remaining_points,
                    'description' => "分配點數給代理: {$agent->name}",
                    'reference_id' => $agent->id,
                    'created_by' => auth()->id(),
                ]);
            }

            // 記錄目標代理的點數異動
            PointTransaction::create([
                'agent_id' => $agent->id,
                'type' => PointTransaction::TYPE_AGENT_ALLOCATION,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $agent->total_points,
                'description' => $fromAgent ? "從代理 {$fromAgent->name} 獲得點數" : "系統分配點數",
                'reference_id' => $fromAgent?->id,
                'created_by' => auth()->id(),
            ]);

            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function allocatePointsToPlayer(Player $player, float $amount, Agent $fromAgent): void
    {
        if (!$fromAgent->canAllocatePoints($amount)) {
            throw new InsufficientPointsException();
        }

        DB::beginTransaction();
        
        try {
            $balanceBefore = $player->points;
            
            // 更新玩家點數
            $player->addPoints($amount);
            
            // 從代理扣除點數
            $fromAgent->allocatePoints($amount);

            // 記錄代理的點數異動
            PointTransaction::create([
                'agent_id' => $fromAgent->id,
                'type' => PointTransaction::TYPE_PLAYER_ALLOCATION,
                'amount' => -$amount,
                'balance_before' => $fromAgent->remaining_points + $amount,
                'balance_after' => $fromAgent->remaining_points,
                'description' => "分配點數給玩家: {$player->name}",
                'reference_id' => $player->id,
                'created_by' => auth()->id(),
            ]);

            // 記錄玩家的點數異動
            PointTransaction::create([
                'player_id' => $player->id,
                'type' => PointTransaction::TYPE_PLAYER_ALLOCATION,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $player->points,
                'description' => "從代理 {$fromAgent->name} 獲得點數",
                'reference_id' => $fromAgent->id,
                'created_by' => auth()->id(),
            ]);

            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function recoverPointsFromAgent(Agent $agent, float $amount, Agent $toAgent): void
    {
        if ($agent->remaining_points < $amount) {
            throw new InsufficientPointsException();
        }

        DB::beginTransaction();
        
        try {
            // 從代理回收點數
            $agent->decrement('total_points', $amount);
            $agent->decrement('remaining_points', $amount);
            
            // 回收到上層代理
            $toAgent->recoverPoints($amount);

            // 記錄點數異動
            PointTransaction::create([
                'agent_id' => $agent->id,
                'type' => PointTransaction::TYPE_AGENT_RECOVERY,
                'amount' => -$amount,
                'balance_before' => $agent->total_points + $amount,
                'balance_after' => $agent->total_points,
                'description' => "點數被代理 {$toAgent->name} 回收",
                'reference_id' => $toAgent->id,
                'created_by' => auth()->id(),
            ]);

            PointTransaction::create([
                'agent_id' => $toAgent->id,
                'type' => PointTransaction::TYPE_AGENT_RECOVERY,
                'amount' => $amount,
                'balance_before' => $toAgent->remaining_points - $amount,
                'balance_after' => $toAgent->remaining_points,
                'description' => "從代理 {$agent->name} 回收點數",
                'reference_id' => $agent->id,
                'created_by' => auth()->id(),
            ]);

            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function recoverPointsFromPlayer(Player $player, float $amount, Agent $toAgent): void
    {
        if (!$player->canDeductPoints($amount)) {
            throw new InsufficientPointsException();
        }

        DB::beginTransaction();
        
        try {
            // 從玩家扣除點數
            $player->deductPoints($amount);
            
            // 回收到代理
            $toAgent->recoverPoints($amount);

            // 記錄點數異動
            PointTransaction::create([
                'player_id' => $player->id,
                'type' => PointTransaction::TYPE_PLAYER_RECOVERY,
                'amount' => -$amount,
                'balance_before' => $player->points + $amount,
                'balance_after' => $player->points,
                'description' => "點數被代理 {$toAgent->name} 回收",
                'reference_id' => $toAgent->id,
                'created_by' => auth()->id(),
            ]);

            PointTransaction::create([
                'agent_id' => $toAgent->id,
                'type' => PointTransaction::TYPE_PLAYER_RECOVERY,
                'amount' => $amount,
                'balance_before' => $toAgent->remaining_points - $amount,
                'balance_after' => $toAgent->remaining_points,
                'description' => "從玩家 {$player->name} 回收點數",
                'reference_id' => $player->id,
                'created_by' => auth()->id(),
            ]);

            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
```

## Livewire 元件設計

### 1. 代理列表元件

```php
// app/Livewire/Admin/Channels/AgentList.php
class AgentList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $levelFilter = 'all';
    public string $statusFilter = 'all';
    public string $prefixFilter = 'all';
    public int $perPage = 25;

    protected $queryString = [
        'search' => ['except' => ''],
        'levelFilter' => ['except' => 'all'],
        'statusFilter' => ['except' => 'all'],
        'prefixFilter' => ['except' => 'all'],
        'perPage' => ['except' => 25],
    ];

    public function render()
    {
        $agents = Agent::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                      ->orWhere('account', 'like', "%{$this->search}%")
                      ->orWhere('username', 'like', "%{$this->search}%");
                });
            })
            ->when($this->levelFilter !== 'all', function ($query) {
                $query->where('level', $this->levelFilter);
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('is_active', $this->statusFilter === 'active');
            })
            ->when($this->prefixFilter !== 'all', function ($query) {
                $query->where('prefix', $this->prefixFilter);
            })
            ->withCount(['children', 'players'])
            ->orderBy('level')
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.admin.channels.agent-list', [
            'agents' => $agents,
            'prefixOptions' => $this->getPrefixOptions(),
        ]);
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->levelFilter = 'all';
        $this->statusFilter = 'all';
        $this->prefixFilter = 'all';
        $this->resetPage();
    }

    private function getPrefixOptions(): array
    {
        return Agent::where('level', 1)
            ->whereNotNull('prefix')
            ->pluck('prefix', 'prefix')
            ->toArray();
    }
}
```

### 2. 代理表單元件

```php
// app/Livewire/Admin/Channels/AgentForm.php
class AgentForm extends Component
{
    public ?Agent $agent = null;
    public bool $isEdit = false;

    // 表單欄位
    public string $name = '';
    public string $username = '';
    public string $email = '';
    public string $phone = '';
    public string $prefix = '';
    public ?int $parent_id = null;
    public float $initial_points = 0;
    public bool $is_active = true;
    public string $notes = '';

    // 選項資料
    public Collection $parentOptions;
    public array $prefixOptions;

    protected function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'username' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9_]+$/',
                Rule::unique('agents', 'username')->ignore($this->agent?->id),
            ],
            'email' => [
                'required',
                'email',
                Rule::unique('agents', 'email')->ignore($this->agent?->id),
            ],
            'phone' => 'nullable|string|max:20',
            'initial_points' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ];

        if (!$this->isEdit || !$this->agent) {
            if (!$this->parent_id) {
                // 第一層代理需要前置符號
                $rules['prefix'] = [
                    'required',
                    'string',
                    'size:1',
                    'regex:/^[a-z]$/',
                    Rule::unique('agents', 'prefix')->whereNull('deleted_at'),
                ];
            } else {
                // 下層代理需要選擇上層代理
                $rules['parent_id'] = 'required|exists:agents,id';
            }
        }

        return $rules;
    }

    public function mount(?Agent $agent = null): void
    {
        if ($agent) {
            $this->agent = $agent;
            $this->isEdit = true;
            $this->fill($agent->toArray());
        }

        $this->loadOptions();
    }

    public function save(): void
    {
        $this->validate();

        try {
            $data = [
                'name' => $this->name,
                'username' => $this->username,
                'email' => $this->email,
                'phone' => $this->phone,
                'is_active' => $this->is_active,
                'notes' => $this->notes,
            ];

            if (!$this->isEdit) {
                $data['prefix'] = $this->prefix;
                $data['parent_id'] = $this->parent_id;
                $data['initial_points'] = $this->initial_points;
                $data['level'] = $this->parent_id ? 0 : 1; // 將在服務中計算正確層級
            }

            $agentService = app(AgentService::class);

            if ($this->isEdit) {
                $agentService->updateAgent($this->agent, $data);
                $message = '代理資料更新成功';
            } else {
                $agentService->createAgent($data);
                $message = '代理建立成功';
            }

            $this->dispatch('agent-saved');
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => $message,
            ]);

            if (!$this->isEdit) {
                return redirect()->route('admin.channels.agents.index');
            }

        } catch (Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '操作失敗：' . $e->getMessage(),
            ]);
        }
    }

    public function updatedParentId(): void
    {
        if ($this->parent_id) {
            $this->prefix = '';
        }
    }

    private function loadOptions(): void
    {
        $this->parentOptions = Agent::where('is_active', true)
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        $this->prefixOptions = range('a', 'z');
        
        // 移除已使用的前置符號
        $usedPrefixes = Agent::where('level', 1)
            ->whereNotNull('prefix')
            ->pluck('prefix')
            ->toArray();
            
        $this->prefixOptions = array_diff($this->prefixOptions, $usedPrefixes);
    }
}
```

### 3. 點數管理元件

```php
// app/Livewire/Admin/Channels/PointManagement.php
class PointManagement extends Component
{
    public Agent $agent;
    public string $activeTab = 'overview';
    
    // 點數操作
    public string $operation = '';
    public ?int $target_id = null;
    public float $amount = 0;
    public string $description = '';

    public function mount(Agent $agent): void
    {
        $this->agent = $agent;
    }

    public function render()
    {
        $data = [
            'agent' => $this->agent->load(['children', 'players']),
            'pointTransactions' => $this->getPointTransactions(),
            'childrenWithPoints' => $this->getChildrenWithPoints(),
            'playersWithPoints' => $this->getPlayersWithPoints(),
        ];

        return view('livewire.admin.channels.point-management', $data);
    }

    public function allocatePoints(): void
    {
        $this->validate([
            'target_id' => 'required|integer',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $pointService = app(PointService::class);

            if ($this->operation === 'agent') {
                $target = Agent::findOrFail($this->target_id);
                $pointService->allocatePointsToAgent($target, $this->amount, $this->agent);
            } else {
                $target = Player::findOrFail($this->target_id);
                $pointService->allocatePointsToPlayer($target, $this->amount, $this->agent);
            }

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => '點數分配成功',
            ]);

            $this->reset(['operation', 'target_id', 'amount', 'description']);
            $this->agent->refresh();

        } catch (Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '點數分配失敗：' . $e->getMessage(),
            ]);
        }
    }

    public function recoverPoints(): void
    {
        $this->validate([
            'target_id' => 'required|integer',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $pointService = app(PointService::class);

            if ($this->operation === 'agent') {
                $target = Agent::findOrFail($this->target_id);
                $pointService->recoverPointsFromAgent($target, $this->amount, $this->agent);
            } else {
                $target = Player::findOrFail($this->target_id);
                $pointService->recoverPointsFromPlayer($target, $this->amount, $this->agent);
            }

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => '點數回收成功',
            ]);

            $this->reset(['operation', 'target_id', 'amount', 'description']);
            $this->agent->refresh();

        } catch (Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '點數回收失敗：' . $e->getMessage(),
            ]);
        }
    }

    private function getPointTransactions()
    {
        return PointTransaction::where('agent_id', $this->agent->id)
            ->orWhereIn('player_id', $this->agent->players->pluck('id'))
            ->with(['agent', 'player', 'creator'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
    }

    private function getChildrenWithPoints()
    {
        return $this->agent->children()
            ->select(['id', 'name', 'account', 'total_points', 'remaining_points'])
            ->get();
    }

    private function getPlayersWithPoints()
    {
        return $this->agent->players()
            ->select(['id', 'name', 'account', 'points'])
            ->get();
    }
}
```

## 資料庫設計

### 資料表結構

```sql
-- 代理資料表
CREATE TABLE agents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT '代理姓名',
    username VARCHAR(50) NOT NULL COMMENT '原始帳號',
    account VARCHAR(51) NOT NULL UNIQUE COMMENT '完整帳號',
    email VARCHAR(255) NOT NULL UNIQUE COMMENT '電子郵件',
    phone VARCHAR(20) NULL COMMENT '電話號碼',
    prefix CHAR(1) NULL COMMENT '前置符號',
    level TINYINT UNSIGNED NOT NULL COMMENT '代理層級',
    parent_id BIGINT UNSIGNED NULL COMMENT '上層代理ID',
    total_points DECIMAL(15,2) DEFAULT 0.00 COMMENT '總點數',
    allocated_points DECIMAL(15,2) DEFAULT 0.00 COMMENT '已分配點數',
    remaining_points DECIMAL(15,2) DEFAULT 0.00 COMMENT '剩餘點數',
    is_active BOOLEAN DEFAULT TRUE COMMENT '是否啟用',
    created_by BIGINT UNSIGNED NULL COMMENT '建立者',
    notes TEXT NULL COMMENT '備註',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    INDEX idx_parent_id (parent_id),
    INDEX idx_level (level),
    INDEX idx_prefix (prefix),
    INDEX idx_account (account),
    INDEX idx_is_active (is_active),
    
    FOREIGN KEY (parent_id) REFERENCES agents(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    
    UNIQUE KEY uk_prefix_level (prefix, level),
    CONSTRAINT chk_level_prefix CHECK (
        (level = 1 AND prefix IS NOT NULL) OR 
        (level > 1 AND prefix IS NULL)
    )
);

-- 玩家資料表
CREATE TABLE players (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT '玩家姓名',
    username VARCHAR(50) NOT NULL COMMENT '原始帳號',
    account VARCHAR(51) NOT NULL UNIQUE COMMENT '完整帳號',
    email VARCHAR(255) NOT NULL UNIQUE COMMENT '電子郵件',
    phone VARCHAR(20) NULL COMMENT '電話號碼',
    agent_id BIGINT UNSIGNED NOT NULL COMMENT '隸屬代理ID',
    points DECIMAL(15,2) DEFAULT 0.00 COMMENT '玩家點數',
    is_active BOOLEAN DEFAULT TRUE COMMENT '是否啟用',
    created_by BIGINT UNSIGNED NULL COMMENT '建立者',
    notes TEXT NULL COMMENT '備註',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    INDEX idx_agent_id (agent_id),
    INDEX idx_account (account),
    INDEX idx_is_active (is_active),
    INDEX idx_points (points),
    
    FOREIGN KEY (agent_id) REFERENCES agents(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- 點數交易記錄表
CREATE TABLE point_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agent_id BIGINT UNSIGNED NULL COMMENT '代理ID',
    player_id BIGINT UNSIGNED NULL COMMENT '玩家ID',
    type VARCHAR(50) NOT NULL COMMENT '交易類型',
    amount DECIMAL(15,2) NOT NULL COMMENT '交易金額',
    balance_before DECIMAL(15,2) NOT NULL COMMENT '交易前餘額',
    balance_after DECIMAL(15,2) NOT NULL COMMENT '交易後餘額',
    description TEXT NULL COMMENT '交易描述',
    reference_id BIGINT UNSIGNED NULL COMMENT '參考ID',
    created_by BIGINT UNSIGNED NULL COMMENT '操作者',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_agent_id (agent_id),
    INDEX idx_player_id (player_id),
    INDEX idx_type (type),
    INDEX idx_created_at (created_at),
    INDEX idx_reference_id (reference_id),
    
    FOREIGN KEY (agent_id) REFERENCES agents(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    
    CONSTRAINT chk_target CHECK (
        (agent_id IS NOT NULL AND player_id IS NULL) OR 
        (agent_id IS NULL AND player_id IS NOT NULL)
    )
);
```

## 錯誤處理

### 自定義例外類別

```php
// app/Exceptions/ChannelManagement/InsufficientPointsException.php
class InsufficientPointsException extends Exception
{
    public function __construct(string $message = '點數不足')
    {
        parent::__construct($message);
    }
}

// app/Exceptions/ChannelManagement/InvalidPrefixException.php
class InvalidPrefixException extends Exception
{
    public function __construct(string $message = '無效的前置符號')
    {
        parent::__construct($message);
    }
}

// app/Exceptions/ChannelManagement/PrefixAlreadyExistsException.php
class PrefixAlreadyExistsException extends Exception
{
    public function __construct(string $message = '前置符號已存在')
    {
        parent::__construct($message);
    }
}

// app/Exceptions/ChannelManagement/AgentHasDependenciesException.php
class AgentHasDependenciesException extends Exception
{
    public function __construct(string $message = '代理有下層關聯，無法刪除')
    {
        parent::__construct($message);
    }
}
```

## 測試策略

### 單元測試

1. **模型測試**
   - 代理模型的關聯關係
   - 點數計算邏輯
   - 前置符號生成

2. **服務測試**
   - 代理建立流程
   - 點數分配邏輯
   - 錯誤處理機制

3. **驗證測試**
   - 表單驗證規則
   - 業務規則驗證
   - 資料完整性檢查

### 整合測試

1. **API 測試**
   - 代理 CRUD 操作
   - 點數管理操作
   - 權限控制測試

2. **資料庫測試**
   - 交易完整性
   - 外鍵約束
   - 觸發器邏輯

### 端到端測試

1. **使用者流程測試**
   - 代理建立完整流程
   - 點數分配流程
   - 組織架構瀏覽

2. **權限測試**
   - 不同角色的存取權限
   - 資料範圍限制
   - 操作權限驗證

這個設計文件提供了通路管理系統的完整架構和實作方案，涵蓋了所有需求中提到的功能，並考慮了系統的可擴展性、安全性和效能。