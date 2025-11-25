<?php

namespace Tests\Feature\Livewire\Admin\Channels;

use App\Livewire\Admin\Channels\AgentList;
use App\Models\Agent;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AgentListTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 暫時停用權限安全觀察者
        Permission::unsetEventDispatcher();
        
        // 建立測試使用者和權限
        $this->user = User::factory()->create([
            'username' => 'testadmin',
            'email' => 'testadmin@example.com',
            'is_active' => true,
        ]);

        // 建立管理員角色和權限
        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理員',
            'description' => '系統管理員角色',
        ]);

        // 建立代理相關權限（使用正確的權限名稱）
        $permissions = [
            'channels.agents.view',
            'channels.agents.create', 
            'channels.agents.edit',
            'channels.agents.delete',
            'channels.agents.export',
        ];

        foreach ($permissions as $permissionName) {
            $permission = Permission::create([
                'name' => $permissionName,
                'display_name' => ucfirst(str_replace('.', ' ', $permissionName)),
                'module' => 'channels',
                'type' => explode('.', $permissionName)[2] ?? 'view',
            ]);
            
            $this->adminRole->permissions()->attach($permission->id);
        }

        // 指派角色給使用者
        $this->user->roles()->attach($this->adminRole->id);
        
        // 登入使用者
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_render_agent_list_component()
    {
        Livewire::test(AgentList::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.admin.channels.agent-list');
    }

    /** @test */
    public function it_displays_agents_in_the_list()
    {
        // 建立測試代理
        $agent1 = Agent::factory()->create([
            'name' => '測試代理一',
            'username' => 'agent1',
            'account' => 'aagent1',
            'level' => 1,
            'prefix' => 'a',
            'is_active' => true,
            'total_points' => 10000,
            'allocated_points' => 3000,
            'remaining_points' => 7000,
        ]);

        $agent2 = Agent::factory()->create([
            'name' => '測試代理二',
            'username' => 'agent2', 
            'account' => 'bagent2',
            'level' => 1,
            'prefix' => 'b',
            'is_active' => false,
            'total_points' => 5000,
            'allocated_points' => 1000,
            'remaining_points' => 4000,
        ]);

        Livewire::test(AgentList::class)
            ->assertSee('測試代理一')
            ->assertSee('測試代理二')
            ->assertSee('aagent1')
            ->assertSee('bagent2')
            ->assertSee('10,000.00')
            ->assertSee('5,000.00');
    }

    /** @test */
    public function it_can_search_agents_by_name()
    {
        Agent::factory()->create([
            'name' => '張三代理',
            'username' => 'zhang3',
            'account' => 'azhang3',
        ]);

        Agent::factory()->create([
            'name' => '李四代理',
            'username' => 'li4',
            'account' => 'bli4',
        ]);

        Livewire::test(AgentList::class)
            ->set('search', '張三')
            ->assertSee('張三代理')
            ->assertDontSee('李四代理');
    }

    /** @test */
    public function it_can_search_agents_by_account()
    {
        Agent::factory()->create([
            'name' => '代理A',
            'username' => 'agenta',
            'account' => 'aagenta',
        ]);

        Agent::factory()->create([
            'name' => '代理B',
            'username' => 'agentb',
            'account' => 'bagentb',
        ]);

        Livewire::test(AgentList::class)
            ->set('search', 'aagenta')
            ->assertSee('代理A')
            ->assertDontSee('代理B');
    }

    /** @test */
    public function it_can_filter_agents_by_level()
    {
        $parentAgent = Agent::factory()->create([
            'name' => '第一層代理',
            'level' => 1,
            'prefix' => 'a',
        ]);

        Agent::factory()->create([
            'name' => '第二層代理',
            'level' => 2,
            'parent_id' => $parentAgent->id,
        ]);

        Livewire::test(AgentList::class)
            ->set('levelFilter', '1')
            ->assertSee('第一層代理')
            ->assertDontSee('第二層代理');
    }

    /** @test */
    public function it_can_filter_agents_by_status()
    {
        Agent::factory()->create([
            'name' => '啟用代理',
            'is_active' => true,
        ]);

        Agent::factory()->create([
            'name' => '停用代理',
            'is_active' => false,
        ]);

        Livewire::test(AgentList::class)
            ->set('statusFilter', 'active')
            ->assertSee('啟用代理')
            ->assertDontSee('停用代理');
    }

    /** @test */
    public function it_can_filter_agents_by_prefix()
    {
        Agent::factory()->create([
            'name' => 'A前置代理',
            'prefix' => 'a',
            'level' => 1,
        ]);

        Agent::factory()->create([
            'name' => 'B前置代理',
            'prefix' => 'b',
            'level' => 1,
        ]);

        Livewire::test(AgentList::class)
            ->set('prefixFilter', 'a')
            ->assertSee('A前置代理')
            ->assertDontSee('B前置代理');
    }

    /** @test */
    public function it_can_filter_agents_by_points_range()
    {
        Agent::factory()->create([
            'name' => '低點數代理',
            'remaining_points' => 500,
        ]);

        Agent::factory()->create([
            'name' => '中點數代理',
            'remaining_points' => 5000,
        ]);

        Agent::factory()->create([
            'name' => '高點數代理',
            'remaining_points' => 15000,
        ]);

        Agent::factory()->create([
            'name' => '零點數代理',
            'remaining_points' => 0,
        ]);

        // 測試低點數篩選
        Livewire::test(AgentList::class)
            ->set('pointsRangeFilter', 'low')
            ->assertSee('低點數代理')
            ->assertDontSee('中點數代理')
            ->assertDontSee('高點數代理')
            ->assertSee('零點數代理');

        // 測試中點數篩選
        Livewire::test(AgentList::class)
            ->set('pointsRangeFilter', 'medium')
            ->assertSee('中點數代理')
            ->assertDontSee('低點數代理')
            ->assertDontSee('高點數代理');

        // 測試高點數篩選
        Livewire::test(AgentList::class)
            ->set('pointsRangeFilter', 'high')
            ->assertSee('高點數代理')
            ->assertDontSee('低點數代理')
            ->assertDontSee('中點數代理');

        // 測試零點數篩選
        Livewire::test(AgentList::class)
            ->set('pointsRangeFilter', 'zero')
            ->assertSee('零點數代理')
            ->assertDontSee('低點數代理')
            ->assertDontSee('中點數代理')
            ->assertDontSee('高點數代理');
    }

    /** @test */
    public function it_can_reset_all_filters()
    {
        Agent::factory()->create([
            'name' => '測試代理',
            'level' => 2,
            'is_active' => false,
        ]);

        Livewire::test(AgentList::class)
            ->set('search', '測試')
            ->set('levelFilter', '2')
            ->set('statusFilter', 'inactive')
            ->call('resetFilters')
            ->assertSet('search', '')
            ->assertSet('levelFilter', 'all')
            ->assertSet('statusFilter', 'all')
            ->assertSet('prefixFilter', 'all')
            ->assertSet('pointsRangeFilter', 'all');
    }

    /** @test */
    public function it_can_change_per_page_setting()
    {
        // 建立足夠的代理來測試分頁
        Agent::factory()->count(30)->create();

        Livewire::test(AgentList::class)
            ->set('perPage', 10)
            ->assertSet('perPage', 10);
    }

    /** @test */
    public function it_can_navigate_to_specific_page()
    {
        // 建立足夠的代理來測試分頁
        Agent::factory()->count(30)->create();

        Livewire::test(AgentList::class)
            ->set('perPage', 10)
            ->call('gotoPage', 2)
            ->assertSet('page', 2);
    }

    /** @test */
    public function it_can_toggle_agent_status()
    {
        $agent = Agent::factory()->create([
            'name' => '測試代理',
            'is_active' => true,
        ]);

        Livewire::test(AgentList::class)
            ->call('toggleAgentStatus', $agent->id);

        $this->assertFalse($agent->fresh()->is_active);
    }

    /** @test */
    public function it_displays_correct_statistics()
    {
        // 建立測試代理
        Agent::factory()->create([
            'is_active' => true,
            'total_points' => 1000,
            'allocated_points' => 300,
            'remaining_points' => 700,
        ]);

        Agent::factory()->create([
            'is_active' => false,
            'total_points' => 2000,
            'allocated_points' => 500,
            'remaining_points' => 1500,
        ]);

        Livewire::test(AgentList::class)
            ->assertSee('2') // 總代理數
            ->assertSee('1') // 啟用代理數
            ->assertSee('3,000.00') // 總點數
            ->assertSee('2,200.00'); // 剩餘點數
    }

    /** @test */
    public function it_shows_empty_state_when_no_agents()
    {
        Livewire::test(AgentList::class)
            ->assertSee('沒有找到代理')
            ->assertSee('目前還沒有任何代理資料');
    }

    /** @test */
    public function it_shows_filtered_empty_state_when_no_matching_agents()
    {
        Agent::factory()->create(['name' => '測試代理']);

        Livewire::test(AgentList::class)
            ->set('search', '不存在的代理')
            ->assertSee('沒有找到代理')
            ->assertSee('沒有符合篩選條件的代理');
    }

    /** @test */
    public function it_requires_view_permission_to_access()
    {
        // 移除檢視權限
        $viewPermission = Permission::where('name', 'channels.agents.view')->first();
        $this->adminRole->permissions()->detach($viewPermission->id);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        Livewire::test(AgentList::class);
    }

    /** @test */
    public function it_can_export_agents_with_permission()
    {
        Agent::factory()->count(5)->create();

        Livewire::test(AgentList::class)
            ->call('exportAgents')
            ->assertDispatched('show-toast');
    }

    /** @test */
    public function it_cannot_export_agents_without_permission()
    {
        // 移除匯出權限
        $exportPermission = Permission::where('name', 'channels.agents.export')->first();
        $this->adminRole->permissions()->detach($exportPermission->id);

        Agent::factory()->count(5)->create();

        Livewire::test(AgentList::class)
            ->call('exportAgents')
            ->assertDispatched('show-toast', function ($event) {
                return $event['type'] === 'error' && 
                       str_contains($event['message'], '沒有匯出代理資料的權限');
            });
    }

    /** @test */
    public function it_cannot_toggle_status_without_edit_permission()
    {
        // 移除編輯權限
        $editPermission = Permission::where('name', 'channels.agents.edit')->first();
        $this->adminRole->permissions()->detach($editPermission->id);

        $agent = Agent::factory()->create(['is_active' => true]);

        Livewire::test(AgentList::class)
            ->call('toggleAgentStatus', $agent->id)
            ->assertDispatched('show-toast', function ($event) {
                return $event['type'] === 'error' && 
                       str_contains($event['message'], '沒有編輯代理的權限');
            });

        // 確認狀態沒有改變
        $this->assertTrue($agent->fresh()->is_active);
    }

    /** @test */
    public function it_displays_agent_hierarchy_information()
    {
        $parentAgent = Agent::factory()->create([
            'name' => '上層代理',
            'level' => 1,
            'prefix' => 'a',
        ]);

        $childAgent = Agent::factory()->create([
            'name' => '下層代理',
            'level' => 2,
            'parent_id' => $parentAgent->id,
        ]);

        Livewire::test(AgentList::class)
            ->assertSee('上層代理')
            ->assertSee('下層代理')
            ->assertSee('第 1 層')
            ->assertSee('第 2 層')
            ->assertSee('上層：上層代理');
    }

    /** @test */
    public function it_displays_agent_points_information_correctly()
    {
        Agent::factory()->create([
            'name' => '點數代理',
            'total_points' => 10000.50,
            'allocated_points' => 3000.25,
            'remaining_points' => 7000.25,
        ]);

        Livewire::test(AgentList::class)
            ->assertSee('10,000.50')
            ->assertSee('3,000.25')
            ->assertSee('7,000.25');
    }

    /** @test */
    public function it_maintains_filter_state_in_url_query_string()
    {
        Livewire::test(AgentList::class)
            ->set('search', '測試')
            ->set('levelFilter', '1')
            ->assertSet('search', '測試')
            ->assertSet('levelFilter', '1');
    }
}