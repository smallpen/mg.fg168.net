<?php

namespace Tests\Feature\Livewire\Admin\Activities;

use App\Livewire\Admin\Activities\ActivityList;
use App\Models\Activity;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * ActivityList 元件測試
 */
class ActivityListTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立管理員角色
        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理員',
            'description' => '系統管理員',
            'is_system' => true,
        ]);

        // 建立管理員使用者
        $this->adminUser = User::create([
            'username' => 'admin',
            'name' => '系統管理員',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        // 指派角色
        $this->adminUser->roles()->attach($this->adminRole);

        // 建立權限
        $permissions = [
            'system.logs' => '系統記錄檢視',
        ];

        foreach ($permissions as $name => $displayName) {
            $permission = \App\Models\Permission::create([
                'name' => $name,
                'display_name' => $displayName,
                'description' => $displayName,
                'module' => 'system',
            ]);

            $this->adminRole->permissions()->attach($permission);
        }

        // 登入管理員
        $this->actingAs($this->adminUser);
    }

    /** @test */
    public function it_can_render_activity_list_component()
    {
        Livewire::test(ActivityList::class)
            ->assertStatus(200)
            ->assertSee('活動記錄')
            ->assertSee('總記錄數');
    }

    /** @test */
    public function it_displays_activities_with_pagination()
    {
        // 建立測試活動記錄
        Activity::factory()->count(60)->create([
            'user_id' => $this->adminUser->id,
        ]);

        Livewire::test(ActivityList::class)
            ->assertStatus(200)
            ->assertSee('顯示')
            ->assertSee('筆記錄');
    }

    /** @test */
    public function it_can_search_activities()
    {
        // 建立特定的活動記錄
        Activity::create([
            'type' => Activity::TYPE_LOGIN,
            'description' => '管理員登入系統',
            'module' => Activity::MODULE_AUTH,
            'user_id' => $this->adminUser->id,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Test Browser',
            'result' => 'success',
            'risk_level' => 1,
        ]);

        Activity::create([
            'type' => Activity::TYPE_CREATE_USER,
            'description' => '建立新使用者',
            'module' => Activity::MODULE_USERS,
            'user_id' => $this->adminUser->id,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Test Browser',
            'result' => 'success',
            'risk_level' => 2,
        ]);

        Livewire::test(ActivityList::class)
            ->set('search', '登入')
            ->assertSee('管理員登入系統')
            ->assertDontSee('建立新使用者');
    }

    /** @test */
    public function it_can_filter_by_user()
    {
        // 建立另一個使用者
        $otherUser = User::create([
            'username' => 'testuser',
            'name' => '測試使用者',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        // 建立不同使用者的活動記錄
        Activity::create([
            'type' => Activity::TYPE_LOGIN,
            'description' => '管理員登入',
            'user_id' => $this->adminUser->id,
            'ip_address' => '192.168.1.1',
            'result' => 'success',
        ]);

        Activity::create([
            'type' => Activity::TYPE_LOGIN,
            'description' => '測試使用者登入',
            'user_id' => $otherUser->id,
            'ip_address' => '192.168.1.2',
            'result' => 'success',
        ]);

        Livewire::test(ActivityList::class)
            ->set('userFilter', $this->adminUser->id)
            ->assertSee('管理員登入')
            ->assertDontSee('測試使用者登入');
    }

    /** @test */
    public function it_can_filter_by_date_range()
    {
        // 建立不同日期的活動記錄
        Activity::create([
            'type' => Activity::TYPE_LOGIN,
            'description' => '今日登入',
            'user_id' => $this->adminUser->id,
            'created_at' => now(),
            'ip_address' => '192.168.1.1',
            'result' => 'success',
        ]);

        Activity::create([
            'type' => Activity::TYPE_LOGIN,
            'description' => '昨日登入',
            'user_id' => $this->adminUser->id,
            'created_at' => now()->subDay(),
            'ip_address' => '192.168.1.1',
            'result' => 'success',
        ]);

        Livewire::test(ActivityList::class)
            ->set('dateFrom', now()->format('Y-m-d'))
            ->set('dateTo', now()->format('Y-m-d'))
            ->assertSee('今日登入')
            ->assertDontSee('昨日登入');
    }

    /** @test */
    public function it_can_toggle_real_time_mode()
    {
        Livewire::test(ActivityList::class)
            ->assertSet('realTimeMode', false)
            ->call('toggleRealTime')
            ->assertSet('realTimeMode', true)
            ->call('toggleRealTime')
            ->assertSet('realTimeMode', false);
    }

    /** @test */
    public function it_can_clear_filters()
    {
        Livewire::test(ActivityList::class)
            ->set('search', 'test')
            ->set('userFilter', '1')
            ->set('typeFilter', 'login')
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('userFilter', '')
            ->assertSet('typeFilter', '');
    }

    /** @test */
    public function it_can_sort_activities()
    {
        Livewire::test(ActivityList::class)
            ->assertSet('sortField', 'created_at')
            ->assertSet('sortDirection', 'desc')
            ->call('sortBy', 'type')
            ->assertSet('sortField', 'type')
            ->assertSet('sortDirection', 'asc')
            ->call('sortBy', 'type')
            ->assertSet('sortDirection', 'desc');
    }

    /** @test */
    public function it_shows_correct_statistics()
    {
        // 建立不同類型的活動記錄
        Activity::create([
            'type' => Activity::TYPE_LOGIN,
            'description' => '成功登入',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
            'risk_level' => 1,
            'ip_address' => '192.168.1.1',
        ]);

        Activity::create([
            'type' => 'login_failed',
            'description' => '登入失敗',
            'user_id' => $this->adminUser->id,
            'result' => 'failed',
            'risk_level' => 8,
            'ip_address' => '192.168.1.1',
        ]);

        $component = Livewire::test(ActivityList::class);
        $stats = $component->get('stats');

        $this->assertEquals(2, $stats['total']);
        $this->assertEquals(1, $stats['failed']);
        $this->assertEquals(1, $stats['high_risk']);
    }

    /** @test */
    public function it_requires_proper_permissions()
    {
        // 建立沒有權限的使用者
        $unauthorizedUser = User::create([
            'username' => 'unauthorized',
            'name' => '無權限使用者',
            'email' => 'unauthorized@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->actingAs($unauthorizedUser);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        Livewire::test(ActivityList::class);
    }
}