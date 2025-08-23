<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * 活動記錄功能測試
 */
class ActivityLoggingTest extends TestCase
{
    use RefreshDatabase;

    protected ActivityLogger $activityLogger;
    protected User $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->activityLogger = app(ActivityLogger::class);
        $this->testUser = User::factory()->create([
            'username' => 'testuser',
            'name' => '測試使用者',
            'email' => 'test@example.com',
        ]);
    }

    /** @test */
    public function it_can_log_basic_activity(): void
    {
        // 模擬使用者登入
        Auth::login($this->testUser);

        // 記錄活動
        $activity = $this->activityLogger->log(
            'test_activity',
            '測試活動記錄',
            [
                'module' => 'test',
                'properties' => ['test_data' => 'test_value'],
                'result' => 'success',
                'risk_level' => 2,
            ]
        );

        // 驗證活動記錄
        $this->assertInstanceOf(Activity::class, $activity);
        $this->assertEquals('test_activity', $activity->type);
        $this->assertEquals('測試活動記錄', $activity->description);
        $this->assertEquals('test', $activity->module);
        $this->assertEquals($this->testUser->id, $activity->user_id);
        $this->assertEquals('success', $activity->result);
        $this->assertEquals(2, $activity->risk_level);
        $this->assertNotNull($activity->signature);
        
        // 驗證資料庫中的記錄
        $this->assertDatabaseHas('activities', [
            'type' => 'test_activity',
            'description' => '測試活動記錄',
            'module' => 'test',
            'user_id' => $this->testUser->id,
            'result' => 'success',
            'risk_level' => 2,
        ]);
    }

    /** @test */
    public function it_can_log_user_login(): void
    {
        $activity = $this->activityLogger->logLogin($this->testUser->id, [
            'method' => 'username_password',
            'remember' => true,
        ]);

        $this->assertEquals('user_login', $activity->type);
        $this->assertEquals('使用者登入系統', $activity->description);
        $this->assertEquals('auth', $activity->module);
        $this->assertEquals($this->testUser->id, $activity->user_id);
        $this->assertEquals('success', $activity->result);
        
        $properties = $activity->properties;
        $this->assertEquals('username_password', $properties['login_method']);
        $this->assertTrue($properties['remember_me']);
    }

    /** @test */
    public function it_can_log_login_failed(): void
    {
        $activity = $this->activityLogger->logLoginFailed('wronguser', [
            'attempt_count' => 3,
            'reason' => 'invalid_credentials',
        ]);

        $this->assertEquals('login_failed', $activity->type);
        $this->assertEquals('登入失敗：wronguser', $activity->description);
        $this->assertNull($activity->user_id);
        $this->assertEquals('warning', $activity->result);
        
        $properties = $activity->properties;
        $this->assertEquals('wronguser', $properties['username']);
        $this->assertEquals(3, $properties['attempt_count']);
        $this->assertEquals('invalid_credentials', $properties['reason']);
    }

    /** @test */
    public function it_can_log_user_action_with_subject(): void
    {
        Auth::login($this->testUser);
        
        $targetUser = User::factory()->create(['username' => 'target_user']);
        
        $activity = $this->activityLogger->logUserAction('updated', $targetUser, [
            'module' => 'users',
            'properties' => ['field_changed' => 'email'],
        ]);

        $this->assertEquals('updated', $activity->type);
        $this->assertEquals($targetUser->id, $activity->subject_id);
        $this->assertEquals(User::class, $activity->subject_type);
        $this->assertEquals($this->testUser->id, $activity->user_id);
        $this->assertEquals('users', $activity->module);
    }

    /** @test */
    public function it_can_log_security_event(): void
    {
        $activity = $this->activityLogger->logSecurityEvent(
            'suspicious_activity',
            '可疑活動檢測',
            [
                'ip_address' => '192.168.1.100',
                'unusual_time' => true,
            ]
        );

        $this->assertEquals('suspicious_activity', $activity->type);
        $this->assertEquals('可疑活動檢測', $activity->description);
        $this->assertEquals('security', $activity->module);
        $this->assertEquals('warning', $activity->result);
        $this->assertGreaterThanOrEqual(3, $activity->risk_level);
    }

    /** @test */
    public function it_can_log_system_event(): void
    {
        $activity = $this->activityLogger->logSystemEvent('system_maintenance', [
            'maintenance_type' => 'database_cleanup',
            'duration' => 300,
        ]);

        $this->assertEquals('system_maintenance', $activity->type);
        $this->assertEquals('系統事件: system_maintenance', $activity->description);
        $this->assertEquals('system', $activity->module);
        $this->assertNull($activity->user_id);
        
        $properties = $activity->properties;
        $this->assertEquals('database_cleanup', $properties['maintenance_type']);
        $this->assertEquals(300, $properties['duration']);
    }

    /** @test */
    public function it_can_log_api_access(): void
    {
        Auth::login($this->testUser);
        
        $activity = $this->activityLogger->logApiAccess('/api/users', [
            'response_code' => 200,
            'method' => 'GET',
        ]);

        $this->assertEquals('api_access', $activity->type);
        $this->assertEquals('API 存取: /api/users', $activity->description);
        $this->assertEquals('api', $activity->module);
        $this->assertEquals($this->testUser->id, $activity->user_id);
        
        $properties = $activity->properties;
        $this->assertEquals('/api/users', $properties['endpoint']);
        $this->assertEquals('GET', $properties['method']);
        $this->assertEquals(200, $properties['response_code']);
    }

    /** @test */
    public function it_filters_sensitive_data(): void
    {
        Auth::login($this->testUser);
        
        $activity = $this->activityLogger->log(
            'test_sensitive',
            '測試敏感資料過濾',
            [
                'properties' => [
                    'username' => 'testuser',
                    'password' => 'secret123',
                    'api_token' => 'token123',
                    'normal_field' => 'normal_value',
                ],
            ]
        );

        $properties = $activity->properties;
        $this->assertEquals('testuser', $properties['username']);
        $this->assertEquals('[FILTERED]', $properties['password']);
        $this->assertEquals('[FILTERED]', $properties['api_token']);
        $this->assertEquals('normal_value', $properties['normal_field']);
    }

    /** @test */
    public function it_generates_valid_signature(): void
    {
        Auth::login($this->testUser);
        
        $activity = $this->activityLogger->log(
            'test_signature',
            '測試數位簽章',
            ['module' => 'test']
        );

        // 驗證簽章存在
        $this->assertNotNull($activity->signature);
        
        // 驗證簽章正確性
        $this->assertTrue($activity->verifyIntegrity());
        
        // 修改資料後驗證簽章失效
        $activity->description = '修改後的描述';
        $this->assertFalse($activity->verifyIntegrity());
    }

    /** @test */
    public function it_can_log_batch_activities(): void
    {
        Auth::login($this->testUser);
        
        $activities = [
            [
                'type' => 'batch_test_1',
                'description' => '批量測試 1',
                'data' => ['module' => 'test'],
            ],
            [
                'type' => 'batch_test_2',
                'description' => '批量測試 2',
                'data' => ['module' => 'test'],
            ],
        ];

        $this->activityLogger->logBatch($activities);

        // 驗證批量記錄
        $this->assertDatabaseHas('activities', [
            'type' => 'batch_test_1',
            'description' => '批量測試 1',
            'module' => 'test',
        ]);
        
        $this->assertDatabaseHas('activities', [
            'type' => 'batch_test_2',
            'description' => '批量測試 2',
            'module' => 'test',
        ]);
    }
}