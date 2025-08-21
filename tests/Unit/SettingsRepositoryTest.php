<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Models\SettingBackup;
use App\Models\SettingChange;
use App\Models\User;
use App\Repositories\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SettingsRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected SettingsRepository $repository;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = new SettingsRepository();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_get_all_settings(): void
    {
        // 建立測試設定
        Setting::factory()->create(['key' => 'test.setting1', 'category' => 'test', 'sort_order' => 1]);
        Setting::factory()->create(['key' => 'test.setting2', 'category' => 'test', 'sort_order' => 2]);

        $settings = $this->repository->getAllSettings();

        $this->assertCount(2, $settings);
        $this->assertTrue($settings->contains('key', 'test.setting1'));
        $this->assertTrue($settings->contains('key', 'test.setting2'));
    }

    public function test_can_get_settings_by_category(): void
    {
        // 建立不同分類的設定
        Setting::factory()->create(['key' => 'basic.setting1', 'category' => 'basic']);
        Setting::factory()->create(['key' => 'security.setting1', 'category' => 'security']);
        Setting::factory()->create(['key' => 'basic.setting2', 'category' => 'basic']);

        $basicSettings = $this->repository->getSettingsByCategory('basic');
        $allSettings = $this->repository->getSettingsByCategory();

        $this->assertCount(2, $basicSettings);
        $this->assertIsObject($allSettings); // 分組後的集合
        $this->assertTrue($allSettings->has('basic'));
        $this->assertTrue($allSettings->has('security'));
        $this->assertCount(2, $allSettings->get('basic'));
        $this->assertCount(1, $allSettings->get('security'));
    }

    public function test_can_get_single_setting(): void
    {
        $setting = Setting::factory()->create(['key' => 'test.setting']);

        $result = $this->repository->getSetting('test.setting');

        $this->assertNotNull($result);
        $this->assertEquals($setting->id, $result->id);
        $this->assertEquals('test.setting', $result->key);
    }

    public function test_can_get_multiple_settings(): void
    {
        Setting::factory()->create(['key' => 'test.setting1']);
        Setting::factory()->create(['key' => 'test.setting2']);
        Setting::factory()->create(['key' => 'test.setting3']);

        $settings = $this->repository->getSettings(['test.setting1', 'test.setting2']);

        $this->assertCount(2, $settings);
        $this->assertTrue($settings->has('test.setting1'));
        $this->assertTrue($settings->has('test.setting2'));
        $this->assertFalse($settings->has('test.setting3'));
    }

    public function test_can_update_setting(): void
    {
        $setting = Setting::factory()->create([
            'key' => 'test.setting',
            'value' => 'old_value',
            'type' => 'text'
        ]);

        $result = $this->repository->updateSetting('test.setting', 'new_value');

        $this->assertTrue($result);
        $this->assertEquals('new_value', $setting->fresh()->value);
    }

    public function test_can_batch_update_settings(): void
    {
        Setting::factory()->create(['key' => 'test.setting1', 'value' => 'old1']);
        Setting::factory()->create(['key' => 'test.setting2', 'value' => 'old2']);

        $result = $this->repository->updateSettings([
            'test.setting1' => 'new1',
            'test.setting2' => 'new2',
        ]);

        $this->assertTrue($result);
        $this->assertEquals('new1', Setting::where('key', 'test.setting1')->first()->value);
        $this->assertEquals('new2', Setting::where('key', 'test.setting2')->first()->value);
    }

    public function test_can_reset_setting_to_default(): void
    {
        $setting = Setting::factory()->create([
            'key' => 'test.setting',
            'value' => 'current_value',
            'default_value' => 'default_value'
        ]);

        $result = $this->repository->resetSetting('test.setting');

        $this->assertTrue($result);
        $this->assertEquals('default_value', $setting->fresh()->value);
    }

    public function test_can_create_setting(): void
    {
        $data = [
            'key' => 'test.new_setting',
            'value' => 'test_value',
            'category' => 'test',
            'type' => 'text',
            'description' => 'Test setting',
        ];

        $setting = $this->repository->createSetting($data);

        $this->assertInstanceOf(Setting::class, $setting);
        $this->assertEquals('test.new_setting', $setting->key);
        $this->assertEquals('test_value', $setting->value);
        $this->assertDatabaseHas('settings', ['key' => 'test.new_setting']);
    }

    public function test_can_delete_non_system_setting(): void
    {
        $setting = Setting::factory()->create([
            'key' => 'test.setting',
            'is_system' => false
        ]);

        $result = $this->repository->deleteSetting('test.setting');

        $this->assertTrue($result);
        $this->assertDatabaseMissing('settings', ['key' => 'test.setting']);
    }

    public function test_cannot_delete_system_setting(): void
    {
        $setting = Setting::factory()->create([
            'key' => 'system.setting',
            'is_system' => true
        ]);

        $result = $this->repository->deleteSetting('system.setting');

        $this->assertFalse($result);
        $this->assertDatabaseHas('settings', ['key' => 'system.setting']);
    }

    public function test_can_get_changed_settings(): void
    {
        // 建立未變更的設定
        Setting::factory()->create([
            'key' => 'test.unchanged',
            'value' => 'default',
            'default_value' => 'default'
        ]);

        // 建立已變更的設定
        Setting::factory()->create([
            'key' => 'test.changed',
            'value' => 'new_value',
            'default_value' => 'default'
        ]);

        $changedSettings = $this->repository->getChangedSettings();

        $this->assertCount(1, $changedSettings);
        $this->assertEquals('test.changed', $changedSettings->first()->key);
    }

    public function test_can_search_settings(): void
    {
        Setting::factory()->create(['key' => 'app.name', 'description' => 'Application name']);
        Setting::factory()->create(['key' => 'app.version', 'description' => 'Application version']);
        Setting::factory()->create(['key' => 'mail.host', 'description' => 'Mail server host']);

        $results = $this->repository->searchSettings('app');

        $this->assertCount(2, $results);
        $this->assertTrue($results->contains('key', 'app.name'));
        $this->assertTrue($results->contains('key', 'app.version'));
    }

    public function test_can_export_settings(): void
    {
        Setting::factory()->create(['key' => 'basic.setting1', 'category' => 'basic']);
        Setting::factory()->create(['key' => 'security.setting1', 'category' => 'security']);

        $exported = $this->repository->exportSettings(['basic']);

        $this->assertCount(1, $exported);
        $this->assertEquals('basic.setting1', $exported[0]['key']);
        $this->assertEquals('basic', $exported[0]['category']);
    }

    public function test_can_import_settings(): void
    {
        $data = [
            [
                'key' => 'imported.setting1',
                'value' => 'value1',
                'category' => 'test',
                'type' => 'text',
                'description' => 'Imported setting 1',
                'default_value' => 'value1',
                'is_encrypted' => false,
                'is_system' => false,
                'is_public' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'imported.setting2',
                'value' => 'value2',
                'category' => 'test',
                'type' => 'text',
                'description' => 'Imported setting 2',
                'default_value' => 'value2',
                'is_encrypted' => false,
                'is_system' => false,
                'is_public' => true,
                'sort_order' => 2,
            ],
        ];

        $result = $this->repository->importSettings($data);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['created']);
        $this->assertEquals(0, $result['updated']);
        $this->assertDatabaseHas('settings', ['key' => 'imported.setting1']);
        $this->assertDatabaseHas('settings', ['key' => 'imported.setting2']);
    }

    public function test_can_validate_setting(): void
    {
        $setting = Setting::factory()->create([
            'key' => 'test.number',
            'type' => 'number',
            'options' => [
                'validation' => 'integer|min:1|max:100'
            ]
        ]);

        $this->assertTrue($this->repository->validateSetting('test.number', 50));
        $this->assertFalse($this->repository->validateSetting('test.number', 150));
        $this->assertFalse($this->repository->validateSetting('test.number', 'not_a_number'));
    }

    public function test_can_create_backup(): void
    {
        Setting::factory()->create(['key' => 'test.setting1', 'category' => 'test']);
        Setting::factory()->create(['key' => 'test.setting2', 'category' => 'test']);

        $backup = $this->repository->createBackup('Test Backup', 'Test description', ['test']);

        $this->assertInstanceOf(SettingBackup::class, $backup);
        $this->assertEquals('Test Backup', $backup->name);
        $this->assertEquals('Test description', $backup->description);
        $this->assertCount(2, $backup->settings_data);
        $this->assertEquals($this->user->id, $backup->created_by);
    }

    public function test_can_restore_backup(): void
    {
        // 建立原始設定
        $setting = Setting::factory()->create([
            'key' => 'test.setting',
            'value' => 'original_value'
        ]);

        // 建立備份
        $backup = SettingBackup::factory()->create([
            'settings_data' => [
                [
                    'key' => 'test.setting',
                    'value' => 'backup_value',
                    'category' => 'test',
                    'type' => 'text',
                ]
            ]
        ]);

        $result = $this->repository->restoreBackup($backup->id);

        $this->assertTrue($result);
        $this->assertEquals('backup_value', $setting->fresh()->value);
    }

    public function test_can_get_backups(): void
    {
        SettingBackup::factory()->count(3)->create();

        $backups = $this->repository->getBackups(2);

        $this->assertCount(2, $backups);
    }

    public function test_can_delete_backup(): void
    {
        $backup = SettingBackup::factory()->create();

        $result = $this->repository->deleteBackup($backup->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('setting_backups', ['id' => $backup->id]);
    }

    public function test_can_get_setting_changes(): void
    {
        $setting = Setting::factory()->create(['key' => 'test.setting']);
        
        SettingChange::factory()->create([
            'setting_key' => 'test.setting',
            'changed_by' => $this->user->id
        ]);

        $changes = $this->repository->getSettingChanges('test.setting');

        $this->assertCount(1, $changes);
        $this->assertEquals('test.setting', $changes->first()->setting_key);
    }

    public function test_can_get_available_categories(): void
    {
        Setting::factory()->create(['category' => 'basic']);
        Setting::factory()->create(['category' => 'security']);
        Setting::factory()->create(['category' => 'basic']); // 重複分類

        $categories = $this->repository->getAvailableCategories();

        $this->assertCount(2, $categories);
        $this->assertTrue($categories->contains('basic'));
        $this->assertTrue($categories->contains('security'));
    }

    public function test_can_get_available_types(): void
    {
        Setting::factory()->create(['type' => 'text']);
        Setting::factory()->create(['type' => 'number']);
        Setting::factory()->create(['type' => 'text']); // 重複類型

        $types = $this->repository->getAvailableTypes();

        $this->assertCount(2, $types);
        $this->assertTrue($types->contains('text'));
        $this->assertTrue($types->contains('number'));
    }

    public function test_can_clear_cache(): void
    {
        // 設定一些快取
        Cache::put('settings_key_test.setting', 'cached_value');
        Cache::put('settings_all', 'cached_data');

        $this->repository->clearCache('test.setting');

        $this->assertFalse(Cache::has('settings_key_test.setting'));
        $this->assertTrue(Cache::has('settings_all')); // 其他快取應該還在

        $this->repository->clearCache(); // 清除所有快取

        $this->assertFalse(Cache::has('settings_all'));
    }

    public function test_can_get_and_set_cached_setting(): void
    {
        $setting = Setting::factory()->create([
            'key' => 'test.setting',
            'value' => 'test_value'
        ]);

        // 測試取得快取設定
        $value = $this->repository->getCachedSetting('test.setting', 'default');
        $this->assertEquals('test_value', $value);

        // 測試設定快取
        $this->repository->setCachedSetting('test.cached', 'cached_value');
        $cachedValue = Cache::get('settings_value_test.cached');
        $this->assertEquals('cached_value', $cachedValue);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}
