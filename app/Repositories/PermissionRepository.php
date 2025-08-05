<?php

namespace App\Repositories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * 權限資料存取層
 * 
 * 處理權限相關的資料庫操作
 */
class PermissionRepository
{
    /**
     * 取得所有權限
     *
     * @return Collection
     */
    public function all(): Collection
    {
        return Permission::orderBy('module')
                        ->orderBy('name')
                        ->get();
    }

    /**
     * 分頁取得權限列表
     *
     * @param int $perPage
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Permission::query();

        // 搜尋過濾
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('display_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // 模組過濾
        if (!empty($filters['module'])) {
            $query->where('module', $filters['module']);
        }

        return $query->withCount('roles')
                    ->orderBy('module')
                    ->orderBy('name')
                    ->paginate($perPage);
    }

    /**
     * 根據 ID 尋找權限
     *
     * @param int $id
     * @return Permission|null
     */
    public function find(int $id): ?Permission
    {
        return Permission::find($id);
    }

    /**
     * 根據 ID 尋找權限，找不到則拋出例外
     *
     * @param int $id
     * @return Permission
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Permission
    {
        return Permission::findOrFail($id);
    }

    /**
     * 根據名稱尋找權限
     *
     * @param string $name
     * @return Permission|null
     */
    public function findByName(string $name): ?Permission
    {
        return Permission::where('name', $name)->first();
    }

    /**
     * 建立新權限
     *
     * @param array $data
     * @return Permission
     */
    public function create(array $data): Permission
    {
        return Permission::create($data);
    }

    /**
     * 更新權限
     *
     * @param Permission $permission
     * @param array $data
     * @return bool
     */
    public function update(Permission $permission, array $data): bool
    {
        return $permission->update($data);
    }

    /**
     * 刪除權限
     *
     * @param Permission $permission
     * @return bool
     * @throws \Exception
     */
    public function delete(Permission $permission): bool
    {
        // 檢查是否有角色正在使用此權限
        if ($permission->roles()->exists()) {
            throw new \Exception('無法刪除權限，因為仍有角色正在使用此權限');
        }

        return $permission->delete();
    }

    /**
     * 取得權限及其角色
     *
     * @param int $id
     * @return Permission|null
     */
    public function findWithRoles(int $id): ?Permission
    {
        return Permission::with('roles')->find($id);
    }

    /**
     * 取得權限的角色數量
     *
     * @param Permission $permission
     * @return int
     */
    public function getRoleCount(Permission $permission): int
    {
        return $permission->roles()->count();
    }

    /**
     * 檢查權限名稱是否已存在
     *
     * @param string $name
     * @param int|null $excludeId
     * @return bool
     */
    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        $query = Permission::where('name', $name);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * 根據模組取得權限
     *
     * @param string $module
     * @return Collection
     */
    public function getByModule(string $module): Collection
    {
        return Permission::where('module', $module)
                        ->orderBy('name')
                        ->get();
    }

    /**
     * 取得所有模組列表
     *
     * @return Collection
     */
    public function getAllModules(): Collection
    {
        return Permission::select('module')
                        ->distinct()
                        ->orderBy('module')
                        ->pluck('module');
    }

    /**
     * 取得權限按模組分組
     *
     * @return Collection
     */
    public function getAllGroupedByModule(): Collection
    {
        return Permission::orderBy('module')
                        ->orderBy('name')
                        ->get()
                        ->groupBy('module');
    }

    /**
     * 取得權限統計資訊
     *
     * @return array
     */
    public function getStats(): array
    {
        return [
            'total_permissions' => Permission::count(),
            'permissions_with_roles' => Permission::whereHas('roles')->count(),
            'permissions_by_module' => Permission::select('module', DB::raw('count(*) as count'))
                                                ->groupBy('module')
                                                ->orderBy('module')
                                                ->get()
                                                ->pluck('count', 'module')
                                                ->toArray(),
            'most_used_permissions' => Permission::withCount('roles')
                                                ->orderByDesc('roles_count')
                                                ->limit(10)
                                                ->get()
                                                ->map(function ($permission) {
                                                    return [
                                                        'name' => $permission->display_name,
                                                        'roles_count' => $permission->roles_count
                                                    ];
                                                })
                                                ->toArray()
        ];
    }

    /**
     * 搜尋權限
     *
     * @param string $term
     * @param int $limit
     * @return Collection
     */
    public function search(string $term, int $limit = 10): Collection
    {
        return Permission::where('name', 'like', "%{$term}%")
                        ->orWhere('display_name', 'like', "%{$term}%")
                        ->limit($limit)
                        ->get();
    }

    /**
     * 批量建立權限
     *
     * @param array $permissions
     * @return bool
     * @throws \Exception
     */
    public function bulkCreate(array $permissions): bool
    {
        try {
            DB::beginTransaction();

            foreach ($permissions as $permissionData) {
                // 檢查權限是否已存在
                if (!$this->nameExists($permissionData['name'])) {
                    $this->create($permissionData);
                }
            }

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 批量刪除權限
     *
     * @param array $permissionIds
     * @return int 刪除的記錄數
     * @throws \Exception
     */
    public function bulkDelete(array $permissionIds): int
    {
        // 檢查是否有權限正在被使用
        $usedPermissions = Permission::whereIn('id', $permissionIds)
                                   ->whereHas('roles')
                                   ->pluck('display_name')
                                   ->toArray();

        if (!empty($usedPermissions)) {
            throw new \Exception('無法刪除權限，以下權限仍在使用中：' . implode(', ', $usedPermissions));
        }

        return Permission::whereIn('id', $permissionIds)->delete();
    }

    /**
     * 取得模組的權限樹狀結構
     *
     * @param string $module
     * @return array
     */
    public function getModulePermissionTree(string $module): array
    {
        $permissions = $this->getByModule($module);
        
        $tree = [];
        foreach ($permissions as $permission) {
            // 解析權限名稱，例如 "user.create" -> ["user", "create"]
            $parts = explode('.', $permission->name);
            $action = end($parts);
            
            $tree[] = [
                'id' => $permission->id,
                'name' => $permission->name,
                'display_name' => $permission->display_name,
                'action' => $action,
                'description' => $permission->description
            ];
        }

        return $tree;
    }

    /**
     * 取得權限矩陣（所有模組和權限的結構化資料）
     *
     * @return array
     */
    public function getPermissionMatrix(): array
    {
        $permissions = $this->getAllGroupedByModule();
        
        $matrix = [];
        foreach ($permissions as $module => $modulePermissions) {
            $matrix[$module] = [
                'module' => $module,
                'permissions' => $modulePermissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'display_name' => $permission->display_name,
                        'description' => $permission->description,
                        'roles_count' => $permission->roles()->count()
                    ];
                })->toArray()
            ];
        }

        return $matrix;
    }

    /**
     * 根據角色 ID 取得權限
     *
     * @param int $roleId
     * @return Collection
     */
    public function getByRoleId(int $roleId): Collection
    {
        return Permission::whereHas('roles', function ($query) use ($roleId) {
            $query->where('role_id', $roleId);
        })->orderBy('module')->orderBy('name')->get();
    }

    /**
     * 取得未分配給任何角色的權限
     *
     * @return Collection
     */
    public function getUnassignedPermissions(): Collection
    {
        return Permission::whereDoesntHave('roles')
                        ->orderBy('module')
                        ->orderBy('name')
                        ->get();
    }
}