<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\AdminComponent;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

/**
 * 多語言設定預覽元件
 * 
 * 提供即時預覽不同語言設定的功能
 */
class LanguagePreview extends AdminComponent
{
    /**
     * 目前預覽的語言
     */
    public string $previewLocale = '';

    /**
     * 是否啟用預覽模式
     */
    public bool $previewMode = false;

    /**
     * 原始語言設定
     */
    public string $originalLocale = '';

    /**
     * 支援的語言列表
     */
    protected array $supportedLocales = [
        'zh_TW' => '正體中文',
        'en' => 'English',
        'zh_CN' => '简体中文',
        'ja' => '日本語',
    ];

    /**
     * 初始化元件
     */
    public function mount(): void
    {
        parent::mount();
        $this->originalLocale = App::getLocale();
        $this->previewLocale = $this->originalLocale;
    }

    /**
     * 取得支援的語言選項
     */
    #[Computed]
    public function localeOptions(): array
    {
        return $this->supportedLocales;
    }

    /**
     * 取得目前語言的顯示名稱
     */
    #[Computed]
    public function currentLocaleName(): string
    {
        return $this->supportedLocales[$this->previewLocale] ?? $this->previewLocale;
    }

    /**
     * 開始語言預覽
     */
    public function startPreview(string $locale = null): void
    {
        if ($locale) {
            $this->previewLocale = $locale;
        }
        
        $this->previewMode = true;
        
        // 暫時切換語言
        App::setLocale($this->previewLocale);
        
        // 觸發預覽開始事件
        $this->dispatch('language-preview-started', [
            'locale' => $this->previewLocale,
            'localeName' => $this->currentLocaleName
        ]);
        
        $this->addFlash('info', __('settings.preview.preview_mode') . ': ' . $this->currentLocaleName);
    }

    /**
     * 停止語言預覽
     */
    public function stopPreview(): void
    {
        $this->previewMode = false;
        $this->previewLocale = $this->originalLocale;
        
        // 恢復原始語言
        App::setLocale($this->originalLocale);
        
        // 觸發預覽停止事件
        $this->dispatch('language-preview-stopped');
        
        $this->addFlash('info', __('settings.preview.disable'));
    }

    /**
     * 切換預覽語言
     */
    public function switchPreviewLocale(): void
    {
        if (!$this->previewMode) {
            return;
        }
        
        // 切換語言
        App::setLocale($this->previewLocale);
        
        // 觸發語言切換事件
        $this->dispatch('language-preview-changed', [
            'locale' => $this->previewLocale,
            'localeName' => $this->currentLocaleName
        ]);
        
        $this->addFlash('info', __('settings.preview.preview_mode') . ': ' . $this->currentLocaleName);
    }

    /**
     * 套用預覽語言
     */
    public function applyPreview(): void
    {
        if (!$this->previewMode) {
            return;
        }
        
        try {
            // 更新系統設定
            $settingsRepo = app(\App\Repositories\SettingsRepositoryInterface::class);
            $result = $settingsRepo->updateSetting('app.locale', $this->previewLocale);
            
            if ($result) {
                $this->originalLocale = $this->previewLocale;
                $this->previewMode = false;
                
                // 清除語言相關快取
                $this->clearLanguageCache();
                
                // 觸發設定更新事件
                $this->dispatch('setting-updated', 'app.locale');
                $this->dispatch('language-applied', [
                    'locale' => $this->previewLocale,
                    'localeName' => $this->currentLocaleName
                ]);
                
                $this->addFlash('success', __('settings.messages.saved'));
            } else {
                $this->addFlash('error', __('settings.messages.save_failed'));
            }
        } catch (\Exception $e) {
            $this->addFlash('error', __('settings.messages.save_failed') . ': ' . $e->getMessage());
        }
    }

    /**
     * 取得語言設定的示例文字
     */
    #[Computed]
    public function sampleTexts(): array
    {
        return [
            'title' => __('settings.title'),
            'subtitle' => __('settings.subtitle'),
            'save' => __('settings.actions.save'),
            'cancel' => __('settings.actions.cancel'),
            'success' => __('settings.status.success'),
            'error' => __('settings.status.error'),
            'loading' => __('settings.status.loading'),
            'categories' => [
                'basic' => __('settings.categories.basic.name'),
                'security' => __('settings.categories.security.name'),
                'notification' => __('settings.categories.notification.name'),
                'appearance' => __('settings.categories.appearance.name'),
            ],
        ];
    }

    /**
     * 取得日期時間格式示例
     */
    #[Computed]
    public function dateTimeExamples(): array
    {
        $now = now();
        
        return [
            'date' => $now->format('Y-m-d'),
            'time' => $now->format('H:i'),
            'datetime' => $now->format('Y-m-d H:i:s'),
            'relative' => $now->diffForHumans(),
        ];
    }

    /**
     * 清除語言相關快取
     */
    protected function clearLanguageCache(): void
    {
        // 清除翻譯快取
        Cache::forget('translations.' . $this->previewLocale);
        Cache::forget('translations.' . $this->originalLocale);
        
        // 清除語言相關的設定快取
        Cache::forget('app.locale');
        Cache::forget('locale.options');
        
        // 重新載入語言檔案
        app('translator')->setLocale($this->previewLocale);
    }

    /**
     * 監聽語言設定變更
     */
    #[On('setting-updated')]
    public function handleSettingUpdated(string $settingKey): void
    {
        if ($settingKey === 'app.locale') {
            $this->originalLocale = App::getLocale();
            if (!$this->previewMode) {
                $this->previewLocale = $this->originalLocale;
            }
        }
    }

    /**
     * 監聽預覽語言變更
     */
    public function updatedPreviewLocale(): void
    {
        if ($this->previewMode) {
            $this->switchPreviewLocale();
        }
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.settings.language-preview');
    }
}