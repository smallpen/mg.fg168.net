<?php

namespace Tests\Unit\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Services\PermissionValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * 權限驗證服務測試
 */
class PermissionValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PermissionValidationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PermissionValidationService();
    }

    /** @test */
    public function it_validates_create_data_successfully()
    {
        $data = [
            'name' => 'users.view',
            'display_name' => '檢視使用者',
            'description' => '檢視使用者列表的權限',
            'module' => 'users',
            'type' => 'view',
            'dependencies' => [],
        ];

        $result = $this->service->validateCreateData($data);

        $this->assertEquals('users.view', $result['name']);
        $this->assertEquals('檢視使用者', $result['display_name']);
        $this->assertEquals('檢視使用者列表的權限', $result['description']);
        $this->assertEquals('users', $result['module']);
        $this->assertEquals('view', $result['type']);
        $this->assertEquals([], $result['dependencies']);
    }

    /** @test */
    public function it_fails_validation_for_invalid_permission_name()
    {
        $data = [
            'name' => 'Invalid-Name!',
            'display_name' => '檢視使用者',
            'module' => 'users',
            'type' => 'view',
        ];

        $this->expectException(ValidationException::class);
        $this->service->validateCreateData($data);
    }

    /** @test */
    public function it_fails_validation_for_duplicate_permission_name()
    {
        Permission::create([
            'name' => 'users.view',
            'display_name' => '檢視使用者',
            'module' => 'users',
            'type' => 'view',
        ]);

        $data = [
            'name' => 'users.view',
            'display_name' => '檢視使用者',
            'module' => 'users',
            'type' => 'view',
        ];

        $this->expectException(ValidationException::class);
        $this->service->validateCreateData($data);
    }

    /** @test */
    public function it_fails_validation_for_invalid_permission_type()
    {
        $data = [
            'name' => 'users.view',
            'display_name' => '檢視使用者',
            'module' => 'users',
            'type' => 'invalid_type',
        ];

        $this->expectException(ValidationException::class);
        $this->service->validateCreateData($data);
    }

    /** @test */
    public function it_validates_update_data_for_regular_permission()
    {
        $permission = Permission::create([
            'name' => 'users.view',
            'display_name' => '檢視使用者',
            'module' => 'users',
            'type' => 'view',
        ]);

        $data = [
            'name' => 'users.list',
            'display_name' => '列出使用者',
            'description' => '列出所有使用者',
            'module' => 'users',
            'type' => 'view',
        ];

        $result = $this->service->validateUpdateData($permission, $data);

        $this->assertEquals('users.list', $result['name']);
        $this->assertEquals('列出使用者', $result['display_name']);
    }

    /** @test */
    public function it_validates_update_data_for_system_permission()
    {
        $permission = Permission::create([
            'name' => 'system.core',
            'display_name' => '系統核心',
            'module' => 'system',
            'type' => 'manage',
        ]);

        $data = [
            'name' => 'system.new', // 這個應該被忽略
            'display_name' => '更新的系統核心',
            'module' => 'new_module', // 這個應該被忽略
            'type' => 'view',
        ];

        $result = $this->service->validateUpdateData($permission, $data);

        // 系統權限不應該包含 name 和 module
        $this->assertArrayNotHasKey('name', $result);
        $this->assertArrayNotHasKey('module', $result);
        $this->assertEquals('更新的系統核心', $result['display_name']);
        $this->assertEquals('view', $result['type']);
    }

    /** @test */
    public function it_validates_dependencies_successfully()
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

        $result = $this->service->validateDependencies($permission2->id, [$permission1->id]);
        $this->assertTrue($result);
    }

    /** @test */
    public function it_fails_validation_for_circular_dependencies()
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
        $permission1->dependencies()->attach($permission2->id);

        // 嘗試建立 permission2 依賴 permission1（循環依賴）
        $this->expectException(ValidationException::class);
        $this->service->validateDependencies($permission2->id, [$permission1->id]);
    }

    /** @test */
    public function it_validates_permission_name_format()
    {
        $this->assertTrue($this->service->validatePermissionName('users.view'));
        $this->assertTrue($this->service->validatePermissionName('system_admin.manage'));
        $this->assertTrue($this->service->validatePermissionName('reports.export_data'));

        $this->expectException(ValidationException::class);
        $this->service->validatePermissionName('Invalid-Name!');
    }

    /** @test */
    public function it_validates_permission_name_uniqueness()
    {
        Permission::create([
            'name' => 'users.view',
            'display_name' => '檢視使用者',
            'module' => 'users',
            'type' => 'view',
        ]);

        $this->expectException(ValidationException::class);
        $this->service->validatePermissionNameUnique('users.view');
    }

    /** @test */
    public function it_validates_permission_name_uniqueness_with_exclusion()
    {
        $permission = Permission::create([
            'name' => 'users.view',
            'display_name' => '檢視使用者',
            'module' => 'users',
            'type' => 'view',
        ]);

        // 排除自己的 ID，應該通過驗證
        $this->assertTrue($this->service->validatePermissionNameUnique('users.view', $permission->id));
    }

    /** @test */
    public function it_validates_module_name()
    {
        $this->assertTrue($this->service->validateModule('users'));
        $this->assertTrue($this->service->validateModule('roles'));
        $this->assertTrue($this->service->validateModule('permissions'));

        $this->expectException(ValidationException::class);
        $this->service->validateModule('invalid_module');
    }

    /** @test */
    public function it_validates_permission_type()
    {
        $this->assertTrue($this->service->validatePermissionType('view'));
        $this->assertTrue($this->service->validatePermissionType('create'));
        $this->assertTrue($this->service->validatePermissionType('edit'));
        $this->assertTrue($this->service->validatePermissionType('delete'));
        $this->assertTrue($this->service->validatePermissionType('manage'));

        $this->expectException(ValidationException::class);
        $this->service->validatePermissionType('invalid_type');
    }

    /** @test */
    public function it_validates_permission_can_be_deleted()
    {
        $permission = Permission::create([
            'name' => 'users.view',
            'display_name' => '檢視使用者',
            'module' => 'users',
            'type' => 'view',
        ]);

        $this->assertTrue($this->service->validateCanDelete($permission));
    }

    /** @test */
    public function it_fails_validation_for_system_permission_deletion()
    {
        $permission = Permission::create([
            'name' => 'system.core',
            'display_name' => '系統核心',
            'module' => 'system',
            'type' => 'manage',
        ]);

        $this->expectException(ValidationException::class);
        $this->service->validateCanDelete($permission);
    }

    /** @test */
    public function it_fails_validation_for_permission_in_use()
    {
        $permission = Permission::create([
            'name' => 'users.view',
            'display_name' => '檢視使用者',
            'module' => 'users',
            'type' => 'view',
        ]);

        $role = Role::create([
            'name' => 'user',
            'display_name' => '一般使用者',
        ]);

        $role->permissions()->attach($permission);

        $this->expectException(ValidationException::class);
        $this->service->validateCanDelete($permission);
    }

    /** @test */
    public function it_fails_validation_for_permission_with_dependents()
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

        $permission2->dependencies()->attach($permission1);

        $this->expectException(ValidationException::class);
        $this->service->validateCanDelete($permission1);
    }

    /** @test */
    public function it_validates_bulk_operation_data()
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

        $data = [
            'action' => 'delete',
            'permission_ids' => [$permission1->id, $permission2->id],
            'confirm' => true,
        ];

        $result = $this->service->validateBulkOperation($data);

        $this->assertEquals('delete', $result['action']);
        $this->assertEquals([$permission1->id, $permission2->id], $result['permission_ids']);
        $this->assertTrue($result['confirm']);
    }

    /** @test */
    public function it_fails_bulk_operation_validation_without_confirmation()
    {
        $permission = Permission::create([
            'name' => 'users.view',
            'display_name' => '檢視使用者',
            'module' => 'users',
            'type' => 'view',
        ]);

        $data = [
            'action' => 'delete',
            'permission_ids' => [$permission->id],
            // 沒有 confirm 欄位，應該觸發 required_if 驗證
        ];

        $this->expectException(ValidationException::class);
        $this->service->validateBulkOperation($data);
    }

    /** @test */
    public function it_validates_import_data()
    {
        $data = [
            [
                'name' => 'users.view',
                'display_name' => '檢視使用者',
                'description' => '檢視使用者列表',
                'module' => 'users',
                'type' => 'view',
                'dependencies' => [],
            ],
            [
                'name' => 'users.edit',
                'display_name' => '編輯使用者',
                'description' => '編輯使用者資料',
                'module' => 'users',
                'type' => 'edit',
                'dependencies' => ['users.view'],
            ],
        ];

        $result = $this->service->validateImportData($data);

        $this->assertCount(2, $result);
        $this->assertEquals('users.view', $result[0]['name']);
        $this->assertEquals('users.edit', $result[1]['name']);
    }

    /** @test */
    public function it_fails_import_validation_for_invalid_data()
    {
        $data = [
            [
                'name' => '', // 空名稱
                'display_name' => '檢視使用者',
                'module' => 'users',
                'type' => 'view',
            ],
            [
                'name' => 'users.edit',
                'display_name' => '', // 空顯示名稱
                'module' => 'users',
                'type' => 'invalid_type', // 無效類型
            ],
        ];

        $this->expectException(ValidationException::class);
        $this->service->validateImportData($data);
    }

    /** @test */
    public function it_sanitizes_permission_data()
    {
        $data = [
            'name' => '  USERS.VIEW  ',
            'display_name' => '  檢視使用者  ',
            'description' => '  檢視使用者列表  ',
            'module' => '  USERS  ',
            'type' => '  VIEW  ',
            'dependencies' => [1, 2, 2, 3], // 包含重複
        ];

        $result = $this->service->validateCreateData($data);

        $this->assertEquals('users.view', $result['name']);
        $this->assertEquals('檢視使用者', $result['display_name']);
        $this->assertEquals('檢視使用者列表', $result['description']);
        $this->assertEquals('users', $result['module']);
        $this->assertEquals('view', $result['type']);
        $this->assertEquals([1, 2, 3], $result['dependencies']); // 去重並重新索引
    }
}