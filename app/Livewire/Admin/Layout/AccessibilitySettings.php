<?php

namespace App\Livewire\Admin\Layout;

use Livewire\Component;
use App\Services\AccessibilityService;
use Livewire\Attributes\On;

/**
 * 無障礙設定元件
 * 提供使用者自訂無障礙功能的介面
 */
class AccessibilitySettings extends Component
{
    public bool $isOpen = false;
    public array $preferences = [];

    protected AccessibilityService $accessibilityService;

    public function boot(AccessibilityService $accessibilityService)
    {
        $this->accessibilityService = $accessibilityService;
    }

    public function mount()
    {
        $this->preferences = $this->accessibilityService->getUserAccessibilityPreferences();
    }

    /**
     * 切換設定面板顯示狀態
     */
    public function toggle(): void
    {
        $this->isOpen = !$this->isOpen;
        
        if ($this->isOpen) {
            $this->dispatch('accessibility-settings-opened');
        }
    }

    /**
     * 關閉設定面板
     */
    public function close(): void
    {
        $this->isOpen = false;
    }

    /**
     * 更新偏好設定
     */
    public function updatePreference(string $key, bool $value): void
    {
        $this->preferences[$key] = $value;
        $this->accessibilityService->saveUserAccessibilityPreferences($this->preferences);
        
        // 觸發全域事件通知其他元件
        $this->dispatch('accessibility-preference-changed', [
            'key' => $key,
            'value' => $value,
            'preferences' => $this->preferences
        ]);

        // 顯示成功訊息
        $this->dispatch('show-toast', [
            'type' => 'success',
            'message' => '無障礙設定已更新'
        ]);
    }

    /**
     * 重設為預設設定
     */
    public function resetToDefaults(): void
    {
        $this->preferences = [
            'high_contrast' => false,
            'large_text' => false,
            'reduced_motion' => false,
            'keyboard_navigation' => true,
            'screen_reader_support' => true,
            'focus_indicators' => true,
            'skip_links' => true,
        ];

        $this->accessibilityService->saveUserAccessibilityPreferences($this->preferences);
        
        $this->dispatch('accessibility-preferences-reset', [
            'preferences' => $this->preferences
        ]);

        $this->dispatch('show-toast', [
            'type' => 'success',
            'message' => '無障礙設定已重設為預設值'
        ]);
    }

    /**
     * 取得鍵盤快捷鍵說明
     */
    public function getKeyboardShortcutsProperty(): array
    {
        return $this->accessibilityService->getKeyboardShortcuts();
    }

    /**
     * 監聽鍵盤事件
     */
    #[On('keydown')]
    public function handleKeydown(array $event): void
    {
        $key = $event['key'] ?? '';
        $altKey = $event['altKey'] ?? false;

        // Alt + A 開啟無障礙設定
        if ($altKey && strtolower($key) === 'a') {
            $this->toggle();
        }

        // Escape 關閉設定面板
        if ($key === 'Escape' && $this->isOpen) {
            $this->close();
        }
    }

    public function render()
    {
        return view('livewire.admin.layout.accessibility-settings');
    }
}