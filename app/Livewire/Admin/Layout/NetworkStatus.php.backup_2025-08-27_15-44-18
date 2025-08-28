<?php

namespace App\Livewire\Admin\Layout;

use Livewire\Component;
use Livewire\Attributes\On;

/**
 * NetworkStatus 網路狀態檢測和離線提示
 * 
 * 監控網路連線狀態並提供離線模式支援
 */
class NetworkStatus extends Component
{
    // 網路狀態
    public bool $isOnline = true;
    public bool $showOfflineMessage = false;
    public string $connectionType = 'unknown';
    public int $lastOnlineTime = 0;
    public int $offlineDuration = 0;
    
    // 連線品質
    public string $connectionQuality = 'good'; // good, fair, poor
    public int $latency = 0;
    public float $downloadSpeed = 0;
    
    // 離線功能
    public bool $offlineModeEnabled = false;
    public array $offlineQueue = [];
    public int $maxOfflineActions = 50;
    
    // 重連設定
    public bool $autoReconnect = true;
    public int $reconnectAttempts = 0;
    public int $maxReconnectAttempts = 5;
    public int $reconnectInterval = 5000; // 5 秒
    
    /**
     * 元件初始化
     */
    public function mount(): void
    {
        $this->isOnline = true;
        $this->lastOnlineTime = now()->timestamp;
    }
    
    /**
     * 設定網路狀態
     */
    public function setNetworkStatus(bool $isOnline, array $details = []): void
    {
        $wasOffline = !$this->isOnline;
        $this->isOnline = $isOnline;
        
        if ($isOnline) {
            // 網路恢復
            if ($wasOffline) {
                $this->handleNetworkRestore();
            }
            
            $this->showOfflineMessage = false;
            $this->reconnectAttempts = 0;
            $this->lastOnlineTime = now()->timestamp;
            
            // 更新連線詳情
            $this->updateConnectionDetails($details);
        } else {
            // 網路中斷
            $this->showOfflineMessage = true;
            $this->offlineDuration = 0;
            
            if ($this->autoReconnect) {
                $this->scheduleReconnect();
            }
        }
        
        // 觸發狀態變更事件
        $this->dispatch('network-status-changed', [
            'isOnline' => $isOnline,
            'details' => $details
        ]);
    }
    
    /**
     * 更新連線詳情
     */
    protected function updateConnectionDetails(array $details): void
    {
        $this->connectionType = $details['type'] ?? 'unknown';
        $this->latency = $details['latency'] ?? 0;
        $this->downloadSpeed = $details['downloadSpeed'] ?? 0;
        
        // 判斷連線品質
        if ($this->latency > 0) {
            if ($this->latency < 100) {
                $this->connectionQuality = 'good';
            } elseif ($this->latency < 300) {
                $this->connectionQuality = 'fair';
            } else {
                $this->connectionQuality = 'poor';
            }
        }
    }
    
    /**
     * 處理網路恢復
     */
    protected function handleNetworkRestore(): void
    {
        // 處理離線佇列
        if (!empty($this->offlineQueue)) {
            $this->processOfflineQueue();
        }
        
        // 顯示恢復訊息
        $this->dispatch('show-feedback', [
            'message' => '網路連線已恢復',
            'type' => 'success',
            'duration' => 3000
        ]);
        
        // 重新載入關鍵資料
        $this->dispatch('network-restored');
    }
    
    /**
     * 處理離線佇列
     */
    protected function processOfflineQueue(): void
    {
        $processedCount = 0;
        $failedCount = 0;
        
        foreach ($this->offlineQueue as $index => $action) {
            try {
                // 嘗試執行離線動作
                $this->executeOfflineAction($action);
                $processedCount++;
                unset($this->offlineQueue[$index]);
            } catch (\Exception $e) {
                $failedCount++;
                // 保留失敗的動作以供稍後重試
            }
        }
        
        // 重新索引陣列
        $this->offlineQueue = array_values($this->offlineQueue);
        
        // 顯示處理結果
        if ($processedCount > 0) {
            $this->dispatch('show-feedback', [
                'message' => "已同步 {$processedCount} 個離線操作",
                'type' => 'info',
                'duration' => 5000
            ]);
        }
        
        if ($failedCount > 0) {
            $this->dispatch('show-feedback', [
                'message' => "有 {$failedCount} 個操作同步失敗",
                'type' => 'warning',
                'duration' => 8000
            ]);
        }
    }
    
