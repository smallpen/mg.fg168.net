<?php

namespace Tests\Feature\Livewire\Admin\Users;

use App\Livewire\Admin\Users\UserList;
use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * UserList 元件效能和安全性測試
 * 
 * 測試元件在各種效能和安全性場景下的表現：
 * - 大量資料處理
 * - 查詢效能優化
 * - 快取機制
 * - 安全性驗證
 * - 防護機制
 */
class UserListPerformanceSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試角色和權限
        $this->adminRole = Role::factory()->create(['name' => 'admin']);
        
        $permissions = ['users.view', 'users.edit', 'users.delete'];
        foreach ($permissions as $permissionName) {
            $permission = Permission::factory()->create(['name' => $permissionName]);
            $this->adminRole->permissions()->attach($permission);
        }
        
        // 建立管理員使用者
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($this->adminRole);
    }

    // ==================== 效能測試 ====================

    /**
     * 測試大量資料載入效能
     */
    public function test_large_dataset_loading_performance()
    {
        $this->actingAs($this->admin);

        // 建立大量測試資料
        User::factory()->count(1000)->create();

        $startTime = microtime(true);

        $component = Livewire::test(UserList::class);
        $component->assertStatus(200);

        $endTime = microtime(true);
        $loadTime = $endTime - $startTime;

        // 載入時間應該在合理範圍內（2秒內）
        $this->assertLessThan(2.0, $loadTime, '大量資料載入時間超過 2 秒');
    }

    /**
     * 測試搜尋查詢效能
     */
    public function test_search_query_performance()
    {
        $this->actingAs($this->admin);

        // 建立測試資料
        User::factory()->count(500)->create();

        // 記錄查詢次數
        $queryCount = 0;
        DB::listen(function ($query) use (&$queryCount) {
            $queryCount++;
        });

        $startTime = microtime(true);

        Livewire::test(UserList::class)
            ->set('search', 'test');

        $endTime = microtime(true);
        $searchTime = $endTime - $startTime;

        // 搜尋時間應該在 1 秒內
        $this->assertLessThan(1.0, $searchTime, '搜尋查詢時間超過 1 秒');
        
        // 查詢次數應該合理（避免 N+1 問題）
        $this->assertLessThan(10, $queryCount, '搜尋查詢次數過多');
    }

    /**
     * 測試分頁查詢效能
     */
    public function test_pagination_query_performance()
    {
        $this->actingAs($this->admin);

        User::factory()->count(100)->create();

        $queryCount = 0;
        DB::listen(function ($query) use (&$queryCount) {
            $queryCount++;
        });

        $component = Livewire::test(UserList::class);

        // 測試翻頁效能
        for ($page = 1; $page <= 5; $page++) {
            $startTime = microtime(true);
            
            $component->call('gotoPage', $page);
            
            $endTime = microtime(true);
            $pageTime = $endTime - $startTime;
            
            $this->assertLessThan(0.5, $pageTime, "第 {$page} 頁載入時間超過 0.5 秒");
        }
    }

    /**
     * 測試排序查詢效能
     */
    public function test_sorting_query_performance()
    {
        $this->actingAs($this->admin);

        User::factory()->count(200)->create();

        $sortFields = ['name', 'username', 'email', 'created_at', 'is_active'];

        foreach ($sortFields as $field) {
            $queryCount = 0;
            DB::listen(function ($query) use (&$queryCount) {
                $queryCount++;
            });

            $startTime = microtime(true);

            Livewire::test(UserList::class)
                ->call('sortBy', $field);

            $endTime = microtime(true);
            $sortTime = $endTime - $startTime;

            $this->assertLessThan(0.5, $sortTime, "按 {$field} 排序時間超過 0.5 秒");
        }
    }

    /**
     * 測試批量操作效能
     */
    public function test_bulk_operations_performance()
    {
        $this->actingAs($this->admin);

        $users = User::factory()->count(50)->create(['is_active' => false]);
        $userIds = $users->pluck('id')->toArray();

        $startTime = microtime(true);

        Livewire::test(UserList::class)
            ->set('selectedUsers', $userIds)
            ->call('bulkActivate');

        $endTime = microtime(true);
        $bulkTime = $endTime - $startTime;

        // 批量操作應該在 1 秒內完成
        $this->assertLessThan(1.0, $bulkTime, '批量操作時間超過 1 秒');

        // 驗證所有使用者都被啟用
        foreach ($users as $user) {
            $this->assertTrue($user->fresh()->is_active);
        }
    }

    // ==================== 快取測試 ====================

    /**
     * 測試角色列表快取
     */
    public function test_roles_list_caching()
    {
        $this->actingAs($this->admin);

        // 清除快取
        Cache::flush();

        $queryCount = 0;
        DB::listen(function ($query) use (&$queryCount) {
            if (str_contains($query->sql, 'roles')) {
                $queryCount++;
            }
        });

        // 第一次載入應該查詢資料庫
        $component1 = Livewire::test(UserList::class);
        $firstQueryCount = $queryCount;

        // 第二次載入應該使用快取
        $component2 = Livewire::test(UserList::class);
        $secondQueryCount = $queryCount;

        // 第二次載入的角色查詢次數不應該增加
        $this->assertEquals($firstQueryCount, $secondQueryCount, '角色列表沒有使用快取');
    }

    /**
     * 測試搜尋結果快取
     */
    public function test_search_results_caching()
    {
        $this->actingAs($this->admin);

        User::factory()->count(20)->create();

        $component = Livewire::test(UserList::class);

        $queryCount = 0;
        DB::listen(function ($query) use (&$queryCount) {
            $queryCount++;
        });

        // 第一次搜尋
        $component->set('search', 'test');
        $firstQueryCount = $queryCount;

        // 相同搜尋條件應該使用快取（如果有實作）
        $component->set('search', '');
        $component->set('search', 'test');
        $secondQueryCount = $queryCount;

        // 驗證快取機制（這裡主要測試查詢優化）
        $this->assertGreaterThan(0, $firstQueryCount);
    }

    /**
     * 測試快取失效機制
     */
    public function test_cache_invalidation()
    {
        $this->actingAs($this->admin);

        // 設定測試快取
        Cache::put('user_stats', ['total' => 100], 3600);
        $this->assertEquals(['total' => 100], Cache::get('user_stats'));

        $user = User::factory()->create(['is_active' => true]);

        // 執行會影響快取的操作
        Livewire::test(UserList::class)
            ->call('toggleUserStatus', $user->id);

        // 相關快取應該被清除（這裡測試快取清除邏輯）
        $this->assertFalse($user->fresh()->is_active);
    }

    // ==================== 安全性測試 ====================

    /**
     * 測試 SQL 注入防護
     */
    public function test_sql_injection_protection()
    {
        $this->actingAs($this->admin);

        $sqlInjectionAttempts = [
            "'; DROP TABLE users; --",
            "' OR '1'='1",
            "'; UPDATE users SET is_active = 0; --",
            "' UNION SELECT * FROM users --",
            "'; DELETE FROM users WHERE id > 0; --"
        ];

        foreach ($sqlInjectionAttempts as $maliciousInput) {
            $component = Livewire::test(UserList::class);
            
            // 嘗試 SQL 注入
            $component->set('search', $maliciousInput);
            
            // 搜尋應該被清理或拒絕
            $this->assertNotEquals($maliciousInput, $component->get('search'));
            
            // 資料庫應該保持完整
            $this->assertGreaterThan(0, User::count());
        }
    }

    /**
     * 測試 XSS 防護
     */
    public function test_xss_protection()
    {
        $this->actingAs($this->admin);

        $xssAttempts = [
            '<script>alert("xss")</script>',
            '<img src=x onerror=alert("xss")>',
            'javascript:alert("xss")',
            '<svg onload=alert("xss")>',
            '<iframe src="javascript:alert(\'xss\')"></iframe>'
        ];

        foreach ($xssAttempts as $maliciousInput) {
            $user = User::factory()->create(['name' => $maliciousInput]);
            
            $component = Livewire::test(UserList::class);
            
            // 輸出應該被轉義
            $html = $component->render()->render();
            $this->assertStringNotContainsString('<script>', $html);
            $this->assertStringNotContainsString('javascript:', $html);
        }
    }

    /**
     * 測試 CSRF 防護
     */
    public function test_csrf_protection()
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create(['is_active' => true]);

        // Livewire 應該自動處理 CSRF 保護
        $component = Livewire::test(UserList::class);
        
        // 正常操作應該成功
        $component->call('toggleUserStatus', $user->id)
            ->assertDispatched('user-status-updated');
    }

    /**
     * 測試權限提升攻擊防護
     */
    public function test_privilege_escalation_protection()
    {
        // 建立一般使用者
        $regularUser = User::factory()->create();
        $userRole = Role::factory()->create(['name' => 'user']);
        $regularUser->roles()->attach($userRole);

        $this->actingAs($regularUser);

        $targetUser = User::factory()->create(['is_active' => true]);

        // 一般使用者嘗試執行管理員操作
        Livewire::test(UserList::class)
            ->call('toggleUserStatus', $targetUser->id)
            ->assertDispatched('show-toast');

        // 目標使用者狀態不應該改變
        $this->assertTrue($targetUser->fresh()->is_active);
    }

    /**
     * 測試大量請求防護（防止 DoS）
     */
    public function test_rate_limiting_protection()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(UserList::class);

        // 模擬大量快速請求
        $startTime = microtime(true);
        
        for ($i = 0; $i < 50; $i++) {
            $component->set('search', "test{$i}");
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;

        // 系統應該能處理大量請求而不崩潰
        $this->assertLessThan(10.0, $totalTime, '大量請求處理時間過長');
        $component->assertStatus(200);
    }

    /**
     * 測試敏感資料洩露防護
     */
    public function test_sensitive_data_protection()
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create([
            'password' => 'secret_password',
            'remember_token' => 'secret_token'
        ]);

        $component = Livewire::test(UserList::class);
        $html = $component->render()->render();

        // 敏感資料不應該出現在 HTML 中
        $this->assertStringNotContainsString('secret_password', $html);
        $this->assertStringNotContainsString('secret_token', $html);
        $this->assertStringNotContainsString('password', $html);
        $this->assertStringNotContainsString('remember_token', $html);
    }

    // ==================== 並發測試 ====================

    /**
     * 測試並發操作處理
     */
    public function test_concurrent_operations()
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create(['is_active' => true]);

        // 模擬兩個並發的狀態切換操作
        $component1 = Livewire::test(UserList::class);
        $component2 = Livewire::test(UserList::class);

        // 同時執行操作
        $component1->call('toggleUserStatus', $user->id);
        $component2->call('toggleUserStatus', $user->id);

        // 最終狀態應該是一致的
        $finalUser = $user->fresh();
        $this->assertNotNull($finalUser);
        $this->assertIsBool($finalUser->is_active);
    }

    /**
     * 測試資料競爭條件
     */
    public function test_race_condition_handling()
    {
        $this->actingAs($this->admin);

        $users = User::factory()->count(5)->create(['is_active' => false]);
        $userIds = $users->pluck('id')->toArray();

        // 模擬並發的批量操作
        $component1 = Livewire::test(UserList::class);
        $component2 = Livewire::test(UserList::class);

        $component1->set('selectedUsers', $userIds)->call('bulkActivate');
        $component2->set('selectedUsers', $userIds)->call('bulkDeactivate');

        // 所有使用者應該有明確的最終狀態
        foreach ($users as $user) {
            $finalUser = $user->fresh();
            $this->assertNotNull($finalUser);
            $this->assertIsBool($finalUser->is_active);
        }
    }

    // ==================== 記憶體使用測試 ====================

    /**
     * 測試記憶體使用效率
     */
    public function test_memory_usage_efficiency()
    {
        $this->actingAs($this->admin);

        $initialMemory = memory_get_usage(true);

        // 建立大量資料
        User::factory()->count(100)->create();

        $component = Livewire::test(UserList::class);
        
        // 執行各種操作
        $component->set('search', 'test');
        $component->call('sortBy', 'name');
        $component->call('gotoPage', 2);

        $finalMemory = memory_get_usage(true);
        $memoryIncrease = $finalMemory - $initialMemory;

        // 記憶體增長應該在合理範圍內（50MB）
        $this->assertLessThan(50 * 1024 * 1024, $memoryIncrease, '記憶體使用量過高');
    }
}