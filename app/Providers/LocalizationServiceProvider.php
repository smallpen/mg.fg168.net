<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\Helpers\LocalizationHelper;
use App\Services\LanguageService;
use Carbon\Carbon;

/**
 * 本地化服務提供者
 * 
 * 註冊本地化相關的服務和 Blade 指令
 */
class LocalizationServiceProvider extends ServiceProvider
{
    /**
     * 註冊服務
     */
    public function register(): void
    {
        // 註冊語言服務為單例
        $this->app->singleton(LanguageService::class, function ($app) {
            return new LanguageService();
        });
    }

    /**
     * 啟動服務
     */
    public function boot(): void
    {
        $this->registerBladeDirectives();
        $this->registerMacros();
    }

    /**
     * 註冊 Blade 指令
     */
    protected function registerBladeDirectives(): void
    {
        // 格式化日期時間
        Blade::directive('datetime', function ($expression) {
            return "<?php echo App\Helpers\LocalizationHelper::formatDateTime($expression); ?>";
        });

        // 格式化日期
        Blade::directive('date', function ($expression) {
            return "<?php echo App\Helpers\LocalizationHelper::formatDate($expression); ?>";
        });

        // 格式化時間
        Blade::directive('time', function ($expression) {
            return "<?php echo App\Helpers\LocalizationHelper::formatTime($expression); ?>";
        });

        // 格式化相對時間
        Blade::directive('timeago', function ($expression) {
            return "<?php echo App\Helpers\LocalizationHelper::formatRelativeTime($expression); ?>";
        });

        // 格式化數字
        Blade::directive('number', function ($expression) {
            return "<?php echo App\Helpers\LocalizationHelper::formatNumber($expression); ?>";
        });

        // 格式化貨幣
        Blade::directive('currency', function ($expression) {
            return "<?php echo App\Helpers\LocalizationHelper::formatCurrency($expression); ?>";
        });

        // 格式化百分比
        Blade::directive('percentage', function ($expression) {
            return "<?php echo App\Helpers\LocalizationHelper::formatPercentage($expression); ?>";
        });

        // 格式化檔案大小
        Blade::directive('filesize', function ($expression) {
            return "<?php echo App\Helpers\LocalizationHelper::formatFileSize($expression); ?>";
        });

        // 語言方向
        Blade::directive('dir', function () {
            return "<?php echo app(App\Services\LanguageService::class)->getDirection(); ?>";
        });

        // 檢查是否為 RTL
        Blade::directive('isRtl', function () {
            return "<?php echo app(App\Services\LanguageService::class)->isRtl() ? 'true' : 'false'; ?>";
        });

        // 當前語言代碼
        Blade::directive('locale', function () {
            return "<?php echo app()->getLocale(); ?>";
        });

        // 語言切換連結
        Blade::directive('langUrl', function ($expression) {
            return "<?php echo request()->fullUrlWithQuery(['locale' => $expression]); ?>";
        });
    }

    /**
     * 註冊巨集
     */
    protected function registerMacros(): void
    {
        // Carbon 巨集：本地化格式
        Carbon::macro('toLocalizedDateString', function ($locale = null) {
            return LocalizationHelper::formatDate($this, $locale);
        });

        Carbon::macro('toLocalizedTimeString', function ($locale = null) {
            return LocalizationHelper::formatTime($this, $locale);
        });

        Carbon::macro('toLocalizedDateTimeString', function ($locale = null) {
            return LocalizationHelper::formatDateTime($this, $locale);
        });

        Carbon::macro('toRelativeString', function ($locale = null) {
            return LocalizationHelper::formatRelativeTime($this, $locale);
        });
    }
}