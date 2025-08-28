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
        try {
            $this->metrics = $this->performanceService->getPerformanceStats($this->selectedPeriod);
            $this->recommendations = $this->performanceService->getPerformanceRecommendations();
            
            // 清除之前的錯誤訊息
            session()->forget('performance_error');
            
        } catch (\Exception $e) {
            // 記錄錯誤
            \Log::error('效能指標載入失敗', [
                'period' => $this->selectedPeriod,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // 設定預設值
            $this->metrics = [
                'performance_score' => 0,
                'average_lcp' => 0,
                'average_fid' => 0,
                'average_cls' => 0,
            ];
            $this->recommendations = [];
            
            // 顯示錯誤訊息
            session()->flash('performance_error', '載入效能資料時發生錯誤，請稍後再試');
        }
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
        
        // 強制重新渲染元件以確保前端同步
        $this->dispatch('$refresh');
        
        // 發送前端刷新事件
        $this->dispatch('performance-period-changed', period: $period);
    }

    /**
     * 當時間週期選擇變更時觸發
     */
    public function updatedSelectedPeriod($value)
    {
        $this->loadMetrics();
        
        // 強制重新渲染元件以確保前端同步
        $this->dispatch('$refresh');
        
        // 發送前端刷新事件
        $this->dispatch('performance-period-changed', period: $value);
    }

    /**
     * 清除效能資料
     */
    public function clearData()
    {
        try {
            $this->performanceService->clearPerformanceData($this->selectedPeriod);
            $this->loadMetrics();
            
            // 強制重新渲染元件
            $this->dispatch('$refresh');
            
            $this->dispatch('performance-data-cleared');
            
        } catch (\Exception $e) {
            \Log::error('清除效能資料失敗', [
                'period' => $this->selectedPeriod,
                'error' => $e->getMessage()
            ]);
            
            session()->flash('performance_error', '清除效能資料時發生錯誤，請稍後再試');
        }
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