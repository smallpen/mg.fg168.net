<?php

namespace App\Http\Livewire\Admin\Roles;

use App\Http\Livewire\Admin\AdminComponent;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

/**
 * 權限矩陣管理 Livewire 元件
 * 
 * 提供權限的階層式顯示和批量管理功能
 */
class PermissionMatrix extends AdminComponent
{
    /**
     * 選中的角色 ID
     * 
     * @var int|null
     */
    public $selectedRoleId;

    /**
     * 權限變更記錄
     * 
     * @var array
     */
    public $permissionChanges = [];

    /**
     * 是否顯示變更預覽
     * 
     * @var bool
     */
    public $showPreview = false;

    /**
     * 搜尋關鍵字
     * 
     * @var string
     */
    public $search = '';

    /**
     * 模組篩選
     * 
     * @var string
     */
    public $moduleFilter = '';

    /**
     * 顯示模式：matrix（矩陣）或 list（列表）
     * 
     * @var string
     */
    public $viewMode = 'matrix';

    /**
     * 是否顯示描述
     * 
     * @var bool
     */
    public $showDescriptions = false;

    /**
     * 元件掛載
     */
    public function mount()
    {
        parent::mount();
        
        // 檢查權限管理權限
        if (!$this->hasPermission('permissions.edit')) {
            abort(403, __('admin.permissions.no_permission_edit'));
        }
    }

    /**
     * 選擇角色
     * 
     * @param int $roleId
     */
    public function selectRole($roleId)
    {
        $this->selectedRoleId = $roleId;
        $this->permissionChanges = [];
        $this->showPreview = false;
    }

    /**
     * 切換權限狀態
     * 
     * @param int $roleId
     * @param int $permissionId
     */
    public function togglePermission($roleId, $permissionId)
    {
        $role = Role::findOrFail($roleId);
        $permission = Permission::findOrFail($permissionId);

        // 防止修改超級管理員角色的權限
        if ($role->name === 'super_admin' && !auth()->user()->isSuperAdmin()) {
            $this->showError(__('admin.roles.cannot_modify_super_admin'));
            return;
        }

        $hasPermission = $role->permissions()->where('permission_id', $permissionId)->exists();
        $changeKey = "{$roleId}_{$permissionId}";

        if ($hasPermission) {
            // 目前有權限，標記為移除
            $this->permissionChanges[$changeKey] = [
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'action' => 'remove',
                'role_name' => $role->display_name,
                'permission_name' => $permission->display_name,
            ];
        } else {
            // 目前沒有權限，標記為新增
            $this->permissionChanges[$changeKey] = [
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'action' => 'add',
                'role_name' => $role->display_name,
                'permission_name' => $permission->display_name,
            ];
        }

        $this->showPreview = count($this->permissionChanges) > 0;
    }

    /**
     * 批量指派模組權限給角色
     * 
     * @param int $roleId
     * @param string $module
     */
    public function assignModuleToRole($roleId, $module)
    {
        $role = Role::findOrFail($roleId);
        
        // 防止修改超級管理員角色的權限
        if ($role->name === 'super_admin' && !auth()->user()->isSuperAdmin()) {
            $this->showError(__('admin.roles.cannot_modify_super_admin'));
            return;
        }

        $modulePermissions = Permission::where('module', $module)->get();
        
        foreach ($modulePermissions as $permission) {
            $hasPermission = $role->permissions()->where('permission_id', $permission->id)->exists();
            if (!$hasPermission) {
                $changeKey = "{$roleId}_{$permission->id}";
                $this->permissionChanges[$changeKey] = [
                    'role_id' => $roleId,
                    'permission_id' => $permission->id,
                    'action' => 'add',
                    'role_name' => $role->display_name,
                    'permission_name' => $permission->display_name,
                ];
            }
        }

        $this->showPreview = count($this->permissionChanges) > 0;
        $this->showSuccess("已標記將 {$module} 模組的所有權限指派給 {$role->display_name}");
    }

