<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * 點數交易集合 API 資源
 */
class PointTransactionCollection extends ResourceCollection
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
                'total_transactions' => $this->collection->count(),
                'total_amount' => $this->collection->sum('amount'),
                'positive_transactions' => $this->collection->where('amount', '>', 0)->count(),
                'negative_transactions' => $this->collection->where('amount', '<', 0)->count(),
                'total_positive_amount' => $this->collection->where('amount', '>', 0)->sum('amount'),
                'total_negative_amount' => abs($this->collection->where('amount', '<', 0)->sum('amount')),
                'net_flow' => $this->collection->sum('amount'),
                'types_distribution' => $this->getTypesDistribution(),
                'date_range' => $this->getDateRange(),
            ],
        ];
    }

    /**
     * 取得交易類型分佈統計
     */
    private function getTypesDistribution(): array
    {
        return $this->collection
            ->groupBy('type')
            ->map(function ($transactions, $type) {
                return [
                    'type' => $type,
                    'type_name' => \App\Models\PointTransaction::getTransactionTypes()[$type] ?? $type,
                    'count' => $transactions->count(),
                    'total_amount' => $transactions->sum('amount'),
                    'average_amount' => $transactions->count() > 0 
                        ? round($transactions->sum('amount') / $transactions->count(), 2) 
                        : 0,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * 取得日期範圍
     */
    private function getDateRange(): array
    {
        if ($this->collection->isEmpty()) {
            return [
                'earliest' => null,
                'latest' => null,
                'span_days' => 0,
            ];
        }

        $dates = $this->collection->pluck('created_at');
        $earliest = $dates->min();
        $latest = $dates->max();

        return [
            'earliest' => $earliest?->toISOString(),
            'latest' => $latest?->toISOString(),
            'span_days' => $earliest && $latest ? $earliest->diffInDays($latest) : 0,
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
                'pagination' => [
                    'current_page' => $this->currentPage(),
                    'per_page' => $this->perPage(),
                    'total' => $this->total(),
                    'last_page' => $this->lastPage(),
                    'from' => $this->firstItem(),
                    'to' => $this->lastItem(),
                ],
                'filters_applied' => array_filter($request->only([
                    'agent_id', 'player_id', 'type', 'date_from', 'date_to'
                ])),
                'available_types' => \App\Models\PointTransaction::getTransactionTypes(),
                'export_formats' => ['csv', 'json', 'pdf'],
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