<?php

namespace App\Providers;

use App\Models\Activity;
use App\Observers\ActivityObserver;
use App\Services\ActivityIntegrityService;
use App\Services\SensitiveDataFilter;
use Illuminate\Support\ServiceProvider;

class ActivityIntegrityServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // 註冊完整性服務為單例
        $this->app->singleton(ActivityIntegrityService::class, function ($app) {
            return new ActivityIntegrityService();
        });
        
        // 註冊敏感資料過濾服務為單例
        $this->app->singleton(SensitiveDataFilter::class, function ($app) {
            return new SensitiveDataFilter();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // 註冊活動記錄觀察者
        if (class_exists(Activity::class)) {
            Activity::observe(ActivityObserver::class);
        }
        
        // 載入完整性相關配置
        $this->loadIntegrityConfiguration();
    }
    
    /**
     * 載入完整性相關配置
     * 
     * @return void
     */
    protected function loadIntegrityConfiguration(): void
    {
        // 可以在這裡載入自訂的敏感欄位配置
        $sensitiveDataFilter = $this->app->make(SensitiveDataFilter::class);
        
        // 從配置檔案載入額外的敏感欄位（如果存在）
        $customSensitiveFields = config('activity-log.sensitive_fields', []);
        if (!empty($customSensitiveFields)) {
            $sensitiveDataFilter->addSensitiveFields($customSensitiveFields);
        }
        
        // 從配置檔案載入額外的敏感模式（如果存在）
        $customSensitivePatterns = config('activity-log.sensitive_patterns', []);
        if (!empty($customSensitivePatterns)) {
            $sensitiveDataFilter->addSensitivePatterns($customSensitivePatterns);
        }
    }
}
