<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Http\Livewire\Admin\Users\UserDelete;

/**
 * 使用者刪除元件測試
 */
class UserDeleteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 設定測試環境
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試角色
        $superAdminRole = Role::create([
            'name' => 'super_admin',
            'display_name' => '超級管理員',
            'description' => '系統超級管理員'
        ]);
        
        $adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理員',
            'description' => '系統管理員'
        ]);
        
        $userRole = Role::create([
            'name' => 'user',
            'display_name' => '一般使用者',
            'description' => '一般使用者'
        ]);
    }

    /**
     * 測試超級管理員可以看到刪除確認對話框
     */
    public function test_super_admin_can_see_delete_confirmation_dialog()
    {
        // 建立超級管理員
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        
        // 建立要刪除的使用者
        $userToDelete = User::factory()->create();
        
        // 以超級管理員身份登入
        $this->actingAs($superAdmin);
        
        // 測試 UserDelete 元件
        Livewire::test(UserDelete::class)
            ->call('showDeleteConfirmation', $userToDelete->id)
            ->assertSet('showConfirmDialog', true)
            ->assertSet('userToDelete', $userToDelete->id)
            ->assertSee('確認停用使用者');
    }

    /**
     * 測試使用者停用功能
     */
    public function test_can_disable_user()
    {
        // 建立超級管理員
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        
        // 建立要停用的使用者
        $userToDisable = User::factory()->create(['is_active' => true]);
        
        // 以超級管理員身份登入
        $this->actingAs($superAdmin);
        
        // 測試停用使用者
        Livewire::test(UserDelete::class)
            ->call('showDeleteConfirmation', $userToDisable->id)
            ->set('deleteAction', 'disable')
            ->call('confirmDelete')
            ->assertEmitted('userDeleted');
        
        // 驗證使用者已被停用
        $this->assertFalse($userToDisable->fresh()->is_active);
    }

    /**
     * 測試使用者永久刪除功能
     */
    public function test_can_permanently_delete_user()
    {
        // 建立超級管理員
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        
        // 建立要刪除的使用者
        $userToDelete = User::factory()->create();
        $username = $userToDelete->username;
        
        // 以超級管理員身份登入
        $this->actingAs($superAdmin);
        
        // 測試永久刪除使用者
        Livewire::test(UserDelete::class)
            ->call('showDeleteConfirmation', $userToDelete->id)
            ->set('deleteAction', 'delete')
            ->set('confirmUsername', $username)
            ->call('confirmDelete')
            ->assertEmitted('userDeleted');
        
        // 驗證使用者已被刪除
        $this->assertDatabaseMissing('users', ['id' => $userToDelete->id]);
    }

    /**
     * 測試不能刪除自己的帳號
     */
    public function test_cannot_delete_own_account()
    {
        // 建立超級管理員
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        
        // 以超級管理員身份登入
        $this->actingAs($superAdmin);
        
        // 嘗試刪除自己的帳號
        Livewire::test(UserDelete::class)
            ->call('showDeleteConfirmation', $superAdmin->id)
            ->assertSet('showConfirmDialog', false);
    }

    /**
     * 測試確認使用者名稱驗證
     */
    public function test_username_confirmation_validation()
    {
        // 建立超級管理員
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        
        // 建立要刪除的使用者
        $userToDelete = User::factory()->create();
        
        // 以超級管理員身份登入
        $this->actingAs($superAdmin);
        
        // 測試錯誤的使用者名稱確認
        Livewire::test(UserDelete::class)
            ->call('showDeleteConfirmation', $userToDelete->id)
            ->set('deleteAction', 'delete')
            ->set('confirmUsername', 'wrong_username')
            ->call('confirmDelete')
            ->assertHasErrors('confirmUsername');
    }

    /**
     * 測試即時使用者名稱驗證
     */
    public function test_real_time_username_validation()
    {
        // 建立超級管理員
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        
        // 建立要刪除的使用者
        $userToDelete = User::factory()->create();
        
        // 以超級管理員身份登入
        $this->actingAs($superAdmin);
        
        // 測試即時驗證
        Livewire::test(UserDelete::class)
            ->call('showDeleteConfirmation', $userToDelete->id)
            ->set('deleteAction', 'delete')
            ->set('confirmUsername', 'wrong')
            ->assertHasErrors('confirmUsername')
            ->set('confirmUsername', $userToDelete->username)
            ->assertHasNoErrors('confirmUsername');
    }

    /**
     * 測試處理中狀態不能關閉對話框
     */
    public function test_cannot_close_dialog_while_processing()
    {
        // 建立超級管理員
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        
        // 建立要刪除的使用者
        $userToDelete = User::factory()->create();
        
        // 以超級管理員身份登入
        $this->actingAs($superAdmin);
        
        // 測試處理中狀態
        $component = Livewire::test(UserDelete::class)
            ->call('showDeleteConfirmation', $userToDelete->id)
            ->set('isProcessing', true)
            ->call('closeDialog')
            ->assertSet('showConfirmDialog', true); // 對話框應該仍然開啟
    }
}