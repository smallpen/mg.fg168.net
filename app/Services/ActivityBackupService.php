<?php

namespace App\Services;

use App\Models\Activity;
use App\Services\ActivityIntegrityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;
use ZipArchive;

/**
 * 活動記錄備份服務
 * 
 * 負責活動記錄的備份、還原和完整性驗證
 */
class ActivityBackupService
{
    protected ActivityIntegrityService $integrityService;
    protected ActivityLogger $activityLogger;

    public function __construct(
        ActivityIntegrityService $integrityService,
        ActivityLogger $activityLogger
    ) {
        $this->integrityService = $integrityService;
        $this->activityLogger = $activityLogger;
    }

    /**
     * 執行活動記錄備份
     *
     * @param array $options 備份選項
     * @return array
     */
    public function backupActivityLogs(array $options = []): array
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupName = "activity_logs_backup_{$timestamp}";
        
        $this->activityLogger->logSystemActivity(
            'activity_backup_started',
            '開始執行活動記錄備份',
            ['backup_name' => $backupName]
        );

        $results = [
            'started_at' => now()->toISOString(),
            'backup_name' => $backupName,
            'data_export' => null,
            'compression' => null,
            'encryption' => null,
            'integrity_check' => null,
            'completed_at' => null,
            'success' => false,
        ];

        try {
            // 1. 匯出活動記錄資料
            $results['data_export'] = $this->exportActivityData($options);
            
            if (!$results['data_export']['success']) {
                throw new \Exception('活動記錄資料匯出失敗');
            }

            // 2. 壓縮備份檔案
            $results['compression'] = $this->compressBackupData(
                $results['data_export']['file_path'],
                $backupName
            );

            if (!$results['compression']['success']) {
                throw new \Exception('備份檔案壓縮失敗');
            }

            // 3. 加密備份檔案
            $results['encryption'] = $this->encryptBackupFile(
                $results['compression']['compressed_path']
            );

            if (!$results['encryption']['success']) {
                throw new \Exception('備份檔案加密失敗');
            }

            // 4. 驗證備份完整性
            $results['integrity_check'] = $this->verifyBackupIntegrity(
                $results['encryption']['encrypted_path']
            );

            $results['completed_at'] = now()->toISOString();
            $results['success'] = true;

            // 清理臨時檔案
            $this->cleanupTemporaryFiles([
                $results['data_export']['file_path'],
                $results['compression']['compressed_path']
            ]);

            $this->activityLogger->logSystemActivity(
                'activity_backup_completed',
                '活動記錄備份完成',
                $results
            );

        } catch (\Exception $e) {
            $results['error'] = $e->getMessage();
            $results['completed_at'] = now()->toISOString();
            
            $this->activityLogger->logSystemActivity(
                'activity_backup_failed',
                '活動記錄備份失敗',
                array_merge($results, ['error' => $e->getMessage()])
            );
        }

