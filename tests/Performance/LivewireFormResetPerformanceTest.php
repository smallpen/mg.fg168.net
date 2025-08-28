<?php

namespace Tests\Performance;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

/**
 * Livewire 表單重置效能測試套件
 * 
 * 測量修復前後的效能差異，分析記憶體使用和響應時間變化
 * 建立效能監控和警報機制
 */
class LivewireFormResetPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected array $performanceBaselines;
    protected array $performanceResults = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestData();
        $this->definePerformanceBaselines();
    }

    /**
     * 建立測試資料
     */
    private function setupTestData(): void
    {
        // 建立權限
        $permissions = [
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
            'permissions.view', 'permissions.create', 'permissions.edit', 'permissions.delete',
            'dashboard.view', 'dashboard.stats',
            'activity_logs.view', 'activity_logs.export', 'activity_logs.delete',
            'settings.view', 'settings.edit', 'settings.backup', 'settings.reset'
        ];

        foreach ($permissions as $permission) {
            Permission::create([
                'name' => $permission,
                'display_name' => ucfirst(str_replace('.', ' ', $permission)),
                'module' => explode('.', $permission)[0]
            ]);
        }

        // 建立管理員角色
        $adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理員'
        ]);

        $adminRole->permissions()->attach(Permission::all());

        // 建立管理員使用者
        $this->adminUser = User::create([
            'username' => 'admin',
            'name' => '系統管理員',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'is_active' => true
        ]);

        $this->adminUser->roles()->attach($adminRole);

        // 建立大量測試資料以測試效能
        $this->createLargeDataset();
    }

    /**
     * 建立大量測試資料
     */
    private function createLargeDataset(): void
    {
        // 建立 100 個測試使用者
        for ($i = 1; $i <= 100; $i++) {
            User::create([
                'username' => "testuser{$i}",
                'name' => "測試使用者 {$i}",
                'email' => "testuser{$i}@example.com",
                'password' => bcrypt('password123'),
                'is_active' => $i % 2 === 0 // 一半啟用，一半停用
            ]);
        }
    }

    /**
     * 定義效能基準線
     */
    private function definePerformanceBaselines(): void
    {
        $this->performanceBaselines = [
            'UserList' => [
                'max_reset_time' => 500, // 毫秒
                'max_memory_usage' => 10, // MB
                'max_query_count' => 5,
                'max_render_time' => 200 // 毫秒
            ],
            'ActivityExport' => [
                'max_reset_time' => 300,
                'max_memory_usage' => 8,
                'max_query_count' => 3,
                'max_render_time' => 150
            ],
            'PermissionAuditLog' => [
                'max_reset_time' => 400,
                'max_memory_usage' => 12,
                'max_query_count' => 6,
                'max_render_time' => 250
            ],
            'SettingsList' => [
                'max_reset_time' => 200,
                'max_memory_usage' => 5,
                'max_query_count' => 2,
                'max_render_time' => 100
            ],
            'NotificationList' => [
                'max_reset_time' => 350,
                'max_memory_usage' => 7,
                'max_query_count' => 4,
                'max_render_time' => 180
            ]
        ];
    }

    /**
     * 測試 UserList 元件效能
     * 
     * @test
     */
    public function test_user_list_performance()
    {
        $this->actingAs($this->adminUser);
        
        $metrics = $this->measureComponentPerformance(
            \App\Livewire\Admin\Users\UserList::class,
            'UserList',
            function ($component) {
                // 設定複雜的篩選條件
                $component->set('search', 'test user search term')
                          ->set('statusFilter', 'active')
                          ->set('roleFilter', '1')
                          ->set('sortBy', 'created_at')
                          ->set('sortDirection', 'desc');

                // 執行重置
                $component->call('resetFilters');
            }
        );

        $this->performanceResults['UserList'] = $metrics;
        $this->assertPerformanceWithinLimits('UserList', $metrics);
    }

    /**
     * 測試 ActivityExport 元件效能
     * 
     * @test
     */
    public function test_activity_export_performance()
    {
        $this->actingAs($this->adminUser);
        
        $metrics = $this->measureComponentPerformance(
            \App\Livewire\Admin\Activities\ActivityExport::class,
            'ActivityExport',
            function ($component) {
                // 設定匯出參數
                $component->set('startDate', '2024-01-01')
                          ->set('endDate', '2024-12-31')
                          ->set('userFilter', $this->adminUser->id)
                          ->set('actionFilter', 'created');

                // 執行重置
                $component->call('resetFilters');
            }
        );

        $this->performanceResults['ActivityExport'] = $metrics;
        $this->assertPerformanceWithinLimits('ActivityExport', $metrics);
    }

    /**
     * 測試 PermissionAuditLog 元件效能
     * 
     * @test
     */
    public function test_permission_audit_log_performance()
    {
        $this->actingAs($this->adminUser);
        
        $metrics = $this->measureComponentPerformance(
            \App\Livewire\Admin\Permissions\PermissionAuditLog::class,
            'PermissionAuditLog',
            function ($component) {
                // 設定稽核篩選條件
                $component->set('search', 'permission audit test')
                          ->set('userFilter', $this->adminUser->id)
                          ->set('actionFilter', 'granted')
                          ->set('dateRange', '30d');

                // 執行重置
                $component->call('resetFilters');
            }
        );

        $this->performanceResults['PermissionAuditLog'] = $metrics;
        $this->assertPerformanceWithinLimits('PermissionAuditLog', $metrics);
    }

    /**
     * 測試 SettingsList 元件效能
     * 
     * @test
     */
    public function test_settings_list_performance()
    {
        $this->actingAs($this->adminUser);
        
        $metrics = $this->measureComponentPerformance(
            \App\Livewire\Admin\Settings\SettingsList::class,
            'SettingsList',
            function ($component) {
                // 設定搜尋條件
                $component->set('search', 'app settings search')
                          ->set('categoryFilter', 'system')
                          ->set('sortBy', 'key')
                          ->set('sortDirection', 'asc');

                // 執行清除
                $component->call('clearFilters');
            }
        );

        $this->performanceResults['SettingsList'] = $metrics;
        $this->assertPerformanceWithinLimits('SettingsList', $metrics);
    }

    /**
     * 測試 NotificationList 元件效能
     * 
     * @test
     */
    public function test_notification_list_performance()
    {
        $this->actingAs($this->adminUser);
        
        $metrics = $this->measureComponentPerformance(
            \App\Livewire\Admin\Activities\NotificationList::class,
            'NotificationList',
            function ($component) {
                // 設定通知篩選條件
                $component->set('search', 'notification test search')
                          ->set('statusFilter', 'unread')
                          ->set('typeFilter', 'system')
                          ->set('priorityFilter', 'high');

                // 執行清除
                $component->call('clearFilters');
            }
        );

        $this->performanceResults['NotificationList'] = $metrics;
        $this->assertPerformanceWithinLimits('NotificationList', $metrics);
    }

    /**
     * 測量元件效能指標
     */
    private function measureComponentPerformance(string $componentClass, string $componentName, callable $testAction): array
    {
        // 記錄初始狀態
        $initialMemory = memory_get_usage(true);
        $initialTime = microtime(true);
        
        // 啟用查詢日誌
        \DB::enableQueryLog();
        
        try {
            // 建立元件實例
            $component = Livewire::test($componentClass);
            
            // 測量渲染時間
            $renderStartTime = microtime(true);
            $component->assertStatus(200);
            $renderEndTime = microtime(true);
            $renderTime = ($renderEndTime - $renderStartTime) * 1000;
            
            // 執行測試動作
            $actionStartTime = microtime(true);
            $testAction($component);
            $actionEndTime = microtime(true);
            $actionTime = ($actionEndTime - $actionStartTime) * 1000;
            
            // 記錄結束狀態
            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);
            
            // 獲取查詢統計
            $queries = \DB::getQueryLog();
            \DB::disableQueryLog();
            
            return [
                'component' => $componentName,
                'total_time' => round(($endTime - $initialTime) * 1000, 2), // 毫秒
                'render_time' => round($renderTime, 2), // 毫秒
                'reset_time' => round($actionTime, 2), // 毫秒
                'memory_usage' => round(($endMemory - $initialMemory) / 1024 / 1024, 2), // MB
                'peak_memory' => round(memory_get_peak_usage(true) / 1024 / 1024, 2), // MB
                'query_count' => count($queries),
                'query_time' => $this->calculateQueryTime($queries),
                'queries' => $this->analyzeQueries($queries),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (\Exception $e) {
            \DB::disableQueryLog();
            
            return [
                'component' => $componentName,
                'error' => $e->getMessage(),
                'total_time' => 0,
                'render_time' => 0,
                'reset_time' => 0,
                'memory_usage' => 0,
                'query_count' => 0,
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
    }

    /**
     * 計算查詢總時間
     */
    private function calculateQueryTime(array $queries): float
    {
        return array_sum(array_column($queries, 'time'));
    }

    /**
     * 分析查詢
     */
    private function analyzeQueries(array $queries): array
    {
        $analysis = [
            'select_queries' => 0,
            'insert_queries' => 0,
            'update_queries' => 0,
            'delete_queries' => 0,
            'slow_queries' => 0,
            'duplicate_queries' => 0
        ];

        $queryHashes = [];
        
        foreach ($queries as $query) {
            $sql = strtolower(trim($query['query']));
            
            // 分類查詢類型
            if (strpos($sql, 'select') === 0) {
                $analysis['select_queries']++;
            } elseif (strpos($sql, 'insert') === 0) {
                $analysis['insert_queries']++;
            } elseif (strpos($sql, 'update') === 0) {
                $analysis['update_queries']++;
            } elseif (strpos($sql, 'delete') === 0) {
                $analysis['delete_queries']++;
            }
            
            // 檢查慢查詢（超過 100ms）
            if ($query['time'] > 100) {
                $analysis['slow_queries']++;
            }
            
            // 檢查重複查詢
            $queryHash = md5($sql);
            if (isset($queryHashes[$queryHash])) {
                $analysis['duplicate_queries']++;
            } else {
                $queryHashes[$queryHash] = true;
            }
        }
        
        return $analysis;
    }

    /**
     * 驗證效能是否在限制範圍內
     */
    private function assertPerformanceWithinLimits(string $componentName, array $metrics): void
    {
        if (isset($metrics['error'])) {
            $this->fail("元件 {$componentName} 效能測試失敗: " . $metrics['error']);
        }

        $baselines = $this->performanceBaselines[$componentName];

        // 檢查重置時間
        $this->assertLessThan(
            $baselines['max_reset_time'],
            $metrics['reset_time'],
            "元件 {$componentName} 重置時間 ({$metrics['reset_time']}ms) 超過基準線 ({$baselines['max_reset_time']}ms)"
        );

        // 檢查記憶體使用
        $this->assertLessThan(
            $baselines['max_memory_usage'],
            $metrics['memory_usage'],
            "元件 {$componentName} 記憶體使用 ({$metrics['memory_usage']}MB) 超過基準線 ({$baselines['max_memory_usage']}MB)"
        );

        // 檢查查詢數量
        $this->assertLessThan(
            $baselines['max_query_count'],
            $metrics['query_count'],
            "元件 {$componentName} 查詢數量 ({$metrics['query_count']}) 超過基準線 ({$baselines['max_query_count']})"
        );

        // 檢查渲染時間
        $this->assertLessThan(
            $baselines['max_render_time'],
            $metrics['render_time'],
            "元件 {$componentName} 渲染時間 ({$metrics['render_time']}ms) 超過基準線 ({$baselines['max_render_time']}ms)"
        );
    }

    /**
     * 測試併發處理效能
     * 
     * @test
     */
    public function test_concurrent_reset_performance()
    {
        $this->actingAs($this->adminUser);
        
        $concurrentResults = [];
        $componentClasses = [
            'UserList' => \App\Livewire\Admin\Users\UserList::class,
            'ActivityExport' => \App\Livewire\Admin\Activities\ActivityExport::class,
            'SettingsList' => \App\Livewire\Admin\Settings\SettingsList::class
        ];

        $startTime = microtime(true);
        $initialMemory = memory_get_usage(true);

        // 模擬併發操作
        foreach ($componentClasses as $name => $class) {
            $component = Livewire::test($class);
            
            // 設定測試資料
            $component->set('search', "concurrent test {$name}");
            
            // 執行重置
            $resetStartTime = microtime(true);
            $component->call(in_array($name, ['UserList', 'ActivityExport']) ? 'resetFilters' : 'clearFilters');
            $resetEndTime = microtime(true);
            
            $concurrentResults[$name] = [
                'reset_time' => round(($resetEndTime - $resetStartTime) * 1000, 2)
            ];
        }

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $totalConcurrentTime = round(($endTime - $startTime) * 1000, 2);
        $totalMemoryUsage = round(($endMemory - $initialMemory) / 1024 / 1024, 2);

        // 驗證併發效能
        $this->assertLessThan(2000, $totalConcurrentTime, "併發重置總時間超過 2 秒");
        $this->assertLessThan(50, $totalMemoryUsage, "併發重置記憶體使用超過 50MB");

        // 記錄併發測試結果
        $this->performanceResults['concurrent'] = [
            'total_time' => $totalConcurrentTime,
            'memory_usage' => $totalMemoryUsage,
            'components' => $concurrentResults
        ];
    }

    /**
     * 測試記憶體洩漏
     * 
     * @test
     */
    public function test_memory_leak_detection()
    {
        $this->actingAs($this->adminUser);
        
        $memorySnapshots = [];
        $component = Livewire::test(\App\Livewire\Admin\Users\UserList::class);

        // 執行多次重置操作，檢查記憶體是否持續增長
        for ($i = 0; $i < 10; $i++) {
            $memorySnapshots[] = memory_get_usage(true);
            
            // 設定資料
            $component->set('search', "memory test iteration {$i}")
                      ->set('statusFilter', $i % 2 === 0 ? 'active' : 'inactive');
            
            // 執行重置
            $component->call('resetFilters');
            
            // 強制垃圾回收
            gc_collect_cycles();
        }

        // 分析記憶體使用趨勢
        $memoryGrowth = end($memorySnapshots) - $memorySnapshots[0];
        $memoryGrowthMB = round($memoryGrowth / 1024 / 1024, 2);

        // 記憶體增長不應超過 5MB
        $this->assertLessThan(5, $memoryGrowthMB, 
            "檢測到可能的記憶體洩漏，記憶體增長: {$memoryGrowthMB}MB");

        $this->performanceResults['memory_leak_test'] = [
            'initial_memory' => round($memorySnapshots[0] / 1024 / 1024, 2),
            'final_memory' => round(end($memorySnapshots) / 1024 / 1024, 2),
            'memory_growth' => $memoryGrowthMB,
            'iterations' => 10
        ];
    }

    /**
     * 生成效能測試報告
     */
    protected function tearDown(): void
    {
        if (!empty($this->performanceResults)) {
            $this->generatePerformanceReport();
        }
        
        parent::tearDown();
    }

    /**
     * 生成效能測試報告
     */
    private function generatePerformanceReport(): void
    {
        $report = "# Livewire 表單重置效能測試報告\n\n";
        $report .= "**測試時間**: " . date('Y-m-d H:i:s') . "\n\n";

        // 效能統計表格
        $report .= "## 效能測試結果\n\n";
        $report .= "| 元件名稱 | 重置時間(ms) | 渲染時間(ms) | 記憶體使用(MB) | 查詢數量 | 狀態 |\n";
        $report .= "|---------|-------------|-------------|-------------|---------|------|\n";

        foreach ($this->performanceResults as $componentName => $metrics) {
            if (in_array($componentName, ['concurrent', 'memory_leak_test'])) {
                continue;
            }

            $status = isset($metrics['error']) ? '❌ 失敗' : '✅ 通過';
            $resetTime = $metrics['reset_time'] ?? 'N/A';
            $renderTime = $metrics['render_time'] ?? 'N/A';
            $memoryUsage = $metrics['memory_usage'] ?? 'N/A';
            $queryCount = $metrics['query_count'] ?? 'N/A';

            $report .= "| {$componentName} | {$resetTime} | {$renderTime} | {$memoryUsage} | {$queryCount} | {$status} |\n";
        }

        // 效能分析
        $report .= "\n## 效能分析\n\n";
        
        $avgResetTime = $this->calculateAverageMetric('reset_time');
        $avgMemoryUsage = $this->calculateAverageMetric('memory_usage');
        $avgQueryCount = $this->calculateAverageMetric('query_count');

        $report .= "- **平均重置時間**: " . round($avgResetTime, 2) . "ms\n";
        $report .= "- **平均記憶體使用**: " . round($avgMemoryUsage, 2) . "MB\n";
        $report .= "- **平均查詢數量**: " . round($avgQueryCount, 2) . "\n\n";

        // 併發測試結果
        if (isset($this->performanceResults['concurrent'])) {
            $concurrent = $this->performanceResults['concurrent'];
            $report .= "## 併發測試結果\n\n";
            $report .= "- **總執行時間**: {$concurrent['total_time']}ms\n";
            $report .= "- **記憶體使用**: {$concurrent['memory_usage']}MB\n\n";
        }

        // 記憶體洩漏測試結果
        if (isset($this->performanceResults['memory_leak_test'])) {
            $memoryTest = $this->performanceResults['memory_leak_test'];
            $report .= "## 記憶體洩漏測試結果\n\n";
            $report .= "- **初始記憶體**: {$memoryTest['initial_memory']}MB\n";
            $report .= "- **最終記憶體**: {$memoryTest['final_memory']}MB\n";
            $report .= "- **記憶體增長**: {$memoryTest['memory_growth']}MB\n";
            $report .= "- **測試迭代**: {$memoryTest['iterations']} 次\n\n";
        }

        // 效能建議
        $report .= "## 效能建議\n\n";
        $report .= $this->generatePerformanceRecommendations();

        // 儲存報告
        $reportPath = storage_path('logs/livewire-performance-test-report.md');
        file_put_contents($reportPath, $report);
        
        echo "效能測試報告已儲存至: {$reportPath}\n";
    }

    /**
     * 計算平均指標
     */
    private function calculateAverageMetric(string $metric): float
    {
        $values = [];
        
        foreach ($this->performanceResults as $componentName => $metrics) {
            if (in_array($componentName, ['concurrent', 'memory_leak_test'])) {
                continue;
            }
            
            if (isset($metrics[$metric]) && is_numeric($metrics[$metric])) {
                $values[] = $metrics[$metric];
            }
        }
        
        return empty($values) ? 0 : array_sum($values) / count($values);
    }

    /**
     * 生成效能建議
     */
    private function generatePerformanceRecommendations(): string
    {
        $recommendations = "";
        
        $avgResetTime = $this->calculateAverageMetric('reset_time');
        $avgMemoryUsage = $this->calculateAverageMetric('memory_usage');
        $avgQueryCount = $this->calculateAverageMetric('query_count');

        if ($avgResetTime > 300) {
            $recommendations .= "⚠️ **重置時間偏高**: 平均重置時間為 " . round($avgResetTime, 2) . "ms，建議優化 DOM 操作和事件處理。\n\n";
        }

        if ($avgMemoryUsage > 10) {
            $recommendations .= "⚠️ **記憶體使用偏高**: 平均記憶體使用為 " . round($avgMemoryUsage, 2) . "MB，建議檢查是否有記憶體洩漏。\n\n";
        }

        if ($avgQueryCount > 5) {
            $recommendations .= "⚠️ **查詢數量偏多**: 平均查詢數量為 " . round($avgQueryCount, 2) . "，建議優化資料庫查詢和使用快取。\n\n";
        }

        if (empty($recommendations)) {
            $recommendations = "✅ 所有效能指標都在可接受範圍內，表單重置功能效能良好。\n\n";
        }

        $recommendations .= "**一般建議**:\n";
        $recommendations .= "- 定期監控效能指標\n";
        $recommendations .= "- 使用快取減少資料庫查詢\n";
        $recommendations .= "- 優化 Livewire 元件的資料綁定\n";
        $recommendations .= "- 考慮使用延遲載入減少初始載入時間\n";

        return $recommendations;
    }
}