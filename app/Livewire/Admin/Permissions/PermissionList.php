<?php

namespace App\Livewire\Admin\Permissions;

use App\Models\Permission;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use App\Services\AuditLogService;
use App\Services\InputValidationService;
use App\Traits\HandlesLivewireErrors;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

/**
 * 權限列表 Livewire 元件
 * 
 * 提供權限管理的主要介面，包含搜尋、篩選、分頁、排序和多種檢視模式
 */
class PermissionList extends Component
{
    use WithPagination, HandlesLivewireErrors;

    /**
     * 設定分頁主題
     */
    protected $paginationTheme = 'tailwind';

    // 搜尋相關屬性
    public string $search = '';
    
    // 篩選相關屬性
    public string $moduleFilter = 'all';
    public string $typeFilter = 'all';
    public string $usageFilter = 'all';
    public bool $showFilters = false;
    
    // 檢視模式相關屬性
    public string $viewMode = 'list'; // list, grouped, tree
    public array $expandedGroups = [];
    
    // 分頁相關屬性
    public int $perPage = 25;
    public array $perPageOptions = [10, 25, 50, 100];
    
    // URL 查詢字串屬性（用於狀態持久化）
    protected $queryString = [
        'search' => ['except' => ''],
        'moduleFilter' => ['except' => 'all'],
        'typeFilter' => ['except' => 'all'],
        'usageFilter' => ['except' => 'all'],
        'perPage' => ['except' => 25],
    ];
    
    // 排序相關屬性
    public string $sortField = 'module';
    public string $sortDirection = 'asc';
    
    // 批量操作相關屬性
    public array $selectedPermissions = [];
    public bool $selectAll = false;
    public string $bulkAction = '';

    protected PermissionRepositoryInterface $permissionRepository;
    protected InputValidationService $validationService;
    protected AuditLogService $auditService;

    /**
     * 元件初始化
     */
    public function boot(
        PermissionRepositoryInterface $permissionRepository,
        InputValidationService $validationService,
        AuditLogService $auditService
    ): void {
        $this->permissionRepository = $permissionRepository;
        $this->validationService = $validationService;
        $this->auditService = $auditService;
    }

    /**
     * 元件掛載時執行權限檢查
     */
    public function mount(): void
    {
        // 檢查權限
        if (!auth()->user()->hasPermission('permissions.view')) {
            abort(403, __('admin.errors.unauthorized'));
        }

        // 從 URL 參數初始化狀態
        $this->initializeFromQueryString();

        // 記錄存取日誌
        // $this->auditService->logDataAccess('permissions', 'list_view');
    }

    /**
     * 從 URL 查詢字串初始化狀態
     */
    private function initializeFromQueryString(): void
    {
        // 從 request 獲取參數
        $request = request();
        
        $this->search = $request->get('search', '');
        $this->moduleFilter = $request->get('moduleFilter', 'all');
        $this->typeFilter = $request->get('typeFilter', 'all');
        $this->usageFilter = $request->get('usageFilter', 'all');
        
        // 驗證並設定 perPage
        $requestedPerPage = (int) $request->get('perPage', 25);
        if (in_array($requestedPerPage, $this->perPageOptions)) {
            $this->perPage = $requestedPerPage;
        }
    }

    /**
     * 取得篩選後的權限資料（計算屬性）
     */
    public function getPermissionsProperty(): LengthAwarePaginator
    {
        try {
            // 驗證和清理篩選條件
            $filters = $this->validationService->validatePermissionFilters([
                'search' => $this->search,
                'module' => $this->moduleFilter,
                'type' => $this->typeFilter,
                'usage' => $this->usageFilter,
                'sort_field' => $this->sortField,
                'sort_direction' => $this->sortDirection,
                'include_relations' => ['roles'], // 只載入必要的關聯
                'include_counts' => ['roles'], // 只載入必要的計數
            ]);

            // 直接呼叫 repository，不使用額外的快取層
            // 因為 repository 內部已經有快取機制
            return $this->permissionRepository->getPaginatedPermissions($filters, $this->perPage);
            
        } catch (\Exception $e) {
            // 記錄錯誤並返回空的分頁結果
            logger()->error('Error getting permissions', [
                'error' => $e->getMessage(),
                'filters' => [
                    'search' => $this->search,
                    'module' => $this->moduleFilter,
                    'type' => $this->typeFilter,
                    'usage' => $this->usageFilter,
                ],
                'current_page' => $this->getPage(),
                'per_page' => $this->perPage,
            ]);
            
            return $this->permissionRepository->getPaginatedPermissions([], $this->perPage);
        }
    }

