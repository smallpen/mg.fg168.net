<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Services\RoleHierarchyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * 角色層級管理功能測試
 */
class RoleHierarchyTest extends TestCase
{
    use RefreshDatabase;

    private RoleHierarchyService $hierarchyService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hierarchyService = app(RoleHierarchyService::class);
    }

    /**
     * 測試角色層級建立
     */
    public function test_can_create_role_hierarchy(): void
    {
        // 建立父角色
        $parentRole = Role::factory()->create([
            'name' => 'manager',
            'display_name' => '管理員',
            'parent_id' => null
        ]);

        // 建立子角色
        $childRole = Role::factory()->create([
            'name' => 'editor',
            'display_name' => '編輯者',
            'parent_id' => $parentRole->id
        ]);

        // 驗證關聯關係
        $this->assertEquals($parentRole->id, $childRole->parent_id);
        $this->assertTrue($parentRole->children->contains($childRole));
        $this->assertEquals($parentRole->id, $childRole->parent->id);
    }

    /**
     * 測試權限繼承
     */
    public function test_permission_inheritance(): void
    {
        // 建立權限
        $permission1 = Permission::factory()->create(['name' => 'users.view']);
        $permission2 = Permission::factory()->create(['name' => 'users.edit']);
        $permission3 = Permission::factory()->create(['name' => 'posts.view']);

        // 建立父角色並指派權限
        $parentRole = Role::factory()->create([
            'name' => 'manager',
            'display_name' => '管理員'
        ]);
        $parentRole->permissions()->attach([$permission1->id, $permission2->id]);

        // 建立子角色並指派權限
        $childRole = Role::factory()->create([
            'name' => 'editor',
            'display_name' => '編輯者',
            'parent_id' => $parentRole->id
        ]);
        $childRole->permissions()->attach([$permission3->id]);

        // 測試權限繼承
        $allPermissions = $childRole->getAllPermissions();
        $directPermissions = $childRole->getDirectPermissions();
        $inheritedPermissions = $childRole->getInheritedPermissions();

        $this->assertCount(3, $allPermissions); // 1 直接 + 2 繼承
        $this->assertCount(1, $directPermissions); // 只有 posts.view
        $this->assertCount(2, $inheritedPermissions); // users.view 和 users.edit

        $this->assertTrue($allPermissions->contains('name', 'users.view'));
        $this->assertTrue($allPermissions->contains('name', 'users.edit'));
        $this->assertTrue($allPermissions->contains('name', 'posts.view'));
    }

    /**
     * 測試循環依賴檢查
     */
    public function test_circular_dependency_detection(): void
    {
        // 建立角色鏈：A -> B -> C
        $roleA = Role::factory()->create(['name' => 'role_a']);
        $roleB = Role::factory()->create(['name' => 'role_b', 'parent_id' => $roleA->id]);
        $roleC = Role::factory()->create(['name' => 'role_c', 'parent_id' => $roleB->id]);

        // 嘗試建立循環依賴：A -> B -> C -> A
        $this->assertTrue($roleA->hasCircularDependency($roleC->id));
        $this->assertTrue($roleB->hasCircularDependency($roleC->id));
        $this->assertFalse($roleC->hasCircularDependency($roleA->id)); // C 可以設定 A 為父角色（會形成循環，但這個方法檢查的是設定後是否循環）
    }

    /**
     * 測試角色移動功能
     */
    public function test_role_movement(): void
    {
        // 建立角色結構
        $rootRole = Role::factory()->create(['name' => 'root']);
        $parentRole1 = Role::factory()->create(['name' => 'parent1', 'parent_id' => $rootRole->id]);
        $parentRole2 = Role::factory()->create(['name' => 'parent2', 'parent_id' => $rootRole->id]);
        $childRole = Role::factory()->create(['name' => 'child', 'parent_id' => $parentRole1->id]);

        // 移動子角色到另一個父角色下
        $this->hierarchyService->updateRoleParent($childRole, $parentRole2->id);

        // 驗證移動結果
        $childRole->refresh();
        $this->assertEquals($parentRole2->id, $childRole->parent_id);
        $this->assertTrue($parentRole2->children->contains($childRole));
        $this->assertFalse($parentRole1->children->contains($childRole));
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

        $parentRole = Role::factory()->create(['name' => 'manager']);

        // 嘗試移動系統角色應該失敗
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('系統角色不能修改層級關係');
        
        $this->hierarchyService->updateRoleParent($systemRole, $parentRole->id);
    }

    /**
     * 測試批量移動功能
     */
    public function test_bulk_role_movement(): void
    {
        // 建立角色
        $parentRole = Role::factory()->create(['name' => 'parent']);
        $role1 = Role::factory()->create(['name' => 'role1']);
        $role2 = Role::factory()->create(['name' => 'role2']);
        $systemRole = Role::factory()->create(['name' => 'system', 'is_system_role' => true]);

        $roleIds = [$role1->id, $role2->id, $systemRole->id];

        // 執行批量移動
        $results = $this->hierarchyService->bulkMoveRoles($roleIds, $parentRole->id);

        // 驗證結果
        $this->assertEquals(2, $results['success_count']); // role1 和 role2 成功
        $this->assertEquals(1, $results['error_count']); // systemRole 失敗

        // 驗證角色移動
        $role1->refresh();
        $role2->refresh();
        $systemRole->refresh();

        $this->assertEquals($parentRole->id, $role1->parent_id);
        $this->assertEquals($parentRole->id, $role2->parent_id);
        $this->assertNull($systemRole->parent_id); // 系統角色未移動
    }

    /**
     * 測試層級統計功能
     */
    public function test_hierarchy_statistics(): void
    {
        // 建立角色結構
        $root1 = Role::factory()->create(['name' => 'root1']);
        $root2 = Role::factory()->create(['name' => 'root2']);
        $child1 = Role::factory()->create(['name' => 'child1', 'parent_id' => $root1->id]);
        $child2 = Role::factory()->create(['name' => 'child2', 'parent_id' => $root1->id]);
        $grandchild = Role::factory()->create(['name' => 'grandchild', 'parent_id' => $child1->id]);
        $systemRole = Role::factory()->create(['name' => 'system', 'is_system_role' => true]);

        $stats = $this->hierarchyService->getHierarchyStats();

        $this->assertEquals(6, $stats['total_roles']);
        $this->assertEquals(3, $stats['root_roles']); // root1, root2, system
        $this->assertEquals(3, $stats['child_roles']); // child1, child2, grandchild
        $this->assertEquals(2, $stats['max_depth']); // grandchild 在第 2 層
        $this->assertEquals(1, $stats['system_roles']);
        $this->assertEquals(6, $stats['active_roles']); // 預設都是啟用的
    }

    /**
     * 測試權限快取清除
     */
    public function test_permission_cache_clearing(): void
    {
        // 建立角色和權限
        $permission = Permission::factory()->create(['name' => 'test.permission']);
        $parentRole = Role::factory()->create(['name' => 'parent']);
        $childRole = Role::factory()->create(['name' => 'child', 'parent_id' => $parentRole->id]);

        $parentRole->permissions()->attach($permission->id);

        // 建立快取
        $cacheKey = "role_all_permissions_{$childRole->id}";
        Cache::put($cacheKey, $childRole->getAllPermissions(), 3600);
        $this->assertTrue(Cache::has($cacheKey));

        // 移動角色應該清除快取
        $newParent = Role::factory()->create(['name' => 'new_parent']);
        $this->hierarchyService->updateRoleParent($childRole, $newParent->id);

        // 驗證快取已清除
        $this->assertFalse(Cache::has($cacheKey));
    }

    /**
     * 測試層級完整性驗證
     */
    public function test_hierarchy_integrity_validation(): void
    {
        // 建立正常的角色結構
        $parent = Role::factory()->create(['name' => 'parent']);
        $child = Role::factory()->create(['name' => 'child', 'parent_id' => $parent->id]);

        $validation = $this->hierarchyService->validateHierarchyIntegrity();
        $this->assertTrue($validation['is_valid']);
        $this->assertEmpty($validation['issues']);

        // 測試循環依賴檢測
        // 建立角色鏈：A -> B -> C，然後嘗試讓 A 指向 C（會形成循環）
        $roleA = Role::factory()->create(['name' => 'role_a']);
        $roleB = Role::factory()->create(['name' => 'role_b', 'parent_id' => $roleA->id]);
        $roleC = Role::factory()->create(['name' => 'role_c', 'parent_id' => $roleB->id]);
        
        // 直接在資料庫中建立循環依賴（繞過模型驗證）
        \DB::table('roles')->where('id', $roleA->id)->update(['parent_id' => $roleC->id]);

        $validation = $this->hierarchyService->validateHierarchyIntegrity();
        $this->assertFalse($validation['is_valid']);
        $this->assertArrayHasKey('circular_dependencies', $validation['issues']);
        $this->assertGreaterThan(0, count($validation['issues']['circular_dependencies']));
    }

    /**
     * 測試深度過深的角色檢測
     */
    public function test_deep_roles_detection(): void
    {
        // 建立深度超過 5 層的角色結構
        $roles = [];
        $parentId = null;
        
        for ($i = 0; $i < 7; $i++) {
            $role = Role::factory()->create([
                'name' => "level_{$i}",
                'parent_id' => $parentId
            ]);
            $roles[] = $role;
            $parentId = $role->id;
        }

        $validation = $this->hierarchyService->validateHierarchyIntegrity();
        
        // 應該檢測到深度過深的角色
        $this->assertFalse($validation['is_valid']);
        $this->assertArrayHasKey('deep_roles', $validation['issues']);
        $this->assertGreaterThan(0, count($validation['issues']['deep_roles']));
    }

    /**
     * 測試角色深度計算
     */
    public function test_role_depth_calculation(): void
    {
        // 建立多層級角色結構
        $level0 = Role::factory()->create(['name' => 'level0']);
        $level1 = Role::factory()->create(['name' => 'level1', 'parent_id' => $level0->id]);
        $level2 = Role::factory()->create(['name' => 'level2', 'parent_id' => $level1->id]);
        $level3 = Role::factory()->create(['name' => 'level3', 'parent_id' => $level2->id]);

        $this->assertEquals(0, $level0->getDepth());
        $this->assertEquals(1, $level1->getDepth());
        $this->assertEquals(2, $level2->getDepth());
        $this->assertEquals(3, $level3->getDepth());
    }

    /**
     * 測試角色路徑生成
     */
    public function test_role_path_generation(): void
    {
        $grandparent = Role::factory()->create(['name' => 'grandparent', 'display_name' => '祖父角色']);
        $parent = Role::factory()->create([
            'name' => 'parent', 
            'display_name' => '父角色',
            'parent_id' => $grandparent->id
        ]);
        $child = Role::factory()->create([
            'name' => 'child', 
            'display_name' => '子角色',
            'parent_id' => $parent->id
        ]);

        $expectedPath = '祖父角色 > 父角色 > 子角色';
        $this->assertEquals($expectedPath, $child->getHierarchyPath());
    }

    /**
     * 測試角色刪除時的子角色處理
     */
    public function test_role_deletion_with_children(): void
    {
        $parent = Role::factory()->create(['name' => 'parent']);
        $child1 = Role::factory()->create(['name' => 'child1', 'parent_id' => $parent->id]);
        $child2 = Role::factory()->create(['name' => 'child2', 'parent_id' => $parent->id]);

        // 角色有子角色時不能刪除
        $this->assertFalse($parent->can_be_deleted);

        // 刪除父角色應該將子角色的 parent_id 設為 null
        $parent->delete();

        $child1->refresh();
        $child2->refresh();

        $this->assertNull($child1->parent_id);
        $this->assertNull($child2->parent_id);
    }
}