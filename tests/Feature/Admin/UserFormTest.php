<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Http\Livewire\Admin\Users\UserForm;

/**
 * 使用者表單元件測試
 */
class UserFormTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 設定測試環境
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // 執行種子檔案
        $this->seed();
    }

    /**
     * 測試建立使用者表單渲染
     */
    public function test_create_user_form_renders()
    {
        // 建立具有權限的使用者
        $user = User::factory()->create();
        $adminRole = Role::where('name', 'admin')->first();
        $user->roles()->attach($adminRole);

        $this->actingAs($user);

        Livewire::test(UserForm::class)
            ->assertSee('建立使用者')
            ->assertSee('使用者名稱')
            ->assertSee('密碼')
            ->assertSee('角色指派');
    }

    /**
     * 測試編輯使用者表單渲染
     */
    public function test_edit_user_form_renders()
    {
        // 建立具有權限的使用者
        $adminUser = User::factory()->create();
        $adminRole = Role::where('name', 'admin')->first();
        $adminUser->roles()->attach($adminRole);

        // 建立要編輯的使用者
        $targetUser = User::factory()->create([
            'username' => 'testuser',
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);

        $this->actingAs($adminUser);

        Livewire::test(UserForm::class, ['userId' => $targetUser->id])
            ->assertSee('編輯使用者')
            ->assertSee('testuser')
            ->assertSee('Test User')
            ->assertSee('test@example.com');
    }

    /**
     * 測試建立使用者功能
     */
    public function test_can_create_user()
    {
        // 建立具有權限的使用者
        $user = User::factory()->create();
        $adminRole = Role::where('name', 'admin')->first();
        $user->roles()->attach($adminRole);

        $this->actingAs($user);

        $userRole = Role::where('name', 'user')->first();

        Livewire::test(UserForm::class)
            ->set('username', 'newuser')
            ->set('name', 'New User')
            ->set('email', 'newuser@example.com')
            ->set('password', 'Password123!')
            ->set('password_confirmation', 'Password123!')
            ->set('selectedRoles', [$userRole->id])
            ->call('save')
            ->assertRedirect(route('admin.users.index'));

        // 驗證使用者已建立
        $this->assertDatabaseHas('users', [
            'username' => 'newuser',
            'name' => 'New User',
            'email' => 'newuser@example.com'
        ]);

        // 驗證角色已指派
        $newUser = User::where('username', 'newuser')->first();
        $this->assertTrue($newUser->hasRole('user'));
    }

    /**
     * 測試使用者名稱唯一性驗證
     */
    public function test_username_uniqueness_validation()
    {
        // 建立具有權限的使用者
        $user = User::factory()->create();
        $adminRole = Role::where('name', 'admin')->first();
        $user->roles()->attach($adminRole);

        // 建立已存在的使用者
        $existingUser = User::factory()->create(['username' => 'existinguser']);

        $this->actingAs($user);

        Livewire::test(UserForm::class)
            ->set('username', 'existinguser')
            ->set('password', 'Password123!')
            ->set('password_confirmation', 'Password123!')
            ->call('save')
            ->assertHasErrors(['username']);
    }

    /**
     * 測試密碼強度驗證
     */
    public function test_password_strength_validation()
    {
        // 建立具有權限的使用者
        $user = User::factory()->create();
        $adminRole = Role::where('name', 'admin')->first();
        $user->roles()->attach($adminRole);

        $this->actingAs($user);

        Livewire::test(UserForm::class)
            ->set('username', 'testuser')
            ->set('password', '123') // 太短的密碼
            ->set('password_confirmation', '123')
            ->call('save')
            ->assertHasErrors(['password']);
    }

    /**
     * 測試即時驗證
     */
    public function test_real_time_validation()
    {
        // 建立具有權限的使用者
        $user = User::factory()->create();
        $adminRole = Role::where('name', 'admin')->first();
        $user->roles()->attach($adminRole);

        $this->actingAs($user);

        Livewire::test(UserForm::class)
            ->set('username', 'ab') // 太短
            ->call('updatedUsername')
            ->assertHasErrors(['username']);
    }

    /**
     * 測試權限檢查
     */
    public function test_permission_check()
    {
        // 建立沒有權限的使用者
        $user = User::factory()->create();
        $userRole = Role::where('name', 'user')->first();
        $user->roles()->attach($userRole);

        $this->actingAs($user);

        // 測試建立權限
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        Livewire::test(UserForm::class);
    }
}