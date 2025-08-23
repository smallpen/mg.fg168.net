<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Services\SecurityAnalyzer;
use Illuminate\Console\Command;
use Carbon\Carbon;

/**
 * 安全分析命令
 * 用於批量分析活動記錄的安全風險
 */
class SecurityAnalysisCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:analyze 
                            {--days=1 : 分析最近幾天的活動記錄}
                            {--user= : 分析特定使用者的活動}
                            {--type= : 分析特定類型的活動}
                            {--force : 強制重新分析已分析的記錄}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '分析活動記錄的安全風險並生成警報';

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
        $userId = $this->option('user');
        $activityType = $this->option('type');
        $force = $this->option('force');

        $this->info("開始安全分析...");
        $this->info("分析範圍：最近 {$days} 天");

        // 建立查詢
        $query = Activity::where('created_at', '>=', now()->subDays($days));

        if ($userId) {
            $query->where('causer_id', $userId);
            $this->info("使用者篩選：{$userId}");
        }

        if ($activityType) {
            $query->where('type', $activityType);
            $this->info("活動類型篩選：{$activityType}");
        }

        if (!$force) {
            // 只分析尚未分析或風險等級為 0 的記錄
            $query->where(function ($q) {
                $q->whereNull('risk_level')->orWhere('risk_level', 0);
            });
        }

        $activities = $query->orderBy('created_at', 'desc')->get();
        $totalCount = $activities->count();

        if ($totalCount === 0) {
            $this->info('沒有需要分析的活動記錄。');
            return 0;
        }

        $this->info("找到 {$totalCount} 筆活動記錄需要分析");

        $progressBar = $this->output->createProgressBar($totalCount);
        $progressBar->start();

        $analysisResults = [
            'analyzed' => 0,
            'alerts_generated' => 0,
            'high_risk' => 0,
            'medium_risk' => 0,
            'low_risk' => 0,
            'errors' => 0
        ];

        foreach ($activities as $activity) {
            try {
                // 進行安全分析
                $analysis = $this->securityAnalyzer->analyzeActivity($activity);

                // 更新活動記錄的風險等級
                $riskLevelMap = [
                    'low' => 1,
                    'medium' => 2,
                    'high' => 3,
                    'critical' => 4
                ];

                $riskLevel = $riskLevelMap[$analysis['risk_level']] ?? 1;
                $activity->update(['risk_level' => $riskLevel]);

                // 統計風險等級
                switch ($analysis['risk_level']) {
                    case 'critical':
                    case 'high':
                        $analysisResults['high_risk']++;
                        break;
                    case 'medium':
                        $analysisResults['medium_risk']++;
                        break;
                    default:
                        $analysisResults['low_risk']++;
                }

                // 生成安全警報
                if (!empty($analysis['security_events'])) {
                    $alert = $this->securityAnalyzer->generateSecurityAlert($activity, $analysis['security_events']);
                    if ($alert) {
                        $analysisResults['alerts_generated']++;
                    }
                }

                $analysisResults['analyzed']++;

            } catch (\Exception $e) {
                $this->error("分析活動 {$activity->id} 時發生錯誤: " . $e->getMessage());
                $analysisResults['errors']++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // 顯示分析結果
        $this->displayResults($analysisResults);

        return 0;
    }

    /**
     * 顯示分析結果
     */
    protected function displayResults(array $results): void
    {
        $this->info('=== 安全分析完成 ===');
        $this->table(
            ['項目', '數量'],
            [
                ['已分析活動', $results['analyzed']],
                ['生成警報', $results['alerts_generated']],
                ['高風險活動', $results['high_risk']],
                ['中風險活動', $results['medium_risk']],
                ['低風險活動', $results['low_risk']],
                ['錯誤數量', $results['errors']]
            ]
        );

        if ($results['alerts_generated'] > 0) {
            $this->warn("生成了 {$results['alerts_generated']} 個安全警報，請及時處理！");
        }

        if ($results['high_risk'] > 0) {
            $this->warn("發現 {$results['high_risk']} 個高風險活動，建議立即檢查！");
        }
    }
}
