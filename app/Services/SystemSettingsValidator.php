<?php

namespace App\Services;

use App\Helpers\SystemSettingsHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

class SystemSettingsValidator
{
    protected array $errors = [];
    protected array $warnings = [];

    /**
     * 驗證系統設定配置
     *
     * @return array
     */
    public function validateConfiguration(): array
    {
        $this->errors = [];
        $this->warnings = [];

        $this->validateCategories();
        $this->validateSettings();
        $this->validateDependencies();
        $this->validateInputTypes();
        $this->validateTestableSettings();

        return [
            'valid' => empty($this->errors),
            'errors' => $this->errors,
            'warnings' => $this->warnings,
        ];
    }

    /**
     * 驗證分類配置
     *
     * @return void
     */
    protected function validateCategories(): void
    {
        $categories = Config::get('system-settings.categories', []);

        if (empty($categories)) {
            $this->errors[] = '未定義任何設定分類';
            return;
        }

        foreach ($categories as $key => $category) {
            if (!isset($category['name'])) {
                $this->errors[] = "分類 '{$key}' 缺少名稱";
            }

            if (!isset($category['icon'])) {
                $this->warnings[] = "分類 '{$key}' 缺少圖示";
            }

            if (!isset($category['order'])) {
                $this->warnings[] = "分類 '{$key}' 缺少排序";
            }
        }
    }

    /**
     * 驗證設定配置
     *
     * @return void
     */
    protected function validateSettings(): void
    {
        $settings = Config::get('system-settings.settings', []);
        $categories = Config::get('system-settings.categories', []);

        if (empty($settings)) {
            $this->errors[] = '未定義任何設定項目';
            return;
        }

        foreach ($settings as $key => $setting) {
            $this->validateSingleSetting($key, $setting, $categories);
        }
    }

