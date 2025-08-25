<?php

namespace App\Jobs;

use App\Services\PerformanceMonitoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

/**
 * 活動記錄效能監控工作
 * 
 * 定期收集和分析活動記錄系統的效能指標
 */
class ActivityPerformanceMonitorJob implements ShouldQueue
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
    public $timeout = 300;

    /**
     * 監控時間範圍
     *
     * @var string
     */
    protected string $timeRange;

    /**
     * 監控類型
     *
     * @var string
     */
    protected string $monitorType;

    /**
     * 建立新的工作實例
     *
     * @param string $timeRange 時間範圍 (1h, 6h, 24h)
     * @param string $monitorType 監控類型 (queue, database, performance, health)
     */
    public function __construct(string $timeRange = '1h', string $monitorType = 'all')
    {
        $this->timeRange = $timeRange;
        $this->monitorType = $monitorType;
    }

    /**
     * 執行工作
     *
     * @return void
     */
    public function handle(): void
    {
        $startTime = microtime(true);
        
        try {
            Log::channel('activity')->info('開始活動記錄效能監控', [
                'time_range' => $this->timeRange,
                'monitor_type' => $this->monitorType,
            ]);

            $metrics = [];

            // 根據監控類型執行不同的監控任務
            switch ($this->monitorType) {
                case 'queue':
                    $metrics = $this->monitorQueuePerformance();
                    break;
                case 'database':
                    $metrics = $this->monitorDatabasePerformance();
                    break;
                case 'performance':
                    $metrics = $this->monitorSystemPerformance();
                    break;
                case 'health':
                    $metrics = $this->monitorSystemHealth();
                    break;
                case 'all':
                default:
                    $metrics = array_merge(
                        $this->monitorQueuePerformance(),
                        $this->monitorDatabasePerformance(),
                        $this->monitorSystemPerformance(),
                        $this->monitorSystemHealth()
                    );
                    break;
            }

            // 儲存監控結果
            $this->storeMetrics($metrics);

            // 檢查警告條件
            $alerts = $this->checkAlertConditions($metrics);
            if (!empty($alerts)) {
                $this->handleAlerts($alerts);
            }

            // 生成效能報告
            $this->generatePerformanceReport($metrics);

            $processingTime = (microtime(true) - $startTime) * 1000;

            Log::channel('activity')->info('活動記錄效能監控完成', [
                'time_range' => $this->timeRange,
                'monitor_type' => $this->monitorType,
                'metrics_count' => count($metrics),
                'alerts_count' => count($alerts),
                'processing_time_ms' => $processingTime,
            ]);

        } catch (\Exception $e) {
            Log::error('活動記錄效能監控失敗', [
                'error' => $e->getMessage(),
                'time_range' => $this->timeRange,
                'monitor_type' => $this->monitorType,
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }

    /**
     * 監控佇列效能
     *
     * @return array
     */
    protected function monitorQueuePerformance(): array
    {
        $metrics = [];

        try {
            // 佇列大小監控
            $queues = ['activities', 'activities-high', 'activities-batch'];
            foreach ($queues as $queue) {
                $size = Queue::size($queue);
                $metrics["queue_size_{$queue}"] = $size;
            }

            // 失敗工作監控
            $failedJobsCount = DB::table('failed_jobs')->count();
            $metrics['failed_jobs_total'] = $failedJobsCount;

            // 最近失敗的工作
            $recentFailures = DB::table('failed_jobs')
                ->where('failed_at', '>=', now()->subHours(1))
                ->count();
            $metrics['failed_jobs_recent_1h'] = $recentFailures;

            // 佇列處理速率
            $processingRate = $this->calculateQueueProcessingRate();
            $metrics['queue_processing_rate'] = $processingRate;

            // 平均等待時間
            $avgWaitTime = $this->calculateAverageWaitTime();
            $metrics['queue_avg_wait_time'] = $avgWaitTime;

        } catch (\Exception $e) {
            Log::warning('佇列效能監控部分失敗', ['error' => $e->getMessage()]);
        }

        return $metrics;
    }

    /**
     * 監控資料庫效能
     *
     * @return array
     */
    protected function monitorDatabasePerformance(): array
    {
        $metrics = [];

        try {
            // 活動記錄表大小
            $tableSize = DB::select("
                SELECT 
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                FROM information_schema.tables 
                WHERE table_schema = DATABASE() 
                AND table_name = 'activities'
            ");
            $metrics['activities_table_size_mb'] = $tableSize[0]->size_mb ?? 0;

            // 記錄數量統計
            $totalRecords = DB::table('activities')->count();
            $metrics['activities_total_records'] = $totalRecords;

            // 最近 1 小時的記錄數量
            $recentRecords = DB::table('activities')
                ->where('created_at', '>=', now()->subHour())
                ->count();
            $metrics['activities_recent_1h'] = $recentRecords;

            // 平均插入時間（從快取中取得）
            $avgInsertTime = Cache::get('activity_avg_insert_time', 0);
            $metrics['activities_avg_insert_time_ms'] = $avgInsertTime;

            // 慢查詢監控
            $slowQueries = $this->getSlowQueriesCount();
            $metrics['slow_queries_count'] = $slowQueries;

            // 資料庫連線數
            $connections = DB::select("SHOW STATUS LIKE 'Threads_connected'");
            $metrics['db_connections'] = $connections[0]->Value ?? 0;

            // 資料庫鎖等待
            $lockWaits = DB::select("SHOW STATUS LIKE 'Table_locks_waited'");
            $metrics['db_lock_waits'] = $lockWaits[0]->Value ?? 0;

        } catch (\Exception $e) {
            Log::warning('資料庫效能監控部分失敗', ['error' => $e->getMessage()]);
        }

        return $metrics;
    }

    /**
     * 監控系統效能
     *
     * @return array
     */
    protected function monitorSystemPerformance(): array
    {
        $metrics = [];

        try {
            // 記憶體使用量
            $memoryUsage = memory_get_usage(true);
            $memoryPeak = memory_get_peak_usage(true);
            $metrics['memory_usage_mb'] = round($memoryUsage / 1024 / 1024, 2);
            $metrics['memory_peak_mb'] = round($memoryPeak / 1024 / 1024, 2);

            // CPU 使用率（如果可用）
            if (function_exists('sys_getloadavg')) {
                $load = sys_getloadavg();
                $metrics['cpu_load_1min'] = $load[0] ?? 0;
                $metrics['cpu_load_5min'] = $load[1] ?? 0;
                $metrics['cpu_load_15min'] = $load[2] ?? 0;
            }

            // 磁碟使用量
            $diskFree = disk_free_space(storage_path());
            $diskTotal = disk_total_space(storage_path());
            $diskUsage = (($diskTotal - $diskFree) / $diskTotal) * 100;
            $metrics['disk_usage_percent'] = round($diskUsage, 2);

            // 快取命中率
            $cacheHitRate = $this->calculateCacheHitRate();
            $metrics['cache_hit_rate'] = $cacheHitRate;

            // 回應時間統計
            $responseTimeStats = $this->getResponseTimeStats();
            $metrics = array_merge($metrics, $responseTimeStats);

        } catch (\Exception $e) {
            Log::warning('系統效能監控部分失敗', ['error' => $e->getMessage()]);
        }

        return $metrics;
    }

    /**
     * 監控系統健康狀態
     *
     * @return array
     */
    protected function monitorSystemHealth(): array
    {
        $metrics = [];

        try {
            // 錯誤率統計
            $errorRate = $this->calculateErrorRate();
            $metrics['error_rate_percent'] = $errorRate;

            // 成功率統計
            $successRate = 100 - $errorRate;
            $metrics['success_rate_percent'] = $successRate;

            // 系統可用性
            $uptime = $this->getSystemUptime();
            $metrics['system_uptime_hours'] = $uptime;

            // 服務健康檢查
            $serviceHealth = $this->checkServiceHealth();
            $metrics = array_merge($metrics, $serviceHealth);

            // 資源使用警告
            $resourceWarnings = $this->checkResourceWarnings();
            $metrics['resource_warnings_count'] = count($resourceWarnings);

        } catch (\Exception $e) {
            Log::warning('系統健康監控部分失敗', ['error' => $e->getMessage()]);
        }

        return $metrics;
    }

    /**
     * 儲存監控指標
     *
     * @param array $metrics
     * @return void
     */
    protected function storeMetrics(array $metrics): void
    {
        $timestamp = now();
        $cacheKey = "activity_performance_metrics:{$this->timeRange}:" . $timestamp->format('Y-m-d-H');

        // 儲存到快取
        $existingMetrics = Cache::get($cacheKey, []);
        $existingMetrics[$timestamp->format('i')] = array_merge($metrics, [
            'timestamp' => $timestamp->toISOString(),
            'monitor_type' => $this->monitorType,
        ]);

        Cache::put($cacheKey, $existingMetrics, 86400); // 24小時

        // 更新效能監控服務
        try {
            $performanceService = app(PerformanceMonitoringService::class);
            foreach ($metrics as $metric => $value) {
                if (is_numeric($value)) {
                    $performanceService->recordMetric($metric, $value, [
                        'monitor_type' => $this->monitorType,
                        'time_range' => $this->timeRange,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::warning('無法更新效能監控服務', ['error' => $e->getMessage()]);
        }
    }

    /**
     * 檢查警告條件
     *
     * @param array $metrics
     * @return array
     */
    protected function checkAlertConditions(array $metrics): array
    {
        $alerts = [];

        // 佇列大小警告
        foreach (['activities', 'activities-high', 'activities-batch'] as $queue) {
            $size = $metrics["queue_size_{$queue}"] ?? 0;
            if ($size > 1000) {
                $alerts[] = [
                    'type' => 'queue_overload',
                    'severity' => $size > 5000 ? 'critical' : 'warning',
                    'message' => "佇列 {$queue} 積壓過多工作: {$size}",
                    'metric' => "queue_size_{$queue}",
                    'value' => $size,
                    'threshold' => 1000,
                ];
            }
        }

        // 錯誤率警告
        $errorRate = $metrics['error_rate_percent'] ?? 0;
        if ($errorRate > 5) {
            $alerts[] = [
                'type' => 'high_error_rate',
                'severity' => $errorRate > 10 ? 'critical' : 'warning',
                'message' => "錯誤率過高: {$errorRate}%",
                'metric' => 'error_rate_percent',
                'value' => $errorRate,
                'threshold' => 5,
            ];
        }

        // 記憶體使用警告
        $memoryUsage = $metrics['memory_usage_mb'] ?? 0;
        if ($memoryUsage > 512) {
            $alerts[] = [
                'type' => 'high_memory_usage',
                'severity' => $memoryUsage > 1024 ? 'critical' : 'warning',
                'message' => "記憶體使用量過高: {$memoryUsage}MB",
                'metric' => 'memory_usage_mb',
                'value' => $memoryUsage,
                'threshold' => 512,
            ];
        }

        // 磁碟使用警告
        $diskUsage = $metrics['disk_usage_percent'] ?? 0;
        if ($diskUsage > 80) {
            $alerts[] = [
                'type' => 'high_disk_usage',
                'severity' => $diskUsage > 90 ? 'critical' : 'warning',
                'message' => "磁碟使用量過高: {$diskUsage}%",
                'metric' => 'disk_usage_percent',
                'value' => $diskUsage,
                'threshold' => 80,
            ];
        }

        return $alerts;
    }

    /**
     * 處理警告
     *
     * @param array $alerts
     * @return void
     */
    protected function handleAlerts(array $alerts): void
    {
        foreach ($alerts as $alert) {
            // 記錄警告
            $logLevel = $alert['severity'] === 'critical' ? 'critical' : 'warning';
            Log::channel('activity')->{$logLevel}('活動記錄系統效能警告', $alert);

            // 儲存警告到快取
            $this->storeAlert($alert);

            // 發送通知（如果是嚴重警告）
            if ($alert['severity'] === 'critical') {
                $this->sendCriticalAlert($alert);
            }
        }
    }

    /**
     * 生成效能報告
     *
     * @param array $metrics
     * @return void
     */
    protected function generatePerformanceReport(array $metrics): void
    {
        $report = [
            'timestamp' => now()->toISOString(),
            'time_range' => $this->timeRange,
            'monitor_type' => $this->monitorType,
            'metrics' => $metrics,
            'summary' => $this->generateSummary($metrics),
            'recommendations' => $this->generateRecommendations($metrics),
        ];

        // 儲存報告
        $reportKey = "activity_performance_report:" . now()->format('Y-m-d-H-i');
        Cache::put($reportKey, $report, 86400); // 24小時

        Log::channel('activity')->info('效能報告已生成', [
            'report_key' => $reportKey,
            'metrics_count' => count($metrics),
        ]);
    }

    /**
     * 計算佇列處理速率
     *
     * @return float
     */
    protected function calculateQueueProcessingRate(): float
    {
        $cacheKey = 'activity_queue_processing_rate';
        return Cache::get($cacheKey, 0.0);
    }

    /**
     * 計算平均等待時間
     *
     * @return float
     */
    protected function calculateAverageWaitTime(): float
    {
        $cacheKey = 'activity_queue_avg_wait_time';
        return Cache::get($cacheKey, 0.0);
    }

    /**
     * 取得慢查詢數量
     *
     * @return int
     */
    protected function getSlowQueriesCount(): int
    {
        try {
            $result = DB::select("SHOW STATUS LIKE 'Slow_queries'");
            return (int) ($result[0]->Value ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * 計算快取命中率
     *
     * @return float
     */
    protected function calculateCacheHitRate(): float
    {
        // 這裡應該根據實際的快取系統實作
        return 95.0; // 預設值
    }

    /**
     * 取得回應時間統計
     *
     * @return array
     */
    protected function getResponseTimeStats(): array
    {
        $cacheKey = 'activity_response_time_stats';
        return Cache::get($cacheKey, [
            'avg_response_time_ms' => 0,
            'p95_response_time_ms' => 0,
            'p99_response_time_ms' => 0,
        ]);
    }

    /**
     * 計算錯誤率
     *
     * @return float
     */
    protected function calculateErrorRate(): float
    {
        $cacheKey = 'activity_error_rate:' . date('Y-m-d-H');
        $stats = Cache::get($cacheKey, ['total' => 0, 'errors' => 0]);
        
        if ($stats['total'] === 0) {
            return 0.0;
        }
        
        return ($stats['errors'] / $stats['total']) * 100;
    }

    /**
     * 取得系統運行時間
     *
     * @return float
     */
    protected function getSystemUptime(): float
    {
        // 簡化實作，實際應該從系統取得
        return 24.0; // 預設 24 小時
    }

    /**
     * 檢查服務健康狀態
     *
     * @return array
     */
    protected function checkServiceHealth(): array
    {
        $health = [];
        
        // 資料庫健康檢查
        try {
            DB::select('SELECT 1');
            $health['database_status'] = 'healthy';
        } catch (\Exception $e) {
            $health['database_status'] = 'unhealthy';
        }
        
        // 快取健康檢查
        try {
            Cache::put('health_check', 'ok', 60);
            $health['cache_status'] = Cache::get('health_check') === 'ok' ? 'healthy' : 'unhealthy';
        } catch (\Exception $e) {
            $health['cache_status'] = 'unhealthy';
        }
        
        return $health;
    }

    /**
     * 檢查資源警告
     *
     * @return array
     */
    protected function checkResourceWarnings(): array
    {
        $warnings = [];
        
        // 這裡可以加入更多資源檢查邏輯
        
        return $warnings;
    }

    /**
     * 儲存警告
     *
     * @param array $alert
     * @return void
     */
    protected function storeAlert(array $alert): void
    {
        $cacheKey = 'activity_performance_alerts:' . date('Y-m-d');
        $alerts = Cache::get($cacheKey, []);
        
        $alerts[] = array_merge($alert, [
            'timestamp' => now()->toISOString(),
        ]);
        
        Cache::put($cacheKey, $alerts, 86400); // 24小時
    }

    /**
     * 發送嚴重警告
     *
     * @param array $alert
     * @return void
     */
    protected function sendCriticalAlert(array $alert): void
    {
        // 這裡可以整合通知系統
        Log::critical('活動記錄系統嚴重效能警告', $alert);
        
        // TODO: 發送郵件、Slack 通知等
    }

    /**
     * 生成摘要
     *
     * @param array $metrics
     * @return array
     */
    protected function generateSummary(array $metrics): array
    {
        return [
            'overall_health' => $this->calculateOverallHealth($metrics),
            'key_metrics' => $this->extractKeyMetrics($metrics),
            'performance_score' => $this->calculatePerformanceScore($metrics),
        ];
    }

    /**
     * 生成建議
     *
     * @param array $metrics
     * @return array
     */
    protected function generateRecommendations(array $metrics): array
    {
        $recommendations = [];
        
        // 根據指標生成建議
        if (($metrics['queue_size_activities'] ?? 0) > 500) {
            $recommendations[] = '考慮增加佇列 worker 數量以處理積壓的工作';
        }
        
        if (($metrics['error_rate_percent'] ?? 0) > 3) {
            $recommendations[] = '檢查錯誤日誌並修復導致失敗的問題';
        }
        
        if (($metrics['memory_usage_mb'] ?? 0) > 256) {
            $recommendations[] = '考慮優化記憶體使用或增加伺服器記憶體';
        }
        
        return $recommendations;
    }

    /**
     * 計算整體健康狀態
     *
     * @param array $metrics
     * @return string
     */
    protected function calculateOverallHealth(array $metrics): string
    {
        $score = $this->calculatePerformanceScore($metrics);
        
        if ($score >= 90) return 'excellent';
        if ($score >= 80) return 'good';
        if ($score >= 70) return 'fair';
        if ($score >= 60) return 'poor';
        
        return 'critical';
    }

    /**
     * 提取關鍵指標
     *
     * @param array $metrics
     * @return array
     */
    protected function extractKeyMetrics(array $metrics): array
    {
        return [
            'queue_size' => $metrics['queue_size_activities'] ?? 0,
            'error_rate' => $metrics['error_rate_percent'] ?? 0,
            'memory_usage' => $metrics['memory_usage_mb'] ?? 0,
            'success_rate' => $metrics['success_rate_percent'] ?? 100,
        ];
    }

    /**
     * 計算效能分數
     *
     * @param array $metrics
     * @return int
     */
    protected function calculatePerformanceScore(array $metrics): int
    {
        $score = 100;
        
        // 佇列大小影響
        $queueSize = $metrics['queue_size_activities'] ?? 0;
        if ($queueSize > 1000) $score -= 20;
        elseif ($queueSize > 500) $score -= 10;
        
        // 錯誤率影響
        $errorRate = $metrics['error_rate_percent'] ?? 0;
        $score -= min($errorRate * 2, 30);
        
        // 記憶體使用影響
        $memoryUsage = $metrics['memory_usage_mb'] ?? 0;
        if ($memoryUsage > 512) $score -= 15;
        elseif ($memoryUsage > 256) $score -= 5;
        
        return max(0, min(100, $score));
    }
}