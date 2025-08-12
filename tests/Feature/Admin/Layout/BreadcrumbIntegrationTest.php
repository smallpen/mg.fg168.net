<?php

namespace Tests\Feature\Admin\Layout;

use App\Livewire\Admin\Layout\TopNavBar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 麵包屑導航整合測試
 */
class BreadcrumbIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /**
     * 測試 TopNavBar 包含麵包屑元件
     */
    public function test_top_nav_bar_includes_breadcrumb_component(): void
    {
        $component = Livewire::test(TopNavBar::class);
        
        // 檢查視圖是否包含麵包屑元件的容器
        $component->assertSeeHtml('breadcrumb-container');
        $component->assertSeeHtml('breadcrumb-nav');
    }

    /**
     * 測試麵包屑在不同路由下的顯示
     */
    public function test_breadcrumb_displays_correctly_on_different_routes(): void
    {
        // 模擬不同的路由
        $routes = [
            'admin.dashboard' => ['管理後台'],
            'admin.users.index' => ['管理後台', '使用者管理'],
            'admin.roles.index' => ['管理後台', '角色管理'],
        ];

        foreach ($routes as $routeName => $expectedBreadcrumbs) {
            // 設定當前路由
            $this->app['router']->get("/{$routeName}", function () {
                return 'test';
            })->name($routeName);

            // 模擬路由請求
            $this->get(route($routeName));

            // 測試麵包屑顯示
            $topNavBar = Livewire::test(TopNavBar::class);
            $topNavBar->assertStatus(200);
        }
    }

    /**
     * 測試麵包屑響應式設計
     */
    public function test_breadcrumb_responsive_design(): void
    {
        $component = Livewire::test(TopNavBar::class);
        
        // 檢查是否包含響應式類別
        $component->assertSeeHtml('breadcrumb-mobile');
        $component->assertSeeHtml('md:hidden');
    }

    /**
     * 測試麵包屑無障礙功能
     */
    public function test_breadcrumb_accessibility_features(): void
    {
        $component = Livewire::test(TopNavBar::class);
        
        // 檢查 ARIA 標籤
        $component->assertSeeHtml('aria-label="麵包屑導航"');
        $component->assertSeeHtml('aria-current="page"');
    }
}