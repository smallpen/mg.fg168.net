<?php

namespace App\Livewire\Admin\Roles;

use App\Livewire\Admin\AdminComponent;
use App\Models\Role;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Services\RoleSecurityService;
use App\Services\RoleDataValidationService;
use App\Services\AuditLogService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;

/**
 * 角色表單元件
 * 
 * 提供角色建立和編輯功能，包含資料驗證和系統角色保護機制
 */
class RoleForm extends AdminComponent
{
    // 角色資料屬性
    public ?Role $role = null;
    public string $name = '';
    public string $display_name = '';
    public string $description = '';
    public ?int $parent_id = null;
    public bool $is_active = true;
    
    // 表單狀態屬性
    public bool $isEditing = false;
    public bool $showParentSelector = false;
    public bool $isSystemRole = false;
    public bool $autoSaveEnabled = true;
    public bool $hasUnsavedChanges = false;
    public ?string $lastAutoSaveTime = null;
    
    // 可選父角色列表
    public array $availableParents = [];

    protected RoleRepositoryInterface $roleRepository;
    protected RoleSecurityService $securityService;
    protected RoleDataValidationService $validationService;
    protected AuditLogService $auditLogService;

    /**
     * 元件初始化
     */
    public function boot(
        RoleRepositoryInterface $roleRepository,
        RoleSecurityService $securityService,
        RoleDataValidationService $validationService,
        AuditLogService $auditLogService
    ): void {
        $this->roleRepository = $roleRepository;
        $this->securityService = $securityService;
        $this->validationService = $validationService;
        $this->auditLogService = $auditLogService;
    }

    /**
     * 元件掛載
     */
    public function mount($role = null): void
    {
        // 先調用父類的 mount 方法
        parent::mount();
        
        if ($role && $role instanceof Role) {
            $this->checkPermission('roles.edit');
            $this->isEditing = true;
            $this->role = $role;
            $this->loadRoleData();
        } else {
            $this->checkPermission('roles.create');
            $this->isEditing = false;
            $this->role = null;
        }
        
        $this->loadAvailableParents();
    }

    /**
     * 載入角色資料
     */
    private function loadRoleData(): void
    {
        $this->name = $this->role->name ?? '';
        $this->display_name = $this->role->display_name ?? '';
        $this->description = $this->role->description ?? '';
        $this->parent_id = $this->role->parent_id;
        $this->is_active = $this->role->is_active ?? true;
        $this->isSystemRole = $this->role->is_system_role ?? false;
    }

    /**
     * 載入可選的父角色列表
     */
    private function loadAvailableParents(): void
    {
        $query = Role::where('is_active', true);
        
        // 編輯模式時排除自己和自己的後代
        if ($this->isEditing && $this->role) {
            $excludeIds = [$this->role->id];
            $descendants = $this->role->getDescendants();
            $excludeIds = array_merge($excludeIds, $descendants->pluck('id')->toArray());
            $query->whereNotIn('id', $excludeIds);
        }
        
        $this->availableParents = $query->orderBy('display_name')
                                       ->get(['id', 'display_name', 'name'])
                                       ->toArray();
    }

    /**
     * 驗證規則
     */
    protected function rules(): array
    {
        $rules = [
            'display_name' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:roles,id'],
            'is_active' => ['boolean'],
        ];

        // 系統角色不能修改名稱
        if (!$this->isSystemRole) {
            $rules['name'] = [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z_]+$/',
                Rule::unique('roles')->ignore($this->role?->id)
            ];
        }

