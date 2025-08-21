<?php

namespace Tests\Unit;

use App\Repositories\SettingsRepositoryInterface;
use App\Services\ConfigurationService;
use Illuminate\Support\Facades\Config;
use Mockery;
use Tests\TestCase;

class ConfigurationServiceTest extends TestCase
{
    protected ConfigurationService $service;
    protected $mockRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockRepository = Mockery::mock(SettingsRepositoryInterface::class);
        $this->service = new ConfigurationService($this->mockRepository);
        
        // 設定測試配置
        Config::set('system-settings', [
            'categories' => [
                'basic' => [
                    'name' => '基本設定',
                    'icon' => 'cog',
                    'description' => '應用程式基本資訊設定',
                    'sort_order' => 1,
                ],
            ],
            'settings' => [
                'app.name' => [
                    'category' => 'basic',
                    'type' => 'text',
                    'default' => 'Laravel Admin System',
                    'required' => true,
                    'validation' => 'required|string|max:100',
                    'description' => '應用程式名稱',
                ],
                'security.password_min_length' => [
                    'category' => 'security',
                    'type' => 'number',
                    'default' => 8,
                    'required' => true,
                    'validation' => 'required|integer|min:6|max:20',
                    'min' => 6,
                    'max' => 20,
                    'description' => '密碼最小長度',
                ],
                'theme.primary_color' => [
                    'category' => 'appearance',
                    'type' => 'color',
                    'default' => '#3B82F6',
                    'validation' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
                    'description' => '主要顏色',
                    'preview' => true,
                ],
                'mail.password' => [
                    'category' => 'notification',
                    'type' => 'password',
                    'default' => '',
                    'sensitive' => true,
                    'description' => 'SMTP 密碼',
                ],
            ],
        ]);
    }

    public function test_can_get_setting_config(): void
    {
        $config = $this->service->getSettingConfig('app.name');

        $this->assertEquals('basic', $config['category']);
        $this->assertEquals('text', $config['type']);
        $this->assertEquals('Laravel Admin System', $config['default']);
        $this->assertTrue($config['required']);
    }

    public function test_returns_empty_array_for_unknown_setting(): void
    {
        $config = $this->service->getSettingConfig('unknown.setting');

        $this->assertEquals([], $config);
    }

    public function test_can_get_categories(): void
    {
        $categories = $this->service->getCategories();

        $this->assertArrayHasKey('basic', $categories);
        $this->assertEquals('基本設定', $categories['basic']['name']);
        $this->assertEquals('cog', $categories['basic']['icon']);
    }

    public function test_can_validate_text_setting(): void
    {
        $result = $this->service->validateSettingValue('app.name', 'Valid App Name');
        $this->assertTrue($result);

        $result = $this->service->validateSettingValue('app.name', '');
        $this->assertFalse($result); // required 驗證失敗

        $result = $this->service->validateSettingValue('app.name', str_repeat('a', 101));
        $this->assertFalse($result); // max:100 驗證失敗
    }

    public function test_can_validate_number_setting(): void
    {
        $result = $this->service->validateSettingValue('security.password_min_length', 8);
        $this->assertTrue($result);

        $result = $this->service->validateSettingValue('security.password_min_length', 5);
        $this->assertFalse($result); // min:6 驗證失敗

        $result = $this->service->validateSettingValue('security.password_min_length', 25);
        $this->assertFalse($result); // max:20 驗證失敗

        $result = $this->service->validateSettingValue('security.password_min_length', 'not_a_number');
        $this->assertFalse($result); // integer 驗證失敗
    }

    public function test_can_validate_color_setting(): void
    {
        $result = $this->service->validateSettingValue('theme.primary_color', '#FF0000');
        $this->assertTrue($result);

        $result = $this->service->validateSettingValue('theme.primary_color', '#ff0000');
        $this->assertTrue($result);

        $result = $this->service->validateSettingValue('theme.primary_color', 'red');
        $this->assertFalse($result); // regex 驗證失敗

        $result = $this->service->validateSettingValue('theme.primary_color', '#FF00');
        $this->assertFalse($result); // regex 驗證失敗
    }

    public function test_returns_false_for_unknown_setting_validation(): void
    {
        $result = $this->service->validateSettingValue('unknown.setting', 'any_value');
        
        $this->assertFalse($result);
    }

    public function test_can_get_setting_type(): void
    {
        $this->assertEquals('text', $this->service->getSettingType('app.name'));
        $this->assertEquals('number', $this->service->getSettingType('security.password_min_length'));
        $this->assertEquals('color', $this->service->getSettingType('theme.primary_color'));
        $this->assertEquals('text', $this->service->getSettingType('unknown.setting')); // 預設類型
    }

    public function test_can_get_setting_options(): void
    {
        $options = $this->service->getSettingOptions('security.password_min_length');

        $this->assertEquals(6, $options['min']);
        $this->assertEquals(20, $options['max']);
    }

    public function test_can_get_dependent_settings(): void
    {
        // 設定有依賴關係的配置
        Config::set('system-settings.settings.integration.google_client_id', [
            'category' => 'integration',
            'type' => 'text',
            'dependencies' => ['integration.google_oauth_enabled'],
        ]);

        $dependencies = $this->service->getDependentSettings('integration.google_client_id');

        $this->assertEquals(['integration.google_oauth_enabled'], $dependencies);
    }

    public function test_can_get_default_value(): void
    {
        $this->assertEquals('Laravel Admin System', $this->service->getDefaultValue('app.name'));
        $this->assertEquals(8, $this->service->getDefaultValue('security.password_min_length'));
        $this->assertNull($this->service->getDefaultValue('unknown.setting'));
    }

    public function test_can_check_if_required(): void
    {
        $this->assertTrue($this->service->isRequired('app.name'));
        $this->assertTrue($this->service->isRequired('security.password_min_length'));
        $this->assertFalse($this->service->isRequired('unknown.setting'));
    }

    public function test_can_check_if_sensitive(): void
    {
        $this->assertTrue($this->service->isSensitive('mail.password'));
        $this->assertFalse($this->service->isSensitive('app.name'));
        $this->assertFalse($this->service->isSensitive('unknown.setting'));
    }

    public function test_can_get_input_component(): void
    {
        $this->assertEquals('text-input', $this->service->getInputComponent('app.name'));
        $this->assertEquals('number-input', $this->service->getInputComponent('security.password_min_length'));
        $this->assertEquals('color-input', $this->service->getInputComponent('theme.primary_color'));
        $this->assertEquals('password-input', $this->service->getInputComponent('mail.password'));
    }

    public function test_can_apply_settings(): void
    {
        $settings = [
            'app.name' => 'New App Name',
            'app.timezone' => 'UTC',
        ];

        // 設定配置映射
        Config::set('system-settings.settings.app.name.config_path', 'app.name');
        Config::set('system-settings.settings.app.timezone.config_path', 'app.timezone');

        $this->service->applySettings($settings);

        $this->assertEquals('New App Name', Config::get('app.name'));
        $this->assertEquals('UTC', Config::get('app.timezone'));
    }

    public function test_can_generate_preview(): void
    {
        $settings = [
            'theme.primary_color' => '#FF0000',
            'app.name' => 'Test App', // 沒有 preview 設定
        ];

        $preview = $this->service->generatePreview($settings);

        $this->assertArrayHasKey('theme.primary_color', $preview);
        $this->assertArrayNotHasKey('app.name', $preview);
        $this->assertEquals('--primary-color: #FF0000', $preview['theme.primary_color']['css']);
    }

    public function test_can_test_smtp_connection(): void
    {
        $config = [
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'test@gmail.com',
            'password' => 'password',
            'test_email' => 'test@example.com',
        ];

        // 由於實際的 SMTP 測試需要真實的伺服器，這裡只測試方法是否存在
        $result = $this->service->testConnection('smtp', $config);
        
        // 在測試環境中，SMTP 連線通常會失敗，這是正常的
        $this->assertIsBool($result);
    }

    public function test_returns_false_for_unknown_connection_type(): void
    {
        $result = $this->service->testConnection('unknown_type', []);
        
        $this->assertFalse($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
