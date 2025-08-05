<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Livewire\Livewire;
use App\Http\Livewire\Admin\Layout\ThemeToggle;

/**
 * 主題切換單元測試
 */
class ThemeToggleUnitTest extends TestCase
{
    /**
     * 測試主題切換元件的基本功能
     */
    public function test_theme_toggle_basic_functionality()
    {
        // 建立一個模擬使用者
        $user = new User([
            'username' => 'testuser',
            'name' => '測試使用者',
            'email' => 'test@example.com',
            'theme_preference' => 'light'
        ]);

        // 模擬認證
        $this->actingAs($user);

        // 測試元件初始化
        $component = new ThemeToggle();
        $component->mount();

        // 驗證初始主題
        $this->assertEquals('light', $component->currentTheme);

        // 測試主題切換
        $component->toggleTheme();
        $this->assertEquals('dark', $component->currentTheme);

        // 再次切換
        $component->toggleTheme();
        $this->assertEquals('light', $component->currentTheme);
    }

    /**
     * 測試設定特定主題
     */
    public function test_set_specific_theme()
    {
        $user = new User([
            'username' => 'testuser',
            'name' => '測試使用者',
            'email' => 'test@example.com',
            'theme_preference' => 'light'
        ]);

        $this->actingAs($user);

        $component = new ThemeToggle();
        $component->mount();

        // 設定暗黑主題
        $component->setTheme('dark');
        $this->assertEquals('dark', $component->currentTheme);

        // 設定淺色主題
        $component->setTheme('light');
        $this->assertEquals('light', $component->currentTheme);

        // 測試無效主題（應該被忽略）
        $component->setTheme('invalid');
        $this->assertEquals('light', $component->currentTheme);
    }

    /**
     * 測試 isDarkTheme 方法
     */
    public function test_is_dark_theme_method()
    {
        $user = new User([
            'username' => 'testuser',
            'name' => '測試使用者',
            'email' => 'test@example.com',
            'theme_preference' => 'light'
        ]);

        $this->actingAs($user);

        $component = new ThemeToggle();
        $component->mount();

        // 測試淺色主題
        $this->assertFalse($component->isDarkTheme());

        // 切換到暗黑主題
        $component->setTheme('dark');
        $this->assertTrue($component->isDarkTheme());
    }

    /**
     * 測試未登入使用者的主題處理
     */
    public function test_guest_user_theme_handling()
    {
        // 不設定認證使用者
        $component = new ThemeToggle();
        $component->mount();

        // 預設應該是淺色主題
        $this->assertEquals('light', $component->currentTheme);

        // 測試主題切換
        $component->toggleTheme();
        $this->assertEquals('dark', $component->currentTheme);
    }

    /**
     * 測試主題偏好設定載入
     */
    public function test_theme_preference_loading()
    {
        // 測試暗黑主題使用者
        $darkUser = new User([
            'username' => 'darkuser',
            'name' => '暗黑主題使用者',
            'email' => 'dark@example.com',
            'theme_preference' => 'dark'
        ]);

        $this->actingAs($darkUser);

        $component = new ThemeToggle();
        $component->mount();

        $this->assertEquals('dark', $component->currentTheme);
        $this->assertTrue($component->isDarkTheme());

        // 測試沒有偏好設定的使用者
        $defaultUser = new User([
            'username' => 'defaultuser',
            'name' => '預設使用者',
            'email' => 'default@example.com',
            'theme_preference' => null
        ]);

        $this->actingAs($defaultUser);

        $component2 = new ThemeToggle();
        $component2->mount();

        $this->assertEquals('light', $component2->currentTheme);
        $this->assertFalse($component2->isDarkTheme());
    }
}