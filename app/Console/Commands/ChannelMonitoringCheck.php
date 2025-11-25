<?php

namespace App\Console\Commands;

use App\Services\ChannelMonitoringService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ChannelMonitoringCheck extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'channel:monitoring-check 
                            {--auto-repair : 自動修復發現的問題}
                            {--cleanup : 清理舊的監控資料}
                            {--days-keep=30 : 保留監控資料的天數}';

    /**
     * The console command description.
     */
    protected $description = '執行通路管理系統監控檢查';

    private ChannelMonitoringService $monitoringService;

    public function __construct(ChannelMonitoringService $monitoringService)
    {
        parent::__construct();
        $this->monitoringService = $monitoringService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 開始通路管理系統監控檢查...');
        $startTime = microtime(true);

        try {
            // 執行監控檢查
            $results = $this->monitoringService->performMonitoringCheck();

            // 顯示結果
            $this->displayResults($results);

            // 自動修復（如果指定）
            if ($this->option('auto-repair') && $results['status'] !== 'healthy') {
                $this->performAutoRepair();
            }

            // 清理舊資料（如果指定）
            if ($this->option('cleanup')) {
                $this->cleanupOldData();
            }

            $executionTime = round(microtime(true) - $startTime, 2);
            $this->info("✅ 監控檢查完成，耗時 {$executionTime} 秒");

            // 根據狀態返回適當的退出碼
            return match($results['status']) {
                'healthy' => 0,
                'warning' => 1,
                'critical', 'error' => 2,
                default => 3
            };

        } catch (\Exception $e) {
            $this->error('❌ 監控檢查過程中發生錯誤: ' . $e->getMessage());
            Log::error('Channel monitoring check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 2;
        }
    }

    /**
     * 顯示監控結果
     */
    private function displayResults(array $results): void
    {
        // 顯示整體狀態
        $statusIcon = match($results['status']) {
            'healthy' => '✅',
            'warning' => '⚠️',
            'critical' => '🔴',
            'error' => '❌',
            default => '❓'
        };

        $this->info("\n📊 監控結果");
        $this->info("=" . str_repeat("=", 40));
        $this->info("{$statusIcon} 整體狀態: " . strtoupper($results['status']));
        $this->info("🕐 檢查時間: {$results['timestamp']}");

        // 顯示各項檢查結果
        if (isset($results['checks'])) {
            $this->info("\n🔍 檢查項目:");
            foreach ($results['checks'] as $checkName => $checkResult) {
                $checkIcon = match($checkResult['status']) {
                    'healthy' => '✅',
                    'warning' => '⚠️',
                    'critical' => '🔴',
                    'error' => '❌',
                    default => '❓'
                };
                
                $checkLabel = $this->getCheckLabel($checkName);
                $this->line("  {$checkIcon} {$checkLabel}: {$checkResult['status']}");
                
                // 顯示關鍵指標
                $this->displayCheckDetails($checkName, $checkResult);
            }
        }

        // 顯示系統指標
        if (isset($results['metrics'])) {
            $this->info("\n📈 系統指標:");
            $this->displayMetrics($results['metrics']);
        }

        // 顯示警報
        if (!empty($results['alerts'])) {
            $this->info("\n🚨 警報 ({$this->count($results['alerts'])} 個):");
            foreach ($results['alerts'] as $alert) {
                $alertIcon = match($alert['severity']) {
                    'critical' => '🔴',
                    'warning' => '🟡',
                    'error' => '❌',
                    default => '⚠️'
                };
                $this->warn("  {$alertIcon} {$alert['message']}");
            }
        } else {
            $this->info("\n✅ 無警報");
        }
    }

    /**
     * 獲取檢查項目標籤
     */
    private function getCheckLabel(string $checkName): string
    {
        return match($checkName) {
            'points_consistency' => '點數一致性',
            'negative_balances' => '負餘額檢查',
            'orphaned_records' => '孤立記錄檢查',
            'transaction_integrity' => '交易完整性',
            'system_performance' => '系統效能',
            'data_growth' => '資料增長',
            default => $checkName
        };
    }

    /**
     * 顯示檢查詳情
     */
    private function displayCheckDetails(string $checkName, array $checkResult): void
    {
        switch ($checkName) {
            case 'points_consistency':
                if ($checkResult['agents_with_issues'] > 0) {
                    $this->line("    問題代理: {$checkResult['agents_with_issues']}/{$checkResult['agents_checked']}");
                    $this->line("    總差異: {$checkResult['total_discrepancy']}");
                }
                break;

            case 'negative_balances':
                if ($checkResult['total_negative'] > 0) {
                    $this->line("    負餘額代理: {$checkResult['negative_agents']}");
                    $this->line("    負餘額玩家: {$checkResult['negative_players']}");
                }
                break;

            case 'orphaned_records':
                if ($checkResult['total_orphaned'] > 0) {
                    $this->line("    孤立玩家: {$checkResult['orphaned_players']}");
                    $this->line("    孤立交易: {$checkResult['orphaned_transactions']}");
                }
                break;

            case 'system_performance':
                $this->line("    響應時間: {$checkResult['response_time_ms']}ms");
                $this->line("    記憶體使用: {$checkResult['memory_usage_mb']}MB");
                break;

            case 'transaction_integrity':
                if ($checkResult['errors_found'] > 0) {
                    $this->line("    錯誤率: " . ($checkResult['error_rate'] * 100) . "%");
                    $this->line("    檢查樣本: {$checkResult['sample_checked']}");
                }
                break;
        }
    }

    /**
     * 顯示系統指標
     */
    private function displayMetrics(array $metrics): void
    {
        $keyMetrics = [
            'total_points_in_system' => '系統總點數',
            'active_agents_count' => '活躍代理數',
            'active_players_count' => '活躍玩家數',
            'transactions_today' => '今日交易數',
            'transactions_this_hour' => '本小時交易數'
        ];

        foreach ($keyMetrics as $key => $label) {
            if (isset($metrics[$key])) {
                $value = is_numeric($metrics[$key]) ? number_format($metrics[$key], 2) : $metrics[$key];
                $this->line("  📊 {$label}: {$value}");
            }
        }
    }

    /**
     * 執行自動修復
     */
    private function performAutoRepair(): void
    {
        $this->info("\n🔧 開始自動修復...");
        
        try {
            $repairResults = $this->monitoringService->performAutoRepair();
            
            $this->info("修復嘗試: {$repairResults['repairs_attempted']}");
            $this->info("修復成功: {$repairResults['repairs_successful']}");
            
            if ($repairResults['repairs_failed'] > 0) {
                $this->warn("修復失敗: {$repairResults['repairs_failed']}");
            }

            foreach ($repairResults['details'] as $detail) {
                $this->line("  - {$detail}");
            }

        } catch (\Exception $e) {
            $this->error("自動修復失敗: " . $e->getMessage());
        }
    }

    /**
     * 清理舊資料
     */
    private function cleanupOldData(): void
    {
        $daysToKeep = (int) $this->option('days-keep');
        $this->info("\n🧹 清理 {$daysToKeep} 天前的監控資料...");
        
        try {
            $deletedCount = $this->monitoringService->cleanupOldData($daysToKeep);
            $this->info("已清理 {$deletedCount} 筆舊監控記錄");
        } catch (\Exception $e) {
            $this->error("清理失敗: " . $e->getMessage());
        }
    }

    /**
     * 計算陣列元素數量
     */
    private function count(array $array): int
    {
        return count($array);
    }
}