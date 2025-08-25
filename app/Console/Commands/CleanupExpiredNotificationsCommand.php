<?php

namespace App\Console\Commands;

use App\Services\ActivityNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 清理過期通知的 Console Command
 */
class CleanupExpiredNotificationsCommand extends Command
{
    /**
     * 命令名稱和參數
     */
    protected $signature = 'notifications:cleanup 
                           {--days=30 : 保留天數}
                           {--dry-run : 僅顯示將要刪除的數量，不實際執行}';

    /**
     * 命令描述
     */
    protected $description = '清理過期的活動記錄通知';

    /**
     * 執行命令
     */
    public function handle(ActivityNotificationService $notificationService): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');

        $this->info("開始清理 {$days} 天前的通知...");

        try {
            if ($dryRun) {
                // 僅計算數量，不實際刪除
                $count = $this->getExpiredNotificationCount($days);
                $this->info("將會刪除 {$count} 個過期通知（僅預覽，未實際執行）");
            } else {
                // 實際執行清理
                $deletedCount = $notificationService->cleanupExpiredNotifications();
                $this->info("已刪除 {$deletedCount} 個過期通知");

                // 記錄到日誌
                Log::info('通知清理完成', [
                    'deleted_count' => $deletedCount,
                    'retention_days' => $days
                ]);
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('清理通知時發生錯誤：' . $e->getMessage());
            Log::error('通知清理失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * 取得過期通知數量（用於 dry-run）
     */
    protected function getExpiredNotificationCount(int $days): int
    {
        return \App\Models\Notification::where('type', 'activity_log')
            ->where('created_at', '<', now()->subDays($days))
            ->count();
    }
}
