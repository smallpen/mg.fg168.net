<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * 角色安全服務
 * 
 * 提供角色管理的多層級安全控制，包括：
 * - 多層級權限檢查
 * - 系統角色保護機制
 * - 操作審計日誌記錄
 * - 資料驗證和清理
 */
class RoleSecurityService
{
    protected AuditLogService $auditLogService;
    protected PermissionService $permissionService;

    /**
     * 系統保護角色列表
     */
    const PROTECTED_SYSTEM_ROLES = [
        'super_admin',
        'admin',
        'system'
    ];

    /**
     * 核心權限列表（系統角色不能失去的權限）
     */
    const CORE_PERMISSIONS = [
        'admin.access',
        'roles.view',
        'users.view',
        'system.manage'
    ];

    public function __construct(
        AuditLogService $auditLogService,
        PermissionService $permissionService
    ) {
        $this->auditLogService = $auditLogService;
        $this->permissionService = $permissionService;
    }

    /**
     * 多層級權限檢查
     * 
     * @param string $action 要執行的操作
     * @param Role|null $targetRole 目標角色
     * @param User|null $user 執行操作的使用者
     * @return array 檢查結果
     */
    public function checkMultiLevelPermissions(string $action, ?Role $targetRole = null, ?User $user = null): array
    {
        $user = $user ?? Auth::user();
        
        if (!$user) {
            return [
                'allowed' => false,
                'reason' => 'user_not_authenticated',
                'message' => '使用者未登入'
            ];
        }

        // 第一層：基本權限檢查
        $basicPermissionCheck = $this->checkBasicPermission($action, $user);
        if (!$basicPermissionCheck['allowed']) {
            $this->auditLogService->logPermissionDenied($action, 'basic_permission', [
                'target_role_id' => $targetRole?->id,
                'reason' => $basicPermissionCheck['reason']
            ], $user);
            return $basicPermissionCheck;
        }

        // 第二層：角色層級檢查
        if ($targetRole) {
            $roleLevelCheck = $this->checkRoleLevelPermission($action, $targetRole, $user);
            if (!$roleLevelCheck['allowed']) {
                $this->auditLogService->logPermissionDenied($action, 'role_level', [
                    'target_role_id' => $targetRole->id,
                    'target_role_name' => $targetRole->name,
                    'reason' => $roleLevelCheck['reason']
                ], $user);
                return $roleLevelCheck;
            }
        }

        // 第三層：系統角色保護檢查
        if ($targetRole && $this->isSystemRole($targetRole)) {
            $systemProtectionCheck = $this->checkSystemRoleProtection($action, $targetRole, $user);
            if (!$systemProtectionCheck['allowed']) {
                $this->auditLogService->logSecurityEvent('system_role_protection_triggered', 'high', [
                    'action' => $action,
                    'target_role_id' => $targetRole->id,
                    'target_role_name' => $targetRole->name,
                    'reason' => $systemProtectionCheck['reason']
                ], $user);
                return $systemProtectionCheck;
            }
        }

        // 第四層：業務邏輯檢查
        $businessLogicCheck = $this->checkBusinessLogic($action, $targetRole, $user);
        if (!$businessLogicCheck['allowed']) {
            $this->auditLogService->logPermissionDenied($action, 'business_logic', [
                'target_role_id' => $targetRole?->id,
                'reason' => $businessLogicCheck['reason']
            ], $user);
            return $businessLogicCheck;
        }

        // 所有檢查通過
        return [
            'allowed' => true,
            'reason' => 'all_checks_passed',
            'message' => '權限檢查通過'
        ];
    }

