<?php

namespace Tests\Unit\Services;

use App\Models\Setting;
use App\Models\SettingPerformanceMetric;
use App\Repositories\SettingsRepositoryInterface;
use App\Services\SettingsPerformanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

/**
 * 設定效能服務測試
 */
class SettingsPerformanceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SettingsPerformanceService $performanceService;
    protected $mockRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockRepository = Mockery::mock(SettingsRepositoryInterface::class);
        $this->performanceService = new SettingsPerformanceService($this->mockRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_batch_load_settings()
    {
        // 準備測試資料
        $keys = ['app.name', 'app.description', 'app.timezone'];
        $expectedSettings = collect([
            'app.name' => $this->createMockSetting('app.name', 'Test App'),
            'app.description' => $this->createMockSetting('app.description', 'Test Description'),
            'app.timezone' => $this->createMockSetting('app.timezone', 'Asia/Taipei'),
        ]);

        // 設定 Mock 期望
        $this->mockRepository
            ->shouldReceive('getSettings')
            ->with($keys)
            ->once()
            ->andReturn($expectedSettings);

        // 執行測試
        $result = $this->performanceService->batchLoadSettings($keys, false);

        // 驗證結果
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals(3, $result->count());
        $this->assertTrue($result->has('app.name'));
        $this->assertTrue($result->has('app.description'));
        $this->assertTrue($result->has('app.timezone'));
    }

    /** @test */
    public function it_can_batch_update_settings()
    {
        // 準備測試資料
        $settings = [
            'app.name' => 'Updated App Name',
            'app.description' => 'Updated Description',
        ];

        // 設定 Mock 期望
        $this->mockRepository
            ->shouldReceive('validateSetting')
            ->andReturn(true);

        $this->mockRepository
            ->shouldReceive('updateSetting')
            ->andReturn(true);

        $this->mockRepository
            ->shouldReceive('clearCache')
            ->once();

        // 執行測試
        $result = $this->performanceService->batchUpdateSettings($settings, [
            'use_transaction' => false,
            'validate' => true,
        ]);

        // 驗證結果
        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['updated']);
        $this->assertEquals(0, $result['failed']);
        $this->assertGreaterThan(0, $result['execution_time']);
    }

    /** @test */
    public function it_handles_batch_update_validation_errors()
    {
        // 準備測試資料
        $settings = [
            'app.name' => '', // 無效值
            'app.description' => 'Valid Description',
        ];

        // 設定 Mock 期望
        $this->mockRepository
            ->shouldReceive('validateSetting')
            ->with('app.name', '')
            ->andReturn(false);

        $this->mockRepository
            ->shouldReceive('validateSetting')
            ->with('app.description', 'Valid Description')
            ->andReturn(true);

        $this->mockRepository
            ->shouldReceive('updateSetting')
            ->with('app.description', 'Valid Description')
            ->andReturn(true);

        // 執行測試
        $result = $this->performanceService->batchUpdateSettings($settings, [
            'use_transaction' => false,
            'validate' => true,
        ]);

        // 驗證結果
        $this->assertFalse($result['success']);
        $this->assertEquals(1, $result['updated']);
        $this->assertEquals(1, $result['failed']);
        $this->assertNotEmpty($result['errors']);
    }

    /** @test */
    public function it_can_get_performance_stats()
    {
        // 建立測試效能指標
        SettingPerformanceMetric::create([
            'metric_type' => 'cache_hit',
            'operation' => 'get_setting',
            'value' => 50.5,
            'unit' => 'ms',
            'recorded_at' => now(),
        ]);

        SettingPerformanceMetric::create([
            'metric_type' => 'batch_update',
            'operation' => 'update_settings',
            'value' => 150.0,
            'unit' => 'ms',
            'recorded_at' => now(),
        ]);

        // 執行測試
        $stats = $this->performanceService->getPerformanceStats(24);

        // 驗證結果
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('cache_performance', $stats);
        $this->assertArrayHasKey('batch_operations', $stats);
        $this->assertArrayHasKey('lazy_loading', $stats);
        $this->assertArrayHasKey('errors', $stats);
    }

    /** @test */
    public function it_can_cleanup_performance_metrics()
    {
        // 建立舊的效能指標
        SettingPerformanceMetric::create([
            'metric_type' => 'cache_hit',
            'operation' => 'get_setting',
            'value' => 50.5,
            'unit' => 'ms',
            'recorded_at' => now()->subDays(35), // 35 天前
        ]);

        // 建立新的效能指標
        SettingPerformanceMetric::create([
            'metric_type' => 'cache_hit',
            'operation' => 'get_setting',
            'value' => 45.0,
            'unit' => 'ms',
            'recorded_at' => now()->subDays(5), // 5 天前
        ]);

        // 執行清理
        $cleanedCount = $this->performanceService->cleanupPerformanceMetrics(30);

        // 驗證結果
        $this->assertEquals(1, $cleanedCount);
        $this->assertEquals(1, SettingPerformanceMetric::count());
    }

    /** @test */
    public function it_can_warmup_cache()
    {
        // 準備測試資料
        $categories = ['basic', 'security'];
        $basicSettings = collect([
            $this->createMockSetting('app.name', 'Test App'),
            $this->createMockSetting('app.description', 'Test Description'),
        ]);
        $securitySettings = collect([
            $this->createMockSetting('security.password_min_length', 8),
        ]);

        // 設定 Mock 期望
        $this->mockRepository
            ->shouldReceive('getSettingsByCategory')
            ->with('basic')
            ->once()
            ->andReturn($basicSettings);

        $this->mockRepository
            ->shouldReceive('getSettingsByCategory')
            ->with('security')
            ->once()
            ->andReturn($securitySettings);

        // 執行測試
        $result = $this->performanceService->warmupCache($categories);

        // 驗證結果
        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['cached_settings']);
        $this->assertEquals(2, $result['categories_processed']);
        $this->assertGreaterThan(0, $result['execution_time']);
    }

    /** @test */
    public function it_can_set_batch_size()
    {
        // 測試設定批量大小
        $this->performanceService->setBatchSize(200);
        $this->assertEquals(200, $this->performanceService->getDefaultBatchSize());

        // 測試邊界值
        $this->performanceService->setBatchSize(0);
        $this->assertEquals(1, $this->performanceService->getDefaultBatchSize());

        $this->performanceService->setBatchSize(2000);
        $this->assertEquals(1000, $this->performanceService->getDefaultBatchSize()); // 最大值限制
    }

    /** @test */
    public function it_can_set_lazy_load_threshold()
    {
        // 測試設定延遲載入閾值
        $this->performanceService->setLazyLoadThreshold(100);
        
        // 由於沒有 getter 方法，我們通過反射來驗證
        $reflection = new \ReflectionClass($this->performanceService);
        $property = $reflection->getProperty('lazyLoadThreshold');
        $property->setAccessible(true);
        
        $this->assertEquals(100, $property->getValue($this->performanceService));

        // 測試邊界值
        $this->performanceService->setLazyLoadThreshold(0);
        $this->assertEquals(1, $property->getValue($this->performanceService));
    }

    /**
     * 建立模擬設定物件
     */
    private function createMockSetting(string $key, $value): Setting
    {
        $setting = new Setting();
        $setting->key = $key;
        $setting->value = $value;
        $setting->category = explode('.', $key)[0];
        $setting->type = 'text';
        
        return $setting;
    }
}