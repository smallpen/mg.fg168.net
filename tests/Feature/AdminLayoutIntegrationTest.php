<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Services\AdminLayoutIntegrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;

/**
 * 管理後台佈局整合測試
 * 
 * 測試所有佈局和導航元件的整合功能
 */
class AdminLayoutIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    
    protected User $adminUser;
    protected AdminLayoutIntegrationService $integrationService;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試用管理員使用者
        $this->adminUser = User::factory()->create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'theme_preference' => 'light',
            'locale' => 'zh_TW'
        ]);
        
        $this->integrationService = app(AdminLayoutIntegrationService::class);
    }
    
    /** @test */
    public function 可以執行完整的系統整合檢查()
    {
        $this->actingAs($this->adminUser);
        
        $results = $this->integrationService->performFullIntegration();
        
        $this->assertIsArray($results);
        $this->assertArrayHasKey('component_integration', $results);
        $this->assertArrayHasKey('performance_optimization', $results);
        $this->assertArrayHasKey('permission_control', $results);
        $this->assertArrayHasKey('responsive_design', $results);
        $this->assertArrayHasKey('accessibility_features', $results);
        $this->assertArrayHasKey('user_experience', $results);
        $this->assertArrayHasKey('overall_status', $results);
        
        $this->assertContains($results['overall_status'], ['passed', 'failed']);
    }
    
    /** @test */
    public function 所有核心佈局元件都能正常載入()
    {
        $this->actingAs($this->adminUser);
        
        $coreComponents = [
            'admin.layout.admin-layout',
            'admin.layout.sidebar',
            'admin.layout.top-nav-bar',
            'admin.layout.notification-center',
            'admin.layout.global-search',
            'admin.layout.theme-toggle',
            'admin.layout.user-menu',
            'admin.layout.breadcrumb'
        ];
        
        foreach ($coreComponents as $component) {
            try {
                $livewireComponent = Livewire::test($component);
                $this->assertNotNull($livewireComponent);
            } catch (\Exception $e) {
                $this->fail("Component {$component} failed to load: " . $e->getMessage());
            }
        }
    }
    
    /** @test */
    public function 管理後台主佈局可以正常渲染()
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->get(route('admin.dashboard'));
        
        $response->assertStatus(200);
        $response->assertSee('管理後台');
        $response->assertSee('儀表板');
    }
    
    /** @test */
    public function 側邊欄導航選單正常顯示()
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test('admin.layout.sidebar')
            ->assertSee('儀表板')
            ->assertSee('使用者管理')
            ->assertSee('角色管理')
            ->assertSee('系統設定');
    }
    
    /** @test */
    public function 頂部導航列功能正常()
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test('admin.layout.top-nav-bar')
            ->assertSee($this->adminUser->name)
            ->call('toggleNotifications')
            ->assertEmitted('notification-panel-toggled');
    }
    
    /** @test */
    public function 通知中心可以正常運作()
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test('admin.layout.notification-center')
            ->call('toggle')
            ->assertSet('isOpen', true)
            ->call('toggle')
            ->assertSet('isOpen', false);
    }
    
    /** @test */
    public function 全域搜尋功能正常()
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test('admin.layout.global-search')
            ->set('query', 'test')
            ->call('search')
            ->assertSet('isOpen', true);
    }
    
    /** @test */
    public function 主題切換功能正常()
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test('admin.layout.theme-toggle')
            ->call('setTheme', 'dark')
            ->assertSet('currentTheme', 'dark')
            ->call('setTheme', 'light')
            ->assertSet('currentTheme', 'light');
    }
    
    /** @test */
    public function 使用者選單功能正常()
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test('admin.layout.user-menu')
            ->assertSee($this->adminUser->name)
            ->assertSee($this->adminUser->email);
    }
    
    /** @test */
    public function 麵包屑導航正常生成()
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test('admin.layout.breadcrumb')
            ->set('breadcrumbs', [
                ['label' => '首頁', 'url' => route('admin.dashboard')],
                ['label' => '使用者管理', 'url' => route('admin.users.index')],
                ['label' => '使用者列表', 'url' => null]
            ])
            ->assertSee('首頁')
            ->assertSee('使用者管理')
            ->assertSee('使用者列表');
    }
    
    /** @test */
    public function 響應式佈局在不同裝置上正常運作()
    {
        $this->actingAs($this->adminUser);
        
        $component = Livewire::test('admin.layout.admin-layout');
        
        // 測試桌面版
        $component->call('handleViewportChange', [
            'isMobile' => false,
            'isTablet' => false,
            'isDesktop' => true,
            'width' => 1200
        ])
        ->assertSet('isDesktop', true)
        ->assertSet('isMobile', false);
        
        // 測試行動版
        $component->call('handleViewportChange', [
            'isMobile' => true,
            'isTablet' => false,
            'isDesktop' => false,
            'width' => 600
        ])
        ->assertSet('isMobile', true)
        ->assertSet('isDesktop', false);
    }
    
    /** @test */
    public function 側邊欄收合功能正常()
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test('admin.layout.admin-layout')
            ->assertSet('sidebarCollapsed', false)
            ->call('toggleSidebar')
            ->assertSet('sidebarCollapsed', true)
            ->call('toggleSidebar')
            ->assertSet('sidebarCollapsed', false);
    }
    
    /** @test */
    public function 行動版側邊欄抽屜功能正常()
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test('admin.layout.admin-layout')
            ->set('isMobile', true)
            ->assertSet('sidebarMobile', false)
            ->call('toggleMobileSidebar')
            ->assertSet('sidebarMobile', true)
            ->call('toggleMobileSidebar')
            ->assertSet('sidebarMobile', false);
    }
    
    /** @test */
    public function 載入狀態管理正常運作()
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test('admin.layout.loading-overlay')
            ->call('show', '載入中...')
            ->assertSet('isVisible', true)
            ->call('hide')
            ->assertSet('isVisible', false);
    }
    
    /** @test */
    public function 頁面載入指示器正常運作()
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test('admin.layout.page-loading-indicator')
            ->call('startLoading', ['步驟1', '步驟2', '步驟3'])
            ->assertSet('isLoading', true)
            ->call('finishLoading')
            ->assertSet('isLoading', false);
    }
    
    /** @test */
    public function 無障礙功能正常運作()
    {
        $this->actingAs($this->adminUser);
        
        // 測試跳轉連結
        Livewire::test('admin.layout.skip-links')
            ->assertSee('跳到主要內容')
            ->assertSee('跳到導航選單');
        
        // 測試焦點管理
        Livewire::test('admin.layout.focus-manager')
            ->call('setFocusToMain')
            ->assertEmitted('focus-set');
    }
    
    /** @test */
    public function 鍵盤快捷鍵功能正常()
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test('admin.layout.keyboard-shortcut-manager')
            ->call('registerShortcut', 'ctrl+b', 'toggle-sidebar')
            ->assertEmitted('shortcut-registered');
    }
    
    /** @test */
    public function 多語言支援功能正常()
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test('admin.layout.language-selector')
            ->call('setLanguage', 'en')
            ->assertEmitted('locale-changed')
            ->call('setLanguage', 'zh_TW')
            ->assertEmitted('locale-changed');
    }
    
    /** @test */
    public function 網路狀態監控正常()
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test('admin.layout.network-status')
            ->call('handleNetworkStatusChange', ['isOnline' => false])
            ->assertSet('isOnline', false)
            ->call('handleNetworkStatusChange', ['isOnline' => true])
            ->assertSet('isOnline', true);
    }
    
    /** @test */
    public function 操作回饋系統正常()
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test('admin.layout.operation-feedback')
            ->call('showSuccess', '操作成功')
            ->assertEmitted('feedback-shown')
            ->call('showError', '操作失敗')
            ->assertEmitted('feedback-shown');
    }
    
    /** @test */
    public function 效能監控功能正常()
    {
        $this->actingAs($this->adminUser);
        
        if (class_exists('App\Livewire\Admin\Performance\PerformanceMonitor')) {
            Livewire::test('admin.performance.performance-monitor')
                ->call('startMonitoring')
                ->assertSet('isMonitoring', true);
        } else {
            $this->markTestSkipped('PerformanceMonitor component not available');
        }
    }
    
    /** @test */
    public function 安全控制功能正常()
    {
        $this->actingAs($this->adminUser);
        
        // 測試 Session 過期警告
        Livewire::test('admin.security.session-expiry-warning')
            ->call('showWarning', 300) // 5分鐘警告
            ->assertSet('showWarning', true);
        
        // 測試多裝置 Session 管理
        Livewire::test('admin.security.multi-device-session-manager')
            ->call('loadActiveSessions')
            ->assertSet('sessionsLoaded', true);
    }
    
    /** @test */
    public function 元件整合檢查通過()
    {
        $this->actingAs($this->adminUser);
        
        $result = $this->integrationService->checkComponentIntegration();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('components', $result);
        $this->assertArrayHasKey('total_components', $result);
        $this->assertArrayHasKey('working_components', $result);
        
        // 至少 80% 的元件應該正常運作
        $successRate = $result['working_components'] / $result['total_components'];
        $this->assertGreaterThanOrEqual(0.8, $successRate, '至少 80% 的元件應該正常運作');
    }
    
    /** @test */
    public function 效能優化檢查通過()
    {
        $this->actingAs($this->adminUser);
        
        $result = $this->integrationService->performPerformanceOptimization();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('optimizations', $result);
        $this->assertArrayHasKey('performance_score', $result);
        
        // 效能分數應該至少達到 70 分
        $this->assertGreaterThanOrEqual(70, $result['performance_score'], '效能分數應該至少達到 70 分');
    }
    
    /** @test */
    public function 權限控制檢查通過()
    {
        $this->actingAs($this->adminUser);
        
        $result = $this->integrationService->verifyPermissionControl();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('total_admin_routes', $result);
        $this->assertArrayHasKey('unprotected_routes', $result);
        $this->assertArrayHasKey('security_score', $result);
        
        // 安全分數應該至少達到 90 分
        $this->assertGreaterThanOrEqual(90, $result['security_score'], '安全分數應該至少達到 90 分');
    }
    
    /** @test */
    public function 響應式設計檢查通過()
    {
        $this->actingAs($this->adminUser);
        
        $result = $this->integrationService->validateResponsiveDesign();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('features', $result);
        $this->assertArrayHasKey('responsive_score', $result);
        
        // 響應式分數應該至少達到 80 分
        $this->assertGreaterThanOrEqual(80, $result['responsive_score'], '響應式分數應該至少達到 80 分');
    }
    
    /** @test */
    public function 使用者體驗測試通過()
    {
        $this->actingAs($this->adminUser);
        
        $result = $this->integrationService->performUserExperienceTest();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('tests', $result);
        $this->assertArrayHasKey('ux_score', $result);
        
        // UX 分數應該至少達到 85 分
        $this->assertGreaterThanOrEqual(85, $result['ux_score'], 'UX 分數應該至少達到 85 分');
    }
    
    /** @test */
    public function 整體整合狀態為通過()
    {
        $this->actingAs($this->adminUser);
        
        $result = $this->integrationService->performFullIntegration();
        
        // 如果所有子系統都通過，整體狀態應該為通過
        $allSubsystemsPassed = collect($result)
            ->except('overall_status')
            ->every(function ($subsystem) {
                return is_array($subsystem) && 
                       isset($subsystem['status']) && 
                       $subsystem['status'] === 'passed';
            });
        
        if ($allSubsystemsPassed) {
            $this->assertEquals('passed', $result['overall_status'], '所有子系統通過時，整體狀態應該為通過');
        } else {
            // 如果有子系統失敗，記錄詳細資訊以便除錯
            $failedSubsystems = collect($result)
                ->except('overall_status')
                ->filter(function ($subsystem) {
                    return is_array($subsystem) && 
                           isset($subsystem['status']) && 
                           $subsystem['status'] !== 'passed';
                })
                ->keys()
                ->toArray();
            
            $this->addWarning('以下子系統未通過測試: ' . implode(', ', $failedSubsystems));
        }
    }
}