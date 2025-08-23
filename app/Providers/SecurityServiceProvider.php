<?php

namespace App\Providers;

use App\Services\SecurityAnalyzer;
use Illuminate\Support\ServiceProvider;

/**
 * 安全服務提供者
 * 
 * 註冊安全相關的服務
 */
class SecurityServiceProvider extends ServiceProvider
{
    /**
     * 註冊服務
     */
    public function register(): void
    {
        $this->app->singleton(SecurityAnalyzer::class, function ($app) {
            return new SecurityAnalyzer();
        });
    }

    /**
     * 啟動服務
     */
    public function boot(): void
    {
        //
    }
}