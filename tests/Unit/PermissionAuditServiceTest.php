<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\PermissionAuditService;
use App\Models\Permission;
use App\Models\User;
use App\Models\PermissionAuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

/**
 * 權限審計服務測試
 */
class PermissionAuditServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PermissionAuditService $auditService;
    protected User $testUser;
    protected Permission $testPermission;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->auditService = app(PermissionAuditService::class);
        
        // 建立測試使用者
        $this->testUser = User::factory()->create([
            'username' => 'testuser',
            'name' => '測試使用者',
        ]);
        
        // 建立測試權限
        $this->testPermission = Permission::factory()->create([
            'name' => 'test.permission',
            'display_name' => '測試權限',
            'module' => 'test',
            'type' => 'view',
        ]);
    }

    /**
     * 測試記錄權限變更
     */
    public function test_log_permission_change(): void
    {
        $changes = [
            'display_name' => [
                'old' => '舊名稱',
                'new' => '新名稱',
            ],
        ];

        $this->auditService->logPermissionChange('updated', $this->testPermission, $changes, $this->testUser);

        // 驗證日誌是否正確記錄
        $this->assertDatabaseHas('permission_audit_logs', [
            'action' => 'updated',
            'permission_id' => $this->testPermission->id,
            'permission_name' => $this->testPermission->name,
            'user_id' => $this->testUser->id,
            'username' => $this->testUser->username,
        ]);

        $log = PermissionAuditLog::where('action', 'updated')->first();
        $this->assertNotNull($log);
        $this->assertEquals($changes, $log->data['changes']);
    }

    /**
     * 測試記錄依賴關係變更
     */
    public function test_log_dependency_change(): void
    {
        $dependencyPermission = Permission::factory()->create([
            'name' => 'dependency.permission',
            'display_name' => '依賴權限',
        ]);

        $this->auditService->logDependencyChange(
            $this->testPermission,
            'added',
            [$dependencyPermission->id],
            $this->testUser
        );

        $this->assertDatabaseHas('permission_audit_logs', [
            'action' => 'dependency_added',
            'permission_id' => $this->testPermission->id,
            'user_id' => $this->testUser->id,
        ]);

        $log = PermissionAuditLog::where('action', 'dependency_added')->first();
        $this->assertEquals([$dependencyPermission->id], $log->data['dependency_ids']);
        $this->assertEquals(['dependency.permission'], $log->data['dependency_names']);
    }

    /**
     * 測試記錄角色指派變更
     */
    public function test_log_role_assignment_change(): void
    {
        // 建立測試角色
        $role = \App\Models\Role::factory()->create([
            'name' => 'test_role',
            'display_name' => '測試角色',
        ]);

        $this->auditService->logRoleAssignmentChange(
            $this->testPermission,
            'assigned',
            [$role->id],
            $this->testUser
        );

        $this->assertDatabaseHas('permission_audit_logs', [
            'action' => 'role_assigned',
            'permission_id' => $this->testPermission->id,
            'user_id' => $this->testUser->id,
        ]);

        $log = PermissionAuditLog::where('action', 'role_assigned')->first();
        $this->assertEquals([$role->id], $log->data['role_ids']);
        $this->assertEquals(['test_role'], $log->data['role_names']);
    }

    /**
     * 測試記錄匯入匯出操作
     */
    public function test_log_import_export_action(): void
    {
        $exportData = [
            'total_permissions' => 10,
            'exported_at' => now()->toISOString(),
        ];

        $this->auditService->logImportExportAction('exported', $exportData, $this->testUser);

        $this->assertDatabaseHas('permission_audit_logs', [
            'action' => 'permission_exported',
            'user_id' => $this->testUser->id,
        ]);

        $log = PermissionAuditLog::where('action', 'permission_exported')->first();
        $this->assertEquals($exportData, $log->data['data']);
    }

    /**
     * 測試取得權限審計日誌
     */
    public function test_get_permission_audit_log(): void
    {
        // 建立多筆測試日誌
        PermissionAuditLog::factory()->count(5)->create([
            'permission_id' => $this->testPermission->id,
        ]);

        PermissionAuditLog::factory()->count(3)->create([
            'permission_id' => Permission::factory()->create()->id,
        ]);

        $logs = $this->auditService->getPermissionAuditLog($this->testPermission->id);

        $this->assertCount(5, $logs);
        $logs->each(function ($log) {
            $this->assertEquals($this->testPermission->id, $log->permission_id);
        });
    }

    /**
     * 測試搜尋審計日誌
     */
    public function test_search_audit_log(): void
    {
        // 建立不同類型的測試日誌
        PermissionAuditLog::factory()->create([
            'action' => 'created',
            'permission_id' => $this->testPermission->id,
            'user_id' => $this->testUser->id,
            'created_at' => now()->subDays(5),
        ]);

        PermissionAuditLog::factory()->create([
            'action' => 'updated',
            'permission_id' => $this->testPermission->id,
            'user_id' => $this->testUser->id,
            'created_at' => now()->subDays(3),
        ]);

        PermissionAuditLog::factory()->create([
            'action' => 'deleted',
            'permission_id' => Permission::factory()->create()->id,
            'user_id' => User::factory()->create()->id,
            'created_at' => now()->subDays(1),
        ]);

        // 測試按操作類型篩選
        $results = $this->auditService->searchAuditLog([
            'action' => 'created',
            'per_page' => 10,
        ]);

        $this->assertEquals(1, $results->total());
        $this->assertEquals('created', $results->first()->action);

        // 測試按使用者篩選
        $results = $this->auditService->searchAuditLog([
            'user_id' => $this->testUser->id,
            'per_page' => 10,
        ]);

        $this->assertEquals(2, $results->total());

        // 測試按日期範圍篩選
        $results = $this->auditService->searchAuditLog([
            'start_date' => now()->subDays(4)->format('Y-m-d H:i:s'),
            'end_date' => now()->subDays(2)->format('Y-m-d H:i:s'),
            'per_page' => 10,
        ]);

        $this->assertEquals(1, $results->total());
        $this->assertEquals('updated', $results->first()->action);
    }

    /**
     * 測試取得審計統計資料
     */
    public function test_get_audit_stats(): void
    {
        // 建立不同日期的測試日誌
        PermissionAuditLog::factory()->create([
            'action' => 'created',
            'permission_id' => $this->testPermission->id,
            'user_id' => $this->testUser->id,
            'created_at' => now()->subDays(5),
        ]);

        PermissionAuditLog::factory()->create([
            'action' => 'updated',
            'permission_id' => $this->testPermission->id,
            'user_id' => $this->testUser->id,
            'created_at' => now()->subDays(3),
        ]);

        PermissionAuditLog::factory()->create([
            'action' => 'created',
            'permission_id' => Permission::factory()->create()->id,
            'user_id' => User::factory()->create()->id,
            'created_at' => now()->subDays(1),
        ]);

        $stats = $this->auditService->getAuditStats(30);

        $this->assertEquals(3, $stats['total_actions']);
        $this->assertEquals(2, $stats['unique_permissions']);
        $this->assertEquals(2, $stats['unique_users']);
        $this->assertArrayHasKey('daily_activity', $stats);
        $this->assertArrayHasKey('actions_by_type', $stats);
    }

    /**
     * 測試清理舊日誌
     */
    public function test_cleanup_old_audit_logs(): void
    {
        // 建立新舊不同的日誌
        PermissionAuditLog::factory()->create([
            'created_at' => now()->subDays(10), // 新日誌
        ]);

        PermissionAuditLog::factory()->create([
            'created_at' => now()->subDays(400), // 舊日誌
        ]);

        PermissionAuditLog::factory()->create([
            'created_at' => now()->subDays(500), // 更舊的日誌
        ]);

        // 清理超過 365 天的日誌
        $deletedCount = $this->auditService->cleanupOldAuditLogs(365);

        $this->assertEquals(2, $deletedCount);
        $this->assertEquals(1, PermissionAuditLog::count());
    }

    /**
     * 測試匯出審計日誌
     */
    public function test_export_audit_logs(): void
    {
        // 建立測試日誌
        PermissionAuditLog::factory()->count(3)->create([
            'permission_id' => $this->testPermission->id,
            'user_id' => $this->testUser->id,
        ]);

        $exportData = $this->auditService->exportAuditLogs([
            'permission_id' => $this->testPermission->id,
        ]);

        $this->assertArrayHasKey('export_date', $exportData);
        $this->assertArrayHasKey('total_records', $exportData);
        $this->assertArrayHasKey('logs', $exportData);
        $this->assertEquals(3, $exportData['total_records']);
        $this->assertCount(3, $exportData['logs']);
    }

    /**
     * 測試取得使用者權限操作歷史
     */
    public function test_get_user_permission_history(): void
    {
        $otherUser = User::factory()->create();

        // 建立不同使用者的日誌
        PermissionAuditLog::factory()->count(3)->create([
            'user_id' => $this->testUser->id,
        ]);

        PermissionAuditLog::factory()->count(2)->create([
            'user_id' => $otherUser->id,
        ]);

        $history = $this->auditService->getUserPermissionHistory($this->testUser->id);

        $this->assertCount(3, $history);
        $history->each(function ($log) {
            $this->assertEquals($this->testUser->id, $log->user_id);
        });
    }

    /**
     * 測試取得權限變更歷史
     */
    public function test_get_permission_change_history(): void
    {
        $otherPermission = Permission::factory()->create();

        // 建立不同權限的變更日誌
        PermissionAuditLog::factory()->create([
            'action' => 'created',
            'permission_id' => $this->testPermission->id,
        ]);

        PermissionAuditLog::factory()->create([
            'action' => 'updated',
            'permission_id' => $this->testPermission->id,
        ]);

        PermissionAuditLog::factory()->create([
            'action' => 'dependency_added', // 這個不應該被包含
            'permission_id' => $this->testPermission->id,
        ]);

        PermissionAuditLog::factory()->create([
            'action' => 'updated',
            'permission_id' => $otherPermission->id,
        ]);

        $history = $this->auditService->getPermissionChangeHistory($this->testPermission->id);

        $this->assertCount(2, $history);
        $history->each(function ($log) {
            $this->assertEquals($this->testPermission->id, $log->permission_id);
            $this->assertContains($log->action, ['created', 'updated', 'deleted']);
        });
    }
}
