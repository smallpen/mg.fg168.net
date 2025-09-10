<?php

namespace App\Livewire\Agent\Dashboard;

use App\Models\Agent;
use App\Services\AgentService;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * 代理下層代理管理元件
 * 
 * 允許代理檢視和管理其直屬下層代理
 */
class AgentManagement extends Component
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
     * 停用代理
     */
    public function deactivateAgent(int $agentId): void
    {
        try {
            $childAgent = Agent::findOrFail($agentId);
            
            // 檢查權限：只能操作直屬下層代理
            if ($childAgent->parent_id !== $this->agent->id) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => '您只能管理直屬下層代理'
                ]);
                return;
            }

            // 檢查是否有下層關聯
            if ($childAgent->hasDependencies()) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => '該代理還有下層代理或玩家，無法停用'
                ]);
                return;
            }

            $childAgent->update(['is_active' => false]);
            
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => '代理已停用'
            ]);

        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '操作失敗：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 啟用代理
     */
    public function activateAgent(int $agentId): void
    {
        try {
            $childAgent = Agent::findOrFail($agentId);
            
            // 檢查權限：只能操作直屬下層代理
            if ($childAgent->parent_id !== $this->agent->id) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => '您只能管理直屬下層代理'
                ]);
                return;
            }

            $childAgent->update(['is_active' => true]);
            
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => '代理已啟用'
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
        $children = $this->agent->children()
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
            ->withCount(['children', 'players'])
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        return view('livewire.agent.dashboard.agent-management', [
            'children' => $children,
        ]);
    }
}