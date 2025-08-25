<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Models\User;
use App\Services\ActivityNotificationService;
use Illuminate\Console\Command;

/**
 * 測試通知系統的 Console Command
 */
class TestNotificationSystemCommand extends Command
{
    /**
     * 命令名稱和參數
     */
    protected $signature = 'notifications:test 
                           {--user-id= : 指定使用者 ID}
                           {--activity-type=security : 活動類型}
                           {--risk-level=8 : 風險等級}';

    /**
     * 命令描述
     */
    protected $description = '測試通知系統功能';

    /**
     * 執行命令
     */
    public function handle(ActivityNotificationService $notificationService): int
    {
        $this->info('開始測試通知系統...');

        try {
            // 取得測試使用者
            $userId = $this->option('user-id');
            $user = $userId ? User::find($userId) : User::first();
            
            if (!$user) {
                $this->error('找不到測試使用者');
                return Command::FAILURE;
            }

            // 建立測試活動記錄
            $activity = Activity::create([
                'type' => $this->option('activity-type'),
                'description' => '這是一個測試活動記錄，用於驗證通知系統功能',
                'subject_type' => User::class,
                'subject_id' => $user->id,
                'causer_type' => User::class,
                'causer_id' => $user->id,
                'properties' => [
                    'test' => true,
                    'command' => 'notifications:test'
                ],
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Console Command',
                'result' => 'success',
                'risk_level' => (int) $this->option('risk-level'),
                'signature' => 'test_signature'
            ]);

            $this->info("已建立測試活動記錄 ID: {$activity->id}");

            // 觸發活動記錄事件
            event(new \App\Events\ActivityLogged($activity));
            
            // 也可以直接處理通知（用於測試）
            $this->info('處理通知中...');
            $notificationService->handleActivityNotification($activity);
            
            // 檢查是否有通知被建立
            $notificationCount = \App\Models\Notification::where('type', 'activity_log')->count();
            $this->info("通知建立數量: {$notificationCount}");

            $this->info('通知處理完成');

            // 顯示統計資訊
            $stats = $notificationService->getNotificationStatistics();
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

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('測試失敗：' . $e->getMessage());
            $this->line('錯誤詳情：' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
