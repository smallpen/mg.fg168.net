<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Repositories\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 設定匯入匯出單元測試
 */
class SettingImportExportUnitTest extends TestCase
{
    use RefreshDatabase;

    protected SettingsRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new SettingsRepository();
        
        // 建立測試設定
        Setting::create([
            'key' => 'test.setting1',
            'value' => 'original value 1',
            'category' => 'test',
            'type' => 'text',
            'description' => 'Test setting 1',
            'default_value' => 'default 1',
            'is_system' => false,
        ]);

        Setting::create([
            'key' => 'test.setting2',
            'value' => 'original value 2',
            'category' => 'test',
            'type' => 'text',
            'description' => 'Test setting 2',
            'default_value' => 'default 2',
            'is_system' => true,
        ]);
    }

    /** @test */
    public function it_can_export_settings()
    {
        $exported = $this->repository->exportSettings();
        
        $this->assertIsArray($exported);
        $this->assertCount(2, $exported);
        
        $setting1 = collect($exported)->firstWhere('key', 'test.setting1');
        $this->assertNotNull($setting1);
        $this->assertEquals('original value 1', $setting1['value']);
        $this->assertEquals('test', $setting1['category']);
        $this->assertEquals('text', $setting1['type']);
    }

    /** @test */
    public function it_can_export_settings_by_category()
    {
        // 建立另一個分類的設定
        Setting::create([
            'key' => 'other.setting',
            'value' => 'other value',
            'category' => 'other',
            'type' => 'text',
            'description' => 'Other setting',
            'default_value' => 'other default',
            'is_system' => false,
        ]);

        $exported = $this->repository->exportSettings(['test']);
        
        $this->assertCount(2, $exported); // 只有 test 分類的設定
        
        foreach ($exported as $setting) {
            $this->assertEquals('test', $setting['category']);
        }
    }

    /** @test */
    public function it_can_import_new_settings()
    {
        $importData = [
            [
                'key' => 'new.setting',
                'value' => 'new value',
                'category' => 'new',
                'type' => 'text',
                'description' => 'New setting',
                'default_value' => 'new default',
                'is_system' => false,
                'is_encrypted' => false,
                'is_public' => true,
                'sort_order' => 0,
            ]
        ];

        $result = $this->repository->importSettings($importData);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['created']);
        $this->assertEquals(0, $result['updated']);
        $this->assertEquals(0, $result['skipped']);

        $this->assertDatabaseHas('settings', [
            'key' => 'new.setting',
        ]);
        
        $setting = Setting::where('key', 'new.setting')->first();
        $this->assertEquals('new value', $setting->value);
    }

    /** @test */
    public function it_can_handle_import_conflicts_with_skip_resolution()
    {
        $importData = [
            [
                'key' => 'test.setting1', // 與現有設定衝突
                'value' => 'conflicted value',
                'category' => 'test',
                'type' => 'text',
                'description' => 'Conflicted setting',
                'default_value' => 'default 1',
                'is_system' => false,
                'is_encrypted' => false,
                'is_public' => true,
                'sort_order' => 0,
            ]
        ];

        $result = $this->repository->importSettings($importData, [
            'conflict_resolution' => 'skip'
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['created']);
        $this->assertEquals(0, $result['updated']);
        $this->assertEquals(1, $result['skipped']);

        // 確認原始值沒有改變
        $setting = Setting::where('key', 'test.setting1')->first();
        $this->assertEquals('original value 1', $setting->value);
    }

    /** @test */
    public function it_can_handle_import_conflicts_with_update_resolution()
    {
        $importData = [
            [
                'key' => 'test.setting1',
                'value' => 'updated value',
                'category' => 'test',
                'type' => 'text',
                'description' => 'Updated setting',
                'default_value' => 'default 1',
                'is_system' => false,
                'is_encrypted' => false,
                'is_public' => true,
                'sort_order' => 0,
            ]
        ];

        $result = $this->repository->importSettings($importData, [
            'conflict_resolution' => 'update'
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['created']);
        $this->assertEquals(1, $result['updated']);
        $this->assertEquals(0, $result['skipped']);

        // 確認值已更新
        $setting = Setting::where('key', 'test.setting1')->first();
        $this->assertEquals('updated value', $setting->value);
        $this->assertEquals('Updated setting', $setting->description);
    }

    /** @test */
    public function it_can_handle_import_conflicts_with_merge_resolution()
    {
        $importData = [
            [
                'key' => 'test.setting2', // 系統設定
                'value' => 'merged value',
                'category' => 'test',
                'type' => 'text',
                'description' => 'Merged setting',
                'default_value' => 'default 2',
                'is_system' => false, // 嘗試修改系統屬性
                'is_encrypted' => false,
                'is_public' => true,
                'sort_order' => 0,
            ]
        ];

        $result = $this->repository->importSettings($importData, [
            'conflict_resolution' => 'merge'
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['created']);
        $this->assertEquals(1, $result['updated']);
        $this->assertEquals(0, $result['skipped']);

        $setting = Setting::where('key', 'test.setting2')->first();
        
        // 確認值和描述已更新
        $this->assertEquals('merged value', $setting->value);
        $this->assertEquals('Merged setting', $setting->description);
        
        // 但系統屬性應該保持不變
        $this->assertTrue($setting->is_system);
    }

    /** @test */
    public function it_can_perform_dry_run_import()
    {
        $importData = [
            [
                'key' => 'dry.run.setting',
                'value' => 'dry run value',
                'category' => 'test',
                'type' => 'text',
                'description' => 'Dry run setting',
                'default_value' => 'dry default',
                'is_system' => false,
                'is_encrypted' => false,
                'is_public' => true,
                'sort_order' => 0,
            ]
        ];

        $result = $this->repository->importSettings($importData, [
            'dry_run' => true
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['created']);

        // 確認設定沒有實際建立
        $this->assertDatabaseMissing('settings', [
            'key' => 'dry.run.setting',
        ]);
    }

    /** @test */
    public function it_can_import_selected_settings_only()
    {
        $importData = [
            [
                'key' => 'selected.setting1',
                'value' => 'selected value 1',
                'category' => 'test',
                'type' => 'text',
                'description' => 'Selected setting 1',
                'default_value' => 'default',
                'is_system' => false,
                'is_encrypted' => false,
                'is_public' => true,
                'sort_order' => 0,
            ],
            [
                'key' => 'selected.setting2',
                'value' => 'selected value 2',
                'category' => 'test',
                'type' => 'text',
                'description' => 'Selected setting 2',
                'default_value' => 'default',
                'is_system' => false,
                'is_encrypted' => false,
                'is_public' => true,
                'sort_order' => 0,
            ]
        ];

        $result = $this->repository->importSettings($importData, [
            'selected_keys' => ['selected.setting1'] // 只匯入第一個設定
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['created']);

        // 確認只有選中的設定被建立
        $this->assertDatabaseHas('settings', ['key' => 'selected.setting1']);
        $this->assertDatabaseMissing('settings', ['key' => 'selected.setting2']);
    }

    /** @test */
    public function it_handles_invalid_import_data()
    {
        $invalidData = [
            [
                'key' => 'invalid.setting',
                // 缺少必要欄位
            ]
        ];

        $result = $this->repository->importSettings($invalidData, [
            'validate_data' => true
        ]);

        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['errors']);
        $this->assertEquals(0, $result['created']);
    }

    /** @test */
    public function it_provides_detailed_import_results()
    {
        $importData = [
            [
                'key' => 'detailed.setting1',
                'value' => 'detailed value 1',
                'category' => 'test',
                'type' => 'text',
                'description' => 'Detailed setting 1',
                'default_value' => 'default',
                'is_system' => false,
                'is_encrypted' => false,
                'is_public' => true,
                'sort_order' => 0,
            ],
            [
                'key' => 'test.setting1', // 衝突設定
                'value' => 'conflicted value',
                'category' => 'test',
                'type' => 'text',
                'description' => 'Conflicted setting',
                'default_value' => 'default',
                'is_system' => false,
                'is_encrypted' => false,
                'is_public' => true,
                'sort_order' => 0,
            ]
        ];

        $result = $this->repository->importSettings($importData, [
            'conflict_resolution' => 'skip'
        ]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('details', $result);
        
        $details = $result['details'];
        $this->assertArrayHasKey('detailed.setting1', $details);
        $this->assertArrayHasKey('test.setting1', $details);
        
        $this->assertEquals('created', $details['detailed.setting1']['status']);
        $this->assertEquals('skipped', $details['test.setting1']['status']);
    }
}