    /**
     * 基本權限檢查
     */
    protected function checkBasicPermission(string $action, User $user): array
    {
        // 檢查使用者是否啟用
        if (!$user->is_active) {
            return [
                'allowed' => false,
                'reason' => 'user_inactive',
                'message' => '使用者帳號已停用'
            ];
        }

        // 檢查基本權限
        $requiredPermission = $this->getRequiredPermission($action);
        if (!$this->permissionService->hasPermission($requiredPermission, $user)) {
            return [
                'allowed' => false,
                'reason' => 'insufficient_permission',
                'message' => "缺少必要權限：{$requiredPermission}"
            ];
        }

        return ['allowed' => true];
    }

    /**
     * 角色層級權限檢查
     */
    protected function checkRoleLevelPermission(string $action, Role $targetRole, User $user): array
    {
        // 檢查是否可以操作比自己權限高的角色
        if (!$user->isSuperAdmin()) {
            $userMaxRoleLevel = $user->getMaxRoleLevel();
            $targetRoleLevel = $targetRole->getLevel();
            
            if ($targetRoleLevel >= $userMaxRoleLevel) {
                return [
                    'allowed' => false,
                    'reason' => 'insufficient_role_level',
                    'message' => '無法操作權限等級相同或更高的角色'
                ];
            }
        }

        // 檢查是否可以操作系統角色
        if ($this->isSystemRole($targetRole) && !$user->hasPermission('system.manage')) {
            return [
                'allowed' => false,
                'reason' => 'cannot_modify_system_role',
                'message' => '無權限修改系統角色'
            ];
        }

        return ['allowed' => true];
    }

    /**
     * 系統角色保護檢查
     */
    protected function checkSystemRoleProtection(string $action, Role $targetRole, User $user): array
    {
        // 超級管理員可以執行所有操作
        if ($user->isSuperAdmin()) {
            return ['allowed' => true];
        }

        // 檢查是否為受保護的系統角色
        if (in_array($targetRole->name, self::PROTECTED_SYSTEM_ROLES)) {
            $restrictedActions = ['delete', 'disable', 'modify_core_permissions'];
            
            if (in_array($action, $restrictedActions)) {
                return [
                    'allowed' => false,
                    'reason' => 'protected_system_role',
                    'message' => '受保護的系統角色不能執行此操作'
                ];
            }
        }

        // 檢查是否嘗試移除核心權限
        if ($action === 'modify_permissions') {
            $currentPermissions = $targetRole->permissions->pluck('name')->toArray();
            $corePermissionsInRole = array_intersect($currentPermissions, self::CORE_PERMISSIONS);
            
            if (!empty($corePermissionsInRole)) {
                return [
                    'allowed' => false,
                    'reason' => 'cannot_remove_core_permissions',
                    'message' => '不能移除系統角色的核心權限'
                ];
            }
        }

        return ['allowed' => true];
    }

    /**
     * 業務邏輯檢查
     */
    protected function checkBusinessLogic(string $action, ?Role $targetRole, User $user): array
    {
        switch ($action) {
            case 'delete':
                if ($targetRole) {
                    // 檢查角色是否有使用者關聯
                    if ($targetRole->users()->exists()) {
                        return [
                            'allowed' => false,
                            'reason' => 'role_has_users',
                            'message' => '角色仍有使用者關聯，無法刪除'
                        ];
                    }

                    // 檢查角色是否有子角色
                    if ($targetRole->children()->exists()) {
                        return [
                            'allowed' => false,
                            'reason' => 'role_has_children',
                            'message' => '角色仍有子角色，無法刪除'
                        ];
                    }
                }
                break;

            case 'modify_hierarchy':
                if ($targetRole) {
                    // 檢查是否會造成循環依賴
                    $parentId = request()->input('parent_id');
                    if ($parentId && $targetRole->hasCircularDependency($parentId)) {
                        return [
                            'allowed' => false,
                            'reason' => 'circular_dependency',
                            'message' => '設定父角色會造成循環依賴'
                        ];
                    }
                }
                break;
        }

        return ['allowed' => true];
    }

