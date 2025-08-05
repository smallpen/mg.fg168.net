<?php

namespace Tests\Browser;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * 管理後台儀表板瀏覽器測試
 * 
 * 測試儀表板的完整使用者工作流程和響應式設計
 */
class AdminDashboardTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立基本角色
        $this->adminRole = Role::factory()->create(['name' => 'admin']);
        
        // 建立管理員使用者
        $this->admin = User::factory()->create([
            'username' => 'admin',
            'password' => bcrypt('password123'),
            'is_active' => true
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
     * 測試儀表板基本顯示
     */
    public function test_dashboard_basic_display()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            $browser->assertSee('儀表板')
                    ->assertSee('系統統計')
                    ->assertSee('快速操作')
                    ->assertSee('最近活動');
        });
    }

    /**
     * 測試統計卡片顯示
     */
    public function test_statistics_cards_display()
    {
        // 建立一些測試資料
        User::factory()->count(10)->create();
        Role::factory()->count(5)->create();

        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            $browser->assertSee('使用者總數')
                    ->assertSee('角色總數')
                    ->assertSee('今日新增')
                    ->assertSee('本週新增');

            // 檢查統計數字是否顯示
            $browser->assertPresent('.stats-card')
                    ->assertPresent('.stats-number');
        });
    }

    /**
     * 測試快速操作連結
     */
    public function test_quick_actions_links()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            $browser->assertSee('新增使用者')
                    ->assertSee('新增角色')
                    ->click('a[href="/admin/users/create"]')
                    ->waitForLocation('/admin/users/create')
                    ->assertPathIs('/admin/users/create');
        });
    }

    /**
     * 測試側邊欄導航
     */
    public function test_sidebar_navigation()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 測試導航連結
            $browser->assertSee('儀表板')
                    ->assertSee('使用者管理')
                    ->assertSee('角色權限')
                    ->click('a[href="/admin/users"]')
                    ->waitForLocation('/admin/users')
                    ->assertPathIs('/admin/users');
        });
    }

    /**
     * 測試頂部導航列
     */
    public function test_top_navigation_bar()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            $browser->assertSee($this->admin->name)
                    ->assertPresent('.theme-toggle')
                    ->assertPresent('.language-selector')
                    ->assertPresent('@logout-button');
        });
    }

    /**
     * 測試響應式側邊欄
     */
    public function test_responsive_sidebar()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 桌面版 - 側邊欄應該可見
            $browser->resize(1200, 800)
                    ->assertVisible('.sidebar');

            // 平板版 - 側邊欄可能隱藏
            $browser->resize(768, 1024)
                    ->refresh()
                    ->waitForLocation('/admin/dashboard');

            // 手機版 - 側邊欄應該隱藏，有切換按鈕
            $browser->resize(375, 667)
                    ->refresh()
                    ->waitForLocation('/admin/dashboard')
                    ->assertPresent('.sidebar-toggle');
        });
    }

    /**
     * 測試主題切換功能
     */
    public function test_theme_toggle_functionality()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 檢查預設主題
            $browser->assertPresent('html:not(.dark)')
                    ->click('.theme-toggle')
                    ->waitFor('html.dark')
                    ->assertPresent('html.dark');

            // 切換回淺色主題
            $browser->click('.theme-toggle')
                    ->waitUntilMissing('html.dark')
                    ->assertPresent('html:not(.dark)');
        });
    }

    /**
     * 測試語言切換功能
     */
    public function test_language_selector_functionality()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 點擊語言選擇器
            $browser->click('.language-selector')
                    ->waitFor('.language-dropdown')
                    ->assertSee('正體中文')
                    ->assertSee('English')
                    ->click('a[data-locale="en"]')
                    ->waitForReload()
                    ->assertSee('Dashboard'); // 英文版的儀表板
        });
    }

    /**
     * 測試搜尋功能
     */
    public function test_global_search_functionality()
    {
        // 建立一些測試使用者
        User::factory()->create(['name' => 'John Doe']);

        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            $browser->type('.global-search input', 'John')
                    ->keys('.global-search input', '{enter}')
                    ->waitFor('.search-results')
                    ->assertSee('John Doe');
        });
    }

    /**
     * 測試通知系統
     */
    public function test_notification_system()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 觸發一個會產生通知的操作
            $browser->click('a[href="/admin/users/create"]')
                    ->waitForLocation('/admin/users/create')
                    ->type('username', 'testuser')
                    ->type('name', 'Test User')
                    ->type('password', 'password123')
                    ->press('儲存')
                    ->waitFor('.notification')
                    ->assertSee('使用者建立成功');
        });
    }

    /**
     * 測試最近活動顯示
     */
    public function test_recent_activity_display()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            $browser->assertPresent('.recent-activity')
                    ->assertSee('最近活動');

            // 如果有活動記錄，檢查是否顯示
            if ($browser->element('.activity-item')) {
                $browser->assertPresent('.activity-item')
                        ->assertPresent('.activity-time')
                        ->assertPresent('.activity-description');
            }
        });
    }

    /**
     * 測試統計圖表
     */
    public function test_statistics_charts()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 檢查圖表容器是否存在
            $browser->assertPresent('.chart-container')
                    ->waitFor('.chart-canvas', 10);

            // 檢查圖表是否載入
            $browser->script('return document.querySelector(".chart-canvas") !== null');
        });
    }

    /**
     * 測試鍵盤快捷鍵
     */
    public function test_keyboard_shortcuts()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 測試搜尋快捷鍵 (Ctrl+K)
            $browser->keys('body', ['{control}', 'k'])
                    ->waitFor('.search-modal')
                    ->assertVisible('.search-modal');

            // 測試關閉快捷鍵 (Escape)
            $browser->keys('body', '{escape}')
                    ->waitUntilMissing('.search-modal')
                    ->assertMissing('.search-modal');
        });
    }

    /**
     * 測試載入效能
     */
    public function test_page_loading_performance()
    {
        $this->browse(function (Browser $browser) {
            $startTime = microtime(true);
            
            $this->loginAdmin($browser);
            
            $loadTime = microtime(true) - $startTime;
            
            // 確保頁面在合理時間內載入（5秒內）
            $this->assertLessThan(5.0, $loadTime);
            
            // 檢查關鍵元素是否載入
            $browser->assertPresent('.dashboard-content')
                    ->assertPresent('.stats-cards')
                    ->assertPresent('.sidebar');
        });
    }

    /**
     * 測試錯誤處理
     */
    public function test_error_handling()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 訪問不存在的頁面
            $browser->visit('/admin/nonexistent')
                    ->assertSee('404')
                    ->assertSee('頁面不存在');
        });
    }

    /**
     * 測試無障礙功能
     */
    public function test_accessibility_features()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 檢查 ARIA 標籤
            $browser->assertAttribute('.sidebar', 'role', 'navigation')
                    ->assertAttribute('.main-content', 'role', 'main')
                    ->assertPresent('[aria-label]');

            // 檢查鍵盤導航
            $browser->keys('body', '{tab}')
                    ->assertFocused('a, button, input, select, textarea');
        });
    }

    /**
     * 測試資料重新整理
     */
    public function test_data_refresh()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 點擊重新整理按鈕
            $browser->click('.refresh-button')
                    ->waitFor('.loading-spinner')
                    ->waitUntilMissing('.loading-spinner')
                    ->assertPresent('.stats-cards');
        });
    }

    /**
     * 測試多標籤頁支援
     */
    public function test_multi_tab_support()
    {
        $this->browse(function (Browser $browser1, Browser $browser2) {
            // 在兩個標籤頁中登入
            $this->loginAdmin($browser1);
            $this->loginAdmin($browser2);

            // 在第一個標籤頁進行操作
            $browser1->click('a[href="/admin/users"]')
                     ->waitForLocation('/admin/users');

            // 檢查第二個標籤頁是否仍然正常
            $browser2->assertPathIs('/admin/dashboard')
                     ->assertSee('儀表板');
        });
    }

    /**
     * 測試 Session 持續性
     */
    public function test_session_persistence()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 重新整理頁面
            $browser->refresh()
                    ->waitForLocation('/admin/dashboard')
                    ->assertSee('儀表板')
                    ->assertSee($this->admin->name);
        });
    }
}