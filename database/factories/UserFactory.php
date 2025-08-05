<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * 使用者模型工廠
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * 定義模型的預設狀態
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'username' => fake('en_US')->unique()->userName(),
            'name' => fake('en_US')->name(),
            'email' => fake('en_US')->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'theme_preference' => 'light',
            'locale' => 'zh_TW',
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * 指示使用者的電子郵件地址應該未驗證
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * 指示使用者應該被停用
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * 建立管理員使用者
     */
    public function admin(): static
    {
        return $this->afterCreating(function ($user) {
            $adminRole = \App\Models\Role::firstOrCreate([
                'name' => 'admin'
            ], [
                'display_name' => '管理員',
                'description' => '一般管理員'
            ]);
            
            $user->assignRole($adminRole);
        });
    }

    /**
     * 建立超級管理員使用者
     */
    public function superAdmin(): static
    {
        return $this->afterCreating(function ($user) {
            $superAdminRole = \App\Models\Role::firstOrCreate([
                'name' => 'super_admin'
            ], [
                'display_name' => '超級管理員',
                'description' => '擁有所有權限的超級管理員'
            ]);
            
            $user->assignRole($superAdminRole);
        });
    }
}