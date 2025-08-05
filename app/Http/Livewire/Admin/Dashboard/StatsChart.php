<?php

namespace App\Http\Livewire\Admin\Dashboard;

use App\Http\Livewire\Admin\AdminComponent;
use App\Services\DashboardService;

/**
 * 統計圖表元件
 * 
 * 顯示統計資料的視覺化圖表
 */
class StatsChart extends AdminComponent
{
    /**
     * 圖表類型
     * 
     * @var string
     */
    public $chartType = 'user_activity';

    /**
     * 圖表資料
     * 
     * @var array
     */
    public $chartData = [];

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
    public function mount($type = 'user_activity')
    {
        $this->chartType = $type;
        $this->dashboardService = app(DashboardService::class);
        $this->loadChartData();
    }

    /**
     * 載入圖表資料
     * 
     * @return void
     */
    public function loadChartData()
    {
        $stats = $this->dashboardService->getStats();
        
        switch ($this->chartType) {
            case 'user_activity':
                $this->chartData = $this->prepareUserActivityData($stats['user_activity'] ?? []);
                break;
            case 'role_distribution':
                $this->chartData = $this->prepareRoleDistributionData($stats['role_distribution'] ?? []);
                break;
            default:
                $this->chartData = [];
        }
    }

    /**
     * 準備使用者活動資料
     * 
     * @param array $data
     * @return array
     */
    protected function prepareUserActivityData(array $data): array
    {
        if (empty($data['daily_registrations'])) {
            return [];
        }

        return [
            'type' => 'line',
            'title' => '過去 7 天使用者註冊趨勢',
            'labels' => collect($data['daily_registrations'])->pluck('label')->toArray(),
            'datasets' => [
                [
                    'label' => '新增使用者',
                    'data' => collect($data['daily_registrations'])->pluck('count')->toArray(),
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.4
                ]
            ],
            'summary' => [
                'total' => $data['total_week'] ?? 0,
                'peak' => $data['peak_day'] ?? null
            ]
        ];
    }

    /**
     * 準備角色分佈資料
     * 
     * @param array $data
     * @return array
     */
    protected function prepareRoleDistributionData(array $data): array
    {
        if (empty($data)) {
            return [];
        }

        $colors = [
            'rgb(59, 130, 246)',   // blue
            'rgb(16, 185, 129)',   // green
            'rgb(245, 158, 11)',   // yellow
            'rgb(239, 68, 68)',    // red
            'rgb(139, 92, 246)',   // purple
            'rgb(236, 72, 153)',   // pink
        ];

        return [
            'type' => 'doughnut',
            'title' => '角色分佈統計',
            'labels' => collect($data)->pluck('name')->toArray(),
            'datasets' => [
                [
                    'data' => collect($data)->pluck('count')->toArray(),
                    'backgroundColor' => array_slice($colors, 0, count($data)),
                    'borderWidth' => 2,
                    'borderColor' => '#ffffff'
                ]
            ],
            'summary' => [
                'total_roles' => count($data),
                'largest_role' => collect($data)->sortByDesc('count')->first()
            ]
        ];
    }

    /**
     * 切換圖表類型
     * 
     * @param string $type
     * @return void
     */
    public function switchChart($type)
    {
        $this->chartType = $type;
        $this->loadChartData();
    }

    /**
     * 重新整理圖表資料
     * 
     * @return void
     */
    public function refreshChart()
    {
        $this->dashboardService->clearCache();
        $this->loadChartData();
        
        $this->dispatchBrowserEvent('chart-refreshed', [
            'message' => '圖表資料已更新'
        ]);
    }

    /**
     * 渲染元件
     * 
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.admin.dashboard.stats-chart');
    }
}