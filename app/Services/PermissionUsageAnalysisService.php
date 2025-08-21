<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * 權限使用情況分析服務
 * 
 * 提供權限使用統計、分析和快取功能
 */
class PermissionUsageAnalysisService
{
    /**
     * 快取時間（秒）
     */
    private const CACHE_TTL = 3600; // 1小時
    private const STATS_CACHE_TTL = 1800; // 30分鐘
    private const USAGE_CACHE_TTL = 7200; // 2小時

    /**
     * 取得權限使用統計資料
     * 
     * @param int|null $permissionId 特定權限ID，null表示所有權限
     * @return array
     */
    public function getUsageStats(?int $permissionId = null): array
    {
        $cacheKey = $permissionId ? "permission_usage_stats_{$permissionId}" : 'all_permissions_usage_stats';
        
        return Cache::remember($cacheKey, self::STATS_CACHE_TTL, function () use ($permissionId) {
            if ($permissionId) {
                return $this->calculateSinglePermissionStats($permissionId);
            }
            
            return $this->calculateOverallStats();
        });
    }

    /**
     * 取得未使用的權限列表
     * 
     * @return Collection
     */
    public function getUnusedPermissions(): Collection
    {
        return Cache::remember('unused_permissions', self::CACHE_TTL, function () {
            return Permission::doesntHave('roles')
                            ->with(['dependencies', 'dependents'])
                            ->orderBy('module')
                            ->orderBy('name')
                            ->get()
                            ->map(function ($permission) {
                                return [
                                    'id' => $permission->id,
                                    'name' => $permission->name,
                                    'display_name' => $permission->display_name,
                                    'module' => $permission->module,
                                    'type' => $permission->type,
                                    'created_at' => $permission->created_at,
                                    'has_dependencies' => $permission->dependencies->isNotEmpty(),
                                    'has_dependents' => $permission->dependents->isNotEmpty(),
                                    'is_system_permission' => $permission->is_system_permission,
                                ];
                            });
        });
    }

    /**
     * 取得權限使用頻率統計
     * 
     * @param int $permissionId
     * @return array
     */
    public function getUsageFrequency(int $permissionId): array
    {
        $cacheKey = "permission_frequency_{$permissionId}";
        
        return Cache::remember($cacheKey, self::USAGE_CACHE_TTL, function () use ($permissionId) {
            $permission = Permission::findOrFail($permissionId);
            
            // 基本使用統計
            $roleCount = $permission->roles()->count();
            $userCount = $this->getUserCountForPermission($permissionId);
            
            // 從活動日誌計算使用頻率
            $recentUsage = $this->getRecentUsageFromLogs($permissionId);
            
            // 計算使用頻率分數
            $frequencyScore = $this->calculateFrequencyScore($roleCount, $userCount, $recentUsage);
            
            return [
                'permission_id' => $permissionId,
                'role_count' => $roleCount,
                'user_count' => $userCount,
                'recent_usage_count' => $recentUsage['count'],
                'last_used_at' => $recentUsage['last_used_at'],
                'frequency_score' => $frequencyScore,
                'frequency_level' => $this->getFrequencyLevel($frequencyScore),
                'usage_trend' => $this->getUsageTrend($permissionId),
            ];
        });
    }

