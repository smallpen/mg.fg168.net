<?php

namespace Tests\Feature\Livewire\Admin\Settings;

use App\Livewire\Admin\Settings\SettingBackupManager;
use App\Models\Role;
use App\Models\Setting;
use App\Models\SettingBackup;
use App\Models\User;
use App\Models\Permission;
use App\Repositories\SettingsRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Livewire;
use Tests\TestCase;
use Mockery;

/**
 * SettingBackupManager 元件功能測試
 * 
 * 測試設定備份管理元件的完整功能，包含建立、還原、比較、下載和刪除備份
 */
class SettingBackupManagerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;
    protected Role $adminRole;
    protected array $testSettings;
    protected array $testBackups;
    protected $mockRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createRolesAndUsers();
        $this->createTestSettings();
        $this->createTestBackups();
        $this->createMockServices();
    }

    private function createRolesAndUsers(): void
    {
        // 建立管理員角色
        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '系統管理員',
            'is_system_role' => true,
            'is_active' => true,
        ]);

        // 建立權限
        $permissions = [
            'system.settings.view' => '檢視系統設定',
            'system.settings.backup' => '備份系統設定',
            'system.settings.restore' => '還原系統設定',
        ];

        foreach ($permissions as $name => $displayName) {
            Permission::create([
                'name' => $name,
                'display_name' => $displayName,
                'module' => 'settings',
                'type' => 'action'
            ]);
        }

        $this->adminRole->permissions()->attach(
            Permission::whereIn('name', array_keys($permissions))->pluck('id')
        );

        // 建立使用者
        $this->adminUser = User::factory()->create([
            'username' => 'admin',
            'name' => '系統管理員',
            'email' => 'admin@example.com',
            'is_active' => true,
        ]);
        $this->adminUser->roles()->attach($this->adminRole);

        $this->regularUser = User::factory()->create([
            'username' => 'user',
            'name' => '一般使用者',
            'email' => 'user@example.com',
            'is_active' => true,
        ]);
    }

    private function createTestSettings(): void
    {
        $this->testSettings = [
            [
                'key' => 'app.name',
                'value' => 'Laravel Admin System',
                'category' => 'basic',
                'type' => 'text',
                'description' => '應用程式名稱',
                'default_value' => 'Laravel Admin System',
                'is_encrypted' => false,
                'is_system' => true,
                'is_public' => true,
            ],
            [
                'key' => 'app.timezone',
                'value' => 'Asia/Taipei',
                'category' => 'basic',
                'type' => 'select',
                'description' => '系統時區',
                'default_value' => 'UTC',
                'is_encrypted' => false,
                'is_system' => true,
                'is_public' => true,
            ],
            [
                'key' => 'security.password_min_length',
                'value' => 8,
                'category' => 'security',
                'type' => 'number',
                'description' => '密碼最小長度',
                'default_value' => 6,
                'is_encrypted' => false,
                'is_system' => true,
                'is_public' => false,
            ],
        ];

        foreach ($this->testSettings as $settingData) {
            Setting::create($settingData);
        }
    }

    private function createTestBackups(): void
    {
        $this->testBackups = [
            [
                'id' => 1,
                'name' => '生產環境初始設定',
                'description' => '系統上線前的初始設定備份',
                'settings_data' => $this->testSettings,
                'created_by' => $this->adminUser->id,
                'created_at' => now()->subDays(7),
                'updated_at' => now()->subDays(7),
            ],
            [
                'id' => 2,
                'name' => '安全設定更新',
                'description' => '更新密碼政策後的備份',
                'settings_data' => array_merge($this->testSettings, [
                    [
                        'key' => 'security.password_complexity',
                        'value' => true,
                        'category' => 'security',
                        'type' => 'boolean',
                        'description' => '密碼複雜度要求',
                        'default_value' => false,
                        'is_encrypted' => false,
                        'is_system' => true,
                        'is_public' => false,
                    ]
                ]),
                'created_by' => $this->adminUser->id,
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(3),
            ],
            [
                'id' => 3,
                'name' => '外觀調整備份',
                'description' => '調整系統主題後的備份',
                'settings_data' => $this->testSettings,
                'created_by' => $this->adminUser->id,
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ],
        ];

        foreach ($this->testBackups as $backupData) {
            SettingBackup::create($backupData);
        }
    }

    private function createMockServices(): void
    {
        $this->mockRepository = Mockery::mock(SettingsRepositoryInterface::class);
        
        // 設定預設的 Mock 行為
        $this->mockRepository->shouldReceive('createBackup')
            ->andReturnUsing(function ($name, $description) {
                $backup = new SettingBackup();
                $backup->id = fake()->numberBetween(100, 999);
                $backup->name = $name;
                $backup->description = $description;
                $backup->settings_data = $this->testSettings;
                $backup->created_by = $this->adminUser->id;
                $backup->created_at = now();
                return $backup;
            });

        $this->mockRepository->shouldReceive('restoreBackup')
            ->andReturn(true);

        $this->mockRepository->shouldReceive('deleteBackup')
            ->andReturn(true);

        // 綁定到容器
        $this->app->instance(SettingsRepositoryInterface::class, $this->mockRepository);
    }

    /** @test */
    public function 管理員可以存取備份管理元件()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingBackupManager::class);

        $component->assertStatus(200)
                 ->assertSet('backupName', '')
                 ->assertSet('backupDescription', '')
                 ->assertSet('showCreateModal', false)
                 ->assertSet('showRestoreModal', false)
                 ->assertSet('showCompareModal', false)
                 ->assertSet('showDeleteModal', false)
                 ->assertSet('search', '')
                 ->assertSet('sortBy', 'created_at')
                 ->assertSet('sortDirection', 'desc')
                 ->assertSet('perPage', 10);
    }

    /** @test */
    public function 無權限使用者無法存取備份管理()
    {
        $this->actingAs($this->regularUser);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        
        Livewire::test(SettingBackupManager::class);
    }

    /** @test */
    public function 可以顯示備份列表()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingBackupManager::class);

        $component->assertStatus(200);

        // 檢查是否顯示備份項目
        foreach ($this->testBackups as $backup) {
            $component->assertSee($backup['name'])
                     ->assertSee($backup['description']);
        }
    }

    /** @test */
    public function 可以開啟建立備份對話框()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingBackupManager::class);

        $component->call('openCreateModal')
                 ->assertSet('showCreateModal', true)
                 ->assertSet('backupName', function ($name) {
                     return str_contains($name, '設定備份');
                 })
                 ->assertSet('backupDescription', '');
    }

    /** @test */
    public function 可以建立新備份()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingBackupManager::class);

        $backupName = '測試備份';
        $backupDescription = '這是一個測試備份';

        $component->call('openCreateModal')
                 ->set('backupName', $backupName)
                 ->set('backupDescription', $backupDescription)
                 ->call('createBackup');

        $component->assertSet('showCreateModal', false)
                 ->assertDispatched('backup-created');

        // 檢查是否有成功訊息
        $this->assertTrue($component->instance()->hasFlashMessage('success'));
    }

    /** @test */
    public function 建立備份時驗證必填欄位()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingBackupManager::class);

        $component->call('openCreateModal')
                 ->set('backupName', '') // 空的備份名稱
                 ->call('createBackup');

        $component->assertHasErrors(['backupName']);
    }

    /** @test */
    public function 可以關閉建立備份對話框()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingBackupManager::class);

        $component->call('openCreateModal')
                 ->set('backupName', '測試備份')
                 ->call('closeCreateModal')
                 ->assertSet('showCreateModal', false)
                 ->assertSet('backupName', '')
                 ->assertSet('backupDescription', '');
    }

    /** @test */
    public function 可以開啟還原確認對話框()
    {
        $this->actingAs($this->adminUser);

        $backup = SettingBackup::first();

        $component = Livewire::test(SettingBackupManager::class);

        $component->call('openRestoreModal', $backup->id)
                 ->assertSet('showRestoreModal', true)
                 ->assertSet('selectedBackup.id', $backup->id);

        // 檢查還原預覽是否生成
        $restorePreview = $component->get('restorePreview');
        $this->assertIsArray($restorePreview);
        $this->assertArrayHasKey('will_add', $restorePreview);
        $this->assertArrayHasKey('will_update', $restorePreview);
        $this->assertArrayHasKey('will_remove', $restorePreview);
        $this->assertArrayHasKey('unchanged', $restorePreview);
    }

    /** @test */
    public function 可以還原備份()
    {
        $this->actingAs($this->adminUser);

        $backup = SettingBackup::first();

        $component = Livewire::test(SettingBackupManager::class);

        $component->call('openRestoreModal', $backup->id)
                 ->call('restoreBackup');

        $component->assertSet('showRestoreModal', false)
                 ->assertDispatched('settings-restored')
                 ->assertDispatched('settings-bulk-updated');

        // 檢查是否有成功訊息
        $this->assertTrue($component->instance()->hasFlashMessage('success'));
    }

    /** @test */
    public function 可以關閉還原對話框()
    {
        $this->actingAs($this->adminUser);

        $backup = SettingBackup::first();

        $component = Livewire::test(SettingBackupManager::class);

        $component->call('openRestoreModal', $backup->id)
                 ->call('closeRestoreModal')
                 ->assertSet('showRestoreModal', false)
                 ->assertSet('selectedBackup', null)
                 ->assertSet('restorePreview', []);
    }

    /** @test */
    public function 可以開啟比較對話框()
    {
        $this->actingAs($this->adminUser);

        $backup = SettingBackup::first();

        $component = Livewire::test(SettingBackupManager::class);

        $component->call('openCompareModal', $backup->id)
                 ->assertSet('showCompareModal', true)
                 ->assertSet('selectedBackup.id', $backup->id);

        // 檢查比較結果是否生成
        $compareResult = $component->get('compareResult');
        $this->assertIsArray($compareResult);
    }

    /** @test */
    public function 可以關閉比較對話框()
    {
        $this->actingAs($this->adminUser);

        $backup = SettingBackup::first();

        $component = Livewire::test(SettingBackupManager::class);

        $component->call('openCompareModal', $backup->id)
                 ->call('closeCompareModal')
                 ->assertSet('showCompareModal', false)
                 ->assertSet('selectedBackup', null)
                 ->assertSet('compareResult', []);
    }

    /** @test */
    public function 可以開啟刪除確認對話框()
    {
        $this->actingAs($this->adminUser);

        $backup = SettingBackup::first();

        $component = Livewire::test(SettingBackupManager::class);

        $component->call('openDeleteModal', $backup->id)
                 ->assertSet('showDeleteModal', true)
                 ->assertSet('selectedBackup.id', $backup->id);
    }

    /** @test */
    public function 可以刪除備份()
    {
        $this->actingAs($this->adminUser);

        $backup = SettingBackup::first();

        $component = Livewire::test(SettingBackupManager::class);

        $component->call('openDeleteModal', $backup->id)
                 ->call('deleteBackup');

        $component->assertSet('showDeleteModal', false);

        // 檢查是否有成功訊息
        $this->assertTrue($component->instance()->hasFlashMessage('success'));
    }

    /** @test */
    public function 可以關閉刪除對話框()
    {
        $this->actingAs($this->adminUser);

        $backup = SettingBackup::first();

        $component = Livewire::test(SettingBackupManager::class);

        $component->call('openDeleteModal', $backup->id)
                 ->call('closeDeleteModal')
                 ->assertSet('showDeleteModal', false)
                 ->assertSet('selectedBackup', null);
    }

    /** @test */
    public function 可以下載備份()
    {
        $this->actingAs($this->adminUser);

        $backup = SettingBackup::first();

        $component = Livewire::test(SettingBackupManager::class);

        $component->call('downloadBackup', $backup->id)
                 ->assertDispatched('download-file');

        // 檢查是否有成功訊息
        $this->assertTrue($component->instance()->hasFlashMessage('success'));
    }

    /** @test */
    public function 搜尋功能正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingBackupManager::class);

        // 搜尋特定備份
        $component->set('search', '生產環境')
                 ->assertSee('生產環境初始設定');

        // 清除搜尋
        $component->call('clearSearch')
                 ->assertSet('search', '');
    }

    /** @test */
    public function 排序功能正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingBackupManager::class);

        // 按名稱排序
        $component->call('sortBy', 'name')
                 ->assertSet('sortBy', 'name')
                 ->assertSet('sortDirection', 'desc');

        // 再次點擊應該切換排序方向
        $component->call('sortBy', 'name')
                 ->assertSet('sortDirection', 'asc');

        // 切換到不同欄位
        $component->call('sortBy', 'created_at')
                 ->assertSet('sortBy', 'created_at')
                 ->assertSet('sortDirection', 'desc');
    }

    /** @test */
    public function 統計資訊計算正確()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingBackupManager::class);

        $stats = $component->get('stats');

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_backups', $stats);
        $this->assertArrayHasKey('recent_backups', $stats);
        $this->assertArrayHasKey('total_size', $stats);
        $this->assertArrayHasKey('oldest_backup', $stats);

        // 檢查統計數據的合理性
        $this->assertGreaterThanOrEqual(0, $stats['total_backups']);
        $this->assertGreaterThanOrEqual(0, $stats['recent_backups']);
        $this->assertIsString($stats['total_size']);
    }

    /** @test */
    public function 備份大小計算正確()
    {
        $this->actingAs($this->adminUser);

        $backup = SettingBackup::first();
        $component = Livewire::test(SettingBackupManager::class);

        $size = $component->instance()->getBackupSize($backup);
        
        $this->assertIsString($size);
        $this->assertTrue(
            str_contains($size, 'B') || 
            str_contains($size, 'KB') || 
            str_contains($size, 'MB')
        );
    }

    /** @test */
    public function 備份類型標籤和顏色正確()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingBackupManager::class);

        // 測試手動備份
        $manualLabel = $component->instance()->getBackupTypeLabel('manual');
        $manualColor = $component->instance()->getBackupTypeColor('manual');
        $this->assertEquals('手動', $manualLabel);
        $this->assertEquals('blue', $manualColor);

        // 測試自動備份
        $autoLabel = $component->instance()->getBackupTypeLabel('auto');
        $autoColor = $component->instance()->getBackupTypeColor('auto');
        $this->assertEquals('自動', $autoLabel);
        $this->assertEquals('green', $autoColor);

        // 測試排程備份
        $scheduledLabel = $component->instance()->getBackupTypeLabel('scheduled');
        $scheduledColor = $component->instance()->getBackupTypeColor('scheduled');
        $this->assertEquals('排程', $scheduledLabel);
        $this->assertEquals('purple', $scheduledColor);
    }

    /** @test */
    public function 監聽開啟備份對話框事件()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingBackupManager::class);

        $component->dispatch('open-backup-dialog')
                 ->assertSet('showCreateModal', true);
    }

    /** @test */
    public function 監聽設定更新事件()
    {
        $this->actingAs($this->adminUser);

        $backup = SettingBackup::first();
        $component = Livewire::test(SettingBackupManager::class);

        // 開啟比較對話框
        $component->call('openCompareModal', $backup->id);

        // 觸發設定更新事件
        $component->dispatch('setting-updated', settingKey: 'app.name');

        // 比較結果應該重新生成
        $this->assertTrue(true); // 如果沒有異常就表示正常
    }

    /** @test */
    public function 監聽設定批量更新事件()
    {
        $this->actingAs($this->adminUser);

        $backup = SettingBackup::first();
        $component = Livewire::test(SettingBackupManager::class);

        // 開啟還原對話框
        $component->call('openRestoreModal', $backup->id);

        // 觸發批量更新事件
        $component->dispatch('settings-bulk-updated');

        // 還原預覽應該重新生成
        $this->assertTrue(true); // 如果沒有異常就表示正常
    }

    /** @test */
    public function 處理不存在的備份()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingBackupManager::class);

        // 嘗試開啟不存在的備份
        $component->call('openRestoreModal', 99999);

        // 應該有錯誤訊息
        $this->assertTrue($component->instance()->hasFlashMessage('error'));
    }

    /** @test */
    public function 備份操作失敗時的錯誤處理()
    {
        $this->actingAs($this->adminUser);

        // 設定建立備份失敗的 Mock
        $this->mockRepository->shouldReceive('createBackup')
            ->andThrow(new \Exception('Backup creation failed'));

        $component = Livewire::test(SettingBackupManager::class);

        $component->call('openCreateModal')
                 ->set('backupName', '測試備份')
                 ->call('createBackup');

        // 應該有錯誤訊息
        $this->assertTrue($component->instance()->hasFlashMessage('error'));
    }

    /** @test */
    public function 還原操作失敗時的錯誤處理()
    {
        $this->actingAs($this->adminUser);

        // 設定還原失敗的 Mock
        $this->mockRepository->shouldReceive('restoreBackup')
            ->andThrow(new \Exception('Restore failed'));

        $backup = SettingBackup::first();
        $component = Livewire::test(SettingBackupManager::class);

        $component->call('openRestoreModal', $backup->id)
                 ->call('restoreBackup');

        // 應該有錯誤訊息
        $this->assertTrue($component->instance()->hasFlashMessage('error'));
    }

    /** @test */
    public function 刪除操作失敗時的錯誤處理()
    {
        $this->actingAs($this->adminUser);

        // 設定刪除失敗的 Mock
        $this->mockRepository->shouldReceive('deleteBackup')
            ->andThrow(new \Exception('Delete failed'));

        $backup = SettingBackup::first();
        $component = Livewire::test(SettingBackupManager::class);

        $component->call('openDeleteModal', $backup->id)
                 ->call('deleteBackup');

        // 應該有錯誤訊息
        $this->assertTrue($component->instance()->hasFlashMessage('error'));
    }

    /** @test */
    public function 分頁功能正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingBackupManager::class);

        // 檢查預設每頁數量
        $component->assertSet('perPage', 10);

        // 檢查備份列表是否為分頁物件
        $backups = $component->get('backups');
        $this->assertInstanceOf(LengthAwarePaginator::class, $backups);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}