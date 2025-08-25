<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Activity;
use App\Services\ActivityLogger;
use App\Services\SecurityAnalyzer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

/**
 * 活動記錄效能和負載測試
 * 
 * 測試系統在高負載情況下的效能表現
 */
class ActivityLogPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $testUser;
    protected ActivityLogger $activityLogger;
    protected SecurityAnalyzer $securityAnalyzer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testUser = User::factory()->create([
            'username' => 'performance_test_user',
            'name' => '效能測試使用者'
        ]);
        
        $this->activityLogger = app(ActivityLogger::class);
        $this->securityAnalyzer = app(SecurityAnalyzer::class);
    }

    /**
     * 測試大量活動記錄的寫入效能
     * 
     * @test
     */
    public function test_bulk_activity_logging_performance()
    {
        $recordCount = 10000;
        $batchSize = 1000;
        
        // 測試批量同步寫入
        $startTime = microtime(true);
        
        for ($batch = 0; $batch < $recordCount / $batchSize; $batch++) {
            $activities = [];
            
            for ($i = 0; $i < $batchSize; $i++) {
                $activities[] = [
                    'type' => 'performance_test',
                    'description' => "效能測試活動 " . ($batch * $batchSize + $i),
                    'data' => [
                        'user_id' => $this->testUser->id,
                        'properties' => [
                            'batch' => $batch,
                            'index' => $i,
                            'timestamp' => now()->toISOString()
                        ]
                    ]
                ];
            }
            
            $this->activityLogger->logBatch($activities);
        }
        
        $syncTime = microtime(true) - $startTime;
        
        // 驗證記錄數量
        $actualCount = Activity::where('type', 'performance_test')->count();
        $this->assertEquals($recordCount, $actualCount);
        
        // 效能要求：10,000 筆記錄應在 30 秒內完成
        $this->assertLessThan(30.0, $syncTime, 
            "批量寫入 {$recordCount} 筆記錄耗時 {$syncTime} 秒，超過 30 秒限制");
        
        // 計算每秒處理量
        $throughput = $recordCount / $syncTime;
        $this->assertGreaterThan(300, $throughput, 
            "寫入效能每秒 {$throughput} 筆，低於 300 筆/秒的要求");
        
        echo "\n批量寫入效能測試結果：\n";
        echo "- 記錄數量：{$recordCount} 筆\n";
        echo "- 總耗時：" . number_format($syncTime, 2) . " 秒\n";
        echo "- 處理量：" . number_format($throughput, 0) . " 筆/秒\n";
    }

    /**
     * 測試非同步記錄效能
     * 
     * @test
     */
    public function test_async_activity_logging_performance()
    {
        Queue::fake();
        
        $recordCount = 5000;
        $startTime = microtime(true);
        
        for ($i = 0; $i < $recordCount; $i++) {
            $this->activityLogger->logAsync(
                'async_performance_test',
                "非同步效能測試 {$i}",
                [
                    'user_id' => $this->testUser->id,
                    'properties' => ['index' => $i]
                ]
            );
        }
        
        $asyncTime = microtime(true) - $startTime;
        
        // 非同步記錄應該非常快速
        $this->assertLessThan(5.0, $asyncTime, 
            "非同步記錄 {$recordCount} 筆耗時 {$asyncTime} 秒，超過 5 秒限制");
        
        $throughput = $recordCount / $asyncTime;
        $this->assertGreaterThan(1000, $throughput, 
            "非同步記錄效能每秒 {$throughput} 筆，低於 1000 筆/秒的要求");
        
        echo "\n非同步記錄效能測試結果：\n";
        echo "- 記錄數量：{$recordCount} 筆\n";
        echo "- 總耗時：" . number_format($asyncTime, 3) . " 秒\n";
        echo "- 處理量：" . number_format($throughput, 0) . " 筆/秒\n";
    }

    /**
     * 測試大量資料查詢效能
     * 
     * @test
     */
    public function test_large_dataset_query_performance()
    {
        // 建立大量測試資料
        $this->createLargeDataset(50000);
        
        // 測試基本查詢效能
        $startTime = microtime(true);
        $results = Activity::where('user_id', $this->testUser->id)
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();
        $queryTime = microtime(true) - $startTime;
        
        $this->assertLessThan(1.0, $queryTime, 
            "基本查詢耗時 {$queryTime} 秒，超過 1 秒限制");
        $this->assertEquals(100, $results->count());
        
        // 測試分頁查詢效能
        $startTime = microtime(true);
        $paginatedResults = Activity::where('user_id', $this->testUser->id)
            ->orderBy('created_at', 'desc')
            ->paginate(50);
        $paginationTime = microtime(true) - $startTime;
        
        $this->assertLessThan(1.5, $paginationTime, 
            "分頁查詢耗時 {$paginationTime} 秒，超過 1.5 秒限制");
        
        // 測試複雜查詢效能
        $startTime = microtime(true);
        $complexResults = Activity::where('user_id', $this->testUser->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->whereIn('type', ['user_login', 'user_logout', 'user_created'])
            ->orderBy('created_at', 'desc')
            ->limit(200)
            ->get();
        $complexQueryTime = microtime(true) - $startTime;
        
        $this->assertLessThan(2.0, $complexQueryTime, 
            "複雜查詢耗時 {$complexQueryTime} 秒，超過 2 秒限制");
        
        echo "\n大量資料查詢效能測試結果：\n";
        echo "- 資料集大小：50,000 筆\n";
        echo "- 基本查詢：" . number_format($queryTime, 3) . " 秒\n";
        echo "- 分頁查詢：" . number_format($paginationTime, 3) . " 秒\n";
        echo "- 複雜查詢：" . number_format($complexQueryTime, 3) . " 秒\n";
    }

    /**
     * 測試搜尋功能效能
     * 
     * @test
     */
    public function test_search_functionality_performance()
    {
        // 建立包含不同關鍵字的測試資料
        $this->createSearchTestData(10000);
        
        $searchTerms = ['登入', '建立', '更新', '刪除', '權限'];
        
        foreach ($searchTerms as $term) {
            $startTime = microtime(true);
            
            $results = Activity::where('description', 'like', "%{$term}%")
                ->orWhere('type', 'like', "%{$term}%")
                ->limit(100)
                ->get();
            
            $searchTime = microtime(true) - $startTime;
            
            $this->assertLessThan(2.0, $searchTime, 
                "搜尋 '{$term}' 耗時 {$searchTime} 秒，超過 2 秒限制");
            
            echo "搜尋 '{$term}'：" . number_format($searchTime, 3) . " 秒，找到 {$results->count()} 筆\n";
        }
        
        // 測試全文搜尋效能
        $startTime = microtime(true);
        $fullTextResults = Activity::where(function($query) {
            $query->where('description', 'like', '%使用者%')
                  ->orWhere('properties->username', 'like', '%test%');
        })->limit(50)->get();
        $fullTextTime = microtime(true) - $startTime;
        
        $this->assertLessThan(3.0, $fullTextTime, 
            "全文搜尋耗時 {$fullTextTime} 秒，超過 3 秒限制");
    }

    /**
     * 測試統計查詢效能
     * 
     * @test
     */
    public function test_statistics_query_performance()
    {
        // 建立統計測試資料
        $this->createStatisticsTestData(20000);
        
        // 測試基本統計查詢
        $startTime = microtime(true);
        $basicStats = [
            'total_count' => Activity::count(),
            'today_count' => Activity::whereDate('created_at', today())->count(),
            'this_week_count' => Activity::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
            'user_count' => Activity::distinct('user_id')->count('user_id')
        ];
        $basicStatsTime = microtime(true) - $startTime;
        
        $this->assertLessThan(3.0, $basicStatsTime, 
            "基本統計查詢耗時 {$basicStatsTime} 秒，超過 3 秒限制");
        
        // 測試分組統計查詢
        $startTime = microtime(true);
        $groupStats = Activity::select('type', DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->orderBy('count', 'desc')
            ->get();
        $groupStatsTime = microtime(true) - $startTime;
        
        $this->assertLessThan(2.0, $groupStatsTime, 
            "分組統計查詢耗時 {$groupStatsTime} 秒，超過 2 秒限制");
        
        // 測試時間序列統計
        $startTime = microtime(true);
        $timeSeriesStats = Activity::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        $timeSeriesTime = microtime(true) - $startTime;
        
        $this->assertLessThan(2.5, $timeSeriesTime, 
            "時間序列統計耗時 {$timeSeriesTime} 秒，超過 2.5 秒限制");
        
        echo "\n統計查詢效能測試結果：\n";
        echo "- 基本統計：" . number_format($basicStatsTime, 3) . " 秒\n";
        echo "- 分組統計：" . number_format($groupStatsTime, 3) . " 秒\n";
        echo "- 時間序列：" . number_format($timeSeriesTime, 3) . " 秒\n";
    }

    /**
     * 測試安全分析效能
     * 
     * @test
     */
    public function test_security_analysis_performance()
    {
        // 建立包含安全事件的測試資料
        $this->createSecurityTestData(5000);
        
        // 測試風險分析效能
        $activities = Activity::where('created_at', '>=', now()->subHours(1))
            ->limit(1000)
            ->get();
        
        $startTime = microtime(true);
        foreach ($activities->take(100) as $activity) {
            $this->securityAnalyzer->analyzeActivity($activity);
        }
        $riskAnalysisTime = microtime(true) - $startTime;
        
        $this->assertLessThan(10.0, $riskAnalysisTime, 
            "100 筆活動風險分析耗時 {$riskAnalysisTime} 秒，超過 10 秒限制");
        
        // 測試異常檢測效能
        $startTime = microtime(true);
        $anomalies = $this->securityAnalyzer->detectAnomalies($activities);
        $anomalyDetectionTime = microtime(true) - $startTime;
        
        $this->assertLessThan(5.0, $anomalyDetectionTime, 
            "異常檢測耗時 {$anomalyDetectionTime} 秒，超過 5 秒限制");
        
        // 測試安全報告生成效能
        $startTime = microtime(true);
        $securityReport = $this->securityAnalyzer->generateSecurityReport('1d');
        $reportGenerationTime = microtime(true) - $startTime;
        
        $this->assertLessThan(8.0, $reportGenerationTime, 
            "安全報告生成耗時 {$reportGenerationTime} 秒，超過 8 秒限制");
        
        echo "\n安全分析效能測試結果：\n";
        echo "- 風險分析：" . number_format($riskAnalysisTime, 3) . " 秒 (100 筆)\n";
        echo "- 異常檢測：" . number_format($anomalyDetectionTime, 3) . " 秒\n";
        echo "- 報告生成：" . number_format($reportGenerationTime, 3) . " 秒\n";
    }

    /**
     * 測試快取效能
     * 
     * @test
     */
    public function test_cache_performance()
    {
        // 清除所有快取
        Cache::flush();
        
        // 建立測試資料
        Activity::factory()->count(1000)->create([
            'user_id' => $this->testUser->id
        ]);
        
        // 測試首次載入（無快取）
        $startTime = microtime(true);
        $recentActivities = $this->activityLogger->getRecentActivities(50);
        $firstLoadTime = microtime(true) - $startTime;
        
        // 測試快取載入
        $startTime = microtime(true);
        $cachedActivities = $this->activityLogger->getRecentActivities(50);
        $cachedLoadTime = microtime(true) - $startTime;
        
        // 快取載入應該明顯更快
        $this->assertLessThan($firstLoadTime * 0.1, $cachedLoadTime, 
            "快取載入時間應該少於首次載入時間的 10%");
        
        // 測試快取失效和重建
        $this->activityLogger->clearCache();
        
        $startTime = microtime(true);
        $rebuiltCache = $this->activityLogger->getRecentActivities(50);
        $rebuildTime = microtime(true) - $startTime;
        
        $this->assertLessThan(2.0, $rebuildTime, 
            "快取重建耗時 {$rebuildTime} 秒，超過 2 秒限制");
        
        echo "\n快取效能測試結果：\n";
        echo "- 首次載入：" . number_format($firstLoadTime, 4) . " 秒\n";
        echo "- 快取載入：" . number_format($cachedLoadTime, 4) . " 秒\n";
        echo "- 快取重建：" . number_format($rebuildTime, 4) . " 秒\n";
        echo "- 效能提升：" . number_format($firstLoadTime / $cachedLoadTime, 1) . " 倍\n";
    }

    /**
     * 測試記憶體使用效能
     * 
     * @test
     */
    public function test_memory_usage_performance()
    {
        $memoryBefore = memory_get_usage(true);
        
        // 載入大量資料
        $largeDataset = Activity::factory()->count(10000)->create([
            'user_id' => $this->testUser->id
        ]);
        
        $memoryAfterCreation = memory_get_usage(true);
        
        // 查詢大量資料
        $results = Activity::where('user_id', $this->testUser->id)
            ->limit(5000)
            ->get();
        
        $memoryAfterQuery = memory_get_usage(true);
        
        // 計算記憶體使用量
        $creationMemory = ($memoryAfterCreation - $memoryBefore) / 1024 / 1024; // MB
        $queryMemory = ($memoryAfterQuery - $memoryAfterCreation) / 1024 / 1024; // MB
        
        // 記憶體使用量應該在合理範圍內
        $this->assertLessThan(100, $creationMemory, 
            "建立 10,000 筆記錄使用 {$creationMemory} MB，超過 100 MB 限制");
        
        $this->assertLessThan(50, $queryMemory, 
            "查詢 5,000 筆記錄使用 {$queryMemory} MB，超過 50 MB 限制");
        
        // 測試記憶體釋放
        unset($results, $largeDataset);
        gc_collect_cycles();
        
        $memoryAfterCleanup = memory_get_usage(true);
        $cleanupMemory = ($memoryAfterQuery - $memoryAfterCleanup) / 1024 / 1024; // MB
        
        echo "\n記憶體使用效能測試結果：\n";
        echo "- 建立記錄：" . number_format($creationMemory, 2) . " MB\n";
        echo "- 查詢記錄：" . number_format($queryMemory, 2) . " MB\n";
        echo "- 釋放記憶體：" . number_format($cleanupMemory, 2) . " MB\n";
    }

    /**
     * 測試並發存取效能
     * 
     * @test
     */
    public function test_concurrent_access_performance()
    {
        // 模擬並發寫入
        $processes = 5;
        $recordsPerProcess = 1000;
        
        $startTime = microtime(true);
        
        // 在實際環境中，這裡會使用多進程或多執行緒
        // 這裡我們模擬並發行為
        for ($process = 0; $process < $processes; $process++) {
            for ($i = 0; $i < $recordsPerProcess; $i++) {
                $this->activityLogger->log(
                    'concurrent_test',
                    "並發測試 Process {$process} Record {$i}",
                    [
                        'user_id' => $this->testUser->id,
                        'process_id' => $process,
                        'record_id' => $i
                    ]
                );
            }
        }
        
        $concurrentTime = microtime(true) - $startTime;
        $totalRecords = $processes * $recordsPerProcess;
        
        // 驗證所有記錄都已建立
        $actualCount = Activity::where('type', 'concurrent_test')->count();
        $this->assertEquals($totalRecords, $actualCount);
        
        $throughput = $totalRecords / $concurrentTime;
        
        echo "\n並發存取效能測試結果：\n";
        echo "- 模擬進程數：{$processes}\n";
        echo "- 每進程記錄數：{$recordsPerProcess}\n";
        echo "- 總記錄數：{$totalRecords}\n";
        echo "- 總耗時：" . number_format($concurrentTime, 2) . " 秒\n";
        echo "- 處理量：" . number_format($throughput, 0) . " 筆/秒\n";
    }

    /**
     * 建立大量測試資料集
     */
    protected function createLargeDataset(int $count): void
    {
        $batchSize = 1000;
        $batches = ceil($count / $batchSize);
        
        for ($batch = 0; $batch < $batches; $batch++) {
            $currentBatchSize = min($batchSize, $count - ($batch * $batchSize));
            
            Activity::factory()->count($currentBatchSize)->create([
                'user_id' => $this->testUser->id,
                'created_at' => now()->subMinutes(rand(1, 10080)) // 過去一週
            ]);
        }
    }

    /**
     * 建立搜尋測試資料
     */
    protected function createSearchTestData(int $count): void
    {
        $keywords = ['登入', '建立', '更新', '刪除', '權限', '角色', '使用者', '系統'];
        $types = ['user_login', 'user_created', 'user_updated', 'user_deleted', 'role_assigned'];
        
        for ($i = 0; $i < $count; $i++) {
            $keyword = $keywords[array_rand($keywords)];
            $type = $types[array_rand($types)];
            
            Activity::factory()->create([
                'type' => $type,
                'description' => "測試{$keyword}操作 #{$i}",
                'user_id' => $this->testUser->id,
                'properties' => [
                    'keyword' => $keyword,
                    'index' => $i
                ]
            ]);
        }
    }

    /**
     * 建立統計測試資料
     */
    protected function createStatisticsTestData(int $count): void
    {
        $types = ['user_login', 'user_logout', 'user_created', 'user_updated', 'role_assigned'];
        $users = User::factory()->count(10)->create();
        
        for ($i = 0; $i < $count; $i++) {
            Activity::factory()->create([
                'type' => $types[array_rand($types)],
                'user_id' => $users->random()->id,
                'created_at' => now()->subDays(rand(0, 30))->setTime(rand(8, 18), rand(0, 59))
            ]);
        }
    }

    /**
     * 建立安全測試資料
     */
    protected function createSecurityTestData(int $count): void
    {
        $securityTypes = ['login_failed', 'permission_escalation', 'suspicious_activity'];
        $riskLevels = [1, 2, 3, 5, 7, 8, 9];
        
        for ($i = 0; $i < $count; $i++) {
            Activity::factory()->create([
                'type' => $securityTypes[array_rand($securityTypes)],
                'user_id' => rand(0, 1) ? $this->testUser->id : null,
                'risk_level' => $riskLevels[array_rand($riskLevels)],
                'ip_address' => '192.168.' . rand(1, 255) . '.' . rand(1, 255),
                'created_at' => now()->subHours(rand(1, 24))
            ]);
        }
    }
}