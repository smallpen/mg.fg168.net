<?php

namespace App\Jobs;

use App\Services\ActivityIntegrityService;
use App\Models\Activity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * 執行完整性檢查的佇列工作
 */
class PerformIntegrityCheck implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 工作超時時間（秒）
     *
     * @var int
     */
    public int $timeout = 300;

    /**
     * 最大重試次數
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * 檢查參數
     *
     * @var array
     */
    protected array $parameters;

    /**
     * 建立新的工作實例
     *
     * @param array $parameters 檢查參數
     */
    public function __construct(array $parameters = [])
    {
        $this->parameters = array_merge([
            'batch_size' => 100,
            'days_back' => 1,
            'auto_fix' => false,
            'send_report' => true,
        ], $parameters);
    }

    /**
     * 執行工作
     *
     * @param ActivityIntegrityService $integrityService
     * @return void
     */
    public function handle(ActivityIntegrityService $integrityService): void
    {
        Log::info('開始執行活動記錄完整性檢查工作', [
            'parameters' => $this->parameters,
            'job_id' => $this->job->getJobId(),
        ]);

        try {
            // 查詢需要檢查的活動記錄
            $activities = $this->getActivitiesToCheck();

            if ($activities->isEmpty()) {
                Log::info('沒有找到需要檢查的活動記錄');
                return;
            }

            Log::info("找到 {$activities->count()} 筆活動記錄需要檢查");

            // 執行批量完整性檢查
            $results = $integrityService->verifyBatch($activities);
            
            // 統計結果
            $totalCount = count($results);
            $validCount = array_sum($results);
            $invalidCount = $totalCount - $validCount;
            $integrityRate = $totalCount > 0 ? round(($validCount / $totalCount) * 100, 2) : 0;

            Log::info('完整性檢查完成', [
                'total_activities' => $totalCount,
                'valid_activities' => $validCount,
                'invalid_activities' => $invalidCount,
                'integrity_rate' => $integrityRate,
            ]);

            // 處理無效記錄
            if ($invalidCount > 0) {
                $this->handleInvalidActivities($integrityService, $activities, $results);
            }

            // 發送報告
            if ($this->parameters['send_report']) {
                $this->sendIntegrityReport($integrityService, $integrityRate, $invalidCount);
            }

            // 記錄成功完成
            Log::info('活動記錄完整性檢查工作完成', [
                'integrity_rate' => $integrityRate,
                'issues_found' => $invalidCount,
            ]);

        } catch (\Exception $e) {
            Log::error('活動記錄完整性檢查工作失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'parameters' => $this->parameters,
            ]);

            // 重新拋出異常以觸發重試機制
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
        Log::critical('活動記錄完整性檢查工作最終失敗', [
            'error' => $exception->getMessage(),
            'parameters' => $this->parameters,
            'attempts' => $this->attempts(),
        ]);

        // 這裡可以發送警報通知管理員
        // 例如：發送郵件、Slack 通知等
    }

    /**
     * 取得需要檢查的活動記錄
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getActivitiesToCheck()
    {
        $query = Activity::query();

        // 設定時間範圍
        if ($this->parameters['days_back'] > 0) {
            $query->where('created_at', '>=', Carbon::now()->subDays($this->parameters['days_back']));
        }

        // 只檢查有簽章的記錄
        $query->whereNotNull('signature');

        // 排序並限制數量
        return $query->orderBy('created_at', 'desc')
            ->limit($this->parameters['batch_size'])
            ->get();
    }

    /**
     * 處理無效的活動記錄
     *
     * @param ActivityIntegrityService $integrityService
     * @param \Illuminate\Database\Eloquent\Collection $activities
     * @param array $results
     * @return void
     */
    protected function handleInvalidActivities(
        ActivityIntegrityService $integrityService,
        $activities,
        array $results
    ): void {
        $invalidActivities = [];

        foreach ($results as $activityId => $isValid) {
            if (!$isValid) {
                $activity = $activities->find($activityId);
                if ($activity) {
                    $invalidActivities[] = [
                        'id' => $activity->id,
                        'type' => $activity->type,
                        'description' => $activity->description,
                        'created_at' => $activity->created_at->toISOString(),
                    ];

                    // 如果啟用自動修復，標記為可疑
                    if ($this->parameters['auto_fix']) {
                        $integrityService->markAsSuspicious(
                            $activityId,
                            '自動完整性檢查發現問題'
                        );
                    }
                }
            }
        }

        Log::warning('發現完整性問題的活動記錄', [
            'count' => count($invalidActivities),
            'activities' => array_slice($invalidActivities, 0, 10), // 只記錄前 10 筆
            'auto_fixed' => $this->parameters['auto_fix'],
        ]);
    }

    /**
     * 發送完整性報告
     *
     * @param ActivityIntegrityService $integrityService
     * @param float $integrityRate
     * @param int $invalidCount
     * @return void
     */
    protected function sendIntegrityReport(
        ActivityIntegrityService $integrityService,
        float $integrityRate,
        int $invalidCount
    ): void {
        // 生成詳細報告
        $filters = [
            'date_from' => Carbon::now()->subDays($this->parameters['days_back'])->toDateString(),
            'date_to' => Carbon::now()->toDateString(),
            'limit' => $this->parameters['batch_size'],
        ];

        $report = $integrityService->generateIntegrityReport($filters);

        Log::info('完整性檢查報告已生成', [
            'report_summary' => [
                'integrity_rate' => $integrityRate,
                'total_activities' => $report['statistics']['total_activities'],
                'invalid_activities' => $invalidCount,
                'tampered_count' => count($report['tampered_activities']),
            ],
        ]);

        // 如果完整性比率低於閾值，發送警報
        $threshold = config('activity-log.integrity_check.integrity_threshold', 95);
        if ($integrityRate < $threshold) {
            Log::alert('活動記錄完整性比率低於閾值', [
                'current_rate' => $integrityRate,
                'threshold' => $threshold,
                'invalid_count' => $invalidCount,
                'requires_immediate_attention' => true,
            ]);
        }

        // 這裡可以整合郵件或其他通知系統
        // 發送報告給管理員
    }

    /**
     * 計算工作延遲時間（用於重試）
     *
     * @return int
     */
    public function backoff(): int
    {
        return $this->attempts() * 60; // 每次重試延遲增加 60 秒
    }
}
