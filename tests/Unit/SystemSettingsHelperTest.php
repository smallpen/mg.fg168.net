<?php

namespace Tests\Unit;

use App\Helpers\SystemSettingsHelper;
use Tests\TestCase;

class SystemSettingsHelperTest extends TestCase
{
    /**
     * 測試取得所有分類
     */
    public function test_get_categories(): void
    {
        $categories = SystemSettingsHelper::getCategories();
        
        $this->assertNotEmpty($categories);
        $this->assertTrue($categories->has('basic'));
        $this->assertTrue($categories->has('security'));
        $this->assertTrue($categories->has('notification'));
        $this->assertTrue($categories->has('appearance'));
        $this->assertTrue($categories->has('integration'));
        $this->assertTrue($categories->has('maintenance'));
    }

    /**
     * 測試取得指定分類的設定
     */
    public function test_get_settings_by_category(): void
    {
        $basicSettings = SystemSettingsHelper::getSettingsByCategory('basic');
        
        $this->assertNotEmpty($basicSettings);
        $this->assertTrue($basicSettings->has('app.name'));
        $this->assertTrue($basicSettings->has('app.timezone'));
        $this->assertTrue($basicSettings->has('app.locale'));
    }

    /**
     * 測試取得設定配置
     */
    public function test_get_setting_config(): void
    {
        $config = SystemSettingsHelper::getSettingConfig('app.name');
        
        $this->assertNotNull($config);
        $this->assertEquals('basic', $config['category']);
        $this->assertEquals('text', $config['type']);
        $this->assertEquals('Laravel Admin System', $config['default']);
        $this->assertArrayHasKey('description', $config);
    }

    /**
     * 測試取得預設值
     */
    public function test_get_default_value(): void
    {
        $defaultValue = SystemSettingsHelper::getDefaultValue('app.name');
        $this->assertEquals('Laravel Admin System', $defaultValue);

        $defaultTimezone = SystemSettingsHelper::getDefaultValue('app.timezone');
        $this->assertEquals('Asia/Taipei', $defaultTimezone);
    }

    /**
     * 測試取得設定類型
     */
    public function test_get_setting_type(): void
    {
        $this->assertEquals('text', SystemSettingsHelper::getSettingType('app.name'));
        $this->assertEquals('boolean', SystemSettingsHelper::getSettingType('security.force_https'));
        $this->assertEquals('number', SystemSettingsHelper::getSettingType('security.password_min_length'));
        $this->assertEquals('select', SystemSettingsHelper::getSettingType('app.timezone'));
        $this->assertEquals('color', SystemSettingsHelper::getSettingType('appearance.primary_color'));
    }

    /**
     * 測試檢查加密設定
     */
    public function test_is_encrypted(): void
    {
        $this->assertTrue(SystemSettingsHelper::isEncrypted('notification.smtp_password'));
        $this->assertTrue(SystemSettingsHelper::isEncrypted('integration.google_client_secret'));
        $this->assertFalse(SystemSettingsHelper::isEncrypted('app.name'));
        $this->assertFalse(SystemSettingsHelper::isEncrypted('app.timezone'));
    }

    /**
     * 測試檢查預覽設定
     */
    public function test_is_previewable(): void
    {
        $this->assertTrue(SystemSettingsHelper::isPreviewable('appearance.primary_color'));
        $this->assertTrue(SystemSettingsHelper::isPreviewable('appearance.default_theme'));
        $this->assertFalse(SystemSettingsHelper::isPreviewable('app.name'));
        $this->assertFalse(SystemSettingsHelper::isPreviewable('security.password_min_length'));
    }

    /**
     * 測試取得依賴關係
     */
    public function test_get_dependencies(): void
    {
        $dependencies = SystemSettingsHelper::getDependencies('notification.smtp_host');
        $this->assertArrayHasKey('notification.email_enabled', $dependencies);

        $dependencies = SystemSettingsHelper::getDependencies('integration.google_client_id');
        $this->assertArrayHasKey('integration.google_oauth_enabled', $dependencies);
    }

    /**
     * 測試驗證設定值
     */
    public function test_validate_setting(): void
    {
        // 測試有效的設定值
        $result = SystemSettingsHelper::validateSetting('app.name', 'Test App');
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);

        // 測試無效的設定值（超過長度限制）
        $result = SystemSettingsHelper::validateSetting('app.name', str_repeat('a', 101));
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);

        // 測試數字範圍驗證
        $result = SystemSettingsHelper::validateSetting('security.password_min_length', 8);
        $this->assertTrue($result['valid']);

        $result = SystemSettingsHelper::validateSetting('security.password_min_length', 25);
        $this->assertFalse($result['valid']);
    }

    /**
     * 測試取得顯示值
     */
    public function test_get_display_value(): void
    {
        $this->assertEquals('是', SystemSettingsHelper::getDisplayValue('security.force_https', true));
        $this->assertEquals('否', SystemSettingsHelper::getDisplayValue('security.force_https', false));
        
        $this->assertEquals('••••••••', SystemSettingsHelper::getDisplayValue('notification.smtp_password', 'secret'));
        $this->assertEquals('', SystemSettingsHelper::getDisplayValue('notification.smtp_password', ''));
        
        $this->assertEquals('#3B82F6', SystemSettingsHelper::getDisplayValue('appearance.primary_color', '#3b82f6'));
    }

    /**
     * 測試格式化儲存值
     */
    public function test_format_value_for_storage(): void
    {
        $this->assertTrue(SystemSettingsHelper::formatValueForStorage('security.force_https', 'true'));
        $this->assertFalse(SystemSettingsHelper::formatValueForStorage('security.force_https', 'false'));
        $this->assertFalse(SystemSettingsHelper::formatValueForStorage('security.force_https', '0'));
        
        $this->assertEquals(8, SystemSettingsHelper::formatValueForStorage('security.password_min_length', '8'));
        $this->assertEquals(0, SystemSettingsHelper::formatValueForStorage('security.password_min_length', 'invalid'));
        
        $this->assertEquals('Test App', SystemSettingsHelper::formatValueForStorage('app.name', 'Test App'));
    }

    /**
     * 測試取得輸入元件
     */
    public function test_get_input_component(): void
    {
        $component = SystemSettingsHelper::getInputComponent('text');
        $this->assertArrayHasKey('component', $component);
        $this->assertEquals('text-input', $component['component']);

        $component = SystemSettingsHelper::getInputComponent('boolean');
        $this->assertEquals('toggle-input', $component['component']);

        $component = SystemSettingsHelper::getInputComponent('select');
        $this->assertEquals('select-input', $component['component']);
    }

    /**
     * 測試取得測試群組
     */
    public function test_get_test_group(): void
    {
        $this->assertEquals('smtp', SystemSettingsHelper::getTestGroup('notification.smtp_host'));
        $this->assertEquals('aws_s3', SystemSettingsHelper::getTestGroup('integration.aws_access_key'));
        $this->assertEquals('google_oauth', SystemSettingsHelper::getTestGroup('integration.google_client_id'));
        $this->assertNull(SystemSettingsHelper::getTestGroup('app.name'));
    }
}
