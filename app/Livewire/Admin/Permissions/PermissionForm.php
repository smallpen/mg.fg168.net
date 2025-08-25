<?php

namespace App\Livewire\Admin\Permissions;

use App\Models\Permission;
use App\Repositories\PermissionRepository;
use App\Services\AuditLogService;
use App\Services\PermissionValidationService;
use App\Traits\HandlesLivewireErrors;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Collection;

/**
 * 權限表單 Livewire 元件
 * 
 * 提供權限建立和編輯功能，包含資料驗證、依賴關係管理和系統權限保護
 */
class PermissionForm extends Component
{
    use HandlesLivewireErrors;

    // 表單模式
    public string $mode = 'create'; // create, edit
    
    // 權限實例
    public ?Permission $permission = null;
    
    // 表單欄位
    public string $name = '';
    public string $display_name = '';
    public string $description = '';
    public string $module = '';
    public string $type = '';
    public array $dependencies = [];
    
    // 選項資料
    public array $availableModules = [];
    public array $availableTypes = [];
    public Collection $availablePermissions;
    
    // UI 狀態
    public bool $showForm = false;
    public bool $isLoading = false;
    public bool $isSystemPermission = false;
    
    protected PermissionRepository $permissionRepository;
    protected PermissionValidationService $validationService;
    protected AuditLogService $auditService;

    /**
     * 元件初始化
     */
    public function boot(
        PermissionRepository $permissionRepository,
        PermissionValidationService $validationService,
        AuditLogService $auditService
    ): void {
        $this->permissionRepository = $permissionRepository;
        $this->validationService = $validationService;
        $this->auditService = $auditService;
    }

    /**
     * 元件掛載
     */
    public function mount(): void
    {
        $this->loadFormOptions();
        $this->availablePermissions = collect();
    }

