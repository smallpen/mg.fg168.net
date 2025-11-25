<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ChannelPerformanceMonitor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 檢查是否啟用效能監控
        if (!config('channel-management.performance.monitoring_enabled', true)) {
            return $next($request);
        }

        // 記錄開始時間和記憶體使用
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        $startPeakMemory = memory_get_peak_usage(true);

        // 啟用查詢日誌
        if (config('channel-management.performance.log_query_time', true)) {
            DB::enableQueryLog();
        }

        // 執行請求
        $response = $next($request);

        // 計算執行時間和記憶體使用
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $endPeakMemory = memory_get_peak_usage(true);

        $executionTime = ($endTime - $startTime) * 1000; // 轉換為毫秒
        $memoryUsed = $endMemory - $startMemory;
        $peakMemoryUsed = $endPeakMemory - $startPeakMemory;

        // 收集效能資料
        $performanceData = [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'execution_time_ms' => round($executionTime, 2),
            'memory_used_mb' => round($memoryUsed / 1024 / 1024, 2),
            'peak_memory_mb' => round($endPeakMemory / 1024 / 1024, 2),
            'response_status' => $response->getStatusCode(),
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        // 收集查詢資料
        if (config('channel-management.performance.log_query_time', true)) {
            $queries = DB::getQueryLog();
            $performanceData['query_count'] = count($queries);
            $performanceData['total_query_time_ms'] = round(
                collect($queries)->sum('time'), 2
            );

            // 檢查慢查詢
            $slowQueryThreshold = config('channel-management.performance.slow_query_threshold', 1000);
            $slowQueries = collect($queries)->filter(function ($query) use ($slowQueryThreshold) {
                return $query['time'] > $slowQueryThreshold;
            });

            if ($slowQueries->isNotEmpty()) {
                $performanceData['slow_queries'] = $slowQueries->toArray();
            }
        }

        // 記錄效能資料
        $this->logPerformanceData($performanceData);

        // 檢查警告閾值
        $this->checkPerformanceThresholds($performanceData);

        // 添加效能標頭（僅在開發環境）
        if (app()->environment('local', 'development')) {
            $response->headers->set('X-Execution-Time', $performanceData['execution_time_ms'] . 'ms');
            $response->headers->set('X-Memory-Usage', $performanceData['memory_used_mb'] . 'MB');
            $response->headers->set('X-Query-Count', $performanceData['query_count'] ?? 0);
        }

        return $response;
    }

    /**
     * 記錄效能資料
     */
    private function logPerformanceData(array $data): void
    {
        // 只記錄通路管理相關的請求
        if (!$this->isChannelManagementRequest($data['url'])) {
            return;
        }

        $logData = [
            'type' => 'performance',
            'url' => $data['url'],
            'method' => $data['method'],
            'execution_time' => $data['execution_time_ms'],
            'memory_used' => $data['memory_used_mb'],
            'peak_memory' => $data['peak_memory_mb'],
            'query_count' => $data['query_count'] ?? 0,
            'query_time' => $data['total_query_time_ms'] ?? 0,
            'status' => $data['response_status'],
            'user_id' => $data['user_id'],
            'ip' => $data['ip_address'],
        ];

        Log::channel('channel_management')->info('Performance metrics', $logData);
    }

    /**
     * 檢查效能閾值並發出警告
     */
    private function checkPerformanceThresholds(array $data): void
    {
        $memoryWarningThreshold = config('channel-management.performance.memory_warning_threshold', 128);
        $memoryErrorThreshold = config('channel-management.performance.memory_error_threshold', 256);
        $slowQueryThreshold = config('channel-management.performance.slow_query_threshold', 1000);

        // 檢查記憶體使用
        if ($data['peak_memory_mb'] > $memoryErrorThreshold) {
            Log::channel('channel_management')->error('High memory usage detected', [
                'url' => $data['url'],
                'memory_used' => $data['peak_memory_mb'],
                'threshold' => $memoryErrorThreshold,
                'user_id' => $data['user_id'],
            ]);
        } elseif ($data['peak_memory_mb'] > $memoryWarningThreshold) {
            Log::channel('channel_management')->warning('Memory usage warning', [
                'url' => $data['url'],
                'memory_used' => $data['peak_memory_mb'],
                'threshold' => $memoryWarningThreshold,
                'user_id' => $data['user_id'],
            ]);
        }

        // 檢查執行時間
        if ($data['execution_time_ms'] > $slowQueryThreshold) {
            Log::channel('channel_management')->warning('Slow request detected', [
                'url' => $data['url'],
                'execution_time' => $data['execution_time_ms'],
                'threshold' => $slowQueryThreshold,
                'user_id' => $data['user_id'],
            ]);
        }

        // 檢查慢查詢
        if (isset($data['slow_queries']) && !empty($data['slow_queries'])) {
            Log::channel('channel_management')->warning('Slow queries detected', [
                'url' => $data['url'],
                'slow_queries' => $data['slow_queries'],
                'user_id' => $data['user_id'],
            ]);
        }
    }

    /**
     * 檢查是否為通路管理相關請求
     */
    private function isChannelManagementRequest(string $url): bool
    {
        $channelPaths = [
            '/admin/channels',
            '/api/channels',
            '/livewire/admin/channels',
        ];

        foreach ($channelPaths as $path) {
            if (str_contains($url, $path)) {
                return true;
            }
        }

        return false;
    }
}