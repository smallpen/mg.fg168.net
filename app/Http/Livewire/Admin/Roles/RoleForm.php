<?php

namespace App\Http\Livewire\Admin\Roles;

use App\Http\Livewire\Admin\AdminComponent;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

/**
 * 角色表單 Livewire 元件
 * 
 * 用於建立和編輯角色，支援即時驗證和權限設定
 */
class RoleForm extends AdminComponent
{
    /**
     * 角色 ID（編輯模式時使用）
     * 
     * @var int|null
     */
    public $roleId;

    /**
     * 角色名稱
     * 
     * @var string
     */
    public $name = '';

    /**
     * 顯示名稱
     * 
     * @var string
     */
    public $display_name = '';

    /**
     * 描述
     * 
     * @var string
     */
    public $description = '';

    /**
     * 是否啟用（如果系統支援）
     * 
     * @var bool
     */
    public $is_active = true;

    /**
     * 選中的權限 ID 陣列
     * 
     * @var array
     */
    public $selectedPermissions = [];

    /**
     * 是否為編輯模式
     * 
     * @var bool
     */
    public $isEditMode = false;

    /**
     * 是否顯示權限設定區域
     * 
     * @var bool
     */
    public $showPermissions = false;

    /**
     * 即時驗證規則
     * 
     * @var array
     */
    protected $rules = [
        'name' => 'required|string|min:2|max:50|regex:/^[a-zA-Z0-9_]+$/',
        'display_name' => 'required|string|min:2|max:100',
        'description' => 'nullable|string|max:500',
        'is_active' => 'boolean',
        'selectedPermissions' => 'array',
        'selectedPermissions.*' => 'exists:permissions,id',
    ];

    /**
     * 驗證訊息
     * 
     * @var array
     */
    protected $messages = [
        'name.required' => '角色名稱為必填欄位',
        'name.min' => '角色名稱至少需要 2 個字元',
        'name.max' => '角色名稱不能超過 50 個字元',
        'name.regex' => '角色名稱只能包含字母、數字和底線',
        'name.unique' => '此角色名稱已被使用',
        'display_name.required' => '顯示名稱為必填欄位',
        'display_name.min' => '顯示名稱至少需要 2 個字元',
        'display_name.max' => '顯示名稱不能超過 100 個字元',
        'description.max' => '描述不能超過 500 個字元',
        'selectedPermissions.*.exists' => '選擇的權限不存在',
    ];

    /**
     * 元件掛載
     * 
     * @param int|null $roleId
     */
    public function mount($roleId = null)
    {
        parent::mount();

        // 檢查權限
        if ($roleId) {
            if (!$this->hasPermission('roles.edit')) {
                abort(403, __('admin.roles.no_permission_edit'));
            }
            $this->loadRole($roleId);
        } else {
            if (!$this->hasPermission('roles.create')) {
                abort(403, __('admin.roles.no_permission_create'));
            }
        }
    }

    /**
     * 載入角色資料
     * 
     * @param int $roleId
     */
    private function loadRole($roleId)
    {
        $role = Role::with('permissions')->findOrFail($roleId);

        // 防止編輯超級管理員角色（除非自己也是超級管理員）
        if ($role->name === 'super_admin' && !auth()->user()->isSuperAdmin()) {
            abort(403, __('admin.roles.cannot_modify_super_admin'));
        }

        $this->roleId = $role->id;
        $this->name = $role->name;
        $this->display_name = $role->display_name;
        $this->description = $role->description ?? '';
        
        // 檢查是否支援狀態管理
        if ($this->supportsStatus) {
            $this->is_active = $role->is_active ?? true;
        }
        
        $this->selectedPermissions = $role->permissions->pluck('id')->toArray();
        $this->isEditMode = true;
    }

