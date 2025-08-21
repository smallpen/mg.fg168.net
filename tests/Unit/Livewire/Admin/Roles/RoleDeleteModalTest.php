<?php

namespace Tests\Unit\Livewire\Admin\Roles;

use App\Livewire\Admin\Roles\RoleDeleteModal;
use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * RoleDeleteModal 元件單元測試
 */
class RoleDeleteModalTest extends TestCase
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
            'is_system_role' => true,
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create(['is_active' => true]);
        $this->adminUser->roles()->attach($this->adminRole);
        
        // 建立權限
        Permission::create([
            'name' => 'roles.delete',
            'display_name' => '刪除角色',
            'module' => 'roles'
        ]);
        
        $this->adminRole->permissions()->attach(
            Permission::where('name', 'roles.delete')->first()
        );
    }

    /** @test */
    public function 元件初始狀態正確()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(RoleDeleteModal::class);

        $component->assertSet('showModal', false)
                 ->assertSet('roleToDelete', null)
                 ->assertSet('deleteChecks', [])
                 ->assertSet('canDelete', false)
                 ->assertSet('confirmationText', '')
                 ->assertSet('forceDelete', false);
    }

    /** @test */
    public function 可以正確開啟刪除模態()
    {
        $this->actingAs($this->adminUser);

        $role = Role::create([
            'name' => 'test_role',
            'display_name' => '測試角色',
            'is_active' => true,
        ]);

        $component = Livewire::test(RoleDeleteModal::class);

        $component->call('openDeleteModal', $role->id)
                 ->assertSet('showModal', true)
                 ->assertSet('roleToDelete.id', $role->id)
                 ->assertSet('confirmationText', '')
                 ->assertSet('forceDelete', false);

        // 檢查是否執行了刪除檢查
        $this->assertNotEmpty($component->get('deleteChecks'));
    }

    /** @test */
    public function 找不到角色時觸發錯誤事件()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(RoleDeleteModal::class)
            ->call('openDeleteModal', 999) // 不存在的角色 ID
            ->assertDispatched('role-delete-error');
    }

    /** @test */
    public function 可以正確關閉模態()
    {
        $this->actingAs($this->adminUser);

        $role = Role::create([
            'name' => 'test_role',
            'display_name' => '測試角色',
            'is_active' => true,
        ]);

        $component = Livewire::test(RoleDeleteModal::class);

        // 先開啟模態
        $component->call('openDeleteModal', $role->id);

        // 然後關閉
        $component->call('closeModal')
                 ->assertSet('showModal', false)
                 ->assertSet('roleToDelete', null)
                 ->assertSet('deleteChecks', [])
                 ->assertSet('canDelete', false)
                 ->assertSet('confirmationText', '')
                 ->assertSet('forceDelete', false);
    }

    /** @test */
    public function 系統角色刪除檢查正確()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(RoleDeleteModal::class);

        $component->call('openDeleteModal', $this->adminRole->id);

        $deleteChecks = $component->get('deleteChecks');
        
        $this->assertArrayHasKey('system_role', $deleteChecks);
        $this->assertEquals('error', $deleteChecks['system_role']['status']);
        $this->assertTrue($deleteChecks['system_role']['blocking']);
        $this->assertFalse($component->get('canDelete'));
        $this->assertEquals('系統角色受到保護', $component->get('deleteReason'));
    }

    /** @test */
    public function 有使用者關聯的角色刪除檢查正確()
    {
        $this->actingAs($this->adminUser);

        $role = Role::create([
            'name' => 'used_role',
            'display_name' => '使用中角色',
            'is_active' => true,
        ]);

        // 建立使用者並關聯角色
        $users = User::factory()->count(3)->create();
        foreach ($users as $user) {
            $user->roles()->attach($role);
        }

        $component = Livewire::test(RoleDeleteModal::class);

        $component->call('openDeleteModal', $role->id);

        $deleteChecks = $component->get('deleteChecks');
        
        $this->assertArrayHasKey('users', $deleteChecks);
        $this->assertEquals('error', $deleteChecks['users']['status']);
        $this->assertTrue($deleteChecks['users']['blocking']);
        $this->assertEquals(3, $deleteChecks['users']['count']);
        $this->assertFalse($component->get('canDelete'));
    }

    /** @test */
    public function 有子角色的角色刪除檢查正確()
    {
        $this->actingAs($this->adminUser);

        $parentRole = Role::create([
            'name' => 'parent_role',
            'display_name' => '父角色',
            'is_active' => true,
        ]);

        $childRoles = [];
        for ($i = 1; $i <= 2; $i++) {
            $childRoles[] = Role::create([
                'name' => "child_role_{$i}",
                'display_name' => "子角色{$i}",
                'parent_id' => $parentRole->id,
                'is_active' => true,
            ]);
        }

        $component = Livewire::test(RoleDeleteModal::class);

        $component->call('openDeleteModal', $parentRole->id);

        $deleteChecks = $component->get('deleteChecks');
        
        $this->assertArrayHasKey('children', $deleteChecks);
        $this->assertEquals('warning', $deleteChecks['children']['status']);
        $this->assertFalse($deleteChecks['children']['blocking']);
        $this->assertEquals(2, $deleteChecks['children']['count']);
    }

    /** @test */
    public function 有權限的角色刪除檢查正確()
    {
        $this->actingAs($this->adminUser);

        $role = Role::create([
            'name' => 'role_with_permissions',
            'display_name' => '有權限角色',
            'is_active' => true,
        ]);

        // 為角色添加權限
        $permissionIds = [];
        for ($i = 1; $i <= 5; $i++) {
            $permission = Permission::create([
                'name' => "test.permission.{$i}",
                'display_name' => "測試權限{$i}",
                'module' => 'test'
            ]);
            $permissionIds[] = $permission->id;
        }
        $role->permissions()->attach($permissionIds);

        $component = Livewire::test(RoleDeleteModal::class);

        $component->call('openDeleteModal', $role->id);

        $deleteChecks = $component->get('deleteChecks');
        
        $this->assertArrayHasKey('permissions', $deleteChecks);
        $this->assertEquals('info', $deleteChecks['permissions']['status']);
        $this->assertFalse($deleteChecks['permissions']['blocking']);
        $this->assertEquals(5, $deleteChecks['permissions']['count']);
    }

    /** @test */
    public function 可以刪除沒有阻塞問題的角色()
    {
        $this->actingAs($this->adminUser);

        $role = Role::create([
            'name' => 'deletable_role',
            'display_name' => '可刪除角色',
            'is_active' => true,
        ]);

        $component = Livewire::test(RoleDeleteModal::class);

        $component->call('openDeleteModal', $role->id)
                 ->assertSet('canDelete', true)
                 ->set('confirmationText', $role->display_name)
                 ->call('confirmDelete')
                 ->assertHasNoErrors()
                 ->assertDispatched('role-deleted');

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    /** @test */
    public function 確認文字不正確時無法刪除()
    {
        $this->actingAs($this->adminUser);

        $role = Role::create([
            'name' => 'test_role',
            'display_name' => '測試角色',
            'is_active' => true,
        ]);

        $component = Livewire::test(RoleDeleteModal::class);

        $component->call('openDeleteModal', $role->id)
                 ->set('confirmationText', '錯誤的名稱')
                 ->call('confirmDelete')
                 ->assertHasErrors('confirmation');

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    /** @test */
    public function 有阻塞問題且未強制刪除時無法刪除()
    {
        $this->actingAs($this->adminUser);

        $role = Role::create([
            'name' => 'used_role',
            'display_name' => '使用中角色',
            'is_active' => true,
        ]);

        // 建立使用者關聯
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $component = Livewire::test(RoleDeleteModal::class);

        $component->call('openDeleteModal', $role->id)
                 ->set('confirmationText', $role->display_name)
                 ->set('forceDelete', false)
                 ->call('confirmDelete')
                 ->assertHasErrors('delete');

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    /** @test */
    public function 強制刪除功能正常運作()
    {
        $this->actingAs($this->adminUser);

        $role = Role::create([
            'name' => 'force_delete_role',
            'display_name' => '強制刪除角色',
            'is_active' => true,
        ]);

        // 建立子角色
        $childRole = Role::create([
            'name' => 'child_role',
            'display_name' => '子角色',
            'parent_id' => $role->id,
            'is_active' => true,
        ]);

        $component = Livewire::test(RoleDeleteModal::class);

        $component->call('openDeleteModal', $role->id)
                 ->set('confirmationText', $role->display_name)
                 ->set('forceDelete', true)
                 ->call('confirmDelete')
                 ->assertHasNoErrors()
                 ->assertDispatched('role-deleted');

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
        
        // 檢查子角色的父角色關聯是否被清除
        $childRole->refresh();
        $this->assertNull($childRole->parent_id);
    }

    /** @test */
    public function 刪除時正確清理權限關聯()
    {
        $this->actingAs($this->adminUser);

        $role = Role::create([
            'name' => 'role_with_permissions',
            'display_name' => '有權限角色',
            'is_active' => true,
        ]);

        $permission = Permission::create([
            'name' => 'test.permission',
            'display_name' => '測試權限',
            'module' => 'test'
        ]);

        $role->permissions()->attach($permission);

        $component = Livewire::test(RoleDeleteModal::class);

        $component->call('openDeleteModal', $role->id)
                 ->set('confirmationText', $role->display_name)
                 ->call('confirmDelete')
                 ->assertDispatched('role-deleted');

        // 檢查權限關聯是否被移除
        $this->assertDatabaseMissing('role_permissions', [
            'role_id' => $role->id,
            'permission_id' => $permission->id,
        ]);
    }

    /** @test */
    public function 檢查狀態圖示和顏色正確()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(RoleDeleteModal::class);

        // 測試各種狀態的圖示和顏色
        $componentInstance = $component->instance();
        $this->assertEquals('heroicon-o-check-circle', $componentInstance->getCheckIcon('success'));
        $this->assertEquals('heroicon-o-exclamation-triangle', $componentInstance->getCheckIcon('warning'));
        $this->assertEquals('heroicon-o-x-circle', $componentInstance->getCheckIcon('error'));
        $this->assertEquals('heroicon-o-information-circle', $componentInstance->getCheckIcon('info'));

        $this->assertEquals('text-green-500', $componentInstance->getCheckColor('success'));
        $this->assertEquals('text-yellow-500', $componentInstance->getCheckColor('warning'));
        $this->assertEquals('text-red-500', $componentInstance->getCheckColor('error'));
        $this->assertEquals('text-blue-500', $componentInstance->getCheckColor('info'));
    }

    /** @test */
    public function 阻塞問題檢測正確()
    {
        $this->actingAs($this->adminUser);

        // 測試沒有阻塞問題的角色
        $normalRole = Role::create([
            'name' => 'normal_role',
            'display_name' => '普通角色',
            'is_active' => true,
        ]);

        $component = Livewire::test(RoleDeleteModal::class);
        $component->call('openDeleteModal', $normalRole->id);
        $this->assertFalse($component->get('hasBlockingIssues'));

        // 測試有阻塞問題的角色（系統角色）
        $component->call('openDeleteModal', $this->adminRole->id);
        $this->assertTrue($component->get('hasBlockingIssues'));
    }

    /** @test */
    public function 使用者列表和子角色列表正確顯示()
    {
        $this->actingAs($this->adminUser);

        $role = Role::create([
            'name' => 'test_role',
            'display_name' => '測試角色',
            'is_active' => true,
        ]);

        // 建立使用者關聯
        $users = User::factory()->count(3)->create();
        foreach ($users as $user) {
            $user->roles()->attach($role);
        }

        // 建立子角色
        $childRoles = [];
        for ($i = 1; $i <= 2; $i++) {
            $childRoles[] = Role::create([
                'name' => "child_{$i}",
                'display_name' => "子角色{$i}",
                'parent_id' => $role->id,
                'is_active' => true,
            ]);
        }

        $component = Livewire::test(RoleDeleteModal::class);
        $component->call('openDeleteModal', $role->id);

        $userList = $component->get('userList');
        $childrenList = $component->get('childrenList');

        $this->assertCount(3, $userList);
        $this->assertCount(2, $childrenList);
    }

    /** @test */
    public function 權限檢查正確運作()
    {
        // 測試沒有權限的使用者
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user);

        $role = Role::create([
            'name' => 'test_role',
            'display_name' => '測試角色',
            'is_active' => true,
        ]);

        Livewire::test(RoleDeleteModal::class)
            ->call('openDeleteModal', $role->id)
            ->assertForbidden();
    }
}