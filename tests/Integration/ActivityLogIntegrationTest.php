<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Activity;
use App\Models\SecurityAlert;
use App\Services\ActivityLogger;
use App\Services\SecurityAnalyzer;
use App\Services\ActivityIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * 活動記錄功能整合測試
 * 
 * 測試完整的活動記錄流程，包含安全事件檢測、權限控制、效能要求等
 */
class ActivityLogIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;
    protected User $viewOnlyUser;
    protected Role $adminRole;
    protected Role $userRole;
    protected Role $viewOnlyRole;
    protected ActivityLogger $activityLogger;
    protected SecurityAnalyzer $securityAnalyzer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setupTestUsers();
        $this->setupTestRoles();
        $this->setupTestPermissions();
        
        $this->activityLogger = app(ActivityLogger::class);
        $this->securityAnalyzer = app(SecurityAnalyzer::class);
    }

    /**
     * 測試完整的活動記錄流程
     * 
     * @test
     */
    public function test_complete_activity_logging_flow()
    {
        // 1. 測試使用者登入活動記錄
        $this->actingAs($this->adminUser);
        
        $loginActivity = $this->activityLogger->logLogin($this->adminUser->id, [
            'method' => 'username_password',
            'remember' => false
        ]);
        
        $this->assertDatabaseHas('activities', [
            'id' => $loginActivity->id,
            'type' => 'user_login',
            'user_id' => $this->adminUser->id,
            'result' => 'success'
        ]);

        // 2. 測試使用者操作記錄
        $newUser = User::factory()->create([
            'username' => 'test_user_integration',
            'name' => '整合測試使用者'
        ]);

        $createActivity = $this->activityLogger->logUserAction('user_created', $newUser, [
            'module' => 'users',
            'properties' => [
                'username' => $newUser->username,
                'roles' => []
            ]
        ]);

        $this->assertDatabaseHas('activities', [
            'type' => 'user_created',
            'subject_id' => $newUser->id,
            'subject_type' => User::class,
            'user_id' => $this->adminUser->id
        ]);

        // 3. 測試角色指派記錄
        $newUser->roles()->attach($this->userRole->id);
        
        $roleActivity = $this->activityLogger->logRoleAssigned(
            $newUser->id, 
            [$this->userRole->id],
            ['assigned_by' => $this->adminUser->id]
        );

        $this->assertDatabaseHas('activities', [
            'type' => 'role_assigned',
            'subject_id' => $newUser->id,
            'user_id' => $this->adminUser->id
        ]);

        // 4. 測試安全事件記錄
        $securityActivity = $this->activityLogger->logSecurityEvent(
            'permission_escalation',
            '使用者權限提升',
            [
                'target_user' => $newUser->id,
                'elevated_permissions' => ['users.create', 'users.edit']
            ]
        );

        $this->assertDatabaseHas('activities', [
            'type' => 'permission_escalation',
            'user_id' => $this->adminUser->id,
            'module' => 'security'
        ]);

        // 5. 測試活動記錄完整性
        $this->assertTrue($loginActivity->verifyIntegrity());
        $this->assertTrue($createActivity->verifyIntegrity());
        $this->assertTrue($roleActivity->verifyIntegrity());
        $this->assertTrue($securityActivity->verifyIntegrity());

        // 6. 測試活動記錄查詢
        $recentActivities = Activity::where('user_id', $this->adminUser->id)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->get();

        $this->assertGreaterThanOrEqual(4, $recentActivities->count());
        
        // 7. 測試登出記錄
        $logoutActivity = $this->activityLogger->logLogout($this->adminUser->id, [
            'method' => 'manual'
        ]);

        $this->assertDatabaseHas('activities', [
            'type' => 'user_logout',
            'user_id' => $this->adminUser->id,
            'result' => 'success'
        ]);
    }

    /**
     * 測試安全事件檢測和警報
     * 
     * @test
     */
    public function test_security_event_detection_and_alerts()
    {
        // 1. 測試登入失敗檢測
        for ($i = 0; $i < 6; $i++) {
            $this->activityLogger->logLoginFailed('suspicious_user', [
                'ip_address' => '192.168.1.100',
                'attempt_count' => $i + 1,
                'reason' => 'invalid_credentials'
            ]);
        }

        $failedLoginAlerts = $this->securityAnalyzer->monitorFailedLogins();
        $this->assertNotEmpty($failedLoginAlerts);
        $this->assertEquals('multiple_login_failures', $failedLoginAlerts[0]['type']);

        // 2. 測試可疑 IP 檢測
        $suspiciousIPs = $this->securityAnalyzer->checkSuspiciousIPs();
        $this->assertNotEmpty($suspiciousIPs);

        // 3. 測試批量操作檢測
        $this->actingAs($this->adminUser);
        
        // 模擬批量建立使用者
        for ($i = 0; $i < 12; $i++) {
            $user = User::factory()->create(['username' => "bulk_user_{$i}"]);
            $this->activityLogger->logUserAction('user_created', $user);
        }

        $recentActivities = Activity::where('created_at', '>=', now()->subMinutes(30))->get();
        $anomalies = $this->securityAnalyzer->detectAnomalies($recentActivities);
        
        $bulkOperations = collect($anomalies)->where('type', 'bulk_operations');
        $this->assertNotEmpty($bulkOperations);

        // 4. 測試權限提升檢測
        $this->activityLogger->logSecurityEvent(
            'permission_escalation',
            '異常權限提升',
            ['elevated_to' => 'admin']
        );

        $privilegeEscalation = collect($anomalies)->where('type', 'privilege_escalation');
        $this->assertNotEmpty($privilegeEscalation);

        // 5. 測試風險分數計算
        $highRiskActivity = Activity::create([
            'type' => 'system_config_change',
            'description' => '修改關鍵系統設定',
            'user_id' => $this->adminUser->id,
            'ip_address' => '10.0.0.1', // 外網 IP
            'risk_level' => 8,
            'created_at' => now()->hour(2) // 深夜時間
        ]);

        $riskAnalysis = $this->securityAnalyzer->analyzeActivity($highRiskActivity);
        $this->assertGreaterThanOrEqual(6, $riskAnalysis['risk_score']);
        $this->assertContains($riskAnalysis['risk_level'], ['high', 'critical']);

        // 6. 測試安全報告生成
        $securityReport = $this->securityAnalyzer->generateSecurityReport('1d');
        
        $this->assertArrayHasKey('summary', $securityReport);
        $this->assertArrayHasKey('top_risks', $securityReport);
        $this->assertArrayHasKey('alert_statistics', $securityReport);
        $this->assertArrayHasKey('recommendations', $securityReport);
    }

    /**
     * 測試不同權限使用者的存取控制
     * 
     * @test
     */
    public function test_access_control_for_different_permission_users()
    {
        // 1. 測試管理員權限 - 應該能存取所有活動記錄
        $this->actingAs($this->adminUser);
        
        $adminActivity = Activity::factory()->create([
            'type' => 'sensitive_operation',
            'user_id' => $this->adminUser->id,
            'risk_level' => 8
        ]);

        $this->assertTrue($adminActivity->canAccess($this->adminUser, 'view'));
        $this->assertTrue($adminActivity->canAccess($this->adminUser, 'export'));
        $this->assertTrue($adminActivity->canAccess($this->adminUser, 'delete'));

        // 2. 測試一般使用者權限 - 只能檢視自己的活動記錄
        $userActivity = Activity::factory()->create([
            'type' => 'user_login',
            'user_id' => $this->regularUser->id,
            'risk_level' => 2
        ]);

        $this->assertTrue($userActivity->canAccess($this->regularUser, 'view'));
        $this->assertFalse($userActivity->canAccess($this->regularUser, 'export'));
        $this->assertFalse($userActivity->canAccess($this->regularUser, 'delete'));

        // 一般使用者不能檢視其他人的活動記錄
        $this->assertFalse($adminActivity->canAccess($this->regularUser, 'view'));

        // 3. 測試唯讀使用者權限 - 只能檢視，不能操作
        $this->assertTrue($userActivity->canAccess($this->viewOnlyUser, 'view'));
        $this->assertFalse($userActivity->canAccess($this->viewOnlyUser, 'export'));
        $this->assertFalse($userActivity->canAccess($this->viewOnlyUser, 'delete'));

        // 4. 測試敏感資料過濾
        $sensitiveActivity = Activity::factory()->create([
            'type' => 'password_change',
            'user_id' => $this->regularUser->id,
            'properties' => [
                'old_password' => 'secret123',
                'new_password' => 'newsecret456',
                'username' => $this->regularUser->username
            ]
        ]);

        $filteredData = $sensitiveActivity->getFilteredData($this->regularUser);
        $this->assertEquals('[FILTERED]', $filteredData['properties']['old_password']);
        $this->assertEquals('[FILTERED]', $filteredData['properties']['new_password']);
        $this->assertEquals($this->regularUser->username, $filteredData['properties']['username']);

        // 5. 測試受保護活動記錄
        $protectedActivity = Activity::factory()->create([
            'type' => 'security_audit',
            'user_id' => $this->adminUser->id,
            'risk_level' => 9,
            'created_at' => now()->subDays(5) // 在保護期限內
        ]);

        $this->assertTrue($protectedActivity->isProtected());
        $this->assertFalse($protectedActivity->canAccess($this->regularUser, 'delete'));

        // 6. 測試 API 存取權限
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/admin/activities');
        $response->assertStatus(200);

        $response = $this->actingAs($this->regularUser)
            ->getJson('/api/admin/activities');
        $response->assertStatus(403); // 無權限存取

        // 7. 測試匯出權限
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/admin/activities/export', [
                'format' => 'csv',
                'date_from' => now()->subDays(7)->toDateString(),
                'date_to' => now()->toDateString()
            ]);
        $response->assertStatus(200);

        $response = $this->actingAs($this->regularUser)
            ->postJson('/api/admin/activities/export', [
                'format' => 'csv'
            ]);
        $response->assertStatus(403); // 無匯出權限
    }

    /**
     * 測試效能要求和負載測試
     * 
     * @test
     */
    public function test_performance_requirements_and_load_testing()
    {
        // 1. 測試批量活動記錄效能
        $startTime = microtime(true);
        
        $activities = [];
        for ($i = 0; $i < 1000; $i++) {
            $activities[] = [
                'type' => 'bulk_test',
                'description' => "批量測試活動 {$i}",
                'data' => [
                    'user_id' => $this->adminUser->id,
                    'properties' => ['test_id' => $i]
                ]
            ];
        }

        $this->activityLogger->logBatch($activities);
        
        $batchTime = microtime(true) - $startTime;
        $this->assertLessThan(5.0, $batchTime, '批量記錄 1000 筆活動應在 5 秒內完成');

        // 2. 測試查詢效能
        $startTime = microtime(true);
        
        $results = Activity::where('type', 'bulk_test')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->paginate(50);
        
        $queryTime = microtime(true) - $startTime;
        $this->assertLessThan(1.0, $queryTime, '分頁查詢應在 1 秒內完成');
        $this->assertEquals(50, $results->count());

        // 3. 測試搜尋效能
        $startTime = microtime(true);
        
        $searchResults = Activity::where('description', 'like', '%批量測試%')
            ->limit(100)
            ->get();
        
        $searchTime = microtime(true) - $startTime;
        $this->assertLessThan(2.0, $searchTime, '搜尋查詢應在 2 秒內完成');

        // 4. 測試統計查詢效能
        $startTime = microtime(true);
        
        $stats = [
            'total_count' => Activity::count(),
            'today_count' => Activity::whereDate('created_at', today())->count(),
            'user_count' => Activity::distinct('user_id')->count('user_id'),
            'type_distribution' => Activity::select('type', DB::raw('COUNT(*) as count'))
                ->groupBy('type')
                ->get()
        ];
        
        $statsTime = microtime(true) - $startTime;
        $this->assertLessThan(3.0, $statsTime, '統計查詢應在 3 秒內完成');

        // 5. 測試快取效能
        Cache::flush();
        
        $startTime = microtime(true);
        $recentActivities = $this->activityLogger->getRecentActivities(20);
        $firstLoadTime = microtime(true) - $startTime;

        $startTime = microtime(true);
        $cachedActivities = $this->activityLogger->getRecentActivities(20);
        $cachedLoadTime = microtime(true) - $startTime;

        $this->assertLessThan($firstLoadTime, $cachedLoadTime, '快取查詢應比首次查詢更快');

        // 6. 測試非同步記錄效能
        Queue::fake();
        
        $startTime = microtime(true);
        
        for ($i = 0; $i < 100; $i++) {
            $this->activityLogger->logAsync(
                'async_test',
                "非同步測試 {$i}",
                ['test_id' => $i]
            );
        }
        
        $asyncTime = microtime(true) - $startTime;
        $this->assertLessThan(1.0, $asyncTime, '100 筆非同步記錄應在 1 秒內完成');

        // 7. 測試記憶體使用量
        $memoryBefore = memory_get_usage();
        
        $largeDataSet = Activity::limit(5000)->get();
        
        $memoryAfter = memory_get_usage();
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // MB
        
        $this->assertLessThan(50, $memoryUsed, '載入 5000 筆記錄應使用少於 50MB 記憶體');

        // 8. 測試資料庫連線效能
        $startTime = microtime(true);
        
        for ($i = 0; $i < 50; $i++) {
            Activity::where('id', '>', 0)->first();
        }
        
        $connectionTime = microtime(true) - $startTime;
        $this->assertLessThan(2.0, $connectionTime, '50 次資料庫查詢應在 2 秒內完成');
    }

    /**
     * 測試活動記錄的資料完整性
     * 
     * @test
     */
    public function test_activity_data_integrity()
    {
        $integrityService = app(ActivityIntegrityService::class);

        // 1. 測試數位簽章生成
        $activityData = [
            'type' => 'integrity_test',
            'description' => '完整性測試',
            'user_id' => $this->adminUser->id,
            'created_at' => now()
        ];

        $signature = $integrityService->generateSignature($activityData);
        $this->assertNotEmpty($signature);
        $this->assertEquals(64, strlen($signature)); // SHA256 簽章長度

        // 2. 測試活動記錄完整性驗證
        $activity = Activity::create(array_merge($activityData, [
            'signature' => $signature
        ]));

        $this->assertTrue($integrityService->verifyActivity($activity));

        // 3. 測試篡改檢測
        $activity->description = '被篡改的描述';
        $this->assertFalse($integrityService->verifyActivity($activity));

        // 4. 測試批量完整性檢查
        $activities = Activity::factory()->count(10)->create();
        
        foreach ($activities as $activity) {
            $activity->signature = $integrityService->regenerateSignature($activity);
            $activity->save();
        }

        $verificationResults = $integrityService->verifyBatch($activities);
        $this->assertEquals(10, count($verificationResults));
        $this->assertTrue(collect($verificationResults)->every(fn($result) => $result === true));

        // 5. 測試敏感資料過濾
        $sensitiveActivity = Activity::create([
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

        $filteredData = $sensitiveActivity->getFilteredData($this->adminUser);
        $this->assertEquals('[FILTERED]', $filteredData['properties']['password']);
        $this->assertEquals('[FILTERED]', $filteredData['properties']['api_key']);
        $this->assertEquals('testuser', $filteredData['properties']['username']);
        $this->assertEquals('normal_value', $filteredData['properties']['normal_field']);
    }

    /**
     * 測試活動記錄的匯出和備份功能
     * 
     * @test
     */
    public function test_activity_export_and_backup_functionality()
    {
        $this->actingAs($this->adminUser);

        // 建立測試資料
        Activity::factory()->count(50)->create([
            'created_at' => now()->subDays(rand(1, 30))
        ]);

        // 1. 測試 CSV 匯出
        $response = $this->postJson('/api/admin/activities/export', [
            'format' => 'csv',
            'date_from' => now()->subDays(30)->toDateString(),
            'date_to' => now()->toDateString(),
            'filters' => []
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'download_url'
        ]);

        // 2. 測試 JSON 匯出
        $response = $this->postJson('/api/admin/activities/export', [
            'format' => 'json',
            'date_from' => now()->subDays(7)->toDateString(),
            'date_to' => now()->toDateString()
        ]);

        $response->assertStatus(200);

        // 3. 測試 PDF 匯出
        $response = $this->postJson('/api/admin/activities/export', [
            'format' => 'pdf',
            'date_from' => now()->subDays(1)->toDateString(),
            'date_to' => now()->toDateString()
        ]);

        $response->assertStatus(200);

        // 4. 測試備份功能
        $response = $this->postJson('/api/admin/activities/backup', [
            'date_from' => now()->subDays(30)->toDateString(),
            'date_to' => now()->toDateString(),
            'include_integrity_check' => true
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'backup_file',
            'record_count',
            'file_size'
        ]);

        // 5. 測試大量資料匯出效能
        Activity::factory()->count(1000)->create();

        $startTime = microtime(true);
        
        $response = $this->postJson('/api/admin/activities/export', [
            'format' => 'csv',
            'date_from' => now()->subDays(1)->toDateString(),
            'date_to' => now()->toDateString()
        ]);

        $exportTime = microtime(true) - $startTime;
        
        $response->assertStatus(200);
        $this->assertLessThan(10.0, $exportTime, '大量資料匯出應在 10 秒內完成');
    }

    /**
     * 測試活動記錄的保留政策
     * 
     * @test
     */
    public function test_activity_retention_policy()
    {
        // 建立不同時期的測試資料
        $oldActivities = Activity::factory()->count(10)->create([
            'created_at' => now()->subDays(100) // 超過保留期限
        ]);

        $recentActivities = Activity::factory()->count(10)->create([
            'created_at' => now()->subDays(30) // 在保留期限內
        ]);

        $securityActivities = Activity::factory()->count(5)->create([
            'type' => 'security_event',
            'risk_level' => 8,
            'created_at' => now()->subDays(200) // 安全事件有更長保留期
        ]);

        // 1. 測試保留政策執行
        $this->artisan('activities:cleanup', [
            '--days' => 90,
            '--dry-run' => false
        ])->assertExitCode(0);

        // 2. 驗證清理結果
        $this->assertEquals(0, Activity::whereIn('id', $oldActivities->pluck('id'))->count());
        $this->assertEquals(10, Activity::whereIn('id', $recentActivities->pluck('id'))->count());
        $this->assertEquals(5, Activity::whereIn('id', $securityActivities->pluck('id'))->count());

        // 3. 測試受保護記錄不被清理
        $protectedActivity = Activity::factory()->create([
            'type' => 'admin_action',
            'risk_level' => 9,
            'created_at' => now()->subDays(150)
        ]);

        $this->artisan('activities:cleanup', ['--days' => 90]);
        
        $this->assertDatabaseHas('activities', ['id' => $protectedActivity->id]);
    }

    /**
     * 設定測試使用者
     */
    protected function setupTestUsers(): void
    {
        $this->adminUser = User::factory()->create([
            'username' => 'admin_integration',
            'name' => '整合測試管理員',
            'email' => 'admin@integration.test',
            'is_active' => true
        ]);

        $this->regularUser = User::factory()->create([
            'username' => 'user_integration',
            'name' => '整合測試使用者',
            'email' => 'user@integration.test',
            'is_active' => true
        ]);

        $this->viewOnlyUser = User::factory()->create([
            'username' => 'viewer_integration',
            'name' => '整合測試檢視者',
            'email' => 'viewer@integration.test',
            'is_active' => true
        ]);
    }

    /**
     * 設定測試角色
     */
    protected function setupTestRoles(): void
    {
        $this->adminRole = Role::create([
            'name' => 'admin_integration',
            'display_name' => '整合測試管理員',
            'description' => '整合測試用管理員角色'
        ]);

        $this->userRole = Role::create([
            'name' => 'user_integration',
            'display_name' => '整合測試使用者',
            'description' => '整合測試用一般使用者角色'
        ]);

        $this->viewOnlyRole = Role::create([
            'name' => 'viewer_integration',
            'display_name' => '整合測試檢視者',
            'description' => '整合測試用唯讀角色'
        ]);

        // 指派角色給使用者
        $this->adminUser->roles()->attach($this->adminRole);
        $this->regularUser->roles()->attach($this->userRole);
        $this->viewOnlyUser->roles()->attach($this->viewOnlyRole);
    }

    /**
     * 設定測試權限
     */
    protected function setupTestPermissions(): void
    {
        $permissions = [
            // 活動記錄權限
            'activity_logs.view' => '檢視活動日誌',
            'activity_logs.export' => '匯出活動日誌',
            'activity_logs.delete' => '刪除活動日誌',
            
            // 使用者管理權限
            'users.view' => '檢視使用者',
            'users.create' => '建立使用者',
            'users.edit' => '編輯使用者',
            'users.delete' => '刪除使用者',
            
            // 系統權限
            'system.logs' => '檢視系統日誌',
            'system.settings' => '系統設定',
            
            // 安全權限
            'security.view' => '檢視安全資訊',
            'security.incidents' => '管理安全事件'
        ];

        foreach ($permissions as $name => $displayName) {
            $permission = Permission::create([
                'name' => $name,
                'display_name' => $displayName,
                'module' => explode('.', $name)[0]
            ]);

            // 管理員擁有所有權限
            $this->adminRole->permissions()->attach($permission);

            // 一般使用者只有檢視權限
            if (str_contains($name, '.view')) {
                $this->userRole->permissions()->attach($permission);
            }

            // 檢視者只有活動記錄檢視權限
            if ($name === 'activity_logs.view') {
                $this->viewOnlyRole->permissions()->attach($permission);
            }
        }
    }

    protected function tearDown(): void
    {
        // 清理測試資料
        Cache::flush();
        Queue::purge('activities');
        
        parent::tearDown();
    }
}