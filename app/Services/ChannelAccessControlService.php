<?php

namespace App\Services;

use App\Models\User;
use App\Models\Agent;
use App\Models\Player;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * 通路管理存取控制服務
 * 
 * 提供基於角色的資料存取控制邏輯
 * 實作代理自主管理的權限檢查
 */
class ChannelAccessControlService
{
    /**
     * 根據使用者權限限制代理查詢範圍
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \App\Models\User|null  $user
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAgentsForUser(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            return $query->whereRaw('1 = 0'); // 返回空結果
        }

        // 系統管理員可以看到所有代理
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return $query;
        }

        // 有代理自主管理權限的使用者只能看到自己管轄的代理
        if ($user->hasPermission('channels.agents.self_manage')) {
            $userAgent = $this->getUserAgent($user);
            if ($userAgent) {
                return $this->scopeSubordinateAgents($query, $userAgent);
            }
        }

        // 其他使用者無法看到任何代理
        return $query->whereRaw('1 = 0');
    }

    /**
     * 根據使用者權限限制玩家查詢範圍
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \App\Models\User|null  $user
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePlayersForUser(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // 系統管理員可以看到所有玩家
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return $query;
        }

        // 有代理自主管理權限的使用者只能看到自己管轄的玩家
        if ($user->hasPermission('channels.agents.self_manage')) {
            $userAgent = $this->getUserAgent($user);
            if ($userAgent) {
                return $this->scopeSubordinatePlayers($query, $userAgent);
            }
        }

        // 其他使用者無法看到任何玩家
        return $query->whereRaw('1 = 0');
    }

    /**
     * 限制查詢範圍為指定代理的下層代理（包含自己）
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \App\Models\Agent  $agent
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSubordinateAgents(Builder $query, Agent $agent): Builder
    {
        $subordinateIds = $this->getSubordinateAgentIds($agent);
        $subordinateIds[] = $agent->id; // 包含自己

        return $query->whereIn('id', $subordinateIds);
    }

    /**
     * 限制查詢範圍為指定代理管轄的玩家
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \App\Models\Agent  $agent
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSubordinatePlayers(Builder $query, Agent $agent): Builder
    {
        $subordinateAgentIds = $this->getSubordinateAgentIds($agent);
        $subordinateAgentIds[] = $agent->id; // 包含自己

        return $query->whereIn('agent_id', $subordinateAgentIds);
    }

    /**
     * 檢查使用者是否可以存取指定代理
     *
     * @param  \App\Models\Agent  $agent
     * @param  \App\Models\User|null  $user
     * @return bool
     */
    public function canAccessAgent(Agent $agent, ?User $user = null): bool
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            return false;
        }

        // 系統管理員可以存取所有代理
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        // 檢查代理自主管理權限
        if ($user->hasPermission('channels.agents.self_manage')) {
            $userAgent = $this->getUserAgent($user);
            if ($userAgent) {
                return $agent->id === $userAgent->id || 
                       $this->isSubordinateAgent($userAgent, $agent);
            }
        }

        return false;
    }

    /**
     * 檢查使用者是否可以存取指定玩家
     *
     * @param  \App\Models\Player  $player
     * @param  \App\Models\User|null  $user
     * @return bool
     */
    public function canAccessPlayer(Player $player, ?User $user = null): bool
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            return false;
        }

        // 系統管理員可以存取所有玩家
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        // 檢查代理自主管理權限
        if ($user->hasPermission('channels.agents.self_manage')) {
            $userAgent = $this->getUserAgent($user);
            if ($userAgent && $player->agent) {
                return $player->agent_id === $userAgent->id || 
                       $this->isSubordinateAgent($userAgent, $player->agent);
            }
        }

        return false;
    }

    /**
     * 檢查使用者是否可以建立下層代理
     *
     * @param  \App\Models\Agent|null  $parentAgent
     * @param  \App\Models\User|null  $user
     * @return bool
     */
    public function canCreateSubAgent(?Agent $parentAgent, ?User $user = null): bool
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            return false;
        }

        // 系統管理員可以建立任何層級的代理
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        // 檢查代理自主管理權限
        if ($user->hasPermission('channels.agents.self_manage') && $parentAgent) {
            $userAgent = $this->getUserAgent($user);
            return $userAgent && $userAgent->id === $parentAgent->id;
        }

        return false;
    }

    /**
     * 檢查使用者是否可以建立玩家
     *
     * @param  \App\Models\Agent  $agent
     * @param  \App\Models\User|null  $user
     * @return bool
     */
    public function canCreatePlayer(Agent $agent, ?User $user = null): bool
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            return false;
        }

        // 系統管理員可以為任何代理建立玩家
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        // 檢查代理自主管理權限
        if ($user->hasPermission('channels.agents.self_manage')) {
            $userAgent = $this->getUserAgent($user);
            if ($userAgent) {
                return $agent->id === $userAgent->id || 
                       $this->isSubordinateAgent($userAgent, $agent);
            }
        }

        return false;
    }

    /**
     * 取得使用者對應的代理記錄
     *
     * @param  \App\Models\User  $user
     * @return \App\Models\Agent|null
     */
    public function getUserAgent(User $user): ?Agent
    {
        // 根據實際的使用者-代理關聯邏輯來實作
        // 這裡提供幾種可能的匹配方式：

        // 方式1: 透過 email 匹配
        $agent = Agent::where('email', $user->email)->first();
        if ($agent) {
            return $agent;
        }

        // 方式2: 透過 username 匹配
        $agent = Agent::where('username', $user->username)->first();
        if ($agent) {
            return $agent;
        }

        // 方式3: 透過使用者自定義欄位匹配（如果有的話）
        // $agent = Agent::where('user_id', $user->id)->first();

        return null;
    }

    /**
     * 取得指定代理的所有下層代理ID
     *
     * @param  \App\Models\Agent  $agent
     * @return array
     */
    private function getSubordinateAgentIds(Agent $agent): array
    {
        $subordinateIds = [];
        $this->collectSubordinateIds($agent, $subordinateIds);
        return $subordinateIds;
    }

    /**
     * 遞迴收集下層代理ID
     *
     * @param  \App\Models\Agent  $agent
     * @param  array  &$subordinateIds
     * @return void
     */
    private function collectSubordinateIds(Agent $agent, array &$subordinateIds): void
    {
        $children = Agent::where('parent_id', $agent->id)->get();
        
        foreach ($children as $child) {
            $subordinateIds[] = $child->id;
            $this->collectSubordinateIds($child, $subordinateIds);
        }
    }

    /**
     * 檢查是否為下層代理
     *
     * @param  \App\Models\Agent  $ancestor
     * @param  \App\Models\Agent  $descendant
     * @return bool
     */
    private function isSubordinateAgent(Agent $ancestor, Agent $descendant): bool
    {
        if ($descendant->parent_id === $ancestor->id) {
            return true;
        }

        if ($descendant->parent_id) {
            $parent = Agent::find($descendant->parent_id);
            if ($parent) {
                return $this->isSubordinateAgent($ancestor, $parent);
            }
        }

        return false;
    }

    /**
     * 取得使用者可管理的代理選項
     *
     * @param  \App\Models\User|null  $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getManageableAgents(?User $user = null)
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            return collect();
        }

        // 系統管理員可以管理所有代理
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return Agent::where('is_active', true)
                        ->orderBy('level')
                        ->orderBy('name')
                        ->get();
        }

        // 代理只能管理自己的下層
        if ($user->hasPermission('channels.agents.self_manage')) {
            $userAgent = $this->getUserAgent($user);
            if ($userAgent) {
                $query = Agent::where('is_active', true);
                $this->scopeSubordinateAgents($query, $userAgent);
                return $query->orderBy('level')->orderBy('name')->get();
            }
        }

        return collect();
    }
}