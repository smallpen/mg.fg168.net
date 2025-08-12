<?php

namespace App\Livewire\Admin\Layout;

use App\Services\KeyboardShortcutService;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;

/**
 * 快捷鍵設定元件
 * 允許使用者自訂快捷鍵配置
 */
class ShortcutSettings extends Component
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
     * 當前編輯的快捷鍵
     */
    public ?string $editingKey = null;

    /**
     * 表單資料
     */
    #[Validate('required|string|max:100')]
    public string $shortcutKey = '';

    #[Validate('required|string|max:255')]
    public string $description = '';

    #[Validate('required|string')]
    public string $action = '';

    #[Validate('nullable|string|max:255')]
    public string $target = '';

    #[Validate('required|string')]
    public string $category = 'custom';

    public bool $enabled = true;

    /**
     * 可用的動作類型
     */
    protected array $availableActions = [
        'navigate' => '導航到頁面',
        'toggle-search' => '切換搜尋',
        'toggle-notifications' => '切換通知',
        'toggle-theme' => '切換主題',
        'toggle-sidebar' => '切換側邊選單',
        'show-help' => '顯示說明',
        'close-modal' => '關閉對話框',
        'custom' => '自訂動作',
    ];

    /**
     * 衝突檢測結果
     */
    public array $conflicts = [];

    /**
     * 初始化元件
     */
    public function boot(KeyboardShortcutService $shortcutService): void
    {
        $this->shortcutService = $shortcutService;
    }

    /**
     * 開啟設定對話框
     */
    public function open(): void
    {
        $this->isOpen = true;
        $this->resetForm();
    }

    /**
     * 關閉設定對話框
     */
    public function close(): void
    {
        $this->isOpen = false;
        $this->resetForm();
    }

    /**
     * 重置表單
     */
    public function resetForm(): void
    {
        $this->editingKey = null;
        $this->shortcutKey = '';
        $this->description = '';
        $this->action = '';
        $this->target = '';
        $this->category = 'custom';
        $this->enabled = true;
        $this->conflicts = [];
        $this->resetValidation();
    }

    /**
     * 編輯快捷鍵
     */
    public function editShortcut(string $key): void
    {
        $shortcuts = $this->shortcutService->getUserShortcuts();
        
        if (!$shortcuts->has($key)) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '找不到指定的快捷鍵',
            ]);
            return;
        }

        $shortcut = $shortcuts->get($key);
        
        $this->editingKey = $key;
        $this->shortcutKey = $key;
        $this->description = $shortcut['description'] ?? '';
        $this->action = $shortcut['action'] ?? '';
        $this->target = $shortcut['target'] ?? '';
        $this->category = $shortcut['category'] ?? 'custom';
        $this->enabled = $shortcut['enabled'] ?? true;
        
        $this->open();
    }

    /**
     * 建立新快捷鍵
     */
    public function createShortcut(): void
    {
        $this->resetForm();
        $this->open();
    }

    /**
     * 儲存快捷鍵
     */
    public function save(): void
    {
        $this->validate();

        // 檢查快捷鍵格式
        if (!$this->shortcutService->isValidShortcutKey($this->shortcutKey)) {
            $this->addError('shortcutKey', '快捷鍵格式不正確');
            return;
        }

        // 檢查衝突（除了正在編輯的快捷鍵）
        if ($this->editingKey !== $this->shortcutKey && 
            $this->shortcutService->hasConflict($this->shortcutKey)) {
            $this->addError('shortcutKey', '此快捷鍵已被使用');
            return;
        }

        $config = [
            'description' => $this->description,
            'action' => $this->action,
            'target' => $this->target,
            'category' => $this->category,
            'enabled' => $this->enabled,
        ];

        try {
            if ($this->editingKey) {
                // 更新現有快捷鍵
                if ($this->editingKey !== $this->shortcutKey) {
                    // 快捷鍵改變了，需要刪除舊的並建立新的
                    $this->shortcutService->removeShortcut($this->editingKey);
                    $this->shortcutService->registerShortcut($this->shortcutKey, $config);
                } else {
                    // 只更新配置
                    $this->shortcutService->updateShortcut($this->shortcutKey, $config);
                }
            } else {
                // 建立新快捷鍵
                $this->shortcutService->registerShortcut($this->shortcutKey, $config);
            }

            $this->dispatch('shortcuts-updated');
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => '快捷鍵已儲存',
            ]);
            
            $this->close();
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '儲存失敗：' . $e->getMessage(),
            ]);
        }
    }

    /**
     * 刪除快捷鍵
     */
    public function deleteShortcut(string $key): void
    {
        try {
            $this->shortcutService->removeShortcut($key);
            
            $this->dispatch('shortcuts-updated');
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => '快捷鍵已刪除',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '刪除失敗：' . $e->getMessage(),
            ]);
        }
    }

    /**
     * 重置為預設設定
     */
    public function resetToDefaults(): void
    {
        try {
            $this->shortcutService->resetToDefaults();
            
            $this->dispatch('shortcuts-updated');
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => '已重置為預設快捷鍵設定',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '重置失敗：' . $e->getMessage(),
            ]);
        }
    }

    /**
     * 匯出快捷鍵設定
     */
    public function exportSettings(): void
    {
        try {
            $shortcuts = $this->shortcutService->exportShortcuts();
            
            $this->dispatch('download-file', [
                'filename' => 'keyboard-shortcuts-' . date('Y-m-d') . '.json',
                'content' => json_encode($shortcuts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                'type' => 'application/json',
            ]);
            
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => '快捷鍵設定已匯出',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '匯出失敗：' . $e->getMessage(),
            ]);
        }
    }

    /**
     * 匯入快捷鍵設定
     */
    public function importSettings(array $shortcuts, bool $overwrite = false): void
    {
        try {
            $results = $this->shortcutService->importShortcuts($shortcuts, $overwrite);
            
            $this->dispatch('shortcuts-updated');
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => "已匯入 {$results['imported']} 個快捷鍵，跳過 {$results['skipped']} 個",
            ]);
            
            if (!empty($results['conflicts'])) {
                $this->conflicts = $results['conflicts'];
            }
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '匯入失敗：' . $e->getMessage(),
            ]);
        }
    }

    /**
     * 檢測快捷鍵衝突
     */
    public function checkConflicts(): void
    {
        if (empty($this->shortcutKey)) {
            return;
        }

        if ($this->editingKey !== $this->shortcutKey && 
            $this->shortcutService->hasConflict($this->shortcutKey)) {
            $this->addError('shortcutKey', '此快捷鍵已被使用');
        } else {
            $this->resetErrorBag('shortcutKey');
        }
    }

    /**
     * 更新快捷鍵輸入時檢查衝突
     */
    public function updatedShortcutKey(): void
    {
        $this->checkConflicts();
    }

    /**
     * 獲取所有快捷鍵
     */
    public function getShortcutsProperty(): array
    {
        return $this->shortcutService->getUserShortcuts()->toArray();
    }

    /**
     * 獲取分類列表
     */
    public function getCategoriesProperty(): array
    {
        return $this->shortcutService->getCategories();
    }

    /**
     * 獲取可用動作
     */
    public function getAvailableActionsProperty(): array
    {
        return $this->availableActions;
    }

    /**
     * 監聽開啟設定事件
     */
    #[On('open-shortcut-settings')]
    public function handleOpenSettings(): void
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
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.layout.shortcut-settings');
    }
}