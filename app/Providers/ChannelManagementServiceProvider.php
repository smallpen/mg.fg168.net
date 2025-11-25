<?php

namespace App\Providers;

use App\Contracts\AgentServiceInterface;
use App\Contracts\PlayerServiceInterface;
use App\Contracts\PointServiceInterface;
use App\Services\AgentService;
use App\Services\PlayerService;
use App\Services\PointService;
use App\Services\ChannelIntegrityService;
use App\Services\ChannelMonitoringService;
use App\Services\ActivityLogger;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

/**
 * 通路管理服務提供者
 * 
 * 負責註冊通路管理相關的服務類別
 */
class ChannelManagementServiceProvider extends ServiceProvider
{
    /**
     * 註冊服務
     */
    public function register(): void
    {
        // 註冊配置
        $this->mergeConfigFrom(
            __DIR__.'/../../config/channel.php', 'channel'
        );

        // 註冊 PointService（最基礎的服務，其他服務依賴它）
        $this->app->singleton(PointServiceInterface::class, PointService::class);
        $this->app->singleton(PointService::class, function ($app) {
            return new PointService(
                $app->make(ActivityLogger::class)
            );
        });

        // 註冊 AgentService
        $this->app->singleton(AgentServiceInterface::class, AgentService::class);
        $this->app->singleton(AgentService::class, function ($app) {
            return new AgentService(
                $app->make(PointService::class),
                $app->make(ActivityLogger::class)
            );
        });

        // 註冊 PlayerService
        $this->app->singleton(PlayerServiceInterface::class, PlayerService::class);
        $this->app->singleton(PlayerService::class, function ($app) {
            return new PlayerService(
                $app->make(PointService::class),
                $app->make(ActivityLogger::class)
            );
        });

        // 註冊完整性檢查服務
        $this->app->singleton(ChannelIntegrityService::class);

        // 註冊監控服務
        $this->app->singleton(ChannelMonitoringService::class, function ($app) {
            return new ChannelMonitoringService(
                $app->make(ChannelIntegrityService::class)
            );
        });
    }

    /**
     * 啟動服務
     */
    public function boot(): void
    {
        // 發布配置檔案
        $this->publishes([
            __DIR__.'/../../config/channel.php' => config_path('channel.php'),
        ], 'channel-config');

        // 註冊權限
        $this->registerPermissions();

        // 註冊命令
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\ChannelDataIntegrityCheck::class,
                \App\Console\Commands\ChannelMonitoringCheck::class,
                \App\Console\Commands\ChannelBackupRestore::class,
            ]);
        }
    }

    /**
     * 註冊通路管理相關權限
     */
    private function registerPermissions(): void
    {
        Gate::define('channels.view', function ($user) {
            return $user->hasPermission('channels.view');
        });

        Gate::define('channels.create', function ($user) {
            return $user->hasPermission('channels.create');
        });

        Gate::define('channels.edit', function ($user) {
            return $user->hasPermission('channels.edit');
        });

        Gate::define('channels.delete', function ($user) {
            return $user->hasPermission('channels.delete');
        });

        Gate::define('channels.monitoring.view', function ($user) {
            return $user->hasPermission('channels.monitoring.view');
        });

        Gate::define('channels.monitoring.check', function ($user) {
            return $user->hasPermission('channels.monitoring.check');
        });

        Gate::define('channels.integrity.check', function ($user) {
            return $user->hasPermission('channels.integrity.check');
        });

        Gate::define('channels.auto.repair', function ($user) {
            return $user->hasPermission('channels.auto.repair');
        });

        Gate::define('channels.backup.create', function ($user) {
            return $user->hasPermission('channels.backup.create');
        });

        Gate::define('channels.backup.restore', function ($user) {
            return $user->hasPermission('channels.backup.restore');
        });

        Gate::define('channels.alerts.resolve', function ($user) {
            return $user->hasPermission('channels.alerts.resolve');
        });
    }

    /**
     * 取得提供的服務
     */
    public function provides(): array
    {
        return [
            AgentServiceInterface::class,
            PlayerServiceInterface::class,
            PointServiceInterface::class,
            AgentService::class,
            PlayerService::class,
            PointService::class,
            ChannelIntegrityService::class,
            ChannelMonitoringService::class,
        ];
    }
}