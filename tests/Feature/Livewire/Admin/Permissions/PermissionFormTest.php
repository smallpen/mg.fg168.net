<?php

namespace Tests\Feature\Livewire\Admin\Permissions;

use App\Livewire\Admin\Permissions\PermissionForm;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 權限表單元件測試
 */
class PermissionFormTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立測試使用者和角色
        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '系統管理員',
            'description' => '系統管理員角色',
        ]);

        $this->adminUser = User::create([
            'username' => 'admin',
            'name' => '系統管理員',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->adminUser->roles()->attach($this->adminRole);

        // 建立必要的權限
        $permissions = [
            ['name' => 'permissions.view', 'display_name' => '檢視權限', 'module' => 'permissions', 'type' => 'view'],
            ['name' => 'permissions.create', 'display_name' => '建立權限', 'module' => 'permissions', 'type' => 'create'],
            ['name' => 'permissions.edit', 'display_name' => '編輯權限', 'module' => 'permissions', 'type' => 'edit'],
            ['name' => 'permissions.delete', 'display_name' => '刪除權限', 'module' => 'permissions', 'type' => 'delete'],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }

        $this->adminRole->permissions()->sync(Permission::all()->pluck('id'));

        $this->actingAs($this->adminUser);
    }

    /** @test */
    public function it_can_render_permission_form_component()
    {
        Livewire::test(PermissionForm::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.admin.permissions.permission-form');
    }

    /** @test */
    public function it_can_open_create_form()
    {
        Livewire::test(PermissionForm::class)
            ->call('openForm', 'create')
            ->assertSet('mode', 'create')
            ->assertSet('showForm', true)
            ->assertSet('name', '')
            ->assertSet('display_name', '')
            ->assertSet('description', '')
            ->assertSet('module', '')
            ->assertSet('type', '');
    }

    /** @test */
    public function it_can_open_edit_form()
    {
        $permission = Permission::create([
            'name' => 'test.permission',
            'display_name' => '測試權限',
            'description' => '測試用權限',
            'module' => 'users',
            'type' => 'view',
        ]);

        Livewire::test(PermissionForm::class)
            ->call('openForm', 'edit', $permission->id)
            ->assertSet('mode', 'edit')
            ->assertSet('showForm', true)
            ->assertSet('name', 'test.permission')
            ->assertSet('display_name', '測試權限')
            ->assertSet('description', '測試用權限')
            ->assertSet('module', 'users')
            ->assertSet('type', 'view');
    }

    /** @test */
    public function it_can_create_new_permission()
    {
        Livewire::test(PermissionForm::class)
            ->call('openForm', 'create')
            ->set('name', 'users.manage')
            ->set('display_name', '管理使用者')
            ->set('description', '管理使用者的權限')
            ->set('module', 'users')
            ->set('type', 'manage')
            ->call('save')
            ->assertDispatched('show-toast')
            ->assertDispatched('permission-saved');

        $this->assertDatabaseHas('permissions', [
            'name' => 'users.manage',
            'display_name' => '管理使用者',
            'description' => '管理使用者的權限',
            'module' => 'users',
            'type' => 'manage',
        ]);
    }

    /** @test */
    public function it_can_update_existing_permission()
    {
        $permission = Permission::create([
            'name' => 'test.permission',
            'display_name' => '測試權限',
            'description' => '測試用權限',
            'module' => 'users',
            'type' => 'view',
        ]);

        Livewire::test(PermissionForm::class)
            ->call('openForm', 'edit', $permission->id)
            ->set('display_name', '更新的測試權限')
            ->set('description', '更新的測試用權限')
            ->set('type', 'edit')
            ->call('save')
            ->assertDispatched('show-toast')
            ->assertDispatched('permission-saved');

        $this->assertDatabaseHas('permissions', [
            'id' => $permission->id,
            'name' => 'test.permission',
            'display_name' => '更新的測試權限',
            'description' => '更新的測試用權限',
            'module' => 'users',
            'type' => 'edit',
        ]);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        Livewire::test(PermissionForm::class)
            ->call('openForm', 'create')
            ->set('name', '')
            ->set('display_name', '')
            ->set('module', '')
            ->set('type', '')
            ->call('save')
            ->assertHasErrors([
                'name' => 'required',
                'display_name' => 'required',
                'module' => 'required',
                'type' => 'required',
            ]);
    }

    /** @test */
    public function it_validates_permission_name_format()
    {
        Livewire::test(PermissionForm::class)
            ->call('openForm', 'create')
            ->set('name', 'Invalid-Name!')
            ->set('display_name', '測試權限')
            ->set('module', 'users')
            ->set('type', 'view')
            ->call('save')
            ->assertHasErrors(['name' => 'regex']);
    }

    /** @test */
    public function it_validates_permission_name_uniqueness()
    {
        Permission::create([
            'name' => 'existing.permission',
            'display_name' => '現有權限',
            'module' => 'users',
            'type' => 'view',
        ]);

        Livewire::test(PermissionForm::class)
            ->call('openForm', 'create')
            ->set('name', 'existing.permission')
            ->set('display_name', '測試權限')
            ->set('module', 'users')
            ->set('type', 'view')
            ->call('save')
            ->assertHasErrors(['name' => 'unique']);
    }

    /** @test */
    public function it_validates_permission_type()
    {
        Livewire::test(PermissionForm::class)
            ->call('openForm', 'create')
            ->set('name', 'test.permission')
            ->set('display_name', '測試權限')
            ->set('module', 'users')
            ->set('type', 'invalid_type')
            ->call('save')
            ->assertHasErrors(['type' => 'in']);
    }

    /** @test */
    public function it_can_add_and_remove_dependencies()
    {
        $dependency1 = Permission::create([
            'name' => 'users.view',
            'display_name' => '檢視使用者',
            'module' => 'users',
            'type' => 'view',
        ]);

        $dependency2 = Permission::create([
            'name' => 'users.edit',
            'display_name' => '編輯使用者',
            'module' => 'users',
            'type' => 'edit',
        ]);

        $component = Livewire::test(PermissionForm::class)
            ->call('openForm', 'create');

        // 新增依賴
        $component->call('addDependency', $dependency1->id)
            ->assertSet('dependencies', [$dependency1->id]);

        $component->call('addDependency', $dependency2->id)
            ->assertSet('dependencies', [$dependency1->id, $dependency2->id]);

        // 移除依賴
        $component->call('removeDependency', $dependency1->id)
            ->assertSet('dependencies', [$dependency2->id]);
    }

    /** @test */
    public function it_prevents_circular_dependencies()
    {
        $permission1 = Permission::create([
            'name' => 'test.permission1',
            'display_name' => '測試權限1',
            'module' => 'users',
            'type' => 'view',
        ]);

        $permission2 = Permission::create([
            'name' => 'test.permission2',
            'display_name' => '測試權限2',
            'module' => 'users',
            'type' => 'edit',
        ]);

        // 建立 permission1 依賴 permission2
        $permission1->dependencies()->attach($permission2->id);

        // 嘗試建立 permission2 依賴 permission1（會形成循環）
        Livewire::test(PermissionForm::class)
            ->call('openForm', 'edit', $permission2->id)
            ->set('dependencies', [$permission1->id])
            ->call('save')
            ->assertHasErrors(['dependencies']);
    }

    /** @test */
    public function it_protects_system_permissions()
    {
        $systemPermission = Permission::create([
            'name' => 'system.core',
            'display_name' => '系統核心',
            'module' => 'system',
            'type' => 'manage',
        ]);

        Livewire::test(PermissionForm::class)
            ->call('openForm', 'edit', $systemPermission->id)
            ->assertSet('isSystemPermission', true)
            ->set('display_name', '更新的系統核心')
            ->call('save')
            ->assertDispatched('show-toast');

        // 系統權限的名稱和模組不應該被更新
        $this->assertDatabaseHas('permissions', [
            'id' => $systemPermission->id,
            'name' => 'system.core',
            'module' => 'system',
            'display_name' => '更新的系統核心',
        ]);
    }

    /** @test */
    public function it_auto_suggests_permission_name_based_on_module()
    {
        Livewire::test(PermissionForm::class)
            ->call('openForm', 'create')
            ->set('module', 'users')
            ->assertSet('name', 'users.');
    }

    /** @test */
    public function it_auto_suggests_display_name_based_on_module_and_type()
    {
        Livewire::test(PermissionForm::class)
            ->call('openForm', 'create')
            ->set('module', 'users')
            ->set('type', 'view')
            ->assertSet('display_name', '檢視使用者管理');
    }

    /** @test */
    public function it_can_cancel_form()
    {
        Livewire::test(PermissionForm::class)
            ->call('openForm', 'create')
            ->set('name', 'test.permission')
            ->call('cancel')
            ->assertSet('showForm', false)
            ->assertSet('name', '')
            ->assertDispatched('permission-form-closed');
    }

    /** @test */
    public function it_requires_create_permission_for_creating()
    {
        // 移除建立權限
        $this->adminRole->permissions()->detach(
            Permission::where('name', 'permissions.create')->first()->id
        );

        Livewire::test(PermissionForm::class)
            ->call('openForm', 'create')
            ->set('name', 'test.permission')
            ->set('display_name', '測試權限')
            ->set('module', 'users')
            ->set('type', 'view')
            ->call('save')
            ->assertDispatched('show-toast');

        $this->assertDatabaseMissing('permissions', [
            'name' => 'test.permission',
        ]);
    }

    /** @test */
    public function it_requires_edit_permission_for_editing()
    {
        $permission = Permission::create([
            'name' => 'test.permission',
            'display_name' => '測試權限',
            'module' => 'users',
            'type' => 'view',
        ]);

        // 移除編輯權限
        $this->adminRole->permissions()->detach(
            Permission::where('name', 'permissions.edit')->first()->id
        );

        Livewire::test(PermissionForm::class)
            ->call('openForm', 'edit', $permission->id)
            ->assertDispatched('show-toast');
    }

    /** @test */
    public function it_handles_non_existent_permission_gracefully()
    {
        Livewire::test(PermissionForm::class)
            ->call('openForm', 'edit', 99999)
            ->assertDispatched('show-toast');
    }

    /** @test */
    public function it_can_save_permission_with_dependencies()
    {
        $dependency = Permission::create([
            'name' => 'users.view',
            'display_name' => '檢視使用者',
            'module' => 'users',
            'type' => 'view',
        ]);

        Livewire::test(PermissionForm::class)
            ->call('openForm', 'create')
            ->set('name', 'users.edit')
            ->set('display_name', '編輯使用者')
            ->set('module', 'users')
            ->set('type', 'edit')
            ->set('dependencies', [$dependency->id])
            ->call('save')
            ->assertDispatched('show-toast')
            ->assertDispatched('permission-saved');

        $permission = Permission::where('name', 'users.edit')->first();
        $this->assertTrue($permission->dependencies->contains($dependency));
    }

    /** @test */
    public function it_validates_dependency_existence()
    {
        Livewire::test(PermissionForm::class)
            ->call('openForm', 'create')
            ->set('name', 'test.permission')
            ->set('display_name', '測試權限')
            ->set('module', 'users')
            ->set('type', 'view')
            ->set('dependencies', [99999]) // 不存在的權限 ID
            ->call('save')
            ->assertHasErrors(['dependencies.0' => 'exists']);
    }
}