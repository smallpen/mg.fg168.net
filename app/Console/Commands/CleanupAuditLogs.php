<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Log;

/**
 * 清理舊的審計日誌命令
 */
class CleanupAuditLogs extends Command
{
    /**
     * 命令名稱和簽名
     */
    protected $signature = 'audit:cleanup {--days=90 : Number of days to keep audit logs}';

    /**
     * 命令描述
     */
    protected $description = '清理指定天數之前的審計日誌';

    /**
     * 審計日誌服務
     */
    protected AuditLogService $auditLogService;

    /**
     * 建構函式
     */
    public function __construct(AuditLogService $auditLogService)
    {
        parent::__construct();
        $this->auditLogService = $auditLogService;
    }

    /**
     * 執行命令
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        
        if ($days <= 0) {
            $this->error('保留天數必須大於 0');
            return 1;
        }

        $this->info("開始清理 {$days} 天前的審計日誌...");

        try {
            $deletedCount = $this->auditLogService->cleanupOldLogs($days);
            
            $this->info("成功清理了 {$deletedCount} 筆審計日誌記錄");
            
            // 記錄清理操作
            Log::info('審計日誌清理完成', [
                'deleted_count' => $deletedCount,
                'retention_days' => $days,
                'executed_by' => 'system',
                'timestamp' => now()->toISOString(),
            ]);
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("清理審計日誌時發生錯誤: {$e->getMessage()}");
            
            Log::error('審計日誌清理失敗', [
                'error' => $e->getMessage(),
                'retention_days' => $days,
                'timestamp' => now()->toISOString(),
            ]);
            
            return 1;
        }
    }
}