<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Livewire\Livewire;
use App\Livewire\Admin\Layout\ThemeToggle;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * 主題切換功能測試
 */
class ThemeToggleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試主題切換元件可以正常渲染
     */
    public function test_theme_toggle_component_can_render()
    {
        $user = User::factory()->create([
            'theme_preference' => 'light'
        ]);

        $this->actingAs($user);

        Livewire::test(ThemeToggle::class)
            ->assertStatus(200)
            ->assertSee('當前主題：亮色主題')
            ->assertSet('currentTheme', 'light');
    }

    /**
     * 測試可以切換到暗黑主題
     */
    public function test_can_toggle_to_dark_theme()
    {
        $user = User::factory()->create([
            'theme_preference' => 'light'
        ]);

        $this->actingAs($user);

        Livewire::test(ThemeToggle::class)
            ->call('toggleTheme')
            ->assertSet('currentTheme', 'dark')
            ->assertDispatched('theme-changed', theme: 'dark')
            ->assertDispatched('update-theme-class', theme: 'dark');

        // 驗證使用者偏好設定已更新
        $this->assertEquals('dark', $user->fresh()->theme_preference);
    }

    /**
     * 測試可以切換到淺色主題
     */
    public function test_can_toggle_to_light_theme()
    {
        $user = User::factory()->create([
            'theme_preference' => 'dark'
        ]);

        $this->actingAs($user);

        Livewire::test(ThemeToggle::class)
            ->call('toggleTheme')
            ->assertSet('currentTheme', 'light')
            ->assertDispatched('theme-changed', theme: 'light')
            ->assertDispatched('update-theme-class', theme: 'light');

        // 驗證使用者偏好設定已更新
        $this->assertEquals('light', $user->fresh()->theme_preference);
    }

    /**
     * 測試可以直接設定特定主題
     */
    public function test_can_set_specific_theme()
    {
        $user = User::factory()->create([
            'theme_preference' => 'light'
        ]);

        $this->actingAs($user);

        Livewire::test(ThemeToggle::class)
            ->call('setTheme', 'dark')
            ->assertSet('currentTheme', 'dark')
            ->assertDispatched('theme-changed', theme: 'dark');

        // 驗證使用者偏好設定已更新
        $this->assertEquals('dark', $user->fresh()->theme_preference);
    }

    /**
     * 測試無效的主題設定會被忽略
     */
    public function test_invalid_theme_setting_is_ignored()
    {
        $user = User::factory()->create([
            'theme_preference' => 'light'
        ]);

        $this->actingAs($user);

        Livewire::test(ThemeToggle::class)
            ->call('setTheme', 'invalid')
            ->assertSet('currentTheme', 'light');

        // 驗證使用者偏好設定沒有變更
        $this->assertEquals('light', $user->fresh()->theme_preference);
    }

    /**
     * 測試未登入使用者的主題切換
     */
    public function test_guest_user_theme_toggle()
    {
        Livewire::test(ThemeToggle::class)
            ->assertSet('currentTheme', 'light')
            ->call('toggleTheme')
            ->assertSet('currentTheme', 'dark')
            ->assertDispatched('theme-changed', theme: 'dark');
    }

    /**
     * 測試 isDarkTheme 方法
     */
    public function test_is_dark_theme_method()
    {
        $user = User::factory()->create([
            'theme_preference' => 'dark'
        ]);

        $this->actingAs($user);

        $component = Livewire::test(ThemeToggle::class);
        
        $this->assertTrue($component->instance()->isDarkTheme());

        $component->call('setTheme', 'light');
        
        $this->assertFalse($component->instance()->isDarkTheme());
    }

    /**
     * 測試主題偏好設定的載入
     */
    public function test_theme_preference_loading()
    {
        // 測試暗黑主題使用者
        $darkUser = User::factory()->create([
            'theme_preference' => 'dark'
        ]);

        $this->actingAs($darkUser);

        Livewire::test(ThemeToggle::class)
            ->assertSet('currentTheme', 'dark');

        // 測試淺色主題使用者
        $lightUser = User::factory()->create([
            'theme_preference' => 'light'
        ]);

        $this->actingAs($lightUser);

        Livewire::test(ThemeToggle::class)
            ->assertSet('currentTheme', 'light');

        // 測試沒有偏好設定的使用者（預設為淺色）
        $defaultUser = User::factory()->create([
            'theme_preference' => null
        ]);

        $this->actingAs($defaultUser);

        Livewire::test(ThemeToggle::class)
            ->assertSet('currentTheme', 'light');
    }
}