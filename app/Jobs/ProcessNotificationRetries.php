<?php

namespace App\Jobs;

use App\Services\ActivityNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 處理通知重試的 Job
 * 
 * 定期檢查並處理失敗的通知重試
 */
class ProcessNotificationRetries implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Job 執行超時時間（秒）
     */
    public int $timeout = 300;

    /**
     * 最大重試次數
     */
    public int $tries = 3;

    /**
     * 建立新的 Job 實例
     */
    public function __construct()
    {
        // 設定佇列名稱
        $this->onQueue('notifications');
    }

    /**
     * 執行 Job
     */
    public function handle(ActivityNotificationService $notificationService): void
    {
        try {
            Log::info('開始處理通知重試');

            // 處理所有待重試的通知
            $notificationService->processRetries();

            Log::info('通知重試處理完成');

        } catch (\Exception $e) {
            Log::error('處理通知重試時發生錯誤', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // 重新拋出異常以觸發 Job 重試機制
            throw $e;
        }
    }

    /**
     * Job 失敗時的處理
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('通知重試 Job 執行失敗', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
