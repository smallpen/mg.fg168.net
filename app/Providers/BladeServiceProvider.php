<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\Helpers\DateTimeHelper;

/**
 * Blade 服務提供者
 * 
 * 註冊自訂的 Blade 指令
 */
class BladeServiceProvider extends ServiceProvider
{
    /**
     * 註冊服務
     */
    public function register(): void
    {
        //
    }

    /**
     * 啟動服務
     */
    public function boot(): void
    {
        $this->registerBladeDirectives();
    }

    /**
     * 註冊 Blade 指令
     */
    private function registerBladeDirectives(): void
    {
        // 日期時間格式化指令
        Blade::directive('datetime', function ($expression) {
            return "<?php echo \App\Helpers\DateTimeHelper::formatDateTime($expression); ?>";
        });

        // 相對時間指令
        Blade::directive('timeago', function ($expression) {
            return "<?php echo \App\Helpers\DateTimeHelper::formatRelative($expression); ?>";
        });

        // 僅日期指令
        Blade::directive('dateonly', function ($expression) {
            return "<?php echo \App\Helpers\DateTimeHelper::formatDate($expression); ?>";
        });

        // 僅時間指令
        Blade::directive('timeonly', function ($expression) {
            return "<?php echo \App\Helpers\DateTimeHelper::formatTime($expression); ?>";
        });

        // 本地化狀態指令
        Blade::directive('status', function ($expression) {
            return "<?php echo $expression ? __('admin.users.active') : __('admin.users.inactive'); ?>";
        });

        // 本地化角色名稱指令
        Blade::directive('rolename', function ($expression) {
            return "<?php echo $expression->localized_display_name ?? $expression->display_name; ?>";
        });
    }
}