    /**
     * 驗證和清理角色資料
     */
    public function validateAndSanitizeRoleData(array $data, ?Role $existingRole = null): array
    {
        $sanitizedData = [];

        // 驗證和清理角色名稱
        if (isset($data['name'])) {
            $name = $this->sanitizeRoleName($data['name']);
            $this->validateRoleName($name, $existingRole);
            $sanitizedData['name'] = $name;
        }

        // 驗證和清理顯示名稱
        if (isset($data['display_name'])) {
            $displayName = $this->sanitizeDisplayName($data['display_name']);
            $this->validateDisplayName($displayName);
            $sanitizedData['display_name'] = $displayName;
        }

        // 驗證和清理描述
        if (isset($data['description'])) {
            $description = $this->sanitizeDescription($data['description']);
            $this->validateDescription($description);
            $sanitizedData['description'] = $description;
        }

        // 驗證父角色ID
        if (isset($data['parent_id'])) {
            $parentId = $this->validateParentId($data['parent_id'], $existingRole);
            $sanitizedData['parent_id'] = $parentId;
        }

        // 驗證啟用狀態
        if (isset($data['is_active'])) {
            $sanitizedData['is_active'] = (bool) $data['is_active'];
        }

        return $sanitizedData;
    }

    /**
     * 清理角色名稱
     */
    protected function sanitizeRoleName(string $name): string
    {
        // 移除危險字元，只保留字母、數字和底線
        $name = preg_replace('/[^a-zA-Z0-9_]/', '', trim($name));
        
        // 轉換為小寫
        $name = strtolower($name);
        
        // 限制長度
        return substr($name, 0, 50);
    }

    /**
     * 清理顯示名稱
     */
    protected function sanitizeDisplayName(string $displayName): string
    {
        // 移除HTML標籤和危險字元
        $displayName = strip_tags(trim($displayName));
        
        // 移除多餘的空白字元
        $displayName = preg_replace('/\s+/', ' ', $displayName);
        
        // 限制長度
        return substr($displayName, 0, 50);
    }

    /**
     * 清理描述
     */
    protected function sanitizeDescription(?string $description): ?string
    {
        if (empty($description)) {
            return null;
        }

        // 移除HTML標籤
        $description = strip_tags(trim($description));
        
        // 移除多餘的空白字元
        $description = preg_replace('/\s+/', ' ', $description);
        
        // 限制長度
        return substr($description, 0, 255);
    }

