<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Services\InputValidationService;

/**
 * 安全輸入驗證規則
 * 
 * 檢查輸入是否包含惡意內容
 */
class SecureInput implements ValidationRule
{
    protected InputValidationService $validationService;

    public function __construct()
    {
        $this->validationService = app(InputValidationService::class);
    }

    /**
     * 執行驗證規則
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            return;
        }

        // 檢查是否包含惡意內容
        if ($this->validationService->containsMaliciousContent($value)) {
            $fail('輸入內容包含不安全的字元或腳本');
        }

        // 檢查長度限制
        if (strlen($value) > 1000) {
            $fail('輸入內容過長');
        }

        // 檢查是否包含 SQL 注入模式
        if ($this->containsSqlInjectionPatterns($value)) {
            $fail('輸入內容包含不安全的 SQL 模式');
        }
    }

    /**
     * 檢查是否包含 SQL 注入模式
     */
    protected function containsSqlInjectionPatterns(string $input): bool
    {
        $sqlPatterns = [
            '/(\bUNION\b.*\bSELECT\b)/i',
            '/(\bSELECT\b.*\bFROM\b)/i',
            '/(\bINSERT\b.*\bINTO\b)/i',
            '/(\bUPDATE\b.*\bSET\b)/i',
            '/(\bDELETE\b.*\bFROM\b)/i',
            '/(\bDROP\b.*\bTABLE\b)/i',
            '/(\bALTER\b.*\bTABLE\b)/i',
            '/(\bEXEC\b|\bEXECUTE\b)/i',
            '/(\'.*\bOR\b.*\')/i',
            '/(\'.*\bAND\b.*\')/i',
            '/(\-\-)/i',
            '/(\/\*.*\*\/)/i',
        ];

        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }
}