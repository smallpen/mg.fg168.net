<?php

namespace App\Livewire\Admin\Roles;

use App\Livewire\Admin\AdminComponent;
use App\Models\Role;
use App\Services\RoleStatisticsService;
use Livewire\Attributes\On;

/**
 * 角色統計元件
 * 
 * 顯示角色的詳細統計資訊和圖表
 */
class RoleStatistics extends AdminComponent
{
    /**
     * 角色實例
     */
    public ?Role $role = null;

    /**
     * 統計資料
     */
    public array $statistics = [];

    /**
     * 權限分佈資料
     */
    public array $permissionDistribution = [];

    /**
     * 系統統計資料
     */
    public array $systemStatistics = [];

    /**
     * 使用趨勢資料
     */
    public array $usageTrends = [];

    /**
     * 顯示模式：'role' 或 'system'
     */
    public string $mode = 'system';

    /**
     * 趨勢統計天數
     */
    public int $trendDays = 30;

    /**
     * 是否正在載入
     */
    public bool $loading = false;

    /**
     * 是否自動重新整理
     */
    public bool $autoRefresh = false;

    /**
     * 自動重新整理間隔（秒）
     */
    public int $refreshInterval = 300; // 5分鐘

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
    public function mount(?Role $role = null, string $mode = 'system'): void
    {
        $this->role = $role;
        $this->mode = $mode;
        
        $this->loadStatistics();
    }

    /**
     * 載入統計資料
     */
    public function loadStatistics(): void
    {
        $this->loading = true;

        try {
            if ($this->mode === 'role' && $this->role) {
                // 載入特定角色統計
                $this->statistics = $this->statisticsService->getRoleStatistics($this->role);
                $this->permissionDistribution = $this->statisticsService->getPermissionDistribution($this->role);
            } else {
                // 載入系統統計
                $this->systemStatistics = $this->statisticsService->getSystemRoleStatistics();
                $this->permissionDistribution = $this->statisticsService->getPermissionDistribution();
                $this->usageTrends = $this->statisticsService->getRoleUsageTrends($this->trendDays);
            }
        } catch (\Exception $e) {
            $this->addError('載入統計資料時發生錯誤：' . $e->getMessage());
        } finally {
            $this->loading = false;
        }
    }

    /**
     * 重新整理統計資料
     */
    public function refreshStatistics(): void
    {
        $this->loadStatistics();
        $this->dispatch('statistics-refreshed');
        $this->addSuccess('統計資料已更新');
    }

    /**
     * 清除快取並重新載入
     */
    public function clearCacheAndRefresh(): void
    {
        try {
            if ($this->role) {
                $this->statisticsService->clearRoleCache($this->role);
            } else {
                $this->statisticsService->clearAllCache();
            }
            
            $this->loadStatistics();
            $this->addSuccess('快取已清除，統計資料已更新');
        } catch (\Exception $e) {
            $this->addError('清除快取時發生錯誤：' . $e->getMessage());
        }
    }

    /**
     * 切換顯示模式
     */
    public function switchMode(string $mode): void
    {
        $this->mode = $mode;
        $this->loadStatistics();
    }

    /**
     * 設定趨勢統計天數
     */
    public function setTrendDays(int $days): void
    {
        $this->trendDays = $days;
        if ($this->mode === 'system') {
            $this->usageTrends = $this->statisticsService->getRoleUsageTrends($days);
        }
    }

    /**
     * 切換自動重新整理
     */
    public function toggleAutoRefresh(): void
    {
        $this->autoRefresh = !$this->autoRefresh;
        
        if ($this->autoRefresh) {
            $this->dispatch('start-auto-refresh', interval: $this->refreshInterval);
        } else {
            $this->dispatch('stop-auto-refresh');
        }
    }

    /**
     * 監聽自動重新整理事件
     */
    #[On('auto-refresh-tick')]
    public function handleAutoRefresh(): void
    {
        if ($this->autoRefresh) {
            $this->loadStatistics();
        }
    }

    /**
     * 監聽角色更新事件
     */
    #[On('role-updated')]
    public function handleRoleUpdated($roleId): void
    {
        if ($this->role && $this->role->id == $roleId) {
            $this->role->refresh();
            $this->loadStatistics();
        }
    }

