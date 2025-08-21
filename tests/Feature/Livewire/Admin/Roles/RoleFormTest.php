<?php

namespace Tests\Feature\Livewire\Admin\Roles;

use App\Livewire\Admin\Roles\RoleForm;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * RoleForm 元件測試
 * 
 * 測試角色表單的渲染、驗證、建立、編輯和權限設定
 */
class RoleFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立角色管理相關權限
        $rolePermissions = [
            Permission::firstOrCreate(['name' => 'roles.view', 'display_name' => '檢視角色', 'module' => 'roles']),
            Permission::firstOrCreate(['name' => 'roles.create', 'display_name' => '建立角色', 'module' => 'roles']),
            Permission::firstOrCreate(['name' => 'roles.edit', 'display_name' => '編輯角色', 'module' => 'roles']),
            Permission::firstOrCreate(['name' => 'roles.delete', 'display_name' => '刪除角色', 'module' => 'roles']),
        ];
        
        // 建立一些測試權限（使用唯一名稱避免衝突）
        $this->permissions = collect();
        for ($i = 1; $i <= 5; $i++) {
            $this->permissions->push(
                Permission::firstOrCreate([
                    'name' => "test.permission.{$i}",
                    'display_name' => "測試權限 {$i}",
                    'module' => 'test'
                ])
            );
        }
        
        // 建立測試角色
        $this->adminRole = Role::factory()->create(['name' => 'test_admin_' . time()]);
        $this->adminRole->permissions()->attach(collect($rolePermissions)->pluck('id'));
        
        // 建立管理員使用者
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($this->adminRole);
    }

    /**
     * 測試建立角色表單渲染
     */
    public function test_create_form_renders_correctly()
    {
        $this->actingAs($this->admin);

        Livewire::test(RoleForm::class)
            ->assertStatus(200)
            ->assertSee('建立新角色')
            ->assertSee('角色名稱')
            ->assertSee('顯示名稱')
            ->assertSee('角色描述')
            ->assertSee('父角色設定')
            ->assertSee('啟用此角色');
    }

    /**
     * 測試編輯角色表單渲染
     */
    public function test_edit_form_renders_correctly()
    {
        $this->actingAs($this->admin);
        $role = Role::factory()->create();

        Livewire::test(RoleForm::class, ['roleId' => $role->id])
            ->assertStatus(200)
            ->assertSee('編輯角色')
            ->assertSet('name', $role->name)
            ->assertSet('displayName', $role->display_name)
            ->assertSet('description', $role->description);
    }

    /**
     * 測試表單驗證規則
     */
    public function test_form_validation_rules()
    {
        $this->actingAs($this->admin);

        Livewire::test(RoleForm::class)
            ->set('name', '')
            ->set('displayName', '')
            ->call('save')
            ->assertHasErrors([
                'name' => 'required',
                'displayName' => 'required'
            ]);
    }

    /**
     * 測試角色名稱唯一性驗證
     */
    public function test_role_name_uniqueness_validation()
    {
        $this->actingAs($this->admin);
        $existingRole = Role::factory()->create(['name' => 'existing_role']);

        Livewire::test(RoleForm::class)
            ->set('name', 'existing_role')
            ->set('displayName', '現有角色')
            ->call('save')
            ->assertHasErrors(['name' => 'unique']);
    }

    /**
     * 測試角色名稱格式驗證
     */
    public function test_role_name_format_validation()
    {
        $this->actingAs($this->admin);

        // 測試無效字元
        Livewire::test(RoleForm::class)
            ->set('name', 'invalid-name!')
            ->call('save')
            ->assertHasErrors(['name']);

        // 測試有效格式
        Livewire::test(RoleForm::class)
            ->set('name', 'valid_role_name')
            ->assertHasNoErrors(['name']);
    }

    /**
     * 測試成功建立角色
     */
    public function test_successful_role_creation()
    {
        $this->actingAs($this->admin);

        Livewire::test(RoleForm::class)
            ->set('name', 'new_role')
            ->set('displayName', '新角色')
            ->set('description', '這是一個新角色')
            ->set('selectedPermissions', [$this->permissions[0]->id, $this->permissions[1]->id])
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('role-saved')
            ->assertRedirect('/admin/roles');

        $this->assertDatabaseHas('roles', [
            'name' => 'new_role',
            'display_name' => '新角色',
            'description' => '這是一個新角色'
        ]);
    }

    /**
     * 測試成功編輯角色
     */
    public function test_successful_role_update()
    {
        $this->actingAs($this->admin);
        $role = Role::factory()->create();

        Livewire::test(RoleForm::class, ['roleId' => $role->id])
            ->set('displayName', '更新的角色名稱')
            ->set('description', '更新的描述')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('role-saved');

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'display_name' => '更新的角色名稱',
            'description' => '更新的描述'
        ]);
    }

    /**
     * 測試權限設定功能
     */
    public function test_permission_assignment()
    {
        $this->actingAs($this->admin);

        Livewire::test(RoleForm::class)
            ->set('name', 'permission_role')
            ->set('displayName', '權限角色')
            ->set('selectedPermissions', [$this->permissions[0]->id, $this->permissions[2]->id])
            ->call('save');

        $role = Role::where('name', 'permission_role')->first();
        $this->assertTrue($role->permissions->contains($this->permissions[0]));
        $this->assertTrue($role->permissions->contains($this->permissions[2]));
        $this->assertFalse($role->permissions->contains($this->permissions[1]));
    }

    /**
     * 測試權限分組顯示
     */
    public function test_permission_grouping()
    {
        $this->actingAs($this->admin);

        // 建立不同模組的權限
        $userPermissions = Permission::factory()->count(3)->create(['module' => 'users']);
        $rolePermissions = Permission::factory()->count(2)->create(['module' => 'roles']);

        Livewire::test(RoleForm::class)
            ->assertSee('使用者管理')
            ->assertSee('角色管理');
    }

    /**
     * 測試全選/取消全選功能
     */
    public function test_select_all_permissions()
    {
        $this->actingAs($this->admin);

        Livewire::test(RoleForm::class)
            ->call('selectAllPermissions')
            ->assertSet('selectedPermissions', $this->permissions->pluck('id')->toArray())
            ->call('deselectAllPermissions')
            ->assertSet('selectedPermissions', []);
    }

    /**
     * 測試模組權限全選
     */
    public function test_select_module_permissions()
    {
        $this->actingAs($this->admin);

        $userPermissions = Permission::factory()->count(3)->create(['module' => 'users']);

        Livewire::test(RoleForm::class)
            ->call('selectModulePermissions', 'users')
            ->assertSet('selectedPermissions', $userPermissions->pluck('id')->toArray());
    }

    /**
     * 測試即時驗證
     */
    public function test_real_time_validation()
    {
        $this->actingAs($this->admin);

        Livewire::test(RoleForm::class)
            ->set('name', 'ab')
            ->assertHasErrors(['name' => 'min'])
            ->set('name', 'valid_role_name')
            ->assertHasNoErrors(['name']);
    }

    /**
     * 測試表單重置功能
     */
    public function test_form_reset()
    {
        $this->actingAs($this->admin);

        Livewire::test(RoleForm::class)
            ->set('name', 'test_role')
            ->set('displayName', '測試角色')
            ->set('description', '測試描述')
            ->set('selectedPermissions', [$this->permissions[0]->id])
            ->call('resetForm')
            ->assertSet('name', '')
            ->assertSet('displayName', '')
            ->assertSet('description', '')
            ->assertSet('selectedPermissions', []);
    }

    /**
     * 測試權限控制 - 無權限使用者
     */
    public function test_unauthorized_access()
    {
        $unauthorizedUser = User::factory()->create();
        $this->actingAs($unauthorizedUser);

        Livewire::test(RoleForm::class)
            ->assertForbidden();
    }

    /**
     * 測試系統角色編輯限制
     */
    public function test_system_role_edit_restrictions()
    {
        $this->actingAs($this->admin);

        $systemRole = Role::factory()->create([
            'name' => 'super_admin',
            'is_system' => true
        ]);

        Livewire::test(RoleForm::class, ['roleId' => $systemRole->id])
            ->assertSee('系統角色')
            ->set('name', 'modified_name')
            ->call('save')
            ->assertHasErrors(['role' => 'system_role']);
    }

    /**
     * 測試權限預覽功能
     */
    public function test_permission_preview()
    {
        $this->actingAs($this->admin);

        Livewire::test(RoleForm::class)
            ->set('selectedPermissions', [$this->permissions[0]->id, $this->permissions[1]->id])
            ->call('previewPermissions')
            ->assertSee('權限預覽')
            ->assertSee($this->permissions[0]->display_name)
            ->assertSee($this->permissions[1]->display_name);
    }

    /**
     * 測試權限依賴檢查
     */
    public function test_permission_dependency_check()
    {
        $this->actingAs($this->admin);

        // 建立有依賴關係的權限
        $viewPermission = Permission::factory()->create(['name' => 'users.view']);
        $editPermission = Permission::factory()->create([
            'name' => 'users.edit',
            'depends_on' => [$viewPermission->id]
        ]);

        Livewire::test(RoleForm::class)
            ->set('selectedPermissions', [$editPermission->id])
            ->call('checkPermissionDependencies')
            ->assertSet('selectedPermissions', [$viewPermission->id, $editPermission->id])
            ->assertSee('已自動選擇相依權限');
    }

    /**
     * 測試角色範本功能
     */
    public function test_role_template()
    {
        $this->actingAs($this->admin);

        $templateRole = Role::factory()->create(['name' => 'editor_template']);
        $templateRole->permissions()->attach($this->permissions->take(3));

        Livewire::test(RoleForm::class)
            ->call('loadTemplate', $templateRole->id)
            ->assertSet('selectedPermissions', $templateRole->permissions->pluck('id')->toArray())
            ->assertSee('已載入角色範本');
    }

    /**
     * 測試權限搜尋功能
     */
    public function test_permission_search()
    {
        $this->actingAs($this->admin);

        $searchablePermission = Permission::factory()->create(['display_name' => '特殊權限']);

        Livewire::test(RoleForm::class)
            ->set('permissionSearch', '特殊')
            ->assertSee('特殊權限')
            ->set('permissionSearch', '不存在')
            ->assertDontSee('特殊權限');
    }
}