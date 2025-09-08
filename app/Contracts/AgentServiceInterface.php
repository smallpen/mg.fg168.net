<?php

namespace App\Contracts;

use App\Models\Agent;

/**
 * 代理服務介面
 * 
 * 定義代理管理的核心業務邏輯介面
 */
interface AgentServiceInterface
{
    /**
     * 建立新代理
     * 
     * @param array $data 代理資料
     * @return Agent 建立的代理實例
     */
    public function createAgent(array $data): Agent;

    /**
     * 更新代理資料
     * 
     * @param Agent $agent 要更新的代理
     * @param array $data 更新資料
     * @return Agent 更新後的代理實例
     */
    public function updateAgent(Agent $agent, array $data): Agent;

    /**
     * 刪除代理
     * 
     * @param Agent $agent 要刪除的代理
     * @return bool 是否刪除成功
     */
    public function deleteAgent(Agent $agent): bool;

    /**
     * 取得代理統計資訊
     * 
     * @param Agent $agent 代理實例
     * @return array 統計資訊
     */
    public function getAgentStatistics(Agent $agent): array;

    /**
     * 檢查代理是否可以建立下層代理
     * 
     * @param Agent $agent 代理實例
     * @param float $requiredPoints 需要的點數
     * @return bool 是否可以建立
     */
    public function canCreateSubAgent(Agent $agent, float $requiredPoints = 0): bool;

    /**
     * 取得可用的前置符號列表
     * 
     * @return array 可用的前置符號
     */
    public function getAvailablePrefixes(): array;
}