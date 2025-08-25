<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * 語言功能效能監控服務
 * 
 * 監控多語系功能的效能指標並提供警報機制
 */
class LanguagePerformanceMonitor
{
    /**
     * 效能指標快取前綴
     */
    private const METRICS_PREFIX = 'lang_perf_metrics';
    
    /**
     * 警報快取前綴
     */
    private const ALERT_PREFIX = 'lang_perf_alerts';
    
    /**
     * 多語系日誌記錄器
     */
    private MultilingualLogger $logger;
    
    /**
     * 效能閾值設定
     */
    private array $thresholds;

    /**
     * 建構函數
     */
    public function __construct(MultilingualLogger $logger)
    {
        $this->logger = $logger;
        $this->thresholds = [
            'file_load_time' => config('multilingual.performance.log_threshold', 100), // ms
            'translation_time' => config('multilingual.performance.slow_query_threshold', 50), // ms
            'cache_miss_rate' => 30, // %
            'error_rate' => 5, // %
            'memory_usage' => 10 * 1024 * 1024, // 10MB
        ];
    }

    /**
     * 記錄語言檔案載入效能
     *
     * @param string $locale 語言代碼
     * @param string $group 語言檔案群組
     * @param float $loadTime 載入時間（毫秒）
     * @param bool $fromCache 是否來自快取
     * @param int $memoryUsage 記憶體使用量（位元組）
     * @return void
     */
    public function recordFileLoadPerformance(
        string $locale, 
        string $group, 
        float $loadTime, 
        bool $fromCache, 
        int $memoryUsage = 0
    ): void {
        $timestamp = now();
        $metricKey = $this->getMetricKey('file_load', $locale, $group);
        
        // 記錄效能指標
        $metric = [
            'timestamp' => $timestamp->toISOString(),
            'locale' => $locale,
            'group' => $group,
            'load_time_ms' => round($loadTime, 2),
            'from_cache' => $fromCache,
            'memory_usage_bytes' => $memoryUsage,
            'hour' => $timestamp->format('Y-m-d-H'),
            'date' => $timestamp->format('Y-m-d'),
        ];
        
        // 儲存到快取（保留 24 小時）
        $this->appendMetric($metricKey, $metric, 24 * 60);
        
        // 更新統計資料
        $this->updateHourlyStats('file_load', $loadTime, $fromCache, $timestamp);
        
        // 檢查是否需要警報
        $this->checkFileLoadAlerts($locale, $group, $loadTime, $fromCache);
    }

    /**
     * 記錄翻譯查詢效能
     *
     * @param string $key 翻譯鍵
     * @param string $locale 語言代碼
     * @param float $queryTime 查詢時間（毫秒）
     * @param bool $found 是否找到翻譯
     * @param bool $usedFallback 是否使用回退
     * @return void
     */
    public function recordTranslationPerformance(
        string $key, 
        string $locale, 
        float $queryTime, 
        bool $found, 
        bool $usedFallback = false
    ): void {
        $timestamp = now();
        $metricKey = $this->getMetricKey('translation', $locale);
        
        $metric = [
            'timestamp' => $timestamp->toISOString(),
            'translation_key' => $key,
            'locale' => $locale,
            'query_time_ms' => round($queryTime, 2),
            'found' => $found,
            'used_fallback' => $usedFallback,
            'hour' => $timestamp->format('Y-m-d-H'),
            'date' => $timestamp->format('Y-m-d'),
        ];
        
        // 儲存到快取（保留 24 小時）
        $this->appendMetric($metricKey, $metric, 24 * 60);
        
        // 更新統計資料
        $this->updateHourlyStats('translation', $queryTime, $found, $timestamp);
        
        // 檢查是否需要警報
        $this->checkTranslationAlerts($key, $locale, $queryTime, $found);
    }

