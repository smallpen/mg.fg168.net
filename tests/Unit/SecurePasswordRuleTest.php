<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Rules\SecurePassword;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * 安全密碼規則測試
 */
class SecurePasswordRuleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 設定測試環境
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立預設安全設定
        $this->createDefaultSecuritySettings();
    }

    /**
     * 建立預設安全設定
     */
    protected function createDefaultSecuritySettings(): void
    {
        $settings = [
            'security.password_min_length' => 8,
            'security.password_require_uppercase' => true,
            'security.password_require_lowercase' => true,
            'security.password_require_numbers' => true,
            'security.password_require_symbols' => false,
        ];

        foreach ($settings as $key => $value) {
            Setting::create([
                'key' => $key,
                'value' => $value,
                'category' => 'security',
                'type' => is_bool($value) ? 'boolean' : 'number',
                'is_system' => true,
            ]);
        }
    }

    /**
     * 測試有效密碼通過驗證
     */
    public function test_valid_password_passes(): void
    {
        $rule = new SecurePassword();
        $failed = false;
        
        $rule->validate('password', 'Password123', function($message) use (&$failed) {
            $failed = true;
        });
        
        $this->assertFalse($failed);
    }

    /**
     * 測試太短的密碼失敗
     */
    public function test_short_password_fails(): void
    {
        $rule = new SecurePassword();
        $failed = false;
        $errorMessage = '';
        
        $rule->validate('password', 'Pass1', function($message) use (&$failed, &$errorMessage) {
            $failed = true;
            $errorMessage = $message;
        });
        
        $this->assertTrue($failed);
        $this->assertStringContainsString('密碼長度至少需要 8 個字元', $errorMessage);
    }

    /**
     * 測試缺少大寫字母的密碼失敗
     */
    public function test_password_without_uppercase_fails(): void
    {
        $rule = new SecurePassword();
        $failed = false;
        $errorMessage = '';
        
        $rule->validate('password', 'password123', function($message) use (&$failed, &$errorMessage) {
            $failed = true;
            $errorMessage = $message;
        });
        
        $this->assertTrue($failed);
        $this->assertStringContainsString('密碼必須包含至少一個大寫字母', $errorMessage);
    }

    /**
     * 測試缺少小寫字母的密碼失敗
     */
    public function test_password_without_lowercase_fails(): void
    {
        $rule = new SecurePassword();
        $failed = false;
        $errorMessage = '';
        
        $rule->validate('password', 'PASSWORD123', function($message) use (&$failed, &$errorMessage) {
            $failed = true;
            $errorMessage = $message;
        });
        
        $this->assertTrue($failed);
        $this->assertStringContainsString('密碼必須包含至少一個小寫字母', $errorMessage);
    }

    /**
     * 測試缺少數字的密碼失敗
     */
    public function test_password_without_numbers_fails(): void
    {
        $rule = new SecurePassword();
        $failed = false;
        $errorMessage = '';
        
        $rule->validate('password', 'Password', function($message) use (&$failed, &$errorMessage) {
            $failed = true;
            $errorMessage = $message;
        });
        
        $this->assertTrue($failed);
        $this->assertStringContainsString('密碼必須包含至少一個數字', $errorMessage);
    }

    /**
     * 測試非字串密碼失敗
     */
    public function test_non_string_password_fails(): void
    {
        $rule = new SecurePassword();
        $failed = false;
        $errorMessage = '';
        
        $rule->validate('password', 123, function($message) use (&$failed, &$errorMessage) {
            $failed = true;
            $errorMessage = $message;
        });
        
        $this->assertTrue($failed);
        $this->assertEquals('密碼必須是字串', $errorMessage);
    }

    /**
     * 測試取得密碼要求
     */
    public function test_gets_password_requirements(): void
    {
        $requirements = SecurePassword::getPasswordRequirements();
        
        $this->assertContains('至少 8 個字元', $requirements);
        $this->assertContains('至少一個大寫字母', $requirements);
        $this->assertContains('至少一個小寫字母', $requirements);
        $this->assertContains('至少一個數字', $requirements);
    }

    /**
     * 測試特殊字元要求
     */
    public function test_symbol_requirement(): void
    {
        // 啟用特殊字元要求
        Setting::where('key', 'security.password_require_symbols')->update(['value' => json_encode(true)]);
        
        $rule = new SecurePassword();
        $failed = false;
        $errorMessage = '';
        
        $rule->validate('password', 'Password123', function($message) use (&$failed, &$errorMessage) {
            $failed = true;
            $errorMessage = $message;
        });
        
        $this->assertTrue($failed);
        $this->assertStringContainsString('密碼必須包含至少一個特殊字元', $errorMessage);
        
        // 測試包含特殊字元的密碼通過
        $failed = false;
        $rule->validate('password', 'Password123!', function($message) use (&$failed) {
            $failed = true;
        });
        
        $this->assertFalse($failed);
    }
}