<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\AdminComponent;
use App\Repositories\SettingsRepositoryInterface;
use App\Services\ConfigurationService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;

/**
 * 分類設定表單元件
 * 
 * 提供特定分類的所有設定項目編輯介面
 */
class CategorySettingsForm extends AdminComponent
{
    use WithFileUploads;

    /**
     * 設定分類
     */
    public string $category = '';

    /**
     * 設定值陣列
     */
    public array $values = [];

    /**
     * 原始值陣列（用於比較變更）
     */
    public array $originalValues = [];

    /**
     * 驗證錯誤訊息
     */
    public array $validationErrors = [];

    /**
     * 依賴檢查結果
     */
    public array $dependencyWarnings = [];

    /**
     * 正在儲存
     */
    public bool $saving = false;

    /**
     * 啟用自動儲存
     */
    public bool $autoSave = false;

    /**
     * 自動儲存延遲（毫秒）
     */
    public int $autoSaveDelay = 2000;

    /**
     * 上傳的檔案
     */
    public array $uploadedFiles = [];

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
     * 取得分類設定
     */
    #[Computed]
    public function categorySettings(): array
    {
        if (empty($this->category)) {
            return [];
        }

        $allSettings = config('system-settings.settings', []);
        $categorySettings = [];

        foreach ($allSettings as $key => $config) {
            if (($config['category'] ?? '') === $this->category) {
                $categorySettings[$key] = $config;
            }
        }

        // 按 order 排序
        uasort($categorySettings, function ($a, $b) {
            return ($a['order'] ?? 999) <=> ($b['order'] ?? 999);
        });

        return $categorySettings;
    }

    /**
     * 取得分類資訊
     */
    #[Computed]
    public function categoryInfo(): array
    {
        $categories = config('system-settings.categories', []);
        return $categories[$this->category] ?? [
            'name' => $this->category,
            'description' => '',
            'icon' => 'cog'
        ];
    }

    /**
     * 檢查是否有變更
     */
    #[Computed]
    public function hasChanges(): bool
    {
        foreach ($this->values as $key => $value) {
            if (($this->originalValues[$key] ?? null) !== $value) {
                return true;
            }
        }
        return false;
    }

    /**
     * 取得所有驗證規則
     */
    #[Computed]
    public function validationRules(): array
    {
        $rules = [];
        
        foreach ($this->categorySettings as $key => $config) {
            if (isset($config['validation'])) {
                $fieldName = "values.{$key}";
                
                if (is_string($config['validation'])) {
                    $rules[$fieldName] = $config['validation'];
                } elseif (is_array($config['validation'])) {
                    $rules[$fieldName] = $config['validation'];
                }
            }
        }

        return $rules;
    }

    /**
     * 掛載元件
     */
    public function mount(string $category = ''): void
    {
        $this->category = $category;
        $this->loadSettings();
    }

    /**
     * 載入設定資料
     */
    public function loadSettings(): void
    {
        if (empty($this->category)) {
            return;
        }

        $this->values = [];
        $this->originalValues = [];

        foreach ($this->categorySettings as $key => $config) {
            $setting = $this->getSettingsRepository()->getSetting($key);
            $value = $setting ? $setting->value : ($config['default'] ?? null);
            
            $this->values[$key] = $value;
            $this->originalValues[$key] = $value;
        }

        $this->checkAllDependencies();
    }

