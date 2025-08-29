<?php

namespace App\Livewire\Admin\Roles;

use App\Livewire\Admin\AdminComponent;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Services\RoleCacheService;
use App\Services\RoleOptimizationService;
use App\Services\RoleSecurityService;
use App\Services\RoleDataValidationService;
use App\Services\AuditLogService;
use App\Models\Role;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;

/**
 * 角色列表元件
 * 
 * 提供角色的列表顯示、搜尋、篩選、分頁和批量操作功能
 */
class RoleList extends AdminComponent
{
    use WithPagination;

    // 搜尋和篩選屬性
    #[Url(as: 'search')]
    public string $search = '';
    
    #[Url(as: 'permission_filter')]
    public string $permissionCountFilter = 'all';
    
    #[Url(as: 'user_filter')]
    public string $userCountFilter = 'all';
    
    #[Url(as: 'system_filter')]
    public string $systemRoleFilter = 'all';
    
    #[Url(as: 'status_filter')]
    public string $statusFilter = 'all';

    // 分頁和排序屬性
    public int $perPage = 20;
    
    #[Url(as: 'sort')]
    public string $sortField = 'created_at';
    
    #[Url(as: 'direction')]
    public string $sortDirection = 'desc';

    // 批量操作屬性
    public array $selectedRoles = [];
    public bool $selectAll = false;
    public string $bulkAction = '';

    // 顯示控制屬性
    public bool $showFilters = false;
    public bool $showBulkActions = false;

    protected RoleRepositoryInterface $roleRepository;
    protected RoleCacheService $cacheService;
    protected RoleOptimizationService $optimizationService;
    protected RoleSecurityService $securityService;
    protected RoleDataValidationService $validationService;
    protected AuditLogService $auditLogService;

    /**
     * 元件初始化
     */
    public function boot(
        RoleRepositoryInterface $roleRepository,
        RoleCacheService $cacheService,
        RoleOptimizationService $optimizationService,
        RoleSecurityService $securityService,
        RoleDataValidationService $validationService,
        AuditLogService $auditLogService
    ): void {
        $this->roleRepository = $roleRepository;
        $this->cacheService = $cacheService;
        $this->optimizationService = $optimizationService;
        $this->securityService = $securityService;
        $this->validationService = $validationService;
        $this->auditLogService = $auditLogService;
    }

    /**
     * 元件掛載時檢查權限
     */
    public function mount(): void
    {
        $this->checkPermission('roles.view');
    }

    /**
     * 取得角色列表（計算屬性）
     */
    public function getRolesProperty(): LengthAwarePaginator
    {
        $filters = [
            'search' => $this->search,
            'permission_count_filter' => $this->permissionCountFilter,
            'user_count_filter' => $this->userCountFilter,
            'is_system_role' => $this->getSystemRoleFilterValue(),
            'is_active' => $this->getStatusFilterValue(),
            'sort_field' => $this->sortField,
            'sort_direction' => $this->sortDirection,
        ];

        return $this->roleRepository->getPaginatedRoles($filters, $this->perPage);
    }

    /**
     * 取得統計資訊（計算屬性）
     */
    public function getStatsProperty(): array
    {
        return $this->cacheService->getRoleStats();
    }

    /**
     * 取得篩選選項（計算屬性）
     */
    public function getFilterOptionsProperty(): array
    {
        return [
            'permission_count' => [
                'all' => __('admin.roles.filters.all_permissions'),
                'none' => __('admin.roles.filters.no_permissions'),
                'low' => __('admin.roles.filters.low_permissions', ['count' => '≤5']),
                'medium' => __('admin.roles.filters.medium_permissions', ['range' => '6-15']),
                'high' => __('admin.roles.filters.high_permissions', ['count' => '>15']),
            ],
            'user_count' => [
                'all' => __('admin.roles.filters.all_users'),
                'none' => __('admin.roles.filters.no_users'),
                'low' => __('admin.roles.filters.low_users', ['count' => '≤5']),
                'medium' => __('admin.roles.filters.medium_users', ['range' => '6-20']),
                'high' => __('admin.roles.filters.high_users', ['count' => '>20']),
            ],
            'system_role' => [
                'all' => __('admin.roles.filters.all_roles'),
                'system' => __('admin.roles.filters.system_roles'),
                'custom' => __('admin.roles.filters.custom_roles'),
            ],
            'status' => [
                'all' => __('admin.roles.filters.all_status'),
                'active' => __('admin.roles.filters.active'),
                'inactive' => __('admin.roles.filters.inactive'),
            ],
        ];
    }

