<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\AdminComponent;
use App\Repositories\SettingsRepositoryInterface;
use App\Services\ConfigurationService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

/**
 * 系統設定列表元件
 * 
 * 提供設定的搜尋、篩選、分類檢視和批量操作功能
 */
class SettingsList extends AdminComponent
{
    /**
     * 搜尋關鍵字
     */
    public string $search = '';

    /**
     * 分類篩選
     */
    public string $categoryFilter = 'all';

    /**
     * 變更狀態篩選
     */
    public string $changedFilter = 'all';

    /**
     * 設定類型篩選
     */
    public string $typeFilter = 'all';

    /**
     * 檢視模式
     */
    public string $viewMode = 'category';

    /**
     * 展開的分類
     */
    public array $expandedCategories = [];

    /**
     * 選中的設定項目
     */
    public array $selectedSettings = [];

    /**
     * 批量操作類型
     */
    public string $bulkAction = '';

    /**
     * 顯示批量操作確認對話框
     */
    public bool $showBulkConfirm = false;

    /**
     * 初始化元件
     */
    public function mount(): void
    {
        parent::mount();
        
        // 預設展開所有分類
        $this->expandedCategories = array_keys($this->getConfigService()->getCategories());
    }

    /**
     * 取得設定資料庫
     */
    protected function getSettingsRepository(): SettingsRepositoryInterface
    {
        return app(SettingsRepositoryInterface::class);
    }

    /**
     * 取得配置服務
     */
    protected function getConfigService(): ConfigurationService
    {
        return app(ConfigurationService::class);
    }

    /**
     * 取得篩選後的設定列表
     */
    #[Computed]
    public function settings(): Collection
    {
        $filters = [
            'category' => $this->categoryFilter !== 'all' ? $this->categoryFilter : null,
            'type' => $this->typeFilter !== 'all' ? $this->typeFilter : null,
            'changed' => $this->changedFilter !== 'all' ? ($this->changedFilter === 'changed') : null,
        ];

        return $this->getSettingsRepository()->searchSettings($this->search, $filters);
    }

    /**
     * 取得按分類分組的設定
     */
    #[Computed]
    public function settingsByCategory(): Collection
    {
        return $this->settings->groupBy('category');
    }

    /**
     * 取得所有可用分類
     */
    #[Computed]
    public function categories(): array
    {
        return $this->getConfigService()->getCategories();
    }

    /**
     * 取得所有可用類型
     */
    #[Computed]
    public function availableTypes(): Collection
    {
        return $this->getSettingsRepository()->getAvailableTypes();
    }

    /**
     * 取得已變更的設定
     */
    #[Computed]
    public function changedSettings(): Collection
    {
        return $this->getSettingsRepository()->getChangedSettings();
    }

    /**
     * 取得統計資訊
     */
    #[Computed]
    public function stats(): array
    {
        $allSettings = $this->getSettingsRepository()->getAllSettings();
        $changedSettings = $this->changedSettings;

        return [
            'total' => $allSettings->count(),
            'changed' => $changedSettings->count(),
            'categories' => $allSettings->groupBy('category')->count(),
            'filtered' => $this->settings->count(),
        ];
    }

    /**
     * 切換分類展開狀態
     */
    public function toggleCategory(string $category): void
    {
        if (in_array($category, $this->expandedCategories)) {
            $this->expandedCategories = array_diff($this->expandedCategories, [$category]);
        } else {
            $this->expandedCategories[] = $category;
        }
    }

    /**
     * 展開所有分類
     */
    public function expandAllCategories(): void
    {
        $this->expandedCategories = array_keys($this->categories);
    }

    /**
     * 收合所有分類
     */
    public function collapseAllCategories(): void
    {
        $this->expandedCategories = [];
    }

    /**
     * 編輯設定
     */
    public function editSetting(string $key): void
    {
        $this->dispatch('open-setting-form', settingKey: $key);
    }

    /**
     * 重設設定為預設值
     */
    public function resetSetting(string $key): void
    {
        try {
            $result = $this->getSettingsRepository()->resetSetting($key);
            
            if ($result) {
                $this->dispatch('setting-updated', settingKey: $key);
                $this->addFlash('success', "設定 '{$key}' 已重設為預設值");
            } else {
                $this->addFlash('error', "無法重設設定 '{$key}'");
            }
        } catch (\Exception $e) {
            $this->addFlash('error', "重設設定時發生錯誤：{$e->getMessage()}");
        }
    }