    /**
     * 儲存所有設定
     */
    public function save(): void
    {
        $this->saving = true;
        $this->resetValidationState();

        try {
            // 驗證所有設定
            if (!$this->validateAllSettings()) {
                return;
            }

            // 檢查依賴關係
            $this->checkAllDependencies();

            // 處理檔案上傳
            $this->handleFileUploads();

            // 批量更新設定
            $updatedCount = 0;
            foreach ($this->values as $key => $value) {
                if (($this->originalValues[$key] ?? null) !== $value) {
                    $result = $this->getSettingsRepository()->updateSetting($key, $value);
                    if ($result) {
                        $this->originalValues[$key] = $value;
                        $updatedCount++;
                    }
                }
            }

            if ($updatedCount > 0) {
                $this->dispatch('settings-updated', category: $this->category, count: $updatedCount);
                $this->addFlash('success', "成功更新 {$updatedCount} 個設定");
                
                // 觸發預覽更新
                $this->dispatch('category-settings-updated', [
                    'category' => $this->category,
                    'settings' => $this->values
                ]);
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
     * 重設表單
     */
    public function resetForm(): void
    {
        $this->loadSettings();
        $this->resetValidationState();
        $this->uploadedFiles = [];
        $this->addFlash('info', '表單已重設');
    }

    /**
     * 重設特定設定為預設值
     */
    public function resetSetting(string $key): void
    {
        if (!isset($this->categorySettings[$key])) {
            return;
        }

        try {
            $result = $this->getSettingsRepository()->resetSetting($key);
            
            if ($result) {
                $setting = $this->getSettingsRepository()->getSetting($key);
                $this->values[$key] = $setting ? $setting->value : ($this->categorySettings[$key]['default'] ?? null);
                $this->originalValues[$key] = $this->values[$key];
                
                $this->dispatch('setting-reset', settingKey: $key);
                $this->addFlash('success', '設定已重設為預設值');
            } else {
                $this->addFlash('error', '設定重設失敗');
            }
        } catch (\Exception $e) {
            $this->addFlash('error', "設定重設時發生錯誤：{$e->getMessage()}");
        }
    }

    /**
     * 自動儲存特定設定
     */
    public function autoSaveSetting(string $key): void
    {
        if (!$this->autoSave || !isset($this->values[$key])) {
            return;
        }

        // 只有在有變更時才自動儲存
        if (($this->originalValues[$key] ?? null) === $this->values[$key]) {
            return;
        }

        try {
            // 驗證單個設定
            $fieldName = "values.{$key}";
            $rules = $this->validationRules();
            
            if (isset($rules[$fieldName])) {
                $validator = Validator::make(
                    [$fieldName => $this->values[$key]],
                    [$fieldName => $rules[$fieldName]]
                );

                if ($validator->fails()) {
                    return; // 驗證失敗時不自動儲存
                }
            }

            // 更新設定
            $result = $this->getSettingsRepository()->updateSetting($key, $this->values[$key]);

            if ($result) {
                $this->originalValues[$key] = $this->values[$key];
                $this->dispatch('setting-auto-saved', settingKey: $key);
            }

        } catch (\Exception $e) {
            // 自動儲存失敗時不顯示錯誤，讓使用者手動儲存
            logger()->warning("Auto-save failed for setting {$key}: " . $e->getMessage());
        }
    }

    /**
     * 測試連線
     */
    public function testConnection(string $type): void
    {
        try {
            // 建立測試配置
            $testConfig = $this->buildConnectionTestConfig($type);
            
            // 執行連線測試
            $result = $this->getConfigService()->testConnection($type, $testConfig);
            
            if ($result) {
                $this->addFlash('success', '連線測試成功');
            } else {
                $this->addFlash('error', '連線測試失敗，請檢查設定');
            }

        } catch (\Exception $e) {
            $this->addFlash('error', "連線測試錯誤：{$e->getMessage()}");
        }
    }

    /**
     * 驗證所有設定
     */
    protected function validateAllSettings(): bool
    {
        $this->resetValidationState();

        $rules = $this->validationRules();
        if (empty($rules)) {
            return true;
        }

        try {
            $validator = Validator::make($this->values, $rules);

            if ($validator->fails()) {
                $this->validationErrors = $validator->errors()->toArray();
                return false;
            }

            return true;
        } catch (\Exception $e) {
            $this->validationErrors = ['general' => [$e->getMessage()]];
            return false;
        }
    }

    /**
     * 檢查所有依賴關係
     */
    protected function checkAllDependencies(): void
    {
        $this->dependencyWarnings = [];

        foreach ($this->categorySettings as $key => $config) {
            if (!isset($config['depends_on'])) {
                continue;
            }

            $warnings = [];
            foreach ($config['depends_on'] as $dependentKey => $expectedValue) {
                $currentValue = $this->values[$dependentKey] ?? null;
                
                if ($currentValue !== $expectedValue) {
                    $dependentConfig = $this->categorySettings[$dependentKey] ?? [];
                    $warnings[] = [
                        'key' => $dependentKey,
                        'name' => $dependentConfig['description'] ?? $dependentKey,
                        'current_value' => $currentValue,
                        'expected_value' => $expectedValue,
                        'message' => "此設定需要 '{$dependentConfig['description']}' 設為 '{$expectedValue}'"
                    ];
                }
            }

            if (!empty($warnings)) {
                $this->dependencyWarnings[$key] = $warnings;
            }
        }
    }

    /**
     * 處理檔案上傳
     */
    protected function handleFileUploads(): void
    {
        foreach ($this->uploadedFiles as $key => $file) {
            if (!$file) {
                continue;
            }

            $config = $this->categorySettings[$key] ?? [];
            
            // 驗證檔案
            $rules = $this->getFileValidationRules($config);
            $validator = Validator::make(
                ['file' => $file],
                ['file' => $rules]
            );

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            // 儲存檔案
            $path = $file->store('settings', 'public');
            $this->values[$key] = asset("storage/{$path}");
        }

        $this->uploadedFiles = [];
    }

    /**
     * 取得檔案驗證規則
     */
    protected function getFileValidationRules(array $config): array
    {
        if (isset($config['validation'])) {
            if (is_string($config['validation'])) {
                return explode('|', $config['validation']);
            }
            return $config['validation'];
        }

        // 預設檔案驗證規則
        $rules = ['file'];
        
        if (($config['type'] ?? '') === 'image') {
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
    protected function buildConnectionTestConfig(string $type): array
    {
        $testableSettings = config('system-settings.testable_settings', []);
        
        if (!isset($testableSettings[$type])) {
            return [];
        }

        $config = [];
        $settingKeys = $testableSettings[$type]['settings'];
        
        foreach ($settingKeys as $key) {
            if (isset($this->values[$key])) {
                $config[str_replace($type . '.', '', $key)] = $this->values[$key];
            }
        }

        return $config;
    }

    /**
     * 重設驗證狀態
     */
    protected function resetValidationState(): void
    {
        $this->validationErrors = [];
    }

    /**
     * 更新值時的處理
     */
    public function updatedValues($value, $key): void
    {
        // 檢查依賴關係
        $this->checkAllDependencies();
        
        // 如果啟用自動儲存，延遲儲存
        if ($this->autoSave) {
            $this->dispatch('auto-save-scheduled', settingKey: $key);
        }
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.settings.category-settings-form');
    }
}