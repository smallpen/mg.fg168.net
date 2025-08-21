<?php

namespace App\Observers;

use App\Models\Permission;
use App\Services\PermissionSecurityService;
use App\Services\PermissionAuditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * 權限安全觀察者
 * 
 * 自動記錄權限模型的所有變更，並執行安全檢查
 */
class PermissionSecurityObserver
{
    protected PermissionSecurityService $securityService;
    protected PermissionAuditService $auditService;

    public function __construct(
        PermissionSecurityService $securityService,
        PermissionAuditService $auditService
    ) {
        $this->securityService = $securityService;
        $this->auditService = $auditService;
    }

    /**
     * 權限建立前的處理
     */
    public function creating(Permission $permission): void
    {
        $user = Auth::user();

        // 執行建立前的安全檢查
        try {
            $this->securityService->checkMultiLevelPermission('create', null, $user);
        } catch (\Exception $e) {
            Log::error('權限建立安全檢查失敗', [
                'permission_name' => $permission->name,
                'user_id' => $user?->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        // 記錄建立嘗試
        $this->auditService->log('permission_creating', $permission, [
            'permission_data' => $permission->toArray(),
        ], $user);
    }

    /**
     * 權限建立後的處理
     */
    public function created(Permission $permission): void
    {
        $user = Auth::user();

        // 記錄建立成功
        $this->auditService->logPermissionChange('created', $permission, [], $user);

        // 清除相關快取
        $this->clearRelatedCache($permission);

        // 記錄安全事件
        $this->logSecurityEvent('permission_created', $permission, $user);

        Log::info('權限建立成功', [
            'permission_id' => $permission->id,
            'permission_name' => $permission->name,
            'user_id' => $user?->id,
        ]);
    }

    /**
     * 權限更新前的處理
     */
    public function updating(Permission $permission): void
    {
        $user = Auth::user();
        $changes = $permission->getDirty();

        // 執行更新前的安全檢查
        try {
            $this->securityService->checkMultiLevelPermission('update', $permission, $user);
        } catch (\Exception $e) {
            Log::error('權限更新安全檢查失敗', [
                'permission_id' => $permission->id,
                'permission_name' => $permission->name,
                'user_id' => $user?->id,
                'changes' => $changes,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        // 檢查系統權限保護
        if ($this->securityService->isSystemCorePermission($permission->name)) {
            $this->validateSystemPermissionUpdate($permission, $changes, $user);
        }

        // 記錄更新嘗試
        $this->auditService->log('permission_updating', $permission, [
            'changes' => $changes,
            'original' => $permission->getOriginal(),
        ], $user);
    }

    /**
     * 權限更新後的處理
     */
    public function updated(Permission $permission): void
    {
        $user = Auth::user();
        $changes = $permission->getChanges();

        // 記錄更新成功
        $this->auditService->logPermissionChange('updated', $permission, $changes, $user);

        // 清除相關快取
        $this->clearRelatedCache($permission);

        // 記錄安全事件
        $this->logSecurityEvent('permission_updated', $permission, $user, [
            'changes' => $changes,
        ]);

        // 如果是重要欄位的變更，記錄額外的安全事件
        $criticalFields = ['name', 'module', 'type'];
        $criticalChanges = array_intersect_key($changes, array_flip($criticalFields));
        
        if (!empty($criticalChanges)) {
            $this->logSecurityEvent('permission_critical_fields_updated', $permission, $user, [
                'critical_changes' => $criticalChanges,
            ]);
        }

        Log::info('權限更新成功', [
            'permission_id' => $permission->id,
            'permission_name' => $permission->name,
            'user_id' => $user?->id,
            'changes' => $changes,
        ]);
    }

    /**
     * 權限刪除前的處理
     */
    public function deleting(Permission $permission): void
    {
        $user = Auth::user();

        // 執行刪除前的安全檢查
        try {
            $this->securityService->checkMultiLevelPermission('delete', $permission, $user);
        } catch (\Exception $e) {
            Log::error('權限刪除安全檢查失敗', [
                'permission_id' => $permission->id,
                'permission_name' => $permission->name,
                'user_id' => $user?->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        // 檢查系統權限保護
        if ($this->securityService->isSystemCorePermission($permission->name)) {
            throw new \Exception('不能刪除系統核心權限');
        }

        // 檢查是否被使用
        if ($permission->roles()->exists()) {
            $roleNames = $permission->roles()->pluck('name')->toArray();
            throw new \Exception('權限仍被以下角色使用：' . implode('、', $roleNames));
        }

        // 檢查是否被依賴
        if ($permission->dependents()->exists()) {
            $dependentNames = $permission->dependents()->pluck('name')->toArray();
            throw new \Exception('以下權限依賴此權限：' . implode('、', $dependentNames));
        }

        // 記錄刪除嘗試
        $this->auditService->log('permission_deleting', $permission, [
            'permission_data' => $permission->toArray(),
        ], $user);
    }

    /**
     * 權限刪除後的處理
     */
    public function deleted(Permission $permission): void
    {
        $user = Auth::user();

        // 記錄刪除成功
        $this->auditService->logPermissionChange('deleted', $permission, [], $user);

        // 清除相關快取
        $this->clearRelatedCache($permission);

        // 記錄安全事件
        $this->logSecurityEvent('permission_deleted', $permission, $user);

        Log::warning('權限刪除成功', [
            'permission_id' => $permission->id,
            'permission_name' => $permission->name,
            'user_id' => $user?->id,
        ]);
    }

    /**
     * 權限恢復後的處理
     */
    public function restored(Permission $permission): void
    {
        $user = Auth::user();

        // 記錄恢復
        $this->auditService->logPermissionChange('restored', $permission, [], $user);

        // 清除相關快取
        $this->clearRelatedCache($permission);

        // 記錄安全事件
        $this->logSecurityEvent('permission_restored', $permission, $user);

        Log::info('權限恢復成功', [
            'permission_id' => $permission->id,
            'permission_name' => $permission->name,
            'user_id' => $user?->id,
        ]);
    }

    /**
     * 權限強制刪除前的處理
     */
    public function forceDeleting(Permission $permission): void
    {
        $user = Auth::user();

        // 強制刪除需要超級管理員權限
        if (!$user || !$user->hasRole('super_admin')) {
            throw new \Exception('只有超級管理員可以強制刪除權限');
        }

        // 記錄強制刪除嘗試
        $this->auditService->log('permission_force_deleting', $permission, [
            'permission_data' => $permission->toArray(),
        ], $user);

        // 記錄高風險安全事件
        $this->logSecurityEvent('permission_force_delete_attempt', $permission, $user, [], 'high');
    }

    /**
     * 權限強制刪除後的處理
     */
    public function forceDeleted(Permission $permission): void
    {
        $user = Auth::user();

        // 記錄強制刪除成功
        $this->auditService->logPermissionChange('force_deleted', $permission, [], $user);

        // 清除相關快取
        $this->clearRelatedCache($permission);

        // 記錄高風險安全事件
        $this->logSecurityEvent('permission_force_deleted', $permission, $user, [], 'high');

        Log::critical('權限強制刪除成功', [
            'permission_id' => $permission->id,
            'permission_name' => $permission->name,
            'user_id' => $user?->id,
        ]);
    }

    /**
     * 驗證系統權限更新
     */
    protected function validateSystemPermissionUpdate(Permission $permission, array $changes, $user): void
    {
        // 系統權限只能由超級管理員修改
        if (!$user || !$user->hasRole('super_admin')) {
            throw new \Exception('只有超級管理員可以修改系統核心權限');
        }

        // 某些欄位不能修改
        $protectedFields = ['name', 'module'];
        $protectedChanges = array_intersect_key($changes, array_flip($protectedFields));
        
        if (!empty($protectedChanges)) {
            throw new \Exception('系統核心權限的名稱和模組不能修改');
        }

        // 記錄系統權限修改嘗試
        $this->logSecurityEvent('system_permission_modification_attempt', $permission, $user, [
            'changes' => $changes,
        ], 'high');
    }

    /**
     * 記錄安全事件
     */
    protected function logSecurityEvent(
        string $event, 
        Permission $permission, 
        $user, 
        array $additionalData = [], 
        string $severity = 'medium'
    ): void {
        $eventData = array_merge([
            'permission_id' => $permission->id,
            'permission_name' => $permission->name,
            'permission_module' => $permission->module,
            'permission_type' => $permission->type,
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ], $additionalData);

        // 記錄到應用程式日誌
        $logLevel = $severity === 'high' ? 'warning' : ($severity === 'medium' ? 'info' : 'debug');
        Log::$logLevel("權限安全事件: {$event}", $eventData);

        // 記錄到審計服務
        $this->auditService->log("security_event_{$event}", $permission, $eventData, $user);
    }

    /**
     * 清除相關快取
     */
    protected function clearRelatedCache(Permission $permission): void
    {
        try {
            // 清除權限相關的快取
            $permission->clearAllRelatedCache();

            // 清除使用者權限快取
            $userIds = $permission->roles()
                                 ->with('users')
                                 ->get()
                                 ->pluck('users')
                                 ->flatten()
                                 ->pluck('id')
                                 ->unique();

            foreach ($userIds as $userId) {
                cache()->forget("user_permissions_{$userId}");
                cache()->forget("user_roles_{$userId}");
            }

            // 清除角色權限快取
            $roleIds = $permission->roles()->pluck('id');
            foreach ($roleIds as $roleId) {
                cache()->forget("role_permissions_{$roleId}");
            }

            Log::debug('權限相關快取已清除', [
                'permission_id' => $permission->id,
                'affected_users' => $userIds->count(),
                'affected_roles' => $roleIds->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('清除權限快取失敗', [
                'permission_id' => $permission->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 檢查操作是否被允許
     */
    protected function isOperationAllowed(string $operation, Permission $permission, $user): bool
    {
        try {
            return $this->securityService->checkMultiLevelPermission($operation, $permission, $user);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 記錄操作失敗
     */
    protected function logOperationFailure(string $operation, Permission $permission, $user, string $reason): void
    {
        Log::warning("權限{$operation}操作失敗", [
            'operation' => $operation,
            'permission_id' => $permission->id,
            'permission_name' => $permission->name,
            'user_id' => $user?->id,
            'reason' => $reason,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $this->auditService->log("permission_{$operation}_failed", $permission, [
            'reason' => $reason,
        ], $user);
    }

    /**
     * 檢查是否為批量操作
     */
    protected function isBulkOperation(): bool
    {
        return request()->has('bulk_action') || 
               request()->has('selected_permissions') ||
               str_contains(request()->url(), 'bulk');
    }

    /**
     * 處理批量操作的安全檢查
     */
    protected function handleBulkOperationSecurity(string $operation, $user): void
    {
        // 批量操作需要額外的權限
        if (!$user->hasPermission('permissions.bulk_operations')) {
            throw new \Exception('您沒有執行批量操作的權限');
        }

        // 檢查批量操作頻率限制
        $cacheKey = "bulk_operation_{$user->id}";
        $recentOperations = cache()->get($cacheKey, 0);

        if ($recentOperations >= 5) {
            throw new \Exception('批量操作頻率過高，請稍後再試');
        }

        cache()->put($cacheKey, $recentOperations + 1, now()->addHour());

        // 記錄批量操作嘗試
        $this->logSecurityEvent('bulk_operation_attempt', new Permission(), $user, [
            'operation' => $operation,
            'recent_operations' => $recentOperations,
        ], 'medium');
    }
}