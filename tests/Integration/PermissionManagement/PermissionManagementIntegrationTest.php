<?php

namespace Tests\Integration\PermissionManagement;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionImportExportService;
use App\Services\PermissionSecurityService;
use App\Services\PermissionUsageAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * 權限管理功能完整整合測試
 * 
 * 測試完整的權限管理工作流程，包含：
 * - 權限 CRUD 操作
 * - 依賴關係管理
 * - 使用情況分析
 * - 匯入匯出功能
 * - 安全性控制
 * - 審計功能
 */
class PermissionManagementIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $superAdmin;
    protected User $admin;
    protected User $regularUser;
    protected Role $superAdminRole;
    protected Role $adminRole;
    protected Role $userRole;

    protected PermissionImportExportService $importExportService;
    protected PermissionSecurityService $securityService;
    protected PermissionUsageAnalysisService $usageAnalysisService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupServices();
        $this->setupTestUsers();
        $this->setupTestPermissions();
    }

    protected function setupServices(): void
    {
        $this->importExportService = app(PermissionImportExportService::class);
        $this->securityService = app(PermissionSecurityService::class);
        $this->usageAnalysisService = app(PermissionUsageAnalysisService::class);
    }

    protected function setupTestUsers(): void
    {
        // 建立測試角色
        $this->superAdminRole = Role::create([
            'name' => 'super_admin',
            'display_name' => '超級管理員',
            'description' => '系統超級管理員',
        ]);

        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理員',
            'description' => '系統管理員',
        ]);

        $this->userRole = Role::create([
            'name' => 'user',
            'display_name' => '一般使用者',
            'description' => '一般使用者',
        ]);

        // 建立測試使用者
        $this->superAdmin = User::create([
            'username' => 'superadmin',
            'name' => '超級管理員',
            'email' => 'superadmin@test.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'username' => 'admin',
            'name' => '管理員',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->regularUser = User::create([
            'username' => 'user',
            'name' => '一般使用者',
            'email' => 'user@test.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        // 分配角色
        $this->superAdmin->roles()->attach($this->superAdminRole);
        $this->admin->roles()->attach($this->adminRole);
        $this->regularUser->roles()->attach($this->userRole);
    }

    protected function setupTestPermissions(): void
    {
        // 建立權限管理相關權限
        $permissions = [
            // 基本權限管理
            ['name' => 'permissions.view', 'display_name' => '檢視權限', 'module' => 'permissions', 'type' => 'view'],
            ['name' => 'permissions.create', 'display_name' => '建立權限', 'module' => 'permissions', 'type' => 'create'],
            ['name' => 'permissions.edit', 'display_name' => '編輯權限', 'module' => 'permissions', 'type' => 'edit'],
            ['name' => 'permissions.delete', 'display_name' => '刪除權限', 'module' => 'permissions', 'type' => 'delete'],
            
            // 進階權限管理
            ['name' => 'permissions.manage', 'display_name' => '管理權限', 'module' => 'permissions', 'type' => 'manage'],
            ['name' => 'permissions.export', 'display_name' => '匯出權限', 'module' => 'permissions', 'type' => 'export'],
            ['name' => 'permissions.import', 'display_name' => '匯入權限', 'module' => 'permissions', 'type' => 'import'],
            ['name' => 'permissions.test', 'display_name' => '測試權限', 'module' => 'permissions', 'type' => 'test'],
            
            // 系統權限
            ['name' => 'system.manage', 'display_name' => '系統管理', 'module' => 'system', 'type' => 'manage'],
            ['name' => 'admin.access', 'display_name' => '管理員存取', 'module' => 'admin', 'type' => 'view'],
            
            // 使用者管理權限
            ['name' => 'users.view', 'display_name' => '檢視使用者', 'module' => 'users', 'type' => 'view'],
            ['name' => 'users.create', 'display_name' => '建立使用者', 'module' => 'users', 'type' => 'create'],
            ['name' => 'users.edit', 'display_name' => '編輯使用者', 'module' => 'users', 'type' => 'edit'],
            ['name' => 'users.delete', 'display_name' => '刪除使用者', 'module' => 'users', 'type' => 'delete'],
        ];

        foreach ($permissions as $permissionData) {
            Permission::create($permissionData);
        }

        // 為超級管理員分配所有權限
        $this->superAdminRole->permissions()->attach(Permission::all());

        // 為管理員分配權限管理權限
        $adminPermissions = Permission::whereIn('name', [
            'permissions.view', 'permissions.create', 'permissions.edit', 'permissions.delete',
            'permissions.export', 'permissions.import', 'permissions.test',
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'admin.access'
        ])->get();
        $this->adminRole->permissions()->attach($adminPermissions);

        // 為一般使用者分配基本權限
        $userPermissions = Permission::whereIn('name', [
            'users.view'
        ])->get();
        $this->userRole->permissions()->attach($userPermissions);
    }

    /** @test */
    public function test_complete_permission_management_workflow()
    {
        $this->actingAs($this->superAdmin);

        // 1. 建立新權限
        $permissionData = [
            'name' => 'reports.view',
            'display_name' => '檢視報表',
            'description' => '檢視系統報表',
            'module' => 'reports',
            'type' => 'view',
        ];

        $response = $this->postJson('/admin/permissions', $permissionData);
        $response->assertStatus(201);

        $permission = Permission::where('name', 'reports.view')->first();
        $this->assertNotNull($permission);
        $this->assertEquals('檢視報表', $permission->display_name);

        // 2. 編輯權限
        $updateData = [
            'display_name' => '檢視報表（更新）',
            'description' => '檢視系統報表的更新描述',
        ];

        $response = $this->putJson("/admin/permissions/{$permission->id}", $updateData);
        $response->assertStatus(200);

        $permission->refresh();
        $this->assertEquals('檢視報表（更新）', $permission->display_name);

        // 3. 建立依賴權限並設定依賴關係
        $dependentPermission = Permission::create([
            'name' => 'reports.create',
            'display_name' => '建立報表',
            'module' => 'reports',
            'type' => 'create',
        ]);

        // 設定依賴關係：建立報表需要檢視報表權限
        $dependentPermission->dependencies()->attach($permission->id);

        // 4. 測試權限使用情況分析
        $this->adminRole->permissions()->attach($permission->id);
        
        $usageStats = $this->usageAnalysisService->getUsageStats();
        $this->assertArrayHasKey('total_permissions', $usageStats);
        $this->assertArrayHasKey('used_permissions', $usageStats);

        // 5. 測試權限匯出
        $exportData = $this->importExportService->exportPermissions();
        $this->assertArrayHasKey('permissions', $exportData);
        $this->assertArrayHasKey('metadata', $exportData);

        // 6. 刪除權限（應該檢查依賴關係）
        $response = $this->deleteJson("/admin/permissions/{$permission->id}");
        $response->assertStatus(422); // 應該失敗，因為有依賴關係

        // 7. 先移除依賴關係再刪除
        $dependentPermission->dependencies()->detach($permission->id);
        $response = $this->deleteJson("/admin/permissions/{$permission->id}");
        $response->assertStatus(200);

        $this->assertDatabaseMissing('permissions', ['id' => $permission->id]);
    }

    /** @test */
    public function test_permission_dependency_management()
    {
        $this->actingAs($this->superAdmin);

        // 建立測試權限
        $viewPermission = Permission::create([
            'name' => 'test.view',
            'display_name' => '測試檢視',
            'module' => 'test',
            'type' => 'view',
        ]);

        $editPermission = Permission::create([
            'name' => 'test.edit',
            'display_name' => '測試編輯',
            'module' => 'test',
            'type' => 'edit',
        ]);

        $deletePermission = Permission::create([
            'name' => 'test.delete',
            'display_name' => '測試刪除',
            'module' => 'test',
            'type' => 'delete',
        ]);

        // 建立依賴鏈：delete -> edit -> view
        $editPermission->dependencies()->attach($viewPermission->id);
        $deletePermission->dependencies()->attach($editPermission->id);

        // 測試依賴關係查詢
        $this->assertTrue($editPermission->dependencies->contains($viewPermission));
        $this->assertTrue($deletePermission->dependencies->contains($editPermission));

        // 測試完整依賴鏈
        $allDependencies = $deletePermission->getAllDependencies();
        $this->assertCount(2, $allDependencies);
        $this->assertTrue($allDependencies->contains($editPermission));
        $this->assertTrue($allDependencies->contains($viewPermission));

        // 測試循環依賴檢查
        $hasCircular = $viewPermission->hasCircularDependency([$deletePermission->id]);
        $this->assertTrue($hasCircular);

        // 測試依賴關係自動解析
        $testRole = Role::create([
            'name' => 'test_role',
            'display_name' => '測試角色',
        ]);

        // 分配刪除權限應該自動包含依賴權限
        $testRole->permissions()->attach($deletePermission->id);
        $testRole->syncPermissionDependencies();

        $rolePermissions = $testRole->permissions()->pluck('name')->toArray();
        $this->assertContains('test.delete', $rolePermissions);
        $this->assertContains('test.edit', $rolePermissions);
        $this->assertContains('test.view', $rolePermissions);
    }

    /** @test */
    public function test_circular_dependency_prevention()
    {
        $this->actingAs($this->superAdmin);

        // 建立測試權限
        $permissionA = Permission::create([
            'name' => 'test.a',
            'display_name' => '測試A',
            'module' => 'test',
            'type' => 'view',
        ]);

        $permissionB = Permission::create([
            'name' => 'test.b',
            'display_name' => '測試B',
            'module' => 'test',
            'type' => 'view',
        ]);

        $permissionC = Permission::create([
            'name' => 'test.c',
            'display_name' => '測試C',
            'module' => 'test',
            'type' => 'view',
        ]);

        // 建立依賴鏈：A -> B -> C
        $permissionA->dependencies()->attach($permissionB->id);
        $permissionB->dependencies()->attach($permissionC->id);

        // 嘗試建立循環依賴：C -> A（應該被阻止）
        $this->expectException(\InvalidArgumentException::class);
        $permissionC->addDependency($permissionA);
    }

    /** @test */
    public function test_access_control_for_different_users()
    {
        // 測試超級管理員存取
        $this->actingAs($this->superAdmin);
        $response = $this->get('/admin/permissions');
        $response->assertStatus(200);

        $response = $this->get('/admin/permissions/create');
        $response->assertStatus(200);

        // 測試管理員存取
        $this->actingAs($this->admin);
        $response = $this->get('/admin/permissions');
        $response->assertStatus(200);

        $response = $this->get('/admin/permissions/create');
        $response->assertStatus(200);

        // 測試一般使用者存取（應該被拒絕）
        $this->actingAs($this->regularUser);
        $response = $this->get('/admin/permissions');
        $response->assertStatus(403);

        $response = $this->get('/admin/permissions/create');
        $response->assertStatus(403);

        // 測試未登入使用者存取
        auth()->logout();
        $response = $this->get('/admin/permissions');
        $response->assertRedirect('/admin/login');
    }

    /** @test */
    public function test_permission_import_export_workflow()
    {
        $this->actingAs($this->superAdmin);

        // 1. 建立測試權限
        $testPermissions = [
            [
                'name' => 'export.test1',
                'display_name' => '匯出測試1',
                'module' => 'export_test',
                'type' => 'view',
            ],
            [
                'name' => 'export.test2',
                'display_name' => '匯出測試2',
                'module' => 'export_test',
                'type' => 'create',
                'dependencies' => ['export.test1'],
            ],
        ];

        foreach ($testPermissions as $permissionData) {
            $dependencies = $permissionData['dependencies'] ?? [];
            unset($permissionData['dependencies']);
            
            $permission = Permission::create($permissionData);
            
            if (!empty($dependencies)) {
                $dependencyIds = Permission::whereIn('name', $dependencies)->pluck('id');
                $permission->dependencies()->attach($dependencyIds);
            }
        }

        // 2. 測試匯出功能
        $response = $this->postJson('/admin/permissions/import-export/export', [
            'modules' => ['export_test'],
            'format' => 'json',
        ]);

        $response->assertStatus(200);
        $exportData = $response->json();

        $this->assertArrayHasKey('metadata', $exportData);
        $this->assertArrayHasKey('permissions', $exportData);
        $this->assertCount(2, $exportData['permissions']);

        // 3. 清除測試權限
        Permission::where('module', 'export_test')->delete();

        // 4. 測試匯入功能
        $response = $this->postJson('/admin/permissions/import-export/import', [
            'data' => $exportData,
            'conflict_resolution' => 'skip',
        ]);

        $response->assertStatus(200);
        $importResult = $response->json();

        $this->assertTrue($importResult['success']);
        $this->assertEquals(2, $importResult['results']['created']);

        // 5. 驗證匯入的權限和依賴關係
        $importedPermission1 = Permission::where('name', 'export.test1')->first();
        $importedPermission2 = Permission::where('name', 'export.test2')->first();

        $this->assertNotNull($importedPermission1);
        $this->assertNotNull($importedPermission2);
        $this->assertTrue($importedPermission2->dependencies->contains($importedPermission1));
    }

    /** @test */
    public function test_permission_usage_analysis_workflow()
    {
        $this->actingAs($this->superAdmin);

        // 建立測試權限
        $usedPermission = Permission::create([
            'name' => 'analysis.used',
            'display_name' => '已使用權限',
            'module' => 'analysis',
            'type' => 'view',
        ]);

        $unusedPermission = Permission::create([
            'name' => 'analysis.unused',
            'display_name' => '未使用權限',
            'module' => 'analysis',
            'type' => 'view',
            'created_at' => now()->subDays(100),
        ]);

        // 分配權限給角色
        $this->adminRole->permissions()->attach($usedPermission->id);

        // 測試使用情況統計
        $stats = $this->usageAnalysisService->getUsageStats();
        $this->assertArrayHasKey('total_permissions', $stats);
        $this->assertArrayHasKey('used_permissions', $stats);
        $this->assertArrayHasKey('unused_permissions', $stats);

        // 測試未使用權限識別
        $unusedPermissions = $this->usageAnalysisService->getUnusedPermissions();
        $unusedIds = $unusedPermissions->pluck('id')->toArray();
        $this->assertContains($unusedPermission->id, $unusedIds);
        $this->assertNotContains($usedPermission->id, $unusedIds);

        // 測試使用頻率分析
        $frequency = $this->usageAnalysisService->getUsageFrequency($usedPermission->id);
        $this->assertArrayHasKey('frequency_score', $frequency);
        $this->assertArrayHasKey('frequency_level', $frequency);

        // 測試模組使用統計
        $moduleStats = $this->usageAnalysisService->getModuleUsageStats();
        $analysisModule = collect($moduleStats)->firstWhere('module', 'analysis');
        $this->assertNotNull($analysisModule);
        $this->assertEquals(2, $analysisModule['total_permissions']);
        $this->assertEquals(1, $analysisModule['used_permissions']);
        $this->assertEquals(1, $analysisModule['unused_permissions']);
    }

    /** @test */
    public function test_permission_security_controls()
    {
        $this->actingAs($this->admin);

        // 測試系統權限保護
        $systemPermission = Permission::where('name', 'system.manage')->first();
        
        $this->expectException(\Exception::class);
        $this->securityService->checkMultiLevelPermission('delete', $systemPermission, $this->admin);

        // 測試權限名稱驗證
        $invalidPermissionData = [
            'name' => 'INVALID-NAME!',
            'display_name' => '無效權限',
            'module' => 'test',
            'type' => 'view',
        ];

        $response = $this->postJson('/admin/permissions', $invalidPermissionData);
        $response->assertStatus(422);

        // 測試危險操作速率限制
        $testPermission = Permission::create([
            'name' => 'security.test',
            'display_name' => '安全測試',
            'module' => 'security',
            'type' => 'view',
        ]);

        // 模擬達到速率限制
        Cache::put("high_risk_operation_{$this->admin->id}_delete", 10, now()->addHour());

        $canDelete = $this->securityService->checkMultiLevelPermission('delete', $testPermission, $this->admin);
        $this->assertFalse($canDelete);
    }

    /** @test */
    public function test_permission_audit_functionality()
    {
        $this->actingAs($this->superAdmin);

        // 建立權限（應該記錄審計日誌）
        $permissionData = [
            'name' => 'audit.test',
            'display_name' => '審計測試',
            'module' => 'audit',
            'type' => 'view',
        ];

        $response = $this->postJson('/admin/permissions', $permissionData);
        $response->assertStatus(201);

        $permission = Permission::where('name', 'audit.test')->first();

        // 檢查審計日誌是否記錄
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Permission::class,
            'auditable_id' => $permission->id,
            'event' => 'created',
            'user_id' => $this->superAdmin->id,
        ]);

        // 編輯權限
        $updateData = ['display_name' => '審計測試（更新）'];
        $response = $this->putJson("/admin/permissions/{$permission->id}", $updateData);
        $response->assertStatus(200);

        // 檢查更新審計日誌
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Permission::class,
            'auditable_id' => $permission->id,
            'event' => 'updated',
            'user_id' => $this->superAdmin->id,
        ]);

        // 刪除權限
        $response = $this->deleteJson("/admin/permissions/{$permission->id}");
        $response->assertStatus(200);

        // 檢查刪除審計日誌
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Permission::class,
            'auditable_id' => $permission->id,
            'event' => 'deleted',
            'user_id' => $this->superAdmin->id,
        ]);
    }

    /** @test */
    public function test_permission_template_functionality()
    {
        $this->actingAs($this->superAdmin);

        // 測試權限模板應用
        $templateData = [
            'name' => 'crud_template',
            'display_name' => 'CRUD 模板',
            'module' => 'template_test',
            'permissions' => [
                ['type' => 'view', 'display_name' => '檢視'],
                ['type' => 'create', 'display_name' => '建立'],
                ['type' => 'edit', 'display_name' => '編輯'],
                ['type' => 'delete', 'display_name' => '刪除'],
            ],
        ];

        $response = $this->postJson('/admin/permissions/templates/apply', $templateData);
        $response->assertStatus(200);

        // 驗證模板權限已建立
        $createdPermissions = Permission::where('module', 'template_test')->get();
        $this->assertCount(4, $createdPermissions);

        $permissionNames = $createdPermissions->pluck('name')->toArray();
        $this->assertContains('template_test.view', $permissionNames);
        $this->assertContains('template_test.create', $permissionNames);
        $this->assertContains('template_test.edit', $permissionNames);
        $this->assertContains('template_test.delete', $permissionNames);
    }

    /** @test */
    public function test_permission_test_functionality()
    {
        $this->actingAs($this->superAdmin);

        // 測試使用者權限檢查
        $testPermission = Permission::where('name', 'permissions.view')->first();

        $response = $this->postJson('/admin/permissions/test/user', [
            'user_id' => $this->admin->id,
            'permission' => 'permissions.view',
        ]);

        $response->assertStatus(200);
        $testResult = $response->json();

        $this->assertTrue($testResult['has_permission']);
        $this->assertArrayHasKey('permission_path', $testResult);

        // 測試角色權限檢查
        $response = $this->postJson('/admin/permissions/test/role', [
            'role_id' => $this->adminRole->id,
            'permission' => 'permissions.view',
        ]);

        $response->assertStatus(200);
        $testResult = $response->json();

        $this->assertTrue($testResult['has_permission']);
        $this->assertArrayHasKey('permissions', $testResult);
    }

    /** @test */
    public function test_performance_with_large_dataset()
    {
        $this->actingAs($this->superAdmin);

        // 建立大量測試權限
        $permissions = [];
        for ($i = 1; $i <= 100; $i++) {
            $permissions[] = [
                'name' => "performance.test{$i}",
                'display_name' => "效能測試{$i}",
                'module' => 'performance',
                'type' => 'view',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('permissions')->insert($permissions);

        // 測試列表載入效能
        $startTime = microtime(true);
        $response = $this->get('/admin/permissions');
        $endTime = microtime(true);

        $response->assertStatus(200);
        $loadTime = $endTime - $startTime;
        $this->assertLessThan(2.0, $loadTime, '權限列表載入時間應少於 2 秒');

        // 測試搜尋效能
        $startTime = microtime(true);
        $response = $this->get('/admin/permissions?search=performance');
        $endTime = microtime(true);

        $response->assertStatus(200);
        $searchTime = $endTime - $startTime;
        $this->assertLessThan(1.0, $searchTime, '權限搜尋時間應少於 1 秒');

        // 測試使用情況分析效能
        $startTime = microtime(true);
        $stats = $this->usageAnalysisService->getUsageStats();
        $endTime = microtime(true);

        $analysisTime = $endTime - $startTime;
        $this->assertLessThan(3.0, $analysisTime, '使用情況分析時間應少於 3 秒');
    }

    /** @test */
    public function test_concurrent_operations()
    {
        $this->actingAs($this->superAdmin);

        // 模擬並發建立權限
        $promises = [];
        for ($i = 1; $i <= 5; $i++) {
            $permissionData = [
                'name' => "concurrent.test{$i}",
                'display_name' => "並發測試{$i}",
                'module' => 'concurrent',
                'type' => 'view',
            ];

            $promises[] = $this->postJson('/admin/permissions', $permissionData);
        }

        // 驗證所有權限都成功建立
        foreach ($promises as $i => $response) {
            $response->assertStatus(201);
        }

        $createdPermissions = Permission::where('module', 'concurrent')->count();
        $this->assertEquals(5, $createdPermissions);
    }

    protected function tearDown(): void
    {
        // 清除快取
        Cache::flush();
        
        parent::tearDown();
    }
}