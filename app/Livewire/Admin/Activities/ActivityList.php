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
        
        // 設定預設日期範圍（最近7天）
        if (empty($this->dateFrom)) {
            $this->dateFrom = Carbon::now()->subDays(7)->format('Y-m-d');
        }
        if (empty($this->dateTo)) {
            $this->dateTo = Carbon::now()->format('Y-m-d');
        }
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
                Activity::TYPE_LOGIN => '登入',
                Activity::TYPE_LOGOUT => '登出',
                Activity::TYPE_CREATE_USER => '建立使用者',
                Activity::TYPE_UPDATE_USER => '更新使用者',
                Activity::TYPE_DELETE_USER => '刪除使用者',
                Activity::TYPE_CREATE_ROLE => '建立角色',
                Activity::TYPE_UPDATE_ROLE => '更新角色',
                Activity::TYPE_DELETE_ROLE => '刪除角色',
                Activity::TYPE_ASSIGN_ROLE => '指派角色',
                Activity::TYPE_REMOVE_ROLE => '移除角色',
                Activity::TYPE_UPDATE_PERMISSIONS => '更新權限',
                Activity::TYPE_VIEW_DASHBOARD => '檢視儀表板',
                Activity::TYPE_EXPORT_DATA => '匯出資料',
                Activity::TYPE_QUICK_ACTION => '快速操作',
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
        $this->resetPage();
        $this->loadedPages = 1; // 重置載入頁數
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
        $this->dispatch('open-activity-detail', activityId: $activityId);
    }

    /**
     * 匯出活動記錄
     */
    public function exportActivities(): void
    {
        try {
            $filters = $this->getFilters();
            $filePath = $this->activityRepository->exportActivities($filters, $this->exportFormat);
            
            $this->dispatch('download-file', filePath: $filePath);
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
     * 清除所有篩選條件
     */
    public function clearFilters(): void
    {
        $this->search = '';
        $this->dateFrom = Carbon::now()->subDays(7)->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
        $this->userFilter = '';
        $this->typeFilter = '';
        $this->moduleFilter = '';
        $this->resultFilter = '';
        $this->ipFilter = '';
        $this->riskLevelFilter = '';
        $this->selectedActivities = [];
        $this->selectAll = false;
        $this->loadedPages = 1;
        $this->resetPage();
    }

    /**
     * 切換篩選面板顯示
     */
    public function toggleFilters(): void
    {
        $this->showFilters = !$this->showFilters;
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
        $this->dispatch('download-file', filePath: $filePath);
        
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
    public function render()
    {
        return view('livewire.admin.activities.activity-list', [
            'activities' => $this->activities,
            'filterOptions' => $this->filterOptions,
            'stats' => $this->stats,
        ]);
    }
}