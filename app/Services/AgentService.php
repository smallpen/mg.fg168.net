<?php

namespace App\Services;

use App\Contracts\AgentServiceInterface;
use App\Models\Agent;
use App\Models\User;
use App\Exceptions\InsufficientPointsException;
use App\Exceptions\InvalidPrefixException;
use App\Exceptions\PrefixAlreadyExistsException;
use App\Exceptions\AgentHasDependenciesException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * 代理服務類別
 * 
 * 負責處理代理的建立、更新、刪除等核心業務邏輯
 * 包含前置符號驗證、層級管理、點數分配等功能
 */
class AgentService implements AgentServiceInterface
{
    public function __construct(
        private PointService $pointService,
        private ActivityLogger $activityLogger
    ) {}

    /**
     * 建立新代理
     * 
     * @param array $data 代理資料
     * @return Agent 建立的代理實例
     * @throws InvalidPrefixException 前置符號格式錯誤
     * @throws PrefixAlreadyExistsException 前置符號已被使用
     * @throws InsufficientPointsException 上層代理點數不足
     */
    public function createAgent(array $data): Agent
    {
        DB::beginTransaction();
        
        try {
            // 處理前置符號和層級
            if (empty($data['parent_id'])) {
                // 第一層代理
                $this->validatePrefix($data['prefix']);
                $data['account'] = $data['prefix'] . $data['username'];
                $data['level'] = 1;
            } else {
                // 下層代理
                $parent = Agent::findOrFail($data['parent_id']);
                $data['account'] = $parent->full_prefix . $data['username'];
                $data['level'] = $parent->level + 1;
                $data['prefix'] = null; // 下層代理不需要前置符號
            }

            // 設定建立者
            $user = auth()->user();
            $data['created_by'] = $user ? $user->id : null;

            // 建立代理
            $agent = Agent::create($data);

            // 分配初始點數
            if (isset($data['initial_points']) && $data['initial_points'] > 0) {
                $fromAgent = isset($data['parent_id']) ? Agent::find($data['parent_id']) : null;
                $this->pointService->allocatePointsToAgent(
                    $agent,
                    $data['initial_points'],
                    $fromAgent
                );
            }

            // 記錄活動日誌
            $this->activityLogger->log('agent_created', "代理建立: {$agent->name}", [
                'agent_name' => $agent->name,
                'agent_level' => $agent->level,
                'initial_points' => $data['initial_points'] ?? 0,
            ]);

            DB::commit();
            
            Log::info('代理建立成功', [
                'agent_id' => $agent->id,
                'agent_name' => $agent->name,
                'level' => $agent->level,
                'created_by' => auth()->user()->username ?? 'system',
            ]);

            return $agent;

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('代理建立失敗', [
                'error' => $e->getMessage(),
                'data' => $data,
                'user' => auth()->user()->username ?? 'system',
            ]);
            
            throw $e;
        }
    }

    /**
     * 更新代理資料
     * 
     * @param Agent $agent 要更新的代理
     * @param array $data 更新資料
     * @return Agent 更新後的代理實例
     */
    public function updateAgent(Agent $agent, array $data): Agent
    {
        DB::beginTransaction();
        
        try {
            $originalData = $agent->toArray();

            // 處理帳號變更
            if (isset($data['username']) && $data['username'] !== $agent->username) {
                $data['account'] = $agent->full_prefix . $data['username'];
                
                // 更新所有下層代理和玩家的帳號
                $this->updateDescendantAccounts($agent, $data['username']);
            }

            // 處理前置符號變更（僅第一層代理）
            if ($agent->level === 1 && isset($data['prefix']) && $data['prefix'] !== $agent->prefix) {
                $this->validatePrefix($data['prefix'], $agent->id);
                $data['account'] = $data['prefix'] . $agent->username;
                
                // 更新整個代理鏈的帳號
                $this->updateDescendantAccountsWithNewPrefix($agent, $data['prefix']);
            }

            $agent->update($data);

            // 記錄活動日誌
            $this->activityLogger->log('agent_updated', "代理更新: {$agent->name}", [
                'changes' => array_diff_assoc($data, $originalData),
            ]);

            DB::commit();
            
            Log::info('代理更新成功', [
                'agent_id' => $agent->id,
                'agent_name' => $agent->name,
                'updated_by' => auth()->user()->username ?? 'system',
            ]);

            return $agent;

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('代理更新失敗', [
                'agent_id' => $agent->id,
                'error' => $e->getMessage(),
                'data' => $data,
                'user' => auth()->user()->username ?? 'system',
            ]);
            
            throw $e;
        }
    }

