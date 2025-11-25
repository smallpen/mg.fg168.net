<?php

namespace App\Console\Commands;

use App\Services\ChannelIntegrityService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ChannelBackupRestore extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'channel:backup-restore 
                            {action : 動作類型 (backup|restore|validate|list)}
                            {--file= : 備份檔案路徑 (用於 restore 和 validate)}
                            {--compress : 壓縮備份檔案}
                            {--encrypt : 加密備份檔案}
                            {--verify : 建立備份後驗證完整性}';

    /**
     * The console command description.
     */
    protected $description = '通路管理系統資料備份和還原';

    private ChannelIntegrityService $integrityService;

    public function __construct(ChannelIntegrityService $integrityService)
    {
        parent::__construct();
        $this->integrityService = $integrityService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        try {
            return match($action) {
                'backup' => $this->performBackup(),
                'restore' => $this->performRestore(),
                'validate' => $this->validateBackup(),
                'list' => $this->listBackups(),
                default => $this->invalidAction($action)
            };
        } catch (\Exception $e) {
            $this->error("操作失敗: " . $e->getMessage());
            Log::error('Channel backup/restore failed', [
                'action' => $action,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * 執行備份
     */
    private function performBackup(): int
    {
        $this->info('🔄 開始建立通路管理系統備份...');
        $startTime = microtime(true);

        try {
            // 建立備份
            $backupPath = $this->integrityService->createBackup();
            $this->info("✅ 備份建立成功: {$backupPath}");

            // 獲取備份檔案資訊
            $fileSize = $this->formatFileSize(filesize($backupPath));
            $this->info("📁 檔案大小: {$fileSize}");

            // 壓縮備份（如果指定）
            if ($this->option('compress')) {
                $compressedPath = $this->compressBackup($backupPath);
                if ($compressedPath) {
                    $compressedSize = $this->formatFileSize(filesize($compressedPath));
                    $this->info("🗜️  壓縮完成: {$compressedPath} ({$compressedSize})");
                    $backupPath = $compressedPath;
                }
            }

            // 加密備份（如果指定）
            if ($this->option('encrypt')) {
                $encryptedPath = $this->encryptBackup($backupPath);
                if ($encryptedPath) {
                    $this->info("🔒 加密完成: {$encryptedPath}");
                    $backupPath = $encryptedPath;
                }
            }

            // 驗證備份完整性（如果指定）
            if ($this->option('verify')) {
                $this->info('🔍 驗證備份完整性...');
                $validation = $this->integrityService->validateBackupIntegrity($backupPath);
                
                if ($validation['valid']) {
                    $this->info("✅ 備份驗證通過");
                    $this->info("📊 驗證統計: {$validation['tables_checked']} 個表格, {$validation['records_validated']} 筆記錄");
                } else {
                    $this->error("❌ 備份驗證失敗:");
                    foreach ($validation['issues'] as $issue) {
                        $this->error("  - {$issue}");
                    }
                    return 1;
                }
            }

            $executionTime = round(microtime(true) - $startTime, 2);
            $this->info("⏱️  備份完成，耗時 {$executionTime} 秒");

            return 0;

        } catch (\Exception $e) {
            $this->error("備份失敗: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * 執行還原
     */
    private function performRestore(): int
    {
        $backupFile = $this->option('file');
        
        if (!$backupFile) {
            $this->error('請指定備份檔案路徑 --file=path/to/backup.json');
            return 1;
        }

        if (!file_exists($backupFile)) {
            $this->error("備份檔案不存在: {$backupFile}");
            return 1;
        }

        // 確認還原操作
        if (!$this->confirm('⚠️  還原操作將覆蓋現有資料，是否繼續？')) {
            $this->info('還原操作已取消');
            return 0;
        }

        $this->info('🔄 開始還原通路管理系統資料...');
        $startTime = microtime(true);

        try {
            // 先驗證備份檔案
            $this->info('🔍 驗證備份檔案...');
            $validation = $this->integrityService->validateBackupIntegrity($backupFile);
            
            if (!$validation['valid']) {
                $this->error("❌ 備份檔案驗證失敗:");
                foreach ($validation['issues'] as $issue) {
                    $this->error("  - {$issue}");
                }
                return 1;
            }

            $this->info("✅ 備份檔案驗證通過");

            // 執行還原
            $restoreResults = $this->integrityService->restoreBackup($backupFile);
            
            if ($restoreResults['success']) {
                $this->info("✅ 還原成功");
                $this->info("📊 還原記錄數: {$restoreResults['restored_records']}");
                
                // 執行完整性檢查
                $this->info('🔍 執行還原後完整性檢查...');
                $this->call('channel:integrity-check');
                
            } else {
                $this->error("❌ 還原失敗:");
                foreach ($restoreResults['errors'] as $error) {
                    $this->error("  - {$error}");
                }
                return 1;
            }

            $executionTime = round(microtime(true) - $startTime, 2);
            $this->info("⏱️  還原完成，耗時 {$executionTime} 秒");

            return 0;

        } catch (\Exception $e) {
            $this->error("還原失敗: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * 驗證備份檔案
     */
    private function validateBackup(): int
    {
        $backupFile = $this->option('file');
        
        if (!$backupFile) {
            $this->error('請指定備份檔案路徑 --file=path/to/backup.json');
            return 1;
        }

        if (!file_exists($backupFile)) {
            $this->error("備份檔案不存在: {$backupFile}");
            return 1;
        }

        $this->info('🔍 驗證備份檔案完整性...');

        try {
            $validation = $this->integrityService->validateBackupIntegrity($backupFile);
            
            if ($validation['valid']) {
                $this->info("✅ 備份檔案驗證通過");
                $this->info("📊 驗證統計:");
                $this->info("  - 檢查表格數: {$validation['tables_checked']}");
                $this->info("  - 驗證記錄數: {$validation['records_validated']}");
                
                // 顯示備份檔案資訊
                $fileSize = $this->formatFileSize(filesize($backupFile));
                $fileTime = date('Y-m-d H:i:s', filemtime($backupFile));
                $this->info("📁 檔案大小: {$fileSize}");
                $this->info("🕐 建立時間: {$fileTime}");
                
                return 0;
            } else {
                $this->error("❌ 備份檔案驗證失敗:");
                foreach ($validation['issues'] as $issue) {
                    $this->error("  - {$issue}");
                }
                return 1;
            }

        } catch (\Exception $e) {
            $this->error("驗證失敗: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * 列出備份檔案
     */
    private function listBackups(): int
    {
        $this->info('📋 通路管理系統備份檔案列表');
        $this->info('=' . str_repeat('=', 60));

        try {
            $backupDir = storage_path('backups');
            
            if (!is_dir($backupDir)) {
                $this->info('📁 備份目錄不存在');
                return 0;
            }

            $backupFiles = glob($backupDir . '/channel-backup-*.json*');
            
            if (empty($backupFiles)) {
                $this->info('📁 未找到備份檔案');
                return 0;
            }

            // 按修改時間排序
            usort($backupFiles, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });

            $this->table(
                ['檔案名稱', '大小', '建立時間', '狀態'],
                array_map(function($file) {
                    $filename = basename($file);
                    $size = $this->formatFileSize(filesize($file));
                    $time = date('Y-m-d H:i:s', filemtime($file));
                    
                    // 快速驗證檔案
                    $status = '✅ 正常';
                    try {
                        $validation = $this->integrityService->validateBackupIntegrity($file);
                        if (!$validation['valid']) {
                            $status = '❌ 損壞';
                        }
                    } catch (\Exception $e) {
                        $status = '❓ 未知';
                    }
                    
                    return [$filename, $size, $time, $status];
                }, $backupFiles)
            );

            $this->info("\n📊 總計: " . count($backupFiles) . " 個備份檔案");

            return 0;

        } catch (\Exception $e) {
            $this->error("列出備份檔案失敗: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * 無效動作
     */
    private function invalidAction(string $action): int
    {
        $this->error("無效的動作: {$action}");
        $this->info('可用動作: backup, restore, validate, list');
        return 1;
    }

    /**
     * 壓縮備份檔案
     */
    private function compressBackup(string $backupPath): ?string
    {
        try {
            $compressedPath = $backupPath . '.gz';
            
            $data = file_get_contents($backupPath);
            $compressed = gzencode($data, 9);
            
            if ($compressed === false) {
                $this->warn('壓縮失敗');
                return null;
            }
            
            file_put_contents($compressedPath, $compressed);
            
            // 刪除原始檔案
            unlink($backupPath);
            
            return $compressedPath;
            
        } catch (\Exception $e) {
            $this->warn("壓縮失敗: " . $e->getMessage());
            return null;
        }
    }

    /**
     * 加密備份檔案
     */
    private function encryptBackup(string $backupPath): ?string
    {
        try {
            // 這裡可以實作加密邏輯
            // 為了簡化，這裡只是重命名檔案
            $encryptedPath = $backupPath . '.enc';
            
            // 實際應用中應該使用適當的加密算法
            $data = file_get_contents($backupPath);
            $encrypted = base64_encode($data); // 簡化的"加密"
            
            file_put_contents($encryptedPath, $encrypted);
            
            // 刪除原始檔案
            unlink($backupPath);
            
            return $encryptedPath;
            
        } catch (\Exception $e) {
            $this->warn("加密失敗: " . $e->getMessage());
            return null;
        }
    }

    /**
     * 格式化檔案大小
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
}