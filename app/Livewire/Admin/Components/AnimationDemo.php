<?php

namespace App\Livewire\Admin\Components;

use Livewire\Component;

class AnimationDemo extends Component
{
    public $currentAnimation = 'fade';
    public $isLoading = false;
    public $showNotification = false;
    public $notificationMessage = '';
    public $notificationType = 'success';
    public $buttonState = 'normal';
    public $menuExpanded = false;
    public $sidebarCollapsed = false;
    
    public $animationTypes = [
        'fade' => '淡入淡出',
        'slide' => '滑動',
        'scale' => '縮放',
        'rotate' => '旋轉',
        'bounce' => '彈跳',
        'flip' => '翻轉',
    ];
    
    public $loadingTypes = [
        'spinner' => '旋轉載入器',
        'dots' => '點點載入器',
        'pulse' => '脈衝載入器',
        'wave' => '波浪載入器',
        'ring' => '環形載入器',
    ];

    /**
     * 觸發頁面轉換動畫
     */
    public function triggerPageTransition($type)
    {
        $this->currentAnimation = $type;
        $this->dispatch('page-transition', type: $type);
    }

    /**
     * 觸發載入動畫
     */
    public function triggerLoading($type = 'spinner')
    {
        $this->isLoading = true;
        $this->dispatch('show-loading', type: $type);
        
        // 模擬載入過程
        $this->dispatch('simulate-loading');
    }

    /**
     * 停止載入動畫
     */
    public function stopLoading()
    {
        $this->isLoading = false;
        $this->dispatch('hide-loading');
    }

    /**
     * 觸發按鈕動畫
     */
    public function triggerButtonAnimation($state)
    {
        $this->buttonState = $state;
        $this->dispatch('button-animation', state: $state);
        
        // 重置按鈕狀態
        $this->dispatch('reset-button-state')->delay(1000);
    }

    /**
     * 重置按鈕狀態
     */
    public function resetButtonState()
    {
        $this->buttonState = 'normal';
    }

    /**
     * 顯示通知
     */
    public function showNotification($type, $message)
    {
        $this->notificationType = $type;
        $this->notificationMessage = $message;
        $this->showNotification = true;
        
        $this->dispatch('show-notification', [
            'type' => $type,
            'message' => $message
        ]);
        
        // 自動隱藏通知
        $this->dispatch('hide-notification')->delay(3000);
    }

    /**
     * 隱藏通知
     */
    public function hideNotification()
    {
        $this->showNotification = false;
    }

    /**
     * 切換選單狀態
     */
    public function toggleMenu()
    {
        $this->menuExpanded = !$this->menuExpanded;
        $this->dispatch('menu-toggle', expanded: $this->menuExpanded);
    }

    /**
     * 切換側邊欄狀態
     */
    public function toggleSidebar()
    {
        $this->sidebarCollapsed = !$this->sidebarCollapsed;
        $this->dispatch('sidebar-toggle', collapsed: $this->sidebarCollapsed);
    }

    /**
     * 觸發狀態變更動畫
     */
    public function triggerStateAnimation($state)
    {
        $this->dispatch('state-animation', state: $state);
    }

    /**
     * 觸發手勢動畫
     */
    public function triggerGestureAnimation($gesture)
    {
        $this->dispatch('gesture-animation', gesture: $gesture);
    }

    /**
     * 模擬資料載入
     */
    public function simulateDataLoad()
    {
        $this->isLoading = true;
        
        // 模擬 API 呼叫延遲
        sleep(2);
        
        $this->isLoading = false;
        $this->showNotification('success', '資料載入完成！');
    }

    /**
     * 測試表單提交動畫
     */
    public function testFormSubmit()
    {
        $this->isLoading = true;
        $this->dispatch('form-submit-start');
        
        // 模擬表單處理
        sleep(1);
        
        $this->isLoading = false;
        $this->dispatch('form-submit-success');
        $this->showNotification('success', '表單提交成功！');
    }

    /**
     * 測試錯誤動畫
     */
    public function testError()
    {
        $this->dispatch('form-submit-error');
        $this->showNotification('error', '發生錯誤，請重試！');
    }

    public function render()
    {
        return view('livewire.admin.components.animation-demo');
    }
}