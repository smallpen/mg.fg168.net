<?php

namespace App\Livewire\Admin\Layout;

use App\Services\KeyboardShortcutService;
use Livewire\Component;
use Livewire\Attributes\On;

/**
 * 快捷鍵說明對話框元件
 * 顯示所有可用的快捷鍵和說明
 */
class ShortcutHelpDialog extends Component
{
    /**
     * 快捷鍵服務
     */
    protected KeyboardShortcutService $shortcutService;

    /**
     * 對話框是否開啟
     */
    public bool $isOpen = false;

    /**
     * 當前選擇的分類
     */
    public string $selectedCategory = 'all';

    /**
     * 搜尋關鍵字
     */
    public string $searchQuery = '';

    /**
     * 初始化元件
     */
    public function boot(KeyboardShortcutService $shortcutService): void
    {
        $this->shortcutService = $shortcutService;
    }

    /**
     * 開啟對話框
     */
    public function open(): void
    {
        $this->isOpen = true;
        $this->dispatch('dialog-opened', ['dialog' => 'shortcut-help']);
    }

    /**
     * 關閉對話框
     */
    public function close(): void
    {
        $this->isOpen = false;
        $this->searchQuery = '';
        $this->selectedCategory = 'all';
        $this->dispatch('dialog-closed', ['dialog' => 'shortcut-help']);
    }

    /**
     * 切換對話框狀態
     */
    public function toggle(): void
    {
        if ($this->isOpen) {
            $this->close();
        } else {
            $this->open();
        }
    }

    /**
     * 設定選擇的分類
     */
    public function setCategory(string $category): void
    {
        $this->selectedCategory = $category;
    }

    /**
     * 清除搜尋
     */
    public function clearSearch(): void
    {
        $this->searchQuery = '';
    }

    /**
     * 獲取過濾後的快捷鍵
     */
    public function getFilteredShortcutsProperty(): array
    {
        $shortcuts = $this->shortcutService->getUserShortcuts();

        // 按分類過濾
        if ($this->selectedCategory !== 'all') {
            $shortcuts = $shortcuts->filter(function ($shortcut) {
                return ($shortcut['category'] ?? 'custom') === $this->selectedCategory;
            });
        }

        // 按搜尋關鍵字過濾
        if (!empty($this->searchQuery)) {
            $query = strtolower($this->searchQuery);
            $shortcuts = $shortcuts->filter(function ($shortcut, $key) use ($query) {
                return str_contains(strtolower($key), $query) ||
                       str_contains(strtolower($shortcut['description'] ?? ''), $query);
            });
        }

        // 按分類分組
        return $shortcuts->groupBy(function ($shortcut) {
            return $shortcut['category'] ?? 'custom';
        })->toArray();
    }

    /**
     * 獲取分類列表
     */
    public function getCategoriesProperty(): array
    {
        return $this->shortcutService->getCategories();
    }

    /**
     * 獲取分類統計
     */
    public function getCategoryStatsProperty(): array
    {
        $shortcuts = $this->shortcutService->getUserShortcuts();
        $stats = ['all' => $shortcuts->count()];

        foreach ($this->categories as $key => $name) {
            $stats[$key] = $shortcuts->filter(function ($shortcut) use ($key) {
                return ($shortcut['category'] ?? 'custom') === $key;
            })->count();
        }

        return $stats;
    }

    /**
     * 格式化快捷鍵顯示
     */
    public function formatShortcutKey(string $key): string
    {
        $parts = explode('+', $key);
        $formatted = [];

        foreach ($parts as $part) {
            switch (strtolower($part)) {
                case 'ctrl':
                    $formatted[] = 'Ctrl';
                    break;
                case 'alt':
                    $formatted[] = 'Alt';
                    break;
                case 'shift':
                    $formatted[] = 'Shift';
                    break;
                case 'meta':
                    $formatted[] = 'Cmd';
                    break;
                case 'escape':
                    $formatted[] = 'Esc';
                    break;
                case 'enter':
                    $formatted[] = 'Enter';
                    break;
                case 'space':
                    $formatted[] = 'Space';
                    break;
                case 'tab':
                    $formatted[] = 'Tab';
                    break;
                default:
                    $formatted[] = strtoupper($part);
                    break;
            }
        }

        return implode(' + ', $formatted);
    }

    /**
     * 複製快捷鍵到剪貼簿
     */
    public function copyShortcut(string $key): void
    {
        $this->dispatch('copy-to-clipboard', [
            'text' => $key,
            'message' => "快捷鍵 '{$this->formatShortcutKey($key)}' 已複製到剪貼簿",
        ]);
    }

    /**
     * 測試快捷鍵
     */
    public function testShortcut(string $key): void
    {
        $this->dispatch('test-shortcut', ['key' => $key]);
        $this->dispatch('show-toast', [
            'type' => 'info',
            'message' => "正在測試快捷鍵: {$this->formatShortcutKey($key)}",
        ]);
    }

    /**
     * 監聽顯示說明對話框事件
     */
    #[On('show-shortcut-help')]
    public function handleShowHelp(): void
    {
        $this->open();
    }

    /**
     * 監聽關閉對話框事件
     */
    #[On('close-modal')]
    public function handleCloseModal(): void
    {
        if ($this->isOpen) {
            $this->close();
        }
    }

    /**
     * 監聽鍵盤事件
     */
    #[On('keyboard-event')]
    public function handleKeyboardEvent(array $event): void
    {
        if (!$this->isOpen) {
            return;
        }

        // 在對話框開啟時處理特定鍵盤事件
        if ($event['key'] === 'Escape') {
            $this->close();
        }
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.layout.shortcut-help-dialog');
    }
}