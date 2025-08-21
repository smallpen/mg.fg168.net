<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Services\RoleCacheService;
use App\Services\RoleOptimizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * 角色效能測試
 * 
 * 測試角色管理的效能優化功能
 */
class RolePerformanceTest extends TestCase
{
    use RefreshDatabase;

    private RoleCacheService $cacheService;
    private RoleOptimizationService $optimizationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cacheService = app(RoleCacheService::class);
        $this->optimizationService = app(RoleOptimizationService::class);
    }

    /**
     * 測試角色權限快取功能
     */
    public function test_role_permissions_caching(): void
    {
        // 建立測試資料
        $role = Role::factory()->create(['name' => 'test_role']);
        $permissions = Permission::factory()->count(5)->create();
        $role->permissions()->attach($permissions->pluck('id'));

        // 清除快取
        Cache::flush();

        // 第一次查詢（應該從資料庫載入）
        $startTime = microtime(true);
        $permissions1 = $this->cacheService->getRoleAllPermissions($role);
        $firstQueryTime = microtime(true) - $startTime;

        // 第二次查詢（應該從快取載入）
        $startTime = microtime(true);
        $permissions2 = $this->cacheService->getRoleAllPermissions($role);
        $secondQueryTime = microtime(true) - $startTime;

        // 驗證結果相同
        $this->assertEquals($permissions1->count(), $permissions2->count());
        $this->assertEquals($permissions1->pluck('id')->sort(), $permissions2->pluck('id')->sort());

        // 驗證快取效能提升（第二次查詢應該更快）
        $this->assertLessThan($firstQueryTime, $secondQueryTime);
    }

    /**
     * 測試權限繼承快取
     */
    public function test_permission_inheritance_caching(): void
    {
        // 建立父子角色
        $parentRole = Role::factory()->create(['name' => 'parent_role_test']);
        $childRole = Role::factory()->create(['name' => 'child_role_test', 'parent_id' => $parentRole->id]);

        // 為父角色分配權限
        $parentPermissions = collect();
        for ($i = 1; $i <= 3; $i++) {
            $parentPermissions->push(Permission::factory()->create([
                'name' => "parent_permission_{$i}_" . uniqid(),
                'module' => 'parent_test'
            ]));
        }
        $parentRole->permissions()->attach($parentPermissions->pluck('id'));

        // 為子角色分配權限
        $childPermissions = collect();
        for ($i = 1; $i <= 2; $i++) {
            $childPermissions->push(Permission::factory()->create([
                'name' => "child_permission_{$i}_" . uniqid(),
                'module' => 'child_test'
            ]));
        }
        $childRole->permissions()->attach($childPermissions->pluck('id'));

        // 清除快取
        Cache::flush();

        // 測試子角色的所有權限（包含繼承）
        $allPermissions = $this->cacheService->getRoleAllPermissions($childRole);
        $inheritedPermissions = $this->cacheService->getRoleInheritedPermissions($childRole);
        $directPermissions = $this->cacheService->getRoleDirectPermissions($childRole);

        // 驗證權限數量
        $this->assertEquals(5, $allPermissions->count()); // 3 + 2
        $this->assertEquals(3, $inheritedPermissions->count()); // 來自父角色
        $this->assertEquals(2, $directPermissions->count()); // 直接分配

        // 驗證快取是否生效（再次查詢應該從快取載入）
        $cachedAllPermissions = $this->cacheService->getRoleAllPermissions($childRole);
        $this->assertEquals($allPermissions->pluck('id')->sort(), $cachedAllPermissions->pluck('id')->sort());
    }

    /**
     * 測試批量處理效能
     */
    public function test_batch_processing_performance(): void
    {
        // 建立測試資料
        $roles = Role::factory()->count(10)->create();
        $permissions = Permission::factory()->count(5)->create();

        // 測試批量權限同步
        $startTime = microtime(true);
        $result = $this->optimizationService->batchProcessRolePermissions(
            $roles->pluck('id')->toArray(),
            $permissions->pluck('id')->toArray(),
            'sync'
        );
        $batchTime = microtime(true) - $startTime;

        // 驗證批量處理結果
        $this->assertEquals(10, $result['success_count']);
        $this->assertEquals(0, $result['error_count']);

        // 驗證權限已正確分配
        foreach ($roles as $role) {
            $this->assertEquals(5, $role->fresh()->permissions()->count());
        }

        // 批量處理應該在合理時間內完成
        $this->assertLessThan(1.0, $batchTime); // 應該在 1 秒內完成
    }

    /**
     * 測試優化查詢效能
     */
    public function test_optimized_query_performance(): void
    {
        // 建立測試資料
        $role = Role::factory()->create(['name' => 'perf_test_role']);
        $permissions = collect();
        for ($i = 1; $i <= 20; $i++) {
            $permissions->push(Permission::factory()->create([
                'name' => "perf_permission_{$i}_" . uniqid(),
                'module' => 'perf_test'
            ]));
        }
        $role->permissions()->attach($permissions->pluck('id'));

        // 測試優化查詢
        $startTime = microtime(true);
        $optimizedPermissions = $this->optimizationService->getOptimizedRolePermissions($role->id, false);
        $optimizedTime = microtime(true) - $startTime;

        // 測試標準查詢
        $startTime = microtime(true);
        $standardPermissions = $role->permissions;
        $standardTime = microtime(true) - $startTime;

        // 驗證結果相同
        $this->assertEquals($standardPermissions->count(), $optimizedPermissions->count());

        // 優化查詢應該不會比標準查詢慢太多
        $this->assertLessThan($standardTime * 2, $optimizedTime);
    }

    /**
     * 測試快取清除功能
     */
    public function test_cache_clearing(): void
    {
        // 建立測試資料
        $role = Role::factory()->create();
        $permissions = Permission::factory()->count(3)->create();
        $role->permissions()->attach($permissions->pluck('id'));

        // 預熱快取
        $this->cacheService->getRoleAllPermissions($role);

        // 驗證快取存在（通過檢查快取統計）
        $stats = $this->cacheService->getCacheStats();
        $this->assertNotEmpty($stats);

        // 清除特定角色快取
        $this->cacheService->clearRoleCache($role->id);

        // 再次查詢應該重新從資料庫載入
        $permissions = $this->cacheService->getRoleAllPermissions($role);
        $this->assertEquals(3, $permissions->count());
    }

    /**
     * 測試記憶體使用統計
     */
    public function test_memory_usage_stats(): void
    {
        $stats = $this->optimizationService->getMemoryStats();

        $this->assertArrayHasKey('memory_usage', $stats);
        $this->assertArrayHasKey('memory_peak', $stats);
        $this->assertArrayHasKey('memory_limit', $stats);

        $this->assertIsInt($stats['memory_usage']);
        $this->assertIsInt($stats['memory_peak']);
        $this->assertIsString($stats['memory_limit']);

        // 記憶體使用應該大於 0
        $this->assertGreaterThan(0, $stats['memory_usage']);
        $this->assertGreaterThan(0, $stats['memory_peak']);
    }

    /**
     * 測試延遲載入功能
     */
    public function test_lazy_loading(): void
    {
        // 建立大量測試資料
        Role::factory()->count(50)->create();

        // 測試延遲載入
        $lazyRoles = $this->optimizationService->lazyLoadRoles();

        $this->assertInstanceOf(\Illuminate\Support\LazyCollection::class, $lazyRoles);

        // 計算載入的角色數量
        $count = 0;
        foreach ($lazyRoles as $role) {
            $count++;
            if ($count >= 10) break; // 只處理前 10 個
        }

        $this->assertEquals(10, $count);
    }

    /**
     * 測試角色搜尋優化
     */
    public function test_optimized_role_search(): void
    {
        // 建立測試資料
        Role::factory()->create(['name' => 'search_admin_role', 'display_name' => '搜尋管理員角色']);
        Role::factory()->create(['name' => 'search_user_role', 'display_name' => '搜尋使用者角色']);
        Role::factory()->create(['name' => 'search_guest_role', 'display_name' => '搜尋訪客角色']);

        // 測試搜尋
        $results = $this->optimizationService->optimizedRoleSearch('search_admin');

        $this->assertGreaterThan(0, $results->count());
        $this->assertTrue($results->contains('name', 'search_admin_role'));
    }

    /**
     * 測試權限依賴解析優化
     */
    public function test_optimized_permission_dependency_resolution(): void
    {
        // 建立權限依賴關係
        $permission1 = Permission::factory()->create(['name' => 'dep_view_users_' . uniqid(), 'module' => 'dep_test']);
        $permission2 = Permission::factory()->create(['name' => 'dep_edit_users_' . uniqid(), 'module' => 'dep_test']);
        $permission3 = Permission::factory()->create(['name' => 'dep_delete_users_' . uniqid(), 'module' => 'dep_test']);

        // 設定依賴關係：delete_users 依賴 edit_users，edit_users 依賴 view_users
        $permission2->dependencies()->attach($permission1->id);
        $permission3->dependencies()->attach($permission2->id);

        // 測試依賴解析
        $resolvedIds = $this->optimizationService->resolvePermissionDependenciesOptimized([$permission3->id]);

        // 應該包含所有依賴的權限
        $this->assertContains($permission1->id, $resolvedIds);
        $this->assertContains($permission2->id, $resolvedIds);
        $this->assertContains($permission3->id, $resolvedIds);
    }
}