    /**
     * 執行離線動作
     */
    protected function executeOfflineAction(array $action): void
    {
        // 根據動作類型執行相應操作
        switch ($action['type']) {
            case 'form_submit':
                $this->dispatch('retry-form-submit', $action['data']);
                break;
            case 'data_update':
                $this->dispatch('retry-data-update', $action['data']);
                break;
            case 'file_upload':
                $this->dispatch('retry-file-upload', $action['data']);
                break;
            default:
                $this->dispatch('retry-generic-action', $action);
        }
    }
    
    /**
     * 新增離線動作到佇列
     */
    public function addOfflineAction(array $action): void
    {
        if (!$this->offlineModeEnabled) {
            return;
        }
        
        // 限制佇列大小
        if (count($this->offlineQueue) >= $this->maxOfflineActions) {
            array_shift($this->offlineQueue);
        }
        
        $action['timestamp'] = now()->timestamp;
        $action['id'] = uniqid('offline_');
        
        $this->offlineQueue[] = $action;
        
        $this->dispatch('show-feedback', [
            'message' => '操作已加入離線佇列，網路恢復後將自動同步',
            'type' => 'info',
            'duration' => 3000
        ]);
    }
    
    /**
     * 移除離線動作
     */
    public function removeOfflineAction(string $actionId): void
    {
        $this->offlineQueue = array_filter(
            $this->offlineQueue,
            fn($action) => $action['id'] !== $actionId
        );
        $this->offlineQueue = array_values($this->offlineQueue);
    }
    
    /**
     * 清空離線佇列
     */
    public function clearOfflineQueue(): void
    {
        $this->offlineQueue = [];
    }
    
    /**
     * 手動重新連線
     */
    public function reconnect(): void
    {
        $this->reconnectAttempts++;
        
        // 觸發重連檢測
        $this->dispatch('check-network-connection');
        
        $this->dispatch('show-feedback', [
            'message' => "正在嘗試重新連線... (第 {$this->reconnectAttempts} 次)",
            'type' => 'info',
            'duration' => 3000
        ]);
    }
    
    /**
     * 排程自動重連
     */
    protected function scheduleReconnect(): void
    {
        if ($this->reconnectAttempts >= $this->maxReconnectAttempts) {
            $this->dispatch('show-feedback', [
                'message' => '已達到最大重連次數，請手動重新連線',
                'type' => 'warning',
                'duration' => 0
            ]);
            return;
        }
        
        // 使用 JavaScript 設定延遲重連
        $this->js("
            setTimeout(() => {
                \$wire.reconnect();
            }, {$this->reconnectInterval});
        ");
    }
    
    /**
     * 切換離線模式
     */
    public function toggleOfflineMode(): void
    {
        $this->offlineModeEnabled = !$this->offlineModeEnabled;
        
        $message = $this->offlineModeEnabled 
            ? '離線模式已啟用，操作將暫存至佇列'
            : '離線模式已停用';
            
        $this->dispatch('show-feedback', [
            'message' => $message,
            'type' => 'info',
            'duration' => 3000
        ]);
    }
    
    /**
     * 取得離線時間
     */
    public function getOfflineDurationProperty(): int
    {
        if ($this->isOnline) {
            return 0;
        }
        
        return now()->timestamp - $this->lastOnlineTime;
    }
    
    /**
     * 取得連線狀態描述
     */
    public function getConnectionStatusProperty(): string
    {
        if (!$this->isOnline) {
            return '離線';
        }
        
        switch ($this->connectionQuality) {
            case 'good':
                return '連線良好';
            case 'fair':
                return '連線普通';
            case 'poor':
                return '連線不穩';
            default:
                return '已連線';
        }
    }
    
    /**
     * 取得連線品質圖示
     */
    public function getConnectionIconProperty(): string
    {
        if (!$this->isOnline) {
            return 'offline';
        }
        
        switch ($this->connectionQuality) {
            case 'good':
                return 'wifi-strong';
            case 'fair':
                return 'wifi-medium';
            case 'poor':
                return 'wifi-weak';
            default:
                return 'wifi';
        }
    }
    
    // 事件監聽器
    
    /**
     * 監聽網路狀態變更事件
     */
    #[On('network-status-update')]
    public function handleNetworkStatusUpdate(bool $isOnline, array $details = []): void
    {
        $this->setNetworkStatus($isOnline, $details);
    }
    
    /**
     * 監聽離線動作事件
     */
    #[On('add-offline-action')]
    public function handleAddOfflineAction(array $action): void
    {
        $this->addOfflineAction($action);
    }
    
    /**
     * 監聽連線檢測事件
     */
    #[On('connection-test-result')]
    public function handleConnectionTestResult(bool $isOnline, int $latency = 0): void
    {
        $this->setNetworkStatus($isOnline, [
            'latency' => $latency,
            'type' => 'test'
        ]);
    }
    
    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.layout.network-status');
    }
}