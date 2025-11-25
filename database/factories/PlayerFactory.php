<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Player>
 */
class PlayerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $username = 'player' . $this->faker->unique()->randomNumber(5);
        
        return [
            'name' => $this->faker->name(),
            'username' => $username,
            'account' => $username, // 將在建立時根據代理前置符號更新
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->optional()->phoneNumber(),
            'agent_id' => null, // Will be set by the test
            'points' => $this->faker->randomFloat(2, 100, 10000),
            'is_active' => $this->faker->boolean(95), // 95% 機率為啟用
            'created_by' => null, // Will be set by the test
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * 指定隸屬代理
     */
    public function forAgent(Agent $agent): static
    {
        return $this->state(function (array $attributes) use ($agent) {
            $username = $attributes['username'];
            
            return [
                'agent_id' => $agent->id,
                'account' => $agent->full_prefix . $username,
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
    public function withPoints(float $points): static
    {
        return $this->state(fn (array $attributes) => [
            'points' => $points,
        ]);
    }

    /**
     * 設定為無點數
     */
    public function withoutPoints(): static
    {
        return $this->state(fn (array $attributes) => [
            'points' => 0,
        ]);
    }

    /**
     * 設定為高點數玩家
     */
    public function highPoints(): static
    {
        return $this->state(fn (array $attributes) => [
            'points' => $this->faker->randomFloat(2, 50000, 200000),
        ]);
    }

    /**
     * 設定為低點數玩家
     */
    public function lowPoints(): static
    {
        return $this->state(fn (array $attributes) => [
            'points' => $this->faker->randomFloat(2, 10, 500),
        ]);
    }

    /**
     * 建立後處理
     */
    public function configure(): static
    {
        return $this->afterCreating(function ($player) {
            // 如果沒有指定代理，確保帳號格式正確
            if ($player->agent && !str_starts_with($player->account, $player->agent->full_prefix)) {
                $player->update([
                    'account' => $player->agent->full_prefix . $player->username
                ]);
            }
        });
    }
}
