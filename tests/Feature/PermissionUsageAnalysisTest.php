<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionUsageAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * 權限使用情況分析測試
 */
class PermissionUsageAnalysisTest extends TestCase
{
    use RefreshDatabase;

    protected PermissionUsageAnalysisService $usageAnalysisService;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->usageAnalysisService = app(PermissionUsageAnalysisService::class);
        
        // 創建測試使用者
        $this->adminUser = User::factory()->create([
            'username' => 'admin',
            'is_active' => true,
        ]);
        
        // 創建管理員角色和權限
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $viewPermission = Permission::factory()->create([
            'name' => 'permissions.view',
            'module' => 'admin',
        ]);
        $managePermission = Permission::factory()->create([
            'name' => 'permissions.manage',
            'module' => 'admin',
        ]);
        
        $adminRole->permissions()->attach([$viewPermission->id, $managePermission->id]);
        $this->adminUser->roles()->attach($adminRole->id);
    }

    /** @test */
    public function it_can_get_overall_usage_stats()
    {
        // 創建測試權限（使用唯一名稱）
        $usedPermission = Permission::factory()->create([
            'name' => 'test.used_permission',
            'module' => 'test'
        ]);
        $unusedPermission = Permission::factory()->create([
            'name' => 'test.unused_permission',
            'module' => 'test'
        ]);
        
        // 創建角色並分配權限
        $role = Role::factory()->create();
        $role->permissions()->attach($usedPermission->id);
        
        $stats = $this->usageAnalysisService->getUsageStats();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_permissions', $stats);
        $this->assertArrayHasKey('used_permissions', $stats);
        $this->assertArrayHasKey('unused_permissions', $stats);
        $this->assertArrayHasKey('usage_percentage', $stats);
        
        $this->assertGreaterThan(0, $stats['total_permissions']);
        $this->assertGreaterThan(0, $stats['used_permissions']);
    }

    /** @test */
    public function it_can_identify_unused_permissions()
    {
        // 創建未使用的權限
        $unusedPermission = Permission::factory()->create([
            'name' => 'test.unused',
            'module' => 'test',
            'created_at' => now()->subDays(100),
        ]);
        
        $unusedPermissions = $this->usageAnalysisService->getUnusedPermissions();
        
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $unusedPermissions);
        
        $foundUnused = $unusedPermissions->firstWhere('id', $unusedPermission->id);
        $this->assertNotNull($foundUnused);
        $this->assertEquals($unusedPermission->name, $foundUnused['name']);
    }

    /** @test */
    public function it_can_calculate_usage_frequency()
    {
        // 創建權限和角色
        $permission = Permission::factory()->create(['module' => 'test']);
        $role1 = Role::factory()->create();
        $role2 = Role::factory()->create();
        
        // 分配權限給角色
        $role1->permissions()->attach($permission->id);
        $role2->permissions()->attach($permission->id);
        
        // 創建使用者並分配角色
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user1->roles()->attach($role1->id);
        $user2->roles()->attach($role2->id);
        
        $frequency = $this->usageAnalysisService->getUsageFrequency($permission->id);
        
        $this->assertIsArray($frequency);
        $this->assertArrayHasKey('permission_id', $frequency);
        $this->assertArrayHasKey('role_count', $frequency);
        $this->assertArrayHasKey('user_count', $frequency);
        $this->assertArrayHasKey('frequency_score', $frequency);
        $this->assertArrayHasKey('frequency_level', $frequency);
        
        $this->assertEquals($permission->id, $frequency['permission_id']);
        $this->assertEquals(2, $frequency['role_count']);
        $this->assertEquals(2, $frequency['user_count']);
        $this->assertGreaterThan(0, $frequency['frequency_score']);
    }

    /** @test */
    public function it_can_get_module_usage_stats()
    {
        // 創建不同模組的權限
        $adminPermission = Permission::factory()->create([
            'name' => 'test.admin_permission',
            'module' => 'admin'
        ]);
        $userPermission = Permission::factory()->create([
            'name' => 'test.user_permission',
            'module' => 'users'
        ]);
        
        // 創建角色並分配權限
        $role = Role::factory()->create();
        $role->permissions()->attach($adminPermission->id);
        
        $moduleStats = $this->usageAnalysisService->getModuleUsageStats();
        
        $this->assertIsArray($moduleStats);
        $this->assertNotEmpty($moduleStats);
        
        $adminModule = collect($moduleStats)->firstWhere('module', 'admin');
        $this->assertNotNull($adminModule);
        $this->assertArrayHasKey('total_permissions', $adminModule);
        $this->assertArrayHasKey('used_permissions', $adminModule);
        $this->assertArrayHasKey('unused_permissions', $adminModule);
        $this->assertArrayHasKey('usage_percentage', $adminModule);
    }

    /** @test */
    public function it_can_mark_unused_permissions()
    {
        // 創建舊的未使用權限
        $oldUnusedPermission = Permission::factory()->create([
            'name' => 'test.old_unused',
            'module' => 'test',
            'created_at' => now()->subDays(100),
        ]);
        
        // 創建新的未使用權限
        $newUnusedPermission = Permission::factory()->create([
            'name' => 'test.new_unused',
            'module' => 'test',
            'created_at' => now()->subDays(10),
        ]);
        
        $options = [
            'days_threshold' => 90,
            'exclude_system' => true,
        ];
        
        $result = $this->usageAnalysisService->markUnusedPermissions($options);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_unused', $result);
        $this->assertArrayHasKey('marked_unused', $result);
        $this->assertArrayHasKey('marked_permissions', $result);
        
        // 檢查標記結果
        $markedIds = collect($result['marked_permissions'])->pluck('id')->toArray();
        $this->assertContains($oldUnusedPermission->id, $markedIds);
        $this->assertNotContains($newUnusedPermission->id, $markedIds);
    }

    /** @test */
    public function it_can_get_usage_heatmap_data()
    {
        // 創建權限和使用者
        $permission = Permission::factory()->create(['module' => 'test']);
        $role = Role::factory()->create();
        $role->permissions()->attach($permission->id);
        
        $users = User::factory()->count(3)->create();
        foreach ($users as $user) {
            $user->roles()->attach($role->id);
        }
        
        $heatmapData = $this->usageAnalysisService->getUsageHeatmapData();
        
        $this->assertIsArray($heatmapData);
        $this->assertNotEmpty($heatmapData);
        
        $permissionData = collect($heatmapData)->firstWhere('id', $permission->id);
        $this->assertNotNull($permissionData);
        $this->assertArrayHasKey('user_count', $permissionData);
        $this->assertArrayHasKey('role_count', $permissionData);
        $this->assertArrayHasKey('intensity', $permissionData);
        
        $this->assertEquals(3, $permissionData['user_count']);
        $this->assertEquals(1, $permissionData['role_count']);
        $this->assertGreaterThan(0, $permissionData['intensity']);
    }

    /** @test */
    public function it_can_clear_usage_cache()
    {
        // 創建一個權限來測試快取清除
        $permission = Permission::factory()->create(['name' => 'test.cache_permission']);
        
        // 設置一些快取
        Cache::put("permission_usage_stats_{$permission->id}", ['test' => 'data'], 3600);
        Cache::put('unused_permissions', ['test'], 3600);
        Cache::put('all_permissions_usage_stats', ['test' => 'data'], 3600);
        
        $this->assertTrue(Cache::has("permission_usage_stats_{$permission->id}"));
        $this->assertTrue(Cache::has('unused_permissions'));
        $this->assertTrue(Cache::has('all_permissions_usage_stats'));
        
        // 清除快取
        $this->usageAnalysisService->clearUsageCache();
        
        $this->assertFalse(Cache::has("permission_usage_stats_{$permission->id}"));
        $this->assertFalse(Cache::has('unused_permissions'));
        $this->assertFalse(Cache::has('all_permissions_usage_stats'));
    }

    /** @test */
    public function it_excludes_system_permissions_when_requested()
    {
        // 創建系統權限
        $systemPermission = Permission::factory()->create([
            'name' => 'system.manage',
            'module' => 'system',
            'created_at' => now()->subDays(100),
        ]);
        
        // 創建一般權限
        $regularPermission = Permission::factory()->create([
            'name' => 'test.manage',
            'module' => 'test',
            'created_at' => now()->subDays(100),
        ]);
        
        $options = [
            'days_threshold' => 90,
            'exclude_system' => true,
        ];
        
        $result = $this->usageAnalysisService->markUnusedPermissions($options);
        
        $markedIds = collect($result['marked_permissions'])->pluck('id')->toArray();
        
        // 系統權限應該被排除
        $this->assertNotContains($systemPermission->id, $markedIds);
        // 一般權限應該被包含
        $this->assertContains($regularPermission->id, $markedIds);
    }

    /** @test */
    public function permission_model_can_check_if_marked_as_unused()
    {
        $permission = Permission::factory()->create();
        
        // 初始狀態應該是未標記
        $this->assertFalse($permission->isMarkedAsUnused());
        
        // 標記為未使用
        $permission->markAsUnused();
        
        // 重新載入模型以清除快取
        $permission->refresh();
        $this->assertTrue($permission->isMarkedAsUnused());
        
        // 取消標記
        $permission->unmarkAsUnused();
        
        // 重新載入模型以清除快取
        $permission->refresh();
        $this->assertFalse($permission->isMarkedAsUnused());
    }

    /** @test */
    public function permission_model_can_calculate_usage_intensity()
    {
        $permission = Permission::factory()->create();
        $role = Role::factory()->create();
        $role->permissions()->attach($permission->id);
        
        $users = User::factory()->count(2)->create();
        foreach ($users as $user) {
            $user->roles()->attach($role->id);
        }
        
        $intensity = $permission->getUsageIntensity();
        
        $this->assertIsFloat($intensity);
        $this->assertGreaterThan(0, $intensity);
        
        // 使用強度 = (使用者數量 * 2 + 角色數量) / 10
        // 預期值 = (2 * 2 + 1) / 10 = 0.5
        $this->assertEquals(0.5, $intensity);
    }

    /** @test */
    public function permission_model_can_get_frequency_level()
    {
        $permission = Permission::factory()->create(['name' => 'test.frequency_level']);
        
        // 測試不同的頻率等級
        $level = $permission->getFrequencyLevel();
        $this->assertIsString($level);
        $this->assertContains($level, ['very_high', 'high', 'medium', 'low', 'very_low']);
    }
}