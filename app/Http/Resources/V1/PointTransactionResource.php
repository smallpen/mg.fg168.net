<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 點數交易 API 資源
 */
class PointTransactionResource extends JsonResource
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
            'type' => $this->type,
            'type_name' => $this->type_name,
            'amount' => $this->amount,
            'balance_before' => $this->balance_before,
            'balance_after' => $this->balance_after,
            'description' => $this->description,
            'reference_id' => $this->reference_id,
            
            // 交易方向指示
            'is_positive' => $this->isPositiveTransaction(),
            'is_negative' => $this->isNegativeTransaction(),
            
            // 關聯的代理資訊
            'agent' => $this->when($this->relationLoaded('agent') && $this->agent, [
                'id' => $this->agent?->id,
                'name' => $this->agent?->name,
                'account' => $this->agent?->account,
                'level' => $this->agent?->level,
            ]),
            
            // 關聯的玩家資訊
            'player' => $this->when($this->relationLoaded('player') && $this->player, [
                'id' => $this->player?->id,
                'name' => $this->player?->name,
                'account' => $this->player?->account,
            ]),
            
            // 操作者資訊
            'creator' => $this->when($this->relationLoaded('creator') && $this->creator, [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
                'username' => $this->creator?->username,
            ]),
            
            // 交易目標資訊
            'target' => [
                'type' => $this->agent_id ? 'agent' : 'player',
                'id' => $this->agent_id ?: $this->player_id,
                'name' => $this->target_name,
            ],
            
            // 時間資訊
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // API 連結
            'links' => [
                'self' => url("api/v1/points/{$this->id}"),
                'agent' => $this->agent_id ? url("api/v1/agents/{$this->agent_id}") : null,
                'player' => $this->player_id ? url("api/v1/players/{$this->player_id}") : null,
            ],
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
                'transaction_types' => \App\Models\PointTransaction::getTransactionTypes(),
                'user_permissions' => [
                    'can_view_details' => $request->user()->can('points.view'),
                    'can_manage_points' => $request->user()->can('points.manage'),
                ],
            ],
        ];
    }
}