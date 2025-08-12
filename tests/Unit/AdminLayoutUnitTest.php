<?php

namespace Tests\Unit;

use App\Livewire\Admin\Layout\AdminLayout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AdminLayout 元件單元測試
 * 
 * 測試 AdminLayout 元件的核心邏輯和方法，包括：
 * - CSS 類別生成邏輯
 * - 佈局狀態管理
 * - 響應式設計適應
 * - 計算屬性功能
 */
class AdminLayoutUnitTest extends TestCase
{
    use RefreshDatabase;
    
    protected AdminLayout $component;
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
        
        // 建立元件實例
        $this->component = new AdminLayout();
        $this->component->mount();
    }
    
    /** @test */
    public function 可以正確初始化元件屬性()
    {
        $this->assertFalse($this->component->sidebarCollapsed);
        $this->assertFalse($this->component->sidebarMobile);
        $this->assertEquals('light', $this->component->currentTheme);
        $this->assertEquals('zh_TW', $this->component->currentLocale);
        $this->assertFalse($this->component->isMobile);
        $this->assertFalse($this->component->isTablet);
    }
    
    /** @test */
    public function 可以正確生成桌面版容器CSS類別()
    {
        $this->component->isMobile = false;
        $this->component->isTablet = false;
        $this->component->sidebarCollapsed = false;
        
        $classes = $this->component->getLayoutClasses();
        
        $this->assertStringContainsString('layout-desktop', $classes['container']);
        $this->assertStringNotContainsString('sidebar-collapsed', $classes['container']);
    }
    
    /** @test */
    public function 可以正確生成平板版容器CSS類別()
    {
        $this->component->isMobile = false;
        $this->component->isTablet = true;
        $this->component->sidebarCollapsed = false;
        
        $classes = $this->component->getLayoutClasses();
        
        $this->assertStringContainsString('layout-tablet', $classes['container']);
    }
    
    /** @test */
    public function 可以正確生成行動版容器CSS類別()
    {
        $this->component->isMobile = true;
        $this->component->isTablet = false;
        
        $classes = $this->component->getLayoutClasses();
        
        $this->assertStringContainsString('layout-mobile', $classes['container']);
    }
    
    /** @test */
    public function 收合狀態下可以正確生成CSS類別()
    {
        $this->component->sidebarCollapsed = true;
        
        $classes = $this->component->getLayoutClasses();
        
        $this->assertStringContainsString('sidebar-collapsed', $classes['container']);
    }
    
    /** @test */
    public function 可以正確生成桌面版側邊欄CSS類別()
    {
        $this->component->isMobile = false;
        $this->component->isTablet = false;
        $this->component->sidebarCollapsed = false;
        
        $classes = $this->component->getLayoutClasses();
        
        $this->assertStringContainsString('w-72', $classes['sidebar']);
        $this->assertStringContainsString('translate-x-0', $classes['sidebar']);
    }
    
    /** @test */
    public function 可以正確生成收合狀態的側邊欄CSS類別()
    {
        $this->component->isMobile = false;
        $this->component->isTablet = false;
        $this->component->sidebarCollapsed = true;
        
        $classes = $this->component->getLayoutClasses();
        
        $this->assertStringContainsString('w-16', $classes['sidebar']);
    }
    
    /** @test */
    public function 可以正確生成平板版側邊欄CSS類別()
    {
        $this->component->isMobile = false;
        $this->component->isTablet = true;
        $this->component->sidebarCollapsed = false;
        
        $classes = $this->component->getLayoutClasses();
        
        $this->assertStringContainsString('w-64', $classes['sidebar']);
    }
    
    /** @test */
    public function 可以正確生成行動版側邊欄CSS類別()
    {
        $this->component->isMobile = true;
        $this->component->sidebarMobile = false;
        
        $classes = $this->component->getLayoutClasses();
        
        $this->assertStringContainsString('w-80', $classes['sidebar']);
        $this->assertStringContainsString('-translate-x-full', $classes['sidebar']);
    }
    
    /** @test */
    public function 行動版側邊欄開啟時可以正確生成CSS類別()
    {
        $this->component->isMobile = true;
        $this->component->sidebarMobile = true;
        
        $classes = $this->component->getLayoutClasses();
        
        $this->assertStringContainsString('translate-x-0', $classes['sidebar']);
    }
    
    /** @test */
    public function 可以正確生成桌面版主內容CSS類別()
    {
        $this->component->isMobile = false;
        $this->component->isTablet = false;
        $this->component->sidebarCollapsed = false;
        
        $classes = $this->component->getLayoutClasses();
        
        $this->assertStringContainsString('ml-72', $classes['main']);
    }
    
    /** @test */
    public function 收合狀態下可以正確生成主內容CSS類別()
    {
        $this->component->isMobile = false;
        $this->component->isTablet = false;
        $this->component->sidebarCollapsed = true;
        
        $classes = $this->component->getLayoutClasses();
        
        $this->assertStringContainsString('ml-16', $classes['main']);
    }
    
    /** @test */
    public function 平板版可以正確生成主內容CSS類別()
    {
        $this->component->isMobile = false;
        $this->component->isTablet = true;
        $this->component->sidebarCollapsed = false;
        
        $classes = $this->component->getLayoutClasses();
        
        $this->assertStringContainsString('ml-64', $classes['main']);
    }
    
    /** @test */
    public function 行動版可以正確生成主內容CSS類別()
    {
        $this->component->isMobile = true;
        
        $classes = $this->component->getLayoutClasses();
        
        $this->assertStringContainsString('ml-0', $classes['main']);
    }
    
    /** @test */
    public function 行動版側邊欄開啟時顯示遮罩層()
    {
        $this->component->isMobile = true;
        $this->component->sidebarMobile = true;
        
        $classes = $this->component->getLayoutClasses();
        
        $this->assertStringNotContainsString('hidden', $classes['overlay']);
    }
    
    /** @test */
    public function 非行動版時隱藏遮罩層()
    {
        $this->component->isMobile = false;
        $this->component->sidebarMobile = false;
        
        $classes = $this->component->getLayoutClasses();
        
        $this->assertStringContainsString('hidden', $classes['overlay']);
    }
    
    /** @test */
    public function 行動版側邊欄關閉時隱藏遮罩層()
    {
        $this->component->isMobile = true;
        $this->component->sidebarMobile = false;
        
        $classes = $this->component->getLayoutClasses();
        
        $this->assertStringContainsString('hidden', $classes['overlay']);
    }
    
    /** @test */
    public function 計算屬性可以正確回傳當前使用者()
    {
        $currentUser = $this->component->getCurrentUserProperty();
        
        $this->assertInstanceOf(User::class, $currentUser);
        $this->assertEquals($this->user->id, $currentUser->id);
    }
    
    /** @test */
    public function 計算屬性可以正確回傳行動裝置狀態()
    {
        $this->component->isMobile = true;
        
        $isMobile = $this->component->getIsMobileProperty();
        
        $this->assertTrue($isMobile);
    }
    
    /** @test */
    public function 可以正確設定頁面標題()
    {
        $title = '使用者管理';
        
        $this->component->setPageTitle($title);
        
        $this->assertEquals($title, $this->component->pageTitle);
    }
    
    /** @test */
    public function 可以正確設定麵包屑導航()
    {
        $breadcrumbs = [
            ['label' => '首頁', 'url' => '/admin'],
            ['label' => '使用者管理', 'url' => '/admin/users'],
            ['label' => '使用者列表', 'url' => null]
        ];
        
        $this->component->setBreadcrumbs($breadcrumbs);
        
        $this->assertEquals($breadcrumbs, $this->component->breadcrumbs);
    }
    
    /** @test */
    public function 可以正確新增頁面操作按鈕()
    {
        $action = [
            'label' => '新增使用者',
            'url' => '/admin/users/create',
            'icon' => 'plus',
            'class' => 'btn-primary'
        ];
        
        $this->component->addPageAction($action);
        
        $this->assertContains($action, $this->component->pageActions);
    }
    
    /** @test */
    public function 可以正確處理視窗大小變更()
    {
        $viewport = [
            'isMobile' => true,
            'isTablet' => false,
            'width' => 375
        ];
        
        $this->component->handleViewportChange($viewport);
        
        $this->assertTrue($this->component->isMobile);
        $this->assertFalse($this->component->isTablet);
    }
    
    /** @test */
    public function 行動裝置模式下會自動關閉側邊欄()
    {
        $this->component->sidebarCollapsed = false;
        $this->component->sidebarMobile = true;
        
        $this->component->handleViewportChange([
            'isMobile' => true,
            'isTablet' => false,
            'width' => 375
        ]);
        
        $this->assertTrue($this->component->isMobile);
        $this->assertFalse($this->component->sidebarMobile);
    }
    
    /** @test */
    public function 可以正確處理主題變更事件()
    {
        $this->component->handleThemeChange('dark');
        
        $this->assertEquals('dark', $this->component->currentTheme);
    }
    
    /** @test */
    public function 可以正確處理語言變更事件()
    {
        $this->component->handleLocaleChange('en');
        
        $this->assertEquals('en', $this->component->currentLocale);
    }
    
    /** @test */
    public function 可以取得預設頁面標題()
    {
        $reflection = new \ReflectionClass($this->component);
        $method = $reflection->getMethod('getDefaultPageTitle');
        $method->setAccessible(true);
        
        $title = $method->invoke($this->component);
        
        $this->assertEquals(__('管理後台'), $title);
    }
    
    /** @test */
    public function 可以取得預設麵包屑導航()
    {
        $reflection = new \ReflectionClass($this->component);
        $method = $reflection->getMethod('getDefaultBreadcrumbs');
        $method->setAccessible(true);
        
        $breadcrumbs = $method->invoke($this->component);
        
        $this->assertIsArray($breadcrumbs);
        $this->assertCount(1, $breadcrumbs);
        $this->assertEquals(__('首頁'), $breadcrumbs[0]['label']);
    }
    
    /** @test */
    public function 可以正確儲存側邊欄狀態到session()
    {
        $this->component->toggleSidebar();
        
        $this->assertTrue(session('sidebar_collapsed'));
    }
    
    /** @test */
    public function 可以從session恢復側邊欄狀態()
    {
        session(['sidebar_collapsed' => true]);
        
        $component = new AdminLayout();
        $component->mount();
        
        $this->assertTrue($component->sidebarCollapsed);
    }
    
    /** @test */
    public function 主題設定會更新使用者偏好()
    {
        $this->component->setTheme('dark');
        
        $this->assertEquals('dark', $this->user->fresh()->theme_preference);
    }
    
    /** @test */
    public function 語言設定會更新使用者偏好()
    {
        $this->component->setLocale('en');
        
        $this->assertEquals('en', $this->user->fresh()->locale);
    }
}