    /**
     * 批量移除模組權限從角色
     * 
     * @param int $roleId
     * @param string $module
     */
    public function revokeModuleFromRole($roleId, $module)
    {
        $role = Role::findOrFail($roleId);
        
        // 防止修改超級管理員角色的權限
        if ($role->name === 'super_admin' && !auth()->user()->isSuperAdmin()) {
            $this->showError(__('admin.roles.cannot_modify_super_admin'));
            return;
        }

        $modulePermissions = Permission::where('module', $module)->get();
        
        foreach ($modulePermissions as $permission) {
            $hasPermission = $role->permissions()->where('permission_id', $permission->id)->exists();
            if ($hasPermission) {
                $changeKey = "{$roleId}_{$permission->id}";
                $this->permissionChanges[$changeKey] = [
                    'role_id' => $roleId,
                    'permission_id' => $permission->id,
                    'action' => 'remove',
                    'role_name' => $role->display_name,
                    'permission_name' => $permission->display_name,
                ];
            }
        }

        $this->showPreview = count($this->permissionChanges) > 0;
        $this->showSuccess("已標記將 {$module} 模組的所有權限從 {$role->display_name} 移除");
    }

    /**
     * 批量指派權限給所有角色
     * 
     * @param int $permissionId
     */
    public function assignPermissionToAllRoles($permissionId)
    {
        $permission = Permission::findOrFail($permissionId);
        $roles = Role::all();

        foreach ($roles as $role) {
            // 跳過超級管理員角色（除非自己也是超級管理員）
            if ($role->name === 'super_admin' && !auth()->user()->isSuperAdmin()) {
                continue;
            }

            $hasPermission = $role->permissions()->where('permission_id', $permissionId)->exists();
            if (!$hasPermission) {
                $changeKey = "{$role->id}_{$permissionId}";
                $this->permissionChanges[$changeKey] = [
                    'role_id' => $role->id,
                    'permission_id' => $permissionId,
                    'action' => 'add',
                    'role_name' => $role->display_name,
                    'permission_name' => $permission->display_name,
                ];
            }
        }

        $this->showPreview = count($this->permissionChanges) > 0;
        $this->showSuccess("已標記將 {$permission->display_name} 權限指派給所有角色");
    }

    /**
     * 批量移除權限從所有角色
     * 
     * @param int $permissionId
     */
    public function revokePermissionFromAllRoles($permissionId)
    {
        $permission = Permission::findOrFail($permissionId);
        $roles = Role::all();

        foreach ($roles as $role) {
            // 跳過超級管理員角色（除非自己也是超級管理員）
            if ($role->name === 'super_admin' && !auth()->user()->isSuperAdmin()) {
                continue;
            }

            $hasPermission = $role->permissions()->where('permission_id', $permissionId)->exists();
            if ($hasPermission) {
                $changeKey = "{$role->id}_{$permissionId}";
                $this->permissionChanges[$changeKey] = [
                    'role_id' => $role->id,
                    'permission_id' => $permissionId,
                    'action' => 'remove',
                    'role_name' => $role->display_name,
                    'permission_name' => $permission->display_name,
                ];
            }
        }

        $this->showPreview = count($this->permissionChanges) > 0;
        $this->showSuccess("已標記將 {$permission->display_name} 權限從所有角色移除");
    }

    /**
     * 應用權限變更
     */
    public function applyChanges()
    {
        if (empty($this->permissionChanges)) {
            $this->showError('沒有待應用的變更');
            return;
        }

        try {
            DB::beginTransaction();

            $addCount = 0;
            $removeCount = 0;

            foreach ($this->permissionChanges as $change) {
                $role = Role::findOrFail($change['role_id']);
                
                if ($change['action'] === 'add') {
                    if (!$role->permissions()->where('permission_id', $change['permission_id'])->exists()) {
                        $role->permissions()->attach($change['permission_id']);
                        $addCount++;
                    }
                } elseif ($change['action'] === 'remove') {
                    if ($role->permissions()->where('permission_id', $change['permission_id'])->exists()) {
                        $role->permissions()->detach($change['permission_id']);
                        $removeCount++;
                    }
                }
            }

            DB::commit();

            $this->permissionChanges = [];
            $this->showPreview = false;

            $message = "權限變更已應用：新增 {$addCount} 個權限，移除 {$removeCount} 個權限";
            $this->showSuccess($message);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->showError('應用權限變更時發生錯誤：' . $e->getMessage());
        }
    }

    /**
     * 取消所有變更
     */
    public function cancelChanges()
    {
        $this->permissionChanges = [];
        $this->showPreview = false;
        $this->showSuccess('已取消所有待應用的變更');
    }

