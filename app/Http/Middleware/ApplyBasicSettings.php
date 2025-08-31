<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use App\Repositories\SettingsRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * 應用基本設定中介軟體
 * 
 * 在每個請求中應用系統的基本設定，確保設定變更立即生效
 */
class ApplyBasicSettings
{
    /**
     * 設定資料庫介面
     */
    protected SettingsRepositoryInterface $settingsRepository;

    /**
     * 建構函式
     */
    public function __construct(SettingsRepositoryInterface $settingsRepository)
    {
        $this->settingsRepository = $settingsRepository;
    }

    /**
     * 處理傳入的請求
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 應用基本設定
        $this->applyBasicSettings();

        return $next($request);
    }

    /**
     * 應用基本設定到系統配置
     */
    protected function applyBasicSettings(): void
    {
        // 使用快取來避免每次請求都查詢資料庫
        $settings = Cache::remember('basic_settings_applied', 300, function () {
            return $this->loadBasicSettings();
        });

        // 應用設定到系統配置
        foreach ($settings as $key => $value) {
            $this->applySetting($key, $value);
        }
    }

    /**
     * 載入基本設定
     */
    protected function loadBasicSettings(): array
    {
        $basicSettingKeys = [
            'app.name',
            'app.description',
            'app.timezone',
            'app.locale',
            'app.date_format',
            'app.time_format',
        ];

        $settings = [];
        $settingsData = $this->settingsRepository->getSettings($basicSettingKeys);

        foreach ($basicSettingKeys as $key) {
            $setting = $settingsData->get($key);
            if ($setting) {
                $settings[$key] = $setting->value;
            }
        }

        return $settings;
    }

    /**
     * 應用單一設定
     */
    protected function applySetting(string $key, $value): void
    {
        switch ($key) {
            case 'app.name':
                Config::set('app.name', $value);
                break;
                
            case 'app.timezone':
                if ($value && $value !== Config::get('app.timezone')) {
                    Config::set('app.timezone', $value);
                    date_default_timezone_set($value);
                }
                break;
                
            case 'app.locale':
                // 只有在沒有明確語言偏好時才應用資料庫設定
                // 檢查是否有 URL 參數或 session 中的語言設定
                $request = request();
                $hasExplicitLocale = $request->has('locale') || 
                                   session()->has('locale') || 
                                   ($request->user() && $request->user()->locale);
                
                if ($value && $value !== app()->getLocale() && !$hasExplicitLocale) {
                    Config::set('app.locale', $value);
                    app()->setLocale($value);
                    
                    // 更新 Carbon 語言設定
                    \Carbon\Carbon::setLocale($this->mapAppLocaleToCarbon($value));
                }
                break;
                
            case 'app.date_format':
                // 儲存到快取供 DateTimeHelper 使用
                Cache::put('app.date_format', $value, 3600);
                break;
                
            case 'app.time_format':
                // 儲存到快取供 DateTimeHelper 使用
                Cache::put('app.time_format', $value, 3600);
                break;
        }
    }

    /**
     * 將應用程式語言代碼對應到 Carbon 語言代碼
     */
    protected function mapAppLocaleToCarbon(string $locale): string
    {
        $mapping = [
            'zh_TW' => 'zh_TW',
            'zh_CN' => 'zh_CN',
            'en' => 'en',
            'ja' => 'ja',
            'ko' => 'ko',
        ];

        return $mapping[$locale] ?? 'zh_TW';
    }

    /**
     * 清除基本設定快取
     */
    public static function clearCache(): void
    {
        Cache::forget('basic_settings_applied');
    }
}