<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ActivitySecurityService;
use Carbon\Carbon;

/**
 * 活動記錄安全審計命令
 * 
 * 執行活動記錄的安全審計檢查
 */
class ActivitySecurityAudit extends Command
{
    /**
     * 命令名稱和簽名
     *
     * @var string
     */
    protected $signature = 'activity:security-audit 
                            {--scope=full : 審計範圍 (full, integrity, access, patterns, incidents)}
                            {--from= : 開始日期 (Y-m-d)}
                            {--to= : 結束日期 (Y-m-d)}
                            {--batch-size=1000 : 批次處理大小}
                            {--output= : 輸出檔案路徑}
                            {--format=json : 輸出格式 (json, csv, txt)}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '執行活動記錄安全審計檢查';

    protected ActivitySecurityService $securityService;

    public function __construct(ActivitySecurityService $securityService)
    {
        parent::__construct();
        $this->securityService = $securityService;
    }

    /**
     * 執行命令
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('開始執行活動記錄安全審計...');
        
        try {
            // 準備審計選項
            $options = $this->prepareAuditOptions();
            
            // 顯示審計參數
            $this->displayAuditParameters($options);
            
            // 執行安全審計
            $report = $this->securityService->performSecurityAudit($options);
            
            // 顯示審計結果
            $this->displayAuditResults($report);
            
            // 輸出報告檔案
            if ($this->option('output')) {
                $this->saveReportToFile($report);
            }
            
            // 根據審計結果決定退出碼
            return $this->determineExitCode($report);
            
        } catch (\Exception $e) {
            $this->error('安全審計執行失敗: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * 準備審計選項
     *
     * @return array
     */
    private function prepareAuditOptions(): array
    {
        $options = [
            'scope' => $this->option('scope'),
            'batch_size' => (int) $this->option('batch-size'),
        ];

        // 設定日期範圍
        if ($this->option('from')) {
            $options['date_from'] = Carbon::parse($this->option('from'))->startOfDay();
        }

        if ($this->option('to')) {
            $options['date_to'] = Carbon::parse($this->option('to'))->endOfDay();
        }

        return $options;
    }

    /**
     * 顯示審計參數
     *
     * @param array $options
     * @return void
     */
    private function displayAuditParameters(array $options): void
    {
        $this->info('審計參數:');
        $this->line("  範圍: {$options['scope']}");
        $this->line("  批次大小: {$options['batch_size']}");
        
        if (isset($options['date_from'])) {
            $this->line("  開始日期: {$options['date_from']->format('Y-m-d H:i:s')}");
        }
        
        if (isset($options['date_to'])) {
            $this->line("  結束日期: {$options['date_to']->format('Y-m-d H:i:s')}");
        }
        
        $this->newLine();
    }

    /**
     * 顯示審計結果
     *
     * @param array $report
     * @return void
     */
    private function displayAuditResults(array $report): void
    {
        $this->info('審計結果摘要:');
        $this->newLine();

        // 基本統計
        if (isset($report['statistics'])) {
            $stats = $report['statistics'];
            $this->line("檢查的活動記錄總數: {$stats['total_activities']}");
            $this->line("完整性違規: {$stats['integrity_violations']}");
            $this->line("存取違規: {$stats['access_violations']}");
            $this->line("高風險活動: {$stats['high_risk_activities']}");
        }

        $this->newLine();

        // 執行時間
        if (isset($report['execution_time'])) {
            $this->line("執行時間: {$report['execution_time']} 秒");
        }

        // 狀態
        $status = $report['status'] ?? 'unknown';
        $statusColor = match ($status) {
            'completed' => 'info',
            'failed' => 'error',
            default => 'comment'
        };
        
        $this->line("狀態: <{$statusColor}>{$status}</{$statusColor}>");

        // 詳細結果
        $this->displayDetailedResults($report);
    }

