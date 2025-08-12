<?php

namespace Tests\Feature\Admin\Layout;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Livewire\Livewire;
use App\Livewire\Admin\Layout\UserMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

/**
 * 使用者選單元件測試
 */
class UserMenuTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立測試角色
        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理員',
            'description' => '系統管理員',
        ]);

        // 建立測試使用者
        $this->user = User::create([
            'username' => 'testuser',
            'name' => '測試使用者',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'theme_preference' => 'light',
            'locale' => 'zh_TW',
            'is_active' => true,
        ]);

        $this->user->roles()->attach($this->adminRole);
    }

    /** @test */
    public function 可以渲染使用者選單元件()
    {
        $this->actingAs($this->user);

        Livewire::test(UserMenu::class)
            ->assertSee('測試使用者')
            ->assertSee('test@example.com')
            ->call('toggle')
            ->assertSee('管理員');
    }

    /** @test */
    public function 可以切換選單顯示狀態()
    {
        $this->actingAs($this->user);

        Livewire::test(UserMenu::class)
            ->assertSet('isOpen', false)
            ->call('toggle')
            ->assertSet('isOpen', true)
            ->call('toggle')
            ->assertSet('isOpen', false);
    }

    /** @test */
    public function 可以顯示使用者縮寫()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(UserMenu::class);
        
        $this->assertEquals('測', $component->get('userInitials'));
    }

    /** @test */
    public function 可以顯示多個字的使用者縮寫()
    {
        $this->user->update(['name' => '張 三']);
        $this->actingAs($this->user);

        $component = Livewire::test(UserMenu::class);
        
        $this->assertEquals('張三', $component->get('userInitials'));
    }

    /** @test */
    public function 可以更新個人資料()
    {
        $this->actingAs($this->user);

        Livewire::test(UserMenu::class)
            ->set('name', '新名稱')
            ->set('email', 'newemail@example.com')
            ->call('updateProfile')
            ->assertHasNoErrors();

        $this->user->refresh();
        $this->assertEquals('新名稱', $this->user->name);
        $this->assertEquals('newemail@example.com', $this->user->email);
    }

    /** @test */
    public function 更新個人資料時驗證必填欄位()
    {
        $this->actingAs($this->user);

        Livewire::test(UserMenu::class)
            ->set('name', '')
            ->set('email', '')
            ->call('updateProfile')
            ->assertHasErrors(['name', 'email']);
    }

    /** @test */
    public function 更新個人資料時驗證電子郵件格式()
    {
        $this->actingAs($this->user);

        Livewire::test(UserMenu::class)
            ->set('name', '測試使用者')
            ->set('email', 'invalid-email')
            ->call('updateProfile')
            ->assertHasErrors(['email']);
    }

    /** @test */
    public function 可以更新密碼()
    {
        $this->actingAs($this->user);

        Livewire::test(UserMenu::class)
            ->set('currentPassword', 'password')
            ->set('newPassword', 'newpassword123')
            ->set('newPasswordConfirmation', 'newpassword123')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $this->user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $this->user->password));
    }

    /** @test */
    public function 更新密碼時驗證目前密碼()
    {
        $this->actingAs($this->user);

        Livewire::test(UserMenu::class)
            ->set('currentPassword', 'wrongpassword')
            ->set('newPassword', 'newpassword123')
            ->set('newPasswordConfirmation', 'newpassword123')
            ->call('updatePassword')
            ->assertHasErrors(['currentPassword']);
    }

    /** @test */
    public function 更新密碼時驗證新密碼確認()
    {
        $this->actingAs($this->user);

        Livewire::test(UserMenu::class)
            ->set('currentPassword', 'password')
            ->set('newPassword', 'newpassword123')
            ->set('newPasswordConfirmation', 'differentpassword')
            ->call('updatePassword')
            ->assertHasErrors(['newPassword']);
    }

    /** @test */
    public function 可以上傳頭像()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $file = UploadedFile::fake()->image('avatar.jpg');

        Livewire::test(UserMenu::class)
            ->set('avatar', $file)
            ->call('uploadAvatar')
            ->assertHasNoErrors();

        $this->user->refresh();
        $this->assertNotNull($this->user->avatar);
        Storage::disk('public')->assertExists($this->user->avatar);
    }

    /** @test */
    public function 上傳頭像時驗證檔案類型()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $file = UploadedFile::fake()->create('document.pdf', 1000);

        Livewire::test(UserMenu::class)
            ->set('avatar', $file)
            ->call('uploadAvatar')
            ->assertHasErrors(['avatar']);
    }

    /** @test */
    public function 可以移除頭像()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        // 先設定頭像
        $this->user->update(['avatar' => 'avatars/test.jpg']);
        Storage::disk('public')->put('avatars/test.jpg', 'fake content');

        Livewire::test(UserMenu::class)
            ->call('removeAvatar');

        $this->user->refresh();
        $this->assertNull($this->user->avatar);
        Storage::disk('public')->assertMissing('avatars/test.jpg');
    }

    /** @test */
    public function 可以更新主題偏好()
    {
        $this->actingAs($this->user);

        Livewire::test(UserMenu::class)
            ->call('updateThemePreference', 'dark')
            ->assertDispatched('theme-changed', theme: 'dark');

        $this->user->refresh();
        $this->assertEquals('dark', $this->user->theme_preference);
    }

    /** @test */
    public function 可以更新語言偏好()
    {
        $this->actingAs($this->user);

        Livewire::test(UserMenu::class)
            ->call('updateLocalePreference', 'en')
            ->assertDispatched('locale-changed', locale: 'en');

        $this->user->refresh();
        $this->assertEquals('en', $this->user->locale);
    }

    /** @test */
    public function 可以登出()
    {
        $this->actingAs($this->user);

        Livewire::test(UserMenu::class)
            ->call('logout')
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }

    /** @test */
    public function 可以前往個人資料頁面()
    {
        $this->actingAs($this->user);

        Livewire::test(UserMenu::class)
            ->call('goToProfile')
            ->assertRedirect(route('admin.profile'));
    }

    /** @test */
    public function 可以前往帳號設定頁面()
    {
        $this->actingAs($this->user);

        Livewire::test(UserMenu::class)
            ->call('goToAccountSettings')
            ->assertRedirect(route('admin.account.settings'));
    }

    /** @test */
    public function 可以前往說明中心()
    {
        $this->actingAs($this->user);

        Livewire::test(UserMenu::class)
            ->call('goToHelpCenter')
            ->assertRedirect(route('admin.help'));
    }

    /** @test */
    public function 可以關閉選單()
    {
        $this->actingAs($this->user);

        Livewire::test(UserMenu::class)
            ->set('isOpen', true)
            ->set('showAvatarUpload', true)
            ->set('showProfileEdit', true)
            ->call('close')
            ->assertSet('isOpen', false)
            ->assertSet('showAvatarUpload', false)
            ->assertSet('showProfileEdit', false);
    }

    /** @test */
    public function 可以監聽關閉其他選單事件()
    {
        $this->actingAs($this->user);

        Livewire::test(UserMenu::class)
            ->set('isOpen', true)
            ->dispatch('close-other-menus', except: 'notifications')
            ->assertSet('isOpen', false);
    }

    /** @test */
    public function 不會關閉自己的選單當收到關閉其他選單事件()
    {
        $this->actingAs($this->user);

        Livewire::test(UserMenu::class)
            ->set('isOpen', true)
            ->dispatch('close-other-menus', except: 'user-menu')
            ->assertSet('isOpen', true);
    }

    /** @test */
    public function 可以取得頭像URL()
    {
        $this->actingAs($this->user);

        // 測試沒有頭像時使用 Gravatar
        $component = Livewire::test(UserMenu::class);
        $avatarUrl = $component->get('avatarUrl');
        $this->assertStringContains('gravatar.com', $avatarUrl);

        // 測試有頭像時使用本地檔案
        $this->user->update(['avatar' => 'avatars/test.jpg']);
        $component = Livewire::test(UserMenu::class);
        $avatarUrl = $component->get('avatarUrl');
        $this->assertStringContains('avatars/test.jpg', $avatarUrl);
    }

    /** @test */
    public function 可以取得使用者角色()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(UserMenu::class);
        $userRoles = $component->get('userRoles');
        
        $this->assertEquals('管理員', $userRoles);
    }

    /** @test */
    public function 沒有角色時顯示一般使用者()
    {
        $this->user->roles()->detach();
        $this->actingAs($this->user);

        $component = Livewire::test(UserMenu::class);
        $userRoles = $component->get('userRoles');
        
        $this->assertEquals('一般使用者', $userRoles);
    }
}