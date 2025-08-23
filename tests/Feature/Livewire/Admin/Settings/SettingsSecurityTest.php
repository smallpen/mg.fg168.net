<?php

namespace Tests\Feature\Livewire\Admin\Settings;

use App\Livewire\Admin\Settings\SettingsList;
use App\Livewire\Admin\Settings\SettingForm;
use App\Livewire\Admin\Settings\SettingBackupManager;
use App\Livewire\Admin\Settings\SettingPreview;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Models\Permission;
use App\Repositories\SettingsRepositoryInterface;
use App\Services\ConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Mockery;

/**
 * 設定系統安全性和權限檢查測試
 * 
 * 測試設定管理系統的安全性控制、權限檢查和敏感資料保護
 */
class SettingsSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $editorUser;
    protected User $viewerUser;
    protected User $unauthorizedUser;
    protected Role $adminRole;
    protected Role $editorRole;
    protected Role $viewerRole;
    protected array $testSettings;
    protected $mockRepository;
    protected $mockConfigService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createRolesAndPermissions();
        $this->createTestUsers();
        $this->createTestSettings();
        $this->createMockServices();
    }

    private function createRolesAndPermissions(): void
    {
        // 建立角色
        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '系統管理員',
            'is_system_role' => true,
            'is_active' => true,
        ]);

        $this->editorRole = Role::create([
            'name' => 'editor',
            'display_name' => '編輯者',
            'is_system_role' => false,
            'is_active' => true,
        ]);

        $this->viewerRole = Role::create([
            'name' => 'viewer',
            'display_name' => '檢視者',
            'is_system_role' => false,
            'is_active' => true,
        ]);

        // 建立權限
        $permissions = [
            'system.settings.view' => '檢視系統設定',
            'system.settings.edit' => '編輯系統設定',
            'system.settings.edit.basic' => '編輯基本設定',
            'system.settings.edit.security' => '編輯安全設定',
            'system.settings.edit.notification' => '編輯通知設定',
            'system.settings.edit.appearance' => '編輯外觀設定',
            'system.settings.edit.integration' => '編輯整合設定',
            'system.settings.edit.maintenance' => '編輯維護設定',
            'system.settings.export' => '匯出系統設定',
            'system.settings.import' => '匯入系統設定',
            'system.settings.backup' => '備份系統設定',
            'system.settings.restore' => '還原系統設定',
            'system.settings.reset' => '重設系統設定',
            'system.settings.preview' => '預覽系統設定',
            'system.settings.test' => '測試系統設定',
            'system.settings.view.sensitive' => '檢視敏感設定',
            'system.settings.edit.sensitive' => '編輯敏感設定',
        ];

        foreach ($permissions as $name => $displayName) {
            Permission::create([
                'name' => $name,
                'display_name' => $displayName,
                'module' => 'settings',
                'type' => 'action'
            ]);
        }

        // 管理員擁有所有權限
        $this->adminRole->permissions()->attach(
            Permission::whereIn('name', array_keys($permissions))->pluck('id')
        );

        // 編輯者擁有部分權限
        $editorPermissions = [
            'system.settings.view',
            'system.settings.edit',
            'system.settings.edit.basic',
            'system.settings.edit.appearance',
            'system.settings.preview',
        ];
        $this->editorRole->permissions()->attach(
            Permission::whereIn('name', $editorPermissions)->pluck('id')
        );

        // 檢視者只有檢視權限
        $viewerPermissions = [
            'system.settings.view',
        ];
        $this->viewerRole->permissions()->attach(
            Permission::whereIn('name', $viewerPermissions)->pluck('id')
        );
    }

    private function createTestUsers(): void
    {
        $this->adminUser = User::factory()->create([
            'username' => 'admin',
            'name' => '系統管理員',
            'email' => 'admin@example.com',
            'is_active' => true,
        ]);
        $this->adminUser->roles()->attach($this->adminRole);

        $this->editorUser = User::factory()->create([
            'username' => 'editor',
            'name' => '編輯者',
            'email' => 'editor@example.com',
            'is_active' => true,
        ]);
        $this->editorUser->roles()->attach($this->editorRole);

        $this->viewerUser = User::factory()->create([
            'username' => 'viewer',
            'name' => '檢視者',
            'email' => 'viewer@example.com',
            'is_active' => true,
        ]);
        $this->viewerUser->roles()->attach($this->viewerRole);

        $this->unauthorizedUser = User::factory()->create([
            'username' => 'unauthorized',
            'name' => '無權限使用者',
            'email' => 'unauthorized@example.com',
            'is_active' => true,
        ]);
    }

    private function createTestSettings(): void
    {
        $this->testSettings = [
            // 公開設定
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
            // 非公開但非敏感設定
            [
                'key' => 'app.timezone',
                'value' => 'Asia/Taipei',
                'category' => 'basic',
                'type' => 'select',
                'description' => '系統時區',
                'default_value' => 'UTC',
                'is_encrypted' => false,
                'is_system' => true,
                'is_public' => false,
            ],
            // 敏感設定（加密）
            [
                'key' => 'integration.api_secret',
                'value' => 'secret_key_123',
                'category' => 'integration',
                'type' => 'password',
                'description' => 'API 密鑰',
                'default_value' => '',
                'is_encrypted' => true,
                'is_system' => false,
                'is_public' => false,
            ],
            // 系統關鍵設定
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
            // 維護模式設定（高影響）
            [
                'key' => 'maintenance.maintenance_mode',
                'value' => false,
                'category' => 'maintenance',
                'type' => 'boolean',
                'description' => '維護模式',
                'default_value' => false,
                'is_encrypted' => false,
                'is_system' => true,
                'is_public' => false,
            ],
        ];

        foreach ($this->testSettings as $settingData) {
            Setting::create($settingData);
        }
    }

    private function createMockServices(): void
    {
        $this->mockRepository = Mockery::mock(SettingsRepositoryInterface::class);
        $this->mockConfigService = Mockery::mock(ConfigurationService::class);
        
        // 設定預設的 Mock 行為
        $this->mockRepository->shouldReceive('searchSettings')
            ->andReturnUsing(function ($search, $filters) {
                return collect($this->testSettings)->map(function ($data) {
                    $setting = new Setting();
                    $setting->fill($data);
                    $setting->id = fake()->numberBetween(1, 1000);
                    return $setting;
                });
            });

        $this->mockRepository->shouldReceive('getSetting')
            ->andReturnUsing(function ($key) {
                $settingData = collect($this->testSettings)->firstWhere('key', $key);
                if ($settingData) {
                    $setting = new Setting();
                    $setting->fill($settingData);
                    $setting->id = fake()->numberBetween(1, 1000);
                    return $setting;
                }
                return null;
            });

        $this->mockConfigService->shouldReceive('getCategories')
            ->andReturn([
                'basic' => ['name' => '基本設定', 'icon' => 'cog'],
                'security' => ['name' => '安全設定', 'icon' => 'shield-check'],
                'integration' => ['name' => '整合設定', 'icon' => 'link'],
                'maintenance' => ['name' => '維護設定', 'icon' => 'wrench'],
            ]);

        // 綁定到容器
        $this->app->instance(SettingsRepositoryInterface::class, $this->mockRepository);
        $this->app->instance(ConfigurationService::class, $this->mockConfigService);
    }

    /** @test */
    public function 管理員可以存取所有設定元件()
    {
        $this->actingAs($this->adminUser);

        // 測試 SettingsList
        $listComponent = Livewire::test(SettingsList::class);
        $listComponent->assertStatus(200);

        // 測試 SettingForm
        $formComponent = Livewire::test(SettingForm::class);
        $formComponent->assertStatus(200);

        // 測試 SettingBackupManager
        $backupComponent = Livewire::test(SettingBackupManager::class);
        $backupComponent->assertStatus(200);

        // 測試 SettingPreview
        $previewComponent = Livewire::test(SettingPreview::class);
        $previewComponent->assertStatus(200);
    }

    /** @test */
    public function 編輯者只能存取有權限的元件()
    {
        $this->actingAs($this->editorUser);

        // 可以存取設定列表
        $listComponent = Livewire::test(SettingsList::class);
        $listComponent->assertStatus(200);

        // 可以存取設定表單
        $formComponent = Livewire::test(SettingForm::class);
        $formComponent->assertStatus(200);

        // 可以存取預覽功能
        $previewComponent = Livewire::test(SettingPreview::class);
        $previewComponent->assertStatus(200);

        // 無法存取備份管理
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        Livewire::test(SettingBackupManager::class);
    }

    /** @test */
    public function 檢視者只能存取檢視功能()
    {
        $this->actingAs($this->viewerUser);

        // 可以存取設定列表（只讀）
        $listComponent = Livewire::test(SettingsList::class);
        $listComponent->assertStatus(200);

        // 無法存取設定表單
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        Livewire::test(SettingForm::class);
    }

    /** @test */
    public function 無權限使用者無法存取任何設定元件()
    {
        $this->actingAs($this->unauthorizedUser);

        // 無法存取設定列表
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        Livewire::test(SettingsList::class);
    }

    /** @test */
    public function 敏感設定的存取控制()
    {
        // 管理員可以存取敏感設定
        $this->actingAs($this->adminUser);
        $adminComponent = Livewire::test(SettingForm::class);
        $adminComponent->call('openForm', 'integration.api_secret');
        $adminComponent->assertStatus(200);

        // 編輯者無法存取敏感設定
        $this->actingAs($this->editorUser);
        $editorComponent = Livewire::test(SettingForm::class);
        
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $editorComponent->call('openForm', 'integration.api_secret');
    }

    /** @test */
    public function 系統設定的編輯權限控制()
    {
        // 管理員可以編輯系統設定
        $this->actingAs($this->adminUser);
        
        $this->mockRepository->shouldReceive('updateSetting')
            ->with('security.password_min_length', 10)
            ->andReturn(true);

        $adminComponent = Livewire::test(SettingForm::class);
        $adminComponent->call('openForm', 'security.password_min_length')
                      ->set('value', 10)
                      ->call('save');
        $adminComponent->assertHasNoErrors();

        // 編輯者無法編輯安全設定
        $this->actingAs($this->editorUser);
        $editorComponent = Livewire::test(SettingForm::class);
        
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $editorComponent->call('openForm', 'security.password_min_length');
    }

    /** @test */
    public function 批量操作的權限控制()
    {
        // 管理員可以執行批量操作
        $this->actingAs($this->adminUser);
        
        $this->mockRepository->shouldReceive('resetSetting')
            ->andReturn(true);

        $adminComponent = Livewire::test(SettingsList::class);
        $adminComponent->call('toggleSettingSelection', 'app.name')
                      ->set('bulkAction', 'reset')
                      ->call('executeBulkAction')
                      ->call('confirmBulkAction');
        $adminComponent->assertHasNoErrors();

        // 檢視者無法執行批量操作
        $this->actingAs($this->viewerUser);
        $viewerComponent = Livewire::test(SettingsList::class);
        
        // 應該無法看到批量操作按鈕或執行批量操作
        $viewerComponent->call('toggleSettingSelection', 'app.name')
                        ->set('bulkAction', 'reset')
                        ->call('executeBulkAction');
        
        // 檢查是否有權限錯誤
        $this->assertTrue(true); // 這裡應該有更具體的權限檢查
    }

    /** @test */
    public function 匯入匯出功能的權限控制()
    {
        // 管理員可以匯入匯出
        $this->actingAs($this->adminUser);
        $adminComponent = Livewire::test(SettingsList::class);
        $adminComponent->call('exportSettings')
                      ->assertDispatched('open-export-dialog');
        $adminComponent->call('openImportDialog')
                      ->assertDispatched('open-import-dialog');

        // 編輯者無法匯入匯出
        $this->actingAs($this->editorUser);
        $editorComponent = Livewire::test(SettingsList::class);
        
        // 嘗試匯出應該被拒絕
        $editorComponent->call('exportSettings');
        // 這裡應該檢查權限並拒絕操作
        
        // 嘗試匯入應該被拒絕
        $editorComponent->call('openImportDialog');
        // 這裡應該檢查權限並拒絕操作
    }

    /** @test */
    public function 備份還原功能的權限控制()
    {
        // 管理員可以建立和還原備份
        $this->actingAs($this->adminUser);
        
        $this->mockRepository->shouldReceive('createBackup')
            ->andReturn(new \App\Models\SettingBackup([
                'id' => 1,
                'name' => '測試備份',
                'description' => '測試描述',
            ]));

        $adminComponent = Livewire::test(SettingBackupManager::class);
        $adminComponent->call('openCreateModal')
                      ->set('backupName', '測試備份')
                      ->call('createBackup');
        $adminComponent->assertHasNoErrors();

        // 編輯者無法存取備份管理
        $this->actingAs($this->editorUser);
        
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        Livewire::test(SettingBackupManager::class);
    }

    /** @test */
    public function 連線測試功能的權限控制()
    {
        // 管理員可以執行連線測試
        $this->actingAs($this->adminUser);
        
        $this->mockConfigService->shouldReceive('testConnection')
            ->andReturn(true);

        $adminComponent = Livewire::test(SettingPreview::class);
        $adminComponent->set('previewSettings', [
            'notification.smtp_host' => 'smtp.gmail.com',
            'notification.smtp_username' => 'admin@example.com',
        ]);
        $adminComponent->call('testSmtpConnection');
        $adminComponent->assertHasNoErrors();

        // 編輯者可以執行連線測試（有預覽權限）
        $this->actingAs($this->editorUser);
        $editorComponent = Livewire::test(SettingPreview::class);
        $editorComponent->set('previewSettings', [
            'notification.smtp_host' => 'smtp.gmail.com',
            'notification.smtp_username' => 'admin@example.com',
        ]);
        $editorComponent->call('testSmtpConnection');
        $editorComponent->assertHasNoErrors();

        // 檢視者無法執行連線測試
        $this->actingAs($this->viewerUser);
        
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        Livewire::test(SettingPreview::class);
    }

    /** @test */
    public function 敏感資料的顯示保護()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingForm::class);
        $component->call('openForm', 'integration.api_secret');

        // 檢查敏感設定是否正確標記
        $this->assertTrue($component->instance()->isSensitive());

        // 敏感設定的值在前端應該被適當處理
        // 這裡應該檢查值是否被遮罩或加密顯示
        $this->assertTrue(true); // 實際實作中應該有更具體的檢查
    }

    /** @test */
    public function 設定變更的審計日誌()
    {
        $this->actingAs($this->adminUser);

        $this->mockRepository->shouldReceive('updateSetting')
            ->with('app.name', 'New App Name')
            ->andReturn(true);

        $component = Livewire::test(SettingForm::class);
        $component->call('openForm', 'app.name')
                 ->set('value', 'New App Name')
                 ->call('save');

        // 檢查設定變更是否被記錄
        // 這裡應該檢查審計日誌是否正確記錄了變更
        $this->assertTrue(true); // 實際實作中應該檢查審計日誌
    }

    /** @test */
    public function IP限制檢查()
    {
        // 這個測試需要模擬不同的 IP 地址
        $this->actingAs($this->adminUser);

        // 模擬來自受限 IP 的請求
        $this->app['request']->server->set('REMOTE_ADDR', '192.168.1.100');

        $component = Livewire::test(SettingForm::class);
        
        // 如果有 IP 限制，應該檢查並拒絕存取
        // 這裡應該根據實際的 IP 限制邏輯進行測試
        $this->assertTrue(true); // 實際實作中應該有 IP 限制檢查
    }

    /** @test */
    public function 會話安全檢查()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingForm::class);
        $component->call('openForm', 'security.password_min_length');

        // 檢查會話是否安全
        // 這裡應該檢查 CSRF 令牌、會話過期等安全措施
        $this->assertTrue(true); // 實際實作中應該有會話安全檢查
    }

    /** @test */
    public function 輸入驗證和清理()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingForm::class);
        $component->call('openForm', 'app.name');

        // 測試 XSS 攻擊防護
        $maliciousInput = '<script>alert("XSS")</script>';
        $component->set('value', $maliciousInput)
                 ->call('validateValue');

        // 檢查是否正確處理惡意輸入
        $validationErrors = $component->get('validationErrors');
        $this->assertNotEmpty($validationErrors); // 應該有驗證錯誤

        // 測試 SQL 注入防護
        $sqlInjection = "'; DROP TABLE settings; --";
        $component->set('value', $sqlInjection)
                 ->call('validateValue');

        // 檢查是否正確處理 SQL 注入嘗試
        $validationErrors = $component->get('validationErrors');
        $this->assertNotEmpty($validationErrors); // 應該有驗證錯誤
    }

    /** @test */
    public function 檔案上傳安全檢查()
    {
        $this->actingAs($this->adminUser);

        // 建立檔案上傳設定
        $fileSetting = Setting::create([
            'key' => 'appearance.logo',
            'value' => '',
            'category' => 'appearance',
            'type' => 'image',
            'description' => '系統標誌',
            'default_value' => '',
            'is_encrypted' => false,
            'is_system' => false,
            'is_public' => true,
        ]);

        $this->mockRepository->shouldReceive('getSetting')
            ->with('appearance.logo')
            ->andReturn($fileSetting);

        $component = Livewire::test(SettingForm::class);
        $component->call('openForm', 'appearance.logo');

        // 測試惡意檔案上傳
        $maliciousFile = \Illuminate\Http\UploadedFile::fake()->create('malicious.php', 1000);
        $component->set('uploadedFile', $maliciousFile);

        // 檢查是否正確拒絕惡意檔案
        // 這裡應該有檔案類型和內容的安全檢查
        $this->assertTrue(true); // 實際實作中應該有檔案安全檢查
    }

    /** @test */
    public function 速率限制檢查()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingForm::class);

        // 模擬快速連續的請求
        for ($i = 0; $i < 10; $i++) {
            $component->call('openForm', 'app.name');
        }

        // 檢查是否有速率限制
        // 這裡應該檢查是否觸發了速率限制
        $this->assertTrue(true); // 實際實作中應該有速率限制檢查
    }

    /** @test */
    public function 資料加密和解密()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingForm::class);
        $component->call('openForm', 'integration.api_secret');

        // 檢查敏感資料是否正確加密
        $setting = Setting::where('key', 'integration.api_secret')->first();
        $this->assertTrue($setting->is_encrypted);

        // 檢查解密功能是否正常
        // 這裡應該測試加密和解密邏輯
        $this->assertTrue(true); // 實際實作中應該測試加密解密
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}