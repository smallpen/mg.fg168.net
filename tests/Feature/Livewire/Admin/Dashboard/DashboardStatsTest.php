<?php

namespace Tests\Feature\Livewire\Admin\Dashboard;

use App\Http\Livewire\Admin\Dashboard\DashboardStats;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * DashboardStats 元件測試
 * 
 * 測試儀表板統計資訊的顯示、快取機制和權限控制
 */
class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試角色
        $this->adminRole = Role::factory()->create(['name' => 'admin']);
        
        // 建立管理員使用者
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($this->adminRole);
    }

    /**
     * 測試元件能正確渲染
     */
    public function test_component_renders_correctly()
    {
        $this->actingAs($this->admin);

        Livewire::test(DashboardStats::class)
            ->assertStatus(200)
            ->assertSee('系統統計')
            ->assertSee('使用者總數')
            ->assertSee('角色總數');
    }

    /**
     * 測試使用者統計顯示
     */
    public function test_user_statistics_display()
    {
        $this->actingAs($this->admin);

        // 建立測試使用者
        User::factory()->count(10)->create();
        $activeUsers = User::factory()->count(8)->create(['is_active' => true]);
        $inactiveUsers = User::factory()->count(2)->create(['is_active' => false]);

        Livewire::test(DashboardStats::class)
            ->assertSee('11') // 包含管理員使用者，總共11個
            ->assertSee('8') // 啟用使用者
            ->assertSee('2'); // 停用使用者
    }

    /**
     * 測試角色統計顯示
     */
    public function test_role_statistics_display()
    {
        $this->actingAs($this->admin);

        // 建立測試角色
        Role::factory()->count(5)->create(['is_active' => true]);
        Role::factory()->count(2)->create(['is_active' => false]);

        Livewire::test(DashboardStats::class)
            ->assertSee('8') // 包含管理員角色，總共8個
            ->assertSee('6') // 啟用角色
            ->assertSee('2'); // 停用角色
    }

    /**
     * 測試今日新增使用者統計
     */
    public function test_today_new_users_statistics()
    {
        $this->actingAs($this->admin);

        // 建立今日新增的使用者
        User::factory()->count(3)->create(['created_at' => now()]);
        
        // 建立昨日新增的使用者
        User::factory()->count(2)->create(['created_at' => now()->subDay()]);

        Livewire::test(DashboardStats::class)
            ->assertSee('今日新增')
            ->assertSee('3');
    }

    /**
     * 測試本週新增使用者統計
     */
    public function test_this_week_new_users_statistics()
    {
        $this->actingAs($this->admin);

        // 建立本週新增的使用者
        User::factory()->count(5)->create(['created_at' => now()->startOfWeek()]);
        
        // 建立上週新增的使用者
        User::factory()->count(3)->create(['created_at' => now()->subWeek()]);

        Livewire::test(DashboardStats::class)
            ->assertSee('本週新增')
            ->assertSee('5');
    }

    /**
     * 測試統計資料快取機制
     */
    public function test_statistics_caching()
    {
        $this->actingAs($this->admin);

        // 清除快取
        Cache::flush();

        // 第一次載入
        Livewire::test(DashboardStats::class);

        // 檢查快取是否被建立
        $this->assertTrue(Cache::has('dashboard_stats_users'));
        $this->assertTrue(Cache::has('dashboard_stats_roles'));
    }

    /**
     * 測試快取刷新功能
     */
    public function test_cache_refresh()
    {
        $this->actingAs($this->admin);

        // 設定初始快取
        Cache::put('dashboard_stats_users', ['total' => 5], 3600);

        // 建立新使用者
        User::factory()->count(3)->create();

        Livewire::test(DashboardStats::class)
            ->call('refreshStats')
            ->assertDispatched('stats-refreshed');

        // 檢查快取是否被更新
        $cachedStats = Cache::get('dashboard_stats_users');
        $this->assertGreaterThan(5, $cachedStats['total']);
    }

    /**
     * 測試統計圖表資料
     */
    public function test_chart_data_generation()
    {
        $this->actingAs($this->admin);

        // 建立不同時間的使用者資料
        for ($i = 6; $i >= 0; $i--) {
            User::factory()->count(rand(1, 5))->create([
                'created_at' => now()->subDays($i)
            ]);
        }

        $component = Livewire::test(DashboardStats::class);
        
        // 檢查圖表資料是否正確生成
        $this->assertIsArray($component->get('chartData'));
        $this->assertArrayHasKey('labels', $component->get('chartData'));
        $this->assertArrayHasKey('datasets', $component->get('chartData'));
    }

    /**
     * 測試權限控制 - 無權限使用者
     */
    public function test_unauthorized_access()
    {
        $unauthorizedUser = User::factory()->create();
        $this->actingAs($unauthorizedUser);

        Livewire::test(DashboardStats::class)
            ->assertForbidden();
    }

    /**
     * 測試權限控制 - 部分統計權限
     */
    public function test_partial_statistics_permission()
    {
        $limitedUser = User::factory()->create();
        $limitedRole = Role::factory()->create(['name' => 'limited_viewer']);
        $limitedUser->roles()->attach($limitedRole);
        
        $this->actingAs($limitedUser);

        Livewire::test(DashboardStats::class)
            ->assertSee('使用者總數')
            ->assertDontSee('系統設定'); // 沒有系統管理權限
    }

    /**
     * 測試即時統計更新
     */
    public function test_real_time_statistics_update()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(DashboardStats::class);
        $initialUserCount = $component->get('userStats')['total'];

        // 建立新使用者
        User::factory()->create();

        $component->call('refreshStats')
            ->assertSet('userStats.total', $initialUserCount + 1);
    }

    /**
     * 測試統計資料匯出
     */
    public function test_statistics_export()
    {
        $this->actingAs($this->admin);

        User::factory()->count(10)->create();
        Role::factory()->count(5)->create();

        Livewire::test(DashboardStats::class)
            ->call('exportStatistics')
            ->assertDispatched('statistics-export-started');
    }

    /**
     * 測試統計時間範圍篩選
     */
    public function test_statistics_time_range_filter()
    {
        $this->actingAs($this->admin);

        // 建立不同時間的資料
        User::factory()->count(3)->create(['created_at' => now()->subDays(7)]);
        User::factory()->count(5)->create(['created_at' => now()->subDays(30)]);

        Livewire::test(DashboardStats::class)
            ->set('timeRange', 'last_7_days')
            ->call('updateTimeRange')
            ->assertSee('3') // 最近7天的使用者
            ->set('timeRange', 'last_30_days')
            ->call('updateTimeRange')
            ->assertSee('8'); // 最近30天的使用者
    }

    /**
     * 測試統計資料比較
     */
    public function test_statistics_comparison()
    {
        $this->actingAs($this->admin);

        // 建立本月和上月的資料
        User::factory()->count(10)->create(['created_at' => now()]);
        User::factory()->count(8)->create(['created_at' => now()->subMonth()]);

        Livewire::test(DashboardStats::class)
            ->assertSee('較上月增長')
            ->assertSee('25%'); // (10-8)/8 * 100%
    }

    /**
     * 測試統計資料載入狀態
     */
    public function test_statistics_loading_state()
    {
        $this->actingAs($this->admin);

        Livewire::test(DashboardStats::class)
            ->call('refreshStats')
            ->assertSet('isLoading', true)
            ->assertSee('載入中...');
    }

    /**
     * 測試統計資料錯誤處理
     */
    public function test_statistics_error_handling()
    {
        $this->actingAs($this->admin);

        // 模擬資料庫錯誤
        \DB::shouldReceive('table')->andThrow(new \Exception('Database error'));

        Livewire::test(DashboardStats::class)
            ->call('refreshStats')
            ->assertSee('統計資料載入失敗')
            ->assertDispatched('stats-error');
    }

    /**
     * 測試統計卡片點擊事件
     */
    public function test_statistics_card_click_events()
    {
        $this->actingAs($this->admin);

        Livewire::test(DashboardStats::class)
            ->call('onUserStatsClick')
            ->assertDispatched('navigate-to-users')
            ->call('onRoleStatsClick')
            ->assertDispatched('navigate-to-roles');
    }

    /**
     * 測試響應式統計顯示
     */
    public function test_responsive_statistics_display()
    {
        $this->actingAs($this->admin);

        Livewire::test(DashboardStats::class)
            ->set('isMobile', true)
            ->assertSee('統計摘要') // 行動版顯示簡化版本
            ->set('isMobile', false)
            ->assertSee('詳細統計'); // 桌面版顯示完整版本
    }
}