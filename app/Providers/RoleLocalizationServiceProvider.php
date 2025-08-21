<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\Helpers\RoleLocalizationHelper;

/**
 * 角色本地化服務提供者
 * 
 * 註冊角色本地化相關服務和 Blade 指令
 */
class RoleLocalizationServiceProvider extends ServiceProvider
{
    /**
     * 註冊服務
     */
    public function register(): void
    {
        // 註冊 RoleLocalizationHelper 為單例
        $this->app->singleton('role.localization', function () {
            return new RoleLocalizationHelper();
        });
    }

    /**
     * 啟動服務
     */
    public function boot(): void
    {
        // 註冊 Blade 指令
        $this->registerBladeDirectives();
        
        // 註冊視圖組合器
        $this->registerViewComposers();
    }

    /**
     * 註冊 Blade 指令
     */
    private function registerBladeDirectives(): void
    {
        // @roleDisplayName('admin') - 顯示角色的本地化名稱
        Blade::directive('roleDisplayName', function ($expression) {
            return "<?php echo App\Helpers\RoleLocalizationHelper::getRoleDisplayName($expression); ?>";
        });

        // @permissionDisplayName('users.view') - 顯示權限的本地化名稱
        Blade::directive('permissionDisplayName', function ($expression) {
            return "<?php echo App\Helpers\RoleLocalizationHelper::getPermissionDisplayName($expression); ?>";
        });

        // @moduleDisplayName('users') - 顯示模組的本地化名稱
        Blade::directive('moduleDisplayName', function ($expression) {
            return "<?php echo App\Helpers\RoleLocalizationHelper::getModuleDisplayName($expression); ?>";
        });

        // @roleDescription('admin') - 顯示角色的本地化描述
        Blade::directive('roleDescription', function ($expression) {
            return "<?php echo App\Helpers\RoleLocalizationHelper::getRoleDescription($expression); ?>";
        });

        // @permissionDescription('users.view') - 顯示權限的本地化描述
        Blade::directive('permissionDescription', function ($expression) {
            return "<?php echo App\Helpers\RoleLocalizationHelper::getPermissionDescription($expression); ?>";
        });

        // @roleErrorMessage('crud.role_not_found') - 顯示角色錯誤訊息
        Blade::directive('roleErrorMessage', function ($expression) {
            $parts = explode(',', $expression, 2);
            $key = trim($parts[0], " '\"");
            $params = isset($parts[1]) ? $parts[1] : '[]';
            return "<?php echo App\Helpers\RoleLocalizationHelper::getErrorMessage('$key', $params); ?>";
        });

        // @roleSuccessMessage('created', ['name' => $role->name]) - 顯示角色成功訊息
        Blade::directive('roleSuccessMessage', function ($expression) {
            $parts = explode(',', $expression, 2);
            $key = trim($parts[0], " '\"");
            $params = isset($parts[1]) ? $parts[1] : '[]';
            return "<?php echo App\Helpers\RoleLocalizationHelper::getSuccessMessage('$key', $params); ?>";
        });

        // @localizedDate($role->created_at) - 顯示本地化日期
        Blade::directive('localizedDate', function ($expression) {
            return "<?php echo App\Helpers\RoleLocalizationHelper::formatDate($expression); ?>";
        });
    }

    /**
     * 註冊視圖組合器
     */
    private function registerViewComposers(): void
    {
        // 為角色管理相關視圖提供本地化資料
        view()->composer([
            'livewire.admin.roles.*',
            'admin.roles.*',
            'components.role.*'
        ], function ($view) {
            $view->with([
                'roleNames' => RoleLocalizationHelper::getAllRoleNames(),
                'permissionNames' => RoleLocalizationHelper::getAllPermissionNames(),
                'moduleNames' => RoleLocalizationHelper::getAllModuleNames(),
                'isChineseLocale' => RoleLocalizationHelper::isChineseLocale(),
                'dateFormat' => RoleLocalizationHelper::getDateFormat(),
            ]);
        });
    }
}