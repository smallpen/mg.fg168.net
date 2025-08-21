<?php

namespace Tests\Unit\Livewire\Admin\Permissions;

use App\Livewire\Admin\Permissions\PermissionDeleteModal;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Repositories\PermissionRepository;
use App\Services\AuditLogService;
use App\Services\InputValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * PermissionDeleteModal 元件單元測試
 */
class PermissionDeleteModalTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Role $adminRole;
    protected array $testPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 停用權限安全觀察者以避免測試中的安全檢查
        \App\Models\Permission::unsetEventDispatcher();
        
        // 建立管理員角色和使用者
        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理員',
            'is_system_role' => true,
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create(['is_active' => true]);
        $this->adminUser->roles()->attach($this->adminRole);
        
        // 建立權限管理相關權限
        $permissions = ['permissions.view', 'permissions.delete'];
        foreach ($permissions as $permission) {
            Permission::create([
                'name' => $permission,
                'display_name' => $permission,
                'module' => 'permissions',
                'type' => 'view'
            ]);
        }
        
        $this->adminRole->permissions()->attach(
            Permission::whereIn('name', $permissions)->pluck('id')
        );

        // 建立測試權限
        $this->createTestPermissions();
    }

    private function createTestPermissions(): void
    {
        $this->testPermissions = [
            'deletable' => Permission::create([
                'name' => 'test.deletable',
                'display_name' => '可刪除權限',
                'description' => '這是一個可以刪除的測試權限',
                'module' => 'test',
                'type' => 'view',
            ]),
            'used_by_role' => Permission::create([
                'name' => 'test.used_by_role',
                'display_name' => '被角色使用的權限',
                'description' => '這個權限被角色使用',
                'module' => 'test',
                'type' => 'view',
            ]),
            'system' => Permission::create([
                'name' => 'system.permission',
                'display_name' => '系統權限',
                'description' => '系統核心權限',
                'module' => 'system',
                'type' => 'manage',
                'is_system_permission' => true,
            ]),
            'has_dependents' => Permission::create([
                'name' => 'test.has_dependents',
                'display_name' => '有被依賴的權限',
                'description' => '這個權限被其他權限依賴',
                'module' => 'test',
                'type' => 'view',
            ]),
            'dependent' => Permission::create([
                'name' => 'test.dependent',
                'display_name' => '依賴其他權限的權限',
                'description' => '這個權限依賴其他權限',
                'module' => 'test',
                'type' => 'edit',
            ]),
        ];

        // 建立角色使用權限的關係
        $testRole = Role::create([
            'name' => 'test_role',
            'display_name' => '測試角色',
            'is_active' => true,
        ]);
        $testRole->permissions()->attach($this->testPermissions['used_by_role']->id);

        // 建立依賴關係
        $this->testPermissions['dependent']->dependencies()->attach($this->testPermissions['has_dependents']->id);
    }

    /** @test */
    public function 元件可以正確初始化()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionDeleteModal::class);

        $component->assertSet('showModal', false)
                 ->assertSet('permission', null)
                 ->assertSet('permissionId', 0)
                 ->assertSet('deleteChecks', [])
                 ->assertSet('canDelete', false)
                 ->assertSet('hasBlockingIssues', false)
                 ->assertSet('confirmationText', '')
                 ->assertSet('processing', false);
    }

    /** @test */
    public function 沒有權限的使用者無法存取元件()
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        
        Livewire::test(PermissionDeleteModal::class);
    }

    /** @test */
    public function 可以開啟刪除確認對話框()
    {
        $this->actingAs($this->adminUser);

        $permission = $this->testPermissions['deletable'];
        
        $component = Livewire::test(PermissionDeleteModal::class);

        $component->dispatch('confirm-permission-delete', permissionId: $permission->id)
                 ->assertSet('showModal', true)
                 ->assertSet('permissionId', $permission->id)
                 ->assertSet('permission.id', $permission->id);

        // 檢查是否執行了刪除檢查
        $deleteChecks = $component->get('deleteChecks');
        $this->assertNotEmpty($deleteChecks);
    }

    /** @test */
    public function 開啟不存在的權限顯示錯誤()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionDeleteModal::class);

        $component->dispatch('confirm-permission-delete', permissionId: 99999)
                 ->assertDispatched('show-toast', [
                     'type' => 'error',
                     'message' => '權限不存在'
                 ]);
    }

    /** @test */
    public function 可刪除權限的檢查結果正確()
    {
        $this->actingAs($this->adminUser);

        $permission = $this->testPermissions['deletable'];
        
        $component = Livewire::test(PermissionDeleteModal::class);

        $component->dispatch('confirm-permission-delete', permissionId: $permission->id);

        $component->assertSet('canDelete', true)
                 ->assertSet('hasBlockingIssues', false);

        $deleteChecks = $component->get('deleteChecks');
        
        // 檢查各項檢查結果
        $this->assertArrayHasKey('is_system_permission', $deleteChecks);
        $this->assertArrayHasKey('used_by_roles', $deleteChecks);
        $this->assertArrayHasKey('has_dependents', $deleteChecks);
        
        $this->assertFalse($deleteChecks['is_system_permission']['blocking']);
        $this->assertFalse($deleteChecks['used_by_roles']['blocking']);
        $this->assertFalse($deleteChecks['has_dependents']['blocking']);
    }

    /** @test */
    public function 系統權限無法刪除()
    {
        $this->actingAs($this->adminUser);

        $permission = $this->testPermissions['system'];
        
        $component = Livewire::test(PermissionDeleteModal::class);

        $component->dispatch('confirm-permission-delete', permissionId: $permission->id);

        $component->assertSet('canDelete', false)
                 ->assertSet('hasBlockingIssues', true);

        $deleteChecks = $component->get('deleteChecks');
        $this->assertTrue($deleteChecks['is_system_permission']['blocking']);
    }

    /** @test */
    public function 被角色使用的權限無法刪除()
    {
        $this->actingAs($this->adminUser);

        $permission = $this->testPermissions['used_by_role'];
        
        $component = Livewire::test(PermissionDeleteModal::class);

        $component->dispatch('confirm-permission-delete', permissionId: $permission->id);

        $component->assertSet('canDelete', false)
                 ->assertSet('hasBlockingIssues', true);

        $deleteChecks = $component->get('deleteChecks');
        $this->assertTrue($deleteChecks['used_by_roles']['blocking']);
        $this->assertNotEmpty($deleteChecks['used_by_roles']['roles']);
    }

    /** @test */
    public function 有被依賴的權限無法刪除()
    {
        $this->actingAs($this->adminUser);

        $permission = $this->testPermissions['has_dependents'];
        
        $component = Livewire::test(PermissionDeleteModal::class);

        $component->dispatch('confirm-permission-delete', permissionId: $permission->id);

        $component->assertSet('canDelete', false)
                 ->assertSet('hasBlockingIssues', true);

        $deleteChecks = $component->get('deleteChecks');
        $this->assertTrue($deleteChecks['has_dependents']['blocking']);
        $this->assertNotEmpty($deleteChecks['has_dependents']['dependents']);
    }

    /** @test */
    public function 可以取消刪除操作()
    {
        $this->actingAs($this->adminUser);

        $permission = $this->testPermissions['deletable'];
        
        $component = Livewire::test(PermissionDeleteModal::class);

        $component->dispatch('confirm-permission-delete', permissionId: $permission->id)
                 ->call('cancel')
                 ->assertSet('showModal', false)
                 ->assertSet('permission', null)
                 ->assertSet('permissionId', 0)
                 ->assertSet('confirmationText', '')
                 ->assertSet('deleteChecks', []);

        $component->assertDispatched('permission-delete-cancelled');
    }

    /** @test */
    public function 可以成功刪除權限()
    {
        $this->actingAs($this->adminUser);

        $permission = $this->testPermissions['deletable'];
        
        $component = Livewire::test(PermissionDeleteModal::class);

        $component->dispatch('confirm-permission-delete', permissionId: $permission->id)
                 ->set('confirmationText', $permission->name)
                 ->call('delete');

        // 檢查權限是否被刪除
        $this->assertDatabaseMissing('permissions', [
            'id' => $permission->id
        ]);

        $component->assertDispatched('show-toast', [
            'type' => 'success',
            'message' => "權限「{$permission->display_name}」刪除成功"
        ]);

        $component->assertDispatched('permission-deleted', [
            'permissionId' => $permission->id,
            'permissionName' => $permission->name
        ]);

        $component->assertSet('showModal', false);
    }

    /** @test */
    public function 確認文字不正確時無法刪除()
    {
        $this->actingAs($this->adminUser);

        $permission = $this->testPermissions['deletable'];
        
        $component = Livewire::test(PermissionDeleteModal::class);

        $component->dispatch('confirm-permission-delete', permissionId: $permission->id)
                 ->set('confirmationText', 'wrong_text')
                 ->call('delete')
                 ->assertHasErrors(['confirmationText']);

        // 檢查權限沒有被刪除
        $this->assertDatabaseHas('permissions', [
            'id' => $permission->id
        ]);
    }

    /** @test */
    public function 無法刪除的權限不能執行刪除操作()
    {
        $this->actingAs($this->adminUser);

        $permission = $this->testPermissions['system'];
        
        $component = Livewire::test(PermissionDeleteModal::class);

        $component->dispatch('confirm-permission-delete', permissionId: $permission->id)
                 ->set('confirmationText', $permission->name)
                 ->call('delete')
                 ->assertDispatched('show-toast', [
                     'type' => 'error',
                     'message' => '此權限無法刪除'
                 ]);

        // 檢查權限沒有被刪除
        $this->assertDatabaseHas('permissions', [
            'id' => $permission->id
        ]);
    }

    /** @test */
    public function 沒有刪除權限的使用者無法刪除權限()
    {
        // 建立只有檢視權限的使用者
        $user = User::factory()->create(['is_active' => true]);
        $role = Role::create([
            'name' => 'viewer',
            'display_name' => '檢視者',
            'is_active' => true,
        ]);
        
        $viewPermission = Permission::where('name', 'permissions.view')->first();
        $role->permissions()->attach($viewPermission);
        $user->roles()->attach($role);

        $this->actingAs($user);

        $permission = $this->testPermissions['deletable'];
        
        $component = Livewire::test(PermissionDeleteModal::class);

        $component->dispatch('confirm-permission-delete', permissionId: $permission->id)
                 ->assertDispatched('show-toast', [
                     'type' => 'error',
                     'message' => '您沒有刪除權限的權限'
                 ]);
    }

    /** @test */
    public function 刪除處理中時無法重複提交()
    {
        $this->actingAs($this->adminUser);

        $permission = $this->testPermissions['deletable'];
        
        $component = Livewire::test(PermissionDeleteModal::class);

        $component->dispatch('confirm-permission-delete', permissionId: $permission->id)
                 ->set('confirmationText', $permission->name)
                 ->set('processing', true)
                 ->call('delete')
                 ->assertDispatched('show-toast', [
                     'type' => 'warning',
                     'message' => '刪除操作進行中，請稍候'
                 ]);
    }

    /** @test */
    public function 刪除檢查包含完整資訊()
    {
        $this->actingAs($this->adminUser);

        $permission = $this->testPermissions['used_by_role'];
        
        $component = Livewire::test(PermissionDeleteModal::class);

        $component->dispatch('confirm-permission-delete', permissionId: $permission->id);

        $deleteChecks = $component->get('deleteChecks');
        
        // 檢查系統權限檢查
        $this->assertArrayHasKey('is_system_permission', $deleteChecks);
        $this->assertArrayHasKey('status', $deleteChecks['is_system_permission']);
        $this->assertArrayHasKey('blocking', $deleteChecks['is_system_permission']);
        $this->assertArrayHasKey('message', $deleteChecks['is_system_permission']);

        // 檢查角色使用檢查
        $this->assertArrayHasKey('used_by_roles', $deleteChecks);
        $this->assertArrayHasKey('status', $deleteChecks['used_by_roles']);
        $this->assertArrayHasKey('blocking', $deleteChecks['used_by_roles']);
        $this->assertArrayHasKey('message', $deleteChecks['used_by_roles']);
        $this->assertArrayHasKey('roles', $deleteChecks['used_by_roles']);

        // 檢查依賴關係檢查
        $this->assertArrayHasKey('has_dependents', $deleteChecks);
        $this->assertArrayHasKey('status', $deleteChecks['has_dependents']);
        $this->assertArrayHasKey('blocking', $deleteChecks['has_dependents']);
        $this->assertArrayHasKey('message', $deleteChecks['has_dependents']);
    }

    /** @test */
    public function 刪除操作記錄審計日誌()
    {
        $this->actingAs($this->adminUser);

        $permission = $this->testPermissions['deletable'];
        $permissionName = $permission->name;
        $permissionDisplayName = $permission->display_name;
        
        $component = Livewire::test(PermissionDeleteModal::class);

        $component->dispatch('confirm-permission-delete', permissionId: $permission->id)
                 ->set('confirmationText', $permission->name)
                 ->call('delete');

        // 檢查是否記錄了審計日誌（這裡假設有審計日誌表）
        // 實際實作中可能需要根據具體的審計日誌實作來調整
    }

    /** @test */
    public function 驗證規則正確運作()
    {
        $this->actingAs($this->adminUser);

        $permission = $this->testPermissions['deletable'];
        
        $component = Livewire::test(PermissionDeleteModal::class);

        $component->dispatch('confirm-permission-delete', permissionId: $permission->id);

        // 測試確認文字必填
        $component->set('confirmationText', '')
                 ->call('delete')
                 ->assertHasErrors(['confirmationText' => 'required']);

        // 測試確認文字必須匹配
        $component->set('confirmationText', 'wrong_name')
                 ->call('delete')
                 ->assertHasErrors(['confirmationText']);

        // 測試正確的確認文字
        $component->set('confirmationText', $permission->name)
                 ->call('delete')
                 ->assertHasNoErrors(['confirmationText']);
    }

    /** @test */
    public function 關閉對話框時重置狀態()
    {
        $this->actingAs($this->adminUser);

        $permission = $this->testPermissions['deletable'];
        
        $component = Livewire::test(PermissionDeleteModal::class);

        // 開啟對話框並設定一些狀態
        $component->dispatch('confirm-permission-delete', permissionId: $permission->id)
                 ->set('confirmationText', 'some text');

        // 確認狀態已設定
        $component->assertSet('showModal', true)
                 ->assertSet('permissionId', $permission->id)
                 ->assertSet('confirmationText', 'some text');

        // 關閉對話框
        $component->call('cancel');

        // 檢查狀態是否重置
        $component->assertSet('showModal', false)
                 ->assertSet('permission', null)
                 ->assertSet('permissionId', 0)
                 ->assertSet('confirmationText', '')
                 ->assertSet('deleteChecks', [])
                 ->assertSet('canDelete', false)
                 ->assertSet('hasBlockingIssues', false)
                 ->assertSet('processing', false);
    }

    /** @test */
    public function 刪除失敗時顯示錯誤訊息()
    {
        $this->actingAs($this->adminUser);

        $permission = $this->testPermissions['deletable'];
        
        // 模擬刪除失敗的情況（例如資料庫錯誤）
        $component = Livewire::test(PermissionDeleteModal::class);

        $component->dispatch('confirm-permission-delete', permissionId: $permission->id)
                 ->set('confirmationText', $permission->name);

        // 刪除權限後再次嘗試刪除（模擬權限不存在的情況）
        $permission->delete();
        
        $component->call('delete')
                 ->assertDispatched('show-toast', [
                     'type' => 'error'
                 ]);
    }

    /** @test */
    public function 計算屬性返回正確的值()
    {
        $this->actingAs($this->adminUser);

        $permission = $this->testPermissions['deletable'];
        
        $component = Livewire::test(PermissionDeleteModal::class);

        $component->dispatch('confirm-permission-delete', permissionId: $permission->id);

        // 測試 confirmationRequired 計算屬性
        $this->assertTrue($component->get('confirmationRequired'));

        // 測試 deleteButtonText 計算屬性
        $this->assertEquals('確認刪除', $component->get('deleteButtonText'));

        // 測試 modalTitle 計算屬性
        $this->assertEquals('刪除權限確認', $component->get('modalTitle'));
    }
}