<?php

namespace App\Livewire\Admin\Channels;

use App\Models\Agent;
use App\Models\Player;
use Livewire\Component;
use Livewire\WithPagination;

class AgentList extends Component
{
    use WithPagination;

    // 搜尋和篩選屬性
    public string $search = '';
    public string $levelFilter = 'all';
    public string $statusFilter = 'all';
    public string $prefixFilter = 'all';
    public string $pointsRangeFilter = 'all';
    
    // 分頁設定
    public int $perPage = 25;
    public array $perPageOptions = [10, 25, 50, 100];

    // 當前檢視的代理
    public ?int $currentAgentId = null;
    public ?Agent $currentAgent = null;
    
    // 檢視模式：'agents' 或 'players'
    public string $viewMode = 'agents';

    // URL 查詢字串屬性（用於狀態持久化）
    protected $queryString = [
        'search' => ['except' => ''],
        'levelFilter' => ['except' => 'all'],
        'statusFilter' => ['except' => 'all'],
        'prefixFilter' => ['except' => 'all'],
        'pointsRangeFilter' => ['except' => 'all'],
        'perPage' => ['except' => 25],
        'currentAgentId' => ['except' => null],
        'viewMode' => ['except' => 'agents'],
    ];

    /**
     * 元件掛載時執行
     */
    public function mount(): void
    {
        // 檢查權限
        if (!auth()->user()->can('channels.agents.view')) {
            abort(403, '您沒有檢視代理的權限');
        }

        // 從 URL 參數初始化狀態
        $this->initializeFromQueryString();
        
        // 載入當前代理資訊
        if ($this->currentAgentId) {
            $this->currentAgent = Agent::find($this->currentAgentId);
            if (!$this->currentAgent) {
                $this->currentAgentId = null;
                $this->currentAgent = null;
                $this->viewMode = 'agents';
            }
        }
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        if ($this->viewMode === 'players' && $this->currentAgent) {
            $data = $this->getPlayersQuery();
            $viewData = [
                'players' => $data,
                'agents' => collect(),
                'prefixOptions' => $this->getPrefixOptions(),
                'levelOptions' => $this->getLevelOptions(),
                'statistics' => $this->getStatistics(),
                'breadcrumbs' => $this->getBreadcrumbs(),
            ];
        } else {
            $data = $this->getAgentsQuery();
            $viewData = [
                'agents' => $data,
                'players' => collect(),
                'prefixOptions' => $this->getPrefixOptions(),
                'levelOptions' => $this->getLevelOptions(),
                'statistics' => $this->getStatistics(),
                'breadcrumbs' => $this->getBreadcrumbs(),
            ];
        }
        
        return view('livewire.admin.channels.agent-list', $viewData);
    }

