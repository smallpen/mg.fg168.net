<?php

namespace Tests\Feature\Livewire\Admin\Dashboard;

use App\Http\Livewire\Admin\Dashboard\QuickActions;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * QuickActions 元件測試
 * 
 * 測試快速操作連結的顯示和權限控制
 */
class QuickActionsTest extends TestCase
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

        Livewire::test(QuickActions::class)
            ->assertStatus(200)
            ->assertSee('快速操作')
            ->assertSee('常用功能');
    }

    /**
     * 測試管理員快速操作顯示
     */
    public function test_admin_quick_actions_display()
    {
        $this->actingAs($this->admin);

        Livewire::test(QuickActions::class)
            ->assertSee('新增使用者')
            ->assertSee('新增角色')
            ->assertSee('系統設定')
            ->assertSee('檢視日誌');
    }

    /**
     * 測試編輯者快速操作顯示
     */
    public function test_editor_quick_actions_display()
    {
        $editor = User::factory()->create();
        $editor->roles()->attach($this->editorRole);
        
        $this->actingAs($editor);

        Livewire::test(QuickActions::class)
            ->assertSee('新增使用者')
            ->assertDontSee('新增角色') // 編輯者沒有角色管理權限
            ->assertDontSee('系統設定'); // 編輯者沒有系統設定權限
    }

    /**
     * 測試快速操作點擊事件
     */
    public function test_quick_action_click_events()
    {
        $this->actingAs($this->admin);

        Livewire::test(QuickActions::class)
            ->call('navigateToCreateUser')
            ->assertDispatched('navigate-to', ['route' => 'admin.users.create'])
            ->call('navigateToCreateRole')
            ->assertDispatched('navigate-to', ['route' => 'admin.roles.create']);
    }

    /**
     * 測試權限控制 - 無權限使用者
     */
    public function test_unauthorized_access()
    {
        $unauthorizedUser = User::factory()->create();
        $this->actingAs($unauthorizedUser);

        Livewire::test(QuickActions::class)
            ->assertSee('沒有可用的快速操作'); // 無權限時顯示提示訊息
    }

    /**
     * 測試動態權限檢查
     */
    public function test_dynamic_permission_check()
    {
        $this->actingAs($this->admin);

        Livewire::test(QuickActions::class)
            ->assertSee('新增使用者')
            ->call('checkPermissions') // 重新檢查權限
            ->assertSee('新增使用者'); // 確認權限仍然有效
    }

    /**
     * 測試快速操作統計
     */
    public function test_quick_actions_statistics()
    {
        $this->actingAs($this->admin);

        // 建立一些測試資料
        User::factory()->count(10)->create();
        Role::factory()->count(5)->create();

        Livewire::test(QuickActions::class)
            ->assertSee('11 位使用者') // 包含管理員
            ->assertSee('6 個角色'); // 包含管理員角色
    }

    /**
     * 測試最近使用的操作
     */
    public function test_recent_actions_display()
    {
        $this->actingAs($this->admin);

        // 模擬最近的操作記錄
        session(['recent_actions' => [
            ['action' => 'create_user', 'timestamp' => now()],
            ['action' => 'edit_role', 'timestamp' => now()->subMinutes(5)]
        ]]);

        Livewire::test(QuickActions::class)
            ->assertSee('最近操作')
            ->assertSee('建立使用者')
            ->assertSee('編輯角色');
    }

    /**
     * 測試快速搜尋功能
     */
    public function test_quick_search_functionality()
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create(['name' => 'John Doe']);

        Livewire::test(QuickActions::class)
            ->set('searchTerm', 'John')
            ->call('quickSearch')
            ->assertSee('John Doe')
            ->assertDispatched('search-results-updated');
    }

    /**
     * 測試快速建立使用者
     */
    public function test_quick_create_user()
    {
        $this->actingAs($this->admin);

        Livewire::test(QuickActions::class)
            ->set('quickUserName', 'Quick User')
            ->set('quickUserEmail', 'quick@example.com')
            ->call('quickCreateUser')
            ->assertDispatched('user-quick-created')
            ->assertSee('使用者建立成功');

        $this->assertDatabaseHas('users', [
            'name' => 'Quick User',
            'email' => 'quick@example.com'
        ]);
    }

    /**
     * 測試快速建立角色
     */
    public function test_quick_create_role()
    {
        $this->actingAs($this->admin);

        Livewire::test(QuickActions::class)
            ->set('quickRoleName', 'Quick Role')
            ->call('quickCreateRole')
            ->assertDispatched('role-quick-created')
            ->assertSee('角色建立成功');

        $this->assertDatabaseHas('roles', [
            'name' => 'quick_role',
            'display_name' => 'Quick Role'
        ]);
    }

    /**
     * 測試系統狀態檢查
     */
    public function test_system_status_check()
    {
        $this->actingAs($this->admin);

        Livewire::test(QuickActions::class)
            ->call('checkSystemStatus')
            ->assertSee('系統狀態')
            ->assertSee('正常'); // 或其他狀態指示器
    }

    /**
     * 測試快速備份功能
     */
    public function test_quick_backup()
    {
        $this->actingAs($this->admin);

        Livewire::test(QuickActions::class)
            ->call('initiateQuickBackup')
            ->assertDispatched('backup-started')
            ->assertSee('備份已開始');
    }

    /**
     * 測試快速操作的載入狀態
     */
    public function test_quick_actions_loading_state()
    {
        $this->actingAs($this->admin);

        Livewire::test(QuickActions::class)
            ->call('quickCreateUser')
            ->assertSet('isLoading', true)
            ->assertSee('處理中...');
    }

    /**
     * 測試快速操作錯誤處理
     */
    public function test_quick_actions_error_handling()
    {
        $this->actingAs($this->admin);

        // 測試無效的快速建立使用者
        Livewire::test(QuickActions::class)
            ->set('quickUserName', '')
            ->set('quickUserEmail', 'invalid-email')
            ->call('quickCreateUser')
            ->assertHasErrors(['quickUserName', 'quickUserEmail'])
            ->assertSee('請檢查輸入資料');
    }

    /**
     * 測試快速操作的鍵盤快捷鍵
     */
    public function test_keyboard_shortcuts()
    {
        $this->actingAs($this->admin);

        Livewire::test(QuickActions::class)
            ->assertSee('Ctrl+N') // 新增使用者快捷鍵
            ->assertSee('Ctrl+R') // 新增角色快捷鍵
            ->call('handleKeyboardShortcut', 'ctrl+n')
            ->assertDispatched('navigate-to', ['route' => 'admin.users.create']);
    }

    /**
     * 測試自訂快速操作
     */
    public function test_custom_quick_actions()
    {
        $this->actingAs($this->admin);

        // 設定自訂快速操作
        $customActions = [
            ['name' => '匯出報表', 'action' => 'export_report'],
            ['name' => '清理快取', 'action' => 'clear_cache']
        ];

        Livewire::test(QuickActions::class)
            ->set('customActions', $customActions)
            ->assertSee('匯出報表')
            ->assertSee('清理快取');
    }

    /**
     * 測試快速操作的使用統計
     */
    public function test_quick_actions_usage_statistics()
    {
        $this->actingAs($this->admin);

        // 模擬操作使用記錄
        Livewire::test(QuickActions::class)
            ->call('navigateToCreateUser')
            ->call('trackActionUsage', 'create_user');

        // 檢查使用統計是否被記錄
        $this->assertDatabaseHas('action_usage_logs', [
            'user_id' => $this->admin->id,
            'action' => 'create_user'
        ]);
    }

    /**
     * 測試快速操作的個人化設定
     */
    public function test_personalized_quick_actions()
    {
        $this->actingAs($this->admin);

        // 設定個人化的快速操作偏好
        Livewire::test(QuickActions::class)
            ->set('favoriteActions', ['create_user', 'view_logs'])
            ->call('savePersonalizedActions')
            ->assertDispatched('preferences-saved')
            ->assertSee('偏好設定已儲存');
    }

    /**
     * 測試快速操作的工具提示
     */
    public function test_quick_actions_tooltips()
    {
        $this->actingAs($this->admin);

        Livewire::test(QuickActions::class)
            ->assertSee('title="建立新的使用者帳號"') // 工具提示
            ->assertSee('title="建立新的角色"');
    }
}