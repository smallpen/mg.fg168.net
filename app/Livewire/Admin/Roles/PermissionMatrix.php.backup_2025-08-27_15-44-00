<?php

namespace App\Livewire\Admin\Roles;

use App\Models\Role;
use App\Models\Permission;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use Livewire\Component;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 權限矩陣管理元件
 * 
 * 提供視覺化的權限矩陣介面，支援按模組分組顯示、
 * 權限勾選/取消、依賴關係自動處理和批量操作功能
 */
class PermissionMatrix extends Component
{
    /**
     * 搜尋關鍵字
     */
    public string $search = '';

    /**
     * 模組篩選
     */
    public string $moduleFilter = '';

    /**
     * 顯示模式：matrix（矩陣）或 list（列表）
     */
    public string $viewMode = 'matrix';

    /**
     * 是否顯示權限描述
     */
    public bool $showDescriptions = false;

    /**
     * 是否顯示變更預覽
     */
    public bool $showPreview = false;

    /**
     * 權限變更記錄
     */
    public array $permissionChanges = [];

    /**
     * 選中的角色（用於批量操作）
     */
    public array $selectedRoles = [];

    /**
     * 選中的權限（用於批量操作）
     */
    public array $selectedPermissions = [];

    /**
     * 批量操作模式
     */
    public bool $bulkMode = false;

    /**
     * 資料存取層
     */
    protected ?RoleRepositoryInterface $roleRepository = null;
    protected ?PermissionRepositoryInterface $permissionRepository = null;

    /**
     * 元件初始化
     */
    public function mount()
    {
        $this->initializeRepositories();
        
        // 檢查權限
        $this->authorize('roles.view');
    }

    /**
     * 初始化資料存取層
     */
    protected function initializeRepositories(): void
    {
        if (!$this->roleRepository) {
            $this->roleRepository = app(RoleRepositoryInterface::class);
        }
        
        if (!$this->permissionRepository) {
            $this->permissionRepository = app(PermissionRepositoryInterface::class);
        }
    }

    /**
     * 取得所有角色（含權限數量）
     */
    public function getRolesProperty(): Collection
    {
        return Cache::remember('permission_matrix_roles', 300, function () {
            return Role::with('permissions')
                      ->withCount('permissions')
                      ->where('is_active', true)
                      ->orderBy('name')
                      ->get();
        });
    }

    /**
     * 取得所有模組列表
     */
    public function getModulesProperty(): Collection
    {
        $this->initializeRepositories();
        
        return Cache::remember('permission_matrix_modules', 300, function () {
            return $this->permissionRepository->getAllModules();
        });
    }

    /**
     * 取得篩選後的權限（按模組分組）
     */
    public function getFilteredPermissionsProperty(): Collection
    {
        $cacheKey = 'permission_matrix_filtered_' . md5($this->search . $this->moduleFilter);
        
        return Cache::remember($cacheKey, 300, function () {
            $query = Permission::query();

            // 搜尋篩選
            if ($this->search) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                      ->orWhere('display_name', 'like', "%{$this->search}%")
                      ->orWhere('description', 'like', "%{$this->search}%");
                });
            }

            // 模組篩選
            if ($this->moduleFilter) {
                $query->where('module', $this->moduleFilter);
            }

