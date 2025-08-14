<?php

namespace Tests\Feature;

use App\Livewire\Admin\Users\UserList;
use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 排序和批量操作功能測試
 * 
 * 測試使用者列表的排序功能和批量操作功能
 */
class SortingAndBatchOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $adminRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立管理員角色
        $this->adminRole = Role::factory()->create(['name' => 'admin', 'display_name' => '管理員']);
        
        // 建立管理員使用者
        $this->admin = User::factory()->create(['username' => 'admin']);
        $this->admin->roles()->attach($this->adminRole);
        
        // 建立必要的權限
        $permissions = [
            ['name' => 'users.view', 'display_name' => '檢視使用者', 'module' => 'users'],
            ['name' => 'users.create', 'display_name' => '建立使用者', 'module' => 'users'],
            ['name' => 'users.edit', 'display_name' => '編輯使用者', 'module' => 'users'],
            ['name' => 'users.delete', 'display_name' => '刪除使用者', 'module' => 'users']
        ];
        
        foreach ($permissions as $permissionData) {
            $permission = Permission::factory()->create($permissionData);
            $this->adminRole->permissions()->attach($permission);
        }
    }

    /**
     * 測試按姓名排序功能
     */
    public function test_sorting_by_name()
    {
        $this->actingAs($this->admin);

        // 建立測試使用者
        $userA = User::factory()->create(['name' => 'Alice']);
        $userB = User::factory()->create(['name' => 'Bob']);
        $userC = User::factory()->create(['name' => 'Charlie']);

        $component = Livewire::test(UserList::class);
        
        // 測試按姓名升序排序
        $component->call('sortBy', 'name')
            ->assertSet('sortField', 'name')
            ->assertSet('sortDirection', 'asc');

        // 測試按姓名降序排序
        $component->call('sortBy', 'name')
            ->assertSet('sortField', 'name')
            ->assertSet('sortDirection', 'desc');
    }

    /**
     * 測試按使用者名稱排序功能
     */
    public function test_sorting_by_username()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(UserList::class);
        
        // 測試按使用者名稱排序
        $component->call('sortBy', 'username')
            ->assertSet('sortField', 'username')
            ->assertSet('sortDirection', 'asc');

        // 再次點擊應該反向排序
        $component->call('sortBy', 'username')
            ->assertSet('sortField', 'username')
            ->assertSet('sortDirection', 'desc');
    }

    /**
     * 測試按建立時間排序功能
     */
    public function test_sorting_by_created_at()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(UserList::class);
        
        // 測試按建立時間排序
        $component->call('sortBy', 'created_at')
            ->assertSet('sortField', 'created_at')
            ->assertSet('sortDirection', 'asc');
    }

    /**
     * 測試按狀態排序功能
     */
    public function test_sorting_by_status()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(UserList::class);
        
        // 測試按狀態排序
        $component->call('sortBy', 'is_active')
            ->assertSet('sortField', 'is_active')
            ->assertSet('sortDirection', 'asc');
    }

    /**
     * 測試全選功能
     */
    public function test_select_all_functionality()
    {
        $this->actingAs($this->admin);

        // 建立測試使用者
        User::factory()->count(3)->create();

        $component = Livewire::test(UserList::class);
        
        // 測試全選
        $component->set('selectAll', true)
            ->call('toggleSelectAll');
        
        // 檢查是否有選中的使用者
        $this->assertNotEmpty($component->get('selectedUsers'));

        // 測試取消全選
        $component->set('selectAll', false)
            ->call('toggleSelectAll')
            ->assertSet('selectedUsers', []);
    }

    /**
     * 測試單個使用者選擇功能
     */
    public function test_individual_user_selection()
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create();

        $component = Livewire::test(UserList::class);
        
        // 測試選擇使用者
        $component->call('toggleUserSelection', $user->id);
        $this->assertContains($user->id, $component->get('selectedUsers'));

        // 測試取消選擇使用者
        $component->call('toggleUserSelection', $user->id);
        $this->assertNotContains($user->id, $component->get('selectedUsers'));
    }

    /**
     * 測試批量啟用功能
     */
    public function test_bulk_activate()
    {
        $this->actingAs($this->admin);

        // 建立停用的測試使用者
        $users = User::factory()->count(3)->create(['is_active' => false]);
        $userIds = $users->pluck('id')->toArray();

        $component = Livewire::test(UserList::class);
        
        $component->set('selectedUsers', $userIds)
            ->call('bulkActivate')
            ->assertDispatched('users-bulk-updated')
            ->assertSet('selectedUsers', [])
            ->assertSet('selectAll', false);

        // 驗證使用者已被啟用
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

        // 建立啟用的測試使用者
        $users = User::factory()->count(3)->create(['is_active' => true]);
        $userIds = $users->pluck('id')->toArray();

        $component = Livewire::test(UserList::class);
        
        $component->set('selectedUsers', $userIds)
            ->call('bulkDeactivate')
            ->assertDispatched('users-bulk-updated')
            ->assertSet('selectedUsers', [])
            ->assertSet('selectAll', false);

        // 驗證使用者已被停用
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
            ->call('bulkActivate');
        
        // 空選擇時不應該觸發事件
        $component->assertNotDispatched('users-bulk-updated');

        // 測試空選擇時的批量停用
        $component->set('selectedUsers', [])
            ->call('bulkDeactivate');
        
        $component->assertNotDispatched('users-bulk-updated');
    }

    /**
     * 測試批量停用 - 防止停用當前使用者
     */
    public function test_bulk_deactivate_prevents_self_deactivation()
    {
        $this->actingAs($this->admin);

        $otherUser = User::factory()->create(['is_active' => true]);
        $userIds = [$this->admin->id, $otherUser->id];

        $component = Livewire::test(UserList::class);
        
        $component->set('selectedUsers', $userIds)
            ->call('bulkDeactivate')
            ->assertDispatched('show-toast');

        // 確認當前使用者仍然啟用
        $this->assertTrue($this->admin->fresh()->is_active);
    }

    /**
     * 測試排序時重置分頁
     */
    public function test_sorting_resets_pagination()
    {
        $this->actingAs($this->admin);

        // 建立足夠的使用者來觸發分頁
        User::factory()->count(25)->create();

        $component = Livewire::test(UserList::class);
        
        // 跳到第二頁
        $component->call('gotoPage', 2);
        
        // 排序後應該重置到第一頁
        $component->call('sortBy', 'name')
            ->assertSet('page', 1);
    }
}