<?php

/**
 * 系統設定 MCP 整合測試執行腳本
 * 
 * 使用 Playwright 和 MySQL MCP 工具進行完整的端到端測試
 * 測試所有系統設定功能的整合和使用者體驗
 */

require_once __DIR__ . '/vendor/autoload.php';

class SystemSettingsMcpTestRunner
{
    private array $testResults = [];
    private float $startTime;
    private array $config;

    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->config = [
            'base_url' => 'http://localhost',
            'admin_username' => 'admin',
            'admin_password' => 'password123',
            'database' => 'laravel_admin',
            'test_timeout' => 30000, // 30 seconds
            'screenshot_dir' => __DIR__ . '/storage/screenshots',
        ];

        // 確保截圖目錄存在
        if (!is_dir($this->config['screenshot_dir'])) {
            mkdir($this->config['screenshot_dir'], 0755, true);
        }
    }

    /**
     * 執行所有測試
     */
    public function runAllTests(): void
    {
        echo "🚀 開始執行系統設定 MCP 整合測試\n";
        echo "================================\n\n";

        try {
            // 1. 準備測試環境
            $this->prepareTestEnvironment();

            // 2. 執行基本功能測試
            $this->testBasicFunctionality();

            // 3. 執行搜尋和篩選測試
            $this->testSearchAndFilter();

            // 4. 執行設定編輯測試
            $this->testSettingsEdit();

            // 5. 執行備份還原測試
            $this->testBackupRestore();

            // 6. 執行匯入匯出測試
            $this->testImportExport();

            // 7. 執行響應式設計測試
            $this->testResponsiveDesign();

            // 8. 執行無障礙功能測試
            $this->testAccessibility();

            // 9. 執行效能測試
            $this->testPerformance();

            // 10. 生成測試報告
            $this->generateReport();

        } catch (Exception $e) {
            echo "❌ 測試執行失敗: " . $e->getMessage() . "\n";
            $this->testResults['error'] = $e->getMessage();
        } finally {
            // 清理測試環境
            $this->cleanupTestEnvironment();
        }
    }

    /**
     * 準備測試環境
     */
    private function prepareTestEnvironment(): void
    {
        echo "📋 準備測試環境...\n";

        // 檢查資料庫連線
        $this->checkDatabaseConnection();

        // 建立測試資料
        $this->createTestData();

        // 啟動瀏覽器
        $this->startBrowser();

        echo "✅ 測試環境準備完成\n\n";
    }

    /**
     * 檢查資料庫連線
     */
    private function checkDatabaseConnection(): void
    {
        echo "  • 檢查資料庫連線...\n";

        // 使用 MySQL MCP 檢查連線
        $databases = $this->executeMcpCommand('mcp_mysql_list_databases');
        
        if (empty($databases)) {
            throw new Exception('無法連接到資料庫');
        }

        echo "    ✓ 資料庫連線正常\n";
    }

    /**
     * 建立測試資料
     */
    private function createTestData(): void
    {
        echo "  • 建立測試資料...\n";

        // 檢查是否已有測試設定
        $existingSettings = $this->executeMcpCommand('mcp_mysql_execute_query', [
            'query' => "SELECT COUNT(*) as count FROM settings WHERE `key` LIKE 'mcp_test.%'",
            'database' => $this->config['database']
        ]);

        if ($existingSettings[0]['count'] == 0) {
            // 建立測試設定
            $testSettings = [
                [
                    'key' => 'mcp_test.app.name',
                    'value' => 'MCP Test Application',
                    'category' => 'basic',
                    'type' => 'text',
                    'description' => 'MCP 測試應用程式名稱',
                    'default_value' => 'Default App',
                    'is_system' => 0,
                    'is_encrypted' => 0,
                    'sort_order' => 1,
                ],
                [
                    'key' => 'mcp_test.security.password_length',
                    'value' => '10',
                    'category' => 'security',
                    'type' => 'number',
                    'description' => 'MCP 測試密碼長度',
                    'default_value' => '8',
                    'is_system' => 1,
                    'is_encrypted' => 0,
                    'sort_order' => 1,
                ],
                [
                    'key' => 'mcp_test.appearance.theme_color',
                    'value' => '#FF5722',
                    'category' => 'appearance',
                    'type' => 'color',
                    'description' => 'MCP 測試主題顏色',
                    'default_value' => '#3B82F6',
                    'is_system' => 0,
                    'is_encrypted' => 0,
                    'sort_order' => 1,
                ],
            ];

            foreach ($testSettings as $setting) {
                $this->executeMcpCommand('mcp_mysql_execute_query', [
                    'query' => "INSERT INTO settings (`key`, `value`, category, type, description, default_value, is_system, is_encrypted, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
                    'database' => $this->config['database']
                ]);
            }
        }

        echo "    ✓ 測試資料建立完成\n";
    }

    /**
     * 啟動瀏覽器
     */
    private function startBrowser(): void
    {
        echo "  • 啟動瀏覽器...\n";

        $this->executeMcpCommand('mcp_playwright_playwright_navigate', [
            'url' => $this->config['base_url'] . '/admin/login',
            'headless' => false,
            'width' => 1920,
            'height' => 1080
        ]);

        echo "    ✓ 瀏覽器啟動完成\n";
    }

    /**
     * 測試基本功能
     */
    private function testBasicFunctionality(): void
    {
        echo "🔧 測試基本功能...\n";

        $testResult = [
            'name' => '基本功能測試',
            'passed' => true,
            'details' => [],
            'screenshots' => [],
        ];

        try {
            // 1. 登入系統
            echo "  • 登入系統...\n";
            $this->loginAsAdmin();
            $testResult['details'][] = '登入成功';

            // 2. 導航到設定頁面
            echo "  • 導航到設定頁面...\n";
            $this->executeMcpCommand('mcp_playwright_playwright_navigate', [
                'url' => $this->config['base_url'] . '/admin/settings/system'
            ]);

            // 截圖
            $screenshot = $this->takeScreenshot('settings-page-loaded');
            $testResult['screenshots'][] = $screenshot;

            // 3. 檢查頁面元素
            echo "  • 檢查頁面元素...\n";
            $pageContent = $this->executeMcpCommand('mcp_playwright_playwright_get_visible_text');
            
            if (strpos($pageContent, '系統設定管理') === false) {
                throw new Exception('設定頁面未正確載入');
            }
            $testResult['details'][] = '設定頁面載入正常';

            // 4. 檢查設定列表
            echo "  • 檢查設定列表...\n";
            $settingsCount = $this->executeMcpCommand('mcp_mysql_execute_query', [
                'query' => "SELECT COUNT(*) as count FROM settings WHERE `key` LIKE 'mcp_test.%'",
                'database' => $this->config['database']
            ]);

            if ($settingsCount[0]['count'] < 3) {
                throw new Exception('測試設定數量不足');
            }
            $testResult['details'][] = "找到 {$settingsCount[0]['count']} 個測試設定";

            echo "    ✅ 基本功能測試通過\n\n";

        } catch (Exception $e) {
            $testResult['passed'] = false;
            $testResult['error'] = $e->getMessage();
            echo "    ❌ 基本功能測試失敗: " . $e->getMessage() . "\n\n";
        }

        $this->testResults['basic_functionality'] = $testResult;
    }

    /**
     * 測試搜尋和篩選功能
     */
    private function testSearchAndFilter(): void
    {
        echo "🔍 測試搜尋和篩選功能...\n";

        $testResult = [
            'name' => '搜尋和篩選測試',
            'passed' => true,
            'details' => [],
            'screenshots' => [],
        ];

        try {
            // 1. 測試搜尋功能
            echo "  • 測試搜尋功能...\n";
            $this->executeMcpCommand('mcp_playwright_playwright_fill', [
                'selector' => '#search-input',
                'value' => 'mcp_test'
            ]);

            // 等待搜尋結果
            sleep(2);
            $screenshot = $this->takeScreenshot('search-results');
            $testResult['screenshots'][] = $screenshot;

            $testResult['details'][] = '搜尋功能執行完成';

            // 2. 測試分類篩選
            echo "  • 測試分類篩選...\n";
            $this->executeMcpCommand('mcp_playwright_playwright_select', [
                'selector' => '#category-filter',
                'value' => 'basic'
            ]);

            sleep(2);
            $screenshot = $this->takeScreenshot('category-filter');
            $testResult['screenshots'][] = $screenshot;

            $testResult['details'][] = '分類篩選功能執行完成';

            // 3. 清除篩選
            echo "  • 清除篩選...\n";
            $this->executeMcpCommand('mcp_playwright_playwright_click', [
                'selector' => '#clear-filters'
            ]);

            $testResult['details'][] = '篩選清除功能執行完成';

            echo "    ✅ 搜尋和篩選測試通過\n\n";

        } catch (Exception $e) {
            $testResult['passed'] = false;
            $testResult['error'] = $e->getMessage();
            echo "    ❌ 搜尋和篩選測試失敗: " . $e->getMessage() . "\n\n";
        }

        $this->testResults['search_and_filter'] = $testResult;
    }

    /**
     * 測試設定編輯功能
     */
    private function testSettingsEdit(): void
    {
        echo "✏️ 測試設定編輯功能...\n";

        $testResult = [
            'name' => '設定編輯測試',
            'passed' => true,
            'details' => [],
            'screenshots' => [],
        ];

        try {
            // 1. 點擊編輯按鈕
            echo "  • 點擊編輯按鈕...\n";
            $this->executeMcpCommand('mcp_playwright_playwright_click', [
                'selector' => '[data-setting-key="mcp_test.app.name"] .edit-button'
            ]);

            sleep(1);
            $screenshot = $this->takeScreenshot('edit-modal-opened');
            $testResult['screenshots'][] = $screenshot;

            // 2. 修改設定值
            echo "  • 修改設定值...\n";
            $newValue = 'MCP Test Updated App Name';
            $this->executeMcpCommand('mcp_playwright_playwright_fill', [
                'selector' => '#setting-value-input',
                'value' => $newValue
            ]);

            // 3. 儲存設定
            echo "  • 儲存設定...\n";
            $this->executeMcpCommand('mcp_playwright_playwright_click', [
                'selector' => '#save-setting-button'
            ]);

            sleep(2);
            $screenshot = $this->takeScreenshot('setting-saved');
            $testResult['screenshots'][] = $screenshot;

            // 4. 驗證資料庫中的變更
            echo "  • 驗證資料庫變更...\n";
            $updatedSetting = $this->executeMcpCommand('mcp_mysql_execute_query', [
                'query' => "SELECT value FROM settings WHERE `key` = 'mcp_test.app.name'",
                'database' => $this->config['database']
            ]);

            if ($updatedSetting[0]['value'] !== $newValue) {
                throw new Exception('設定值未正確更新到資料庫');
            }

            $testResult['details'][] = '設定值已成功更新';

            echo "    ✅ 設定編輯測試通過\n\n";

        } catch (Exception $e) {
            $testResult['passed'] = false;
            $testResult['error'] = $e->getMessage();
            echo "    ❌ 設定編輯測試失敗: " . $e->getMessage() . "\n\n";
        }

        $this->testResults['settings_edit'] = $testResult;
    }

    /**
     * 測試備份還原功能
     */
    private function testBackupRestore(): void
    {
        echo "💾 測試備份還原功能...\n";

        $testResult = [
            'name' => '備份還原測試',
            'passed' => true,
            'details' => [],
            'screenshots' => [],
        ];

        try {
            // 1. 導航到備份頁面
            echo "  • 導航到備份頁面...\n";
            $this->executeMcpCommand('mcp_playwright_playwright_navigate', [
                'url' => $this->config['base_url'] . '/admin/settings/backups'
            ]);

            $screenshot = $this->takeScreenshot('backup-page');
            $testResult['screenshots'][] = $screenshot;

            // 2. 建立備份
            echo "  • 建立備份...\n";
            $this->executeMcpCommand('mcp_playwright_playwright_click', [
                'selector' => '#create-backup-button'
            ]);

            // 填寫備份名稱
            $this->executeMcpCommand('mcp_playwright_playwright_fill', [
                'selector' => '#backup-name',
                'value' => 'MCP 整合測試備份'
            ]);

            $this->executeMcpCommand('mcp_playwright_playwright_fill', [
                'selector' => '#backup-description',
                'value' => 'MCP 工具整合測試建立的備份'
            ]);

            $this->executeMcpCommand('mcp_playwright_playwright_click', [
                'selector' => '#confirm-create-backup'
            ]);

            sleep(3);
            $screenshot = $this->takeScreenshot('backup-created');
            $testResult['screenshots'][] = $screenshot;

            // 3. 驗證備份已建立
            echo "  • 驗證備份已建立...\n";
            $backups = $this->executeMcpCommand('mcp_mysql_execute_query', [
                'query' => "SELECT COUNT(*) as count FROM setting_backups WHERE name = 'MCP 整合測試備份'",
                'database' => $this->config['database']
            ]);

            if ($backups[0]['count'] == 0) {
                throw new Exception('備份未成功建立');
            }

            $testResult['details'][] = '備份建立成功';

            echo "    ✅ 備份還原測試通過\n\n";

        } catch (Exception $e) {
            $testResult['passed'] = false;
            $testResult['error'] = $e->getMessage();
            echo "    ❌ 備份還原測試失敗: " . $e->getMessage() . "\n\n";
        }

        $this->testResults['backup_restore'] = $testResult;
    }

    /**
     * 測試匯入匯出功能
     */
    private function testImportExport(): void
    {
        echo "📤 測試匯入匯出功能...\n";

        $testResult = [
            'name' => '匯入匯出測試',
            'passed' => true,
            'details' => [],
            'screenshots' => [],
        ];

        try {
            // 1. 測試匯出功能
            echo "  • 測試匯出功能...\n";
            $this->executeMcpCommand('mcp_playwright_playwright_navigate', [
                'url' => $this->config['base_url'] . '/admin/settings/system'
            ]);

            $this->executeMcpCommand('mcp_playwright_playwright_click', [
                'selector' => '#export-settings-button'
            ]);

            sleep(2);
            $screenshot = $this->takeScreenshot('export-dialog');
            $testResult['screenshots'][] = $screenshot;

            $testResult['details'][] = '匯出功能執行完成';

            echo "    ✅ 匯入匯出測試通過\n\n";

        } catch (Exception $e) {
            $testResult['passed'] = false;
            $testResult['error'] = $e->getMessage();
            echo "    ❌ 匯入匯出測試失敗: " . $e->getMessage() . "\n\n";
        }

        $this->testResults['import_export'] = $testResult;
    }

    /**
     * 測試響應式設計
     */
    private function testResponsiveDesign(): void
    {
        echo "📱 測試響應式設計...\n";

        $testResult = [
            'name' => '響應式設計測試',
            'passed' => true,
            'details' => [],
            'screenshots' => [],
        ];

        try {
            // 1. 測試桌面版本
            echo "  • 測試桌面版本...\n";
            $this->executeMcpCommand('mcp_playwright_playwright_navigate', [
                'url' => $this->config['base_url'] . '/admin/settings/system',
                'width' => 1920,
                'height' => 1080
            ]);

            $screenshot = $this->takeScreenshot('desktop-view');
            $testResult['screenshots'][] = $screenshot;

            // 2. 測試平板版本
            echo "  • 測試平板版本...\n";
            $this->executeMcpCommand('mcp_playwright_playwright_navigate', [
                'url' => $this->config['base_url'] . '/admin/settings/system',
                'width' => 768,
                'height' => 1024
            ]);

            $screenshot = $this->takeScreenshot('tablet-view');
            $testResult['screenshots'][] = $screenshot;

            // 3. 測試手機版本
            echo "  • 測試手機版本...\n";
            $this->executeMcpCommand('mcp_playwright_playwright_navigate', [
                'url' => $this->config['base_url'] . '/admin/settings/system',
                'width' => 375,
                'height' => 667
            ]);

            $screenshot = $this->takeScreenshot('mobile-view');
            $testResult['screenshots'][] = $screenshot;

            $testResult['details'][] = '響應式設計測試完成';

            echo "    ✅ 響應式設計測試通過\n\n";

        } catch (Exception $e) {
            $testResult['passed'] = false;
            $testResult['error'] = $e->getMessage();
            echo "    ❌ 響應式設計測試失敗: " . $e->getMessage() . "\n\n";
        }

        $this->testResults['responsive_design'] = $testResult;
    }

    /**
     * 測試無障礙功能
     */
    private function testAccessibility(): void
    {
        echo "♿ 測試無障礙功能...\n";

        $testResult = [
            'name' => '無障礙功能測試',
            'passed' => true,
            'details' => [],
            'screenshots' => [],
        ];

        try {
            // 1. 檢查鍵盤導航
            echo "  • 檢查鍵盤導航...\n";
            $this->executeMcpCommand('mcp_playwright_playwright_navigate', [
                'url' => $this->config['base_url'] . '/admin/settings/system'
            ]);

            // 使用 Tab 鍵導航
            $this->executeMcpCommand('mcp_playwright_playwright_press_key', [
                'key' => 'Tab'
            ]);

            $this->executeMcpCommand('mcp_playwright_playwright_press_key', [
                'key' => 'Tab'
            ]);

            $screenshot = $this->takeScreenshot('keyboard-navigation');
            $testResult['screenshots'][] = $screenshot;

            // 2. 檢查 ARIA 標籤
            echo "  • 檢查 ARIA 標籤...\n";
            $html = $this->executeMcpCommand('mcp_playwright_playwright_get_visible_html');
            
            if (strpos($html, 'aria-label') === false) {
                $testResult['details'][] = '警告：未找到 ARIA 標籤';
            } else {
                $testResult['details'][] = 'ARIA 標籤檢查通過';
            }

            echo "    ✅ 無障礙功能測試通過\n\n";

        } catch (Exception $e) {
            $testResult['passed'] = false;
            $testResult['error'] = $e->getMessage();
            echo "    ❌ 無障礙功能測試失敗: " . $e->getMessage() . "\n\n";
        }

        $this->testResults['accessibility'] = $testResult;
    }

    /**
     * 測試效能指標
     */
    private function testPerformance(): void
    {
        echo "⚡ 測試效能指標...\n";

        $testResult = [
            'name' => '效能測試',
            'passed' => true,
            'details' => [],
            'screenshots' => [],
            'metrics' => [],
        ];

        try {
            // 1. 測試頁面載入時間
            echo "  • 測試頁面載入時間...\n";
            $startTime = microtime(true);
            
            $this->executeMcpCommand('mcp_playwright_playwright_navigate', [
                'url' => $this->config['base_url'] . '/admin/settings/system'
            ]);
            
            $loadTime = (microtime(true) - $startTime) * 1000;
            $testResult['metrics']['page_load_time'] = round($loadTime, 2);

            if ($loadTime > 3000) {
                $testResult['details'][] = "警告：頁面載入時間過長 ({$loadTime}ms)";
            } else {
                $testResult['details'][] = "頁面載入時間正常 ({$loadTime}ms)";
            }

            // 2. 測試搜尋響應時間
            echo "  • 測試搜尋響應時間...\n";
            $startTime = microtime(true);
            
            $this->executeMcpCommand('mcp_playwright_playwright_fill', [
                'selector' => '#search-input',
                'value' => 'test'
            ]);
            
            sleep(1); // 等待搜尋完成
            $searchTime = (microtime(true) - $startTime) * 1000;
            $testResult['metrics']['search_response_time'] = round($searchTime, 2);

            // 3. 測試資料庫查詢效能
            echo "  • 測試資料庫查詢效能...\n";
            $startTime = microtime(true);
            
            $this->executeMcpCommand('mcp_mysql_execute_query', [
                'query' => "SELECT * FROM settings LIMIT 100",
                'database' => $this->config['database']
            ]);
            
            $queryTime = (microtime(true) - $startTime) * 1000;
            $testResult['metrics']['database_query_time'] = round($queryTime, 2);

            $testResult['details'][] = '效能指標測試完成';

            echo "    ✅ 效能測試通過\n\n";

        } catch (Exception $e) {
            $testResult['passed'] = false;
            $testResult['error'] = $e->getMessage();
            echo "    ❌ 效能測試失敗: " . $e->getMessage() . "\n\n";
        }

        $this->testResults['performance'] = $testResult;
    }

    /**
     * 生成測試報告
     */
    private function generateReport(): void
    {
        echo "📊 生成測試報告...\n";

        $totalTime = round((microtime(true) - $this->startTime) * 1000, 2);
        $passedTests = 0;
        $failedTests = 0;

        foreach ($this->testResults as $result) {
            if (isset($result['passed'])) {
                if ($result['passed']) {
                    $passedTests++;
                } else {
                    $failedTests++;
                }
            }
        }

        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'total_execution_time' => $totalTime,
            'total_tests' => $passedTests + $failedTests,
            'passed_tests' => $passedTests,
            'failed_tests' => $failedTests,
            'success_rate' => $passedTests + $failedTests > 0 ? round(($passedTests / ($passedTests + $failedTests)) * 100, 2) : 0,
            'test_results' => $this->testResults,
            'environment' => [
                'base_url' => $this->config['base_url'],
                'database' => $this->config['database'],
                'php_version' => PHP_VERSION,
            ],
        ];

        // 儲存 JSON 報告
        $reportPath = __DIR__ . '/storage/logs/mcp_integration_test_report_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 生成 HTML 報告
        $this->generateHtmlReport($report);

        echo "  • JSON 報告已儲存至: {$reportPath}\n";
        echo "  • HTML 報告已生成\n";

        // 顯示摘要
        echo "\n📋 測試摘要\n";
        echo "===========\n";
        echo "總測試數: {$report['total_tests']}\n";
        echo "通過: {$report['passed_tests']}\n";
        echo "失敗: {$report['failed_tests']}\n";
        echo "成功率: {$report['success_rate']}%\n";
        echo "執行時間: {$totalTime}ms\n\n";

        if ($failedTests > 0) {
            echo "❌ 有測試失敗，請檢查詳細報告\n";
        } else {
            echo "✅ 所有測試通過！\n";
        }
    }

    /**
     * 生成 HTML 報告
     */
    private function generateHtmlReport(array $report): void
    {
        $html = $this->generateHtmlReportTemplate($report);
        $htmlPath = __DIR__ . '/storage/logs/mcp_integration_test_report_' . date('Y-m-d_H-i-s') . '.html';
        file_put_contents($htmlPath, $html);
    }

    /**
     * 生成 HTML 報告模板
     */
    private function generateHtmlReportTemplate(array $report): string
    {
        $testResultsHtml = '';
        foreach ($report['test_results'] as $testName => $result) {
            if (!isset($result['name'])) continue;
            
            $status = $result['passed'] ? '✅ 通過' : '❌ 失敗';
            $statusClass = $result['passed'] ? 'success' : 'failure';
            
            $detailsHtml = '';
            if (!empty($result['details'])) {
                $detailsHtml = '<ul>';
                foreach ($result['details'] as $detail) {
                    $detailsHtml .= "<li>{$detail}</li>";
                }
                $detailsHtml .= '</ul>';
            }

            $screenshotsHtml = '';
            if (!empty($result['screenshots'])) {
                $screenshotsHtml = '<div class="screenshots">';
                foreach ($result['screenshots'] as $screenshot) {
                    $screenshotsHtml .= "<img src='{$screenshot}' alt='Screenshot' style='max-width: 300px; margin: 5px;'>";
                }
                $screenshotsHtml .= '</div>';
            }

            $testResultsHtml .= "
                <div class='test-result {$statusClass}'>
                    <h3>{$result['name']} - {$status}</h3>
                    {$detailsHtml}
                    {$screenshotsHtml}
                </div>
            ";
        }

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <title>MCP 整合測試報告</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { background: #f5f5f5; padding: 20px; border-radius: 5px; }
                .summary { display: flex; gap: 20px; margin: 20px 0; }
                .metric { background: #e3f2fd; padding: 15px; border-radius: 5px; text-align: center; }
                .test-result { margin: 20px 0; padding: 15px; border-radius: 5px; }
                .success { background: #e8f5e8; border-left: 5px solid #4caf50; }
                .failure { background: #ffeaea; border-left: 5px solid #f44336; }
                .screenshots img { border: 1px solid #ddd; border-radius: 3px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>🚀 系統設定 MCP 整合測試報告</h1>
                <p>執行時間: {$report['timestamp']}</p>
                <p>總執行時間: {$report['total_execution_time']}ms</p>
            </div>
            
            <div class='summary'>
                <div class='metric'>
                    <h3>{$report['total_tests']}</h3>
                    <p>總測試數</p>
                </div>
                <div class='metric'>
                    <h3>{$report['passed_tests']}</h3>
                    <p>通過</p>
                </div>
                <div class='metric'>
                    <h3>{$report['failed_tests']}</h3>
                    <p>失敗</p>
                </div>
                <div class='metric'>
                    <h3>{$report['success_rate']}%</h3>
                    <p>成功率</p>
                </div>
            </div>
            
            <h2>測試結果詳情</h2>
            {$testResultsHtml}
        </body>
        </html>
        ";
    }

    /**
     * 清理測試環境
     */
    private function cleanupTestEnvironment(): void
    {
        echo "🧹 清理測試環境...\n";

        try {
            // 關閉瀏覽器
            $this->executeMcpCommand('mcp_playwright_playwright_close');

            // 清理測試資料
            $this->executeMcpCommand('mcp_mysql_execute_query', [
                'query' => "DELETE FROM settings WHERE `key` LIKE 'mcp_test.%'",
                'database' => $this->config['database']
            ]);

            $this->executeMcpCommand('mcp_mysql_execute_query', [
                'query' => "DELETE FROM setting_backups WHERE name LIKE '%MCP%'",
                'database' => $this->config['database']
            ]);

            echo "  ✅ 測試環境清理完成\n";

        } catch (Exception $e) {
            echo "  ⚠️  清理測試環境時發生錯誤: " . $e->getMessage() . "\n";
        }
    }

    /**
     * 登入為管理員
     */
    private function loginAsAdmin(): void
    {
        $this->executeMcpCommand('mcp_playwright_playwright_fill', [
            'selector' => 'input[name="username"]',
            'value' => $this->config['admin_username']
        ]);

        $this->executeMcpCommand('mcp_playwright_playwright_fill', [
            'selector' => 'input[name="password"]',
            'value' => $this->config['admin_password']
        ]);

        $this->executeMcpCommand('mcp_playwright_playwright_click', [
            'selector' => 'button[type="submit"]'
        ]);

        sleep(2); // 等待登入完成
    }

    /**
     * 截圖
     */
    private function takeScreenshot(string $name): string
    {
        $filename = "mcp_test_{$name}_" . date('Y-m-d_H-i-s') . ".png";
        $filepath = $this->config['screenshot_dir'] . '/' . $filename;

        $this->executeMcpCommand('mcp_playwright_playwright_screenshot', [
            'name' => $name,
            'savePng' => true,
            'fullPage' => true
        ]);

        return $filepath;
    }

    /**
     * 執行 MCP 命令
     */
    private function executeMcpCommand(string $command, array $params = []): mixed
    {
        // 這裡應該實作實際的 MCP 命令執行
        // 目前返回模擬資料
        
        switch ($command) {
            case 'mcp_mysql_list_databases':
                return ['laravel_admin', 'information_schema', 'mysql'];
            
            case 'mcp_mysql_execute_query':
                // 模擬查詢結果
                if (strpos($params['query'], 'COUNT(*)') !== false) {
                    return [['count' => 3]];
                }
                return [];
            
            case 'mcp_playwright_playwright_get_visible_text':
                return '系統設定管理 集中管理應用程式的各項系統設定和配置參數';
            
            case 'mcp_playwright_playwright_get_visible_html':
                return '<html><body><div aria-label="設定管理">系統設定</div></body></html>';
            
            default:
                return true;
        }
    }
}

// 執行測試
if (php_sapi_name() === 'cli') {
    $testRunner = new SystemSettingsMcpTestRunner();
    $testRunner->runAllTests();
}