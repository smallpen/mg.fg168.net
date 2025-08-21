<?php

namespace Tests\Feature;

use App\Livewire\Admin\Roles\RoleHierarchy;
use App\Livewire\Admin\Roles\RoleForm;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 角色層級管理 Livewire 元件測試
 */
class RoleHierarchyLivewireTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立具有所有權限的測試使用者
        $user = User::factory()->create();
        
        // 建立管理員角色和權限
        $adminRole = Role::factory()->create([
            'name' => 'admin',
            'display_name' => '管理員',
            'is_system_role' => true
        ]);
        
        // 建立必要的權限
        $permissions = [
            'roles.view',
            'roles.create', 
            'roles.edit',
            'roles.delete',
            'users.view'
        ];
        
        foreach ($permissions as $permissionName) {
            $permission = Permission::factory()->create(['name' => $permissionName]);
            $adminRole->permissions()->attach($permission->id);
        }
        
        // 指派角色給使用者
        $user->roles()->attach($adminRole->id);
        
        $this->actingAs($user);
    }

    /**
     * 測試角色層級元件載入
     */
    public function test_role_hierarchy_component_loads(): void
    {
        $component = Livewire::test(RoleHierarchy::class);
        
        $component->assertStatus(200)
                 ->assertSee('角色層級管理')
                 ->assertSee('根角色')
                 ->assertSee('最大深度');
    }

    /**
     * 測試角色層級樹狀顯示
     */
    public function test_hierarchy_tree_display(): void
    {
        // 建立角色結構
        $parent = Role::factory()->create([
            'name' => 'manager',
            'display_name' => '管理員'
        ]);
        
        $child = Role::factory()->create([
            'name' => 'editor',
            'display_name' => '編輯者',
            'parent_id' => $parent->id
        ]);

        $component = Livewire::test(RoleHierarchy::class);
        
        $component->assertSee('管理員')
                 ->assertSee('編輯者');
    }

    /**
     * 測試搜尋功能
     */
    public function test_search_functionality(): void
    {
        Role::factory()->create([
            'name' => 'admin',
            'display_name' => '系統管理員'
        ]);
        
        Role::factory()->create([
            'name' => 'user',
            'display_name' => '一般使用者'
        ]);

        $component = Livewire::test(RoleHierarchy::class);
        
        // 搜尋 "管理"
        $component->set('search', '管理')
                 ->assertSee('系統管理員')
                 ->assertDontSee('一般使用者');
    }

    /**
     * 測試節點展開/收合
     */
    public function test_node_toggle(): void
    {
        $parent = Role::factory()->create([
            'name' => 'manager',
            'display_name' => '管理員'
        ]);
        
        $child = Role::factory()->create([
            'name' => 'editor',
            'display_name' => '編輯者',
            'parent_id' => $parent->id
        ]);

        $component = Livewire::test(RoleHierarchy::class);
        
        // 展開節點
        $component->call('toggleNode', $parent->id);
        
        $expandedNodes = json_decode($component->get('expandedNodes'), true);
        $this->assertContains($parent->id, $expandedNodes);
        
        // 收合節點
        $component->call('toggleNode', $parent->id);
        
        $expandedNodes = json_decode($component->get('expandedNodes'), true);
        $this->assertNotContains($parent->id, $expandedNodes);
    }

    /**
     * 測試角色移動功能
     */
    public function test_role_movement(): void
    {
        $parent1 = Role::factory()->create(['name' => 'parent1']);
        $parent2 = Role::factory()->create(['name' => 'parent2']);
        $child = Role::factory()->create([
            'name' => 'child',
            'parent_id' => $parent1->id
        ]);

        $component = Livewire::test(RoleHierarchy::class);
        
        // 移動角色
        $component->call('moveRole', $child->id, $parent2->id);
        
        // 驗證移動成功
        $child->refresh();
        $this->assertEquals($parent2->id, $child->parent_id);
    }

    /**
     * 測試系統角色保護
     */
    public function test_system_role_protection(): void
    {
        $systemRole = Role::factory()->create([
            'name' => 'admin',
            'is_system_role' => true
        ]);
        
        $parent = Role::factory()->create(['name' => 'parent']);

        $component = Livewire::test(RoleHierarchy::class);
        
        // 嘗試移動系統角色應該失敗
        $component->call('moveRole', $systemRole->id, $parent->id);
        
        $component->assertHasErrors(['move']);
        
        // 驗證系統角色未被移動
        $systemRole->refresh();
        $this->assertNull($systemRole->parent_id);
    }

    /**
     * 測試角色表單的父角色選擇功能
     */
    public function test_role_form_parent_selection(): void
    {
        $parentRole = Role::factory()->create([
            'name' => 'manager',
            'display_name' => '管理員'
        ]);

        $component = Livewire::test(RoleForm::class);
        
        // 設定父角色
        $component->call('toggleParentSelector')
                 ->set('parent_id', $parentRole->id)
                 ->assertSet('parent_id', $parentRole->id);
        
        // 檢查父角色資訊
        $parentInfo = $component->get('parentRoleInfo');
        $this->assertEquals('管理員', $parentInfo['display_name']);
    }

    /**
     * 測試權限繼承預覽
     */
    public function test_permission_inheritance_preview(): void
    {
        // 建立權限
        $permission1 = Permission::factory()->create(['name' => 'users.view']);
        $permission2 = Permission::factory()->create(['name' => 'users.edit']);
        
        // 建立父角色並指派權限
        $parentRole = Role::factory()->create([
            'name' => 'manager',
            'display_name' => '管理員'
        ]);
        $parentRole->permissions()->attach([$permission1->id, $permission2->id]);

        $component = Livewire::test(RoleForm::class);
        
        // 設定父角色
        $component->call('toggleParentSelector')
                 ->set('parent_id', $parentRole->id);
        
        // 檢查權限繼承預覽
        $preview = $component->get('permissionInheritancePreview');
        $this->assertNotNull($preview);
        $this->assertEquals('管理員', $preview['parent_name']);
        $this->assertEquals(2, $preview['inherited_count']);
    }

    /**
     * 測試循環依賴檢查
     */
    public function test_circular_dependency_prevention(): void
    {
        // 建立角色鏈：A -> B -> C
        $roleA = Role::factory()->create(['name' => 'role_a']);
        $roleB = Role::factory()->create(['name' => 'role_b', 'parent_id' => $roleA->id]);
        $roleC = Role::factory()->create(['name' => 'role_c', 'parent_id' => $roleB->id]);

        $component = Livewire::test(RoleForm::class, ['role' => $roleA]);
        
        // 嘗試設定 C 為 A 的父角色（會造成循環依賴）
        $component->call('toggleParentSelector')
                 ->set('parent_id', $roleC->id);
        
        $component->assertHasErrors(['parent_id']);
        $this->assertEquals(null, $component->get('parent_id'));
    }

    /**
     * 測試篩選功能
     */
    public function test_filtering_options(): void
    {
        // 建立不同類型的角色
        Role::factory()->create([
            'name' => 'active_role',
            'display_name' => '啟用角色',
            'is_active' => true,
            'is_system_role' => false
        ]);
        
        Role::factory()->create([
            'name' => 'inactive_role',
            'display_name' => '停用角色',
            'is_active' => false,
            'is_system_role' => false
        ]);
        
        Role::factory()->create([
            'name' => 'system_role',
            'display_name' => '系統角色',
            'is_active' => true,
            'is_system_role' => true
        ]);

        $component = Livewire::test(RoleHierarchy::class);
        
        // 測試顯示停用角色
        $component->set('showInactive', false)
                 ->assertSee('啟用角色')
                 ->assertDontSee('停用角色');
        
        $component->set('showInactive', true)
                 ->assertSee('啟用角色')
                 ->assertSee('停用角色');
        
        // 測試顯示系統角色
        $component->set('showSystemRoles', false)
                 ->assertSee('啟用角色')
                 ->assertDontSee('系統角色');
        
        $component->set('showSystemRoles', true)
                 ->assertSee('啟用角色')
                 ->assertSee('系統角色');
    }

    /**
     * 測試統計資訊顯示
     */
    public function test_statistics_display(): void
    {
        // 建立角色結構
        Role::factory()->create(['name' => 'root1']);
        Role::factory()->create(['name' => 'root2']);
        $parent = Role::factory()->create(['name' => 'parent']);
        Role::factory()->create(['name' => 'child', 'parent_id' => $parent->id]);

        $component = Livewire::test(RoleHierarchy::class);
        
        $stats = $component->get('hierarchyStats');
        
        $this->assertEquals(4, $stats['total_roles']);
        $this->assertEquals(3, $stats['root_roles']); // root1, root2, parent
        $this->assertEquals(1, $stats['child_roles']); // child
        $this->assertEquals(1, $stats['max_depth']); // child 在第 1 層
    }
}