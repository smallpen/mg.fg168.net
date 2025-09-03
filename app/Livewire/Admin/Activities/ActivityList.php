<?php

namespace App\Livewire\Admin\Activities;

use App\Livewire\Admin\AdminComponent;
use App\Models\Activity;
use App\Models\User;
use App\Repositories\ActivityRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use Carbon\Carbon;

/**
 * 活動記錄列表元件
 * 
 * 提供活動記錄的顯示、搜尋、篩選、分頁和即時更新功能
 */
class ActivityList extends AdminComponent
{
    use WithPagination;

    /**
     * 篩選條件
     */
    public string $search = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $userFilter = '';
    public string $typeFilter = '';
    public string $moduleFilter = '';
    public string $resultFilter = '';
    public string $ipFilter = '';
    public string $riskLevelFilter = '';

    /**
     * 顯示設定
     */
    public int $perPage = 50;
    public array $perPageOptions = [25, 50, 100, 200];
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';
    public bool $realTimeMode = false;

    /**
     * 批量操作
     */
    public array $selectedActivities = [];
    public string $bulkAction = '';
    public bool $selectAll = false;

    /**
     * 其他設定
     */
    public bool $showFilters = false;
    public bool $showExportModal = false;
    public string $exportFormat = 'csv';
    public bool $infiniteScroll = false;
    public int $loadedPages = 1;

    /**
     * 活動記錄資料存取層
     */
    protected ActivityRepository $activityRepository;

    /**
     * 初始化元件
     */
    public function boot(ActivityRepository $activityRepository): void
    {
        $this->activityRepository = $activityRepository;
    }

    /**
     * 掛載元件時執行
     */
    public function mount(): void
    {
        // 檢查權限
        // TODO: Fix authorization issue
        // $this->authorize('system.logs');
        
        // 不再預設設定日期範圍，讓使用者自行選擇
        // 這樣可以避免重置按鈕一進入就顯示
    }

    /**
     * 計算屬性：取得活動記錄分頁資料
     */
    public function getActivitiesProperty(): LengthAwarePaginator
    {
        $filters = $this->getFilters();
        
        if ($this->infiniteScroll) {
            // 無限滾動模式：載入多頁資料
            $perPage = $this->perPage * $this->loadedPages;
            return $this->activityRepository->getPaginatedActivities($filters, $perPage);
        }
        
        return $this->activityRepository->getPaginatedActivities($filters, $this->perPage);
    }

    /**
     * 計算屬性：取得篩選選項
     */
    public function getFilterOptionsProperty(): array
    {
        return [
            'users' => User::select('id', 'username', 'name')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'types' => [
                // 使用者相關活動
                'user_login' => '使用者登入',
                'user_logout' => '使用者登出',
                'user_updated' => '使用者更新',
                'login_failed' => '登入失敗',
                
                // 系統活動
                'view_activity_logs' => '檢視活動記錄',
                'view_activity_stats' => '檢視活動統計',
                'view_activity_monitor' => '檢視活動監控',
                'view_activity_export' => '檢視活動匯出',
                'security_monitor_access' => '安全監控存取',
                
                // 資料操作
                'export_data' => '匯出資料',
                'download_activity_export' => '下載活動匯出',
                'download_export' => '下載匯出',
                
                // 系統事件
                'cache_hit' => '快取命中',
                'cache_missed' => '快取未命中',
                'slow_query' => '慢查詢',
                'server_error' => '伺服器錯誤',
                
                // 存取控制
                'forbidden_access' => '禁止存取',
                'not_found_access' => '找不到資源',
                
                // 安全相關
                'activity_flagged' => '活動標記',
            ],
            'modules' => [
                Activity::MODULE_AUTH => '認證',
                Activity::MODULE_USERS => '使用者管理',
                Activity::MODULE_ROLES => '角色管理',
                Activity::MODULE_PERMISSIONS => '權限管理',
                Activity::MODULE_DASHBOARD => '儀表板',
                Activity::MODULE_SYSTEM => '系統',
            ],
            'results' => [
                'success' => '成功',
                'failed' => '失敗',
                'warning' => '警告',
            ],
            'riskLevels' => [
                'low' => '低風險 (1-2)',
                'medium' => '中風險 (3-5)',
                'high' => '高風險 (6-8)',
                'critical' => '極高風險 (9-10)',
            ],
        ];
    }

