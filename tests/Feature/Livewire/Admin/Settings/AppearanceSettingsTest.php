<?php

namespace Tests\Feature\Livewire\Admin\Settings;

use App\Livewire\Admin\Settings\AppearanceSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use App\Models\Role;
use Tests\TestCase;

class AppearanceSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create();
        $adminRole = Role::create(['name' => 'admin', 'display_name' => 'Admin']);
        $this->adminUser->assignRole($adminRole);
        $this->actingAs($this->adminUser);
    }

    /** @test */
    public function component_renders_successfully()
    {
        Livewire::test(AppearanceSettings::class)
            ->assertStatus(200)
            ->assertSee(__('admin.settings_page.appearance.title'));
    }

    /** @test */
    public function it_loads_initial_settings_correctly()
    {
        // Arrange: Set a known value in config
        config(['system-settings.settings.appearance.default_theme.default' => 'dark']);

        Livewire::test(AppearanceSettings::class)
            ->assertSet('settings.appearance.default_theme', 'dark');
    }

    /** @test */
    public function it_can_update_valid_settings()
    {
        Livewire::test(AppearanceSettings::class)
            ->set('settings.appearance.default_theme', 'dark')
            ->set('settings.appearance.primary_color', '#000000')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('saved', type: 'success');

        $this->assertEquals('dark', config('system-settings.settings.appearance.default_theme.default'));
        $this->assertEquals('#000000', config('system-settings.settings.appearance.primary_color.default'));
    }

    /** @test */
    public function validation_fails_with_invalid_color_format()
    {
        Livewire::test(AppearanceSettings::class)
            ->set('settings.appearance.primary_color', 'invalid-color')
            ->call('save')
            ->assertHasErrors(['settings.appearance.primary_color' => 'regex']);
    }

    /** @test */
    public function it_can_upload_a_logo_image()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('logo.png');

        Livewire::test(AppearanceSettings::class)
            ->set('logo', $file)
            ->call('save')
            ->assertHasNoErrors();

        Storage::disk('public')->assertExists('logos/' . $file->hashName());
    }

    /** @test */
    public function validation_fails_for_logo_that_is_not_an_image()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        Livewire::test(AppearanceSettings::class)
            ->set('logo', $file)
            ->call('save')
            ->assertHasErrors(['logo' => 'image']);
    }

    /** @test */
    public function validation_fails_for_logo_that_is_too_large()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('large_logo.png')->size(3000); // 3000 KB > 2048 KB

        Livewire::test(AppearanceSettings::class)
            ->set('logo', $file)
            ->call('save')
            ->assertHasErrors(['logo' => 'max']);
    }

    /** @test */
    public function it_loads_responsive_config_correctly()
    {
        Livewire::test(AppearanceSettings::class)
            ->assertSet('responsiveConfig.mobile_breakpoint', 768)
            ->assertSet('responsiveConfig.tablet_breakpoint', 1024)
            ->assertSet('responsiveConfig.desktop_breakpoint', 1280)
            ->assertSet('responsiveConfig.enable_mobile_menu', true)
            ->assertSet('responsiveConfig.enable_responsive_tables', true)
            ->assertSet('responsiveConfig.enable_touch_friendly', true);
    }

    /** @test */
    public function it_can_update_responsive_config()
    {
        Livewire::test(AppearanceSettings::class)
            ->set('responsiveConfig.mobile_breakpoint', 600)
            ->set('responsiveConfig.tablet_breakpoint', 900)
            ->set('responsiveConfig.desktop_breakpoint', 1200)
            ->set('responsiveConfig.enable_mobile_menu', false)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('saved', type: 'success');
    }

    /** @test */
    public function validation_fails_for_invalid_breakpoints()
    {
        Livewire::test(AppearanceSettings::class)
            ->set('responsiveConfig.mobile_breakpoint', 100) // Too small
            ->set('responsiveConfig.tablet_breakpoint', 2000) // Too large
            ->call('save')
            ->assertHasErrors([
                'responsiveConfig.mobile_breakpoint' => 'min',
                'responsiveConfig.tablet_breakpoint' => 'max'
            ]);
    }

    /** @test */
    public function it_can_toggle_preview_mode()
    {
        Livewire::test(AppearanceSettings::class)
            ->assertSet('previewMode', false)
            ->call('togglePreview')
            ->assertSet('previewMode', true)
            ->assertDispatched('appearance-preview-start')
            ->call('togglePreview')
            ->assertSet('previewMode', false)
            ->assertDispatched('appearance-preview-stop');
    }

    /** @test */
    public function it_can_switch_preview_device()
    {
        Livewire::test(AppearanceSettings::class)
            ->set('previewMode', true)
            ->call('switchPreviewDevice', 'mobile')
            ->assertSet('previewDevice', 'mobile')
            ->assertDispatched('appearance-preview-device-changed', device: 'mobile')
            ->call('switchPreviewDevice', 'tablet')
            ->assertSet('previewDevice', 'tablet')
            ->call('switchPreviewDevice', 'desktop')
            ->assertSet('previewDevice', 'desktop');
    }

    /** @test */
    public function it_can_reset_to_defaults()
    {
        // First, change some settings
        Livewire::test(AppearanceSettings::class)
            ->set('settings.appearance.default_theme', 'dark')
            ->set('settings.appearance.primary_color', '#000000')
            ->set('responsiveConfig.mobile_breakpoint', 600)
            ->call('resetToDefaults')
            ->assertSet('settings.appearance.default_theme', 'auto') // Default from config
            ->assertSet('settings.appearance.primary_color', '#3B82F6') // Default from config
            ->assertSet('responsiveConfig.mobile_breakpoint', 768) // Default value
            ->assertDispatched('settings-reset', type: 'info');
    }

    /** @test */
    public function it_can_upload_favicon()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('favicon.png', 32, 32);

        Livewire::test(AppearanceSettings::class)
            ->set('favicon', $file)
            ->call('save')
            ->assertHasNoErrors();

        Storage::disk('public')->assertExists('favicons/' . $file->hashName());
    }

    /** @test */
    public function it_can_upload_login_background()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('background.jpg', 1920, 1080);

        Livewire::test(AppearanceSettings::class)
            ->set('loginBackground', $file)
            ->call('save')
            ->assertHasNoErrors();

        Storage::disk('public')->assertExists('backgrounds/' . $file->hashName());
    }

    /** @test */
    public function validation_fails_for_favicon_that_is_too_large()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('large_favicon.png')->size(600); // 600 KB > 512 KB

        Livewire::test(AppearanceSettings::class)
            ->set('favicon', $file)
            ->call('save')
            ->assertHasErrors(['favicon' => 'max']);
    }

    /** @test */
    public function validation_fails_for_login_background_that_is_too_large()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('large_background.jpg')->size(6000); // 6000 KB > 5120 KB

        Livewire::test(AppearanceSettings::class)
            ->set('loginBackground', $file)
            ->call('save')
            ->assertHasErrors(['loginBackground' => 'max']);
    }

    /** @test */
    public function it_saves_responsive_config_as_json()
    {
        $component = Livewire::test(AppearanceSettings::class)
            ->set('responsiveConfig.mobile_breakpoint', 600)
            ->set('responsiveConfig.enable_mobile_menu', false)
            ->call('save');

        // Check that the responsive config is saved in the settings array
        $component->assertSet('settings.appearance.responsive_config', [
            'mobile_breakpoint' => 600,
            'tablet_breakpoint' => 1024,
            'desktop_breakpoint' => 1280,
            'enable_mobile_menu' => false,
            'enable_responsive_tables' => true,
            'enable_touch_friendly' => true,
        ]);
    }
}
