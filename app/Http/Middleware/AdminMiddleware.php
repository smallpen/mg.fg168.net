<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * 管理員權限中介軟體
 * 
 * 檢查使用者是否有管理員權限
 */
class AdminMiddleware
{
    /**
     * 處理傳入的請求
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // 檢查使用者是否已登入
        if (!auth()->check()) {
            return redirect()->route('admin.login');
        }

        // 暫時允許所有已登入的使用者存取（用於測試）
        // 在實際環境中，這裡應該檢查使用者角色
        // if (!auth()->user()->hasRole('admin') && !auth()->user()->hasRole('super_admin')) {
        //     abort(403, '您沒有權限存取此頁面');
        // }

        return $next($request);
    }
}