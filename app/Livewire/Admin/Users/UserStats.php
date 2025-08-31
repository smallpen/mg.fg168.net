<?php

namespace App\Livewire\Admin\Users;

use App\Repositories\UserRepository;
use App\Services\UserCacheService;
use App\Traits\HandlesLivewireErrors;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Cache;

/**
 * 使用者統計資訊 Livewire 元件
 * 
 * 顯示使用者相關的統計資訊，包含總數、狀態分佈、角色分佈等
 */
class UserStats extends Component
{
    use HandlesLivewireErrors;

    // 統計資料屬性
    public array $stats = [];
    
    // 是否顯示詳細統計
    public bool $showDetails = false;
    
    // 是否正在載入
    public bool $isLoading = true;

    /**
     * 取得 UserRepository 實例
     */
    protected function getUserRepository(): UserRepository
    {
        return app(UserRepository::class);
    }

    /**
     * 取得 UserCacheService 實例
     */
    protected function getCacheService(): UserCacheService
    {
        return app(UserCacheService::class);
    }

    /**
     * 元件掛載時載入統計資料
     */
    public function mount(): void
    {
        $this->loadStats();
    }

    /**
     * 載入統計資料
     */
    public function loadStats(): void
    {
        $this->isLoading = true;
        
        $result = $this->safeExecute(function () {
            $this->stats = $this->getUserRepository()->getUserStats();
            $this->isLoading = false;
            return true;
        }, 'load_user_stats', [
            'component' => 'UserStats',
            'action' => 'loadStats',
        ]);
        
        // 如果執行失敗，載入預設統計資料
        if (!$result) {
            $this->loadDefaultStats();
        }
    }

    /**
     * 載入預設統計資料（當正常載入失敗時使用）
     */
    protected function loadDefaultStats(): void
    {
        $this->stats = [
            'total_users' => 0,
            'active_users' => 0,
            'inactive_users' => 0,
            'recent_users' => 0,
            'activity_rate' => 0,
            'users_by_role' => [],
            'users_by_status' => [
                'active' => 0,
                'inactive' => 0,
            ],
            'growth_rate' => 0,
            'last_updated' => now()->toISOString(),
        ];
        
        $this->isLoading = false;
        
        // 記錄載入預設資料的事件
        logger()->warning('UserStats: 載入預設統計資料', [
            'component' => 'UserStats',
            'reason' => '正常統計資料載入失敗',
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * 切換詳細統計顯示
     */
    public function toggleDetails(): void
    {
        $this->showDetails = !$this->showDetails;
    }

    /**
     * 重新整理統計資料
     */
    public function refreshStats(): void
    {
        // 清除快取
        $this->getCacheService()->clearStats();
        
        // 重新載入統計資料
        $this->loadStats();
        
        $this->dispatch('show-toast', [
            'type' => 'success',
            'message' => __('admin.users.stats_refreshed')
        ]);
    }

    /**
     * 監聽使用者狀態更新事件
     */
    #[On('user-status-updated')]
    public function handleUserStatusUpdate(): void
    {
        $this->refreshStats();
    }

    /**
     * 監聽使用者批量更新事件
     */
    #[On('users-bulk-updated')]
    public function handleUsersBulkUpdate(): void
    {
        $this->refreshStats();
    }

    /**
     * 監聽使用者建立事件
     */
    #[On('user-created')]
    public function handleUserCreated(): void
    {
        $this->refreshStats();
    }

    /**
     * 監聽使用者刪除事件
     */
    #[On('user-deleted')]
    public function handleUserDeleted(): void
    {
        $this->refreshStats();
    }

    /**
     * 取得格式化的活躍率
     */
    public function getFormattedActivityRate(): string
    {
        $rate = $this->stats['activity_rate'] ?? 0;
        return number_format($rate, 1) . '%';
    }

    /**
     * 取得活躍率的顏色類別
     */
    public function getActivityRateColorClass(): string
    {
        $rate = $this->stats['activity_rate'] ?? 0;
        
        if ($rate >= 80) {
            return 'text-green-600 dark:text-green-400';
        } elseif ($rate >= 60) {
            return 'text-yellow-600 dark:text-yellow-400';
        } else {
            return 'text-red-600 dark:text-red-400';
        }
    }

    /**
     * 取得最受歡迎的角色
     */
    public function getTopRole(): array
    {
        $usersByRole = $this->stats['users_by_role'] ?? [];
        
        if (empty($usersByRole)) {
            return [
                'name' => __('admin.users.no_roles'),
                'count' => 0
            ];
        }

        $topRole = array_keys($usersByRole)[0];
        $topCount = $usersByRole[$topRole];

        return [
            'name' => $topRole,
            'count' => $topCount
        ];
    }

    /**
     * 取得角色分佈的百分比
     */
    public function getRoleDistribution(): array
    {
        $usersByRole = $this->stats['users_by_role'] ?? [];
        $totalUsers = $this->stats['total_users'] ?? 1; // 避免除以零
        
        $distribution = [];
        foreach ($usersByRole as $role => $count) {
            $percentage = ($count / $totalUsers) * 100;
            $distribution[] = [
                'role' => $role,
                'count' => $count,
                'percentage' => round($percentage, 1)
            ];
        }

        return $distribution;
    }

    /**
     * 取得統計卡片資料
     */
    public function getStatsCards(): array
    {
        return [
            [
                'title' => __('admin.users.total_users'),
                'value' => $this->stats['total_users'] ?? 0,
                'icon' => 'users',
                'color' => 'blue',
                'description' => __('admin.users.total_users_desc')
            ],
            [
                'title' => __('admin.users.active_users'),
                'value' => $this->stats['active_users'] ?? 0,
                'icon' => 'check-circle',
                'color' => 'green',
                'description' => __('admin.users.active_users_desc')
            ],
            [
                'title' => __('admin.users.inactive_users'),
                'value' => $this->stats['inactive_users'] ?? 0,
                'icon' => 'x-circle',
                'color' => 'red',
                'description' => __('admin.users.inactive_users_desc')
            ],
            [
                'title' => __('admin.users.recent_users'),
                'value' => $this->stats['recent_users'] ?? 0,
                'icon' => 'clock',
                'color' => 'purple',
                'description' => __('admin.users.recent_users_desc')
            ]
        ];
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.users.user-stats', [
            'statsCards' => $this->getStatsCards(),
            'roleDistribution' => $this->getRoleDistribution(),
            'topRole' => $this->getTopRole(),
            'activityRate' => $this->getFormattedActivityRate(),
            'activityRateColor' => $this->getActivityRateColorClass(),
        ]);
    }
}