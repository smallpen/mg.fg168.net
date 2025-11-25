<?php

namespace App\Contracts;

use App\Models\Player;
use App\Models\Agent;

/**
 * 玩家服務介面
 * 
 * 定義玩家管理的核心業務邏輯介面
 */
interface PlayerServiceInterface
{
    /**
     * 建立新玩家
     * 
     * @param array $data 玩家資料
     * @return Player 建立的玩家實例
     */
    public function createPlayer(array $data): Player;

    /**
     * 更新玩家資料
     * 
     * @param Player $player 要更新的玩家
     * @param array $data 更新資料
     * @return Player 更新後的玩家實例
     */
    public function updatePlayer(Player $player, array $data): Player;

    /**
     * 刪除玩家
     * 
     * @param Player $player 要刪除的玩家
     * @return bool 是否刪除成功
     */
    public function deletePlayer(Player $player): bool;

    /**
     * 取得玩家統計資訊
     * 
     * @param Player $player 玩家實例
     * @return array 統計資訊
     */
    public function getPlayerStatistics(Player $player): array;

    /**
     * 檢查玩家是否可以進行遊戲
     * 
     * @param Player $player 玩家實例
     * @param float $requiredPoints 需要的點數
     * @return bool 是否可以進行遊戲
     */
    public function canPlay(Player $player, float $requiredPoints = 0): bool;

    /**
     * 玩家點數消費
     * 
     * @param Player $player 玩家實例
     * @param float $amount 消費金額
     * @param string $description 消費描述
     * @return bool 是否消費成功
     */
    public function consumePoints(Player $player, float $amount, string $description = ''): bool;

    /**
     * 批次更新玩家狀態
     * 
     * @param array $playerIds 玩家ID陣列
     * @param array $data 更新資料
     * @return int 更新的玩家數量
     */
    public function batchUpdatePlayers(array $playerIds, array $data): int;

    /**
     * 取得代理的所有玩家統計
     * 
     * @param Agent $agent 代理實例
     * @return array 統計資訊
     */
    public function getAgentPlayersStatistics(Agent $agent): array;
}