<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

/**
 * 活動服務類別
 * 
 * 處理活動記錄相關的業務邏輯
 */
class ActivityService
{
    /**
     * 快取鍵前綴
     * 
     * @var string
     */
    protected $cachePrefix = 'activity';

    /**
     * 預設快取時間（分鐘）
     * 
     * @var int
     */
    protected $defaultCacheMinutes = 5;

    /**
     * 記錄活動
     *
     * @param string $type
     * @param string $description
     * @param array $options
     * @return Activity
     */
    public function log(string $type, string $description, array $options = []): Activity
    {
        $activity = Activity::log($type, $description, $options);
        
        // 清除相關快取
        $this->clearRecentActivitiesCache();
        
        return $activity;
    }

    /**
     * 取得最近的活動記錄
     *
     * @param int $limit
     * @param bool $forceRefresh
     * @return Collection
     */
    public function getRecentActivities(int $limit = 10, bool $forceRefresh = false): Collection
    {
        $cacheKey = $this->getCacheKey("recent_activities_{$limit}");

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, $this->defaultCacheMinutes * 60, function () use ($limit) {
            return Activity::with(['user'])
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * 取得使用者的活動記錄
     *
     * @param User $user
     * @param int $limit
     * @return Collection
     */
    public function getUserActivities(User $user, int $limit = 20): Collection
    {
        $cacheKey = $this->getCacheKey("user_activities_{$user->id}_{$limit}");

        return Cache::remember($cacheKey, $this->defaultCacheMinutes * 60, function () use ($user, $limit) {
            return Activity::byUser($user->id)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * 取得活動統計資料
     *
     * @param bool $forceRefresh
     * @return array
     */
    public function getActivityStats(bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey('activity_stats');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, $this->defaultCacheMinutes * 60, function () {
            return [
                'today_activities' => Activity::today()->count(),
                'week_activities' => Activity::thisWeek()->count(),
                'total_activities' => Activity::count(),
                'active_users_today' => Activity::today()
                    ->distinct('user_id')
                    ->whereNotNull('user_id')
                    ->count(),
                'most_active_user' => $this->getMostActiveUser(),
                'activity_by_type' => $this->getActivityByType(),
                'activity_by_module' => $this->getActivityByModule(),
                'hourly_activity' => $this->getHourlyActivity(),
            ];
        });
    }

    /**
     * 取得按類型分組的活動統計
     *
     * @return array
     */
    protected function getActivityByType(): array
    {
        return Activity::selectRaw('type, COUNT(*) as count')
            ->recent(7)
            ->groupBy('type')
            ->orderBy('count', 'desc')
            ->pluck('count', 'type')
            ->toArray();
    }

    /**
     * 取得按模組分組的活動統計
     *
     * @return array
     */
    protected function getActivityByModule(): array
    {
        return Activity::selectRaw('module, COUNT(*) as count')
            ->recent(7)
            ->whereNotNull('module')
            ->groupBy('module')
            ->orderBy('count', 'desc')
            ->pluck('count', 'module')
            ->toArray();
    }

    /**
     * 取得最活躍的使用者
     *
     * @return array|null
     */
    protected function getMostActiveUser(): ?array
    {
        $result = Activity::selectRaw('user_id, COUNT(*) as activity_count')
            ->recent(7)
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderBy('activity_count', 'desc')
            ->with('user')
            ->first();

        if (!$result || !$result->user) {
            return null;
        }

        return [
            'user' => $result->user,
            'activity_count' => $result->activity_count,
        ];
    }

    /**
     * 取得每小時的活動統計
     *
     * @return array
     */
    protected function getHourlyActivity(): array
    {
        $activities = Activity::selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->today()
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        // 填充 0-23 小時的資料
        $hourlyData = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $hourlyData[$hour] = $activities[$hour] ?? 0;
        }

        return $hourlyData;
    }

    /**
     * 取得活動記錄（分頁）
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getActivitiesPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Activity::with(['user'])
            ->orderBy('created_at', 'desc');

        // 應用篩選條件
        if (!empty($filters['user_id'])) {
            $query->byUser($filters['user_id']);
        }

        if (!empty($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (!empty($filters['module'])) {
            $query->byModule($filters['module']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from']));
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }

        return $query->paginate($perPage);
    }

    /**
     * 清理舊的活動記錄
     *
     * @param int $days
     * @return int
     */
    public function cleanupOldActivities(int $days = 90): int
    {
        $deletedCount = Activity::where('created_at', '<', Carbon::now()->subDays($days))
            ->delete();

        // 清除快取
        $this->clearAllCache();

        return $deletedCount;
    }

    /**
     * 記錄使用者登入活動
     *
     * @param User $user
     * @return Activity
     */
    public function logLogin(User $user): Activity
    {
        return $this->log(
            Activity::TYPE_LOGIN,
            "使用者 {$user->name} 登入系統",
            [
                'module' => Activity::MODULE_AUTH,
                'user_id' => $user->id,
                'properties' => [
                    'username' => $user->username,
                    'login_time' => now()->toDateTimeString(),
                ]
            ]
        );
    }

    /**
     * 記錄使用者登出活動
     *
     * @param User $user
     * @return Activity
     */
    public function logLogout(User $user): Activity
    {
        return $this->log(
            Activity::TYPE_LOGOUT,
            "使用者 {$user->name} 登出系統",
            [
                'module' => Activity::MODULE_AUTH,
                'user_id' => $user->id,
                'properties' => [
                    'username' => $user->username,
                    'logout_time' => now()->toDateTimeString(),
                ]
            ]
        );
    }

    /**
     * 記錄使用者建立活動
     *
     * @param User $createdUser
     * @param User|null $creator
     * @return Activity
     */
    public function logUserCreated(User $createdUser, ?User $creator = null): Activity
    {
        $creator = $creator ?? auth()->user();
        
        return $this->log(
            Activity::TYPE_CREATE_USER,
            "建立新使用者：{$createdUser->name}",
            [
                'module' => Activity::MODULE_USERS,
                'user_id' => $creator?->id,
                'subject_id' => $createdUser->id,
                'subject_type' => User::class,
                'properties' => [
                    'created_username' => $createdUser->username,
                    'created_name' => $createdUser->name,
                    'created_email' => $createdUser->email,
                ]
            ]
        );
    }

    /**
     * 記錄使用者更新活動
     *
     * @param User $updatedUser
     * @param array $changes
     * @param User|null $updater
     * @return Activity
     */
    public function logUserUpdated(User $updatedUser, array $changes, ?User $updater = null): Activity
    {
        $updater = $updater ?? auth()->user();
        
        return $this->log(
            Activity::TYPE_UPDATE_USER,
            "更新使用者：{$updatedUser->name}",
            [
                'module' => Activity::MODULE_USERS,
                'user_id' => $updater?->id,
                'subject_id' => $updatedUser->id,
                'subject_type' => User::class,
                'properties' => [
                    'updated_username' => $updatedUser->username,
                    'changes' => $changes,
                ]
            ]
        );
    }

    /**
     * 記錄儀表板檢視活動
     *
     * @param User $user
     * @return Activity
     */
    public function logDashboardView(User $user): Activity
    {
        return $this->log(
            Activity::TYPE_VIEW_DASHBOARD,
            "檢視儀表板",
            [
                'module' => Activity::MODULE_DASHBOARD,
                'user_id' => $user->id,
            ]
        );
    }

    /**
     * 記錄快速操作活動
     *
     * @param User $user
     * @param string $route
     * @param string $actionTitle
     * @return Activity
     */
    public function logQuickAction(User $user, string $route, string $actionTitle): Activity
    {
        return $this->log(
            'quick_action',
            "使用快速操作：{$actionTitle}",
            [
                'module' => Activity::MODULE_DASHBOARD,
                'user_id' => $user->id,
                'properties' => [
                    'route' => $route,
                    'action_title' => $actionTitle,
                    'action_time' => now()->toDateTimeString(),
                ]
            ]
        );
    }

    /**
     * 清除最近活動快取
     *
     * @return void
     */
    public function clearRecentActivitiesCache(): void
    {
        $keys = [
            'recent_activities_5',
            'recent_activities_10',
            'recent_activities_15',
            'recent_activities_20',
        ];

        foreach ($keys as $key) {
            Cache::forget($this->getCacheKey($key));
        }
    }

    /**
     * 清除所有活動相關快取
     *
     * @return void
     */
    public function clearAllCache(): void
    {
        $this->clearRecentActivitiesCache();
        Cache::forget($this->getCacheKey('activity_stats'));
        
        // 清除使用者活動快取（這裡簡化處理，實際可能需要更精確的快取管理）
        Cache::flush();
    }

    /**
     * 取得快取鍵
     *
     * @param string $key
     * @return string
     */
    protected function getCacheKey(string $key): string
    {
        return "{$this->cachePrefix}.{$key}";
    }

    /**
     * 設定快取時間
     *
     * @param int $minutes
     * @return self
     */
    public function setCacheMinutes(int $minutes): self
    {
        $this->defaultCacheMinutes = $minutes;
        return $this;
    }
}