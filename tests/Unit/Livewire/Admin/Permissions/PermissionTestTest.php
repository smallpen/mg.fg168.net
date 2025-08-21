<?php

namespace Tests\Unit\Livewire\Admin\Permissions;

use App\Livewire\Admin\Permissions\PermissionTest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * PermissionTest 元件單元測試
 */
class PermissionTestTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $testUser;
    protected Role $adminRole;
    protected Role $testRole;
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
        
        // 建立測試角色和使用者
        $this->testRole = Role::create([
            'name' => 'test_role',
            'display_name' => '測試角色',
            'is_active' => true,
        ]);

        $this->testUser = User::factory()->create(['is_active' => true]);
        $this->testUser->roles()->attach($this->testRole);

        // 建立測試權限
        $this->createTestPermissions();
    }

    private function createTestPermissions(): void
    {
        $this->testPermissions = [
            'users.view' => Permission::create([
                'name' => 'users.view',
                'display_name' => '檢視使用者',
                'description' => '檢視使用者列表',
                'module' => 'users',
                'type' => 'view',
            ]),
            'users.edit' => Permission::create([
                'name' => 'users.edit',
                'display_name' => '編輯使用者',
                'description' => '編輯使用者資料',
                'module' => 'users',
                'type' => 'edit',
            ]),
            'roles.view' => Permission::create([
                'name' => 'roles.view',
                'display_name' => '檢視角色',
                'description' => '檢視角色列表',
                'module' => 'roles',
                'type' => 'view',
            ]),
        ];

        // 給測試角色分配一些權限
        $this->testRole->permissions()->attach([
            $this->testPermissions['users.view']->id,
            $this->testPermissions['users.edit']->id,
        ]);

        // 給管理員角色分配所有權限
        $this->adminRole->permissions()->attach(
            collect($this->testPermissions)->pluck('id')->toArray()
        );
    }

    /** @test */
    public function 元件可以正確初始化()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionTest::class);

        $component->assertSet('selectedUserId', 0)
                 ->assertSet('selectedRoleId', 0)
                 ->assertSet('permissionToTest', '')
                 ->assertSet('testResults', [])
                 ->assertSet('testMode', 'user')
                 ->assertSet('showDetailedPath', false)
                 ->assertSet('permissionPath', []);
    }

    /** @test */
    public function 可以取得使用者列表()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionTest::class);

        $users = $component->get('users');
        
        $this->assertNotEmpty($users);
        $this->assertTrue($users->contains('id', $this->testUser->id));
        $this->assertTrue($users->contains('id', $this->adminUser->id));
        
        // 檢查使用者資料格式
        $testUserData = $users->firstWhere('id', $this->testUser->id);
        $this->assertArrayHasKey('display_name', $testUserData);
        $this->assertArrayHasKey('username', $testUserData);
        $this->assertArrayHasKey('name', $testUserData);
    }

    /** @test */
    public function 可以取得角色列表()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionTest::class);

        $roles = $component->get('roles');
        
        $this->assertNotEmpty($roles);
        $this->assertTrue($roles->contains('id', $this->testRole->id));
        $this->assertTrue($roles->contains('id', $this->adminRole->id));
        
        // 檢查角色資料格式
        $testRoleData = $roles->firstWhere('id', $this->testRole->id);
        $this->assertArrayHasKey('display_name', $testRoleData);
        $this->assertArrayHasKey('name', $testRoleData);
    }

    /** @test */
    public function 可以取得權限列表()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionTest::class);

        $permissions = $component->get('permissions');
        
        $this->assertNotEmpty($permissions);
        
        // 檢查權限是否按模組分組
        $this->assertTrue($permissions->has('users'));
        $this->assertTrue($permissions->has('roles'));
        
        // 檢查權限資料格式
        $usersPermissions = $permissions->get('users')['permissions'];
        $this->assertNotEmpty($usersPermissions);
        
        $firstPermission = $usersPermissions->first();
        $this->assertArrayHasKey('name', $firstPermission);
        $this->assertArrayHasKey('display_name', $firstPermission);
        $this->assertArrayHasKey('type', $firstPermission);
    }

    /** @test */
    public function 可以測試使用者權限()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionTest::class);

        $component->set('selectedUserId', $this->testUser->id)
                 ->set('permissionToTest', 'users.view')
                 ->call('testUserPermission');

        // 檢查測試結果
        $testResults = $component->get('testResults');
        $this->assertNotEmpty($testResults);
        $this->assertEquals('user', $testResults['type']);
        $this->assertEquals($this->testUser->id, $testResults['subject']['id']);
        $this->assertEquals('users.view', $testResults['permission']['name']);
        $this->assertTrue($testResults['result']); // 測試使用者應該有這個權限

        // 檢查是否發送了事件
        $component->assertDispatched('permission-tested', [
            'type' => 'user',
            'result' => true,
            'subject' => $this->testUser->display_name,
            'permission' => $this->testPermissions['users.view']->localized_display_name,
        ]);
    }

    /** @test */
    public function 測試使用者沒有的權限()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionTest::class);

        $component->set('selectedUserId', $this->testUser->id)
                 ->set('permissionToTest', 'roles.view') // 測試使用者沒有這個權限
                 ->call('testUserPermission');

        // 檢查測試結果
        $testResults = $component->get('testResults');
        $this->assertNotEmpty($testResults);
        $this->assertEquals('user', $testResults['type']);
        $this->assertFalse($testResults['result']); // 測試使用者應該沒有這個權限
    }

    /** @test */
    public function 使用者權限測試驗證規則正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionTest::class);

        // 測試必填欄位驗證
        $component->call('testUserPermission')
                 ->assertHasErrors([
                     'selectedUserId' => 'required',
                     'permissionToTest' => 'required',
                 ]);

        // 測試不存在的使用者
        $component->set('selectedUserId', 99999)
                 ->set('permissionToTest', 'users.view')
                 ->call('testUserPermission')
                 ->assertHasErrors(['selectedUserId' => 'exists']);

        // 測試不存在的權限
        $component->set('selectedUserId', $this->testUser->id)
                 ->set('permissionToTest', 'nonexistent.permission')
                 ->call('testUserPermission')
                 ->assertHasErrors(['permissionToTest' => 'exists']);
    }

    /** @test */
    public function 可以測試角色權限()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionTest::class);

        $component->set('selectedRoleId', $this->testRole->id)
                 ->set('permissionToTest', 'users.view')
                 ->call('testRolePermission');

        // 檢查測試結果
        $testResults = $component->get('testResults');
        $this->assertNotEmpty($testResults);
        $this->assertEquals('role', $testResults['type']);
        $this->assertEquals($this->testRole->id, $testResults['subject']['id']);
        $this->assertEquals('users.view', $testResults['permission']['name']);
        $this->assertTrue($testResults['result']); // 測試角色應該有這個權限

        // 檢查是否發送了事件
        $component->assertDispatched('permission-tested', [
            'type' => 'role',
            'result' => true,
            'subject' => $this->testRole->localized_display_name,
            'permission' => $this->testPermissions['users.view']->localized_display_name,
        ]);
    }

    /** @test */
    public function 測試角色沒有的權限()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionTest::class);

        $component->set('selectedRoleId', $this->testRole->id)
                 ->set('permissionToTest', 'roles.view') // 測試角色沒有這個權限
                 ->call('testRolePermission');

        // 檢查測試結果
        $testResults = $component->get('testResults');
        $this->assertNotEmpty($testResults);
        $this->assertEquals('role', $testResults['type']);
        $this->assertFalse($testResults['result']); // 測試角色應該沒有這個權限
    }

    /** @test */
    public function 角色權限測試驗證規則正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionTest::class);

        // 測試必填欄位驗證
        $component->call('testRolePermission')
                 ->assertHasErrors([
                     'selectedRoleId' => 'required',
                     'permissionToTest' => 'required',
                 ]);

        // 測試不存在的角色
        $component->set('selectedRoleId', 99999)
                 ->set('permissionToTest', 'users.view')
                 ->call('testRolePermission')
                 ->assertHasErrors(['selectedRoleId' => 'exists']);

        // 測試不存在的權限
        $component->set('selectedRoleId', $this->testRole->id)
                 ->set('permissionToTest', 'nonexistent.permission')
                 ->call('testRolePermission')
                 ->assertHasErrors(['permissionToTest' => 'exists']);
    }

    /** @test */
    public function 可以取得使用者權限路徑()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionTest::class);

        $path = $component->getPermissionPath($this->testUser->id, 'users.view');
        
        $this->assertIsArray($path);
        $this->assertNotEmpty($path);
        
        // 檢查路徑包含角色資訊
        $rolePath = collect($path)->firstWhere('type', 'role');
        $this->assertNotNull($rolePath);
        $this->assertEquals($this->testRole->id, $rolePath['role_id']);
    }

    /** @test */
    public function 可以取得角色權限路徑()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionTest::class);

        $path = $component->getRolePermissionPath($this->testRole->id, 'users.view');
        
        $this->assertIsArray($path);
        $this->assertNotEmpty($path);
        
        // 檢查路徑包含直接分配資訊
        $directPath = collect($path)->firstWhere('type', 'direct');
        $this->assertNotNull($directPath);
        $this->assertEquals($this->testRole->id, $directPath['role_id']);
    }

    /** @test */
    public function 不存在的使用者返回空路徑()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionTest::class);

        $path = $component->getPermissionPath(99999, 'users.view');
        
        $this->assertIsArray($path);
        $this->assertEmpty($path);
    }

    /** @test */
    public function 不存在的角色返回空路徑()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionTest::class);

        $path = $component->getRolePermissionPath(99999, 'users.view');
        
        $this->assertIsArray($path);
        $this->assertEmpty($path);
    }

    /** @test */
    public function 可以清除測試結果()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionTest::class);

        // 先執行一個測試
        $component->set('selectedUserId', $this->testUser->id)
                 ->set('permissionToTest', 'users.view')
                 ->call('testUserPermission');

        // 確認有測試結果
        $this->assertNotEmpty($component->get('testResults'));

        // 清除結果
        $component->call('clearResults')
                 ->assertSet('testResults', [])
                 ->assertSet('permissionPath', [])
                 ->assertSet('showDetailedPath', false)
                 ->assertDispatched('results-cleared');
    }

    /** @test */
    public function 可以切換測試模式()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionTest::class);

        // 切換到角色模式
        $component->call('setTestMode', 'role')
                 ->assertSet('testMode', 'role')
                 ->assertSet('testResults', []); // 應該清除之前的結果

        // 切換到使用者模式
        $component->call('setTestMode', 'user')
                 ->assertSet('testMode', 'user');

        // 無效的模式應該被忽略
        $component->call('setTestMode', 'invalid')
                 ->assertSet('testMode', 'user'); // 應該保持不變
    }

    /** @test */
    public function 可以切換詳細路徑顯示()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionTest::class);

        // 初始狀態應該是隱藏的
        $component->assertSet('showDetailedPath', false);

        // 切換顯示
        $component->call('toggleDetailedPath')
                 ->assertSet('showDetailedPath', true);

        // 再次切換應該隱藏
        $component->call('toggleDetailedPath')
                 ->assertSet('showDetailedPath', false);
    }

    /** @test */
    public function 沒有測試結果時無法匯出報告()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionTest::class);

        $component->call('exportReport')
                 ->assertHasErrors(['export']);
    }

    /** @test */
    public function 可以執行批量權限測試()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionTest::class);

        // 設定使用者模式
        $component->set('testMode', 'user')
                 ->set('selectedUserId', $this->testUser->id);

        $permissions = ['users.view', 'users.edit', 'roles.view'];
        $results = $component->batchTestPermissions($permissions);

        $this->assertIsArray($results);
        $this->assertCount(3, $results);
        
        // 檢查結果格式
        foreach ($permissions as $permission) {
            $this->assertArrayHasKey($permission, $results);
            $this->assertArrayHasKey('permission', $results[$permission]);
            $this->assertArrayHasKey('has_permission', $results[$permission]);
            $this->assertArrayHasKey('path', $results[$permission]);
        }

        // 檢查具體結果
        $this->assertTrue($results['users.view']['has_permission']);
        $this->assertTrue($results['users.edit']['has_permission']);
        $this->assertFalse($results['roles.view']['has_permission']);
    }

    /** @test */
    public function 可以執行角色批量權限測試()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionTest::class);

        // 設定角色模式
        $component->set('testMode', 'role')
                 ->set('selectedRoleId', $this->testRole->id);

        $permissions = ['users.view', 'users.edit', 'roles.view'];
        $results = $component->batchTestPermissions($permissions);

        $this->assertIsArray($results);
        $this->assertCount(3, $results);
        
        // 檢查具體結果
        $this->assertTrue($results['users.view']['has_permission']);
        $this->assertTrue($results['users.edit']['has_permission']);
        $this->assertFalse($results['roles.view']['has_permission']);
    }

    /** @test */
    public function 未選擇使用者或角色時批量測試返回空結果()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionTest::class);

        $permissions = ['users.view', 'users.edit'];
        
        // 使用者模式但未選擇使用者
        $component->set('testMode', 'user');
        $results = $component->batchTestPermissions($permissions);
        $this->assertEmpty($results);

        // 角色模式但未選擇角色
        $component->set('testMode', 'role');
        $results = $component->batchTestPermissions($permissions);
        $this->assertEmpty($results);
    }

    /** @test */
    public function 測試摘要正確生成()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionTest::class);

        // 測試有權限的情況
        $component->set('selectedUserId', $this->testUser->id)
                 ->set('permissionToTest', 'users.view')
                 ->call('testUserPermission');

        $testResults = $component->get('testResults');
        $summary = $testResults['summary'];
        
        $this->assertArrayHasKey('result_text', $summary);
        $this->assertArrayHasKey('result_class', $summary);
        $this->assertArrayHasKey('icon', $summary);
        $this->assertArrayHasKey('details', $summary);
        
        $this->assertEquals('success', $summary['result_class']);
        $this->assertEquals('check-circle', $summary['icon']);

        // 測試沒有權限的情況
        $component->set('permissionToTest', 'roles.view')
                 ->call('testUserPermission');

        $testResults = $component->get('testResults');
        $summary = $testResults['summary'];
        
        $this->assertEquals('danger', $summary['result_class']);
        $this->assertEquals('x-circle', $summary['icon']);
    }

    /** @test */
    public function 角色測試摘要正確生成()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionTest::class);

        // 測試有權限的情況
        $component->set('selectedRoleId', $this->testRole->id)
                 ->set('permissionToTest', 'users.view')
                 ->call('testRolePermission');

        $testResults = $component->get('testResults');
        $summary = $testResults['summary'];
        
        $this->assertArrayHasKey('result_text', $summary);
        $this->assertArrayHasKey('result_class', $summary);
        $this->assertArrayHasKey('icon', $summary);
        $this->assertArrayHasKey('details', $summary);
        
        $this->assertEquals('success', $summary['result_class']);
        $this->assertEquals('check-circle', $summary['icon']);
    }

    /** @test */
    public function 超級管理員權限路徑正確顯示()
    {
        // 建立超級管理員使用者
        $superAdmin = User::factory()->create([
            'is_active' => true,
            'is_super_admin' => true,
        ]);

        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionTest::class);

        $path = $component->getPermissionPath($superAdmin->id, 'any.permission');
        
        $this->assertIsArray($path);
        $this->assertNotEmpty($path);
        
        $superAdminPath = $path[0];
        $this->assertEquals('super_admin', $superAdminPath['type']);
    }

    /** @test */
    public function 權限依賴路徑正確顯示()
    {
        $this->actingAs($this->adminUser);

        // 建立權限依賴關係
        $viewPermission = $this->testPermissions['users.view'];
        $editPermission = $this->testPermissions['users.edit'];
        
        // 建立一個新權限依賴 users.edit
        $managePermission = Permission::create([
            'name' => 'users.manage',
            'display_name' => '管理使用者',
            'module' => 'users',
            'type' => 'manage',
        ]);
        
        $managePermission->dependencies()->attach($editPermission->id);
        
        // 給測試角色分配管理權限
        $this->testRole->permissions()->attach($managePermission->id);

        $component = Livewire::test(PermissionTest::class);

        $path = $component->getRolePermissionPath($this->testRole->id, 'users.manage');
        
        $this->assertIsArray($path);
        $this->assertNotEmpty($path);
        
        // 應該包含直接分配的路徑
        $directPath = collect($path)->firstWhere('type', 'direct');
        $this->assertNotNull($directPath);
    }
}