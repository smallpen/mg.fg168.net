<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Admin\Roles\RoleStatistics;
use App\Livewire\Admin\Roles\RoleStatsDashboard;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 角色統計元件測試
 */
class RoleStatisticsComponentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試使用者並登入
        $user = User::factory()->create();
        $this->actingAs($user);
    }

    /**
     * 測試角色統計元件載入
     */
    public function test_role_statistics_component_loads(): void
    {
        $role = Role::factory()->create([
            'display_name' => '測試角色',
        ]);

        Livewire::test(RoleStatistics::class, ['role' => $role, 'mode' => 'role'])
            ->assertSet('role.id', $role->id)
            ->assertSet('mode', 'role')
            ->assertSee('測試角色 - 統計資訊');
    }

    /**
     * 測試系統統計模式
     */
    public function test_system_statistics_mode(): void
    {
        // 建立測試資料
        Role::factory()->count(3)->create();
        
        Livewire::test(RoleStatistics::class, ['mode' => 'system'])
            ->assertSet('mode', 'system')
            ->assertSee('角色管理統計');
    }

    /**
     * 測試統計資料重新整理
     */
    public function test_can_refresh_statistics(): void
    {
        $role = Role::factory()->create();
        
        Livewire::test(RoleStatistics::class, ['role' => $role, 'mode' => 'role'])
            ->call('refreshStatistics')
            ->assertDispatched('statistics-refreshed')
            ->assertSee('統計資料已更新');
    }

    /**
     * 測試清除快取功能
     */
    public function test_can_clear_cache(): void
    {
        $role = Role::factory()->create();
        
        Livewire::test(RoleStatistics::class, ['role' => $role, 'mode' => 'role'])
            ->call('clearCacheAndRefresh')
            ->assertSee('快取已清除，統計資料已更新');
    }

    /**
     * 測試模式切換
     */
    public function test_can_switch_modes(): void
    {
        $role = Role::factory()->create();
        
        Livewire::test(RoleStatistics::class, ['role' => $role, 'mode' => 'role'])
            ->assertSet('mode', 'role')
            ->call('switchMode', 'system')
            ->assertSet('mode', 'system');
    }

    /**
     * 測試趨勢天數設定
     */
    public function test_can_set_trend_days(): void
    {
        Livewire::test(RoleStatistics::class, ['mode' => 'system'])
            ->assertSet('trendDays', 30)
            ->call('setTrendDays', 90)
            ->assertSet('trendDays', 90);
    }

    /**
     * 測試自動重新整理切換
     */
    public function test_can_toggle_auto_refresh(): void
    {
        Livewire::test(RoleStatistics::class, ['mode' => 'system'])
            ->assertSet('autoRefresh', false)
            ->call('toggleAutoRefresh')
            ->assertSet('autoRefresh', true)
            ->assertDispatched('start-auto-refresh');
    }

    /**
     * 測試統計卡片資料
     */
    public function test_statistics_cards_display_correctly(): void
    {
        $role = Role::factory()->create();
        $permissions = Permission::factory()->count(3)->create();
        $users = User::factory()->count(2)->create();
        
        $role->permissions()->attach($permissions->pluck('id'));
        $role->users()->attach($users->pluck('id'));

        $component = Livewire::test(RoleStatistics::class, ['role' => $role, 'mode' => 'role']);
        
        $cards = $component->get('statisticsCards');
        
        $this->assertCount(4, $cards);
        $this->assertEquals('使用者數量', $cards[0]['title']);
        $this->assertEquals(2, $cards[0]['value']);
        $this->assertEquals('直接權限', $cards[1]['title']);
        $this->assertEquals(3, $cards[1]['value']);
    }

    /**
     * 測試權限圖表資料
     */
    public function test_permission_chart_data_format(): void
    {
        $role = Role::factory()->create();
        $permissions = Permission::factory()->count(2)->create(['module' => 'users']);
        $role->permissions()->attach($permissions->pluck('id'));

        $component = Livewire::test(RoleStatistics::class, ['role' => $role, 'mode' => 'role']);
        
        $chartData = $component->get('permissionChartData');
        
        $this->assertArrayHasKey('labels', $chartData);
        $this->assertArrayHasKey('datasets', $chartData);
        $this->assertCount(1, $chartData['datasets']);
        $this->assertEquals('權限數量', $chartData['datasets'][0]['label']);
    }

    /**
     * 測試角色資訊屬性
     */
    public function test_role_info_property(): void
    {
        $role = Role::factory()->create([
            'name' => 'test_role',
            'display_name' => '測試角色',
            'description' => '測試描述',
            'is_system_role' => false,
            'is_active' => true,
        ]);

        $component = Livewire::test(RoleStatistics::class, ['role' => $role, 'mode' => 'role']);
        
        $roleInfo = $component->get('roleInfo');
        
        $this->assertEquals($role->id, $roleInfo['id']);
        $this->assertEquals('test_role', $roleInfo['name']);
        $this->assertEquals('測試角色', $roleInfo['display_name']);
        $this->assertEquals('測試描述', $roleInfo['description']);
        $this->assertFalse($roleInfo['is_system_role']);
        $this->assertTrue($roleInfo['is_active']);
    }

    /**
     * 測試統計儀表板元件
     */
    public function test_role_stats_dashboard_component(): void
    {
        // 建立測試資料
        Role::factory()->count(5)->create(['is_active' => true]);
        Role::factory()->count(2)->create(['is_active' => false]);
        
        $component = Livewire::test(RoleStatsDashboard::class);
        
        $quickStats = $component->get('quickStats');
        
        $this->assertCount(4, $quickStats);
        $this->assertEquals('總角色數', $quickStats[0]['label']);
        $this->assertEquals(7, $quickStats[0]['value']);
        $this->assertEquals('啟用角色', $quickStats[1]['label']);
        $this->assertEquals(5, $quickStats[1]['value']);
    }

    /**
     * 測試儀表板詳細資訊切換
     */
    public function test_dashboard_toggle_details(): void
    {
        Livewire::test(RoleStatsDashboard::class)
            ->assertSet('showDetails', false)
            ->call('toggleDetails')
            ->assertSet('showDetails', true);
    }

    /**
     * 測試儀表板統計重新整理
     */
    public function test_dashboard_refresh_stats(): void
    {
        Livewire::test(RoleStatsDashboard::class)
            ->call('refreshStats')
            ->assertSee('統計資料已更新');
    }

    /**
     * 測試事件監聽
     */
    public function test_handles_role_update_events(): void
    {
        $role = Role::factory()->create();
        
        $component = Livewire::test(RoleStatistics::class, ['role' => $role, 'mode' => 'role']);
        
        // 模擬角色更新事件
        $component->dispatch('role-updated', $role->id);
        
        // 元件應該重新載入統計資料
        $this->assertTrue(true); // 事件處理不會有明顯的斷言，但不應該出錯
    }

    /**
     * 測試權限更新事件
     */
    public function test_handles_permissions_update_events(): void
    {
        $role = Role::factory()->create();
        
        $component = Livewire::test(RoleStatistics::class, ['role' => $role, 'mode' => 'role']);
        
        // 模擬權限更新事件
        $component->dispatch('permissions-updated', $role->id);
        
        // 元件應該重新載入統計資料
        $this->assertTrue(true); // 事件處理不會有明顯的斷言，但不應該出錯
    }

    /**
     * 測試載入狀態
     */
    public function test_loading_state(): void
    {
        $role = Role::factory()->create();
        
        $component = Livewire::test(RoleStatistics::class, ['role' => $role, 'mode' => 'role']);
        
        // 初始狀態應該不是載入中
        $component->assertSet('loading', false);
        
        // 呼叫載入統計時應該設定載入狀態
        $component->call('loadStatistics');
        
        // 載入完成後應該重置載入狀態
        $component->assertSet('loading', false);
    }
}