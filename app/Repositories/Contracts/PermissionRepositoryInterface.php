<?php

namespace App\Repositories\Contracts;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;

/**
 * 權限資料存取層介面
 * 
 * 定義權限相關的資料庫操作方法，包括查詢、篩選、分頁和模組管理功能
 */
interface PermissionRepositoryInterface
{
    /**
     * 取得所有權限
     *
     * @return Collection
     */
    public function all(): Collection;

    /**
     * 分頁取得權限列表
     *
     * @param int $perPage 每頁筆數
     * @param array $filters 篩選條件
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * 根據 ID 尋找權限
     *
     * @param int $id 權限 ID
     * @return Permission|null
     */
    public function find(int $id): ?Permission;

    /**
     * 根據 ID 尋找權限，找不到則拋出例外
     *
     * @param int $id 權限 ID
     * @return Permission
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Permission;

    /**
     * 根據名稱尋找權限
     *
     * @param string $name 權限名稱
     * @return Permission|null
     */
    public function findByName(string $name): ?Permission;

    /**
     * 建立新權限
     *
     * @param array $data 權限資料
     * @return Permission
     */
    public function create(array $data): Permission;

    /**
     * 更新權限
     *
     * @param Permission $permission 權限實例
     * @param array $data 更新資料
     * @return bool
     */
    public function update(Permission $permission, array $data): bool;

    /**
     * 刪除權限
     *
     * @param Permission $permission 權限實例
     * @return bool
     * @throws \Exception
     */
    public function delete(Permission $permission): bool;

    /**
     * 取得權限及其角色
     *
     * @param int $id 權限 ID
     * @return Permission|null
     */
    public function findWithRoles(int $id): ?Permission;

    /**
     * 檢查權限名稱是否已存在
     *
     * @param string $name 權限名稱
     * @param int|null $excludeId 排除的權限 ID
     * @return bool
     */
    public function nameExists(string $name, ?int $excludeId = null): bool;

    /**
     * 根據模組取得權限
     *
     * @param string $module 模組名稱
     * @return Collection
     */
    public function getByModule(string $module): Collection;

    /**
     * 取得所有模組列表
     *
     * @return SupportCollection
     */
    public function getAllModules(): SupportCollection;

    /**
     * 取得權限按模組分組
     *
     * @return Collection
     */
    public function getAllGroupedByModule(): Collection;

    /**
     * 搜尋權限
     *
     * @param string $term 搜尋關鍵字
     * @param int $limit 限制筆數
     * @return Collection
     */
    public function search(string $term, int $limit = 10): Collection;

    /**
     * 批量建立權限
     *
     * @param array $permissions 權限資料陣列
     * @return bool
     * @throws \Exception
     */
    public function bulkCreate(array $permissions): bool;

    /**
     * 批量刪除權限
     *
     * @param array $permissionIds 權限 ID 陣列
     * @return int 刪除的記錄數
     * @throws \Exception
     */
    public function bulkDelete(array $permissionIds): int;

    /**
     * 取得權限統計資訊
     *
     * @return array
     */
    public function getStats(): array;

    /**
     * 取得模組的權限樹狀結構
     *
     * @param string $module 模組名稱
     * @return array
     */
    public function getModulePermissionTree(string $module): array;

    /**
     * 取得權限矩陣（所有模組和權限的結構化資料）
     *
     * @return array
     */
    public function getPermissionMatrix(): array;

    /**
     * 根據角色 ID 取得權限
     *
     * @param int $roleId 角色 ID
     * @return Collection
     */
    public function getByRoleId(int $roleId): Collection;

    /**
     * 取得未分配給任何角色的權限
     *
     * @return Collection
     */
    public function getUnassignedPermissions(): Collection;

    /**
     * 解析權限依賴關係
     *
     * @param array $permissionIds 權限 ID 陣列
     * @return array 包含依賴關係的權限 ID 陣列
     */
    public function resolveDependencies(array $permissionIds): array;

    /**
     * 取得權限的依賴權限
     *
     * @param Permission $permission 權限實例
     * @return Collection
     */
    public function getDependencies(Permission $permission): Collection;

    /**
     * 取得依賴於指定權限的其他權限
     *
     * @param Permission $permission 權限實例
     * @return Collection
     */
    public function getDependents(Permission $permission): Collection;

    /**
     * 檢查權限是否可以被刪除（沒有其他權限依賴它）
     *
     * @param Permission $permission 權限實例
     * @return bool
     */
    public function canBeDeleted(Permission $permission): bool;

    /**
     * 取得權限的角色數量
     *
     * @param Permission $permission 權限實例
     * @return int
     */
    public function getRoleCount(Permission $permission): int;

    /**
     * 驗證權限組合的有效性
     *
     * @param array $permissionIds 權限 ID 陣列
     * @return array 驗證結果，包含錯誤訊息
     */
    public function validatePermissionCombination(array $permissionIds): array;

    /**
     * 取得權限依賴關係圖
     *
     * @return array
     */
    public function getPermissionDependencies(): array;
}