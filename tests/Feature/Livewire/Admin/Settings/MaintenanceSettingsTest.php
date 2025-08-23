<?php

namespace Tests\Feature\Livewire\Admin\Settings;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Services\ConfigurationService;
use App\Services\BackupService;
use App\Livewire\Admin\Settings\MaintenanceSettings;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class MaintenanceSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立管理員角色和使用者
        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '系統管理員',
            'description' => '系統管理員角色',
        ]);

        $this->adminUser = User::factory()->create([
            'username' => 'admin',
            'name' => '系統管理員',
            'email' => 'admin@example.com',
            'is_active' => true,
        ]);

        $this->adminUser->roles()->attach($this->adminRole);
    }

    /** @test */
    public function it_can_render_maintenance_settings_component()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(MaintenanceSettings::class)
            ->assertStatus(200)
            ->assertSee('備份設定')
            ->assertSee('日誌設定')
            ->assertSee('快取設定')
            ->assertSee('維護模式')
            ->assertSee('系統監控');
    }

    /** @test */
    public function it_loads_maintenance_settings_on_mount()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(MaintenanceSettings::class);

        $this->assertArrayHasKey('maintenance.auto_backup_enabled', $component->get('settings'));
        $this->assertArrayHasKey('maintenance.log_level', $component->get('settings'));
        $this->assertArrayHasKey('maintenance.cache_driver', $component->get('settings'));
        $this->assertArrayHasKey('maintenance.maintenance_mode', $component->get('settings'));
        $this->assertArrayHasKey('maintenance.monitoring_enabled', $component->get('settings'));
    }

    /** @test */
    public function it_can_save_maintenance_settings()
    {
        $this->actingAs($this->adminUser);

        $newSettings = [
            'maintenance.auto_backup_enabled' => true,
            'maintenance.backup_frequency' => 'daily',
            'maintenance.backup_retention_days' => 30,
            'maintenance.backup_storage_path' => '/custom/backup/path',
            'maintenance.log_level' => 'warning',
            'maintenance.log_retention_days' => 7,
            'maintenance.cache_driver' => 'redis',
            'maintenance.cache_ttl' => 7200,
            'maintenance.maintenance_mode' => false,
            'maintenance.maintenance_message' => '系統維護中',
            'maintenance.monitoring_enabled' => true,
            'maintenance.monitoring_interval' => 300,
        ];

        Livewire::test(MaintenanceSettings::class)
            ->set('settings', $newSettings)
            ->call('save')
            ->assertDispatched('saved');
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(MaintenanceSettings::class)
            ->set('settings.maintenance.auto_backup_enabled', true)
            ->set('settings.maintenance.backup_frequency', '') // 必填但為空
            ->call('save')
            ->assertHasErrors(['settings.maintenance.backup_frequency']);
    }

    /** @test */
    public function it_validates_numeric_ranges()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(MaintenanceSettings::class)
            ->set('settings.maintenance.backup_retention_days', 500) // 超過最大值 365
            ->call('save')
            ->assertHasErrors(['settings.maintenance.backup_retention_days']);

        Livewire::test(MaintenanceSettings::class)
            ->set('settings.maintenance.cache_ttl', 30) // 小於最小值 60
            ->call('save')
            ->assertHasErrors(['settings.maintenance.cache_ttl']);
    }

    /** @test */
    public function it_shows_maintenance_mode_warning()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(MaintenanceSettings::class)
            ->set('settings.maintenance.maintenance_mode', true)
            ->set('settings.maintenance.maintenance_message', '系統維護中')
            ->call('save');

        $this->assertTrue($component->get('showMaintenanceWarning'));
        $component->assertDispatched('maintenance-mode-warning');
    }

    /** @test */
    public function it_can_confirm_maintenance_mode()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(MaintenanceSettings::class)
            ->set('settings.maintenance.maintenance_mode', true)
            ->set('settings.maintenance.maintenance_message', '系統維護中')
            ->set('showMaintenanceWarning', true)
            ->call('confirmMaintenanceMode')
            ->assertSet('showMaintenanceWarning', false)
            ->assertDispatched('saved');
    }

    /** @test */
    public function it_can_cancel_maintenance_mode()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(MaintenanceSettings::class)
            ->set('settings.maintenance.maintenance_mode', true)
            ->set('showMaintenanceWarning', true)
            ->call('cancelMaintenanceMode')
            ->assertSet('settings.maintenance.maintenance_mode', false)
            ->assertSet('showMaintenanceWarning', false);
    }

    /** @test */
    public function it_can_test_backup_functionality()
    {
        $this->actingAs($this->adminUser);

        // 模擬 BackupService
        $this->mock(BackupService::class, function ($mock) {
            $mock->shouldReceive('performTestBackup')
                ->once()
                ->andReturn([
                    'started_at' => now()->toISOString(),
                    'test_database' => ['status' => 'success', 'message' => '資料庫備份測試成功'],
                    'test_files' => ['status' => 'success', 'message' => '檔案備份測試成功'],
                    'storage_check' => ['status' => 'success', 'message' => '儲存空間檢查正常'],
                    'completed_at' => now()->toISOString(),
                    'success' => true,
                ]);
        });

        Livewire::test(MaintenanceSettings::class)
            ->call('testBackup')
            ->assertSet('showStorageTest', true)
            ->assertDispatched('test-completed');
    }

    /** @test */
    public function it_can_test_monitoring_functionality()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(MaintenanceSettings::class)
            ->call('testMonitoring')
            ->assertDispatched('test-completed');

        // 檢查測試結果是否包含監控資料
        $component = Livewire::test(MaintenanceSettings::class);
        $component->call('testMonitoring');
        
        $testResults = $component->get('testResults');
        $this->assertArrayHasKey('monitoring', $testResults);
        $this->assertEquals('success', $testResults['monitoring']['status']);
    }

    /** @test */
    public function it_can_clear_cache()
    {
        $this->actingAs($this->adminUser);

        // 設定一些快取資料
        Cache::put('test_key', 'test_value', 3600);
        $this->assertEquals('test_value', Cache::get('test_key'));

        Livewire::test(MaintenanceSettings::class)
            ->call('clearCache')
            ->assertDispatched('cache-cleared');

        // 驗證快取已被清除
        $this->assertNull(Cache::get('test_key'));
    }

    /** @test */
    public function it_validates_backup_storage_path()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(MaintenanceSettings::class);
        
        // 測試有效的儲存路徑
        $component->set('settings.maintenance.backup_storage_path', storage_path('test_backups'))
                  ->call('validateBackupStorage');

        $storageValidation = $component->get('storageValidation');
        $this->assertArrayHasKey('backup_path', $storageValidation);
    }

    /** @test */
    public function it_validates_cache_connection()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(MaintenanceSettings::class);
        
        // 測試檔案快取連線
        $component->set('settings.maintenance.cache_driver', 'file')
                  ->call('validateCacheConnection');

        $storageValidation = $component->get('storageValidation');
        $this->assertArrayHasKey('cache_connection', $storageValidation);
        $this->assertEquals('success', $storageValidation['cache_connection']['status']);
    }

    /** @test */
    public function it_handles_backup_storage_validation_errors()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(MaintenanceSettings::class);
        
        // 測試無效的儲存路徑（不存在且無法建立）
        $invalidPath = '/root/invalid_backup_path';
        
        $component->set('settings.maintenance.backup_storage_path', $invalidPath);
        
        try {
            $component->call('validateBackupStorage');
        } catch (\Exception $e) {
            $storageValidation = $component->get('storageValidation');
            $this->assertArrayHasKey('backup_path', $storageValidation);
            $this->assertEquals('error', $storageValidation['backup_path']['status']);
        }
    }

    /** @test */
    public function it_requires_maintenance_message_when_maintenance_mode_enabled()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(MaintenanceSettings::class)
            ->set('settings.maintenance.maintenance_mode', true)
            ->set('settings.maintenance.maintenance_message', '') // 必填但為空
            ->call('save')
            ->assertHasErrors(['settings.maintenance.maintenance_message']);
    }

    /** @test */
    public function it_requires_monitoring_interval_when_monitoring_enabled()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(MaintenanceSettings::class)
            ->set('settings.maintenance.monitoring_enabled', true)
            ->set('settings.maintenance.monitoring_interval', '') // 必填但為空
            ->call('save')
            ->assertHasErrors(['settings.maintenance.monitoring_interval']);
    }

    /** @test */
    public function unauthorized_users_cannot_access_maintenance_settings()
    {
        $regularUser = User::factory()->create();
        $this->actingAs($regularUser);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        Livewire::test(MaintenanceSettings::class);
    }
}