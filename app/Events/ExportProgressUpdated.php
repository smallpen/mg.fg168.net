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
 * 匯出進度更新事件
 */
class ExportProgressUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $jobId;
    public int $progress;
    public string $status;

    /**
     * 建立新的事件實例
     */
    public function __construct(string $jobId, int $progress, string $status)
    {
        $this->jobId = $jobId;
        $this->progress = $progress;
        $this->status = $status;
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
        return 'export.progress.updated';
    }

    /**
     * 廣播資料
     */
    public function broadcastWith(): array
    {
        return [
            'job_id' => $this->jobId,
            'progress' => $this->progress,
            'status' => $this->status,
            'completed' => $this->progress >= 100,
            'timestamp' => now()->toDateTimeString(),
        ];
    }
}