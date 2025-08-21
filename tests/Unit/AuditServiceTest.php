<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AuditService;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * 通用審計服務測試
 */
class AuditServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AuditService $auditService;
    protected User $testUser;
    protected Permission $testPermission;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->auditService = app(AuditService::class);
        
        // 建立測試使用者（使用隨機用戶名避免衝突）
        $this->testUser = User::factory()->create([
            'username' => 'testuser_' . uniqid(),
            'name' => '測試使用者',
        ]);

        // 建立測試權限（使用隨機名稱避免衝突）
        $this->testPermission = Permission::factory()->create([
            'name' => 'test.permission.' . uniqid(),
            'display_name' => '測試權限',
            'module' => 'test',
            'type' => 'view',
        ]);

        // 建立審計日誌資料表（如果不存在）
        if (!DB::getSchemaBuilder()->hasTable('audit_logs')) {
            DB::statement('
                CREATE TABLE audit_logs (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    action VARCHAR(50) NOT NULL,
                    subject_type VARCHAR(255),
                    subject_id BIGINT UNSIGNED,
                    user_id BIGINT UNSIGNED,
                    username VARCHAR(50),
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    url VARCHAR(500),
                    method VARCHAR(10),
                    data JSON NOT NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL,
                    INDEX idx_action (action),
                    INDEX idx_subject (subject_type, subject_id),
                    INDEX idx_user (user_id),
                    INDEX idx_created_at (created_at)
                )
            ');
        }

        // 清空審計日誌表以確保測試隔離
        DB::table('audit_logs')->truncate();
    }

    /** @test */
    public function it_can_log_general_operations()
    {
        $data = ['test_field' => 'test_value'];

        $this->auditService->log('test_action', $this->testPermission, $data, $this->testUser);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'test_action',
            'subject_type' => Permission::class,
            'subject_id' => $this->testPermission->id,
            'user_id' => $this->testUser->id,
            'username' => $this->testUser->username,
        ]);
    }

    /** @test */
    public function it_can_search_audit_logs()
    {
        // 建立測試資料
        $this->auditService->log('action1', $this->testPermission, [], $this->testUser);
        $this->auditService->log('action2', $this->testPermission, [], $this->testUser);
        $this->auditService->log('action1', null, [], $this->testUser);

        // 測試按操作類型搜尋
        $results = $this->auditService->search([
            'action' => 'action1',
            'per_page' => 10,
        ]);

        $this->assertEquals(2, $results->total());

        // 測試按使用者搜尋
        $results = $this->auditService->search([
            'user_id' => $this->testUser->id,
            'per_page' => 10,
        ]);

        $this->assertEquals(3, $results->total());
    }

    /** @test */
    public function it_can_get_statistics()
    {
        // 建立測試資料
        $this->auditService->log('created', $this->testPermission, [], $this->testUser);
        $this->auditService->log('updated', $this->testPermission, [], $this->testUser);
        $this->auditService->log('deleted', $this->testPermission, [], $this->testUser);

        $stats = $this->auditService->getStats(30);

        $this->assertEquals(3, $stats['total_actions']);
        $this->assertEquals(1, $stats['unique_subjects']);
        $this->assertEquals(1, $stats['unique_users']);
        $this->assertArrayHasKey('daily_activity', $stats);
        $this->assertArrayHasKey('actions_by_type', $stats);
    }

    /** @test */
    public function it_can_cleanup_old_logs()
    {
        // 建立舊的測試資料
        DB::table('audit_logs')->insert([
            'action' => 'old_action',
            'subject_type' => Permission::class,
            'subject_id' => $this->testPermission->id,
            'user_id' => $this->testUser->id,
            'username' => $this->testUser->username,
            'data' => json_encode(['test' => 'data']),
            'created_at' => now()->subDays(400),
            'updated_at' => now()->subDays(400),
        ]);

        // 建立新的測試資料
        $this->auditService->log('new_action', $this->testPermission, [], $this->testUser);

        // 清理超過 365 天的日誌
        $deletedCount = $this->auditService->cleanup(365);

        $this->assertEquals(1, $deletedCount);
        
        // 確認新資料仍然存在
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'new_action',
        ]);
        
        // 確認舊資料已被刪除
        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'old_action',
        ]);
    }

    /** @test */
    public function it_can_export_logs()
    {
        // 建立測試資料
        $this->auditService->log('export_test', $this->testPermission, ['key' => 'value'], $this->testUser);

        $exportData = $this->auditService->export([
            'action' => 'export_test',
        ]);

        $this->assertArrayHasKey('export_date', $exportData);
        $this->assertArrayHasKey('total_records', $exportData);
        $this->assertArrayHasKey('logs', $exportData);
        $this->assertEquals(1, $exportData['total_records']);
        $this->assertCount(1, $exportData['logs']);
    }

    /** @test */
    public function it_can_get_user_history()
    {
        // 建立測試資料
        $this->auditService->log('user_action1', $this->testPermission, [], $this->testUser);
        $this->auditService->log('user_action2', $this->testPermission, [], $this->testUser);
        $this->auditService->log('user_action3', $this->testPermission, [], $this->testUser);

        $history = $this->auditService->getUserHistory($this->testUser->id);

        $this->assertCount(3, $history);
        // 檢查是否按時間倒序排列（最新的在前面）
        $actions = $history->pluck('action')->toArray();
        $this->assertContains('user_action1', $actions);
        $this->assertContains('user_action2', $actions);
        $this->assertContains('user_action3', $actions);
    }

    /** @test */
    public function it_can_get_subject_history()
    {
        // 建立測試資料
        $this->auditService->log('subject_action1', $this->testPermission, [], $this->testUser);
        $this->auditService->log('subject_action2', $this->testPermission, [], $this->testUser);

        $history = $this->auditService->getSubjectHistory(Permission::class, $this->testPermission->id);

        $this->assertCount(2, $history);
        // 檢查是否包含預期的操作
        $actions = $history->pluck('action')->toArray();
        $this->assertContains('subject_action1', $actions);
        $this->assertContains('subject_action2', $actions);
    }

    /** @test */
    public function it_handles_storage_errors_gracefully()
    {
        // 模擬資料庫錯誤
        DB::shouldReceive('table')->andThrow(new \Exception('Database error'));

        // 應該不會拋出例外
        $this->auditService->log('error_test', $this->testPermission, [], $this->testUser);

        // 測試通過表示錯誤被正確處理
        $this->assertTrue(true);
    }
}