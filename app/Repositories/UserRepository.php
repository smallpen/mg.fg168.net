<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Role;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Services\UserCacheService;

/**
 * 使用者資料存取層
 * 
 * 處理使用者相關的資料庫操作，包括查詢、搜尋、篩選和統計功能
 */
class UserRepository implements UserRepositoryInterface
{
    protected UserCacheService $cacheService;

    public function __construct(UserCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }
    /**
     * 取得所有使用者
     *
     * @return Collection
     */
    public function all(): Collection
    {
        return User::with("roles")->orderBy("username")->get();
    }

    /**
     * 分頁取得使用者列表
     *
     * @param int $perPage
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = User::query()->with("roles");

        // 搜尋過濾
        if (!empty($filters["search"])) {
            $search = $filters["search"];
            $query->where(function ($q) use ($search) {
                $q->where("username", "like", "%{$search}%")
                  ->orWhere("name", "like", "%{$search}%")
                  ->orWhere("email", "like", "%{$search}%");
            });
        }

        // 角色過濾
        if (!empty($filters["role"])) {
            $query->whereHas("roles", function ($q) use ($filters) {
                $q->where("name", $filters["role"]);
            });
        }

        // 狀態過濾
        if (isset($filters["is_active"])) {
            $query->where("is_active", $filters["is_active"]);
        }

        return $query->orderBy("username")->paginate($perPage);
    }

    /**
     * 根據 ID 尋找使用者
     *
     * @param int $id
     * @return User|null
     */
    public function find(int $id): ?User
    {
        return User::find($id);
    }

    /**
     * 根據使用者名稱尋找使用者
     *
     * @param string $username
     * @return User|null
     */
    public function findByUsername(string $username): ?User
    {
        return User::where("username", $username)->first();
    }

    /**
     * 根據電子郵件尋找使用者
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User
    {
        return User::where("email", $email)->first();
    }

    /**
     * 建立新使用者
     *
     * @param array $data
     * @return User
     */
    public function create(array $data): User
    {
        if (isset($data["password"])) {
            $data["password"] = Hash::make($data["password"]);
        }

        return User::create($data);
    }

    /**
     * 更新使用者
     *
     * @param User $user
     * @param array $data
     * @return bool
     */
    public function update(User $user, array $data): bool
    {
        if (isset($data["password"]) && !empty($data["password"])) {
            $data["password"] = Hash::make($data["password"]);
        } else {
            unset($data["password"]);
        }

        return $user->update($data);
    }

