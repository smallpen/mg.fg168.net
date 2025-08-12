<?php

namespace Tests\Feature\Admin\Layout;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Livewire\Livewire;
use App\Livewire\Admin\Layout\TopNavBar;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * 頂部導航列元件測試
 */
class TopNavBarTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立測試使用者和角色
        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理員',
            'description' => '系統管理員',
        ]);

        $this->user = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'username' => 'admin',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->user->roles()->attach($this->adminRole);
    }

    /** @test */
    public function it_can_render_top_nav_bar()
    {
        $this->actingAs($this->user);

        Livewire::test(TopNavBar::class)
            ->assertStatus(200)
            ->assertSee('管理後台')
            ->assertSee($this->user->name);
    }

    /** @test */
    public function it_can_toggle_sidebar()
    {
        $this->actingAs($this->user);

        Livewire::test(TopNavBar::class)
            ->call('toggleSidebar')
            ->assertDispatched('sidebar-toggle');
    }

    /** @test */
    public function it_can_perform_global_search()
    {
        $this->actingAs($this->user);

        Livewire::test(TopNavBar::class)
            ->set('globalSearch', '使用者')
            ->assertSet('showSearchResults', true)
            ->assertCount('searchResults', 1); // 應該找到使用者管理頁面
    }

    /** @test */
    public function it_can_clear_search()
    {
        $this->actingAs($this->user);

        Livewire::test(TopNavBar::class)
            ->set('globalSearch', '測試')
            ->call('clearSearch')
            ->assertSet('globalSearch', '')
            ->assertSet('showSearchResults', false)
            ->assertCount('searchResults', 0);
    }

    /** @test */
    public function it_can_toggle_notifications()
    {
        $this->actingAs($this->user);

        Livewire::test(TopNavBar::class)
            ->call('toggleNotifications')
            ->assertSet('showNotifications', true)
            ->call('toggleNotifications')
            ->assertSet('showNotifications', false);
    }

    /** @test */
    public function it_can_mark_notification_as_read()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(TopNavBar::class);
        
        // 模擬有未讀通知
        $component->set('recentNotifications', [
            [
                'id' => 1,
                'title' => '測試通知',
                'message' => '這是一個測試通知',
                'type' => 'info',
                'read' => false,
                'created_at' => now(),
            ]
        ]);

        $component->call('markAsRead', 1)
            ->assertDispatched('notification-read');
    }

    /** @test */
    public function it_can_mark_all_notifications_as_read()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(TopNavBar::class);
        
        // 模擬有多個未讀通知
        $component->set('recentNotifications', [
            [
                'id' => 1,
                'title' => '通知1',
                'message' => '訊息1',
                'type' => 'info',
                'read' => false,
                'created_at' => now(),
            ],
            [
                'id' => 2,
                'title' => '通知2',
                'message' => '訊息2',
                'type' => 'success',
                'read' => false,
                'created_at' => now(),
            ]
        ]);

        $component->call('markAllAsRead')
            ->assertDispatched('all-notifications-read')
            ->assertSet('unreadNotifications', 0);
    }

    /** @test */
    public function it_can_toggle_user_menu()
    {
        $this->actingAs($this->user);

        Livewire::test(TopNavBar::class)
            ->call('toggleUserMenu')
            ->assertSet('showUserMenu', true)
            ->call('toggleUserMenu')
            ->assertSet('showUserMenu', false);
    }

    /** @test */
    public function it_can_close_all_menus()
    {
        $this->actingAs($this->user);

        Livewire::test(TopNavBar::class)
            ->set('showUserMenu', true)
            ->set('showNotifications', true)
            ->set('showSearchResults', true)
            ->call('closeAllMenus')
            ->assertSet('showUserMenu', false)
            ->assertSet('showNotifications', false)
            ->assertSet('showSearchResults', false);
    }

    /** @test */
    public function it_generates_correct_breadcrumbs()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(TopNavBar::class);
        
        // 測試預設麵包屑
        $breadcrumbs = $component->get('breadcrumbs');
        $this->assertIsArray($breadcrumbs);
        $this->assertCount(1, $breadcrumbs);
        $this->assertEquals('管理後台', $breadcrumbs[0]['title']);
    }

    /** @test */
    public function it_handles_new_notification_event()
    {
        $this->actingAs($this->user);

        $notification = [
            'id' => 999,
            'title' => '新通知',
            'message' => '這是一個新通知',
            'type' => 'info',
            'read' => false,
            'created_at' => now(),
        ];

        Livewire::test(TopNavBar::class)
            ->dispatch('notification-received', $notification)
            ->assertDispatched('show-browser-notification');
    }

    /** @test */
    public function it_displays_user_information_correctly()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(TopNavBar::class);
        
        // 檢查視圖中是否包含使用者資訊
        $component->assertSee('T') // 使用者縮寫
                  ->assertSee('Test Admin') // 使用者名稱
                  ->assertSee('admin@test.com'); // 使用者電子郵件
    }

    /** @test */
    public function it_handles_theme_and_locale_changes()
    {
        $this->actingAs($this->user);

        Livewire::test(TopNavBar::class)
            ->dispatch('theme-changed', 'dark')
            ->dispatch('locale-changed', 'en');
        
        // 驗證事件被正確處理（這裡主要測試不會出錯）
        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_handle_page_title_changes()
    {
        $this->actingAs($this->user);

        Livewire::test(TopNavBar::class)
            ->dispatch('page-title-changed', '使用者管理')
            ->assertSet('pageTitle', '使用者管理');
    }

    /** @test */
    public function it_can_handle_breadcrumbs_changes()
    {
        $this->actingAs($this->user);

        $breadcrumbs = [
            ['title' => '管理後台', 'url' => '/admin'],
            ['title' => '使用者管理', 'url' => null],
        ];

        Livewire::test(TopNavBar::class)
            ->dispatch('breadcrumbs-changed', $breadcrumbs)
            ->assertSet('breadcrumbs', $breadcrumbs);
    }
}