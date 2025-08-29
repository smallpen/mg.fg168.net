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
    public bool $showFilters = false;
    
    // 分頁相關屬性
    public int $perPage = 15;
    
    // 排序相關屬性
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';
    
    // 批量操作相關屬性
    public array $selectedUsers = [];
    public bool $selectAll = false;

    /**
     * 取得 UserRepository 實例
     */
    protected function getUserRepository(): UserRepository
    {
        return app(UserRepository::class);
    }

    /**
     * 取得 UserCacheService 實例
     */
    protected function getCacheService(): UserCacheService
    {
        return app(UserCacheService::class);
    }

    /**
     * 取得 PermissionService 實例
     */
    protected function getPermissionService(): PermissionService
    {
        return app(PermissionService::class);
    }

    /**
     * 取得 InputValidationService 實例
     */
    protected function getValidationService(): InputValidationService
    {
        return app(InputValidationService::class);
    }

    /**
     * 取得 AuditLogService 實例
     */
    protected function getAuditService(): AuditLogService
    {
        return app(AuditLogService::class);
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
        $this->getAuditService()->logDataAccess('users', 'list_view');
    }

    /**
     * 取得篩選後的使用者資料（計算屬性）
     */
    public function getUsersProperty(): LengthAwarePaginator
    {
        return $this->safeExecute(function () {
            // 驗證和清理篩選條件
            $filters = $this->getValidationService()->validateFilters([
                'search' => $this->search,
                'status' => $this->statusFilter,
                'role' => $this->roleFilter,
                'sort_field' => $this->sortField,
                'sort_direction' => $this->sortDirection,
            ]);

            $users = $this->getUserRepository()->getPaginatedUsers($filters, $this->perPage);
            
            // 確保每個使用者都有唯一的 ID 和穩定的屬性
            $users->getCollection()->transform(function ($user) {
                // 確保使用者物件有所有必要的屬性
                if (!isset($user->formatted_created_at)) {
                    $user->formatted_created_at = $user->created_at ? $user->created_at->format('Y-m-d H:i') : '';
                }
                
                if (!isset($user->avatar_url)) {
                    $user->avatar_url = '/images/default-avatar.png';
                }
                
                if (!isset($user->display_name)) {
                    $user->display_name = $user->name ?: $user->username;
                }
                
                return $user;
            });
            
            return $users;
        }, 'get_users', [
            'filters' => [
                'search' => $this->search,
                'status' => $this->statusFilter,
                'role' => $this->roleFilter,
            ],
        ]) ?? $this->getUserRepository()->getPaginatedUsers([], $this->perPage);
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
        if (!$datetime) {
            return '';
        }
        
        switch ($format) {
            case 'relative':
                return \App\Helpers\DateTimeHelper::formatRelative($datetime);
            case 'date_only':
                return \App\Helpers\DateTimeHelper::formatDate($datetime);
            case 'time_only':
                return \App\Helpers\DateTimeHelper::formatTime($datetime);
            default:
                return \App\Helpers\DateTimeHelper::formatDateTime($datetime);
        }
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
                $this->search = $this->getValidationService()->validateSearchInput($this->search);
                
                // 檢查是否包含惡意內容
                if ($this->getValidationService()->containsMaliciousContent($this->search)) {
                    $this->getAuditService()->logSecurityEvent('malicious_search_input', 'high', [
                        'search_input' => $this->search,
                    ]);
                    
                    $this->search = '';
                    $this->dispatch('show-toast', [
                        'type' => 'error',
                        'message' => __('admin.users.invalid_search_content')
                    ]);
                    return;
                }
            }
            
            $this->resetPage();
            
            // 如果搜尋條件為空，清除查詢快取以確保資料一致性
            if (empty($this->search)) {
                $this->getCacheService()->clearQueries();
            }
        } catch (ValidationException $e) {
            $this->search = '';
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => __('admin.users.search_format_error')
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
            $userId = $this->getValidationService()->validateUserId($userId);
            
            // 檢查權限
            if (!$this->getPermissionService()->hasPermission('users.view')) {
                $this->getPermissionService()->logPermissionDenied('users.view', 'view_user');
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
            $this->getAuditService()->logUserManagementAction('user_view', [
                'target_user_id' => $userId,
            ], $targetUser);
            
            $this->redirect(route('admin.users.show', $userId));
        } catch (ValidationException $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => __('admin.users.invalid_user_id')
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
            $userId = $this->getValidationService()->validateUserId($userId);
            
            $targetUser = User::find($userId);
            if (!$targetUser) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => __('admin.users.user_not_found')
                ]);
                return;
            }

            // 檢查權限
            if (!$this->getPermissionService()->canPerformActionOnUser('users.edit', $targetUser)) {
                $this->getPermissionService()->logPermissionDenied('users.edit', 'edit_user');
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => __('admin.users.no_permission_edit')
                ]);
                return;
            }

            // 記錄操作日誌
            $this->getAuditService()->logUserManagementAction('user_edit_access', [
                'target_user_id' => $userId,
            ], $targetUser);
            
            $this->redirect(route('admin.users.edit', $userId));
        } catch (ValidationException $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => __('admin.users.invalid_user_id')
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
            $userId = $this->getValidationService()->validateUserId($userId);
            
            $user = User::find($userId);
            if (!$user) {
                throw new \InvalidArgumentException(__('admin.users.user_not_exists'));
            }

            // 檢查是否可以對此使用者執行操作
            if (!$this->getPermissionService()->canPerformActionOnUser('users.edit', $user)) {
                throw new AuthorizationException('無權限編輯此使用者');
            }

            $oldStatus = $user->is_active;
            $success = $this->getUserRepository()->toggleUserStatus($userId);
            
            if (!$success) {
                throw new \RuntimeException(__('admin.users.status_toggle_failed'));
            }

            $user->refresh();
            $newStatus = $user->is_active;
            
            // 記錄操作日誌
            $this->getAuditService()->logUserManagementAction('user_status_toggle', [
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
            $userId = $this->getValidationService()->validateUserId($userId);
            
            $user = User::find($userId);
            if (!$user) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => __('admin.users.user_not_found')
                ]);
                return;
            }

            // 檢查權限
            if (!$this->getPermissionService()->canPerformActionOnUser('users.delete', $user)) {
                $this->getPermissionService()->logPermissionDenied('users.delete', 'delete_user');
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => __('admin.users.no_permission_delete')
                ]);
                return;
            }

            // 記錄刪除嘗試
            $this->getAuditService()->logUserManagementAction('user_delete_attempt', [
                'target_user_id' => $userId,
            ], $user);
            
            $this->dispatch('confirm-user-delete', userId: $userId);
        } catch (ValidationException $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => __('admin.users.invalid_user_id')
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
            $success = $this->getUserRepository()->softDeleteUser($userId);
            
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
            $userIds = $this->getValidationService()->validateUserIds($this->selectedUsers);
            
            // 檢查權限
            if (!$this->getPermissionService()->hasPermission('users.edit')) {
                $this->getPermissionService()->logPermissionDenied('users.edit', 'bulk_activate');
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => __('admin.users.no_permission_edit')
                ]);
                return;
            }

            $count = $this->getUserRepository()->bulkUpdateStatus($userIds, true);
            
            // 記錄批量操作
            $this->getAuditService()->logBulkOperation('activate_users', $userIds, [
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
                'message' => __('admin.users.invalid_user_ids')
            ]);
        } catch (\Exception $e) {
            $this->getAuditService()->logSecurityEvent('bulk_activate_failed', 'medium', [
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
            $userIds = $this->getValidationService()->validateUserIds($this->selectedUsers);
            
            // 檢查權限
            if (!$this->getPermissionService()->hasPermission('users.edit')) {
                $this->getPermissionService()->logPermissionDenied('users.edit', 'bulk_deactivate');
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => __('admin.users.no_permission_edit')
                ]);
                return;
            }

            // 檢查是否包含當前使用者
            if (in_array(auth()->id(), $userIds)) {
                $this->getAuditService()->logSecurityEvent('attempt_self_deactivate', 'medium', [
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
                    $this->getAuditService()->logSecurityEvent('attempt_deactivate_super_admin', 'high', [
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

            $count = $this->getUserRepository()->bulkUpdateStatus($userIds, false);
            
            // 記錄批量操作
            $this->getAuditService()->logBulkOperation('deactivate_users', $userIds, [
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
                'message' => __('admin.users.invalid_user_ids')
            ]);
        } catch (\Exception $e) {
            $this->getAuditService()->logSecurityEvent('bulk_deactivate_failed', 'medium', [
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
        if (!$this->getPermissionService()->hasPermission('users.export')) {
            $this->getPermissionService()->logPermissionDenied('users.export', 'export_users');
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => __('admin.users.no_permission_export')
            ]);
            return;
        }

        // 記錄匯出操作
        $this->getAuditService()->logDataAccess('users', 'export', [
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
     * 切換篩選器顯示狀態
     */
    public function toggleFilters(): void
    {
        $this->showFilters = !$this->showFilters;
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
                    'statusFilter' => $this->statusFilter ?? 'all',
                    'roleFilter' => $this->roleFilter ?? 'all',
                ]
            ]);
            
            // 重置所有篩選條件
            $this->search = '';
            $this->statusFilter = 'all';
            $this->roleFilter = 'all';
            $this->selectedUsers = [];
            $this->selectAll = false;
            
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
                    
                    const statusSelects = document.querySelectorAll(\'select[wire\\\\:model\\\\.live="statusFilter"]\');
                    statusSelects.forEach(select => {
                        select.value = "all";
                        select.dispatchEvent(new Event("change", { bubbles: true }));
                    });
                    
                    const roleSelects = document.querySelectorAll(\'select[wire\\\\:model\\\\.live="roleFilter"]\');
                    roleSelects.forEach(select => {
                        select.value = "all";
                        select.dispatchEvent(new Event("change", { bubbles: true }));
                    });
                    
                    console.log("✅ 表單元素已強制同步");
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
                    'statusFilter' => $this->statusFilter,
                    'roleFilter' => $this->roleFilter,
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
        }
    }

    /**
     * 測試方法 - 用於驗證 Livewire 連接
     */
    public function testMethod(): void
    {
        \Log::info('🧪 testMethod - 測試方法被呼叫了！', [
            'timestamp' => now()->toISOString(),
            'user' => auth()->user()->username ?? 'unknown',
        ]);
        
        $this->dispatch('show-toast', [
            'type' => 'success',
            'message' => '測試方法執行成功！'
        ]);
    }

    /**
     * 完整重置方法（備用）
     */
    public function resetFiltersComplete(): void
    {
        // 記錄方法被呼叫
        \Log::info('resetFiltersComplete method called', [
            'before_reset' => [
                'search' => $this->search,
                'statusFilter' => $this->statusFilter,
                'roleFilter' => $this->roleFilter,
            ]
        ]);
        
        // 直接設定屬性值而不使用 reset() 方法
        $this->search = '';
        $this->statusFilter = 'all';
        $this->roleFilter = 'all';
        $this->selectedUsers = [];
        $this->selectAll = false;
        
        // 重置排序
        $this->sortField = 'created_at';
        $this->sortDirection = 'desc';
        
        // 重置分頁
        $this->resetPage();
        
        // 記錄重置後的狀態
        \Log::info('resetFiltersComplete completed', [
            'after_reset' => [
                'search' => $this->search,
                'statusFilter' => $this->statusFilter,
                'roleFilter' => $this->roleFilter,
            ]
        ]);
        
        // 顯示成功訊息
        $this->dispatch('show-toast', [
            'type' => 'success',
            'message' => '篩選條件已清除'
        ]);
        
        // 強制重新渲染
        $this->render();
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
        $this->getCacheService()->clearAll();
    }

    /**
     * 格式化使用者建立時間
     */
    public function formatUserCreatedAt(User $user): string
    {
        return $user->formatted_created_at;
    }

    /**
     * 強制重新載入元件資料
     */
    public function forceRefresh(): void
    {
        // 清除所有快取
        $this->clearUserCaches();
        
        // 重置分頁
        $this->resetPage();
        
        // 強制重新渲染
        $this->dispatch('$refresh');
        
        \Log::info('UserList 元件強制重新整理完成');
    }

    /**
     * 修復 DOM 狀態
     */
    public function fixDomState(): void
    {
        try {
            // 重置所有可能導致 DOM 衝突的狀態
            $this->selectedUsers = [];
            $this->selectAll = false;
            
            // 清除快取
            $this->clearUserCaches();
            
            // 重新載入資料
            $this->resetPage();
            
            \Log::info('DOM 狀態修復完成');
            
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'DOM 狀態已修復'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('DOM 狀態修復失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        try {
            // 使用新的響應式設計版本
            return view('livewire.admin.users.user-list', [
                'users' => $this->users,
                'availableRoles' => $this->availableRoles,
                'statusOptions' => $this->statusOptions,
            ]);
        } catch (\Exception $e) {
            \Log::error('UserList 渲染失敗', [
                'error' => $e->getMessage(),
                'filters' => [
                    'search' => $this->search,
                    'statusFilter' => $this->statusFilter,
                    'roleFilter' => $this->roleFilter,
                ]
            ]);
            
            // 嘗試重置狀態並重新渲染
            $this->fixDomState();
            
            return view('livewire.admin.users.user-list', [
                'users' => collect(),
                'availableRoles' => collect(),
                'statusOptions' => $this->statusOptions,
            ]);
        }
    }
}