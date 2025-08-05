<?php

namespace App\Http\Livewire\Admin\Users;

use App\Http\Livewire\Admin\AdminComponent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;

/**
 * 使用者刪除確認 Livewire 元件
 * 
 * 處理使用者刪除確認對話框和安全的使用者資料移除邏輯
 */
class UserDelete extends AdminComponent
{
    /**
     * 是否顯示確認對話框
     * 
     * @var bool
     */
    public $showConfirmDialog = false;

    /**
     * 要刪除的使用者 ID
     * 
     * @var int|null
     */
    public $userToDelete = null;

    /**
     * 要刪除的使用者物件
     * 
     * @var User|null
     */
    public $user = null;

    /**
     * 刪除操作類型（delete 或 disable）
     * 
     * @var string
     */
    public $deleteAction = 'disable';

    /**
     * 確認輸入的使用者名稱
     * 
     * @var string
     */
    public $confirmUsername = '';

    /**
     * 即時驗證確認使用者名稱
     */
    public function updatedConfirmUsername()
    {
        if ($this->deleteAction === 'delete' && $this->user) {
            if ($this->confirmUsername !== $this->user->username) {
                $this->addError('confirmUsername', __('admin.users.confirm_username_mismatch'));
            } else {
                $this->resetErrorBag('confirmUsername');
            }
        }
    }

    /**
     * 是否正在處理刪除
     * 
     * @var bool
     */
    public $isProcessing = false;

    /**
     * 元件掛載
     */
    public function mount()
    {
        parent::mount();
        
        // 檢查刪除權限
        if (!$this->hasPermission('users.delete')) {
            abort(403, __('admin.users.no_permission_delete'));
        }
    }

    /**
     * 顯示刪除確認對話框
     * 
     * @param int $userId
     */
    #[On('confirmUserDelete')]
    public function showDeleteConfirmation($userId)
    {
        $this->userToDelete = $userId;
        $this->user = User::with('roles')->findOrFail($userId);
        
        // 檢查是否可以刪除此使用者
        if (!$this->canDeleteUser($this->user)) {
            return;
        }

        // 重設表單狀態
        $this->confirmUsername = '';
        $this->deleteAction = 'disable';
        $this->showConfirmDialog = true;
    }

