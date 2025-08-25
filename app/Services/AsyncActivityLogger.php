<?php

namespace App\Services;

use App\Jobs\LogActivityJob;
use App\Jobs\LogActivitiesBatchJob;
use App\Jobs\ActivityPerformanceMonitorJob;
use App\Models\Activity;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * 非同步活動記錄服務
 * 
 * 提供高效能的非同步活動記錄功能，包含批量處理、失敗重試和效能監控
 */
class AsyncActivityLogger
{
    /**
     * 預設佇列名稱
     */
    protected string $defaultQueue = 'activities';

    /**
     * 高優先級佇列名稱
     */
    protected string $highPriorityQueue = 'activities-high';

    /**
     * 批量處理佇列名稱
     */
    protected string $batchQueue = 'activities-batch';

    /**
     * 批量處理大小
     */
    protected int $batchSize = 100;

    /**
     * 批量處理等待時間（秒）
     */
    protected int $batchWaitTime = 30;

    /**
     * 效能監控閾值（毫秒）
     */
    protected int $performanceThreshold = 1000;

    /**
     * 快取鍵前綴
     */
    protected string $cachePrefix = 'async_activity_logger';

    /**
     * 非同步記錄單一活動
     * 
     * @param string $type 活動類型
     * @param string $description 活動描述
     * @param array $data 活動資料
     * @param string|null $queue 指定佇列名稱
     * @param int $delay 延遲執行時間（秒）
     * @return string 工作 ID
     */
    public function logAsync(string $type, string $description, array $data = [], ?string $queue = null, int $delay = 0): string
    {
        $activityData = $this->prepareActivityData($type, $description, $data);
        
        // 決定使用的佇列
        $queueName = $queue ?? $this->determineQueue($type, $data);
        
        // 建立工作
        $job = new LogActivityJob($activityData);
        
        if ($delay > 0) {
            $job = $job->delay($delay);
        }
        
        // 分派工作
        $pendingDispatch = dispatch($job->onQueue($queueName));
        $jobId = uniqid('job_');
        
        // 記錄工作分派
        $this->recordJobDispatch($jobId, $type, $queueName);
        
        // 更新效能指標
        $this->updatePerformanceMetrics('single_dispatch', microtime(true));
        
        return $jobId;
    }

    /**
     * 非同步批量記錄活動
     * 
     * @param array $activities 活動陣列
     * @param string|null $queue 指定佇列名稱
     * @return array 工作 ID 陣列
     */
    public function logBatchAsync(array $activities, ?string $queue = null): array
    {
        $startTime = microtime(true);
        $jobIds = [];
        
        // 準備活動資料
        $preparedActivities = [];
        foreach ($activities as $activity) {
            $preparedActivities[] = $this->prepareActivityData(
                $activity['type'],
                $activity['description'],
                $activity['data'] ?? []
            );
        }
        
        // 分批處理
        $chunks = array_chunk($preparedActivities, $this->batchSize);
        $queueName = $queue ?? $this->batchQueue;
        
        foreach ($chunks as $chunk) {
            $job = new LogActivitiesBatchJob($chunk);
            $pendingDispatch = dispatch($job->onQueue($queueName));
            $jobId = uniqid('batch_job_');
            $jobIds[] = $jobId;
        }
        
        // 記錄批量分派
        $this->recordBatchDispatch($jobIds, count($activities), $queueName);
        
        // 更新效能指標
        $processingTime = (microtime(true) - $startTime) * 1000;
        $this->updatePerformanceMetrics('batch_dispatch', $processingTime);
        
        return $jobIds;
    }

    /**
     * 新增活動到批量佇列
     * 
     * @param string $type 活動類型
     * @param string $description 活動描述
     * @param array $data 活動資料
     * @return void
     */
    public function addToBatch(string $type, string $description, array $data = []): void
    {
        $activityData = $this->prepareActivityData($type, $description, $data);
        
        // 新增到批量快取
        $batchKey = $this->getBatchCacheKey();
        $batch = Cache::get($batchKey, []);
        $batch[] = $activityData;
        
        Cache::put($batchKey, $batch, $this->batchWaitTime + 60);
        
        // 檢查是否達到批量大小
        if (count($batch) >= $this->batchSize) {
            $this->processBatch($batchKey);
        }
    }

    /**
     * 強制處理當前批量
     * 
     * @return int 處理的活動數量
     */
    public function flushBatch(): int
    {
        $batchKey = $this->getBatchCacheKey();
        return $this->processBatch($batchKey);
    }

