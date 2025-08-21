<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Setting>
 */
class SettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['basic', 'security', 'notification', 'appearance', 'integration', 'maintenance'];
        $types = ['text', 'number', 'boolean', 'select', 'email', 'url', 'textarea', 'password'];
        
        return [
            'key' => $this->faker->unique()->slug(2, '.'),
            'value' => $this->faker->word(),
            'category' => $this->faker->randomElement($categories),
            'type' => $this->faker->randomElement($types),
            'options' => [
                'required' => $this->faker->boolean(30),
                'validation' => null,
            ],
            'description' => $this->faker->sentence(),
            'default_value' => $this->faker->word(),
            'is_encrypted' => $this->faker->boolean(10),
            'is_system' => $this->faker->boolean(20),
            'is_public' => $this->faker->boolean(80),
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }

    /**
     * 系統設定狀態
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_system' => true,
        ]);
    }

    /**
     * 加密設定狀態
     */
    public function encrypted(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_encrypted' => true,
            'is_public' => false,
        ]);
    }

    /**
     * 布林類型設定
     */
    public function boolean(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'boolean',
            'value' => $this->faker->boolean(),
            'default_value' => $this->faker->boolean(),
        ]);
    }

    /**
     * 數字類型設定
     */
    public function number(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'number',
            'value' => $this->faker->numberBetween(1, 100),
            'default_value' => $this->faker->numberBetween(1, 100),
            'options' => [
                'min' => 1,
                'max' => 100,
                'validation' => 'integer|min:1|max:100',
            ],
        ]);
    }

    /**
     * 選擇類型設定
     */
    public function select(): static
    {
        $options = [
            'option1' => 'Option 1',
            'option2' => 'Option 2',
            'option3' => 'Option 3',
        ];
        
        return $this->state(fn (array $attributes) => [
            'type' => 'select',
            'value' => array_rand($options),
            'default_value' => array_rand($options),
            'options' => [
                'values' => $options,
                'validation' => 'in:' . implode(',', array_keys($options)),
            ],
        ]);
    }
}
