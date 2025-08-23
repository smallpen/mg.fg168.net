<?php

namespace App\Http\Middleware;

use App\Models\Activity;
use App\Services\ActivityIntegrityService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ActivityIntegrityMiddleware
{
    /**
     * 完整性服務
     */
    protected ActivityIntegrityService $integrityService;
    
    /**
     * 建構子
     */
    public function __construct(ActivityIntegrityService $integrityService)
    {
        $this->integrityService = $integrityService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 檢查是否為活動記錄相關的修改請求
        if ($this->isActivityModificationRequest($request)) {
            // 記錄嘗試修改活動記錄的行為
            Log::warning('檢測到活動記錄修改嘗試', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'user_id' => auth()->id(),
                'parameters' => $request->all()
            ]);
            
            // 阻止請求並回傳錯誤
            return response()->json([
                'error' => '活動記錄不允許修改，以確保審計追蹤的完整性',
                'code' => 'ACTIVITY_INTEGRITY_VIOLATION'
            ], 403);
        }
        
        return $next($request);
    }
    
    /**
     * 檢查是否為活動記錄修改請求
     * 
     * @param Request $request HTTP 請求
     * @return bool 是否為修改請求
     */
    protected function isActivityModificationRequest(Request $request): bool
    {
        // 檢查 URL 路徑
        $path = $request->path();
        
        // 活動記錄相關的路由模式
        $activityRoutePatterns = [
            'admin/activities/*/edit',
            'admin/activities/*',
            'api/activities/*'
        ];
        
        foreach ($activityRoutePatterns as $pattern) {
            if (fnmatch($pattern, $path)) {
                // 檢查 HTTP 方法是否為修改操作
                if (in_array($request->method(), ['PUT', 'PATCH', 'DELETE'])) {
                    return true;
                }
            }
        }
        
        // 檢查是否直接操作 Activity 模型
        if ($request->has('activity_id') && in_array($request->method(), ['PUT', 'PATCH', 'DELETE'])) {
            return true;
        }
        
        return false;
    }
}
