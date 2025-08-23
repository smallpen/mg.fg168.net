<?php

namespace Tests\Unit;

use App\Services\SensitiveDataFilter;
use Tests\TestCase;

class SensitiveDataFilterTest extends TestCase
{
    protected SensitiveDataFilter $filter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filter = new SensitiveDataFilter();
    }

    /** @test */
    public function it_can_identify_sensitive_fields()
    {
        $this->assertTrue($this->filter->isSensitiveField('password'));
        $this->assertTrue($this->filter->isSensitiveField('user_password'));
        $this->assertTrue($this->filter->isSensitiveField('api_token'));
        $this->assertTrue($this->filter->isSensitiveField('secret_key'));
        $this->assertTrue($this->filter->isSensitiveField('credit_card_number'));
        
        $this->assertFalse($this->filter->isSensitiveField('username'));
        $this->assertFalse($this->filter->isSensitiveField('name'));
        $this->assertFalse($this->filter->isSensitiveField('status'));
    }

    /** @test */
    public function it_can_detect_sensitive_data_in_values()
    {
        // 信用卡號碼
        $this->assertTrue($this->filter->containsSensitiveData('4111 1111 1111 1111'));
        $this->assertTrue($this->filter->containsSensitiveData('4111-1111-1111-1111'));
        $this->assertTrue($this->filter->containsSensitiveData('4111111111111111'));
        
        // 電子郵件
        $this->assertTrue($this->filter->containsSensitiveData('user@example.com'));
        $this->assertTrue($this->filter->containsSensitiveData('test.email+tag@domain.co.uk'));
        
        // 電話號碼
        $this->assertTrue($this->filter->containsSensitiveData('123-456-7890'));
        $this->assertTrue($this->filter->containsSensitiveData('123.456.7890'));
        $this->assertTrue($this->filter->containsSensitiveData('1234567890'));
        
        // IP 位址
        $this->assertTrue($this->filter->containsSensitiveData('192.168.1.1'));
        $this->assertTrue($this->filter->containsSensitiveData('10.0.0.1'));
        
        // JWT Token
        $this->assertTrue($this->filter->containsSensitiveData('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c'));
        
        // 一般文字
        $this->assertFalse($this->filter->containsSensitiveData('這是一般文字'));
        $this->assertFalse($this->filter->containsSensitiveData('username123'));
    }

    /** @test */
    public function it_can_mask_sensitive_values()
    {
        $this->assertEquals('pass****', $this->filter->maskValue('password'));
        $this->assertEquals('secr*****', $this->filter->maskValue('secret123'));
        $this->assertEquals('****', $this->filter->maskValue('test'));
        $this->assertEquals('**', $this->filter->maskValue('ab'));
        
        // 測試自訂可見字元數量
        $this->assertEquals('passw***', $this->filter->maskValue('password', 5));
        $this->assertEquals('p*******', $this->filter->maskValue('password', 1));
    }

    /** @test */
    public function it_can_filter_properties_array()
    {
        $properties = [
            'username' => 'john_doe',
            'password' => 'secret123',
            'email' => 'john@example.com',
            'api_token' => 'abc123def456',
            'profile' => [
                'name' => 'John Doe',
                'phone' => '123-456-7890',
                'address' => '123 Main St',
                'credit_card' => '4111-1111-1111-1111'
            ],
            'settings' => [
                'theme' => 'dark',
                'secret_key' => 'very_secret_key'
            ]
        ];

        $filtered = $this->filter->filterProperties($properties);

        // 一般欄位應該保持不變
        $this->assertEquals('john_doe', $filtered['username']);
        $this->assertEquals('John Doe', $filtered['profile']['name']);
        $this->assertEquals('dark', $filtered['settings']['theme']);

        // 敏感欄位應該被遮蔽
        $this->assertEquals('secr*****', $filtered['password']);
        $this->assertEquals('abc1********', $filtered['api_token']);
        $this->assertEquals('very***********', $filtered['settings']['secret_key']);
        $this->assertEquals('4111***************', $filtered['profile']['credit_card']);

        // 包含敏感資料的值應該被過濾
        $this->assertStringContainsString('*', $filtered['profile']['phone']);
    }

    /** @test */
    public function it_can_filter_text_content()
    {
        $text = '使用者 john@example.com 使用信用卡 4111-1111-1111-1111 進行付款，電話號碼是 123-456-7890';
        
        $filtered = $this->filter->filterText($text);
        
        // 敏感資料應該被遮蔽
        $this->assertStringNotContainsString('john@example.com', $filtered);
        $this->assertStringNotContainsString('4111-1111-1111-1111', $filtered);
        $this->assertStringNotContainsString('123-456-7890', $filtered);
        $this->assertStringContainsString('*', $filtered);
    }

    /** @test */
    public function it_can_add_custom_sensitive_fields()
    {
        $this->filter->addSensitiveFields(['custom_field', 'another_sensitive']);
        
        $this->assertTrue($this->filter->isSensitiveField('custom_field'));
        $this->assertTrue($this->filter->isSensitiveField('another_sensitive'));
        $this->assertTrue($this->filter->isSensitiveField('my_custom_field_name'));
    }

    /** @test */
    public function it_can_add_custom_sensitive_patterns()
    {
        // 新增台灣身分證字號模式
        $this->filter->addSensitivePatterns(['/[A-Z][12]\d{8}/']);
        
        $this->assertTrue($this->filter->containsSensitiveData('A123456789'));
        $this->assertTrue($this->filter->containsSensitiveData('B234567890'));
        $this->assertFalse($this->filter->containsSensitiveData('C345678901')); // 無效格式
    }

    /** @test */
    public function it_can_set_custom_mask_character()
    {
        $this->filter->setMaskCharacter('#');
        
        $masked = $this->filter->maskValue('password');
        
        $this->assertEquals('pass####', $masked);
        $this->assertStringNotContainsString('*', $masked);
    }

    /** @test */
    public function it_can_get_sensitive_fields_and_patterns()
    {
        $fields = $this->filter->getSensitiveFields();
        $patterns = $this->filter->getSensitivePatterns();
        
        $this->assertIsArray($fields);
        $this->assertIsArray($patterns);
        $this->assertContains('password', $fields);
        $this->assertContains('token', $fields);
        $this->assertNotEmpty($patterns);
    }

    /** @test */
    public function it_can_reset_to_default_settings()
    {
        // 修改設定
        $this->filter->addSensitiveFields(['custom']);
        $this->filter->setMaskCharacter('#');
        
        // 重設
        $this->filter->reset();
        
        // 檢查是否恢復預設值
        $this->assertFalse($this->filter->isSensitiveField('custom'));
        $this->assertEquals('pass****', $this->filter->maskValue('password'));
    }

    /** @test */
    public function it_handles_non_string_values_gracefully()
    {
        $properties = [
            'number' => 123,
            'boolean' => true,
            'null_value' => null,
            'array' => ['nested' => 'value'],
            'password' => 'secret123'
        ];

        $filtered = $this->filter->filterProperties($properties);

        $this->assertEquals(123, $filtered['number']);
        $this->assertTrue($filtered['boolean']);
        $this->assertNull($filtered['null_value']);
        $this->assertEquals(['nested' => 'value'], $filtered['array']);
        $this->assertEquals('secr*****', $filtered['password']);
    }

    /** @test */
    public function it_handles_deeply_nested_arrays()
    {
        $properties = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'password' => 'deep_secret',
                        'normal_field' => 'normal_value'
                    ]
                ]
            ]
        ];

        $filtered = $this->filter->filterProperties($properties);

        $this->assertEquals('deep*******', $filtered['level1']['level2']['level3']['password']);
        $this->assertEquals('normal_value', $filtered['level1']['level2']['level3']['normal_field']);
    }

    /** @test */
    public function it_returns_filtered_placeholder_for_non_string_sensitive_values()
    {
        $result = $this->filter->maskValue(123);
        $this->assertEquals('[FILTERED]', $result);

        $result = $this->filter->maskValue(true);
        $this->assertEquals('[FILTERED]', $result);

        $result = $this->filter->maskValue(['array']);
        $this->assertEquals('[FILTERED]', $result);
    }
}