    /**
     * 取得排序選項（計算屬性）
     */
    public function getSortOptionsProperty(): array
    {
        return [
            'name' => __('admin.roles.sort.name'),
            'display_name' => __('admin.roles.sort.display_name'),
            'created_at' => __('admin.roles.sort.created_at'),
            'updated_at' => __('admin.roles.sort.updated_at'),
            'users_count' => __('admin.roles.sort.users_count'),
            'permissions_count' => __('admin.roles.sort.permissions_count'),
        ];
    }

    /**
     * 取得批量操作選項（計算屬性）- 增強版本
     */
    public function getBulkActionsProperty(): array
    {
        $actions = [];
        $selectedCount = count($this->selectedRoles);
        
        // 檢查選中的角色類型
        $selectedRoles = Role::whereIn('id', $this->selectedRoles)->get();
        $hasSystemRoles = $selectedRoles->where('is_system_role', true)->count() > 0;
        $hasActiveRoles = $selectedRoles->where('is_active', true)->count() > 0;
        $hasInactiveRoles = $selectedRoles->where('is_active', false)->count() > 0;

        if ($this->can('roles.edit') && $selectedCount > 0) {
            // 只有在有非活躍角色時才顯示啟用選項
            if ($hasInactiveRoles) {
                $actions['activate'] = __('admin.roles.bulk_actions.activate') . " ({$selectedRoles->where('is_active', false)->count()})";
            }
            
            // 只有在有活躍的非系統角色時才顯示停用選項
            if ($hasActiveRoles && !$hasSystemRoles) {
                $actions['deactivate'] = __('admin.roles.bulk_actions.deactivate') . " ({$selectedRoles->where('is_active', true)->count()})";
            }
            
            // 權限批量設定
            $actions['permissions'] = __('admin.roles.bulk_actions.permissions') . " ({$selectedCount})";
            
            // 批量匯出
            $actions['export'] = __('admin.roles.bulk_actions.export') . " ({$selectedCount})";
        }

        if ($this->can('roles.delete') && $selectedCount > 0) {
            // 只有在沒有系統角色時才顯示刪除選項
            if (!$hasSystemRoles) {
                $actions['delete'] = __('admin.roles.bulk_actions.delete') . " ({$selectedCount})";
            }
        }

        return $actions;
    }

    /**
     * 搜尋功能 - 增強版本，包含效能優化和搜尋建議
     */
    public function updatedSearch(): void
    {
        // 記錄搜尋操作以進行效能分析
        if (!empty($this->search)) {
            \Log::info('🔍 Role search performed', [
                'search_term' => $this->search,
                'search_length' => strlen($this->search),
                'user' => auth()->user()->username ?? 'unknown',
                'timestamp' => now()->toISOString()
            ]);
        }

        $this->resetPage();
        
        // 清除之前的搜尋錯誤
        $this->resetErrorBag('search');
        
        // 如果搜尋詞太短，顯示提示
        if (!empty($this->search) && strlen($this->search) < 2) {
            $this->dispatch('search-hint', [
                'message' => '請輸入至少2個字元以獲得更好的搜尋結果'
            ]);
        }
        
        // 如果搜尋詞很長，可能需要優化
        if (strlen($this->search) > 50) {
            $this->dispatch('search-warning', [
                'message' => '搜尋詞過長，建議使用更簡潔的關鍵字'
            ]);
        }
    }

    /**
     * 篩選更新時重置分頁
     */
    public function updatedPermissionCountFilter(): void
    {
        $this->resetPage();
    }

