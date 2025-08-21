<?php

namespace App\Mail;

use App\Models\SettingChange;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * 設定變更通知郵件
 */
class SettingChangeNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * 建立新的郵件實例
     */
    public function __construct(
        public SettingChange $settingChange,
        public string $action = 'changed'
    ) {
        //
    }

    /**
     * 取得郵件信封
     */
    public function envelope(): Envelope
    {
        $subject = match ($this->action) {
            'restored' => '系統設定已回復',
            'changed' => '系統設定已變更',
            default => '系統設定通知',
        };

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * 取得郵件內容定義
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.setting-change-notification',
            with: [
                'settingChange' => $this->settingChange,
                'action' => $this->action,
            ],
        );
    }

    /**
     * 取得郵件附件
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
