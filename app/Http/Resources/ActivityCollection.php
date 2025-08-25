<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * 活動記錄集合 API 資源
 */
class ActivityCollection extends ResourceCollection
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
            'links' => [
                'first' => $this->url(1),
                'last' => $this->url($this->lastPage()),
                'prev' => $this->previousPageUrl(),
                'next' => $this->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $this->currentPage(),
                'from' => $this->firstItem(),
                'last_page' => $this->lastPage(),
                'per_page' => $this->perPage(),
                'to' => $this->lastItem(),
                'total' => $this->total(),
                'path' => $this->path(),
                
                // 統計資訊
                'statistics' => $this->getStatistics(),
                
                // API 資訊
                'api_version' => 'v1',
                'generated_at' => now()->toISOString(),
                
                // 使用者權限
                'user_permissions' => [
                    'can_view_details' => $request->user()->can('system.logs'),
                    'can_export' => $request->user()->can('activity_logs.export'),
                    'can_delete' => $request->user()->can('activity_logs.delete'),
                ],
                
                // 篩選資訊
                'filters_applied' => $this->getAppliedFilters($request),
            ],
        ];
    }

    /**
     * 取得統計資訊
     */
    private function getStatistics(): array
    {
        $items = $this->collection;
        
        if ($items->isEmpty()) {
            return [
                'total_items' => 0,
                'risk_distribution' => [],
                'type_distribution' => [],
                'result_distribution' => [],
            ];
        }

        // 風險等級分佈
        $riskDistribution = $items->groupBy('risk_level')->map(function ($group) {
            return $group->count();
        })->toArray();

        // 活動類型分佈
        $typeDistribution = $items->groupBy('type')->map(function ($group) {
            return $group->count();
        })->take(10)->toArray(); // 只取前 10 種類型

        // 結果分佈
        $resultDistribution = $items->groupBy('result')->map(function ($group) {
            return $group->count();
        })->toArray();

        return [
            'total_items' => $items->count(),
            'risk_distribution' => $riskDistribution,
            'type_distribution' => $typeDistribution,
            'result_distribution' => $resultDistribution,
            'security_events_count' => $items->where('is_security_event', true)->count(),
            'date_range' => [
                'earliest' => $items->min('created_at')?->toISOString(),
                'latest' => $items->max('created_at')?->toISOString(),
            ],
        ];
    }

    /**
     * 取得已套用的篩選條件
     */
    private function getAppliedFilters(Request $request): array
    {
        $filters = [];
        
        $filterParams = [
            'search', 'date_from', 'date_to', 'user_id', 'type', 
            'subject_type', 'result', 'risk_level', 'ip_address',
            'causer_type', 'has_properties', 'is_security_event', 'time_range'
        ];

        foreach ($filterParams as $param) {
            if ($request->filled($param)) {
                $filters[$param] = $request->input($param);
            }
        }

        return $filters;
    }

    /**
     * 取得額外的回應資料
     */
    public function with(Request $request): array
    {
        return [
            'included' => [
                'available_filters' => [
                    'types' => $this->getAvailableTypes(),
                    'subject_types' => $this->getAvailableSubjectTypes(),
                    'results' => ['success', 'failed', 'warning'],
                    'risk_levels' => range(0, 10),
                    'time_ranges' => [
                        '1h' => '過去 1 小時',
                        '6h' => '過去 6 小時',
                        '12h' => '過去 12 小時',
                        '1d' => '過去 1 天',
                        '3d' => '過去 3 天',
                        '7d' => '過去 7 天',
                        '30d' => '過去 30 天',
                        '90d' => '過去 90 天',
                    ],
                ],
                'sort_options' => [
                    'created_at' => '建立時間',
                    'type' => '活動類型',
                    'causer_id' => '操作者',
                    'subject_type' => '操作對象類型',
                    'risk_level' => '風險等級',
                ],
            ],
            'api_info' => [
                'rate_limits' => [
                    'current_endpoint' => '100 requests per minute',
                    'search_endpoint' => '50 requests per minute',
                    'export_endpoint' => '5 requests per minute',
                ],
                'documentation' => url('/api/v1/docs'),
                'support' => [
                    'email' => config('app.support_email'),
                    'docs' => url('/api/v1/docs'),
                ],
            ],
        ];
    }

    /**
     * 取得可用的活動類型
     */
    private function getAvailableTypes(): array
    {
        // 這裡可以從資料庫或快取中取得
        return [
            'user_login', 'user_logout', 'user_created', 'user_updated', 'user_deleted',
            'role_created', 'role_updated', 'role_deleted', 'role_assigned', 'role_revoked',
            'permission_created', 'permission_updated', 'permission_deleted',
            'settings_updated', 'system_maintenance', 'security_alert',
            'api_access', 'export_data', 'import_data'
        ];
    }

    /**
     * 取得可用的操作對象類型
     */
    private function getAvailableSubjectTypes(): array
    {
        return [
            'App\\Models\\User',
            'App\\Models\\Role',
            'App\\Models\\Permission',
            'App\\Models\\Setting',
            'App\\Models\\Activity',
        ];
    }
}