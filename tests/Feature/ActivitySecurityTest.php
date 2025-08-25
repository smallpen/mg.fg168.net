<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Activity;
use App\Services\ActivitySecurityService;
use App\Services\ActivityIntegrityService;
use App\Services\SensitiveDataFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

/**
 * 活動記錄安全功能測試
 */
class ActivitySecurityTest extends TestCase
{
    use RefreshDatabase;

    protected ActivitySecurityService $securityService;
    protected ActivityIntegrityService $integrityService;
    protected SensitiveDataFilter $sensitiveDataFilter;
    protected User $adminUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 執行必要的 seeders
        $this->seed([
            \Database\Seeders\PermissionSeeder::class,
            \Database\Seeders\RoleSeeder::class,
        ]);
        
        $this->securityService = app(ActivitySecurityService::class);
        $this->integrityService = app(ActivityIntegrityService::class);
        $this->sensitiveDataFilter = app(SensitiveDataFilter::class);
        
        // 建立測試使用者
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');
        
        $this->regularUser = User::factory()->create();
        $this->regularUser->assignRole('user');
    }

    /** @test */
    public function it_can_check_access_permissions_for_admin_user()
    {
        $activity = Activity::factory()->create([
            'type' => 'user_login',
            'risk_level' => 3
        ]);

        $result = $this->securityService->checkAccessPermission(
            $this->adminUser, 
            'view', 
            $activity
        );

        $this->assertTrue($result['allowed']);
        $this->assertLessThanOrEqual(5, $result['risk_level']);
    }

    /** @test */
    public function it_denies_access_to_sensitive_activities_for_regular_users()
    {
        $sensitiveActivity = Activity::factory()->create([
            'type' => 'permission_escalation',
            'risk_level' => 9,
            'user_id' => $this->adminUser->id
        ]);

        $result = $this->securityService->checkAccessPermission(
            $this->regularUser, 
            'view', 
            $sensitiveActivity
        );

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('敏感活動記錄', $result['reason']);
    }

    /** @test */
    public function it_allows_users_to_view_their_own_activities()
    {
        $userActivity = Activity::factory()->create([
            'type' => 'profile_update',
            'user_id' => $this->regularUser->id,
            'risk_level' => 2
        ]);

        $result = $this->securityService->checkAccessPermission(
            $this->regularUser, 
            'view', 
            $userActivity
        );

        $this->assertTrue($result['allowed']);
    }

    /** @test */
    public function it_denies_users_access_to_other_users_activities()
    {
        $otherUserActivity = Activity::factory()->create([
            'type' => 'profile_update',
            'user_id' => $this->adminUser->id,
            'risk_level' => 2
        ]);

        $result = $this->securityService->checkAccessPermission(
            $this->regularUser, 
            'view', 
            $otherUserActivity
        );

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('只能檢視自己的活動記錄', $result['reason']);
    }

    /** @test */
    public function it_filters_sensitive_data_for_regular_users()
    {
        $activity = Activity::factory()->create([
            'type' => 'user_login',
            'user_id' => $this->regularUser->id,
            'properties' => [
                'username' => 'testuser',
                'password' => 'secret123',
                'ip_address' => '192.168.1.100',
                'session_id' => 'sess_abc123'
            ],
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]);

        $filteredData = $this->securityService->filterActivityData($activity, $this->regularUser);

        // 檢查敏感資料是否被過濾
        $this->assertStringContainsString('*', $filteredData['properties']['password']);
        $this->assertStringContainsString('***', $filteredData['ip_address']);
        $this->assertStringContainsString('...', $filteredData['user_agent']);
        
        // 檢查簽章是否被移除
        $this->assertArrayNotHasKey('signature', $filteredData);
    }

    /** @test */
    public function it_does_not_filter_data_for_admin_users_with_audit_permission()
    {
        $activity = Activity::factory()->create([
            'type' => 'user_login',
            'properties' => [
                'username' => 'testuser',
                'password' => 'secret123',
                'ip_address' => '192.168.1.100'
            ],
            'ip_address' => '192.168.1.100',
            'signature' => 'test_signature'
        ]);

        $filteredData = $this->securityService->filterActivityData($activity, $this->adminUser);

        // 管理員應該能看到完整資料
        $this->assertEquals('192.168.1.100', $filteredData['ip_address']);
        $this->assertArrayHasKey('signature', $filteredData);
    }

    /** @test */
    public function it_detects_tampering_attempts()
    {
        $activity = Activity::factory()->create([
            'type' => 'user_login',
            'description' => 'User logged in',
            'user_id' => $this->regularUser->id
        ]);

        $originalData = [
            'type' => 'user_login',
            'description' => 'User logged in',
            'causer_id' => $this->regularUser->id,
            'created_at' => $activity->created_at
        ];

        // 模擬篡改
        $activity->type = 'admin_action';
        $activity->description = 'Admin performed action';

        $result = $this->securityService->detectTamperingAttempt($activity, $originalData);

        $this->assertTrue($result['tampering_detected']);
        $this->assertCount(2, $result['tampered_fields']);
        $this->assertEquals('medium', $result['severity']);
    }

    /** @test */
    public function it_encrypts_and_decrypts_sensitive_data_when_enabled()
    {
        config(['activity-security.encryption.enabled' => true]);

        $sensitiveData = [
            'description' => 'User password changed',
            'properties' => [
                'old_password' => 'oldpass123',
                'new_password' => 'newpass456'
            ]
        ];

        // 加密
        $encryptedData = $this->securityService->encryptSensitiveData($sensitiveData);
        
        $this->assertNotEquals($sensitiveData['description'], $encryptedData['description']);
        $this->assertTrue($encryptedData['description_encrypted']);

        // 解密
        $decryptedData = $this->securityService->decryptSensitiveData($encryptedData);
        
        $this->assertEquals($sensitiveData['description'], $decryptedData['description']);
        $this->assertArrayNotHasKey('description_encrypted', $decryptedData);
    }

    /** @test */
    public function it_generates_and_verifies_activity_signatures()
    {
        $activityData = [
            'type' => 'user_login',
            'description' => 'User logged in successfully',
            'user_id' => $this->regularUser->id,
            'ip_address' => '192.168.1.100',
            'created_at' => now()
        ];

        // 生成簽章
        $signature = $this->integrityService->generateSignature($activityData);
        
        $this->assertNotEmpty($signature);
        $this->assertStringStartsWith('v1:', $signature);

        // 建立活動記錄並驗證簽章
        $activity = Activity::create(array_merge($activityData, ['signature' => $signature]));
        
        $this->assertTrue($this->integrityService->verifyActivity($activity));
    }

    /** @test */
    public function it_detects_signature_tampering()
    {
        $activity = Activity::factory()->create([
            'type' => 'user_login',
            'signature' => 'v1:valid_signature_hash'
        ]);

        // 篡改簽章
        $activity->signature = 'v1:tampered_signature_hash';

        $this->assertFalse($this->integrityService->verifyActivity($activity));
    }

    /** @test */
    public function it_performs_security_audit()
    {
        // 建立一些測試活動記錄
        Activity::factory()->count(5)->create(['risk_level' => 2]);
        Activity::factory()->count(2)->create(['risk_level' => 8]); // 高風險
        Activity::factory()->create(['signature' => '']); // 缺少簽章

        $report = $this->securityService->performSecurityAudit([
            'scope' => 'full'
        ]);

        $this->assertArrayHasKey('audit_id', $report);
        $this->assertArrayHasKey('statistics', $report);
        $this->assertArrayHasKey('results', $report);
        $this->assertEquals('completed', $report['status']);
    }

    /** @test */
    public function sensitive_data_filter_masks_sensitive_fields()
    {
        $data = [
            'username' => 'testuser',
            'password' => 'secret123',
            'email' => 'test@example.com',
            'api_key' => 'abc123def456',
            'normal_field' => 'normal_value'
        ];

        $filteredData = $this->sensitiveDataFilter->filterProperties($data);

        $this->assertStringContainsString('*', $filteredData['password']);
        $this->assertStringContainsString('*', $filteredData['email']);
        $this->assertStringContainsString('*', $filteredData['api_key']);
        $this->assertEquals('normal_value', $filteredData['normal_field']);
    }

    /** @test */
    public function it_identifies_sensitive_field_names()
    {
        $this->assertTrue($this->sensitiveDataFilter->isSensitiveField('password'));
        $this->assertTrue($this->sensitiveDataFilter->isSensitiveField('user_password'));
        $this->assertTrue($this->sensitiveDataFilter->isSensitiveField('api_key'));
        $this->assertTrue($this->sensitiveDataFilter->isSensitiveField('secret_token'));
        
        $this->assertFalse($this->sensitiveDataFilter->isSensitiveField('username'));
        $this->assertFalse($this->sensitiveDataFilter->isSensitiveField('name'));
    }

    /** @test */
    public function it_detects_sensitive_data_patterns()
    {
        $this->assertTrue($this->sensitiveDataFilter->containsSensitiveData('test@example.com'));
        $this->assertTrue($this->sensitiveDataFilter->containsSensitiveData('1234-5678-9012-3456'));
        $this->assertTrue($this->sensitiveDataFilter->containsSensitiveData('192.168.1.1'));
        
        $this->assertFalse($this->sensitiveDataFilter->containsSensitiveData('normal text'));
        $this->assertFalse($this->sensitiveDataFilter->containsSensitiveData('username123'));
    }

    /** @test */
    public function activity_model_security_methods_work_correctly()
    {
        Auth::login($this->adminUser);

        $regularActivity = Activity::factory()->create([
            'type' => 'user_login',
            'risk_level' => 3,
            'user_id' => $this->adminUser->id
        ]);

        $sensitiveActivity = Activity::factory()->create([
            'type' => 'permission_escalation',
            'risk_level' => 9
        ]);

        // 測試存取權限檢查
        $this->assertTrue($regularActivity->canAccess($this->adminUser, 'view'));
        $this->assertTrue($sensitiveActivity->canAccess($this->adminUser, 'view'));
        $this->assertFalse($regularActivity->canAccess($this->regularUser, 'delete'));

        // 測試敏感性檢查
        $this->assertFalse($regularActivity->isSensitive());
        $this->assertTrue($sensitiveActivity->isSensitive());

        // 測試保護狀態檢查
        $this->assertTrue($regularActivity->isProtected()); // 新建立的記錄在保護期內
        $this->assertTrue($sensitiveActivity->isProtected()); // 敏感記錄受保護
    }
}