    /**
     * 檢查是否可以刪除使用者
     * 
     * @param User $user
     * @return bool
     */
    private function canDeleteUser(User $user): bool
    {
        // 防止刪除自己的帳號
        if ($user->id === auth()->id()) {
            $this->showError(__('admin.users.cannot_delete_self'));
            return false;
        }

        // 防止刪除超級管理員（除非自己也是超級管理員）
        if ($user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            $this->showError(__('admin.users.cannot_delete_super_admin'));
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
        $this->userToDelete = null;
        $this->user = null;
        $this->confirmUsername = '';
        $this->deleteAction = 'disable';
        $this->isProcessing = false;
        $this->resetErrorBag();
    }

    /**
     * 執行使用者刪除或停用
     */
    public function confirmDelete()
    {
        // 重新檢查使用者是否存在
        if (!$this->user || !User::find($this->userToDelete)) {
            $this->showError(__('admin.users.user_not_found'));
            $this->closeDialog();
            return;
        }

        // 重新檢查權限
        if (!$this->canDeleteUser($this->user)) {
            $this->closeDialog();
            return;
        }

        // 驗證確認輸入
        if ($this->deleteAction === 'delete') {
            $this->validate([
                'confirmUsername' => 'required|string',
            ], [
                'confirmUsername.required' => __('admin.users.confirm_username_required'),
            ]);

            // 檢查輸入的使用者名稱是否正確
            if ($this->confirmUsername !== $this->user->username) {
                $this->addError('confirmUsername', __('admin.users.confirm_username_mismatch'));
                return;
            }
        }

        $this->isProcessing = true;

        try {
            DB::beginTransaction();

            if ($this->deleteAction === 'delete') {
                $this->performHardDelete();
            } else {
                $this->performSoftDisable();
            }

            DB::commit();

            // 發出事件通知父元件刷新列表
            $this->dispatch('userDeleted', [
                'userId' => $this->userToDelete,
                'action' => $this->deleteAction,
                'username' => $this->user->username,
            ]);
            
            $this->closeDialog();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('使用者刪除失敗', [
                'user_id' => $this->userToDelete,
                'username' => $this->user->username ?? 'unknown',
                'action' => $this->deleteAction,
                'error' => $e->getMessage(),
                'admin_user' => auth()->id(),
                'admin_username' => auth()->user()->username,
                'admin_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now()->toISOString(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // 根據錯誤類型顯示不同的錯誤訊息
            if (str_contains($e->getMessage(), 'cannot_delete_self') || 
                str_contains($e->getMessage(), 'cannot_disable_self')) {
                $this->showError($e->getMessage());
            } elseif (str_contains($e->getMessage(), 'cannot_delete_super_admin') || 
                      str_contains($e->getMessage(), 'cannot_modify_super_admin')) {
                $this->showError($e->getMessage());
            } else {
                $this->showError(__('admin.users.delete_failed') . ': ' . $e->getMessage());
            }
        } finally {
            $this->isProcessing = false;
        }
    }

    /**
     * 執行硬刪除（完全移除使用者資料）
     */
    private function performHardDelete()
    {
        $username = $this->user->username;
        $userId = $this->user->id;
        
        // 額外的安全檢查
        if ($userId === auth()->id()) {
            throw new \Exception(__('admin.users.cannot_delete_self'));
        }
        
        if ($this->user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            throw new \Exception(__('admin.users.cannot_delete_super_admin'));
        }
        
        // 記錄刪除操作
        Log::info('使用者硬刪除', [
            'deleted_user_id' => $userId,
            'deleted_username' => $username,
            'deleted_user_email' => $this->user->email,
            'deleted_user_roles' => $this->user->roles->pluck('name')->toArray(),
            'admin_user' => auth()->id(),
            'admin_username' => auth()->user()->username,
            'admin_ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ]);

        // 先移除角色關聯
        $this->user->roles()->detach();
        
        // 刪除使用者
        $this->user->delete();

        $this->showSuccess(__('admin.users.user_deleted_permanently', ['username' => $username]));
    }

    /**
     * 執行軟停用（停用使用者但保留資料）
     */
    private function performSoftDisable()
    {
        $username = $this->user->username;
        $userId = $this->user->id;
        
        // 額外的安全檢查
        if ($userId === auth()->id()) {
            throw new \Exception(__('admin.users.cannot_disable_self'));
        }
        
        if ($this->user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            throw new \Exception(__('admin.users.cannot_modify_super_admin'));
        }
        
        // 記錄停用操作
        Log::info('使用者停用', [
            'disabled_user_id' => $userId,
            'disabled_username' => $username,
            'disabled_user_email' => $this->user->email,
            'disabled_user_roles' => $this->user->roles->pluck('name')->toArray(),
            'admin_user' => auth()->id(),
            'admin_username' => auth()->user()->username,
            'admin_ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ]);

        // 停用使用者
        $this->user->update(['is_active' => false]);

        $this->showSuccess(__('admin.users.user_disabled', ['username' => $username]));
    }

    /**
     * 取得刪除操作的描述
     * 
     * @return string
     */
    public function getDeleteActionDescriptionProperty(): string
    {
        if ($this->deleteAction === 'delete') {
            return __('admin.users.delete_action_description');
        }
        
        return __('admin.users.disable_action_description');
    }

    /**
     * 取得確認按鈕文字
     * 
     * @return string
     */
    public function getConfirmButtonTextProperty(): string
    {
        if ($this->isProcessing) {
            return __('admin.users.processing');
        }

        if ($this->deleteAction === 'delete') {
            return __('admin.users.confirm_delete');
        }
        
        return __('admin.users.confirm_disable');
    }

    /**
     * 取得確認按鈕樣式類別
     * 
     * @return string
     */
    public function getConfirmButtonClassProperty(): string
    {
        $baseClass = 'inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200 ';
        
        if ($this->isProcessing) {
            return $baseClass . 'bg-gray-400 text-white cursor-not-allowed';
        }

        if ($this->deleteAction === 'delete') {
            return $baseClass . 'bg-red-600 hover:bg-red-700 text-white';
        }
        
        return $baseClass . 'bg-orange-600 hover:bg-orange-700 text-white';
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

        if ($this->deleteAction === 'delete') {
            return !empty($this->confirmUsername) && $this->confirmUsername === $this->user->username;
        }

        return true;
    }

    /**
     * 渲染元件
     * 
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.admin.users.user-delete', [
            'deleteActionDescription' => $this->deleteActionDescription,
            'confirmButtonText' => $this->confirmButtonText,
            'confirmButtonClass' => $this->confirmButtonClass,
            'canConfirm' => $this->canConfirm,
        ]);
    }
}