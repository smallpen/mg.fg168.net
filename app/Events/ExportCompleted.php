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
 * 匯出完成事件
 */
class ExportCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $jobId;
    public array $result;

    /**
     * 建立新的事件實例
     */
    public function __construct(string $jobId, array $result)
    {
        $this->jobId = $jobId;
        $this->result = $result;
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
        return 'export.completed';
    }

    /**
     * 廣播資料
     */
    public function broadcastWith(): array
    {
        return [
            'job_id' => $this->jobId,
            'completed' => true,
            'download_url' => $this->result['download_url'],
            'filename' => $this->result['filename'],
            'record_count' => $this->result['record_count'],
            'file_size' => $this->result['file_size'],
            'timestamp' => now()->toDateTimeString(),
        ];
    }
}