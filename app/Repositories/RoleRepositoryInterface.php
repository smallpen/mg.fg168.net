<?php

namespace App\Repositories;

use App\Models\Role;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * 角色資料存取介面
 */
interface RoleRepositoryInterface
{
    /**
     * 取得分頁角色列表
     * 
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginatedRoles(array $filters, int $perPage): LengthAwarePaginator;

    /**
     * 取得角色及其權限
     * 
     * @param int $roleId
     * @return Role
     */
    public function getRoleWithPermissions(int $roleId): Role;

    /**
     * 取得角色層級結構
     * 
     * @return Collection
     */
    public function getRoleHierarchy(): Collection;

    /**
     * 建立角色
     * 
     * @param array $data
     * @return Role
     */
    public function createRole(array $data): Role;

    /**
     * 更新角色
     * 
     * @param Role $role
     * @param array $data
     * @return bool
     */
    public function updateRole(Role $role, array $data): bool;

    /**
     * 刪除角色
     * 
     * @param Role $role
     * @return bool
     */
    public function deleteRole(Role $role): bool;

    /**
     * 複製角色
     * 
     * @param Role $role
     * @param string $newName
     * @return Role
     */
    public function duplicateRole(Role $role, string $newName): Role;

    /**
     * 同步角色權限
     * 
     * @param Role $role
     * @param array $permissionIds
     * @return void
     */
    public function syncPermissions(Role $role, array $permissionIds): void;

    /**
     * 取得角色統計
     * 
     * @return array
     */
    public function getRoleStats(): array;

    /**
     * 檢查角色是否可以刪除
     * 
     * @param Role $role
     * @return bool
     */
    public function canDeleteRole(Role $role): bool;
}