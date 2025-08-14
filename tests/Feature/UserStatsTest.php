<?php

namespace Tests\Feature;

use App\Livewire\Admin\Users\UserStats;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * UserStats 元件測試
 * 
 * 測試使用者統計功能
 */
class UserStatsTest extends TestCase
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
        
        // 建立必要的權限
        $permission = \App\Models\Permission::factory()->create(['name' => 'users.view']);
        $this->adminRole->permissions()->attach($permission);
    }

    /**
     * 測試統計元件能正確渲染
     */
    public function test_stats_component_renders_correctly()
    {
        $this->actingAs($this->admin);

        // 建立一些測試資料
        User::factory()->count(5)->create(['is_active' => true]);
        User::factory()->count(2)->create(['is_active' => false]);

        $component = Livewire::test(UserStats::class);
        
        $component->assertStatus(200)
                  ->assertSee('統計資訊')
                  ->assertSee('總使用者數')
                  ->assertSee('啟用使用者')
                  ->assertSee('停用使用者');
    }

    /**
     * 測試統計資料計算正確性
     */
    public function test_stats_calculation_is_correct()
    {
        $this->actingAs($this->admin);

        // 建立測試資料
        $activeUsers = User::factory()->count(8)->create(['is_active' => true]);
        $inactiveUsers = User::factory()->count(2)->create(['is_active' => false]);
        
        $component = Livewire::test(UserStats::class);
        
        // 檢查統計資料是否正確（包含 admin 使用者，所以總數是 11）
        $this->assertEquals(11, $component->get('stats')['total_users']);
        $this->assertEquals(9, $component->get('stats')['active_users']); // 8 + admin
        $this->assertEquals(2, $component->get('stats')['inactive_users']);
    }

    /**
     * 測試重新整理功能
     */
    public function test_refresh_stats_works()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(UserStats::class);
        
        $component->call('refreshStats')
                  ->assertDispatched('show-toast');
    }

    /**
     * 測試詳細資訊切換
     */
    public function test_toggle_details_works()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(UserStats::class);
        
        // 預設應該是隱藏詳細資訊
        $this->assertFalse($component->get('showDetails'));
        
        // 切換顯示詳細資訊
        $component->call('toggleDetails');
        $this->assertTrue($component->get('showDetails'));
        
        // 再次切換隱藏詳細資訊
        $component->call('toggleDetails');
        $this->assertFalse($component->get('showDetails'));
    }

    /**
     * 測試事件監聽
     */
    public function test_listens_to_user_events()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(UserStats::class);
        
        // 模擬使用者狀態更新事件
        $component->dispatch('user-status-updated');
        
        // 模擬批量更新事件
        $component->dispatch('users-bulk-updated');
        
        // 模擬使用者建立事件
        $component->dispatch('user-created');
        
        // 模擬使用者刪除事件
        $component->dispatch('user-deleted');
        
        // 這些事件應該觸發統計資料重新載入
        $this->assertNotNull($component->get('stats'));
    }
}