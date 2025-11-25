<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * 玩家集合 API 資源
 */
class PlayerCollection extends ResourceCollection
{
    /**
     * 將資源集合轉換為陣列
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'summary' => [
                'total_players' => $this->collection->count(),
                'active_players' => $this->collection->where('is_active', true)->count(),
                'total_points' => $this->collection->sum('points'),
                'average_points' => $this->collection->count() > 0 
                    ? round($this->collection->sum('points') / $this->collection->count(), 2) 
                    : 0,
                'agents_distribution' => $this->getAgentsDistribution(),
                'points_distribution' => $this->getPointsDistribution(),
            ],
        ];
    }

    /**
     * 取得代理分佈統計
     */
    private function getAgentsDistribution(): array
    {
        return $this->collection
            ->groupBy('agent_id')
            ->map(function ($players, $agentId) {
                $agent = $players->first()->agent;
                return [
                    'agent_id' => $agentId,
                    'agent_name' => $agent?->name,
                    'agent_level' => $agent?->level,
                    'players_count' => $players->count(),
                    'total_points' => $players->sum('points'),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * 取得點數分佈統計
     */
    private function getPointsDistribution(): array
    {
        $points = $this->collection->pluck('points')->sort();
        
        return [
            'min' => $points->min() ?? 0,
            'max' => $points->max() ?? 0,
            'median' => $this->getMedian($points),
            'ranges' => [
                '0-100' => $points->filter(fn($p) => $p >= 0 && $p <= 100)->count(),
                '101-1000' => $points->filter(fn($p) => $p > 100 && $p <= 1000)->count(),
                '1001-10000' => $points->filter(fn($p) => $p > 1000 && $p <= 10000)->count(),
                '10000+' => $points->filter(fn($p) => $p > 10000)->count(),
            ],
        ];
    }

    /**
     * 計算中位數
     */
    private function getMedian($collection): float
    {
        $count = $collection->count();
        if ($count === 0) return 0;
        
        $sorted = $collection->sort()->values();
        
        if ($count % 2 === 0) {
            return ($sorted[$count / 2 - 1] + $sorted[$count / 2]) / 2;
        } else {
            return $sorted[intval($count / 2)];
        }
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
                'pagination' => [
                    'current_page' => $this->currentPage(),
                    'per_page' => $this->perPage(),
                    'total' => $this->total(),
                    'last_page' => $this->lastPage(),
                    'from' => $this->firstItem(),
                    'to' => $this->lastItem(),
                ],
                'filters_applied' => array_filter($request->only([
                    'search', 'agent_id', 'is_active', 'min_points', 'max_points'
                ])),
                'available_actions' => [
                    'bulk_activate',
                    'bulk_deactivate', 
                    'bulk_transfer',
                    'bulk_export',
                ],
            ],
            'links' => [
                'first' => $this->url(1),
                'last' => $this->url($this->lastPage()),
                'prev' => $this->previousPageUrl(),
                'next' => $this->nextPageUrl(),
            ],
        ];
    }
}