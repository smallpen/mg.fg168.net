<?php

namespace Tests\Feature\Livewire\Admin\Settings;

use App\Livewire\Admin\Settings\SettingPreview;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Models\Permission;
use App\Repositories\SettingsRepositoryInterface;
use App\Services\ConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Mockery;

/**
 * SettingPreview 元件功能測試
 * 
 * 測試設定預覽元件的完整功能，包含即時預覽、連線測試和影響分析
 */
class SettingPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;
    protected Role $adminRole;
    protected array $testSettings;
    protected $mockRepository;
    protected $mockConfigService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createRolesAndUsers();
        $this->createTestSettings();
        $this->createMockServices();
    }

    private function createRolesAndUsers(): void
    {
        // 建立管理員角色
        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '系統管理員',
            'is_system_role' => true,
            'is_active' => true,
        ]);

        // 建立權限
        $permissions = [
            'system.settings.view' => '檢視系統設定',
            'system.settings.preview' => '預覽系統設定',
            'system.settings.test' => '測試系統設定',
        ];

        foreach ($permissions as $name => $displayName) {
            Permission::create([
                'name' => $name,
                'display_name' => $displayName,
                'module' => 'settings',
                'type' => 'action'
            ]);
        }

        $this->adminRole->permissions()->attach(
            Permission::whereIn('name', array_keys($permissions))->pluck('id')
        );

        // 建立使用者
        $this->adminUser = User::factory()->create([
            'username' => 'admin',
            'name' => '系統管理員',
            'email' => 'admin@example.com',
            'is_active' => true,
        ]);
        $this->adminUser->roles()->attach($this->adminRole);

        $this->regularUser = User::factory()->create([
            'username' => 'user',
            'name' => '一般使用者',
            'email' => 'user@example.com',
            'is_active' => true,
        ]);
    }

    private function createTestSettings(): void
    {
        $this->testSettings = [
            // 外觀設定
            [
                'key' => 'appearance.default_theme',
                'value' => 'auto',
                'category' => 'appearance',
                'type' => 'select',
                'description' => '預設主題',
                'default_value' => 'auto',
                'is_encrypted' => false,
                'is_system' => false,
                'is_public' => true,
            ],
            [
                'key' => 'appearance.primary_color',
                'value' => '#3B82F6',
                'category' => 'appearance',
                'type' => 'color',
                'description' => '主要顏色',
                'default_value' => '#3B82F6',
                'is_encrypted' => false,
                'is_system' => false,
                'is_public' => true,
            ],
            [
                'key' => 'appearance.secondary_color',
                'value' => '#6B7280',
                'category' => 'appearance',
                'type' => 'color',
                'description' => '次要顏色',
                'default_value' => '#6B7280',
                'is_encrypted' => false,
                'is_system' => false,
                'is_public' => true,
            ],
            [
                'key' => 'appearance.logo_url',
                'value' => '/images/logo.png',
                'category' => 'appearance',
                'type' => 'image',
                'description' => '系統標誌',
                'default_value' => '',
                'is_encrypted' => false,
                'is_system' => false,
                'is_public' => true,
            ],
            // 通知設定
            [
                'key' => 'notification.smtp_host',
                'value' => 'smtp.gmail.com',
                'category' => 'notification',
                'type' => 'text',
                'description' => 'SMTP 主機',
                'default_value' => '',
                'is_encrypted' => false,
                'is_system' => false,
                'is_public' => false,
            ],
            [
                'key' => 'notification.smtp_port',
                'value' => 587,
                'category' => 'notification',
                'type' => 'number',
                'description' => 'SMTP 埠號',
                'default_value' => 587,
                'is_encrypted' => false,
                'is_system' => false,
                'is_public' => false,
            ],
            [
                'key' => 'notification.smtp_username',
                'value' => 'admin@example.com',
                'category' => 'notification',
                'type' => 'email',
                'description' => 'SMTP 使用者名稱',
                'default_value' => '',
                'is_encrypted' => false,
                'is_system' => false,
                'is_public' => false,
            ],
            [
                'key' => 'notification.smtp_password',
                'value' => 'password123',
                'category' => 'notification',
                'type' => 'password',
                'description' => 'SMTP 密碼',
                'default_value' => '',
                'is_encrypted' => true,
                'is_system' => false,
                'is_public' => false,
            ],
            // 整合設定
            [
                'key' => 'integration.aws_access_key',
                'value' => 'AKIAIOSFODNN7EXAMPLE',
                'category' => 'integration',
                'type' => 'text',
                'description' => 'AWS Access Key',
                'default_value' => '',
                'is_encrypted' => true,
                'is_system' => false,
                'is_public' => false,
            ],
            [
                'key' => 'integration.aws_secret_key',
                'value' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
                'category' => 'integration',
                'type' => 'password',
                'description' => 'AWS Secret Key',
                'default_value' => '',
                'is_encrypted' => true,
                'is_system' => false,
                'is_public' => false,
            ],
            [
                'key' => 'integration.google_client_id',
                'value' => '123456789.apps.googleusercontent.com',
                'category' => 'integration',
                'type' => 'text',
                'description' => 'Google Client ID',
                'default_value' => '',
                'is_encrypted' => false,
                'is_system' => false,
                'is_public' => false,
            ],
            [
                'key' => 'integration.google_client_secret',
                'value' => 'GOCSPX-example_secret',
                'category' => 'integration',
                'type' => 'password',
                'description' => 'Google Client Secret',
                'default_value' => '',
                'is_encrypted' => true,
                'is_system' => false,
                'is_public' => false,
            ],
        ];

        foreach ($this->testSettings as $settingData) {
            Setting::create($settingData);
        }
    }

    private function createMockServices(): void
    {
        $this->mockRepository = Mockery::mock(SettingsRepositoryInterface::class);
        $this->mockConfigService = Mockery::mock(ConfigurationService::class);
        
        // 設定預設的 Mock 行為
        $this->mockRepository->shouldReceive('getSetting')
            ->andReturnUsing(function ($key) {
                $settingData = collect($this->testSettings)->firstWhere('key', $key);
                if ($settingData) {
                    $setting = new Setting();
                    $setting->fill($settingData);
                    $setting->id = fake()->numberBetween(1, 1000);
                    return $setting;
                }
                return null;
            });

        $this->mockConfigService->shouldReceive('generatePreview')
            ->andReturnUsing(function ($settings) {
                return [
                    'theme' => $settings['appearance.default_theme'] ?? 'auto',
                    'colors' => [
                        'primary' => $settings['appearance.primary_color'] ?? '#3B82F6',
                        'secondary' => $settings['appearance.secondary_color'] ?? '#6B7280',
                    ],
                    'logo' => $settings['appearance.logo_url'] ?? '',
                ];
            });

        $this->mockConfigService->shouldReceive('testConnection')
            ->andReturnUsing(function ($type, $config) {
                // 模擬連線測試結果
                switch ($type) {
                    case 'smtp':
                        return !empty($config['host']) && !empty($config['username']);
                    case 'aws_s3':
                        return !empty($config['access_key']) && !empty($config['secret_key']);
                    case 'google_oauth':
                        return !empty($config['client_id']) && !empty($config['client_secret']);
                    default:
                        return false;
                }
            });

        // 綁定到容器
        $this->app->instance(SettingsRepositoryInterface::class, $this->mockRepository);
        $this->app->instance(ConfigurationService::class, $this->mockConfigService);
    }

    /** @test */
    public function 管理員可以存取設定預覽元件()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingPreview::class);

        $component->assertStatus(200)
                 ->assertSet('previewSettings', [])
                 ->assertSet('previewMode', 'theme')
                 ->assertSet('showPreview', false)
                 ->assertSet('connectionTests', [])
                 ->assertSet('testingConnections', [])
                 ->assertSet('impactAnalysis', []);
    }

    /** @test */
    public function 無權限使用者無法存取預覽元件()
    {
        $this->actingAs($this->regularUser);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        
        Livewire::test(SettingPreview::class);
    }

    /** @test */
    public function 可以開始設定預覽()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingPreview::class);

        $previewData = [
            'key' => 'appearance.primary_color',
            'value' => '#FF5733'
        ];

        $component->dispatch('setting-preview-start', $previewData)
                 ->assertSet('showPreview', true)
                 ->assertSet('previewMode', 'theme')
                 ->assertSet('previewSettings.appearance.primary_color', '#FF5733');

        // 檢查主題預覽資料是否更新
        $themePreview = $component->get('themePreview');
        $this->assertEquals('#FF5733', $themePreview['primary_color']);
    }

    /** @test */
    public function 可以更新預覽設定()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingPreview::class);

        // 先開始預覽
        $component->dispatch('setting-preview-start', [
            'key' => 'appearance.primary_color',
            'value' => '#3B82F6'
        ]);

        // 更新預覽
        $component->dispatch('setting-preview-update', [
            'key' => 'appearance.primary_color',
            'value' => '#FF5733'
        ]);

        $component->assertSet('previewSettings.appearance.primary_color', '#FF5733');

        // 檢查主題預覽資料是否更新
        $themePreview = $component->get('themePreview');
        $this->assertEquals('#FF5733', $themePreview['primary_color']);
    }

    /** @test */
    public function 可以停止預覽()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingPreview::class);

        // 先開始預覽
        $component->dispatch('setting-preview-start', [
            'key' => 'appearance.primary_color',
            'value' => '#FF5733'
        ]);

        // 停止預覽
        $component->dispatch('setting-preview-stop')
                 ->assertSet('showPreview', false)
                 ->assertSet('previewSettings', [])
                 ->assertSet('impactAnalysis', [])
                 ->assertSet('connectionTests', []);
    }

    /** @test */
    public function 可以切換預覽模式()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingPreview::class);

        // 切換到郵件預覽模式
        $component->call('switchPreviewMode', 'email')
                 ->assertSet('previewMode', 'email');

        // 切換到佈局預覽模式
        $component->call('switchPreviewMode', 'layout')
                 ->assertSet('previewMode', 'layout');

        // 切換到整合預覽模式
        $component->call('switchPreviewMode', 'integration')
                 ->assertSet('previewMode', 'integration');

        // 切換回主題預覽模式
        $component->call('switchPreviewMode', 'theme')
                 ->assertSet('previewMode', 'theme');
    }

    /** @test */
    public function 可以測試SMTP連線()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingPreview::class);

        // 設定 SMTP 預覽設定
        $component->set('previewSettings', [
            'notification.smtp_host' => 'smtp.gmail.com',
            'notification.smtp_port' => 587,
            'notification.smtp_username' => 'admin@example.com',
            'notification.smtp_password' => 'password123',
        ]);

        $component->call('testSmtpConnection');

        // 檢查連線測試結果
        $connectionTests = $component->get('connectionTests');
        $this->assertArrayHasKey('smtp', $connectionTests);
        $this->assertTrue($connectionTests['smtp']['success']);
        $this->assertStringContains('成功', $connectionTests['smtp']['message']);

        // 檢查是否有成功訊息
        $this->assertTrue($component->instance()->hasFlashMessage('success'));
    }

    /** @test */
    public function 可以測試AWS_S3連線()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingPreview::class);

        // 設定 AWS S3 預覽設定
        $component->set('previewSettings', [
            'integration.aws_access_key' => 'AKIAIOSFODNN7EXAMPLE',
            'integration.aws_secret_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'integration.aws_region' => 'us-east-1',
            'integration.aws_bucket' => 'my-bucket',
        ]);

        $component->call('testAwsS3Connection');

        // 檢查連線測試結果
        $connectionTests = $component->get('connectionTests');
        $this->assertArrayHasKey('aws_s3', $connectionTests);
        $this->assertTrue($connectionTests['aws_s3']['success']);
        $this->assertStringContains('成功', $connectionTests['aws_s3']['message']);

        // 檢查敏感資訊是否被隱藏
        $this->assertEquals('***', $connectionTests['aws_s3']['details']['secret_key']);

        // 檢查是否有成功訊息
        $this->assertTrue($component->instance()->hasFlashMessage('success'));
    }

    /** @test */
    public function 可以測試Google_OAuth連線()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingPreview::class);

        // 設定 Google OAuth 預覽設定
        $component->set('previewSettings', [
            'integration.google_client_id' => '123456789.apps.googleusercontent.com',
            'integration.google_client_secret' => 'GOCSPX-example_secret',
        ]);

        $component->call('testGoogleOAuthConnection');

        // 檢查連線測試結果
        $connectionTests = $component->get('connectionTests');
        $this->assertArrayHasKey('google_oauth', $connectionTests);
        $this->assertTrue($connectionTests['google_oauth']['success']);
        $this->assertStringContains('成功', $connectionTests['google_oauth']['message']);

        // 檢查敏感資訊是否被隱藏
        $this->assertEquals('***', $connectionTests['google_oauth']['details']['client_secret']);

        // 檢查是否有成功訊息
        $this->assertTrue($component->instance()->hasFlashMessage('success'));
    }

    /** @test */
    public function 連線測試失敗時顯示錯誤訊息()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingPreview::class);

        // 設定不完整的 SMTP 設定
        $component->set('previewSettings', [
            'notification.smtp_host' => '', // 空的主機
        ]);

        $component->call('testSmtpConnection');

        // 檢查連線測試結果
        $connectionTests = $component->get('connectionTests');
        $this->assertArrayHasKey('smtp', $connectionTests);
        $this->assertFalse($connectionTests['smtp']['success']);

        // 檢查是否有錯誤訊息
        $this->assertTrue($component->instance()->hasFlashMessage('error'));
    }

    /** @test */
    public function 可以測試所有連線()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingPreview::class);

        // 設定所有連線的預覽設定
        $component->set('previewSettings', [
            'notification.smtp_host' => 'smtp.gmail.com',
            'notification.smtp_username' => 'admin@example.com',
            'integration.aws_access_key' => 'AKIAIOSFODNN7EXAMPLE',
            'integration.aws_secret_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'integration.google_client_id' => '123456789.apps.googleusercontent.com',
            'integration.google_client_secret' => 'GOCSPX-example_secret',
        ]);

        $component->call('testAllConnections');

        // 檢查所有連線測試結果
        $connectionTests = $component->get('connectionTests');
        $this->assertArrayHasKey('smtp', $connectionTests);
        $this->assertArrayHasKey('aws_s3', $connectionTests);
        $this->assertArrayHasKey('google_oauth', $connectionTests);
    }

    /** @test */
    public function 影響分析功能正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingPreview::class);

        // 分析高影響設定
        $component->call('analyzeImpact', 'maintenance.maintenance_mode', true);

        $impactAnalysis = $component->get('impactAnalysis');
        $this->assertArrayHasKey('maintenance.maintenance_mode', $impactAnalysis);

        $impacts = $impactAnalysis['maintenance.maintenance_mode'];
        $this->assertNotEmpty($impacts);

        // 檢查是否有直接影響分析
        $directImpact = collect($impacts)->firstWhere('type', 'direct');
        $this->assertNotNull($directImpact);
        $this->assertEquals('high', $directImpact['severity']);
    }

    /** @test */
    public function 預覽CSS變數生成正確()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingPreview::class);

        // 設定顏色預覽設定
        $component->set('previewSettings', [
            'appearance.primary_color' => '#FF5733',
            'appearance.secondary_color' => '#33FF57',
        ]);

        $cssVariables = $component->get('previewCssVariables');
        
        $this->assertStringContains('--primary-color: #FF5733', $cssVariables);
        $this->assertStringContains('--secondary-color: #33FF57', $cssVariables);
    }

    /** @test */
    public function 預覽主題類別生成正確()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingPreview::class);

        // 測試亮色主題
        $component->set('previewSettings', [
            'appearance.default_theme' => 'light',
        ]);
        $this->assertEquals('theme-light', $component->get('previewThemeClass'));

        // 測試暗色主題
        $component->set('previewSettings', [
            'appearance.default_theme' => 'dark',
        ]);
        $this->assertEquals('theme-dark', $component->get('previewThemeClass'));

        // 測試自動主題
        $component->set('previewSettings', [
            'appearance.default_theme' => 'auto',
        ]);
        $this->assertEquals('theme-auto', $component->get('previewThemeClass'));
    }

    /** @test */
    public function 檢查是否有高影響變更()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingPreview::class);

        // 分析高影響設定
        $component->call('analyzeImpact', 'maintenance.maintenance_mode', true);

        $hasHighImpact = $component->get('hasHighImpactChanges');
        $this->assertTrue($hasHighImpact);
    }

    /** @test */
    public function 連線測試狀態檢查正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingPreview::class);

        // 初始狀態不應該在測試
        $this->assertFalse($component->instance()->isTestingConnection('smtp'));

        // 設定測試狀態
        $component->set('testingConnections.smtp', true);
        $this->assertTrue($component->instance()->isTestingConnection('smtp'));
    }

    /** @test */
    public function 取得連線測試結果正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingPreview::class);

        // 初始狀態沒有測試結果
        $this->assertNull($component->instance()->getConnectionTestResult('smtp'));

        // 設定測試結果
        $testResult = [
            'success' => true,
            'message' => '連線測試成功',
            'tested_at' => now()->format('Y-m-d H:i:s'),
        ];
        $component->set('connectionTests.smtp', $testResult);

        $result = $component->instance()->getConnectionTestResult('smtp');
        $this->assertEquals($testResult, $result);
    }

    /** @test */
    public function 取得影響分析結果正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingPreview::class);

        // 初始狀態沒有影響分析
        $this->assertEmpty($component->instance()->getImpactAnalysis('app.name'));

        // 分析設定影響
        $component->call('analyzeImpact', 'app.name', 'New App Name');

        $impacts = $component->instance()->getImpactAnalysis('app.name');
        $this->assertNotEmpty($impacts);
    }

    /** @test */
    public function 預覽模式自動切換正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingPreview::class);

        // 外觀設定應該切換到主題模式
        $component->dispatch('setting-preview-start', [
            'key' => 'appearance.primary_color',
            'value' => '#FF5733'
        ]);
        $component->assertSet('previewMode', 'theme');

        // 通知設定應該切換到郵件模式
        $component->dispatch('setting-preview-start', [
            'key' => 'notification.smtp_host',
            'value' => 'smtp.gmail.com'
        ]);
        $component->assertSet('previewMode', 'email');

        // 整合設定應該切換到整合模式
        $component->dispatch('setting-preview-start', [
            'key' => 'integration.aws_access_key',
            'value' => 'AKIAIOSFODNN7EXAMPLE'
        ]);
        $component->assertSet('previewMode', 'integration');
    }

    /** @test */
    public function 連線測試異常處理正常運作()
    {
        $this->actingAs($this->adminUser);

        // 設定連線測試異常的 Mock
        $this->mockConfigService->shouldReceive('testConnection')
            ->andThrow(new \Exception('Connection test error'));

        $component = Livewire::test(SettingPreview::class);

        $component->set('previewSettings', [
            'notification.smtp_host' => 'smtp.gmail.com',
            'notification.smtp_username' => 'admin@example.com',
        ]);

        $component->call('testSmtpConnection');

        // 檢查錯誤處理
        $connectionTests = $component->get('connectionTests');
        $this->assertArrayHasKey('smtp', $connectionTests);
        $this->assertFalse($connectionTests['smtp']['success']);
        $this->assertStringContains('錯誤', $connectionTests['smtp']['message']);

        // 檢查是否有錯誤訊息
        $this->assertTrue($component->instance()->hasFlashMessage('error'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}