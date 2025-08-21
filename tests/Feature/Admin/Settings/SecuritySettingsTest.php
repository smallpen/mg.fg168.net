<?php

namespace Tests\Feature\Admin\Settings;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Setting;
use App\Livewire\Admin\Settings\SecuritySettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

/**
 * 安全設定測試
 */
class SecuritySettingsTest extends TestCase
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

        // 暫時停用觀察者以避免權限檢查循環
        \App\Models\Permission::unsetEventDispatcher();

        // 建立管理員角色和使用者
        $adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理員',
            'description' => '系統管理員',
        ]);

        // 建立必要的權限
        $permission = \App\Models\Permission::create([
            'name' => 'settings.security',
            'display_name' => '安全設定管理',
            'description' => '管理系統安全設定',
            'module' => 'settings',
            'type' => 'manage',
        ]);

        // 將權限指派給角色
        $adminRole->permissions()->attach($permission);

        $this->admin = User::factory()->create([
            'username' => 'admin',
            'name' => '系統管理員',
            'email' => 'admin@example.com',
            'is_active' => true,
        ]);

        $this->admin->roles()->attach($adminRole);

        // 建立預設安全設定
        $this->createDefaultSecuritySettings();
    }

    /**
     * 建立預設安全設定
     */
    protected function createDefaultSecuritySettings(): void
    {
        $settings = [
            'security.password_min_length' => 8,
            'security.password_require_uppercase' => true,
            'security.password_require_lowercase' => true,
            'security.password_require_numbers' => true,
            'security.password_require_symbols' => false,
            'security.password_expiry_days' => 0,
            'security.login_max_attempts' => 5,
            'security.lockout_duration' => 15,
            'security.session_lifetime' => 120,
            'security.force_https' => false,
            'security.two_factor_enabled' => false,
        ];

        foreach ($settings as $key => $value) {
            Setting::create([
                'key' => $key,
                'value' => $value,
                'category' => 'security',
                'type' => is_bool($value) ? 'boolean' : 'number',
                'is_system' => true,
            ]);
        }
    }

    /**
     * 測試安全設定頁面載入
     */
    public function test_security_settings_page_loads(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.settings.security'));

        $response->assertStatus(200);
        $response->assertSeeLivewire(SecuritySettings::class);
    }

    /**
     * 測試安全設定元件渲染
     */
    public function test_security_settings_component_renders(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(SecuritySettings::class)
            ->assertStatus(200)
            ->assertSee('安全設定')
            ->assertSee('密碼政策')
            ->assertSee('登入安全')
            ->assertSee('Session 管理')
            ->assertSee('雙因子認證');
    }

    /**
     * 測試載入安全設定
     */
    public function test_loads_security_settings(): void
    {
        $this->actingAs($this->admin);

        // 直接測試元件的方法而不是渲染
        $component = new SecuritySettings();
        $component->mount();

        // 檢查設定值是否正確載入
        $this->assertEquals(8, $component->settings['security.password_min_length'] ?? null);
        $this->assertTrue($component->settings['security.password_require_uppercase'] ?? false);
        $this->assertTrue($component->settings['security.password_require_lowercase'] ?? false);
        $this->assertTrue($component->settings['security.password_require_numbers'] ?? false);
        $this->assertFalse($component->settings['security.password_require_symbols'] ?? true);
        $this->assertEquals(0, $component->settings['security.password_expiry_days'] ?? null);
        $this->assertEquals(5, $component->settings['security.login_max_attempts'] ?? null);
        $this->assertEquals(15, $component->settings['security.lockout_duration'] ?? null);
        $this->assertEquals(120, $component->settings['security.session_lifetime'] ?? null);
        $this->assertFalse($component->settings['security.force_https'] ?? true);
        $this->assertFalse($component->settings['security.two_factor_enabled'] ?? true);
    }

    /**
     * 測試密碼強度計算
     */
    public function test_password_strength_calculation(): void
    {
        $this->actingAs($this->admin);

        $component = new SecuritySettings();
        $component->mount();

        // 預設設定應該是中等強度
        $strength = $component->passwordStrengthLevel();
        $this->assertEquals('medium', $strength);

        // 啟用特殊字元要求，強度應該提升
        $component->settings['security.password_require_symbols'] = true;
        $strength = $component->passwordStrengthLevel();
        $this->assertEquals('strong', $strength);

        // 降低最小長度和要求，強度應該降低
        $component->settings['security.password_min_length'] = 6;
        $component->settings['security.password_require_uppercase'] = false;
        $component->settings['security.password_require_lowercase'] = false;
        $strength = $component->passwordStrengthLevel();
        $this->assertEquals('weak', $strength);
    }

    /**
     * 測試儲存安全設定
     */
    public function test_saves_security_settings(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(SecuritySettings::class);

        // 修改設定
        $component->set('settings.security.password_min_length', 10);
        $component->set('settings.security.password_require_symbols', true);
        $component->set('settings.security.login_max_attempts', 3);
        $component->set('settings.security.session_lifetime', 60);
        $component->set('settings.security.two_factor_enabled', true);

        // 儲存設定
        $component->call('save');

        // 檢查資料庫是否更新
        $this->assertDatabaseHas('settings', [
            'key' => 'security.password_min_length',
            'value' => '10'
        ]);

        $this->assertDatabaseHas('settings', [
            'key' => 'security.password_require_symbols',
            'value' => '1'
        ]);

        $this->assertDatabaseHas('settings', [
            'key' => 'security.login_max_attempts',
            'value' => '3'
        ]);

        $this->assertDatabaseHas('settings', [
            'key' => 'security.session_lifetime',
            'value' => '60'
        ]);

        $this->assertDatabaseHas('settings', [
            'key' => 'security.two_factor_enabled',
            'value' => '1'
        ]);

        // 檢查成功訊息
        $component->assertHasNoErrors();
        $component->assertDispatched('security-settings-updated');
    }

    /**
     * 測試設定驗證
     */
    public function test_validates_security_settings(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(SecuritySettings::class);

        // 測試無效的密碼最小長度
        $component->set('settings.security.password_min_length', 25); // 超過最大值
        $component->call('save');
        $this->assertNotEmpty($component->get('validationErrors'));

        // 測試無效的登入失敗次數
        $component->set('settings.security.password_min_length', 8); // 重設為有效值
        $component->set('settings.security.login_max_attempts', 15); // 超過最大值
        $component->call('save');
        $this->assertNotEmpty($component->get('validationErrors'));

        // 測試無效的 Session 過期時間
        $component->set('settings.security.login_max_attempts', 5); // 重設為有效值
        $component->set('settings.security.session_lifetime', 2000); // 超過最大值
        $component->call('save');
        $this->assertNotEmpty($component->get('validationErrors'));
    }

    /**
     * 測試重設單一設定
     */
    public function test_resets_single_setting(): void
    {
        $this->actingAs($this->admin);

        // 修改設定值
        $setting = Setting::where('key', 'security.password_min_length')->first();
        $setting->update(['value' => 12]);

        $component = Livewire::test(SecuritySettings::class);

        // 重設設定
        $component->call('resetSetting', 'security.password_min_length');

        // 檢查設定是否重設為預設值
        $this->assertDatabaseHas('settings', [
            'key' => 'security.password_min_length',
            'value' => '8' // 預設值
        ]);

        // 檢查元件狀態
        $this->assertEquals(8, $component->get('settings.security.password_min_length'));
    }

    /**
     * 測試重設所有設定
     */
    public function test_resets_all_settings(): void
    {
        $this->actingAs($this->admin);

        // 修改多個設定值
        Setting::where('key', 'security.password_min_length')->update(['value' => 12]);
        Setting::where('key', 'security.login_max_attempts')->update(['value' => 8]);
        Setting::where('key', 'security.two_factor_enabled')->update(['value' => true]);

        $component = Livewire::test(SecuritySettings::class);

        // 重設所有設定
        $component->call('resetAll');

        // 檢查所有設定是否重設為預設值
        $this->assertDatabaseHas('settings', [
            'key' => 'security.password_min_length',
            'value' => '8'
        ]);

        $this->assertDatabaseHas('settings', [
            'key' => 'security.login_max_attempts',
            'value' => '5'
        ]);

        $this->assertDatabaseHas('settings', [
            'key' => 'security.two_factor_enabled',
            'value' => '0'
        ]);
    }

    /**
     * 測試影響範圍檢查
     */
    public function test_checks_security_impact(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(SecuritySettings::class);

        // 啟用強制 HTTPS
        $component->set('settings.security.force_https', true);

        // 檢查是否顯示影響範圍警告
        $this->assertTrue($component->get('showImpactWarning'));
        $this->assertNotEmpty($component->get('impactInfo'));

        // 檢查影響範圍資訊
        $impactInfo = $component->get('impactInfo');
        $this->assertContains('強制 HTTPS 啟用', array_column($impactInfo, 'title'));
    }

    /**
     * 測試變更檢測
     */
    public function test_detects_changes(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(SecuritySettings::class);

        // 初始狀態應該沒有變更
        $this->assertFalse($component->get('hasChanges'));

        // 修改設定
        $component->set('settings.security.password_min_length', 10);

        // 應該檢測到變更
        $this->assertTrue($component->get('hasChanges'));

        // 檢查變更的設定
        $changedSettings = $component->get('changedSettings');
        $this->assertArrayHasKey('security.password_min_length', $changedSettings);
        $this->assertEquals(8, $changedSettings['security.password_min_length']['old']);
        $this->assertEquals(10, $changedSettings['security.password_min_length']['new']);
    }

    /**
     * 測試權限檢查
     */
    public function test_requires_permission(): void
    {
        // 建立沒有權限的使用者
        $user = User::factory()->create();

        $this->actingAs($user);

        // 應該無法存取安全設定頁面
        $response = $this->get(route('admin.settings.security'));
        $response->assertStatus(403);
    }

    /**
     * 測試安全設定元件可以直接實例化
     */
    public function test_security_settings_component_can_be_instantiated(): void
    {
        $this->actingAs($this->admin);
        
        $component = new SecuritySettings();
        $this->assertInstanceOf(SecuritySettings::class, $component);
    }
}