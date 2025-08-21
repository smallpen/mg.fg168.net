<?php

namespace Tests\Unit\Models;

use App\Models\Permission;
use App\Models\PermissionDependency;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Permission 模型單元測試
 */
class PermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 在測試期間停用安全觀察者
        Permission::unsetEventDispatcher();
        Role::unsetEventDispatcher();
        User::unsetEventDispatcher();
        
        // 清除快取
        Cache::flush();
    }

    /** @test */
    public function it_can_create_permission_with_basic_attributes()
    {
        $permission = Permission::create([
            'name' => 'users.view',
            'display_name' => '檢視使用者',
            'description' => '檢視使用者列表和詳細資訊',
            'module' => 'users',
            'type' => 'view',
        ]);

        $this->assertDatabaseHas('permissions', [
            'name' => 'users.view',
            'display_name' => '檢視使用者',
            'module' => 'users',
            'type' => 'view',
        ]);
    }

    /** @test */
    public function it_has_roles_relationship()
    {
        $permission = Permission::factory()->create();
        $role = Role::factory()->create();
        
        $permission->roles()->attach($role->id);
        
        $this->assertTrue($permission->roles->contains($role));
        $this->assertEquals(1, $permission->role_count);
    }

    /** @test */
    public function it_has_dependencies_relationship()
    {
        $permission = Permission::factory()->create(['name' => 'users.edit']);
        $dependency = Permission::factory()->create(['name' => 'users.view']);
        
        $permission->dependencies()->attach($dependency->id);
        
        $this->assertTrue($permission->dependencies->contains($dependency));
        $this->assertTrue($dependency->dependents->contains($permission));
    }

    /** @test */
    public function it_can_add_dependency()
    {
        $permission = Permission::factory()->create(['name' => 'users.edit']);
        $dependency = Permission::factory()->create(['name' => 'users.view']);
        
        $permission->addDependency($dependency);
        
        $this->assertTrue($permission->dependsOn($dependency));
        $this->assertTrue($permission->dependencies->contains($dependency));
    }

    /** @test */
    public function it_can_remove_dependency()
    {
        $permission = Permission::factory()->create(['name' => 'users.edit']);
        $dependency = Permission::factory()->create(['name' => 'users.view']);
        
        $permission->addDependency($dependency);
        $this->assertTrue($permission->dependsOn($dependency));
        
        $permission->removeDependency($dependency);
        $this->assertFalse($permission->dependsOn($dependency));
    }

    /** @test */
    public function it_can_sync_dependencies()
    {
        $permission = Permission::factory()->create(['name' => 'users.delete']);
        $dep1 = Permission::factory()->create(['name' => 'users.view']);
        $dep2 = Permission::factory()->create(['name' => 'users.edit']);
        $dep3 = Permission::factory()->create(['name' => 'users.create']);
        
        // 先新增一些依賴
        $permission->addDependency($dep1);
        $permission->addDependency($dep3);
        
        // 同步依賴（應該移除 dep3，保留 dep1，新增 dep2）
        $permission->syncDependencies([$dep1->id, $dep2->id]);
        
        $permission->refresh();
        $this->assertTrue($permission->dependsOn($dep1));
        $this->assertTrue($permission->dependsOn($dep2));
        $this->assertFalse($permission->dependsOn($dep3));
    }

    /** @test */
    public function it_prevents_circular_dependency()
    {
        $permA = Permission::factory()->create(['name' => 'perm.a']);
        $permB = Permission::factory()->create(['name' => 'perm.b']);
        
        // A 依賴 B
        $permA->addDependency($permB);
        
        // B 不能依賴 A（會形成循環）
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('不能建立循環依賴關係');
        
        $permB->addDependency($permA);
    }

    /** @test */
    public function it_prevents_self_dependency()
    {
        $permission = Permission::factory()->create(['name' => 'self.test']);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('不能建立循環依賴關係');
        
        $permission->addDependency($permission);
    }

    /** @test */
    public function it_detects_complex_circular_dependency()
    {
        $permA = Permission::factory()->create(['name' => 'perm.a']);
        $permB = Permission::factory()->create(['name' => 'perm.b']);
        $permC = Permission::factory()->create(['name' => 'perm.c']);
        
        // A -> B -> C
        $permA->addDependency($permB);
        $permB->addDependency($permC);
        
        // C 不能依賴 A（會形成循環：A -> B -> C -> A）
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('不能建立循環依賴關係');
        
        $permC->addDependency($permA);
    }

    /** @test */
    public function it_gets_all_dependencies_recursively()
    {
        $permA = Permission::factory()->create(['name' => 'perm.a']);
        $permB = Permission::factory()->create(['name' => 'perm.b']);
        $permC = Permission::factory()->create(['name' => 'perm.c']);
        $permD = Permission::factory()->create(['name' => 'perm.d']);
        
        // A -> B -> C
        // A -> D
        $permA->addDependency($permB);
        $permA->addDependency($permD);
        $permB->addDependency($permC);
        
        $allDependencies = $permA->getAllDependencies();
        
        $this->assertCount(3, $allDependencies);
        $this->assertTrue($allDependencies->contains('name', 'perm.b'));
        $this->assertTrue($allDependencies->contains('name', 'perm.c'));
        $this->assertTrue($allDependencies->contains('name', 'perm.d'));
    }

    /** @test */
    public function it_gets_all_dependents_recursively()
    {
        $permA = Permission::factory()->create(['name' => 'perm.a']);
        $permB = Permission::factory()->create(['name' => 'perm.b']);
        $permC = Permission::factory()->create(['name' => 'perm.c']);
        $permD = Permission::factory()->create(['name' => 'perm.d']);
        
        // B -> A
        // C -> A
        // D -> C -> A
        $permB->addDependency($permA);
        $permC->addDependency($permA);
        $permD->addDependency($permC);
        
        $allDependents = $permA->getAllDependents();
        
        $this->assertCount(3, $allDependents);
        $this->assertTrue($allDependents->contains('name', 'perm.b'));
        $this->assertTrue($allDependents->contains('name', 'perm.c'));
        $this->assertTrue($allDependents->contains('name', 'perm.d'));
    }

    /** @test */
    public function it_identifies_system_permissions()
    {
        $systemPerm = Permission::factory()->create([
            'name' => 'system.config',
            'module' => 'system'
        ]);
        
        $authPerm = Permission::factory()->create([
            'name' => 'auth.login',
            'module' => 'auth'
        ]);
        
        $userPerm = Permission::factory()->create([
            'name' => 'users.view',
            'module' => 'users'
        ]);
        
        $this->assertTrue($systemPerm->is_system_permission);
        $this->assertTrue($authPerm->is_system_permission);
        $this->assertFalse($userPerm->is_system_permission);
    }

    /** @test */
    public function it_determines_if_permission_can_be_deleted()
    {
        // 系統權限不能刪除
        $systemPerm = Permission::factory()->create([
            'name' => 'system.config',
            'module' => 'system'
        ]);
        $this->assertFalse($systemPerm->can_be_deleted);
        
        // 被角色使用的權限不能刪除
        $usedPerm = Permission::factory()->create(['name' => 'test.used.view']);
        $role = Role::factory()->create();
        $usedPerm->roles()->attach($role->id);
        $this->assertFalse($usedPerm->can_be_deleted);
        
        // 被其他權限依賴的權限不能刪除
        $dependedPerm = Permission::factory()->create(['name' => 'test.depended.view']);
        $dependentPerm = Permission::factory()->create(['name' => 'test.dependent.edit']);
        $dependentPerm->addDependency($dependedPerm);
        $this->assertFalse($dependedPerm->can_be_deleted);
        
        // 普通未使用權限可以刪除
        $normalPerm = Permission::factory()->create([
            'name' => 'custom.feature',
            'module' => 'custom'
        ]);
        $this->assertTrue($normalPerm->can_be_deleted);
    }

    /** @test */
    public function it_calculates_user_count_through_roles()
    {
        $permission = Permission::factory()->create();
        $role1 = Role::factory()->create();
        $role2 = Role::factory()->create();
        
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();
        
        // 權限分配給兩個角色
        $permission->roles()->attach([$role1->id, $role2->id]);
        
        // 使用者分配角色
        $role1->users()->attach([$user1->id, $user2->id]);
        $role2->users()->attach([$user2->id, $user3->id]); // user2 有兩個角色
        
        // 應該計算出 3 個不重複的使用者
        $this->assertEquals(3, $permission->user_count);
    }

    /** @test */
    public function it_provides_usage_statistics()
    {
        $permission = Permission::factory()->create();
        $role = Role::factory()->create();
        $user = User::factory()->create();
        
        $permission->roles()->attach($role->id);
        $role->users()->attach($user->id);
        
        $stats = $permission->usage_stats;
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('role_count', $stats);
        $this->assertArrayHasKey('user_count', $stats);
        $this->assertArrayHasKey('is_used', $stats);
        $this->assertArrayHasKey('usage_frequency', $stats);
        $this->assertArrayHasKey('dependency_count', $stats);
        $this->assertArrayHasKey('dependent_count', $stats);
        
        $this->assertEquals(1, $stats['role_count']);
        $this->assertEquals(1, $stats['user_count']);
        $this->assertTrue($stats['is_used']);
    }

    /** @test */
    public function it_checks_if_permission_is_in_use()
    {
        $permission = Permission::factory()->create();
        $this->assertFalse($permission->isInUse());
        $this->assertFalse($permission->isUsed());
        
        $role = Role::factory()->create();
        $permission->roles()->attach($role->id);
        
        $permission->refresh();
        $this->assertTrue($permission->isInUse());
        $this->assertTrue($permission->isUsed());
    }

    /** @test */
    public function it_groups_permissions_by_module()
    {
        Permission::factory()->create(['module' => 'users', 'name' => 'users.view']);
        Permission::factory()->create(['module' => 'users', 'name' => 'users.create']);
        Permission::factory()->create(['module' => 'roles', 'name' => 'roles.view']);
        
        $grouped = Permission::groupedByModule();
        
        $this->assertCount(2, $grouped);
        $this->assertCount(2, $grouped['users']);
        $this->assertCount(1, $grouped['roles']);
    }

    /** @test */
    public function it_gets_permissions_by_module()
    {
        Permission::factory()->create(['module' => 'users', 'name' => 'users.view']);
        Permission::factory()->create(['module' => 'users', 'name' => 'users.create']);
        Permission::factory()->create(['module' => 'roles', 'name' => 'roles.view']);
        
        $userPermissions = Permission::getByModule('users');
        
        $this->assertCount(2, $userPermissions);
        $this->assertTrue($userPermissions->every(fn($p) => $p->module === 'users'));
    }

    /** @test */
    public function it_clears_cache_when_dependencies_change()
    {
        $permission = Permission::factory()->create();
        $dependency = Permission::factory()->create();
        
        // 觸發快取
        $permission->getAllDependencies();
        $this->assertTrue(Cache::has("permission_all_dependencies_{$permission->id}"));
        
        // 新增依賴應該清除快取
        $permission->addDependency($dependency);
        $this->assertFalse(Cache::has("permission_all_dependencies_{$permission->id}"));
    }

    /** @test */
    public function it_handles_localized_display_names()
    {
        $permission = Permission::factory()->create([
            'name' => 'users.view',
            'display_name' => 'View Users'
        ]);
        
        // 如果沒有翻譯，應該返回原始 display_name
        $this->assertEquals('View Users', $permission->localized_display_name);
    }

    /** @test */
    public function it_handles_localized_descriptions()
    {
        $permission = Permission::factory()->create([
            'name' => 'users.view',
            'description' => 'View user list and details'
        ]);
        
        // 如果沒有翻譯，應該返回原始 description
        $this->assertEquals('View user list and details', $permission->localized_description);
    }
}