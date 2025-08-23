<?php

namespace App\Repositories;

use App\Models\Activity;
use App\Models\User;
use App\Repositories\Contracts\ActivityRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

/**
 * 活動記錄資料存取層實作
 * 
 * 處理活動記錄相關的資料庫操作，包括查詢、篩選、分頁和統計功能
 */
class ActivityRepository implements ActivityRepositoryInterface
{
    /**
     * 快取鍵前綴
     */
    protected string $cachePrefix = 'activity_repository';

    /**
     * 預設快取時間（分鐘）
     */
    protected int $defaultCacheMinutes = 5;

    /**
     * 取得分頁的活動記錄，支援搜尋和篩選
     *
     * @param array $filters 篩選條件
     * @param int $perPage 每頁筆數
     * @return LengthAwarePaginator
     */
    public function getPaginatedActivities(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = Activity::with(['user:id,username,name', 'subject'])
            ->select([
                'id', 'type', 'description', 'module', 'user_id',
                'subject_id', 'subject_type', 'ip_address', 'result',
                'risk_level', 'created_at'
            ]);

        // 應用篩選條件
        $this->applyFilters($query, $filters);

        // 排序
        $sortField = $filters['sort_field'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortField, $sortDirection);

        // 如果不是按 ID 排序，添加 ID 作為次要排序以確保一致性
        if ($sortField !== 'id') {
            $query->orderBy('id', $sortDirection);
        }

        return $query->paginate($perPage);
    }

    /**
     * 根據 ID 取得活動記錄
     *
     * @param int $id 活動記錄 ID
     * @return Activity|null
     */
    public function getActivityById(int $id): ?Activity
    {
        return Activity::with(['user:id,username,name', 'subject'])
            ->find($id);
    }

