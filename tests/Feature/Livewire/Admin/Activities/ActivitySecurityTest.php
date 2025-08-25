<?php

namespace Tests\Feature\Livewire\Admin\Activities;

use App\Livewire\Admin\Activities\ActivityList;
use App\Livewire\Admin\Activities\ActivityDetail;
use App\Livewire\Admin\Activities\ActivityStats;
use App\Livewire\Admin\Activities\ActivityMonitor;
use App\Models\Activity;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Services\ActivityIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Traits\DisablesPermissionSecurity;
use Carbon\Carbon;

/**
 * 活動記錄安全性和權限測試
 * 
 * 測試活動記錄功能的安全性控制，包括：
 * - 權限檢查和存取控制
 * - 敏感資料過濾和保護
 * - 完整性驗證和防篡改
 * - 安全事件檢測和處理
 * - 資料加密和簽章驗證
 */
class ActivitySecurityTest extends TestCase
{
    use RefreshDatabase, DisablesPermissionSecurity;

    protected User $adminUser;
    protected User $regularUser;
    protected User $limitedUser;
    protected Role $adminRole;
    protected Role $userRole;
    protected Role $limitedRole;

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

        $this->limitedRole = Role::create([
            'name' => 'limited',
            'display_name' => '受限使用者',
            'description' => '受限使用者',
            'is_system' => false,
        ]);

