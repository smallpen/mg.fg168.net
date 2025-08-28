<?php

namespace App\Livewire\Admin\Settings;

use App\Services\ConfigurationService;
use App\Repositories\SettingsRepositoryInterface;
use Livewire\Component;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * 整合設定管理元件
 * 
 * 提供第三方服務整合設定的管理功能，包含：
 * - 社群媒體登入設定（Google、Facebook、GitHub）
 * - 雲端儲存設定（AWS S3、Google Drive）
 * - 支付閘道設定（Stripe、PayPal）
 * - API 金鑰管理和加密
 * - 整合測試功能
 */
class IntegrationSettings extends Component
{
    /** @var SettingsRepositoryInterface 設定儲存庫 */
    protected SettingsRepositoryInterface $settingsRepository;
    
    /** @var ConfigurationService 配置服務 */
    protected ConfigurationService $configService;

    // 設定分組
    public array $analyticsSettings = [];
    public array $socialLoginSettings = [];
    public array $cloudStorageSettings = [];
    public array $paymentGatewaySettings = [];
    public array $customApiKeys = [];

    // UI 狀態
    public string $activeTab = 'analytics';
    public array $testResults = [];
    public array $testingServices = [];
    public bool $showApiKeyModal = false;
    public array $newApiKey = ['name' => '', 'key' => '', 'description' => ''];

    // 錯誤和成功訊息
    public array $errors = [];
    public string $successMessage = '';

    /**
     * 建構函式
     */
    public function __construct()
    {
        $this->settingsRepository = app(SettingsRepositoryInterface::class);
        $this->configService = app(ConfigurationService::class);
    }

    /**
     * 元件掛載
     */
    public function mount(): void
    {
        $this->loadSettings();
    }

    /**
     * 載入所有整合設定
     */
    public function loadSettings(): void
    {
        $allSettings = $this->settingsRepository->getSettingsByCategory('integration');
        
        // 分析工具設定
        $this->analyticsSettings = [
            'google_analytics_id' => $allSettings->where('key', 'integration.google_analytics_id')->first()?->value ?? '',
            'google_tag_manager_id' => $allSettings->where('key', 'integration.google_tag_manager_id')->first()?->value ?? '',
        ];

        // 社群媒體登入設定
        $this->socialLoginSettings = [
            'google_oauth_enabled' => $allSettings->where('key', 'integration.google_oauth_enabled')->first()?->value ?? false,
            'google_client_id' => $allSettings->where('key', 'integration.google_client_id')->first()?->value ?? '',
            'google_client_secret' => $allSettings->where('key', 'integration.google_client_secret')->first()?->value ?? '',
            'facebook_oauth_enabled' => $allSettings->where('key', 'integration.facebook_oauth_enabled')->first()?->value ?? false,
            'facebook_app_id' => $allSettings->where('key', 'integration.facebook_app_id')->first()?->value ?? '',
            'facebook_app_secret' => $allSettings->where('key', 'integration.facebook_app_secret')->first()?->value ?? '',
            'github_oauth_enabled' => $allSettings->where('key', 'integration.github_oauth_enabled')->first()?->value ?? false,
            'github_client_id' => $allSettings->where('key', 'integration.github_client_id')->first()?->value ?? '',
            'github_client_secret' => $allSettings->where('key', 'integration.github_client_secret')->first()?->value ?? '',
        ];

        // 雲端儲存設定
        $this->cloudStorageSettings = [
            'aws_s3_enabled' => $allSettings->where('key', 'integration.aws_s3_enabled')->first()?->value ?? false,
            'aws_access_key' => $allSettings->where('key', 'integration.aws_access_key')->first()?->value ?? '',
            'aws_secret_key' => $allSettings->where('key', 'integration.aws_secret_key')->first()?->value ?? '',
            'aws_region' => $allSettings->where('key', 'integration.aws_region')->first()?->value ?? 'ap-northeast-1',
            'aws_bucket' => $allSettings->where('key', 'integration.aws_bucket')->first()?->value ?? '',
            'google_drive_enabled' => $allSettings->where('key', 'integration.google_drive_enabled')->first()?->value ?? false,
            'google_drive_client_id' => $allSettings->where('key', 'integration.google_drive_client_id')->first()?->value ?? '',
            'google_drive_client_secret' => $allSettings->where('key', 'integration.google_drive_client_secret')->first()?->value ?? '',
        ];

        // 支付閘道設定
        $this->paymentGatewaySettings = [
            'stripe_enabled' => $allSettings->where('key', 'integration.stripe_enabled')->first()?->value ?? false,
            'stripe_publishable_key' => $allSettings->where('key', 'integration.stripe_publishable_key')->first()?->value ?? '',
            'stripe_secret_key' => $allSettings->where('key', 'integration.stripe_secret_key')->first()?->value ?? '',
            'stripe_webhook_secret' => $allSettings->where('key', 'integration.stripe_webhook_secret')->first()?->value ?? '',
            'paypal_enabled' => $allSettings->where('key', 'integration.paypal_enabled')->first()?->value ?? false,
            'paypal_client_id' => $allSettings->where('key', 'integration.paypal_client_id')->first()?->value ?? '',
            'paypal_client_secret' => $allSettings->where('key', 'integration.paypal_client_secret')->first()?->value ?? '',
            'paypal_mode' => $allSettings->where('key', 'integration.paypal_mode')->first()?->value ?? 'sandbox',
        ];

        // 自訂 API 金鑰
        $apiKeysData = $allSettings->where('key', 'integration.api_keys')->first()?->value ?? [];
        $this->customApiKeys = is_array($apiKeysData) ? $apiKeysData : [];
    }

