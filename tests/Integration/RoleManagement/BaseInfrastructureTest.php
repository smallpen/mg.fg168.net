<?php

namespace Tests\Integration\RoleManagement;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

/**
 * 測試基礎架構驗證測試
 */
class BaseInfrastructureTest extends RoleManagementTestCase
{
    /**
     * 測試基礎測試環境設定
     * 
     * @group role-management
     * @group integration
     * @group infrastructure
     */
    public function test_basic_test_environment_setup(): void
    {
        // 驗證測試群組
        $this->assertTestGroup('role-management');
        $this->assertTestGroup('integration');
        
        // 驗證資料庫連線
        $this->assertDatabaseHas('permissions', []);
        
        // 驗證基礎資料已填充
        $this->assertGreaterThan(0, Permission::count(), '應該有基礎權限資料');
        
        // 驗證快取配置
        $this->assertEquals('array', config('cache.default'));
        $this->assertEquals('sync', config('queue.default'));
    }

    /**
     * 測試測試輔助方法
     * 
     * @group role-management
     * @group integration
     * @group helpers
     */
    public function test_helper_methods(): void
    {
        // 測試建立管理員使用者
        $admin = $this->createAdminUser();
        $this->assertInstanceOf(User::class, $admin);
        $this->assertTrue($admin->hasPermission('roles.view'));
        $this->assertTrue($admin->hasPermission('roles.create'));
        
        // 測試建立限制使用者
        $limitedUser = $this->createLimitedUser(['roles.view']);
        $this->assertInstanceOf(User::class, $limitedUser);
        $this->assertTrue($limitedUser->hasPermission('roles.view'));
        $this->assertFalse($limitedUser->hasPermission('roles.create'));
        
        // 測試建立無權限使用者
        $unauthorizedUser = $this->createUnauthorizedUser();
        $this->assertInstanceOf(User::class, $unauthorizedUser);
        $this->assertFalse($unauthorizedUser->hasPermission('roles.view'));
    }

    /**
     * 測試角色層級建立
     * 
     * @group role-management
     * @group integration
     * @group hierarchy
     */
    public function test_role_hierarchy_creation(): void
    {
        // 建立 3 層角色結構
        $roles = $this->createRoleHierarchy(3, 2);
        
        $this->assertCount(3, $roles);
        
        // 驗證層級關係
        $level0 = $roles[0];
        $level1 = $roles[1];
        $level2 = $roles[2];
        
        $this->assertNull($level0->parent_id);
        $this->assertEquals($level0->id, $level1->parent_id);
        $this->assertEquals($level1->id, $level2->parent_id);
        
        // 驗證權限數量
        $this->assertEquals(2, $level0->permissions()->count());
        $this->assertEquals(2, $level1->permissions()->count());
        $this->assertEquals(2, $level2->permissions()->count());
    }

    /**
     * 測試複雜角色結構建立
     * 
     * @group role-management
     * @group integration
     * @group complex-structure
     */
    public function test_complex_role_structure_creation(): void
    {
        $structure = $this->createComplexRoleStructure();
        
        $this->assertArrayHasKey('roles', $structure);
        $this->assertArrayHasKey('permissions', $structure);
        $this->assertArrayHasKey('modules', $structure);
        
        $roles = $structure['roles'];
        $permissions = $structure['permissions'];
        
        // 驗證角色
        $this->assertInstanceOf(Role::class, $roles['superAdmin']);
        $this->assertInstanceOf(Role::class, $roles['admin']);
        $this->assertInstanceOf(Role::class, $roles['moderator']);
        $this->assertInstanceOf(Role::class, $roles['editor']);
        
        // 驗證權限數量
        $this->assertEquals(20, $permissions->count()); // 5 模組 × 4 動作
        
        // 驗證層級關係
        $this->assertNull($roles['superAdmin']->parent_id);
        $this->assertEquals($roles['superAdmin']->id, $roles['admin']->parent_id);
        $this->assertEquals($roles['admin']->id, $roles['moderator']->parent_id);
        $this->assertEquals($roles['moderator']->id, $roles['editor']->parent_id);
    }

