<?php

namespace Tests\Unit;

use App\Models\Permission;
use App\Models\Role;
use App\Repositories\PermissionRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 權限資料存取層測試
 */
class PermissionRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected PermissionRepository $permissionRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->permissionRepository = new PermissionRepository();
    }

    /**
     * 測試建立權限
     */
    public function test_create_permission(): void
    {
        $permissionData = [
            'name' => 'test.create',
            'display_name' => '建立測試',
            'description' => '建立測試項目的權限',
            'module' => 'test',
            'type' => 'create'
        ];

        $permission = $this->permissionRepository->createPermission($permissionData);

        $this->assertInstanceOf(Permission::class, $permission);
        $this->assertEquals('test.create', $permission->name);
        $this->assertEquals('建立測試', $permission->display_name);
        $this->assertEquals('test', $permission->module);
        $this->assertEquals('create', $permission->type);
    }

    /**
     * 測試更新權限
     */
    public function test_update_permission(): void
    {
        $permission = Permission::create([
            'name' => 'test.view',
            'display_name' => '檢視測試',
            'module' => 'test',
            'type' => 'view'
        ]);

        $updateData = [
            'display_name' => '檢視測試項目',
            'description' => '檢視測試項目的權限'
        ];

        $result = $this->permissionRepository->updatePermission($permission, $updateData);

        $this->assertTrue($result);
        $permission->refresh();
        $this->assertEquals('檢視測試項目', $permission->display_name);
        $this->assertEquals('檢視測試項目的權限', $permission->description);
    }

    /**
     * 測試取得分頁權限列表
     */
    public function test_get_paginated_permissions(): void
    {
        // 建立測試資料
        Permission::create(['name' => 'user.view', 'display_name' => '檢視使用者', 'module' => 'user', 'type' => 'view']);
        Permission::create(['name' => 'user.create', 'display_name' => '建立使用者', 'module' => 'user', 'type' => 'create']);
        Permission::create(['name' => 'role.view', 'display_name' => '檢視角色', 'module' => 'role', 'type' => 'view']);

        $result = $this->permissionRepository->getPaginatedPermissions();

        $this->assertEquals(3, $result->total());
        $this->assertEquals(25, $result->perPage());
    }

    /**
     * 測試搜尋篩選
     */
    public function test_search_filter(): void
    {
        Permission::create(['name' => 'user.view', 'display_name' => '檢視使用者', 'module' => 'user', 'type' => 'view']);
        Permission::create(['name' => 'user.create', 'display_name' => '建立使用者', 'module' => 'user', 'type' => 'create']);
        Permission::create(['name' => 'role.view', 'display_name' => '檢視角色', 'module' => 'role', 'type' => 'view']);

        $filters = ['search' => '使用者'];
        $result = $this->permissionRepository->getPaginatedPermissions($filters);

        $this->assertEquals(2, $result->total());
    }

    /**
     * 測試模組篩選
     */
    public function test_module_filter(): void
    {
        Permission::create(['name' => 'user.view', 'display_name' => '檢視使用者', 'module' => 'user', 'type' => 'view']);
        Permission::create(['name' => 'user.create', 'display_name' => '建立使用者', 'module' => 'user', 'type' => 'create']);
        Permission::create(['name' => 'role.view', 'display_name' => '檢視角色', 'module' => 'role', 'type' => 'view']);

        $filters = ['module' => 'user'];
        $result = $this->permissionRepository->getPaginatedPermissions($filters);

        $this->assertEquals(2, $result->total());
    }

    /**
     * 測試按模組分組取得權限
     */
    public function test_get_permissions_by_module(): void
    {
        Permission::create(['name' => 'user.view', 'display_name' => '檢視使用者', 'module' => 'user', 'type' => 'view']);
        Permission::create(['name' => 'user.create', 'display_name' => '建立使用者', 'module' => 'user', 'type' => 'create']);
        Permission::create(['name' => 'role.view', 'display_name' => '檢視角色', 'module' => 'role', 'type' => 'view']);

        $grouped = $this->permissionRepository->getPermissionsByModule();

        $this->assertArrayHasKey('user', $grouped->toArray());
        $this->assertArrayHasKey('role', $grouped->toArray());
        $this->assertEquals(2, $grouped['user']->count());
        $this->assertEquals(1, $grouped['role']->count());
    }

    /**
     * 測試取得權限依賴關係
     */
    public function test_get_permission_dependencies(): void
    {
        $permission1 = Permission::create(['name' => 'user.create', 'display_name' => '建立使用者', 'module' => 'user', 'type' => 'create']);
        $permission2 = Permission::create(['name' => 'user.view', 'display_name' => '檢視使用者', 'module' => 'user', 'type' => 'view']);

        $permission1->addDependency($permission2);

        $dependencies = $this->permissionRepository->getPermissionDependencies($permission1->id);

        $this->assertEquals(1, $dependencies->count());
        $this->assertTrue($dependencies->contains('id', $permission2->id));
    }

    /**
     * 測試取得權限被依賴關係
     */
    public function test_get_permission_dependents(): void
    {
        $permission1 = Permission::create(['name' => 'user.create', 'display_name' => '建立使用者', 'module' => 'user', 'type' => 'create']);
        $permission2 = Permission::create(['name' => 'user.view', 'display_name' => '檢視使用者', 'module' => 'user', 'type' => 'view']);

        $permission1->addDependency($permission2);

        $dependents = $this->permissionRepository->getPermissionDependents($permission2->id);

        $this->assertEquals(1, $dependents->count());
        $this->assertTrue($dependents->contains('id', $permission1->id));
    }

    /**
     * 測試同步權限依賴關係
     */
    public function test_sync_dependencies(): void
    {
        $permission1 = Permission::create(['name' => 'user.create', 'display_name' => '建立使用者', 'module' => 'user', 'type' => 'create']);
        $permission2 = Permission::create(['name' => 'user.view', 'display_name' => '檢視使用者', 'module' => 'user', 'type' => 'view']);
        $permission3 = Permission::create(['name' => 'user.list', 'display_name' => '列出使用者', 'module' => 'user', 'type' => 'view']);

        $this->permissionRepository->syncDependencies($permission1, [$permission2->id, $permission3->id]);

        $dependencies = $permission1->dependencies;
        $this->assertEquals(2, $dependencies->count());
        $this->assertTrue($dependencies->contains('id', $permission2->id));
        $this->assertTrue($dependencies->contains('id', $permission3->id));
    }

    /**
     * 測試取得未使用的權限
     */
    public function test_get_unused_permissions(): void
    {
        $permission1 = Permission::create(['name' => 'user.create', 'display_name' => '建立使用者', 'module' => 'user', 'type' => 'create']);
        $permission2 = Permission::create(['name' => 'user.view', 'display_name' => '檢視使用者', 'module' => 'user', 'type' => 'view']);

        $role = Role::create(['name' => 'test_role', 'display_name' => '測試角色']);
        $role->permissions()->attach($permission1->id);

        $unusedPermissions = $this->permissionRepository->getUnusedPermissions();

        $this->assertEquals(1, $unusedPermissions->count());
        $this->assertTrue($unusedPermissions->contains('id', $permission2->id));
    }

    /**
     * 測試取得權限使用統計
     */
    public function test_get_permission_usage_stats(): void
    {
        Permission::create(['name' => 'user.create', 'display_name' => '建立使用者', 'module' => 'user', 'type' => 'create']);
        Permission::create(['name' => 'user.view', 'display_name' => '檢視使用者', 'module' => 'user', 'type' => 'view']);
        Permission::create(['name' => 'role.create', 'display_name' => '建立角色', 'module' => 'role', 'type' => 'create']);

        $role = Role::create(['name' => 'test_role', 'display_name' => '測試角色']);
        $role->permissions()->attach(Permission::where('name', 'user.create')->first()->id);

        $stats = $this->permissionRepository->getPermissionUsageStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('used', $stats);
        $this->assertArrayHasKey('unused', $stats);
        $this->assertArrayHasKey('usage_percentage', $stats);
        $this->assertArrayHasKey('modules', $stats);

        $this->assertEquals(3, $stats['total']);
        $this->assertEquals(1, $stats['used']);
        $this->assertEquals(2, $stats['unused']);
    }

    /**
     * 測試檢查權限是否可以刪除
     */
    public function test_can_delete_permission(): void
    {
        $permission1 = Permission::create(['name' => 'user.create', 'display_name' => '建立使用者', 'module' => 'user', 'type' => 'create']);
        $permission2 = Permission::create(['name' => 'user.view', 'display_name' => '檢視使用者', 'module' => 'user', 'type' => 'view']);

        // 沒有角色使用且沒有其他權限依賴的權限可以被刪除
        $this->assertTrue($this->permissionRepository->canDeletePermission($permission1));

        // 有其他權限依賴的權限不能被刪除
        $permission1->addDependency($permission2);
        $this->assertFalse($this->permissionRepository->canDeletePermission($permission2));

        // 有角色使用的權限不能被刪除
        $role = Role::create(['name' => 'test_role', 'display_name' => '測試角色']);
        $role->permissions()->attach($permission1->id);
        $this->assertFalse($this->permissionRepository->canDeletePermission($permission1));
    }

    /**
     * 測試檢查循環依賴
     */
    public function test_has_circular_dependency(): void
    {
        $permission1 = Permission::create(['name' => 'user.create', 'display_name' => '建立使用者', 'module' => 'user', 'type' => 'create']);
        $permission2 = Permission::create(['name' => 'user.view', 'display_name' => '檢視使用者', 'module' => 'user', 'type' => 'view']);

        // 測試自我依賴
        $this->assertTrue($this->permissionRepository->hasCircularDependency($permission1->id, [$permission1->id]));

        // 測試正常依賴
        $this->assertFalse($this->permissionRepository->hasCircularDependency($permission1->id, [$permission2->id]));

        // 建立依賴關係後測試循環依賴
        $permission1->addDependency($permission2);
        $this->assertTrue($this->permissionRepository->hasCircularDependency($permission2->id, [$permission1->id]));
    }

    /**
     * 測試刪除權限
     */
    public function test_delete_permission(): void
    {
        $permission = Permission::create(['name' => 'test.delete', 'display_name' => '刪除測試', 'module' => 'test', 'type' => 'delete']);

        $result = $this->permissionRepository->deletePermission($permission);

        $this->assertTrue($result);
        $this->assertNull(Permission::find($permission->id));
    }

    /**
     * 測試匯出權限
     */
    public function test_export_permissions(): void
    {
        $permission1 = Permission::create(['name' => 'user.create', 'display_name' => '建立使用者', 'module' => 'user', 'type' => 'create']);
        $permission2 = Permission::create(['name' => 'user.view', 'display_name' => '檢視使用者', 'module' => 'user', 'type' => 'view']);

        $permission1->addDependency($permission2);

        $exported = $this->permissionRepository->exportPermissions();

        $this->assertIsArray($exported);
        $this->assertCount(2, $exported);
        
        $exportedPermission1 = collect($exported)->firstWhere('name', 'user.create');
        $this->assertNotNull($exportedPermission1);
        $this->assertArrayHasKey('dependencies', $exportedPermission1);
        $this->assertContains('user.view', $exportedPermission1['dependencies']);
    }

    /**
     * 測試匯入權限
     */
    public function test_import_permissions(): void
    {
        $importData = [
            [
                'name' => 'test.create',
                'display_name' => '建立測試',
                'description' => '建立測試項目',
                'module' => 'test',
                'type' => 'create',
                'dependencies' => []
            ],
            [
                'name' => 'test.view',
                'display_name' => '檢視測試',
                'description' => '檢視測試項目',
                'module' => 'test',
                'type' => 'view',
                'dependencies' => []
            ]
        ];

        $result = $this->permissionRepository->importPermissions($importData);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('created', $result);
        $this->assertArrayHasKey('updated', $result);
        $this->assertEquals(2, $result['created']);
        $this->assertEquals(0, $result['updated']);

        $this->assertEquals(2, Permission::where('module', 'test')->count());
    }

    /**
     * 測試取得可用模組
     */
    public function test_get_available_modules(): void
    {
        Permission::create(['name' => 'user.view', 'display_name' => '檢視使用者', 'module' => 'user', 'type' => 'view']);
        Permission::create(['name' => 'role.view', 'display_name' => '檢視角色', 'module' => 'role', 'type' => 'view']);
        Permission::create(['name' => 'system.config', 'display_name' => '系統設定', 'module' => 'system', 'type' => 'manage']);

        $modules = $this->permissionRepository->getAvailableModules();

        $this->assertEquals(3, $modules->count());
        $this->assertTrue($modules->contains('user'));
        $this->assertTrue($modules->contains('role'));
        $this->assertTrue($modules->contains('system'));
    }

    /**
     * 測試取得可用類型
     */
    public function test_get_available_types(): void
    {
        Permission::create(['name' => 'user.view', 'display_name' => '檢視使用者', 'module' => 'user', 'type' => 'view']);
        Permission::create(['name' => 'user.create', 'display_name' => '建立使用者', 'module' => 'user', 'type' => 'create']);
        Permission::create(['name' => 'system.manage', 'display_name' => '系統管理', 'module' => 'system', 'type' => 'manage']);

        $types = $this->permissionRepository->getAvailableTypes();

        $this->assertEquals(3, $types->count());
        $this->assertTrue($types->contains('view'));
        $this->assertTrue($types->contains('create'));
        $this->assertTrue($types->contains('manage'));
    }

    /**
     * 測試搜尋權限
     */
    public function test_search_permissions(): void
    {
        Permission::create(['name' => 'user.view', 'display_name' => '檢視使用者', 'module' => 'user', 'type' => 'view']);
        Permission::create(['name' => 'user.create', 'display_name' => '建立使用者', 'module' => 'user', 'type' => 'create']);
        Permission::create(['name' => 'role.view', 'display_name' => '檢視角色', 'module' => 'role', 'type' => 'view']);

        $results = $this->permissionRepository->searchPermissions('使用者');

        $this->assertEquals(2, $results->count());
        $this->assertTrue($results->every(fn($p) => str_contains($p->display_name, '使用者')));
    }
}