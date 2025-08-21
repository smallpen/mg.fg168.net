<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * 角色測試資料工廠擴充
 */
class RoleTestFactory extends Factory
{
    protected $model = Role::class;

    /**
     * 定義模型的預設狀態
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->slug(2),
            'display_name' => $this->faker->jobTitle(),
            'description' => $this->faker->sentence(),
            'is_active' => true,
            'is_system_role' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * 建立系統角色
     */
    public function systemRole(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_system_role' => true,
            'name' => 'system_' . $this->faker->unique()->word(),
        ]);
    }

    /**
     * 建立停用的角色
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * 建立具有父角色的角色
     */
    public function withParent(Role $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent->id,
        ]);
    }

    /**
     * 建立具有指定數量權限的角色
     */
    public function withPermissions(int $count = 5): static
    {
        return $this->afterCreating(function (Role $role) use ($count) {
            $permissions = Permission::factory()->count($count)->create();
            $role->permissions()->attach($permissions->pluck('id'));
        });
    }

    /**
     * 建立具有隨機權限的角色
     */
    public function withRandomPermissions(int $min = 1, int $max = 10): static
    {
        return $this->afterCreating(function (Role $role) use ($min, $max) {
            $count = $this->faker->numberBetween($min, $max);
            $permissions = Permission::factory()->count($count)->create();
            $role->permissions()->attach($permissions->pluck('id'));
        });
    }

    /**
     * 建立具有使用者的角色
     */
    public function withUsers(int $count = 3): static
    {
        return $this->afterCreating(function (Role $role) use ($count) {
            $users = \App\Models\User::factory()->count($count)->create();
            $users->each(fn($user) => $user->roles()->attach($role->id));
        });
    }

    /**
     * 建立具有特定模組權限的角色
     */
    public function withModulePermissions(string $module): static
    {
        return $this->afterCreating(function (Role $role) use ($module) {
            $actions = ['view', 'create', 'edit', 'delete'];
            foreach ($actions as $action) {
                $permission = Permission::factory()->create([
                    'name' => "{$module}.{$action}",
                    'module' => $module,
                ]);
                $role->permissions()->attach($permission->id);
            }
        });
    }

    /**
     * 建立管理員角色
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'admin',
            'display_name' => '管理員',
            'is_system_role' => true,
        ])->afterCreating(function (Role $role) {
            // 建立所有基本權限
            $modules = ['users', 'roles', 'permissions', 'system'];
            $actions = ['view', 'create', 'edit', 'delete'];
            
            foreach ($modules as $module) {
                foreach ($actions as $action) {
                    $permission = Permission::factory()->create([
                        'name' => "{$module}.{$action}",
                        'module' => $module,
                    ]);
                    $role->permissions()->attach($permission->id);
                }
            }
        });
    }

    /**
     * 建立編輯者角色
     */
    public function editor(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'editor',
            'display_name' => '編輯者',
        ])->afterCreating(function (Role $role) {
            $permissions = [
                'users.view', 'users.edit',
                'roles.view',
                'permissions.view'
            ];
            
            foreach ($permissions as $permissionName) {
                $permission = Permission::factory()->create(['name' => $permissionName]);
                $role->permissions()->attach($permission->id);
            }
        });
    }

    /**
     * 建立檢視者角色
     */
    public function viewer(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'viewer',
            'display_name' => '檢視者',
        ])->afterCreating(function (Role $role) {
            $permissions = ['users.view', 'roles.view', 'permissions.view'];
            
            foreach ($permissions as $permissionName) {
                $permission = Permission::factory()->create(['name' => $permissionName]);
                $role->permissions()->attach($permission->id);
            }
        });
    }

    /**
     * 建立角色層級結構
     */
    public function hierarchy(int $depth = 3): static
    {
        return $this->afterCreating(function (Role $role) use ($depth) {
            $currentParent = $role;
            
            for ($i = 1; $i < $depth; $i++) {
                $childRole = Role::factory()->create([
                    'name' => $role->name . "_level_{$i}",
                    'display_name' => $role->display_name . " 層級 {$i}",
                    'parent_id' => $currentParent->id,
                ]);
                
                // 為每個子層級添加一些權限
                $permissions = Permission::factory()->count(2)->create();
                $childRole->permissions()->attach($permissions->pluck('id'));
                
                $currentParent = $childRole;
            }
        });
    }

    /**
     * 建立具有循環依賴風險的角色（用於測試）
     */
    public function withCircularRisk(): static
    {
        return $this->afterCreating(function (Role $role) {
            // 建立另一個角色作為子角色
            $childRole = Role::factory()->create([
                'name' => $role->name . '_child',
                'parent_id' => $role->id,
            ]);
            
            // 儲存子角色 ID 以便測試時使用
            $role->setAttribute('test_child_id', $childRole->id);
        });
    }

    /**
     * 建立大量測試角色
     */
    public function bulk(int $count = 50): static
    {
        return $this->count($count)->afterCreating(function (Role $role, $index) {
            // 為每個角色添加隨機數量的權限
            $permissionCount = rand(1, 8);
            $permissions = Permission::factory()->count($permissionCount)->create();
            $role->permissions()->attach($permissions->pluck('id'));
            
            // 隨機分配一些使用者
            if (rand(1, 3) === 1) {
                $userCount = rand(1, 5);
                $users = \App\Models\User::factory()->count($userCount)->create();
                $users->each(fn($user) => $user->roles()->attach($role->id));
            }
        });
    }
}