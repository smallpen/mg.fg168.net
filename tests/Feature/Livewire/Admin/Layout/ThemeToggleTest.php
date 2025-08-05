<?php

namespace Tests\Feature\Livewire\Admin\Layout;

use App\Http\Livewire\Admin\Layout\ThemeToggle;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * ThemeToggle 元件測試
 * 
 * 測試主題切換功能的渲染、狀態管理和偏好設定儲存
 */
class ThemeToggleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試角色
        $this->adminRole = Role::factory()->create(['name' => 'admin']);
        
        // 建立管理員使用者
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($this->adminRole);
    }

    /**
     * 測試元件能正確渲染
     */
    public function test_component_renders_correctly()
    {
        $this->actingAs($this->admin);

        Livewire::test(ThemeToggle::class)
            ->assertStatus(200)
            ->assertSee('主題切換')
            ->assertSee('淺色')
            ->assertSee('暗黑');
    }

    /**
     * 測試預設主題設定
     */
    public function test_default_theme_setting()
    {
        $this->actingAs($this->admin);

        Livewire::test(ThemeToggle::class)
            ->assertSet('currentTheme', 'light'); // 預設為淺色主題
    }

    /**
     * 測試主題切換功能
     */
    public function test_theme_toggle_functionality()
    {
        $this->actingAs($this->admin);

        Livewire::test(ThemeToggle::class)
            ->assertSet('currentTheme', 'light')
            ->call('toggleTheme')
            ->assertSet('currentTheme', 'dark')
            ->assertDispatched('theme-changed', ['theme' => 'dark'])
            ->call('toggleTheme')
            ->assertSet('currentTheme', 'light')
            ->assertDispatched('theme-changed', ['theme' => 'light']);
    }

    /**
     * 測試特定主題設定
     */
    public function test_specific_theme_setting()
    {
        $this->actingAs($this->admin);

        Livewire::test(ThemeToggle::class)
            ->call('setTheme', 'dark')
            ->assertSet('currentTheme', 'dark')
            ->assertDispatched('theme-changed', ['theme' => 'dark'])
            ->call('setTheme', 'light')
            ->assertSet('currentTheme', 'light')
            ->assertDispatched('theme-changed', ['theme' => 'light']);
    }

    /**
     * 測試主題偏好設定儲存
     */
    public function test_theme_preference_persistence()
    {
        $this->actingAs($this->admin);

        Livewire::test(ThemeToggle::class)
            ->call('setTheme', 'dark')
            ->assertDispatched('theme-saved');

        // 檢查使用者偏好設定是否被儲存
        $this->assertEquals('dark', $this->admin->fresh()->theme_preference);
    }

    /**
     * 測試從使用者偏好設定載入主題
     */
    public function test_load_theme_from_user_preference()
    {
        $this->admin->update(['theme_preference' => 'dark']);
        $this->actingAs($this->admin);

        Livewire::test(ThemeToggle::class)
            ->assertSet('currentTheme', 'dark');
    }

    /**
     * 測試系統主題偵測
     */
    public function test_system_theme_detection()
    {
        $this->actingAs($this->admin);

        Livewire::test(ThemeToggle::class)
            ->call('detectSystemTheme')
            ->assertDispatched('system-theme-detected');
    }

    /**
     * 測試主題圖示顯示
     */
    public function test_theme_icon_display()
    {
        $this->actingAs($this->admin);

        Livewire::test(ThemeToggle::class)
            ->assertSet('currentTheme', 'light')
            ->assertSee('sun') // 淺色主題顯示太陽圖示
            ->call('setTheme', 'dark')
            ->assertSee('moon'); // 暗黑主題顯示月亮圖示
    }

    /**
     * 測試主題切換動畫
     */
    public function test_theme_transition_animation()
    {
        $this->actingAs($this->admin);

        Livewire::test(ThemeToggle::class)
            ->call('toggleTheme')
            ->assertSet('isTransitioning', true)
            ->assertDispatched('theme-transition-start');
    }

    /**
     * 測試主題預覽功能
     */
    public function test_theme_preview()
    {
        $this->actingAs($this->admin);

        Livewire::test(ThemeToggle::class)
            ->call('previewTheme', 'dark')
            ->assertSet('previewTheme', 'dark')
            ->assertDispatched('theme-preview-start')
            ->call('applyPreview')
            ->assertSet('currentTheme', 'dark')
            ->call('cancelPreview')
            ->assertSet('previewTheme', null);
    }

    /**
     * 測試自動主題切換
     */
    public function test_automatic_theme_switching()
    {
        $this->actingAs($this->admin);

        Livewire::test(ThemeToggle::class)
            ->call('enableAutoTheme')
            ->assertSet('autoTheme', true)
            ->assertDispatched('auto-theme-enabled')
            ->call('disableAutoTheme')
            ->assertSet('autoTheme', false);
    }

    /**
     * 測試主題切換快捷鍵
     */
    public function test_theme_toggle_keyboard_shortcut()
    {
        $this->actingAs($this->admin);

        Livewire::test(ThemeToggle::class)
            ->call('handleKeyboardShortcut', 'ctrl+shift+t')
            ->assertSet('currentTheme', 'dark');
    }

    /**
     * 測試主題狀態廣播
     */
    public function test_theme_state_broadcast()
    {
        $this->actingAs($this->admin);

        Livewire::test(ThemeToggle::class)
            ->call('toggleTheme')
            ->assertDispatched('theme-changed')
            ->assertDispatched('update-css-variables');
    }

    /**
     * 測試主題相容性檢查
     */
    public function test_theme_compatibility_check()
    {
        $this->actingAs($this->admin);

        Livewire::test(ThemeToggle::class)
            ->call('checkThemeCompatibility')
            ->assertDispatched('theme-compatibility-checked');
    }

    /**
     * 測試自訂主題支援
     */
    public function test_custom_theme_support()
    {
        $this->actingAs($this->admin);

        Livewire::test(ThemeToggle::class)
            ->call('setCustomTheme', [
                'name' => 'custom',
                'colors' => ['primary' => '#ff0000']
            ])
            ->assertSet('currentTheme', 'custom')
            ->assertDispatched('custom-theme-applied');
    }

    /**
     * 測試主題重置功能
     */
    public function test_theme_reset()
    {
        $this->actingAs($this->admin);

        // 先設定為暗黑主題
        $this->admin->update(['theme_preference' => 'dark']);

        Livewire::test(ThemeToggle::class)
            ->call('resetTheme')
            ->assertSet('currentTheme', 'light')
            ->assertDispatched('theme-reset');

        // 檢查使用者偏好設定是否被重置
        $this->assertEquals('light', $this->admin->fresh()->theme_preference);
    }

    /**
     * 測試主題載入錯誤處理
     */
    public function test_theme_loading_error_handling()
    {
        $this->actingAs($this->admin);

        Livewire::test(ThemeToggle::class)
            ->call('setTheme', 'invalid_theme')
            ->assertHasErrors(['theme'])
            ->assertSee('無效的主題設定');
    }

    /**
     * 測試主題切換確認對話框
     */
    public function test_theme_change_confirmation()
    {
        $this->actingAs($this->admin);

        Livewire::test(ThemeToggle::class)
            ->set('requireConfirmation', true)
            ->call('toggleTheme')
            ->assertSee('確認切換主題')
            ->call('confirmThemeChange')
            ->assertSet('currentTheme', 'dark');
    }

    /**
     * 測試主題切換歷史記錄
     */
    public function test_theme_change_history()
    {
        $this->actingAs($this->admin);

        Livewire::test(ThemeToggle::class)
            ->call('toggleTheme')
            ->call('toggleTheme')
            ->call('showThemeHistory')
            ->assertSee('主題切換歷史')
            ->assertSee('light → dark')
            ->assertSee('dark → light');
    }

    /**
     * 測試主題同步功能
     */
    public function test_theme_synchronization()
    {
        $this->actingAs($this->admin);

        Livewire::test(ThemeToggle::class)
            ->call('syncThemeAcrossTabs')
            ->assertDispatched('sync-theme-across-tabs');
    }

    /**
     * 測試主題效能優化
     */
    public function test_theme_performance_optimization()
    {
        $this->actingAs($this->admin);

        Livewire::test(ThemeToggle::class)
            ->call('optimizeThemeLoading')
            ->assertDispatched('theme-optimized');
    }

    /**
     * 測試主題輔助功能
     */
    public function test_theme_accessibility()
    {
        $this->actingAs($this->admin);

        Livewire::test(ThemeToggle::class)
            ->assertSee('aria-label="切換主題"')
            ->call('enableHighContrast')
            ->assertSet('highContrast', true)
            ->assertDispatched('high-contrast-enabled');
    }

    /**
     * 測試主題本地化
     */
    public function test_theme_localization()
    {
        $this->actingAs($this->admin);

        app()->setLocale('en');

        Livewire::test(ThemeToggle::class)
            ->assertSee('Light')
            ->assertSee('Dark');

        app()->setLocale('zh_TW');

        Livewire::test(ThemeToggle::class)
            ->assertSee('淺色')
            ->assertSee('暗黑');
    }
}