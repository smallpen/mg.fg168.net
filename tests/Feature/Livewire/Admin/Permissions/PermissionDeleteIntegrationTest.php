<?php

namespace Tests\Feature\Livewire\Admin\Permissions;

use App\Livewire\Admin\Permissions\PermissionList;
use App\Livewire\Admin\Permissions\PermissionDeleteModal;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 權限刪除整合測試
 */
class PermissionDeleteIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Permission $testPermission;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立權限刪除權限
        $deletePermission = Permission::factory()->create([
            'name' => 'permissions.delete',
            'display_name' => '刪除權限',
            'module' => 'permissions',
            'type' => 'delete'
        ]);

        $viewPermission = Permission::factory()->create([
            'name' => 'permissions.view',
            'display_name' => '檢視權限',
            'module' => 'permissions',
            'type' => 'view'
        ]);

        // 建立測試管理員
        $this->admin = User::factory()->create();
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $adminRole->permissions()->attach([$deletePermission->id, $viewPermission->id]);
        $this->admin->roles()->attach($adminRole);

        // 建立測試權限
        $this->testPermission = Permission::factory()->create([
            'name' => 'test.permission',
            'display_name' => '測試權限',
            'module' => 'test',
            'type' => 'view'
        ]);
    }

    /**
     * 測試從權限列表觸發刪除流程
     */
    public function test_delete_permission_from_list()
    {
        $this->actingAs($this->admin);

        // 直接測試刪除方法，不渲染整個元件
        $listComponent = Livewire::test(PermissionList::class);
        
        // 觸發刪除操作
        $listComponent->call('deletePermission', $this->testPermission->id);
        
        // 驗證刪除事件被觸發
        $listComponent->assertDispatched('confirm-permission-delete');
    }

    /**
     * 測試完整的刪除流程
     */
    public function test_complete_delete_workflow()
    {
        $this->actingAs($this->admin);

        // 步驟 1: 刪除模態對話框處理刪除確認
        $modalComponent = Livewire::test(PermissionDeleteModal::class);
        $modalComponent->dispatch('confirm-permission-delete', permissionId: $this->testPermission->id)
            ->assertSet('showModal', true)
            ->assertSet('canDelete', true)
            ->set('confirmationText', $this->testPermission->name)
            ->call('executeDelete')
            ->assertDispatched('permission-delete-confirmed');

        // 步驟 2: 驗證權限已被刪除
        $this->assertDatabaseMissing('permissions', [
            'id' => $this->testPermission->id
        ]);
    }

    /**
     * 測試刪除被阻止的情況
     */
    public function test_blocked_delete_workflow()
    {
        $this->actingAs($this->admin);

        // 將權限分配給角色，使其無法刪除
        $role = Role::factory()->create();
        $role->permissions()->attach($this->testPermission);

        // 觸發刪除流程
        $modalComponent = Livewire::test(PermissionDeleteModal::class);
        $modalComponent->dispatch('confirm-permission-delete', permissionId: $this->testPermission->id)
            ->assertSet('showModal', true)
            ->assertSet('hasBlockingIssues', true)
            ->assertSet('canDelete', false);

        // 驗證權限未被刪除
        $this->assertDatabaseHas('permissions', [
            'id' => $this->testPermission->id
        ]);
    }

    /**
     * 測試權限檢查
     */
    public function test_permission_check_in_delete_workflow()
    {
        // 建立沒有刪除權限的使用者
        $user = User::factory()->create();
        $role = Role::factory()->create(['name' => 'viewer']);
        $user->roles()->attach($role);

        $this->actingAs($user);

        // 嘗試直接開啟刪除模態對話框
        $modalComponent = Livewire::test(PermissionDeleteModal::class);
        $modalComponent->dispatch('confirm-permission-delete', permissionId: $this->testPermission->id)
            ->assertSet('showModal', false)
            ->assertDispatched('show-toast');
    }
}