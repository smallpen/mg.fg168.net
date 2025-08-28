<?php

namespace App\Repositories;

use App\Models\Role;
use App\Models\Permission;
use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
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

    // Implementation of additional methods from Contracts\RoleRepositoryInterface

    /**
     * Get all roles
     *
     * @return Collection
     */
    public function all(): Collection
    {
        return Role::all();
    }

    /**
     * 分頁取得角色列表
     *
     * @param int $perPage 每頁筆數
     * @param array $filters 篩選條件
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->getPaginatedRoles($filters, $perPage);
    }

    /**
     * 根據 ID 尋找角色
     *
     * @param int $id 角色 ID
     * @return Role|null
     */
    public function find(int $id): ?Role
    {
        return Role::find($id);
    }

    /**
     * 根據 ID 尋找角色，找不到則拋出例外
     *
     * @param int $id 角色 ID
     * @return Role
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Role
    {
        return Role::findOrFail($id);
    }

    /**
     * 根據名稱尋找角色
     *
     * @param string $name 角色名稱
     * @return Role|null
     */
    public function findByName(string $name): ?Role
    {
        return Role::where('name', $name)->first();
    }

    /**
     * 建立新角色
     *
     * @param array $data 角色資料
     * @return Role
     */
    public function create(array $data): Role
    {
        return $this->createRole($data);
    }

    /**
     * 更新角色
     *
     * @param Role $role 角色實例
     * @param array $data 更新資料
     * @return bool
     */
    public function update(Role $role, array $data): bool
    {
        return $this->updateRole($role, $data);
    }

    /**
     * 刪除角色
     *
     * @param Role $role 角色實例
     * @return bool
     * @throws \Exception
     */
    public function delete(Role $role): bool
    {
        return $this->deleteRole($role);
    }

    /**
     * 取得角色及其權限
     *
     * @param int $id 角色 ID
     * @return Role|null
     */
    public function findWithPermissions(int $id): ?Role
    {
        return Role::with('permissions')->find($id);
    }

    /**
     * 取得角色及其使用者
     *
     * @param int $id 角色 ID
     * @return Role|null
     */
    public function findWithUsers(int $id): ?Role
    {
        return Role::with('users')->find($id);
    }

    /**
     * 檢查角色名稱是否已存在
     *
     * @param string $name 角色名稱
     * @param int|null $excludeId 排除的角色 ID
     * @return bool
     */
    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        $query = Role::where('name', $name);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * 搜尋角色
     *
     * @param string $term 搜尋關鍵字
     * @param int $limit 限制筆數
     * @return Collection
     */
    public function search(string $term, int $limit = 10): Collection
    {
        return Role::where(function ($query) use ($term) {
            $query->where('name', 'like', "%{$term}%")
                  ->orWhere('display_name', 'like', "%{$term}%")
                  ->orWhere('description', 'like', "%{$term}%");
        })->limit($limit)->get();
    }

    /**
     * 取得可指派的角色（排除系統角色）
     *
     * @return Collection
     */
    public function getAssignableRoles(): Collection
    {
        return Role::where('is_system_role', false)
                   ->where('is_active', true)
                   ->orderBy('display_name')
                   ->get();
    }

    /**
     * 批量更新角色狀態
     *
     * @param array $roleIds 角色 ID 陣列
     * @param bool $isActive 目標狀態
     * @return int 更新的記錄數
     */
    public function bulkUpdateStatus(array $roleIds, bool $isActive): int
    {
        return Role::whereIn('id', $roleIds)
                   ->where('is_system_role', false)
                   ->update(['is_active' => $isActive]);
    }

    /**
     * 取得角色統計資訊
     *
     * @return array
     */
    public function getStats(): array
    {
        return $this->getRoleStats();
    }

    /**
     * 複製角色及其權限
     *
     * @param Role $sourceRole 來源角色
     * @param array $newRoleData 新角色資料
     * @return Role
     * @throws \Exception
     */
    public function duplicate(Role $sourceRole, array $newRoleData): Role
    {
        return DB::transaction(function () use ($sourceRole, $newRoleData) {
            // 建立角色副本
            $newRole = $sourceRole->replicate();
            $newRole->fill($newRoleData);
            $newRole->is_system_role = false;
            $newRole->save();
            
            // 複製權限
            $permissionIds = $sourceRole->permissions->pluck('id')->toArray();
            $this->syncPermissions($newRole, $permissionIds);
            
            return $newRole;
        });
    }

    /**
     * 取得角色的權限樹狀結構
     *
     * @param Role $role 角色實例
     * @return array
     */
    public function getPermissionTree(Role $role): array
    {
        $permissions = $role->permissions()->with('parent', 'children')->get();
        
        // 建立樹狀結構
        $tree = [];
        $grouped = $permissions->groupBy('module');
        
        foreach ($grouped as $module => $modulePermissions) {
            $tree[$module] = $modulePermissions->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'display_name' => $permission->display_name,
                    'type' => $permission->type,
                ];
            })->toArray();
        }
        
        return $tree;
    }

    /**
     * 為角色新增權限
     *
     * @param Role $role 角色實例
     * @param array $permissionIds 權限 ID 陣列
     * @return void
     */
    public function attachPermissions(Role $role, array $permissionIds): void
    {
        $role->permissions()->attach($permissionIds);
    }

    /**
     * 移除角色權限
     *
     * @param Role $role 角色實例
     * @param array $permissionIds 權限 ID 陣列
     * @return void
     */
    public function detachPermissions(Role $role, array $permissionIds): void
    {
        $role->permissions()->detach($permissionIds);
    }

    /**
     * 檢查角色是否可以被刪除
     *
     * @param Role $role 角色實例
     * @return bool
     */
    public function canBeDeleted(Role $role): bool
    {
        return $this->canDeleteRole($role);
    }

    /**
     * 取得角色的使用者數量
     *
     * @param Role $role 角色實例
     * @return int
     */
    public function getUserCount(Role $role): int
    {
        return $role->users()->count();
    }

    /**
     * 取得角色的權限數量
     *
     * @param Role $role 角色實例
     * @return int
     */
    public function getPermissionCount(Role $role): int
    {
        return $role->permissions()->count();
    }

    /**
     * 強制刪除角色（包含有使用者關聯的角色）
     *
     * @param Role $role 角色實例
     * @return bool
     * @throws \Exception
     */
    public function forceDelete(Role $role): bool
    {
        return DB::transaction(function () use ($role) {
            // 移除使用者關聯
            $role->users()->detach();
            
            // 移除權限關聯
            $role->permissions()->detach();
            
            // 刪除角色
            return $role->delete();
        });
    }}