    /**
     * 刪除使用者
     *
     * @param User $user
     * @return bool
     */
    public function delete(User $user): bool
    {
        try {
            DB::beginTransaction();
            $user->roles()->detach();
            $result = $user->delete();
            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 搜尋使用者
     *
     * @param string $term
     * @param int $limit
     * @return Collection
     */
    public function search(string $term, int $limit = 10): Collection
    {
        return User::where("username", "like", "%{$term}%")
                  ->orWhere("name", "like", "%{$term}%")
                  ->orWhere("email", "like", "%{$term}%")
                  ->limit($limit)
                  ->get();
    }
    /**
     * 根據角色取得使用者
     *
     * @param string $roleName
     * @return Collection
     */
    public function getByRole(string $roleName): Collection
    {
        return User::whereHas("roles", function ($query) use ($roleName) {
            $query->where("name", $roleName);
        })->with("roles")->get();
    }

    /**
     * 檢查使用者名稱是否已存在
     *
     * @param string $username
     * @param int|null $excludeId
     * @return bool
     */
    public function usernameExists(string $username, ?int $excludeId = null): bool
    {
        $query = User::where("username", $username);
        
        if ($excludeId) {
            $query->where("id", "!=", $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * 檢查電子郵件是否已存在
     *
     * @param string $email
     * @param int|null $excludeId
     * @return bool
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $query = User::where("email", $email);
        
        if ($excludeId) {
            $query->where("id", "!=", $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * 取得啟用的使用者
     *
     * @return Collection
     */
    public function getActiveUsers(): Collection
    {
        return User::where("is_active", true)
                  ->with("roles")
                  ->orderBy("username")
                  ->get();
    }

    /**
     * 取得停用的使用者
     *
     * @return Collection
     */
    public function getInactiveUsers(): Collection
    {
        return User::where("is_active", false)
                  ->with("roles")
                  ->orderBy("username")
                  ->get();
    }

    /**
     * 取得最近註冊的使用者
     *
     * @param int $limit
     * @param int $days
     * @return Collection
     */
    public function getRecentUsers(int $limit = 10, int $days = 30): Collection
    {
        return User::where("created_at", ">=", Carbon::now()->subDays($days))
                  ->with("roles")
                  ->orderByDesc("created_at")
                  ->limit($limit)
                  ->get();
    }

    /**
     * 取得使用者統計資訊
     *
     * @return array
     */
    public function getStats(): array
    {
        // 使用單一查詢取得基本統計
        $basicStats = User::selectRaw('
            COUNT(*) as total_users,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users,
            SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_users,
            SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as recent_users
        ', [Carbon::now()->subDays(30)])
        ->first();

        $totalUsers = $basicStats->total_users;
        $activeUsers = $basicStats->active_users;
        $inactiveUsers = $basicStats->inactive_users;
        $recentUsers = $basicStats->recent_users;
        
        // 按角色統計使用者數量（優化查詢）
        $usersByRole = DB::table("users")
            ->join("user_roles", "users.id", "=", "user_roles.user_id")
            ->join("roles", "user_roles.role_id", "=", "roles.id")
            ->select("roles.display_name", DB::raw("count(DISTINCT users.id) as count"))
            ->where("users.deleted_at", null) // 排除軟刪除的使用者
            ->groupBy("roles.id", "roles.display_name")
            ->orderByDesc("count")
            ->get()
            ->pluck("count", "display_name")
            ->toArray();

        // 計算沒有角色的使用者數量
        $usersWithoutRoles = User::whereDoesntHave("roles")->count();

        // 計算活躍率
        $activityRate = $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 2) : 0;

        // 計算成長率（與上個月比較）
        $lastMonthUsers = User::where("created_at", ">=", Carbon::now()->subDays(60))
                             ->where("created_at", "<", Carbon::now()->subDays(30))
                             ->count();
        
        $growthRate = $lastMonthUsers > 0 ? 
            round((($recentUsers - $lastMonthUsers) / $lastMonthUsers) * 100, 2) : 
            ($recentUsers > 0 ? 100 : 0);

        return [
            "total_users" => $totalUsers,
            "active_users" => $activeUsers,
            "inactive_users" => $inactiveUsers,
            "recent_users" => $recentUsers,
            "users_by_role" => $usersByRole,
            "users_without_roles" => $usersWithoutRoles,
            "activity_rate" => $activityRate,
            "growth_rate" => $growthRate,
            "last_updated" => now()->toISOString()
        ];
    }

    /**
     * 批量更新使用者狀態
     *
     * @param array $userIds
     * @param bool $isActive
     * @return int
     */
    public function bulkUpdateStatus(array $userIds, bool $isActive): int
    {
        return User::whereIn("id", $userIds)
                  ->update(["is_active" => $isActive]);
    }

    /**
     * 重設使用者密碼
     *
     * @param User $user
     * @param string $newPassword
     * @return bool
     */
    public function resetPassword(User $user, string $newPassword): bool
    {
        return $user->update([
            "password" => Hash::make($newPassword)
        ]);
    }

    /**
     * 更新使用者偏好設定
     *
     * @param User $user
     * @param array $preferences
     * @return bool
     */
    public function updatePreferences(User $user, array $preferences): bool
    {
        $allowedPreferences = ["theme_preference", "locale"];
        $updateData = array_intersect_key($preferences, array_flip($allowedPreferences));
        
        return $user->update($updateData);
    }

    /**
     * 取得分頁的使用者列表，支援搜尋和篩選
     *
     * @param array $filters 篩選條件
     * @param int $perPage 每頁筆數
     * @return LengthAwarePaginator
     */
    public function getPaginatedUsers(array $filters, int $perPage): LengthAwarePaginator
    {
        // 如果沒有搜尋條件，使用快取
        if (empty($filters['search'])) {
            $cacheKey = $this->cacheService->buildQueryCacheKey('users_paginated', [
                'status' => $filters['status'] ?? 'all',
                'role' => $filters['role'] ?? 'all',
                'sort_field' => $filters['sort_field'] ?? 'created_at',
                'sort_direction' => $filters['sort_direction'] ?? 'desc',
                'per_page' => $perPage,
                'page' => request()->get('page', 1)
            ]);
            
            return $this->cacheService->rememberWithTags(
                [UserCacheService::CACHE_TAGS['users_paginated']], 
                $cacheKey, 
                function () use ($filters, $perPage) {
                    return $this->buildUserQuery($filters)->paginate($perPage);
                },
                UserCacheService::CACHE_TTL['users_paginated']
            );
        }
        
        // 有搜尋條件時使用短期快取
        $searchCacheKey = $this->cacheService->buildQueryCacheKey('users_search', [
            'search' => $filters['search'],
            'status' => $filters['status'] ?? 'all',
            'role' => $filters['role'] ?? 'all',
            'sort_field' => $filters['sort_field'] ?? 'created_at',
            'sort_direction' => $filters['sort_direction'] ?? 'desc',
            'per_page' => $perPage,
            'page' => request()->get('page', 1)
        ]);
        
        return Cache::remember($searchCacheKey, UserCacheService::CACHE_TTL['user_search'], function () use ($filters, $perPage) {
            return $this->buildUserQuery($filters)->paginate($perPage);
        });
    }

    /**
     * 建立使用者查詢
     *
     * @param array $filters 篩選條件
     * @return Builder
     */
    private function buildUserQuery(array $filters): Builder
    {
        $query = User::select([
                'users.id',
                'users.username', 
                'users.name',
                'users.email',
                'users.avatar',
                'users.is_active',
                'users.created_at',
                'users.updated_at',
                'users.last_login_at'
            ])
            ->with(['roles:id,name,display_name']);

        // 搜尋優化：使用索引友好的查詢
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                // 優先使用精確匹配，然後是前綴匹配，最後是模糊匹配
                $q->where('username', $search)
                  ->orWhere('email', $search)
                  ->orWhere('name', $search)
                  ->orWhere('username', 'like', "{$search}%")
                  ->orWhere('email', 'like', "{$search}%")
                  ->orWhere('name', 'like', "{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // 狀態篩選
        if (isset($filters['status']) && $filters['status'] !== 'all') {
            $query->where('is_active', $filters['status'] === 'active');
        }

        // 角色篩選優化：使用 EXISTS 子查詢
        if (isset($filters['role']) && $filters['role'] !== 'all') {
            $query->whereExists(function ($subQuery) use ($filters) {
                $subQuery->select(DB::raw(1))
                        ->from('user_roles')
                        ->join('roles', 'user_roles.role_id', '=', 'roles.id')
                        ->whereColumn('user_roles.user_id', 'users.id')
                        ->where('roles.name', $filters['role']);
            });
        }

        // 排序優化
        $sortField = $filters['sort_field'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        
        // 為分頁優化添加 ID 作為次要排序
        if ($sortField !== 'id') {
            $query->orderBy($sortField, $sortDirection)
                  ->orderBy('id', $sortDirection);
        } else {
            $query->orderBy('id', $sortDirection);
        }

        return $query;
    }



    /**
     * 搜尋使用者，支援姓名、使用者名稱、電子郵件搜尋
     *
     * @param string $search 搜尋關鍵字
     * @param array $filters 額外篩選條件
     * @return Builder
     */
    public function searchUsers(string $search, array $filters): Builder
    {
        return User::with(['roles:id,name,display_name'])
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('username', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                if ($status !== 'all') {
                    $query->where('is_active', $status === 'active');
                }
            })
            ->when($filters['role'] ?? null, function ($query, $role) {
                if ($role !== 'all') {
                    $query->whereHas('roles', function ($q) use ($role) {
                        $q->where('name', $role);
                    });
                }
            });
    }

    /**
     * 根據角色取得使用者
     *
     * @param string $role 角色名稱
     * @return Collection
     */
    public function getUsersByRole(string $role): Collection
    {
        return $this->getByRole($role);
    }

    /**
     * 根據狀態取得使用者
     *
     * @param bool $isActive 是否啟用
     * @return Collection
     */
    public function getUsersByStatus(bool $isActive): Collection
    {
        return User::with(['roles:id,name,display_name'])
            ->where('is_active', $isActive)
            ->orderBy('username')
            ->get();
    }

    /**
     * 取得使用者統計資訊
     *
     * @return array
     */
    public function getUserStats(): array
    {
        return $this->cacheService->remember(
            UserCacheService::CACHE_KEYS['user_stats'], 
            function () {
                return $this->getStats();
            },
            UserCacheService::CACHE_TTL['user_stats']
        );
    }

    /**
     * 軟刪除使用者
     *
     * @param int $userId 使用者 ID
     * @return bool
     */
    public function softDeleteUser(int $userId): bool
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        try {
            DB::beginTransaction();
            
            // 檢查是否可以刪除
            if (!$this->canBeDeleted($userId)) {
                DB::rollBack();
                return false;
            }

            // 先停用使用者
            $user->update(['is_active' => false]);
            
            // 移除所有角色關聯
            $user->roles()->detach();
            
            // 執行軟刪除
            $result = $user->delete();
            
            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 恢復軟刪除的使用者
     *
     * @param int $userId 使用者 ID
     * @return bool
     */
    public function restoreUser(int $userId): bool
    {
        $user = User::withTrashed()->find($userId);
        if (!$user) {
            return false;
        }

        return $user->restore();
    }

    /**
     * 切換使用者狀態
     *
     * @param int $userId 使用者 ID
     * @return bool
     */
    public function toggleUserStatus(int $userId): bool
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        return $user->update(['is_active' => !$user->is_active]);
    }

    /**
     * 啟用使用者
     *
     * @param User $user 使用者實例
     * @return bool
     */
    public function activate(User $user): bool
    {
        return $user->update(['is_active' => true]);
    }

    /**
     * 停用使用者
     *
     * @param User $user 使用者實例
     * @return bool
     */
    public function deactivate(User $user): bool
    {
        return $user->update(['is_active' => false]);
    }

    /**
     * 取得所有可用的角色選項
     *
     * @return Collection
     */
    public function getAvailableRoles(): Collection
    {
        return $this->cacheService->remember(
            UserCacheService::CACHE_KEYS['user_roles_list'], 
            function () {
                return Role::select('id', 'name', 'display_name')
                          ->orderBy('display_name')
                          ->get();
            },
            UserCacheService::CACHE_TTL['user_roles']
        );
    }

    /**
     * 清除角色列表快取
     *
     * @return void
     */
    public function clearRolesCache(): void
    {
        $this->cacheService->clearRoles();
    }

    /**
     * 清除使用者相關快取
     *
     * @return void
     */
    public function clearUserCache(): void
    {
        $this->cacheService->clearAll();
    }

    /**
     * 檢查使用者是否可以被刪除
     *
     * @param int $userId 使用者 ID
     * @return bool
     */
    public function canBeDeleted(int $userId): bool
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        // 檢查是否為超級管理員
        if ($user->isSuperAdmin()) {
            return false;
        }

        // 檢查是否為當前登入使用者
        if (auth()->check() && auth()->id() === $userId) {
            return false;
        }

        // 可以在這裡添加其他業務邏輯檢查
        // 例如：檢查是否有關聯的重要資料

        return true;
    }
}
