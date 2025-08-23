<?php

namespace App\Services;

use App\Repositories\SettingsRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

/**
 * 配置服務類別
 * 
 * 提供系統設定的配置管理和驗證功能
 */
class ConfigurationService
{
    /**
     * 設定資料存取介面
     *
     * @var SettingsRepositoryInterface
     */
    protected SettingsRepositoryInterface $settingsRepository;

    /**
     * 設定配置快取鍵
     */
    private const CONFIG_CACHE_KEY = 'system_settings_config';

    /**
     * 建構函式
     *
     * @param SettingsRepositoryInterface $settingsRepository
     */
    public function __construct(SettingsRepositoryInterface $settingsRepository)
    {
        $this->settingsRepository = $settingsRepository;
    }

    /**
     * 取得設定配置
     * 
     * @param string $key 設定鍵值
     * @return array
     */
    public function getSettingConfig(string $key): array
    {
        $configs = $this->getAllConfigs();
        
        return $configs['settings'][$key] ?? [];
    }

    /**
     * 取得所有分類
     * 
     * @return array
     */
    public function getCategories(): array
    {
        $configs = $this->getAllConfigs();
        
        return $configs['categories'] ?? [];
    }

    /**
     * 驗證設定值
     * 
     * @param string $key 設定鍵值
     * @param mixed $value 設定值
     * @return bool
     */
    public function validateSettingValue(string $key, $value): bool
    {
        $config = $this->getSettingConfig($key);
        
        if (empty($config)) {
            return false;
        }

        // 取得驗證規則
        $rules = $this->buildValidationRules($config);
        
        if (empty($rules)) {
            return true;
        }

        // 如果 rules 是字串，需要轉換為陣列
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        $validator = Validator::make(['value' => $value], ['value' => $rules]);
        
        return !$validator->fails();
    }

    /**
     * 取得設定類型
     * 
     * @param string $key 設定鍵值
     * @return string
     */
    public function getSettingType(string $key): string
    {
        $config = $this->getSettingConfig($key);
        
        return $config['type'] ?? 'text';
    }

    /**
     * 取得設定選項
     * 
     * @param string $key 設定鍵值
     * @return array
     */
    public function getSettingOptions(string $key): array
    {
        $config = $this->getSettingConfig($key);
        
        // 合併所有選項相關的配置
        $options = [];
        
        if (isset($config['options'])) {
            $options = array_merge($options, $config['options']);
        }
        
        // 添加其他配置項
        if (isset($config['min'])) {
            $options['min'] = $config['min'];
        }
        
        if (isset($config['max'])) {
            $options['max'] = $config['max'];
        }
        
        if (isset($config['values'])) {
            $options['values'] = $config['values'];
        }
        
        return $options;
    }

    /**
     * 取得相依設定
     * 
     * @param string $key 設定鍵值
     * @return array
     */
    public function getDependentSettings(string $key): array
    {
        $config = $this->getSettingConfig($key);
        
        return $config['dependencies'] ?? [];
    }

