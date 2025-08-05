<?php

namespace Tests\Feature\Livewire\Admin\Auth;

use App\Http\Livewire\Admin\Auth\LogoutButton;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * LogoutButton 元件測試
 * 
 * 測試登出按鈕的渲染、使用者互動和登出邏輯
 */
class LogoutButtonTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試元件能正確渲染
     */
    public function test_component_renders_correctly()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(LogoutButton::class)
            ->assertStatus(200)
            ->assertSee('登出');
    }

    /**
     * 測試登出功能
     */
    public function test_logout_functionality()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->assertAuthenticated();

        Livewire::test(LogoutButton::class)
            ->call('logout')
            ->assertRedirect('/admin/login');

        $this->assertGuest();
    }

    /**
     * 測試未登入使用者存取
     */
    public function test_guest_user_access()
    {
        Livewire::test(LogoutButton::class)
            ->assertRedirect('/admin/login');
    }

    /**
     * 測試 Session 清除
     */
    public function test_session_cleanup()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 設定一些 session 資料
        session(['test_key' => 'test_value']);
        session(['admin_theme' => 'dark']);

        Livewire::test(LogoutButton::class)
            ->call('logout');

        // 檢查 session 是否被清除
        $this->assertNull(session('test_key'));
        $this->assertNull(session('admin_theme'));
    }

    /**
     * 測試登出確認對話框
     */
    public function test_logout_confirmation()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(LogoutButton::class)
            ->call('confirmLogout')
            ->assertSet('showConfirmation', true)
            ->assertSee('確定要登出嗎？');
    }

    /**
     * 測試取消登出
     */
    public function test_cancel_logout()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(LogoutButton::class)
            ->set('showConfirmation', true)
            ->call('cancelLogout')
            ->assertSet('showConfirmation', false);

        $this->assertAuthenticated();
    }

    /**
     * 測試登出事件發送
     */
    public function test_logout_event_dispatch()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(LogoutButton::class)
            ->call('logout')
            ->assertDispatched('user-logged-out');
    }
}