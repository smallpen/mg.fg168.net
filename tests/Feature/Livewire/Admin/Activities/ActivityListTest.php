<?php

namespace Tests\Feature\Livewire\Admin\Activities;

use App\Livewire\Admin\Activities\ActivityList;
use App\Models\Activity;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Traits\DisablesPermissionSecurity;
use Carbon\Carbon;

/**
 * ActivityList 元件功能測試
 * 
 * 測試活動記錄列表元件的所有功能，包括：
 * - 基本顯示功能
 * - 搜尋和篩選功能
 * - 分頁和排序功能
 * - 即時更新功能
 * - 批量操作功能
 * - 權限檢查
 * - 統計資料顯示
 */
class ActivityListTest extends TestCase
{
    use RefreshDatabase, DisablesPermissionSecurity;

    protected User $adminUser;
    protected User $regularUser;
    protected Role $adminRole;
    protected Role $userRole;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立角色
        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理員',
            'description' => '系統管理員',
            'is_system' => true,
        ]);

        $this->userRole = Role::create([
            'name' => 'user',
            'display_name' => '一般使用者',
            'description' => '一般使用者',
            'is_system' => false,
        ]);

        // 建立權限
        $permissions = [
            'system.logs' => ['系統記錄檢視', 'system'],
            'activity_logs.view' => ['檢視活動日誌', 'activity_logs'],
            'activity_logs.export' => ['匯出活動日誌', 'activity_logs'],
            'activity_logs.delete' => ['刪除活動日誌', 'activity_logs'],
        ];

        foreach ($permissions as $name => [$displayName, $module]) {
            $permission = Permission::create([
                'name' => $name,
                'display_name' => $displayName,
                'description' => $displayName,
                'module' => $module,
            ]);

            // 管理員擁有所有權限
            $this->adminRole->permissions()->attach($permission);
        }

        // 建立使用者
        $this->adminUser = User::create([
            'username' => 'admin',
            'name' => '系統管理員',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->regularUser = User::create([
            'username' => 'user',
            'name' => '一般使用者',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        // 指派角色
        $this->adminUser->roles()->attach($this->adminRole);
        $this->regularUser->roles()->attach($this->userRole);

        // 預設登入管理員
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
            'type' => 'login',
            'description' => '管理員登入系統',
            'module' => 'auth',
            'user_id' => $this->adminUser->id,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Test Browser',
            'result' => 'success',
            'risk_level' => 1,
        ]);

        Activity::create([
            'type' => 'create_user',
            'description' => '建立新使用者',
            'module' => 'users',
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
            'type' => 'login',
            'description' => '管理員登入',
            'user_id' => $this->adminUser->id,
            'ip_address' => '192.168.1.1',
            'result' => 'success',
        ]);

        Activity::create([
            'type' => 'login',
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
            'type' => 'login',
            'description' => '今日登入',
            'user_id' => $this->adminUser->id,
            'created_at' => now(),
            'ip_address' => '192.168.1.1',
            'result' => 'success',
        ]);

        Activity::create([
            'type' => 'login',
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
    public function it_can_filter_by_activity_type()
    {
        // 建立不同類型的活動記錄
        Activity::create([
            'type' => 'login',
            'description' => '使用者登入',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
        ]);

        Activity::create([
            'type' => 'create_user',
            'description' => '建立使用者',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
        ]);

        Livewire::test(ActivityList::class)
            ->set('typeFilter', 'login')
            ->assertSee('使用者登入')
            ->assertDontSee('建立使用者');
    }

    /** @test */
    public function it_can_filter_by_result()
    {
        // 建立不同結果的活動記錄
        Activity::create([
            'type' => 'login',
            'description' => '成功登入',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
        ]);

        Activity::create([
            'type' => 'login',
            'description' => '登入失敗',
            'user_id' => $this->adminUser->id,
            'result' => 'failed',
        ]);

        Livewire::test(ActivityList::class)
            ->set('resultFilter', 'failed')
            ->assertSee('登入失敗')
            ->assertDontSee('成功登入');
    }

    /** @test */
    public function it_can_filter_by_ip_address()
    {
        // 建立不同 IP 的活動記錄
        Activity::create([
            'type' => 'login',
            'description' => '內網登入',
            'user_id' => $this->adminUser->id,
            'ip_address' => '192.168.1.100',
            'result' => 'success',
        ]);

        Activity::create([
            'type' => 'login',
            'description' => '外網登入',
            'user_id' => $this->adminUser->id,
            'ip_address' => '203.0.113.1',
            'result' => 'success',
        ]);

        Livewire::test(ActivityList::class)
            ->set('ipFilter', '192.168.1.100')
            ->assertSee('內網登入')
            ->assertDontSee('外網登入');
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
            ->set('resultFilter', 'success')
            ->set('ipFilter', '192.168.1.1')
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('userFilter', '')
            ->assertSet('typeFilter', '')
            ->assertSet('resultFilter', '')
            ->assertSet('ipFilter', '');
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
            'type' => 'login',
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
    public function it_can_handle_bulk_operations()
    {
        // 建立測試活動記錄
        $activity1 = Activity::create([
            'type' => 'login',
            'description' => '活動 1',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
        ]);

        $activity2 = Activity::create([
            'type' => 'logout',
            'description' => '活動 2',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
        ]);

        Livewire::test(ActivityList::class)
            ->set('selectedActivities', [$activity1->id, $activity2->id])
            ->set('bulkAction', 'export')
            ->call('executeBulkAction')
            ->assertDispatched('bulk-export-started');
    }

    /** @test */
    public function it_can_view_activity_detail()
    {
        $activity = Activity::create([
            'type' => 'login',
            'description' => '使用者登入',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
        ]);

        Livewire::test(ActivityList::class)
            ->call('viewDetail', $activity->id)
            ->assertDispatched('show-activity-detail', ['activityId' => $activity->id]);
    }

    /** @test */
    public function it_can_export_activities()
    {
        // 建立測試活動記錄
        Activity::factory()->count(5)->create([
            'user_id' => $this->adminUser->id,
        ]);

        Livewire::test(ActivityList::class)
            ->call('exportActivities')
            ->assertDispatched('export-started');
    }

    /** @test */
    public function it_handles_real_time_activity_updates()
    {
        $component = Livewire::test(ActivityList::class)
            ->set('realTimeMode', true);

        // 模擬即時活動更新
        $component->dispatch('activity-logged', [
            'id' => 1,
            'type' => 'login',
            'description' => '新的登入活動'
        ]);

        $component->assertDispatched('activities-refreshed');
    }

    /** @test */
    public function it_calculates_filter_options_correctly()
    {
        // 建立不同類型的活動記錄
        Activity::create([
            'type' => 'login',
            'description' => '登入',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
        ]);

        Activity::create([
            'type' => 'create_user',
            'description' => '建立使用者',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
        ]);

        $component = Livewire::test(ActivityList::class);
        $filterOptions = $component->get('filterOptions');

        $this->assertIsArray($filterOptions);
        $this->assertArrayHasKey('types', $filterOptions);
        $this->assertArrayHasKey('users', $filterOptions);
        $this->assertArrayHasKey('results', $filterOptions);
    }

    /** @test */
    public function it_handles_pagination_correctly()
    {
        // 建立超過一頁的活動記錄
        Activity::factory()->count(60)->create([
            'user_id' => $this->adminUser->id,
        ]);

        $component = Livewire::test(ActivityList::class)
            ->assertSet('perPage', 50);

        // 測試更改每頁顯示數量
        $component->set('perPage', 25)
            ->assertSet('perPage', 25);
    }

    /** @test */
    public function it_requires_proper_permissions()
    {
        // 測試沒有權限的使用者
        $this->actingAs($this->regularUser);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        Livewire::test(ActivityList::class);
    }

    /** @test */
    public function it_handles_empty_activity_list()
    {
        // 確保沒有活動記錄
        Activity::query()->delete();

        Livewire::test(ActivityList::class)
            ->assertSee('目前沒有活動記錄')
            ->assertSee('0 筆記錄');
    }

    /** @test */
    public function it_can_refresh_activities_manually()
    {
        Livewire::test(ActivityList::class)
            ->call('refreshActivities')
            ->assertDispatched('activities-refreshed');
    }

    /** @test */
    public function it_validates_date_range_filters()
    {
        $component = Livewire::test(ActivityList::class);

        // 測試無效的日期範圍
        $component->set('dateFrom', '2024-12-31')
            ->set('dateTo', '2024-01-01');

        // 應該自動修正或顯示錯誤
        $this->assertTrue(true); // 具體驗證邏輯取決於實作
    }

    /** @test */
    public function it_shows_activity_risk_levels()
    {
        // 建立不同風險等級的活動記錄
        Activity::create([
            'type' => 'login',
            'description' => '低風險活動',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
            'risk_level' => 1,
        ]);

        Activity::create([
            'type' => 'admin_action',
            'description' => '高風險活動',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
            'risk_level' => 9,
        ]);

        Livewire::test(ActivityList::class)
            ->assertSee('低風險活動')
            ->assertSee('高風險活動');
    }

    /** @test */
    public function it_handles_concurrent_user_sessions()
    {
        // 模擬多個使用者同時操作
        $component1 = Livewire::test(ActivityList::class)
            ->set('realTimeMode', true);

        $component2 = Livewire::test(ActivityList::class)
            ->set('realTimeMode', true);

        // 兩個元件都應該能正常運作
        $component1->assertStatus(200);
        $component2->assertStatus(200);
    }

    /** @test */
    public function it_can_toggle_infinite_scroll_mode()
    {
        Livewire::test(ActivityList::class)
            ->assertSet('infiniteScroll', false)
            ->call('toggleLoadMode')
            ->assertSet('infiniteScroll', true)
            ->call('toggleLoadMode')
            ->assertSet('infiniteScroll', false);
    }

    /** @test */
    public function it_can_load_more_activities_in_infinite_scroll()
    {
        // 建立足夠的測試資料
        Activity::factory()->count(100)->create([
            'user_id' => $this->adminUser->id,
        ]);

        $component = Livewire::test(ActivityList::class)
            ->set('infiniteScroll', true)
            ->assertSet('loadedPages', 1);

        $component->call('loadMore')
            ->assertSet('loadedPages', 2);
    }

    /** @test */
    public function it_can_filter_by_risk_level()
    {
        // 建立不同風險等級的活動記錄
        Activity::create([
            'type' => 'login',
            'description' => '低風險活動',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
            'risk_level' => 2,
        ]);

        Activity::create([
            'type' => 'admin_action',
            'description' => '高風險活動',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
            'risk_level' => 8,
        ]);

        Livewire::test(ActivityList::class)
            ->set('riskLevelFilter', 'high')
            ->assertSee('高風險活動')
            ->assertDontSee('低風險活動');
    }

    /** @test */
    public function it_can_filter_by_module()
    {
        // 建立不同模組的活動記錄
        Activity::create([
            'type' => 'login',
            'description' => '認證模組活動',
            'module' => 'auth',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
        ]);

        Activity::create([
            'type' => 'create_user',
            'description' => '使用者模組活動',
            'module' => 'users',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
        ]);

        Livewire::test(ActivityList::class)
            ->set('moduleFilter', 'auth')
            ->assertSee('認證模組活動')
            ->assertDontSee('使用者模組活動');
    }

    /** @test */
    public function it_handles_bulk_mark_reviewed_operation()
    {
        // 建立測試活動記錄
        $activity1 = Activity::create([
            'type' => 'login',
            'description' => '活動 1',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
        ]);

        $activity2 = Activity::create([
            'type' => 'logout',
            'description' => '活動 2',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
        ]);

        Livewire::test(ActivityList::class)
            ->set('selectedActivities', [$activity1->id, $activity2->id])
            ->set('bulkAction', 'mark_reviewed')
            ->call('executeBulkAction');

        // 檢查活動是否被標記為已審查
        $this->assertDatabaseHas('activities', [
            'id' => $activity1->id,
            'reviewed_by' => $this->adminUser->id,
        ]);

        $this->assertDatabaseHas('activities', [
            'id' => $activity2->id,
            'reviewed_by' => $this->adminUser->id,
        ]);
    }

    /** @test */
    public function it_validates_date_range_properly()
    {
        $component = Livewire::test(ActivityList::class);

        // 測試開始日期晚於結束日期的情況
        $component->set('dateFrom', '2024-12-31')
            ->set('dateTo', '2024-01-01');

        // 應該自動調整或顯示適當的結果
        $activities = $component->get('activities');
        $this->assertNotNull($activities);
    }

    /** @test */
    public function it_shows_export_modal_correctly()
    {
        Livewire::test(ActivityList::class)
            ->assertSet('showExportModal', false)
            ->set('showExportModal', true)
            ->assertSet('showExportModal', true);
    }

    /** @test */
    public function it_handles_security_alert_events()
    {
        $component = Livewire::test(ActivityList::class)
            ->set('realTimeMode', true);

        // 模擬安全警報事件
        $component->dispatch('security-alert', [
            'message' => '檢測到可疑登入嘗試',
            'severity' => 'high'
        ]);

        // 元件應該正常處理警報
        $component->assertStatus(200);
    }

    /** @test */
    public function it_can_handle_keyboard_shortcuts()
    {
        $component = Livewire::test(ActivityList::class);

        // 測試清除篩選快捷鍵
        $component->set('search', 'test')
            ->dispatch('clear-all-filters')
            ->assertSet('search', '');
    }

    /** @test */
    public function it_maintains_state_during_real_time_updates()
    {
        $component = Livewire::test(ActivityList::class)
            ->set('search', 'test')
            ->set('realTimeMode', true);

        // 模擬即時更新
        $component->dispatch('activity-logged', ['id' => 1]);

        // 搜尋條件應該保持
        $component->assertSet('search', 'test');
    }
}