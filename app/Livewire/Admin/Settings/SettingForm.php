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

        return Setting::where('key', $this->settingKey)->first();
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

        // 簡化驗證規則
        return ['required'];
    }

    /**
     * 取得輸入類型
     */
    #[Computed]
    public function inputType(): string
    {
        if (!$this->setting) {
            return 'text';
        }
        
        return $this->setting->type ?? 'text';
    }

    /**
     * 取得設定選項
     */
    #[Computed]
    public function options(): array
    {
        if (!$this->setting) {
            return [];
        }
        
        try {
            // 處理 select 類型的選項
            if ($this->setting->type === 'select' && $this->setting->options) {
                $options = $this->setting->options;
                
                // 如果 options 是陣列且包含 values 鍵
                if (is_array($options) && isset($options['values'])) {
                    return $options['values'];
                }
                
                // 如果 options 直接是選項陣列
                if (is_array($options)) {
                    return $options;
                }
            }
            
            return [];
        } catch (\Exception $e) {
            \Log::error('取得設定選項失敗', [
                'settingKey' => $this->settingKey,
                'error' => $e->getMessage()
            ]);
            return [];
        }
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
        try {
            $this->settingKey = $settingKey;
            $this->showForm = true;
            $this->loadSetting();
            $this->resetValidationState();
            $this->resetConnectionTest();
        } catch (\Exception $e) {
            \Log::error('開啟設定表單失敗', [
                'settingKey' => $settingKey,
                'error' => $e->getMessage()
            ]);
            
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '開啟設定表單失敗：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 載入設定資料
     */
    public function loadSetting(): void
    {
        try {
            if (!$this->setting) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => '找不到指定的設定'
                ]);
                return;
            }

            // 處理可能是陣列的值
            $rawValue = $this->setting->value;
            if (is_array($rawValue)) {
                // 如果是陣列，轉換為 JSON 字串用於編輯
                $this->value = json_encode($rawValue, JSON_UNESCAPED_UNICODE);
            } else {
                $this->value = $rawValue ?? '';
            }
            
            $this->originalValue = $this->value;
            $this->settingConfig = [];
            
            // 簡化依賴關係檢查
            $this->dependencyWarnings = [];
            
        } catch (\Exception $e) {
            \Log::error('載入設定資料失敗', [
                'settingKey' => $this->settingKey,
                'error' => $e->getMessage()
            ]);
            
            $this->value = '';
            $this->originalValue = '';
            $this->settingConfig = [];
            $this->dependencyWarnings = [];
        }
    }

    /**
     * 儲存設定
     */
    public function save()
    {
        $this->saving = true;
        
        try {
            // 記錄儲存操作開始
            \Log::info('💾 設定儲存開始', [
                'setting_key' => $this->settingKey,
                'old_value' => $this->originalValue,
                'new_value' => $this->value,
                'user' => auth()->user()->username ?? 'unknown',
                'timestamp' => now()->toISOString()
            ]);

            // 驗證設定是否存在
            $setting = Setting::where('key', $this->settingKey)->first();
            if (!$setting) {
                throw new \Exception("找不到設定項目：{$this->settingKey}");
            }

            // 取得設定的顯示名稱
            $displayName = $setting->description ?? $this->settingKey;
            
            // 檢查值是否有變更
            if ($this->value === $this->originalValue) {
                $this->dispatch('show-toast', [
                    'type' => 'info',
                    'message' => "「{$displayName}」沒有變更，無需儲存"
                ]);
                $this->showForm = false;
                return;
            }

            // 執行資料庫更新
            $affected = \DB::table('settings')
                ->where('key', $this->settingKey)
                ->update([
                    'value' => json_encode($this->value),
                    'is_changed' => true,
                    'updated_at' => now()
                ]);

            if ($affected > 0) {
                // 更新成功
                $this->originalValue = $this->value;
                
                // 發送設定更新事件
                $this->dispatch('setting-updated', settingKey: $this->settingKey);
                
                // 記錄成功日誌
                \Log::info('✅ 設定儲存成功', [
                    'setting_key' => $this->settingKey,
                    'display_name' => $displayName,
                    'new_value' => $this->value,
                    'affected_rows' => $affected
                ]);

                // 顯示成功訊息
                $this->dispatch('show-toast', [
                    'type' => 'success',
                    'message' => "✅ 「{$displayName}」設定已成功更新！"
                ]);
                
                $this->showForm = false;
            } else {
                // 沒有資料被更新
                throw new \Exception('資料庫更新失敗，沒有資料被修改');
            }

        } catch (\Exception $e) {
            // 記錄錯誤日誌
            \Log::error('❌ 設定儲存失敗', [
                'setting_key' => $this->settingKey,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user' => auth()->user()->username ?? 'unknown'
            ]);

            // 取得友好的錯誤訊息
            $friendlyMessage = $this->getFriendlyErrorMessage($e);
            
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => "❌ 儲存失敗：{$friendlyMessage}"
            ]);
        } finally {
            $this->saving = false;
        }
    }

    /**
     * 取得友好的錯誤訊息
     */
    private function getFriendlyErrorMessage(\Exception $e): string
    {
        $message = $e->getMessage();
        
        // 常見錯誤的友好訊息
        if (str_contains($message, 'Duplicate entry')) {
            return '設定值重複，請使用不同的值';
        }
        
        if (str_contains($message, 'Data too long')) {
            return '設定值太長，請縮短內容';
        }
        
        if (str_contains($message, 'Connection refused')) {
            return '資料庫連線失敗，請稍後再試';
        }
        
        if (str_contains($message, 'Syntax error')) {
            return '設定格式錯誤，請檢查輸入內容';
        }
        
        if (str_contains($message, 'Access denied')) {
            return '權限不足，無法修改此設定';
        }
        
        // 如果是自定義錯誤訊息，直接返回
        if (str_contains($message, '找不到設定項目')) {
            return $message;
        }
        
        // 預設錯誤訊息
        return '系統錯誤，請聯絡管理員或稍後再試';
    }

    /**
     * 取消編輯
     */
    public function cancel(): void
    {
        // 檢查是否有未儲存的變更
        $hasUnsavedChanges = $this->value !== $this->originalValue;
        
        if ($hasUnsavedChanges) {
            // 取得設定顯示名稱
            $displayName = $this->setting ? ($this->setting->description ?? $this->settingKey) : $this->settingKey;
            
            // 記錄取消操作
            \Log::info('🚫 使用者取消設定編輯', [
                'setting_key' => $this->settingKey,
                'display_name' => $displayName,
                'had_changes' => true,
                'original_value' => $this->originalValue,
                'cancelled_value' => $this->value,
                'user' => auth()->user()->username ?? 'unknown'
            ]);
            
            // 顯示取消訊息
            $this->dispatch('show-toast', [
                'type' => 'info',
                'message' => "📝 已取消「{$displayName}」的變更"
            ]);
        }
        
        // 重置所有狀態
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
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '❌ 找不到設定項目，無法重設'
            ]);
            return;
        }

        try {
            $displayName = $this->setting->description ?? $this->settingKey;
            $currentValue = $this->value;
            $defaultValue = $this->setting->default_value;
            
            // 檢查是否已經是預設值
            if ($currentValue === $defaultValue) {
                $this->dispatch('show-toast', [
                    'type' => 'info',
                    'message' => "ℹ️ 「{$displayName}」已經是預設值，無需重設"
                ]);
                return;
            }
            
            // 記錄重設操作
            \Log::info('🔄 設定重設為預設值', [
                'setting_key' => $this->settingKey,
                'display_name' => $displayName,
                'current_value' => $currentValue,
                'default_value' => $defaultValue,
                'user' => auth()->user()->username ?? 'unknown'
            ]);
            
            $result = $this->getSettingsRepository()->resetSetting($this->settingKey);
            
            if ($result) {
                // 更新本地值
                $this->value = $this->setting->fresh()->value;
                $this->originalValue = $this->value;
                
                // 發送更新事件
                $this->dispatch('setting-updated', settingKey: $this->settingKey);
                
                // 記錄成功日誌
                \Log::info('✅ 設定重設成功', [
                    'setting_key' => $this->settingKey,
                    'display_name' => $displayName,
                    'reset_to_value' => $this->value
                ]);
                
                // 顯示成功訊息
                $this->dispatch('show-toast', [
                    'type' => 'success',
                    'message' => "🔄 「{$displayName}」已重設為預設值"
                ]);
            } else {
                throw new \Exception('重設操作失敗，請稍後再試');
            }
        } catch (\Exception $e) {
            // 記錄錯誤日誌
            \Log::error('❌ 設定重設失敗', [
                'setting_key' => $this->settingKey,
                'error' => $e->getMessage(),
                'user' => auth()->user()->username ?? 'unknown'
            ]);
            
            $displayName = $this->setting ? ($this->setting->description ?? $this->settingKey) : $this->settingKey;
            
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => "❌ 重設「{$displayName}」失敗：{$e->getMessage()}"
            ]);
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
            $this->dispatch('show-toast', [
                'type' => 'warning',
                'message' => '⚠️ 此設定不支援連線測試'
            ]);
            return;
        }

        $this->testingConnection = true;
        $this->resetConnectionTest();
        
        $displayName = $this->setting ? ($this->setting->description ?? $this->settingKey) : $this->settingKey;

        try {
            // 記錄測試開始
            \Log::info('🔗 開始連線測試', [
                'setting_key' => $this->settingKey,
                'display_name' => $displayName,
                'test_value' => $this->value,
                'user' => auth()->user()->username ?? 'unknown'
            ]);
            
            // 顯示測試中訊息
            $this->dispatch('show-toast', [
                'type' => 'info',
                'message' => "🔗 正在測試「{$displayName}」的連線..."
            ]);

            // 取得測試配置
            $testConfig = $this->buildConnectionTestConfig();
            
            // 執行連線測試
            $testType = $this->getConnectionTestType();
            $result = $this->getConfigService()->testConnection($testType, $testConfig);
            
            $this->connectionTestResult = $result;
            
            if ($result) {
                $this->connectionTestMessage = '✅ 連線測試成功！設定正確可用';
                
                // 記錄成功日誌
                \Log::info('✅ 連線測試成功', [
                    'setting_key' => $this->settingKey,
                    'display_name' => $displayName,
                    'test_type' => $testType
                ]);
                
                // 顯示成功訊息
                $this->dispatch('show-toast', [
                    'type' => 'success',
                    'message' => "✅ 「{$displayName}」連線測試成功！"
                ]);
            } else {
                $this->connectionTestMessage = '❌ 連線測試失敗，請檢查設定值是否正確';
                
                // 記錄失敗日誌
                \Log::warning('❌ 連線測試失敗', [
                    'setting_key' => $this->settingKey,
                    'display_name' => $displayName,
                    'test_type' => $testType,
                    'test_config' => $testConfig
                ]);
                
                // 顯示失敗訊息
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => "❌ 「{$displayName}」連線測試失敗，請檢查設定"
                ]);
            }

        } catch (\Exception $e) {
            $this->connectionTestResult = false;
            $this->connectionTestMessage = "❌ 連線測試錯誤：{$e->getMessage()}";
            
            // 記錄錯誤日誌
            \Log::error('❌ 連線測試錯誤', [
                'setting_key' => $this->settingKey,
                'display_name' => $displayName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // 顯示錯誤訊息
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => "❌ 「{$displayName}」連線測試發生錯誤：{$e->getMessage()}"
            ]);
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

        return $this->setting->description ?? $this->settingKey;
    }

    /**
     * 取得設定說明文字
     */
    public function getSettingHelp(): string
    {
        try {
            // 嘗試從 options 中取得 help
            if ($this->setting && $this->setting->options) {
                $options = $this->setting->options;
                if (is_array($options) && isset($options['help'])) {
                    return $options['help'];
                }
            }
            
            // 回退到 setting 的 help 屬性
            return $this->setting->help ?? '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * 檢查設定是否為必填
     */
    public function isRequired(): bool
    {
        try {
            return in_array('required', $this->validationRules);
        } catch (\Exception $e) {
            return false;
        }
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