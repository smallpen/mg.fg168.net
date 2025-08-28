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
            // 清除任何殘留的會話資料
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            return redirect()->route('admin.login')->with('error', '請先登入以存取管理後台');
        }

        // 檢查使用者是否有管理員權限
        $user = auth()->user();
        
        // 確保使用者帳號是啟用狀態
        if (!$user->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            return redirect()->route('admin.login')->with('error', '您的帳號已被停用，請聯繫管理員');
        }

        // 檢查使用者是否有管理員角色
        if (!$user->hasRole('admin') && !$user->hasRole('super_admin')) {
            // 記錄未授權存取嘗試
            logger()->warning('未授權的管理後台存取嘗試', [
                'user_id' => $user->id,
                'username' => $user->username,
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
            ]);
            
            abort(403, '您沒有權限存取管理後台');
        }

        return $next($request);
    }
}