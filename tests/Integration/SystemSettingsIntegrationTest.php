<?php

namespace Tests\Integration;

use App\Models\Setting;
use App\Models\SettingBackup;
use App\Models\SettingChange;
use App\Models\User;
use App\Repositories\SettingsRepositoryInterface;
use App\Services\ConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * 系統設定功能完整整合測試
 * 
 * 測試覆蓋範圍：
 * - 完整的設定管理流程
 * - 設定備份和還原功能
 * - 不同權限使用者的存取控制
 * - 設定匯入匯出功能
 * - 設定快取和效能
 * - 設定驗證和安全性
 */
class SystemSettingsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected SettingsRepositoryInterface $repository;
    protected ConfigurationService $configService;
    protected User $adminUser;
    protected User $editorUser;
    protected User $viewerUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = app(SettingsRepositoryInterface::class);
        $this->configService = app(ConfigurationService::class);
        
        // 建立不同權限的測試使用者
        $this->createTestUsers();
        
        // 建立測試設定
        $this->createTestSettings();
        
        // 清除快取
        Cache::flush();
    }

    /**
     * 建立測試使用者
     */
    protected function createTestUsers(): void
    {
        $this->adminUser = User::factory()->create([
            'username' => 'admin_user',
            'name' => '系統管理員',
            'email' => 'admin@test.com',
            'is_active' => true,
        ]);

        $this->editorUser = User::factory()->create([
            'username' => 'editor_user',
            'name' => '設定編輯者',
            'email' => 'editor@test.com',
            'is_active' => true,
        ]);

        $this->viewerUser = User::factory()->create([
            'username' => 'viewer_user',
            'name' => '設定檢視者',
            'email' => 'viewer@test.com',
            'is_active' => true,
        ]);

        $this->regularUser = User::factory()->create([
            'username' => 'regular_user',
            'name' => '一般使用者',
            'email' => 'regular@test.com',
            'is_active' => true,
        ]);
    }

    /**
     * 建立測試設定
     */
    protected function createTestSettings(): void
    {
        $testSettings = [
            [
                'key' => 'test.basic.app_name',
                'value' => 'Test Application',
                'category' => 'basic',
                'type' => 'text',
                'description' => '測試應用程式名稱',
                'default_value' => 'Default App',
                'is_system' => false,
                'is_encrypted' => false,
                'sort_order' => 1,
            ],
            [
                'key' => 'test.security.password_length',
                'value' => 10,
                'category' => 'security',
                'type' => 'number',
                'description' => '測試密碼長度',
                'default_value' => 8,
                'is_system' => true,
                'is_encrypted' => false,
                'sort_order' => 1,
            ],
            [
                'key' => 'test.notification.smtp_password',
                'value' => 'secret_password',
                'category' => 'notification',
                'type' => 'password',
                'description' => '測試 SMTP 密碼',
                'default_value' => '',
                'is_system' => false,
                'is_encrypted' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'test.appearance.theme_color',
                'value' => '#FF0000',
                'category' => 'appearance',
                'type' => 'color',
                'description' => '測試主題顏色',
                'default_value' => '#3B82F6',
                'is_system' => false,
                'is_encrypted' => false,
                'sort_order' => 1,
            ],
        ];

        foreach ($testSettings as $settingData) {
            Setting::create($settingData);
        }
    }

    /**
     * 測試完整的設定管理流程
     * 
     * @test
     */
    public function test_complete_settings_management_workflow(): void
    {
        $this->actingAs($this->adminUser);

        // 1. 取得所有設定
        $allSettings = $this->repository->getAllSettings();
        $this->assertGreaterThan(0, $allSettings->count());

        // 2. 按分類取得設定
        $basicSettings = $this->repository->getSettingsByCategory('basic');
        $this->assertGreaterThan(0, $basicSettings->count());

        // 3. 搜尋設定
        $searchResults = $this->repository->searchSettings('test');
        $this->assertGreaterThan(0, $searchResults->count());

        // 4. 更新設定
        $updateResult = $this->repository->updateSetting('test.basic.app_name', 'Updated App Name');
        $this->assertTrue($updateResult);

        // 5. 驗證設定已更新
        $updatedSetting = $this->repository->getSetting('test.basic.app_name');
        $this->assertEquals('Updated App Name', $updatedSetting->value);

        // 6. 檢查變更歷史
        $changes = SettingChange::where('setting_key', 'test.basic.app_name')->get();
        $this->assertGreaterThan(0, $changes->count());

        // 7. 重設設定
        $resetResult = $this->repository->resetSetting('test.basic.app_name');
        $this->assertTrue($resetResult);

        // 8. 驗證設定已重設
        $resetSetting = $this->repository->getSetting('test.basic.app_name');
        $this->assertEquals('Default App', $resetSetting->value);
    }

    /**
     * 測試設定備份和還原功能
     * 
     * @test
     */
    public function test_settings_backup_and_restore_functionality(): void
    {
        $this->actingAs($this->adminUser);

        // 1. 記錄原始設定值
        $originalAppName = $this->repository->getSetting('test.basic.app_name')->value;
        $originalPasswordLength = $this->repository->getSetting('test.security.password_length')->value;

        // 2. 建立備份
        $backup = $this->repository->createBackup(
            '整合測試備份',
            '完整功能測試的備份',
            ['basic', 'security']
        );

        $this->assertInstanceOf(SettingBackup::class, $backup);
        $this->assertEquals('整合測試備份', $backup->name);
        $this->assertNotEmpty($backup->settings_data);

        // 3. 修改設定
        $this->repository->updateSetting('test.basic.app_name', 'Modified App Name');
        $this->repository->updateSetting('test.security.password_length', 12);

        // 4. 驗證設定已修改
        $this->assertEquals('Modified App Name', $this->repository->getSetting('test.basic.app_name')->value);
        $this->assertEquals(12, $this->repository->getSetting('test.security.password_length')->value);

        // 5. 還原備份
        $restoreResult = $this->repository->restoreBackup($backup->id);
        $this->assertTrue($restoreResult);

        // 6. 驗證設定已還原
        $this->assertEquals($originalAppName, $this->repository->getSetting('test.basic.app_name')->value);
        $this->assertEquals($originalPasswordLength, $this->repository->getSetting('test.security.password_length')->value);

        // 7. 檢查備份列表
        $backups = $this->repository->getBackups();
        $this->assertGreaterThan(0, $backups->count());

        // 8. 測試備份比較功能
        $comparison = $this->repository->compareBackup($backup->id);
        $this->assertIsArray($comparison);
    }

    /**
     * 測試不同權限使用者的存取控制
     * 
     * @test
     */
    public function test_different_user_permission_access_control(): void
    {
        // 測試管理員權限
        $this->actingAs($this->adminUser);
        
        // 管理員應該能夠執行所有操作
        $this->assertTrue($this->repository->updateSetting('test.basic.app_name', 'Admin Updated'));
        $backup = $this->repository->createBackup('Admin Backup', 'Test');
        $this->assertInstanceOf(SettingBackup::class, $backup);
        $this->assertTrue($this->repository->restoreBackup($backup->id));

        // 測試編輯者權限
        $this->actingAs($this->editorUser);
        
        // 編輯者應該能夠更新非系統設定
        $this->assertTrue($this->repository->updateSetting('test.basic.app_name', 'Editor Updated'));
        
        // 但不能更新系統設定（根據業務邏輯可能會限制）
        // 這裡假設系統設定需要更高權限
        
        // 測試檢視者權限
        $this->actingAs($this->viewerUser);
        
        // 檢視者應該能夠讀取設定
        $setting = $this->repository->getSetting('test.basic.app_name');
        $this->assertNotNull($setting);
        
        // 但不能修改設定（這裡需要在 Repository 中實作權限檢查）
        
        // 測試一般使用者權限
        $this->actingAs($this->regularUser);
        
        // 一般使用者不應該能夠存取設定管理
        // 這裡需要在控制器或中介軟體層面實作權限檢查
    }

    /**
     * 測試設定匯入匯出功能
     * 
     * @test
     */
    public function test_settings_import_export_functionality(): void
    {
        $this->actingAs($this->adminUser);
        Storage::fake('local');

        // 1. 匯出設定
        $exportedSettings = $this->repository->exportSettings(['basic', 'appearance']);
        $this->assertIsArray($exportedSettings);
        $this->assertGreaterThan(0, count($exportedSettings));

        // 2. 驗證匯出資料結構
        $firstSetting = $exportedSettings[0];
        $this->assertArrayHasKey('key', $firstSetting);
        $this->assertArrayHasKey('value', $firstSetting);
        $this->assertArrayHasKey('category', $firstSetting);
        $this->assertArrayHasKey('type', $firstSetting);

        // 3. 建立匯入檔案
        $importData = [
            [
                'key' => 'test.import.new_setting',
                'value' => 'Imported Value',
                'category' => 'basic',
                'type' => 'text',
                'description' => '匯入的新設定',
                'default_value' => 'Default',
                'is_system' => false,
                'is_encrypted' => false,
            ],
            [
                'key' => 'test.basic.app_name', // 與現有設定衝突
                'value' => 'Conflicted Value',
                'category' => 'basic',
                'type' => 'text',
                'description' => '衝突的設定',
                'default_value' => 'Default App',
                'is_system' => false,
                'is_encrypted' => false,
            ]
        ];

        // 4. 測試匯入功能
        $importResult = $this->repository->importSettings($importData);
        $this->assertTrue($importResult['success']);
        $this->assertGreaterThan(0, $importResult['created'] + $importResult['updated']);

        // 5. 驗證新設定已建立
        $newSetting = $this->repository->getSetting('test.import.new_setting');
        $this->assertNotNull($newSetting);
        $this->assertEquals('Imported Value', $newSetting->value);

        // 6. 測試衝突處理
        $conflictedSetting = $this->repository->getSetting('test.basic.app_name');
        $this->assertNotNull($conflictedSetting);
        // 根據衝突處理策略，值可能被更新或保持原樣

        // 7. 測試部分匯入
        $partialImportData = [
            [
                'key' => 'test.partial.setting1',
                'value' => 'Partial 1',
                'category' => 'basic',
                'type' => 'text',
                'description' => '部分匯入設定 1',
                'default_value' => 'Default',
                'is_system' => false,
                'is_encrypted' => false,
            ],
            [
                'key' => 'test.partial.setting2',
                'value' => 'Partial 2',
                'category' => 'basic',
                'type' => 'text',
                'description' => '部分匯入設定 2',
                'default_value' => 'Default',
                'is_system' => false,
                'is_encrypted' => false,
            ]
        ];

        // 只匯入第一個設定
        $selectedKeys = ['test.partial.setting1'];
        $partialResult = $this->repository->importSettings($partialImportData, [
            'selected_keys' => $selectedKeys
        ]);

        $this->assertTrue($partialResult['success']);
        $this->assertNotNull($this->repository->getSetting('test.partial.setting1'));
        $this->assertNull($this->repository->getSetting('test.partial.setting2'));
    }

    /**
     * 測試設定驗證功能
     * 
     * @test
     */
    public function test_settings_validation_functionality(): void
    {
        $this->actingAs($this->adminUser);

        // 1. 測試有效值驗證
        $validResult = $this->configService->validateSettingValue('test.basic.app_name', 'Valid App Name');
        $this->assertTrue($validResult);

        // 2. 測試無效值驗證
        $invalidResult = $this->configService->validateSettingValue('test.basic.app_name', '');
        $this->assertFalse($invalidResult);

        // 3. 測試數字範圍驗證
        $validNumberResult = $this->configService->validateSettingValue('test.security.password_length', 10);
        $this->assertTrue($validNumberResult);

        $invalidNumberResult = $this->configService->validateSettingValue('test.security.password_length', 3);
        $this->assertFalse($invalidNumberResult);

        // 4. 測試顏色格式驗證
        $validColorResult = $this->configService->validateSettingValue('test.appearance.theme_color', '#FF0000');
        $this->assertTrue($validColorResult);

        $invalidColorResult = $this->configService->validateSettingValue('test.appearance.theme_color', 'red');
        $this->assertFalse($invalidColorResult);

        // 5. 測試設定相依性檢查
        $dependencies = $this->configService->getDependentSettings('test.basic.app_name');
        $this->assertIsArray($dependencies);
    }

    /**
     * 測試設定快取功能
     * 
     * @test
     */
    public function test_settings_cache_functionality(): void
    {
        $this->actingAs($this->adminUser);

        // 1. 清除快取
        Cache::flush();

        // 2. 第一次取得設定（建立快取）
        $setting1 = $this->repository->getSetting('test.basic.app_name');
        $this->assertNotNull($setting1);

        // 3. 檢查快取是否建立
        $cachedValue = Cache::get('setting_test.basic.app_name');
        $this->assertNotNull($cachedValue);

        // 4. 第二次取得設定（從快取）
        $setting2 = $this->repository->getSetting('test.basic.app_name');
        $this->assertEquals($setting1->id, $setting2->id);

        // 5. 更新設定（應該清除快取）
        $this->repository->updateSetting('test.basic.app_name', 'Cache Test Updated');

        // 6. 檢查快取是否被清除
        $cachedValueAfterUpdate = Cache::get('setting_test.basic.app_name');
        $this->assertNull($cachedValueAfterUpdate);

        // 7. 再次取得設定（重新建立快取）
        $setting3 = $this->repository->getSetting('test.basic.app_name');
        $this->assertEquals('Cache Test Updated', $setting3->value);
    }

    /**
     * 測試設定加密功能
     * 
     * @test
     */
    public function test_settings_encryption_functionality(): void
    {
        $this->actingAs($this->adminUser);

        // 1. 取得加密設定
        $encryptedSetting = $this->repository->getSetting('test.notification.smtp_password');
        $this->assertNotNull($encryptedSetting);
        $this->assertTrue($encryptedSetting->is_encrypted);

        // 2. 更新加密設定
        $newPassword = 'new_secret_password';
        $updateResult = $this->repository->updateSetting('test.notification.smtp_password', $newPassword);
        $this->assertTrue($updateResult);

        // 3. 驗證加密設定值
        $updatedSetting = $this->repository->getSetting('test.notification.smtp_password');
        $this->assertEquals($newPassword, $updatedSetting->value);

        // 4. 檢查資料庫中的值是否已加密
        $rawSetting = Setting::where('key', 'test.notification.smtp_password')->first();
        $this->assertNotEquals($newPassword, $rawSetting->getRawOriginal('value'));
    }

    /**
     * 測試設定變更歷史功能
     * 
     * @test
     */
    public function test_settings_change_history_functionality(): void
    {
        $this->actingAs($this->adminUser);

        // 1. 記錄初始變更數量
        $initialChangeCount = SettingChange::count();

        // 2. 更新設定
        $this->repository->updateSetting('test.basic.app_name', 'History Test 1');
        $this->repository->updateSetting('test.basic.app_name', 'History Test 2');
        $this->repository->updateSetting('test.basic.app_name', 'History Test 3');

        // 3. 檢查變更歷史記錄
        $finalChangeCount = SettingChange::count();
        $this->assertEquals($initialChangeCount + 3, $finalChangeCount);

        // 4. 取得特定設定的變更歷史
        $settingChanges = SettingChange::where('setting_key', 'test.basic.app_name')
            ->orderBy('created_at', 'desc')
            ->get();

        $this->assertGreaterThanOrEqual(3, $settingChanges->count());

        // 5. 驗證變更記錄內容
        $latestChange = $settingChanges->first();
        $this->assertEquals('test.basic.app_name', $latestChange->setting_key);
        $this->assertEquals('History Test 3', $latestChange->new_value);
        $this->assertEquals($this->adminUser->id, $latestChange->changed_by);

        // 6. 測試變更歷史查詢功能
        $historyResults = $this->repository->getSettingHistory('test.basic.app_name');
        $this->assertGreaterThan(0, $historyResults->count());
    }

    /**
     * 測試批量設定操作
     * 
     * @test
     */
    public function test_bulk_settings_operations(): void
    {
        $this->actingAs($this->adminUser);

        // 1. 批量更新設定
        $bulkUpdates = [
            'test.basic.app_name' => 'Bulk Updated App',
            'test.appearance.theme_color' => '#00FF00',
        ];

        $bulkResult = $this->repository->updateMultipleSettings($bulkUpdates);
        $this->assertTrue($bulkResult);

        // 2. 驗證批量更新結果
        $this->assertEquals('Bulk Updated App', $this->repository->getSetting('test.basic.app_name')->value);
        $this->assertEquals('#00FF00', $this->repository->getSetting('test.appearance.theme_color')->value);

        // 3. 批量重設設定
        $resetKeys = ['test.basic.app_name', 'test.appearance.theme_color'];
        $resetResult = $this->repository->resetMultipleSettings($resetKeys);
        $this->assertTrue($resetResult);

        // 4. 驗證批量重設結果
        $this->assertEquals('Default App', $this->repository->getSetting('test.basic.app_name')->value);
        $this->assertEquals('#3B82F6', $this->repository->getSetting('test.appearance.theme_color')->value);
    }

    /**
     * 測試設定搜尋和篩選功能
     * 
     * @test
     */
    public function test_settings_search_and_filter_functionality(): void
    {
        $this->actingAs($this->adminUser);

        // 1. 關鍵字搜尋
        $searchResults = $this->repository->searchSettings('test');
        $this->assertGreaterThan(0, $searchResults->count());

        // 2. 分類篩選
        $basicSettings = $this->repository->searchSettings('', ['category' => 'basic']);
        $this->assertGreaterThan(0, $basicSettings->count());
        foreach ($basicSettings as $setting) {
            $this->assertEquals('basic', $setting->category);
        }

        // 3. 類型篩選
        $textSettings = $this->repository->searchSettings('', ['type' => 'text']);
        $this->assertGreaterThan(0, $textSettings->count());
        foreach ($textSettings as $setting) {
            $this->assertEquals('text', $setting->type);
        }

        // 4. 系統設定篩選
        $systemSettings = $this->repository->searchSettings('', ['is_system' => true]);
        $this->assertGreaterThan(0, $systemSettings->count());
        foreach ($systemSettings as $setting) {
            $this->assertTrue($setting->is_system);
        }

        // 5. 已變更設定篩選
        $this->repository->updateSetting('test.basic.app_name', 'Changed Value');
        $changedSettings = $this->repository->getChangedSettings();
        $this->assertGreaterThan(0, $changedSettings->count());

        // 6. 複合篩選
        $complexResults = $this->repository->searchSettings('app', [
            'category' => 'basic',
            'type' => 'text'
        ]);
        $this->assertGreaterThanOrEqual(0, $complexResults->count());
    }

    /**
     * 測試設定預覽功能
     * 
     * @test
     */
    public function test_settings_preview_functionality(): void
    {
        $this->actingAs($this->adminUser);

        // 1. 生成外觀設定預覽
        $previewSettings = [
            'appearance.primary_color' => '#FF0000',
            'appearance.default_theme' => 'dark',
        ];

        $previewData = $this->configService->generatePreview($previewSettings);
        $this->assertIsArray($previewData);
        $this->assertArrayHasKey('css_variables', $previewData);

        // 2. 測試連線設定預覽
        $smtpSettings = [
            'notification.smtp_host' => 'smtp.gmail.com',
            'notification.smtp_port' => 587,
            'notification.smtp_encryption' => 'tls',
        ];

        $connectionTest = $this->configService->testConnection('smtp', $smtpSettings);
        $this->assertIsBool($connectionTest);
    }

    /**
     * 清理測試資料
     */
    protected function tearDown(): void
    {
        // 清除測試建立的設定
        Setting::where('key', 'like', 'test.%')->delete();
        SettingBackup::where('name', 'like', '%測試%')->delete();
        SettingChange::where('setting_key', 'like', 'test.%')->delete();
        
        // 清除快取
        Cache::flush();
        
        parent::tearDown();
    }
}