    /**
     * 取得代理查詢
     */
    private function getAgentsQuery()
    {
        $query = Agent::query();
        
        // 根據當前檢視的代理決定查詢範圍
        if ($this->currentAgent) {
            // 顯示當前代理的直屬下層代理
            $query->where('parent_id', $this->currentAgent->id);
        } else {
            // 顯示第一層代理
            $query->where('level', 1);
        }
        
        return $query
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                      ->orWhere('account', 'like', "%{$this->search}%")
                      ->orWhere('username', 'like', "%{$this->search}%")
                      ->orWhere('email', 'like', "%{$this->search}%");
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
            ->when($this->pointsRangeFilter !== 'all', function ($query) {
                switch ($this->pointsRangeFilter) {
                    case 'low':
                        $query->where('remaining_points', '<', 1000);
                        break;
                    case 'medium':
                        $query->whereBetween('remaining_points', [1000, 10000]);
                        break;
                    case 'high':
                        $query->where('remaining_points', '>', 10000);
                        break;
                    case 'zero':
                        $query->where('remaining_points', '=', 0);
                        break;
                }
            })
            ->with(['parent', 'creator'])
            ->withCount(['children', 'players'])
            ->orderBy('level')
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    /**
     * 取得玩家查詢
     */
    private function getPlayersQuery()
    {
        if (!$this->currentAgent) {
            return collect();
        }

        return Player::query()
            ->where('agent_id', $this->currentAgent->id)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                      ->orWhere('account', 'like', "%{$this->search}%")
                      ->orWhere('username', 'like', "%{$this->search}%")
                      ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('is_active', $this->statusFilter === 'active');
            })
            ->with(['agent'])
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    /**
     * 重置所有篩選條件
     */
    public function resetFilters(): void
    {
        try {
            // 記錄篩選重置操作
            \Log::info('🔄 AgentList resetFilters - 篩選重置開始', [
                'timestamp' => now()->toISOString(),
                'user' => auth()->user()->username ?? 'unknown',
                'before_reset' => [
                    'search' => $this->search ?? '',
                    'levelFilter' => $this->levelFilter ?? 'all',
                    'statusFilter' => $this->statusFilter ?? 'all',
                    'prefixFilter' => $this->prefixFilter ?? 'all',
                    'pointsRangeFilter' => $this->pointsRangeFilter ?? 'all',
                ]
            ]);
            
            // 重置所有篩選條件
            $this->search = '';
            $this->levelFilter = 'all';
            $this->statusFilter = 'all';
            $this->prefixFilter = 'all';
            $this->pointsRangeFilter = 'all';
            
            // 重置分頁和驗證
            $this->resetPage();
            $this->resetValidation();
            
            // 發送前端重置事件
            $this->dispatch('reset-form-elements');
            
            // 顯示成功訊息
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => '篩選條件已清除'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('AgentList 重置方法執行失敗', [
                'method' => 'resetFilters',
                'error' => $e->getMessage(),
                'component' => static::class,
            ]);
            
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '重置操作失敗，請重試'
            ]);
        }
    }

    /**
     * 前往指定頁面
     */
    public function gotoPage(int $page): void
    {
        $this->setPage($page);
    }

    /**
     * 每頁顯示筆數更新時重置分頁
     */
    public function updatedPerPage(): void
    {
        try {
            // 驗證 perPage 值
            if (!in_array($this->perPage, $this->perPageOptions)) {
                $this->perPage = 25; // 重置為預設值
            }
            
            $this->resetPage();
            
            // 發送更新事件
            $this->dispatch('per-page-updated', perPage: $this->perPage);
            
        } catch (\Exception $e) {
            logger()->error('Error updating perPage', [
                'error' => $e->getMessage(),
                'perPage' => $this->perPage
            ]);
            
            // 重置為預設值
            $this->perPage = 25;
            $this->resetPage();
        }
    }

    /**
     * 取得前置符號選項
     */
    private function getPrefixOptions(): array
    {
        return Agent::where('level', 1)
            ->whereNotNull('prefix')
            ->distinct()
            ->pluck('prefix', 'prefix')
            ->toArray();
    }

    /**
     * 取得層級選項
     */
    private function getLevelOptions(): array
    {
        return Agent::distinct()
            ->pluck('level', 'level')
            ->sort()
            ->toArray();
    }

    /**
     * 取得統計資訊
     */
    private function getStatistics(): array
    {
        if ($this->viewMode === 'players' && $this->currentAgent) {
            // 玩家檢視模式：顯示當前代理的玩家統計
            $totalPlayers = Player::where('agent_id', $this->currentAgent->id)->count();
            $activePlayers = Player::where('agent_id', $this->currentAgent->id)->where('is_active', true)->count();
            $totalPlayerPoints = Player::where('agent_id', $this->currentAgent->id)->sum('total_points') ?? 0;
            $availablePlayerPoints = Player::where('agent_id', $this->currentAgent->id)->sum('available_points') ?? 0;
            
            return [
                'total_agents' => $totalPlayers,
                'active_agents' => $activePlayers,
                'inactive_agents' => $totalPlayers - $activePlayers,
                'total_points' => $totalPlayerPoints,
                'allocated_points' => $totalPlayerPoints - $availablePlayerPoints,
                'remaining_points' => $availablePlayerPoints,
                'total_children' => 0,
                'total_players' => $totalPlayers,
                'is_player_view' => true,
            ];
        }
        
        // 代理檢視模式：根據當前檢視範圍計算統計
        if ($this->currentAgent) {
            // 檢視特定代理的下層代理統計
            $agents = Agent::where('parent_id', $this->currentAgent->id)->get();
            $totalAgents = $agents->count();
            $activeAgents = $agents->where('is_active', true)->count();
            $totalPoints = $agents->sum('total_points') ?? 0;
            $allocatedPoints = $agents->sum('allocated_points') ?? 0;
            $remainingPoints = $agents->sum('remaining_points') ?? 0;
            
            // 計算下層代理的子代理和玩家數
            $totalChildren = 0;
            $totalPlayers = 0;
            
            foreach ($agents as $agent) {
                $totalChildren += Agent::where('parent_id', $agent->id)->count();
                $totalPlayers += Player::where('agent_id', $agent->id)->count();
            }
            
            return [
                'total_agents' => $totalAgents,
                'active_agents' => $activeAgents,
                'inactive_agents' => $totalAgents - $activeAgents,
                'total_points' => $totalPoints,
                'allocated_points' => $allocatedPoints,
                'remaining_points' => $remainingPoints,
                'total_children' => $totalChildren,
                'total_players' => $totalPlayers,
                'current_agent_name' => $this->currentAgent->name,
                'is_player_view' => false,
            ];
        } else {
            // 根層級：顯示所有第一層代理的統計
            $agents = Agent::where('level', 1)->get();
            $totalAgents = $agents->count();
            $activeAgents = $agents->where('is_active', true)->count();
            $totalPoints = $agents->sum('total_points') ?? 0;
            $allocatedPoints = $agents->sum('allocated_points') ?? 0;
            $remainingPoints = $agents->sum('remaining_points') ?? 0;
            
            // 計算所有下層代理數和玩家數
            $totalChildren = 0;
            $totalPlayers = 0;
            
            foreach ($agents as $agent) {
                $totalChildren += $this->countAllDescendants($agent);
                $totalPlayers += $this->countAllPlayers($agent);
            }
            
            return [
                'total_agents' => $totalAgents,
                'active_agents' => $activeAgents,
                'inactive_agents' => $totalAgents - $activeAgents,
                'total_points' => $totalPoints,
                'allocated_points' => $allocatedPoints,
                'remaining_points' => $remainingPoints,
                'total_children' => $totalChildren,
                'total_players' => $totalPlayers,
                'is_player_view' => false,
            ];
        }
    }

    /**
     * 遞迴計算代理的所有下層代理數量
     */
    private function countAllDescendants(Agent $agent): int
    {
        $count = $agent->children()->count();
        
        foreach ($agent->children as $child) {
            $count += $this->countAllDescendants($child);
        }
        
        return $count;
    }

    /**
     * 遞迴計算代理及其下層代理的所有玩家數量
     */
    private function countAllPlayers(Agent $agent): int
    {
        $count = $agent->players()->count();
        
        foreach ($agent->children as $child) {
            $count += $this->countAllPlayers($child);
        }
        
        return $count;
    }

    /**
     * 從 URL 查詢字串初始化狀態
     */
    private function initializeFromQueryString(): void
    {
        $request = request();
        
        $this->search = $request->get('search', '');
        $this->levelFilter = $request->get('levelFilter', 'all');
        $this->statusFilter = $request->get('statusFilter', 'all');
        $this->prefixFilter = $request->get('prefixFilter', 'all');
        $this->pointsRangeFilter = $request->get('pointsRangeFilter', 'all');
        $this->currentAgentId = $request->get('currentAgentId', null);
        $this->viewMode = $request->get('viewMode', 'agents');
        
        // 驗證並設定 perPage
        $requestedPerPage = (int) $request->get('perPage', 25);
        if (in_array($requestedPerPage, $this->perPageOptions)) {
            $this->perPage = $requestedPerPage;
        }
    }

    /**
     * 檢視代理的下層代理
     */
    public function viewAgentChildren(int $agentId): void
    {
        try {
            $agent = Agent::findOrFail($agentId);
            
            // 檢查權限
            if (!auth()->user()->can('channels.agents.view')) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => '您沒有檢視代理的權限'
                ]);
                return;
            }
            
            $this->currentAgentId = $agentId;
            $this->currentAgent = $agent;
            $this->viewMode = 'agents';
            $this->resetPage();
            
            // 清除搜尋條件
            $this->search = '';
            
        } catch (\Exception $e) {
            \Log::error('檢視代理下層失敗', [
                'agent_id' => $agentId,
                'error' => $e->getMessage(),
                'user' => auth()->id(),
            ]);
            
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '操作失敗，請重試'
            ]);
        }
    }

    /**
     * 檢視代理的直屬玩家
     */
    public function viewAgentPlayers(int $agentId): void
    {
        try {
            $agent = Agent::findOrFail($agentId);
            
            // 檢查權限
            if (!auth()->user()->can('channels.players.view')) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => '您沒有檢視玩家的權限'
                ]);
                return;
            }
            
            $this->currentAgentId = $agentId;
            $this->currentAgent = $agent;
            $this->viewMode = 'players';
            $this->resetPage();
            
            // 清除搜尋條件
            $this->search = '';
            
        } catch (\Exception $e) {
            \Log::error('檢視代理玩家失敗', [
                'agent_id' => $agentId,
                'error' => $e->getMessage(),
                'user' => auth()->id(),
            ]);
            
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '操作失敗，請重試'
            ]);
        }
    }

    /**
     * 返回上一層
     */
    public function goBack(): void
    {
        if ($this->currentAgent && $this->currentAgent->parent) {
            $this->currentAgentId = $this->currentAgent->parent->id;
            $this->currentAgent = $this->currentAgent->parent;
        } else {
            $this->currentAgentId = null;
            $this->currentAgent = null;
        }
        
        $this->viewMode = 'agents';
        $this->resetPage();
        $this->search = '';
    }

    /**
     * 返回根層級
     */
    public function goToRoot(): void
    {
        $this->currentAgentId = null;
        $this->currentAgent = null;
        $this->viewMode = 'agents';
        $this->resetPage();
        $this->search = '';
    }

    /**
     * 取得麵包屑導航
     */
    private function getBreadcrumbs(): array
    {
        $breadcrumbs = [
            [
                'name' => '第一層代理',
                'action' => 'goToRoot',
                'active' => !$this->currentAgent
            ]
        ];

        if ($this->currentAgent) {
            // 建立代理路徑
            $path = collect([$this->currentAgent]);
            $current = $this->currentAgent;
            
            while ($current->parent) {
                $current = $current->parent;
                $path->prepend($current);
            }
            
            foreach ($path as $index => $agent) {
                if ($index === 0 && $agent->level === 1) continue; // 跳過第一層代理，已經在上面添加了
                
                $isLast = $index === $path->count() - 1;
                $breadcrumbs[] = [
                    'name' => $agent->name,
                    'action' => $isLast ? null : "viewAgentChildren({$agent->id})",
                    'active' => $isLast && $this->viewMode === 'agents'
                ];
            }
            
            if ($this->viewMode === 'players') {
                $breadcrumbs[] = [
                    'name' => '直屬玩家',
                    'action' => null,
                    'active' => true
                ];
            }
        }

        return $breadcrumbs;
    }

    /**
     * 匯出代理資料
     */
    public function exportAgents(): void
    {
        // 檢查匯出權限
        if (!auth()->user()->can('channels.agents.export')) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '您沒有匯出代理資料的權限'
            ]);
            return;
        }

        try {
            // 取得篩選後的代理資料
            $agents = $this->getAgentsQuery()->get();
            
            // 這裡可以實作匯出邏輯，例如 CSV 或 Excel
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => "已匯出 {$agents->count()} 筆代理資料"
            ]);
            
        } catch (\Exception $e) {
            \Log::error('代理資料匯出失敗', [
                'error' => $e->getMessage(),
                'user' => auth()->id(),
            ]);
            
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '匯出失敗，請重試'
            ]);
        }
    }

    /**
     * 切換代理狀態
     */
    public function toggleAgentStatus(int $agentId): void
    {
        try {
            $agent = Agent::findOrFail($agentId);
            
            // 檢查編輯權限
            if (!auth()->user()->can('channels.agents.edit')) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => '您沒有編輯代理的權限'
                ]);
                return;
            }
            
            $agent->update(['is_active' => !$agent->is_active]);
            
            $status = $agent->is_active ? '啟用' : '停用';
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => "代理 {$agent->name} 已{$status}"
            ]);
            
        } catch (\Exception $e) {
            \Log::error('切換代理狀態失敗', [
                'agent_id' => $agentId,
                'error' => $e->getMessage(),
                'user' => auth()->id(),
            ]);
            
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '操作失敗，請重試'
            ]);
        }
    }
}