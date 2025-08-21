<?php

namespace App\Livewire\Admin\Permissions;

use App\Services\PermissionImportExportService;
use App\Services\AuditLogService;
use App\Traits\HandlesLivewireErrors;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * 權限匯入匯出 Livewire 元件
 * 
 * 處理權限資料的匯入匯出功能
 */
class PermissionImportExport extends Component
{
    use WithFileUploads, HandlesLivewireErrors;

    // 匯出相關屬性
    public array $exportFilters = [
        'modules' => [],
        'types' => [],
        'usage_status' => 'all',
        'permission_ids' => [],
    ];
    public bool $exportInProgress = false;

    // 匯入相關屬性
    public $importFile = null;
    public array $importOptions = [
        'conflict_resolution' => 'skip',
        'validate_dependencies' => true,
        'create_missing_dependencies' => false,
        'dry_run' => false,
    ];
    public bool $importInProgress = false;
    public array $importResults = [];
    public array $importPreview = [];
    public bool $showImportPreview = false;
    public bool $showImportResults = false;

    // 衝突處理相關屬性
    public array $conflicts = [];
    public bool $showConflictResolution = false;
    public array $conflictResolutions = [];

    protected PermissionImportExportService $importExportService;
    protected AuditLogService $auditService;

    /**
     * 元件初始化
     */
    public function boot(
        PermissionImportExportService $importExportService,
        AuditLogService $auditService
    ): void {
        $this->importExportService = $importExportService;
        $this->auditService = $auditService;
    }

    /**
     * 元件掛載
     */
    public function mount(): void
    {
        // 檢查權限
        if (!auth()->user()->hasPermission('permissions.import') && 
            !auth()->user()->hasPermission('permissions.export')) {
            abort(403, '您沒有權限存取此功能');
        }
    }

