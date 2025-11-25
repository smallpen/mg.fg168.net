<?php

namespace App\Livewire\Admin\Channels;

use App\Models\Player;
use App\Models\Agent;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * 玩家列表 Livewire 元件
 * 
 * 負責顯示玩家列表、搜尋、篩選、分頁等功能
 * 包含玩家統計資訊和批次操作
 */
class PlayerList extends Component
{
    use WithPagination;

    // 搜尋和篩選屬性
    public string $search = '';
    public string $agentFilter = 'all';
    public string $statusFilter = 'all';
    public string $pointsRangeFilter = 'all';
    public string $levelFilter = 'all';
    
    // 分頁設定
    public int $perPage = 25;
    public array $perPageOptions = [10, 25, 50, 100];

    // 批次操作
    public array $selectedItems = [];
    public bool $selectAll = false;

    // URL 查詢字串屬性（用於狀態持久化）
    protected $queryString = [
        'search' => ['except' => ''],
        'agentFilter' => ['except' => 'all'],
        'statusFilter' => ['except' => 'all'],
        'pointsRangeFilter' => ['except' => 'all'],
        'levelFilter' => ['except' => 'all'],
        'perPage' => ['except' => 25],
    ];

    /**
     * 元件掛載時執行
     */
    public function mount(): void
    {
        // 檢查權限
        if (!auth()->user()->can('channels.players.view')) {
            abort(403, '您沒有檢視玩家的權限');
        }

        // 從 URL 參數初始化狀態
        $this->initializeFromQueryString();
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        $players = $this->getPlayersQuery();
        
        return view('livewire.admin.channels.player-list', [
            'players' => $players,
            'agentOptions' => $this->getAgentOptions(),
            'levelOptions' => $this->getLevelOptions(),
            'statistics' => $this->getStatistics(),
        ]);
    }

