<?php

namespace App\Jobs;

use App\Models\Activity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 非同步活動記錄工作
 */
class LogActivityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 工作最大嘗試次數
     *
     * @var int
     */
    public $tries = 3;

    /**
     * 工作超時時間（秒）
     *
     * @var int
     */
    public $timeout = 30;

    /**
     * 活動資料
     *
     * @var array
     */
    protected array $activityData;

    /**
     * 建立新的工作實例
     *
     * @param array $activityData
     */
    public function __construct(array $activityData)
    {
        $this->activityData = $activityData;
    }

    /**
     * 執行工作
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            Activity::create($this->activityData);
            
            Log::channel('activity')->info('Async activity logged', [
                'type' => $this->activityData['type'],
                'user_id' => $this->activityData['user_id'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log activity asynchronously', [
                'error' => $e->getMessage(),
                'activity_data' => $this->activityData,
            ]);
            
            throw $e;
        }
    }

    /**
     * 工作失敗時的處理
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Activity logging job failed permanently', [
            'error' => $exception->getMessage(),
            'activity_data' => $this->activityData,
        ]);
    }
}