<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 角色統計服務
 * 
 * 提供角色相關的統計資料計算和快取功能
 */
class RoleStatisticsService
{
    /**
     * 快取鍵前綴
     */
    private const CACHE_PREFIX = 'role_stats';
    
    /**
     * 預設快取時間（秒）
     */
    private const DEFAULT_CACHE_TTL = 3600; // 1小時

    /**
     * 取得角色的詳細統計資訊
     *
     * @param Role $role
     * @param bool $useCache 是否使用快取
     * @return array
     */
    public function getRoleStatistics(Role $role, bool $useCache = true): array
    {
        $cacheKey = self::CACHE_PREFIX . "_role_{$role->id}";
        
        if (!$useCache) {
            Cache::forget($cacheKey);
        }
        
        return Cache::remember($cacheKey, self::DEFAULT_CACHE_TTL, function () use ($role) {
            return $this->calculateRoleStatistics($role);
        });
    }

    /**
     * 取得系統整體角色統計
     *
     * @param bool $useCache 是否使用快取
     * @return array
     */
    public function getSystemRoleStatistics(bool $useCache = true): array
    {
        $cacheKey = self::CACHE_PREFIX . '_system';
        
        if (!$useCache) {
            Cache::forget($cacheKey);
        }
        
        return Cache::remember($cacheKey, self::DEFAULT_CACHE_TTL, function () {
            return $this->calculateSystemStatistics();
        });
    }

    /**
     * 取得權限分佈統計
     *
     * @param Role|null $role 特定角色，null 表示全系統統計
     * @param bool $useCache 是否使用快取
     * @return array
     */
    public function getPermissionDistribution(?Role $role = null, bool $useCache = true): array
    {
        $cacheKey = $role 
            ? self::CACHE_PREFIX . "_permission_dist_role_{$role->id}"
            : self::CACHE_PREFIX . '_permission_dist_system';
        
        if (!$useCache) {
            Cache::forget($cacheKey);
        }
        
        return Cache::remember($cacheKey, self::DEFAULT_CACHE_TTL, function () use ($role) {
            return $this->calculatePermissionDistribution($role);
        });
    }

    /**
     * 取得角色使用趨勢統計
     *
     * @param int $days 統計天數
     * @param bool $useCache 是否使用快取
     * @return array
     */
    public function getRoleUsageTrends(int $days = 30, bool $useCache = true): array
    {
        $cacheKey = self::CACHE_PREFIX . "_usage_trends_{$days}";
        
        if (!$useCache) {
            Cache::forget($cacheKey);
        }
        
        return Cache::remember($cacheKey, self::DEFAULT_CACHE_TTL, function () use ($days) {
            return $this->calculateUsageTrends($days);
        });
    }

    /**
     * 清除所有角色統計快取
     *
     * @return void
     */
    public function clearAllCache(): void
    {
        $keys = [
            self::CACHE_PREFIX . '_system',
            self::CACHE_PREFIX . '_permission_dist_system',
        ];
        
        // 清除系統級快取
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        
        // 清除所有角色的個別快取
        Role::all()->each(function ($role) {
            $this->clearRoleCache($role);
        });
    }

