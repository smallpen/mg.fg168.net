<?php

namespace Tests\Feature\Livewire\Admin\Users;

use App\Http\Livewire\Admin\Users\UserForm;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * UserForm 元件測試
 * 
 * 測試使用者表單的渲染、驗證、建立、編輯和權限控制
 */
class UserFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試角色
        $this->adminRole = Role::factory()->create(['name' => 'admin']);
        $this->userRole = Role::factory()->create(['name' => 'user']);
        
        // 建立管理員使用者
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($this->adminRole);
    }

    /**
     * 測試建立使用者表單渲染
     */
    public function test_create_form_renders_correctly()
    {
        $this->actingAs($this->admin);

        Livewire::test(UserForm::class)
            ->assertStatus(200)
            ->assertSee('建立使用者')
            ->assertSee('使用者名稱')
            ->assertSee('姓名')
            ->assertSee('電子郵件')
            ->assertSee('密碼')
            ->assertSee('角色');
    }

    /**
     * 測試編輯使用者表單渲染
     */
    public function test_edit_form_renders_correctly()
    {
        $this->actingAs($this->admin);
        $user = User::factory()->create();

        Livewire::test(UserForm::class, ['userId' => $user->id])
            ->assertStatus(200)
            ->assertSee('編輯使用者')
            ->assertSet('username', $user->username)
            ->assertSet('name', $user->name)
            ->assertSet('email', $user->email);
    }

    /**
     * 測試表單驗證規則
     */
    public function test_form_validation_rules()
    {
        $this->actingAs($this->admin);

        Livewire::test(UserForm::class)
            ->set('username', '')
            ->set('name', '')
            ->set('email', 'invalid-email')
            ->set('password', '123')
            ->call('save')
            ->assertHasErrors([
                'username' => 'required',
                'name' => 'required',
                'email' => 'email',
                'password' => 'min'
            ]);
    }

    /**
     * 測試使用者名稱唯一性驗證
     */
    public function test_username_uniqueness_validation()
    {
        $this->actingAs($this->admin);
        $existingUser = User::factory()->create(['username' => 'testuser']);

        Livewire::test(UserForm::class)
            ->set('username', 'testuser')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password', 'password123')
            ->call('save')
            ->assertHasErrors(['username' => 'unique']);
    }

    /**
     * 測試電子郵件唯一性驗證
     */
    public function test_email_uniqueness_validation()
    {
        $this->actingAs($this->admin);
        $existingUser = User::factory()->create(['email' => 'test@example.com']);

        Livewire::test(UserForm::class)
            ->set('username', 'newuser')
            ->set('name', 'New User')
            ->set('email', 'test@example.com')
            ->set('password', 'password123')
            ->call('save')
            ->assertHasErrors(['email' => 'unique']);
    }

    /**
     * 測試成功建立使用者
     */
    public function test_successful_user_creation()
    {
        $this->actingAs($this->admin);

        Livewire::test(UserForm::class)
            ->set('username', 'newuser')
            ->set('name', 'New User')
            ->set('email', 'newuser@example.com')
            ->set('password', 'password123')
            ->set('selectedRoles', [$this->userRole->id])
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('user-saved')
            ->assertRedirect('/admin/users');

        $this->assertDatabaseHas('users', [
            'username' => 'newuser',
            'name' => 'New User',
            'email' => 'newuser@example.com'
        ]);
    }

    /**
     * 測試成功編輯使用者
     */
    public function test_successful_user_update()
    {
        $this->actingAs($this->admin);
        $user = User::factory()->create();

        Livewire::test(UserForm::class, ['userId' => $user->id])
            ->set('name', 'Updated Name')
            ->set('email', 'updated@example.com')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('user-saved');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com'
        ]);
    }

    /**
     * 測試角色指派功能
     */
    public function test_role_assignment()
    {
        $this->actingAs($this->admin);

        Livewire::test(UserForm::class)
            ->set('username', 'roleuser')
            ->set('name', 'Role User')
            ->set('email', 'roleuser@example.com')
            ->set('password', 'password123')
            ->set('selectedRoles', [$this->adminRole->id, $this->userRole->id])
            ->call('save');

        $user = User::where('username', 'roleuser')->first();
        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasRole('user'));
    }

    /**
     * 測試密碼更新（編輯模式）
     */
    public function test_password_update_in_edit_mode()
    {
        $this->actingAs($this->admin);
        $user = User::factory()->create();
        $originalPassword = $user->password;

        // 不更新密碼
        Livewire::test(UserForm::class, ['userId' => $user->id])
            ->set('name', 'Updated Name')
            ->call('save');

        $this->assertEquals($originalPassword, $user->fresh()->password);

        // 更新密碼
        Livewire::test(UserForm::class, ['userId' => $user->id])
            ->set('password', 'newpassword123')
            ->call('save');

        $this->assertNotEquals($originalPassword, $user->fresh()->password);
    }

    /**
     * 測試即時驗證
     */
    public function test_real_time_validation()
    {
        $this->actingAs($this->admin);

        Livewire::test(UserForm::class)
            ->set('username', 'ab')
            ->assertHasErrors(['username' => 'min'])
            ->set('username', 'validusername')
            ->assertHasNoErrors(['username']);
    }

    /**
     * 測試表單重置功能
     */
    public function test_form_reset()
    {
        $this->actingAs($this->admin);

        Livewire::test(UserForm::class)
            ->set('username', 'testuser')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->call('resetForm')
            ->assertSet('username', '')
            ->assertSet('name', '')
            ->assertSet('email', '')
            ->assertSet('password', '');
    }

    /**
     * 測試權限控制 - 無權限使用者
     */
    public function test_unauthorized_access()
    {
        $unauthorizedUser = User::factory()->create();
        $this->actingAs($unauthorizedUser);

        Livewire::test(UserForm::class)
            ->assertForbidden();
    }

    /**
     * 測試編輯自己的帳號限制
     */
    public function test_edit_own_account_restrictions()
    {
        $this->actingAs($this->admin);

        Livewire::test(UserForm::class, ['userId' => $this->admin->id])
            ->assertSee('編輯個人資料')
            ->assertDontSee('角色'); // 不能修改自己的角色
    }

    /**
     * 測試密碼強度驗證
     */
    public function test_password_strength_validation()
    {
        $this->actingAs($this->admin);

        Livewire::test(UserForm::class)
            ->set('password', 'weak')
            ->call('save')
            ->assertHasErrors(['password']);

        Livewire::test(UserForm::class)
            ->set('password', 'StrongPassword123!')
            ->assertHasNoErrors(['password']);
    }

    /**
     * 測試使用者名稱格式驗證
     */
    public function test_username_format_validation()
    {
        $this->actingAs($this->admin);

        // 測試無效字元
        Livewire::test(UserForm::class)
            ->set('username', 'user@name')
            ->call('save')
            ->assertHasErrors(['username']);

        // 測試有效格式
        Livewire::test(UserForm::class)
            ->set('username', 'valid_username123')
            ->assertHasNoErrors(['username']);
    }

    /**
     * 測試角色選擇介面
     */
    public function test_role_selection_interface()
    {
        $this->actingAs($this->admin);

        Livewire::test(UserForm::class)
            ->assertSee($this->adminRole->display_name)
            ->assertSee($this->userRole->display_name)
            ->call('toggleRole', $this->adminRole->id)
            ->assertSet('selectedRoles', [$this->adminRole->id]);
    }
}