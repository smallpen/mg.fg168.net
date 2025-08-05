<?php

namespace Tests\Feature\Livewire\Admin;

use App\Http\Livewire\Admin\LanguageSelector;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * LanguageSelector 元件測試
 * 
 * 測試語言選擇器的渲染、語言切換和偏好設定儲存
 */
class LanguageSelectorTest extends TestCase
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

        Livewire::test(LanguageSelector::class)
            ->assertStatus(200)
            ->assertSee('語言選擇')
            ->assertSee('正體中文')
            ->assertSee('English');
    }

    /**
     * 測試預設語言設定
     */
    public function test_default_language_setting()
    {
        $this->actingAs($this->admin);

        Livewire::test(LanguageSelector::class)
            ->assertSet('currentLocale', 'zh_TW'); // 預設為正體中文
    }

    /**
     * 測試語言切換功能
     */
    public function test_language_switching()
    {
        $this->actingAs($this->admin);

        Livewire::test(LanguageSelector::class)
            ->assertSet('currentLocale', 'zh_TW')
            ->call('changeLanguage', 'en')
            ->assertSet('currentLocale', 'en')
            ->assertDispatched('language-changed', ['locale' => 'en'])
            ->call('changeLanguage', 'zh_TW')
            ->assertSet('currentLocale', 'zh_TW')
            ->assertDispatched('language-changed', ['locale' => 'zh_TW']);
    }

    /**
     * 測試語言偏好設定儲存
     */
    public function test_language_preference_persistence()
    {
        $this->actingAs($this->admin);

        Livewire::test(LanguageSelector::class)
            ->call('changeLanguage', 'en')
            ->assertDispatched('language-saved');

        // 檢查使用者偏好設定是否被儲存
        $this->assertEquals('en', $this->admin->fresh()->locale);
    }

    /**
     * 測試從使用者偏好設定載入語言
     */
    public function test_load_language_from_user_preference()
    {
        $this->admin->update(['locale' => 'en']);
        $this->actingAs($this->admin);

        Livewire::test(LanguageSelector::class)
            ->assertSet('currentLocale', 'en');
    }

    /**
     * 測試語言選項顯示
     */
    public function test_language_options_display()
    {
        $this->actingAs($this->admin);

        Livewire::test(LanguageSelector::class)
            ->call('toggleDropdown')
            ->assertSet('dropdownOpen', true)
            ->assertSee('正體中文')
            ->assertSee('English')
            ->assertSee('zh_TW')
            ->assertSee('en');
    }

    /**
     * 測試當前語言高亮顯示
     */
    public function test_current_language_highlight()
    {
        $this->actingAs($this->admin);

        Livewire::test(LanguageSelector::class)
            ->assertSet('currentLocale', 'zh_TW')
            ->assertSee('selected') // 檢查是否有選中狀態的 CSS 類別
            ->call('changeLanguage', 'en')
            ->assertSee('selected'); // 切換後檢查新的選中狀態
    }

    /**
     * 測試語言切換後的介面更新
     */
    public function test_interface_update_after_language_change()
    {
        $this->actingAs($this->admin);

        Livewire::test(LanguageSelector::class)
            ->call('changeLanguage', 'en')
            ->assertDispatched('update-interface-language')
            ->assertDispatched('reload-translations');
    }

    /**
     * 測試語言驗證
     */
    public function test_language_validation()
    {
        $this->actingAs($this->admin);

        Livewire::test(LanguageSelector::class)
            ->call('changeLanguage', 'invalid_locale')
            ->assertHasErrors(['locale'])
            ->assertSee('不支援的語言');
    }

    /**
     * 測試語言切換確認對話框
     */
    public function test_language_change_confirmation()
    {
        $this->actingAs($this->admin);

        Livewire::test(LanguageSelector::class)
            ->set('requireConfirmation', true)
            ->call('changeLanguage', 'en')
            ->assertSee('確認切換語言')
            ->assertSee('這將會重新載入頁面')
            ->call('confirmLanguageChange')
            ->assertSet('currentLocale', 'en');
    }

    /**
     * 測試語言切換動畫效果
     */
    public function test_language_change_animation()
    {
        $this->actingAs($this->admin);

        Livewire::test(LanguageSelector::class)
            ->call('changeLanguage', 'en')
            ->assertSet('isChanging', true)
            ->assertDispatched('language-transition-start');
    }

    /**
     * 測試語言自動偵測
     */
    public function test_language_auto_detection()
    {
        $this->actingAs($this->admin);

        // 模擬瀏覽器語言偏好
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US,en;q=0.9';

        Livewire::test(LanguageSelector::class)
            ->call('detectBrowserLanguage')
            ->assertDispatched('browser-language-detected', ['locale' => 'en']);
    }

    /**
     * 測試語言切換歷史記錄
     */
    public function test_language_change_history()
    {
        $this->actingAs($this->admin);

        Livewire::test(LanguageSelector::class)
            ->call('changeLanguage', 'en')
            ->call('changeLanguage', 'zh_TW')
            ->call('showLanguageHistory')
            ->assertSee('語言切換歷史')
            ->assertSee('zh_TW → en')
            ->assertSee('en → zh_TW');
    }

    /**
     * 測試語言包載入狀態
     */
    public function test_language_pack_loading_state()
    {
        $this->actingAs($this->admin);

        Livewire::test(LanguageSelector::class)
            ->call('changeLanguage', 'en')
            ->assertSet('isLoadingLanguage', true)
            ->assertSee('載入語言包...');
    }

    /**
     * 測試語言切換錯誤處理
     */
    public function test_language_change_error_handling()
    {
        $this->actingAs($this->admin);

        // 模擬語言包載入失敗
        Livewire::test(LanguageSelector::class)
            ->call('changeLanguage', 'corrupted_locale')
            ->assertSee('語言切換失敗')
            ->assertDispatched('language-error');
    }

    /**
     * 測試語言選擇器的鍵盤導航
     */
    public function test_keyboard_navigation()
    {
        $this->actingAs($this->admin);

        Livewire::test(LanguageSelector::class)
            ->call('handleKeyDown', 'ArrowDown')
            ->assertSet('selectedIndex', 1)
            ->call('handleKeyDown', 'Enter')
            ->assertDispatched('language-selected');
    }

    /**
     * 測試語言選擇器的輔助功能
     */
    public function test_accessibility_features()
    {
        $this->actingAs($this->admin);

        Livewire::test(LanguageSelector::class)
            ->assertSee('aria-label="選擇語言"')
            ->assertSee('role="listbox"')
            ->call('toggleDropdown')
            ->assertSee('aria-expanded="true"');
    }

    /**
     * 測試語言切換通知
     */
    public function test_language_change_notification()
    {
        $this->actingAs($this->admin);

        Livewire::test(LanguageSelector::class)
            ->call('changeLanguage', 'en')
            ->assertDispatched('show-notification', [
                'type' => 'success',
                'message' => 'Language changed to English'
            ]);
    }

    /**
     * 測試語言選擇器的響應式設計
     */
    public function test_responsive_design()
    {
        $this->actingAs($this->admin);

        Livewire::test(LanguageSelector::class)
            ->set('isMobile', true)
            ->assertSee('語言') // 行動版顯示簡化文字
            ->set('isMobile', false)
            ->assertSee('語言選擇'); // 桌面版顯示完整文字
    }

    /**
     * 測試語言切換的 URL 更新
     */
    public function test_url_update_on_language_change()
    {
        $this->actingAs($this->admin);

        Livewire::test(LanguageSelector::class)
            ->call('changeLanguage', 'en')
            ->assertDispatched('update-url-locale', ['locale' => 'en']);
    }

    /**
     * 測試語言相關的 Cookie 設定
     */
    public function test_language_cookie_setting()
    {
        $this->actingAs($this->admin);

        Livewire::test(LanguageSelector::class)
            ->call('changeLanguage', 'en')
            ->assertDispatched('set-language-cookie', ['locale' => 'en']);
    }

    /**
     * 測試語言切換的效能優化
     */
    public function test_language_change_performance()
    {
        $this->actingAs($this->admin);

        Livewire::test(LanguageSelector::class)
            ->call('preloadLanguage', 'en')
            ->assertDispatched('language-preloaded')
            ->call('changeLanguage', 'en')
            ->assertDispatched('language-changed-fast');
    }

    /**
     * 測試語言選擇器的搜尋功能
     */
    public function test_language_search()
    {
        $this->actingAs($this->admin);

        Livewire::test(LanguageSelector::class)
            ->set('searchTerm', 'Eng')
            ->assertSee('English')
            ->assertDontSee('正體中文');
    }

    /**
     * 測試語言選擇器的批量操作
     */
    public function test_bulk_language_operations()
    {
        $this->actingAs($this->admin);

        Livewire::test(LanguageSelector::class)
            ->call('resetAllUserLanguages')
            ->assertDispatched('bulk-language-reset')
            ->assertSee('已重置所有使用者語言設定');
    }
}