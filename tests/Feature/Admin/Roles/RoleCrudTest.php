<?php

namespace Tests\Feature\Admin\Roles;

use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Livewire\Livewire;
use App\Livewire\Admin\Roles\RoleForm;
use App\Livewire\Admin\Roles\RoleDeleteModal;

/**
 * 角色 CRUD 操作測試
 */
class RoleCrudTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $adminUser;
    protected Role $adminRole;
    protected RoleRepositoryInterface $roleRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立管理員角色和使用者
        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理員',
            'description' => '系統管理員',
            'is_system_role' => true,
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'is_active' => true,
        ]);
        
        $this->adminUser->roles()->attach($this->adminRole);
        
        // 建立必要的權限
        $permissions = [
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete'
        ];
        
        foreach ($permissions as $permission) {
            Permission::create([
                'name' => $permission,
                'display_name' => $permission,
                'module' => 'roles'
            ]);
        }
        
        $this->adminRole->permissions()->attach(
            Permission::whereIn('name', $permissions)->pluck('id')
        );

        $this->roleRepository = app(RoleRepositoryInterface::class);
    }

    /** @test */
    public function 管理員可以檢視角色建立表單()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(RoleForm::class);

        $component->assertSet('isEditing', false)
            ->assertSet('name', '')
            ->assertSet('display_name', '')
            ->assertSet('description', '')
            ->assertSet('is_active', true)
            ->assertSee('建立新角色');
    }

    /** @test */
    public function 管理員可以建立新角色()
    {
        $this->actingAs($this->adminUser);

        $roleData = [
            'name' => 'editor',
            'display_name' => '編輯者',
            'description' => '內容編輯者',
            'is_active' => true,
        ];

        Livewire::test(RoleForm::class)
            ->set('name', $roleData['name'])
            ->set('display_name', $roleData['display_name'])
            ->set('description', $roleData['description'])
            ->set('is_active', $roleData['is_active'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('role-created');

        $this->assertDatabaseHas('roles', [
            'name' => $roleData['name'],
            'display_name' => $roleData['display_name'],
            'description' => $roleData['description'],
            'is_active' => $roleData['is_active'],
            'is_system_role' => false,
        ]);
    }

    /** @test */
    public function 角色名稱必須唯一()
    {
        $this->actingAs($this->adminUser);

        // 建立一個角色
        Role::create([
            'name' => 'existing_role',
            'display_name' => '已存在角色',
            'is_active' => true,
        ]);

        Livewire::test(RoleForm::class)
            ->set('name', 'existing_role')
            ->set('display_name', '新角色')
            ->call('save')
            ->assertHasErrors(['name' => 'unique']);
    }

    /** @test */
    public function 角色名稱必須符合格式要求()
    {
        $this->actingAs($this->adminUser);

        // 測試無效的角色名稱
        $invalidNames = [
            'Invalid Name',  // 包含空格
            'invalid-name',  // 包含連字符
            'Invalid123',    // 包含數字
            'INVALID',       // 大寫字母
        ];

        foreach ($invalidNames as $invalidName) {
            Livewire::test(RoleForm::class)
                ->set('name', $invalidName)
                ->set('display_name', '測試角色')
                ->call('save')
                ->assertHasErrors(['name' => 'regex']);
        }
    }

    /** @test */
    public function 管理員可以編輯現有角色()
    {
        $this->actingAs($this->adminUser);

        $role = Role::create([
            'name' => 'editor',
            'display_name' => '編輯者',
            'description' => '內容編輯者',
            'is_active' => true,
        ]);

        $updatedData = [
            'display_name' => '高級編輯者',
            'description' => '高級內容編輯者',
            'is_active' => false,
        ];

        Livewire::test(RoleForm::class, ['role' => $role])
            ->assertSet('isEditing', true)
            ->assertSet('name', $role->name)
            ->assertSet('display_name', $role->display_name)
            ->set('display_name', $updatedData['display_name'])
            ->set('description', $updatedData['description'])
            ->set('is_active', $updatedData['is_active'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('role-updated');

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => $role->name, // 名稱不應該改變
            'display_name' => $updatedData['display_name'],
            'description' => $updatedData['description'],
            'is_active' => $updatedData['is_active'],
        ]);
    }

    /** @test */
    public function 系統角色的名稱不能修改()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(RoleForm::class, ['role' => $this->adminRole])
            ->assertSet('isSystemRole', true)
            ->set('display_name', '超級管理員')
            ->call('save')
            ->assertHasNoErrors();

        // 確認名稱沒有改變
        $this->assertDatabaseHas('roles', [
            'id' => $this->adminRole->id,
            'name' => 'admin', // 原始名稱
            'display_name' => '超級管理員',
        ]);
    }

    /** @test */
    public function 可以設定角色的父角色()
    {
        $this->actingAs($this->adminUser);

        $parentRole = Role::create([
            'name' => 'manager',
            'display_name' => '管理者',
            'is_active' => true,
        ]);

        Livewire::test(RoleForm::class)
            ->set('name', 'supervisor')
            ->set('display_name', '主管')
            ->set('parent_id', $parentRole->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('roles', [
            'name' => 'supervisor',
            'parent_id' => $parentRole->id,
        ]);
    }

    /** @test */
    public function 不能設定會造成循環依賴的父角色()
    {
        $this->actingAs($this->adminUser);

        // 建立父子關係：parent -> child
        $parentRole = Role::create([
            'name' => 'parent',
            'display_name' => '父角色',
            'is_active' => true,
        ]);

        $childRole = Role::create([
            'name' => 'child',
            'display_name' => '子角色',
            'parent_id' => $parentRole->id,
            'is_active' => true,
        ]);

        // 嘗試將子角色設為父角色的父角色（造成循環）
        Livewire::test(RoleForm::class, ['role' => $parentRole])
            ->set('parent_id', $childRole->id)
            ->assertHasErrors('parent_id');
    }

    /** @test */
    public function 可以複製現有角色()
    {
        $this->actingAs($this->adminUser);

        $originalRole = Role::create([
            'name' => 'original',
            'display_name' => '原始角色',
            'description' => '原始描述',
            'is_active' => true,
        ]);

        // 為原始角色添加權限
        $permission = Permission::create([
            'name' => 'test.permission',
            'display_name' => '測試權限',
            'module' => 'test'
        ]);
        $originalRole->permissions()->attach($permission);

        Livewire::test(RoleForm::class, ['role' => $originalRole])
            ->call('duplicateRole')
            ->assertHasNoErrors()
            ->assertDispatched('role-duplicated');

        // 檢查是否建立了複製的角色
        $this->assertDatabaseHas('roles', [
            'display_name' => '原始角色 (複製)',
            'description' => '原始描述',
            'is_system_role' => false,
        ]);

        // 檢查權限是否也被複製
        $duplicatedRole = Role::where('display_name', '原始角色 (複製)')->first();
        $this->assertTrue($duplicatedRole->permissions->contains($permission));
    }

    /** @test */
    public function 管理員可以檢視角色刪除確認對話框()
    {
        $this->actingAs($this->adminUser);

        $role = Role::create([
            'name' => 'test_role',
            'display_name' => '測試角色',
            'is_active' => true,
        ]);

        Livewire::test(RoleDeleteModal::class)
            ->call('openDeleteModal', $role->id)
            ->assertSet('showModal', true)
            ->assertSet('roleToDelete.id', $role->id)
            ->assertSee('刪除角色確認')
            ->assertSee($role->display_name);
    }

    /** @test */
    public function 可以刪除沒有使用者關聯的角色()
    {
        $this->actingAs($this->adminUser);

        $role = Role::create([
            'name' => 'deletable_role',
            'display_name' => '可刪除角色',
            'is_active' => true,
        ]);

        Livewire::test(RoleDeleteModal::class)
            ->call('openDeleteModal', $role->id)
            ->assertSet('canDelete', true)
            ->set('confirmationText', $role->display_name)
            ->call('confirmDelete')
            ->assertHasNoErrors()
            ->assertDispatched('role-deleted');

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    /** @test */
    public function 不能刪除有使用者關聯的角色()
    {
        $this->actingAs($this->adminUser);

        $role = Role::create([
            'name' => 'used_role',
            'display_name' => '使用中角色',
            'is_active' => true,
        ]);

        // 建立使用者並關聯角色
        $user = User::factory()->create();
        $user->roles()->attach($role);

        Livewire::test(RoleDeleteModal::class)
            ->call('openDeleteModal', $role->id)
            ->assertSet('canDelete', false)
            ->assertSee('仍有 1 個使用者使用此角色');

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    /** @test */
    public function 不能刪除系統角色()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(RoleDeleteModal::class)
            ->call('openDeleteModal', $this->adminRole->id)
            ->assertSet('canDelete', false)
            ->assertSee('這是系統預設角色，無法刪除');

        $this->assertDatabaseHas('roles', ['id' => $this->adminRole->id]);
    }

    /** @test */
    public function 刪除角色時會清理子角色的父角色關聯()
    {
        $this->actingAs($this->adminUser);

        $parentRole = Role::create([
            'name' => 'parent_role',
            'display_name' => '父角色',
            'is_active' => true,
        ]);

        $childRole = Role::create([
            'name' => 'child_role',
            'display_name' => '子角色',
            'parent_id' => $parentRole->id,
            'is_active' => true,
        ]);

        Livewire::test(RoleDeleteModal::class)
            ->call('openDeleteModal', $parentRole->id)
            ->set('confirmationText', $parentRole->display_name)
            ->call('confirmDelete')
            ->assertDispatched('role-deleted');

        // 檢查子角色的父角色關聯是否被清除
        $childRole->refresh();
        $this->assertNull($childRole->parent_id);
    }

    /** @test */
    public function 刪除角色時會移除所有權限關聯()
    {
        $this->actingAs($this->adminUser);

        $role = Role::create([
            'name' => 'role_with_permissions',
            'display_name' => '有權限的角色',
            'is_active' => true,
        ]);

        $permission = Permission::create([
            'name' => 'test.permission',
            'display_name' => '測試權限',
            'module' => 'test'
        ]);

        $role->permissions()->attach($permission);

        Livewire::test(RoleDeleteModal::class)
            ->call('openDeleteModal', $role->id)
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
    public function 需要正確的權限才能執行角色操作()
    {
        // 建立沒有權限的使用者
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user);

        // 測試建立權限
        Livewire::test(RoleForm::class)
            ->assertForbidden();

        // 測試刪除權限
        $role = Role::create([
            'name' => 'test_role',
            'display_name' => '測試角色',
            'is_active' => true,
        ]);

        Livewire::test(RoleDeleteModal::class)
            ->call('openDeleteModal', $role->id)
            ->assertForbidden();
    }

    /** @test */
    public function 表單驗證規則正確運作()
    {
        $this->actingAs($this->adminUser);

        // 測試必填欄位
        Livewire::test(RoleForm::class)
            ->set('name', '')
            ->set('display_name', '')
            ->call('save')
            ->assertHasErrors(['name', 'display_name']);

        // 測試欄位長度限制
        Livewire::test(RoleForm::class)
            ->set('name', str_repeat('a', 51))
            ->set('display_name', str_repeat('b', 51))
            ->set('description', str_repeat('c', 256))
            ->call('save')
            ->assertHasErrors(['name', 'display_name', 'description']);
    }

    /** @test */
    public function 即時驗證功能正常運作()
    {
        $this->actingAs($this->adminUser);

        // 測試名稱即時驗證
        Livewire::test(RoleForm::class)
            ->set('name', 'Invalid Name')
            ->assertHasErrors(['name']);

        // 測試顯示名稱即時驗證
        Livewire::test(RoleForm::class)
            ->set('display_name', str_repeat('a', 51))
            ->assertHasErrors(['display_name']);
    }

    /** @test */
    public function 自動生成角色名稱功能正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(RoleForm::class)
            ->set('display_name', 'Content Editor');
        
        // 檢查名稱是否自動生成
        $generatedName = $component->get('name');
        $this->assertNotEmpty($generatedName);
        $this->assertStringContainsString('content', $generatedName);
    }
}