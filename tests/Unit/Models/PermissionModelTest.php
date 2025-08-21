<?php

namespace Tests\Unit\Models;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 權限模型單元測試
 * 
 * 測試 Permission 模型的所有方法和計算屬性
 */
class PermissionModelTest extends TestCase
{
    use RefreshDatabase;

    protected Permission $permission1;
    protected Permission $permission2;
    protected Permission $permission3;
    protected Permission $permission4;

    protected function setUp(): void
    {
        parent::setUp();
        
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
            'name' => 'users.edit',
            'display_name' => '編輯使用者',
            'description' => '編輯使用者資料',
            'module' => 'users'
        ]);

        $this->permission4 = Permission::create([
            'name' => 'roles.view',
            'display_name' => '檢視角色',
            'description' => '檢視角色列表',
            'module' => 'roles'
        ]);
    }

    /**
     * 測試權限與角色的關聯
     */
    public function test_roles_relationship(): void
    {
        $role = Role::create([
            'name' => 'test_role',
            'display_name' => '測試角色'
        ]);

        $this->permission1->roles()->attach($role->id);

        $this->assertEquals(1, $this->permission1->roles()->count());
        $this->assertTrue($this->permission1->roles->contains($role));
    }

    /**
     * 測試根據模組分組權限
     */
    public function test_grouped_by_module(): void
    {
        $grouped = Permission::groupedByModule();

        $this->assertArrayHasKey('users', $grouped->toArray());
        $this->assertArrayHasKey('roles', $grouped->toArray());
        $this->assertEquals(3, $grouped['users']->count());
        $this->assertEquals(1, $grouped['roles']->count());
    }

    /**
     * 測試取得特定模組的權限
     */
    public function test_get_by_module(): void
    {
        $userPermissions = Permission::getByModule('users');
        $rolePermissions = Permission::getByModule('roles');

        $this->assertEquals(3, $userPermissions->count());
        $this->assertEquals(1, $rolePermissions->count());
        
        $this->assertTrue($userPermissions->contains('name', 'users.view'));
        $this->assertTrue($userPermissions->contains('name', 'users.create'));
        $this->assertTrue($userPermissions->contains('name', 'users.edit'));
        $this->assertTrue($rolePermissions->contains('name', 'roles.view'));
    }

    /**
     * 測試檢查權限是否被使用
     */
    public function test_is_in_use(): void
    {
        $this->assertFalse($this->permission1->isInUse());

        $role = Role::create([
            'name' => 'test_role',
            'display_name' => '測試角色'
        ]);

        $this->permission1->roles()->attach($role->id);

        $this->assertTrue($this->permission1->isInUse());
    }

    /**
     * 測試取得角色數量計算屬性
     */
    public function test_role_count_attribute(): void
    {
        $this->assertEquals(0, $this->permission1->role_count);

        $role1 = Role::create(['name' => 'role1', 'display_name' => '角色1']);
        $role2 = Role::create(['name' => 'role2', 'display_name' => '角色2']);

        $this->permission1->roles()->attach([$role1->id, $role2->id]);

        $this->permission1->refresh();
        $this->assertEquals(2, $this->permission1->role_count);
    }

    /**
     * 測試權限依賴關聯
     */
    public function test_dependencies_relationship(): void
    {
        // permission2 依賴 permission1
        $this->permission2->dependencies()->attach($this->permission1->id);

        $this->assertEquals(1, $this->permission2->dependencies()->count());
        $this->assertTrue($this->permission2->dependencies->contains($this->permission1));
    }

    /**
     * 測試權限被依賴關聯
     */
    public function test_dependents_relationship(): void
    {
        // permission2 依賴 permission1
        $this->permission2->dependencies()->attach($this->permission1->id);

        $this->assertEquals(1, $this->permission1->dependents()->count());
        $this->assertTrue($this->permission1->dependents->contains($this->permission2));
    }

    /**
     * 測試檢查權限依賴
     */
    public function test_depends_on(): void
    {
        // permission2 依賴 permission1
        $this->permission2->dependencies()->attach($this->permission1->id);

        $this->assertTrue($this->permission2->dependsOn($this->permission1));
        $this->assertTrue($this->permission2->dependsOn('users.view'));
        $this->assertFalse($this->permission2->dependsOn($this->permission3));
        $this->assertFalse($this->permission1->dependsOn($this->permission2));
    }

    /**
     * 測試新增權限依賴
     */
    public function test_add_dependency(): void
    {
        $this->assertFalse($this->permission2->dependsOn($this->permission1));

        $this->permission2->addDependency($this->permission1);

        $this->assertTrue($this->permission2->dependsOn($this->permission1));
        $this->assertEquals(1, $this->permission2->dependencies()->count());
    }

    /**
     * 測試新增權限依賴（使用字串）
     */
    public function test_add_dependency_with_string(): void
    {
        $this->permission2->addDependency('users.view');

        $this->assertTrue($this->permission2->dependsOn('users.view'));
        $this->assertEquals(1, $this->permission2->dependencies()->count());
    }

    /**
     * 測試移除權限依賴
     */
    public function test_remove_dependency(): void
    {
        $this->permission2->dependencies()->attach($this->permission1->id);
        $this->assertTrue($this->permission2->dependsOn($this->permission1));

        $this->permission2->removeDependency($this->permission1);

        $this->assertFalse($this->permission2->dependsOn($this->permission1));
        $this->assertEquals(0, $this->permission2->dependencies()->count());
    }

    /**
     * 測試移除權限依賴（使用字串）
     */
    public function test_remove_dependency_with_string(): void
    {
        $this->permission2->dependencies()->attach($this->permission1->id);
        $this->assertTrue($this->permission2->dependsOn($this->permission1));

        $this->permission2->removeDependency('users.view');

        $this->assertFalse($this->permission2->dependsOn($this->permission1));
        $this->assertEquals(0, $this->permission2->dependencies()->count());
    }

    /**
     * 測試同步權限依賴
     */
    public function test_sync_dependencies(): void
    {
        // 先新增一個依賴
        $this->permission3->dependencies()->attach($this->permission1->id);
        $this->assertEquals(1, $this->permission3->dependencies()->count());

        // 同步依賴（使用權限名稱）
        $this->permission3->syncDependencies(['users.view', 'users.create']);

        $this->assertEquals(2, $this->permission3->dependencies()->count());
        $this->assertTrue($this->permission3->dependsOn('users.view'));
        $this->assertTrue($this->permission3->dependsOn('users.create'));
    }

    /**
     * 測試同步依賴（使用 Permission 物件）
     */
    public function test_sync_dependencies_with_objects(): void
    {
        $this->permission3->syncDependencies([$this->permission1, $this->permission2]);

        $this->assertEquals(2, $this->permission3->dependencies()->count());
        $this->assertTrue($this->permission3->dependsOn($this->permission1));
        $this->assertTrue($this->permission3->dependsOn($this->permission2));
    }

    /**
     * 測試同步依賴（使用 ID）
     */
    public function test_sync_dependencies_with_ids(): void
    {
        $this->permission3->syncDependencies([$this->permission1->id, $this->permission2->id]);

        $this->assertEquals(2, $this->permission3->dependencies()->count());
        $this->assertTrue($this->permission3->dependsOn($this->permission1));
        $this->assertTrue($this->permission3->dependsOn($this->permission2));
    }

    /**
     * 測試取得所有依賴此權限的權限（遞迴）
     */
    public function test_get_all_dependents(): void
    {
        // 建立依賴鏈：permission3 -> permission2 -> permission1
        $this->permission2->dependencies()->attach($this->permission1->id);
        $this->permission3->dependencies()->attach($this->permission2->id);

        $allDependents = $this->permission1->getAllDependents();

        $this->assertEquals(2, $allDependents->count());
        $this->assertTrue($allDependents->contains('id', $this->permission2->id));
        $this->assertTrue($allDependents->contains('id', $this->permission3->id));
    }

    /**
     * 測試取得此權限依賴的所有權限（遞迴）
     */
    public function test_get_all_dependencies(): void
    {
        // 建立依賴鏈：permission3 -> permission2 -> permission1
        $this->permission2->dependencies()->attach($this->permission1->id);
        $this->permission3->dependencies()->attach($this->permission2->id);

        $allDependencies = $this->permission3->getAllDependencies();

        $this->assertEquals(2, $allDependencies->count());
        $this->assertTrue($allDependencies->contains('id', $this->permission1->id));
        $this->assertTrue($allDependencies->contains('id', $this->permission2->id));
    }

    /**
     * 測試複雜的依賴關係
     */
    public function test_complex_dependency_relationships(): void
    {
        // 建立複雜的依賴關係
        // permission3 依賴 permission1 和 permission2
        // permission4 依賴 permission2
        $this->permission3->dependencies()->attach([$this->permission1->id, $this->permission2->id]);
        $this->permission4->dependencies()->attach($this->permission2->id);

        // 測試 permission1 的所有依賴者
        $permission1Dependents = $this->permission1->getAllDependents();
        $this->assertEquals(1, $permission1Dependents->count());
        $this->assertTrue($permission1Dependents->contains('id', $this->permission3->id));

        // 測試 permission2 的所有依賴者
        $permission2Dependents = $this->permission2->getAllDependents();
        $this->assertEquals(2, $permission2Dependents->count());
        $this->assertTrue($permission2Dependents->contains('id', $this->permission3->id));
        $this->assertTrue($permission2Dependents->contains('id', $this->permission4->id));
    }

    /**
     * 測試檢查循環依賴
     */
    public function test_has_circular_dependency(): void
    {
        // 測試自我依賴
        $this->assertTrue($this->permission1->hasCircularDependency($this->permission1));

        // 建立依賴鏈：permission2 -> permission1
        $this->permission2->dependencies()->attach($this->permission1->id);

        // 測試循環依賴：permission1 不能依賴 permission2
        $this->assertTrue($this->permission1->hasCircularDependency($this->permission2));

        // 測試正常情況：permission3 可以依賴 permission1
        $this->assertFalse($this->permission3->hasCircularDependency($this->permission1));
    }

    /**
     * 測試複雜的循環依賴檢查
     */
    public function test_complex_circular_dependency_check(): void
    {
        // 建立依賴鏈：permission3 -> permission2 -> permission1
        $this->permission2->dependencies()->attach($this->permission1->id);
        $this->permission3->dependencies()->attach($this->permission2->id);

        // permission1 不能依賴 permission3（會形成循環）
        $this->assertTrue($this->permission1->hasCircularDependency($this->permission3));

        // permission4 可以依賴任何現有權限
        $this->assertFalse($this->permission4->hasCircularDependency($this->permission1));
        $this->assertFalse($this->permission4->hasCircularDependency($this->permission2));
        $this->assertFalse($this->permission4->hasCircularDependency($this->permission3));
    }

    /**
     * 測試本地化顯示名稱
     */
    public function test_localized_display_name_attribute(): void
    {
        app()->setLocale('zh_TW');
        
        $localizedName = $this->permission1->localized_display_name;
        $this->assertEquals('檢視使用者', $localizedName);
    }

    /**
     * 測試本地化描述
     */
    public function test_localized_description_attribute(): void
    {
        app()->setLocale('zh_TW');
        
        $localizedDescription = $this->permission1->localized_description;
        $this->assertEquals('檢視使用者列表', $localizedDescription);
    }

    /**
     * 測試檢查權限是否有依賴
     */
    public function test_has_dependencies(): void
    {
        $this->assertFalse($this->permission1->hasDependencies());

        $this->permission2->dependencies()->attach($this->permission1->id);

        $this->assertFalse($this->permission1->hasDependencies());
        $this->assertTrue($this->permission2->hasDependencies());
    }

    /**
     * 測試檢查權限是否被其他權限依賴
     */
    public function test_has_dependents(): void
    {
        $this->assertFalse($this->permission1->hasDependents());

        $this->permission2->dependencies()->attach($this->permission1->id);

        $this->assertTrue($this->permission1->hasDependents());
        $this->assertFalse($this->permission2->hasDependents());
    }

    /**
     * 測試重複依賴的處理
     */
    public function test_duplicate_dependency_handling(): void
    {
        // 嘗試多次新增同一個依賴
        $this->permission2->addDependency($this->permission1);
        $this->permission2->addDependency($this->permission1);
        $this->permission2->addDependency($this->permission1);

        // 應該只有一個依賴
        $this->assertEquals(1, $this->permission2->dependencies()->count());
        $this->assertTrue($this->permission2->dependsOn($this->permission1));
    }

    /**
     * 測試空依賴陣列的同步
     */
    public function test_sync_empty_dependencies(): void
    {
        // 先新增一些依賴
        $this->permission3->dependencies()->attach([$this->permission1->id, $this->permission2->id]);
        $this->assertEquals(2, $this->permission3->dependencies()->count());

        // 同步空陣列應該清除所有依賴
        $this->permission3->syncDependencies([]);

        $this->assertEquals(0, $this->permission3->dependencies()->count());
        $this->assertFalse($this->permission3->hasDependencies());
    }

    /**
     * 測試權限依賴的唯一性
     */
    public function test_dependency_uniqueness(): void
    {
        $dependencies = $this->permission3->getAllDependencies();
        $dependents = $this->permission1->getAllDependents();

        // 建立複雜的依賴關係，確保結果中沒有重複
        $this->permission2->dependencies()->attach($this->permission1->id);
        $this->permission3->dependencies()->attach([$this->permission1->id, $this->permission2->id]);

        $allDependencies = $this->permission3->getAllDependencies();
        $allDependents = $this->permission1->getAllDependents();

        // 檢查結果的唯一性
        $this->assertEquals($allDependencies->count(), $allDependencies->unique('id')->count());
        $this->assertEquals($allDependents->count(), $allDependents->unique('id')->count());
    }
}