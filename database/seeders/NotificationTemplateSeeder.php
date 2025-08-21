<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NotificationTemplate;

/**
 * 通知範本種子檔案
 * 
 * 建立系統預設的通知範本
 */
class NotificationTemplateSeeder extends Seeder
{
    /**
     * 執行通知範本種子
     */
    public function run(): void
    {
        $templates = [
            [
                'key' => 'welcome',
                'name' => '歡迎郵件',
                'category' => NotificationTemplate::CATEGORY_USER,
                'subject' => '歡迎加入 {app_name}',
                'content' => "親愛的 {user_name}，\n\n歡迎加入 {app_name}！\n\n您的帳號已成功建立，現在可以開始使用我們的服務。\n\n登入資訊：\n- 使用者名稱：{username}\n- 登入網址：{login_url}\n\n如有任何問題，請隨時聯繫我們。\n\n祝好，\n{app_name} 團隊",
                'variables' => [
                    'app_name' => '應用程式名稱',
                    'user_name' => '使用者姓名',
                    'username' => '使用者名稱',
                    'login_url' => '登入網址',
                ],
                'description' => '新使用者註冊後發送的歡迎郵件',
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'key' => 'password_reset',
                'name' => '密碼重設',
                'category' => NotificationTemplate::CATEGORY_SECURITY,
                'subject' => '{app_name} - 密碼重設請求',
                'content' => "親愛的 {user_name}，\n\n我們收到您的密碼重設請求。\n\n請點擊以下連結重設您的密碼：\n{reset_link}\n\n此連結將在 {expires_in} 分鐘後失效。\n\n如果您沒有請求重設密碼，請忽略此郵件。為了您的帳號安全，建議您：\n- 定期更換密碼\n- 使用強密碼\n- 不要與他人分享密碼\n\n祝好，\n{app_name} 團隊",
                'variables' => [
                    'app_name' => '應用程式名稱',
                    'user_name' => '使用者姓名',
                    'reset_link' => '密碼重設連結',
                    'expires_in' => '連結有效時間（分鐘）',
                ],
                'description' => '使用者請求密碼重設時發送的郵件',
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'key' => 'account_locked',
                'name' => '帳號鎖定通知',
                'category' => NotificationTemplate::CATEGORY_SECURITY,
                'subject' => '{app_name} - 帳號安全警告',
                'content' => "親愛的 {user_name}，\n\n您的帳號因多次登入失敗而被暫時鎖定。\n\n鎖定詳情：\n- 鎖定時間：{lockout_duration} 分鐘\n- 鎖定原因：連續 {max_attempts} 次登入失敗\n- 鎖定時間：{locked_at}\n- 預計解鎖：{unlock_at}\n\n如果這不是您的操作，可能有人正在嘗試存取您的帳號。建議您：\n- 檢查是否有可疑活動\n- 更換密碼\n- 啟用雙因子認證\n\n如需協助，請立即聯繫我們。\n\n祝好，\n{app_name} 團隊",
                'variables' => [
                    'app_name' => '應用程式名稱',
                    'user_name' => '使用者姓名',
                    'lockout_duration' => '鎖定時間（分鐘）',
                    'max_attempts' => '最大嘗試次數',
                    'locked_at' => '鎖定時間',
                    'unlock_at' => '解鎖時間',
                ],
                'description' => '帳號被鎖定時發送的安全警告郵件',
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'key' => 'system_maintenance',
                'name' => '系統維護通知',
                'category' => NotificationTemplate::CATEGORY_SYSTEM,
                'subject' => '{app_name} - 系統維護通知',
                'content' => "親愛的用戶，\n\n我們將於 {maintenance_start} 進行系統維護。\n\n維護詳情：\n- 開始時間：{maintenance_start}\n- 預計時長：{maintenance_duration}\n- 維護內容：{maintenance_description}\n- 影響範圍：{affected_services}\n\n維護期間系統將暫時無法使用，造成不便敬請見諒。\n\n我們會盡快完成維護工作，並在完成後立即通知您。\n\n如有緊急問題，請聯繫：{emergency_contact}\n\n祝好，\n{app_name} 團隊",
                'variables' => [
                    'app_name' => '應用程式名稱',
                    'maintenance_start' => '維護開始時間',
                    'maintenance_duration' => '維護時長',
                    'maintenance_description' => '維護內容描述',
                    'affected_services' => '受影響的服務',
                    'emergency_contact' => '緊急聯繫方式',
                ],
                'description' => '系統維護前發送的通知郵件',
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'key' => 'security_alert',
                'name' => '安全警報',
                'category' => NotificationTemplate::CATEGORY_SECURITY,
                'subject' => '{app_name} - 重要安全警報',
                'content' => "親愛的 {user_name}，\n\n我們偵測到您的帳號有異常活動。\n\n警報詳情：\n- 事件類型：{alert_type}\n- 發生時間：{occurred_at}\n- IP 位址：{ip_address}\n- 位置：{location}\n- 裝置：{device_info}\n\n如果這是您的正常操作，請忽略此郵件。\n\n如果這不是您的操作，請立即：\n1. 更改您的密碼\n2. 檢查帳號設定\n3. 啟用雙因子認證\n4. 聯繫我們的支援團隊\n\n您的帳號安全對我們非常重要。\n\n祝好，\n{app_name} 安全團隊",
                'variables' => [
                    'app_name' => '應用程式名稱',
                    'user_name' => '使用者姓名',
                    'alert_type' => '警報類型',
                    'occurred_at' => '發生時間',
                    'ip_address' => 'IP 位址',
                    'location' => '地理位置',
                    'device_info' => '裝置資訊',
                ],
                'description' => '偵測到可疑活動時發送的安全警報',
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'key' => 'email_verification',
                'name' => '電子郵件驗證',
                'category' => NotificationTemplate::CATEGORY_USER,
                'subject' => '{app_name} - 請驗證您的電子郵件',
                'content' => "親愛的 {user_name}，\n\n感謝您註冊 {app_name}！\n\n為了確保您的帳號安全，請點擊以下連結驗證您的電子郵件地址：\n{verification_link}\n\n此連結將在 {expires_in} 小時後失效。\n\n驗證完成後，您就可以：\n- 完整使用所有功能\n- 接收重要通知\n- 確保帳號安全\n\n如果您沒有註冊此帳號，請忽略此郵件。\n\n祝好，\n{app_name} 團隊",
                'variables' => [
                    'app_name' => '應用程式名稱',
                    'user_name' => '使用者姓名',
                    'verification_link' => '驗證連結',
                    'expires_in' => '連結有效時間（小時）',
                ],
                'description' => '新使用者註冊後發送的電子郵件驗證',
                'is_system' => true,
                'is_active' => true,
            ],
        ];

        foreach ($templates as $templateData) {
            NotificationTemplate::updateOrCreate(
                ['key' => $templateData['key']],
                $templateData
            );
        }

        $this->command->info('已成功建立 ' . count($templates) . ' 個通知範本');
    }
}