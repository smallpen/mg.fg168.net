<?php

namespace App\Traits;

use App\Models\Role;
use App\Models\User;
use App\Services\RoleSecurityService;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Auth;

/**
 * 角色安全特徵
 * 
 * 提供角色管理元件的通用安全功能
 */
trait RoleSecurityTrait
{
    /**
     * 檢查角色操作權限
     */
    protected function checkRoleOperationPermission(string $operation, ?Role $role = null, ?User $user = null): bool
    {
        $user = $user ?? Auth::user();
        
        if (!$user) {
            return false;
        }

        // 取得安全服務
        $securityService = app(RoleSecurityService::class);
        
        // 執行多層級安全檢查
        $securityCheck = $securityService->checkMultiLevelPermissions($operation, $role, $user);
        
        if (!$securityCheck['allowed']) {
            // 記錄權限檢查失敗
            app(AuditLogService::class)->logPermissionDenied($operation, 'role_operation', [
                'target_role_id' => $role?->id,
                'target_role_name' => $role?->name,
                'reason' => $securityCheck['reason'],
                'message' => $securityCheck['message']
            ], $user);
            
            return false;
        }

        return true;
    }

    /**
     * 檢查是否可以修改角色
     */
    protected function canModifyRole(Role $role, ?User $user = null): bool
    {
        return $this->checkRoleOperationPermission('edit', $role, $user);
    }

    /**
     * 檢查是否可以刪除角色
     */
    protected function canDeleteRole(Role $role, ?User $user = null): bool
    {
        return $this->checkRoleOperationPermission('delete', $role, $user);
    }

    /**
     * 檢查是否可以建立角色
     */
    protected function canCreateRole(?User $user = null): bool
    {
        return $this->checkRoleOperationPermission('create', null, $user);
    }

    /**
     * 檢查是否可以檢視角色
     */
    protected function canViewRole(Role $role, ?User $user = null): bool
    {
        return $this->checkRoleOperationPermission('view', $role, $user);
    }

    /**
     * 檢查是否為系統角色
     */
    protected function isSystemRole(Role $role): bool
    {
        return app(RoleSecurityService::class)->isSystemRole($role);
    }

    /**
     * 記錄角色操作
     */
    protected function logRoleOperation(string $operation, Role $role, array $data = [], ?User $user = null): void
    {
        app(RoleSecurityService::class)->logRoleOperation($operation, $role, $data, $user);
    }

    /**
     * 記錄安全事件
     */
    protected function logSecurityEvent(string $event, string $severity = 'medium', array $data = [], ?User $user = null): void
    {
        app(AuditLogService::class)->logSecurityEvent($event, $severity, $data, $user);
    }

    /**
     * 驗證角色資料
     */
    protected function validateRoleData(array $data, ?Role $existingRole = null): array
    {
        return app(RoleSecurityService::class)->validateAndSanitizeRoleData($data, $existingRole);
    }

    /**
     * 檢查批量操作權限
     */
    protected function checkBulkOperationPermission(string $operation, array $roleIds, ?User $user = null): array
    {
        $user = $user ?? Auth::user();
        $results = [];
        $allowedRoles = [];
        $deniedRoles = [];

        foreach ($roleIds as $roleId) {
            $role = Role::find($roleId);
            if (!$role) {
                $deniedRoles[] = [
                    'id' => $roleId,
                    'reason' => 'role_not_found',
                    'message' => '角色不存在'
                ];
                continue;
            }

            if ($this->checkRoleOperationPermission($operation, $role, $user)) {
                $allowedRoles[] = $role;
            } else {
                $deniedRoles[] = [
                    'id' => $roleId,
                    'name' => $role->name,
                    'display_name' => $role->display_name,
                    'reason' => 'permission_denied',
                    'message' => '權限不足'
                ];
            }
        }

        return [
            'allowed_roles' => $allowedRoles,
            'denied_roles' => $deniedRoles,
            'can_proceed' => !empty($allowedRoles),
            'total_allowed' => count($allowedRoles),
            'total_denied' => count($deniedRoles)
        ];
    }

