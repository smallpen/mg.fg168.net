<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\Helpers\PermissionLanguageHelper;

class PermissionLanguageServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // 註冊權限語言助手為單例
        // Register permission language helper as singleton
        $this->app->singleton('permission.lang', function () {
            return new PermissionLanguageHelper();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // 註冊 Blade 指令用於權限翻譯
        // Register Blade directives for permission translations
        $this->registerBladeDirectives();

        // 註冊視圖組合器
        // Register view composers
        $this->registerViewComposers();
    }

    /**
     * 註冊 Blade 指令
     * Register Blade directives
     */
    protected function registerBladeDirectives(): void
    {
        // @permission('key') - 權限翻譯指令
        Blade::directive('permission', function ($expression) {
            return "<?php echo App\Helpers\PermissionLanguageHelper::permission({$expression}); ?>";
        });

        // @permissionError('key') - 權限錯誤訊息指令
        Blade::directive('permissionError', function ($expression) {
            return "<?php echo App\Helpers\PermissionLanguageHelper::error({$expression}); ?>";
        });

        // @permissionMessage('key') - 權限成功訊息指令
        Blade::directive('permissionMessage', function ($expression) {
            return "<?php echo App\Helpers\PermissionLanguageHelper::message({$expression}); ?>";
        });

        // @permissionUI('key') - 權限 UI 翻譯指令
        Blade::directive('permissionUI', function ($expression) {
            return "<?php echo App\Helpers\PermissionLanguageHelper::ui({$expression}); ?>";
        });

        // @permissionType('type') - 權限類型翻譯指令
        Blade::directive('permissionType', function ($expression) {
            return "<?php echo App\Helpers\PermissionLanguageHelper::type({$expression}); ?>";
        });

        // @permissionModule('module') - 模組翻譯指令
        Blade::directive('permissionModule', function ($expression) {
            return "<?php echo App\Helpers\PermissionLanguageHelper::module({$expression}); ?>";
        });

        // @permissionStatus('status') - 狀態翻譯指令
        Blade::directive('permissionStatus', function ($expression) {
            return "<?php echo App\Helpers\PermissionLanguageHelper::status({$expression}); ?>";
        });

        // @permissionDateTime($datetime) - 日期時間格式化指令
        Blade::directive('permissionDateTime', function ($expression) {
            return "<?php echo App\Helpers\PermissionLanguageHelper::formatDateTime({$expression}); ?>";
        });

        // @permissionNumber($number) - 數字格式化指令
        Blade::directive('permissionNumber', function ($expression) {
            return "<?php echo App\Helpers\PermissionLanguageHelper::formatNumber({$expression}); ?>";
        });
    }

    /**
     * 註冊視圖組合器
     * Register view composers
     */
    protected function registerViewComposers(): void
    {
        // 為權限相關視圖提供通用資料
        // Provide common data for permission-related views
        view()->composer('livewire.admin.permissions.*', function ($view) {
            $view->with([
                'permissionTypes' => PermissionLanguageHelper::getAllTypes(),
                'permissionModules' => PermissionLanguageHelper::getAllModules(),
                'permissionStatuses' => PermissionLanguageHelper::getAllStatuses(),
                'isChineseLocale' => PermissionLanguageHelper::isChineseLocale(),
            ]);
        });

        // 為管理後台提供權限相關翻譯
        // Provide permission-related translations for admin backend
        view()->composer('layouts.admin', function ($view) {
            $view->with([
                'permissionNavigation' => [
                    'permissions' => PermissionLanguageHelper::permission('navigation.permissions'),
                    'list' => PermissionLanguageHelper::permission('navigation.list'),
                    'create' => PermissionLanguageHelper::permission('navigation.create'),
                    'dependencies' => PermissionLanguageHelper::permission('navigation.dependencies'),
                    'templates' => PermissionLanguageHelper::permission('navigation.templates'),
                    'test_tool' => PermissionLanguageHelper::permission('navigation.test_tool'),
                    'usage_stats' => PermissionLanguageHelper::permission('navigation.usage_stats'),
                    'audit' => PermissionLanguageHelper::permission('navigation.audit'),
                ],
            ]);
        });
    }
}