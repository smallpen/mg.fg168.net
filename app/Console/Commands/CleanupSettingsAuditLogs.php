<?php

namespace App\Console\Commands;

use App\Services\SettingsSecurityService;
use App\Repositories\SettingsRepositoryInterface;
use Illuminate\Console\Command;

/**
 * 清理設定審計日誌命令
 */
class CleanupSettingsAuditLogs extends Command
{
    /**
     * 命令名稱和簽名
     *
     * @var string
     */
    protected $signature = 'settings:cleanup-audit-logs 
                            {--days= : 保留天數，預設從設定中讀取}
                            {--dry-run : 僅顯示將要刪除的記錄數量，不實際刪除}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '清理過期的設定審計日誌';

    /**
     * 設定安全服務
     */
    protected SettingsSecurityService $securityService;

    /**
     * 設定資料庫
     */
    protected SettingsRepositoryInterface $settingsRepository;

    /**
     * 建構函式
     */
    public function __construct(
        SettingsSecurityService $securityService,
        SettingsRepositoryInterface $settingsRepository
    ) {
        parent::__construct();
        $this->securityService = $securityService;
        $this->settingsRepository = $settingsRepository;
    }

    /**
     * 執行命令
     */
    public function handle(): int
    {
        $this->info('開始清理設定審計日誌...');

        try {
            // 取得保留天數
            $retentionDays = $this->getRetentionDays();
            $this->info("保留天數: {$retentionDays} 天");

            // 檢查是否為乾運行模式
            $isDryRun = $this->option('dry-run');
            
            if ($isDryRun) {
                $this->info('乾運行模式：僅顯示統計資訊，不會實際刪除資料');
                $count = $this->getExpiredLogsCount($retentionDays);
                $this->info("將要刪除的審計日誌記錄數量: {$count}");
                return 0;
            }

            // 執行清理
            $deletedCount = $this->securityService->cleanupAuditLogs($retentionDays);
            
            $this->info("成功清理了 {$deletedCount} 筆過期的審計日誌記錄");
            
            // 顯示清理後的統計資訊
            $this->showStatistics();
            
            return 0;

        } catch (\Exception $e) {
            $this->error('清理審計日誌時發生錯誤: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * 取得保留天數
     */
    protected function getRetentionDays(): int
    {
        // 優先使用命令列參數
        if ($this->option('days')) {
            $days = (int) $this->option('days');
            if ($days < 1) {
                throw new \InvalidArgumentException('保留天數必須大於 0');
            }
            return $days;
        }

        // 從設定中讀取
        try {
            $setting = $this->settingsRepository->getSetting('security.audit_log_retention_days');
            return $setting ? (int) $setting->value : 90;
        } catch (\Exception $e) {
            $this->warn('無法從設定中讀取保留天數，使用預設值 90 天');
            return 90;
        }
    }

    /**
     * 取得過期日誌數量
     */
    protected function getExpiredLogsCount(int $retentionDays): int
    {
        $cutoffDate = now()->subDays($retentionDays);
        return \App\Models\SettingChange::where('created_at', '<', $cutoffDate)->count();
    }

    /**
     * 顯示統計資訊
     */
    protected function showStatistics(): void
    {
        $totalLogs = \App\Models\SettingChange::count();
        $recentLogs = \App\Models\SettingChange::where('created_at', '>=', now()->subDays(7))->count();
        $oldestLog = \App\Models\SettingChange::oldest()->first();
        $newestLog = \App\Models\SettingChange::latest()->first();

        $this->info('');
        $this->info('審計日誌統計資訊:');
        $this->info("總記錄數: {$totalLogs}");
        $this->info("最近 7 天記錄數: {$recentLogs}");
        
        if ($oldestLog) {
            $this->info("最舊記錄時間: {$oldestLog->created_at->format('Y-m-d H:i:s')}");
        }
        
        if ($newestLog) {
            $this->info("最新記錄時間: {$newestLog->created_at->format('Y-m-d H:i:s')}");
        }
    }
}
