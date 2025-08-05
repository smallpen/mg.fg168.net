<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * 系統監控服務
 * 
 * 負責監控系統效能指標和健康狀態
 */
class MonitoringService
{
    protected LoggingService $loggingService;

    public function __construct(LoggingService $loggingService)
    {
        $this->loggingService = $loggingService;
    }

    /**
     * 收集系統效能指標
     *
     * @return array
     */
    public function collectPerformanceMetrics(): array
    {
        $metrics = [];

        // 記憶體使用量
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        $metrics['memory'] = [
            'current' => $memoryUsage,
            'peak' => $memoryPeak,
            'current_mb' => round($memoryUsage / 1024 / 1024, 2),
            'peak_mb' => round($memoryPeak / 1024 / 1024, 2),
        ];

        // CPU 負載（如果可用）
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            $metrics['cpu_load'] = [
                '1min' => $load[0],
                '5min' => $load[1],
                '15min' => $load[2],
            ];
        }

        // 資料庫連線狀態
        try {
            $dbStart = microtime(true);
            DB::select('SELECT 1');
            $dbTime = (microtime(true) - $dbStart) * 1000;
            $metrics['database'] = [
                'status' => 'healthy',
                'response_time_ms' => round($dbTime, 2),
            ];
        } catch (\Exception $e) {
            $metrics['database'] = [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }

        // Redis 連線狀態
        try {
            $redisStart = microtime(true);
            Redis::ping();
            $redisTime = (microtime(true) - $redisStart) * 1000;
            $metrics['redis'] = [
                'status' => 'healthy',
                'response_time_ms' => round($redisTime, 2),
            ];
        } catch (\Exception $e) {
            $metrics['redis'] = [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }

        // 磁碟空間
        $diskFree = disk_free_space(storage_path());
        $diskTotal = disk_total_space(storage_path());
        $diskUsed = $diskTotal - $diskFree;
        $diskUsagePercent = ($diskUsed / $diskTotal) * 100;

        $metrics['disk'] = [
            'free_bytes' => $diskFree,
            'total_bytes' => $diskTotal,
            'used_bytes' => $diskUsed,
            'usage_percent' => round($diskUsagePercent, 2),
            'free_gb' => round($diskFree / 1024 / 1024 / 1024, 2),
            'total_gb' => round($diskTotal / 1024 / 1024 / 1024, 2),
        ];

        // 記錄效能指標
        $this->loggingService->logPerformanceMetric('memory_usage', $metrics['memory']['current_mb'], 'MB');
        $this->loggingService->logPerformanceMetric('disk_usage', $metrics['disk']['usage_percent'], '%');
        
        if (isset($metrics['database']['response_time_ms'])) {
            $this->loggingService->logPerformanceMetric('db_response_time', $metrics['database']['response_time_ms'], 'ms');
        }
        
        if (isset($metrics['redis']['response_time_ms'])) {
            $this->loggingService->logPerformanceMetric('redis_response_time', $metrics['redis']['response_time_ms'], 'ms');
        }

        return $metrics;
    }

    /**
     * 檢查系統健康狀態
     *
     * @return array
     */
    public function checkSystemHealth(): array
    {
        $health = [
            'overall_status' => 'healthy',
            'components' => [],
            'timestamp' => now()->toISOString(),
        ];

        // 檢查資料庫健康狀態
        $dbHealth = $this->checkDatabaseHealth();
        $health['components']['database'] = $dbHealth;

        // 檢查 Redis 健康狀態
        $redisHealth = $this->checkRedisHealth();
        $health['components']['redis'] = $redisHealth;

        // 檢查檔案系統健康狀態
        $filesystemHealth = $this->checkFilesystemHealth();
        $health['components']['filesystem'] = $filesystemHealth;

        // 檢查應用程式健康狀態
        $appHealth = $this->checkApplicationHealth();
        $health['components']['application'] = $appHealth;

        // 確定整體健康狀態
        $statuses = array_column($health['components'], 'status');
        if (in_array('critical', $statuses)) {
            $health['overall_status'] = 'critical';
        } elseif (in_array('warning', $statuses)) {
            $health['overall_status'] = 'warning';
        }

        // 記錄健康狀態
        foreach ($health['components'] as $component => $status) {
            $this->loggingService->logHealthStatus($component, $status['status'], $status);
        }

        return $health;
    }

    /**
     * 檢查資料庫健康狀態
     *
     * @return array
     */
    protected function checkDatabaseHealth(): array
    {
        try {
            $start = microtime(true);
            
            // 測試基本連線
            DB::select('SELECT 1');
            
            // 檢查使用者表是否可存取
            $userCount = DB::table('users')->count();
            
            $responseTime = (microtime(true) - $start) * 1000;

            $status = 'healthy';
            if ($responseTime > 1000) { // 超過 1 秒
                $status = 'warning';
            }
            if ($responseTime > 5000) { // 超過 5 秒
                $status = 'critical';
            }

            return [
                'status' => $status,
                'response_time_ms' => round($responseTime, 2),
                'user_count' => $userCount,
                'message' => '資料庫連線正常',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'error' => $e->getMessage(),
                'message' => '資料庫連線失敗',
            ];
        }
    }

    /**
     * 檢查 Redis 健康狀態
     *
     * @return array
     */
    protected function checkRedisHealth(): array
    {
        try {
            $start = microtime(true);
            
            // 測試 Redis 連線
            $pong = Redis::ping();
            
            // 測試讀寫操作
            $testKey = 'health_check_' . time();
            Redis::set($testKey, 'test', 'EX', 10);
            $testValue = Redis::get($testKey);
            Redis::del($testKey);
            
            $responseTime = (microtime(true) - $start) * 1000;

            $status = 'healthy';
            if ($responseTime > 500) { // 超過 500ms
                $status = 'warning';
            }
            if ($responseTime > 2000) { // 超過 2 秒
                $status = 'critical';
            }

            return [
                'status' => $status,
                'response_time_ms' => round($responseTime, 2),
                'ping_response' => $pong,
                'read_write_test' => $testValue === 'test' ? 'passed' : 'failed',
                'message' => 'Redis 連線正常',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'error' => $e->getMessage(),
                'message' => 'Redis 連線失敗',
            ];
        }
    }

    /**
     * 檢查檔案系統健康狀態
     *
     * @return array
     */
    protected function checkFilesystemHealth(): array
    {
        $storagePath = storage_path();
        $diskFree = disk_free_space($storagePath);
        $diskTotal = disk_total_space($storagePath);
        $diskUsagePercent = (($diskTotal - $diskFree) / $diskTotal) * 100;

        $status = 'healthy';
        $message = '檔案系統正常';

        if ($diskUsagePercent > 80) {
            $status = 'warning';
            $message = '磁碟使用量超過 80%';
        }
        if ($diskUsagePercent > 90) {
            $status = 'critical';
            $message = '磁碟使用量超過 90%';
        }

        // 檢查重要目錄是否可寫
        $writableCheck = [
            'storage' => is_writable(storage_path()),
            'logs' => is_writable(storage_path('logs')),
            'cache' => is_writable(storage_path('framework/cache')),
        ];

        if (in_array(false, $writableCheck)) {
            $status = 'critical';
            $message = '重要目錄不可寫';
        }

        return [
            'status' => $status,
            'disk_usage_percent' => round($diskUsagePercent, 2),
            'free_space_gb' => round($diskFree / 1024 / 1024 / 1024, 2),
            'writable_directories' => $writableCheck,
            'message' => $message,
        ];
    }

    /**
     * 檢查應用程式健康狀態
     *
     * @return array
     */
    protected function checkApplicationHealth(): array
    {
        try {
            // 檢查快取是否正常工作
            $cacheKey = 'health_check_' . time();
            Cache::put($cacheKey, 'test', 10);
            $cacheValue = Cache::get($cacheKey);
            Cache::forget($cacheKey);

            $cacheWorking = $cacheValue === 'test';

            // 檢查 session 是否正常工作
            $sessionWorking = session()->isStarted() || session()->start();

            $status = 'healthy';
            $message = '應用程式正常運行';

            if (!$cacheWorking || !$sessionWorking) {
                $status = 'warning';
                $message = '應用程式部分功能異常';
            }

            return [
                'status' => $status,
                'cache_working' => $cacheWorking,
                'session_working' => $sessionWorking,
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'message' => $message,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'error' => $e->getMessage(),
                'message' => '應用程式健康檢查失敗',
            ];
        }
    }

    /**
     * 檢查是否需要發送警報
     *
     * @param array $metrics 效能指標
     * @param array $health 健康狀態
     */
    public function checkAlerts(array $metrics, array $health): void
    {
        // 記憶體使用量警報
        if ($metrics['memory']['current_mb'] > 512) { // 超過 512MB
            $this->sendAlert('high_memory_usage', [
                'current_usage' => $metrics['memory']['current_mb'] . 'MB',
                'threshold' => '512MB',
            ]);
        }

        // 磁碟使用量警報
        if ($metrics['disk']['usage_percent'] > 85) {
            $this->sendAlert('high_disk_usage', [
                'current_usage' => $metrics['disk']['usage_percent'] . '%',
                'threshold' => '85%',
            ]);
        }

        // 資料庫回應時間警報
        if (isset($metrics['database']['response_time_ms']) && $metrics['database']['response_time_ms'] > 1000) {
            $this->sendAlert('slow_database_response', [
                'response_time' => $metrics['database']['response_time_ms'] . 'ms',
                'threshold' => '1000ms',
            ]);
        }

        // 系統健康狀態警報
        if ($health['overall_status'] === 'critical') {
            $this->sendAlert('system_critical', [
                'components' => array_filter($health['components'], function ($component) {
                    return $component['status'] === 'critical';
                }),
            ]);
        }
    }

    /**
     * 發送警報
     *
     * @param string $alertType 警報類型
     * @param array $data 警報資料
     */
    protected function sendAlert(string $alertType, array $data): void
    {
        // 檢查是否在冷卻期內（避免重複警報）
        $cooldownKey = "alert_cooldown_{$alertType}";
        if (Cache::has($cooldownKey)) {
            return;
        }

        // 設定冷卻期（15 分鐘）
        Cache::put($cooldownKey, true, 900);

        // 記錄警報
        Log::channel('security')->critical('系統警報', [
            'alert_type' => $alertType,
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ]);

        // 這裡可以整合其他警報機制，如：
        // - 發送 Slack 通知
        // - 發送電子郵件
        // - 呼叫 webhook
        // - 發送 SMS

        $this->loggingService->logSecurityEvent(
            'system_alert',
            "系統警報：{$alertType}",
            $data,
            'critical'
        );
    }
}