<?php

namespace App\Repositories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * 角色資料存取層
 * 
 * 處理角色相關的資料庫操作
 */
class RoleRepository
{
    /**
     * 取得所有角色
     *
     * @return Collection
     */
    public function all(): Collection
    {
        return Role::orderBy('name')->get();
    }

    /**
     * 分頁取得角色列表
     *
     * @param int $perPage
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Role::query();

        // 搜尋過濾
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('display_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // 狀態過濾（如果有 is_active 欄位）
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->withCount('users')
                    ->withCount('permissions')
                    ->orderBy('name')
                    ->paginate($perPage);
    }

    /**
     * 根據 ID 尋找角色
     *
     * @param int $id
     * @return Role|null
     */
    public function find(int $id): ?Role
    {
        return Role::find($id);
    }

    /**
     * 根據 ID 尋找角色，找不到則拋出例外
     *
     * @param int $id
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
     * @param string $name
     * @return Role|null
     */
    public function findByName(string $name): ?Role
    {
        return Role::where('name', $name)->first();
    }

    /**
     * 建立新角色
     *
     * @param array $data
     * @return Role
     */
    public function create(array $data): Role
    {
        return Role::create($data);
    }

    /**
     * 更新角色
     *
     * @param Role $role
     * @param array $data
     * @return bool
     */
    public function update(Role $role, array $data): bool
    {
        return $role->update($data);
    }

    /**
     * 刪除角色
     *
     * @param Role $role
     * @return bool
     * @throws \Exception
     */
    public function delete(Role $role): bool
    {
        // 檢查是否有使用者正在使用此角色
        if ($role->users()->exists()) {
            throw new \Exception('無法刪除角色，因為仍有使用者正在使用此角色');
        }

        // 移除角色的所有權限關聯
        $role->permissions()->detach();

        return $role->delete();
    }

    /**
     * 取得角色及其權限
     *
     * @param int $id
     * @return Role|null
     */
    public function findWithPermissions(int $id): ?Role
    {
        return Role::with('permissions')->find($id);
    }

    /**
     * 取得角色及其使用者
     *
     * @param int $id
     * @return Role|null
     */
    public function findWithUsers(int $id): ?Role
    {
        return Role::with('users')->find($id);
    }

    /**
     * 取得角色的使用者數量
     *
     * @param Role $role
     * @return int
     */
    public function getUserCount(Role $role): int
    {
        return $role->users()->count();
    }

    /**
     * 取得角色的權限數量
     *
     * @param Role $role
     * @return int
     */
    public function getPermissionCount(Role $role): int
    {
        return $role->permissions()->count();
    }

    /**
     * 檢查角色名稱是否已存在
     *
     * @param string $name
     * @param int|null $excludeId
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
     * 取得角色統計資訊
     *
     * @return array
     */
    public function getStats(): array
    {
        return [
            'total_roles' => Role::count(),
            'roles_with_users' => Role::whereHas('users')->count(),
            'roles_with_permissions' => Role::whereHas('permissions')->count(),
            'average_permissions_per_role' => Role::withCount('permissions')
                                                 ->get()
                                                 ->avg('permissions_count'),
            'most_used_roles' => Role::withCount('users')
                                    ->orderByDesc('users_count')
                                    ->limit(5)
                                    ->get()
                                    ->map(function ($role) {
                                        return [
                                            'name' => $role->display_name,
                                            'users_count' => $role->users_count
                                        ];
                                    })
                                    ->toArray()
        ];
    }

    /**
     * 搜尋角色
     *
     * @param string $term
     * @param int $limit
     * @return Collection
     */
    public function search(string $term, int $limit = 10): Collection
    {
        return Role::where('name', 'like', "%{$term}%")
                  ->orWhere('display_name', 'like', "%{$term}%")
                  ->limit($limit)
                  ->get();
    }

    /**
     * 取得可指派的角色（排除系統角色）
     *
     * @return Collection
     */
    public function getAssignableRoles(): Collection
    {
        // 排除超級管理員角色，避免意外指派
        return Role::where('name', '!=', 'super_admin')
                  ->orderBy('name')
                  ->get();
    }

    /**
     * 批量更新角色狀態
     *
     * @param array $roleIds
     * @param bool $isActive
     * @return int 更新的記錄數
     */
    public function bulkUpdateStatus(array $roleIds, bool $isActive): int
    {
        return Role::whereIn('id', $roleIds)
                  ->update(['is_active' => $isActive]);
    }

    /**
     * 取得角色的權限樹狀結構
     *
     * @param Role $role
     * @return array
     */
    public function getPermissionTree(Role $role): array
    {
        $permissions = $role->permissions()
                           ->orderBy('module')
                           ->orderBy('name')
                           ->get();

        $tree = [];
        foreach ($permissions as $permission) {
            $module = $permission->module;
            if (!isset($tree[$module])) {
                $tree[$module] = [
                    'module' => $module,
                    'permissions' => []
                ];
            }
            $tree[$module]['permissions'][] = $permission;
        }

        return array_values($tree);
    }

    /**
     * 複製角色及其權限
     *
     * @param Role $sourceRole
     * @param array $newRoleData
     * @return Role
     * @throws \Exception
     */
    public function duplicate(Role $sourceRole, array $newRoleData): Role
    {
        try {
            DB::beginTransaction();

            // 建立新角色
            $newRole = $this->create($newRoleData);

            // 複製權限
            $permissionIds = $sourceRole->permissions()->pluck('permissions.id')->toArray();
            $newRole->permissions()->attach($permissionIds);

            DB::commit();

            return $newRole;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}