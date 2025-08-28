<?php

namespace App\Livewire\Admin\Layout;

use Livewire\Component;
use Livewire\Attributes\On;

/**
 * OperationFeedback 操作狀態回饋系統
 * 
 * 提供操作成功/失敗/警告等狀態的即時回饋
 */
class OperationFeedback extends Component
{
    // 回饋狀態
    public array $feedbacks = [];
    public int $maxFeedbacks = 5;
    public int $defaultDuration = 5000; // 5 秒
    
    // 回饋類型定義
    protected array $feedbackTypes = [
        'success' => [
            'icon' => 'check-circle',
            'color' => 'green',
            'title' => '操作成功'
        ],
        'error' => [
            'icon' => 'x-circle',
            'color' => 'red',
            'title' => '操作失敗'
        ],
        'warning' => [
            'icon' => 'exclamation-triangle',
            'color' => 'yellow',
            'title' => '注意'
        ],
        'info' => [
            'icon' => 'information-circle',
            'color' => 'blue',
            'title' => '資訊'
        ],
        'loading' => [
            'icon' => 'refresh',
            'color' => 'gray',
            'title' => '處理中'
        ]
    ];
    
    /**
     * 新增回饋訊息
     */
    public function addFeedback(
        string $message,
        string $type = 'info',
        ?int $duration = null,
        ?string $title = null,
        ?array $actions = null
    ): string {
        $id = uniqid('feedback_');
        $duration = $duration ?? $this->defaultDuration;
        
        $feedback = [
            'id' => $id,
            'type' => $type,
            'title' => $title ?? $this->feedbackTypes[$type]['title'] ?? '通知',
            'message' => $message,
            'duration' => $duration,
            'actions' => $actions ?? [],
            'timestamp' => now()->timestamp,
            'persistent' => $duration === 0,
            'progress' => $type === 'loading' ? 0 : null
        ];
        
        // 限制回饋數量
        if (count($this->feedbacks) >= $this->maxFeedbacks) {
            array_shift($this->feedbacks);
        }
        
        $this->feedbacks[] = $feedback;
        
        // 自動移除非持久性回饋
        if (!$feedback['persistent']) {
            $this->dispatch('auto-remove-feedback', id: $id, duration: $duration);
        }
        
        return $id;
    }
    
    /**
     * 移除回饋訊息
     */
    public function removeFeedback(string $id): void
    {
        $this->feedbacks = array_filter($this->feedbacks, fn($feedback) => $feedback['id'] !== $id);
        $this->feedbacks = array_values($this->feedbacks); // 重新索引
    }
    
    /**
     * 清除所有回饋
     */
    public function clearAllFeedbacks(): void
    {
        $this->feedbacks = [];
    }
    
    /**
     * 更新載入進度
     */
    public function updateProgress(string $id, int $progress, ?string $message = null): void
    {
        foreach ($this->feedbacks as &$feedback) {
            if ($feedback['id'] === $id && $feedback['type'] === 'loading') {
                $feedback['progress'] = max(0, min(100, $progress));
                
                if ($message) {
                    $feedback['message'] = $message;
                }
                
                // 完成時自動轉換為成功狀態
                if ($progress >= 100) {
                    $feedback['type'] = 'success';
                    $feedback['title'] = '操作完成';
                    $feedback['progress'] = null;
                    $feedback['duration'] = 3000;
                    $feedback['persistent'] = false;
                    
                    $this->dispatch('auto-remove-feedback', id: $id, duration: 3000);
                }
                break;
            }
        }
    }
    
    /**
     * 顯示成功訊息
     */
    public function showSuccess(string $message, ?string $title = null, ?int $duration = null): string
    {
        return $this->addFeedback($message, 'success', $duration, $title);
    }
    
    /**
     * 顯示錯誤訊息
     */
    public function showError(string $message, ?string $title = null, ?array $actions = null): string
    {
        return $this->addFeedback($message, 'error', 0, $title, $actions); // 錯誤訊息持久顯示
    }
    
    /**
     * 顯示警告訊息
     */
    public function showWarning(string $message, ?string $title = null, ?int $duration = null): string
    {
        return $this->addFeedback($message, 'warning', $duration ?? 8000, $title);
    }
    
