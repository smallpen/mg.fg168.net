<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

/**
 * Livewire 表單重置功能全面測試套件
 * 
 * 此測試套件驗證所有已修復的 Livewire 元件的表單重置功能
 * 確保前後端狀態同步的正確性
 */
class LivewireFormResetComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $testUser;
    protected Role $adminRole;
    protected array $testData;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試資料
        $this->setupTestData();
        
        // 以管理員身份登入
        $this->actingAs($this->adminUser);
    }

    /**
     * 建立測試資料
     */
    private function setupTestData(): void
    {
        // 建立權限
        $permissions = [
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
            'permissions.view', 'permissions.create', 'permissions.edit', 'permissions.delete',
            'dashboard.view', 'dashboard.stats',
            'activity_logs.view', 'activity_logs.export', 'activity_logs.delete',
            'settings.view', 'settings.edit', 'settings.backup', 'settings.reset',
            'system.settings', 'system.logs', 'system.maintenance',
            'profile.view', 'profile.edit'
        ];

        foreach ($permissions as $permission) {
            Permission::create([
                'name' => $permission,
                'display_name' => ucfirst(str_replace('.', ' ', $permission)),
                'module' => explode('.', $permission)[0]
            ]);
        }

        // 建立管理員角色
        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理員'
        ]);

        // 指派所有權限給管理員角色
        $this->adminRole->permissions()->attach(Permission::all());

        // 建立管理員使用者
        $this->adminUser = User::create([
            'username' => 'admin',
            'name' => '系統管理員',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'is_active' => true
        ]);

        $this->adminUser->roles()->attach($this->adminRole);

        // 建立測試使用者
        $this->testUser = User::create([
            'username' => 'testuser',
            'name' => '測試使用者',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'is_active' => true
        ]);

        $this->testData = [
            'search_term' => 'test search',
            'filter_value' => 'active',
            'form_data' => [
                'username' => 'newuser',
                'name' => '新使用者',
                'email' => 'newuser@example.com'
            ]
        ];
    }

    /**
     * 測試 UserList 元件的篩選重置功能
     * 
     * @test
     */
    public function test_user_list_filter_reset_functionality()
    {
        $component = Livewire::test(\App\Livewire\Admin\Users\UserList::class);

        // 設定篩選條件
        $component->set('search', $this->testData['search_term'])
                  ->set('statusFilter', $this->testData['filter_value'])
                  ->set('roleFilter', $this->adminRole->id);

        // 驗證篩選條件已設定
        $component->assertSet('search', $this->testData['search_term'])
                  ->assertSet('statusFilter', $this->testData['filter_value'])
                  ->assertSet('roleFilter', $this->adminRole->id);

        // 執行重置
        $component->call('resetFilters');

        // 驗證篩選條件已重置
        $component->assertSet('search', '')
                  ->assertSet('statusFilter', 'all')
                  ->assertSet('roleFilter', '');

        // 驗證頁面已重置到第一頁
        $component->assertSet('page', 1);
    }

    /**
     * 測試 ActivityExport 元件的重置功能
     * 
     * @test
     */
    public function test_activity_export_reset_functionality()
    {
        $component = Livewire::test(\App\Livewire\Admin\Activities\ActivityExport::class);

        // 設定匯出參數
        $component->set('startDate', '2024-01-01')
                  ->set('endDate', '2024-12-31')
                  ->set('userFilter', $this->testUser->id)
                  ->set('actionFilter', 'created');

        // 驗證參數已設定
        $component->assertSet('startDate', '2024-01-01')
                  ->assertSet('endDate', '2024-12-31')
                  ->assertSet('userFilter', $this->testUser->id)
                  ->assertSet('actionFilter', 'created');

        // 執行重置
        $component->call('resetFilters');

        // 驗證參數已重置
        $component->assertSet('startDate', '')
                  ->assertSet('endDate', '')
                  ->assertSet('userFilter', '')
                  ->assertSet('actionFilter', '');
    }

    /**
     * 測試 PermissionAuditLog 元件的篩選功能
     * 
     * @test
     */
    public function test_permission_audit_log_filter_functionality()
    {
        $component = Livewire::test(\App\Livewire\Admin\Permissions\PermissionAuditLog::class);

        // 設定篩選條件
        $component->set('search', 'permission test')
                  ->set('userFilter', $this->adminUser->id)
                  ->set('actionFilter', 'granted');

        // 驗證篩選條件已設定
        $component->assertSet('search', 'permission test')
                  ->assertSet('userFilter', $this->adminUser->id)
                  ->assertSet('actionFilter', 'granted');

        // 執行重置
        $component->call('resetFilters');

        // 驗證篩選條件已重置
        $component->assertSet('search', '')
                  ->assertSet('userFilter', '')
                  ->assertSet('actionFilter', '');
    }

    /**
     * 測試 SettingsList 元件的搜尋清除功能
     * 
     * @test
     */
    public function test_settings_list_clear_functionality()
    {
        $component = Livewire::test(\App\Livewire\Admin\Settings\SettingsList::class);

        // 設定搜尋條件
        $component->set('search', 'app settings')
                  ->set('categoryFilter', 'system');

        // 驗證搜尋條件已設定
        $component->assertSet('search', 'app settings')
                  ->assertSet('categoryFilter', 'system');

        // 執行清除
        $component->call('clearFilters');

        // 驗證搜尋條件已清除
        $component->assertSet('search', '')
                  ->assertSet('categoryFilter', '');
    }

    /**
     * 測試 NotificationList 元件的篩選清除功能
     * 
     * @test
     */
    public function test_notification_list_clear_functionality()
    {
        $component = Livewire::test(\App\Livewire\Admin\Activities\NotificationList::class);

        // 設定篩選條件
        $component->set('search', 'notification test')
                  ->set('statusFilter', 'unread')
                  ->set('typeFilter', 'system');

        // 驗證篩選條件已設定
        $component->assertSet('search', 'notification test')
                  ->assertSet('statusFilter', 'unread')
                  ->assertSet('typeFilter', 'system');

        // 執行清除
        $component->call('clearFilters');

        // 驗證篩選條件已清除
        $component->assertSet('search', '')
                  ->assertSet('statusFilter', 'all')
                  ->assertSet('typeFilter', '');
    }

    /**
     * 測試 PermissionTemplateManager 模板表單重置
     * 
     * @test
     */
    public function test_permission_template_manager_form_reset()
    {
        $component = Livewire::test(\App\Livewire\Admin\Permissions\PermissionTemplateManager::class);

        // 設定模板表單資料
        $component->set('templateName', 'Test Template')
                  ->set('templateDescription', 'Test Description')
                  ->set('selectedPermissions', [1, 2, 3]);

        // 驗證表單資料已設定
        $component->assertSet('templateName', 'Test Template')
                  ->assertSet('templateDescription', 'Test Description')
                  ->assertSet('selectedPermissions', [1, 2, 3]);

        // 執行重置
        $component->call('resetTemplateForm');

        // 驗證表單已重置
        $component->assertSet('templateName', '')
                  ->assertSet('templateDescription', '')
                  ->assertSet('selectedPermissions', []);
    }

    /**
     * 測試 UserDeleteModal 刪除確認表單重置
     * 
     * @test
     */
    public function test_user_delete_modal_reset_functionality()
    {
        $component = Livewire::test(\App\Livewire\Admin\Users\UserDeleteModal::class);

        // 設定刪除確認資料
        $component->set('showModal', true)
                  ->set('selectedUser', $this->testUser->id)
                  ->set('confirmationText', 'DELETE')
                  ->set('deleteReason', 'Test deletion');

        // 驗證資料已設定
        $component->assertSet('showModal', true)
                  ->assertSet('selectedUser', $this->testUser->id)
                  ->assertSet('confirmationText', 'DELETE')
                  ->assertSet('deleteReason', 'Test deletion');

        // 執行重置（關閉模態）
        $component->call('closeModal');

        // 驗證表單已重置
        $component->assertSet('showModal', false)
                  ->assertSet('selectedUser', null)
                  ->assertSet('confirmationText', '')
                  ->assertSet('deleteReason', '');
    }

    /**
     * 測試 RetentionPolicyManager 保留政策表單重置
     * 
     * @test
     */
    public function test_retention_policy_manager_form_reset()
    {
        $component = Livewire::test(\App\Livewire\Admin\Activities\RetentionPolicyManager::class);

        // 設定政策表單資料
        $component->set('policyName', 'Test Policy')
                  ->set('retentionDays', 90)
                  ->set('policyType', 'activity_logs')
                  ->set('isActive', true);

        // 驗證表單資料已設定
        $component->assertSet('policyName', 'Test Policy')
                  ->assertSet('retentionDays', 90)
                  ->assertSet('policyType', 'activity_logs')
                  ->assertSet('isActive', true);

        // 執行重置
        $component->call('resetPolicyForm');

        // 驗證表單已重置
        $component->assertSet('policyName', '')
                  ->assertSet('retentionDays', 30)
                  ->assertSet('policyType', '')
                  ->assertSet('isActive', false);
    }

    /**
     * 測試 PerformanceMonitor 時間週期控制
     * 
     * @test
     */
    public function test_performance_monitor_period_control()
    {
        $component = Livewire::test(\App\Livewire\Admin\Performance\PerformanceMonitor::class);

        // 設定時間週期
        $component->set('selectedPeriod', '7d')
                  ->set('customStartDate', '2024-01-01')
                  ->set('customEndDate', '2024-01-07');

        // 驗證設定已生效
        $component->assertSet('selectedPeriod', '7d')
                  ->assertSet('customStartDate', '2024-01-01')
                  ->assertSet('customEndDate', '2024-01-07');

        // 重置到預設值
        $component->call('resetPeriod');

        // 驗證已重置到預設值
        $component->assertSet('selectedPeriod', '24h')
                  ->assertSet('customStartDate', '')
                  ->assertSet('customEndDate', '');
    }

    /**
     * 測試 SystemMonitor 自動刷新控制
     * 
     * @test
     */
    public function test_system_monitor_refresh_control()
    {
        $component = Livewire::test(\App\Livewire\Admin\Monitoring\SystemMonitor::class);

        // 設定自動刷新參數
        $component->set('autoRefresh', true)
                  ->set('refreshInterval', 10);

        // 驗證設定已生效
        $component->assertSet('autoRefresh', true)
                  ->assertSet('refreshInterval', 10);

        // 重置設定
        $component->call('resetSettings');

        // 驗證已重置到預設值
        $component->assertSet('autoRefresh', false)
                  ->assertSet('refreshInterval', 30);
    }

    /**
     * 測試 RecentActivity 篩選清除功能
     * 
     * @test
     */
    public function test_recent_activity_filter_clear()
    {
        $component = Livewire::test(\App\Livewire\Admin\Dashboard\RecentActivity::class);

        // 設定篩選條件
        $component->set('activityFilter', 'created')
                  ->set('userFilter', $this->adminUser->id)
                  ->set('dateFilter', '7d');

        // 驗證篩選條件已設定
        $component->assertSet('activityFilter', 'created')
                  ->assertSet('userFilter', $this->adminUser->id)
                  ->assertSet('dateFilter', '7d');

        // 執行清除
        $component->call('clearFilters');

        // 驗證篩選條件已清除
        $component->assertSet('activityFilter', '')
                  ->assertSet('userFilter', '')
                  ->assertSet('dateFilter', '');
    }

    /**
     * 測試 SettingChangeHistory 篩選清除功能
     * 
     * @test
     */
    public function test_setting_change_history_filter_clear()
    {
        $component = Livewire::test(\App\Livewire\Admin\Settings\SettingChangeHistory::class);

        // 設定篩選條件
        $component->set('search', 'setting change')
                  ->set('userFilter', $this->adminUser->id)
                  ->set('categoryFilter', 'system')
                  ->set('dateRange', '30d');

        // 驗證篩選條件已設定
        $component->assertSet('search', 'setting change')
                  ->assertSet('userFilter', $this->adminUser->id)
                  ->assertSet('categoryFilter', 'system')
                  ->assertSet('dateRange', '30d');

        // 執行清除
        $component->call('clearFilters');

        // 驗證篩選條件已清除
        $component->assertSet('search', '')
                  ->assertSet('userFilter', '')
                  ->assertSet('categoryFilter', '')
                  ->assertSet('dateRange', '');
    }

    /**
     * 測試表單重置後的前端同步
     * 
     * @test
     */
    public function test_frontend_sync_after_reset()
    {
        $component = Livewire::test(\App\Livewire\Admin\Users\UserList::class);

        // 設定複雜的篩選條件
        $component->set('search', 'complex search term')
                  ->set('statusFilter', 'inactive')
                  ->set('roleFilter', $this->adminRole->id)
                  ->set('sortBy', 'created_at')
                  ->set('sortDirection', 'desc')
                  ->set('perPage', 50);

        // 執行重置
        $component->call('resetFilters');

        // 驗證所有狀態都已正確重置
        $component->assertSet('search', '')
                  ->assertSet('statusFilter', 'all')
                  ->assertSet('roleFilter', '')
                  ->assertSet('sortBy', 'username')
                  ->assertSet('sortDirection', 'asc')
                  ->assertSet('perPage', 10)
                  ->assertSet('page', 1);

        // 驗證元件可以正常重新渲染
        $component->assertSee('使用者列表');
    }

    /**
     * 測試批次重置功能
     * 
     * @test
     */
    public function test_batch_reset_functionality()
    {
        // 測試多個元件同時重置
        $userListComponent = Livewire::test(\App\Livewire\Admin\Users\UserList::class);
        $activityExportComponent = Livewire::test(\App\Livewire\Admin\Activities\ActivityExport::class);
        $settingsListComponent = Livewire::test(\App\Livewire\Admin\Settings\SettingsList::class);

        // 為所有元件設定資料
        $userListComponent->set('search', 'user search');
        $activityExportComponent->set('startDate', '2024-01-01');
        $settingsListComponent->set('search', 'setting search');

        // 執行批次重置
        $userListComponent->call('resetFilters');
        $activityExportComponent->call('resetFilters');
        $settingsListComponent->call('clearFilters');

        // 驗證所有元件都已重置
        $userListComponent->assertSet('search', '');
        $activityExportComponent->assertSet('startDate', '');
        $settingsListComponent->assertSet('search', '');
    }

    /**
     * 測試重置功能的錯誤處理
     * 
     * @test
     */
    public function test_reset_error_handling()
    {
        $component = Livewire::test(\App\Livewire\Admin\Users\UserList::class);

        // 設定無效的篩選值
        $component->set('roleFilter', 999999); // 不存在的角色 ID

        // 執行重置應該能正常處理
        $component->call('resetFilters');

        // 驗證重置成功，即使原始值無效
        $component->assertSet('roleFilter', '');
    }

    /**
     * 測試重置後的資料完整性
     * 
     * @test
     */
    public function test_data_integrity_after_reset()
    {
        $component = Livewire::test(\App\Livewire\Admin\Users\UserList::class);

        // 記錄重置前的使用者總數
        $initialUserCount = User::count();

        // 設定篩選並重置
        $component->set('search', 'test')
                  ->call('resetFilters');

        // 驗證資料庫資料未受影響
        $this->assertEquals($initialUserCount, User::count());

        // 驗證元件仍能正常載入資料
        $component->assertSee('使用者列表');
    }
}