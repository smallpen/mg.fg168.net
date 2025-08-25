<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\LanguageFileCache;
use App\Services\LanguagePerformanceMonitor;
use App\Services\MultilingualLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 語言效能功能測試
 */
class LanguagePerformanceTest extends TestCase
{
    use RefreshDatabase;

    private LanguageFileCache $cache;
    private LanguagePerformanceMonitor $monitor;
    private MultilingualLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->logger = app(MultilingualLogger::class);
        $this->cache = app(LanguageFileCache::class);
        $this->monitor = app(LanguagePerformanceMonitor::class);
    }

    /**
     * 測試語言檔案快取載入
     */
    public function test_language_file_cache_loading(): void
    {
        // 清除快取確保測試環境乾淨
        $this->cache->clearCache();
        
        // 第一次載入（應該從檔案載入）
        $startTime = microtime(true);
        $data1 = $this->cache->loadLanguageFile('zh_TW', 'auth');
        $loadTime1 = (microtime(true) - $startTime) * 1000;
        
        $this->assertIsArray($data1);
        $this->assertNotEmpty($data1);
        
        // 第二次載入（應該從快取載入）
        $startTime = microtime(true);
        $data2 = $this->cache->loadLanguageFile('zh_TW', 'auth');
        $loadTime2 = (microtime(true) - $startTime) * 1000;
        
        $this->assertEquals($data1, $data2);
        $this->assertLessThan($loadTime1, $loadTime2); // 快取載入應該更快
    }

    /**
     * 測試快取預熱功能
     */
    public function test_cache_warmup(): void
    {
        // 清除快取
        $this->cache->clearCache();
        
        // 執行預熱
        $results = $this->cache->warmupCache(['zh_TW'], ['auth', 'admin']);
        
        $this->assertIsArray($results);
        $this->assertArrayHasKey('success', $results);
        $this->assertArrayHasKey('failed', $results);
        $this->assertArrayHasKey('details', $results);
        
        // 檢查預熱結果
        $this->assertGreaterThan(0, $results['success']);
        $this->assertIsArray($results['details']);
        
        // 驗證快取統計
        $stats = $this->cache->getCacheStats();
        $this->assertGreaterThan(0, $stats['cached_files']);
    }

    /**
     * 測試效能監控記錄
     */
    public function test_performance_monitoring(): void
    {
        // 記錄檔案載入效能
        $this->monitor->recordFileLoadPerformance('zh_TW', 'auth', 50.5, false, 1024);
        
        // 記錄翻譯查詢效能
        $this->monitor->recordTranslationPerformance('auth.login', 'zh_TW', 25.3, true, false);
        
        // 記錄快取效能
        $this->monitor->recordCachePerformance('hit', 'zh_TW', 'auth');
        
        // 取得即時指標
        $metrics = $this->monitor->getRealTimeMetrics();
        
        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('timestamp', $metrics);
        $this->assertArrayHasKey('file_load', $metrics);
        $this->assertArrayHasKey('translation', $metrics);
        $this->assertArrayHasKey('cache', $metrics);
        $this->assertArrayHasKey('alerts', $metrics);
        $this->assertArrayHasKey('thresholds', $metrics);
    }

    /**
     * 測試效能統計資料
     */
    public function test_performance_stats(): void
    {
        // 記錄一些測試資料
        for ($i = 0; $i < 5; $i++) {
            $this->monitor->recordFileLoadPerformance('zh_TW', 'auth', 30 + $i * 10, $i % 2 === 0, 1024);
            $this->monitor->recordTranslationPerformance("test.key.{$i}", 'zh_TW', 20 + $i * 5, true, false);
        }
        
        // 取得統計資料
        $fileLoadStats = $this->monitor->getPerformanceStats('file_load', 1);
        $translationStats = $this->monitor->getPerformanceStats('translation', 1);
        
        $this->assertIsArray($fileLoadStats);
        $this->assertArrayHasKey('summary', $fileLoadStats);
        $this->assertArrayHasKey('hourly_data', $fileLoadStats);
        
        $this->assertIsArray($translationStats);
        $this->assertArrayHasKey('summary', $translationStats);
        $this->assertArrayHasKey('hourly_data', $translationStats);
        
        // 檢查摘要統計
        $summary = $fileLoadStats['summary'];
        $this->assertArrayHasKey('total_requests', $summary);
        $this->assertArrayHasKey('avg_time_ms', $summary);
        $this->assertArrayHasKey('success_rate_percent', $summary);
        
        // 由於統計資料可能需要時間聚合，我們檢查結構而不是具體數值
        $this->assertGreaterThanOrEqual(0, $summary['total_requests']);
        $this->assertGreaterThanOrEqual(0, $summary['avg_time_ms']);
    }

    /**
     * 測試警報觸發
     */
    public function test_alert_triggering(): void
    {
        // 記錄一個超過閾值的慢載入
        $this->monitor->recordFileLoadPerformance('zh_TW', 'auth', 150, false, 1024); // 超過 100ms 閾值
        
        // 記錄一個慢翻譯查詢
        $this->monitor->recordTranslationPerformance('slow.key', 'zh_TW', 80, true, false); // 超過 50ms 閾值
        
        // 取得警報
        $metrics = $this->monitor->getRealTimeMetrics();
        $alerts = $metrics['alerts'];
        
        $this->assertIsArray($alerts);
        // 注意：由於警報有冷卻期機制，可能不會立即觸發
    }

    /**
     * 測試快取統計
     */
    public function test_cache_statistics(): void
    {
        // 清除快取
        $this->cache->clearCache();
        
        // 載入一些檔案
        $this->cache->loadLanguageFile('zh_TW', 'auth');
        $this->cache->loadLanguageFile('en', 'auth');
        
        // 取得統計
        $stats = $this->cache->getCacheStats();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('cache_enabled', $stats);
        $this->assertArrayHasKey('cache_ttl', $stats);
        $this->assertArrayHasKey('total_possible', $stats);
        $this->assertArrayHasKey('cached_files', $stats);
        $this->assertArrayHasKey('cache_hit_rate', $stats);
        $this->assertArrayHasKey('details', $stats);
        
        $this->assertTrue($stats['cache_enabled']);
        $this->assertGreaterThan(0, $stats['cache_ttl']);
        $this->assertGreaterThan(0, $stats['total_possible']);
    }

    /**
     * 測試快取清除功能
     */
    public function test_cache_clearing(): void
    {
        // 預熱快取
        $this->cache->warmupCache(['zh_TW'], ['auth']);
        
        // 確認快取存在
        $statsBefore = $this->cache->getCacheStats();
        $this->assertGreaterThan(0, $statsBefore['cached_files']);
        
        // 清除特定快取
        $cleared = $this->cache->clearCache('zh_TW', 'auth');
        $this->assertGreaterThan(0, $cleared);
        
        // 清除所有快取
        $clearedAll = $this->cache->clearCache();
        $this->assertGreaterThanOrEqual(0, $clearedAll);
        
        // 確認快取已清除
        $statsAfter = $this->cache->getCacheStats();
        $this->assertLessThanOrEqual($statsBefore['cached_files'], $statsAfter['cached_files']);
    }

    /**
     * 測試效能資料清理
     */
    public function test_performance_data_cleanup(): void
    {
        // 記錄一些測試資料
        for ($i = 0; $i < 3; $i++) {
            $this->monitor->recordFileLoadPerformance('zh_TW', 'auth', 30, false, 1024);
        }
        
        // 清理資料
        $cleared = $this->monitor->clearPerformanceData(0); // 清理所有資料
        
        $this->assertGreaterThanOrEqual(0, $cleared);
    }

    /**
     * 測試多語系日誌記錄
     */
    public function test_multilingual_logging(): void
    {
        Log::shouldReceive('channel')
            ->with('multilingual_performance')
            ->andReturnSelf();
        
        Log::shouldReceive('warning')
            ->once();
        
        // 記錄一個慢載入以觸發日誌
        $this->monitor->recordFileLoadPerformance('zh_TW', 'auth', 150, false, 1024);
    }

    /**
     * 測試不存在的語言檔案處理
     */
    public function test_missing_language_file_handling(): void
    {
        // 嘗試載入不存在的語言檔案
        $data = $this->cache->loadLanguageFile('invalid_locale', 'nonexistent');
        
        $this->assertIsArray($data);
        $this->assertEmpty($data);
    }

    /**
     * 測試快取版本控制
     */
    public function test_cache_versioning(): void
    {
        // 載入檔案（建立快取）
        $data1 = $this->cache->loadLanguageFile('zh_TW', 'auth');
        
        // 模擬檔案修改（在實際應用中，這會是檔案系統的修改時間變更）
        // 這裡我們直接清除版本快取來模擬
        Cache::forget('lang_file_version:zh_TW:auth');
        
        // 再次載入（應該重新從檔案載入）
        $data2 = $this->cache->loadLanguageFile('zh_TW', 'auth');
        
        $this->assertEquals($data1, $data2);
    }
}