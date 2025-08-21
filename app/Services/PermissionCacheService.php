<?php

namespace App\Services;

use App\Models\Permission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 權限快取服務
 */
class PermissionCacheService
{
    /**
     * 快取標籤
     */
    const CACHE_TAG_PERMISSIONS = 'permissions';

    /**
     * 快取時間（秒）
     */
    const CACHE_TTL_SHORT = 900;    // 15 分鐘
    const CACHE_TTL_MEDIUM = 3600;  // 1 小時
    const CACHE_TTL_LONG = 7200;    // 2 小時
    const CACHE_TTL_EXTENDED = 86400; // 24 小時

    /**
     * 取得所有權限（快取版本）
     */
    public function getAllPermissions(): Collection
    {
        return Cache::tags([self::CACHE_TAG_PERMISSIONS])
                   ->remember('all_permissions', self::CACHE_TTL_MEDIUM, function () {
                       return Permission::orderBy('module')->orderBy('name')->get();
                   });
    }

    /**
     * 預熱權限快取
     */
    public function warmupCache(): void
    {
        Log::info('開始預熱權限快取');
        $this->getAllPermissions();
        Log::info('權限快取預熱完成');
    }

    /**
     * 清除權限相關快取
     */
    public function clearPermissionCache(?int $permissionId = null): void
    {
        Cache::tags([self::CACHE_TAG_PERMISSIONS])->flush();
    }

    /**
     * 取得權限依賴關係（快取版本）
     */
    public function getPermissionDependencies(int $permissionId): Collection
    {
        return Cache::tags([self::CACHE_TAG_PERMISSIONS])
                   ->remember("permission_dependencies_{$permissionId}", self::CACHE_TTL_MEDIUM, function () use ($permissionId) {
                       return Permission::find($permissionId)?->dependencies ?? collect();
                   });
    }

    /**
     * 取得權限被依賴關係（快取版本）
     */
    public function getPermissionDependents(int $permissionId): Collection
    {
        return Cache::tags([self::CACHE_TAG_PERMISSIONS])
                   ->remember("permission_dependents_{$permissionId}", self::CACHE_TTL_MEDIUM, function () use ($permissionId) {
                       return Permission::find($permissionId)?->dependents ?? collect();
                   });
    }

    /**
     * 批量取得權限使用統計（快取版本）
     */
    public function getBatchPermissionUsage(array $permissionIds): array
    {
        if (empty($permissionIds)) {
            return [];
        }

        sort($permissionIds);
        $cacheKey = 'batch_permission_usage_' . md5(implode(',', $permissionIds));
        
        return Cache::tags([self::CACHE_TAG_PERMISSIONS])
                   ->remember($cacheKey, self::CACHE_TTL_MEDIUM, function () use ($permissionIds) {
                       return Permission::batchCheckUsageStatus($permissionIds);
                   });
    }

    /**
     * 取得權限樹狀結構（快取版本）
     */
    public function getPermissionTree(?string $module = null): array
    {
        $cacheKey = $module ? "permission_tree_{$module}" : 'permission_tree_all';
        
        return Cache::tags([self::CACHE_TAG_PERMISSIONS])
                   ->remember($cacheKey, self::CACHE_TTL_MEDIUM, function () use ($module) {
                       $query = Permission::select(['id', 'name', 'display_name', 'module', 'type'])
                                        ->with(['dependencies:id,name,display_name,module', 'dependents:id,name,display_name,module'])
                                        ->withCount('roles');
                       
                       if ($module) {
                           $query->where('module', $module);
                       }
                       
                       return $query->orderBy('module')
                                   ->orderBy('type')
                                   ->orderBy('name')
                                   ->get()
                                   ->groupBy('module')
                                   ->map(function ($permissions, $moduleName) {
                                       return [
                                           'module' => $moduleName,
                                           'total_permissions' => $permissions->count(),
                                           'used_permissions' => $permissions->where('roles_count', '>', 0)->count(),
                                           'permissions' => $permissions->groupBy('type')->map(function ($typePermissions, $type) {
                                               return [
                                                   'type' => $type,
                                                   'count' => $typePermissions->count(),
                                                   'permissions' => $typePermissions->toArray(),
                                               ];
                                           })->values()->toArray(),
                                       ];
                                   })
                                   ->toArray();
                   });
    }

    /**
     * 取得權限統計摘要（快取版本）
     */
    public function getPermissionStatsSummary(): array
    {
        return Cache::tags([self::CACHE_TAG_PERMISSIONS])
                   ->remember('permission_stats_summary', self::CACHE_TTL_MEDIUM, function () {
                       return Permission::getStatsSummary();
                   });
    }

