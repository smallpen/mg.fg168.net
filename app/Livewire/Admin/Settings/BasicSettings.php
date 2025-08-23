<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\AdminComponent;
use App\Repositories\SettingsRepositoryInterface;
use App\Services\ConfigurationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

/**
 * 基本設定管理元件
 * 
 * 提供應用程式基本資訊、時區、語言、日期時間格式等設定的管理功能
 */
class BasicSettings extends AdminComponent
{
    /**
     * 基本設定值
     */
    public array $settings = [];

    /**
     * 原始設定值（用於比較變更）
     */
    public array $originalSettings = [];

    /**
     * 正在儲存
     */
    public bool $saving = false;

    /**
     * 顯示預覽
     */
    public bool $showPreview = false;

    /**
     * 預覽設定
     */
    public array $previewSettings = [];

    /**
     * 驗證錯誤
     */
    public array $validationErrors = [];

    /**
     * 基本設定鍵值列表
     */
    protected array $basicSettingKeys = [
        'app.name',
        'app.description', 
        'app.timezone',
        'app.locale',
        'app.date_format',
        'app.time_format',
    ];

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
     * 初始化元件
     */
    public function mount(): void
    {
        parent::mount();
        $this->loadSettings();
    }

    /**
     * 載入設定資料
     */
    public function loadSettings(): void
    {
        $settingsData = $this->getSettingsRepository()->getSettings($this->basicSettingKeys);
        
        foreach ($this->basicSettingKeys as $key) {
            $setting = $settingsData->get($key);
            $this->settings[$key] = $setting ? $setting->value : $this->getDefaultValue($key);
        }
        
        $this->originalSettings = $this->settings;
        $this->previewSettings = $this->settings;
    }

    /**
     * 取得設定的預設值
     */
    protected function getDefaultValue(string $key)
    {
        $config = $this->getConfigService()->getSettingConfig($key);
        return $config['default'] ?? '';
    }

    /**
     * 取得時區選項
     */
    #[Computed]
    public function timezoneOptions(): array
    {
        return [
            'Asia/Taipei' => '台北 (UTC+8)',
            'Asia/Shanghai' => '上海 (UTC+8)', 
            'Asia/Hong_Kong' => '香港 (UTC+8)',
            'Asia/Tokyo' => '東京 (UTC+9)',
            'Asia/Seoul' => '首爾 (UTC+9)',
            'Asia/Singapore' => '新加坡 (UTC+8)',
            'UTC' => 'UTC (UTC+0)',
            'America/New_York' => '紐約 (UTC-5)',
            'America/Los_Angeles' => '洛杉磯 (UTC-8)',
            'Europe/London' => '倫敦 (UTC+0)',
            'Europe/Paris' => '巴黎 (UTC+1)',
            'Australia/Sydney' => '雪梨 (UTC+10)',
        ];
    }

    /**
     * 取得語言選項
     */
    #[Computed]
    public function localeOptions(): array
    {
        return [
            'zh_TW' => '正體中文 (繁體)',
            'zh_CN' => '简体中文 (简体)',
            'en' => 'English',
            'ja' => '日本語',
            'ko' => '한국어',
        ];
    }

    /**
     * 取得日期格式選項
     */
    #[Computed]
    public function dateFormatOptions(): array
    {
        $now = now();
        return [
            'Y-m-d' => $now->format('Y-m-d') . ' (2024-01-15)',
            'd/m/Y' => $now->format('d/m/Y') . ' (15/01/2024)',
            'm/d/Y' => $now->format('m/d/Y') . ' (01/15/2024)',
            'd-m-Y' => $now->format('d-m-Y') . ' (15-01-2024)',
            'Y年m月d日' => $now->format('Y年m月d日') . ' (2024年01月15日)',
        ];
    }

    /**
     * 取得時間格式選項
     */
    #[Computed]
    public function timeFormatOptions(): array
    {
        $now = now();
        return [
            'H:i' => $now->format('H:i') . ' (24 小時制)',
            'g:i A' => $now->format('g:i A') . ' (12 小時制)',
            'H:i:s' => $now->format('H:i:s') . ' (24 小時制含秒)',
            'g:i:s A' => $now->format('g:i:s A') . ' (12 小時制含秒)',
        ];
    }

    /**
     * 檢查是否有變更
     */
    #[Computed]
    public function hasChanges(): bool
    {
        foreach ($this->basicSettingKeys as $key) {
            if (($this->settings[$key] ?? '') !== ($this->originalSettings[$key] ?? '')) {
                return true;
            }
        }
        return false;
    }

    /**
     * 取得變更的設定
     */
    #[Computed]
    public function changedSettings(): array
    {
        $changed = [];
        foreach ($this->basicSettingKeys as $key) {
            if (($this->settings[$key] ?? '') !== ($this->originalSettings[$key] ?? '')) {
                $changed[$key] = [
                    'old' => $this->originalSettings[$key] ?? '',
                    'new' => $this->settings[$key] ?? '',
                ];
            }
        }
        return $changed;
    }

