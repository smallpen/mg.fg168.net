<?php

namespace App\Console\Commands;

use App\Jobs\ProcessNotificationRetries;
use App\Services\ActivityNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 處理通知重試的 Console Command
 * 
 * 可以手動執行或透過排程定期執行
 */
class ProcessNotificationRetriesCommand extends Command
{
    /**
     * 命令名稱和參數
     */
    protected $signature = 'notifications:process-retries 
                           {--queue : 使用佇列處理}
                           {--force : 強制處理所有重試}';

    /**
     * 命令描述
     */
    protected $description = '處理失敗的通知重試';

    /**
     * 執行命令
     */
    public function handle(ActivityNotificationService $notificationService): int
    {
        $this->info('開始處理通知重試...');

        try {
            if ($this->option('queue')) {
                // 使用佇列處理
                ProcessNotificationRetries::dispatch();
                $this->info('通知重試 Job 已加入佇列');
            } else {
                // 直接處理
                $notificationService->processRetries();
                $this->info('通知重試處理完成');
            }

            // 顯示統計資訊
            $this->displayStatistics($notificationService);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('處理通知重試時發生錯誤：' . $e->getMessage());
            Log::error('通知重試命令執行失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * 顯示統計資訊
     */
    protected function displayStatistics(ActivityNotificationService $notificationService): void
    {
        $stats = $notificationService->getNotificationStatistics();

        $this->newLine();
        $this->info('通知統計資訊：');
        $this->table(
            ['項目', '數量'],
            [
                ['總通知數', $stats['total_notifications']],
                ['未讀通知', $stats['unread_notifications']],
                ['今日通知', $stats['notifications_today']],
                ['啟用規則', $stats['active_rules']],
                ['待重試', $stats['pending_retries']],
            ]
        );
    }
}
