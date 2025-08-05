<?php

namespace Tests\Unit;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Services\RoleService;
use App\Services\PermissionService;
use App\Repositories\RoleRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * 角色服務測試
 */
class RoleServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RoleService $roleService;
    protected RoleRepository $roleRepository;
    protected PermissionService $permissionService;
    protected User $adminUser;
    protected Role $testRole;
    protected Role $adminRole;
    protected Role $userRole;
    protected Permission $testPermission;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立服務實例
        $this->roleRepository = app(RoleRepository::class);
        $this->permissionService = app(PermissionService::class);
        $this->roleService = new RoleService(
            $this->roleRepository,
            $this->permissionService
        );
        
        // 建立測試資料
        $this->createTestData();
    }

    /**
     * 建立測試資料
     */
    private function createTestData(): void
    {
        // 建立權限
        $this->testPermission = Permission::create([
            'name' => 'user.manage',
            'display_name' => '使用者管理',
            'description' => '管理使用者帳號',
            'module' => 'user'
        ]);

        Permission::create([
            'name' => 'role.manage',
            'display_name' => '角色管理',
            'description' => '管理角色權限',
            'module' => 'role'
        ]);

        // 建立角色
        $this->testRole = Role::create([
            'name' => 'test_role',
            'display_name' => '測試角色',
            'description' => '用於測試的角色'
        ]);

        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理員',
            'description' => '一般管理員'
        ]);

        $this->userRole = Role::create([
            'name' => 'user',
            'display_name' => '一般使用者',
            'description' => '一般使用者'
        ]);

        // 建立系統角色
        Role::create([
            'name' => 'super_admin',
            'display_name' => '超級管理員',
            'description' => '超級管理員'
        ]);

        // 建立管理員使用者
        $this->adminUser = User::factory()->create([
            'username' => 'admin',
            'name' => '管理員',
            'email' => 'admin@example.com',
            'is_active' => true
        ]);

        // 設定當前認證使用者
        $this->actingAs($this->adminUser);
    }

    /**
     * 測試建立角色成功
     */
    public function test_create_role_successfully(): void
    {
        // 確保認證狀態
        $this->actingAs($this->adminUser);
        
        $roleData = [
            'name' => 'new_role',
            'display_name' => '新角色',
            'description' => '這是一個新角色',
            'permissions' => ['user.manage']
        ];

        $role = $this->roleService->createRole($roleData);

        $this->assertInstanceOf(Role::class, $role);
        $this->assertEquals('new_role', $role->name);
        $this->assertEquals('新角色', $role->display_name);
        $this->assertEquals('這是一個新角色', $role->description);
        $this->assertTrue($role->hasPermission('user.manage'));
    }

    /**
     * 測試建立角色時驗證失敗
     */
    public function test_create_role_validation_fails(): void
    {
        $this->expectException(ValidationException::class);

        $roleData = [
            'name' => 'a', // 太短
            'display_name' => '', // 必填
            'permissions' => ['nonexistent.permission'] // 不存在的權限
        ];

        $this->roleService->createRole($roleData);
    }

    /**
     * 測試建立角色時名稱重複
     */
    public function test_create_role_duplicate_name(): void
    {
        $this->expectException(ValidationException::class);

        $roleData = [
            'name' => 'test_role', // 已存在
            'display_name' => '重複角色'
        ];

        $this->roleService->createRole($roleData);
    }

    /**
     * 測試建立角色時使用系統保留名稱
     */
    public function test_create_role_system_reserved_name(): void
    {
        $this->expectException(ValidationException::class);

        $roleData = [
            'name' => 'super_admin', // 系統保留名稱
            'display_name' => '測試超級管理員'
        ];

        $this->roleService->createRole($roleData);
    }

    /**
     * 測試更新角色成功
     */
    public function test_update_role_successfully(): void
    {
        $updateData = [
            'display_name' => '更新後的角色',
            'description' => '更新後的描述',
            'permissions' => ['user.manage', 'role.manage']
        ];

        $updatedRole = $this->roleService->updateRole($this->testRole, $updateData);

        $this->assertEquals('更新後的角色', $updatedRole->display_name);
        $this->assertEquals('更新後的描述', $updatedRole->description);
        $this->assertTrue($updatedRole->hasPermission('user.manage'));
        $this->assertTrue($updatedRole->hasPermission('role.manage'));
    }

    /**
     * 測試更新角色名稱
     */
    public function test_update_role_name(): void
    {
        $updateData = [
            'name' => 'updated_role'
        ];

        $updatedRole = $this->roleService->updateRole($this->testRole, $updateData);

        $this->assertEquals('updated_role', $updatedRole->name);
    }

    /**
     * 測試刪除角色成功
     */
    public function test_delete_role_successfully(): void
    {
        $result = $this->roleService->deleteRole($this->testRole);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('roles', ['id' => $this->testRole->id]);
    }

    /**
     * 測試無法刪除系統角色
     */
    public function test_cannot_delete_system_role(): void
    {
        $superAdminRole = Role::where('name', 'super_admin')->first();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('無法修改系統保護角色');

        $this->roleService->deleteRole($superAdminRole);
    }

    /**
     * 測試無法刪除有使用者的角色
     */
    public function test_cannot_delete_role_with_users(): void
    {
        // 為角色指派使用者
        $this->adminUser->roles()->attach($this->testRole->id);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('無法刪除角色，存在以下依賴關係');

        $this->roleService->deleteRole($this->testRole);
    }

    /**
     * 測試強制刪除有使用者的角色
     */
    public function test_force_delete_role_with_users(): void
    {
        // 為角色指派使用者
        $this->adminUser->roles()->attach($this->testRole->id);

        $result = $this->roleService->deleteRole($this->testRole, true);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('roles', ['id' => $this->testRole->id]);
        $this->assertDatabaseMissing('user_roles', [
            'user_id' => $this->adminUser->id,
            'role_id' => $this->testRole->id
        ]);
    }

    /**
     * 測試為角色指派權限
     */
    public function test_assign_permissions_to_role(): void
    {
        $result = $this->roleService->assignPermissionsToRole($this->testRole, ['user.manage', 'role.manage']);

        $this->assertTrue($result);
        $this->assertTrue($this->testRole->hasPermission('user.manage'));
        $this->assertTrue($this->testRole->hasPermission('role.manage'));
    }

    /**
     * 測試同步角色權限
     */
    public function test_sync_role_permissions(): void
    {
        // 先指派一些權限
        $this->testRole->permissions()->attach($this->testPermission->id);

        // 同步為只有 role.manage 權限
        $result = $this->roleService->syncRolePermissions($this->testRole, ['role.manage']);

        $this->assertTrue($result);
        $this->assertFalse($this->testRole->hasPermission('user.manage'));
        $this->assertTrue($this->testRole->hasPermission('role.manage'));
    }

    /**
     * 測試移除角色權限
     */
    public function test_remove_permission_from_role(): void
    {
        $this->testRole->permissions()->attach($this->testPermission->id);
        $this->assertTrue($this->testRole->hasPermission('user.manage'));

        $result = $this->roleService->removePermissionFromRole($this->testRole, 'user.manage');

        $this->assertTrue($result);
        $this->assertFalse($this->testRole->hasPermission('user.manage'));
    }

    /**
     * 測試複製角色
     */
    public function test_duplicate_role(): void
    {
        // 為原角色指派權限
        $this->testRole->permissions()->attach($this->testPermission->id);

        $newRoleData = [
            'name' => 'duplicated_role',
            'display_name' => '複製的角色',
            'description' => '從測試角色複製而來'
        ];

        $duplicatedRole = $this->roleService->duplicateRole($this->testRole, $newRoleData);

        $this->assertInstanceOf(Role::class, $duplicatedRole);
        $this->assertEquals('duplicated_role', $duplicatedRole->name);
        $this->assertEquals('複製的角色', $duplicatedRole->display_name);
        $this->assertTrue($duplicatedRole->hasPermission('user.manage'));
    }

    /**
     * 測試取得角色權限樹狀結構
     */
    public function test_get_role_permission_tree(): void
    {
        $this->testRole->permissions()->attach($this->testPermission->id);

        $tree = $this->roleService->getRolePermissionTree($this->testRole);

        $this->assertIsArray($tree);
        $this->assertCount(1, $tree);
        $this->assertEquals('user', $tree[0]['module']);
        $this->assertCount(1, $tree[0]['permissions']);
        $this->assertEquals('user.manage', $tree[0]['permissions'][0]->name);
    }

    /**
     * 測試取得可指派的角色
     */
    public function test_get_assignable_roles(): void
    {
        $assignableRoles = $this->roleService->getAssignableRoles();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $assignableRoles);
        
        // 確認不包含超級管理員角色
        $roleNames = $assignableRoles->pluck('name')->toArray();
        $this->assertNotContains('super_admin', $roleNames);
        $this->assertContains('admin', $roleNames);
        $this->assertContains('user', $roleNames);
    }

    /**
     * 測試批量更新角色狀態
     */
    public function test_bulk_update_role_status(): void
    {
        // 建立另一個非系統角色
        $anotherRole = Role::create([
            'name' => 'another_role',
            'display_name' => '另一個角色',
            'description' => '用於測試的另一個角色'
        ]);

        $roleIds = [$this->testRole->id, $anotherRole->id];

        $updatedCount = $this->roleService->bulkUpdateRoleStatus($roleIds, false);

        $this->assertEquals(2, $updatedCount);
    }

    /**
     * 測試批量更新時包含系統角色
     */
    public function test_bulk_update_cannot_modify_system_roles(): void
    {
        $superAdminRole = Role::where('name', 'super_admin')->first();
        $roleIds = [$this->testRole->id, $superAdminRole->id];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('無法修改系統保護角色的狀態');

        $this->roleService->bulkUpdateRoleStatus($roleIds, false);
    }

    /**
     * 測試檢查是否為系統角色
     */
    public function test_is_system_role(): void
    {
        $superAdminRole = Role::where('name', 'super_admin')->first();

        // 測試系統角色無法被修改
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('無法修改系統保護角色');

        $this->roleService->updateRole($superAdminRole, ['display_name' => '測試更新']);
    }

    /**
     * 測試取得角色刪除依賴檢查
     */
    public function test_get_role_deletion_dependencies(): void
    {
        // 測試系統角色
        $superAdminRole = Role::where('name', 'super_admin')->first();
        $dependencies = $this->roleService->getRoleDeletionDependencies($superAdminRole);

        $this->assertArrayHasKey('system_protected', $dependencies);
        $this->assertEquals('此角色為系統保護角色，無法刪除', $dependencies['system_protected']);

        // 測試有使用者的角色
        $this->adminUser->roles()->attach($this->testRole->id);
        $dependencies = $this->roleService->getRoleDeletionDependencies($this->testRole);

        $this->assertArrayHasKey('users', $dependencies);
        $this->assertStringContainsString('個使用者正在使用此角色', $dependencies['users']);
    }

    /**
     * 測試驗證角色建立規則
     */
    public function test_validate_role_creation_rules(): void
    {
        $data = [
            'name' => 'super_admin', // 系統保留名稱
            'permissions' => ['nonexistent.permission'] // 不存在的權限
        ];

        $errors = $this->roleService->validateRoleCreationRules($data);

        $this->assertArrayHasKey('permissions', $errors);
        $this->assertStringContainsString('不存在', $errors['permissions'][0]);
    }

    /**
     * 測試取得角色統計資訊
     */
    public function test_get_role_stats(): void
    {
        $stats = $this->roleService->getRoleStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_roles', $stats);
        $this->assertArrayHasKey('roles_with_users', $stats);
        $this->assertArrayHasKey('roles_with_permissions', $stats);
        $this->assertArrayHasKey('average_permissions_per_role', $stats);
        $this->assertArrayHasKey('most_used_roles', $stats);
    }

    /**
     * 測試搜尋角色
     */
    public function test_search_roles(): void
    {
        $results = $this->roleService->searchRoles('test', 5);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $results);
        $this->assertCount(1, $results);
        $this->assertEquals('test_role', $results->first()->name);
    }

    /**
     * 測試角色名稱格式驗證
     */
    public function test_role_name_format_validation(): void
    {
        // 測試包含特殊字元的角色名稱
        $this->expectException(ValidationException::class);

        $invalidData = [
            'name' => 'role@name', // 包含 @
            'display_name' => '測試角色'
        ];

        $this->roleService->createRole($invalidData);
    }

    /**
     * 測試建立角色時的日誌記錄
     */
    public function test_create_role_logging(): void
    {
        Log::shouldReceive('info')
            ->with('Role created successfully', \Mockery::type('array'))
            ->once()
            ->andReturn(null);

        Log::shouldReceive('info')
            ->with('All permission cache cleared', \Mockery::type('array'))
            ->once()
            ->andReturn(null);

        $roleData = [
            'name' => 'log_test_role',
            'display_name' => '日誌測試角色'
        ];

        $this->roleService->createRole($roleData);
    }

    /**
     * 測試更新角色時的日誌記錄
     */
    public function test_update_role_logging(): void
    {
        Log::shouldReceive('info')
            ->with('Role updated successfully', \Mockery::type('array'))
            ->once()
            ->andReturn(null);

        Log::shouldReceive('info')
            ->with('All permission cache cleared', \Mockery::type('array'))
            ->once()
            ->andReturn(null);

        $updateData = [
            'display_name' => '更新後的角色'
        ];

        $this->roleService->updateRole($this->testRole, $updateData);
    }

    /**
     * 測試刪除角色時的日誌記錄
     */
    public function test_delete_role_logging(): void
    {
        Log::shouldReceive('info')
            ->with('Role deleted successfully', \Mockery::type('array'))
            ->once()
            ->andReturn(null);

        Log::shouldReceive('info')
            ->with('All permission cache cleared', \Mockery::type('array'))
            ->once()
            ->andReturn(null);

        $this->roleService->deleteRole($this->testRole);
    }

    /**
     * 測試資料庫交易回滾
     */
    public function test_database_transaction_rollback(): void
    {
        // 使用無效的權限名稱觸發錯誤
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $roleData = [
            'name' => 'transaction_test',
            'display_name' => '交易測試',
            'permissions' => ['invalid.permission']
        ];

        $this->roleService->createRole($roleData);
    }

    /**
     * 測試角色描述長度限制
     */
    public function test_role_description_length_limit(): void
    {
        $this->expectException(ValidationException::class);

        $roleData = [
            'name' => 'long_desc_role',
            'display_name' => '長描述角色',
            'description' => str_repeat('a', 501) // 超過 500 字元限制
        ];

        $this->roleService->createRole($roleData);
    }

    /**
     * 測試角色顯示名稱長度限制
     */
    public function test_role_display_name_length_limit(): void
    {
        $this->expectException(ValidationException::class);

        $roleData = [
            'name' => 'long_name_role',
            'display_name' => str_repeat('a', 101) // 超過 100 字元限制
        ];

        $this->roleService->createRole($roleData);
    }

    /**
     * 測試空權限陣列
     */
    public function test_empty_permissions_array(): void
    {
        $roleData = [
            'name' => 'no_permissions_role',
            'display_name' => '無權限角色',
            'permissions' => []
        ];

        $role = $this->roleService->createRole($roleData);

        $this->assertInstanceOf(Role::class, $role);
        $this->assertEquals(0, $role->permissions()->count());
    }

    /**
     * 測試更新不存在的角色
     */
    public function test_update_nonexistent_role(): void
    {
        $nonexistentRole = new Role();
        $nonexistentRole->id = 99999;
        $nonexistentRole->name = 'nonexistent';

        $this->expectException(\Exception::class);

        $this->roleService->updateRole($nonexistentRole, ['display_name' => '測試']);
    }
}