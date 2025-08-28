<?php

namespace App\Http\Middleware;

use App\Services\RoleSecurityService;
use App\Services\AuditLogService;
use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * 角色安全中介軟體
 * 
 * 為角色管理操作提供額外的安全檢查和審計記錄
 */
class RoleSecurityMiddleware
{
    protected RoleSecurityService $roleSecurityService;
    protected AuditLogService $auditLogService;

    public function __construct(
        RoleSecurityService $roleSecurityService,
        AuditLogService $auditLogService
    ) {
        $this->roleSecurityService = $roleSecurityService;
        $this->auditLogService = $auditLogService;
    }

    /**
     * 處理傳入的請求
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('admin.login');
        }

        // 取得請求的操作類型
        $action = $this->determineAction($request);
        
        // 取得目標角色（如果有）
        $targetRole = $this->getTargetRole($request);

        // 執行多層級安全檢查
        $securityCheck = $this->roleSecurityService->checkMultiLevelPermissions($action, $targetRole, $user);
        
        if (!$securityCheck['allowed']) {
            // 記錄安全檢查失敗
            $this->auditLogService->logSecurityEvent('role_security_check_failed', 'medium', [
                'action' => $action,
                'target_role_id' => $targetRole?->id,
                'target_role_name' => $targetRole?->name,
                'reason' => $securityCheck['reason'],
                'message' => $securityCheck['message'],
                'url' => $request->url(),
                'method' => $request->method(),
            ], $user);

            // 根據請求類型返回適當的回應
            if ($request->expectsJson() || $request->header('X-Livewire')) {
                return response()->json([
                    'error' => true,
                    'message' => $securityCheck['message'],
                    'reason' => $securityCheck['reason']
                ], 403);
            }

            return redirect()->back()
                ->with('error', $securityCheck['message']);
        }

        // 記錄成功的安全檢查
        $this->logSuccessfulAccess($request, $action, $targetRole, $user);

        // 在請求處理前設定安全上下文
        $request->attributes->set('security_context', [
            'action' => $action,
            'target_role' => $targetRole,
            'security_check' => $securityCheck,
            'user' => $user
        ]);

        $response = $next($request);

        // 在請求處理後記錄操作結果
        $this->logOperationResult($request, $response, $action, $targetRole, $user);

        return $response;
    }

    /**
     * 確定請求的操作類型
     */
    protected function determineAction(Request $request): string
    {
        $method = $request->method();
        $routeName = $request->route()?->getName() ?? '';
        $path = $request->path();

        // 根據路由名稱判斷
        if (str_contains($routeName, 'create')) {
            return 'create';
        }
        
        if (str_contains($routeName, 'edit') || str_contains($routeName, 'update')) {
            return 'edit';
        }
        
        if (str_contains($routeName, 'delete') || str_contains($routeName, 'destroy')) {
            return 'delete';
        }

        // 根據HTTP方法判斷
        return match ($method) {
            'POST' => 'create',
            'PUT', 'PATCH' => 'edit',
            'DELETE' => 'delete',
            default => 'view'
        };
    }

    /**
     * 取得目標角色
     */
    protected function getTargetRole(Request $request): ?Role
    {
        // 從路由參數取得角色物件或ID
        $role = $request->route('role');
        
        // 如果已經是 Role 物件，直接返回
        if ($role instanceof Role) {
            return $role;
        }
        
        // 如果是 ID，查詢角色
        if ($role && is_numeric($role)) {
            return Role::find($role);
        }
        
        // 嘗試從其他路由參數取得ID
        $roleId = $request->route('id') ?? $request->input('role_id');
        
        if ($roleId && is_numeric($roleId)) {
            return Role::find($roleId);
        }

        // 從Livewire元件參數取得
        if ($request->header('X-Livewire')) {
            $livewireData = json_decode($request->getContent(), true);
            $roleId = $livewireData['serverMemo']['data']['role']['id'] ?? null;
            
            if ($roleId && is_numeric($roleId)) {
                return Role::find($roleId);
            }
        }

        return null;
    }

    /**
     * 記錄成功的存取
     */
    protected function logSuccessfulAccess(Request $request, string $action, ?Role $targetRole, $user): void
    {
        $this->auditLogService->logDataAccess('roles', $action, [
            'target_role_id' => $targetRole?->id,
            'target_role_name' => $targetRole?->name,
            'url' => $request->url(),
            'method' => $request->method(),
        ], $user);
    }

    /**
     * 記錄操作結果
     */
    protected function logOperationResult(Request $request, Response $response, string $action, ?Role $targetRole, $user): void
    {
        $statusCode = $response->getStatusCode();
        $isSuccess = $statusCode >= 200 && $statusCode < 300;

        if (!$isSuccess) {
            $this->auditLogService->logSecurityEvent('role_operation_failed', 'medium', [
                'action' => $action,
                'target_role_id' => $targetRole?->id,
                'target_role_name' => $targetRole?->name,
                'status_code' => $statusCode,
                'url' => $request->url(),
                'method' => $request->method(),
            ], $user);
        }
    }

    /**
     * 檢查是否為敏感操作
     */
    protected function isSensitiveOperation(string $action, ?Role $targetRole): bool
    {
        // 刪除操作總是敏感的
        if ($action === 'delete') {
            return true;
        }

        // 對系統角色的操作是敏感的
        if ($targetRole && $this->roleSecurityService->isSystemRole($targetRole)) {
            return true;
        }

        // 權限修改操作是敏感的
        if (in_array($action, ['modify_permissions', 'modify_hierarchy'])) {
            return true;
        }

        return false;
    }

    /**
     * 檢查請求頻率限制
     */
    protected function checkRateLimit(Request $request, $user): bool
    {
        $key = "role_operations:{$user->id}:" . now()->format('Y-m-d-H-i');
        $attempts = cache()->get($key, 0);
        
        // 每分鐘最多10次角色操作
        if ($attempts >= 10) {
            $this->auditLogService->logSecurityEvent('role_operation_rate_limit_exceeded', 'high', [
                'attempts' => $attempts,
                'url' => $request->url(),
                'method' => $request->method(),
            ], $user);
            
            return false;
        }

        cache()->put($key, $attempts + 1, 60);
        return true;
    }

    /**
     * 驗證請求資料完整性
     */
    protected function validateRequestIntegrity(Request $request): bool
    {
        // 檢查CSRF令牌（對於狀態改變的請求）
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            if (!$request->hasValidSignature() && !$request->session()->token()) {
                return false;
            }
        }

        // 檢查請求大小
        $contentLength = $request->header('Content-Length', 0);
        if ($contentLength > 1024 * 1024) { // 1MB限制
            return false;
        }

        // 檢查可疑的請求標頭
        $suspiciousHeaders = ['X-Forwarded-For', 'X-Real-IP'];
        foreach ($suspiciousHeaders as $header) {
            if ($request->hasHeader($header)) {
                $value = $request->header($header);
                if (preg_match('/[<>"\']/', $value)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * 清理和驗證輸入資料
     */
    protected function sanitizeInput(Request $request): void
    {
        $input = $request->all();
        
        foreach ($input as $key => $value) {
            if (is_string($value)) {
                // 移除潛在的惡意腳本
                $value = strip_tags($value);
                
                // 移除SQL注入嘗試
                $value = preg_replace('/(\b(SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|EXEC|UNION)\b)/i', '', $value);
                
                // 更新請求資料
                $request->merge([$key => $value]);
            }
        }
    }
}