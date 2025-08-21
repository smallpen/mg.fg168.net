<?php

namespace App\Repositories\Contracts;

use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * 角色資料存取層介面
 * 
 * 定義角色相關的資料庫操作方法，包括查詢、篩選、分頁和權限管理功能
 */
interface RoleRepositoryInterface
{
    /**
     * 取得所有角色
     *
     * @return Collection
     */
    public function all(): Collection;

    /**
     * 分頁取得角色列表
     *
     * @param int $perPage 每頁筆數
     * @param array $filters 篩選條件
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * 根據 ID 尋找角色
     *
     * @param int $id 角色 ID
     * @return Role|null
     */
    public function find(int $id): ?Role;

    /**
     * 根據 ID 尋找角色，找不到則拋出例外
     *
     * @param int $id 角色 ID
     * @return Role
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Role;

    /**
     * 根據名稱尋找角色
     *
     * @param string $name 角色名稱
     * @return Role|null
     */
    public function findByName(string $name): ?Role;

    /**
     * 建立新角色
     *
     * @param array $data 角色資料
     * @return Role
     */
    public function create(array $data): Role;

    /**
     * 更新角色
     *
     * @param Role $role 角色實例
     * @param array $data 更新資料
     * @return bool
     */
    public function update(Role $role, array $data): bool;

    /**
     * 刪除角色
     *
     * @param Role $role 角色實例
     * @return bool
     * @throws \Exception
     */
    public function delete(Role $role): bool;

    /**
     * 取得角色及其權限
     *
     * @param int $id 角色 ID
     * @return Role|null
     */
    public function findWithPermissions(int $id): ?Role;

    /**
     * 取得角色及其使用者
     *
     * @param int $id 角色 ID
     * @return Role|null
     */
    public function findWithUsers(int $id): ?Role;

    /**
     * 檢查角色名稱是否已存在
     *
     * @param string $name 角色名稱
     * @param int|null $excludeId 排除的角色 ID
     * @return bool
     */
    public function nameExists(string $name, ?int $excludeId = null): bool;

    /**
     * 搜尋角色
     *
     * @param string $term 搜尋關鍵字
     * @param int $limit 限制筆數
     * @return Collection
     */
    public function search(string $term, int $limit = 10): Collection;

    /**
     * 取得可指派的角色（排除系統角色）
     *
     * @return Collection
     */
    public function getAssignableRoles(): Collection;

    /**
     * 批量更新角色狀態
     *
     * @param array $roleIds 角色 ID 陣列
     * @param bool $isActive 目標狀態
     * @return int 更新的記錄數
     */
    public function bulkUpdateStatus(array $roleIds, bool $isActive): int;

    /**
     * 取得角色統計資訊
     *
     * @return array
     */
    public function getStats(): array;

    /**
     * 複製角色及其權限
     *
     * @param Role $sourceRole 來源角色
     * @param array $newRoleData 新角色資料
     * @return Role
     * @throws \Exception
     */
    public function duplicate(Role $sourceRole, array $newRoleData): Role;

    /**
     * 取得角色的權限樹狀結構
     *
     * @param Role $role 角色實例
     * @return array
     */
    public function getPermissionTree(Role $role): array;

    /**
     * 同步角色權限
     *
     * @param Role $role 角色實例
     * @param array $permissionIds 權限 ID 陣列
     * @return void
     */
    public function syncPermissions(Role $role, array $permissionIds): void;

    /**
     * 為角色新增權限
     *
     * @param Role $role 角色實例
     * @param array $permissionIds 權限 ID 陣列
     * @return void
     */
    public function attachPermissions(Role $role, array $permissionIds): void;

    /**
     * 移除角色權限
     *
     * @param Role $role 角色實例
     * @param array $permissionIds 權限 ID 陣列
     * @return void
     */
    public function detachPermissions(Role $role, array $permissionIds): void;

    /**
     * 檢查角色是否可以被刪除
     *
     * @param Role $role 角色實例
     * @return bool
     */
    public function canBeDeleted(Role $role): bool;

    /**
     * 取得角色的使用者數量
     *
     * @param Role $role 角色實例
     * @return int
     */
    public function getUserCount(Role $role): int;

    /**
     * 取得角色的權限數量
     *
     * @param Role $role 角色實例
     * @return int
     */
    public function getPermissionCount(Role $role): int;

    /**
     * 取得分頁角色列表（支援進階篩選）
     *
     * @param array $filters 篩選條件
     * @param int $perPage 每頁筆數
     * @return LengthAwarePaginator
     */
    public function getPaginatedRoles(array $filters, int $perPage): LengthAwarePaginator;

    /**
     * 取得角色及其權限（包含繼承）
     *
     * @param int $roleId 角色 ID
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
     * 複製角色
     *
     * @param Role $role 來源角色
     * @param string $newName 新角色名稱
     * @return Role
     */
    public function duplicateRole(Role $role, string $newName): Role;

    /**
     * 檢查角色是否可以刪除
     *
     * @param Role $role 角色實例
     * @return bool
     */
    public function canDeleteRole(Role $role): bool;

    /**
     * 強制刪除角色（包含有使用者關聯的角色）
     *
     * @param Role $role 角色實例
     * @return bool
     * @throws \Exception
     */
    public function forceDelete(Role $role): bool;
}