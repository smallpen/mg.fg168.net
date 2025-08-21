<?php

namespace App\Livewire\Admin\Roles;

use App\Livewire\Admin\AdminComponent;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;

/**
 * 批量權限設定模態元件
 * 
 * 提供批量設定多個角色權限的功能
 */
class BulkPermissionModal extends AdminComponent
{
    // 模態狀態
    public bool $showModal = false;
    
    // 選中的角色
    public array $selectedRoleIds = [];
    public Collection $selectedRoles;
    
    // 權限相關屬性
    public Collection $permissions;
    public Collection $modules;
    public array $selectedPermissions = [];
    public string $selectedModule = 'all';
    public string $operationType = 'add'; // add, remove, replace
    
    // 操作結果
    public array $operationResults = [];
    public bool $showResults = false;

    protected RoleRepositoryInterface $roleRepository;
    protected PermissionRepositoryInterface $permissionRepository;

    /**
     * 元件初始化
     */
    public function boot(
        RoleRepositoryInterface $roleRepository,
        PermissionRepositoryInterface $permissionRepository
    ): void {
        $this->roleRepository = $roleRepository;
        $this->permissionRepository = $permissionRepository;
        $this->selectedRoles = collect();
        $this->permissions = collect();
        $this->modules = collect();
    }

    /**
     * 開啟批量權限設定模態
     */
    #[On('open-bulk-permission-modal')]
    public function openModal(array $roleIds): void
    {
        $this->checkPermission('roles.edit');
        
        $this->selectedRoleIds = $roleIds;
        $this->loadSelectedRoles();
        $this->loadPermissions();
        $this->resetForm();
        $this->showModal = true;
    }

    /**
     * 關閉模態
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->showResults = false;
        $this->resetForm();
    }

    /**
     * 重置表單
     */
    private function resetForm(): void
    {
        $this->selectedPermissions = [];
        $this->selectedModule = 'all';
        $this->operationType = 'add';
        $this->operationResults = [];
        $this->resetErrorBag();
    }

    /**
     * 載入選中的角色
     */
    private function loadSelectedRoles(): void
    {
        $this->selectedRoles = Role::whereIn('id', $this->selectedRoleIds)
            ->select('id', 'name', 'display_name', 'is_system_role')
            ->get();
    }

    /**
     * 載入權限資料
     */
    private function loadPermissions(): void
    {
        $this->permissions = $this->permissionRepository->all();
        $this->modules = $this->permissionRepository->getAllGroupedByModule();
    }

    /**
     * 取得分組權限（計算屬性）
     */
    public function getGroupedPermissionsProperty(): Collection
    {
        if ($this->selectedModule === 'all') {
            return $this->modules;
        }

        return $this->modules->filter(function ($permissions, $module) {
            return $module === $this->selectedModule;
        });
    }

    /**
     * 取得操作類型選項（計算屬性）
     */
    public function getOperationTypesProperty(): array
    {
        return [
            'add' => __('admin.roles.bulk_permissions.operations.add'),
            'remove' => __('admin.roles.bulk_permissions.operations.remove'),
            'replace' => __('admin.roles.bulk_permissions.operations.replace'),
        ];
    }

    /**
     * 模組篩選變更時重置權限選擇
     */
    public function updatedSelectedModule(): void
    {
        $this->selectedPermissions = [];
    }

    /**
     * 全選當前模組權限
     */
    public function selectAllModulePermissions(): void
    {
        if ($this->selectedModule === 'all') {
            $this->selectedPermissions = $this->permissions->pluck('id')->toArray();
        } else {
            $modulePermissions = $this->modules->get($this->selectedModule, collect());
            $this->selectedPermissions = array_merge(
                $this->selectedPermissions,
                $modulePermissions->pluck('id')->toArray()
            );
            $this->selectedPermissions = array_unique($this->selectedPermissions);
        }
    }

    /**
     * 清除當前模組權限選擇
     */
    public function clearModulePermissions(): void
    {
        if ($this->selectedModule === 'all') {
            $this->selectedPermissions = [];
        } else {
            $modulePermissions = $this->modules->get($this->selectedModule, collect());
            $modulePermissionIds = $modulePermissions->pluck('id')->toArray();
            $this->selectedPermissions = array_diff($this->selectedPermissions, $modulePermissionIds);
        }
    }

