<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Setting;
use App\Helpers\DateTimeHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

/**
 * 基本設定功能簡單測試
 */
class BasicSettingsSimpleTest extends TestCase
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
        
        // 建立權限和角色
        $this->createPermissionsAndRoles();
        
        // 建立基本設定
        $this->createBasicSettings();
    }

    /**
     * 建立權限和角色
     */
    protected function createPermissionsAndRoles(): void
    {
        // 建立系統設定權限
        $permission = \App\Models\Permission::create([
            'name' => 'system.settings',
            'display_name' => '系統設定管理',
            'description' => '管理系統設定',
            'category' => 'system',
        ]);

        // 建立管理員角色
        $role = \App\Models\Role::create([
            'name' => 'admin',
            'display_name' => '系統管理員',
            'description' => '系統管理員角色',
        ]);

        // 指派權限給角色
        $role->permissions()->attach($permission->id);

        // 指派角色給使用者
        $this->admin->roles()->attach($role->id);
    }

    /**
     * 建立基本設定資料
     */
    protected function createBasicSettings(): void
    {
        $basicSettings = [
            [
                'key' => 'app.name',
                'value' => 'Laravel Admin System',
                'category' => 'basic',
                'type' => 'text',
                'default_value' => 'Laravel Admin System',
                'description' => '應用程式名稱',
            ],
            [
                'key' => 'app.description',
                'value' => '功能完整的管理系統',
                'category' => 'basic',
                'type' => 'textarea',
                'default_value' => '功能完整的管理系統',
                'description' => '應用程式描述',
            ],
            [
                'key' => 'app.timezone',
                'value' => 'Asia/Taipei',
                'category' => 'basic',
                'type' => 'select',
                'default_value' => 'Asia/Taipei',
                'description' => '系統時區',
            ],
            [
                'key' => 'app.locale',
                'value' => 'zh_TW',
                'category' => 'basic',
                'type' => 'select',
                'default_value' => 'zh_TW',
                'description' => '預設語言',
            ],
            [
                'key' => 'app.date_format',
                'value' => 'Y-m-d',
                'category' => 'basic',
                'type' => 'select',
                'default_value' => 'Y-m-d',
                'description' => '日期格式',
            ],
            [
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
     * 測試基本設定路由存在
     */
    public function test_basic_settings_route_exists(): void
    {
        // 測試路由是否存在
        $this->assertTrue(route('admin.settings.basic') !== null);
        
        // 測試路由指向正確的元件
        $route = \Illuminate\Support\Facades\Route::getRoutes()->getByName('admin.settings.basic');
        $this->assertNotNull($route);
        $this->assertStringContains('BasicSettings', $route->getActionName());
    }

    /**
     * 測試設定模型功能
     */
    public function test_setting_model_works(): void
    {
        $setting = Setting::where('key', 'app.name')->first();
        
        $this->assertNotNull($setting);
        $this->assertEquals('Laravel Admin System', $setting->value);
        $this->assertEquals('basic', $setting->category);
        
        // 測試更新設定值
        $setting->updateValue('新的應用程式名稱');
        $this->assertEquals('新的應用程式名稱', $setting->fresh()->value);
        
        // 測試重設為預設值
        $setting->resetToDefault();
        $this->assertEquals('Laravel Admin System', $setting->fresh()->value);
    }

    /**
     * 測試日期時間輔助類別
     */
    public function test_datetime_helper_works(): void
    {
        // 設定日期時間格式
        Cache::put('app.date_format', 'Y-m-d', 3600);
        Cache::put('app.time_format', 'H:i', 3600);
        
        $now = now();
        
        // 測試日期格式化
        $formattedDate = DateTimeHelper::formatDate($now);
        $this->assertEquals($now->format('Y-m-d'), $formattedDate);
        
        // 測試時間格式化
        $formattedTime = DateTimeHelper::formatTime($now);
        $this->assertEquals($now->format('H:i'), $formattedTime);
        
        // 測試日期時間格式化
        $formattedDateTime = DateTimeHelper::formatDateTime($now);
        $this->assertEquals($now->format('Y-m-d H:i'), $formattedDateTime);
    }

    /**
     * 測試設定資料庫操作
     */
    public function test_settings_repository_operations(): void
    {
        $repository = app(\App\Repositories\SettingsRepositoryInterface::class);
        
        // 測試取得設定
        $setting = $repository->getSetting('app.name');
        $this->assertNotNull($setting);
        $this->assertEquals('Laravel Admin System', $setting->value);
        
        // 測試更新設定
        $result = $repository->updateSetting('app.name', '測試應用程式');
        $this->assertTrue($result);
        
        // 驗證更新結果
        $updatedSetting = $repository->getSetting('app.name');
        $this->assertEquals('測試應用程式', $updatedSetting->value);
        
        // 測試重設設定
        $resetResult = $repository->resetSetting('app.name');
        $this->assertTrue($resetResult);
        
        // 驗證重設結果
        $resetSetting = $repository->getSetting('app.name');
        $this->assertEquals('Laravel Admin System', $resetSetting->value);
    }

    /**
     * 測試配置服務
     */
    public function test_configuration_service_works(): void
    {
        $configService = app(\App\Services\ConfigurationService::class);
        
        // 測試取得設定配置
        $config = $configService->getSettingConfig('app.name');
        $this->assertNotEmpty($config);
        $this->assertEquals('basic', $config['category']);
        $this->assertEquals('text', $config['type']);
        
        // 測試取得分類
        $categories = $configService->getCategories();
        $this->assertArrayHasKey('basic', $categories);
        $this->assertEquals('基本設定', $categories['basic']['name']);
        
        // 測試驗證設定值
        $isValid = $configService->validateSettingValue('app.name', '有效的應用程式名稱');
        $this->assertTrue($isValid);
        
        $isInvalid = $configService->validateSettingValue('app.name', '');
        $this->assertFalse($isInvalid);
    }

    /**
     * 測試中介軟體快取清除
     */
    public function test_middleware_cache_clearing(): void
    {
        // 設定一些快取值
        Cache::put('basic_settings_applied', ['test' => 'data'], 300);
        Cache::put('app.date_format', 'old_format', 3600);
        
        // 清除快取
        \App\Http\Middleware\ApplyBasicSettings::clearCache();
        
        // 驗證快取已被清除
        $this->assertNull(Cache::get('basic_settings_applied'));
        
        // 日期格式快取應該還在（由其他方法管理）
        $this->assertEquals('old_format', Cache::get('app.date_format'));
    }

    /**
     * 測試 Blade 指令
     */
    public function test_blade_directives_work(): void
    {
        // 設定日期時間格式
        Cache::put('app.date_format', 'd/m/Y', 3600);
        Cache::put('app.time_format', 'g:i A', 3600);
        
        $now = now();
        
        // 測試日期格式化指令
        $formattedDate = DateTimeHelper::formatDate($now);
        $this->assertEquals($now->format('d/m/Y'), $formattedDate);
        
        // 測試時間格式化指令
        $formattedTime = DateTimeHelper::formatTime($now);
        $this->assertEquals($now->format('g:i A'), $formattedTime);
    }

    /**
     * 測試設定即時生效機制
     */
    public function test_settings_apply_immediately(): void
    {
        $repository = app(\App\Repositories\SettingsRepositoryInterface::class);
        
        // 更新時區設定
        $repository->updateSetting('app.timezone', 'UTC');
        
        // 清除中介軟體快取以觸發重新載入
        \App\Http\Middleware\ApplyBasicSettings::clearCache();
        
        // 模擬中介軟體應用設定
        $middleware = new \App\Http\Middleware\ApplyBasicSettings($repository);
        $request = request();
        
        $middleware->handle($request, function ($req) {
            return response('OK');
        });
        
        // 驗證時區已更新
        $this->assertEquals('UTC', Config::get('app.timezone'));
        $this->assertEquals('UTC', date_default_timezone_get());
    }

    /**
     * 清理測試環境
     */
    protected function tearDown(): void
    {
        // 清除快取
        Cache::flush();
        
        // 重設時區
        date_default_timezone_set('Asia/Taipei');
        
        parent::tearDown();
    }
}