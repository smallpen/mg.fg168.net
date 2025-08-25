<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\RoleRepositoryInterface;
use App\Repositories\RoleRepository;
use App\Repositories\PermissionRepositoryInterface;
use App\Repositories\PermissionRepository;
use App\Repositories\SettingsRepositoryInterface;
use App\Repositories\SettingsRepository;
use App\Repositories\ActivityRepository;
use App\Services\PermissionCacheService;
use App\Services\PermissionBatchService;
use App\Services\PermissionLazyLoadingService;
use App\Services\PermissionPerformanceService;
use App\Services\PermissionSecurityService;
use App\Services\PermissionValidationService;
use App\Services\PermissionAuditService;

/**
 * 資料存取層服務提供者
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * 註冊服務
     *
     * @return void
     */
    public function register(): void
    {
        // 註冊資料存取層
        $this->app->bind(RoleRepositoryInterface::class, RoleRepository::class);
        $this->app->bind(\App\Repositories\Contracts\RoleRepositoryInterface::class, RoleRepository::class);
        $this->app->bind(PermissionRepositoryInterface::class, PermissionRepository::class);
        $this->app->bind(\App\Repositories\Contracts\PermissionRepositoryInterface::class, PermissionRepository::class);
        $this->app->bind(SettingsRepositoryInterface::class, SettingsRepository::class);
        // ActivityRepositoryInterface binding is handled in AppServiceProvider
        
        // 註冊權限效能優化服務
        $this->app->singleton(PermissionCacheService::class);
        $this->app->singleton(PermissionBatchService::class);
        $this->app->singleton(PermissionLazyLoadingService::class);
        $this->app->singleton(PermissionPerformanceService::class);
        
        // 註冊權限安全服務
        $this->app->singleton(PermissionSecurityService::class);
        $this->app->singleton(PermissionValidationService::class);
        $this->app->singleton(PermissionAuditService::class);
    }

    /**
     * 啟動服務
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }
}