    /**
     * 監聽權限更新事件
     */
    #[On('permissions-updated')]
    public function handlePermissionsUpdated($roleId): void
    {
        if ($this->role && $this->role->id == $roleId) {
            $this->loadStatistics();
        }
    }

    /**
     * 取得角色基本資訊
     */
    public function getRoleInfoProperty(): array
    {
        if (!$this->role) {
            return [];
        }

        return [
            'id' => $this->role->id,
            'name' => $this->role->name,
            'display_name' => $this->role->display_name,
            'description' => $this->role->description,
            'is_system_role' => $this->role->is_system_role,
            'is_active' => $this->role->is_active,
            'created_at' => $this->role->formatted_created_at,
            'updated_at' => $this->role->formatted_updated_at,
        ];
    }

    /**
     * 取得圖表配置
     */
    public function getChartConfigProperty(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'enabled' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }

    /**
     * 取得權限分佈圖表資料
     */
    public function getPermissionChartDataProperty(): array
    {
        if (empty($this->permissionDistribution['chart_data'])) {
            return [
                'labels' => [],
                'datasets' => [],
            ];
        }

        $chartData = $this->permissionDistribution['chart_data'];
        
        return [
            'labels' => $chartData['labels'],
            'datasets' => [
                [
                    'label' => '權限數量',
                    'data' => $chartData['data'],
                    'backgroundColor' => $chartData['colors'] ?? [],
                    'borderWidth' => 1,
                ],
            ],
        ];
    }

    /**
     * 取得使用趨勢圖表資料
     */
    public function getUsageTrendChartDataProperty(): array
    {
        if (empty($this->usageTrends['role_creations']['chart_data'])) {
            return [
                'labels' => [],
                'datasets' => [],
            ];
        }

        $creationData = $this->usageTrends['role_creations']['chart_data'];
        $assignmentData = $this->usageTrends['role_assignments']['chart_data'] ?? ['data' => []];
        
        return [
            'labels' => $creationData['labels'],
            'datasets' => [
                [
                    'label' => '新增角色',
                    'data' => $creationData['data'],
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.4,
                ],
                [
                    'label' => '角色指派',
                    'data' => $assignmentData['data'],
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'tension' => 0.4,
                ],
            ],
        ];
    }

    /**
     * 取得統計卡片資料
     */
    public function getStatisticsCardsProperty(): array
    {
        if ($this->mode === 'role' && !empty($this->statistics)) {
            return [
                [
                    'title' => '使用者數量',
                    'value' => $this->statistics['basic']['user_count'],
                    'icon' => 'users',
                    'color' => 'blue',
                ],
                [
                    'title' => '直接權限',
                    'value' => $this->statistics['basic']['direct_permission_count'],
                    'icon' => 'key',
                    'color' => 'green',
                ],
                [
                    'title' => '繼承權限',
                    'value' => $this->statistics['basic']['inherited_permission_count'],
                    'icon' => 'arrow-down',
                    'color' => 'yellow',
                ],
                [
                    'title' => '總權限數',
                    'value' => $this->statistics['basic']['total_permission_count'],
                    'icon' => 'shield-check',
                    'color' => 'purple',
                ],
            ];
        } elseif ($this->mode === 'system' && !empty($this->systemStatistics)) {
            return [
                [
                    'title' => '總角色數',
                    'value' => $this->systemStatistics['overview']['total_roles'],
                    'icon' => 'user-group',
                    'color' => 'blue',
                ],
                [
                    'title' => '啟用角色',
                    'value' => $this->systemStatistics['overview']['active_roles'],
                    'icon' => 'check-circle',
                    'color' => 'green',
                ],
                [
                    'title' => '系統角色',
                    'value' => $this->systemStatistics['overview']['system_roles'],
                    'icon' => 'cog',
                    'color' => 'yellow',
                ],
                [
                    'title' => '自訂角色',
                    'value' => $this->systemStatistics['overview']['custom_roles'],
                    'icon' => 'plus-circle',
                    'color' => 'purple',
                ],
            ];
        }

        return [];
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.roles.role-statistics');
    }
}