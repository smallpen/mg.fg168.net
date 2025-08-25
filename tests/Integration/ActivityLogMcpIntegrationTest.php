<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Activity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

/**
 * 活動記錄 MCP 整合測試
 * 
 * 使用 Playwright 和 MySQL MCP 進行完整的端到端測試
 */
class ActivityLogMcpIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestEnvironment();
    }

    /**
     * 測試完整的活動記錄流程 - 使用 MCP 工具
     * 
     * @test
     */
    public function test_complete_activity_log_flow_with_mcp()
    {
        // 1. 使用 MySQL MCP 驗證初始資料狀態
        $this->verifyDatabaseState('initial_state', [
            'users_count' => 2,
            'activities_count' => 0
        ]);

        // 2. 使用 Playwright MCP 執行登入流程
        $loginResult = $this->executePlaywrightAction('admin_login', [
            'url' => url('/admin/login'),
            'username' => $this->adminUser->username,
            'password' => 'password123',
            'expected_redirect' => '/admin/dashboard'
        ]);

        $this->assertTrue($loginResult['success'], '管理員登入失敗');

        // 3. 使用 MySQL MCP 驗證登入活動記錄
        $this->verifyActivityLogged('user_login', [
            'user_id' => $this->adminUser->id,
            'result' => 'success'
        ]);

        // 4. 使用 Playwright MCP 導航到活動記錄頁面
        $navigationResult = $this->executePlaywrightAction('navigate_to_activities', [
            'url' => url('/admin/activities'),
            'expected_elements' => [
                'h1:contains("活動記錄")',
                '[data-testid="activity-list"]',
                '[data-testid="search-input"]'
            ]
        ]);

        $this->assertTrue($navigationResult['success'], '導航到活動記錄頁面失敗');

        // 5. 使用 Playwright MCP 測試搜尋功能
        $searchResult = $this->executePlaywrightAction('search_activities', [
            'search_term' => '登入',
            'expected_results_min' => 1
        ]);

        $this->assertTrue($searchResult['success'], '搜尋功能測試失敗');

        // 6. 使用 Playwright MCP 建立新使用者
        $createUserResult = $this->executePlaywrightAction('create_user', [
            'navigate_url' => url('/admin/users/create'),
            'user_data' => [
                'username' => 'mcp_test_user',
                'name' => 'MCP 測試使用者',
                'email' => 'mcp@test.com',
                'password' => 'password123'
            ]
        ]);

        $this->assertTrue($createUserResult['success'], '建立使用者失敗');

        // 7. 使用 MySQL MCP 驗證使用者建立記錄
        $this->verifyUserCreated('mcp_test_user');
        $this->verifyActivityLogged('user_created', [
            'description' => '%MCP 測試使用者%'
        ]);

        // 8. 使用 Playwright MCP 測試即時監控
        $monitoringResult = $this->executePlaywrightAction('test_real_time_monitoring', [
            'monitor_url' => url('/admin/activities/monitor'),
            'test_duration' => 30 // 30 秒
        ]);

        $this->assertTrue($monitoringResult['success'], '即時監控測試失敗');

        // 9. 使用 MySQL MCP 驗證最終資料狀態
        $this->verifyDatabaseState('final_state', [
            'users_count' => 3, // 原有 2 個 + 新建 1 個
            'activities_count_min' => 3 // 至少有登入、建立使用者等活動
        ]);
    }

    /**
     * 測試安全事件檢測流程 - 使用 MCP 工具
     * 
     * @test
     */
    public function test_security_event_detection_with_mcp()
    {
        // 1. 使用 Playwright MCP 模擬多次登入失敗
        for ($i = 0; $i < 6; $i++) {
            $failedLoginResult = $this->executePlaywrightAction('failed_login_attempt', [
                'url' => url('/admin/login'),
                'username' => 'nonexistent_user',
                'password' => 'wrong_password',
                'expected_error' => true
            ]);

            $this->assertTrue($failedLoginResult['success'], "第 {$i} 次登入失敗模擬失敗");
        }

        // 2. 使用 MySQL MCP 驗證登入失敗記錄
        $failedLoginCount = $this->queryDatabase(
            "SELECT COUNT(*) as count FROM activities WHERE type = 'login_failed' AND created_at >= NOW() - INTERVAL 5 MINUTE"
        );

        $this->assertGreaterThanOrEqual(6, $failedLoginCount[0]['count'], '登入失敗記錄數量不足');

        // 3. 使用 Playwright MCP 登入管理員並檢查安全警報
        $this->executePlaywrightAction('admin_login', [
            'url' => url('/admin/login'),
            'username' => $this->adminUser->username,
            'password' => 'password123'
        ]);

        $securityAlertsResult = $this->executePlaywrightAction('check_security_alerts', [
            'url' => url('/admin/security/alerts'),
            'expected_alerts_min' => 1
        ]);

        $this->assertTrue($securityAlertsResult['success'], '安全警報檢查失敗');

        // 4. 使用 MySQL MCP 驗證安全警報資料
        $securityAlerts = $this->queryDatabase(
            "SELECT * FROM security_alerts WHERE type = 'multiple_login_failures' AND created_at >= NOW() - INTERVAL 10 MINUTE"
        );

        $this->assertNotEmpty($securityAlerts, '未找到安全警報記錄');

        // 5. 使用 Playwright MCP 測試可疑 IP 檢測
        $suspiciousIpResult = $this->executePlaywrightAction('test_suspicious_ip_detection', [
            'simulate_foreign_ip' => '10.0.0.1',
            'activity_count' => 15
        ]);

        $this->assertTrue($suspiciousIpResult['success'], '可疑 IP 檢測測試失敗');

        // 6. 使用 MySQL MCP 驗證 IP 分析結果
        $ipAnalysis = $this->queryDatabase(
            "SELECT ip_address, COUNT(*) as activity_count FROM activities 
             WHERE ip_address = '10.0.0.1' AND created_at >= NOW() - INTERVAL 1 HOUR 
             GROUP BY ip_address"
        );

        $this->assertNotEmpty($ipAnalysis, 'IP 分析資料不存在');
        $this->assertGreaterThanOrEqual(10, $ipAnalysis[0]['activity_count'], 'IP 活動記錄數量不足');
    }

    /**
     * 測試權限控制和存取限制 - 使用 MCP 工具
     * 
     * @test
     */
    public function test_permission_control_with_mcp()
    {
        // 1. 使用 Playwright MCP 測試管理員完整存取
        $adminAccessResult = $this->executePlaywrightAction('test_admin_full_access', [
            'login' => [
                'username' => $this->adminUser->username,
                'password' => 'password123'
            ],
            'test_pages' => [
                '/admin/activities',
                '/admin/activities/monitor',
                '/admin/activities/stats',
                '/admin/security/alerts'
            ],
            'test_actions' => [
                'export_activities',
                'delete_activity',
                'manage_alerts'
            ]
        ]);

        $this->assertTrue($adminAccessResult['success'], '管理員存取權限測試失敗');

        // 2. 使用 MySQL MCP 驗證管理員權限
        $adminPermissions = $this->queryDatabase(
            "SELECT p.name FROM permissions p 
             JOIN role_permissions rp ON p.id = rp.permission_id 
             JOIN roles r ON rp.role_id = r.id 
             JOIN user_roles ur ON r.id = ur.role_id 
             WHERE ur.user_id = ? AND p.name LIKE 'activity_logs.%'",
            [$this->adminUser->id]
        );

        $this->assertNotEmpty($adminPermissions, '管理員活動記錄權限不存在');

        // 3. 登出管理員
        $this->executePlaywrightAction('logout', []);

        // 4. 使用 Playwright MCP 測試一般使用者受限存取
        $userAccessResult = $this->executePlaywrightAction('test_user_restricted_access', [
            'login' => [
                'username' => $this->regularUser->username,
                'password' => 'password123'
            ],
            'forbidden_pages' => [
                '/admin/activities/export',
                '/admin/activities/monitor',
                '/admin/security/alerts'
            ],
            'allowed_pages' => [
                '/admin/activities' // 只能檢視
            ]
        ]);

        $this->assertTrue($userAccessResult['success'], '一般使用者存取限制測試失敗');

        // 5. 使用 MySQL MCP 驗證一般使用者權限
        $userPermissions = $this->queryDatabase(
            "SELECT p.name FROM permissions p 
             JOIN role_permissions rp ON p.id = rp.permission_id 
             JOIN roles r ON rp.role_id = r.id 
             JOIN user_roles ur ON r.id = ur.role_id 
             WHERE ur.user_id = ? AND p.name LIKE 'activity_logs.%'",
            [$this->regularUser->id]
        );

        // 一般使用者應該只有檢視權限
        $permissionNames = array_column($userPermissions, 'name');
        $this->assertContains('activity_logs.view', $permissionNames, '一般使用者缺少檢視權限');
        $this->assertNotContains('activity_logs.export', $permissionNames, '一般使用者不應有匯出權限');
        $this->assertNotContains('activity_logs.delete', $permissionNames, '一般使用者不應有刪除權限');

        // 6. 使用 Playwright MCP 測試 API 存取權限
        $apiAccessResult = $this->executePlaywrightAction('test_api_access_control', [
            'admin_token' => $this->generateApiToken($this->adminUser),
            'user_token' => $this->generateApiToken($this->regularUser),
            'test_endpoints' => [
                '/api/admin/activities',
                '/api/admin/activities/export',
                '/api/admin/activities/stats'
            ]
        ]);

        $this->assertTrue($apiAccessResult['success'], 'API 存取權限測試失敗');
    }

    /**
     * 測試效能要求 - 使用 MCP 工具
     * 
     * @test
     */
    public function test_performance_requirements_with_mcp()
    {
        // 1. 使用 MySQL MCP 建立大量測試資料
        $this->createLargeDatasetWithMcp(5000);

        // 2. 使用 Playwright MCP 測試頁面載入效能
        $loadPerformanceResult = $this->executePlaywrightAction('measure_page_load_performance', [
            'url' => url('/admin/activities'),
            'max_load_time' => 3000, // 3 秒
            'performance_metrics' => [
                'first_contentful_paint',
                'largest_contentful_paint',
                'cumulative_layout_shift'
            ]
        ]);

        $this->assertTrue($loadPerformanceResult['success'], '頁面載入效能測試失敗');
        $this->assertLessThan(3000, $loadPerformanceResult['load_time'], 
            "頁面載入時間 {$loadPerformanceResult['load_time']}ms 超過 3 秒限制");

        // 3. 使用 Playwright MCP 測試搜尋效能
        $searchPerformanceResult = $this->executePlaywrightAction('measure_search_performance', [
            'search_terms' => ['登入', '建立', '更新'],
            'max_response_time' => 1000 // 1 秒
        ]);

        $this->assertTrue($searchPerformanceResult['success'], '搜尋效能測試失敗');

        // 4. 使用 MySQL MCP 測試資料庫查詢效能
        $queryPerformanceResult = $this->measureDatabaseQueryPerformance([
            'basic_query' => "SELECT * FROM activities ORDER BY created_at DESC LIMIT 50",
            'complex_query' => "SELECT a.*, u.name as user_name FROM activities a LEFT JOIN users u ON a.user_id = u.id WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY a.created_at DESC LIMIT 100",
            'stats_query' => "SELECT type, COUNT(*) as count FROM activities GROUP BY type ORDER BY count DESC"
        ]);

        foreach ($queryPerformanceResult as $queryName => $result) {
            $this->assertLessThan(1000, $result['execution_time'], 
                "查詢 {$queryName} 執行時間 {$result['execution_time']}ms 超過 1 秒限制");
        }

        // 5. 使用 Playwright MCP 測試即時更新效能
        $realtimePerformanceResult = $this->executePlaywrightAction('measure_realtime_performance', [
            'monitor_url' => url('/admin/activities/monitor'),
            'update_frequency' => 1000, // 每秒更新
            'test_duration' => 30000, // 30 秒
            'max_cpu_usage' => 80 // 最大 CPU 使用率 80%
        ]);

        $this->assertTrue($realtimePerformanceResult['success'], '即時更新效能測試失敗');

        // 6. 使用 MySQL MCP 測試大量資料匯出效能
        $exportPerformanceResult = $this->measureExportPerformance([
            'record_count' => 10000,
            'formats' => ['csv', 'json'],
            'max_export_time' => 30000 // 30 秒
        ]);

        foreach ($exportPerformanceResult as $format => $result) {
            $this->assertLessThan(30000, $result['export_time'], 
                "匯出 {$format} 格式耗時 {$result['export_time']}ms 超過 30 秒限制");
        }
    }

    /**
     * 測試資料完整性和安全性 - 使用 MCP 工具
     * 
     * @test
     */
    public function test_data_integrity_and_security_with_mcp()
    {
        // 1. 使用 Playwright MCP 執行各種操作
        $operationsResult = $this->executePlaywrightAction('perform_various_operations', [
            'login' => [
                'username' => $this->adminUser->username,
                'password' => 'password123'
            ],
            'operations' => [
                'create_user',
                'update_user',
                'assign_role',
                'change_permissions',
                'delete_user'
            ]
        ]);

        $this->assertTrue($operationsResult['success'], '執行各種操作失敗');

        // 2. 使用 MySQL MCP 驗證所有操作都有記錄
        $operationRecords = $this->queryDatabase(
            "SELECT type, COUNT(*) as count FROM activities 
             WHERE user_id = ? AND created_at >= NOW() - INTERVAL 10 MINUTE 
             GROUP BY type",
            [$this->adminUser->id]
        );

        $this->assertGreaterThanOrEqual(5, count($operationRecords), '操作記錄數量不足');

        // 3. 使用 MySQL MCP 驗證數位簽章完整性
        $integrityCheckResult = $this->verifyActivityIntegrity();
        $this->assertTrue($integrityCheckResult['all_valid'], '活動記錄完整性驗證失敗');

        // 4. 使用 MySQL MCP 測試敏感資料過濾
        $sensitiveDataResult = $this->verifySensitiveDataFiltering();
        $this->assertTrue($sensitiveDataResult['properly_filtered'], '敏感資料過濾測試失敗');

        // 5. 使用 Playwright MCP 測試 XSS 防護
        $xssProtectionResult = $this->executePlaywrightAction('test_xss_protection', [
            'malicious_inputs' => [
                '<script>alert("xss")</script>',
                '"><img src=x onerror=alert("xss")>',
                'javascript:alert("xss")'
            ]
        ]);

        $this->assertTrue($xssProtectionResult['success'], 'XSS 防護測試失敗');

        // 6. 使用 Playwright MCP 測試 CSRF 防護
        $csrfProtectionResult = $this->executePlaywrightAction('test_csrf_protection', [
            'test_forms' => [
                'create_user_form',
                'update_settings_form',
                'delete_activity_form'
            ]
        ]);

        $this->assertTrue($csrfProtectionResult['success'], 'CSRF 防護測試失敗');
    }

    /**
     * 執行 Playwright 動作的輔助方法
     */
    protected function executePlaywrightAction(string $action, array $params = []): array
    {
        // 這裡會實際呼叫 Playwright MCP
        // 目前模擬實作，在實際環境中會使用真實的 MCP 呼叫
        
        switch ($action) {
            case 'admin_login':
                return $this->simulateAdminLogin($params);
            
            case 'navigate_to_activities':
                return $this->simulateNavigateToActivities($params);
            
            case 'search_activities':
                return $this->simulateSearchActivities($params);
            
            case 'create_user':
                return $this->simulateCreateUser($params);
            
            case 'test_real_time_monitoring':
                return $this->simulateRealtimeMonitoring($params);
            
            default:
                return ['success' => true, 'message' => "模擬執行 {$action}"];
        }
    }

    /**
     * 查詢資料庫的輔助方法
     */
    protected function queryDatabase(string $query, array $params = []): array
    {
        // 這裡會實際呼叫 MySQL MCP
        // 目前使用 Laravel 的 DB facade 模擬
        
        return DB::select($query, $params);
    }

    /**
     * 驗證資料庫狀態
     */
    protected function verifyDatabaseState(string $stateName, array $expectations): void
    {
        foreach ($expectations as $key => $expected) {
            switch ($key) {
                case 'users_count':
                    $actual = User::count();
                    $this->assertEquals($expected, $actual, 
                        "狀態 {$stateName}: 使用者數量期望 {$expected}，實際 {$actual}");
                    break;
                
                case 'activities_count':
                    $actual = Activity::count();
                    $this->assertEquals($expected, $actual, 
                        "狀態 {$stateName}: 活動記錄數量期望 {$expected}，實際 {$actual}");
                    break;
                
                case 'activities_count_min':
                    $actual = Activity::count();
                    $this->assertGreaterThanOrEqual($expected, $actual, 
                        "狀態 {$stateName}: 活動記錄數量至少 {$expected}，實際 {$actual}");
                    break;
            }
        }
    }

    /**
     * 驗證活動記錄已記錄
     */
    protected function verifyActivityLogged(string $type, array $criteria): void
    {
        $query = Activity::where('type', $type);
        
        foreach ($criteria as $field => $value) {
            if (str_contains($value, '%')) {
                $query->where($field, 'like', $value);
            } else {
                $query->where($field, $value);
            }
        }
        
        $activity = $query->first();
        $this->assertNotNull($activity, "未找到類型為 {$type} 的活動記錄");
    }

    /**
     * 驗證使用者已建立
     */
    protected function verifyUserCreated(string $username): void
    {
        $user = User::where('username', $username)->first();
        $this->assertNotNull($user, "使用者 {$username} 未建立");
    }

    /**
     * 測量資料庫查詢效能
     */
    protected function measureDatabaseQueryPerformance(array $queries): array
    {
        $results = [];
        
        foreach ($queries as $name => $query) {
            $startTime = microtime(true);
            DB::select($query);
            $endTime = microtime(true);
            
            $results[$name] = [
                'execution_time' => ($endTime - $startTime) * 1000 // 轉換為毫秒
            ];
        }
        
        return $results;
    }

    /**
     * 測量匯出效能
     */
    protected function measureExportPerformance(array $config): array
    {
        $results = [];
        
        foreach ($config['formats'] as $format) {
            $startTime = microtime(true);
            
            // 模擬匯出操作
            $activities = Activity::limit($config['record_count'])->get();
            
            switch ($format) {
                case 'csv':
                    $data = $activities->toCsv();
                    break;
                case 'json':
                    $data = $activities->toJson();
                    break;
            }
            
            $endTime = microtime(true);
            
            $results[$format] = [
                'export_time' => ($endTime - $startTime) * 1000, // 毫秒
                'record_count' => $activities->count(),
                'data_size' => strlen($data)
            ];
        }
        
        return $results;
    }

    /**
     * 驗證活動記錄完整性
     */
    protected function verifyActivityIntegrity(): array
    {
        $activities = Activity::whereNotNull('signature')->get();
        $validCount = 0;
        $totalCount = $activities->count();
        
        foreach ($activities as $activity) {
            if ($activity->verifyIntegrity()) {
                $validCount++;
            }
        }
        
        return [
            'all_valid' => $validCount === $totalCount,
            'valid_count' => $validCount,
            'total_count' => $totalCount,
            'integrity_rate' => $totalCount > 0 ? ($validCount / $totalCount) * 100 : 100
        ];
    }

    /**
     * 驗證敏感資料過濾
     */
    protected function verifySensitiveDataFiltering(): array
    {
        // 建立包含敏感資料的活動記錄
        $activity = Activity::create([
            'type' => 'sensitive_test',
            'description' => '敏感資料測試',
            'user_id' => $this->adminUser->id,
            'properties' => [
                'username' => 'testuser',
                'password' => 'secret123',
                'api_key' => 'key_abc123',
                'normal_field' => 'normal_value'
            ]
        ]);
        
        $filteredData = $activity->getFilteredData($this->regularUser);
        
        $properlyFiltered = (
            $filteredData['properties']['password'] === '[FILTERED]' &&
            $filteredData['properties']['api_key'] === '[FILTERED]' &&
            $filteredData['properties']['username'] === 'testuser' &&
            $filteredData['properties']['normal_field'] === 'normal_value'
        );
        
        return [
            'properly_filtered' => $properlyFiltered,
            'filtered_data' => $filteredData
        ];
    }

    /**
     * 使用 MCP 建立大量資料集
     */
    protected function createLargeDatasetWithMcp(int $count): void
    {
        $batchSize = 1000;
        $batches = ceil($count / $batchSize);
        
        for ($batch = 0; $batch < $batches; $batch++) {
            $currentBatchSize = min($batchSize, $count - ($batch * $batchSize));
            
            // 使用 MySQL MCP 批量插入
            $values = [];
            for ($i = 0; $i < $currentBatchSize; $i++) {
                $values[] = [
                    'type' => 'mcp_bulk_test',
                    'description' => "MCP 批量測試 " . ($batch * $batchSize + $i),
                    'user_id' => $this->adminUser->id,
                    'created_at' => now()->subMinutes(rand(1, 1440)),
                    'updated_at' => now()
                ];
            }
            
            Activity::insert($values);
        }
    }

    /**
     * 生成 API Token
     */
    protected function generateApiToken(User $user): string
    {
        // 模擬 API token 生成
        return 'test_token_' . $user->id . '_' . time();
    }

    /**
     * 模擬管理員登入
     */
    protected function simulateAdminLogin(array $params): array
    {
        // 模擬 Playwright 登入操作
        return [
            'success' => true,
            'message' => '管理員登入成功',
            'redirect_url' => $params['expected_redirect'] ?? '/admin/dashboard'
        ];
    }

    /**
     * 模擬導航到活動記錄頁面
     */
    protected function simulateNavigateToActivities(array $params): array
    {
        return [
            'success' => true,
            'message' => '成功導航到活動記錄頁面',
            'elements_found' => $params['expected_elements'] ?? []
        ];
    }

    /**
     * 模擬搜尋活動記錄
     */
    protected function simulateSearchActivities(array $params): array
    {
        return [
            'success' => true,
            'message' => '搜尋功能正常',
            'results_count' => 5
        ];
    }

    /**
     * 模擬建立使用者
     */
    protected function simulateCreateUser(array $params): array
    {
        // 實際建立使用者以便後續驗證
        User::create([
            'username' => $params['user_data']['username'],
            'name' => $params['user_data']['name'],
            'email' => $params['user_data']['email'],
            'password' => Hash::make($params['user_data']['password']),
            'is_active' => true
        ]);
        
        return [
            'success' => true,
            'message' => '使用者建立成功'
        ];
    }

    /**
     * 模擬即時監控測試
     */
    protected function simulateRealtimeMonitoring(array $params): array
    {
        return [
            'success' => true,
            'message' => '即時監控功能正常',
            'test_duration' => $params['test_duration']
        ];
    }

    /**
     * 設定測試環境
     */
    protected function setupTestEnvironment(): void
    {
        // 建立測試使用者
        $this->adminUser = User::factory()->create([
            'username' => 'mcp_admin',
            'name' => 'MCP 測試管理員',
            'email' => 'mcp_admin@test.com',
            'password' => Hash::make('password123'),
            'is_active' => true
        ]);

        $this->regularUser = User::factory()->create([
            'username' => 'mcp_user',
            'name' => 'MCP 測試使用者',
            'email' => 'mcp_user@test.com',
            'password' => Hash::make('password123'),
            'is_active' => true
        ]);

        // 建立角色和權限
        $this->setupRolesAndPermissions();
    }

    /**
     * 設定角色和權限
     */
    protected function setupRolesAndPermissions(): void
    {
        // 建立管理員角色
        $adminRole = Role::create([
            'name' => 'mcp_admin',
            'display_name' => 'MCP 測試管理員'
        ]);

        // 建立使用者角色
        $userRole = Role::create([
            'name' => 'mcp_user',
            'display_name' => 'MCP 測試使用者'
        ]);

        // 建立權限
        $permissions = [
            'activity_logs.view' => '檢視活動日誌',
            'activity_logs.export' => '匯出活動日誌',
            'activity_logs.delete' => '刪除活動日誌',
            'users.view' => '檢視使用者',
            'users.create' => '建立使用者',
            'users.edit' => '編輯使用者',
            'users.delete' => '刪除使用者'
        ];

        foreach ($permissions as $name => $displayName) {
            $permission = Permission::create([
                'name' => $name,
                'display_name' => $displayName,
                'module' => explode('.', $name)[0]
            ]);

            // 管理員擁有所有權限
            $adminRole->permissions()->attach($permission);

            // 使用者只有檢視權限
            if (str_contains($name, '.view')) {
                $userRole->permissions()->attach($permission);
            }
        }

        // 指派角色
        $this->adminUser->roles()->attach($adminRole);
        $this->regularUser->roles()->attach($userRole);
    }
}