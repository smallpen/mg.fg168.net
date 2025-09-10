<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * 代理集合 API 資源
 */
class AgentCollection extends ResourceCollection
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
                'total_agents' => $this->collection->count(),
                'active_agents' => $this->collection->where('is_active', true)->count(),
                'total_points' => $this->collection->sum('total_points'),
                'total_allocated_points' => $this->collection->sum('allocated_points'),
                'total_remaining_points' => $this->collection->sum('remaining_points'),
                'levels_distribution' => $this->getLevelsDistribution(),
                'prefixes_used' => $this->getPrefixesUsed(),
            ],
        ];
    }

    /**
     * 取得層級分佈統計
     */
    private function getLevelsDistribution(): array
    {
        return $this->collection
            ->groupBy('level')
            ->map(function ($agents, $level) {
                return [
                    'level' => $level,
                    'count' => $agents->count(),
                    'total_points' => $agents->sum('total_points'),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * 取得已使用的前置符號
     */
    private function getPrefixesUsed(): array
    {
        return $this->collection
            ->where('level', 1)
            ->whereNotNull('prefix')
            ->pluck('prefix')
            ->unique()
            ->sort()
            ->values()
            ->toArray();
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
                    'search', 'level', 'prefix', 'is_active', 'parent_id'
                ])),
                'available_filters' => [
                    'levels' => range(1, 10),
                    'prefixes' => range('a', 'z'),
                    'status' => ['active', 'inactive'],
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