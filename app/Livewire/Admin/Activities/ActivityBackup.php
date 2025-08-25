<?php

namespace App\Livewire\Admin\Activities;

use App\Services\ActivityBackupService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

/**
 * 活動記錄備份管理元件
 */
class ActivityBackup extends Component
{
    use WithFileUploads;

    // 備份設定
    public string $backupDateFrom = '';
    public string $backupDateTo = '';
    public bool $includeDeleted = false;
    
    // 還原設定
    public $restoreFile;
    public bool $replaceExisting = false;
    public bool $validateIntegrity = true;
    
    // 清理設定
    public int $cleanupDays = 90;
    
    // 狀態
    public bool $isBackingUp = false;
    public bool $isRestoring = false;
    public bool $isCleaning = false;
    public array $backupResult = [];
    public array $restoreResult = [];
    public array $cleanupResult = [];
    public string $activeTab = 'backup';

    protected ActivityBackupService $backupService;

    public function boot(ActivityBackupService $backupService): void
    {
        $this->backupService = $backupService;
    }

    public function mount(): void
    {
        $this->authorize('activity_logs.view');
        
        // 設定預設日期範圍（最近 30 天）
        $this->backupDateTo = now()->format('Y-m-d');
        $this->backupDateFrom = now()->subDays(30)->format('Y-m-d');
    }

    /**
     * 取得可用的備份列表
     */
    public function getBackupsProperty(): array
    {
        return $this->backupService->listActivityBackups();
    }

    /**
     * 執行活動記錄備份
     */
    public function createBackup(): void
    {
        $this->authorize('activity_logs.export');
        
        $this->validate([
            'backupDateFrom' => 'required|date|before_or_equal:backupDateTo',
            'backupDateTo' => 'required|date|after_or_equal:backupDateFrom',
        ], [
            'backupDateFrom.required' => '請選擇開始日期',
            'backupDateFrom.before_or_equal' => '開始日期不能晚於結束日期',
            'backupDateTo.required' => '請選擇結束日期',
            'backupDateTo.after_or_equal' => '結束日期不能早於開始日期',
        ]);

        $this->isBackingUp = true;
        $this->backupResult = [];

        try {
            $options = [
                'date_from' => $this->backupDateFrom,
                'date_to' => $this->backupDateTo,
                'include_deleted' => $this->includeDeleted,
            ];

            $this->backupResult = $this->backupService->backupActivityLogs($options);

            if ($this->backupResult['success']) {
                $this->dispatch('backup-completed', [
                    'message' => '活動記錄備份完成',
                    'backup_name' => $this->backupResult['backup_name']
                ]);
            } else {
                $this->dispatch('backup-failed', [
                    'message' => '備份失敗: ' . ($this->backupResult['error'] ?? '未知錯誤')
                ]);
            }

        } catch (\Exception $e) {
            $this->backupResult = [
                'success' => false,
                'error' => $e->getMessage()
            ];
            
            $this->dispatch('backup-failed', [
                'message' => '備份過程中發生錯誤: ' . $e->getMessage()
            ]);
        }

        $this->isBackingUp = false;
    }

    /**
     * 還原活動記錄備份
     */
    public function restoreBackup(string $backupPath): void
    {
        $this->authorize('activity_logs.delete'); // 需要刪除權限才能還原
        
        $this->isRestoring = true;
        $this->restoreResult = [];

        try {
            $options = [
                'replace_existing' => $this->replaceExisting,
                'validate_integrity' => $this->validateIntegrity,
            ];

            $this->restoreResult = $this->backupService->restoreActivityLogs($backupPath, $options);

            if ($this->restoreResult['success']) {
                $this->dispatch('restore-completed', [
                    'message' => '活動記錄還原完成',
                    'imported_count' => $this->restoreResult['data_import']['imported_count'] ?? 0
                ]);
            } else {
                $this->dispatch('restore-failed', [
                    'message' => '還原失敗: ' . ($this->restoreResult['error'] ?? '未知錯誤')
                ]);
            }

        } catch (\Exception $e) {
            $this->restoreResult = [
                'success' => false,
                'error' => $e->getMessage()
            ];
            
            $this->dispatch('restore-failed', [
                'message' => '還原過程中發生錯誤: ' . $e->getMessage()
            ]);
        }

        $this->isRestoring = false;
    }