    /**
     * 記錄快取效能
     *
     * @param string $operation 操作類型（hit, miss, set, delete）
     * @param string $locale 語言代碼
     * @param string|null $group 語言檔案群組
     * @return void
     */
    public function recordCachePerformance(string $operation, string $locale, ?string $group = null): void
    {
        $timestamp = now();
        $hourKey = $this->getHourlyStatsKey('cache', $timestamp);
        
        // 更新快取統計
        $stats = Cache::get($hourKey, [
            'hits' => 0,
            'misses' => 0,
            'sets' => 0,
            'deletes' => 0,
            'total' => 0,
        ]);
        
        $stats[$operation] = ($stats[$operation] ?? 0) + 1;
        $stats['total']++;
        
        Cache::put($hourKey, $stats, 25 * 60); // 保留 25 小時
        
        // 檢查快取命中率警報
        $this->checkCacheAlerts($stats, $timestamp);
    }

    /**
     * 取得效能統計資料
     *
     * @param string $type 統計類型（file_load, translation, cache）
     * @param int $hours 統計小時數（預設 24 小時）
     * @return array 統計資料
     */
    public function getPerformanceStats(string $type, int $hours = 24): array
    {
        $stats = [
            'type' => $type,
            'period_hours' => $hours,
            'hourly_data' => [],
            'summary' => []
        ];
        
        $endTime = now();
        $startTime = $endTime->copy()->subHours($hours);
        
        for ($i = 0; $i < $hours; $i++) {
            $hour = $startTime->copy()->addHours($i);
            $hourKey = $this->getHourlyStatsKey($type, $hour);
            $hourData = Cache::get($hourKey, []);
            
            $stats['hourly_data'][$hour->format('Y-m-d H:00')] = $hourData;
        }
        
        // 計算摘要統計
        $stats['summary'] = $this->calculateSummaryStats($stats['hourly_data'], $type);
        
        return $stats;
    }

    /**
     * 取得即時效能指標
     *
     * @return array 即時指標
     */
    public function getRealTimeMetrics(): array
    {
        $currentHour = now();
        
        return [
            'timestamp' => $currentHour->toISOString(),
            'file_load' => $this->getCurrentHourStats('file_load', $currentHour),
            'translation' => $this->getCurrentHourStats('translation', $currentHour),
            'cache' => $this->getCurrentHourStats('cache', $currentHour),
            'alerts' => $this->getActiveAlerts(),
            'thresholds' => $this->thresholds,
        ];
    }

    /**
     * 檢查並觸發語言檔案載入警報
     *
     * @param string $locale 語言代碼
     * @param string $group 語言檔案群組
     * @param float $loadTime 載入時間
     * @param bool $fromCache 是否來自快取
     * @return void
     */
    private function checkFileLoadAlerts(string $locale, string $group, float $loadTime, bool $fromCache): void
    {
        // 檢查載入時間警報
        if ($loadTime > $this->thresholds['file_load_time']) {
            $this->triggerAlert('slow_file_load', [
                'locale' => $locale,
                'group' => $group,
                'load_time_ms' => $loadTime,
                'threshold_ms' => $this->thresholds['file_load_time'],
                'from_cache' => $fromCache,
            ]);
        }
    }

    /**
     * 檢查並觸發翻譯查詢警報
     *
     * @param string $key 翻譯鍵
     * @param string $locale 語言代碼
     * @param float $queryTime 查詢時間
     * @param bool $found 是否找到翻譯
     * @return void
     */
    private function checkTranslationAlerts(string $key, string $locale, float $queryTime, bool $found): void
    {
        // 檢查查詢時間警報
        if ($queryTime > $this->thresholds['translation_time']) {
            $this->triggerAlert('slow_translation', [
                'translation_key' => $key,
                'locale' => $locale,
                'query_time_ms' => $queryTime,
                'threshold_ms' => $this->thresholds['translation_time'],
                'found' => $found,
            ]);
        }
    }

    /**
     * 檢查並觸發快取警報
     *
     * @param array $stats 快取統計
     * @param Carbon $timestamp 時間戳
     * @return void
     */
    private function checkCacheAlerts(array $stats, Carbon $timestamp): void
    {
        if ($stats['total'] < 10) return; // 樣本數太少，不觸發警報
        
        $hitRate = $stats['total'] > 0 ? ($stats['hits'] / $stats['total']) * 100 : 0;
        $missRate = 100 - $hitRate;
        
        if ($missRate > $this->thresholds['cache_miss_rate']) {
            $this->triggerAlert('high_cache_miss_rate', [
                'miss_rate_percent' => round($missRate, 2),
                'threshold_percent' => $this->thresholds['cache_miss_rate'],
                'total_requests' => $stats['total'],
                'hits' => $stats['hits'],
                'misses' => $stats['misses'],
                'hour' => $timestamp->format('Y-m-d H:00'),
            ]);
        }
    }

