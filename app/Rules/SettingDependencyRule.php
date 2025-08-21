<?php

namespace App\Rules;

use App\Helpers\SystemSettingsHelper;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SettingDependencyRule implements ValidationRule
{
    protected string $settingKey;
    protected array $allSettings;

    public function __construct(string $settingKey, array $allSettings = [])
    {
        $this->settingKey = $settingKey;
        $this->allSettings = $allSettings;
    }

    /**
     * 執行驗證規則
     *
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // 檢查設定依賴是否滿足
        if (!SystemSettingsHelper::areDependenciesSatisfied($this->settingKey, $this->allSettings)) {
            $dependencies = SystemSettingsHelper::getDependencies($this->settingKey);
            $dependencyNames = [];
            
            foreach ($dependencies as $depKey => $expectedValue) {
                $config = SystemSettingsHelper::getSettingConfig($depKey);
                $dependencyNames[] = $config['description'] ?? $depKey;
            }
            
            $fail("此設定需要先啟用以下相關設定：" . implode('、', $dependencyNames));
        }

        // 執行設定特定的驗證
        $validation = SystemSettingsHelper::validateSetting($this->settingKey, $value, $this->allSettings);
        
        if (!$validation['valid']) {
            foreach ($validation['errors'] as $error) {
                $fail($error);
            }
        }
    }
}