    /**
     * 取得佇列統計資訊
     * 
     * @return array
     */
    public function getQueueStats(): array
    {
        return [
            'queues' => [
                'activities' => $this->getQueueSize($this->defaultQueue),
                'activities-high' => $this->getQueueSize($this->highPriorityQueue),
                'activities-batch' => $this->getQueueSize($this->batchQueue),
            ],
            'performance' => $this->getPerformanceMetrics(),
            'failed_jobs' => $this->getFailedJobsCount(),
            'processing_rate' => $this->getProcessingRate(),
        ];
    }

    /**
     * 取得效能指標
     * 
     * @return array
     */
    public function getPerformanceMetrics(): array
    {
        $cacheKey = "{$this->cachePrefix}:performance";
        
        return Cache::get($cacheKey, [
            'average_dispatch_time' => 0,
            'average_processing_time' => 0,
            'total_dispatched' => 0,
            'total_processed' => 0,
            'success_rate' => 100,
            'last_updated' => now()->toISOString(),
        ]);
    }

    /**
     * 清理過期的效能資料
     * 
     * @param int $days 保留天數
     * @return int 清理的記錄數
     */
    public function cleanupPerformanceData(int $days = 7): int
    {
        $cutoffDate = now()->subDays($days);
        $cleaned = 0;
        
        // 清理快取中的過期資料
        $keys = [
            "{$this->cachePrefix}:jobs:*",
            "{$this->cachePrefix}:batches:*",
            "{$this->cachePrefix}:performance:*",
        ];
        
        foreach ($keys as $pattern) {
            $matchingKeys = Cache::getRedis()->keys($pattern);
            foreach ($matchingKeys as $key) {
                $data = Cache::get($key);
                if (isset($data['created_at']) && $data['created_at'] < $cutoffDate) {
                    Cache::forget($key);
                    $cleaned++;
                }
            }
        }
        
        return $cleaned;
    }

    /**
     * 監控佇列健康狀態
     * 
     * @return array
     */
    public function monitorQueueHealth(): array
    {
        $stats = $this->getQueueStats();
        $health = [
            'status' => 'healthy',
            'issues' => [],
            'recommendations' => [],
        ];
        
        // 檢查佇列大小
        foreach ($stats['queues'] as $queueName => $size) {
            if ($size > 1000) {
                $health['status'] = 'warning';
                $health['issues'][] = "佇列 {$queueName} 積壓過多工作 ({$size} 個)";
                $health['recommendations'][] = "考慮增加 worker 數量或優化處理邏輯";
            }
        }
        
        // 檢查失敗率
        if ($stats['performance']['success_rate'] < 95) {
            $health['status'] = 'critical';
            $health['issues'][] = "成功率過低 ({$stats['performance']['success_rate']}%)";
            $health['recommendations'][] = "檢查失敗工作的錯誤原因並修復";
        }
        
        // 檢查處理速度
        if ($stats['performance']['average_processing_time'] > $this->performanceThreshold) {
            $health['status'] = $health['status'] === 'critical' ? 'critical' : 'warning';
            $health['issues'][] = "平均處理時間過長 ({$stats['performance']['average_processing_time']}ms)";
            $health['recommendations'][] = "優化活動記錄邏輯或增加資料庫效能";
        }
        
        return $health;
    }

