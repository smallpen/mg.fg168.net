<?php

namespace App\Contracts;

use App\Models\Agent;
use App\Models\Player;

/**
 * 點數服務介面
 * 
 * 定義點數管理的核心業務邏輯介面
 */
interface PointServiceInterface
{
    /**
     * 分配點數給代理
     * 
     * @param Agent $agent 目標代理
     * @param float $amount 分配金額
     * @param Agent|null $fromAgent 來源代理（null表示系統分配）
     */
    public function allocatePointsToAgent(Agent $agent, float $amount, ?Agent $fromAgent = null): void;

    /**
     * 分配點數給玩家
     * 
     * @param Player $player 目標玩家
     * @param float $amount 分配金額
     * @param Agent $fromAgent 來源代理
     */
    public function allocatePointsToPlayer(Player $player, float $amount, Agent $fromAgent): void;

    /**
     * 從代理回收點數
     * 
     * @param Agent $agent 來源代理
     * @param float $amount 回收金額
     * @param Agent $toAgent 目標代理
     */
    public function recoverPointsFromAgent(Agent $agent, float $amount, Agent $toAgent): void;

    /**
     * 從玩家回收點數
     * 
     * @param Player $player 來源玩家
     * @param float $amount 回收金額
     * @param Agent $toAgent 目標代理
     */
    public function recoverPointsFromPlayer(Player $player, float $amount, Agent $toAgent): void;

    /**
     * 系統調整點數（管理員功能）
     * 
     * @param Agent|Player $target 目標對象
     * @param float $amount 調整金額（正數為增加，負數為減少）
     * @param string $reason 調整原因
     */
    public function systemAdjustPoints($target, float $amount, string $reason = ''): void;

    /**
     * 取得點數統計資訊
     * 
     * @param Agent|null $agent 指定代理（null表示全系統統計）
     * @return array 統計資訊
     */
    public function getPointsStatistics(?Agent $agent = null): array;

    /**
     * 點數稽核檢查
     * 
     * @return array 稽核結果
     */
    public function auditPoints(): array;

    /**
     * 轉移玩家到新代理
     * 
     * @param \App\Models\Player $player 玩家
     * @param Agent $newAgent 新代理
     */
    public function transferPlayerToNewAgent(\App\Models\Player $player, Agent $newAgent): void;

    /**
     * 批量分配點數給多個代理
     * 
     * @param array $allocations 分配資料
     * @param Agent|null $fromAgent 來源代理
     */
    public function batchAllocateToAgents(array $allocations, ?Agent $fromAgent = null): void;

    /**
     * 批量分配點數給多個玩家
     * 
     * @param array $allocations 分配資料
     * @param Agent $fromAgent 來源代理
     */
    public function batchAllocateToPlayers(array $allocations, Agent $fromAgent): void;

    /**
     * 取得點數交易歷史
     * 
     * @param Agent|\App\Models\Player|null $target 目標對象
     * @param int $limit 限制數量
     * @param array $filters 篩選條件
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getTransactionHistory($target = null, int $limit = 50, array $filters = []);

    /**
     * 修復點數不一致問題
     * 
     * @param array $issues 稽核發現的問題
     * @return array 修復結果
     */
    public function fixPointsInconsistencies(array $issues): array;
}