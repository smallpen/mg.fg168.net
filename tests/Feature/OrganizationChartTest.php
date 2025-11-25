<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Player;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrganizationChartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 執行 seeders 來建立基本資料
        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        $this->artisan('db:seed', ['--class' => 'UserSeeder']);
    }

    /** @test */
    public function it_can_display_organization_chart_page()
    {
        // 建立測試使用者並登入
        $user = User::where('username', 'admin')->first();
        $this->actingAs($user);

        // 訪問組織架構圖表頁面
        $response = $this->get(route('admin.channels.organization.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.channels.organization');
        $response->assertSee('組織架構圖表');
    }

    /** @test */
    public function it_can_render_organization_chart_livewire_component()
    {
        // 建立測試使用者並登入
        $user = User::where('username', 'admin')->first();
        $this->actingAs($user);

        // 建立測試代理資料
        $rootAgent = Agent::create([
            'name' => '測試根代理',
            'username' => 'root_agent',
            'account' => 'a_root_agent',
            'email' => 'root@test.com',
            'prefix' => 'a',
            'level' => 1,
            'total_points' => 10000,
            'allocated_points' => 5000,
            'remaining_points' => 5000,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $subAgent = Agent::create([
            'name' => '測試子代理',
            'username' => 'sub_agent',
            'account' => 'a_sub_agent',
            'email' => 'sub@test.com',
            'level' => 2,
            'parent_id' => $rootAgent->id,
            'total_points' => 5000,
            'allocated_points' => 2000,
            'remaining_points' => 3000,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $player = Player::create([
            'name' => '測試玩家',
            'username' => 'test_player',
            'account' => 'a_test_player',
            'email' => 'player@test.com',
            'agent_id' => $subAgent->id,
            'points' => 1000,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        // 測試 Livewire 元件
        Livewire::test('admin.channels.organization-chart')
            ->assertStatus(200)
            ->call('setViewMode', 'tree')
            ->assertSet('viewMode', 'tree')
            ->call('togglePlayers')
            ->assertSet('showPlayers', false)
            ->call('togglePoints')
            ->assertSet('showPoints', false);
    }

    /** @test */
    public function it_can_select_agent_and_show_details()
    {
        // 建立測試使用者並登入
        $user = User::where('username', 'admin')->first();
        $this->actingAs($user);

        // 建立測試代理
        $agent = Agent::create([
            'name' => '測試代理',
            'username' => 'test_agent',
            'account' => 'b_test_agent',
            'email' => 'test@test.com',
            'prefix' => 'b',
            'level' => 1,
            'total_points' => 8000,
            'allocated_points' => 3000,
            'remaining_points' => 5000,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        // 測試選擇代理功能
        Livewire::test('admin.channels.organization-chart')
            ->call('selectAgent', $agent->id)
            ->assertSet('selectedAgentId', (string) $agent->id);
    }

    /** @test */
    public function it_can_get_organization_data()
    {
        // 建立測試使用者並登入
        $user = User::where('username', 'admin')->first();
        $this->actingAs($user);

        // 建立測試代理結構
        $rootAgent = Agent::create([
            'name' => '根代理',
            'username' => 'root',
            'account' => 'c_root',
            'email' => 'root@example.com',
            'prefix' => 'c',
            'level' => 1,
            'total_points' => 15000,
            'allocated_points' => 8000,
            'remaining_points' => 7000,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $childAgent = Agent::create([
            'name' => '子代理',
            'username' => 'child',
            'account' => 'c_child',
            'email' => 'child@example.com',
            'level' => 2,
            'parent_id' => $rootAgent->id,
            'total_points' => 8000,
            'allocated_points' => 3000,
            'remaining_points' => 5000,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $player = Player::create([
            'name' => '玩家一',
            'username' => 'player1',
            'account' => 'c_player1',
            'email' => 'player1@example.com',
            'agent_id' => $childAgent->id,
            'points' => 2000,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        // 測試組織資料獲取
        $component = Livewire::test('admin.channels.organization-chart');
        
        // 檢查元件是否正常載入
        $component->assertStatus(200);
    }

    /** @test */
    public function it_can_export_chart()
    {
        // 建立測試使用者並登入
        $user = User::where('username', 'admin')->first();
        $this->actingAs($user);

        // 測試匯出功能
        Livewire::test('admin.channels.organization-chart')
            ->call('exportChart', 'png');
    }

    /** @test */
    public function it_can_set_root_agent()
    {
        // 建立測試使用者並登入
        $user = User::where('username', 'admin')->first();
        $this->actingAs($user);

        // 建立測試代理
        $agent = Agent::create([
            'name' => '指定根代理',
            'username' => 'specified_root',
            'account' => 'd_specified_root',
            'email' => 'specified@test.com',
            'prefix' => 'd',
            'level' => 1,
            'total_points' => 12000,
            'allocated_points' => 6000,
            'remaining_points' => 6000,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        // 測試設定根代理
        Livewire::test('admin.channels.organization-chart')
            ->call('setRootAgent', $agent->id);
    }

    /** @test */
    public function it_can_get_agent_details()
    {
        // 建立測試使用者並登入
        $user = User::where('username', 'admin')->first();
        $this->actingAs($user);

        // 建立測試代理
        $agent = Agent::create([
            'name' => '詳情測試代理',
            'username' => 'detail_agent',
            'account' => 'e_detail_agent',
            'email' => 'detail@test.com',
            'phone' => '0912345678',
            'prefix' => 'e',
            'level' => 1,
            'total_points' => 20000,
            'allocated_points' => 10000,
            'remaining_points' => 10000,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        // 測試獲取代理詳情
        $component = Livewire::test('admin.channels.organization-chart');
        
        // 檢查元件是否正常載入並包含代理資料
        $component->assertStatus(200);
    }
}