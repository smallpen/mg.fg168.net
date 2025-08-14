<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Support\Facades\Log;
use App\Services\AuditLogService;

class VerifyCsrfToken extends Middleware
{
    /**
     * 應該從 CSRF 驗證中排除的 URI
     *
     * @var array<int, string>
     */
    protected $except = [
        // API 路由通常使用其他認證方式
        'api/*',
    ];

    /**
     * 處理 CSRF token 不匹配的情況
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Session\TokenMismatchException  $exception
     * @return \Illuminate\Http\Response
     */
    protected function tokensMatch($request)
    {
        $tokensMatch = parent::tokensMatch($request);
        
        // 如果 token 不匹配，記錄安全事件
        if (!$tokensMatch) {
            $this->logCsrfFailure($request);
        }
        
        return $tokensMatch;
    }

    /**
     * 記錄 CSRF 驗證失敗
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function logCsrfFailure($request): void
    {
        $auditService = app(AuditLogService::class);
        
        $auditService->logSecurityEvent('csrf_token_mismatch', 'high', [
            'url' => $request->url(),
            'method' => $request->method(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referer' => $request->header('referer'),
            'user_id' => auth()->id(),
            'session_id' => $request->session()->getId(),
            'provided_token' => $request->input('_token') ? 'present' : 'missing',
            'expected_token_present' => $request->session()->token() ? 'yes' : 'no',
        ]);

        Log::warning('CSRF token 驗證失敗', [
            'url' => $request->url(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * 判斷請求是否應該使用 CSRF 保護
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function shouldPassThrough($request)
    {
        $shouldPass = parent::shouldPassThrough($request);
        
        // 記錄被排除的請求（用於監控）
        if ($shouldPass) {
            Log::info('CSRF 驗證已跳過', [
                'url' => $request->url(),
                'method' => $request->method(),
                'reason' => 'excluded_uri',
            ]);
        }
        
        return $shouldPass;
    }

    /**
     * 取得 CSRF token 的額外來源
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function getTokenFromRequest($request)
    {
        $token = parent::getTokenFromRequest($request);
        
        // 如果標準方法沒有找到 token，嘗試從其他地方獲取
        if (!$token) {
            // 嘗試從 Livewire 請求中獲取
            $token = $request->header('X-CSRF-TOKEN') ?: 
                     $request->header('X-Livewire-Token') ?:
                     $request->input('_token');
        }
        
        return $token;
    }
}