<?php

namespace App\Notifications;

use App\Models\SettingChange;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * 敏感設定變更通知
 */
class SensitiveSettingChanged extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * 設定鍵值
     */
    protected string $settingKey;

    /**
     * 變更記錄
     */
    protected SettingChange $change;

    /**
     * 建立新的通知實例
     */
    public function __construct(string $settingKey, SettingChange $change)
    {
        $this->settingKey = $settingKey;
        $this->change = $change;
    }

    /**
     * 取得通知的傳送管道
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * 取得通知的郵件表示
     */
    public function toMail($notifiable): MailMessage
    {
        $settingName = $this->getSettingDisplayName($this->settingKey);
        $changedBy = $this->change->user->name ?? '未知使用者';
        
        return (new MailMessage)
            ->subject('敏感設定變更通知')
            ->greeting('您好！')
            ->line("系統中的敏感設定「{$settingName}」已被變更。")
            ->line("變更者：{$changedBy}")
            ->line("變更時間：{$this->change->created_at->format('Y-m-d H:i:s')}")
            ->line("IP 位址：{$this->change->ip_address}")
            ->when($this->change->reason, function ($message) {
                return $message->line("變更原因：{$this->change->reason}");
            })
            ->line('如果這不是您授權的變更，請立即檢查系統安全性。')
            ->action('查看變更詳情', route('admin.settings.changes', ['setting' => $this->settingKey]))
            ->line('謝謝您使用我們的系統！');
    }

    /**
     * 取得通知的陣列表示
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'sensitive_setting_changed',
            'setting_key' => $this->settingKey,
            'setting_name' => $this->getSettingDisplayName($this->settingKey),
            'changed_by' => $this->change->user->name ?? '未知使用者',
            'changed_at' => $this->change->created_at->toISOString(),
            'ip_address' => $this->change->ip_address,
            'reason' => $this->change->reason,
            'change_id' => $this->change->id,
        ];
    }

    /**
     * 取得設定的顯示名稱
     */
    protected function getSettingDisplayName(string $key): string
    {
        $settingNames = [
            'notification.smtp_password' => 'SMTP 密碼',
            'integration.google_client_secret' => 'Google Client Secret',
            'integration.facebook_app_secret' => 'Facebook App Secret',
            'integration.github_client_secret' => 'GitHub Client Secret',
            'integration.stripe_secret_key' => 'Stripe 秘密金鑰',
            'integration.stripe_webhook_secret' => 'Stripe Webhook 密鑰',
            'integration.paypal_client_secret' => 'PayPal Client Secret',
            'integration.aws_secret_key' => 'AWS 秘密存取金鑰',
            'integration.google_drive_client_secret' => 'Google Drive Client Secret',
            'integration.api_keys' => 'API 金鑰',
        ];

        return $settingNames[$key] ?? $key;
    }
}