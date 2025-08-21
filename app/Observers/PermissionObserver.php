<?php

namespace App\Observers;

use App\Models\Permission;
use App\Services\PermissionAuditService;

/**
 * 權限模型觀察者
 * 
 * 自動記錄權限的建立、更新、刪除等操作
 */
class PermissionObserver
{
    /**
     * 權限審計服務
     * 
     * @var PermissionAuditService
     */
    protected $auditService;

    /**
     * 建構函式
     * 
     * @param PermissionAuditService $auditService
     */
    public function __construct(PermissionAuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Handle the Permission "created" event.
     */
    public function created(Permission $permission): void
    {
        $this->auditService->logPermissionChange('created', $permission, [
            'permission_data' => $permission->toArray(),
        ]);
    }

    /**
     * Handle the Permission "updated" event.
     */
    public function updated(Permission $permission): void
    {
        $changes = [];
        
        // 取得變更的欄位
        foreach ($permission->getDirty() as $field => $newValue) {
            $oldValue = $permission->getOriginal($field);
            $changes[$field] = [
                'old' => $oldValue,
                'new' => $newValue,
            ];
        }

        if (!empty($changes)) {
            $this->auditService->logPermissionChange('updated', $permission, [
                'changes' => $changes,
                'updated_fields' => array_keys($changes),
            ]);
        }
    }

    /**
     * Handle the Permission "deleted" event.
     */
    public function deleted(Permission $permission): void
    {
        $this->auditService->logPermissionChange('deleted', $permission, [
            'deleted_permission_data' => $permission->toArray(),
            'had_dependencies' => $permission->dependencies()->exists(),
            'had_dependents' => $permission->dependents()->exists(),
            'was_in_use' => $permission->isInUse(),
            'role_count' => $permission->role_count,
        ]);
    }

    /**
     * Handle the Permission "restored" event.
     */
    public function restored(Permission $permission): void
    {
        $this->auditService->logPermissionChange('restored', $permission, [
            'restored_permission_data' => $permission->toArray(),
        ]);
    }

    /**
     * Handle the Permission "force deleted" event.
     */
    public function forceDeleted(Permission $permission): void
    {
        $this->auditService->logPermissionChange('force_deleted', $permission, [
            'force_deleted_permission_data' => $permission->toArray(),
        ]);
    }
}
