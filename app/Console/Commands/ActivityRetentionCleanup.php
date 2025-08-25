<?php

namespace App\Console\Commands;

use App\Services\ActivityRetentionService;
use App\Models\ActivityRetentionPolicy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 活動記錄保留政策清理命令
 * 
 * 自動執行活動記錄的清理和歸檔操作
 */
class ActivityRetentionCleanup extends Command
{
    /**
     * 命令名稱和簽名
     *
     * @var string
     */
    protected $signature = 'activity:cleanup 
                            {--dry-run : 測試執行，不實際刪除資料}
                            {--policy= : 指定執行特定政策ID}
                            {--force : 強制執行，不詢問確認}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '執行活動記錄保留政策清理';

    /**
     * 活動保留服務
     *
     * @var ActivityRetentionService
     */
    protected ActivityRetentionService $retentionService;

    /**
     * 建立新的命令實例
     */
    public function __construct(ActivityRetentionService $retentionService)
    {
        parent::__construct();
        $this->retentionService = $retentionService;
    }

    /**
     * 執行命令
     *
     * @return int
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $policyId = $this->option('policy');
        $force = $this->option('force');

        $this->info('🧹 活動記錄保留政策清理');
        $this->newLine();

        if ($dryRun) {
            $this->warn('⚠️  測試模式：不會實際刪除任何資料');
            $this->newLine();
        }

        try {
            if ($policyId) {
                return $this->executeSinglePolicy($policyId, $dryRun, $force);
            } else {
                return $this->executeAllPolicies($dryRun, $force);
            }
        } catch (\Exception $e) {
            $this->error("執行失敗: {$e->getMessage()}");
            Log::error('活動記錄清理命令執行失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * 執行所有啟用的政策
     *
     * @param bool $dryRun
     * @param bool $force
     * @return int
     */
    protected function executeAllPolicies(bool $dryRun, bool $force): int
    {
        $policies = ActivityRetentionPolicy::active()->byPriority()->get();

        if ($policies->isEmpty()) {
            $this->warn('沒有找到啟用的保留政策');
            return Command::SUCCESS;
        }

        $this->info("找到 {$policies->count()} 個啟用的保留政策:");
        $this->newLine();

        // 顯示政策預覽
        $totalRecords = 0;
        foreach ($policies as $policy) {
            $preview = $this->retentionService->previewPolicyImpact($policy);
            $totalRecords += $preview['total_records'];
            
            $this->line("📋 {$policy->name}");
            $this->line("   動作: {$policy->action_text}");
            $this->line("   保留天數: {$policy->retention_days} 天");
            $this->line("   適用範圍: {$policy->scope_description}");
            $this->line("   影響記錄: {$preview['total_records']} 筆");
            $this->line("   預估大小: {$preview['estimated_size_mb']} MB");
            $this->newLine();
        }

        if ($totalRecords === 0) {
            $this->info('✅ 沒有需要處理的記錄');
            return Command::SUCCESS;
        }

        $this->warn("總計將處理 {$totalRecords} 筆記錄");
        $this->newLine();

        // 確認執行
        if (!$force && !$dryRun) {
            if (!$this->confirm('確定要執行清理操作嗎？')) {
                $this->info('操作已取消');
                return Command::SUCCESS;
            }
        }

        // 執行政策
        $this->info('開始執行保留政策...');
        $this->newLine();

        $results = $this->retentionService->executeAllPolicies($dryRun);

        // 顯示結果
        $this->displayResults($results);

        return Command::SUCCESS;
    }

