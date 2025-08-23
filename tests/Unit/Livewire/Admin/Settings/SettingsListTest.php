<?php

namespace Tests\Unit\Livewire\Admin\Settings;

use App\Livewire\Admin\Settings\SettingsList;
use App\Models\Setting;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Repositories\SettingsRepositoryInterface;
use App\Services\ConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Livewire\Livewire;
use Tests\TestCase;
use Mockery;

/**
 * SettingsList 元件單元測試
 */
class SettingsListTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Role $adminRole;
    protected array $testSettings;
    protected $mockRepository;
    protected $mockConfigService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立管理員角色和使用者
        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理員',
            'is_system_role' => true,
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create(['is_active' => true]);
        $this->adminUser->roles()->attach($this->adminRole);
        
        // 建立系統設定相關權限
        $permissions = [
            'system.settings.view', 'system.settings.edit', 'system.settings.export', 
            'system.settings.import', 'system.settings.backup'
        ];
        
        foreach ($permissions as $permission) {
            Permission::create([
                'name' => $permission,
                'display_name' => $permission,
                'module' => 'settings',
                'type' => 'view'
            ]);
        }
        
        $this->adminRole->permissions()->attach(
            Permission::whereIn('name', $permissions)->pluck('id')
        );

        // 建立測試設定資料
        $this->createTestSettings();
        
        // 建立 Mock 服務
        $this->createMockServices();
    }

    private function createTestSettings(): void
    {
        $this->testSettings = [
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
                'sort_order' => 1,
            ],
            [
                'key' => 'app.timezone',
                'value' => 'Asia/Taipei',
                'category' => 'basic',
                'type' => 'select',
                'description' => '系統時區',
                'default_value' => 'UTC',
                'is_encrypted' => false,
                'is_system' => true,
                'is_public' => true,
                'sort_order' => 2,
            ],
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
                'sort_order' => 1,
            ],
            [
                'key' => 'notification.smtp_host',
                'value' => 'smtp.gmail.com',
                'category' => 'notification',
                'type' => 'text',
                'description' => 'SMTP 主機',
                'default_value' => '',
                'is_encrypted' => false,
                'is_system' => false,
                'is_public' => false,
                'sort_order' => 1,
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
            ->andReturn(collect($this->testSettings)->map(function ($data) {
                $setting = new Setting();
                $setting->fill($data);
                return $setting;
            }));
            
        $this->mockRepository->shouldReceive('getAllSettings')
            ->andReturn(collect($this->testSettings)->map(function ($data) {
                $setting = new Setting();
                $setting->fill($data);
                return $setting;
            }));
            
        $this->mockRepository->shouldReceive('getChangedSettings')
            ->andReturn(collect());
            
        $this->mockRepository->shouldReceive('getAvailableTypes')
            ->andReturn(collect(['text', 'number', 'select', 'boolean']));
            
        $this->mockConfigService->shouldReceive('getCategories')
            ->andReturn([
                'basic' => ['name' => '基本設定', 'icon' => 'cog', 'description' => '應用程式基本設定'],
                'security' => ['name' => '安全設定', 'icon' => 'shield-check', 'description' => '系統安全設定'],
                'notification' => ['name' => '通知設定', 'icon' => 'bell', 'description' => '通知相關設定'],
            ]);
        
        // 綁定到容器
        $this->app->instance(SettingsRepositoryInterface::class, $this->mockRepository);
        $this->app->instance(ConfigurationService::class, $this->mockConfigService);
    }

    /** @test */
    public function 元件可以正確初始化()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingsList::class);

        $component->assertSet('search', '')
                 ->assertSet('categoryFilter', 'all')
                 ->assertSet('changedFilter', 'all')
                 ->assertSet('typeFilter', 'all')
                 ->assertSet('viewMode', 'category')
                 ->assertSet('selectedSettings', [])
                 ->assertSet('bulkAction', '')
                 ->assertSet('showBulkConfirm', false);
    }

    /** @test */
    public function 沒有權限的使用者無法存取元件()
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        
        Livewire::test(SettingsList::class);
    }

    /** @test */
    public function 可以正確顯示設定列表()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingsList::class);

        $component->assertStatus(200);
        
        // 檢查是否顯示測試設定
        foreach ($this->testSettings as $setting) {
            $component->assertSee($setting['key'])
                     ->assertSee($setting['description']);
        }
    }

    /** @test */
    public function 搜尋功能正常運作()
    {
        $this->actingAs($this->adminUser);
        
        // 設定搜尋結果的 Mock
        $this->mockRepository->shouldReceive('searchSettings')
            ->with('app', [])
            ->andReturn(collect($this->testSettings)->filter(function ($setting) {
                return str_contains($setting['key'], 'app');
            })->map(function ($data) {
                $setting = new Setting();
                $setting->fill($data);
                return $setting;
            }));

        $component = Livewire::test(SettingsList::class);

        // 搜尋 "app"
        $component->set('search', 'app')
                 ->assertSee('app.name')
                 ->assertSee('app.timezone');
    }

    /** @test */
    public function 分類篩選功能正常運作()
    {
        $this->actingAs($this->adminUser);
        
        // 設定分類篩選結果的 Mock
        $this->mockRepository->shouldReceive('searchSettings')
            ->with('', ['category' => 'basic', 'type' => null, 'changed' => null])
            ->andReturn(collect($this->testSettings)->filter(function ($setting) {
                return $setting['category'] === 'basic';
            })->map(function ($data) {
                $setting = new Setting();
                $setting->fill($data);
                return $setting;
            }));

        $component = Livewire::test(SettingsList::class);

        // 篩選 basic 分類
        $component->set('categoryFilter', 'basic')
                 ->assertSee('app.name')
                 ->assertSee('app.timezone');
    }

    /** @test */
    public function 類型篩選功能正常運作()
    {
        $this->actingAs($this->adminUser);
        
        // 設定類型篩選結果的 Mock
        $this->mockRepository->shouldReceive('searchSettings')
            ->with('', ['category' => null, 'type' => 'text', 'changed' => null])
            ->andReturn(collect($this->testSettings)->filter(function ($setting) {
                return $setting['type'] === 'text';
            })->map(function ($data) {
                $setting = new Setting();
                $setting->fill($data);
                return $setting;
            }));

        $component = Livewire::test(SettingsList::class);

        // 篩選 text 類型
        $component->set('typeFilter', 'text')
                 ->assertSee('app.name')
                 ->assertSee('notification.smtp_host');
    }

    /** @test */
    public function 變更狀態篩選功能正常運作()
    {
        $this->actingAs($this->adminUser);
        
        // 設定變更狀態篩選結果的 Mock
        $this->mockRepository->shouldReceive('searchSettings')
            ->with('', ['category' => null, 'type' => null, 'changed' => true])
            ->andReturn(collect($this->testSettings)->filter(function ($setting) {
                return $setting['value'] !== $setting['default_value'];
            })->map(function ($data) {
                $setting = new Setting();
                $setting->fill($data);
                return $setting;
            }));

        $component = Livewire::test(SettingsList::class);

        // 篩選已變更的設定
        $component->set('changedFilter', 'changed')
                 ->assertSee('app.timezone') // 值與預設值不同
                 ->assertSee('security.password_min_length'); // 值與預設值不同
    }

    /** @test */
    public function 檢視模式切換功能正常()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingsList::class);

        // 切換到列表檢視
        $component->set('viewMode', 'list')
                 ->assertSet('viewMode', 'list');

        // 切換到樹狀檢視
        $component->set('viewMode', 'tree')
                 ->assertSet('viewMode', 'tree');

        // 切換回分類檢視
        $component->set('viewMode', 'category')
                 ->assertSet('viewMode', 'category');
    }

    /** @test */
    public function 分類展開收合功能正常()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingsList::class);

        // 預設應該展開所有分類
        $expandedCategories = $component->get('expandedCategories');
        $this->assertContains('basic', $expandedCategories);
        $this->assertContains('security', $expandedCategories);
        $this->assertContains('notification', $expandedCategories);

        // 收合 basic 分類
        $component->call('toggleCategory', 'basic');
        $expandedCategories = $component->get('expandedCategories');
        $this->assertNotContains('basic', $expandedCategories);

        // 重新展開 basic 分類
        $component->call('toggleCategory', 'basic');
        $expandedCategories = $component->get('expandedCategories');
        $this->assertContains('basic', $expandedCategories);
    }

    /** @test */
    public function 展開收合所有分類功能正常()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingsList::class);

        // 收合所有分類
        $component->call('collapseAllCategories')
                 ->assertSet('expandedCategories', []);

        // 展開所有分類
        $component->call('expandAllCategories');
        $expandedCategories = $component->get('expandedCategories');
        $this->assertContains('basic', $expandedCategories);
        $this->assertContains('security', $expandedCategories);
        $this->assertContains('notification', $expandedCategories);
    }

    /** @test */
    public function 編輯設定功能正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingsList::class);

        $component->call('editSetting', 'app.name')
                 ->assertDispatched('open-setting-form', ['settingKey' => 'app.name']);
    }

    /** @test */
    public function 重設設定功能正常運作()
    {
        $this->actingAs($this->adminUser);
        
        // 設定重設功能的 Mock
        $this->mockRepository->shouldReceive('resetSetting')
            ->with('app.timezone')
            ->andReturn(true);

        $component = Livewire::test(SettingsList::class);

        $component->call('resetSetting', 'app.timezone')
                 ->assertDispatched('setting-updated', ['settingKey' => 'app.timezone']);
    }

    /** @test */
    public function 重設設定失敗時顯示錯誤()
    {
        $this->actingAs($this->adminUser);
        
        // 設定重設失敗的 Mock
        $this->mockRepository->shouldReceive('resetSetting')
            ->with('app.name')
            ->andReturn(false);

        $component = Livewire::test(SettingsList::class);

        $component->call('resetSetting', 'app.name');
        
        // 檢查是否有錯誤訊息
        $this->assertTrue($component->instance()->hasFlashMessage('error'));
    }

    /** @test */
    public function 設定選擇功能正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingsList::class);

        // 選擇設定
        $component->call('toggleSettingSelection', 'app.name')
                 ->assertSet('selectedSettings', ['app.name']);

        // 再次選擇應該取消選擇
        $component->call('toggleSettingSelection', 'app.name')
                 ->assertSet('selectedSettings', []);

        // 選擇多個設定
        $component->call('toggleSettingSelection', 'app.name')
                 ->call('toggleSettingSelection', 'app.timezone');
        
        $selectedSettings = $component->get('selectedSettings');
        $this->assertContains('app.name', $selectedSettings);
        $this->assertContains('app.timezone', $selectedSettings);
    }

    /** @test */
    public function 全選功能正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingsList::class);

        // 執行全選
        $component->call('toggleSelectAll');

        $selectedSettings = $component->get('selectedSettings');
        $this->assertNotEmpty($selectedSettings);

        // 再次執行應該取消全選
        $component->call('toggleSelectAll')
                 ->assertSet('selectedSettings', []);
    }

    /** @test */
    public function 清除選擇功能正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingsList::class);

        // 先選擇一些設定
        $component->call('toggleSettingSelection', 'app.name')
                 ->call('toggleSettingSelection', 'app.timezone');

        // 清除選擇
        $component->call('clearSelection')
                 ->assertSet('selectedSettings', []);
    }

    /** @test */
    public function 批量操作功能正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingsList::class);

        // 選擇設定並設定批量操作
        $component->call('toggleSettingSelection', 'app.name')
                 ->set('bulkAction', 'reset')
                 ->call('executeBulkAction')
                 ->assertSet('showBulkConfirm', true);
    }

    /** @test */
    public function 批量操作沒有選擇設定時顯示警告()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingsList::class);

        // 沒有選擇設定就執行批量操作
        $component->set('bulkAction', 'reset')
                 ->call('executeBulkAction');
        
        // 檢查是否有警告訊息
        $this->assertTrue($component->instance()->hasFlashMessage('warning'));
    }

    /** @test */
    public function 確認批量操作功能正常運作()
    {
        $this->actingAs($this->adminUser);
        
        // 設定批量重設的 Mock
        $this->mockRepository->shouldReceive('resetSetting')
            ->with('app.name')
            ->andReturn(true);
        $this->mockRepository->shouldReceive('resetSetting')
            ->with('app.timezone')
            ->andReturn(true);

        $component = Livewire::test(SettingsList::class);

        // 選擇設定並執行批量重設
        $component->call('toggleSettingSelection', 'app.name')
                 ->call('toggleSettingSelection', 'app.timezone')
                 ->set('bulkAction', 'reset')
                 ->call('executeBulkAction')
                 ->call('confirmBulkAction')
                 ->assertSet('selectedSettings', [])
                 ->assertSet('bulkAction', '')
                 ->assertSet('showBulkConfirm', false)
                 ->assertDispatched('settings-bulk-updated');
    }

    /** @test */
    public function 取消批量操作功能正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingsList::class);

        // 開啟批量操作確認對話框後取消
        $component->set('showBulkConfirm', true)
                 ->set('bulkAction', 'reset')
                 ->call('cancelBulkAction')
                 ->assertSet('showBulkConfirm', false)
                 ->assertSet('bulkAction', '');
    }

    /** @test */
    public function 匯出設定功能正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingsList::class);

        $component->call('exportSettings')
                 ->assertDispatched('open-export-dialog');
    }

    /** @test */
    public function 匯入設定功能正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingsList::class);

        $component->call('openImportDialog')
                 ->assertDispatched('open-import-dialog');
    }

    /** @test */
    public function 建立備份功能正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingsList::class);

        $component->call('createBackup')
                 ->assertDispatched('open-backup-dialog');
    }

    /** @test */
    public function 清除篩選功能正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingsList::class);

        // 設定一些篩選條件
        $component->set('search', 'test')
                 ->set('categoryFilter', 'basic')
                 ->set('changedFilter', 'changed')
                 ->set('typeFilter', 'text');

        // 清除篩選
        $component->call('clearFilters')
                 ->assertSet('search', '')
                 ->assertSet('categoryFilter', 'all')
                 ->assertSet('changedFilter', 'all')
                 ->assertSet('typeFilter', 'all');
    }

    /** @test */
    public function 監聽設定更新事件正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingsList::class);

        $component->dispatch('setting-updated', settingKey: 'app.name');
        
        // 事件處理應該正常執行（重新整理快取的計算屬性）
        $this->assertTrue(true); // 如果沒有異常就表示正常
    }

    /** @test */
    public function 監聽設定匯入完成事件正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingsList::class);

        $component->dispatch('settings-imported');
        
        // 檢查是否有成功訊息
        $this->assertTrue($component->instance()->hasFlashMessage('success'));
    }

    /** @test */
    public function 監聽備份建立完成事件正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingsList::class);

        $component->dispatch('backup-created');
        
        // 檢查是否有成功訊息
        $this->assertTrue($component->instance()->hasFlashMessage('success'));
    }

    /** @test */
    public function 分類相關方法正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingsList::class);

        // 測試取得分類圖示
        $icon = $component->instance()->getCategoryIcon('basic');
        $this->assertEquals('cog', $icon);

        // 測試取得分類名稱
        $name = $component->instance()->getCategoryName('basic');
        $this->assertEquals('基本設定', $name);

        // 測試取得分類描述
        $description = $component->instance()->getCategoryDescription('basic');
        $this->assertEquals('應用程式基本設定', $description);

        // 測試檢查分類是否展開
        $isExpanded = $component->instance()->isCategoryExpanded('basic');
        $this->assertTrue($isExpanded);

        // 測試檢查設定是否選中
        $component->call('toggleSettingSelection', 'app.name');
        $isSelected = $component->instance()->isSettingSelected('app.name');
        $this->assertTrue($isSelected);
    }

    /** @test */
    public function 統計資訊計算正確()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingsList::class);

        $stats = $component->get('stats');
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('changed', $stats);
        $this->assertArrayHasKey('categories', $stats);
        $this->assertArrayHasKey('filtered', $stats);
    }

    /** @test */
    public function 計算屬性返回正確的資料類型()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingsList::class);

        // 測試 settings 計算屬性
        $settings = $component->get('settings');
        $this->assertInstanceOf(Collection::class, $settings);

        // 測試 settingsByCategory 計算屬性
        $settingsByCategory = $component->get('settingsByCategory');
        $this->assertInstanceOf(Collection::class, $settingsByCategory);

        // 測試 categories 計算屬性
        $categories = $component->get('categories');
        $this->assertIsArray($categories);

        // 測試 availableTypes 計算屬性
        $availableTypes = $component->get('availableTypes');
        $this->assertInstanceOf(Collection::class, $availableTypes);

        // 測試 changedSettings 計算屬性
        $changedSettings = $component->get('changedSettings');
        $this->assertInstanceOf(Collection::class, $changedSettings);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}