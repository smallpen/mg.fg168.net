<?php

namespace App\Livewire\Admin\Layout;

use Livewire\Component;
use Livewire\Attributes\On;

/**
 * LoadingOverlay 全域載入元件
 * 
 * 提供全域載入狀態管理，包含載入指示器、進度條和操作狀態回饋
 */
class LoadingOverlay extends Component
{
    // 載入狀態
    public bool $isLoading = false;
    public bool $showProgress = false;
    public int $progress = 0;
    public string $loadingText = '載入中...';
    public string $loadingType = 'spinner'; // spinner, progress, skeleton
    
    // 操作狀態
    public bool $showOperationStatus = false;
    public string $operationMessage = '';
    public string $operationType = 'info'; // info, success, error, warning
    public int $estimatedTime = 0;
    public int $elapsedTime = 0;
    
    // 網路狀態
    public bool $isOnline = true;
    public bool $showOfflineMessage = false;
    
    /**
     * 顯示載入狀態
     */
    public function showLoading(string $text = '載入中...', string $type = 'spinner'): void
    {
        $this->isLoading = true;
        $this->loadingText = $text;
        $this->loadingType = $type;
        $this->progress = 0;
        $this->showProgress = ($type === 'progress');
        
        $this->dispatch('loading-started');
    }
    
    /**
     * 隱藏載入狀態
     */
    public function hideLoading(): void
    {
        $this->isLoading = false;
        $this->showProgress = false;
        $this->progress = 0;
        $this->elapsedTime = 0;
        
        $this->dispatch('loading-finished');
    }
    
    /**
     * 更新載入進度
     */
    public function updateProgress(int $progress, ?string $text = null): void
    {
        $this->progress = max(0, min(100, $progress));
        
        if ($text) {
            $this->loadingText = $text;
        }
        
        if ($this->progress >= 100) {
            $this->hideLoading();
        }
    }
    
    /**
     * 顯示操作狀態訊息
     */
    public function showOperationStatus(
        string $message, 
        string $type = 'info', 
        int $duration = 3000,
        ?int $estimatedTime = null
    ): void {
        $this->showOperationStatus = true;
        $this->operationMessage = $message;
        $this->operationType = $type;
        $this->estimatedTime = $estimatedTime ?? 0;
        
        // 自動隱藏訊息
        if ($duration > 0) {
            $this->dispatch('auto-hide-status', duration: $duration);
        }
    }
    
    /**
     * 隱藏操作狀態訊息
     */
    public function hideOperationStatus(): void
    {
        $this->showOperationStatus = false;
        $this->operationMessage = '';
        $this->estimatedTime = 0;
        $this->elapsedTime = 0;
    }
    
    /**
     * 設定網路狀態
     */
    public function setNetworkStatus(bool $isOnline): void
    {
        $this->isOnline = $isOnline;
        $this->showOfflineMessage = !$isOnline;
        
        if (!$isOnline) {
            $this->showOperationStatus('網路連線中斷，請檢查您的網路連線', 'error', 0);
        } else {
            $this->hideOperationStatus();
        }
    }
    
    /**
     * 重試操作
     */
    public function retryOperation(): void
    {
        $this->dispatch('retry-operation');
        $this->hideOperationStatus();
    }
    
    // 事件監聽器
    
    /**
     * 監聽載入開始事件
     */
    #[On('start-loading')]
    public function handleStartLoading(string $text = '載入中...', string $type = 'spinner'): void
    {
        $this->showLoading($text, $type);
    }
    
    /**
     * 監聽載入結束事件
     */
    #[On('stop-loading')]
    public function handleStopLoading(): void
    {
        $this->hideLoading();
    }
    
    /**
     * 監聽進度更新事件
     */
    #[On('update-progress')]
    public function handleUpdateProgress(int $progress, ?string $text = null): void
    {
        $this->updateProgress($progress, $text);
    }
    
    /**
     * 監聽操作狀態事件
     */
    #[On('show-operation-status')]
    public function handleShowOperationStatus(
        string $message, 
        string $type = 'info', 
        int $duration = 3000,
        ?int $estimatedTime = null
    ): void {
        $this->showOperationStatus($message, $type, $duration, $estimatedTime);
    }
    
    /**
     * 監聽網路狀態變更事件
     */
    #[On('network-status-changed')]
    public function handleNetworkStatusChange(bool $isOnline): void
    {
        $this->setNetworkStatus($isOnline);
    }
    
    /**
     * 監聽頁面載入事件
     */
    #[On('page-loading')]
    public function handlePageLoading(): void
    {
        $this->showLoading('頁面載入中...', 'progress');
    }
    
    /**
     * 監聽長時間操作事件
     */
    #[On('long-operation-started')]
    public function handleLongOperationStarted(string $message, int $estimatedTime = 0): void
    {
        $this->showLoading($message, 'progress');
        $this->estimatedTime = $estimatedTime;
    }
    
    /**
     * 取得載入狀態的 CSS 類別
     */
    public function getLoadingClassesProperty(): string
    {
        $classes = ['loading-overlay'];
        
        if ($this->isLoading) {
            $classes[] = 'active';
        }
        
        $classes[] = "loading-{$this->loadingType}";
        
        return implode(' ', $classes);
    }
    
    /**
     * 取得操作狀態的 CSS 類別
     */
    public function getOperationStatusClassesProperty(): string
    {
        $classes = ['operation-status'];
        
        if ($this->showOperationStatus) {
            $classes[] = 'active';
        }
        
        $classes[] = "status-{$this->operationType}";
        
        return implode(' ', $classes);
    }
    
    /**
     * 取得預估剩餘時間
     */
    public function getEstimatedRemainingTimeProperty(): int
    {
        if ($this->estimatedTime <= 0 || $this->progress <= 0) {
            return 0;
        }
        
        $remainingProgress = 100 - $this->progress;
        $timePerPercent = $this->elapsedTime / $this->progress;
        
        return (int) ($remainingProgress * $timePerPercent);
    }
    
    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.layout.loading-overlay');
    }
}