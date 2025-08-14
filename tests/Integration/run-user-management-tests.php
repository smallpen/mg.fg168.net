<?php

/**
 * 使用者管理整合測試執行腳本
 * 
 * 這個腳本用於執行所有使用者管理相關的整合測試
 * 包含功能測試、瀏覽器測試和效能測試
 */

require_once __DIR__ . '/../../vendor/autoload.php';

class UserManagementTestRunner
{
    private array $testResults = [];
    private float $startTime;
    private string $reportPath;

    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->reportPath = storage_path('logs/user-management-test-report-' . date('Y-m-d-H-i-s') . '.html');
    }

    /**
     * 執行所有整合測試
     */
    public function runAllTests(): void
    {
        echo "🚀 開始執行使用者管理整合測試套件\n";
        echo "=" . str_repeat("=", 50) . "\n\n";

        // 1. 執行功能整合測試
        $this->runIntegrationTests();

        // 2. 執行效能測試
        $this->runPerformanceTests();

        // 3. 執行瀏覽器測試
        $this->runBrowserTests();

        // 4. 生成測試報告
        $this->generateReport();

        echo "\n" . str_repeat("=", 52) . "\n";
        echo "✅ 所有測試執行完成！\n";
        echo "📊 測試報告已生成：{$this->reportPath}\n";
        echo "⏱️  總執行時間：" . round(microtime(true) - $this->startTime, 2) . " 秒\n";
    }

    /**
     * 執行功能整合測試
     */
    private function runIntegrationTests(): void
    {
        echo "📋 執行功能整合測試...\n";
        
        $command = 'php artisan test tests/Feature/Integration/UserManagementIntegrationTest.php --verbose';
        $output = $this->executeCommand($command);
        
        $this->testResults['integration'] = [
            'name' => '功能整合測試',
            'command' => $command,
            'output' => $output,
            'status' => $this->parseTestStatus($output),
            'duration' => $this->parseTestDuration($output),
        ];

        echo $this->testResults['integration']['status'] === 'passed' ? "✅" : "❌";
        echo " 功能整合測試完成\n\n";
    }

    /**
     * 執行效能測試
     */
    private function runPerformanceTests(): void
    {
        echo "⚡ 執行效能測試...\n";
        
        $command = 'php artisan test tests/Feature/Performance/UserManagementPerformanceTest.php --verbose';
        $output = $this->executeCommand($command);
        
        $this->testResults['performance'] = [
            'name' => '效能測試',
            'command' => $command,
            'output' => $output,
            'status' => $this->parseTestStatus($output),
            'duration' => $this->parseTestDuration($output),
            'metrics' => $this->parsePerformanceMetrics($output),
        ];

        echo $this->testResults['performance']['status'] === 'passed' ? "✅" : "❌";
        echo " 效能測試完成\n\n";
    }

    /**
     * 執行瀏覽器測試
     */
    private function runBrowserTests(): void
    {
        echo "🌐 執行瀏覽器自動化測試...\n";
        
        // 檢查是否安裝了 Laravel Dusk
        if (!file_exists(base_path('vendor/laravel/dusk'))) {
            echo "⚠️  Laravel Dusk 未安裝，跳過瀏覽器測試\n\n";
            $this->testResults['browser'] = [
                'name' => '瀏覽器測試',
                'status' => 'skipped',
                'reason' => 'Laravel Dusk 未安裝',
            ];
            return;
        }

        $command = 'php artisan dusk tests/Browser/UserManagementBrowserTest.php --verbose';
        $output = $this->executeCommand($command);
        
        $this->testResults['browser'] = [
            'name' => '瀏覽器自動化測試',
            'command' => $command,
            'output' => $output,
            'status' => $this->parseTestStatus($output),
            'duration' => $this->parseTestDuration($output),
        ];

        echo $this->testResults['browser']['status'] === 'passed' ? "✅" : "❌";
        echo " 瀏覽器測試完成\n\n";
    }

    /**
     * 執行命令並返回輸出
     */
    private function executeCommand(string $command): string
    {
        $startTime = microtime(true);
        
        ob_start();
        $returnCode = 0;
        passthru($command . ' 2>&1', $returnCode);
        $output = ob_get_clean();
        
        $duration = microtime(true) - $startTime;
        
        return $output . "\n\n執行時間: " . round($duration, 2) . " 秒\n返回碼: " . $returnCode;
    }

    /**
     * 解析測試狀態
     */
    private function parseTestStatus(string $output): string
    {
        if (strpos($output, 'FAILURES!') !== false || strpos($output, 'ERRORS!') !== false) {
            return 'failed';
        } elseif (strpos($output, 'OK') !== false || strpos($output, 'Tests:') !== false) {
            return 'passed';
        } else {
            return 'unknown';
        }
    }

    /**
     * 解析測試執行時間
     */
    private function parseTestDuration(string $output): ?float
    {
        if (preg_match('/Time: (\d+(?:\.\d+)?)\s*(?:seconds?|ms)/', $output, $matches)) {
            return (float) $matches[1];
        }
        return null;
    }

    /**
     * 解析效能指標
     */
    private function parsePerformanceMetrics(string $output): array
    {
        $metrics = [];
        
        // 解析載入時間
        if (preg_match('/頁面載入時間.*?(\d+(?:\.\d+)?)\s*秒/', $output, $matches)) {
            $metrics['page_load_time'] = (float) $matches[1];
        }
        
        // 解析搜尋響應時間
        if (preg_match('/搜尋響應時間.*?(\d+(?:\.\d+)?)\s*秒/', $output, $matches)) {
            $metrics['search_response_time'] = (float) $matches[1];
        }
        
        // 解析記憶體使用量
        if (preg_match('/記憶體使用量.*?(\d+(?:\.\d+)?)\s*MB/', $output, $matches)) {
            $metrics['memory_usage_mb'] = (float) $matches[1];
        }
        
        return $metrics;
    }

    /**
     * 生成 HTML 測試報告
     */
    private function generateReport(): void
    {
        $totalDuration = microtime(true) - $this->startTime;
        $timestamp = date('Y-m-d H:i:s');
        
        $html = $this->generateReportHTML($timestamp, $totalDuration);
        
        // 確保目錄存在
        $reportDir = dirname($this->reportPath);
        if (!is_dir($reportDir)) {
            mkdir($reportDir, 0755, true);
        }
        
        file_put_contents($this->reportPath, $html);
    }

    /**
     * 生成 HTML 報告內容
     */
    private function generateReportHTML(string $timestamp, float $totalDuration): string
    {
        $passedCount = 0;
        $failedCount = 0;
        $skippedCount = 0;
        
        foreach ($this->testResults as $result) {
            switch ($result['status']) {
                case 'passed':
                    $passedCount++;
                    break;
                case 'failed':
                    $failedCount++;
                    break;
                case 'skipped':
                    $skippedCount++;
                    break;
            }
        }
        
        $overallStatus = $failedCount > 0 ? 'FAILED' : 'PASSED';
        $statusColor = $failedCount > 0 ? '#dc3545' : '#28a745';
        
        return <<<HTML
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>使用者管理整合測試報告</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; background-color: #f8f9fa; }
        .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 8px 8px 0 0; }
        .header h1 { margin: 0; font-size: 2.5em; }
        .header .subtitle { margin: 10px 0 0 0; opacity: 0.9; }
        .summary { padding: 30px; border-bottom: 1px solid #eee; }
        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px; }
        .summary-card { background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center; }
        .summary-card h3 { margin: 0 0 10px 0; color: #495057; }
        .summary-card .number { font-size: 2em; font-weight: bold; color: #007bff; }
        .status-badge { display: inline-block; padding: 5px 15px; border-radius: 20px; color: white; font-weight: bold; }
        .status-passed { background-color: #28a745; }
        .status-failed { background-color: #dc3545; }
        .status-skipped { background-color: #6c757d; }
        .test-results { padding: 30px; }
        .test-section { margin-bottom: 30px; border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden; }
        .test-section-header { background: #f8f9fa; padding: 15px 20px; border-bottom: 1px solid #dee2e6; }
        .test-section-content { padding: 20px; }
        .test-output { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 15px; font-family: 'Courier New', monospace; font-size: 0.9em; white-space: pre-wrap; max-height: 400px; overflow-y: auto; }
        .metrics-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .metrics-table th, .metrics-table td { padding: 10px; text-align: left; border-bottom: 1px solid #dee2e6; }
        .metrics-table th { background: #f8f9fa; font-weight: bold; }
        .footer { padding: 20px 30px; background: #f8f9fa; border-radius: 0 0 8px 8px; text-align: center; color: #6c757d; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>使用者管理整合測試報告</h1>
            <p class="subtitle">完整的功能、效能和瀏覽器測試結果</p>
        </div>
        
        <div class="summary">
            <h2>測試摘要</h2>
            <p><strong>執行時間：</strong> {$timestamp}</p>
            <p><strong>總執行時間：</strong> " . round($totalDuration, 2) . " 秒</p>
            <p><strong>整體狀態：</strong> <span class="status-badge status-" . strtolower($overallStatus) . "\">{$overallStatus}</span></p>
            
            <div class="summary-grid">
                <div class="summary-card">
                    <h3>通過測試</h3>
                    <div class="number" style="color: #28a745;">{$passedCount}</div>
                </div>
                <div class="summary-card">
                    <h3>失敗測試</h3>
                    <div class="number" style="color: #dc3545;">{$failedCount}</div>
                </div>
                <div class="summary-card">
                    <h3>跳過測試</h3>
                    <div class="number" style="color: #6c757d;">{$skippedCount}</div>
                </div>
                <div class="summary-card">
                    <h3>總測試數</h3>
                    <div class="number">" . count($this->testResults) . "</div>
                </div>
            </div>
        </div>
        
        <div class="test-results">
            <h2>詳細測試結果</h2>
HTML;

        foreach ($this->testResults as $key => $result) {
            $statusClass = 'status-' . $result['status'];
            $statusText = strtoupper($result['status']);
            
            $html .= <<<HTML
            <div class="test-section">
                <div class="test-section-header">
                    <h3>{$result['name']} <span class="status-badge {$statusClass}">{$statusText}</span></h3>
                </div>
                <div class="test-section-content">
HTML;

            if (isset($result['command'])) {
                $html .= "<p><strong>執行命令：</strong> <code>{$result['command']}</code></p>";
            }
            
            if (isset($result['duration'])) {
                $html .= "<p><strong>執行時間：</strong> " . round($result['duration'], 2) . " 秒</p>";
            }
            
            if (isset($result['reason'])) {
                $html .= "<p><strong>跳過原因：</strong> {$result['reason']}</p>";
            }
            
            if (isset($result['metrics']) && !empty($result['metrics'])) {
                $html .= "<h4>效能指標</h4>";
                $html .= "<table class=\"metrics-table\">";
                $html .= "<tr><th>指標</th><th>數值</th></tr>";
                
                foreach ($result['metrics'] as $metric => $value) {
                    $html .= "<tr><td>{$metric}</td><td>{$value}</td></tr>";
                }
                
                $html .= "</table>";
            }
            
            if (isset($result['output'])) {
                $html .= "<h4>測試輸出</h4>";
                $html .= "<div class=\"test-output\">" . htmlspecialchars($result['output']) . "</div>";
            }
            
            $html .= "</div></div>";
        }

        $html .= <<<HTML
        </div>
        
        <div class="footer">
            <p>此報告由使用者管理整合測試套件自動生成</p>
            <p>生成時間：{$timestamp}</p>
        </div>
    </div>
</body>
</html>
HTML;

        return $html;
    }
}

// 執行測試套件
if (php_sapi_name() === 'cli') {
    $runner = new UserManagementTestRunner();
    $runner->runAllTests();
} else {
    echo "此腳本只能在命令列中執行\n";
    exit(1);
}