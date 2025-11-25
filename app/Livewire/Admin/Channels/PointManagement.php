<?php

namespace App\Livewire\Admin\Channels;

use App\Livewire\Admin\AdminComponent;
use App\Models\Agent;
use App\Models\Player;
use App\Models\PointTransaction;
use App\Services\PointService;
use App\Exceptions\InsufficientPointsException;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Illuminate\Support\Collection;
use Exception;

/**
 * 點數管理 Livewire 元件
 * 
 * 提供完整的點數管理功能，包括：
 * - 點數分配和回收介面
 * - 點數交易歷史顯示
 * - 點數統計和儀表板
 */
class PointManagement extends AdminComponent
{
    use WithPagination;

    // 當前管理的代理
    public ?Agent $agent = null;
    public bool $isSystemLevel = false;

    // 活動標籤
    public string $activeTab = 'overview';

    // 點數操作相關屬性
    public string $operation = '';
    public string $targetType = '';
    public ?int $targetId = null;
    public float $amount = 0;
    public string $description = '';
    public bool $showOperationModal = false;

    // 批量操作
    public array $selectedTargets = [];
    public bool $batchMode = false;
    public float $batchAmount = 0;
    public string $batchDescription = '';

    // 篩選和搜尋
    public string $search = '';
    public string $transactionTypeFilter = 'all';
    public string $dateFrom = '';
    public string $dateTo = '';
    public float $amountMin = 0;
    public float $amountMax = 0;
    public int $perPage = 25;

    // 統計資料快取
    public array $statistics = [];
    public Collection $childrenWithPoints;
    public Collection $playersWithPoints;

    protected $queryString = [
        'search' => ['except' => ''],
        'transactionTypeFilter' => ['except' => 'all'],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'activeTab' => ['except' => 'overview'],
        'perPage' => ['except' => 25],
    ];

