<?php

namespace App\Repositories;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 角色資料存取實作
 */
class RoleRepository implements RoleRepositoryInterface
{
    /**
     * 取得分頁角色列表
     * 
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginatedRoles(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = Role::with(['permissions', 'users'])
                    ->withCount(['permissions', 'users']);

        // 搜尋篩選
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('display_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // 權限數量篩選
        if (!empty($filters['permission_count_filter']) && $filters['permission_count_filter'] !== 'all') {
            switch ($filters['permission_count_filter']) {
                case 'none':
                    $query->having('permissions_count', '=', 0);
                    break;
                case 'few':
                    $query->having('permissions_count', '>', 0)
                          ->having('permissions_count', '<=', 5);
                    break;
                case 'many':
                    $query->having('permissions_count', '>', 5);
                    break;
            }
        }

        // 使用者數量篩選
        if (!empty($filters['user_count_filter']) && $filters['user_count_filter'] !== 'all') {
            switch ($filters['user_count_filter']) {
                case 'none':
                    $query->having('users_count', '=', 0);
                    break;
                case 'few':
                    $query->having('users_count', '>', 0)
                          ->having('users_count', '<=', 10);
                    break;
                case 'many':
                    $query->having('users_count', '>', 10);
                    break;
            }
        }

        // 狀態篩選
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // 系統角色篩選
        if (isset($filters['is_system_role'])) {
            $query->where('is_system_role', $filters['is_system_role']);
        }

        // 排序
        $sortField = $filters['sort_field'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortField, $sortDirection);

        return $query->paginate($perPage);
    }

    /**
     * 取得角色及其權限
     * 
     * @param int $roleId
     * @return Role
     */
    public function getRoleWithPermissions(int $roleId): Role
    {
        return Role::with(['permissions', 'parent', 'children', 'users'])
                   ->findOrFail($roleId);
    }

    /**
     * 取得角色層級結構
     * 
     * @return Collection
     */
    public function getRoleHierarchy(): Collection
    {
        return Role::with(['children' => function ($query) {
            $query->with('children')->orderBy('name');
        }])
        ->whereNull('parent_id')
        ->orderBy('name')
        ->get();
    }

    /**
     * 建立角色
     * 
     * @param array $data
     * @return Role
     */
    public function createRole(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            $role = Role::create($data);
            
            // 如果有權限資料，同步權限
            if (isset($data['permissions'])) {
                $this->syncPermissions($role, $data['permissions']);
            }
            
            return $role;
        });
    }

    /**
     * 更新角色
     * 
     * @param Role $role
     * @param array $data
     * @return bool
     */
    public function updateRole(Role $role, array $data): bool
    {
        return DB::transaction(function () use ($role, $data) {
            $updated = $role->update($data);
            
            // 如果有權限資料，同步權限
            if (isset($data['permissions'])) {
                $this->syncPermissions($role, $data['permissions']);
            }
            
            return $updated;
        });
    }

    /**
     * 刪除角色
     * 
     * @param Role $role
     * @return bool
     */
    public function deleteRole(Role $role): bool
    {
        if (!$this->canDeleteRole($role)) {
            throw new \Exception('角色無法刪除：仍有使用者關聯或為系統角色');
        }

        return DB::transaction(function () use ($role) {
            // 移除權限關聯
            $role->permissions()->detach();
            
            // 刪除角色
            return $role->delete();
        });
    }

    /**
     * 複製角色
     * 
     * @param Role $role
     * @param string $newName
     * @return Role
     */
    public function duplicateRole(Role $role, string $newName): Role
    {
        return DB::transaction(function () use ($role, $newName) {
            // 建立角色副本
            $newRole = $role->replicate();
            $newRole->name = $newName;
            $newRole->display_name = $role->display_name . ' (副本)';
            $newRole->is_system_role = false;
            $newRole->save();
            
            // 複製權限
            $permissionIds = $role->permissions->pluck('id')->toArray();
            $this->syncPermissions($newRole, $permissionIds);
            
            return $newRole;
        });
    }

    /**
     * 同步角色權限
     * 
     * @param Role $role
     * @param array $permissionIds
     * @return void
     */
    public function syncPermissions(Role $role, array $permissionIds): void
    {
        $role->permissions()->sync($permissionIds);
    }

    /**
     * 取得角色統計
     * 
     * @return array
     */
    public function getRoleStats(): array
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
     * 檢查角色是否可以刪除
     * 
     * @param Role $role
     * @return bool
     */
    public function canDeleteRole(Role $role): bool
    {
        // 系統角色不能刪除
        if ($role->is_system_role) {
            return false;
        }

        // 有使用者的角色不能刪除
        if ($role->users()->exists()) {
            return false;
        }

        // 有子角色的角色不能刪除
        if ($role->children()->exists()) {
            return false;
        }

        return true;
    }
}