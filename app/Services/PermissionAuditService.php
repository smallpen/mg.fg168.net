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
 * 權限審計服務
 * 
 * 專門處理權限相關的審計日誌記錄、查詢和管理
 */
class PermissionAuditService implements AuditServiceInterface
{
    /**
     * 記錄權限變更
     * 
     * @param string $action 操作類型 (created, updated, deleted)
     * @param Permission $permission 權限物件
     * @param array $changes 變更內容
     * @param User|null $user 操作使用者
     * @return void
     */
    public function logPermissionChange(string $action, Permission $permission, array $changes = [], ?User $user = null): void
    {
        $user = $user ?? Auth::user();
        
        $logData = [
            'action' => $action,
            'permission_id' => $permission->id,
            'permission_name' => $permission->name,
            'permission_display_name' => $permission->display_name,
            'permission_module' => $permission->module,
            'permission_type' => $permission->type,
            'user_id' => $user?->id,
            'username' => $user?->username,
            'user_name' => $user?->name,
            'changes' => $changes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->url(),
            'method' => request()->method(),
            'timestamp' => now()->toISOString(),
        ];

        // 記錄到應用程式日誌
        Log::info("權限{$action}: {$permission->name}", $logData);

        // 儲存到資料庫
        $this->storePermissionAuditLog($action, $logData);
    }

    /**
     * 記錄權限依賴關係變更
     * 
     * @param Permission $permission
     * @param string $action (added, removed, synced)
     * @param array $dependencyIds
     * @param User|null $user
     * @return void
     */
    public function logDependencyChange(Permission $permission, string $action, array $dependencyIds, ?User $user = null): void
    {
        $user = $user ?? Auth::user();
        
        $dependencyNames = Permission::whereIn('id', $dependencyIds)->pluck('name')->toArray();
        
        $logData = [
            'action' => "dependency_{$action}",
            'permission_id' => $permission->id,
            'permission_name' => $permission->name,
            'dependency_ids' => $dependencyIds,
            'dependency_names' => $dependencyNames,
            'user_id' => $user?->id,
            'username' => $user?->username,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ];

        Log::info("權限依賴{$action}: {$permission->name}", $logData);
        $this->storePermissionAuditLog("dependency_{$action}", $logData);
    }

    /**
     * 記錄權限角色指派變更
     * 
     * @param Permission $permission
     * @param string $action (assigned, unassigned)
     * @param array $roleIds
     * @param User|null $user
     * @return void
     */
    public function logRoleAssignmentChange(Permission $permission, string $action, array $roleIds, ?User $user = null): void
    {
        $user = $user ?? Auth::user();
        
        $roleNames = DB::table('roles')->whereIn('id', $roleIds)->pluck('name')->toArray();
        
        $logData = [
            'action' => "role_{$action}",
            'permission_id' => $permission->id,
            'permission_name' => $permission->name,
            'role_ids' => $roleIds,
            'role_names' => $roleNames,
            'user_id' => $user?->id,
            'username' => $user?->username,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ];

        Log::info("權限角色{$action}: {$permission->name}", $logData);
        $this->storePermissionAuditLog("role_{$action}", $logData);
    }

    /**
     * 記錄權限匯入匯出操作
     * 
     * @param string $action (exported, imported)
     * @param array $data
     * @param User|null $user
     * @return void
     */
    public function logImportExportAction(string $action, array $data, ?User $user = null): void
    {
        $user = $user ?? Auth::user();
        
        $logData = [
            'action' => "permission_{$action}",
            'data' => $data,
            'user_id' => $user?->id,
            'username' => $user?->username,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ];

        Log::info("權限{$action}", $logData);
        $this->storePermissionAuditLog("permission_{$action}", $logData);
    }

    /**
     * 記錄權限測試操作
     * 
     * @param array $testData
     * @param User|null $user
     * @return void
     */
    public function logPermissionTest(array $testData, ?User $user = null): void
    {
        $user = $user ?? Auth::user();
        
        $logData = [
            'action' => 'permission_test',
            'test_data' => $testData,
            'user_id' => $user?->id,
            'username' => $user?->username,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ];

        Log::info('權限測試', $logData);
        $this->storePermissionAuditLog('permission_test', $logData);
    }