    /**
     * 執行批量權限操作
     */
    public function executeBulkPermissionOperation(): void
    {
        $this->validate([
            'selectedPermissions' => 'required|array|min:1',
            'selectedPermissions.*' => 'exists:permissions,id',
            'operationType' => 'required|in:add,remove,replace',
        ], [
            'selectedPermissions.required' => __('admin.roles.bulk_permissions.errors.no_permissions_selected'),
            'selectedPermissions.min' => __('admin.roles.bulk_permissions.errors.no_permissions_selected'),
        ]);

        try {
            $this->operationResults = [];
            $successCount = 0;
            $errorCount = 0;

            foreach ($this->selectedRoles as $role) {
                try {
                    // 檢查是否為系統角色
                    if ($role->is_system_role && $this->operationType === 'replace') {
                        $this->operationResults[] = [
                            'role' => $role,
                            'success' => false,
                            'message' => __('admin.roles.bulk_permissions.errors.system_role_replace', ['name' => $role->display_name])
                        ];
                        $errorCount++;
                        continue;
                    }

                    $this->executeRolePermissionOperation($role);
                    
                    $this->operationResults[] = [
                        'role' => $role,
                        'success' => true,
                        'message' => $this->getSuccessMessage($role)
                    ];
                    $successCount++;

                } catch (\Exception $e) {
                    $this->operationResults[] = [
                        'role' => $role,
                        'success' => false,
                        'message' => __('admin.roles.bulk_permissions.errors.operation_failed', [
                            'name' => $role->display_name,
                            'error' => $e->getMessage()
                        ])
                    ];
                    $errorCount++;
                }
            }

            // 顯示操作結果
            $this->showResults = true;
            
            // 發送事件通知父元件
            $this->dispatch('bulk-permissions-updated', [
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'operation_type' => $this->operationType
            ]);

        } catch (\Exception $e) {
            $this->addError('bulk_operation', $e->getMessage());
        }
    }

    /**
     * 執行單一角色的權限操作
     */
    private function executeRolePermissionOperation(Role $role): void
    {
        switch ($this->operationType) {
            case 'add':
                // 新增權限（不移除現有權限）
                $currentPermissions = $role->permissions->pluck('id')->toArray();
                $newPermissions = array_unique(array_merge($currentPermissions, $this->selectedPermissions));
                $this->roleRepository->syncPermissions($role, $newPermissions);
                break;

            case 'remove':
                // 移除權限
                $currentPermissions = $role->permissions->pluck('id')->toArray();
                $newPermissions = array_diff($currentPermissions, $this->selectedPermissions);
                $this->roleRepository->syncPermissions($role, $newPermissions);
                break;

            case 'replace':
                // 替換權限（完全替換）
                $this->roleRepository->syncPermissions($role, $this->selectedPermissions);
                break;
        }
    }

    /**
     * 取得成功訊息
     */
    private function getSuccessMessage(Role $role): string
    {
        $permissionCount = count($this->selectedPermissions);
        
        return match ($this->operationType) {
            'add' => __('admin.roles.bulk_permissions.success.added', [
                'name' => $role->display_name,
                'count' => $permissionCount
            ]),
            'remove' => __('admin.roles.bulk_permissions.success.removed', [
                'name' => $role->display_name,
                'count' => $permissionCount
            ]),
            'replace' => __('admin.roles.bulk_permissions.success.replaced', [
                'name' => $role->display_name,
                'count' => $permissionCount
            ]),
        };
    }

    /**
     * 檢查權限是否被選中
     */
    public function isPermissionSelected(int $permissionId): bool
    {
        return in_array($permissionId, $this->selectedPermissions);
    }

    /**
     * 切換權限選擇狀態
     */
    public function togglePermission(int $permissionId): void
    {
        if ($this->isPermissionSelected($permissionId)) {
            $this->selectedPermissions = array_diff($this->selectedPermissions, [$permissionId]);
        } else {
            $this->selectedPermissions[] = $permissionId;
        }
    }

    /**
     * 取得選中權限的統計資訊
     */
    public function getSelectedPermissionsStatsProperty(): array
    {
        $selectedCount = count($this->selectedPermissions);
        $totalCount = $this->permissions->count();
        
        $moduleStats = [];
        foreach ($this->modules as $module => $permissions) {
            $modulePermissionIds = $permissions->pluck('id')->toArray();
            $selectedInModule = count(array_intersect($this->selectedPermissions, $modulePermissionIds));
            $totalInModule = count($modulePermissionIds);
            
            $moduleStats[$module] = [
                'selected' => $selectedInModule,
                'total' => $totalInModule,
                'percentage' => $totalInModule > 0 ? round(($selectedInModule / $totalInModule) * 100) : 0
            ];
        }

        return [
            'total_selected' => $selectedCount,
            'total_permissions' => $totalCount,
            'percentage' => $totalCount > 0 ? round(($selectedCount / $totalCount) * 100) : 0,
            'modules' => $moduleStats
        ];
    }

    /**
     * 重新執行操作
     */
    public function retryOperation(): void
    {
        $this->showResults = false;
        $this->executeBulkPermissionOperation();
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.roles.bulk-permission-modal');
    }
}