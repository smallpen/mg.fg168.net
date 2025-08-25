<?php

namespace App\Livewire\Admin\Activities;

use App\Livewire\Admin\AdminComponent;
use App\Models\Activity;
use App\Repositories\Contracts\ActivityRepositoryInterface;
use App\Services\ActivityExportService;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

/**
 * 活動記錄匯出元件
 * 
 * 提供多格式匯出、範圍設定、批量處理和進度追蹤功能
 */
class ActivityExport extends AdminComponent
{
    /**
     * 匯出格式選項
     */
    public string $exportFormat = 'csv';
    public array $availableFormats = [
        'csv' => 'CSV (Excel 相容)',
        'json' => 'JSON (程式處理)',
        'pdf' => 'PDF (報告列印)'
    ];

    /**
     * 匯出範圍設定
     */
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $timeRange = '7d';
    public array $timeRangeOptions = [
        '1d' => '最近 1 天',
        '7d' => '最近 7 天',
        '30d' => '最近 30 天',
        '90d' => '最近 90 天',
        'custom' => '自訂範圍'
    ];

    /**
     * 篩選條件
     */
    public string $userFilter = '';
    public string $typeFilter = '';
    public string $moduleFilter = '';
    public string $resultFilter = '';
    public string $ipFilter = '';
    public string $riskLevelFilter = '';
    public bool $securityEventsOnly = false;

    /**
     * 匯出選項
     */
    public bool $includeUserDetails = true;
    public bool $includeProperties = true;
    public bool $includeRelatedData = false;
    public int $batchSize = 1000;
    public int $maxRecords = 10000;

    /**
     * 進度追蹤
     */
    public bool $isExporting = false;
    public int $exportProgress = 0;
    public string $exportStatus = '';
    public ?string $exportJobId = null;
    public ?string $downloadUrl = null;
    public array $exportHistory = [];

    /**
     * 統計資訊
     */
    public int $totalRecords = 0;
    public int $estimatedSize = 0;
    public string $estimatedTime = '';

    /**
     * 依賴注入
     */
    protected ActivityRepositoryInterface $activityRepository;
    protected ActivityExportService $exportService;

    /**
     * 初始化元件
     */
    public function mount(): void
    {
        $this->authorize('activity_logs.export');
        
        $this->activityRepository = app(ActivityRepositoryInterface::class);
        $this->exportService = app(ActivityExportService::class);
        
        // 設定預設日期範圍
        $this->dateTo = now()->format('Y-m-d');
        $this->dateFrom = now()->subDays(7)->format('Y-m-d');
        
        // 載入匯出歷史
        $this->loadExportHistory();
        
        // 更新統計資訊
        $this->updateStatistics();
    }

    /**
     * 計算屬性：篩選條件
     */
    public function getFiltersProperty(): array
    {
        $filters = [];

        // 時間範圍
        if ($this->timeRange === 'custom') {
            if ($this->dateFrom) {
                $filters['date_from'] = $this->dateFrom;
            }
            if ($this->dateTo) {
                $filters['date_to'] = $this->dateTo;
            }
        } else {
            $days = $this->parseDays($this->timeRange);
            $filters['date_from'] = now()->subDays($days)->format('Y-m-d');
            $filters['date_to'] = now()->format('Y-m-d');
        }

        // 其他篩選條件
        if ($this->userFilter) {
            $filters['user_id'] = $this->userFilter;
        }
        if ($this->typeFilter) {
            $filters['type'] = $this->typeFilter;
        }
        if ($this->moduleFilter) {
            $filters['module'] = $this->moduleFilter;
        }
        if ($this->resultFilter) {
            $filters['result'] = $this->resultFilter;
        }
        if ($this->ipFilter) {
            $filters['ip_address'] = $this->ipFilter;
        }
        if ($this->riskLevelFilter) {
            $filters['risk_level'] = $this->riskLevelFilter;
        }
        if ($this->securityEventsOnly) {
            $filters['security_events_only'] = true;
        }

        return $filters;
    }

