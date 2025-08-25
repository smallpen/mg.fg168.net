<?php

namespace Tests\Unit\Services;

use App\Models\Activity;
use App\Models\User;
use App\Services\ActivityBackupService;
use App\Services\ActivityIntegrityService;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Carbon\Carbon;

/**
 * 活動記錄備份服務測試
 */
class ActivityBackupServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ActivityBackupService $backupService;
    protected ActivityIntegrityService $integrityService;
    protected ActivityLogger $activityLogger;
    protected User $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->integrityService = app(ActivityIntegrityService::class);
        $this->activityLogger = app(ActivityLogger::class);
        $this->backupService = new ActivityBackupService(
            $this->integrityService,
            $this->activityLogger
        );
        
        $this->testUser = User::factory()->create();
        
        // 建立測試活動記錄
        $this->createTestActivities();
    }

    protected function tearDown(): void
    {
        // 清理測試備份檔案
        $backupDir = storage_path('backups/activity_logs');
        if (File::exists($backupDir)) {
            File::deleteDirectory($backupDir);
        }
        
        parent::tearDown();
    }

    /**
     * 建立測試活動記錄
     */
    protected function createTestActivities(): void
    {
        // 建立不同時間的活動記錄
        Activity::factory()->count(5)->create([
            'user_id' => $this->testUser->id,
            'created_at' => Carbon::now()->subDays(1),
        ]);
        
        Activity::factory()->count(3)->create([
            'user_id' => $this->testUser->id,
            'created_at' => Carbon::now()->subDays(7),
        ]);
        
        Activity::factory()->count(2)->create([
            'user_id' => $this->testUser->id,
            'created_at' => Carbon::now()->subDays(30),
        ]);
    }

    /**
     * 測試活動記錄備份功能
     */
    public function test_backup_activity_logs_success(): void
    {
        $options = [
            'date_from' => Carbon::now()->subDays(10)->format('Y-m-d'),
            'date_to' => Carbon::now()->format('Y-m-d'),
        ];

        $result = $this->backupService->backupActivityLogs($options);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('backup_name', $result);
        $this->assertArrayHasKey('data_export', $result);
        $this->assertArrayHasKey('compression', $result);
        $this->assertArrayHasKey('encryption', $result);
        $this->assertArrayHasKey('integrity_check', $result);
        
        // 驗證資料匯出結果
        $this->assertTrue($result['data_export']['success']);
        $this->assertEquals(8, $result['data_export']['record_count']); // 5 + 3 筆記錄
        
        // 驗證壓縮結果
        $this->assertTrue($result['compression']['success']);
        $this->assertGreaterThan(0, $result['compression']['compression_ratio']);
        
        // 驗證加密結果
        $this->assertTrue($result['encryption']['success']);
        $this->assertNotEmpty($result['encryption']['checksum']);
        
        // 驗證完整性檢查
        $this->assertTrue($result['integrity_check']['success']);
    }

    /**
     * 測試備份檔案完整性驗證
     */
    public function test_verify_backup_integrity(): void
    {
        // 先建立備份
        $backupResult = $this->backupService->backupActivityLogs();
        $this->assertTrue($backupResult['success']);
        
        $backupPath = $backupResult['encryption']['encrypted_path'];
        
        // 驗證完整性
        $verifyResult = $this->backupService->verifyBackupIntegrity($backupPath);
        
        $this->assertTrue($verifyResult['success']);
        $this->assertArrayHasKey('checksum', $verifyResult);
        $this->assertArrayHasKey('backup_info', $verifyResult);
    }

    /**
     * 測試還原活動記錄備份
     */
    public function test_restore_activity_logs_success(): void
    {
        // 先建立備份
        $backupResult = $this->backupService->backupActivityLogs();
        $this->assertTrue($backupResult['success']);
        
        $backupPath = $backupResult['encryption']['encrypted_path'];
        
        // 清空活動記錄表
        Activity::truncate();
        $this->assertEquals(0, Activity::count());
        
        // 還原備份
        $restoreResult = $this->backupService->restoreActivityLogs($backupPath);
        
        $this->assertTrue($restoreResult['success']);
        $this->assertArrayHasKey('data_import', $restoreResult);
        
        // 驗證資料已還原
        $importResult = $restoreResult['data_import'];
        $this->assertTrue($importResult['success']);
        $this->assertEquals(10, $importResult['total_records']); // 所有測試記錄
        $this->assertEquals(10, $importResult['imported_count']);
        $this->assertEquals(0, $importResult['error_count']);
        
        // 驗證資料庫中的記錄數量
        $this->assertEquals(10, Activity::count());
    }

    /**
     * 測試列出可用備份
     */
    public function test_list_activity_backups(): void
    {
        // 建立多個備份
        $this->backupService->backupActivityLogs();
        $this->backupService->backupActivityLogs();
        
        $backups = $this->backupService->listActivityBackups();
        
        $this->assertCount(2, $backups);
        
        foreach ($backups as $backup) {
            $this->assertArrayHasKey('filename', $backup);
            $this->assertArrayHasKey('path', $backup);
            $this->assertArrayHasKey('size', $backup);
            $this->assertArrayHasKey('size_mb', $backup);
            $this->assertArrayHasKey('created_at', $backup);
            $this->assertArrayHasKey('checksum', $backup);
            
            $this->assertStringEndsWith('.encrypted', $backup['filename']);
            $this->assertTrue(File::exists($backup['path']));
        }
    }

    /**
     * 測試清理舊備份
     */
    public function test_cleanup_old_activity_backups(): void
    {
        // 建立備份
        $this->backupService->backupActivityLogs();
        
        // 模擬舊備份（修改檔案時間）
        $backups = $this->backupService->listActivityBackups();
        $this->assertCount(1, $backups);
        
        $oldBackupPath = $backups[0]['path'];
        $oldTime = Carbon::now()->subDays(100)->timestamp;
        touch($oldBackupPath, $oldTime);
        
        // 清理 30 天前的備份
        $cleanupResult = $this->backupService->cleanupOldActivityBackups(30);
        
        $this->assertTrue($cleanupResult['success']);
        $this->assertEquals(1, $cleanupResult['deleted_count']);
        $this->assertGreaterThan(0, $cleanupResult['deleted_size_mb']);
        
        // 驗證檔案已被刪除
        $this->assertFalse(File::exists($oldBackupPath));
        
        // 驗證備份列表為空
        $remainingBackups = $this->backupService->listActivityBackups();
        $this->assertCount(0, $remainingBackups);
    }

    /**
     * 測試備份日期範圍篩選
     */
    public function test_backup_with_date_range_filter(): void
    {
        $options = [
            'date_from' => Carbon::now()->subDays(5)->format('Y-m-d'),
            'date_to' => Carbon::now()->format('Y-m-d'),
        ];

        $result = $this->backupService->backupActivityLogs($options);

        $this->assertTrue($result['success']);
        
        // 應該只包含最近 5 天的記錄（5 筆）
        $this->assertEquals(5, $result['data_export']['record_count']);
    }

    /**
     * 測試包含已刪除記錄的備份
     */
    public function test_backup_with_deleted_records(): void
    {
        // 軟刪除一些記錄
        Activity::take(2)->delete();
        
        $options = [
            'include_deleted' => true,
        ];

        $result = $this->backupService->backupActivityLogs($options);

        $this->assertTrue($result['success']);
        
        // 應該包含所有記錄（包括已刪除的）
        $this->assertEquals(10, $result['data_export']['record_count']);
    }

    /**
     * 測試還原時替換現有記錄
     */
    public function test_restore_with_replace_existing(): void
    {
        // 建立備份
        $backupResult = $this->backupService->backupActivityLogs();
        $backupPath = $backupResult['encryption']['encrypted_path'];
        
        // 修改一些記錄
        Activity::first()->update(['description' => '修改後的描述']);
        
        // 還原並替換現有記錄
        $restoreResult = $this->backupService->restoreActivityLogs($backupPath, [
            'replace_existing' => true,
        ]);
        
        $this->assertTrue($restoreResult['success']);
        
        // 驗證記錄已被還原到原始狀態
        $restoredActivity = Activity::first();
        $this->assertNotEquals('修改後的描述', $restoredActivity->description);
    }

    /**
     * 測試備份檔案不存在的情況
     */
    public function test_verify_nonexistent_backup_file(): void
    {
        $result = $this->backupService->verifyBackupIntegrity('/nonexistent/path');
        
        $this->assertFalse($result['success']);
        $this->assertStringContains('備份檔案不存在', $result['error']);
    }

    /**
     * 測試還原不存在的備份檔案
     */
    public function test_restore_nonexistent_backup_file(): void
    {
        $result = $this->backupService->restoreActivityLogs('/nonexistent/path');
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /**
     * 測試空資料庫的備份
     */
    public function test_backup_empty_database(): void
    {
        // 清空活動記錄
        Activity::truncate();
        
        $result = $this->backupService->backupActivityLogs();
        
        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['data_export']['record_count']);
    }
}