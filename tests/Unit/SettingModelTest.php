<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Setting;
use App\Models\SettingChange;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;

class SettingModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試使用者
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /**
     * 測試設定建立
     */
    public function test_can_create_setting(): void
    {
        $setting = Setting::create([
            'key' => 'test.setting',
            'value' => 'test value',
            'category' => 'test',
            'type' => 'text',
            'description' => '測試設定',
        ]);

        $this->assertDatabaseHas('settings', [
            'key' => 'test.setting',
            'category' => 'test',
            'type' => 'text',
        ]);

        $this->assertEquals('test value', $setting->value);
    }

    /**
     * 測試加密設定
     */
    public function test_encrypted_setting_value(): void
    {
        $setting = Setting::create([
            'key' => 'test.encrypted',
            'value' => 'secret value',
            'category' => 'security',
            'type' => 'password',
            'is_encrypted' => true,
        ]);

        // 檢查資料庫中的值是否已加密
        $rawValue = $setting->getAttributes()['value'];
        $this->assertNotEquals('secret value', $rawValue);

        // 檢查透過模型存取器取得的值是否正確解密
        $this->assertEquals('secret value', $setting->value);
    }

    /**
     * 測試設定值更新
     */
    public function test_setting_value_update(): void
    {
        $setting = Setting::create([
            'key' => 'test.update',
            'value' => 'original value',
            'category' => 'test',
            'type' => 'text',
        ]);

        $result = $setting->updateValue('new value');

        $this->assertTrue($result);
        $this->assertEquals('new value', $setting->fresh()->value);
    }

    /**
     * 測試設定重設為預設值
     */
    public function test_reset_to_default(): void
    {
        $setting = Setting::create([
            'key' => 'test.reset',
            'value' => 'current value',
            'default_value' => 'default value',
            'category' => 'test',
            'type' => 'text',
        ]);

        $result = $setting->resetToDefault();

        $this->assertTrue($result);
        $this->assertEquals('default value', $setting->fresh()->value);
    }

    /**
     * 測試驗證規則生成
     */
    public function test_validation_rules(): void
    {
        // 測試文字類型
        $textSetting = Setting::create([
            'key' => 'test.text',
            'category' => 'test',
            'type' => 'text',
            'options' => ['max_length' => 100, 'required' => true],
        ]);

        $rules = $textSetting->validation_rules;
        $this->assertContains('string', $rules);
        $this->assertContains('max:100', $rules);
        $this->assertContains('required', $rules);

        // 測試數字類型
        $numberSetting = Setting::create([
            'key' => 'test.number',
            'category' => 'test',
            'type' => 'number',
            'options' => ['min' => 1, 'max' => 100],
        ]);

        $rules = $numberSetting->validation_rules;
        $this->assertContains('numeric', $rules);
        $this->assertContains('min:1', $rules);
        $this->assertContains('max:100', $rules);
    }

    /**
     * 測試設定變更記錄
     */
    public function test_setting_change_logging(): void
    {
        $setting = Setting::create([
            'key' => 'test.logging',
            'value' => 'original',
            'category' => 'test',
            'type' => 'text',
        ]);

        // 更新設定值
        $setting->updateValue('updated');

        // 檢查是否記錄了變更
        $this->assertDatabaseHas('setting_changes', [
            'setting_key' => 'test.logging',
            'old_value' => json_encode('original'),
            'new_value' => json_encode('updated'),
            'changed_by' => $this->user->id,
        ]);
    }

    /**
     * 測試設定分類查詢
     */
    public function test_scope_by_category(): void
    {
        Setting::create(['key' => 'basic.test1', 'category' => 'basic']);
        Setting::create(['key' => 'security.test1', 'category' => 'security']);
        Setting::create(['key' => 'basic.test2', 'category' => 'basic']);

        $basicSettings = Setting::byCategory('basic')->get();
        $this->assertCount(2, $basicSettings);

        $securitySettings = Setting::byCategory('security')->get();
        $this->assertCount(1, $securitySettings);
    }

    /**
     * 測試公開設定查詢
     */
    public function test_scope_public(): void
    {
        Setting::create(['key' => 'public.test', 'category' => 'test', 'is_public' => true]);
        Setting::create(['key' => 'private.test', 'category' => 'test', 'is_public' => false]);

        $publicSettings = Setting::public()->get();
        $this->assertCount(1, $publicSettings);
        $this->assertEquals('public.test', $publicSettings->first()->key);
    }

    /**
     * 測試系統設定查詢
     */
    public function test_scope_system(): void
    {
        Setting::create(['key' => 'system.test', 'category' => 'system', 'is_system' => true]);
        Setting::create(['key' => 'user.test', 'category' => 'user', 'is_system' => false]);

        $systemSettings = Setting::system()->get();
        $this->assertCount(1, $systemSettings);
        $this->assertEquals('system.test', $systemSettings->first()->key);
    }

    /**
     * 測試加密功能特性
     */
    public function test_encryptable_settings_trait(): void
    {
        $setting = new Setting([
            'key' => 'test.api_key',
            'type' => 'api_key',
            'is_encrypted' => false,
        ]);

        // 測試是否應該加密
        $this->assertTrue($setting->shouldEncrypt());

        // 測試加密值
        $encrypted = $setting->encryptValue('secret-api-key');
        $this->assertNotEquals('secret-api-key', $encrypted);

        // 測試解密值
        $decrypted = $setting->decryptValue($encrypted);
        $this->assertEquals('secret-api-key', $decrypted);
    }

    /**
     * 測試遮罩值顯示
     */
    public function test_masked_value_display(): void
    {
        $setting = Setting::create([
            'key' => 'test.secret',
            'category' => 'security',
            'value' => 'very-secret-value',
            'is_encrypted' => true,
        ]);

        $maskedValue = $setting->getMaskedValue(4);
        $this->assertStringStartsWith('very', $maskedValue);
        $this->assertStringContainsString('*', $maskedValue);
    }
}
