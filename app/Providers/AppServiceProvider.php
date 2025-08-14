<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * 註冊任何應用程式服務
     */
    public function register(): void
    {
        // 註冊 NavigationService 作為單例
        $this->app->singleton(\App\Services\NavigationService::class);
        
        // 註冊安全相關服務作為單例
        $this->app->singleton(\App\Services\PermissionService::class);
        $this->app->singleton(\App\Services\InputValidationService::class);
        $this->app->singleton(\App\Services\AuditLogService::class);
    }

    /**
     * 啟動任何應用程式服務
     */
    public function boot(): void
    {
        // 設定 Carbon 本地化
        $this->configureCarbonLocalization();
    }

    /**
     * 配置 Carbon 日期時間本地化
     */
    private function configureCarbonLocalization(): void
    {
        // 根據應用程式語言設定 Carbon 語言
        $locale = app()->getLocale();
        
        // 設定 Carbon 語言
        \Carbon\Carbon::setLocale($this->mapAppLocaleToCarbon($locale));
        
        // 設定預設時區
        date_default_timezone_set(config('app.timezone', 'Asia/Taipei'));
    }

    /**
     * 將應用程式語言代碼對應到 Carbon 語言代碼
     */
    private function mapAppLocaleToCarbon(string $locale): string
    {
        $mapping = [
            'zh_TW' => 'zh_TW',
            'en' => 'en',
        ];

        return $mapping[$locale] ?? 'zh_TW';
    }
}