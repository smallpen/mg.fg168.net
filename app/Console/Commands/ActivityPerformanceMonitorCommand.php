<?php

namespace App\Console\Commands;

use App\Jobs\ActivityPerformanceMonitorJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 活動記錄效能監控排程命令
 */
class ActivityPerformanceMonitorCommand extends Command
{
    /**
     * 命令名稱和簽名
     *
     * @var string
     */
    protected $signature = 'activity-log:monitor 
                            {--time-range=1h : 監控時間範圍 (1h, 6h, 24h)}
                            {--monitor-type=all : 監控類型 (queue, database, performance, health, all)}
                            {--queue=activities-monitoring : 執行佇列}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '執行活動記錄效能監控';

    /**
     * 執行命令
     *
     * @return int
     */
    public function handle(): int
    {
        $timeRange = $this->option('time-range');
        $monitorType = $this->option('monitor-type');
        $queue = $this->option('queue');

        try {
            // 檢查是否啟用效能監控
            if (!config('activity-log.async.performance.enabled', true)) {
                $this->info('效能監控已停用');
                return 0;
            }

            $this->info("正在分派效能監控工作 (範圍: {$timeRange}, 類型: {$monitorType})");

            // 分派監控工作
            dispatch(new ActivityPerformanceMonitorJob($timeRange, $monitorType))
                ->onQueue($queue);

            $this->info('✅ 效能監控工作已成功分派');

            Log::channel('activity')->info('效能監控工作已分派', [
                'time_range' => $timeRange,
                'monitor_type' => $monitorType,
                'queue' => $queue,
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error("分派效能監控工作失敗: {$e->getMessage()}");
            
            Log::error('分派效能監控工作失敗', [
                'error' => $e->getMessage(),
                'time_range' => $timeRange,
                'monitor_type' => $monitorType,
            ]);

            return 1;
        }
    }
}