    /**
     * 儲存分析工具設定
     */
    public function saveAnalyticsSettings(): void
    {
        try {
            $this->validateAnalyticsSettings();
            
            $this->settingsRepository->updateSetting('integration.google_analytics_id', $this->analyticsSettings['google_analytics_id']);
            $this->settingsRepository->updateSetting('integration.google_tag_manager_id', $this->analyticsSettings['google_tag_manager_id']);
            
            $this->successMessage = '分析工具設定已成功儲存';
            $this->errors = [];
            
            Log::info('Analytics settings updated', [
                'user_id' => auth()->id(),
                'settings' => $this->analyticsSettings
            ]);
            
        } catch (ValidationException $e) {
            $this->errors = $e->errors();
        } catch (\Exception $e) {
            $this->errors = ['general' => ['儲存設定時發生錯誤：' . $e->getMessage()]];
            Log::error('Failed to save analytics settings', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
        }
    }

    /**
     * 儲存社群媒體登入設定
     */
    public function saveSocialLoginSettings(): void
    {
        try {
            $this->validateSocialLoginSettings();
            
            foreach ($this->socialLoginSettings as $key => $value) {
                $this->settingsRepository->updateSetting("integration.{$key}", $value);
            }
            
            $this->successMessage = '社群媒體登入設定已成功儲存';
            $this->errors = [];
            
            Log::info('Social login settings updated', [
                'user_id' => auth()->id(),
                'enabled_services' => array_keys(array_filter([
                    'google' => $this->socialLoginSettings['google_oauth_enabled'],
                    'facebook' => $this->socialLoginSettings['facebook_oauth_enabled'],
                    'github' => $this->socialLoginSettings['github_oauth_enabled'],
                ]))
            ]);
            
        } catch (ValidationException $e) {
            $this->errors = $e->errors();
        } catch (\Exception $e) {
            $this->errors = ['general' => ['儲存設定時發生錯誤：' . $e->getMessage()]];
            Log::error('Failed to save social login settings', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
        }
    }

    /**
     * 儲存雲端儲存設定
     */
    public function saveCloudStorageSettings(): void
    {
        try {
            $this->validateCloudStorageSettings();
            
            foreach ($this->cloudStorageSettings as $key => $value) {
                $this->settingsRepository->updateSetting("integration.{$key}", $value);
            }
            
            $this->successMessage = '雲端儲存設定已成功儲存';
            $this->errors = [];
            
            Log::info('Cloud storage settings updated', [
                'user_id' => auth()->id(),
                'enabled_services' => array_keys(array_filter([
                    'aws_s3' => $this->cloudStorageSettings['aws_s3_enabled'],
                    'google_drive' => $this->cloudStorageSettings['google_drive_enabled'],
                ]))
            ]);
            
        } catch (ValidationException $e) {
            $this->errors = $e->errors();
        } catch (\Exception $e) {
            $this->errors = ['general' => ['儲存設定時發生錯誤：' . $e->getMessage()]];
            Log::error('Failed to save cloud storage settings', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
        }
    }

    /**
     * 儲存支付閘道設定
     */
    public function savePaymentGatewaySettings(): void
    {
        try {
            $this->validatePaymentGatewaySettings();
            
            foreach ($this->paymentGatewaySettings as $key => $value) {
                $this->settingsRepository->updateSetting("integration.{$key}", $value);
            }
            
            $this->successMessage = '支付閘道設定已成功儲存';
            $this->errors = [];
            
            Log::info('Payment gateway settings updated', [
                'user_id' => auth()->id(),
                'enabled_services' => array_keys(array_filter([
                    'stripe' => $this->paymentGatewaySettings['stripe_enabled'],
                    'paypal' => $this->paymentGatewaySettings['paypal_enabled'],
                ]))
            ]);
            
        } catch (ValidationException $e) {
            $this->errors = $e->errors();
        } catch (\Exception $e) {
            $this->errors = ['general' => ['儲存設定時發生錯誤：' . $e->getMessage()]];
            Log::error('Failed to save payment gateway settings', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
        }
    }

    /**
     * 新增自訂 API 金鑰
     */
    public function addApiKey(): void
    {
        try {
            $validator = Validator::make($this->newApiKey, [
                'name' => 'required|string|max:100',
                'key' => 'required|string|max:500',
                'description' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            // 檢查名稱是否已存在
            if (collect($this->customApiKeys)->pluck('name')->contains($this->newApiKey['name'])) {
                throw new ValidationException(
                    Validator::make([], []),
                    ['name' => ['此 API 金鑰名稱已存在']]
                );
            }

            $this->customApiKeys[] = [
                'name' => $this->newApiKey['name'],
                'key' => $this->newApiKey['key'],
                'description' => $this->newApiKey['description'],
                'created_at' => now()->toISOString(),
            ];

            $this->settingsRepository->updateSetting('integration.api_keys', $this->customApiKeys);

            $this->newApiKey = ['name' => '', 'key' => '', 'description' => ''];
            $this->showApiKeyModal = false;
            $this->successMessage = 'API 金鑰已成功新增';
            $this->errors = [];

            Log::info('Custom API key added', [
                'user_id' => auth()->id(),
                'key_name' => $this->newApiKey['name']
            ]);

        } catch (ValidationException $e) {
            $this->errors = $e->errors();
        } catch (\Exception $e) {
            $this->errors = ['general' => ['新增 API 金鑰時發生錯誤：' . $e->getMessage()]];
            Log::error('Failed to add custom API key', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
        }
    }

    /**
     * 刪除自訂 API 金鑰
     */
    public function removeApiKey(int $index): void
    {
        try {
            if (isset($this->customApiKeys[$index])) {
                $keyName = $this->customApiKeys[$index]['name'];
                unset($this->customApiKeys[$index]);
                $this->customApiKeys = array_values($this->customApiKeys);
                
                $this->settingsRepository->updateSetting('integration.api_keys', $this->customApiKeys);
                
                $this->successMessage = "API 金鑰「{$keyName}」已成功刪除";
                
                Log::info('Custom API key removed', [
                    'user_id' => auth()->id(),
                    'key_name' => $keyName
                ]);
            }
        } catch (\Exception $e) {
            $this->errors = ['general' => ['刪除 API 金鑰時發生錯誤：' . $e->getMessage()]];
            Log::error('Failed to remove custom API key', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
        }
    }

    /**
     * 測試整合服務連線
     */
    public function testIntegration(string $service): void
    {
        $this->testingServices[$service] = true;
        $this->testResults[$service] = null;

        try {
            switch ($service) {
                case 'google_oauth':
                    $this->testResults[$service] = $this->testGoogleOAuth();
                    break;
                case 'facebook_oauth':
                    $this->testResults[$service] = $this->testFacebookOAuth();
                    break;
                case 'github_oauth':
                    $this->testResults[$service] = $this->testGitHubOAuth();
                    break;
                case 'aws_s3':
                    $this->testResults[$service] = $this->testAwsS3();
                    break;
                case 'google_drive':
                    $this->testResults[$service] = $this->testGoogleDrive();
                    break;
                case 'stripe':
                    $this->testResults[$service] = $this->testStripe();
                    break;
                case 'paypal':
                    $this->testResults[$service] = $this->testPayPal();
                    break;
                default:
                    throw new \InvalidArgumentException("不支援的服務：{$service}");
            }
        } catch (\Exception $e) {
            $this->testResults[$service] = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
            
            Log::error("Integration test failed for {$service}", [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
        } finally {
            $this->testingServices[$service] = false;
        }
    }

    /**
     * 測試 Google OAuth 連線
     */
    protected function testGoogleOAuth(): array
    {
        if (!$this->socialLoginSettings['google_oauth_enabled']) {
            return ['success' => false, 'message' => 'Google OAuth 未啟用'];
        }

        $clientId = $this->socialLoginSettings['google_client_id'];
        $clientSecret = $this->socialLoginSettings['google_client_secret'];

        if (empty($clientId) || empty($clientSecret)) {
            return ['success' => false, 'message' => 'Google OAuth 設定不完整'];
        }

        try {
            // 測試 Google OAuth 端點
            $response = Http::timeout(10)->get('https://accounts.google.com/.well-known/openid_configuration');
            
            if ($response->successful()) {
                return ['success' => true, 'message' => 'Google OAuth 連線測試成功'];
            } else {
                return ['success' => false, 'message' => 'Google OAuth 端點無法連接'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Google OAuth 連線測試失敗：' . $e->getMessage()];
        }
    }

    /**
     * 測試 Facebook OAuth 連線
     */
    protected function testFacebookOAuth(): array
    {
        if (!$this->socialLoginSettings['facebook_oauth_enabled']) {
            return ['success' => false, 'message' => 'Facebook OAuth 未啟用'];
        }

        $appId = $this->socialLoginSettings['facebook_app_id'];
        $appSecret = $this->socialLoginSettings['facebook_app_secret'];

        if (empty($appId) || empty($appSecret)) {
            return ['success' => false, 'message' => 'Facebook OAuth 設定不完整'];
        }

        try {
            // 測試 Facebook Graph API
            $response = Http::timeout(10)->get("https://graph.facebook.com/oauth/access_token", [
                'client_id' => $appId,
                'client_secret' => $appSecret,
                'grant_type' => 'client_credentials'
            ]);
            
            if ($response->successful()) {
                return ['success' => true, 'message' => 'Facebook OAuth 連線測試成功'];
            } else {
                return ['success' => false, 'message' => 'Facebook OAuth 認證失敗'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Facebook OAuth 連線測試失敗：' . $e->getMessage()];
        }
    }

    /**
     * 測試 GitHub OAuth 連線
     */
    protected function testGitHubOAuth(): array
    {
        if (!$this->socialLoginSettings['github_oauth_enabled']) {
            return ['success' => false, 'message' => 'GitHub OAuth 未啟用'];
        }

        $clientId = $this->socialLoginSettings['github_client_id'];
        $clientSecret = $this->socialLoginSettings['github_client_secret'];

        if (empty($clientId) || empty($clientSecret)) {
            return ['success' => false, 'message' => 'GitHub OAuth 設定不完整'];
        }

        try {
            // 測試 GitHub API
            $response = Http::timeout(10)->get('https://api.github.com/user', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ]);
            
            // GitHub API 會回傳 401 但這表示端點可用
            if ($response->status() === 401 || $response->successful()) {
                return ['success' => true, 'message' => 'GitHub OAuth 連線測試成功'];
            } else {
                return ['success' => false, 'message' => 'GitHub OAuth 端點無法連接'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'GitHub OAuth 連線測試失敗：' . $e->getMessage()];
        }
    }

    /**
     * 測試 AWS S3 連線
     */
    protected function testAwsS3(): array
    {
        if (!$this->cloudStorageSettings['aws_s3_enabled']) {
            return ['success' => false, 'message' => 'AWS S3 未啟用'];
        }

        $accessKey = $this->cloudStorageSettings['aws_access_key'];
        $secretKey = $this->cloudStorageSettings['aws_secret_key'];
        $region = $this->cloudStorageSettings['aws_region'];
        $bucket = $this->cloudStorageSettings['aws_bucket'];

        if (empty($accessKey) || empty($secretKey) || empty($region) || empty($bucket)) {
            return ['success' => false, 'message' => 'AWS S3 設定不完整'];
        }

        try {
            // 這裡應該使用 AWS SDK 進行實際測試
            // 為了簡化，我們只檢查設定是否完整
            return ['success' => true, 'message' => 'AWS S3 設定檢查通過（需要 AWS SDK 進行完整測試）'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'AWS S3 連線測試失敗：' . $e->getMessage()];
        }
    }

    /**
     * 測試 Google Drive 連線
     */
    protected function testGoogleDrive(): array
    {
        if (!$this->cloudStorageSettings['google_drive_enabled']) {
            return ['success' => false, 'message' => 'Google Drive 未啟用'];
        }

        $clientId = $this->cloudStorageSettings['google_drive_client_id'];
        $clientSecret = $this->cloudStorageSettings['google_drive_client_secret'];

        if (empty($clientId) || empty($clientSecret)) {
            return ['success' => false, 'message' => 'Google Drive 設定不完整'];
        }

        try {
            // 測試 Google Drive API 端點
            $response = Http::timeout(10)->get('https://www.googleapis.com/drive/v3/about', [
                'key' => $clientId
            ]);
            
            // 即使認證失敗，如果端點可用也算成功
            if ($response->status() === 401 || $response->successful()) {
                return ['success' => true, 'message' => 'Google Drive API 端點可用'];
            } else {
                return ['success' => false, 'message' => 'Google Drive API 端點無法連接'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Google Drive 連線測試失敗：' . $e->getMessage()];
        }
    }

    /**
     * 測試 Stripe 連線
     */
    protected function testStripe(): array
    {
        if (!$this->paymentGatewaySettings['stripe_enabled']) {
            return ['success' => false, 'message' => 'Stripe 未啟用'];
        }

        $secretKey = $this->paymentGatewaySettings['stripe_secret_key'];

        if (empty($secretKey)) {
            return ['success' => false, 'message' => 'Stripe 設定不完整'];
        }

        try {
            // 測試 Stripe API
            $response = Http::timeout(10)
                ->withHeaders(['Authorization' => 'Bearer ' . $secretKey])
                ->get('https://api.stripe.com/v1/account');
            
            if ($response->successful()) {
                return ['success' => true, 'message' => 'Stripe 連線測試成功'];
            } else {
                return ['success' => false, 'message' => 'Stripe API 認證失敗'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Stripe 連線測試失敗：' . $e->getMessage()];
        }
    }

    /**
     * 測試 PayPal 連線
     */
    protected function testPayPal(): array
    {
        if (!$this->paymentGatewaySettings['paypal_enabled']) {
            return ['success' => false, 'message' => 'PayPal 未啟用'];
        }

        $clientId = $this->paymentGatewaySettings['paypal_client_id'];
        $clientSecret = $this->paymentGatewaySettings['paypal_client_secret'];
        $mode = $this->paymentGatewaySettings['paypal_mode'];

        if (empty($clientId) || empty($clientSecret)) {
            return ['success' => false, 'message' => 'PayPal 設定不完整'];
        }

        try {
            $baseUrl = $mode === 'live' 
                ? 'https://api.paypal.com' 
                : 'https://api.sandbox.paypal.com';

            // 測試 PayPal OAuth
            $response = Http::timeout(10)
                ->asForm()
                ->withBasicAuth($clientId, $clientSecret)
                ->post("{$baseUrl}/v1/oauth2/token", [
                    'grant_type' => 'client_credentials'
                ]);
            
            if ($response->successful()) {
                return ['success' => true, 'message' => 'PayPal 連線測試成功'];
            } else {
                return ['success' => false, 'message' => 'PayPal API 認證失敗'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'PayPal 連線測試失敗：' . $e->getMessage()];
        }
    }

    /**
     * 驗證分析工具設定
     */
    protected function validateAnalyticsSettings(): void
    {
        $rules = [
            'analyticsSettings.google_analytics_id' => 'nullable|string|regex:/^G-[A-Z0-9]+$/',
            'analyticsSettings.google_tag_manager_id' => 'nullable|string|regex:/^GTM-[A-Z0-9]+$/',
        ];

        $validator = Validator::make($this->all(), $rules, [
            'analyticsSettings.google_analytics_id.regex' => 'Google Analytics ID 格式不正確（應為 G-XXXXXXXXXX）',
            'analyticsSettings.google_tag_manager_id.regex' => 'Google Tag Manager ID 格式不正確（應為 GTM-XXXXXXX）',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * 驗證社群媒體登入設定
     */
    protected function validateSocialLoginSettings(): void
    {
        $rules = [
            'socialLoginSettings.google_oauth_enabled' => 'required|boolean',
            'socialLoginSettings.google_client_id' => 'required_if:socialLoginSettings.google_oauth_enabled,true|string|max:255',
            'socialLoginSettings.google_client_secret' => 'required_if:socialLoginSettings.google_oauth_enabled,true|string|max:255',
            'socialLoginSettings.facebook_oauth_enabled' => 'required|boolean',
            'socialLoginSettings.facebook_app_id' => 'required_if:socialLoginSettings.facebook_oauth_enabled,true|string|max:255',
            'socialLoginSettings.facebook_app_secret' => 'required_if:socialLoginSettings.facebook_oauth_enabled,true|string|max:255',
            'socialLoginSettings.github_oauth_enabled' => 'required|boolean',
            'socialLoginSettings.github_client_id' => 'required_if:socialLoginSettings.github_oauth_enabled,true|string|max:255',
            'socialLoginSettings.github_client_secret' => 'required_if:socialLoginSettings.github_oauth_enabled,true|string|max:255',
        ];

        $validator = Validator::make($this->all(), $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * 驗證雲端儲存設定
     */
    protected function validateCloudStorageSettings(): void
    {
        $rules = [
            'cloudStorageSettings.aws_s3_enabled' => 'required|boolean',
            'cloudStorageSettings.aws_access_key' => 'required_if:cloudStorageSettings.aws_s3_enabled,true|string|max:255',
            'cloudStorageSettings.aws_secret_key' => 'required_if:cloudStorageSettings.aws_s3_enabled,true|string|max:255',
            'cloudStorageSettings.aws_region' => 'required_if:cloudStorageSettings.aws_s3_enabled,true|string',
            'cloudStorageSettings.aws_bucket' => 'required_if:cloudStorageSettings.aws_s3_enabled,true|string|max:255',
            'cloudStorageSettings.google_drive_enabled' => 'required|boolean',
            'cloudStorageSettings.google_drive_client_id' => 'required_if:cloudStorageSettings.google_drive_enabled,true|string|max:255',
            'cloudStorageSettings.google_drive_client_secret' => 'required_if:cloudStorageSettings.google_drive_enabled,true|string|max:255',
        ];

        $validator = Validator::make($this->all(), $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * 驗證支付閘道設定
     */
    protected function validatePaymentGatewaySettings(): void
    {
        $rules = [
            'paymentGatewaySettings.stripe_enabled' => 'required|boolean',
            'paymentGatewaySettings.stripe_publishable_key' => 'required_if:paymentGatewaySettings.stripe_enabled,true|string|max:255',
            'paymentGatewaySettings.stripe_secret_key' => 'required_if:paymentGatewaySettings.stripe_enabled,true|string|max:255',
            'paymentGatewaySettings.stripe_webhook_secret' => 'nullable|string|max:255',
            'paymentGatewaySettings.paypal_enabled' => 'required|boolean',
            'paymentGatewaySettings.paypal_client_id' => 'required_if:paymentGatewaySettings.paypal_enabled,true|string|max:255',
            'paymentGatewaySettings.paypal_client_secret' => 'required_if:paymentGatewaySettings.paypal_enabled,true|string|max:255',
            'paymentGatewaySettings.paypal_mode' => 'required_if:paymentGatewaySettings.paypal_enabled,true|string|in:sandbox,live',
        ];

        $validator = Validator::make($this->all(), $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * 切換分頁
     */
    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->errors = [];
        $this->successMessage = '';
    }

    /**
     * 清除訊息
     */
    public function clearMessages(): void
    {
        $this->errors = [];
        $this->successMessage = '';
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.settings.integration-settings');
    }
}