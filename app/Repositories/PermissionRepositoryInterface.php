<?php

namespace App\Repositories;

use App\Models\Permission;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * 權限資料存取介面
 */
interface PermissionRepositoryInterface
{
    /**
     * 取得分頁權限列表
     * 
     * @param array $filters 篩選條件
     * @param int $perPage 每頁筆數
     * @return LengthAwarePaginator
     */
    public function getPaginatedPermissions(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    /**
     * 取得按模組分組的權限
     * 
     * @return Collection
     */
    public function getPermissionsByModule(): Collection;

    /**
     * 取得權限依賴關係
     * 
     * @param int $permissionId 權限 ID
     * @return Collection
     */
    public function getPermissionDependencies(int $permissionId): Collection;

    /**
     * 取得依賴此權限的其他權限
     * 
     * @param int $permissionId 權限 ID
     * @return Collection
     */
    public function getPermissionDependents(int $permissionId): Collection;

    /**
     * 建立權限
     * 
     * @param array $data 權限資料
     * @return Permission
     */
    public function createPermission(array $data): Permission;

    /**
     * 更新權限
     * 
     * @param Permission $permission 權限實例
     * @param array $data 更新資料
     * @return bool
     */
    public function updatePermission(Permission $permission, array $data): bool;

    /**
     * 刪除權限
     * 
     * @param Permission $permission 權限實例
     * @return bool
     */
    public function deletePermission(Permission $permission): bool;

    /**
     * 同步權限依賴關係
     * 
     * @param Permission $permission 權限實例
     * @param array $dependencyIds 依賴權限 ID 陣列
     * @return void
     */
    public function syncDependencies(Permission $permission, array $dependencyIds): void;

    /**
     * 取得未使用的權限
     * 
     * @return Collection
     */
    public function getUnusedPermissions(): Collection;

    /**
     * 取得權限使用統計
     * 
     * @return array
     */
    public function getPermissionUsageStats(): array;

    /**
     * 匯出權限資料
     * 
     * @return array
     */
    public function exportPermissions(): array;

    /**
     * 匯入權限資料
     * 
     * @param array $data 權限資料
     * @return array 匯入結果
     */
    public function importPermissions(array $data): array;

    /**
     * 檢查權限是否可以刪除
     * 
     * @param Permission $permission 權限實例
     * @return bool
     */
    public function canDeletePermission(Permission $permission): bool;

    /**
     * 檢查是否有循環依賴
     * 
     * @param int $permissionId 權限 ID
     * @param array $dependencyIds 依賴權限 ID 陣列
     * @return bool
     */
    public function hasCircularDependency(int $permissionId, array $dependencyIds): bool;

    /**
     * 取得權限的完整依賴鏈
     * 
     * @param int $permissionId 權限 ID
     * @return Collection
     */
    public function getFullDependencyChain(int $permissionId): Collection;

    /**
     * 取得權限的完整被依賴鏈
     * 
     * @param int $permissionId 權限 ID
     * @return Collection
     */
    public function getFullDependentChain(int $permissionId): Collection;

    /**
     * 根據模組和類型篩選權限
     * 
     * @param string|null $module 模組名稱
     * @param string|null $type 權限類型
     * @return Collection
     */
    public function getPermissionsByModuleAndType(?string $module = null, ?string $type = null): Collection;

    /**
     * 搜尋權限
     * 
     * @param string $search 搜尋關鍵字
     * @return Collection
     */
    public function searchPermissions(string $search): Collection;

    /**
     * 取得所有可用的模組
     * 
     * @return Collection
     */
    public function getAvailableModules(): Collection;

    /**
     * 取得所有可用的權限類型
     * 
     * @return Collection
     */
    public function getAvailableTypes(): Collection;
}