    /**
     * 觸發警報
     *
     * @param string $alertType 警報類型
     * @param array $data 警報資料
     * @return void
     */
    private function triggerAlert(string $alertType, array $data): void
    {
        $alertKey = $this->getAlertKey($alertType);
        $timestamp = now();
        
        // 檢查是否在冷卻期內（避免重複警報）
        $lastAlert = Cache::get($alertKey);
        if ($lastAlert && $timestamp->diffInMinutes($lastAlert['timestamp']) < 15) {
            return; // 15 分鐘內不重複警報
        }
        
        $alert = [
            'type' => $alertType,
            'timestamp' => $timestamp->toISOString(),
            'data' => $data,
            'severity' => $this->getAlertSeverity($alertType),
        ];
        
        // 儲存警報（保留 24 小時）
        Cache::put($alertKey, $alert, 24 * 60);
        
        // 記錄到日誌
        Log::channel('multilingual_performance')->warning("Language performance alert: {$alertType}", $alert);
        
        // 如果是高嚴重性警報，也記錄到錯誤日誌
        if ($alert['severity'] === 'high') {
            Log::channel('multilingual_errors')->error("High severity language performance alert: {$alertType}", $alert);
        }
    }

    /**
     * 取得警報嚴重性
     *
     * @param string $alertType 警報類型
     * @return string 嚴重性等級
     */
    private function getAlertSeverity(string $alertType): string
    {
        return match ($alertType) {
            'slow_file_load' => 'medium',
            'slow_translation' => 'low',
            'high_cache_miss_rate' => 'high',
            'high_error_rate' => 'high',
            'high_memory_usage' => 'medium',
            default => 'low',
        };
    }

