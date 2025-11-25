<?php

namespace Database\Factories;

use App\Models\PointTransaction;
use App\Models\Agent;
use App\Models\Player;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PointTransaction>
 */
class PointTransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PointTransaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = $this->faker->randomFloat(2, -1000, 1000);
        $balanceBefore = $this->faker->randomFloat(2, 0, 5000);
        
        return [
            'type' => $this->faker->randomElement([
                PointTransaction::TYPE_AGENT_ALLOCATION,
                PointTransaction::TYPE_AGENT_RECOVERY,
                PointTransaction::TYPE_PLAYER_ALLOCATION,
                PointTransaction::TYPE_PLAYER_RECOVERY,
                PointTransaction::TYPE_PLAYER_CONSUMPTION,
                PointTransaction::TYPE_SYSTEM_ADJUSTMENT,
            ]),
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceBefore + $amount,
            'description' => $this->faker->sentence(),
            'reference_id' => $this->faker->optional()->randomNumber(),
            'created_by' => null, // Will be set by the test
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'updated_at' => now(),
        ];
    }

    /**
     * 代理相關交易
     */
    public function forAgent(?Agent $agent = null): static
    {
        return $this->state(function (array $attributes) use ($agent) {
            return [
                'agent_id' => $agent?->id ?? Agent::factory(),
                'player_id' => null,
                'type' => $this->faker->randomElement([
                    PointTransaction::TYPE_AGENT_ALLOCATION,
                    PointTransaction::TYPE_AGENT_RECOVERY,
                    PointTransaction::TYPE_SYSTEM_ADJUSTMENT,
                ]),
            ];
        });
    }

    /**
     * 玩家相關交易
     */
    public function forPlayer(?Player $player = null): static
    {
        return $this->state(function (array $attributes) use ($player) {
            return [
                'agent_id' => null,
                'player_id' => $player?->id ?? Player::factory(),
                'type' => $this->faker->randomElement([
                    PointTransaction::TYPE_PLAYER_ALLOCATION,
                    PointTransaction::TYPE_PLAYER_RECOVERY,
                    PointTransaction::TYPE_PLAYER_CONSUMPTION,
                    PointTransaction::TYPE_SYSTEM_ADJUSTMENT,
                ]),
            ];
        });
    }

    /**
     * 點數分配交易
     */
    public function allocation(): static
    {
        return $this->state(function (array $attributes) {
            $amount = $this->faker->randomFloat(2, 1, 1000);
            $balanceBefore = $this->faker->randomFloat(2, 0, 2000);
            
            return [
                'type' => $this->faker->randomElement([
                    PointTransaction::TYPE_AGENT_ALLOCATION,
                    PointTransaction::TYPE_PLAYER_ALLOCATION,
                ]),
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $amount,
                'description' => '點數分配',
            ];
        });
    }

    /**
     * 點數回收交易
     */
    public function recovery(): static
    {
        return $this->state(function (array $attributes) {
            $amount = $this->faker->randomFloat(2, -1000, -1);
            $balanceBefore = $this->faker->randomFloat(2, 1000, 3000);
            
            return [
                'type' => $this->faker->randomElement([
                    PointTransaction::TYPE_AGENT_RECOVERY,
                    PointTransaction::TYPE_PLAYER_RECOVERY,
                ]),
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $amount,
                'description' => '點數回收',
            ];
        });
    }

    /**
     * 玩家消費交易
     */
    public function consumption(): static
    {
        return $this->state(function (array $attributes) {
            $amount = $this->faker->randomFloat(2, -500, -1);
            $balanceBefore = $this->faker->randomFloat(2, 500, 2000);
            
            return [
                'player_id' => Player::factory(),
                'agent_id' => null,
                'type' => PointTransaction::TYPE_PLAYER_CONSUMPTION,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $amount,
                'description' => '遊戲消費',
            ];
        });
    }

    /**
     * 系統調整交易
     */
    public function systemAdjustment(): static
    {
        return $this->state(function (array $attributes) {
            $amount = $this->faker->randomFloat(2, -500, 500);
            $balanceBefore = $this->faker->randomFloat(2, 0, 2000);
            
            return [
                'type' => PointTransaction::TYPE_SYSTEM_ADJUSTMENT,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $amount,
                'description' => '系統調整 - ' . $this->faker->sentence(3),
            ];
        });
    }

    /**
     * 玩家轉移交易
     */
    public function playerTransfer(): static
    {
        return $this->state(function (array $attributes) {
            $balanceBefore = $this->faker->randomFloat(2, 100, 1000);
            
            return [
                'player_id' => Player::factory(),
                'agent_id' => null,
                'type' => PointTransaction::TYPE_PLAYER_TRANSFER,
                'amount' => 0, // 轉移時玩家點數不變
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore,
                'description' => '玩家代理轉移',
            ];
        });
    }

    /**
     * 正向交易（增加點數）
     */
    public function positive(): static
    {
        return $this->state(function (array $attributes) {
            $amount = $this->faker->randomFloat(2, 1, 1000);
            $balanceBefore = $this->faker->randomFloat(2, 0, 2000);
            
            return [
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $amount,
            ];
        });
    }

    /**
     * 負向交易（減少點數）
     */
    public function negative(): static
    {
        return $this->state(function (array $attributes) {
            $amount = $this->faker->randomFloat(2, -1000, -1);
            $balanceBefore = $this->faker->randomFloat(2, 1000, 3000);
            
            return [
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $amount,
            ];
        });
    }

    /**
     * 大額交易
     */
    public function largeAmount(): static
    {
        return $this->state(function (array $attributes) {
            $amount = $this->faker->randomFloat(2, 5000, 50000);
            $balanceBefore = $this->faker->randomFloat(2, 10000, 100000);
            
            return [
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $amount,
                'description' => '大額' . $attributes['description'] ?? '交易',
            ];
        });
    }

    /**
     * 最近的交易
     */
    public function recent(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'created_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
                'updated_at' => now(),
            ];
        });
    }

    /**
     * 歷史交易
     */
    public function historical(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'created_at' => $this->faker->dateTimeBetween('-1 year', '-1 month'),
                'updated_at' => $this->faker->dateTimeBetween('-1 year', '-1 month'),
            ];
        });
    }
}