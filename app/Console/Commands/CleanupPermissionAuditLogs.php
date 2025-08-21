<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PermissionAuditService;
use Illuminate\Support\Facades\Log;

/**
 * 清理權限審計日誌命令
 * 
 * 專門用於清理權限相關的審計日誌記錄
 */
class CleanupPermissionAuditLogs extends Command
{
    /**
     * 命令名稱和簽名
     */
    protected $signature = 'permission:audit-cleanup 
                            {--days=365 : 保留天數，預設 365 天}
                            {--dry-run : 僅顯示將要刪除的記錄數量，不實際刪除}
                            {--force : 強制執行，不詢問確認}';

    /**
     * 命令描述
     */
    protected $description = '清理指定天數之前的權限審計日誌';

    /**
     * 權限審計服務
     */
    protected PermissionAuditService $auditService;

    /**
     * 建構函式
     */
    public function __construct(PermissionAuditService $auditService)
    {
        parent::__construct();
        $this->auditService = $auditService;
    }

    /**
     * 執行命令
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        
        if ($days <= 0) {
            $this->error('保留天數必須大於 0');
            return 1;
        }

        $this->info("權限審計日誌清理工具");
        $this->info("保留天數: {$days} 天");
        $this->info("清理日期: " . now()->subDays($days)->format('Y-m-d H:i:s') . " 之前的記錄");

        try {
            // 取得將要刪除的記錄統計
            $stats = $this->auditService->getCleanupStats($days);
            
            $this->table(
                ['項目', '數量'],
                [
                    ['將要刪除的總記錄數', number_format($stats['total_records'])],
                    ['涉及的權限數量', number_format($stats['unique_permissions'])],
                    ['涉及的使用者數量', number_format($stats['unique_users'])],
                    ['最舊記錄日期', $stats['oldest_record_date'] ?? 'N/A'],
                    ['最新記錄日期', $stats['newest_record_date'] ?? 'N/A'],
                ]
            );

            if ($stats['total_records'] === 0) {
                $this->info('沒有需要清理的記錄');
                return 0;
            }

            if ($dryRun) {
                $this->info('這是模擬執行，不會實際刪除任何記錄');
                return 0;
            }

            // 確認執行
            if (!$force) {
                if (!$this->confirm("確定要刪除 {$stats['total_records']} 筆權限審計日誌記錄嗎？")) {
                    $this->info('操作已取消');
                    return 0;
                }
            }

            $this->info('開始清理權限審計日誌...');
            
            // 顯示進度條
            $bar = $this->output->createProgressBar(1);
            $bar->start();
            
            $deletedCount = $this->auditService->cleanupOldAuditLogs($days);
            
            $bar->advance();
            $bar->finish();
            $this->newLine();
            
            $this->info("✅ 成功清理了 " . number_format($deletedCount) . " 筆權限審計日誌記錄");
            
            // 記錄清理操作
            Log::info('權限審計日誌清理完成', [
                'deleted_count' => $deletedCount,
                'retention_days' => $days,
                'executed_by' => 'console_command',
                'dry_run' => false,
                'timestamp' => now()->toISOString(),
            ]);
            
            // 顯示清理後的統計
            $this->showPostCleanupStats();
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("清理權限審計日誌時發生錯誤: {$e->getMessage()}");
            
            Log::error('權限審計日誌清理失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'retention_days' => $days,
                'dry_run' => $dryRun,
                'timestamp' => now()->toISOString(),
            ]);
            
            return 1;
        }
    }

    /**
     * 顯示清理後的統計資訊
     */
    private function showPostCleanupStats(): void
    {
        try {
            $currentStats = $this->auditService->getAuditStats(30);
            
            $this->newLine();
            $this->info('📊 當前審計日誌統計 (最近 30 天):');
            $this->table(
                ['項目', '數量'],
                [
                    ['總操作次數', number_format($currentStats['total_actions'])],
                    ['涉及權限數量', number_format($currentStats['unique_permissions'])],
                    ['涉及使用者數量', number_format($currentStats['unique_users'])],
                    ['平均每日活動', number_format($currentStats['average_daily_activity'], 2)],
                ]
            );
            
        } catch (\Exception $e) {
            $this->warn('無法取得清理後統計資訊: ' . $e->getMessage());
        }
    }
}