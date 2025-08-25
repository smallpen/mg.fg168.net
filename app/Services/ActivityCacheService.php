<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

/**
 * 活動記錄快取服務
 * 
 * 提供活動記錄查詢結果的快取機制，包含智慧快取、快取預熱和失效管理
 */
class ActivityCacheService
{
    /**
     * 快取鍵前綴
     */
    protected string $cachePrefix = 'activity_cache';

    /**
     * 預設快取時間（秒）
     */
    protected int $defaultTtl = 3600; // 1小時

    /**
     * 統計資料快取時間（秒）
     */
    protected int $statsTtl = 1800; // 30分鐘

    /**
     * 熱門查詢快取時間（秒）
     */
    protected int $hotQueryTtl = 7200; // 2小時

    /**
     * 快取標籤
     */
    protected array $cacheTags = ['activities', 'activity_stats', 'activity_queries'];

    /**
     * 快取活動列表查詢結果
     * 
     * @param string $queryKey 查詢鍵
     * @param callable $queryCallback 查詢回調函數
     * @param array $filters 查詢條件
     * @param int|null $ttl 快取時間
     * @return mixed
     */
    public function cacheActivityQuery(string $queryKey, callable $queryCallback, array $filters = [], ?int $ttl = null): mixed
    {
        $cacheKey = $this->generateQueryCacheKey($queryKey, $filters);
        $ttl = $ttl ?? $this->determineTtl($filters);
        
        try {
            return Cache::tags($this->cacheTags)->remember($cacheKey, $ttl, function () use ($queryCallback, $cacheKey) {
                $startTime = microtime(true);
                $result = $queryCallback();
                $executionTime = (microtime(true) - $startTime) * 1000;
                
                // 記錄查詢效能
                $this->recordQueryPerformance($cacheKey, $executionTime, 'cache_miss');
                
                // 如果是分頁結果，轉換為可快取的格式
                if ($result instanceof LengthAwarePaginator) {
                    return $this->serializePaginator($result);
                }
                
                return $result;
            });
            
        } catch (\Exception $e) {
            Log::warning("快取查詢失敗，直接執行查詢", [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage(),
            ]);
            
            // 快取失敗時直接執行查詢
            return $queryCallback();
        }
    }

    /**
     * 快取活動統計資料
     * 
     * @param string $statsType 統計類型
     * @param callable $statsCallback 統計回調函數
     * @param array $parameters 統計參數
     * @return mixed
     */
    public function cacheActivityStats(string $statsType, callable $statsCallback, array $parameters = []): mixed
    {
        $cacheKey = $this->generateStatsCacheKey($statsType, $parameters);
        
        return Cache::tags(['activity_stats'])->remember($cacheKey, $this->statsTtl, function () use ($statsCallback, $cacheKey) {
            $startTime = microtime(true);
            $result = $statsCallback();
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            // 記錄統計查詢效能
            $this->recordQueryPerformance($cacheKey, $executionTime, 'stats_cache_miss');
            
            return $result;
        });
    }

    /**
     * 快取使用者活動摘要
     * 
     * @param int $userId 使用者ID
     * @param callable $summaryCallback 摘要回調函數
     * @return mixed
     */
    public function cacheUserActivitySummary(int $userId, callable $summaryCallback): mixed
    {
        $cacheKey = "{$this->cachePrefix}:user_summary:{$userId}";
        
        return Cache::tags(['user_activities'])->remember($cacheKey, $this->defaultTtl, $summaryCallback);
    }