    /**
     * 切換設定選中狀態
     */
    public function toggleSettingSelection(string $key): void
    {
        if (in_array($key, $this->selectedSettings)) {
            $this->selectedSettings = array_diff($this->selectedSettings, [$key]);
        } else {
            $this->selectedSettings[] = $key;
        }
    }

    /**
     * 全選/取消全選當前頁面的設定
     */
    public function toggleSelectAll(): void
    {
        $currentSettingKeys = $this->settings->pluck('key')->toArray();
        
        if (count(array_intersect($this->selectedSettings, $currentSettingKeys)) === count($currentSettingKeys)) {
            // 如果當前頁面的設定都已選中，則取消選中
            $this->selectedSettings = array_diff($this->selectedSettings, $currentSettingKeys);
        } else {
            // 否則選中當前頁面的所有設定
            $this->selectedSettings = array_unique(array_merge($this->selectedSettings, $currentSettingKeys));
        }
    }

    /**
     * 清除所有選中的設定
     */
    public function clearSelection(): void
    {
        $this->selectedSettings = [];
    }

    /**
     * 執行批量操作
     */
    public function executeBulkAction(): void
    {
        if (empty($this->selectedSettings) || empty($this->bulkAction)) {
            $this->addFlash('warning', '請選擇設定項目和操作類型');
            return;
        }

        $this->showBulkConfirm = true;
    }

    /**
     * 確認批量操作
     */
    public function confirmBulkAction(): void
    {
        try {
            $successCount = 0;
            $errorCount = 0;

            foreach ($this->selectedSettings as $settingKey) {
                switch ($this->bulkAction) {
                    case 'reset':
                        if ($this->getSettingsRepository()->resetSetting($settingKey)) {
                            $successCount++;
                        } else {
                            $errorCount++;
                        }
                        break;
                    
                    case 'export':
                        // 批量匯出將在後面實作
                        break;
                }
            }

            if ($successCount > 0) {
                $this->addFlash('success', "成功處理 {$successCount} 個設定項目");
                $this->dispatch('settings-bulk-updated');
            }

            if ($errorCount > 0) {
                $this->addFlash('warning', "有 {$errorCount} 個設定項目處理失敗");
            }

            $this->clearSelection();
            $this->bulkAction = '';
            $this->showBulkConfirm = false;

        } catch (\Exception $e) {
            $this->addFlash('error', "批量操作時發生錯誤：{$e->getMessage()}");
        }
    }

    /**
     * 取消批量操作
     */
    public function cancelBulkAction(): void
    {
        $this->showBulkConfirm = false;
        $this->bulkAction = '';
    }

    /**
     * 匯出設定
     */
    public function exportSettings(): void
    {
        $this->dispatch('open-export-dialog');
    }

    /**
     * 開啟匯入對話框
     */
    public function openImportDialog(): void
    {
        $this->dispatch('open-import-dialog');
    }

    /**
     * 建立備份
     */
    public function createBackup(): void
    {
        $this->dispatch('open-backup-dialog');
    }

