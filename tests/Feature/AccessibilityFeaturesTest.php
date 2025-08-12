<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Services\AccessibilityService;
use Livewire\Livewire;
use App\Livewire\Admin\Layout\AccessibilitySettings;
use App\Livewire\Admin\Layout\SkipLinks;
use App\Livewire\Admin\Layout\FocusManager;
use App\Livewire\Admin\Layout\ScreenReaderSupport;

/**
 * 無障礙功能測試
 * 
 * 測試管理後台的無障礙功能實作，包括：
 * - 鍵盤導航支援
 * - ARIA 標籤和語義化標記
 * - 螢幕閱讀器支援
 * - 高對比模式
 * - 焦點管理和跳轉連結
 */
class AccessibilityFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected AccessibilityService $accessibilityService;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立測試用的管理員使用者
        $adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理員',
            'description' => '系統管理員'
        ]);

        $this->adminUser = User::create([
            'username' => 'admin',
            'name' => '測試管理員',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->adminUser->roles()->attach($adminRole->id);
        
        $this->accessibilityService = app(AccessibilityService::class);
    }

    /** @test */
    public function 可以取得預設的無障礙偏好設定()
    {
        $this->actingAs($this->adminUser);
        
        $preferences = $this->accessibilityService->getUserAccessibilityPreferences();
        
        $this->assertIsArray($preferences);
        $this->assertArrayHasKey('high_contrast', $preferences);
        $this->assertArrayHasKey('large_text', $preferences);
        $this->assertArrayHasKey('reduced_motion', $preferences);
        $this->assertArrayHasKey('keyboard_navigation', $preferences);
        $this->assertArrayHasKey('screen_reader_support', $preferences);
        $this->assertArrayHasKey('focus_indicators', $preferences);
        $this->assertArrayHasKey('skip_links', $preferences);
        
        // 檢查預設值
        $this->assertFalse($preferences['high_contrast']);
        $this->assertFalse($preferences['large_text']);
        $this->assertFalse($preferences['reduced_motion']);
        $this->assertTrue($preferences['keyboard_navigation']);
        $this->assertTrue($preferences['screen_reader_support']);
        $this->assertTrue($preferences['focus_indicators']);
        $this->assertTrue($preferences['skip_links']);
    }

    /** @test */
    public function 可以儲存使用者的無障礙偏好設定()
    {
        $this->actingAs($this->adminUser);
        
        $newPreferences = [
            'high_contrast' => true,
            'large_text' => true,
            'reduced_motion' => true,
        ];
        
        $this->accessibilityService->saveUserAccessibilityPreferences($newPreferences);
        
        // 重新載入使用者
        $this->adminUser->refresh();
        
        $savedPreferences = $this->adminUser->accessibility_preferences;
        $this->assertIsArray($savedPreferences);
        $this->assertTrue($savedPreferences['high_contrast']);
        $this->assertTrue($savedPreferences['large_text']);
        $this->assertTrue($savedPreferences['reduced_motion']);
    }

    /** @test */
    public function 可以產生正確的ARIA標籤()
    {
        $ariaLabel = $this->accessibilityService->generateAriaLabel('menu_toggle');
        $this->assertEquals('切換選單', $ariaLabel);
        
        $ariaLabelWithData = $this->accessibilityService->generateAriaLabel('search', ['使用者', '角色']);
        $this->assertEquals('搜尋：使用者，角色', $ariaLabelWithData);
    }

    /** @test */
    public function 可以取得鍵盤快捷鍵說明()
    {
        $shortcuts = $this->accessibilityService->getKeyboardShortcuts();
        
        $this->assertIsArray($shortcuts);
        $this->assertArrayHasKey('navigation', $shortcuts);
        $this->assertArrayHasKey('general', $shortcuts);
        $this->assertArrayHasKey('menu', $shortcuts);
        
        // 檢查導航快捷鍵
        $navigationShortcuts = $shortcuts['navigation'];
        $this->assertArrayHasKey('Alt + M', $navigationShortcuts);
        $this->assertArrayHasKey('Alt + S', $navigationShortcuts);
        $this->assertEquals('開啟/關閉選單', $navigationShortcuts['Alt + M']);
        $this->assertEquals('聚焦搜尋框', $navigationShortcuts['Alt + S']);
    }

    /** @test */
    public function 可以檢查無障礙功能狀態()
    {
        $this->actingAs($this->adminUser);
        
        // 預設狀態
        $this->assertFalse($this->accessibilityService->isHighContrastEnabled());
        $this->assertFalse($this->accessibilityService->isLargeTextEnabled());
        $this->assertFalse($this->accessibilityService->isReducedMotionEnabled());
        
        // 啟用高對比模式
        $this->accessibilityService->saveUserAccessibilityPreferences(['high_contrast' => true]);
        $this->assertTrue($this->accessibilityService->isHighContrastEnabled());
    }

    /** @test */
    public function 可以產生無障礙CSS類別()
    {
        $this->actingAs($this->adminUser);
        
        // 預設狀態
        $classes = $this->accessibilityService->getAccessibilityClasses();
        $this->assertStringContainsString('enhanced-focus', $classes);
        
        // 啟用高對比和大字體
        $this->accessibilityService->saveUserAccessibilityPreferences([
            'high_contrast' => true,
            'large_text' => true,
            'reduced_motion' => true,
        ]);
        
        $classes = $this->accessibilityService->getAccessibilityClasses();
        $this->assertStringContainsString('high-contrast', $classes);
        $this->assertStringContainsString('large-text', $classes);
        $this->assertStringContainsString('reduced-motion', $classes);
    }

    /** @test */
    public function 無障礙設定元件可以正常運作()
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(AccessibilitySettings::class)
            ->assertSet('isOpen', false)
            ->call('toggle')
            ->assertSet('isOpen', true)
            ->call('close')
            ->assertSet('isOpen', false);
    }

    /** @test */
    public function 可以更新無障礙偏好設定()
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(AccessibilitySettings::class)
            ->call('updatePreference', 'high_contrast', true)
            ->assertDispatched('accessibility-preference-changed')
            ->assertDispatched('show-toast');
        
        // 檢查資料庫是否已更新
        $this->adminUser->refresh();
        $preferences = $this->adminUser->accessibility_preferences;
        $this->assertTrue($preferences['high_contrast']);
    }

    /** @test */
    public function 可以重設無障礙設定為預設值()
    {
        $this->actingAs($this->adminUser);
        
        // 先設定一些非預設值
        $this->adminUser->update([
            'accessibility_preferences' => [
                'high_contrast' => true,
                'large_text' => true,
            ]
        ]);
        
        Livewire::test(AccessibilitySettings::class)
            ->call('resetToDefaults')
            ->assertDispatched('accessibility-preferences-reset')
            ->assertDispatched('show-toast');
        
        // 檢查是否重設為預設值
        $this->adminUser->refresh();
        $preferences = $this->adminUser->accessibility_preferences;
        $this->assertFalse($preferences['high_contrast']);
        $this->assertFalse($preferences['large_text']);
        $this->assertTrue($preferences['keyboard_navigation']);
    }

    /** @test */
    public function 跳轉連結元件可以正常運作()
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(SkipLinks::class)
            ->assertSet('visible', false)
            ->call('show')
            ->assertSet('visible', true)
            ->call('hide')
            ->assertSet('visible', false);
    }

    /** @test */
    public function 跳轉連結包含正確的連結項目()
    {
        $this->actingAs($this->adminUser);
        
        $component = Livewire::test(SkipLinks::class);
        $links = $component->get('links');
        
        $this->assertIsArray($links);
        $this->assertCount(4, $links);
        
        // 檢查連結項目
        $linkIds = array_column($links, 'id');
        $this->assertContains('main-content', $linkIds);
        $this->assertContains('navigation', $linkIds);
        $this->assertContains('search', $linkIds);
        $this->assertContains('user-menu', $linkIds);
    }

    /** @test */
    public function 可以跳轉到指定元素()
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(SkipLinks::class)
            ->call('skipTo', 'main-content')
            ->assertDispatched('skip-to-element', ['targetId' => 'main-content'])
            ->assertSet('visible', false);
    }

    /** @test */
    public function 焦點管理元件可以設定焦點()
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(FocusManager::class)
            ->call('setFocus', 'test-element')
            ->assertSet('currentFocus', 'test-element')
            ->assertDispatched('set-focus', ['elementId' => 'test-element']);
    }

    /** @test */
    public function 焦點管理元件可以啟用焦點陷阱()
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(FocusManager::class)
            ->call('enableFocusTrap', 'modal-container')
            ->assertSet('trapFocus', true)
            ->assertSet('trapContainer', 'modal-container')
            ->assertDispatched('enable-focus-trap', ['containerId' => 'modal-container']);
    }

    /** @test */
    public function 焦點管理元件可以停用焦點陷阱()
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(FocusManager::class)
            ->call('enableFocusTrap', 'modal-container')
            ->call('disableFocusTrap')
            ->assertSet('trapFocus', false)
            ->assertSet('trapContainer', null)
            ->assertDispatched('disable-focus-trap');
    }

    /** @test */
    public function 螢幕閱讀器支援元件可以宣告訊息()
    {
        $this->actingAs($this->adminUser);
        
        $component = Livewire::test(ScreenReaderSupport::class)
            ->call('announce', '測試訊息', 'polite');
        
        $announcements = $component->get('announcements');
        $this->assertCount(1, $announcements);
        $this->assertEquals('測試訊息', $announcements[0]['message']);
        $this->assertEquals('polite', $announcements[0]['priority']);
    }

    /** @test */
    public function 螢幕閱讀器支援元件可以宣告頁面變更()
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(ScreenReaderSupport::class)
            ->call('announcePageChange', '使用者管理', '首頁 > 使用者管理')
            ->assertDispatched('screen-reader-announce');
    }

    /** @test */
    public function 螢幕閱讀器支援元件可以宣告載入狀態()
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(ScreenReaderSupport::class)
            ->call('announceLoadingState', true, '使用者資料')
            ->assertDispatched('screen-reader-announce')
            ->call('announceLoadingState', false, '使用者資料')
            ->assertDispatched('screen-reader-announce');
    }

    /** @test */
    public function 螢幕閱讀器支援元件可以宣告表單錯誤()
    {
        $this->actingAs($this->adminUser);
        
        $errors = ['姓名為必填欄位', '電子郵件格式不正確'];
        
        Livewire::test(ScreenReaderSupport::class)
            ->call('announceFormErrors', $errors)
            ->assertDispatched('screen-reader-announce');
    }

    /** @test */
    public function 螢幕閱讀器支援元件可以宣告操作結果()
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(ScreenReaderSupport::class)
            ->call('announceOperationResult', true, '儲存使用者', '使用者資料已成功儲存')
            ->assertDispatched('screen-reader-announce')
            ->call('announceOperationResult', false, '刪除使用者', '權限不足')
            ->assertDispatched('screen-reader-announce');
    }

    /** @test */
    public function 可以處理鍵盤導航事件()
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(FocusManager::class)
            ->call('handleKeyboardNavigation', [
                'key' => 'Tab',
                'shiftKey' => false,
                'ctrlKey' => false,
                'altKey' => false,
            ]);
        
        // 測試 Escape 鍵
        Livewire::test(FocusManager::class)
            ->call('enableFocusTrap', 'modal')
            ->call('handleKeyboardNavigation', [
                'key' => 'Escape',
                'shiftKey' => false,
                'ctrlKey' => false,
                'altKey' => false,
            ])
            ->assertDispatched('close-focused-element');
    }

    /** @test */
    public function 可以處理模態框事件()
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(FocusManager::class)
            ->dispatch('modal-opened', ['modalId' => 'test-modal'])
            ->assertSet('trapFocus', true)
            ->assertSet('trapContainer', 'test-modal')
            ->dispatch('modal-closed')
            ->assertSet('trapFocus', false)
            ->assertSet('trapContainer', null);
    }

    /** @test */
    public function 無障礙偏好設定會影響CSS類別生成()
    {
        $this->actingAs($this->adminUser);
        
        // 測試預設狀態
        $classes = $this->accessibilityService->getAccessibilityClasses();
        $this->assertStringNotContainsString('high-contrast', $classes);
        
        // 啟用高對比模式
        $this->accessibilityService->saveUserAccessibilityPreferences(['high_contrast' => true]);
        $classes = $this->accessibilityService->getAccessibilityClasses();
        $this->assertStringContainsString('high-contrast', $classes);
        
        // 啟用大字體模式
        $this->accessibilityService->saveUserAccessibilityPreferences([
            'high_contrast' => true,
            'large_text' => true,
        ]);
        $classes = $this->accessibilityService->getAccessibilityClasses();
        $this->assertStringContainsString('high-contrast', $classes);
        $this->assertStringContainsString('large-text', $classes);
    }

    /** @test */
    public function 可以清除使用者偏好快取()
    {
        $this->actingAs($this->adminUser);
        
        // 先取得偏好設定以建立快取
        $this->accessibilityService->getUserAccessibilityPreferences();
        
        // 清除快取
        $this->accessibilityService->clearUserPreferencesCache();
        
        // 這個測試主要確保方法可以正常執行而不會出錯
        $this->assertTrue(true);
    }
}