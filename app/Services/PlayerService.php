<?php

namespace App\Services;

use App\Contracts\PlayerServiceInterface;
use App\Models\Player;
use App\Models\Agent;
use App\Models\User;
use App\Exceptions\InsufficientPointsException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * 玩家服務類別
 * 
 * 負責處理玩家的建立、更新、刪除等核心業務邏輯
 * 包含代理關聯管理、點數分配等功能
 */
class PlayerService implements PlayerServiceInterface
{
    public function __construct(
        private PointService $pointService,
        private ActivityLogger $activityLogger
    ) {}

    /**
     * 建立新玩家
     * 
     * @param array $data 玩家資料
     * @return Player 建立的玩家實例
     * @throws InsufficientPointsException 代理點數不足
     */
    public function createPlayer(array $data): Player
    {
        DB::beginTransaction();
        
        try {
            $agent = Agent::findOrFail($data['agent_id']);
            
            // 設定完整帳號（繼承代理前置符號 + 底線 + 使用者名稱）
            $data['account'] = $agent->full_prefix . '_' . $data['username'];

            // 設定建立者
            $data['created_by'] = auth()->user()->id;

            // 建立玩家
            $player = Player::create($data);

            // 分配初始點數
            if (isset($data['initial_points']) && $data['initial_points'] > 0) {
                $this->pointService->allocatePointsToPlayer(
                    $player,
                    $data['initial_points'],
                    $agent
                );
            }

            // 記錄活動日誌
            $this->activityLogger->log('player_created', "建立玩家：{$player->name}", [
                'module' => 'players',
                'subject_id' => $player->id,
                'subject_type' => Player::class,
                'properties' => [
                    'player_name' => $player->name,
                    'player_username' => $player->username,
                    'agent_name' => $agent->name,
                    'agent_id' => $agent->id,
                    'initial_points' => $data['initial_points'] ?? 0,
                ],
            ]);

            DB::commit();
            
            Log::info('玩家建立成功', [
                'player_id' => $player->id,
                'player_name' => $player->name,
                'agent_id' => $agent->id,
                'agent_name' => $agent->name,
                'created_by' => auth()->user()->username ?? 'system',
            ]);

            return $player;

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('玩家建立失敗', [
                'error' => $e->getMessage(),
                'data' => $data,
                'user' => auth()->user()->username ?? 'system',
            ]);
            
            throw $e;
        }
    }

    /**
     * 更新玩家資料
     * 
     * @param Player $player 要更新的玩家
     * @param array $data 更新資料
     * @return Player 更新後的玩家實例
     */
    public function updatePlayer(Player $player, array $data): Player
    {
        DB::beginTransaction();
        
        try {
            $originalData = $player->toArray();
            $originalAgent = $player->agent;

            // 處理代理變更
            if (isset($data['agent_id']) && $data['agent_id'] !== $player->agent_id) {
                $newAgent = Agent::findOrFail($data['agent_id']);
                $data['account'] = $newAgent->full_prefix . '_' . $player->username;
                
                // 轉移點數到新代理
                $this->transferPlayerToNewAgent($player, $newAgent);
            }

            // 處理用戶名變更
            if (isset($data['username']) && $data['username'] !== $player->username) {
                $currentAgent = isset($data['agent_id']) 
                    ? Agent::find($data['agent_id']) 
                    : $player->agent;
                $data['account'] = $currentAgent->full_prefix . '_' . $data['username'];
            }

            $player->update($data);

            // 記錄活動日誌
            $this->activityLogger->log('player_updated', "更新玩家：{$player->name}", [
                'module' => 'players',
                'subject_id' => $player->id,
                'subject_type' => Player::class,
                'properties' => [
                    'changes' => array_diff_assoc($data, $originalData),
                    'original_agent' => $originalAgent->name,
                    'new_agent' => $player->agent->name,
                ],
            ]);

            DB::commit();
            
            Log::info('玩家更新成功', [
                'player_id' => $player->id,
                'player_name' => $player->name,
                'updated_by' => auth()->user()->username ?? 'system',
            ]);

            return $player;

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('玩家更新失敗', [
                'player_id' => $player->id,
                'error' => $e->getMessage(),
                'data' => $data,
                'user' => auth()->user()->username ?? 'system',
            ]);
            
            throw $e;
        }
    }