    /**
     * 驗證規則
     */
    protected function rules(): array
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z_\.]+$/',
                Rule::unique('permissions')->ignore($this->permission?->id)
            ],
            'display_name' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
            'module' => ['required', 'string', 'max:50'],
            'type' => ['required', 'string', 'in:view,create,edit,delete,manage'],
            'dependencies' => ['array'],
            'dependencies.*' => ['integer', 'exists:permissions,id'],
        ];

        // 系統權限的特殊規則
        if ($this->isSystemPermission && $this->mode === 'edit') {
            // 系統權限不能修改名稱和模組
            unset($rules['name']);
            unset($rules['module']);
        }

        return $rules;
    }

    /**
     * 驗證訊息
     */
    protected function messages(): array
    {
        return [
            'name.required' => '權限名稱為必填項目',
            'name.regex' => '權限名稱只能包含小寫字母、底線和點號',
            'name.unique' => '權限名稱已存在',
            'name.max' => '權限名稱不能超過 100 個字元',
            'display_name.required' => '顯示名稱為必填項目',
            'display_name.max' => '顯示名稱不能超過 50 個字元',
            'description.max' => '描述不能超過 255 個字元',
            'module.required' => '模組為必填項目',
            'module.max' => '模組名稱不能超過 50 個字元',
            'type.required' => '權限類型為必填項目',
            'type.in' => '無效的權限類型',
            'dependencies.array' => '依賴權限格式錯誤',
            'dependencies.*.integer' => '依賴權限 ID 必須為整數',
            'dependencies.*.exists' => '選擇的依賴權限不存在',
        ];
    }

    /**
     * 開啟建立表單
     */
    #[On('open-permission-form')]
    public function openForm(string $mode = 'create', ?int $permissionId = null): void
    {
        try {
            $this->mode = $mode;
            $this->resetForm();
            
            if ($mode === 'edit' && $permissionId) {
                $this->loadPermission($permissionId);
            }
            
            $this->loadAvailablePermissions();
            $this->showForm = true;
            
            // 記錄表單開啟
            $this->auditService->logDataAccess('permissions', 'form_opened', [
                'mode' => $mode,
                'permission_id' => $permissionId,
            ]);
            
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '無法開啟表單：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 載入權限資料（編輯模式）
     */
    private function loadPermission(int $permissionId): void
    {
        $this->permission = Permission::with(['dependencies'])->find($permissionId);
        
        if (!$this->permission) {
            throw new \Exception('權限不存在');
        }

        // 檢查權限
        if (!auth()->user()->hasPermission('permissions.edit')) {
            throw new \Exception('您沒有編輯權限的權限');
        }

        // 填入表單資料
        $this->name = $this->permission->name;
        $this->display_name = $this->permission->display_name;
        $this->description = $this->permission->description ?? '';
        $this->module = $this->permission->module;
        $this->type = $this->permission->type;
        $this->dependencies = $this->permission->dependencies->pluck('id')->toArray();
        
        // 檢查是否為系統權限
        $this->isSystemPermission = $this->permission->is_system_permission;
    }

    /**
     * 載入可用的權限選項（用於依賴關係）
     */
    private function loadAvailablePermissions(): void
    {
        $query = Permission::select('id', 'name', 'display_name', 'module', 'type')
                          ->orderBy('module')
                          ->orderBy('name');
        
        // 編輯模式時排除自己
        if ($this->mode === 'edit' && $this->permission) {
            $query->where('id', '!=', $this->permission->id);
        }
        
        $this->availablePermissions = $query->get();
    }

    /**
     * 載入表單選項
     */
    private function loadFormOptions(): void
    {
        // 預定義模組選項
        $this->availableModules = [
            'users' => __('permissions.modules.users'),
            'roles' => __('permissions.modules.roles'),
            'permissions' => __('permissions.modules.permissions'),
            'dashboard' => __('permissions.modules.dashboard'),
            'system' => __('permissions.modules.system'),
            'reports' => __('permissions.modules.reports'),
            'settings' => __('permissions.modules.settings'),
            'audit' => __('permissions.modules.audit'),
            'monitoring' => __('permissions.modules.monitoring'),
            'security' => __('permissions.modules.security'),
        ];

        // 預定義類型選項
        $this->availableTypes = [
            'view' => __('permissions.types.view'),
            'create' => __('permissions.types.create'),
            'edit' => __('permissions.types.edit'),
            'delete' => __('permissions.types.delete'),
            'manage' => __('permissions.types.manage'),
        ];
    }

    /**
     * 儲存權限
     */
    public function save(): void
    {
        try {
            $this->isLoading = true;
            
            // 檢查權限
            $requiredPermission = $this->mode === 'create' ? 'permissions.create' : 'permissions.edit';
            if (!auth()->user()->hasPermission($requiredPermission)) {
                throw new \Exception('您沒有執行此操作的權限');
            }

            // 驗證表單資料
            $this->validate();
            
            // 準備儲存資料
            $data = [
                'name' => $this->name,
                'display_name' => $this->display_name,
                'description' => $this->description ?: null,
                'module' => $this->module,
                'type' => $this->type,
            ];

            // 系統權限保護
            if ($this->isSystemPermission && $this->mode === 'edit') {
                // 移除不能修改的欄位
                unset($data['name'], $data['module']);
            }

            // 驗證依賴關係
            if (!empty($this->dependencies)) {
                $this->validateDependencies();
            }

            if ($this->mode === 'create') {
                $this->createPermission($data);
            } else {
                $this->updatePermission($data);
            }

        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->errors());
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => $e->getMessage()
            ]);
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * 建立權限
     */
    private function createPermission(array $data): void
    {
        $permission = $this->permissionRepository->createPermission($data);
        
        // 同步依賴關係
        if (!empty($this->dependencies)) {
            $this->permissionRepository->syncDependencies($permission, $this->dependencies);
        }

        // 記錄審計日誌
        $this->auditService->logDataAccess('permissions', 'created', [
            'permission_id' => $permission->id,
            'permission_name' => $permission->name,
            'permission_data' => $data,
            'dependencies' => $this->dependencies,
        ]);

        $this->dispatch('show-toast', [
            'type' => 'success',
            'message' => "權限「{$permission->display_name}」建立成功"
        ]);

        $this->dispatch('permission-saved', permissionId: $permission->id);
        $this->closeForm();
    }

    /**
     * 更新權限
     */
    private function updatePermission(array $data): void
    {
        $originalData = $this->permission->toArray();
        $originalDependencies = $this->permission->dependencies->pluck('id')->toArray();
        
        $success = $this->permissionRepository->updatePermission($this->permission, $data);
        
        if (!$success) {
            throw new \Exception('權限更新失敗');
        }

        // 同步依賴關係
        $this->permissionRepository->syncDependencies($this->permission, $this->dependencies);

        // 記錄審計日誌
        $this->auditService->logDataAccess('permissions', 'updated', [
            'permission_id' => $this->permission->id,
            'permission_name' => $this->permission->name,
            'original_data' => $originalData,
            'updated_data' => $data,
            'original_dependencies' => $originalDependencies,
            'updated_dependencies' => $this->dependencies,
        ]);

        $this->dispatch('show-toast', [
            'type' => 'success',
            'message' => "權限「{$this->permission->display_name}」更新成功"
        ]);

        $this->dispatch('permission-saved', permissionId: $this->permission->id);
        $this->closeForm();
    }

    /**
     * 驗證依賴關係
     */
    public function validateDependencies(): bool
    {
        if (empty($this->dependencies)) {
            return true;
        }

        try {
            // 檢查循環依賴
            $permissionId = $this->permission?->id ?? 0;
            
            if ($this->permissionRepository->hasCircularDependency($permissionId, $this->dependencies)) {
                $this->addError('dependencies', '不能建立循環依賴關係');
                return false;
            }

            return true;
        } catch (\Exception $e) {
            $this->addError('dependencies', '依賴關係驗證失敗：' . $e->getMessage());
            return false;
        }
    }

    /**
     * 新增依賴權限
     */
    public function addDependency(int $permissionId): void
    {
        if (!in_array($permissionId, $this->dependencies)) {
            $this->dependencies[] = $permissionId;
            
            // 即時驗證
            if (!$this->validateDependencies()) {
                // 如果驗證失敗，移除剛新增的依賴
                $this->dependencies = array_diff($this->dependencies, [$permissionId]);
            }
        }
    }

    /**
     * 移除依賴權限
     */
    public function removeDependency(int $permissionId): void
    {
        $this->dependencies = array_values(array_diff($this->dependencies, [$permissionId]));
    }

    /**
     * 取消表單
     */
    public function cancel(): void
    {
        $this->closeForm();
    }

    /**
     * 關閉表單
     */
    private function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('permission-form-closed');
    }

    /**
     * 重置表單
     */
    private function resetForm(): void
    {
        $this->permission = null;
        $this->name = '';
        $this->display_name = '';
        $this->description = '';
        $this->module = '';
        $this->type = '';
        $this->dependencies = [];
        $this->isSystemPermission = false;
        $this->resetErrorBag();
    }

    /**
     * 模組變更時的處理
     */
    public function updatedModule(): void
    {
        // 根據模組自動建議權限名稱前綴
        if (!empty($this->module) && empty($this->name)) {
            $this->name = $this->module . '.';
        }
    }

    /**
     * 類型變更時的處理
     */
    public function updatedType(): void
    {
        // 根據類型自動建議顯示名稱
        if (!empty($this->type) && !empty($this->module) && empty($this->display_name)) {
            $typeNames = [
                'view' => '檢視',
                'create' => '建立',
                'edit' => '編輯',
                'delete' => '刪除',
                'manage' => '管理',
            ];
            
            $moduleName = $this->availableModules[$this->module] ?? $this->module;
            $typeName = $typeNames[$this->type] ?? $this->type;
            
            $this->display_name = $typeName . $moduleName;
        }
    }

    /**
     * 取得依賴權限的顯示資訊
     */
    public function getDependencyInfo(int $permissionId): ?array
    {
        $permission = $this->availablePermissions->firstWhere('id', $permissionId);
        
        if (!$permission) {
            return null;
        }

        return [
            'id' => $permission->id,
            'name' => $permission->name,
            'display_name' => $permission->display_name,
            'module' => $permission->module,
            'type' => $permission->type,
        ];
    }

    /**
     * 檢查是否可以選擇特定權限作為依賴
     */
    public function canSelectAsDependency(int $permissionId): bool
    {
        // 不能依賴自己
        if ($this->permission && $permissionId === $this->permission->id) {
            return false;
        }

        // 檢查是否會造成循環依賴
        $testDependencies = array_merge($this->dependencies, [$permissionId]);
        $currentPermissionId = $this->permission?->id ?? 0;
        
        return !$this->permissionRepository->hasCircularDependency($currentPermissionId, $testDependencies);
    }

    /**
     * 取得表單標題
     */
    public function getFormTitleProperty(): string
    {
        return $this->mode === 'create' ? '建立權限' : '編輯權限';
    }

    /**
     * 取得儲存按鈕文字
     */
    public function getSaveButtonTextProperty(): string
    {
        return $this->mode === 'create' ? '建立' : '更新';
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.permissions.permission-form', [
            'formTitle' => $this->formTitle,
            'saveButtonText' => $this->saveButtonText,
        ]);
    }
}