    /**
     * 移除特定變更
     * 
     * @param string $changeKey
     */
    public function removeChange($changeKey)
    {
        unset($this->permissionChanges[$changeKey]);
        $this->showPreview = count($this->permissionChanges) > 0;
    }

    /**
     * 切換顯示模式
     */
    public function toggleViewMode()
    {
        $this->viewMode = $this->viewMode === 'matrix' ? 'list' : 'matrix';
    }

    /**
     * 切換描述顯示
     */
    public function toggleDescriptions()
    {
        $this->showDescriptions = !$this->showDescriptions;
    }

    /**
     * 清除篩選條件
     */
    public function clearFilters()
    {
        $this->search = '';
        $this->moduleFilter = '';
    }

    /**
     * 取得所有角色
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRolesProperty()
    {
        $query = Role::withCount('permissions')->orderBy('name');

        // 非超級管理員不能看到超級管理員角色
        if (!auth()->user()->isSuperAdmin()) {
            $query->where('name', '!=', 'super_admin');
        }

        return $query->get();
    }

    /**
     * 取得篩選後的權限（按模組分組）
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFilteredPermissionsProperty()
    {
        $query = Permission::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('display_name', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->moduleFilter) {
            $query->where('module', $this->moduleFilter);
        }

        return $query->orderBy('module')->orderBy('name')->get()->groupBy('module');
    }

    /**
     * 取得所有模組
     * 
     * @return array
     */
    public function getModulesProperty()
    {
        return Permission::distinct()->pluck('module')->filter()->sort()->values()->toArray();
    }

    /**
     * 檢查角色是否擁有權限
     * 
     * @param int $roleId
     * @param int $permissionId
     * @return bool
     */
    public function roleHasPermission($roleId, $permissionId)
    {
        $changeKey = "{$roleId}_{$permissionId}";
        
        // 檢查是否有待應用的變更
        if (isset($this->permissionChanges[$changeKey])) {
            $change = $this->permissionChanges[$changeKey];
            $currentHasPermission = Role::find($roleId)->permissions()->where('permission_id', $permissionId)->exists();
            
            if ($change['action'] === 'add') {
                return true; // 將要新增
            } elseif ($change['action'] === 'remove') {
                return false; // 將要移除
            }
        }

        // 返回當前狀態
        return Role::find($roleId)->permissions()->where('permission_id', $permissionId)->exists();
    }

    /**
     * 檢查權限變更狀態
     * 
     * @param int $roleId
     * @param int $permissionId
     * @return string|null
     */
    public function getPermissionChangeStatus($roleId, $permissionId)
    {
        $changeKey = "{$roleId}_{$permissionId}";
        return isset($this->permissionChanges[$changeKey]) ? $this->permissionChanges[$changeKey]['action'] : null;
    }

    /**
     * 檢查角色是否擁有模組的所有權限
     * 
     * @param int $roleId
     * @param string $module
     * @return bool
     */
    public function roleHasAllModulePermissions($roleId, $module)
    {
        $modulePermissions = Permission::where('module', $module)->pluck('id');
        $rolePermissions = Role::find($roleId)->permissions()->pluck('permission_id');
        
        return $modulePermissions->diff($rolePermissions)->isEmpty();
    }

    /**
     * 檢查角色是否擁有模組的部分權限
     * 
     * @param int $roleId
     * @param string $module
     * @return bool
     */
    public function roleHasSomeModulePermissions($roleId, $module)
    {
        $modulePermissions = Permission::where('module', $module)->pluck('id');
        $rolePermissions = Role::find($roleId)->permissions()->pluck('permission_id');
        
        return $modulePermissions->intersect($rolePermissions)->isNotEmpty() && 
               !$modulePermissions->diff($rolePermissions)->isEmpty();
    }

    /**
     * 取得變更統計
     * 
     * @return array
     */
    public function getChangeStatsProperty()
    {
        $addCount = collect($this->permissionChanges)->where('action', 'add')->count();
        $removeCount = collect($this->permissionChanges)->where('action', 'remove')->count();
        
        return [
            'total' => count($this->permissionChanges),
            'add' => $addCount,
            'remove' => $removeCount,
        ];
    }

    /**
     * 渲染元件
     * 
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.admin.roles.permission-matrix', [
            'roles' => $this->roles,
            'filteredPermissions' => $this->filteredPermissions,
            'modules' => $this->modules,
            'changeStats' => $this->changeStats,
        ]);
    }
}