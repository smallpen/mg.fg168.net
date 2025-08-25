<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 匯出失敗事件
 */
class ExportFailed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $jobId;
    public string $error;

    /**
     * 建立新的事件實例
     */
    public function __construct(string $jobId, string $error)
    {
        $this->jobId = $jobId;
        $this->error = $error;
    }

    /**
     * 取得事件應該廣播的頻道
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('export-progress.' . $this->jobId),
        ];
    }

    /**
     * 廣播事件名稱
     */
    public function broadcastAs(): string
    {
        return 'export.failed';
    }

    /**
     * 廣播資料
     */
    public function broadcastWith(): array
    {
        return [
            'job_id' => $this->jobId,
            'failed' => true,
            'error' => $this->error,
            'timestamp' => now()->toDateTimeString(),
        ];
    }
}