    /**
     * 驗證角色名稱
     */
    protected function validateRoleName(string $name, ?Role $existingRole = null): void
    {
        if (empty($name)) {
            throw ValidationException::withMessages([
                'name' => '角色名稱不能為空'
            ]);
        }

        if (strlen($name) < 2) {
            throw ValidationException::withMessages([
                'name' => '角色名稱至少需要2個字元'
            ]);
        }

        if (!preg_match('/^[a-z_][a-z0-9_]*$/', $name)) {
            throw ValidationException::withMessages([
                'name' => '角色名稱只能包含小寫字母、數字和底線，且必須以字母或底線開頭'
            ]);
        }

        // 檢查名稱是否重複
        $query = Role::where('name', $name);
        if ($existingRole) {
            $query->where('id', '!=', $existingRole->id);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'name' => '角色名稱已存在'
            ]);
        }

        // 檢查是否為保留名稱
        $reservedNames = ['root', 'system', 'guest', 'anonymous', 'public'];
        if (in_array($name, $reservedNames)) {
            throw ValidationException::withMessages([
                'name' => '此名稱為系統保留名稱'
            ]);
        }
    }

    /**
     * 驗證顯示名稱
     */
    protected function validateDisplayName(string $displayName): void
    {
        if (empty($displayName)) {
            throw ValidationException::withMessages([
                'display_name' => '顯示名稱不能為空'
            ]);
        }

        if (strlen($displayName) < 2) {
            throw ValidationException::withMessages([
                'display_name' => '顯示名稱至少需要2個字元'
            ]);
        }
    }

    /**
     * 驗證描述
     */
    protected function validateDescription(?string $description): void
    {
        if ($description && strlen($description) > 255) {
            throw ValidationException::withMessages([
                'description' => '描述不能超過255個字元'
            ]);
        }
    }

    /**
     * 驗證父角色ID
     */
    protected function validateParentId(?int $parentId, ?Role $existingRole = null): ?int
    {
        if (!$parentId) {
            return null;
        }

        // 檢查父角色是否存在
        $parentRole = Role::find($parentId);
        if (!$parentRole) {
            throw ValidationException::withMessages([
                'parent_id' => '指定的父角色不存在'
            ]);
        }

        // 檢查是否會造成循環依賴
        if ($existingRole && $existingRole->hasCircularDependency($parentId)) {
            throw ValidationException::withMessages([
                'parent_id' => '設定此父角色會造成循環依賴'
            ]);
        }

        return $parentId;
    }

    /**
     * 記錄角色操作審計日誌
     */
    public function logRoleOperation(string $operation, Role $role, array $data = [], ?User $user = null): void
    {
        $user = $user ?? Auth::user();
        
        $logData = [
            'operation' => $operation,
            'role_id' => $role->id,
            'role_name' => $role->name,
            'role_display_name' => $role->display_name,
            'is_system_role' => $role->is_system_role,
            'data' => $data,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ];

        // 記錄到審計日誌
        $this->auditLogService->logUserManagementAction(
            "role_{$operation}",
            $logData,
            null,
            $user
        );

        // 記錄到系統日誌
        Log::info("角色操作: {$operation}", array_merge($logData, [
            'user_id' => $user?->id,
            'username' => $user?->username,
        ]));
    }

    /**
     * 檢查是否為系統角色
     */
    public function isSystemRole(Role $role): bool
    {
        return $role->is_system_role || in_array($role->name, self::PROTECTED_SYSTEM_ROLES);
    }

    /**
     * 取得操作所需的權限
     */
    protected function getRequiredPermission(string $action): string
    {
        return match ($action) {
            'create' => 'roles.create',
            'edit', 'modify_permissions', 'modify_hierarchy' => 'roles.edit',
            'delete', 'disable' => 'roles.delete',
            'view' => 'roles.view',
            default => 'roles.manage'
        };
    }

    /**
     * 檢查角色刪除的安全性
     */
    public function checkRoleDeletionSecurity(Role $role, ?User $user = null): array
    {
        $user = $user ?? Auth::user();
        $issues = [];

        // 檢查是否為系統角色
        if ($this->isSystemRole($role)) {
            $issues[] = [
                'type' => 'error',
                'code' => 'system_role',
                'message' => '系統角色不能刪除',
                'blocking' => true
            ];
        }

        // 檢查使用者關聯
        $userCount = $role->users()->count();
        if ($userCount > 0) {
            $issues[] = [
                'type' => 'error',
                'code' => 'has_users',
                'message' => "角色仍有 {$userCount} 個使用者關聯",
                'blocking' => true,
                'count' => $userCount
            ];
        }

        // 檢查子角色
        $childCount = $role->children()->count();
        if ($childCount > 0) {
            $issues[] = [
                'type' => 'warning',
                'code' => 'has_children',
                'message' => "角色有 {$childCount} 個子角色，刪除後子角色將失去父角色",
                'blocking' => false,
                'count' => $childCount
            ];
        }

        // 檢查權限數量
        $permissionCount = $role->permissions()->count();
        if ($permissionCount > 0) {
            $issues[] = [
                'type' => 'info',
                'code' => 'has_permissions',
                'message' => "角色擁有 {$permissionCount} 個權限，刪除後權限關聯將被移除",
                'blocking' => false,
                'count' => $permissionCount
            ];
        }

        $hasBlockingIssues = collect($issues)->contains('blocking', true);

        return [
            'can_delete' => !$hasBlockingIssues,
            'issues' => $issues,
            'has_blocking_issues' => $hasBlockingIssues,
            'requires_confirmation' => !empty($issues)
        ];
    }

    /**
     * 執行安全的角色刪除
     */
    public function secureRoleDelete(Role $role, bool $forceDelete = false, ?User $user = null): array
    {
        $user = $user ?? Auth::user();

        // 執行安全檢查
        $securityCheck = $this->checkRoleDeletionSecurity($role, $user);
        
        if (!$securityCheck['can_delete'] && !$forceDelete) {
            throw new \Exception('角色刪除安全檢查失敗');
        }

        // 記錄刪除前的狀態
        $preDeleteData = [
            'role_id' => $role->id,
            'role_name' => $role->name,
            'role_display_name' => $role->display_name,
            'is_system_role' => $role->is_system_role,
            'user_count' => $role->users()->count(),
            'permission_count' => $role->permissions()->count(),
            'child_count' => $role->children()->count(),
            'force_delete' => $forceDelete,
            'security_issues' => $securityCheck['issues']
        ];

        try {
            DB::transaction(function () use ($role, $forceDelete) {
                // 如果強制刪除，先處理關聯
                if ($forceDelete) {
                    // 移除使用者關聯
                    $role->users()->detach();
                    
                    // 處理子角色（設定 parent_id 為 null）
                    $role->children()->update(['parent_id' => null]);
                }

                // 移除權限關聯
                $role->permissions()->detach();

                // 執行軟刪除
                $role->delete();
            });

            // 記錄成功的刪除操作
            $this->logRoleOperation('delete', $role, $preDeleteData, $user);

            return [
                'success' => true,
                'message' => '角色刪除成功',
                'deleted_role' => $preDeleteData
            ];

        } catch (\Exception $e) {
            // 記錄刪除失敗
            $this->auditLogService->logSecurityEvent('role_delete_failed', 'high', [
                'role_data' => $preDeleteData,
                'error' => $e->getMessage()
            ], $user);

            throw $e;
        }
    }

    /**
     * 驗證權限設定的安全性
     */
    public function validatePermissionAssignment(Role $role, array $permissionIds, ?User $user = null): array
    {
        $user = $user ?? Auth::user();
        $issues = [];

        // 檢查是否為系統角色
        if ($this->isSystemRole($role)) {
            $currentPermissions = $role->permissions->pluck('name')->toArray();
            $corePermissionsInRole = array_intersect($currentPermissions, self::CORE_PERMISSIONS);
            
            // 檢查是否嘗試移除核心權限
            $newPermissions = \App\Models\Permission::whereIn('id', $permissionIds)->pluck('name')->toArray();
            $removedCorePermissions = array_diff($corePermissionsInRole, $newPermissions);
            
            if (!empty($removedCorePermissions)) {
                $issues[] = [
                    'type' => 'error',
                    'code' => 'core_permissions_removed',
                    'message' => '不能移除系統角色的核心權限: ' . implode(', ', $removedCorePermissions),
                    'blocking' => true
                ];
            }
        }

        // 檢查使用者是否有權限指派這些權限
        if (!$user->isSuperAdmin()) {
            $userPermissions = $user->getAllPermissions()->pluck('name')->toArray();
            $requestedPermissions = \App\Models\Permission::whereIn('id', $permissionIds)->pluck('name')->toArray();
            $unauthorizedPermissions = array_diff($requestedPermissions, $userPermissions);
            
            if (!empty($unauthorizedPermissions)) {
                $issues[] = [
                    'type' => 'error',
                    'code' => 'insufficient_permission_to_assign',
                    'message' => '您沒有權限指派以下權限: ' . implode(', ', $unauthorizedPermissions),
                    'blocking' => true
                ];
            }
        }

        $hasBlockingIssues = collect($issues)->contains('blocking', true);

        return [
            'valid' => !$hasBlockingIssues,
            'issues' => $issues,
            'has_blocking_issues' => $hasBlockingIssues
        ];
    }
}