    /**
     * 顯示詳細結果
     *
     * @param array $report
     * @return void
     */
    private function displayDetailedResults(array $report): void
    {
        if (!isset($report['results'])) {
            return;
        }

        $results = $report['results'];

        // 完整性檢查結果
        if (!empty($results['integrity_check'])) {
            $this->newLine();
            $this->warn('完整性檢查發現問題:');
            foreach ($results['integrity_check'] as $issue) {
                $this->line("  - {$issue}");
            }
        }

        // 存取違規
        if (!empty($results['access_violations'])) {
            $this->newLine();
            $this->warn('存取違規:');
            foreach ($results['access_violations'] as $violation) {
                $this->line("  - {$violation}");
            }
        }

        // 可疑模式
        if (!empty($results['suspicious_patterns'])) {
            $this->newLine();
            $this->warn('可疑模式:');
            foreach ($results['suspicious_patterns'] as $pattern) {
                $this->line("  - {$pattern}");
            }
        }

        // 安全事件
        if (!empty($results['security_incidents'])) {
            $this->newLine();
            $this->error('安全事件:');
            foreach ($results['security_incidents'] as $incident) {
                $this->line("  - {$incident}");
            }
        }

        // 建議
        if (!empty($results['recommendations'])) {
            $this->newLine();
            $this->info('建議:');
            foreach ($results['recommendations'] as $recommendation) {
                $this->line("  - {$recommendation}");
            }
        }
    }

    /**
     * 儲存報告到檔案
     *
     * @param array $report
     * @return void
     */
    private function saveReportToFile(array $report): void
    {
        $outputPath = $this->option('output');
        $format = $this->option('format');

        try {
            $content = match ($format) {
                'json' => json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                'csv' => $this->convertReportToCsv($report),
                'txt' => $this->convertReportToText($report),
                default => json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            };

            file_put_contents($outputPath, $content);
            $this->info("報告已儲存至: {$outputPath}");
            
        } catch (\Exception $e) {
            $this->error("儲存報告失敗: {$e->getMessage()}");
        }
    }

    /**
     * 轉換報告為 CSV 格式
     *
     * @param array $report
     * @return string
     */
    private function convertReportToCsv(array $report): string
    {
        $csv = "項目,值\n";
        $csv .= "審計ID,{$report['audit_id']}\n";
        $csv .= "開始時間,{$report['started_at']}\n";
        $csv .= "完成時間,{$report['completed_at']}\n";
        $csv .= "執行時間,{$report['execution_time']}秒\n";
        $csv .= "狀態,{$report['status']}\n";

        if (isset($report['statistics'])) {
            $stats = $report['statistics'];
            $csv .= "檢查總數,{$stats['total_activities']}\n";
            $csv .= "完整性違規,{$stats['integrity_violations']}\n";
            $csv .= "存取違規,{$stats['access_violations']}\n";
            $csv .= "高風險活動,{$stats['high_risk_activities']}\n";
        }

        return $csv;
    }

    /**
     * 轉換報告為文字格式
     *
     * @param array $report
     * @return string
     */
    private function convertReportToText(array $report): string
    {
        $text = "活動記錄安全審計報告\n";
        $text .= str_repeat('=', 50) . "\n\n";
        
        $text .= "審計ID: {$report['audit_id']}\n";
        $text .= "開始時間: {$report['started_at']}\n";
        $text .= "完成時間: {$report['completed_at']}\n";
        $text .= "執行時間: {$report['execution_time']} 秒\n";
        $text .= "狀態: {$report['status']}\n\n";

        if (isset($report['statistics'])) {
            $stats = $report['statistics'];
            $text .= "統計資訊:\n";
            $text .= "  檢查的活動記錄總數: {$stats['total_activities']}\n";
            $text .= "  完整性違規: {$stats['integrity_violations']}\n";
            $text .= "  存取違規: {$stats['access_violations']}\n";
            $text .= "  高風險活動: {$stats['high_risk_activities']}\n\n";
        }

        return $text;
    }

    /**
     * 根據審計結果決定退出碼
     *
     * @param array $report
     * @return int
     */
    private function determineExitCode(array $report): int
    {
        // 如果審計失敗
        if (($report['status'] ?? '') === 'failed') {
            return Command::FAILURE;
        }

        // 如果發現嚴重安全問題
        $stats = $report['statistics'] ?? [];
        if (($stats['integrity_violations'] ?? 0) > 0 || 
            ($stats['access_violations'] ?? 0) > 10 ||
            ($stats['high_risk_activities'] ?? 0) > 50) {
            return 2; // 警告退出碼
        }

        return Command::SUCCESS;
    }
}