<?php

namespace Tests\Browser;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * 管理後台登入瀏覽器測試
 * 
 * 使用 Laravel Dusk 測試完整的使用者登入工作流程
 */
class AdminLoginTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立基本角色
        $this->adminRole = Role::factory()->create(['name' => 'admin']);
        $this->userRole = Role::factory()->create(['name' => 'user']);
    }

    /**
     * 測試管理員登入流程
     */
    public function test_admin_can_login_through_browser()
    {
        $admin = User::factory()->create([
            'username' => 'admin',
            'password' => bcrypt('password123'),
            'is_active' => true
        ]);
        $admin->roles()->attach($this->adminRole);

        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/login')
                    ->assertSee('登入')
                    ->type('username', 'admin')
                    ->type('password', 'password123')
                    ->press('登入')
                    ->waitForLocation('/admin/dashboard')
                    ->assertPathIs('/admin/dashboard')
                    ->assertSee('儀表板');
        });
    }

    /**
     * 測試登入表單驗證
     */
    public function test_login_form_validation()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/login')
                    ->press('登入')
                    ->waitForText('請輸入使用者名稱')
                    ->assertSee('請輸入使用者名稱')
                    ->assertSee('請輸入密碼');
        });
    }

    /**
     * 測試錯誤登入憑證
     */
    public function test_invalid_login_credentials()
    {
        $admin = User::factory()->create([
            'username' => 'admin',
            'password' => bcrypt('password123'),
            'is_active' => true
        ]);
        $admin->roles()->attach($this->adminRole);

        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/login')
                    ->type('username', 'admin')
                    ->type('password', 'wrongpassword')
                    ->press('登入')
                    ->waitForText('使用者名稱或密碼錯誤')
                    ->assertSee('使用者名稱或密碼錯誤')
                    ->assertPathIs('/admin/login');
        });
    }

    /**
     * 測試一般使用者無法存取管理後台
     */
    public function test_regular_user_cannot_access_admin()
    {
        $user = User::factory()->create([
            'username' => 'user',
            'password' => bcrypt('password123'),
            'is_active' => true
        ]);
        $user->roles()->attach($this->userRole);

        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/login')
                    ->type('username', 'user')
                    ->type('password', 'password123')
                    ->press('登入')
                    ->waitForText('您沒有權限存取管理後台')
                    ->assertSee('您沒有權限存取管理後台')
                    ->assertPathIs('/admin/login');
        });
    }

    /**
     * 測試記住我功能
     */
    public function test_remember_me_functionality()
    {
        $admin = User::factory()->create([
            'username' => 'admin',
            'password' => bcrypt('password123'),
            'is_active' => true
        ]);
        $admin->roles()->attach($this->adminRole);

        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/login')
                    ->type('username', 'admin')
                    ->type('password', 'password123')
                    ->check('remember')
                    ->press('登入')
                    ->waitForLocation('/admin/dashboard')
                    ->assertPathIs('/admin/dashboard');

            // 檢查記住我 cookie 是否存在
            $cookies = $browser->driver->manage()->getCookies();
            $rememberCookie = collect($cookies)->firstWhere('name', 'remember_web_' . sha1(config('app.key')));
            $this->assertNotNull($rememberCookie);
        });
    }

    /**
     * 測試登出功能
     */
    public function test_admin_can_logout()
    {
        $admin = User::factory()->create([
            'username' => 'admin',
            'password' => bcrypt('password123'),
            'is_active' => true
        ]);
        $admin->roles()->attach($this->adminRole);

        $this->browse(function (Browser $browser) {
            // 先登入
            $browser->visit('/admin/login')
                    ->type('username', 'admin')
                    ->type('password', 'password123')
                    ->press('登入')
                    ->waitForLocation('/admin/dashboard');

            // 執行登出
            $browser->click('@logout-button')
                    ->waitForLocation('/admin/login')
                    ->assertPathIs('/admin/login')
                    ->assertSee('登入');
        });
    }

    /**
     * 測試未認證使用者重新導向
     */
    public function test_unauthenticated_user_redirect()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/dashboard')
                    ->waitForLocation('/admin/login')
                    ->assertPathIs('/admin/login')
                    ->assertSee('登入');

            $browser->visit('/admin/users')
                    ->waitForLocation('/admin/login')
                    ->assertPathIs('/admin/login');
        });
    }

    /**
     * 測試停用使用者無法登入
     */
    public function test_inactive_user_cannot_login()
    {
        $admin = User::factory()->create([
            'username' => 'admin',
            'password' => bcrypt('password123'),
            'is_active' => false
        ]);
        $admin->roles()->attach($this->adminRole);

        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/login')
                    ->type('username', 'admin')
                    ->type('password', 'password123')
                    ->press('登入')
                    ->waitForText('帳號已被停用')
                    ->assertSee('帳號已被停用')
                    ->assertPathIs('/admin/login');
        });
    }

    /**
     * 測試登入頁面響應式設計
     */
    public function test_login_page_responsive_design()
    {
        $this->browse(function (Browser $browser) {
            // 測試桌面版
            $browser->resize(1200, 800)
                    ->visit('/admin/login')
                    ->assertSee('登入')
                    ->assertVisible('input[name="username"]')
                    ->assertVisible('input[name="password"]');

            // 測試平板版
            $browser->resize(768, 1024)
                    ->refresh()
                    ->assertSee('登入')
                    ->assertVisible('input[name="username"]')
                    ->assertVisible('input[name="password"]');

            // 測試手機版
            $browser->resize(375, 667)
                    ->refresh()
                    ->assertSee('登入')
                    ->assertVisible('input[name="username"]')
                    ->assertVisible('input[name="password"]');
        });
    }

    /**
     * 測試鍵盤導航
     */
    public function test_keyboard_navigation()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/login')
                    ->keys('input[name="username"]', '{tab}')
                    ->assertFocused('input[name="password"]')
                    ->keys('input[name="password"]', '{tab}')
                    ->assertFocused('button[type="submit"]');
        });
    }

    /**
     * 測試表單自動完成
     */
    public function test_form_autocomplete()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/login')
                    ->assertAttribute('input[name="username"]', 'autocomplete', 'username')
                    ->assertAttribute('input[name="password"]', 'autocomplete', 'current-password');
        });
    }

    /**
     * 測試 CSRF 保護
     */
    public function test_csrf_protection()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/login')
                    ->assertPresent('input[name="_token"]');
        });
    }

    /**
     * 測試載入狀態
     */
    public function test_loading_state()
    {
        $admin = User::factory()->create([
            'username' => 'admin',
            'password' => bcrypt('password123'),
            'is_active' => true
        ]);
        $admin->roles()->attach($this->adminRole);

        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/login')
                    ->type('username', 'admin')
                    ->type('password', 'password123')
                    ->press('登入')
                    ->waitUntilMissing('.loading-spinner', 5)
                    ->assertPathIs('/admin/dashboard');
        });
    }

    /**
     * 測試錯誤訊息顯示
     */
    public function test_error_message_display()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/login')
                    ->type('username', 'nonexistent')
                    ->type('password', 'wrongpassword')
                    ->press('登入')
                    ->waitForText('使用者名稱或密碼錯誤')
                    ->assertSee('使用者名稱或密碼錯誤')
                    ->assertPresent('.error-message');
        });
    }

    /**
     * 測試密碼顯示/隱藏功能
     */
    public function test_password_visibility_toggle()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/login')
                    ->type('password', 'testpassword')
                    ->assertAttribute('input[name="password"]', 'type', 'password')
                    ->click('.password-toggle')
                    ->assertAttribute('input[name="password"]', 'type', 'text')
                    ->click('.password-toggle')
                    ->assertAttribute('input[name="password"]', 'type', 'password');
        });
    }
}