<?php

namespace App\Http\Livewire\Admin\Roles;

use App\Http\Livewire\Admin\AdminComponent;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;

/**
 * 角色刪除確認 Livewire 元件
 * 
 * 處理角色刪除確認對話框和安全的角色資料移除邏輯
 */
class RoleDelete extends AdminComponent
{
    /**
     * 是否顯示確認對話框
     * 
     * @var bool
     */
    public $showConfirmDialog = false;

    /**
     * 要刪除的角色 ID
     * 
     * @var int|null
     */
    public $roleToDelete = null;

    /**
     * 要刪除的角色物件
     * 
     * @var Role|null
     */
    public $role = null;

    /**
     * 確認輸入的角色名稱
     * 
     * @var string
     */
    public $confirmRoleName = '';

    /**
     * 是否強制刪除（即使有使用者使用此角色）
     * 
     * @var bool
     */
    public $forceDelete = false;

    /**
     * 是否正在處理刪除
     * 
     * @var bool
     */
    public $isProcessing = false;

    /**
     * 即時驗證確認角色名稱
     */
    public function updatedConfirmRoleName()
    {
        if ($this->role) {
            if ($this->confirmRoleName !== $this->role->name) {
                $this->addError('confirmRoleName', __('admin.roles.confirm_role_name_mismatch'));
            } else {
                $this->resetErrorBag('confirmRoleName');
            }
        }
    }

    /**
     * 元件掛載
     */
    public function mount()
    {
        parent::mount();
        
        // 檢查刪除權限
        if (!$this->hasPermission('roles.delete')) {
            abort(403, __('admin.roles.no_permission_delete'));
        }
    }

    /**
     * 顯示刪除確認對話框
     * 
     * @param int $roleId
     */
    #[On('confirmRoleDelete')]
    public function showDeleteConfirmation($roleId)
    {
        $this->roleToDelete = $roleId;
        $this->role = Role::withCount(['users', 'permissions'])->findOrFail($roleId);
        
        // 檢查是否可以刪除此角色
        if (!$this->canDeleteRole($this->role)) {
            return;
        }

        // 重設表單狀態
        $this->confirmRoleName = '';
        $this->forceDelete = false;
        $this->showConfirmDialog = true;
    }

    /**
     * 檢查是否可以刪除角色
     * 
     * @param Role $role
     * @return bool
     */
    private function canDeleteRole(Role $role): bool
    {
        // 防止刪除超級管理員角色
        if ($role->name === 'super_admin') {
            $this->showError(__('admin.roles.cannot_delete_super_admin'));
            return false;
        }

        // 防止刪除系統預設角色（除非是超級管理員）
        $systemRoles = ['admin', 'user'];
        if (in_array($role->name, $systemRoles) && !auth()->user()->isSuperAdmin()) {
            $this->showError(__('admin.roles.cannot_delete_system_role'));
            return false;
        }

        return true;
    }

    /**
     * 關閉確認對話框
     */
    public function closeDialog()
    {
        // 如果正在處理中，不允許關閉對話框
        if ($this->isProcessing) {
            return;
        }

        $this->showConfirmDialog = false;
        $this->roleToDelete = null;
        $this->role = null;
        $this->confirmRoleName = '';
        $this->forceDelete = false;
        $this->isProcessing = false;
        $this->resetErrorBag();
    }

