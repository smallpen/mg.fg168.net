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
    }

    /**
     * 啟動任何應用程式服務
     */
    public function boot(): void
    {
        //
    }
}