    /**
     * 儲存設定
     */
    public function save(): void
    {
        $this->saving = true;
        $this->validationErrors = [];

        try {
            // 驗證變更的設定
            $this->validateChangedSettings();

            // 更新設定
            $updateData = [];
            foreach ($this->basicSettingKeys as $key) {
                if (($this->settings[$key] ?? '') !== ($this->originalSettings[$key] ?? '')) {
                    $updateData[$key] = $this->settings[$key];
                }
            }

            if (!empty($updateData)) {
                $result = $this->getSettingsRepository()->updateSettings($updateData);
                
                if ($result) {
                    // 即時應用設定
                    $this->applySettingsImmediately($updateData);
                    
                    // 更新原始值
                    $this->originalSettings = $this->settings;
                    
                    // 清除相關快取
                    $this->clearSettingsCache();
                    
                    // 觸發設定更新事件
                    $this->dispatch('basic-settings-updated', $updateData);
                    
                    $this->addFlash('success', '基本設定已成功更新');
                } else {
                    $this->addFlash('error', '設定更新失敗');
                }
            } else {
                $this->addFlash('info', '沒有設定需要更新');
            }

        } catch (ValidationException $e) {
            $this->validationErrors = $e->validator->errors()->toArray();
            $this->addFlash('error', '設定驗證失敗，請檢查輸入值');
        } catch (\Exception $e) {
            $this->addFlash('error', "設定更新時發生錯誤：{$e->getMessage()}");
        } finally {
            $this->saving = false;
        }
    }

    /**
     * 重設所有設定為預設值
     */
    public function resetAll(): void
    {
        try {
            foreach ($this->basicSettingKeys as $key) {
                $this->getSettingsRepository()->resetSetting($key);
            }
            
            // 重新載入設定
            $this->loadSettings();
            
            // 即時應用設定
            $this->applySettingsImmediately($this->settings);
            
            // 清除快取
            $this->clearSettingsCache();
            
            $this->dispatch('basic-settings-updated', $this->settings);
            $this->addFlash('success', '所有基本設定已重設為預設值');
            
        } catch (\Exception $e) {
            $this->addFlash('error', "重設設定時發生錯誤：{$e->getMessage()}");
        }
    }

    /**
     * 重設單一設定
     */
    public function resetSetting(string $key): void
    {
        if (!in_array($key, $this->basicSettingKeys)) {
            return;
        }

        try {
            $result = $this->getSettingsRepository()->resetSetting($key);
            
            if ($result) {
                // 重新載入該設定
                $setting = $this->getSettingsRepository()->getSetting($key);
                $this->settings[$key] = $setting ? $setting->value : $this->getDefaultValue($key);
                $this->originalSettings[$key] = $this->settings[$key];
                
                // 即時應用設定
                $this->applySettingsImmediately([$key => $this->settings[$key]]);
                
                $this->dispatch('basic-settings-updated', [$key => $this->settings[$key]]);
                $this->addFlash('success', "設定 '{$key}' 已重設為預設值");
            } else {
                $this->addFlash('error', "無法重設設定 '{$key}'");
            }
        } catch (\Exception $e) {
            $this->addFlash('error', "重設設定時發生錯誤：{$e->getMessage()}");
        }
    }

    /**
     * 開啟預覽模式
     */
    public function startPreview(): void
    {
        $this->showPreview = true;
        $this->previewSettings = $this->settings;
        $this->dispatch('basic-settings-preview-start', $this->previewSettings);
    }

    /**
     * 停止預覽模式
     */
    public function stopPreview(): void
    {
        $this->showPreview = false;
        $this->previewSettings = $this->originalSettings;
        $this->dispatch('basic-settings-preview-stop');
    }

    /**
     * 應用預覽設定
     */
    public function applyPreview(): void
    {
        $this->settings = $this->previewSettings;
        $this->save();
        $this->stopPreview();
    }

    /**
     * 測試時區設定
     */
    public function testTimezone(): void
    {
        try {
            $timezone = $this->settings['app.timezone'] ?? 'UTC';
            $now = now()->setTimezone($timezone);
            
            $this->addFlash('info', "時區測試：{$timezone} - 目前時間：{$now->format('Y-m-d H:i:s')}");
        } catch (\Exception $e) {
            $this->addFlash('error', "時區測試失敗：{$e->getMessage()}");
        }
    }

    /**
     * 驗證變更的設定
     */
    protected function validateChangedSettings(): void
    {
        $rules = [];
        $messages = [];
        $dataToValidate = [];
        
        // 只驗證有變更的設定
        foreach ($this->basicSettingKeys as $key) {
            if (($this->settings[$key] ?? '') !== ($this->originalSettings[$key] ?? '')) {
                $config = $this->getConfigService()->getSettingConfig($key);
                if (isset($config['validation'])) {
                    $rules[$key] = $config['validation'];
                    $dataToValidate[$key] = $this->settings[$key] ?? '';
                }
            }
        }

        if (!empty($rules)) {
            $validator = Validator::make($dataToValidate, $rules, $messages);
            
            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
        }
    }

