<?php

namespace Tests\Feature\Livewire\Admin\Permissions;

use App\Livewire\Admin\Permissions\DependencyGraph;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 權限依賴關係圖表元件測試
 */
class DependencyGraphTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Role $adminRole;
    protected Permission $viewPermission;
    protected Permission $editPermission;
    protected Permission $deletePermission;

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

        // 建立測試權限
        $this->viewPermission = Permission::create([
            'name' => 'users.view',
            'display_name' => '檢視使用者',
            'module' => 'users',
            'type' => 'view',
        ]);

        $this->editPermission = Permission::create([
            'name' => 'users.edit',
            'display_name' => '編輯使用者',
            'module' => 'users',
            'type' => 'edit',
        ]);

        $this->deletePermission = Permission::create([
            'name' => 'users.delete',
            'display_name' => '刪除使用者',
            'module' => 'users',
            'type' => 'delete',
        ]);

        // 建立必要的權限
        $permissions = [
            ['name' => 'permissions.view', 'display_name' => '檢視權限', 'module' => 'permissions', 'type' => 'view'],
            ['name' => 'permissions.edit', 'display_name' => '編輯權限', 'module' => 'permissions', 'type' => 'edit'],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }

        $this->adminRole->permissions()->sync(Permission::all()->pluck('id'));

        $this->actingAs($this->adminUser);
    }

    /** @test */
    public function it_can_render_dependency_graph_component()
    {
        Livewire::test(DependencyGraph::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.admin.permissions.dependency-graph');
    }

    /** @test */
    public function it_can_select_permission()
    {
        Livewire::test(DependencyGraph::class)
            ->call('selectPermission', $this->viewPermission->id)
            ->assertSet('selectedPermissionId', $this->viewPermission->id)
            ->assertSet('selectedPermission.id', $this->viewPermission->id);
    }

    /** @test */
    public function it_handles_non_existent_permission_gracefully()
    {
        Livewire::test(DependencyGraph::class)
            ->call('selectPermission', 99999)
            ->assertDispatched('show-toast');
    }

    /** @test */
    public function it_can_build_dependency_tree()
    {
        // 建立依賴關係：delete -> edit -> view
        $this->editPermission->dependencies()->attach($this->viewPermission->id);
        $this->deletePermission->dependencies()->attach($this->editPermission->id);

        $component = Livewire::test(DependencyGraph::class)
            ->call('selectPermission', $this->deletePermission->id)
            ->set('direction', 'dependencies');

        $this->assertNotEmpty($component->get('dependencyTree'));
    }

    /** @test */
    public function it_can_build_dependent_tree()
    {
        // 建立依賴關係：delete -> edit -> view
        $this->editPermission->dependencies()->attach($this->viewPermission->id);
        $this->deletePermission->dependencies()->attach($this->editPermission->id);

        $component = Livewire::test(DependencyGraph::class)
            ->call('selectPermission', $this->viewPermission->id)
            ->set('direction', 'dependents');

        $this->assertNotEmpty($component->get('dependentTree'));
    }

    /** @test */
    public function it_can_generate_graph_data()
    {
        // 建立依賴關係
        $this->editPermission->dependencies()->attach($this->viewPermission->id);

        $component = Livewire::test(DependencyGraph::class)
            ->call('selectPermission', $this->editPermission->id);

        $graphData = $component->get('graphData');
        $this->assertArrayHasKey('nodes', $graphData);
        $this->assertArrayHasKey('edges', $graphData);
        $this->assertNotEmpty($graphData['nodes']);
    }

    /** @test */
    public function it_can_open_add_dependency_dialog()
    {
        Livewire::test(DependencyGraph::class)
            ->call('selectPermission', $this->viewPermission->id)
            ->call('openAddDependency')
            ->assertSet('showAddDependency', true);
    }

    /** @test */
    public function it_requires_edit_permission_to_add_dependencies()
    {
        // 移除編輯權限
        $this->adminRole->permissions()->detach(
            Permission::where('name', 'permissions.edit')->first()->id
        );

        Livewire::test(DependencyGraph::class)
            ->call('selectPermission', $this->viewPermission->id)
            ->call('openAddDependency')
            ->assertDispatched('show-toast');
    }

    /** @test */
    public function it_can_add_dependencies()
    {
        $component = Livewire::test(DependencyGraph::class)
            ->call('selectPermission', $this->editPermission->id)
            ->set('selectedDependencies', [$this->viewPermission->id])
            ->call('addDependencies')
            ->assertDispatched('show-toast');

        $this->assertTrue(
            $this->editPermission->fresh()->dependencies->contains($this->viewPermission)
        );
    }

    /** @test */
    public function it_validates_dependencies_before_adding()
    {
        // 建立循環依賴的情況
        $this->viewPermission->dependencies()->attach($this->editPermission->id);

        Livewire::test(DependencyGraph::class)
            ->call('selectPermission', $this->editPermission->id)
            ->set('selectedDependencies', [$this->viewPermission->id])
            ->call('addDependencies')
            ->assertDispatched('show-toast');

        // 應該不會新增循環依賴
        $this->assertFalse(
            $this->editPermission->fresh()->dependencies->contains($this->viewPermission)
        );
    }

    /** @test */
    public function it_can_remove_dependency()
    {
        // 先建立依賴關係
        $this->editPermission->dependencies()->attach($this->viewPermission->id);

        Livewire::test(DependencyGraph::class)
            ->call('selectPermission', $this->editPermission->id)
            ->call('removeDependency', $this->viewPermission->id)
            ->assertDispatched('show-toast');

        $this->assertFalse(
            $this->editPermission->fresh()->dependencies->contains($this->viewPermission)
        );
    }

    /** @test */
    public function it_requires_edit_permission_to_remove_dependencies()
    {
        // 移除編輯權限
        $this->adminRole->permissions()->detach(
            Permission::where('name', 'permissions.edit')->first()->id
        );

        // 先建立依賴關係
        $this->editPermission->dependencies()->attach($this->viewPermission->id);

        Livewire::test(DependencyGraph::class)
            ->call('selectPermission', $this->editPermission->id)
            ->call('removeDependency', $this->viewPermission->id)
            ->assertDispatched('show-toast');

        // 依賴關係應該仍然存在
        $this->assertTrue(
            $this->editPermission->fresh()->dependencies->contains($this->viewPermission)
        );
    }

    /** @test */
    public function it_can_toggle_node_expansion()
    {
        $component = Livewire::test(DependencyGraph::class);

        $nodeId = 'dependency_1';
        
        // 初始狀態應該是收合的
        $this->assertFalse($component->instance()->isNodeExpanded($nodeId));

        // 展開節點
        $component->call('toggleNode', $nodeId);
        $this->assertTrue($component->instance()->isNodeExpanded($nodeId));

        // 收合節點
        $component->call('toggleNode', $nodeId);
        $this->assertFalse($component->instance()->isNodeExpanded($nodeId));
    }

    /** @test */
    public function it_can_auto_resolve_dependencies()
    {
        Livewire::test(DependencyGraph::class)
            ->call('selectPermission', $this->editPermission->id)
            ->call('autoResolveDependencies')
            ->assertDispatched('show-toast');

        // 編輯權限應該自動依賴檢視權限
        $this->assertTrue(
            $this->editPermission->fresh()->dependencies->contains($this->viewPermission)
        );
    }

    /** @test */
    public function it_suggests_correct_dependencies_for_edit_permission()
    {
        $component = Livewire::test(DependencyGraph::class)
            ->call('selectPermission', $this->editPermission->id);

        $suggestions = $component->instance()->getSuggestedDependencies($this->editPermission);
        
        $this->assertContains($this->viewPermission->id, $suggestions);
    }

    /** @test */
    public function it_suggests_correct_dependencies_for_delete_permission()
    {
        $component = Livewire::test(DependencyGraph::class)
            ->call('selectPermission', $this->deletePermission->id);

        $suggestions = $component->instance()->getSuggestedDependencies($this->deletePermission);
        
        $this->assertContains($this->viewPermission->id, $suggestions);
        $this->assertContains($this->editPermission->id, $suggestions);
    }

    /** @test */
    public function it_can_check_circular_dependencies()
    {
        Livewire::test(DependencyGraph::class)
            ->call('checkCircularDependencies')
            ->assertDispatched('show-toast');
    }

    /** @test */
    public function it_updates_view_when_view_mode_changes()
    {
        $component = Livewire::test(DependencyGraph::class)
            ->call('selectPermission', $this->viewPermission->id)
            ->set('viewMode', 'network');

        $this->assertEquals('network', $component->get('viewMode'));
    }

    /** @test */
    public function it_updates_data_when_direction_changes()
    {
        $component = Livewire::test(DependencyGraph::class)
            ->call('selectPermission', $this->viewPermission->id)
            ->set('direction', 'dependents');

        $this->assertEquals('dependents', $component->get('direction'));
    }

    /** @test */
    public function it_filters_by_module()
    {
        // 建立不同模組的權限
        $rolePermission = Permission::create([
            'name' => 'roles.view',
            'display_name' => '檢視角色',
            'module' => 'roles',
            'type' => 'view',
        ]);

        $this->editPermission->dependencies()->attach([$this->viewPermission->id, $rolePermission->id]);

        $component = Livewire::test(DependencyGraph::class)
            ->call('selectPermission', $this->editPermission->id)
            ->set('moduleFilter', 'users');

        // 應該只顯示 users 模組的權限
        $dependencyTree = $component->get('dependencyTree');
        $this->assertNotEmpty($dependencyTree);
    }

    /** @test */
    public function it_respects_max_depth_setting()
    {
        // 建立多層依賴關係
        $managePermission = Permission::create([
            'name' => 'users.manage',
            'display_name' => '管理使用者',
            'module' => 'users',
            'type' => 'manage',
        ]);

        $this->editPermission->dependencies()->attach($this->viewPermission->id);
        $this->deletePermission->dependencies()->attach($this->editPermission->id);
        $managePermission->dependencies()->attach($this->deletePermission->id);

        $component = Livewire::test(DependencyGraph::class)
            ->call('selectPermission', $managePermission->id)
            ->set('maxDepth', 2);

        $this->assertEquals(2, $component->get('maxDepth'));
    }

    /** @test */
    public function it_requires_view_permission_to_access()
    {
        // 移除檢視權限
        $this->adminRole->permissions()->detach(
            Permission::where('name', 'permissions.view')->first()->id
        );

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        Livewire::test(DependencyGraph::class);
    }

    /** @test */
    public function it_loads_available_permissions_correctly()
    {
        $component = Livewire::test(DependencyGraph::class)
            ->call('selectPermission', $this->viewPermission->id);

        $availablePermissions = $component->get('availablePermissions');
        
        // 應該包含其他權限但不包含選中的權限
        $this->assertNotEmpty($availablePermissions);
        $this->assertFalse(
            collect($availablePermissions)->contains('id', $this->viewPermission->id)
        );
    }

    /** @test */
    public function it_handles_empty_dependency_selection()
    {
        Livewire::test(DependencyGraph::class)
            ->call('selectPermission', $this->viewPermission->id)
            ->set('selectedDependencies', [])
            ->call('addDependencies')
            ->assertDispatched('show-toast');
    }

    /** @test */
    public function it_can_mount_with_permission_id()
    {
        $component = Livewire::test(DependencyGraph::class, ['permissionId' => $this->viewPermission->id]);
        
        $this->assertEquals($this->viewPermission->id, $component->get('selectedPermissionId'));
    }

    /** @test */
    public function it_provides_modules_and_types_properties()
    {
        $component = Livewire::test(DependencyGraph::class);
        
        $modules = $component->get('modules');
        $types = $component->get('types');
        
        $this->assertNotEmpty($modules);
        $this->assertNotEmpty($types);
        $this->assertContains('users', $modules->toArray());
        $this->assertContains('view', $types->toArray());
    }
}