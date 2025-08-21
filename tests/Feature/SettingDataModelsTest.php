<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Setting;
use App\Models\SettingBackup;
use App\Models\SettingChange;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SettingDataModelsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試使用者
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /**
     * 測試設定資料模型的完整流程
     */
    public function test_complete_setting_data_model_workflow(): void
    {
        // 1. 建立設定
        $setting = Setting::create([
            'key' => 'app.test_setting',
            'value' => 'initial_value',
            'category' => 'test',
            'type' => 'text',
            'description' => '測試設定項目',
        ]);

        $this->assertDatabaseHas('settings', [
            'key' => 'app.test_setting',
            'value' => json_encode('initial_value'),
            'category' => 'test',
        ]);

        // 2. 更新設定值（這應該觸發觀察者記錄變更）
        $setting->updateValue('updated_value');

        $this->assertDatabaseHas('setting_changes', [
            'setting_key' => 'app.test_setting',
            'old_value' => json_encode('initial_value'),
            'new_value' => json_encode('updated_value'),
            'changed_by' => $this->user->id,
        ]);

        // 3. 建立設定備份
        $settingsData = Setting::all()->toArray();
        $backup = SettingBackup::create([
            'name' => '測試備份',
            'description' => '完整流程測試備份',
            'settings_data' => $settingsData,
            'created_by' => $this->user->id,
            'backup_type' => 'manual',
            'settings_count' => count($settingsData),
        ]);

        $this->assertDatabaseHas('setting_backups', [
            'name' => '測試備份',
            'created_by' => $this->user->id,
            'backup_type' => 'manual',
            'settings_count' => count($settingsData),
        ]);

        // 4. 測試備份還原功能
        $setting->updateValue('before_restore');
        $this->assertEquals('before_restore', $setting->fresh()->value);

        $restoreResult = $backup->restore();
        $this->assertTrue($restoreResult);
        
        // 驗證設定值已還原
        $this->assertEquals('updated_value', $setting->fresh()->value);

        // 5. 測試設定關聯關係
        $changes = $setting->changes;
        $this->assertGreaterThan(0, $changes->count());

        $backups = $setting->backups;
        $this->assertGreaterThanOrEqual(0, $backups->count());
    }

    /**
     * 測試加密設定的完整流程
     */
    public function test_encrypted_setting_workflow(): void
    {
        // 建立加密設定
        $setting = Setting::create([
            'key' => 'security.api_key',
            'value' => 'super-secret-api-key',
            'category' => 'security',
            'type' => 'api_key',
            'is_encrypted' => true,
        ]);

        // 驗證值已加密儲存
        $rawValue = $setting->getAttributes()['value'];
        $this->assertNotEquals('super-secret-api-key', $rawValue);

        // 驗證可以正確解密
        $this->assertEquals('super-secret-api-key', $setting->value);

        // 測試加密設定的備份和還原
        $backup = SettingBackup::create([
            'name' => '加密設定備份',
            'settings_data' => [$setting->toArray()],
            'created_by' => $this->user->id,
            'settings_count' => 1,
        ]);

        // 修改設定值
        $setting->updateValue('new-secret-key');
        $this->assertEquals('new-secret-key', $setting->fresh()->value);

        // 還原備份
        $backup->restore();
        $this->assertEquals('super-secret-api-key', $setting->fresh()->value);
    }

    /**
     * 測試設定變更記錄的詳細資訊
     */
    public function test_setting_change_detailed_logging(): void
    {
        $setting = Setting::create([
            'key' => 'test.logging',
            'value' => 'original',
            'category' => 'test',
        ]);

        // 更新設定
        $setting->updateValue('modified');

        // 檢查變更記錄
        $change = SettingChange::where('setting_key', 'test.logging')->first();
        
        $this->assertNotNull($change);
        $this->assertEquals('original', $change->old_value);
        $this->assertEquals('modified', $change->new_value);
        $this->assertEquals($this->user->id, $change->changed_by);
        $this->assertNotNull($change->ip_address);

        // 測試變更摘要
        $this->assertStringContainsString('original', $change->change_summary);
        $this->assertStringContainsString('modified', $change->change_summary);
    }

    /**
     * 測試設定備份的比較功能
     */
    public function test_setting_backup_comparison(): void
    {
        // 建立初始設定
        $setting1 = Setting::create(['key' => 'test.setting1', 'value' => 'value1', 'category' => 'test']);
        $setting2 = Setting::create(['key' => 'test.setting2', 'value' => 'value2', 'category' => 'test']);

        // 建立備份
        $backup = SettingBackup::create([
            'name' => '比較測試備份',
            'settings_data' => Setting::all()->toArray(),
            'created_by' => $this->user->id,
            'settings_count' => 2,
        ]);

        // 修改設定
        $setting1->updateValue('modified_value1');
        $setting2->delete();
        Setting::create(['key' => 'test.setting3', 'value' => 'value3', 'category' => 'test']);

        // 比較差異
        $differences = $backup->compare();

        $this->assertArrayHasKey('modified', $differences);
        $this->assertArrayHasKey('removed', $differences);
        $this->assertArrayHasKey('added', $differences);
        $this->assertArrayHasKey('unchanged', $differences);

        // 驗證修改的設定
        $this->assertCount(1, $differences['modified']);
        $this->assertEquals('test.setting1', $differences['modified'][0]['key']);

        // 驗證新增的設定
        $this->assertCount(1, $differences['added']);
        $this->assertEquals('test.setting3', $differences['added'][0]['key']);

        // 驗證刪除的設定
        $this->assertCount(1, $differences['removed']);
        $this->assertEquals('test.setting2', $differences['removed'][0]['key']);
    }

    /**
     * 測試設定的範圍查詢功能
     */
    public function test_setting_scope_queries(): void
    {
        // 建立不同類型的設定
        Setting::create(['key' => 'basic.setting1', 'category' => 'basic', 'is_public' => true]);
        Setting::create(['key' => 'security.setting1', 'category' => 'security', 'is_system' => true]);
        Setting::create(['key' => 'user.setting1', 'category' => 'user', 'is_public' => false, 'is_system' => false]);

        // 測試分類查詢
        $basicSettings = Setting::byCategory('basic')->get();
        $this->assertCount(1, $basicSettings);

        // 測試公開設定查詢
        $publicSettings = Setting::public()->get();
        $this->assertCount(1, $publicSettings);

        // 測試系統設定查詢
        $systemSettings = Setting::system()->get();
        $this->assertCount(1, $systemSettings);
    }
}