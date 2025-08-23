<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

/**
 * 活動記錄中介軟體
 * 
 * 自動記錄 HTTP 請求和回應的活動
 */
class ActivityLoggingMiddleware
{
    /**
     * 活動記錄服務
     *
     * @var ActivityLogger
     */
    protected ActivityLogger $activityLogger;

    /**
     * 不需要記錄的路由模式
     *
     * @var array
     */
    protected array $excludedRoutes = [
        'api/health',
        'api/ping',
        '_debugbar/*',
        'telescope/*',
        'horizon/*',
        'livewire/*',
    ];

    /**
     * 不需要記錄的 HTTP 方法
     *
     * @var array
     */
    protected array $excludedMethods = [
        'OPTIONS',
        'HEAD',
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
     * 處理傳入的請求
     *
     * @param Request $request
     * @param Closure $next
     * @return BaseResponse
     */
    public function handle(Request $request, Closure $next): BaseResponse
    {
        // 檢查是否需要記錄此請求
        if (!$this->shouldLogRequest($request)) {
            return $next($request);
        }

        $startTime = microtime(true);
        
        // 記錄請求開始
        $this->logRequestStart($request);

        // 處理請求
        $response = $next($request);

        // 記錄請求完成
        $this->logRequestComplete($request, $response, $startTime);

        return $response;
    }

    /**
     * 檢查是否應該記錄此請求
     *
     * @param Request $request
     * @return bool
     */
    protected function shouldLogRequest(Request $request): bool
    {
        // 檢查 HTTP 方法
        if (in_array($request->method(), $this->excludedMethods)) {
            return false;
        }

        // 檢查路由模式
        $path = $request->path();
        foreach ($this->excludedRoutes as $pattern) {
            if (fnmatch($pattern, $path)) {
                return false;
            }
        }

        // 檢查是否為 AJAX 請求（可選擇性記錄）
        if ($request->ajax() && !$this->shouldLogAjaxRequest($request)) {
            return false;
        }

        return true;
    }

    /**
     * 檢查是否應該記錄 AJAX 請求
     *
     * @param Request $request
     * @return bool
     */
    protected function shouldLogAjaxRequest(Request $request): bool
    {
        // 只記錄重要的 AJAX 操作
        $importantAjaxRoutes = [
            'admin/users/*',
            'admin/roles/*',
            'admin/permissions/*',
            'admin/settings/*',
        ];

        $path = $request->path();
        foreach ($importantAjaxRoutes as $pattern) {
            if (fnmatch($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 記錄請求開始
     *
     * @param Request $request
     * @return void
     */
    protected function logRequestStart(Request $request): void
    {
        // 只記錄重要的請求開始（如登入、重要操作等）
        if ($this->isImportantRequest($request)) {
            $this->activityLogger->logAsync(
                'request_started',
                "開始處理請求：{$request->method()} {$request->path()}",
                [
                    'module' => 'system',
                    'properties' => [
                        'method' => $request->method(),
                        'path' => $request->path(),
                        'query_params' => $request->query(),
                        'is_ajax' => $request->ajax(),
                        'referer' => $request->header('referer'),
                    ],
                    'risk_level' => 1,
                ]
            );
        }
    }

    /**
     * 記錄請求完成
     *
     * @param Request $request
     * @param BaseResponse $response
     * @param float $startTime
     * @return void
     */
    protected function logRequestComplete(Request $request, BaseResponse $response, float $startTime): void
    {
        $duration = round((microtime(true) - $startTime) * 1000, 2); // 毫秒
        $statusCode = $response->getStatusCode();
        
        // 判斷請求結果
        $result = $this->determineRequestResult($statusCode);
        $riskLevel = $this->calculateRequestRiskLevel($request, $response);
        
        // 生成活動描述
        $description = $this->generateRequestDescription($request, $response);
        
        // 準備活動資料
        $activityData = [
            'module' => $this->getRequestModule($request),
            'properties' => [
                'method' => $request->method(),
                'path' => $request->path(),
                'status_code' => $statusCode,
                'duration_ms' => $duration,
                'response_size' => $this->getResponseSize($response),
                'query_params' => $request->query(),
                'is_ajax' => $request->ajax(),
                'route_name' => $request->route()?->getName(),
            ],
            'result' => $result,
            'risk_level' => $riskLevel,
        ];

        // 如果是 POST/PUT/DELETE 請求，記錄請求資料（過濾敏感資訊）
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $activityData['properties']['request_data'] = $this->filterSensitiveData($request->all());
        }

        // 記錄活動
        $this->activityLogger->logAsync(
            $this->getActivityType($request, $response),
            $description,
            $activityData
        );

        // 如果是錯誤回應，額外記錄安全事件
        if ($statusCode >= 400) {
            $this->logErrorResponse($request, $response);
        }
    }

    /**
     * 判斷是否為重要請求
     *
     * @param Request $request
     * @return bool
     */
    protected function isImportantRequest(Request $request): bool
    {
        $importantPaths = [
            'admin/login',
            'admin/logout',
            'admin/users/create',
            'admin/users/*/edit',
            'admin/roles/create',
            'admin/roles/*/edit',
        ];

        $path = $request->path();
        foreach ($importantPaths as $pattern) {
            if (fnmatch($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 判斷請求結果
     *
     * @param int $statusCode
     * @return string
     */
    protected function determineRequestResult(int $statusCode): string
    {
        return match (true) {
            $statusCode >= 200 && $statusCode < 300 => 'success',
            $statusCode >= 300 && $statusCode < 400 => 'redirect',
            $statusCode >= 400 && $statusCode < 500 => 'client_error',
            $statusCode >= 500 => 'server_error',
            default => 'unknown',
        };
    }

    /**
     * 計算請求風險等級
     *
     * @param Request $request
     * @param BaseResponse $response
     * @return int
     */
    protected function calculateRequestRiskLevel(Request $request, BaseResponse $response): int
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
            $statusCode >= 400 && $statusCode < 500 => 2,
            $statusCode >= 500 => 3,
            default => 1,
        };
        
        // 根據路徑調整風險
        $pathRisk = 0;
        $path = $request->path();
        if (str_contains($path, 'admin')) {
            $pathRisk += 1;
        }
        if (str_contains($path, 'users') || str_contains($path, 'roles')) {
            $pathRisk += 1;
        }
        
        // 未認證使用者的風險較高
        if (!Auth::check()) {
            $baseRisk += 1;
        }

        return min($baseRisk + $methodRisk + $statusRisk + $pathRisk, 10);
    }

    /**
     * 生成請求描述
     *
     * @param Request $request
     * @param BaseResponse $response
     * @return string
     */
    protected function generateRequestDescription(Request $request, BaseResponse $response): string
    {
        $method = $request->method();
        $path = $request->path();
        $statusCode = $response->getStatusCode();
        
        $action = match ($method) {
            'GET' => '檢視',
            'POST' => '建立',
            'PUT', 'PATCH' => '更新',
            'DELETE' => '刪除',
            default => '存取',
        };
        
        return "{$action}頁面：{$path} (狀態碼: {$statusCode})";
    }

    /**
     * 取得請求模組
     *
     * @param Request $request
     * @return string
     */
    protected function getRequestModule(Request $request): string
    {
        $path = $request->path();
        
        if (str_contains($path, 'admin/users')) {
            return 'users';
        }
        if (str_contains($path, 'admin/roles')) {
            return 'roles';
        }
        if (str_contains($path, 'admin/permissions')) {
            return 'permissions';
        }
        if (str_contains($path, 'admin/settings')) {
            return 'settings';
        }
        if (str_contains($path, 'admin')) {
            return 'admin';
        }
        if (str_contains($path, 'api')) {
            return 'api';
        }
        
        return 'web';
    }

    /**
     * 取得活動類型
     *
     * @param Request $request
     * @param BaseResponse $response
     * @return string
     */
    protected function getActivityType(Request $request, BaseResponse $response): string
    {
        $statusCode = $response->getStatusCode();
        
        if ($statusCode >= 400) {
            return 'http_error';
        }
        
        return match ($request->method()) {
            'GET' => 'page_view',
            'POST' => 'data_create',
            'PUT', 'PATCH' => 'data_update',
            'DELETE' => 'data_delete',
            default => 'http_request',
        };
    }

    /**
     * 取得回應大小
     *
     * @param BaseResponse $response
     * @return int
     */
    protected function getResponseSize(BaseResponse $response): int
    {
        $content = $response->getContent();
        return $content ? strlen($content) : 0;
    }

    /**
     * 過濾敏感資料
     *
     * @param array $data
     * @return array
     */
    protected function filterSensitiveData(array $data): array
    {
        $sensitiveFields = [
            'password', 'password_confirmation', 'current_password',
            'token', 'api_token', 'secret', 'key', 'credit_card'
        ];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[FILTERED]';
            }
        }
        
        return $data;
    }

    /**
     * 記錄錯誤回應
     *
     * @param Request $request
     * @param BaseResponse $response
     * @return void
     */
    protected function logErrorResponse(Request $request, BaseResponse $response): void
    {
        $statusCode = $response->getStatusCode();
        
        // 只記錄特定的錯誤狀態碼
        if (!in_array($statusCode, [401, 403, 404, 422, 429, 500, 503])) {
            return;
        }
        
        $eventType = match ($statusCode) {
            401 => 'unauthorized_access',
            403 => 'forbidden_access',
            404 => 'not_found_access',
            422 => 'validation_error',
            429 => 'rate_limit_exceeded',
            500 => 'server_error',
            503 => 'service_unavailable',
            default => 'http_error',
        };
        
        $this->activityLogger->logSecurityEvent(
            $eventType,
            "HTTP 錯誤：{$statusCode} - {$request->method()} {$request->path()}",
            [
                'status_code' => $statusCode,
                'method' => $request->method(),
                'path' => $request->path(),
                'user_agent' => $request->userAgent(),
                'referer' => $request->header('referer'),
            ]
        );
    }
}