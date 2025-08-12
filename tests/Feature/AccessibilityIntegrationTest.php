<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\AccessibilityService;
use App\Services\KeyboardShortcutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 無障礙功能整合測試
 * 
 * 測試無障礙功能與其他系統元件的整合
 */
class AccessibilityIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected AccessibilityService $accessibilityService;
    protected KeyboardShortcutService $shortcutService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $this->admin = User::factory()->create([
            'username' => 'admin',
            'accessibility_preferences' => [
                'high_contrast' => false,
                'large_text' => false,
                'reduced_motion' => false,
                'keyboard_navigation' => true,
                'screen_reader_support' => true,
                'focus_indicators' => true,
                'skip_links' => true,
            ]
        ]);
        $this->admin->roles()->attach($adminRole);
        
        $this->accessibilityService = app(AccessibilityService::class);
        $this->shortcutService = app(KeyboardShortcutService::class);
        
        $this->actingAs($this->admin);
    }

    /** @test */
    public function accessibility_preferences_integrate_with_theme_system()
    {
        // 啟用高對比模式
        $this->accessibilityService->saveUserAccessibilityPreferences([
            'high_contrast' => true,
            'large_text' => true,
        ]);

        // 測試主題切換元件
        $component = Livewire::test('admin.layout.theme-toggle');
        
        // 檢查是否包含無障礙相關的 CSS 類別
        $classes = $this->accessibilityService->getAccessibilityClasses();
        $this->assertStringContainsString('high-contrast', $classes);
        $this->assertStringContainsString('large-text', $classes);

        // 切換到暗黑主題時，高對比設定應該保持
        $component->call('setTheme', 'dark');
        
        $updatedClasses = $this->accessibilityService->getAccessibilityClasses();
        $this->assertStringContainsString('high-contrast', $updatedClasses);
        // Note: Theme classes are managed separately from accessibility classes
    }

    /** @test */
    public function keyboard_shortcuts_work_with_accessibility_features()
    {
        // 測試無障礙相關的鍵盤快捷鍵
        $shortcuts = $this->shortcutService->getUserShortcuts($this->admin->id);
        
        // 檢查無障礙快捷鍵是否存在
        $this->assertTrue($shortcuts->has('alt+a')); // 開啟無障礙設定
        $this->assertTrue($shortcuts->has('alt+s')); // 跳到主要內容
        $this->assertTrue($shortcuts->has('alt+m')); // 跳到選單
        $this->assertTrue($shortcuts->has('alt+h')); // 說明

        // 測試快捷鍵與螢幕閱讀器的整合
        $component = Livewire::test('admin.layout.screen-reader-support');
        
        // 模擬使用快捷鍵開啟無障礙設定
        $component->call('announceShortcutActivation', 'alt+a', '無障礙設定');
        $component->assertDispatched('screen-reader-announce');
    }

    /** @test */
    public function focus_management_integrates_with_navigation()
    {
        $focusManager = Livewire::test('admin.layout.focus-manager');
        $sidebar = Livewire::test('admin.layout.sidebar');
        
        // 測試側邊欄導航時的焦點管理
        $sidebar->call('setActiveMenu', 'users');
        
        // 焦點應該設定到啟用的選單項目
        $focusManager->assertDispatched('set-focus');
        
        // 測試選單展開時的焦點陷阱
        $sidebar->call('toggleMenu', 'users');
        $focusManager->call('enableFocusTrap', 'submenu-users');
        
        $focusManager->assertSet('trapFocus', true);
        $focusManager->assertSet('trapContainer', 'submenu-users');
    }

    /** @test */
    public function screen_reader_announces_navigation_changes()
    {
        $screenReader = Livewire::test('admin.layout.screen-reader-support');
        $breadcrumb = Livewire::test('admin.layout.breadcrumb');
        
        // 模擬頁面導航
        $breadcrumb->call('updateBreadcrumbs', [
            ['title' => '首頁', 'url' => '/admin/dashboard'],
            ['title' => '使用者管理', 'url' => '/admin/users'],
            ['title' => '使用者列表', 'url' => null]
        ]);
        
        // 螢幕閱讀器應該宣告頁面變更
        $screenReader->call('announcePageChange', '使用者列表', '首頁 > 使用者管理 > 使用者列表');
        $screenReader->assertDispatched('screen-reader-announce');
    }

    /** @test */
    public function accessibility_settings_persist_across_components()
    {
        // 在無障礙設定元件中更新偏好
        $accessibilitySettings = Livewire::test('admin.layout.accessibility-settings');
        $accessibilitySettings->call('updatePreference', 'reduced_motion', true);
        
        // 檢查其他元件是否反映這個變更
        $adminLayout = Livewire::test('admin.layout.admin-layout');
        
        // 管理佈局應該包含減少動畫的類別
        $classes = $this->accessibilityService->getAccessibilityClasses();
        $this->assertStringContainsString('reduced-motion', $classes);
        
        // 主題切換元件也應該尊重這個設定
        $themeToggle = Livewire::test('admin.layout.theme-toggle');
        $themeToggle->call('setTheme', 'dark');
        
        // 動畫應該被禁用或減少
        $this->assertTrue($this->admin->fresh()->accessibility_preferences['reduced_motion']);
    }

    /** @test */
    public function skip_links_integrate_with_page_structure()
    {
        $skipLinks = Livewire::test('admin.layout.skip-links');
        $adminLayout = Livewire::test('admin.layout.admin-layout');
        
        // 測試跳轉連結的目標是否存在
        $skipLinks->call('skipTo', 'main-content');
        $skipLinks->assertDispatched('skip-to-element');
        
        // 測試跳轉到側邊欄
        $skipLinks->call('skipTo', 'sidebar-navigation');
        $skipLinks->assertDispatched('skip-to-element');
        
        // 測試跳轉到搜尋
        $skipLinks->call('skipTo', 'global-search');
        $skipLinks->assertDispatched('skip-to-element');
    }

    /** @test */
    public function high_contrast_mode_affects_all_components()
    {
        // 啟用高對比模式
        $this->accessibilityService->saveUserAccessibilityPreferences([
            'high_contrast' => true
        ]);
        
        // 測試各個元件是否都應用高對比樣式
        $components = [
            'admin.layout.admin-layout',
            'admin.layout.sidebar',
            'admin.layout.top-nav-bar',
            'admin.layout.notification-center',
            'admin.layout.global-search',
            'admin.layout.theme-toggle'
        ];
        
        foreach ($components as $componentName) {
            $component = Livewire::test($componentName);
            
            // 每個元件都應該能夠取得高對比樣式
            $classes = $this->accessibilityService->getAccessibilityClasses();
            $this->assertStringContainsString('high-contrast', $classes);
        }
    }

    /** @test */
    public function keyboard_navigation_works_across_all_components()
    {
        $focusManager = Livewire::test('admin.layout.focus-manager');
        
        // 測試鍵盤導航序列
        $navigationSequence = [
            'skip-links',
            'sidebar-navigation',
            'main-content',
            'global-search',
            'notification-center',
            'user-menu'
        ];
        
        foreach ($navigationSequence as $target) {
            $focusManager->call('setFocus', $target);
            $focusManager->assertSet('currentFocus', $target);
            $focusManager->assertDispatched('set-focus');
        }
    }

    /** @test */
    public function screen_reader_announces_form_interactions()
    {
        $screenReader = Livewire::test('admin.layout.screen-reader-support');
        
        // 模擬表單驗證錯誤
        $errors = [
            '使用者名稱為必填欄位',
            '電子郵件格式不正確',
            '密碼長度至少需要 8 個字元'
        ];
        
        $screenReader->call('announceFormErrors', $errors);
        $screenReader->assertDispatched('screen-reader-announce');
        
        // 模擬表單成功提交
        $screenReader->call('announceOperationResult', true, '建立使用者', '使用者已成功建立');
        $screenReader->assertDispatched('screen-reader-announce');
        
        // 模擬載入狀態
        $screenReader->call('announceLoadingState', true, '儲存使用者資料');
        $screenReader->assertDispatched('screen-reader-announce');
        
        $screenReader->call('announceLoadingState', false, '儲存使用者資料');
        $screenReader->assertDispatched('screen-reader-announce');
    }

    /** @test */
    public function accessibility_features_work_with_responsive_design()
    {
        // 測試在不同螢幕尺寸下的無障礙功能
        $accessibilitySettings = Livewire::test('admin.layout.accessibility-settings');
        $skipLinks = Livewire::test('admin.layout.skip-links');
        
        // 在手機版模式下
        $accessibilitySettings->set('isMobile', true);
        $skipLinks->set('isMobile', true);
        
        // 跳轉連結應該適應手機版佈局
        $skipLinks->call('skipTo', 'mobile-menu');
        $skipLinks->assertDispatched('skip-to-element');
        
        // 無障礙設定應該在手機版也能正常運作
        $accessibilitySettings->call('updatePreference', 'large_text', true);
        $accessibilitySettings->assertDispatched('accessibility-preference-changed');
    }

    /** @test */
    public function accessibility_integrates_with_notification_system()
    {
        $screenReader = Livewire::test('admin.layout.screen-reader-support');
        $notificationCenter = Livewire::test('admin.layout.notification-center');
        
        // 模擬新通知到達
        $notification = [
            'id' => 1,
            'title' => '系統更新',
            'message' => '系統已成功更新到版本 2.1.0',
            'type' => 'info',
            'priority' => 'normal'
        ];
        
        $notificationCenter->call('addNotification', $notification);
        
        // 螢幕閱讀器應該宣告新通知
        $screenReader->call('announceNotification', $notification);
        $screenReader->assertDispatched('screen-reader-announce');
        
        // 高優先級通知應該立即宣告
        $urgentNotification = array_merge($notification, [
            'priority' => 'urgent',
            'title' => '安全警報',
            'message' => '檢測到異常登入嘗試'
        ]);
        
        $screenReader->call('announceNotification', $urgentNotification);
        $screenReader->assertDispatched('screen-reader-announce');
    }

    /** @test */
    public function accessibility_preferences_affect_search_interface()
    {
        // 啟用大字體模式
        $this->accessibilityService->saveUserAccessibilityPreferences([
            'large_text' => true,
            'enhanced_focus' => true
        ]);
        
        $globalSearch = Livewire::test('admin.layout.global-search');
        $screenReader = Livewire::test('admin.layout.screen-reader-support');
        
        // 開啟搜尋
        $globalSearch->call('open');
        
        // 螢幕閱讀器應該宣告搜尋開啟
        $screenReader->call('announce', '全域搜尋已開啟，請輸入搜尋關鍵字');
        $screenReader->assertDispatched('screen-reader-announce');
        
        // 執行搜尋
        $globalSearch->set('query', 'user');
        $globalSearch->call('search');
        
        // 宣告搜尋結果
        $screenReader->call('announceSearchResults', 5, 'user');
        $screenReader->assertDispatched('screen-reader-announce');
    }

    /** @test */
    public function focus_trap_works_in_modal_dialogs()
    {
        $focusManager = Livewire::test('admin.layout.focus-manager');
        
        // 開啟模態對話框
        $focusManager->call('enableFocusTrap', 'confirmation-modal');
        $focusManager->assertSet('trapFocus', true);
        $focusManager->assertSet('trapContainer', 'confirmation-modal');
        
        // 測試焦點在模態內循環
        $focusManager->call('setFocus', 'modal-close-button');
        $focusManager->assertSet('currentFocus', 'modal-close-button');
        
        // 關閉模態對話框
        $focusManager->call('disableFocusTrap');
        $focusManager->assertSet('trapFocus', false);
        $focusManager->assertSet('trapContainer', null);
    }

    /** @test */
    public function accessibility_works_with_dynamic_content()
    {
        $screenReader = Livewire::test('admin.layout.screen-reader-support');
        
        // 測試動態載入內容的宣告
        $screenReader->call('announceContentChange', '使用者列表已更新，新增 3 位使用者');
        $screenReader->assertDispatched('screen-reader-announce');
        
        // 測試即時資料更新的宣告
        $screenReader->call('announceLiveUpdate', '統計資料', '使用者總數已更新為 150 人');
        $screenReader->assertDispatched('screen-reader-announce');
        
        // 測試錯誤狀態的宣告
        $screenReader->call('announceError', '載入失敗', '無法載入使用者資料，請稍後再試');
        $screenReader->assertDispatched('screen-reader-announce');
    }

    /** @test */
    public function accessibility_settings_export_and_import()
    {
        // 設定一些無障礙偏好
        $preferences = [
            'high_contrast' => true,
            'large_text' => true,
            'reduced_motion' => true,
            'keyboard_navigation' => true,
            'screen_reader_support' => true,
            'focus_indicators' => true,
            'skip_links' => true,
        ];
        
        $this->accessibilityService->saveUserAccessibilityPreferences($preferences);
        
        // 匯出設定
        $exportedSettings = $this->accessibilityService->exportUserSettings($this->admin->id);
        
        $this->assertArrayHasKey('accessibility_preferences', $exportedSettings);
        $this->assertEquals($preferences, $exportedSettings['accessibility_preferences']);
        
        // 清除設定
        $this->accessibilityService->resetToDefaults($this->admin->id);
        
        // 匯入設定
        $result = $this->accessibilityService->importUserSettings($exportedSettings, $this->admin->id);
        
        $this->assertTrue($result);
        
        // 驗證設定已恢復
        $restoredPreferences = $this->accessibilityService->getUserAccessibilityPreferences();
        $this->assertEquals($preferences, $restoredPreferences);
    }
}