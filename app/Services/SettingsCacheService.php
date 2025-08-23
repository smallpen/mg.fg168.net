<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\SettingCache;
use App\Models\SettingPerformanceMetric;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * 設定快取服務
 * 
 * 提供多層快取策略、智慧快取管理和效能監控
 */
class SettingsCacheService
{
    /**
     * 快取前綴
     */
    private const CACHE_PREFIX = 'settings_';

    /**
     * 預設快取時間（秒）
     */
    private const DEFAULT_TTL = 3600;

    /**
     * 短期快取時間（秒）
     */
    private const SHORT_TTL = 300;

    /**
     * 長期快取時間（秒）
     */
    private const LONG_TTL = 86400;

    /**
     * 快取層級配置
     */
    protected array $cacheConfig = [
        'memory' => ['enabled' => true, 'ttl' => 60],
        'redis' => ['enabled' => true, 'ttl' => 3600],
        'database' => ['enabled' => true, 'ttl' => 86400],
    ];

    /**
     * 記憶體快取
     */
    protected array $memoryCache = [];

    /**
     * 快取統計
     */
    protected array $stats = [
        'hits' => 0,
        'misses' => 0,
        'sets' => 0,
        'deletes' => 0,
    ];

    /**
     * 取得設定（多層快取）
     * 
     * @param string $key 設定鍵值
     * @param mixed $default 預設值
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $startTime = microtime(true);
        $cacheKey = self::CACHE_PREFIX . "key_{$key}";

        try {
            // 第一層：記憶體快取
            if ($this->cacheConfig['memory']['enabled'] && isset($this->memoryCache[$cacheKey])) {
                $this->stats['hits']++;
                $this->recordCacheMetric('memory_hit', $startTime);
                return $this->memoryCache[$cacheKey];
            }

            // 第二層：Redis 快取
            if ($this->cacheConfig['redis']['enabled']) {
                $value = Cache::get($cacheKey);
                if ($value !== null) {
                    // 回填記憶體快取
                    if ($this->cacheConfig['memory']['enabled']) {
                        $this->memoryCache[$cacheKey] = $value;
                    }
                    
                    $this->stats['hits']++;
                    $this->recordCacheMetric('redis_hit', $startTime);
                    return $value;
                }
            }

            // 第三層：資料庫快取
            if ($this->cacheConfig['database']['enabled']) {
                $value = SettingCache::retrieve($cacheKey);
                if ($value !== null) {
                    // 回填上層快取
                    $this->backfillCache($cacheKey, $value);
                    
                    $this->stats['hits']++;
                    $this->recordCacheMetric('database_hit', $startTime);
                    return $value;
                }
            }

            // 快取未命中
            $this->stats['misses']++;
            $this->recordCacheMetric('cache_miss', $startTime);
            return $default;

        } catch (\Exception $e) {
            Log::error('快取取得失敗', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            $this->recordCacheMetric('cache_error', $startTime, ['error' => $e->getMessage()]);
            return $default;
        }
    }

    /**
     * 設定快取（多層快取）
     * 
     * @param string $key 設定鍵值
     * @param mixed $value 設定值
     * @param int|null $ttl 快取時間（秒）
     * @return bool
     */
    public function set(string $key, $value, ?int $ttl = null): bool
    {
        $startTime = microtime(true);
        $cacheKey = self::CACHE_PREFIX . "key_{$key}";
        $ttl = $ttl ?? self::DEFAULT_TTL;

        try {
            $success = true;

            // 第一層：記憶體快取
            if ($this->cacheConfig['memory']['enabled']) {
                $this->memoryCache[$cacheKey] = $value;
            }

            // 第二層：Redis 快取
            if ($this->cacheConfig['redis']['enabled']) {
                $success = Cache::put($cacheKey, $value, $ttl) && $success;
            }

            // 第三層：資料庫快取
            if ($this->cacheConfig['database']['enabled']) {
                try {
                    SettingCache::store($cacheKey, $value, 'setting', $ttl);
                } catch (\Exception $e) {
                    Log::warning('資料庫快取設定失敗', [
                        'key' => $key,
                        'error' => $e->getMessage(),
                    ]);
                    $success = false;
                }
            }

            if ($success) {
                $this->stats['sets']++;
                $this->recordCacheMetric('cache_set', $startTime);
            }

            return $success;

        } catch (\Exception $e) {
            Log::error('快取設定失敗', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            $this->recordCacheMetric('cache_error', $startTime, ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * 刪除快取（多層快取）
     * 
     * @param string $key 設定鍵值
     * @return bool
     */
    public function forget(string $key): bool
    {
        $startTime = microtime(true);
        $cacheKey = self::CACHE_PREFIX . "key_{$key}";

        try {
            $success = true;

            // 第一層：記憶體快取
            if ($this->cacheConfig['memory']['enabled']) {
                unset($this->memoryCache[$cacheKey]);
            }

            // 第二層：Redis 快取
            if ($this->cacheConfig['redis']['enabled']) {
                $success = Cache::forget($cacheKey) && $success;
            }

            // 第三層：資料庫快取
            if ($this->cacheConfig['database']['enabled']) {
                $success = SettingCache::forget($cacheKey) && $success;
            }

            if ($success) {
                $this->stats['deletes']++;
                $this->recordCacheMetric('cache_delete', $startTime);
            }

            return $success;

        } catch (\Exception $e) {
            Log::error('快取刪除失敗', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            $this->recordCacheMetric('cache_error', $startTime, ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * 批量取得快取
     * 
     * @param array $keys 設定鍵值陣列
     * @return array 快取資料陣列
     */
    public function getMany(array $keys): array
    {
        $startTime = microtime(true);
        $results = [];
        $missingKeys = [];

        try {
            // 第一層：記憶體快取
            if ($this->cacheConfig['memory']['enabled']) {
                foreach ($keys as $key) {
                    $cacheKey = self::CACHE_PREFIX . "key_{$key}";
                    if (isset($this->memoryCache[$cacheKey])) {
                        $results[$key] = $this->memoryCache[$cacheKey];
                    } else {
                        $missingKeys[] = $key;
                    }
                }
            } else {
                $missingKeys = $keys;
            }

            // 第二層：Redis 快取
            if ($this->cacheConfig['redis']['enabled'] && !empty($missingKeys)) {
                $cacheKeys = array_map(fn($key) => self::CACHE_PREFIX . "key_{$key}", $missingKeys);
                $cachedData = Cache::many($cacheKeys);
                
                $stillMissing = [];
                foreach ($missingKeys as $key) {
                    $cacheKey = self::CACHE_PREFIX . "key_{$key}";
                    if (isset($cachedData[$cacheKey]) && $cachedData[$cacheKey] !== null) {
                        $results[$key] = $cachedData[$cacheKey];
                        
                        // 回填記憶體快取
                        if ($this->cacheConfig['memory']['enabled']) {
                            $this->memoryCache[$cacheKey] = $cachedData[$cacheKey];
                        }
                    } else {
                        $stillMissing[] = $key;
                    }
                }
                $missingKeys = $stillMissing;
            }

            // 第三層：資料庫快取
            if ($this->cacheConfig['database']['enabled'] && !empty($missingKeys)) {
                $cacheKeys = array_map(fn($key) => self::CACHE_PREFIX . "key_{$key}", $missingKeys);
                $cachedData = SettingCache::retrieveBatch($cacheKeys);
                
                foreach ($missingKeys as $key) {
                    $cacheKey = self::CACHE_PREFIX . "key_{$key}";
                    if (isset($cachedData[$cacheKey])) {
                        $results[$key] = $cachedData[$cacheKey];
                        
                        // 回填上層快取
                        $this->backfillCache($cacheKey, $cachedData[$cacheKey]);
                    }
                }
            }

            $hitCount = count($results);
            $missCount = count($keys) - $hitCount;
            
            $this->stats['hits'] += $hitCount;
            $this->stats['misses'] += $missCount;

            $this->recordCacheMetric('batch_get', $startTime, [
                'total_keys' => count($keys),
                'hits' => $hitCount,
                'misses' => $missCount,
            ]);

            return $results;

        } catch (\Exception $e) {
            Log::error('批量快取取得失敗', [
                'keys' => $keys,
                'error' => $e->getMessage(),
            ]);

            $this->recordCacheMetric('cache_error', $startTime, ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * 批量設定快取
     * 
     * @param array $items 快取項目陣列 [key => value]
     * @param int|null $ttl 快取時間（秒）
     * @return bool
     */
    public function setMany(array $items, ?int $ttl = null): bool
    {
        $startTime = microtime(true);
        $ttl = $ttl ?? self::DEFAULT_TTL;

        try {
            $success = true;

            // 第一層：記憶體快取
            if ($this->cacheConfig['memory']['enabled']) {
                foreach ($items as $key => $value) {
                    $cacheKey = self::CACHE_PREFIX . "key_{$key}";
                    $this->memoryCache[$cacheKey] = $value;
                }
            }

            // 第二層：Redis 快取
            if ($this->cacheConfig['redis']['enabled']) {
                $cacheItems = [];
                foreach ($items as $key => $value) {
                    $cacheKey = self::CACHE_PREFIX . "key_{$key}";
                    $cacheItems[$cacheKey] = $value;
                }
                $success = Cache::putMany($cacheItems, $ttl) && $success;
            }

            // 第三層：資料庫快取
            if ($this->cacheConfig['database']['enabled']) {
                try {
                    $cacheItems = [];
                    foreach ($items as $key => $value) {
                        $cacheKey = self::CACHE_PREFIX . "key_{$key}";
                        $cacheItems[$cacheKey] = $value;
                    }
                    SettingCache::storeBatch($cacheItems, 'setting', $ttl);
                } catch (\Exception $e) {
                    Log::warning('批量資料庫快取設定失敗', [
                        'items_count' => count($items),
                        'error' => $e->getMessage(),
                    ]);
                    $success = false;
                }
            }

            if ($success) {
                $this->stats['sets'] += count($items);
                $this->recordCacheMetric('batch_set', $startTime, [
                    'items_count' => count($items),
                ]);
            }

            return $success;

        } catch (\Exception $e) {
            Log::error('批量快取設定失敗', [
                'items_count' => count($items),
                'error' => $e->getMessage(),
            ]);

            $this->recordCacheMetric('cache_error', $startTime, ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * 智慧快取設定
     * 
     * 根據設定的使用頻率和重要性自動選擇快取策略
     * 
     * @param string $key 設定鍵值
     * @param mixed $value 設定值
     * @param array $metadata 設定元資料
     * @return bool
     */
    public function smartCache(string $key, $value, array $metadata = []): bool
    {
        // 根據設定類型和重要性決定快取策略
        $ttl = $this->calculateSmartTTL($key, $metadata);
        $strategy = $this->determineStrategy($key, $metadata);

        switch ($strategy) {
            case 'hot':
                // 熱點資料：使用所有快取層級，較短 TTL
                return $this->set($key, $value, min($ttl, self::SHORT_TTL));
                
            case 'warm':
                // 溫資料：使用 Redis 和資料庫快取
                $this->cacheConfig['memory']['enabled'] = false;
                $result = $this->set($key, $value, $ttl);
                $this->cacheConfig['memory']['enabled'] = true;
                return $result;
                
            case 'cold':
                // 冷資料：僅使用資料庫快取，較長 TTL
                return SettingCache::store(
                    self::CACHE_PREFIX . "key_{$key}",
                    $value,
                    'setting',
                    max($ttl, self::LONG_TTL)
                ) !== null;
                
            default:
                return $this->set($key, $value, $ttl);
        }
    }

    /**
     * 計算智慧 TTL
     * 
     * @param string $key 設定鍵值
     * @param array $metadata 設定元資料
     * @return int TTL（秒）
     */
    protected function calculateSmartTTL(string $key, array $metadata): int
    {
        $baseTTL = self::DEFAULT_TTL;
        
        // 根據設定類型調整 TTL
        if (isset($metadata['type'])) {
            switch ($metadata['type']) {
                case 'system':
                    $baseTTL = self::LONG_TTL; // 系統設定變更較少
                    break;
                case 'user_preference':
                    $baseTTL = self::SHORT_TTL; // 使用者偏好變更較頻繁
                    break;
                case 'cache_config':
                    $baseTTL = self::SHORT_TTL; // 快取配置需要快速生效
                    break;
            }
        }
        
        // 根據變更頻率調整
        if (isset($metadata['change_frequency'])) {
            switch ($metadata['change_frequency']) {
                case 'high':
                    $baseTTL = intval($baseTTL * 0.5);
                    break;
                case 'low':
                    $baseTTL = intval($baseTTL * 2);
                    break;
            }
        }
        
        return max($baseTTL, 60); // 最小 1 分鐘
    }

    /**
     * 決定快取策略
     * 
     * @param string $key 設定鍵值
     * @param array $metadata 設定元資料
     * @return string 策略名稱
     */
    protected function determineStrategy(string $key, array $metadata): string
    {
        // 系統核心設定使用熱快取
        $hotPatterns = ['app.*', 'cache.*', 'database.*', 'session.*'];
        foreach ($hotPatterns as $pattern) {
            if (fnmatch($pattern, $key)) {
                return 'hot';
            }
        }
        
        // 使用者相關設定使用溫快取
        $warmPatterns = ['user.*', 'notification.*', 'theme.*'];
        foreach ($warmPatterns as $pattern) {
            if (fnmatch($pattern, $key)) {
                return 'warm';
            }
        }
        
        // 其他設定使用冷快取
        return 'cold';
    }

    /**
     * 回填上層快取
     * 
     * @param string $cacheKey 快取鍵值
     * @param mixed $value 快取值
     * @return void
     */
    protected function backfillCache(string $cacheKey, $value): void
    {
        try {
            // 回填記憶體快取
            if ($this->cacheConfig['memory']['enabled']) {
                $this->memoryCache[$cacheKey] = $value;
            }
            
            // 回填 Redis 快取
            if ($this->cacheConfig['redis']['enabled']) {
                Cache::put($cacheKey, $value, $this->cacheConfig['redis']['ttl']);
            }
        } catch (\Exception $e) {
            Log::warning('回填快取失敗', [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 記錄快取效能指標
     * 
     * @param string $operation 操作名稱
     * @param float $startTime 開始時間
     * @param array $metadata 額外資料
     * @return void
     */
    protected function recordCacheMetric(string $operation, float $startTime, array $metadata = []): void
    {
        try {
            $executionTime = (microtime(true) - $startTime) * 1000;
            SettingPerformanceMetric::record('cache', $operation, $executionTime, 'ms', $metadata);
        } catch (\Exception $e) {
            // 效能指標記錄失敗不應影響主要功能
            Log::debug('記錄快取效能指標失敗', [
                'operation' => $operation,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 清除所有快取
     * 
     * @param string|null $pattern 清除模式
     * @return bool
     */
    public function flush(?string $pattern = null): bool
    {
        $startTime = microtime(true);
        
        try {
            $success = true;

            // 清除記憶體快取
            if ($pattern) {
                foreach (array_keys($this->memoryCache) as $key) {
                    if (fnmatch($pattern, $key)) {
                        unset($this->memoryCache[$key]);
                    }
                }
            } else {
                $this->memoryCache = [];
            }

            // 清除 Redis 快取
            if ($this->cacheConfig['redis']['enabled']) {
                if ($pattern) {
                    // 使用 Redis 的 SCAN 命令清除匹配的鍵
                    if (Cache::getStore()->getRedis()) {
                        $redis = Cache::getStore()->getRedis();
                        $keys = $redis->keys($pattern);
                        if (!empty($keys)) {
                            $redis->del($keys);
                        }
                    }
                } else {
                    Cache::flush();
                }
            }

            // 清除資料庫快取
            if ($this->cacheConfig['database']['enabled']) {
                if ($pattern) {
                    SettingCache::where('cache_key', 'like', str_replace('*', '%', $pattern))->delete();
                } else {
                    SettingCache::truncate();
                }
            }

            $this->recordCacheMetric('cache_flush', $startTime, [
                'pattern' => $pattern,
            ]);

            return $success;

        } catch (\Exception $e) {
            Log::error('清除快取失敗', [
                'pattern' => $pattern,
                'error' => $e->getMessage(),
            ]);

            $this->recordCacheMetric('cache_error', $startTime, ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * 取得快取統計
     * 
     * @return array 快取統計
     */
    public function getStats(): array
    {
        $hitRate = ($this->stats['hits'] + $this->stats['misses']) > 0
            ? round(($this->stats['hits'] / ($this->stats['hits'] + $this->stats['misses'])) * 100, 2)
            : 0;

        return array_merge($this->stats, [
            'hit_rate' => $hitRate,
            'memory_cache_size' => count($this->memoryCache),
            'database_cache_stats' => SettingCache::getStats(),
        ]);
    }

    /**
     * 重設統計
     * 
     * @return void
     */
    public function resetStats(): void
    {
        $this->stats = [
            'hits' => 0,
            'misses' => 0,
            'sets' => 0,
            'deletes' => 0,
        ];
    }

    /**
     * 設定快取配置
     * 
     * @param array $config 快取配置
     * @return self
     */
    public function setConfig(array $config): self
    {
        $this->cacheConfig = array_merge($this->cacheConfig, $config);
        return $this;
    }

    /**
     * 取得快取配置
     * 
     * @return array 快取配置
     */
    public function getConfig(): array
    {
        return $this->cacheConfig;
    }
}