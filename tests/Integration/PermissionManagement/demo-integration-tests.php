#!/usr/bin/env php
<?php

/**
 * 權限管理整合測試示範腳本
 * 
 * 此腳本示範如何使用 MCP 工具進行權限管理的整合測試
 * 包含 Playwright 瀏覽器自動化和 MySQL 資料庫驗證
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

class PermissionManagementIntegrationDemo
{
    protected string $baseUrl;
    protected array $testResults = [];

    public function __construct()
    {
        $this->baseUrl = 'http://localhost';
        $this->setupEnvironment();
    }

    public function run(): void
    {
        $this->displayHeader();
        
        try {
            // 1. 資料庫狀態檢查
            $this->checkDatabaseState();
            
            // 2. 瀏覽器自動化測試
            $this->runBrowserTests();
            
            // 3. 資料驗證
            $this->verifyDataIntegrity();
            
            // 4. 生成報告
            $this->generateReport();
            
        } catch (\Exception $e) {
            $this->output("錯誤: " . $e->getMessage() . "\n", 'error');
            exit(1);
        }
    }

    protected function setupEnvironment(): void
    {
        // 載入 Laravel 應用程式
        $app = require __DIR__ . '/../../../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    }

    protected function displayHeader(): void
    {
        $this->output("
╔══════════════════════════════════════════════════════════════╗
║              權限管理整合測試示範                              ║
║                                                              ║
║  此示範展示如何使用 MCP 工具進行完整的整合測試：               ║
║  • Playwright MCP - 瀏覽器自動化測試                         ║
║  • MySQL MCP - 資料庫狀態驗證                                ║
║  • 完整的端到端工作流程測試                                   ║
║                                                              ║
╚══════════════════════════════════════════════════════════════╝

", 'info');
    }

    protected function checkDatabaseState(): void
    {
        $this->output("1. 檢查資料庫狀態...\n", 'info');

        try {
            // 檢查權限表結構
            $this->output("   檢查權限表結構...\n");
            $tableStructure = mcp_mysql_describe_table([
                'table' => 'permissions',
                'database' => 'laravel_admin'
            ]);
            
            if (empty($tableStructure)) {
                throw new \Exception('權限表不存在或無法存取');
            }
            
            $this->output("   ✓ 權限表結構正常\n", 'success');

            // 檢查現有權限數量
            $this->output("   檢查現有權限...\n");
            $permissionCount = mcp_mysql_execute_query([
                'query' => 'SELECT COUNT(*) as count FROM permissions',
                'database' => 'laravel_admin'
            ]);
            
            $count = $permissionCount[0]['count'] ?? 0;
            $this->output("   ✓ 發現 {$count} 個權限\n", 'success');

            // 檢查管理員使用者
            $this->output("   檢查管理員使用者...\n");
            $adminUser = mcp_mysql_execute_query([
                'query' => 'SELECT username, name, is_active FROM users WHERE username = "admin"',
                'database' => 'laravel_admin'
            ]);
            
            if (empty($adminUser)) {
                $this->output("   ⚠ 未找到管理員使用者，將建立測試使用者\n", 'warning');
                $this->createTestUser();
            } else {
                $this->output("   ✓ 管理員使用者存在\n", 'success');
            }

            $this->testResults['database_check'] = 'passed';

        } catch (\Exception $e) {
            $this->output("   ✗ 資料庫檢查失敗: " . $e->getMessage() . "\n", 'error');
            $this->testResults['database_check'] = 'failed';
            throw $e;
        }
    }

    protected function createTestUser(): void
    {
        // 這裡應該建立測試使用者，但為了示範，我們跳過
        $this->output("   建立測試使用者的邏輯應該在這裡實作\n", 'warning');
    }

    protected function runBrowserTests(): void
    {
        $this->output("\n2. 執行瀏覽器自動化測試...\n", 'info');

        try {
            // 啟動瀏覽器
            $this->output("   啟動瀏覽器...\n");
            mcp_playwright_playwright_navigate([
                'url' => $this->baseUrl,
                'headless' => true,
                'width' => 1280,
                'height' => 720
            ]);
            
            $this->output("   ✓ 瀏覽器已啟動\n", 'success');

            // 導航到登入頁面
            $this->output("   導航到登入頁面...\n");
            mcp_playwright_playwright_navigate([
                'url' => $this->baseUrl . '/admin/login'
            ]);
            
            // 截圖登入頁面
            mcp_playwright_playwright_screenshot([
                'name' => 'demo-login-page',
                'savePng' => true
            ]);
            
            $this->output("   ✓ 已截圖登入頁面\n", 'success');

            // 檢查頁面內容
            $pageText = mcp_playwright_playwright_get_visible_text();
            if (strpos($pageText, '登入') !== false || strpos($pageText, 'Login') !== false) {
                $this->output("   ✓ 登入頁面載入正常\n", 'success');
            } else {
                $this->output("   ⚠ 登入頁面內容可能不正確\n", 'warning');
            }

            // 嘗試登入（如果有測試使用者）
            $this->attemptLogin();

            // 測試權限頁面存取
            $this->testPermissionPageAccess();

            $this->testResults['browser_tests'] = 'passed';

        } catch (\Exception $e) {
            $this->output("   ✗ 瀏覽器測試失敗: " . $e->getMessage() . "\n", 'error');
            $this->testResults['browser_tests'] = 'failed';
        } finally {
            // 確保關閉瀏覽器
            try {
                mcp_playwright_playwright_close();
                $this->output("   ✓ 瀏覽器已關閉\n", 'success');
            } catch (\Exception $e) {
                $this->output("   ⚠ 關閉瀏覽器時發生錯誤\n", 'warning');
            }
        }
    }

    protected function attemptLogin(): void
    {
        $this->output("   嘗試登入...\n");
        
        try {
            // 填寫登入表單
            mcp_playwright_playwright_fill([
                'selector' => 'input[name="username"], input[name="email"]',
                'value' => 'admin'
            ]);

            mcp_playwright_playwright_fill([
                'selector' => 'input[name="password"]',
                'value' => 'password123'
            ]);

            // 提交表單
            mcp_playwright_playwright_click([
                'selector' => 'button[type="submit"], input[type="submit"]'
            ]);

            // 等待登入處理
            sleep(3);

            // 截圖登入後狀態
            mcp_playwright_playwright_screenshot([
                'name' => 'demo-after-login',
                'savePng' => true
            ]);

            // 檢查是否登入成功
            $pageText = mcp_playwright_playwright_get_visible_text();
            if (strpos($pageText, '管理後台') !== false || strpos($pageText, 'Dashboard') !== false) {
                $this->output("   ✓ 登入成功\n", 'success');
            } else {
                $this->output("   ⚠ 登入狀態不確定\n", 'warning');
            }

        } catch (\Exception $e) {
            $this->output("   ⚠ 登入過程中發生錯誤: " . $e->getMessage() . "\n", 'warning');
        }
    }

    protected function testPermissionPageAccess(): void
    {
        $this->output("   測試權限頁面存取...\n");
        
        try {
            // 導航到權限管理頁面
            mcp_playwright_playwright_navigate([
                'url' => $this->baseUrl . '/admin/permissions'
            ]);

            sleep(2);

            // 截圖權限頁面
            mcp_playwright_playwright_screenshot([
                'name' => 'demo-permissions-page',
                'savePng' => true
            ]);

            // 檢查頁面內容
            $pageText = mcp_playwright_playwright_get_visible_text();
            if (strpos($pageText, '權限') !== false || strpos($pageText, 'Permission') !== false) {
                $this->output("   ✓ 權限頁面載入正常\n", 'success');
            } else {
                $this->output("   ⚠ 權限頁面內容可能不正確\n", 'warning');
            }

            // 獲取頁面 HTML 進行更詳細的檢查
            $html = mcp_playwright_playwright_get_visible_html([
                'removeScripts' => true,
                'maxLength' => 10000
            ]);

            if (strpos($html, 'table') !== false || strpos($html, 'permissions') !== false) {
                $this->output("   ✓ 發現權限相關的 HTML 元素\n", 'success');
            }

        } catch (\Exception $e) {
            $this->output("   ⚠ 權限頁面測試中發生錯誤: " . $e->getMessage() . "\n", 'warning');
        }
    }

    protected function verifyDataIntegrity(): void
    {
        $this->output("\n3. 驗證資料完整性...\n", 'info');

        try {
            // 檢查權限和角色的關聯
            $this->output("   檢查權限角色關聯...\n");
            $rolePermissions = mcp_mysql_execute_query([
                'query' => 'SELECT r.name as role_name, p.name as permission_name FROM roles r JOIN role_permissions rp ON r.id = rp.role_id JOIN permissions p ON rp.permission_id = p.id LIMIT 10',
                'database' => 'laravel_admin'
            ]);

            if (!empty($rolePermissions)) {
                $this->output("   ✓ 發現 " . count($rolePermissions) . " 個角色權限關聯\n", 'success');
                
                // 顯示一些範例
                foreach (array_slice($rolePermissions, 0, 3) as $rp) {
                    $this->output("     - {$rp['role_name']} -> {$rp['permission_name']}\n");
                }
            } else {
                $this->output("   ⚠ 未發現角色權限關聯\n", 'warning');
            }

            // 檢查使用者角色關聯
            $this->output("   檢查使用者角色關聯...\n");
            $userRoles = mcp_mysql_execute_query([
                'query' => 'SELECT u.username, r.name as role_name FROM users u JOIN user_roles ur ON u.id = ur.user_id JOIN roles r ON ur.role_id = r.id LIMIT 5',
                'database' => 'laravel_admin'
            ]);

            if (!empty($userRoles)) {
                $this->output("   ✓ 發現 " . count($userRoles) . " 個使用者角色關聯\n", 'success');
                
                foreach ($userRoles as $ur) {
                    $this->output("     - {$ur['username']} -> {$ur['role_name']}\n");
                }
            } else {
                $this->output("   ⚠ 未發現使用者角色關聯\n", 'warning');
            }

            // 檢查權限依賴關係
            $this->output("   檢查權限依賴關係...\n");
            $dependencies = mcp_mysql_execute_query([
                'query' => 'SELECT COUNT(*) as count FROM permission_dependencies',
                'database' => 'laravel_admin'
            ]);

            $depCount = $dependencies[0]['count'] ?? 0;
            if ($depCount > 0) {
                $this->output("   ✓ 發現 {$depCount} 個權限依賴關係\n", 'success');
            } else {
                $this->output("   ℹ 未發現權限依賴關係（這是正常的）\n", 'info');
            }

            $this->testResults['data_integrity'] = 'passed';

        } catch (\Exception $e) {
            $this->output("   ✗ 資料完整性檢查失敗: " . $e->getMessage() . "\n", 'error');
            $this->testResults['data_integrity'] = 'failed';
        }
    }

    protected function generateReport(): void
    {
        $this->output("\n4. 生成測試報告...\n", 'info');

        $totalTests = count($this->testResults);
        $passedTests = count(array_filter($this->testResults, fn($result) => $result === 'passed'));
        $failedTests = $totalTests - $passedTests;
        $successRate = $totalTests > 0 ? ($passedTests / $totalTests) * 100 : 0;

        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'summary' => [
                'total_tests' => $totalTests,
                'passed' => $passedTests,
                'failed' => $failedTests,
                'success_rate' => $successRate
            ],
            'test_results' => $this->testResults,
            'screenshots' => [
                'demo-login-page.png',
                'demo-after-login.png',
                'demo-permissions-page.png'
            ]
        ];

        // 儲存 JSON 報告
        $reportPath = storage_path('logs/permission-integration-demo-' . date('Y-m-d-H-i-s') . '.json');
        file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->output("\n=== 測試報告摘要 ===\n", 'info');
        $this->output("執行時間: " . date('Y-m-d H:i:s') . "\n");
        $this->output("總測試數: {$totalTests}\n");
        $this->output("通過: {$passedTests}\n");
        $this->output("失敗: {$failedTests}\n");
        $this->output("成功率: " . number_format($successRate, 1) . "%\n");
        $this->output("報告檔案: {$reportPath}\n");

        // 顯示詳細結果
        $this->output("\n=== 詳細結果 ===\n", 'info');
        foreach ($this->testResults as $test => $result) {
            $icon = $result === 'passed' ? '✓' : '✗';
            $color = $result === 'passed' ? 'success' : 'error';
            $this->output("{$icon} {$test}: {$result}\n", $color);
        }

        $this->output("\n=== 截圖檔案 ===\n", 'info');
        foreach ($report['screenshots'] as $screenshot) {
            $this->output("📷 {$screenshot}\n");
        }

        $this->output("\n測試示範完成！\n", 'success');
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

// 檢查是否有必要的 MCP 函數
if (!function_exists('mcp_mysql_execute_query')) {
    echo "錯誤: MySQL MCP 工具不可用。請確保 MCP 伺服器已正確配置。\n";
    exit(1);
}

if (!function_exists('mcp_playwright_playwright_navigate')) {
    echo "錯誤: Playwright MCP 工具不可用。請確保 MCP 伺服器已正確配置。\n";
    exit(1);
}

// 執行示範
$demo = new PermissionManagementIntegrationDemo();
$demo->run();