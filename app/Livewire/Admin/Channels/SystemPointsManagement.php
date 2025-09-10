<?php

namespace App\Livewire\Admin\Channels;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Agent;
use App\Models\Player;
use App\Models\PointTransaction;
use App\Services\PointService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * 系統級點數管理元件
 * 
 * 提供系統管理員進行全域點數管理的功能
 */
class SystemPointsManagement extends Component
{
    use WithPagination;

    // 搜尋和篩選
    public string $search = '';
    public string $typeFilter = 'all'; // all, agent, player
    public string $statusFilter = 'all'; // all, active, inactive
    public string $sortBy = 'total_points';
    public string $sortDirection = 'desc';
    public int $perPage = 25;

    // 點數調整
    public bool $showAdjustModal = false;
    public string $adjustType = 'agent';
    public ?int $adjustTargetId = null;
    public string $adjustTargetName = '';
    public float $adjustAmount = 0;
    public string $adjustReason = '';

    // 批次操作
    public array $selectedItems = [];
    public bool $selectAll = false;
    public string $batchAction = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'typeFilter' => ['except' => 'all'],
        'statusFilter' => ['except' => 'all'],
        'sortBy' => ['except' => 'total_points'],
        'sortDirection' => ['except' => 'desc'],
        'perPage' => ['except' => 25],
    ];

    public function mount(): void
    {
        $this->authorize('channels.system.manage');
    }

    public function render()
    {
        $data = $this->getData();
        $statistics = $this->getStatistics();
        
        return view('livewire.admin.channels.system-points-management', [
            'items' => $data,
            'statistics' => $statistics,
        ]);
    }

    private function getData()
    {
        $query = collect();

        if ($this->typeFilter === 'all' || $this->typeFilter === 'agent') {
            $agents = Agent::select([
                'id',
                'name',
                'account',
                'level',
                'total_points',
                'allocated_points', 
                'remaining_points',
                'is_active',
                'created_at',
                DB::raw("'agent' as type")
            ])
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                          ->orWhere('account', 'like', "%{$this->search}%");
                });
            })
            ->when($this->statusFilter !== 'all', function ($q) {
                $q->where('is_active', $this->statusFilter === 'active');
            });

            $query = $query->merge($agents->get());
        }

        if ($this->typeFilter === 'all' || $this->typeFilter === 'player') {
            $players = Player::select([
                'id',
                'name',
                'account',
                DB::raw('NULL as level'),
                'points as total_points',
                DB::raw('0 as allocated_points'),
                'points as remaining_points',
                'is_active',
                'created_at',
                DB::raw("'player' as type")
            ])
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                          ->orWhere('account', 'like', "%{$this->search}%");
                });
            })
            ->when($this->statusFilter !== 'all', function ($q) {
                $q->where('is_active', $this->statusFilter === 'active');
            });

            $query = $query->merge($players->get());
        }

        // 排序
        $query = $query->sortBy(function ($item) {
            return $this->sortDirection === 'asc' ? $item->{$this->sortBy} : -$item->{$this->sortBy};
        });

        // 分頁
        $total = $query->count();
        $items = $query->forPage($this->getPage(), $this->perPage);

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $this->perPage,
            $this->getPage(),
            ['path' => request()->url()]
        );
    }

    private function getStatistics(): array
    {
        return [
            'total_system_points' => Agent::sum('total_points'),
            'total_allocated_points' => Agent::sum('allocated_points'),
            'total_remaining_points' => Agent::sum('remaining_points'),
            'total_player_points' => Player::sum('points'),
            'transactions_today' => PointTransaction::whereDate('created_at', Carbon::today())->count(),
            'transactions_week' => PointTransaction::where('created_at', '>=', Carbon::now()->subDays(7))->count(),
            'anomalies' => $this->detectAnomalies(),
        ];
    }

    private function detectAnomalies(): array
    {
        $anomalies = [];

        // 檢查負點數
        $negativeAgents = Agent::where('remaining_points', '<', 0)->count();
        if ($negativeAgents > 0) {
            $anomalies[] = [
                'type' => 'negative_points',
                'count' => $negativeAgents,
                'message' => "發現 {$negativeAgents} 個代理有負點數餘額"
            ];
        }

        // 檢查點數不一致
        $inconsistentAgents = Agent::whereRaw('total_points != allocated_points + remaining_points')->count();
        if ($inconsistentAgents > 0) {
            $anomalies[] = [
                'type' => 'inconsistent_points',
                'count' => $inconsistentAgents,
                'message' => "發現 {$inconsistentAgents} 個代理點數計算不一致"
            ];
        }

        return $anomalies;
    }

    public function sortBy(string $field): void
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
        
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->typeFilter = 'all';
        $this->statusFilter = 'all';
        $this->sortBy = 'total_points';
        $this->sortDirection = 'desc';
        $this->selectedItems = [];
        $this->selectAll = false;
        $this->resetPage();
    }

    public function openAdjustModal(string $type, int $id, string $name): void
    {
        $this->adjustType = $type;
        $this->adjustTargetId = $id;
        $this->adjustTargetName = $name;
        $this->adjustAmount = 0;
        $this->adjustReason = '';
        $this->showAdjustModal = true;
    }

    public function closeAdjustModal(): void
    {
        $this->showAdjustModal = false;
        $this->reset(['adjustType', 'adjustTargetId', 'adjustTargetName', 'adjustAmount', 'adjustReason']);
    }

    public function adjustPoints(): void
    {
        $this->validate([
            'adjustAmount' => 'required|numeric|not_in:0',
            'adjustReason' => 'required|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $pointService = app(PointService::class);

            if ($this->adjustType === 'agent') {
                $agent = Agent::findOrFail($this->adjustTargetId);
                $pointService->systemAdjustAgentPoints(
                    $agent,
                    $this->adjustAmount,
                    $this->adjustReason
                );
            } else {
                $player = Player::findOrFail($this->adjustTargetId);
                $pointService->systemAdjustPlayerPoints(
                    $player,
                    $this->adjustAmount,
                    $this->adjustReason
                );
            }

            DB::commit();

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => '點數調整成功'
            ]);

            $this->closeAdjustModal();

        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '點數調整失敗：' . $e->getMessage()
            ]);
        }
    }

    public function updatedSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selectedItems = $this->getData()->pluck('type')->zip($this->getData()->pluck('id'))
                ->map(fn($item) => $item[0] . '_' . $item[1])
                ->toArray();
        } else {
            $this->selectedItems = [];
        }
    }

    public function executeBatchAction(): void
    {
        if (empty($this->selectedItems) || empty($this->batchAction)) {
            $this->dispatch('show-toast', [
                'type' => 'warning',
                'message' => '請選擇項目和操作'
            ]);
            return;
        }

        try {
            DB::beginTransaction();

            foreach ($this->selectedItems as $item) {
                [$type, $id] = explode('_', $item);
                
                switch ($this->batchAction) {
                    case 'activate':
                        $this->toggleStatus($type, $id, true);
                        break;
                    case 'deactivate':
                        $this->toggleStatus($type, $id, false);
                        break;
                }
            }

            DB::commit();

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => '批次操作完成'
            ]);

            $this->selectedItems = [];
            $this->selectAll = false;
            $this->batchAction = '';

        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '批次操作失敗：' . $e->getMessage()
            ]);
        }
    }

    private function toggleStatus(string $type, int $id, bool $status): void
    {
        if ($type === 'agent') {
            Agent::findOrFail($id)->update(['is_active' => $status]);
        } else {
            Player::findOrFail($id)->update(['is_active' => $status]);
        }
    }

    public function exportData(): void
    {
        $this->dispatch('export-points-data', [
            'filters' => [
                'search' => $this->search,
                'typeFilter' => $this->typeFilter,
                'statusFilter' => $this->statusFilter,
            ]
        ]);
    }
}