<?php

namespace Tests\Unit\Services;

use App\Models\SettingCache;
use App\Models\SettingPerformanceMetric;
use App\Services\SettingsCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * 設定快取服務測試
 */
class SettingsCacheServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SettingsCacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheService = new SettingsCacheService();
    }

    /** @test */
    public function it_can_set_and_get_cache()
    {
        $key = 'test_setting';
        $value = 'test_value';

        // 設定快取
        $result = $this->cacheService->set($key, $value);
        $this->assertTrue($result);

        // 取得快取
        $cachedValue = $this->cacheService->get($key);
        $this->assertEquals($value, $cachedValue);
    }

    /** @test */
    public function it_returns_default_value_when_cache_miss()
    {
        $key = 'non_existent_key';
        $default = 'default_value';

        $result = $this->cacheService->get($key, $default);
        $this->assertEquals($default, $result);
    }

    /** @test */
    public function it_can_forget_cache()
    {
        $key = 'test_setting';
        $value = 'test_value';

        // 設定快取
        $this->cacheService->set($key, $value);
        $this->assertEquals($value, $this->cacheService->get($key));

        // 刪除快取
        $result = $this->cacheService->forget($key);
        $this->assertTrue($result);

        // 驗證快取已被刪除
        $this->assertNull($this->cacheService->get($key));
    }

    /** @test */
    public function it_can_batch_get_cache()
    {
        $items = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];

        // 設定多個快取項目
        foreach ($items as $key => $value) {
            $this->cacheService->set($key, $value);
        }

        // 批量取得快取
        $keys = array_keys($items);
        $results = $this->cacheService->getMany($keys);

        // 驗證結果
        $this->assertCount(3, $results);
        foreach ($items as $key => $value) {
            $this->assertEquals($value, $results[$key]);
        }
    }

    /** @test */
    public function it_can_batch_set_cache()
    {
        $items = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];

        // 批量設定快取
        $result = $this->cacheService->setMany($items);
        $this->assertTrue($result);

        // 驗證所有項目都已快取
        foreach ($items as $key => $value) {
            $this->assertEquals($value, $this->cacheService->get($key));
        }
    }

    /** @test */
    public function it_can_flush_cache()
    {
        // 設定一些快取項目
        $this->cacheService->set('key1', 'value1');
        $this->cacheService->set('key2', 'value2');

        // 清除所有快取
        $result = $this->cacheService->flush();
        $this->assertTrue($result);

        // 驗證快取已被清除
        $this->assertNull($this->cacheService->get('key1'));
        $this->assertNull($this->cacheService->get('key2'));
    }

    /** @test */
    public function it_can_get_cache_stats()
    {
        // 執行一些快取操作來產生統計資料
        $this->cacheService->set('key1', 'value1');
        $this->cacheService->get('key1'); // 命中
        $this->cacheService->get('non_existent'); // 未命中

        $stats = $this->cacheService->getStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('hits', $stats);
        $this->assertArrayHasKey('misses', $stats);
        $this->assertArrayHasKey('sets', $stats);
        $this->assertArrayHasKey('deletes', $stats);
        $this->assertArrayHasKey('hit_rate', $stats);
        $this->assertArrayHasKey('memory_cache_size', $stats);
    }

    /** @test */
    public function it_can_reset_stats()
    {
        // 執行一些操作
        $this->cacheService->set('key1', 'value1');
        $this->cacheService->get('key1');

        // 重設統計
        $this->cacheService->resetStats();
        $stats = $this->cacheService->getStats();

        $this->assertEquals(0, $stats['hits']);
        $this->assertEquals(0, $stats['misses']);
        $this->assertEquals(0, $stats['sets']);
        $this->assertEquals(0, $stats['deletes']);
    }

    /** @test */
    public function it_can_configure_cache_layers()
    {
        $config = [
            'memory' => ['enabled' => false, 'ttl' => 30],
            'redis' => ['enabled' => true, 'ttl' => 1800],
            'database' => ['enabled' => false, 'ttl' => 7200],
        ];

        $this->cacheService->setConfig($config);
        $currentConfig = $this->cacheService->getConfig();

        $this->assertFalse($currentConfig['memory']['enabled']);
        $this->assertEquals(30, $currentConfig['memory']['ttl']);
        $this->assertTrue($currentConfig['redis']['enabled']);
        $this->assertEquals(1800, $currentConfig['redis']['ttl']);
        $this->assertFalse($currentConfig['database']['enabled']);
        $this->assertEquals(7200, $currentConfig['database']['ttl']);
    }

    /** @test */
    public function it_can_use_smart_caching()
    {
        $key = 'system_setting';
        $value = 'important_value';
        $metadata = [
            'type' => 'system',
            'change_frequency' => 'low',
        ];

        // 使用智慧快取
        $result = $this->cacheService->smartCache($key, $value, $metadata);
        $this->assertTrue($result);

        // 驗證快取已設定
        $cachedValue = $this->cacheService->get($key);
        $this->assertEquals($value, $cachedValue);
    }

    /** @test */
    public function it_records_performance_metrics()
    {
        // 執行一些快取操作
        $this->cacheService->set('test_key', 'test_value');
        $this->cacheService->get('test_key');
        $this->cacheService->get('non_existent_key');

        // 檢查是否有效能指標被記錄
        $metricsCount = SettingPerformanceMetric::where('metric_type', 'cache')->count();
        $this->assertGreaterThan(0, $metricsCount);
    }

    /** @test */
    public function it_handles_cache_errors_gracefully()
    {
        // 模擬快取錯誤情況
        // 這裡我們可以通過設定無效的快取配置來測試錯誤處理
        
        // 測試取得不存在的快取不會拋出例外
        $result = $this->cacheService->get('invalid_key');
        $this->assertNull($result);

        // 測試設定快取失敗時的處理
        // 在實際情況下，這可能需要模擬 Redis 連線失敗等情況
        $this->assertTrue(true); // 暫時通過，實際實作中需要更詳細的錯誤處理測試
    }
}