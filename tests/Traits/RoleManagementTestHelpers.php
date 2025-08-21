<?php

namespace Tests\Traits;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * 角色管理測試輔助方法
 */
trait RoleManagementTestHelpers
{
    /**
     * 建立具有完整權限的管理員使用者
     */
    protected function createAdminUser(): User
    {
        $adminRole = Role::factory()->create([
            'name' => 'admin',
            'display_name' => '系統管理員',
            'is_system_role' => true
        ]);
        
        $permissions = [
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'permissions.view', 'permissions.create', 'permissions.edit', 'permissions.delete'
        ];
        
        foreach ($permissions as $permissionName) {
            $permission = Permission::factory()->create(['name' => $permissionName]);
            $adminRole->permissions()->attach($permission->id);
        }
        
        $user = User::factory()->create([
            'name' => '測試管理員',
            'email' => 'admin@test.com'
        ]);
        $user->roles()->attach($adminRole->id);
        
        return $user;
    }

    /**
     * 建立具有限制權限的使用者
     */
    protected function createLimitedUser(array $permissions = ['roles.view']): User
    {
        $role = Role::factory()->create([
            'name' => 'limited_user',
            'display_name' => '限制使用者'
        ]);
        
        foreach ($permissions as $permissionName) {
            $permission = Permission::factory()->create(['name' => $permissionName]);
            $role->permissions()->attach($permission->id);
        }
        
        $user = User::factory()->create([
            'name' => '測試限制使用者',
            'email' => 'limited@test.com'
        ]);
        $user->roles()->attach($role->id);
        
        return $user;
    }

    /**
     * 建立無權限的使用者
     */
    protected function createUnauthorizedUser(): User
    {
        return User::factory()->create([
            'name' => '無權限使用者',
            'email' => 'unauthorized@test.com'
        ]);
    }

    /**
     * 建立角色層級結構
     */
    protected function createRoleHierarchy(int $depth = 3, int $permissionsPerLevel = 2): Collection
    {
        $roles = collect();
        $parentId = null;
        
        for ($i = 0; $i < $depth; $i++) {
            $role = Role::factory()->create([
                'name' => "level_{$i}",
                'display_name' => "層級 {$i}",
                'parent_id' => $parentId
            ]);
            
            // 為每個層級添加權限
            $permissions = Permission::factory()->count($permissionsPerLevel)->create([
                'module' => "level_{$i}_module"
            ]);
            $role->permissions()->attach($permissions->pluck('id'));
            
            $roles->push($role);
            $parentId = $role->id;
        }
        
        return $roles;
    }

