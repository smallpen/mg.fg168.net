<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Activity;
use App\Services\ActivityLogger;
use App\Services\SecurityAnalyzer;
use App\Services\ActivityIntegrityService;
use App\Services\ActivityExportService;
use App\Services\ActivityBackupService;
use App\Repositories\Contracts\ActivityRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

/**
 * 活動記錄功能最終整合測試
 * 
 * 測試所有活動記錄元件的整合和協作
 */
class ActivityLogFinalIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;
    protected ActivityLogger $activityLogger;
    protected SecurityAnalyzer $securityAnalyzer;
    protected ActivityIntegrityService $integrityService;
    protected ActivityExportService $exportService;
    protected ActivityBackupService $backupService;
    protected ActivityRepositoryInterface $activityRepository;

    protected function setUp(): void
    {
        parent::setUp();

        // 停用事件以避免觀察者干擾
        Event::fake();

        // 停用權限安全檢查以便測試
        config(['permission.security.enabled' => false]);

        // 建立測試使用者和權限
        $this->setupTestData();

        // 初始化服務
        $this->activityLogger = app(ActivityLogger::class);
        $this->securityAnalyzer = app(SecurityAnalyzer::class);
        $this->integrityService = app(ActivityIntegrityService::class);
        $this->exportService = app(ActivityExportService::class);
        $this->backupService = app(ActivityBackupService::class);
        $this->activityRepository = app(ActivityRepositoryInterface::class);
    }

    /**
     * 測試完整的活動記錄流程
     * 
     * @test
     */
    public function test_complete_activity_logging_flow()
    {
        // 1. 記錄各種類型的活動
        $this->actingAs($this->adminUser);

        // 記錄使用者登入
        $this->activityLogger->logUserAction('user_login', $this->adminUser, [
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 Test Browser'
        ]);

        // 記錄系統操作
        $this->activityLogger->logSystemEvent('system_backup', [
            'backup_type' => 'full',
            'size' => '1.2GB'
        ]);

        // 記錄安全事件
        $this->activityLogger->logSecurityEvent('login_failed', '登入失敗嘗試', [
            'ip_address' => '10.0.0.1',
            'attempts' => 3
        ]);

        // 2. 驗證活動記錄已建立
        $activities = Activity::all();
        $this->assertCount(3, $activities);

        // 3. 測試完整性保護
        foreach ($activities as $activity) {
            $this->assertTrue($this->integrityService->verifyActivity($activity));
            $this->assertNotNull($activity->signature);
        }

        // 4. 測試安全分析
        $securityEvents = $this->securityAnalyzer->detectAnomalies($activities);
        $this->assertIsArray($securityEvents);

        // 5. 測試查詢和篩選功能
        $paginatedActivities = $this->activityRepository->getPaginatedActivities([], 10);
        $this->assertEquals(3, $paginatedActivities->total());

        // 測試按類型篩選
        $loginActivities = $this->activityRepository->getPaginatedActivities(['type' => 'user_login'], 10);
        $this->assertEquals(1, $loginActivities->total());

        // 6. 測試匯出功能
        $exportPath = $this->exportService->exportToCSV($activities->pluck('id')->toArray());
        $this->assertFileExists(storage_path('app/' . $exportPath));

        // 7. 測試備份功能
        $backupPath = $this->backupService->createBackup();
        $this->assertFileExists(storage_path('app/backups/' . $backupPath));

        $this->assertTrue(true, '完整的活動記錄流程測試通過');
    }

    /**
     * 測試效能優化功能
     * 
     * @test
     */
    public function test_performance_optimization()
    {
        // 建立大量測試資料
        Activity::factory()->count(1000)->create([
            'user_id' => $this->adminUser->id
        ]);

        $startTime = microtime(true);

        // 測試分頁查詢效能
        $activities = $this->activityRepository->getPaginatedActivities([], 50);
        $this->assertEquals(50, $activities->count());

        $queryTime = microtime(true) - $startTime;
        $this->assertLessThan(1.0, $queryTime, '查詢時間應少於 1 秒');

        // 測試快取功能
        Cache::flush();
        $startTime = microtime(true);
        
        $stats = $this->activityRepository->getActivityStats('7d');
        $firstQueryTime = microtime(true) - $startTime;

        $startTime = microtime(true);
        $cachedStats = $this->activityRepository->getActivityStats('7d');
        $cachedQueryTime = microtime(true) - $startTime;

        $this->assertLessThan($firstQueryTime, $cachedQueryTime, '快取查詢應該更快');
        $this->assertEquals($stats, $cachedStats, '快取資料應該一致');

        $this->assertTrue(true, '效能優化測試通過');
    }

    /**
     * 測試權限控制整合
     * 
     * @test
     */
    public function test_permission_control_integration()
    {
        // 測試管理員權限
        $this->actingAs($this->adminUser);
        
        $response = $this->get(route('admin.activities.index'));
        $response->assertStatus(200);

        $response = $this->get(route('admin.activities.export'));
        $response->assertStatus(200);

        // 測試一般使用者權限
        $this->actingAs($this->regularUser);
        
        $response = $this->get(route('admin.activities.index'));
        $response->assertStatus(403);

        $response = $this->get(route('admin.activities.export'));
        $response->assertStatus(403);

        $this->assertTrue(true, '權限控制整合測試通過');
    }

    /**
     * 測試安全功能整合
     * 
     * @test
     */
    public function test_security_features_integration()
    {
        // 1. 測試敏感資料過濾
        $this->actingAs($this->adminUser);

        $this->activityLogger->logUserAction('password_change', $this->adminUser, [
            'old_password' => 'secret123',
            'new_password' => 'newsecret456',
            'credit_card' => '1234-5678-9012-3456',
            'normal_field' => 'normal_value'
        ]);

        $activity = Activity::latest()->first();
        $properties = $activity->properties;

        $this->assertEquals('[FILTERED]', $properties['old_password']);
        $this->assertEquals('[FILTERED]', $properties['new_password']);
        $this->assertEquals('[FILTERED]', $properties['credit_card']);
        $this->assertEquals('normal_value', $properties['normal_field']);

        // 2. 測試完整性驗證
        $this->assertTrue($this->integrityService->verifyActivity($activity));

        // 3. 測試篡改檢測
        $originalSignature = $activity->signature;
        $activity->update(['description' => '被篡改的描述']);
        
        $this->assertFalse($this->integrityService->verifyActivity($activity->fresh()));

        // 恢復原始資料
        $activity->update(['signature' => $originalSignature]);

        // 4. 測試安全事件檢測
        $this->activityLogger->logSecurityEvent('suspicious_login', '可疑登入嘗試', [
            'ip_address' => '192.168.1.999',
            'failed_attempts' => 5
        ]);

        $securityActivity = Activity::where('type', 'suspicious_login')->first();
        $riskScore = $this->securityAnalyzer->calculateRiskScore($securityActivity);
        
        $this->assertGreaterThan(5, $riskScore, '可疑活動應該有較高的風險分數');

        $this->assertTrue(true, '安全功能整合測試通過');
    }

    /**
     * 測試使用者體驗功能
     * 
     * @test
     */
    public function test_user_experience_features()
    {
        $this->actingAs($this->adminUser);

        // 建立測試資料
        Activity::factory()->count(100)->create([
            'user_id' => $this->adminUser->id,
            'created_at' => now()->subDays(rand(1, 30))
        ]);

        // 1. 測試搜尋功能
        $searchResults = $this->activityRepository->searchActivities('test', []);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $searchResults);

        // 2. 測試統計資料
        $stats = $this->activityRepository->getActivityStats('30d');
        $this->assertArrayHasKey('total_activities', $stats);
        $this->assertArrayHasKey('daily_activities', $stats);
        $this->assertArrayHasKey('activity_types', $stats);

        // 3. 測試最活躍使用者
        $topUsers = $this->activityRepository->getTopUsers('30d', 10);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $topUsers);

        // 4. 測試安全事件統計
        $securityEvents = $this->activityRepository->getSecurityEvents('30d');
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $securityEvents);

        $this->assertTrue(true, '使用者體驗功能測試通過');
    }

    /**
     * 測試多語言支援
     * 
     * @test
     */
    public function test_multilingual_support()
    {
        // 測試中文環境
        app()->setLocale('zh_TW');
        
        $this->actingAs($this->adminUser);
        $response = $this->get(route('admin.activities.index'));
        $response->assertSee('活動記錄');

        // 測試英文環境
        app()->setLocale('en');
        
        $response = $this->get(route('admin.activities.index'));
        $response->assertStatus(200);

        $this->assertTrue(true, '多語言支援測試通過');
    }

    /**
     * 測試錯誤處理
     * 
     * @test
     */
    public function test_error_handling()
    {
        $this->actingAs($this->adminUser);

        // 1. 測試不存在的活動記錄
        $response = $this->get(route('admin.activities.show', 99999));
        $response->assertStatus(404);

        // 2. 測試無效的匯出格式
        try {
            $this->exportService->export([], 'invalid_format');
            $this->fail('應該拋出例外');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContains('不支援的匯出格式', $e->getMessage());
        }

        // 3. 測試無效的備份檔案
        $result = $this->backupService->verifyBackup('nonexistent.backup');
        $this->assertFalse($result);

        $this->assertTrue(true, '錯誤處理測試通過');
    }

    /**
     * 設定測試資料
     */
    private function setupTestData(): void
    {
        // 建立權限
        $permissions = [
            'activity_logs.view' => '檢視活動日誌',
            'activity_logs.export' => '匯出活動日誌',
            'activity_logs.delete' => '刪除活動日誌',
            'system.logs' => '檢視系統日誌',
            'dashboard.view' => '檢視儀表板'
        ];

        foreach ($permissions as $name => $displayName) {
            Permission::create([
                'name' => $name,
                'display_name' => $displayName,
                'module' => explode('.', $name)[0]
            ]);
        }

        // 建立角色
        $adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理員'
        ]);

        $userRole = Role::create([
            'name' => 'user',
            'display_name' => '一般使用者'
        ]);

        // 指派權限
        $adminRole->permissions()->attach(Permission::all());
        $userRole->permissions()->attach(Permission::where('name', 'dashboard.view')->first());

        // 建立使用者
        $this->adminUser = User::create([
            'username' => 'admin',
            'name' => '系統管理員',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true
        ]);

        $this->regularUser = User::create([
            'username' => 'user',
            'name' => '一般使用者',
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true
        ]);

        // 指派角色
        $this->adminUser->roles()->attach($adminRole);
        $this->regularUser->roles()->attach($userRole);
    }
}