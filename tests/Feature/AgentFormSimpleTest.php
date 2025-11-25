<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentFormSimpleTest extends TestCase
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
    public function agent_create_page_can_be_accessed()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('admin.channels.agents.create'));

        $response->assertStatus(200);
        $response->assertSee('建立代理');
    }

    /** @test */
    public function agent_index_page_can_be_accessed()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('admin.channels.agents.index'));

        $response->assertStatus(200);
        $response->assertSee('代理管理');
    }
}