    /**
     * 取得相關活動記錄
     *
     * @param Activity $activity 參考活動記錄
     * @return Collection
     */
    public function getRelatedActivities(Activity $activity): Collection
    {
        return Activity::with(['user:id,username,name'])
            ->where('id', '!=', $activity->id)
            ->where(function ($query) use ($activity) {
                $query->where('user_id', $activity->user_id)
                      ->orWhere(function ($q) use ($activity) {
                          if ($activity->subject_id && $activity->subject_type) {
                              $q->where('subject_id', $activity->subject_id)
                                ->where('subject_type', $activity->subject_type);
                          }
                      })
                      ->orWhere('ip_address', $activity->ip_address);
            })
            ->whereBetween('created_at', [
                $activity->created_at->subHours(24),
                $activity->created_at->addHours(24)
            ])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * 取得活動統計資料
     *
     * @param string $timeRange 時間範圍 (1d, 7d, 30d, 90d)
     * @return array
     */
    public function getActivityStats(string $timeRange): array
    {
        $cacheKey = $this->getCacheKey("stats_{$timeRange}");

        return Cache::remember($cacheKey, $this->defaultCacheMinutes * 60, function () use ($timeRange) {
            $days = $this->parseDays($timeRange);
            $startDate = Carbon::now()->subDays($days);

            $baseQuery = Activity::where('created_at', '>=', $startDate);

            return [
                'total_activities' => $baseQuery->count(),
                'unique_users' => $baseQuery->distinct('user_id')->whereNotNull('user_id')->count(),
                'security_events' => $baseQuery->securityEvents()->count(),
                'high_risk_activities' => $baseQuery->highRisk()->count(),
                'success_rate' => $this->calculateSuccessRate($baseQuery),
                'activity_by_type' => $this->getActivityByType($baseQuery),
                'activity_by_module' => $this->getActivityByModule($baseQuery),
                'hourly_distribution' => $this->getHourlyDistribution($baseQuery),
                'daily_trends' => $this->getDailyTrends($startDate, $days),
            ];
        });
    }

    /**
     * 取得安全事件記錄
     *
     * @param string $timeRange 時間範圍
     * @return Collection
     */
    public function getSecurityEvents(string $timeRange): Collection
    {
        $days = $this->parseDays($timeRange);
        $startDate = Carbon::now()->subDays($days);

        return Activity::with(['user:id,username,name'])
            ->securityEvents()
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * 取得最活躍使用者排行榜
     *
     * @param string $timeRange 時間範圍
     * @param int $limit 限制數量
     * @return Collection
     */
    public function getTopUsers(string $timeRange, int $limit = 10): Collection
    {
        $days = $this->parseDays($timeRange);
        $startDate = Carbon::now()->subDays($days);

        return Activity::select('user_id', DB::raw('COUNT(*) as activity_count'))
            ->with(['user:id,username,name'])
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderBy('activity_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 匯出活動記錄
     *
     * @param array $filters 篩選條件
     * @param string $format 匯出格式 (csv, json, pdf)
     * @return string 匯出檔案路徑
     */
    public function exportActivities(array $filters, string $format): string
    {
        $query = Activity::with(['user:id,username,name']);
        $this->applyFilters($query, $filters);
        
        $activities = $query->orderBy('created_at', 'desc')->get();
        
        $filename = 'activities_export_' . now()->format('Y-m-d_H-i-s') . '.' . $format;
        $path = 'exports/' . $filename;

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
            default:
                throw new \InvalidArgumentException("不支援的匯出格式: {$format}");
        }

        return $path;
    }

    /**
     * 清理舊的活動記錄
     *
     * @param int $daysToKeep 保留天數
     * @return int 清理的記錄數量
     */
    public function cleanupOldActivities(int $daysToKeep): int
    {
        $cutoffDate = Carbon::now()->subDays($daysToKeep);
        
        return Activity::where('created_at', '<', $cutoffDate)->delete();
    }

    /**
     * 驗證活動記錄完整性
     *
     * @return array 驗證結果
     */
    public function verifyIntegrity(): array
    {
        $totalRecords = Activity::count();
        $recordsWithSignature = Activity::whereNotNull('signature')->count();
        $validSignatures = 0;
        $invalidSignatures = 0;

        Activity::whereNotNull('signature')->chunk(100, function ($activities) use (&$validSignatures, &$invalidSignatures) {
            foreach ($activities as $activity) {
                if ($activity->verifyIntegrity()) {
                    $validSignatures++;
                } else {
                    $invalidSignatures++;
                }
            }
        });

        return [
            'total_records' => $totalRecords,
            'records_with_signature' => $recordsWithSignature,
            'valid_signatures' => $validSignatures,
            'invalid_signatures' => $invalidSignatures,
            'integrity_rate' => $recordsWithSignature > 0 ? 
                round(($validSignatures / $recordsWithSignature) * 100, 2) : 0,
            'verified_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * 建立活動記錄備份
     *
     * @param string $timeRange 時間範圍
     * @return string 備份檔案路徑
     */
    public function createBackup(string $timeRange): string
    {
        $days = $this->parseDays($timeRange);
        $startDate = Carbon::now()->subDays($days);
        
        $activities = Activity::where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'activity_backup_' . now()->format('Y-m-d_H-i-s') . '.json';
        $path = 'backups/' . $filename;

        $backupData = [
            'backup_info' => [
                'created_at' => now()->toDateTimeString(),
                'time_range' => $timeRange,
                'start_date' => $startDate->toDateTimeString(),
                'total_records' => $activities->count(),
            ],
            'activities' => $activities->toArray(),
        ];

        Storage::put($path, json_encode($backupData, JSON_PRETTY_PRINT));

        return $path;
    }

    /**
     * 搜尋活動記錄
     *
     * @param string $query 搜尋關鍵字
     * @param array $filters 額外篩選條件
     * @return Collection
     */
    public function searchActivities(string $query, array $filters = []): Collection
    {
        $searchQuery = Activity::with(['user:id,username,name'])
            ->where(function ($q) use ($query) {
                $q->where('description', 'like', "%{$query}%")
                  ->orWhere('type', 'like', "%{$query}%")
                  ->orWhere('ip_address', 'like', "%{$query}%")
                  ->orWhereHas('user', function ($userQuery) use ($query) {
                      $userQuery->where('username', 'like', "%{$query}%")
                               ->orWhere('name', 'like', "%{$query}%");
                  });
            });

        $this->applyFilters($searchQuery, $filters);

        return $searchQuery->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();
    } 
   /**
     * 根據使用者取得活動記錄
     *
     * @param int $userId 使用者 ID
     * @param int $limit 限制數量
     * @return Collection
     */
    public function getActivitiesByUser(int $userId, int $limit = 20): Collection
    {
        return Activity::with(['subject'])
            ->byUser($userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 根據類型取得活動記錄
     *
     * @param string $type 活動類型
     * @param int $limit 限制數量
     * @return Collection
     */
    public function getActivitiesByType(string $type, int $limit = 20): Collection
    {
        return Activity::with(['user:id,username,name', 'subject'])
            ->byType($type)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 根據模組取得活動記錄
     *
     * @param string $module 模組名稱
     * @param int $limit 限制數量
     * @return Collection
     */
    public function getActivitiesByModule(string $module, int $limit = 20): Collection
    {
        return Activity::with(['user:id,username,name', 'subject'])
            ->byModule($module)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 取得最近的活動記錄
     *
     * @param int $limit 限制數量
     * @return Collection
     */
    public function getRecentActivities(int $limit = 20): Collection
    {
        $cacheKey = $this->getCacheKey("recent_{$limit}");

        return Cache::remember($cacheKey, $this->defaultCacheMinutes * 60, function () use ($limit) {
            return Activity::with(['user:id,username,name'])
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * 取得今日活動統計
     *
     * @return array
     */
    public function getTodayStats(): array
    {
        $cacheKey = $this->getCacheKey('today_stats');

        return Cache::remember($cacheKey, 30 * 60, function () {
            $today = Carbon::today();
            $baseQuery = Activity::whereDate('created_at', $today);

            return [
                'total_activities' => $baseQuery->count(),
                'unique_users' => $baseQuery->distinct('user_id')->whereNotNull('user_id')->count(),
                'security_events' => $baseQuery->securityEvents()->count(),
                'failed_activities' => $baseQuery->byResult('failed')->count(),
                'hourly_breakdown' => $this->getTodayHourlyBreakdown(),
            ];
        });
    }

    /**
     * 取得活動趨勢資料
     *
     * @param string $timeRange 時間範圍
     * @param string $groupBy 分組方式 (hour, day, week, month)
     * @return array
     */
    public function getActivityTrends(string $timeRange, string $groupBy = 'day'): array
    {
        $days = $this->parseDays($timeRange);
        $startDate = Carbon::now()->subDays($days);

        $dateFormat = match ($groupBy) {
            'hour' => '%Y-%m-%d %H:00:00',
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        return Activity::selectRaw("DATE_FORMAT(created_at, '{$dateFormat}') as period, COUNT(*) as count")
            ->where('created_at', '>=', $startDate)
            ->groupBy('period')
            ->orderBy('period')
            ->pluck('count', 'period')
            ->toArray();
    }

    /**
     * 批量建立活動記錄
     *
     * @param array $activities 活動記錄陣列
     * @return bool
     */
    public function createBatch(array $activities): bool
    {
        try {
            DB::beginTransaction();

            foreach (array_chunk($activities, 100) as $chunk) {
                Activity::insert($chunk);
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 檢查可疑活動模式
     *
     * @param int $userId 使用者 ID
     * @param string $timeRange 時間範圍
     * @return array
     */
    public function detectSuspiciousPatterns(int $userId, string $timeRange): array
    {
        $days = $this->parseDays($timeRange);
        $startDate = Carbon::now()->subDays($days);

        $activities = Activity::byUser($userId)
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->get();

        $patterns = [];

        // 檢查登入失敗次數
        $failedLogins = $activities->where('type', 'login_failed')->count();
        if ($failedLogins > 5) {
            $patterns[] = [
                'type' => 'excessive_failed_logins',
                'description' => "在 {$days} 天內有 {$failedLogins} 次登入失敗",
                'severity' => 'high',
                'count' => $failedLogins,
            ];
        }

        // 檢查異常時間活動
        $nightActivities = $activities->filter(function ($activity) {
            $hour = $activity->created_at->hour;
            return $hour < 6 || $hour > 22;
        })->count();

        if ($nightActivities > 10) {
            $patterns[] = [
                'type' => 'unusual_time_activity',
                'description' => "在非正常時間（22:00-06:00）有 {$nightActivities} 次活動",
                'severity' => 'medium',
                'count' => $nightActivities,
            ];
        }

        // 檢查 IP 位址變化
        $uniqueIps = $activities->pluck('ip_address')->unique()->count();
        if ($uniqueIps > 5) {
            $patterns[] = [
                'type' => 'multiple_ip_addresses',
                'description' => "使用了 {$uniqueIps} 個不同的 IP 位址",
                'severity' => 'medium',
                'count' => $uniqueIps,
            ];
        }

        return $patterns;
    }

    /**
     * 應用篩選條件到查詢
     *
     * @param Builder $query
     * @param array $filters
     * @return void
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        // 搜尋關鍵字
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('username', 'like', "%{$search}%")
                               ->orWhere('name', 'like', "%{$search}%");
                  });
            });
        }

        // 日期範圍篩選
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }

        // 使用者篩選
        if (!empty($filters['user_id'])) {
            $query->byUser($filters['user_id']);
        }

        // 類型篩選
        if (!empty($filters['type'])) {
            $query->byType($filters['type']);
        }

        // 模組篩選
        if (!empty($filters['module'])) {
            $query->byModule($filters['module']);
        }

        // 結果篩選
        if (!empty($filters['result'])) {
            $query->byResult($filters['result']);
        }

        // IP 位址篩選
        if (!empty($filters['ip_address'])) {
            $query->byIp($filters['ip_address']);
        }

        // 風險等級篩選
        if (isset($filters['risk_level'])) {
            if ($filters['risk_level'] === 'high') {
                $query->highRisk();
            } elseif (is_numeric($filters['risk_level'])) {
                $query->where('risk_level', $filters['risk_level']);
            }
        }

        // 風險等級範圍篩選
        if (isset($filters['risk_level_min'])) {
            $query->where('risk_level', '>=', $filters['risk_level_min']);
        }

        if (isset($filters['risk_level_max'])) {
            $query->where('risk_level', '<=', $filters['risk_level_max']);
        }

        // 選中的 ID 篩選（用於批量操作）
        if (!empty($filters['selected_ids'])) {
            $query->whereIn('id', $filters['selected_ids']);
        }

        // 安全事件篩選
        if (!empty($filters['security_events_only'])) {
            $query->securityEvents();
        }
    }

    /**
     * 解析時間範圍為天數
     *
     * @param string $timeRange
     * @return int
     */
    protected function parseDays(string $timeRange): int
    {
        return match ($timeRange) {
            '1d' => 1,
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            default => 7,
        };
    }

    /**
     * 計算成功率
     *
     * @param Builder $query
     * @return float
     */
    protected function calculateSuccessRate(Builder $query): float
    {
        $total = $query->count();
        if ($total === 0) {
            return 0;
        }

        $successful = $query->byResult('success')->count();
        return round(($successful / $total) * 100, 2);
    }

    /**
     * 取得按類型分組的活動統計
     *
     * @param Builder $query
     * @return array
     */
    protected function getActivityByType(Builder $query): array
    {
        return $query->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->orderBy('count', 'desc')
            ->pluck('count', 'type')
            ->toArray();
    }

    /**
     * 取得按模組分組的活動統計
     *
     * @param Builder $query
     * @return array
     */
    protected function getActivityByModule(Builder $query): array
    {
        return $query->selectRaw('module, COUNT(*) as count')
            ->whereNotNull('module')
            ->groupBy('module')
            ->orderBy('count', 'desc')
            ->pluck('count', 'module')
            ->toArray();
    }

    /**
     * 取得每小時分佈統計
     *
     * @param Builder $query
     * @return array
     */
    protected function getHourlyDistribution(Builder $query): array
    {
        $hourlyData = $query->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        // 填充 0-23 小時的資料
        $result = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $result[$hour] = $hourlyData[$hour] ?? 0;
        }

        return $result;
    }

    /**
     * 取得每日趨勢統計
     *
     * @param Carbon $startDate
     * @param int $days
     * @return array
     */
    protected function getDailyTrends(Carbon $startDate, int $days): array
    {
        $trends = Activity::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // 填充所有日期的資料
        $result = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i)->format('Y-m-d');
            $result[$date] = $trends[$date] ?? 0;
        }

        return $result;
    }

    /**
     * 取得今日每小時統計
     *
     * @return array
     */
    protected function getTodayHourlyBreakdown(): array
    {
        $hourlyData = Activity::selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->whereDate('created_at', Carbon::today())
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        // 填充 0-23 小時的資料
        $result = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $result[$hour] = $hourlyData[$hour] ?? 0;
        }

        return $result;
    }

    /**
     * 匯出為 CSV 格式
     *
     * @param Collection $activities
     * @param string $path
     * @return void
     */
    protected function exportToCsv(Collection $activities, string $path): void
    {
        $csvData = [];
        $csvData[] = ['ID', '類型', '描述', '模組', '使用者', 'IP位址', '結果', '風險等級', '建立時間'];

        foreach ($activities as $activity) {
            $csvData[] = [
                $activity->id,
                $activity->type,
                $activity->description,
                $activity->module,
                $activity->user?->name ?? '系統',
                $activity->ip_address,
                $activity->result,
                $activity->risk_level_text,
                $activity->created_at->format('Y-m-d H:i:s'),
            ];
        }

        $csv = '';
        foreach ($csvData as $row) {
            $csv .= implode(',', array_map(function ($field) {
                return '"' . str_replace('"', '""', $field) . '"';
            }, $row)) . "\n";
        }

        Storage::put($path, $csv);
    }

    /**
     * 匯出為 JSON 格式
     *
     * @param Collection $activities
     * @param string $path
     * @return void
     */
    protected function exportToJson(Collection $activities, string $path): void
    {
        $jsonData = [
            'export_info' => [
                'exported_at' => now()->toDateTimeString(),
                'total_records' => $activities->count(),
            ],
            'activities' => $activities->toArray(),
        ];

        Storage::put($path, json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * 匯出為 PDF 格式
     *
     * @param Collection $activities
     * @param string $path
     * @return void
     */
    protected function exportToPdf(Collection $activities, string $path): void
    {
        // 這裡可以使用 PDF 生成套件如 DomPDF 或 TCPDF
        // 暫時使用簡單的 HTML 格式儲存
        $html = '<h1>活動記錄匯出</h1>';
        $html .= '<p>匯出時間: ' . now()->format('Y-m-d H:i:s') . '</p>';
        $html .= '<p>總記錄數: ' . $activities->count() . '</p>';
        $html .= '<table border="1" style="width:100%; border-collapse: collapse;">';
        $html .= '<tr><th>ID</th><th>類型</th><th>描述</th><th>使用者</th><th>時間</th></tr>';

        foreach ($activities as $activity) {
            $html .= '<tr>';
            $html .= '<td>' . $activity->id . '</td>';
            $html .= '<td>' . $activity->type . '</td>';
            $html .= '<td>' . $activity->description . '</td>';
            $html .= '<td>' . ($activity->user?->name ?? '系統') . '</td>';
            $html .= '<td>' . $activity->created_at->format('Y-m-d H:i:s') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';

        Storage::put($path, $html);
    }

    /**
     * 取得快取鍵
     *
     * @param string $key
     * @return string
     */
    protected function getCacheKey(string $key): string
    {
        return "{$this->cachePrefix}.{$key}";
    }
}