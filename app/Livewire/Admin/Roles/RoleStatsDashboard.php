<?php

namespace App\Livewire\Admin\Roles;

use App\Livewire\Admin\AdminComponent;
use App\Services\RoleStatisticsService;
use Livewire\Attributes\On;

/**
 * 角色統計儀表板元件
 * 
 * 在角色列表頁面顯示簡化的統計資訊
 */
class RoleStatsDashboard extends AdminComponent
{
    /**
     * 系統統計資料
     */
    public array $systemStats = [];

    /**
     * 權限分佈資料
     */
    public array $permissionDistribution = [];

    /**
     * 是否顯示詳細統計
     */
    public bool $showDetails = false;

    /**
     * 統計服務
     */
    private RoleStatisticsService $statisticsService;

    /**
     * 元件初始化
     */
    public function boot(RoleStatisticsService $statisticsService): void
    {
        $this->statisticsService = $statisticsService;
    }

    /**
     * 元件掛載
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
        try {
            $this->systemStats = $this->statisticsService->getSystemRoleStatistics();
            $this->permissionDistribution = $this->statisticsService->getPermissionDistribution();
        } catch (\Exception $e) {
            $this->addError('載入統計資料失敗：' . $e->getMessage());
        }
    }

    /**
     * 重新整理統計
     */
    public function refreshStats(): void
    {
        $this->loadStats();
        $this->addSuccess('統計資料已更新');
    }

    /**
     * 切換詳細顯示
     */
    public function toggleDetails(): void
    {
        $this->showDetails = !$this->showDetails;
    }

    /**
     * 監聽角色更新事件
     */
    #[On('role-updated')]
    #[On('role-created')]
    #[On('role-deleted')]
    #[On('permissions-updated')]
    public function handleRoleChange(): void
    {
        // 清除快取並重新載入
        $this->statisticsService->clearAllCache();
        $this->loadStats();
    }

    /**
     * 取得快速統計卡片
     */
    public function getQuickStatsProperty(): array
    {
        if (empty($this->systemStats)) {
            return [];
        }

        return [
            [
                'label' => '總角色數',
                'value' => $this->systemStats['overview']['total_roles'] ?? 0,
                'icon' => 'user-group',
                'color' => 'blue',
                'change' => null,
            ],
            [
                'label' => '啟用角色',
                'value' => $this->systemStats['overview']['active_roles'] ?? 0,
                'icon' => 'check-circle',
                'color' => 'green',
                'change' => null,
            ],
            [
                'label' => '有使用者的角色',
                'value' => $this->systemStats['overview']['roles_with_users'] ?? 0,
                'icon' => 'users',
                'color' => 'purple',
                'change' => null,
            ],
            [
                'label' => '平均權限數',
                'value' => round($this->systemStats['permissions']['avg_permissions_per_role'] ?? 0, 1),
                'icon' => 'key',
                'color' => 'yellow',
                'change' => null,
            ],
        ];
    }

    /**
     * 取得權限覆蓋率
     */
    public function getPermissionCoverageProperty(): array
    {
        if (empty($this->systemStats['permissions']['permission_coverage'])) {
            return [
                'used' => 0,
                'unused' => 0,
                'percentage' => 0,
            ];
        }

        return $this->systemStats['permissions']['permission_coverage'];
    }

    /**
     * 取得熱門角色
     */
    public function getTopRolesProperty(): array
    {
        return $this->systemStats['top_roles']['most_used'] ?? [];
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.roles.role-stats-dashboard');
    }
}