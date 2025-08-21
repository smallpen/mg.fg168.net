<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * 權限效能監控服務
 * 
 * 監控和優化權限系統的效能
 */
class PermissionPerformanceService
{
    /**
     * 記錄操作效能指標
     * 
     * @param string $operationType 操作類型
     * @param string $operationName 操作名稱
     * @param float $executionTime 執行時間（毫秒）
     * @param int|null $memoryUsage 記憶體使用量（位元組）
     * @param array $metadata 額外資料
     * @return void
     */
    public function recordMetric(
        string $operationType,
        string $operationName,
        float $executionTime,
        ?int $memoryUsage = null,
        array $metadata = []
    ): void {
        try {
            DB::table('permission_performance_metrics')->insert([
                'operation_type' => $operationType,
                'operation_name' => $operationName,
                'execution_time_ms' => $executionTime,
                'memory_usage_bytes' => $memoryUsage,
                'metadata' => json_encode($metadata),
                'measured_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // 效能監控不應影響主要功能
            Log::warning('無法記錄效能指標', [
                'error' => $e->getMessage(),
                'operation' => $operationType . '.' . $operationName,
            ]);
        }
    }

    /**
     * 測量並記錄操作效能
     * 
     * @param string $operationType 操作類型
     * @param string $operationName 操作名稱
     * @param callable $operation 要測量的操作
     * @param array $metadata 額外資料
     * @return mixed 操作結果
     */
    public function measureOperation(
        string $operationType,
        string $operationName,
        callable $operation,
        array $metadata = []
    ) {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        try {
            $result = $operation();
            
            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);
            
            $executionTime = ($endTime - $startTime) * 1000; // 轉換為毫秒
            $memoryUsage = $endMemory - $startMemory;
            
            $this->recordMetric($operationType, $operationName, $executionTime, $memoryUsage, $metadata);
            
            return $result;
        } catch (\Exception $e) {
            $endTime = microtime(true);
            $executionTime = ($endTime - $startTime) * 1000;
            
            // 記錄失敗的操作
            $this->recordMetric($operationType, $operationName, $executionTime, null, array_merge($metadata, [
                'error' => $e->getMessage(),
                'status' => 'failed',
            ]));
            
            throw $e;
        }
    }

    /**
     * 取得效能統計報告
     * 
     * @param int $days 統計天數
     * @return array
     */
    public function getPerformanceReport(int $days = 7): array
    {
        $cacheKey = "performance_report_{$days}_days";
        
        return Cache::remember($cacheKey, 3600, function () use ($days) {
            $startDate = Carbon::now()->subDays($days);
            
            // 基本統計
            $basicStats = DB::table('permission_performance_metrics')
                           ->where('measured_at', '>=', $startDate)
                           ->selectRaw('
                               COUNT(*) as total_operations,
                               AVG(execution_time_ms) as avg_execution_time,
                               MAX(execution_time_ms) as max_execution_time,
                               MIN(execution_time_ms) as min_execution_time,
                               AVG(memory_usage_bytes) as avg_memory_usage,
                               MAX(memory_usage_bytes) as max_memory_usage
                           ')
                           ->first();

            // 按操作類型統計
            $operationTypeStats = DB::table('permission_performance_metrics')
                                   ->where('measured_at', '>=', $startDate)
                                   ->groupBy('operation_type')
                                   ->selectRaw('
                                       operation_type,
                                       COUNT(*) as count,
                                       AVG(execution_time_ms) as avg_time,
                                       MAX(execution_time_ms) as max_time,
                                       SUM(execution_time_ms) as total_time
                                   ')
                                   ->orderBy('total_time', 'desc')
                                   ->get();

            // 慢查詢統計
            $slowOperations = DB::table('permission_performance_metrics')
                               ->where('measured_at', '>=', $startDate)
                               ->where('execution_time_ms', '>', 1000) // 超過 1 秒
                               ->orderBy('execution_time_ms', 'desc')
                               ->limit(10)
                               ->get();

            // 記憶體使用統計
            $memoryStats = DB::table('permission_performance_metrics')
                            ->where('measured_at', '>=', $startDate)
                            ->whereNotNull('memory_usage_bytes')
                            ->selectRaw('
                                AVG(memory_usage_bytes) as avg_memory,
                                MAX(memory_usage_bytes) as max_memory,
                                COUNT(*) as operations_with_memory_data
                            ')
                            ->first();

            // 每日趨勢
            $dailyTrends = DB::table('permission_performance_metrics')
                            ->where('measured_at', '>=', $startDate)
                            ->selectRaw('
                                DATE(measured_at) as date,
                                COUNT(*) as operations,
                                AVG(execution_time_ms) as avg_time,
                                MAX(execution_time_ms) as max_time
                            ')
                            ->groupBy('date')
                            ->orderBy('date')
                            ->get();

            return [
                'period' => [
                    'days' => $days,
                    'start_date' => $startDate->toDateString(),
                    'end_date' => Carbon::now()->toDateString(),
                ],
                'basic_stats' => $basicStats,
                'operation_type_stats' => $operationTypeStats,
                'slow_operations' => $slowOperations,
                'memory_stats' => $memoryStats,
                'daily_trends' => $dailyTrends,
                'recommendations' => $this->generateRecommendations($basicStats, $operationTypeStats, $slowOperations),
            ];
        });
    }

    /**
     * 生成效能優化建議
     * 
     * @param object $basicStats 基本統計
     * @param \Illuminate\Support\Collection $operationTypeStats 操作類型統計
     * @param \Illuminate\Support\Collection $slowOperations 慢操作
     * @return array
     */
    private function generateRecommendations($basicStats, $operationTypeStats, $slowOperations): array
    {
        $recommendations = [];

        // 平均執行時間建議
        if ($basicStats->avg_execution_time > 500) {
            $recommendations[] = [
                'type' => 'performance',
                'priority' => 'high',
                'message' => '平均執行時間過長，建議檢查資料庫索引和查詢優化',
                'metric' => 'avg_execution_time',
                'value' => $basicStats->avg_execution_time,
            ];
        }

        // 慢查詢建議
        if ($slowOperations->count() > 0) {
            $recommendations[] = [
                'type' => 'slow_queries',
                'priority' => 'high',
                'message' => '發現慢查詢，建議優化相關操作',
                'metric' => 'slow_operations_count',
                'value' => $slowOperations->count(),
            ];
        }

        // 記憶體使用建議
        if ($basicStats->max_memory_usage > 50 * 1024 * 1024) { // 50MB
            $recommendations[] = [
                'type' => 'memory',
                'priority' => 'medium',
                'message' => '記憶體使用量較高，建議檢查資料載入策略',
                'metric' => 'max_memory_usage',
                'value' => $basicStats->max_memory_usage,
            ];
        }

        // 操作頻率建議
        $highFrequencyOps = $operationTypeStats->where('count', '>', 1000);
        if ($highFrequencyOps->count() > 0) {
            $recommendations[] = [
                'type' => 'caching',
                'priority' => 'medium',
                'message' => '高頻操作建議增加快取策略',
                'metric' => 'high_frequency_operations',
                'value' => $highFrequencyOps->count(),
            ];
        }

        return $recommendations;
    }

    /**
     * 清理舊的效能資料
     * 
     * @param int $daysToKeep 保留天數
     * @return int 清理的記錄數
     */
    public function cleanupOldMetrics(int $daysToKeep = 30): int
    {
        $cutoffDate = Carbon::now()->subDays($daysToKeep);
        
        return DB::table('permission_performance_metrics')
                 ->where('measured_at', '<', $cutoffDate)
                 ->delete();
    }

    /**
     * 取得快取效能統計
     * 
     * @return array
     */
    public function getCachePerformanceStats(): array
    {
        return Cache::remember('cache_performance_stats', 1800, function () {
            $stats = DB::table('permission_cache_statistics')
                      ->selectRaw('
                          COUNT(*) as total_cache_keys,
                          SUM(hit_count) as total_hits,
                          SUM(miss_count) as total_misses,
                          AVG(hit_rate) as avg_hit_rate,
                          SUM(size_bytes) as total_cache_size
                      ')
                      ->first();

            // 最常使用的快取鍵
            $topCacheKeys = DB::table('permission_cache_statistics')
                             ->orderBy('hit_count', 'desc')
                             ->limit(10)
                             ->get(['cache_key', 'hit_count', 'miss_count', 'hit_rate', 'size_bytes']);

            // 命中率最低的快取鍵
            $lowHitRateKeys = DB::table('permission_cache_statistics')
                               ->where('hit_rate', '<', 0.5)
                               ->orderBy('hit_rate')
                               ->limit(10)
                               ->get(['cache_key', 'hit_count', 'miss_count', 'hit_rate']);

            return [
                'overall_stats' => $stats,
                'top_cache_keys' => $topCacheKeys,
                'low_hit_rate_keys' => $lowHitRateKeys,
                'recommendations' => $this->generateCacheRecommendations($stats, $lowHitRateKeys),
            ];
        });
    }

    /**
     * 生成快取優化建議
     * 
     * @param object $stats 統計資料
     * @param \Illuminate\Support\Collection $lowHitRateKeys 低命中率快取鍵
     * @return array
     */
    private function generateCacheRecommendations($stats, $lowHitRateKeys): array
    {
        $recommendations = [];

        if ($stats->avg_hit_rate < 0.8) {
            $recommendations[] = [
                'type' => 'cache_hit_rate',
                'priority' => 'high',
                'message' => '整體快取命中率偏低，建議檢查快取策略',
                'value' => $stats->avg_hit_rate,
            ];
        }

        if ($lowHitRateKeys->count() > 0) {
            $recommendations[] = [
                'type' => 'low_hit_rate_keys',
                'priority' => 'medium',
                'message' => '部分快取鍵命中率過低，建議調整快取時間或策略',
                'value' => $lowHitRateKeys->count(),
            ];
        }

        if ($stats->total_cache_size > 100 * 1024 * 1024) { // 100MB
            $recommendations[] = [
                'type' => 'cache_size',
                'priority' => 'medium',
                'message' => '快取使用量較大，建議清理不必要的快取項目',
                'value' => $stats->total_cache_size,
            ];
        }

        return $recommendations;
    }

    /**
     * 更新快取統計
     * 
     * @param string $cacheKey 快取鍵
     * @param bool $hit 是否命中
     * @param int $size 快取大小
     * @return void
     */
    public function updateCacheStats(string $cacheKey, bool $hit, int $size = 0): void
    {
        try {
            DB::table('permission_cache_statistics')
              ->updateOrInsert(
                  ['cache_key' => $cacheKey],
                  [
                      'hit_count' => DB::raw($hit ? 'hit_count + 1' : 'hit_count'),
                      'miss_count' => DB::raw($hit ? 'miss_count' : 'miss_count + 1'),
                      'hit_rate' => DB::raw('hit_count / (hit_count + miss_count)'),
                      'size_bytes' => $size > 0 ? $size : DB::raw('size_bytes'),
                      'last_accessed_at' => now(),
                      'updated_at' => now(),
                  ]
              );
        } catch (\Exception $e) {
            Log::warning('無法更新快取統計', [
                'error' => $e->getMessage(),
                'cache_key' => $cacheKey,
            ]);
        }
    }

    /**
     * 自動效能優化
     * 
     * @return array 優化結果
     */
    public function autoOptimize(): array
    {
        $optimizations = [];

        // 清理舊的效能資料
        $cleaned = $this->cleanupOldMetrics(30);
        if ($cleaned > 0) {
            $optimizations[] = "清理了 {$cleaned} 筆舊的效能資料";
        }

        // 分析並優化慢查詢
        $slowOpsOptimized = $this->optimizeSlowOperations();
        if ($slowOpsOptimized > 0) {
            $optimizations[] = "優化了 {$slowOpsOptimized} 個慢操作";
        }

        // 優化快取策略
        $cacheOptimized = $this->optimizeCacheStrategy();
        if ($cacheOptimized > 0) {
            $optimizations[] = "優化了 {$cacheOptimized} 個快取項目";
        }

        return $optimizations;
    }

    /**
     * 優化慢操作
     * 
     * @return int 優化的操作數
     */
    private function optimizeSlowOperations(): int
    {
        // 這裡可以實作自動優化邏輯
        // 例如：調整快取時間、預載入資料等
        return 0;
    }

    /**
     * 優化快取策略
     * 
     * @return int 優化的快取項目數
     */
    private function optimizeCacheStrategy(): int
    {
        // 清理低命中率的快取項目
        $lowHitRateKeys = DB::table('permission_cache_statistics')
                           ->where('hit_rate', '<', 0.1)
                           ->where('last_accessed_at', '<', Carbon::now()->subDays(7))
                           ->pluck('cache_key');

        $cleaned = 0;
        foreach ($lowHitRateKeys as $key) {
            Cache::forget($key);
            $cleaned++;
        }

        // 刪除統計記錄
        if ($cleaned > 0) {
            DB::table('permission_cache_statistics')
              ->whereIn('cache_key', $lowHitRateKeys)
              ->delete();
        }

        return $cleaned;
    }
}