    /**
     * 顯示資訊訊息
     */
    public function showInfo(string $message, ?string $title = null, ?int $duration = null): string
    {
        return $this->addFeedback($message, 'info', $duration, $title);
    }
    
    /**
     * 顯示載入訊息
     */
    public function showLoading(string $message, ?string $title = null): string
    {
        return $this->addFeedback($message, 'loading', 0, $title ?? '處理中');
    }
    
    /**
     * 執行回饋動作
     */
    public function executeAction(string $feedbackId, string $actionKey): void
    {
        $feedback = collect($this->feedbacks)->firstWhere('id', $feedbackId);
        
        if ($feedback && isset($feedback['actions'][$actionKey])) {
            $action = $feedback['actions'][$actionKey];
            
            // 觸發動作事件
            $this->dispatch('feedback-action-executed', [
                'feedbackId' => $feedbackId,
                'actionKey' => $actionKey,
                'action' => $action
            ]);
            
            // 如果動作指定要關閉回饋
            if ($action['close'] ?? false) {
                $this->removeFeedback($feedbackId);
            }
        }
    }
    
    /**
     * 取得回饋的 CSS 類別
     */
    public function getFeedbackClasses(array $feedback): string
    {
        $type = $feedback['type'];
        $config = $this->feedbackTypes[$type] ?? $this->feedbackTypes['info'];
        
        $classes = [
            'feedback-item',
            "feedback-{$type}",
            "border-{$config['color']}-200",
            "bg-{$config['color']}-50",
            "dark:border-{$config['color']}-800",
            "dark:bg-{$config['color']}-900"
        ];
        
        return implode(' ', $classes);
    }
    
    /**
     * 取得回饋圖示
     */
    public function getFeedbackIcon(array $feedback): string
    {
        $type = $feedback['type'];
        $config = $this->feedbackTypes[$type] ?? $this->feedbackTypes['info'];
        
        return $config['icon'];
    }
    
    /**
     * 取得回饋顏色
     */
    public function getFeedbackColor(array $feedback): string
    {
        $type = $feedback['type'];
        $config = $this->feedbackTypes[$type] ?? $this->feedbackTypes['info'];
        
        return $config['color'];
    }
    
    // 事件監聽器
    
    /**
     * 監聽顯示回饋事件
     */
    #[On('show-feedback')]
    public function handleShowFeedback(
        string $message,
        string $type = 'info',
        ?int $duration = null,
        ?string $title = null,
        ?array $actions = null
    ): void {
        $this->addFeedback($message, $type, $duration, $title, $actions);
    }
    
    /**
     * 監聽移除回饋事件
     */
    #[On('remove-feedback')]
    public function handleRemoveFeedback(string $id): void
    {
        $this->removeFeedback($id);
    }
    
    /**
     * 監聽清除所有回饋事件
     */
    #[On('clear-all-feedbacks')]
    public function handleClearAllFeedbacks(): void
    {
        $this->clearAllFeedbacks();
    }
    
    /**
     * 監聽更新進度事件
     */
    #[On('update-feedback-progress')]
    public function handleUpdateProgress(string $id, int $progress, ?string $message = null): void
    {
        $this->updateProgress($id, $progress, $message);
    }
    
    /**
     * 監聽操作成功事件
     */
    #[On('operation-success')]
    public function handleOperationSuccess(string $message, ?string $title = null): void
    {
        $this->showSuccess($message, $title);
    }
    
    /**
     * 監聽操作失敗事件
     */
    #[On('operation-error')]
    public function handleOperationError(string $message, ?string $title = null, ?array $actions = null): void
    {
        $this->showError($message, $title, $actions);
    }
    
    /**
     * 監聽操作警告事件
     */
    #[On('operation-warning')]
    public function handleOperationWarning(string $message, ?string $title = null): void
    {
        $this->showWarning($message, $title);
    }
    
    /**
     * 監聽操作開始事件
     */
    #[On('operation-started')]
    public function handleOperationStarted(string $message, ?string $title = null): void
    {
        $this->showLoading($message, $title);
    }
    
    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.layout.operation-feedback');
    }
}