    /**
     * 取得權限使用趨勢
     * 
     * @param int $permissionId
     * @param int $days 分析天數
     * @return array
     */
    public function getUsageTrend(int $permissionId, int $days = 30): array
    {
        $cacheKey = "permission_trend_{$permissionId}_{$days}";
        
        return Cache::remember($cacheKey, self::USAGE_CACHE_TTL, function () use ($permissionId, $days) {
            $startDate = Carbon::now()->subDays($days);
            
            // 從活動日誌取得使用趨勢
            $dailyUsage = DB::table('activity_log')
                           ->where('subject_type', Permission::class)
                           ->where('subject_id', $permissionId)
                           ->where('created_at', '>=', $startDate)
                           ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                           ->groupBy('date')
                           ->orderBy('date')
                           ->get()
                           ->keyBy('date')
                           ->map(function ($item) {
                               return (int) $item->count;
                           });
            
            // 填補缺失的日期
            $trend = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i)->format('Y-m-d');
                $trend[$date] = $dailyUsage->get($date, 0);
            }
            
            return [
                'daily_usage' => $trend,
                'total_usage' => array_sum($trend),
                'average_daily' => round(array_sum($trend) / $days, 2),
                'peak_usage' => max($trend),
                'trend_direction' => $this->calculateTrendDirection($trend),
            ];
        });
    }

    /**
     * 取得模組使用統計
     * 
     * @return array
     */
    public function getModuleUsageStats(): array
    {
        return Cache::remember('module_usage_stats', self::STATS_CACHE_TTL, function () {
            $modules = Permission::select('module')
                                ->withCount(['roles as role_count'])
                                ->get()
                                ->groupBy('module')
                                ->map(function ($permissions, $module) {
                                    $totalPermissions = $permissions->count();
                                    $usedPermissions = $permissions->where('role_count', '>', 0)->count();
                                    $unusedPermissions = $totalPermissions - $usedPermissions;
                                    
                                    $totalUsers = $this->getUserCountForModule($module);
                                    
                                    return [
                                        'module' => $module,
                                        'total_permissions' => $totalPermissions,
                                        'used_permissions' => $usedPermissions,
                                        'unused_permissions' => $unusedPermissions,
                                        'usage_percentage' => $totalPermissions > 0 ? 
                                                            round(($usedPermissions / $totalPermissions) * 100, 2) : 0,
                                        'total_users' => $totalUsers,
                                        'average_permissions_per_user' => $totalUsers > 0 ? 
                                                                        round($usedPermissions / $totalUsers, 2) : 0,
                                    ];
                                })
                                ->sortByDesc('usage_percentage')
                                ->values()
                                ->toArray();
            
            return $modules;
        });
    }

    /**
     * 取得權限使用熱力圖資料
     * 
     * @return array
     */
    public function getUsageHeatmapData(): array
    {
        return Cache::remember('permission_usage_heatmap', self::CACHE_TTL, function () {
            $permissions = Permission::with('roles.users')
                                   ->get()
                                   ->map(function ($permission) {
                                       $userCount = $permission->roles
                                                              ->pluck('users')
                                                              ->flatten()
                                                              ->unique('id')
                                                              ->count();
                                       
                                       return [
                                           'id' => $permission->id,
                                           'name' => $permission->name,
                                           'module' => $permission->module,
                                           'type' => $permission->type,
                                           'user_count' => $userCount,
                                           'role_count' => $permission->roles->count(),
                                           'intensity' => $this->calculateUsageIntensity($userCount, $permission->roles->count()),
                                       ];
                                   })
                                   ->sortByDesc('intensity')
                                   ->values()
                                   ->toArray();
            
            return $permissions;
        });
    }

    /**
     * 標記未使用的權限
     * 
     * @param array $options 標記選項
     * @return array
     */
    public function markUnusedPermissions(array $options = []): array
    {
        $unusedPermissions = $this->getUnusedPermissions();
        $daysThreshold = $options['days_threshold'] ?? 90; // 90天未使用視為未使用
        $excludeSystemPermissions = $options['exclude_system'] ?? true;
        
        $markedPermissions = $unusedPermissions->filter(function ($permission) use ($daysThreshold, $excludeSystemPermissions) {
            // 排除系統權限
            if ($excludeSystemPermissions && $permission['is_system_permission']) {
                return false;
            }
            
            // 檢查是否在指定天數內未使用
            $createdAt = Carbon::parse($permission['created_at']);
            $daysSinceCreated = $createdAt->diffInDays(now());
            
            return $daysSinceCreated >= $daysThreshold;
        });
        
        // 更新標記狀態（這裡可以添加到資料庫欄位或快取中）
        $this->updateUnusedPermissionMarks($markedPermissions->pluck('id')->toArray());
        
        return [
            'total_unused' => $unusedPermissions->count(),
            'marked_unused' => $markedPermissions->count(),
            'excluded_system' => $unusedPermissions->where('is_system_permission', true)->count(),
            'marked_permissions' => $markedPermissions->values()->toArray(),
        ];
    }

    /**
     * 清除使用情況快取
     * 
     * @param int|null $permissionId 特定權限ID，null表示清除所有
     * @return void
     */
    public function clearUsageCache(?int $permissionId = null): void
    {
        if ($permissionId) {
            Cache::forget("permission_usage_stats_{$permissionId}");
            Cache::forget("permission_frequency_{$permissionId}");
            Cache::forget("permission_trend_{$permissionId}_30");
        } else {
            // 清除所有相關快取
            $cacheKeys = [
                'all_permissions_usage_stats',
                'unused_permissions',
                'module_usage_stats',
                'permission_usage_heatmap',
            ];
            
            foreach ($cacheKeys as $key) {
                Cache::forget($key);
            }
            
            // 清除所有權限的個別快取
            Permission::pluck('id')->each(function ($id) {
                Cache::forget("permission_usage_stats_{$id}");
                Cache::forget("permission_frequency_{$id}");
                Cache::forget("permission_trend_{$id}_30");
            });
        }
    }

    /**
     * 計算單一權限統計
     * 
     * @param int $permissionId
     * @return array
     */
    private function calculateSinglePermissionStats(int $permissionId): array
    {
        $permission = Permission::with(['roles.users', 'dependencies', 'dependents'])
                                ->findOrFail($permissionId);
        
        $roleCount = $permission->roles->count();
        $userCount = $permission->roles->pluck('users')->flatten()->unique('id')->count();
        $dependencyCount = $permission->dependencies->count();
        $dependentCount = $permission->dependents->count();
        
        $recentUsage = $this->getRecentUsageFromLogs($permissionId);
        
        return [
            'permission_id' => $permissionId,
            'permission_name' => $permission->name,
            'display_name' => $permission->display_name,
            'module' => $permission->module,
            'type' => $permission->type,
            'is_used' => $roleCount > 0,
            'role_count' => $roleCount,
            'user_count' => $userCount,
            'dependency_count' => $dependencyCount,
            'dependent_count' => $dependentCount,
            'usage_frequency' => $this->calculateFrequencyScore($roleCount, $userCount, $recentUsage),
            'last_used_at' => $recentUsage['last_used_at'],
            'recent_usage_count' => $recentUsage['count'],
            'is_system_permission' => $permission->is_system_permission,
            'can_be_deleted' => $permission->can_be_deleted,
        ];
    }

    /**
     * 計算整體統計
     * 
     * @return array
     */
    private function calculateOverallStats(): array
    {
        $totalPermissions = Permission::count();
        $usedPermissions = Permission::has('roles')->count();
        $unusedPermissions = $totalPermissions - $usedPermissions;
        
        $systemPermissions = Permission::where(function ($query) {
            $query->where('module', 'system')
                  ->orWhere('module', 'auth')
                  ->orWhere('module', 'admin')
                  ->orWhere('name', 'like', 'system.%')
                  ->orWhere('name', 'like', 'auth.%')
                  ->orWhere('name', 'like', 'admin.core%');
        })->count();
        
        $customPermissions = $totalPermissions - $systemPermissions;
        
        return [
            'total_permissions' => $totalPermissions,
            'used_permissions' => $usedPermissions,
            'unused_permissions' => $unusedPermissions,
            'system_permissions' => $systemPermissions,
            'custom_permissions' => $customPermissions,
            'usage_percentage' => $totalPermissions > 0 ? 
                                round(($usedPermissions / $totalPermissions) * 100, 2) : 0,
            'unused_percentage' => $totalPermissions > 0 ? 
                                 round(($unusedPermissions / $totalPermissions) * 100, 2) : 0,
            'total_roles' => Role::count(),
            'total_users' => User::count(),
            'average_permissions_per_role' => $this->getAveragePermissionsPerRole(),
            'average_permissions_per_user' => $this->getAveragePermissionsPerUser(),
        ];
    }

    /**
     * 取得權限的使用者數量
     * 
     * @param int $permissionId
     * @return int
     */
    private function getUserCountForPermission(int $permissionId): int
    {
        return DB::table('role_permissions')
                 ->join('user_roles', 'role_permissions.role_id', '=', 'user_roles.role_id')
                 ->where('role_permissions.permission_id', $permissionId)
                 ->distinct('user_roles.user_id')
                 ->count('user_roles.user_id');
    }

    /**
     * 取得模組的使用者數量
     * 
     * @param string $module
     * @return int
     */
    private function getUserCountForModule(string $module): int
    {
        return DB::table('permissions')
                 ->join('role_permissions', 'permissions.id', '=', 'role_permissions.permission_id')
                 ->join('user_roles', 'role_permissions.role_id', '=', 'user_roles.role_id')
                 ->where('permissions.module', $module)
                 ->distinct('user_roles.user_id')
                 ->count('user_roles.user_id');
    }

    /**
     * 從活動日誌取得最近使用情況
     * 
     * @param int $permissionId
     * @return array
     */
    private function getRecentUsageFromLogs(int $permissionId): array
    {
        $recentLogs = DB::table('activity_log')
                       ->where('subject_type', Permission::class)
                       ->where('subject_id', $permissionId)
                       ->where('created_at', '>=', Carbon::now()->subDays(30))
                       ->orderBy('created_at', 'desc')
                       ->get();
        
        return [
            'count' => $recentLogs->count(),
            'last_used_at' => $recentLogs->first()?->created_at,
        ];
    }

    /**
     * 計算頻率分數
     * 
     * @param int $roleCount
     * @param int $userCount
     * @param array $recentUsage
     * @return float
     */
    private function calculateFrequencyScore(int $roleCount, int $userCount, array $recentUsage): float
    {
        // 基礎分數：角色數量 * 2 + 使用者數量
        $baseScore = ($roleCount * 2) + $userCount;
        
        // 最近使用加成：最近30天的使用次數 * 0.5
        $recentBonus = $recentUsage['count'] * 0.5;
        
        // 最近使用時間加成
        $timeBonus = 0;
        if ($recentUsage['last_used_at']) {
            $daysSinceLastUse = Carbon::parse($recentUsage['last_used_at'])->diffInDays(now());
            $timeBonus = max(0, 30 - $daysSinceLastUse) * 0.1;
        }
        
        return round($baseScore + $recentBonus + $timeBonus, 2);
    }

    /**
     * 取得頻率等級
     * 
     * @param float $score
     * @return string
     */
    private function getFrequencyLevel(float $score): string
    {
        if ($score >= 50) return 'very_high';
        if ($score >= 20) return 'high';
        if ($score >= 10) return 'medium';
        if ($score >= 5) return 'low';
        return 'very_low';
    }

    /**
     * 計算趨勢方向
     * 
     * @param array $trend
     * @return string
     */
    private function calculateTrendDirection(array $trend): string
    {
        $values = array_values($trend);
        $count = count($values);
        
        if ($count < 2) return 'stable';
        
        $firstHalf = array_slice($values, 0, intval($count / 2));
        $secondHalf = array_slice($values, intval($count / 2));
        
        $firstAvg = array_sum($firstHalf) / count($firstHalf);
        $secondAvg = array_sum($secondHalf) / count($secondHalf);
        
        $difference = $secondAvg - $firstAvg;
        
        if ($difference > 1) return 'increasing';
        if ($difference < -1) return 'decreasing';
        return 'stable';
    }

    /**
     * 計算使用強度
     * 
     * @param int $userCount
     * @param int $roleCount
     * @return float
     */
    private function calculateUsageIntensity(int $userCount, int $roleCount): float
    {
        // 使用強度 = (使用者數量 * 2 + 角色數量) / 10
        return round(($userCount * 2 + $roleCount) / 10, 2);
    }

    /**
     * 取得每個角色的平均權限數
     * 
     * @return float
     */
    private function getAveragePermissionsPerRole(): float
    {
        $totalRoles = Role::count();
        if ($totalRoles === 0) return 0;
        
        $totalPermissions = DB::table('role_permissions')->count();
        return round($totalPermissions / $totalRoles, 2);
    }

    /**
     * 取得每個使用者的平均權限數
     * 
     * @return float
     */
    private function getAveragePermissionsPerUser(): float
    {
        $totalUsers = User::count();
        if ($totalUsers === 0) return 0;
        
        $totalUserPermissions = DB::table('user_roles')
                                 ->join('role_permissions', 'user_roles.role_id', '=', 'role_permissions.role_id')
                                 ->count();
        
        return round($totalUserPermissions / $totalUsers, 2);
    }

    /**
     * 更新未使用權限標記
     * 
     * @param array $permissionIds
     * @return void
     */
    private function updateUnusedPermissionMarks(array $permissionIds): void
    {
        // 將標記資訊存入快取
        Cache::put('marked_unused_permissions', $permissionIds, self::CACHE_TTL);
        
        // 這裡可以添加到資料庫欄位或其他持久化存儲
        // 例如：Permission::whereIn('id', $permissionIds)->update(['is_marked_unused' => true]);
    }
}