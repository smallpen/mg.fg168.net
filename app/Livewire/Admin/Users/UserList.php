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
    public array $perPageOptions = [10, 15, 25, 50];
    
    // URL 查詢字串屬性（用於狀態持久化）
    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
        'roleFilter' => ['except' => 'all'],
        'perPage' => ['except' => 15],
        // 注意：不要添加 'page'，Livewire 會自動處理
    ];
    
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
        // 檢查權限
        if (!auth()->user()->hasPermission('users.view')) {
            abort(403, '您沒有檢視使用者的權限');
        }

        // 從 URL 參數初始化狀態
        $this->initializeFromQueryString();

        // 記錄存取日誌
        $this->getAuditService()->logDataAccess('users', 'list_view');
    }

    /**
     * 從 URL 查詢字串初始化狀態
     */
    private function initializeFromQueryString(): void
    {
        $request = request();
        
        $this->search = $request->get('search', '');
        $this->statusFilter = $request->get('statusFilter', 'all');
        $this->roleFilter = $request->get('roleFilter', 'all');
        
        // 驗證並設定 perPage
        $requestedPerPage = (int) $request->get('perPage', 15);
        if (in_array($requestedPerPage, $this->perPageOptions)) {
            $this->perPage = $requestedPerPage;
        }
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
     * 每頁顯示筆數更新時重置分頁
     */
    public function updatedPerPage(): void
    {
        try {
            // 驗證 perPage 值
            if (!in_array($this->perPage, $this->perPageOptions)) {
                $this->perPage = 15; // 重置為預設值
            }
            
            $this->resetPage();
            $this->clearCache(); // 如果有快取機制
            
            // 發送更新事件
            $this->dispatch('per-page-updated', perPage: $this->perPage);
            
        } catch (\Exception $e) {
            logger()->error('Error updating perPage', [
                'error' => $e->getMessage(),
                'perPage' => $this->perPage
            ]);
            
            // 重置為預設值
            $this->perPage = 15;
            $this->resetPage();
        }
    }

    /**
     * 前往指定頁面
     */
    public function gotoPage(int $page): void
    {
        $this->setPage($page);
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
            $users = $this->getUsersProperty();
            $this->selectedUsers = $users->pluck('id')->toArray();
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
        $users = $this->getUsersProperty();
        $this->selectAll = count($this->selectedUsers) === $users->count();
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
            
            // 發送強制 UI 更新事件
            $this->dispatch('force-ui-update');
            
            // 發送前端重置事件，讓 Alpine.js 處理
            $this->dispatch('reset-form-elements');
            
            // 顯示成功訊息
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => '篩選條件已清除'
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
     * 切換篩選器顯示狀態
     */
    public function toggleFilters(): void
    {
        $this->showFilters = !$this->showFilters;
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
     * 清除快取
     */
    private function clearCache(): void
    {
        $this->clearUserCaches();
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.users.user-list');
    }
}