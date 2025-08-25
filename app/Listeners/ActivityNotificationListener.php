<?php

namespace App\Listeners;

use App\Events\ActivityLogged;
use App\Services\ActivityNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * 活動記錄通知監聽器
 * 
 * 監聽活動記錄事件並觸發相應的通知
 */
class ActivityNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    protected ActivityNotificationService $notificationService;

    /**
     * 建立事件監聽器
     */
    public function __construct(ActivityNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * 處理事件
     */
    public function handle(ActivityLogged $event): void
    {
        try {
            // 處理活動記錄通知
            $this->notificationService->handleActivityNotification($event->activity);

        } catch (\Exception $e) {
            Log::error('處理活動記錄通知時發生錯誤', [
                'activity_id' => $event->activity->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // 不重新拋出異常，避免影響主要的活動記錄流程
        }
    }

    /**
     * 監聽器失敗時的處理
     */
    public function failed(ActivityLogged $event, \Throwable $exception): void
    {
        Log::error('活動記錄通知監聽器執行失敗', [
            'activity_id' => $event->activity->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
