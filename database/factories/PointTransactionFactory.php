<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\Player;
use App\Models\PointTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PointTransaction>
 */
class PointTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = $this->faker->randomFloat(2, -10000, 10000);
        $balanceBefore = $this->faker->randomFloat(2, 0, 50000);
        
        return [
            'agent_id' => null,
            'player_id' => null,
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
            'created_by' => User::factory(),
        ];
    }

    /**
     * 代理相關交易
     */
    public function forAgent(Agent $agent): static
    {
        return $this->state(fn (array $attributes) => [
            'agent_id' => $agent->id,
            'player_id' => null,
        ]);
    }

    /**
     * 玩家相關交易
     */
    public function forPlayer(Player $player): static
    {
        return $this->state(fn (array $attributes) => [
            'agent_id' => null,
            'player_id' => $player->id,
        ]);
    }

    /**
     * 代理點數分配交易
     */
    public function agentAllocation(): static
    {
        return $this->state(function (array $attributes) {
            $amount = $this->faker->randomFloat(2, 100, 10000);
            $balanceBefore = $this->faker->randomFloat(2, 0, 50000);
            
            return [
                'type' => PointTransaction::TYPE_AGENT_ALLOCATION,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $amount,
                'description' => '代理點數分配',
            ];
        });
    }

    /**
     * 代理點數回收交易
     */
    public function agentRecovery(): static
    {
        return $this->state(function (array $attributes) {
            $amount = $this->faker->randomFloat(2, -10000, -100);
            $balanceBefore = $this->faker->randomFloat(2, 1000, 50000);
            
            return [
                'type' => PointTransaction::TYPE_AGENT_RECOVERY,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $amount,
                'description' => '代理點數回收',
            ];
        });
    }

    /**
     * 玩家點數分配交易
     */
    public function playerAllocation(): static
    {
        return $this->state(function (array $attributes) {
            $amount = $this->faker->randomFloat(2, 50, 5000);
            $balanceBefore = $this->faker->randomFloat(2, 0, 20000);
            
            return [
                'type' => PointTransaction::TYPE_PLAYER_ALLOCATION,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $amount,
                'description' => '玩家點數分配',
            ];
        });
    }

    /**
     * 玩家點數回收交易
     */
    public function playerRecovery(): static
    {
        return $this->state(function (array $attributes) {
            $amount = $this->faker->randomFloat(2, -5000, -50);
            $balanceBefore = $this->faker->randomFloat(2, 500, 20000);
            
            return [
                'type' => PointTransaction::TYPE_PLAYER_RECOVERY,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $amount,
                'description' => '玩家點數回收',
            ];
        });
    }

    /**
     * 玩家點數消費交易
     */
    public function playerConsumption(): static
    {
        return $this->state(function (array $attributes) {
            $amount = $this->faker->randomFloat(2, -2000, -10);
            $balanceBefore = $this->faker->randomFloat(2, 100, 10000);
            
            return [
                'type' => PointTransaction::TYPE_PLAYER_CONSUMPTION,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $amount,
                'description' => '玩家點數消費',
            ];
        });
    }

    /**
     * 系統調整交易
     */
    public function systemAdjustment(): static
    {
        return $this->state(function (array $attributes) {
            $amount = $this->faker->randomFloat(2, -5000, 5000);
            $balanceBefore = $this->faker->randomFloat(2, 0, 30000);
            
            return [
                'type' => PointTransaction::TYPE_SYSTEM_ADJUSTMENT,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $amount,
                'description' => '系統點數調整',
            ];
        });
    }

    /**
     * 正向交易（增加點數）
     */
    public function positive(): static
    {
        return $this->state(function (array $attributes) {
            $amount = $this->faker->randomFloat(2, 10, 10000);
            $balanceBefore = $this->faker->randomFloat(2, 0, 50000);
            
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
            $amount = $this->faker->randomFloat(2, -10000, -10);
            $balanceBefore = $this->faker->randomFloat(2, 1000, 50000);
            
            return [
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $amount,
            ];
        });
    }

    /**
     * 設定參考ID
     */
    public function withReference($referenceId): static
    {
        return $this->state(fn (array $attributes) => [
            'reference_id' => $referenceId,
        ]);
    }
}
