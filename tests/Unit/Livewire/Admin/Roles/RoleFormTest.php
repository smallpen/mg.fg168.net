<?php

namespace Tests\Unit\Livewire\Admin\Roles;

use App\Livewire\Admin\Roles\RoleForm;
use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * RoleForm 元件單元測試
 */
class RoleFormTest extends TestCase
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
        $permissions = ['roles.view', 'roles.create', 'roles.edit', 'roles.delete'];
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
    public function 元件可以正確初始化為建立模式()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(RoleForm::class);

        $component->assertSet('isEditing', false)
                 ->assertSet('name', '')
                 ->assertSet('display_name', '')
                 ->assertSet('description', '')
                 ->assertSet('parent_id', null)
                 ->assertSet('is_active', true)
                 ->assertSet('isSystemRole', false);
    }

    /** @test */
    public function 元件可以正確初始化為編輯模式()
    {
        $this->actingAs($this->adminUser);

        $role = Role::create([
            'name' => 'editor',
            'display_name' => '編輯者',
            'description' => '內容編輯者',
            'is_active' => false,
        ]);

        $component = Livewire::test(RoleForm::class, ['role' => $role]);

        $component->assertSet('isEditing', true)
                 ->assertSet('role.id', $role->id)
                 ->assertSet('name', $role->name)
                 ->assertSet('display_name', $role->display_name)
                 ->assertSet('description', $role->description)
                 ->assertSet('is_active', $role->is_active);
    }

    /** @test */
    public function 計算屬性返回正確的值()
    {
        $this->actingAs($this->adminUser);

        // 測試建立模式
        $component = Livewire::test(RoleForm::class);
        $this->assertEquals('建立新角色', $component->get('formTitle'));
        $this->assertEquals('建立角色', $component->get('saveButtonText'));

        // 測試編輯模式
        $role = Role::create([
            'name' => 'test_role',
            'display_name' => '測試角色',
            'is_active' => true,
        ]);

        $component = Livewire::test(RoleForm::class, ['role' => $role]);
        $this->assertEquals('編輯角色：測試角色', $component->get('formTitle'));
        $this->assertEquals('更新角色', $component->get('saveButtonText'));
    }

    /** @test */
    public function 可以正確載入可選父角色列表()
    {
        $this->actingAs($this->adminUser);

        $parentRole1 = Role::create([
            'name' => 'parent1',
            'display_name' => '父角色1',
            'is_active' => true,
        ]);

        $parentRole2 = Role::create([
            'name' => 'parent2',
            'display_name' => '父角色2',
            'is_active' => true,
        ]);

        // 停用的角色不應該出現在列表中
        Role::create([
            'name' => 'inactive_parent',
            'display_name' => '停用父角色',
            'is_active' => false,
        ]);

        $component = Livewire::test(RoleForm::class);
        $availableParents = $component->get('availableParents');

        $this->assertCount(3, $availableParents); // 包含 admin 角色
        $this->assertTrue(collect($availableParents)->contains('id', $parentRole1->id));
        $this->assertTrue(collect($availableParents)->contains('id', $parentRole2->id));
        $this->assertFalse(collect($availableParents)->pluck('name')->contains('inactive_parent'));
    }

    /** @test */
    public function 編輯模式時排除自己和後代角色()
    {
        $this->actingAs($this->adminUser);

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

        $grandchildRole = Role::create([
            'name' => 'grandchild',
            'display_name' => '孫角色',
            'parent_id' => $childRole->id,
            'is_active' => true,
        ]);

        // 編輯父角色時，不應該包含自己和後代
        $component = Livewire::test(RoleForm::class, ['role' => $parentRole]);
        $availableParents = $component->get('availableParents');
        
        $parentIds = collect($availableParents)->pluck('id')->toArray();
        $this->assertNotContains($parentRole->id, $parentIds);
        $this->assertNotContains($childRole->id, $parentIds);
        $this->assertNotContains($grandchildRole->id, $parentIds);
    }

    /** @test */
    public function 自動生成名稱功能正確運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(RoleForm::class);

        // 測試各種顯示名稱的轉換
        $testCases = [
            '內容編輯者' => 'content_editor',
            '系統管理員' => 'system_admin',
            'Content Editor!' => 'content_editor',
            'Multi   Space   Name' => 'multi_space_name',
            '特殊@字#元$測試' => 'special_character_test',
        ];

        // 測試英文顯示名稱的自動生成
        $component = Livewire::test(RoleForm::class);
        $component->set('display_name', 'Content Editor');
        
        // 檢查名稱是否自動生成
        $generatedName = $component->get('name');
        $this->assertNotEmpty($generatedName);
        $this->assertStringContainsString('content', strtolower($generatedName));
        
        // 測試特殊字元處理
        $component2 = Livewire::test(RoleForm::class);
        $component2->set('display_name', 'System Admin!');
        $generatedName2 = $component2->get('name');
        $this->assertNotEmpty($generatedName2);
        $this->assertStringContainsString('system', strtolower($generatedName2));
    }

    /** @test */
    public function 父角色選擇器切換功能正常()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(RoleForm::class);

        // 初始狀態
        $component->assertSet('showParentSelector', false)
                 ->assertSet('parent_id', null);

        // 開啟父角色選擇器
        $component->call('toggleParentSelector')
                 ->assertSet('showParentSelector', true);

        // 設定父角色
        $parentRole = Role::create([
            'name' => 'parent',
            'display_name' => '父角色',
            'is_active' => true,
        ]);

        $component->set('parent_id', $parentRole->id);

        // 關閉父角色選擇器應該清除選擇
        $component->call('toggleParentSelector')
                 ->assertSet('showParentSelector', false)
                 ->assertSet('parent_id', null);
    }

    /** @test */
    public function 循環依賴檢查正常運作()
    {
        $this->actingAs($this->adminUser);

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

        // 嘗試將子角色設為父角色的父角色
        $component = Livewire::test(RoleForm::class, ['role' => $parentRole]);
        
        $component->set('parent_id', $childRole->id)
                 ->assertHasErrors('parent_id')
                 ->assertSet('parent_id', null);
    }

    /** @test */
    public function 系統角色保護機制正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(RoleForm::class, ['role' => $this->adminRole]);

        // 系統角色應該被標記
        $component->assertSet('isSystemRole', true);

        // 檢查保護屬性
        $this->assertFalse($component->get('canModifyParent'));
        
        // 核心系統角色不能停用
        if (in_array($this->adminRole->name, ['super_admin', 'admin'])) {
            $this->assertFalse($component->get('canDeactivate'));
        }
    }

    /** @test */
    public function 名稱可用性檢查功能正常()
    {
        $this->actingAs($this->adminUser);

        // 建立一個現有角色
        Role::create([
            'name' => 'existing_role',
            'display_name' => '現有角色',
            'is_active' => true,
        ]);

        $component = Livewire::test(RoleForm::class);

        // 檢查重複名稱
        $component->set('name', 'existing_role')
                 ->call('checkNameAvailability')
                 ->assertHasErrors('name');

        // 檢查可用名稱
        $component->set('name', 'new_role')
                 ->call('checkNameAvailability')
                 ->assertHasNoErrors('name');
    }

    /** @test */
    public function 表單重置功能正常()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(RoleForm::class);

        // 設定一些值
        $component->set('name', 'test_role')
                 ->set('display_name', '測試角色')
                 ->set('description', '測試描述')
                 ->set('is_active', false)
                 ->set('showParentSelector', true);

        // 重置表單
        $component->call('resetForm');

        // 檢查是否重置
        $component->assertSet('name', '')
                 ->assertSet('display_name', '')
                 ->assertSet('description', '')
                 ->assertSet('is_active', true)
                 ->assertSet('showParentSelector', false);
    }

    /** @test */
    public function 取消操作觸發正確事件()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(RoleForm::class)
            ->call('cancel')
            ->assertDispatched('role-form-cancelled');
    }

    /** @test */
    public function 父角色資訊正確顯示()
    {
        $this->actingAs($this->adminUser);

        $parentRole = Role::create([
            'name' => 'parent',
            'display_name' => '父角色',
            'is_active' => true,
        ]);

        $component = Livewire::test(RoleForm::class);
        
        // 沒有選擇父角色時
        $this->assertNull($component->get('parentRoleInfo'));

        // 選擇父角色後
        $component->set('parent_id', $parentRole->id);
        $parentInfo = $component->get('parentRoleInfo');
        
        $this->assertNotNull($parentInfo);
        $this->assertEquals($parentRole->id, $parentInfo['id']);
        $this->assertEquals($parentRole->display_name, $parentInfo['display_name']);
    }

    /** @test */
    public function 權限檢查正確運作()
    {
        // 測試沒有權限的使用者
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user);

        // 建立模式需要 roles.create 權限
        Livewire::test(RoleForm::class)
            ->assertForbidden();

        // 編輯模式需要 roles.edit 權限
        $role = Role::create([
            'name' => 'test_role',
            'display_name' => '測試角色',
            'is_active' => true,
        ]);

        Livewire::test(RoleForm::class, ['role' => $role])
            ->assertForbidden();
    }
}