    /**
     * 執行角色刪除
     */
    public function confirmDelete()
    {
        // 重新檢查角色是否存在
        if (!$this->role || !Role::find($this->roleToDelete)) {
            $this->showError(__('admin.roles.role_not_found'));
            $this->closeDialog();
            return;
        }

        // 重新檢查權限
        if (!$this->canDeleteRole($this->role)) {
            $this->closeDialog();
            return;
        }

        // 驗證確認輸入
        $this->validate([
            'confirmRoleName' => 'required|string',
        ], [
            'confirmRoleName.required' => __('admin.roles.confirm_role_name_required'),
        ]);

        // 檢查輸入的角色名稱是否正確
        if ($this->confirmRoleName !== $this->role->name) {
            $this->addError('confirmRoleName', __('admin.roles.confirm_role_name_mismatch'));
            return;
        }

        // 檢查是否有使用者正在使用此角色
        $usersCount = $this->role->users()->count();
        if ($usersCount > 0 && !$this->forceDelete) {
            $this->addError('forceDelete', __('admin.roles.role_has_users', ['count' => $usersCount]));
            return;
        }

        $this->isProcessing = true;

        try {
            DB::beginTransaction();

            $this->performRoleDelete();

            DB::commit();

            // 發出事件通知父元件刷新列表
            $this->dispatch('roleDeleted', [
                'roleId' => $this->roleToDelete,
                'roleName' => $this->role->name,
                'displayName' => $this->role->display_name,
            ]);
            
            $this->closeDialog();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('角色刪除失敗', [
                'role_id' => $this->roleToDelete,
                'role_name' => $this->role->name ?? 'unknown',
                'role_display_name' => $this->role->display_name ?? 'unknown',
                'users_count' => $this->role->users_count ?? 0,
                'permissions_count' => $this->role->permissions_count ?? 0,
                'force_delete' => $this->forceDelete,
                'error' => $e->getMessage(),
                'admin_user' => auth()->id(),
                'admin_username' => auth()->user()->username,
                'admin_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now()->toISOString(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // 根據錯誤類型顯示不同的錯誤訊息
            if (str_contains($e->getMessage(), 'cannot_delete_super_admin') || 
                str_contains($e->getMessage(), 'cannot_delete_system_role')) {
                $this->showError($e->getMessage());
            } else {
                $this->showError(__('admin.roles.delete_failed') . ': ' . $e->getMessage());
            }
        } finally {
            $this->isProcessing = false;
        }
    }

    /**
     * 執行角色刪除
     */
    private function performRoleDelete()
    {
        $roleName = $this->role->name;
        $displayName = $this->role->display_name;
        $roleId = $this->role->id;
        $usersCount = $this->role->users()->count();
        $permissionsCount = $this->role->permissions()->count();
        
        // 額外的安全檢查
        if ($roleName === 'super_admin') {
            throw new \Exception(__('admin.roles.cannot_delete_super_admin'));
        }
        
        $systemRoles = ['admin', 'user'];
        if (in_array($roleName, $systemRoles) && !auth()->user()->isSuperAdmin()) {
            throw new \Exception(__('admin.roles.cannot_delete_system_role'));
        }
        
        // 記錄刪除操作
        Log::info('角色刪除', [
            'deleted_role_id' => $roleId,
            'deleted_role_name' => $roleName,
            'deleted_role_display_name' => $displayName,
            'deleted_role_description' => $this->role->description,
            'users_count' => $usersCount,
            'permissions_count' => $permissionsCount,
            'force_delete' => $this->forceDelete,
            'admin_user' => auth()->id(),
            'admin_username' => auth()->user()->username,
            'admin_ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ]);

        // 如果有使用者使用此角色，先移除使用者角色關聯
        if ($usersCount > 0) {
            $affectedUsers = $this->role->users()->pluck('username')->toArray();
            Log::info('移除使用者角色關聯', [
                'role_name' => $roleName,
                'affected_users' => $affectedUsers,
                'users_count' => $usersCount,
            ]);
            
            $this->role->users()->detach();
        }

        // 移除角色權限關聯
        if ($permissionsCount > 0) {
            $this->role->permissions()->detach();
        }
        
        // 刪除角色
        $this->role->delete();

        $message = __('admin.roles.role_deleted_successfully', [
            'name' => $displayName,
            'users_affected' => $usersCount
        ]);
        
        $this->showSuccess($message);
    }

    /**
     * 取得刪除警告訊息
     * 
     * @return string
     */
    public function getDeleteWarningProperty(): string
    {
        if (!$this->role) {
            return '';
        }

        $usersCount = $this->role->users_count ?? 0;
        $permissionsCount = $this->role->permissions_count ?? 0;

        $warnings = [];

        if ($usersCount > 0) {
            $warnings[] = __('admin.roles.delete_warning_users', ['count' => $usersCount]);
        }

        if ($permissionsCount > 0) {
            $warnings[] = __('admin.roles.delete_warning_permissions', ['count' => $permissionsCount]);
        }

        $warnings[] = __('admin.roles.delete_warning_irreversible');

        return implode(' ', $warnings);
    }

    /**
     * 取得確認按鈕文字
     * 
     * @return string
     */
    public function getConfirmButtonTextProperty(): string
    {
        if ($this->isProcessing) {
            return __('admin.roles.processing');
        }

        return __('admin.roles.confirm_delete');
    }

    /**
     * 取得確認按鈕樣式類別
     * 
     * @return string
     */
    public function getConfirmButtonClassProperty(): string
    {
        $baseClass = 'inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200 ';
        
        if ($this->isProcessing || !$this->canConfirm) {
            return $baseClass . 'bg-gray-400 text-white cursor-not-allowed';
        }

        return $baseClass . 'bg-red-600 hover:bg-red-700 text-white';
    }

    /**
     * 檢查是否可以確認操作
     * 
     * @return bool
     */
    public function getCanConfirmProperty(): bool
    {
        if ($this->isProcessing) {
            return false;
        }

        if (empty($this->confirmRoleName) || $this->confirmRoleName !== $this->role?->name) {
            return false;
        }

        // 如果角色有使用者，必須勾選強制刪除
        $usersCount = $this->role?->users_count ?? 0;
        if ($usersCount > 0 && !$this->forceDelete) {
            return false;
        }

        return true;
    }

    /**
     * 檢查角色是否有使用者
     * 
     * @return bool
     */
    public function getRoleHasUsersProperty(): bool
    {
        return ($this->role?->users_count ?? 0) > 0;
    }

    /**
     * 取得使用者數量
     * 
     * @return int
     */
    public function getUsersCountProperty(): int
    {
        return $this->role?->users_count ?? 0;
    }

    /**
     * 取得權限數量
     * 
     * @return int
     */
    public function getPermissionsCountProperty(): int
    {
        return $this->role?->permissions_count ?? 0;
    }

    /**
     * 渲染元件
     * 
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.admin.roles.role-delete', [
            'deleteWarning' => $this->deleteWarning,
            'confirmButtonText' => $this->confirmButtonText,
            'confirmButtonClass' => $this->confirmButtonClass,
            'canConfirm' => $this->canConfirm,
            'roleHasUsers' => $this->roleHasUsers,
            'usersCount' => $this->usersCount,
            'permissionsCount' => $this->permissionsCount,
        ]);
    }
}