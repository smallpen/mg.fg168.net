<?php

namespace App\Http\Middleware;

use App\Models\SettingPerformanceMetric;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * 設定效能監控中介軟體
 * 
 * 監控設定相關請求的效能指標
 */
class SettingsPerformanceMonitor
{
    /**
     * 處理傳入的請求
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 檢查是否為設定相關的請求
        if (!$this->isSettingsRequest($request)) {
            return $next($request);
        }

        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        // 記錄請求開始
        $requestId = uniqid('req_');
        $this->logRequestStart($request, $requestId);

        try {
            // 執行請求
            $response = $next($request);
            
            // 計算效能指標
            $executionTime = (microtime(true) - $startTime) * 1000; // 毫秒
            $memoryUsage = memory_get_usage(true) - $startMemory; // 位元組
            $peakMemory = memory_get_peak_usage(true); // 位元組
            
            // 記錄效能指標
            $this->recordPerformanceMetrics($request, $response, [
                'execution_time' => $executionTime,
                'memory_usage' => $memoryUsage,
                'peak_memory' => $peakMemory,
                'request_id' => $requestId,
            ]);
            
            // 記錄請求完成
            $this->logRequestComplete($request, $response, $requestId, $executionTime);
            
            return $response;
            
        } catch (\Throwable $exception) {
            // 計算錯誤時的效能指標
            $executionTime = (microtime(true) - $startTime) * 1000;
            $memoryUsage = memory_get_usage(true) - $startMemory;
            
            // 記錄錯誤效能指標
            $this->recordErrorMetrics($request, $exception, [
                'execution_time' => $executionTime,
                'memory_usage' => $memoryUsage,
                'request_id' => $requestId,
            ]);
            
            // 記錄請求錯誤
            $this->logRequestError($request, $exception, $requestId, $executionTime);
            
            // 重新拋出例外
            throw $exception;
        }
    }

    /**
     * 檢查是否為設定相關的請求
     *
     * @param Request $request
     * @return bool
     */
    protected function isSettingsRequest(Request $request): bool
    {
        $path = $request->path();
        
        // 設定相關的路由模式
        $settingsPatterns = [
            'admin/settings*',
            'api/settings*',
            'livewire/message/admin.settings.*',
        ];
        
        foreach ($settingsPatterns as $pattern) {
            if (fnmatch($pattern, $path)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * 記錄效能指標
     *
     * @param Request $request
     * @param Response $response
     * @param array $metrics
     * @return void
     */
    protected function recordPerformanceMetrics(Request $request, Response $response, array $metrics): void
    {
        try {
            $operation = $this->getOperationName($request);
            $statusCode = $response->getStatusCode();
            
            // 記錄執行時間
            SettingPerformanceMetric::record(
                'http_request',
                $operation,
                $metrics['execution_time'],
                'ms',
                [
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'status_code' => $statusCode,
                    'request_id' => $metrics['request_id'],
                    'user_id' => auth()->id(),
                    'ip_address' => $request->ip(),
                ]
            );
            
            // 記錄記憶體使用量
            SettingPerformanceMetric::record(
                'memory_usage',
                $operation,
                $metrics['memory_usage'] / 1024 / 1024, // 轉換為 MB
                'MB',
                [
                    'peak_memory_mb' => $metrics['peak_memory'] / 1024 / 1024,
                    'request_id' => $metrics['request_id'],
                ]
            );
            
            // 記錄響應大小（如果可用）
            if ($response->headers->has('Content-Length')) {
                $responseSize = (int) $response->headers->get('Content-Length');
                SettingPerformanceMetric::record(
                    'response_size',
                    $operation,
                    $responseSize / 1024, // 轉換為 KB
                    'KB',
                    ['request_id' => $metrics['request_id']]
                );
            }
            
            // 記錄慢查詢（超過 1 秒）
            if ($metrics['execution_time'] > 1000) {
                SettingPerformanceMetric::record(
                    'slow_request',
                    $operation,
                    $metrics['execution_time'],
                    'ms',
                    [
                        'method' => $request->method(),
                        'path' => $request->path(),
                        'request_id' => $metrics['request_id'],
                        'threshold' => 1000,
                    ]
                );
            }
            
        } catch (\Exception $e) {
            // 效能指標記錄失敗不應影響主要功能
            Log::warning('記錄設定效能指標失敗', [
                'error' => $e->getMessage(),
                'request_id' => $metrics['request_id'] ?? 'unknown',
            ]);
        }
    }

    /**
     * 記錄錯誤效能指標
     *
     * @param Request $request
     * @param \Throwable $exception
     * @param array $metrics
     * @return void
     */
    protected function recordErrorMetrics(Request $request, \Throwable $exception, array $metrics): void
    {
        try {
            $operation = $this->getOperationName($request);
            
            SettingPerformanceMetric::record(
                'http_error',
                $operation,
                $metrics['execution_time'],
                'ms',
                [
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'error_class' => get_class($exception),
                    'error_message' => $exception->getMessage(),
                    'request_id' => $metrics['request_id'],
                    'user_id' => auth()->id(),
                    'ip_address' => $request->ip(),
                ]
            );
            
        } catch (\Exception $e) {
            Log::warning('記錄設定錯誤指標失敗', [
                'error' => $e->getMessage(),
                'request_id' => $metrics['request_id'] ?? 'unknown',
            ]);
        }
    }

    /**
     * 取得操作名稱
     *
     * @param Request $request
     * @return string
     */
    protected function getOperationName(Request $request): string
    {
        $path = $request->path();
        $method = strtolower($request->method());
        
        // 根據路由模式決定操作名稱
        if (str_contains($path, 'settings/list')) {
            return 'settings_list';
        } elseif (str_contains($path, 'settings/update')) {
            return 'settings_update';
        } elseif (str_contains($path, 'settings/batch')) {
            return 'settings_batch_update';
        } elseif (str_contains($path, 'settings/backup')) {
            return 'settings_backup';
        } elseif (str_contains($path, 'settings/import')) {
            return 'settings_import';
        } elseif (str_contains($path, 'settings/export')) {
            return 'settings_export';
        } elseif (str_contains($path, 'livewire/message')) {
            // Livewire 元件請求
            $component = $this->extractLivewireComponent($path);
            return "livewire_{$component}";
        } else {
            return "settings_{$method}";
        }
    }

    /**
     * 提取 Livewire 元件名稱
     *
     * @param string $path
     * @return string
     */
    protected function extractLivewireComponent(string $path): string
    {
        if (preg_match('/livewire\/message\/(.+)/', $path, $matches)) {
            $component = str_replace('.', '_', $matches[1]);
            return strtolower($component);
        }
        
        return 'unknown_component';
    }

    /**
     * 記錄請求開始
     *
     * @param Request $request
     * @param string $requestId
     * @return void
     */
    protected function logRequestStart(Request $request, string $requestId): void
    {
        Log::debug('設定請求開始', [
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => $request->path(),
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    /**
     * 記錄請求完成
     *
     * @param Request $request
     * @param Response $response
     * @param string $requestId
     * @param float $executionTime
     * @return void
     */
    protected function logRequestComplete(Request $request, Response $response, string $requestId, float $executionTime): void
    {
        $logLevel = $executionTime > 1000 ? 'warning' : 'debug';
        
        Log::log($logLevel, '設定請求完成', [
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => $request->path(),
            'status_code' => $response->getStatusCode(),
            'execution_time_ms' => round($executionTime, 2),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * 記錄請求錯誤
     *
     * @param Request $request
     * @param \Throwable $exception
     * @param string $requestId
     * @param float $executionTime
     * @return void
     */
    protected function logRequestError(Request $request, \Throwable $exception, string $requestId, float $executionTime): void
    {
        Log::error('設定請求錯誤', [
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => $request->path(),
            'error_class' => get_class($exception),
            'error_message' => $exception->getMessage(),
            'error_file' => $exception->getFile(),
            'error_line' => $exception->getLine(),
            'execution_time_ms' => round($executionTime, 2),
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
        ]);
    }
}