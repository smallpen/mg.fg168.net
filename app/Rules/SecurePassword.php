<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Repositories\SettingsRepositoryInterface;

/**
 * 安全密碼驗證規則
 * 
 * 根據系統安全設定驗證密碼強度
 */
class SecurePassword implements ValidationRule
{
    /**
     * 設定資料庫
     */
    protected SettingsRepositoryInterface $settingsRepository;

    /**
     * 建構子
     */
    public function __construct()
    {
        $this->settingsRepository = app(SettingsRepositoryInterface::class);
    }

    /**
     * 執行驗證規則
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('密碼必須是字串');
            return;
        }

        // 載入安全設定
        $settings = $this->loadSecuritySettings();
        
        // 檢查最小長度
        if (strlen($value) < $settings['min_length']) {
            $fail("密碼長度至少需要 {$settings['min_length']} 個字元");
            return;
        }

        // 檢查大寫字母
        if ($settings['require_uppercase'] && !preg_match('/[A-Z]/', $value)) {
            $fail('密碼必須包含至少一個大寫字母');
            return;
        }

        // 檢查小寫字母
        if ($settings['require_lowercase'] && !preg_match('/[a-z]/', $value)) {
            $fail('密碼必須包含至少一個小寫字母');
            return;
        }

        // 檢查數字
        if ($settings['require_numbers'] && !preg_match('/[0-9]/', $value)) {
            $fail('密碼必須包含至少一個數字');
            return;
        }

        // 檢查特殊字元
        if ($settings['require_symbols'] && !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $value)) {
            $fail('密碼必須包含至少一個特殊字元 (!@#$%^&*(),.?":{}|<>)');
            return;
        }
    }

    /**
     * 載入安全設定
     */
    protected function loadSecuritySettings(): array
    {
        $defaultSettings = [
            'min_length' => 8,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_symbols' => false,
        ];

        try {
            $settings = $this->settingsRepository->getSettings([
                'security.password_min_length',
                'security.password_require_uppercase',
                'security.password_require_lowercase',
                'security.password_require_numbers',
                'security.password_require_symbols',
            ]);

            return [
                'min_length' => (int) ($settings->get('security.password_min_length')?->value ?? $defaultSettings['min_length']),
                'require_uppercase' => (bool) ($settings->get('security.password_require_uppercase')?->value ?? $defaultSettings['require_uppercase']),
                'require_lowercase' => (bool) ($settings->get('security.password_require_lowercase')?->value ?? $defaultSettings['require_lowercase']),
                'require_numbers' => (bool) ($settings->get('security.password_require_numbers')?->value ?? $defaultSettings['require_numbers']),
                'require_symbols' => (bool) ($settings->get('security.password_require_symbols')?->value ?? $defaultSettings['require_symbols']),
            ];
        } catch (\Exception $e) {
            // 如果無法載入設定，使用預設值
            \Log::warning('Failed to load security settings for password validation', [
                'error' => $e->getMessage()
            ]);
            
            return $defaultSettings;
        }
    }

    /**
     * 取得密碼強度描述
     */
    public static function getPasswordRequirements(): array
    {
        $rule = new self();
        $settings = $rule->loadSecuritySettings();
        
        $requirements = [];
        $requirements[] = "至少 {$settings['min_length']} 個字元";
        
        if ($settings['require_uppercase']) {
            $requirements[] = '至少一個大寫字母';
        }
        
        if ($settings['require_lowercase']) {
            $requirements[] = '至少一個小寫字母';
        }
        
        if ($settings['require_numbers']) {
            $requirements[] = '至少一個數字';
        }
        
        if ($settings['require_symbols']) {
            $requirements[] = '至少一個特殊字元';
        }
        
        return $requirements;
    }
}