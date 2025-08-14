<?php

namespace Tests\Feature\Livewire\Admin\Users;

use App\Livewire\Admin\Users\UserDeleteModal;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * UserDeleteModal 元件測試
 */
class UserDeleteModalTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $testUser;
    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立超級管理員角色
        $superAdminRole = Role::create([
            'name' => 'super_admin',
            'display_name' => '超級管理員',
            'description' => '超級管理員'
        ]);

        // 建立測試角色
        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理員',
            'description' => '系統管理員'
        ]);

        // 建立管理員使用者（使用超級管理員角色以確保有所有權限）
        $this->admin = User::create([
            'username' => 'admin',
            'name' => '管理員',
            'email' => 'admin@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);
        $this->admin->roles()->attach($superAdminRole);

        // 建立測試使用者
        $this->testUser = User::create([
            'username' => 'testuser',
            'name' => '測試使用者',
            'email' => 'test@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function 可以顯示刪除確認對話框()
    {
        $this->actingAs($this->admin);

        Livewire::test(UserDeleteModal::class)
            ->dispatch('confirm-user-delete', userId: $this->testUser->id)
            ->assertSet('showModal', true)
            ->assertSet('userId', $this->testUser->id)
            ->assertSee('請選擇操作')
            ->assertSee($this->testUser->username);
    }

    /** @test */
    public function 可以選擇停用使用者()
    {
        $this->actingAs($this->admin);

        Livewire::test(UserDeleteModal::class)
            ->dispatch('confirm-user-delete', userId: $this->testUser->id)
            ->set('selectedAction', 'disable')
            ->call('executeAction')
            ->assertDispatched('show-toast')
            ->assertDispatched('user-status-updated');

        $this->testUser->refresh();
        $this->assertFalse($this->testUser->is_active);
    }

    /** @test */
    public function 可以軟刪除使用者()
    {
        $this->actingAs($this->admin);

        Livewire::test(UserDeleteModal::class)
            ->dispatch('confirm-user-delete', userId: $this->testUser->id)
            ->set('selectedAction', 'delete')
            ->set('confirmText', $this->testUser->username)
            ->call('executeAction')
            ->assertDispatched('show-toast')
            ->assertDispatched('user-delete-confirmed');

        $this->assertSoftDeleted($this->testUser);
    }

    /** @test */
    public function 刪除時需要確認使用者名稱()
    {
        $this->actingAs($this->admin);

        Livewire::test(UserDeleteModal::class)
            ->dispatch('confirm-user-delete', userId: $this->testUser->id)
            ->set('selectedAction', 'delete')
            ->set('confirmText', 'wrong-username')
            ->call('executeAction')
            ->assertHasErrors('confirmText');

        $this->assertDatabaseHas('users', ['id' => $this->testUser->id, 'deleted_at' => null]);
    }

    /** @test */
    public function 不能刪除自己的帳號()
    {
        $this->actingAs($this->admin);



        $component = Livewire::test(UserDeleteModal::class)
            ->dispatch('confirm-user-delete', userId: $this->admin->id);
            
        // 檢查對話框是否沒有顯示
        $component->assertSet('showModal', false);
        
        // 檢查是否發送了錯誤訊息
        $component->assertDispatched('show-toast');
    }

    /** @test */
    public function 非管理員不能刪除使用者()
    {
        $regularUser = User::create([
            'username' => 'regular',
            'name' => '一般使用者',
            'email' => 'regular@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);

        $this->actingAs($regularUser);

        Livewire::test(UserDeleteModal::class)
            ->dispatch('confirm-user-delete', userId: $this->testUser->id)
            ->assertSet('showModal', false)
            ->assertDispatched('show-toast');
    }

    /** @test */
    public function 可以關閉對話框()
    {
        $this->actingAs($this->admin);

        Livewire::test(UserDeleteModal::class)
            ->dispatch('confirm-user-delete', userId: $this->testUser->id)
            ->assertSet('showModal', true)
            ->call('closeModal')
            ->assertSet('showModal', false)
            ->assertSet('selectedAction', '')
            ->assertSet('confirmText', '');
    }

    /** @test */
    public function 驗證表單規則()
    {
        $this->actingAs($this->admin);

        Livewire::test(UserDeleteModal::class)
            ->dispatch('confirm-user-delete', userId: $this->testUser->id)
            ->call('executeAction')
            ->assertHasErrors('selectedAction');
    }

    /** @test */
    public function 超級管理員不能被刪除()
    {
        // 建立另一個超級管理員使用者
        $superAdmin = User::create([
            'username' => 'superadmin',
            'name' => '超級管理員',
            'email' => 'superadmin@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);
        
        // 取得已存在的 super_admin 角色
        $superAdminRole = Role::where('name', 'super_admin')->first();
        $superAdmin->roles()->attach($superAdminRole);

        // 建立一般管理員來執行刪除操作
        $regularAdmin = User::create([
            'username' => 'regularadmin',
            'name' => '一般管理員',
            'email' => 'regularadmin@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);
        $regularAdmin->roles()->attach($this->adminRole);

        $this->actingAs($regularAdmin);

        Livewire::test(UserDeleteModal::class)
            ->dispatch('confirm-user-delete', userId: $superAdmin->id)
            ->assertSet('showModal', false)
            ->assertDispatched('show-toast');
    }
}