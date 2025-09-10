<?php

namespace App\Livewire\Agent\Dashboard;

use App\Models\Agent;
use App\Models\Player;
use App\Services\PlayerService;
use Livewire\Component;
use Illuminate\Validation\Rule;

/**
 * 代理建立玩家元件
 * 
 * 允許代理建立隸屬於自己的玩家
 */
class CreatePlayer extends Component
{
    public Agent $agent;
    
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
                Rule::unique('players', 'username'),
            ],
            'email' => [
                'required',
                'email',
                Rule::unique('players', 'email'),
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
        $this->agent = auth()->user()->agent;
    }

    /**
     * 建立玩家
     */
    public function createPlayer(): void
    {
        $this->validate();

        try {
            $playerService = app(PlayerService::class);

            $data = [
                'name' => $this->name,
                'username' => $this->username,
                'email' => $this->email,
                'phone' => $this->phone,
                'agent_id' => $this->agent->id,
                'initial_points' => $this->initial_points,
                'notes' => $this->notes,
                'is_active' => true,
            ];

            $player = $playerService->createPlayer($data);

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => '玩家建立成功'
            ]);

            // 重定向到玩家管理頁面
            return redirect()->route('agent.dashboard.players');

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
        
        return $this->agent->full_prefix . $this->username;
    }

    public function render()
    {
        return view('livewire.agent.dashboard.create-player');
    }
}