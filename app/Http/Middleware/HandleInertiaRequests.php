<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * 處理 Inertia 請求中介軟體
 * 
 * 雖然我們使用 Livewire，但保留此中介軟體以備未來擴展使用
 */
class HandleInertiaRequests
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
        return $next($request);
    }
}