    /**
     * 建立複雜的角色權限結構
     */
    protected function createComplexRoleStructure(): array
    {
        // 建立權限模組
        $modules = ['users', 'roles', 'posts', 'comments', 'settings'];
        $actions = ['view', 'create', 'edit', 'delete'];
        
        $permissions = collect();
        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $permission = Permission::factory()->create([
                    'name' => "{$module}.{$action}",
                    'display_name' => ucfirst($action) . ' ' . ucfirst($module),
                    'module' => $module
                ]);
                $permissions->push($permission);
            }
        }
        
        // 建立角色結構
        $superAdmin = Role::factory()->create([
            'name' => 'super_admin',
            'display_name' => '超級管理員',
            'is_system_role' => true
        ]);
        $superAdmin->permissions()->attach($permissions->pluck('id'));
        
        $admin = Role::factory()->create([
            'name' => 'admin',
            'display_name' => '管理員',
            'parent_id' => $superAdmin->id
        ]);
        $admin->permissions()->attach($permissions->where('module', '!=', 'settings')->pluck('id'));
        
        $moderator = Role::factory()->create([
            'name' => 'moderator',
            'display_name' => '版主',
            'parent_id' => $admin->id
        ]);
        $moderator->permissions()->attach($permissions->whereIn('module', ['posts', 'comments'])->pluck('id'));
        
        $editor = Role::factory()->create([
            'name' => 'editor',
            'display_name' => '編輯者',
            'parent_id' => $moderator->id
        ]);
        $editor->permissions()->attach($permissions->where('module', 'posts')->where('name', '!=', 'posts.delete')->pluck('id'));
        
        return [
            'roles' => compact('superAdmin', 'admin', 'moderator', 'editor'),
            'permissions' => $permissions,
            'modules' => $modules
        ];
    }

    /**
     * 驗證權限繼承
     */
    protected function assertPermissionInheritance(Role $child, Role $parent): void
    {
        $parentPermissions = $parent->permissions->pluck('id')->toArray();
        $childAllPermissions = $child->getAllPermissions()->pluck('id')->toArray();
        
        foreach ($parentPermissions as $permissionId) {
            $this->assertContains($permissionId, $childAllPermissions,
                "子角色 '{$child->name}' 應該繼承父角色 '{$parent->name}' 的權限 ID: {$permissionId}");
        }
    }

    /**
     * 驗證角色統計資訊
     */
    protected function assertRoleStats(Role $role, array $expectedStats): void
    {
        if (isset($expectedStats['permissions_count'])) {
            $this->assertEquals($expectedStats['permissions_count'], $role->permissions()->count(),
                "角色 '{$role->name}' 的權限數量不符合預期");
        }
        
        if (isset($expectedStats['users_count'])) {
            $this->assertEquals($expectedStats['users_count'], $role->users()->count(),
                "角色 '{$role->name}' 的使用者數量不符合預期");
        }
        
        if (isset($expectedStats['children_count'])) {
            $this->assertEquals($expectedStats['children_count'], $role->children()->count(),
                "角色 '{$role->name}' 的子角色數量不符合預期");
        }
    }

    /**
     * 建立測試用的批量角色
     */
    protected function createBulkRoles(int $count = 10, bool $withPermissions = true): Collection
    {
        $roles = collect();
        
        for ($i = 0; $i < $count; $i++) {
            $role = Role::factory()->create([
                'name' => "bulk_role_{$i}",
                'display_name' => "批量角色 {$i}"
            ]);
            
            if ($withPermissions) {
                $permissions = Permission::factory()->count(rand(1, 5))->create();
                $role->permissions()->attach($permissions->pluck('id'));
            }
            
            $roles->push($role);
        }
        
        return $roles;
    }

    /**
     * 建立測試用的大量資料
     */
    protected function createLargeDataSet(): array
    {
        // 建立大量權限
        $permissions = Permission::factory()->count(100)->create();
        
        // 建立大量角色
        $roles = Role::factory()->count(50)->create();
        
        // 隨機分配權限給角色
        $roles->each(function (Role $role) use ($permissions) {
            $randomPermissions = $permissions->random(rand(5, 20));
            $role->permissions()->attach($randomPermissions->pluck('id'));
        });
        
        // 建立大量使用者
        $users = User::factory()->count(200)->create();
        
        // 隨機分配角色給使用者
        $users->each(function (User $user) use ($roles) {
            $randomRoles = $roles->random(rand(1, 3));
            $user->roles()->attach($randomRoles->pluck('id'));
        });
        
        return [
            'permissions' => $permissions,
            'roles' => $roles,
            'users' => $users
        ];
    }

    /**
     * 清理測試資料
     */
    protected function cleanupTestData(): void
    {
        // 清理關聯表
        \DB::table('role_user')->truncate();
        \DB::table('permission_role')->truncate();
        
        // 清理主要資料表
        User::whereNotNull('id')->delete();
        Role::whereNotNull('id')->delete();
        Permission::whereNotNull('id')->delete();
    }

    /**
     * 測量執行時間
     */
    protected function measureExecutionTime(callable $callback): array
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        $result = $callback();
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        
        return [
            'result' => $result,
            'execution_time' => $endTime - $startTime,
            'memory_used' => $endMemory - $startMemory,
            'peak_memory' => $peakMemory,
            'start_memory' => $startMemory,
            'end_memory' => $endMemory
        ];
    }

    /**
     * 驗證效能指標
     */
    protected function assertPerformanceMetrics(array $metrics, array $limits): void
    {
        if (isset($limits['max_execution_time'])) {
            $this->assertLessThan($limits['max_execution_time'], $metrics['execution_time'],
                "執行時間 {$metrics['execution_time']}s 超過限制 {$limits['max_execution_time']}s");
        }
        
        if (isset($limits['max_memory_usage'])) {
            $this->assertLessThan($limits['max_memory_usage'], $metrics['memory_used'],
                "記憶體使用量 {$metrics['memory_used']} bytes 超過限制 {$limits['max_memory_usage']} bytes");
        }
        
        if (isset($limits['max_peak_memory'])) {
            $this->assertLessThan($limits['max_peak_memory'], $metrics['peak_memory'],
                "峰值記憶體 {$metrics['peak_memory']} bytes 超過限制 {$limits['max_peak_memory']} bytes");
        }
    }

    /**
     * 模擬並發操作
     */
    protected function simulateConcurrentOperations(array $operations, int $concurrency = 5): array
    {
        $results = [];
        $chunks = array_chunk($operations, $concurrency);
        
        foreach ($chunks as $chunk) {
            $chunkResults = [];
            foreach ($chunk as $operation) {
                try {
                    $chunkResults[] = [
                        'success' => true,
                        'result' => $operation(),
                        'error' => null
                    ];
                } catch (\Exception $e) {
                    $chunkResults[] = [
                        'success' => false,
                        'result' => null,
                        'error' => $e->getMessage()
                    ];
                }
            }
            $results = array_merge($results, $chunkResults);
        }
        
        return $results;
    }

    /**
     * 驗證資料庫一致性
     */
    protected function assertDatabaseConsistency(): void
    {
        // 檢查孤立的權限關聯
        $orphanedPermissionRoles = \DB::table('permission_role')
            ->leftJoin('roles', 'permission_role.role_id', '=', 'roles.id')
            ->leftJoin('permissions', 'permission_role.permission_id', '=', 'permissions.id')
            ->whereNull('roles.id')
            ->orWhereNull('permissions.id')
            ->count();
        
        $this->assertEquals(0, $orphanedPermissionRoles, '發現孤立的權限-角色關聯');
        
        // 檢查孤立的使用者角色關聯
        $orphanedUserRoles = \DB::table('role_user')
            ->leftJoin('roles', 'role_user.role_id', '=', 'roles.id')
            ->leftJoin('users', 'role_user.user_id', '=', 'users.id')
            ->whereNull('roles.id')
            ->orWhereNull('users.id')
            ->count();
        
        $this->assertEquals(0, $orphanedUserRoles, '發現孤立的使用者-角色關聯');
        
        // 檢查循環依賴
        $rolesWithParents = Role::whereNotNull('parent_id')->get();
        foreach ($rolesWithParents as $role) {
            $this->assertFalse($role->hasCircularDependency($role->parent_id),
                "角色 '{$role->name}' 存在循環依賴");
        }
    }
}