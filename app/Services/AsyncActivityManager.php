<?php

namespace App\Services;

use App\Jobs\ActivityPerformanceMonitorJob;
use App\Services\AsyncActivityLogger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Console\Scheduling\Event;

/**
 * 非同步活動記錄管理服務
 * 
 * 統一管理非同步活動記錄系統的各個組件
 */
class AsyncActivityManager
{
    /**
     * 非同步記錄器
     */
    protected AsyncActivityLogger $asyncLogger;

    /**
     * 快取鍵前綴
     */
    protected string $cachePrefix = 'async_activity_manager';

    /**
     * 建構函式
     */
    public function __construct(AsyncActivityLogger $asyncLogger)
    {
        $this->asyncLogger = $asyncLogger;
    }

    /**
     * 啟動非同步記錄系統
     * 
     * @return array 啟動結果
     */
    public function start(): array
    {
        $startTime = microtime(true);
        $results = [];

        try {
            Log::channel('activity')->info('啟動非同步活動記錄系統');

            // 1. 檢查系統狀態
            $systemCheck = $this->performSystemCheck();
            $results['system_check'] = $systemCheck;

            if (!$systemCheck['ready']) {
                throw new \Exception('系統檢查失敗: ' . implode(', ', $systemCheck['issues']));
            }

            // 2. 初始化效能監控
            $this->initializePerformanceMonitoring();
            $results['performance_monitoring'] = 'initialized';

            // 3. 設定排程任務
            $this->setupScheduledTasks();
            $results['scheduled_tasks'] = 'configured';

            // 4. 清理舊資料
            $cleanupResult = $this->performInitialCleanup();
            $results['cleanup'] = $cleanupResult;

            // 5. 記錄啟動狀態
            $this->recordStartupStatus(true);

            $startupTime = (microtime(true) - $startTime) * 1000;
            $results['startup_time_ms'] = $startupTime;
            $results['status'] = 'success';

            Log::channel('activity')->info('非同步活動記錄系統啟動成功', $results);

        } catch (\Exception $e) {
            $results['status'] = 'failed';
            $results['error'] = $e->getMessage();

            Log::error('非同步活動記錄系統啟動失敗', [
                'error' => $e->getMessage(),
                'results' => $results,
            ]);

            $this->recordStartupStatus(false, $e->getMessage());
        }

        return $results;
    }

    /**
     * 停止非同步記錄系統
     * 
     * @return array 停止結果
     */
    public function stop(): array
    {
        $stopTime = microtime(true);
        $results = [];

        try {
            Log::channel('activity')->info('停止非同步活動記錄系統');

            // 1. 處理剩餘的批量記錄
            $flushedCount = $this->asyncLogger->flushBatch();
            $results['flushed_activities'] = $flushedCount;

            // 2. 生成最終效能報告
            $this->generateFinalReport();
            $results['final_report'] = 'generated';

            // 3. 清理快取資料
            $this->cleanupCacheData();
            $results['cache_cleanup'] = 'completed';

            // 4. 記錄停止狀態
            $this->recordShutdownStatus(true);

            $shutdownTime = (microtime(true) - $stopTime) * 1000;
            $results['shutdown_time_ms'] = $shutdownTime;
            $results['status'] = 'success';

            Log::channel('activity')->info('非同步活動記錄系統停止成功', $results);

        } catch (\Exception $e) {
            $results['status'] = 'failed';
            $results['error'] = $e->getMessage();

            Log::error('非同步活動記錄系統停止失敗', [
                'error' => $e->getMessage(),
                'results' => $results,
            ]);

            $this->recordShutdownStatus(false, $e->getMessage());
        }

        return $results;
    }

    /**
     * 取得系統狀態
     * 
     * @return array
     */
    public function getSystemStatus(): array
    {
        return [
            'is_running' => $this->isRunning(),
            'queue_stats' => $this->asyncLogger->getQueueStats(),
            'performance_metrics' => $this->asyncLogger->getPerformanceMetrics(),
            'health_status' => $this->asyncLogger->monitorQueueHealth(),
            'last_startup' => $this->getLastStartupTime(),
            'uptime_hours' => $this->getUptimeHours(),
        ];
    }

    /**
     * 執行系統維護
     * 
     * @return array
     */
    public function performMaintenance(): array
    {
        $maintenanceTime = microtime(true);
        $results = [];

        try {
            Log::channel('activity')->info('開始系統維護');

            // 1. 清理過期效能資料
            $cleanedCount = $this->asyncLogger->cleanupPerformanceData(7);
            $results['cleaned_performance_data'] = $cleanedCount;

            // 2. 優化佇列
            $queueOptimization = $this->optimizeQueues();
            $results['queue_optimization'] = $queueOptimization;

            // 3. 檢查系統健康狀態
            $healthCheck = $this->performHealthCheck();
            $results['health_check'] = $healthCheck;

            // 4. 生成維護報告
            $maintenanceReport = $this->generateMaintenanceReport($results);
            $results['maintenance_report'] = $maintenanceReport;

            $maintenanceTime = (microtime(true) - $maintenanceTime) * 1000;
            $results['maintenance_time_ms'] = $maintenanceTime;
            $results['status'] = 'success';

            Log::channel('activity')->info('系統維護完成', $results);

        } catch (\Exception $e) {
            $results['status'] = 'failed';
            $results['error'] = $e->getMessage();

            Log::error('系統維護失敗', [
                'error' => $e->getMessage(),
                'results' => $results,
            ]);
        }

        return $results;
    }

