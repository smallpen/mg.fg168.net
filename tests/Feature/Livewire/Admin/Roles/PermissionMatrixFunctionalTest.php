<?php

namespace Tests\Feature\Livewire\Admin\Roles;

use App\Livewire\Admin\Roles\PermissionMatrix;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * PermissionMatrix 功能測試
 */
class PermissionMatrixFunctionalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試角色
        $this->adminRole = Role::factory()->create(['name' => 'admin']);
        $this->editorRole = Role::factory()->create(['name' => 'editor']);
        
        // 建立測試權限
        $this->userPermissions = [
            Permission::factory()->create(['name' => 'users.view', 'display_name' => '檢視使用者', 'module' => 'users']),
            Permission::factory()->create(['name' => 'users.create', 'display_name' => '建立使用者', 'module' => 'users']),
            Permission::factory()->create(['name' => 'users.edit', 'display_name' => '編輯使用者', 'module' => 'users']),
            Permission::factory()->create(['name' => 'users.delete', 'display_name' => '刪除使用者', 'module' => 'users']),
        ];
        
        $this->rolePermissions = [
            Permission::factory()->create(['name' => 'roles.view', 'display_name' => '檢視角色', 'module' => 'roles']),
            Permission::factory()->create(['name' => 'roles.edit', 'display_name' => '編輯角色', 'module' => 'roles']),
        ];
        
        // 建立管理員使用者並給予權限
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($this->adminRole);
        $this->adminRole->permissions()->attach([
            $this->rolePermissions[0]->id, // roles.view
            $this->rolePermissions[1]->id, // roles.edit
        ]);
    }

    /**
     * 測試權限切換功能
     */
    public function test_toggle_permission()
    {
        $this->actingAs($this->admin);

        $permission = $this->userPermissions[0];

        $changeKey = "{$this->editorRole->id}_{$permission->id}";
        
        Livewire::test(PermissionMatrix::class)
            ->call('togglePermission', $this->editorRole->id, $permission->id)
            ->assertDispatched('permission-toggled')
            ->assertSet('showPreview', true)
            ->assertSee('待應用的權限變更');
    }

    /**
     * 測試模組權限批量指派
     */
    public function test_assign_module_to_role()
    {
        $this->actingAs($this->admin);

        Livewire::test(PermissionMatrix::class)
            ->call('assignModuleToRole', $this->editorRole->id, 'users')
            ->assertDispatched('module-assigned');
    }

    /**
     * 測試模組權限批量移除
     */
    public function test_revoke_module_from_role()
    {
        $this->actingAs($this->admin);

        // 先指派一些權限
        $this->editorRole->permissions()->attach(collect($this->userPermissions)->pluck('id')->toArray());

        Livewire::test(PermissionMatrix::class)
            ->call('revokeModuleFromRole', $this->editorRole->id, 'users')
            ->assertDispatched('module-revoked');
    }

    /**
     * 測試權限指派給所有角色
     */
    public function test_assign_permission_to_all_roles()
    {
        $this->actingAs($this->admin);

        $permission = $this->userPermissions[0];

        Livewire::test(PermissionMatrix::class)
            ->call('assignPermissionToAllRoles', $permission->id)
            ->assertDispatched('permission-assigned-to-all');
    }

    /**
     * 測試從所有角色移除權限
     */
    public function test_revoke_permission_from_all_roles()
    {
        $this->actingAs($this->admin);

        $permission = $this->userPermissions[0];
        
        // 先指派權限給角色
        $this->adminRole->permissions()->attach($permission->id);
        $this->editorRole->permissions()->attach($permission->id);

        Livewire::test(PermissionMatrix::class)
            ->call('revokePermissionFromAllRoles', $permission->id)
            ->assertDispatched('permission-revoked-from-all');
    }

    /**
     * 測試應用權限變更
     */
    public function test_apply_changes()
    {
        $this->actingAs($this->admin);

        $permission = $this->userPermissions[0];

        $component = Livewire::test(PermissionMatrix::class)
            ->call('togglePermission', $this->editorRole->id, $permission->id)
            ->call('applyChanges')
            ->assertDispatched('success')
            ->assertDispatched('permissions-applied');

        // 檢查權限是否真的被指派
        $this->assertTrue($this->editorRole->fresh()->permissions->contains($permission));
    }

    /**
     * 測試取消權限變更
     */
    public function test_cancel_changes()
    {
        $this->actingAs($this->admin);

        $permission = $this->userPermissions[0];

        Livewire::test(PermissionMatrix::class)
            ->call('togglePermission', $this->editorRole->id, $permission->id)
            ->call('cancelChanges')
            ->assertDispatched('info')
            ->assertDispatched('changes-cancelled')
            ->assertSet('permissionChanges', [])
            ->assertSet('showPreview', false);
    }

    /**
     * 測試搜尋功能
     */
    public function test_search_functionality()
    {
        $this->actingAs($this->admin);

        Livewire::test(PermissionMatrix::class)
            ->set('search', '使用者')
            ->assertDispatched('search-updated');
    }

    /**
     * 測試模組篩選功能
     */
    public function test_module_filter()
    {
        $this->actingAs($this->admin);

        Livewire::test(PermissionMatrix::class)
            ->set('moduleFilter', 'users')
            ->assertDispatched('module-filter-updated');
    }

    /**
     * 測試顯示模式切換
     */
    public function test_toggle_view_mode()
    {
        $this->actingAs($this->admin);

        Livewire::test(PermissionMatrix::class)
            ->assertSet('viewMode', 'matrix')
            ->call('toggleViewMode')
            ->assertSet('viewMode', 'list')
            ->call('toggleViewMode')
            ->assertSet('viewMode', 'matrix');
    }

    /**
     * 測試描述顯示切換
     */
    public function test_toggle_descriptions()
    {
        $this->actingAs($this->admin);

        Livewire::test(PermissionMatrix::class)
            ->assertSet('showDescriptions', false)
            ->call('toggleDescriptions')
            ->assertSet('showDescriptions', true);
    }

    /**
     * 測試清除篩選
     */
    public function test_clear_filters()
    {
        $this->actingAs($this->admin);

        Livewire::test(PermissionMatrix::class)
            ->set('search', 'test')
            ->set('moduleFilter', 'users')
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('moduleFilter', '')
            ->assertDispatched('filters-cleared');
    }

    /**
     * 測試角色權限檢查方法
     */
    public function test_role_has_permission()
    {
        $this->actingAs($this->admin);

        $permission = $this->userPermissions[0];
        
        // 指派權限給角色
        $this->editorRole->permissions()->attach($permission->id);

        $component = Livewire::test(PermissionMatrix::class);
        
        $this->assertTrue($component->instance()->roleHasPermission($this->editorRole->id, $permission->id));
        $this->assertFalse($component->instance()->roleHasPermission($this->adminRole->id, $permission->id));
    }

    /**
     * 測試模組權限檢查方法
     */
    public function test_role_has_module_permissions()
    {
        $this->actingAs($this->admin);

        // 指派所有使用者權限給編輯者角色
        $this->editorRole->permissions()->attach(collect($this->userPermissions)->pluck('id')->toArray());

        $component = Livewire::test(PermissionMatrix::class);
        
        $this->assertTrue($component->instance()->roleHasAllModulePermissions($this->editorRole->id, 'users'));
        $this->assertFalse($component->instance()->roleHasAllModulePermissions($this->adminRole->id, 'users'));
        
        // 移除一個權限
        $this->editorRole->permissions()->detach($this->userPermissions[0]->id);
        
        $this->assertFalse($component->instance()->roleHasAllModulePermissions($this->editorRole->id, 'users'));
        $this->assertTrue($component->instance()->roleHasSomeModulePermissions($this->editorRole->id, 'users'));
    }
}