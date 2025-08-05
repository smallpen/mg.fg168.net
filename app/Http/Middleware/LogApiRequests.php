<?php

namespace App\Http\Middleware;

use App\Services\LoggingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API 請求日誌中介軟體
 * 
 * 記錄所有 API 請求的詳細資訊，用於監控和除錯
 */
class LogApiRequests
{
    protected LoggingService $loggingService;

    public function __construct(LoggingService $loggingService)
    {
        $this->loggingService = $loggingService;
    }

    /**
     * 處理傳入的請求
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        // 記錄請求開始
        $requestId = uniqid('req_');
        $request->attributes->set('request_id', $requestId);

        // 執行請求
        $response = $next($request);

        // 計算處理時間
        $processingTime = (microtime(true) - $startTime) * 1000; // 轉換為毫秒

        // 記錄請求詳情
        $this->logRequest($request, $response, $processingTime, $requestId);

        // 檢查是否需要效能警報
        if ($processingTime > 5000) { // 超過 5 秒
            $this->loggingService->logPerformanceMetric(
                'slow_api_request',
                $processingTime,
                'ms',
                [
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'request_id' => $requestId,
                ]
            );
        }

        return $response;
    }

    /**
     * 記錄請求詳情
     */
    protected function logRequest(Request $request, Response $response, float $processingTime, string $requestId): void
    {
        $logData = [
            'request_id' => $requestId,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => auth()->id(),
            'status_code' => $response->getStatusCode(),
            'processing_time_ms' => round($processingTime, 2),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'timestamp' => now()->toISOString(),
        ];

        // 記錄請求參數（排除敏感資料）
        $input = $request->except(['password', 'password_confirmation', 'current_password']);
        if (!empty($input)) {
            $logData['input'] = $input;
        }

        // 記錄回應大小
        $responseContent = $response->getContent();
        if ($responseContent) {
            $logData['response_size_bytes'] = strlen($responseContent);
        }

        // 根據狀態碼決定日誌等級
        $logLevel = match (true) {
            $response->getStatusCode() >= 500 => 'error',
            $response->getStatusCode() >= 400 => 'warning',
            $processingTime > 2000 => 'warning', // 超過 2 秒
            default => 'info',
        };

        // 記錄到效能日誌
        \Log::channel('performance')->{$logLevel}('API 請求', $logData);

        // 如果是錯誤回應，額外記錄到安全日誌
        if ($response->getStatusCode() >= 400) {
            $this->loggingService->logSecurityEvent(
                'api_error_response',
                "API 請求返回錯誤狀態碼: {$response->getStatusCode()}",
                $logData,
                $response->getStatusCode() >= 500 ? 'high' : 'medium'
            );
        }
    }
}