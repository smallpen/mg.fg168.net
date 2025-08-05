<?php

namespace Tests\Feature\Livewire\Admin\Users;

use App\Http\Livewire\Admin\Users\UserList;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * UserList 元件測試
 * 
 * 測試使用者列表的渲染、搜尋、篩選、分頁和權限控制
 */
class UserListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試角色
        $this->adminRole = Role::factory()->create(['name' => 'admin']);
        $this->userRole = Role::factory()->create(['name' => 'user']);
        
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

        Livewire::test(UserList::class)
            ->assertStatus(200)
            ->assertSee('使用者管理')
            ->assertSee('搜尋')
            ->assertSee('新增使用者');
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
            ->assertSee($users[1]->name)
            ->assertSee($users[2]->name);
    }

    /**
     * 測試搜尋功能
     */
    public function test_search_functionality()
    {
        $this->actingAs($this->admin);

        $user1 = User::factory()->create(['name' => 'John Doe', 'username' => 'johndoe']);
        $user2 = User::factory()->create(['name' => 'Jane Smith', 'username' => 'janesmith']);

        Livewire::test(UserList::class)
            ->set('search', 'John')
            ->assertSee('John Doe')
            ->assertDontSee('Jane Smith');

        Livewire::test(UserList::class)
            ->set('search', 'janesmith')
            ->assertSee('Jane Smith')
            ->assertDontSee('John Doe');
    }

    /**
     * 測試角色篩選功能
     */
    public function test_role_filter()
    {
        $this->actingAs($this->admin);

        $adminUser = User::factory()->create();
        $adminUser->roles()->attach($this->adminRole);

        $regularUser = User::factory()->create();
        $regularUser->roles()->attach($this->userRole);

        Livewire::test(UserList::class)
            ->set('roleFilter', $this->adminRole->id)
            ->assertSee($adminUser->name)
            ->assertDontSee($regularUser->name);
    }

    /**
     * 測試狀態篩選功能
     */
    public function test_status_filter()
    {
        $this->actingAs($this->admin);

        $activeUser = User::factory()->create(['is_active' => true]);
        $inactiveUser = User::factory()->create(['is_active' => false]);

        Livewire::test(UserList::class)
            ->set('statusFilter', 'active')
            ->assertSee($activeUser->name)
            ->assertDontSee($inactiveUser->name);

        Livewire::test(UserList::class)
            ->set('statusFilter', 'inactive')
            ->assertSee($inactiveUser->name)
            ->assertDontSee($activeUser->name);
    }

    /**
     * 測試分頁功能
     */
    public function test_pagination()
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
     * 測試排序功能
     */
    public function test_sorting()
    {
        $this->actingAs($this->admin);

        $userA = User::factory()->create(['name' => 'Alice']);
        $userB = User::factory()->create(['name' => 'Bob']);

        Livewire::test(UserList::class)
            ->call('sortBy', 'name')
            ->assertSeeInOrder(['Alice', 'Bob']);

        Livewire::test(UserList::class)
            ->call('sortBy', 'name')
            ->call('sortBy', 'name') // 第二次點擊反向排序
            ->assertSeeInOrder(['Bob', 'Alice']);
    }

    /**
     * 測試使用者啟用/停用功能
     */
    public function test_toggle_user_status()
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create(['is_active' => true]);

        Livewire::test(UserList::class)
            ->call('toggleUserStatus', $user->id)
            ->assertDispatched('user-status-updated');

        $this->assertFalse($user->fresh()->is_active);
    }

    /**
     * 測試權限控制 - 無權限使用者
     */
    public function test_unauthorized_access()
    {
        $unauthorizedUser = User::factory()->create();
        $this->actingAs($unauthorizedUser);

        Livewire::test(UserList::class)
            ->assertForbidden();
    }

    /**
     * 測試權限控制 - 只讀權限
     */
    public function test_read_only_permission()
    {
        $readOnlyUser = User::factory()->create();
        $readOnlyRole = Role::factory()->create(['name' => 'viewer']);
        $readOnlyUser->roles()->attach($readOnlyRole);
        
        $this->actingAs($readOnlyUser);

        Livewire::test(UserList::class)
            ->assertDontSee('新增使用者')
            ->assertDontSee('編輯')
            ->assertDontSee('刪除');
    }

    /**
     * 測試批量操作
     */
    public function test_bulk_operations()
    {
        $this->actingAs($this->admin);

        $users = User::factory()->count(3)->create();
        $userIds = $users->pluck('id')->toArray();

        Livewire::test(UserList::class)
            ->set('selectedUsers', $userIds)
            ->call('bulkDeactivate')
            ->assertDispatched('users-bulk-updated');

        foreach ($users as $user) {
            $this->assertFalse($user->fresh()->is_active);
        }
    }

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
     * 測試清除篩選
     */
    public function test_clear_filters()
    {
        $this->actingAs($this->admin);

        Livewire::test(UserList::class)
            ->set('search', 'test')
            ->set('roleFilter', $this->adminRole->id)
            ->set('statusFilter', 'active')
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('roleFilter', '')
            ->assertSet('statusFilter', '');
    }
}