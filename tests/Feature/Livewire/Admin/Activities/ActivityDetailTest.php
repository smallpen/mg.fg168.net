<?php

namespace Tests\Feature\Livewire\Admin\Activities;

use App\Livewire\Admin\Activities\ActivityDetail;
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
 * ActivityDetail 元件功能測試
 * 
 * 測試活動詳情元件的所有功能，包括：
 * - 活動詳情顯示
 * - 原始資料切換
 * - 活動標記功能
 * - 註記添加功能
 * - 匯出功能
 * - 導航功能
 * - 安全性檢查
 * - 資料格式化
 */
class ActivityDetailTest extends TestCase
{
    use RefreshDatabase, DisablesPermissionSecurity;

    protected User $adminUser;
    protected User $regularUser;
    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立角色和權限
        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理員',
            'description' => '系統管理員',
            'is_system' => true,
        ]);

        $permissions = [
            'activity_logs.view' => ['檢視活動日誌', 'activity_logs'],
            'activity_logs.export' => ['匯出活動日誌', 'activity_logs'],
        ];

        foreach ($permissions as $name => [$displayName, $module]) {
            $permission = Permission::create([
                'name' => $name,
                'display_name' => $displayName,
                'description' => $displayName,
                'module' => $module,
            ]);

            $this->adminRole->permissions()->attach($permission);
        }
        
        // 建立測試使用者
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

        $this->adminUser->roles()->attach($this->adminRole);
        
        $this->actingAs($this->adminUser);
    }

    /** @test */
    public function it_can_render_activity_detail_component()
    {
        $component = Livewire::test(ActivityDetail::class);
        
        $component->assertStatus(200);
    }

    /** @test */
    public function it_can_load_activity_details()
    {
        // 建立測試活動記錄
        $activity = Activity::create([
            'type' => 'user_login',
            'description' => '使用者登入系統',
            'module' => 'auth',
            'user_id' => $this->user->id,
            'properties' => [
                'login_method' => 'username_password',
                'success' => true,
            ],
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 Test Browser',
            'result' => 'success',
            'risk_level' => 2,
        ]);

        $component = Livewire::test(ActivityDetail::class);
        
        $component->call('loadActivity', $activity->id);
        
        $component->assertSet('activity.id', $activity->id)
                  ->assertSet('activity.type', 'user_login')
                  ->assertSet('activity.description', '使用者登入系統')
                  ->assertSet('showModal', true);
    }

    /** @test */
    public function it_can_toggle_raw_data_display()
    {
        $activity = Activity::create([
            'type' => 'user_login',
            'description' => '使用者登入系統',
            'user_id' => $this->user->id,
            'properties' => ['test' => 'data'],
            'result' => 'success',
            'risk_level' => 2,
        ]);

        $component = Livewire::test(ActivityDetail::class);
        $component->call('loadActivity', $activity->id);
        
        $component->assertSet('showRawData', false);
        
        $component->call('toggleRawData');
        $component->assertSet('showRawData', true);
        
        $component->call('toggleRawData');
        $component->assertSet('showRawData', false);
    }

    /** @test */
    public function it_can_flag_activity_as_suspicious()
    {
        $activity = Activity::create([
            'type' => 'user_login',
            'description' => '使用者登入系統',
            'user_id' => $this->user->id,
            'result' => 'success',
            'risk_level' => 3,
        ]);

        $component = Livewire::test(ActivityDetail::class);
        $component->call('loadActivity', $activity->id);
        
        $component->assertSet('isSuspicious', false);
        
        $component->call('flagAsSuspicious');
        $component->assertSet('isSuspicious', true);
        
        // 檢查是否建立了標記記錄
        $this->assertDatabaseHas('activities', [
            'type' => 'activity_flagged',
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_can_add_note_to_activity()
    {
        $activity = Activity::create([
            'type' => 'user_login',
            'description' => '使用者登入系統',
            'user_id' => $this->user->id,
            'result' => 'success',
            'risk_level' => 2,
        ]);

        $component = Livewire::test(ActivityDetail::class);
        $component->call('loadActivity', $activity->id);
        
        $component->set('note', '這是一個測試註記');
        $component->call('addNote', '這是一個測試註記');
        
        // 檢查是否建立了註記記錄
        $this->assertDatabaseHas('activities', [
            'type' => 'activity_note_added',
            'user_id' => $this->user->id,
        ]);
        
        $component->assertSet('note', '')
                  ->assertSet('showNoteForm', false);
    }

    /** @test */
    public function it_can_export_activity_detail()
    {
        $activity = Activity::create([
            'type' => 'user_login',
            'description' => '使用者登入系統',
            'user_id' => $this->user->id,
            'properties' => ['test' => 'data'],
            'result' => 'success',
            'risk_level' => 2,
        ]);

        $component = Livewire::test(ActivityDetail::class);
        $component->call('loadActivity', $activity->id);
        
        $component->call('exportDetail');
        
        // 檢查是否建立了匯出記錄
        $this->assertDatabaseHas('activities', [
            'type' => 'export_activity_detail',
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_can_close_modal()
    {
        $activity = Activity::create([
            'type' => 'user_login',
            'description' => '使用者登入系統',
            'user_id' => $this->user->id,
            'result' => 'success',
            'risk_level' => 2,
        ]);

        $component = Livewire::test(ActivityDetail::class);
        $component->call('loadActivity', $activity->id);
        
        $component->assertSet('showModal', true);
        
        $component->call('closeModal');
        
        $component->assertSet('showModal', false)
                  ->assertSet('activity', null);
    }

    /** @test */
    public function it_calculates_security_risk_level_correctly()
    {
        $lowRiskActivity = Activity::create([
            'type' => 'user_login',
            'description' => '使用者登入系統',
            'user_id' => $this->user->id,
            'result' => 'success',
            'risk_level' => 2,
        ]);

        $highRiskActivity = Activity::create([
            'type' => 'login_failed',
            'description' => '登入失敗',
            'user_id' => $this->user->id,
            'result' => 'failed',
            'risk_level' => 8,
        ]);

        // 測試低風險活動
        $component = Livewire::test(ActivityDetail::class);
        $component->call('loadActivity', $lowRiskActivity->id);
        $component->assertSet('securityRiskLevel', 'low');

        // 測試高風險活動
        $component->call('loadActivity', $highRiskActivity->id);
        $component->assertSet('securityRiskLevel', 'high');
    }

    /** @test */
    public function it_formats_properties_correctly()
    {
        $activity = Activity::create([
            'type' => 'user_login',
            'description' => '使用者登入系統',
            'user_id' => $this->user->id,
            'properties' => [
                'username' => 'testuser',
                'login_method' => 'password',
                'remember_me' => true,
                'session_data' => ['key' => 'value'],
            ],
            'result' => 'success',
            'risk_level' => 2,
        ]);

        $component = Livewire::test(ActivityDetail::class);
        $component->call('loadActivity', $activity->id);
        
        $formattedData = $component->get('formattedData');
        
        $this->assertIsArray($formattedData);
        $this->assertNotEmpty($formattedData);
        
        // 檢查格式化的資料結構
        foreach ($formattedData as $item) {
            $this->assertArrayHasKey('key', $item);
            $this->assertArrayHasKey('value', $item);
            $this->assertArrayHasKey('raw_key', $item);
            $this->assertArrayHasKey('raw_value', $item);
            $this->assertArrayHasKey('type', $item);
        }
    }

    /** @test */
    public function it_handles_activity_not_found()
    {
        $component = Livewire::test(ActivityDetail::class);
        
        $component->call('loadActivity', 99999);
        
        $component->assertSet('activity', null);
    }

    /** @test */
    public function it_can_navigate_between_activities()
    {
        // 建立多個活動記錄
        $activity1 = Activity::create([
            'type' => 'user_login',
            'description' => '第一個活動',
            'user_id' => $this->user->id,
            'result' => 'success',
            'risk_level' => 2,
            'created_at' => now()->subMinutes(10),
        ]);

        $activity2 = Activity::create([
            'type' => 'user_logout',
            'description' => '第二個活動',
            'user_id' => $this->user->id,
            'result' => 'success',
            'risk_level' => 1,
            'created_at' => now()->subMinutes(5),
        ]);

        $activity3 = Activity::create([
            'type' => 'user_login',
            'description' => '第三個活動',
            'user_id' => $this->user->id,
            'result' => 'success',
            'risk_level' => 2,
            'created_at' => now(),
        ]);

        $component = Livewire::test(ActivityDetail::class);
        $component->call('loadActivity', $activity2->id);
        
        // 檢查導航 ID 是否正確設定
        $component->assertSet('previousActivityId', $activity1->id);
        $component->assertSet('nextActivityId', $activity3->id);
    }

    /** @test */
    public function it_can_navigate_to_previous_activity()
    {
        // 建立多個活動記錄
        $activity1 = Activity::create([
            'type' => 'user_login',
            'description' => '第一個活動',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
            'risk_level' => 2,
            'created_at' => now()->subMinutes(10),
        ]);

        $activity2 = Activity::create([
            'type' => 'user_logout',
            'description' => '第二個活動',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
            'risk_level' => 1,
            'created_at' => now()->subMinutes(5),
        ]);

        $component = Livewire::test(ActivityDetail::class);
        $component->call('loadActivity', $activity2->id);
        
        // 導航到上一個活動
        $component->call('navigateToPrevious');
        $component->assertSet('activity.id', $activity1->id);
    }

    /** @test */
    public function it_can_navigate_to_next_activity()
    {
        // 建立多個活動記錄
        $activity1 = Activity::create([
            'type' => 'user_login',
            'description' => '第一個活動',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
            'risk_level' => 2,
            'created_at' => now()->subMinutes(10),
        ]);

        $activity2 = Activity::create([
            'type' => 'user_logout',
            'description' => '第二個活動',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
            'risk_level' => 1,
            'created_at' => now()->subMinutes(5),
        ]);

        $component = Livewire::test(ActivityDetail::class);
        $component->call('loadActivity', $activity1->id);
        
        // 導航到下一個活動
        $component->call('navigateToNext');
        $component->assertSet('activity.id', $activity2->id);
    }

    /** @test */
    public function it_shows_related_activities()
    {
        $mainActivity = Activity::create([
            'type' => 'user_login',
            'description' => '主要活動',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
            'risk_level' => 2,
            'ip_address' => '192.168.1.100',
        ]);

        // 建立相關活動（同一使用者、同一 IP）
        Activity::create([
            'type' => 'view_dashboard',
            'description' => '相關活動',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
            'risk_level' => 1,
            'ip_address' => '192.168.1.100',
            'created_at' => now()->addMinutes(1),
        ]);

        $component = Livewire::test(ActivityDetail::class);
        $component->call('loadActivity', $mainActivity->id);
        
        $relatedActivities = $component->get('relatedActivities');
        $this->assertNotEmpty($relatedActivities);
    }

    /** @test */
    public function it_handles_sensitive_data_filtering()
    {
        $activity = Activity::create([
            'type' => 'user_login',
            'description' => '使用者登入系統',
            'user_id' => $this->adminUser->id,
            'properties' => [
                'username' => 'testuser',
                'password' => 'secret123', // 敏感資料
                'token' => 'abc123def456', // 敏感資料
                'login_method' => 'password',
            ],
            'result' => 'success',
            'risk_level' => 2,
        ]);

        $component = Livewire::test(ActivityDetail::class);
        $component->call('loadActivity', $activity->id);
        
        $formattedData = $component->get('formattedData');
        
        // 檢查敏感資料是否被過濾
        $passwordField = collect($formattedData)->firstWhere('raw_key', 'password');
        $tokenField = collect($formattedData)->firstWhere('raw_key', 'token');
        
        $this->assertEquals('[FILTERED]', $passwordField['value']);
        $this->assertEquals('[FILTERED]', $tokenField['value']);
    }

    /** @test */
    public function it_can_copy_activity_id()
    {
        $activity = Activity::create([
            'type' => 'user_login',
            'description' => '使用者登入系統',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
            'risk_level' => 2,
        ]);

        $component = Livewire::test(ActivityDetail::class);
        $component->call('loadActivity', $activity->id);
        
        $component->call('copyActivityId');
        
        // 檢查是否觸發了複製事件
        $component->assertDispatched('copy-to-clipboard', ['text' => $activity->id]);
    }

    /** @test */
    public function it_shows_activity_timeline()
    {
        $activity = Activity::create([
            'type' => 'user_login',
            'description' => '使用者登入系統',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
            'risk_level' => 2,
        ]);

        $component = Livewire::test(ActivityDetail::class);
        $component->call('loadActivity', $activity->id);
        
        $component->call('showTimeline');
        $component->assertSet('showTimeline', true);
    }

    /** @test */
    public function it_can_refresh_activity_data()
    {
        $activity = Activity::create([
            'type' => 'user_login',
            'description' => '使用者登入系統',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
            'risk_level' => 2,
        ]);

        $component = Livewire::test(ActivityDetail::class);
        $component->call('loadActivity', $activity->id);
        
        $component->call('refreshActivity');
        
        // 檢查活動資料是否重新載入
        $component->assertSet('activity.id', $activity->id);
    }

    /** @test */
    public function it_validates_note_input()
    {
        $activity = Activity::create([
            'type' => 'user_login',
            'description' => '使用者登入系統',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
            'risk_level' => 2,
        ]);

        $component = Livewire::test(ActivityDetail::class);
        $component->call('loadActivity', $activity->id);
        
        // 測試空白註記
        $component->call('addNote', '');
        $component->assertHasErrors(['note']);
        
        // 測試過長註記
        $longNote = str_repeat('a', 1001);
        $component->call('addNote', $longNote);
        $component->assertHasErrors(['note']);
    }

    /** @test */
    public function it_handles_activity_permissions()
    {
        // 測試沒有權限的使用者
        $this->actingAs($this->regularUser);

        $activity = Activity::create([
            'type' => 'user_login',
            'description' => '使用者登入系統',
            'user_id' => $this->regularUser->id,
            'result' => 'success',
            'risk_level' => 2,
        ]);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        $component = Livewire::test(ActivityDetail::class);
        $component->call('loadActivity', $activity->id);
    }

    /** @test */
    public function it_shows_correct_risk_level_indicators()
    {
        $activities = [
            ['risk_level' => 1, 'expected' => 'low'],
            ['risk_level' => 4, 'expected' => 'medium'],
            ['risk_level' => 7, 'expected' => 'high'],
            ['risk_level' => 10, 'expected' => 'critical'],
        ];

        foreach ($activities as $activityData) {
            $activity = Activity::create([
                'type' => 'user_login',
                'description' => '測試活動',
                'user_id' => $this->adminUser->id,
                'result' => 'success',
                'risk_level' => $activityData['risk_level'],
            ]);

            $component = Livewire::test(ActivityDetail::class);
            $component->call('loadActivity', $activity->id);
            
            $component->assertSet('securityRiskLevel', $activityData['expected']);
        }
    }

    /** @test */
    public function it_can_toggle_note_form()
    {
        $activity = Activity::create([
            'type' => 'user_login',
            'description' => '使用者登入系統',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
            'risk_level' => 2,
        ]);

        $component = Livewire::test(ActivityDetail::class);
        $component->call('loadActivity', $activity->id);
        
        $component->assertSet('showNoteForm', false);
        
        $component->call('toggleNoteForm');
        $component->assertSet('showNoteForm', true);
        
        $component->call('toggleNoteForm');
        $component->assertSet('showNoteForm', false);
    }
}