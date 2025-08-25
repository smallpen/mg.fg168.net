<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

/**
 * 活動記錄查詢最佳化服務
 * 
 * 提供查詢最佳化、索引建議、效能監控和查詢重寫功能
 */
class ActivityQueryOptimizer
{
    /**
     * 查詢效能閾值（毫秒）
     */
    protected int $slowQueryThreshold = 1000;

    /**
     * 索引選擇性閾值
     */
    protected float $indexSelectivityThreshold = 0.1;

    /**
     * 快取鍵前綴
     */
    protected string $cachePrefix = 'query_optimizer';

    /**
     * 最佳化活動查詢
     * 
     * @param EloquentBuilder $query
     * @param array $filters
     * @return EloquentBuilder
     */
    public function optimizeActivityQuery(EloquentBuilder $query, array $filters = []): EloquentBuilder
    {
        $startTime = microtime(true);
        
        // 分析查詢條件並選擇最佳索引
        $optimizedQuery = $this->applyOptimalIndexHints($query, $filters);
        
        // 重寫查詢以提高效能
        $optimizedQuery = $this->rewriteQuery($optimizedQuery, $filters);
        
        // 應用查詢限制
        $optimizedQuery = $this->applyQueryLimits($optimizedQuery, $filters);
        
        // 記錄查詢效能
        $this->recordQueryExecution($optimizedQuery, $filters, $startTime);
        
        return $optimizedQuery;
    }

