<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Services\SecurityAnalyzer;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * 安全事件分析命令
 * 
 * 批量分析活動記錄的安全風險
 */
class AnalyzeSecurityEvents extends Command
{
    /**
     * 命令名稱和簽名
     */
    protected $signature = 'security:analyze 
                            {--days=7 : 分析最近幾天的活動}
                            {--batch=100 : 批次處理數量}
                            {--force : 強制重新分析已分析的活動}';

    /**
     * 命令描述
     */
    protected $description = '分析活動記錄中的安全事件並生成風險評分';

    /**
     * 安全分析器
     */
    protected SecurityAnalyzer $analyzer;

    /**
     * 建立命令實例
     */
    public function __construct(SecurityAnalyzer $analyzer)
    {
        parent::__construct();
        $this->analyzer = $analyzer;
    }

    /**
     * 執行命令
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $batchSize = (int) $this->option('batch');
        $force = $this->option('force');

        $this->info("開始分析最近 {$days} 天的安全事件...");

        $startDate = Carbon::now()->subDays($days);
        
        // 建立查詢
        $query = Activity::where('created_at', '>=', $startDate);
        
        if (!$force) {
            // 只分析尚未分析的活動（risk_level 為 null 或 0）
            $query->where(function ($q) {
                $q->whereNull('risk_level')->orWhere('risk_level', 0);
            });
        }

        $totalCount = $query->count();
        
        if ($totalCount === 0) {
            $this->info('沒有需要分析的活動記錄。');
            return 0;
        }

        $this->info("找到 {$totalCount} 筆活動需要分析");

        $progressBar = $this->output->createProgressBar($totalCount);
        $progressBar->start();

        $processedCount = 0;
        $alertsGenerated = 0;
        $highRiskCount = 0;

        // 分批處理
        $query->chunk($batchSize, function ($activities) use (&$processedCount, &$alertsGenerated, &$highRiskCount, $progressBar) {
            foreach ($activities as $activity) {
                try {
                    // 分析活動
                    $analysis = $this->analyzer->analyzeActivity($activity);
                    
                    // 更新風險等級
                    $activity->update([
                        'risk_level' => SecurityAnalyzer::RISK_LEVELS[$analysis['risk_level']]
                    ]);

                    // 統計高風險活動
                    if ($analysis['risk_level'] === 'high' || $analysis['risk_level'] === 'critical') {
                        $highRiskCount++;
                    }

                    // 生成安全警報
                    if (!empty($analysis['security_events'])) {
                        $alert = $this->analyzer->generateSecurityAlert($activity, $analysis['security_events']);
                        if ($alert) {
                            $alertsGenerated++;
                        }
                    }

                    $processedCount++;
                    $progressBar->advance();

                } catch (\Exception $e) {
                    $this->error("分析活動 {$activity->id} 時發生錯誤: " . $e->getMessage());
                }
            }
        });

        $progressBar->finish();
        $this->newLine(2);

        // 顯示統計結果
        $this->info("分析完成！");
        $this->table(
            ['項目', '數量'],
            [
                ['已處理活動', $processedCount],
                ['高風險活動', $highRiskCount],
                ['生成警報', $alertsGenerated],
            ]
        );

        // 生成安全報告
        if ($this->confirm('是否生成安全報告？', true)) {
            $this->generateSecurityReport($days);
        }

        return 0;
    }

    /**
     * 生成安全報告
     */
    protected function generateSecurityReport(int $days): void
    {
        $this->info('正在生成安全報告...');

        $timeRange = $days . 'd';
        $report = $this->analyzer->generateSecurityReport($timeRange);

        $this->newLine();
        $this->info('=== 安全報告 ===');
        $this->info("報告期間: {$report['start_date']} 至 {$report['end_date']}");
        $this->newLine();

        // 摘要統計
        $this->info('摘要統計:');
        $this->table(
            ['項目', '數量'],
            [
                ['總活動數', $report['summary']['total_activities']],
                ['安全事件', $report['summary']['security_events']],
                ['高風險事件', $report['summary']['high_risk_events']],
                ['嚴重事件', $report['summary']['critical_events']],
            ]
        );

        // 頂級風險
        if (!empty($report['top_risks'])) {
            $this->newLine();
            $this->info('頂級風險事件:');
            $topRisks = array_slice($report['top_risks'], 0, 5);
            $this->table(
                ['時間', '使用者', '類型', '描述', '風險等級'],
                array_map(function ($risk) {
                    return [
                        $risk['created_at'],
                        $risk['user'],
                        $risk['type'],
                        substr($risk['description'], 0, 50) . '...',
                        $risk['risk_level']
                    ];
                }, $topRisks)
            );
        }

        // 使用者風險排名
        if (!empty($report['user_risk_ranking'])) {
            $this->newLine();
            $this->info('使用者風險排名:');
            $topUsers = array_slice($report['user_risk_ranking'], 0, 5);
            $this->table(
                ['使用者', '活動數', '平均風險', '高風險活動'],
                array_map(function ($user) {
                    return [
                        $user['username'],
                        $user['activity_count'],
                        round($user['avg_risk_score'], 2),
                        $user['high_risk_activities']
                    ];
                }, $topUsers)
            );
        }

        // 安全建議
        if (!empty($report['recommendations'])) {
            $this->newLine();
            $this->info('安全建議:');
            foreach ($report['recommendations'] as $recommendation) {
                $priority = strtoupper($recommendation['priority']);
                $this->line("[$priority] {$recommendation['title']}: {$recommendation['description']}");
            }
        }
    }
}