    /**
     * 上傳並還原備份檔案
     */
    public function uploadAndRestore(): void
    {
        $this->authorize('activity_logs.delete');
        
        $this->validate([
            'restoreFile' => 'required|file|mimes:encrypted|max:102400', // 最大 100MB
        ], [
            'restoreFile.required' => '請選擇要還原的備份檔案',
            'restoreFile.mimes' => '只能上傳 .encrypted 格式的備份檔案',
            'restoreFile.max' => '檔案大小不能超過 100MB',
        ]);

        try {
            // 儲存上傳的檔案
            $uploadedPath = $this->restoreFile->store('temp_restore', 'local');
            $fullPath = storage_path('app/' . $uploadedPath);

            // 執行還原
            $this->restoreBackup($fullPath);

            // 清理臨時檔案
            Storage::disk('local')->delete($uploadedPath);

        } catch (\Exception $e) {
            $this->dispatch('restore-failed', [
                'message' => '檔案上傳失敗: ' . $e->getMessage()
            ]);
        }

        $this->restoreFile = null;
    }

    /**
     * 清理舊備份
     */
    public function cleanupOldBackups(): void
    {
        $this->authorize('activity_logs.delete');
        
        $this->validate([
            'cleanupDays' => 'required|integer|min:1|max:3650',
        ], [
            'cleanupDays.required' => '請輸入保留天數',
            'cleanupDays.integer' => '保留天數必須是整數',
            'cleanupDays.min' => '保留天數至少為 1 天',
            'cleanupDays.max' => '保留天數不能超過 10 年',
        ]);

        $this->isCleaning = true;
        $this->cleanupResult = [];

        try {
            $this->cleanupResult = $this->backupService->cleanupOldActivityBackups($this->cleanupDays);

            if ($this->cleanupResult['success']) {
                $this->dispatch('cleanup-completed', [
                    'message' => "清理完成，刪除了 {$this->cleanupResult['deleted_count']} 個舊備份",
                    'deleted_count' => $this->cleanupResult['deleted_count']
                ]);
            } else {
                $this->dispatch('cleanup-failed', [
                    'message' => '清理過程中發生錯誤'
                ]);
            }

        } catch (\Exception $e) {
            $this->cleanupResult = [
                'success' => false,
                'error' => $e->getMessage()
            ];
            
            $this->dispatch('cleanup-failed', [
                'message' => '清理過程中發生錯誤: ' . $e->getMessage()
            ]);
        }

        $this->isCleaning = false;
    }

    /**
     * 下載備份檔案
     */
    public function downloadBackup(string $filename): void
    {
        $this->authorize('activity_logs.export');
        
        $backupPath = storage_path('backups/activity_logs/' . $filename);
        
        if (!File::exists($backupPath)) {
            $this->dispatch('download-failed', [
                'message' => '備份檔案不存在'
            ]);
            return;
        }

        return response()->download($backupPath);
    }

    /**
     * 刪除備份檔案
     */
    public function deleteBackup(string $filename): void
    {
        $this->authorize('activity_logs.delete');
        
        $backupPath = storage_path('backups/activity_logs/' . $filename);
        
        if (File::exists($backupPath)) {
            File::delete($backupPath);
            
            $this->dispatch('backup-deleted', [
                'message' => '備份檔案已刪除'
            ]);
        } else {
            $this->dispatch('delete-failed', [
                'message' => '備份檔案不存在'
            ]);
        }
    }

    /**
     * 驗證備份檔案完整性
     */
    public function verifyBackup(string $filename): void
    {
        $this->authorize('activity_logs.view');
        
        $backupPath = storage_path('backups/activity_logs/' . $filename);
        
        if (!File::exists($backupPath)) {
            $this->dispatch('verify-failed', [
                'message' => '備份檔案不存在'
            ]);
            return;
        }

        try {
            $result = $this->backupService->verifyBackupIntegrity($backupPath);
            
            if ($result['success']) {
                $this->dispatch('verify-completed', [
                    'message' => '備份檔案完整性驗證通過',
                    'checksum' => substr($result['checksum'], 0, 16) . '...'
                ]);
            } else {
                $this->dispatch('verify-failed', [
                    'message' => '完整性驗證失敗: ' . $result['error']
                ]);
            }

        } catch (\Exception $e) {
            $this->dispatch('verify-failed', [
                'message' => '驗證過程中發生錯誤: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * 切換分頁
     */
    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    /**
     * 格式化檔案大小
     */
    public function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }

    /**
     * 格式化日期時間
     */
    public function formatDateTime(string $datetime): string
    {
        return Carbon::parse($datetime)->format('Y-m-d H:i:s');
    }

    public function render()
    {
        return view('livewire.admin.activities.activity-backup');
    }
}