    /**
     * 即時驗證角色名稱
     */
    public function updatedName()
    {
        $this->validateOnly('name', [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:50',
                'regex:/^[a-zA-Z0-9_]+$/',
                Rule::unique('roles', 'name')->ignore($this->roleId),
            ],
        ]);
    }

    /**
     * 即時驗證顯示名稱
     */
    public function updatedDisplayName()
    {
        $this->validateOnly('display_name');
    }

    /**
     * 即時驗證描述
     */
    public function updatedDescription()
    {
        $this->validateOnly('description');
    }

    /**
     * 取得驗證規則
     * 
     * @return array
     */
    protected function getValidationRules()
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:50',
                'regex:/^[a-zA-Z0-9_]+$/',
                Rule::unique('roles', 'name')->ignore($this->roleId),
            ],
            'display_name' => 'required|string|min:2|max:100',
            'description' => 'nullable|string|max:500',
            'selectedPermissions' => 'array',
            'selectedPermissions.*' => 'exists:permissions,id',
        ];

        // 如果支援狀態管理，加入驗證規則
        if ($this->supportsStatus) {
            $rules['is_active'] = 'boolean';
        }

        return $rules;
    }

    /**
     * 切換權限設定區域顯示
     */
    public function togglePermissions()
    {
        $this->showPermissions = !$this->showPermissions;
    }

    /**
     * 儲存角色
     */
    public function save()
    {
        $this->validate($this->getValidationRules(), $this->messages);

        try {
            DB::beginTransaction();

            $roleData = [
                'name' => $this->name,
                'display_name' => $this->display_name,
                'description' => $this->description ?: null,
            ];

            // 如果支援狀態管理，加入狀態欄位
            if ($this->supportsStatus) {
                $roleData['is_active'] = $this->is_active;
            }

            if ($this->isEditMode) {
                // 更新角色
                $role = Role::findOrFail($this->roleId);
                
                // 防止修改超級管理員角色（除非自己也是超級管理員）
                if ($role->name === 'super_admin' && !auth()->user()->isSuperAdmin()) {
                    throw new \Exception(__('admin.roles.cannot_modify_super_admin'));
                }

                // 防止修改超級管理員角色的名稱
                if ($role->name === 'super_admin' && $this->name !== 'super_admin') {
                    throw new \Exception(__('admin.roles.cannot_change_super_admin_name'));
                }

                $role->update($roleData);
                $message = __('admin.messages.success.updated', ['item' => '角色']);
            } else {
                // 建立角色
                $role = Role::create($roleData);
                $message = __('admin.messages.success.created', ['item' => '角色']);
            }

            // 同步權限
            $this->syncRolePermissions($role);

            DB::commit();

            $this->showSuccess($message);

            // 重新導向到角色列表
            return redirect()->route('admin.roles.index');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->showError($e->getMessage());
        }
    }

    /**
     * 同步角色權限
     * 
     * @param Role $role
     */
    private function syncRolePermissions(Role $role)
    {
        // 檢查是否有權限管理權限
        if (!$this->hasPermission('permissions.edit')) {
            return;
        }

        // 超級管理員角色應該擁有所有權限
        if ($role->name === 'super_admin') {
            $allPermissions = Permission::pluck('id')->toArray();
            $role->permissions()->sync($allPermissions);
        } else {
            // 同步選中的權限
            $role->permissions()->sync($this->selectedPermissions);
        }
    }

    /**
     * 取得所有可用權限（按模組分組）
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getGroupedPermissionsProperty()
    {
        return Permission::orderBy('module')->orderBy('name')->get()->groupBy('module');
    }

    /**
     * 檢查權限是否被選中
     * 
     * @param int $permissionId
     * @return bool
     */
    public function isPermissionSelected($permissionId)
    {
        return in_array($permissionId, $this->selectedPermissions);
    }

    /**
     * 切換權限選擇
     * 
     * @param int $permissionId
     */
    public function togglePermission($permissionId)
    {
        if (in_array($permissionId, $this->selectedPermissions)) {
            $this->selectedPermissions = array_diff($this->selectedPermissions, [$permissionId]);
        } else {
            $this->selectedPermissions[] = $permissionId;
        }
    }

    /**
     * 選擇模組的所有權限
     * 
     * @param string $module
     */
    public function selectModulePermissions($module)
    {
        $modulePermissions = Permission::where('module', $module)->pluck('id')->toArray();
        
        foreach ($modulePermissions as $permissionId) {
            if (!in_array($permissionId, $this->selectedPermissions)) {
                $this->selectedPermissions[] = $permissionId;
            }
        }
    }

    /**
     * 取消選擇模組的所有權限
     * 
     * @param string $module
     */
    public function deselectModulePermissions($module)
    {
        $modulePermissions = Permission::where('module', $module)->pluck('id')->toArray();
        $this->selectedPermissions = array_diff($this->selectedPermissions, $modulePermissions);
    }

    /**
     * 檢查模組是否全部被選中
     * 
     * @param string $module
     * @return bool
     */
    public function isModuleFullySelected($module)
    {
        $modulePermissions = Permission::where('module', $module)->pluck('id')->toArray();
        return count(array_intersect($modulePermissions, $this->selectedPermissions)) === count($modulePermissions);
    }

    /**
     * 檢查模組是否部分被選中
     * 
     * @param string $module
     * @return bool
     */
    public function isModulePartiallySelected($module)
    {
        $modulePermissions = Permission::where('module', $module)->pluck('id')->toArray();
        $selectedCount = count(array_intersect($modulePermissions, $this->selectedPermissions));
        return $selectedCount > 0 && $selectedCount < count($modulePermissions);
    }

    /**
     * 重設表單
     */
    public function resetForm()
    {
        $this->reset([
            'name',
            'display_name',
            'description',
            'selectedPermissions'
        ]);
        
        $this->is_active = true;
        $this->showPermissions = false;
        $this->resetErrorBag();
    }

    /**
     * 檢查是否支援狀態管理
     * 
     * @return bool
     */
    public function getSupportsStatusProperty()
    {
        return Role::first()?->getConnection()->getSchemaBuilder()->hasColumn('roles', 'is_active') ?? false;
    }

    /**
     * 取得角色名稱建議
     * 
     * @return array
     */
    public function getRoleNameSuggestionsProperty()
    {
        return [
            'admin' => '管理員',
            'editor' => '編輯者',
            'viewer' => '檢視者',
            'moderator' => '版主',
            'user' => '一般使用者',
        ];
    }

    /**
     * 使用建議的角色名稱
     * 
     * @param string $name
     * @param string $displayName
     */
    public function useSuggestion($name, $displayName)
    {
        $this->name = $name;
        $this->display_name = $displayName;
    }

    /**
     * 渲染元件
     * 
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.admin.roles.role-form', [
            'groupedPermissions' => $this->groupedPermissions,
            'supportsStatus' => $this->supportsStatus,
            'roleNameSuggestions' => $this->roleNameSuggestions,
        ]);
    }
}