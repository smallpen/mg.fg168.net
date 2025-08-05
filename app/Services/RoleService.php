<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Repositories\RoleRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * 角色服務類別
 * 
 * 處理角色相關的業務邏輯，包括角色建立、更新、刪除等功能
 * 實作角色權限管理的業務規則和角色刪除時的依賴檢查邏輯
 */
class RoleService
{
    /**
     * 角色資料存取層實例
     * 
     * @var RoleRepository
     */
    protected RoleRepository $roleRepository;

    /**
     * 權限服務實例
     * 
     * @var PermissionService
     */
    protected PermissionService $permissionService;

    /**
     * 建構函式
     * 
     * @param RoleRepository $roleRepository
     * @param PermissionService $permissionService
     */
    public function __construct(
        RoleRepository $roleRepository,
        PermissionService $permissionService
    ) {
        $this->roleRepository = $roleRepository;
        $this->permissionService = $permissionService;
    }

    /**
     * 建立新角色
     * 
     * @param array $data
     * @return Role
     * @throws ValidationException
     * @throws \Exception
     */
    public function createRole(array $data): Role
    {
        // 驗證輸入資料
        $this->validateRoleData($data);

        try {
            DB::beginTransaction();

            // 建立角色
            $role = $this->roleRepository->create([
                'name' => $data['name'],
                'display_name' => $data['display_name'],
                'description' => $data['description'] ?? null,
            ]);

            // 指派權限（如果有提供）
            if (!empty($data['permissions'])) {
                $this->assignPermissionsToRole($role, $data['permissions']);
            }

            DB::commit();

            // 清除相關快取
            $this->clearRoleRelatedCache();

            Log::info('Role created successfully', [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'created_by' => auth()->id(),
                'permissions' => $data['permissions'] ?? []
            ]);

            return $role;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create role', [
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * 更新角色資訊
     * 
     * @param Role $role
     * @param array $data
     * @return Role
     * @throws ValidationException
     * @throws \Exception
     */
    public function updateRole(Role $role, array $data): Role
    {
        // 檢查是否為系統保護角色
        $this->checkSystemProtectedRole($role);

        // 驗證輸入資料（更新時）
        $this->validateRoleData($data, $role->id);

        try {
            DB::beginTransaction();

            // 準備更新資料
            $updateData = [];
            
            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
            }
            
            if (isset($data['display_name'])) {
                $updateData['display_name'] = $data['display_name'];
            }
            
            if (isset($data['description'])) {
                $updateData['description'] = $data['description'];
            }

            // 更新角色資訊
            $this->roleRepository->update($role, $updateData);

            // 更新權限（如果有提供）
            if (isset($data['permissions'])) {
                $this->syncRolePermissions($role, $data['permissions']);
            }

            DB::commit();

            // 清除相關快取
            $this->clearRoleRelatedCache();

            Log::info('Role updated successfully', [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'updated_by' => auth()->id(),
                'updated_fields' => array_keys($updateData),
                'permissions_updated' => isset($data['permissions'])
            ]);

            $freshRole = $role->fresh();
            if (!$freshRole) {
                throw new \Exception('Role not found after update');
            }
            return $freshRole;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to update role', [
                'role_id' => $role->id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * 刪除角色
     * 
     * @param Role $role
     * @param bool $forceDelete 是否強制刪除（忽略依賴檢查）
     * @return bool
     * @throws \Exception
     */
    public function deleteRole(Role $role, bool $forceDelete = false): bool
    {
        // 檢查是否為系統保護角色
        $this->checkSystemProtectedRole($role);

        // 執行依賴檢查
        if (!$forceDelete) {
            $dependencies = $this->getRoleDeletionDependencies($role);
            if (!empty($dependencies)) {
                throw new \InvalidArgumentException(
                    '無法刪除角色，存在以下依賴關係: ' . implode(', ', $dependencies)
                );
            }
        }

        try {
            DB::beginTransaction();

            $roleId = $role->id;
            $roleName = $role->name;

            if ($forceDelete) {
                // 強制刪除：移除所有關聯
                $this->forceDeleteRoleWithDependencies($role);
            } else {
                // 一般刪除：使用 Repository 的刪除方法
                $this->roleRepository->delete($role);
            }

            DB::commit();

            // 清除相關快取
            $this->clearRoleRelatedCache();

            Log::info('Role deleted successfully', [
                'role_id' => $roleId,
                'role_name' => $roleName,
                'deleted_by' => auth()->id(),
                'force_delete' => $forceDelete
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to delete role', [
                'role_id' => $role->id,
                'force_delete' => $forceDelete,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * 為角色指派權限
     * 
     * @param Role $role
     * @param array $permissionNames
     * @return bool
     * @throws \Exception
     */
    public function assignPermissionsToRole(Role $role, array $permissionNames): bool
    {
        try {
            foreach ($permissionNames as $permissionName) {
                $this->permissionService->assignPermissionToRole($role, $permissionName);
            }

            // 清除相關快取
            $this->clearRoleRelatedCache();

            Log::info('Permissions assigned to role', [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'permissions' => $permissionNames,
                'assigned_by' => auth()->id()
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to assign permissions to role', [
                'role_id' => $role->id,
                'permissions' => $permissionNames,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * 同步角色權限
     * 
     * @param Role $role
     * @param array $permissionNames
     * @return bool
     * @throws \Exception
     */
    public function syncRolePermissions(Role $role, array $permissionNames): bool
    {
        try {
            $this->permissionService->syncRolePermissions($role, $permissionNames);

            // 清除相關快取
            $this->clearRoleRelatedCache();

            Log::info('Role permissions synchronized', [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'permissions' => $permissionNames,
                'synchronized_by' => auth()->id()
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to synchronize role permissions', [
                'role_id' => $role->id,
                'permissions' => $permissionNames,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * 移除角色的權限
     * 
     * @param Role $role
     * @param string $permissionName
     * @return bool
     * @throws \Exception
     */
    public function removePermissionFromRole(Role $role, string $permissionName): bool
    {
        try {
            $this->permissionService->removePermissionFromRole($role, $permissionName);

            // 清除相關快取
            $this->clearRoleRelatedCache();

            Log::info('Permission removed from role', [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'permission' => $permissionName,
                'removed_by' => auth()->id()
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to remove permission from role', [
                'role_id' => $role->id,
                'permission' => $permissionName,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * 複製角色及其權限
     * 
     * @param Role $sourceRole
     * @param array $newRoleData
     * @return Role
     * @throws \Exception
     */
    public function duplicateRole(Role $sourceRole, array $newRoleData): Role
    {
        // 驗證新角色資料
        $this->validateRoleData($newRoleData);

        try {
            return $this->roleRepository->duplicate($sourceRole, $newRoleData);

        } catch (\Exception $e) {
            Log::error('Failed to duplicate role', [
                'source_role_id' => $sourceRole->id,
                'new_role_data' => $newRoleData,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * 取得角色的權限樹狀結構
     * 
     * @param Role $role
     * @return array
     */
    public function getRolePermissionTree(Role $role): array
    {
        return $this->roleRepository->getPermissionTree($role);
    }

    /**
     * 取得可指派的角色列表
     * 
     * @return Collection
     */
    public function getAssignableRoles(): Collection
    {
        return $this->roleRepository->getAssignableRoles();
    }

    /**
     * 搜尋角色
     * 
     * @param string $term
     * @param int $limit
     * @return Collection
     */
    public function searchRoles(string $term, int $limit = 10): Collection
    {
        return $this->roleRepository->search($term, $limit);
    }

    /**
     * 取得角色統計資訊
     * 
     * @return array
     */
    public function getRoleStats(): array
    {
        return $this->roleRepository->getStats();
    }

    /**
     * 批量更新角色狀態
     * 
     * @param array $roleIds
     * @param bool $isActive
     * @return int 更新的記錄數
     * @throws \Exception
     */
    public function bulkUpdateRoleStatus(array $roleIds, bool $isActive): int
    {
        // 檢查是否包含系統保護角色
        $protectedRoles = $this->getSystemProtectedRoles();
        $protectedRoleIds = Role::whereIn('name', $protectedRoles)->pluck('id')->toArray();
        
        $conflictIds = array_intersect($roleIds, $protectedRoleIds);
        if (!empty($conflictIds)) {
            throw new \InvalidArgumentException('無法修改系統保護角色的狀態');
        }

        try {
            $updatedCount = $this->roleRepository->bulkUpdateStatus($roleIds, $isActive);

            // 清除相關快取
            $this->clearRoleRelatedCache();

            Log::info('Bulk role status updated', [
                'role_ids' => $roleIds,
                'is_active' => $isActive,
                'updated_count' => $updatedCount,
                'updated_by' => auth()->id()
            ]);

            return $updatedCount;

        } catch (\Exception $e) {
            Log::error('Failed to bulk update role status', [
                'role_ids' => $roleIds,
                'is_active' => $isActive,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * 取得角色刪除前的依賴檢查結果
     * 
     * @param Role $role
     * @return array
     */
    public function getRoleDeletionDependencies(Role $role): array
    {
        $dependencies = [];

        // 檢查是否為系統保護角色
        if ($this->isSystemProtectedRole($role)) {
            $dependencies['system_protected'] = '此角色為系統保護角色，無法刪除';
        }

        // 檢查是否有使用者正在使用此角色
        $userCount = $this->roleRepository->getUserCount($role);
        if ($userCount > 0) {
            $dependencies['users'] = "有 {$userCount} 個使用者正在使用此角色";
        }

        // 檢查是否有相關的活動記錄
        $activityCount = DB::table('activities')
                          ->where('properties->role_id', $role->id)
                          ->count();
        if ($activityCount > 0) {
            $dependencies['activities'] = "角色有 {$activityCount} 筆相關活動記錄";
        }

        return $dependencies;
    }

    /**
     * 驗證角色建立的業務規則
     * 
     * @param array $data
     * @return array
     */
    public function validateRoleCreationRules(array $data): array
    {
        $errors = [];

        // 檢查角色名稱是否符合業務規則
        if (isset($data['name'])) {
            // 檢查是否為保留名稱
            if ($this->isReservedRoleName($data['name'])) {
                $errors['name'][] = '此角色名稱為系統保留名稱';
            }
            
            // 檢查名稱格式
            if (!preg_match('/^[a-z_]+$/', $data['name'])) {
                $errors['name'][] = '角色名稱只能包含小寫字母和底線';
            }
            
            // 檢查名稱是否已存在
            if ($this->roleRepository->nameExists($data['name'])) {
                $errors['name'][] = '此角色名稱已被使用';
            }
        }

        // 檢查權限是否存在
        if (!empty($data['permissions'])) {
            $existingPermissions = Permission::whereIn('name', $data['permissions'])
                                           ->pluck('name')
                                           ->toArray();
            $nonExistentPermissions = array_diff($data['permissions'], $existingPermissions);
            
            if (!empty($nonExistentPermissions)) {
                $errors['permissions'][] = '以下權限不存在: ' . implode(', ', $nonExistentPermissions);
            }
        }

        return $errors;
    }

    /**
     * 檢查是否為系統保護角色
     * 
     * @param Role $role
     * @return bool
     */
    protected function isSystemProtectedRole(Role $role): bool
    {
        $protectedRoles = $this->getSystemProtectedRoles();
        return in_array($role->name, $protectedRoles);
    }

    /**
     * 取得系統保護角色列表
     * 
     * @return array
     */
    protected function getSystemProtectedRoles(): array
    {
        return [
            'super_admin',
            'admin',
            'user'
        ];
    }

    /**
     * 檢查是否為保留角色名稱
     * 
     * @param string $name
     * @return bool
     */
    protected function isReservedRoleName(string $name): bool
    {
        $reservedNames = [
            'root',
            'system',
            'guest',
            'anonymous',
            'public'
        ];

        return in_array($name, $reservedNames);
    }

    /**
     * 檢查系統保護角色並拋出例外
     * 
     * @param Role $role
     * @throws \InvalidArgumentException
     */
    protected function checkSystemProtectedRole(Role $role): void
    {
        if ($this->isSystemProtectedRole($role)) {
            throw new \InvalidArgumentException('無法修改系統保護角色');
        }
    }

    /**
     * 強制刪除角色及其依賴關係
     * 
     * @param Role $role
     * @return void
     * @throws \Exception
     */
    protected function forceDeleteRoleWithDependencies(Role $role): void
    {
        // 移除使用者角色關聯
        $role->users()->detach();

        // 移除角色權限關聯
        $role->permissions()->detach();

        // 刪除相關活動記錄（如果需要）
        DB::table('activities')
          ->where('properties->role_id', $role->id)
          ->delete();

        // 刪除角色
        $role->delete();
    }

    /**
     * 驗證角色資料
     * 
     * @param array $data
     * @param int|null $roleId 更新時的角色 ID
     * @return void
     * @throws ValidationException
     */
    protected function validateRoleData(array $data, ?int $roleId = null): void
    {
        $rules = [
            'name' => [
                $roleId ? 'sometimes' : 'required',
                'string',
                'min:2',
                'max:50',
                'regex:/^[a-z_]+$/',
                'unique:roles,name' . ($roleId ? ",$roleId" : '')
            ],
            'display_name' => ($roleId ? 'sometimes' : 'required') . '|string|max:100',
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name'
        ];

        $messages = [
            'name.required' => '角色名稱為必填欄位',
            'name.min' => '角色名稱至少需要 2 個字元',
            'name.max' => '角色名稱不能超過 50 個字元',
            'name.regex' => '角色名稱只能包含小寫字母和底線',
            'name.unique' => '此角色名稱已被使用',
            'display_name.required' => '顯示名稱為必填欄位',
            'display_name.max' => '顯示名稱不能超過 100 個字元',
            'description.max' => '描述不能超過 500 個字元',
            'permissions.array' => '權限必須是陣列格式',
            'permissions.*.exists' => '指定的權限不存在'
        ];

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // 額外的業務規則驗證
        $businessErrors = $this->validateRoleCreationRules($data);
        if (!empty($businessErrors)) {
            $validator->errors()->merge($businessErrors);
            throw new ValidationException($validator);
        }
    }

    /**
     * 清除角色相關快取
     * 
     * @return void
     */
    protected function clearRoleRelatedCache(): void
    {
        // 清除權限服務的快取
        $this->permissionService->clearAllPermissionCache();
    }
}