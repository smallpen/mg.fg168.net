<?php

namespace App\Rules\ChannelManagement;

use App\Models\Agent;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * 點數驗證規則
 * 
 * 驗證點數操作是否符合系統要求：
 * - 點數必須為正數
 * - 分配點數時檢查來源是否有足夠點數
 * - 回收點數時檢查目標是否有足夠點數
 * 
 * 對應需求: 11.3, 11.4, 12.3, 12.5, 14.2, 15.3, 15.5
 */
class PointsValidationRule implements ValidationRule
{
    /**
     * 驗證類型：allocate（分配）或 recover（回收）
     */
    private string $validationType;

    /**
     * 來源代理 ID（用於分配點數時檢查）
     */
    private ?int $sourceAgentId;

    /**
     * 目標代理或玩家 ID（用於回收點數時檢查）
     */
    private ?int $targetId;

    /**
     * 目標類型：agent 或 player
     */
    private string $targetType;

    /**
     * 建立新的點數驗證規則實例
     *
     * @param string $validationType 驗證類型 ('allocate' 或 'recover')
     * @param int|null $sourceAgentId 來源代理 ID
     * @param int|null $targetId 目標 ID
     * @param string $targetType 目標類型 ('agent' 或 'player')
     */
    public function __construct(
        string $validationType = 'allocate',
        ?int $sourceAgentId = null,
        ?int $targetId = null,
        string $targetType = 'agent'
    ) {
        $this->validationType = $validationType;
        $this->sourceAgentId = $sourceAgentId;
        $this->targetId = $targetId;
        $this->targetType = $targetType;
    }

    /**
     * 執行驗證規則
     *
     * @param string $attribute 屬性名稱
     * @param mixed $value 要驗證的值
     * @param Closure $fail 失敗回調函數
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // 檢查是否為數字
        if (!is_numeric($value)) {
            $fail('點數必須是數字。');
            return;
        }

        $points = (float) $value;

        // 檢查是否為無窮大或 NaN
        if (!is_finite($points)) {
            $fail('點數必須是有效的數字。');
            return;
        }

        // 檢查是否為正數
        if ($points <= 0) {
            $fail('點數必須大於 0。');
            return;
        }

        // 檢查小數位數（最多 2 位）
        if (round($points, 2) !== $points) {
            $fail('點數最多只能有 2 位小數。');
            return;
        }

        // 根據驗證類型執行相應檢查
        if ($this->validationType === 'allocate') {
            $this->validateAllocation($points, $fail);
        } elseif ($this->validationType === 'recover') {
            $this->validateRecovery($points, $fail);
        }
    }

    /**
     * 驗證點數分配
     *
     * @param float $points 要分配的點數
     * @param Closure $fail 失敗回調函數
     * @return void
     */
    private function validateAllocation(float $points, Closure $fail): void
    {
        if (!$this->sourceAgentId) {
            return; // 如果沒有指定來源代理，跳過檢查（可能是系統分配）
        }

        $sourceAgent = Agent::find($this->sourceAgentId);
        if (!$sourceAgent) {
            $fail('來源代理不存在。');
            return;
        }

        if ($sourceAgent->remaining_points < $points) {
            $available = number_format($sourceAgent->remaining_points, 2);
            $requested = number_format($points, 2);
            $fail("代理「{$sourceAgent->name}」剩餘點數不足。需要 {$requested} 點，但只有 {$available} 點可用。");
        }
    }

    /**
     * 驗證點數回收
     *
     * @param float $points 要回收的點數
     * @param Closure $fail 失敗回調函數
     * @return void
     */
    private function validateRecovery(float $points, Closure $fail): void
    {
        if (!$this->targetId) {
            return; // 如果沒有指定目標，跳過檢查
        }

        if ($this->targetType === 'agent') {
            $target = Agent::find($this->targetId);
            if (!$target) {
                $fail('目標代理不存在。');
                return;
            }

            if ($target->remaining_points < $points) {
                $available = number_format($target->remaining_points, 2);
                $requested = number_format($points, 2);
                $fail("代理「{$target->name}」剩餘點數不足。要回收 {$requested} 點，但只有 {$available} 點可回收。");
            }
        } elseif ($this->targetType === 'player') {
            $target = \App\Models\Player::find($this->targetId);
            if (!$target) {
                $fail('目標玩家不存在。');
                return;
            }

            if ($target->points < $points) {
                $available = number_format($target->points, 2);
                $requested = number_format($points, 2);
                $fail("玩家「{$target->name}」點數不足。要回收 {$requested} 點，但只有 {$available} 點可回收。");
            }
        }
    }

    /**
     * 檢查代理是否有足夠點數進行分配
     *
     * @param int $agentId 代理 ID
     * @param float $points 要分配的點數
     * @return bool
     */
    public static function canAllocate(int $agentId, float $points): bool
    {
        try {
            $agent = Agent::find($agentId);
            return $agent && $agent->remaining_points >= $points;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 檢查代理是否有足夠點數進行回收
     *
     * @param int $agentId 代理 ID
     * @param float $points 要回收的點數
     * @return bool
     */
    public static function canRecoverFromAgent(int $agentId, float $points): bool
    {
        try {
            $agent = Agent::find($agentId);
            return $agent && $agent->remaining_points >= $points;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 檢查玩家是否有足夠點數進行回收
     *
     * @param int $playerId 玩家 ID
     * @param float $points 要回收的點數
     * @return bool
     */
    public static function canRecoverFromPlayer(int $playerId, float $points): bool
    {
        try {
            $player = \App\Models\Player::find($playerId);
            return $player && $player->points >= $points;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 取得代理的可用點數
     *
     * @param int $agentId 代理 ID
     * @return float
     */
    public static function getAvailablePoints(int $agentId): float
    {
        try {
            $agent = Agent::find($agentId);
            return $agent ? $agent->remaining_points : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
}