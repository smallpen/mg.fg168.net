<?php

namespace App\Livewire\Agent\Dashboard;

use App\Models\Agent;
use Livewire\Component;

/**
 * 代理儀表板主頁元件
 * 
 * 顯示代理的基本統計資訊和快速操作
 */
class AgentDashboard extends Component
{
    public Agent $agent;
    
    public function mount(): void
    {
        $this->agent = auth()->user()->agent->load([
            'children' => function ($query) {
                $query->where('is_active', true);
            },
            'players' => function ($query) {
                $query->where('is_active', true);
            }
        ]);
    }

    /**
     * 取得統計資料
     */
    public function getStatsProperty(): array
    {
        return [
            'total_points' => $this->agent->total_points,
            'allocated_points' => $this->agent->allocated_points,
            'remaining_points' => $this->agent->remaining_points,
            'children_count' => $this->agent->children->count(),
            'players_count' => $this->agent->players->count(),
            'active_children' => $this->agent->children->where('is_active', true)->count(),
            'active_players' => $this->agent->players->where('is_active', true)->count(),
        ];
    }

    /**
     * 取得最近的下層代理
     */
    public function getRecentChildrenProperty()
    {
        return $this->agent->children()
            ->where('is_active', true)
            ->latest()
            ->limit(5)
            ->get();
    }

    /**
     * 取得最近的玩家
     */
    public function getRecentPlayersProperty()
    {
        return $this->agent->players()
            ->where('is_active', true)
            ->latest()
            ->limit(5)
            ->get();
    }

    public function render()
    {
        return view('livewire.agent.dashboard.agent-dashboard');
    }
}