<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Sanctum\PersonalAccessToken;
use App\Services\ActivityLogger;

/**
 * API 認證中介軟體
 */
class ApiAuthentication
{
    public function __construct(
        private ActivityLogger $activityLogger
    ) {}

    /**
     * 處理傳入的請求
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // 檢查 API Token
        $token = $this->extractToken($request);
        
        if (!$token) {
            return $this->unauthorizedResponse('缺少 API Token');
        }

        // 驗證 Token
        $accessToken = PersonalAccessToken::findToken($token);
        
        if (!$accessToken) {
            $this->logFailedAuthentication($request, 'Invalid token');
            return $this->unauthorizedResponse('無效的 API Token');
        }

        // 檢查 Token 是否過期
        if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
            $this->logFailedAuthentication($request, 'Token expired');
            return $this->unauthorizedResponse('API Token 已過期');
        }

        // 檢查 Token 能力
        if (!$this->hasRequiredAbilities($accessToken, $request)) {
            $this->logFailedAuthentication($request, 'Insufficient abilities');
            return $this->unauthorizedResponse('API Token 權限不足');
        }

        // 更新最後使用時間
        $accessToken->forceFill(['last_used_at' => now()])->save();

        // 設定認證使用者
        $request->setUserResolver(function () use ($accessToken) {
            return $accessToken->tokenable;
        });

        // 記錄成功的 API 存取
        $this->logSuccessfulAuthentication($request, $accessToken);

        return $next($request);
    }

    /**
     * 從請求中提取 Token
     */
    private function extractToken(Request $request): ?string
    {
        // 從 Authorization header 提取
        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }

        // 從查詢參數提取（不建議，但支援）
        return $request->query('api_token');
    }

    /**
     * 檢查 Token 是否具有所需能力
     */
    private function hasRequiredAbilities(PersonalAccessToken $token, Request $request): bool
    {
        $route = $request->route();
        $routeName = $route?->getName();

        // 定義路由所需的能力
        $requiredAbilities = match ($routeName) {
            'api.v1.activities.index',
            'api.v1.activities.show',
            'api.v1.activities.search',
            'api.v1.activities.stats',
            'api.v1.activities.related' => ['activities:read'],
            
            'api.v1.activities.export',
            'api.v1.activities.download' => ['activities:export'],
            
            default => ['activities:read']
        };

        // 檢查 Token 是否具有所需能力
        foreach ($requiredAbilities as $ability) {
            if (!$token->can($ability)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 記錄失敗的認證嘗試
     */
    private function logFailedAuthentication(Request $request, string $reason): void
    {
        $this->activityLogger->logSecurityEvent('api_authentication_failed', 'API 認證失敗', [
            'reason' => $reason,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'endpoint' => $request->getPathInfo(),
            'method' => $request->getMethod(),
        ]);
    }

    /**
     * 記錄成功的認證
     */
    private function logSuccessfulAuthentication(Request $request, PersonalAccessToken $token): void
    {
        $this->activityLogger->logApiAccess('api_authentication_success', [
            'token_id' => $token->id,
            'token_name' => $token->name,
            'user_id' => $token->tokenable_id,
            'endpoint' => $request->getPathInfo(),
            'method' => $request->getMethod(),
        ]);
    }

    /**
     * 返回未授權回應
     */
    private function unauthorizedResponse(string $message): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'error' => 'Unauthorized',
            'message' => $message,
            'code' => 'API_AUTH_FAILED'
        ], Response::HTTP_UNAUTHORIZED);
    }
}