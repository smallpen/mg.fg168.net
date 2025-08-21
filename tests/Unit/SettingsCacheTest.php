<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Repositories\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SettingsCacheTest extends TestCase
{
    use RefreshDatabase;

    protected SettingsRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = new SettingsRepository();
    }

    public function test_settings_are_cached_when_retrieved(): void
    {
        // 建立測試設定
        $setting = Setting::factory()->create(['key' => 'test.cached_setting']);

        // 第一次取得設定（應該會建立快取）
        $result1 = $this->repository->getSetting('test.cached_setting');
        
        // 檢查快取是否存在
        $this->assertTrue(Cache::has('settings_key_test.cached_setting'));
        
        // 第二次取得設定（應該從快取取得）
        $result2 = $this->repository->getSetting('test.cached_setting');
        
        $this->assertEquals($result1->id, $result2->id);
    }

    public function test_cache_is_cleared_when_setting_updated(): void
    {
        // 建立測試設定
        $setting = Setting::factory()->create([
            'key' => 'test.cache_clear',
            'value' => 'original_value'
        ]);

        // 取得設定以建立快取
        $this->repository->getSetting('test.cache_clear');
        $this->assertTrue(Cache::has('settings_key_test.cache_clear'));

        // 更新設定
        $this->repository->updateSetting('test.cache_clear', 'new_value');

        // 檢查快取是否被清除
        $this->assertFalse(Cache::has('settings_key_test.cache_clear'));
    }

    public function test_can_manually_clear_specific_cache(): void
    {
        // 建立測試設定
        Setting::factory()->create(['key' => 'test.manual_clear']);

        // 取得設定以建立快取
        $this->repository->getSetting('test.manual_clear');
        $this->assertTrue(Cache::has('settings_key_test.manual_clear'));

        // 手動清除特定快取
        $this->repository->clearCache('test.manual_clear');

        // 檢查快取是否被清除
        $this->assertFalse(Cache::has('settings_key_test.manual_clear'));
    }

    public function test_can_clear_all_settings_cache(): void
    {
        // 建立多個測試設定
        Setting::factory()->create(['key' => 'test.cache1']);
        Setting::factory()->create(['key' => 'test.cache2']);

        // 取得設定以建立快取
        $this->repository->getSetting('test.cache1');
        $this->repository->getSetting('test.cache2');
        $this->repository->getAllSettings();

        // 檢查快取存在
        $this->assertTrue(Cache::has('settings_key_test.cache1'));
        $this->assertTrue(Cache::has('settings_key_test.cache2'));
        $this->assertTrue(Cache::has('settings_all'));

        // 清除所有快取
        $this->repository->clearCache();

        // 檢查所有快取都被清除
        $this->assertFalse(Cache::has('settings_key_test.cache1'));
        $this->assertFalse(Cache::has('settings_key_test.cache2'));
        $this->assertFalse(Cache::has('settings_all'));
    }

    public function test_cached_setting_values_work_correctly(): void
    {
        // 建立測試設定
        $setting = Setting::factory()->create([
            'key' => 'test.cached_value',
            'value' => 'test_value'
        ]);

        // 使用快取方法取得設定值
        $cachedValue = $this->repository->getCachedSetting('test.cached_value', 'default');
        
        $this->assertEquals('test_value', $cachedValue);

        // 測試不存在的設定返回預設值
        $defaultValue = $this->repository->getCachedSetting('nonexistent.setting', 'default_value');
        
        $this->assertEquals('default_value', $defaultValue);
    }

    public function test_can_set_cached_setting_value(): void
    {
        // 設定快取值
        $this->repository->setCachedSetting('test.manual_cache', 'cached_value', 60);

        // 檢查快取是否存在
        $this->assertTrue(Cache::has('settings_value_test.manual_cache'));
        
        // 檢查快取值是否正確
        $cachedValue = Cache::get('settings_value_test.manual_cache');
        $this->assertEquals('cached_value', $cachedValue);
    }

    public function test_category_cache_works_correctly(): void
    {
        // 建立不同分類的設定
        Setting::factory()->create(['key' => 'basic.setting1', 'category' => 'basic']);
        Setting::factory()->create(['key' => 'basic.setting2', 'category' => 'basic']);

        // 取得分類設定
        $basicSettings = $this->repository->getSettingsByCategory('basic');
        
        // 檢查分類快取是否存在
        $this->assertTrue(Cache::has('settings_category_basic'));
        
        $this->assertCount(2, $basicSettings);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}
