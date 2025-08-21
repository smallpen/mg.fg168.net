<?php

namespace Tests\Feature\Livewire\Admin\Settings;

use App\Livewire\Admin\Settings\SettingChangeHistory;
use App\Models\Setting;
use App\Models\SettingChange;
use App\Models\User;
use App\Repositories\SettingsRepositoryInterface;
use App\Services\ConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 設定變更歷史元件測試
 */
class SettingChangeHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $editor;
    protected Setting $setting;
    protected SettingChange $change;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立測試使用者
        $this->admin = User::factory()->create([
            'name' => '管理員',
            'username' => 'admin',
        ]);

        $this->editor = User::factory()->create([
            'name' => '編輯者',
            'username' => 'editor',
        ]);

        // 建立測試設定
        $this->setting = Setting::factory()->create([
            'key' => 'app.name',
            'value' => 'Laravel Admin',
            'category' => 'basic',
            'type' => 'text',
            'description' => '應用程式名稱',
            'default_value' => 'Laravel App',
            'is_system' => true,
        ]);

        // 建立測試變更記錄
        $this->change = SettingChange::factory()->create([
            'setting_key' => $this->setting->key,
            'old_value' => 'Laravel App',
            'new_value' => 'Laravel Admin',
            'changed_by' => $this->admin->id,
            'ip_address' => '127.0.0.1',
            'reason' => '更新應用程式名稱',
        ]);

        // 模擬已登入的管理員
        $this->actingAs($this->admin);
    }

    /** @test */
    public function it_can_render_the_component()
    {
        Livewire::test(SettingChangeHistory::class)
            ->assertStatus(200)
            ->assertSee('設定變更歷史')
            ->assertSee('檢視和管理系統設定的變更記錄');
    }

    /** @test */
    public function it_displays_change_records()
    {
        Livewire::test(SettingChangeHistory::class)
            ->assertSee($this->change->setting_key)
            ->assertSee($this->admin->name)
            ->assertSee('更新應用程式名稱');
    }

    /** @test */
    public function it_can_search_changes()
    {
        // 建立另一個變更記錄
        $otherChange = SettingChange::factory()->create([
            'setting_key' => 'app.description',
            'old_value' => 'Old Description',
            'new_value' => 'New Description',
            'changed_by' => $this->editor->id,
        ]);

        Livewire::test(SettingChangeHistory::class)
            ->set('search', 'app.name')
            ->assertSee($this->change->setting_key)
            ->assertDontSee($otherChange->setting_key);
    }

    /** @test */
    public function it_can_filter_by_category()
    {
        // 建立不同分類的設定和變更記錄
        $securitySetting = Setting::factory()->create([
            'key' => 'security.password_min_length',
            'category' => 'security',
        ]);

        $securityChange = SettingChange::factory()->create([
            'setting_key' => $securitySetting->key,
            'changed_by' => $this->admin->id,
        ]);

        Livewire::test(SettingChangeHistory::class)
            ->set('categoryFilter', 'basic')
            ->assertSee($this->change->setting_key)
            ->assertDontSee($securityChange->setting_key);
    }

    /** @test */
    public function it_can_filter_by_user()
    {
        // 建立編輯者的變更記錄
        $editorChange = SettingChange::factory()->create([
            'setting_key' => 'app.description',
            'changed_by' => $this->editor->id,
        ]);

        Livewire::test(SettingChangeHistory::class)
            ->set('userFilter', $this->admin->id)
            ->assertSee($this->change->setting_key)
            ->assertDontSee($editorChange->setting_key);
    }

    /** @test */
    public function it_can_filter_by_date_range()
    {
        // 建立昨天的變更記錄
        $yesterdayChange = SettingChange::factory()->create([
            'setting_key' => 'app.timezone',
            'changed_by' => $this->admin->id,
            'created_at' => now()->subDay(),
        ]);

        Livewire::test(SettingChangeHistory::class)
            ->set('dateFrom', now()->format('Y-m-d'))
            ->set('dateTo', now()->format('Y-m-d'))
            ->assertSee($this->change->setting_key)
            ->assertDontSee($yesterdayChange->setting_key);
    }

    /** @test */
    public function it_can_filter_important_changes_only()
    {
        // 建立重要變更（系統設定）
        $importantChange = SettingChange::factory()->create([
            'setting_key' => $this->setting->key, // 系統設定
            'changed_by' => $this->admin->id,
        ]);

        // 建立一般變更
        $normalSetting = Setting::factory()->create([
            'key' => 'app.description',
            'category' => 'basic',
            'is_system' => false,
        ]);

        $normalChange = SettingChange::factory()->create([
            'setting_key' => $normalSetting->key,
            'changed_by' => $this->admin->id,
        ]);

        Livewire::test(SettingChangeHistory::class)
            ->set('importantOnly', true)
            ->assertSee($importantChange->setting_key)
            ->assertDontSee($normalChange->setting_key);
    }

    /** @test */
    public function it_can_sort_changes()
    {
        Livewire::test(SettingChangeHistory::class)
            ->call('sortBy', 'setting_key')
            ->assertSet('sortBy', 'setting_key')
            ->assertSet('sortDirection', 'desc')
            ->call('sortBy', 'setting_key') // 再次點擊切換方向
            ->assertSet('sortDirection', 'asc');
    }

    /** @test */
    public function it_can_set_per_page()
    {
        Livewire::test(SettingChangeHistory::class)
            ->call('setPerPage', 50)
            ->assertSet('perPage', 50);
    }

    /** @test */
    public function it_can_clear_filters()
    {
        Livewire::test(SettingChangeHistory::class)
            ->set('search', 'test')
            ->set('categoryFilter', 'security')
            ->set('userFilter', $this->admin->id)
            ->set('importantOnly', true)
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('categoryFilter', 'all')
            ->assertSet('userFilter', 'all')
            ->assertSet('importantOnly', false);
    }

    /** @test */
    public function it_can_set_date_ranges()
    {
        $component = Livewire::test(SettingChangeHistory::class);

        // 測試今天
        $component->call('setDateRange', 'today')
            ->assertSet('dateFrom', now()->format('Y-m-d'))
            ->assertSet('dateTo', now()->format('Y-m-d'));

        // 測試本週
        $component->call('setDateRange', 'week')
            ->assertSet('dateFrom', now()->subWeek()->format('Y-m-d'))
            ->assertSet('dateTo', now()->format('Y-m-d'));

        // 測試本月
        $component->call('setDateRange', 'month')
            ->assertSet('dateFrom', now()->subMonth()->format('Y-m-d'))
            ->assertSet('dateTo', now()->format('Y-m-d'));
    }

    /** @test */
    public function it_can_show_change_details()
    {
        Livewire::test(SettingChangeHistory::class)
            ->call('showDetails', $this->change->id)
            ->assertSet('selectedChange.id', $this->change->id)
            ->assertSet('showDetailsModal', true)
            ->assertSee('變更詳情');
    }

    /** @test */
    public function it_can_close_details_modal()
    {
        Livewire::test(SettingChangeHistory::class)
            ->set('selectedChange', $this->change)
            ->set('showDetailsModal', true)
            ->call('closeDetailsModal')
            ->assertSet('selectedChange', null)
            ->assertSet('showDetailsModal', false);
    }

    /** @test */
    public function it_can_confirm_restore()
    {
        Livewire::test(SettingChangeHistory::class)
            ->call('confirmRestore', $this->change->id)
            ->assertSet('selectedChange.id', $this->change->id)
            ->assertSet('showRestoreModal', true);
    }

    /** @test */
    public function it_can_execute_restore()
    {
        // 模擬 SettingsRepository
        $mockRepository = $this->mock(SettingsRepositoryInterface::class);
        $mockRepository->shouldReceive('updateSetting')
            ->with($this->change->setting_key, $this->change->old_value)
            ->once()
            ->andReturn(true);

        Livewire::test(SettingChangeHistory::class)
            ->set('selectedChange', $this->change)
            ->call('executeRestore')
            ->assertSet('showRestoreModal', false)
            ->assertSet('selectedChange', null)
            ->assertDispatched('setting-updated');

        // 驗證新的變更記錄被建立
        $this->assertDatabaseHas('setting_changes', [
            'setting_key' => $this->change->setting_key,
            'old_value' => json_encode($this->change->new_value),
            'new_value' => json_encode($this->change->old_value),
            'changed_by' => $this->admin->id,
        ]);
    }

    /** @test */
    public function it_handles_restore_failure()
    {
        // 模擬 SettingsRepository 失敗
        $mockRepository = $this->mock(SettingsRepositoryInterface::class);
        $mockRepository->shouldReceive('updateSetting')
            ->once()
            ->andReturn(false);

        Livewire::test(SettingChangeHistory::class)
            ->set('selectedChange', $this->change)
            ->call('executeRestore')
            ->assertHasErrors(); // 應該有錯誤訊息
    }

    /** @test */
    public function it_can_close_restore_modal()
    {
        Livewire::test(SettingChangeHistory::class)
            ->set('selectedChange', $this->change)
            ->set('showRestoreModal', true)
            ->call('closeRestoreModal')
            ->assertSet('selectedChange', null)
            ->assertSet('showRestoreModal', false);
    }

    /** @test */
    public function it_can_open_notification_settings()
    {
        Livewire::test(SettingChangeHistory::class)
            ->call('openNotificationSettings')
            ->assertSet('showNotificationModal', true);
    }

    /** @test */
    public function it_can_save_notification_settings()
    {
        // 模擬 SettingsRepository
        $mockRepository = $this->mock(SettingsRepositoryInterface::class);
        $mockRepository->shouldReceive('updateSetting')
            ->with('notifications.change_history', \Mockery::type('array'))
            ->once()
            ->andReturn(true);

        Livewire::test(SettingChangeHistory::class)
            ->set('notificationSettings', [
                'email_enabled' => true,
                'important_only' => false,
                'categories' => ['basic', 'security'],
                'users' => [],
            ])
            ->call('saveNotificationSettings')
            ->assertSet('showNotificationModal', false);
    }

    /** @test */
    public function it_can_export_changes()
    {
        Livewire::test(SettingChangeHistory::class)
            ->call('exportChanges')
            ->assertDispatched('download-file');
    }

    /** @test */
    public function it_displays_correct_statistics()
    {
        // 建立額外的測試資料
        SettingChange::factory()->count(5)->create([
            'changed_by' => $this->admin->id,
        ]);

        SettingChange::factory()->create([
            'changed_by' => $this->editor->id,
        ]);

        $component = Livewire::test(SettingChangeHistory::class);
        $stats = $component->get('stats');

        $this->assertArrayHasKey('total_changes', $stats);
        $this->assertArrayHasKey('important_changes', $stats);
        $this->assertArrayHasKey('unique_settings', $stats);
        $this->assertArrayHasKey('unique_users', $stats);
        $this->assertArrayHasKey('filtered_count', $stats);

        $this->assertGreaterThan(0, $stats['total_changes']);
        $this->assertGreaterThanOrEqual(2, $stats['unique_users']); // admin 和 editor
    }

    /** @test */
    public function it_shows_recent_active_settings()
    {
        $component = Livewire::test(SettingChangeHistory::class);
        $recentSettings = $component->get('recentActiveSettings');

        $this->assertNotEmpty($recentSettings);
        $this->assertTrue($recentSettings->contains('key', $this->setting->key));
    }

    /** @test */
    public function it_handles_setting_updated_event()
    {
        Livewire::test(SettingChangeHistory::class)
            ->dispatch('setting-updated', settingKey: 'app.name')
            ->assertStatus(200);
    }

    /** @test */
    public function it_formats_display_values_correctly()
    {
        $component = new SettingChangeHistory();

        // 測試 null 值
        $this->assertEquals('(空值)', $component->formatDisplayValue(null));

        // 測試布林值
        $this->assertEquals('是', $component->formatDisplayValue(true));
        $this->assertEquals('否', $component->formatDisplayValue(false));

        // 測試陣列值
        $arrayValue = ['key' => 'value'];
        $result = $component->formatDisplayValue($arrayValue);
        $this->assertStringContainsString('key', $result);
        $this->assertStringContainsString('value', $result);

        // 測試長字串截斷
        $longString = str_repeat('a', 100);
        $result = $component->formatDisplayValue($longString, 50);
        $this->assertStringEndsWith('...', $result);
        $this->assertEquals(50, strlen($result));
    }

    /** @test */
    public function it_gets_correct_change_type_icons()
    {
        $component = new SettingChangeHistory();

        // 測試新增
        $newChange = new SettingChange(['old_value' => null, 'new_value' => 'value']);
        $this->assertEquals('plus-circle', $component->getChangeTypeIcon($newChange));

        // 測試刪除
        $deleteChange = new SettingChange(['old_value' => 'value', 'new_value' => null]);
        $this->assertEquals('minus-circle', $component->getChangeTypeIcon($deleteChange));

        // 測試回復
        $restoreChange = new SettingChange(['reason' => '回復到之前版本']);
        $this->assertEquals('arrow-uturn-left', $component->getChangeTypeIcon($restoreChange));

        // 測試修改
        $updateChange = new SettingChange(['old_value' => 'old', 'new_value' => 'new']);
        $this->assertEquals('pencil-square', $component->getChangeTypeIcon($updateChange));
    }

    /** @test */
    public function it_gets_correct_change_type_text()
    {
        $component = new SettingChangeHistory();

        // 測試新增
        $newChange = new SettingChange(['old_value' => null, 'new_value' => 'value']);
        $this->assertEquals('新增', $component->getChangeTypeText($newChange));

        // 測試刪除
        $deleteChange = new SettingChange(['old_value' => 'value', 'new_value' => null]);
        $this->assertEquals('刪除', $component->getChangeTypeText($deleteChange));

        // 測試回復
        $restoreChange = new SettingChange(['reason' => '回復到之前版本']);
        $this->assertEquals('回復', $component->getChangeTypeText($restoreChange));

        // 測試修改
        $updateChange = new SettingChange(['old_value' => 'old', 'new_value' => 'new']);
        $this->assertEquals('修改', $component->getChangeTypeText($updateChange));
    }

    /** @test */
    public function it_gets_correct_importance_labels()
    {
        $component = new SettingChangeHistory();

        // 模擬重要變更
        $importantChange = new SettingChange();
        $importantChange->setRelation('setting', new Setting(['is_system' => true]));
        $this->assertEquals('重要', $component->getImportanceLabel($importantChange));

        // 模擬一般變更
        $normalChange = new SettingChange();
        $normalChange->setRelation('setting', new Setting(['is_system' => false, 'category' => 'basic']));
        $this->assertEquals('一般', $component->getImportanceLabel($normalChange));
    }

    /** @test */
    public function it_loads_notification_settings_on_mount()
    {
        // 建立通知設定
        Setting::factory()->create([
            'key' => 'notifications.change_history',
            'value' => [
                'email_enabled' => true,
                'important_only' => false,
                'categories' => ['basic'],
            ],
        ]);

        $component = Livewire::test(SettingChangeHistory::class);
        
        $this->assertTrue($component->get('notificationSettings.email_enabled'));
        $this->assertFalse($component->get('notificationSettings.important_only'));
        $this->assertContains('basic', $component->get('notificationSettings.categories'));
    }

    /** @test */
    public function it_requires_authentication()
    {
        auth()->logout();

        Livewire::test(SettingChangeHistory::class)
            ->assertStatus(302); // 重導向到登入頁面
    }

    /** @test */
    public function it_handles_missing_change_record_gracefully()
    {
        Livewire::test(SettingChangeHistory::class)
            ->call('showDetails', 99999) // 不存在的 ID
            ->assertSet('selectedChange', null)
            ->assertSet('showDetailsModal', false);
    }

    /** @test */
    public function it_handles_restore_without_selected_change()
    {
        Livewire::test(SettingChangeHistory::class)
            ->set('selectedChange', null)
            ->call('executeRestore')
            ->assertHasErrors(); // 應該有錯誤訊息
    }

    /** @test */
    public function it_formats_export_values_correctly()
    {
        $component = new SettingChangeHistory();

        // 測試 null 值
        $this->assertEquals('(null)', $component->formatValueForExport(null));

        // 測試布林值
        $this->assertEquals('true', $component->formatValueForExport(true));
        $this->assertEquals('false', $component->formatValueForExport(false));

        // 測試陣列值
        $arrayValue = ['key' => 'value'];
        $result = $component->formatValueForExport($arrayValue);
        $this->assertJson($result);

        // 測試字串值
        $this->assertEquals('test', $component->formatValueForExport('test'));
    }
}