<?php

namespace App\Livewire\Admin\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

/**
 * 管理後台登出按鈕元件
 * 
 * 處理使用者登出邏輯和 session 清除
 */
class LogoutButton extends Component
{
    /**
     * 處理登出請求
     * 
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout()
    {
        try {
            // 記錄登出日誌
            logger()->info('管理員登出', [
                'user_id' => auth()->id(),
                'username' => auth()->user()->username,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // 清除使用者權限快取
            if (auth()->user() && method_exists(auth()->user(), 'clearPermissionCache')) {
                auth()->user()->clearPermissionCache();
            }

            // 登出使用者
            Auth::logout();

            // 使 session 無效
            request()->session()->invalidate();

            // 重新生成 CSRF token
            request()->session()->regenerateToken();

            // 重新導向到登入頁面
            return redirect()->route('admin.login')->with('success', '您已成功登出');

        } catch (\Exception $e) {
            // 記錄錯誤日誌
            logger()->error('登出時發生錯誤', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // 強制登出並重新導向
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect()->route('admin.login')->with('error', '登出時發生錯誤，但您已被安全登出');
        }
    }

    /**
     * 渲染元件視圖
     * 
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.admin.auth.logout-button');
    }
}