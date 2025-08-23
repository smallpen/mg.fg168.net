<?php

namespace Tests\Feature;

use App\Services\SettingsSecurityService;
use App\Services\EncryptionService;
use App\Http\Middleware\SettingsAccessControl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 設定安全基本功能測試
 */
class SettingsSecurityBasicTest extends TestCase
{
    use RefreshDatabase;

    protected SettingsSecurityService $securityService;
    protected EncryptionService $encryptionService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->encryptionService = app(EncryptionService::class);
        $this->securityService = app(SettingsSecurityService::class);
    }

    /**
     * 測試敏感設定識別
     */
    public function test_sensitive_setting_identification(): void
    {
        // 測試敏感設定鍵值
        $this->assertTrue($this->securityService->shouldEncryptSetting('integration.stripe_secret_key'));
        $this->assertTrue($this->securityService->shouldEncryptSetting('notification.smtp_password'));
        $this->assertTrue($this->securityService->shouldEncryptSetting('integration.api_keys'));
        
        // 測試非敏感設定鍵值
        $this->assertFalse($this->securityService->shouldEncryptSetting('app.name'));
        $this->assertFalse($this->securityService->shouldEncryptSetting('app.timezone'));
        $this->assertFalse($this->securityService->shouldEncryptSetting('appearance.primary_color'));
    }

    /**
     * 測試加密服務基本功能
     */
    public function test_encryption_service_basic_functionality(): void
    {
        $originalValue = 'sk_test_123456789';
        
        // 測試加密
        $encryptedValue = $this->encryptionService->encrypt($originalValue);
        $this->assertNotEquals($originalValue, $encryptedValue);
        $this->assertNotEmpty($encryptedValue);
        
        // 測試解密
        $decryptedValue = $this->encryptionService->decrypt($encryptedValue);
        $this->assertEquals($originalValue, $decryptedValue);
    }

    /**
     * 測試敏感資料遮罩
     */
    public function test_sensitive_data_masking(): void
    {
        $sensitiveValue = 'sk_test_1234567890abcdef';
        
        // 測試預設遮罩
        $masked = $this->encryptionService->maskSensitiveData($sensitiveValue);
        $this->assertStringStartsWith('sk_t', $masked);
        $this->assertStringEndsWith('cdef', $masked);
        $this->assertStringContainsString('*', $masked);
        
        // 測試自訂遮罩參數
        $customMasked = $this->encryptionService->maskSensitiveData($sensitiveValue, 2, 2, '#');
        $this->assertStringStartsWith('sk', $customMasked);
        $this->assertStringEndsWith('ef', $customMasked);
        $this->assertStringContainsString('#', $customMasked);
    }

    /**
     * 測試 API 金鑰格式驗證
     */
    public function test_api_key_format_validation(): void
    {
        // 測試有效的 API 金鑰格式
        $this->assertTrue($this->encryptionService->validateApiKeyFormat('sk-1234567890abcdef1234567890abcdef1234567890abcdef'));
        $this->assertTrue($this->encryptionService->validateApiKeyFormat('pk_test_1234567890abcdef1234567890'));
        $this->assertTrue($this->encryptionService->validateApiKeyFormat('AIzaSyDdI0hCZtE6vySjMm-WEfRq3CPzqKqqsHI'));
        
        // 測試無效的 API 金鑰格式
        $this->assertFalse($this->encryptionService->validateApiKeyFormat(''));
        $this->assertFalse($this->encryptionService->validateApiKeyFormat('short'));
        $this->assertFalse($this->encryptionService->validateApiKeyFormat('invalid-chars-!@#$%'));
    }

    /**
     * 測試 API 金鑰生成
     */
    public function test_api_key_generation(): void
    {
        // 測試預設長度
        $apiKey = $this->encryptionService->generateApiKey();
        $this->assertEquals(32, strlen($apiKey));
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $apiKey);
        
        // 測試自訂長度和前綴
        $customKey = $this->encryptionService->generateApiKey(16, 'test_');
        $this->assertEquals(21, strlen($customKey)); // 16 + 5 (prefix)
        $this->assertStringStartsWith('test_', $customKey);
    }

    /**
     * 測試 IP 範圍檢查
     */
    public function test_ip_range_checking(): void
    {
        $middleware = new SettingsAccessControl();
        $reflection = new \ReflectionClass($middleware);
        $method = $reflection->getMethod('ipInRange');
        $method->setAccessible(true);
        
        // 測試單一 IP 匹配
        $this->assertTrue($method->invoke($middleware, '192.168.1.100', '192.168.1.100'));
        $this->assertFalse($method->invoke($middleware, '192.168.1.101', '192.168.1.100'));
        
        // 測試 CIDR 範圍匹配
        $this->assertTrue($method->invoke($middleware, '192.168.1.100', '192.168.1.0/24'));
        $this->assertTrue($method->invoke($middleware, '192.168.1.1', '192.168.1.0/24'));
        $this->assertFalse($method->invoke($middleware, '192.168.2.1', '192.168.1.0/24'));
        
        // 測試更大的 CIDR 範圍
        $this->assertTrue($method->invoke($middleware, '10.0.0.1', '10.0.0.0/8'));
        $this->assertTrue($method->invoke($middleware, '10.255.255.255', '10.0.0.0/8'));
        $this->assertFalse($method->invoke($middleware, '11.0.0.1', '10.0.0.0/8'));
    }

    /**
     * 測試安全設定儲存
     */
    public function test_secure_setting_storage(): void
    {
        // 測試敏感設定加密儲存
        $sensitiveKey = 'integration.stripe_secret_key';
        $sensitiveValue = 'sk_test_123456789';
        
        $result = $this->securityService->secureStoreSetting($sensitiveKey, $sensitiveValue);
        
        $this->assertTrue($result['success']);
        $this->assertTrue($result['encrypted']);
        $this->assertNotEquals($sensitiveValue, $result['stored_value']);
        
        // 測試解密讀取
        $decryptedValue = $this->securityService->secureReadSetting($sensitiveKey, $result['stored_value']);
        $this->assertEquals($sensitiveValue, $decryptedValue);
        
        // 測試非敏感設定不加密
        $normalKey = 'app.name';
        $normalValue = 'Test Application';
        
        $normalResult = $this->securityService->secureStoreSetting($normalKey, $normalValue);
        
        $this->assertTrue($normalResult['success']);
        $this->assertFalse($normalResult['encrypted']);
        $this->assertEquals($normalValue, $normalResult['stored_value']);
    }

    /**
     * 測試系統設定識別
     */
    public function test_system_setting_identification(): void
    {
        $reflection = new \ReflectionClass($this->securityService);
        $method = $reflection->getMethod('isSystemSetting');
        $method->setAccessible(true);
        
        // 測試系統設定
        $this->assertTrue($method->invoke($this->securityService, 'app.name'));
        $this->assertTrue($method->invoke($this->securityService, 'app.timezone'));
        $this->assertTrue($method->invoke($this->securityService, 'system.maintenance_mode'));
        $this->assertTrue($method->invoke($this->securityService, 'security.force_https'));
        
        // 測試非系統設定
        $this->assertFalse($method->invoke($this->securityService, 'appearance.primary_color'));
        $this->assertFalse($method->invoke($this->securityService, 'notification.email_enabled'));
        $this->assertFalse($method->invoke($this->securityService, 'integration.google_client_id'));
    }

    /**
     * 測試加密是否檢查
     */
    public function test_encryption_detection(): void
    {
        $plainText = 'This is plain text';
        $encryptedText = $this->encryptionService->encrypt($plainText);
        
        // 測試加密檢測
        $this->assertFalse($this->encryptionService->isEncrypted($plainText));
        $this->assertTrue($this->encryptionService->isEncrypted($encryptedText));
        
        // 測試空值
        $this->assertFalse($this->encryptionService->isEncrypted(''));
        $this->assertFalse($this->encryptionService->isEncrypted(null));
    }

    /**
     * 測試批量加密解密
     */
    public function test_batch_encryption_decryption(): void
    {
        $data = [
            'stripe_key' => 'sk_test_123456789',
            'paypal_secret' => 'paypal_secret_abc123',
            'normal_setting' => 'normal_value',
        ];
        
        $encryptFields = ['stripe_key', 'paypal_secret'];
        
        // 測試批量加密
        $encryptedData = $this->encryptionService->encryptArray($data, $encryptFields);
        
        $this->assertNotEquals($data['stripe_key'], $encryptedData['stripe_key']);
        $this->assertNotEquals($data['paypal_secret'], $encryptedData['paypal_secret']);
        $this->assertEquals($data['normal_setting'], $encryptedData['normal_setting']);
        
        // 測試批量解密
        $decryptedData = $this->encryptionService->decryptArray($encryptedData, $encryptFields);
        
        $this->assertEquals($data['stripe_key'], $decryptedData['stripe_key']);
        $this->assertEquals($data['paypal_secret'], $decryptedData['paypal_secret']);
        $this->assertEquals($data['normal_setting'], $decryptedData['normal_setting']);
    }
}
