<?php

namespace App\Events;

use App\Models\Activity;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 活動記錄事件
 * 
 * 當新的活動被記錄時觸發此事件
 */
class ActivityLogged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * 活動實例
     */
    public Activity $activity;

    /**
     * 建立新的事件實例
     */
    public function __construct(Activity $activity)
    {
        $this->activity = $activity;
    }
}