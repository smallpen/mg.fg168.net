<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use App\Repositories\SettingsRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * 應用安全設定中介軟體
 * 
 * 從資料庫載入安全設定並應用到系統配置中
 */
class ApplySecuritySettings
{
    /**
     * 快取鍵值
     */
    protected const CACHE_KEY = 'security_settings_applied';
    
    /**
     * 快取時間（秒）
     */
    protected const CACHE_TTL = 3600;

    /**
     * 設定資料庫
     */
    protected SettingsRepositoryInterface $settingsRepository;

    /**
     * 建構子
     */
    public function __construct(SettingsRepositoryInterface $settingsRepository)
    {
        $this->settingsRepository = $settingsRepository;
    }

    /**
     * 處理請求
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 檢查是否已經應用過設定（避免重複載入）
        if (!Cache::has(self::CACHE_KEY)) {
            $this->applySecuritySettings();
            Cache::put(self::CACHE_KEY, true, self::CACHE_TTL);
        }

        return $next($request);
    }

    /**
     * 應用安全設定到系統配置
     */
    protected function applySecuritySettings(): void
    {
        try {
            // 載入安全相關設定
            $securitySettings = $this->settingsRepository->getSettingsByCategory('security');
            
            foreach ($securitySettings as $setting) {
                $this->applySingleSetting($setting->key, $setting->value);
            }
            
        } catch (\Exception $e) {
            // 記錄錯誤但不中斷請求
            \Log::error('Failed to apply security settings', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * 應用單一設定
     */
    protected function applySingleSetting(string $key, $value): void
    {
        switch ($key) {
            case 'security.session_lifetime':
                Config::set('session.lifetime', (int) $value);
                break;
                
            case 'security.force_https':
                if ($value) {
                    Config::set('app.force_https', true);
                    // 設定 URL 強制使用 HTTPS
                    Config::set('app.url', str_replace('http://', 'https://', config('app.url')));
                }
                break;
                
            case 'security.login_max_attempts':
                Config::set('auth.throttle.max_attempts', (int) $value);
                break;
                
            case 'security.lockout_duration':
                Config::set('auth.throttle.decay_minutes', (int) $value);
                break;
                
            case 'security.password_min_length':
                Config::set('auth.password.min_length', (int) $value);
                break;
                
            case 'security.password_require_uppercase':
                Config::set('auth.password.require_uppercase', (bool) $value);
                break;
                
            case 'security.password_require_lowercase':
                Config::set('auth.password.require_lowercase', (bool) $value);
                break;
                
            case 'security.password_require_numbers':
                Config::set('auth.password.require_numbers', (bool) $value);
                break;
                
            case 'security.password_require_symbols':
                Config::set('auth.password.require_symbols', (bool) $value);
                break;
                
            case 'security.password_expiry_days':
                Config::set('auth.password.expiry_days', (int) $value);
                break;
                
            case 'security.two_factor_enabled':
                Config::set('auth.two_factor.enabled', (bool) $value);
                break;
        }
    }

    /**
     * 清除快取
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * 強制重新載入設定
     */
    public static function forceReload(): void
    {
        self::clearCache();
        
        // 立即重新載入設定
        $middleware = app(self::class);
        $middleware->applySecuritySettings();
        Cache::put(self::CACHE_KEY, true, self::CACHE_TTL);
    }
}