<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * API 活動記錄中介軟體
 * 
 * 專門記錄 API 請求和回應的活動
 */
class ApiActivityLoggingMiddleware
{
    /**
     * 活動記錄服務
     *
     * @var ActivityLogger
     */
    protected ActivityLogger $activityLogger;

    /**
     * 不需要記錄的 API 端點
     *
     * @var array
     */
    protected array $excludedEndpoints = [
        'api/health',
        'api/ping',
        'api/status',
        'api/metrics',
    ];

    /**
     * 敏感的 API 端點（需要特別記錄）
     *
     * @var array
     */
    protected array $sensitiveEndpoints = [
        'api/auth/*',
        'api/users/*',
        'api/roles/*',
        'api/permissions/*',
        'api/admin/*',
    ];

    /**
     * 建構函式
     *
     * @param ActivityLogger $activityLogger
     */
    public function __construct(ActivityLogger $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    /**
     * 處理傳入的 API 請求
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 檢查是否需要記錄此 API 請求
        if (!$this->shouldLogApiRequest($request)) {
            return $next($request);
        }

        $startTime = microtime(true);
        
        // 記錄 API 請求開始
        $this->logApiRequestStart($request);

        // 處理請求
        $response = $next($request);

        // 記錄 API 請求完成
        $this->logApiRequestComplete($request, $response, $startTime);

        return $response;
    }

    /**
     * 檢查是否應該記錄此 API 請求
     *
     * @param Request $request
     * @return bool
     */
    protected function shouldLogApiRequest(Request $request): bool
    {
        $path = $request->path();
        
        // 檢查排除的端點
        foreach ($this->excludedEndpoints as $pattern) {
            if (fnmatch($pattern, $path)) {
                return false;
            }
        }

        // 只記錄 API 路由
        if (!str_starts_with($path, 'api/')) {
            return false;
        }

        return true;
    }

    /**
     * 檢查是否為敏感 API 端點
     *
     * @param Request $request
     * @return bool
     */
    protected function isSensitiveEndpoint(Request $request): bool
    {
        $path = $request->path();
        
        foreach ($this->sensitiveEndpoints as $pattern) {
            if (fnmatch($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 記錄 API 請求開始
     *
     * @param Request $request
     * @return void
     */
    protected function logApiRequestStart(Request $request): void
    {
        // 只記錄敏感端點的請求開始
        if ($this->isSensitiveEndpoint($request)) {
            $this->activityLogger->logAsync(
                'api_request_started',
                "API 請求開始：{$request->method()} {$request->path()}",
                [
                    'module' => 'api',
                    'properties' => [
                        'method' => $request->method(),
                        'endpoint' => $request->path(),
                        'query_params' => $request->query(),
                        'content_type' => $request->header('content-type'),
                        'accept' => $request->header('accept'),
                        'api_version' => $request->header('api-version'),
                    ],
                    'risk_level' => 2,
                ]
            );
        }
    }

    /**
     * 記錄 API 請求完成
     *
     * @param Request $request
     * @param Response $response
     * @param float $startTime
     * @return void
     */
    protected function logApiRequestComplete(Request $request, Response $response, float $startTime): void
    {
        $duration = round((microtime(true) - $startTime) * 1000, 2); // 毫秒
        $statusCode = $response->getStatusCode();
        
        // 判斷請求結果
        $result = $this->determineApiResult($statusCode);
        $riskLevel = $this->calculateApiRiskLevel($request, $response);
        
        // 生成活動描述
        $description = $this->generateApiDescription($request, $response);
        
        // 準備活動資料
        $activityData = [
            'module' => 'api',
            'properties' => [
                'method' => $request->method(),
                'endpoint' => $request->path(),
                'status_code' => $statusCode,
                'duration_ms' => $duration,
                'response_size' => $this->getResponseSize($response),
                'query_params' => $request->query(),
                'route_name' => $request->route()?->getName(),
                'api_version' => $request->header('api-version'),
                'user_agent' => $request->userAgent(),
                'content_type' => $request->header('content-type'),
                'accept' => $request->header('accept'),
            ],
            'result' => $result,
            'risk_level' => $riskLevel,
        ];

        // 記錄請求資料（過濾敏感資訊）
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            $activityData['properties']['request_data'] = $this->filterSensitiveApiData($request->all());
        }

        // 記錄回應資料（僅在錯誤時或敏感端點）
        if ($statusCode >= 400 || $this->isSensitiveEndpoint($request)) {
            $activityData['properties']['response_data'] = $this->getResponseData($response);
        }

        // 記錄認證資訊
        if (Auth::check()) {
            $activityData['properties']['authenticated_user'] = [
                'id' => Auth::id(),
                'username' => Auth::user()->username,
            ];
        } else {
            $activityData['properties']['authentication'] = 'unauthenticated';
        }

        // 記錄 API 金鑰資訊（如果有）
        if ($apiKey = $this->extractApiKey($request)) {
            $activityData['properties']['api_key_hash'] = hash('sha256', $apiKey);
        }

        // 記錄活動
        $this->activityLogger->logApiAccess(
            $request->path(),
            $activityData
        );

        // 如果是錯誤回應，額外記錄安全事件
        if ($statusCode >= 400) {
            $this->logApiError($request, $response);
        }

        // 如果是成功的敏感操作，記錄安全事件
        if ($statusCode < 400 && $this->isSensitiveEndpoint($request) && in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $this->logSensitiveApiOperation($request, $response);
        }
    }

    /**
     * 判斷 API 請求結果
     *
     * @param int $statusCode
     * @return string
     */
    protected function determineApiResult(int $statusCode): string
    {
        return match (true) {
            $statusCode >= 200 && $statusCode < 300 => 'success',
            $statusCode >= 300 && $statusCode < 400 => 'redirect',
            $statusCode === 400 => 'bad_request',
            $statusCode === 401 => 'unauthorized',
            $statusCode === 403 => 'forbidden',
            $statusCode === 404 => 'not_found',
            $statusCode === 422 => 'validation_error',
            $statusCode === 429 => 'rate_limited',
            $statusCode >= 400 && $statusCode < 500 => 'client_error',
            $statusCode >= 500 => 'server_error',
            default => 'unknown',
        };
    }

    /**
     * 計算 API 請求風險等級
     *
     * @param Request $request
     * @param Response $response
     * @return int
     */
    protected function calculateApiRiskLevel(Request $request, Response $response): int
    {
        $baseRisk = 1;
        $statusCode = $response->getStatusCode();
        
        // 根據 HTTP 方法調整風險
        $methodRisk = match ($request->method()) {
            'GET', 'HEAD', 'OPTIONS' => 0,
            'POST' => 2,
            'PUT', 'PATCH' => 3,
            'DELETE' => 4,
            default => 1,
        };
        
        // 根據狀態碼調整風險
        $statusRisk = match (true) {
            $statusCode >= 200 && $statusCode < 300 => 0,
            $statusCode === 401 || $statusCode === 403 => 3,
            $statusCode >= 400 && $statusCode < 500 => 2,
            $statusCode >= 500 => 4,
            default => 1,
        };
        
        // 敏感端點風險較高
        if ($this->isSensitiveEndpoint($request)) {
            $baseRisk += 2;
        }
        
        // 未認證使用者的風險較高
        if (!Auth::check()) {
            $baseRisk += 1;
        }

        // 異常 IP 位址風險較高
        if ($this->isUnusualIpAddress($request->ip())) {
            $baseRisk += 2;
        }

        return min($baseRisk + $methodRisk + $statusRisk, 10);
    }

    /**
     * 生成 API 請求描述
     *
     * @param Request $request
     * @param Response $response
     * @return string
     */
    protected function generateApiDescription(Request $request, Response $response): string
    {
        $method = $request->method();
        $endpoint = $request->path();
        $statusCode = $response->getStatusCode();
        
        return "API 請求：{$method} {$endpoint} (狀態碼: {$statusCode})";
    }

    /**
     * 取得回應大小
     *
     * @param Response $response
     * @return int
     */
    protected function getResponseSize(Response $response): int
    {
        $content = $response->getContent();
        return $content ? strlen($content) : 0;
    }

    /**
     * 取得回應資料
     *
     * @param Response $response
     * @return array|null
     */
    protected function getResponseData(Response $response): ?array
    {
        if ($response instanceof JsonResponse) {
            $data = $response->getData(true);
            
            // 限制回應資料大小
            if (is_array($data) && count($data) > 100) {
                return ['message' => 'Response data too large to log'];
            }
            
            return $data;
        }

        return null;
    }

    /**
     * 過濾敏感 API 資料
     *
     * @param array $data
     * @return array
     */
    protected function filterSensitiveApiData(array $data): array
    {
        $sensitiveFields = [
            'password', 'password_confirmation', 'current_password',
            'token', 'api_token', 'secret', 'key', 'credit_card',
            'ssn', 'bank_account', 'private_key'
        ];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[FILTERED]';
            }
        }
        
        return $data;
    }

    /**
     * 提取 API 金鑰
     *
     * @param Request $request
     * @return string|null
     */
    protected function extractApiKey(Request $request): ?string
    {
        // 檢查 Authorization header
        $authorization = $request->header('Authorization');
        if ($authorization && str_starts_with($authorization, 'Bearer ')) {
            return substr($authorization, 7);
        }

        // 檢查 API-Key header
        if ($apiKey = $request->header('API-Key')) {
            return $apiKey;
        }

        // 檢查查詢參數
        if ($apiKey = $request->query('api_key')) {
            return $apiKey;
        }

        return null;
    }

    /**
     * 檢查是否為異常 IP 位址
     *
     * @param string $ip
     * @return bool
     */
    protected function isUnusualIpAddress(string $ip): bool
    {
        // 這裡可以實作更複雜的 IP 檢查邏輯
        // 例如檢查是否為已知的惡意 IP、是否來自異常地理位置等
        
        // 簡單的實作：檢查是否為內網 IP
        $internalIps = ['127.0.0.1', '::1'];
        $internalRanges = ['192.168.', '10.', '172.16.', '172.17.', '172.18.', '172.19.', '172.20.', '172.21.', '172.22.', '172.23.', '172.24.', '172.25.', '172.26.', '172.27.', '172.28.', '172.29.', '172.30.', '172.31.'];
        
        if (in_array($ip, $internalIps)) {
            return false;
        }
        
        foreach ($internalRanges as $range) {
            if (str_starts_with($ip, $range)) {
                return false;
            }
        }
        
        // 如果不是內網 IP，可能需要進一步檢查
        return false; // 暫時返回 false，實際應用中可以實作更複雜的邏輯
    }

    /**
     * 記錄 API 錯誤
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    protected function logApiError(Request $request, Response $response): void
    {
        $statusCode = $response->getStatusCode();
        
        $eventType = match ($statusCode) {
            401 => 'api_unauthorized',
            403 => 'api_forbidden',
            404 => 'api_not_found',
            422 => 'api_validation_error',
            429 => 'api_rate_limited',
            500 => 'api_server_error',
            default => 'api_error',
        };
        
        $this->activityLogger->logSecurityEvent(
            $eventType,
            "API 錯誤：{$statusCode} - {$request->method()} {$request->path()}",
            [
                'status_code' => $statusCode,
                'method' => $request->method(),
                'endpoint' => $request->path(),
                'response_data' => $this->getResponseData($response),
            ]
        );
    }

    /**
     * 記錄敏感 API 操作
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    protected function logSensitiveApiOperation(Request $request, Response $response): void
    {
        $this->activityLogger->logSecurityEvent(
            'sensitive_api_operation',
            "敏感 API 操作：{$request->method()} {$request->path()}",
            [
                'method' => $request->method(),
                'endpoint' => $request->path(),
                'status_code' => $response->getStatusCode(),
                'authenticated_user' => Auth::check() ? Auth::user()->username : null,
            ]
        );
    }
}