    /**
     * 測試權限繼承驗證
     * 
     * @group role-management
     * @group integration
     * @group inheritance
     */
    public function test_permission_inheritance_assertion(): void
    {
        // 建立父子角色
        $parent = Role::factory()->create(['name' => 'parent']);
        $child = Role::factory()->create(['name' => 'child', 'parent_id' => $parent->id]);
        
        // 為父角色添加權限
        $permissions = Permission::factory()->count(3)->create();
        $parent->permissions()->attach($permissions->pluck('id'));
        
        // 驗證權限繼承
        $this->assertPermissionInheritance($child, $parent);
    }

    /**
     * 測試效能測量功能
     * 
     * @group role-management
     * @group integration
     * @group performance
     */
    public function test_performance_measurement(): void
    {
        $metrics = $this->measureExecutionTime(function () {
            // 執行一些操作
            Role::factory()->count(10)->create();
            return 'test_result';
        });
        
        $this->assertArrayHasKey('result', $metrics);
        $this->assertArrayHasKey('execution_time', $metrics);
        $this->assertArrayHasKey('memory_used', $metrics);
        $this->assertArrayHasKey('peak_memory', $metrics);
        
        $this->assertEquals('test_result', $metrics['result']);
        $this->assertIsFloat($metrics['execution_time']);
        $this->assertGreaterThan(0, $metrics['execution_time']);
    }

    /**
     * 測試資料庫一致性檢查
     * 
     * @group role-management
     * @group integration
     * @group consistency
     */
    public function test_database_consistency_check(): void
    {
        // 建立正常的資料結構
        $role = Role::factory()->create();
        $permission = Permission::factory()->create();
        $user = User::factory()->create();
        
        $role->permissions()->attach($permission->id);
        $user->roles()->attach($role->id);
        
        // 驗證資料庫一致性
        $this->assertDatabaseConsistency();
        
        // 這個測試應該通過，因為沒有孤立資料
        $this->assertTrue(true);
    }

    /**
     * 測試批量角色建立
     * 
     * @group role-management
     * @group integration
     * @group bulk-operations
     */
    public function test_bulk_role_creation(): void
    {
        $roles = $this->createBulkRoles(5, true);
        
        $this->assertCount(5, $roles);
        
        foreach ($roles as $role) {
            $this->assertInstanceOf(Role::class, $role);
            $this->assertGreaterThan(0, $role->permissions()->count());
        }
    }

    /**
     * 測試大量資料集建立
     * 
     * @group role-management
     * @group integration
     * @group large-dataset
     */
    public function test_large_dataset_creation(): void
    {
        $dataset = $this->createLargeDataSet();
        
        $this->assertArrayHasKey('permissions', $dataset);
        $this->assertArrayHasKey('roles', $dataset);
        $this->assertArrayHasKey('users', $dataset);
        
        $this->assertCount(100, $dataset['permissions']);
        $this->assertCount(50, $dataset['roles']);
        $this->assertCount(200, $dataset['users']);
        
        // 驗證關聯已建立
        $randomRole = $dataset['roles']->random();
        $this->assertGreaterThan(0, $randomRole->permissions()->count());
        
        $randomUser = $dataset['users']->random();
        $this->assertGreaterThan(0, $randomUser->roles()->count());
    }

    /**
     * 測試測試資料清理
     * 
     * @group role-management
     * @group integration
     * @group cleanup
     */
    public function test_test_data_cleanup(): void
    {
        // 建立一些測試資料
        Role::factory()->count(5)->create();
        Permission::factory()->count(10)->create();
        User::factory()->count(3)->create();
        
        $initialRoleCount = Role::count();
        $initialPermissionCount = Permission::count();
        $initialUserCount = User::count();
        
        $this->assertGreaterThan(0, $initialRoleCount);
        $this->assertGreaterThan(0, $initialPermissionCount);
        $this->assertGreaterThan(0, $initialUserCount);
        
        // 執行清理
        $this->cleanupTestData();
        
        // 驗證資料已清理
        $this->assertEquals(0, Role::count());
        $this->assertEquals(0, Permission::count());
        $this->assertEquals(0, User::count());
    }
}