        return $results;
    }

    /**
     * 匯出活動記錄資料
     *
     * @param array $options
     * @return array
     */
    protected function exportActivityData(array $options = []): array
    {
        try {
            $dateFrom = $options['date_from'] ?? null;
            $dateTo = $options['date_to'] ?? null;
            $includeDeleted = $options['include_deleted'] ?? false;
            
            // 建立查詢
            $query = Activity::query();
            
            if ($dateFrom) {
                $query->where('created_at', '>=', Carbon::parse($dateFrom));
            }
            
            if ($dateTo) {
                $query->where('created_at', '<=', Carbon::parse($dateTo));
            }

            if ($includeDeleted) {
                $query->withTrashed();
            }

            // 取得活動記錄
            $activities = $query->orderBy('created_at', 'asc')->get();
            
            // 準備匯出資料
            $exportData = [
                'metadata' => [
                    'export_version' => '1.0',
                    'exported_at' => now()->toISOString(),
                    'total_records' => $activities->count(),
                    'date_range' => [
                        'from' => $dateFrom,
                        'to' => $dateTo,
                    ],
                    'include_deleted' => $includeDeleted,
                    'integrity_enabled' => config('activity-log.integrity.enabled', true),
                ],
                'activities' => $activities->toArray(),
            ];

            // 建立備份目錄
            $backupDir = storage_path('backups/activity_logs');
            if (!File::exists($backupDir)) {
                File::makeDirectory($backupDir, 0755, true);
            }

            // 儲存為 JSON 檔案
            $filename = 'activity_data_' . now()->format('Y-m-d_H-i-s') . '.json';
            $filePath = $backupDir . '/' . $filename;
            
            File::put($filePath, json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return [
                'success' => true,
                'filename' => $filename,
                'file_path' => $filePath,
                'record_count' => $activities->count(),
                'file_size' => File::size($filePath),
                'file_size_mb' => round(File::size($filePath) / 1024 / 1024, 2),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 壓縮備份資料
     *
     * @param string $dataFilePath
     * @param string $backupName
     * @return array
     */
    protected function compressBackupData(string $dataFilePath, string $backupName): array
    {
        try {
            $compressedPath = dirname($dataFilePath) . '/' . $backupName . '.zip';
            
            $zip = new ZipArchive();
            $result = $zip->open($compressedPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            
            if ($result !== TRUE) {
                throw new \Exception("無法建立壓縮檔案: {$result}");
            }

            // 新增資料檔案到壓縮檔
            $zip->addFile($dataFilePath, basename($dataFilePath));
            
            // 新增備份資訊檔案
            $backupInfo = [
                'backup_name' => $backupName,
                'created_at' => now()->toISOString(),
                'original_size' => File::size($dataFilePath),
                'checksum' => hash_file('sha256', $dataFilePath),
            ];
            
            $zip->addFromString('backup_info.json', json_encode($backupInfo, JSON_PRETTY_PRINT));
            
            $zip->close();

            $compressedSize = File::size($compressedPath);
            $originalSize = File::size($dataFilePath);
            
            return [
                'success' => true,
                'compressed_path' => $compressedPath,
                'original_size' => $originalSize,
                'compressed_size' => $compressedSize,
                'compression_ratio' => round((1 - $compressedSize / $originalSize) * 100, 2),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 加密備份檔案
     *
     * @param string $filePath
     * @return array
     */
    protected function encryptBackupFile(string $filePath): array
    {
        try {
            $encryptedPath = $filePath . '.encrypted';
            
            // 讀取檔案內容
            $fileContent = File::get($filePath);
            
            // 加密檔案內容
            $encryptedContent = Crypt::encrypt($fileContent);
            
            // 儲存加密檔案
            File::put($encryptedPath, $encryptedContent);
            
            // 生成檔案雜湊值
            $checksum = hash_file('sha256', $encryptedPath);
            
            return [
                'success' => true,
                'encrypted_path' => $encryptedPath,
                'original_size' => File::size($filePath),
                'encrypted_size' => File::size($encryptedPath),
                'checksum' => $checksum,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 驗證備份完整性
     *
     * @param string $backupPath
     * @return array
     */
    public function verifyBackupIntegrity(string $backupPath): array
    {
        try {
            if (!File::exists($backupPath)) {
                throw new \Exception('備份檔案不存在');
            }

            // 計算檔案雜湊值
            $currentChecksum = hash_file('sha256', $backupPath);
            
            // 嘗試解密並驗證內容
            $encryptedContent = File::get($backupPath);
            $decryptedContent = Crypt::decrypt($encryptedContent);
            
            // 驗證解密後的內容是否為有效的壓縮檔
            $tempPath = sys_get_temp_dir() . '/temp_backup_verify.zip';
            File::put($tempPath, $decryptedContent);
            
            $zip = new ZipArchive();
            $result = $zip->open($tempPath);
            
            if ($result !== TRUE) {
                throw new \Exception('備份檔案格式無效');
            }

            // 檢查必要檔案是否存在
            $requiredFiles = ['backup_info.json'];
            $hasDataFile = false;
            
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                
                if (str_ends_with($filename, '.json') && $filename !== 'backup_info.json') {
                    $hasDataFile = true;
                }
            }
            
            if (!$hasDataFile) {
                throw new \Exception('備份檔案中缺少資料檔案');
            }

            // 讀取備份資訊
            $backupInfoContent = $zip->getFromName('backup_info.json');
            if ($backupInfoContent === false) {
                throw new \Exception('無法讀取備份資訊');
            }
            
            $backupInfo = json_decode($backupInfoContent, true);
            if (!$backupInfo) {
                throw new \Exception('備份資訊格式無效');
            }

            $zip->close();
            File::delete($tempPath);

            return [
                'success' => true,
                'checksum' => $currentChecksum,
                'backup_info' => $backupInfo,
                'file_size' => File::size($backupPath),
                'verified_at' => now()->toISOString(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 還原活動記錄備份
     *
     * @param string $backupPath
     * @param array $options
     * @return array
     */
    public function restoreActivityLogs(string $backupPath, array $options = []): array
    {
        $this->activityLogger->logSystemActivity(
            'activity_restore_started',
            '開始還原活動記錄備份',
            ['backup_file' => basename($backupPath)]
        );

        $results = [
            'started_at' => now()->toISOString(),
            'backup_file' => basename($backupPath),
            'integrity_check' => null,
            'decryption' => null,
            'data_import' => null,
            'completed_at' => null,
            'success' => false,
        ];

        try {
            // 1. 驗證備份完整性
            $results['integrity_check'] = $this->verifyBackupIntegrity($backupPath);
            
            if (!$results['integrity_check']['success']) {
                throw new \Exception('備份檔案完整性驗證失敗');
            }

            // 2. 解密備份檔案
            $results['decryption'] = $this->decryptBackupFile($backupPath);
            
            if (!$results['decryption']['success']) {
                throw new \Exception('備份檔案解密失敗');
            }

            // 3. 匯入活動記錄資料
            $results['data_import'] = $this->importActivityData(
                $results['decryption']['decrypted_path'],
                $options
            );

            if (!$results['data_import']['success']) {
                throw new \Exception('活動記錄資料匯入失敗');
            }

            $results['completed_at'] = now()->toISOString();
            $results['success'] = true;

            // 清理臨時檔案
            $this->cleanupTemporaryFiles([
                $results['decryption']['decrypted_path']
            ]);

            $this->activityLogger->logSystemActivity(
                'activity_restore_completed',
                '活動記錄備份還原完成',
                $results
            );

        } catch (\Exception $e) {
            $results['error'] = $e->getMessage();
            $results['completed_at'] = now()->toISOString();
            
            $this->activityLogger->logSystemActivity(
                'activity_restore_failed',
                '活動記錄備份還原失敗',
                array_merge($results, ['error' => $e->getMessage()])
            );
        }

        return $results;
    }

    /**
     * 解密備份檔案
     *
     * @param string $encryptedPath
     * @return array
     */
    protected function decryptBackupFile(string $encryptedPath): array
    {
        try {
            $decryptedPath = str_replace('.encrypted', '', $encryptedPath);
            
            // 讀取加密內容
            $encryptedContent = File::get($encryptedPath);
            
            // 解密內容
            $decryptedContent = Crypt::decrypt($encryptedContent);
            
            // 儲存解密檔案
            File::put($decryptedPath, $decryptedContent);
            
            return [
                'success' => true,
                'decrypted_path' => $decryptedPath,
                'file_size' => File::size($decryptedPath),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 匯入活動記錄資料
     *
     * @param string $backupPath
     * @param array $options
     * @return array
     */
    protected function importActivityData(string $backupPath, array $options = []): array
    {
        try {
            $replaceExisting = $options['replace_existing'] ?? false;
            $validateIntegrity = $options['validate_integrity'] ?? true;
            
            // 解壓縮備份檔案
            $zip = new ZipArchive();
            $result = $zip->open($backupPath);
            
            if ($result !== TRUE) {
                throw new \Exception("無法開啟備份檔案: {$result}");
            }

            // 找到資料檔案
            $dataFile = null;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                if (str_ends_with($filename, '.json') && $filename !== 'backup_info.json') {
                    $dataFile = $filename;
                    break;
                }
            }

            if (!$dataFile) {
                throw new \Exception('備份檔案中找不到資料檔案');
            }

            // 解壓縮資料檔案
            $tempDir = sys_get_temp_dir() . '/activity_restore_' . uniqid();
            File::makeDirectory($tempDir);
            
            $zip->extractTo($tempDir);
            $zip->close();

            // 讀取活動記錄資料
            $dataPath = $tempDir . '/' . $dataFile;
            $backupData = json_decode(File::get($dataPath), true);
            
            if (!$backupData || !isset($backupData['activities'])) {
                throw new \Exception('備份資料格式無效');
            }

            $activities = $backupData['activities'];
            $importedCount = 0;
            $skippedCount = 0;
            $errorCount = 0;

            DB::beginTransaction();

            foreach ($activities as $activityData) {
                try {
                    // 檢查活動是否已存在
                    if (!$replaceExisting) {
                        $existing = Activity::where('id', $activityData['id'])->first();
                        if ($existing) {
                            $skippedCount++;
                            continue;
                        }
                    }

                    // 驗證完整性（如果啟用）
                    if ($validateIntegrity && isset($activityData['signature'])) {
                        $expectedSignature = $this->integrityService->generateSignature(
                            collect($activityData)->except(['signature', 'updated_at'])->toArray()
                        );
                        
                        if ($expectedSignature !== $activityData['signature']) {
                            Log::warning('Activity integrity verification failed', [
                                'activity_id' => $activityData['id'],
                                'expected' => $expectedSignature,
                                'actual' => $activityData['signature']
                            ]);
                        }
                    }

                    // 建立或更新活動記錄
                    Activity::updateOrCreate(
                        ['id' => $activityData['id']],
                        $activityData
                    );

                    $importedCount++;

                } catch (\Exception $e) {
                    $errorCount++;
                    Log::error('Failed to import activity', [
                        'activity_id' => $activityData['id'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();

            // 清理臨時檔案
            File::deleteDirectory($tempDir);

            return [
                'success' => true,
                'total_records' => count($activities),
                'imported_count' => $importedCount,
                'skipped_count' => $skippedCount,
                'error_count' => $errorCount,
                'backup_metadata' => $backupData['metadata'] ?? null,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 列出可用的活動記錄備份
     *
     * @return array
     */
    public function listActivityBackups(): array
    {
        $backupDir = storage_path('backups/activity_logs');
        
        if (!File::exists($backupDir)) {
            return [];
        }

        $backups = [];
        $files = File::files($backupDir);
        
        foreach ($files as $file) {
            if (str_ends_with($file->getFilename(), '.encrypted')) {
                $backups[] = [
                    'filename' => $file->getFilename(),
                    'path' => $file->getPathname(),
                    'size' => File::size($file->getPathname()),
                    'size_mb' => round(File::size($file->getPathname()) / 1024 / 1024, 2),
                    'created_at' => Carbon::createFromTimestamp(
                        File::lastModified($file->getPathname())
                    )->toISOString(),
                    'checksum' => hash_file('sha256', $file->getPathname()),
                ];
            }
        }

        // 按建立時間排序（最新的在前）
        usort($backups, function ($a, $b) {
            return strcmp($b['created_at'], $a['created_at']);
        });

        return $backups;
    }

    /**
     * 清理舊的活動記錄備份
     *
     * @param int $daysToKeep
     * @return array
     */
    public function cleanupOldActivityBackups(int $daysToKeep = 90): array
    {
        $backupDir = storage_path('backups/activity_logs');
        
        if (!File::exists($backupDir)) {
            return [
                'success' => true,
                'deleted_count' => 0,
                'message' => '備份目錄不存在'
            ];
        }

        $cutoffDate = Carbon::now()->subDays($daysToKeep);
        $deletedCount = 0;
        $deletedSize = 0;
        $errors = [];

        $files = File::files($backupDir);
        
        foreach ($files as $file) {
            $fileTime = Carbon::createFromTimestamp(File::lastModified($file->getPathname()));
            
            if ($fileTime->lt($cutoffDate)) {
                try {
                    $fileSize = File::size($file->getPathname());
                    File::delete($file->getPathname());
                    $deletedCount++;
                    $deletedSize += $fileSize;
                    
                    $this->activityLogger->logSystemActivity(
                        'activity_backup_deleted',
                        '刪除過期的活動記錄備份',
                        [
                            'filename' => $file->getFilename(),
                            'file_size' => $fileSize,
                            'age_days' => $fileTime->diffInDays(now())
                        ]
                    );
                    
                } catch (\Exception $e) {
                    $errors[] = "無法刪除 {$file->getFilename()}: " . $e->getMessage();
                }
            }
        }

        return [
            'success' => empty($errors),
            'deleted_count' => $deletedCount,
            'deleted_size_mb' => round($deletedSize / 1024 / 1024, 2),
            'errors' => $errors,
        ];
    }

    /**
     * 清理臨時檔案
     *
     * @param array $filePaths
     * @return void
     */
    protected function cleanupTemporaryFiles(array $filePaths): void
    {
        foreach ($filePaths as $filePath) {
            if (File::exists($filePath)) {
                try {
                    File::delete($filePath);
                } catch (\Exception $e) {
                    Log::warning('Failed to cleanup temporary file', [
                        'file' => $filePath,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }
}