    protected function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01|max:999999999',
            'description' => 'nullable|string|max:255',
            'targetId' => 'required|integer|min:1',
            'batchAmount' => 'required_if:batchMode,true|numeric|min:0.01|max:999999999',
            'batchDescription' => 'nullable|string|max:255',
        ];
    }

    protected function messages(): array
    {
        return [
            'amount.required' => '請輸入點數金額',
            'amount.numeric' => '點數金額必須為數字',
            'amount.min' => '點數金額必須大於 0',
            'amount.max' => '點數金額不能超過 999,999,999',
            'targetId.required' => '請選擇目標對象',
            'batchAmount.required_if' => '請輸入批量操作金額',
            'batchAmount.numeric' => '批量操作金額必須為數字',
            'batchAmount.min' => '批量操作金額必須大於 0',
        ];
    }

    /**
     * 元件掛載時執行
     * 
     * @param Agent|null $agent 代理物件（系統級管理時為 null）
     * @return void
     */
    public function mount(?Agent $agent = null): void
    {
        // 檢查權限
        $this->checkPermission('channels.points.view');

        // 設定代理和系統級別標記
        $this->agent = $agent;
        $this->isSystemLevel = is_null($agent);

        // 初始化日期篩選（預設最近30天）
        $this->dateTo = now()->format('Y-m-d');
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');

        // 載入初始資料
        $this->loadData();
    }

    public function render()
    {
        return view('livewire.admin.channels.point-management', [
            'transactions' => $this->getTransactions(),
            'transactionTypes' => PointTransaction::getTransactionTypes(),
            'availableAgents' => $this->getAvailableAgents(),
            'availablePlayers' => $this->getAvailablePlayers(),
        ]);
    }

    /**
     * 載入基礎資料
     */
    public function loadData(): void
    {
        try {
            $pointService = app(PointService::class);
            
            // 載入統計資料
            $this->statistics = $pointService->getPointsStatistics($this->agent);
            
            // 載入下層代理和玩家資料
            if ($this->agent) {
                $this->childrenWithPoints = $this->agent->children()
                    ->with(['children', 'players'])
                    ->get()
                    ->map(function ($child) {
                        return [
                            'id' => $child->id,
                            'name' => $child->name,
                            'account' => $child->account,
                            'level' => $child->level,
                            'total_points' => $child->total_points,
                            'allocated_points' => $child->allocated_points,
                            'remaining_points' => $child->remaining_points,
                            'children_count' => $child->children->count(),
                            'players_count' => $child->players->count(),
                            'is_active' => $child->is_active,
                        ];
                    });

                $this->playersWithPoints = $this->agent->players()
                    ->get()
                    ->map(function ($player) {
                        return [
                            'id' => $player->id,
                            'name' => $player->name,
                            'account' => $player->account,
                            'points' => $player->points,
                            'is_active' => $player->is_active,
                        ];
                    });
            } else {
                // 系統級檢視
                $this->childrenWithPoints = Agent::where('level', 1)
                    ->with(['children', 'players'])
                    ->get()
                    ->map(function ($agent) {
                        return [
                            'id' => $agent->id,
                            'name' => $agent->name,
                            'account' => $agent->account,
                            'level' => $agent->level,
                            'total_points' => $agent->total_points,
                            'allocated_points' => $agent->allocated_points,
                            'remaining_points' => $agent->remaining_points,
                            'children_count' => $agent->getAllDescendants()->count(),
                            'players_count' => $agent->players->count(),
                            'is_active' => $agent->is_active,
                        ];
                    });

                $this->playersWithPoints = collect();
            }

        } catch (Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '載入資料失敗：' . $e->getMessage(),
            ]);
        }
    }

    /**
     * 切換活動標籤
     */
    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    /**
     * 開啟點數操作模態框
     */
    public function openOperationModal(string $operation, string $targetType, int $targetId): void
    {
        $this->operation = $operation;
        $this->targetType = $targetType;
        $this->targetId = $targetId;
        $this->amount = 0;
        $this->description = '';
        $this->showOperationModal = true;
        $this->resetValidation();
    }

    /**
     * 關閉操作模態框
     */
    public function closeOperationModal(): void
    {
        $this->showOperationModal = false;
        $this->reset(['operation', 'targetType', 'targetId', 'amount', 'description']);
        $this->resetValidation();
    }

    /**
     * 執行點數操作
     */
    public function executeOperation(): void
    {
        // 檢查操作權限
        if ($this->operation === 'allocate') {
            $this->checkPermission('channels.points.allocate');
        } else {
            $this->checkPermission('channels.points.recover');
        }

        $this->validate([
            'amount' => 'required|numeric|min:0.01|max:999999999',
            'targetId' => 'required|integer|min:1',
        ]);

        try {
            $pointService = app(PointService::class);

            if ($this->targetType === 'agent') {
                $target = Agent::findOrFail($this->targetId);
                
                if ($this->operation === 'allocate') {
                    if ($this->agent) {
                        $pointService->allocatePointsToAgent($target, $this->amount, $this->agent);
                    } else {
                        $pointService->systemAdjustPoints($target, $this->amount, $this->description ?: '系統分配點數');
                    }
                    $message = "成功分配 {$this->amount} 點給代理 {$target->name}";
                } else {
                    $pointService->recoverPointsFromAgent($target, $this->amount, $this->agent ?? $target->parent);
                    $message = "成功從代理 {$target->name} 回收 {$this->amount} 點";
                }
            } else {
                $target = Player::findOrFail($this->targetId);
                
                if ($this->operation === 'allocate') {
                    $pointService->allocatePointsToPlayer($target, $this->amount, $this->agent ?? $target->agent);
                    $message = "成功分配 {$this->amount} 點給玩家 {$target->name}";
                } else {
                    $pointService->recoverPointsFromPlayer($target, $this->amount, $this->agent ?? $target->agent);
                    $message = "成功從玩家 {$target->name} 回收 {$this->amount} 點";
                }
            }

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => $message,
            ]);

            $this->closeOperationModal();
            $this->loadData();
            $this->resetPage();

        } catch (InsufficientPointsException $e) {
            $this->addError('amount', $e->getMessage());
        } catch (Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '操作失敗：' . $e->getMessage(),
            ]);
        }
    }

    /**
     * 切換批量模式
     */
    public function toggleBatchMode(): void
    {
        $this->batchMode = !$this->batchMode;
        $this->selectedTargets = [];
        $this->batchAmount = 0;
        $this->batchDescription = '';
    }

    /**
     * 切換目標選擇
     */
    public function toggleTarget(string $type, int $id): void
    {
        $key = "{$type}_{$id}";
        
        if (in_array($key, $this->selectedTargets)) {
            $this->selectedTargets = array_filter($this->selectedTargets, fn($item) => $item !== $key);
        } else {
            $this->selectedTargets[] = $key;
        }
    }

    /**
     * 執行批量操作
     */
    public function executeBatchOperation(string $operation): void
    {
        // 檢查操作權限
        if ($operation === 'allocate') {
            $this->checkPermission('channels.points.allocate');
        } else {
            $this->checkPermission('channels.points.recover');
        }

        if (empty($this->selectedTargets)) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '請選擇要操作的目標',
            ]);
            return;
        }

        $this->validate([
            'batchAmount' => 'required|numeric|min:0.01|max:999999999',
        ]);

        try {
            $pointService = app(PointService::class);
            $agentAllocations = [];
            $playerAllocations = [];

            // 分類選中的目標
            foreach ($this->selectedTargets as $target) {
                [$type, $id] = explode('_', $target);
                
                if ($type === 'agent') {
                    $agentAllocations[] = ['agent_id' => (int)$id, 'amount' => $this->batchAmount];
                } else {
                    $playerAllocations[] = ['player_id' => (int)$id, 'amount' => $this->batchAmount];
                }
            }

            // 執行批量操作
            if (!empty($agentAllocations)) {
                if ($operation === 'allocate') {
                    $pointService->batchAllocateToAgents($agentAllocations, $this->agent);
                }
            }

            if (!empty($playerAllocations)) {
                if ($operation === 'allocate') {
                    $pointService->batchAllocateToPlayers($playerAllocations, $this->agent);
                }
            }

            $totalTargets = count($this->selectedTargets);
            $totalAmount = $this->batchAmount * $totalTargets;
            
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => "批量操作成功：對 {$totalTargets} 個目標分配了總計 {$totalAmount} 點",
            ]);

            $this->toggleBatchMode();
            $this->loadData();
            $this->resetPage();

        } catch (Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '批量操作失敗：' . $e->getMessage(),
            ]);
        }
    }

    /**
     * 重置篩選條件
     */
    public function resetFilters(): void
    {
        $this->search = '';
        $this->transactionTypeFilter = 'all';
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->amountMin = 0;
        $this->amountMax = 0;
        $this->resetPage();
    }

    /**
     * 匯出交易記錄
     */
    public function exportTransactions(): void
    {
        try {
            // 這裡可以實作匯出功能
            $this->dispatch('show-toast', [
                'type' => 'info',
                'message' => '匯出功能開發中...',
            ]);
        } catch (Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '匯出失敗：' . $e->getMessage(),
            ]);
        }
    }

    /**
     * 執行點數稽核
     */
    public function auditPoints(): void
    {
        try {
            $pointService = app(PointService::class);
            $auditResult = $pointService->auditPoints();

            if ($auditResult['total_issues'] === 0) {
                $this->dispatch('show-toast', [
                    'type' => 'success',
                    'message' => '點數稽核完成，未發現問題',
                ]);
            } else {
                $this->dispatch('show-toast', [
                    'type' => 'warning',
                    'message' => "點數稽核完成，發現 {$auditResult['total_issues']} 個問題",
                ]);
            }

        } catch (Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '稽核失敗：' . $e->getMessage(),
            ]);
        }
    }

    /**
     * 重新載入資料
     */
    #[On('refresh-data')]
    public function refreshData(): void
    {
        $this->loadData();
        $this->resetPage();
    }

    /**
     * 取得交易記錄
     */
    private function getTransactions()
    {
        $filters = [];

        if ($this->transactionTypeFilter !== 'all') {
            $filters['type'] = $this->transactionTypeFilter;
        }

        if ($this->dateFrom) {
            $filters['date_from'] = $this->dateFrom . ' 00:00:00';
        }

        if ($this->dateTo) {
            $filters['date_to'] = $this->dateTo . ' 23:59:59';
        }

        if ($this->amountMin > 0) {
            $filters['amount_min'] = $this->amountMin;
        }

        if ($this->amountMax > 0) {
            $filters['amount_max'] = $this->amountMax;
        }

        $pointService = app(PointService::class);
        $transactions = $pointService->getTransactionHistory($this->agent, $this->perPage, $filters);

        // 如果有搜尋條件，進一步篩選
        if ($this->search) {
            $transactions->getCollection()->transform(function ($transaction) {
                $searchTerm = strtolower($this->search);
                $description = strtolower($transaction->description);
                $targetName = strtolower($transaction->target_name ?? '');
                
                if (str_contains($description, $searchTerm) || str_contains($targetName, $searchTerm)) {
                    return $transaction;
                }
                
                return null;
            })->filter();
        }

        return $transactions;
    }

    /**
     * 取得可用的代理列表
     */
    private function getAvailableAgents(): Collection
    {
        if ($this->agent) {
            return $this->agent->children()->where('is_active', true)->get();
        }
        
        return Agent::where('is_active', true)->where('level', 1)->get();
    }

    /**
     * 取得可用的玩家列表
     */
    private function getAvailablePlayers(): Collection
    {
        if ($this->agent) {
            return $this->agent->players()->where('is_active', true)->get();
        }
        
        return collect();
    }

    /**
     * 更新每頁顯示數量
     */
    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    /**
     * 更新搜尋條件時重置分頁
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * 更新篩選條件時重置分頁
     */
    public function updatedTransactionTypeFilter(): void
    {
        $this->resetPage();
    }

    /**
     * 更新日期篩選時重置分頁
     */
    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    /**
     * 更新日期篩選時重置分頁
     */
    public function updatedDateTo(): void
    {
        $this->resetPage();
    }
}