    public function updatedUserCountFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSystemRoleFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    /**
     * 排序功能
     */
    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    /**
     * 重置所有篩選條件
     */
    public function resetFilters(): void
    {
        try {
            // 記錄篩選重置操作
            \Log::info('🔄 resetFilters - 篩選重置開始', [
                'timestamp' => now()->toISOString(),
                'user' => auth()->user()->username ?? 'unknown',
                'before_reset' => [
                    'search' => $this->search ?? '',
                    'permissionCountFilter' => $this->permissionCountFilter ?? 'all',
                    'userCountFilter' => $this->userCountFilter ?? 'all',
                    'systemRoleFilter' => $this->systemRoleFilter ?? 'all',
                    'statusFilter' => $this->statusFilter ?? 'all',
                ]
            ]);
            
            // 重置所有篩選條件
            $this->search = '';
            $this->permissionCountFilter = 'all';
            $this->userCountFilter = 'all';
            $this->systemRoleFilter = 'all';
            $this->statusFilter = 'all';
            $this->selectedRoles = [];
            $this->selectAll = false;
            $this->bulkAction = '';
            $this->showFilters = 'all';
            $this->showBulkActions = false;
            
            // 清除快取
            if (method_exists($this, 'clearCache')) {
                $this->clearCache();
            }
            
            // 重置分頁和驗證
            $this->resetPage();
            $this->resetValidation();
            
            // 強制重新渲染整個元件
            $this->skipRender = false;
            
            // 強制 Livewire 同步狀態到前端
            $this->js('
                // 強制更新所有表單元素的值
                setTimeout(() => {
                    const searchInputs = document.querySelectorAll(\'input[wire\\\\:model\\\\.live="search"]\');
                    searchInputs.forEach(input => {
                        input.value = "";
                        input.dispatchEvent(new Event("input", { bubbles: true }));
                    });
                    
                    const filterSelects = document.querySelectorAll(\'select[wire\\\\:model\\\\.live*="Filter"]\');
                    filterSelects.forEach(select => {
                        select.value = "all";
                        select.dispatchEvent(new Event("change", { bubbles: true }));
                    });
                    
                    console.log("✅ 角色管理表單元素已強制同步");
                }, 100);
            ');
            
            // 發送強制 UI 更新事件
            $this->dispatch('force-ui-update');
            
            // 發送前端重置事件，讓 Alpine.js 處理
            $this->dispatch('reset-form-elements');
            
            // 顯示成功訊息
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => '篩選條件已清除'
            ]);
            
            // 記錄重置完成
            \Log::info('✅ resetFilters - 篩選重置完成', [
                'after_reset' => [
                    'search' => $this->search,
                    'permissionCountFilter' => $this->permissionCountFilter,
                    'userCountFilter' => $this->userCountFilter,
                    'systemRoleFilter' => $this->systemRoleFilter,
                    'statusFilter' => $this->statusFilter,
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('重置方法執行失敗', [
                'method' => 'resetFilters',
                'error' => $e->getMessage(),
                'component' => static::class,
            ]);
            
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '重置操作失敗，請重試'
            ]);
        }}

    /**
     * 切換篩選器顯示
     */
    public function toggleFilters(): void
    {
        $this->showFilters = !$this->showFilters;
    }

    /**
     * 全選/取消全選
     */
    public function updatedSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selectedRoles = $this->roles->pluck('id')->toArray();
        } else {
            $this->selectedRoles = [];
        }
        
        $this->updateBulkActionsVisibility();
    }

    /**
     * 選擇項目更新時的處理
     */
    public function updatedSelectedRoles(): void
    {
        $this->selectAll = count($this->selectedRoles) === $this->roles->count();
        $this->updateBulkActionsVisibility();
    }

    /**
     * 更新批量操作顯示狀態
     */
    private function updateBulkActionsVisibility(): void
    {
        $this->showBulkActions = !empty($this->selectedRoles);
    }

