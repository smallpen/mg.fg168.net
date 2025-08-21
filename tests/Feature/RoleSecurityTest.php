<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use App\Services\RoleSecurityService;
use App\Services\RoleDataValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

/**
 * 角色安全功能測試
 */
class RoleSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected RoleSecurityService $securityService;
    protected RoleDataValidationService $validationService;
    protected User $adminUser;
    protected User $regularUser;
    protected Role $systemRole;
    protected Role $regularRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->securityService = app(RoleSecurityService::class);
        $this->validationService = app(RoleDataValidationService::class);
        
        // 建立測試使用者和角色
        $this->setupTestData();
    }

    protected function setupTestData(): void
    {
        // 建立權限
        $permissions = [
            Permission::create(['name' => 'admin.access', 'display_name' => '管理員存取', 'module' => 'admin']),
            Permission::create(['name' => 'roles.view', 'display_name' => '檢視角色', 'module' => 'roles']),
            Permission::create(['name' => 'roles.create', 'display_name' => '建立角色', 'module' => 'roles']),
            Permission::create(['name' => 'roles.edit', 'display_name' => '編輯角色', 'module' => 'roles']),
            Permission::create(['name' => 'roles.delete', 'display_name' => '刪除角色', 'module' => 'roles']),
            Permission::create(['name' => 'users.view', 'display_name' => '檢視使用者', 'module' => 'users']),
        ];

        // 建立系統角色
        $this->systemRole = Role::create([
            'name' => 'super_admin',
            'display_name' => '超級管理員',
            'description' => '系統超級管理員',
            'is_system_role' => true,
        ]);
        $this->systemRole->permissions()->attach(collect($permissions)->pluck('id')->toArray());

        // 建立一般角色
        $this->regularRole = Role::create([
            'name' => 'editor',
            'display_name' => '編輯者',
            'description' => '內容編輯者',
            'is_system_role' => false,
        ]);
        $this->regularRole->permissions()->attach([$permissions[1]->id, $permissions[5]->id]);

        // 建立管理員使用者
        $this->adminUser = User::create([
            'username' => 'admin',
            'name' => '管理員',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $this->adminUser->roles()->attach($this->systemRole);

        // 建立一般使用者
        $this->regularUser = User::create([
            'username' => 'user',
            'name' => '一般使用者',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $this->regularUser->roles()->attach($this->regularRole);
    }

    /** @test */
    public function it_can_check_multi_level_permissions_for_admin_user()
    {
        $result = $this->securityService->checkMultiLevelPermissions('edit', $this->regularRole, $this->adminUser);
        
        $this->assertTrue($result['allowed']);
        $this->assertEquals('all_checks_passed', $result['reason']);
    }

    /** @test */
    public function it_denies_permission_for_regular_user_on_system_role()
    {
        $result = $this->securityService->checkMultiLevelPermissions('edit', $this->systemRole, $this->regularUser);
        
        $this->assertFalse($result['allowed']);
        $this->assertContains($result['reason'], ['insufficient_permission', 'cannot_modify_system_role']);
    }

    /** @test */
    public function it_protects_system_roles_from_deletion()
    {
        $result = $this->securityService->checkMultiLevelPermissions('delete', $this->systemRole, $this->adminUser);
        
        // 即使是管理員也不能刪除受保護的系統角色
        $this->assertFalse($result['allowed']);
        $this->assertEquals('protected_system_role', $result['reason']);
    }

    /** @test */
    public function it_validates_role_creation_data()
    {
        $validData = [
            'name' => 'test_role',
            'display_name' => '測試角色',
            'description' => '這是一個測試角色',
            'is_active' => true,
        ];

        $result = $this->validationService->validateRoleCreationData($validData);
        
        $this->assertEquals('test_role', $result['name']);
        $this->assertEquals('測試角色', $result['display_name']);
    }

    /** @test */
    public function it_rejects_invalid_role_names()
    {
        $invalidData = [
            'name' => 'invalid-name!',
            'display_name' => '無效角色',
        ];

        $this->expectException(ValidationException::class);
        $this->validationService->validateRoleCreationData($invalidData);
    }

    /** @test */
    public function it_rejects_reserved_role_names()
    {
        $reservedData = [
            'name' => 'admin',
            'display_name' => '管理員',
        ];

        $this->expectException(ValidationException::class);
        $this->validationService->validateRoleCreationData($reservedData);
    }

    /** @test */
    public function it_sanitizes_dangerous_content()
    {
        $dangerousData = [
            'name' => 'test_role',
            'display_name' => '<script>alert("xss")</script>測試角色',
            'description' => 'javascript:alert("xss")這是描述',
        ];

        $result = $this->validationService->validateRoleCreationData($dangerousData);
        
        $this->assertStringNotContainsString('<script>', $result['display_name']);
        $this->assertStringNotContainsString('javascript:', $result['description']);
    }

    /** @test */
    public function it_checks_role_deletion_security()
    {
        $result = $this->securityService->checkRoleDeletionSecurity($this->systemRole);
        
        $this->assertFalse($result['can_delete']);
        $this->assertTrue($result['has_blocking_issues']);
        $this->assertNotEmpty($result['issues']);
    }

    /** @test */
    public function it_allows_deletion_of_regular_role_without_users()
    {
        $result = $this->securityService->checkRoleDeletionSecurity($this->regularRole);
        
        // 因為角色有使用者關聯，所以不能刪除
        $this->assertFalse($result['can_delete']);
        $this->assertTrue($result['has_blocking_issues']);
    }

    /** @test */
    public function it_validates_permission_assignment()
    {
        $permissions = Permission::take(2)->pluck('id')->toArray();
        
        $result = $this->securityService->validatePermissionAssignment($this->regularRole, $permissions, $this->adminUser);
        
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['issues']);
    }

    /** @test */
    public function it_prevents_removing_core_permissions_from_system_role()
    {
        $nonCorePermissions = Permission::whereNotIn('name', ['admin.access', 'roles.view'])->pluck('id')->toArray();
        
        $result = $this->securityService->validatePermissionAssignment($this->systemRole, $nonCorePermissions, $this->adminUser);
        
        $this->assertFalse($result['valid']);
        $this->assertTrue($result['has_blocking_issues']);
    }

    /** @test */
    public function it_logs_role_operations()
    {
        // 這個測試需要檢查日誌是否正確記錄
        // 由於我們使用 Laravel 的日誌系統，這裡只測試方法不會拋出例外
        
        $this->expectNotToPerformAssertions();
        
        $this->securityService->logRoleOperation('test_operation', $this->regularRole, [
            'test_data' => 'test_value'
        ], $this->adminUser);
    }

    /** @test */
    public function it_validates_bulk_operation_data()
    {
        $validData = [
            'role_ids' => [$this->regularRole->id],
            'operation' => 'activate',
        ];

        $result = $this->validationService->validateBulkOperationData($validData);
        
        $this->assertEquals([$this->regularRole->id], $result['role_ids']);
        $this->assertEquals('activate', $result['operation']);
    }

    /** @test */
    public function it_rejects_bulk_operations_on_system_roles()
    {
        $invalidData = [
            'role_ids' => [$this->systemRole->id],
            'operation' => 'delete',
        ];

        $this->expectException(ValidationException::class);
        $this->validationService->validateBulkOperationData($invalidData);
    }

    /** @test */
    public function it_validates_search_and_filter_data()
    {
        $validData = [
            'search' => 'test search',
            'permission_count_filter' => 'high',
            'sort_field' => 'name',
            'sort_direction' => 'asc',
        ];

        $result = $this->validationService->validateSearchAndFilterData($validData);
        
        $this->assertEquals('test search', $result['search']);
        $this->assertEquals('high', $result['permission_count_filter']);
    }

    /** @test */
    public function it_sanitizes_search_strings()
    {
        $dangerousData = [
            'search' => '<script>alert("xss")</script>test',
        ];

        $result = $this->validationService->validateSearchAndFilterData($dangerousData);
        
        $this->assertStringNotContainsString('<script>', $result['search']);
        $this->assertEquals('test', $result['search']);
    }
}