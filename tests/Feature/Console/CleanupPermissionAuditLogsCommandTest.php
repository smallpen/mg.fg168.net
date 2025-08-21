<?php

namespace Tests\Feature\Console;

use Tests\TestCase;
use App\Models\Permission;
use App\Models\User;
use App\Services\PermissionAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * 權限審計日誌清理命令測試
 */
class CleanupPermissionAuditLogsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected User $testUser;
    protected Permission $testPermission;
    protected PermissionAuditService $auditService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testUser = User::factory()->create();
        $this->testPermission = Permission::factory()->create();
        $this->auditService = app(PermissionAuditService::class);
    }

    /** @test */
    public function it_can_show_cleanup_statistics_in_dry_run_mode()
    {
        // 建立舊的審計日誌
        DB::table('permission_audit_logs')->insert([
            'action' => 'old_action',
            'permission_id' => $this->testPermission->id,
            'permission_name' => $this->testPermission->name,
            'user_id' => $this->testUser->id,
            'username' => $this->testUser->username,
            'data' => json_encode(['test' => 'data']),
            'created_at' => now()->subDays(400),
            'updated_at' => now()->subDays(400),
        ]);

        // 建立新的審計日誌
        $this->auditService->logPermissionChange('new_action', $this->testPermission, [], $this->testUser);

        $this->artisan('permission:audit-cleanup --days=365 --dry-run')
             ->expectsOutput('權限審計日誌清理工具')
             ->expectsOutput('這是模擬執行，不會實際刪除任何記錄')
             ->assertExitCode(0);

        // 確認資料沒有被刪除
        $this->assertDatabaseHas('permission_audit_logs', [
            'action' => 'old_action',
        ]);
    }

    /** @test */
    public function it_can_cleanup_old_audit_logs()
    {
        // 建立舊的審計日誌
        DB::table('permission_audit_logs')->insert([
            'action' => 'old_action',
            'permission_id' => $this->testPermission->id,
            'permission_name' => $this->testPermission->name,
            'user_id' => $this->testUser->id,
            'username' => $this->testUser->username,
            'data' => json_encode(['test' => 'data']),
            'created_at' => now()->subDays(400),
            'updated_at' => now()->subDays(400),
        ]);

        // 建立新的審計日誌
        $this->auditService->logPermissionChange('new_action', $this->testPermission, [], $this->testUser);

        $this->artisan('permission:audit-cleanup --days=365 --force')
             ->expectsOutput('權限審計日誌清理工具')
             ->assertExitCode(0);

        // 確認舊資料被刪除
        $this->assertDatabaseMissing('permission_audit_logs', [
            'action' => 'old_action',
        ]);

        // 確認新資料仍然存在
        $this->assertDatabaseHas('permission_audit_logs', [
            'action' => 'new_action',
        ]);
    }

    /** @test */
    public function it_shows_no_records_message_when_nothing_to_cleanup()
    {
        // 只建立新的審計日誌
        $this->auditService->logPermissionChange('recent_action', $this->testPermission, [], $this->testUser);

        $this->artisan('permission:audit-cleanup --days=365 --dry-run')
             ->expectsOutput('沒有需要清理的記錄')
             ->assertExitCode(0);
    }

    /** @test */
    public function it_validates_days_parameter()
    {
        $this->artisan('permission:audit-cleanup --days=0')
             ->expectsOutput('保留天數必須大於 0')
             ->assertExitCode(1);

        $this->artisan('permission:audit-cleanup --days=-10')
             ->expectsOutput('保留天數必須大於 0')
             ->assertExitCode(1);
    }

    /** @test */
    public function it_requires_confirmation_without_force_flag()
    {
        // 建立舊的審計日誌
        DB::table('permission_audit_logs')->insert([
            'action' => 'old_action',
            'permission_id' => $this->testPermission->id,
            'permission_name' => $this->testPermission->name,
            'user_id' => $this->testUser->id,
            'username' => $this->testUser->username,
            'data' => json_encode(['test' => 'data']),
            'created_at' => now()->subDays(400),
            'updated_at' => now()->subDays(400),
        ]);

        // 測試拒絕確認
        $this->artisan('permission:audit-cleanup --days=365')
             ->expectsConfirmation('確定要刪除 1 筆權限審計日誌記錄嗎？', 'no')
             ->expectsOutput('操作已取消')
             ->assertExitCode(0);

        // 確認資料沒有被刪除
        $this->assertDatabaseHas('permission_audit_logs', [
            'action' => 'old_action',
        ]);
    }

    /** @test */
    public function it_handles_cleanup_errors_gracefully()
    {
        // 模擬資料庫錯誤
        DB::shouldReceive('table')
          ->with('permission_audit_logs')
          ->andThrow(new \Exception('Database connection failed'));

        $this->artisan('permission:audit-cleanup --days=365 --force')
             ->expectsOutput('清理權限審計日誌時發生錯誤: Database connection failed')
             ->assertExitCode(1);
    }

    /** @test */
    public function it_shows_statistics_table_correctly()
    {
        // 建立多筆不同的審計日誌（超過 365 天）
        for ($i = 0; $i < 5; $i++) {
            DB::table('permission_audit_logs')->insert([
                'action' => "old_action_{$i}",
                'permission_id' => $this->testPermission->id,
                'permission_name' => $this->testPermission->name,
                'user_id' => $this->testUser->id,
                'username' => $this->testUser->username,
                'data' => json_encode(['test' => 'data']),
                'created_at' => now()->subDays(400 + $i),
                'updated_at' => now()->subDays(400 + $i),
            ]);
        }

        $this->artisan('permission:audit-cleanup --days=365 --dry-run')
             ->expectsOutput('權限審計日誌清理工具')
             ->expectsOutput('這是模擬執行，不會實際刪除任何記錄')
             ->assertExitCode(0);
    }
}