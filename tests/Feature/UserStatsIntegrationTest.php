<?php

namespace Tests\Feature;

use App\Livewire\Admin\Users\UserStats;
use App\Livewire\Admin\Users\UserList;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * UserStats 整合測試
 * 
 * 測試統計功能與使用者管理的整合
 */
class UserStatsIntegrationTest extends TestCase
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
        $permissions = ['users.view', 'users.edit'];
        foreach ($permissions as $permissionName) {
            $permission = \App\Models\Permission::factory()->create(['name' => $permissionName]);
            $this->adminRole->permissions()->attach($permission);
        }
    }

    /**
     * 測試統計資料在使用者狀態變更後更新
     */
    public function test_stats_update_when_user_status_changes()
    {
        $this->actingAs($this->admin);

        // 建立測試使用者
        $user = User::factory()->create(['is_active' => true]);

        // 建立統計元件
        $statsComponent = Livewire::test(UserStats::class);
        $initialActiveCount = $statsComponent->get('stats')['active_users'];

        // 建立使用者列表元件並切換使用者狀態
        $userListComponent = Livewire::test(UserList::class);
        $userListComponent->call('toggleUserStatus', $user->id);

        // 重新載入統計元件並檢查統計資料是否更新
        $statsComponent = Livewire::test(UserStats::class);
        $newActiveCount = $statsComponent->get('stats')['active_users'];

        // 啟用使用者數應該減少 1
        $this->assertEquals($initialActiveCount - 1, $newActiveCount);
    }

    /**
     * 測試統計快取機制
     */
    public function test_stats_caching_works()
    {
        $this->actingAs($this->admin);

        // 建立一些測試資料
        User::factory()->count(5)->create(['is_active' => true]);

        // 第一次載入統計
        $component1 = Livewire::test(UserStats::class);
        $stats1 = $component1->get('stats');

        // 第二次載入統計（應該使用快取）
        $component2 = Livewire::test(UserStats::class);
        $stats2 = $component2->get('stats');

        // 統計資料應該相同
        $this->assertEquals($stats1['total_users'], $stats2['total_users']);
        $this->assertEquals($stats1['active_users'], $stats2['active_users']);
    }

    /**
     * 測試統計資料的準確性
     */
    public function test_stats_accuracy()
    {
        $this->actingAs($this->admin);

        // 建立測試資料
        $activeUsers = User::factory()->count(8)->create(['is_active' => true]);
        $inactiveUsers = User::factory()->count(3)->create(['is_active' => false]);
        
        // 為一些使用者分配角色
        $role = Role::factory()->create(['name' => 'test_role']);
        $activeUsers->take(5)->each(function ($user) use ($role) {
            $user->roles()->attach($role);
        });

        $component = Livewire::test(UserStats::class);
        $stats = $component->get('stats');

        // 檢查統計資料準確性（包含 admin 使用者）
        $this->assertEquals(12, $stats['total_users']); // 8 + 3 + 1 (admin)
        $this->assertEquals(9, $stats['active_users']); // 8 + 1 (admin)
        $this->assertEquals(3, $stats['inactive_users']);
        
        // 檢查活躍率計算
        $expectedActivityRate = round((9 / 12) * 100, 2);
        $this->assertEquals($expectedActivityRate, $stats['activity_rate']);
    }
}