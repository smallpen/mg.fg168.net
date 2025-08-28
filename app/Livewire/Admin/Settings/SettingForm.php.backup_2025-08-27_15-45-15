<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\AdminComponent;
use App\Models\Setting;
use App\Repositories\SettingsRepositoryInterface;
use App\Services\ConfigurationService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;

/**
 * 設定編輯表單元件
 * 
 * 提供不同類型設定的編輯介面，包含即時驗證、依賴檢查和預覽功能
 */
class SettingForm extends AdminComponent
{
    use WithFileUploads;

    /**
     * 設定鍵值
     */
    public string $settingKey = '';

    /**
     * 設定值
     */
    public $value = null;

    /**
     * 原始值（用於比較變更）
     */
    public $originalValue = null;

    /**
     * 設定配置
     */
    public array $settingConfig = [];

    /**
     * 顯示預覽
     */
    public bool $showPreview = false;

    /**
     * 顯示表單
     */
    public bool $showForm = false;

    /**
     * 驗證錯誤訊息
     */
    public array $validationErrors = [];

    /**
     * 依賴檢查結果
     */
    public array $dependencyWarnings = [];

    /**
     * 連線測試結果
     */
    public ?bool $connectionTestResult = null;

    /**
     * 連線測試訊息
     */
    public string $connectionTestMessage = '';

    /**
     * 正在測試連線
     */
    public bool $testingConnection = false;

    /**
     * 正在儲存
     */
    public bool $saving = false;

    /**
     * 上傳的檔案
     */
    public $uploadedFile = null;

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
     * 取得設定模型
     */
    #[Computed]
    public function setting(): ?Setting
    {
        if (empty($this->settingKey)) {
            return null;
        }

        return $this->getSettingsRepository()->getSetting($this->settingKey);
    }

    /**
     * 取得驗證規則
     */
    #[Computed]
    public function validationRules(): array
    {
        if (!$this->setting) {
            return [];
        }

        $config = $this->getConfigService()->getSettingConfig($this->settingKey);
        
        if (isset($config['validation'])) {
            if (is_string($config['validation'])) {
                return explode('|', $config['validation']);
            }
            return $config['validation'];
        }

        return $this->setting->validation_rules ?? [];
    }

    /**
     * 取得輸入類型
     */
    #[Computed]
    public function inputType(): string
    {
        return $this->getConfigService()->getSettingType($this->settingKey);
    }

    /**
     * 取得設定選項
     */
    #[Computed]
    public function options(): array
    {
        return $this->getConfigService()->getSettingOptions($this->settingKey);
    }

    /**
     * 取得輸入元件名稱
     */
    #[Computed]
    public function inputComponent(): string
    {
        return $this->getConfigService()->getInputComponent($this->settingKey);
    }

    /**
     * 檢查是否有變更
     */
    #[Computed]
    public function hasChanges(): bool
    {
        return $this->value !== $this->originalValue;
    }

    /**
     * 檢查是否可以重設
     */
    #[Computed]
    public function canReset(): bool
    {
        if (!$this->setting) {
            return false;
        }

        return $this->setting->value !== $this->setting->default_value;
    }

    /**
     * 檢查是否支援預覽
     */
    #[Computed]
    public function supportsPreview(): bool
    {
        $config = $this->getConfigService()->getSettingConfig($this->settingKey);
        return $config['preview'] ?? false;
    }

