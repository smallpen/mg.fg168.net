<?php

namespace App\Helpers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;

class SystemSettingsHelper
{
    /**
     * 取得所有設定分類
     *
     * @return Collection
     */
    public static function getCategories(): Collection
    {
        $categories = Config::get('system-settings.categories', []);
        
        return collect($categories)->sortBy('order');
    }

    /**
     * 取得指定分類的設定項目
     *
     * @param string $category
     * @return Collection
     */
    public static function getSettingsByCategory(string $category): Collection
    {
        $settings = Config::get('system-settings.settings', []);
        
        return collect($settings)
            ->filter(fn($setting) => $setting['category'] === $category)
            ->sortBy('order');
    }

    /**
     * 取得所有設定項目
     *
     * @return Collection
     */
    public static function getAllSettings(): Collection
    {
        $settings = Config::get('system-settings.settings', []);
        
        return collect($settings);
    }

    /**
     * 取得指定設定的配置
     *
     * @param string $key
     * @return array|null
     */
    public static function getSettingConfig(string $key): ?array
    {
        $settings = Config::get('system-settings.settings', []);
        return $settings[$key] ?? null;
    }

    /**
     * 取得設定的預設值
     *
     * @param string $key
     * @return mixed
     */
    public static function getDefaultValue(string $key): mixed
    {
        $config = self::getSettingConfig($key);
        
        return $config['default'] ?? null;
    }

    /**
     * 取得設定值（從資料庫或預設值）
     * 注意：這個方法需要 Setting 模型存在才能正常工作
     *
     * @param string $key
     * @return mixed
     */
    public static function getSetting(string $key): mixed
    {
        // 如果 Setting 模型存在，從資料庫取得值
        if (class_exists(\App\Models\Setting::class)) {
            try {
                $setting = \App\Models\Setting::where('key', $key)->first();
                if ($setting) {
                    return $setting->value;
                }
            } catch (\Exception $e) {
                // 如果資料庫還沒建立或其他錯誤，回傳預設值
            }
        }
        
        // 回傳預設值
        return self::getDefaultValue($key);
    }

    /**
     * 取得設定的驗證規則
     *
     * @param string $key
     * @return string
     */
    public static function getValidationRules(string $key): string
    {
        $config = self::getSettingConfig($key);
        
        return $config['validation'] ?? '';
    }

    /**
     * 取得設定的類型
     *
     * @param string $key
     * @return string
     */
    public static function getSettingType(string $key): string
    {
        $config = self::getSettingConfig($key);
        
        return $config['type'] ?? 'text';
    }

    /**
     * 取得設定的選項
     *
     * @param string $key
     * @return array
     */
    public static function getSettingOptions(string $key): array
    {
        $config = self::getSettingConfig($key);
        
        return $config['options'] ?? [];
    }

    /**
     * 檢查設定是否需要加密
     *
     * @param string $key
     * @return bool
     */
    public static function isEncrypted(string $key): bool
    {
        $config = self::getSettingConfig($key);
        
        return $config['encrypted'] ?? false;
    }

    /**
     * 檢查設定是否支援預覽
     *
     * @param string $key
     * @return bool
     */
    public static function isPreviewable(string $key): bool
    {
        $config = self::getSettingConfig($key);
        $previewSettings = Config::get('system-settings.preview_settings', []);
        
        return ($config['preview'] ?? false) || in_array($key, $previewSettings);
    }

    /**
     * 取得設定的依賴關係
     *
     * @param string $key
     * @return array
     */
    public static function getDependencies(string $key): array
    {
        $config = self::getSettingConfig($key);
        $globalDependencies = Config::get('system-settings.dependencies', []);
        
        // 合併設定中的依賴和全域依賴
        $settingDependencies = $config['depends_on'] ?? [];
        $keyDependencies = $globalDependencies[$key] ?? [];
        
        // 將字串陣列轉換為關聯陣列格式
        $result = [];
        
        // 處理設定中的依賴（已經是關聯陣列格式）
        foreach ($settingDependencies as $depKey => $expectedValue) {
            $result[$depKey] = $expectedValue;
        }
        
        // 處理全域依賴（字串陣列格式，預設值為 true）
        foreach ($keyDependencies as $depKey) {
            if (!isset($result[$depKey])) {
                $result[$depKey] = true;
            }
        }
        
        return $result;
    }

    /**
     * 取得依賴於指定設定的其他設定
     *
     * @param string $key
     * @return array
     */
    public static function getDependentSettings(string $key): array
    {
        $dependencies = Config::get('system-settings.dependencies', []);
        $dependentSettings = [];
        
        foreach ($dependencies as $settingKey => $deps) {
            if (in_array($key, $deps)) {
                $dependentSettings[] = $settingKey;
            }
        }
        
        // 也檢查設定配置中的 depends_on
        $allSettings = self::getAllSettings();
        foreach ($allSettings as $settingKey => $config) {
            $dependsOn = $config['depends_on'] ?? [];
            if (array_key_exists($key, $dependsOn)) {
                $dependentSettings[] = $settingKey;
            }
        }
        
        return array_unique($dependentSettings);
    }

