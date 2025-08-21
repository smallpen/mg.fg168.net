<?php

namespace Tests\Unit\Livewire\Admin\Permissions;

use App\Livewire\Admin\Permissions\PermissionForm;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Repositories\PermissionRepository;
use App\Services\AuditLogService;
use App\Services\PermissionValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * PermissionForm 元件單元測試
 */
class PermissionFormTest extends TestCase
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
        
        // 建立權限管理相關權限
        $permissions = ['permissions.view', 'permissions.create', 'permissions.edit', 'permissions.delete'];
        foreach ($permissions as $permission) {
            Permission::create([
                'name' => $permission,
                'display_name' => $permission,
                'module' => 'permissions',
                'type' => 'view'
            ]);
        }
        
        $this->adminRole->permissions()->attach(
            Permission::whereIn('name', $permissions)->pluck('id')
        );
    }

    /** @test */
    public function 元件可以正確初始化為建立模式()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionForm::class);

        $component->assertSet('mode', 'create')
                 ->assertSet('permission', null)
                 ->assertSet('name', '')
                 ->assertSet('display_name', '')
                 ->assertSet('description', '')
                 ->assertSet('module', '')
                 ->assertSet('type', '')
                 ->assertSet('dependencies', [])
                 ->assertSet('showForm', false)
                 ->assertSet('isLoading', false)
                 ->assertSet('isSystemPermission', false);
    }

    /** @test */
    public function 可以開啟建立表單()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionForm::class);

        $component->dispatch('open-permission-form', mode: 'create')
                 ->assertSet('mode', 'create')
                 ->assertSet('showForm', true);
    }

    /** @test */
    public function 可以開啟編輯表單()
    {
        $this->actingAs($this->adminUser);

        $permission = Permission::create([
            'name' => 'test.permission',
            'display_name' => '測試權限',
            'description' => '測試用權限',
            'module' => 'test',
            'type' => 'view',
        ]);

        $component = Livewire::test(PermissionForm::class);

        $component->dispatch('open-permission-form', mode: 'edit', permissionId: $permission->id)
                 ->assertSet('mode', 'edit')
                 ->assertSet('showForm', true)
                 ->assertSet('name', $permission->name)
                 ->assertSet('display_name', $permission->display_name)
                 ->assertSet('description', $permission->description)
                 ->assertSet('module', $permission->module)
                 ->assertSet('type', $permission->type);
    }

    /** @test */
    public function 編輯不存在的權限顯示錯誤()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionForm::class);

        $component->dispatch('open-permission-form', mode: 'edit', permissionId: 99999)
                 ->assertDispatched('show-toast', [
                     'type' => 'error',
                     'message' => '無法開啟表單：權限不存在'
                 ]);
    }

    /** @test */
    public function 表單驗證規則正確運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionForm::class);

        $component->dispatch('open-permission-form', mode: 'create');

        // 測試必填欄位驗證
        $component->call('save')
                 ->assertHasErrors([
                     'name' => 'required',
                     'display_name' => 'required',
                     'module' => 'required',
                     'type' => 'required',
                 ]);

        // 測試權限名稱格式驗證
        $component->set('name', 'Invalid Name!')
                 ->set('display_name', '測試權限')
                 ->set('module', 'test')
                 ->set('type', 'view')
                 ->call('save')
                 ->assertHasErrors(['name' => 'regex']);

        // 測試有效的權限名稱
        $component->set('name', 'test.valid_permission')
                 ->call('save')
                 ->assertHasNoErrors(['name']);
    }

    /** @test */
    public function 權限名稱唯一性驗證正確運作()
    {
        $this->actingAs($this->adminUser);

        // 建立現有權限
        Permission::create([
            'name' => 'existing.permission',
            'display_name' => '現有權限',
            'module' => 'test',
            'type' => 'view',
        ]);

        $component = Livewire::test(PermissionForm::class);

        $component->dispatch('open-permission-form', mode: 'create');

        // 測試重複名稱
        $component->set('name', 'existing.permission')
                 ->set('display_name', '新權限')
                 ->set('module', 'test')
                 ->set('type', 'view')
                 ->call('save')
                 ->assertHasErrors(['name' => 'unique']);
    }

    /** @test */
    public function 可以成功建立權限()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionForm::class);

        $component->dispatch('open-permission-form', mode: 'create');

        $component->set('name', 'test.new_permission')
                 ->set('display_name', '新測試權限')
                 ->set('description', '這是一個新的測試權限')
                 ->set('module', 'test')
                 ->set('type', 'view')
                 ->call('save');

        // 檢查權限是否被建立
        $this->assertDatabaseHas('permissions', [
            'name' => 'test.new_permission',
            'display_name' => '新測試權限',
            'description' => '這是一個新的測試權限',
            'module' => 'test',
            'type' => 'view',
        ]);

        $component->assertDispatched('show-toast', [
            'type' => 'success',
            'message' => '權限「新測試權限」建立成功'
        ]);

        $component->assertDispatched('permission-saved');
        $component->assertSet('showForm', false);
    }

    /** @test */
    public function 可以成功更新權限()
    {
        $this->actingAs($this->adminUser);

        $permission = Permission::create([
            'name' => 'test.permission',
            'display_name' => '測試權限',
            'description' => '原始描述',
            'module' => 'test',
            'type' => 'view',
        ]);

        $component = Livewire::test(PermissionForm::class);

        $component->dispatch('open-permission-form', mode: 'edit', permissionId: $permission->id);

        $component->set('display_name', '更新的測試權限')
                 ->set('description', '更新的描述')
                 ->call('save');

        // 檢查權限是否被更新
        $this->assertDatabaseHas('permissions', [
            'id' => $permission->id,
            'name' => 'test.permission', // 名稱不變
            'display_name' => '更新的測試權限',
            'description' => '更新的描述',
            'module' => 'test',
            'type' => 'view',
        ]);

        $component->assertDispatched('show-toast', [
            'type' => 'success',
            'message' => '權限「更新的測試權限」更新成功'
        ]);
    }

    /** @test */
    public function 系統權限保護機制正常運作()
    {
        $this->actingAs($this->adminUser);

        $systemPermission = Permission::create([
            'name' => 'system.permission',
            'display_name' => '系統權限',
            'module' => 'system',
            'type' => 'manage',
            'is_system_permission' => true,
        ]);

        $component = Livewire::test(PermissionForm::class);

        $component->dispatch('open-permission-form', mode: 'edit', permissionId: $systemPermission->id);

        $component->assertSet('isSystemPermission', true);

        // 嘗試修改系統權限的名稱和模組（應該被忽略）
        $component->set('name', 'modified.system.permission')
                 ->set('module', 'modified')
                 ->set('display_name', '修改的系統權限')
                 ->call('save');

        // 檢查名稱和模組沒有被修改
        $this->assertDatabaseHas('permissions', [
            'id' => $systemPermission->id,
            'name' => 'system.permission', // 名稱不變
            'module' => 'system', // 模組不變
            'display_name' => '修改的系統權限', // 顯示名稱可以修改
        ]);
    }

    /** @test */
    public function 依賴關係管理功能正常運作()
    {
        $this->actingAs($this->adminUser);

        $permission1 = Permission::create([
            'name' => 'test.permission1',
            'display_name' => '測試權限1',
            'module' => 'test',
            'type' => 'view',
        ]);

        $permission2 = Permission::create([
            'name' => 'test.permission2',
            'display_name' => '測試權限2',
            'module' => 'test',
            'type' => 'edit',
        ]);

        $component = Livewire::test(PermissionForm::class);

        $component->dispatch('open-permission-form', mode: 'edit', permissionId: $permission2->id);

        // 新增依賴關係
        $component->call('addDependency', $permission1->id);

        $dependencies = $component->get('dependencies');
        $this->assertContains($permission1->id, $dependencies);

        // 移除依賴關係
        $component->call('removeDependency', $permission1->id);

        $dependencies = $component->get('dependencies');
        $this->assertNotContains($permission1->id, $dependencies);
    }

    /** @test */
    public function 循環依賴檢查正常運作()
    {
        $this->actingAs($this->adminUser);

        $permission1 = Permission::create([
            'name' => 'test.permission1',
            'display_name' => '測試權限1',
            'module' => 'test',
            'type' => 'view',
        ]);

        $permission2 = Permission::create([
            'name' => 'test.permission2',
            'display_name' => '測試權限2',
            'module' => 'test',
            'type' => 'edit',
        ]);

        // 建立 permission1 -> permission2 的依賴
        $permission1->dependencies()->attach($permission2->id);

        $component = Livewire::test(PermissionForm::class);

        $component->dispatch('open-permission-form', mode: 'edit', permissionId: $permission2->id);

        // 嘗試建立 permission2 -> permission1 的依賴（會造成循環）
        $component->call('addDependency', $permission1->id);

        // 應該不會新增依賴關係
        $dependencies = $component->get('dependencies');
        $this->assertNotContains($permission1->id, $dependencies);
    }

    /** @test */
    public function 模組變更時自動建議權限名稱()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionForm::class);

        $component->dispatch('open-permission-form', mode: 'create');

        // 設定模組
        $component->set('module', 'users');

        // 檢查名稱是否自動填入前綴
        $name = $component->get('name');
        $this->assertEquals('users.', $name);
    }

    /** @test */
    public function 類型變更時自動建議顯示名稱()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionForm::class);

        $component->dispatch('open-permission-form', mode: 'create');

        // 設定模組和類型
        $component->set('module', 'users')
                 ->set('type', 'view');

        // 檢查顯示名稱是否自動生成
        $displayName = $component->get('display_name');
        $this->assertStringContainsString('檢視', $displayName);
        $this->assertStringContainsString('使用者', $displayName);
    }

    /** @test */
    public function 取消操作正確關閉表單()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionForm::class);

        $component->dispatch('open-permission-form', mode: 'create');

        $component->set('name', 'test.permission')
                 ->set('display_name', '測試權限')
                 ->call('cancel')
                 ->assertSet('showForm', false)
                 ->assertSet('name', '')
                 ->assertSet('display_name', '');

        $component->assertDispatched('permission-form-closed');
    }

    /** @test */
    public function 沒有建立權限的使用者無法建立權限()
    {
        $user = User::factory()->create(['is_active' => true]);
        $role = Role::create([
            'name' => 'viewer',
            'display_name' => '檢視者',
            'is_active' => true,
        ]);
        
        $viewPermission = Permission::where('name', 'permissions.view')->first();
        $role->permissions()->attach($viewPermission);
        $user->roles()->attach($role);

        $this->actingAs($user);

        $component = Livewire::test(PermissionForm::class);

        $component->dispatch('open-permission-form', mode: 'create')
                 ->assertDispatched('show-toast', [
                     'type' => 'error',
                     'message' => '無法開啟表單：您沒有執行此操作的權限'
                 ]);
    }

    /** @test */
    public function 沒有編輯權限的使用者無法編輯權限()
    {
        $user = User::factory()->create(['is_active' => true]);
        $role = Role::create([
            'name' => 'viewer',
            'display_name' => '檢視者',
            'is_active' => true,
        ]);
        
        $viewPermission = Permission::where('name', 'permissions.view')->first();
        $role->permissions()->attach($viewPermission);
        $user->roles()->attach($role);

        $permission = Permission::create([
            'name' => 'test.permission',
            'display_name' => '測試權限',
            'module' => 'test',
            'type' => 'view',
        ]);

        $this->actingAs($user);

        $component = Livewire::test(PermissionForm::class);

        $component->dispatch('open-permission-form', mode: 'edit', permissionId: $permission->id)
                 ->assertDispatched('show-toast', [
                     'type' => 'error',
                     'message' => '無法開啟表單：您沒有編輯權限的權限'
                 ]);
    }

    /** @test */
    public function 表單載入時正確載入可用權限()
    {
        $this->actingAs($this->adminUser);

        // 建立一些測試權限
        $permission1 = Permission::create([
            'name' => 'test.permission1',
            'display_name' => '測試權限1',
            'module' => 'test',
            'type' => 'view',
        ]);

        $permission2 = Permission::create([
            'name' => 'test.permission2',
            'display_name' => '測試權限2',
            'module' => 'test',
            'type' => 'edit',
        ]);

        $component = Livewire::test(PermissionForm::class);

        $component->dispatch('open-permission-form', mode: 'edit', permissionId: $permission1->id);

        $availablePermissions = $component->get('availablePermissions');
        
        // 應該包含其他權限但不包含自己
        $this->assertTrue($availablePermissions->contains('id', $permission2->id));
        $this->assertFalse($availablePermissions->contains('id', $permission1->id));
    }

    /** @test */
    public function 依賴權限資訊正確顯示()
    {
        $this->actingAs($this->adminUser);

        $permission1 = Permission::create([
            'name' => 'test.permission1',
            'display_name' => '測試權限1',
            'module' => 'test',
            'type' => 'view',
        ]);

        $component = Livewire::test(PermissionForm::class);

        $component->dispatch('open-permission-form', mode: 'create');

        $dependencyInfo = $component->getDependencyInfo($permission1->id);

        $this->assertIsArray($dependencyInfo);
        $this->assertEquals($permission1->id, $dependencyInfo['id']);
        $this->assertEquals($permission1->name, $dependencyInfo['name']);
        $this->assertEquals($permission1->display_name, $dependencyInfo['display_name']);
        $this->assertEquals($permission1->module, $dependencyInfo['module']);
        $this->assertEquals($permission1->type, $dependencyInfo['type']);
    }

    /** @test */
    public function 不存在的依賴權限返回null()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionForm::class);

        $component->dispatch('open-permission-form', mode: 'create');

        $dependencyInfo = $component->getDependencyInfo(99999);

        $this->assertNull($dependencyInfo);
    }

    /** @test */
    public function 計算屬性返回正確的值()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionForm::class);

        // 測試建立模式
        $component->dispatch('open-permission-form', mode: 'create');
        $this->assertEquals('建立權限', $component->get('formTitle'));
        $this->assertEquals('建立', $component->get('saveButtonText'));

        // 測試編輯模式
        $permission = Permission::create([
            'name' => 'test.permission',
            'display_name' => '測試權限',
            'module' => 'test',
            'type' => 'view',
        ]);

        $component->dispatch('open-permission-form', mode: 'edit', permissionId: $permission->id);
        $this->assertEquals('編輯權限', $component->get('formTitle'));
        $this->assertEquals('更新', $component->get('saveButtonText'));
    }

    /** @test */
    public function 表單重置功能正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionForm::class);

        $component->dispatch('open-permission-form', mode: 'create');

        // 設定一些值
        $component->set('name', 'test.permission')
                 ->set('display_name', '測試權限')
                 ->set('description', '測試描述')
                 ->set('module', 'test')
                 ->set('type', 'view')
                 ->set('dependencies', [1, 2, 3]);

        // 關閉表單應該重置所有值
        $component->call('cancel');

        $component->assertSet('permission', null)
                 ->assertSet('name', '')
                 ->assertSet('display_name', '')
                 ->assertSet('description', '')
                 ->assertSet('module', '')
                 ->assertSet('type', '')
                 ->assertSet('dependencies', [])
                 ->assertSet('isSystemPermission', false);
    }

    /** @test */
    public function 載入失敗時顯示錯誤訊息()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionForm::class);

        // 嘗試載入不存在的權限
        $component->dispatch('open-permission-form', mode: 'edit', permissionId: null)
                 ->assertDispatched('show-toast', [
                     'type' => 'error'
                 ]);
    }
}