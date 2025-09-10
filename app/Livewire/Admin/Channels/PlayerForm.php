<?php

namespace App\Livewire\Admin\Channels;

use App\Models\Player;
use App\Models\Agent;
use App\Services\PlayerService;
use Livewire\Component;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;
use Exception;

/**
 * 玩家表單 Livewire 元件
 * 
 * 負責處理玩家的建立和編輯表單
 * 包含代理選擇、點數分配、表單驗證等功能
 */
class PlayerForm extends Component
{
    public ?Player $player = null;
    public bool $isEdit = false;

    // 表單欄位
    public string $name = '';
    public string $username = '';
    public string $email = '';
    public string $phone = '';
    public ?int $agent_id = null;
    public float $initial_points = 0;
    public bool $is_active = true;
    public string $notes = '';

    // 選項資料和 UI 狀態
    public Collection $agentOptions;
    public string $previewAccount = '';
    public array $selectedAgentPath = [];
    public string $agentSearchTerm = '';
    public bool $showAgentDropdown = false;
    public Collection $filteredAgents;

    // 點數管理
    public bool $showPointsManagement = false;
    public float $pointsToAdd = 0;
    public float $pointsToDeduct = 0;
    public string $pointsDescription = '';

    /**
     * 驗證規則
     */
    protected function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'username' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9_]+$/',
                Rule::unique('players', 'username')->ignore($this->player?->id)->whereNull('deleted_at'),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('players', 'email')->ignore($this->player?->id)->whereNull('deleted_at'),
            ],
            'phone' => 'nullable|string|max:20',
            'agent_id' => 'required|exists:agents,id',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ];

        // 編輯模式不需要驗證初始點數
        if (!$this->isEdit) {
            $rules['initial_points'] = 'required|numeric|min:0|max:999999999.99';
        }

        // 點數操作驗證
        if ($this->showPointsManagement) {
            $rules['pointsToAdd'] = 'nullable|numeric|min:0|max:999999999.99';
            $rules['pointsToDeduct'] = 'nullable|numeric|min:0|max:999999999.99';
            $rules['pointsDescription'] = 'nullable|string|max:255';
        }

        return $rules;
    }

    /**
     * 驗證訊息
     */
    protected function messages(): array
    {
        return [
            'name.required' => '玩家姓名為必填欄位',
            'name.max' => '玩家姓名不能超過 255 個字元',
            'username.required' => '使用者名稱為必填欄位',
            'username.regex' => '使用者名稱只能包含英文字母、數字和底線',
            'username.unique' => '此使用者名稱已被使用',
            'username.max' => '使用者名稱不能超過 50 個字元',
            'email.required' => '電子郵件為必填欄位',
            'email.email' => '請輸入有效的電子郵件格式',
            'email.unique' => '此電子郵件已被使用',
            'phone.max' => '電話號碼不能超過 20 個字元',
            'agent_id.required' => '請選擇隸屬代理',
            'agent_id.exists' => '選擇的代理不存在',
            'initial_points.required' => '初始點數為必填欄位',
            'initial_points.numeric' => '初始點數必須是數字',
            'initial_points.min' => '初始點數不能小於 0',
            'initial_points.max' => '初始點數不能超過 999,999,999.99',
            'notes.max' => '備註不能超過 1000 個字元',
            'pointsToAdd.numeric' => '增加點數必須是數字',
            'pointsToAdd.min' => '增加點數不能小於 0',
            'pointsToAdd.max' => '增加點數不能超過 999,999,999.99',
            'pointsToDeduct.numeric' => '扣除點數必須是數字',
            'pointsToDeduct.min' => '扣除點數不能小於 0',
            'pointsToDeduct.max' => '扣除點數不能超過 999,999,999.99',
            'pointsDescription.max' => '點數操作描述不能超過 255 個字元',
        ];
    }

    /**
     * 元件掛載
     */
    public function mount(?Player $player = null): void
    {
        // 檢查權限
        if ($player) {
            if (!auth()->user()->can('channels.players.edit')) {
                abort(403, '您沒有編輯玩家的權限');
            }
            $this->player = $player;
            $this->isEdit = true;
            $this->fillFormData($player);
        } else {
            if (!auth()->user()->can('channels.players.create')) {
                abort(403, '您沒有建立玩家的權限');
            }
        }

        $this->loadAgentOptions();
        $this->updatePreviewAccount();
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.channels.player-form');
    }

    /**
     * 儲存表單
     */
    public function save()
    {
        $this->validate();

        try {
            $data = [
                'name' => trim($this->name),
                'username' => trim($this->username),
                'email' => trim($this->email),
                'phone' => trim($this->phone) ?: null,
                'agent_id' => $this->agent_id,
                'is_active' => $this->is_active,
                'notes' => trim($this->notes) ?: null,
            ];

            if (!$this->isEdit) {
                $data['initial_points'] = $this->initial_points;
            }

            $playerService = app(PlayerService::class);

            if ($this->isEdit) {
                $playerService->updatePlayer($this->player, $data);
                $message = '玩家資料更新成功';
                $this->dispatch('player-updated', playerId: $this->player->id);
            } else {
                $player = $playerService->createPlayer($data);
                $message = '玩家建立成功';
                $this->dispatch('player-created', playerId: $player->id);
            }

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => $message,
            ]);

            // 如果是建立模式，重定向到玩家列表
            if (!$this->isEdit) {
                return redirect()->route('admin.channels.players.index');
            }

        } catch (Exception $e) {
            \Log::error('玩家表單儲存失敗', [
                'error' => $e->getMessage(),
                'is_edit' => $this->isEdit,
                'player_id' => $this->player?->id,
                'user' => auth()->user()->username ?? 'unknown',
            ]);

            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '操作失敗：' . $e->getMessage(),
            ]);
        }
    }

    /**
     * 取消操作
     */
    public function cancel()
    {
        return redirect()->route('admin.channels.players.index');
    }

    /**
     * 當代理選擇變更時
     */
    public function updatedAgentId(): void
    {
        $this->updatePreviewAccount();
        $this->updateSelectedAgentPath();
        $this->resetValidation(['agent_id']);
        
        // 檢查代理點數是否足夠
        if (!$this->isEdit && $this->agent_id && $this->initial_points > 0) {
            $this->checkAgentPoints();
        }
    }

    /**
     * 當使用者名稱變更時
     */
    public function updatedUsername(): void
    {
        $this->updatePreviewAccount();
        $this->resetValidation('username');
    }

    /**
     * 當初始點數變更時
     */
    public function updatedInitialPoints(): void
    {
        $this->resetValidation('initial_points');
        
        if (!$this->isEdit && $this->agent_id && $this->initial_points > 0) {
            $this->checkAgentPoints();
        }
    }

    /**
     * 當代理搜尋詞變更時
     */
    public function updatedAgentSearchTerm(): void
    {
        $this->filterAgents();
        $this->showAgentDropdown = !empty($this->agentSearchTerm);
    }

    /**
     * 選擇代理
     */
    public function selectAgent(int $agentId): void
    {
        $this->agent_id = $agentId;
        $this->showAgentDropdown = false;
        
        $selectedAgent = $this->agentOptions->find($agentId);
        if ($selectedAgent) {
            $this->agentSearchTerm = $selectedAgent->name;
        }
        
        $this->updatedAgentId();
    }

    /**
     * 切換代理下拉選單
     */
    public function toggleAgentDropdown(): void
    {
        $this->showAgentDropdown = !$this->showAgentDropdown;
        if ($this->showAgentDropdown) {
            $this->filterAgents();
        }
    }

    /**
     * 切換點數管理面板
     */
    public function togglePointsManagement(): void
    {
        if (!$this->isEdit) {
            return;
        }

        $this->showPointsManagement = !$this->showPointsManagement;
        
        if (!$this->showPointsManagement) {
            $this->pointsToAdd = 0;
            $this->pointsToDeduct = 0;
            $this->pointsDescription = '';
            $this->resetValidation(['pointsToAdd', 'pointsToDeduct', 'pointsDescription']);
        }
    }

    /**
     * 增加玩家點數
     */
    public function addPoints(): void
    {
        if (!$this->isEdit || $this->pointsToAdd <= 0) {
            return;
        }

        $this->validate([
            'pointsToAdd' => 'required|numeric|min:0.01|max:999999999.99',
            'pointsDescription' => 'nullable|string|max:255',
        ]);

        try {
            $playerService = app(PlayerService::class);
            $agent = $this->player->agent;

            // 檢查代理是否有足夠點數
            if (!$agent->canAllocatePoints($this->pointsToAdd)) {
                $this->addError('pointsToAdd', "代理剩餘點數不足，可用點數：{$agent->remaining_points}");
                return;
            }

            // 使用 PointService 分配點數
            $pointService = app(\App\Services\PointService::class);
            $pointService->allocatePointsToPlayer(
                $this->player,
                $this->pointsToAdd,
                $agent
            );

            // 重新載入玩家資料
            $this->player->refresh();

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => "已為玩家 {$this->player->name} 增加 {$this->pointsToAdd} 點數"
            ]);

            // 重置表單
            $this->pointsToAdd = 0;
            $this->pointsDescription = '';

        } catch (Exception $e) {
            \Log::error('增加玩家點數失敗', [
                'player_id' => $this->player->id,
                'amount' => $this->pointsToAdd,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '增加點數失敗：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 扣除玩家點數
     */
    public function deductPoints(): void
    {
        if (!$this->isEdit || $this->pointsToDeduct <= 0) {
            return;
        }

        $this->validate([
            'pointsToDeduct' => 'required|numeric|min:0.01|max:999999999.99',
            'pointsDescription' => 'nullable|string|max:255',
        ]);

        try {
            // 檢查玩家是否有足夠點數
            if (!$this->player->canDeductPoints($this->pointsToDeduct)) {
                $this->addError('pointsToDeduct', "玩家點數不足，目前點數：{$this->player->points}");
                return;
            }

            // 使用 PointService 回收點數
            $pointService = app(\App\Services\PointService::class);
            $pointService->recoverPointsFromPlayer(
                $this->player,
                $this->pointsToDeduct,
                $this->player->agent
            );

            // 重新載入玩家資料
            $this->player->refresh();

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => "已從玩家 {$this->player->name} 扣除 {$this->pointsToDeduct} 點數"
            ]);

            // 重置表單
            $this->pointsToDeduct = 0;
            $this->pointsDescription = '';

        } catch (Exception $e) {
            \Log::error('扣除玩家點數失敗', [
                'player_id' => $this->player->id,
                'amount' => $this->pointsToDeduct,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '扣除點數失敗：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 填充表單資料（編輯模式）
     */
    private function fillFormData(Player $player): void
    {
        $this->name = $player->name ?? '';
        $this->username = $player->username ?? '';
        $this->email = $player->email ?? '';
        $this->phone = $player->phone ?? '';
        $this->agent_id = $player->agent_id;
        $this->is_active = $player->is_active ?? true;
        $this->notes = $player->notes ?? '';
        
        // 設定代理搜尋詞
        if ($player->agent) {
            $this->agentSearchTerm = $player->agent->name;
        }
    }

    /**
     * 載入代理選項
     */
    private function loadAgentOptions(): void
    {
        $this->agentOptions = Agent::where('is_active', true)
            ->with('parent')
            ->orderBy('level')
            ->orderBy('name')
            ->get();
            
        $this->filteredAgents = $this->agentOptions;
        $this->updateSelectedAgentPath();
    }

    /**
     * 篩選代理選項
     */
    private function filterAgents(): void
    {
        if (empty($this->agentSearchTerm)) {
            $this->filteredAgents = $this->agentOptions;
            return;
        }

        $this->filteredAgents = $this->agentOptions->filter(function ($agent) {
            return str_contains(strtolower($agent->name), strtolower($this->agentSearchTerm)) ||
                   str_contains(strtolower($agent->account), strtolower($this->agentSearchTerm));
        });
    }

    /**
     * 更新帳號預覽
     */
    private function updatePreviewAccount(): void
    {
        if (!$this->username || !$this->agent_id) {
            $this->previewAccount = '';
            return;
        }

        $agent = $this->agentOptions->find($this->agent_id);
        if ($agent) {
            $this->previewAccount = $agent->full_prefix . $this->username;
        }
    }

    /**
     * 更新選中代理的路徑
     */
    private function updateSelectedAgentPath(): void
    {
        if (!$this->agent_id) {
            $this->selectedAgentPath = [];
            return;
        }

        $agent = $this->agentOptions->find($this->agent_id);
        if ($agent) {
            $path = [];
            $current = $agent;
            
            while ($current) {
                $path[] = [
                    'id' => $current->id,
                    'name' => $current->name,
                    'level' => $current->level,
                    'remaining_points' => $current->remaining_points ?? 0,
                ];
                $current = $current->parent;
            }
            
            $this->selectedAgentPath = array_reverse($path);
        }
    }

    /**
     * 檢查代理點數是否足夠
     */
    private function checkAgentPoints(): void
    {
        if (!$this->agent_id || $this->initial_points <= 0) {
            return;
        }

        $agent = $this->agentOptions->find($this->agent_id);
        if ($agent && !$agent->canAllocatePoints($this->initial_points)) {
            $this->addError('initial_points', "代理剩餘點數不足，可用點數：{$agent->remaining_points}");
        }
    }

    /**
     * 取得代理顯示文字
     */
    public function getAgentDisplayText(Agent $agent): string
    {
        $path = [];
        $current = $agent;
        
        // 建立路徑陣列
        while ($current) {
            $path[] = $current->name . " (第{$current->level}層)";
            $current = $current->parent;
        }
        
        // 反轉陣列以顯示從根到當前的路徑
        $pathString = implode(' → ', array_reverse($path));
        
        return "{$pathString} - 剩餘點數：{$agent->remaining_points}";
    }

    /**
     * 取得玩家統計資訊（編輯模式）
     */
    public function getPlayerStatistics(): array
    {
        if (!$this->isEdit || !$this->player) {
            return [];
        }

        $playerService = app(PlayerService::class);
        return $playerService->getPlayerStatistics($this->player);
    }
}