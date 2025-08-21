<?php

namespace Tests\Unit\Models;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 角色模型單元測試
 * 
 * 測試 Role 模型的所有方法和計算屬性
 */
class RoleModelTest extends TestCase
{
    use RefreshDatabase;

    protected Role $role;
    protected Permission $permission1;
    protected Permission $permission2;
    protected Permission $permission3;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試角色
        $this->role = Role::create([
            'name' => 'test_role',
            'display_name' => '測試角色',
            'description' => '測試用角色',
            'is_active' => true,
            'is_system_role' => false
        ]);

        // 建立測試權限
        $this->permission1 = Permission::create([
            'name' => 'users.view',
            'display_name' => '檢視使用者',
            'description' => '檢視使用者列表',
            'module' => 'users'
        ]);

        $this->permission2 = Permission::create([
            'name' => 'users.create',
            'display_name' => '建立使用者',
            'description' => '建立新使用者',
            'module' => 'users'
        ]);

        $this->permission3 = Permission::create([
            'name' => 'roles.view',
            'display_name' => '檢視角色',
            'description' => '檢視角色列表',
            'module' => 'roles'
        ]);
    }

    /**
     * 測試角色與使用者的關聯
     */
    public function test_users_relationship(): void
    {
        $user = User::factory()->create();
        $this->role->users()->attach($user->id);

        $this->assertEquals(1, $this->role->users()->count());
        $this->assertTrue($this->role->users->contains($user));
    }

    /**
     * 測試角色與權限的關聯
     */
    public function test_permissions_relationship(): void
    {
        $this->role->permissions()->attach($this->permission1->id);

        $this->assertEquals(1, $this->role->permissions()->count());
        $this->assertTrue($this->role->permissions->contains($this->permission1));
    }

    /**
     * 測試父子角色關聯
     */
    public function test_parent_child_relationship(): void
    {
        $parentRole = Role::create([
            'name' => 'parent_role',
            'display_name' => '父角色'
        ]);

        $childRole = Role::create([
            'name' => 'child_role',
            'display_name' => '子角色',
            'parent_id' => $parentRole->id
        ]);

        // 測試父角色關聯
        $this->assertEquals($parentRole->id, $childRole->parent->id);
        
        // 測試子角色關聯
        $this->assertEquals(1, $parentRole->children()->count());
        $this->assertTrue($parentRole->children->contains($childRole));
    }

    /**
     * 測試檢查角色是否擁有特定權限
     */
    public function test_has_permission(): void
    {
        $this->role->permissions()->attach($this->permission1->id);

        $this->assertTrue($this->role->hasPermission('users.view'));
        $this->assertFalse($this->role->hasPermission('users.create'));
    }

    /**
     * 測試為角色新增權限
     */
    public function test_give_permission(): void
    {
        $this->role->givePermission('users.view');

        $this->assertTrue($this->role->hasPermission('users.view'));
        $this->assertEquals(1, $this->role->permissions()->count());
    }

    /**
     * 測試為角色新增權限（使用 Permission 物件）
     */
    public function test_give_permission_with_object(): void
    {
        $this->role->givePermission($this->permission1);

        $this->assertTrue($this->role->hasPermission('users.view'));
        $this->assertEquals(1, $this->role->permissions()->count());
    }

    /**
     * 測試移除角色權限
     */
    public function test_revoke_permission(): void
    {
        $this->role->permissions()->attach($this->permission1->id);
        $this->assertTrue($this->role->hasPermission('users.view'));

        $this->role->revokePermission('users.view');

        $this->assertFalse($this->role->hasPermission('users.view'));
        $this->assertEquals(0, $this->role->permissions()->count());
    }

    /**
     * 測試同步角色權限
     */
    public function test_sync_permissions(): void
    {
        // 先新增一個權限
        $this->role->permissions()->attach($this->permission1->id);
        $this->assertEquals(1, $this->role->permissions()->count());

        // 同步權限（使用權限名稱）
        $this->role->syncPermissions(['users.create', 'roles.view']);

        $this->assertEquals(2, $this->role->permissions()->count());
        $this->assertTrue($this->role->hasPermission('users.create'));
        $this->assertTrue($this->role->hasPermission('roles.view'));
        $this->assertFalse($this->role->hasPermission('users.view'));
    }

    /**
     * 測試同步權限（使用 Permission 物件）
     */
    public function test_sync_permissions_with_objects(): void
    {
        $this->role->syncPermissions([$this->permission1, $this->permission2]);

        $this->assertEquals(2, $this->role->permissions()->count());
        $this->assertTrue($this->role->hasPermission('users.view'));
        $this->assertTrue($this->role->hasPermission('users.create'));
    }

    /**
     * 測試同步權限（使用 ID）
     */
    public function test_sync_permissions_with_ids(): void
    {
        $this->role->syncPermissions([$this->permission1->id, $this->permission2->id]);

        $this->assertEquals(2, $this->role->permissions()->count());
        $this->assertTrue($this->role->hasPermission('users.view'));
        $this->assertTrue($this->role->hasPermission('users.create'));
    }

    /**
     * 測試取得使用者數量計算屬性
     */
    public function test_user_count_attribute(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->assertEquals(0, $this->role->user_count);

        $this->role->users()->attach([$user1->id, $user2->id]);

        // 重新載入模型以取得最新資料
        $this->role->refresh();
        $this->assertEquals(2, $this->role->user_count);
    }

    /**
     * 測試取得權限數量計算屬性
     */
    public function test_permission_count_attribute(): void
    {
        $this->assertEquals(0, $this->role->permission_count);

        $this->role->permissions()->attach([$this->permission1->id, $this->permission2->id]);

        $this->role->refresh();
        $this->assertEquals(2, $this->role->permission_count);
    }

    /**
     * 測試系統角色計算屬性
     */
    public function test_is_system_role_attribute(): void
    {
        $this->assertFalse($this->role->is_system_role);

        $systemRole = Role::create([
            'name' => 'system_admin',
            'display_name' => '系統管理員',
            'is_system_role' => true
        ]);

        $this->assertTrue($systemRole->is_system_role);
    }

    /**
     * 測試角色是否可以被刪除
     */
    public function test_can_be_deleted_attribute(): void
    {
        // 沒有使用者和子角色的角色可以被刪除
        $this->assertTrue($this->role->can_be_deleted);

        // 系統角色不能被刪除
        $systemRole = Role::create([
            'name' => 'system_admin',
            'display_name' => '系統管理員',
            'is_system_role' => true
        ]);
        $this->assertFalse($systemRole->can_be_deleted);

        // 有使用者的角色不能被刪除
        $user = User::factory()->create();
        $this->role->users()->attach($user->id);
        $this->role->refresh();
        $this->assertFalse($this->role->can_be_deleted);

        // 移除使用者後可以被刪除
        $this->role->users()->detach();
        $this->role->refresh();
        $this->assertTrue($this->role->can_be_deleted);

        // 有子角色的角色不能被刪除
        $childRole = Role::create([
            'name' => 'child_role',
            'display_name' => '子角色',
            'parent_id' => $this->role->id
        ]);
        $this->role->refresh();
        $this->assertFalse($this->role->can_be_deleted);
    }

    /**
     * 測試取得所有權限（包含繼承）
     */
    public function test_get_all_permissions(): void
    {
        $parentRole = Role::create([
            'name' => 'parent_role',
            'display_name' => '父角色'
        ]);

        $childRole = Role::create([
            'name' => 'child_role',
            'display_name' => '子角色',
            'parent_id' => $parentRole->id
        ]);

        // 父角色有權限1
        $parentRole->permissions()->attach($this->permission1->id);
        
        // 子角色有權限2
        $childRole->permissions()->attach($this->permission2->id);

        $allPermissions = $childRole->getAllPermissions();

        $this->assertEquals(2, $allPermissions->count());
        $this->assertTrue($allPermissions->contains('id', $this->permission1->id));
        $this->assertTrue($allPermissions->contains('id', $this->permission2->id));
    }

    /**
     * 測試取得繼承的權限
     */
    public function test_get_inherited_permissions(): void
    {
        $parentRole = Role::create([
            'name' => 'parent_role',
            'display_name' => '父角色'
        ]);

        $childRole = Role::create([
            'name' => 'child_role',
            'display_name' => '子角色',
            'parent_id' => $parentRole->id
        ]);

        // 父角色有權限1和權限2
        $parentRole->permissions()->attach([$this->permission1->id, $this->permission2->id]);
        
        // 子角色有權限3
        $childRole->permissions()->attach($this->permission3->id);

        $inheritedPermissions = $childRole->getInheritedPermissions();

        $this->assertEquals(2, $inheritedPermissions->count());
        $this->assertTrue($inheritedPermissions->contains('id', $this->permission1->id));
        $this->assertTrue($inheritedPermissions->contains('id', $this->permission2->id));
        $this->assertFalse($inheritedPermissions->contains('id', $this->permission3->id));
    }

    /**
     * 測試沒有父角色時的繼承權限
     */
    public function test_get_inherited_permissions_without_parent(): void
    {
        $inheritedPermissions = $this->role->getInheritedPermissions();

        $this->assertEquals(0, $inheritedPermissions->count());
        $this->assertTrue($inheritedPermissions->isEmpty());
    }

    /**
     * 測試循環依賴檢查
     */
    public function test_has_circular_dependency(): void
    {
        $role1 = Role::create(['name' => 'role1', 'display_name' => '角色1']);
        $role2 = Role::create(['name' => 'role2', 'display_name' => '角色2', 'parent_id' => $role1->id]);
        $role3 = Role::create(['name' => 'role3', 'display_name' => '角色3', 'parent_id' => $role2->id]);

        // 測試自我依賴
        $this->assertTrue($role1->hasCircularDependency($role1->id));

        // 測試循環依賴 - role1 不能設定其後代為父角色
        $this->assertTrue($role1->hasCircularDependency($role2->id));
        $this->assertTrue($role1->hasCircularDependency($role3->id));

        // 測試正常情況 - role1 可以設定一個新的父角色
        $role4 = Role::create(['name' => 'role4', 'display_name' => '角色4']);
        $this->assertFalse($role1->hasCircularDependency($role4->id));
    }

    /**
     * 測試檢查權限（包含繼承）
     */
    public function test_has_permission_including_inherited(): void
    {
        $parentRole = Role::create([
            'name' => 'parent_role',
            'display_name' => '父角色'
        ]);

        $childRole = Role::create([
            'name' => 'child_role',
            'display_name' => '子角色',
            'parent_id' => $parentRole->id
        ]);

        // 父角色有權限1
        $parentRole->permissions()->attach($this->permission1->id);
        
        // 子角色有權限2
        $childRole->permissions()->attach($this->permission2->id);

        // 子角色應該擁有自己的權限和繼承的權限
        $this->assertTrue($childRole->hasPermissionIncludingInherited('users.view')); // 繼承的
        $this->assertTrue($childRole->hasPermissionIncludingInherited('users.create')); // 自己的
        $this->assertFalse($childRole->hasPermissionIncludingInherited('roles.view')); // 沒有的
    }

    /**
     * 測試取得角色層級深度
     */
    public function test_get_depth(): void
    {
        $root = Role::create(['name' => 'root', 'display_name' => '根角色']);
        $level1 = Role::create(['name' => 'level1', 'display_name' => '第一層', 'parent_id' => $root->id]);
        $level2 = Role::create(['name' => 'level2', 'display_name' => '第二層', 'parent_id' => $level1->id]);

        $this->assertEquals(0, $root->getDepth());
        $this->assertEquals(1, $level1->getDepth());
        $this->assertEquals(2, $level2->getDepth());
    }

    /**
     * 測試取得祖先角色
     */
    public function test_get_ancestors(): void
    {
        $grandParent = Role::create(['name' => 'grandparent', 'display_name' => '祖父角色']);
        $parent = Role::create(['name' => 'parent', 'display_name' => '父角色', 'parent_id' => $grandParent->id]);
        $child = Role::create(['name' => 'child', 'display_name' => '子角色', 'parent_id' => $parent->id]);

        $ancestors = $child->getAncestors();

        $this->assertEquals(2, $ancestors->count());
        $this->assertEquals($parent->id, $ancestors->first()->id);
        $this->assertEquals($grandParent->id, $ancestors->last()->id);

        // 根角色沒有祖先
        $rootAncestors = $grandParent->getAncestors();
        $this->assertEquals(0, $rootAncestors->count());
    }

    /**
     * 測試取得後代角色
     */
    public function test_get_descendants(): void
    {
        $root = Role::create(['name' => 'root', 'display_name' => '根角色']);
        $child1 = Role::create(['name' => 'child1', 'display_name' => '子角色1', 'parent_id' => $root->id]);
        $child2 = Role::create(['name' => 'child2', 'display_name' => '子角色2', 'parent_id' => $root->id]);
        $grandChild = Role::create(['name' => 'grandchild', 'display_name' => '孫角色', 'parent_id' => $child1->id]);

        $descendants = $root->getDescendants();

        $this->assertEquals(3, $descendants->count());
        $this->assertTrue($descendants->contains('id', $child1->id));
        $this->assertTrue($descendants->contains('id', $child2->id));
        $this->assertTrue($descendants->contains('id', $grandChild->id));

        // 葉子角色沒有後代
        $leafDescendants = $child2->getDescendants();
        $this->assertEquals(0, $leafDescendants->count());
    }

    /**
     * 測試取得直接權限
     */
    public function test_get_direct_permissions(): void
    {
        $parentRole = Role::create([
            'name' => 'parent_role',
            'display_name' => '父角色'
        ]);

        $childRole = Role::create([
            'name' => 'child_role',
            'display_name' => '子角色',
            'parent_id' => $parentRole->id
        ]);

        // 父角色有權限1
        $parentRole->permissions()->attach($this->permission1->id);
        
        // 子角色有權限2
        $childRole->permissions()->attach($this->permission2->id);

        $directPermissions = $childRole->getDirectPermissions();

        $this->assertEquals(1, $directPermissions->count());
        $this->assertTrue($directPermissions->contains('id', $this->permission2->id));
        $this->assertFalse($directPermissions->contains('id', $this->permission1->id));
    }

    /**
     * 測試檢查是否有子角色
     */
    public function test_has_children(): void
    {
        $this->assertFalse($this->role->hasChildren());

        $childRole = Role::create([
            'name' => 'child_role',
            'display_name' => '子角色',
            'parent_id' => $this->role->id
        ]);

        $this->role->refresh();
        $this->assertTrue($this->role->hasChildren());
    }

    /**
     * 測試檢查是否有父角色
     */
    public function test_has_parent(): void
    {
        $this->assertFalse($this->role->hasParent());

        $parentRole = Role::create([
            'name' => 'parent_role',
            'display_name' => '父角色'
        ]);

        $this->role->update(['parent_id' => $parentRole->id]);
        $this->assertTrue($this->role->hasParent());
    }

    /**
     * 測試取得層級路徑
     */
    public function test_get_hierarchy_path(): void
    {
        $grandParent = Role::create(['name' => 'grandparent', 'display_name' => '祖父角色']);
        $parent = Role::create(['name' => 'parent', 'display_name' => '父角色', 'parent_id' => $grandParent->id]);
        $child = Role::create(['name' => 'child', 'display_name' => '子角色', 'parent_id' => $parent->id]);

        $this->assertEquals('祖父角色 > 父角色 > 子角色', $child->getHierarchyPath());
        $this->assertEquals('祖父角色', $grandParent->getHierarchyPath());
    }

    /**
     * 測試檢查是否為根角色
     */
    public function test_is_root(): void
    {
        $this->assertTrue($this->role->isRoot());

        $parentRole = Role::create([
            'name' => 'parent_role',
            'display_name' => '父角色'
        ]);

        $childRole = Role::create([
            'name' => 'child_role',
            'display_name' => '子角色',
            'parent_id' => $parentRole->id
        ]);

        $this->assertTrue($parentRole->isRoot());
        $this->assertFalse($childRole->isRoot());
    }

    /**
     * 測試檢查是否為葉子角色
     */
    public function test_is_leaf(): void
    {
        $this->assertTrue($this->role->isLeaf());

        $childRole = Role::create([
            'name' => 'child_role',
            'display_name' => '子角色',
            'parent_id' => $this->role->id
        ]);

        $this->role->refresh();
        $this->assertFalse($this->role->isLeaf());
        $this->assertTrue($childRole->isLeaf());
    }

    /**
     * 測試取得權限統計
     */
    public function test_get_permission_stats(): void
    {
        $parentRole = Role::create([
            'name' => 'parent_role',
            'display_name' => '父角色'
        ]);

        $childRole = Role::create([
            'name' => 'child_role',
            'display_name' => '子角色',
            'parent_id' => $parentRole->id
        ]);

        // 父角色有 users 模組的權限
        $parentRole->permissions()->attach($this->permission1->id);
        
        // 子角色有 users 和 roles 模組的權限
        $childRole->permissions()->attach([$this->permission2->id, $this->permission3->id]);

        $stats = $childRole->getPermissionStats();

        $this->assertEquals(2, $stats['direct_count']);
        $this->assertEquals(1, $stats['inherited_count']);
        $this->assertEquals(3, $stats['total_count']);
        $this->assertEquals(2, $stats['by_module']['users']);
        $this->assertEquals(1, $stats['by_module']['roles']);
    }

    /**
     * 測試清除所有權限
     */
    public function test_clear_all_permissions(): void
    {
        $this->role->permissions()->attach([$this->permission1->id, $this->permission2->id]);
        $this->assertEquals(2, $this->role->permissions()->count());

        $this->role->clearAllPermissions();

        $this->assertEquals(0, $this->role->permissions()->count());
    }

    /**
     * 測試檢查是否擁有任何權限
     */
    public function test_has_any_permission(): void
    {
        $this->assertFalse($this->role->hasAnyPermission());

        $this->role->permissions()->attach($this->permission1->id);

        $this->assertTrue($this->role->hasAnyPermission());
    }

    /**
     * 測試檢查是否擁有所有指定權限
     */
    public function test_has_all_permissions(): void
    {
        $this->role->permissions()->attach([$this->permission1->id, $this->permission2->id]);

        $this->assertTrue($this->role->hasAllPermissions(['users.view', 'users.create']));
        $this->assertFalse($this->role->hasAllPermissions(['users.view', 'users.create', 'roles.view']));
        $this->assertTrue($this->role->hasAllPermissions([])); // 空陣列應該回傳 true
    }

    /**
     * 測試檢查是否擁有任一指定權限
     */
    public function test_has_any_of_permissions(): void
    {
        $this->role->permissions()->attach($this->permission1->id);

        $this->assertTrue($this->role->hasAnyOfPermissions(['users.view', 'users.create']));
        $this->assertTrue($this->role->hasAnyOfPermissions(['users.view']));
        $this->assertFalse($this->role->hasAnyOfPermissions(['users.create', 'roles.view']));
        $this->assertFalse($this->role->hasAnyOfPermissions([])); // 空陣列應該回傳 false
    }

    /**
     * 測試權限依賴解析 - givePermissionTo
     */
    public function test_give_permission_to_with_dependencies(): void
    {
        // 建立權限依賴關係：permission2 依賴 permission1
        $this->permission2->dependencies()->attach($this->permission1->id);

        // 當給予 permission2 時，應該自動給予 permission1
        $this->role->givePermissionTo($this->permission2);

        $this->assertTrue($this->role->hasPermission('users.view')); // 依賴的權限
        $this->assertTrue($this->role->hasPermission('users.create')); // 目標權限
        $this->assertEquals(2, $this->role->permissions()->count());
    }

    /**
     * 測試權限依賴解析 - revokePermissionTo
     */
    public function test_revoke_permission_to_with_dependents(): void
    {
        // 建立權限依賴關係：permission2 依賴 permission1
        $this->permission2->dependencies()->attach($this->permission1->id);

        // 先給予兩個權限
        $this->role->permissions()->attach([$this->permission1->id, $this->permission2->id]);
        $this->assertEquals(2, $this->role->permissions()->count());

        // 當移除 permission1 時，應該自動移除依賴它的 permission2
        $this->role->revokePermissionTo($this->permission1);

        $this->assertFalse($this->role->hasPermission('users.view'));
        $this->assertFalse($this->role->hasPermission('users.create'));
        $this->assertEquals(0, $this->role->permissions()->count());
    }

    /**
     * 測試本地化顯示名稱
     */
    public function test_localized_display_name_attribute(): void
    {
        // 模擬語言檔案存在的情況
        app()->setLocale('zh_TW');
        
        // 由於我們無法在測試中輕易模擬語言檔案，這裡測試預設行為
        $localizedName = $this->role->localized_display_name;
        $this->assertEquals('測試角色', $localizedName);
    }

    /**
     * 測試本地化描述
     */
    public function test_localized_description_attribute(): void
    {
        app()->setLocale('zh_TW');
        
        $localizedDescription = $this->role->localized_description;
        $this->assertEquals('測試用角色', $localizedDescription);
    }

    /**
     * 測試格式化建立時間
     */
    public function test_formatted_created_at_attribute(): void
    {
        // 這個測試需要 DateTimeHelper 類別存在
        if (class_exists('\App\Helpers\DateTimeHelper')) {
            $formattedDate = $this->role->formatted_created_at;
            $this->assertIsString($formattedDate);
        } else {
            $this->markTestSkipped('DateTimeHelper class not found');
        }
    }

    /**
     * 測試格式化更新時間
     */
    public function test_formatted_updated_at_attribute(): void
    {
        if (class_exists('\App\Helpers\DateTimeHelper')) {
            $formattedDate = $this->role->formatted_updated_at;
            $this->assertIsString($formattedDate);
        } else {
            $this->markTestSkipped('DateTimeHelper class not found');
        }
    }

    /**
     * 測試多層級權限繼承
     */
    public function test_multi_level_permission_inheritance(): void
    {
        $grandParent = Role::create(['name' => 'grandparent', 'display_name' => '祖父角色']);
        $parent = Role::create(['name' => 'parent', 'display_name' => '父角色', 'parent_id' => $grandParent->id]);
        $child = Role::create(['name' => 'child', 'display_name' => '子角色', 'parent_id' => $parent->id]);

        // 祖父角色有權限1
        $grandParent->permissions()->attach($this->permission1->id);
        
        // 父角色有權限2
        $parent->permissions()->attach($this->permission2->id);
        
        // 子角色有權限3
        $child->permissions()->attach($this->permission3->id);

        $allPermissions = $child->getAllPermissions();

        $this->assertEquals(3, $allPermissions->count());
        $this->assertTrue($allPermissions->contains('id', $this->permission1->id)); // 從祖父繼承
        $this->assertTrue($allPermissions->contains('id', $this->permission2->id)); // 從父親繼承
        $this->assertTrue($allPermissions->contains('id', $this->permission3->id)); // 自己的權限
    }

    /**
     * 測試重複權限的處理
     */
    public function test_duplicate_permission_handling(): void
    {
        // 嘗試多次給予同一個權限
        $this->role->givePermissionTo($this->permission1);
        $this->role->givePermissionTo($this->permission1);
        $this->role->givePermissionTo($this->permission1);

        // 應該只有一個權限
        $this->assertEquals(1, $this->role->permissions()->count());
        $this->assertTrue($this->role->hasPermission('users.view'));
    }
}