<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Services\AccessibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Livewire\Admin\Layout\AccessibilitySettings;
use App\Livewire\Admin\Layout\FocusManager;
use App\Livewire\Admin\Layout\ScreenReaderSupport;
use App\Livewire\Admin\Layout\SkipLinks;

class AccessibilityTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected AccessibilityService $accessibilityService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
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
        
        $this->accessibilityService = app(AccessibilityService::class);
    }

    /** @test */
    public function accessibility_service_returns_default_preferences()
    {
        $preferences = $this->accessibilityService->getUserAccessibilityPreferences();
        
        $this->assertIsArray($preferences);
        $this->assertArrayHasKey('high_contrast', $preferences);
        $this->assertArrayHasKey('large_text', $preferences);
        $this->assertArrayHasKey('reduced_motion', $preferences);
        $this->assertArrayHasKey('keyboard_navigation', $preferences);
        $this->assertArrayHasKey('screen_reader_support', $preferences);
        $this->assertArrayHasKey('focus_indicators', $preferences);
        $this->assertArrayHasKey('skip_links', $preferences);
    }

    /** @test */
    public function accessibility_service_saves_user_preferences()
    {
        $this->actingAs($this->user);
        
        $newPreferences = [
            'high_contrast' => true,
            'large_text' => true,
            'reduced_motion' => true,
        ];
        
        $this->accessibilityService->saveUserAccessibilityPreferences($newPreferences);
        
        $this->user->refresh();
        $savedPreferences = $this->user->accessibility_preferences;
        
        $this->assertTrue($savedPreferences['high_contrast']);
        $this->assertTrue($savedPreferences['large_text']);
        $this->assertTrue($savedPreferences['reduced_motion']);
    }

    /** @test */
    public function accessibility_service_generates_correct_css_classes()
    {
        $this->actingAs($this->user);
        
        // 設定一些偏好
        $this->accessibilityService->saveUserAccessibilityPreferences([
            'high_contrast' => true,
            'large_text' => true,
            'focus_indicators' => true,
        ]);
        
        $classes = $this->accessibilityService->getAccessibilityClasses();
        
        $this->assertStringContainsString('high-contrast', $classes);
        $this->assertStringContainsString('large-text', $classes);
        $this->assertStringContainsString('enhanced-focus', $classes);
    }

    /** @test */
    public function accessibility_service_generates_aria_labels()
    {
        $label = $this->accessibilityService->generateAriaLabel('menu_toggle');
        $this->assertEquals('切換選單', $label);
        
        $labelWithData = $this->accessibilityService->generateAriaLabel('search', ['使用者']);
        $this->assertEquals('搜尋：使用者', $labelWithData);
    }

    /** @test */
    public function accessibility_service_returns_keyboard_shortcuts()
    {
        $shortcuts = $this->accessibilityService->getKeyboardShortcuts();
        
        $this->assertIsArray($shortcuts);
        $this->assertArrayHasKey('navigation', $shortcuts);
        $this->assertArrayHasKey('general', $shortcuts);
        $this->assertArrayHasKey('menu', $shortcuts);
        
        $this->assertArrayHasKey('Alt + M', $shortcuts['navigation']);
        $this->assertArrayHasKey('Tab', $shortcuts['general']);
        $this->assertArrayHasKey('↑ ↓', $shortcuts['menu']);
    }

    /** @test */
    public function accessibility_settings_component_can_be_rendered()
    {
        $this->actingAs($this->user);
        
        Livewire::test(AccessibilitySettings::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.admin.layout.accessibility-settings');
    }

    /** @test */
    public function accessibility_settings_component_can_toggle_visibility()
    {
        $this->actingAs($this->user);
        
        Livewire::test(AccessibilitySettings::class)
            ->assertSet('isOpen', false)
            ->call('toggle')
            ->assertSet('isOpen', true)
            ->call('toggle')
            ->assertSet('isOpen', false);
    }

    /** @test */
    public function accessibility_settings_component_can_update_preferences()
    {
        $this->actingAs($this->user);
        
        Livewire::test(AccessibilitySettings::class)
            ->call('updatePreference', 'high_contrast', true)
            ->assertDispatched('accessibility-preference-changed')
            ->assertDispatched('show-toast');
        
        $this->user->refresh();
        $this->assertTrue($this->user->accessibility_preferences['high_contrast']);
    }

    /** @test */
    public function accessibility_settings_component_can_reset_to_defaults()
    {
        $this->actingAs($this->user);
        
        // 先設定一些非預設值
        $this->user->update([
            'accessibility_preferences' => [
                'high_contrast' => true,
                'large_text' => true,
                'reduced_motion' => true,
            ]
        ]);
        
        Livewire::test(AccessibilitySettings::class)
            ->call('resetToDefaults')
            ->assertDispatched('accessibility-preferences-reset')
            ->assertDispatched('show-toast');
        
        $this->user->refresh();
        $preferences = $this->user->accessibility_preferences;
        
        $this->assertFalse($preferences['high_contrast']);
        $this->assertFalse($preferences['large_text']);
        $this->assertFalse($preferences['reduced_motion']);
        $this->assertTrue($preferences['keyboard_navigation']);
    }

    /** @test */
    public function focus_manager_component_can_be_rendered()
    {
        $this->actingAs($this->user);
        
        Livewire::test(FocusManager::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.admin.layout.focus-manager');
    }

    /** @test */
    public function focus_manager_component_can_set_focus()
    {
        $this->actingAs($this->user);
        
        Livewire::test(FocusManager::class)
            ->call('setFocus', 'test-element')
            ->assertSet('currentFocus', 'test-element')
            ->assertDispatched('set-focus');
    }

    /** @test */
    public function focus_manager_component_can_enable_focus_trap()
    {
        $this->actingAs($this->user);
        
        Livewire::test(FocusManager::class)
            ->call('enableFocusTrap', 'modal-container')
            ->assertSet('trapFocus', true)
            ->assertSet('trapContainer', 'modal-container')
            ->assertDispatched('enable-focus-trap');
    }

    /** @test */
    public function focus_manager_component_can_disable_focus_trap()
    {
        $this->actingAs($this->user);
        
        Livewire::test(FocusManager::class)
            ->call('enableFocusTrap', 'modal-container')
            ->call('disableFocusTrap')
            ->assertSet('trapFocus', false)
            ->assertSet('trapContainer', null)
            ->assertDispatched('disable-focus-trap');
    }

    /** @test */
    public function screen_reader_support_component_can_be_rendered()
    {
        $this->actingAs($this->user);
        
        Livewire::test(ScreenReaderSupport::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.admin.layout.screen-reader-support');
    }

    /** @test */
    public function screen_reader_support_component_can_announce_messages()
    {
        $this->actingAs($this->user);
        
        Livewire::test(ScreenReaderSupport::class)
            ->call('announce', '測試訊息', 'polite')
            ->assertDispatched('screen-reader-announce');
    }

    /** @test */
    public function screen_reader_support_component_can_announce_page_changes()
    {
        $this->actingAs($this->user);
        
        Livewire::test(ScreenReaderSupport::class)
            ->call('announcePageChange', '使用者管理', '首頁 > 使用者管理')
            ->assertDispatched('screen-reader-announce');
    }

    /** @test */
    public function screen_reader_support_component_can_announce_loading_states()
    {
        $this->actingAs($this->user);
        
        Livewire::test(ScreenReaderSupport::class)
            ->call('announceLoadingState', true, '使用者資料')
            ->assertDispatched('screen-reader-announce')
            ->call('announceLoadingState', false, '使用者資料')
            ->assertDispatched('screen-reader-announce');
    }

    /** @test */
    public function screen_reader_support_component_can_announce_form_errors()
    {
        $this->actingAs($this->user);
        
        $errors = ['姓名為必填欄位', '電子郵件格式不正確'];
        
        Livewire::test(ScreenReaderSupport::class)
            ->call('announceFormErrors', $errors)
            ->assertDispatched('screen-reader-announce');
    }

    /** @test */
    public function screen_reader_support_component_can_announce_operation_results()
    {
        $this->actingAs($this->user);
        
        Livewire::test(ScreenReaderSupport::class)
            ->call('announceOperationResult', true, '儲存使用者', '使用者已成功建立')
            ->assertDispatched('screen-reader-announce')
            ->call('announceOperationResult', false, '刪除使用者', '權限不足')
            ->assertDispatched('screen-reader-announce');
    }

    /** @test */
    public function skip_links_component_can_be_rendered()
    {
        $this->actingAs($this->user);
        
        Livewire::test(SkipLinks::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.admin.layout.skip-links');
    }

    /** @test */
    public function skip_links_component_can_show_and_hide()
    {
        $this->actingAs($this->user);
        
        Livewire::test(SkipLinks::class)
            ->assertSet('visible', false)
            ->call('show')
            ->assertSet('visible', true)
            ->call('hide')
            ->assertSet('visible', false);
    }

    /** @test */
    public function skip_links_component_can_skip_to_elements()
    {
        $this->actingAs($this->user);
        
        Livewire::test(SkipLinks::class)
            ->call('skipTo', 'main-content')
            ->assertDispatched('skip-to-element')
            ->assertSet('visible', false);
    }

    /** @test */
    public function skip_links_component_respects_user_preferences()
    {
        $this->actingAs($this->user);
        
        // 停用跳轉連結
        $this->accessibilityService->saveUserAccessibilityPreferences([
            'skip_links' => false
        ]);
        
        $component = Livewire::test(SkipLinks::class);
        $this->assertFalse($component->get('isEnabled'));
        
        // 啟用跳轉連結
        $this->accessibilityService->saveUserAccessibilityPreferences([
            'skip_links' => true
        ]);
        
        $component = Livewire::test(SkipLinks::class);
        $this->assertTrue($component->get('isEnabled'));
    }

    /** @test */
    public function accessibility_features_work_together()
    {
        $this->actingAs($this->user);
        
        // 測試多個無障礙元件的整合
        $accessibilitySettings = Livewire::test(AccessibilitySettings::class);
        $focusManager = Livewire::test(FocusManager::class);
        $screenReader = Livewire::test(ScreenReaderSupport::class);
        $skipLinks = Livewire::test(SkipLinks::class);
        
        // 開啟無障礙設定
        $accessibilitySettings->call('toggle');
        
        // 更新偏好設定
        $accessibilitySettings->call('updatePreference', 'keyboard_navigation', true);
        
        // 設定焦點
        $focusManager->call('setFocus', 'accessibility-settings');
        
        // 宣告變更
        $screenReader->call('announce', '無障礙設定已開啟');
        
        // 顯示跳轉連結
        $skipLinks->call('show');
        
        // 驗證所有元件都正常運作
        $accessibilitySettings->assertSet('isOpen', true);
        $focusManager->assertSet('currentFocus', 'accessibility-settings');
        $skipLinks->assertSet('visible', true);
    }

    /** @test */
    public function accessibility_preferences_persist_across_sessions()
    {
        $this->actingAs($this->user);
        
        // 設定偏好
        $preferences = [
            'high_contrast' => true,
            'large_text' => true,
            'keyboard_navigation' => true,
        ];
        
        $this->accessibilityService->saveUserAccessibilityPreferences($preferences);
        
        // 清除快取
        $this->accessibilityService->clearUserPreferencesCache();
        
        // 重新取得偏好設定
        $retrievedPreferences = $this->accessibilityService->getUserAccessibilityPreferences();
        
        $this->assertTrue($retrievedPreferences['high_contrast']);
        $this->assertTrue($retrievedPreferences['large_text']);
        $this->assertTrue($retrievedPreferences['keyboard_navigation']);
    }

    /** @test */
    public function accessibility_css_classes_are_applied_correctly()
    {
        $this->actingAs($this->user);
        
        // 設定高對比和大字體
        $this->accessibilityService->saveUserAccessibilityPreferences([
            'high_contrast' => true,
            'large_text' => true,
            'enhanced_focus' => true,
        ]);
        
        $classes = $this->accessibilityService->getAccessibilityClasses();
        
        $this->assertStringContainsString('high-contrast', $classes);
        $this->assertStringContainsString('large-text', $classes);
    }

    /** @test */
    public function keyboard_shortcuts_are_properly_defined()
    {
        $shortcuts = $this->accessibilityService->getKeyboardShortcuts();
        
        // 檢查導航快捷鍵
        $this->assertArrayHasKey('Alt + M', $shortcuts['navigation']);
        $this->assertArrayHasKey('Alt + S', $shortcuts['navigation']);
        $this->assertArrayHasKey('Alt + N', $shortcuts['navigation']);
        $this->assertArrayHasKey('Alt + U', $shortcuts['navigation']);
        
        // 檢查一般快捷鍵
        $this->assertArrayHasKey('Tab', $shortcuts['general']);
        $this->assertArrayHasKey('Shift + Tab', $shortcuts['general']);
        $this->assertArrayHasKey('Enter', $shortcuts['general']);
        $this->assertArrayHasKey('Escape', $shortcuts['general']);
        
        // 檢查選單快捷鍵
        $this->assertArrayHasKey('↑ ↓', $shortcuts['menu']);
        $this->assertArrayHasKey('→', $shortcuts['menu']);
        $this->assertArrayHasKey('←', $shortcuts['menu']);
    }
}