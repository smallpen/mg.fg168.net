<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * 活動記錄複製工作
 * 
 * 負責將活動記錄複製到其他分片或備份資料庫
 */
class ActivityReplicationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 工作嘗試次數
     */
    public int $tries = 3;

    /**
     * 工作超時時間（秒）
     */
    public int $timeout = 60;

    /**
     * 目標分片
     */
    protected string $targetShard;

    /**
     * 活動資料
     */
    protected array $activityData;

    /**
     * 原始活動 ID
     */
    protected int $originalActivityId;

    /**
     * 建立新的工作實例
     */
    public function __construct(string $targetShard, array $activityData, int $originalActivityId)
    {
        $this->targetShard = $targetShard;
        $this->activityData = $activityData;
        $this->originalActivityId = $originalActivityId;
        
        // 設定佇列
        $this->onQueue('replication');
    }

    /**
     * 執行工作
     */
    public function handle(): void
    {
        try {
            Log::debug("開始複製活動記錄", [
                'target_shard' => $this->targetShard,
                'original_id' => $this->originalActivityId,
            ]);

            // 取得分片配置
            $shardConfig = config("database.connections.{$this->targetShard}");
            
            if (!$shardConfig) {
                throw new Exception("分片配置不存在: {$this->targetShard}");
            }

            // 準備複製資料
            $replicaData = array_merge($this->activityData, [
                'original_id' => $this->originalActivityId,
                'replica_shard' => $this->targetShard,
                'replicated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 執行複製
            DB::connection($this->targetShard)
                ->table('activities')
                ->insert($replicaData);

            Log::info("活動記錄複製成功", [
                'target_shard' => $this->targetShard,
                'original_id' => $this->originalActivityId,
            ]);

        } catch (Exception $e) {
            Log::error("活動記錄複製失敗", [
                'target_shard' => $this->targetShard,
                'original_id' => $this->originalActivityId,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // 如果達到最大嘗試次數，記錄到失敗表
            if ($this->attempts() >= $this->tries) {
                $this->recordReplicationFailure($e);
            }

            throw $e;
        }
    }

    /**
     * 工作失敗時的處理
     */
    public function failed(Exception $exception): void
    {
        Log::critical("活動記錄複製最終失敗", [
            'target_shard' => $this->targetShard,
            'original_id' => $this->originalActivityId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        $this->recordReplicationFailure($exception);
    }

    /**
     * 記錄複製失敗
     */
    protected function recordReplicationFailure(Exception $exception): void
    {
        try {
            DB::table('replication_failures')->insert([
                'target_shard' => $this->targetShard,
                'original_activity_id' => $this->originalActivityId,
                'activity_data' => json_encode($this->activityData),
                'error_message' => $exception->getMessage(),
                'attempts' => $this->attempts(),
                'failed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (Exception $e) {
            Log::error("記錄複製失敗資訊時發生錯誤", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}