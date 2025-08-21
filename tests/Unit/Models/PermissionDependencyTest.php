<?php

namespace Tests\Unit\Models;

use App\Models\Permission;
use App\Models\PermissionDependency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 權限依賴關係模型測試
 */
class PermissionDependencyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_validates_no_cycle_for_valid_dependency()
    {
        $permission1 = Permission::create([
            'name' => 'users.view',
            'display_name' => '檢視使用者',
            'module' => 'users',
            'type' => 'view',
        ]);

        $permission2 = Permission::create([
            'name' => 'users.edit',
            'display_name' => '編輯使用者',
            'module' => 'users',
            'type' => 'edit',
        ]);

        $this->assertTrue(PermissionDependency::validateNoCycle($permission2->id, $permission1->id));
    }

    /** @test */
    public function it_prevents_self_dependency()
    {
        $permission = Permission::create([
            'name' => 'users.view',
            'display_name' => '檢視使用者',
            'module' => 'users',
            'type' => 'view',
        ]);

        $this->assertFalse(PermissionDependency::validateNoCycle($permission->id, $permission->id));
    }

    /** @test */
    public function it_detects_direct_circular_dependency()
    {
        $permission1 = Permission::create([
            'name' => 'users.view',
            'display_name' => '檢視使用者',
            'module' => 'users',
            'type' => 'view',
        ]);

        $permission2 = Permission::create([
            'name' => 'users.edit',
            'display_name' => '編輯使用者',
            'module' => 'users',
            'type' => 'edit',
        ]);

        // 建立 permission1 依賴 permission2
        PermissionDependency::create([
            'permission_id' => $permission1->id,
            'depends_on_permission_id' => $permission2->id,
        ]);

        // 嘗試建立 permission2 依賴 permission1（會形成循環）
        $this->assertFalse(PermissionDependency::validateNoCycle($permission2->id, $permission1->id));
    }

    /** @test */
    public function it_detects_indirect_circular_dependency()
    {
        $permission1 = Permission::create([
            'name' => 'users.view',
            'display_name' => '檢視使用者',
            'module' => 'users',
            'type' => 'view',
        ]);

        $permission2 = Permission::create([
            'name' => 'users.edit',
            'display_name' => '編輯使用者',
            'module' => 'users',
            'type' => 'edit',
        ]);

        $permission3 = Permission::create([
            'name' => 'users.delete',
            'display_name' => '刪除使用者',
            'module' => 'users',
            'type' => 'delete',
        ]);

        // 建立依賴鏈：permission1 -> permission2 -> permission3
        PermissionDependency::create([
            'permission_id' => $permission1->id,
            'depends_on_permission_id' => $permission2->id,
        ]);

        PermissionDependency::create([
            'permission_id' => $permission2->id,
            'depends_on_permission_id' => $permission3->id,
        ]);

        // 嘗試建立 permission3 依賴 permission1（會形成間接循環）
        $this->assertFalse(PermissionDependency::validateNoCycle($permission3->id, $permission1->id));
    }

    /** @test */
    public function it_finds_dependency_path()
    {
        $permission1 = Permission::create([
            'name' => 'users.view',
            'display_name' => '檢視使用者',
            'module' => 'users',
            'type' => 'view',
        ]);

        $permission2 = Permission::create([
            'name' => 'users.edit',
            'display_name' => '編輯使用者',
            'module' => 'users',
            'type' => 'edit',
        ]);

        $permission3 = Permission::create([
            'name' => 'users.delete',
            'display_name' => '刪除使用者',
            'module' => 'users',
            'type' => 'delete',
        ]);

        // 建立依賴鏈：permission1 -> permission2 -> permission3
        PermissionDependency::create([
            'permission_id' => $permission1->id,
            'depends_on_permission_id' => $permission2->id,
        ]);

        PermissionDependency::create([
            'permission_id' => $permission2->id,
            'depends_on_permission_id' => $permission3->id,
        ]);

        $path = PermissionDependency::getDependencyPath($permission1->id, $permission3->id);
        $this->assertEquals([$permission1->id, $permission2->id, $permission3->id], $path);
    }

    /** @test */
    public function it_returns_empty_path_when_no_dependency_exists()
    {
        $permission1 = Permission::create([
            'name' => 'users.view',
            'display_name' => '檢視使用者',
            'module' => 'users',
            'type' => 'view',
        ]);

        $permission2 = Permission::create([
            'name' => 'roles.view',
            'display_name' => '檢視角色',
            'module' => 'roles',
            'type' => 'view',
        ]);

        $path = PermissionDependency::getDependencyPath($permission1->id, $permission2->id);
        $this->assertEmpty($path);
    }

    /** @test */
    public function it_gets_direct_dependencies()
    {
        $permission1 = Permission::create([
            'name' => 'users.view',
            'display_name' => '檢視使用者',
            'module' => 'users',
            'type' => 'view',
        ]);

        $permission2 = Permission::create([
            'name' => 'users.edit',
            'display_name' => '編輯使用者',
            'module' => 'users',
            'type' => 'edit',
        ]);

        $permission3 = Permission::create([
            'name' => 'users.delete',
            'display_name' => '刪除使用者',
            'module' => 'users',
            'type' => 'delete',
        ]);

        // permission3 依賴 permission1 和 permission2
        PermissionDependency::create([
            'permission_id' => $permission3->id,
            'depends_on_permission_id' => $permission1->id,
        ]);

        PermissionDependency::create([
            'permission_id' => $permission3->id,
            'depends_on_permission_id' => $permission2->id,
        ]);

        $dependencies = PermissionDependency::getDirectDependencies($permission3->id);
        $this->assertCount(2, $dependencies);
        $this->assertContains($permission1->id, $dependencies);
        $this->assertContains($permission2->id, $dependencies);
    }

    /** @test */
    public function it_gets_direct_dependents()
    {
        $permission1 = Permission::create([
            'name' => 'users.view',
            'display_name' => '檢視使用者',
            'module' => 'users',
            'type' => 'view',
        ]);

        $permission2 = Permission::create([
            'name' => 'users.edit',
            'display_name' => '編輯使用者',
            'module' => 'users',
            'type' => 'edit',
        ]);

        $permission3 = Permission::create([
            'name' => 'users.delete',
            'display_name' => '刪除使用者',
            'module' => 'users',
            'type' => 'delete',
        ]);

        // permission2 和 permission3 都依賴 permission1
        PermissionDependency::create([
            'permission_id' => $permission2->id,
            'depends_on_permission_id' => $permission1->id,
        ]);

        PermissionDependency::create([
            'permission_id' => $permission3->id,
            'depends_on_permission_id' => $permission1->id,
        ]);

        $dependents = PermissionDependency::getDirectDependents($permission1->id);
        $this->assertCount(2, $dependents);
        $this->assertContains($permission2->id, $dependents);
        $this->assertContains($permission3->id, $dependents);
    }

    /** @test */
    public function it_builds_full_dependency_tree()
    {
        $permission1 = Permission::create([
            'name' => 'users.view',
            'display_name' => '檢視使用者',
            'module' => 'users',
            'type' => 'view',
        ]);

        $permission2 = Permission::create([
            'name' => 'users.edit',
            'display_name' => '編輯使用者',
            'module' => 'users',
            'type' => 'edit',
        ]);

        $permission3 = Permission::create([
            'name' => 'users.delete',
            'display_name' => '刪除使用者',
            'module' => 'users',
            'type' => 'delete',
        ]);

        // 建立依賴鏈：permission3 -> permission2 -> permission1
        PermissionDependency::create([
            'permission_id' => $permission2->id,
            'depends_on_permission_id' => $permission1->id,
        ]);

        PermissionDependency::create([
            'permission_id' => $permission3->id,
            'depends_on_permission_id' => $permission2->id,
        ]);

        $tree = PermissionDependency::getFullDependencyTree($permission3->id);
        
        $this->assertArrayHasKey($permission2->id, $tree);
        $this->assertArrayHasKey($permission1->id, $tree[$permission2->id]);
    }

    /** @test */
    public function it_builds_full_dependent_tree()
    {
        $permission1 = Permission::create([
            'name' => 'users.view',
            'display_name' => '檢視使用者',
            'module' => 'users',
            'type' => 'view',
        ]);

        $permission2 = Permission::create([
            'name' => 'users.edit',
            'display_name' => '編輯使用者',
            'module' => 'users',
            'type' => 'edit',
        ]);

        $permission3 = Permission::create([
            'name' => 'users.delete',
            'display_name' => '刪除使用者',
            'module' => 'users',
            'type' => 'delete',
        ]);

        // 建立依賴鏈：permission3 -> permission2 -> permission1
        PermissionDependency::create([
            'permission_id' => $permission2->id,
            'depends_on_permission_id' => $permission1->id,
        ]);

        PermissionDependency::create([
            'permission_id' => $permission3->id,
            'depends_on_permission_id' => $permission2->id,
        ]);

        $tree = PermissionDependency::getFullDependentTree($permission1->id);
        
        $this->assertArrayHasKey($permission2->id, $tree);
        $this->assertArrayHasKey($permission3->id, $tree[$permission2->id]);
    }

    /** @test */
    public function it_validates_integrity_successfully()
    {
        $permission1 = Permission::create([
            'name' => 'users.view',
            'display_name' => '檢視使用者',
            'module' => 'users',
            'type' => 'view',
        ]);

        $permission2 = Permission::create([
            'name' => 'users.edit',
            'display_name' => '編輯使用者',
            'module' => 'users',
            'type' => 'edit',
        ]);

        PermissionDependency::create([
            'permission_id' => $permission2->id,
            'depends_on_permission_id' => $permission1->id,
        ]);

        $result = PermissionDependency::validateIntegrity();
        
        $this->assertTrue($result['is_valid']);
        $this->assertEmpty($result['issues']);
        $this->assertEquals(0, $result['total_issues']);
    }

    /** @test */
    public function it_detects_circular_dependency_in_integrity_check()
    {
        $permission1 = Permission::create([
            'name' => 'users.view',
            'display_name' => '檢視使用者',
            'module' => 'users',
            'type' => 'view',
        ]);

        $permission2 = Permission::create([
            'name' => 'users.edit',
            'display_name' => '編輯使用者',
            'module' => 'users',
            'type' => 'edit',
        ]);

        // 建立循環依賴
        PermissionDependency::create([
            'permission_id' => $permission1->id,
            'depends_on_permission_id' => $permission2->id,
        ]);

        PermissionDependency::create([
            'permission_id' => $permission2->id,
            'depends_on_permission_id' => $permission1->id,
        ]);

        $result = PermissionDependency::validateIntegrity();
        
        $this->assertFalse($result['is_valid']);
        $this->assertNotEmpty($result['issues']);
        $this->assertGreaterThan(0, $result['total_issues']);
        
        $circularIssues = array_filter($result['issues'], function ($issue) {
            return $issue['type'] === 'circular_dependency';
        });
        
        $this->assertNotEmpty($circularIssues);
    }

    /** @test */
    public function it_detects_orphaned_dependencies_in_integrity_check()
    {
        $permission1 = Permission::create([
            'name' => 'users.view',
            'display_name' => '檢視使用者',
            'module' => 'users',
            'type' => 'view',
        ]);

        $permission2 = Permission::create([
            'name' => 'users.edit',
            'display_name' => '編輯使用者',
            'module' => 'users',
            'type' => 'edit',
        ]);

        // 建立正常的依賴關係
        PermissionDependency::create([
            'permission_id' => $permission1->id,
            'depends_on_permission_id' => $permission2->id,
        ]);

        // 刪除被依賴的權限，造成孤立依賴
        $permission2->delete();

        $result = PermissionDependency::validateIntegrity();
        
        $this->assertFalse($result['is_valid']);
        $this->assertNotEmpty($result['issues']);
        
        $orphanedIssues = array_filter($result['issues'], function ($issue) {
            return $issue['type'] === 'orphaned_dependency';
        });
        
        $this->assertNotEmpty($orphanedIssues);
    }

    /** @test */
    public function it_cleans_up_invalid_dependencies()
    {
        $permission1 = Permission::create([
            'name' => 'users.view',
            'display_name' => '檢視使用者',
            'module' => 'users',
            'type' => 'view',
        ]);

        $permission2 = Permission::create([
            'name' => 'users.edit',
            'display_name' => '編輯使用者',
            'module' => 'users',
            'type' => 'edit',
        ]);

        // 建立有效的依賴關係
        $validDependency = PermissionDependency::create([
            'permission_id' => $permission1->id,
            'depends_on_permission_id' => $permission1->id,
        ]);

        // 建立將要變成無效的依賴關係
        $invalidDependency = PermissionDependency::create([
            'permission_id' => $permission1->id,
            'depends_on_permission_id' => $permission2->id,
        ]);

        // 刪除被依賴的權限，使依賴關係變成無效
        $permission2->delete();

        $deletedCount = PermissionDependency::cleanupInvalidDependencies();
        
        $this->assertEquals(1, $deletedCount);
        $this->assertDatabaseHas('permission_dependencies', ['id' => $validDependency->id]);
        $this->assertDatabaseMissing('permission_dependencies', ['id' => $invalidDependency->id]);
    }
}