    /**
     * 取得活躍警報
     *
     * @return array 活躍警報列表
     */
    private function getActiveAlerts(): array
    {
        $alertTypes = ['slow_file_load', 'slow_translation', 'high_cache_miss_rate', 'high_error_rate', 'high_memory_usage'];
        $activeAlerts = [];
        
        foreach ($alertTypes as $type) {
            $alertKey = $this->getAlertKey($type);
            $alert = Cache::get($alertKey);
            
            if ($alert) {
                $activeAlerts[] = $alert;
            }
        }
        
        // 按時間戳排序（最新的在前）
        usort($activeAlerts, function ($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        return $activeAlerts;
    }

    /**
     * 更新每小時統計資料
     *
     * @param string $type 統計類型
     * @param float $value 數值
     * @param bool $success 是否成功
     * @param Carbon $timestamp 時間戳
     * @return void
     */
    private function updateHourlyStats(string $type, float $value, bool $success, Carbon $timestamp): void
    {
        $hourKey = $this->getHourlyStatsKey($type, $timestamp);
        
        $stats = Cache::get($hourKey, [
            'count' => 0,
            'total_time' => 0,
            'min_time' => PHP_FLOAT_MAX,
            'max_time' => 0,
            'success_count' => 0,
            'error_count' => 0,
        ]);
        
        $stats['count']++;
        $stats['total_time'] += $value;
        $stats['min_time'] = min($stats['min_time'], $value);
        $stats['max_time'] = max($stats['max_time'], $value);
        
        if ($success) {
            $stats['success_count']++;
        } else {
            $stats['error_count']++;
        }
        
        // 計算平均值
        $stats['avg_time'] = $stats['count'] > 0 ? $stats['total_time'] / $stats['count'] : 0;
        $stats['success_rate'] = $stats['count'] > 0 ? ($stats['success_count'] / $stats['count']) * 100 : 0;
        
        Cache::put($hourKey, $stats, 25 * 60); // 保留 25 小時
    }

    /**
     * 計算摘要統計
     *
     * @param array $hourlyData 每小時資料
     * @param string $type 統計類型
     * @return array 摘要統計
     */
    private function calculateSummaryStats(array $hourlyData, string $type): array
    {
        $totalCount = 0;
        $totalTime = 0;
        $totalSuccess = 0;
        $totalErrors = 0;
        $minTime = PHP_FLOAT_MAX;
        $maxTime = 0;
        
        foreach ($hourlyData as $hour => $data) {
            if (empty($data)) continue;
            
            $totalCount += $data['count'] ?? 0;
            $totalTime += $data['total_time'] ?? 0;
            $totalSuccess += $data['success_count'] ?? 0;
            $totalErrors += $data['error_count'] ?? 0;
            
            if (isset($data['min_time'])) {
                $minTime = min($minTime, $data['min_time']);
            }
            if (isset($data['max_time'])) {
                $maxTime = max($maxTime, $data['max_time']);
            }
        }
        
        return [
            'total_requests' => $totalCount,
            'avg_time_ms' => $totalCount > 0 ? round($totalTime / $totalCount, 2) : 0,
            'min_time_ms' => $minTime === PHP_FLOAT_MAX ? 0 : round($minTime, 2),
            'max_time_ms' => round($maxTime, 2),
            'success_rate_percent' => $totalCount > 0 ? round(($totalSuccess / $totalCount) * 100, 2) : 0,
            'error_rate_percent' => $totalCount > 0 ? round(($totalErrors / $totalCount) * 100, 2) : 0,
            'total_success' => $totalSuccess,
            'total_errors' => $totalErrors,
        ];
    }

    /**
     * 取得當前小時統計
     *
     * @param string $type 統計類型
     * @param Carbon $timestamp 時間戳
     * @return array 統計資料
     */
    private function getCurrentHourStats(string $type, Carbon $timestamp): array
    {
        $hourKey = $this->getHourlyStatsKey($type, $timestamp);
        return Cache::get($hourKey, []);
    }

    /**
     * 取得指標快取鍵
     *
     * @param string $type 指標類型
     * @param string $locale 語言代碼
     * @param string|null $group 語言檔案群組
     * @return string 快取鍵
     */
    private function getMetricKey(string $type, string $locale, ?string $group = null): string
    {
        $key = self::METRICS_PREFIX . ":{$type}:{$locale}";
        if ($group) {
            $key .= ":{$group}";
        }
        return $key;
    }

    /**
     * 取得每小時統計快取鍵
     *
     * @param string $type 統計類型
     * @param Carbon $timestamp 時間戳
     * @return string 快取鍵
     */
    private function getHourlyStatsKey(string $type, Carbon $timestamp): string
    {
        return self::METRICS_PREFIX . ":hourly:{$type}:" . $timestamp->format('Y-m-d-H');
    }

    /**
     * 取得警報快取鍵
     *
     * @param string $alertType 警報類型
     * @return string 快取鍵
     */
    private function getAlertKey(string $alertType): string
    {
        return self::ALERT_PREFIX . ":{$alertType}";
    }

    /**
     * 附加指標到快取
     *
     * @param string $key 快取鍵
     * @param array $metric 指標資料
     * @param int $ttlMinutes TTL（分鐘）
     * @return void
     */
    private function appendMetric(string $key, array $metric, int $ttlMinutes): void
    {
        $metrics = Cache::get($key, []);
        $metrics[] = $metric;
        
        // 限制最多保留 1000 筆記錄
        if (count($metrics) > 1000) {
            $metrics = array_slice($metrics, -1000);
        }
        
        Cache::put($key, $metrics, $ttlMinutes);
    }

    /**
     * 清除效能資料
     *
     * @param int $olderThanHours 清除多少小時前的資料
     * @return int 清除的項目數量
     */
    public function clearPerformanceData(int $olderThanHours = 24): int
    {
        $cleared = 0;
        $cutoffTime = now()->subHours($olderThanHours);
        
        // 清除每小時統計資料
        $types = ['file_load', 'translation', 'cache'];
        foreach ($types as $type) {
            for ($i = $olderThanHours; $i < $olderThanHours + 24; $i++) {
                $hour = $cutoffTime->copy()->subHours($i);
                $hourKey = $this->getHourlyStatsKey($type, $hour);
                if (Cache::forget($hourKey)) {
                    $cleared++;
                }
            }
        }
        
        Log::channel('multilingual_performance')->info('Performance data cleared', [
            'cleared_count' => $cleared,
            'older_than_hours' => $olderThanHours
        ]);
        
        return $cleared;
    }
}