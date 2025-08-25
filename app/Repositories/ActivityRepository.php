<?php

namespace App\Repositories;

use App\Models\Activity;
use App\Repositories\Contracts\ActivityRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * 活動記錄儲存庫實作
 */
class ActivityRepository implements ActivityRepositoryInterface
{
    /**
     * 取得分頁的活動記錄
     */
    public function getPaginatedActivities(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = Activity::with(['user', 'subject']);

        // 應用篩選條件
        $this->applyFilters($query, $filters);

        // 排序
        $sortField = $filters['sort_field'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortField, $sortDirection);

        return $query->paginate($perPage);
    }

    /**
     * 根據 ID 取得活動記錄
     */
    public function getActivityById(int $id): ?Activity
    {
        return Activity::with(['user', 'subject'])->find($id);
    }

    /**
     * 取得相關活動記錄
     */
    public function getRelatedActivities(Activity $activity): Collection
    {
        return Activity::where(function ($query) use ($activity) {
            $query->where('user_id', $activity->user_id);
        })
        ->orWhere(function ($query) use ($activity) {
            if ($activity->subject_id && $activity->subject_type) {
                $query->where('subject_id', $activity->subject_id)
                      ->where('subject_type', $activity->subject_type);
            }
        })
        ->where('id', '!=', $activity->id)
        ->whereBetween('created_at', [
            $activity->created_at->subHours(2),
            $activity->created_at->addHours(2)
        ])
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();
    }

    /**
     * 取得活動統計資料
     */
    public function getActivityStats(string $timeRange): array
    {
        $startDate = $this->getStartDateFromRange($timeRange);
        
        $baseQuery = Activity::where('created_at', '>=', $startDate);

        return [
            'timeline' => $this->getTimelineData($baseQuery->clone(), $timeRange),
            'distribution' => $this->getDistributionData($baseQuery->clone()),
            'top_users' => $this->getTopUsersData($baseQuery->clone()),
            'security_events' => $this->getSecurityEventsData($baseQuery->clone()),
            'total_activities' => $baseQuery->count(),
            'success_rate' => $this->getSuccessRate($baseQuery->clone()),
        ];
    }

