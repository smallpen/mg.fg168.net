<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Activity;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Jobs\LogActivityJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;

/**
 * 活動記錄服務測試
 */
class ActivityLoggerTest extends TestCase
{
    use RefreshDatabase;

    protected ActivityLogger $logger;
    protected User $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->logger = app(ActivityLogger::class);
        $this->testUser = User::factory()->create();
        
        $this->actingAs($this->testUser);
    }

    /** @test */
    public function it_can_log_activity_synchronously()
    {
        $activity = $this->logger->log('test_action', '測試活動', [
            'module' => 'test',
            'properties' => ['key' => 'value']
        ]);

        $this->assertInstanceOf(Activity::class, $activity);
        $this->assertEquals('test_action', $activity->type);
        $this->assertEquals('測試活動', $activity->description);
        $this->assertEquals('test', $activity->module);
        $this->assertEquals($this->testUser->id, $activity->user_id);
        $this->assertNotNull($activity->signature);
    }

    /** @test */
    public function it_can_log_user_action_with_subject()
    {
        $targetUser = User::factory()->create();

        $activity = $this->logger->logUserAction('created', $targetUser, [
            'module' => 'users'
        ]);

        $this->assertEquals('created', $activity->type);
        $this->assertEquals($targetUser->id, $activity->subject_id);
        $this->assertEquals(User::class, $activity->subject_type);
        $this->assertStringContains('建立', $activity->description);
    }

    /** @test */
    public function it_can_log_security_event()
    {
        $activity = $this->logger->logSecurityEvent('login_failed', '登入失敗', [
            'failed_attempts' => 3,
            'ip_address' => '192.168.1.1'
        ]);

        $this->assertEquals('login_failed', $activity->type);
        $this->assertEquals('warning', $activity->result);
        $this->assertEquals('security', $activity->module);
        $this->assertGreaterThan(1, $activity->risk_level);
    }

    /** @test */
    public function it_can_log_system_event()
    {
        $activity = $this->logger->logSystemEvent('backup_completed', [
            'backup_size' => '1.2GB',
            'duration' => '5 minutes'
        ]);

        $this->assertEquals('backup_completed', $activity->type);
        $this->assertEquals('system', $activity->module);
        $this->assertNull($activity->user_id);
        $this->assertStringContains('系統事件', $activity->description);
    }

    /** @test */
    public function it_can_log_api_access()
    {
        $this->app['request']->setMethod('POST');

        $activity = $this->logger->logApiAccess('/api/users', [
            'response_code' => 200
        ]);

        $this->assertEquals('api_access', $activity->type);
        $this->assertEquals('api', $activity->module);
        $this->assertStringContains('/api/users', $activity->description);
        $this->assertEquals('POST', $activity->properties['method']);
        $this->assertEquals(200, $activity->properties['response_code']);
    }

    /** @test */
    public function it_filters_sensitive_data()
    {
        $activity = $this->logger->log('user_update', '更新使用者', [
            'properties' => [
                'username' => 'testuser',
                'password' => 'secret123',
                'api_key' => 'abc123',
                'email' => 'test@example.com'
            ]
        ]);

        $this->assertEquals('testuser', $activity->properties['username']);
        $this->assertEquals('[FILTERED]', $activity->properties['password']);
        $this->assertEquals('[FILTERED]', $activity->properties['api_key']);
        $this->assertEquals('test@example.com', $activity->properties['email']);
    }

    /** @test */
    public function it_can_log_asynchronously()
    {
        Queue::fake();

        $this->logger->logAsync('async_test', '非同步測試', [
            'module' => 'test'
        ]);

        Queue::assertPushed(LogActivityJob::class);
    }

    /** @test */
    public function it_can_log_batch_activities()
    {
        $activities = [
            [
                'type' => 'batch_test_1',
                'description' => '批量測試 1',
                'data' => ['module' => 'test']
            ],
            [
                'type' => 'batch_test_2',
                'description' => '批量測試 2',
                'data' => ['module' => 'test']
            ]
        ];

        $this->logger->logBatch($activities);

        $this->assertEquals(2, Activity::count());
        $this->assertEquals('batch_test_1', Activity::first()->type);
        $this->assertEquals('batch_test_2', Activity::latest()->first()->type);
    }

    /** @test */
    public function it_generates_digital_signature()
    {
        $activity = $this->logger->log('signature_test', '簽章測試');

        $this->assertNotNull($activity->signature);
        $this->assertTrue($activity->verifyIntegrity());
    }

    /** @test */
    public function it_calculates_security_risk_level()
    {
        // 低風險事件
        $lowRiskActivity = $this->logger->logSecurityEvent('login_failed', '登入失敗');
        $this->assertLessThan(5, $lowRiskActivity->risk_level);

        // 高風險事件
        $highRiskActivity = $this->logger->logSecurityEvent('permission_escalation', '權限提升', [
            'failed_attempts' => 10,
            'unusual_time' => true,
            'unknown_ip' => true
        ]);
        $this->assertGreaterThan(7, $highRiskActivity->risk_level);
    }

    /** @test */
    public function it_logs_user_login()
    {
        $activity = $this->logger->logLogin($this->testUser->id);

        $this->assertEquals('user_login', $activity->type);
        $this->assertEquals($this->testUser->id, $activity->user_id);
        $this->assertStringContains('登入', $activity->description);
    }

    /** @test */
    public function it_logs_user_logout()
    {
        // 模擬 session 登入時間
        session(['login_time' => now()->subMinutes(30)]);

        $activity = $this->logger->logLogout($this->testUser->id);

        $this->assertEquals('user_logout', $activity->type);
        $this->assertEquals($this->testUser->id, $activity->user_id);
        $this->assertStringContains('登出', $activity->description);
    }

    /** @test */
    public function it_logs_user_created()
    {
        $newUser = User::factory()->create();

        $activity = $this->logger->logUserCreated($newUser->id, $this->testUser->id);

        $this->assertEquals('user_created', $activity->type);
        $this->assertEquals($this->testUser->id, $activity->user_id);
        $this->assertStringContains($newUser->name, $activity->description);
    }

    /** @test */
    public function it_logs_user_updated()
    {
        $targetUser = User::factory()->create();
        $changes = ['name' => 'New Name', 'email' => 'new@example.com'];

        $activity = $this->logger->logUserUpdated($targetUser->id, $changes, $this->testUser->id);

        $this->assertEquals('user_updated', $activity->type);
        $this->assertEquals($this->testUser->id, $activity->user_id);
        $this->assertEquals($changes, $activity->properties['changes']);
    }

    /** @test */
    public function it_logs_role_created()
    {
        $role = \App\Models\Role::factory()->create();

        $activity = $this->logger->logRoleCreated($role->id, $this->testUser->id);

        $this->assertEquals('role_created', $activity->type);
        $this->assertEquals($this->testUser->id, $activity->user_id);
        $this->assertStringContains($role->display_name, $activity->description);
    }

    /** @test */
    public function it_logs_permissions_changed()
    {
        $role = \App\Models\Role::factory()->create();
        $addedPermissions = ['create_user', 'edit_user'];
        $removedPermissions = ['delete_user'];

        $activity = $this->logger->logPermissionsChanged(
            $role->id, 
            $addedPermissions, 
            $removedPermissions, 
            $this->testUser->id
        );

        $this->assertEquals('permissions_changed', $activity->type);
        $this->assertEquals($addedPermissions, $activity->properties['added_permissions']);
        $this->assertEquals($removedPermissions, $activity->properties['removed_permissions']);
    }

    /** @test */
    public function it_logs_system_activity()
    {
        $activity = $this->logger->logSystemActivity('maintenance_start', '系統維護開始', [
            'scheduled_duration' => '2 hours'
        ]);

        $this->assertEquals('maintenance_start', $activity->type);
        $this->assertNull($activity->user_id);
        $this->assertEquals('2 hours', $activity->properties['scheduled_duration']);
    }

    /** @test */
    public function it_gets_recent_activities()
    {
        Activity::factory()->count(15)->create();

        $activities = $this->logger->getRecentActivities(10);

        $this->assertEquals(10, count($activities));
    }

    /** @test */
    public function it_clears_cache()
    {
        // 建立一些活動並快取
        Activity::factory()->count(5)->create();
        $this->logger->getRecentActivities(10);

        // 清除快取
        $this->logger->clearCache();

        // 這個測試主要確保方法可以執行而不會出錯
        $this->assertTrue(true);
    }

    /** @test */
    public function it_writes_to_log_file()
    {
        Log::shouldReceive('channel')
            ->with('activity')
            ->once()
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->with('User Activity', \Mockery::type('array'))
            ->once();

        $this->logger->log('log_test', '日誌測試');
    }
}