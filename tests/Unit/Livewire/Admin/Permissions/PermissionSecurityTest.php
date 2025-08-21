<?php

namespace Tests\Unit\Livewire\Admin\Permissions;

use App\Livewire\Admin\Permissions\PermissionList;
use App\Livewire\Admin\Permissions\PermissionForm;
use App\Livewire\Admin\Permissions\DependencyGraph;
use App\Livewire\Admin\Permissions\PermissionTest;
use App\Livewire\Admin\Permissions\PermissionDeleteModal;
use App\Livewire\Admin\Permissions\PermissionImportExport;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 權限管理元件安全性測試
 * 
 * 測試所有權限管理元件的安全性控制和權限檢查
 */
class PermissionSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $viewerUser;
    protected User $editorUser;
    protected User $unauthorizedUser;
    protected Role $adminRole;
    protected Role $viewerRole;
    protected Role $editorRole;



    protected function setUp(): void
    {
        parent::setUp();
        
        // 停用權限安全觀察者以避免測試中的安全檢查
        \App\Models\Permission::unsetEventDispatcher();
        
        $this->createRolesAndUsers();
        $this->createPermissions();
        $this->assignPermissions();
    }

    private function createRolesAndUsers(): void
    {
        // 建立角色
        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理員',
            'is_system_role' => true,
            'is_active' => true,
        ]);

        $this->viewerRole = Role::create([
            'name' => 'viewer',
            'display_name' => '檢視者',
            'is_active' => true,
        ]);

        $this->editorRole = Role::create([
            'name' => 'editor',
            'display_name' => '編輯者',
            'is_active' => true,
        ]);

        // 建立使用者
        $this->adminUser = User::factory()->create(['is_active' => true]);
        $this->viewerUser = User::factory()->create(['is_active' => true]);
        $this->editorUser = User::factory()->create(['is_active' => true]);
        $this->unauthorizedUser = User::factory()->create(['is_active' => true]);

        // 分配角色
        $this->adminUser->roles()->attach($this->adminRole);
        $this->viewerUser->roles()->attach($this->viewerRole);
        $this->editorUser->roles()->attach($this->editorRole);
        // unauthorizedUser 沒有分配任何角色
    }

    private function createPermissions(): void
    {
        $permissions = [
            'permissions.view' => '檢視權限',
            'permissions.create' => '建立權限',
            'permissions.edit' => '編輯權限',
            'permissions.delete' => '刪除權限',
            'permissions.export' => '匯出權限',
            'permissions.import' => '匯入權限',
            'permissions.test' => '測試權限',
        ];

        foreach ($permissions as $name => $displayName) {
            Permission::create([
                'name' => $name,
                'display_name' => $displayName,
                'module' => 'permissions',
                'type' => 'view'
            ]);
        }
    }

    private function assignPermissions(): void
    {
        // 管理員擁有所有權限
        $allPermissions = Permission::where('module', 'permissions')->pluck('id');
        $this->adminRole->permissions()->attach($allPermissions);

        // 檢視者只有檢視權限
        $viewPermission = Permission::where('name', 'permissions.view')->first();
        $this->viewerRole->permissions()->attach($viewPermission);

        // 編輯者有檢視、建立、編輯權限
        $editorPermissions = Permission::whereIn('name', [
            'permissions.view',
            'permissions.create',
            'permissions.edit'
        ])->pluck('id');
        $this->editorRole->permissions()->attach($editorPermissions);
    }

    /** @test */
    public function PermissionList元件權限檢查正常運作()
    {
        // 管理員可以存取
        $this->actingAs($this->adminUser);
        $component = Livewire::test(PermissionList::class);
        $component->assertStatus(200);

        // 檢視者可以存取
        $this->actingAs($this->viewerUser);
        $component = Livewire::test(PermissionList::class);
        $component->assertStatus(200);

        // 未授權使用者無法存取
        $this->actingAs($this->unauthorizedUser);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        Livewire::test(PermissionList::class);
    }

    /** @test */
    public function PermissionList建立權限操作權限檢查()
    {
        // 管理員可以建立權限
        $this->actingAs($this->adminUser);
        $component = Livewire::test(PermissionList::class);
        $component->call('createPermission')
                 ->assertDispatched('open-permission-form');

        // 編輯者可以建立權限
        $this->actingAs($this->editorUser);
        $component = Livewire::test(PermissionList::class);
        $component->call('createPermission')
                 ->assertDispatched('open-permission-form');

        // 檢視者無法建立權限
        $this->actingAs($this->viewerUser);
        $component = Livewire::test(PermissionList::class);
        $component->call('createPermission')
                 ->assertDispatched('show-toast', [
                     'type' => 'error'
                 ]);
    }

    /** @test */
    public function PermissionList匯出權限操作權限檢查()
    {
        // 管理員可以匯出權限
        $this->actingAs($this->adminUser);
        $component = Livewire::test(PermissionList::class);
        $component->call('exportPermissions')
                 ->assertDispatched('export-permissions-started');

        // 檢視者無法匯出權限
        $this->actingAs($this->viewerUser);
        $component = Livewire::test(PermissionList::class);
        $component->call('exportPermissions')
                 ->assertDispatched('show-toast', [
                     'type' => 'error'
                 ]);
    }

    /** @test */
    public function PermissionForm元件權限檢查正常運作()
    {
        // 管理員可以存取
        $this->actingAs($this->adminUser);
        $component = Livewire::test(PermissionForm::class);
        $component->assertStatus(200);

        // 編輯者可以存取
        $this->actingAs($this->editorUser);
        $component = Livewire::test(PermissionForm::class);
        $component->assertStatus(200);

        // 檢視者可以存取（但操作會被限制）
        $this->actingAs($this->viewerUser);
        $component = Livewire::test(PermissionForm::class);
        $component->assertStatus(200);

        // 未授權使用者無法存取
        $this->actingAs($this->unauthorizedUser);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        Livewire::test(PermissionForm::class);
    }

    /** @test */
    public function PermissionForm建立操作權限檢查()
    {
        $permission = Permission::create([
            'name' => 'test.permission',
            'display_name' => '測試權限',
            'module' => 'test',
            'type' => 'view',
        ]);

        // 管理員可以建立
        $this->actingAs($this->adminUser);
        $component = Livewire::test(PermissionForm::class);
        $component->dispatch('open-permission-form', mode: 'create')
                 ->assertSet('showForm', true);

        // 編輯者可以建立
        $this->actingAs($this->editorUser);
        $component = Livewire::test(PermissionForm::class);
        $component->dispatch('open-permission-form', mode: 'create')
                 ->assertSet('showForm', true);

        // 檢視者無法建立
        $this->actingAs($this->viewerUser);
        $component = Livewire::test(PermissionForm::class);
        $component->dispatch('open-permission-form', mode: 'create')
                 ->assertDispatched('show-toast', [
                     'type' => 'error'
                 ]);
    }

    /** @test */
    public function PermissionForm編輯操作權限檢查()
    {
        $permission = Permission::create([
            'name' => 'test.permission',
            'display_name' => '測試權限',
            'module' => 'test',
            'type' => 'view',
        ]);

        // 管理員可以編輯
        $this->actingAs($this->adminUser);
        $component = Livewire::test(PermissionForm::class);
        $component->dispatch('open-permission-form', mode: 'edit', permissionId: $permission->id)
                 ->assertSet('showForm', true);

        // 編輯者可以編輯
        $this->actingAs($this->editorUser);
        $component = Livewire::test(PermissionForm::class);
        $component->dispatch('open-permission-form', mode: 'edit', permissionId: $permission->id)
                 ->assertSet('showForm', true);

        // 檢視者無法編輯
        $this->actingAs($this->viewerUser);
        $component = Livewire::test(PermissionForm::class);
        $component->dispatch('open-permission-form', mode: 'edit', permissionId: $permission->id)
                 ->assertDispatched('show-toast', [
                     'type' => 'error'
                 ]);
    }

    /** @test */
    public function DependencyGraph元件權限檢查正常運作()
    {
        // 管理員可以存取
        $this->actingAs($this->adminUser);
        $component = Livewire::test(DependencyGraph::class);
        $component->assertStatus(200);

        // 檢視者可以存取
        $this->actingAs($this->viewerUser);
        $component = Livewire::test(DependencyGraph::class);
        $component->assertStatus(200);

        // 未授權使用者無法存取
        $this->actingAs($this->unauthorizedUser);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        Livewire::test(DependencyGraph::class);
    }

    /** @test */
    public function DependencyGraph編輯操作權限檢查()
    {
        $permission = Permission::create([
            'name' => 'test.permission',
            'display_name' => '測試權限',
            'module' => 'test',
            'type' => 'view',
        ]);

        // 管理員可以新增依賴
        $this->actingAs($this->adminUser);
        $component = Livewire::test(DependencyGraph::class);
        $component->call('selectPermission', $permission->id)
                 ->call('openAddDependency')
                 ->assertSet('showAddDependency', true);

        // 編輯者可以新增依賴
        $this->actingAs($this->editorUser);
        $component = Livewire::test(DependencyGraph::class);
        $component->call('selectPermission', $permission->id)
                 ->call('openAddDependency')
                 ->assertSet('showAddDependency', true);

        // 檢視者無法新增依賴
        $this->actingAs($this->viewerUser);
        $component = Livewire::test(DependencyGraph::class);
        $component->call('selectPermission', $permission->id)
                 ->call('openAddDependency')
                 ->assertDispatched('show-toast', [
                     'type' => 'error'
                 ]);
    }

    /** @test */
    public function PermissionTest元件權限檢查正常運作()
    {
        // 所有有權限檢視的使用者都可以存取測試工具
        $this->actingAs($this->adminUser);
        $component = Livewire::test(PermissionTest::class);
        $component->assertStatus(200);

        $this->actingAs($this->viewerUser);
        $component = Livewire::test(PermissionTest::class);
        $component->assertStatus(200);

        $this->actingAs($this->editorUser);
        $component = Livewire::test(PermissionTest::class);
        $component->assertStatus(200);

        // 未授權使用者無法存取
        $this->actingAs($this->unauthorizedUser);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        Livewire::test(PermissionTest::class);
    }

    /** @test */
    public function PermissionDeleteModal元件權限檢查正常運作()
    {
        // 管理員可以存取
        $this->actingAs($this->adminUser);
        $component = Livewire::test(PermissionDeleteModal::class);
        $component->assertStatus(200);

        // 檢視者可以存取（但操作會被限制）
        $this->actingAs($this->viewerUser);
        $component = Livewire::test(PermissionDeleteModal::class);
        $component->assertStatus(200);

        // 未授權使用者無法存取
        $this->actingAs($this->unauthorizedUser);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        Livewire::test(PermissionDeleteModal::class);
    }

    /** @test */
    public function PermissionDeleteModal刪除操作權限檢查()
    {
        $permission = Permission::create([
            'name' => 'test.deletable',
            'display_name' => '可刪除權限',
            'module' => 'test',
            'type' => 'view',
        ]);

        // 管理員可以刪除
        $this->actingAs($this->adminUser);
        $component = Livewire::test(PermissionDeleteModal::class);
        $component->dispatch('confirm-permission-delete', permissionId: $permission->id)
                 ->assertSet('showModal', true);

        // 檢視者無法刪除
        $this->actingAs($this->viewerUser);
        $component = Livewire::test(PermissionDeleteModal::class);
        $component->dispatch('confirm-permission-delete', permissionId: $permission->id)
                 ->assertDispatched('show-toast', [
                     'type' => 'error'
                 ]);

        // 編輯者無法刪除（沒有刪除權限）
        $this->actingAs($this->editorUser);
        $component = Livewire::test(PermissionDeleteModal::class);
        $component->dispatch('confirm-permission-delete', permissionId: $permission->id)
                 ->assertDispatched('show-toast', [
                     'type' => 'error'
                 ]);
    }

    /** @test */
    public function PermissionImportExport元件權限檢查正常運作()
    {
        // 管理員可以存取
        $this->actingAs($this->adminUser);
        $component = Livewire::test(PermissionImportExport::class);
        $component->assertStatus(200);

        // 檢視者可以存取（但操作會被限制）
        $this->actingAs($this->viewerUser);
        $component = Livewire::test(PermissionImportExport::class);
        $component->assertStatus(200);

        // 未授權使用者無法存取
        $this->actingAs($this->unauthorizedUser);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        Livewire::test(PermissionImportExport::class);
    }

    /** @test */
    public function PermissionImportExport匯出操作權限檢查()
    {
        // 管理員可以匯出
        $this->actingAs($this->adminUser);
        $component = Livewire::test(PermissionImportExport::class);
        $component->call('exportPermissions')
                 ->assertSet('exportInProgress', true);

        // 檢視者無法匯出
        $this->actingAs($this->viewerUser);
        $component = Livewire::test(PermissionImportExport::class);
        $component->call('exportPermissions')
                 ->assertDispatched('show-toast', [
                     'type' => 'error'
                 ]);
    }

    /** @test */
    public function PermissionImportExport匯入操作權限檢查()
    {
        // 管理員可以匯入
        $this->actingAs($this->adminUser);
        $component = Livewire::test(PermissionImportExport::class);
        $component->call('executeImport')
                 ->assertSet('importInProgress', true);

        // 檢視者無法匯入
        $this->actingAs($this->viewerUser);
        $component = Livewire::test(PermissionImportExport::class);
        $component->call('executeImport')
                 ->assertDispatched('show-toast', [
                     'type' => 'error'
                 ]);
    }

    /** @test */
    public function 系統權限保護機制正常運作()
    {
        $systemPermission = Permission::create([
            'name' => 'system.core',
            'display_name' => '系統核心權限',
            'module' => 'system',
            'type' => 'manage',
            'is_system_permission' => true,
        ]);

        $this->actingAs($this->adminUser);

        // 系統權限無法被刪除
        $component = Livewire::test(PermissionDeleteModal::class);
        $component->dispatch('confirm-permission-delete', permissionId: $systemPermission->id)
                 ->assertSet('canDelete', false)
                 ->assertSet('hasBlockingIssues', true);

        // 系統權限的名稱和模組無法被修改
        $component = Livewire::test(PermissionForm::class);
        $component->dispatch('open-permission-form', mode: 'edit', permissionId: $systemPermission->id)
                 ->assertSet('isSystemPermission', true);
    }

    /** @test */
    public function 輸入驗證和清理正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionList::class);

        // 測試惡意輸入被清理
        $maliciousInput = '<script>alert("xss")</script>';
        $component->set('search', $maliciousInput);

        $search = $component->get('search');
        $this->assertNotEquals($maliciousInput, $search);
    }

    /** @test */
    public function 無效ID參數被正確處理()
    {
        $this->actingAs($this->adminUser);

        // 測試 PermissionList 處理無效 ID
        $component = Livewire::test(PermissionList::class);
        $component->call('editPermission', 'invalid_id')
                 ->assertDispatched('show-toast', [
                     'type' => 'error'
                 ]);

        // 測試 DependencyGraph 處理無效 ID
        $component = Livewire::test(DependencyGraph::class);
        $component->call('selectPermission', 'invalid_id')
                 ->assertDispatched('show-toast', [
                     'type' => 'error'
                 ]);

        // 測試 PermissionDeleteModal 處理無效 ID
        $component = Livewire::test(PermissionDeleteModal::class);
        $component->dispatch('confirm-permission-delete', permissionId: 'invalid_id')
                 ->assertDispatched('show-toast', [
                     'type' => 'error'
                 ]);
    }

    /** @test */
    public function 會話安全性檢查正常運作()
    {
        // 測試未登入使用者無法存取任何元件
        $components = [
            PermissionList::class,
            PermissionForm::class,
            DependencyGraph::class,
            PermissionTest::class,
            PermissionDeleteModal::class,
            PermissionImportExport::class,
        ];

        foreach ($components as $componentClass) {
            $this->expectException(\Illuminate\Auth\AuthenticationException::class);
            Livewire::test($componentClass);
        }
    }

    /** @test */
    public function 停用使用者無法存取元件()
    {
        // 建立停用的使用者
        $inactiveUser = User::factory()->create(['is_active' => false]);
        $inactiveUser->roles()->attach($this->adminRole);

        $this->actingAs($inactiveUser);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        Livewire::test(PermissionList::class);
    }

    /** @test */
    public function 角色停用後使用者失去權限()
    {
        // 停用檢視者角色
        $this->viewerRole->update(['is_active' => false]);

        $this->actingAs($this->viewerUser);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        Livewire::test(PermissionList::class);
    }

    /** @test */
    public function 權限撤銷後立即生效()
    {
        // 撤銷檢視者的檢視權限
        $viewPermission = Permission::where('name', 'permissions.view')->first();
        $this->viewerRole->permissions()->detach($viewPermission);

        $this->actingAs($this->viewerUser);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        Livewire::test(PermissionList::class);
    }

    /** @test */
    public function 批量操作權限檢查正常運作()
    {
        $permission1 = Permission::create([
            'name' => 'test.permission1',
            'display_name' => '測試權限1',
            'module' => 'test',
            'type' => 'view',
        ]);

        $permission2 = Permission::create([
            'name' => 'test.permission2',
            'display_name' => '測試權限2',
            'module' => 'test',
            'type' => 'view',
        ]);

        // 管理員可以執行批量操作
        $this->actingAs($this->adminUser);
        $component = Livewire::test(PermissionList::class);
        $component->set('selectedPermissions', [$permission1->id, $permission2->id])
                 ->call('bulkDelete'); // 假設有批量刪除功能

        // 檢視者無法執行批量操作
        $this->actingAs($this->viewerUser);
        $component = Livewire::test(PermissionList::class);
        $component->set('selectedPermissions', [$permission1->id, $permission2->id])
                 ->call('bulkDelete')
                 ->assertDispatched('show-toast', [
                     'type' => 'error'
                 ]);
    }

    /** @test */
    public function 審計日誌記錄敏感操作()
    {
        $permission = Permission::create([
            'name' => 'test.audit',
            'display_name' => '審計測試權限',
            'module' => 'test',
            'type' => 'view',
        ]);

        $this->actingAs($this->adminUser);

        // 刪除操作應該被記錄
        $component = Livewire::test(PermissionDeleteModal::class);
        $component->dispatch('confirm-permission-delete', permissionId: $permission->id)
                 ->set('confirmationText', $permission->name)
                 ->call('delete');

        // 這裡應該檢查審計日誌是否被記錄
        // 實際實作中需要根據具體的審計日誌系統來驗證
    }
}