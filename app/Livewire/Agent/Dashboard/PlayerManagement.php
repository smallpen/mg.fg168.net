<?php

namespace App\Livewire\Agent\Dashboard;

use App\Models\Agent;
use App\Models\Player;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * 代理玩家管理元件
 * 
 * 允許代理檢視和管理其隸屬玩家
 */
class PlayerManagement extends Component
{
    use WithPagination;

    public Agent $agent;
    public string $search = '';
    public string $statusFilter = 'all';
    public int $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
        'perPage' => ['except' => 10],
    ];

    public function mount(): void
    {
        $this->agent = auth()->user()->agent;
    }

    /**
     * 搜尋更新時重置分頁
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * 狀態篩選更新時重置分頁
     */
    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    /**
     * 每頁顯示筆數更新時重置分頁
     */
    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    /**
     * 重置篩選條件
     */
    public function resetFilters(): void
    {
        $this->search = '';
        $this->statusFilter = 'all';
        $this->resetPage();
        
        $this->dispatch('show-toast', [
            'type' => 'success',
            'message' => '篩選條件已清除'
        ]);
    }

    /**
     * 停用玩家
     */
    public function deactivatePlayer(int $playerId): void
    {
        try {
            $player = Player::findOrFail($playerId);
            
            // 檢查權限：只能操作隸屬玩家
            if ($player->agent_id !== $this->agent->id) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => '您只能管理隸屬於您的玩家'
                ]);
                return;
            }

            $player->update(['is_active' => false]);
            
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => '玩家已停用'
            ]);

        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '操作失敗：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 啟用玩家
     */
    public function activatePlayer(int $playerId): void
    {
        try {
            $player = Player::findOrFail($playerId);
            
            // 檢查權限：只能操作隸屬玩家
            if ($player->agent_id !== $this->agent->id) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => '您只能管理隸屬於您的玩家'
                ]);
                return;
            }

            $player->update(['is_active' => true]);
            
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => '玩家已啟用'
            ]);

        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '操作失敗：' . $e->getMessage()
            ]);
        }
    }

    public function render()
    {
        $players = $this->agent->players()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                      ->orWhere('account', 'like', "%{$this->search}%")
                      ->orWhere('username', 'like', "%{$this->search}%");
                });
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('is_active', $this->statusFilter === 'active');
            })
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        return view('livewire.agent.dashboard.player-management', [
            'players' => $players,
        ]);
    }
}