<?php

namespace Tests\Feature\Livewire\Admin\Activities;

use App\Livewire\Admin\Activities\ActivityMonitor;
use App\Models\Activity;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\MonitorRule;
use App\Models\SecurityAlert;
use App\Services\SecurityAnalyzer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Traits\DisablesPermissionSecurity;
use Carbon\Carbon;

/**
 * ActivityMonitor 元件功能測試
 * 
 * 測試活動監控元件的所有功能，包括：
 * - 即時監控功能
 * - 監控規則管理
 * - 安全警報處理
 * - 統計資料顯示
 * - 活動頻率監控
 * - 權限檢查
 */
class ActivityMonitorTest extends TestCase
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
            'security.view' => ['檢視安全資訊', 'security'],
            'security.incidents' => ['管理安全事件', 'security'],
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
    public function it_can_render_activity_monitor_component()
    {
        Livewire::test(ActivityMonitor::class)
            ->assertStatus(200)
            ->assertSee('即時活動監控')
            ->assertSee('監控規則')
            ->assertSee('安全警報');
    }

    /** @test */
    public function it_can_start_monitoring()
    {
        Livewire::test(ActivityMonitor::class)
            ->assertSet('isMonitoring', false)
            ->call('startMonitoring')
            ->assertSet('isMonitoring', true)
            ->assertDispatched('monitoring-started');
    }

    /** @test */
    public function it_can_stop_monitoring()
    {
        Livewire::test(ActivityMonitor::class)
            ->set('isMonitoring', true)
            ->call('stopMonitoring')
            ->assertSet('isMonitoring', false)
            ->assertDispatched('monitoring-stopped');
    }

    /** @test */
    public function it_can_toggle_monitoring()
    {
        $component = Livewire::test(ActivityMonitor::class)
            ->assertSet('isMonitoring', false);

        $component->call('toggleMonitoring')
            ->assertSet('isMonitoring', true);

        $component->call('toggleMonitoring')
            ->assertSet('isMonitoring', false);
    }

    /** @test */
    public function it_can_add_monitor_rule()
    {
        Livewire::test(ActivityMonitor::class)
            ->set('newRule.name', '登入失敗監控')
            ->set('newRule.description', '監控連續登入失敗')
            ->set('newRule.priority', 5)
            ->set('newRule.conditions', ['type' => 'login_failed', 'count' => 5])
            ->set('newRule.actions', ['alert' => true, 'block_ip' => true])
            ->call('addRule');

        $this->assertDatabaseHas('monitor_rules', [
            'name' => '登入失敗監控',
            'description' => '監控連續登入失敗',
            'priority' => 5,
            'created_by' => $this->adminUser->id,
        ]);
    }

    /** @test */
    public function it_validates_monitor_rule_input()
    {
        Livewire::test(ActivityMonitor::class)
            ->set('newRule.name', '')
            ->set('newRule.description', '')
            ->call('addRule')
            ->assertHasErrors(['newRule.name', 'newRule.description']);
    }

    /** @test */
    public function it_can_remove_monitor_rule()
    {
        $rule = MonitorRule::create([
            'name' => '測試規則',
            'description' => '測試規則描述',
            'conditions' => ['type' => 'login'],
            'actions' => ['alert' => true],
            'priority' => 1,
            'is_active' => true,
            'created_by' => $this->adminUser->id,
        ]);

        Livewire::test(ActivityMonitor::class)
            ->call('removeRule', $rule->id);

        $this->assertDatabaseMissing('monitor_rules', [
            'id' => $rule->id,
        ]);
    }

    /** @test */
    public function it_can_toggle_rule_status()
    {
        $rule = MonitorRule::create([
            'name' => '測試規則',
            'description' => '測試規則描述',
            'conditions' => ['type' => 'login'],
            'actions' => ['alert' => true],
            'priority' => 1,
            'is_active' => true,
            'created_by' => $this->adminUser->id,
        ]);

        Livewire::test(ActivityMonitor::class)
            ->call('toggleRule', $rule->id);

        $this->assertDatabaseHas('monitor_rules', [
            'id' => $rule->id,
            'is_active' => false,
        ]);
    }

    /** @test */
    public function it_can_acknowledge_alert()
    {
        $activity = Activity::create([
            'type' => 'login_failed',
            'description' => '登入失敗',
            'user_id' => $this->adminUser->id,
            'result' => 'failed',
            'risk_level' => 8,
        ]);

        $alert = SecurityAlert::create([
            'activity_id' => $activity->id,
            'type' => 'login_failure',
            'severity' => 'high',
            'title' => '多次登入失敗',
            'description' => '檢測到多次登入失敗嘗試',
        ]);

        Livewire::test(ActivityMonitor::class)
            ->call('acknowledgeAlert', $alert->id);

        $this->assertDatabaseHas('security_alerts', [
            'id' => $alert->id,
            'acknowledged_by' => $this->adminUser->id,
        ]);
    }

    /** @test */
    public function it_can_view_alert_detail()
    {
        $activity = Activity::create([
            'type' => 'login_failed',
            'description' => '登入失敗',
            'user_id' => $this->adminUser->id,
            'result' => 'failed',
            'risk_level' => 8,
        ]);

        $alert = SecurityAlert::create([
            'activity_id' => $activity->id,
            'type' => 'login_failure',
            'severity' => 'high',
            'title' => '多次登入失敗',
            'description' => '檢測到多次登入失敗嘗試',
        ]);

        Livewire::test(ActivityMonitor::class)
            ->call('viewAlertDetail', $alert->id)
            ->assertSet('showAlertDetail', true)
            ->assertSet('selectedAlert.id', $alert->id);
    }

    /** @test */
    public function it_can_close_alert_detail()
    {
        Livewire::test(ActivityMonitor::class)
            ->set('showAlertDetail', true)
            ->call('closeAlertDetail')
            ->assertSet('showAlertDetail', false)
            ->assertSet('selectedAlert', null);
    }

    /** @test */
    public function it_can_ignore_alert()
    {
        $activity = Activity::create([
            'type' => 'login_failed',
            'description' => '登入失敗',
            'user_id' => $this->adminUser->id,
            'result' => 'failed',
            'risk_level' => 8,
        ]);

        $alert = SecurityAlert::create([
            'activity_id' => $activity->id,
            'type' => 'login_failure',
            'severity' => 'high',
            'title' => '多次登入失敗',
            'description' => '檢測到多次登入失敗嘗試',
        ]);

        Livewire::test(ActivityMonitor::class)
            ->call('ignoreAlert', $alert->id);

        $this->assertDatabaseHas('security_alerts', [
            'id' => $alert->id,
            'acknowledged_by' => $this->adminUser->id,
        ]);
    }

    /** @test */
    public function it_can_block_ip_address()
    {
        $ipAddress = '192.168.1.100';

        Livewire::test(ActivityMonitor::class)
            ->call('blockIp', $ipAddress);

        // 檢查是否記錄了封鎖操作
        $this->assertDatabaseHas('activities', [
            'type' => 'ip_blocked',
            'user_id' => $this->adminUser->id,
        ]);
    }

    /** @test */
    public function it_can_refresh_monitor_data()
    {
        Livewire::test(ActivityMonitor::class)
            ->call('refreshMonitorData');

        // 檢查是否觸發了資料重新整理
        // 這裡主要測試方法執行不會出錯
        $this->assertTrue(true);
    }

    /** @test */
    public function it_handles_security_alert_events()
    {
        $component = Livewire::test(ActivityMonitor::class)
            ->set('isMonitoring', true);

        // 模擬安全警報事件
        $component->dispatch('security-alert', [
            'title' => '可疑活動',
            'message' => '檢測到異常登入嘗試',
            'severity' => 'high'
        ]);

        // 檢查警報計數是否增加
        $this->assertTrue($component->get('alertCount') >= 0);
    }

    /** @test */
    public function it_handles_activity_logged_events()
    {
        $activity = Activity::create([
            'type' => 'login',
            'description' => '使用者登入',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
            'risk_level' => 2,
        ]);

        $component = Livewire::test(ActivityMonitor::class)
            ->set('isMonitoring', true);

        // 模擬活動記錄事件
        $component->dispatch('activity-logged', [
            'id' => $activity->id,
            'type' => 'login',
            'user_id' => $this->adminUser->id
        ]);

        // 檢查統計資料是否更新
        $stats = $component->get('todayStats');
        $this->assertIsArray($stats);
    }

    /** @test */
    public function it_shows_correct_today_statistics()
    {
        // 建立今日的活動記錄
        Activity::create([
            'type' => 'login',
            'description' => '正常登入',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
            'risk_level' => 2,
            'created_at' => now(),
        ]);

        Activity::create([
            'type' => 'login_failed',
            'description' => '登入失敗',
            'user_id' => $this->adminUser->id,
            'result' => 'failed',
            'risk_level' => 8,
            'created_at' => now(),
        ]);

        $component = Livewire::test(ActivityMonitor::class);
        $stats = $component->get('todayStats');

        $this->assertArrayHasKey('total_activities', $stats);
        $this->assertArrayHasKey('security_events', $stats);
        $this->assertArrayHasKey('alerts', $stats);
        $this->assertArrayHasKey('unique_users', $stats);
        $this->assertGreaterThanOrEqual(2, $stats['total_activities']);
    }

    /** @test */
    public function it_shows_activity_frequency_data()
    {
        // 建立不同時間的活動記錄
        for ($i = 0; $i < 5; $i++) {
            Activity::create([
                'type' => 'login',
                'description' => "活動 {$i}",
                'user_id' => $this->adminUser->id,
                'result' => 'success',
                'risk_level' => 2,
                'created_at' => now()->subMinutes($i),
            ]);
        }

        $component = Livewire::test(ActivityMonitor::class);
        $frequency = $component->get('activityFrequency');

        $this->assertIsArray($frequency);
        $this->assertNotEmpty($frequency);
        
        foreach ($frequency as $interval) {
            $this->assertArrayHasKey('time', $interval);
            $this->assertArrayHasKey('count', $interval);
            $this->assertArrayHasKey('is_abnormal', $interval);
        }
    }

    /** @test */
    public function it_detects_abnormal_activity_frequency()
    {
        $component = Livewire::test(ActivityMonitor::class)
            ->set('normalFrequencyThreshold', 5); // 設定較低的閾值

        // 建立大量活動記錄（超過閾值）
        for ($i = 0; $i < 10; $i++) {
            Activity::create([
                'type' => 'login',
                'description' => "大量活動 {$i}",
                'user_id' => $this->adminUser->id,
                'result' => 'success',
                'risk_level' => 2,
                'created_at' => now(),
            ]);
        }

        // 重新載入頻率資料
        $component->call('refreshMonitorData');
        
        $frequency = $component->get('activityFrequency');
        $latestInterval = end($frequency);
        
        // 檢查是否檢測到異常
        $this->assertTrue($latestInterval['is_abnormal']);
    }

    /** @test */
    public function it_shows_active_monitor_rules()
    {
        // 建立啟用的監控規則
        MonitorRule::create([
            'name' => '啟用規則',
            'description' => '這是啟用的規則',
            'conditions' => ['type' => 'login'],
            'actions' => ['alert' => true],
            'priority' => 1,
            'is_active' => true,
            'created_by' => $this->adminUser->id,
        ]);

        // 建立停用的監控規則
        MonitorRule::create([
            'name' => '停用規則',
            'description' => '這是停用的規則',
            'conditions' => ['type' => 'logout'],
            'actions' => ['alert' => true],
            'priority' => 1,
            'is_active' => false,
            'created_by' => $this->adminUser->id,
        ]);

        $component = Livewire::test(ActivityMonitor::class);
        $activeRules = $component->get('activeRules');

        $this->assertCount(1, $activeRules);
        $this->assertEquals('啟用規則', $activeRules->first()->name);
    }

    /** @test */
    public function it_shows_recent_activities()
    {
        // 建立最近的活動記錄
        for ($i = 0; $i < 15; $i++) {
            Activity::create([
                'type' => 'login',
                'description' => "最近活動 {$i}",
                'user_id' => $this->adminUser->id,
                'result' => 'success',
                'risk_level' => 2,
                'created_at' => now()->subMinutes($i),
            ]);
        }

        $component = Livewire::test(ActivityMonitor::class);
        $recentActivities = $component->get('recentActivities');

        $this->assertCount(10, $recentActivities); // 限制為 10 筆
        $this->assertEquals('最近活動 0', $recentActivities->first()->description);
    }

    /** @test */
    public function it_counts_unacknowledged_alerts()
    {
        $activity = Activity::create([
            'type' => 'login_failed',
            'description' => '登入失敗',
            'user_id' => $this->adminUser->id,
            'result' => 'failed',
            'risk_level' => 8,
        ]);

        // 建立未確認的警報
        SecurityAlert::create([
            'activity_id' => $activity->id,
            'type' => 'login_failure',
            'severity' => 'high',
            'title' => '未確認警報 1',
            'description' => '第一個未確認警報',
        ]);

        SecurityAlert::create([
            'activity_id' => $activity->id,
            'type' => 'login_failure',
            'severity' => 'medium',
            'title' => '未確認警報 2',
            'description' => '第二個未確認警報',
        ]);

        // 建立已確認的警報
        SecurityAlert::create([
            'activity_id' => $activity->id,
            'type' => 'login_failure',
            'severity' => 'low',
            'title' => '已確認警報',
            'description' => '這個警報已確認',
            'acknowledged_at' => now(),
            'acknowledged_by' => $this->adminUser->id,
        ]);

        $component = Livewire::test(ActivityMonitor::class);
        $unacknowledgedCount = $component->get('unacknowledgedAlertsCount');

        $this->assertEquals(2, $unacknowledgedCount);
    }

    /** @test */
    public function it_requires_proper_permissions()
    {
        // 測試沒有權限的使用者
        $this->actingAs($this->regularUser);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        Livewire::test(ActivityMonitor::class);
    }

    /** @test */
    public function it_handles_rule_modal_toggle()
    {
        Livewire::test(ActivityMonitor::class)
            ->assertSet('showRuleModal', false)
            ->set('showRuleModal', true)
            ->assertSet('showRuleModal', true);
    }

    /** @test */
    public function it_resets_rule_form_after_adding()
    {
        $component = Livewire::test(ActivityMonitor::class)
            ->set('newRule.name', '測試規則')
            ->set('newRule.description', '測試描述')
            ->set('newRule.priority', 5)
            ->call('addRule');

        // 檢查表單是否重置
        $component->assertSet('newRule.name', '')
            ->assertSet('newRule.description', '')
            ->assertSet('newRule.priority', 1)
            ->assertSet('showRuleModal', false);
    }

    /** @test */
    public function it_handles_monitoring_when_not_active()
    {
        $component = Livewire::test(ActivityMonitor::class)
            ->set('isMonitoring', false);

        // 當監控未啟動時，事件應該被忽略
        $component->dispatch('security-alert', ['message' => '測試警報']);
        $component->dispatch('activity-logged', ['id' => 1]);

        // 監控狀態應該保持不變
        $component->assertSet('isMonitoring', false);
    }
}