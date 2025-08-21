<?php

namespace Tests\Feature\Livewire\Admin\Permissions;

use App\Livewire\Admin\Permissions\PermissionDeleteModal;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 權限刪除模態對話框測試
 */
class PermissionDeleteModalTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Permission $testPermission;
    protected Permission $systemPermission;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立權限刪除權限
        $deletePermission = Permission::factory()->create([
            'name' => 'permissions.delete',
            'display_name' => '刪除權限',
            'description' => '刪除權限的權限',
            'module' => 'permissions',
            'type' => 'delete'
        ]);

        // 建立測試管理員
        $this->admin = User::factory()->create();
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $adminRole->permissions()->attach($deletePermission);
        $this->admin->roles()->attach($adminRole);

        // 建立測試權限
        $this->testPermission = Permission::factory()->create([
            'name' => 'test.permission',
            'display_name' => '測試權限',
            'description' => '這是一個測試權限',
            'module' => 'test',
            'type' => 'view'
        ]);

        // 建立系統權限
        $this->systemPermission = Permission::factory()->create([
            'name' => 'system.core',
            'display_name' => '系統核心權限',
            'description' => '系統核心權限',
            'module' => 'system',
            'type' => 'manage'
        ]);
    }

    /**
     * 測試開啟刪除確認對話框
     */
    public function test_opens_delete_confirmation_modal()
    {
        $this->actingAs($this->admin);

        Livewire::test(PermissionDeleteModal::class)
            ->dispatch('confirm-permission-delete', permissionId: $this->testPermission->id)
            ->assertSet('showModal', true)
            ->assertSet('permissionId', $this->testPermission->id)
            ->assertSet('permission.id', $this->testPermission->id);
    }

    /**
     * 測試系統權限無法刪除
     */
    public function test_system_permission_cannot_be_deleted()
    {
        $this->actingAs($this->admin);

        Livewire::test(PermissionDeleteModal::class)
            ->dispatch('confirm-permission-delete', permissionId: $this->systemPermission->id)
            ->assertSet('showModal', true)
            ->assertSet('hasBlockingIssues', true)
            ->assertSet('canDelete', false);
    }

    /**
     * 測試被角色使用的權限無法刪除
     */
    public function test_permission_used_by_roles_cannot_be_deleted()
    {
        $this->actingAs($this->admin);

        // 將權限分配給角色
        $role = Role::factory()->create();
        $role->permissions()->attach($this->testPermission);

        Livewire::test(PermissionDeleteModal::class)
            ->dispatch('confirm-permission-delete', permissionId: $this->testPermission->id)
            ->assertSet('showModal', true)
            ->assertSet('hasBlockingIssues', true)
            ->assertSet('canDelete', false);
    }

    /**
     * 測試有依賴者的權限無法刪除
     */
    public function test_permission_with_dependents_cannot_be_deleted()
    {
        $this->actingAs($this->admin);

        // 建立依賴此權限的其他權限
        $dependentPermission = Permission::factory()->create([
            'name' => 'test.dependent',
            'display_name' => '依賴權限',
            'module' => 'test',
            'type' => 'edit'
        ]);
        
        $dependentPermission->dependencies()->attach($this->testPermission);

        Livewire::test(PermissionDeleteModal::class)
            ->dispatch('confirm-permission-delete', permissionId: $this->testPermission->id)
            ->assertSet('showModal', true)
            ->assertSet('hasBlockingIssues', true)
            ->assertSet('canDelete', false);
    }

    /**
     * 測試成功刪除權限
     */
    public function test_successfully_deletes_permission()
    {
        $this->actingAs($this->admin);

        Livewire::test(PermissionDeleteModal::class)
            ->dispatch('confirm-permission-delete', permissionId: $this->testPermission->id)
            ->assertSet('showModal', true)
            ->assertSet('canDelete', true)
            ->set('confirmationText', $this->testPermission->name)
            ->call('executeDelete')
            ->assertDispatched('show-toast')
            ->assertDispatched('permission-delete-confirmed');

        // 驗證權限已被刪除
        $this->assertDatabaseMissing('permissions', [
            'id' => $this->testPermission->id
        ]);
    }

    /**
     * 測試確認文字不正確時無法刪除
     */
    public function test_cannot_delete_with_incorrect_confirmation_text()
    {
        $this->actingAs($this->admin);

        Livewire::test(PermissionDeleteModal::class)
            ->dispatch('confirm-permission-delete', permissionId: $this->testPermission->id)
            ->set('confirmationText', 'wrong-name')
            ->call('executeDelete')
            ->assertHasErrors('confirmationText');

        // 驗證權限未被刪除
        $this->assertDatabaseHas('permissions', [
            'id' => $this->testPermission->id
        ]);
    }

    /**
     * 測試沒有權限時無法開啟刪除對話框
     */
    public function test_cannot_open_modal_without_permission()
    {
        // 建立沒有刪除權限的使用者
        $user = User::factory()->create();
        $role = Role::factory()->create(['name' => 'viewer']);
        $user->roles()->attach($role);

        $this->actingAs($user);

        Livewire::test(PermissionDeleteModal::class)
            ->dispatch('confirm-permission-delete', permissionId: $this->testPermission->id)
            ->assertSet('showModal', false)
            ->assertDispatched('show-toast');
    }

    /**
     * 測試關閉對話框
     */
    public function test_closes_modal()
    {
        $this->actingAs($this->admin);

        Livewire::test(PermissionDeleteModal::class)
            ->dispatch('confirm-permission-delete', permissionId: $this->testPermission->id)
            ->assertSet('showModal', true)
            ->call('closeModal')
            ->assertSet('showModal', false)
            ->assertSet('confirmationText', '')
            ->assertSet('permission', null);
    }

    /**
     * 測試權限不存在時的處理
     */
    public function test_handles_non_existent_permission()
    {
        $this->actingAs($this->admin);

        Livewire::test(PermissionDeleteModal::class)
            ->dispatch('confirm-permission-delete', permissionId: 99999)
            ->assertSet('showModal', false)
            ->assertDispatched('show-toast');
    }

    /**
     * 測試刪除檢查結果
     */
    public function test_delete_checks_are_performed()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(PermissionDeleteModal::class)
            ->dispatch('confirm-permission-delete', permissionId: $this->testPermission->id);

        // 檢查是否執行了所有必要的檢查
        $deleteChecks = $component->get('deleteChecks');
        
        $this->assertArrayHasKey('system_permission', $deleteChecks);
        $this->assertArrayHasKey('roles', $deleteChecks);
        $this->assertArrayHasKey('dependencies', $deleteChecks);
        $this->assertArrayHasKey('dependents', $deleteChecks);
    }

    /**
     * 測試計算屬性
     */
    public function test_computed_properties()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(PermissionDeleteModal::class)
            ->dispatch('confirm-permission-delete', permissionId: $this->testPermission->id);

        // 測試確認標籤
        $confirmLabel = $component->get('confirmLabel');
        $this->assertStringContainsString($this->testPermission->name, $confirmLabel);

        // 測試確認按鈕可用性
        $component->set('confirmationText', $this->testPermission->name);
        $this->assertTrue($component->get('canConfirm'));

        $component->set('confirmationText', 'wrong-name');
        $this->assertFalse($component->get('canConfirm'));
    }
}