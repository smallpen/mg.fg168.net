<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * 檢查管理員權限中介軟體
 * 
 * 實作基於角色和權限的存取控制邏輯
 * 提供完整的錯誤處理和重新導向機制
 */
class CheckAdminPermission
{
    /**
     * 處理傳入的請求
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  $permission 需要檢查的特定權限
     * @param  string|null  $role 需要檢查的特定角色
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ?string $permission = null, ?string $role = null): Response
    {
        // 檢查使用者是否已登入
        if (!Auth::check()) {
            $this->logAccessAttempt($request, 'unauthenticated');
            
            // 如果是 AJAX 請求，回傳 JSON 錯誤
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => '請先登入系統',
                    'redirect' => route('admin.login')
                ], 401);
            }
            
            // 儲存原始請求 URL 以便登入後重新導向
            session(['url.intended' => $request->url()]);
            
            return redirect()->route('admin.login')
                           ->with('error', '請先登入系統以存取管理功能');
        }

        $user = Auth::user();

        // 檢查使用者帳號是否啟用
        if (!$user->is_active) {
            $this->logAccessAttempt($request, 'inactive_user', $user->id);
            
            Auth::logout();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => '您的帳號已被停用，請聯絡管理員',
                    'redirect' => route('admin.login')
                ], 403);
            }
            
            return redirect()->route('admin.login')
                           ->with('error', '您的帳號已被停用，請聯絡管理員');
        }

        // 如果指定了特定角色，檢查使用者是否擁有該角色
        if ($role && !$user->hasRole($role)) {
            $this->logAccessAttempt($request, 'insufficient_role', $user->id, [
                'required_role' => $role,
                'user_roles' => $user->roles->pluck('name')->toArray()
            ]);
            
            return $this->handleInsufficientPermission($request, "您需要 '{$role}' 角色才能存取此功能");
        }

        // 如果指定了特定權限，檢查使用者是否擁有該權限
        if ($permission && !$user->hasPermission($permission)) {
            $this->logAccessAttempt($request, 'insufficient_permission', $user->id, [
                'required_permission' => $permission,
                'user_permissions' => $user->getAllPermissions()->pluck('name')->toArray()
            ]);
            
            return $this->handleInsufficientPermission($request, "您沒有 '{$permission}' 權限存取此功能");
        }

        // 檢查路由特定權限
        $routePermission = $this->getRoutePermission($request);
        if ($routePermission && !$user->hasPermission($routePermission)) {
            $this->logAccessAttempt($request, 'route_permission_denied', $user->id, [
                'route_permission' => $routePermission,
                'route_name' => $request->route()->getName(),
            ]);
            
            return $this->handleInsufficientPermission($request, "您沒有存取此頁面的權限");
        }
        
        // 檢查模組存取權限（暫時停用，因為我們使用具體的功能權限）
        /*
        $modulePermission = $this->getModulePermission($request);
        if ($modulePermission && !$user->hasPermission($modulePermission)) {
            $this->logAccessAttempt($request, 'module_permission_denied', $user->id, [
                'module_permission' => $modulePermission,
                'route_name' => $request->route()->getName(),
            ]);
            
            return $this->handleInsufficientPermission($request, "您沒有存取此模組的權限");
        }
        */

        // 暫時允許所有已登入的使用者存取（用於測試主題切換功能）
        // 在實際環境中，應該取消註解以下程式碼來檢查管理員權限
        /*
        if (!$role && !$user->isAdmin()) {
            $this->logAccessAttempt($request, 'not_admin', $user->id, [
                'user_roles' => $user->roles->pluck('name')->toArray()
            ]);
            
            return $this->handleInsufficientPermission($request, '您沒有管理員權限存取此功能');
        }
        */

        // 記錄成功的存取
        $this->logAccessAttempt($request, 'success', $user->id);

        return $next($request);
    }

    /**
     * 處理權限不足的情況
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $message
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function handleInsufficientPermission(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'error' => 'insufficient_permission'
            ], 403);
        }

        // 對於 Livewire 請求，回傳適當的錯誤回應
        if ($request->header('X-Livewire')) {
            abort(403, $message);
        }

        // 一般 HTTP 請求重新導向到儀表板並顯示錯誤訊息
        return redirect()->route('admin.dashboard')
                       ->with('error', $message);
    }

    /**
     * 記錄存取嘗試
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $status
     * @param  int|null  $userId
     * @param  array  $context
     * @return void
     */
    private function logAccessAttempt(Request $request, string $status, ?int $userId = null, array $context = []): void
    {
        $logData = [
            'status' => $status,
            'url' => $request->url(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => $userId,
            'timestamp' => now()->toISOString(),
        ];

        // 合併額外的上下文資訊
        if (!empty($context)) {
            $logData['context'] = $context;
        }

        // 根據狀態選擇適當的日誌等級
        switch ($status) {
            case 'success':
                Log::info('Admin access granted', $logData);
                break;
            case 'unauthenticated':
                Log::warning('Unauthenticated admin access attempt', $logData);
                break;
            case 'inactive_user':
            case 'insufficient_role':
            case 'insufficient_permission':
            case 'not_admin':
                Log::warning('Admin access denied', $logData);
                break;
            default:
                Log::info('Admin access attempt', $logData);
        }
    }
    
    /**
     * 根據路由取得所需權限
     */
    protected function getRoutePermission(Request $request): ?string
    {
        $routeName = $request->route()->getName();
        
        // 定義路由權限映射
        $routePermissions = [
            'admin.users.index' => 'users.view',
            'admin.users.create' => 'users.create',
            'admin.users.show' => 'users.view',
            'admin.users.edit' => 'users.edit',
            'admin.users.delete' => 'users.delete',
            'admin.roles.index' => 'roles.view',
            'admin.roles.create' => 'roles.create',
            'admin.roles.edit' => 'roles.edit',
            'admin.permissions.index' => 'permissions.view',
            'admin.settings.index' => 'settings.manage',
        ];
        
        return $routePermissions[$routeName] ?? null;
    }
    
    /**
     * 根據路由取得模組權限
     */
    protected function getModulePermission(Request $request): ?string
    {
        $routeName = $request->route()->getName();
        
        // 定義模組權限映射
        $modulePermissions = [
            'admin.users' => 'module.users',
            'admin.roles' => 'module.roles',
            'admin.permissions' => 'module.permissions',
            'admin.settings' => 'module.settings',
        ];
        
        // 從路由名稱提取模組名稱
        if (preg_match('/^admin\.([^.]+)/', $routeName, $matches)) {
            $module = "admin.{$matches[1]}";
            return $modulePermissions[$module] ?? null;
        }
        
        return null;
    }
    
    /**
     * 檢查使用者是否有管理員角色
     */
    protected function isAdminUser($user): bool
    {
        return $user->hasRole(['super_admin', 'admin']) || 
               $user->hasPermission('admin.access');
    }
    
    /**
     * 檢查是否為開發環境的測試路由
     */
    protected function isTestRoute(Request $request): bool
    {
        $routeName = $request->route()->getName();
        $testRoutes = [
            'admin.test-layout',
            'admin.test-theme',
            'admin.test-responsive',
            'admin.animations',
        ];
        
        return in_array($routeName, $testRoutes) && app()->environment('local');
    }
}