    /**
     * 計算屬性：取得統計資料
     */
    public function getStatsProperty(): array
    {
        $filters = $this->getFilters();
        $activities = $this->activities;
        
        return [
            'total' => $activities->total(),
            'security_events' => $activities->getCollection()->where('is_security_event', true)->count(),
            'high_risk' => $activities->getCollection()->where('risk_level', '>=', 6)->count(),
            'failed' => $activities->getCollection()->where('result', 'failed')->count(),
            'unique_users' => $activities->getCollection()->pluck('user_id')->unique()->count(),
            'unique_ips' => $activities->getCollection()->pluck('ip_address')->unique()->count(),
        ];
    }

    /**
     * 更新搜尋條件時重置分頁
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->loadedPages = 1;
    }

    /**
     * 更新篩選條件時重置分頁
     */
    public function updatedDateFrom(): void
    {
        $this->resetPage();
        $this->loadedPages = 1;
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
        $this->loadedPages = 1;
    }

    public function updatedUserFilter(): void
    {
        $this->resetPage();
        $this->loadedPages = 1;
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
        $this->loadedPages = 1;
    }

    public function updatedModuleFilter(): void
    {
        $this->resetPage();
        $this->loadedPages = 1;
    }

    public function updatedResultFilter(): void
    {
        $this->resetPage();
        $this->loadedPages = 1;
    }

    public function updatedIpFilter(): void
    {
        $this->resetPage();
        $this->loadedPages = 1;
    }

    public function updatedRiskLevelFilter(): void
    {
        $this->resetPage();
        $this->loadedPages = 1; // 重置載入頁數
    }

    /**
     * 更新每頁顯示數量
     */
    public function updatedPerPage(): void
    {
        try {
            // 驗證 perPage 值
            if (!in_array($this->perPage, $this->perPageOptions)) {
                $this->perPage = 50; // 重置為預設值
            }
            
            $this->resetPage();
            $this->loadedPages = 1; // 重置載入頁數
            
            // 發送更新事件
            $this->dispatch('per-page-updated', perPage: $this->perPage);
            
        } catch (\Exception $e) {
            logger()->error('Error updating perPage', [
                'error' => $e->getMessage(),
                'perPage' => $this->perPage
            ]);
            
            // 重置為預設值
            $this->perPage = 50;
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
     * 更新全選狀態
     */
    public function updatedSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selectedActivities = $this->activities->getCollection()->pluck('id')->toArray();
        } else {
            $this->selectedActivities = [];
        }
    }

    /**
     * 檢視活動詳情
     */
    public function viewDetail(int $activityId): void
    {
        // 觸發 ActivityDetail 元件的 viewDetail 事件
        $this->dispatch('viewDetail', activityId: $activityId);
    }

