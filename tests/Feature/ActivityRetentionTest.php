<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityRetentionPolicy;
use App\Models\ActivityCleanupLog;
use App\Models\ArchivedActivity;
use App\Models\User;
use App\Services\ActivityRetentionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

/**
 * 活動記錄保留政策測試
 */
class ActivityRetentionTest extends TestCase
{
    use RefreshDatabase;

    protected ActivityRetentionService $retentionService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->retentionService = app(ActivityRetentionService::class);
        
        // 建立測試使用者
        $this->user = User::factory()->create([
            'username' => 'test_admin',
            'name' => '測試管理員',
            'email' => 'test@example.com',
        ]);
        
        $this->actingAs($this->user);
        
        // 確保資料庫表格存在
        $this->artisan('migrate:fresh');
    }

    /**
     * 測試建立預設保留政策
     */
    public function test_can_create_default_policies(): void
    {
        ActivityRetentionPolicy::createDefaultPolicies();
        
        $this->assertDatabaseHas('activity_retention_policies', [
            'name' => '一般活動記錄',
            'retention_days' => 90,
            'action' => 'archive',
        ]);
        
        $this->assertDatabaseHas('activity_retention_policies', [
            'name' => '安全事件記錄',
            'retention_days' => 365,
            'action' => 'archive',
        ]);
        
        $this->assertDatabaseHas('activity_retention_policies', [
            'name' => '系統管理操作',
            'module' => 'system',
            'retention_days' => 1095,
            'action' => 'archive',
        ]);
    }

    /**
     * 測試政策適用性檢查
     */
    public function test_policy_applies_to_activity(): void
    {
        // 建立測試政策
        $policy = ActivityRetentionPolicy::create([
            'name' => '測試政策',
            'activity_type' => 'login',
            'module' => 'auth',
            'retention_days' => 30,
            'action' => 'archive',
            'is_active' => true,
            'priority' => 1,
        ]);

        // 建立符合條件的活動
        $matchingActivity = Activity::create([
            'type' => 'login',
            'module' => 'auth',
            'description' => '使用者登入',
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(35),
        ]);

        // 建立不符合條件的活動
        $nonMatchingActivity = Activity::create([
            'type' => 'logout',
            'module' => 'auth',
            'description' => '使用者登出',
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(35),
        ]);

        $this->assertTrue($policy->appliesTo($matchingActivity));
        $this->assertFalse($policy->appliesTo($nonMatchingActivity));
    }

    /**
     * 測試政策執行（歸檔）
     */
    public function test_can_execute_archive_policy(): void
    {
        // 建立測試政策
        $policy = ActivityRetentionPolicy::create([
            'name' => '測試歸檔政策',
            'retention_days' => 30,
            'action' => 'archive',
            'is_active' => true,
            'priority' => 1,
            'created_by' => $this->user->id,
        ]);

        // 建立過期的活動記錄
        $oldActivity = Activity::create([
            'type' => 'test_action',
            'description' => '測試活動',
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(35),
        ]);

        // 建立未過期的活動記錄
        $recentActivity = Activity::create([
            'type' => 'test_action',
            'description' => '最近活動',
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(15),
        ]);

        // 執行政策
        $result = $this->retentionService->executePolicy($policy);

        // 驗證結果
        $this->assertEquals('completed', $result['status']);
        $this->assertEquals(1, $result['records_processed']);
        $this->assertEquals(1, $result['records_deleted']);
        $this->assertEquals(1, $result['records_archived']);

        // 驗證原始記錄已刪除
        $this->assertDatabaseMissing('activities', ['id' => $oldActivity->id]);
        
        // 驗證歸檔記錄已建立
        $this->assertDatabaseHas('archived_activities', [
            'original_id' => $oldActivity->id,
            'type' => 'test_action',
            'description' => '測試活動',
        ]);

        // 驗證未過期記錄仍存在
        $this->assertDatabaseHas('activities', ['id' => $recentActivity->id]);

        // 驗證清理日誌已建立
        $this->assertDatabaseHas('activity_cleanup_logs', [
            'policy_id' => $policy->id,
            'type' => 'automatic',
            'action' => 'archive',
            'status' => 'completed',
            'records_processed' => 1,
        ]);
    }

    /**
     * 測試政策執行（刪除）
     */
    public function test_can_execute_delete_policy(): void
    {
        // 建立測試政策
        $policy = ActivityRetentionPolicy::create([
            'name' => '測試刪除政策',
            'retention_days' => 30,
            'action' => 'delete',
            'is_active' => true,
            'priority' => 1,
            'created_by' => $this->user->id,
        ]);

        // 建立過期的活動記錄
        $oldActivity = Activity::create([
            'type' => 'test_action',
            'description' => '測試活動',
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(35),
        ]);

        // 執行政策
        $result = $this->retentionService->executePolicy($policy);

        // 驗證結果
        $this->assertEquals('completed', $result['status']);
        $this->assertEquals(1, $result['records_processed']);
        $this->assertEquals(1, $result['records_deleted']);
        $this->assertEquals(0, $result['records_archived']);

        // 驗證原始記錄已刪除
        $this->assertDatabaseMissing('activities', ['id' => $oldActivity->id]);
        
        // 驗證沒有建立歸檔記錄
        $this->assertDatabaseMissing('archived_activities', [
            'original_id' => $oldActivity->id,
        ]);
    }

    /**
     * 測試手動清理功能
     */
    public function test_can_execute_manual_cleanup(): void
    {
        // 建立測試活動記錄
        $activity1 = Activity::create([
            'type' => 'login',
            'module' => 'auth',
            'description' => '使用者登入',
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(35),
        ]);

        $activity2 = Activity::create([
            'type' => 'logout',
            'module' => 'auth',
            'description' => '使用者登出',
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(25),
        ]);

        // 執行手動清理
        $criteria = [
            'date_from' => Carbon::now()->subDays(40)->format('Y-m-d'),
            'date_to' => Carbon::now()->subDays(20)->format('Y-m-d'),
            'module' => 'auth',
        ];

        $result = $this->retentionService->manualCleanup($criteria, 'archive');

        // 驗證結果
        $this->assertEquals('completed', $result['status']);
        $this->assertEquals(2, $result['records_processed']);
        $this->assertEquals(2, $result['records_deleted']);
        $this->assertEquals(2, $result['records_archived']);

        // 驗證清理日誌已建立
        $this->assertDatabaseHas('activity_cleanup_logs', [
            'type' => 'manual',
            'action' => 'archive',
            'status' => 'completed',
            'records_processed' => 2,
        ]);
    }

    /**
     * 測試測試執行模式
     */
    public function test_dry_run_mode_does_not_modify_data(): void
    {
        // 建立測試政策
        $policy = ActivityRetentionPolicy::create([
            'name' => '測試政策',
            'retention_days' => 30,
            'action' => 'archive',
            'is_active' => true,
            'priority' => 1,
            'created_by' => $this->user->id,
        ]);

        // 建立過期的活動記錄
        $oldActivity = Activity::create([
            'type' => 'test_action',
            'description' => '測試活動',
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(35),
        ]);

        // 執行測試模式
        $result = $this->retentionService->executePolicy($policy, true);

        // 驗證結果
        $this->assertEquals('completed', $result['status']);
        $this->assertEquals(1, $result['records_processed']);

        // 驗證原始記錄仍存在（測試模式不應刪除資料）
        $this->assertDatabaseHas('activities', ['id' => $oldActivity->id]);
        
        // 驗證沒有建立歸檔記錄
        $this->assertDatabaseMissing('archived_activities', [
            'original_id' => $oldActivity->id,
        ]);
    }

    /**
     * 測試政策預覽功能
     */
    public function test_can_preview_policy_impact(): void
    {
        // 建立測試政策
        $policy = ActivityRetentionPolicy::create([
            'name' => '測試政策',
            'retention_days' => 30,
            'action' => 'archive',
            'is_active' => true,
            'priority' => 1,
        ]);

        // 建立測試活動記錄
        Activity::create([
            'type' => 'login',
            'description' => '使用者登入',
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(35),
        ]);

        Activity::create([
            'type' => 'logout',
            'description' => '使用者登出',
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(25),
        ]);

        Activity::create([
            'type' => 'login',
            'description' => '最近登入',
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(15),
        ]);

        // 取得預覽資料
        $preview = $this->retentionService->previewPolicyImpact($policy);

        // 驗證預覽結果
        $this->assertEquals(2, $preview['total_records']); // 只有 2 筆過期記錄
        $this->assertArrayHasKey('breakdown', $preview);
        $this->assertArrayHasKey('policy_info', $preview);
        $this->assertEquals('測試政策', $preview['policy_info']['name']);
    }

    /**
     * 測試歸檔記錄還原功能
     */
    public function test_can_restore_archived_activities(): void
    {
        // 建立歸檔記錄
        $archivedActivity = ArchivedActivity::create([
            'original_id' => 999,
            'type' => 'login',
            'description' => '使用者登入',
            'user_id' => $this->user->id,
            'original_created_at' => Carbon::now()->subDays(35),
            'archived_at' => Carbon::now(),
            'archived_by' => $this->user->id,
            'archive_reason' => '測試歸檔',
        ]);

        // 執行還原
        $result = $this->retentionService->restoreArchivedActivities([$archivedActivity->id]);

        // 驗證結果
        $this->assertEquals('completed', $result['status']);
        $this->assertEquals(1, $result['restored']);
        $this->assertEquals(0, $result['failed']);

        // 驗證活動記錄已還原
        $this->assertDatabaseHas('activities', [
            'type' => 'login',
            'description' => '使用者登入',
            'user_id' => $this->user->id,
        ]);

        // 驗證歸檔記錄已刪除
        $this->assertDatabaseMissing('archived_activities', [
            'id' => $archivedActivity->id,
        ]);
    }

    /**
     * 測試條件政策
     */
    public function test_policy_with_conditions(): void
    {
        // 建立帶條件的政策
        $policy = ActivityRetentionPolicy::create([
            'name' => '高風險活動政策',
            'retention_days' => 30,
            'action' => 'archive',
            'is_active' => true,
            'priority' => 1,
            'conditions' => ['risk_level' => ['>=', 5]],
            'created_by' => $this->user->id,
        ]);

        // 建立高風險活動
        $highRiskActivity = Activity::create([
            'type' => 'security_event',
            'description' => '高風險活動',
            'user_id' => $this->user->id,
            'risk_level' => 8,
            'created_at' => Carbon::now()->subDays(35),
        ]);

        // 建立低風險活動
        $lowRiskActivity = Activity::create([
            'type' => 'normal_action',
            'description' => '一般活動',
            'user_id' => $this->user->id,
            'risk_level' => 2,
            'created_at' => Carbon::now()->subDays(35),
        ]);

        // 執行政策
        $result = $this->retentionService->executePolicy($policy);

        // 驗證只有高風險活動被處理
        $this->assertEquals(1, $result['records_processed']);
        
        // 驗證高風險活動已歸檔
        $this->assertDatabaseMissing('activities', ['id' => $highRiskActivity->id]);
        $this->assertDatabaseHas('archived_activities', [
            'original_id' => $highRiskActivity->id,
        ]);

        // 驗證低風險活動仍存在
        $this->assertDatabaseHas('activities', ['id' => $lowRiskActivity->id]);
    }
}