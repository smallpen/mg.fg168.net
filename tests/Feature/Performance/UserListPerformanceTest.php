<?php

namespace Tests\Feature\Performance;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Services\UserCacheService;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 使用者列表效能測試
 * 
 * 測試使用者管理功能的效能優化，包括快取、索引和查詢優化
 */
class UserListPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected UserCacheService $cacheService;
    protected UserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cacheService = app(UserCacheService::class);
        $this->userRepository = app(UserRepository::class);
        
        // 建立測試資料
        $this->createTestData();
    }

    /**
     * 建立測試資料
     */
    private function createTestData(): void
    {
        // 建立角色
        $adminRole = Role::create(['name' => 'admin', 'display_name' => '管理員']);
        $userRole = Role::create(['name' => 'user', 'display_name' => '使用者']);
        
        // 建立大量使用者資料來測試效能
        $users = User::factory()->count(100)->create();
        
        // 隨機指派角色
        foreach ($users as $user) {
            $role = rand(0, 1) ? $adminRole : $userRole;
            $user->roles()->attach($role);
        }
    }

    /**
     * 測試查詢結果快取機制
     */
    public function test_query_result_caching(): void
    {
        // 清除快取
        $this->cacheService->clearAll();
        
        $filters = ['status' => 'all', 'role' => 'all'];
        
        // 第一次查詢（從資料庫）
        $start = microtime(true);
        $users1 = $this->userRepository->getPaginatedUsers($filters, 15);
        $time1 = microtime(true) - $start;
        
        // 第二次查詢（從快取）
        $start = microtime(true);
        $users2 = $this->userRepository->getPaginatedUsers($filters, 15);
        $time2 = microtime(true) - $start;
        
        // 驗證結果一致
        $this->assertEquals($users1->total(), $users2->total());
        $this->assertEquals($users1->count(), $users2->count());
        
        // 驗證快取效能提升
        $this->assertLessThan($time1, $time2);
        
        // 效能提升應該超過 50%
        $improvement = (($time1 - $time2) / $time1) * 100;
        $this->assertGreaterThan(50, $improvement);
    }

    /**
     * 測試角色列表快取
     */
    public function test_roles_list_caching(): void
    {
        // 清除快取
        $this->cacheService->clearRoles();
        
        // 第一次查詢
        $start = microtime(true);
        $roles1 = $this->userRepository->getAvailableRoles();
        $time1 = microtime(true) - $start;
        
        // 第二次查詢（應該從快取讀取）
        $start = microtime(true);
        $roles2 = $this->userRepository->getAvailableRoles();
        $time2 = microtime(true) - $start;
        
        // 驗證結果一致
        $this->assertEquals($roles1->count(), $roles2->count());
        $this->assertEquals($roles1->pluck('name')->toArray(), $roles2->pluck('name')->toArray());
        
        // 驗證快取效能提升
        $this->assertLessThan($time1, $time2);
    }

    /**
     * 測試搜尋功能效能
     */
    public function test_search_performance(): void
    {
        // 測試不同的搜尋模式
        $searchTerms = ['admin', 'user', 'test'];
        
        foreach ($searchTerms as $term) {
            $start = microtime(true);
            
            $users = $this->userRepository->getPaginatedUsers([
                'search' => $term,
                'status' => 'all',
                'role' => 'all'
            ], 15);
            
            $time = microtime(true) - $start;
            
            // 搜尋應該在合理時間內完成（< 100ms）
            $this->assertLessThan(0.1, $time, "搜尋 '{$term}' 耗時過長: " . ($time * 1000) . "ms");
        }
    }

    /**
     * 測試索引效能
     */
    public function test_database_indexes_performance(): void
    {
        // 測試狀態篩選查詢
        $start = microtime(true);
        $activeUsers = $this->userRepository->getPaginatedUsers([
            'status' => 'active',
            'role' => 'all'
        ], 15);
        $time1 = microtime(true) - $start;
        
        // 測試角色篩選查詢
        $start = microtime(true);
        $adminUsers = $this->userRepository->getPaginatedUsers([
            'status' => 'all',
            'role' => 'admin'
        ], 15);
        $time2 = microtime(true) - $start;
        
        // 測試複合篩選查詢
        $start = microtime(true);
        $activeAdmins = $this->userRepository->getPaginatedUsers([
            'status' => 'active',
            'role' => 'admin'
        ], 15);
        $time3 = microtime(true) - $start;
        
        // 所有查詢都應該在合理時間內完成
        $this->assertLessThan(0.05, $time1, "狀態篩選查詢耗時過長: " . ($time1 * 1000) . "ms");
        $this->assertLessThan(0.05, $time2, "角色篩選查詢耗時過長: " . ($time2 * 1000) . "ms");
        $this->assertLessThan(0.05, $time3, "複合篩選查詢耗時過長: " . ($time3 * 1000) . "ms");
    }

    /**
     * 測試統計資料快取
     */
    public function test_stats_caching(): void
    {
        // 清除統計快取
        $this->cacheService->clearStats();
        
        // 第一次查詢統計
        $start = microtime(true);
        $stats1 = $this->userRepository->getUserStats();
        $time1 = microtime(true) - $start;
        
        // 第二次查詢統計（應該從快取讀取）
        $start = microtime(true);
        $stats2 = $this->userRepository->getUserStats();
        $time2 = microtime(true) - $start;
        
        // 驗證結果一致
        $this->assertEquals($stats1['total_users'], $stats2['total_users']);
        $this->assertEquals($stats1['active_users'], $stats2['active_users']);
        
        // 驗證快取效能提升
        $this->assertLessThan($time1, $time2);
    }

    /**
     * 測試大量資料的分頁效能
     */
    public function test_pagination_performance_with_large_dataset(): void
    {
        // 建立更多測試資料
        User::factory()->count(500)->create();
        
        $pages = [1, 5, 10, 20];
        
        foreach ($pages as $page) {
            $start = microtime(true);
            
            // 模擬分頁請求
            request()->merge(['page' => $page]);
            
            $users = $this->userRepository->getPaginatedUsers([
                'status' => 'all',
                'role' => 'all',
                'sort_field' => 'created_at',
                'sort_direction' => 'desc'
            ], 15);
            
            $time = microtime(true) - $start;
            
            // 分頁查詢應該在合理時間內完成
            $this->assertLessThan(0.1, $time, "第 {$page} 頁查詢耗時過長: " . ($time * 1000) . "ms");
            $this->assertEquals(15, $users->perPage());
        }
    }

    /**
     * 測試快取清除功能
     */
    public function test_cache_clearing(): void
    {
        // 預熱快取
        $this->userRepository->getPaginatedUsers(['status' => 'all', 'role' => 'all'], 15);
        $this->userRepository->getAvailableRoles();
        $this->userRepository->getUserStats();
        
        // 驗證快取存在
        $this->assertTrue(Cache::has(UserCacheService::CACHE_KEYS['user_roles_list']));
        $this->assertTrue(Cache::has(UserCacheService::CACHE_KEYS['user_stats']));
        
        // 清除所有快取
        $this->cacheService->clearAll();
        
        // 驗證快取已清除
        $this->assertFalse(Cache::has(UserCacheService::CACHE_KEYS['user_roles_list']));
        $this->assertFalse(Cache::has(UserCacheService::CACHE_KEYS['user_stats']));
    }

    /**
     * 測試搜尋防抖功能（模擬）
     */
    public function test_search_debounce_simulation(): void
    {
        $searchTerms = ['a', 'ad', 'adm', 'admin'];
        $times = [];
        
        foreach ($searchTerms as $term) {
            $start = microtime(true);
            
            // 模擬搜尋請求
            $users = $this->userRepository->getPaginatedUsers([
                'search' => $term,
                'status' => 'all',
                'role' => 'all'
            ], 15);
            
            $times[] = microtime(true) - $start;
        }
        
        // 每個搜尋都應該在合理時間內完成
        foreach ($times as $i => $time) {
            $this->assertLessThan(0.1, $time, "搜尋 '{$searchTerms[$i]}' 耗時過長: " . ($time * 1000) . "ms");
        }
    }

    /**
     * 測試查詢計畫優化
     */
    public function test_query_plan_optimization(): void
    {
        // 測試基本查詢的執行計畫
        $explain = DB::select("EXPLAIN SELECT id, username, name, email, is_active, created_at 
                              FROM users 
                              WHERE is_active = 1 
                              ORDER BY created_at DESC, id DESC 
                              LIMIT 15");
        
        // 應該使用索引
        $this->assertNotEquals('ALL', $explain[0]->type);
        $this->assertNotNull($explain[0]->key);
        
        // 測試搜尋查詢的執行計畫
        $explain = DB::select("EXPLAIN SELECT id, username, name, email, is_active, created_at 
                              FROM users 
                              WHERE (username LIKE 'admin%' OR email LIKE 'admin%' OR name LIKE 'admin%') 
                              ORDER BY created_at DESC, id DESC 
                              LIMIT 15");
        
        // 搜尋查詢應該使用索引合併或其他優化策略
        $this->assertContains($explain[0]->type, ['index_merge', 'range', 'ref']);
    }
}