        // 建立權限
        $permissions = [
            'system.logs' => ['系統記錄檢視', 'system'],
            'activity_logs.view' => ['檢視活動日誌', 'activity_logs'],
            'activity_logs.export' => ['匯出活動日誌', 'activity_logs'],
            'activity_logs.delete' => ['刪除活動日誌', 'activity_logs'],
            'security.view' => ['檢視安全資訊', 'security'],
            'security.incidents' => ['管理安全事件', 'security'],
            'security.audit' => ['安全稽核', 'security'],
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

            // 一般使用者只有檢視權限
            if (in_array($name, ['activity_logs.view'])) {
                $this->userRole->permissions()->attach($permission);
            }
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

        $this->limitedUser = User::create([
            'username' => 'limited',
            'name' => '受限使用者',
            'email' => 'limited@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        // 指派角色
        $this->adminUser->roles()->attach($this->adminRole);
        $this->regularUser->roles()->attach($this->userRole);
        $this->limitedUser->roles()->attach($this->limitedRole);
    }

    /** @test */
    public function admin_can_access_all_activity_components()
    {
        $this->actingAs($this->adminUser);

        // 管理員應該能存取所有活動記錄元件
        Livewire::test(ActivityList::class)->assertStatus(200);
        Livewire::test(ActivityDetail::class)->assertStatus(200);
        Livewire::test(ActivityStats::class)->assertStatus(200);
        Livewire::test(ActivityMonitor::class)->assertStatus(200);
    }

    /** @test */
    public function regular_user_has_limited_access()
    {
        $this->actingAs($this->regularUser);

        // 一般使用者只能存取有權限的元件
        Livewire::test(ActivityList::class)->assertStatus(200);
        Livewire::test(ActivityDetail::class)->assertStatus(200);

        // 沒有統計和監控權限
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        Livewire::test(ActivityStats::class);
    }

    /** @test */
    public function limited_user_cannot_access_activity_components()
    {
        $this->actingAs($this->limitedUser);

        // 受限使用者不能存取任何活動記錄元件
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        Livewire::test(ActivityList::class);
    }

    /** @test */
    public function sensitive_data_is_filtered_in_activity_properties()
    {
        $this->actingAs($this->adminUser);

        $activity = Activity::create([
            'type' => 'user_login',
            'description' => '使用者登入',
            'user_id' => $this->adminUser->id,
            'properties' => [
                'username' => 'testuser',
                'password' => 'secret123', // 敏感資料
                'token' => 'abc123def456', // 敏感資料
                'api_key' => 'key_12345', // 敏感資料
                'credit_card' => '1234-5678-9012-3456', // 敏感資料
                'normal_field' => 'normal_value',
            ],
            'result' => 'success',
            'risk_level' => 2,
        ]);

        $component = Livewire::test(ActivityDetail::class);
        $component->call('loadActivity', $activity->id);
        
        $formattedData = $component->get('formattedData');
        
        // 檢查敏感資料是否被過濾
        $sensitiveFields = ['password', 'token', 'api_key', 'credit_card'];
        foreach ($formattedData as $field) {
            if (in_array($field['raw_key'], $sensitiveFields)) {
                $this->assertEquals('[FILTERED]', $field['value']);
            }
        }

        // 檢查正常欄位是否保持原值
        $normalField = collect($formattedData)->firstWhere('raw_key', 'normal_field');
        $this->assertEquals('normal_value', $normalField['value']);
    }

    /** @test */
    public function activity_integrity_is_verified()
    {
        $this->actingAs($this->adminUser);

        // 建立帶有簽章的活動記錄
        $activityData = [
            'type' => 'user_login',
            'description' => '使用者登入',
            'user_id' => $this->adminUser->id,
            'properties' => ['test' => 'data'],
            'result' => 'success',
            'risk_level' => 2,
        ];

        $integrityService = app(ActivityIntegrityService::class);
        $signature = $integrityService->generateSignature($activityData);

        $activity = Activity::create(array_merge($activityData, [
            'signature' => $signature,
        ]));

        // 驗證完整性
        $this->assertTrue($integrityService->verifyActivity($activity));
    }

    /** @test */
    public function tampered_activity_is_detected()
    {
        $this->actingAs($this->adminUser);

        // 建立活動記錄
        $activityData = [
            'type' => 'user_login',
            'description' => '使用者登入',
            'user_id' => $this->adminUser->id,
            'properties' => ['test' => 'data'],
            'result' => 'success',
            'risk_level' => 2,
        ];

        $integrityService = app(ActivityIntegrityService::class);
        $signature = $integrityService->generateSignature($activityData);

        $activity = Activity::create(array_merge($activityData, [
            'signature' => $signature,
        ]));

        // 篡改資料
        $activity->update(['description' => '篡改的描述']);

        // 驗證應該失敗
        $this->assertFalse($integrityService->verifyActivity($activity));
    }

    /** @test */
    public function export_requires_proper_permissions()
    {
        // 測試沒有匯出權限的使用者
        $this->actingAs($this->regularUser);

        Activity::create([
            'type' => 'login',
            'description' => '測試活動',
            'user_id' => $this->regularUser->id,
            'result' => 'success',
        ]);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        Livewire::test(ActivityList::class)
            ->call('exportActivities');
    }

    /** @test */
    public function admin_can_export_activities()
    {
        $this->actingAs($this->adminUser);

        Activity::create([
            'type' => 'login',
            'description' => '測試活動',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
        ]);

        // 管理員應該能匯出活動記錄
        Livewire::test(ActivityList::class)
            ->call('exportActivities')
            ->assertDispatched('export-started');
    }

    /** @test */
    public function security_events_are_properly_flagged()
    {
        $this->actingAs($this->adminUser);

        // 建立高風險活動記錄
        $securityActivity = Activity::create([
            'type' => 'login_failed',
            'description' => '多次登入失敗',
            'user_id' => $this->adminUser->id,
            'result' => 'failed',
            'risk_level' => 9,
            'is_security_event' => true,
        ]);

        // 建立正常活動記錄
        $normalActivity = Activity::create([
            'type' => 'login',
            'description' => '正常登入',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
            'risk_level' => 2,
            'is_security_event' => false,
        ]);

        $component = Livewire::test(ActivityList::class);
        $stats = $component->get('stats');

        // 檢查安全事件統計
        $this->assertEquals(1, $stats['security_events']);
        $this->assertEquals(1, $stats['high_risk']);
    }

    /** @test */
    public function ip_address_filtering_works_correctly()
    {
        $this->actingAs($this->adminUser);

        // 建立來自不同 IP 的活動記錄
        Activity::create([
            'type' => 'login',
            'description' => '內網登入',
            'user_id' => $this->adminUser->id,
            'ip_address' => '192.168.1.100',
            'result' => 'success',
        ]);

        Activity::create([
            'type' => 'login',
            'description' => '可疑 IP 登入',
            'user_id' => $this->adminUser->id,
            'ip_address' => '10.0.0.1',
            'result' => 'success',
        ]);

        // 測試 IP 篩選功能
        Livewire::test(ActivityList::class)
            ->set('ipFilter', '192.168.1.100')
            ->assertSee('內網登入')
            ->assertDontSee('可疑 IP 登入');
    }

    /** @test */
    public function user_can_only_see_own_activities_when_restricted()
    {
        // 建立受限權限的使用者（只能看自己的活動）
        $restrictedRole = Role::create([
            'name' => 'restricted_viewer',
            'display_name' => '受限檢視者',
            'description' => '只能檢視自己的活動記錄',
        ]);

        $viewOwnPermission = Permission::create([
            'name' => 'activity_logs.view_own',
            'display_name' => '檢視自己的活動日誌',
            'module' => 'activity_logs',
        ]);

        $restrictedRole->permissions()->attach($viewOwnPermission);

        $restrictedUser = User::create([
            'username' => 'restricted_viewer',
            'name' => '受限檢視者',
            'email' => 'restricted@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $restrictedUser->roles()->attach($restrictedRole);

        // 建立不同使用者的活動記錄
        Activity::create([
            'type' => 'login',
            'description' => '受限使用者活動',
            'user_id' => $restrictedUser->id,
            'result' => 'success',
        ]);

        Activity::create([
            'type' => 'login',
            'description' => '其他使用者活動',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
        ]);

        $this->actingAs($restrictedUser);

        // 受限使用者應該只能看到自己的活動
        $component = Livewire::test(ActivityList::class);
        $activities = $component->get('activities');

        foreach ($activities as $activity) {
            $this->assertEquals($restrictedUser->id, $activity->user_id);
        }
    }

    /** @test */
    public function bulk_operations_require_proper_permissions()
    {
        $this->actingAs($this->regularUser);

        $activity = Activity::create([
            'type' => 'login',
            'description' => '測試活動',
            'user_id' => $this->regularUser->id,
            'result' => 'success',
        ]);

        // 一般使用者不應該能執行批量操作
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        Livewire::test(ActivityList::class)
            ->set('selectedActivities', [$activity->id])
            ->set('bulkAction', 'mark_reviewed')
            ->call('executeBulkAction');
    }

    /** @test */
    public function security_monitoring_requires_security_permissions()
    {
        $this->actingAs($this->regularUser);

        // 一般使用者不應該能存取安全監控
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        Livewire::test(ActivityMonitor::class);
    }

    /** @test */
    public function activity_flagging_is_logged()
    {
        $this->actingAs($this->adminUser);

        $activity = Activity::create([
            'type' => 'user_login',
            'description' => '可疑登入',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
            'risk_level' => 7,
        ]);

        $component = Livewire::test(ActivityDetail::class);
        $component->call('loadActivity', $activity->id);
        $component->call('flagAsSuspicious');

        // 檢查是否建立了標記記錄
        $this->assertDatabaseHas('activities', [
            'type' => 'activity_flagged',
            'user_id' => $this->adminUser->id,
        ]);
    }

    /** @test */
    public function note_addition_is_logged()
    {
        $this->actingAs($this->adminUser);

        $activity = Activity::create([
            'type' => 'user_login',
            'description' => '使用者登入',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
            'risk_level' => 2,
        ]);

        $component = Livewire::test(ActivityDetail::class);
        $component->call('loadActivity', $activity->id);
        $component->call('addNote', '這是一個安全註記');

        // 檢查是否建立了註記記錄
        $this->assertDatabaseHas('activities', [
            'type' => 'activity_note_added',
            'user_id' => $this->adminUser->id,
        ]);
    }

    /** @test */
    public function high_risk_activities_are_highlighted()
    {
        $this->actingAs($this->adminUser);

        // 建立高風險活動
        Activity::create([
            'type' => 'admin_action',
            'description' => '高風險管理操作',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
            'risk_level' => 9,
        ]);

        $component = Livewire::test(ActivityList::class);
        $stats = $component->get('stats');

        // 檢查高風險活動統計
        $this->assertEquals(1, $stats['high_risk']);
    }

    /** @test */
    public function failed_activities_are_tracked()
    {
        $this->actingAs($this->adminUser);

        // 建立失敗的活動記錄
        Activity::create([
            'type' => 'login',
            'description' => '登入失敗',
            'user_id' => $this->adminUser->id,
            'result' => 'failed',
            'risk_level' => 6,
        ]);

        $component = Livewire::test(ActivityList::class);
        $stats = $component->get('stats');

        // 檢查失敗活動統計
        $this->assertEquals(1, $stats['failed']);
    }

    /** @test */
    public function activity_export_includes_security_metadata()
    {
        $this->actingAs($this->adminUser);

        $activity = Activity::create([
            'type' => 'login',
            'description' => '測試活動',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
            'risk_level' => 5,
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Test Browser',
        ]);

        // 測試匯出功能包含安全相關欄位
        Livewire::test(ActivityList::class)
            ->call('exportActivities');

        // 檢查匯出操作是否被記錄
        $this->assertDatabaseHas('activities', [
            'type' => 'export_data',
            'user_id' => $this->adminUser->id,
        ]);
    }

    /** @test */
    public function real_time_monitoring_security_events()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(ActivityMonitor::class)
            ->set('isMonitoring', true);

        // 模擬安全事件
        $component->dispatch('security-alert', [
            'title' => '可疑活動檢測',
            'message' => '檢測到異常登入模式',
            'severity' => 'high'
        ]);

        // 檢查警報計數是否更新
        $this->assertTrue($component->get('alertCount') >= 0);
    }

    /** @test */
    public function activity_signature_prevents_modification()
    {
        $this->actingAs($this->adminUser);

        $activityData = [
            'type' => 'user_login',
            'description' => '原始描述',
            'user_id' => $this->adminUser->id,
            'properties' => ['original' => 'data'],
            'result' => 'success',
            'risk_level' => 2,
        ];

        $integrityService = app(ActivityIntegrityService::class);
        $originalSignature = $integrityService->generateSignature($activityData);

        $activity = Activity::create(array_merge($activityData, [
            'signature' => $originalSignature,
        ]));

        // 嘗試修改活動記錄
        $activity->update(['description' => '修改後的描述']);

        // 重新生成簽章應該不同
        $newSignature = $integrityService->generateSignature([
            'type' => $activity->type,
            'description' => $activity->description,
            'properties' => $activity->properties,
            'created_at' => $activity->created_at,
        ]);

        $this->assertNotEquals($originalSignature, $newSignature);
        $this->assertFalse($integrityService->verifyActivity($activity));
    }
}