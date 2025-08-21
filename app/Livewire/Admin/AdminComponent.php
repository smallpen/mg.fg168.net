<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Services\AuditLogService;
use App\Services\PermissionService;

/**
 * 管理後台基礎 Livewire 元件
 * 
 * 所有管理後台 Livewire 元件的基礎類別，提供統一的安全控制和審計功能
 */
abstract class AdminComponent extends Component
{
    /**
     * 元件掛載時執行
     * 
     * 初始化基本設定（權限檢查由中介軟體處理）
     */
    public function mount()
    {
        // 權限檢查已由中介軟體處理，這裡可以進行其他初始化工作
    }

    /**
     * 取得當前使用者
     * 
     * @return \App\Models\User|null
     */
    protected function getCurrentUser()
    {
        return auth()->user();
    }

    /**
     * 檢查使用者是否擁有特定權限
     * 
     * @param string $permission
     * @return bool
     */
    protected function hasPermission(string $permission): bool
    {
        return $this->getCurrentUser()?->hasPermission($permission) ?? false;
    }

    /**
     * 檢查使用者是否擁有特定權限（別名方法）
     * 
     * @param string $permission
     * @return bool
     */
    protected function can(string $permission): bool
    {
        return $this->hasPermission($permission);
    }

    /**
     * 檢查權限，如果沒有權限則拋出例外
     * 
     * @param string $permission
     * @param string $resource 資源名稱（用於審計日誌）
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function checkPermission(string $permission, string $resource = ''): void
    {
        if (!$this->hasPermission($permission)) {
            // 記錄權限檢查失敗
            $this->logPermissionDenied($permission, $resource);
            
            abort(403, __('admin.errors.unauthorized'));
        }
    }

    /**
     * 記錄權限檢查失敗
     */
    protected function logPermissionDenied(string $permission, string $resource = ''): void
    {
        try {
            $auditService = app(AuditLogService::class);
            $auditService->logPermissionDenied($permission, $resource, [
                'component' => static::class,
                'url' => request()->url(),
                'method' => request()->method(),
            ]);
        } catch (\Exception $e) {
            // 靜默處理審計日誌錯誤，避免影響主要功能
            logger()->error('Failed to log permission denied', [
                'permission' => $permission,
                'resource' => $resource,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 記錄使用者操作
     */
    protected function logUserAction(string $action, array $data = [], $targetUser = null): void
    {
        try {
            $auditService = app(AuditLogService::class);
            $auditService->logUserManagementAction($action, $data, $targetUser);
        } catch (\Exception $e) {
            logger()->error('Failed to log user action', [
                'action' => $action,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 記錄安全事件
     */
    protected function logSecurityEvent(string $event, string $severity = 'medium', array $data = []): void
    {
        try {
            $auditService = app(AuditLogService::class);
            $auditService->logSecurityEvent($event, $severity, array_merge($data, [
                'component' => static::class,
                'url' => request()->url(),
                'method' => request()->method(),
            ]));
        } catch (\Exception $e) {
            logger()->error('Failed to log security event', [
                'event' => $event,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 顯示成功訊息
     * 
     * @param string $message
     */
    protected function showSuccess(string $message): void
    {
        session()->flash('success', $message);
    }

    /**
     * 顯示錯誤訊息
     * 
     * @param string $message
     */
    protected function showError(string $message): void
    {
        session()->flash('error', $message);
    }

    /**
     * 新增 Flash 訊息
     * 
     * @param string $type 訊息類型 (success, error, warning, info)
     * @param string $message 訊息內容
     */
    protected function addFlash(string $type, string $message): void
    {
        session()->flash($type, $message);
    }
}