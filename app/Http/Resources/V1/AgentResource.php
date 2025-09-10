<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 代理 API 資源
 */
class AgentResource extends JsonResource
{
    /**
     * 將資源轉換為陣列
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'account' => $this->account,
            'email' => $this->email,
            'phone' => $this->phone,
            'prefix' => $this->prefix,
            'level' => $this->level,
            'is_active' => $this->is_active,
            
            // 點數資訊
            'points' => [
                'total_points' => $this->total_points,
                'allocated_points' => $this->allocated_points,
                'remaining_points' => $this->remaining_points,
                'utilization_rate' => $this->total_points > 0 
                    ? round(($this->allocated_points / $this->total_points) * 100, 2) 
                    : 0,
            ],
            
            // 關聯資訊
            'parent' => $this->when($this->relationLoaded('parent') && $this->parent, [
                'id' => $this->parent?->id,
                'name' => $this->parent?->name,
                'account' => $this->parent?->account,
                'level' => $this->parent?->level,
            ]),
            
            'children_count' => $this->when($this->relationLoaded('children'), $this->children->count()),
            'players_count' => $this->when($this->relationLoaded('players'), $this->players->count()),
            
            // 詳細關聯資料（僅在需要時載入）
            'children' => AgentResource::collection($this->whenLoaded('children')),
            'players' => PlayerResource::collection($this->whenLoaded('players')),
            
            // 層級路徑
            'agent_path' => $this->when($request->input('include_path'), function () {
                return $this->agent_path->map(function ($agent) {
                    return [
                        'id' => $agent->id,
                        'name' => $agent->name,
                        'level' => $agent->level,
                    ];
                });
            }),
            
            // 統計資訊
            'statistics' => $this->when($request->input('include_stats'), [
                'descendants_count' => $this->getAllDescendants()->count(),
                'total_network_players' => $this->getTotalNetworkPlayers(),
                'network_points' => $this->getNetworkPointsDistribution(),
            ]),
            
            // 最近交易（僅在詳細檢視時顯示）
            'recent_transactions' => $this->when(
                $request->input('include_transactions'),
                function () {
                    return $this->pointTransactions()
                        ->latest()
                        ->take(5)
                        ->get()
                        ->map(function ($transaction) {
                            return [
                                'id' => $transaction->id,
                                'type' => $transaction->type,
                                'amount' => $transaction->amount,
                                'description' => $transaction->description,
                                'created_at' => $transaction->created_at->toISOString(),
                            ];
                        });
                }
            ),
            
            // 備註（僅管理員可見）
            'notes' => $this->when(
                $request->user()->can('agents.view_notes'),
                $this->notes
            ),
            
            // 時間資訊
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // API 連結
            'links' => [
                'self' => url("api/v1/agents/{$this->id}"),
                'hierarchy' => url("api/v1/agents/{$this->id}/hierarchy"),
                'stats' => url("api/v1/agents/{$this->id}/stats"),
                'points' => url("api/v1/points/agents/{$this->id}"),
            ],
        ];
    }

    /**
     * 取得網絡中的總玩家數
     */
    private function getTotalNetworkPlayers(): int
    {
        $count = $this->players->count();
        
        foreach ($this->children as $child) {
            $count += $this->getTotalNetworkPlayersRecursive($child);
        }
        
        return $count;
    }

    /**
     * 遞迴計算網絡玩家數
     */
    private function getTotalNetworkPlayersRecursive($agent): int
    {
        $count = $agent->players->count();
        
        foreach ($agent->children as $child) {
            $count += $this->getTotalNetworkPlayersRecursive($child);
        }
        
        return $count;
    }

    /**
     * 取得網絡點數分佈
     */
    private function getNetworkPointsDistribution(): array
    {
        $childrenPoints = $this->children->sum('total_points');
        $playersPoints = $this->players->sum('points');
        
        return [
            'children_points' => $childrenPoints,
            'players_points' => $playersPoints,
            'total_distributed' => $childrenPoints + $playersPoints,
        ];
    }

    /**
     * 取得額外的中繼資料
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'api_version' => 'v1',
                'timestamp' => now()->toISOString(),
                'user_permissions' => [
                    'can_view_details' => $request->user()->can('agents.view'),
                    'can_edit' => $request->user()->can('agents.edit'),
                    'can_delete' => $request->user()->can('agents.delete'),
                    'can_manage_points' => $request->user()->can('points.manage'),
                ],
            ],
        ];
    }
}