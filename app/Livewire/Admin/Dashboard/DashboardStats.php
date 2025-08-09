<?php

namespace App\Livewire\Admin\Dashboard;

use Livewire\Component;
use App\Services\DashboardService;

/**
 * 儀表板統計元件
 * 
 * 顯示系統統計資訊，包含使用者數量、角色數量等
 */
class DashboardStats extends Component
{
    /**
     * 統計資料
     * 
     * @var array
     */
    public $stats = [];

    /**
     * 儀表板服務
     * 
     * @var DashboardService
     */
    protected $dashboardService;

    /**
     * 元件掛載時執行
     * 
     * @return void
     */
    public function mount()
    {
        $this->dashboardService = app(DashboardService::class);
        $this->loadStats();
    }

    /**
     * 載入統計資料
     * 
     * @return void
     */
    public function loadStats()
    {
        $this->stats = $this->dashboardService->getStats();
    }

    /**
     * 重新整理統計資料
     * 
     * @return void
     */
    public function refreshStats()
    {
        $this->stats = $this->dashboardService->getStats(true);
        
        $this->dispatch('stats-refreshed', [
            'message' => '統計資料已更新'
        ]);
    }

    /**
     * 取得統計卡片資料
     * 
     * @return array
     */
    public function getStatsCardsProperty(): array
    {
        return [
            [
                'title' => '使用者總數',
                'value' => $this->stats['total_users'] ?? 0,
                'icon' => 'users',
                'color' => 'primary',
                'description' => '系統中的所有使用者',
                'trend' => $this->stats['user_growth'] ?? null
            ],
            [
                'title' => '啟用使用者',
                'value' => $this->stats['active_users'] ?? 0,
                'icon' => 'user-check',
                'color' => 'success',
                'description' => '目前啟用的使用者',
                'percentage' => $this->stats['total_users'] > 0 
                    ? round(($this->stats['active_users'] / $this->stats['total_users']) * 100, 1)
                    : 0
            ],
            [
                'title' => '角色總數',
                'value' => $this->stats['total_roles'] ?? 0,
                'icon' => 'shield-check',
                'color' => 'warning',
                'description' => '系統中定義的角色',
            ],
            [
                'title' => '權限總數',
                'value' => $this->stats['total_permissions'] ?? 0,
                'icon' => 'lock-closed',
                'color' => 'info',
                'description' => '系統中定義的權限',
            ],
            [
                'title' => '線上使用者',
                'value' => $this->stats['online_users'] ?? 0,
                'icon' => 'wifi',
                'color' => 'danger',
                'description' => '目前線上的使用者',
            ],
            [
                'title' => '新增使用者',
                'value' => $this->stats['recent_users'] ?? 0,
                'icon' => 'user-add',
                'color' => 'purple',
                'description' => '過去 7 天新增的使用者',
            ]
        ];
    }

    /**
     * 渲染元件
     * 
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.admin.dashboard.dashboard-stats');
    }
}