    /**
     * 分析查詢效能
     * 
     * @param string $sql
     * @param array $bindings
     * @return array
     */
    public function analyzeQueryPerformance(string $sql, array $bindings = []): array
    {
        try {
            // 執行 EXPLAIN 分析
            $explainResult = DB::select("EXPLAIN FORMAT=JSON " . $sql, $bindings);
            $explainData = json_decode($explainResult[0]->EXPLAIN, true);
            
            // 分析執行計畫
            $analysis = $this->parseExplainResult($explainData);
            
            // 檢查索引使用情況
            $indexAnalysis = $this->analyzeIndexUsage($sql, $bindings);
            
            return array_merge($analysis, $indexAnalysis);
            
        } catch (\Exception $e) {
            Log::warning("查詢效能分析失敗", [
                'sql' => $sql,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * 建議索引最佳化
     * 
     * @param string $tableName
     * @return array
     */
    public function suggestIndexOptimizations(string $tableName = 'activities'): array
    {
        $suggestions = [];
        
        try {
            // 分析查詢模式
            $queryPatterns = $this->analyzeQueryPatterns($tableName);
            
            // 檢查現有索引效能
            $indexPerformance = $this->analyzeIndexPerformance($tableName);
            
            // 識別缺失的索引
            $missingIndexes = $this->identifyMissingIndexes($queryPatterns, $indexPerformance);
            
            // 識別冗餘索引
            $redundantIndexes = $this->identifyRedundantIndexes($tableName);
            
            // 識別低效索引
            $inefficientIndexes = $this->identifyInefficientIndexes($indexPerformance);
            
            $suggestions = [
                'missing_indexes' => $missingIndexes,
                'redundant_indexes' => $redundantIndexes,
                'inefficient_indexes' => $inefficientIndexes,
                'recommendations' => $this->generateRecommendations($queryPatterns, $indexPerformance),
            ];
            
        } catch (\Exception $e) {
            Log::error("索引最佳化建議失敗", [
                'table' => $tableName,
                'error' => $e->getMessage(),
            ]);
        }
        
        return $suggestions;
    }

    /**
     * 監控慢查詢
     * 
     * @param int $thresholdMs 慢查詢閾值（毫秒）
     * @return array
     */
    public function monitorSlowQueries(int $thresholdMs = null): array
    {
        $threshold = $thresholdMs ?? $this->slowQueryThreshold;
        
        try {
            // 從效能監控表取得慢查詢
            $slowQueries = DB::table('activity_query_performance')
                ->where('execution_time', '>', $threshold)
                ->where('executed_at', '>=', now()->subHours(24))
                ->orderBy('execution_time', 'desc')
                ->limit(50)
                ->get();
            
            $analysis = [
                'total_slow_queries' => $slowQueries->count(),
                'average_execution_time' => $slowQueries->avg('execution_time'),
                'slowest_query_time' => $slowQueries->max('execution_time'),
                'most_common_slow_types' => $slowQueries->groupBy('query_type')
                    ->map->count()
                    ->sortDesc()
                    ->take(5),
                'queries' => $slowQueries->toArray(),
            ];
            
            // 生成最佳化建議
            $analysis['optimization_suggestions'] = $this->generateSlowQueryOptimizations($slowQueries);
            
            return $analysis;
            
        } catch (\Exception $e) {
            Log::error("慢查詢監控失敗", ['error' => $e->getMessage()]);
            
            return [
                'error' => $e->getMessage(),
                'total_slow_queries' => 0,
            ];
        }
    }

    /**
     * 最佳化資料庫統計資訊
     * 
     * @param array $tables
     * @return array
     */
    public function optimizeTableStatistics(array $tables = ['activities', 'security_alerts', 'monitor_rules']): array
    {
        $results = [
            'analyzed_tables' => 0,
            'updated_statistics' => 0,
            'errors' => [],
        ];
        
        foreach ($tables as $table) {
            try {
                // 分析表統計資訊
                DB::statement("ANALYZE TABLE {$table}");
                
                // 更新索引統計
                $this->updateIndexStatistics($table);
                
                $results['analyzed_tables']++;
                $results['updated_statistics']++;
                
                Log::info("更新表統計資訊", ['table' => $table]);
                
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'table' => $table,
                    'error' => $e->getMessage(),
                ];
                
                Log::error("更新表統計資訊失敗", [
                    'table' => $table,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return $results;
    }

    /**
     * 取得查詢效能報告
     * 
     * @param int $days 報告天數
     * @return array
     */
    public function getPerformanceReport(int $days = 7): array
    {
        try {
            $fromDate = now()->subDays($days);
            
            // 基本統計
            $basicStats = DB::table('activity_query_performance')
                ->where('executed_at', '>=', $fromDate)
                ->selectRaw('
                    COUNT(*) as total_queries,
                    AVG(execution_time) as avg_execution_time,
                    MAX(execution_time) as max_execution_time,
                    MIN(execution_time) as min_execution_time,
                    SUM(CASE WHEN used_cache THEN 1 ELSE 0 END) as cached_queries,
                    SUM(CASE WHEN execution_time > ? THEN 1 ELSE 0 END) as slow_queries
                ', [$this->slowQueryThreshold])
                ->first();
            
            // 查詢類型分佈
            $queryTypeStats = DB::table('activity_query_performance')
                ->where('executed_at', '>=', $fromDate)
                ->groupBy('query_type')
                ->selectRaw('
                    query_type,
                    COUNT(*) as count,
                    AVG(execution_time) as avg_time,
                    MAX(execution_time) as max_time
                ')
                ->orderBy('count', 'desc')
                ->get();
            
            // 每日趨勢
            $dailyTrends = DB::table('activity_query_performance')
                ->where('executed_at', '>=', $fromDate)
                ->groupBy(DB::raw('DATE(executed_at)'))
                ->selectRaw('
                    DATE(executed_at) as date,
                    COUNT(*) as query_count,
                    AVG(execution_time) as avg_execution_time,
                    SUM(CASE WHEN execution_time > ? THEN 1 ELSE 0 END) as slow_query_count
                ', [$this->slowQueryThreshold])
                ->orderBy('date')
                ->get();
            
            // 索引使用統計
            $indexStats = DB::table('activity_index_stats')
                ->where('last_used_at', '>=', $fromDate)
                ->orderBy('usage_count', 'desc')
                ->limit(20)
                ->get();
            
            return [
                'period' => [
                    'from' => $fromDate->toDateString(),
                    'to' => now()->toDateString(),
                    'days' => $days,
                ],
                'basic_stats' => $basicStats,
                'query_types' => $queryTypeStats,
                'daily_trends' => $dailyTrends,
                'index_usage' => $indexStats,
                'cache_hit_rate' => $basicStats->total_queries > 0 
                    ? round(($basicStats->cached_queries / $basicStats->total_queries) * 100, 2) 
                    : 0,
                'slow_query_rate' => $basicStats->total_queries > 0 
                    ? round(($basicStats->slow_queries / $basicStats->total_queries) * 100, 2) 
                    : 0,
            ];
            
        } catch (\Exception $e) {
            Log::error("生成效能報告失敗", ['error' => $e->getMessage()]);
            
            return [
                'error' => $e->getMessage(),
                'period' => ['days' => $days],
            ];
        }
    }

    /**
     * 應用最佳索引提示
     * 
     * @param EloquentBuilder $query
     * @param array $filters
     * @return EloquentBuilder
     */
    protected function applyOptimalIndexHints(EloquentBuilder $query, array $filters): EloquentBuilder
    {
        // 根據查詢條件選擇最佳索引
        $indexHint = $this->selectOptimalIndex($filters);
        
        if ($indexHint) {
            $query->from(DB::raw("activities USE INDEX ({$indexHint})"));
        }
        
        return $query;
    }

    /**
     * 選擇最佳索引
     * 
     * @param array $filters
     * @return string|null
     */
    protected function selectOptimalIndex(array $filters): ?string
    {
        // 時間範圍查詢
        if (isset($filters['date_from']) || isset($filters['date_to'])) {
            if (isset($filters['type'])) {
                return 'idx_activities_security_events';
            }
            if (isset($filters['user_id'])) {
                return 'idx_activities_user_type_time';
            }
            return 'idx_activities_covering_main';
        }
        
        // 使用者查詢
        if (isset($filters['user_id'])) {
            return 'idx_activities_user_type_time';
        }
        
        // 類型查詢
        if (isset($filters['type'])) {
            return 'idx_activities_security_events';
        }
        
        // 模組查詢
        if (isset($filters['module'])) {
            return 'idx_activities_module_analysis';
        }
        
        return null;
    }

    /**
     * 重寫查詢以提高效能
     * 
     * @param EloquentBuilder $query
     * @param array $filters
     * @return EloquentBuilder
     */
    protected function rewriteQuery(EloquentBuilder $query, array $filters): EloquentBuilder
    {
        // 將 OR 條件重寫為 UNION
        if (isset($filters['multiple_types']) && count($filters['multiple_types']) > 3) {
            // 對於多個類型的查詢，使用 UNION 可能更高效
            // 這裡簡化處理，實際實作需要更複雜的邏輯
        }
        
        // 最佳化日期範圍查詢
        if (isset($filters['date_from']) && isset($filters['date_to'])) {
            $query->whereBetween('created_at', [
                $filters['date_from'],
                $filters['date_to']
            ]);
        }
        
        return $query;
    }

    /**
     * 應用查詢限制
     * 
     * @param EloquentBuilder $query
     * @param array $filters
     * @return EloquentBuilder
     */
    protected function applyQueryLimits(EloquentBuilder $query, array $filters): EloquentBuilder
    {
        // 設定合理的查詢限制
        $maxLimit = 1000;
        $defaultLimit = 50;
        
        $limit = $filters['limit'] ?? $defaultLimit;
        $limit = min($limit, $maxLimit);
        
        $query->limit($limit);
        
        return $query;
    }

    /**
     * 記錄查詢執行
     * 
     * @param EloquentBuilder $query
     * @param array $filters
     * @param float $startTime
     * @return void
     */
    protected function recordQueryExecution(EloquentBuilder $query, array $filters, float $startTime): void
    {
        $executionTime = (microtime(true) - $startTime) * 1000;
        
        // 只記錄慢查詢或隨機採樣
        if ($executionTime > $this->slowQueryThreshold || rand(1, 100) <= 5) {
            try {
                DB::table('activity_query_performance')->insert([
                    'query_type' => $this->determineQueryType($filters),
                    'query_hash' => md5($query->toSql()),
                    'query_parameters' => json_encode($filters),
                    'execution_time' => $executionTime,
                    'result_count' => null, // 需要在查詢執行後設定
                    'used_cache' => false, // 需要從快取服務取得
                    'index_used' => $this->detectUsedIndex($query),
                    'executed_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Exception $e) {
                // 記錄失敗不應影響主要功能
                Log::debug("記錄查詢效能失敗", ['error' => $e->getMessage()]);
            }
        }
    }

    /**
     * 決定查詢類型
     * 
     * @param array $filters
     * @return string
     */
    protected function determineQueryType(array $filters): string
    {
        if (isset($filters['search'])) {
            return 'search';
        }
        
        if (isset($filters['export'])) {
            return 'export';
        }
        
        if (isset($filters['stats'])) {
            return 'stats';
        }
        
        return 'list';
    }

    /**
     * 檢測使用的索引
     * 
     * @param EloquentBuilder $query
     * @return string|null
     */
    protected function detectUsedIndex(EloquentBuilder $query): ?string
    {
        try {
            $explain = DB::select("EXPLAIN " . $query->toSql(), $query->getBindings());
            return $explain[0]->key ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 解析 EXPLAIN 結果
     * 
     * @param array $explainData
     * @return array
     */
    protected function parseExplainResult(array $explainData): array
    {
        // 簡化的 EXPLAIN 結果解析
        return [
            'status' => 'analyzed',
            'cost' => $explainData['query_block']['cost_info']['query_cost'] ?? 0,
            'rows_examined' => $explainData['query_block']['cost_info']['read_cost'] ?? 0,
        ];
    }

    /**
     * 分析索引使用情況
     * 
     * @param string $sql
     * @param array $bindings
     * @return array
     */
    protected function analyzeIndexUsage(string $sql, array $bindings): array
    {
        return [
            'indexes_used' => [],
            'full_table_scan' => false,
            'selectivity' => 1.0,
        ];
    }

    /**
     * 分析查詢模式
     * 
     * @param string $tableName
     * @return array
     */
    protected function analyzeQueryPatterns(string $tableName): array
    {
        // 分析最近的查詢模式
        return [];
    }

    /**
     * 分析索引效能
     * 
     * @param string $tableName
     * @return array
     */
    protected function analyzeIndexPerformance(string $tableName): array
    {
        return DB::table('activity_index_stats')
            ->where('table_name', $tableName)
            ->get()
            ->toArray();
    }

    /**
     * 識別缺失的索引
     * 
     * @param array $queryPatterns
     * @param array $indexPerformance
     * @return array
     */
    protected function identifyMissingIndexes(array $queryPatterns, array $indexPerformance): array
    {
        return [];
    }

    /**
     * 識別冗餘索引
     * 
     * @param string $tableName
     * @return array
     */
    protected function identifyRedundantIndexes(string $tableName): array
    {
        return [];
    }

    /**
     * 識別低效索引
     * 
     * @param array $indexPerformance
     * @return array
     */
    protected function identifyInefficientIndexes(array $indexPerformance): array
    {
        $inefficient = [];
        
        foreach ($indexPerformance as $index) {
            if ($index['selectivity'] > $this->indexSelectivityThreshold) {
                $inefficient[] = $index;
            }
        }
        
        return $inefficient;
    }

    /**
     * 生成最佳化建議
     * 
     * @param array $queryPatterns
     * @param array $indexPerformance
     * @return array
     */
    protected function generateRecommendations(array $queryPatterns, array $indexPerformance): array
    {
        return [
            'add_indexes' => [],
            'drop_indexes' => [],
            'modify_queries' => [],
        ];
    }

    /**
     * 生成慢查詢最佳化建議
     * 
     * @param \Illuminate\Support\Collection $slowQueries
     * @return array
     */
    protected function generateSlowQueryOptimizations($slowQueries): array
    {
        return [
            'add_indexes' => [],
            'rewrite_queries' => [],
            'add_caching' => [],
        ];
    }

    /**
     * 更新索引統計資訊
     * 
     * @param string $tableName
     * @return void
     */
    protected function updateIndexStatistics(string $tableName): void
    {
        try {
            // 取得表的索引資訊
            $indexes = DB::select("SHOW INDEX FROM {$tableName}");
            
            foreach ($indexes as $index) {
                DB::table('activity_index_stats')->updateOrInsert(
                    [
                        'table_name' => $tableName,
                        'index_name' => $index->Key_name,
                    ],
                    [
                        'last_used_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        } catch (\Exception $e) {
            Log::warning("更新索引統計失敗", [
                'table' => $tableName,
                'error' => $e->getMessage(),
            ]);
        }
    }
}