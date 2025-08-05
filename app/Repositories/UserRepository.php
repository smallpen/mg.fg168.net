<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

/**
 * 使用者資料存取層
 * 
 * 處理使用者相關的資料庫操作，包括查詢、搜尋、篩選和統計功能
 */
class UserRepository
{
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
        $totalUsers = User::count();
        $activeUsers = User::where("is_active", true)->count();
        $inactiveUsers = User::where("is_active", false)->count();
        $recentUsers = User::where("created_at", ">=", Carbon::now()->subDays(30))->count();
        
        // 按角色統計使用者數量
        $usersByRole = DB::table("users")
            ->join("user_roles", "users.id", "=", "user_roles.user_id")
            ->join("roles", "user_roles.role_id", "=", "roles.id")
            ->select("roles.display_name", DB::raw("count(*) as count"))
            ->groupBy("roles.id", "roles.display_name")
            ->orderByDesc("count")
            ->get()
            ->pluck("count", "display_name")
            ->toArray();
        $usersWithoutRoles = User::whereDoesntHave("roles")->count();

        return [
            "total_users" => $totalUsers,
            "active_users" => $activeUsers,
            "inactive_users" => $inactiveUsers,
            "recent_users" => $recentUsers,
            "users_by_role" => $usersByRole,
            "users_without_roles" => $usersWithoutRoles,
            "activity_rate" => $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 2) : 0
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
}
