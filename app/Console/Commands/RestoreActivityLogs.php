<?php

namespace App\Console\Commands;

use App\Services\ActivityBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * 活動記錄還原指令
 * 
 * 用於還原活動記錄備份資料
 */
class RestoreActivityLogs extends Command
{
    /**
     * 指令名稱和簽名
     *
     * @var string
     */
    protected $signature = 'activity:restore 
                            {backup-file? : 備份檔案路徑}
                            {--replace : 替換現有記錄}
                            {--no-integrity : 跳過完整性驗證}
                            {--list : 列出可用的備份檔案}';

    /**
     * 指令描述
     *
     * @var string
     */
    protected $description = '還原活動記錄備份資料';

    /**
     * 執行指令
     */
    public function handle(ActivityBackupService $backupService): int
    {
        // 如果要求列出備份檔案
        if ($this->option('list')) {
            return $this->listBackups($backupService);
        }

        $backupFile = $this->argument('backup-file');
        
        if (!$backupFile) {
            $this->error('請提供備份檔案路徑，或使用 --list 選項查看可用備份');
            return Command::FAILURE;
        }
        
        // 檢查備份檔案是否存在
        if (!File::exists($backupFile)) {
            // 嘗試在預設備份目錄中尋找
            $defaultPath = storage_path('backups/activity_logs/' . $backupFile);
            if (File::exists($defaultPath)) {
                $backupFile = $defaultPath;
            } else {
                $this->error("❌ 備份檔案不存在: {$backupFile}");
                return Command::FAILURE;
            }
        }

        $this->info("開始還原活動記錄備份: " . basename($backupFile));
        
        // 確認操作
        if (!$this->option('replace')) {
            if (!$this->confirm('此操作將匯入活動記錄資料，是否繼續？')) {
                $this->info('操作已取消');
                return Command::SUCCESS;
            }
        } else {
            if (!$this->confirm('⚠️  此操作將替換現有的活動記錄，是否確定要繼續？')) {
                $this->info('操作已取消');
                return Command::SUCCESS;
            }
        }

        try {
            // 準備還原選項
            $options = [
                'replace_existing' => $this->option('replace'),
                'validate_integrity' => !$this->option('no-integrity'),
            ];

            // 執行還原
            $result = $backupService->restoreActivityLogs($backupFile, $options);
            
            if ($result['success']) {
                $this->displayRestoreResult($result);
                $this->info('✅ 活動記錄還原完成');
                return Command::SUCCESS;
                
            } else {
                $this->error('❌ 活動記錄還原失敗: ' . ($result['error'] ?? '未知錯誤'));
                return Command::FAILURE;
            }
            
        } catch (\Exception $e) {
            $this->error('❌ 還原過程中發生錯誤: ' . $e->getMessage());
            Log::error('Activity restore command failed', [
                'backup_file' => $backupFile,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * 列出可用的備份檔案
     *
     * @param ActivityBackupService $backupService
     * @return int
     */
    protected function listBackups(ActivityBackupService $backupService): int
    {
        $backups = $backupService->listActivityBackups();
        
        if (empty($backups)) {
            $this->info('沒有找到活動記錄備份檔案');
            return Command::SUCCESS;
        }

        $this->info('📁 可用的活動記錄備份:');
        $this->line('');

        $headers = ['檔案名稱', '大小 (MB)', '建立時間', '雜湊值'];
        $rows = [];

        foreach ($backups as $backup) {
            $rows[] = [
                $backup['filename'],
                $backup['size_mb'],
                \Carbon\Carbon::parse($backup['created_at'])->format('Y-m-d H:i:s'),
                substr($backup['checksum'], 0, 16) . '...'
            ];
        }

        $this->table($headers, $rows);
        
        $this->line('');
        $this->info('使用方式: php artisan activity:restore <檔案名稱>');
        
        return Command::SUCCESS;
    }

    /**
     * 顯示還原結果
     *
     * @param array $result
     * @return void
     */
    protected function displayRestoreResult(array $result): void
    {
        $this->info('📊 還原結果:');
        $this->line("   備份檔案: {$result['backup_file']}");
        $this->line("   開始時間: {$result['started_at']}");
        $this->line("   完成時間: {$result['completed_at']}");
        
        if (isset($result['data_import'])) {
            $import = $result['data_import'];
            $this->line("   總記錄數: {$import['total_records']} 筆");
            $this->line("   匯入成功: {$import['imported_count']} 筆");
            
            if ($import['skipped_count'] > 0) {
                $this->line("   跳過記錄: {$import['skipped_count']} 筆");
            }
            
            if ($import['error_count'] > 0) {
                $this->warn("   錯誤記錄: {$import['error_count']} 筆");
            }
        }
        
        if (isset($result['integrity_check']['checksum'])) {
            $this->line("   檔案雜湊: " . substr($result['integrity_check']['checksum'], 0, 16) . '...');
        }
    }
}