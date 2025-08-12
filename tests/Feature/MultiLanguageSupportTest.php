<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Services\LanguageService;
use App\Helpers\LocalizationHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

/**
 * 多語言支援系統測試
 */
class MultiLanguageSupportTest extends TestCase
{
    use RefreshDatabase;

    protected LanguageService $languageService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->languageService = app(LanguageService::class);
        $this->user = User::factory()->create([
            'locale' => 'zh_TW'
        ]);
    }

    /** @test */
    public function it_can_get_supported_locales()
    {
        $locales = $this->languageService->getSupportedLocales();
        
        $this->assertIsArray($locales);
        $this->assertArrayHasKey('zh_TW', $locales);
        $this->assertArrayHasKey('en', $locales);
        
        $this->assertEquals('正體中文', $locales['zh_TW']['name']);
        $this->assertEquals('English', $locales['en']['name']);
    }

    /** @test */
    public function it_can_check_if_locale_is_supported()
    {
        $this->assertTrue($this->languageService->isLocaleSupported('zh_TW'));
        $this->assertTrue($this->languageService->isLocaleSupported('en'));
        $this->assertFalse($this->languageService->isLocaleSupported('fr'));
    }

    /** @test */
    public function it_can_get_current_locale_info()
    {
        App::setLocale('zh_TW');
        
        $info = $this->languageService->getCurrentLocaleInfo();
        
        $this->assertEquals('正體中文', $info['name']);
        $this->assertEquals('🇹🇼', $info['flag']);
        $this->assertEquals('ltr', $info['direction']);
    }

    /** @test */
    public function it_can_switch_locale()
    {
        $result = $this->languageService->switchLocale('en');
        
        $this->assertTrue($result);
        $this->assertEquals('en', App::getLocale());
        $this->assertEquals('en', Session::get('locale'));
    }

    /** @test */
    public function it_cannot_switch_to_unsupported_locale()
    {
        $result = $this->languageService->switchLocale('fr');
        
        $this->assertFalse($result);
        $this->assertNotEquals('fr', App::getLocale());
    }

    /** @test */
    public function it_can_format_datetime_in_different_locales()
    {
        $date = Carbon::create(2023, 12, 25, 15, 30, 0);
        
        $zhTWFormat = $this->languageService->formatDateTime($date, 'zh_TW');
        $enFormat = $this->languageService->formatDateTime($date, 'en');
        
        $this->assertStringContainsString('年', $zhTWFormat);
        $this->assertStringContainsString('月', $zhTWFormat);
        $this->assertStringContainsString('日', $zhTWFormat);
        
        $this->assertStringContainsString('-', $enFormat);
        $this->assertStringNotContainsString('年', $enFormat);
    }

    /** @test */
    public function it_can_format_numbers_in_different_locales()
    {
        $number = 1234.56;
        
        $zhTWFormat = $this->languageService->formatNumber($number, 2, 'zh_TW');
        $enFormat = $this->languageService->formatNumber($number, 2, 'en');
        
        $this->assertEquals('1,234.56', $zhTWFormat);
        $this->assertEquals('1,234.56', $enFormat);
    }

    /** @test */
    public function it_can_format_currency_in_different_locales()
    {
        $amount = 1000.50;
        
        $zhTWFormat = $this->languageService->formatCurrency($amount, 'zh_TW');
        $enFormat = $this->languageService->formatCurrency($amount, 'en');
        
        $this->assertStringContainsString('NT$', $zhTWFormat);
        $this->assertStringContainsString('$', $enFormat);
        $this->assertStringContainsString('1,000.50', $zhTWFormat);
        $this->assertStringContainsString('1,000.50', $enFormat);
    }

    /** @test */
    public function it_can_get_relative_time_in_different_locales()
    {
        $date = Carbon::now()->subMinutes(5);
        
        App::setLocale('zh_TW');
        $zhTWRelative = $this->languageService->getRelativeTime($date, 'zh_TW');
        
        App::setLocale('en');
        $enRelative = $this->languageService->getRelativeTime($date, 'en');
        
        $this->assertStringContainsString('分鐘前', $zhTWRelative);
        $this->assertStringContainsString('minutes ago', $enRelative);
    }

    /** @test */
    public function it_can_detect_rtl_languages()
    {
        $this->assertFalse($this->languageService->isRtl('zh_TW'));
        $this->assertFalse($this->languageService->isRtl('en'));
        
        $this->assertEquals('ltr', $this->languageService->getDirection('zh_TW'));
        $this->assertEquals('ltr', $this->languageService->getDirection('en'));
    }

    /** @test */
    public function localization_helper_can_format_datetime()
    {
        $date = Carbon::create(2023, 12, 25, 15, 30, 0);
        
        $zhTWFormat = LocalizationHelper::formatDateTime($date, 'zh_TW');
        $enFormat = LocalizationHelper::formatDateTime($date, 'en');
        
        $this->assertStringContainsString('年', $zhTWFormat);
        $this->assertStringContainsString('-', $enFormat);
    }

    /** @test */
    public function localization_helper_can_format_relative_time()
    {
        $date = Carbon::now()->subHours(2);
        
        App::setLocale('zh_TW');
        $zhTWRelative = LocalizationHelper::formatRelativeTime($date, 'zh_TW');
        
        App::setLocale('en');
        $enRelative = LocalizationHelper::formatRelativeTime($date, 'en');
        
        $this->assertStringContainsString('小時前', $zhTWRelative);
        $this->assertStringContainsString('hours ago', $enRelative);
    }

    /** @test */
    public function localization_helper_can_format_numbers()
    {
        $number = 12345.67;
        
        $formatted = LocalizationHelper::formatNumber($number, 2, 'zh_TW');
        
        $this->assertEquals('12,345.67', $formatted);
    }

    /** @test */
    public function localization_helper_can_format_currency()
    {
        $amount = 999.99;
        
        $zhTWFormat = LocalizationHelper::formatCurrency($amount, 'zh_TW');
        $enFormat = LocalizationHelper::formatCurrency($amount, 'en');
        
        $this->assertEquals('NT$999.99', $zhTWFormat);
        $this->assertEquals('$999.99', $enFormat);
    }

    /** @test */
    public function localization_helper_can_format_percentage()
    {
        $percentage = 85.5;
        
        $formatted = LocalizationHelper::formatPercentage($percentage, 1, 'zh_TW');
        
        $this->assertEquals('85.5%', $formatted);
    }

    /** @test */
    public function localization_helper_can_get_weekdays()
    {
        $zhTWWeekdays = LocalizationHelper::getWeekdays('zh_TW');
        $enWeekdays = LocalizationHelper::getWeekdays('en');
        
        $this->assertEquals('星期一', $zhTWWeekdays['monday']);
        $this->assertEquals('Monday', $enWeekdays['monday']);
    }

    /** @test */
    public function localization_helper_can_get_months()
    {
        $zhTWMonths = LocalizationHelper::getMonths('zh_TW');
        $enMonths = LocalizationHelper::getMonths('en');
        
        $this->assertEquals('一月', $zhTWMonths[1]);
        $this->assertEquals('January', $enMonths[1]);
    }

    /** @test */
    public function localization_helper_can_format_file_size()
    {
        $bytes = 1024 * 1024; // 1MB
        
        $zhTWFormat = LocalizationHelper::formatFileSize($bytes, 'zh_TW');
        $enFormat = LocalizationHelper::formatFileSize($bytes, 'en');
        
        $this->assertStringContainsString('MB', $zhTWFormat);
        $this->assertStringContainsString('MB', $enFormat);
    }

    /** @test */
    public function it_updates_user_locale_when_switching_language()
    {
        $this->actingAs($this->user);
        
        $this->languageService->switchLocale('en');
        
        $this->user->refresh();
        $this->assertEquals('en', $this->user->locale);
    }

    /** @test */
    public function language_selector_component_can_render()
    {
        $this->actingAs($this->user);
        
        $component = \Livewire\Livewire::test(\App\Livewire\Admin\Layout\LanguageSelector::class);
        
        $component->assertStatus(200)
                  ->assertSee('正體中文')
                  ->assertSee('English');
    }

    /** @test */
    public function language_selector_can_switch_language()
    {
        $this->actingAs($this->user);
        
        $component = \Livewire\Livewire::test(\App\Livewire\Admin\Layout\LanguageSelector::class);
        
        $component->call('switchLanguage', 'en')
                  ->assertRedirect();
        
        $this->assertEquals('en', Session::get('locale'));
    }

    /** @test */
    public function language_selector_rejects_unsupported_language()
    {
        $this->actingAs($this->user);
        
        $component = \Livewire\Livewire::test(\App\Livewire\Admin\Layout\LanguageSelector::class);
        
        $component->call('switchLanguage', 'fr')
                  ->assertDispatched('toast');
    }

    /** @test */
    public function set_locale_middleware_sets_locale_from_session()
    {
        Session::put('locale', 'en');
        
        $response = $this->get('/');
        
        $this->assertEquals('en', App::getLocale());
    }

    /** @test */
    public function set_locale_middleware_sets_locale_from_user_preference()
    {
        $user = User::factory()->create(['locale' => 'en']);
        
        $this->actingAs($user);
        
        $response = $this->get('/');
        
        $this->assertEquals('en', App::getLocale());
    }

    /** @test */
    public function language_files_exist_for_supported_locales()
    {
        $supportedLocales = ['zh_TW', 'en'];
        $requiredFiles = ['admin', 'auth', 'layout', 'validation', 'passwords'];
        
        foreach ($supportedLocales as $locale) {
            foreach ($requiredFiles as $file) {
                $path = resource_path("lang/{$locale}/{$file}.php");
                $this->assertFileExists($path, "Language file {$file}.php missing for locale {$locale}");
            }
        }
    }

    /** @test */
    public function language_service_can_load_language_files()
    {
        $adminTranslations = $this->languageService->loadLanguageFile('admin', 'zh_TW');
        
        $this->assertIsArray($adminTranslations);
        $this->assertArrayHasKey('title', $adminTranslations);
        $this->assertEquals('後台管理系統', $adminTranslations['title']);
    }

    /** @test */
    public function language_service_can_check_translation_existence()
    {
        $this->assertTrue($this->languageService->hasTranslation('admin.title', 'zh_TW'));
        $this->assertFalse($this->languageService->hasTranslation('admin.nonexistent', 'zh_TW'));
    }

    /** @test */
    public function language_service_can_get_translation()
    {
        $translation = $this->languageService->getTranslation('admin.title', [], 'zh_TW');
        
        $this->assertEquals('後台管理系統', $translation);
    }
}