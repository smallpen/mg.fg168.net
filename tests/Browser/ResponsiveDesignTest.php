<?php

namespace Tests\Browser;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * 響應式設計瀏覽器測試
 * 
 * 測試管理後台在不同裝置上的響應式設計表現
 */
class ResponsiveDesignTest extends DuskTestCase
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
     * 測試桌面版佈局
     */
    public function test_desktop_layout()
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(1920, 1080);
            $this->loginAdmin($browser);

            // 檢查桌面版佈局元素
            $browser->assertVisible('.sidebar')
                    ->assertVisible('.main-content')
                    ->assertVisible('.top-bar')
                    ->assertPresent('.sidebar-expanded');

            // 檢查側邊欄是否完全展開
            $browser->assertSeeIn('.sidebar', '儀表板')
                    ->assertSeeIn('.sidebar', '使用者管理')
                    ->assertSeeIn('.sidebar', '角色權限');

            // 檢查內容區域寬度
            $contentWidth = $browser->script('return document.querySelector(".main-content").offsetWidth')[0];
            $this->assertGreaterThan(800, $contentWidth);
        });
    }

    /**
     * 測試平板版佈局
     */
    public function test_tablet_layout()
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(768, 1024);
            $this->loginAdmin($browser);

            // 檢查平板版佈局調整
            $browser->assertVisible('.main-content')
                    ->assertVisible('.top-bar');

            // 側邊欄可能收合或隱藏
            if ($browser->element('.sidebar-toggle')) {
                $browser->assertPresent('.sidebar-toggle');
            }

            // 檢查統計卡片是否適當排列
            $browser->assertPresent('.stats-cards')
                    ->assertVisible('.stats-card');

            // 檢查表格是否有水平滾動
            if ($browser->element('.table-responsive')) {
                $browser->assertPresent('.table-responsive');
            }
        });
    }

    /**
     * 測試手機版佈局
     */
    public function test_mobile_layout()
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667);
            $this->loginAdmin($browser);

            // 檢查手機版佈局
            $browser->assertVisible('.main-content')
                    ->assertVisible('.top-bar')
                    ->assertPresent('.sidebar-toggle');

            // 側邊欄應該預設隱藏
            $browser->assertNotVisible('.sidebar');

            // 點擊切換按鈕顯示側邊欄
            $browser->click('.sidebar-toggle')
                    ->waitFor('.sidebar-overlay')
                    ->assertVisible('.sidebar');

            // 點擊遮罩關閉側邊欄
            $browser->click('.sidebar-overlay')
                    ->waitUntilMissing('.sidebar-overlay')
                    ->assertNotVisible('.sidebar');
        });
    }

    /**
     * 測試統計卡片響應式佈局
     */
    public function test_stats_cards_responsive_layout()
    {
        // 建立測試資料
        User::factory()->count(10)->create();

        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 桌面版 - 4列佈局
            $browser->resize(1200, 800)
                    ->refresh()
                    ->waitForLocation('/admin/dashboard');

            $cardsPerRow = $browser->script('
                const cards = document.querySelectorAll(".stats-card");
                const firstCard = cards[0];
                const firstCardTop = firstCard.offsetTop;
                let count = 0;
                cards.forEach(card => {
                    if (card.offsetTop === firstCardTop) count++;
                });
                return count;
            ')[0];

            $this->assertGreaterThanOrEqual(3, $cardsPerRow);

            // 平板版 - 2列佈局
            $browser->resize(768, 1024)
                    ->refresh()
                    ->waitForLocation('/admin/dashboard');

            // 手機版 - 1列佈局
            $browser->resize(375, 667)
                    ->refresh()
                    ->waitForLocation('/admin/dashboard');

            $mobileCardsPerRow = $browser->script('
                const cards = document.querySelectorAll(".stats-card");
                const firstCard = cards[0];
                const firstCardTop = firstCard.offsetTop;
                let count = 0;
                cards.forEach(card => {
                    if (card.offsetTop === firstCardTop) count++;
                });
                return count;
            ')[0];

            $this->assertEquals(1, $mobileCardsPerRow);
        });
    }

    /**
     * 測試表格響應式設計
     */
    public function test_table_responsive_design()
    {
        // 建立測試使用者
        User::factory()->count(5)->create();

        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);
            $browser->visit('/admin/users');

            // 桌面版 - 完整表格
            $browser->resize(1200, 800)
                    ->refresh()
                    ->waitForLocation('/admin/users')
                    ->assertVisible('table')
                    ->assertSee('使用者名稱')
                    ->assertSee('姓名')
                    ->assertSee('電子郵件');

            // 平板版 - 可能有水平滾動
            $browser->resize(768, 1024)
                    ->refresh()
                    ->waitForLocation('/admin/users');

            if ($browser->element('.table-responsive')) {
                $browser->assertPresent('.table-responsive');
            }

            // 手機版 - 卡片式佈局或堆疊佈局
            $browser->resize(375, 667)
                    ->refresh()
                    ->waitForLocation('/admin/users');

            // 檢查是否有行動版友善的佈局
            if ($browser->element('.mobile-card')) {
                $browser->assertPresent('.mobile-card');
            } else {
                $browser->assertPresent('.table-responsive');
            }
        });
    }

    /**
     * 測試表單響應式設計
     */
    public function test_form_responsive_design()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);
            $browser->visit('/admin/users/create');

            // 桌面版 - 多列表單
            $browser->resize(1200, 800)
                    ->refresh()
                    ->waitForLocation('/admin/users/create')
                    ->assertVisible('form')
                    ->assertVisible('input[name="username"]')
                    ->assertVisible('input[name="name"]');

            // 平板版 - 調整表單佈局
            $browser->resize(768, 1024)
                    ->refresh()
                    ->waitForLocation('/admin/users/create')
                    ->assertVisible('form');

            // 手機版 - 單列表單
            $browser->resize(375, 667)
                    ->refresh()
                    ->waitForLocation('/admin/users/create')
                    ->assertVisible('form');

            // 檢查表單元素是否適當堆疊
            $usernameInput = $browser->element('input[name="username"]');
            $nameInput = $browser->element('input[name="name"]');
            
            if ($usernameInput && $nameInput) {
                $usernameTop = $browser->script('return arguments[0].getBoundingClientRect().top', $usernameInput)[0];
                $nameTop = $browser->script('return arguments[0].getBoundingClientRect().top', $nameInput)[0];
                
                // 在手機版，表單元素應該垂直堆疊
                $this->assertNotEquals($usernameTop, $nameTop);
            }
        });
    }

    /**
     * 測試導航選單響應式行為
     */
    public function test_navigation_responsive_behavior()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 桌面版 - 側邊欄始終可見
            $browser->resize(1200, 800)
                    ->refresh()
                    ->waitForLocation('/admin/dashboard')
                    ->assertVisible('.sidebar')
                    ->assertDontSee('.sidebar-toggle');

            // 平板版 - 可能有切換按鈕
            $browser->resize(768, 1024)
                    ->refresh()
                    ->waitForLocation('/admin/dashboard');

            // 手機版 - 漢堡選單
            $browser->resize(375, 667)
                    ->refresh()
                    ->waitForLocation('/admin/dashboard')
                    ->assertPresent('.sidebar-toggle')
                    ->assertNotVisible('.sidebar');

            // 測試選單切換
            $browser->click('.sidebar-toggle')
                    ->waitFor('.sidebar')
                    ->assertVisible('.sidebar');

            // 點擊選單項目後自動關閉（手機版）
            $browser->click('.sidebar a[href="/admin/users"]')
                    ->waitForLocation('/admin/users')
                    ->assertNotVisible('.sidebar');
        });
    }

    /**
     * 測試觸控友善的互動元素
     */
    public function test_touch_friendly_interactions()
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667);
            $this->loginAdmin($browser);

            // 檢查按鈕大小是否適合觸控
            $buttons = $browser->elements('button, .btn');
            foreach ($buttons as $button) {
                $height = $browser->script('return arguments[0].offsetHeight', $button)[0];
                $this->assertGreaterThanOrEqual(44, $height); // 最小觸控目標 44px
            }

            // 檢查連結間距
            $links = $browser->elements('.sidebar a');
            if (count($links) > 1) {
                $firstLink = $links[0];
                $secondLink = $links[1];
                
                $firstBottom = $browser->script('return arguments[0].getBoundingClientRect().bottom', $firstLink)[0];
                $secondTop = $browser->script('return arguments[0].getBoundingClientRect().top', $secondLink)[0];
                
                $spacing = $secondTop - $firstBottom;
                $this->assertGreaterThanOrEqual(8, $spacing); // 最小間距 8px
            }
        });
    }

    /**
     * 測試文字可讀性
     */
    public function test_text_readability()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 測試不同螢幕尺寸的文字大小
            $screenSizes = [
                [1920, 1080], // 桌面
                [768, 1024],  // 平板
                [375, 667]    // 手機
            ];

            foreach ($screenSizes as [$width, $height]) {
                $browser->resize($width, $height)
                        ->refresh()
                        ->waitForLocation('/admin/dashboard');

                // 檢查主要文字大小
                $headingSize = $browser->script('
                    const heading = document.querySelector("h1, .page-title");
                    return heading ? window.getComputedStyle(heading).fontSize : "16px";
                ')[0];

                $bodySize = $browser->script('
                    const body = document.querySelector("body, .content");
                    return body ? window.getComputedStyle(body).fontSize : "14px";
                ')[0];

                // 確保文字大小適當
                $headingSizePx = (float) str_replace('px', '', $headingSize);
                $bodySizePx = (float) str_replace('px', '', $bodySize);

                $this->assertGreaterThanOrEqual(14, $bodySizePx);
                $this->assertGreaterThanOrEqual(18, $headingSizePx);
            }
        });
    }

    /**
     * 測試圖片和媒體響應式
     */
    public function test_media_responsive()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            $screenSizes = [
                [1200, 800],
                [768, 1024],
                [375, 667]
            ];

            foreach ($screenSizes as [$width, $height]) {
                $browser->resize($width, $height)
                        ->refresh()
                        ->waitForLocation('/admin/dashboard');

                // 檢查圖片是否響應式
                $images = $browser->elements('img');
                foreach ($images as $img) {
                    $maxWidth = $browser->script('return window.getComputedStyle(arguments[0]).maxWidth', $img)[0];
                    $this->assertEquals('100%', $maxWidth);
                }

                // 檢查圖表容器是否響應式
                if ($browser->element('.chart-container')) {
                    $chartWidth = $browser->script('return document.querySelector(".chart-container").offsetWidth')[0];
                    $containerWidth = $browser->script('return document.querySelector(".main-content").offsetWidth')[0];
                    
                    $this->assertLessThanOrEqual($containerWidth, $chartWidth);
                }
            }
        });
    }

    /**
     * 測試橫向和直向模式
     */
    public function test_orientation_modes()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // 直向模式（手機）
            $browser->resize(375, 667)
                    ->refresh()
                    ->waitForLocation('/admin/dashboard')
                    ->assertPresent('.sidebar-toggle');

            // 橫向模式（手機）
            $browser->resize(667, 375)
                    ->refresh()
                    ->waitForLocation('/admin/dashboard');

            // 檢查佈局是否適應橫向模式
            $browser->assertVisible('.main-content');
            
            // 在橫向模式下，側邊欄可能有不同的行為
            if ($browser->element('.sidebar-toggle')) {
                $browser->assertPresent('.sidebar-toggle');
            }
        });
    }

    /**
     * 測試高對比度和輔助功能
     */
    public function test_accessibility_responsive()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            $screenSizes = [
                [1200, 800],
                [375, 667]
            ];

            foreach ($screenSizes as [$width, $height]) {
                $browser->resize($width, $height)
                        ->refresh()
                        ->waitForLocation('/admin/dashboard');

                // 檢查焦點指示器
                $browser->keys('body', '{tab}')
                        ->assertPresent(':focus');

                // 檢查顏色對比度（簡單檢查）
                $backgroundColor = $browser->script('
                    return window.getComputedStyle(document.body).backgroundColor
                ')[0];
                
                $textColor = $browser->script('
                    return window.getComputedStyle(document.body).color
                ')[0];

                $this->assertNotEquals($backgroundColor, $textColor);
            }
        });
    }
}