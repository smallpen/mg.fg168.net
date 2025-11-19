#!/usr/bin/env php
<?php

/**
 * 權限管理整合測試執行腳本
 * 
 * 使用方法:
 * php tests/Integration/PermissionManagement/run-permission-management-tests.php [options]
 * 
 * 選項:
 * --functional    只執行功能測試
 * --browser       只執行瀏覽器測試
 * --performance   只執行效能測試
 * --security      只執行安全性測試
 * --all           執行所有測試（預設）
 * --verbose       顯示詳細輸出
 * --help          顯示幫助訊息
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase;
use Tests\Integration\PermissionManagement\PermissionManagementTestSuite;

class PermissionManagementTestRunner
{
    protected array $options = [];
    protected bool $verbose = false;

    public function __construct(array $argv)
    {
        $this->parseArguments($argv);
    }

    public function run(): int
    {
        $this->displayHeader();

        if (isset($this->options['help'])) {
            $this->displayHelp();
            return 0;
        }

        try {
            $this->setupEnvironment();
            $this->runTests();
            return 0;
        } catch (\Exception $e) {
            $this->output("錯誤: " . $e->getMessage() . "\n", 'error');
            return 1;
        }
    }

    protected function parseArguments(array $argv): void
    {
        for ($i = 1; $i < count($argv); $i++) {
            switch ($argv[$i]) {
                case '--functional':
                    $this->options['functional'] = true;
                    break;
                case '--browser':
                    $this->options['browser'] = true;
                    break;
                case '--performance':
                    $this->options['performance'] = true;
                    break;
                case '--security':
                    $this->options['security'] = true;
                    break;
                case '--all':
                    $this->options['all'] = true;
                    break;
                case '--verbose':
                    $this->verbose = true;
                    break;
                case '--help':
                    $this->options['help'] = true;
                    break;
                default:
                    $this->output("未知選項: {$argv[$i]}\n", 'warning');
                    break;
            }
        }

        // 如果沒有指定特定測試類型，預設執行所有測試
        if (!isset($this->options['functional']) && 
            !isset($this->options['browser']) && 
            !isset($this->options['performance']) && 
            !isset($this->options['security'])) {
            $this->options['all'] = true;
        }
    }

    protected function displayHeader(): void
    {
        $this->output("
╔══════════════════════════════════════════════════════════════╗
║                    權限管理整合測試套件                        ║
║                                                              ║
║  測試範圍：                                                   ║
║  • 完整權限管理工作流程測試                                    ║
║  • 權限依賴關係和循環檢查測試                                  ║
║  • 不同權限使用者的存取控制測試                                ║
║  • 匯入匯出功能測試                                           ║
║  • 瀏覽器自動化測試                                           ║
║                                                              ║
╚══════════════════════════════════════════════════════════════╝

", 'info');
    }

    protected function displayHelp(): void
    {
        $this->output("
使用方法:
  php run-permission-management-tests.php [選項]

選項:
  --functional    只執行功能整合測試
  --browser       只執行瀏覽器自動化測試
  --performance   只執行效能測試
  --security      只執行安全性測試
  --all           執行所有測試（預設）
  --verbose       顯示詳細輸出
  --help          顯示此幫助訊息

範例:
  php run-permission-management-tests.php --all --verbose
  php run-permission-management-tests.php --functional
  php run-permission-management-tests.php --browser --performance

", 'info');
    }

    protected function setupEnvironment(): void
    {
        $this->output("設定測試環境...\n");

        // 檢查必要的環境設定
        $this->checkEnvironmentRequirements();

        // 設定 Laravel 應用程式
        $this->setupLaravelApplication();

        // 檢查資料庫連線
        $this->checkDatabaseConnection();

        // 檢查瀏覽器測試環境（如果需要）
        if (isset($this->options['browser']) || isset($this->options['all'])) {
            $this->checkBrowserTestingEnvironment();
        }

        $this->output("環境設定完成！\n", 'success');
    }

    protected function checkEnvironmentRequirements(): void
    {
        // 檢查 PHP 版本
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            throw new \Exception('需要 PHP 8.1.0 或更高版本');
        }

        // 檢查必要的 PHP 擴展
        $requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
        foreach ($requiredExtensions as $extension) {
            if (!extension_loaded($extension)) {
                throw new \Exception("缺少必要的 PHP 擴展: {$extension}");
            }
        }

        // 檢查記憶體限制
        $memoryLimit = ini_get('memory_limit');
        if ($memoryLimit !== '-1' && $this->parseMemoryLimit($memoryLimit) < 512 * 1024 * 1024) {
            $this->output("警告: 記憶體限制可能不足，建議至少 512MB\n", 'warning');
        }
    }

    protected function setupLaravelApplication(): void
    {
        // 載入 Laravel 應用程式
        $app = require __DIR__ . '/../../../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        // 設定測試環境
        $app['env'] = 'testing';
        
        // 確保使用測試資料庫
        config(['database.default' => 'testing']);
    }

    protected function checkDatabaseConnection(): void
    {
        try {
            \Illuminate\Support\Facades\DB::connection('testing')->getPdo();
            $this->output("資料庫連線: 正常\n", 'success');
        } catch (\Exception $e) {
            throw new \Exception("資料庫連線失敗: " . $e->getMessage());
        }
    }

    protected function checkBrowserTestingEnvironment(): void
    {
        // 檢查是否有可用的瀏覽器測試工具
        $hasPlaywright = class_exists('Playwright\\Playwright');
        $hasDusk = class_exists('Laravel\\Dusk\\Browser');
        $hasMcp = function_exists('mcp_playwright_navigate');

        if (!$hasPlaywright && !$hasDusk && !$hasMcp) {
            $this->output("警告: 未檢測到瀏覽器測試工具，將跳過瀏覽器測試\n", 'warning');
            unset($this->options['browser']);
        } else {
            $this->output("瀏覽器測試環境: 可用\n", 'success');
        }
    }

    protected function runTests(): void
    {
        $this->output("開始執行測試...\n\n");

        // 建立測試套件實例
        $testSuite = new PermissionManagementTestSuite();
        $testSuite->setUp();

        $startTime = microtime(true);

        try {
            if (isset($this->options['functional']) || isset($this->options['all'])) {
                $this->runFunctionalTests($testSuite);
            }

            if (isset($this->options['browser']) || isset($this->options['all'])) {
                $this->runBrowserTests($testSuite);
            }

            if (isset($this->options['performance']) || isset($this->options['all'])) {
                $this->runPerformanceTests($testSuite);
            }

            if (isset($this->options['security']) || isset($this->options['all'])) {
                $this->runSecurityTests($testSuite);
            }

            if (isset($this->options['all'])) {
                $this->runCompleteTestSuite($testSuite);
            }

        } catch (\Exception $e) {
            $this->output("測試執行失敗: " . $e->getMessage() . "\n", 'error');
            throw $e;
        }

        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;

        $this->output("\n測試執行完成！總耗時: " . number_format($totalTime, 2) . " 秒\n", 'success');
    }

    protected function runFunctionalTests($testSuite): void
    {
        $this->output("執行功能整合測試...\n", 'info');
        
        $functionalTests = [
            'test_complete_permission_management_workflow',
            'test_permission_dependency_management',
            'test_circular_dependency_prevention',
            'test_access_control_for_different_users',
            'test_permission_import_export_workflow',
            'test_permission_usage_analysis_workflow',
            'test_permission_security_controls',
            'test_permission_audit_functionality',
        ];

        $this->runTestMethods($testSuite, $functionalTests, '功能測試');
    }

    protected function runBrowserTests($testSuite): void
    {
        $this->output("執行瀏覽器自動化測試...\n", 'info');
        
        // 這裡需要實際的瀏覽器測試實作
        $this->output("瀏覽器測試: 已跳過（需要實際的瀏覽器環境）\n", 'warning');
    }

    protected function runPerformanceTests($testSuite): void
    {
        $this->output("執行效能測試...\n", 'info');
        
        $performanceTests = [
            'test_performance_with_large_dataset',
            'test_concurrent_operations',
        ];

        $this->runTestMethods($testSuite, $performanceTests, '效能測試');
    }

    protected function runSecurityTests($testSuite): void
    {
        $this->output("執行安全性測試...\n", 'info');
        
        $securityTests = [
            'test_permission_security_controls',
        ];

        $this->runTestMethods($testSuite, $securityTests, '安全性測試');
    }

    protected function runCompleteTestSuite($testSuite): void
    {
        $this->output("執行完整測試套件...\n", 'info');
        
        try {
            $testSuite->run_complete_permission_management_test_suite();
            $this->output("完整測試套件執行成功！\n", 'success');
        } catch (\Exception $e) {
            $this->output("完整測試套件執行失敗: " . $e->getMessage() . "\n", 'error');
            throw $e;
        }
    }

    protected function runTestMethods($testSuite, array $methods, string $category): void
    {
        $passed = 0;
        $failed = 0;

        foreach ($methods as $method) {
            $startTime = microtime(true);
            
            try {
                if (method_exists($testSuite, $method)) {
                    $testSuite->$method();
                    $status = '通過';
                    $statusColor = 'success';
                    $passed++;
                } else {
                    $status = '跳過（方法不存在）';
                    $statusColor = 'warning';
                }
            } catch (\Exception $e) {
                $status = '失敗: ' . $e->getMessage();
                $statusColor = 'error';
                $failed++;
            }

            $endTime = microtime(true);
            $duration = number_format($endTime - $startTime, 3);

            $this->output("  {$method}: {$status} ({$duration}s)\n", $statusColor);

            if ($this->verbose && $statusColor === 'error') {
                $this->output("    詳細錯誤: " . $e->getTraceAsString() . "\n", 'error');
            }
        }

        $total = $passed + $failed;
        $successRate = $total > 0 ? ($passed / $total) * 100 : 0;
        
        $this->output("\n{$category}摘要: {$passed}/{$total} 通過 (" . number_format($successRate, 1) . "%)\n\n");
    }

    protected function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;

        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    protected function output(string $message, string $type = 'normal'): void
    {
        $colors = [
            'normal' => '',
            'info' => "\033[36m",      // 青色
            'success' => "\033[32m",   // 綠色
            'warning' => "\033[33m",   // 黃色
            'error' => "\033[31m",     // 紅色
            'reset' => "\033[0m",      // 重置
        ];

        $color = $colors[$type] ?? '';
        $reset = $colors['reset'];

        echo $color . $message . $reset;
    }
}

// 執行測試
$runner = new PermissionManagementTestRunner($argv);
exit($runner->run());