    /**
     * 重新啟動系統
     * 
     * @return array
     */
    public function restart(): array
    {
        $stopResult = $this->stop();
        sleep(2); // 等待 2 秒
        $startResult = $this->start();

        return [
            'stop_result' => $stopResult,
            'start_result' => $startResult,
            'restart_successful' => $stopResult['status'] === 'success' && $startResult['status'] === 'success',
        ];
    }

    /**
     * 執行系統檢查
     * 
     * @return array
     */
    protected function performSystemCheck(): array
    {
        $issues = [];
        $checks = [];

        // 檢查資料庫連線
        try {
            \DB::select('SELECT 1');
            $checks['database'] = 'ok';
        } catch (\Exception $e) {
            $checks['database'] = 'failed';
            $issues[] = '資料庫連線失敗';
        }

        // 檢查快取系統
        try {
            Cache::put('system_check', 'ok', 60);
            $checks['cache'] = Cache::get('system_check') === 'ok' ? 'ok' : 'failed';
            if ($checks['cache'] === 'failed') {
                $issues[] = '快取系統異常';
            }
        } catch (\Exception $e) {
            $checks['cache'] = 'failed';
            $issues[] = '快取系統連線失敗';
        }

        // 檢查佇列系統
        try {
            $queueSize = \Queue::size('activities');
            $checks['queue'] = 'ok';
        } catch (\Exception $e) {
            $checks['queue'] = 'failed';
            $issues[] = '佇列系統異常';
        }

        // 檢查儲存空間
        $freeSpace = disk_free_space(storage_path());
        $totalSpace = disk_total_space(storage_path());
        $usagePercent = (($totalSpace - $freeSpace) / $totalSpace) * 100;
        
        if ($usagePercent > 90) {
            $checks['storage'] = 'warning';
            $issues[] = '儲存空間不足';
        } else {
            $checks['storage'] = 'ok';
        }

        // 檢查記憶體
        $memoryLimit = ini_get('memory_limit');
        $memoryUsage = memory_get_usage(true);
        
        if ($memoryUsage > (512 * 1024 * 1024)) { // 512MB
            $checks['memory'] = 'warning';
            $issues[] = '記憶體使用量過高';
        } else {
            $checks['memory'] = 'ok';
        }

        return [
            'ready' => empty($issues),
            'checks' => $checks,
            'issues' => $issues,
        ];
    }

    /**
     * 初始化效能監控
     * 
     * @return void
     */
    protected function initializePerformanceMonitoring(): void
    {
        // 啟動效能監控工作
        dispatch(new ActivityPerformanceMonitorJob('1h', 'all'))
            ->onQueue('activities-monitoring');

        // 設定監控指標初始值
        $initialMetrics = [
            'system_started_at' => now()->toISOString(),
            'total_jobs_processed' => 0,
            'total_jobs_failed' => 0,
            'average_processing_time' => 0,
        ];

        Cache::put("{$this->cachePrefix}:metrics", $initialMetrics, 86400);
    }

    /**
     * 設定排程任務
     * 
     * @return void
     */
    protected function setupScheduledTasks(): void
    {
        // 這些任務應該在 App\Console\Kernel 中定義
        // 這裡只是記錄設定
        $tasks = [
            'performance_monitoring' => '每 5 分鐘執行效能監控',
            'batch_processing' => '每分鐘處理批量記錄',
            'cleanup_old_data' => '每日清理過期資料',
            'health_check' => '每 10 分鐘執行健康檢查',
        ];

        Cache::put("{$this->cachePrefix}:scheduled_tasks", $tasks, 86400);
    }

    /**
     * 執行初始清理
     * 
     * @return array
     */
    protected function performInitialCleanup(): array
    {
        $results = [];

        // 清理過期的效能資料
        $cleanedPerformance = $this->asyncLogger->cleanupPerformanceData(7);
        $results['performance_data_cleaned'] = $cleanedPerformance;

        // 清理過期的快取
        $this->cleanupExpiredCache();
        $results['cache_cleaned'] = true;

        return $results;
    }

