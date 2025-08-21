<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 角色快取服務
 * 
 * 提供角色和權限相關的快取功能，提升系統效能
 */
class RoleCacheService
{
    /**
     * 快取鍵前綴
     */
    private const CACHE_PREFIX = 'role_management:';
    
    /**
     * 快取時間（秒）
     */
    private const CACHE_TTL = 3600; // 1小時
    private const PERMISSION_INHERITANCE_TTL = 1800; // 30分鐘
    private const ROLE_STATS_TTL = 900; // 15分鐘
    
    /**
     * 快取標籤
     */
    private const CACHE_TAGS = [
        'roles' => 'roles',
        'permissions' => 'permissions',
        'role_permissions' => 'role_permissions',
        'role_hierarchy' => 'role_hierarchy',
    ];

    /**
     * 取得角色的所有權限（包含繼承，帶快取）
     * 
     * @param Role $role
     * @return Collection
     */
    public function getRoleAllPermissions(Role $role): Collection
    {
        $cacheKey = $this->getCacheKey('role_all_permissions', $role->id);
        
        return Cache::tags([self::CACHE_TAGS['roles'], self::CACHE_TAGS['permissions']])
                   ->remember($cacheKey, self::PERMISSION_INHERITANCE_TTL, function () use ($role) {
                       return $this->calculateRoleAllPermissions($role);
                   });
    }

    /**
     * 取得角色的直接權限（帶快取）
     * 
     * @param Role $role
     * @return Collection
     */
    public function getRoleDirectPermissions(Role $role): Collection
    {
        $cacheKey = $this->getCacheKey('role_direct_permissions', $role->id);
        
        return Cache::tags([self::CACHE_TAGS['roles'], self::CACHE_TAGS['permissions']])
                   ->remember($cacheKey, self::CACHE_TTL, function () use ($role) {
                       return $role->permissions()->orderBy('module')->orderBy('name')->get();
                   });
    }

    /**
     * 取得角色的繼承權限（帶快取）
     * 
     * @param Role $role
     * @return Collection
     */
    public function getRoleInheritedPermissions(Role $role): Collection
    {
        $cacheKey = $this->getCacheKey('role_inherited_permissions', $role->id);
        
        return Cache::tags([self::CACHE_TAGS['roles'], self::CACHE_TAGS['permissions']])
                   ->remember($cacheKey, self::PERMISSION_INHERITANCE_TTL, function () use ($role) {
                       if (!$role->parent) {
                           return collect();
                       }
                       return $this->getRoleAllPermissions($role->parent);
                   });
    }

    /**
     * 取得角色層級結構（帶快取）
     * 
     * @return Collection
     */
    public function getRoleHierarchy(): Collection
    {
        $cacheKey = $this->getCacheKey('role_hierarchy');
        
        return Cache::tags([self::CACHE_TAGS['role_hierarchy']])
                   ->remember($cacheKey, self::CACHE_TTL, function () {
                       return Role::with(['children' => function ($query) {
                           $query->orderBy('name');
                       }])
                       ->whereNull('parent_id')
                       ->orderBy('name')
                       ->get();
                   });
    }

    /**
     * 取得權限按模組分組（帶快取）
     * 
     * @return Collection
     */
    public function getPermissionsByModule(): Collection
    {
        $cacheKey = $this->getCacheKey('permissions_by_module');
        
        return Cache::tags([self::CACHE_TAGS['permissions']])
                   ->remember($cacheKey, self::CACHE_TTL, function () {
                       return Permission::orderBy('module')
                                       ->orderBy('name')
                                       ->get()
                                       ->groupBy('module');
                   });
    }

    /**
     * 取得權限依賴關係圖（帶快取）
     * 
     * @return array
     */
    public function getPermissionDependencies(): array
    {
        $cacheKey = $this->getCacheKey('permission_dependencies');
        
        return Cache::tags([self::CACHE_TAGS['permissions']])
                   ->remember($cacheKey, self::CACHE_TTL, function () {
                       return $this->buildPermissionDependencyGraph();
                   });
    }

    /**
     * 取得角色統計資訊（帶快取）
     * 
     * @return array
     */
    public function getRoleStats(): array
    {
        $cacheKey = $this->getCacheKey('role_stats');
        
        return Cache::tags([self::CACHE_TAGS['roles']])
                   ->remember($cacheKey, self::ROLE_STATS_TTL, function () {
                       return $this->calculateRoleStats();
                   });
    }

