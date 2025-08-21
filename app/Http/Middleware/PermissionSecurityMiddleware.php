<?php

namespace App\Http\Middleware;

use App\Services\PermissionSecurityService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * 權限安全中介軟體
 * 
 * 為權限管理操作提供額外的安全檢查和審計記錄
 */
class PermissionSecurityMiddleware
{
    protected PermissionSecurityService $securityService;

    public function __construct(PermissionSecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    /**
     * 處理傳入的請求
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  $operation 操作類型
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ?string $operation = null): Response
    {
        // 如果使用者未登入，跳過安全檢查
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        $operation = $operation ?? $this->detectOperation($request);

        // 記錄請求開始
        $this->logRequestStart($request, $operation, $user);

        // 執行安全檢查
        $securityCheck = $this->performSecurityCheck($request, $operation, $user);

        // 如果安全檢查失敗，拒絕請求
        if (!$securityCheck['passed']) {
            return $this->handleSecurityFailure($request, $securityCheck);
        }

        // 如果有警告，記錄但允許繼續
        if (!empty($securityCheck['warnings'])) {
            $this->handleSecurityWarnings($request, $securityCheck['warnings'], $user);
        }

        // 設定安全上下文
        $this->setSecurityContext($request, $securityCheck);

        // 執行請求
        $response = $next($request);

        // 記錄請求完成
        $this->logRequestComplete($request, $response, $operation, $user);

        return $response;
    }

    /**
     * 偵測操作類型
     */
    protected function detectOperation(Request $request): string
    {
        $method = $request->method();
        $routeName = $request->route()?->getName() ?? '';

        // 根據 HTTP 方法和路由名稱推斷操作類型
        if (str_contains($routeName, 'create') || $method === 'POST') {
            return 'create';
        }

        if (str_contains($routeName, 'edit') || str_contains($routeName, 'update') || $method === 'PUT' || $method === 'PATCH') {
            return 'update';
        }

        if (str_contains($routeName, 'delete') || str_contains($routeName, 'destroy') || $method === 'DELETE') {
            return 'delete';
        }

        if (str_contains($routeName, 'export')) {
            return 'export';
        }

        if (str_contains($routeName, 'import')) {
            return 'import';
        }

        if (str_contains($routeName, 'test')) {
            return 'test';
        }

        if (str_contains($routeName, 'bulk')) {
            return 'bulk_operation';
        }

        return 'view';
    }

    /**
     * 執行安全檢查
     */
    protected function performSecurityCheck(Request $request, string $operation, $user): array
    {
        $data = $this->extractRequestData($request);
        $permission = $this->extractPermissionFromRequest($request);

        try {
            return $this->securityService->performSecurityCheck($operation, $data, $permission, $user);
        } catch (\Exception $e) {
            Log::error('權限安全檢查失敗', [
                'operation' => $operation,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'request_data' => $data,
            ]);

            return [
                'passed' => false,
                'errors' => ['安全檢查執行失敗: ' . $e->getMessage()],
                'warnings' => [],
                'risk_level' => 'high',
            ];
        }
    }

    /**
     * 從請求中提取資料
     */
    protected function extractRequestData(Request $request): array
    {
        $data = [];

        // 提取表單資料
        if ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('PATCH')) {
            $data = $request->all();
        }

        // 提取查詢參數
        $data = array_merge($data, $request->query());

        // 移除敏感資料
        unset($data['password'], $data['_token'], $data['_method']);

        return $data;
    }

