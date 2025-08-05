<?php

namespace Tests\Feature\Livewire\Admin\Dashboard;

use App\Http\Livewire\Admin\Dashboard\RecentActivity;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * RecentActivity 元件測試
 * 
 * 測試最近活動的顯示、篩選和權限控制
 */
class RecentActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試角色
        $this->adminRole = Role::factory()->create(['name' => 'admin']);
        $this->editorRole = Role::factory()->create(['name' => 'editor']);
        
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

        Livewire::test(RecentActivity::class)
            ->assertStatus(200)
            ->assertSee('最近活動')
            ->assertSee('系統動態');
    }

    /**
     * 測試最近活動列表顯示
     */
    public function test_recent_activities_display()
    {
        $this->actingAs($this->admin);

        // 建立測試活動記錄
        $activities = [
            [
                'type' => 'user_created',
                'description' => '建立了新使用者 John Doe',
                'user_id' => $this->admin->id,
                'created_at' => now()
            ],
            [
                'type' => 'role_updated',
                'description' => '更新了角色權限',
                'user_id' => $this->admin->id,
                'created_at' => now()->subMinutes(5)
            ]
        ];

        foreach ($activities as $activity) {
            \DB::table('activity_logs')->insert($activity);
        }

        Livewire::test(RecentActivity::class)
            ->assertSee('建立了新使用者 John Doe')
            ->assertSee('更新了角色權限')
            ->assertSee($this->admin->name);
    }

    /**
     * 測試活動類型篩選
     */
    public function test_activity_type_filter()
    {
        $this->actingAs($this->admin);

        // 建立不同類型的活動
        \DB::table('activity_logs')->insert([
            ['type' => 'user_created', 'description' => '建立使用者', 'user_id' => $this->admin->id, 'created_at' => now()],
            ['type' => 'role_updated', 'description' => '更新角色', 'user_id' => $this->admin->id, 'created_at' => now()],
            ['type' => 'login', 'description' => '使用者登入', 'user_id' => $this->admin->id, 'created_at' => now()]
        ]);

        Livewire::test(RecentActivity::class)
            ->set('activityFilter', 'user_created')
            ->assertSee('建立使用者')
            ->assertDontSee('更新角色')
            ->assertDontSee('使用者登入');
    }

    /**
     * 測試時間範圍篩選
     */
    public function test_time_range_filter()
    {
        $this->actingAs($this->admin);

        // 建立不同時間的活動
        \DB::table('activity_logs')->insert([
            ['type' => 'recent', 'description' => '最近活動', 'user_id' => $this->admin->id, 'created_at' => now()],
            ['type' => 'old', 'description' => '舊活動', 'user_id' => $this->admin->id, 'created_at' => now()->subDays(2)]
        ]);

        Livewire::test(RecentActivity::class)
            ->set('timeRange', 'today')
            ->assertSee('最近活動')
            ->assertDontSee('舊活動');
    }

    /**
     * 測試使用者篩選
     */
    public function test_user_filter()
    {
        $this->actingAs($this->admin);

        $otherUser = User::factory()->create();

        // 建立不同使用者的活動
        \DB::table('activity_logs')->insert([
            ['type' => 'action', 'description' => '管理員操作', 'user_id' => $this->admin->id, 'created_at' => now()],
            ['type' => 'action', 'description' => '其他使用者操作', 'user_id' => $otherUser->id, 'created_at' => now()]
        ]);

        Livewire::test(RecentActivity::class)
            ->set('userFilter', $this->admin->id)
            ->assertSee('管理員操作')
            ->assertDontSee('其他使用者操作');
    }

    /**
     * 測試活動詳情顯示
     */
    public function test_activity_details_display()
    {
        $this->actingAs($this->admin);

        $activityId = \DB::table('activity_logs')->insertGetId([
            'type' => 'user_created',
            'description' => '建立了新使用者',
            'user_id' => $this->admin->id,
            'metadata' => json_encode(['user_name' => 'John Doe', 'email' => 'john@example.com']),
            'created_at' => now()
        ]);

        Livewire::test(RecentActivity::class)
            ->call('showActivityDetails', $activityId)
            ->assertSee('活動詳情')
            ->assertSee('John Doe')
            ->assertSee('john@example.com');
    }

    /**
     * 測試權限控制 - 無權限使用者
     */
    public function test_unauthorized_access()
    {
        $unauthorizedUser = User::factory()->create();
        $this->actingAs($unauthorizedUser);

        Livewire::test(RecentActivity::class)
            ->assertForbidden();
    }

    /**
     * 測試權限控制 - 限制檢視權限
     */
    public function test_limited_view_permission()
    {
        $limitedUser = User::factory()->create();
        $limitedRole = Role::factory()->create(['name' => 'limited_viewer']);
        $limitedUser->roles()->attach($limitedRole);
        
        $this->actingAs($limitedUser);

        // 建立敏感活動記錄
        \DB::table('activity_logs')->insert([
            ['type' => 'system_config', 'description' => '修改系統設定', 'user_id' => $this->admin->id, 'created_at' => now()],
            ['type' => 'user_created', 'description' => '建立使用者', 'user_id' => $this->admin->id, 'created_at' => now()]
        ]);

        Livewire::test(RecentActivity::class)
            ->assertSee('建立使用者')
            ->assertDontSee('修改系統設定'); // 沒有系統設定檢視權限
    }

    /**
     * 測試分頁功能
     */
    public function test_pagination()
    {
        $this->actingAs($this->admin);

        // 建立大量活動記錄
        for ($i = 0; $i < 25; $i++) {
            \DB::table('activity_logs')->insert([
                'type' => 'test',
                'description' => "測試活動 {$i}",
                'user_id' => $this->admin->id,
                'created_at' => now()->subMinutes($i)
            ]);
        }

        $component = Livewire::test(RecentActivity::class);
        
        // 檢查分頁連結存在
        $component->assertSee('下一頁');
        
        // 測試換頁
        $component->call('gotoPage', 2)
            ->assertSet('page', 2);
    }

    /**
     * 測試即時更新功能
     */
    public function test_real_time_updates()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(RecentActivity::class);

        // 建立新活動
        \DB::table('activity_logs')->insert([
            'type' => 'new_activity',
            'description' => '新活動',
            'user_id' => $this->admin->id,
            'created_at' => now()
        ]);

        $component->call('refreshActivities')
            ->assertSee('新活動')
            ->assertDispatched('activities-refreshed');
    }

    /**
     * 測試活動搜尋功能
     */
    public function test_activity_search()
    {
        $this->actingAs($this->admin);

        \DB::table('activity_logs')->insert([
            ['type' => 'test', 'description' => '建立了新使用者 John', 'user_id' => $this->admin->id, 'created_at' => now()],
            ['type' => 'test', 'description' => '更新了角色設定', 'user_id' => $this->admin->id, 'created_at' => now()]
        ]);

        Livewire::test(RecentActivity::class)
            ->set('searchTerm', 'John')
            ->assertSee('建立了新使用者 John')
            ->assertDontSee('更新了角色設定');
    }

    /**
     * 測試活動匯出功能
     */
    public function test_activity_export()
    {
        $this->actingAs($this->admin);

        \DB::table('activity_logs')->insert([
            ['type' => 'test', 'description' => '測試活動', 'user_id' => $this->admin->id, 'created_at' => now()]
        ]);

        Livewire::test(RecentActivity::class)
            ->call('exportActivities')
            ->assertDispatched('export-started');
    }

    /**
     * 測試活動統計顯示
     */
    public function test_activity_statistics()
    {
        $this->actingAs($this->admin);

        // 建立不同類型的活動
        \DB::table('activity_logs')->insert([
            ['type' => 'user_created', 'description' => '建立使用者1', 'user_id' => $this->admin->id, 'created_at' => now()],
            ['type' => 'user_created', 'description' => '建立使用者2', 'user_id' => $this->admin->id, 'created_at' => now()],
            ['type' => 'role_updated', 'description' => '更新角色', 'user_id' => $this->admin->id, 'created_at' => now()]
        ]);

        Livewire::test(RecentActivity::class)
            ->call('showStatistics')
            ->assertSee('活動統計')
            ->assertSee('使用者建立: 2')
            ->assertSee('角色更新: 1');
    }

    /**
     * 測試活動清理功能
     */
    public function test_activity_cleanup()
    {
        $this->actingAs($this->admin);

        // 建立舊活動記錄
        \DB::table('activity_logs')->insert([
            ['type' => 'old', 'description' => '舊活動', 'user_id' => $this->admin->id, 'created_at' => now()->subDays(90)]
        ]);

        Livewire::test(RecentActivity::class)
            ->call('cleanupOldActivities')
            ->assertDispatched('cleanup-completed')
            ->assertSee('已清理舊活動記錄');
    }

    /**
     * 測試活動圖示顯示
     */
    public function test_activity_icons()
    {
        $this->actingAs($this->admin);

        \DB::table('activity_logs')->insert([
            ['type' => 'user_created', 'description' => '建立使用者', 'user_id' => $this->admin->id, 'created_at' => now()],
            ['type' => 'role_updated', 'description' => '更新角色', 'user_id' => $this->admin->id, 'created_at' => now()],
            ['type' => 'login', 'description' => '使用者登入', 'user_id' => $this->admin->id, 'created_at' => now()]
        ]);

        Livewire::test(RecentActivity::class)
            ->assertSee('user-plus') // 使用者建立圖示
            ->assertSee('shield-check') // 角色更新圖示
            ->assertSee('login'); // 登入圖示
    }

    /**
     * 測試活動時間顯示格式
     */
    public function test_activity_time_format()
    {
        $this->actingAs($this->admin);

        \DB::table('activity_logs')->insert([
            ['type' => 'test', 'description' => '剛才的活動', 'user_id' => $this->admin->id, 'created_at' => now()],
            ['type' => 'test', 'description' => '一小時前的活動', 'user_id' => $this->admin->id, 'created_at' => now()->subHour()],
            ['type' => 'test', 'description' => '昨天的活動', 'user_id' => $this->admin->id, 'created_at' => now()->subDay()]
        ]);

        Livewire::test(RecentActivity::class)
            ->assertSee('剛才')
            ->assertSee('1 小時前')
            ->assertSee('1 天前');
    }
}