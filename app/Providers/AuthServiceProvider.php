<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * 模型的政策對應
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * 註冊任何認證/授權服務
     */
    public function boot(): void
    {
        //
    }
}