    /**
     * 從請求中提取權限物件
     */
    protected function extractPermissionFromRequest(Request $request): ?\App\Models\Permission
    {
        // 嘗試從路由參數中取得權限 ID
        $permissionId = $request->route('permission') ?? $request->route('id') ?? $request->input('permission_id');

        if ($permissionId) {
            try {
                return \App\Models\Permission::find($permissionId);
            } catch (\Exception $e) {
                Log::warning('無法從請求中提取權限物件', [
                    'permission_id' => $permissionId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return null;
    }

    /**
     * 處理安全檢查失敗
     */
    protected function handleSecurityFailure(Request $request, array $securityCheck): Response
    {
        $errors = $securityCheck['errors'] ?? ['安全檢查失敗'];
        $riskLevel = $securityCheck['risk_level'] ?? 'medium';

        // 記錄安全失敗事件
        Log::warning('權限操作安全檢查失敗', [
            'url' => $request->url(),
            'method' => $request->method(),
            'user_id' => Auth::id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'errors' => $errors,
            'risk_level' => $riskLevel,
        ]);

        // 根據請求類型返回適當的回應
        if ($request->expectsJson()) {
            return response()->json([
                'message' => '安全檢查失敗',
                'errors' => $errors,
                'risk_level' => $riskLevel,
            ], 403);
        }

        // 對於 Livewire 請求
        if ($request->header('X-Livewire')) {
            $flatErrors = [];
            foreach ($errors as $error) {
                if (is_array($error)) {
                    $flatErrors = array_merge($flatErrors, $error);
                } else {
                    $flatErrors[] = $error;
                }
            }
            abort(403, implode('; ', $flatErrors));
        }

        // 一般 HTTP 請求
        $flatErrors = [];
        foreach ($errors as $error) {
            if (is_array($error)) {
                $flatErrors = array_merge($flatErrors, $error);
            } else {
                $flatErrors[] = $error;
            }
        }
        
        return redirect()->back()
                       ->withErrors(['security' => implode('; ', $flatErrors)])
                       ->with('error', '安全檢查失敗，操作被拒絕');
    }

    /**
     * 處理安全警告
     */
    protected function handleSecurityWarnings(Request $request, array $warnings, $user): void
    {
        Log::info('權限操作安全警告', [
            'url' => $request->url(),
            'method' => $request->method(),
            'user_id' => $user->id,
            'warnings' => $warnings,
        ]);

        // 將警告加入到 session 中，以便在視圖中顯示
        session()->flash('security_warnings', $warnings);
    }

    /**
     * 設定安全上下文
     */
    protected function setSecurityContext(Request $request, array $securityCheck): void
    {
        // 將安全檢查結果加入到請求屬性中
        $request->attributes->set('security_check', $securityCheck);

        // 設定風險等級
        $request->attributes->set('risk_level', $securityCheck['risk_level']);

        // 如果是高風險操作，設定額外的標記
        if ($securityCheck['risk_level'] === 'high') {
            $request->attributes->set('high_risk_operation', true);
        }
    }

    /**
     * 記錄請求開始
     */
    protected function logRequestStart(Request $request, string $operation, $user): void
    {
        Log::info('權限操作請求開始', [
            'operation' => $operation,
            'url' => $request->url(),
            'method' => $request->method(),
            'user_id' => $user->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * 記錄請求完成
     */
    protected function logRequestComplete(Request $request, Response $response, string $operation, $user): void
    {
        $statusCode = $response->getStatusCode();
        $logLevel = $statusCode >= 400 ? 'warning' : 'info';

        Log::$logLevel('權限操作請求完成', [
            'operation' => $operation,
            'url' => $request->url(),
            'method' => $request->method(),
            'user_id' => $user->id,
            'status_code' => $statusCode,
            'response_size' => strlen($response->getContent()),
            'execution_time' => $this->getExecutionTime($request),
            'timestamp' => now()->toISOString(),
        ]);

        // 對於失敗的請求，記錄額外的詳細資訊
        if ($statusCode >= 400) {
            $this->logFailedRequest($request, $response, $operation, $user);
        }
    }

    /**
     * 記錄失敗的請求
     */
    protected function logFailedRequest(Request $request, Response $response, string $operation, $user): void
    {
        $errorDetails = [
            'operation' => $operation,
            'user_id' => $user->id,
            'status_code' => $response->getStatusCode(),
            'request_data' => $request->all(),
            'headers' => $request->headers->all(),
        ];

        // 嘗試從回應中提取錯誤訊息
        if ($response->headers->get('content-type') === 'application/json') {
            try {
                $responseData = json_decode($response->getContent(), true);
                if (isset($responseData['message'])) {
                    $errorDetails['error_message'] = $responseData['message'];
                }
                if (isset($responseData['errors'])) {
                    $errorDetails['validation_errors'] = $responseData['errors'];
                }
            } catch (\Exception $e) {
                // 忽略 JSON 解析錯誤
            }
        }

        Log::error('權限操作請求失敗', $errorDetails);
    }

    /**
     * 取得執行時間
     */
    protected function getExecutionTime(Request $request): float
    {
        $startTime = $request->server('REQUEST_TIME_FLOAT');
        return $startTime ? round((microtime(true) - $startTime) * 1000, 2) : 0;
    }

    /**
     * 檢查是否為高風險 IP
     */
    protected function isHighRiskIp(string $ip): bool
    {
        // 這裡可以實作 IP 黑名單檢查
        $blacklistedIps = config('security.blacklisted_ips', []);
        return in_array($ip, $blacklistedIps);
    }

    /**
     * 檢查是否為可疑的 User Agent
     */
    protected function isSuspiciousUserAgent(string $userAgent): bool
    {
        $suspiciousPatterns = [
            'bot',
            'crawler',
            'spider',
            'scraper',
            'curl',
            'wget',
        ];

        $userAgent = strtolower($userAgent);
        foreach ($suspiciousPatterns as $pattern) {
            if (str_contains($userAgent, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 檢查請求頻率
     */
    protected function checkRequestRate(Request $request, $user): bool
    {
        $cacheKey = "request_rate_{$user->id}_{$request->ip()}";
        $requests = cache()->get($cacheKey, 0);

        // 每分鐘最多 60 個請求
        if ($requests >= 60) {
            Log::warning('請求頻率過高', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'requests_per_minute' => $requests,
            ]);
            return false;
        }

        cache()->put($cacheKey, $requests + 1, now()->addMinute());
        return true;
    }

    /**
     * 檢查並發請求
     */
    protected function checkConcurrentRequests($user): bool
    {
        $cacheKey = "concurrent_requests_{$user->id}";
        $concurrentRequests = cache()->get($cacheKey, 0);

        // 最多 5 個並發請求
        if ($concurrentRequests >= 5) {
            Log::warning('並發請求過多', [
                'user_id' => $user->id,
                'concurrent_requests' => $concurrentRequests,
            ]);
            return false;
        }

        return true;
    }

    /**
     * 增加並發請求計數
     */
    protected function incrementConcurrentRequests($user): void
    {
        $cacheKey = "concurrent_requests_{$user->id}";
        $current = cache()->get($cacheKey, 0);
        cache()->put($cacheKey, $current + 1, now()->addMinutes(5));
    }

    /**
     * 減少並發請求計數
     */
    protected function decrementConcurrentRequests($user): void
    {
        $cacheKey = "concurrent_requests_{$user->id}";
        $current = cache()->get($cacheKey, 0);
        if ($current > 0) {
            cache()->put($cacheKey, $current - 1, now()->addMinutes(5));
        }
    }
}