    /**
     * 準備活動資料
     * 
     * @param string $type
     * @param string $description
     * @param array $data
     * @return array
     */
    protected function prepareActivityData(string $type, string $description, array $data): array
    {
        $userId = $data['user_id'] ?? auth()->id();
        
        return [
            'type' => $type,
            'description' => $description,
            'module' => $data['module'] ?? null,
            'user_id' => is_numeric($userId) ? (int) $userId : null,
            'subject_id' => $data['subject_id'] ?? null,
            'subject_type' => $data['subject_type'] ?? null,
            'properties' => $data['properties'] ?? null,
            'ip_address' => $data['ip_address'] ?? request()->ip(),
            'user_agent' => $data['user_agent'] ?? request()->userAgent(),
            'result' => $data['result'] ?? 'success',
            'risk_level' => $data['risk_level'] ?? 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * 決定使用的佇列
     * 
     * @param string $type
     * @param array $data
     * @return string
     */
    protected function determineQueue(string $type, array $data): string
    {
        // 高優先級活動類型
        $highPriorityTypes = [
            'security_incident',
            'login_failed',
            'permission_escalation',
            'system_error',
            'data_breach',
        ];
        
        // 高風險等級
        $riskLevel = $data['risk_level'] ?? 1;
        
        if (in_array($type, $highPriorityTypes) || $riskLevel >= 7) {
            return $this->highPriorityQueue;
        }
        
        return $this->defaultQueue;
    }

    /**
     * 記錄工作分派
     * 
     * @param string $jobId
     * @param string $type
     * @param string $queue
     * @return void
     */
    protected function recordJobDispatch(string $jobId, string $type, string $queue): void
    {
        $cacheKey = "{$this->cachePrefix}:jobs:{$jobId}";
        
        Cache::put($cacheKey, [
            'job_id' => $jobId,
            'type' => $type,
            'queue' => $queue,
            'dispatched_at' => now()->toISOString(),
            'status' => 'dispatched',
        ], 3600); // 1小時
    }

    /**
     * 記錄批量分派
     * 
     * @param array $jobIds
     * @param int $activityCount
     * @param string $queue
     * @return void
     */
    protected function recordBatchDispatch(array $jobIds, int $activityCount, string $queue): void
    {
        $batchId = uniqid('batch_');
        $cacheKey = "{$this->cachePrefix}:batches:{$batchId}";
        
        Cache::put($cacheKey, [
            'batch_id' => $batchId,
            'job_ids' => $jobIds,
            'activity_count' => $activityCount,
            'queue' => $queue,
            'dispatched_at' => now()->toISOString(),
            'status' => 'dispatched',
        ], 3600); // 1小時
    }

    /**
     * 取得批量快取鍵
     * 
     * @return string
     */
    protected function getBatchCacheKey(): string
    {
        $minute = now()->format('Y-m-d-H-i');
        return "{$this->cachePrefix}:batch:{$minute}";
    }

    /**
     * 處理批量
     * 
     * @param string $batchKey
     * @return int
     */
    protected function processBatch(string $batchKey): int
    {
        $batch = Cache::get($batchKey, []);
        
        if (empty($batch)) {
            return 0;
        }
        
        // 分派批量工作
        $job = new LogActivitiesBatchJob($batch);
        dispatch($job->onQueue($this->batchQueue));
        
        // 清除快取
        Cache::forget($batchKey);
        
        return count($batch);
    }

    /**
     * 取得佇列大小
     * 
     * @param string $queue
     * @return int
     */
    protected function getQueueSize(string $queue): int
    {
        try {
            return Queue::size($queue);
        } catch (\Exception $e) {
            Log::warning("無法取得佇列大小: {$queue}", ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * 取得失敗工作數量
     * 
     * @return int
     */
    protected function getFailedJobsCount(): int
    {
        try {
            return \DB::table('failed_jobs')->count();
        } catch (\Exception $e) {
            Log::warning("無法取得失敗工作數量", ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * 取得處理速率
     * 
     * @return float
     */
    protected function getProcessingRate(): float
    {
        $cacheKey = "{$this->cachePrefix}:processing_rate";
        
        return Cache::get($cacheKey, 0.0);
    }

    /**
     * 更新效能指標
     * 
     * @param string $metric
     * @param float $value
     * @return void
     */
    protected function updatePerformanceMetrics(string $metric, float $value): void
    {
        $cacheKey = "{$this->cachePrefix}:performance";
        $metrics = Cache::get($cacheKey, [
            'average_dispatch_time' => 0,
            'average_processing_time' => 0,
            'total_dispatched' => 0,
            'total_processed' => 0,
            'success_rate' => 100,
        ]);
        
        switch ($metric) {
            case 'single_dispatch':
            case 'batch_dispatch':
                $metrics['total_dispatched']++;
                $metrics['average_dispatch_time'] = (
                    ($metrics['average_dispatch_time'] * ($metrics['total_dispatched'] - 1)) + $value
                ) / $metrics['total_dispatched'];
                break;
                
            case 'processing_time':
                $metrics['total_processed']++;
                $metrics['average_processing_time'] = (
                    ($metrics['average_processing_time'] * ($metrics['total_processed'] - 1)) + $value
                ) / $metrics['total_processed'];
                break;
        }
        
        $metrics['last_updated'] = now()->toISOString();
        
        Cache::put($cacheKey, $metrics, 3600); // 1小時
    }
}