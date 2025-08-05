<?php

namespace Tests\Feature\Livewire\Admin\Layout;

use App\Http\Livewire\Admin\Layout\AdminLayout;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * AdminLayout 元件測試
 * 
 * 測試管理後台主要佈局的渲染、響應式設計和狀態管理
 */
class AdminLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試角色
        $this->adminRole = Role::factory()->create(['name' => 'admin']);
        
        // 建立管理員使用者
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($this->adminRole);
    }

    /**
     * 測試元件能正確渲染
     */
    public function test_component_renders_correctly()
    {
        $this->actingAs($this->admin);

        Livewire::test(AdminLayout::class)
            ->assertStatus(200)
            ->assertSee('管理後台')
            ->assertViewHas('user', $this->admin);
    }

    /**
     * 測試側邊欄顯示狀態
     */
    public function test_sidebar_display_state()
    {
        $this->actingAs($this->admin);

        Livewire::test(AdminLayout::class)
            ->assertSet('sidebarOpen', true) // 預設開啟
            ->call('toggleSidebar')
            ->assertSet('sidebarOpen', false)
            ->call('toggleSidebar')
            ->assertSet('sidebarOpen', true);
    }

    /**
     * 測試響應式側邊欄行為
     */
    public function test_responsive_sidebar_behavior()
    {
        $this->actingAs($this->admin);

        Livewire::test(AdminLayout::class)
            ->set('isMobile', true)
            ->assertSet('sidebarOpen', false) // 行動版預設關閉
            ->call('toggleSidebar')
            ->assertSet('sidebarOpen', true)
            ->assertDispatched('sidebar-toggled');
    }

    /**
     * 測試頂部導航列顯示
     */
    public function test_top_navigation_display()
    {
        $this->actingAs($this->admin);

        Livewire::test(AdminLayout::class)
            ->assertSee($this->admin->name)
            ->assertSee('登出')
            ->assertSee('主題切換')
            ->assertSee('語言選擇');
    }

    /**
     * 測試麵包屑導航
     */
    public function test_breadcrumb_navigation()
    {
        $this->actingAs($this->admin);

        Livewire::test(AdminLayout::class)
            ->set('breadcrumbs', [
                ['name' => '首頁', 'url' => '/admin'],
                ['name' => '使用者管理', 'url' => '/admin/users'],
                ['name' => '編輯使用者', 'url' => null]
            ])
            ->assertSee('首頁')
            ->assertSee('使用者管理')
            ->assertSee('編輯使用者');
    }

    /**
     * 測試頁面標題設定
     */
    public function test_page_title_setting()
    {
        $this->actingAs($this->admin);

        Livewire::test(AdminLayout::class)
            ->call('setPageTitle', '使用者管理')
            ->assertSet('pageTitle', '使用者管理')
            ->assertSee('使用者管理');
    }

    /**
     * 測試通知顯示
     */
    public function test_notification_display()
    {
        $this->actingAs($this->admin);

        Livewire::test(AdminLayout::class)
            ->call('showNotification', 'success', '操作成功')
            ->assertSee('操作成功')
            ->assertSee('success');
    }

    /**
     * 測試載入狀態顯示
     */
    public function test_loading_state_display()
    {
        $this->actingAs($this->admin);

        Livewire::test(AdminLayout::class)
            ->set('isLoading', true)
            ->assertSee('載入中...')
            ->set('isLoading', false)
            ->assertDontSee('載入中...');
    }

    /**
     * 測試權限控制 - 無權限使用者
     */
    public function test_unauthorized_access()
    {
        $unauthorizedUser = User::factory()->create();
        $this->actingAs($unauthorizedUser);

        Livewire::test(AdminLayout::class)
            ->assertRedirect('/admin/login');
    }

    /**
     * 測試使用者選單顯示
     */
    public function test_user_menu_display()
    {
        $this->actingAs($this->admin);

        Livewire::test(AdminLayout::class)
            ->call('toggleUserMenu')
            ->assertSet('userMenuOpen', true)
            ->assertSee('個人資料')
            ->assertSee('帳號設定')
            ->assertSee('登出');
    }

    /**
     * 測試搜尋功能
     */
    public function test_global_search_functionality()
    {
        $this->actingAs($this->admin);

        User::factory()->create(['name' => 'John Doe']);

        Livewire::test(AdminLayout::class)
            ->set('searchTerm', 'John')
            ->call('performSearch')
            ->assertDispatched('search-performed')
            ->assertSee('搜尋結果');
    }

    /**
     * 測試快捷鍵支援
     */
    public function test_keyboard_shortcuts()
    {
        $this->actingAs($this->admin);

        Livewire::test(AdminLayout::class)
            ->call('handleKeyboardShortcut', 'ctrl+k')
            ->assertSet('searchFocused', true)
            ->call('handleKeyboardShortcut', 'ctrl+b')
            ->assertDispatched('sidebar-toggled');
    }

    /**
     * 測試主題狀態管理
     */
    public function test_theme_state_management()
    {
        $this->actingAs($this->admin);

        Livewire::test(AdminLayout::class)
            ->set('currentTheme', 'dark')
            ->assertSet('currentTheme', 'dark')
            ->call('updateTheme', 'light')
            ->assertSet('currentTheme', 'light');
    }

    /**
     * 測試語言狀態管理
     */
    public function test_language_state_management()
    {
        $this->actingAs($this->admin);

        Livewire::test(AdminLayout::class)
            ->set('currentLocale', 'en')
            ->assertSet('currentLocale', 'en')
            ->call('updateLocale', 'zh_TW')
            ->assertSet('currentLocale', 'zh_TW');
    }

    /**
     * 測試側邊欄選單項目顯示
     */
    public function test_sidebar_menu_items()
    {
        $this->actingAs($this->admin);

        Livewire::test(AdminLayout::class)
            ->assertSee('儀表板')
            ->assertSee('使用者管理')
            ->assertSee('角色權限')
            ->assertSee('系統設定');
    }

    /**
     * 測試當前頁面高亮
     */
    public function test_current_page_highlight()
    {
        $this->actingAs($this->admin);

        Livewire::test(AdminLayout::class)
            ->set('currentRoute', 'admin.users.index')
            ->assertSee('active') // 檢查是否有 active 類別
            ->call('setCurrentRoute', 'admin.roles.index')
            ->assertSet('currentRoute', 'admin.roles.index');
    }

    /**
     * 測試頁面載入進度條
     */
    public function test_page_loading_progress()
    {
        $this->actingAs($this->admin);

        Livewire::test(AdminLayout::class)
            ->call('startPageLoading')
            ->assertSet('pageLoadingProgress', 0)
            ->call('updateLoadingProgress', 50)
            ->assertSet('pageLoadingProgress', 50)
            ->call('completePageLoading')
            ->assertSet('pageLoadingProgress', 100);
    }

    /**
     * 測試錯誤處理顯示
     */
    public function test_error_handling_display()
    {
        $this->actingAs($this->admin);

        Livewire::test(AdminLayout::class)
            ->call('showError', '發生錯誤')
            ->assertSee('發生錯誤')
            ->assertSee('error');
    }

    /**
     * 測試全螢幕模式
     */
    public function test_fullscreen_mode()
    {
        $this->actingAs($this->admin);

        Livewire::test(AdminLayout::class)
            ->call('toggleFullscreen')
            ->assertSet('isFullscreen', true)
            ->assertDispatched('fullscreen-toggled')
            ->call('toggleFullscreen')
            ->assertSet('isFullscreen', false);
    }

    /**
     * 測試佈局設定儲存
     */
    public function test_layout_settings_persistence()
    {
        $this->actingAs($this->admin);

        Livewire::test(AdminLayout::class)
            ->set('sidebarOpen', false)
            ->set('currentTheme', 'dark')
            ->call('saveLayoutSettings')
            ->assertDispatched('settings-saved');

        // 檢查設定是否被儲存到 session 或資料庫
        $this->assertEquals('dark', session('admin_theme'));
        $this->assertFalse(session('sidebar_open'));
    }

    /**
     * 測試即時通知系統
     */
    public function test_real_time_notifications()
    {
        $this->actingAs($this->admin);

        Livewire::test(AdminLayout::class)
            ->call('connectToNotifications')
            ->assertDispatched('notifications-connected')
            ->call('receiveNotification', [
                'type' => 'info',
                'message' => '新的系統更新可用'
            ])
            ->assertSee('新的系統更新可用');
    }

    /**
     * 測試多標籤頁支援
     */
    public function test_multi_tab_support()
    {
        $this->actingAs($this->admin);

        Livewire::test(AdminLayout::class)
            ->call('openTab', 'users', '使用者管理')
            ->assertSet('tabs', [['id' => 'users', 'title' => '使用者管理']])
            ->call('closeTab', 'users')
            ->assertSet('tabs', []);
    }

    /**
     * 測試佈局響應式斷點
     */
    public function test_responsive_breakpoints()
    {
        $this->actingAs($this->admin);

        Livewire::test(AdminLayout::class)
            ->set('screenSize', 'mobile')
            ->assertSet('isMobile', true)
            ->set('screenSize', 'desktop')
            ->assertSet('isMobile', false);
    }

    /**
     * 測試輔助功能支援
     */
    public function test_accessibility_support()
    {
        $this->actingAs($this->admin);

        Livewire::test(AdminLayout::class)
            ->assertSee('aria-label')
            ->assertSee('role="navigation"')
            ->call('toggleHighContrast')
            ->assertSet('highContrast', true);
    }
}