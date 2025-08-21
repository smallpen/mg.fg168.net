<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Services\RoleStatisticsService;
use App\Services\RoleStatisticsCacheManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * 角色統計功能測試
 */
class RoleStatisticsTest extends TestCase
{
    use RefreshDatabase;

    private RoleStatisticsService $statisticsService;
    private RoleStatisticsCacheManager $cacheManager;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->statisticsService = app(RoleStatisticsService::class);
        $this->cacheManager = app(RoleStatisticsCacheManager::class);
    }

    /**
     * 測試取得角色統計資訊
     */
    public function test_can_get_role_statistics(): void
    {
        // 建立測試資料
        $role = Role::factory()->create([
            'name' => 'test_role',
            'display_name' => '測試角色',
            'description' => '測試用角色',
        ]);

        $permissions = Permission::factory()->count(3)->create([
            'module' => 'test_module',
        ]);

        $users = User::factory()->count(2)->create();

        // 指派權限和使用者
        $role->permissions()->attach($permissions->pluck('id'));
        $role->users()->attach($users->pluck('id'));

        // 取得統計資料
        $statistics = $this->statisticsService->getRoleStatistics($role);

        // 驗證基本統計
        $this->assertEquals(2, $statistics['basic']['user_count']);
        $this->assertEquals(3, $statistics['basic']['direct_permission_count']);
        $this->assertEquals(3, $statistics['basic']['total_permission_count']);
        $this->assertEquals(0, $statistics['basic']['inherited_permission_count']);

        // 驗證權限分佈
        $this->assertArrayHasKey('permissions', $statistics);
        $this->assertArrayHasKey('by_module', $statistics['permissions']);
        $this->assertCount(1, $statistics['permissions']['by_module']);
        $this->assertEquals('test_module', $statistics['permissions']['by_module'][0]['module']);
        $this->assertEquals(3, $statistics['permissions']['by_module'][0]['count']);
    }

    /**
     * 測試系統角色統計
     */
    public function test_can_get_system_role_statistics(): void
    {
        // 建立測試資料
        Role::factory()->count(5)->create();
        Role::factory()->count(2)->create(['is_system_role' => true]);
        
        $activeRoles = Role::factory()->count(3)->create(['is_active' => true]);
        Role::factory()->count(2)->create(['is_active' => false]);

        // 為部分角色指派使用者
        $users = User::factory()->count(3)->create();
        $activeRoles->first()->users()->attach($users->pluck('id'));

        // 取得系統統計
        $statistics = $this->statisticsService->getSystemRoleStatistics();

        // 驗證統計資料
        $this->assertEquals(10, $statistics['overview']['total_roles']); // 5 + 2 + 3 = 10
        $this->assertEquals(2, $statistics['overview']['system_roles']);
        $this->assertEquals(8, $statistics['overview']['custom_roles']);
        $this->assertEquals(1, $statistics['overview']['roles_with_users']);
    }

    /**
     * 測試權限分佈統計
     */
    public function test_can_get_permission_distribution(): void
    {
        // 建立不同模組的權限
        $userPermissions = Permission::factory()->count(3)->create(['module' => 'users']);
        $rolePermissions = Permission::factory()->count(2)->create(['module' => 'roles']);
        
        $role = Role::factory()->create();
        $role->permissions()->attach($userPermissions->pluck('id'));

        // 取得權限分佈
        $distribution = $this->statisticsService->getPermissionDistribution($role);

        // 驗證分佈資料
        $this->assertEquals(3, $distribution['summary']['total_permissions']);
        $this->assertEquals(5, $distribution['summary']['total_system_permissions']);
        $this->assertEquals(60, $distribution['summary']['coverage_percentage']); // 3/5 * 100

        $this->assertCount(1, $distribution['by_module']);
        $this->assertEquals('users', $distribution['by_module'][0]['module']);
        $this->assertEquals(3, $distribution['by_module'][0]['count']);
        $this->assertEquals(100, $distribution['by_module'][0]['percentage']); // 3/3 * 100
    }

    /**
     * 測試角色層級統計
     */
    public function test_can_handle_role_hierarchy_statistics(): void
    {
        // 建立父子角色
        $parentRole = Role::factory()->create(['name' => 'parent_role']);
        $childRole = Role::factory()->create([
            'name' => 'child_role',
            'parent_id' => $parentRole->id,
        ]);

        $permissions = Permission::factory()->count(2)->create();
        $parentRole->permissions()->attach($permissions->pluck('id'));

        // 取得子角色統計
        $statistics = $this->statisticsService->getRoleStatistics($childRole);

        // 驗證層級資訊
        $this->assertEquals(1, $statistics['hierarchy']['depth']);
        $this->assertTrue($statistics['hierarchy']['has_parent']);
        $this->assertEquals($parentRole->display_name, $statistics['hierarchy']['parent_name']);
        $this->assertEquals(0, $statistics['hierarchy']['children_count']);

        // 驗證權限繼承
        $this->assertEquals(0, $statistics['basic']['direct_permission_count']);
        $this->assertEquals(2, $statistics['basic']['inherited_permission_count']);
        $this->assertEquals(2, $statistics['basic']['total_permission_count']);
    }

    /**
     * 測試快取功能
     */
    public function test_statistics_caching_works(): void
    {
        $role = Role::factory()->create();
        
        // 第一次呼叫應該建立快取
        $statistics1 = $this->statisticsService->getRoleStatistics($role, true);
        
        // 檢查快取是否存在
        $cacheKey = 'role_stats_role_' . $role->id;
        $this->assertTrue(Cache::has($cacheKey));
        
        // 第二次呼叫應該使用快取
        $statistics2 = $this->statisticsService->getRoleStatistics($role, true);
        
        // 結果應該相同
        $this->assertEquals($statistics1, $statistics2);
        
        // 清除快取
        $this->cacheManager->clearRoleCache($role);
        $this->assertFalse(Cache::has($cacheKey));
    }

    /**
     * 測試快取自動清除
     */
    public function test_cache_clears_on_role_update(): void
    {
        $role = Role::factory()->create();
        
        // 建立快取
        $this->statisticsService->getRoleStatistics($role, true);
        $cacheKey = 'role_stats_role_' . $role->id;
        $this->assertTrue(Cache::has($cacheKey));
        
        // 更新角色應該清除快取
        $role->update(['display_name' => '更新後的角色名稱']);
        
        // 快取應該被清除（透過事件監聽器）
        $this->assertFalse(Cache::has($cacheKey));
    }

    /**
     * 測試使用趨勢統計
     */
    public function test_can_get_usage_trends(): void
    {
        // 建立不同時間的角色
        Role::factory()->create(['created_at' => now()->subDays(5)]);
        Role::factory()->create(['created_at' => now()->subDays(3)]);
        Role::factory()->create(['created_at' => now()->subDay()]);

        // 取得使用趨勢
        $trends = $this->statisticsService->getRoleUsageTrends(7);

        // 驗證趨勢資料
        $this->assertEquals(7, $trends['period']['days']);
        $this->assertEquals(3, $trends['role_creations']['total']);
        $this->assertCount(7, $trends['role_creations']['daily']);
        
        // 驗證圖表資料格式
        $this->assertArrayHasKey('chart_data', $trends['role_creations']);
        $this->assertArrayHasKey('labels', $trends['role_creations']['chart_data']);
        $this->assertArrayHasKey('data', $trends['role_creations']['chart_data']);
    }

    /**
     * 測試統計資料的準確性
     */
    public function test_statistics_accuracy(): void
    {
        // 建立複雜的測試場景
        $parentRole = Role::factory()->create();
        $childRole = Role::factory()->create(['parent_id' => $parentRole->id]);
        
        $permissions = Permission::factory()->count(5)->create();
        $users = User::factory()->count(3)->create();
        
        // 父角色有 3 個權限
        $parentRole->permissions()->attach($permissions->take(3)->pluck('id'));
        
        // 子角色有 2 個直接權限
        $childRole->permissions()->attach($permissions->skip(3)->take(2)->pluck('id'));
        
        // 子角色有 2 個使用者
        $childRole->users()->attach($users->take(2)->pluck('id'));

        // 取得子角色統計
        $statistics = $this->statisticsService->getRoleStatistics($childRole);

        // 驗證統計準確性
        $this->assertEquals(2, $statistics['basic']['user_count']);
        $this->assertEquals(2, $statistics['basic']['direct_permission_count']);
        $this->assertEquals(3, $statistics['basic']['inherited_permission_count']);
        $this->assertEquals(5, $statistics['basic']['total_permission_count']); // 2 直接 + 3 繼承
        
        // 驗證使用者分佈
        $this->assertEquals(2, $statistics['users']['total']);
        $this->assertEquals(2, $statistics['users']['distribution']['active']); // 預設使用者是啟用的
    }

    /**
     * 測試空資料情況
     */
    public function test_handles_empty_data_gracefully(): void
    {
        $role = Role::factory()->create();
        
        // 沒有權限和使用者的角色
        $statistics = $this->statisticsService->getRoleStatistics($role);
        
        // 應該正確處理空資料
        $this->assertEquals(0, $statistics['basic']['user_count']);
        $this->assertEquals(0, $statistics['basic']['direct_permission_count']);
        $this->assertEquals(0, $statistics['basic']['total_permission_count']);
        $this->assertEmpty($statistics['permissions']['by_module']);
        $this->assertEmpty($statistics['users']['recent_assignments']);
    }
}