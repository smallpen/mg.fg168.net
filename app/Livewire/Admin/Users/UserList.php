<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use App\Models\Role;
use App\Repositories\UserRepository;
use App\Services\PermissionService;
use App\Services\InputValidationService;
use App\Services\AuditLogService;
use App\Services\UserCacheService;
use App\Traits\HandlesLivewireErrors;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Client\ConnectionException;

/**
 * 使用者列表 Livewire 元件
 * 
 * 提供使用者管理的主要介面，包含搜尋、篩選、分頁、排序和批量操作功能
 */
class UserList extends Component
{
    use WithPagination, HandlesLivewireErrors;

    // 搜尋相關屬性
    public string $search = '';
    
    // 篩選相關屬性
    public string $statusFilter = 'all';
    public string $roleFilter = 'all';
    
    // 分頁相關屬性
    public int $perPage = 15;
    
    // 排序相關屬性
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';
    
    // 批量操作相關屬性
    public array $selectedUsers = [];
    public bool $selectAll = false;

    protected UserRepository $userRepository;
    protected UserCacheService $cacheService;
    protected PermissionService $permissionService;
    protected InputValidationService $validationService;
    protected AuditLogService $auditService;

    /**
     * 元件初始化
     */
    public function boot(
        UserRepository $userRepository, 
        UserCacheService $cacheService,
        PermissionService $permissionService,
        InputValidationService $validationService,
        AuditLogService $auditService
    ): void {
        $this->userRepository = $userRepository;
        $this->cacheService = $cacheService;
        $this->permissionService = $permissionService;
        $this->validationService = $validationService;
        $this->auditService = $auditService;
    }

    /**
     * 元件掛載時執行權限檢查
     */
    public function mount(): void
    {
        // 使用新的錯誤處理機制檢查權限
        if (!$this->checkPermissionOrFail('users.view', 'mount_user_list', [
            'component' => 'UserList',
            'action' => 'mount',
        ])) {
            // 權限檢查失敗時，HandlesLivewireErrors trait 會處理錯誤顯示
            return;
        }

        // 記錄存取日誌
        $this->auditService->logDataAccess('users', 'list_view');
    }

    /**
     * 取得篩選後的使用者資料（計算屬性）
     */
    public function getUsersProperty(): LengthAwarePaginator
    {
        return $this->safeExecute(function () {
            // 驗證和清理篩選條件
            $filters = $this->validationService->validateFilters([
                'search' => $this->search,
                'status' => $this->statusFilter,
                'role' => $this->roleFilter,
                'sort_field' => $this->sortField,
                'sort_direction' => $this->sortDirection,
            ]);

            return $this->userRepository->getPaginatedUsers($filters, $this->perPage);
        }, 'get_users', [
            'filters' => [
                'search' => $this->search,
                'status' => $this->statusFilter,
                'role' => $this->roleFilter,
            ],
        ]) ?? $this->userRepository->getPaginatedUsers([], $this->perPage);
    }

    /**
     * 延遲載入使用者資料
     * 用於初始頁面載入時的效能優化
     */
    public function loadUsers(): void
    {
        // 這個方法會觸發 getUsersProperty 的執行
        $this->users;
    }

    /**
     * 檢查是否應該延遲載入
     */
    public function shouldLazyLoad(): bool
    {
        // 如果有搜尋或篩選條件，不使用延遲載入
        return empty($this->search) && 
               $this->statusFilter === 'all' && 
               $this->roleFilter === 'all';
    }

    /**
     * 取得可用的角色選項（計算屬性）
     */
    public function getAvailableRolesProperty(): Collection
    {
        return Cache::remember('user_roles_list', 3600, function () {
            return Role::select('id', 'name', 'display_name')
                      ->orderBy('display_name')
                      ->get();
        });
    }

    /**
     * 取得狀態篩選選項（計算屬性）
     */
    public function getStatusOptionsProperty(): array
    {
        return [
            'all' => __('admin.users.all_status'),
            'active' => __('admin.users.active'),
            'inactive' => __('admin.users.inactive'),
        ];
    }

    /**
     * 取得本地化的使用者狀態
     */
    public function getLocalizedStatus(bool $isActive): string
    {
        return $isActive ? __('admin.users.active') : __('admin.users.inactive');
    }

