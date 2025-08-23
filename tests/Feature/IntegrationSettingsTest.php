<?php

namespace Tests\Feature;

use App\Livewire\Admin\Settings\IntegrationSettings;
use App\Models\User;
use App\Services\EncryptionService;
use App\Contracts\SettingsRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 整合設定功能測試
 */
class IntegrationSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected SettingsRepositoryInterface $settingsRepository;
    protected EncryptionService $encryptionService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->settingsRepository = app(SettingsRepositoryInterface::class);
        $this->encryptionService = app(EncryptionService::class);
        
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_render_integration_settings_component()
    {
        Livewire::test(IntegrationSettings::class)
            ->assertStatus(200)
            ->assertSee('整合設定管理')
            ->assertSee('分析工具')
            ->assertSee('社群登入')
            ->assertSee('雲端儲存')
            ->assertSee('支付閘道')
            ->assertSee('API 金鑰');
    }

    /** @test */
    public function it_can_switch_between_tabs()
    {
        Livewire::test(IntegrationSettings::class)
            ->assertSet('activeTab', 'analytics')
            ->call('switchTab', 'social')
            ->assertSet('activeTab', 'social')
            ->call('switchTab', 'storage')
            ->assertSet('activeTab', 'storage')
            ->call('switchTab', 'payment')
            ->assertSet('activeTab', 'payment')
            ->call('switchTab', 'api')
            ->assertSet('activeTab', 'api');
    }

    /** @test */
    public function it_can_save_analytics_settings()
    {
        Livewire::test(IntegrationSettings::class)
            ->set('analyticsSettings.google_analytics_id', 'G-XXXXXXXXXX')
            ->set('analyticsSettings.google_tag_manager_id', 'GTM-XXXXXXX')
            ->call('saveAnalyticsSettings')
            ->assertHasNoErrors()
            ->assertSet('successMessage', '分析工具設定已成功儲存');

        // 驗證設定已儲存
        $this->assertEquals('G-XXXXXXXXXX', $this->settingsRepository->getCachedSetting('integration.google_analytics_id'));
        $this->assertEquals('GTM-XXXXXXX', $this->settingsRepository->getCachedSetting('integration.google_tag_manager_id'));
    }

    /** @test */
    public function it_validates_analytics_settings_format()
    {
        Livewire::test(IntegrationSettings::class)
            ->set('analyticsSettings.google_analytics_id', 'invalid-format')
            ->set('analyticsSettings.google_tag_manager_id', 'invalid-format')
            ->call('saveAnalyticsSettings')
            ->assertHasErrors();
    }

    /** @test */
    public function it_can_save_social_login_settings()
    {
        Livewire::test(IntegrationSettings::class)
            ->set('socialLoginSettings.google_oauth_enabled', true)
            ->set('socialLoginSettings.google_client_id', 'test-client-id')
            ->set('socialLoginSettings.google_client_secret', 'test-client-secret')
            ->call('saveSocialLoginSettings')
            ->assertHasNoErrors()
            ->assertSet('successMessage', '社群媒體登入設定已成功儲存');

        // 驗證設定已儲存且敏感資料已加密
        $this->assertTrue($this->settingsRepository->getCachedSetting('integration.google_oauth_enabled'));
        $this->assertEquals('test-client-id', $this->settingsRepository->getCachedSetting('integration.google_client_id'));
        
        // 驗證 client_secret 已加密
        $encryptedSecret = $this->settingsRepository->getSetting('integration.google_client_secret')->value;
        $this->assertNotEquals('test-client-secret', $encryptedSecret);
        $this->assertEquals('test-client-secret', $this->settingsRepository->getDecryptedSetting('integration.google_client_secret'));
    }

    /** @test */
    public function it_validates_social_login_dependencies()
    {
        // 測試啟用 Google OAuth 但未提供必要資訊
        Livewire::test(IntegrationSettings::class)
            ->set('socialLoginSettings.google_oauth_enabled', true)
            ->set('socialLoginSettings.google_client_id', '')
            ->set('socialLoginSettings.google_client_secret', '')
            ->call('saveSocialLoginSettings')
            ->assertHasErrors();
    }

    /** @test */
    public function it_can_save_cloud_storage_settings()
    {
        Livewire::test(IntegrationSettings::class)
            ->set('cloudStorageSettings.aws_s3_enabled', true)
            ->set('cloudStorageSettings.aws_access_key', 'test-access-key')
            ->set('cloudStorageSettings.aws_secret_key', 'test-secret-key')
            ->set('cloudStorageSettings.aws_region', 'ap-northeast-1')
            ->set('cloudStorageSettings.aws_bucket', 'test-bucket')
            ->call('saveCloudStorageSettings')
            ->assertHasNoErrors()
            ->assertSet('successMessage', '雲端儲存設定已成功儲存');

        // 驗證敏感資料已加密
        $this->assertEquals('test-access-key', $this->settingsRepository->getDecryptedSetting('integration.aws_access_key'));
        $this->assertEquals('test-secret-key', $this->settingsRepository->getDecryptedSetting('integration.aws_secret_key'));
    }

    /** @test */
    public function it_can_save_payment_gateway_settings()
    {
        Livewire::test(IntegrationSettings::class)
            ->set('paymentGatewaySettings.stripe_enabled', true)
            ->set('paymentGatewaySettings.stripe_publishable_key', 'pk_test_123')
            ->set('paymentGatewaySettings.stripe_secret_key', 'sk_test_123')
            ->set('paymentGatewaySettings.stripe_webhook_secret', 'whsec_test_123')
            ->call('savePaymentGatewaySettings')
            ->assertHasNoErrors()
            ->assertSet('successMessage', '支付閘道設定已成功儲存');

        // 驗證敏感資料已加密
        $this->assertEquals('sk_test_123', $this->settingsRepository->getDecryptedSetting('integration.stripe_secret_key'));
        $this->assertEquals('whsec_test_123', $this->settingsRepository->getDecryptedSetting('integration.stripe_webhook_secret'));
    }

    /** @test */
    public function it_can_add_custom_api_key()
    {
        Livewire::test(IntegrationSettings::class)
            ->set('newApiKey.name', 'OpenAI API')
            ->set('newApiKey.key', 'sk-test123456789')
            ->set('newApiKey.description', 'OpenAI API 金鑰用於 AI 功能')
            ->call('addApiKey')
            ->assertHasNoErrors()
            ->assertSet('successMessage', 'API 金鑰已成功新增')
            ->assertSet('showApiKeyModal', false);

        // 驗證 API 金鑰已加密儲存
        $apiKeys = $this->settingsRepository->getDecryptedSetting('integration.api_keys', []);
        $this->assertCount(1, $apiKeys);
        $this->assertEquals('OpenAI API', $apiKeys[0]['name']);
        $this->assertEquals('sk-test123456789', $apiKeys[0]['key']);
        $this->assertEquals('OpenAI API 金鑰用於 AI 功能', $apiKeys[0]['description']);
    }

    /** @test */
    public function it_validates_api_key_input()
    {
        // 測試空的 API 金鑰名稱
        Livewire::test(IntegrationSettings::class)
            ->set('newApiKey.name', '')
            ->set('newApiKey.key', 'sk-test123456789')
            ->call('addApiKey')
            ->assertHasErrors();

        // 測試空的 API 金鑰
        Livewire::test(IntegrationSettings::class)
            ->set('newApiKey.name', 'Test API')
            ->set('newApiKey.key', '')
            ->call('addApiKey')
            ->assertHasErrors();
    }

    /** @test */
    public function it_can_remove_custom_api_key()
    {
        // 先新增一個 API 金鑰
        $component = Livewire::test(IntegrationSettings::class)
            ->set('newApiKey.name', 'Test API')
            ->set('newApiKey.key', 'sk-test123456789')
            ->call('addApiKey');

        // 然後刪除它
        $component->call('removeApiKey', 0)
            ->assertHasNoErrors()
            ->assertSet('successMessage', 'API 金鑰「Test API」已成功刪除');

        // 驗證 API 金鑰已刪除
        $apiKeys = $this->settingsRepository->getDecryptedSetting('integration.api_keys', []);
        $this->assertCount(0, $apiKeys);
    }

    /** @test */
    public function it_can_test_google_oauth_integration()
    {
        Http::fake([
            'https://accounts.google.com/.well-known/openid_configuration' => Http::response(['issuer' => 'https://accounts.google.com'], 200),
        ]);

        Livewire::test(IntegrationSettings::class)
            ->set('socialLoginSettings.google_oauth_enabled', true)
            ->set('socialLoginSettings.google_client_id', 'test-client-id')
            ->set('socialLoginSettings.google_client_secret', 'test-client-secret')
            ->call('testIntegration', 'google_oauth')
            ->assertSet('testResults.google_oauth.success', true)
            ->assertSet('testResults.google_oauth.message', 'Google OAuth 連線測試成功');
    }

    /** @test */
    public function it_can_test_stripe_integration()
    {
        Http::fake([
            'https://api.stripe.com/v1/account' => Http::response(['id' => 'acct_test'], 200),
        ]);

        Livewire::test(IntegrationSettings::class)
            ->set('paymentGatewaySettings.stripe_enabled', true)
            ->set('paymentGatewaySettings.stripe_secret_key', 'sk_test_123')
            ->call('testIntegration', 'stripe')
            ->assertSet('testResults.stripe.success', true)
            ->assertSet('testResults.stripe.message', 'Stripe 連線測試成功');
    }

    /** @test */
    public function it_handles_failed_integration_tests()
    {
        Http::fake([
            'https://accounts.google.com/.well-known/openid_configuration' => Http::response([], 500),
        ]);

        Livewire::test(IntegrationSettings::class)
            ->set('socialLoginSettings.google_oauth_enabled', true)
            ->set('socialLoginSettings.google_client_id', 'test-client-id')
            ->set('socialLoginSettings.google_client_secret', 'test-client-secret')
            ->call('testIntegration', 'google_oauth')
            ->assertSet('testResults.google_oauth.success', false);
    }

    /** @test */
    public function it_prevents_duplicate_api_key_names()
    {
        // 先新增一個 API 金鑰
        Livewire::test(IntegrationSettings::class)
            ->set('newApiKey.name', 'Test API')
            ->set('newApiKey.key', 'sk-test123456789')
            ->call('addApiKey');

        // 嘗試新增相同名稱的 API 金鑰
        Livewire::test(IntegrationSettings::class)
            ->set('newApiKey.name', 'Test API')
            ->set('newApiKey.key', 'sk-another123456789')
            ->call('addApiKey')
            ->assertHasErrors();
    }

    /** @test */
    public function it_clears_messages_when_switching_tabs()
    {
        Livewire::test(IntegrationSettings::class)
            ->set('successMessage', 'Test message')
            ->set('errors', ['test' => ['Test error']])
            ->call('switchTab', 'social')
            ->assertSet('successMessage', '')
            ->assertSet('errors', []);
    }

    /** @test */
    public function it_loads_existing_settings_on_mount()
    {
        // 預先設定一些值
        $this->settingsRepository->updateSetting('integration.google_analytics_id', 'G-TEST123');
        $this->settingsRepository->updateSetting('integration.google_oauth_enabled', true);

        $component = Livewire::test(IntegrationSettings::class);

        $this->assertEquals('G-TEST123', $component->get('analyticsSettings.google_analytics_id'));
        $this->assertTrue($component->get('socialLoginSettings.google_oauth_enabled'));
    }
}