<?php

namespace Tests\Unit;

use App\Models\Activity;
use App\Models\User;
use App\Services\ActivityIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;
use Carbon\Carbon;

class ActivityIntegrityServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ActivityIntegrityService $integrityService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->integrityService = new ActivityIntegrityService();
        
        // 暫時停用活動記錄保護以便測試
        config(['activity-log.security.prevent_tampering' => false]);
    }

    /** @test */
    public function it_can_generate_signature_for_activity_data()
    {
        $data = [
            'type' => 'user.login',
            'description' => '使用者登入',
            'user_id' => 1,
            'created_at' => '2024-01-15 10:30:25'
        ];

        $signature = $this->integrityService->generateSignature($data);

        $this->assertNotEmpty($signature);
        $this->assertStringStartsWith('v1:', $signature);
        $this->assertEquals(67, strlen($signature)); // v1: + 64 chars hash
    }

    /** @test */
    public function it_generates_consistent_signatures_for_same_data()
    {
        $data = [
            'type' => 'user.login',
            'description' => '使用者登入',
            'user_id' => 1,
            'created_at' => '2024-01-15 10:30:25'
        ];

        $signature1 = $this->integrityService->generateSignature($data);
        $signature2 = $this->integrityService->generateSignature($data);

        $this->assertEquals($signature1, $signature2);
    }

    /** @test */
    public function it_generates_different_signatures_for_different_data()
    {
        $data1 = [
            'type' => 'user.login',
            'description' => '使用者登入',
            'user_id' => 1,
            'created_at' => '2024-01-15 10:30:25'
        ];

        $data2 = [
            'type' => 'user.logout',
            'description' => '使用者登出',
            'user_id' => 1,
            'created_at' => '2024-01-15 10:30:25'
        ];

        $signature1 = $this->integrityService->generateSignature($data1);
        $signature2 = $this->integrityService->generateSignature($data2);

        $this->assertNotEquals($signature1, $signature2);
    }

    /** @test */
    public function it_can_verify_valid_activity_integrity()
    {
        $user = User::factory()->create();
        $activity = Activity::factory()->create([
            'type' => 'login',
            'description' => '使用者登入',
            'user_id' => $user->id,
        ]);

        // 手動生成簽章
        $data = [
            'type' => $activity->type,
            'event' => $activity->event,
            'description' => $activity->description,
            'module' => $activity->module,
            'user_id' => $activity->user_id,
            'subject_type' => $activity->subject_type,
            'subject_id' => $activity->subject_id,
            'properties' => $activity->properties,
            'ip_address' => $activity->ip_address,
            'user_agent' => $activity->user_agent,
            'result' => $activity->result,
            'risk_level' => $activity->risk_level,
            'created_at' => $activity->created_at
        ];

        $signature = $this->integrityService->generateSignature($data);
        $activity->update(['signature' => $signature]);

        $isValid = $this->integrityService->verifyActivity($activity);

        $this->assertTrue($isValid);
    }

    /** @test */
    public function it_can_detect_tampered_activity()
    {
        $user = User::factory()->create();
        $activity = Activity::factory()->create([
            'type' => 'login',
            'description' => '使用者登入',
            'user_id' => $user->id,
        ]);

        // 生成原始簽章
        $data = [
            'type' => $activity->type,
            'event' => $activity->event,
            'description' => $activity->description,
            'module' => $activity->module,
            'user_id' => $activity->user_id,
            'subject_type' => $activity->subject_type,
            'subject_id' => $activity->subject_id,
            'properties' => $activity->properties,
            'ip_address' => $activity->ip_address,
            'user_agent' => $activity->user_agent,
            'result' => $activity->result,
            'risk_level' => $activity->risk_level,
            'created_at' => $activity->created_at
        ];

        $signature = $this->integrityService->generateSignature($data);
        $activity->signature = $signature;

        // 篡改資料
        $activity->description = '篡改後的描述';

        $isValid = $this->integrityService->verifyActivity($activity);

        $this->assertFalse($isValid);
    }

    /** @test */
    public function it_can_verify_batch_activities()
    {
        $user = User::factory()->create();
        $activities = Activity::factory()->count(3)->create(['user_id' => $user->id]);

        // 為每個活動生成簽章
        foreach ($activities as $activity) {
            $data = [
                'type' => $activity->type,
                'event' => $activity->event,
                'description' => $activity->description,
                'module' => $activity->module,
                'user_id' => $activity->user_id,
                'subject_type' => $activity->subject_type,
                'subject_id' => $activity->subject_id,
                'properties' => $activity->properties,
                'ip_address' => $activity->ip_address,
                'user_agent' => $activity->user_agent,
                'result' => $activity->result,
                'risk_level' => $activity->risk_level,
                'created_at' => $activity->created_at
            ];

            $signature = $this->integrityService->generateSignature($data);
            $activity->update(['signature' => $signature]);
        }

        $results = $this->integrityService->verifyBatch($activities);

        $this->assertCount(3, $results);
        $this->assertTrue($results[$activities[0]->id]);
        $this->assertTrue($results[$activities[1]->id]);
        $this->assertTrue($results[$activities[2]->id]);
    }

    /** @test */
    public function it_can_perform_integrity_check_and_generate_report()
    {
        $user = User::factory()->create();
        
        // 建立一些測試活動
        $validActivities = Activity::factory()->count(2)->create(['user_id' => $user->id]);
        $invalidActivity = Activity::factory()->create([
            'signature' => 'invalid_signature',
            'user_id' => $user->id
        ]);
        $noSignatureActivity = Activity::factory()->create([
            'signature' => null,
            'user_id' => $user->id
        ]);

        // 為有效活動生成正確的簽章
        foreach ($validActivities as $activity) {
            $data = [
                'type' => $activity->type,
                'event' => $activity->event,
                'description' => $activity->description,
                'module' => $activity->module,
                'user_id' => $activity->user_id,
                'subject_type' => $activity->subject_type,
                'subject_id' => $activity->subject_id,
                'properties' => $activity->properties,
                'ip_address' => $activity->ip_address,
                'user_agent' => $activity->user_agent,
                'result' => $activity->result,
                'risk_level' => $activity->risk_level,
                'created_at' => $activity->created_at
            ];

            $signature = $this->integrityService->generateSignature($data);
            $activity->update(['signature' => $signature]);
        }

        $report = $this->integrityService->performIntegrityCheck([
            'batch_size' => 10
        ]);

        $this->assertEquals('completed', $report['status']);
        $this->assertEquals(4, $report['total_checked']);
        $this->assertEquals(2, $report['valid_records']);
        $this->assertEquals(1, $report['invalid_records']);
        $this->assertEquals(1, $report['missing_signatures']);
        $this->assertCount(1, $report['corrupted_records']);
        $this->assertGreaterThan(0, $report['execution_time']);
    }

    /** @test */
    public function it_can_detect_tampering_attempts()
    {
        $user = User::factory()->create();
        $activity = Activity::factory()->create([
            'type' => 'login',
            'description' => '使用者登入',
            'user_id' => $user->id,
        ]);

        $originalData = [
            'type' => 'login',
            'description' => '使用者登入',
            'causer_id' => $activity->user_id,
            'created_at' => $activity->created_at,
        ];

        // 篡改活動資料
        $activity->description = '篡改後的描述';

        $isTampered = $this->integrityService->detectTamperingAttempt($activity, $originalData);

        $this->assertTrue($isTampered);
    }

    /** @test */
    public function it_can_regenerate_signature_for_activity()
    {
        $user = User::factory()->create();
        $activity = Activity::factory()->create([
            'signature' => 'old_signature',
            'user_id' => $user->id
        ]);

        $newSignature = $this->integrityService->regenerateSignature($activity);

        $this->assertNotEquals('old_signature', $newSignature);
        $this->assertStringStartsWith('v1:', $newSignature);
        
        // 重新載入活動以檢查資料庫中的簽章是否已更新
        $activity->refresh();
        $this->assertEquals($newSignature, $activity->signature);
    }

    /** @test */
    public function it_handles_missing_app_key_gracefully()
    {
        // 暫時清除應用程式金鑰
        config(['app.key' => '']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('應用程式金鑰未設定，無法生成簽章');

        $this->integrityService->generateSignature(['test' => 'data']);
    }

    /** @test */
    public function it_handles_verification_errors_gracefully()
    {
        $user = User::factory()->create();
        $activity = Activity::factory()->create([
            'signature' => 'invalid_signature',
            'properties' => null, // 這可能會導致 JSON 編碼問題
            'user_id' => $user->id
        ]);

        $isValid = $this->integrityService->verifyActivity($activity);

        $this->assertFalse($isValid);
    }
}