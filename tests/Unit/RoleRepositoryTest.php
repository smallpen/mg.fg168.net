<?php

namespace Tests\Unit;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Repositories\RoleRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 角色資料存取層測試
 */
class RoleRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected RoleRepository $roleRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->roleRepository = new RoleRepository();
    }

    /**
     * 測試建立角色
     */
    public function test_create_role(): void
    {
        $roleData = [
            'name' => 'test_role',
            'display_name' => '測試角色',
            'description' => '這是一個測試角色'
        ];

        $role = $this->roleRepository->create($roleData);

        $this->assertInstanceOf(Role::class, $role);
        $this->assertEquals('test_role', $role->name);
        $this->assertEquals('測試角色', $role->display_name);
    }

    /**
     * 測試根據名稱尋找角色
     */
    public function test_find_by_name(): void
    {
        $role = Role::create([
            'name' => 'admin',
            'display_name' => '管理員',
            'description' => '管理員角色'
        ]);

        $foundRole = $this->roleRepository->findByName('admin');

        $this->assertNotNull($foundRole);
        $this->assertEquals($role->id, $foundRole->id);
    }

    /**
     * 測試檢查角色名稱是否存在
     */
    public function test_name_exists(): void
    {
        Role::create([
            'name' => 'existing_role',
            'display_name' => '已存在角色',
            'description' => '已存在的角色'
        ]);

        $this->assertTrue($this->roleRepository->nameExists('existing_role'));
        $this->assertFalse($this->roleRepository->nameExists('non_existing_role'));
    }

    /**
     * 測試分頁功能
     */
    public function test_paginate(): void
    {
        // 建立測試角色
        Role::create(['name' => 'role1', 'display_name' => '角色1', 'description' => '描述1']);
        Role::create(['name' => 'role2', 'display_name' => '角色2', 'description' => '描述2']);
        Role::create(['name' => 'role3', 'display_name' => '角色3', 'description' => '描述3']);

        $result = $this->roleRepository->paginate(2);

        $this->assertEquals(2, $result->perPage());
        $this->assertEquals(3, $result->total());
        $this->assertEquals(2, $result->count());
    }

    /**
     * 測試搜尋功能
     */
    public function test_search(): void
    {
        Role::create(['name' => 'admin', 'display_name' => '管理員', 'description' => '系統管理員']);
        Role::create(['name' => 'user', 'display_name' => '使用者', 'description' => '一般使用者']);
        Role::create(['name' => 'guest', 'display_name' => '訪客', 'description' => '訪客角色']);

        $results = $this->roleRepository->search('管理', 10);

        $this->assertEquals(1, $results->count());
        $this->assertEquals('admin', $results->first()->name);
    }

    /**
     * 測試權限同步
     */
    public function test_sync_permissions(): void
    {
        $role = Role::create(['name' => 'test_role', 'display_name' => '測試角色']);
        
        $permission1 = Permission::create([
            'name' => 'test.create',
            'display_name' => '建立測試',
            'module' => 'test'
        ]);
        
        $permission2 = Permission::create([
            'name' => 'test.edit',
            'display_name' => '編輯測試',
            'module' => 'test'
        ]);

        $this->roleRepository->syncPermissions($role, [$permission1->id, $permission2->id]);

        $this->assertEquals(2, $role->permissions()->count());
        $this->assertTrue($role->permissions()->where('permissions.id', $permission1->id)->exists());
        $this->assertTrue($role->permissions()->where('permissions.id', $permission2->id)->exists());
    }

    /**
     * 測試新增權限
     */
    public function test_attach_permissions(): void
    {
        $role = Role::create(['name' => 'test_role', 'display_name' => '測試角色']);
        
        $permission = Permission::create([
            'name' => 'test.create',
            'display_name' => '建立測試',
            'module' => 'test'
        ]);

        $this->roleRepository->attachPermissions($role, [$permission->id]);

        $this->assertEquals(1, $role->permissions()->count());
        $this->assertTrue($role->permissions()->where('permissions.id', $permission->id)->exists());
    }

    /**
     * 測試移除權限
     */
    public function test_detach_permissions(): void
    {
        $role = Role::create(['name' => 'test_role', 'display_name' => '測試角色']);
        
        $permission = Permission::create([
            'name' => 'test.create',
            'display_name' => '建立測試',
            'module' => 'test'
        ]);

        $role->permissions()->attach($permission->id);
        $this->assertEquals(1, $role->permissions()->count());

        $this->roleRepository->detachPermissions($role, [$permission->id]);

        $this->assertEquals(0, $role->permissions()->count());
    }

    /**
     * 測試檢查角色是否可以被刪除
     */
    public function test_can_be_deleted(): void
    {
        $role = Role::create(['name' => 'test_role', 'display_name' => '測試角色']);
        
        // 沒有使用者的角色可以被刪除
        $this->assertTrue($this->roleRepository->canBeDeleted($role));

        // 有使用者的角色不能被刪除
        $user = User::create([
            'username' => 'testuser',
            'name' => '測試使用者',
            'email' => 'test@example.com',
            'password' => bcrypt('password')
        ]);
        
        $user->roles()->attach($role->id);
        
        $this->assertFalse($this->roleRepository->canBeDeleted($role));
    }

    /**
     * 測試複製角色
     */
    public function test_duplicate_role(): void
    {
        $sourceRole = Role::create(['name' => 'source_role', 'display_name' => '來源角色']);
        
        $permission = Permission::create([
            'name' => 'test.create',
            'display_name' => '建立測試',
            'module' => 'test'
        ]);
        
        $sourceRole->permissions()->attach($permission->id);

        $newRoleData = [
            'name' => 'duplicated_role',
            'display_name' => '複製角色',
            'description' => '複製的角色'
        ];

        $duplicatedRole = $this->roleRepository->duplicate($sourceRole, $newRoleData);

        $this->assertEquals('duplicated_role', $duplicatedRole->name);
        $this->assertEquals('複製角色', $duplicatedRole->display_name);
        $this->assertEquals(1, $duplicatedRole->permissions()->count());
        $this->assertTrue($duplicatedRole->permissions()->where('permissions.id', $permission->id)->exists());
    }

    /**
     * 測試取得權限樹狀結構
     */
    public function test_get_permission_tree(): void
    {
        $role = Role::create(['name' => 'test_role', 'display_name' => '測試角色']);
        
        $permission1 = Permission::create([
            'name' => 'user.create',
            'display_name' => '建立使用者',
            'module' => 'user'
        ]);
        
        $permission2 = Permission::create([
            'name' => 'user.edit',
            'display_name' => '編輯使用者',
            'module' => 'user'
        ]);
        
        $permission3 = Permission::create([
            'name' => 'role.create',
            'display_name' => '建立角色',
            'module' => 'role'
        ]);

        $role->permissions()->attach([$permission1->id, $permission2->id, $permission3->id]);

        $tree = $this->roleRepository->getPermissionTree($role);

        $this->assertIsArray($tree);
        $this->assertCount(2, $tree); // 兩個模組：user 和 role
        
        // 檢查 user 模組
        $userModule = collect($tree)->firstWhere('module', 'user');
        $this->assertNotNull($userModule);
        $this->assertCount(2, $userModule['permissions']);
        
        // 檢查 role 模組
        $roleModule = collect($tree)->firstWhere('module', 'role');
        $this->assertNotNull($roleModule);
        $this->assertCount(1, $roleModule['permissions']);
    }

    /**
     * 測試取得統計資訊
     */
    public function test_get_stats(): void
    {
        // 建立測試資料
        $role1 = Role::create(['name' => 'role1', 'display_name' => '角色1']);
        $role2 = Role::create(['name' => 'role2', 'display_name' => '角色2']);
        
        $user = User::create([
            'username' => 'testuser',
            'name' => '測試使用者',
            'email' => 'test@example.com',
            'password' => bcrypt('password')
        ]);
        
        $permission = Permission::create([
            'name' => 'test.create',
            'display_name' => '建立測試',
            'module' => 'test'
        ]);

        $user->roles()->attach($role1->id);
        $role1->permissions()->attach($permission->id);

        $stats = $this->roleRepository->getStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_roles', $stats);
        $this->assertArrayHasKey('roles_with_users', $stats);
        $this->assertArrayHasKey('roles_with_permissions', $stats);
        $this->assertArrayHasKey('most_used_roles', $stats);
        
        $this->assertEquals(2, $stats['total_roles']);
        $this->assertEquals(1, $stats['roles_with_users']);
        $this->assertEquals(1, $stats['roles_with_permissions']);
    }

    /**
     * 測試角色層級功能
     */
    public function test_role_hierarchy(): void
    {
        $parentRole = Role::create([
            'name' => 'parent_role',
            'display_name' => '父角色',
            'description' => '父角色描述'
        ]);

        $childRole = Role::create([
            'name' => 'child_role',
            'display_name' => '子角色',
            'description' => '子角色描述',
            'parent_id' => $parentRole->id
        ]);

        // 測試關聯關係
        $this->assertEquals($parentRole->id, $childRole->parent->id);
        $this->assertEquals(1, $parentRole->children->count());
        $this->assertEquals($childRole->id, $parentRole->children->first()->id);
    }

    /**
     * 測試權限繼承
     */
    public function test_permission_inheritance(): void
    {
        $parentRole = Role::create(['name' => 'parent', 'display_name' => '父角色']);
        $childRole = Role::create(['name' => 'child', 'display_name' => '子角色', 'parent_id' => $parentRole->id]);

        $permission1 = Permission::create(['name' => 'test.create', 'display_name' => '建立測試', 'module' => 'test']);
        $permission2 = Permission::create(['name' => 'test.edit', 'display_name' => '編輯測試', 'module' => 'test']);

        // 父角色有權限1，子角色有權限2
        $parentRole->permissions()->attach($permission1->id);
        $childRole->permissions()->attach($permission2->id);

        // 子角色應該繼承父角色的權限
        $allPermissions = $childRole->getAllPermissions();
        $this->assertEquals(2, $allPermissions->count());
        $this->assertTrue($allPermissions->contains('id', $permission1->id));
        $this->assertTrue($allPermissions->contains('id', $permission2->id));

        // 測試繼承的權限
        $inheritedPermissions = $childRole->getInheritedPermissions();
        $this->assertEquals(1, $inheritedPermissions->count());
        $this->assertTrue($inheritedPermissions->contains('id', $permission1->id));
    }

    /**
     * 測試循環依賴檢查
     */
    public function test_circular_dependency_check(): void
    {
        $role1 = Role::create(['name' => 'role1', 'display_name' => '角色1']);
        $role2 = Role::create(['name' => 'role2', 'display_name' => '角色2', 'parent_id' => $role1->id]);
        $role3 = Role::create(['name' => 'role3', 'display_name' => '角色3', 'parent_id' => $role2->id]);

        // 測試正常情況 - role1 可以設定一個新的父角色（不是其後代）
        $role4 = Role::create(['name' => 'role4', 'display_name' => '角色4']);
        $this->assertFalse($role1->hasCircularDependency($role4->id));

        // 測試循環依賴 - role1 不能設定其後代為父角色
        $this->assertTrue($role1->hasCircularDependency($role2->id));
        $this->assertTrue($role1->hasCircularDependency($role3->id));

        // 測試自我依賴
        $this->assertTrue($role1->hasCircularDependency($role1->id));
    }

    /**
     * 測試系統角色保護
     */
    public function test_system_role_protection(): void
    {
        $systemRole = Role::create([
            'name' => 'system_admin',
            'display_name' => '系統管理員',
            'is_system_role' => true
        ]);

        $normalRole = Role::create([
            'name' => 'normal_role',
            'display_name' => '一般角色',
            'is_system_role' => false
        ]);

        // 系統角色不能被刪除
        $this->assertFalse($systemRole->can_be_deleted);
        $this->assertTrue($normalRole->can_be_deleted);
    }

    /**
     * 測試進階篩選功能
     */
    public function test_advanced_filtering(): void
    {
        // 建立測試資料
        $role1 = Role::create(['name' => 'role1', 'display_name' => '角色1', 'is_system_role' => true]);
        $role2 = Role::create(['name' => 'role2', 'display_name' => '角色2', 'is_active' => false]);
        $role3 = Role::create(['name' => 'role3', 'display_name' => '角色3']);

        $permission = Permission::create(['name' => 'test.create', 'display_name' => '建立測試', 'module' => 'test']);
        $role1->permissions()->attach($permission->id);

        $user = User::create([
            'username' => 'testuser',
            'name' => '測試使用者',
            'email' => 'test@example.com',
            'password' => bcrypt('password')
        ]);
        $user->roles()->attach($role1->id);

        // 測試系統角色篩選
        $systemRoles = $this->roleRepository->getPaginatedRoles(['is_system_role' => true], 10);
        $this->assertEquals(1, $systemRoles->count());

        // 測試狀態篩選
        $inactiveRoles = $this->roleRepository->getPaginatedRoles(['is_active' => false], 10);
        $this->assertEquals(1, $inactiveRoles->count());

        // 測試權限數量篩選
        $rolesWithPermissions = $this->roleRepository->getPaginatedRoles(['permission_count_filter' => 'low'], 10);
        $this->assertGreaterThanOrEqual(1, $rolesWithPermissions->count());
    }

    /**
     * 測試取得角色層級結構
     */
    public function test_get_role_hierarchy(): void
    {
        $rootRole1 = Role::create(['name' => 'root1', 'display_name' => '根角色1']);
        $rootRole2 = Role::create(['name' => 'root2', 'display_name' => '根角色2']);
        $childRole = Role::create(['name' => 'child', 'display_name' => '子角色', 'parent_id' => $rootRole1->id]);

        $hierarchy = $this->roleRepository->getRoleHierarchy();

        $this->assertEquals(2, $hierarchy->count()); // 兩個根角色
        
        $rootWithChild = $hierarchy->firstWhere('id', $rootRole1->id);
        $this->assertNotNull($rootWithChild);
        $this->assertEquals(1, $rootWithChild->children->count());
    }

    /**
     * 測試複製角色（新版本）
     */
    public function test_duplicate_role_enhanced(): void
    {
        $parentRole = Role::create(['name' => 'parent', 'display_name' => '父角色']);
        $sourceRole = Role::create([
            'name' => 'source',
            'display_name' => '來源角色',
            'description' => '來源描述',
            'parent_id' => $parentRole->id,
            'is_system_role' => true
        ]);

        $permission = Permission::create(['name' => 'test.create', 'display_name' => '建立測試', 'module' => 'test']);
        $sourceRole->permissions()->attach($permission->id);

        $duplicatedRole = $this->roleRepository->duplicateRole($sourceRole, 'duplicated_role');

        $this->assertEquals('duplicated_role', $duplicatedRole->name);
        $this->assertEquals('來源角色 (複製)', $duplicatedRole->display_name);
        $this->assertEquals($parentRole->id, $duplicatedRole->parent_id);
        $this->assertFalse($duplicatedRole->is_system_role); // 複製的角色不是系統角色
        $this->assertEquals(1, $duplicatedRole->permissions()->count());
    }

    /**
     * 測試批量設定父角色
     */
    public function test_bulk_set_parent(): void
    {
        $parentRole = Role::create(['name' => 'parent', 'display_name' => '父角色']);
        $role1 = Role::create(['name' => 'role1', 'display_name' => '角色1']);
        $role2 = Role::create(['name' => 'role2', 'display_name' => '角色2']);
        $systemRole = Role::create(['name' => 'system', 'display_name' => '系統角色', 'is_system_role' => true]);

        $updatedCount = $this->roleRepository->bulkSetParent(
            [$role1->id, $role2->id, $systemRole->id], 
            $parentRole->id
        );

        // 只有非系統角色會被更新
        $this->assertEquals(2, $updatedCount);

        $role1->refresh();
        $role2->refresh();
        $systemRole->refresh();

        $this->assertEquals($parentRole->id, $role1->parent_id);
        $this->assertEquals($parentRole->id, $role2->parent_id);
        $this->assertNull($systemRole->parent_id); // 系統角色不會被更新
    }

    /**
     * 測試取得角色路徑
     */
    public function test_get_role_path(): void
    {
        $grandParent = Role::create(['name' => 'grandparent', 'display_name' => '祖父角色']);
        $parent = Role::create(['name' => 'parent', 'display_name' => '父角色', 'parent_id' => $grandParent->id]);
        $child = Role::create(['name' => 'child', 'display_name' => '子角色', 'parent_id' => $parent->id]);

        $path = $this->roleRepository->getRolePath($child);
        $this->assertEquals('祖父角色 > 父角色 > 子角色', $path);

        $rootPath = $this->roleRepository->getRolePath($grandParent);
        $this->assertEquals('祖父角色', $rootPath);
    }

    /**
     * 測試搜尋角色（增強版）
     */
    public function test_search_roles_enhanced(): void
    {
        $systemRole = Role::create(['name' => 'system_admin', 'display_name' => '系統管理員', 'is_system_role' => true]);
        $normalRole = Role::create(['name' => 'admin', 'display_name' => '管理員', 'is_system_role' => false]);
        $rootRole = Role::create(['name' => 'root', 'display_name' => '根角色']);
        $childRole = Role::create(['name' => 'child', 'display_name' => '子角色', 'parent_id' => $rootRole->id]);

        // 測試包含系統角色的搜尋
        $allResults = $this->roleRepository->searchRoles('管理', ['include_system_roles' => true]);
        $this->assertEquals(2, $allResults->count());

        // 測試排除系統角色的搜尋
        $normalResults = $this->roleRepository->searchRoles('管理', ['include_system_roles' => false]);
        $this->assertEquals(1, $normalResults->count());

        // 測試只搜尋根角色
        $rootResults = $this->roleRepository->searchRoles('角色', ['root_only' => true]);
        $this->assertEquals(1, $rootResults->count());
        $this->assertEquals($rootRole->id, $rootResults->first()->id);
    }

    /**
     * 測試取得角色統計資訊（增強版）
     */
    public function test_get_role_stats_enhanced(): void
    {
        $rootRole = Role::create(['name' => 'root', 'display_name' => '根角色']);
        $childRole = Role::create(['name' => 'child', 'display_name' => '子角色', 'parent_id' => $rootRole->id]);
        $systemRole = Role::create(['name' => 'system', 'display_name' => '系統角色', 'is_system_role' => true]);

        $stats = $this->roleRepository->getRoleStats();

        $this->assertArrayHasKey('hierarchy_stats', $stats);
        $this->assertArrayHasKey('root_roles', $stats['hierarchy_stats']);
        $this->assertArrayHasKey('child_roles', $stats['hierarchy_stats']);
        $this->assertArrayHasKey('system_roles', $stats['hierarchy_stats']);

        $this->assertEquals(2, $stats['hierarchy_stats']['root_roles']); // root 和 system
        $this->assertEquals(1, $stats['hierarchy_stats']['child_roles']);
        $this->assertEquals(1, $stats['hierarchy_stats']['system_roles']);
    }
}