<?php

/**
 * 活動記錄整合測試執行器
 * 
 * 執行所有活動記錄相關的整合測試，包含：
 * - 基本功能整合測試
 * - 瀏覽器自動化測試
 * - 效能和負載測試
 * - MCP 整合測試
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ActivityLogIntegrationTestRunner
{
    protected array $testResults = [];
    protected int $totalTests = 0;
    protected int $passedTests = 0;
    protected int $failedTests = 0;
    protected float $startTime;

    public function __construct()
    {
        $this->startTime = microtime(true);
    }

    /**
     * 執行所有整合測試
     */
    public function runAllTests(): void
    {
        $this->printHeader();
        
        // 1. 準備測試環境
        $this->prepareTestEnvironment();
        
        // 2. 執行基本功能整合測試
        $this->runBasicIntegrationTests();
        
        // 3. 執行瀏覽器自動化測試
        $this->runBrowserAutomationTests();
        
        // 4. 執行效能測試
        $this->runPerformanceTests();
        
        // 5. 執行 MCP 整合測試
        $this->runMcpIntegrationTests();
        
        // 6. 清理測試環境
        $this->cleanupTestEnvironment();
        
        // 7. 生成測試報告
        $this->generateTestReport();
    }

    /**
     * 準備測試環境
     */
    protected function prepareTestEnvironment(): void
    {
        $this->printSection('準備測試環境');
        
        try {
            // 清除快取
            Cache::flush();
            
            // 重建資料庫
            $this->executeCommand('php artisan migrate:fresh --seed --env=testing');
            
            // 確保測試資料完整
            $this->executeCommand('php artisan db:seed --class=TestDataSeeder --env=testing');
            
            $this->printSuccess('測試環境準備完成');
        } catch (Exception $e) {
            $this->printError('測試環境準備失敗: ' . $e->getMessage());
            exit(1);
        }
    }

    /**
     * 執行基本功能整合測試
     */
    protected function runBasicIntegrationTests(): void
    {
        $this->printSection('執行基本功能整合測試');
        
        $tests = [
            'test_complete_activity_logging_flow' => '完整活動記錄流程測試',
            'test_security_event_detection_and_alerts' => '安全事件檢測和警報測試',
            'test_access_control_for_different_permission_users' => '不同權限使用者存取控制測試',
            'test_activity_data_integrity' => '活動記錄資料完整性測試',
            'test_activity_export_and_backup_functionality' => '活動記錄匯出和備份功能測試',
            'test_activity_retention_policy' => '活動記錄保留政策測試'
        ];
        
        foreach ($tests as $testMethod => $testName) {
            $this->runSingleTest(
                'Tests\\Integration\\ActivityLogIntegrationTest',
                $testMethod,
                $testName
            );
        }
    }

    /**
     * 執行瀏覽器自動化測試
     */
    protected function runBrowserAutomationTests(): void
    {
        $this->printSection('執行瀏覽器自動化測試');
        
        $tests = [
            'test_activity_list_page_complete_flow' => '活動記錄列表頁面完整流程測試',
            'test_real_time_monitoring' => '即時監控功能測試',
            'test_permission_based_ui_access' => '基於權限的 UI 存取測試',
            'test_activity_statistics_and_charts' => '活動統計和圖表測試',
            'test_mobile_responsive_design' => '行動裝置響應式設計測試',
            'test_performance_and_loading_times' => '效能和載入時間測試'
        ];
        
        foreach ($tests as $testMethod => $testName) {
            $this->runSingleTest(
                'Tests\\Integration\\ActivityLogBrowserTest',
                $testMethod,
                $testName
            );
        }
    }

    /**
     * 執行效能測試
     */
    protected function runPerformanceTests(): void
    {
        $this->printSection('執行效能和負載測試');
        
        $tests = [
            'test_bulk_activity_logging_performance' => '大量活動記錄寫入效能測試',
            'test_async_activity_logging_performance' => '非同步記錄效能測試',
            'test_large_dataset_query_performance' => '大量資料查詢效能測試',
            'test_search_functionality_performance' => '搜尋功能效能測試',
            'test_statistics_query_performance' => '統計查詢效能測試',
            'test_security_analysis_performance' => '安全分析效能測試',
            'test_cache_performance' => '快取效能測試',
            'test_memory_usage_performance' => '記憶體使用效能測試',
            'test_concurrent_access_performance' => '並發存取效能測試'
        ];
        
        foreach ($tests as $testMethod => $testName) {
            $this->runSingleTest(
                'Tests\\Integration\\ActivityLogPerformanceTest',
                $testMethod,
                $testName
            );
        }
    }

    /**
     * 執行 MCP 整合測試
     */
    protected function runMcpIntegrationTests(): void
    {
        $this->printSection('執行 MCP 整合測試');
        
        // 檢查 MCP 服務是否可用
        if (!$this->checkMcpServices()) {
            $this->printWarning('MCP 服務不可用，跳過 MCP 整合測試');
            return;
        }
        
        $tests = [
            'test_complete_activity_log_flow_with_mcp' => '使用 MCP 的完整活動記錄流程測試',
            'test_security_event_detection_with_mcp' => '使用 MCP 的安全事件檢測測試',
            'test_permission_control_with_mcp' => '使用 MCP 的權限控制測試',
            'test_performance_requirements_with_mcp' => '使用 MCP 的效能要求測試',
            'test_data_integrity_and_security_with_mcp' => '使用 MCP 的資料完整性和安全性測試'
        ];
        
        foreach ($tests as $testMethod => $testName) {
            $this->runSingleTest(
                'Tests\\Integration\\ActivityLogMcpIntegrationTest',
                $testMethod,
                $testName
            );
        }
    }

    /**
     * 執行單一測試
     */
    protected function runSingleTest(string $testClass, string $testMethod, string $testName): void
    {
        $this->totalTests++;
        
        try {
            $startTime = microtime(true);
            
            // 執行測試
            $command = "php artisan test {$testClass}::{$testMethod} --env=testing";
            $output = $this->executeCommand($command);
            
            $endTime = microtime(true);
            $duration = $endTime - $startTime;
            
            // 檢查測試結果
            if (strpos($output, 'FAILED') !== false || strpos($output, 'ERROR') !== false) {
                $this->failedTests++;
                $this->testResults[] = [
                    'name' => $testName,
                    'status' => 'FAILED',
                    'duration' => $duration,
                    'output' => $output
                ];
                $this->printError("✗ {$testName} - 失敗 (" . number_format($duration, 2) . "s)");
            } else {
                $this->passedTests++;
                $this->testResults[] = [
                    'name' => $testName,
                    'status' => 'PASSED',
                    'duration' => $duration,
                    'output' => $output
                ];
                $this->printSuccess("✓ {$testName} - 通過 (" . number_format($duration, 2) . "s)");
            }
            
        } catch (Exception $e) {
            $this->failedTests++;
            $this->testResults[] = [
                'name' => $testName,
                'status' => 'ERROR',
                'duration' => 0,
                'output' => $e->getMessage()
            ];
            $this->printError("✗ {$testName} - 錯誤: " . $e->getMessage());
        }
    }

    /**
     * 檢查 MCP 服務是否可用
     */
    protected function checkMcpServices(): bool
    {
        try {
            // 檢查 Playwright MCP
            $playwrightCheck = $this->executeCommand('curl -s http://localhost:3000/health || echo "FAILED"');
            
            // 檢查 MySQL MCP
            $mysqlCheck = $this->executeCommand('mysql -h localhost -u root -e "SELECT 1" 2>/dev/null || echo "FAILED"');
            
            return (strpos($playwrightCheck, 'FAILED') === false) && 
                   (strpos($mysqlCheck, 'FAILED') === false);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 清理測試環境
     */
    protected function cleanupTestEnvironment(): void
    {
        $this->printSection('清理測試環境');
        
        try {
            // 清除測試資料
            $this->executeCommand('php artisan migrate:fresh --env=testing');
            
            // 清除快取
            Cache::flush();
            
            // 清除日誌檔案
            $this->executeCommand('rm -f storage/logs/laravel-*.log');
            
            $this->printSuccess('測試環境清理完成');
        } catch (Exception $e) {
            $this->printWarning('測試環境清理失敗: ' . $e->getMessage());
        }
    }

    /**
     * 生成測試報告
     */
    protected function generateTestReport(): void
    {
        $totalTime = microtime(true) - $this->startTime;
        
        $this->printSection('測試報告');
        
        echo "\n";
        echo "總測試數量: {$this->totalTests}\n";
        echo "通過測試: {$this->passedTests}\n";
        echo "失敗測試: {$this->failedTests}\n";
        echo "成功率: " . number_format(($this->passedTests / $this->totalTests) * 100, 1) . "%\n";
        echo "總執行時間: " . number_format($totalTime, 2) . " 秒\n";
        
        // 生成詳細報告檔案
        $this->generateDetailedReport();
        
        // 顯示結果摘要
        if ($this->failedTests > 0) {
            $this->printError("\n測試完成，但有 {$this->failedTests} 個測試失敗");
            $this->printFailedTests();
            exit(1);
        } else {
            $this->printSuccess("\n所有測試都通過！");
            exit(0);
        }
    }

    /**
     * 生成詳細報告檔案
     */
    protected function generateDetailedReport(): void
    {
        $reportPath = storage_path('logs/activity-log-integration-test-report.json');
        
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'summary' => [
                'total_tests' => $this->totalTests,
                'passed_tests' => $this->passedTests,
                'failed_tests' => $this->failedTests,
                'success_rate' => ($this->passedTests / $this->totalTests) * 100,
                'total_duration' => microtime(true) - $this->startTime
            ],
            'test_results' => $this->testResults,
            'environment' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'database' => config('database.default'),
                'cache_driver' => config('cache.default'),
                'queue_driver' => config('queue.default')
            ]
        ];
        
        file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        echo "詳細報告已儲存至: {$reportPath}\n";
    }

    /**
     * 顯示失敗的測試
     */
    protected function printFailedTests(): void
    {
        $failedTests = array_filter($this->testResults, fn($test) => $test['status'] !== 'PASSED');
        
        if (empty($failedTests)) {
            return;
        }
        
        echo "\n失敗的測試詳情:\n";
        echo str_repeat('=', 80) . "\n";
        
        foreach ($failedTests as $test) {
            echo "\n測試名稱: {$test['name']}\n";
            echo "狀態: {$test['status']}\n";
            echo "執行時間: " . number_format($test['duration'], 2) . " 秒\n";
            echo "輸出:\n";
            echo str_repeat('-', 40) . "\n";
            echo $test['output'] . "\n";
            echo str_repeat('-', 40) . "\n";
        }
    }

    /**
     * 執行命令
     */
    protected function executeCommand(string $command): string
    {
        $output = shell_exec($command . ' 2>&1');
        
        if ($output === null) {
            throw new Exception("命令執行失敗: {$command}");
        }
        
        return $output;
    }

    /**
     * 列印標題
     */
    protected function printHeader(): void
    {
        echo "\n";
        echo str_repeat('=', 80) . "\n";
        echo "                    活動記錄功能整合測試                    \n";
        echo str_repeat('=', 80) . "\n";
        echo "開始時間: " . date('Y-m-d H:i:s') . "\n";
        echo str_repeat('=', 80) . "\n";
    }

    /**
     * 列印區段標題
     */
    protected function printSection(string $title): void
    {
        echo "\n";
        echo str_repeat('-', 60) . "\n";
        echo $title . "\n";
        echo str_repeat('-', 60) . "\n";
    }

    /**
     * 列印成功訊息
     */
    protected function printSuccess(string $message): void
    {
        echo "\033[32m{$message}\033[0m\n";
    }

    /**
     * 列印錯誤訊息
     */
    protected function printError(string $message): void
    {
        echo "\033[31m{$message}\033[0m\n";
    }

    /**
     * 列印警告訊息
     */
    protected function printWarning(string $message): void
    {
        echo "\033[33m{$message}\033[0m\n";
    }
}

// 執行測試
if (php_sapi_name() === 'cli') {
    $runner = new ActivityLogIntegrationTestRunner();
    $runner->runAllTests();
} else {
    echo "此腳本只能在命令列中執行\n";
    exit(1);
}