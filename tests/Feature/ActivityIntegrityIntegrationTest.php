<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\User;
use App\Services\ActivityIntegrityService;
use App\Services\SensitiveDataFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityIntegrityIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected ActivityIntegrityService $integrityService;
    protected SensitiveDataFilter $sensitiveDataFilter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->integrityService = app(ActivityIntegrityService::class);
        $this->sensitiveDataFilter = app(SensitiveDataFilter::class);
    }

    /** @test */
    public function it_automatically_generates_signature_when_creating_activity()
    {
        $user = User::factory()->create();

        $activity = Activity::create([
            'type' => 'login',
            'description' => '使用者登入系統',
            'user_id' => $user->id,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'result' => 'success',
            'risk_level' => 1,
        ]);

        $this->assertNotEmpty($activity->signature);
        $this->assertStringStartsWith('v1:', $activity->signature);
        $this->assertTrue($this->integrityService->verifyActivity($activity));
    }

    /** @test */
    public function it_filters_sensitive_data_when_creating_activity()
    {
        $user = User::factory()->create();

        $activity = Activity::create([
            'type' => 'user_update',
            'description' => '更新使用者資料',
            'user_id' => $user->id,
            'properties' => [
                'username' => 'john_doe',
                'password' => 'secret123',
                'email' => 'john@example.com',
                'api_token' => 'abc123def456'
            ],
            'ip_address' => '192.168.1.1',
            'result' => 'success',
            'risk_level' => 2,
        ]);

        // 檢查敏感資料是否被過濾
        $this->assertStringContainsString('*', $activity->properties['password']);
        $this->assertStringContainsString('*', $activity->properties['api_token']);
        $this->assertEquals('john_doe', $activity->properties['username']); // 非敏感欄位保持不變
    }

    /** @test */
    public function it_prevents_activity_modification()
    {
        $user = User::factory()->create();

        $activity = Activity::create([
            'type' => 'login',
            'description' => '使用者登入系統',
            'user_id' => $user->id,
            'result' => 'success',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('活動記錄不允許修改，以確保審計追蹤的完整性');

        $activity->update(['description' => '篡改後的描述']);
    }

    /** @test */
    public function it_prevents_activity_deletion()
    {
        $user = User::factory()->create();

        $activity = Activity::create([
            'type' => 'login',
            'description' => '使用者登入系統',
            'user_id' => $user->id,
            'result' => 'success',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('活動記錄不允許刪除，以確保審計追蹤的完整性');

        $activity->delete();
    }

    /** @test */
    public function it_can_perform_integrity_check_on_multiple_activities()
    {
        $user = User::factory()->create();

        // 建立一些有效的活動記錄
        $validActivities = [];
        for ($i = 0; $i < 3; $i++) {
            $validActivities[] = Activity::create([
                'type' => 'login',
                'description' => "使用者登入系統 #{$i}",
                'user_id' => $user->id,
                'result' => 'success',
                'risk_level' => 1,
            ]);
        }

        // 建立一個無效簽章的活動記錄
        $invalidActivity = Activity::create([
            'type' => 'logout',
            'description' => '使用者登出系統',
            'user_id' => $user->id,
            'result' => 'success',
            'risk_level' => 1,
        ]);

        // 手動破壞簽章
        $invalidActivity->update(['signature' => 'invalid_signature']);

        // 執行完整性檢查
        $report = $this->integrityService->performIntegrityCheck();

        $this->assertEquals('completed', $report['status']);
        $this->assertEquals(4, $report['total_checked']);
        $this->assertEquals(3, $report['valid_records']);
        $this->assertEquals(1, $report['invalid_records']);
        $this->assertEquals(0, $report['missing_signatures']);
        $this->assertCount(1, $report['corrupted_records']);
    }

    /** @test */
    public function it_can_detect_tampering_attempts()
    {
        $user = User::factory()->create();

        $activity = Activity::create([
            'type' => 'login',
            'description' => '使用者登入系統',
            'user_id' => $user->id,
            'result' => 'success',
        ]);

        $originalData = [
            'type' => 'login',
            'description' => '使用者登入系統',
            'causer_id' => $user->id,
            'created_at' => $activity->created_at,
        ];

        // 模擬篡改
        $activity->description = '篡改後的描述';

        $isTampered = $this->integrityService->detectTamperingAttempt($activity, $originalData);

        $this->assertTrue($isTampered);
    }

    /** @test */
    public function it_can_regenerate_signature_for_corrupted_activity()
    {
        $user = User::factory()->create();

        $activity = Activity::create([
            'type' => 'login',
            'description' => '使用者登入系統',
            'user_id' => $user->id,
            'result' => 'success',
        ]);

        $originalSignature = $activity->signature;

        // 破壞簽章
        $activity->update(['signature' => 'corrupted_signature']);

        // 重新生成簽章
        $newSignature = $this->integrityService->regenerateSignature($activity);

        $this->assertNotEquals('corrupted_signature', $newSignature);
        $this->assertStringStartsWith('v1:', $newSignature);
        
        // 重新載入活動並驗證
        $activity->refresh();
        $this->assertTrue($this->integrityService->verifyActivity($activity));
    }

    /** @test */
    public function it_handles_activities_without_signatures_gracefully()
    {
        $user = User::factory()->create();

        // 建立沒有簽章的活動記錄（繞過觀察者）
        $activity = new Activity([
            'type' => 'login',
            'description' => '使用者登入系統',
            'user_id' => $user->id,
            'result' => 'success',
            'risk_level' => 1,
        ]);
        
        $activity->saveQuietly(); // 不觸發觀察者

        $this->assertEmpty($activity->signature);
        $this->assertFalse($this->integrityService->verifyActivity($activity));

        // 執行完整性檢查
        $report = $this->integrityService->performIntegrityCheck();

        $this->assertEquals(1, $report['missing_signatures']);
    }
}
