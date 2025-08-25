<?php

namespace Tests\Unit;

use App\Livewire\Admin\Activities\ActivityStats;
use App\Models\User;
use App\Repositories\Contracts\ActivityRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * 活動統計元件單元測試
 */
class ActivityStatsUnitTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected $mockRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試資料
        $this->seed();
        
        // 建立管理員使用者
        $this->adminUser = User::where('username', 'admin')->first();
        $this->assertNotNull($this->adminUser, '管理員使用者不存在，請確認已執行 Seeder');
        
        // 建立 Mock Repository
        $this->mockRepository = Mockery::mock(ActivityRepositoryInterface::class);
        $this->app->instance(ActivityRepositoryInterface::class, $this->mockRepository);
    }

    /** @test */
    public function 可以取得時間範圍選項()
    {
        $this->actingAs($this->adminUser);
        
        $component = new ActivityStats();
        $component->mount();
        
        $options = $component->getTimeRangeOptionsProperty();
        
        $this->assertIsArray($options);
        $this->assertArrayHasKey('1d', $options);
        $this->assertArrayHasKey('7d', $options);
        $this->assertArrayHasKey('30d', $options);
        $this->assertArrayHasKey('90d', $options);
        
        $this->assertEquals('今天', $options['1d']);
        $this->assertEquals('最近 7 天', $options['7d']);
    }

    /** @test */
    public function 可以取得圖表類型選項()
    {
        $this->actingAs($this->adminUser);
        
        $component = new ActivityStats();
        $component->mount();
        
        $options = $component->getChartTypeOptionsProperty();
        
        $this->assertIsArray($options);
        $this->assertArrayHasKey('timeline', $options);
        $this->assertArrayHasKey('distribution', $options);
        $this->assertArrayHasKey('heatmap', $options);
        
        $this->assertEquals('時間線圖', $options['timeline']);
        $this->assertEquals('分佈圖', $options['distribution']);
    }

    /** @test */
    public function 可以取得統計指標選項()
    {
        $this->actingAs($this->adminUser);
        
        $component = new ActivityStats();
        $component->mount();
        
        $options = $component->getMetricOptionsProperty();
        
        $this->assertIsArray($options);
        $this->assertArrayHasKey('total', $options);
        $this->assertArrayHasKey('users', $options);
        $this->assertArrayHasKey('security', $options);
        
        $this->assertEquals('總活動數', $options['total']);
        $this->assertEquals('活躍使用者', $options['users']);
    }

    /** @test */
    public function 可以更新時間範圍()
    {
        $this->actingAs($this->adminUser);
        
        $component = new ActivityStats();
        $component->mount();
        
        $this->assertEquals('7d', $component->timeRange);
        
        $component->updateTimeRange('30d');
        
        $this->assertEquals('30d', $component->timeRange);
    }

    /** @test */
    public function 可以更新圖表類型()
    {
        $this->actingAs($this->adminUser);
        
        $component = new ActivityStats();
        $component->mount();
        
        $this->assertEquals('timeline', $component->chartType);
        
        $component->updateChartType('distribution');
        
        $this->assertEquals('distribution', $component->chartType);
    }

    /** @test */
    public function 可以切換詳細統計顯示()
    {
        $this->actingAs($this->adminUser);
        
        $component = new ActivityStats();
        $component->mount();
        
        $this->assertFalse($component->showDetailedStats);
        
        $component->toggleDetailedStats();
        
        $this->assertTrue($component->showDetailedStats);
        
        $component->toggleDetailedStats();
        
        $this->assertFalse($component->showDetailedStats);
    }

    /** @test */
    public function 可以切換自動重新整理()
    {
        $this->actingAs($this->adminUser);
        
        $component = new ActivityStats();
        $component->mount();
        
        $this->assertFalse($component->autoRefresh);
        
        $component->toggleAutoRefresh();
        
        $this->assertTrue($component->autoRefresh);
        
        $component->toggleAutoRefresh();
        
        $this->assertFalse($component->autoRefresh);
    }

    /** @test */
    public function 可以取得活動類型顯示名稱()
    {
        $this->actingAs($this->adminUser);
        
        $component = new ActivityStats();
        $component->mount();
        
        // 使用反射來測試 protected 方法
        $reflection = new \ReflectionClass($component);
        $method = $reflection->getMethod('getTypeDisplayName');
        $method->setAccessible(true);
        
        $this->assertEquals('登入', $method->invoke($component, 'login'));
        $this->assertEquals('登出', $method->invoke($component, 'logout'));
        $this->assertEquals('建立使用者', $method->invoke($component, 'create_user'));
        $this->assertEquals('unknown_type', $method->invoke($component, 'unknown_type'));
    }

    /** @test */
    public function 可以取得模組顯示名稱()
    {
        $this->actingAs($this->adminUser);
        
        $component = new ActivityStats();
        $component->mount();
        
        // 使用反射來測試 protected 方法
        $reflection = new \ReflectionClass($component);
        $method = $reflection->getMethod('getModuleDisplayName');
        $method->setAccessible(true);
        
        $this->assertEquals('認證', $method->invoke($component, 'auth'));
        $this->assertEquals('使用者管理', $method->invoke($component, 'users'));
        $this->assertEquals('角色管理', $method->invoke($component, 'roles'));
        $this->assertEquals('unknown_module', $method->invoke($component, 'unknown_module'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}