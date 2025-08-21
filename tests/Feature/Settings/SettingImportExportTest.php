<?php

namespace Tests\Feature\Settings;

use App\Livewire\Admin\Settings\SettingImportExport;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 設定匯入匯出功能測試
 */
class SettingImportExportTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create();
        $this->actingAs($this->adminUser);
        
        // 建立測試設定
        $this->createTestSettings();
    }

    /**
     * 建立測試設定
     */
    protected function createTestSettings(): void
    {
        Setting::create([
            'key' => 'app.name',
            'value' => 'Test Application',
            'category' => 'basic',
            'type' => 'text',
            'description' => '應用程式名稱',
            'default_value' => 'Laravel Admin',
            'is_system' => false,
        ]);

        Setting::create([
            'key' => 'app.timezone',
            'value' => 'Asia/Taipei',
            'category' => 'basic',
            'type' => 'select',
            'description' => '系統時區',
            'default_value' => 'UTC',
            'is_system' => true,
        ]);

        Setting::create([
            'key' => 'security.password_min_length',
            'value' => 10,
            'category' => 'security',
            'type' => 'number',
            'description' => '密碼最小長度',
            'default_value' => 8,
            'is_system' => false,
        ]);
    }

    /** @test */
    public function it_can_render_import_export_component()
    {
        Livewire::test(SettingImportExport::class)
            ->assertStatus(200)
            ->assertSee('匯出設定')
            ->assertSee('匯入設定');
    }

    /** @test */
    public function it_can_open_export_dialog()
    {
        Livewire::test(SettingImportExport::class)
            ->call('openExportDialog')
            ->assertSet('showExportDialog', true)
            ->assertSee('匯出選項');
    }

    /** @test */
    public function it_can_calculate_export_stats()
    {
        $component = Livewire::test(SettingImportExport::class)
            ->call('openExportDialog');

        $stats = $component->get('exportStats');
        
        $this->assertEquals(3, $stats['total']);
        $this->assertEquals(2, $stats['categories']);
        $this->assertArrayHasKey('size_estimate', $stats);
    }

    /** @test */
    public function it_can_filter_export_by_category()
    {
        $component = Livewire::test(SettingImportExport::class)
            ->set('exportCategories', ['basic'])
            ->call('openExportDialog');

        $stats = $component->get('exportStats');
        
        $this->assertEquals(2, $stats['total']); // 只有 basic 分類的設定
    }

    /** @test */
    public function it_can_exclude_system_settings_from_export()
    {
        $component = Livewire::test(SettingImportExport::class)
            ->set('exportIncludeSystem', false)
            ->call('openExportDialog');

        $stats = $component->get('exportStats');
        
        $this->assertEquals(2, $stats['total']); // 排除系統設定
    }

    /** @test */
    public function it_can_export_only_changed_settings()
    {
        // 修改一個設定
        $setting = Setting::where('key', 'app.name')->first();
        $setting->update(['value' => 'Modified App Name']);

        $component = Livewire::test(SettingImportExport::class)
            ->set('exportOnlyChanged', true)
            ->call('openExportDialog');

        $stats = $component->get('exportStats');
        
        $this->assertEquals(1, $stats['total']); // 只有已變更的設定
    }

    /** @test */
    public function it_can_open_import_dialog()
    {
        Livewire::test(SettingImportExport::class)
            ->call('openImportDialog')
            ->assertSet('showImportDialog', true)
            ->assertSet('currentStep', 'upload')
            ->assertSee('選擇設定檔案');
    }

    /** @test */
    public function it_can_validate_import_file_format()
    {
        Storage::fake('local');
        
        // 建立無效的檔案
        $invalidFile = UploadedFile::fake()->create('invalid.txt', 100, 'text/plain');
        
        Livewire::test(SettingImportExport::class)
            ->call('openImportDialog')
            ->set('importFile', $invalidFile)
            ->assertHasErrors(); // 應該有驗證錯誤
    }

    /** @test */
    public function it_can_parse_valid_import_file()
    {
        Storage::fake('local');
        
        // 建立有效的 JSON 檔案
        $validData = [
            [
                'key' => 'test.setting',
                'value' => 'test value',
                'category' => 'test',
                'type' => 'text',
                'description' => 'Test setting',
                'default_value' => 'default',
                'is_system' => false,
                'is_encrypted' => false,
            ]
        ];
        
        $jsonContent = json_encode($validData);
        $validFile = UploadedFile::fake()->createWithContent('settings.json', $jsonContent);
        
        $component = Livewire::test(SettingImportExport::class)
            ->call('openImportDialog')
            ->set('importFile', $validFile);

        $this->assertEquals(1, count($component->get('importData')));
        $this->assertEquals('preview', $component->get('currentStep'));
    }

    /** @test */
    public function it_can_detect_import_conflicts()
    {
        Storage::fake('local');
        
        // 建立與現有設定衝突的資料
        $conflictData = [
            [
                'key' => 'app.name', // 與現有設定衝突
                'value' => 'Conflicted App Name',
                'category' => 'basic',
                'type' => 'text',
                'description' => 'Conflicted description',
                'default_value' => 'Laravel Admin',
                'is_system' => false,
                'is_encrypted' => false,
            ]
        ];
        
        $jsonContent = json_encode($conflictData);
        $conflictFile = UploadedFile::fake()->createWithContent('conflict.json', $jsonContent);
        
        $component = Livewire::test(SettingImportExport::class)
            ->call('openImportDialog')
            ->set('importFile', $conflictFile);

        $conflicts = $component->get('importConflicts');
        
        $this->assertCount(1, $conflicts);
        $this->assertEquals('app.name', $conflicts[0]['key']);
        $this->assertTrue($conflicts[0]['has_value_conflict']);
        $this->assertEquals('conflicts', $component->get('currentStep'));
    }

    /** @test */
    public function it_can_handle_skip_conflict_resolution()
    {
        Storage::fake('local');
        
        $conflictData = [
            [
                'key' => 'app.name',
                'value' => 'New App Name',
                'category' => 'basic',
                'type' => 'text',
                'description' => 'New description',
                'default_value' => 'Laravel Admin',
                'is_system' => false,
                'is_encrypted' => false,
            ]
        ];
        
        $jsonContent = json_encode($conflictData);
        $conflictFile = UploadedFile::fake()->createWithContent('conflict.json', $jsonContent);
        
        $originalValue = Setting::where('key', 'app.name')->first()->value;
        
        $component = Livewire::test(SettingImportExport::class)
            ->call('openImportDialog')
            ->set('importFile', $conflictFile)
            ->set('conflictResolution', 'skip')
            ->call('executeImport');

        // 確認設定值沒有改變
        $this->assertEquals($originalValue, Setting::where('key', 'app.name')->first()->value);
        
        $results = $component->get('importResults');
        $this->assertEquals(1, $results['skipped']);
        $this->assertEquals(0, $results['updated']);
    }

    /** @test */
    public function it_can_handle_update_conflict_resolution()
    {
        Storage::fake('local');
        
        $conflictData = [
            [
                'key' => 'app.name',
                'value' => 'Updated App Name',
                'category' => 'basic',
                'type' => 'text',
                'description' => 'Updated description',
                'default_value' => 'Laravel Admin',
                'is_system' => false,
                'is_encrypted' => false,
            ]
        ];
        
        $jsonContent = json_encode($conflictData);
        $conflictFile = UploadedFile::fake()->createWithContent('conflict.json', $jsonContent);
        
        $component = Livewire::test(SettingImportExport::class)
            ->call('openImportDialog')
            ->set('importFile', $conflictFile)
            ->set('conflictResolution', 'update')
            ->call('executeImport');

        // 確認設定值已更新
        $this->assertEquals('Updated App Name', Setting::where('key', 'app.name')->first()->value);
        
        $results = $component->get('importResults');
        $this->assertEquals(0, $results['skipped']);
        $this->assertEquals(1, $results['updated']);
    }

    /** @test */
    public function it_can_handle_merge_conflict_resolution()
    {
        Storage::fake('local');
        
        $conflictData = [
            [
                'key' => 'app.timezone', // 系統設定
                'value' => 'America/New_York',
                'category' => 'basic',
                'type' => 'select',
                'description' => 'Updated timezone description',
                'default_value' => 'UTC',
                'is_system' => false, // 嘗試修改系統屬性
                'is_encrypted' => false,
            ]
        ];
        
        $jsonContent = json_encode($conflictData);
        $conflictFile = UploadedFile::fake()->createWithContent('merge.json', $jsonContent);
        
        $component = Livewire::test(SettingImportExport::class)
            ->call('openImportDialog')
            ->set('importFile', $conflictFile)
            ->set('conflictResolution', 'merge')
            ->call('executeImport');

        $updatedSetting = Setting::where('key', 'app.timezone')->first();
        
        // 確認值已更新但系統屬性保持不變
        $this->assertEquals('America/New_York', $updatedSetting->value);
        $this->assertTrue($updatedSetting->is_system); // 系統屬性應該保持不變
        
        $results = $component->get('importResults');
        $this->assertEquals(1, $results['updated']);
    }

    /** @test */
    public function it_can_create_new_settings_from_import()
    {
        Storage::fake('local');
        
        $newData = [
            [
                'key' => 'new.setting',
                'value' => 'new value',
                'category' => 'test',
                'type' => 'text',
                'description' => 'New test setting',
                'default_value' => 'default',
                'is_system' => false,
                'is_encrypted' => false,
            ]
        ];
        
        $jsonContent = json_encode($newData);
        $newFile = UploadedFile::fake()->createWithContent('new.json', $jsonContent);
        
        $component = Livewire::test(SettingImportExport::class)
            ->call('openImportDialog')
            ->set('importFile', $newFile)
            ->call('executeImport');

        // 確認新設定已建立
        $this->assertDatabaseHas('settings', [
            'key' => 'new.setting',
            'value' => json_encode('new value'),
        ]);
        
        $results = $component->get('importResults');
        $this->assertEquals(1, $results['created']);
    }

    /** @test */
    public function it_can_perform_dry_run_import()
    {
        Storage::fake('local');
        
        $newData = [
            [
                'key' => 'dry.run.setting',
                'value' => 'dry run value',
                'category' => 'test',
                'type' => 'text',
                'description' => 'Dry run setting',
                'default_value' => 'default',
                'is_system' => false,
                'is_encrypted' => false,
            ]
        ];
        
        $jsonContent = json_encode($newData);
        $dryRunFile = UploadedFile::fake()->createWithContent('dryrun.json', $jsonContent);
        
        $component = Livewire::test(SettingImportExport::class)
            ->call('openImportDialog')
            ->set('importFile', $dryRunFile)
            ->set('dryRun', true)
            ->call('previewImport');

        // 確認設定沒有實際建立
        $this->assertDatabaseMissing('settings', [
            'key' => 'dry.run.setting',
        ]);
        
        // 但結果應該顯示會建立
        $results = $component->get('importResults');
        $this->assertEquals(1, $results['created']);
    }

    /** @test */
    public function it_can_select_specific_settings_for_import()
    {
        Storage::fake('local');
        
        $multipleData = [
            [
                'key' => 'setting.one',
                'value' => 'value one',
                'category' => 'test',
                'type' => 'text',
                'description' => 'Setting one',
                'default_value' => 'default',
                'is_system' => false,
                'is_encrypted' => false,
            ],
            [
                'key' => 'setting.two',
                'value' => 'value two',
                'category' => 'test',
                'type' => 'text',
                'description' => 'Setting two',
                'default_value' => 'default',
                'is_system' => false,
                'is_encrypted' => false,
            ]
        ];
        
        $jsonContent = json_encode($multipleData);
        $multipleFile = UploadedFile::fake()->createWithContent('multiple.json', $jsonContent);
        
        $component = Livewire::test(SettingImportExport::class)
            ->call('openImportDialog')
            ->set('importFile', $multipleFile)
            ->set('selectedSettings', ['setting.one']) // 只選擇一個設定
            ->call('executeImport');

        // 確認只有選中的設定被建立
        $this->assertDatabaseHas('settings', ['key' => 'setting.one']);
        $this->assertDatabaseMissing('settings', ['key' => 'setting.two']);
        
        $results = $component->get('importResults');
        $this->assertEquals(1, $results['created']);
    }

    /** @test */
    public function it_can_toggle_category_selection()
    {
        Storage::fake('local');
        
        $categoryData = [
            [
                'key' => 'cat1.setting1',
                'value' => 'value1',
                'category' => 'category1',
                'type' => 'text',
                'description' => 'Category 1 Setting 1',
                'default_value' => 'default',
                'is_system' => false,
                'is_encrypted' => false,
            ],
            [
                'key' => 'cat1.setting2',
                'value' => 'value2',
                'category' => 'category1',
                'type' => 'text',
                'description' => 'Category 1 Setting 2',
                'default_value' => 'default',
                'is_system' => false,
                'is_encrypted' => false,
            ]
        ];
        
        $jsonContent = json_encode($categoryData);
        $categoryFile = UploadedFile::fake()->createWithContent('category.json', $jsonContent);
        
        $component = Livewire::test(SettingImportExport::class)
            ->call('openImportDialog')
            ->set('importFile', $categoryFile)
            ->call('toggleCategorySelection', 'category1');

        $selectedSettings = $component->get('selectedSettings');
        
        // 確認該分類下的所有設定都被選中
        $this->assertContains('cat1.setting1', $selectedSettings);
        $this->assertContains('cat1.setting2', $selectedSettings);
        
        // 再次切換應該取消選中
        $component->call('toggleCategorySelection', 'category1');
        $selectedSettings = $component->get('selectedSettings');
        
        $this->assertNotContains('cat1.setting1', $selectedSettings);
        $this->assertNotContains('cat1.setting2', $selectedSettings);
    }

    /** @test */
    public function it_validates_import_data_when_enabled()
    {
        Storage::fake('local');
        
        // 建立無效的設定資料（缺少必要欄位）
        $invalidData = [
            [
                'key' => 'invalid.setting',
                'value' => 'some value',
                // 缺少 category, type 等必要欄位
            ]
        ];
        
        $jsonContent = json_encode($invalidData);
        $invalidFile = UploadedFile::fake()->createWithContent('invalid.json', $jsonContent);
        
        $component = Livewire::test(SettingImportExport::class)
            ->call('openImportDialog')
            ->set('importFile', $invalidFile)
            ->set('validateImportData', true)
            ->call('executeImport');

        $results = $component->get('importResults');
        
        // 應該有錯誤且沒有建立任何設定
        $this->assertNotEmpty($results['errors']);
        $this->assertEquals(0, $results['created']);
    }
}