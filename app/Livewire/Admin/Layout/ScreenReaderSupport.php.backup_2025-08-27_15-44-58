<?php

namespace App\Livewire\Admin\Layout;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Services\AccessibilityService;

/**
 * 螢幕閱讀器支援元件
 * 提供螢幕閱讀器使用者所需的輔助功能
 */
class ScreenReaderSupport extends Component
{
    public array $announcements = [];
    public string $currentRegion = '';
    public array $landmarks = [];

    protected AccessibilityService $accessibilityService;

    public function boot(AccessibilityService $accessibilityService)
    {
        $this->accessibilityService = $accessibilityService;
    }

    public function mount()
    {
        $this->initializeLandmarks();
    }

    /**
     * 初始化頁面地標
     */
    protected function initializeLandmarks(): void
    {
        $this->landmarks = [
            'banner' => '頁面標題區域',
            'navigation' => '主要導航選單',
            'main' => '主要內容區域',
            'search' => '搜尋功能',
            'complementary' => '輔助資訊區域',
            'contentinfo' => '頁面資訊區域',
        ];
    }

    /**
     * 宣告訊息給螢幕閱讀器
     */
    public function announce(string $message, string $priority = 'polite'): void
    {
        $announcement = [
            'id' => uniqid('announcement_'),
            'message' => $message,
            'priority' => $priority, // 'polite' 或 'assertive'
            'timestamp' => now(),
        ];

        $this->announcements[] = $announcement;

        // 限制宣告數量
        if (count($this->announcements) > 5) {
            array_shift($this->announcements);
        }

        $this->dispatch('screen-reader-announce', $announcement);
    }

    /**
     * 宣告頁面變更
     */
    public function announcePageChange(string $pageTitle, string $breadcrumb = ''): void
    {
        $message = "頁面已變更為：{$pageTitle}";
        if ($breadcrumb) {
            $message .= "，位置：{$breadcrumb}";
        }
        
        $this->announce($message, 'assertive');
    }

    /**
     * 宣告載入狀態
     */
    public function announceLoadingState(bool $isLoading, string $context = ''): void
    {
        if ($isLoading) {
            $message = $context ? "正在載入{$context}" : "正在載入";
            $this->announce($message, 'assertive');
        } else {
            $message = $context ? "{$context}載入完成" : "載入完成";
            $this->announce($message, 'polite');
        }
    }

    /**
     * 宣告表單驗證錯誤
     */
    public function announceFormErrors(array $errors): void
    {
        $errorCount = count($errors);
        if ($errorCount > 0) {
            $message = "表單包含 {$errorCount} 個錯誤：" . implode('，', $errors);
            $this->announce($message, 'assertive');
        }
    }

    /**
     * 宣告操作結果
     */
    public function announceOperationResult(bool $success, string $operation, string $details = ''): void
    {
        if ($success) {
            $message = "{$operation}成功";
            if ($details) {
                $message .= "：{$details}";
            }
            $this->announce($message, 'polite');
        } else {
            $message = "{$operation}失敗";
            if ($details) {
                $message .= "：{$details}";
            }
            $this->announce($message, 'assertive');
        }
    }

    /**
     * 設定當前區域
     */
    public function setCurrentRegion(string $region): void
    {
        $this->currentRegion = $region;
        
        if (isset($this->landmarks[$region])) {
            $this->announce("進入{$this->landmarks[$region]}", 'polite');
        }
    }

    /**
     * 處理頁面載入事件
     */
    #[On('page-loaded')]
    public function handlePageLoaded(array $data): void
    {
        $title = $data['title'] ?? '';
        $breadcrumb = $data['breadcrumb'] ?? '';
        
        if ($title) {
            $this->announcePageChange($title, $breadcrumb);
        }
    }

    /**
     * 處理載入狀態變更事件
     */
    #[On('loading-state-changed')]
    public function handleLoadingStateChanged(array $data): void
    {
        $isLoading = $data['isLoading'] ?? false;
        $context = $data['context'] ?? '';
        
        $this->announceLoadingState($isLoading, $context);
    }

    /**
     * 處理表單提交事件
     */
    #[On('form-submitted')]
    public function handleFormSubmitted(array $data): void
    {
        $success = $data['success'] ?? false;
        $errors = $data['errors'] ?? [];
        
        if ($success) {
            $this->announceOperationResult(true, '表單提交');
        } else {
            $this->announceFormErrors($errors);
        }
    }

    /**
     * 處理通知事件
     */
    #[On('notification-received')]
    public function handleNotificationReceived(array $data): void
    {
        $title = $data['title'] ?? '';
        $type = $data['type'] ?? 'info';
        
        $typeText = match($type) {
            'success' => '成功通知',
            'error' => '錯誤通知',
            'warning' => '警告通知',
            default => '一般通知'
        };
        
        $message = "{$typeText}：{$title}";
        $priority = in_array($type, ['error', 'warning']) ? 'assertive' : 'polite';
        
        $this->announce($message, $priority);
    }

    /**
     * 處理選單狀態變更事件
     */
    #[On('menu-state-changed')]
    public function handleMenuStateChanged(array $data): void
    {
        $isOpen = $data['isOpen'] ?? false;
        $menuName = $data['menuName'] ?? '選單';
        
        $message = $isOpen ? "{$menuName}已開啟" : "{$menuName}已關閉";
        $this->announce($message, 'polite');
    }

    /**
     * 處理搜尋結果事件
     */
    #[On('search-results-updated')]
    public function handleSearchResultsUpdated(array $data): void
    {
        $count = $data['count'] ?? 0;
        $query = $data['query'] ?? '';
        
        if ($count > 0) {
            $message = "搜尋「{$query}」找到 {$count} 個結果";
        } else {
            $message = "搜尋「{$query}」沒有找到結果";
        }
        
        $this->announce($message, 'polite');
    }

    /**
     * 清除舊的宣告
     */
    public function clearAnnouncements(): void
    {
        $this->announcements = [];
    }

    /**
     * 取得 ARIA 即時區域屬性
     */
    public function getAriaLiveProperty(): string
    {
        return 'polite';
    }

    /**
     * 取得 ARIA 原子屬性
     */
    public function getAriaAtomicProperty(): string
    {
        return 'true';
    }

    public function render()
    {
        return view('livewire.admin.layout.screen-reader-support');
    }
}