    /**
     * 檢查是否支援連線測試
     */
    #[Computed]
    public function supportsConnectionTest(): bool
    {
        $testableSettings = config('system-settings.testable_settings', []);
        
        foreach ($testableSettings as $group => $settings) {
            if (in_array($this->settingKey, $settings['settings'])) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * 開啟設定表單
     */
    #[On('open-setting-form')]
    public function openForm(string $settingKey): void
    {
        $this->settingKey = $settingKey;
        $this->loadSetting();
        $this->showForm = true;
        $this->resetValidationState();
        $this->resetConnectionTest();
    }

    /**
     * 載入設定資料
     */
    public function loadSetting(): void
    {
        if (!$this->setting) {
            $this->addFlash('error', '找不到指定的設定');
            return;
        }

        $this->value = $this->setting->value;
        $this->originalValue = $this->setting->value;
        $this->settingConfig = $this->getConfigService()->getSettingConfig($this->settingKey);
        
        // 檢查依賴關係
        $this->checkDependencies();
    }

    /**
     * 儲存設定
     */
    public function save(): void
    {
        if (!$this->setting) {
            $this->addFlash('error', '找不到指定的設定');
            return;
        }

        $this->saving = true;
        $this->resetValidationState();

        try {
            // 即時驗證
            if (!$this->validateValue()) {
                return;
            }

            // 檢查依賴關係
            $dependencyIssues = $this->checkDependencies();
            if (!empty($dependencyIssues)) {
                $this->dependencyWarnings = $dependencyIssues;
                $this->addFlash('warning', '設定變更可能影響其他相關設定，請檢查依賴關係');
            }

            // 處理檔案上傳
            $valueToSave = $this->value;
            if ($this->uploadedFile && in_array($this->inputType, ['file', 'image'])) {
                $valueToSave = $this->handleFileUpload();
            }

            // 更新設定
            $result = $this->getSettingsRepository()->updateSetting($this->settingKey, $valueToSave);

            if ($result) {
                $this->originalValue = $valueToSave;
                $this->value = $valueToSave;
                $this->uploadedFile = null;
                
                $this->dispatch('setting-updated', settingKey: $this->settingKey);
                $this->addFlash('success', '設定已成功更新');
                
                // 如果支援預覽，觸發預覽更新
                if ($this->supportsPreview) {
                    $this->dispatch('setting-preview-updated', [
                        'key' => $this->settingKey,
                        'value' => $valueToSave
                    ]);
                }
            } else {
                $this->addFlash('error', '設定更新失敗');
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
     * 取消編輯
     */
    public function cancel(): void
    {
        $this->value = $this->originalValue;
        $this->uploadedFile = null;
        $this->showForm = false;
        $this->resetValidationState();
        $this->resetConnectionTest();
    }

    /**
     * 重設為預設值
     */
    public function resetToDefault(): void
    {
        if (!$this->setting) {
            return;
        }

        try {
            $result = $this->getSettingsRepository()->resetSetting($this->settingKey);
            
            if ($result) {
                $this->value = $this->setting->fresh()->value;
                $this->originalValue = $this->value;
                
                $this->dispatch('setting-updated', settingKey: $this->settingKey);
                $this->addFlash('success', '設定已重設為預設值');
            } else {
                $this->addFlash('error', '設定重設失敗');
            }
        } catch (\Exception $e) {
            $this->addFlash('error', "設定重設時發生錯誤：{$e->getMessage()}");
        }
    }

    /**
     * 切換預覽模式
     */
    public function togglePreview(): void
    {
        $this->showPreview = !$this->showPreview;
        
        if ($this->showPreview && $this->supportsPreview) {
            $this->dispatch('setting-preview-start', [
                'key' => $this->settingKey,
                'value' => $this->value
            ]);
        } else {
            $this->dispatch('setting-preview-stop');
        }
    }

    /**
     * 開啟預覽面板
     */
    public function openPreview(): void
    {
        if ($this->supportsPreview) {
            $this->dispatch('setting-preview-start', [
                'key' => $this->settingKey,
                'value' => $this->value
            ]);
        }
    }

    /**
     * 測試連線
     */
    public function testConnection(): void
    {
        if (!$this->supportsConnectionTest) {
            return;
        }

        $this->testingConnection = true;
        $this->resetConnectionTest();

        try {
            // 取得測試配置
            $testConfig = $this->buildConnectionTestConfig();
            
            // 執行連線測試
            $testType = $this->getConnectionTestType();
            $result = $this->getConfigService()->testConnection($testType, $testConfig);
            
            $this->connectionTestResult = $result;
            $this->connectionTestMessage = $result 
                ? '連線測試成功' 
                : '連線測試失敗，請檢查設定';

        } catch (\Exception $e) {
            $this->connectionTestResult = false;
            $this->connectionTestMessage = "連線測試錯誤：{$e->getMessage()}";
        } finally {
            $this->testingConnection = false;
        }
    }

    /**
     * 即時驗證設定值
     */
    public function validateValue(): bool
    {
        $this->resetValidationState();

        if (empty($this->validationRules)) {
            return true;
        }

        try {
            $validator = Validator::make(
                ['value' => $this->value],
                ['value' => $this->validationRules]
            );

            if ($validator->fails()) {
                $this->validationErrors = $validator->errors()->toArray();
                return false;
            }

            return true;
        } catch (\Exception $e) {
            $this->validationErrors = ['value' => [$e->getMessage()]];
            return false;
        }
    }

    /**
     * 檢查設定依賴關係
     */
    public function checkDependencies(): array
    {
        $warnings = [];
        $config = $this->getConfigService()->getSettingConfig($this->settingKey);
        
        if (!isset($config['depends_on'])) {
            return $warnings;
        }

        foreach ($config['depends_on'] as $dependentKey => $expectedValue) {
            $dependentSetting = $this->getSettingsRepository()->getSetting($dependentKey);
            
            if (!$dependentSetting) {
                continue;
            }

            if ($dependentSetting->value !== $expectedValue) {
                $dependentConfig = $this->getConfigService()->getSettingConfig($dependentKey);
                $warnings[] = [
                    'key' => $dependentKey,
                    'name' => $dependentConfig['description'] ?? $dependentKey,
                    'current_value' => $dependentSetting->value,
                    'expected_value' => $expectedValue,
                    'message' => "此設定需要 '{$dependentConfig['description']}' 設為 '{$expectedValue}'"
                ];
            }
        }

        return $warnings;
    }

    /**
     * 更新值（用於即時驗證）
     */
    public function updatedValue(): void
    {
        // 即時驗證
        $this->validateValue();
        
        // 檢查依賴關係
        $this->dependencyWarnings = $this->checkDependencies();
        
        // 如果支援預覽且正在預覽，更新預覽
        if ($this->showPreview && $this->supportsPreview) {
            $this->dispatch('setting-preview-update', [
                'key' => $this->settingKey,
                'value' => $this->value
            ]);
        }
    }

    /**
     * 處理檔案上傳
     */
    protected function handleFileUpload(): string
    {
        if (!$this->uploadedFile) {
            return $this->value;
        }

        // 驗證檔案
        $rules = $this->getFileValidationRules();
        $validator = Validator::make(
            ['file' => $this->uploadedFile],
            ['file' => $rules]
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // 儲存檔案
        $path = $this->uploadedFile->store('settings', 'public');
        
        return asset("storage/{$path}");
    }

    /**
     * 取得檔案驗證規則
     */
    protected function getFileValidationRules(): array
    {
        $config = $this->getConfigService()->getSettingConfig($this->settingKey);
        
        if (isset($config['validation'])) {
            if (is_string($config['validation'])) {
                return explode('|', $config['validation']);
            }
            return $config['validation'];
        }

        // 預設檔案驗證規則
        $rules = ['file'];
        
        if ($this->inputType === 'image') {
            $rules[] = 'image';
            $rules[] = 'mimes:jpeg,png,jpg,gif,svg';
            $rules[] = 'max:2048'; // 2MB
        } else {
            $rules[] = 'max:5120'; // 5MB
        }

        return $rules;
    }

    /**
     * 建立連線測試配置
     */
    protected function buildConnectionTestConfig(): array
    {
        $testableSettings = config('system-settings.testable_settings', []);
        $testType = $this->getConnectionTestType();
        
        if (!isset($testableSettings[$testType])) {
            return [];
        }

        $config = [];
        $settingKeys = $testableSettings[$testType]['settings'];
        
        foreach ($settingKeys as $key) {
            if ($key === $this->settingKey) {
                // 使用當前編輯的值
                $config[str_replace($testType . '.', '', $key)] = $this->value;
            } else {
                // 使用資料庫中的值
                $setting = $this->getSettingsRepository()->getSetting($key);
                if ($setting) {
                    $config[str_replace($testType . '.', '', $key)] = $setting->value;
                }
            }
        }

        return $config;
    }

    /**
     * 取得連線測試類型
     */
    protected function getConnectionTestType(): string
    {
        $testableSettings = config('system-settings.testable_settings', []);
        
        foreach ($testableSettings as $type => $settings) {
            if (in_array($this->settingKey, $settings['settings'])) {
                return $type;
            }
        }
        
        return '';
    }

    /**
     * 重設驗證狀態
     */
    protected function resetValidationState(): void
    {
        $this->validationErrors = [];
        $this->dependencyWarnings = [];
    }

    /**
     * 重設連線測試狀態
     */
    protected function resetConnectionTest(): void
    {
        $this->connectionTestResult = null;
        $this->connectionTestMessage = '';
        $this->testingConnection = false;
    }

    /**
     * 關閉表單
     */
    public function closeForm(): void
    {
        $this->showForm = false;
        $this->cancel();
    }

    /**
     * 取得設定顯示名稱
     */
    public function getSettingDisplayName(): string
    {
        if (!$this->setting) {
            return '';
        }

        return $this->settingConfig['description'] ?? $this->setting->description ?? $this->settingKey;
    }

    /**
     * 取得設定說明文字
     */
    public function getSettingHelp(): string
    {
        return $this->settingConfig['help'] ?? '';
    }

    /**
     * 檢查設定是否為必填
     */
    public function isRequired(): bool
    {
        return in_array('required', $this->validationRules);
    }

    /**
     * 檢查設定是否為敏感資料
     */
    public function isSensitive(): bool
    {
        return $this->setting && $this->setting->is_encrypted;
    }

    /**
     * 自動儲存設定
     */
    public function autoSave(string $settingKey): void
    {
        if ($settingKey !== $this->settingKey) {
            return;
        }

        // 只有在有變更時才自動儲存
        if (!$this->hasChanges) {
            return;
        }

        try {
            // 即時驗證
            if (!$this->validateValue()) {
                return;
            }

            // 更新設定
            $result = $this->getSettingsRepository()->updateSetting($this->settingKey, $this->value);

            if ($result) {
                $this->originalValue = $this->value;
                $this->dispatch('setting-updated', settingKey: $this->settingKey);
                
                // 如果支援預覽，觸發預覽更新
                if ($this->supportsPreview) {
                    $this->dispatch('setting-preview-updated', [
                        'key' => $this->settingKey,
                        'value' => $this->value
                    ]);
                }
            }

        } catch (\Exception $e) {
            // 自動儲存失敗時不顯示錯誤，讓使用者手動儲存
            logger()->warning("Auto-save failed for setting {$this->settingKey}: " . $e->getMessage());
        }
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.settings.setting-form');
    }
}