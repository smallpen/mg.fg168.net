<?php

namespace Tests\Integration\RoleManagement;

use Tests\TestCase;
use App\Services\RoleManagementIntegrationService;
use App\Services\RolePerformanceOptimizationService;
use App\Services\RoleSecurityAuditService;
use App\Services\RoleHierarchyValidationService;
use App\Services\RoleManagementUXTestService;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

/**
 * 角色管理最終整合測試
 * 
 * 執行完整的角色管理系統整合測試，驗證所有功能的正確性
 */
class FinalIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private RoleManagementIntegrationService $integrationService;
    private RolePerformanceOptimizationService $performanceService;
    private RoleSecurityAuditService $securityService;
    private RoleHierarchyValidationService $validationService;
    private RoleManagementUXTestService $uxTestService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->integrationService = app(RoleManagementIntegrationService::class);
        $this->performanceService = app(RolePerformanceOptimizationService::class);
        $this->securityService = app(RoleSecurityAuditService::class);
        $this->validationService = app(RoleHierarchyValidationService::class);
        $this->uxTestService = app(RoleManagementUXTestService::class);
    }

    /**
     * 測試完整的角色管理系統整合
     * 
     * @test
     */
    public function it_performs_complete_role_management_integration()
    {
        Log::info('開始執行角色管理最終整合測試');

        // 1. 初始化系統
        $initResult = $this->integrationService->initializeSystem();
        $this->assertTrue($initResult['success'], '系統初始化應該成功');
        $this->assertArrayHasKey('stats', $initResult);

        // 2. 執行系統完整性檢查
        $integrityResult = $this->integrationService->performSystemIntegrityCheck();
        $this->assertTrue($integrityResult['success'], '系統完整性檢查應該通過');
        $this->assertEmpty($integrityResult['errors'], '不應該有系統完整性錯誤');

        // 3. 執行效能測試和優化
        $performanceResult = $this->performanceService->runPerformanceTestSuite();
        $this->assertTrue($performanceResult['success'], '效能測試應該成功執行');
        $this->assertArrayHasKey('performance_report', $performanceResult['results']);

        // 4. 執行安全性稽核
        $securityResult = $this->securityService->performSecurityAudit();
        $this->assertTrue($securityResult['success'], '安全性稽核應該成功執行');
        
        // 檢查是否有高嚴重性安全問題
        $highSeverityIssues = collect($securityResult['security_issues'])
            ->where('severity', 'high')
            ->count();
        $this->assertEquals(0, $highSeverityIssues, '不應該有高嚴重性安全問題');

        // 5. 執行角色層級和權限繼承驗證
        $validationResult = $this->validationService->performCompleteValidation();
        $this->assertTrue($validationResult['success'], '角色層級驗證應該成功');
        $this->assertEmpty($validationResult['errors'], '不應該有驗證錯誤');

        // 6. 執行系統優化
        $optimizationResult = $this->performanceService->performOptimizations();
        $this->assertTrue($optimizationResult['success'], '系統優化應該成功');

        // 7. 執行健康檢查
        $healthResult = $this->integrationService->performHealthCheck();
        $this->assertEquals('healthy', $healthResult['overall_status'], '系統整體健康狀態應該良好');

        Log::info('角色管理最終整合測試完成', [
            'init_success' => $initResult['success'],
            'integrity_success' => $integrityResult['success'],
            'performance_success' => $performanceResult['success'],
            'security_success' => $securityResult['success'],
            'validation_success' => $validationResult['success'],
            'optimization_success' => $optimizationResult['success'],
            'health_status' => $healthResult['overall_status']
        ]);
    }

    /**
     * 測試角色 CRUD 操作的完整流程
     * 
     * @test
     */
    public function it_tests_complete_role_crud_operations()
    {
        // 建立測試權限
        $permissions = [
            Permission::create(['name' => 'test.view', 'display_name' => '測試檢視', 'module' => 'test']),
            Permission::create(['name' => 'test.create', 'display_name' => '測試建立', 'module' => 'test']),
            Permission::create(['name' => 'test.edit', 'display_name' => '測試編輯', 'module' => 'test'])
        ];

        // 1. 建立角色
        $role = Role::create([
            'name' => 'integration_test_role',
            'display_name' => '整合測試角色',
            'description' => '用於整合測試的角色',
            'is_active' => true,
            'is_system_role' => false
        ]);

        $this->assertDatabaseHas('roles', [
            'name' => 'integration_test_role',
            'display_name' => '整合測試角色'
        ]);

        // 2. 指派權限
        $role->syncPermissions($permissions);
        $this->assertEquals(3, $role->permissions()->count());

        // 3. 建立子角色測試繼承
        $childRole = Role::create([
            'name' => 'integration_test_child_role',
            'display_name' => '整合測試子角色',
            'description' => '用於測試繼承的子角色',
            'parent_id' => $role->id,
            'is_active' => true,
            'is_system_role' => false
        ]);

        // 驗證權限繼承
        $inheritedPermissions = $childRole->getAllPermissions();
        $this->assertEquals(3, $inheritedPermissions->count());

        // 4. 測試使用者角色指派
        $user = User::factory()->create();
        $user->roles()->attach($childRole->id);

        // 驗證使用者權限
        $userPermissions = $user->roles->flatMap->getAllPermissions()->unique('id');
        $this->assertEquals(3, $userPermissions->count());

        // 5. 測試角色更新
        $role->update(['display_name' => '整合測試角色 (已更新)']);
        $this->assertEquals('整合測試角色 (已更新)', $role->fresh()->display_name);

        // 6. 測試角色刪除保護
        $this->assertFalse($role->can_be_deleted, '有子角色的角色不應該可以刪除');

        // 先刪除子角色
        $childRole->delete();
        $this->assertTrue($role->fresh()->can_be_deleted, '沒有子角色的角色應該可以刪除');

        // 7. 清理測試資料
        $user->roles()->detach();
        $role->permissions()->detach();
        $role->delete();
        
        foreach ($permissions as $permission) {
            $permission->delete();
        }
    }

    /**
     * 測試權限繼承的複雜場景
     * 
     * @test
     */
    public function it_tests_complex_permission_inheritance_scenarios()
    {
        // 建立權限
        $permissions = [
            Permission::create(['name' => 'level1.view', 'display_name' => '層級1檢視', 'module' => 'level1']),
            Permission::create(['name' => 'level2.view', 'display_name' => '層級2檢視', 'module' => 'level2']),
            Permission::create(['name' => 'level3.view', 'display_name' => '層級3檢視', 'module' => 'level3'])
        ];

        // 建立多層級角色結構
        $rootRole = Role::create([
            'name' => 'root_role',
            'display_name' => '根角色',
            'is_active' => true,
            'is_system_role' => false
        ]);
        $rootRole->givePermissionTo($permissions[0]);

        $middleRole = Role::create([
            'name' => 'middle_role',
            'display_name' => '中間角色',
            'parent_id' => $rootRole->id,
            'is_active' => true,
            'is_system_role' => false
        ]);
        $middleRole->givePermissionTo($permissions[1]);

        $leafRole = Role::create([
            'name' => 'leaf_role',
            'display_name' => '葉子角色',
            'parent_id' => $middleRole->id,
            'is_active' => true,
            'is_system_role' => false
        ]);
        $leafRole->givePermissionTo($permissions[2]);

        // 驗證權限繼承
        $this->assertEquals(1, $rootRole->getAllPermissions()->count());
        $this->assertEquals(2, $middleRole->getAllPermissions()->count());
        $this->assertEquals(3, $leafRole->getAllPermissions()->count());

        // 驗證特定權限
        $this->assertTrue($leafRole->hasPermissionIncludingInherited('level1.view'));
        $this->assertTrue($leafRole->hasPermissionIncludingInherited('level2.view'));
        $this->assertTrue($leafRole->hasPermissionIncludingInherited('level3.view'));

        $this->assertTrue($middleRole->hasPermissionIncludingInherited('level1.view'));
        $this->assertTrue($middleRole->hasPermissionIncludingInherited('level2.view'));
        $this->assertFalse($middleRole->hasPermissionIncludingInherited('level3.view'));

        // 測試權限變更的影響
        $newPermission = Permission::create(['name' => 'new.permission', 'display_name' => '新權限', 'module' => 'new']);
        $rootRole->givePermissionTo($newPermission);

        // 重新載入角色以取得最新的權限關聯
        $middleRole = $middleRole->fresh();
        $leafRole = $leafRole->fresh();

        // 子角色應該自動繼承新權限
        $this->assertTrue($middleRole->hasPermissionIncludingInherited('new.permission'));
        $this->assertTrue($leafRole->hasPermissionIncludingInherited('new.permission'));

        // 清理
        $leafRole->delete();
        $middleRole->delete();
        $rootRole->delete();
        foreach ($permissions as $permission) {
            $permission->delete();
        }
        $newPermission->delete();
    }

    /**
     * 測試批量操作的安全性和正確性
     * 
     * @test
     */
    public function it_tests_bulk_operations_security_and_correctness()
    {
        // 建立測試角色
        $roles = collect();
        for ($i = 1; $i <= 5; $i++) {
            $roles->push(Role::create([
                'name' => "bulk_test_role_{$i}",
                'display_name' => "批量測試角色 {$i}",
                'is_active' => true,
                'is_system_role' => false
            ]));
        }

        // 建立系統角色（不應該被批量操作影響）
        $systemRole = Role::create([
            'name' => 'system_role',
            'display_name' => '系統角色',
            'is_active' => true,
            'is_system_role' => true
        ]);

        // 建立有使用者關聯的角色（不應該被刪除）
        $roleWithUser = Role::create([
            'name' => 'role_with_user',
            'display_name' => '有使用者的角色',
            'is_active' => true,
            'is_system_role' => false
        ]);
        
        $user = User::factory()->create();
        $user->roles()->attach($roleWithUser->id);

        // 測試批量權限指派
        $permission = Permission::create(['name' => 'bulk.test', 'display_name' => '批量測試', 'module' => 'bulk']);
        
        foreach ($roles as $role) {
            $role->givePermissionTo($permission);
        }

        // 驗證所有角色都有權限
        foreach ($roles as $role) {
            $this->assertTrue($role->hasPermission('bulk.test'));
        }

        // 測試批量狀態更新
        Role::whereIn('id', $roles->pluck('id'))->update(['is_active' => false]);
        
        foreach ($roles as $role) {
            $freshRole = $role->fresh();
            $this->assertFalse($freshRole->is_active);
        }

        // 系統角色不應該受影響
        $this->assertTrue($systemRole->fresh()->is_active);

        // 清理
        $user->roles()->detach();
        $user->delete();
        $roleWithUser->delete();
        $systemRole->delete();
        foreach ($roles as $role) {
            $role->delete();
        }
        $permission->delete();
    }

    /**
     * 測試快取一致性和效能
     * 
     * @test
     */
    public function it_tests_cache_consistency_and_performance()
    {
        $cacheService = app(\App\Services\RoleCacheService::class);

        // 建立測試資料
        $role = Role::create([
            'name' => 'cache_test_role',
            'display_name' => '快取測試角色',
            'is_active' => true,
            'is_system_role' => false
        ]);

        $permissions = [
            Permission::create(['name' => 'cache.test1', 'display_name' => '快取測試1', 'module' => 'cache']),
            Permission::create(['name' => 'cache.test2', 'display_name' => '快取測試2', 'module' => 'cache'])
        ];

        $role->syncPermissions($permissions);

        // 測試快取一致性
        $cacheService->clearRoleCache($role->id);
        
        $permissionsFromDb = $role->getAllPermissions(false);
        $permissionsFromCache = $role->getAllPermissions(true);

        $this->assertEquals($permissionsFromDb->count(), $permissionsFromCache->count());
        $this->assertEquals(
            $permissionsFromDb->pluck('id')->sort()->values(),
            $permissionsFromCache->pluck('id')->sort()->values()
        );

        // 測試快取效能
        $startTime = microtime(true);
        for ($i = 0; $i < 10; $i++) {
            $role->getAllPermissions(false); // 不使用快取
        }
        $timeWithoutCache = microtime(true) - $startTime;

        $startTime = microtime(true);
        for ($i = 0; $i < 10; $i++) {
            $role->getAllPermissions(true); // 使用快取
        }
        $timeWithCache = microtime(true) - $startTime;

        // 快取應該顯著提升效能（或至少不會更慢）
        $this->assertLessThanOrEqual($timeWithoutCache * 1.5, $timeWithCache, '快取效能測試：快取版本不應該比非快取版本慢太多');

        // 清理
        $role->delete();
        foreach ($permissions as $permission) {
            $permission->delete();
        }
    }

    /**
     * 測試錯誤處理和邊界情況
     * 
     * @test
     */
    public function it_tests_error_handling_and_edge_cases()
    {
        // 測試循環依賴檢測
        $role1 = Role::create([
            'name' => 'circular_test_1',
            'display_name' => '循環測試1',
            'is_active' => true,
            'is_system_role' => false
        ]);

        $role2 = Role::create([
            'name' => 'circular_test_2',
            'display_name' => '循環測試2',
            'parent_id' => $role1->id,
            'is_active' => true,
            'is_system_role' => false
        ]);

        // 嘗試建立循環依賴應該被阻止
        $this->assertTrue($role1->hasCircularDependency($role2->id));

        // 測試不存在的權限處理
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $role1->givePermission('non.existent.permission');

        // 清理
        $role2->delete();
        $role1->delete();
    }

    /**
     * 測試多語言支援
     * 
     * @test
     */
    public function it_tests_multilingual_support()
    {
        // 建立測試角色
        $role = Role::create([
            'name' => 'multilingual_test_role',
            'display_name' => 'Multilingual Test Role',
            'description' => 'A role for testing multilingual support',
            'is_active' => true,
            'is_system_role' => false
        ]);

        // 測試本地化顯示名稱
        app()->setLocale('zh_TW');
        $localizedName = $role->localized_display_name;
        $this->assertNotEmpty($localizedName);

        app()->setLocale('en');
        $englishName = $role->localized_display_name;
        $this->assertNotEmpty($englishName);

        // 清理
        $role->delete();
    }
}