    /**
     * 執行批量操作
     */
    public function executeBulkAction(): void
    {
        if (empty($this->bulkAction) || empty($this->selectedRoles)) {
            $this->addError('bulk_action', __('admin.roles.errors.no_action_selected'));
            return;
        }

        try {
            // 驗證批量操作資料
            $validatedData = $this->validationService->validateBulkOperationData([
                'role_ids' => $this->selectedRoles,
                'operation' => $this->bulkAction
            ]);

            // 執行多層級安全檢查
            foreach ($this->selectedRoles as $roleId) {
                $role = Role::find($roleId);
                if ($role) {
                    $securityCheck = $this->securityService->checkMultiLevelPermissions($this->bulkAction, $role);
                    if (!$securityCheck['allowed']) {
                        $this->addError('bulk_action', "角色 '{$role->display_name}': {$securityCheck['message']}");
                        return;
                    }
                }
            }

            $count = count($this->selectedRoles);
            
            switch ($this->bulkAction) {
                case 'activate':
                    $this->checkPermission('roles.edit');
                    $this->bulkActivateOptimized();
                    $this->auditLogService->logBulkOperation('activate_roles', $this->selectedRoles, ['count' => $count]);
                    $this->dispatch('role-bulk-updated', 
                        message: __('admin.roles.messages.bulk_activated', ['count' => $count])
                    );
                    break;

                case 'deactivate':
                    $this->checkPermission('roles.edit');
                    $this->bulkDeactivateOptimized();
                    $this->auditLogService->logBulkOperation('deactivate_roles', $this->selectedRoles, ['count' => $count]);
                    $this->dispatch('role-bulk-updated', 
                        message: __('admin.roles.messages.bulk_deactivated', ['count' => $count])
                    );
                    break;

                case 'permissions':
                    $this->checkPermission('roles.edit');
                    $this->dispatch('open-bulk-permission-modal', roleIds: $this->selectedRoles);
                    return; // 不重置選擇，保持模態開啟狀態

                case 'delete':
                    $this->checkPermission('roles.delete');
                    $this->dispatch('confirm-bulk-delete', roleIds: $this->selectedRoles);
                    return; // 不重置選擇，等待確認

                default:
                    $this->addError('bulk_action', __('admin.roles.errors.invalid_action'));
                    return;
            }

            $this->resetBulkSelection();

        } catch (\Illuminate\Validation\ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError('bulk_action', $message);
                }
            }
        } catch (\Exception $e) {
            $this->addError('bulk_action', $e->getMessage());
            $this->auditLogService->logSecurityEvent('bulk_operation_failed', 'medium', [
                'operation' => $this->bulkAction,
                'role_ids' => $this->selectedRoles,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 批量啟用角色（優化版）
     */
    private function bulkActivateOptimized(): void
    {
        // 使用批量更新，提升效能
        $updatedCount = Role::whereIn('id', $this->selectedRoles)
                           ->where('is_system_role', false)
                           ->update(['is_active' => true]);

        // 清除相關快取
        foreach ($this->selectedRoles as $roleId) {
            $this->cacheService->clearRoleCache($roleId);
        }
    }

    /**
     * 批量停用角色（優化版）
     */
    private function bulkDeactivateOptimized(): void
    {
        // 使用批量更新，提升效能
        $updatedCount = Role::whereIn('id', $this->selectedRoles)
                           ->where('is_system_role', false)
                           ->update(['is_active' => false]);

        // 清除相關快取
        foreach ($this->selectedRoles as $roleId) {
            $this->cacheService->clearRoleCache($roleId);
        }
    }

    /**
     * 確認批量刪除
     */
    #[On('confirmed-bulk-delete')]
    public function confirmedBulkDelete(): void
    {
        try {
            $deletedCount = 0;
            $errors = [];
            $deletedRoles = [];

            foreach ($this->selectedRoles as $roleId) {
                $role = Role::find($roleId);
                if (!$role) {
                    continue;
                }

                // 執行安全檢查
                $securityCheck = $this->securityService->checkRoleDeletionSecurity($role);
                if (!$securityCheck['can_delete']) {
                    $errors[] = __('admin.roles.errors.cannot_delete_role', ['name' => $role->display_name]);
                    continue;
                }

                try {
                    // 使用安全刪除方法
                    $deleteResult = $this->securityService->secureRoleDelete($role, false);
                    if ($deleteResult['success']) {
                        $deletedCount++;
                        $deletedRoles[] = $role->display_name;
                    }
                } catch (\Exception $e) {
                    $errors[] = "刪除角色 '{$role->display_name}' 失敗: {$e->getMessage()}";
                }
            }

            if ($deletedCount > 0) {
                $this->auditLogService->logBulkOperation('delete_roles', $this->selectedRoles, [
                    'deleted_count' => $deletedCount,
                    'deleted_roles' => $deletedRoles,
                    'error_count' => count($errors)
                ]);

                $this->dispatch('role-bulk-deleted', 
                    message: __('admin.roles.messages.bulk_deleted', ['count' => $deletedCount])
                );
            }

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addError('bulk_delete', $error);
                }
            }

            $this->resetBulkSelection();

        } catch (\Exception $e) {
            $this->addError('bulk_delete', $e->getMessage());
            $this->auditLogService->logSecurityEvent('bulk_delete_failed', 'high', [
                'role_ids' => $this->selectedRoles,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 重置批量選擇
     */
    private function resetBulkSelection(): void
    {
        $this->selectedRoles = [];
        $this->selectAll = false;
        $this->bulkAction = '';
        $this->showBulkActions = false;
    }

    /**
     * 建立角色
     */
    public function createRole()
    {
        $this->checkPermission('roles.create');
        return $this->redirect(route('admin.roles.create'));
    }

    /**
     * 編輯角色
     */
    public function editRole(int $roleId)
    {
        $this->checkPermission('roles.edit');
        return $this->redirect(route('admin.roles.edit', $roleId));
    }

    /**
     * 檢視角色
     */
    public function viewRole(int $roleId): void
    {
        $this->checkPermission('roles.view');
        // TODO: 實作角色檢視頁面後啟用
        // $this->redirect(route('admin.roles.show', $roleId));
        $this->dispatch('role-view-requested', roleId: $roleId);
    }

    /**
     * 複製角色
     */
    public function duplicateRole(int $roleId): void
    {
        $this->checkPermission('roles.create');
        
        try {
            $role = $this->roleRepository->findOrFail($roleId);
            $newName = $role->name . '_copy_' . time();
            
            $duplicatedRole = $this->roleRepository->duplicateRole($role, $newName);
            
            $this->dispatch('role-duplicated', 
                message: __('admin.roles.messages.duplicated', ['name' => $duplicatedRole->display_name])
            );
            
            $this->redirect(route('admin.roles.edit', $duplicatedRole->id));
            
        } catch (\Exception $e) {
            $this->addError('duplicate', $e->getMessage());
        }
    }

    /**
     * 刪除角色
     */
    public function deleteRole(int $roleId): void
    {
        $this->checkPermission('roles.delete');
        $this->dispatch('confirm-delete', roleId: $roleId);
    }

    /**
     * 確認刪除角色
     */
    #[On('confirmed-delete')]
    public function confirmedDelete(int $roleId): void
    {
        try {
            $role = $this->roleRepository->findOrFail($roleId);
            
            if (!$this->roleRepository->canDeleteRole($role)) {
                $this->addError('delete', __('admin.roles.errors.cannot_delete_role', ['name' => $role->display_name]));
                return;
            }

            $roleName = $role->display_name;
            $this->roleRepository->delete($role);
            
            $this->dispatch('role-deleted', 
                message: __('admin.roles.messages.deleted', ['name' => $roleName])
            );

        } catch (\Exception $e) {
            $this->addError('delete', $e->getMessage());
        }
    }

    /**
     * 批量權限更新完成處理
     */
    #[On('bulk-permissions-updated')]
    public function handleBulkPermissionsUpdated(array $data): void
    {
        $successCount = $data['success_count'] ?? 0;
        $errorCount = $data['error_count'] ?? 0;
        $operationType = $data['operation_type'] ?? 'add';

        if ($successCount > 0) {
            $message = match ($operationType) {
                'add' => __('admin.roles.messages.bulk_permissions_added', ['count' => $successCount]),
                'remove' => __('admin.roles.messages.bulk_permissions_removed', ['count' => $successCount]),
                'replace' => __('admin.roles.messages.bulk_permissions_replaced', ['count' => $successCount]),
                default => __('admin.roles.messages.bulk_permissions_updated', ['count' => $successCount])
            };

            $this->dispatch('role-bulk-updated', message: $message);
        }

        if ($errorCount > 0) {
            $this->addError('bulk_permissions', __('admin.roles.errors.bulk_permissions_partial_failure', [
                'success' => $successCount,
                'failed' => $errorCount
            ]));
        }

        // 重置批量選擇
        $this->resetBulkSelection();
    }

    /**
     * 切換角色狀態
     */
    public function toggleRoleStatus(int $roleId): void
    {
        $this->checkPermission('roles.edit');
        
        try {
            $role = $this->roleRepository->findOrFail($roleId);
            
            if ($role->is_system_role) {
                $this->addError('toggle_status', __('admin.roles.errors.cannot_modify_system_role'));
                return;
            }

            $role->update(['is_active' => !$role->is_active]);
            
            $status = $role->is_active ? __('admin.roles.status.activated') : __('admin.roles.status.deactivated');
            $this->dispatch('role-status-changed', 
                message: __('admin.roles.messages.status_changed', ['name' => $role->display_name, 'status' => $status])
            );

        } catch (\Exception $e) {
            $this->addError('toggle_status', $e->getMessage());
        }
    }

    /**
     * 管理角色權限
     */
    public function managePermissions(int $roleId): void
    {
        $this->checkPermission('roles.edit');
        // TODO: 實作權限管理頁面後啟用
        // $this->redirect(route('admin.roles.permissions', $roleId));
        $this->dispatch('role-permissions-requested', roleId: $roleId);
    }

    /**
     * 檢視角色使用者
     */
    public function viewUsers(int $roleId): void
    {
        $this->checkPermission('users.view');
        // TODO: 實作使用者列表頁面後啟用
        // $this->redirect(route('admin.users.index', ['role' => $roleId]));
        $this->dispatch('role-users-requested', roleId: $roleId);
    }

    /**
     * 轉換系統角色篩選值
     */
    private function getSystemRoleFilterValue(): ?bool
    {
        return match ($this->systemRoleFilter) {
            'system' => true,
            'custom' => false,
            default => null,
        };
    }

    /**
     * 轉換狀態篩選值
     */
    private function getStatusFilterValue(): ?bool
    {
        return match ($this->statusFilter) {
            'active' => true,
            'inactive' => false,
            default => null,
        };
    }

    /**
     * 取得排序圖示
     */
    public function getSortIcon(string $field): string
    {
        if ($this->sortField !== $field) {
            return 'heroicon-m-bars-3-bottom-left';
        }

        return $this->sortDirection === 'asc' 
            ? 'heroicon-m-bars-arrow-up' 
            : 'heroicon-m-bars-arrow-down';
    }

    /**
     * 檢查是否可以執行批量操作
     */
    public function canExecuteBulkAction(string $action): bool
    {
        if (empty($this->selectedRoles)) {
            return false;
        }

        return match ($action) {
            'activate', 'deactivate', 'permissions' => $this->can('roles.edit'),
            'delete' => $this->can('roles.delete'),
            default => false,
        };
    }

    /**
     * 取得選中角色的資訊
     */
    public function getSelectedRolesInfoProperty(): array
    {
        if (empty($this->selectedRoles)) {
            return [];
        }

        return Role::whereIn('id', $this->selectedRoles)
                  ->select('id', 'name', 'display_name', 'is_system_role')
                  ->get()
                  ->toArray();
    }

    /**
     * 渲染元件
     */
    
    /**
     * roleFilter 更新時重置分頁
     */
    public function updatedRoleFilter(): void
    {
        $this->resetPage();
    }


    public function render()
    {
        return view('livewire.admin.roles.role-list')
            ->layout('layouts.admin');
    }
}