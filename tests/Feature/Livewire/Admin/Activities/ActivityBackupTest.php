<?php

namespace Tests\Feature\Livewire\Admin\Activities;

use App\Livewire\Admin\Activities\ActivityBackup;
use App\Models\Activity;
use App\Models\User;
use App\Models\Role;
use App\Services\ActivityBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;
use Carbon\Carbon;

/**
 * 活動記錄備份元件測試
 */
class ActivityBackupTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試使用者和角色
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $userRole = Role::factory()->create(['name' => 'user']);
        
        $this->adminUser = User::factory()->create();
        $this->adminUser->roles()->attach($adminRole);
        
        $this->regularUser = User::factory()->create();
        $this->regularUser->roles()->attach($userRole);
        
        // 建立測試活動記錄
        Activity::factory()->count(10)->create([
            'user_id' => $this->adminUser->id,
        ]);
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
     * 測試管理員可以存取備份頁面
     */
    public function test_admin_can_access_backup_page(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(ActivityBackup::class)
            ->assertSuccessful()
            ->assertSee('活動記錄備份管理')
            ->assertSee('建立備份')
            ->assertSee('還原備份')
            ->assertSee('管理備份');
    }

    /**
     * 測試一般使用者無法存取備份頁面
     */
    public function test_regular_user_cannot_access_backup_page(): void
    {
        $this->actingAs($this->regularUser);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        Livewire::test(ActivityBackup::class);
    }

    /**
     * 測試建立備份功能
     */
    public function test_create_backup_functionality(): void
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(ActivityBackup::class)
            ->set('backupDateFrom', Carbon::now()->subDays(7)->format('Y-m-d'))
            ->set('backupDateTo', Carbon::now()->format('Y-m-d'))
            ->set('includeDeleted', false);

        // 模擬備份服務
        $mockService = $this->mock(ActivityBackupService::class);
        $mockService->shouldReceive('backupActivityLogs')
            ->once()
            ->andReturn([
                'success' => true,
                'backup_name' => 'test_backup_' . now()->format('Y-m-d_H-i-s'),
                'data_export' => [
                    'success' => true,
                    'record_count' => 10,
                    'file_size_mb' => 2.5,
                ],
                'compression' => [
                    'success' => true,
                    'compression_ratio' => 75.5,
                ],
                'encryption' => [
                    'success' => true,
                    'checksum' => 'abc123def456',
                ],
                'integrity_check' => [
                    'success' => true,
                ],
            ]);

        $component->call('createBackup')
            ->assertSet('isBackingUp', false)
            ->assertDispatched('backup-completed');
    }

    /**
     * 測試備份表單驗證
     */
    public function test_backup_form_validation(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(ActivityBackup::class)
            ->set('backupDateFrom', '')
            ->set('backupDateTo', '')
            ->call('createBackup')
            ->assertHasErrors(['backupDateFrom', 'backupDateTo']);

        Livewire::test(ActivityBackup::class)
            ->set('backupDateFrom', Carbon::now()->format('Y-m-d'))
            ->set('backupDateTo', Carbon::now()->subDays(1)->format('Y-m-d'))
            ->call('createBackup')
            ->assertHasErrors(['backupDateFrom']);
    }

    /**
     * 測試還原備份功能
     */
    public function test_restore_backup_functionality(): void
    {
        $this->actingAs($this->adminUser);

        $mockService = $this->mock(ActivityBackupService::class);
        $mockService->shouldReceive('restoreActivityLogs')
            ->once()
            ->with('/test/backup/path', [
                'replace_existing' => false,
                'validate_integrity' => true,
            ])
            ->andReturn([
                'success' => true,
                'data_import' => [
                    'success' => true,
                    'total_records' => 10,
                    'imported_count' => 10,
                    'skipped_count' => 0,
                    'error_count' => 0,
                ],
            ]);

        Livewire::test(ActivityBackup::class)
            ->set('replaceExisting', false)
            ->set('validateIntegrity', true)
            ->call('restoreBackup', '/test/backup/path')
            ->assertSet('isRestoring', false)
            ->assertDispatched('restore-completed');
    }

    /**
     * 測試清理舊備份功能
     */
    public function test_cleanup_old_backups_functionality(): void
    {
        $this->actingAs($this->adminUser);

        $mockService = $this->mock(ActivityBackupService::class);
        $mockService->shouldReceive('cleanupOldActivityBackups')
            ->once()
            ->with(90)
            ->andReturn([
                'success' => true,
                'deleted_count' => 5,
                'deleted_size_mb' => 25.5,
                'errors' => [],
            ]);

        Livewire::test(ActivityBackup::class)
            ->set('cleanupDays', 90)
            ->call('cleanupOldBackups')
            ->assertSet('isCleaning', false)
            ->assertDispatched('cleanup-completed');
    }

    /**
     * 測試清理表單驗證
     */
    public function test_cleanup_form_validation(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(ActivityBackup::class)
            ->set('cleanupDays', 0)
            ->call('cleanupOldBackups')
            ->assertHasErrors(['cleanupDays']);

        Livewire::test(ActivityBackup::class)
            ->set('cleanupDays', 5000)
            ->call('cleanupOldBackups')
            ->assertHasErrors(['cleanupDays']);
    }

    /**
     * 測試驗證備份功能
     */
    public function test_verify_backup_functionality(): void
    {
        $this->actingAs($this->adminUser);

        $mockService = $this->mock(ActivityBackupService::class);
        $mockService->shouldReceive('verifyBackupIntegrity')
            ->once()
            ->andReturn([
                'success' => true,
                'checksum' => 'abc123def456789',
            ]);

        Livewire::test(ActivityBackup::class)
            ->call('verifyBackup', 'test_backup.encrypted')
            ->assertDispatched('verify-completed');
    }

    /**
     * 測試刪除備份功能
     */
    public function test_delete_backup_functionality(): void
    {
        $this->actingAs($this->adminUser);

        // 建立測試備份檔案
        $backupDir = storage_path('backups/activity_logs');
        File::makeDirectory($backupDir, 0755, true);
        $testFile = $backupDir . '/test_backup.encrypted';
        File::put($testFile, 'test content');

        Livewire::test(ActivityBackup::class)
            ->call('deleteBackup', 'test_backup.encrypted')
            ->assertDispatched('backup-deleted');

        $this->assertFalse(File::exists($testFile));
    }

    /**
     * 測試分頁切換功能
     */
    public function test_tab_switching(): void
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(ActivityBackup::class)
            ->assertSet('activeTab', 'backup');

        $component->call('setActiveTab', 'restore')
            ->assertSet('activeTab', 'restore');

        $component->call('setActiveTab', 'manage')
            ->assertSet('activeTab', 'manage');
    }

    /**
     * 測試備份列表顯示
     */
    public function test_backup_list_display(): void
    {
        $this->actingAs($this->adminUser);

        $mockService = $this->mock(ActivityBackupService::class);
        $mockService->shouldReceive('listActivityBackups')
            ->andReturn([
                [
                    'filename' => 'backup_1.encrypted',
                    'path' => '/path/to/backup_1.encrypted',
                    'size' => 1024000,
                    'size_mb' => 1.0,
                    'created_at' => Carbon::now()->toISOString(),
                    'checksum' => 'abc123',
                ],
                [
                    'filename' => 'backup_2.encrypted',
                    'path' => '/path/to/backup_2.encrypted',
                    'size' => 2048000,
                    'size_mb' => 2.0,
                    'created_at' => Carbon::now()->subHour()->toISOString(),
                    'checksum' => 'def456',
                ],
            ]);

        Livewire::test(ActivityBackup::class)
            ->call('setActiveTab', 'manage')
            ->assertSee('backup_1.encrypted')
            ->assertSee('backup_2.encrypted')
            ->assertSee('1.0')
            ->assertSee('2.0');
    }

    /**
     * 測試備份失敗處理
     */
    public function test_backup_failure_handling(): void
    {
        $this->actingAs($this->adminUser);

        $mockService = $this->mock(ActivityBackupService::class);
        $mockService->shouldReceive('backupActivityLogs')
            ->once()
            ->andReturn([
                'success' => false,
                'error' => '磁碟空間不足',
            ]);

        Livewire::test(ActivityBackup::class)
            ->set('backupDateFrom', Carbon::now()->subDays(7)->format('Y-m-d'))
            ->set('backupDateTo', Carbon::now()->format('Y-m-d'))
            ->call('createBackup')
            ->assertSet('isBackingUp', false)
            ->assertDispatched('backup-failed');
    }

    /**
     * 測試還原失敗處理
     */
    public function test_restore_failure_handling(): void
    {
        $this->actingAs($this->adminUser);

        $mockService = $this->mock(ActivityBackupService::class);
        $mockService->shouldReceive('restoreActivityLogs')
            ->once()
            ->andReturn([
                'success' => false,
                'error' => '備份檔案損壞',
            ]);

        Livewire::test(ActivityBackup::class)
            ->call('restoreBackup', '/test/backup/path')
            ->assertSet('isRestoring', false)
            ->assertDispatched('restore-failed');
    }

    /**
     * 測試權限檢查
     */
    public function test_permission_checks(): void
    {
        // 建立沒有適當權限的使用者
        $limitedUser = User::factory()->create();
        $limitedRole = Role::factory()->create(['name' => 'limited']);
        $limitedUser->roles()->attach($limitedRole);

        $this->actingAs($limitedUser);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        Livewire::test(ActivityBackup::class);
    }

    /**
     * 測試格式化輔助方法
     */
    public function test_formatting_helper_methods(): void
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(ActivityBackup::class);

        // 測試檔案大小格式化
        $this->assertEquals('1.00 KB', $component->instance()->formatFileSize(1024));
        $this->assertEquals('1.00 MB', $component->instance()->formatFileSize(1024 * 1024));

        // 測試日期時間格式化
        $dateTime = '2024-01-15T10:30:25.000000Z';
        $formatted = $component->instance()->formatDateTime($dateTime);
        $this->assertStringContains('2024-01-15', $formatted);
    }
}