    /**
     * 執行單一政策
     *
     * @param int $policyId
     * @param bool $dryRun
     * @param bool $force
     * @return int
     */
    protected function executeSinglePolicy(int $policyId, bool $dryRun, bool $force): int
    {
        $policy = ActivityRetentionPolicy::find($policyId);

        if (!$policy) {
            $this->error("找不到政策 ID: {$policyId}");
            return Command::FAILURE;
        }

        if (!$policy->is_active) {
            $this->warn("政策 '{$policy->name}' 未啟用");
            if (!$force && !$this->confirm('是否仍要執行此政策？')) {
                return Command::SUCCESS;
            }
        }

        $this->info("執行政策: {$policy->name}");
        $this->newLine();

        // 顯示政策預覽
        $preview = $this->retentionService->previewPolicyImpact($policy);
        
        $this->line("📋 政策詳情:");
        $this->line("   名稱: {$policy->name}");
        $this->line("   動作: {$policy->action_text}");
        $this->line("   保留天數: {$policy->retention_days} 天");
        $this->line("   適用範圍: {$policy->scope_description}");
        $this->line("   影響記錄: {$preview['total_records']} 筆");
        $this->line("   預估大小: {$preview['estimated_size_mb']} MB");
        $this->newLine();

        if ($preview['total_records'] === 0) {
            $this->info('✅ 沒有需要處理的記錄');
            return Command::SUCCESS;
        }

        // 確認執行
        if (!$force && !$dryRun) {
            if (!$this->confirm('確定要執行此政策嗎？')) {
                $this->info('操作已取消');
                return Command::SUCCESS;
            }
        }

        // 執行政策
        $this->info('開始執行政策...');
        $result = $this->retentionService->executePolicy($policy, $dryRun);

        // 顯示結果
        $this->displaySingleResult($result);

        return Command::SUCCESS;
    }

    /**
     * 顯示執行結果
     *
     * @param array $results
     * @return void
     */
    protected function displayResults(array $results): void
    {
        $this->newLine();
        $this->info('📊 執行結果摘要:');
        $this->newLine();

        $this->line("總政策數: {$results['total_policies']}");
        $this->line("成功執行: {$results['successful_policies']}");
        $this->line("執行失敗: {$results['failed_policies']}");
        $this->line("處理記錄: {$results['total_records_processed']} 筆");
        $this->line("刪除記錄: {$results['total_records_deleted']} 筆");
        $this->line("歸檔記錄: {$results['total_records_archived']} 筆");
        $this->line("執行時間: {$results['executed_at']}");
        
        if ($results['dry_run']) {
            $this->warn("模式: 測試執行");
        }

        $this->newLine();

        // 顯示各政策詳細結果
        if (!empty($results['policy_results'])) {
            $this->info('📋 各政策執行詳情:');
            $this->newLine();

            foreach ($results['policy_results'] as $result) {
                $status = $result['status'] === 'completed' ? '✅' : '❌';
                $this->line("{$status} {$result['policy_name']}");
                
                if ($result['status'] === 'completed') {
                    $this->line("   處理: {$result['records_processed']} 筆");
                    $this->line("   刪除: {$result['records_deleted']} 筆");
                    $this->line("   歸檔: {$result['records_archived']} 筆");
                    
                    if (!empty($result['archive_path'])) {
                        $this->line("   歸檔檔案: {$result['archive_path']}");
                    }
                } else {
                    $this->error("   錯誤: {$result['error']}");
                }
                
                $this->newLine();
            }
        }

        if ($results['successful_policies'] > 0) {
            $this->info('✅ 清理操作完成');
        } else {
            $this->error('❌ 所有政策執行失敗');
        }
    }

    /**
     * 顯示單一政策執行結果
     *
     * @param array $result
     * @return void
     */
    protected function displaySingleResult(array $result): void
    {
        $this->newLine();
        $this->info('📊 執行結果:');
        $this->newLine();

        $status = $result['status'] === 'completed' ? '✅ 成功' : '❌ 失敗';
        $this->line("狀態: {$status}");
        
        if ($result['status'] === 'completed') {
            $this->line("處理記錄: {$result['records_processed']} 筆");
            $this->line("刪除記錄: {$result['records_deleted']} 筆");
            $this->line("歸檔記錄: {$result['records_archived']} 筆");
            
            if (!empty($result['archive_path'])) {
                $this->line("歸檔檔案: {$result['archive_path']}");
            }
            
            if (!empty($result['cleanup_log_id'])) {
                $this->line("清理日誌 ID: {$result['cleanup_log_id']}");
            }
        } else {
            $this->error("錯誤訊息: {$result['error']}");
        }
    }
}