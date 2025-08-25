<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use App\Services\ActivityLogger;

/**
 * API 速率限制中介軟體
 */
class ApiRateLimit
{
    public function __construct(
        private ActivityLogger $activityLogger
    ) {}

    /**
     * 處理傳入的請求
     *
     * @param Request $request
     * @param Closure $next
     * @param string $maxAttempts 最大嘗試次數
     * @param string $decayMinutes 重置時間（分鐘）
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $maxAttempts = '60', string $decayMinutes = '1'): mixed
    {
        $key = $this->resolveRequestSignature($request);
        $maxAttempts = (int) $maxAttempts;
        $decayMinutes = (int) $decayMinutes;

        // 檢查是否超過速率限制
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $this->logRateLimitExceeded($request, $key);
            return $this->buildRateLimitResponse($key, $maxAttempts);
        }

        // 增加嘗試次數
        RateLimiter::hit($key, $decayMinutes * 60);

        $response = $next($request);

        // 添加速率限制標頭
        return $this->addRateLimitHeaders(
            $response,
            $maxAttempts,
            RateLimiter::attempts($key),
            RateLimiter::availableIn($key)
        );
    }

    /**
     * 解析請求簽名
     */
    private function resolveRequestSignature(Request $request): string
    {
        $user = $request->user();
        
        if ($user) {
            // 已認證使用者：基於使用者 ID
            return 'api_rate_limit:user:' . $user->id;
        }
        
        // 未認證請求：基於 IP 位址
        return 'api_rate_limit:ip:' . $request->ip();
    }

    /**
     * 記錄速率限制超過事件
     */
    private function logRateLimitExceeded(Request $request, string $key): void
    {
        $user = $request->user();
        
        $this->activityLogger->logSecurityEvent('api_rate_limit_exceeded', 'API 速率限制超過', [
            'rate_limit_key' => $key,
            'user_id' => $user?->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'endpoint' => $request->getPathInfo(),
            'method' => $request->getMethod(),
            'attempts' => RateLimiter::attempts($key),
        ]);

        // 如果是重複違規，提高風險等級
        $violationCount = $this->getViolationCount($key);
        if ($violationCount > 5) {
            $this->activityLogger->logSecurityEvent('api_rate_limit_abuse', 'API 速率限制濫用', [
                'rate_limit_key' => $key,
                'violation_count' => $violationCount,
                'user_id' => $user?->id,
                'ip_address' => $request->ip(),
                'risk_level' => 8, // 高風險
            ]);
        }
    }

    /**
     * 取得違規次數
     */
    private function getViolationCount(string $key): int
    {
        $violationKey = "rate_limit_violations:{$key}";
        $count = Cache::get($violationKey, 0);
        Cache::put($violationKey, $count + 1, now()->addHours(24));
        return $count + 1;
    }

    /**
     * 建立速率限制回應
     */
    private function buildRateLimitResponse(string $key, int $maxAttempts): \Illuminate\Http\JsonResponse
    {
        $retryAfter = RateLimiter::availableIn($key);
        
        return response()->json([
            'error' => 'Too Many Requests',
            'message' => '請求過於頻繁，請稍後再試',
            'code' => 'RATE_LIMIT_EXCEEDED',
            'retry_after' => $retryAfter,
            'limit' => $maxAttempts,
        ], Response::HTTP_TOO_MANY_REQUESTS)
        ->header('Retry-After', $retryAfter)
        ->header('X-RateLimit-Limit', $maxAttempts)
        ->header('X-RateLimit-Remaining', 0);
    }

    /**
     * 添加速率限制標頭
     */
    private function addRateLimitHeaders($response, int $maxAttempts, int $attempts, int $retryAfter)
    {
        $remaining = max(0, $maxAttempts - $attempts);
        
        return $response->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remaining,
            'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->timestamp,
        ]);
    }
}