    /**
     * 匯出權限
     */
    public function exportPermissions(): void
    {
        try {
            // 檢查權限
            if (!auth()->user()->hasPermission('permissions.export')) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => '您沒有匯出權限的權限'
                ]);
                return;
            }

            $this->exportInProgress = true;

            // 執行匯出
            $exportData = $this->importExportService->exportPermissions($this->exportFilters);

            // 生成檔案名稱
            $filename = 'permissions_export_' . date('Y-m-d_H-i-s') . '.json';

            // 觸發下載
            $this->dispatch('download-json', [
                'filename' => $filename,
                'data' => $exportData,
            ]);

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => "成功匯出 {$exportData['metadata']['total_permissions']} 個權限"
            ]);

        } catch (\Exception $e) {
            $this->handleError($e, '匯出權限時發生錯誤');
        } finally {
            $this->exportInProgress = false;
        }
    }

    /**
     * 處理檔案上傳
     */
    public function updatedImportFile(): void
    {
        $this->validate([
            'importFile' => 'required|file|mimes:json|max:10240', // 最大 10MB
        ]);

        try {
            // 讀取並解析 JSON 檔案
            $content = file_get_contents($this->importFile->getRealPath());
            $importData = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('JSON 檔案格式錯誤：' . json_last_error_msg());
            }

            // 生成預覽
            $this->generateImportPreview($importData);

        } catch (\Exception $e) {
            $this->importFile = null;
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '檔案處理失敗：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 生成匯入預覽
     */
    protected function generateImportPreview(array $importData): void
    {
        try {
            // 執行試運行匯入
            $previewOptions = array_merge($this->importOptions, ['dry_run' => true]);
            $results = $this->importExportService->importPermissions($importData, $previewOptions);

            $this->importPreview = [
                'metadata' => $importData['metadata'] ?? [],
                'summary' => [
                    'total_permissions' => count($importData['permissions'] ?? []),
                    'will_create' => $results['created'],
                    'will_update' => $results['updated'],
                    'will_skip' => $results['skipped'],
                    'has_errors' => !empty($results['errors']),
                    'has_conflicts' => !empty($results['conflicts']),
                ],
                'conflicts' => $results['conflicts'],
                'errors' => $results['errors'],
                'warnings' => $results['warnings'],
            ];

            $this->conflicts = $results['conflicts'];
            $this->showImportPreview = true;

            // 如果有衝突，初始化衝突解決方案
            if (!empty($this->conflicts)) {
                $this->initializeConflictResolutions();
            }

        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '預覽生成失敗：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 初始化衝突解決方案
     */
    protected function initializeConflictResolutions(): void
    {
        foreach ($this->conflicts as $index => $conflict) {
            $this->conflictResolutions[$index] = $this->importOptions['conflict_resolution'];
        }
    }

    /**
     * 執行匯入
     */
    public function executeImport(): void
    {
        try {
            // 檢查權限
            if (!auth()->user()->hasPermission('permissions.import')) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => '您沒有匯入權限的權限'
                ]);
                return;
            }

            if (!$this->importFile) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => '請先選擇要匯入的檔案'
                ]);
                return;
            }

            $this->importInProgress = true;

            // 讀取檔案內容
            $content = file_get_contents($this->importFile->getRealPath());
            $importData = json_decode($content, true);

            // 應用衝突解決方案
            $finalOptions = $this->importOptions;
            if (!empty($this->conflictResolutions)) {
                // 這裡可以根據需要實作更細緻的衝突解決邏輯
                // 目前使用全域設定
            }

            // 執行匯入
            $results = $this->importExportService->importPermissions($importData, $finalOptions);

            // 生成報告
            $this->importResults = $this->importExportService->generateImportReport($results);
            $this->showImportResults = true;
            $this->showImportPreview = false;

            if ($results['success']) {
                $this->dispatch('show-toast', [
                    'type' => 'success',
                    'message' => "匯入完成！建立 {$results['created']} 個，更新 {$results['updated']} 個，跳過 {$results['skipped']} 個權限"
                ]);

                // 通知其他元件更新
                $this->dispatch('permissions-imported');
            } else {
                $this->dispatch('show-toast', [
                    'type' => 'warning',
                    'message' => '匯入完成但有錯誤，請檢查詳細報告'
                ]);
            }

        } catch (ValidationException $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '資料驗證失敗：' . implode(', ', $e->validator->errors()->all())
            ]);
        } catch (\Exception $e) {
            $this->handleError($e, '匯入權限時發生錯誤');
        } finally {
            $this->importInProgress = false;
        }
    }

    /**
     * 取消匯入
     */
    public function cancelImport(): void
    {
        $this->reset([
            'importFile',
            'importPreview',
            'showImportPreview',
            'conflicts',
            'conflictResolutions',
            'showConflictResolution'
        ]);
    }

    /**
     * 關閉匯入結果
     */
    public function closeImportResults(): void
    {
        $this->reset([
            'importResults',
            'showImportResults',
            'importFile',
            'importPreview',
            'showImportPreview'
        ]);
    }

    /**
     * 更新匯出篩選條件
     */
    public function updateExportFilters(string $type, $value): void
    {
        switch ($type) {
            case 'modules':
                if (in_array($value, $this->exportFilters['modules'])) {
                    $this->exportFilters['modules'] = array_diff($this->exportFilters['modules'], [$value]);
                } else {
                    $this->exportFilters['modules'][] = $value;
                }
                break;
            case 'types':
                if (in_array($value, $this->exportFilters['types'])) {
                    $this->exportFilters['types'] = array_diff($this->exportFilters['types'], [$value]);
                } else {
                    $this->exportFilters['types'][] = $value;
                }
                break;
            case 'usage_status':
                $this->exportFilters['usage_status'] = $value;
                break;
        }
    }

    /**
     * 重置匯出篩選條件
     */
    public function resetExportFilters(): void
    {
        $this->exportFilters = [
            'modules' => [],
            'types' => [],
            'usage_status' => 'all',
            'permission_ids' => [],
        ];
    }

    /**
     * 切換衝突解決顯示
     */
    public function toggleConflictResolution(): void
    {
        $this->showConflictResolution = !$this->showConflictResolution;
    }

    /**
     * 更新衝突解決方案
     */
    public function updateConflictResolution(int $index, string $resolution): void
    {
        $this->conflictResolutions[$index] = $resolution;
    }

    /**
     * 批量設定衝突解決方案
     */
    public function setBulkConflictResolution(string $resolution): void
    {
        foreach ($this->conflictResolutions as $index => $current) {
            $this->conflictResolutions[$index] = $resolution;
        }
    }

    /**
     * 取得可用的模組列表
     */
    public function getAvailableModulesProperty(): array
    {
        return \App\Models\Permission::distinct()->orderBy('module')->pluck('module')->toArray();
    }

    /**
     * 取得可用的權限類型列表
     */
    public function getAvailableTypesProperty(): array
    {
        return \App\Models\Permission::distinct()->orderBy('type')->pluck('type')->toArray();
    }

    /**
     * 取得衝突解決選項
     */
    public function getConflictResolutionOptionsProperty(): array
    {
        return [
            'skip' => '跳過（保留現有）',
            'update' => '更新（覆蓋現有）',
            'merge' => '合併（智慧合併）',
        ];
    }

    /**
     * 取得使用狀態選項
     */
    public function getUsageStatusOptionsProperty(): array
    {
        return [
            'all' => '全部權限',
            'used' => '已使用權限',
            'unused' => '未使用權限',
        ];
    }

    /**
     * 監聽匯出開始事件
     */
    #[On('export-permissions-started')]
    public function handleExportStarted(array $filters = []): void
    {
        // 合併傳入的篩選條件
        if (!empty($filters)) {
            $this->exportFilters = array_merge($this->exportFilters, $filters);
        }
        $this->exportPermissions();
    }

    /**
     * 監聽匯入模態開啟事件
     */
    #[On('open-import-modal')]
    public function handleImportModalOpen(): void
    {
        // 重置匯入狀態
        $this->reset([
            'importFile',
            'importPreview',
            'showImportPreview',
            'importResults',
            'showImportResults',
            'conflicts',
            'conflictResolutions'
        ]);
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.permissions.permission-import-export', [
            'availableModules' => $this->availableModules,
            'availableTypes' => $this->availableTypes,
            'conflictResolutionOptions' => $this->conflictResolutionOptions,
            'usageStatusOptions' => $this->usageStatusOptions,
        ]);
    }
}