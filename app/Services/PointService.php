<?php

namespace App\Services;

use App\Contracts\PointServiceInterface;
use App\Models\Agent;
use App\Models\Player;
use App\Models\PointTransaction;
use App\Exceptions\InsufficientPointsException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * 點數服務類別
 * 
 * 負責處理點數分配、回收、轉移等核心業務邏輯
 * 包含代理點數管理、玩家點數管理、交易記錄等功能
 */
class PointService implements PointServiceInterface
{
    public function __construct(
        private ActivityLogger $activityLogger
    ) {}

    /**
     * 分配點數給代理
     * 
     * @param Agent $agent 目標代理
     * @param float $amount 分配金額
     * @param Agent|null $fromAgent 來源代理（null表示系統分配）
     * @throws InsufficientPointsException 來源代理點數不足
     */
    public function allocatePointsToAgent(Agent $agent, float $amount, ?Agent $fromAgent = null): void
    {
        if ($fromAgent && !$fromAgent->canAllocatePoints($amount)) {
            throw new InsufficientPointsException(
                "代理 {$fromAgent->name} 點數不足，無法分配 {$amount} 點給 {$agent->name}"
            );
        }

        DB::beginTransaction();
        
        try {
            $balanceBefore = $agent->total_points;
            
            // 更新目標代理點數
            $agent->increment('total_points', $amount);
            $agent->increment('remaining_points', $amount);

            // 從來源代理扣除點數
            if ($fromAgent) {
                $fromAgent->allocatePoints($amount);
                
                // 記錄來源代理的點數異動
                PointTransaction::create([
                    'agent_id' => $fromAgent->id,
                    'type' => PointTransaction::TYPE_AGENT_ALLOCATION,
                    'amount' => -$amount,
                    'balance_before' => $fromAgent->remaining_points + $amount,
                    'balance_after' => $fromAgent->remaining_points,
                    'description' => "分配點數給代理: {$agent->name}",
                    'reference_id' => $agent->id,
                    'created_by' => auth()->id(),
                ]);
            }

            // 記錄目標代理的點數異動
            PointTransaction::create([
                'agent_id' => $agent->id,
                'type' => PointTransaction::TYPE_AGENT_ALLOCATION,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $agent->total_points,
                'description' => $fromAgent 
                    ? "從代理 {$fromAgent->name} 獲得點數" 
                    : "系統分配點數",
                'reference_id' => $fromAgent?->id,
                'created_by' => auth()->id(),
            ]);

            // 記錄活動日誌
            $this->activityLogger->log('points_allocated_to_agent', $agent, [
                'amount' => $amount,
                'from_agent' => $fromAgent?->name ?? 'system',
                'balance_after' => $agent->total_points,
            ]);

            DB::commit();
            
            Log::info('代理點數分配成功', [
                'target_agent' => $agent->name,
                'from_agent' => $fromAgent?->name ?? 'system',
                'amount' => $amount,
                'balance_after' => $agent->total_points,
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('代理點數分配失敗', [
                'target_agent' => $agent->name,
                'from_agent' => $fromAgent?->name ?? 'system',
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * 分配點數給玩家
     * 
     * @param Player $player 目標玩家
     * @param float $amount 分配金額
     * @param Agent $fromAgent 來源代理
     * @throws InsufficientPointsException 代理點數不足
     */
    public function allocatePointsToPlayer(Player $player, float $amount, Agent $fromAgent): void
    {
        if (!$fromAgent->canAllocatePoints($amount)) {
            throw new InsufficientPointsException(
                "代理 {$fromAgent->name} 點數不足，無法分配 {$amount} 點給玩家 {$player->name}"
            );
        }

        DB::beginTransaction();
        
        try {
            $balanceBefore = $player->points;
            
            // 更新玩家點數
            $player->addPoints($amount);
            
            // 從代理扣除點數
            $fromAgent->allocatePoints($amount);

            // 記錄代理的點數異動
            PointTransaction::create([
                'agent_id' => $fromAgent->id,
                'type' => PointTransaction::TYPE_PLAYER_ALLOCATION,
                'amount' => -$amount,
                'balance_before' => $fromAgent->remaining_points + $amount,
                'balance_after' => $fromAgent->remaining_points,
                'description' => "分配點數給玩家: {$player->name}",
                'reference_id' => $player->id,
                'created_by' => auth()->id(),
            ]);

            // 記錄玩家的點數異動
            PointTransaction::create([
                'player_id' => $player->id,
                'type' => PointTransaction::TYPE_PLAYER_ALLOCATION,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $player->points,
                'description' => "從代理 {$fromAgent->name} 獲得點數",
                'reference_id' => $fromAgent->id,
                'created_by' => auth()->id(),
            ]);

            // 記錄活動日誌
            $this->activityLogger->log('points_allocated_to_player', $player, [
                'amount' => $amount,
                'from_agent' => $fromAgent->name,
                'balance_after' => $player->points,
            ]);

            DB::commit();
            
            Log::info('玩家點數分配成功', [
                'player' => $player->name,
                'from_agent' => $fromAgent->name,
                'amount' => $amount,
                'balance_after' => $player->points,
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('玩家點數分配失敗', [
                'player' => $player->name,
                'from_agent' => $fromAgent->name,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * 從代理回收點數
     * 
     * @param Agent $agent 來源代理
     * @param float $amount 回收金額
     * @param Agent $toAgent 目標代理
     * @throws InsufficientPointsException 代理剩餘點數不足
     */
    public function recoverPointsFromAgent(Agent $agent, float $amount, Agent $toAgent): void
    {
        if ($agent->remaining_points < $amount) {
            throw new InsufficientPointsException(
                "代理 {$agent->name} 剩餘點數不足，無法回收 {$amount} 點"
            );
        }

        DB::beginTransaction();
        
        try {
            // 從來源代理回收點數
            $agent->decrement('total_points', $amount);
            $agent->decrement('remaining_points', $amount);
            
            // 回收到目標代理
            $toAgent->recoverPoints($amount);

            // 記錄來源代理的點數異動
            PointTransaction::create([
                'agent_id' => $agent->id,
                'type' => PointTransaction::TYPE_AGENT_RECOVERY,
                'amount' => -$amount,
                'balance_before' => $agent->total_points + $amount,
                'balance_after' => $agent->total_points,
                'description' => "點數被代理 {$toAgent->name} 回收",
                'reference_id' => $toAgent->id,
                'created_by' => auth()->id(),
            ]);

            // 記錄目標代理的點數異動
            PointTransaction::create([
                'agent_id' => $toAgent->id,
                'type' => PointTransaction::TYPE_AGENT_RECOVERY,
                'amount' => $amount,
                'balance_before' => $toAgent->remaining_points - $amount,
                'balance_after' => $toAgent->remaining_points,
                'description' => "從代理 {$agent->name} 回收點數",
                'reference_id' => $agent->id,
                'created_by' => auth()->id(),
            ]);

            // 記錄活動日誌
            $this->activityLogger->log('points_recovered_from_agent', $agent, [
                'amount' => $amount,
                'to_agent' => $toAgent->name,
                'balance_after' => $agent->total_points,
            ]);

            DB::commit();
            
            Log::info('代理點數回收成功', [
                'from_agent' => $agent->name,
                'to_agent' => $toAgent->name,
                'amount' => $amount,
                'balance_after' => $agent->total_points,
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('代理點數回收失敗', [
                'from_agent' => $agent->name,
                'to_agent' => $toAgent->name,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * 從玩家回收點數
     * 
     * @param Player $player 來源玩家
     * @param float $amount 回收金額
     * @param Agent $toAgent 目標代理
     * @throws InsufficientPointsException 玩家點數不足
     */
    public function recoverPointsFromPlayer(Player $player, float $amount, Agent $toAgent): void
    {
        if (!$player->canDeductPoints($amount)) {
            throw new InsufficientPointsException(
                "玩家 {$player->name} 點數不足，無法回收 {$amount} 點"
            );
        }

        DB::beginTransaction();
        
        try {
            // 從玩家扣除點數
            $player->deductPoints($amount);
            
            // 回收到代理
            $toAgent->recoverPoints($amount);

            // 記錄玩家的點數異動
            PointTransaction::create([
                'player_id' => $player->id,
                'type' => PointTransaction::TYPE_PLAYER_RECOVERY,
                'amount' => -$amount,
                'balance_before' => $player->points + $amount,
                'balance_after' => $player->points,
                'description' => "點數被代理 {$toAgent->name} 回收",
                'reference_id' => $toAgent->id,
                'created_by' => auth()->id(),
            ]);

            // 記錄代理的點數異動
            PointTransaction::create([
                'agent_id' => $toAgent->id,
                'type' => PointTransaction::TYPE_PLAYER_RECOVERY,
                'amount' => $amount,
                'balance_before' => $toAgent->remaining_points - $amount,
                'balance_after' => $toAgent->remaining_points,
                'description' => "從玩家 {$player->name} 回收點數",
                'reference_id' => $player->id,
                'created_by' => auth()->id(),
            ]);

            // 記錄活動日誌
            $this->activityLogger->log('points_recovered_from_player', $player, [
                'amount' => $amount,
                'to_agent' => $toAgent->name,
                'balance_after' => $player->points,
            ]);

            DB::commit();
            
            Log::info('玩家點數回收成功', [
                'player' => $player->name,
                'to_agent' => $toAgent->name,
                'amount' => $amount,
                'balance_after' => $player->points,
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('玩家點數回收失敗', [
                'player' => $player->name,
                'to_agent' => $toAgent->name,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * 系統調整點數（管理員功能）
     * 
     * @param Agent|Player $target 目標對象
     * @param float $amount 調整金額（正數為增加，負數為減少）
     * @param string $reason 調整原因
     */
    public function systemAdjustPoints($target, float $amount, string $reason = ''): void
    {
        DB::beginTransaction();
        
        try {
            $balanceBefore = $target instanceof Agent ? $target->total_points : $target->points;
            
            if ($target instanceof Agent) {
                // 代理點數調整
                if ($amount > 0) {
                    $target->increment('total_points', $amount);
                    $target->increment('remaining_points', $amount);
                } else {
                    $adjustAmount = abs($amount);
                    if ($target->remaining_points < $adjustAmount) {
                        throw new InsufficientPointsException(
                            "代理 {$target->name} 剩餘點數不足，無法扣除 {$adjustAmount} 點"
                        );
                    }
                    $target->decrement('total_points', $adjustAmount);
                    $target->decrement('remaining_points', $adjustAmount);
                }

                // 記錄交易
                PointTransaction::create([
                    'agent_id' => $target->id,
                    'type' => PointTransaction::TYPE_SYSTEM_ADJUSTMENT,
                    'amount' => $amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $target->total_points,
                    'description' => $reason ?: '系統調整',
                    'created_by' => auth()->id(),
                ]);

            } else {
                // 玩家點數調整
                if ($amount > 0) {
                    $target->addPoints($amount);
                } else {
                    $target->deductPoints(abs($amount));
                }

                // 記錄交易
                PointTransaction::create([
                    'player_id' => $target->id,
                    'type' => PointTransaction::TYPE_SYSTEM_ADJUSTMENT,
                    'amount' => $amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $target->points,
                    'description' => $reason ?: '系統調整',
                    'created_by' => auth()->id(),
                ]);
            }

            // 記錄活動日誌
            $this->activityLogger->log('points_system_adjusted', $target, [
                'amount' => $amount,
                'reason' => $reason,
                'balance_before' => $balanceBefore,
                'balance_after' => $target instanceof Agent ? $target->total_points : $target->points,
            ]);

            DB::commit();
            
            Log::info('系統點數調整', [
                'target_type' => $target instanceof Agent ? 'agent' : 'player',
                'target_name' => $target->name,
                'amount' => $amount,
                'reason' => $reason,
                'adjusted_by' => auth()->user()->username ?? 'system',
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('系統點數調整失敗', [
                'target_type' => $target instanceof Agent ? 'agent' : 'player',
                'target_name' => $target->name,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * 取得點數統計資訊
     * 
     * @param Agent|null $agent 指定代理（null表示全系統統計）
     * @return array 統計資訊
     */
    public function getPointsStatistics(?Agent $agent = null): array
    {
        if ($agent) {
            // 代理範圍統計
            $descendants = $agent->getAllDescendants();
            $allAgents = collect([$agent])->merge($descendants);
            $allPlayers = $agent->players;
            foreach ($descendants as $descendant) {
                $allPlayers = $allPlayers->merge($descendant->players);
            }

            return [
                'total_agents' => $allAgents->count(),
                'active_agents' => $allAgents->where('is_active', true)->count(),
                'total_players' => $allPlayers->count(),
                'active_players' => $allPlayers->where('is_active', true)->count(),
                'total_agent_points' => $allAgents->sum('total_points'),
                'total_allocated_points' => $allAgents->sum('allocated_points'),
                'total_remaining_points' => $allAgents->sum('remaining_points'),
                'total_player_points' => $allPlayers->sum('points'),
                'total_system_points' => $allAgents->sum('total_points') + $allPlayers->sum('points'),
            ];
        } else {
            // 全系統統計
            return [
                'total_agents' => Agent::count(),
                'active_agents' => Agent::where('is_active', true)->count(),
                'total_players' => Player::count(),
                'active_players' => Player::where('is_active', true)->count(),
                'total_agent_points' => Agent::sum('total_points'),
                'total_allocated_points' => Agent::sum('allocated_points'),
                'total_remaining_points' => Agent::sum('remaining_points'),
                'total_player_points' => Player::sum('points'),
                'total_system_points' => Agent::sum('total_points') + Player::sum('points'),
                'total_transactions' => PointTransaction::count(),
            ];
        }
    }

    /**
     * 點數稽核檢查
     * 
     * @return array 稽核結果
     */
    public function auditPoints(): array
    {
        $issues = [];

        // 檢查代理點數一致性
        $agents = Agent::all();
        foreach ($agents as $agent) {
            $calculatedAllocated = $agent->children->sum('total_points') + $agent->players->sum('points');
            if (abs($agent->allocated_points - $calculatedAllocated) > 0.01) {
                $issues[] = [
                    'type' => 'agent_points_mismatch',
                    'agent_id' => $agent->id,
                    'agent_name' => $agent->name,
                    'recorded_allocated' => $agent->allocated_points,
                    'calculated_allocated' => $calculatedAllocated,
                    'difference' => $agent->allocated_points - $calculatedAllocated,
                ];
            }

            // 檢查剩餘點數計算
            $expectedRemaining = $agent->total_points - $agent->allocated_points;
            if (abs($agent->remaining_points - $expectedRemaining) > 0.01) {
                $issues[] = [
                    'type' => 'agent_remaining_mismatch',
                    'agent_id' => $agent->id,
                    'agent_name' => $agent->name,
                    'recorded_remaining' => $agent->remaining_points,
                    'calculated_remaining' => $expectedRemaining,
                    'difference' => $agent->remaining_points - $expectedRemaining,
                ];
            }
        }

        return [
            'total_issues' => count($issues),
            'issues' => $issues,
            'audit_time' => now(),
            'audited_by' => auth()->user()->username ?? 'system',
        ];
    }
}