    /**
     * 驗證設定值
     *
     * @param string $key
     * @param mixed $value
     * @param array $allSettings 所有設定值，用於依賴驗證
     * @return array 驗證結果 ['valid' => bool, 'errors' => array]
     */
    public static function validateSetting(string $key, mixed $value, array $allSettings = []): array
    {
        $config = self::getSettingConfig($key);
        
        if (!$config) {
            return ['valid' => false, 'errors' => ['設定項目不存在']];
        }
        
        $validationRules = $config['validation'] ?? '';
        
        if (empty($validationRules)) {
            return ['valid' => true, 'errors' => []];
        }
        
        $rules = ['value' => $validationRules];
        $data = ['value' => $value];
        
        // 處理依賴驗證
        $dependencies = self::getDependencies($key);
        foreach ($dependencies as $depKey => $depValue) {
            if (isset($allSettings[$depKey])) {
                $data[$depKey] = $allSettings[$depKey];
            }
        }
        
        try {
            $validator = Validator::make($data, $rules);
            
            return [
                'valid' => !$validator->fails(),
                'errors' => $validator->errors()->get('value')
            ];
        } catch (\Exception $e) {
            return ['valid' => false, 'errors' => ['驗證過程發生錯誤: ' . $e->getMessage()]];
        }
    }

    /**
     * 檢查設定依賴是否滿足
     *
     * @param string $key
     * @param array $allSettings
     * @return bool
     */
    public static function areDependenciesSatisfied(string $key, array $allSettings): bool
    {
        $config = self::getSettingConfig($key);
        $dependsOn = $config['depends_on'] ?? [];
        
        foreach ($dependsOn as $depKey => $expectedValue) {
            $actualValue = $allSettings[$depKey] ?? null;
            
            // 處理布林值比較
            if (is_bool($expectedValue)) {
                $actualValue = filter_var($actualValue, FILTER_VALIDATE_BOOLEAN);
            }
            
            if ($actualValue !== $expectedValue) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * 取得輸入元件類型
     *
     * @param string $settingType
     * @return array
     */
    public static function getInputComponent(string $settingType): array
    {
        $inputTypes = Config::get('system-settings.input_types', []);
        
        return $inputTypes[$settingType] ?? $inputTypes['text'];
    }

    /**
     * 取得可測試的設定群組
     *
     * @return array
     */
    public static function getTestableSettings(): array
    {
        return Config::get('system-settings.testable_settings', []);
    }

    /**
     * 檢查設定是否可以測試
     *
     * @param string $key
     * @return string|null 回傳測試群組名稱，如果不可測試則回傳 null
     */
    public static function getTestGroup(string $key): ?string
    {
        $testableSettings = self::getTestableSettings();
        
        foreach ($testableSettings as $groupName => $group) {
            if (in_array($key, $group['settings'])) {
                return $groupName;
            }
        }
        
        return null;
    }

    /**
     * 取得設定的顯示值
     *
     * @param string $key
     * @param mixed $value
     * @return string
     */
    public static function getDisplayValue(string $key, mixed $value): string
    {
        $config = self::getSettingConfig($key);
        $type = $config['type'] ?? 'text';
        
        switch ($type) {
            case 'boolean':
                return $value ? '是' : '否';
                
            case 'select':
                $options = $config['options'] ?? [];
                return $options[$value] ?? $value;
                
            case 'password':
                return $value ? '••••••••' : '';
                
            case 'file':
                return $value ? basename($value) : '未設定';
                
            case 'color':
                return $value ? strtoupper($value) : '';
                
            default:
                return (string) $value;
        }
    }

    /**
     * 取得設定的搜尋關鍵字
     *
     * @param string $key
     * @return array
     */
    public static function getSearchKeywords(string $key): array
    {
        $config = self::getSettingConfig($key);
        
        $keywords = [
            $key,
            $config['description'] ?? '',
            $config['help'] ?? '',
        ];
        
        // 加入選項文字
        if (isset($config['options'])) {
            $keywords = array_merge($keywords, array_values($config['options']));
        }
        
        // 加入分類名稱
        $categoryKey = $config['category'] ?? '';
        $categories = self::getCategories();
        if (isset($categories[$categoryKey])) {
            $keywords[] = $categories[$categoryKey]['name'];
        }
        
        return array_filter($keywords);
    }

    /**
     * 格式化設定值用於儲存
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public static function formatValueForStorage(string $key, mixed $value): mixed
    {
        $config = self::getSettingConfig($key);
        $type = $config['type'] ?? 'text';
        
        switch ($type) {
            case 'boolean':
                if (is_string($value)) {
                    return in_array(strtolower($value), ['true', '1', 'yes', 'on'], true);
                }
                return (bool) $value;
                
            case 'number':
                return is_numeric($value) ? (int) $value : 0;
                
            case 'email':
            case 'text':
            case 'textarea':
            case 'password':
                return (string) $value;
                
            case 'select':
            case 'color':
                return (string) $value;
                
            case 'file':
                return $value; // 檔案路徑
                
            default:
                return $value;
        }
    }

    /**
     * 格式化設定值用於顯示
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public static function formatValueForDisplay(string $key, mixed $value): mixed
    {
        $config = self::getSettingConfig($key);
        $type = $config['type'] ?? 'text';
        
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
                
            case 'number':
                return is_numeric($value) ? (int) $value : 0;
                
            case 'password':
                return ''; // 不顯示密碼值
                
            default:
                return $value;
        }
    }
}