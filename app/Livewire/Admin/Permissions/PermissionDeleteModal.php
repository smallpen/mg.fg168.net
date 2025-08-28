<?php

namespace App\Livewire\Admin\Permissions;

use App\Models\Permission;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use App\Services\AuditLogService;
use App\Services\InputValidationService;
use App\Traits\HandlesLivewireErrors;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

/**
 * 權限刪除確認對話框元件
 * 
 * 提供權限刪除的確認介面，包含刪除前檢查、依賴關係檢查和審計日誌記錄
 */
class PermissionDeleteModal extends Component
{
    use HandlesLivewireErrors;

    // 對話框狀態
    public bool $showModal = false;
    
    // 權限資料
    public ?Permission $permission = null;
    public int $permissionId = 0;
    
    // 刪除檢查結果
    public array $deleteChecks = [];
    public bool $canDelete = false;
    public bool $hasBlockingIssues = false;
    
    // 確認輸入
    public string $confirmationText = '';
    
    // 處理狀態
    public bool $processing = false;

    protected PermissionRepositoryInterface $permissionRepository;
    protected InputValidationService $validationService;
    protected AuditLogService $auditService;

    /**
     * 驗證規則
     */
    protected function rules(): array
    {
        return [
            'confirmationText' => [
                'required',
                function ($attribute, $value, $fail) {
                    if ($this->permission && $value !== $this->permission->name) {
                        $fail(__('admin.permissions.confirmation_mismatch'));
                    }
                },
            ],
        ];
    }

    /**
     * 自訂驗證訊息
     */
    protected function messages(): array
    {
        return [
            'confirmationText.required' => '請輸入權限名稱以確認刪除',
        ];
    }

    /**
     * 元件初始化
     */
    public function boot(
        PermissionRepositoryInterface $permissionRepository,
        InputValidationService $validationService,
        AuditLogService $auditService
    ): void {
        $this->permissionRepository = $permissionRepository;
        $this->validationService = $validationService;
        $this->auditService = $auditService;
    }

