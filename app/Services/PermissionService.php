<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 權限服務類別
 * 
 * 處理權限相關的業務邏輯，包括權限檢查、角色指派等功能
 * 提供權限快取機制以提升效能
 */
class PermissionService
{
    /**
     * 權限快取的過期時間（秒）
     */
    private const CACHE_TTL = 3600; // 1 小時

    /**
     * 權限快取鍵前綴
     */
    private const CACHE_PREFIX = 'permissions:';

    /**
     * 檢查使用者是否擁有特定權限
     *
     * @param User $user
     * @param string $permission
     * @return bool
     */
    public function hasPermission(User $user, string $permission): bool
    {
        // 超級管理員擁有所有權限
        if ($user->isSuperAdmin()) {
            return true;
        }

        // 使用快取檢查權限
        $userPermissions = $this->getCachedUserPermissions($user);
        
        return $userPermissions->contains('name', $permission);
    }

    /**
     * 檢查使用者是否擁有任一指定權限
     *
     * @param User $user
     * @param array $permissions
     * @return bool
     */
    public function hasAnyPermission(User $user, array $permissions): bool
    {
        // 超級管理員擁有所有權限
        if ($user->isSuperAdmin()) {
            return true;
        }

        foreach ($permissions as $permission) {
            if ($this->hasPermission($user, $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 檢查使用者是否擁有所有指定權限
     *
     * @param User $user
     * @param array $permissions
     * @return bool
     */
    public function hasAllPermissions(User $user, array $permissions): bool
    {
        // 超級管理員擁有所有權限
        if ($user->isSuperAdmin()) {
            return true;
        }

        foreach ($permissions as $permission) {
            if (!$this->hasPermission($user, $permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 為使用者指派角色
     *
     * @param User $user
     * @param string|Role $role
     * @return bool
     * @throws \Exception
     */
    public function assignRole(User $user, $role): bool
    {
        try {
            DB::beginTransaction();

            if (is_string($role)) {
                $role = Role::where('name', $role)->firstOrFail();
            }

            // 檢查使用者是否已經擁有該角色
            if ($user->hasRole($role->name)) {
                Log::info('User already has role', [
                    'user_id' => $user->id,
                    'role' => $role->name
                ]);
                return true;
            }

            // 指派角色
            $user->roles()->attach($role->id);

            // 清除使用者權限快取
            $this->clearUserPermissionCache($user);

            DB::commit();

            Log::info('Role assigned to user', [
                'user_id' => $user->id,
                'role' => $role->name,
                'assigned_by' => auth()->id()
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to assign role to user', [
                'user_id' => $user->id,
                'role' => is_string($role) ? $role : $role->name,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * 移除使用者的角色
     *
     * @param User $user
     * @param string|Role $role
     * @return bool
     * @throws \Exception
     */
    public function removeRole(User $user, $role): bool
    {
        try {
            DB::beginTransaction();

            if (is_string($role)) {
                $role = Role::where('name', $role)->firstOrFail();
            }

            // 檢查使用者是否擁有該角色
            if (!$user->hasRole($role->name)) {
                Log::info('User does not have role', [
                    'user_id' => $user->id,
                    'role' => $role->name
                ]);
                return true;
            }

            // 移除角色
            $user->roles()->detach($role->id);

            // 清除使用者權限快取
            $this->clearUserPermissionCache($user);

            DB::commit();

            Log::info('Role removed from user', [
                'user_id' => $user->id,
                'role' => $role->name,
                'removed_by' => auth()->id()
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to remove role from user', [
                'user_id' => $user->id,
                'role' => is_string($role) ? $role : $role->name,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * 同步使用者的角色（移除舊角色，指派新角色）
     *
     * @param User $user
     * @param array $roleNames
     * @return bool
     * @throws \Exception
     */
    public function syncRoles(User $user, array $roleNames): bool
    {
        try {
            DB::beginTransaction();

            // 取得角色 ID
            $roleIds = Role::whereIn('name', $roleNames)->pluck('id')->toArray();

            if (count($roleIds) !== count($roleNames)) {
                throw new \InvalidArgumentException('Some roles do not exist');
            }

            // 同步角色
            $user->roles()->sync($roleIds);

            // 清除使用者權限快取
            $this->clearUserPermissionCache($user);

            DB::commit();

            Log::info('User roles synchronized', [
                'user_id' => $user->id,
                'roles' => $roleNames,
                'synchronized_by' => auth()->id()
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to synchronize user roles', [
                'user_id' => $user->id,
                'roles' => $roleNames,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * 取得使用者的所有權限（使用快取）
     *
     * @param User $user
     * @return Collection
     */
    public function getUserPermissions(User $user): Collection
    {
        return $this->getCachedUserPermissions($user);
    }

    /**
     * 取得使用者在特定模組的權限
     *
     * @param User $user
     * @param string $module
     * @return Collection
     */
    public function getUserPermissionsByModule(User $user, string $module): Collection
    {
        $permissions = $this->getUserPermissions($user);
        
        return $permissions->where('module', $module);
    }

    /**
     * 檢查使用者是否可以存取特定模組
     *
     * @param User $user
     * @param string $module
     * @return bool
     */
    public function canAccessModule(User $user, string $module): bool
    {
        // 超級管理員可以存取所有模組
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $this->getUserPermissionsByModule($user, $module)->isNotEmpty();
    }

    /**
     * 取得角色的所有權限
     *
     * @param Role $role
     * @return Collection
     */
    public function getRolePermissions(Role $role): Collection
    {
        $cacheKey = self::CACHE_PREFIX . "role:{$role->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($role) {
            return $role->permissions()->get();
        });
    }

    /**
     * 為角色指派權限
     *
     * @param Role $role
     * @param string|Permission $permission
     * @return bool
     * @throws \Exception
     */
    public function assignPermissionToRole(Role $role, $permission): bool
    {
        try {
            DB::beginTransaction();

            if (is_string($permission)) {
                $permission = Permission::where('name', $permission)->firstOrFail();
            }

            // 檢查角色是否已經擁有該權限
            if ($role->permissions()->where('permission_id', $permission->id)->exists()) {
                Log::info('Role already has permission', [
                    'role_id' => $role->id,
                    'permission' => $permission->name
                ]);
                return true;
            }

            // 指派權限
            $role->permissions()->attach($permission->id);

            // 清除相關快取
            $this->clearRolePermissionCache($role);
            $this->clearUsersPermissionCacheByRole($role);

            DB::commit();

            Log::info('Permission assigned to role', [
                'role_id' => $role->id,
                'permission' => $permission->name,
                'assigned_by' => auth()->id()
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to assign permission to role', [
                'role_id' => $role->id,
                'permission' => is_string($permission) ? $permission : $permission->name,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * 移除角色的權限
     *
     * @param Role $role
     * @param string|Permission $permission
     * @return bool
     * @throws \Exception
     */
    public function removePermissionFromRole(Role $role, $permission): bool
    {
        try {
            DB::beginTransaction();

            if (is_string($permission)) {
                $permission = Permission::where('name', $permission)->firstOrFail();
            }

            // 移除權限
            $role->permissions()->detach($permission->id);

            // 清除相關快取
            $this->clearRolePermissionCache($role);
            $this->clearUsersPermissionCacheByRole($role);

            DB::commit();

            Log::info('Permission removed from role', [
                'role_id' => $role->id,
                'permission' => $permission->name,
                'removed_by' => auth()->id()
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to remove permission from role', [
                'role_id' => $role->id,
                'permission' => is_string($permission) ? $permission : $permission->name,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * 同步角色的權限
     *
     * @param Role $role
     * @param array $permissionNames
     * @return bool
     * @throws \Exception
     */
    public function syncRolePermissions(Role $role, array $permissionNames): bool
    {
        try {
            DB::beginTransaction();

            // 取得權限 ID
            $permissionIds = Permission::whereIn('name', $permissionNames)->pluck('id')->toArray();

            if (count($permissionIds) !== count($permissionNames)) {
                throw new \InvalidArgumentException('Some permissions do not exist');
            }

            // 同步權限
            $role->permissions()->sync($permissionIds);

            // 清除相關快取
            $this->clearRolePermissionCache($role);
            $this->clearUsersPermissionCacheByRole($role);

            DB::commit();

            Log::info('Role permissions synchronized', [
                'role_id' => $role->id,
                'permissions' => $permissionNames,
                'synchronized_by' => auth()->id()
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to synchronize role permissions', [
                'role_id' => $role->id,
                'permissions' => $permissionNames,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * 取得所有可用的權限，按模組分組
     *
     * @return Collection
     */
    public function getAllPermissionsGroupedByModule(): Collection
    {
        $cacheKey = self::CACHE_PREFIX . 'all_grouped';

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            return Permission::orderBy('module')
                           ->orderBy('name')
                           ->get()
                           ->groupBy('module');
        });
    }

    /**
     * 取得使用者的快取權限
     *
     * @param User $user
     * @return Collection
     */
    private function getCachedUserPermissions(User $user): Collection
    {
        $cacheKey = self::CACHE_PREFIX . "user:{$user->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            return $user->roles()
                       ->with('permissions')
                       ->get()
                       ->pluck('permissions')
                       ->flatten()
                       ->unique('id');
        });
    }

    /**
     * 清除使用者權限快取
     *
     * @param User $user
     * @return void
     */
    public function clearUserPermissionCache(User $user): void
    {
        $cacheKey = self::CACHE_PREFIX . "user:{$user->id}";
        Cache::forget($cacheKey);
    }

    /**
     * 清除角色權限快取
     *
     * @param Role $role
     * @return void
     */
    private function clearRolePermissionCache(Role $role): void
    {
        $cacheKey = self::CACHE_PREFIX . "role:{$role->id}";
        Cache::forget($cacheKey);
    }

    /**
     * 清除擁有特定角色的所有使用者的權限快取
     *
     * @param Role $role
     * @return void
     */
    private function clearUsersPermissionCacheByRole(Role $role): void
    {
        $userIds = $role->users()->pluck('users.id');
        
        foreach ($userIds as $userId) {
            $cacheKey = self::CACHE_PREFIX . "user:{$userId}";
            Cache::forget($cacheKey);
        }
    }

    /**
     * 清除所有權限相關快取
     *
     * @return void
     */
    public function clearAllPermissionCache(): void
    {
        // 清除所有權限快取
        Cache::forget(self::CACHE_PREFIX . 'all_grouped');
        
        // 清除所有使用者權限快取
        $userIds = User::pluck('id');
        foreach ($userIds as $userId) {
            $cacheKey = self::CACHE_PREFIX . "user:{$userId}";
            Cache::forget($cacheKey);
        }

        // 清除所有角色權限快取
        $roleIds = Role::pluck('id');
        foreach ($roleIds as $roleId) {
            $cacheKey = self::CACHE_PREFIX . "role:{$roleId}";
            Cache::forget($cacheKey);
        }

        Log::info('All permission cache cleared', [
            'cleared_by' => auth()->id()
        ]);
    }

    /**
     * 取得權限統計資訊
     *
     * @return array
     */
    public function getPermissionStats(): array
    {
        $cacheKey = self::CACHE_PREFIX . 'stats';

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            return [
                'total_permissions' => Permission::count(),
                'total_roles' => Role::count(),
                'total_users_with_roles' => User::whereHas('roles')->count(),
                'permissions_by_module' => Permission::select('module', DB::raw('count(*) as count'))
                                                   ->groupBy('module')
                                                   ->pluck('count', 'module')
                                                   ->toArray(),
                'users_by_role' => Role::withCount('users')
                                     ->get()
                                     ->pluck('users_count', 'name')
                                     ->toArray()
            ];
        });
    }
}