    /**
     * 預熱特定權限的快取
     */
    public function warmupPermissionCache(int $permissionId): void
    {
        Log::info("開始預熱權限 {$permissionId} 的快取");
        
        // 預熱基本資訊
        Permission::find($permissionId);
        
        // 預熱依賴關係
        $this->getPermissionDependencies($permissionId);
        $this->getPermissionDependents($permissionId);
        
        // 預熱使用統計
        $this->getBatchPermissionUsage([$permissionId]);
        
        Log::info("權限 {$permissionId} 快取預熱完成");
    }

    /**
     * 批量預熱權限快取
     */
    public function batchWarmupCache(array $permissionIds, int $batchSize = 50): void
    {
        Log::info('開始批量預熱權限快取', ['count' => count($permissionIds)]);
        
        $chunks = array_chunk($permissionIds, $batchSize);
        
        foreach ($chunks as $chunk) {
            // 批量載入基本資訊
            Permission::whereIn('id', $chunk)
                     ->with(['dependencies', 'dependents'])
                     ->withCount('roles')
                     ->get();
            
            // 批量載入使用統計
            $this->getBatchPermissionUsage($chunk);
            
            // 為每個權限預熱依賴關係快取
            foreach ($chunk as $permissionId) {
                $this->getPermissionDependencies($permissionId);
                $this->getPermissionDependents($permissionId);
            }
        }
        
        Log::info('批量預熱權限快取完成');
    }

    /**
     * 清除使用者權限快取
     */
    public function clearUserPermissionCache(?int $userId = null): void
    {
        if ($userId) {
            Cache::forget("user_permissions_{$userId}");
            Cache::forget("user_permission_names_{$userId}");
            Cache::forget("user_effective_permissions_{$userId}");
        } else {
            // 清除所有使用者權限快取（使用標籤）
            Cache::tags(['user_permissions'])->flush();
        }
    }

    /**
     * 清除角色權限快取
     */
    public function clearRolePermissionCache(?int $roleId = null): void
    {
        if ($roleId) {
            Cache::forget("role_permissions_{$roleId}");
            Cache::forget("role_permission_names_{$roleId}");
            Cache::forget("role_effective_permissions_{$roleId}");
        } else {
            // 清除所有角色權限快取（使用標籤）
            Cache::tags(['role_permissions'])->flush();
        }
    }

    /**
     * 智慧快取清除（只清除相關的快取）
     */
    public function smartClearCache(int $permissionId): void
    {
        // 清除特定權限的快取
        Cache::forget("permission_dependencies_{$permissionId}");
        Cache::forget("permission_dependents_{$permissionId}");
        
        // 清除包含此權限的批量快取（這裡簡化處理）
        Cache::tags([self::CACHE_TAG_PERMISSIONS])->flush();
        
        // 清除統計快取
        Cache::forget('permission_stats_summary');
        
        // 清除樹狀結構快取
        $permission = Permission::find($permissionId);
        if ($permission) {
            Cache::forget("permission_tree_{$permission->module}");
            Cache::forget('permission_tree_all');
        }
    }

    /**
     * 取得快取統計資訊
     */
    public function getCacheStats(): array
    {
        // 這裡需要根據實際的快取驅動實作
        // 目前返回模擬統計資料
        return [
            'total_keys' => 150,
            'memory_usage' => '25MB',
            'hit_rate' => 0.85,
            'miss_rate' => 0.15,
            'expired_keys' => 12,
            'cache_tags' => [
                self::CACHE_TAG_PERMISSIONS => 45,
                'user_permissions' => 30,
                'role_permissions' => 25,
            ],
        ];
    }

    /**
     * 清理過期快取
     */
    public function cleanupExpiredCache(): int
    {
        $cleaned = 0;
        
        // 清理標記為未使用但實際已被使用的權限快取
        $markedUnused = Cache::get('marked_unused_permissions', []);
        if (!empty($markedUnused)) {
            $actuallyUsed = Permission::whereIn('id', $markedUnused)
                                    ->has('roles')
                                    ->pluck('id')
                                    ->toArray();
            
            if (!empty($actuallyUsed)) {
                $stillUnused = array_diff($markedUnused, $actuallyUsed);
                Cache::put('marked_unused_permissions', $stillUnused, now()->addDays(30));
                $cleaned += count($actuallyUsed);
            }
        }
        
        return $cleaned;
    }

    /**
     * 多層級快取策略
     * 
     * @param string $key 快取鍵
     * @param callable $callback 資料載入回調
     * @param int $ttl 快取時間
     * @param string $level 快取層級 (memory, redis, database)
     * @return mixed
     */
    public function multiLevelCache(string $key, callable $callback, int $ttl = self::CACHE_TTL_MEDIUM, string $level = 'redis')
    {
        // 第一層：記憶體快取（最快）
        static $memoryCache = [];
        if (isset($memoryCache[$key])) {
            return $memoryCache[$key];
        }

        // 第二層：Redis 快取
        $value = Cache::remember($key, $ttl, $callback);
        
        // 儲存到記憶體快取（限制大小避免記憶體溢出）
        if (count($memoryCache) < 100) {
            $memoryCache[$key] = $value;
        }

        return $value;
    }

