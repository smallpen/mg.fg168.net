<?php

namespace App\Livewire\Admin\Settings;

use App\Repositories\SettingsRepositoryInterface;
use Livewire\Component;
use App\Services\ConfigurationService;
use Illuminate\Support\Facades\Log;
use Livewire\WithFileUploads;

/**
 * 外觀設定管理元件
 * 
 * 負責處理主題、顏色、Logo 等外觀相關的設定
 */
class AppearanceSettings extends Component
{
    use WithFileUploads;

    // 設定值綁定
    public $settings = [];

    // 檔案上傳綁定
    public $logo;
    public $favicon;
    public $loginBackground;

    // 響應式設定
    public $responsiveConfig = [
        'mobile_breakpoint' => 768,
        'tablet_breakpoint' => 1024,
        'desktop_breakpoint' => 1280,
        'enable_mobile_menu' => true,
        'enable_responsive_tables' => true,
        'enable_touch_friendly' => true,
    ];

    // 預覽模式
    public $previewMode = false;
    public $previewDevice = 'desktop'; // desktop, tablet, mobile

    // 原始設定值
    protected $originalSettings;

    protected function rules()
    {
        return [
            'settings.appearance.default_theme' => 'required|string|in:light,dark,auto',
            'settings.appearance.primary_color' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
            'settings.appearance.secondary_color' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
            'settings.appearance.page_title_format' => 'required|string|max:100',
            'settings.appearance.custom_css' => 'nullable|string|max:10000',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
            'favicon' => 'nullable|image|mimes:png,ico|max:512',
            'loginBackground' => 'nullable|image|mimes:png,jpg,jpeg|max:5120',
            'responsiveConfig.mobile_breakpoint' => 'required|integer|min:320|max:1024',
            'responsiveConfig.tablet_breakpoint' => 'required|integer|min:768|max:1440',
            'responsiveConfig.desktop_breakpoint' => 'required|integer|min:1024|max:2560',
            'responsiveConfig.enable_mobile_menu' => 'required|boolean',
            'responsiveConfig.enable_responsive_tables' => 'required|boolean',
            'responsiveConfig.enable_touch_friendly' => 'required|boolean',
        ];
    }

    protected function messages()
    {
        return [
            'settings.appearance.primary_color.regex' => '主要顏色格式不正確，請使用十六進位顏色碼 (例如 #3B82F6)',
            'settings.appearance.secondary_color.regex' => '次要顏色格式不正確，請使用十六進位顏色碼 (例如 #6B7280)',
            'logo.image' => '系統標誌必須是圖片格式',
            'logo.max' => '系統標誌不能超過 2MB',
        ];
    }

    protected SettingsRepositoryInterface $settingsRepository;

    public function boot(SettingsRepositoryInterface $settingsRepository)
    {
        $this->settingsRepository = $settingsRepository;
    }

    /**
     * 元件初始化
     */
    public function mount()
    {
        $this->loadSettings();
    }

    /**
     * 載入設定
     */
    public function loadSettings()
    {
        // 1. 從設定檔獲取所有 'appearance' 分類的設定定義
        $settingConfigs = collect(config('system-settings.settings'))
            ->filter(fn ($config) => $config['category'] === 'appearance');

        // 2. 從資料庫獲取已儲存的設定值
        $savedSettings = $this->settingsRepository->getSettingsByCategory('appearance')
                                                  ->keyBy('key');

        // 3. 建立供元件使用的設定陣列
        $this->settings = $settingConfigs->mapWithKeys(function ($config, $key) use ($savedSettings) {
            // 如果資料庫中有值，則使用它；否則，使用設定檔中的預設值
            $value = $savedSettings->has($key) ? $savedSettings->get($key)->value : $config['default'];
            return [$key => $value];
        })->all();

        // 4. 載入響應式設定
        if (isset($this->settings['appearance.responsive_config'])) {
            $this->responsiveConfig = array_merge(
                $this->responsiveConfig,
                is_array($this->settings['appearance.responsive_config']) 
                    ? $this->settings['appearance.responsive_config']
                    : json_decode($this->settings['appearance.responsive_config'], true) ?? []
            );
        }

        $this->originalSettings = $this->settings;
    }

    /**
     * 儲存設定
     */
    public function save()
    {
        $this->validate();

        try {
            // 處理檔案上傳
            if ($this->logo) {
                $this->settings['appearance.logo_url'] = $this->logo->store('logos', 'public');
            }
            if ($this->favicon) {
                $this->settings['appearance.favicon_url'] = $this->favicon->store('favicons', 'public');
            }
            if ($this->loginBackground) {
                $this->settings['appearance.login_background_url'] = $this->loginBackground->store('backgrounds', 'public');
            }

            // 處理響應式設定
            $this->settings['appearance.responsive_config'] = $this->responsiveConfig;

            // 更新設定
            $this->settingsRepository->updateSettings($this->settings);

            // 清除已上傳的暫存檔案
            $this->logo = null;
            $this->favicon = null;
            $this->loginBackground = null;

            // 重新載入設定
            $this->loadSettings();
            
            $this->dispatch('saved', [
                'type' => 'success',
                'message' => '外觀設定已成功儲存！'
            ]);

            // 觸發預覽更新事件
            if ($this->previewMode) {
                $this->dispatch('appearance-preview-updated', [
                    'settings' => $this->settings,
                    'responsiveConfig' => $this->responsiveConfig
                ]);
            }

        } catch (\Exception $e) {
            Log::error('儲存外觀設定失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            $this->dispatch('saved', [
                'type' => 'error',
                'message' => '儲存外觀設定時發生錯誤，請稍後再試。'
            ]);
        }
    }

    /**
     * 切換預覽模式
     */
    public function togglePreview()
    {
        $this->previewMode = !$this->previewMode;
        
        if ($this->previewMode) {
            $this->dispatch('appearance-preview-start', [
                'settings' => $this->settings,
                'responsiveConfig' => $this->responsiveConfig
            ]);
        } else {
            $this->dispatch('appearance-preview-stop');
        }
    }

    /**
     * 切換預覽裝置
     */
    public function switchPreviewDevice($device)
    {
        $this->previewDevice = $device;
        
        if ($this->previewMode) {
            $this->dispatch('appearance-preview-device-changed', [
                'device' => $device,
                'responsiveConfig' => $this->responsiveConfig
            ]);
        }
    }

    /**
     * 即時預覽設定變更
     */
    public function previewSetting($key, $value)
    {
        if ($this->previewMode) {
            $this->dispatch('appearance-setting-preview', [
                'key' => $key,
                'value' => $value
            ]);
        }
    }

    /**
     * 重設為預設值
     */
    public function resetToDefaults()
    {
        $settingConfigs = collect(config('system-settings.settings'))
            ->filter(fn ($config) => $config['category'] === 'appearance');

        $this->settings = $settingConfigs->mapWithKeys(function ($config, $key) {
            return [$key => $config['default']];
        })->all();

        // 重設響應式設定
        $this->responsiveConfig = [
            'mobile_breakpoint' => 768,
            'tablet_breakpoint' => 1024,
            'desktop_breakpoint' => 1280,
            'enable_mobile_menu' => true,
            'enable_responsive_tables' => true,
            'enable_touch_friendly' => true,
        ];

        $this->dispatch('settings-reset', [
            'type' => 'info',
            'message' => '設定已重設為預設值'
        ]);
    }

    /**
     * 渲染元件
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.admin.settings.appearance-settings')
            ->layout('layouts.admin');
    }
}