    /**
     * 刪除玩家
     * 
     * @param Player $player 要刪除的玩家
     * @return bool 是否刪除成功
     */
    public function deletePlayer(Player $player): bool
    {
        DB::beginTransaction();
        
        try {
            $agent = $player->agent;
            $remainingPoints = $player->points;

            // 回收剩餘點數給隸屬代理
            if ($remainingPoints > 0) {
                $this->pointService->recoverPointsFromPlayer(
                    $player,
                    $remainingPoints,
                    $agent
                );
            }

            // 軟刪除玩家
            $player->delete();

            // 記錄活動日誌
            $this->activityLogger->log('player_deleted', "刪除玩家：{$player->name}", [
                'module' => 'players',
                'subject_id' => $player->id,
                'subject_type' => Player::class,
                'properties' => [
                    'player_name' => $player->name,
                    'agent_name' => $agent->name,
                    'recovered_points' => $remainingPoints,
                ],
            ]);

            DB::commit();
            
            Log::info('玩家刪除成功', [
                'player_id' => $player->id,
                'player_name' => $player->name,
                'agent_name' => $agent->name,
                'deleted_by' => auth()->user()->username ?? 'system',
            ]);

            return true;

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('玩家刪除失敗', [
                'player_id' => $player->id,
                'error' => $e->getMessage(),
                'user' => auth()->user()->username ?? 'system',
            ]);
            
            throw $e;
        }
    }

    /**
     * 轉移玩家到新代理
     * 
     * @param Player $player 玩家實例
     * @param Agent $newAgent 新代理
     * @throws \Exception 玩家還有點數時無法轉移
     */
    private function transferPlayerToNewAgent(Player $player, Agent $newAgent): void
    {
        $oldAgent = $player->agent;
        $playerPoints = $player->points ?? 0;

        // 檢查玩家是否還有點數
        if ($playerPoints > 0) {
            throw new \Exception(
                "無法變更隸屬代理：玩家「{$player->name}」目前還有 " . number_format($playerPoints, 2) . " 點數。" .
                "請先回收所有點數後再變更隸屬代理。"
            );
        }

        Log::info('玩家代理轉移', [
            'player_id' => $player->id,
            'player_name' => $player->name,
            'old_agent' => $oldAgent->name,
            'new_agent' => $newAgent->name,
            'player_points' => 0,
            'note' => '玩家點數為 0，允許轉移代理',
        ]);
    }

