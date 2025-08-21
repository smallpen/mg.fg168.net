<?php

namespace Tests\Feature;

use App\Livewire\Admin\Settings\SettingPreview;
use App\Models\Setting;
use App\Models\User;
use App\Services\ConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 設定預覽功能測試
 */
class SettingPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立管理員使用者
        $this->adminUser = User::factory()->create([
            'username' => 'admin',
            'is_active' => true,
        ]);
        
        // 建立測試設定
        Setting::factory()->create([
            'key' => 'appearance.primary_color',
            'value' => '#3B82F6',
            'category' => 'appearance',
            'type' => 'color',
            'description' => '主要顏色',
        ]);
        
        Setting::factory()->create([
            'key' => 'notification.smtp_host',
            'value' => 'smtp.gmail.com',
            'category' => 'notification',
            'type' => 'text',
            'description' => 'SMTP 主機',
        ]);
    }

    /** @test */
    public function it_can_render_setting_preview_component()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(SettingPreview::class)
            ->assertStatus(200)
            ->assertSet('showPreview', false)
            ->assertSet('previewSettings', []);
    }

    /** @test */
    public function it_can_start_preview_for_appearance_settings()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingPreview::class)
            ->call('startPreview', [
                'key' => 'appearance.primary_color',
                'value' => '#FF0000'
            ])
            ->assertSet('showPreview', true)
            ->assertSet('previewMode', 'theme');
            
        $previewSettings = $component->get('previewSettings');
        $this->assertArrayHasKey('appearance.primary_color', $previewSettings);
        $this->assertEquals('#FF0000', $previewSettings['appearance.primary_color']);
    }

    /** @test */
    public function it_can_switch_preview_modes()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(SettingPreview::class)
            ->call('switchPreviewMode', 'email')
            ->assertSet('previewMode', 'email')
            ->call('switchPreviewMode', 'integration')
            ->assertSet('previewMode', 'integration');
    }

    /** @test */
    public function it_can_analyze_setting_impact()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingPreview::class);
        
        $component->call('analyzeImpact', 'appearance.primary_color', '#FF0000');
        
        $impactAnalysis = $component->get('impactAnalysis');
        $this->assertArrayHasKey('appearance.primary_color', $impactAnalysis);
        $this->assertNotEmpty($impactAnalysis['appearance.primary_color']);
    }

    /** @test */
    public function it_can_generate_preview_css_variables()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingPreview::class);
        
        $component->set('previewSettings', [
            'appearance.primary_color' => '#FF0000',
            'appearance.secondary_color' => '#00FF00'
        ]);
        
        $cssVariables = $component->get('previewCssVariables');
        $this->assertStringContainsString('--primary-color: #FF0000', $cssVariables);
        $this->assertStringContainsString('--secondary-color: #00FF00', $cssVariables);
    }

    /** @test */
    public function it_can_collect_smtp_settings()
    {
        $this->actingAs($this->adminUser);

        // 建立更多 SMTP 設定
        Setting::factory()->create([
            'key' => 'notification.smtp_port',
            'value' => '587',
            'category' => 'notification',
            'type' => 'number',
        ]);

        Setting::factory()->create([
            'key' => 'notification.smtp_encryption',
            'value' => 'tls',
            'category' => 'notification',
            'type' => 'select',
        ]);

        $component = new SettingPreview();
        $reflection = new \ReflectionClass($component);
        $method = $reflection->getMethod('collectSmtpSettings');
        $method->setAccessible(true);
        
        $smtpSettings = $method->invoke($component);
        
        $this->assertArrayHasKey('host', $smtpSettings);
        $this->assertEquals('smtp.gmail.com', $smtpSettings['host']);
        $this->assertArrayHasKey('port', $smtpSettings);
        $this->assertEquals('587', $smtpSettings['port']);
    }

    /** @test */
    public function it_can_determine_impact_severity()
    {
        $this->actingAs($this->adminUser);

        $component = new SettingPreview();
        $reflection = new \ReflectionClass($component);
        $method = $reflection->getMethod('getImpactSeverity');
        $method->setAccessible(true);
        
        // 測試高影響設定
        $severity = $method->invoke($component, 'maintenance.maintenance_mode');
        $this->assertEquals('high', $severity);
        
        // 測試中等影響設定
        $severity = $method->invoke($component, 'security.password_min_length');
        $this->assertEquals('medium', $severity);
        
        // 測試低影響設定
        $severity = $method->invoke($component, 'appearance.primary_color');
        $this->assertEquals('low', $severity);
    }

    /** @test */
    public function it_can_stop_preview()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(SettingPreview::class)
            ->call('startPreview', [
                'key' => 'appearance.primary_color',
                'value' => '#FF0000'
            ])
            ->assertSet('showPreview', true)
            ->call('stopPreview')
            ->assertSet('showPreview', false)
            ->assertSet('previewSettings', [])
            ->assertSet('impactAnalysis', []);
    }

    /** @test */
    public function it_can_detect_high_impact_changes()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingPreview::class);
        
        // 添加高影響設定變更
        $component->call('analyzeImpact', 'maintenance.maintenance_mode', true);
        
        $hasHighImpact = $component->get('hasHighImpactChanges');
        $this->assertTrue($hasHighImpact);
    }

    /** @test */
    public function configuration_service_can_test_connections()
    {
        $configService = app(ConfigurationService::class);
        
        // 測試無效的 SMTP 配置（應該返回 false）
        $result = $configService->testConnection('smtp', [
            'host' => 'invalid.smtp.server',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'test@example.com',
            'password' => 'invalid_password',
        ]);
        
        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_find_dependent_settings()
    {
        $this->actingAs($this->adminUser);

        $component = new SettingPreview();
        $reflection = new \ReflectionClass($component);
        $method = $reflection->getMethod('findDependentSettings');
        $method->setAccessible(true);
        
        // 測試尋找依賴於 notification.email_enabled 的設定
        $dependentSettings = $method->invoke($component, 'notification.email_enabled');
        
        // 根據配置，應該有多個設定依賴於 email_enabled
        $this->assertIsArray($dependentSettings);
    }
}