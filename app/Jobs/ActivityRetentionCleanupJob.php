<?php

namespace App\Jobs;

use App\Services\ActivityRetentionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 活動記錄保留政策清理工作
 * 
 * 定期執行活動記錄的清理和歸檔操作
 */
class ActivityRetentionCleanupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 工作超時時間（秒）
     *
     * @var int
     */
    public $timeout = 3600; // 1 小時

    /**
     * 最大重試次數
     *
     * @var int
     */
    public $tries = 3;

    /**
     * 是否為測試執行
     *
     * @var bool
     */
    protected bool $dryRun;

    /**
     * 建立新的工作實例
     *
     * @param bool $dryRun 是否為測試執行
     */
    public function __construct(bool $dryRun = false)
    {
        $this->dryRun = $dryRun;
        $this->onQueue('activities');
    }

    /**
     * 執行工作
     *
     * @param ActivityRetentionService $retentionService
     * @return void
     */
    public function handle(ActivityRetentionService $retentionService): void
    {
        Log::info('開始執行活動記錄保留政策清理', [
            'dry_run' => $this->dryRun,
            'job_id' => $this->job->getJobId(),
        ]);

        try {
            $results = $retentionService->executeAllPolicies($this->dryRun);

            Log::info('活動記錄保留政策清理完成', [
                'dry_run' => $this->dryRun,
                'total_policies' => $results['total_policies'],
                'successful_policies' => $results['successful_policies'],
                'failed_policies' => $results['failed_policies'],
                'total_records_processed' => $results['total_records_processed'],
                'total_records_deleted' => $results['total_records_deleted'],
                'total_records_archived' => $results['total_records_archived'],
                'executed_at' => $results['executed_at'],
            ]);

            // 如果有失敗的政策，記錄詳細資訊
            if ($results['failed_policies'] > 0) {
                $failedPolicies = array_filter($results['policy_results'], function ($result) {
                    return $result['status'] === 'failed';
                });

                Log::warning('部分保留政策執行失敗', [
                    'failed_policies' => $failedPolicies,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('活動記錄保留政策清理失敗', [
                'dry_run' => $this->dryRun,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * 工作失敗時的處理
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('活動記錄保留政策清理工作失敗', [
            'dry_run' => $this->dryRun,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'attempts' => $this->attempts(),
        ]);
    }

    /**
     * 計算重試延遲時間
     *
     * @return array
     */
    public function backoff(): array
    {
        return [60, 300, 900]; // 1分鐘、5分鐘、15分鐘
    }

    /**
     * 取得工作標籤
     *
     * @return array
     */
    public function tags(): array
    {
        return [
            'activity-retention',
            'cleanup',
            $this->dryRun ? 'dry-run' : 'production',
        ];
    }
}