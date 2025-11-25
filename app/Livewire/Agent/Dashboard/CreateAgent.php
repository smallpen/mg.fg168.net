<?php

namespace App\Livewire\Agent\Dashboard;

use App\Models\Agent;
use App\Services\AgentService;
use Livewire\Component;
use Illuminate\Validation\Rule;

/**
 * 代理建立下層代理元件
 * 
 * 允許代理建立其直屬下層代理
 */
class CreateAgent extends Component
{
    public Agent $parentAgent;
    
    // 表單欄位
    public string $name = '';
    public string $username = '';
    public string $email = '';
    public string $phone = '';
    public float $initial_points = 0;
    public string $notes = '';

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'username' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9_]+$/',
                Rule::unique('agents', 'username'),
            ],
            'email' => [
                'required',
                'email',
                Rule::unique('agents', 'email'),
            ],
            'phone' => 'nullable|string|max:20',
            'initial_points' => 'required|numeric|min:0|max:' . auth()->user()->agent->remaining_points,
            'notes' => 'nullable|string|max:1000',
        ];
    }

    protected $messages = [
        'username.regex' => '帳號只能包含英文字母、數字和底線',
        'username.unique' => '此帳號已被使用',
        'email.unique' => '此電子郵件已被使用',
        'initial_points.max' => '分配點數不能超過您的剩餘點數',
    ];

    public function mount(): void
    {
        $this->parentAgent = auth()->user()->agent;
    }

    /**
     * 建立下層代理
     */
    public function createAgent(): void
    {
        $this->validate();

        try {
            $agentService = app(AgentService::class);

            $data = [
                'name' => $this->name,
                'username' => $this->username,
                'email' => $this->email,
                'phone' => $this->phone,
                'parent_id' => $this->parentAgent->id,
                'initial_points' => $this->initial_points,
                'notes' => $this->notes,
                'is_active' => true,
            ];

            $agent = $agentService->createAgent($data);

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => '下層代理建立成功'
            ]);

            // 重定向到代理管理頁面
            return redirect()->route('agent.dashboard.agents');

        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '建立失敗：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 取得預覽帳號
     */
    public function getPreviewAccountProperty(): string
    {
        if (empty($this->username)) {
            return '';
        }
        
        return $this->parentAgent->full_prefix . $this->username;
    }

    public function render()
    {
        return view('livewire.agent.dashboard.create-agent');
    }
}