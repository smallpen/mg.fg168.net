<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Carbon\Carbon;

/**
 * 備份服務
 * 
 * 負責資料庫和檔案的自動備份與恢復
 */
class BackupService
{
    protected LoggingService $loggingService;

    public function __construct(LoggingService $loggingService)
    {
        $this->loggingService = $loggingService;
    }

    /**
     * 執行完整備份
     *
     * @return array
     */
    public function performFullBackup(): array
    {
        $this->loggingService->logBackupOperation('full_backup', 'started');

        $results = [
            'started_at' => now()->toISOString(),
            'database' => null,
            'files' => null,
            'cleanup' => null,
            'completed_at' => null,
            'success' => false,
        ];

        try {
            // 1. 備份資料庫
            $results['database'] = $this->backupDatabase();
            
            // 2. 備份重要檔案
            $results['files'] = $this->backupFiles();
            
            // 3. 清理舊備份
            $results['cleanup'] = $this->cleanupOldBackups();
            
            $results['completed_at'] = now()->toISOString();
            $results['success'] = $results['database']['success'] && $results['files']['success'];

            if ($results['success']) {
                $this->loggingService->logBackupOperation('full_backup', 'completed', $results);
            } else {
                $this->loggingService->logBackupOperation('full_backup', 'failed', $results);
            }

        } catch (\Exception $e) {
            $results['error'] = $e->getMessage();
            $results['completed_at'] = now()->toISOString();
            $this->loggingService->logBackupOperation('full_backup', 'failed', $results);
        }

        return $results;
    }

    /**
     * 備份資料庫
     *
     * @return array
     */
    public function backupDatabase(): array
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "database_backup_{$timestamp}.sql";
        $backupPath = storage_path("backups/database/{$filename}");

