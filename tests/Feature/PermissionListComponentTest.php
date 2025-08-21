<?php

namespace Tests\Feature;

use App\Livewire\Admin\Permissions\PermissionList;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PermissionListComponentTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立管理員角色和使用者
        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理員',
            'description' => '系統管理員',
        ]);

        $this->adminUser = User::create([
            'username' => 'admin',
            'name' => '管理員',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->adminUser->roles()->attach($this->adminRole);

        // 建立權限管理相關權限
        $permissions = [
            ['name' => 'permissions.view', 'display_name' => '檢視權限', 'module' => 'permissions', 'type' => 'view'],
            ['name' => 'permissions.create', 'display_name' => '建立權限', 'module' => 'permissions', 'type' => 'create'],
            ['name' => 'permissions.edit', 'display_name' => '編輯權限', 'module' => 'permissions', 'type' => 'edit'],
            ['name' => 'permissions.delete', 'display_name' => '刪除權限', 'module' => 'permissions', 'type' => 'delete'],
        ];

        foreach ($permissions as $permissionData) {
            $permission = Permission::create($permissionData);
            $this->adminRole->permissions()->attach($permission);
        }

        $this->actingAs($this->adminUser);
    }

    /** @test */
    public function it_can_render_permission_list_component()
    {
        Livewire::test(PermissionList::class)
            ->assertStatus(200)
            ->assertSee('權限管理')
            ->assertSee('總權限數');
    }

    /** @test */
    public function it_displays_permissions_in_list_view()
    {
        // 建立測試權限
        Permission::create([
            'name' => 'users.view',
            'display_name' => '檢視使用者',
            'description' => '檢視使用者列表',
            'module' => 'users',
            'type' => 'view',
        ]);

        Livewire::test(PermissionList::class)
            ->assertSee('users.view')
            ->assertSee('檢視使用者')
            ->assertSee('users')
            ->assertSee('檢視');
    }

    /** @test */
    public function it_can_search_permissions()
    {
        Permission::create([
            'name' => 'users.view',
            'display_name' => '檢視使用者',
            'module' => 'users',
            'type' => 'view',
        ]);

        Permission::create([
            'name' => 'roles.view',
            'display_name' => '檢視角色',
            'module' => 'roles',
            'type' => 'view',
        ]);

        Livewire::test(PermissionList::class)
            ->set('search', 'users')
            ->assertSee('users.view')
            ->assertDontSee('roles.view');
    }

    /** @test */
    public function it_can_filter_by_module()
    {
        Permission::create([
            'name' => 'users.view',
            'display_name' => '檢視使用者',
            'module' => 'users',
            'type' => 'view',
        ]);

        Permission::create([
            'name' => 'roles.view',
            'display_name' => '檢視角色',
            'module' => 'roles',
            'type' => 'view',
        ]);

        Livewire::test(PermissionList::class)
            ->set('moduleFilter', 'users')
            ->assertSee('users.view')
            ->assertDontSee('roles.view');
    }

    /** @test */
    public function it_can_filter_by_type()
    {
        Permission::create([
            'name' => 'users.view',
            'display_name' => '檢視使用者',
            'module' => 'users',
            'type' => 'view',
        ]);

        Permission::create([
            'name' => 'users.create',
            'display_name' => '建立使用者',
            'module' => 'users',
            'type' => 'create',
        ]);

        Livewire::test(PermissionList::class)
            ->set('typeFilter', 'view')
            ->assertSee('users.view')
            ->assertDontSee('users.create');
    }

    /** @test */
    public function it_can_switch_view_modes()
    {
        Livewire::test(PermissionList::class)
            ->set('viewMode', 'grouped')
            ->assertSet('viewMode', 'grouped')
            ->set('viewMode', 'tree')
            ->assertSet('viewMode', 'tree')
            ->set('viewMode', 'list')
            ->assertSet('viewMode', 'list');
    }

    /** @test */
    public function it_can_sort_permissions()
    {
        Permission::create([
            'name' => 'a.permission',
            'display_name' => 'A Permission',
            'module' => 'a',
            'type' => 'view',
        ]);

        Permission::create([
            'name' => 'z.permission',
            'display_name' => 'Z Permission',
            'module' => 'z',
            'type' => 'view',
        ]);

        $component = Livewire::test(PermissionList::class);

        // 測試按名稱排序
        $component->call('sortBy', 'name')
            ->assertSet('sortField', 'name')
            ->assertSet('sortDirection', 'asc');

        // 再次點擊應該改變排序方向
        $component->call('sortBy', 'name')
            ->assertSet('sortDirection', 'desc');
    }

    /** @test */
    public function it_can_select_permissions()
    {
        $permission = Permission::create([
            'name' => 'test.permission',
            'display_name' => '測試權限',
            'module' => 'test',
            'type' => 'view',
        ]);

        Livewire::test(PermissionList::class)
            ->call('togglePermissionSelection', $permission->id)
            ->assertSet('selectedPermissions', [$permission->id]);
    }

    /** @test */
    public function it_can_reset_filters()
    {
        Livewire::test(PermissionList::class)
            ->set('search', 'test')
            ->set('moduleFilter', 'users')
            ->set('typeFilter', 'view')
            ->call('resetFilters')
            ->assertSet('search', '')
            ->assertSet('moduleFilter', 'all')
            ->assertSet('typeFilter', 'all');
    }

    /** @test */
    public function it_requires_permission_to_access()
    {
        // 建立沒有權限的使用者
        $user = User::create([
            'username' => 'testuser',
            'name' => '測試使用者',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        Livewire::test(PermissionList::class);
    }

    /** @test */
    public function it_shows_permission_statistics()
    {
        // 建立一些測試權限
        $usedPermission = Permission::create([
            'name' => 'used.permission',
            'display_name' => '已使用權限',
            'module' => 'test',
            'type' => 'view',
        ]);

        $unusedPermission = Permission::create([
            'name' => 'unused.permission',
            'display_name' => '未使用權限',
            'module' => 'test',
            'type' => 'view',
        ]);

        // 將一個權限分配給角色
        $this->adminRole->permissions()->attach($usedPermission);

        $component = Livewire::test(PermissionList::class);

        // 檢查統計資料是否正確顯示
        $stats = $component->get('stats');
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('used', $stats);
        $this->assertArrayHasKey('unused', $stats);
    }
}