    /**
     * 取得權限的審計日誌
     * 
     * @param int $permissionId
     * @param int $limit
     * @return Collection
     */
    public function getPermissionAuditLog(int $permissionId, int $limit = 50): Collection
    {
        return DB::table('permission_audit_logs')
                 ->where('permission_id', $permissionId)
                 ->orderBy('created_at', 'desc')
                 ->limit($limit)
                 ->get()
                 ->map(function ($log) {
                     $log->data = json_decode($log->data, true);
                     return $log;
                 });
    }

    /**
     * 搜尋審計日誌
     * 
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function searchAuditLog(array $filters): LengthAwarePaginator
    {
        $query = DB::table('permission_audit_logs');

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

        // 權限篩選
        if (!empty($filters['permission_id'])) {
            $query->where('permission_id', $filters['permission_id']);
        }
        
        if (!empty($filters['permission_name'])) {
            $query->where('permission_name', 'like', "%{$filters['permission_name']}%");
        }

        // 操作類型篩選
        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        // 模組篩選
        if (!empty($filters['module'])) {
            $query->where('permission_module', $filters['module']);
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
     * 取得審計日誌統計資料
     * 
     * @param int $days
     * @return array
     */
    public function getAuditStats(int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days);

        $stats = DB::table('permission_audit_logs')
                   ->where('created_at', '>=', $startDate)
                   ->selectRaw('
                       action,
                       COUNT(*) as count,
                       COUNT(DISTINCT permission_id) as unique_permissions,
                       COUNT(DISTINCT user_id) as unique_users
                   ')
                   ->groupBy('action')
                   ->get()
                   ->keyBy('action');

        $dailyStats = DB::table('permission_audit_logs')
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
            'total_actions' => $stats->sum('count'),
            'unique_permissions' => $stats->sum('unique_permissions'),
            'unique_users' => $stats->sum('unique_users'),
            'actions_by_type' => $stats->toArray(),
            'daily_activity' => $dailyActivity,
            'most_active_day' => array_keys($dailyActivity, max($dailyActivity))[0] ?? null,
            'average_daily_activity' => round(array_sum($dailyActivity) / $days, 2),
        ];
    }

    /**
     * 取得使用者的權限操作歷史
     * 
     * @param int $userId
     * @param int $limit
     * @return Collection
     */
    public function getUserPermissionHistory(int $userId, int $limit = 50): Collection
    {
        return DB::table('permission_audit_logs')
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
     * 取得權限的變更歷史
     * 
     * @param int $permissionId
     * @return Collection
     */
    public function getPermissionChangeHistory(int $permissionId): Collection
    {
        return DB::table('permission_audit_logs')
                 ->where('permission_id', $permissionId)
                 ->whereIn('action', ['created', 'updated', 'deleted'])
                 ->orderBy('created_at', 'desc')
                 ->get()
                 ->map(function ($log) {
                     $log->data = json_decode($log->data, true);
                     return $log;
                 });
    }

    /**
     * 清理舊的審計日誌
     * 
     * @param int $daysToKeep
     * @return int 刪除的記錄數量
     */
    public function cleanupOldAuditLogs(int $daysToKeep = 365): int
    {
        $cutoffDate = Carbon::now()->subDays($daysToKeep);
        
        $deletedCount = DB::table('permission_audit_logs')
                          ->where('created_at', '<', $cutoffDate)
                          ->delete();

        if ($deletedCount > 0) {
            Log::info("清理權限審計日誌", [
                'deleted_count' => $deletedCount,
                'cutoff_date' => $cutoffDate->toDateString(),
                'days_kept' => $daysToKeep,
            ]);
        }

        return $deletedCount;
    }

    /**
     * 取得清理統計資訊
     * 
     * @param int $daysToKeep
     * @return array
     */
    public function getCleanupStats(int $daysToKeep = 365): array
    {
        $cutoffDate = Carbon::now()->subDays($daysToKeep);
        
        $stats = DB::table('permission_audit_logs')
                   ->where('created_at', '<', $cutoffDate)
                   ->selectRaw('
                       COUNT(*) as total_records,
                       COUNT(DISTINCT permission_id) as unique_permissions,
                       COUNT(DISTINCT user_id) as unique_users,
                       MIN(created_at) as oldest_record_date,
                       MAX(created_at) as newest_record_date
                   ')
                   ->first();

        return [
            'total_records' => (int) ($stats->total_records ?? 0),
            'unique_permissions' => (int) ($stats->unique_permissions ?? 0),
            'unique_users' => (int) ($stats->unique_users ?? 0),
            'oldest_record_date' => $stats->oldest_record_date,
            'newest_record_date' => $stats->newest_record_date,
            'cutoff_date' => $cutoffDate->toDateString(),
            'days_to_keep' => $daysToKeep,
        ];
    }

    /**
     * 取得審計日誌的儲存空間使用情況
     * 
     * @return array
     */
    public function getStorageStats(): array
    {
        $stats = DB::selectOne("
            SELECT 
                COUNT(*) as total_records,
                ROUND(
                    (DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2
                ) as size_mb,
                ROUND(AVG(LENGTH(data)), 2) as avg_data_size
            FROM information_schema.TABLES t
            LEFT JOIN permission_audit_logs p ON 1=1
            WHERE t.TABLE_SCHEMA = DATABASE()
            AND t.TABLE_NAME = 'permission_audit_logs'
            GROUP BY t.TABLE_NAME
        ");

        $oldestRecord = DB::table('permission_audit_logs')
                          ->orderBy('created_at')
                          ->value('created_at');

        $newestRecord = DB::table('permission_audit_logs')
                          ->orderBy('created_at', 'desc')
                          ->value('created_at');

        return [
            'total_records' => (int) ($stats->total_records ?? 0),
            'size_mb' => (float) ($stats->size_mb ?? 0),
            'avg_data_size_bytes' => (float) ($stats->avg_data_size ?? 0),
            'oldest_record' => $oldestRecord,
            'newest_record' => $newestRecord,
            'date_range_days' => $oldestRecord && $newestRecord ? 
                Carbon::parse($newestRecord)->diffInDays(Carbon::parse($oldestRecord)) : 0,
        ];
    }

    /**
     * 取得審計日誌的詳細分析
     * 
     * @param int $days
     * @return array
     */
    public function getDetailedAnalysis(int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days);

        // 按操作類型統計
        $actionStats = DB::table('permission_audit_logs')
                         ->where('created_at', '>=', $startDate)
                         ->selectRaw('action, COUNT(*) as count')
                         ->groupBy('action')
                         ->orderBy('count', 'desc')
                         ->get()
                         ->keyBy('action');

        // 按模組統計
        $moduleStats = DB::table('permission_audit_logs')
                         ->where('created_at', '>=', $startDate)
                         ->whereNotNull('permission_module')
                         ->selectRaw('permission_module, COUNT(*) as count')
                         ->groupBy('permission_module')
                         ->orderBy('count', 'desc')
                         ->get()
                         ->keyBy('permission_module');

        // 按使用者統計
        $userStats = DB::table('permission_audit_logs')
                       ->where('created_at', '>=', $startDate)
                       ->whereNotNull('user_id')
                       ->selectRaw('username, COUNT(*) as count')
                       ->groupBy('username')
                       ->orderBy('count', 'desc')
                       ->limit(10)
                       ->get();

        // 按小時統計活動
        $hourlyStats = DB::table('permission_audit_logs')
                         ->where('created_at', '>=', $startDate)
                         ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
                         ->groupBy('hour')
                         ->orderBy('hour')
                         ->get()
                         ->keyBy('hour');

        // 填補缺失的小時
        $hourlyActivity = [];
        for ($i = 0; $i < 24; $i++) {
            $hourlyActivity[$i] = $hourlyStats->get($i)?->count ?? 0;
        }

        return [
            'period_days' => $days,
            'start_date' => $startDate->toDateString(),
            'end_date' => Carbon::now()->toDateString(),
            'actions_by_type' => $actionStats->toArray(),
            'actions_by_module' => $moduleStats->toArray(),
            'top_users' => $userStats->toArray(),
            'hourly_activity' => $hourlyActivity,
            'peak_hour' => array_keys($hourlyActivity, max($hourlyActivity))[0] ?? null,
            'total_actions' => array_sum($hourlyActivity),
        ];
    }

    /**
     * 匯出審計日誌
     * 
     * @param array $filters
     * @return array
     */
    public function exportAuditLogs(array $filters = []): array
    {
        $query = DB::table('permission_audit_logs');

        // 應用篩選條件
        if (!empty($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }
        
        if (!empty($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        if (!empty($filters['permission_id'])) {
            $query->where('permission_id', $filters['permission_id']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
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
     * 儲存權限審計日誌到資料庫
     * 
     * @param string $action
     * @param array $data
     * @return void
     */
    private function storePermissionAuditLog(string $action, array $data): void
    {
        try {
            DB::table('permission_audit_logs')->insert([
                'action' => $action,
                'permission_id' => $data['permission_id'] ?? null,
                'permission_name' => $data['permission_name'] ?? null,
                'permission_module' => $data['permission_module'] ?? null,
                'user_id' => $data['user_id'] ?? null,
                'username' => $data['username'] ?? null,
                'ip_address' => $data['ip_address'] ?? null,
                'user_agent' => $data['user_agent'] ?? null,
                'url' => $data['url'] ?? null,
                'method' => $data['method'] ?? null,
                'data' => json_encode($data),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // 如果儲存審計日誌失敗，記錄錯誤但不影響主要功能
            Log::error('儲存權限審計日誌失敗', [
                'error' => $e->getMessage(),
                'action' => $action,
                'data' => $data,
            ]);
        }
    }

    /**
     * 實作 AuditServiceInterface::log 方法
     * 
     * @param string $action
     * @param mixed $subject
     * @param array $data
     * @param mixed $user
     * @return void
     */
    public function log(string $action, $subject, array $data = [], $user = null): void
    {
        if ($subject instanceof Permission) {
            $this->logPermissionChange($action, $subject, $data, $user);
        } else {
            // 對於非權限對象，記錄一般日誌
            $user = $user ?? Auth::user();
            
            $logData = [
                'action' => $action,
                'subject_type' => is_object($subject) ? get_class($subject) : null,
                'subject_id' => is_object($subject) && method_exists($subject, 'getKey') ? $subject->getKey() : null,
                'data' => $data,
                'user_id' => $user?->id,
                'username' => $user?->username,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now()->toISOString(),
            ];

            Log::info("權限相關操作: {$action}", $logData);
            $this->storePermissionAuditLog($action, $logData);
        }
    }

    /**
     * 實作 AuditServiceInterface::search 方法
     * 
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function search(array $filters): LengthAwarePaginator
    {
        return $this->searchAuditLog($filters);
    }

    /**
     * 實作 AuditServiceInterface::getStats 方法
     * 
     * @param int $days
     * @return array
     */
    public function getStats(int $days = 30): array
    {
        return $this->getAuditStats($days);
    }

    /**
     * 實作 AuditServiceInterface::cleanup 方法
     * 
     * @param int $daysToKeep
     * @return int
     */
    public function cleanup(int $daysToKeep = 365): int
    {
        return $this->cleanupOldAuditLogs($daysToKeep);
    }

    /**
     * 實作 AuditServiceInterface::export 方法
     * 
     * @param array $filters
     * @return array
     */
    public function export(array $filters = []): array
    {
        return $this->exportAuditLogs($filters);
    }
}