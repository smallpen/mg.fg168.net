<?php

namespace App\Jobs;

use App\Models\Activity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 批量非同步活動記錄工作
 */
class LogActivitiesBatchJob implements ShouldQueue
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
    public $timeout = 120;

    /**
     * 活動資料陣列
     *
     * @var array
     */
    protected array $activitiesData;

    /**
     * 建立新的工作實例
     *
     * @param array $activitiesData
     */
    public function __construct(array $activitiesData)
    {
        $this->activitiesData = $activitiesData;
    }

    /**
     * 執行工作
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            DB::beginTransaction();

            // 分批插入以避免記憶體問題
            foreach (array_chunk($this->activitiesData, 100) as $chunk) {
                Activity::insert($chunk);
            }

            DB::commit();

            Log::channel('activity')->info('Batch activities logged', [
                'count' => count($this->activitiesData),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to log activities batch', [
                'error' => $e->getMessage(),
                'count' => count($this->activitiesData),
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
        Log::error('Batch activity logging job failed permanently', [
            'error' => $exception->getMessage(),
            'count' => count($this->activitiesData),
        ]);
    }
}