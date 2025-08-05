<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * 取得使用者應該被重新導向的路徑
     * 
     * 當使用者未認證時，將其重新導向到適當的登入頁面
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        // 如果是管理後台路由，重新導向到管理員登入頁面
        if ($request->is('admin') || $request->is('admin/*')) {
            return route('admin.login');
        }

        // 預設重新導向到管理員登入頁面（因為這個系統主要是管理後台）
        return route('admin.login');
    }
}