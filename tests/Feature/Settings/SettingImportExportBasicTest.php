<?php

namespace Tests\Feature\Settings;

use App\Livewire\Admin\Settings\SettingImportExport;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 設定匯入匯出基本功能測試
 */
class SettingImportExportBasicTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create();
        $this->actingAs($this->adminUser);
        
        // 建立基本測試設定
        Setting::create([
            'key' => 'test.setting',
            'value' => 'test value',
            'category' => 'test',
            'type' => 'text',
            'description' => 'Test setting',
            'default_value' => 'default',
            'is_system' => false,
        ]);
    }

    /** @test */
    public function it_can_render_component()
    {
        $component = Livewire::test(SettingImportExport::class);
        
        $component->assertStatus(200);
    }

    /** @test */
    public function it_can_open_and_close_export_dialog()
    {
        Livewire::test(SettingImportExport::class)
            ->call('openExportDialog')
            ->assertSet('showExportDialog', true)
            ->call('closeExportDialog')
            ->assertSet('showExportDialog', false);
    }

    /** @test */
    public function it_can_open_and_close_import_dialog()
    {
        Livewire::test(SettingImportExport::class)
            ->call('openImportDialog')
            ->assertSet('showImportDialog', true)
            ->assertSet('currentStep', 'upload')
            ->call('closeImportDialog')
            ->assertSet('showImportDialog', false);
    }

    /** @test */
    public function it_can_calculate_export_stats()
    {
        $component = Livewire::test(SettingImportExport::class)
            ->call('openExportDialog');

        $stats = $component->get('exportStats');
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('categories', $stats);
        $this->assertArrayHasKey('size_estimate', $stats);
        $this->assertEquals(1, $stats['total']);
    }

    /** @test */
    public function it_can_reset_import_state()
    {
        $component = Livewire::test(SettingImportExport::class)
            ->call('openImportDialog')
            ->set('currentStep', 'preview')
            ->set('importData', [['key' => 'test', 'value' => 'value']])
            ->call('resetImportState');

        $this->assertEquals('upload', $component->get('currentStep'));
        $this->assertEmpty($component->get('importData'));
    }

    /** @test */
    public function it_can_toggle_select_all()
    {
        $component = Livewire::test(SettingImportExport::class)
            ->set('importData', [
                ['key' => 'setting1', 'category' => 'test'],
                ['key' => 'setting2', 'category' => 'test'],
            ])
            ->call('toggleSelectAll');

        $selectedSettings = $component->get('selectedSettings');
        $this->assertContains('setting1', $selectedSettings);
        $this->assertContains('setting2', $selectedSettings);

        // 再次切換應該清空選擇
        $component->call('toggleSelectAll');
        $this->assertEmpty($component->get('selectedSettings'));
    }

    /** @test */
    public function it_can_handle_available_categories()
    {
        $component = Livewire::test(SettingImportExport::class);
        
        $categories = $component->get('availableCategories');
        $this->assertIsArray($categories);
    }
}