    /**
     * 計算屬性：可用的使用者選項
     */
    public function getUserOptionsProperty(): array
    {
        return Activity::with('user:id,username,name')
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->get()
            ->pluck('user.name', 'user_id')
            ->toArray();
    }

    /**
     * 計算屬性：可用的類型選項
     */
    public function getTypeOptionsProperty(): array
    {
        return Activity::distinct('type')
            ->whereNotNull('type')
            ->orderBy('type')
            ->pluck('type')
            ->mapWithKeys(fn($type) => [$type => $this->getTypeDisplayName($type)])
            ->toArray();
    }

    /**
     * 計算屬性：可用的模組選項
     */
    public function getModuleOptionsProperty(): array
    {
        return Activity::distinct('module')
            ->whereNotNull('module')
            ->orderBy('module')
            ->pluck('module')
            ->mapWithKeys(fn($module) => [$module => ucfirst($module)])
            ->toArray();
    }

    /**
     * 更新時間範圍
     */
    public function updatedTimeRange(): void
    {
        if ($this->timeRange !== 'custom') {
            $days = $this->parseDays($this->timeRange);
            $this->dateFrom = now()->subDays($days)->format('Y-m-d');
            $this->dateTo = now()->format('Y-m-d');
        }
        
        $this->updateStatistics();
    }

    /**
     * 更新篩選條件時重新計算統計
     */
    public function updated($propertyName): void
    {
        if (in_array($propertyName, [
            'dateFrom', 'dateTo', 'userFilter', 'typeFilter', 
            'moduleFilter', 'resultFilter', 'ipFilter', 
            'riskLevelFilter', 'securityEventsOnly'
        ])) {
            $this->updateStatistics();
        }
    }

