<?php

namespace Tests\Feature\Admin\Settings;

use App\Livewire\Admin\Settings\SettingBackupManager;
use App\Models\Setting;
use App\Models\SettingBackup as SettingBackupModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 設定備份元件測試
 */
class SettingBackupTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試用管理員使用者
        $this->adminUser = User::factory()->create([
            'username' => 'admin',
            'name' => '系統管理員',
            'email' => 'admin@example.com',
        ]);
        
        // 建立測試設定
        Setting::factory()->create([
            'key' => 'app.name',
            'value' => 'Test App',
            'category' => 'basic',
            'type' => 'text',
            'default_value' => 'Laravel Admin',
        ]);
        
        Setting::factory()->create([
            'key' => 'app.timezone',
            'value' => 'Asia/Taipei',
            'category' => 'basic',
            'type' => 'select',
            'default_value' => 'UTC',
        ]);
    }

    /** @test */
    public function it_can_render_backup_management_page()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(SettingBackupManager::class)
            ->assertStatus(200)
            ->assertSee('設定備份管理')
            ->assertSee('建立備份');
    }

    /** @test */
    public function it_can_create_backup()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(SettingBackupManager::class)
            ->set('backupName', '測試備份')
            ->set('backupDescription', '這是一個測試備份')
            ->call('createBackup')
            ->assertSet('showCreateModal', false)
            ->assertDispatched('backup-created');

        // 驗證備份已建立
        $this->assertDatabaseHas('setting_backups', [
            'name' => '測試備份',
            'description' => '這是一個測試備份',
            'created_by' => $this->adminUser->id,
        ]);

        $backup = SettingBackupModel::where('name', '測試備份')->first();
        $this->assertNotNull($backup);
        $this->assertEquals(2, count($backup->settings_data)); // 應該有 2 個設定
    }

    /** @test */
    public function it_validates_backup_name_is_required()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(SettingBackupManager::class)
            ->set('backupName', '')
            ->set('backupDescription', '測試描述')
            ->call('createBackup')
            ->assertHasErrors(['backupName' => 'required']);
    }
}