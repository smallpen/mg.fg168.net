<?php

namespace App\Console\Commands;

use App\Services\SecurityAnalyzer;
use App\Models\SecurityAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * 安全報告命令
 * 生成安全分析報告
 */
class SecurityReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:report 
                            {--days=30 : 報告時間範圍（天）}
                            {--format=console : 報告格式 (console|json|csv)}
                            {--output= : 輸出檔案路徑}
                            {--email= : 發送報告到指定郵箱}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成安全分析報告';

    protected SecurityAnalyzer $securityAnalyzer;

    /**
     * 建立命令實例
     */
    public function __construct(SecurityAnalyzer $securityAnalyzer)
    {
        parent::__construct();
        $this->securityAnalyzer = $securityAnalyzer;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = $this->option('days');
        $format = $this->option('format');
        $outputPath = $this->option('output');
        $email = $this->option('email');

        $this->info("生成安全報告...");
        $this->info("時間範圍：最近 {$days} 天");

        // 生成報告
        $timeRange = "{$days}d";
        $report = $this->securityAnalyzer->generateSecurityReport($timeRange);

        // 添加額外的統計資訊
        $report['alert_statistics'] = SecurityAlert::getStatistics($days);
        $report['suspicious_ips'] = $this->securityAnalyzer->checkSuspiciousIPs();
        $report['failed_logins'] = $this->securityAnalyzer->monitorFailedLogins();

        // 根據格式輸出報告
        switch ($format) {
            case 'json':
                $this->outputJsonReport($report, $outputPath);
                break;
            case 'csv':
                $this->outputCsvReport($report, $outputPath);
                break;
            default:
                $this->outputConsoleReport($report);
        }

        // 發送郵件
        if ($email) {
            $this->sendReportByEmail($report, $email);
        }

        $this->info('安全報告生成完成！');
        return 0;
    }

    /**
     * 輸出控制台報告
     */
    protected function outputConsoleReport(array $report): void
    {
        $this->info('=== 安全分析報告 ===');
        $this->info("報告時間：{$report['generated_at']}");
        $this->info("時間範圍：{$report['time_range']}");
        $this->newLine();

        // 摘要資訊
        $this->info('=== 摘要 ===');
        $summary = $report['summary'];
        $this->table(
            ['項目', '數量'],
            [
                ['總活動數', number_format($summary['total_activities'])],
                ['安全事件', number_format($summary['security_events'])],
                ['高風險活動', number_format($summary['high_risk_activities'])],
                ['總警報數', number_format($summary['total_alerts'])],
                ['未確認警報', number_format($summary['unacknowledged_alerts'])]
            ]
        );

        // 主要風險
        if (!empty($report['top_risks'])) {
            $this->newLine();
            $this->info('=== 主要風險 ===');
            $riskData = [];
            foreach (array_slice($report['top_risks'], 0, 5) as $risk) {
                $riskData[] = [
                    $risk['id'],
                    $risk['type'],
                    substr($risk['description'], 0, 50) . '...',
                    $risk['risk_level'],
                    $risk['created_at']
                ];
            }
            $this->table(['ID', '類型', '描述', '風險等級', '時間'], $riskData);
        }

        // 可疑 IP
        if ($report['suspicious_ips']->isNotEmpty()) {
            $this->newLine();
            $this->info('=== 可疑 IP 位址 ===');
            $ipData = [];
            foreach ($report['suspicious_ips']->take(5) as $ip) {
                $ipData[] = [
                    $ip['ip_address'],
                    $ip['activity_count'],
                    $ip['failed_logins'],
                    $ip['risk_score'],
                    implode(', ', $ip['reasons'])
                ];
            }
            $this->table(['IP 位址', '活動數', '失敗登入', '風險分數', '原因'], $ipData);
        }

        // 登入失敗分析
        $failedLogins = $report['failed_logins'];
        if ($failedLogins['total_failures'] > 0) {
            $this->newLine();
            $this->info('=== 登入失敗分析 ===');
            $this->table(
                ['項目', '數量'],
                [
                    ['總失敗次數', $failedLogins['total_failures']],
                    ['涉及 IP 數', $failedLogins['unique_ips']],
                    ['涉及使用者數', $failedLogins['unique_users']],
                    ['高風險 IP', count($failedLogins['by_ip'])],
                    ['高風險使用者', count($failedLogins['by_user'])]
                ]
            );
        }

        // 建議
        if (!empty($report['recommendations'])) {
            $this->newLine();
            $this->info('=== 安全建議 ===');
            foreach ($report['recommendations'] as $index => $recommendation) {
                $this->line(($index + 1) . '. ' . $recommendation);
            }
        }
    }

    /**
     * 輸出 JSON 報告
     */
    protected function outputJsonReport(array $report, ?string $outputPath): void
    {
        $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        if ($outputPath) {
            Storage::put($outputPath, $json);
            $this->info("JSON 報告已儲存到：{$outputPath}");
        } else {
            $this->line($json);
        }
    }

    /**
     * 輸出 CSV 報告
     */
    protected function outputCsvReport(array $report, ?string $outputPath): void
    {
        $csv = $this->generateCsvContent($report);
        
        if ($outputPath) {
            Storage::put($outputPath, $csv);
            $this->info("CSV 報告已儲存到：{$outputPath}");
        } else {
            $this->line($csv);
        }
    }

    /**
     * 生成 CSV 內容
     */
    protected function generateCsvContent(array $report): string
    {
        $csv = "安全分析報告\n";
        $csv .= "生成時間,{$report['generated_at']}\n";
        $csv .= "時間範圍,{$report['time_range']}\n\n";

        // 摘要
        $csv .= "摘要\n";
        $csv .= "項目,數量\n";
        foreach ($report['summary'] as $key => $value) {
            $csv .= "{$key},{$value}\n";
        }

        // 主要風險
        if (!empty($report['top_risks'])) {
            $csv .= "\n主要風險\n";
            $csv .= "ID,類型,描述,風險等級,時間\n";
            foreach ($report['top_risks'] as $risk) {
                $csv .= "{$risk['id']},{$risk['type']},\"{$risk['description']}\",{$risk['risk_level']},{$risk['created_at']}\n";
            }
        }

        return $csv;
    }

    /**
     * 發送報告郵件
     */
    protected function sendReportByEmail(array $report, string $email): void
    {
        // 這裡可以實作郵件發送邏輯
        $this->info("報告將發送到：{$email}");
        // Mail::to($email)->send(new SecurityReportMail($report));
    }
}
