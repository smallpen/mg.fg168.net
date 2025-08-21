<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Repositories\SettingsRepositoryInterface;
use App\Services\ConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected SettingsRepositoryInterface $repository;
    protected ConfigurationService $configService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = app(SettingsRepositoryInterface::class);
        $this->configService = app(ConfigurationService::class);
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_settings_repository_is_properly_bound(): void
    {
        $this->assertInstanceOf(SettingsRepositoryInterface::class, $this->repository);
    }

    public function test_configuration_service_is_properly_bound(): void
    {
        $this->assertInstanceOf(ConfigurationService::class, $this->configService);
    }

    public function test_can_create_and_retrieve_setting(): void
    {
        // 建立設定
        $setting = $this->repository->createSetting([
            'key' => 'test.integration',
            'value' => 'integration_value',
            'category' => 'test',
            'type' => 'text',
            'description' => 'Integration test setting',
            'default_value' => 'default_value',
            'is_system' => false,
            'is_public' => true,
            'sort_order' => 1,
        ]);

        $this->assertInstanceOf(Setting::class, $setting);
        $this->assertEquals('test.integration', $setting->key);

        // 取得設定
        $retrieved = $this->repository->getSetting('test.integration');
        $this->assertNotNull($retrieved);
        $this->assertEquals('integration_value', $retrieved->value);
    }

    public function test_can_update_setting_with_validation(): void
    {
        // 建立有驗證規則的設定
        $setting = $this->repository->createSetting([
            'key' => 'test.validated',
            'value' => 50,
            'category' => 'test',
            'type' => 'number',
            'options' => [
                'validation' => 'integer|min:1|max:100'
            ],
            'description' => 'Validated setting',
            'default_value' => 50,
        ]);

        // 測試有效值更新
        $result = $this->repository->updateSetting('test.validated', 75);
        $this->assertTrue($result);
        $this->assertEquals(75, $setting->fresh()->value);

        // 測試無效值更新
        $result = $this->repository->updateSetting('test.validated', 150);
        $this->assertFalse($result);
        $this->assertEquals(75, $setting->fresh()->value); // 值應該沒有變更
    }

    public function test_can_backup_and_restore_settings(): void
    {
        // 建立測試設定
        $setting1 = $this->repository->createSetting([
            'key' => 'backup.test1',
            'value' => 'original1',
            'category' => 'backup',
            'type' => 'text',
        ]);

        $setting2 = $this->repository->createSetting([
            'key' => 'backup.test2',
            'value' => 'original2',
            'category' => 'backup',
            'type' => 'text',
        ]);

        // 建立備份
        $backup = $this->repository->createBackup('Integration Test Backup', 'Test backup', ['backup']);
        $this->assertNotNull($backup);
        $this->assertEquals('Integration Test Backup', $backup->name);

        // 修改設定
        $this->repository->updateSetting('backup.test1', 'modified1');
        $this->repository->updateSetting('backup.test2', 'modified2');

        $this->assertEquals('modified1', $setting1->fresh()->value);
        $this->assertEquals('modified2', $setting2->fresh()->value);

        // 還原備份
        $result = $this->repository->restoreBackup($backup->id);
        $this->assertTrue($result);

        // 檢查設定是否還原
        $this->assertEquals('original1', $setting1->fresh()->value);
        $this->assertEquals('original2', $setting2->fresh()->value);
    }

    public function test_configuration_service_validates_correctly(): void
    {
        // 測試文字設定驗證
        $this->assertTrue($this->configService->validateSettingValue('app.name', 'Valid App Name'));
        $this->assertFalse($this->configService->validateSettingValue('app.name', ''));

        // 測試數字設定驗證
        $this->assertTrue($this->configService->validateSettingValue('security.password_min_length', 8));
        $this->assertFalse($this->configService->validateSettingValue('security.password_min_length', 5));

        // 測試顏色設定驗證
        $this->assertTrue($this->configService->validateSettingValue('theme.primary_color', '#FF0000'));
        $this->assertFalse($this->configService->validateSettingValue('theme.primary_color', 'red'));
    }

    public function test_can_search_and_filter_settings(): void
    {
        // 建立測試設定
        $this->repository->createSetting([
            'key' => 'search.app_name',
            'value' => 'My App',
            'category' => 'basic',
            'type' => 'text',
            'description' => 'Application name setting',
        ]);

        $this->repository->createSetting([
            'key' => 'search.app_version',
            'value' => '1.0.0',
            'category' => 'basic',
            'type' => 'text',
            'description' => 'Application version setting',
        ]);

        $this->repository->createSetting([
            'key' => 'search.mail_host',
            'value' => 'smtp.gmail.com',
            'category' => 'notification',
            'type' => 'text',
            'description' => 'Mail server host',
        ]);

        // 搜尋測試
        $results = $this->repository->searchSettings('app');
        $this->assertCount(2, $results);

        // 分類篩選測試
        $results = $this->repository->searchSettings('', ['category' => 'basic']);
        $this->assertCount(2, $results);

        // 類型篩選測試
        $results = $this->repository->searchSettings('', ['type' => 'text']);
        $this->assertCount(3, $results);
    }

    public function test_can_export_and_import_settings(): void
    {
        // 建立測試設定
        $this->repository->createSetting([
            'key' => 'export.setting1',
            'value' => 'value1',
            'category' => 'export',
            'type' => 'text',
        ]);

        $this->repository->createSetting([
            'key' => 'export.setting2',
            'value' => 'value2',
            'category' => 'export',
            'type' => 'text',
        ]);

        // 匯出設定
        $exported = $this->repository->exportSettings(['export']);
        $this->assertCount(2, $exported);

        // 刪除設定
        $this->repository->deleteSetting('export.setting1');
        $this->repository->deleteSetting('export.setting2');

        // 匯入設定
        $result = $this->repository->importSettings($exported);
        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['created']);

        // 檢查設定是否匯入成功
        $setting1 = $this->repository->getSetting('export.setting1');
        $setting2 = $this->repository->getSetting('export.setting2');
        
        $this->assertNotNull($setting1);
        $this->assertNotNull($setting2);
        $this->assertEquals('value1', $setting1->value);
        $this->assertEquals('value2', $setting2->value);
    }

    public function test_settings_cache_integration(): void
    {
        // 建立設定
        $setting = $this->repository->createSetting([
            'key' => 'cache.integration',
            'value' => 'cached_value',
            'category' => 'test',
            'type' => 'text',
        ]);

        // 第一次取得（建立快取）
        $result1 = $this->repository->getSetting('cache.integration');
        
        // 第二次取得（從快取）
        $result2 = $this->repository->getSetting('cache.integration');
        
        $this->assertEquals($result1->id, $result2->id);

        // 更新設定（應該清除快取）
        $this->repository->updateSetting('cache.integration', 'new_value');
        
        // 再次取得（應該是新值）
        $result3 = $this->repository->getSetting('cache.integration');
        $this->assertEquals('new_value', $result3->value);
    }
}
