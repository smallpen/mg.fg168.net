<?php

namespace Tests\Browser;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * 使用者管理瀏覽器自動化測試
 * 
 * 使用 Laravel Dusk 進行端到端測試，模擬真實使用者操作
 * 需求: 6.1, 6.2, 6.3, 7.1, 7.2, 9.1
 */
class UserManagementBrowserTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $admin;
    protected User $regularUser;
    protected Role $adminRole;
    protected Role $userRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試角色
        $this->adminRole = Role::factory()->create([
            'name' => 'admin',
            'display_name' => '管理員'
        ]);
        
        $this->userRole = Role::factory()->create([
            'name' => 'user',
            'display_name' => '一般使用者'
        ]);

        // 建立測試使用者
        $this->admin = User::factory()->create([
            'username' => 'admin',
            'name' => '管理員',
            'email' => 'admin@example.com',
            'password' => bcrypt('password')
        ]);
        $this->admin->roles()->attach($this->adminRole);

        $this->regularUser = User::factory()->create([
            'username' => 'user',
            'name' => '一般使用者',
            'email' => 'user@example.com',
            'password' => bcrypt('password')
        ]);
        $this->regularUser->roles()->attach($this->userRole);
    }

    /**
     * 測試完整的使用者管理工作流程
     */
    public function test_complete_user_management_workflow()
    {
        $this->browse(function (Browser $browser) {
            // 1. 登入系統
            $browser->visit('/admin/login')
                    ->type('username', 'admin')
                    ->type('password', 'password')
                    ->press('登入')
                    ->waitForLocation('/admin/dashboard')
                    ->assertSee('Dashboard');

            // 2. 導航到使用者管理頁面
            $browser->click('a[href="/admin/users"]')
                    ->waitForLocation('/admin/users')
                    ->assertSee('使用者管理')
                    ->assertSee('建立使用者');

            // 3. 測試搜尋功能
            $browser->type('input[wire\\:model\\.live\\.debounce\\.300ms="search"]', 'admin')
                    ->waitFor('.user-row')
                    ->assertSee($this->admin->username)
                    ->assertDontSee($this->regularUser->username);

            // 清除搜尋
            $browser->clear('input[wire\\:model\\.live\\.debounce\\.300ms="search"]')
                    ->waitFor('.user-row')
                    ->assertSee($this->admin->username)
                    ->assertSee($this->regularUser->username);

            // 4. 測試狀態篩選
            $browser->select('select[wire\\:model="statusFilter"]', 'active')
                    ->waitFor('.user-row')
                    ->assertSee($this->admin->username);

            // 5. 測試角色篩選
            $browser->select('select[wire\\:model="roleFilter"]', 'admin')
                    ->waitFor('.user-row')
                    ->assertSee($this->admin->username)
                    ->assertDontSee($this->regularUser->username);

            // 重置篩選
            $browser->click('button[wire\\:click="resetFilters"]')
                    ->waitFor('.user-row')
                    ->assertSee($this->admin->username)
                    ->assertSee($this->regularUser->username);

            // 6. 測試排序功能
            $browser->click('th[wire\\:click="sortBy(\'name\')"]')
                    ->waitFor('.user-row')
                    ->assertSee($this->admin->name);

            // 7. 測試分頁功能（如果有多頁資料）
            User::factory()->count(20)->create();
            
            $browser->refresh()
                    ->waitFor('.user-row')
                    ->assertSee('下一頁')
                    ->click('a[rel="next"]')
                    ->waitForLocation('/admin/users?page=2')
                    ->assertSee('上一頁');

            // 8. 測試使用者詳情檢視
            $browser->visit('/admin/users')
                    ->waitFor('.user-row')
                    ->click('.view-user-btn')
                    ->waitForRoute('admin.users.show', $this->admin)
                    ->assertSee($this->admin->name)
                    ->assertSee($this->admin->email);

            // 9. 測試使用者編輯
            $browser->visit('/admin/users')
                    ->waitFor('.user-row')
                    ->click('.edit-user-btn')
                    ->waitForRoute('admin.users.edit', $this->admin)
                    ->assertSee('編輯使用者')
                    ->type('name', '更新的管理員')
                    ->press('更新使用者')
                    ->waitForLocation('/admin/users')
                    ->assertSee('更新的管理員');

            // 10. 測試使用者狀態切換
            $browser->click('.toggle-status-btn')
                    ->waitForText('已停用')
                    ->assertSee('已停用');

            // 11. 測試批量選擇
            $browser->check('input[type="checkbox"][wire\\:model="selectAll"]')
                    ->assertChecked('input[type="checkbox"][wire\\:model="selectAll"]')
                    ->assertChecked('.user-checkbox');

            // 12. 測試批量操作
            $browser->click('button[wire\\:click="bulkActivate"]')
                    ->waitForText('批量啟用成功')
                    ->assertSee('批量啟用成功');

            // 13. 測試使用者建立
            $browser->click('a[href="/admin/users/create"]')
                    ->waitForLocation('/admin/users/create')
                    ->assertSee('建立使用者')
                    ->type('username', 'newuser')
                    ->type('name', '新使用者')
                    ->type('email', 'newuser@example.com')
                    ->type('password', 'password123')
                    ->type('password_confirmation', 'password123')
                    ->check('roles[]', $this->userRole->id)
                    ->press('建立使用者')
                    ->waitForLocation('/admin/users')
                    ->assertSee('newuser');

            // 14. 測試使用者刪除
            $newUser = User::where('username', 'newuser')->first();
            
            $browser->click(".delete-user-btn[data-user-id=\"{$newUser->id}\"]")
                    ->waitForText('確認刪除')
                    ->assertSee('確認刪除')
                    ->click('button[wire\\:click="confirmDelete"]')
                    ->waitForText('使用者已刪除')
                    ->assertSee('使用者已刪除')
                    ->assertDontSee('newuser');
        });
    }

    /**
     * 測試響應式設計 - 桌面版本
     * 需求: 6.1
     */
    public function test_desktop_responsive_design()
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(1920, 1080) // 桌面解析度
                    ->loginAs($this->admin)
                    ->visit('/admin/users')
                    ->waitFor('.user-table')
                    ->assertVisible('.user-table')
                    ->assertVisible('th:contains("建立時間")') // 桌面版顯示完整欄位
                    ->assertVisible('th:contains("操作")')
                    ->assertVisible('.search-box')
                    ->assertVisible('.filter-controls')
                    ->assertVisible('.bulk-actions');

            // 檢查表格欄位是否完整顯示
            $browser->assertSee('使用者名稱')
                    ->assertSee('姓名')
                    ->assertSee('電子郵件')
                    ->assertSee('角色')
                    ->assertSee('狀態')
                    ->assertSee('建立時間')
                    ->assertSee('操作');
        });
    }

    /**
     * 測試響應式設計 - 平板版本
     * 需求: 6.2
     */
    public function test_tablet_responsive_design()
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(768, 1024) // 平板解析度
                    ->loginAs($this->admin)
                    ->visit('/admin/users')
                    ->waitFor('.user-table')
                    ->assertVisible('.user-table')
                    ->assertVisible('.search-box')
                    ->assertVisible('.filter-controls');

            // 平板版本可能隱藏某些次要欄位
            $browser->assertSee('使用者名稱')
                    ->assertSee('姓名')
                    ->assertSee('狀態');

            // 檢查篩選器是否適當調整
            $browser->assertVisible('select[wire\\:model="statusFilter"]')
                    ->assertVisible('select[wire\\:model="roleFilter"]');
        });
    }

    /**
     * 測試響應式設計 - 手機版本
     * 需求: 6.3
     */
    public function test_mobile_responsive_design()
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667) // iPhone 解析度
                    ->loginAs($this->admin)
                    ->visit('/admin/users')
                    ->waitFor('.user-cards') // 手機版使用卡片佈局
                    ->assertVisible('.user-cards')
                    ->assertVisible('.mobile-search')
                    ->assertVisible('.mobile-filters');

            // 檢查卡片式佈局
            $browser->assertVisible('.user-card')
                    ->assertSee($this->admin->name)
                    ->assertSee($this->admin->username);

            // 檢查手機版篩選器
            $browser->click('.mobile-filter-toggle')
                    ->waitFor('.mobile-filter-panel')
                    ->assertVisible('.mobile-filter-panel')
                    ->assertVisible('select[wire\\:model="statusFilter"]');

            // 測試手機版操作選單
            $browser->click('.mobile-actions-toggle')
                    ->waitFor('.mobile-actions-menu')
                    ->assertVisible('.mobile-actions-menu')
                    ->assertSee('檢視')
                    ->assertSee('編輯');
        });
    }

    /**
     * 測試效能要求 - 載入時間
     * 需求: 7.1
     */
    public function test_page_load_performance()
    {
        // 建立大量測試資料
        User::factory()->count(50)->create();

        $this->browse(function (Browser $browser) {
            $startTime = microtime(true);
            
            $browser->loginAs($this->admin)
                    ->visit('/admin/users')
                    ->waitFor('.user-row', 5); // 最多等待 5 秒
            
            $loadTime = microtime(true) - $startTime;
            
            // 檢查載入時間是否在 2 秒內
            $this->assertLessThan(2.0, $loadTime, '頁面載入時間應少於 2 秒');
            
            $browser->assertSee('使用者管理');
        });
    }

    /**
     * 測試搜尋響應時間
     * 需求: 7.2
     */
    public function test_search_response_time()
    {
        // 建立測試資料
        User::factory()->count(30)->create();

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/admin/users')
                    ->waitFor('.user-row');

            $startTime = microtime(true);
            
            $browser->type('input[wire\\:model\\.live\\.debounce\\.300ms="search"]', 'admin')
                    ->waitFor('.user-row', 2); // 最多等待 2 秒
            
            $searchTime = microtime(true) - $startTime;
            
            // 檢查搜尋響應時間是否在 1 秒內（加上防抖延遲）
            $this->assertLessThan(1.5, $searchTime, '搜尋響應時間應少於 1.5 秒');
            
            $browser->assertSee($this->admin->username);
        });
    }

    /**
     * 測試權限控制
     * 需求: 9.1
     */
    public function test_permission_control()
    {
        $this->browse(function (Browser $browser) {
            // 測試管理員權限
            $browser->loginAs($this->admin)
                    ->visit('/admin/users')
                    ->waitFor('.user-row')
                    ->assertSee('建立使用者')
                    ->assertVisible('.edit-user-btn')
                    ->assertVisible('.delete-user-btn');

            // 登出並以一般使用者登入
            $browser->click('.logout-btn')
                    ->waitForLocation('/admin/login')
                    ->type('username', 'user')
                    ->type('password', 'password')
                    ->press('登入')
                    ->waitForLocation('/admin/dashboard');

            // 嘗試存取使用者管理頁面
            $browser->visit('/admin/users')
                    ->assertSee('403') // 或其他權限錯誤訊息
                    ->assertDontSee('使用者管理');
        });
    }

    /**
     * 測試錯誤處理
     */
    public function test_error_handling()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/admin/users')
                    ->waitFor('.user-row');

            // 測試無效搜尋輸入
            $browser->type('input[wire\\:model\\.live\\.debounce\\.300ms="search"]', '<script>alert("xss")</script>')
                    ->pause(500)
                    ->assertInputValue('input[wire\\:model\\.live\\.debounce\\.300ms="search"]', ''); // 應該被清除

            // 測試網路錯誤恢復
            // 這裡可以模擬網路中斷情況
            $browser->refresh()
                    ->waitFor('.user-row')
                    ->assertSee('使用者管理');
        });
    }

    /**
     * 測試多語言支援
     * 需求: 8.1, 8.2, 8.3
     */
    public function test_multilingual_support()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/admin/users')
                    ->waitFor('.user-row');

            // 檢查正體中文介面
            $browser->assertSee('使用者管理')
                    ->assertSee('建立使用者')
                    ->assertSee('搜尋')
                    ->assertSee('狀態')
                    ->assertSee('角色')
                    ->assertSee('操作');

            // 檢查狀態本地化
            $browser->assertSee('啟用')
                    ->assertSee('停用');

            // 檢查日期時間格式
            $browser->assertSeeIn('.created-at', date('Y-m-d')); // 檢查日期格式
        });
    }

    /**
     * 測試鍵盤導航和無障礙功能
     */
    public function test_keyboard_navigation_and_accessibility()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/admin/users')
                    ->waitFor('.user-row');

            // 測試 Tab 鍵導航
            $browser->keys('body', '{tab}')
                    ->assertFocused('.search-box input')
                    ->keys('body', '{tab}')
                    ->assertFocused('select[wire\\:model="statusFilter"]');

            // 測試 Enter 鍵操作
            $browser->click('.search-box input')
                    ->type('.search-box input', 'admin')
                    ->keys('.search-box input', '{enter}')
                    ->waitFor('.user-row')
                    ->assertSee($this->admin->username);

            // 檢查 ARIA 標籤
            $browser->assertAttribute('.search-box input', 'aria-label', '搜尋使用者')
                    ->assertAttribute('select[wire\\:model="statusFilter"]', 'aria-label', '篩選狀態');
        });
    }

    /**
     * 測試即時更新功能
     */
    public function test_real_time_updates()
    {
        $this->browse(function (Browser $browser1, Browser $browser2) {
            // 兩個瀏覽器同時登入
            $browser1->loginAs($this->admin)
                     ->visit('/admin/users')
                     ->waitFor('.user-row');

            $browser2->loginAs($this->admin)
                     ->visit('/admin/users')
                     ->waitFor('.user-row');

            // 在第一個瀏覽器中切換使用者狀態
            $browser1->click('.toggle-status-btn')
                     ->waitForText('已停用');

            // 檢查第二個瀏覽器是否即時更新
            $browser2->waitForText('已停用', 5)
                     ->assertSee('已停用');
        });
    }

    /**
     * 測試批量操作的使用者體驗
     */
    public function test_bulk_operations_user_experience()
    {
        // 建立多個測試使用者
        $users = User::factory()->count(5)->create();

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/admin/users')
                    ->waitFor('.user-row');

            // 測試全選功能
            $browser->check('input[type="checkbox"][wire\\:model="selectAll"]')
                    ->assertChecked('input[type="checkbox"][wire\\:model="selectAll"]');

            // 檢查所有使用者是否被選中
            $browser->script('
                const checkboxes = document.querySelectorAll(".user-checkbox");
                return Array.from(checkboxes).every(cb => cb.checked);
            ');

            // 測試批量操作確認對話框
            $browser->click('button[wire\\:click="bulkDeactivate"]')
                    ->waitForText('確認批量停用')
                    ->assertSee('確認批量停用')
                    ->click('button[data-confirm="yes"]')
                    ->waitForText('批量操作成功')
                    ->assertSee('批量操作成功');

            // 檢查操作結果
            $browser->assertSee('已停用');
        });
    }
}