<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 玩家 API 資源
 */
class PlayerResource extends JsonResource
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
            'points' => $this->points,
            'is_active' => $this->is_active,
            
            // 隸屬代理資訊
            'agent' => $this->when($this->relationLoaded('agent') && $this->agent, [
                'id' => $this->agent?->id,
                'name' => $this->agent?->name,
                'account' => $this->agent?->account,
                'level' => $this->agent?->level,
                'prefix' => $this->agent?->prefix,
            ]),
            
            // 代理路徑
            'agent_path' => $this->when($request->input('include_path'), function () {
                return $this->agent_path->map(function ($agent) {
                    return [
                        'id' => $agent->id,
                        'name' => $agent->name,
                        'level' => $agent->level,
                    ];
                });
            }),
            
            'agent_path_string' => $this->when(
                $request->input('include_path_string'),
                $this->agent_path_string
            ),
            
            // 點數統計
            'points_statistics' => $this->when($request->input('include_stats'), function () {
                return [
                    'total_allocated' => $this->pointTransactions()
                        ->where('amount', '>', 0)
                        ->sum('amount'),
                    'total_consumed' => abs($this->pointTransactions()
                        ->where('amount', '<', 0)
                        ->sum('amount')),
                    'transaction_count' => $this->pointTransactions->count(),
                    'last_transaction_at' => $this->pointTransactions()
                        ->latest()
                        ->first()?->created_at?->toISOString(),
                ];
            }),
            
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
                                'type_name' => $transaction->type_name,
                                'amount' => $transaction->amount,
                                'description' => $transaction->description,
                                'created_at' => $transaction->created_at->toISOString(),
                            ];
                        });
                }
            ),
            
            // 備註（僅管理員可見）
            'notes' => $this->when(
                $request->user()->can('players.view_notes'),
                $this->notes
            ),
            
            // 時間資訊
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // API 連結
            'links' => [
                'self' => url("api/v1/players/{$this->id}"),
                'stats' => url("api/v1/players/{$this->id}/stats"),
                'points' => url("api/v1/points/players/{$this->id}"),
                'agent' => $this->agent ? url("api/v1/agents/{$this->agent->id}") : null,
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
                'user_permissions' => [
                    'can_view_details' => $request->user()->can('players.view'),
                    'can_edit' => $request->user()->can('players.edit'),
                    'can_delete' => $request->user()->can('players.delete'),
                    'can_manage_points' => $request->user()->can('points.manage'),
                ],
            ],
        ];
    }
}