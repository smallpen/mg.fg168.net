<?php

namespace App\Services;

use App\Contracts\AuditServiceInterface;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

/**
 * 通用審計服務
 * 
 * 提供通用的審計日誌功能，可以被其他專門的審計服務繼承或使用
 */
class AuditService implements AuditServiceInterface
{
    /**
     * 審計日誌資料表名稱
     */
    protected string $tableName = 'audit_logs';

    /**
     * 記錄操作日誌
     * 
     * @param string $action 操作類型
     * @param mixed $subject 操作對象
     * @param array $data 額外資料
     * @param mixed $user 操作使用者
     * @return void
     */
    public function log(string $action, $subject, array $data = [], $user = null): void
    {
        $user = $user ?? Auth::user();
        
        $logData = [
            'action' => $action,
            'subject_type' => is_object($subject) ? get_class($subject) : null,
            'subject_id' => is_object($subject) && method_exists($subject, 'getKey') ? $subject->getKey() : null,
            'user_id' => $user?->id,
            'username' => $user?->username,
            'user_name' => $user?->name,
            'data' => $data,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->url(),
            'method' => request()->method(),
            'timestamp' => now()->toISOString(),
        ];

        // 記錄到應用程式日誌
        Log::info("審計日誌: {$action}", $logData);

        // 儲存到資料庫
        $this->storeAuditLog($logData);
    }

    /**
     * 記錄權限變更（專門用於權限操作）
     * 
     * @param string $action 操作類型
     * @param Permission $permission 權限物件
     * @param array $changes 變更內容
     * @param User|null $user 操作使用者
     * @return void
     */
    public function logPermissionChange(string $action, Permission $permission, array $changes = [], ?User $user = null): void
    {
        // 委託給專門的權限審計服務
        $permissionAuditService = app(PermissionAuditService::class);
        $permissionAuditService->logPermissionChange($action, $permission, $changes, $user);
    }

