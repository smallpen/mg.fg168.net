<?php

namespace App\Livewire\Admin\Layout;

use Livewire\Component;
use App\Services\AccessibilityService;

/**
 * 跳轉連結元件
 * 提供鍵盤使用者快速跳轉到頁面主要區域的功能
 */
class SkipLinks extends Component
{
    public array $links = [];
    public bool $visible = false;

    protected AccessibilityService $accessibilityService;

    public function boot(AccessibilityService $accessibilityService)
    {
        $this->accessibilityService = $accessibilityService;
    }

    public function mount()
    {
        $this->initializeLinks();
    }

    /**
     * 初始化跳轉連結
     */
    protected function initializeLinks(): void
    {
        $this->links = [
            [
                'id' => 'main-content',
                'label' => '跳至主要內容',
                'description' => '跳過導航選單，直接到主要內容區域',
                'shortcut' => 'Alt + C'
            ],
            [
                'id' => 'navigation',
                'label' => '跳至導航選單',
                'description' => '移動到側邊導航選單',
                'shortcut' => 'Alt + N'
            ],
            [
                'id' => 'search',
                'label' => '跳至搜尋',
                'description' => '移動到全域搜尋框',
                'shortcut' => 'Alt + S'
            ],
            [
                'id' => 'user-menu',
                'label' => '跳至使用者選單',
                'description' => '移動到使用者選單區域',
                'shortcut' => 'Alt + U'
            ],
        ];
    }

    /**
     * 顯示跳轉連結
     */
    public function show(): void
    {
        $this->visible = true;
    }

    /**
     * 隱藏跳轉連結
     */
    public function hide(): void
    {
        $this->visible = false;
    }

    /**
     * 跳轉到指定元素
     */
    public function skipTo(string $targetId): void
    {
        $this->dispatch('skip-to-element', ['targetId' => $targetId]);
        $this->hide();
    }

    /**
     * 檢查是否啟用跳轉連結
     */
    public function getIsEnabledProperty(): bool
    {
        $preferences = $this->accessibilityService->getUserAccessibilityPreferences();
        return $preferences['skip_links'] ?? true;
    }

    /**
     * 處理鍵盤焦點事件
     */
    public function handleFocus(): void
    {
        $this->show();
    }

    /**
     * 處理鍵盤失焦事件
     */
    public function handleBlur(): void
    {
        // 延遲隱藏，讓使用者有時間在連結間移動
        $this->dispatch('hide-skip-links-delayed');
    }

    public function render()
    {
        return view('livewire.admin.layout.skip-links');
    }
}