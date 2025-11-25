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

class AgentListBasicTest extends TestCase
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
        $agent1 = Agent::create([
            'name' => '測試代理一',
            'username' => 'agent1',
            'account' => 'aagent1',
            'email' => 'agent1@example.com',
            'level' => 1,
            'prefix' => 'a',
            'is_active' => true,
            'total_points' => 10000,
            'allocated_points' => 3000,
            'remaining_points' => 7000,
        ]);

        $agent2 = Agent::create([
            'name' => '測試代理二',
            'username' => 'agent2', 
            'account' => 'bagent2',
            'email' => 'agent2@example.com',
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
        Agent::create([
            'name' => '張三代理',
            'username' => 'zhang3',
            'account' => 'azhang3',
            'email' => 'zhang3@example.com',
            'level' => 1,
            'prefix' => 'a',
            'is_active' => true,
            'total_points' => 1000,
            'remaining_points' => 1000,
        ]);

        Agent::create([
            'name' => '李四代理',
            'username' => 'li4',
            'account' => 'bli4',
            'email' => 'li4@example.com',
            'level' => 1,
            'prefix' => 'b',
            'is_active' => true,
            'total_points' => 1000,
            'remaining_points' => 1000,
        ]);

        Livewire::test(AgentList::class)
            ->set('search', '張三')
            ->assertSee('張三代理')
            ->assertDontSee('李四代理');
    }

    /** @test */
    public function it_can_reset_all_filters()
    {
        Agent::create([
            'name' => '測試代理',
            'username' => 'testagent',
            'account' => 'atestagent',
            'email' => 'test@example.com',
            'level' => 2,
            'is_active' => false,
            'total_points' => 1000,
            'remaining_points' => 1000,
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
    public function it_shows_empty_state_when_no_agents()
    {
        Livewire::test(AgentList::class)
            ->assertSee('沒有找到代理')
            ->assertSee('目前還沒有任何代理資料');
    }
}