    /**
     * 驗證單一設定配置
     *
     * @param string $key
     * @param array $setting
     * @param array $categories
     * @return void
     */
    protected function validateSingleSetting(string $key, array $setting, array $categories): void
    {
        // 檢查必要欄位
        $requiredFields = ['category', 'type', 'default', 'description'];
        foreach ($requiredFields as $field) {
            if (!isset($setting[$field])) {
                $this->errors[] = "設定 '{$key}' 缺少必要欄位 '{$field}'";
            }
        }

        // 檢查分類是否存在
        if (isset($setting['category']) && !isset($categories[$setting['category']])) {
            $this->errors[] = "設定 '{$key}' 的分類 '{$setting['category']}' 不存在";
        }

        // 檢查設定類型
        $validTypes = ['text', 'textarea', 'number', 'email', 'password', 'boolean', 'select', 'color', 'file'];
        if (isset($setting['type']) && !in_array($setting['type'], $validTypes)) {
            $this->errors[] = "設定 '{$key}' 的類型 '{$setting['type']}' 無效";
        }

        // 檢查 select 類型是否有選項
        if (isset($setting['type']) && $setting['type'] === 'select' && empty($setting['options'])) {
            $this->errors[] = "設定 '{$key}' 是 select 類型但缺少選項";
        }

        // 檢查預設值是否在選項中
        if (isset($setting['type'], $setting['options'], $setting['default']) && 
            $setting['type'] === 'select' && 
            !array_key_exists($setting['default'], $setting['options'])) {
            $this->warnings[] = "設定 '{$key}' 的預設值不在選項中";
        }

        // 檢查數字類型的範圍
        if (isset($setting['type']) && $setting['type'] === 'number') {
            if (isset($setting['options']['min'], $setting['default']) && 
                $setting['default'] < $setting['options']['min']) {
                $this->warnings[] = "設定 '{$key}' 的預設值小於最小值";
            }
            if (isset($setting['options']['max'], $setting['default']) && 
                $setting['default'] > $setting['options']['max']) {
                $this->warnings[] = "設定 '{$key}' 的預設值大於最大值";
            }
        }

        // 檢查顏色格式
        if (isset($setting['type'], $setting['default']) && 
            $setting['type'] === 'color' && 
            !preg_match('/^#[0-9A-Fa-f]{6}$/', $setting['default'])) {
            $this->errors[] = "設定 '{$key}' 的預設顏色格式無效";
        }

        // 檢查電子郵件格式
        if (isset($setting['type'], $setting['default']) && 
            $setting['type'] === 'email' && 
            !empty($setting['default']) &&
            !filter_var($setting['default'], FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = "設定 '{$key}' 的預設電子郵件格式無效";
        }
    }

    /**
     * 驗證依賴關係
     *
     * @return void
     */
    protected function validateDependencies(): void
    {
        $settings = Config::get('system-settings.settings', []);
        $dependencies = Config::get('system-settings.dependencies', []);

        // 檢查全域依賴
        foreach ($dependencies as $settingKey => $deps) {
            if (!isset($settings[$settingKey])) {
                $this->errors[] = "依賴配置中的設定 '{$settingKey}' 不存在";
                continue;
            }

            foreach ($deps as $depKey) {
                if (!isset($settings[$depKey])) {
                    $this->errors[] = "設定 '{$settingKey}' 依賴的設定 '{$depKey}' 不存在";
                }
            }
        }

        // 檢查設定中的依賴
        foreach ($settings as $key => $setting) {
            if (isset($setting['depends_on'])) {
                foreach ($setting['depends_on'] as $depKey => $expectedValue) {
                    if (!isset($settings[$depKey])) {
                        $this->errors[] = "設定 '{$key}' 依賴的設定 '{$depKey}' 不存在";
                    }
                }
            }
        }

        // 檢查循環依賴
        $this->checkCircularDependencies($settings);
    }

    /**
     * 檢查循環依賴
     *
     * @param array $settings
     * @return void
     */
    protected function checkCircularDependencies(array $settings): void
    {
        foreach ($settings as $key => $setting) {
            $visited = [];
            if ($this->hasCircularDependency($key, $settings, $visited)) {
                $this->errors[] = "設定 '{$key}' 存在循環依賴";
            }
        }
    }

    /**
     * 遞迴檢查循環依賴
     *
     * @param string $key
     * @param array $settings
     * @param array $visited
     * @return bool
     */
    protected function hasCircularDependency(string $key, array $settings, array &$visited): bool
    {
        if (in_array($key, $visited)) {
            return true;
        }

        $visited[] = $key;
        $dependencies = SystemSettingsHelper::getDependencies($key);

        foreach ($dependencies as $depKey => $value) {
            if ($this->hasCircularDependency($depKey, $settings, $visited)) {
                return true;
            }
        }

        array_pop($visited);
        return false;
    }

    /**
     * 驗證輸入類型配置
     *
     * @return void
     */
    protected function validateInputTypes(): void
    {
        $inputTypes = Config::get('system-settings.input_types', []);
        $settings = Config::get('system-settings.settings', []);

        // 檢查所有使用的類型是否有對應的輸入元件
        $usedTypes = collect($settings)->pluck('type')->unique();
        
        foreach ($usedTypes as $type) {
            if (!isset($inputTypes[$type])) {
                $this->warnings[] = "設定類型 '{$type}' 沒有對應的輸入元件配置";
            }
        }

        // 檢查輸入類型配置的完整性
        foreach ($inputTypes as $type => $config) {
            if (!isset($config['component'])) {
                $this->errors[] = "輸入類型 '{$type}' 缺少元件配置";
            }
        }
    }

    /**
     * 驗證可測試設定配置
     *
     * @return void
     */
    protected function validateTestableSettings(): void
    {
        $testableSettings = Config::get('system-settings.testable_settings', []);
        $settings = Config::get('system-settings.settings', []);

        foreach ($testableSettings as $groupName => $group) {
            if (!isset($group['settings']) || !is_array($group['settings'])) {
                $this->errors[] = "測試群組 '{$groupName}' 缺少設定列表";
                continue;
            }

            if (!isset($group['test_method'])) {
                $this->errors[] = "測試群組 '{$groupName}' 缺少測試方法";
            }

            foreach ($group['settings'] as $settingKey) {
                if (!isset($settings[$settingKey])) {
                    $this->errors[] = "測試群組 '{$groupName}' 中的設定 '{$settingKey}' 不存在";
                }
            }
        }
    }

    /**
     * 取得所有錯誤
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * 取得所有警告
     *
     * @return array
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * 檢查配置是否有效
     *
     * @return bool
     */
    public function isValid(): bool
    {
        $result = $this->validateConfiguration();
        return $result['valid'];
    }
}