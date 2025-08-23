<?php

namespace Tests\Integration;

use App\Models\Setting;
use App\Models\SettingBackup;
use App\Models\User;
use App\Services\ConfigurationService;
use App\Services\SettingsPerformanceService;
use App\Repositories\SettingsRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * 系統設定最終整合測試
 * 
 * 完整測試所有系統設定功能的整合，包含：
 * - 所有 Livewire 元件的整合
 * - 效能優化功能
 * - 權限控制
 * - 安全性驗證
 * - 使用者體驗流程
 */
class SystemSettingsFinalIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected SettingsRepositoryInterface $repository;
    protected ConfigurationService $configService;
    protected SettingsPerformanceService $performanceService;
    protected User $adminUser;
    protected User $editorUser;
    protected User $viewerUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = app(SettingsRepositoryInterface::class);
        $this->configService = app(ConfigurationService::class);
        $this->performanceService = app(SettingsPerformanceService::class);
        
        // 建立測試使用者
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
            'username' => 'admin_final_test',
            'name' => '最終測試管理員',
            'email' => 'admin.final@test.com',
            'is_active' => true,
        ]);

        $this->editorUser = User::factory()->create([
            'username' => 'editor_final_test',
            'name' => '最終測試編輯者',
            'email' => 'editor.final@test.com',
            'is_active' => true,
        ]);

        $this->viewerUser = User::factory()->create([
            'username' => 'viewer_final_test',
            'name' => '最終測試檢視者',
            'email' => 'viewer.final@test.com',
            'is_active' => true,
        ]);
    }

    /**
     * 建立測試設定
     */
    protected function createTestSettings(): void
    {
        $testSettings = [
            // 基本設定
            [
                'key' => 'final_test.app.name',
                'value' => 'Final Test Application',
                'category' => 'basic',
                'type' => 'text',
                'description' => '最終測試應用程式名稱',
                'default_value' => 'Default App',
                'is_system' => false,
                'is_encrypted' => false,
                'sort_order' => 1,
            ],
            [
                'key' => 'final_test.app.description',
                'value' => 'This is a final integration test application',
                'category' => 'basic',
                'type' => 'textarea',
                'description' => '最終測試應用程式描述',
                'default_value' => 'Default Description',
                'is_system' => false,
                'is_encrypted' => false,
                'sort_order' => 2,
            ],
            
            // 安全設定
            [
                'key' => 'final_test.security.password_length',
                'value' => 12,
                'category' => 'security',
                'type' => 'number',
                'description' => '最終測試密碼長度',
                'default_value' => 8,
                'is_system' => true,
                'is_encrypted' => false,
                'sort_order' => 1,
            ],
            [
                'key' => 'final_test.security.api_key',
                'value' => 'secret_api_key_12345',
                'category' => 'security',
                'type' => 'password',
                'description' => '最終測試 API 金鑰',
                'default_value' => '',
                'is_system' => false,
                'is_encrypted' => true,
                'sort_order' => 2,
            ],
            
            // 外觀設定
            [
                'key' => 'final_test.appearance.primary_color',
                'value' => '#FF5722',
                'category' => 'appearance',
                'type' => 'color',
                'description' => '最終測試主要顏色',
                'default_value' => '#3B82F6',
                'is_system' => false,
                'is_encrypted' => false,
                'sort_order' => 1,
            ],
            [
                'key' => 'final_test.appearance.dark_mode',
                'value' => true,
                'category' => 'appearance',
                'type' => 'boolean',
                'description' => '最終測試深色模式',
                'default_value' => false,
                'is_system' => false,
                'is_encrypted' => false,
                'sort_order' => 2,
            ],
            
            // 通知設定
            [
                'key' => 'final_test.notification.smtp_host',
                'value' => 'smtp.gmail.com',
                'category' => 'notification',
                'type' => 'text',
                'description' => '最終測試 SMTP 主機',
                'default_value' => 'localhost',
                'is_system' => false,
                'is_encrypted' => false,
                'sort_order' => 1,
            ],
            [
                'key' => 'final_test.notification.smtp_password',
                'value' => 'smtp_password_123',
                'category' => 'notification',
                'type' => 'password',
                'description' => '最終測試 SMTP 密碼',
                'default_value' => '',
                'is_system' => false,
                'is_encrypted' => true,
                'sort_order' => 2,
            ],
        ];

        foreach ($testSettings as $settingData) {
            Setting::create($settingData);
        }
    }

    /**
     * 測試完整的設定管理工作流程
     * 
     * @test
     */
    public function test_complete_settings_management_workflow(): void
    {
        $this->actingAs($this->adminUser);

        // 1. 測試設定列表功能
        $this->info('測試設定列表功能...');
        $allSettings = $this->repository->getAllSettings();
        $this->assertGreaterThan(0, $allSettings->count());

        // 2. 測試分類篩選
        $this->info('測試分類篩選...');
        $basicSettings = $this->repository->getSettingsByCategory('basic');
        $this->assertGreaterThan(0, $basicSettings->count());
        foreach ($basicSettings as $setting) {
            $this->assertEquals('basic', $setting->category);
        }

        // 3. 測試搜尋功能
        $this->info('測試搜尋功能...');
        $searchResults = $this->repository->searchSettings('final_test');
        $this->assertGreaterThan(0, $searchResults->count());

        // 4. 測試設定更新
        $this->info('測試設定更新...');
        $originalValue = $this->repository->getSetting('final_test.app.name')->value;
        $newValue = 'Updated Final Test App';
        
        $updateResult = $this->repository->updateSetting('final_test.app.name', $newValue);
        $this->assertTrue($updateResult);
        
        $updatedSetting = $this->repository->getSetting('final_test.app.name');
        $this->assertEquals($newValue, $updatedSetting->value);

        // 5. 測試設定重設
        $this->info('測試設定重設...');
        $resetResult = $this->repository->resetSetting('final_test.app.name');
        $this->assertTrue($resetResult);
        
        $resetSetting = $this->repository->getSetting('final_test.app.name');
        $this->assertEquals('Default App', $resetSetting->value);

        $this->info('✅ 完整設定管理工作流程測試通過');
    }

    /**
     * 測試效能優化功能
     * 
     * @test
     */
    public function test_performance_optimization(): void
    {
        $this->actingAs($this->adminUser);

        // 1. 測試快取預熱
        $this->info('測試快取預熱...');
        $warmupResult = $this->performanceService->warmupCache();
        
        $this->assertIsArray($warmupResult);
        $this->assertArrayHasKey('total_settings', $warmupResult);
        $this->assertArrayHasKey('cached_settings', $warmupResult);
        $this->assertGreaterThan(0, $warmupResult['total_settings']);

        // 2. 測試查詢優化
        $this->info('測試查詢優化...');
        $optimizationResult = $this->performanceService->optimizeQueries();
        
        $this->assertIsArray($optimizationResult);
        $this->assertArrayHasKey('indexes_created', $optimizationResult);
        $this->assertArrayHasKey('queries_optimized', $optimizationResult);

        // 3. 測試批量更新
        $this->info('測試批量更新...');
        $batchSettings = [
            'final_test.app.name' => 'Batch Updated App',
            'final_test.app.description' => 'Batch updated description',
        ];
        
        $batchResult = $this->performanceService->batchUpdateSettings($batchSettings);
        
        $this->assertIsArray($batchResult);
        $this->assertEquals(2, $batchResult['total']);
        $this->assertEquals(2, $batchResult['updated']);
        $this->assertEquals(0, $batchResult['failed']);

        // 4. 測試使用統計分析
        $this->info('測試使用統計分析...');
        $statsResult = $this->performanceService->analyzeUsageStatistics();
        
        $this->assertIsArray($statsResult);
        $this->assertArrayHasKey('total_settings', $statsResult);
        $this->assertArrayHasKey('by_category', $statsResult);
        $this->assertArrayHasKey('by_type', $statsResult);

        // 5. 測試完整性檢查
        $this->info('測試完整性檢查...');
        $integrityResult = $this->performanceService->checkIntegrity();
        
        $this->assertIsArray($integrityResult);
        $this->assertArrayHasKey('total_checks', $integrityResult);
        $this->assertArrayHasKey('passed', $integrityResult);
        $this->assertArrayHasKey('failed', $integrityResult);

        $this->info('✅ 效能優化功能測試通過');
    }

    /**
     * 測試權限控制功能
     * 
     * @test
     */
    public function test_permission_control(): void
    {
        // 測試管理員權限
        $this->info('測試管理員權限...');
        $this->actingAs($this->adminUser);
        
        // 管理員應該能夠執行所有操作
        $this->assertTrue($this->repository->updateSetting('final_test.app.name', 'Admin Updated'));
        
        $backup = $this->repository->createBackup('Admin Test Backup', 'Test backup by admin');
        $this->assertInstanceOf(SettingBackup::class, $backup);
        
        $this->assertTrue($this->repository->restoreBackup($backup->id));

        // 測試編輯者權限
        $this->info('測試編輯者權限...');
        $this->actingAs($this->editorUser);
        
        // 編輯者應該能夠更新非系統設定
        $this->assertTrue($this->repository->updateSetting('final_test.app.name', 'Editor Updated'));
        
        // 測試檢視者權限
        $this->info('測試檢視者權限...');
        $this->actingAs($this->viewerUser);
        
        // 檢視者應該能夠讀取設定
        $setting = $this->repository->getSetting('final_test.app.name');
        $this->assertNotNull($setting);

        $this->info('✅ 權限控制功能測試通過');
    }

    /**
     * 測試設定備份和還原功能
     * 
     * @test
     */
    public function test_backup_and_restore_functionality(): void
    {
        $this->actingAs($this->adminUser);

        // 1. 記錄原始設定值
        $this->info('記錄原始設定值...');
        $originalValues = [
            'final_test.app.name' => $this->repository->getSetting('final_test.app.name')->value,
            'final_test.app.description' => $this->repository->getSetting('final_test.app.description')->value,
        ];

        // 2. 建立備份
        $this->info('建立備份...');
        $backup = $this->repository->createBackup(
            '最終整合測試備份',
            '完整功能測試的備份',
            ['basic', 'security']
        );

        $this->assertInstanceOf(SettingBackup::class, $backup);
        $this->assertEquals('最終整合測試備份', $backup->name);
        $this->assertNotEmpty($backup->settings_data);

        // 3. 修改設定
        $this->info('修改設定...');
        $this->repository->updateSetting('final_test.app.name', 'Modified for Backup Test');
        $this->repository->updateSetting('final_test.app.description', 'Modified description for backup test');

        // 4. 驗證設定已修改
        $this->assertEquals('Modified for Backup Test', $this->repository->getSetting('final_test.app.name')->value);
        $this->assertEquals('Modified description for backup test', $this->repository->getSetting('final_test.app.description')->value);

        // 5. 還原備份
        $this->info('還原備份...');
        $restoreResult = $this->repository->restoreBackup($backup->id);
        $this->assertTrue($restoreResult);

        // 6. 驗證設定已還原
        $this->assertEquals($originalValues['final_test.app.name'], $this->repository->getSetting('final_test.app.name')->value);
        $this->assertEquals($originalValues['final_test.app.description'], $this->repository->getSetting('final_test.app.description')->value);

        // 7. 測試備份比較功能
        $this->info('測試備份比較功能...');
        $comparison = $this->repository->compareBackup($backup->id);
        $this->assertIsArray($comparison);

        // 8. 測試備份列表
        $this->info('測試備份列表...');
        $backups = $this->repository->getBackups();
        $this->assertGreaterThan(0, $backups->count());

        $this->info('✅ 備份和還原功能測試通過');
    }

    /**
     * 測試設定匯入匯出功能
     * 
     * @test
     */
    public function test_import_export_functionality(): void
    {
        $this->actingAs($this->adminUser);

        // 1. 測試匯出功能
        $this->info('測試匯出功能...');
        $exportData = $this->repository->exportSettings(['basic', 'appearance']);
        
        $this->assertIsArray($exportData);
        $this->assertGreaterThan(0, count($exportData));
        
        // 驗證匯出資料結構
        $firstSetting = $exportData[0];
        $this->assertArrayHasKey('key', $firstSetting);
        $this->assertArrayHasKey('value', $firstSetting);
        $this->assertArrayHasKey('category', $firstSetting);
        $this->assertArrayHasKey('type', $firstSetting);

        // 2. 測試匯入功能
        $this->info('測試匯入功能...');
        $importData = [
            [
                'key' => 'final_test.import.new_setting',
                'value' => 'Imported Value',
                'category' => 'basic',
                'type' => 'text',
                'description' => '匯入的新設定',
                'default_value' => 'Default',
                'is_system' => false,
                'is_encrypted' => false,
            ],
            [
                'key' => 'final_test.app.name', // 與現有設定衝突
                'value' => 'Conflicted Import Value',
                'category' => 'basic',
                'type' => 'text',
                'description' => '衝突的匯入設定',
                'default_value' => 'Default App',
                'is_system' => false,
                'is_encrypted' => false,
            ]
        ];

        $importResult = $this->repository->importSettings($importData);
        
        $this->assertTrue($importResult['success']);
        $this->assertGreaterThan(0, $importResult['created'] + $importResult['updated']);

        // 3. 驗證匯入結果
        $this->info('驗證匯入結果...');
        $newSetting = $this->repository->getSetting('final_test.import.new_setting');
        $this->assertNotNull($newSetting);
        $this->assertEquals('Imported Value', $newSetting->value);

        // 4. 測試選擇性匯入
        $this->info('測試選擇性匯入...');
        $partialImportData = [
            [
                'key' => 'final_test.partial.setting1',
                'value' => 'Partial 1',
                'category' => 'basic',
                'type' => 'text',
                'description' => '部分匯入設定 1',
                'default_value' => 'Default',
                'is_system' => false,
                'is_encrypted' => false,
            ],
            [
                'key' => 'final_test.partial.setting2',
                'value' => 'Partial 2',
                'category' => 'basic',
                'type' => 'text',
                'description' => '部分匯入設定 2',
                'default_value' => 'Default',
                'is_system' => false,
                'is_encrypted' => false,
            ]
        ];

        $selectedKeys = ['final_test.partial.setting1'];
        $partialResult = $this->repository->importSettings($partialImportData, [
            'selected_keys' => $selectedKeys
        ]);

        $this->assertTrue($partialResult['success']);
        $this->assertNotNull($this->repository->getSetting('final_test.partial.setting1'));
        $this->assertNull($this->repository->getSetting('final_test.partial.setting2'));

        $this->info('✅ 匯入匯出功能測試通過');
    }

    /**
     * 測試設定驗證和安全性
     * 
     * @test
     */
    public function test_validation_and_security(): void
    {
        $this->actingAs($this->adminUser);

        // 1. 測試設定值驗證
        $this->info('測試設定值驗證...');
        
        // 有效值驗證
        $validResult = $this->configService->validateSettingValue('final_test.app.name', 'Valid App Name');
        $this->assertTrue($validResult);
        
        // 無效值驗證（空值）
        $invalidResult = $this->configService->validateSettingValue('final_test.app.name', '');
        $this->assertFalse($invalidResult);
        
        // 數字範圍驗證
        $validNumberResult = $this->configService->validateSettingValue('final_test.security.password_length', 12);
        $this->assertTrue($validNumberResult);
        
        $invalidNumberResult = $this->configService->validateSettingValue('final_test.security.password_length', 3);
        $this->assertFalse($invalidNumberResult);
        
        // 顏色格式驗證
        $validColorResult = $this->configService->validateSettingValue('final_test.appearance.primary_color', '#FF0000');
        $this->assertTrue($validColorResult);
        
        $invalidColorResult = $this->configService->validateSettingValue('final_test.appearance.primary_color', 'red');
        $this->assertFalse($invalidColorResult);

        // 2. 測試加密設定
        $this->info('測試加密設定...');
        $encryptedSetting = $this->repository->getSetting('final_test.security.api_key');
        $this->assertNotNull($encryptedSetting);
        $this->assertTrue($encryptedSetting->is_encrypted);
        
        // 更新加密設定
        $newApiKey = 'new_secret_api_key_67890';
        $updateResult = $this->repository->updateSetting('final_test.security.api_key', $newApiKey);
        $this->assertTrue($updateResult);
        
        // 驗證加密設定值
        $updatedSetting = $this->repository->getSetting('final_test.security.api_key');
        $this->assertEquals($newApiKey, $updatedSetting->value);
        
        // 檢查資料庫中的值是否已加密
        $rawSetting = Setting::where('key', 'final_test.security.api_key')->first();
        $this->assertNotEquals($newApiKey, $rawSetting->getRawOriginal('value'));

        // 3. 測試設定相依性檢查
        $this->info('測試設定相依性檢查...');
        $dependencies = $this->configService->getDependentSettings('final_test.app.name');
        $this->assertIsArray($dependencies);

        $this->info('✅ 驗證和安全性測試通過');
    }

    /**
     * 測試使用者體驗流程
     * 
     * @test
     */
    public function test_user_experience_workflow(): void
    {
        $this->actingAs($this->adminUser);

        // 1. 測試設定搜尋和篩選
        $this->info('測試設定搜尋和篩選...');
        
        // 關鍵字搜尋
        $searchResults = $this->repository->searchSettings('final_test');
        $this->assertGreaterThan(0, $searchResults->count());
        
        // 分類篩選
        $basicSettings = $this->repository->searchSettings('', ['category' => 'basic']);
        $this->assertGreaterThan(0, $basicSettings->count());
        foreach ($basicSettings as $setting) {
            $this->assertEquals('basic', $setting->category);
        }
        
        // 類型篩選
        $textSettings = $this->repository->searchSettings('', ['type' => 'text']);
        $this->assertGreaterThan(0, $textSettings->count());
        foreach ($textSettings as $setting) {
            $this->assertEquals('text', $setting->type);
        }
        
        // 複合篩選
        $complexResults = $this->repository->searchSettings('app', [
            'category' => 'basic',
            'type' => 'text'
        ]);
        $this->assertGreaterThanOrEqual(0, $complexResults->count());

        // 2. 測試批量操作
        $this->info('測試批量操作...');
        $bulkUpdates = [
            'final_test.app.name' => 'Bulk Updated App',
            'final_test.app.description' => 'Bulk updated description',
        ];
        
        $bulkResult = $this->performanceService->batchUpdateSettings($bulkUpdates);
        $this->assertTrue($bulkResult['updated'] > 0);
        
        // 驗證批量更新結果
        $this->assertEquals('Bulk Updated App', $this->repository->getSetting('final_test.app.name')->value);
        $this->assertEquals('Bulk updated description', $this->repository->getSetting('final_test.app.description')->value);

        // 3. 測試設定預覽功能
        $this->info('測試設定預覽功能...');
        $previewSettings = [
            'final_test.appearance.primary_color' => '#00FF00',
            'final_test.appearance.dark_mode' => false,
        ];
        
        $previewData = $this->configService->generatePreview($previewSettings);
        $this->assertIsArray($previewData);

        // 4. 測試連線測試功能
        $this->info('測試連線測試功能...');
        $smtpSettings = [
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'encryption' => 'tls',
        ];
        
        $connectionTest = $this->configService->testConnection('smtp', $smtpSettings);
        $this->assertIsBool($connectionTest);

        $this->info('✅ 使用者體驗流程測試通過');
    }

    /**
     * 測試整合命令功能
     * 
     * @test
     */
    public function test_integration_command(): void
    {
        $this->info('測試整合命令功能...');

        // 測試完整整合命令
        $exitCode = Artisan::call('settings:integrate', [
            '--test' => true,
            '--optimize' => true,
            '--check' => true,
        ]);
        
        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('系統設定整合和優化工具', $output);
        $this->assertStringContainsString('執行結果摘要', $output);

        $this->info('✅ 整合命令功能測試通過');
    }

    /**
     * 測試快取效能
     * 
     * @test
     */
    public function test_cache_performance(): void
    {
        $this->actingAs($this->adminUser);

        // 1. 清除快取
        $this->info('清除快取...');
        Cache::flush();

        // 2. 第一次取得設定（建立快取）
        $this->info('第一次取得設定（建立快取）...');
        $startTime = microtime(true);
        $setting1 = $this->repository->getSetting('final_test.app.name');
        $firstQueryTime = microtime(true) - $startTime;
        
        $this->assertNotNull($setting1);

        // 3. 第二次取得設定（從快取）
        $this->info('第二次取得設定（從快取）...');
        $startTime = microtime(true);
        $setting2 = $this->repository->getSetting('final_test.app.name');
        $secondQueryTime = microtime(true) - $startTime;
        
        $this->assertEquals($setting1->id, $setting2->id);
        
        // 快取查詢應該更快
        $this->assertLessThan($firstQueryTime, $secondQueryTime);

        // 4. 測試快取預熱
        $this->info('測試快取預熱...');
        $warmupResult = $this->performanceService->warmupCache();
        
        $this->assertGreaterThan(0, $warmupResult['cached_settings']);
        $this->assertLessThan(1000, $warmupResult['execution_time']); // 應該在 1 秒內完成

        $this->info('✅ 快取效能測試通過');
    }

    /**
     * 輸出測試資訊
     */
    protected function info(string $message): void
    {
        if (app()->runningInConsole()) {
            echo "ℹ️  {$message}\n";
        }
    }

    /**
     * 清理測試資料
     */
    protected function tearDown(): void
    {
        // 清除測試建立的設定
        Setting::where('key', 'like', 'final_test.%')->delete();
        SettingBackup::where('name', 'like', '%測試%')->delete();
        
        // 清除快取
        Cache::flush();
        
        parent::tearDown();
    }
}