    /**
     * 安全地執行角色操作
     */
    protected function secureRoleOperation(callable $operation, string $operationName, ?Role $role = null, array $context = []): mixed
    {
        $user = Auth::user();
        
        try {
            // 檢查權限
            if (!$this->checkRoleOperationPermission($operationName, $role, $user)) {
                throw new \Exception('權限不足，無法執行此操作');
            }

            // 記錄操作開始
            $this->logSecurityEvent("role_operation_started", 'info', [
                'operation' => $operationName,
                'role_id' => $role?->id,
                'role_name' => $role?->name,
                'context' => $context
            ], $user);

            // 執行操作
            $result = $operation();

            // 記錄操作成功
            if ($role) {
                $this->logRoleOperation($operationName, $role, array_merge($context, [
                    'result' => 'success'
                ]), $user);
            }

            return $result;

        } catch (\Exception $e) {
            // 記錄操作失敗
            $this->logSecurityEvent("role_operation_failed", 'high', [
                'operation' => $operationName,
                'role_id' => $role?->id,
                'role_name' => $role?->name,
                'error' => $e->getMessage(),
                'context' => $context
            ], $user);

            throw $e;
        }
    }

    /**
     * 檢查角色層級權限
     */
    protected function checkRoleHierarchyPermission(Role $role, ?int $newParentId = null, ?User $user = null): bool
    {
        $user = $user ?? Auth::user();
        
        if (!$user) {
            return false;
        }

        // 超級管理員可以修改任何層級
        if ($user->isSuperAdmin()) {
            return true;
        }

        // 檢查使用者是否可以修改此角色的層級
        $userMaxLevel = $user->getMaxRoleLevel();
        $roleLevel = $role->getLevel();
        
        // 不能修改比自己權限高或相等的角色
        if ($roleLevel >= $userMaxLevel) {
            return false;
        }

        // 如果設定新的父角色，檢查新父角色的層級
        if ($newParentId) {
            $newParent = Role::find($newParentId);
            if ($newParent) {
                $newParentLevel = $newParent->getLevel();
                
                // 不能設定比自己權限高的角色為父角色
                if ($newParentLevel >= $userMaxLevel) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * 檢查權限指派權限
     */
    protected function checkPermissionAssignmentPermission(Role $role, array $permissionIds, ?User $user = null): bool
    {
        $user = $user ?? Auth::user();
        
        if (!$user) {
            return false;
        }

        // 超級管理員可以指派任何權限
        if ($user->isSuperAdmin()) {
            return true;
        }

        // 檢查使用者是否擁有要指派的所有權限
        $userPermissions = $user->getAllPermissions()->pluck('id')->toArray();
        $unauthorizedPermissions = array_diff($permissionIds, $userPermissions);
        
        if (!empty($unauthorizedPermissions)) {
            return false;
        }

        // 檢查是否可以修改此角色
        return $this->canModifyRole($role, $user);
    }

    /**
     * 取得角色操作的安全上下文
     */
    protected function getRoleSecurityContext(Role $role, ?User $user = null): array
    {
        $user = $user ?? Auth::user();
        
        return [
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'role_id' => $role->id,
            'role_name' => $role->name,
            'role_display_name' => $role->display_name,
            'is_system_role' => $this->isSystemRole($role),
            'user_can_modify' => $this->canModifyRole($role, $user),
            'user_can_delete' => $this->canDeleteRole($role, $user),
            'user_max_role_level' => $user?->getMaxRoleLevel(),
            'role_level' => $role->getLevel(),
            'timestamp' => now()->toISOString(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ];
    }

    /**
     * 驗證操作頻率限制
     */
    protected function checkOperationRateLimit(string $operation, ?User $user = null): bool
    {
        $user = $user ?? Auth::user();
        
        if (!$user) {
            return false;
        }

        $key = "role_operation_rate_limit:{$user->id}:{$operation}:" . now()->format('Y-m-d-H-i');
        $attempts = cache()->get($key, 0);
        
        // 根據操作類型設定不同的限制
        $limits = [
            'create' => 5,    // 每分鐘最多建立5個角色
            'edit' => 10,     // 每分鐘最多編輯10個角色
            'delete' => 3,    // 每分鐘最多刪除3個角色
            'bulk' => 2,      // 每分鐘最多2次批量操作
            'default' => 10   // 預設限制
        ];
        
        $limit = $limits[$operation] ?? $limits['default'];
        
        if ($attempts >= $limit) {
            $this->logSecurityEvent('operation_rate_limit_exceeded', 'high', [
                'operation' => $operation,
                'attempts' => $attempts,
                'limit' => $limit
            ], $user);
            
            return false;
        }

        cache()->put($key, $attempts + 1, 60);
        return true;
    }
}