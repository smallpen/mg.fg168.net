<?php

namespace App\Livewire\Agent\Dashboard;

use App\Models\Agent;
use App\Models\Player;
use App\Models\PointTransaction;
use App\Services\PointService;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * 代理點數管理元件
 * 
 * 允許代理管理其點數分配和回收
 */
class PointManagement extends Component
{
    use WithPagination;

    public Agent $agent;
    public string $activeTab = 'overview';
    
    // 點數操作相關屬性
    public string $operation = '';
    public string $targetType = '';
    public ?int $targetId = null;
    public float $amount = 0;
    public string $description = '';
    public bool $showModal = false;

    protected $rules = [
        'amount' => 'required|numeric|min:0.01',
        'description' => 'nullable|string|max:255',
    ];

    public function mount(): void
    {
        $this->agent = auth()->user()->agent;
    }

    /**
     * 開啟點數操作模態框
     */
    public function openPointModal(string $operation, string $targetType, int $targetId): void
    {
        $this->operation = $operation;
        $this->targetType = $targetType;
        $this->targetId = $targetId;
        $this->amount = 0;
        $this->description = '';
        $this->showModal = true;
        $this->resetValidation();
    }

    /**
     * 關閉模態框
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['operation', 'targetType', 'targetId', 'amount', 'description']);
    }

    /**
     * 執行點數操作
     */
    public function executePointOperation(): void
    {
        $this->validate();

        try {
            $pointService = app(PointService::class);

            if ($this->targetType === 'agent') {
                $target = Agent::findOrFail($this->targetId);
                
                // 檢查權限：只能操作直屬下層代理
                if ($target->parent_id !== $this->agent->id) {
                    throw new \Exception('您只能管理直屬下層代理的點數');
                }

                if ($this->operation === 'allocate') {
                    $pointService->allocatePointsToAgent($target, $this->amount, $this->agent);
                    $message = "成功分配 {$this->amount} 點數給代理 {$target->name}";
                } else {
                    $pointService->recoverPointsFromAgent($target, $this->amount, $this->agent);
                    $message = "成功從代理 {$target->name} 回收 {$this->amount} 點數";
                }
            } else {
                $target = Player::findOrFail($this->targetId);
                
                // 檢查權限：只能操作隸屬玩家
                if ($target->agent_id !== $this->agent->id) {
                    throw new \Exception('您只能管理隸屬於您的玩家點數');
                }

                if ($this->operation === 'allocate') {
                    $pointService->allocatePointsToPlayer($target, $this->amount, $this->agent);
                    $message = "成功分配 {$this->amount} 點數給玩家 {$target->name}";
                } else {
                    $pointService->recoverPointsFromPlayer($target, $this->amount, $this->agent);
                    $message = "成功從玩家 {$target->name} 回收 {$this->amount} 點數";
                }
            }

            $this->closeModal();
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => $message
            ]);

            // 重新載入代理資料
            $this->agent->refresh();

        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '操作失敗：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 取得點數統計
     */
    public function getPointStatsProperty(): array
    {
        return [
            'total_points' => $this->agent->total_points,
            'allocated_points' => $this->agent->allocated_points,
            'remaining_points' => $this->agent->remaining_points,
            'utilization_rate' => $this->agent->total_points > 0 
                ? round(($this->agent->allocated_points / $this->agent->total_points) * 100, 2) 
                : 0,
        ];
    }

    /**
     * 取得下層代理點數資訊
     */
    public function getChildrenPointsProperty()
    {
        return $this->agent->children()
            ->where('is_active', true)
            ->select(['id', 'name', 'account', 'total_points', 'allocated_points', 'remaining_points'])
            ->get();
    }

    /**
     * 取得玩家點數資訊
     */
    public function getPlayersPointsProperty()
    {
        return $this->agent->players()
            ->where('is_active', true)
            ->select(['id', 'name', 'account', 'points'])
            ->get();
    }

    /**
     * 取得點數交易記錄
     */
    public function getTransactionsProperty()
    {
        return PointTransaction::where('agent_id', $this->agent->id)
            ->orWhereHas('player', function ($query) {
                $query->where('agent_id', $this->agent->id);
            })
            ->with(['agent', 'player', 'creator'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    public function render()
    {
        return view('livewire.agent.dashboard.point-management');
    }
}