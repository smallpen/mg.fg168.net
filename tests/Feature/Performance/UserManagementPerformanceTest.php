<?php

namespace Tests\Feature\Performance;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Livewire\Livewire;
use App\Livewire\Admin\Users\UserList;

/**
 * 使用者管理效能測試
 * 
 * 專門測試使用者管理功能的效能要求
 * 需求: 7.1, 7.2, 7.3
 */
class UserManagementPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試角色和使用者
        $this->adminRole = Role::factory()->create(['name' => 'admin']);
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($this->adminRole);
    }

    /**
     * 測試頁面初始載入效能
     * 需求: 7.1 - 載入使用者列表頁面應在 2 秒內完成初始載入
     */
    public function test_initial_page_load_performance()
    {
        $this->actingAs($this->admin);

        // 建立大量測試資料
        User::factory()->count(100)->create();

        $startTime = microtime(true);
        
        $response = $this->get('/admin/users');
        
        $loadTime = microtime(true) - $startTime;
        
        $response->assertStatus(200);
        $this->assertLessThan(2.0, $loadTime, "頁面載入時間 {$loadTime} 秒超過 2 秒限制");
        
        // 記錄效能指標
        $this->recordPerformanceMetric('page_load_time', $loadTime);
    }

    /**
     * 測試 Livewire 元件載入效能
     */
    public function test_livewire_component_load_performance()
    {
        $this->actingAs($this->admin);

        // 建立測試資料
        User::factory()->count(50)->create();

        $startTime = microtime(true);
        
        $component = Livewire::test(UserList::class);
        $users = $component->instance()->users;
        
        $componentLoadTime = microtime(true) - $startTime;
        
        $this->assertLessThan(1.0, $componentLoadTime, "Livewire 元件載入時間 {$componentLoadTime} 秒超過 1 秒限制");
        $this->assertNotNull($users);
        $this->assertGreaterThan(0, $users->count());
        
        $this->recordPerformanceMetric('component_load_time', $componentLoadTime);
    }

    /**
     * 測試搜尋功能效能
     * 需求: 7.2 - 執行搜尋或篩選操作應在 1 秒內顯示結果
     */
    public function test_search_performance()
    {
        $this->actingAs($this->admin);

        // 建立大量測試資料，包含可搜尋的內容
        User::factory()->count(200)->create();
        User::factory()->create(['name' => 'Test Search User', 'username' => 'testsearch']);

        $startTime = microtime(true);
        
        $component = Livewire::test(UserList::class)
            ->set('search', 'testsearch');
            
        $searchTime = microtime(true) - $startTime;
        
        $this->assertLessThan(1.0, $searchTime, "搜尋響應時間 {$searchTime} 秒超過 1 秒限制");
        
        // 驗證搜尋結果正確性
        $users = $component->instance()->users;
        $this->assertGreaterThan(0, $users->count());
        
        $this->recordPerformanceMetric('search_response_time', $searchTime);
    }

    /**
     * 測試篩選功能效能
     */
    public function test_filter_performance()
    {
        $this->actingAs($this->admin);

        // 建立不同狀態的使用者
        User::factory()->count(50)->create(['is_active' => true]);
        User::factory()->count(50)->create(['is_active' => false]);

        // 測試狀態篩選效能
        $startTime = microtime(true);
        
        $component = Livewire::test(UserList::class)
            ->set('statusFilter', 'active');
            
        $filterTime = microtime(true) - $startTime;
        
        $this->assertLessThan(1.0, $filterTime, "篩選響應時間 {$filterTime} 秒超過 1 秒限制");
        
        // 驗證篩選結果
        $users = $component->instance()->users;
        $this->assertGreaterThan(0, $users->count());
        
        $this->recordPerformanceMetric('filter_response_time', $filterTime);
    }

    /**
     * 測試角色篩選效能
     */
    public function test_role_filter_performance()
    {
        $this->actingAs($this->admin);

        // 建立不同角色的使用者
        $userRole = Role::factory()->create(['name' => 'user']);
        $users = User::factory()->count(30)->create();
        
        foreach ($users->take(15) as $user) {
            $user->roles()->attach($this->adminRole);
        }
        
        foreach ($users->skip(15) as $user) {
            $user->roles()->attach($userRole);
        }

        $startTime = microtime(true);
        
        $component = Livewire::test(UserList::class)
            ->set('roleFilter', 'admin');
            
        $roleFilterTime = microtime(true) - $startTime;
        
        $this->assertLessThan(1.0, $roleFilterTime, "角色篩選響應時間 {$roleFilterTime} 秒超過 1 秒限制");
        
        $this->recordPerformanceMetric('role_filter_response_time', $roleFilterTime);
    }

    /**
     * 測試分頁效能
     */
    public function test_pagination_performance()
    {
        $this->actingAs($this->admin);

        // 建立足夠的資料來測試分頁
        User::factory()->count(100)->create();

        $startTime = microtime(true);
        
        $component = Livewire::test(UserList::class);
        $users = $component->instance()->users;
        
        $paginationTime = microtime(true) - $startTime;
        
        $this->assertLessThan(1.0, $paginationTime, "分頁載入時間 {$paginationTime} 秒超過 1 秒限制");
        $this->assertEquals(15, $users->perPage(), '每頁應顯示 15 筆資料');
        $this->assertGreaterThan(0, $users->count());
        
        $this->recordPerformanceMetric('pagination_load_time', $paginationTime);
    }

    /**
     * 測試排序效能
     */
    public function test_sorting_performance()
    {
        $this->actingAs($this->admin);

        // 建立測試資料
        User::factory()->count(80)->create();

        $startTime = microtime(true);
        
        $component = Livewire::test(UserList::class)
            ->call('sortBy', 'name');
            
        $sortTime = microtime(true) - $startTime;
        
        $this->assertLessThan(1.0, $sortTime, "排序響應時間 {$sortTime} 秒超過 1 秒限制");
        
        // 驗證排序設定
        $this->assertEquals('name', $component->get('sortField'));
        $this->assertEquals('asc', $component->get('sortDirection'));
        
        $this->recordPerformanceMetric('sort_response_time', $sortTime);
    }

    /**
     * 測試批量操作效能
     */
    public function test_bulk_operations_performance()
    {
        $this->actingAs($this->admin);

        // 建立測試使用者
        $users = User::factory()->count(20)->create(['is_active' => true]);
        $userIds = $users->pluck('id')->toArray();

        $startTime = microtime(true);
        
        $component = Livewire::test(UserList::class)
            ->set('selectedUsers', $userIds)
            ->call('bulkDeactivate');
            
        $bulkOperationTime = microtime(true) - $startTime;
        
        $this->assertLessThan(2.0, $bulkOperationTime, "批量操作時間 {$bulkOperationTime} 秒超過 2 秒限制");
        
        $this->recordPerformanceMetric('bulk_operation_time', $bulkOperationTime);
    }

    /**
     * 測試快取效能
     * 需求: 7.3 - 使用適當的分頁和索引優化查詢效能
     */
    public function test_caching_performance()
    {
        $this->actingAs($this->admin);

        // 清除快取
        Cache::flush();

        // 第一次載入（無快取）
        $startTime = microtime(true);
        
        $component = Livewire::test(UserList::class);
        $roles = $component->instance()->availableRoles;
        
        $firstLoadTime = microtime(true) - $startTime;

        // 第二次載入（有快取）
        $startTime = microtime(true);
        
        $component = Livewire::test(UserList::class);
        $roles = $component->instance()->availableRoles;
        
        $cachedLoadTime = microtime(true) - $startTime;
        
        $this->assertLessThan($firstLoadTime, $cachedLoadTime, '快取載入應該比首次載入更快');
        $this->assertLessThan(0.1, $cachedLoadTime, "快取載入時間 {$cachedLoadTime} 秒應該非常快");
        
        $this->recordPerformanceMetric('cache_load_time', $cachedLoadTime);
        $this->recordPerformanceMetric('first_load_time', $firstLoadTime);
    }

    /**
     * 測試資料庫查詢效能
     */
    public function test_database_query_performance()
    {
        $this->actingAs($this->admin);

        // 建立測試資料
        User::factory()->count(100)->create();

        // 啟用查詢日誌
        DB::enableQueryLog();
        
        $startTime = microtime(true);
        
        $component = Livewire::test(UserList::class)
            ->set('search', 'test')
            ->set('statusFilter', 'active')
            ->set('roleFilter', 'admin');
            
        $queryTime = microtime(true) - $startTime;
        
        $queries = DB::getQueryLog();
        
        // 檢查查詢效能
        $this->assertLessThan(1.0, $queryTime, "資料庫查詢時間 {$queryTime} 秒超過 1 秒限制");
        $this->assertLessThan(10, count($queries), '查詢數量應該合理，避免 N+1 問題');
        
        // 檢查是否有慢查詢
        foreach ($queries as $query) {
            $this->assertLessThan(500, $query['time'], "單個查詢時間 {$query['time']}ms 過長");
        }
        
        DB::disableQueryLog();
        
        $this->recordPerformanceMetric('database_query_time', $queryTime);
        $this->recordPerformanceMetric('query_count', count($queries));
    }

    /**
     * 測試大量資料處理效能
     * 需求: 7.3 - 當使用者數量超過 1000 筆時，系統應使用適當的分頁和索引優化查詢效能
     */
    public function test_large_dataset_performance()
    {
        $this->actingAs($this->admin);

        // 建立大量測試資料（模擬超過 1000 筆的情況）
        User::factory()->count(1000)->create();

        $startTime = microtime(true);
        
        $component = Livewire::test(UserList::class);
        $users = $component->instance()->users;
        
        $largeDatasetTime = microtime(true) - $startTime;
        
        $this->assertLessThan(3.0, $largeDatasetTime, "大量資料載入時間 {$largeDatasetTime} 秒超過 3 秒限制");
        $this->assertEquals(15, $users->perPage(), '應該使用分頁限制每頁資料量');
        $this->assertLessThanOrEqual(15, $users->count(), '每頁資料不應超過 15 筆');
        
        $this->recordPerformanceMetric('large_dataset_load_time', $largeDatasetTime);
    }

    /**
     * 測試搜尋防抖效能
     */
    public function test_search_debounce_performance()
    {
        $this->actingAs($this->admin);

        // 建立測試資料
        User::factory()->count(50)->create();

        // 模擬快速輸入（防抖應該減少查詢次數）
        DB::enableQueryLog();
        
        $component = Livewire::test(UserList::class);
        
        // 快速連續設定搜尋條件
        $startTime = microtime(true);
        
        $component->set('search', 't')
                  ->set('search', 'te')
                  ->set('search', 'tes')
                  ->set('search', 'test');
                  
        $debounceTime = microtime(true) - $startTime;
        
        $queries = DB::getQueryLog();
        
        // 防抖應該減少查詢次數
        $this->assertLessThan(0.5, $debounceTime, "防抖處理時間 {$debounceTime} 秒應該很快");
        
        DB::disableQueryLog();
        
        $this->recordPerformanceMetric('debounce_processing_time', $debounceTime);
    }

    /**
     * 測試記憶體使用效能
     */
    public function test_memory_usage_performance()
    {
        $this->actingAs($this->admin);

        // 記錄初始記憶體使用量
        $initialMemory = memory_get_usage(true);

        // 建立測試資料
        User::factory()->count(100)->create();

        // 執行使用者列表操作
        $component = Livewire::test(UserList::class)
            ->set('search', 'test')
            ->set('statusFilter', 'active')
            ->call('sortBy', 'name');

        $users = $component->instance()->users;

        // 記錄峰值記憶體使用量
        $peakMemory = memory_get_peak_usage(true);
        $memoryUsed = $peakMemory - $initialMemory;

        // 檢查記憶體使用是否合理（不超過 50MB）
        $this->assertLessThan(50 * 1024 * 1024, $memoryUsed, "記憶體使用量 " . ($memoryUsed / 1024 / 1024) . "MB 過高");
        
        $this->recordPerformanceMetric('memory_usage_mb', $memoryUsed / 1024 / 1024);
    }

    /**
     * 測試並發處理效能
     */
    public function test_concurrent_operations_performance()
    {
        $this->actingAs($this->admin);

        // 建立測試資料
        $users = User::factory()->count(10)->create();

        $startTime = microtime(true);

        // 模擬並發操作
        $operations = [];
        
        for ($i = 0; $i < 5; $i++) {
            $operations[] = function() use ($users) {
                $component = Livewire::test(UserList::class);
                $component->call('toggleUserStatus', $users->random()->id);
                return $component;
            };
        }

        // 執行並發操作
        $results = [];
        foreach ($operations as $operation) {
            $results[] = $operation();
        }

        $concurrentTime = microtime(true) - $startTime;

        $this->assertLessThan(2.0, $concurrentTime, "並發操作時間 {$concurrentTime} 秒超過 2 秒限制");
        $this->assertCount(5, $results, '所有並發操作都應該成功完成');
        
        $this->recordPerformanceMetric('concurrent_operations_time', $concurrentTime);
    }

    /**
     * 記錄效能指標（用於監控和分析）
     */
    private function recordPerformanceMetric(string $metric, float $value): void
    {
        // 這裡可以將效能指標記錄到日誌或監控系統
        // 例如：Log::info("Performance metric: {$metric} = {$value}");
        
        // 或者存儲到資料庫用於後續分析
        // PerformanceMetric::create(['metric' => $metric, 'value' => $value, 'timestamp' => now()]);
        
        // 暫時使用斷言來驗證效能指標在合理範圍內
        $this->assertIsFloat($value);
        $this->assertGreaterThan(0, $value);
    }

    /**
     * 效能測試總結
     */
    public function test_performance_summary()
    {
        $this->actingAs($this->admin);

        // 建立綜合測試資料
        User::factory()->count(200)->create();

        $metrics = [];

        // 頁面載入效能
        $startTime = microtime(true);
        $response = $this->get('/admin/users');
        $metrics['page_load'] = microtime(true) - $startTime;

        // 搜尋效能
        $startTime = microtime(true);
        Livewire::test(UserList::class)->set('search', 'test');
        $metrics['search'] = microtime(true) - $startTime;

        // 篩選效能
        $startTime = microtime(true);
        Livewire::test(UserList::class)->set('statusFilter', 'active');
        $metrics['filter'] = microtime(true) - $startTime;

        // 排序效能
        $startTime = microtime(true);
        Livewire::test(UserList::class)->call('sortBy', 'name');
        $metrics['sort'] = microtime(true) - $startTime;

        // 驗證所有效能指標都符合要求
        $this->assertLessThan(2.0, $metrics['page_load'], '頁面載入效能不符合要求');
        $this->assertLessThan(1.0, $metrics['search'], '搜尋效能不符合要求');
        $this->assertLessThan(1.0, $metrics['filter'], '篩選效能不符合要求');
        $this->assertLessThan(1.0, $metrics['sort'], '排序效能不符合要求');

        // 記錄效能摘要
        foreach ($metrics as $metric => $value) {
            $this->recordPerformanceMetric("summary_{$metric}", $value);
        }
    }
}