<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\SecurityService;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * 安全服務測試
 */
class SecurityServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 安全服務實例
     */
    protected SecurityService $securityService;

    /**
     * 設定測試環境
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->securityService = app(SecurityService::class);
        
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
            'security.login_max_attempts' => 5,
            'security.lockout_duration' => 15,
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
     * 測試密碼驗證功能
     */
    public function test_validates_password(): void
    {
        // 測試有效密碼
        $result = $this->securityService->validatePassword('Password123');
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);

        // 測試無效密碼（太短）
        $result = $this->securityService->validatePassword('Pass1');
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);

        // 測試無效密碼（缺少大寫字母）
        $result = $this->securityService->validatePassword('password123');
        $this->assertFalse($result['valid']);
        $this->assertContains('密碼必須包含至少一個大寫字母', $result['errors']);
    }

    /**
     * 測試密碼強度計算
     */
    public function test_calculates_password_strength(): void
    {
        // 弱密碼
        $result = $this->securityService->calculatePasswordStrength('pass');
        $this->assertEquals('weak', $result['level']);

        // 中等密碼
        $result = $this->securityService->calculatePasswordStrength('Password1');
        $this->assertEquals('strong', $result['level']); // 'Password1' 符合所有要求所以是強密碼

        // 強密碼
        $result = $this->securityService->calculatePasswordStrength('Password123!@#');
        $this->assertEquals('strong', $result['level']);
    }

    /**
     * 測試登入政策取得
     */
    public function test_gets_login_policy(): void
    {
        $policy = $this->securityService->getLoginPolicy();
        
        $this->assertEquals(5, $policy['max_attempts']);
        $this->assertEquals(15, $policy['lockout_duration']);
    }

    /**
     * 測試密碼政策取得
     */
    public function test_gets_password_policy(): void
    {
        $policy = $this->securityService->getPasswordPolicy();
        
        $this->assertEquals(8, $policy['min_length']);
        $this->assertTrue($policy['require_uppercase']);
        $this->assertTrue($policy['require_lowercase']);
        $this->assertTrue($policy['require_numbers']);
        $this->assertFalse($policy['require_symbols']);
    }

    /**
     * 測試雙因子認證檢查
     */
    public function test_checks_two_factor_enabled(): void
    {
        // 預設應該是停用的
        $this->assertFalse($this->securityService->isTwoFactorEnabled());

        // 建立啟用雙因子認證的設定
        Setting::create([
            'key' => 'security.two_factor_enabled',
            'value' => true,
            'category' => 'security',
            'type' => 'boolean',
            'is_system' => true,
        ]);

        // 清除快取並重新檢查
        $this->securityService->clearSecurityCache();
        $this->assertTrue($this->securityService->isTwoFactorEnabled());
    }
}