        return $rules;
    }

    /**
     * 驗證訊息
     */
    protected function messages(): array
    {
        return [
            'name.required' => '角色名稱為必填項目',
            'name.unique' => '角色名稱已存在',
            'name.regex' => '角色名稱只能包含小寫英文字母和底線',
            'name.max' => '角色名稱不能超過 50 個字元',
            'display_name.required' => '顯示名稱為必填項目',
            'display_name.max' => '顯示名稱不能超過 50 個字元',
            'description.max' => '角色描述不能超過 255 個字元',
            'parent_id.exists' => '選擇的父角色不存在',
        ];
    }

    /**
     * 即時驗證屬性
     */
    protected function validationAttributes(): array
    {
        return [
            'name' => '角色名稱',
            'display_name' => '顯示名稱',
            'description' => '角色描述',
            'parent_id' => '父角色',
            'is_active' => '啟用狀態',
        ];
    }

    /**
     * 名稱欄位更新時的處理
     */
    public function updatedName(): void
    {
        if (!$this->isSystemRole) {
            $this->validateOnly('name');
        }
        $this->markAsChanged();
    }

    /**
     * 顯示名稱欄位更新時的處理
     */
    public function updatedDisplayName(): void
    {
        $this->validateOnly('display_name');
        
        // 如果是新建角色且名稱為空，自動生成名稱
        if (!$this->isEditing && empty($this->name) && !$this->isSystemRole) {
            $this->name = $this->generateNameFromDisplayName($this->display_name);
        }
        
        $this->markAsChanged();
    }

    /**
     * 父角色選擇更新時的處理
     */
    public function updatedParentId(): void
    {
        if ($this->parent_id) {
            $this->validateOnly('parent_id');
            
            // 檢查循環依賴
            if ($this->isEditing && $this->role && $this->role->hasCircularDependency($this->parent_id)) {
                $this->addError('parent_id', '不能選擇會造成循環依賴的父角色');
                $this->parent_id = null;
                return;
            }
            
            // 預覽權限繼承變更
            $this->previewPermissionInheritance();
        }
        
        $this->markAsChanged();
    }

    /**
     * 預覽權限繼承變更
     */
    private function previewPermissionInheritance(): void
    {
        if (!$this->parent_id) {
            return;
        }
        
        try {
            $parentRole = Role::find($this->parent_id);
            if ($parentRole) {
                $inheritedPermissions = $parentRole->getAllPermissions();
                $this->dispatch('permission-inheritance-preview', [
                    'parentName' => $parentRole->display_name,
                    'inheritedCount' => $inheritedPermissions->count(),
                    'inheritedPermissions' => $inheritedPermissions->pluck('name')->toArray()
                ]);
            }
        } catch (\Exception $e) {
            // 靜默處理預覽錯誤
        }
    }

    /**
     * 從顯示名稱生成角色名稱
     */
    private function generateNameFromDisplayName(string $displayName): string
    {
        // 移除特殊字元，轉換為小寫並用底線連接
        $name = strtolower(trim($displayName));
        // 移除非英文字母、數字和空格的字元
        $name = preg_replace('/[^\p{L}\p{N}\s]/u', '', $name);
        // 將中文等非ASCII字元轉換為拼音或移除
        $name = preg_replace('/[^\x00-\x7F]/', '', $name);
        // 將多個空格替換為單個底線
        $name = preg_replace('/\s+/', '_', $name);
        // 移除開頭和結尾的底線
        $name = trim($name, '_');
        
        return $name ?: 'role_' . time();
    }

    /**
     * 切換父角色選擇器顯示
     */
    public function toggleParentSelector(): void
    {
        $this->showParentSelector = !$this->showParentSelector;
        
        if (!$this->showParentSelector) {
            $this->parent_id = null;
        }
    }

    /**
     * 儲存角色
     */
    public function save(): void
    {
        try {
            // 準備資料
            $data = [
                'display_name' => $this->display_name,
                'description' => $this->description,
                'parent_id' => $this->parent_id,
                'is_active' => $this->is_active,
            ];

            // 只有非系統角色才能修改名稱
            if (!$this->isSystemRole) {
                $data['name'] = $this->name;
            }

            // 執行多層級安全檢查
            $action = $this->isEditing ? 'edit' : 'create';
            $securityCheck = $this->securityService->checkMultiLevelPermissions($action, $this->role);
            
            if (!$securityCheck['allowed']) {
                $this->addError('save', $securityCheck['message']);
                return;
            }

            // 驗證和清理資料
            if ($this->isEditing) {
                $validatedData = $this->validationService->validateRoleUpdateData($data, $this->role);
            } else {
                $validatedData = $this->validationService->validateRoleCreationData($data);
            }

            if ($this->isEditing) {
                $oldData = $this->role->toArray();
                $oldParentId = $this->role->parent_id;
                
                $this->roleRepository->update($this->role, $validatedData);
                
                // 記錄更新操作
                $this->securityService->logRoleOperation('update', $this->role, [
                    'old_data' => $oldData,
                    'new_data' => $validatedData,
                    'parent_changed' => $oldParentId !== $this->parent_id
                ]);
                
                // 如果父角色有變更，更新權限繼承
                if ($oldParentId !== $this->parent_id) {
                    $this->updatePermissionInheritance();
                }
                
                // 重置未儲存變更標記
                $this->hasUnsavedChanges = false;
                $this->lastAutoSaveTime = null;
                
                $message = "角色「{$this->display_name}」已成功更新";
                
                $this->dispatch('role-updated', [
                    'roleId' => $this->role->id,
                    'message' => $message,
                    'parentChanged' => $oldParentId !== $this->parent_id
                ]);
            } else {
                $validatedData['is_system_role'] = false; // 新建角色預設不是系統角色
                $newRole = $this->roleRepository->create($validatedData);
                
                // 記錄建立操作
                $this->securityService->logRoleOperation('create', $newRole, $validatedData);
                
                $message = "角色「{$this->display_name}」已成功建立";
                
                $this->dispatch('role-created', [
                    'roleId' => $newRole->id,
                    'message' => $message
                ]);
                
                // 建立成功後跳轉到權限設定頁面
                $this->redirectToPermissionMatrix($newRole->id);
            }

        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                }
            }
        } catch (\Exception $e) {
            $this->addError('save', '儲存失敗：' . $e->getMessage());
            
            // 記錄錯誤
            $this->auditLogService->logSecurityEvent('role_save_failed', 'medium', [
                'action' => $this->isEditing ? 'update' : 'create',
                'role_id' => $this->role?->id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 系統角色保護檢查
     */
    private function checkSystemRoleProtection(): void
    {
        $originalRole = $this->roleRepository->findOrFail($this->role->id);
        
        // 檢查是否嘗試修改受保護的屬性
        if ($this->name !== $originalRole->name) {
            throw new \Exception('系統角色的名稱不能修改');
        }
        
        // 某些核心系統角色不能停用
        $protectedRoles = ['super_admin', 'admin'];
        if (in_array($originalRole->name, $protectedRoles) && !$this->is_active) {
            throw new \Exception('核心系統角色不能停用');
        }
    }

    /**
     * 跳轉到權限矩陣頁面
     */
    private function redirectToPermissionMatrix(int $roleId): void
    {
        $this->dispatch('redirect-to-permissions', roleId: $roleId);
    }

    /**
     * 取消操作
     */
    public function cancel()
    {
        $this->dispatch('role-form-cancelled');
        return $this->redirect(route('admin.roles.index'));
    }

    /**
     * 重置表單
     */
    public function resetForm(): void
    {
        $this->reset([
            'name', 'display_name', 'description', 
            'parent_id', 'is_active', 'showParentSelector',
            'hasUnsavedChanges', 'lastAutoSaveTime'
        ]);
        
        $this->resetErrorBag();
        $this->resetValidation();
        
        $this->dispatch('form-reset');
    }

    /**
     * 確認重置表單
     */
    public function confirmResetForm(): void
    {
        if ($this->hasUnsavedChanges) {
            $this->dispatch('confirm-form-reset');
        } else {
            $this->resetForm();
        }
    }

    /**
     * 複製角色功能
     */
    public function duplicateRole(): void
    {
        if (!$this->isEditing) {
            return;
        }

        $this->checkPermission('roles.create');

        try {
            $newName = $this->role->name . '_copy_' . time();
            $duplicatedRole = $this->roleRepository->duplicateRole($this->role, $newName);
            
            $this->dispatch('role-duplicated', [
                'roleId' => $duplicatedRole->id,
                'message' => "角色「{$this->role->display_name}」已成功複製為「{$duplicatedRole->display_name}」"
            ]);
            
            // 跳轉到新角色的編輯頁面
            $this->dispatch('redirect-to-edit', roleId: $duplicatedRole->id);
            
        } catch (\Exception $e) {
            $this->addError('duplicate', '複製失敗：' . $e->getMessage());
        }
    }

    /**
     * 檢查名稱是否可用
     */
    public function checkNameAvailability(): void
    {
        if (empty($this->name) || $this->isSystemRole) {
            return;
        }

        $exists = $this->roleRepository->nameExists($this->name, $this->role?->id);
        
        if ($exists) {
            $this->addError('name', '角色名稱已存在');
        } else {
            $this->resetErrorBag('name');
        }
    }

    /**
     * 取得表單標題
     */
    public function getFormTitleProperty(): string
    {
        return $this->isEditing ? "編輯角色：{$this->role->display_name}" : '建立新角色';
    }

    /**
     * 取得儲存按鈕文字
     */
    public function getSaveButtonTextProperty(): string
    {
        return $this->isEditing ? '更新角色' : '建立角色';
    }

    /**
     * 檢查是否可以修改父角色
     */
    public function getCanModifyParentProperty(): bool
    {
        // 系統角色不能修改層級關係
        if ($this->isSystemRole) {
            return false;
        }
        
        // 如果角色有子角色，修改父角色需要特別小心
        if ($this->isEditing && $this->role && $this->role->hasChildren()) {
            return false;
        }
        
        return true;
    }

    /**
     * 檢查是否可以停用角色
     */
    public function getCanDeactivateProperty(): bool
    {
        if (!$this->isEditing) {
            return true;
        }
        
        // 系統角色中的核心角色不能停用
        $protectedRoles = ['super_admin', 'admin'];
        if ($this->isSystemRole && in_array($this->role->name, $protectedRoles)) {
            return false;
        }
        
        return true;
    }

    /**
     * 取得父角色資訊
     */
    public function getParentRoleInfoProperty(): ?array
    {
        if (!$this->parent_id) {
            return null;
        }
        
        $parent = collect($this->availableParents)->firstWhere('id', $this->parent_id);
        return $parent;
    }

    /**
     * 更新權限繼承
     */
    private function updatePermissionInheritance(): void
    {
        if (!$this->role) {
            return;
        }
        
        // 清除角色的權限快取
        cache()->forget("role_all_permissions_{$this->role->id}");
        
        // 遞迴清除所有子角色的權限快取
        $descendants = $this->role->getDescendants();
        foreach ($descendants as $descendant) {
            cache()->forget("role_all_permissions_{$descendant->id}");
        }
        
        $this->dispatch('permission-inheritance-updated', [
            'roleId' => $this->role->id,
            'affectedRoles' => $descendants->pluck('id')->toArray()
        ]);
    }

    /**
     * 取得權限繼承預覽資訊
     */
    public function getPermissionInheritancePreviewProperty(): ?array
    {
        if (!$this->parent_id) {
            return null;
        }
        
        try {
            $parentRole = Role::find($this->parent_id);
            if (!$parentRole) {
                return null;
            }
            
            $inheritedPermissions = $parentRole->getAllPermissions();
            $currentPermissions = $this->isEditing && $this->role ? 
                $this->role->getDirectPermissions() : collect();
            
            return [
                'parent_name' => $parentRole->display_name,
                'inherited_count' => $inheritedPermissions->count(),
                'current_count' => $currentPermissions->count(),
                'total_count' => $inheritedPermissions->merge($currentPermissions)->unique('id')->count(),
                'inherited_permissions' => $inheritedPermissions->groupBy('module')->map(function ($permissions) {
                    return $permissions->pluck('display_name')->toArray();
                })->toArray()
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 標記表單有變更
     */
    private function markAsChanged(): void
    {
        $this->hasUnsavedChanges = true;
        
        if ($this->autoSaveEnabled && $this->isEditing) {
            $this->scheduleAutoSave();
        }
    }

    /**
     * 排程自動儲存
     */
    private function scheduleAutoSave(): void
    {
        // 使用 JavaScript 設定延遲自動儲存
        $this->dispatch('schedule-auto-save');
    }

    /**
     * 執行自動儲存
     */
    public function autoSave(): void
    {
        if (!$this->autoSaveEnabled || !$this->hasUnsavedChanges || !$this->isEditing) {
            return;
        }

        try {
            // 只驗證必要欄位進行自動儲存
            $this->validateOnly(['display_name']);
            
            if ($this->errors->isEmpty()) {
                $data = [
                    'display_name' => $this->display_name,
                    'description' => $this->description,
                    'parent_id' => $this->parent_id,
                    'is_active' => $this->is_active,
                ];

                // 只有非系統角色才能修改名稱
                if (!$this->isSystemRole && !empty($this->name)) {
                    $data['name'] = $this->name;
                }

                $this->roleRepository->update($this->role, $data);
                
                $this->hasUnsavedChanges = false;
                $this->lastAutoSaveTime = now()->format('H:i:s');
                
                $this->dispatch('auto-save-completed', [
                    'message' => '已自動儲存',
                    'time' => $this->lastAutoSaveTime
                ]);
            }
        } catch (\Exception $e) {
            // 自動儲存失敗時不顯示錯誤，避免干擾使用者
            logger()->warning('Auto-save failed for role form', [
                'role_id' => $this->role?->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 切換自動儲存功能
     */
    public function toggleAutoSave(): void
    {
        $this->autoSaveEnabled = !$this->autoSaveEnabled;
        
        $message = $this->autoSaveEnabled ? '已啟用自動儲存' : '已停用自動儲存';
        $this->dispatch('auto-save-toggled', ['message' => $message]);
    }

    /**
     * 檢查是否有未儲存的變更
     */
    public function getHasUnsavedChangesProperty(): bool
    {
        return $this->hasUnsavedChanges;
    }

    /**
     * 取得自動儲存狀態文字
     */
    public function getAutoSaveStatusProperty(): string
    {
        if (!$this->autoSaveEnabled) {
            return '自動儲存已停用';
        }
        
        if ($this->hasUnsavedChanges) {
            return '有未儲存的變更';
        }
        
        if ($this->lastAutoSaveTime) {
            return "最後自動儲存：{$this->lastAutoSaveTime}";
        }
        
        return '自動儲存已啟用';
    }

    /**
     * 監聽描述欄位變更
     */
    public function updatedDescription(): void
    {
        $this->markAsChanged();
    }

    /**
     * 監聽啟用狀態變更
     */
    public function updatedIsActive(): void
    {
        $this->markAsChanged();
    }

    /**
     * 監聽角色列表更新事件
     */
    #[On('role-list-updated')]
    public function handleRoleListUpdated(): void
    {
        $this->loadAvailableParents();
    }

    /**
     * 處理驗證失敗
     */
    public function getErrorBag()
    {
        return parent::getErrorBag();
    }

    /**
     * 取得表單驗證摘要
     */
    public function getValidationSummaryProperty(): array
    {
        $errors = $this->getErrorBag();
        if ($errors->isEmpty()) {
            return [];
        }

        return [
            'hasErrors' => true,
            'errorCount' => $errors->count(),
            'firstError' => $errors->first(),
            'errorFields' => $errors->keys()
        ];
    }

    /**
     * 檢查特定欄位是否有錯誤
     */
    public function hasFieldError(string $field): bool
    {
        return $this->getErrorBag()->has($field);
    }

    /**
     * 取得欄位的第一個錯誤訊息
     */
    public function getFieldError(string $field): ?string
    {
        return $this->getErrorBag()->first($field);
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.roles.role-form');
    }
}