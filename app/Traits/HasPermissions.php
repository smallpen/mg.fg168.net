<?php

namespace App\Traits;

use App\Models\Permission;
use Illuminate\Support\Collection;

/**
 * 權限檢查特性
 * 
 * 提供使用者權限檢查的相關方法
 */
trait HasPermissions
{
    /**
     * 檢查使用者是否擁有特定權限
     * 
     * @param string $permission
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        // 超級管理員擁有所有權限
        if ($this->isSuperAdmin()) {
            return true;
        }

        // 透過角色檢查權限
        return $this->roles()
                    ->whereHas('permissions', function ($query) use ($permission) {
                        $query->where('name', $permission);
                    })
                    ->exists();
    }

    /**
     * 檢查使用者是否擁有任一指定權限
     * 
     * @param array $permissions
     * @return bool
     */
    public function hasAnyPermission(array $permissions): bool
    {
        // 超級管理員擁有所有權限
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->roles()
                    ->whereHas('permissions', function ($query) use ($permissions) {
                        $query->whereIn('name', $permissions);
                    })
                    ->exists();
    }

    /**
     * 檢查使用者是否擁有所有指定權限
     * 
     * @param array $permissions
     * @return bool
     */
    public function hasAllPermissions(array $permissions): bool
    {
        // 超級管理員擁有所有權限
        if ($this->isSuperAdmin()) {
            return true;
        }

        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 取得使用者的所有權限
     * 
     * @return Collection
     */
    public function getAllPermissions(): Collection
    {
        // 超級管理員擁有所有權限
        if ($this->isSuperAdmin()) {
            return Permission::all();
        }

        return $this->roles()
                    ->with('permissions')
                    ->get()
                    ->pluck('permissions')
                    ->flatten()
                    ->unique('id');
    }

    /**
     * 取得使用者在特定模組的權限
     * 
     * @param string $module
     * @return Collection
     */
    public function getPermissionsByModule(string $module): Collection
    {
        return $this->getAllPermissions()
                    ->where('module', $module);
    }

    /**
     * 檢查使用者是否可以存取特定模組
     * 
     * @param string $module
     * @return bool
     */
    public function canAccessModule(string $module): bool
    {
        return $this->getPermissionsByModule($module)->isNotEmpty();
    }

    /**
     * 快取使用者權限（用於效能優化）
     * 
     * @return Collection
     */
    public function getCachedPermissions(): Collection
    {
        $cacheKey = "user_permissions_{$this->id}";
        
        return cache()->remember($cacheKey, 3600, function () {
            return $this->getAllPermissions();
        });
    }

    /**
     * 清除使用者權限快取
     * 
     * @return void
     */
    public function clearPermissionCache(): void
    {
        $cacheKey = "user_permissions_{$this->id}";
        cache()->forget($cacheKey);
    }
}