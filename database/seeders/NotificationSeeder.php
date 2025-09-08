<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUser = User::where('username', 'admin')->first();
        
        if (!$adminUser) {
            return;
        }

        $notifications = [
            [
                'user_id' => $adminUser->id,
                'type' => 'info',
                'title' => '系統維護通知',
                'message' => '系統將於今晚 23:00 進行例行維護，預計維護時間約 2 小時。',
                'priority' => 'normal',
                'icon' => 'fas fa-tools',
                'color' => 'text-blue-500',
            ],
            [
                'user_id' => $adminUser->id,
                'type' => 'warning',
                'title' => '密碼即將過期',
                'message' => '您的密碼將於 7 天後過期，請及時更新密碼以確保帳號安全。',
                'priority' => 'high',
                'icon' => 'fas fa-exclamation-triangle',
                'color' => 'text-yellow-500',
            ],
            [
                'user_id' => $adminUser->id,
                'type' => 'success',
                'title' => '備份完成',
                'message' => '系統資料備份已成功完成，備份檔案已儲存至安全位置。',
                'priority' => 'low',
                'icon' => 'fas fa-check-circle',
                'color' => 'text-green-500',
                'read_at' => now(),
            ],
            [
                'user_id' => $adminUser->id,
                'type' => 'error',
                'title' => '登入異常',
                'message' => '檢測到您的帳號有異常登入行為，請檢查帳號安全設定。',
                'priority' => 'urgent',
                'icon' => 'fas fa-shield-alt',
                'color' => 'text-red-500',
            ],
        ];

        foreach ($notifications as $notification) {
            Notification::create($notification);
        }
    }
}