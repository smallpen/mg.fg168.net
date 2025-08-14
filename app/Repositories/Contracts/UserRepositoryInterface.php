<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * 使用者資料存取層介面
 * 
 * 定義使用者相關的資料庫操作方法，包括查詢、篩選、分頁和統計功能
 */
interface UserRepositoryInterface
{
    /**
     * 取得分頁的使用者列表，支援搜尋和篩選
     *
     * @param array $filters 篩選條件
     * @param int $perPage 每頁筆數
     * @return LengthAwarePaginator
     */
    public function getPaginatedUsers(array $filters, int $perPage): LengthAwarePaginator;

    /**
     * 搜尋使用者，支援姓名、使用者名稱、電子郵件搜尋
     *
     * @param string $search 搜尋關鍵字
     * @param array $filters 額外篩選條件
     * @return Builder
     */
    public function searchUsers(string $search, array $filters): Builder;

    /**
     * 根據角色取得使用者
     *
     * @param string $role 角色名稱
     * @return Collection
     */
    public function getUsersByRole(string $role): Collection;

    /**
     * 根據狀態取得使用者
     *
     * @param bool $isActive 是否啟用
     * @return Collection
     */
    public function getUsersByStatus(bool $isActive): Collection;

    /**
     * 取得使用者統計資訊
     *
     * @return array
     */
    public function getUserStats(): array;

    /**
     * 軟刪除使用者
     *
     * @param int $userId 使用者 ID
     * @return bool
     */
    public function softDeleteUser(int $userId): bool;

    /**
     * 恢復軟刪除的使用者
     *
     * @param int $userId 使用者 ID
     * @return bool
     */
    public function restoreUser(int $userId): bool;

    /**
     * 切換使用者狀態
     *
     * @param int $userId 使用者 ID
     * @return bool
     */
    public function toggleUserStatus(int $userId): bool;

    /**
     * 啟用使用者
     *
     * @param User $user 使用者實例
     * @return bool
     */
    public function activate(User $user): bool;

    /**
     * 停用使用者
     *
     * @param User $user 使用者實例
     * @return bool
     */
    public function deactivate(User $user): bool;

    /**
     * 批量更新使用者狀態
     *
     * @param array $userIds 使用者 ID 陣列
     * @param bool $isActive 目標狀態
     * @return int 更新的筆數
     */
    public function bulkUpdateStatus(array $userIds, bool $isActive): int;

    /**
     * 取得所有可用的角色選項
     *
     * @return Collection
     */
    public function getAvailableRoles(): Collection;

    /**
     * 檢查使用者是否可以被刪除
     *
     * @param int $userId 使用者 ID
     * @return bool
     */
    public function canBeDeleted(int $userId): bool;
}