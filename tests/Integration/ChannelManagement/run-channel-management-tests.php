#!/usr/bin/env php
<?php

/**
 * 通路管理系統整合測試執行腳本
 * 
 * 此腳本用於執行所有通路管理相關的整合測試，
 * 並生成詳細的測試報告。
 * 
 * 使用方法：
 * php tests/Integration/ChannelManagement/run-channel-management-tests.php
 * 
 * 或在 Docker 環境中：
 * docker-compose exec app php tests/Integration/ChannelManagement/run-channel-management-tests.php
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ChannelManagementTestRunner
{
    private array $testResults = [];
    private string $logFile;
    private int $totalTests = 0;
    private int $passedTests = 0;
    private int $failedTests = 0;

    public function __construct()
    {
        $this->logFile = storage_path('logs/channel-management-tests-' . date('Y-m-d-H-i-s') . '.log');
        $this->log("通路管理系統整合測試開始執行");
        $this->log("測試時間: " . date('Y-m-d H:i:s'));
        $this->log("日誌檔案: " . $this->logFile);
        $this->log(str_repeat('=', 80));
    }

    /**
     * 執行所有通路管理整合測試
     */
    public function runAllTests(): void
    {
        $this->log("開始執行通路管理整合測試套件");

        // 定義要執行的測試類別
        $testClasses = [
            'ChannelManagementTestSuite' => '基礎設定和環境測試',
            'AgentManagementIntegrationTest' => '代理管理完整流程測試',
            'PointManagementIntegrationTest' => '點數管理完整流程測試',
            'PlayerManagementIntegrationTest' => '玩家管理完整流程測試',
            'ChannelManagementIntegrationTest' => '綜合業務流程測試',
        ];

        foreach ($testClasses as $testClass => $description) {
            $this->runTestClass($testClass, $description);
        }

        $this->generateSummaryReport();
    }

    /**
     * 執行單一測試類別
     */
    private function runTestClass(string $testClass, string $description): void
    {
        $this->log("\n" . str_repeat('-', 60));
        $this->log("執行測試: {$description}");
        $this->log("測試類別: {$testClass}");
        $this->log(str_repeat('-', 60));

        $testPath = "tests/Integration/ChannelManagement/{$testClass}.php";
        
        if (!file_exists($testPath)) {
            $this->log("❌ 測試檔案不存在: {$testPath}");
            $this->testResults[$testClass] = [
                'status' => 'error',
                'message' => '測試檔案不存在',
                'duration' => 0,
            ];
            return;
        }

        $startTime = microtime(true);

        try {
            // 使用 PHPUnit 執行測試
            $command = [
                'php',
                'vendor/bin/phpunit',
                '--testdox',
                '--colors=always',
                '--stop-on-failure',
                $testPath
            ];

            $process = new Process($command);
            $process->setTimeout(300); // 5分鐘超時
            $process->run();

            $duration = microtime(true) - $startTime;
            $output = $process->getOutput();
            $errorOutput = $process->getErrorOutput();

            if ($process->isSuccessful()) {
                $this->log("✅ 測試通過");
                $this->log("執行時間: " . number_format($duration, 2) . " 秒");
                $this->passedTests++;
                
                $this->testResults[$testClass] = [
                    'status' => 'passed',
                    'duration' => $duration,
                    'output' => $output,
                ];
            } else {
                $this->log("❌ 測試失敗");
                $this->log("錯誤輸出: " . $errorOutput);
                $this->failedTests++;
                
                $this->testResults[$testClass] = [
                    'status' => 'failed',
                    'duration' => $duration,
                    'output' => $output,
                    'error' => $errorOutput,
                ];
            }

            $this->totalTests++;

        } catch (ProcessFailedException $e) {
            $duration = microtime(true) - $startTime;
            $this->log("❌ 測試執行異常: " . $e->getMessage());
            $this->failedTests++;
            $this->totalTests++;
            
            $this->testResults[$testClass] = [
                'status' => 'error',
                'duration' => $duration,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 生成測試摘要報告
     */
    private function generateSummaryReport(): void
    {
        $this->log("\n" . str_repeat('=', 80));
        $this->log("測試執行摘要報告");
        $this->log(str_repeat('=', 80));

        $this->log("總測試數量: {$this->totalTests}");
        $this->log("通過測試: {$this->passedTests}");
        $this->log("失敗測試: {$this->failedTests}");
        
        $successRate = $this->totalTests > 0 ? ($this->passedTests / $this->totalTests) * 100 : 0;
        $this->log("成功率: " . number_format($successRate, 1) . "%");

        $this->log("\n詳細測試結果:");
        foreach ($this->testResults as $testClass => $result) {
            $status = $this->getStatusIcon($result['status']);
            $duration = number_format($result['duration'], 2);
            $this->log("{$status} {$testClass} ({$duration}s)");
            
            if ($result['status'] === 'failed' || $result['status'] === 'error') {
                if (isset($result['error'])) {
                    $this->log("   錯誤: " . substr($result['error'], 0, 200) . "...");
                }
            }
        }

        // 生成建議
        $this->generateRecommendations();

        $this->log("\n測試完成時間: " . date('Y-m-d H:i:s'));
        $this->log("詳細日誌檔案: " . $this->logFile);
        
        // 輸出到控制台
        echo "\n通路管理系統整合測試完成!\n";
        echo "成功率: " . number_format($successRate, 1) . "% ({$this->passedTests}/{$this->totalTests})\n";
        echo "詳細報告: {$this->logFile}\n\n";
    }

    /**
     * 生成改進建議
     */
    private function generateRecommendations(): void
    {
        $this->log("\n" . str_repeat('-', 60));
        $this->log("改進建議");
        $this->log(str_repeat('-', 60));

        if ($this->failedTests === 0) {
            $this->log("🎉 所有測試都通過了！系統整合狀況良好。");
            $this->log("建議:");
            $this->log("- 定期執行這些測試以確保系統穩定性");
            $this->log("- 考慮增加更多邊界條件測試");
            $this->log("- 監控測試執行時間，確保效能不退化");
        } else {
            $this->log("⚠️  發現 {$this->failedTests} 個失敗的測試，需要修復。");
            $this->log("建議:");
            $this->log("- 檢查失敗測試的錯誤訊息");
            $this->log("- 確認資料庫遷移和 Seeder 是否正確執行");
            $this->log("- 驗證模型關聯和服務類別實作");
            $this->log("- 檢查權限設定和業務邏輯");
        }

        // 效能建議
        $totalDuration = array_sum(array_column($this->testResults, 'duration'));
        if ($totalDuration > 60) {
            $this->log("- 測試執行時間較長 (" . number_format($totalDuration, 1) . "s)，考慮優化");
        }

        // 覆蓋率建議
        $this->log("- 考慮使用 --coverage-html 生成測試覆蓋率報告");
        $this->log("- 確保所有業務邏輯都有對應的測試");
    }

    /**
     * 取得狀態圖示
     */
    private function getStatusIcon(string $status): string
    {
        return match($status) {
            'passed' => '✅',
            'failed' => '❌',
            'error' => '💥',
            default => '❓',
        };
    }

    /**
     * 記錄日誌
     */
    private function log(string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}\n";
        
        // 寫入日誌檔案
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
        
        // 同時輸出到控制台
        echo $logMessage;
    }

    /**
     * 執行測試前的環境檢查
     */
    public function checkEnvironment(): bool
    {
        $this->log("檢查測試環境...");

        // 檢查 PHP 版本
        $phpVersion = PHP_VERSION;
        $this->log("PHP 版本: {$phpVersion}");

        // 檢查必要的擴展
        $requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'json'];
        foreach ($requiredExtensions as $extension) {
            if (!extension_loaded($extension)) {
                $this->log("❌ 缺少必要的 PHP 擴展: {$extension}");
                return false;
            }
        }

        // 檢查 PHPUnit
        if (!file_exists('vendor/bin/phpunit')) {
            $this->log("❌ PHPUnit 未安裝");
            return false;
        }

        // 檢查 Laravel 環境
        if (!file_exists('.env')) {
            $this->log("❌ .env 檔案不存在");
            return false;
        }

        $this->log("✅ 環境檢查通過");
        return true;
    }
}

// 主執行邏輯
try {
    $runner = new ChannelManagementTestRunner();
    
    if (!$runner->checkEnvironment()) {
        echo "環境檢查失敗，請修復後重新執行\n";
        exit(1);
    }
    
    $runner->runAllTests();
    
} catch (Exception $e) {
    echo "測試執行過程中發生錯誤: " . $e->getMessage() . "\n";
    echo "詳細錯誤: " . $e->getTraceAsString() . "\n";
    exit(1);
}