    /**
     * 取得使用者的所有權限（包含角色繼承，帶快取）
     * 
     * @param int $userId
     * @return Collection
     */
    public function getUserAllPermissions(int $userId): Collection
    {
        $cacheKey = $this->getCacheKey('user_all_permissions', $userId);
        
        return Cache::tags([self::CACHE_TAGS['roles'], self::CACHE_TAGS['permissions']])
                   ->remember($cacheKey, self::PERMISSION_INHERITANCE_TTL, function () use ($userId) {
                       $userRoles = Role::whereHas('users', function ($query) use ($userId) {
                           $query->where('user_id', $userId);
                       })->get();

                       $allPermissions = collect();
                       foreach ($userRoles as $role) {
                           $rolePermissions = $this->getRoleAllPermissions($role);
                           $allPermissions = $allPermissions->merge($rolePermissions);
                       }

                       return $allPermissions->unique('id');
                   });
    }

    /**
     * 檢查使用者是否擁有特定權限（帶快取）
     * 
     * @param int $userId
     * @param string $permission
     * @return bool
     */
    public function userHasPermission(int $userId, string $permission): bool
    {
        $cacheKey = $this->getCacheKey('user_has_permission', $userId, $permission);
        
        return Cache::tags([self::CACHE_TAGS['roles'], self::CACHE_TAGS['permissions']])
                   ->remember($cacheKey, self::PERMISSION_INHERITANCE_TTL, function () use ($userId, $permission) {
                       $userPermissions = $this->getUserAllPermissions($userId);
                       return $userPermissions->contains('name', $permission);
                   });
    }

