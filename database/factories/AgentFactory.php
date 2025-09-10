<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Agent>
 */
class AgentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $username = 'agent' . $this->faker->unique()->randomNumber(5);
        
        return [
            'name' => $this->faker->firstName() . ' ' . $this->faker->lastName(),
            'username' => $username,
            'account' => $username, // 將在建立時根據前置符號更新
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->optional()->phoneNumber(),
            'prefix' => null, // 將根據層級設定
            'level' => 1, // 預設為第一層
            'parent_id' => null,
            'total_points' => $this->faker->randomFloat(2, 1000, 100000),
            'allocated_points' => 0,
            'remaining_points' => 0, // 將在 afterCreating 中計算
            'is_active' => $this->faker->boolean(90), // 90% 機率為啟用
            'created_by' => null, // Will be set by the test
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * 設定為根代理（第一層）
     */
    public function root(): static
    {
        return $this->state(function (array $attributes) {
            $prefix = $this->faker->randomLetter();
            $username = $attributes['username'];
            
            return [
                'level' => 1,
                'parent_id' => null,
                'prefix' => $prefix,
                'account' => $prefix . $username,
            ];
        });
    }

    /**
     * 設定為子代理
     */
    public function child(Agent $parent): static
    {
        return $this->state(function (array $attributes) use ($parent) {
            $username = $attributes['username'];
            
            return [
                'level' => $parent->level + 1,
                'parent_id' => $parent->id,
                'prefix' => null,
                'account' => $parent->full_prefix . $username,
            ];
        });
    }

    /**
     * 設定為啟用狀態
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * 設定為停用狀態
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * 設定指定點數
     */
    public function withPoints(float $totalPoints): static
    {
        return $this->state(fn (array $attributes) => [
            'total_points' => $totalPoints,
            'allocated_points' => 0,
            'remaining_points' => $totalPoints,
        ]);
    }

    /**
     * 設定已分配部分點數
     */
    public function withAllocatedPoints(float $totalPoints, float $allocatedPoints): static
    {
        return $this->state(fn (array $attributes) => [
            'total_points' => $totalPoints,
            'allocated_points' => $allocatedPoints,
            'remaining_points' => $totalPoints - $allocatedPoints,
        ]);
    }

    /**
     * 建立後處理
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Agent $agent) {
            // 如果 remaining_points 為 0，則設定為 total_points
            if ($agent->remaining_points == 0 && $agent->total_points > 0) {
                $agent->update([
                    'remaining_points' => $agent->total_points
                ]);
            }
        });
    }
}
