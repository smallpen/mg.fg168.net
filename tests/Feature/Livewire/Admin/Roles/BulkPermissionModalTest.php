<?php

namespace Tests\Feature\Livewire\Admin\Roles;

use App\Livewire\Admin\Roles\BulkPermissionModal;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * BulkPermissionModal 元件功能測試
 * 
 * 測試批量權限設定模態的完整功能
 */
class BulkPermissionModalTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Role $adminRole;
    protected array $testRoles;
    protected array $testPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立管理員角色和權限
        $this->adminRole = Role::factory()->create(['name' => 'admin']);
        $editPermission = Permission::factory()->create(['name' => 'roles.edit']);
        $this->adminRole->permissions()->attach($editPermission);
        
        // 建立管理員使用者
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($this->adminRole);
        
        // 建立測試角色
        $this->testRoles = [
            Role::factory()->create(['name' => 'editor', 'display_name' => '編輯者']),
            Role::factory()->create(['name' => 'viewer', 'display_name' => '檢視者']),
            Role::factory()->create(['name' => 'manager', 'display_name' => '管理者']),
        ];
        
        // 建立測試權限（按模組分組）
        $this->testPermissions = [
            'users' => [
                Permission::factory()->create(['name' => 'users.view', 'display_name' => '檢視使用者', 'module' => 'users']),
                Permission::factory()->create(['name' => 'users.create', 'display_name' => '建立使用者', 'module' => 'users']),
                Permission::factory()->create(['name' => 'users.edit', 'display_name' => '編輯使用者', 'module' => 'users']),
            ],
            'posts' => [
                Permission::factory()->create(['name' => 'posts.view', 'display_name' => '檢視文章', 'module' => 'posts']),
                Permission::factory()->create(['name' => 'posts.create', 'display_name' => '建立文章', 'module' => 'posts']),
            ],
        ];
    }

    /** @test */
    public function 元件初始狀態正確()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(BulkPermissionModal::class);

        $component->assertSet('showModal', false)
                 ->assertSet('selectedRoleIds', [])
                 ->assertSet('selectedPermissions', [])
                 ->assertSet('selectedModule', 'all')
                 ->assertSet('operationType', 'add')
                 ->assertSet('showResults', false);
    }

    /** @test */
    public function 可以正確開啟模態()
    {
        $this->actingAs($this->admin);

        $roleIds = collect($this->testRoles)->pluck('id')->toArray();

        $component = Livewire::test(BulkPermissionModal::class);

        $component->dispatch('open-bulk-permission-modal', roleIds: $roleIds)
                 ->assertSet('showModal', true)
                 ->assertSet('selectedRoleIds', $roleIds)
                 ->assertCount('selectedRoles', 3);
    }

    /** @test */
    public function 可以正確關閉模態()
    {
        $this->actingAs($this->admin);

        $roleIds = collect($this->testRoles)->pluck('id')->toArray();

        $component = Livewire::test(BulkPermissionModal::class);

        $component->dispatch('open-bulk-permission-modal', roleIds: $roleIds)
                 ->call('closeModal')
                 ->assertSet('showModal', false)
                 ->assertSet('selectedPermissions', [])
                 ->assertSet('selectedModule', 'all')
                 ->assertSet('operationType', 'add');
    }

    /** @test */
    public function 模組篩選功能正常運作()
    {
        $this->actingAs($this->admin);

        $roleIds = collect($this->testRoles)->pluck('id')->toArray();

        $component = Livewire::test(BulkPermissionModal::class);

        $component->dispatch('open-bulk-permission-modal', roleIds: $roleIds)
                 ->set('selectedModule', 'users')
                 ->assertSet('selectedPermissions', []); // 切換模組時應重置權限選擇

        // 檢查分組權限是否正確篩選
        $groupedPermissions = $component->get('groupedPermissions');
        $this->assertTrue($groupedPermissions->has('users'));
        $this->assertFalse($groupedPermissions->has('posts'));
    }

    /** @test */
    public function 全選模組權限功能正常()
    {
        $this->actingAs($this->admin);

        $roleIds = collect($this->testRoles)->pluck('id')->toArray();

        $component = Livewire::test(BulkPermissionModal::class);

        $component->dispatch('open-bulk-permission-modal', roleIds: $roleIds)
                 ->set('selectedModule', 'users')
                 ->call('selectAllModulePermissions');

        $userPermissionIds = collect($this->testPermissions['users'])->pluck('id')->toArray();
        $selectedPermissions = $component->get('selectedPermissions');
        
        foreach ($userPermissionIds as $permissionId) {
            $this->assertContains($permissionId, $selectedPermissions);
        }
    }

    /** @test */
    public function 清除模組權限選擇功能正常()
    {
        $this->actingAs($this->admin);

        $roleIds = collect($this->testRoles)->pluck('id')->toArray();

        $component = Livewire::test(BulkPermissionModal::class);

        // 先選擇一些權限
        $userPermissionIds = collect($this->testPermissions['users'])->pluck('id')->toArray();
        
        $component->dispatch('open-bulk-permission-modal', roleIds: $roleIds)
                 ->set('selectedModule', 'users')
                 ->set('selectedPermissions', $userPermissionIds)
                 ->call('clearModulePermissions')
                 ->assertSet('selectedPermissions', []);
    }

    /** @test */
    public function 權限切換功能正常()
    {
        $this->actingAs($this->admin);

        $roleIds = collect($this->testRoles)->pluck('id')->toArray();
        $permissionId = $this->testPermissions['users'][0]->id;

        $component = Livewire::test(BulkPermissionModal::class);

        $component->dispatch('open-bulk-permission-modal', roleIds: $roleIds)
                 ->call('togglePermission', $permissionId)
                 ->assertContains('selectedPermissions', $permissionId);

        // 再次切換應該移除
        $component->call('togglePermission', $permissionId)
                 ->assertNotContains('selectedPermissions', $permissionId);
    }

    /** @test */
    public function 批量新增權限操作成功()
    {
        $this->actingAs($this->admin);

        $roleIds = collect($this->testRoles)->pluck('id')->toArray();
        $permissionIds = collect($this->testPermissions['users'])->pluck('id')->toArray();

        $component = Livewire::test(BulkPermissionModal::class);

        $component->dispatch('open-bulk-permission-modal', roleIds: $roleIds)
                 ->set('selectedPermissions', $permissionIds)
                 ->set('operationType', 'add')
                 ->call('executeBulkPermissionOperation')
                 ->assertSet('showResults', true)
                 ->assertDispatched('bulk-permissions-updated');

        // 驗證權限是否正確新增
        foreach ($this->testRoles as $role) {
            $role->refresh();
            foreach ($this->testPermissions['users'] as $permission) {
                $this->assertTrue($role->permissions->contains($permission));
            }
        }
    }

    /** @test */
    public function 批量移除權限操作成功()
    {
        $this->actingAs($this->admin);

        // 先為角色新增權限
        foreach ($this->testRoles as $role) {
            $role->permissions()->attach(collect($this->testPermissions['users'])->pluck('id'));
        }

        $roleIds = collect($this->testRoles)->pluck('id')->toArray();
        $permissionIds = collect($this->testPermissions['users'])->pluck('id')->toArray();

        $component = Livewire::test(BulkPermissionModal::class);

        $component->dispatch('open-bulk-permission-modal', roleIds: $roleIds)
                 ->set('selectedPermissions', $permissionIds)
                 ->set('operationType', 'remove')
                 ->call('executeBulkPermissionOperation')
                 ->assertSet('showResults', true)
                 ->assertDispatched('bulk-permissions-updated');

        // 驗證權限是否正確移除
        foreach ($this->testRoles as $role) {
            $role->refresh();
            foreach ($this->testPermissions['users'] as $permission) {
                $this->assertFalse($role->permissions->contains($permission));
            }
        }
    }

    /** @test */
    public function 批量替換權限操作成功()
    {
        $this->actingAs($this->admin);

        // 先為角色新增一些權限
        foreach ($this->testRoles as $role) {
            $role->permissions()->attach(collect($this->testPermissions['posts'])->pluck('id'));
        }

        $roleIds = collect($this->testRoles)->pluck('id')->toArray();
        $newPermissionIds = collect($this->testPermissions['users'])->pluck('id')->toArray();

        $component = Livewire::test(BulkPermissionModal::class);

        $component->dispatch('open-bulk-permission-modal', roleIds: $roleIds)
                 ->set('selectedPermissions', $newPermissionIds)
                 ->set('operationType', 'replace')
                 ->call('executeBulkPermissionOperation')
                 ->assertSet('showResults', true)
                 ->assertDispatched('bulk-permissions-updated');

        // 驗證權限是否正確替換
        foreach ($this->testRoles as $role) {
            $role->refresh();
            
            // 應該有新權限
            foreach ($this->testPermissions['users'] as $permission) {
                $this->assertTrue($role->permissions->contains($permission));
            }
            
            // 不應該有舊權限
            foreach ($this->testPermissions['posts'] as $permission) {
                $this->assertFalse($role->permissions->contains($permission));
            }
        }
    }

    /** @test */
    public function 系統角色替換權限受到保護()
    {
        $this->actingAs($this->admin);

        // 建立系統角色
        $systemRole = Role::factory()->create([
            'name' => 'system_admin',
            'display_name' => '系統管理員',
            'is_system_role' => true
        ]);

        $roleIds = [$systemRole->id];
        $permissionIds = collect($this->testPermissions['users'])->pluck('id')->toArray();

        $component = Livewire::test(BulkPermissionModal::class);

        $component->dispatch('open-bulk-permission-modal', roleIds: $roleIds)
                 ->set('selectedPermissions', $permissionIds)
                 ->set('operationType', 'replace')
                 ->call('executeBulkPermissionOperation')
                 ->assertSet('showResults', true);

        // 檢查操作結果中是否有錯誤
        $results = $component->get('operationResults');
        $this->assertFalse($results[0]['success']);
        $this->assertStringContainsString('系統角色', $results[0]['message']);
    }

    /** @test */
    public function 表單驗證正常運作()
    {
        $this->actingAs($this->admin);

        $roleIds = collect($this->testRoles)->pluck('id')->toArray();

        $component = Livewire::test(BulkPermissionModal::class);

        // 沒有選擇權限時應該驗證失敗
        $component->dispatch('open-bulk-permission-modal', roleIds: $roleIds)
                 ->set('selectedPermissions', [])
                 ->call('executeBulkPermissionOperation')
                 ->assertHasErrors('selectedPermissions');
    }

    /** @test */
    public function 權限檢查正常運作()
    {
        // 建立沒有權限的使用者
        $user = User::factory()->create();
        $this->actingAs($user);

        $roleIds = collect($this->testRoles)->pluck('id')->toArray();

        Livewire::test(BulkPermissionModal::class)
            ->dispatch('open-bulk-permission-modal', roleIds: $roleIds)
            ->assertForbidden();
    }

    /** @test */
    public function 選中權限統計資訊正確()
    {
        $this->actingAs($this->admin);

        $roleIds = collect($this->testRoles)->pluck('id')->toArray();
        $userPermissionIds = collect($this->testPermissions['users'])->pluck('id')->toArray();

        $component = Livewire::test(BulkPermissionModal::class);

        $component->dispatch('open-bulk-permission-modal', roleIds: $roleIds)
                 ->set('selectedPermissions', $userPermissionIds);

        $stats = $component->get('selectedPermissionsStats');
        
        $this->assertEquals(3, $stats['total_selected']);
        $this->assertArrayHasKey('modules', $stats);
        $this->assertEquals(3, $stats['modules']['users']['selected']);
        $this->assertEquals(3, $stats['modules']['users']['total']);
        $this->assertEquals(100, $stats['modules']['users']['percentage']);
    }

    /** @test */
    public function 可以重新執行操作()
    {
        $this->actingAs($this->admin);

        $roleIds = collect($this->testRoles)->pluck('id')->toArray();
        $permissionIds = collect($this->testPermissions['users'])->pluck('id')->toArray();

        $component = Livewire::test(BulkPermissionModal::class);

        $component->dispatch('open-bulk-permission-modal', roleIds: $roleIds)
                 ->set('selectedPermissions', $permissionIds)
                 ->set('operationType', 'add')
                 ->call('executeBulkPermissionOperation')
                 ->assertSet('showResults', true)
                 ->call('retryOperation')
                 ->assertSet('showResults', true);
    }

    /** @test */
    public function 操作類型選項正確顯示()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(BulkPermissionModal::class);

        $operationTypes = $component->get('operationTypes');
        
        $this->assertArrayHasKey('add', $operationTypes);
        $this->assertArrayHasKey('remove', $operationTypes);
        $this->assertArrayHasKey('replace', $operationTypes);
    }

    /** @test */
    public function 全選所有權限功能正常()
    {
        $this->actingAs($this->admin);

        $roleIds = collect($this->testRoles)->pluck('id')->toArray();

        $component = Livewire::test(BulkPermissionModal::class);

        $component->dispatch('open-bulk-permission-modal', roleIds: $roleIds)
                 ->set('selectedModule', 'all')
                 ->call('selectAllModulePermissions');

        $allPermissionIds = collect($this->testPermissions)->flatten()->pluck('id')->toArray();
        $selectedPermissions = $component->get('selectedPermissions');
        
        foreach ($allPermissionIds as $permissionId) {
            $this->assertContains($permissionId, $selectedPermissions);
        }
    }

    /** @test */
    public function 混合操作結果正確處理()
    {
        $this->actingAs($this->admin);

        // 建立一個會失敗的角色（例如系統角色）
        $systemRole = Role::factory()->create([
            'name' => 'system_role',
            'display_name' => '系統角色',
            'is_system_role' => true
        ]);

        $normalRole = $this->testRoles[0];
        $roleIds = [$systemRole->id, $normalRole->id];
        $permissionIds = collect($this->testPermissions['users'])->pluck('id')->toArray();

        $component = Livewire::test(BulkPermissionModal::class);

        $component->dispatch('open-bulk-permission-modal', roleIds: $roleIds)
                 ->set('selectedPermissions', $permissionIds)
                 ->set('operationType', 'replace')
                 ->call('executeBulkPermissionOperation')
                 ->assertSet('showResults', true);

        $results = $component->get('operationResults');
        
        // 應該有一個成功，一個失敗
        $successCount = collect($results)->where('success', true)->count();
        $failureCount = collect($results)->where('success', false)->count();
        
        $this->assertEquals(1, $successCount);
        $this->assertEquals(1, $failureCount);
    }

    /** @test */
    public function 權限選擇狀態檢查正確()
    {
        $this->actingAs($this->admin);

        $roleIds = collect($this->testRoles)->pluck('id')->toArray();
        $permissionId = $this->testPermissions['users'][0]->id;

        $component = Livewire::test(BulkPermissionModal::class);

        $component->dispatch('open-bulk-permission-modal', roleIds: $roleIds);

        // 初始狀態應該未選中
        $this->assertFalse($component->instance()->isPermissionSelected($permissionId));

        // 選中後應該返回 true
        $component->set('selectedPermissions', [$permissionId]);
        $this->assertTrue($component->instance()->isPermissionSelected($permissionId));
    }
}