    /**
     * 開始匯出
     */
    public function startExport(): void
    {
        $this->authorize('activity_logs.export');

        // 驗證設定
        if (!$this->validateExportSettings()) {
            return;
        }

        // 檢查記錄數量限制
        if ($this->totalRecords > $this->maxRecords) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => "匯出記錄數量 ({$this->totalRecords}) 超過限制 ({$this->maxRecords})，請縮小範圍或使用篩選條件。"
            ]);
            return;
        }

        try {
            $this->isExporting = true;
            $this->exportProgress = 0;
            $this->exportStatus = '準備匯出...';
            $this->downloadUrl = null;

            // 建立匯出工作
            $exportConfig = [
                'format' => $this->exportFormat,
                'filters' => $this->filters,
                'options' => [
                    'include_user_details' => $this->includeUserDetails,
                    'include_properties' => $this->includeProperties,
                    'include_related_data' => $this->includeRelatedData,
                    'batch_size' => $this->batchSize,
                ],
                'user_id' => auth()->id(),
            ];

            if ($this->totalRecords <= $this->batchSize) {
                // 小量資料直接匯出
                $this->performDirectExport($exportConfig);
            } else {
                // 大量資料使用佇列匯出
                $this->performBatchExport($exportConfig);
            }

        } catch (\Exception $e) {
            $this->handleExportError($e);
        }
    }

    /**
     * 取消匯出
     */
    public function cancelExport(): void
    {
        if ($this->exportJobId) {
            $this->exportService->cancelExport($this->exportJobId);
        }

        $this->resetExportState();
        
        $this->dispatch('notify', [
            'type' => 'info',
            'message' => '匯出已取消'
        ]);
    }

    /**
     * 下載匯出檔案
     */
    public function downloadExport(): void
    {
        if (!$this->downloadUrl) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => '下載連結不存在'
            ]);
            return;
        }

        $this->dispatch('download-file', url: $this->downloadUrl);
        
        // 記錄下載操作
        Activity::log('download_export', '下載活動記錄匯出檔案', [
            'module' => 'activities',
            'properties' => [
                'format' => $this->exportFormat,
                'download_url' => $this->downloadUrl,
            ],
        ]);
    }

    /**
     * 清除匯出歷史
     */
    public function clearExportHistory(): void
    {
        $this->exportService->clearUserExportHistory(auth()->id());
        $this->loadExportHistory();
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => '匯出歷史已清除'
        ]);
    }

    /**
     * 重設篩選條件
     */
    public function resetFilters(): void
    {
        $this->userFilter = '';
        $this->typeFilter = '';
        $this->moduleFilter = '';
        $this->resultFilter = '';
        $this->ipFilter = '';
        $this->riskLevelFilter = '';
        $this->securityEventsOnly = false;
        $this->timeRange = '7d';
        
        $this->updatedTimeRange();
    }

    /**
     * 監聽匯出進度更新
     */
    #[On('export-progress-updated')]
    public function updateExportProgress(array $data): void
    {
        if ($data['job_id'] === $this->exportJobId) {
            $this->exportProgress = $data['progress'];
            $this->exportStatus = $data['status'];
            
            if ($data['completed']) {
                $this->exportCompleted($data);
            }
        }
    }

    /**
     * 監聽匯出完成
     */
    #[On('export-completed')]
    public function exportCompleted(array $data): void
    {
        if ($data['job_id'] === $this->exportJobId) {
            $this->isExporting = false;
            $this->exportProgress = 100;
            $this->exportStatus = '匯出完成';
            $this->downloadUrl = $data['download_url'];
            
            $this->loadExportHistory();
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => '匯出完成，可以下載檔案'
            ]);
        }
    }

    /**
     * 監聽匯出失敗
     */
    #[On('export-failed')]
    public function exportFailed(array $data): void
    {
        if ($data['job_id'] === $this->exportJobId) {
            $this->handleExportError(new \Exception($data['error']));
        }
    }

    /**
     * 執行直接匯出（小量資料）
     */
    protected function performDirectExport(array $config): void
    {
        $this->exportStatus = '正在匯出資料...';
        
        $result = $this->exportService->exportDirect($config);
        
        $this->isExporting = false;
        $this->exportProgress = 100;
        $this->exportStatus = '匯出完成';
        $this->downloadUrl = $result['download_url'];
        
        $this->loadExportHistory();
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => '匯出完成'
        ]);
    }

    /**
     * 執行批量匯出（大量資料）
     */
    protected function performBatchExport(array $config): void
    {
        $this->exportStatus = '建立匯出工作...';
        
        $jobId = $this->exportService->exportBatch($config);
        $this->exportJobId = $jobId;
        
        $this->exportStatus = '正在佇列中等待處理...';
    }

    /**
     * 處理匯出錯誤
     */
    protected function handleExportError(\Exception $e): void
    {
        $this->resetExportState();
        
        $this->dispatch('notify', [
            'type' => 'error',
            'message' => '匯出失敗：' . $e->getMessage()
        ]);
        
        // 記錄錯誤
        Activity::log('export_failed', '活動記錄匯出失敗', [
            'module' => 'activities',
            'properties' => [
                'error' => $e->getMessage(),
                'format' => $this->exportFormat,
                'filters' => $this->filters,
            ],
        ]);
    }

    /**
     * 重設匯出狀態
     */
    protected function resetExportState(): void
    {
        $this->isExporting = false;
        $this->exportProgress = 0;
        $this->exportStatus = '';
        $this->exportJobId = null;
        $this->downloadUrl = null;
    }

    /**
     * 驗證匯出設定
     */
    protected function validateExportSettings(): bool
    {
        // 檢查日期範圍
        if ($this->timeRange === 'custom') {
            if (!$this->dateFrom || !$this->dateTo) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => '請選擇有效的日期範圍'
                ]);
                return false;
            }
            
            if (Carbon::parse($this->dateFrom) > Carbon::parse($this->dateTo)) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => '開始日期不能晚於結束日期'
                ]);
                return false;
            }
        }

        // 檢查匯出格式
        if (!in_array($this->exportFormat, array_keys($this->availableFormats))) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => '不支援的匯出格式'
            ]);
            return false;
        }

        return true;
    }

    /**
     * 更新統計資訊
     */
    protected function updateStatistics(): void
    {
        try {
            $query = Activity::query();
            $this->applyFiltersToQuery($query, $this->filters);
            
            $this->totalRecords = $query->count();
            $this->estimatedSize = $this->calculateEstimatedSize();
            $this->estimatedTime = $this->calculateEstimatedTime();
            
        } catch (\Exception $e) {
            $this->totalRecords = 0;
            $this->estimatedSize = 0;
            $this->estimatedTime = '';
        }
    }

    /**
     * 載入匯出歷史
     */
    protected function loadExportHistory(): void
    {
        $this->exportHistory = $this->exportService->getUserExportHistory(auth()->id(), 10);
    }

    /**
     * 應用篩選條件到查詢
     */
    protected function applyFiltersToQuery($query, array $filters): void
    {
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }
        
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }
        
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        
        if (!empty($filters['module'])) {
            $query->where('module', $filters['module']);
        }
        
        if (!empty($filters['result'])) {
            $query->where('result', $filters['result']);
        }
        
        if (!empty($filters['ip_address'])) {
            $query->where('ip_address', 'like', "%{$filters['ip_address']}%");
        }
        
        if (!empty($filters['risk_level'])) {
            if ($filters['risk_level'] === 'high') {
                $query->where('risk_level', '>=', 7);
            } else {
                $query->where('risk_level', $filters['risk_level']);
            }
        }
        
        if (!empty($filters['security_events_only'])) {
            $query->where('risk_level', '>=', 5);
        }
    }

    /**
     * 計算預估檔案大小
     */
    protected function calculateEstimatedSize(): int
    {
        if ($this->totalRecords === 0) {
            return 0;
        }

        // 根據格式和選項估算每筆記錄的大小（位元組）
        $bytesPerRecord = match ($this->exportFormat) {
            'csv' => 200,
            'json' => $this->includeProperties ? 800 : 400,
            'pdf' => 300,
            default => 300,
        };

        if ($this->includeUserDetails) {
            $bytesPerRecord += 100;
        }

        if ($this->includeRelatedData) {
            $bytesPerRecord += 200;
        }

        return $this->totalRecords * $bytesPerRecord;
    }

    /**
     * 計算預估處理時間
     */
    protected function calculateEstimatedTime(): string
    {
        if ($this->totalRecords === 0) {
            return '0 秒';
        }

        // 估算每秒可處理的記錄數
        $recordsPerSecond = match ($this->exportFormat) {
            'csv' => 1000,
            'json' => 500,
            'pdf' => 100,
            default => 500,
        };

        $seconds = ceil($this->totalRecords / $recordsPerSecond);

        if ($seconds < 60) {
            return "{$seconds} 秒";
        } elseif ($seconds < 3600) {
            $minutes = ceil($seconds / 60);
            return "{$minutes} 分鐘";
        } else {
            $hours = ceil($seconds / 3600);
            return "{$hours} 小時";
        }
    }

    /**
     * 解析時間範圍為天數
     */
    protected function parseDays(string $timeRange): int
    {
        return match ($timeRange) {
            '1d' => 1,
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            default => 7,
        };
    }

    /**
     * 取得類型顯示名稱
     */
    protected function getTypeDisplayName(string $type): string
    {
        return match ($type) {
            'login' => '登入',
            'logout' => '登出',
            'create_user' => '建立使用者',
            'update_user' => '更新使用者',
            'delete_user' => '刪除使用者',
            'create_role' => '建立角色',
            'update_role' => '更新角色',
            'delete_role' => '刪除角色',
            'assign_role' => '指派角色',
            'remove_role' => '移除角色',
            'create_permission' => '建立權限',
            'update_permission' => '更新權限',
            'delete_permission' => '刪除權限',
            'view_dashboard' => '檢視儀表板',
            'export_data' => '匯出資料',
            'system_setting' => '系統設定',
            'security_event' => '安全事件',
            default => $type,
        };
    }

    /**
     * 格式化檔案大小
     */
    public function formatFileSize(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor(log($bytes, 1024));
        
        return sprintf('%.1f %s', $bytes / pow(1024, $factor), $units[$factor]);
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.activities.activity-export');
    }
}