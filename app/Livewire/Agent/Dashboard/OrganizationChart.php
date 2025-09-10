<?php

namespace App\Livewire\Agent\Dashboard;

use App\Models\Agent;
use Livewire\Component;

/**
 * 代理組織架構圖元件
 * 
 * 顯示以當前代理為根節點的組織架構
 */
class OrganizationChart extends Component
{
    public Agent $agent;

    public function mount(): void
    {
        $this->agent = auth()->user()->agent;
    }

    /**
     * 取得組織架構資料
     */
    public function getOrganizationDataProperty(): array
    {
        return $this->buildOrganizationTree($this->agent);
    }

    /**
     * 建立組織樹狀結構
     */
    private function buildOrganizationTree(Agent $agent): array
    {
        $children = $agent->children()
            ->where('is_active', true)
            ->with(['children' => function ($query) {
                $query->where('is_active', true);
            }, 'players' => function ($query) {
                $query->where('is_active', true);
            }])
            ->get();

        $players = $agent->players()
            ->where('is_active', true)
            ->get();

        return [
            'id' => $agent->id,
            'name' => $agent->name,
            'account' => $agent->account,
            'level' => $agent->level,
            'total_points' => $agent->total_points,
            'allocated_points' => $agent->allocated_points,
            'remaining_points' => $agent->remaining_points,
            'children_count' => $children->count(),
            'players_count' => $players->count(),
            'children' => $children->map(function ($child) {
                return $this->buildOrganizationTree($child);
            })->toArray(),
            'players' => $players->map(function ($player) {
                return [
                    'id' => $player->id,
                    'name' => $player->name,
                    'account' => $player->account,
                    'points' => $player->points,
                    'is_active' => $player->is_active,
                ];
            })->toArray(),
        ];
    }

    /**
     * 取得統計摘要
     */
    public function getStatsSummaryProperty(): array
    {
        $allDescendants = $this->agent->getAllDescendants();
        $totalPlayers = $this->agent->players()->where('is_active', true)->count();
        
        // 計算所有下層代理的玩家數量
        foreach ($allDescendants as $descendant) {
            $totalPlayers += $descendant->players()->where('is_active', true)->count();
        }

        return [
            'total_agents' => $allDescendants->count() + 1, // 包含自己
            'direct_children' => $this->agent->children()->where('is_active', true)->count(),
            'total_players' => $totalPlayers,
            'direct_players' => $this->agent->players()->where('is_active', true)->count(),
            'max_level' => $allDescendants->max('level') ?? $this->agent->level,
        ];
    }

    public function render()
    {
        return view('livewire.agent.dashboard.organization-chart');
    }
}