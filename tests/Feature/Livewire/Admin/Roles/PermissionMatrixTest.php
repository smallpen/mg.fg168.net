<?php

namespace Tests\Feature\Livewire\Admin\Roles;

use App\Livewire\Admin\Roles\PermissionMatrix;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * PermissionMatrix 元件測試
 * 
 * 測試權限矩陣的渲染、階層式顯示、批量管理和即時預覽功能
 */
class PermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試角色
        $this->adminRole = Role::factory()->create(['name' => 'admin']);
        $this->editorRole = Role::factory()->create(['name' => 'editor']);
        
        // 建立測試權限（階層式）
        $this->userPermissions = [
            Permission::factory()->create(['name' => 'users.view', 'display_name' => '檢視使用者', 'module' => 'users']),
            Permission::factory()->create(['name' => 'users.create', 'display_name' => '建立使用者', 'module' => 'users']),
            Permission::factory()->create(['name' => 'users.edit', 'display_name' => '編輯使用者', 'module' => 'users']),
            Permission::factory()->create(['name' => 'users.delete', 'display_name' => '刪除使用者', 'module' => 'users']),
        ];
        
        $this->rolePermissions = [
            Permission::factory()->create(['name' => 'roles.view', 'display_name' => '檢視角色', 'module' => 'roles']),
            Permission::factory()->create(['name' => 'roles.create', 'display_name' => '建立角色', 'module' => 'roles']),
            Permission::factory()->create(['name' => 'roles.edit', 'display_name' => '編輯角色', 'module' => 'roles']),
        ];
        
        // 建立管理員使用者
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($this->adminRole);
    }

    /**
     * 測試元件能正確渲染
     */
    public function test_component_renders_correctly()
    {
        $this->actingAs($this->admin);

        Livewire::test(PermissionMatrix::class)
            ->assertStatus(200)
            ->assertSee('權限矩陣')
            ->assertSee('角色')
            ->assertSee('權限');
    }

    /**
     * 測試權限矩陣表格顯示
     */
    public function test_permission_matrix_display()
    {
        $this->actingAs($this->admin);

        Livewire::test(PermissionMatrix::class)
            ->assertSee($this->adminRole->display_name)
            ->assertSee($this->editorRole->display_name)
            ->assertSee('使用者管理')
            ->assertSee('角色管理')
            ->assertSee('檢視使用者')
            ->assertSee('建立使用者');
    }

    /**
     * 測試權限階層式顯示
     */
    public function test_hierarchical_permission_display()
    {
        $this->actingAs($this->admin);

        Livewire::test(PermissionMatrix::class)
            ->assertSeeInOrder([
                '使用者管理',
                '檢視使用者',
                '建立使用者',
                '編輯使用者',
                '刪除使用者'
            ]);
    }

    /**
     * 測試單一權限切換
     */
    public function test_toggle_single_permission()
    {
        $this->actingAs($this->admin);

        $permission = $this->userPermissions[0];

        Livewire::test(PermissionMatrix::class)
            ->call('togglePermission', $this->editorRole->id, $permission->id)
            ->assertDispatched('permission-updated');

        $this->assertTrue($this->editorRole->permissions->contains($permission));
    }

    /**
     * 測試批量權限指派
     */
    public function test_bulk_permission_assignment()
    {
        $this->actingAs($this->admin);

        $permissionIds = collect($this->userPermissions)->pluck('id')->toArray();

        Livewire::test(PermissionMatrix::class)
            ->call('assignPermissionsToRole', $this->editorRole->id, $permissionIds)
            ->assertDispatched('permissions-bulk-assigned');

        foreach ($this->userPermissions as $permission) {
            $this->assertTrue($this->editorRole->fresh()->permissions->contains($permission));
        }
    }

    /**
     * 測試批量權限移除
     */
    public function test_bulk_permission_removal()
    {
        $this->actingAs($this->admin);

        // 先指派權限
        $this->editorRole->permissions()->attach($this->userPermissions);

        $permissionIds = collect($this->userPermissions)->pluck('id')->toArray();

        Livewire::test(PermissionMatrix::class)
            ->call('removePermissionsFromRole', $this->editorRole->id, $permissionIds)
            ->assertDispatched('permissions-bulk-removed');

        foreach ($this->userPermissions as $permission) {
            $this->assertFalse($this->editorRole->fresh()->permissions->contains($permission));
        }
    }

    /**
     * 測試模組權限全選
     */
    public function test_select_all_module_permissions()
    {
        $this->actingAs($this->admin);

        Livewire::test(PermissionMatrix::class)
            ->call('selectAllModulePermissions', $this->editorRole->id, 'users')
            ->assertDispatched('module-permissions-assigned');

        foreach ($this->userPermissions as $permission) {
            $this->assertTrue($this->editorRole->fresh()->permissions->contains($permission));
        }
    }

    /**
     * 測試模組權限全部取消
     */
    public function test_deselect_all_module_permissions()
    {
        $this->actingAs($this->admin);

        // 先指派權限
        $this->editorRole->permissions()->attach($this->userPermissions);

        Livewire::test(PermissionMatrix::class)
            ->call('deselectAllModulePermissions', $this->editorRole->id, 'users')
            ->assertDispatched('module-permissions-removed');

        foreach ($this->userPermissions as $permission) {
            $this->assertFalse($this->editorRole->fresh()->permissions->contains($permission));
        }
    }

    /**
     * 測試角色複製權限
     */
    public function test_copy_role_permissions()
    {
        $this->actingAs($this->admin);

        // 為管理員角色指派權限
        $this->adminRole->permissions()->attach($this->userPermissions);

        Livewire::test(PermissionMatrix::class)
            ->call('copyRolePermissions', $this->adminRole->id, $this->editorRole->id)
            ->assertDispatched('permissions-copied');

        foreach ($this->userPermissions as $permission) {
            $this->assertTrue($this->editorRole->fresh()->permissions->contains($permission));
        }
    }

    /**
     * 測試權限變更即時預覽
     */
    public function test_permission_change_preview()
    {
        $this->actingAs($this->admin);

        $permission = $this->userPermissions[0];

        Livewire::test(PermissionMatrix::class)
            ->call('previewPermissionChange', $this->editorRole->id, $permission->id)
            ->assertSee('權限變更預覽')
            ->assertSee('將會獲得權限')
            ->assertSee($permission->display_name);
    }

    /**
     * 測試權限衝突檢查
     */
    public function test_permission_conflict_check()
    {
        $this->actingAs($this->admin);

        // 建立衝突的權限
        $conflictPermission = Permission::factory()->create([
            'name' => 'users.admin',
            'conflicts_with' => [$this->userPermissions[0]->id]
        ]);

        $this->editorRole->permissions()->attach($this->userPermissions[0]);

        Livewire::test(PermissionMatrix::class)
            ->call('togglePermission', $this->editorRole->id, $conflictPermission->id)
            ->assertSee('權限衝突警告')
            ->assertSee('此權限與現有權限衝突');
    }

    /**
     * 測試權限依賴檢查
     */
    public function test_permission_dependency_check()
    {
        $this->actingAs($this->admin);

        // 建立有依賴關係的權限
        $dependentPermission = Permission::factory()->create([
            'name' => 'users.advanced_edit',
            'depends_on' => [$this->userPermissions[0]->id] // 依賴檢視權限
        ]);

        Livewire::test(PermissionMatrix::class)
            ->call('togglePermission', $this->editorRole->id, $dependentPermission->id)
            ->assertSee('自動選擇相依權限')
            ->assertDispatched('dependent-permissions-added');

        $this->assertTrue($this->editorRole->fresh()->permissions->contains($this->userPermissions[0]));
        $this->assertTrue($this->editorRole->fresh()->permissions->contains($dependentPermission));
    }

    /**
     * 測試權限搜尋篩選
     */
    public function test_permission_search_filter()
    {
        $this->actingAs($this->admin);

        Livewire::test(PermissionMatrix::class)
            ->set('searchTerm', '使用者')
            ->assertSee('檢視使用者')
            ->assertSee('建立使用者')
            ->assertDontSee('檢視角色');
    }

    /**
     * 測試模組篩選
     */
    public function test_module_filter()
    {
        $this->actingAs($this->admin);

        Livewire::test(PermissionMatrix::class)
            ->set('moduleFilter', 'users')
            ->assertSee('檢視使用者')
            ->assertSee('建立使用者')
            ->assertDontSee('檢視角色');
    }

    /**
     * 測試權限統計顯示
     */
    public function test_permission_statistics()
    {
        $this->actingAs($this->admin);

        // 為角色指派一些權限
        $this->editorRole->permissions()->attach([$this->userPermissions[0]->id, $this->userPermissions[1]->id]);

        Livewire::test(PermissionMatrix::class)
            ->assertSee('2/' . count($this->userPermissions)); // 顯示已指派/總權限數
    }

    /**
     * 測試權限變更確認
     */
    public function test_permission_change_confirmation()
    {
        $this->actingAs($this->admin);

        $permission = $this->userPermissions[3]; // 刪除權限

        Livewire::test(PermissionMatrix::class)
            ->call('togglePermission', $this->editorRole->id, $permission->id)
            ->assertSee('確認權限變更')
            ->assertSee('此權限具有高風險')
            ->call('confirmPermissionChange')
            ->assertDispatched('permission-confirmed');
    }

    /**
     * 測試權限控制 - 無權限使用者
     */
    public function test_unauthorized_access()
    {
        $unauthorizedUser = User::factory()->create();
        $this->actingAs($unauthorizedUser);

        Livewire::test(PermissionMatrix::class)
            ->assertForbidden();
    }

    /**
     * 測試只讀模式
     */
    public function test_read_only_mode()
    {
        $readOnlyUser = User::factory()->create();
        $readOnlyRole = Role::factory()->create(['name' => 'viewer']);
        $readOnlyUser->roles()->attach($readOnlyRole);
        
        $this->actingAs($readOnlyUser);

        Livewire::test(PermissionMatrix::class)
            ->assertDontSee('checkbox')
            ->assertSee('權限矩陣（唯讀）');
    }

    /**
     * 測試批量操作確認
     */
    public function test_bulk_operation_confirmation()
    {
        $this->actingAs($this->admin);

        $permissionIds = collect($this->userPermissions)->pluck('id')->toArray();

        Livewire::test(PermissionMatrix::class)
            ->set('selectedPermissions', $permissionIds)
            ->call('bulkAssignPermissions', $this->editorRole->id)
            ->assertSee('確認批量指派')
            ->assertSee('將指派 ' . count($permissionIds) . ' 個權限')
            ->call('confirmBulkOperation')
            ->assertDispatched('bulk-operation-confirmed');
    }

    /**
     * 測試權限變更歷史記錄
     */
    public function test_permission_change_history()
    {
        $this->actingAs($this->admin);

        $permission = $this->userPermissions[0];

        Livewire::test(PermissionMatrix::class)
            ->call('togglePermission', $this->editorRole->id, $permission->id);

        // 檢查變更記錄是否被建立
        $this->assertDatabaseHas('permission_change_logs', [
            'role_id' => $this->editorRole->id,
            'permission_id' => $permission->id,
            'action' => 'assigned',
            'changed_by' => $this->admin->id
        ]);
    }

    /**
     * 測試匯出權限矩陣
     */
    public function test_export_permission_matrix()
    {
        $this->actingAs($this->admin);

        Livewire::test(PermissionMatrix::class)
            ->call('exportMatrix')
            ->assertDispatched('matrix-export-started');
    }
}