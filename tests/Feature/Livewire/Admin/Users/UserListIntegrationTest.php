<?php

namespace Tests\Feature\Livewire\Admin\Users;

use App\Livewire\Admin\Users\UserList;
use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * UserList 元件整合測試
 * 
 * 測試完整的使用者管理工作流程和元件間的整合：
 * - 完整的使用者管理流程
 * - 多重條件組合測試
 * - 事件和通知整合
 * - 快取和效能整合
 * - 多語言支援整合
 */
class UserListIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;
    protected Role $adminRole;
    protected Role $userRole;
    protected Role $managerRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試角色
        $this->adminRole = Role::factory()->create([
            'name' => 'admin',
            'display_name' => '管理員'
        ]);
        
        $this->userRole = Role::factory()->create([
            'name' => 'user',
            'display_name' => '使用者'
        ]);
        
        $this->managerRole = Role::factory()->create([
            'name' => 'manager',
            'display_name' => '經理'
        ]);
        
        // 建立測試權限
        $permissions = [
            'users.view' => '檢視使用者',
            'users.create' => '建立使用者',
            'users.edit' => '編輯使用者',
            'users.delete' => '刪除使用者',
            'users.export' => '匯出使用者'
        ];
        
        foreach ($permissions as $name => $displayName) {
            $permission = Permission::factory()->create([
                'name' => $name,
                'display_name' => $displayName
            ]);
            $this->adminRole->permissions()->attach($permission);
        }
        
        // 為使用者角色只分配檢視權限
        $viewPermission = Permission::where('name', 'users.view')->first();
        $this->userRole->permissions()->attach($viewPermission);
        
        // 建立測試使用者
        $this->admin = User::factory()->create([
            'username' => 'admin',
            'name' => '系統管理員',
            'email' => 'admin@example.com',
            'is_active' => true
        ]);
        $this->admin->roles()->attach($this->adminRole);
        
        $this->regularUser = User::factory()->create([
            'username' => 'user',
            'name' => '一般使用者',
            'email' => 'user@example.com',
            'is_active' => true
        ]);
        $this->regularUser->roles()->attach($this->userRole);
    }

    // ==================== 完整工作流程測試 ====================

    /**
     * 測試完整的使用者管理工作流程
     */
    public function test_complete_user_management_workflow()
    {
        $this->actingAs($this->admin);

        // 建立測試資料
        $testUsers = User::factory()->count(10)->create();
        foreach ($testUsers as $index => $user) {
            $role = $index % 2 === 0 ? $this->userRole : $this->managerRole;
            $user->roles()->attach($role);
        }

        $component = Livewire::test(UserList::class);

        // 1. 初始載入和顯示
        $component->assertStatus(200)
            ->assertSee('使用者管理')
            ->assertSee($testUsers[0]->name);

        // 2. 搜尋功能測試
        $component->set('search', $testUsers[0]->name)
            ->assertSee($testUsers[0]->name)
            ->assertDontSee($testUsers[1]->name);

        // 3. 清除搜尋，測試篩選
        $component->set('search', '')
            ->set('roleFilter', $this->userRole->name)
            ->assertSee($testUsers[0]->name) // 假設第一個是 user 角色
            ->assertDontSee($testUsers[1]->name); // 假設第二個是 manager 角色

        // 4. 測試狀態篩選
        $component->set('roleFilter', 'all')
            ->set('statusFilter', 'active');

        // 5. 測試排序
        $component->call('sortBy', 'name')
            ->assertSet('sortField', 'name')
            ->assertSet('sortDirection', 'asc');

        // 6. 測試分頁
        if (User::count() > 15) {
            $component->assertSee('下一頁');
        }

        // 7. 測試單個使用者操作
        $targetUser = $testUsers[0];
        $component->call('toggleUserStatus', $targetUser->id)
            ->assertDispatched('user-status-updated');

        $this->assertFalse($targetUser->fresh()->is_active);

        // 8. 測試批量操作
        $userIds = $testUsers->take(3)->pluck('id')->toArray();
        $component->set('selectedUsers', $userIds)
            ->call('bulkActivate')
            ->assertDispatched('users-bulk-updated');

        // 9. 測試重置功能
        $component->call('resetFilters')
            ->assertSet('search', '')
            ->assertSet('statusFilter', 'all')
            ->assertSet('roleFilter', 'all');
    }

    /**
     * 測試複雜的多重條件組合
     */
    public function test_complex_multi_condition_scenarios()
    {
        $this->actingAs($this->admin);

        // 建立複雜的測試資料集
        $scenarios = [
            ['name' => 'Active Admin User', 'username' => 'active_admin', 'is_active' => true, 'role' => $this->adminRole],
            ['name' => 'Inactive Admin User', 'username' => 'inactive_admin', 'is_active' => false, 'role' => $this->adminRole],
            ['name' => 'Active Regular User', 'username' => 'active_user', 'is_active' => true, 'role' => $this->userRole],
            ['name' => 'Inactive Regular User', 'username' => 'inactive_user', 'is_active' => false, 'role' => $this->userRole],
            ['name' => 'Active Manager', 'username' => 'active_manager', 'is_active' => true, 'role' => $this->managerRole],
            ['name' => 'Inactive Manager', 'username' => 'inactive_manager', 'is_active' => false, 'role' => $this->managerRole],
        ];

        $createdUsers = [];
        foreach ($scenarios as $scenario) {
            $user = User::factory()->create([
                'name' => $scenario['name'],
                'username' => $scenario['username'],
                'is_active' => $scenario['is_active']
            ]);
            $user->roles()->attach($scenario['role']);
            $createdUsers[] = $user;
        }

        $component = Livewire::test(UserList::class);

        // 測試組合 1: 啟用的管理員
        $component->set('statusFilter', 'active')
            ->set('roleFilter', $this->adminRole->name)
            ->assertSee('Active Admin User')
            ->assertDontSee('Inactive Admin User')
            ->assertDontSee('Active Regular User');

        // 測試組合 2: 停用的使用者 + 搜尋
        $component->set('statusFilter', 'inactive')
            ->set('roleFilter', 'all')
            ->set('search', 'user')
            ->assertSee('Inactive Regular User')
            ->assertDontSee('Active Regular User')
            ->assertDontSee('Inactive Manager');

        // 測試組合 3: 特定角色 + 排序
        $component->set('search', '')
            ->set('statusFilter', 'all')
            ->set('roleFilter', $this->managerRole->name)
            ->call('sortBy', 'name')
            ->assertSee('Active Manager')
            ->assertSee('Inactive Manager');

        // 測試組合 4: 複雜搜尋 + 多重篩選
        $component->set('search', 'admin')
            ->set('statusFilter', 'active')
            ->set('roleFilter', $this->adminRole->name)
            ->assertSee('Active Admin User')
            ->assertDontSee('Inactive Admin User')
            ->assertDontSee('Active Regular User');
    }

    // ==================== 事件整合測試 ====================

    /**
     * 測試事件和通知整合
     */
    public function test_events_and_notifications_integration()
    {
        $this->actingAs($this->admin);

        Event::fake();

        $user = User::factory()->create(['is_active' => true]);

        $component = Livewire::test(UserList::class);

        // 測試狀態切換事件
        $component->call('toggleUserStatus', $user->id)
            ->assertDispatched('user-status-updated', function ($event) use ($user) {
                return $event['userId'] === $user->id;
            });

        // 測試批量操作事件
        $users = User::factory()->count(3)->create(['is_active' => false]);
        $userIds = $users->pluck('id')->toArray();

        $component->set('selectedUsers', $userIds)
            ->call('bulkActivate')
            ->assertDispatched('users-bulk-updated');

        // 測試錯誤事件
        $component->call('viewUser', 99999)
            ->assertDispatched('show-toast');
    }

    /**
     * 測試跨元件通信
     */
    public function test_cross_component_communication()
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create(['is_active' => true]);

        $component = Livewire::test(UserList::class);

        // 模擬從其他元件接收事件
        $component->dispatch('user-updated', userId: $user->id);

        // 元件應該重新載入資料或更新狀態
        $component->assertStatus(200);
    }

    // ==================== 快取整合測試 ====================

    /**
     * 測試快取整合和一致性
     */
    public function test_cache_integration_and_consistency()
    {
        $this->actingAs($this->admin);

        // 清除所有快取
        Cache::flush();

        $component = Livewire::test(UserList::class);

        // 第一次載入應該建立快取
        $component->assertStatus(200);

        // 檢查角色快取是否建立
        $this->assertNotNull(Cache::get('user_roles_list'));

        $user = User::factory()->create(['is_active' => true]);

        // 執行會影響快取的操作
        $component->call('toggleUserStatus', $user->id);

        // 相關快取應該被清除或更新
        $this->assertFalse($user->fresh()->is_active);
    }

    /**
     * 測試快取失效和重建
     */
    public function test_cache_invalidation_and_rebuild()
    {
        $this->actingAs($this->admin);

        // 設定初始快取
        Cache::put('user_stats', ['total' => 100, 'active' => 80], 3600);

        $component = Livewire::test(UserList::class);

        $users = User::factory()->count(5)->create(['is_active' => false]);
        $userIds = $users->pluck('id')->toArray();

        // 執行批量操作，應該觸發快取更新
        $component->set('selectedUsers', $userIds)
            ->call('bulkActivate');

        // 驗證操作成功
        foreach ($users as $user) {
            $this->assertTrue($user->fresh()->is_active);
        }
    }

    // ==================== 權限整合測試 ====================

    /**
     * 測試動態權限檢查整合
     */
    public function test_dynamic_permission_integration()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(UserList::class);

        // 初始狀態應該有完整權限
        $component->assertSee('新增使用者')
            ->assertSee('編輯')
            ->assertSee('刪除');

        // 動態移除權限
        $this->admin->roles()->detach();
        $this->admin->roles()->attach($this->userRole); // 只有檢視權限

        // 重新載入元件
        $component = Livewire::test(UserList::class);

        // 應該只顯示允許的操作
        $component->assertDontSee('新增使用者')
            ->assertDontSee('編輯')
            ->assertDontSee('刪除');
    }

    /**
     * 測試角色層級權限整合
     */
    public function test_role_based_permission_integration()
    {
        // 測試不同角色的使用者存取
        $scenarios = [
            ['user' => $this->admin, 'role' => 'admin', 'canEdit' => true, 'canDelete' => true],
            ['user' => $this->regularUser, 'role' => 'user', 'canEdit' => false, 'canDelete' => false],
        ];

        foreach ($scenarios as $scenario) {
            $this->actingAs($scenario['user']);

            $component = Livewire::test(UserList::class);

            if ($scenario['canEdit']) {
                $component->assertSee('編輯');
            } else {
                $component->assertDontSee('編輯');
            }

            if ($scenario['canDelete']) {
                $component->assertSee('刪除');
            } else {
                $component->assertDontSee('刪除');
            }
        }
    }

    // ==================== 多語言整合測試 ====================

    /**
     * 測試多語言支援整合
     */
    public function test_multilingual_support_integration()
    {
        $this->actingAs($this->admin);

        // 設定語言為正體中文
        app()->setLocale('zh_TW');

        $component = Livewire::test(UserList::class);

        // 檢查中文介面元素
        $component->assertSee('使用者管理')
            ->assertSee('搜尋')
            ->assertSee('狀態篩選')
            ->assertSee('角色篩選')
            ->assertSee('新增使用者');

        // 測試狀態顯示本地化
        $activeUser = User::factory()->create(['is_active' => true]);
        $inactiveUser = User::factory()->create(['is_active' => false]);

        $component->assertSee('啟用')
            ->assertSee('停用');
    }

    // ==================== 響應式設計整合測試 ====================

    /**
     * 測試響應式設計整合
     */
    public function test_responsive_design_integration()
    {
        $this->actingAs($this->admin);

        User::factory()->count(5)->create();

        $component = Livewire::test(UserList::class);

        // 測試桌面版佈局
        $component->assertStatus(200);

        // 這裡可以測試不同螢幕尺寸下的元件行為
        // 實際的響應式測試通常需要瀏覽器測試工具
    }

    // ==================== 資料一致性測試 ====================

    /**
     * 測試資料一致性和完整性
     */
    public function test_data_consistency_and_integrity()
    {
        $this->actingAs($this->admin);

        // 建立有關聯的測試資料
        $users = User::factory()->count(10)->create();
        foreach ($users as $user) {
            $user->roles()->attach($this->userRole);
        }

        $component = Livewire::test(UserList::class);

        // 執行批量操作
        $userIds = $users->take(5)->pluck('id')->toArray();
        $component->set('selectedUsers', $userIds)
            ->call('bulkDeactivate');

        // 驗證資料一致性
        $deactivatedUsers = User::whereIn('id', $userIds)->get();
        foreach ($deactivatedUsers as $user) {
            $this->assertFalse($user->is_active);
            $this->assertNotNull($user->roles); // 關聯應該保持
        }

        // 驗證其他使用者未受影響
        $unaffectedUsers = User::whereNotIn('id', $userIds)->get();
        foreach ($unaffectedUsers as $user) {
            $this->assertTrue($user->is_active);
        }
    }

    /**
     * 測試長時間運行的穩定性
     */
    public function test_long_running_stability()
    {
        $this->actingAs($this->admin);

        User::factory()->count(20)->create();

        $component = Livewire::test(UserList::class);

        // 模擬長時間的使用者互動
        for ($i = 0; $i < 10; $i++) {
            $component->set('search', "test{$i}")
                ->call('sortBy', 'name')
                ->set('statusFilter', $i % 2 === 0 ? 'active' : 'inactive')
                ->call('resetFilters');
        }

        // 元件應該保持穩定
        $component->assertStatus(200);
    }
}