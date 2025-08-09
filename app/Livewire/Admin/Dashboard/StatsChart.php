<?php

namespace App\Livewire\Admin\Dashboard;

use Livewire\Component;

/**
 * 統計圖表元件
 * 
 * 顯示統計資料的視覺化圖表
 */
class StatsChart extends Component
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
     * 元件掛載時執行
     * 
     * @return void
     */
    public function mount($type = 'user_activity')
    {
        $this->chartType = $type;
        $this->loadChartData();
    }

    /**
     * 載入圖表資料
     * 
     * @return void
     */
    public function loadChartData()
    {
        // 簡化版本，回傳一些示例資料
        switch ($this->chartType) {
            case 'user_activity':
                $this->chartData = [
                    'type' => 'line',
                    'title' => '使用者活動趨勢',
                    'labels' => ['週一', '週二', '週三', '週四', '週五', '週六', '週日'],
                    'datasets' => [
                        [
                            'label' => '登入次數',
                            'data' => [12, 19, 3, 5, 2, 3, 9],
                            'borderColor' => 'rgb(59, 130, 246)',
                            'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        ]
                    ]
                ];
                break;
            case 'role_distribution':
                $this->chartData = [
                    'type' => 'doughnut',
                    'title' => '角色分佈',
                    'labels' => ['管理員', '編輯者', '使用者'],
                    'datasets' => [
                        [
                            'data' => [2, 5, 10],
                            'backgroundColor' => [
                                'rgb(59, 130, 246)',
                                'rgb(16, 185, 129)',
                                'rgb(245, 158, 11)'
                            ]
                        ]
                    ]
                ];
                break;
            default:
                $this->chartData = [];
        }
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
        $this->loadChartData();
        
        $this->dispatch('chart-refreshed', [
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