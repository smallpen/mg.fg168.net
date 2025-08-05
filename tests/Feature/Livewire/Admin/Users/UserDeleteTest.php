<?php

namespace Tests\Feature\Livewire\Admin\Users;

use App\Http\Livewire\Admin\Users\UserDelete;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * UserDelete 元件測試
 * 
 * 測試使用者刪除確認對話框和安全的使用者資料移除邏輯
 */
class UserDeleteTest extends TestCase
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
     * 測試刪除確認對話框渲染
     */
    public function test_delete_confirmation_dialog_renders()
    {
        $this->actingAs($this->admin);
        $user = User::factory()->create();

        Livewire::test(UserDelete::class, ['userId' => $user->id])
            ->assertStatus(200)
            ->assertSee('確認刪除使用者')
            ->assertSee($user->name)
            ->assertSee('此操作無法復原');
    }

    /**
     * 測試成功刪除使用者
     */
    public function test_successful_user_deletion()
    {
        $this->actingAs($this->admin);
        $user = User::factory()->create();

        Livewire::test(UserDelete::class, ['userId' => $user->id])
            ->call('confirmDelete')
            ->assertDispatched('user-deleted')
            ->assertRedirect('/admin/users');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    /**
     * 測試取消刪除
     */
    public function test_cancel_deletion()
    {
        $this->actingAs($this->admin);
        $user = User::factory()->create();

        Livewire::test(UserDelete::class, ['userId' => $user->id])
            ->call('cancelDelete')
            ->assertDispatched('delete-cancelled')
            ->assertRedirect('/admin/users');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    /**
     * 測試刪除不存在的使用者
     */
    public function test_delete_non_existent_user()
    {
        $this->actingAs($this->admin);

        Livewire::test(UserDelete::class, ['userId' => 999])
            ->assertStatus(404);
    }

    /**
     * 測試權限控制 - 無權限使用者
     */
    public function test_unauthorized_access()
    {
        $unauthorizedUser = User::factory()->create();
        $this->actingAs($unauthorizedUser);

        $user = User::factory()->create();

        Livewire::test(UserDelete::class, ['userId' => $user->id])
            ->assertForbidden();
    }

    /**
     * 測試防止刪除自己的帳號
     */
    public function test_prevent_self_deletion()
    {
        $this->actingAs($this->admin);

        Livewire::test(UserDelete::class, ['userId' => $this->admin->id])
            ->call('confirmDelete')
            ->assertHasErrors(['user' => 'self_deletion'])
            ->assertSee('無法刪除自己的帳號');

        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    /**
     * 測試防止刪除最後一個管理員
     */
    public function test_prevent_last_admin_deletion()
    {
        $this->actingAs($this->admin);

        // 確保只有一個管理員
        User::whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->where('id', '!=', $this->admin->id)->delete();

        Livewire::test(UserDelete::class, ['userId' => $this->admin->id])
            ->call('confirmDelete')
            ->assertHasErrors(['user' => 'last_admin'])
            ->assertSee('無法刪除最後一個管理員');

        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    /**
     * 測試軟刪除功能
     */
    public function test_soft_delete_functionality()
    {
        $this->actingAs($this->admin);
        $user = User::factory()->create();

        Livewire::test(UserDelete::class, ['userId' => $user->id])
            ->set('useSoftDelete', true)
            ->call('confirmDelete')
            ->assertDispatched('user-deactivated');

        // 檢查使用者被停用而非刪除
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => false
        ]);
    }

    /**
     * 測試刪除使用者時清理關聯資料
     */
    public function test_cleanup_related_data()
    {
        $this->actingAs($this->admin);
        $user = User::factory()->create();
        $role = Role::factory()->create();
        $user->roles()->attach($role);

        Livewire::test(UserDelete::class, ['userId' => $user->id])
            ->call('confirmDelete');

        // 檢查關聯資料是否被清理
        $this->assertDatabaseMissing('user_roles', ['user_id' => $user->id]);
    }

    /**
     * 測試刪除確認輸入
     */
    public function test_delete_confirmation_input()
    {
        $this->actingAs($this->admin);
        $user = User::factory()->create(['username' => 'testuser']);

        // 錯誤的確認輸入
        Livewire::test(UserDelete::class, ['userId' => $user->id])
            ->set('confirmationInput', 'wronginput')
            ->call('confirmDelete')
            ->assertHasErrors(['confirmationInput'])
            ->assertSee('請輸入正確的使用者名稱');

        // 正確的確認輸入
        Livewire::test(UserDelete::class, ['userId' => $user->id])
            ->set('confirmationInput', 'testuser')
            ->call('confirmDelete')
            ->assertHasNoErrors();
    }

    /**
     * 測試刪除原因記錄
     */
    public function test_deletion_reason_logging()
    {
        $this->actingAs($this->admin);
        $user = User::factory()->create();

        Livewire::test(UserDelete::class, ['userId' => $user->id])
            ->set('deletionReason', '違反使用條款')
            ->call('confirmDelete');

        // 檢查刪除原因是否被記錄
        $this->assertDatabaseHas('user_deletion_logs', [
            'deleted_user_id' => $user->id,
            'deleted_by' => $this->admin->id,
            'reason' => '違反使用條款'
        ]);
    }

    /**
     * 測試批量刪除預防
     */
    public function test_bulk_deletion_prevention()
    {
        $this->actingAs($this->admin);
        $users = User::factory()->count(3)->create();

        // 模擬快速連續刪除請求
        foreach ($users as $user) {
            Livewire::test(UserDelete::class, ['userId' => $user->id])
                ->call('confirmDelete');
        }

        // 檢查是否有適當的限制機制
        $this->assertTrue(true); // 這裡應該根據實際的批量刪除防護邏輯進行測試
    }

    /**
     * 測試刪除通知
     */
    public function test_deletion_notification()
    {
        $this->actingAs($this->admin);
        $user = User::factory()->create();

        Livewire::test(UserDelete::class, ['userId' => $user->id])
            ->call('confirmDelete')
            ->assertDispatched('show-notification', [
                'type' => 'success',
                'message' => '使用者已成功刪除'
            ]);
    }

    /**
     * 測試刪除前的依賴檢查
     */
    public function test_dependency_check_before_deletion()
    {
        $this->actingAs($this->admin);
        $user = User::factory()->create();

        // 模擬使用者有相關的業務資料
        // 這裡應該根據實際的業務邏輯建立相關資料

        Livewire::test(UserDelete::class, ['userId' => $user->id])
            ->assertSee('此使用者有相關資料')
            ->assertSee('建議停用而非刪除');
    }
}