<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChannelIntegrityAlert extends Notification implements ShouldQueue
{
    use Queueable;

    private array $alert;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $alert)
    {
        $this->alert = $alert;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $severityIcon = match($this->alert['severity']) {
            'critical' => '🔴',
            'warning' => '🟡',
            'error' => '❌',
            default => '⚠️'
        };

        $severityText = match($this->alert['severity']) {
            'critical' => '嚴重',
            'warning' => '警告',
            'error' => '錯誤',
            default => '通知'
        };

        return (new MailMessage)
            ->subject("通路管理系統警報 - {$severityText}")
            ->greeting("系統監控警報 {$severityIcon}")
            ->line("檢測到通路管理系統異常狀況：")
            ->line("**警報類型：** {$this->getAlertTypeText()}")
            ->line("**嚴重程度：** {$severityText}")
            ->line("**詳細訊息：** {$this->alert['message']}")
            ->line("**發生時間：** {$this->alert['timestamp']}")
            ->when($this->hasDetailedData(), function ($mail) {
                return $mail->line("**詳細資料：**")
                           ->line($this->formatDetailedData());
            })
            ->action('查看系統監控', url('/admin/channels/monitoring'))
            ->line('請及時處理此問題以確保系統正常運行。')
            ->salutation('通路管理系統');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->alert['type'],
            'severity' => $this->alert['severity'],
            'message' => $this->alert['message'],
            'timestamp' => $this->alert['timestamp'],
            'data' => $this->alert['data'] ?? null
        ];
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }

    /**
     * 獲取警報類型文字
     */
    private function getAlertTypeText(): string
    {
        return match($this->alert['type']) {
            'points_consistency' => '點數一致性問題',
            'negative_balances' => '負餘額檢測',
            'orphaned_records' => '孤立記錄檢測',
            'transaction_integrity' => '交易完整性問題',
            'system_performance' => '系統效能問題',
            'data_growth' => '資料增長異常',
            default => $this->alert['type']
        };
    }

    /**
     * 檢查是否有詳細資料
     */
    private function hasDetailedData(): bool
    {
        return isset($this->alert['data']) && !empty($this->alert['data']);
    }

    /**
     * 格式化詳細資料
     */
    private function formatDetailedData(): string
    {
        if (!$this->hasDetailedData()) {
            return '';
        }

        $data = $this->alert['data'];
        $formatted = [];

        switch ($this->alert['type']) {
            case 'points_consistency':
                $formatted[] = "檢查代理數: {$data['agents_checked']}";
                $formatted[] = "問題代理數: {$data['agents_with_issues']}";
                $formatted[] = "總差異: {$data['total_discrepancy']}";
                break;

            case 'negative_balances':
                $formatted[] = "負餘額代理: {$data['negative_agents']}";
                $formatted[] = "負餘額玩家: {$data['negative_players']}";
                $formatted[] = "總計: {$data['total_negative']}";
                break;

            case 'orphaned_records':
                $formatted[] = "孤立玩家: {$data['orphaned_players']}";
                $formatted[] = "孤立交易: {$data['orphaned_transactions']}";
                $formatted[] = "總計: {$data['total_orphaned']}";
                break;

            case 'transaction_integrity':
                $formatted[] = "檢查交易數: {$data['sample_checked']}";
                $formatted[] = "錯誤數: {$data['errors_found']}";
                $formatted[] = "錯誤率: " . ($data['error_rate'] * 100) . "%";
                break;

            case 'system_performance':
                $formatted[] = "響應時間: {$data['response_time_ms']}ms";
                $formatted[] = "記憶體使用: {$data['memory_usage_mb']}MB";
                $formatted[] = "閾值: {$data['threshold_ms']}ms";
                break;

            default:
                foreach ($data as $key => $value) {
                    if (is_scalar($value)) {
                        $formatted[] = "{$key}: {$value}";
                    }
                }
        }

        return implode("\n", $formatted);
    }
}