    /**
     * 取得格式化的日期時間
     */
    public function getFormattedDateTime($datetime, string $format = 'default'): string
    {
        return \App\Helpers\DateTimeHelper::format($datetime, $format);
    }

    /**
     * 取得使用者的本地化角色顯示
     */
    public function getLocalizedUserRoles(User $user): string
    {
        return $user->role_count_display;
    }

    /**
     * 取得使用者狀態的本地化顯示（包含圖示）
     */
    public function getStatusBadge(bool $isActive): array
    {
        return $isActive ? [
            'text' => __('admin.users.active'),
            'class' => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
        ] : [
            'text' => __('admin.users.inactive'),
            'class' => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
        ];
    }



    /**
     * 搜尋條件更新時重置分頁並清除快取
     */
    public function updatedSearch(): void
    {
        try {
            // 驗證搜尋輸入
            if (!empty($this->search)) {
                $this->search = $this->validationService->validateSearchInput($this->search);
                
                // 檢查是否包含惡意內容
                if ($this->validationService->containsMaliciousContent($this->search)) {
                    $this->auditService->logSecurityEvent('malicious_search_input', 'high', [
                        'search_input' => $this->search,
                    ]);
                    
                    $this->search = '';
                    $this->dispatch('show-toast', [
                        'type' => 'error',
                        'message' => '搜尋條件包含無效內容'
                    ]);
                    return;
                }
            }
            
            $this->resetPage();
            
            // 如果搜尋條件為空，清除查詢快取以確保資料一致性
            if (empty($this->search)) {
                $this->cacheService->clearQueries();
            }
        } catch (ValidationException $e) {
            $this->search = '';
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '搜尋條件格式錯誤'
            ]);
        }
    }

    /**
     * 狀態篩選更新時重置分頁
     */
    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    /**
     * 角色篩選更新時重置分頁
     */
    public function updatedRoleFilter(): void
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
     * 全選切換功能
     */
    public function toggleSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selectedUsers = $this->users->pluck('id')->toArray();
        } else {
            $this->selectedUsers = [];
        }
    }

    /**
     * 單個使用者選擇切換
     */
    public function toggleUserSelection(int $userId): void
    {
        if (in_array($userId, $this->selectedUsers)) {
            $this->selectedUsers = array_diff($this->selectedUsers, [$userId]);
        } else {
            $this->selectedUsers[] = $userId;
        }

        // 更新全選狀態
        $this->selectAll = count($this->selectedUsers) === $this->users->count();
    }

    /**
     * 檢視使用者
     */
    public function viewUser(int $userId): void
    {
        try {
            // 驗證使用者 ID
            $userId = $this->validationService->validateUserId($userId);
            
            // 檢查權限
            if (!$this->permissionService->hasPermission('users.view')) {
                $this->permissionService->logPermissionDenied('users.view', 'view_user');
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => __('admin.users.no_permission_view')
                ]);
                return;
            }

            $targetUser = User::find($userId);
            if (!$targetUser) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => __('admin.users.user_not_found')
                ]);
                return;
            }

            // 記錄操作日誌
            $this->auditService->logUserManagementAction('user_view', [
                'target_user_id' => $userId,
            ], $targetUser);
            
            $this->redirect(route('admin.users.show', $userId));
        } catch (ValidationException $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '無效的使用者 ID'
            ]);
        }
    }

    /**
     * 編輯使用者
     */
    public function editUser(int $userId): void
    {
        try {
            // 驗證使用者 ID
            $userId = $this->validationService->validateUserId($userId);
            
            $targetUser = User::find($userId);
            if (!$targetUser) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => __('admin.users.user_not_found')
                ]);
                return;
            }

            // 檢查權限
            if (!$this->permissionService->canPerformActionOnUser('users.edit', $targetUser)) {
                $this->permissionService->logPermissionDenied('users.edit', 'edit_user');
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => __('admin.users.no_permission_edit')
                ]);
                return;
            }

            // 記錄操作日誌
            $this->auditService->logUserManagementAction('user_edit_access', [
                'target_user_id' => $userId,
            ], $targetUser);
            
            $this->redirect(route('admin.users.edit', $userId));
        } catch (ValidationException $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '無效的使用者 ID'
            ]);
        }
    }

    /**
     * 切換使用者狀態
     */
    public function toggleUserStatus(int $userId): void
    {
        $this->executeWithPermission('users.edit', function () use ($userId) {
            // 驗證使用者 ID
            $userId = $this->validationService->validateUserId($userId);
            
            $user = User::find($userId);
            if (!$user) {
                throw new \InvalidArgumentException('使用者不存在');
            }

            // 檢查是否可以對此使用者執行操作
            if (!$this->permissionService->canPerformActionOnUser('users.edit', $user)) {
                throw new AuthorizationException('無權限編輯此使用者');
            }

            $oldStatus = $user->is_active;
            $success = $this->userRepository->toggleUserStatus($userId);
            
            if (!$success) {
                throw new \RuntimeException('狀態切換失敗');
            }

            $user->refresh();
            $newStatus = $user->is_active;
            
            // 記錄操作日誌
            $this->auditService->logUserManagementAction('user_status_toggle', [
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'action' => $newStatus ? 'activated' : 'deactivated',
            ], $user);
            
            $message = $user->is_active 
                ? __('admin.users.user_activated')
                : __('admin.users.user_deactivated');
            
            $this->showSuccessMessage($message);
            $this->dispatch('user-status-updated', userId: $userId);
            
            // 清除相關快取
            $this->clearUserCaches();

            return true;
        }, 'toggle_user_status', [
            'user_id' => $userId,
        ]);
    }

    /**
     * 刪除使用者
     */
    public function deleteUser(int $userId): void
    {
        try {
            // 驗證使用者 ID
            $userId = $this->validationService->validateUserId($userId);
            
            $user = User::find($userId);
            if (!$user) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => __('admin.users.user_not_found')
                ]);
                return;
            }

            // 檢查權限
            if (!$this->permissionService->canPerformActionOnUser('users.delete', $user)) {
                $this->permissionService->logPermissionDenied('users.delete', 'delete_user');
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => __('admin.users.no_permission_delete')
                ]);
                return;
            }

            // 記錄刪除嘗試
            $this->auditService->logUserManagementAction('user_delete_attempt', [
                'target_user_id' => $userId,
            ], $user);
            
            $this->dispatch('confirm-user-delete', userId: $userId);
        } catch (ValidationException $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '無效的使用者 ID'
            ]);
        }
    }

    /**
     * 確認刪除使用者
     */
    #[On('user-delete-confirmed')]
    public function confirmDelete(int $userId): void
    {
        $user = User::find($userId);
        
        if (!$user) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => __('admin.users.user_not_found')
            ]);
            return;
        }

        if (!auth()->user()->hasPermission('users.delete')) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => __('admin.users.no_permission_delete')
            ]);
            return;
        }

        try {
            $success = $this->userRepository->softDeleteUser($userId);
            
            if ($success) {
                $this->dispatch('show-toast', [
                    'type' => 'success',
                    'message' => __('admin.users.user_deleted_permanently', ['username' => $user->username])
                ]);

                // 清除相關快取
                $this->clearUserCaches();
                
                // 重新載入頁面資料
                $this->resetPage();
            } else {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => __('admin.users.delete_failed')
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => __('admin.users.delete_failed')
            ]);
        }
    }

    /**
     * 批量啟用使用者
     */
    public function bulkActivate(): void
    {
        try {
            // 驗證選中的使用者 ID
            $userIds = $this->validationService->validateUserIds($this->selectedUsers);
            
            // 檢查權限
            if (!$this->permissionService->hasPermission('users.edit')) {
                $this->permissionService->logPermissionDenied('users.edit', 'bulk_activate');
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => __('admin.users.no_permission_edit')
                ]);
                return;
            }

            $count = $this->userRepository->bulkUpdateStatus($userIds, true);
            
            // 記錄批量操作
            $this->auditService->logBulkOperation('activate_users', $userIds, [
                'affected_count' => $count,
                'status' => 'success',
            ]);
            
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => __('admin.users.bulk_activate_success', ['count' => $count])
            ]);

            $this->dispatch('users-bulk-updated');
            $this->selectedUsers = [];
            $this->selectAll = false;
            
            // 清除相關快取
            $this->clearUserCaches();
        } catch (ValidationException $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '選中的使用者 ID 無效'
            ]);
        } catch (\Exception $e) {
            $this->auditService->logSecurityEvent('bulk_activate_failed', 'medium', [
                'selected_users' => $this->selectedUsers,
                'error' => $e->getMessage(),
            ]);
            
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => __('admin.users.bulk_operation_failed')
            ]);
        }
    }

    /**
     * 批量停用使用者
     */
    public function bulkDeactivate(): void
    {
        try {
            // 驗證選中的使用者 ID
            $userIds = $this->validationService->validateUserIds($this->selectedUsers);
            
            // 檢查權限
            if (!$this->permissionService->hasPermission('users.edit')) {
                $this->permissionService->logPermissionDenied('users.edit', 'bulk_deactivate');
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => __('admin.users.no_permission_edit')
                ]);
                return;
            }

            // 檢查是否包含當前使用者
            if (in_array(auth()->id(), $userIds)) {
                $this->auditService->logSecurityEvent('attempt_self_deactivate', 'medium', [
                    'selected_users' => $userIds,
                ]);
                
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => __('admin.users.cannot_disable_self')
                ]);
                return;
            }

            // 檢查是否包含超級管理員（非超級管理員不能停用超級管理員）
            if (!auth()->user()->isSuperAdmin()) {
                $superAdminIds = User::whereIn('id', $userIds)
                    ->whereHas('roles', function ($query) {
                        $query->where('name', 'super_admin');
                    })
                    ->pluck('id')
                    ->toArray();

                if (!empty($superAdminIds)) {
                    $this->auditService->logSecurityEvent('attempt_deactivate_super_admin', 'high', [
                        'selected_users' => $userIds,
                        'super_admin_ids' => $superAdminIds,
                    ]);
                    
                    $this->dispatch('show-toast', [
                        'type' => 'error',
                        'message' => __('admin.users.cannot_bulk_deactivate_super_admin')
                    ]);
                    return;
                }
            }

            $count = $this->userRepository->bulkUpdateStatus($userIds, false);
            
            // 記錄批量操作
            $this->auditService->logBulkOperation('deactivate_users', $userIds, [
                'affected_count' => $count,
                'status' => 'success',
            ]);
            
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => __('admin.users.bulk_deactivate_success', ['count' => $count])
            ]);

            $this->dispatch('users-bulk-updated');
            $this->selectedUsers = [];
            $this->selectAll = false;
            
            // 清除相關快取
            $this->clearUserCaches();
        } catch (ValidationException $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '選中的使用者 ID 無效'
            ]);
        } catch (\Exception $e) {
            $this->auditService->logSecurityEvent('bulk_deactivate_failed', 'medium', [
                'selected_users' => $this->selectedUsers,
                'error' => $e->getMessage(),
            ]);
            
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => __('admin.users.bulk_operation_failed')
            ]);
        }
    }

    /**
     * 匯出使用者資料
     */
    public function exportUsers(): void
    {
        // 檢查權限
        if (!$this->permissionService->hasPermission('users.export')) {
            $this->permissionService->logPermissionDenied('users.export', 'export_users');
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '您沒有匯出使用者資料的權限'
            ]);
            return;
        }

        // 記錄匯出操作
        $this->auditService->logDataAccess('users', 'export', [
            'filters' => [
                'search' => $this->search,
                'status' => $this->statusFilter,
                'role' => $this->roleFilter,
            ],
        ]);
        
        $this->dispatch('export-started');
        
        // 這裡可以實作匯出邏輯
        // 例如：生成 CSV 或 Excel 檔案
    }

    /**
     * 重置所有篩選條件
     */
    public function resetFilters(): void
    {
        $this->search = '';
        $this->statusFilter = 'all';
        $this->roleFilter = 'all';
        $this->sortField = 'created_at';
        $this->sortDirection = 'desc';
        $this->selectedUsers = [];
        $this->selectAll = false;
        $this->resetPage();
    }

    /**
     * 檢查使用者是否擁有特定權限
     */
    public function hasPermission(string $permission): bool
    {
        return auth()->user()->hasPermission("users.{$permission}");
    }

    /**
     * 清除使用者相關快取
     */
    private function clearUserCaches(): void
    {
        $this->cacheService->clearAll();
    }

    /**
     * 格式化使用者建立時間
     */
    public function formatUserCreatedAt(User $user): string
    {
        return $user->formatted_created_at;
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.users.user-list', [
            'users' => $this->users,
            'availableRoles' => $this->availableRoles,
            'statusOptions' => $this->statusOptions,
        ]);
    }
}