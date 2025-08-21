<?php

namespace Tests\Feature\Admin\Roles;

use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Livewire\Livewire;
use App\Livewire\Admin\Roles\RoleForm;
use App\Livewire\Admin\Roles\RoleDeleteModal;

/**
 * 角色管理整合測試
 * 
 * 測試角色 CRUD 操作的完整流程
 */
class RoleIntegrationTest extends TestCase
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
    }

    /** @test */
    public function 完整的角色管理流程測試()
    {
        $this->actingAs($this->adminUser);

        // 1. 建立新角色
        $roleData = [
            'name' => 'content_editor',
            'display_name' => '內容編輯者',
            'description' => '負責內容編輯的角色',
            'is_active' => true,
        ];

        $createComponent = Livewire::test(RoleForm::class)
            ->set('name', $roleData['name'])
            ->set('display_name', $roleData['display_name'])
            ->set('description', $roleData['description'])
            ->set('is_active', $roleData['is_active'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('role-created');

        // 驗證角色已建立
        $this->assertDatabaseHas('roles', [
            'name' => $roleData['name'],
            'display_name' => $roleData['display_name'],
            'description' => $roleData['description'],
            'is_active' => $roleData['is_active'],
            'is_system_role' => false,
        ]);

        $createdRole = Role::where('name', $roleData['name'])->first();
        $this->assertNotNull($createdRole);

        // 2. 編輯角色
        $updatedData = [
            'display_name' => '高級內容編輯者',
            'description' => '負責高級內容編輯的角色',
            'is_active' => true,
        ];

        Livewire::test(RoleForm::class, ['role' => $createdRole])
            ->set('display_name', $updatedData['display_name'])
            ->set('description', $updatedData['description'])
            ->set('is_active', $updatedData['is_active'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('role-updated');

        // 驗證角色已更新
        $this->assertDatabaseHas('roles', [
            'id' => $createdRole->id,
            'name' => $roleData['name'], // 名稱不應該改變
            'display_name' => $updatedData['display_name'],
            'description' => $updatedData['description'],
            'is_active' => $updatedData['is_active'],
        ]);

        // 3. 複製角色
        Livewire::test(RoleForm::class, ['role' => $createdRole])
            ->call('duplicateRole')
            ->assertHasNoErrors()
            ->assertDispatched('role-duplicated');

        // 驗證複製的角色已建立
        $duplicatedRole = Role::where('display_name', $updatedData['display_name'] . ' (複製)')->first();
        $this->assertNotNull($duplicatedRole);
        $this->assertEquals($updatedData['description'], $duplicatedRole->description);
        $this->assertFalse($duplicatedRole->is_system_role);

        // 4. 測試刪除檢查
        Livewire::test(RoleDeleteModal::class)
            ->call('openDeleteModal', $createdRole->id)
            ->assertSet('showModal', true)
            ->assertSet('canDelete', true);

        // 5. 刷新角色資料以取得最新的 display_name
        $createdRole->refresh();
        
        // 刪除角色
        Livewire::test(RoleDeleteModal::class)
            ->call('openDeleteModal', $createdRole->id)
            ->set('confirmationText', $createdRole->display_name)
            ->call('confirmDelete')
            ->assertHasNoErrors()
            ->assertDispatched('role-deleted');

        // 驗證角色已刪除
        $this->assertDatabaseMissing('roles', ['id' => $createdRole->id]);

        // 6. 驗證複製的角色仍然存在
        $this->assertDatabaseHas('roles', ['id' => $duplicatedRole->id]);
    }

    /** @test */
    public function 角色層級管理流程測試()
    {
        $this->actingAs($this->adminUser);

        // 建立父角色
        $parentRole = Role::create([
            'name' => 'manager',
            'display_name' => '管理者',
            'description' => '管理者角色',
            'is_active' => true,
        ]);

        // 建立子角色並設定父角色
        Livewire::test(RoleForm::class)
            ->set('name', 'supervisor')
            ->set('display_name', '主管')
            ->set('description', '主管角色')
            ->set('parent_id', $parentRole->id)
            ->set('is_active', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('role-created');

        // 驗證子角色已建立並設定了父角色
        $childRole = Role::where('name', 'supervisor')->first();
        $this->assertNotNull($childRole);
        $this->assertEquals($parentRole->id, $childRole->parent_id);

        // 測試循環依賴檢查
        Livewire::test(RoleForm::class, ['role' => $parentRole])
            ->set('parent_id', $childRole->id)
            ->assertHasErrors('parent_id');

        // 刪除父角色時應該清理子角色的父角色關聯
        Livewire::test(RoleDeleteModal::class)
            ->call('openDeleteModal', $parentRole->id)
            ->set('confirmationText', $parentRole->display_name)
            ->call('confirmDelete')
            ->assertDispatched('role-deleted');

        // 驗證子角色的父角色關聯已清除
        $childRole->refresh();
        $this->assertNull($childRole->parent_id);
    }

    /** @test */
    public function 系統角色保護機制測試()
    {
        $this->actingAs($this->adminUser);

        // 測試系統角色不能刪除
        Livewire::test(RoleDeleteModal::class)
            ->call('openDeleteModal', $this->adminRole->id)
            ->assertSet('canDelete', false)
            ->assertSee('這是系統預設角色，無法刪除');

        // 測試系統角色的名稱不能修改
        $originalName = $this->adminRole->name;
        
        Livewire::test(RoleForm::class, ['role' => $this->adminRole])
            ->set('display_name', '超級管理員')
            ->call('save')
            ->assertHasNoErrors();

        // 驗證名稱沒有改變
        $this->adminRole->refresh();
        $this->assertEquals($originalName, $this->adminRole->name);
        $this->assertEquals('超級管理員', $this->adminRole->display_name);
    }

    /** @test */
    public function 角色使用者關聯保護測試()
    {
        $this->actingAs($this->adminUser);

        // 建立一個角色
        $role = Role::create([
            'name' => 'test_role',
            'display_name' => '測試角色',
            'is_active' => true,
        ]);

        // 建立使用者並關聯角色
        $user = User::factory()->create();
        $user->roles()->attach($role);

        // 測試有使用者關聯的角色不能刪除
        Livewire::test(RoleDeleteModal::class)
            ->call('openDeleteModal', $role->id)
            ->assertSet('canDelete', false)
            ->assertSee('仍有 1 個使用者使用此角色');

        // 移除使用者關聯後應該可以刪除
        $user->roles()->detach($role);

        Livewire::test(RoleDeleteModal::class)
            ->call('openDeleteModal', $role->id)
            ->assertSet('canDelete', true)
            ->set('confirmationText', $role->display_name)
            ->call('confirmDelete')
            ->assertDispatched('role-deleted');

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    /** @test */
    public function 權限檢查整合測試()
    {
        // 建立沒有權限的使用者
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user);

        // 測試沒有建立權限時無法存取建立表單
        Livewire::test(RoleForm::class)
            ->assertForbidden();

        // 測試沒有刪除權限時無法存取刪除功能
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