<?php

namespace Tests\Feature\Livewire\Admin\Layout;

use App\Livewire\Admin\Layout\AdminLayout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * AdminLayout 元件功能測試
 * 
 * 測試管理後台主佈局元件的各項功能，包括：
 * - 響應式佈局適應
 * - 側邊欄狀態管理
 * - 主題切換功能
 * - 頁面資訊管理
 * - 事件處理機制
 */
class AdminLayoutTest extends TestCase
{
    use RefreshDatabase;
    
    protected User $user;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試使用者
        $this->user = User::factory()->create([
            'theme_preference' => 'light',
            'locale' => 'zh_TW'
        ]);
        
        $this->actingAs($this->user);
    }
    
    /** @test */
    public function 可以正常渲染佈局元件()
    {
        Livewire::test(AdminLayout::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.admin.layout.admin-layout');
    }
    
    /** @test */
    public function 初始化時載入正確的預設狀態()
    {
        Livewire::test(AdminLayout::class)
            ->assertSet('sidebarCollapsed', false)
            ->assertSet('sidebarMobile', false)
            ->assertSet('currentTheme', 'light')
            ->assertSet('currentLocale', 'zh_TW')
            ->assertSet('isMobile', false)
            ->assertSet('isTablet', false);
    }
    
    /** @test */
    public function 可以切換側邊欄收合狀態()
    {
        Livewire::test(AdminLayout::class)
            ->assertSet('sidebarCollapsed', false)
            ->call('toggleSidebar')
            ->assertSet('sidebarCollapsed', true)
            ->assertDispatched('sidebar-toggled', collapsed: true)
            ->call('toggleSidebar')
            ->assertSet('sidebarCollapsed', false)
            ->assertDispatched('sidebar-toggled', collapsed: false);
    }
    
    /** @test */
    public function 可以切換行動版側邊欄狀態()
    {
        Livewire::test(AdminLayout::class)
            ->assertSet('sidebarMobile', false)
            ->call('toggleMobileSidebar')
            ->assertSet('sidebarMobile', true)
            ->assertDispatched('mobile-sidebar-toggled', open: true)
            ->call('toggleMobileSidebar')
            ->assertSet('sidebarMobile', false)
            ->assertDispatched('mobile-sidebar-toggled', open: false);
    }
    
    /** @test */
    public function 可以設定和切換主題()
    {
        Livewire::test(AdminLayout::class)
            ->assertSet('currentTheme', 'light')
            ->call('setTheme', 'dark')
            ->assertSet('currentTheme', 'dark')
            ->assertDispatched('theme-changed', theme: 'dark');
        
        // 驗證使用者偏好已儲存
        $this->assertEquals('dark', $this->user->fresh()->theme_preference);
    }
    
    /** @test */
    public function 可以設定和切換語言()
    {
        Livewire::test(AdminLayout::class)
            ->assertSet('currentLocale', 'zh_TW')
            ->call('setLocale', 'en')
            ->assertSet('currentLocale', 'en')
            ->assertDispatched('locale-changed', locale: 'en');
        
        // 驗證使用者偏好已儲存
        $this->assertEquals('en', $this->user->fresh()->locale);
    }
    
    /** @test */
    public function 可以設定頁面標題()
    {
        Livewire::test(AdminLayout::class)
            ->call('setPageTitle', '使用者管理')
            ->assertSet('pageTitle', '使用者管理');
    }
    
    /** @test */
    public function 可以設定麵包屑導航()
    {
        $breadcrumbs = [
            ['label' => '首頁', 'url' => '/admin'],
            ['label' => '使用者管理', 'url' => '/admin/users'],
            ['label' => '使用者列表', 'url' => null]
        ];
        
        Livewire::test(AdminLayout::class)
            ->call('setBreadcrumbs', $breadcrumbs)
            ->assertSet('breadcrumbs', $breadcrumbs);
    }
    
    /** @test */
    public function 可以新增頁面操作按鈕()
    {
        $action = [
            'label' => '新增使用者',
            'url' => '/admin/users/create',
            'icon' => 'plus',
            'class' => 'btn-primary'
        ];
        
        Livewire::test(AdminLayout::class)
            ->call('addPageAction', $action)
            ->assertSet('pageActions.0', $action);
    }
    
    /** @test */
    public function 可以處理視窗大小變更事件()
    {
        Livewire::test(AdminLayout::class)
            ->call('handleViewportChange', [
                'isMobile' => true,
                'isTablet' => false,
                'width' => 375
            ])
            ->assertSet('isMobile', true)
            ->assertSet('isTablet', false);
    }
    
    /** @test */
    public function 行動裝置模式下自動關閉側邊欄()
    {
        Livewire::test(AdminLayout::class)
            ->set('sidebarCollapsed', false)
            ->call('handleViewportChange', [
                'isMobile' => true,
                'isTablet' => false,
                'width' => 375
            ])
            ->assertSet('isMobile', true)
            ->assertSet('sidebarMobile', false);
    }
    
    /** @test */
    public function 可以監聽主題變更事件()
    {
        Livewire::test(AdminLayout::class)
            ->dispatch('theme-changed', theme: 'dark')
            ->assertSet('currentTheme', 'dark');
    }
    
    /** @test */
    public function 可以監聽語言變更事件()
    {
        Livewire::test(AdminLayout::class)
            ->dispatch('locale-changed', locale: 'en')
            ->assertSet('currentLocale', 'en');
    }
    
    /** @test */
    public function 側邊欄狀態會儲存到session()
    {
        Livewire::test(AdminLayout::class)
            ->call('toggleSidebar');
        
        $this->assertTrue(session('sidebar_collapsed'));
    }
    
    /** @test */
    public function 從session恢復側邊欄狀態()
    {
        session(['sidebar_collapsed' => true]);
        
        Livewire::test(AdminLayout::class)
            ->assertSet('sidebarCollapsed', true);
    }
    
    /** @test */
    public function 產生正確的佈局CSS類別()
    {
        $component = Livewire::test(AdminLayout::class);
        $layoutClasses = $component->instance()->getLayoutClasses();
        
        $this->assertIsArray($layoutClasses);
        $this->assertArrayHasKey('container', $layoutClasses);
        $this->assertArrayHasKey('sidebar', $layoutClasses);
        $this->assertArrayHasKey('main', $layoutClasses);
        $this->assertArrayHasKey('overlay', $layoutClasses);
    }
    
    /** @test */
    public function 桌面版佈局產生正確的CSS類別()
    {
        $component = Livewire::test(AdminLayout::class)
            ->set('isMobile', false)
            ->set('isTablet', false);
        
        $layoutClasses = $component->instance()->getLayoutClasses();
        
        $this->assertStringContainsString('layout-desktop', $layoutClasses['container']);
        $this->assertStringContainsString('ml-72', $layoutClasses['main']);
    }
    
    /** @test */
    public function 平板版佈局產生正確的CSS類別()
    {
        $component = Livewire::test(AdminLayout::class)
            ->set('isMobile', false)
            ->set('isTablet', true);
        
        $layoutClasses = $component->instance()->getLayoutClasses();
        
        $this->assertStringContainsString('layout-tablet', $layoutClasses['container']);
    }
    
    /** @test */
    public function 行動版佈局產生正確的CSS類別()
    {
        $component = Livewire::test(AdminLayout::class)
            ->set('isMobile', true)
            ->set('isTablet', false);
        
        $layoutClasses = $component->instance()->getLayoutClasses();
        
        $this->assertStringContainsString('layout-mobile', $layoutClasses['container']);
        $this->assertStringContainsString('ml-0', $layoutClasses['main']);
    }
    
    /** @test */
    public function 收合狀態下產生正確的CSS類別()
    {
        $component = Livewire::test(AdminLayout::class)
            ->set('sidebarCollapsed', true);
        
        $layoutClasses = $component->instance()->getLayoutClasses();
        
        $this->assertStringContainsString('sidebar-collapsed', $layoutClasses['container']);
        $this->assertStringContainsString('w-16', $layoutClasses['sidebar']);
        $this->assertStringContainsString('ml-16', $layoutClasses['main']);
    }
    
    /** @test */
    public function 行動版側邊欄開啟時顯示遮罩層()
    {
        $component = Livewire::test(AdminLayout::class)
            ->set('isMobile', true)
            ->set('sidebarMobile', true);
        
        $layoutClasses = $component->instance()->getLayoutClasses();
        
        $this->assertStringNotContainsString('hidden', $layoutClasses['overlay']);
    }
    
    /** @test */
    public function 非行動版或側邊欄關閉時隱藏遮罩層()
    {
        $component = Livewire::test(AdminLayout::class)
            ->set('isMobile', false)
            ->set('sidebarMobile', false);
        
        $layoutClasses = $component->instance()->getLayoutClasses();
        
        $this->assertStringContainsString('hidden', $layoutClasses['overlay']);
    }
    
    /** @test */
    public function 計算屬性正確回傳當前使用者()
    {
        $component = Livewire::test(AdminLayout::class);
        $currentUser = $component->instance()->getCurrentUserProperty();
        
        $this->assertInstanceOf(User::class, $currentUser);
        $this->assertEquals($this->user->id, $currentUser->id);
    }
    
    /** @test */
    public function 計算屬性正確回傳行動裝置狀態()
    {
        $component = Livewire::test(AdminLayout::class)
            ->set('isMobile', true);
        
        $isMobile = $component->instance()->getIsMobileProperty();
        
        $this->assertTrue($isMobile);
    }
}