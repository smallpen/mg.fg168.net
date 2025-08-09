<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * 儀表板服務類別
 * 
 * 處理儀表板相關的業務邏輯和統計資料
 */
class DashboardService
{
    /**
     * 快取鍵前綴
     * 
     * @var string
     */
    protected $cachePrefix = 'dashboard';

    /**
     * 預設快取時間（分鐘）
     * 
     * @var int
     */
    protected $defaultCacheMinutes = 10;

    /**
     * 取得完整的儀表板統計資料
     * 
     * @param bool $forceRefresh 是否強制重新整理快取
     * @return array
     */
    public function getStats(bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey('stats');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, $this->defaultCacheMinutes * 60, function () {
            return [
                'total_users' => $this->getTotalUsers(),
                'active_users' => $this->getActiveUsers(),
                'total_roles' => $this->getTotalRoles(),
                'total_permissions' => $this->getTotalPermissions(),
                'online_users' => $this->getOnlineUsers(),
                'recent_users' => $this->getRecentUsers(),
                'user_growth' => $this->getUserGrowth(),
                'role_distribution' => $this->getRoleDistribution(),
                'user_activity' => $this->getUserActivity(),
                'system_health' => $this->getSystemHealth(),
                'quick_actions_stats' => $this->getQuickActionsStats(),
                'recent_activity_summary' => $this->getRecentActivitySummary(),
            ];
        });
    }

    /**
     * 取得使用者總數
     * 
     * @return int
     */
    public function getTotalUsers(): int
    {
        return Cache::remember(
            $this->getCacheKey('total_users'),
            $this->defaultCacheMinutes * 60,
            fn() => User::count()
        );
    }

    /**
     * 取得啟用的使用者數量
     * 
     * @return int
     */
    public function getActiveUsers(): int
    {
        return Cache::remember(
            $this->getCacheKey('active_users'),
            $this->defaultCacheMinutes * 60,
            fn() => User::where('is_active', true)->count()
        );
    }

    /**
     * 取得角色總數
     * 
     * @return int
     */
    public function getTotalRoles(): int
    {
        return Cache::remember(
            $this->getCacheKey('total_roles'),
            $this->defaultCacheMinutes * 60,
            fn() => Role::count()
        );
    }

    /**
     * 取得權限總數
     * 
     * @return int
     */
    public function getTotalPermissions(): int
    {
        return Cache::remember(
            $this->getCacheKey('total_permissions'),
            $this->defaultCacheMinutes * 60,
            fn() => Permission::count()
        );
    }

    /**
     * 取得線上使用者數量
     * 
     * @return int
     */
    public function getOnlineUsers(): int
    {
        return Cache::remember(
            $this->getCacheKey('online_users'),
            5 * 60, // 5 分鐘快取
            function () {
                try {
                    // 如果使用 Redis session，從 Redis 獲取線上使用者數量
                    if (config('session.driver') === 'redis') {
                        $redis = app('redis')->connection();
                        $sessionKeys = $redis->keys(config('session.cookie') . '*');
                        
                        $onlineUsers = collect($sessionKeys)->map(function ($key) use ($redis) {
                            $sessionData = $redis->get($key);
                            if ($sessionData) {
                                $data = unserialize($sessionData);
                                return isset($data['login_web_' . sha1(config('app.name'))]) ? 
                                    $data['login_web_' . sha1(config('app.name'))] : null;
                            }
                            return null;
                        })->filter()->unique()->count();
                        
                        return $onlineUsers;
                    }
                    
                    // 如果使用資料庫 session，查詢 sessions 表
                    return DB::table('sessions')
                        ->where('last_activity', '>=', now()->subMinutes(15)->timestamp)
                        ->whereNotNull('user_id')
                        ->distinct('user_id')
                        ->count();
                        
                } catch (\Exception $e) {
                    // 如果發生錯誤，記錄日誌並回傳 0
                    logger()->warning('無法取得線上使用者數量', [
                        'error' => $e->getMessage(),
                        'session_driver' => config('session.driver')
                    ]);
                    return 0;
                }
            }
        );
    }

    /**
     * 取得最近註冊的使用者數量
     * 
     * @param int $days 天數，預設 7 天
     * @return int
     */
    public function getRecentUsers(int $days = 7): int
    {
        return Cache::remember(
            $this->getCacheKey("recent_users_{$days}"),
            $this->defaultCacheMinutes * 60,
            fn() => User::where('created_at', '>=', now()->subDays($days))->count()
        );
    }

    /**
     * 取得使用者成長趨勢
     * 
     * @return array
     */
    public function getUserGrowth(): array
    {
        return Cache::remember(
            $this->getCacheKey('user_growth'),
            $this->defaultCacheMinutes * 60,
            function () {
                $currentMonth = User::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count();

                $lastMonth = User::whereMonth('created_at', now()->subMonth()->month)
                    ->whereYear('created_at', now()->subMonth()->year)
                    ->count();

                $growth = $lastMonth > 0 ? (($currentMonth - $lastMonth) / $lastMonth) * 100 : 0;

                return [
                    'current' => $currentMonth,
                    'previous' => $lastMonth,
                    'percentage' => round($growth, 1),
                    'trend' => $growth >= 0 ? 'up' : 'down'
                ];
            }
        );
    }

    /**
     * 取得角色分佈統計
     * 
     * @return array
     */
    public function getRoleDistribution(): array
    {
        return Cache::remember(
            $this->getCacheKey('role_distribution'),
            $this->defaultCacheMinutes * 60,
            function () {
                $totalUsers = $this->getTotalUsers();
                
                return Role::withCount('users')
                    ->get()
                    ->map(function ($role) use ($totalUsers) {
                        return [
                            'name' => $role->display_name,
                            'count' => $role->users_count,
                            'percentage' => $totalUsers > 0 
                                ? round(($role->users_count / $totalUsers) * 100, 1)
                                : 0
                        ];
                    })
                    ->sortByDesc('count')
                    ->values()
                    ->toArray();
            }
        );
    }

    /**
     * 取得使用者活動統計
     * 
     * @return array
     */
    public function getUserActivity(): array
    {
        return Cache::remember(
            $this->getCacheKey('user_activity'),
            $this->defaultCacheMinutes * 60,
            function () {
                $last7Days = collect();
                
                for ($i = 6; $i >= 0; $i--) {
                    $date = now()->subDays($i);
                    $count = User::whereDate('created_at', $date)->count();
                    
                    $last7Days->push([
                        'date' => $date->format('Y-m-d'),
                        'label' => $date->format('m/d'),
                        'count' => $count
                    ]);
                }

                return [
                    'daily_registrations' => $last7Days->toArray(),
                    'peak_day' => $last7Days->sortByDesc('count')->first(),
                    'total_week' => $last7Days->sum('count')
                ];
            }
        );
    }

    /**
     * 取得系統健康狀態
     * 
     * @return array
     */
    public function getSystemHealth(): array
    {
        return Cache::remember(
            $this->getCacheKey('system_health'),
            5 * 60, // 5 分鐘快取
            function () {
                $health = [
                    'database' => $this->checkDatabaseHealth(),
                    'cache' => $this->checkCacheHealth(),
                    'storage' => $this->checkStorageHealth(),
                ];

                $overallHealth = collect($health)->every(fn($status) => $status === 'healthy') 
                    ? 'healthy' 
                    : 'warning';

                return array_merge($health, ['overall' => $overallHealth]);
            }
        );
    }

    /**
     * 檢查資料庫健康狀態
     * 
     * @return string
     */
    protected function checkDatabaseHealth(): string
    {
        try {
            DB::connection()->getPdo();
            return 'healthy';
        } catch (\Exception $e) {
            return 'error';
        }
    }

    /**
     * 檢查快取健康狀態
     * 
     * @return string
     */
    protected function checkCacheHealth(): string
    {
        try {
            $testKey = 'health_check_' . time();
            Cache::put($testKey, 'test', 60);
            $result = Cache::get($testKey);
            Cache::forget($testKey);
            
            return $result === 'test' ? 'healthy' : 'warning';
        } catch (\Exception $e) {
            return 'error';
        }
    }

    /**
     * 檢查儲存空間健康狀態
     * 
     * @return string
     */
    protected function checkStorageHealth(): string
    {
        try {
            $storagePath = storage_path();
            $freeBytes = disk_free_space($storagePath);
            $totalBytes = disk_total_space($storagePath);
            
            if ($freeBytes === false || $totalBytes === false) {
                return 'warning';
            }
            
            $usagePercentage = (($totalBytes - $freeBytes) / $totalBytes) * 100;
            
            return $usagePercentage > 90 ? 'warning' : 'healthy';
        } catch (\Exception $e) {
            return 'error';
        }
    }

    /**
     * 清除所有儀表板快取
     * 
     * @return void
     */
    public function clearCache(): void
    {
        $keys = [
            'stats',
            'total_users',
            'active_users',
            'total_roles',
            'total_permissions',
            'online_users',
            'recent_users_7',
            'user_growth',
            'role_distribution',
            'user_activity',
            'system_health',
            'quick_actions_stats',
            'recent_activity_summary'
        ];

        foreach ($keys as $key) {
            Cache::forget($this->getCacheKey($key));
        }

        // 清除所有使用者權限摘要快取（這裡簡化處理）
        $userIds = User::pluck('id');
        foreach ($userIds as $userId) {
            Cache::forget($this->getCacheKey("user_permissions_summary_{$userId}"));
        }
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

    /**
     * 取得快速操作統計資料
     * 
     * @return array
     */
    public function getQuickActionsStats(): array
    {
        return Cache::remember(
            $this->getCacheKey('quick_actions_stats'),
            $this->defaultCacheMinutes * 60,
            function () {
                // 統計最常使用的快速操作
                $quickActionUsage = DB::table('activities')
                    ->where('type', 'quick_action')
                    ->where('created_at', '>=', now()->subDays(30))
                    ->selectRaw('JSON_EXTRACT(properties, "$.route") as route, COUNT(*) as usage_count')
                    ->groupBy('route')
                    ->orderBy('usage_count', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(function ($item) {
                        return [
                            'route' => trim($item->route, '"'),
                            'usage_count' => $item->usage_count
                        ];
                    });

                return [
                    'total_quick_actions_today' => DB::table('activities')
                        ->where('type', 'quick_action')
                        ->whereDate('created_at', today())
                        ->count(),
                    'total_quick_actions_week' => DB::table('activities')
                        ->where('type', 'quick_action')
                        ->where('created_at', '>=', now()->subDays(7))
                        ->count(),
                    'most_used_actions' => $quickActionUsage->toArray(),
                    'unique_users_using_actions' => DB::table('activities')
                        ->where('type', 'quick_action')
                        ->where('created_at', '>=', now()->subDays(7))
                        ->distinct('user_id')
                        ->count(),
                ];
            }
        );
    }

    /**
     * 取得最近活動摘要
     * 
     * @return array
     */
    public function getRecentActivitySummary(): array
    {
        return Cache::remember(
            $this->getCacheKey('recent_activity_summary'),
            $this->defaultCacheMinutes * 60,
            function () {
                $activityService = app(\App\Services\ActivityService::class);
                $stats = $activityService->getActivityStats();

                return [
                    'today_activities' => $stats['today_activities'] ?? 0,
                    'week_activities' => $stats['week_activities'] ?? 0,
                    'active_users_today' => $stats['active_users_today'] ?? 0,
                    'most_active_user' => $stats['most_active_user'] ?? null,
                    'top_activity_types' => collect($stats['activity_by_type'] ?? [])
                        ->sortDesc()
                        ->take(3)
                        ->toArray(),
                    'top_modules' => collect($stats['activity_by_module'] ?? [])
                        ->sortDesc()
                        ->take(3)
                        ->toArray(),
                ];
            }
        );
    }

    /**
     * 取得使用者權限摘要（用於快速操作顯示）
     * 
     * @param User $user
     * @return array
     */
    public function getUserPermissionsSummary(User $user): array
    {
        return Cache::remember(
            $this->getCacheKey("user_permissions_summary_{$user->id}"),
            $this->defaultCacheMinutes * 60,
            function () use ($user) {
                $permissionService = app(\App\Services\PermissionService::class);
                
                return [
                    'can_manage_users' => $permissionService->canAccessModule($user, 'users'),
                    'can_manage_roles' => $permissionService->canAccessModule($user, 'roles'),
                    'can_view_logs' => $permissionService->hasPermission($user, 'system.logs'),
                    'can_manage_system' => $permissionService->hasPermission($user, 'system.settings'),
                    'can_export_data' => $permissionService->hasPermission($user, 'data.export'),
                    'can_backup_data' => $permissionService->hasPermission($user, 'data.backup'),
                    'available_modules' => $this->getAvailableModules($user, $permissionService),
                ];
            }
        );
    }

    /**
     * 取得使用者可存取的模組列表
     * 
     * @param User $user
     * @param \App\Services\PermissionService $permissionService
     * @return array
     */
    protected function getAvailableModules(User $user, $permissionService): array
    {
        $modules = ['users', 'roles', 'permissions', 'system', 'dashboard'];
        $availableModules = [];

        foreach ($modules as $module) {
            if ($permissionService->canAccessModule($user, $module)) {
                $availableModules[] = $module;
            }
        }

        return $availableModules;
    }

    /**
     * 清除使用者權限摘要快取
     * 
     * @param User $user
     * @return void
     */
    public function clearUserPermissionsSummaryCache(User $user): void
    {
        Cache::forget($this->getCacheKey("user_permissions_summary_{$user->id}"));
    }

    /**
     * 設定快取時間
     * 
     * @param int $minutes
     * @return self
     */
    public function setCacheMinutes(int $minutes): self
    {
        $this->defaultCacheMinutes = $minutes;
        return $this;
    }
}