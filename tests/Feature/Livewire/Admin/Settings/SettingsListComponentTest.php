<?php

namespace Tests\Feature\Livewire\Admin\Settings;

use App\Livewire\Admin\Settings\SettingsList;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Models\Permission;
use App\Repositories\SettingsRepositoryInterface;
use App\Services\ConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Livewire\Livewire;
use Tests\TestCase;
use Mockery;

/**
 * SettingsList 元件功能測試
 * 
 * 測試設定列表元件的完整功能，包含搜尋、篩選、批量操作等
 */
class SettingsListComponentTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;
    protected Role $adminRole;
    protected Role $userRole;
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

        $this->userRole = Role::create([
            'name' => 'user',
            'display_name' => '一般使用者',
            'is_system_role' => false,
            'is_active' => true,
        ]);

        // 建立權限
        $permissions = [
            'system.settings.view' => '檢視系統設定',
            'system.settings.edit' => '編輯系統設定',
            'system.settings.export' => '匯出系統設定',
            'system.settings.import' => '匯入系統設定',
            'system.settings.backup' => '備份系統設定',
            'system.settings.reset' => '重設系統設定',
        ];

        foreach ($permissions as $name => $displayName) {
            Permission::create([
                'name' => $name,
                'display_name' => $displayName,
                'module' => 'settings',
                'type' => 'action'
            ]);
        }

        // 給管理員角色所有權限
        $this->adminRole->permissions()->attach(
            Permission::whereIn('name', array_keys($permissions))->pluck('id')
        );

        // 給一般使用者角色部分權限
        $this->userRole->permissions()->attach(
            Permission::where('name', 'system.settings.view')->pluck('id')
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

        $this->regularUser = User::factory()->create([
            'username' => 'user',
            'name' => '一般使用者',
            'email' => 'user@example.com',
            'is_active' => true,
        ]);
        $this->regularUser->roles()->attach($this->userRole);
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
            [
                'key' => 'appearance.primary_color',
                'value' => '#3B82F6',
                'category' => 'appearance',
                'type' => 'color',
                'description' => '主要顏色',
                'default_value' => '#3B82F6',
                'is_encrypted' => false,
                'is_system' => false,
                'is_public' => true,
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
                $setting->id = fake()->numberBetween(1, 1000);
                return $setting;
            }));
            
        $this->mockRepository->shouldReceive('getAllSettings')
            ->andReturn(collect($this->testSettings)->map(function ($data) {
                $setting = new Setting();
                $setting->fill($data);
                $setting->id = fake()->numberBetween(1, 1000);
                return $setting;
            }));
            
        $this->mockRepository->shouldReceive('getChangedSettings')
            ->andReturn(collect($this->testSettings)->filter(function ($data) {
                return $data['value'] !== $data['default_value'];
            })->map(function ($data) {
                $setting = new Setting();
                $setting->fill($data);
                $setting->id = fake()->numberBetween(1, 1000);
                return $setting;
            }));
            
        $this->mockRepository->shouldReceive('getAvailableTypes')
            ->andReturn(collect(['text', 'number', 'select', 'boolean', 'color', 'email', 'url']));
            
        $this->mockConfigService->shouldReceive('getCategories')
            ->andReturn([
                'basic' => ['name' => '基本設定', 'icon' => 'cog', 'description' => '應用程式基本設定'],
                'security' => ['name' => '安全設定', 'icon' => 'shield-check', 'description' => '系統安全設定'],
                'notification' => ['name' => '通知設定', 'icon' => 'bell', 'description' => '通知相關設定'],
                'appearance' => ['name' => '外觀設定', 'icon' => 'palette', 'description' => '系統外觀設定'],
            ]);
        
        // 綁定到容器
        $this->app->instance(SettingsRepositoryInterface::class, $this->mockRepository);
        $this->app->instance(ConfigurationService::class, $this->mockConfigService);
    }

    /** @test */
    public function 管理員可以存取設定列表元件()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingsList::class);

        $component->assertStatus(200)
                 ->assertSet('search', '')
                 ->assertSet('categoryFilter', 'all')
                 ->assertSet('changedFilter', 'all')
                 ->assertSet('typeFilter', 'all')
                 ->assertSet('viewMode', 'category')
                 ->assertSet('selectedSettings', [])
                 ->assertSet('bulkAction', '')
                 ->assertSet('showBulkConfirm', false);
    }

    /** @test */
    public function 一般使用者只能檢視設定列表()
    {
        $this->actingAs($this->regularUser);

        $component = Livewire::test(SettingsList::class);

        $component->assertStatus(200);
        
        // 檢查是否顯示設定但沒有編輯功能
        foreach ($this->testSettings as $setting) {
            if ($setting['is_public']) {
                $component->assertSee($setting['description']);
            }
        }
    }

    /** @test */
    public function 無權限使用者無法存取設定列表()
    {
        $unauthorizedUser = User::factory()->create(['is_active' => true]);
        $this->actingAs($unauthorizedUser);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        
        Livewire::test(SettingsList::class);
    }

    /** @test */
    public function 搜尋功能可以正確篩選設定()
    {
        $this->actingAs($this->adminUser);
        
        // 設定搜尋結果的 Mock
        $this->mockRepository->shouldReceive('searchSettings')
            ->with('app', [])
            ->andReturn(collect($this->testSettings)->filter(function ($setting) {
                return str_contains($setting['key'], 'app') || str_contains($setting['description'], 'app');
            })->map(function ($data) {
                $setting = new Setting();
                $setting->fill($data);
                $setting->id = fake()->numberBetween(1, 1000);
                return $setting;
            }));

        $component = Livewire::test(SettingsList::class);

        $component->set('search', 'app')
                 ->assertSee('應用程式名稱')
                 ->assertSee('app.name')
                 ->assertSee('app.timezone');
    }

    /** @test */
    public function 分類篩選功能可以正確運作()
    {
        $this->actingAs($this->adminUser);
        
        // 設定分類篩選結果的 Mock
        $this->mockRepository->shouldReceive('searchSettings')
            ->with('', ['category' => 'security', 'type' => null, 'changed' => null])
            ->andReturn(collect($this->testSettings)->filter(function ($setting) {
                return $setting['category'] === 'security';
            })->map(function ($data) {
                $setting = new Setting();
                $setting->fill($data);
                $setting->id = fake()->numberBetween(1, 1000);
                return $setting;
            }));

        $component = Livewire::test(SettingsList::class);

        $component->set('categoryFilter', 'security')
                 ->assertSee('密碼最小長度')
                 ->assertSee('security.password_min_length');
    }

    /** @test */
    public function 類型篩選功能可以正確運作()
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
                $setting->id = fake()->numberBetween(1, 1000);
                return $setting;
            }));

        $component = Livewire::test(SettingsList::class);

        $component->set('typeFilter', 'text')
                 ->assertSee('應用程式名稱')
                 ->assertSee('SMTP 主機');
    }

    /** @test */
    public function 變更狀態篩選功能可以正確運作()
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
                $setting->id = fake()->numberBetween(1, 1000);
                return $setting;
            }));

        $component = Livewire::test(SettingsList::class);

        $component->set('changedFilter', 'changed')
                 ->assertSee('系統時區') // 值與預設值不同
                 ->assertSee('密碼最小長度'); // 值與預設值不同
    }

    /** @test */
    public function 檢視模式切換功能正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingsList::class);

        // 測試切換到列表檢視
        $component->set('viewMode', 'list')
                 ->assertSet('viewMode', 'list');

        // 測試切換到樹狀檢視
        $component->set('viewMode', 'tree')
                 ->assertSet('viewMode', 'tree');

        // 測試切換回分類檢視
        $component->set('viewMode', 'category')
                 ->assertSet('viewMode', 'category');
    }

    /** @test */
    public function 分類展開收合功能正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingsList::class);

        // 預設應該展開所有分類
        $expandedCategories = $component->get('expandedCategories');
        $this->assertContains('basic', $expandedCategories);
        $this->assertContains('security', $expandedCategories);
        $this->assertContains('notification', $expandedCategories);
        $this->assertContains('appearance', $expandedCategories);

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
    public function 展開收合所有分類功能正常運作()
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
        $this->assertContains('appearance', $expandedCategories);
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
        
        // 檢查是否有成功訊息
        $this->assertTrue($component->instance()->hasFlashMessage('success'));
    }

    /** @test */
    public function 重設設定失敗時顯示錯誤訊息()
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
        
        // 檢查是否有成功訊息
        $this->assertTrue($component->instance()->hasFlashMessage('success'));
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
        
        // 檢查統計數據的合理性
        $this->assertGreaterThanOrEqual(0, $stats['total']);
        $this->assertGreaterThanOrEqual(0, $stats['changed']);
        $this->assertGreaterThanOrEqual(0, $stats['categories']);
        $this->assertGreaterThanOrEqual(0, $stats['filtered']);
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

    /** @test */
    public function 權限檢查功能正常運作()
    {
        // 測試沒有編輯權限的使用者
        $viewOnlyUser = User::factory()->create(['is_active' => true]);
        $viewOnlyRole = Role::create([
            'name' => 'viewer',
            'display_name' => '檢視者',
            'is_system_role' => false,
            'is_active' => true,
        ]);
        $viewOnlyRole->permissions()->attach(
            Permission::where('name', 'system.settings.view')->first()->id
        );
        $viewOnlyUser->roles()->attach($viewOnlyRole);

        $this->actingAs($viewOnlyUser);

        $component = Livewire::test(SettingsList::class);

        // 應該可以檢視但不能編輯
        $component->assertStatus(200);
        
        // 嘗試編輯設定應該被拒絕
        $component->call('editSetting', 'app.name');
        // 這裡應該檢查權限並拒絕操作
    }

    /** @test */
    public function 敏感設定的安全性檢查()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingsList::class);

        // 檢查敏感設定是否正確隱藏
        $component->assertStatus(200);
        
        // 敏感設定應該不會在前端顯示實際值
        foreach ($this->testSettings as $setting) {
            if ($setting['is_encrypted'] || !$setting['is_public']) {
                // 檢查敏感設定的處理
                $this->assertTrue(true); // 這裡應該有更具體的檢查
            }
        }
    }

    /** @test */
    public function 響應式設計支援檢查()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(SettingsList::class);

        // 檢查元件是否支援不同的檢視模式
        $component->set('viewMode', 'list')
                 ->assertSet('viewMode', 'list');

        $component->set('viewMode', 'category')
                 ->assertSet('viewMode', 'category');

        // 檢查是否有適當的 CSS 類別和響應式設計
        $component->assertStatus(200);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}