    /**
     * 搜尋審計日誌
     * 
     * @param array $filters 篩選條件
     * @return LengthAwarePaginator
     */
    public function search(array $filters): LengthAwarePaginator
    {
        $query = DB::table($this->tableName);

        // 時間範圍篩選
        if (!empty($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }
        
        if (!empty($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        // 操作者篩選
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        
        if (!empty($filters['username'])) {
            $query->where('username', 'like', "%{$filters['username']}%");
        }

        // 操作類型篩選
        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        // 對象類型篩選
        if (!empty($filters['subject_type'])) {
            $query->where('subject_type', $filters['subject_type']);
        }

        // 對象 ID 篩選
        if (!empty($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }

        // IP 位址篩選
        if (!empty($filters['ip_address'])) {
            $query->where('ip_address', $filters['ip_address']);
        }

        $perPage = $filters['per_page'] ?? 25;
        $page = $filters['page'] ?? 1;

        $total = $query->count();
        $results = $query->orderBy('created_at', 'desc')
                        ->offset(($page - 1) * $perPage)
                        ->limit($perPage)
                        ->get()
                        ->map(function ($log) {
                            $log->data = json_decode($log->data, true);
                            return $log;
                        });

        return new LengthAwarePaginator(
            $results,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'pageName' => 'page']
        );
    }

    /**
     * 取得統計資料
     * 
     * @param int $days 統計天數
     * @return array
     */
    public function getStats(int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days);

        // 按操作類型統計
        $actionStats = DB::table($this->tableName)
                         ->where('created_at', '>=', $startDate)
                         ->selectRaw('
                             action,
                             COUNT(*) as count,
                             COUNT(DISTINCT subject_id) as unique_subjects,
                             COUNT(DISTINCT user_id) as unique_users
                         ')
                         ->groupBy('action')
                         ->get()
                         ->keyBy('action');

        // 總體統計
        $overallStats = DB::table($this->tableName)
                          ->where('created_at', '>=', $startDate)
                          ->selectRaw('
                              COUNT(*) as total_actions,
                              COUNT(DISTINCT subject_id) as unique_subjects,
                              COUNT(DISTINCT user_id) as unique_users
                          ')
                          ->first();

        // 每日統計
        $dailyStats = DB::table($this->tableName)
                        ->where('created_at', '>=', $startDate)
                        ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                        ->groupBy('date')
                        ->orderBy('date')
                        ->get()
                        ->keyBy('date');

        // 填補缺失的日期
        $dailyActivity = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $dailyActivity[$date] = $dailyStats->get($date)?->count ?? 0;
        }

        return [
            'total_actions' => (int) $overallStats->total_actions,
            'unique_subjects' => (int) $overallStats->unique_subjects,
            'unique_users' => (int) $overallStats->unique_users,
            'actions_by_type' => $actionStats->toArray(),
            'daily_activity' => $dailyActivity,
            'most_active_day' => array_keys($dailyActivity, max($dailyActivity))[0] ?? null,
            'average_daily_activity' => round(array_sum($dailyActivity) / $days, 2),
        ];
    }

    /**
     * 清理舊日誌
     * 
     * @param int $daysToKeep 保留天數
     * @return int 刪除的記錄數量
     */
    public function cleanup(int $daysToKeep = 365): int
    {
        $cutoffDate = Carbon::now()->subDays($daysToKeep);
        
        $deletedCount = DB::table($this->tableName)
                          ->where('created_at', '<', $cutoffDate)
                          ->delete();

        if ($deletedCount > 0) {
            Log::info("清理審計日誌", [
                'table' => $this->tableName,
                'deleted_count' => $deletedCount,
                'cutoff_date' => $cutoffDate->toDateString(),
                'days_kept' => $daysToKeep,
            ]);
        }

        return $deletedCount;
    }

    /**
     * 匯出日誌
     * 
     * @param array $filters 篩選條件
     * @return array
     */
    public function export(array $filters = []): array
    {
        $query = DB::table($this->tableName);

        // 應用篩選條件
        if (!empty($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }
        
        if (!empty($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (!empty($filters['subject_type'])) {
            $query->where('subject_type', $filters['subject_type']);
        }

        $logs = $query->orderBy('created_at', 'desc')
                     ->get()
                     ->map(function ($log) {
                         $log->data = json_decode($log->data, true);
                         return $log;
                     });

        return [
            'export_date' => now()->toISOString(),
            'total_records' => $logs->count(),
            'filters_applied' => $filters,
            'logs' => $logs->toArray(),
        ];
    }

    /**
     * 取得使用者的操作歷史
     * 
     * @param int $userId
     * @param int $limit
     * @return Collection
     */
    public function getUserHistory(int $userId, int $limit = 50): Collection
    {
        return DB::table($this->tableName)
                 ->where('user_id', $userId)
                 ->orderBy('created_at', 'desc')
                 ->limit($limit)
                 ->get()
                 ->map(function ($log) {
                     $log->data = json_decode($log->data, true);
                     return $log;
                 });
    }

    /**
     * 取得對象的變更歷史
     * 
     * @param string $subjectType
     * @param int $subjectId
     * @return Collection
     */
    public function getSubjectHistory(string $subjectType, int $subjectId): Collection
    {
        return DB::table($this->tableName)
                 ->where('subject_type', $subjectType)
                 ->where('subject_id', $subjectId)
                 ->orderBy('created_at', 'desc')
                 ->get()
                 ->map(function ($log) {
                     $log->data = json_decode($log->data, true);
                     return $log;
                 });
    }

    /**
     * 儲存審計日誌到資料庫
     * 
     * @param array $data
     * @return void
     */
    protected function storeAuditLog(array $data): void
    {
        try {
            DB::table($this->tableName)->insert([
                'action' => $data['action'],
                'subject_type' => $data['subject_type'],
                'subject_id' => $data['subject_id'],
                'user_id' => $data['user_id'],
                'username' => $data['username'],
                'ip_address' => $data['ip_address'],
                'user_agent' => $data['user_agent'],
                'url' => $data['url'],
                'method' => $data['method'],
                'data' => json_encode($data),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // 如果儲存審計日誌失敗，記錄錯誤但不影響主要功能
            Log::error('儲存審計日誌失敗', [
                'table' => $this->tableName,
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
        }
    }
}