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
        $this->app->singleton(\App\Services\PermissionAuditService::class);
        $this->app->singleton(\App\Services\PermissionValidationService::class);
        
        // 註冊 Repository 介面綁定
        $this->app->bind(
            \App\Repositories\Contracts\PermissionRepositoryInterface::class,
            \App\Repositories\PermissionRepository::class
        );
        
        $this->app->bind(
            \App\Repositories\SettingsRepositoryInterface::class,
            \App\Repositories\SettingsRepository::class
        );
        
        $this->app->bind(
            \App\Repositories\Contracts\ActivityRepositoryInterface::class,
            \App\Repositories\ActivityRepository::class
        );
        
        $this->app->bind(
            \App\Repositories\ActivityRepositoryInterface::class,
            \App\Repositories\ActivityRepository::class
        );

        // 註冊活動記錄相關服務
        $this->app->singleton(\App\Services\ActivityBackupService::class);
        $this->app->singleton(\App\Services\ActivityIntegrityService::class);
        $this->app->singleton(\App\Services\ActivityLogger::class);
        $this->app->singleton(\App\Services\SensitiveDataFilter::class);
        $this->app->singleton(\App\Services\ActivitySecurityService::class);
        
        // 註冊設定相關服務
        $this->app->singleton(\App\Services\EncryptionService::class);
        $this->app->singleton(\App\Services\ConfigurationService::class);
    }

    /**
     * 啟動任何應用程式服務
     */
    public function boot(): void
    {
        // 設定 Carbon 本地化
        $this->configureCarbonLocalization();
        
        // 註冊模型觀察者
        $this->registerModelObservers();
        
        // 註冊 Blade 指令
        $this->registerBladeDirectives();
    }

    /**
     * 註冊模型觀察者
     */
    private function registerModelObservers(): void
    {
        \App\Models\Permission::observe(\App\Observers\PermissionObserver::class);
        \App\Models\Permission::observe(\App\Observers\PermissionSecurityObserver::class);
        \App\Models\Setting::observe(\App\Observers\SettingObserver::class);
        \App\Models\Activity::observe(\App\Observers\ActivitySecurityObserver::class);
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

    /**
     * 註冊 Blade 指令
     */
    private function registerBladeDirectives(): void
    {
        // 日期格式化指令
        \Blade::directive('formatDate', function ($expression) {
            return "<?php echo \App\Helpers\DateTimeHelper::formatDate($expression); ?>";
        });

        // 時間格式化指令
        \Blade::directive('formatTime', function ($expression) {
            return "<?php echo \App\Helpers\DateTimeHelper::formatTime($expression); ?>";
        });

        // 日期時間格式化指令
        \Blade::directive('formatDateTime', function ($expression) {
            return "<?php echo \App\Helpers\DateTimeHelper::formatDateTime($expression); ?>";
        });

        // 相對時間格式化指令
        \Blade::directive('formatRelative', function ($expression) {
            return "<?php echo \App\Helpers\DateTimeHelper::formatRelative($expression); ?>";
        });

        // 人類可讀格式化指令
        \Blade::directive('formatHuman', function ($expression) {
            return "<?php echo \App\Helpers\DateTimeHelper::formatHuman($expression); ?>";
        });
    }
}