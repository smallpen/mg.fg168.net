<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * 活動記錄服務
 * 
 * 記錄和追蹤系統中的使用者活動
 */
class ActivityLogger
{
    /**
     * 快取鍵前綴
     * 
     * @var string
     */
    protected $cachePrefix = 'activity_log';

    /**
     * 記錄使用者活動
     * 
     * @param string $type 活動類型
     * @param string $description 活動描述
     * @param array $details 活動詳細資訊
     * @param int|null $userId 使用者 ID
     * @return void
     */
    public function log(string $type, string $description, array $details = [], ?int $userId = null): void
    {
        $activity = [
            'type' => $type,
            'description' => $description,
            'details' => $details,
            'user_id' => $userId ?? auth()->id(),
            'user_name' => $userId ? $this->getUserName($userId) : (auth()->user()?->display_name ?? '系統'),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now(),
            'session_id' => session()->getId(),
        ];

        // 記錄到日誌檔案
        Log::channel('activity')->info('User Activity', $activity);

        // 儲存到快取中供儀表板顯示
        $this->cacheActivity($activity);
    }

    /**
     * 記錄使用者登入活動
     * 
     * @param int $userId
     * @return void
     */
    public function logLogin(int $userId): void
    {
        $this->log(
            'user_login',
            '使用者登入系統',
            [
                'login_method' => 'username_password',
                'success' => true
            ],
            $userId
        );
    }

    /**
     * 記錄使用者登出活動
     * 
     * @param int $userId
     * @return void
     */
    public function logLogout(int $userId): void
    {
        $this->log(
            'user_logout',
            '使用者登出系統',
            [
                'logout_method' => 'manual',
                'session_duration' => $this->calculateSessionDuration()
            ],
            $userId
        );
    }

    /**
     * 記錄使用者建立活動
     * 
     * @param int $createdUserId 被建立的使用者 ID
     * @param int|null $creatorId 建立者 ID
     * @return void
     */
    public function logUserCreated(int $createdUserId, ?int $creatorId = null): void
    {
        $createdUser = \App\Models\User::find($createdUserId);
        
        $this->log(
            'user_created',
            "建立新使用者：{$createdUser?->display_name}",
            [
                'created_user_id' => $createdUserId,
                'created_username' => $createdUser?->username,
                'assigned_roles' => $createdUser?->roles->pluck('name')->toArray() ?? []
            ],
            $creatorId
        );
    }

    /**
     * 記錄使用者更新活動
     * 
     * @param int $updatedUserId 被更新的使用者 ID
     * @param array $changes 變更內容
     * @param int|null $updaterId 更新者 ID
     * @return void
     */
    public function logUserUpdated(int $updatedUserId, array $changes, ?int $updaterId = null): void
    {
        $updatedUser = \App\Models\User::find($updatedUserId);
        
        $this->log(
            'user_updated',
            "更新使用者：{$updatedUser?->display_name}",
            [
                'updated_user_id' => $updatedUserId,
                'changes' => $changes,
                'fields_changed' => array_keys($changes)
            ],
            $updaterId
        );
    }

    /**
     * 記錄角色建立活動
     * 
     * @param int $roleId 角色 ID
     * @param int|null $creatorId 建立者 ID
     * @return void
     */
    public function logRoleCreated(int $roleId, ?int $creatorId = null): void
    {
        $role = \App\Models\Role::find($roleId);
        
        $this->log(
            'role_created',
            "建立新角色：{$role?->display_name}",
            [
                'role_id' => $roleId,
                'role_name' => $role?->name,
                'permissions_count' => $role?->permissions->count() ?? 0
            ],
            $creatorId
        );
    }

    /**
     * 記錄權限變更活動
     * 
     * @param int $roleId 角色 ID
     * @param array $addedPermissions 新增的權限
     * @param array $removedPermissions 移除的權限
     * @param int|null $updaterId 更新者 ID
     * @return void
     */
    public function logPermissionsChanged(int $roleId, array $addedPermissions, array $removedPermissions, ?int $updaterId = null): void
    {
        $role = \App\Models\Role::find($roleId);
        
        $this->log(
            'permissions_changed',
            "變更角色權限：{$role?->display_name}",
            [
                'role_id' => $roleId,
                'added_permissions' => $addedPermissions,
                'removed_permissions' => $removedPermissions,
                'total_permissions' => $role?->permissions->count() ?? 0
            ],
            $updaterId
        );
    }

    /**
     * 記錄系統活動
     * 
     * @param string $type 活動類型
     * @param string $description 活動描述
     * @param array $details 活動詳細資訊
     * @return void
     */
    public function logSystemActivity(string $type, string $description, array $details = []): void
    {
        $this->log($type, $description, $details, null);
    }

    /**
     * 取得最近的活動記錄
     * 
     * @param int $limit 限制數量
     * @return array
     */
    public function getRecentActivities(int $limit = 20): array
    {
        $cacheKey = "{$this->cachePrefix}.recent.{$limit}";
        
        return Cache::get($cacheKey, []);
    }

    /**
     * 清除活動記錄快取
     * 
     * @return void
     */
    public function clearCache(): void
    {
        $keys = [
            "{$this->cachePrefix}.recent.10",
            "{$this->cachePrefix}.recent.20",
            "{$this->cachePrefix}.recent.50",
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * 將活動記錄儲存到快取
     * 
     * @param array $activity
     * @return void
     */
    protected function cacheActivity(array $activity): void
    {
        $limits = [10, 20, 50];
        
        foreach ($limits as $limit) {
            $cacheKey = "{$this->cachePrefix}.recent.{$limit}";
            $activities = Cache::get($cacheKey, []);
            
            // 新增活動到陣列開頭
            array_unshift($activities, $activity);
            
            // 限制陣列大小
            $activities = array_slice($activities, 0, $limit);
            
            // 儲存回快取（快取 1 小時）
            Cache::put($cacheKey, $activities, 3600);
        }
    }

    /**
     * 取得使用者名稱
     * 
     * @param int $userId
     * @return string
     */
    protected function getUserName(int $userId): string
    {
        $user = \App\Models\User::find($userId);
        return $user?->display_name ?? "使用者 #{$userId}";
    }

    /**
     * 計算 session 持續時間
     * 
     * @return int 秒數
     */
    protected function calculateSessionDuration(): int
    {
        $loginTime = session('login_time');
        
        if (!$loginTime) {
            return 0;
        }
        
        return now()->diffInSeconds($loginTime);
    }
}