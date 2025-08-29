<?php

namespace App\Livewire\Admin\Permissions;

use Livewire\Component;
use Livewire\WithPagination;
use App\Services\PermissionAuditService;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * 權限審計日誌元件
 * 
 * 顯示和管理權限相關的審計日誌
 */
class PermissionAuditLog extends Component
{
    use WithPagination;

    /**
     * 搜尋和篩選條件
     */
    public string $search = '';
    public string $actionFilter = 'all';
    public string $userFilter = '';
    public string $permissionFilter = '';
    public string $moduleFilter = 'all';
    public string $startDate = '';
    public string $endDate = '';
    public string $ipFilter = '';
    public int $perPage = 25;

    /**
     * 顯示選項
     */
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';
    public bool $showDetails = false;
    public ?int $selectedLogId = null;

    /**
     * 統計資料
     */
    public array $stats = [];

    /**
     * 權限審計服務
     */
    protected PermissionAuditService $auditService;

    /**
     * 初始化元件
     */
    public function mount()
    {
        $this->auditService = app(PermissionAuditService::class);
        $this->startDate = Carbon::now()->subDays(30)->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
        $this->loadStats();
    }

    /**
     * 載入統計資料
     */
    public function loadStats(): void
    {
        $this->stats = $this->auditService->getAuditStats(30);
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
                    'actionFilter' => $this->actionFilter ?? 'all',
                    'userFilter' => $this->userFilter ?? '',
                    'permissionFilter' => $this->permissionFilter ?? '',
                    'moduleFilter' => $this->moduleFilter ?? 'all',
                    'startDate' => $this->startDate ?? '',
                    'endDate' => $this->endDate ?? '',
                    'ipFilter' => $this->ipFilter ?? '',
                ]
            ]);
            
            // 重置所有篩選條件
            $this->search = '';
            $this->actionFilter = 'all';
            $this->userFilter = '';
            $this->permissionFilter = '';
            $this->moduleFilter = 'all';
            $this->startDate = Carbon::now()->subDays(30)->format('Y-m-d');
            $this->endDate = Carbon::now()->format('Y-m-d');
            $this->ipFilter = '';
            
            // 清除快取
            if (method_exists($this, 'clearCache')) {
                $this->clearCache();
            }
            
            // 重置分頁和驗證
            $this->resetPage();
            $this->resetValidation();
            
            // 重新載入統計資料
            if (method_exists($this, 'loadStats')) {
                $this->loadStats();
            }
            
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
                    
                    const filterInputs = document.querySelectorAll(\'input[wire\\\\:model\\\\.live*="Filter"]\');
                    filterInputs.forEach(input => {
                        input.value = "";
                        input.dispatchEvent(new Event("input", { bubbles: true }));
                    });
                    
                    const dateInputs = document.querySelectorAll(\'input[wire\\\\:model\\\\.live="startDate"], input[wire\\\\:model\\\\.live="endDate"]\');
                    dateInputs.forEach(input => {
                        if (input.getAttribute("wire:model.live") === "startDate") {
                            input.value = "' . Carbon::now()->subDays(30)->format('Y-m-d') . '";
                        } else if (input.getAttribute("wire:model.live") === "endDate") {
                            input.value = "' . Carbon::now()->format('Y-m-d') . '";
                        }
                        input.dispatchEvent(new Event("input", { bubbles: true }));
                    });
                    
                    const filterSelects = document.querySelectorAll(\'select[wire\\\\:model\\\\.live*="Filter"]\');
                    filterSelects.forEach(select => {
                        select.value = "all";
                        select.dispatchEvent(new Event("change", { bubbles: true }));
                    });
                    
                    console.log("✅ 權限審計日誌表單元素已強制同步");
                }, 100);
            ');
            
            // 發送強制 UI 更新事件
            $this->dispatch('force-ui-update');
            
            // 發送前端重置事件，讓 Alpine.js 處理
            $this->dispatch('reset-form-elements');
        
        \Log::info('🔥 PermissionAuditLog resetFilters - 屬性已重置', [
            'after_reset' => [
                'search' => $this->search,
                'actionFilter' => $this->actionFilter,
                'userFilter' => $this->userFilter,
                'permissionFilter' => $this->permissionFilter,
                'moduleFilter' => $this->moduleFilter,
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
                'ipFilter' => $this->ipFilter,
            ]
        ]);
        
        // 顯示成功訊息
        session()->flash('success', '篩選條件已重置');
        
        \Log::info('🔥 PermissionAuditLog resetFilters - 修復版本執行完成');
    
        
        $this->resetValidation();
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
     * 更新排序
     */
    public function updateSort(string $field): void
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
     * 顯示日誌詳情
     */
    public function showLogDetails(int $logId): void
    {
        $this->selectedLogId = $logId;
        $this->showDetails = true;
    }

    /**
     * 隱藏日誌詳情
     */
    public function hideLogDetails(): void
    {
        $this->selectedLogId = null;
        $this->showDetails = false;
    }

    /**
     * 匯出審計日誌
     */
    public function exportLogs(): void
    {
        $filters = $this->getFilters();
        $exportData = $this->auditService->exportAuditLogs($filters);
        
        $filename = 'permission_audit_logs_' . now()->format('Y-m-d_H-i-s') . '.json';
        
        $this->dispatch('download-file', [
            'content' => json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'filename' => $filename,
            'contentType' => 'application/json'
        ]);
        
        session()->flash('success', '審計日誌已匯出');
    }

    /**
     * 清理舊日誌
     */
    public function cleanupOldLogs(): void
    {
        $deletedCount = $this->auditService->cleanupOldAuditLogs(365);
        
        session()->flash('success', "已清理 {$deletedCount} 筆舊的審計日誌");
        $this->loadStats();
        $this->resetPage();
    }

    /**
     * 取得篩選條件
     */
    private function getFilters(): array
    {
        $filters = [
            'per_page' => $this->perPage,
            'page' => $this->getPage(),
        ];

        if (!empty($this->search)) {
            $filters['permission_name'] = $this->search;
        }

        if ($this->actionFilter !== 'all') {
            $filters['action'] = $this->actionFilter;
        }

        if (!empty($this->userFilter)) {
            $filters['username'] = $this->userFilter;
        }

        if (!empty($this->permissionFilter)) {
            $filters['permission_name'] = $this->permissionFilter;
        }

        if ($this->moduleFilter !== 'all') {
            $filters['module'] = $this->moduleFilter;
        }

        if (!empty($this->startDate)) {
            $filters['start_date'] = $this->startDate . ' 00:00:00';
        }

        if (!empty($this->endDate)) {
            $filters['end_date'] = $this->endDate . ' 23:59:59';
        }

        if (!empty($this->ipFilter)) {
            $filters['ip_address'] = $this->ipFilter;
        }

        return $filters;
    }

    /**
     * 計算屬性：取得審計日誌
     */
    public function getLogsProperty()
    {
        return $this->auditService->searchAuditLog($this->getFilters());
    }

    /**
     * 計算屬性：取得可用的操作類型
     */
    public function getAvailableActionsProperty(): array
    {
        return [
            'all' => '全部',
            'created' => '建立',
            'updated' => '更新',
            'deleted' => '刪除',
            'dependency_added' => '新增依賴',
            'dependency_removed' => '移除依賴',
            'dependency_synced' => '同步依賴',
            'role_assigned' => '指派角色',
            'role_unassigned' => '取消角色',
            'permission_exported' => '匯出權限',
            'permission_imported' => '匯入權限',
            'permission_test' => '權限測試',
        ];
    }

    /**
     * 計算屬性：取得可用的模組
     */
    public function getAvailableModulesProperty(): array
    {
        $modules = Permission::distinct('module')->pluck('module')->toArray();
        $moduleOptions = ['all' => '全部模組'];
        
        foreach ($modules as $module) {
            $moduleOptions[$module] = $module;
        }
        
        return $moduleOptions;
    }

    /**
     * 計算屬性：取得選中的日誌詳情
     */
    public function getSelectedLogProperty()
    {
        if (!$this->selectedLogId) {
            return null;
        }

        return \App\Models\PermissionAuditLog::with(['permission', 'user'])
                                            ->find($this->selectedLogId);
    }

    /**
     * 監聽屬性變更
     */
    public function updated($propertyName): void
    {
        // 當篩選條件變更時重設分頁
        if (in_array($propertyName, [
            'search', 'actionFilter', 'userFilter', 'permissionFilter', 
            'moduleFilter', 'startDate', 'endDate', 'ipFilter'
        ])) {
            $this->resetPage();
        }
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.permissions.permission-audit-log', [
            'logs' => $this->logs,
            'availableActions' => $this->availableActions,
            'availableModules' => $this->availableModules,
            'selectedLog' => $this->selectedLog,
        ]);
    }
}