    /**
     * 重置所有篩選條件
     */
    public function resetFilters(): void
    {
        try {
            // 記錄篩選重置操作
            \Log::info('🔄 resetFilters - 篩選重置開始', [
                'timestamp' => now()->toISOString(),
                'user' => auth()->user()->username ?? 'unknown',
                'before_reset' => [
                    'search' => $this->search ?? '',
                    'categoryFilter' => $this->categoryFilter ?? 'all',
                    'changedFilter' => $this->changedFilter ?? 'all',
                    'typeFilter' => $this->typeFilter ?? 'all',
                ]
            ]);
            
            // 重置所有篩選條件
            $this->search = '';
            $this->categoryFilter = 'all';
            $this->changedFilter = 'all';
            $this->typeFilter = 'all';
            
            // 清除選中的設定
            $this->selectedSettings = [];
            $this->bulkAction = '';
            
            // 清除快取
            $this->resetValidation();
            
            // 強制重新渲染整個元件
            $this->skipRender = false;
            
            // 強制 Livewire 同步狀態到前端
            $this->js('
                // 強制更新所有表單元素的值
                setTimeout(() => {
                    const searchInputs = document.querySelectorAll(\'input[wire\\\\:model\\\\.live="search"]\');
                    searchInputs.forEach(input => {
                        input.value = "";
                        input.dispatchEvent(new Event("input", { bubbles: true }));
                    });
                    
                    const filterSelects = document.querySelectorAll(\'select[wire\\\\:model\\\\.live*="Filter"]\');
                    filterSelects.forEach(select => {
                        select.value = "all";
                        select.dispatchEvent(new Event("change", { bubbles: true }));
                    });
                    
                    console.log("✅ 設定列表表單元素已強制同步");
                }, 100);
            ');
            
            // 發送強制 UI 更新事件
            $this->dispatch('force-ui-update');
            
            // 發送前端重置事件，讓 Alpine.js 處理
            $this->dispatch('reset-form-elements');
            
            // 顯示成功訊息
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => '篩選條件已清除'
            ]);
            
            // 記錄重置完成
            \Log::info('✅ resetFilters - 篩選重置完成', [
                'after_reset' => [
                    'search' => $this->search,
                    'categoryFilter' => $this->categoryFilter,
                    'changedFilter' => $this->changedFilter,
                    'typeFilter' => $this->typeFilter,
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('重置方法執行失敗', [
                'method' => 'resetFilters',
                'error' => $e->getMessage(),
                'component' => static::class,
            ]);
            
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '重置操作失敗，請重試'
            ]);
        }
    }

    /**
     * 清除搜尋和篩選（向後相容）
     */
    public function clearFilters(): void
    {
        $this->resetFilters();
    }

    /**
     * 監聽設定更新事件
     */
    #[On('setting-updated')]
    public function handleSettingUpdated(): void
    {
        // 重新整理快取的計算屬性
        unset($this->settings);
        unset($this->changedSettings);
        unset($this->stats);
    }

    /**
     * 監聽設定批量更新事件
     */
    #[On('settings-bulk-updated')]
    public function handleSettingsBulkUpdated(): void
    {
        $this->handleSettingUpdated();
    }

    /**
     * 監聽設定匯入完成事件
     */
    #[On('settings-imported')]
    public function handleSettingsImported(): void
    {
        $this->handleSettingUpdated();
        $this->addFlash('success', '設定匯入完成');
    }

    /**
     * 監聽備份建立完成事件
     */
    #[On('backup-created')]
    public function handleBackupCreated(): void
    {
        $this->addFlash('success', '設定備份已建立');
    }

    /**
     * 取得分類圖示
     */
    public function getCategoryIcon(string $category): string
    {
        return $this->categories[$category]['icon'] ?? 'cog';
    }

    /**
     * 取得分類名稱
     */
    public function getCategoryName(string $category): string
    {
        return $this->categories[$category]['name'] ?? $category;
    }

    /**
     * 取得分類描述
     */
    public function getCategoryDescription(string $category): string
    {
        return $this->categories[$category]['description'] ?? '';
    }

    /**
     * 檢查分類是否展開
     */
    public function isCategoryExpanded(string $category): bool
    {
        return in_array($category, $this->expandedCategories);
    }

    /**
     * 檢查設定是否選中
     */
    public function isSettingSelected(string $key): bool
    {
        return in_array($key, $this->selectedSettings);
    }

    /**
     * 檢查是否全選
     */
    public function isAllSelected(): bool
    {
        $currentSettingKeys = $this->settings->pluck('key')->toArray();
        return count(array_intersect($this->selectedSettings, $currentSettingKeys)) === count($currentSettingKeys);
    }

    /**
     * 檢查是否部分選中
     */
    public function isPartiallySelected(): bool
    {
        $currentSettingKeys = $this->settings->pluck('key')->toArray();
        $selectedCount = count(array_intersect($this->selectedSettings, $currentSettingKeys));
        return $selectedCount > 0 && $selectedCount < count($currentSettingKeys);
    }

    /**
     * 渲染元件
     */
    
    /**
     * search 更新時重置分頁
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }


    
    /**
     * statusFilter 更新時重置分頁
     */
    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }




    
    /**
     * roleFilter 更新時重置分頁
     */
    public function updatedRoleFilter(): void
    {
        $this->resetPage();
    }






    public function render()
    {
        return view('livewire.admin.settings.settings-list')
            ->layout('components.layouts.admin');
    }
}
