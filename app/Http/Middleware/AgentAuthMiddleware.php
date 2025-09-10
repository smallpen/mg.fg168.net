<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 代理身份驗證中介軟體
 * 
 * 確保只有代理身份的使用者可以存取代理管理功能
 */
class AgentAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        
        // 檢查使用者是否已登入
        if (!$user) {
            return redirect()->route('admin.login');
        }
        
        // 檢查使用者是否為代理
        if (!$user->agent) {
            abort(403, '您不是代理，無法存取此功能');
        }
        
        // 檢查代理是否啟用
        if (!$user->agent->is_active) {
            abort(403, '您的代理帳號已被停用，請聯繫上層代理');
        }
        
        return $next($request);
    }
}