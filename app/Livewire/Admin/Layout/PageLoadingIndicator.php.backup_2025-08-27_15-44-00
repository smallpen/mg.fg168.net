<?php

namespace App\Livewire\Admin\Layout;

use Livewire\Component;
use Livewire\Attributes\On;

/**
 * PageLoadingIndicator 頁面載入進度指示器
 * 
 * 提供頁面載入進度追蹤和視覺化指示器
 */
class PageLoadingIndicator extends Component
{
    // 進度狀態
    public bool $isLoading = false;
    public int $progress = 0;
    public string $currentStep = '';
    public array $loadingSteps = [];
    public int $currentStepIndex = 0;
    
    // 載入階段定義
    protected array $defaultSteps = [
        'initializing' => '初始化頁面...',
        'loading_data' => '載入資料...',
        'rendering' => '渲染介面...',
        'finalizing' => '完成載入...'
    ];
    
    // 時間追蹤
    public int $startTime = 0;
    public int $estimatedDuration = 3000; // 預估 3 秒
    
    /**
     * 元件初始化
     */
    public function mount(): void
    {
        $this->loadingSteps = $this->defaultSteps;
    }
    
    /**
     * 開始頁面載入
     */
    public function startPageLoading(array $steps = null, int $estimatedDuration = 3000): void
    {
        $this->isLoading = true;
        $this->progress = 0;
        $this->currentStepIndex = 0;
        $this->startTime = now()->timestamp;
        $this->estimatedDuration = $estimatedDuration;
        
        if ($steps) {
            $this->loadingSteps = $steps;
        }
        
        $this->currentStep = array_values($this->loadingSteps)[0] ?? '載入中...';
        
        $this->dispatch('page-loading-started');
        $this->simulateProgress();
    }
    
    /**
     * 完成頁面載入
     */
    public function finishPageLoading(): void
    {
        $this->progress = 100;
        $this->currentStep = '載入完成';
        
        // 延遲隱藏以顯示完成狀態
        $this->dispatch('page-loading-finished');
        
        // 500ms 後隱藏指示器
        $this->js('setTimeout(() => { $wire.hideIndicator(); }, 500)');
    }
    
    /**
     * 隱藏指示器
     */
    public function hideIndicator(): void
    {
        $this->isLoading = false;
        $this->progress = 0;
        $this->currentStepIndex = 0;
        $this->currentStep = '';
    }
    
    /**
     * 更新載入步驟
     */
    public function updateStep(string $stepKey, ?int $progress = null): void
    {
        if (isset($this->loadingSteps[$stepKey])) {
            $this->currentStep = $this->loadingSteps[$stepKey];
            $this->currentStepIndex = array_search($stepKey, array_keys($this->loadingSteps));
        }
        
        if ($progress !== null) {
            $this->progress = max(0, min(100, $progress));
        } else {
            // 根據步驟自動計算進度
            $totalSteps = count($this->loadingSteps);
            $this->progress = (int) (($this->currentStepIndex + 1) / $totalSteps * 100);
        }
        
        if ($this->progress >= 100) {
            $this->finishPageLoading();
        }
    }
    
    /**
     * 模擬載入進度 (用於真實載入時間未知的情況)
     */
    protected function simulateProgress(): void
    {
        // 使用 JavaScript 來模擬進度更新
        $this->js('
            let progress = 0;
            let stepIndex = 0;
            const steps = ' . json_encode(array_values($this->loadingSteps)) . ';
            const totalDuration = ' . $this->estimatedDuration . ';
            const stepDuration = totalDuration / steps.length;
            
            const updateProgress = () => {
                if (progress < 90) {
                    progress += Math.random() * 15;
                    progress = Math.min(progress, 90);
                    
                    const currentStepIndex = Math.floor(progress / (100 / steps.length));
                    if (currentStepIndex !== stepIndex && currentStepIndex < steps.length) {
                        stepIndex = currentStepIndex;
                        $wire.set("currentStep", steps[stepIndex]);
                        $wire.set("currentStepIndex", stepIndex);
                    }
                    
                    $wire.set("progress", Math.floor(progress));
                    
                    setTimeout(updateProgress, 200 + Math.random() * 300);
                }
            };
            
            updateProgress();
        ');
    }
    
    /**
     * 設定自訂載入步驟
     */
    public function setLoadingSteps(array $steps): void
    {
        $this->loadingSteps = $steps;
    }
    
    /**
     * 取得當前載入時間
     */
    public function getElapsedTimeProperty(): int
    {
        if (!$this->isLoading || $this->startTime === 0) {
            return 0;
        }
        
        return now()->timestamp - $this->startTime;
    }
    
    /**
     * 取得預估剩餘時間
     */
    public function getEstimatedRemainingTimeProperty(): int
    {
        if ($this->progress <= 0) {
            return $this->estimatedDuration;
        }
        
        $elapsedTime = $this->elapsedTime * 1000; // 轉換為毫秒
        $estimatedTotal = ($elapsedTime / $this->progress) * 100;
        
        return max(0, (int) (($estimatedTotal - $elapsedTime) / 1000));
    }
    
    /**
     * 取得進度條樣式類別
     */
    public function getProgressBarClassesProperty(): string
    {
        $classes = ['progress-bar'];
        
        if ($this->progress >= 100) {
            $classes[] = 'complete';
        } elseif ($this->progress >= 75) {
            $classes[] = 'high';
        } elseif ($this->progress >= 50) {
            $classes[] = 'medium';
        } else {
            $classes[] = 'low';
        }
        
        return implode(' ', $classes);
    }
    
    // 事件監聽器
    
    /**
     * 監聽頁面載入開始事件
     */
    #[On('start-page-loading')]
    public function handleStartPageLoading(array $steps = null, int $estimatedDuration = 3000): void
    {
        $this->startPageLoading($steps, $estimatedDuration);
    }
    
    /**
     * 監聽頁面載入完成事件
     */
    #[On('finish-page-loading')]
    public function handleFinishPageLoading(): void
    {
        $this->finishPageLoading();
    }
    
    /**
     * 監聽載入步驟更新事件
     */
    #[On('update-loading-step')]
    public function handleUpdateStep(string $stepKey, ?int $progress = null): void
    {
        $this->updateStep($stepKey, $progress);
    }
    
    /**
     * 監聽 Livewire 載入開始事件
     */
    #[On('livewire:load-start')]
    public function handleLivewireLoadStart(): void
    {
        $this->startPageLoading([
            'request' => '發送請求...',
            'processing' => '處理資料...',
            'response' => '接收回應...',
            'render' => '更新介面...'
        ], 2000);
    }
    
    /**
     * 監聽 Livewire 載入完成事件
     */
    #[On('livewire:load-end')]
    public function handleLivewireLoadEnd(): void
    {
        $this->finishPageLoading();
    }
    
    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.layout.page-loading-indicator');
    }
}