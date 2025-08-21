<?php

namespace App\Rules;

use App\Helpers\SystemSettingsHelper;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SettingTypeRule implements ValidationRule
{
    protected string $settingKey;

    public function __construct(string $settingKey)
    {
        $this->settingKey = $settingKey;
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
        $config = SystemSettingsHelper::getSettingConfig($this->settingKey);
        
        if (!$config) {
            $fail('設定項目不存在');
            return;
        }

        $type = $config['type'] ?? 'text';
        
        switch ($type) {
            case 'boolean':
                if (!is_bool($value) && !in_array($value, ['0', '1', 'true', 'false', 0, 1, true, false], true)) {
                    $fail('此設定必須是布林值（是/否）');
                }
                break;

            case 'number':
                if (!is_numeric($value)) {
                    $fail('此設定必須是數字');
                    return;
                }
                
                $options = $config['options'] ?? [];
                if (isset($options['min']) && $value < $options['min']) {
                    $fail("此設定的最小值為 {$options['min']}");
                }
                if (isset($options['max']) && $value > $options['max']) {
                    $fail("此設定的最大值為 {$options['max']}");
                }
                break;

            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $fail('此設定必須是有效的電子郵件地址');
                }
                break;

            case 'color':
                if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
                    $fail('此設定必須是有效的顏色代碼（例如：#FF0000）');
                }
                break;

            case 'select':
                $options = $config['options'] ?? [];
                if (!empty($options) && !array_key_exists($value, $options)) {
                    $fail('此設定的值不在允許的選項中');
                }
                break;

            case 'file':
                // 檔案驗證會在檔案上傳時處理
                break;

            case 'text':
            case 'textarea':
            case 'password':
            default:
                if (!is_string($value) && !is_null($value)) {
                    $fail('此設定必須是文字');
                }
                break;
        }
    }
}