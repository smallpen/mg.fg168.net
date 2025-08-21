<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Setting;
use App\Livewire\Admin\Settings\BasicSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;

/**
 * 基本設定功能測試
 */
class BasicSettingsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 管理員使用者
     */
    protected User $admin;

    /**
     * 設定測試環境
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立管理員使用者
        $this->admin = User::factory()->create([
            'username' => 'admin',
            'is_active' => true,
        ]);
        
        // 建立基本設定
        $this->createBasicSettings();
    }

    /**
     * 建立基本設定資料
     */
    protected function createBasicSettings(): void
    {
        $basicSettings = [
            'app.name' => [
                'key' => 'app.name',
                'value' => 'Laravel Admin System',
                'category' => 'basic',
                'type' => 'text',
                'default_value' => 'Laravel Admin System',
                'description' => '應用程式名稱',
            ],
            'app.description' => [
                'key' => 'app.description',
                'value' => '功能完整的管理系統',
                'category' => 'basic',
                'type' => 'textarea',
                'default_value' => '功能完整的管理系統',
                'description' => '應用程式描述',
            ],
            'app.timezone' => [
                'key' => 'app.timezone',
                'value' => 'Asia/Taipei',
                'category' => 'basic',
                'type' => 'select',
                'default_value' => 'Asia/Taipei',
                'description' => '系統時區',
            ],
            'app.locale' => [
                'key' => 'app.locale',
                'value' => 'zh_TW',
                'category' => 'basic',
                'type' => 'select',
                'default_value' => 'zh_TW',
                'description' => '預設語言',
            ],
            'app.date_format' => [
                'key' => 'app.date_format',
                'value' => 'Y-m-d',
                'category' => 'basic',
                'type' => 'select',
                'default_value' => 'Y-m-d',
                'description' => '日期格式',
            ],
            'app.time_format' => [
                'key' => 'app.time_format',
                'value' => 'H:i',
                'category' => 'basic',
                'type' => 'select',
                'default_value' => 'H:i',
                'description' => '時間格式',
            ],
        ];

        foreach ($basicSettings as $settingData) {
            Setting::create($settingData);
        }
    }

    /**
     * 測試基本設定元件載入
     */
    public function test_basic_settings_component_loads(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(BasicSettings::class)
            ->assertSuccessful()
            ->assertSee('基本設定')
            ->assertSee('應用程式資訊')
            ->assertSee('地區和語言')
            ->assertSee('日期時間格式');
    }

    /**
     * 測試設定值載入
     */
    public function test_settings_values_are_loaded(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(BasicSettings::class);
        $settings = $component->get('settings');

        // 驗證所有基本設定都已正確載入
        $this->assertEquals('Laravel Admin System', $settings['app.name']);
        $this->assertEquals('功能完整的管理系統', $settings['app.description']);
        $this->assertEquals('Asia/Taipei', $settings['app.timezone']);
        $this->assertEquals('zh_TW', $settings['app.locale']);
        $this->assertEquals('Y-m-d', $settings['app.date_format']);
        $this->assertEquals('H:i', $settings['app.time_format']);
    }

    /**
     * 測試更新應用程式名稱
     */
    public function test_can_update_app_name(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(BasicSettings::class);
        
        // 檢查初始狀態
        dump('Initial settings:', $component->get('settings'));
        dump('Initial original settings:', $component->get('originalSettings'));
        
        $component->set('settings.app.name', '新的應用程式名稱');
        
        // 檢查設定後的狀態
        dump('After set settings:', $component->get('settings'));
        dump('After set original settings:', $component->get('originalSettings'));
        dump('Has changes after set:', $component->get('hasChanges'));
        
        $component->call('save');

        // 檢查是否有錯誤
        if ($component->get('validationErrors')) {
            dump('Validation errors:', $component->get('validationErrors'));
        }

        // 檢查 saving 狀態
        dump('Saving state:', $component->get('saving'));
        dump('Has changes:', $component->get('hasChanges'));
        dump('Changed settings:', $component->get('changedSettings'));

        $component->assertHasNoErrors()
                  ->assertDispatched('basic-settings-updated');

        // 驗證資料庫中的值已更新
        $this->assertDatabaseHas('settings', [
            'key' => 'app.name',
            'value' => '"新的應用程式名稱"',
        ]);

        // 驗證配置已更新
        $this->assertEquals('新的應用程式名稱', Config::get('app.name'));
    }

    /**
     * 測試更新時區設定
     */
    public function test_can_update_timezone(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(BasicSettings::class)
            ->set('settings.app.timezone', 'Asia/Tokyo')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('basic-settings-updated');

        // 驗證資料庫中的值已更新
        $this->assertDatabaseHas('settings', [
            'key' => 'app.timezone',
            'value' => '"Asia/Tokyo"',
        ]);

        // 驗證時區已更新
        $this->assertEquals('Asia/Tokyo', Config::get('app.timezone'));
        $this->assertEquals('Asia/Tokyo', date_default_timezone_get());
    }

    /**
     * 測試更新語言設定
     */
    public function test_can_update_locale(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(BasicSettings::class)
            ->set('settings.app.locale', 'en')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('basic-settings-updated');

        // 驗證資料庫中的值已更新
        $this->assertDatabaseHas('settings', [
            'key' => 'app.locale',
            'value' => '"en"',
        ]);

        // 驗證語言設定已更新
        $this->assertEquals('en', Config::get('app.locale'));
    }

    /**
     * 測試更新日期格式
     */
    public function test_can_update_date_format(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(BasicSettings::class)
            ->set('settings.app.date_format', 'd/m/Y')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('basic-settings-updated');

        // 驗證資料庫中的值已更新
        $this->assertDatabaseHas('settings', [
            'key' => 'app.date_format',
            'value' => '"d/m/Y"',
        ]);

        // 驗證快取已更新
        $this->assertEquals('d/m/Y', Cache::get('app.date_format'));
    }

    /**
     * 測試更新時間格式
     */
    public function test_can_update_time_format(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(BasicSettings::class)
            ->set('settings.app.time_format', 'g:i A')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('basic-settings-updated');

        // 驗證資料庫中的值已更新
        $this->assertDatabaseHas('settings', [
            'key' => 'app.time_format',
            'value' => '"g:i A"',
        ]);

        // 驗證快取已更新
        $this->assertEquals('g:i A', Cache::get('app.time_format'));
    }

    /**
     * 測試批量更新設定
     */
    public function test_can_update_multiple_settings(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(BasicSettings::class)
            ->set('settings.app.name', '批量測試應用程式')
            ->set('settings.app.timezone', 'UTC')
            ->set('settings.app.locale', 'en')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('basic-settings-updated');

        // 驗證所有設定都已更新
        $this->assertDatabaseHas('settings', [
            'key' => 'app.name',
            'value' => '"批量測試應用程式"',
        ]);

        $this->assertDatabaseHas('settings', [
            'key' => 'app.timezone',
            'value' => '"UTC"',
        ]);

        $this->assertDatabaseHas('settings', [
            'key' => 'app.locale',
            'value' => '"en"',
        ]);
    }

    /**
     * 測試重設單一設定
     */
    public function test_can_reset_single_setting(): void
    {
        $this->actingAs($this->admin);

        // 先更新設定
        $setting = Setting::where('key', 'app.name')->first();
        $setting->updateValue('修改後的名稱');

        Livewire::test(BasicSettings::class)
            ->call('resetSetting', 'app.name')
            ->assertHasNoErrors();

        // 驗證設定已重設為預設值
        $setting->refresh();
        $this->assertEquals('Laravel Admin System', $setting->value);
    }

    /**
     * 測試重設所有設定
     */
    public function test_can_reset_all_settings(): void
    {
        $this->actingAs($this->admin);

        // 先更新一些設定
        Setting::where('key', 'app.name')->first()->updateValue('修改後的名稱');
        Setting::where('key', 'app.timezone')->first()->updateValue('UTC');

        Livewire::test(BasicSettings::class)
            ->call('resetAll')
            ->assertHasNoErrors();

        // 驗證所有設定都已重設為預設值
        $this->assertDatabaseHas('settings', [
            'key' => 'app.name',
            'value' => '"Laravel Admin System"',
        ]);

        $this->assertDatabaseHas('settings', [
            'key' => 'app.timezone',
            'value' => '"Asia/Taipei"',
        ]);
    }

    /**
     * 測試設定驗證
     */
    public function test_validates_required_settings(): void
    {
        $this->actingAs($this->admin);

        // 測試空的應用程式名稱（假設它是必填的）
        Livewire::test(BasicSettings::class)
            ->set('settings.app.name', '')
            ->call('save')
            ->assertHasErrors();
    }

    /**
     * 測試時區測試功能
     */
    public function test_can_test_timezone(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(BasicSettings::class)
            ->set('settings.app.timezone', 'Asia/Tokyo')
            ->call('testTimezone')
            ->assertHasNoErrors();
    }

    /**
     * 測試變更檢測
     */
    public function test_detects_changes(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(BasicSettings::class);

        // 初始狀態應該沒有變更
        $this->assertFalse($component->get('hasChanges'));

        // 修改設定後應該檢測到變更
        $component->set('settings.app.name', '新名稱');
        $this->assertTrue($component->get('hasChanges'));

        // 儲存後應該沒有變更
        $component->call('save');
        $this->assertFalse($component->get('hasChanges'));
    }

    /**
     * 測試預覽功能
     */
    public function test_preview_functionality(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(BasicSettings::class)
            ->set('settings.app.name', '預覽名稱')
            ->call('startPreview')
            ->assertSet('showPreview', true)
            ->assertDispatched('basic-settings-preview-start')
            ->call('stopPreview')
            ->assertSet('showPreview', false)
            ->assertDispatched('basic-settings-preview-stop');
    }

    /**
     * 測試快取清除
     */
    public function test_cache_is_cleared_after_update(): void
    {
        $this->actingAs($this->admin);

        // 設定一些快取值
        Cache::put('app.date_format', 'old_format', 3600);
        Cache::put('basic_settings_applied', ['old' => 'data'], 300);

        Livewire::test(BasicSettings::class)
            ->set('settings.app.date_format', 'd/m/Y')
            ->call('save');

        // 驗證快取已被清除
        $this->assertNull(Cache::get('basic_settings_applied'));
        $this->assertEquals('d/m/Y', Cache::get('app.date_format'));
    }

    /**
     * 測試路由存取
     */
    public function test_basic_settings_route_is_accessible(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.settings.basic'));
        
        $response->assertSuccessful()
                 ->assertSee('基本設定')
                 ->assertSee('應用程式資訊');
    }

    /**
     * 測試未授權存取
     */
    public function test_unauthorized_access_is_denied(): void
    {
        // 建立一般使用者
        $user = User::factory()->create(['is_active' => true]);
        
        $this->actingAs($user);

        $response = $this->get(route('admin.settings.basic'));
        
        // 應該被重新導向或返回 403
        $this->assertTrue(
            $response->status() === 403 || 
            $response->status() === 302
        );
    }

    /**
     * 清理測試環境
     */
    protected function tearDown(): void
    {
        // 清除快取
        Cache::flush();
        
        parent::tearDown();
    }
}