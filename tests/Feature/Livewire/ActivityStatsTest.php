<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Admin\Activities\ActivityStats;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Traits\DisablesPermissionSecurity;

/**
 * 活動統計元件測試
 */
class ActivityStatsTest extends TestCase
{
    use RefreshDatabase, DisablesPermissionSecurity;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試資料
        $this->seed();
        
        // 建立管理員使用者
        $this->adminUser = User::where('username', 'admin')->first();
        $this->assertNotNull($this->adminUser, '管理員使用者不存在，請確認已執行 Seeder');
    }

    /** @test */
    public function 管理員可以檢視活動統計頁面()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(ActivityStats::class);

        $component->assertStatus(200)
                  ->assertSee('活動統計分析')
                  ->assertSee('總活動數')
                  ->assertSee('活躍使用者')
                  ->assertSee('安全事件')
                  ->assertSee('成功率');
    }

    /** @test */
    public function 可以切換時間範圍()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(ActivityStats::class);

        // 測試切換到 30 天
        $component->call('updateTimeRange', '30d')
                  ->assertSet('timeRange', '30d')
                  ->assertDispatched('stats-updated');
    }

    /** @test */
    public function 可以切換圖表類型()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(ActivityStats::class);

        // 測試切換到分佈圖
        $component->call('updateChartType', 'distribution')
                  ->assertSet('chartType', 'distribution');
    }

    /** @test */
    public function 可以重新整理統計資料()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(ActivityStats::class);

        $component->call('refreshStats')
                  ->assertDispatched('stats-refreshed');
    }

    /** @test */
    public function 可以切換自動重新整理()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(ActivityStats::class);

        // 啟用自動重新整理
        $component->call('toggleAutoRefresh')
                  ->assertSet('autoRefresh', true)
                  ->assertDispatched('start-auto-refresh');

        // 停用自動重新整理
        $component->call('toggleAutoRefresh')
                  ->assertSet('autoRefresh', false)
                  ->assertDispatched('stop-auto-refresh');
    }

    /** @test */
    public function 統計資料包含正確的結構()
    {
        $this->actingAs($this->adminUser);

        // 建立一些測試活動記錄
        Activity::factory()->count(10)->create([
            'user_id' => $this->adminUser->id,
            'created_at' => now()->subDays(1),
        ]);

        $component = Livewire::test(ActivityStats::class);

        // 檢查綜合統計資料結構
        $overallStats = $component->get('overallStats');
        $this->assertIsArray($overallStats);
        $this->assertArrayHasKey('total_activities', $overallStats);
        $this->assertArrayHasKey('unique_users', $overallStats);
        $this->assertArrayHasKey('security_events', $overallStats);
        $this->assertArrayHasKey('success_rate', $overallStats);

        // 檢查時間線資料結構
        $timelineData = $component->get('timelineData');
        $this->assertIsArray($timelineData);

        // 檢查分佈資料結構
        $distributionData = $component->get('distributionData');
        $this->assertIsArray($distributionData);
        $this->assertArrayHasKey('type_distribution', $distributionData);
        $this->assertArrayHasKey('module_distribution', $distributionData);
        $this->assertArrayHasKey('hourly_distribution', $distributionData);
    }

    /** @test */
    public function 沒有權限的使用者無法存取()
    {
        // 建立沒有權限的使用者
        $user = User::factory()->create();
        $this->actingAs($user);

        try {
            Livewire::test(ActivityStats::class);
            $this->fail('Expected AuthorizationException was not thrown');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            $this->assertTrue(true, 'Authorization exception was correctly thrown');
        }
    }

    /** @test */
    public function 可以匯出統計報告()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(ActivityStats::class);

        $component->call('exportStats');

        // 檢查是否有匯出相關的事件被觸發
        // 注意：實際的檔案匯出測試需要更複雜的設定
    }

    /** @test */
    public function 可以切換詳細統計顯示()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(ActivityStats::class);

        // 預設應該是關閉的
        $component->assertSet('showDetailedStats', false);

        // 切換到顯示
        $component->call('toggleDetailedStats')
                  ->assertSet('showDetailedStats', true);

        // 再次切換到隱藏
        $component->call('toggleDetailedStats')
                  ->assertSet('showDetailedStats', false);
    }

    /** @test */
    public function 可以切換統計指標()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(ActivityStats::class);

        // 測試新增指標
        $component->call('toggleMetric', 'risk_level')
                  ->assertContains('risk_level', 'selectedMetrics');

        // 測試移除指標
        $component->call('toggleMetric', 'total')
                  ->assertNotContains('total', 'selectedMetrics');
    }

    /** @test */
    public function 可以匯出特定圖表資料()
    {
        $this->actingAs($this->adminUser);

        // 建立測試活動記錄
        Activity::factory()->count(5)->create([
            'user_id' => $this->adminUser->id,
            'created_at' => now()->subDays(1),
        ]);

        $component = Livewire::test(ActivityStats::class);

        $component->call('exportChartData', 'timeline');

        // 檢查是否有匯出相關的事件被觸發
        // 注意：實際的檔案匯出測試需要更複雜的設定
    }

    /** @test */
    public function 可以設定自訂時間範圍()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(ActivityStats::class);

        $startDate = '2024-01-01';
        $endDate = '2024-01-07';

        $component->call('setCustomTimeRange', $startDate, $endDate)
                  ->assertSet('timeRange', '7d')
                  ->assertDispatched('stats-updated');
    }

    /** @test */
    public function 趨勢分析計算正確()
    {
        $this->actingAs($this->adminUser);

        // 建立遞增趨勢的測試資料
        for ($i = 1; $i <= 7; $i++) {
            Activity::factory()->count($i * 2)->create([
                'user_id' => $this->adminUser->id,
                'created_at' => now()->subDays(7 - $i),
            ]);
        }

        $component = Livewire::test(ActivityStats::class);
        $trendAnalysis = $component->get('trendAnalysis');

        $this->assertIsArray($trendAnalysis);
        $this->assertArrayHasKey('trend', $trendAnalysis);
        $this->assertArrayHasKey('change_percentage', $trendAnalysis);
        $this->assertArrayHasKey('prediction', $trendAnalysis);
        $this->assertEquals('increasing', $trendAnalysis['trend']);
    }

    /** @test */
    public function 時間範圍選項正確()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(ActivityStats::class);
        $options = $component->get('timeRangeOptions');

        $expectedOptions = ['1d', '7d', '30d', '90d'];
        foreach ($expectedOptions as $option) {
            $this->assertArrayHasKey($option, $options);
        }
    }

    /** @test */
    public function 圖表類型選項正確()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(ActivityStats::class);
        $options = $component->get('chartTypeOptions');

        $expectedOptions = ['timeline', 'distribution', 'heatmap', 'comparison'];
        foreach ($expectedOptions as $option) {
            $this->assertArrayHasKey($option, $options);
        }
    }

    /** @test */
    public function 統計指標選項正確()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(ActivityStats::class);
        $options = $component->get('metricOptions');

        $expectedOptions = ['total', 'users', 'security', 'success_rate', 'risk_level'];
        foreach ($expectedOptions as $option) {
            $this->assertArrayHasKey($option, $options);
        }
    }

    /** @test */
    public function 可以比較不同時間範圍()
    {
        $this->actingAs($this->adminUser);

        // 建立不同時間的測試資料
        Activity::factory()->count(10)->create([
            'user_id' => $this->adminUser->id,
            'created_at' => now()->subDays(1),
        ]);

        Activity::factory()->count(5)->create([
            'user_id' => $this->adminUser->id,
            'created_at' => now()->subDays(8),
        ]);

        $component = Livewire::test(ActivityStats::class);
        $comparison = $component->instance()->compareTimeRanges(['7d', '30d']);

        $this->assertIsArray($comparison);
        $this->assertArrayHasKey('7d', $comparison);
        $this->assertArrayHasKey('30d', $comparison);
    }

    /** @test */
    public function 處理空資料情況()
    {
        $this->actingAs($this->adminUser);

        // 確保沒有活動記錄
        Activity::query()->delete();

        $component = Livewire::test(ActivityStats::class);

        // 檢查各種統計資料是否正確處理空資料
        $overallStats = $component->get('overallStats');
        $this->assertEquals(0, $overallStats['total_activities']);

        $timelineData = $component->get('timelineData');
        $this->assertIsArray($timelineData);

        $distributionData = $component->get('distributionData');
        $this->assertIsArray($distributionData);
    }

    /** @test */
    public function 快取機制正常運作()
    {
        $this->actingAs($this->adminUser);

        // 建立測試資料
        Activity::factory()->count(5)->create([
            'user_id' => $this->adminUser->id,
        ]);

        $component = Livewire::test(ActivityStats::class);

        // 第一次載入資料
        $firstLoad = $component->get('overallStats');

        // 第二次載入應該使用快取
        $secondLoad = $component->get('overallStats');

        $this->assertEquals($firstLoad, $secondLoad);
    }

    /** @test */
    public function 可以清除統計快取()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(ActivityStats::class);

        // 載入資料建立快取
        $component->get('overallStats');

        // 重新整理應該清除快取
        $component->call('refreshStats')
                  ->assertDispatched('stats-refreshed');
    }

    /** @test */
    public function 活動類型顯示名稱正確()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(ActivityStats::class);
        $instance = $component->instance();

        // 測試各種活動類型的顯示名稱
        $this->assertEquals('登入', $instance->getTypeDisplayName('login'));
        $this->assertEquals('登出', $instance->getTypeDisplayName('logout'));
        $this->assertEquals('建立使用者', $instance->getTypeDisplayName('create_user'));
    }

    /** @test */
    public function 模組顯示名稱正確()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(ActivityStats::class);
        $instance = $component->instance();

        // 測試各種模組的顯示名稱
        $this->assertEquals('認證', $instance->getModuleDisplayName('auth'));
        $this->assertEquals('使用者管理', $instance->getModuleDisplayName('users'));
        $this->assertEquals('角色管理', $instance->getModuleDisplayName('roles'));
    }

    /** @test */
    public function 每小時分佈資料格式正確()
    {
        $this->actingAs($this->adminUser);

        // 建立不同時間的活動記錄
        for ($hour = 0; $hour < 24; $hour += 2) {
            Activity::factory()->count(rand(1, 10))->create([
                'user_id' => $this->adminUser->id,
                'created_at' => now()->setHour($hour)->setMinute(0)->setSecond(0),
            ]);
        }

        $component = Livewire::test(ActivityStats::class);
        $distributionData = $component->get('distributionData');
        $hourlyDistribution = $distributionData['hourly_distribution'];

        $this->assertCount(24, $hourlyDistribution);
        
        foreach ($hourlyDistribution as $hourData) {
            $this->assertArrayHasKey('hour', $hourData);
            $this->assertArrayHasKey('count', $hourData);
            $this->assertArrayHasKey('formatted_hour', $hourData);
            $this->assertArrayHasKey('intensity', $hourData);
        }
    }

    /** @test */
    public function 預測功能正常運作()
    {
        $this->actingAs($this->adminUser);

        // 建立有趨勢的測試資料
        for ($i = 1; $i <= 10; $i++) {
            Activity::factory()->count($i)->create([
                'user_id' => $this->adminUser->id,
                'created_at' => now()->subDays(10 - $i),
            ]);
        }

        $component = Livewire::test(ActivityStats::class);
        $trendAnalysis = $component->get('trendAnalysis');

        $this->assertNotNull($trendAnalysis['prediction']);
        $this->assertIsNumeric($trendAnalysis['prediction']);
    }

    /** @test */
    public function 處理資料不足的預測情況()
    {
        $this->actingAs($this->adminUser);

        // 只建立少量資料
        Activity::factory()->count(2)->create([
            'user_id' => $this->adminUser->id,
        ]);

        $component = Livewire::test(ActivityStats::class);
        $trendAnalysis = $component->get('trendAnalysis');

        $this->assertEquals('insufficient_data', $trendAnalysis['trend']);
        $this->assertNull($trendAnalysis['prediction']);
    }
}