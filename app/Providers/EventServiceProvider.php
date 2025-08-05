<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * 應用程式的事件監聽器對應
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * 註冊任何事件
     */
    public function boot(): void
    {
        //
    }

    /**
     * 判斷事件和監聽器是否應該自動發現
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}