<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ActivityNotificationTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'key' => 'activity_notification',
                'name' => '活動記錄通知',
                'category' => NotificationTemplate::CATEGORY_SYSTEM,
                'subject' => '{notification_title}',
                'content' => '親愛的 {user_name}，

您收到一個新的活動記錄通知：

{notification_message}

如需查看詳細資訊，請點擊以下連結：
{activity_url}

通知優先級：{priority}
通知時間：{current_time}

此郵件由系統自動發送，請勿回覆。

{app_name} 管理團隊',
                'variables' => [
                    'user_name' => '使用者姓名',
                    'notification_title' => '通知標題',
                    'notification_message' => '通知訊息',
                    'activity_url' => '活動詳情連結',
                    'priority' => '通知優先級',
                    'current_time' => '當前時間',
                    'app_name' => '應用程式名稱'
                ],
                'is_active' => true,
                'is_system' => true,
                'description' => '用於發送活動記錄相關通知的郵件範本'
            ],
            [
                'key' => 'security_alert',
                'name' => '安全警報通知',
                'category' => NotificationTemplate::CATEGORY_SECURITY,
                'subject' => '🚨 安全警報：{notification_title}',
                'content' => '親愛的管理員，

系統偵測到重要的安全事件，需要您的立即關注：

{notification_message}

事件詳情：
- 時間：{current_time}
- 優先級：{priority}
- 相關活動：{activity_url}

請立即檢查此事件並採取必要的安全措施。

如有疑問，請聯繫系統管理員。

{app_name} 安全監控系統',
                'variables' => [
                    'notification_title' => '警報標題',
                    'notification_message' => '警報訊息',
                    'activity_url' => '相關活動連結',
                    'priority' => '警報優先級',
                    'current_time' => '當前時間',
                    'app_name' => '應用程式名稱'
                ],
                'is_active' => true,
                'is_system' => true,
                'description' => '用於發送安全警報的郵件範本'
            ],
            [
                'key' => 'login_failure_alert',
                'name' => '登入失敗警報',
                'category' => NotificationTemplate::CATEGORY_SECURITY,
                'subject' => '⚠️ 登入失敗警報',
                'content' => '親愛的管理員，

系統偵測到可疑的登入失敗嘗試：

{notification_message}

建議採取的行動：
1. 檢查是否為合法使用者的操作
2. 如發現異常，考慮封鎖相關 IP 位址
3. 通知相關使用者檢查帳號安全

監控時間：{current_time}

{app_name} 安全監控系統',
                'variables' => [
                    'notification_message' => '警報訊息',
                    'current_time' => '當前時間',
                    'app_name' => '應用程式名稱'
                ],
                'is_active' => true,
                'is_system' => true,
                'description' => '用於發送登入失敗警報的郵件範本'
            ],
            [
                'key' => 'suspicious_ip_alert',
                'name' => '可疑 IP 警報',
                'category' => NotificationTemplate::CATEGORY_SECURITY,
                'subject' => '🌐 可疑 IP 存取警報',
                'content' => '親愛的管理員，

系統偵測到來自可疑 IP 位址的存取嘗試：

{notification_message}

建議立即採取以下行動：
1. 檢查該 IP 位址的存取記錄
2. 確認是否為授權存取
3. 如有必要，將該 IP 加入黑名單

監控時間：{current_time}

{app_name} 安全監控系統',
                'variables' => [
                    'notification_message' => '警報訊息',
                    'current_time' => '當前時間',
                    'app_name' => '應用程式名稱'
                ],
                'is_active' => true,
                'is_system' => true,
                'description' => '用於發送可疑 IP 存取警報的郵件範本'
            ],
            [
                'key' => 'after_hours_activity',
                'name' => '非工作時間活動通知',
                'category' => NotificationTemplate::CATEGORY_SYSTEM,
                'subject' => '🌙 非工作時間活動通知',
                'content' => '親愛的管理員，

系統偵測到非工作時間的活動：

{notification_message}

請確認此活動是否為正常操作。如有疑慮，請進一步調查。

監控時間：{current_time}

{app_name} 監控系統',
                'variables' => [
                    'notification_message' => '活動訊息',
                    'current_time' => '當前時間',
                    'app_name' => '應用程式名稱'
                ],
                'is_active' => true,
                'is_system' => true,
                'description' => '用於發送非工作時間活動通知的郵件範本'
            ]
        ];

        foreach ($templates as $templateData) {
            NotificationTemplate::updateOrCreate(
                ['key' => $templateData['key']],
                $templateData
            );
        }

        $this->command->info('已建立 ' . count($templates) . ' 個活動通知郵件範本');
    }
}