    /**
     * 應用設定到系統配置
     * 
     * @param array $settings 設定陣列
     * @return void
     */
    public function applySettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $this->applySingleSetting($key, $value);
        }
    }

    /**
     * 測試連線設定
     * 
     * @param string $type 連線類型
     * @param array $config 配置資料
     * @return bool
     */
    public function testConnection(string $type, array $config): bool
    {
        try {
            switch ($type) {
                case 'smtp':
                    return $this->testSmtpConnection($config);
                case 'database':
                    return $this->testDatabaseConnection($config);
                case 'redis':
                    return $this->testRedisConnection($config);
                case 'api':
                    return $this->testApiConnection($config);
                case 'aws_s3':
                    return $this->testAwsS3Connection($config);
                case 'google_oauth':
                    return $this->testGoogleOAuthConnection($config);
                default:
                    return false;
            }
        } catch (\Exception $e) {
            Log::error("連線測試失敗: {$type}", [
                'error' => $e->getMessage(),
                'config' => $config,
            ]);
            
            return false;
        }
    }

    /**
     * 生成設定預覽
     * 
     * @param array $settings 設定陣列
     * @return array
     */
    public function generatePreview(array $settings): array
    {
        $preview = [];
        
        foreach ($settings as $key => $value) {
            $config = $this->getSettingConfig($key);
            
            if (isset($config['preview']) && $config['preview']) {
                $preview[$key] = $this->generateSettingPreview($key, $value, $config);
            }
        }
        
        return $preview;
    }

    /**
     * 取得設定的預設值
     * 
     * @param string $key 設定鍵值
     * @return mixed
     */
    public function getDefaultValue(string $key)
    {
        $config = $this->getSettingConfig($key);
        
        return $config['default'] ?? null;
    }

    /**
     * 檢查設定是否為必填
     * 
     * @param string $key 設定鍵值
     * @return bool
     */
    public function isRequired(string $key): bool
    {
        $config = $this->getSettingConfig($key);
        
        return $config['required'] ?? false;
    }

    /**
     * 檢查設定是否為敏感資料
     * 
     * @param string $key 設定鍵值
     * @return bool
     */
    public function isSensitive(string $key): bool
    {
        $config = $this->getSettingConfig($key);
        
        return $config['sensitive'] ?? false;
    }

    /**
     * 取得設定的輸入元件類型
     * 
     * @param string $key 設定鍵值
     * @return string
     */
    public function getInputComponent(string $key): string
    {
        $config = $this->getSettingConfig($key);
        $type = $config['type'] ?? 'text';
        
        $componentMap = [
            'text' => 'text-input',
            'textarea' => 'textarea-input',
            'number' => 'number-input',
            'email' => 'email-input',
            'url' => 'url-input',
            'password' => 'password-input',
            'boolean' => 'toggle-input',
            'select' => 'select-input',
            'multiselect' => 'multiselect-input',
            'color' => 'color-input',
            'file' => 'file-input',
            'image' => 'image-input',
            'json' => 'json-input',
            'code' => 'code-input',
        ];
        
        return $componentMap[$type] ?? 'text-input';
    }

    /**
     * 重新載入設定配置
     * 
     * @return void
     */
    public function reloadConfig(): void
    {
        Cache::forget(self::CONFIG_CACHE_KEY);
    }

    /**
     * 取得所有配置
     * 
     * @return array
     */
    protected function getAllConfigs(): array
    {
        return Cache::remember(self::CONFIG_CACHE_KEY, 3600, function () {
            return Config::get('system-settings', []);
        });
    }

    /**
     * 建立驗證規則
     * 
     * @param array $config 設定配置
     * @return array|string
     */
    protected function buildValidationRules(array $config)
    {
        // 如果有直接的驗證規則，優先使用
        if (isset($config['validation'])) {
            return $config['validation'];
        }
        
        $rules = [];
        
        // 必填檢查
        if ($config['required'] ?? false) {
            $rules[] = 'required';
        }
        
        // 類型驗證
        switch ($config['type'] ?? 'text') {
            case 'text':
            case 'textarea':
                $rules[] = 'string';
                if (isset($config['max_length'])) {
                    $rules[] = 'max:' . $config['max_length'];
                }
                break;
            case 'number':
                $rules[] = 'numeric';
                if (isset($config['min'])) {
                    $rules[] = 'min:' . $config['min'];
                }
                if (isset($config['max'])) {
                    $rules[] = 'max:' . $config['max'];
                }
                break;
            case 'email':
                $rules[] = 'email';
                break;
            case 'url':
                $rules[] = 'url';
                break;
            case 'boolean':
                $rules[] = 'boolean';
                break;
            case 'json':
                $rules[] = 'json';
                break;
            case 'select':
                if (isset($config['options'])) {
                    $rules[] = 'in:' . implode(',', array_keys($config['options']));
                }
                break;
            case 'color':
                $rules[] = 'regex:/^#[0-9A-Fa-f]{6}$/';
                break;
        }
        
        return $rules;
    }

    /**
     * 應用單一設定
     * 
     * @param string $key 設定鍵值
     * @param mixed $value 設定值
     * @return void
     */
    protected function applySingleSetting(string $key, $value): void
    {
        $config = $this->getSettingConfig($key);
        
        // 如果有配置映射，應用到系統配置
        if (isset($config['config_path'])) {
            Config::set($config['config_path'], $value);
        }
        
        // 特殊處理某些設定
        switch ($key) {
            case 'app.timezone':
                Config::set('app.timezone', $value);
                date_default_timezone_set($value);
                break;
            case 'app.locale':
                Config::set('app.locale', $value);
                break;
            case 'mail.default':
                Config::set('mail.default', $value);
                break;
        }
    }

    /**
     * 測試 SMTP 連線
     * 
     * @param array $config SMTP 配置
     * @return bool
     */
    protected function testSmtpConnection(array $config): bool
    {
        // 檢查必要的配置
        if (empty($config['host'])) {
            return false;
        }

        try {
            // 使用 socket 連線測試 SMTP 伺服器
            $host = $config['host'];
            $port = $config['port'] ?? 587;
            $timeout = 10;
            
            // 嘗試建立 socket 連線
            $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
            
            if (!$socket) {
                Log::error('SMTP 連線測試失敗', [
                    'error' => "無法連接到 {$host}:{$port} - {$errstr} ({$errno})",
                    'config' => array_merge($config, ['password' => '***']),
                ]);
                return false;
            }
            
            // 讀取伺服器回應
            $response = fgets($socket, 512);
            fclose($socket);
            
            // 檢查是否收到 SMTP 歡迎訊息（通常以 220 開頭）
            if (strpos($response, '220') === 0) {
                return true;
            }
            
            Log::error('SMTP 連線測試失敗', [
                'error' => "伺服器回應異常: {$response}",
                'config' => array_merge($config, ['password' => '***']),
            ]);
            return false;
            
        } catch (\Exception $e) {
            Log::error('SMTP 連線測試失敗', [
                'error' => $e->getMessage(),
                'config' => array_merge($config, ['password' => '***']),
            ]);
            return false;
        }
    }

    /**
     * 測試資料庫連線
     * 
     * @param array $config 資料庫配置
     * @return bool
     */
    protected function testDatabaseConnection(array $config): bool
    {
        try {
            $pdo = new \PDO(
                "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']}",
                $config['username'],
                $config['password']
            );
            
            $pdo->query('SELECT 1');
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 測試 Redis 連線
     * 
     * @param array $config Redis 配置
     * @return bool
     */
    protected function testRedisConnection(array $config): bool
    {
        try {
            $redis = new \Redis();
            $redis->connect($config['host'], $config['port']);
            
            if (!empty($config['password'])) {
                $redis->auth($config['password']);
            }
            
            $redis->ping();
            $redis->close();
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 測試 API 連線
     * 
     * @param array $config API 配置
     * @return bool
     */
    protected function testApiConnection(array $config): bool
    {
        try {
            $response = Http::timeout(10)
                            ->withHeaders($config['headers'] ?? [])
                            ->get($config['url']);
            
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 測試 AWS S3 連線
     * 
     * @param array $config AWS S3 配置
     * @return bool
     */
    protected function testAwsS3Connection(array $config): bool
    {
        try {
            // 檢查是否安裝了 AWS SDK
            if (!class_exists('\Aws\S3\S3Client')) {
                Log::warning('AWS SDK 未安裝，無法測試 S3 連線');
                return false;
            }

            // 使用 AWS SDK 測試 S3 連線
            $s3Client = new \Aws\S3\S3Client([
                'version' => 'latest',
                'region' => $config['region'],
                'credentials' => [
                    'key' => $config['access_key'],
                    'secret' => $config['secret_key'],
                ],
            ]);
            
            // 嘗試列出儲存桶內容來測試連線
            $result = $s3Client->listObjects([
                'Bucket' => $config['bucket'],
                'MaxKeys' => 1,
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('AWS S3 連線測試失敗', [
                'error' => $e->getMessage(),
                'config' => array_merge($config, ['secret_key' => '***']),
            ]);
            return false;
        }
    }

    /**
     * 測試 Google OAuth 連線
     * 
     * @param array $config Google OAuth 配置
     * @return bool
     */
    protected function testGoogleOAuthConnection(array $config): bool
    {
        try {
            // 測試 Google OAuth 配置
            $response = Http::timeout(10)
                ->post('https://oauth2.googleapis.com/token', [
                    'client_id' => $config['client_id'],
                    'client_secret' => $config['client_secret'],
                    'grant_type' => 'client_credentials',
                ]);
            
            // 如果回應包含錯誤但不是認證錯誤，表示配置正確
            if ($response->failed()) {
                $error = $response->json('error');
                // 'unauthorized_client' 表示配置正確但權限不足，這是預期的
                return $error === 'unauthorized_client';
            }
            
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Google OAuth 連線測試失敗', [
                'error' => $e->getMessage(),
                'config' => array_merge($config, ['client_secret' => '***']),
            ]);
            return false;
        }
    }

    /**
     * 生成單一設定預覽
     * 
     * @param string $key 設定鍵值
     * @param mixed $value 設定值
     * @param array $config 設定配置
     * @return array
     */
    protected function generateSettingPreview(string $key, $value, array $config): array
    {
        $preview = [
            'key' => $key,
            'value' => $value,
            'type' => $config['type'] ?? 'text',
        ];
        
        switch ($config['type'] ?? 'text') {
            case 'color':
                $preview['css'] = "--primary-color: {$value}";
                break;
            case 'image':
                $preview['url'] = $value;
                break;
            case 'boolean':
                $preview['enabled'] = (bool) $value;
                break;
        }
        
        return $preview;
    }

    /**
     * 取得指定分類的所有設定
     *
     * @param string $category 分類名稱
     * @return array
     */
    public function getSettingsByCategory(string $category): array
    {
        $settings = [];
        $settingsCollection = $this->settingsRepository->getSettingsByCategory($category);
        
        foreach ($settingsCollection as $setting) {
            $settings[$setting->key] = $setting->value;
        }
        
        return $settings;
    }

    /**
     * 更新多個設定
     *
     * @param array $settings 設定陣列
     * @return bool
     */
    public function updateSettings(array $settings): bool
    {
        try {
            foreach ($settings as $key => $value) {
                $this->settingsRepository->updateSetting($key, $value);
            }
            return true;
        } catch (\Exception $e) {
            Log::error('更新設定失敗', [
                'error' => $e->getMessage(),
                'settings' => $settings,
            ]);
            return false;
        }
    }
}