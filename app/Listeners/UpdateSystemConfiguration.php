<?php

namespace App\Listeners;

use App\Events\SettingUpdated;
use App\Events\SettingsBatchUpdated;
use App\Services\ConfigurationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * 更新系統配置監聽器
 */
class UpdateSystemConfiguration implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * 配置服務
     *
     * @var ConfigurationService
     */
    protected ConfigurationService $configService;

    /**
     * 建構函式
     *
     * @param ConfigurationService $configService
     */
    public function __construct(ConfigurationService $configService)
    {
        $this->configService = $configService;
    }

    /**
     * 處理設定更新事件
     *
     * @param SettingUpdated $event
     * @return void
     */
    public function handleSettingUpdated(SettingUpdated $event): void
    {
        $this->applySettingToSystemConfig($event->key, $event->newValue);
        
        Log::debug('系統配置已更新', [
            'setting_key' => $event->key,
            'new_value' => $this->maskSensitiveValue($event->key, $event->newValue),
        ]);
    }

    /**
     * 處理批量設定更新事件
     *
     * @param SettingsBatchUpdated $event
     * @return void
     */
    public function handleBatchUpdated(SettingsBatchUpdated $event): void
    {
        foreach ($event->settings as $key => $value) {
            $this->applySettingToSystemConfig($key, $value);
        }
        
        Log::info('批量系統配置已更新', [
            'update_count' => $event->updateCount,
            'affected_categories' => $event->affectedCategories,
        ]);
    }

    /**
     * 應用設定到系統配置
     *
     * @param string $key 設定鍵值
     * @param mixed $value 設定值
     * @return void
     */
    protected function applySettingToSystemConfig(string $key, mixed $value): void
    {
        try {
            // 取得設定配置
            $settingConfig = $this->configService->getSettingConfig($key);
            
            // 如果有配置映射，應用到系統配置
            if (isset($settingConfig['config_path'])) {
                Config::set($settingConfig['config_path'], $value);
            }
            
            // 特殊處理某些重要設定
            $this->handleSpecialSettings($key, $value);
            
        } catch (\Exception $e) {
            Log::error('應用設定到系統配置失敗', [
                'setting_key' => $key,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 處理特殊設定
     *
     * @param string $key 設定鍵值
     * @param mixed $value 設定值
     * @return void
     */
    protected function handleSpecialSettings(string $key, mixed $value): void
    {
        switch ($key) {
            case 'app.name':
                Config::set('app.name', $value);
                break;
                
            case 'app.timezone':
                Config::set('app.timezone', $value);
                if (function_exists('date_default_timezone_set')) {
                    date_default_timezone_set($value);
                }
                break;
                
            case 'app.locale':
                Config::set('app.locale', $value);
                if (function_exists('app') && app()->bound('translator')) {
                    app()->setLocale($value);
                }
                break;
                
            case 'app.debug':
                Config::set('app.debug', (bool) $value);
                break;
                
            case 'mail.default':
                Config::set('mail.default', $value);
                break;
                
            case 'mail.mailers.smtp.host':
                Config::set('mail.mailers.smtp.host', $value);
                break;
                
            case 'mail.mailers.smtp.port':
                Config::set('mail.mailers.smtp.port', (int) $value);
                break;
                
            case 'mail.mailers.smtp.username':
                Config::set('mail.mailers.smtp.username', $value);
                break;
                
            case 'mail.mailers.smtp.password':
                Config::set('mail.mailers.smtp.password', $value);
                break;
                
            case 'mail.mailers.smtp.encryption':
                Config::set('mail.mailers.smtp.encryption', $value);
                break;
                
            case 'cache.default':
                Config::set('cache.default', $value);
                break;
                
            case 'session.lifetime':
                Config::set('session.lifetime', (int) $value);
                break;
                
            case 'session.driver':
                Config::set('session.driver', $value);
                break;
                
            case 'logging.default':
                Config::set('logging.default', $value);
                break;
                
            case 'filesystems.default':
                Config::set('filesystems.default', $value);
                break;
        }
    }

    /**
     * 遮罩敏感值用於日誌記錄
     *
     * @param string $key 設定鍵值
     * @param mixed $value 設定值
     * @return mixed
     */
    protected function maskSensitiveValue(string $key, mixed $value): mixed
    {
        $sensitivePatterns = [
            '*password*',
            '*secret*',
            '*key*',
            '*token*',
            '*api_key*',
            '*client_secret*',
        ];
        
        foreach ($sensitivePatterns as $pattern) {
            if (fnmatch($pattern, strtolower($key))) {
                return '***';
            }
        }
        
        return $value;
    }

    /**
     * 註冊事件監聽器
     *
     * @return array
     */
    public function subscribe(): array
    {
        return [
            SettingUpdated::class => 'handleSettingUpdated',
            SettingsBatchUpdated::class => 'handleBatchUpdated',
        ];
    }
}