<?php

namespace App\Http\Middleware;

use App\Models\Activity;
use App\Services\ActivityIntegrityService;
use App\Services\ActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * 活動記錄防篡改保護中介軟體
 * 
 * 監控對活動記錄的直接修改嘗試並記錄可疑行為
 */
class ActivityTamperProtection
{
    /**
     * 完整性服務
     *
     * @var ActivityIntegrityService
     */
    protected ActivityIntegrityService $integrityService;

    /**
     * 活動記錄服務
     *
     * @var ActivityLogger
     */
    protected ActivityLogger $activityLogger;

    /**
     * 建構函式
     *
     * @param ActivityIntegrityService $integrityService
     * @param ActivityLogger $activityLogger
     */
    public function __construct(
        ActivityIntegrityService $integrityService,
        ActivityLogger $activityLogger
    ) {
        $this->integrityService = $integrityService;
        $this->activityLogger = $activityLogger;
    }

    /**
     * 處理傳入的請求
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 檢查是否為活動記錄相關的修改請求
        if ($this->isActivityModificationRequest($request)) {
            
            // 檢查是否啟用防篡改保護
            if (!config('activity-log.tamper_protection.enabled', true)) {
                return $next($request);
            }

            // 記錄篡改嘗試
            $this->logTamperAttempt($request);

            // 根據設定決定處理方式
            $onTamperDetected = config('activity-log.tamper_protection.on_tamper_detected', 'alert');

            switch ($onTamperDetected) {
                case 'block':
                    return $this->blockRequest($request);
                    
                case 'alert':
                    $this->sendTamperAlert($request);
                    break;
                    
                case 'log':
                default:
                    // 只記錄，不阻止
                    break;
            }
        }

        return $next($request);
    }

    /**
     * 檢查是否為活動記錄修改請求
     *
     * @param Request $request
     * @return bool
     */
    protected function isActivityModificationRequest(Request $request): bool
    {
        // 檢查 URL 路徑
        $path = $request->path();
        
        // 檢查是否為活動記錄相關的路由
        if (str_contains($path, 'activities') || str_contains($path, 'activity-log')) {
            // 檢查是否為修改操作（PUT, PATCH, DELETE）
            return in_array($request->method(), ['PUT', 'PATCH', 'DELETE']);
        }

        // 檢查是否直接操作活動記錄模型
        if ($request->has('activity_id') || $request->route('activity')) {
            return in_array($request->method(), ['PUT', 'PATCH', 'DELETE']);
        }

        return false;
    }

    /**
     * 記錄篡改嘗試
     *
     * @param Request $request
     * @return void
     */
    protected function logTamperAttempt(Request $request): void
    {
        $tamperData = [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => auth()->id(),
            'parameters' => $this->sanitizeParameters($request->all()),
            'headers' => $this->sanitizeHeaders($request->headers->all()),
            'timestamp' => now()->toISOString(),
        ];

        // 記錄到日誌
        Log::warning('檢測到活動記錄篡改嘗試', $tamperData);

        // 記錄安全事件
        if (config('activity-log.tamper_protection.log_tamper_attempts', true)) {
            $this->activityLogger->logSecurityEvent(
                'activity_tamper_attempt',
                '檢測到活動記錄篡改嘗試',
                [
                    'tamper_data' => $tamperData,
                    'risk_level' => 9,
                    'requires_investigation' => true,
                ]
            );
        }
    }

    /**
     * 發送篡改警報
     *
     * @param Request $request
     * @return void
     */
    protected function sendTamperAlert(Request $request): void
    {
        $alertData = [
            'type' => 'activity_tamper_attempt',
            'severity' => 'high',
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'detected_at' => now()->toISOString(),
        ];

        Log::alert('活動記錄篡改警報', $alertData);

        // 這裡可以整合其他警報系統
        // 例如：發送郵件、Slack 通知、簡訊等
    }

    /**
     * 阻止請求
     *
     * @param Request $request
     * @return Response
     */
    protected function blockRequest(Request $request): Response
    {
        Log::critical('已阻止活動記錄篡改嘗試', [
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
        ]);

        return response()->json([
            'error' => '操作被拒絕',
            'message' => '檢測到對活動記錄的非法修改嘗試，操作已被阻止。',
            'code' => 'TAMPER_PROTECTION_ACTIVATED',
            'timestamp' => now()->toISOString(),
        ], 403);
    }

    /**
     * 清理請求參數（移除敏感資訊）
     *
     * @param array $parameters
     * @return array
     */
    protected function sanitizeParameters(array $parameters): array
    {
        $sensitiveKeys = [
            'password', 'token', 'secret', 'key', 'api_key',
            'csrf_token', 'session_id', 'auth_token'
        ];

        foreach ($parameters as $key => $value) {
            foreach ($sensitiveKeys as $sensitiveKey) {
                if (stripos($key, $sensitiveKey) !== false) {
                    $parameters[$key] = '[FILTERED]';
                    break;
                }
            }
        }

        return $parameters;
    }

    /**
     * 清理請求標頭（移除敏感資訊）
     *
     * @param array $headers
     * @return array
     */
    protected function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = [
            'authorization', 'cookie', 'x-api-key', 'x-auth-token'
        ];

        foreach ($headers as $key => $value) {
            if (in_array(strtolower($key), $sensitiveHeaders)) {
                $headers[$key] = '[FILTERED]';
            }
        }

        return $headers;
    }
}
