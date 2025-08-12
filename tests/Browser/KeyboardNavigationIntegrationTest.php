<?php

namespace Tests\Browser;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * 鍵盤導航完整整合測試
 * 
 * 測試所有鍵盤快捷鍵和導航功能的完整整合
 */
class KeyboardNavigationIntegrationTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $admin;
    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminRole = Role::factory()->create(['name' => 'admin']);
        $this->admin = User::factory()->create([
            'username' => 'admin',
            'password' => bcrypt('password123'),
            'is_active' => true
        ]);
        $this->admin->roles()->attach($this->adminRole);

        // 建立測試資料
        User::factory()->count(10)->create();
        Role::factory()->count(3)->create();
    }

    /**
     * 登入管理員
     */
    protected function loginAdmin(Browser $browser): void
    {
        $browser->visit('/admin/login')
                ->type('username', 'admin')
                ->type('password', 'password123')
                ->press('登入')
                ->waitForLocation('/admin/dashboard');
    }

    /**
     * 測試基本鍵盤導航流程
     */
    public function test_basic_keyboard_navigation_flow()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 1. 測試 Tab 鍵導航
            $browser->keys('body', '{tab}')
                    ->assertFocused('.skip-link, a, button, input, select, textarea');

            // 2. 測試跳轉連結
            $browser->keys('body', '{tab}')
                    ->keys('.skip-link:focus', '{enter}')
                    ->waitFor('#main-content:focus, .main-content:focus');

            // 3. 測試側邊欄導航快捷鍵 (Alt+M)
            $browser->keys('body', ['{alt}', 'm'])
                    ->waitFor('.sidebar a:focus, .sidebar button:focus');

            // 4. 測試選單項目導航
            $browser->keys('.sidebar', '{down}')
                    ->keys('.sidebar', '{down}')
                    ->keys('.sidebar', '{enter}')
                    ->waitForLocation('/admin/users');

            // 5. 測試麵包屑導航
            $browser->assertSeeIn('.breadcrumb', '使用者管理')
                    ->keys('.breadcrumb a:first-child', '{enter}')
                    ->waitForLocation('/admin/dashboard');
        });
    }

    /**
     * 測試全域搜尋鍵盤快捷鍵
     */
    public function test_global_search_keyboard_shortcuts()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 1. 使用 Ctrl+K 開啟搜尋
            $browser->keys('body', ['{ctrl}', 'k'])
                    ->waitFor('.global-search-modal, .search-modal')
                    ->assertVisible('.global-search-modal, .search-modal')
                    ->assertFocused('.search-input, input[type="search"]');

            // 2. 輸入搜尋關鍵字
            $browser->type('.search-input, input[type="search"]', 'admin')
                    ->waitFor('.search-results');

            // 3. 使用方向鍵導航搜尋結果
            $browser->keys('.search-input, input[type="search"]', '{down}')
                    ->assertFocused('.search-result:first-child, .search-item:first-child');

            $browser->keys('.search-results', '{down}')
                    ->assertFocused('.search-result:nth-child(2), .search-item:nth-child(2)');

            // 4. 使用 Enter 選擇結果
            $browser->keys('.search-results .search-result:focus, .search-results .search-item:focus', '{enter}');

            // 5. 使用 Escape 關閉搜尋
            $browser->keys('body', ['{ctrl}', 'k'])
                    ->waitFor('.global-search-modal, .search-modal')
                    ->keys('body', '{escape}')
                    ->waitUntilMissing('.global-search-modal, .search-modal');
        });
    }

    /**
     * 測試主題切換快捷鍵
     */
    public function test_theme_toggle_keyboard_shortcut()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 1. 驗證預設主題
            $browser->assertMissing('html.dark');

            // 2. 使用 Ctrl+Shift+T 切換主題
            $browser->keys('body', ['{ctrl}', '{shift}', 't'])
                    ->waitFor('html.dark', 3)
                    ->assertPresent('html.dark');

            // 3. 再次使用快捷鍵切換回來
            $browser->keys('body', ['{ctrl}', '{shift}', 't'])
                    ->waitUntilMissing('html.dark', 3)
                    ->assertMissing('html.dark');

            // 4. 測試快捷鍵在不同頁面的一致性
            $browser->visit('/admin/users')
                    ->keys('body', ['{ctrl}', '{shift}', 't'])
                    ->waitFor('html.dark', 3)
                    ->assertPresent('html.dark');
        });
    }

    /**
     * 測試表單鍵盤導航
     */
    public function test_form_keyboard_navigation()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            $browser->visit('/admin/users/create')
                    ->waitForLocation('/admin/users/create');

            // 1. 第一個表單欄位應該自動獲得焦點
            $browser->assertFocused('input[name="username"]');

            // 2. 使用 Tab 鍵在表單欄位間導航
            $browser->keys('input[name="username"]', '{tab}')
                    ->assertFocused('input[name="name"]');

            $browser->keys('input[name="name"]', '{tab}')
                    ->assertFocused('input[name="email"]');

            $browser->keys('input[name="email"]', '{tab}')
                    ->assertFocused('input[name="password"]');

            // 3. 使用 Shift+Tab 反向導航
            $browser->keys('input[name="password"]', ['{shift}', '{tab}'])
                    ->assertFocused('input[name="email"]');

            // 4. 填寫表單並使用 Enter 提交
            $browser->type('input[name="username"]', 'testuser')
                    ->type('input[name="name"]', 'Test User')
                    ->type('input[name="email"]', 'test@example.com')
                    ->type('input[name="password"]', 'password123')
                    ->keys('button[type="submit"]', '{enter}')
                    ->waitFor('.notification, .alert');
        });
    }

    /**
     * 測試選單鍵盤導航
     */
    public function test_menu_keyboard_navigation()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 1. 使用 Alt+M 跳到選單
            $browser->keys('body', ['{alt}', 'm'])
                    ->waitFor('.sidebar a:focus, .sidebar button:focus');

            // 2. 使用方向鍵導航選單項目
            $browser->keys('.sidebar', '{down}')
                    ->keys('.sidebar', '{down}');

            // 3. 測試子選單展開 (如果有)
            if ($browser->element('.menu-item.has-children')) {
                $browser->keys('.menu-item.has-children > a:focus', '{right}')
                        ->waitFor('.submenu')
                        ->assertVisible('.submenu');

                // 4. 在子選單中導航
                $browser->keys('.submenu', '{down}')
                        ->keys('.submenu', '{down}');

                // 5. 收合子選單
                $browser->keys('.submenu', '{left}')
                        ->waitUntilMissing('.submenu');
            }

            // 6. 使用 Enter 選擇選單項目
            $browser->keys('.sidebar a:focus', '{enter}');
        });
    }

    /**
     * 測試通知中心鍵盤操作
     */
    public function test_notification_center_keyboard_navigation()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 1. 使用 Tab 導航到通知中心
            $this->navigateToElement($browser, '.notification-center, .notification-toggle');

            // 2. 使用 Enter 或 Space 開啟通知面板
            $browser->keys('.notification-center:focus, .notification-toggle:focus', '{enter}')
                    ->waitFor('.notification-dropdown, .notification-panel')
                    ->assertVisible('.notification-dropdown, .notification-panel');

            // 3. 在通知列表中導航
            if ($browser->element('.notification-item')) {
                $browser->keys('.notification-dropdown', '{down}')
                        ->assertFocused('.notification-item:first-child');

                $browser->keys('.notification-item:focus', '{down}')
                        ->assertFocused('.notification-item:nth-child(2)');

                // 4. 使用 Enter 標記為已讀
                $browser->keys('.notification-item:focus', '{enter}');
            }

            // 5. 使用 Escape 關閉通知面板
            $browser->keys('body', '{escape}')
                    ->waitUntilMissing('.notification-dropdown, .notification-panel');
        });
    }

    /**
     * 測試使用者選單鍵盤操作
     */
    public function test_user_menu_keyboard_navigation()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 1. 導航到使用者選單
            $this->navigateToElement($browser, '.user-menu, .user-dropdown-toggle');

            // 2. 開啟使用者選單
            $browser->keys('.user-menu:focus, .user-dropdown-toggle:focus', '{enter}')
                    ->waitFor('.user-dropdown, .user-menu-dropdown')
                    ->assertVisible('.user-dropdown, .user-menu-dropdown');

            // 3. 在選單項目間導航
            $browser->keys('.user-dropdown', '{down}')
                    ->assertFocused('.user-dropdown a:first-child, .user-dropdown button:first-child');

            $browser->keys('.user-dropdown', '{down}')
                    ->assertFocused('.user-dropdown a:nth-child(2), .user-dropdown button:nth-child(2)');

            // 4. 使用 Escape 關閉選單
            $browser->keys('body', '{escape}')
                    ->waitUntilMissing('.user-dropdown, .user-menu-dropdown');
        });
    }

    /**
     * 測試表格鍵盤導航
     */
    public function test_table_keyboard_navigation()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            $browser->visit('/admin/users')
                    ->waitForLocation('/admin/users');

            // 1. 導航到表格
            $this->navigateToElement($browser, 'table, .data-table');

            // 2. 在表格行間導航
            if ($browser->element('tbody tr')) {
                $browser->keys('table', '{down}')
                        ->assertFocused('tbody tr:first-child');

                $browser->keys('tbody tr:focus', '{down}')
                        ->assertFocused('tbody tr:nth-child(2)');

                // 3. 在表格列間導航
                $browser->keys('tbody tr:focus', '{right}')
                        ->keys('tbody tr:focus', '{left}');

                // 4. 使用 Enter 選擇行或執行操作
                if ($browser->element('tbody tr:focus .action-button')) {
                    $browser->keys('tbody tr:focus .action-button', '{enter}');
                }
            }
        });
    }

    /**
     * 測試模態對話框鍵盤操作
     */
    public function test_modal_keyboard_navigation()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            $browser->visit('/admin/users')
                    ->waitForLocation('/admin/users');

            // 1. 觸發模態對話框
            if ($browser->element('.delete-button, .modal-trigger')) {
                $browser->click('.delete-button:first, .modal-trigger:first')
                        ->waitFor('.modal, .confirmation-modal')
                        ->assertVisible('.modal, .confirmation-modal');

                // 2. 焦點應該在模態內
                $browser->assertFocused('.modal button, .modal input, .confirmation-modal button');

                // 3. Tab 鍵應該在模態內循環
                $browser->keys('.modal', '{tab}')
                        ->keys('.modal', '{tab}');

                // 4. 使用 Escape 關閉模態
                $browser->keys('body', '{escape}')
                        ->waitUntilMissing('.modal, .confirmation-modal');
            }
        });
    }

    /**
     * 測試快捷鍵說明對話框
     */
    public function test_keyboard_shortcuts_help()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 1. 使用 ? 或 F1 開啟快捷鍵說明
            $browser->keys('body', '?')
                    ->waitFor('.shortcuts-modal, .help-modal')
                    ->assertVisible('.shortcuts-modal, .help-modal')
                    ->assertSee('鍵盤快捷鍵')
                    ->assertSee('Ctrl+K')
                    ->assertSee('Alt+M');

            // 2. 在說明對話框中導航
            $browser->keys('.shortcuts-modal', '{tab}')
                    ->keys('.shortcuts-modal', '{tab}');

            // 3. 關閉說明對話框
            $browser->keys('body', '{escape}')
                    ->waitUntilMissing('.shortcuts-modal, .help-modal');
        });
    }

    /**
     * 測試自訂快捷鍵
     */
    public function test_custom_keyboard_shortcuts()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 如果有快捷鍵設定頁面
            if ($browser->element('a[href="/admin/settings/shortcuts"]')) {
                $browser->visit('/admin/settings/shortcuts')
                        ->waitForLocation('/admin/settings/shortcuts');

                // 測試新增自訂快捷鍵
                $browser->type('input[name="shortcut_key"]', 'ctrl+shift+u')
                        ->select('select[name="action"]', 'navigate')
                        ->type('input[name="target"]', '/admin/users')
                        ->press('新增快捷鍵')
                        ->waitFor('.notification')
                        ->assertSee('快捷鍵已新增');

                // 測試自訂快捷鍵是否生效
                $browser->visit('/admin/dashboard')
                        ->keys('body', ['{ctrl}', '{shift}', 'u'])
                        ->waitForLocation('/admin/users');
            }
        });
    }

    /**
     * 測試鍵盤導航在響應式設計中的表現
     */
    public function test_keyboard_navigation_responsive()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 桌面版測試
            $browser->resize(1200, 800);
            $this->testBasicKeyboardNavigation($browser);

            // 平板版測試
            $browser->resize(768, 1024);
            $this->testBasicKeyboardNavigation($browser);

            // 手機版測試
            $browser->resize(375, 667);
            $this->testMobileKeyboardNavigation($browser);
        });
    }

    /**
     * 測試基本鍵盤導航功能
     */
    protected function testBasicKeyboardNavigation(Browser $browser): void
    {
        // Tab 鍵導航
        $browser->keys('body', '{tab}')
                ->assertFocused('a, button, input, select, textarea');

        // 全域搜尋快捷鍵
        $browser->keys('body', ['{ctrl}', 'k'])
                ->waitFor('.global-search-modal, .search-modal')
                ->keys('body', '{escape}')
                ->waitUntilMissing('.global-search-modal, .search-modal');
    }

    /**
     * 測試手機版鍵盤導航
     */
    protected function testMobileKeyboardNavigation(Browser $browser): void
    {
        // 手機版選單切換
        if ($browser->element('.sidebar-toggle')) {
            $browser->keys('.sidebar-toggle', '{enter}')
                    ->waitFor('.sidebar-overlay')
                    ->keys('body', '{escape}')
                    ->waitUntilMissing('.sidebar-overlay');
        }

        // 其他基本導航功能應該仍然可用
        $this->testBasicKeyboardNavigation($browser);
    }

    /**
     * 導航到特定元素
     */
    protected function navigateToElement(Browser $browser, string $selector): void
    {
        // 使用 Tab 鍵導航到目標元素
        $maxTabs = 20; // 防止無限循環
        $tabCount = 0;

        while ($tabCount < $maxTabs) {
            $browser->keys('body', '{tab}');
            
            if ($browser->element($selector . ':focus')) {
                break;
            }
            
            $tabCount++;
        }
    }

    /**
     * 測試鍵盤導航的效能
     */
    public function test_keyboard_navigation_performance()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            $startTime = microtime(true);

            // 執行一系列鍵盤操作
            $browser->keys('body', ['{ctrl}', 'k'])
                    ->waitFor('.global-search-modal, .search-modal')
                    ->keys('body', '{escape}')
                    ->waitUntilMissing('.global-search-modal, .search-modal')
                    ->keys('body', ['{alt}', 'm'])
                    ->waitFor('.sidebar a:focus')
                    ->keys('.sidebar', '{down}')
                    ->keys('.sidebar', '{enter}');

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            // 鍵盤操作應該在合理時間內完成
            $this->assertLessThan(3.0, $executionTime, '鍵盤導航操作應該在 3 秒內完成');
        });
    }

    /**
     * 測試鍵盤導航的無障礙性
     */
    public function test_keyboard_navigation_accessibility()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 檢查焦點指示器
            $browser->keys('body', '{tab}')
                    ->assertPresent(':focus');

            // 檢查焦點可見性
            $focusedElement = $browser->element(':focus');
            if ($focusedElement) {
                $outline = $browser->script('
                    return window.getComputedStyle(arguments[0]).outline
                ', $focusedElement)[0];
                
                $this->assertNotEquals('none', $outline, '焦點元素應該有可見的焦點指示器');
            }

            // 檢查跳轉連結
            $browser->keys('body', '{tab}')
                    ->assertFocused('.skip-link');

            // 檢查 ARIA 標籤
            $browser->assertPresent('[aria-label], [aria-labelledby], [aria-describedby]');
        });
    }
}