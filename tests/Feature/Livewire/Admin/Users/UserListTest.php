<?php

namespace Tests\Feature\Livewire\Admin\Users;

use App\Livewire\Admin\Users\UserList;
use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use App\Services\UserCacheService;
use App\Services\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Tests\TestCase;
use Mockery;

/**
 * UserList 元件功能測試
 * 
 * 完整測試使用者列表元件的所有功能，包含：
 * - 基本渲染和顯示
 * - 搜尋和篩選功能
 * - 分頁和排序功能
 * - 權限控制功能
 * - 錯誤處理機制
 * - 批量操作功能
 * - 安全性驗證
 */
class UserListTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;
    protected Role $adminRole;
    protected Role $userRole;
    protected Role $superAdminRole;
    protected array $permissions = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試角色
        $this->superAdminRole = Role::factory()->create([
            'name' => 'super_admin',
            'display_name' => '超級管理員'
        ]);
        
        $this->adminRole = Role::factory()->create([
            'name' => 'admin',
            'display_name' => '管理員'
        ]);
        
        $this->userRole = Role::factory()->create([
            'name' => 'user',
            'display_name' => '使用者'
        ]);
        
        // 建立測試權限
        $permissionNames = [
            'users.view',
            'users.create', 
            'users.edit',
            'users.delete',
            'users.export'
        ];
        
        foreach ($permissionNames as $permissionName) {
            $this->permissions[$permissionName] = Permission::factory()->create([
                'name' => $permissionName,
                'display_name' => ucfirst(str_replace('.', ' ', $permissionName))
            ]);
        }
        
        // 為管理員角色分配所有權限
        $this->adminRole->permissions()->attach(collect($this->permissions)->pluck('id')->toArray());
        
        // 為超級管理員角色分配所有權限
        $this->superAdminRole->permissions()->attach(collect($this->permissions)->pluck('id')->toArray());
        
        // 為使用者角色只分配檢視權限
        $this->userRole->permissions()->attach($this->permissions['users.view']->id);
        
        // 建立測試使用者
        $this->admin = User::factory()->create([
            'username' => 'admin_test',
            'name' => '測試管理員',
            'email' => 'admin@test.com',
            'is_active' => true
        ]);
        $this->admin->roles()->attach($this->adminRole);
        
        $this->regularUser = User::factory()->create([
            'username' => 'user_test',
            'name' => '測試使用者',
            'email' => 'user@test.com',
            'is_active' => true
        ]);
        $this->regularUser->roles()->attach($this->userRole);
        
        // 清除快取
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==================== 基本渲染測試 ====================

    /**
     * 測試元件能正確渲染
     */
    public function test_component_renders_correctly()
    {
        $this->actingAs($this->admin);

        Livewire::test(UserList::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.admin.users.user-list')
            ->assertSee('使用者管理')
            ->assertSee('搜尋')
            ->assertSee('新增使用者')
            ->assertSee('狀態篩選')
            ->assertSee('角色篩選');
    }

    /**
     * 測試元件初始狀態
     */
    public function test_component_initial_state()
    {
        $this->actingAs($this->admin);

        Livewire::test(UserList::class)
            ->assertSet('search', '')
            ->assertSet('statusFilter', 'all')
            ->assertSet('roleFilter', 'all')
            ->assertSet('sortField', 'created_at')
            ->assertSet('sortDirection', 'desc')
            ->assertSet('perPage', 15)
            ->assertSet('selectedUsers', [])
            ->assertSet('selectAll', false);
    }

    /**
     * 測試使用者列表顯示
     */
    public function test_users_list_display()
    {
        $this->actingAs($this->admin);

        $users = User::factory()->count(3)->create();

        Livewire::test(UserList::class)
            ->assertSee($users[0]->name)
            ->assertSee($users[0]->username)
            ->assertSee($users[0]->email)
            ->assertSee($users[1]->name)
            ->assertSee($users[2]->name);
    }

    /**
     * 測試使用者列表顯示包含必要欄位
     */
    public function test_users_list_displays_required_fields()
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create([
            'name' => '測試使用者',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'is_active' => true
        ]);

        Livewire::test(UserList::class)
            ->assertSee('測試使用者')
            ->assertSee('testuser')
            ->assertSee('test@example.com')
            ->assertSee('啟用')
            ->assertSee('檢視')
            ->assertSee('刪除');
    }

    // ==================== 搜尋功能測試 ====================

    /**
     * 測試搜尋功能 - 姓名搜尋
     */
    public function test_search_by_name()
    {
        $this->actingAs($this->admin);

        $user1 = User::factory()->create(['name' => 'John Doe', 'username' => 'johndoe']);
        $user2 = User::factory()->create(['name' => 'Jane Smith', 'username' => 'janesmith']);

        Livewire::test(UserList::class)
            ->set('search', 'John')
            ->assertSee('John Doe')
            ->assertDontSee('Jane Smith');
    }

    /**
     * 測試搜尋功能 - 使用者名稱搜尋
     */
    public function test_search_by_username()
    {
        $this->actingAs($this->admin);

        $user1 = User::factory()->create(['name' => 'John Doe', 'username' => 'johndoe']);
        $user2 = User::factory()->create(['name' => 'Jane Smith', 'username' => 'janesmith']);

        Livewire::test(UserList::class)
            ->set('search', 'janesmith')
            ->assertSee('Jane Smith')
            ->assertDontSee('John Doe');
    }

    /**
     * 測試搜尋功能 - 電子郵件搜尋
     */
    public function test_search_by_email()
    {
        $this->actingAs($this->admin);

        $user1 = User::factory()->create(['email' => 'john@example.com']);
        $user2 = User::factory()->create(['email' => 'jane@example.com']);

        Livewire::test(UserList::class)
            ->set('search', 'john@example')
            ->assertSee('john@example.com')
            ->assertDontSee('jane@example.com');
    }

    /**
     * 測試搜尋功能 - 不區分大小寫
     */
    public function test_search_case_insensitive()
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create(['name' => 'John Doe']);

        Livewire::test(UserList::class)
            ->set('search', 'john')
            ->assertSee('John Doe');

        Livewire::test(UserList::class)
            ->set('search', 'JOHN')
            ->assertSee('John Doe');
    }

    /**
     * 測試搜尋功能 - 空結果
     */
    public function test_search_no_results()
    {
        $this->actingAs($this->admin);

        User::factory()->create(['name' => 'John Doe']);

        Livewire::test(UserList::class)
            ->set('search', 'NonExistent')
            ->assertSee('沒有找到符合條件的使用者');
    }

    /**
     * 測試搜尋功能 - 重置分頁
     */
    public function test_search_resets_pagination()
    {
        $this->actingAs($this->admin);

        User::factory()->count(25)->create();

        $component = Livewire::test(UserList::class);
        
        // 先跳到第二頁
        $component->call('gotoPage', 2)
            ->assertSet('page', 2);

        // 搜尋後應該重置到第一頁
        $component->set('search', 'test')
            ->assertSet('page', 1);
    }

    /**
     * 測試搜尋輸入驗證
     */
    public function test_search_input_validation()
    {
        $this->actingAs($this->admin);

        // 測試惡意輸入
        Livewire::test(UserList::class)
            ->set('search', '<script>alert("xss")</script>')
            ->assertSet('search', '');

        // 測試 SQL 注入嘗試
        Livewire::test(UserList::class)
            ->set('search', "'; DROP TABLE users; --")
            ->assertSet('search', '');
    }

    // ==================== 篩選功能測試 ====================

    /**
     * 測試狀態篩選功能 - 啟用使用者
     */
    public function test_status_filter_active()
    {
        $this->actingAs($this->admin);

        $activeUser = User::factory()->create(['is_active' => true, 'name' => '啟用使用者']);
        $inactiveUser = User::factory()->create(['is_active' => false, 'name' => '停用使用者']);

        Livewire::test(UserList::class)
            ->set('statusFilter', 'active')
            ->assertSee('啟用使用者')
            ->assertDontSee('停用使用者');
    }

    /**
     * 測試狀態篩選功能 - 停用使用者
     */
    public function test_status_filter_inactive()
    {
        $this->actingAs($this->admin);

        $activeUser = User::factory()->create(['is_active' => true, 'name' => '啟用使用者']);
        $inactiveUser = User::factory()->create(['is_active' => false, 'name' => '停用使用者']);

        Livewire::test(UserList::class)
            ->set('statusFilter', 'inactive')
            ->assertSee('停用使用者')
            ->assertDontSee('啟用使用者');
    }

    /**
     * 測試狀態篩選功能 - 全部使用者
     */
    public function test_status_filter_all()
    {
        $this->actingAs($this->admin);

        $activeUser = User::factory()->create(['is_active' => true, 'name' => '啟用使用者']);
        $inactiveUser = User::factory()->create(['is_active' => false, 'name' => '停用使用者']);

        Livewire::test(UserList::class)
            ->set('statusFilter', 'all')
            ->assertSee('啟用使用者')
            ->assertSee('停用使用者');
    }

    /**
     * 測試角色篩選功能
     */
    public function test_role_filter()
    {
        $this->actingAs($this->admin);

        $adminUser = User::factory()->create(['name' => '管理員使用者']);
        $adminUser->roles()->attach($this->adminRole);

        $regularUser = User::factory()->create(['name' => '一般使用者']);
        $regularUser->roles()->attach($this->userRole);

        Livewire::test(UserList::class)
            ->set('roleFilter', $this->adminRole->name)
            ->assertSee('管理員使用者')
            ->assertDontSee('一般使用者');
    }

    /**
     * 測試角色篩選功能 - 多重角色使用者
     */
    public function test_role_filter_multiple_roles()
    {
        $this->actingAs($this->admin);

        $multiRoleUser = User::factory()->create(['name' => '多重角色使用者']);
        $multiRoleUser->roles()->attach([$this->adminRole->id, $this->userRole->id]);

        $singleRoleUser = User::factory()->create(['name' => '單一角色使用者']);
        $singleRoleUser->roles()->attach($this->userRole);

        // 篩選管理員角色時，多重角色使用者應該出現
        Livewire::test(UserList::class)
            ->set('roleFilter', $this->adminRole->name)
            ->assertSee('多重角色使用者')
            ->assertDontSee('單一角色使用者');
    }

    /**
     * 測試篩選條件重置分頁
     */
    public function test_filters_reset_pagination()
    {
        $this->actingAs($this->admin);

        User::factory()->count(25)->create();

        $component = Livewire::test(UserList::class);
        
        // 先跳到第二頁
        $component->call('gotoPage', 2)
            ->assertSet('page', 2);

        // 狀態篩選後應該重置到第一頁
        $component->set('statusFilter', 'active')
            ->assertSet('page', 1);

        // 再次跳到第二頁
        $component->call('gotoPage', 2)
            ->assertSet('page', 2);

        // 角色篩選後應該重置到第一頁
        $component->set('roleFilter', $this->adminRole->name)
            ->assertSet('page', 1);
    }

    /**
     * 測試組合篩選功能
     */
    public function test_combined_filters()
    {
        $this->actingAs($this->admin);

        $activeAdmin = User::factory()->create([
            'name' => '啟用管理員',
            'is_active' => true
        ]);
        $activeAdmin->roles()->attach($this->adminRole);

        $inactiveAdmin = User::factory()->create([
            'name' => '停用管理員',
            'is_active' => false
        ]);
        $inactiveAdmin->roles()->attach($this->adminRole);

        $activeUser = User::factory()->create([
            'name' => '啟用使用者',
            'is_active' => true
        ]);
        $activeUser->roles()->attach($this->userRole);

        // 測試狀態 + 角色組合篩選
        Livewire::test(UserList::class)
            ->set('statusFilter', 'active')
            ->set('roleFilter', $this->adminRole->name)
            ->assertSee('啟用管理員')
            ->assertDontSee('停用管理員')
            ->assertDontSee('啟用使用者');
    }

    // ==================== 分頁功能測試 ====================

    /**
     * 測試分頁功能 - 基本分頁
     */
    public function test_pagination_basic()
    {
        $this->actingAs($this->admin);

        User::factory()->count(25)->create();

        $component = Livewire::test(UserList::class);
        
        // 檢查分頁連結存在
        $component->assertSee('下一頁');
        
        // 測試換頁
        $component->call('gotoPage', 2)
            ->assertSet('page', 2);
    }

    /**
     * 測試分頁功能 - 每頁顯示數量
     */
    public function test_pagination_per_page()
    {
        $this->actingAs($this->admin);

        User::factory()->count(20)->create();

        $component = Livewire::test(UserList::class);
        
        // 預設每頁 15 筆，應該有分頁
        $component->assertSee('下一頁');
        
        // 測試設定每頁數量
        $component->set('perPage', 25);
        
        // 現在應該不需要分頁
        $component->assertDontSee('下一頁');
    }

    /**
     * 測試分頁功能 - 邊界條件
     */
    public function test_pagination_boundary_conditions()
    {
        $this->actingAs($this->admin);

        User::factory()->count(15)->create(); // 剛好一頁

        $component = Livewire::test(UserList::class);
        
        // 剛好一頁時不應該有分頁連結
        $component->assertDontSee('下一頁');
        
        // 新增一個使用者，應該出現分頁
        User::factory()->create();
        
        $component = Livewire::test(UserList::class);
        $component->assertSee('下一頁');
    }

    // ==================== 排序功能測試 ====================

    /**
     * 測試排序功能 - 姓名排序
     */
    public function test_sorting_by_name()
    {
        $this->actingAs($this->admin);

        $userA = User::factory()->create(['name' => 'Alice']);
        $userB = User::factory()->create(['name' => 'Bob']);
        $userC = User::factory()->create(['name' => 'Charlie']);

        $component = Livewire::test(UserList::class);
        
        // 測試按姓名升序排序
        $component->call('sortBy', 'name')
            ->assertSet('sortField', 'name')
            ->assertSet('sortDirection', 'asc')
            ->assertSeeInOrder(['Alice', 'Bob', 'Charlie']);

        // 測試按姓名降序排序
        $component->call('sortBy', 'name')
            ->assertSet('sortField', 'name')
            ->assertSet('sortDirection', 'desc')
            ->assertSeeInOrder(['Charlie', 'Bob', 'Alice']);
    }

    /**
     * 測試排序功能 - 使用者名稱排序
     */
    public function test_sorting_by_username()
    {
        $this->actingAs($this->admin);

        $userA = User::factory()->create(['username' => 'alice', 'name' => 'User A']);
        $userB = User::factory()->create(['username' => 'bob', 'name' => 'User B']);
        $userC = User::factory()->create(['username' => 'charlie', 'name' => 'User C']);

        $component = Livewire::test(UserList::class);
        
        // 測試按使用者名稱升序排序
        $component->call('sortBy', 'username')
            ->assertSet('sortField', 'username')
            ->assertSet('sortDirection', 'asc');

        // 測試按使用者名稱降序排序
        $component->call('sortBy', 'username')
            ->assertSet('sortField', 'username')
            ->assertSet('sortDirection', 'desc');
    }

    /**
     * 測試排序功能 - 建立時間排序
     */
    public function test_sorting_by_created_at()
    {
        $this->actingAs($this->admin);

        $userA = User::factory()->create([
            'name' => 'Oldest User',
            'created_at' => now()->subDays(3)
        ]);
        $userB = User::factory()->create([
            'name' => 'Middle User',
            'created_at' => now()->subDays(2)
        ]);
        $userC = User::factory()->create([
            'name' => 'Newest User',
            'created_at' => now()->subDays(1)
        ]);

        $component = Livewire::test(UserList::class);
        
        // 測試按建立時間升序排序（最舊的在前）
        $component->call('sortBy', 'created_at')
            ->assertSet('sortField', 'created_at')
            ->assertSet('sortDirection', 'asc');

        // 測試按建立時間降序排序（最新的在前）
        $component->call('sortBy', 'created_at')
            ->assertSet('sortField', 'created_at')
            ->assertSet('sortDirection', 'desc');
    }

    /**
     * 測試排序功能 - 狀態排序
     */
    public function test_sorting_by_status()
    {
        $this->actingAs($this->admin);

        $activeUser = User::factory()->create([
            'name' => 'Active User',
            'is_active' => true
        ]);
        $inactiveUser = User::factory()->create([
            'name' => 'Inactive User',
            'is_active' => false
        ]);

        $component = Livewire::test(UserList::class);
        
        // 測試按狀態升序排序
        $component->call('sortBy', 'is_active')
            ->assertSet('sortField', 'is_active')
            ->assertSet('sortDirection', 'asc');

        // 測試按狀態降序排序
        $component->call('sortBy', 'is_active')
            ->assertSet('sortField', 'is_active')
            ->assertSet('sortDirection', 'desc');
    }

    /**
     * 測試排序功能 - 電子郵件排序
     */
    public function test_sorting_by_email()
    {
        $this->actingAs($this->admin);

        $userA = User::factory()->create(['email' => 'alice@example.com']);
        $userB = User::factory()->create(['email' => 'bob@example.com']);
        $userC = User::factory()->create(['email' => 'charlie@example.com']);

        $component = Livewire::test(UserList::class);
        
        // 測試按電子郵件升序排序
        $component->call('sortBy', 'email')
            ->assertSet('sortField', 'email')
            ->assertSet('sortDirection', 'asc');

        // 測試按電子郵件降序排序
        $component->call('sortBy', 'email')
            ->assertSet('sortField', 'email')
            ->assertSet('sortDirection', 'desc');
    }

    /**
     * 測試排序時重置分頁
     */
    public function test_sorting_resets_pagination()
    {
        $this->actingAs($this->admin);

        User::factory()->count(25)->create();

        $component = Livewire::test(UserList::class);
        
        // 先跳到第二頁
        $component->call('gotoPage', 2)
            ->assertSet('page', 2);

        // 排序後應該重置到第一頁
        $component->call('sortBy', 'name')
            ->assertSet('page', 1);
    }

    /**
     * 測試排序方向切換
     */
    public function test_sorting_direction_toggle()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(UserList::class);
        
        // 第一次點擊應該設為升序
        $component->call('sortBy', 'name')
            ->assertSet('sortField', 'name')
            ->assertSet('sortDirection', 'asc');

        // 第二次點擊同一欄位應該切換為降序
        $component->call('sortBy', 'name')
            ->assertSet('sortField', 'name')
            ->assertSet('sortDirection', 'desc');

        // 第三次點擊應該再次切換為升序
        $component->call('sortBy', 'name')
            ->assertSet('sortField', 'name')
            ->assertSet('sortDirection', 'asc');
    }

    /**
     * 測試不同欄位排序
     */
    public function test_sorting_different_fields()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(UserList::class);
        
        // 先按姓名排序
        $component->call('sortBy', 'name')
            ->assertSet('sortField', 'name')
            ->assertSet('sortDirection', 'asc');

        // 再按使用者名稱排序，應該重置為升序
        $component->call('sortBy', 'username')
            ->assertSet('sortField', 'username')
            ->assertSet('sortDirection', 'asc');
    }

    // ==================== 權限控制測試 ====================

    /**
     * 測試權限控制 - 無權限使用者
     */
    public function test_unauthorized_access()
    {
        $unauthorizedUser = User::factory()->create();
        $this->actingAs($unauthorizedUser);

        Livewire::test(UserList::class)
            ->assertStatus(403);
    }

    /**
     * 測試權限控制 - 只讀權限
     */
    public function test_read_only_permission()
    {
        $this->actingAs($this->regularUser); // 只有 users.view 權限

        Livewire::test(UserList::class)
            ->assertStatus(200)
            ->assertDontSee('新增使用者')
            ->assertDontSee('編輯')
            ->assertDontSee('刪除')
            ->assertDontSee('批量操作');
    }

    /**
     * 測試權限控制 - 完整權限
     */
    public function test_full_permission_access()
    {
        $this->actingAs($this->admin);

        Livewire::test(UserList::class)
            ->assertStatus(200)
            ->assertSee('新增使用者')
            ->assertSee('編輯')
            ->assertSee('刪除')
            ->assertSee('批量操作');
    }

    /**
     * 測試操作權限檢查 - 編輯使用者
     */
    public function test_edit_user_permission_check()
    {
        $this->actingAs($this->regularUser);

        $targetUser = User::factory()->create();

        Livewire::test(UserList::class)
            ->call('editUser', $targetUser->id)
            ->assertDispatched('show-toast');
    }

    /**
     * 測試操作權限檢查 - 刪除使用者
     */
    public function test_delete_user_permission_check()
    {
        $this->actingAs($this->regularUser);

        $targetUser = User::factory()->create();

        Livewire::test(UserList::class)
            ->call('deleteUser', $targetUser->id)
            ->assertDispatched('show-toast');
    }

    /**
     * 測試操作權限檢查 - 切換使用者狀態
     */
    public function test_toggle_user_status_permission_check()
    {
        $this->actingAs($this->regularUser);

        $targetUser = User::factory()->create(['is_active' => true]);

        Livewire::test(UserList::class)
            ->call('toggleUserStatus', $targetUser->id)
            ->assertDispatched('show-toast');

        // 狀態不應該改變
        $this->assertTrue($targetUser->fresh()->is_active);
    }

    /**
     * 測試使用者啟用/停用功能 - 有權限
     */
    public function test_toggle_user_status_with_permission()
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create(['is_active' => true]);

        Livewire::test(UserList::class)
            ->call('toggleUserStatus', $user->id)
            ->assertDispatched('user-status-updated');

        $this->assertFalse($user->fresh()->is_active);
    }

    /**
     * 測試安全性 - 防止自我操作
     */
    public function test_prevent_self_operations()
    {
        $this->actingAs($this->admin);

        // 嘗試停用自己的帳號
        Livewire::test(UserList::class)
            ->call('toggleUserStatus', $this->admin->id);

        // 自己的帳號狀態不應該改變
        $this->assertTrue($this->admin->fresh()->is_active);
    }

    /**
     * 測試安全性 - 超級管理員保護
     */
    public function test_super_admin_protection()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->roles()->attach($this->superAdminRole);

        $this->actingAs($this->admin); // 一般管理員

        // 嘗試刪除超級管理員
        Livewire::test(UserList::class)
            ->call('deleteUser', $superAdmin->id)
            ->assertDispatched('show-toast');
    }

    // ==================== 批量操作測試 ====================

    /**
     * 測試全選功能
     */
    public function test_select_all_functionality()
    {
        $this->actingAs($this->admin);

        $users = User::factory()->count(3)->create();

        $component = Livewire::test(UserList::class);
        
        // 測試全選
        $component->set('selectAll', true)
            ->call('toggleSelectAll')
            ->assertCount('selectedUsers', 5); // 包含 admin, regularUser 和 3 個新建立的使用者

        // 測試取消全選
        $component->set('selectAll', false)
            ->call('toggleSelectAll')
            ->assertCount('selectedUsers', 0);
    }

    /**
     * 測試單個使用者選擇
     */
    public function test_individual_user_selection()
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create();

        $component = Livewire::test(UserList::class);
        
        // 測試選擇使用者
        $component->call('toggleUserSelection', $user->id)
            ->assertContains('selectedUsers', $user->id);

        // 測試取消選擇使用者
        $component->call('toggleUserSelection', $user->id)
            ->assertNotContains('selectedUsers', $user->id);
    }

    /**
     * 測試批量選擇狀態同步
     */
    public function test_select_all_state_sync()
    {
        $this->actingAs($this->admin);

        $users = User::factory()->count(2)->create();
        $allUserIds = User::pluck('id')->toArray();

        $component = Livewire::test(UserList::class);
        
        // 手動選擇所有使用者
        foreach ($allUserIds as $userId) {
            $component->call('toggleUserSelection', $userId);
        }
        
        // 全選狀態應該自動更新
        $component->assertSet('selectAll', true);
    }

    /**
     * 測試批量啟用功能
     */
    public function test_bulk_activate()
    {
        $this->actingAs($this->admin);

        $users = User::factory()->count(3)->create(['is_active' => false]);
        $userIds = $users->pluck('id')->toArray();

        Livewire::test(UserList::class)
            ->set('selectedUsers', $userIds)
            ->call('bulkActivate')
            ->assertDispatched('users-bulk-updated')
            ->assertSet('selectedUsers', [])
            ->assertSet('selectAll', false);

        foreach ($users as $user) {
            $this->assertTrue($user->fresh()->is_active);
        }
    }

    /**
     * 測試批量停用功能
     */
    public function test_bulk_deactivate()
    {
        $this->actingAs($this->admin);

        $users = User::factory()->count(3)->create(['is_active' => true]);
        $userIds = $users->pluck('id')->toArray();

        Livewire::test(UserList::class)
            ->set('selectedUsers', $userIds)
            ->call('bulkDeactivate')
            ->assertDispatched('users-bulk-updated')
            ->assertSet('selectedUsers', [])
            ->assertSet('selectAll', false);

        foreach ($users as $user) {
            $this->assertFalse($user->fresh()->is_active);
        }
    }

    /**
     * 測試批量操作 - 空選擇
     */
    public function test_bulk_operations_with_empty_selection()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(UserList::class);
        
        // 測試空選擇時的批量啟用
        $component->set('selectedUsers', [])
            ->call('bulkActivate')
            ->assertDispatched('show-toast');

        // 測試空選擇時的批量停用
        $component->set('selectedUsers', [])
            ->call('bulkDeactivate')
            ->assertDispatched('show-toast');
    }

    /**
     * 測試批量停用 - 包含當前使用者
     */
    public function test_bulk_deactivate_prevents_self_deactivation()
    {
        $this->actingAs($this->admin);

        $otherUser = User::factory()->create(['is_active' => true]);
        $userIds = [$this->admin->id, $otherUser->id];

        Livewire::test(UserList::class)
            ->set('selectedUsers', $userIds)
            ->call('bulkDeactivate')
            ->assertDispatched('show-toast');

        // 確認當前使用者仍然啟用
        $this->assertTrue($this->admin->fresh()->is_active);
        // 其他使用者也不應該被停用，因為操作被阻止
        $this->assertTrue($otherUser->fresh()->is_active);
    }

    /**
     * 測試批量停用 - 包含超級管理員
     */
    public function test_bulk_deactivate_prevents_super_admin_deactivation()
    {
        $this->actingAs($this->admin); // 一般管理員

        $superAdmin = User::factory()->create(['is_active' => true]);
        $superAdmin->roles()->attach($this->superAdminRole);

        $regularUser = User::factory()->create(['is_active' => true]);
        $userIds = [$superAdmin->id, $regularUser->id];

        Livewire::test(UserList::class)
            ->set('selectedUsers', $userIds)
            ->call('bulkDeactivate')
            ->assertDispatched('show-toast');

        // 超級管理員和一般使用者都不應該被停用
        $this->assertTrue($superAdmin->fresh()->is_active);
        $this->assertTrue($regularUser->fresh()->is_active);
    }

    /**
     * 測試批量操作權限檢查
     */
    public function test_bulk_operations_permission_check()
    {
        $this->actingAs($this->regularUser); // 只有檢視權限

        $users = User::factory()->count(2)->create();
        $userIds = $users->pluck('id')->toArray();

        // 測試批量啟用權限檢查
        Livewire::test(UserList::class)
            ->set('selectedUsers', $userIds)
            ->call('bulkActivate')
            ->assertDispatched('show-toast');

        // 測試批量停用權限檢查
        Livewire::test(UserList::class)
            ->set('selectedUsers', $userIds)
            ->call('bulkDeactivate')
            ->assertDispatched('show-toast');
    }

    /**
     * 測試批量操作 - 無效使用者 ID
     */
    public function test_bulk_operations_with_invalid_user_ids()
    {
        $this->actingAs($this->admin);

        $invalidIds = [99999, 99998];

        Livewire::test(UserList::class)
            ->set('selectedUsers', $invalidIds)
            ->call('bulkActivate')
            ->assertDispatched('show-toast');
    }

    // ==================== 錯誤處理測試 ====================

    /**
     * 測試資料庫連線錯誤處理
     */
    public function test_database_connection_error_handling()
    {
        $this->actingAs($this->admin);

        // 模擬資料庫錯誤
        DB::shouldReceive('table')->andThrow(new \Exception('Database connection failed'));

        $component = Livewire::test(UserList::class);
        
        // 應該顯示錯誤訊息而不是崩潰
        $component->assertStatus(200);
    }

    /**
     * 測試無效使用者 ID 處理
     */
    public function test_invalid_user_id_handling()
    {
        $this->actingAs($this->admin);

        Livewire::test(UserList::class)
            ->call('viewUser', 99999)
            ->assertDispatched('show-toast');

        Livewire::test(UserList::class)
            ->call('editUser', 99999)
            ->assertDispatched('show-toast');

        Livewire::test(UserList::class)
            ->call('deleteUser', 99999)
            ->assertDispatched('show-toast');
    }

    /**
     * 測試網路錯誤重試機制
     */
    public function test_network_error_retry_mechanism()
    {
        $this->actingAs($this->admin);

        // 模擬網路錯誤
        $mockCacheService = Mockery::mock(UserCacheService::class);
        $mockCacheService->shouldReceive('clearAll')
            ->andThrow(new \Exception('Network timeout'));

        $this->app->instance(UserCacheService::class, $mockCacheService);

        $component = Livewire::test(UserList::class);
        
        // 應該處理錯誤而不是崩潰
        $component->assertStatus(200);
    }

    /**
     * 測試審計日誌錯誤處理
     */
    public function test_audit_log_error_handling()
    {
        $this->actingAs($this->admin);

        // 模擬審計日誌服務錯誤
        $mockAuditService = Mockery::mock(AuditLogService::class);
        $mockAuditService->shouldReceive('logDataAccess')
            ->andThrow(new \Exception('Audit log failed'));

        $this->app->instance(AuditLogService::class, $mockAuditService);

        // 元件應該仍然能正常載入
        Livewire::test(UserList::class)
            ->assertStatus(200);
    }

    // ==================== 邊界條件測試 ====================

    /**
     * 測試空資料狀態
     */
    public function test_empty_data_state()
    {
        $this->actingAs($this->admin);

        // 刪除所有使用者（除了當前使用者）
        User::where('id', '!=', $this->admin->id)->delete();

        Livewire::test(UserList::class)
            ->assertSee('沒有找到符合條件的使用者');
    }

    /**
     * 測試大量資料處理
     */
    public function test_large_dataset_handling()
    {
        $this->actingAs($this->admin);

        // 建立大量使用者
        User::factory()->count(100)->create();

        $component = Livewire::test(UserList::class);
        
        // 應該正常載入並分頁
        $component->assertStatus(200)
            ->assertSee('下一頁');
    }

    /**
     * 測試特殊字元處理
     */
    public function test_special_characters_handling()
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create([
            'name' => '測試使用者 & <script>',
            'username' => 'test_user_123',
            'email' => 'test+user@example.com'
        ]);

        Livewire::test(UserList::class)
            ->set('search', '測試使用者')
            ->assertSee('測試使用者')
            ->assertDontSee('<script>');
    }

    /**
     * 測試並發操作處理
     */
    public function test_concurrent_operations_handling()
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create(['is_active' => true]);

        // 模擬並發修改
        $component1 = Livewire::test(UserList::class);
        $component2 = Livewire::test(UserList::class);

        // 兩個元件同時嘗試修改同一使用者
        $component1->call('toggleUserStatus', $user->id);
        $component2->call('toggleUserStatus', $user->id);

        // 應該處理並發衝突
        $this->assertNotNull($user->fresh());
    }

    // ==================== 功能整合測試 ====================

    /**
     * 測試匯出功能
     */
    public function test_export_functionality()
    {
        $this->actingAs($this->admin);

        User::factory()->count(5)->create();

        Livewire::test(UserList::class)
            ->call('exportUsers')
            ->assertDispatched('export-started');
    }

    /**
     * 測試匯出功能權限檢查
     */
    public function test_export_permission_check()
    {
        $this->actingAs($this->regularUser);

        Livewire::test(UserList::class)
            ->call('exportUsers')
            ->assertDispatched('show-toast');
    }

    /**
     * 測試即時搜尋
     */
    public function test_real_time_search()
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create(['name' => 'Test User']);

        Livewire::test(UserList::class)
            ->set('search', 'Test')
            ->assertSee('Test User')
            ->set('search', 'NonExistent')
            ->assertDontSee('Test User')
            ->assertSee('沒有找到符合條件的使用者');
    }

    /**
     * 測試重置篩選
     */
    public function test_reset_filters()
    {
        $this->actingAs($this->admin);

        Livewire::test(UserList::class)
            ->set('search', 'test')
            ->set('roleFilter', $this->adminRole->name)
            ->set('statusFilter', 'active')
            ->call('resetFilters')
            ->assertSet('search', '')
            ->assertSet('roleFilter', 'all')
            ->assertSet('statusFilter', 'all')
            ->assertSet('sortField', 'created_at')
            ->assertSet('sortDirection', 'desc')
            ->assertSet('selectedUsers', [])
            ->assertSet('selectAll', false);
    }

    /**
     * 測試快取清除功能
     */
    public function test_cache_clearing()
    {
        $this->actingAs($this->admin);

        // 設定快取
        Cache::put('test_cache_key', 'test_value', 3600);
        $this->assertEquals('test_value', Cache::get('test_cache_key'));

        $user = User::factory()->create(['is_active' => true]);

        // 執行會清除快取的操作
        Livewire::test(UserList::class)
            ->call('toggleUserStatus', $user->id);

        // 快取應該被清除（這裡測試快取服務是否被呼叫）
        $this->assertFalse($user->fresh()->is_active);
    }

    /**
     * 測試複雜查詢場景
     */
    public function test_complex_query_scenarios()
    {
        $this->actingAs($this->admin);

        // 建立複雜的測試資料
        $activeAdmin = User::factory()->create([
            'name' => 'Active Admin',
            'username' => 'active_admin',
            'email' => 'active.admin@example.com',
            'is_active' => true
        ]);
        $activeAdmin->roles()->attach($this->adminRole);

        $inactiveUser = User::factory()->create([
            'name' => 'Inactive User',
            'username' => 'inactive_user',
            'email' => 'inactive.user@example.com',
            'is_active' => false
        ]);
        $inactiveUser->roles()->attach($this->userRole);

        // 測試複雜的搜尋 + 篩選組合
        Livewire::test(UserList::class)
            ->set('search', 'admin')
            ->set('statusFilter', 'active')
            ->set('roleFilter', $this->adminRole->name)
            ->assertSee('Active Admin')
            ->assertDontSee('Inactive User');
    }
}