    /**
     * 取得按模組分組的權限資料（計算屬性）
     */
    public function getGroupedPermissionsProperty(): Collection
    {
        try {
            return Cache::remember('permissions_grouped_' . md5(serialize([
                $this->search,
                $this->moduleFilter,
                $this->typeFilter,
                $this->usageFilter,
            ])), 1800, function () {
                $filters = [
                    'search' => $this->search,
                    'module' => $this->moduleFilter,
                    'type' => $this->typeFilter,
                    'usage' => $this->usageFilter,
                ];

                // 取得所有符合篩選條件的權限
                $allPermissions = $this->permissionRepository->getPaginatedPermissions($filters, 1000);
                
                return $allPermissions->getCollection()->groupBy('module');
            });
        } catch (\Exception $e) {
            logger()->error('Error getting grouped permissions', [
                'error' => $e->getMessage(),
            ]);
            
            return collect();
        }
    }

    /**
     * 取得可用的模組選項（計算屬性）
     */
    public function getModulesProperty(): Collection
    {
        try {
            return Cache::remember('permission_modules_list', 3600, function () {
                return $this->permissionRepository->getAvailableModules();
            });
        } catch (\Exception $e) {
            logger()->error('Error getting modules', ['error' => $e->getMessage()]);
            return collect();
        }
    }

    /**
     * 取得可用的權限類型選項（計算屬性）
     */
    public function getTypesProperty(): Collection
    {
        try {
            return Cache::remember('permission_types_list', 3600, function () {
                return $this->permissionRepository->getAvailableTypes();
            });
        } catch (\Exception $e) {
            logger()->error('Error getting types', ['error' => $e->getMessage()]);
            return collect();
        }
    }

    /**
     * 取得權限統計資料（計算屬性）
     */
    public function getStatsProperty(): array
    {
        try {
            return Cache::remember('permission_stats', 1800, function () {
                return $this->permissionRepository->getPermissionUsageStats();
            });
        } catch (\Exception $e) {
            logger()->error('Error getting stats', ['error' => $e->getMessage()]);
            return [
                'total' => 0,
                'used' => 0,
                'unused' => 0,
                'usage_percentage' => 0,
                'modules' => [],
            ];
        }
    }

    /**
     * 取得使用狀態篩選選項（計算屬性）
     */
    public function getUsageOptionsProperty(): array
    {
        return [
            'all' => __('permissions.search.all_usage'),
            'used' => __('permissions.search.used'),
            'unused' => __('permissions.search.unused'),
            'marked_unused' => '已標記未使用',
            'low_usage' => '低使用率',
        ];
    }

    /**
     * 取得檢視模式選項（計算屬性）
     */
    public function getViewModeOptionsProperty(): array
    {
        return [
            'list' => __('permissions.view_modes.list'),
            'grouped' => __('permissions.view_modes.grouped'),
            'tree' => __('permissions.view_modes.tree'),
        ];
    }

    /**
     * 搜尋條件更新時重置分頁 - 增強版本
     */
    public function updatedSearch(): void
    {
        try {
            // 記錄搜尋操作以進行效能分析
            if (!empty($this->search)) {
                \Log::info('🔍 Permission search performed', [
                    'search_term' => $this->search,
                    'search_length' => strlen($this->search),
                    'user' => auth()->user()->username ?? 'unknown',
                    'timestamp' => now()->toISOString()
                ]);
                
                $this->search = $this->validationService->validateSearchInput($this->search);
                
                // 檢查是否包含惡意內容
                if ($this->validationService->containsMaliciousContent($this->search)) {
                    $this->auditService->logSecurityEvent('malicious_search_input', 'high', [
                        'search_input' => $this->search,
                        'component' => 'PermissionList',
                    ]);
                    
                    $this->search = '';
                    $this->dispatch('show-toast', [
                        'type' => 'error',
                        'message' => '搜尋條件包含無效內容'
                    ]);
                    return;
                }
                
                // 如果搜尋詞太短，顯示提示
                if (strlen($this->search) < 2) {
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
            
            $this->resetPage();
            $this->clearCache();
            
            // 清除之前的搜尋錯誤
            $this->resetErrorBag('search');
            
        } catch (ValidationException $e) {
            $this->search = '';
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '搜尋條件格式錯誤'
            ]);
        }
    }

