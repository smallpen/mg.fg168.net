<?php

/**
 * 管理後台佈局和導航系統整合測試執行腳本
 * 
 * 此腳本用於執行所有相關的整合測試，包含：
 * - 完整佈局導航流程測試
 * - 響應式設計測試
 * - 主題切換和多語言測試
 * - 鍵盤導航和無障礙功能測試
 * - 瀏覽器自動化測試
 */

require_once __DIR__ . '/../../vendor/autoload.php';

class IntegrationTestRunner
{
    private array $testClasses = [
        'AdminLayoutNavigationIntegrationTest',
        'ResponsiveDesignTest',
        'ThemeAndLanguageTest',
        'KeyboardNavigationIntegrationTest',
        'AdminLayoutNavigationTestSuite'
    ];

    private array $testResults = [];
    private float $startTime;

    public function __construct()
    {
        $this->startTime = microtime(true);
    }

    /**
     * 執行所有整合測試
     */
    public function runAllTests(): void
    {
        echo $this->getHeader();
        
        foreach ($this->testClasses as $testClass) {
            $this->runTestClass($testClass);
        }
        
        $this->generateReport();
    }

    /**
     * 執行單一測試類別
     */
    private function runTestClass(string $testClass): void
    {
        echo "\n" . str_repeat("-", 60) . "\n";
        echo "執行測試類別: {$testClass}\n";
        echo str_repeat("-", 60) . "\n";

        $startTime = microtime(true);
        
        try {
            $command = "docker-compose exec app php artisan dusk --filter={$testClass}";
            $output = [];
            $returnCode = 0;
            
            exec($command, $output, $returnCode);
            
            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            
            $this->testResults[$testClass] = [
                'status' => $returnCode === 0 ? 'PASSED' : 'FAILED',
                'execution_time' => $executionTime,
                'output' => implode("\n", $output)
            ];
            
            if ($returnCode === 0) {
                echo "✓ {$testClass} - 通過 (" . number_format($executionTime, 2) . "s)\n";
            } else {
                echo "✗ {$testClass} - 失敗 (" . number_format($executionTime, 2) . "s)\n";
                echo "錯誤輸出:\n" . implode("\n", array_slice($output, -10)) . "\n";
            }
            
        } catch (Exception $e) {
            $this->testResults[$testClass] = [
                'status' => 'ERROR',
                'execution_time' => 0,
                'output' => $e->getMessage()
            ];
            
            echo "✗ {$testClass} - 錯誤: " . $e->getMessage() . "\n";
        }
    }

    /**
     * 生成測試報告
     */
    private function generateReport(): void
    {
        $totalTime = microtime(true) - $this->startTime;
        $passedTests = array_filter($this->testResults, fn($result) => $result['status'] === 'PASSED');
        $failedTests = array_filter($this->testResults, fn($result) => $result['status'] === 'FAILED');
        $errorTests = array_filter($this->testResults, fn($result) => $result['status'] === 'ERROR');

        echo "\n" . str_repeat("=", 80) . "\n";
        echo "整合測試執行報告\n";
        echo str_repeat("=", 80) . "\n";
        echo "執行時間: " . date('Y-m-d H:i:s') . "\n";
        echo "總執行時間: " . number_format($totalTime, 2) . " 秒\n";
        echo "測試類別總數: " . count($this->testClasses) . "\n";
        echo "通過: " . count($passedTests) . "\n";
        echo "失敗: " . count($failedTests) . "\n";
        echo "錯誤: " . count($errorTests) . "\n";
        echo "成功率: " . number_format((count($passedTests) / count($this->testClasses)) * 100, 1) . "%\n";

        echo "\n詳細結果:\n";
        echo str_repeat("-", 80) . "\n";
        
        foreach ($this->testResults as $testClass => $result) {
            $status = $result['status'];
            $time = number_format($result['execution_time'], 2);
            $icon = $status === 'PASSED' ? '✓' : ($status === 'FAILED' ? '✗' : '⚠');
            
            echo sprintf("%-50s %s %s (%ss)\n", $testClass, $icon, $status, $time);
        }

        // 生成 HTML 報告
        $this->generateHtmlReport();
        
        // 生成 JSON 報告
        $this->generateJsonReport();

        echo "\n" . str_repeat("=", 80) . "\n";
        
        if (count($failedTests) > 0 || count($errorTests) > 0) {
            echo "⚠ 部分測試未通過，請檢查上述錯誤訊息\n";
            exit(1);
        } else {
            echo "🎉 所有整合測試通過！\n";
            exit(0);
        }
    }

    /**
     * 生成 HTML 報告
     */
    private function generateHtmlReport(): void
    {
        $html = $this->generateHtmlContent();
        $reportPath = storage_path('app/test-reports/integration-test-report.html');
        
        if (!is_dir(dirname($reportPath))) {
            mkdir(dirname($reportPath), 0755, true);
        }
        
        file_put_contents($reportPath, $html);
        echo "\nHTML 報告已生成: {$reportPath}\n";
    }

