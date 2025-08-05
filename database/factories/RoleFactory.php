<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Role>
 */
class RoleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Role::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->slug(2);
        
        return [
            'name' => $name,
            'display_name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * 建立系統角色
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => '系統角色',
        ]);
    }

    /**
     * 建立停用角色
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * 建立管理員角色
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'admin',
            'display_name' => '管理員',
            'description' => '系統管理員角色',
        ]);
    }

    /**
     * 建立超級管理員角色
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'super_admin',
            'display_name' => '超級管理員',
            'description' => '超級管理員角色',
        ]);
    }

    /**
     * 建立一般使用者角色
     */
    public function user(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'user',
            'display_name' => '一般使用者',
            'description' => '一般使用者角色',
        ]);
    }
}