<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * 活動記錄存取控制中介軟體
 * 
 * 確保只有具備適當權限的使用者才能存取活動記錄功能
 */
class ActivityLogAccessControl
{
    /**
     * 處理傳入的請求
     *
     * @param Request $request
     * @param Closure $next
     * @param string|null $permission
     * @return Response
     */
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        // 檢查使用者是否已登入
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', '請先登入系統');
        }

        $user = Auth::user();

        // 預設權限檢查
        $requiredPermission = $permission ?? $this->getRequiredPermission($request);

        // 檢查使用者是否具備所需權限
        if (!$user->hasPermission($requiredPermission)) {
            // 記錄未授權存取嘗試
            $this->logUnauthorizedAccess($request, $user, $requiredPermission);
            
            abort(403, '您沒有權限存取此功能');
        }

        // 記錄授權存取
        $this->logAuthorizedAccess($request, $user, $requiredPermission);

        return $next($request);
    }

    /**
     * 根據請求路由決定所需權限
     *
     * @param Request $request
     * @return string
     */
    private function getRequiredPermission(Request $request): string
    {
        $route = $request->route();
        $routeName = $route ? $route->getName() : '';
        $method = $request->method();

        // 根據路由名稱和 HTTP 方法決定權限
        return match (true) {
            str_contains($routeName, 'activity') && str_contains($routeName, 'export') => 'activity_logs.export',
            str_contains($routeName, 'activity') && in_array($method, ['DELETE']) => 'activity_logs.delete',
            str_contains($routeName, 'activity') && str_contains($routeName, 'security') => 'system.security',
            str_contains($routeName, 'activity') && str_contains($routeName, 'stats') => 'system.monitor',
            str_contains($routeName, 'activity') && str_contains($routeName, 'monitor') => 'system.monitor',
            str_contains($routeName, 'activity') => 'activity_logs.view',
            str_contains($routeName, 'security') && str_contains($routeName, 'audit') => 'system.security',
            str_contains($routeName, 'security') && str_contains($routeName, 'incident') => 'system.security',
            str_contains($routeName, 'security') => 'system.security',
            default => 'activity_logs.view'
        };
    }

    /**
     * 記錄未授權存取嘗試
     *
     * @param Request $request
     * @param mixed $user
     * @param string $permission
     * @return void
     */
    private function logUnauthorizedAccess(Request $request, $user, string $permission): void
    {
        activity()
            ->causedBy($user)
            ->withProperties([
                'route' => $request->route()?->getName(),
                'url' => $request->url(),
                'method' => $request->method(),
                'required_permission' => $permission,
                'user_permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'risk_level' => 8, // 高風險
            ])
            ->log('unauthorized_activity_access');
    }

    /**
     * 記錄授權存取
     *
     * @param Request $request
     * @param mixed $user
     * @param string $permission
     * @return void
     */
    private function logAuthorizedAccess(Request $request, $user, string $permission): void
    {
        // 只記錄敏感操作的授權存取
        if (in_array($permission, ['activity_logs.export', 'activity_logs.delete', 'security.audit'])) {
            activity()
                ->causedBy($user)
                ->withProperties([
                    'route' => $request->route()?->getName(),
                    'url' => $request->url(),
                    'method' => $request->method(),
                    'permission' => $permission,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'risk_level' => 3, // 中等風險
                ])
                ->log('authorized_activity_access');
        }
    }
}