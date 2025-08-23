<?php

namespace App\Console\Commands;

use App\Services\ActivityIntegrityService;
use App\Models\Activity;
use Illuminate\Console\Command;
use Carbon\Carbon;

/**
 * 活動記錄完整性檢查命令
 */
class ActivityIntegrityCheck extends Command
{
    /**
     * 命令名稱和參數
     *
     * @var string
     */
    protected $signature = 'activity:integrity-check 
                            {--batch-size=100 : 批次處理大小}
                            {--days=7 : 檢查最近幾天的記錄}
                            {--report : 生成詳細報告}
                            {--fix : 自動標記可疑記錄}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '檢查活動記錄的完整性並生成報告';

    /**
     * 完整性服務
     *
     * @var ActivityIntegrityService
     */
    protected ActivityIntegrityService $integrityService;

    /**
     * 建構函式
     *
     * @param ActivityIntegrityService $integrityService
     */
    public function __construct(ActivityIntegrityService $integrityService)
    {
        parent::__construct();
        $this->integrityService = $integrityService;
    }

    /**
     * 執行命令
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('開始執行活動記錄完整性檢查...');
        
        $batchSize = (int) $this->option('batch-size');
        $days = (int) $this->option('days');
        $generateReport = $this->option('report');
        $autoFix = $this->option('fix');
        
        // 查詢要檢查的活動記錄
        $activities = Activity::where('created_at', '>=', Carbon::now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->limit($batchSize * 10) // 限制總數量
            ->get();
        
        if ($activities->isEmpty()) {
            $this->info('沒有找到需要檢查的活動記錄。');
            return Command::SUCCESS;
        }
        
        $this->info("找到 {$activities->count()} 筆活動記錄需要檢查。");
        
        // 分批處理
        $totalBatches = ceil($activities->count() / $batchSize);
        $overallResults = [];
        $invalidCount = 0;
        
        $progressBar = $this->output->createProgressBar($totalBatches);
        $progressBar->start();
        
        foreach ($activities->chunk($batchSize) as $batch) {
            $results = $this->integrityService->verifyBatch($batch);
            $overallResults = array_merge($overallResults, $results);
            
            // 統計無效記錄
            $batchInvalidCount = count(array_filter($results, fn($valid) => !$valid));
            $invalidCount += $batchInvalidCount;
            
            // 如果啟用自動修復，標記可疑記錄
            if ($autoFix && $batchInvalidCount > 0) {
                $this->fixInvalidActivities($batch, $results);
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        // 顯示檢查結果
        $this->displayResults($activities->count(), $invalidCount);
        
        // 生成詳細報告
        if ($generateReport) {
            $this->generateDetailedReport($days, $batchSize);
        }
        
        return $invalidCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * 修復無效的活動記錄
     *
     * @param \Illuminate\Database\Eloquent\Collection $activities
     * @param array $results
     * @return void
     */
    protected function fixInvalidActivities($activities, array $results): void
    {
        foreach ($results as $activityId => $isValid) {
            if (!$isValid) {
                $activity = $activities->find($activityId);
                if ($activity) {
                    $this->integrityService->markAsSuspicious(
                        $activityId, 
                        '完整性檢查失敗 - 自動標記'
                    );
                    $this->warn("已標記活動記錄 #{$activityId} 為可疑");
                }
            }
        }
    }

    /**
     * 顯示檢查結果
     *
     * @param int $totalCount
     * @param int $invalidCount
     * @return void
     */
    protected function displayResults(int $totalCount, int $invalidCount): void
    {
        $validCount = $totalCount - $invalidCount;
        $integrityRate = $totalCount > 0 ? round(($validCount / $totalCount) * 100, 2) : 0;
        
        $this->info('完整性檢查結果：');
        $this->table(
            ['項目', '數量', '百分比'],
            [
                ['總記錄數', $totalCount, '100%'],
                ['有效記錄', $validCount, $integrityRate . '%'],
                ['無效記錄', $invalidCount, round((100 - $integrityRate), 2) . '%'],
            ]
        );
        
        if ($invalidCount === 0) {
            $this->info('✅ 所有活動記錄的完整性驗證通過！');
        } else {
            $this->error("❌ 發現 {$invalidCount} 筆完整性問題的記錄！");
            
            if ($integrityRate < 95) {
                $this->error('⚠️  完整性比率低於 95%，建議立即進行安全審計！');
            }
        }
    }

    /**
     * 生成詳細報告
     *
     * @param int $days
     * @param int $batchSize
     * @return void
     */
    protected function generateDetailedReport(int $days, int $batchSize): void
    {
        $this->info('正在生成詳細完整性報告...');
        
        $filters = [
            'date_from' => Carbon::now()->subDays($days)->toDateString(),
            'date_to' => Carbon::now()->toDateString(),
            'limit' => $batchSize * 10,
        ];
        
        $report = $this->integrityService->generateIntegrityReport($filters);
        
        $this->newLine();
        $this->info('📊 完整性報告');
        $this->info('生成時間: ' . $report['report_generated_at']);
        
        $this->newLine();
        $this->info('📈 統計資訊:');
        $stats = $report['statistics'];
        $this->table(
            ['統計項目', '數值'],
            [
                ['總活動記錄數', $stats['total_activities']],
                ['有效記錄數', $stats['valid_activities']],
                ['無效記錄數', $stats['invalid_activities']],
                ['完整性比率', $stats['integrity_rate'] . '%'],
            ]
        );
        
        if (!empty($report['tampered_activities'])) {
            $this->newLine();
            $this->error('🚨 被篡改的活動記錄:');
            
            $tamperedData = array_map(function ($activity) {
                return [
                    $activity['id'],
                    $activity['type'],
                    substr($activity['description'], 0, 50) . '...',
                    $activity['created_at'],
                ];
            }, array_slice($report['tampered_activities'], 0, 10));
            
            $this->table(
                ['ID', '類型', '描述', '建立時間'],
                $tamperedData
            );
            
            if (count($report['tampered_activities']) > 10) {
                $remaining = count($report['tampered_activities']) - 10;
                $this->warn("還有 {$remaining} 筆被篡改的記錄未顯示...");
            }
        }
        
        if (!empty($report['recommendations'])) {
            $this->newLine();
            $this->info('💡 建議:');
            foreach ($report['recommendations'] as $recommendation) {
                $this->line('• ' . $recommendation);
            }
        }
    }
}
