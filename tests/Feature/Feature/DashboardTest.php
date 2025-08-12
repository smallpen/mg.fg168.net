<?php

namespace Tests\Feature\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Activity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Livewire\Livewire;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試使用者和角色
        $adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理員',
            'description' => '系統管理員'
        ]);
        
        $this->user = User::create([
            'username' => 'testadmin',
            'name' => '測試管理員',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'is_active' => true
        ]);
        
        $this->user->roles()->attach($adminRole);
    }

    /**
     * 測試儀表板頁面可以正常載入
     */
    public function test_dashboard_page_loads(): void
    {
        $response = $this->actingAs($this->user)
                        ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');
    }

    /**
     * 測試儀表板 Livewire 元件可以正常渲染
     */
    public function test_dashboard_livewire_component_renders(): void
    {
        Livewire::actingAs($this->user)
                ->test('admin.dashboard')
                ->assertStatus(200)
                ->assertSee('儀表板')
                ->assertSee('使用者總數')
                ->assertSee('啟用使用者')
                ->assertSee('角色總數')
                ->assertSee('今日活動');
    }

    /**
     * 測試統計資料計算
     */
    public function test_dashboard_statistics_calculation(): void
    {
        // 建立一些測試資料
        User::factory()->count(5)->create();
        Role::factory()->count(3)->create();
        Activity::factory()->count(10)->create([
            'user_id' => $this->user->id,
            'created_at' => now()
        ]);

        Livewire::actingAs($this->user)
                ->test('admin.dashboard')
                ->assertSet('stats.total_users.count', 6) // 5 + 1 (測試使用者)
                ->assertSet('stats.total_roles.count', 4); // 3 + 1 (admin 角色)
    }

    /**
     * 測試重新整理功能
     */
    public function test_dashboard_refresh_functionality(): void
    {
        Livewire::actingAs($this->user)
                ->test('admin.dashboard')
                ->call('refresh')
                ->assertDispatched('toast');
    }

    /**
     * 測試未登入使用者無法存取儀表板
     */
    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('admin.login'));
    }
}
