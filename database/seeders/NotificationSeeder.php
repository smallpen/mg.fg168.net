<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $notificationService = app(\App\Services\NotificationService::class);
        
        // 獲取所有使用者
        $users = \App\Models\User::all();
        
        if ($users->isEmpty()) {
            $this->command->warn('沒有找到使用者，請先執行 UserSeeder');
            return;
        }

        $this->command->info('開始建立通知資料...');

        foreach ($users as $user) {
            // 為每個使用者建立不同類型的通知
            
            // 安全通知
            $notificationService->createSecurityNotification(
                $user,
                '安全警報：異常登入嘗試',
                '檢測到來自 IP 192.168.1.100 的異常登入嘗試，請檢查您的帳號安全。',
                [
                    'data' => [
                        'ip_address' => '192.168.1.100',
                        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                        'location' => '台北市',
                    ],
                    'action_url' => '/admin/security/logs',
                ]
            );

            // 系統通知
            $notificationService->createSystemNotification(
                $user,
                '系統更新完成',
                '系統已成功更新到版本 2.1.0，新增多項功能改進。',
                [
                    'data' => [
                        'version' => '2.1.0',
                        'maintenance_duration' => '30 分鐘',
                    ],
                    'action_url' => '/admin/system/updates',
                ]
            );

            // 使用者操作通知
            $notificationService->createUserActionNotification(
                $user,
                '新使用者註冊',
                '使用者 張小明 已成功註冊並等待審核。',
                [
                    'data' => [
                        'target_user_id' => $user->id,
                        'action_type' => 'create',
                    ],
                    'action_url' => '/admin/users',
                ]
            );

            // 報告通知
            $notificationService->createReportNotification(
                $user,
                '每日統計報告',
                '今日系統共有 85 位使用者活躍，較昨日增長 12%。',
                [
                    'data' => [
                        'report_type' => 'daily',
                        'data_points' => 85,
                    ],
                    'action_url' => '/admin/reports/daily',
                ]
            );

            // 建立一些已讀的通知
            $readNotifications = \App\Models\Notification::factory()
                ->count(3)
                ->read()
                ->create(['user_id' => $user->id]);

            // 建立一些未讀的通知
            $unreadNotifications = \App\Models\Notification::factory()
                ->count(5)
                ->unread()
                ->create(['user_id' => $user->id]);

            // 建立一些高優先級通知
            $highPriorityNotifications = \App\Models\Notification::factory()
                ->count(2)
                ->highPriority()
                ->unread()
                ->create(['user_id' => $user->id]);

            $this->command->info("已為使用者 {$user->name} 建立通知");
        }

        $totalNotifications = \App\Models\Notification::count();
        $this->command->info("通知資料建立完成！總共建立了 {$totalNotifications} 筆通知");
    }
}
