<?php

namespace App\Console\Commands;

use App\Services\ActivityBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 活動記錄備份指令
 * 
 * 用於自動備份活動記錄資料
 */
class BackupActivityLogs extends Command
{
    /**
     * 指令名稱和簽名
     *
     * @var string
     */
    protected $signature = 'activity:backup 
                            {--days-back=30 : 備份多少天前的資料}
                            {--cleanup : 清理舊備份}
                            {--cleanup-days=90 : 清理多少天前的備份}';

    /**
     * 指令描述
     *
     * @var string
     */
    protected $description = '備份活動記錄資料';

    /**
     * 執行指令
     */
    public function handle(ActivityBackupService $backupService): int
    {
        $this->info('開始執行活動記錄備份...');
        
        try {
            // 準備備份選項
            $options = [];
            
            if ($this->option('days-back')) {
                $daysBack = (int) $this->option('days-back');
                $options['date_from'] = now()->subDays($daysBack)->startOfDay();
                $options['date_to'] = now()->endOfDay();
                
                $this->info("備份範圍: {$options['date_from']->format('Y-m-d')} 到 {$options['date_to']->format('Y-m-d')}");
            }

            // 執行備份
            $result = $backupService->backupActivityLogs($options);
            
            if ($result['success']) {
                $this->displayBackupResult($result);
                
                // 清理舊備份
                if ($this->option('cleanup')) {
                    $this->info('清理舊備份...');
                    $cleanupDays = (int) $this->option('cleanup-days');
                    $cleanupResult = $backupService->cleanupOldActivityBackups($cleanupDays);
                    $this->displayCleanupResult($cleanupResult);
                }
                
                $this->info('✅ 活動記錄備份完成');
                return Command::SUCCESS;
                
            } else {
                $this->error('❌ 活動記錄備份失敗: ' . ($result['error'] ?? '未知錯誤'));
                return Command::FAILURE;
            }
            
        } catch (\Exception $e) {
            $this->error('❌ 備份過程中發生錯誤: ' . $e->getMessage());
            Log::error('Activity backup command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * 顯示備份結果
     *
     * @param array $result
     * @return void
     */
    protected function displayBackupResult(array $result): void
    {
        $this->info('📊 備份結果:');
        $this->line("   備份名稱: {$result['backup_name']}");
        $this->line("   開始時間: {$result['started_at']}");
        $this->line("   完成時間: {$result['completed_at']}");
        
        if (isset($result['data_export']['record_count'])) {
            $this->line("   記錄數量: {$result['data_export']['record_count']} 筆");
        }
        
        if (isset($result['data_export']['file_size_mb'])) {
            $this->line("   原始大小: {$result['data_export']['file_size_mb']} MB");
        }
        
        if (isset($result['compression']['compression_ratio'])) {
            $this->line("   壓縮率: {$result['compression']['compression_ratio']}%");
        }
        
        if (isset($result['encryption']['checksum'])) {
            $this->line("   檔案雜湊: " . substr($result['encryption']['checksum'], 0, 16) . '...');
        }
    }

    /**
     * 顯示清理結果
     *
     * @param array $result
     * @return void
     */
    protected function displayCleanupResult(array $result): void
    {
        if ($result['success']) {
            $this->info("🧹 清理完成: 刪除了 {$result['deleted_count']} 個舊備份檔案");
            if ($result['deleted_size_mb'] > 0) {
                $this->line("   釋放空間: {$result['deleted_size_mb']} MB");
            }
        } else {
            $this->warn('⚠️  清理過程中發生錯誤:');
            foreach ($result['errors'] as $error) {
                $this->line("   - {$error}");
            }
        }
    }
}