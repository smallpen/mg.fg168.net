<?php

namespace App\Livewire\Admin\Layout;

use Livewire\Component;
use Livewire\Attributes\On;

/**
 * 焦點管理元件
 * 管理頁面焦點狀態，提供鍵盤導航支援
 */
class FocusManager extends Component
{
    public ?string $currentFocus = null;
    public array $focusHistory = [];
    public bool $trapFocus = false;
    public ?string $trapContainer = null;

    /**
     * 設定焦點到指定元素
     */
    public function setFocus(string $elementId): void
    {
        $this->addToHistory($this->currentFocus);
        $this->currentFocus = $elementId;
        
        $this->dispatch('set-focus', ['elementId' => $elementId]);
    }

    /**
     * 回到上一個焦點位置
     */
    public function restorePreviousFocus(): void
    {
        if (!empty($this->focusHistory)) {
            $previousFocus = array_pop($this->focusHistory);
            $this->currentFocus = $previousFocus;
            
            if ($previousFocus) {
                $this->dispatch('set-focus', ['elementId' => $previousFocus]);
            }
        }
    }

    /**
     * 啟用焦點陷阱
     */
    public function enableFocusTrap(string $containerId): void
    {
        $this->trapFocus = true;
        $this->trapContainer = $containerId;
        
        $this->dispatch('enable-focus-trap', ['containerId' => $containerId]);
    }

    /**
     * 停用焦點陷阱
     */
    public function disableFocusTrap(): void
    {
        $this->trapFocus = false;
        $this->trapContainer = null;
        
        $this->dispatch('disable-focus-trap');
    }

    /**
     * 處理跳轉到元素事件
     */
    #[On('skip-to-element')]
    public function handleSkipToElement(array $data): void
    {
        $targetId = $data['targetId'] ?? null;
        if ($targetId) {
            $this->setFocus($targetId);
            
            // 確保元素可見
            $this->dispatch('scroll-to-element', ['elementId' => $targetId]);
        }
    }

    /**
     * 處理模態框開啟事件
     */
    #[On('modal-opened')]
    public function handleModalOpened(array $data): void
    {
        $modalId = $data['modalId'] ?? null;
        if ($modalId) {
            $this->enableFocusTrap($modalId);
            
            // 設定焦點到模態框的第一個可聚焦元素
            $this->dispatch('focus-first-element', ['containerId' => $modalId]);
        }
    }

    /**
     * 處理模態框關閉事件
     */
    #[On('modal-closed')]
    public function handleModalClosed(): void
    {
        $this->disableFocusTrap();
        $this->restorePreviousFocus();
    }

    /**
     * 處理下拉選單開啟事件
     */
    #[On('dropdown-opened')]
    public function handleDropdownOpened(array $data): void
    {
        $dropdownId = $data['dropdownId'] ?? null;
        if ($dropdownId) {
            $this->enableFocusTrap($dropdownId);
        }
    }

    /**
     * 處理下拉選單關閉事件
     */
    #[On('dropdown-closed')]
    public function handleDropdownClosed(): void
    {
        $this->disableFocusTrap();
    }

    /**
     * 處理鍵盤導航事件
     */
    public function handleKeyboardNavigation(array $data): void
    {
        $key = $data['key'] ?? '';
        $shiftKey = $data['shiftKey'] ?? false;
        $ctrlKey = $data['ctrlKey'] ?? false;
        $altKey = $data['altKey'] ?? false;

        // Tab 導航
        if ($key === 'Tab') {
            if ($this->trapFocus && $this->trapContainer) {
                $direction = $shiftKey ? 'previous' : 'next';
                $this->dispatch('navigate-within-container', [
                    'containerId' => $this->trapContainer,
                    'direction' => $direction
                ]);
            }
        }

        // Escape 鍵處理
        if ($key === 'Escape') {
            if ($this->trapFocus) {
                $this->dispatch('close-focused-element');
            }
        }

        // 方向鍵導航（用於選單）
        if (in_array($key, ['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'])) {
            $this->dispatch('arrow-key-navigation', [
                'key' => $key,
                'currentFocus' => $this->currentFocus
            ]);
        }
    }

    /**
     * 新增到焦點歷史記錄
     */
    protected function addToHistory(?string $elementId): void
    {
        if ($elementId && $elementId !== $this->currentFocus) {
            $this->focusHistory[] = $elementId;
            
            // 限制歷史記錄長度
            if (count($this->focusHistory) > 10) {
                array_shift($this->focusHistory);
            }
        }
    }

    /**
     * 清除焦點歷史記錄
     */
    public function clearHistory(): void
    {
        $this->focusHistory = [];
    }

    public function render()
    {
        return view('livewire.admin.layout.focus-manager');
    }
}