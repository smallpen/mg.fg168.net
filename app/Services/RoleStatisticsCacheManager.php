<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\Cache;

/**
 * 角色統計快取管理服務
 * 
 * 管理角色統計相關的快取失效和更新
 */
class RoleStatisticsCacheManager
{
    /**
     * 快取鍵前綴
     */
    private const CACHE_PREFIX = 'role_stats';

    /**
     * 角色更新時清除相關快取
     *
     * @param Role $role
     * @return void
     */
    public function handleRoleUpdated(Role $role): void
    {
        // 清除該角色的統計快取
        $this->clearRoleCache($role);
        
        // 清除系統統計快取
        $this->clearSystemCache();
        
        // 如果角色有父子關係，也清除相關角色的快取
        if ($role->parent) {
            $this->clearRoleCache($role->parent);
        }
        
        foreach ($role->children as $child) {
            $this->clearRoleCache($child);
        }
    }

    /**
     * 角色權限更新時清除相關快取
     *
     * @param Role $role
     * @return void
     */
    public function handleRolePermissionsUpdated(Role $role): void
    {
        // 清除該角色的統計快取
        $this->clearRoleCache($role);
        
        // 清除系統統計快取
        $this->clearSystemCache();
        
        // 清除權限分佈快取
        $this->clearPermissionDistributionCache($role);
        
        // 如果有子角色，也需要清除（因為權限繼承）
        foreach ($role->children as $child) {
            $this->clearRoleCache($child);
            $this->clearPermissionDistributionCache($child);
        }
    }

    /**
     * 角色建立時清除相關快取
     *
     * @param Role $role
     * @return void
     */
    public function handleRoleCreated(Role $role): void
    {
        // 清除系統統計快取
        $this->clearSystemCache();
        
        // 如果有父角色，清除父角色快取
        if ($role->parent) {
            $this->clearRoleCache($role->parent);
        }
    }

    /**
     * 角色刪除時清除相關快取
     *
     * @param Role $role
     * @return void
     */
    public function handleRoleDeleted(Role $role): void
    {
        // 清除該角色的統計快取
        $this->clearRoleCache($role);
        
        // 清除系統統計快取
        $this->clearSystemCache();
        
        // 如果有父角色，清除父角色快取
        if ($role->parent) {
            $this->clearRoleCache($role->parent);
        }
    }

    /**
     * 使用者角色指派更新時清除相關快取
     *
     * @param int $userId
     * @param array $roleIds
     * @return void
     */
    public function handleUserRolesUpdated(int $userId, array $roleIds): void
    {
        // 清除系統統計快取
        $this->clearSystemCache();
        
        // 清除相關角色的快取
        foreach ($roleIds as $roleId) {
            $role = Role::find($roleId);
            if ($role) {
                $this->clearRoleCache($role);
            }
        }
    }

    /**
     * 權限更新時清除相關快取
     *
     * @param Permission $permission
     * @return void
     */
    public function handlePermissionUpdated(Permission $permission): void
    {
        // 清除系統統計快取
        $this->clearSystemCache();
        
        // 清除所有權限分佈快取
        $this->clearAllPermissionDistributionCache();
        
        // 清除使用此權限的角色快取
        foreach ($permission->roles as $role) {
            $this->clearRoleCache($role);
        }
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
     * 清除系統統計快取
     *
     * @return void
     */
    public function clearSystemCache(): void
    {
        $keys = [
            self::CACHE_PREFIX . '_system',
            self::CACHE_PREFIX . '_permission_dist_system',
        ];
        
        // 清除不同天數的使用趨勢快取
        $trendDays = [7, 30, 90];
        foreach ($trendDays as $days) {
            $keys[] = self::CACHE_PREFIX . "_usage_trends_{$days}";
        }
        
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * 清除權限分佈快取
     *
     * @param Role|null $role
     * @return void
     */
    public function clearPermissionDistributionCache(?Role $role = null): void
    {
        if ($role) {
            Cache::forget(self::CACHE_PREFIX . "_permission_dist_role_{$role->id}");
        } else {
            Cache::forget(self::CACHE_PREFIX . '_permission_dist_system');
        }
    }

    /**
     * 清除所有權限分佈快取
     *
     * @return void
     */
    public function clearAllPermissionDistributionCache(): void
    {
        // 清除系統權限分佈快取
        Cache::forget(self::CACHE_PREFIX . '_permission_dist_system');
        
        // 清除所有角色的權限分佈快取
        Role::all()->each(function ($role) {
            Cache::forget(self::CACHE_PREFIX . "_permission_dist_role_{$role->id}");
        });
    }

    /**
     * 清除所有統計快取
     *
     * @return void
     */
    public function clearAllCache(): void
    {
        // 清除系統快取
        $this->clearSystemCache();
        
        // 清除所有角色快取
        Role::all()->each(function ($role) {
            $this->clearRoleCache($role);
        });
        
        // 清除所有權限分佈快取
        $this->clearAllPermissionDistributionCache();
    }

    /**
     * 預熱快取
     *
     * @return void
     */
    public function warmUpCache(): void
    {
        $statisticsService = app(RoleStatisticsService::class);
        
        try {
            // 預熱系統統計
            $statisticsService->getSystemRoleStatistics(false);
            $statisticsService->getPermissionDistribution(null, false);
            $statisticsService->getRoleUsageTrends(30, false);
            
            // 預熱前幾個最常用角色的統計
            $topRoles = Role::withCount('users')
                ->orderByDesc('users_count')
                ->limit(5)
                ->get();
                
            foreach ($topRoles as $role) {
                $statisticsService->getRoleStatistics($role, false);
                $statisticsService->getPermissionDistribution($role, false);
            }
            
        } catch (\Exception $e) {
            // 記錄錯誤但不拋出異常
            \Log::warning('角色統計快取預熱失敗: ' . $e->getMessage());
        }
    }

    /**
     * 取得快取狀態資訊
     *
     * @return array
     */
    public function getCacheStatus(): array
    {
        $systemCacheKeys = [
            'system' => self::CACHE_PREFIX . '_system',
            'permission_dist' => self::CACHE_PREFIX . '_permission_dist_system',
            'usage_trends_30' => self::CACHE_PREFIX . '_usage_trends_30',
        ];
        
        $status = [
            'system_cache' => [],
            'role_cache_count' => 0,
            'total_cache_size' => 0,
        ];
        
        // 檢查系統快取狀態
        foreach ($systemCacheKeys as $name => $key) {
            $status['system_cache'][$name] = Cache::has($key);
        }
        
        // 統計角色快取數量
        $roleCount = Role::count();
        $cachedRoleCount = 0;
        
        Role::all()->each(function ($role) use (&$cachedRoleCount) {
            $roleKey = self::CACHE_PREFIX . "_role_{$role->id}";
            if (Cache::has($roleKey)) {
                $cachedRoleCount++;
            }
        });
        
        $status['role_cache_count'] = $cachedRoleCount;
        $status['total_roles'] = $roleCount;
        $status['cache_coverage'] = $roleCount > 0 ? round(($cachedRoleCount / $roleCount) * 100, 2) : 0;
        
        return $status;
    }
}