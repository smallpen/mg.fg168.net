<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\AdminComponent;
use App\Repositories\SettingsRepositoryInterface;
use App\Services\ConfigurationService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

/**
 * 系統設定統計資訊元件
 * 
 * 顯示設定的統計資訊，包含總數、已變更數量、分類數量和最近備份資訊
 */
class SettingsStats extends AdminComponent
{
    /**
     * 初始化元件
     */
    public function mount(): void
    {
        parent::mount();
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
     * 取得統計資訊
     */
    #[Computed]
    public function stats(): array
    {
        try {
            $allSettings = $this->getSettingsRepository()->getAllSettings();
            $changedSettings = $this->getSettingsRepository()->getChangedSettings();
            $categories = $this->getConfigService()->getCategories();

            return [
                'total' => $allSettings->count(),
                'changed' => $changedSettings->count(),
                'categories' => count($categories),
                'lastBackup' => $this->getLastBackupInfo(),
            ];
        } catch (\Exception $e) {
            \Log::error('取得設定統計資訊失敗', [
                'error' => $e->getMessage(),
                'component' => static::class,
            ]);

            return [
                'total' => 0,
                'changed' => 0,
                'categories' => 0,
                'lastBackup' => '無',
            ];
        }
    }

    /**
     * 取得最後備份資訊
     */
    protected function getLastBackupInfo(): string
    {
        try {
            $lastBackup = \App\Models\SettingBackup::latest()->first();
            
            if ($lastBackup) {
                return $lastBackup->created_at->diffForHumans();
            }
            
            return '無';
        } catch (\Exception $e) {
            return '無';
        }
    }

    /**
     * 監聽設定更新事件
     */
    #[On('setting-updated')]
    public function handleSettingUpdated(): void
    {
        // 重新整理快取的計算屬性
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
     * 監聽備份建立完成事件
     */
    #[On('backup-created')]
    public function handleBackupCreated(): void
    {
        unset($this->stats);
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.settings.settings-stats');
    }
}