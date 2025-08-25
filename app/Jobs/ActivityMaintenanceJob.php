<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\ActivityPartitionService;
use App\Services\ActivityCompressionService;
use App\Services\ActivityQueryOptimizer;
use Exception;

/**
 * 活動記錄維護工作
 * 
 * 定期執行活動記錄的維護任務，包含分區管理、壓縮、歸檔等
 */
class ActivityMaintenanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 工作嘗試次數
     */
    public int $tries = 1;

    /**
     * 工作超時時間（秒）
     */
    public int $timeout = 3600; // 1小時

    /**
     * 維護類型
     */
    protected string $maintenanceType;

    /**
     * 維護選項
     */
    protected array $options;

    /**
     * 建立新的工作實例
     */
    public function __construct(string $maintenanceType = 'full', array $options = [])
    {
        $this->maintenanceType = $maintenanceType;
        $this->options = $options;
        
        // 設定佇列
        $this->onQueue('maintenance');
    }

    /**
     * 執行工作
     */
    public function handle(): void
    {
        $startTime = microtime(true);
        
        try {
            Log::info("開始活動記錄維護", [
                'type' => $this->maintenanceType,
                'options' => $this->options,
            ]);

            $results = match ($this->maintenanceType) {
                'partition' => $this->performPartitionMaintenance(),
                'compression' => $this->performCompressionMaintenance(),
                'optimization' => $this->performOptimizationMaintenance(),
                'full' => $this->performFullMaintenance(),
                default => throw new Exception("未知的維護類型: {$this->maintenanceType}"),
            };

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::info("活動記錄維護完成", [
                'type' => $this->maintenanceType,
                'execution_time_ms' => $executionTime,
                'results' => $results,
            ]);

        } catch (Exception $e) {
            Log::error("活動記錄維護失敗", [
                'type' => $this->maintenanceType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * 執行分區維護
     */
    protected function performPartitionMaintenance(): array
    {
        $partitionService = app(ActivityPartitionService::class);
        return $partitionService->autoMaintenance();
    }

    /**
     * 執行壓縮維護
     */
    protected function performCompressionMaintenance(): array
    {
        $compressionService = app(ActivityCompressionService::class);
        return $compressionService->autoMaintenance();
    }

    /**
     * 執行最佳化維護
     */
    protected function performOptimizationMaintenance(): array
    {
        $optimizer = app(ActivityQueryOptimizer::class);
        
        $results = [
            'table_statistics' => $optimizer->optimizeTableStatistics(),
            'slow_queries' => $optimizer->monitorSlowQueries(),
            'index_suggestions' => $optimizer->suggestIndexOptimizations(),
        ];

        return $results;
    }

    /**
     * 執行完整維護
     */
    protected function performFullMaintenance(): array
    {
        $results = [
            'partition_maintenance' => $this->performPartitionMaintenance(),
            'compression_maintenance' => $this->performCompressionMaintenance(),
            'optimization_maintenance' => $this->performOptimizationMaintenance(),
        ];

        return $results;
    }

    /**
     * 工作失敗時的處理
     */
    public function failed(Exception $exception): void
    {
        Log::critical("活動記錄維護最終失敗", [
            'type' => $this->maintenanceType,
            'error' => $exception->getMessage(),
        ]);

        // 可以在這裡發送通知給管理員
        // 或者記錄到監控系統
    }
}