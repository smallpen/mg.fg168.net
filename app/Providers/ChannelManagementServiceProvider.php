<?php

namespace App\Providers;

use App\Contracts\AgentServiceInterface;
use App\Contracts\PlayerServiceInterface;
use App\Contracts\PointServiceInterface;
use App\Services\AgentService;
use App\Services\PlayerService;
use App\Services\PointService;
use App\Services\ActivityLogger;
use Illuminate\Support\ServiceProvider;

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
    }

    /**
     * 啟動服務
     */
    public function boot(): void
    {
        // 這裡可以添加服務啟動時需要執行的邏輯
        // 例如：事件監聽器、中介軟體註冊等
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
        ];
    }
}