    /**
     * 快取熱門查詢結果
     * 
     * @param string $queryType 查詢類型
     * @param callable $queryCallback 查詢回調函數
     * @param array $parameters 查詢參數
     * @return mixed
     */
    public function cacheHotQuery(string $queryType, callable $queryCallback, array $parameters = []): mixed
    {
        $cacheKey = $this->generateHotQueryCacheKey($queryType, $parameters);
        
        // 檢查是否為熱門查詢
        if (!$this->isHotQuery($queryType, $parameters)) {
            return $queryCallback();
        }
        
        return Cache::tags(['hot_queries'])->remember($cacheKey, $this->hotQueryTtl, function () use ($queryCallback, $cacheKey) {
            $startTime = microtime(true);
            $result = $queryCallback();
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            // 記錄熱門查詢效能
            $this->recordQueryPerformance($cacheKey, $executionTime, 'hot_query_cache_miss');
            
            return $result;
        });
    }

    /**
     * 預熱快取
     * 
     * @param array $queries 要預熱的查詢
     * @return array 預熱結果
     */
    public function warmupCache(array $queries = []): array
    {
        $results = [
            'warmed_queries' => 0,
            'failed_queries' => 0,
            'total_time' => 0,
            'errors' => [],
        ];
        
        $startTime = microtime(true);
        
        // 預設預熱查詢
        $defaultQueries = [
            'recent_activities' => [
                'type' => 'activity_query',
                'callback' => function () {
                    return \App\Models\Activity::with('user')
                        ->orderBy('created_at', 'desc')
                        ->limit(50)
                        ->get();
                },
                'filters' => ['recent' => true],
            ],
            'daily_stats' => [
                'type' => 'stats',
                'callback' => function () {
                    return \App\Models\Activity::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                        ->where('created_at', '>=', now()->subDays(7))
                        ->groupBy('date')
                        ->orderBy('date', 'desc')
                        ->get();
                },
                'parameters' => ['period' => 'daily', 'days' => 7],
            ],
            'activity_types' => [
                'type' => 'stats',
                'callback' => function () {
                    return \App\Models\Activity::selectRaw('type, COUNT(*) as count')
                        ->where('created_at', '>=', now()->subDays(30))
                        ->groupBy('type')
                        ->orderBy('count', 'desc')
                        ->get();
                },
                'parameters' => ['period' => 'monthly', 'group_by' => 'type'],
            ],
        ];
        
        $queriesToWarmup = array_merge($defaultQueries, $queries);
        
        foreach ($queriesToWarmup as $queryName => $queryConfig) {
            try {
                switch ($queryConfig['type']) {
                    case 'activity_query':
                        $this->cacheActivityQuery(
                            $queryName,
                            $queryConfig['callback'],
                            $queryConfig['filters'] ?? []
                        );
                        break;
                        
                    case 'stats':
                        $this->cacheActivityStats(
                            $queryName,
                            $queryConfig['callback'],
                            $queryConfig['parameters'] ?? []
                        );
                        break;
                        
                    case 'hot_query':
                        $this->cacheHotQuery(
                            $queryName,
                            $queryConfig['callback'],
                            $queryConfig['parameters'] ?? []
                        );
                        break;
                }
                
                $results['warmed_queries']++;
                
            } catch (\Exception $e) {
                $results['failed_queries']++;
                $results['errors'][] = [
                    'query' => $queryName,
                    'error' => $e->getMessage(),
                ];
                
                Log::warning("預熱快取失敗", [
                    'query_name' => $queryName,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        $results['total_time'] = (microtime(true) - $startTime) * 1000;
        
        Log::info("快取預熱完成", $results);
        
        return $results;
    }

    /**
     * 清除活動相關快取
     * 
     * @param array $tags 要清除的快取標籤
     * @return bool
     */
    public function clearActivityCache(array $tags = []): bool
    {
        try {
            $tagsToFlush = empty($tags) ? $this->cacheTags : $tags;
            
            foreach ($tagsToFlush as $tag) {
                Cache::tags([$tag])->flush();
            }
            
            Log::info("清除活動快取", ['tags' => $tagsToFlush]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error("清除活動快取失敗", [
                'tags' => $tags,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * 智慧快取失效
     * 
     * @param string $activityType 活動類型
     * @param array $context 上下文資訊
     * @return void
     */
    public function smartInvalidation(string $activityType, array $context = []): void
    {
        $invalidationRules = [
            // 使用者相關活動影響使用者快取
            'user_created' => ['user_activities', 'activity_stats'],
            'user_updated' => ['user_activities'],
            'user_deleted' => ['user_activities', 'activity_stats'],
            
            // 角色相關活動影響統計快取
            'role_created' => ['activity_stats'],
            'role_updated' => ['activity_stats'],
            'role_deleted' => ['activity_stats'],
            
            // 安全事件影響安全相關快取
            'login_failed' => ['security_stats', 'hot_queries'],
            'permission_escalation' => ['security_stats', 'activity_stats'],
            
            // 系統活動影響所有快取
            'system_config_change' => ['activities', 'activity_stats', 'hot_queries'],
        ];
        
        $tagsToInvalidate = $invalidationRules[$activityType] ?? ['activities'];
        
        // 如果有使用者ID，額外清除使用者相關快取
        if (isset($context['user_id'])) {
            $userCacheKey = "{$this->cachePrefix}:user_summary:{$context['user_id']}";
            Cache::forget($userCacheKey);
        }
        
        // 清除相關標籤的快取
        foreach ($tagsToInvalidate as $tag) {
            Cache::tags([$tag])->flush();
        }
        
        Log::debug("智慧快取失效", [
            'activity_type' => $activityType,
            'invalidated_tags' => $tagsToInvalidate,
            'context' => $context,
        ]);
    }

    /**
     * 取得快取統計資訊
     * 
     * @return array
     */
    public function getCacheStats(): array
    {
        $stats = [
            'cache_hits' => 0,
            'cache_misses' => 0,
            'hit_rate' => 0,
            'total_queries' => 0,
            'average_query_time' => 0,
            'cache_size' => 0,
            'hot_queries' => [],
        ];
        
        try {
            // 從 Redis 取得統計資料
            $performanceKey = "{$this->cachePrefix}:performance";
            $performanceData = Cache::get($performanceKey, []);
            
            if (!empty($performanceData)) {
                $stats['cache_hits'] = $performanceData['hits'] ?? 0;
                $stats['cache_misses'] = $performanceData['misses'] ?? 0;
                $stats['total_queries'] = $stats['cache_hits'] + $stats['cache_misses'];
                
                if ($stats['total_queries'] > 0) {
                    $stats['hit_rate'] = round(($stats['cache_hits'] / $stats['total_queries']) * 100, 2);
                }
                
                $stats['average_query_time'] = $performanceData['avg_time'] ?? 0;
            }
            
            // 取得熱門查詢
            $hotQueriesKey = "{$this->cachePrefix}:hot_queries";
            $stats['hot_queries'] = Cache::get($hotQueriesKey, []);
            
        } catch (\Exception $e) {
            Log::warning("取得快取統計失敗", ['error' => $e->getMessage()]);
        }
        
        return $stats;
    }

    /**
     * 最佳化快取配置
     * 
     * @return array 最佳化結果
     */
    public function optimizeCache(): array
    {
        $results = [
            'cleaned_expired' => 0,
            'optimized_queries' => 0,
            'memory_freed' => 0,
            'recommendations' => [],
        ];
        
        try {
            // 清理過期快取
            $results['cleaned_expired'] = $this->cleanExpiredCache();
            
            // 分析查詢模式並最佳化
            $queryAnalysis = $this->analyzeQueryPatterns();
            $results['optimized_queries'] = $queryAnalysis['optimized_count'];
            $results['recommendations'] = $queryAnalysis['recommendations'];
            
            // 記憶體最佳化
            $results['memory_freed'] = $this->optimizeMemoryUsage();
            
        } catch (\Exception $e) {
            Log::error("快取最佳化失敗", ['error' => $e->getMessage()]);
        }
        
        return $results;
    }

    /**
     * 生成查詢快取鍵
     * 
     * @param string $queryKey
     * @param array $filters
     * @return string
     */
    protected function generateQueryCacheKey(string $queryKey, array $filters): string
    {
        $filterHash = md5(serialize($filters));
        return "{$this->cachePrefix}:query:{$queryKey}:{$filterHash}";
    }

    /**
     * 生成統計快取鍵
     * 
     * @param string $statsType
     * @param array $parameters
     * @return string
     */
    protected function generateStatsCacheKey(string $statsType, array $parameters): string
    {
        $paramHash = md5(serialize($parameters));
        return "{$this->cachePrefix}:stats:{$statsType}:{$paramHash}";
    }

    /**
     * 生成熱門查詢快取鍵
     * 
     * @param string $queryType
     * @param array $parameters
     * @return string
     */
    protected function generateHotQueryCacheKey(string $queryType, array $parameters): string
    {
        $paramHash = md5(serialize($parameters));
        return "{$this->cachePrefix}:hot:{$queryType}:{$paramHash}";
    }

    /**
     * 決定快取時間
     * 
     * @param array $filters
     * @return int
     */
    protected function determineTtl(array $filters): int
    {
        // 即時查詢（最近1小時）使用較短快取時間
        if (isset($filters['date_from']) && Carbon::parse($filters['date_from'])->gt(now()->subHour())) {
            return 300; // 5分鐘
        }
        
        // 歷史查詢使用較長快取時間
        if (isset($filters['date_to']) && Carbon::parse($filters['date_to'])->lt(now()->subDay())) {
            return 7200; // 2小時
        }
        
        return $this->defaultTtl;
    }

    /**
     * 序列化分頁器
     * 
     * @param LengthAwarePaginator $paginator
     * @return array
     */
    protected function serializePaginator(LengthAwarePaginator $paginator): array
    {
        return [
            'data' => $paginator->items(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }

    /**
     * 檢查是否為熱門查詢
     * 
     * @param string $queryType
     * @param array $parameters
     * @return bool
     */
    protected function isHotQuery(string $queryType, array $parameters): bool
    {
        $hotQueryKey = "{$this->cachePrefix}:hot_queries";
        $hotQueries = Cache::get($hotQueryKey, []);
        
        $querySignature = md5($queryType . serialize($parameters));
        
        return isset($hotQueries[$querySignature]) && $hotQueries[$querySignature]['count'] >= 10;
    }

    /**
     * 記錄查詢效能
     * 
     * @param string $cacheKey
     * @param float $executionTime
     * @param string $type
     * @return void
     */
    protected function recordQueryPerformance(string $cacheKey, float $executionTime, string $type): void
    {
        $performanceKey = "{$this->cachePrefix}:performance";
        $performanceData = Cache::get($performanceKey, [
            'hits' => 0,
            'misses' => 0,
            'total_time' => 0,
            'query_count' => 0,
        ]);
        
        if (str_contains($type, 'cache_miss')) {
            $performanceData['misses']++;
        } else {
            $performanceData['hits']++;
        }
        
        $performanceData['total_time'] += $executionTime;
        $performanceData['query_count']++;
        $performanceData['avg_time'] = $performanceData['total_time'] / $performanceData['query_count'];
        
        Cache::put($performanceKey, $performanceData, 86400); // 24小時
    }

    /**
     * 清理過期快取
     * 
     * @return int
     */
    protected function cleanExpiredCache(): int
    {
        // 這個方法的實作取決於快取驅動
        // 對於 Redis，可以使用 SCAN 和 TTL 命令
        return 0;
    }

    /**
     * 分析查詢模式
     * 
     * @return array
     */
    protected function analyzeQueryPatterns(): array
    {
        return [
            'optimized_count' => 0,
            'recommendations' => [],
        ];
    }

    /**
     * 最佳化記憶體使用
     * 
     * @return int
     */
    protected function optimizeMemoryUsage(): int
    {
        // 實作記憶體最佳化邏輯
        return 0;
    }
}