<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

/**
 * 系統備份指令
 */
class BackupSystem extends Command
{
    /**
     * 指令名稱和簽名
     *
     * @var string
     */
    protected $signature = 'backup:system 
                            {--type=full : 備份類型 (full, database, files)}
                            {--cleanup : 是否清理舊備份}';

    /**
     * 指令描述
     *
     * @var string
     */
    protected $description = '執行系統備份';

    /**
     * 執行指令
     */
    public function handle(BackupService $backupService): int
    {
        $type = $this->option('type');
        $cleanup = $this->option('cleanup');

        $this->info("開始執行 {$type} 備份...");

        try {
            switch ($type) {
                case 'database':
                    $result = $backupService->backupDatabase();
                    $this->displayDatabaseBackupResult($result);
                    break;

                case 'files':
                    $result = $backupService->backupFiles();
                    $this->displayFileBackupResult($result);
                    break;

                case 'full':
                default:
                    $result = $backupService->performFullBackup();
                    $this->displayFullBackupResult($result);
                    break;
            }

            if ($cleanup) {
                $this->info('清理舊備份...');
                $cleanupResult = $backupService->cleanupOldBackups();
                $this->displayCleanupResult($cleanupResult);
            }

            $this->info('備份完成！');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('備份失敗: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * 顯示資料庫備份結果
     */
    protected function displayDatabaseBackupResult(array $result): void
    {
        if ($result['success']) {
            $this->info('✓ 資料庫備份成功');
            $this->line("  檔案: {$result['filename']}");
            
            if (isset($result['compressed_size'])) {
                $this->line("  原始大小: " . number_format($result['original_size']) . " bytes");
                $this->line("  壓縮大小: " . number_format($result['compressed_size']) . " bytes");
                $this->line("  壓縮率: {$result['compression_ratio']}%");
            } else {
                $this->line("  大小: " . number_format($result['size']) . " bytes");
            }
        } else {
            $this->error('✗ 資料庫備份失敗');
            $this->error("  錯誤: {$result['error']}");
        }
    }

    /**
     * 顯示檔案備份結果
     */
    protected function displayFileBackupResult(array $result): void
    {
        if ($result['success']) {
            $this->info('✓ 檔案備份成功');
            $this->line("  檔案: {$result['filename']}");
            $this->line("  大小: {$result['size_mb']} MB");
            $this->line("  項目數: {$result['items_backed_up']}");
        } else {
            $this->error('✗ 檔案備份失敗');
            $this->error("  錯誤: {$result['error']}");
        }
    }

    /**
     * 顯示完整備份結果
     */
    protected function displayFullBackupResult(array $result): void
    {
        $this->line('=== 完整備份結果 ===');
        
        if ($result['database']) {
            $this->displayDatabaseBackupResult($result['database']);
        }
        
        if ($result['files']) {
            $this->displayFileBackupResult($result['files']);
        }
        
        if ($result['cleanup']) {
            $this->displayCleanupResult($result['cleanup']);
        }

        if ($result['success']) {
            $this->info('✓ 完整備份成功');
        } else {
            $this->error('✗ 完整備份部分失敗');
        }
    }

    /**
     * 顯示清理結果
     */
    protected function displayCleanupResult(array $result): void
    {
        $this->line('--- 清理舊備份 ---');
        
        if ($result['database_cleanup']['success']) {
            $dbDeleted = $result['database_cleanup']['deleted_count'];
            $dbSize = $result['database_cleanup']['deleted_size_mb'] ?? 0;
            $this->info("✓ 資料庫備份清理: 刪除 {$dbDeleted} 個檔案 ({$dbSize} MB)");
        } else {
            $this->error('✗ 資料庫備份清理失敗');
        }

        if ($result['files_cleanup']['success']) {
            $filesDeleted = $result['files_cleanup']['deleted_count'];
            $filesSize = $result['files_cleanup']['deleted_size_mb'] ?? 0;
            $this->info("✓ 檔案備份清理: 刪除 {$filesDeleted} 個檔案 ({$filesSize} MB)");
        } else {
            $this->error('✗ 檔案備份清理失敗');
        }
    }
}