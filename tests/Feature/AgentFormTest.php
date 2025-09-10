<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AgentFormTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 執行 seeders 來建立基礎資料
        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        $this->artisan('db:seed', ['--class' => 'UserSeeder']);
        
        // 取得管理員使用者
        $this->adminUser = User::where('username', 'admin')->first();
        $this->assertNotNull($this->adminUser, '管理員使用者不存在，請確認 UserSeeder 已執行');
    }

    /** @test */
    public function agent_form_component_can_be_rendered()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test('admin.channels.agent-form');

        $component->assertStatus(200);
        $component->assertSee('代理姓名');
        $component->assertSee('使用者名稱');
        $component->assertSee('電子郵件');
    }

    /** @test */
    public function agent_form_validates_required_fields()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test('admin.channels.agent-form');

        $component->call('save')
                  ->assertHasErrors([
                      'name' => 'required',
                      'username' => 'required',
                      'email' => 'required',
                      'initial_points' => 'required',
                  ]);
    }

    /** @test */
    public function agent_form_can_create_first_level_agent()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test('admin.channels.agent-form');

        $component->set('name', '測試代理')
                  ->set('username', 'testagent')
                  ->set('email', 'test@example.com')
                  ->set('prefix', 'a')
                  ->set('initial_points', 1000)
                  ->call('save');

        $this->assertDatabaseHas('agents', [
            'name' => '測試代理',
            'username' => 'testagent',
            'account' => 'atestagent',
            'email' => 'test@example.com',
            'prefix' => 'a',
            'level' => 1,
            'total_points' => 1000,
            'remaining_points' => 1000,
        ]);
    }

    /** @test */
    public function agent_form_validates_unique_prefix()
    {
        $this->actingAs($this->adminUser);

        // 建立一個已存在的代理
        Agent::create([
            'name' => '現有代理',
            'username' => 'existing',
            'account' => 'aexisting',
            'email' => 'existing@example.com',
            'prefix' => 'a',
            'level' => 1,
            'total_points' => 1000,
            'remaining_points' => 1000,
            'allocated_points' => 0,
            'is_active' => true,
            'created_by' => $this->adminUser->id,
        ]);

        $component = Livewire::test('admin.channels.agent-form');

        $component->set('name', '新代理')
                  ->set('username', 'newagent')
                  ->set('email', 'new@example.com')
                  ->set('prefix', 'a') // 使用已存在的前置符號
                  ->set('initial_points', 1000)
                  ->call('save')
                  ->assertHasErrors(['prefix']);
    }

    /** @test */
    public function agent_form_can_create_sub_agent()
    {
        $this->actingAs($this->adminUser);

        // 建立上層代理
        $parentAgent = Agent::create([
            'name' => '上層代理',
            'username' => 'parent',
            'account' => 'aparent',
            'email' => 'parent@example.com',
            'prefix' => 'a',
            'level' => 1,
            'total_points' => 2000,
            'remaining_points' => 2000,
            'allocated_points' => 0,
            'is_active' => true,
            'created_by' => $this->adminUser->id,
        ]);

        $component = Livewire::test('admin.channels.agent-form');

        $component->set('name', '下層代理')
                  ->set('username', 'subagent')
                  ->set('email', 'sub@example.com')
                  ->set('parent_id', $parentAgent->id)
                  ->set('initial_points', 500)
                  ->call('save');

        $this->assertDatabaseHas('agents', [
            'name' => '下層代理',
            'username' => 'subagent',
            'account' => 'asubagent',
            'email' => 'sub@example.com',
            'parent_id' => $parentAgent->id,
            'level' => 2,
            'total_points' => 500,
            'remaining_points' => 500,
        ]);

        // 檢查上層代理的點數是否正確扣除
        $parentAgent->refresh();
        $this->assertEquals(1500, $parentAgent->remaining_points);
        $this->assertEquals(500, $parentAgent->allocated_points);
    }

    /** @test */
    public function agent_form_validates_insufficient_parent_points()
    {
        $this->actingAs($this->adminUser);

        // 建立點數不足的上層代理
        $parentAgent = Agent::create([
            'name' => '上層代理',
            'username' => 'parent',
            'account' => 'aparent',
            'email' => 'parent@example.com',
            'prefix' => 'a',
            'level' => 1,
            'total_points' => 100,
            'remaining_points' => 100,
            'allocated_points' => 0,
            'is_active' => true,
            'created_by' => $this->adminUser->id,
        ]);

        $component = Livewire::test('admin.channels.agent-form');

        $component->set('name', '下層代理')
                  ->set('username', 'subagent')
                  ->set('email', 'sub@example.com')
                  ->set('parent_id', $parentAgent->id)
                  ->set('initial_points', 500) // 超過上層代理的剩餘點數
                  ->call('updatedInitialPoints')
                  ->assertHasErrors(['initial_points']);
    }

    /** @test */
    public function agent_form_updates_preview_account_correctly()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test('admin.channels.agent-form');

        // 測試第一層代理的帳號預覽
        $component->set('prefix', 'b')
                  ->set('username', 'testuser')
                  ->assertSet('previewAccount', 'btestuser');

        // 測試下層代理的帳號預覽
        $parentAgent = Agent::create([
            'name' => '上層代理',
            'username' => 'parent',
            'account' => 'aparent',
            'email' => 'parent@example.com',
            'prefix' => 'a',
            'level' => 1,
            'total_points' => 1000,
            'remaining_points' => 1000,
            'allocated_points' => 0,
            'is_active' => true,
            'created_by' => $this->adminUser->id,
        ]);

        $component->set('parent_id', $parentAgent->id)
                  ->set('username', 'subuser')
                  ->assertSet('previewAccount', 'asubuser');
    }
}