<?php

namespace Tests\Feature\Livewire\Admin\Roles;

use App\Http\Livewire\Admin\Roles\RoleList;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * RoleList 元件測試
 * 
 * 測試角色列表的渲染、搜尋、篩選和權限控制
 */
class RoleListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試角色
        $this->adminRole = Role::factory()->create(['name' => 'admin']);
        
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

        Livewire::test(RoleList::class)
            ->assertStatus(200)
            ->assertSee('角色管理')
            ->assertSee('搜尋')
            ->assertSee('新增角色');
    }

    /**
     * 測試角色列表顯示
     */
    public function test_roles_list_display()
    {
        $this->actingAs($this->admin);

        $roles = Role::factory()->count(3)->create();

        Livewire::test(RoleList::class)
            ->assertSee($roles[0]->display_name)
            ->assertSee($roles[1]->display_name)
            ->assertSee($roles[2]->display_name);
    }

    /**
     * 測試搜尋功能
     */
    public function test_search_functionality()
    {
        $this->actingAs($this->admin);

        $role1 = Role::factory()->create(['name' => 'manager', 'display_name' => '管理員']);
        $role2 = Role::factory()->create(['name' => 'editor', 'display_name' => '編輯者']);

        Livewire::test(RoleList::class)
            ->set('search', '管理員')
            ->assertSee('管理員')
            ->assertDontSee('編輯者');

        Livewire::test(RoleList::class)
            ->set('search', 'editor')
            ->assertSee('編輯者')
            ->assertDontSee('管理員');
    }

    /**
     * 測試角色狀態顯示
     */
    public function test_role_status_display()
    {
        $this->actingAs($this->admin);

        $activeRole = Role::factory()->create(['is_active' => true]);
        $inactiveRole = Role::factory()->create(['is_active' => false]);

        Livewire::test(RoleList::class)
            ->assertSee('啟用')
            ->assertSee('停用');
    }

    /**
     * 測試使用者數量顯示
     */
    public function test_user_count_display()
    {
        $this->actingAs($this->admin);

        $role = Role::factory()->create();
        $users = User::factory()->count(3)->create();
        $role->users()->attach($users);

        Livewire::test(RoleList::class)
            ->assertSee('3 位使用者');
    }

    /**
     * 測試角色啟用/停用功能
     */
    public function test_toggle_role_status()
    {
        $this->actingAs($this->admin);

        $role = Role::factory()->create(['is_active' => true]);

        Livewire::test(RoleList::class)
            ->call('toggleRoleStatus', $role->id)
            ->assertDispatched('role-status-updated');

        $this->assertFalse($role->fresh()->is_active);
    }

    /**
     * 測試權限控制 - 無權限使用者
     */
    public function test_unauthorized_access()
    {
        $unauthorizedUser = User::factory()->create();
        $this->actingAs($unauthorizedUser);

        Livewire::test(RoleList::class)
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

        Livewire::test(RoleList::class)
            ->assertDontSee('新增角色')
            ->assertDontSee('編輯')
            ->assertDontSee('刪除');
    }

    /**
     * 測試排序功能
     */
    public function test_sorting()
    {
        $this->actingAs($this->admin);

        $roleA = Role::factory()->create(['display_name' => 'A角色']);
        $roleB = Role::factory()->create(['display_name' => 'B角色']);

        Livewire::test(RoleList::class)
            ->call('sortBy', 'display_name')
            ->assertSeeInOrder(['A角色', 'B角色']);

        Livewire::test(RoleList::class)
            ->call('sortBy', 'display_name')
            ->call('sortBy', 'display_name') // 第二次點擊反向排序
            ->assertSeeInOrder(['B角色', 'A角色']);
    }

    /**
     * 測試分頁功能
     */
    public function test_pagination()
    {
        $this->actingAs($this->admin);

        Role::factory()->count(25)->create();

        $component = Livewire::test(RoleList::class);
        
        // 檢查分頁連結存在
        $component->assertSee('下一頁');
        
        // 測試換頁
        $component->call('gotoPage', 2)
            ->assertSet('page', 2);
    }

    /**
     * 測試篩選功能
     */
    public function test_status_filter()
    {
        $this->actingAs($this->admin);

        $activeRole = Role::factory()->create(['is_active' => true]);
        $inactiveRole = Role::factory()->create(['is_active' => false]);

        Livewire::test(RoleList::class)
            ->set('statusFilter', 'active')
            ->assertSee($activeRole->display_name)
            ->assertDontSee($inactiveRole->display_name);

        Livewire::test(RoleList::class)
            ->set('statusFilter', 'inactive')
            ->assertSee($inactiveRole->display_name)
            ->assertDontSee($activeRole->display_name);
    }

    /**
     * 測試清除篩選
     */
    public function test_clear_filters()
    {
        $this->actingAs($this->admin);

        Livewire::test(RoleList::class)
            ->set('search', 'test')
            ->set('statusFilter', 'active')
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('statusFilter', '');
    }

    /**
     * 測試角色權限預覽
     */
    public function test_role_permissions_preview()
    {
        $this->actingAs($this->admin);

        $role = Role::factory()->create();
        $permissions = \App\Models\Permission::factory()->count(3)->create();
        $role->permissions()->attach($permissions);

        Livewire::test(RoleList::class)
            ->call('showPermissions', $role->id)
            ->assertSee($permissions[0]->display_name)
            ->assertSee($permissions[1]->display_name)
            ->assertSee($permissions[2]->display_name);
    }

    /**
     * 測試批量操作
     */
    public function test_bulk_operations()
    {
        $this->actingAs($this->admin);

        $roles = Role::factory()->count(3)->create(['is_active' => true]);
        $roleIds = $roles->pluck('id')->toArray();

        Livewire::test(RoleList::class)
            ->set('selectedRoles', $roleIds)
            ->call('bulkDeactivate')
            ->assertDispatched('roles-bulk-updated');

        foreach ($roles as $role) {
            $this->assertFalse($role->fresh()->is_active);
        }
    }

    /**
     * 測試即時搜尋
     */
    public function test_real_time_search()
    {
        $this->actingAs($this->admin);

        $role = Role::factory()->create(['display_name' => '測試角色']);

        Livewire::test(RoleList::class)
            ->set('search', '測試')
            ->assertSee('測試角色')
            ->set('search', '不存在')
            ->assertDontSee('測試角色')
            ->assertSee('沒有找到符合條件的角色');
    }

    /**
     * 測試角色複製功能
     */
    public function test_role_duplication()
    {
        $this->actingAs($this->admin);

        $role = Role::factory()->create(['name' => 'original']);
        $permissions = \App\Models\Permission::factory()->count(2)->create();
        $role->permissions()->attach($permissions);

        Livewire::test(RoleList::class)
            ->call('duplicateRole', $role->id)
            ->assertDispatched('role-duplicated');

        $this->assertDatabaseHas('roles', [
            'name' => 'original_copy',
            'display_name' => $role->display_name . ' (副本)'
        ]);
    }

    /**
     * 測試系統角色保護
     */
    public function test_system_role_protection()
    {
        $this->actingAs($this->admin);

        $systemRole = Role::factory()->create([
            'name' => 'super_admin',
            'is_system' => true
        ]);

        Livewire::test(RoleList::class)
            ->assertDontSee('刪除', false) // 系統角色不應該有刪除按鈕
            ->call('toggleRoleStatus', $systemRole->id)
            ->assertHasErrors(['role' => 'system_role']);
    }
}