    /**
     * 取得安全事件
     */
    public function getSecurityEvents(string $timeRange): Collection
    {
        $startDate = $this->getStartDateFromRange($timeRange);

        return Activity::where('created_at', '>=', $startDate)
            ->where(function ($query) {
                $query->where('risk_level', '>=', 6)
                      ->orWhereIn('type', [
                          'login_failed', 'permission_denied', 'suspicious_activity',
                          'data_breach', 'unauthorized_access', 'privilege_escalation'
                      ]);
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * 取得最活躍使用者排行榜
     */
    public function getTopUsers(string $timeRange, int $limit = 10): Collection
    {
        $startDate = $this->getStartDateFromRange($timeRange);

        return Activity::select('user_id', DB::raw('COUNT(*) as activity_count'))
            ->with('user:id,username,name')
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderBy('activity_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 匯出活動記錄
     */
    public function exportActivities(array $filters, string $format): string
    {
        $query = Activity::with(['user', 'subject']);
        $this->applyFilters($query, $filters);
        
        $activities = $query->get();
        
        $filename = 'activities_' . date('Y-m-d_H-i-s') . '.' . $format;
        $path = storage_path('app/exports/' . $filename);
        
        // 確保目錄存在
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        
        switch ($format) {
            case 'csv':
                $this->exportToCsv($activities, $path);
                break;
            case 'json':
                $this->exportToJson($activities, $path);
                break;
            case 'pdf':
                $this->exportToPdf($activities, $path);
                break;
        }
        
        return 'exports/' . $filename;
    }

    /**
     * 清理舊的活動記錄
     */
    public function cleanupOldActivities(int $daysToKeep): int
    {
        $cutoffDate = Carbon::now()->subDays($daysToKeep);
        
        return Activity::where('created_at', '<', $cutoffDate)->delete();
    }

    /**
     * 驗證活動記錄完整性
     */
    public function verifyIntegrity(): array
    {
        $total = Activity::count();
        $verified = 0;
        $corrupted = [];
        
        Activity::chunk(1000, function ($activities) use (&$verified, &$corrupted) {
            foreach ($activities as $activity) {
                if ($activity->verifyIntegrity()) {
                    $verified++;
                } else {
                    $corrupted[] = $activity->id;
                }
            }
        });
        
        return [
            'total' => $total,
            'verified' => $verified,
            'corrupted' => count($corrupted),
            'corrupted_ids' => $corrupted,
            'integrity_rate' => $total > 0 ? ($verified / $total) * 100 : 100,
        ];
    }

    /**
     * 建立活動記錄備份
     */
    public function createBackup(string $timeRange): string
    {
        $startDate = $this->getStartDateFromRange($timeRange);
        
        $activities = Activity::where('created_at', '>=', $startDate)->get();
        
        $filename = 'backup_activities_' . date('Y-m-d_H-i-s') . '.json';
        $path = storage_path('app/backups/' . $filename);
        
        // 確保目錄存在
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        
        file_put_contents($path, $activities->toJson(JSON_PRETTY_PRINT));
        
        return 'backups/' . $filename;
    }

    /**
     * 搜尋活動記錄
     */
    public function searchActivities(string $query, array $filters = []): Collection
    {
        $searchQuery = Activity::with(['user', 'subject'])
            ->where(function ($q) use ($query) {
                $q->where('description', 'like', "%{$query}%")
                  ->orWhere('type', 'like', "%{$query}%")
                  ->orWhere('ip_address', 'like', "%{$query}%")
                  ->orWhereHas('user', function ($userQuery) use ($query) {
                      $userQuery->where('name', 'like', "%{$query}%")
                               ->orWhere('username', 'like', "%{$query}%");
                  });
            });
        
        $this->applyFilters($searchQuery, $filters);
        
        return $searchQuery->orderBy('created_at', 'desc')->get();
    }

    /**
     * 根據使用者取得活動記錄
     */
    public function getActivitiesByUser(int $userId, int $limit = 20): Collection
    {
        return Activity::with(['subject'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 根據類型取得活動記錄
     */
    public function getActivitiesByType(string $type, int $limit = 20): Collection
    {
        return Activity::with(['user', 'subject'])
            ->where('type', $type)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 根據模組取得活動記錄
     */
    public function getActivitiesByModule(string $module, int $limit = 20): Collection
    {
        return Activity::with(['user', 'subject'])
            ->where('module', $module)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 取得最近的活動記錄
     */
    public function getRecentActivities(int $limit = 20): Collection
    {
        return Activity::with(['user', 'subject'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 取得今日活動統計
     */
    public function getTodayStats(): array
    {
        $today = Carbon::today();
        
        $baseQuery = Activity::whereDate('created_at', $today);
        
        return [
            'total' => $baseQuery->count(),
            'success' => $baseQuery->clone()->where('result', 'success')->count(),
            'failed' => $baseQuery->clone()->where('result', 'failed')->count(),
            'security_events' => $baseQuery->clone()->where('risk_level', '>=', 6)->count(),
            'unique_users' => $baseQuery->clone()->distinct('user_id')->count('user_id'),
            'unique_ips' => $baseQuery->clone()->distinct('ip_address')->count('ip_address'),
        ];
    }

    /**
     * 取得活動趨勢資料
     */
    public function getActivityTrends(string $timeRange, string $groupBy = 'day'): array
    {
        $startDate = $this->getStartDateFromRange($timeRange);
        
        $format = match ($groupBy) {
            'hour' => '%Y-%m-%d %H:00:00',
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };
        
        return Activity::select(
                DB::raw("DATE_FORMAT(created_at, '{$format}') as period"),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->toArray();
    }

    /**
     * 批量建立活動記錄
     */
    public function createBatch(array $activities): bool
    {
        try {
            Activity::insert($activities);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 檢查可疑活動模式
     */
    public function detectSuspiciousPatterns(int $userId, string $timeRange): array
    {
        $startDate = $this->getStartDateFromRange($timeRange);
        
        $activities = Activity::where('user_id', $userId)
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at')
            ->get();
        
        $patterns = [];
        
        // 檢查登入失敗次數
        $failedLogins = $activities->where('type', 'login')->where('result', 'failed')->count();
        if ($failedLogins > 5) {
            $patterns[] = [
                'type' => 'excessive_failed_logins',
                'count' => $failedLogins,
                'severity' => 'high'
            ];
        }
        
        return $patterns;
    }

    /**
     * 應用篩選條件
     */
    protected function applyFilters($query, array $filters): void
    {
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('username', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['module'])) {
            $query->where('module', $filters['module']);
        }

        if (!empty($filters['result'])) {
            $query->where('result', $filters['result']);
        }

        if (!empty($filters['ip_address'])) {
            $query->where('ip_address', 'like', "%{$filters['ip_address']}%");
        }

        if (isset($filters['risk_level_min'])) {
            $query->where('risk_level', '>=', $filters['risk_level_min']);
        }

        if (isset($filters['risk_level_max'])) {
            $query->where('risk_level', '<=', $filters['risk_level_max']);
        }

        if (!empty($filters['selected_ids'])) {
            $query->whereIn('id', $filters['selected_ids']);
        }
    }

    /**
     * 根據時間範圍取得開始日期
     */
    protected function getStartDateFromRange(string $timeRange): Carbon
    {
        return match ($timeRange) {
            '1d' => Carbon::now()->subDay(),
            '7d' => Carbon::now()->subDays(7),
            '30d' => Carbon::now()->subDays(30),
            '90d' => Carbon::now()->subDays(90),
            default => Carbon::now()->subDays(7),
        };
    }

    /**
     * 取得時間軸資料
     */
    protected function getTimelineData($query, string $timeRange): array
    {
        $format = match ($timeRange) {
            '1d' => '%Y-%m-%d %H:00:00',
            '7d', '30d' => '%Y-%m-%d',
            '90d' => '%Y-%m-%d',
            default => '%Y-%m-%d',
        };

        return $query->select(
                DB::raw("DATE_FORMAT(created_at, '{$format}') as period"),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->toArray();
    }

    /**
     * 取得分佈資料
     */
    protected function getDistributionData($query): array
    {
        return $query->select('type', DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * 取得最活躍使用者資料
     */
    protected function getTopUsersData($query): array
    {
        return $query->select('user_id', DB::raw('COUNT(*) as count'))
            ->with('user:id,username,name')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * 取得安全事件資料
     */
    protected function getSecurityEventsData($query): array
    {
        return $query->where('risk_level', '>=', 6)
            ->select('type', DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * 取得成功率
     */
    protected function getSuccessRate($query): float
    {
        $total = $query->count();
        if ($total === 0) {
            return 100.0;
        }

        $success = $query->where('result', 'success')->count();
        return round(($success / $total) * 100, 2);
    }

    /**
     * 匯出為 CSV
     */
    protected function exportToCsv($activities, string $path): void
    {
        $file = fopen($path, 'w');
        
        // 寫入 BOM 以支援中文
        fwrite($file, "\xEF\xBB\xBF");
        
        // 寫入標題行
        fputcsv($file, [
            '時間', '使用者', '類型', '描述', 'IP位址', '結果', '風險等級'
        ]);
        
        foreach ($activities as $activity) {
            fputcsv($file, [
                $activity->created_at->format('Y-m-d H:i:s'),
                $activity->user ? $activity->user->name : '系統',
                $activity->type,
                $activity->description,
                $activity->ip_address,
                $activity->result,
                $activity->risk_level_text ?? 'N/A',
            ]);
        }
        
        fclose($file);
    }

    /**
     * 匯出為 JSON
     */
    protected function exportToJson($activities, string $path): void
    {
        $data = $activities->map(function ($activity) {
            return [
                'id' => $activity->id,
                'time' => $activity->created_at->format('Y-m-d H:i:s'),
                'user' => $activity->user ? $activity->user->name : '系統',
                'type' => $activity->type,
                'description' => $activity->description,
                'ip_address' => $activity->ip_address,
                'result' => $activity->result,
                'risk_level' => $activity->risk_level,
                'properties' => $activity->properties,
            ];
        });
        
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * 匯出為 PDF
     */
    protected function exportToPdf($activities, string $path): void
    {
        $html = '<h1>活動記錄報告</h1>';
        $html .= '<table border="1" style="width:100%; border-collapse: collapse;">';
        $html .= '<tr><th>時間</th><th>使用者</th><th>類型</th><th>描述</th><th>結果</th></tr>';
        
        foreach ($activities as $activity) {
            $html .= '<tr>';
            $html .= '<td>' . $activity->created_at->format('Y-m-d H:i:s') . '</td>';
            $html .= '<td>' . ($activity->user ? $activity->user->name : '系統') . '</td>';
            $html .= '<td>' . $activity->type . '</td>';
            $html .= '<td>' . $activity->description . '</td>';
            $html .= '<td>' . $activity->result . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        
        // 暫時儲存為 HTML，實際應用中應使用 PDF 生成庫
        file_put_contents($path, $html);
    }

    /**
     * 批量刪除活動記錄
     */
    public function bulkDelete(array $activityIds): int
    {
        return Activity::whereIn('id', $activityIds)->delete();
    }

    /**
     * 批量歸檔活動記錄
     */
    public function bulkArchive(array $activityIds): int
    {
        return Activity::whereIn('id', $activityIds)
            ->update(['archived_at' => now()]);
    }

    /**
     * 批量匯出活動記錄
     */
    public function bulkExport(array $activityIds): string
    {
        $activities = Activity::with(['causer', 'subject'])
            ->whereIn('id', $activityIds)
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'bulk_export_' . now()->format('Y-m-d_H-i-s') . '.json';
        $path = storage_path("app/exports/{$filename}");

        // 確保目錄存在
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $exportData = [
            'export_info' => [
                'type' => 'bulk_export',
                'total_records' => $activities->count(),
                'exported_at' => now()->toISOString(),
                'exported_by' => auth()->user()?->name ?? 'System',
                'activity_ids' => $activityIds,
            ],
            'activities' => $activities->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'type' => $activity->type,
                    'description' => $activity->description,
                    'result' => $activity->result,
                    'risk_level' => $activity->risk_level,
                    'causer' => $activity->causer ? [
                        'id' => $activity->causer->id,
                        'name' => $activity->causer->name,
                        'type' => $activity->causer_type,
                    ] : null,
                    'subject' => $activity->subject ? [
                        'id' => $activity->subject->id,
                        'type' => $activity->subject_type,
                    ] : null,
                    'properties' => $activity->properties,
                    'ip_address' => $activity->ip_address,
                    'user_agent' => $activity->user_agent,
                    'created_at' => $activity->created_at?->toISOString(),
                ];
            })->toArray(),
        ];

        file_put_contents($path, json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $filename;
    }
}