    /**
     * 模組篩選更新時重置分頁
     */
    public function updatedModuleFilter(): void
    {
        $this->resetPage();
        $this->clearCache();
    }

    /**
     * 類型篩選更新時重置分頁
     */
    public function updatedTypeFilter(): void
    {
        $this->resetPage();
        $this->clearCache();
    }

    /**
     * 使用狀態篩選更新時重置分頁
     */
    public function updatedUsageFilter(): void
    {
        $this->resetPage();
        $this->clearCache();
    }

    /**
     * 檢視模式更新時清除快取
     */
    public function updatedViewMode(): void
    {
        $this->clearCache();
        
        // 記錄檢視模式變更
        $this->auditService->logUserAction('permission_view_mode_changed', [
            'view_mode' => $this->viewMode,
        ]);
    }

    /**
     * 每頁顯示筆數更新時重置分頁
     */
    public function updatedPerPage(): void
    {
        try {
            // 驗證 perPage 值
            if (!in_array($this->perPage, $this->perPageOptions)) {
                $this->perPage = 25; // 重置為預設值
            }
            
            $this->resetPage();
            $this->clearCache();
            
            // 記錄每頁顯示筆數變更（暫時註解避免錯誤）
            // $this->auditService->logUserAction('permission_per_page_changed', [
            //     'per_page' => $this->perPage,
            // ]);
            
            // 發送更新事件
            $this->dispatch('per-page-updated', perPage: $this->perPage);
            
        } catch (\Exception $e) {
            logger()->error('Error updating perPage', [
                'error' => $e->getMessage(),
                'perPage' => $this->perPage
            ]);
            
            // 重置為預設值
            $this->perPage = 25;
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
        $this->clearCache();
    }

    /**
     * 切換分組展開/收合狀態
     */
    public function toggleGroup(string $module): void
    {
        if (in_array($module, $this->expandedGroups)) {
            $this->expandedGroups = array_diff($this->expandedGroups, [$module]);
        } else {
            $this->expandedGroups[] = $module;
        }
    }

    /**
     * 全選切換功能
     */
    public function toggleSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selectedPermissions = $this->permissions->pluck('id')->toArray();
        } else {
            $this->selectedPermissions = [];
        }
    }

    /**
     * 單個權限選擇切換
     */
    public function togglePermissionSelection(int $permissionId): void
    {
        if (in_array($permissionId, $this->selectedPermissions)) {
            $this->selectedPermissions = array_diff($this->selectedPermissions, [$permissionId]);
        } else {
            $this->selectedPermissions[] = $permissionId;
        }

        // 更新全選狀態
        $this->selectAll = count($this->selectedPermissions) === $this->permissions->count();
    }

    /**
     * 建立權限
     */
    public function createPermission(): void
    {
        // 檢查權限
        if (!auth()->user()->hasPermission('permissions.create')) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => __('permissions.messages.no_permission_create', ['default' => '您沒有建立權限的權限'])
            ]);
            return;
        }

        // 記錄操作日誌
        $this->auditService->logUserAction('permission_create_access');
        
        $this->dispatch('open-permission-form', mode: 'create');
    }

    /**
     * 編輯權限
     */
    public function editPermission(int $permissionId): void
    {
        try {
            // 驗證權限 ID
            $permissionId = $this->validationService->validateId($permissionId);
            
            $permission = Permission::find($permissionId);
            if (!$permission) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => __('permissions.messages.permission_not_found', ['default' => '找不到指定的權限'])
                ]);
                return;
            }

            // 檢查權限
            if (!auth()->user()->hasPermission('permissions.edit')) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => __('permissions.messages.no_permission_edit', ['default' => '您沒有編輯權限的權限'])
                ]);
                return;
            }

            // 記錄操作日誌
            $this->auditService->logDataAccess('permissions', 'edit_access', [
                'permission_id' => $permissionId,
                'permission_name' => $permission->name,
            ]);
            
            $this->dispatch('open-permission-form', mode: 'edit', permissionId: $permissionId);
        } catch (ValidationException $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '無效的權限 ID'
            ]);
        }
    }

    /**
     * 檢視權限依賴關係
     */
    public function viewDependencies(int $permissionId): void
    {
        try {
            // 驗證權限 ID
            $permissionId = $this->validationService->validateId($permissionId);
            
            $permission = Permission::find($permissionId);
            if (!$permission) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => __('permissions.messages.permission_not_found', ['default' => '找不到指定的權限'])
                ]);
                return;
            }

            // 記錄操作日誌
            $this->auditService->logDataAccess('permissions', 'dependencies_view', [
                'permission_id' => $permissionId,
                'permission_name' => $permission->name,
            ]);
            
            $this->dispatch('select-permission-for-dependencies', permissionId: $permissionId);
        } catch (ValidationException $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '無效的權限 ID'
            ]);
        }
    }

    /**
     * 刪除權限
     */
    public function deletePermission(int $permissionId): void
    {
        try {
            // 驗證權限 ID
            $permissionId = $this->validationService->validateId($permissionId);
            
            $permission = Permission::find($permissionId);
            if (!$permission) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => __('permissions.messages.permission_not_found', ['default' => '找不到指定的權限'])
                ]);
                return;
            }

            // 檢查權限
            if (!auth()->user()->hasPermission('permissions.delete')) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => __('permissions.messages.no_permission_delete', ['default' => '您沒有刪除權限的權限'])
                ]);
                return;
            }

            // 檢查是否可以刪除
            if (!$permission->can_be_deleted) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => __('permissions.messages.cannot_delete_permission', ['default' => '無法刪除此權限'])
                ]);
                return;
            }

            // 記錄刪除嘗試
            $this->auditService->logUserAction('permission_delete_attempt', [
                'permission_id' => $permissionId,
                'permission_name' => $permission->name,
            ]);
            
            $this->dispatch('confirm-permission-delete', permissionId: $permissionId);
        } catch (ValidationException $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '無效的權限 ID'
            ]);
        }
    }

    /**
     * 確認刪除權限（從模態對話框觸發）
     */
    #[On('permission-delete-confirmed')]
    public function confirmDelete(int $permissionId): void
    {
        // 清除快取
        $this->clearCache();
        
        // 重新載入頁面資料
        $this->resetPage();
        
        // 顯示成功訊息已在模態對話框中處理
    }

    /**
     * 匯出權限
     */
    public function exportPermissions(): void
    {
        // 檢查權限
        if (!auth()->user()->hasPermission('permissions.export')) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '您沒有匯出權限資料的權限'
            ]);
            return;
        }

        // 準備匯出篩選條件
        $exportFilters = [
            'modules' => $this->moduleFilter !== 'all' ? [$this->moduleFilter] : [],
            'types' => $this->typeFilter !== 'all' ? [$this->typeFilter] : [],
            'usage_status' => $this->usageFilter !== 'all' ? $this->usageFilter : 'all',
        ];

        // 如果有選中的權限，只匯出選中的
        if (!empty($this->selectedPermissions)) {
            $exportFilters['permission_ids'] = $this->selectedPermissions;
        }

        // 記錄匯出操作
        $this->auditService->logDataAccess('permissions', 'export', [
            'filters' => $exportFilters,
            'selected_count' => count($this->selectedPermissions),
        ]);
        
        $this->dispatch('export-permissions-started', filters: $exportFilters);
    }

    /**
     * 匯入權限
     */
    public function importPermissions(): void
    {
        // 檢查權限
        if (!auth()->user()->hasPermission('permissions.import')) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '您沒有匯入權限資料的權限'
            ]);
            return;
        }

        $this->dispatch('open-import-modal');
    }

    /**
     * 監聽權限匯入完成事件
     */
    #[On('permissions-imported')]
    public function handlePermissionsImported(): void
    {
        // 清除快取並重新載入資料
        $this->clearCache();
        $this->resetPage();
        
        $this->dispatch('show-toast', [
            'type' => 'success',
            'message' => '權限資料已更新，列表已重新載入'
        ]);
    }

    /**
     * 開啟使用情況分析
     */
    public function openUsageAnalysis(): void
    {
        // 檢查權限
        if (!auth()->user()->hasPermission('permissions.view')) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '您沒有檢視權限分析的權限'
            ]);
            return;
        }

        // 記錄操作日誌
        $this->auditService->logDataAccess('permissions', 'usage_analysis_opened');
        
        $this->dispatch('open-usage-analysis');
    }

    /**
     * 開啟未使用權限標記工具
     */
    public function openUnusedPermissionMarker(): void
    {
        // 檢查權限
        if (!auth()->user()->hasPermission('permissions.manage')) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '您沒有管理權限的權限'
            ]);
            return;
        }

        $this->dispatch('open-unused-permission-marker');
    }

    /**
     * 取得權限使用情況徽章
     */
    public function getUsageAnalysisBadge(Permission $permission): array
    {
        $stats = $permission->usage_stats;
        
        if ($permission->isMarkedAsUnused()) {
            return [
                'text' => '已標記未使用',
                'class' => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
            ];
        }
        
        if (!$stats['is_used']) {
            return [
                'text' => '未使用',
                'class' => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
            ];
        }

        $frequency = $stats['usage_frequency'];
        
        if ($frequency >= 20) {
            return [
                'text' => '高頻使用',
                'class' => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
            ];
        } elseif ($frequency >= 10) {
            return [
                'text' => '中頻使用',
                'class' => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
            ];
        } else {
            return [
                'text' => '低頻使用',
                'class' => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200'
            ];
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
                    'moduleFilter' => $this->moduleFilter ?? 'all',
                    'typeFilter' => $this->typeFilter ?? 'all',
                    'usageFilter' => $this->usageFilter ?? 'all',
                ]
            ]);
            
            // 重置所有篩選條件
            $this->search = '';
            $this->moduleFilter = 'all';
            $this->typeFilter = 'all';
            $this->usageFilter = 'all';
            $this->viewMode = 'list';
            $this->expandedGroups = [];
            $this->selectedPermissions = [];
            $this->selectAll = false;
            $this->bulkAction = '';
            
            // 清除快取
            $this->clearCache();
            
            // 重置分頁和驗證
            $this->resetPage();
            $this->resetValidation();
            
            // 使用 Livewire 的 $set 方法強制同步前端狀態
            $this->js('
                console.log("🔄 使用 Livewire $set 方法重置篩選器狀態");
                
                // 使用 Livewire 的 $set 方法直接更新前端狀態
                $wire.set("search", "");
                $wire.set("moduleFilter", "all");
                $wire.set("typeFilter", "all");
                $wire.set("usageFilter", "all");
                
                // 延遲執行 DOM 同步，確保 Livewire 狀態更新後再同步 DOM
                setTimeout(() => {
                    console.log("🔄 開始同步 DOM 元素狀態");
                    
                    // 同步搜尋框
                    const searchInputs = document.querySelectorAll(\'input[wire\\\\:model\\\\.live="search"], input[wire\\\\:model\\\\.live\\\\.debounce\\\\.300ms="search"]\');
                    searchInputs.forEach((input, index) => {
                        if (input.value !== "") {
                            console.log(`同步搜尋框 ${index + 1}: "${input.value}" → ""`);
                            input.value = "";
                            input.dispatchEvent(new Event("input", { bubbles: true }));
                        }
                    });
                    
                    // 同步模組篩選器
                    const moduleFilter = document.querySelector(\'select[wire\\\\:model\\\\.live="moduleFilter"]\');
                    if (moduleFilter && moduleFilter.value !== "all") {
                        console.log(`同步模組篩選器: "${moduleFilter.value}" → "all"`);
                        moduleFilter.value = "all";
                        moduleFilter.selectedIndex = 0;
                        moduleFilter.dispatchEvent(new Event("change", { bubbles: true }));
                        console.log("✅ 模組篩選器已同步:", moduleFilter.options[moduleFilter.selectedIndex].text);
                    }
                    
                    // 同步類型篩選器
                    const typeFilter = document.querySelector(\'select[wire\\\\:model\\\\.live="typeFilter"]\');
                    if (typeFilter && typeFilter.value !== "all") {
                        console.log(`同步類型篩選器: "${typeFilter.value}" → "all"`);
                        typeFilter.value = "all";
                        typeFilter.selectedIndex = 0;
                        typeFilter.dispatchEvent(new Event("change", { bubbles: true }));
                    }
                    
                    // 同步使用狀態篩選器
                    const usageFilter = document.querySelector(\'select[wire\\\\:model\\\\.live="usageFilter"]\');
                    if (usageFilter && usageFilter.value !== "all") {
                        console.log(`同步使用狀態篩選器: "${usageFilter.value}" → "all"`);
                        usageFilter.value = "all";
                        usageFilter.selectedIndex = 0;
                        usageFilter.dispatchEvent(new Event("change", { bubbles: true }));
                    }
                    
                    console.log("✅ DOM 元素狀態同步完成");
                }, 300);
            ');
            
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
                    'moduleFilter' => $this->moduleFilter,
                    'typeFilter' => $this->typeFilter,
                    'usageFilter' => $this->usageFilter,
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('重置方法執行失敗', [
                'method' => 'resetFilters',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'component' => static::class,
            ]);
            
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '重置操作失敗，請重試'
            ]);
        }
    }

    /**
     * 檢查是否有活動的篩選條件
     */
    public function hasActiveFilters(): bool
    {
        return !empty(trim($this->search)) || 
               $this->moduleFilter !== 'all' || 
               $this->typeFilter !== 'all' || 
               $this->usageFilter !== 'all';
    }

    /**
     * 取得篩選狀態（用於前端檢查）
     */
    public function getFilterStatus(): array
    {
        return [
            'search' => $this->search,
            'moduleFilter' => $this->moduleFilter,
            'typeFilter' => $this->typeFilter,
            'usageFilter' => $this->usageFilter,
            'hasActiveFilters' => $this->hasActiveFilters()
        ];
    }

    /**
     * 取得權限的本地化顯示名稱
     */
    public function getLocalizedDisplayName(Permission $permission): string
    {
        return $permission->localized_display_name;
    }

    /**
     * 取得權限的本地化描述
     */
    public function getLocalizedDescription(Permission $permission): string
    {
        return $permission->localized_description;
    }

    /**
     * 取得權限使用狀態的本地化顯示
     */
    public function getUsageBadge(Permission $permission): array
    {
        $isUsed = $permission->isUsed();
        
        return $isUsed ? [
            'text' => __('permissions.status.used'),
            'class' => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
        ] : [
            'text' => __('permissions.status.unused'),
            'class' => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
        ];
    }

    /**
     * 取得權限類型的本地化顯示
     */
    public function getLocalizedType(string $type): string
    {
        $key = "permissions.types.{$type}";
        $translation = __($key);
        
        // 如果翻譯不存在，返回預設值
        if ($translation === $key) {
            return ucfirst($type);
        }
        
        return $translation;
    }

    /**
     * 取得模組的本地化顯示
     */
    public function getLocalizedModule(string $module): string
    {
        $key = "permissions.modules.{$module}";
        $translation = __($key);
        
        // 如果翻譯不存在，返回格式化的預設值
        if ($translation === $key) {
            return ucfirst(str_replace('_', ' ', $module));
        }
        
        return $translation;
    }

    /**
     * 檢查使用者是否擁有特定權限
     */
    public function hasPermission(string $permission): bool
    {
        return auth()->user()->hasPermission("permissions.{$permission}");
    }

    /**
     * 清除相關快取
     */
    private function clearCache(): void
    {
        // 清除當前元件的快取
        $patterns = [
            $this->generateCacheKey('permissions'),
            $this->generateCacheKey('grouped_permissions'),
            $this->generateCacheKey('stats'),
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }

        // 清除全域快取
        Cache::forget('permission_stats');
        Cache::forget('permission_modules_list');
        Cache::forget('permission_types_list');
    }

    /**
     * 生成快取鍵
     * 
     * @param string $type 快取類型
     * @return string
     */
    private function generateCacheKey(string $type): string
    {
        $filters = [
            'search' => $this->search,
            'module' => $this->moduleFilter,
            'type' => $this->typeFilter,
            'usage' => $this->usageFilter,
            'sort_field' => $this->sortField,
            'sort_direction' => $this->sortDirection,
            'view_mode' => $this->viewMode,
            'per_page' => $this->perPage,
            'page' => $this->getPage(),
        ];

        return "permission_list_{$type}_" . md5(serialize($filters));
    }

    /**
     * 延遲載入權限統計（避免阻塞主要資料載入）
     */
    public function loadStats(): void
    {
        $this->dispatch('stats-loaded', stats: $this->stats);
    }

    /**
     * 批量載入權限資料（效能優化）
     * 
     * @param array $permissionIds 權限 ID 陣列
     * @return Collection
     */
    public function batchLoadPermissions(array $permissionIds): Collection
    {
        if (empty($permissionIds)) {
            return collect();
        }

        return $this->permissionRepository->preloadPermissionData($permissionIds, [
            'roles',
            'dependencies',
            'dependents'
        ]);
    }

    /**
     * 虛擬化列表支援（大量資料時使用）
     * 
     * @param int $startIndex 開始索引
     * @param int $endIndex 結束索引
     * @return array
     */
    public function getVirtualizedPermissions(int $startIndex, int $endIndex): array
    {
        $cacheKey = $this->generateCacheKey("virtualized_{$startIndex}_{$endIndex}");
        
        return Cache::remember($cacheKey, 300, function () use ($startIndex, $endIndex) {
            $filters = [
                'search' => $this->search,
                'module' => $this->moduleFilter,
                'type' => $this->typeFilter,
                'usage' => $this->usageFilter,
                'sort_field' => $this->sortField,
                'sort_direction' => $this->sortDirection,
            ];

            $limit = $endIndex - $startIndex + 1;
            $cursor = $startIndex > 0 ? $startIndex : null;

            return $this->permissionRepository->getCursorPaginatedPermissions($filters, $limit, $cursor);
        });
    }

    /**
     * 預載入下一頁資料（提升使用者體驗）
     */
    public function preloadNextPage(): void
    {
        $nextPage = $this->getPage() + 1;
        
        // 在背景預載入下一頁資料
        dispatch(function () use ($nextPage) {
            $filters = [
                'search' => $this->search,
                'module' => $this->moduleFilter,
                'type' => $this->typeFilter,
                'usage' => $this->usageFilter,
                'sort_field' => $this->sortField,
                'sort_direction' => $this->sortDirection,
            ];

            // 預載入到快取中
            $cacheKey = "permission_list_preload_page_{$nextPage}_" . md5(serialize($filters));
            Cache::put($cacheKey, $this->permissionRepository->getPaginatedPermissions($filters, $this->perPage), 300);
        })->afterResponse();
    }

    /**
     * 智慧搜尋建議
     * 
     * @param string $query 搜尋查詢
     * @return array
     */
    public function getSearchSuggestions(string $query): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        $cacheKey = "search_suggestions_" . md5($query);
        
        return Cache::remember($cacheKey, 1800, function () use ($query) {
            $suggestions = [];

            // 權限名稱建議
            $nameMatches = Permission::where('name', 'like', "{$query}%")
                                   ->limit(5)
                                   ->pluck('name')
                                   ->toArray();
            
            // 顯示名稱建議
            $displayNameMatches = Permission::where('display_name', 'like', "{$query}%")
                                          ->limit(5)
                                          ->pluck('display_name')
                                          ->toArray();

            // 模組建議
            $moduleMatches = Permission::where('module', 'like', "{$query}%")
                                     ->distinct()
                                     ->limit(3)
                                     ->pluck('module')
                                     ->toArray();

            return [
                'names' => $nameMatches,
                'display_names' => $displayNameMatches,
                'modules' => $moduleMatches,
            ];
        });
    }

    /**
     * 優化的搜尋功能（防抖動）
     */
    public function optimizedSearch(): void
    {
        // 使用 JavaScript 防抖動，這裡只處理最終搜尋
        $this->resetPage();
        $this->clearCache();
        
        // 如果搜尋詞較長，預載入搜尋建議
        if (strlen($this->search) >= 2) {
            $this->dispatch('search-suggestions-updated', 
                suggestions: $this->getSearchSuggestions($this->search)
            );
        }
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        $data = [
            'modules' => $this->modules,
            'types' => $this->types,
            'usageOptions' => $this->usageOptions,
            'viewModeOptions' => $this->viewModeOptions,
            'stats' => $this->stats,
        ];

        // 根據檢視模式載入不同的資料
        if ($this->viewMode === 'grouped' || $this->viewMode === 'tree') {
            $data['groupedPermissions'] = $this->groupedPermissions;
        } else {
            $data['permissions'] = $this->permissions;
        }

        return view('livewire.admin.permissions.permission-list', $data);
    }

    /**
     * statusFilter 更新時重置分頁
     */
    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }


    /**
     * roleFilter 更新時重置分頁
     */
    public function updatedRoleFilter(): void
    {
        $this->resetPage();
    }


}