            return $query->orderBy('module')
                        ->orderBy('name')
                        ->get()
                        ->groupBy('module');
        });
    }

    /**
     * 取得變更統計
     */
    public function getChangeStatsProperty(): array
    {
        $add = collect($this->permissionChanges)->where('action', 'add')->count();
        $remove = collect($this->permissionChanges)->where('action', 'remove')->count();
        
        return [
            'total' => $add + $remove,
            'add' => $add,
            'remove' => $remove
        ];
    }

    /**
     * 切換權限狀態
     */
    public function togglePermission(int $roleId, int $permissionId): void
    {
        $this->authorize('roles.edit');

        try {
            $role = Role::findOrFail($roleId);
            $permission = Permission::findOrFail($permissionId);
            
            $hasPermission = $this->roleHasPermission($roleId, $permissionId);
            $changeKey = "{$roleId}_{$permissionId}";

            if ($hasPermission) {
                // 移除權限
                $this->addPermissionChange($changeKey, [
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'role_name' => $role->display_name,
                    'permission_name' => $permission->display_name,
                    'action' => 'remove'
                ]);
            } else {
                // 新增權限
                $this->addPermissionChange($changeKey, [
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'role_name' => $role->display_name,
                    'permission_name' => $permission->display_name,
                    'action' => 'add'
                ]);

                // 檢查並自動新增依賴權限
                $this->handlePermissionDependencies($roleId, $permission);
            }

            $this->updatePreviewStatus();
            $this->dispatch('permission-toggled', roleId: $roleId, permissionId: $permissionId);

        } catch (\Exception $e) {
            Log::error('權限切換失敗', [
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'error' => $e->getMessage()
            ]);
            
            $this->dispatch('error', message: '權限切換失敗：' . $e->getMessage());
        }
    }

    /**
     * 處理權限依賴關係
     */
    protected function handlePermissionDependencies(int $roleId, Permission $permission): void
    {
        $dependencies = $permission->dependencies;
        
        foreach ($dependencies as $dependency) {
            if (!$this->roleHasPermission($roleId, $dependency->id)) {
                $changeKey = "{$roleId}_{$dependency->id}";
                $this->addPermissionChange($changeKey, [
                    'role_id' => $roleId,
                    'permission_id' => $dependency->id,
                    'role_name' => Role::find($roleId)->display_name,
                    'permission_name' => $dependency->display_name,
                    'action' => 'add',
                    'auto_added' => true
                ]);
            }
        }
    }

    /**
     * 新增權限變更記錄
     */
    protected function addPermissionChange(string $key, array $change): void
    {
        // 如果已存在相反的操作，則移除
        if (isset($this->permissionChanges[$key])) {
            unset($this->permissionChanges[$key]);
        } else {
            $this->permissionChanges[$key] = $change;
        }
    }

    /**
     * 批量指派模組權限給角色
     */
    public function assignModuleToRole(int $roleId, string $module): void
    {
        $this->authorize('roles.edit');

        try {
            $role = Role::findOrFail($roleId);
            $permissions = Permission::where('module', $module)->get();

            foreach ($permissions as $permission) {
                if (!$this->roleHasPermission($roleId, $permission->id)) {
                    $changeKey = "{$roleId}_{$permission->id}";
                    $this->addPermissionChange($changeKey, [
                        'role_id' => $roleId,
                        'permission_id' => $permission->id,
                        'role_name' => $role->display_name,
                        'permission_name' => $permission->display_name,
                        'action' => 'add'
                    ]);
                }
            }

            $this->updatePreviewStatus();
            $this->dispatch('module-assigned', roleId: $roleId, module: $module);

        } catch (\Exception $e) {
            Log::error('模組權限指派失敗', [
                'role_id' => $roleId,
                'module' => $module,
                'error' => $e->getMessage()
            ]);
            
            $this->dispatch('error', message: '模組權限指派失敗：' . $e->getMessage());
        }
    }

    /**
     * 批量移除角色的模組權限
     */
    public function revokeModuleFromRole(int $roleId, string $module): void
    {
        $this->authorize('roles.edit');

        try {
            $role = Role::findOrFail($roleId);
            $permissions = Permission::where('module', $module)->get();

            foreach ($permissions as $permission) {
                if ($this->roleHasPermission($roleId, $permission->id)) {
                    $changeKey = "{$roleId}_{$permission->id}";
                    $this->addPermissionChange($changeKey, [
                        'role_id' => $roleId,
                        'permission_id' => $permission->id,
                        'role_name' => $role->display_name,
                        'permission_name' => $permission->display_name,
                        'action' => 'remove'
                    ]);
                }
            }

            $this->updatePreviewStatus();
            $this->dispatch('module-revoked', roleId: $roleId, module: $module);

        } catch (\Exception $e) {
            Log::error('模組權限移除失敗', [
                'role_id' => $roleId,
                'module' => $module,
                'error' => $e->getMessage()
            ]);
            
            $this->dispatch('error', message: '模組權限移除失敗：' . $e->getMessage());
        }
    }

    /**
     * 指派權限給所有角色
     */
    public function assignPermissionToAllRoles(int $permissionId): void
    {
        $this->authorize('roles.edit');

        try {
            $permission = Permission::findOrFail($permissionId);
            
            foreach ($this->roles as $role) {
                if (!$this->roleHasPermission($role->id, $permissionId)) {
                    $changeKey = "{$role->id}_{$permissionId}";
                    $this->addPermissionChange($changeKey, [
                        'role_id' => $role->id,
                        'permission_id' => $permissionId,
                        'role_name' => $role->display_name,
                        'permission_name' => $permission->display_name,
                        'action' => 'add'
                    ]);
                }
            }

            $this->updatePreviewStatus();
            $this->dispatch('permission-assigned-to-all', permissionId: $permissionId);

        } catch (\Exception $e) {
            Log::error('權限批量指派失敗', [
                'permission_id' => $permissionId,
                'error' => $e->getMessage()
            ]);
            
            $this->dispatch('error', message: '權限批量指派失敗：' . $e->getMessage());
        }
    }

    /**
     * 從所有角色移除權限
     */
    public function revokePermissionFromAllRoles(int $permissionId): void
    {
        $this->authorize('roles.edit');

        try {
            $permission = Permission::findOrFail($permissionId);
            
            foreach ($this->roles as $role) {
                if ($this->roleHasPermission($role->id, $permissionId)) {
                    $changeKey = "{$role->id}_{$permissionId}";
                    $this->addPermissionChange($changeKey, [
                        'role_id' => $role->id,
                        'permission_id' => $permissionId,
                        'role_name' => $role->display_name,
                        'permission_name' => $permission->display_name,
                        'action' => 'remove'
                    ]);
                }
            }

            $this->updatePreviewStatus();
            $this->dispatch('permission-revoked-from-all', permissionId: $permissionId);

        } catch (\Exception $e) {
            Log::error('權限批量移除失敗', [
                'permission_id' => $permissionId,
                'error' => $e->getMessage()
            ]);
            
            $this->dispatch('error', message: '權限批量移除失敗：' . $e->getMessage());
        }
    }

    /**
     * 指派模組權限給所有角色
     */
    public function assignModuleToAllRoles(string $module): void
    {
        $this->authorize('roles.edit');

        try {
            $permissions = Permission::where('module', $module)->get();
            
            foreach ($this->roles as $role) {
                foreach ($permissions as $permission) {
                    if (!$this->roleHasPermission($role->id, $permission->id)) {
                        $changeKey = "{$role->id}_{$permission->id}";
                        $this->addPermissionChange($changeKey, [
                            'role_id' => $role->id,
                            'permission_id' => $permission->id,
                            'role_name' => $role->display_name,
                            'permission_name' => $permission->display_name,
                            'action' => 'add'
                        ]);
                    }
                }
            }

            $this->updatePreviewStatus();
            $this->dispatch('module-assigned-to-all', module: $module);

        } catch (\Exception $e) {
            Log::error('模組權限批量指派失敗', [
                'module' => $module,
                'error' => $e->getMessage()
            ]);
            
            $this->dispatch('error', message: '模組權限批量指派失敗：' . $e->getMessage());
        }
    }

    /**
     * 從所有角色移除模組權限
     */
    public function revokeModuleFromAllRoles(string $module): void
    {
        $this->authorize('roles.edit');

        try {
            $permissions = Permission::where('module', $module)->get();
            
            foreach ($this->roles as $role) {
                foreach ($permissions as $permission) {
                    if ($this->roleHasPermission($role->id, $permission->id)) {
                        $changeKey = "{$role->id}_{$permission->id}";
                        $this->addPermissionChange($changeKey, [
                            'role_id' => $role->id,
                            'permission_id' => $permission->id,
                            'role_name' => $role->display_name,
                            'permission_name' => $permission->display_name,
                            'action' => 'remove'
                        ]);
                    }
                }
            }

            $this->updatePreviewStatus();
            $this->dispatch('module-revoked-from-all', module: $module);

        } catch (\Exception $e) {
            Log::error('模組權限批量移除失敗', [
                'module' => $module,
                'error' => $e->getMessage()
            ]);
            
            $this->dispatch('error', message: '模組權限批量移除失敗：' . $e->getMessage());
        }
    }

    /**
     * 應用所有權限變更
     */
    public function applyChanges(): void
    {
        $this->authorize('roles.edit');

        if (empty($this->permissionChanges)) {
            $this->dispatch('warning', message: '沒有待應用的變更');
            return;
        }

        try {
            DB::beginTransaction();

            foreach ($this->permissionChanges as $change) {
                $role = Role::findOrFail($change['role_id']);
                $permission = Permission::findOrFail($change['permission_id']);

                if ($change['action'] === 'add') {
                    $role->givePermissionTo($permission);
                } else {
                    $role->revokePermissionTo($permission);
                }
            }

            DB::commit();

            // 清除快取
            $this->clearPermissionCache();

            // 重置變更記錄
            $this->permissionChanges = [];
            $this->showPreview = false;

            $this->dispatch('success', message: '權限變更已成功應用');
            $this->dispatch('permissions-applied');

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('權限變更應用失敗', [
                'changes' => $this->permissionChanges,
                'error' => $e->getMessage()
            ]);
            
            $this->dispatch('error', message: '權限變更應用失敗：' . $e->getMessage());
        }
    }

    /**
     * 取消所有權限變更
     */
    public function cancelChanges(): void
    {
        $this->permissionChanges = [];
        $this->showPreview = false;
        
        $this->dispatch('info', message: '已取消所有權限變更');
        $this->dispatch('changes-cancelled');
    }

    /**
     * 移除特定變更
     */
    public function removeChange(string $changeKey): void
    {
        if (isset($this->permissionChanges[$changeKey])) {
            unset($this->permissionChanges[$changeKey]);
            $this->updatePreviewStatus();
            
            $this->dispatch('change-removed', changeKey: $changeKey);
        }
    }

    /**
     * 切換顯示模式
     */
    public function toggleViewMode(): void
    {
        $this->viewMode = $this->viewMode === 'matrix' ? 'list' : 'matrix';
    }

    /**
     * 切換描述顯示
     */
    public function toggleDescriptions(): void
    {
        $this->showDescriptions = !$this->showDescriptions;
    }

    /**
     * 清除篩選條件
     */
    public function clearFilters(): void
    {
        $this->search = '';
        $this->moduleFilter = '';
        
        $this->dispatch('filters-cleared');
    }

    /**
     * 檢查角色是否擁有特定權限
     */
    public function roleHasPermission(int $roleId, int $permissionId): bool
    {
        $role = $this->roles->firstWhere('id', $roleId);
        if (!$role) {
            return false;
        }

        // 檢查是否有待處理的變更
        $changeKey = "{$roleId}_{$permissionId}";
        if (isset($this->permissionChanges[$changeKey])) {
            $change = $this->permissionChanges[$changeKey];
            $currentHas = $role->permissions->contains('id', $permissionId);
            
            return $change['action'] === 'add' ? true : false;
        }

        return $role->permissions->contains('id', $permissionId);
    }

    /**
     * 檢查角色是否擁有模組的所有權限
     */
    public function roleHasAllModulePermissions(int $roleId, string $module): bool
    {
        $modulePermissions = Permission::where('module', $module)->get();
        
        foreach ($modulePermissions as $permission) {
            if (!$this->roleHasPermission($roleId, $permission->id)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * 檢查角色是否擁有模組的部分權限
     */
    public function roleHasSomeModulePermissions(int $roleId, string $module): bool
    {
        $modulePermissions = Permission::where('module', $module)->get();
        
        foreach ($modulePermissions as $permission) {
            if ($this->roleHasPermission($roleId, $permission->id)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * 取得權限變更狀態
     */
    public function getPermissionChangeStatus(int $roleId, int $permissionId): ?string
    {
        $changeKey = "{$roleId}_{$permissionId}";
        
        if (isset($this->permissionChanges[$changeKey])) {
            return $this->permissionChanges[$changeKey]['action'];
        }
        
        return null;
    }

    /**
     * 更新預覽狀態
     */
    protected function updatePreviewStatus(): void
    {
        $this->showPreview = !empty($this->permissionChanges);
    }

    /**
     * 清除權限相關快取
     */
    protected function clearPermissionCache(): void
    {
        Cache::forget('permission_matrix_roles');
        Cache::forget('permission_matrix_modules');
        
        // 清除篩選快取
        $cachePattern = 'permission_matrix_filtered_*';
        // 注意：這裡需要根據實際快取驅動實作清除模式匹配的快取
    }

    /**
     * 監聽搜尋變更
     */
    public function updatedSearch(): void
    {
        $this->dispatch('search-updated', search: $this->search);
    }

    /**
     * 監聽模組篩選變更
     */
    public function updatedModuleFilter(): void
    {
        $this->dispatch('module-filter-updated', module: $this->moduleFilter);
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.roles.permission-matrix');
    }
}