    /**
     * 標記活動為可疑
     */
    public function flagAsSuspicious(int $activityId): void
    {
        try {
            $activity = Activity::find($activityId);
            
            if (!$activity) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => '找不到指定的活動記錄'
                ]);
                return;
            }

            // 檢查當前標記狀態
            $isSuspicious = $activity->risk_level >= 7;
            $newSuspiciousState = !$isSuspicious;
            
            // 更新風險等級
            $newRiskLevel = $newSuspiciousState ? 
                max($activity->risk_level, 7) : 
                min($activity->risk_level, 6);

            // 由於活動記錄不允許修改，我們建立一個新的標記記錄
            Activity::log('activity_flagged', 
                ($newSuspiciousState ? '標記' : '取消標記') . "活動為可疑 #{$activity->id}", [
                'module' => 'security',
                'properties' => [
                    'flagged_activity_id' => $activity->id,
                    'flagged_as_suspicious' => $newSuspiciousState,
                    'original_risk_level' => $activity->risk_level,
                    'new_risk_level' => $newRiskLevel,
                    'reason' => 'manual_review',
                ],
                'risk_level' => 5,
            ]);

            $message = $newSuspiciousState ? '已標記為可疑活動' : '已取消可疑標記';
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => $message
            ]);

            // 重新載入活動列表以反映變更
            $this->resetPage();

        } catch (\Exception $e) {
            logger()->error('標記可疑活動失敗', [
                'activity_id' => $activityId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => '標記操作失敗：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 顯示匯出模態框
     */
    public function showExportModal(): void
    {
        $this->showExportModal = true;
    }

    /**
     * 隱藏匯出模態框
     */
    public function hideExportModal(): void
    {
        $this->showExportModal = false;
    }

    /**
     * 匯出活動記錄
     */
    public function exportActivities(): void
    {
        try {
            $filters = $this->getFilters();
            $filePath = $this->activityRepository->exportActivities($filters, $this->exportFormat);
            
            // 從 filePath 中提取檔案名稱
            $filename = basename($filePath);
            
            // 使用正確的下載路由
            $downloadUrl = route('admin.activities.download-export', ['filename' => $filename]);
            
            $this->dispatch('download-file', [
                'url' => $downloadUrl,
                'filename' => $filename
            ]);
            $this->showExportModal = false;
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => '活動記錄匯出成功！'
            ]);

            // 記錄匯出操作
            Activity::log(Activity::TYPE_EXPORT_DATA, '匯出活動記錄', [
                'module' => Activity::MODULE_SYSTEM,
                'properties' => [
                    'format' => $this->exportFormat,
                    'filters' => $filters,
                    'total_records' => $this->activities->total(),
                ],
            ]);

        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => '匯出失敗：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 切換即時監控模式
     */
    public function toggleRealTime(): void
    {
        $this->realTimeMode = !$this->realTimeMode;
        
        // 通知前端即時監控狀態變化
        $this->dispatch('real-time-mode-changed', enabled: $this->realTimeMode);
        
        if ($this->realTimeMode) {
            $this->dispatch('notify', [
                'type' => 'info',
                'message' => '已開啟即時監控模式'
            ]);
        } else {
            $this->dispatch('notify', [
                'type' => 'info',
                'message' => '已關閉即時監控模式'
            ]);
        }
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
                    'dateFrom' => $this->dateFrom ?? '',
                    'dateTo' => $this->dateTo ?? '',
                    'userFilter' => $this->userFilter ?? '',
                    'typeFilter' => $this->typeFilter ?? '',
                    'moduleFilter' => $this->moduleFilter ?? '',
                    'resultFilter' => $this->resultFilter ?? '',
                    'ipFilter' => $this->ipFilter ?? '',
                    'riskLevelFilter' => $this->riskLevelFilter ?? '',
                ]
            ]);
            
            // 重置所有篩選條件
            $this->search = '';
            $this->dateFrom = '';
            $this->dateTo = '';
            $this->userFilter = '';
            $this->typeFilter = '';
            $this->moduleFilter = '';
            $this->resultFilter = '';
            $this->ipFilter = '';
            $this->riskLevelFilter = '';
            
            // 重置其他相關屬性
            $this->selectedActivities = [];
            $this->bulkAction = '';
            $this->selectAll = false;
            
            // 清除快取
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
                    
                    const dateInputs = document.querySelectorAll(\'input[wire\\\\:model\\\\.live="dateFrom"], input[wire\\\\:model\\\\.live="dateTo"]\');
                    dateInputs.forEach(input => {
                        input.value = "";
                        input.dispatchEvent(new Event("input", { bubbles: true }));
                    });
                    
                    const filterSelects = document.querySelectorAll(\'select[wire\\\\:model\\\\.live*="Filter"]\');
                    filterSelects.forEach(select => {
                        select.value = "";
                        select.dispatchEvent(new Event("change", { bubbles: true }));
                    });
                    
                    const ipInputs = document.querySelectorAll(\'input[wire\\\\:model\\\\.live="ipFilter"]\');
                    ipInputs.forEach(input => {
                        input.value = "";
                        input.dispatchEvent(new Event("input", { bubbles: true }));
                    });
                    
                    console.log("✅ 活動記錄表單元素已強制同步");
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
                    'dateFrom' => $this->dateFrom,
                    'dateTo' => $this->dateTo,
                    'userFilter' => $this->userFilter,
                    'typeFilter' => $this->typeFilter,
                    'moduleFilter' => $this->moduleFilter,
                    'resultFilter' => $this->resultFilter,
                    'ipFilter' => $this->ipFilter,
                    'riskLevelFilter' => $this->riskLevelFilter,
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
     * 清除所有篩選條件（向後相容）
     */
    public function clearFilters(): void
    {
        $this->resetFilters();
    }

    /**
     * 切換篩選面板顯示
     */
    public function toggleFilters(): void
    {
        $this->showFilters = !$this->showFilters;
        
        // 記錄篩選面板切換
        \Log::info('篩選面板切換', [
            'showFilters' => $this->showFilters,
            'user' => auth()->user()->username ?? 'unknown'
        ]);
    }

    /**
     * 切換載入模式（分頁 vs 無限滾動）
     */
    public function toggleLoadMode(): void
    {
        $this->infiniteScroll = !$this->infiniteScroll;
        $this->loadedPages = 1;
        $this->resetPage();
        
        $mode = $this->infiniteScroll ? '無限滾動' : '分頁模式';
        $this->dispatch('notify', [
            'type' => 'info',
            'message' => "已切換至{$mode}"
        ]);
    }

    /**
     * 載入更多記錄（無限滾動）
     */
    public function loadMore(): void
    {
        if (!$this->infiniteScroll) {
            return;
        }

        $this->loadedPages++;
        
        $this->dispatch('notify', [
            'type' => 'info',
            'message' => '載入更多記錄中...'
        ]);
    }

    /**
     * 排序
     */
    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        
        $this->loadedPages = 1;
        $this->resetPage();
    }

    /**
     * 執行批量操作
     */
    public function executeBulkAction(): void
    {
        if (empty($this->selectedActivities) || empty($this->bulkAction)) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => '請選擇要操作的記錄和操作類型'
            ]);
            return;
        }

        try {
            switch ($this->bulkAction) {
                case 'export':
                    $this->bulkExport();
                    break;
                case 'mark_reviewed':
                    $this->bulkMarkReviewed();
                    break;
                default:
                    throw new \InvalidArgumentException('不支援的批量操作');
            }

            $this->selectedActivities = [];
            $this->selectAll = false;
            $this->bulkAction = '';

        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => '批量操作失敗：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 批量匯出選中的記錄
     */
    protected function bulkExport(): void
    {
        $filters = $this->getFilters();
        $filters['selected_ids'] = $this->selectedActivities;
        
        $filePath = $this->activityRepository->exportActivities($filters, 'csv');
        
        // 從 filePath 中提取檔案名稱
        $filename = basename($filePath);
        
        // 使用正確的下載路由
        $downloadUrl = route('admin.activities.download-export', ['filename' => $filename]);
        
        $this->dispatch('download-file', [
            'url' => $downloadUrl,
            'filename' => $filename
        ]);
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => '選中記錄匯出成功！'
        ]);
    }

    /**
     * 批量標記為已審查
     */
    protected function bulkMarkReviewed(): void
    {
        Activity::whereIn('id', $this->selectedActivities)
            ->update(['reviewed_at' => now(), 'reviewed_by' => auth()->id()]);
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => '已標記 ' . count($this->selectedActivities) . ' 筆記錄為已審查'
        ]);
    }

    /**
     * 即時更新活動記錄
     */
    #[On('activity-logged')]
    public function refreshActivities(): void
    {
        if ($this->realTimeMode) {
            $this->resetPage();
            $this->dispatch('notify', [
                'type' => 'info',
                'message' => '發現新的活動記錄'
            ]);
        }
    }

    /**
     * 安全警報處理
     */
    #[On('security-alert')]
    public function handleSecurityAlert(array $alert): void
    {
        $this->dispatch('notify', [
            'type' => 'error',
            'message' => '安全警報：' . $alert['message'],
            'timeout' => 10000
        ]);
        
        if ($this->realTimeMode) {
            $this->resetPage();
        }
    }

    /**
     * 手動重新整理活動記錄
     */
    #[On('refresh-activities')]
    public function refreshActivitiesManually(): void
    {
        $this->resetPage();
        $this->dispatch('notify', [
            'type' => 'info',
            'message' => '活動記錄已更新'
        ]);
    }

    /**
     * 無限滾動載入更多
     */
    #[On('load-more-activities')]
    public function loadMoreActivities(): void
    {
        if ($this->infiniteScroll) {
            $this->loadMore();
        }
    }

    /**
     * 清除所有篩選（快捷鍵）
     */
    #[On('clear-all-filters')]
    public function clearAllFilters(): void
    {
        $this->clearFilters();
        $this->dispatch('notify', [
            'type' => 'info',
            'message' => '已清除所有篩選條件'
        ]);
    }

    /**
     * 取得篩選條件陣列
     */
    protected function getFilters(): array
    {
        $filters = [
            'search' => $this->search,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'user_id' => $this->userFilter,
            'type' => $this->typeFilter,
            'module' => $this->moduleFilter,
            'result' => $this->resultFilter,
            'ip_address' => $this->ipFilter,
            'sort_field' => $this->sortField,
            'sort_direction' => $this->sortDirection,
        ];

        // 處理風險等級篩選
        if ($this->riskLevelFilter) {
            switch ($this->riskLevelFilter) {
                case 'low':
                    $filters['risk_level_min'] = 1;
                    $filters['risk_level_max'] = 2;
                    break;
                case 'medium':
                    $filters['risk_level_min'] = 3;
                    $filters['risk_level_max'] = 5;
                    break;
                case 'high':
                    $filters['risk_level_min'] = 6;
                    $filters['risk_level_max'] = 8;
                    break;
                case 'critical':
                    $filters['risk_level_min'] = 9;
                    $filters['risk_level_max'] = 10;
                    break;
            }
        }

        return array_filter($filters, function ($value) {
            return $value !== '' && $value !== null;
        });
    }

    /**
     * 渲染元件
     */
    
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




    public function render()
    {
        return view('livewire.admin.activities.activity-list', [
            'activities' => $this->activities,
            'filterOptions' => $this->filterOptions,
            'stats' => $this->stats,
        ]);
    }
}