<?php

namespace App\Providers;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

/**
 * 資料存取層服務提供者
 * 
 * 註冊資料存取層介面與實作的綁定關係
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * 註冊服務
     */
    public function register(): void
    {
        // 綁定使用者資料存取層介面與實作
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
    }

    /**
     * 啟動服務
     */
    public function boot(): void
    {
        //
    }
}