    /**
     * 刪除代理
     * 
     * @param Agent $agent 要刪除的代理
     * @return bool 是否刪除成功
     * @throws AgentHasDependenciesException 代理有下層關聯
     */
    public function deleteAgent(Agent $agent): bool
    {
        // 檢查是否有下層關聯
        if ($agent->hasDependencies()) {
            throw new AgentHasDependenciesException(
                "代理 {$agent->name} 有下層代理或玩家，無法刪除"
            );
        }

        DB::beginTransaction();
        
        try {
            // 回收剩餘點數給上層代理
            if ($agent->remaining_points > 0 && $agent->parent) {
                $this->pointService->recoverPointsFromAgent(
                    $agent,
                    $agent->remaining_points,
                    $agent->parent
                );
            }

            // 軟刪除代理
            $agent->delete();

            // 記錄活動日誌
            $this->activityLogger->log('agent_deleted', "代理刪除: {$agent->name}", [
                'agent_name' => $agent->name,
                'recovered_points' => $agent->remaining_points,
            ]);

            DB::commit();
            
            Log::info('代理刪除成功', [
                'agent_id' => $agent->id,
                'agent_name' => $agent->name,
                'deleted_by' => auth()->user()->username ?? 'system',
            ]);

            return true;

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('代理刪除失敗', [
                'agent_id' => $agent->id,
                'error' => $e->getMessage(),
                'user' => auth()->user()->username ?? 'system',
            ]);
            
            throw $e;
        }
    }

    /**
     * 驗證前置符號
     * 
     * @param string $prefix 前置符號
     * @param int|null $excludeId 排除的代理ID（用於更新時）
     * @throws InvalidPrefixException 前置符號格式錯誤
     * @throws PrefixAlreadyExistsException 前置符號已被使用
     */
    private function validatePrefix(string $prefix, ?int $excludeId = null): void
    {
        // 檢查格式
        if (!preg_match('/^[a-z]$/', $prefix)) {
            throw new InvalidPrefixException('前置符號必須是 a-z 的單一字母');
        }

        // 檢查是否已被使用
        $query = Agent::where('prefix', $prefix)->where('level', 1);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw new PrefixAlreadyExistsException("前置符號 '{$prefix}' 已被使用");
        }
    }

    /**
     * 更新下層代理和玩家的帳號（用戶名變更時）
     * 
     * @param Agent $agent 代理實例
     * @param string $newUsername 新的用戶名
     */
    private function updateDescendantAccounts(Agent $agent, string $newUsername): void
    {
        // 更新代理自己的帳號
        $newPrefix = $agent->level === 1 ? $agent->prefix : $agent->parent->full_prefix;
        $agent->update(['account' => $newPrefix . $newUsername]);

        // 更新所有下層代理的帳號
        $descendants = $agent->getAllDescendants();
        foreach ($descendants as $descendant) {
            $descendant->update([
                'account' => $descendant->parent->full_prefix . $descendant->username
            ]);
        }

        // 更新所有隸屬玩家的帳號
        $players = $agent->players;
        foreach ($players as $player) {
            $player->update([
                'account' => $agent->full_prefix . $player->username
            ]);
        }
    }

    /**
     * 更新下層代理和玩家的帳號（前置符號變更時）
     * 
     * @param Agent $agent 代理實例
     * @param string $newPrefix 新的前置符號
     */
    private function updateDescendantAccountsWithNewPrefix(Agent $agent, string $newPrefix): void
    {
        // 更新所有下層代理的帳號
        $descendants = $agent->getAllDescendants();
        foreach ($descendants as $descendant) {
            $descendant->update([
                'account' => $newPrefix . $descendant->username
            ]);
        }

        // 更新所有隸屬玩家的帳號
        $allPlayers = $agent->players;
        foreach ($descendants as $descendant) {
            $allPlayers = $allPlayers->merge($descendant->players);
        }

        foreach ($allPlayers as $player) {
            $player->update([
                'account' => $newPrefix . $player->username
            ]);
        }
    }

    /**
     * 取得代理統計資訊
     * 
     * @param Agent $agent 代理實例
     * @return array 統計資訊
     */
    public function getAgentStatistics(Agent $agent): array
    {
        return [
            'total_children' => $agent->children()->count(),
            'active_children' => $agent->children()->where('is_active', true)->count(),
            'total_players' => $agent->players()->count(),
            'active_players' => $agent->players()->where('is_active', true)->count(),
            'total_descendants' => $agent->getAllDescendants()->count(),
            'total_points' => $agent->total_points,
            'allocated_points' => $agent->allocated_points,
            'remaining_points' => $agent->remaining_points,
            'points_utilization_rate' => $agent->total_points > 0 
                ? round(($agent->allocated_points / $agent->total_points) * 100, 2) 
                : 0,
        ];
    }

    /**
     * 檢查代理是否可以建立下層代理
     * 
     * @param Agent $agent 代理實例
     * @param float $requiredPoints 需要的點數
     * @return bool 是否可以建立
     */
    public function canCreateSubAgent(Agent $agent, float $requiredPoints = 0): bool
    {
        return $agent->is_active && $agent->canAllocatePoints($requiredPoints);
    }

    /**
     * 取得可用的前置符號列表
     * 
     * @return array 可用的前置符號
     */
    public function getAvailablePrefixes(): array
    {
        $allPrefixes = range('a', 'z');
        $usedPrefixes = Agent::where('level', 1)
            ->whereNotNull('prefix')
            ->pluck('prefix')
            ->map(function ($prefix) {
                return strtolower($prefix); // 統一轉為小寫
            })
            ->toArray();
            
        $availablePrefixes = array_diff($allPrefixes, $usedPrefixes);
        
        Log::info('🔤 計算可用前置符號', [
            'all_prefixes_count' => count($allPrefixes),
            'used_prefixes' => $usedPrefixes,
            'available_prefixes' => array_values($availablePrefixes),
            'available_count' => count($availablePrefixes),
        ]);
        
        return array_values($availablePrefixes);
    }
}