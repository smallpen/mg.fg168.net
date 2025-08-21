<?php

namespace App\Providers;

use App\Helpers\SystemSettingsHelper;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class SystemSettingsServiceProvider extends ServiceProvider
{
    /**
     * 註冊服務
     *
     * @return void
     */
    public function register(): void
    {
        // 註冊 SystemSettingsHelper 為單例
        $this->app->singleton('system-settings', function () {
            return new SystemSettingsHelper();
        });
    }

    /**
     * 啟動服務
     *
     * @return void
     */
    public function boot(): void
    {
        // 註冊 Blade 指令用於設定系統
        $this->registerBladeDirectives();
        
        // 發布配置檔案
        $this->publishes([
            __DIR__.'/../../config/system-settings.php' => config_path('system-settings.php'),
        ], 'system-settings-config');
    }

    /**
     * 註冊 Blade 指令
     *
     * @return void
     */
    protected function registerBladeDirectives(): void
    {
        // @setting 指令 - 取得設定值
        Blade::directive('setting', function ($expression) {
            return "<?php echo \\App\\Helpers\\SystemSettingsHelper::getSetting({$expression}); ?>";
        });

        // @settingDefault 指令 - 取得設定預設值
        Blade::directive('settingDefault', function ($expression) {
            return "<?php echo \\App\\Helpers\\SystemSettingsHelper::getDefaultValue({$expression}); ?>";
        });

        // @settingDisplay 指令 - 取得設定顯示值
        Blade::directive('settingDisplay', function ($expression) {
            $parts = explode(',', $expression, 2);
            $key = trim($parts[0]);
            $value = isset($parts[1]) ? trim($parts[1]) : "\\App\\Helpers\\SystemSettingsHelper::getSetting({$key})";
            
            return "<?php echo \\App\\Helpers\\SystemSettingsHelper::getDisplayValue({$key}, {$value}); ?>";
        });

        // @ifSetting 指令 - 條件判斷設定值
        Blade::if('setting', function ($key, $value = true) {
            $actualValue = \App\Helpers\SystemSettingsHelper::getSetting($key);
            
            if (is_bool($value)) {
                $actualValue = filter_var($actualValue, FILTER_VALIDATE_BOOLEAN);
            }
            
            return $actualValue === $value;
        });

        // @ifSettingEnabled 指令 - 判斷設定是否啟用
        Blade::if('settingEnabled', function ($key) {
            $value = \App\Helpers\SystemSettingsHelper::getSetting($key);
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        });

        // @ifSettingDisabled 指令 - 判斷設定是否停用
        Blade::if('settingDisabled', function ($key) {
            $value = \App\Helpers\SystemSettingsHelper::getSetting($key);
            return !filter_var($value, FILTER_VALIDATE_BOOLEAN);
        });
    }
}