    /**
     * 清除特定角色的快取
     *
     * @param Role $role
     * @return void
     */
    public function clearRoleCache(Role $role): void
    {
        $keys = [
            self::CACHE_PREFIX . "_role_{$role->id}",
            self::CACHE_PREFIX . "_permission_dist_role_{$role->id}",
        ];
        
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * 計算角色統計資訊
     *
     * @param Role $role
     * @return array
     */
    private function calculateRoleStatistics(Role $role): array
    {
        // 基本統計
        $userCount = $role->users()->count();
        $directPermissionCount = $role->permissions()->count();
        $allPermissions = $role->getAllPermissions();
        $totalPermissionCount = $allPermissions->count();
        $inheritedPermissionCount = $role->getInheritedPermissions()->count();

        // 權限模組分佈
        $permissionsByModule = $allPermissions->groupBy('module');
        $moduleDistribution = $permissionsByModule->map(function ($permissions, $module) {
            return [
                'module' => $module,
                'count' => $permissions->count(),
                'permissions' => $permissions->pluck('display_name')->toArray()
            ];
        })->values()->toArray();

        // 層級資訊
        $hierarchyInfo = [
            'depth' => $role->getDepth(),
            'has_parent' => $role->hasParent(),
            'parent_name' => $role->parent?->display_name,
            'children_count' => $role->children()->count(),
            'descendants_count' => $role->getDescendants()->count(),
            'hierarchy_path' => $role->getHierarchyPath(),
        ];

        // 使用者分佈（按狀態）
        $userDistribution = [];
        if ($userCount > 0) {
            $activeUsers = $role->users()->where('users.is_active', true)->count();
            $inactiveUsers = $userCount - $activeUsers;
            $userDistribution = [
                'active' => $activeUsers,
                'inactive' => $inactiveUsers,
            ];
        }

        // 最近的權限變更記錄（如果有審計日誌）
        $recentChanges = $this->getRecentPermissionChanges($role);

        return [
            'basic' => [
                'user_count' => $userCount,
                'direct_permission_count' => $directPermissionCount,
                'inherited_permission_count' => $inheritedPermissionCount,
                'total_permission_count' => $totalPermissionCount,
                'is_system_role' => $role->is_system_role,
                'is_active' => $role->is_active,
                'created_at' => $role->created_at,
                'updated_at' => $role->updated_at,
            ],
            'hierarchy' => $hierarchyInfo,
            'permissions' => [
                'by_module' => $moduleDistribution,
                'direct_permissions' => $role->getDirectPermissions()->pluck('display_name')->toArray(),
                'inherited_permissions' => $role->getInheritedPermissions()->pluck('display_name')->toArray(),
            ],
            'users' => [
                'total' => $userCount,
                'distribution' => array_merge(['active' => 0, 'inactive' => 0], $userDistribution),
                'recent_assignments' => $this->getRecentUserAssignments($role),
            ],
            'recent_changes' => $recentChanges,
            'calculated_at' => now(),
        ];
    }

    /**
     * 計算系統整體統計
     *
     * @return array
     */
    private function calculateSystemStatistics(): array
    {
        // 基本統計
        $totalRoles = Role::count();
        $activeRoles = Role::where('is_active', true)->count();
        $systemRoles = Role::where('is_system_role', true)->count();
        $rolesWithUsers = Role::whereHas('users')->count();
        $rolesWithPermissions = Role::whereHas('permissions')->count();

        // 層級統計
        $rootRoles = Role::whereNull('parent_id')->count();
        $childRoles = Role::whereNotNull('parent_id')->count();
        $maxDepth = $this->calculateMaxHierarchyDepth();

        // 權限統計
        $totalPermissions = Permission::count();
        $avgPermissionsPerRole = Role::withCount('permissions')->get()->avg('permissions_count') ?? 0;
        $avgUsersPerRole = Role::withCount('users')->get()->avg('users_count') ?? 0;

        // 最常用的角色
        $mostUsedRoles = Role::withCount('users')
            ->orderByDesc('users_count')
            ->limit(5)
            ->get()
            ->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->display_name,
                    'users_count' => $role->users_count,
                    'is_system_role' => $role->is_system_role,
                ];
            })
            ->toArray();

        // 權限最多的角色
        $mostPermissiveRoles = Role::withCount('permissions')
            ->orderByDesc('permissions_count')
            ->limit(5)
            ->get()
            ->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->display_name,
                    'permissions_count' => $role->permissions_count,
                    'is_system_role' => $role->is_system_role,
                ];
            })
            ->toArray();

        return [
            'overview' => [
                'total_roles' => $totalRoles,
                'active_roles' => $activeRoles,
                'inactive_roles' => $totalRoles - $activeRoles,
                'system_roles' => $systemRoles,
                'custom_roles' => $totalRoles - $systemRoles,
                'roles_with_users' => $rolesWithUsers,
                'roles_with_permissions' => $rolesWithPermissions,
                'empty_roles' => $totalRoles - $rolesWithPermissions,
            ],
            'hierarchy' => [
                'root_roles' => $rootRoles,
                'child_roles' => $childRoles,
                'max_depth' => $maxDepth,
                'avg_depth' => $this->calculateAverageDepth(),
            ],
            'permissions' => [
                'total_permissions' => $totalPermissions,
                'avg_permissions_per_role' => round($avgPermissionsPerRole, 2),
                'permission_coverage' => $this->calculatePermissionCoverage(),
            ],
            'users' => [
                'avg_users_per_role' => round($avgUsersPerRole, 2),
                'total_role_assignments' => DB::table('user_roles')->count(),
            ],
            'top_roles' => [
                'most_used' => $mostUsedRoles,
                'most_permissive' => $mostPermissiveRoles,
            ],
            'calculated_at' => now(),
        ];
    }

    /**
     * 計算權限分佈
     *
     * @param Role|null $role
     * @return array
     */
    private function calculatePermissionDistribution(?Role $role = null): array
    {
        if ($role) {
            // 特定角色的權限分佈
            $permissions = $role->getAllPermissions();
        } else {
            // 全系統權限分佈
            $permissions = Permission::all();
        }

        $byModule = $permissions->groupBy('module');
        $distribution = $byModule->map(function ($modulePermissions, $module) use ($role) {
            $count = $modulePermissions->count();
            $totalInModule = Permission::where('module', $module)->count();
            
            return [
                'module' => $module,
                'count' => $count,
                'total_in_module' => $totalInModule,
                'percentage' => $totalInModule > 0 ? round(($count / $totalInModule) * 100, 2) : 0,
                'permissions' => $modulePermissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'display_name' => $permission->display_name,
                    ];
                })->toArray(),
            ];
        })->values()->toArray();

        // 計算總體統計
        $totalPermissions = $permissions->count();
        $totalSystemPermissions = Permission::count();
        $coveragePercentage = $totalSystemPermissions > 0 
            ? round(($totalPermissions / $totalSystemPermissions) * 100, 2) 
            : 0;

        return [
            'summary' => [
                'total_permissions' => $totalPermissions,
                'total_system_permissions' => $totalSystemPermissions,
                'coverage_percentage' => $coveragePercentage,
                'modules_count' => $byModule->count(),
            ],
            'by_module' => $distribution,
            'chart_data' => [
                'labels' => $byModule->keys()->toArray(),
                'data' => $byModule->map->count()->values()->toArray(),
                'colors' => $this->generateChartColors($byModule->count()),
            ],
            'calculated_at' => now(),
        ];
    }

    /**
     * 計算使用趨勢
     *
     * @param int $days
     * @return array
     */
    private function calculateUsageTrends(int $days): array
    {
        $startDate = now()->subDays($days);
        
        // 每日新增角色數量
        $dailyRoleCreations = Role::where('created_at', '>=', $startDate)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->date => $item->count];
            })
            ->toArray();

        // 每日角色指派數量（如果有審計日誌）
        $dailyAssignments = $this->getDailyRoleAssignments($startDate);

        // 生成完整的日期範圍
        $dateRange = collect();
        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dateRange->push($date);
        }
        $dateRange = $dateRange->reverse();

        // 填充缺失的日期
        $creationTrend = $dateRange->map(function ($date) use ($dailyRoleCreations) {
            return [
                'date' => $date,
                'count' => $dailyRoleCreations[$date] ?? 0,
            ];
        })->toArray();

        $assignmentTrend = $dateRange->map(function ($date) use ($dailyAssignments) {
            return [
                'date' => $date,
                'count' => $dailyAssignments[$date] ?? 0,
            ];
        })->toArray();

        return [
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d'),
                'days' => $days,
            ],
            'role_creations' => [
                'total' => array_sum($dailyRoleCreations),
                'daily' => $creationTrend,
                'chart_data' => [
                    'labels' => $dateRange->toArray(),
                    'data' => array_column($creationTrend, 'count'),
                ],
            ],
            'role_assignments' => [
                'total' => array_sum($dailyAssignments),
                'daily' => $assignmentTrend,
                'chart_data' => [
                    'labels' => $dateRange->toArray(),
                    'data' => array_column($assignmentTrend, 'count'),
                ],
            ],
            'calculated_at' => now(),
        ];
    }

    /**
     * 取得最近的權限變更記錄
     *
     * @param Role $role
     * @param int $limit
     * @return array
     */
    private function getRecentPermissionChanges(Role $role, int $limit = 10): array
    {
        // 這裡應該查詢審計日誌表，如果沒有則返回空陣列
        // 假設有一個 audit_logs 表記錄權限變更
        
        try {
            return DB::table('audit_logs')
                ->where('auditable_type', Role::class)
                ->where('auditable_id', $role->id)
                ->where('event', 'updated')
                ->whereJsonContains('old_values', ['permissions'])
                ->orWhereJsonContains('new_values', ['permissions'])
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get()
                ->map(function ($log) {
                    return [
                        'event' => $log->event,
                        'user_id' => $log->user_id,
                        'created_at' => $log->created_at,
                        'changes' => json_decode($log->new_values, true),
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            // 如果沒有審計日誌表，返回空陣列
            return [];
        }
    }

    /**
     * 取得最近的使用者指派記錄
     *
     * @param Role $role
     * @param int $limit
     * @return array
     */
    private function getRecentUserAssignments(Role $role, int $limit = 5): array
    {
        return $role->users()
            ->select('users.id', 'users.name', 'users.username', 'user_roles.created_at')
            ->orderByDesc('user_roles.created_at')
            ->limit($limit)
            ->get()
            ->map(function ($user) {
                return [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'name' => $user->name,
                    'assigned_at' => $user->pivot->created_at,
                ];
            })
            ->toArray();
    }

    /**
     * 計算最大層級深度
     *
     * @return int
     */
    private function calculateMaxHierarchyDepth(): int
    {
        $maxDepth = 0;
        $rootRoles = Role::whereNull('parent_id')->get();
        
        foreach ($rootRoles as $role) {
            $depth = $this->calculateRoleDepth($role);
            $maxDepth = max($maxDepth, $depth);
        }
        
        return $maxDepth;
    }

    /**
     * 計算角色深度
     *
     * @param Role $role
     * @param int $currentDepth
     * @return int
     */
    private function calculateRoleDepth(Role $role, int $currentDepth = 0): int
    {
        $maxChildDepth = $currentDepth;
        
        foreach ($role->children as $child) {
            $childDepth = $this->calculateRoleDepth($child, $currentDepth + 1);
            $maxChildDepth = max($maxChildDepth, $childDepth);
        }
        
        return $maxChildDepth;
    }

    /**
     * 計算平均深度
     *
     * @return float
     */
    private function calculateAverageDepth(): float
    {
        $roles = Role::all();
        $totalDepth = $roles->sum(function ($role) {
            return $role->getDepth();
        });
        
        return $roles->count() > 0 ? round($totalDepth / $roles->count(), 2) : 0;
    }

    /**
     * 計算權限覆蓋率
     *
     * @return array
     */
    private function calculatePermissionCoverage(): array
    {
        $totalPermissions = Permission::count();
        $usedPermissions = Permission::whereHas('roles')->count();
        $unusedPermissions = $totalPermissions - $usedPermissions;
        
        return [
            'total' => $totalPermissions,
            'used' => $usedPermissions,
            'unused' => $unusedPermissions,
            'usage_percentage' => $totalPermissions > 0 
                ? round(($usedPermissions / $totalPermissions) * 100, 2) 
                : 0,
        ];
    }

    /**
     * 取得每日角色指派數量
     *
     * @param \Carbon\Carbon $startDate
     * @return array
     */
    private function getDailyRoleAssignments(\Carbon\Carbon $startDate): array
    {
        try {
            return DB::table('user_roles')
                ->where('created_at', '>=', $startDate)
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->date => $item->count];
                })
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * 生成圖表顏色
     *
     * @param int $count
     * @return array
     */
    private function generateChartColors(int $count): array
    {
        $baseColors = [
            '#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6',
            '#06B6D4', '#F97316', '#84CC16', '#EC4899', '#6B7280'
        ];
        
        $colors = [];
        for ($i = 0; $i < $count; $i++) {
            $colors[] = $baseColors[$i % count($baseColors)];
        }
        
        return $colors;
    }
}