    /**
     * 驗證所有設定
     */
    protected function validateAllSettings(): void
    {
        $rules = [];
        $messages = [];
        
        foreach ($this->basicSettingKeys as $key) {
            $config = $this->getConfigService()->getSettingConfig($key);
            if (isset($config['validation'])) {
                $rules[$key] = $config['validation'];
            }
        }

        if (!empty($rules)) {
            $validator = Validator::make($this->settings, $rules, $messages);
            
            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
        }
    }

    /**
     * 即時應用設定到系統
     */
    protected function applySettingsImmediately(array $settings): void
    {
        foreach ($settings as $key => $value) {
            switch ($key) {
                case 'app.name':
                    Config::set('app.name', $value);
                    break;
                    
                case 'app.timezone':
                    Config::set('app.timezone', $value);
                    date_default_timezone_set($value);
                    break;
                    
                case 'app.locale':
                    Config::set('app.locale', $value);
                    app()->setLocale($value);
                    break;
                    
                case 'app.date_format':
                    // 儲存到快取供其他地方使用
                    Cache::put('app.date_format', $value, 3600);
                    break;
                    
                case 'app.time_format':
                    // 儲存到快取供其他地方使用
                    Cache::put('app.time_format', $value, 3600);
                    break;
            }
        }
    }

    /**
     * 清除設定相關快取
     */
    protected function clearSettingsCache(): void
    {
        $this->getSettingsRepository()->clearCache();
        
        // 清除特定快取
        Cache::forget('app.date_format');
        Cache::forget('app.time_format');
        
        // 清除中介軟體快取
        \App\Http\Middleware\ApplyBasicSettings::clearCache();
        
        // 清除配置快取
        $this->getConfigService()->reloadConfig();
        
        // 清除日期時間輔助類別快取
        \App\Helpers\DateTimeHelper::clearCache();
    }

    /**
     * 監聽設定值變更
     */
    public function updatedSettings($value, $key): void
    {
        // 即時驗證
        $this->validateSingleSetting($key, $value);
        
        // 如果在預覽模式，更新預覽
        if ($this->showPreview) {
            $this->previewSettings[$key] = $value;
            $this->dispatch('basic-settings-preview-update', [$key => $value]);
        }
    }

    /**
     * 驗證單一設定
     */
    protected function validateSingleSetting(string $key, $value): void
    {
        $config = $this->getConfigService()->getSettingConfig($key);
        
        if (isset($config['validation'])) {
            $validator = Validator::make(
                [$key => $value],
                [$key => $config['validation']]
            );
            
            if ($validator->fails()) {
                $this->validationErrors[$key] = $validator->errors()->first($key);
            } else {
                unset($this->validationErrors[$key]);
            }
        }
    }

    /**
     * 取得設定顯示名稱
     */
    public function getSettingDisplayName(string $key): string
    {
        // 優先使用語言檔案中的翻譯
        $translationKey = "settings.settings.{$key}.name";
        if (__($translationKey) !== $translationKey) {
            return __($translationKey);
        }
        
        // 回退到配置檔案
        $config = $this->getConfigService()->getSettingConfig($key);
        return $config['description'] ?? $key;
    }

    /**
     * 取得設定說明
     */
    public function getSettingHelp(string $key): string
    {
        // 優先使用語言檔案中的翻譯
        $translationKey = "settings.settings.{$key}.description";
        if (__($translationKey) !== $translationKey) {
            return __($translationKey);
        }
        
        // 回退到配置檔案
        $config = $this->getConfigService()->getSettingConfig($key);
        return $config['help'] ?? '';
    }

    /**
     * 檢查設定是否為必填
     */
    public function isRequired(string $key): bool
    {
        $config = $this->getConfigService()->getSettingConfig($key);
        $validation = $config['validation'] ?? '';
        
        if (is_string($validation)) {
            return str_contains($validation, 'required');
        }
        
        if (is_array($validation)) {
            return in_array('required', $validation);
        }
        
        return false;
    }

    /**
     * 取得設定驗證錯誤
     */
    public function getValidationError(string $key): ?string
    {
        $error = $this->validationErrors[$key] ?? null;
        
        if (is_array($error)) {
            return $error[0] ?? null;
        }
        
        return $error;
    }

    /**
     * 監聽全域設定更新事件
     */
    #[On('setting-updated')]
    public function handleSettingUpdated(string $settingKey): void
    {
        if (in_array($settingKey, $this->basicSettingKeys)) {
            $this->loadSettings();
        }
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.settings.basic-settings')
            ->layout('components.layouts.admin');
    }
}