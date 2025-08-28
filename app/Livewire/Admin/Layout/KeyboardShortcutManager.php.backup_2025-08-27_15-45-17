<?php

namespace App\Livewire\Admin\Layout;

use App\Services\KeyboardShortcutService;
use Livewire\Component;
use Livewire\Attributes\On;

/**
 * 鍵盤快捷鍵管理元件
 * 負責全域鍵盤事件監聽和快捷鍵執行
 */
class KeyboardShortcutManager extends Component
{
    /**
     * 快捷鍵服務
     */
    protected KeyboardShortcutService $shortcutService;

    /**
     * 是否啟用快捷鍵
     */
    public bool $enabled = true;

    /**
     * 當前按下的按鍵組合
     */
    public array $pressedKeys = [];

    /**
     * 快捷鍵執行歷史
     */
    public array $executionHistory = [];

    /**
     * 初始化元件
     */
    public function boot(KeyboardShortcutService $shortcutService): void
    {
        $this->shortcutService = $shortcutService;
    }

    /**
     * 處理鍵盤按下事件
     */
    public function handleKeyDown(array $keyEvent): void
    {
        if (!$this->enabled) {
            return;
        }

        // 檢查是否在輸入框中
        if ($this->isInInputField($keyEvent)) {
            return;
        }

        $shortcutKey = $this->buildShortcutKey($keyEvent);
        
        if ($shortcutKey) {
            $this->executeShortcut($shortcutKey);
        }
    }

    /**
     * 執行快捷鍵動作
     */
    public function executeShortcut(string $shortcutKey): void
    {
        $shortcuts = $this->shortcutService->getUserShortcuts();
        
        if (!$shortcuts->has($shortcutKey)) {
            return;
        }

        $shortcut = $shortcuts->get($shortcutKey);
        
        // 記錄執行歷史
        $this->addToExecutionHistory($shortcutKey, $shortcut);

        // 根據動作類型執行對應操作
        switch ($shortcut['action']) {
            case 'navigate':
                $this->handleNavigation($shortcut['target']);
                break;
                
            case 'toggle-search':
                $this->dispatch('toggle-global-search');
                break;
                
            case 'toggle-notifications':
                $this->dispatch('toggle-notifications');
                break;
                
            case 'toggle-theme':
                $this->dispatch('toggle-theme');
                break;
                
            case 'toggle-sidebar':
                $this->dispatch('toggle-sidebar');
                break;
                
            case 'show-help':
                $this->dispatch('show-shortcut-help');
                break;
                
            case 'close-modal':
                $this->dispatch('close-modal');
                break;
                
            case 'logout':
                $this->handleLogout();
                break;
                
            default:
                // 自訂動作
                $this->dispatch('custom-shortcut-action', [
                    'action' => $shortcut['action'],
                    'target' => $shortcut['target'],
                    'shortcut' => $shortcut,
                ]);
                break;
        }

        // 觸發快捷鍵執行事件
        $this->dispatch('shortcut-executed', [
            'key' => $shortcutKey,
            'shortcut' => $shortcut,
        ]);
    }

    /**
     * 處理導航動作
     */
    protected function handleNavigation(string $target): void
    {
        if (filter_var($target, FILTER_VALIDATE_URL)) {
            // 外部連結
            $this->dispatch('navigate-external', ['url' => $target]);
        } else {
            // 內部路由
            $this->redirect($target);
        }
    }

    /**
     * 處理登出動作
     */
    protected function handleLogout(): void
    {
        // 顯示確認對話框
        $this->dispatch('confirm-logout');
    }

    /**
     * 建立快捷鍵字串
     */
    protected function buildShortcutKey(array $keyEvent): ?string
    {
        $modifiers = [];
        $key = strtolower($keyEvent['key'] ?? '');

        // 檢查修飾鍵
        if ($keyEvent['ctrlKey'] ?? false) {
            $modifiers[] = 'ctrl';
        }
        if ($keyEvent['altKey'] ?? false) {
            $modifiers[] = 'alt';
        }
        if ($keyEvent['shiftKey'] ?? false) {
            $modifiers[] = 'shift';
        }
        if ($keyEvent['metaKey'] ?? false) {
            $modifiers[] = 'meta';
        }

        // 處理特殊鍵
        $specialKeys = [
            'Escape' => 'escape',
            'Enter' => 'enter',
            'Tab' => 'tab',
            ' ' => 'space',
        ];

        if (isset($specialKeys[$keyEvent['key'] ?? ''])) {
            $key = $specialKeys[$keyEvent['key']];
        }

        // 忽略單獨的修飾鍵
        if (in_array($key, ['control', 'alt', 'shift', 'meta'])) {
            return null;
        }

        // 建立快捷鍵字串
        if (empty($modifiers) && !in_array($key, ['escape', 'enter', 'tab', 'space'])) {
            return null;
        }

        return implode('+', array_merge($modifiers, [$key]));
    }

    /**
     * 檢查是否在輸入框中
     */
    protected function isInInputField(array $keyEvent): bool
    {
        $targetTag = strtolower($keyEvent['target']['tagName'] ?? '');
        $targetType = strtolower($keyEvent['target']['type'] ?? '');
        
        // 在輸入框、文字區域或可編輯元素中時忽略快捷鍵
        if (in_array($targetTag, ['input', 'textarea', 'select'])) {
            return true;
        }
        
        if ($targetTag === 'input' && in_array($targetType, ['text', 'password', 'email', 'search', 'url', 'tel'])) {
            return true;
        }
        
        if ($keyEvent['target']['contentEditable'] ?? false) {
            return true;
        }
        
        return false;
    }

    /**
     * 新增到執行歷史
     */
    protected function addToExecutionHistory(string $key, array $shortcut): void
    {
        $this->executionHistory[] = [
            'key' => $key,
            'shortcut' => $shortcut,
            'timestamp' => now(),
        ];

        // 只保留最近 10 筆記錄
        if (count($this->executionHistory) > 10) {
            $this->executionHistory = array_slice($this->executionHistory, -10);
        }
    }

    /**
     * 啟用快捷鍵
     */
    public function enable(): void
    {
        $this->enabled = true;
        $this->dispatch('shortcut-manager-enabled');
    }

    /**
     * 停用快捷鍵
     */
    public function disable(): void
    {
        $this->enabled = false;
        $this->dispatch('shortcut-manager-disabled');
    }

    /**
     * 切換快捷鍵啟用狀態
     */
    public function toggle(): void
    {
        $this->enabled = !$this->enabled;
        $this->dispatch($this->enabled ? 'shortcut-manager-enabled' : 'shortcut-manager-disabled');
    }

    /**
     * 獲取所有快捷鍵
     */
    public function getShortcutsProperty(): array
    {
        return $this->shortcutService->getUserShortcuts()->toArray();
    }

    /**
     * 獲取執行歷史
     */
    public function getExecutionHistoryProperty(): array
    {
        return $this->executionHistory;
    }

    /**
     * 清除執行歷史
     */
    public function clearExecutionHistory(): void
    {
        $this->executionHistory = [];
    }

    /**
     * 監聽快捷鍵設定變更事件
     */
    #[On('shortcuts-updated')]
    public function handleShortcutsUpdated(): void
    {
        // 重新載入快捷鍵配置
        $this->dispatch('$refresh');
    }

    /**
     * 監聽快捷鍵啟用/停用事件
     */
    #[On('toggle-shortcuts')]
    public function handleToggleShortcuts(): void
    {
        $this->toggle();
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.layout.keyboard-shortcut-manager');
    }
}