<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 角色權限管理功能測試
 * 
 * 測試角色和權限的建立、編輯、刪除和指派功能
 */
class RolePermissionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立基本角色和權限
        $this->adminRole = Role::factory()->create(['name' => 'admin']);
        $this->userRole = Role::factory()->create(['name' => 'user']);
        
        $this->viewPermission = Permission::factory()->create(['name' => 'users.view']);
        $this->createPermission = Permission::factory()->create(['name' => 'users.create']);
        $this->editPermission = Permission::factory()->create(['name' => 'users.edit']);
        $this->deletePermission = Permission::factory()->create(['name' => 'users.delete']);
        
        // 建立管理員使用者
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($this->adminRole);
    }

    /**
     * 測試管理員可以檢視角色列表
     */
    public function test_admin_can_view_role_list()
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/roles');
        $response->assertStatus(200);
        $response->assertSee('角色管理');
        $response->assertSee($this->adminRole->display_name);
        $response->assertSee($this->userRole->display_name);
    }

    /**
     * 測試管理員可以建立新角色
     */
    public function test_admin_can_create_new_role()
    {
        $this->actingAs($this->admin);

        // 訪問建立角色頁面
        $response = $this->get('/admin/roles/create');
        $response->assertStatus(200);
        $response->assertSee('建立角色');

        // 提交建立角色表單
        $roleData = [
            'name' => 'editor',
            'display_name' => '編輯者',
            'description' => '內容編輯者角色',
            'permissions' => [$this->viewPermission->id, $this->editPermission->id],
            'is_active' => true
        ];

        $response = $this->post('/admin/roles', $roleData);
        $response->assertRedirect('/admin/roles');

        // 檢查角色是否被建立
        $this->assertDatabaseHas('roles', [
            'name' => 'editor',
            'display_name' => '編輯者',
            'description' => '內容編輯者角色'
        ]);

        // 檢查權限是否被指派
        $newRole = Role::where('name', 'editor')->first();
        $this->assertTrue($newRole->permissions->contains($this->viewPermission));
        $this->assertTrue($newRole->permissions->contains($this->editPermission));
    }

    /**
     * 測試角色建立時的驗證規則
     */
    public function test_role_creation_validation()
    {
        $this->actingAs($this->admin);

        // 測試必填欄位
        $response = $this->post('/admin/roles', []);
        $response->assertSessionHasErrors(['name', 'display_name']);

        // 測試角色名稱唯一性
        $response = $this->post('/admin/roles', [
            'name' => 'admin', // 已存在的角色名稱
            'display_name' => '測試角色'
        ]);
        $response->assertSessionHasErrors(['name']);

        // 測試角色名稱格式
        $response = $this->post('/admin/roles', [
            'name' => 'invalid name!', // 包含無效字元
            'display_name' => '測試角色'
        ]);
        $response->assertSessionHasErrors(['name']);
    }

    /**
     * 測試管理員可以編輯角色
     */
    public function test_admin_can_edit_role()
    {
        $this->actingAs($this->admin);

        $role = Role::factory()->create([
            'name' => 'editor',
            'display_name' => '編輯者',
            'description' => '原始描述'
        ]);

        // 訪問編輯頁面
        $response = $this->get("/admin/roles/{$role->id}/edit");
        $response->assertStatus(200);
        $response->assertSee('編輯角色');
        $response->assertSee($role->display_name);

        // 提交編輯表單
        $updatedData = [
            'name' => 'editor',
            'display_name' => '高級編輯者',
            'description' => '更新的描述',
            'permissions' => [$this->viewPermission->id, $this->editPermission->id, $this->createPermission->id],
            'is_active' => true
        ];

        $response = $this->put("/admin/roles/{$role->id}", $updatedData);
        $response->assertRedirect('/admin/roles');

        // 檢查角色資料是否被更新
        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'display_name' => '高級編輯者',
            'description' => '更新的描述'
        ]);

        // 檢查權限是否被更新
        $role->refresh();
        $this->assertTrue($role->permissions->contains($this->createPermission));
    }

    /**
     * 測試管理員可以刪除角色
     */
    public function test_admin_can_delete_role()
    {
        $this->actingAs($this->admin);

        $role = Role::factory()->create();

        $response = $this->delete("/admin/roles/{$role->id}");
        $response->assertRedirect('/admin/roles');

        // 檢查角色是否被刪除
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    /**
     * 測試無法刪除有使用者的角色
     */
    public function test_cannot_delete_role_with_users()
    {
        $this->actingAs($this->admin);

        $role = Role::factory()->create();
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $response = $this->delete("/admin/roles/{$role->id}");
        $response->assertSessionHasErrors();

        // 檢查角色仍然存在
        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    /**
     * 測試權限矩陣管理
     */
    public function test_permission_matrix_management()
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/roles/permissions');
        $response->assertStatus(200);
        $response->assertSee('權限矩陣');

        // 檢查角色和權限是否顯示
        $response->assertSee($this->adminRole->display_name);
        $response->assertSee($this->viewPermission->display_name);
    }

    /**
     * 測試權限指派和移除
     */
    public function test_permission_assignment_and_removal()
    {
        $this->actingAs($this->admin);

        $role = Role::factory()->create();

        // 指派權限
        $response = $this->post("/admin/roles/{$role->id}/permissions", [
            'permissions' => [$this->viewPermission->id, $this->editPermission->id]
        ]);

        $response->assertRedirect();

        // 檢查權限是否被指派
        $role->refresh();
        $this->assertTrue($role->permissions->contains($this->viewPermission));
        $this->assertTrue($role->permissions->contains($this->editPermission));

        // 移除權限
        $response = $this->post("/admin/roles/{$role->id}/permissions", [
            'permissions' => [$this->viewPermission->id] // 只保留檢視權限
        ]);

        $response->assertRedirect();

        // 檢查權限是否被移除
        $role->refresh();
        $this->assertTrue($role->permissions->contains($this->viewPermission));
        $this->assertFalse($role->permissions->contains($this->editPermission));
    }

    /**
     * 測試角色複製功能
     */
    public function test_role_duplication()
    {
        $this->actingAs($this->admin);

        $originalRole = Role::factory()->create(['name' => 'original']);
        $originalRole->permissions()->attach([$this->viewPermission->id, $this->editPermission->id]);

        $response = $this->post("/admin/roles/{$originalRole->id}/duplicate");
        $response->assertRedirect('/admin/roles');

        // 檢查複製的角色是否被建立
        $this->assertDatabaseHas('roles', [
            'name' => 'original_copy',
            'display_name' => $originalRole->display_name . ' (副本)'
        ]);

        // 檢查權限是否被複製
        $duplicatedRole = Role::where('name', 'original_copy')->first();
        $this->assertTrue($duplicatedRole->permissions->contains($this->viewPermission));
        $this->assertTrue($duplicatedRole->permissions->contains($this->editPermission));
    }

    /**
     * 測試權限檢查功能
     */
    public function test_permission_checking()
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();
        $role->permissions()->attach($this->viewPermission);
        $user->roles()->attach($role);

        // 檢查使用者權限
        $this->assertTrue($user->hasPermission('users.view'));
        $this->assertFalse($user->hasPermission('users.create'));
        $this->assertFalse($user->hasPermission('users.edit'));
        $this->assertFalse($user->hasPermission('users.delete'));
    }

    /**
     * 測試多重角色權限
     */
    public function test_multiple_role_permissions()
    {
        $user = User::factory()->create();
        
        $role1 = Role::factory()->create();
        $role1->permissions()->attach($this->viewPermission);
        
        $role2 = Role::factory()->create();
        $role2->permissions()->attach($this->editPermission);
        
        $user->roles()->attach([$role1->id, $role2->id]);

        // 檢查使用者擁有兩個角色的權限
        $this->assertTrue($user->hasPermission('users.view'));
        $this->assertTrue($user->hasPermission('users.edit'));
        $this->assertFalse($user->hasPermission('users.create'));
    }

    /**
     * 測試權限階層管理
     */
    public function test_permission_hierarchy()
    {
        $this->actingAs($this->admin);

        // 建立階層權限
        $modulePermissions = Permission::factory()->count(4)->create([
            'module' => 'users'
        ]);

        $response = $this->get('/admin/permissions');
        $response->assertStatus(200);

        // 檢查權限是否按模組分組顯示
        $response->assertSee('使用者管理');
        foreach ($modulePermissions as $permission) {
            $response->assertSee($permission->display_name);
        }
    }

    /**
     * 測試權限搜尋和篩選
     */
    public function test_permission_search_and_filtering()
    {
        $this->actingAs($this->admin);

        $userPermission = Permission::factory()->create([
            'name' => 'users.manage',
            'display_name' => '使用者管理',
            'module' => 'users'
        ]);

        $rolePermission = Permission::factory()->create([
            'name' => 'roles.manage',
            'display_name' => '角色管理',
            'module' => 'roles'
        ]);

        // 按模組篩選
        $response = $this->get('/admin/permissions?module=users');
        $response->assertStatus(200);
        $response->assertSee($userPermission->display_name);
        $response->assertDontSee($rolePermission->display_name);

        // 搜尋權限
        $response = $this->get('/admin/permissions?search=角色');
        $response->assertStatus(200);
        $response->assertSee($rolePermission->display_name);
        $response->assertDontSee($userPermission->display_name);
    }

    /**
     * 測試系統角色保護
     */
    public function test_system_role_protection()
    {
        $this->actingAs($this->admin);

        // 假設 admin 是系統角色
        $response = $this->delete("/admin/roles/{$this->adminRole->id}");
        $response->assertSessionHasErrors();

        // 檢查系統角色無法被刪除
        $this->assertDatabaseHas('roles', ['id' => $this->adminRole->id]);
    }

    /**
     * 測試權限變更記錄
     */
    public function test_permission_change_logging()
    {
        $this->actingAs($this->admin);

        $role = Role::factory()->create();

        // 指派權限
        $this->post("/admin/roles/{$role->id}/permissions", [
            'permissions' => [$this->viewPermission->id]
        ]);

        // 檢查變更記錄是否被建立
        $this->assertDatabaseHas('activities', [
            'subject_type' => Role::class,
            'subject_id' => $role->id,
            'description' => 'permissions_updated'
        ]);
    }

    /**
     * 測試權限快取機制
     */
    public function test_permission_caching()
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();
        $role->permissions()->attach($this->viewPermission);
        $user->roles()->attach($role);

        // 第一次檢查權限（應該建立快取）
        $hasPermission1 = $user->hasPermission('users.view');
        $this->assertTrue($hasPermission1);

        // 第二次檢查權限（應該使用快取）
        $hasPermission2 = $user->hasPermission('users.view');
        $this->assertTrue($hasPermission2);

        // 檢查快取是否存在
        $cacheKey = "user_permissions_{$user->id}";
        $this->assertTrue(cache()->has($cacheKey));
    }

    /**
     * 測試權限控制 - 無權限使用者
     */
    public function test_unauthorized_user_cannot_access_role_management()
    {
        $unauthorizedUser = User::factory()->create();
        $this->actingAs($unauthorizedUser);

        $response = $this->get('/admin/roles');
        $response->assertStatus(403);

        $response = $this->get('/admin/roles/create');
        $response->assertStatus(403);
    }
}