    /**
     * 取得玩家統計資訊
     * 
     * @param Player $player 玩家實例
     * @return array 統計資訊
     */
    public function getPlayerStatistics(Player $player): array
    {
        // 確保玩家已經存在於資料庫中
        if (!$player->exists) {
            return [
                'current_points' => 0,
                'total_transactions' => 0,
                'total_received' => 0,
                'total_spent' => 0,
                'agent_path' => '',
                'agent_level' => 0,
                'account_age_days' => 0,
                'last_transaction_date' => null,
            ];
        }

        try {
            $transactions = $player->pointTransactions();
            
            return [
                'current_points' => $player->points ?? 0,
                'total_transactions' => $transactions->count(),
                'total_received' => $transactions->positive()->sum('amount') ?? 0,
                'total_spent' => abs($transactions->negative()->sum('amount') ?? 0),
                'agent_path' => $player->agent_path_string ?? '',
                'agent_level' => $player->agent?->level ?? 0,
                'account_age_days' => $player->created_at ? $player->created_at->diffInDays(now()) : 0,
                'last_transaction_date' => $transactions->latest()->first()?->created_at,
            ];
        } catch (\Exception $e) {
            Log::error('取得玩家統計資訊失敗', [
                'player_id' => $player->id ?? null,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'current_points' => 0,
                'total_transactions' => 0,
                'total_received' => 0,
                'total_spent' => 0,
                'agent_path' => '',
                'agent_level' => 0,
                'account_age_days' => 0,
                'last_transaction_date' => null,
            ];
        }
    }

    /**
     * 檢查玩家是否可以進行遊戲
     * 
     * @param Player $player 玩家實例
     * @param float $requiredPoints 需要的點數
     * @return bool 是否可以進行遊戲
     */
    public function canPlay(Player $player, float $requiredPoints = 0): bool
    {
        return $player->is_active && $player->canDeductPoints($requiredPoints);
    }

    /**
     * 玩家點數消費
     * 
     * @param Player $player 玩家實例
     * @param float $amount 消費金額
     * @param string $description 消費描述
     * @return bool 是否消費成功
     */
    public function consumePoints(Player $player, float $amount, string $description = ''): bool
    {
        if (!$player->canDeductPoints($amount)) {
            throw new InsufficientPointsException("玩家 {$player->name} 點數不足，無法消費 {$amount} 點");
        }

        DB::beginTransaction();
        
        try {
            $balanceBefore = $player->points;
            $player->deductPoints($amount);

            // 記錄點數交易
            $player->pointTransactions()->create([
                'type' => 'player_consumption',
                'amount' => -$amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $player->points,
                'description' => $description ?: "玩家消費",
                'created_by' => auth()->user()->id,
            ]);

            // 記錄活動日誌
            $this->activityLogger->log('player_points_consumed', "玩家點數消費：{$player->name}", [
                'module' => 'players',
                'subject_id' => $player->id,
                'subject_type' => Player::class,
                'properties' => [
                    'amount' => $amount,
                    'description' => $description,
                    'balance_after' => $player->points,
                ],
            ]);

            DB::commit();
            
            Log::info('玩家點數消費', [
                'player_id' => $player->id,
                'player_name' => $player->name,
                'amount' => $amount,
                'balance_after' => $player->points,
            ]);

            return true;

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('玩家點數消費失敗', [
                'player_id' => $player->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * 批次更新玩家狀態
     * 
     * @param array $playerIds 玩家ID陣列
     * @param array $data 更新資料
     * @return int 更新的玩家數量
     */
    public function batchUpdatePlayers(array $playerIds, array $data): int
    {
        DB::beginTransaction();
        
        try {
            $updatedCount = Player::whereIn('id', $playerIds)->update($data);

            // 記錄活動日誌
            $this->activityLogger->log('players_batch_updated', "批次更新玩家：{$updatedCount} 筆", [
                'module' => 'players',
                'properties' => [
                    'player_ids' => $playerIds,
                    'updated_count' => $updatedCount,
                    'data' => $data,
                ],
            ]);

            DB::commit();
            
            Log::info('批次更新玩家', [
                'player_count' => $updatedCount,
                'data' => $data,
                'updated_by' => auth()->user()->username ?? 'system',
            ]);

            return $updatedCount;

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('批次更新玩家失敗', [
                'player_ids' => $playerIds,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * 取得代理的所有玩家統計
     * 
     * @param Agent $agent 代理實例
     * @return array 統計資訊
     */
    public function getAgentPlayersStatistics(Agent $agent): array
    {
        $players = $agent->players();
        
        return [
            'total_players' => $players->count(),
            'active_players' => $players->where('is_active', true)->count(),
            'inactive_players' => $players->where('is_active', false)->count(),
            'total_player_points' => $players->sum('points'),
            'average_player_points' => $players->avg('points') ?? 0,
            'players_with_points' => $players->where('points', '>', 0)->count(),
            'players_without_points' => $players->where('points', '=', 0)->count(),
        ];
    }
}