    /**
     * 生成 JSON 報告
     */
    private function generateJsonReport(): void
    {
        $report = [
            'execution_date' => date('Y-m-d H:i:s'),
            'total_execution_time' => microtime(true) - $this->startTime,
            'test_summary' => [
                'total' => count($this->testClasses),
                'passed' => count(array_filter($this->testResults, fn($r) => $r['status'] === 'PASSED')),
                'failed' => count(array_filter($this->testResults, fn($r) => $r['status'] === 'FAILED')),
                'error' => count(array_filter($this->testResults, fn($r) => $r['status'] === 'ERROR'))
            ],
            'test_results' => $this->testResults,
            'environment' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'browser' => 'Chrome (Headless)',
                'os' => PHP_OS
            ]
        ];

        $reportPath = storage_path('app/test-reports/integration-test-report.json');
        file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "JSON 報告已生成: {$reportPath}\n";
    }

    /**
     * 生成 HTML 報告內容
     */
    private function generateHtmlContent(): string
    {
        $totalTime = microtime(true) - $this->startTime;
        $passedCount = count(array_filter($this->testResults, fn($r) => $r['status'] === 'PASSED'));
        $failedCount = count(array_filter($this->testResults, fn($r) => $r['status'] === 'FAILED'));
        $errorCount = count(array_filter($this->testResults, fn($r) => $r['status'] === 'ERROR'));
        $successRate = number_format(($passedCount / count($this->testClasses)) * 100, 1);

        $html = <<<HTML
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理後台佈局和導航系統 - 整合測試報告</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #007cba; padding-bottom: 10px; }
        .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
        .summary-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; text-align: center; }
        .summary-card h3 { margin: 0 0 10px 0; font-size: 2em; }
        .summary-card p { margin: 0; opacity: 0.9; }
        .test-results { margin-top: 30px; }
        .test-item { display: flex; align-items: center; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #ddd; }
        .test-item.passed { background: #f0f9f0; border-left-color: #28a745; }
        .test-item.failed { background: #fdf2f2; border-left-color: #dc3545; }
        .test-item.error { background: #fff3cd; border-left-color: #ffc107; }
        .test-name { flex: 1; font-weight: bold; }
        .test-status { padding: 5px 15px; border-radius: 20px; color: white; font-size: 0.9em; }
        .status-passed { background: #28a745; }
        .status-failed { background: #dc3545; }
        .status-error { background: #ffc107; color: #333; }
        .test-time { margin-left: 15px; color: #666; font-size: 0.9em; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; text-align: center; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 管理後台佈局和導航系統 - 整合測試報告</h1>
        
        <div class="summary">
            <div class="summary-card">
                <h3>{$passedCount}</h3>
                <p>通過測試</p>
            </div>
            <div class="summary-card">
                <h3>{$failedCount}</h3>
                <p>失敗測試</p>
            </div>
            <div class="summary-card">
                <h3>{$errorCount}</h3>
                <p>錯誤測試</p>
            </div>
            <div class="summary-card">
                <h3>{$successRate}%</h3>
                <p>成功率</p>
            </div>
        </div>

        <div class="test-results">
            <h2>📋 詳細測試結果</h2>
HTML;

        foreach ($this->testResults as $testClass => $result) {
            $status = strtolower($result['status']);
            $statusText = $result['status'];
            $time = number_format($result['execution_time'], 2);
            
            $html .= <<<HTML
            <div class="test-item {$status}">
                <div class="test-name">{$testClass}</div>
                <div class="test-status status-{$status}">{$statusText}</div>
                <div class="test-time">{$time}s</div>
            </div>
HTML;
        }

        $html .= <<<HTML
        </div>

        <div class="footer">
            <p>報告生成時間: {date('Y-m-d H:i:s')}</p>
            <p>總執行時間: {number_format($totalTime, 2)} 秒</p>
            <p>測試環境: PHP {PHP_VERSION} | Laravel {app()->version()}</p>
        </div>
    </div>
</body>
</html>
HTML;

        return $html;
    }

    /**
     * 取得標題
     */
    private function getHeader(): string
    {
        return <<<HEADER

╔══════════════════════════════════════════════════════════════════════════════╗
║                    管理後台佈局和導航系統 - 整合測試套件                      ║
╠══════════════════════════════════════════════════════════════════════════════╣
║ 測試範圍:                                                                    ║
║ • 完整佈局導航流程測試                                                        ║
║ • 響應式設計在不同裝置的表現                                                  ║
║ • 主題切換和多語言功能                                                        ║
║ • 鍵盤導航和無障礙功能                                                        ║
║ • 瀏覽器自動化測試                                                            ║
║                                                                              ║
║ 執行時間: {date('Y-m-d H:i:s')}                                                    ║
╚══════════════════════════════════════════════════════════════════════════════╝

HEADER;
    }
}

// 執行測試
if (php_sapi_name() === 'cli') {
    $runner = new IntegrationTestRunner();
    $runner->runAllTests();
} else {
    echo "此腳本只能在命令列中執行\n";
    exit(1);
}