        // 確保備份目錄存在
        $backupDir = dirname($backupPath);
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        try {
            $dbConfig = config('database.connections.' . config('database.default'));
            
            $command = sprintf(
                'mysqldump --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers %s > %s',
                escapeshellarg($dbConfig['host']),
                escapeshellarg($dbConfig['port'] ?? 3306),
                escapeshellarg($dbConfig['username']),
                escapeshellarg($dbConfig['password']),
                escapeshellarg($dbConfig['database']),
                escapeshellarg($backupPath)
            );

            $result = Process::run($command);

            if ($result->successful()) {
                $fileSize = File::size($backupPath);
                
                // 壓縮備份檔案
                $compressedPath = $backupPath . '.gz';
                $compressResult = Process::run("gzip {$backupPath}");
                
                if ($compressResult->successful()) {
                    $compressedSize = File::size($compressedPath);
                    
                    return [
                        'success' => true,
                        'filename' => basename($compressedPath),
                        'path' => $compressedPath,
                        'original_size' => $fileSize,
                        'compressed_size' => $compressedSize,
                        'compression_ratio' => round((1 - $compressedSize / $fileSize) * 100, 2),
                        'created_at' => now()->toISOString(),
                    ];
                } else {
                    return [
                        'success' => true,
                        'filename' => basename($backupPath),
                        'path' => $backupPath,
                        'size' => $fileSize,
                        'created_at' => now()->toISOString(),
                        'compression_warning' => '壓縮失敗，保留原始檔案',
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'error' => $result->errorOutput(),
                    'command' => $command,
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 備份重要檔案
     *
     * @return array
     */
    public function backupFiles(): array
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "files_backup_{$timestamp}.tar.gz";
        $backupPath = storage_path("backups/files/{$filename}");

        // 確保備份目錄存在
        $backupDir = dirname($backupPath);
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        try {
            // 要備份的目錄和檔案
            $itemsToBackup = [
                storage_path('app'),
                storage_path('logs'),
                base_path('.env'),
                base_path('composer.json'),
                base_path('composer.lock'),
                base_path('package.json'),
                base_path('package-lock.json'),
            ];

            // 過濾存在的項目
            $existingItems = array_filter($itemsToBackup, function ($item) {
                return File::exists($item);
            });

            if (empty($existingItems)) {
                return [
                    'success' => false,
                    'error' => '沒有找到要備份的檔案',
                ];
            }

            // 建立 tar 指令
            $itemsList = implode(' ', array_map('escapeshellarg', $existingItems));
            $command = "tar -czf {$backupPath} -C " . base_path() . " " . 
                      implode(' ', array_map(function ($item) {
                          return '--transform=s|^' . preg_quote(base_path() . '/', '|') . '||' . 
                                 ' ' . escapeshellarg(str_replace(base_path() . '/', '', $item));
                      }, $existingItems));

            // 簡化的 tar 指令
            $command = "tar -czf {$backupPath} " . $itemsList;

            $result = Process::run($command);

            if ($result->successful()) {
                $fileSize = File::size($backupPath);
                
                return [
                    'success' => true,
                    'filename' => basename($backupPath),
                    'path' => $backupPath,
                    'size' => $fileSize,
                    'size_mb' => round($fileSize / 1024 / 1024, 2),
                    'items_backed_up' => count($existingItems),
                    'created_at' => now()->toISOString(),
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $result->errorOutput(),
                    'command' => $command,
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 清理舊備份
     *
     * @param int $daysToKeep 保留天數
     * @return array
     */
    public function cleanupOldBackups(int $daysToKeep = 30): array
    {
        $results = [
            'database_cleanup' => $this->cleanupOldDatabaseBackups($daysToKeep),
            'files_cleanup' => $this->cleanupOldFileBackups($daysToKeep),
        ];

        return $results;
    }

    /**
     * 清理舊的資料庫備份
     *
     * @param int $daysToKeep
     * @return array
     */
    protected function cleanupOldDatabaseBackups(int $daysToKeep): array
    {
        $backupDir = storage_path('backups/database');
        
        if (!File::exists($backupDir)) {
            return ['success' => true, 'deleted_count' => 0, 'message' => '備份目錄不存在'];
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
     * 清理舊的檔案備份
     *
     * @param int $daysToKeep
     * @return array
     */
    protected function cleanupOldFileBackups(int $daysToKeep): array
    {
        $backupDir = storage_path('backups/files');
        
        if (!File::exists($backupDir)) {
            return ['success' => true, 'deleted_count' => 0, 'message' => '備份目錄不存在'];
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
     * 列出可用的備份
     *
     * @return array
     */
    public function listAvailableBackups(): array
    {
        $backups = [
            'database' => [],
            'files' => [],
        ];

        // 列出資料庫備份
        $dbBackupDir = storage_path('backups/database');
        if (File::exists($dbBackupDir)) {
            $dbFiles = File::files($dbBackupDir);
            foreach ($dbFiles as $file) {
                $backups['database'][] = [
                    'filename' => $file->getFilename(),
                    'path' => $file->getPathname(),
                    'size' => File::size($file->getPathname()),
                    'size_mb' => round(File::size($file->getPathname()) / 1024 / 1024, 2),
                    'created_at' => Carbon::createFromTimestamp(File::lastModified($file->getPathname()))->toISOString(),
                ];
            }
        }

        // 列出檔案備份
        $filesBackupDir = storage_path('backups/files');
        if (File::exists($filesBackupDir)) {
            $fileBackups = File::files($filesBackupDir);
            foreach ($fileBackups as $file) {
                $backups['files'][] = [
                    'filename' => $file->getFilename(),
                    'path' => $file->getPathname(),
                    'size' => File::size($file->getPathname()),
                    'size_mb' => round(File::size($file->getPathname()) / 1024 / 1024, 2),
                    'created_at' => Carbon::createFromTimestamp(File::lastModified($file->getPathname()))->toISOString(),
                ];
            }
        }

        // 按建立時間排序（最新的在前）
        usort($backups['database'], function ($a, $b) {
            return strcmp($b['created_at'], $a['created_at']);
        });

        usort($backups['files'], function ($a, $b) {
            return strcmp($b['created_at'], $a['created_at']);
        });

        return $backups;
    }

    /**
     * 恢復資料庫備份
     *
     * @param string $backupPath 備份檔案路徑
     * @return array
     */
    public function restoreDatabase(string $backupPath): array
    {
        if (!File::exists($backupPath)) {
            return [
                'success' => false,
                'error' => '備份檔案不存在',
            ];
        }

        $this->loggingService->logBackupOperation('database_restore', 'started', [
            'backup_file' => basename($backupPath),
        ]);

        try {
            $dbConfig = config('database.connections.' . config('database.default'));
            
            // 如果是壓縮檔案，先解壓縮
            $sqlFile = $backupPath;
            if (str_ends_with($backupPath, '.gz')) {
                $sqlFile = str_replace('.gz', '', $backupPath);
                $decompressResult = Process::run("gunzip -c {$backupPath} > {$sqlFile}");
                
                if (!$decompressResult->successful()) {
                    return [
                        'success' => false,
                        'error' => '解壓縮失敗: ' . $decompressResult->errorOutput(),
                    ];
                }
            }

            $command = sprintf(
                'mysql --host=%s --port=%s --user=%s --password=%s %s < %s',
                escapeshellarg($dbConfig['host']),
                escapeshellarg($dbConfig['port'] ?? 3306),
                escapeshellarg($dbConfig['username']),
                escapeshellarg($dbConfig['password']),
                escapeshellarg($dbConfig['database']),
                escapeshellarg($sqlFile)
            );

            $result = Process::run($command);

            // 如果解壓縮了檔案，清理臨時檔案
            if ($sqlFile !== $backupPath && File::exists($sqlFile)) {
                File::delete($sqlFile);
            }

            if ($result->successful()) {
                $this->loggingService->logBackupOperation('database_restore', 'completed', [
                    'backup_file' => basename($backupPath),
                ]);

                return [
                    'success' => true,
                    'message' => '資料庫恢復成功',
                    'backup_file' => basename($backupPath),
                ];
            } else {
                $this->loggingService->logBackupOperation('database_restore', 'failed', [
                    'backup_file' => basename($backupPath),
                    'error' => $result->errorOutput(),
                ]);

                return [
                    'success' => false,
                    'error' => $result->errorOutput(),
                ];
            }

        } catch (\Exception $e) {
            $this->loggingService->logBackupOperation('database_restore', 'failed', [
                'backup_file' => basename($backupPath),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}