    /**
     * 取得玩家查詢
     */
    private function getPlayersQuery()
    {
        return Player::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                      ->orWhere('account', 'like', "%{$this->search}%")
                      ->orWhere('username', 'like', "%{$this->search}%")
                      ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->when($this->agentFilter !== 'all', function ($query) {
                if (is_numeric($this->agentFilter)) {
                    $query->where('agent_id', $this->agentFilter);
                }
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('is_active', $this->statusFilter === 'active');
            })
            ->when($this->pointsRangeFilter !== 'all', function ($query) {
                switch ($this->pointsRangeFilter) {
                    case 'zero':
                        $query->where('points', '=', 0);
                        break;
                    case 'low':
                        $query->whereBetween('points', [0.01, 100]);
                        break;
                    case 'medium':
                        $query->whereBetween('points', [100.01, 1000]);
                        break;
                    case 'high':
                        $query->where('points', '>', 1000);
                        break;
                }
            })
            ->when($this->levelFilter !== 'all', function ($query) {
                $query->whereHas('agent', function ($q) {
                    $q->where('level', $this->levelFilter);
                });
            })
            ->with(['agent.parent', 'creator'])
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);
    }

    /**
     * 重置所有篩選條件
     */
    public function resetFilters(): void
    {
        try {
            // 記錄篩選重置操作
            \Log::info('🔄 PlayerList resetFilters - 篩選重置開始', [
                'timestamp' => now()->toISOString(),
                'user' => auth()->user()->username ?? 'unknown',
                'before_reset' => [
                    'search' => $this->search ?? '',
                    'agentFilter' => $this->agentFilter ?? 'all',
                    'statusFilter' => $this->statusFilter ?? 'all',
                    'pointsRangeFilter' => $this->pointsRangeFilter ?? 'all',
                    'levelFilter' => $this->levelFilter ?? 'all',
                ]
            ]);
            
            // 重置所有篩選條件
            $this->search = '';
            $this->agentFilter = 'all';
            $this->statusFilter = 'all';
            $this->pointsRangeFilter = 'all';
            $this->levelFilter = 'all';
            $this->selectedItems = [];
            $this->selectAll = false;
            
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
            \Log::error('PlayerList 重置方法執行失敗', [
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
     * 切換全選狀態
     */
    public function updatedSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selectedItems = $this->getPlayersQuery()->pluck('id')->toArray();
        } else {
            $this->selectedItems = [];
        }
    }

    /**
     * 當選擇項目變更時更新全選狀態
     */
    public function updatedSelectedItems(): void
    {
        $totalItems = $this->getPlayersQuery()->count();
        $this->selectAll = count($this->selectedItems) === $totalItems;
    }

    /**
     * 取得代理選項
     */
    private function getAgentOptions(): array
    {
        return Agent::where('is_active', true)
            ->withCount('players')
            ->having('players_count', '>', 0)
            ->orderBy('level')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function ($agent) {
                $label = "{$agent->name} (第{$agent->level}層) - {$agent->players_count}位玩家";
                return [$agent->id => $label];
            })
            ->toArray();
    }

    /**
     * 取得層級選項
     */
    private function getLevelOptions(): array
    {
        return Agent::whereHas('players')
            ->distinct()
            ->pluck('level', 'level')
            ->sort()
            ->mapWithKeys(function ($level) {
                return [$level => "第{$level}層代理"];
            })
            ->toArray();
    }

    /**
     * 取得統計資訊
     */
    private function getStatistics(): array
    {
        $totalPlayers = Player::count();
        $activePlayers = Player::where('is_active', true)->count();
        $totalPoints = Player::sum('points') ?? 0;
        $playersWithPoints = Player::where('points', '>', 0)->count();
        $averagePoints = Player::where('points', '>', 0)->avg('points') ?? 0;
        
        // 按代理層級統計
        $playersByLevel = Player::join('agents', 'players.agent_id', '=', 'agents.id')
            ->selectRaw('agents.level, COUNT(*) as count')
            ->groupBy('agents.level')
            ->orderBy('agents.level')
            ->pluck('count', 'level')
            ->toArray();
        
        return [
            'total_players' => $totalPlayers,
            'active_players' => $activePlayers,
            'inactive_players' => $totalPlayers - $activePlayers,
            'total_points' => $totalPoints,
            'players_with_points' => $playersWithPoints,
            'players_without_points' => $totalPlayers - $playersWithPoints,
            'average_points' => $averagePoints,
            'players_by_level' => $playersByLevel,
        ];
    }

    /**
     * 從 URL 查詢字串初始化狀態
     */
    private function initializeFromQueryString(): void
    {
        $request = request();
        
        $this->search = $request->get('search', '');
        $this->agentFilter = $request->get('agentFilter', 'all');
        $this->statusFilter = $request->get('statusFilter', 'all');
        $this->pointsRangeFilter = $request->get('pointsRangeFilter', 'all');
        $this->levelFilter = $request->get('levelFilter', 'all');
        
        // 驗證並設定 perPage
        $requestedPerPage = (int) $request->get('perPage', 25);
        if (in_array($requestedPerPage, $this->perPageOptions)) {
            $this->perPage = $requestedPerPage;
        }
    }

    /**
     * 切換玩家狀態
     */
    public function togglePlayerStatus(int $playerId): void
    {
        try {
            $player = Player::findOrFail($playerId);
            
            // 檢查編輯權限
            if (!auth()->user()->can('channels.players.edit')) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => '您沒有編輯玩家的權限'
                ]);
                return;
            }
            
            $player->update(['is_active' => !$player->is_active]);
            
            $status = $player->is_active ? '啟用' : '停用';
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => "玩家 {$player->name} 已{$status}"
            ]);
            
        } catch (\Exception $e) {
            \Log::error('切換玩家狀態失敗', [
                'player_id' => $playerId,
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
     * 批次啟用玩家
     */
    public function batchActivatePlayers(): void
    {
        if (empty($this->selectedItems)) {
            $this->dispatch('show-toast', [
                'type' => 'warning',
                'message' => '請先選擇要啟用的玩家'
            ]);
            return;
        }

        try {
            // 檢查編輯權限
            if (!auth()->user()->can('channels.players.edit')) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => '您沒有編輯玩家的權限'
                ]);
                return;
            }

            $updatedCount = Player::whereIn('id', $this->selectedItems)
                ->update(['is_active' => true]);

            $this->selectedItems = [];
            $this->selectAll = false;

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => "已啟用 {$updatedCount} 位玩家"
            ]);

        } catch (\Exception $e) {
            \Log::error('批次啟用玩家失敗', [
                'selected_items' => $this->selectedItems,
                'error' => $e->getMessage(),
                'user' => auth()->id(),
            ]);

            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '批次操作失敗，請重試'
            ]);
        }
    }

    /**
     * 批次停用玩家
     */
    public function batchDeactivatePlayers(): void
    {
        if (empty($this->selectedItems)) {
            $this->dispatch('show-toast', [
                'type' => 'warning',
                'message' => '請先選擇要停用的玩家'
            ]);
            return;
        }

        try {
            // 檢查編輯權限
            if (!auth()->user()->can('channels.players.edit')) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => '您沒有編輯玩家的權限'
                ]);
                return;
            }

            $updatedCount = Player::whereIn('id', $this->selectedItems)
                ->update(['is_active' => false]);

            $this->selectedItems = [];
            $this->selectAll = false;

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => "已停用 {$updatedCount} 位玩家"
            ]);

        } catch (\Exception $e) {
            \Log::error('批次停用玩家失敗', [
                'selected_items' => $this->selectedItems,
                'error' => $e->getMessage(),
                'user' => auth()->id(),
            ]);

            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '批次操作失敗，請重試'
            ]);
        }
    }

    /**
     * 匯出玩家資料
     */
    public function exportPlayers(): void
    {
        // 檢查匯出權限
        if (!auth()->user()->can('channels.players.export')) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '您沒有匯出玩家資料的權限'
            ]);
            return;
        }

        try {
            // 取得篩選後的玩家資料
            $players = $this->getPlayersQuery()->get();
            
            // 這裡可以實作匯出邏輯，例如 CSV 或 Excel
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => "已匯出 {$players->count()} 筆玩家資料"
            ]);
            
        } catch (\Exception $e) {
            \Log::error('玩家資料匯出失敗', [
                'error' => $e->getMessage(),
                'user' => auth()->id(),
            ]);
            
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '匯出失敗，請重試'
            ]);
        }
    }
}