    /**
     * 記錄啟動狀態
     * 
     * @param bool $success
     * @param string|null $error
     * @return void
     */
    protected function recordStartupStatus(bool $success, ?string $error = null): void
    {
        $status = [
            'success' => $success,
            'timestamp' => now()->toISOString(),
            'error' => $error,
        ];

        Cache::put("{$this->cachePrefix}:startup_status", $status, 86400);
        
        if ($success) {
            Cache::put("{$this->cachePrefix}:is_running", true, 86400);
            Cache::put("{$this->cachePrefix}:last_startup", now()->toISOString(), 86400);
        }
    }

    /**
     * 記錄關閉狀態
     * 
     * @param bool $success
     * @param string|null $error
     * @return void
     */
    protected function recordShutdownStatus(bool $success, ?string $error = null): void
    {
        $status = [
            'success' => $success,
            'timestamp' => now()->toISOString(),
            'error' => $error,
        ];

        Cache::put("{$this->cachePrefix}:shutdown_status", $status, 86400);
        
        if ($success) {
            Cache::forget("{$this->cachePrefix}:is_running");
        }
    }

    /**
     * 檢查系統是否運行中
     * 
     * @return bool
     */
    protected function isRunning(): bool
    {
        return Cache::has("{$this->cachePrefix}:is_running");
    }

    /**
     * 取得最後啟動時間
     * 
     * @return string|null
     */
    protected function getLastStartupTime(): ?string
    {
        return Cache::get("{$this->cachePrefix}:last_startup");
    }

    /**
     * 取得運行時間（小時）
     * 
     * @return float
     */
    protected function getUptimeHours(): float
    {
        $startupTime = $this->getLastStartupTime();
        
        if (!$startupTime) {
            return 0;
        }
        
        return now()->diffInHours(\Carbon\Carbon::parse($startupTime));
    }

    /**
     * 優化佇列
     * 
     * @return array
     */
    protected function optimizeQueues(): array
    {
        $results = [];

        // 檢查佇列大小並建議優化
        $queueStats = $this->asyncLogger->getQueueStats();
        
        foreach ($queueStats['queues'] as $queue => $size) {
            if ($size > 1000) {
                $results[$queue] = [
                    'size' => $size,
                    'recommendation' => '建議增加 worker 數量',
                    'priority' => 'high',
                ];
            } elseif ($size > 500) {
                $results[$queue] = [
                    'size' => $size,
                    'recommendation' => '監控佇列增長趨勢',
                    'priority' => 'medium',
                ];
            } else {
                $results[$queue] = [
                    'size' => $size,
                    'status' => 'healthy',
                    'priority' => 'low',
                ];
            }
        }

        return $results;
    }

    /**
     * 執行健康檢查
     * 
     * @return array
     */
    protected function performHealthCheck(): array
    {
        return $this->asyncLogger->monitorQueueHealth();
    }

    /**
     * 生成維護報告
     * 
     * @param array $results
     * @return string
     */
    protected function generateMaintenanceReport(array $results): string
    {
        $reportId = 'maintenance_' . now()->format('Y-m-d_H-i-s');
        
        $report = [
            'id' => $reportId,
            'timestamp' => now()->toISOString(),
            'results' => $results,
            'system_status' => $this->getSystemStatus(),
        ];

        $reportKey = "{$this->cachePrefix}:maintenance_reports:{$reportId}";
        Cache::put($reportKey, $report, 86400 * 7); // 保存 7 天

        return $reportId;
    }

    /**
     * 生成最終報告
     * 
     * @return void
     */
    protected function generateFinalReport(): void
    {
        $report = [
            'shutdown_time' => now()->toISOString(),
            'uptime_hours' => $this->getUptimeHours(),
            'final_stats' => $this->asyncLogger->getQueueStats(),
            'performance_summary' => $this->asyncLogger->getPerformanceMetrics(),
        ];

        $reportKey = "{$this->cachePrefix}:final_report:" . now()->format('Y-m-d');
        Cache::put($reportKey, $report, 86400 * 30); // 保存 30 天
    }

    /**
     * 清理快取資料
     * 
     * @return void
     */
    protected function cleanupCacheData(): void
    {
        // 清理運行狀態相關的快取
        $keys = [
            "{$this->cachePrefix}:is_running",
            "{$this->cachePrefix}:metrics",
            "{$this->cachePrefix}:scheduled_tasks",
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * 清理過期快取
     * 
     * @return void
     */
    protected function cleanupExpiredCache(): void
    {
        // 清理過期的活動記錄相關快取
        $patterns = [
            'activity_batch_stats:*',
            'activity_job_stats:*',
            'activity_performance_metrics:*',
        ];

        foreach ($patterns as $pattern) {
            try {
                $keys = Cache::getRedis()->keys($pattern);
                foreach ($keys as $key) {
                    $data = Cache::get($key);
                    if (isset($data['timestamp'])) {
                        $age = now()->diffInHours(\Carbon\Carbon::parse($data['timestamp']));
                        if ($age > 24) { // 清理超過 24 小時的資料
                            Cache::forget($key);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('清理快取時發生錯誤', ['pattern' => $pattern, 'error' => $e->getMessage()]);
            }
        }
    }
}