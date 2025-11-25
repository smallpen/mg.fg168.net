<?php

namespace App\Livewire\Admin\Channels;

use App\Models\Agent;
use App\Models\User;
use App\Services\AgentService;
use Livewire\Component;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;
use Exception;

/**
 * 代理表單 Livewire 元件
 * 
 * 負責處理代理的建立和編輯表單
 * 包含前置符號選擇、上層代理選擇、表單驗證等功能
 */
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
    public array $prefixOptions = [];
    public array $availablePrefixes = [];

    // UI 狀態
    public bool $showPrefixField = true;
    public bool $showParentField = false;
    public string $previewAccount = '';

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
                Rule::unique('agents', 'username')->ignore($this->agent?->id)->whereNull('deleted_at'),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('agents', 'email')->ignore($this->agent?->id)->whereNull('deleted_at'),
            ],
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ];

        // 編輯模式不需要驗證點數和層級相關欄位
        if (!$this->isEdit) {
            $rules['initial_points'] = 'required|numeric|min:0|max:999999999.99';

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

    /**
     * 驗證訊息
     */
    protected function messages(): array
    {
        return [
            'name.required' => '代理姓名為必填欄位',
            'name.max' => '代理姓名不能超過 255 個字元',
            'username.required' => '使用者名稱為必填欄位',
            'username.regex' => '使用者名稱只能包含英文字母、數字和底線',
            'username.unique' => '此使用者名稱已被使用',
            'username.max' => '使用者名稱不能超過 50 個字元',
            'email.required' => '電子郵件為必填欄位',
            'email.email' => '請輸入有效的電子郵件格式',
            'email.unique' => '此電子郵件已被使用',
            'phone.max' => '電話號碼不能超過 20 個字元',
            'prefix.required' => '前置符號為必填欄位',
            'prefix.regex' => '前置符號必須是 a-z 的單一字母',
            'prefix.unique' => '此前置符號已被使用',
            'parent_id.required' => '請選擇上層代理',
            'parent_id.exists' => '選擇的上層代理不存在',
            'initial_points.required' => '初始點數為必填欄位',
            'initial_points.numeric' => '初始點數必須是數字',
            'initial_points.min' => '初始點數不能小於 0',
            'initial_points.max' => '初始點數不能超過 999,999,999.99',
            'notes.max' => '備註不能超過 1000 個字元',
        ];
    }

    /**
     * 元件掛載
     */
    public function mount(?Agent $agent = null, ?int $parentId = null): void
    {
        // 檢查權限
        if ($agent && $agent->exists) {
            if (!auth()->user()->can('channels.agents.edit')) {
                abort(403, '您沒有編輯代理的權限');
            }
            $this->agent = $agent;
            $this->isEdit = true;
            $this->fillFormData($agent);
        } else {
            if (!auth()->user()->can('channels.agents.create')) {
                abort(403, '您沒有建立代理的權限');
            }
            
            // 如果有指定上層代理，自動設定
            if ($parentId) {
                $parentAgent = Agent::find($parentId);
                if ($parentAgent) {
                    $this->parent_id = $parentId;
                }
            }
        }

        $this->loadOptions();
        $this->updateUIState();
        $this->updatePreviewAccount();
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.channels.agent-form');
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
                'is_active' => $this->is_active,
                'notes' => trim($this->notes) ?: null,
            ];

            if (!$this->isEdit) {
                $data['prefix'] = $this->prefix ?: null;
                $data['parent_id'] = $this->parent_id;
                $data['initial_points'] = $this->initial_points;
            }

            $agentService = app(AgentService::class);

            if ($this->isEdit) {
                $agentService->updateAgent($this->agent, $data);
                $message = '代理資料更新成功';
                $this->dispatch('agent-updated', agentId: $this->agent->id);
            } else {
                $agent = $agentService->createAgent($data);
                $message = '代理建立成功';
                $this->dispatch('agent-created', agentId: $agent->id);
            }

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => $message,
            ]);

            // 如果是建立模式，重定向到代理列表
            if (!$this->isEdit) {
                return redirect()->route('admin.channels.agents.index');
            }

        } catch (Exception $e) {
            \Log::error('代理表單儲存失敗', [
                'error' => $e->getMessage(),
                'is_edit' => $this->isEdit,
                'agent_id' => $this->agent?->id,
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
        return redirect()->route('admin.channels.agents.index');
    }

    /**
     * 當上層代理選擇變更時
     */
    public function updatedParentId(): void
    {
        $this->updateUIState();
        $this->updatePreviewAccount();
        
        // 清除前置符號（下層代理不需要）
        if ($this->parent_id) {
            $this->prefix = '';
        }
        
        $this->resetValidation(['prefix', 'parent_id']);
    }

    /**
     * 當前置符號變更時
     */
    public function updatedPrefix(): void
    {
        $this->updatePreviewAccount();
        $this->resetValidation('prefix');
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
     * 填充表單資料（編輯模式）
     */
    private function fillFormData(Agent $agent): void
    {
        $this->name = $agent->name ?? '';
        $this->username = $agent->username ?? '';
        $this->email = $agent->email ?? '';
        $this->phone = $agent->phone ?? '';
        $this->prefix = $agent->prefix ?? '';
        $this->parent_id = $agent->parent_id;
        $this->is_active = $agent->is_active ?? true;
        $this->notes = $agent->notes ?? '';
    }

    /**
     * 載入選項資料
     */
    private function loadOptions(): void
    {
        // 載入上層代理選項（排除自己和下層代理）
        $excludeIds = [];
        if ($this->agent) {
            $excludeIds[] = $this->agent->id;
            // 排除所有下層代理
            $descendants = $this->agent->getAllDescendants();
            $excludeIds = array_merge($excludeIds, $descendants->pluck('id')->toArray());
        }

        $this->parentOptions = Agent::where('is_active', true)
            ->when(!empty($excludeIds), function ($query) use ($excludeIds) {
                $query->whereNotIn('id', $excludeIds);
            })
            ->with('parent')
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        // 載入可用的前置符號
        try {
            $agentService = app(AgentService::class);
            $this->availablePrefixes = $agentService->getAvailablePrefixes();
        } catch (\Exception $e) {
            // 如果服務不可用，手動計算可用的前置符號
            $allPrefixes = range('a', 'z');
            $usedPrefixes = Agent::where('level', 1)
                ->whereNotNull('prefix')
                ->pluck('prefix')
                ->map(function ($prefix) {
                    return strtolower($prefix); // 統一轉為小寫
                })
                ->toArray();
            $this->availablePrefixes = array_diff($allPrefixes, $usedPrefixes);
        }
        
        // 如果是編輯模式且代理有前置符號，將其加入可用選項
        if ($this->isEdit && $this->agent && $this->agent->prefix) {
            if (!in_array($this->agent->prefix, $this->availablePrefixes)) {
                $this->availablePrefixes[] = $this->agent->prefix;
                sort($this->availablePrefixes);
            }
        }

        $this->prefixOptions = !empty($this->availablePrefixes) 
            ? array_combine($this->availablePrefixes, $this->availablePrefixes)
            : [];
    }

    /**
     * 更新 UI 狀態
     */
    private function updateUIState(): void
    {
        if ($this->isEdit) {
            // 編輯模式：根據代理層級決定顯示哪些欄位
            $this->showPrefixField = $this->agent && $this->agent->level === 1;
            $this->showParentField = false; // 編輯時不允許變更上層代理
        } else {
            // 建立模式：根據是否選擇上層代理決定
            $this->showPrefixField = !$this->parent_id;
            $this->showParentField = true;
        }
    }

    /**
     * 更新帳號預覽
     */
    private function updatePreviewAccount(): void
    {
        if (!$this->username) {
            $this->previewAccount = '';
            return;
        }

        if ($this->isEdit) {
            // 編輯模式：使用現有的前置符號
            $prefix = $this->agent->full_prefix ?? '';
            $this->previewAccount = $prefix . $this->username;
        } else {
            // 建立模式
            if ($this->parent_id) {
                // 下層代理：使用上層代理的前置符號
                $parent = $this->parentOptions->find($this->parent_id);
                $prefix = $parent ? $parent->full_prefix : '';
                $this->previewAccount = $prefix . $this->username;
            } else {
                // 第一層代理：使用選擇的前置符號
                $this->previewAccount = $this->prefix . $this->username;
            }
        }
    }

    /**
     * 取得上層代理的顯示文字
     */
    public function getParentDisplayText(Agent $agent): string
    {
        $path = [];
        $current = $agent;
        
        // 建立路徑陣列
        while ($current) {
            $path[] = $current->name . " (第{$current->level}層)";
            $current = $current->parent;
        }
        
        // 反轉陣列以顯示從根到當前的路徑
        return implode(' → ', array_reverse($path));
    }

    /**
     * 檢查上層代理是否有足夠點數
     */
    public function checkParentPoints(): array
    {
        if (!$this->parent_id || $this->initial_points <= 0) {
            return ['sufficient' => true, 'available' => 0];
        }

        $parent = $this->parentOptions->find($this->parent_id);
        if (!$parent) {
            return ['sufficient' => false, 'available' => 0];
        }

        return [
            'sufficient' => $parent->remaining_points >= $this->initial_points,
            'available' => $parent->remaining_points,
        ];
    }

    /**
     * 當初始點數變更時檢查上層代理點數
     */
    public function updatedInitialPoints(): void
    {
        $this->resetValidation('initial_points');
        
        if ($this->parent_id && $this->initial_points > 0) {
            $pointsCheck = $this->checkParentPoints();
            if (!$pointsCheck['sufficient']) {
                $this->addError('initial_points', "上層代理剩餘點數不足，可用點數：{$pointsCheck['available']}");
            }
        }
    }
}