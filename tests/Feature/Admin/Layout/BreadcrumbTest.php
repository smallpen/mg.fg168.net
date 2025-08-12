<?php

namespace Tests\Feature\Admin\Layout;

use App\Livewire\Admin\Layout\Breadcrumb;
use App\Models\User;
use App\Services\NavigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 麵包屑導航元件測試
 */
class BreadcrumbTest extends TestCase
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
     * 測試麵包屑元件可以正常渲染
     */
    public function test_breadcrumb_component_renders(): void
    {
        Livewire::test(Breadcrumb::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.admin.layout.breadcrumb');
    }

    /**
     * 測試麵包屑載入功能
     */
    public function test_breadcrumb_loads_correctly(): void
    {
        // 模擬導航服務
        $navigationService = $this->mock(NavigationService::class);
        $navigationService->shouldReceive('getCurrentBreadcrumbs')
            ->once()
            ->andReturn([
                ['title' => '管理後台', 'route' => 'admin.dashboard', 'active' => false],
                ['title' => '使用者管理', 'route' => null, 'active' => true],
            ]);

        $this->app->instance(NavigationService::class, $navigationService);

        $component = Livewire::test(Breadcrumb::class);
        
        $this->assertCount(2, $component->get('breadcrumbs'));
        $this->assertEquals('管理後台', $component->get('breadcrumbs')[0]['title']);
        $this->assertEquals('使用者管理', $component->get('breadcrumbs')[1]['title']);
    }

    /**
     * 測試麵包屑壓縮功能
     */
    public function test_breadcrumb_compression_works(): void
    {
        $longBreadcrumbs = [
            ['title' => '管理後台', 'route' => null, 'active' => false],
            ['title' => '使用者管理', 'route' => null, 'active' => false],
            ['title' => '角色管理', 'route' => null, 'active' => false],
            ['title' => '權限設定', 'route' => null, 'active' => false],
            ['title' => '編輯權限', 'route' => null, 'active' => true],
        ];

        $navigationService = $this->mock(NavigationService::class);
        $navigationService->shouldReceive('getCurrentBreadcrumbs')
            ->once()
            ->andReturn($longBreadcrumbs);

        $this->app->instance(NavigationService::class, $navigationService);

        $component = Livewire::test(Breadcrumb::class);
        
        // 應該啟用壓縮
        $this->assertTrue($component->get('compressed'));
        
        // 顯示的麵包屑應該被壓縮
        $displayBreadcrumbs = $component->get('displayBreadcrumbs');
        $this->assertCount(4, $displayBreadcrumbs); // 第一個 + 省略號 + 最後兩個
        $this->assertEquals('...', $displayBreadcrumbs[1]['title']);
        $this->assertTrue($displayBreadcrumbs[1]['ellipsis'] ?? false);
    }

    /**
     * 測試麵包屑導航功能
     */
    public function test_breadcrumb_navigation_works(): void
    {
        $navigationService = $this->mock(NavigationService::class);
        $navigationService->shouldReceive('getCurrentBreadcrumbs')
            ->once()
            ->andReturn([
                ['title' => '管理後台', 'route' => 'admin.dashboard', 'active' => false],
                ['title' => '使用者管理', 'route' => null, 'active' => true],
            ]);

        $this->app->instance(NavigationService::class, $navigationService);

        // 模擬路由存在
        $this->app['router']->get('/admin/dashboard', function () {
            return 'dashboard';
        })->name('admin.dashboard');

        Livewire::test(Breadcrumb::class)
            ->call('navigateTo', 'admin.dashboard')
            ->assertRedirect(route('admin.dashboard'));
    }

    /**
     * 測試無效路由導航
     */
    public function test_breadcrumb_navigation_with_invalid_route(): void
    {
        $navigationService = $this->mock(NavigationService::class);
        $navigationService->shouldReceive('getCurrentBreadcrumbs')
            ->once()
            ->andReturn([]);

        $this->app->instance(NavigationService::class, $navigationService);

        Livewire::test(Breadcrumb::class)
            ->call('navigateTo', 'invalid.route')
            ->assertDispatched('breadcrumb-error');
    }

    /**
     * 測試空路由導航
     */
    public function test_breadcrumb_navigation_with_empty_route(): void
    {
        $navigationService = $this->mock(NavigationService::class);
        $navigationService->shouldReceive('getCurrentBreadcrumbs')
            ->once()
            ->andReturn([]);

        $this->app->instance(NavigationService::class, $navigationService);

        Livewire::test(Breadcrumb::class)
            ->call('navigateTo', '')
            ->assertNotDispatched('breadcrumb-error');
    }

    /**
     * 測試麵包屑展開功能
     */
    public function test_breadcrumb_expand_functionality(): void
    {
        $longBreadcrumbs = array_fill(0, 6, ['title' => 'Test', 'route' => null, 'active' => false]);

        $navigationService = $this->mock(NavigationService::class);
        $navigationService->shouldReceive('getCurrentBreadcrumbs')
            ->once()
            ->andReturn($longBreadcrumbs);

        $this->app->instance(NavigationService::class, $navigationService);

        Livewire::test(Breadcrumb::class)
            ->assertSet('compressed', true)
            ->call('expandBreadcrumbs')
            ->assertSet('compressed', false);
    }

    /**
     * 測試麵包屑壓縮功能
     */
    public function test_breadcrumb_compress_functionality(): void
    {
        $longBreadcrumbs = array_fill(0, 6, ['title' => 'Test', 'route' => null, 'active' => false]);

        $navigationService = $this->mock(NavigationService::class);
        $navigationService->shouldReceive('getCurrentBreadcrumbs')
            ->once()
            ->andReturn($longBreadcrumbs);

        $this->app->instance(NavigationService::class, $navigationService);

        Livewire::test(Breadcrumb::class)
            ->call('expandBreadcrumbs')
            ->assertSet('compressed', false)
            ->call('compressBreadcrumbs')
            ->assertSet('compressed', true);
    }

    /**
     * 測試麵包屑重新整理功能
     */
    public function test_breadcrumb_refresh_functionality(): void
    {
        $navigationService = $this->mock(NavigationService::class);
        $navigationService->shouldReceive('getCurrentBreadcrumbs')
            ->twice()
            ->andReturn([]);

        $this->app->instance(NavigationService::class, $navigationService);

        Livewire::test(Breadcrumb::class)
            ->call('refreshBreadcrumbs', 'admin.users.index');
    }

    /**
     * 測試事件監聽功能
     */
    public function test_breadcrumb_event_listeners(): void
    {
        $navigationService = $this->mock(NavigationService::class);
        $navigationService->shouldReceive('getCurrentBreadcrumbs')
            ->times(3) // mount + breadcrumb-refresh + route-changed
            ->andReturn([]);

        $this->app->instance(NavigationService::class, $navigationService);

        Livewire::test(Breadcrumb::class)
            ->dispatch('breadcrumb-refresh')
            ->dispatch('route-changed', routeName: 'admin.dashboard');
    }

    /**
     * 測試 JSON-LD 結構化資料生成
     */
    public function test_breadcrumb_json_ld_generation(): void
    {
        $breadcrumbs = [
            ['title' => '管理後台', 'route' => 'admin.dashboard', 'active' => false],
            ['title' => '使用者管理', 'route' => null, 'active' => true],
        ];

        $navigationService = $this->mock(NavigationService::class);
        $navigationService->shouldReceive('getCurrentBreadcrumbs')
            ->once()
            ->andReturn($breadcrumbs);

        $this->app->instance(NavigationService::class, $navigationService);

        // 模擬路由
        $this->app['router']->get('/admin/dashboard', function () {
            return 'dashboard';
        })->name('admin.dashboard');

        $component = Livewire::test(Breadcrumb::class);
        $jsonLd = $component->get('breadcrumbJsonLd');
        
        $this->assertJson($jsonLd);
        
        $data = json_decode($jsonLd, true);
        $this->assertEquals('https://schema.org', $data['@context']);
        $this->assertEquals('BreadcrumbList', $data['@type']);
        $this->assertIsArray($data['itemListElement']);
        $this->assertCount(2, $data['itemListElement']);
    }

    /**
     * 測試短麵包屑不會被壓縮
     */
    public function test_short_breadcrumbs_not_compressed(): void
    {
        $shortBreadcrumbs = [
            ['title' => '管理後台', 'route' => 'admin.dashboard', 'active' => false],
            ['title' => '使用者管理', 'route' => null, 'active' => true],
        ];

        $navigationService = $this->mock(NavigationService::class);
        $navigationService->shouldReceive('getCurrentBreadcrumbs')
            ->once()
            ->andReturn($shortBreadcrumbs);

        $this->app->instance(NavigationService::class, $navigationService);

        $component = Livewire::test(Breadcrumb::class);
        
        $this->assertFalse($component->get('compressed'));
        $this->assertEquals($shortBreadcrumbs, $component->get('displayBreadcrumbs'));
    }
}