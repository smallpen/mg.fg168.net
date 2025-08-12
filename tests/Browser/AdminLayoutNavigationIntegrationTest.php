<?php

namespace Tests\Browser;

use App\Models\Role;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * 管理後台佈局和導航系統完整整合測試
 * 
 * 測試所有佈局和導航功能的完整工作流程，包含響應式設計、
 * 主題切換、多語言、鍵盤導航和無障礙功能的整合測試
 */
class AdminLayoutNavigationIntegrationTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $admin;
    protected Role $adminRole;

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

        // 建立測試資料
        User::factory()->count(15)->create();
        Role::factory()->count(5)->create();
        
        // 建立測試通知
        Notification::factory()->count(3)->create([
            'user_id' => $this->admin->id,
            'read_at' => null
        ]);
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
     * 測試完整的佈局導航流程
     */
    public function test_complete_layout_navigation_workflow()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 1. 驗證基本佈局結構
            $browser->assertVisible('.admin-layout')
                    ->assertVisible('.sidebar')
                    ->assertVisible('.top-nav-bar')
                    ->assertVisible('.main-content')
                    ->assertVisible('.breadcrumb');

            // 2. 測試側邊欄導航
            $browser->assertSee('儀表板')
                    ->assertSee('使用者管理')
                    ->assertSee('角色權限')
                    ->assertSee('系統設定');

            // 3. 導航到使用者管理
            $browser->click('a[href="/admin/users"]')
                    ->waitForLocation('/admin/users')
                    ->assertSee('使用者管理')
                    ->assertSee('使用者列表');

            // 4. 檢查麵包屑更新
            $browser->assertSeeIn('.breadcrumb', '首頁')
                    ->assertSeeIn('.breadcrumb', '使用者管理');

            // 5. 測試子選單展開
            if ($browser->element('.menu-item.has-children')) {
                $browser->click('.menu-item.has-children > a')
                        ->waitFor('.submenu')
                        ->assertVisible('.submenu');
            }

            // 6. 導航到角色管理
            $browser->click('a[href="/admin/roles"]')
                    ->waitForLocation('/admin/roles')
                    ->assertSee('角色管理');

            // 7. 返回儀表板
            $browser->click('a[href="/admin/dashboard"]')
                    ->waitForLocation('/admin/dashboard')
                    ->assertSee('儀表板');
        });
    }

    /**
     * 測試響應式設計在不同裝置的完整表現
     */
    public function test_responsive_design_complete_workflow()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 桌面版測試 (1920x1080)
            $browser->resize(1920, 1080)
                    ->refresh()
                    ->waitForLocation('/admin/dashboard');

            // 驗證桌面版佈局
            $browser->assertVisible('.sidebar')
                    ->assertVisible('.sidebar-expanded')
                    ->assertMissing('.sidebar-toggle')
                    ->assertVisible('.stats-cards');

            // 檢查統計卡片佈局 (應該是 4 列)
            $this->verifyStatsCardsLayout($browser, 4);

            // 平板版測試 (768x1024)
            $browser->resize(768, 1024)
                    ->refresh()
                    ->waitForLocation('/admin/dashboard');

            // 驗證平板版佈局
            $browser->assertVisible('.sidebar')
                    ->assertPresent('.sidebar-toggle');

            // 檢查統計卡片佈局 (應該是 2 列)
            $this->verifyStatsCardsLayout($browser, 2);

            // 手機版測試 (375x667)
            $browser->resize(375, 667)
                    ->refresh()
                    ->waitForLocation('/admin/dashboard');

            // 驗證手機版佈局
            $browser->assertNotVisible('.sidebar')
                    ->assertPresent('.sidebar-toggle');

            // 測試手機版選單切換
            $browser->click('.sidebar-toggle')
                    ->waitFor('.sidebar-overlay')
                    ->assertVisible('.sidebar')
                    ->assertVisible('.sidebar-overlay');

            // 點擊遮罩關閉選單
            $browser->click('.sidebar-overlay')
                    ->waitUntilMissing('.sidebar-overlay')
                    ->assertNotVisible('.sidebar');

            // 檢查統計卡片佈局 (應該是 1 列)
            $this->verifyStatsCardsLayout($browser, 1);

            // 測試表格響應式
            $browser->visit('/admin/users')
                    ->waitForLocation('/admin/users');

            if ($browser->element('.table-responsive')) {
                $browser->assertPresent('.table-responsive');
            }

            // 測試表單響應式
            $browser->visit('/admin/users/create')
                    ->waitForLocation('/admin/users/create')
                    ->assertVisible('form');

            // 檢查表單元素是否適當堆疊
            $this->verifyFormResponsiveness($browser);
        });
    }

    /**
     * 測試主題切換和多語言功能的完整整合
     */
    public function test_theme_and_language_complete_integration()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 1. 驗證預設狀態
            $browser->assertMissing('html.dark')
                    ->assertSee('儀表板')
                    ->assertSee('使用者管理');

            // 2. 切換到暗黑主題
            $browser->click('.theme-toggle')
                    ->waitFor('html.dark', 3)
                    ->assertPresent('html.dark');

            // 3. 驗證主題在不同頁面的一致性
            $browser->click('a[href="/admin/users"]')
                    ->waitForLocation('/admin/users')
                    ->assertPresent('html.dark');

            $browser->click('a[href="/admin/roles"]')
                    ->waitForLocation('/admin/roles')
                    ->assertPresent('html.dark');

            // 4. 切換到英文
            $browser->click('.language-selector')
                    ->waitFor('.language-dropdown', 2)
                    ->click('a[data-locale="en"]')
                    ->waitForReload()
                    ->waitForLocation('/admin/roles');

            // 5. 驗證語言切換成功且主題保持
            $browser->assertPresent('html.dark')
                    ->assertSee('Role Management')
                    ->assertSee('Create Role');

            // 6. 測試不同頁面的語言一致性
            $browser->click('a[href="/admin/users"]')
                    ->waitForLocation('/admin/users')
                    ->assertSee('User Management')
                    ->assertSee('Create User');

            $browser->click('a[href="/admin/dashboard"]')
                    ->waitForLocation('/admin/dashboard')
                    ->assertSee('Dashboard')
                    ->assertSee('Statistics');

            // 7. 測試表單驗證訊息的多語系
            $browser->visit('/admin/users/create')
                    ->press('Save')
                    ->waitForText('The username field is required')
                    ->assertSee('The username field is required');

            // 8. 切換回中文並驗證主題保持
            $browser->click('.language-selector')
                    ->waitFor('.language-dropdown', 2)
                    ->click('a[data-locale="zh_TW"]')
                    ->waitForReload()
                    ->waitForLocation('/admin/users/create');

            $browser->assertPresent('html.dark')
                    ->press('儲存')
                    ->waitForText('請輸入使用者名稱')
                    ->assertSee('請輸入使用者名稱');

            // 9. 切換回淺色主題
            $browser->click('.theme-toggle')
                    ->waitUntilMissing('html.dark', 3)
                    ->assertMissing('html.dark');

            // 10. 重新整理頁面驗證設定持久化
            $browser->refresh()
                    ->waitForLocation('/admin/users/create')
                    ->assertMissing('html.dark')
                    ->assertSee('建立使用者');
        });
    }

    /**
     * 測試鍵盤導航和無障礙功能的完整整合
     */
    public function test_keyboard_navigation_and_accessibility_integration()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 1. 測試跳轉連結
            $browser->keys('body', '{tab}')
                    ->assertFocused('.skip-link')
                    ->keys('.skip-link', '{enter}')
                    ->waitFor('#main-content:focus');

            // 2. 測試鍵盤導航到側邊欄
            $browser->keys('body', ['{alt}', 'm'])
                    ->waitFor('.sidebar a:focus');

            // 3. 測試選單鍵盤導航
            $browser->keys('.sidebar', '{down}')
                    ->keys('.sidebar', '{down}')
                    ->keys('.sidebar', '{enter}')
                    ->waitForLocation('/admin/users');

            // 4. 測試全域搜尋快捷鍵
            $browser->keys('body', ['{ctrl}', 'k'])
                    ->waitFor('.global-search-modal')
                    ->assertVisible('.global-search-modal')
                    ->assertFocused('.search-input');

            // 5. 測試搜尋功能
            $browser->type('.search-input', 'user')
                    ->waitFor('.search-results')
                    ->keys('.search-input', '{down}')
                    ->keys('.search-results', '{enter}');

            // 6. 測試 Escape 關閉搜尋
            $browser->keys('body', ['{ctrl}', 'k'])
                    ->waitFor('.global-search-modal')
                    ->keys('body', '{escape}')
                    ->waitUntilMissing('.global-search-modal');

            // 7. 測試主題切換快捷鍵
            $browser->keys('body', ['{ctrl}', '{shift}', 't'])
                    ->waitFor('html.dark', 3)
                    ->assertPresent('html.dark');

            // 8. 測試 ARIA 標籤和語義化標記
            $browser->assertAttribute('.sidebar', 'role', 'navigation')
                    ->assertAttribute('.main-content', 'role', 'main')
                    ->assertAttribute('.breadcrumb', 'role', 'navigation')
                    ->assertPresent('[aria-label]')
                    ->assertPresent('[aria-expanded]');

            // 9. 測試焦點管理
            $browser->visit('/admin/users/create')
                    ->waitForLocation('/admin/users/create')
                    ->assertFocused('input[name="username"]'); // 第一個表單欄位應該獲得焦點

            // 10. 測試表單鍵盤導航
            $browser->keys('input[name="username"]', '{tab}')
                    ->assertFocused('input[name="name"]')
                    ->keys('input[name="name"]', '{tab}')
                    ->assertFocused('input[name="email"]');

            // 11. 測試高對比模式（如果有實作）
            if ($browser->element('.accessibility-toggle')) {
                $browser->click('.accessibility-toggle')
                        ->waitFor('.high-contrast')
                        ->assertPresent('.high-contrast');
            }
        });
    }

    /**
     * 測試通知中心和全域搜尋的完整整合
     */
    public function test_notification_and_search_integration()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 1. 測試通知中心顯示
            $browser->assertPresent('.notification-center')
                    ->assertSee('3'); // 未讀通知數量

            // 2. 開啟通知面板
            $browser->click('.notification-center')
                    ->waitFor('.notification-dropdown')
                    ->assertVisible('.notification-dropdown')
                    ->assertSee('未讀通知')
                    ->assertSee('標記全部為已讀');

            // 3. 標記單一通知為已讀
            $browser->click('.notification-item:first-child .mark-read')
                    ->waitFor('.notification-item:first-child.read')
                    ->assertPresent('.notification-item:first-child.read');

            // 4. 標記全部為已讀
            $browser->click('.mark-all-read')
                    ->waitUntilMissing('.notification-badge')
                    ->assertMissing('.notification-badge');

            // 5. 關閉通知面板
            $browser->click('body')
                    ->waitUntilMissing('.notification-dropdown');

            // 6. 測試全域搜尋
            $browser->click('.global-search')
                    ->waitFor('.search-input')
                    ->type('.search-input', 'admin')
                    ->waitFor('.search-results');

            // 7. 驗證搜尋結果分類
            $browser->assertSee('使用者')
                    ->assertSee('頁面')
                    ->assertSee('功能');

            // 8. 點擊搜尋結果
            $browser->click('.search-result:first-child')
                    ->waitForLocation('/admin/users')
                    ->assertPathIs('/admin/users');

            // 9. 測試搜尋歷史
            $browser->click('.global-search')
                    ->waitFor('.search-input')
                    ->assertSee('最近搜尋')
                    ->assertSee('admin');

            // 10. 清除搜尋歷史
            if ($browser->element('.clear-search-history')) {
                $browser->click('.clear-search-history')
                        ->waitUntilMissing('.search-history');
            }
        });
    }

    /**
     * 測試載入狀態和效能優化
     */
    public function test_loading_states_and_performance()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 1. 測試頁面載入指示器
            $browser->click('a[href="/admin/users"]');
            
            // 檢查是否有載入指示器
            if ($browser->element('.loading-overlay')) {
                $browser->assertVisible('.loading-overlay');
            }
            
            $browser->waitForLocation('/admin/users')
                    ->assertMissing('.loading-overlay');

            // 2. 測試 AJAX 操作載入狀態
            if ($browser->element('.refresh-button')) {
                $browser->click('.refresh-button')
                        ->waitFor('.loading-spinner')
                        ->waitUntilMissing('.loading-spinner');
            }

            // 3. 測試懶載入元件
            $browser->visit('/admin/dashboard')
                    ->waitForLocation('/admin/dashboard');

            // 檢查圖表是否延遲載入
            if ($browser->element('.chart-container[data-lazy]')) {
                $browser->scrollIntoView('.chart-container')
                        ->waitFor('.chart-loaded');
            }

            // 4. 測試網路狀態檢測
            $browser->script('
                window.dispatchEvent(new Event("offline"));
            ');

            if ($browser->element('.offline-indicator')) {
                $browser->waitFor('.offline-indicator')
                        ->assertVisible('.offline-indicator');
            }

            $browser->script('
                window.dispatchEvent(new Event("online"));
            ');

            if ($browser->element('.offline-indicator')) {
                $browser->waitUntilMissing('.offline-indicator');
            }

            // 5. 測試頁面載入效能
            $startTime = microtime(true);
            $browser->visit('/admin/roles')
                    ->waitForLocation('/admin/roles');
            $loadTime = microtime(true) - $startTime;

            $this->assertLessThan(3.0, $loadTime, '頁面載入時間應該少於 3 秒');
        });
    }

    /**
     * 測試安全性控制和 Session 管理
     */
    public function test_security_and_session_management()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 1. 測試 Session 活動檢測
            $browser->assertPresent('.session-indicator');

            // 2. 模擬長時間閒置
            $browser->script('
                // 模擬 Session 即將過期
                window.dispatchEvent(new CustomEvent("session-warning", {
                    detail: { remainingTime: 300 }
                }));
            ');

            if ($browser->element('.session-warning-modal')) {
                $browser->waitFor('.session-warning-modal')
                        ->assertVisible('.session-warning-modal')
                        ->assertSee('Session 即將過期')
                        ->click('.extend-session')
                        ->waitUntilMissing('.session-warning-modal');
            }

            // 3. 測試自動登出
            $browser->script('
                // 模擬 Session 過期
                window.dispatchEvent(new CustomEvent("session-expired"));
            ');

            // 應該重導向到登入頁面
            $browser->waitForLocation('/admin/login')
                    ->assertPathIs('/admin/login')
                    ->assertSee('Session 已過期');

            // 4. 重新登入測試
            $this->loginAdmin($browser);

            // 5. 測試 CSRF 保護
            $browser->visit('/admin/users/create')
                    ->assertPresent('input[name="_token"]');

            // 6. 測試權限檢查
            $browser->visit('/admin/system-settings')
                    ->waitFor('body');

            // 根據使用者權限，可能看到內容或被重導向
            if ($browser->element('.access-denied')) {
                $browser->assertSee('權限不足');
            }
        });
    }

    /**
     * 測試多裝置和跨瀏覽器相容性
     */
    public function test_multi_device_compatibility()
    {
        $this->browse(function (Browser $browser) {
            $deviceSizes = [
                ['desktop', 1920, 1080],
                ['laptop', 1366, 768],
                ['tablet', 768, 1024],
                ['mobile', 375, 667],
                ['mobile-landscape', 667, 375]
            ];

            foreach ($deviceSizes as [$device, $width, $height]) {
                $browser->resize($width, $height);
                $this->loginAdmin($browser);

                // 驗證基本功能在所有裝置上都能正常運作
                $browser->assertVisible('.admin-layout')
                        ->assertVisible('.main-content');

                // 測試導航功能
                if ($device === 'mobile' || $device === 'mobile-landscape') {
                    // 手機版測試
                    $browser->assertPresent('.sidebar-toggle')
                            ->click('.sidebar-toggle')
                            ->waitFor('.sidebar-overlay')
                            ->click('.sidebar-overlay')
                            ->waitUntilMissing('.sidebar-overlay');
                } else {
                    // 桌面/平板版測試
                    $browser->assertVisible('.sidebar');
                }

                // 測試主題切換在所有裝置上的表現
                $browser->click('.theme-toggle')
                        ->waitFor('html.dark', 3)
                        ->click('.theme-toggle')
                        ->waitUntilMissing('html.dark', 3);

                // 測試表單在不同裝置上的可用性
                $browser->visit('/admin/users/create')
                        ->assertVisible('form')
                        ->assertVisible('input[name="username"]');
            }
        });
    }

    /**
     * 驗證統計卡片佈局
     */
    protected function verifyStatsCardsLayout(Browser $browser, int $expectedColumns): void
    {
        $cardsPerRow = $browser->script('
            const cards = document.querySelectorAll(".stats-card");
            if (cards.length === 0) return 0;
            
            const firstCard = cards[0];
            const firstCardTop = firstCard.offsetTop;
            let count = 0;
            
            cards.forEach(card => {
                if (Math.abs(card.offsetTop - firstCardTop) < 10) count++;
            });
            
            return count;
        ')[0];

        $this->assertGreaterThanOrEqual(
            min($expectedColumns, 4), // 不超過實際卡片數量
            $cardsPerRow,
            "統計卡片應該以 {$expectedColumns} 列佈局顯示"
        );
    }

    /**
     * 驗證表單響應式設計
     */
    protected function verifyFormResponsiveness(Browser $browser): void
    {
        $formElements = $browser->elements('input, select, textarea');
        
        foreach ($formElements as $element) {
            $width = $browser->script('return arguments[0].offsetWidth', $element)[0];
            $containerWidth = $browser->script('return arguments[0].parentElement.offsetWidth', $element)[0];
            
            // 表單元素應該適應容器寬度
            $this->assertLessThanOrEqual($containerWidth, $width);
        }
    }

    /**
     * 測試完整的使用者工作流程
     */
    public function test_complete_user_workflow()
    {
        $this->browse(function (Browser $browser) {
            // 1. 登入
            $this->loginAdmin($browser);

            // 2. 檢視儀表板統計
            $browser->assertSee('使用者總數')
                    ->assertSee('角色總數');

            // 3. 使用全域搜尋
            $browser->keys('body', ['{ctrl}', 'k'])
                    ->waitFor('.global-search-modal')
                    ->type('.search-input', 'user')
                    ->waitFor('.search-results')
                    ->keys('body', '{escape}')
                    ->waitUntilMissing('.global-search-modal');

            // 4. 導航到使用者管理
            $browser->click('a[href="/admin/users"]')
                    ->waitForLocation('/admin/users')
                    ->assertSee('使用者列表');

            // 5. 建立新使用者
            $browser->click('a[href="/admin/users/create"]')
                    ->waitForLocation('/admin/users/create')
                    ->type('username', 'testuser')
                    ->type('name', 'Test User')
                    ->type('email', 'test@example.com')
                    ->type('password', 'password123')
                    ->press('儲存')
                    ->waitFor('.notification')
                    ->assertSee('使用者建立成功');

            // 6. 檢查通知
            $browser->click('.notification-center')
                    ->waitFor('.notification-dropdown')
                    ->assertSee('使用者建立成功');

            // 7. 切換主題
            $browser->click('.theme-toggle')
                    ->waitFor('html.dark', 3);

            // 8. 切換語言
            $browser->click('.language-selector')
                    ->waitFor('.language-dropdown')
                    ->click('a[data-locale="en"]')
                    ->waitForReload()
                    ->assertSee('User Management');

            // 9. 登出
            $browser->click('.user-menu')
                    ->waitFor('.user-dropdown')
                    ->click('.logout-button')
                    ->waitForLocation('/admin/login')
                    ->assertSee('登入');
        });
    }
}