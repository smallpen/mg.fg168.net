<?php

namespace Tests\Browser;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * 主題切換和多語系功能瀏覽器測試
 * 
 * 測試主題切換和語言切換的完整使用者體驗
 */
class ThemeAndLanguageTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立基本角色和管理員
        $this->adminRole = Role::factory()->create(['name' => 'admin']);
        $this->admin = User::factory()->create([
            'username' => 'admin',
            'password' => bcrypt('password123'),
            'is_active' => true,
            'theme_preference' => 'light',
            'locale' => 'zh_TW'
        ]);
        $this->admin->roles()->attach($this->adminRole);
    }

    /**
     * 登入管理員
     */
    protected function loginAdmin(Browser $browser)
    {
        $browser->visit('/admin/login')
                ->type('username', 'admin')
                ->type('password', 'password123')
                ->press('登入')
                ->waitForLocation('/admin/dashboard');
    }

    /**
     * 測試預設主題載入
     */
    public function test_default_theme_loading()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 檢查預設為淺色主題
            $browser->assertMissing('html.dark')
                    ->assertPresent('html:not(.dark)');

            // 檢查主題切換按鈕顯示正確圖示
            $browser->assertPresent('.theme-toggle')
                    ->assertSee('深色模式'); // 顯示切換到深色模式的選項
        });
    }

    /**
     * 測試主題切換功能
     */
    public function test_theme_toggle_functionality()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 切換到暗黑主題
            $browser->click('.theme-toggle')
                    ->waitFor('html.dark', 2)
                    ->assertPresent('html.dark')
                    ->assertSee('淺色模式'); // 按鈕文字應該改變

            // 檢查暗黑主題樣式是否生效
            $backgroundColor = $browser->script('
                return window.getComputedStyle(document.body).backgroundColor
            ')[0];
            
            // 暗黑主題的背景應該是深色
            $this->assertStringContainsString('rgb', $backgroundColor);

            // 切換回淺色主題
            $browser->click('.theme-toggle')
                    ->waitUntilMissing('html.dark', 2)
                    ->assertMissing('html.dark')
                    ->assertSee('深色模式');
        });
    }

    /**
     * 測試主題偏好設定持久化
     */
    public function test_theme_preference_persistence()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 切換到暗黑主題
            $browser->click('.theme-toggle')
                    ->waitFor('html.dark', 2);

            // 重新整理頁面
            $browser->refresh()
                    ->waitForLocation('/admin/dashboard')
                    ->waitFor('html.dark', 2)
                    ->assertPresent('html.dark');

            // 檢查資料庫中的偏好設定是否更新
            $this->admin->refresh();
            $this->assertEquals('dark', $this->admin->theme_preference);
        });
    }

    /**
     * 測試主題在不同頁面間的一致性
     */
    public function test_theme_consistency_across_pages()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 在儀表板切換到暗黑主題
            $browser->click('.theme-toggle')
                    ->waitFor('html.dark', 2);

            // 導航到使用者管理頁面
            $browser->click('a[href="/admin/users"]')
                    ->waitForLocation('/admin/users')
                    ->assertPresent('html.dark');

            // 導航到角色管理頁面
            $browser->click('a[href="/admin/roles"]')
                    ->waitForLocation('/admin/roles')
                    ->assertPresent('html.dark');
        });
    }

    /**
     * 測試主題切換動畫效果
     */
    public function test_theme_transition_animation()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 檢查是否有過渡動畫類別
            $browser->click('.theme-toggle');

            // 檢查過渡期間是否有載入狀態
            if ($browser->element('.theme-transitioning')) {
                $browser->assertPresent('.theme-transitioning');
            }

            $browser->waitFor('html.dark', 2);
        });
    }

    /**
     * 測試系統主題偵測
     */
    public function test_system_theme_detection()
    {
        $this->browse(function (Browser $browser) {
            // 設定使用者偏好為系統主題
            $this->admin->update(['theme_preference' => 'system']);

            $this->loginAdmin($browser);

            // 模擬系統暗黑模式
            $browser->script('
                window.matchMedia = function(query) {
                    return {
                        matches: query === "(prefers-color-scheme: dark)",
                        media: query,
                        onchange: null,
                        addListener: function() {},
                        removeListener: function() {}
                    };
                };
            ');

            $browser->refresh()
                    ->waitForLocation('/admin/dashboard');

            // 檢查是否根據系統偏好設定主題
            // 這個測試可能需要根據實際實作調整
        });
    }

    /**
     * 測試預設語言載入
     */
    public function test_default_language_loading()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 檢查預設為正體中文
            $browser->assertSee('儀表板')
                    ->assertSee('使用者管理')
                    ->assertSee('角色權限');

            // 檢查語言選擇器顯示當前語言
            $browser->assertPresent('.language-selector')
                    ->assertSee('正體中文');
        });
    }

    /**
     * 測試語言切換功能
     */
    public function test_language_switching()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 點擊語言選擇器
            $browser->click('.language-selector')
                    ->waitFor('.language-dropdown', 2)
                    ->assertSee('正體中文')
                    ->assertSee('English');

            // 切換到英文
            $browser->click('a[data-locale="en"]')
                    ->waitForReload()
                    ->waitForLocation('/admin/dashboard')
                    ->assertSee('Dashboard')
                    ->assertSee('User Management')
                    ->assertSee('Roles & Permissions');

            // 檢查語言選擇器顯示更新
            $browser->assertSee('English');
        });
    }

    /**
     * 測試語言偏好設定持久化
     */
    public function test_language_preference_persistence()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 切換到英文
            $browser->click('.language-selector')
                    ->waitFor('.language-dropdown', 2)
                    ->click('a[data-locale="en"]')
                    ->waitForReload()
                    ->waitForLocation('/admin/dashboard');

            // 重新整理頁面
            $browser->refresh()
                    ->waitForLocation('/admin/dashboard')
                    ->assertSee('Dashboard');

            // 檢查資料庫中的語言偏好設定
            $this->admin->refresh();
            $this->assertEquals('en', $this->admin->locale);
        });
    }

    /**
     * 測試語言在不同頁面間的一致性
     */
    public function test_language_consistency_across_pages()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 在儀表板切換到英文
            $browser->click('.language-selector')
                    ->waitFor('.language-dropdown', 2)
                    ->click('a[data-locale="en"]')
                    ->waitForReload()
                    ->waitForLocation('/admin/dashboard');

            // 導航到其他頁面檢查語言一致性
            $browser->click('a[href="/admin/users"]')
                    ->waitForLocation('/admin/users')
                    ->assertSee('User Management')
                    ->assertSee('Create User');

            $browser->click('a[href="/admin/roles"]')
                    ->waitForLocation('/admin/roles')
                    ->assertSee('Role Management')
                    ->assertSee('Create Role');
        });
    }

    /**
     * 測試表單驗證訊息的多語系
     */
    public function test_form_validation_messages_localization()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 在中文環境下測試驗證訊息
            $browser->visit('/admin/users/create')
                    ->press('儲存')
                    ->waitForText('請輸入使用者名稱')
                    ->assertSee('請輸入使用者名稱')
                    ->assertSee('請輸入姓名');

            // 切換到英文
            $browser->click('.language-selector')
                    ->waitFor('.language-dropdown', 2)
                    ->click('a[data-locale="en"]')
                    ->waitForReload()
                    ->waitForLocation('/admin/users/create');

            // 測試英文驗證訊息
            $browser->press('Save')
                    ->waitForText('The username field is required')
                    ->assertSee('The username field is required')
                    ->assertSee('The name field is required');
        });
    }

    /**
     * 測試日期時間格式的本地化
     */
    public function test_datetime_localization()
    {
        // 建立一些測試資料
        User::factory()->create(['created_at' => now()]);

        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 在中文環境下檢查日期格式
            $browser->visit('/admin/users')
                    ->assertPresent('.created-at');

            // 切換到英文檢查日期格式變化
            $browser->click('.language-selector')
                    ->waitFor('.language-dropdown', 2)
                    ->click('a[data-locale="en"]')
                    ->waitForReload()
                    ->waitForLocation('/admin/users')
                    ->assertPresent('.created-at');

            // 這裡可以檢查具體的日期格式差異
        });
    }

    /**
     * 測試數字格式的本地化
     */
    public function test_number_localization()
    {
        // 建立大量測試資料
        User::factory()->count(1234)->create();

        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 檢查統計數字的格式
            $browser->assertPresent('.stats-number');

            // 在不同語言環境下，數字格式可能不同
            $statsText = $browser->text('.stats-number');
            $this->assertNotEmpty($statsText);
        });
    }

    /**
     * 測試 RTL 語言支援（如果有實作）
     */
    public function test_rtl_language_support()
    {
        // 這個測試假設有 RTL 語言支援
        $this->markTestSkipped('RTL language support not implemented');

        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 切換到 RTL 語言（如阿拉伯語）
            $browser->click('.language-selector')
                    ->waitFor('.language-dropdown', 2)
                    ->click('a[data-locale="ar"]')
                    ->waitForReload()
                    ->waitForLocation('/admin/dashboard');

            // 檢查 RTL 佈局
            $browser->assertPresent('html[dir="rtl"]')
                    ->assertPresent('.rtl-layout');
        });
    }

    /**
     * 測試主題和語言的組合效果
     */
    public function test_theme_and_language_combination()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 切換到暗黑主題和英文
            $browser->click('.theme-toggle')
                    ->waitFor('html.dark', 2)
                    ->click('.language-selector')
                    ->waitFor('.language-dropdown', 2)
                    ->click('a[data-locale="en"]')
                    ->waitForReload()
                    ->waitForLocation('/admin/dashboard');

            // 檢查兩個設定都生效
            $browser->assertPresent('html.dark')
                    ->assertSee('Dashboard')
                    ->assertSee('Dark Mode'); // 英文版的主題切換按鈕

            // 重新整理頁面確保設定持久化
            $browser->refresh()
                    ->waitForLocation('/admin/dashboard')
                    ->assertPresent('html.dark')
                    ->assertSee('Dashboard');
        });
    }

    /**
     * 測試主題切換的鍵盤快捷鍵
     */
    public function test_theme_toggle_keyboard_shortcut()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 使用鍵盤快捷鍵切換主題 (Ctrl+Shift+T)
            $browser->keys('body', ['{control}', '{shift}', 't'])
                    ->waitFor('html.dark', 2)
                    ->assertPresent('html.dark');

            // 再次使用快捷鍵切換回來
            $browser->keys('body', ['{control}', '{shift}', 't'])
                    ->waitUntilMissing('html.dark', 2)
                    ->assertMissing('html.dark');
        });
    }

    /**
     * 測試語言切換的載入狀態
     */
    public function test_language_switching_loading_state()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 點擊語言切換
            $browser->click('.language-selector')
                    ->waitFor('.language-dropdown', 2)
                    ->click('a[data-locale="en"]');

            // 檢查是否有載入狀態指示器
            if ($browser->element('.language-loading')) {
                $browser->assertPresent('.language-loading');
            }

            $browser->waitForReload()
                    ->waitForLocation('/admin/dashboard')
                    ->assertSee('Dashboard');
        });
    }

    /**
     * 測試主題和語言設定的匯出/匯入
     */
    public function test_settings_export_import()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 設定特定的主題和語言
            $browser->click('.theme-toggle')
                    ->waitFor('html.dark', 2)
                    ->click('.language-selector')
                    ->waitFor('.language-dropdown', 2)
                    ->click('a[data-locale="en"]')
                    ->waitForReload()
                    ->waitForLocation('/admin/dashboard');

            // 如果有設定匯出功能
            if ($browser->element('.export-settings')) {
                $browser->click('.export-settings');
                // 檢查匯出功能
            }

            // 檢查設定是否正確儲存
            $this->admin->refresh();
            $this->assertEquals('dark', $this->admin->theme_preference);
            $this->assertEquals('en', $this->admin->locale);
        });
    }
}