    /**
     * 預熱快取 - 載入常用的角色和權限資料
     * 
     * @return void
     */
    public function warmupCache(): void
    {
        try {
            Log::info('開始預熱角色管理快取');

            // 預熱角色層級結構
            $this->getRoleHierarchy();

            // 預熱權限按模組分組
            $this->getPermissionsByModule();

            // 預熱權限依賴關係
            $this->getPermissionDependencies();

            // 預熱角色統計
            $this->getRoleStats();

            // 預熱活躍角色的權限
            $activeRoles = Role::where('is_active', true)->limit(20)->get();
            foreach ($activeRoles as $role) {
                $this->getRoleAllPermissions($role);
                $this->getRoleDirectPermissions($role);
                $this->getRoleInheritedPermissions($role);
            }

            Log::info('角色管理快取預熱完成');

        } catch (\Exception $e) {
            Log::error('角色管理快取預熱失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * 清除角色相關快取
     * 
     * @param int|null $roleId 特定角色 ID，null 表示清除所有
     * @return void
     */
    public function clearRoleCache(?int $roleId = null): void
    {
        if ($roleId) {
            // 清除特定角色的快取
            $patterns = [
                'role_all_permissions:' . $roleId,
                'role_direct_permissions:' . $roleId,
                'role_inherited_permissions:' . $roleId,
            ];

            foreach ($patterns as $pattern) {
                Cache::forget($this->getCacheKey($pattern));
            }

            // 清除可能受影響的使用者權限快取
            $this->clearUserPermissionCacheByRole($roleId);
        } else {
            // 清除所有角色相關快取
            Cache::tags([self::CACHE_TAGS['roles']])->flush();
        }

        // 清除層級結構快取
        Cache::tags([self::CACHE_TAGS['role_hierarchy']])->flush();
    }

    /**
     * 清除權限相關快取
     * 
     * @param int|null $permissionId 特定權限 ID，null 表示清除所有
     * @return void
     */
    public function clearPermissionCache(?int $permissionId = null): void
    {
        if ($permissionId) {
            // 清除特定權限相關的快取
            // 由於權限變更會影響所有相關角色，需要清除所有角色權限快取
            Cache::tags([self::CACHE_TAGS['roles'], self::CACHE_TAGS['permissions']])->flush();
        } else {
            // 清除所有權限相關快取
            Cache::tags([self::CACHE_TAGS['permissions']])->flush();
        }
    }

    /**
     * 清除使用者權限快取
     * 
     * @param int $userId
     * @return void
     */
    public function clearUserPermissionCache(int $userId): void
    {
        $patterns = [
            'user_all_permissions:' . $userId,
            'user_has_permission:' . $userId . ':*',
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($pattern, '*')) {
                // 使用模式匹配清除快取
                $this->clearCacheByPattern($pattern);
            } else {
                Cache::forget($this->getCacheKey($pattern));
            }
        }
    }

    /**
     * 清除所有快取
     * 
     * @return void
     */
    public function clearAllCache(): void
    {
        foreach (self::CACHE_TAGS as $tag) {
            Cache::tags([$tag])->flush();
        }
    }

    /**
     * 取得快取統計資訊
     * 
     * @return array
     */
    public function getCacheStats(): array
    {
        // 這裡可以根據不同的快取驅動實作統計功能
        // 目前返回基本資訊
        return [
            'cache_driver' => config('cache.default'),
            'cache_prefix' => self::CACHE_PREFIX,
            'ttl_settings' => [
                'default' => self::CACHE_TTL,
                'permission_inheritance' => self::PERMISSION_INHERITANCE_TTL,
                'role_stats' => self::ROLE_STATS_TTL,
            ],
            'cache_tags' => self::CACHE_TAGS,
        ];
    }

    /**
     * 計算角色的所有權限（包含繼承）
     * 
     * @param Role $role
     * @return Collection
     */
    private function calculateRoleAllPermissions(Role $role): Collection
    {
        $permissions = $role->permissions;
        
        // 如果有父角色，合併父角色的權限
        if ($role->parent) {
            $parentPermissions = $this->getRoleAllPermissions($role->parent);
            $permissions = $permissions->merge($parentPermissions);
        }
        
        return $permissions->unique('id');
    }

    /**
     * 建立權限依賴關係圖
     * 
     * @return array
     */
    private function buildPermissionDependencyGraph(): array
    {
        $permissions = Permission::with(['dependencies', 'dependents'])->get();
        $graph = [];

        foreach ($permissions as $permission) {
            $graph[$permission->id] = [
                'permission' => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'display_name' => $permission->display_name,
                    'module' => $permission->module
                ],
                'dependencies' => $permission->dependencies->pluck('id')->toArray(),
                'dependents' => $permission->dependents->pluck('id')->toArray()
            ];
        }

        return $graph;
    }

    /**
     * 計算角色統計資訊
     * 
     * @return array
     */
    private function calculateRoleStats(): array
    {
        return [
            'total_roles' => Role::count(),
            'active_roles' => Role::where('is_active', true)->count(),
            'system_roles' => Role::where('is_system_role', true)->count(),
            'roles_with_users' => Role::whereHas('users')->count(),
            'roles_with_permissions' => Role::whereHas('permissions')->count(),
            'root_roles' => Role::whereNull('parent_id')->count(),
            'child_roles' => Role::whereNotNull('parent_id')->count(),
            'average_permissions_per_role' => Role::withCount('permissions')
                                                 ->get()
                                                 ->avg('permissions_count'),
            'most_used_roles' => Role::withCount('users')
                                    ->orderByDesc('users_count')
                                    ->limit(5)
                                    ->get()
                                    ->map(function ($role) {
                                        return [
                                            'id' => $role->id,
                                            'name' => $role->display_name,
                                            'users_count' => $role->users_count
                                        ];
                                    })
                                    ->toArray()
        ];
    }

    /**
     * 清除特定角色相關的使用者權限快取
     * 
     * @param int $roleId
     * @return void
     */
    private function clearUserPermissionCacheByRole(int $roleId): void
    {
        // 找出使用此角色的所有使用者
        $userIds = \DB::table('user_roles')
                     ->where('role_id', $roleId)
                     ->pluck('user_id')
                     ->toArray();

        foreach ($userIds as $userId) {
            $this->clearUserPermissionCache($userId);
        }
    }

    /**
     * 根據模式清除快取
     * 
     * @param string $pattern
     * @return void
     */
    private function clearCacheByPattern(string $pattern): void
    {
        // 這裡需要根據不同的快取驅動實作
        // 對於 Redis，可以使用 KEYS 命令
        // 對於檔案快取，需要掃描檔案
        // 目前簡化處理，清除相關標籤的所有快取
        Cache::tags([self::CACHE_TAGS['roles'], self::CACHE_TAGS['permissions']])->flush();
    }

    /**
     * 生成快取鍵
     * 
     * @param string $key
     * @param mixed ...$params
     * @return string
     */
    private function getCacheKey(string $key, ...$params): string
    {
        $keyParts = [self::CACHE_PREFIX . $key];
        
        foreach ($params as $param) {
            $keyParts[] = (string) $param;
        }
        
        return implode(':', $keyParts);
    }
}