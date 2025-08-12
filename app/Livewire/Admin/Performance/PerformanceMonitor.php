<?php

namespace App\Livewire\Admin\Performance;

use Livewire\Component;
use App\Services\PerformanceMonitoringService;
use App\Services\ComponentLazyLoadingService;

/**
 * 效能監控元件
 * 
 * 顯示系統效能指標和監控資訊
 */
class PerformanceMonitor extends Component
{
    public bool $showPanel = false;
    public array $metrics = [];
    public array $recommendations = [];
    public string $selectedPeriod = '24h';
    
    protected PerformanceMonitoringService $performanceService;
    protected ComponentLazyLoadingService $lazyLoadingService;

    public function boot(
        PerformanceMonitoringService $performanceService,
        ComponentLazyLoadingService $lazyLoadingService
    ) {
        $this->performanceService = $performanceService;
        $this->lazyLoadingService = $lazyLoadingService;
    }

    public function mount()
    {
        $this->loadMetrics();
    }

    /**
     * 載入效能指標
     */
    public function loadMetrics()
    {
        $this->metrics = $this->performanceService->getPerformanceStats($this->selectedPeriod);
        $this->recommendations = $this->performanceService->getPerformanceRecommendations();
    }

    /**
     * 切換監控面板顯示
     */
    public function togglePanel()
    {
        $this->showPanel = !$this->showPanel;
        
        if ($this->showPanel) {
            $this->loadMetrics();
        }
    }

    /**
     * 變更時間週期
     */
    public function changePeriod(string $period)
    {
        $this->selectedPeriod = $period;
        $this->loadMetrics();
    }

    /**
     * 清除效能資料
     */
    public function clearData()
    {
        $this->performanceService->clearPerformanceData($this->selectedPeriod);
        $this->loadMetrics();
        
        $this->dispatch('performance-data-cleared');
    }

    /**
     * 取得即時指標
     */
    public function getRealTimeMetricsProperty()
    {
        return $this->performanceService->getRealTimeMetrics();
    }

    /**
     * 取得載入統計
     */
    public function getLazyLoadingStatsProperty()
    {
        return $this->lazyLoadingService->getLoadingStats();
    }

    /**
     * 取得效能分數顏色
     */
    public function getScoreColorProperty()
    {
        $score = $this->metrics['performance_score'] ?? 0;
        
        if ($score >= 90) return 'text-green-600';
        if ($score >= 70) return 'text-yellow-600';
        return 'text-red-600';
    }

    /**
     * 格式化數值
     */
    public function formatMetric(string $metric, $value)
    {
        return match ($metric) {
            'lcp', 'fid', 'ttfb', 'fcp' => number_format($value, 0) . 'ms',
            'cls' => number_format($value, 3),
            default => $value,
        };
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.performance.performance-monitor');
    }
}