    /**
     * 監聽確認刪除事件
     */
    #[On('confirm-permission-delete')]
    public function confirmDelete(int $permissionId): void
    {
        try {
            // 驗證權限 ID
            $this->permissionId = $this->validationService->validateId($permissionId);
            $this->permission = Permission::find($this->permissionId);
            
            if (!$this->permission) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => __('admin.permissions.permission_not_found')
                ]);
                return;
            }

            // 檢查權限
            if (!auth()->user()->hasPermission('permissions.delete')) {
                $this->auditService->logSecurityEvent('permission_delete_denied', 'high', [
                    'permission_id' => $this->permissionId,
                    'permission_name' => $this->permission->name,
                ]);
                
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => __('admin.permissions.no_permission_delete')
                ]);
                return;
            }

            // 執行刪除前檢查
            $this->performDeleteChecks();

            // 記錄刪除確認對話框開啟
            $this->auditService->logDataAccess('permissions', 'delete_modal_opened', [
                'permission_id' => $this->permissionId,
                'permission_name' => $this->permission->name,
            ]);

            $this->resetForm();
            $this->showModal = true;
        } catch (ValidationException $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '無效的權限 ID'
            ]);
        }
    }

    /**
     * 執行刪除前檢查
     */
    private function performDeleteChecks(): void
    {
        if (!$this->permission) {
            return;
        }

        $this->deleteChecks = [];
        $this->hasBlockingIssues = false;
        $this->canDelete = true;

        // 檢查是否為系統權限
        $this->checkSystemPermission();

        // 檢查角色使用情況
        $this->checkRoleUsage();

        // 檢查依賴關係
        $this->checkDependencies();

        // 檢查被依賴情況
        $this->checkDependents();

        // 最終判斷是否可以刪除
        $this->canDelete = !$this->hasBlockingIssues;
    }

    /**
     * 檢查系統權限
     */
    private function checkSystemPermission(): void
    {
        if ($this->permission->is_system_permission) {
            $this->deleteChecks['system_permission'] = [
                'status' => 'error',
                'message' => '此權限為系統核心權限，受到保護無法刪除',
                'details' => [],
                'blocking' => true,
                'count' => 0
            ];
            $this->hasBlockingIssues = true;
        } else {
            $this->deleteChecks['system_permission'] = [
                'status' => 'success',
                'message' => '非系統權限，可以安全刪除',
                'details' => [],
                'blocking' => false,
                'count' => 0
            ];
        }
    }

    /**
     * 檢查角色使用情況
     */
    private function checkRoleUsage(): void
    {
        $roles = $this->permission->roles()->get();
        $roleCount = $roles->count();

        if ($roleCount > 0) {
            $roleNames = $roles->pluck('display_name')->toArray();
            
            $this->deleteChecks['roles'] = [
                'status' => 'error',
                'message' => "此權限正被 {$roleCount} 個角色使用，無法刪除",
                'details' => $roleNames,
                'blocking' => true,
                'count' => $roleCount
            ];
            
            $this->hasBlockingIssues = true;
        } else {
            $this->deleteChecks['roles'] = [
                'status' => 'success',
                'message' => '沒有角色使用此權限',
                'details' => [],
                'blocking' => false,
                'count' => 0
            ];
        }
    }

    /**
     * 檢查依賴關係
     */
    private function checkDependencies(): void
    {
        $dependencies = $this->permission->dependencies()->get();
        $dependencyCount = $dependencies->count();

        if ($dependencyCount > 0) {
            $dependencyNames = $dependencies->pluck('display_name')->toArray();
            
            $this->deleteChecks['dependencies'] = [
                'status' => 'info',
                'message' => "此權限依賴 {$dependencyCount} 個其他權限，刪除後將移除這些依賴關係",
                'details' => $dependencyNames,
                'blocking' => false,
                'count' => $dependencyCount
            ];
        } else {
            $this->deleteChecks['dependencies'] = [
                'status' => 'success',
                'message' => '此權限沒有依賴其他權限',
                'details' => [],
                'blocking' => false,
                'count' => 0
            ];
        }
    }

    /**
     * 檢查被依賴情況
     */
    private function checkDependents(): void
    {
        $dependents = $this->permission->dependents()->get();
        $dependentCount = $dependents->count();

        if ($dependentCount > 0) {
            $dependentNames = $dependents->pluck('display_name')->toArray();
            
            $this->deleteChecks['dependents'] = [
                'status' => 'error',
                'message' => "有 {$dependentCount} 個權限依賴此權限，無法刪除",
                'details' => $dependentNames,
                'blocking' => true,
                'count' => $dependentCount
            ];
            
            $this->hasBlockingIssues = true;
        } else {
            $this->deleteChecks['dependents'] = [
                'status' => 'success',
                'message' => '沒有其他權限依賴此權限',
                'details' => [],
                'blocking' => false,
                'count' => 0
            ];
        }
    }

    /**
     * 確認刪除權限
     */
    public function executeDelete(): void
    {
        try {
            $this->validate();

            // 再次檢查權限（防止 CSRF 攻擊）
            if (!auth()->user()->hasPermission('permissions.delete')) {
                $this->auditService->logSecurityEvent('permission_bypass_attempt', 'high', [
                    'permission_id' => $this->permissionId,
                    'permission_name' => $this->permission->name,
                ]);
                
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => '權限驗證失敗'
                ]);
                return;
            }

            // 再次檢查是否可以刪除
            if (!$this->permission->can_be_deleted) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => '此權限無法刪除'
                ]);
                return;
            }

            $this->processing = true;

            // 執行刪除操作
            $success = $this->permissionRepository->deletePermission($this->permission);
            
            if ($success) {
                // 記錄審計日誌
                $this->auditService->logDataAccess('permissions', 'permission_deleted', [
                    'permission_id' => $this->permissionId,
                    'permission_name' => $this->permission->name,
                    'permission_display_name' => $this->permission->display_name,
                    'permission_module' => $this->permission->module,
                    'permission_type' => $this->permission->type,
                    'delete_checks' => $this->deleteChecks,
                ]);

                $this->dispatch('show-toast', [
                    'type' => 'success',
                    'message' => __('admin.permissions.permission_deleted', ['name' => $this->permission->display_name])
                ]);

                $this->dispatch('permission-delete-confirmed', permissionId: $this->permissionId);
                $this->closeModal();
                
                // 清除快取
                $this->clearPermissionCache();
            } else {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => __('admin.permissions.delete_failed')
                ]);
            }
        } catch (ValidationException $e) {
            $errors = $e->errors();
            if (isset($errors['confirmationText'])) {
                $this->addError('confirmationText', $errors['confirmationText'][0]);
            } else {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => '輸入驗證失敗'
                ]);
            }
        } catch (\Exception $e) {
            $this->auditService->logSecurityEvent('permission_delete_failed', 'medium', [
                'permission_id' => $this->permissionId,
                'permission_name' => $this->permission->name,
                'error' => $e->getMessage(),
            ]);

            Log::error('權限刪除失敗', [
                'permission_id' => $this->permissionId,
                'permission_name' => $this->permission->name,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => __('admin.permissions.delete_failed')
            ]);
        } finally {
            $this->processing = false;
        }
    }

    /**
     * 關閉對話框
     */
    public function closeModal(): void
    {
        // 關閉模態
        $this->showModal = false;
        
        // 重置表單和驗證
        $this->resetForm();
        $this->resetValidation();
        
        // 強制重新渲染
        $this->dispatch('$refresh');
        
        // 發送模態關閉事件
        $this->dispatch('modal-closed');
}

    /**
     * 重置表單
     */
    private function resetForm(): void
    {
        $this->confirmationText = '';
        $this->processing = false;
        $this->resetErrorBag();
        
        // 強制重新渲染元件以確保前端同步
        $this->dispatch('$refresh');
        
        // 發送前端刷新事件
        $this->dispatch('permission-delete-modal-reset');
    }

    /**
     * 重置模態狀態
     */
    private function resetModalState(): void
    {
        $this->permission = null;
        $this->permissionId = 0;
        $this->deleteChecks = [];
        $this->canDelete = false;
        $this->hasBlockingIssues = false;
    }

    /**
     * 清除權限相關快取
     */
    private function clearPermissionCache(): void
    {
        Cache::forget('permission_stats');
        Cache::forget('permission_modules_list');
        Cache::forget('permission_types_list');
        
        // 清除分組權限快取
        $cacheKeys = Cache::get('permission_cache_keys', []);
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * 取得檢查圖示
     */
    public function getCheckIcon(string $status): string
    {
        return match ($status) {
            'success' => 'heroicon-o-check-circle',
            'warning' => 'heroicon-o-exclamation-triangle',
            'error' => 'heroicon-o-x-circle',
            default => 'heroicon-o-information-circle',
        };
    }

    /**
     * 取得檢查顏色
     */
    public function getCheckColor(string $status): string
    {
        return match ($status) {
            'success' => 'text-green-500',
            'warning' => 'text-yellow-500',
            'error' => 'text-red-500',
            default => 'text-blue-500',
        };
    }

    /**
     * 取得確認標籤
     */
    public function getConfirmLabelProperty(): string
    {
        if (!$this->permission) {
            return '';
        }

        return "請輸入權限名稱「{$this->permission->name}」以確認刪除";
    }

    /**
     * 檢查確認按鈕是否可用
     */
    public function getCanConfirmProperty(): bool
    {
        if ($this->processing) {
            return false;
        }

        if (!$this->canDelete) {
            return false;
        }

        if ($this->confirmationText !== $this->permission?->name) {
            return false;
        }

        return true;
    }

    /**
     * 取得確認按鈕文字
     */
    public function getConfirmButtonTextProperty(): string
    {
        if ($this->processing) {
            return '刪除中...';
        }

        return '確認刪除';
    }

    /**
     * 取得確認按鈕樣式
     */
    public function getConfirmButtonClassProperty(): string
    {
        $baseClass = 'px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';
        
        if (!$this->canConfirm || $this->processing) {
            return $baseClass . ' bg-gray-400 cursor-not-allowed';
        }

        return $baseClass . ' bg-red-600 hover:bg-red-700 focus:ring-red-500';
    }

    /**
     * 取得角色列表（計算屬性）
     */
    public function getRoleListProperty(): array
    {
        if (!$this->permission) {
            return [];
        }

        return $this->permission->roles()
            ->select('roles.id', 'roles.name', 'roles.display_name')
            ->get()
            ->toArray();
    }

    /**
     * 取得依賴權限列表（計算屬性）
     */
    public function getDependencyListProperty(): array
    {
        if (!$this->permission) {
            return [];
        }

        return $this->permission->dependencies()
            ->select('permissions.id', 'permissions.name', 'permissions.display_name')
            ->get()
            ->toArray();
    }

    /**
     * 取得被依賴權限列表（計算屬性）
     */
    public function getDependentListProperty(): array
    {
        if (!$this->permission) {
            return [];
        }

        return $this->permission->dependents()
            ->select('permissions.id', 'permissions.name', 'permissions.display_name')
            ->get()
            ->toArray();
    }

    /**
     * 渲染元件
     */
    
    /**
     * 開啟模態並初始化狀態
     */
    public function openModal(): void
    {
        // 先重置表單確保乾淨狀態
        $this->resetForm();
        
        // 開啟模態
        $this->showModal = true;
        
        // 發送模態開啟事件
        $this->dispatch('modal-opened');
    }


    public function render()
    {
        return view('livewire.admin.permissions.permission-delete-modal');
    }
}