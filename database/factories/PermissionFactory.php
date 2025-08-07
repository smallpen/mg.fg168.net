<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Permission>
 */
class PermissionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Permission::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $modules = ['users', 'roles', 'permissions', 'dashboard', 'settings'];
        $actions = ['view', 'create', 'edit', 'delete'];
        
        $module = $this->faker->randomElement($modules);
        $action = $this->faker->randomElement($actions);
        $name = "{$module}.{$action}";
        
        return [
            'name' => $name,
            'display_name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'module' => $module,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * 建立使用者管理權限
     */
    public function userManagement(): static
    {
        return $this->state(fn (array $attributes) => [
            'module' => 'users',
            'name' => 'users.' . $this->faker->randomElement(['view', 'create', 'edit', 'delete']),
            'display_name' => '使用者管理',
            'description' => '使用者管理相關權限',
        ]);
    }

    /**
     * 建立角色管理權限
     */
    public function roleManagement(): static
    {
        return $this->state(fn (array $attributes) => [
            'module' => 'roles',
            'name' => 'roles.' . $this->faker->randomElement(['view', 'create', 'edit', 'delete']),
            'display_name' => '角色管理',
            'description' => '角色管理相關權限',
        ]);
    }

    /**
     * 建立儀表板權限
     */
    public function dashboard(): static
    {
        return $this->state(fn (array $attributes) => [
            'module' => 'dashboard',
            'name' => 'dashboard.view',
            'display_name' => '儀表板檢視',
            'description' => '檢視儀表板權限',
        ]);
    }

    /**
     * 建立系統設定權限
     */
    public function systemSettings(): static
    {
        return $this->state(fn (array $attributes) => [
            'module' => 'settings',
            'name' => 'settings.' . $this->faker->randomElement(['view', 'edit']),
            'display_name' => '系統設定',
            'description' => '系統設定相關權限',
        ]);
    }

    /**
     * 建立特定模組的權限
     */
    public function forModule(string $module): static
    {
        return $this->state(fn (array $attributes) => [
            'module' => $module,
            'name' => $module . '.' . $this->faker->randomElement(['view', 'create', 'edit', 'delete']),
            'display_name' => ucfirst($module) . ' 權限',
            'description' => ucfirst($module) . ' 模組相關權限',
        ]);
    }

    /**
     * 建立檢視權限
     */
    public function view(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $attributes['module'] . '.view',
            'display_name' => '檢視 ' . ucfirst($attributes['module']),
            'description' => '檢視 ' . ucfirst($attributes['module']) . ' 的權限',
        ]);
    }

    /**
     * 建立建立權限
     */
    public function createPermission(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $attributes['module'] . '.create',
            'display_name' => '建立 ' . ucfirst($attributes['module']),
            'description' => '建立 ' . ucfirst($attributes['module']) . ' 的權限',
        ]);
    }

    /**
     * 建立編輯權限
     */
    public function edit(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $attributes['module'] . '.edit',
            'display_name' => '編輯 ' . ucfirst($attributes['module']),
            'description' => '編輯 ' . ucfirst($attributes['module']) . ' 的權限',
        ]);
    }

    /**
     * 建立刪除權限
     */
    public function delete(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $attributes['module'] . '.delete',
            'display_name' => '刪除 ' . ucfirst($attributes['module']),
            'description' => '刪除 ' . ucfirst($attributes['module']) . ' 的權限',
        ]);
    }
}