    /**
     * 智慧快取預熱
     * 根據使用頻率和重要性預熱快取
     */
    public function intelligentWarmup(): void
    {
        Log::info('開始智慧快取預熱');

        // 預熱高頻使用的權限資料
        $this->warmupHighFrequencyPermissions();
        
        // 預熱系統核心權限
        $this->warmupSystemPermissions();
        
        // 預熱權限統計資料
        $this->warmupStatisticsCache();
        
        // 預熱依賴關係複雜的權限
        $this->warmupComplexDependencies();

        Log::info('智慧快取預熱完成');
    }

    /**
     * 預熱高頻使用權限
     */
    private function warmupHighFrequencyPermissions(): void
    {
        // 取得使用頻率最高的權限
        $highFrequencyPermissions = Permission::withCount('roles')
                                            ->having('roles_count', '>=', 5)
                                            ->orderBy('roles_count', 'desc')
                                            ->limit(50)
                                            ->pluck('id')
                                            ->toArray();

        if (!empty($highFrequencyPermissions)) {
            $this->batchWarmupCache($highFrequencyPermissions, 25);
        }
    }

    /**
     * 預熱系統權限
     */
    private function warmupSystemPermissions(): void
    {
        $systemPermissions = Permission::where(function ($query) {
                                        $query->where('module', 'system')
                                              ->orWhere('module', 'auth')
                                              ->orWhere('module', 'admin')
                                              ->orWhere('name', 'like', 'system.%')
                                              ->orWhere('name', 'like', 'auth.%')
                                              ->orWhere('name', 'like', 'admin.core%');
                                    })
                                    ->pluck('id')
                                    ->toArray();

        if (!empty($systemPermissions)) {
            $this->batchWarmupCache($systemPermissions, 20);
        }
    }

    /**
     * 預熱統計快取
     */
    private function warmupStatisticsCache(): void
    {
        $this->getPermissionStatsSummary();
        $this->getPermissionTree();
        
        // 預熱各模組的統計資料
        $modules = Permission::distinct()->pluck('module');
        foreach ($modules as $module) {
            $this->getPermissionTree($module);
        }
    }

    /**
     * 預熱複雜依賴關係
     */
    private function warmupComplexDependencies(): void
    {
        // 找出依賴關係複雜的權限（依賴數量 >= 3 或被依賴數量 >= 3）
        $complexPermissions = Permission::withCount(['dependencies', 'dependents'])
                                      ->having('dependencies_count', '>=', 3)
                                      ->orHaving('dependents_count', '>=', 3)
                                      ->pluck('id')
                                      ->toArray();

        foreach ($complexPermissions as $permissionId) {
            $this->getPermissionDependencies($permissionId);
            $this->getPermissionDependents($permissionId);
        }
    }

    /**
     * 快取效能監控
     */
    public function monitorCachePerformance(): array
    {
        $stats = [
            'cache_hits' => 0,
            'cache_misses' => 0,
            'cache_size' => 0,
            'memory_usage' => 0,
            'slow_queries' => [],
            'recommendations' => [],
        ];

        // 檢查快取命中率
        $hitRate = $this->calculateCacheHitRate();
        $stats['hit_rate'] = $hitRate;

        if ($hitRate < 0.8) {
            $stats['recommendations'][] = '快取命中率偏低，建議增加快取時間或預熱更多資料';
        }

        // 檢查記憶體使用量
        $memoryUsage = memory_get_usage(true);
        $stats['memory_usage'] = $memoryUsage;

        if ($memoryUsage > 128 * 1024 * 1024) { // 128MB
            $stats['recommendations'][] = '記憶體使用量較高，建議清理不必要的快取';
        }

        return $stats;
    }

    /**
     * 計算快取命中率
     */
    private function calculateCacheHitRate(): float
    {
        // 這裡需要根據實際的快取驅動實作
        // 目前返回模擬數據
        return 0.85;
    }

    /**
     * 自動快取優化
     */
    public function autoOptimizeCache(): array
    {
        $optimizations = [];

        // 清理過期快取
        $cleaned = $this->cleanupExpiredCache();
        if ($cleaned > 0) {
            $optimizations[] = "清理了 {$cleaned} 個過期快取項目";
        }

        // 檢查並預熱重要快取
        $performance = $this->monitorCachePerformance();
        if ($performance['hit_rate'] < 0.8) {
            $this->intelligentWarmup();
            $optimizations[] = '執行了智慧快取預熱';
        }

        // 壓縮大型快取項目
        $compressed = $this->compressLargeCacheItems();
        if ($compressed > 0) {
            $optimizations[] = "壓縮了 {$compressed} 個大型快取項目";
        }

        return $optimizations;
    }

    /**
     * 壓縮大型快取項目
     */
    private function compressLargeCacheItems(): int
    {
        // 這裡可以實作快取項目壓縮邏輯
        // 目前返回模擬數據
        return 0;
    }
}