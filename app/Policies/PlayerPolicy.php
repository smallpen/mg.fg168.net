<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Player;
use App\Models\Agent;
use App\Services\ChannelAccessControlService;

/**
 * 玩家管理權限策略
 * 
 * 定義玩家相關操作的權限檢查邏輯
 */
class PlayerPolicy
{
    protected ChannelAccessControlService $accessControl;

    public function __construct(ChannelAccessControlService $accessControl)
    {
        $this->accessControl = $accessControl;
    }

    /**
     * 檢查使用者是否可以檢視玩家列表
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('channels.players.view');
    }

    /**
     * 檢查使用者是否可以檢視特定玩家
     */
    public function view(User $user, Player $player): bool
    {
        return $user->hasPermission('channels.players.view') && 
               $this->accessControl->canAccessPlayer($player, $user);
    }

    /**
     * 檢查使用者是否可以建立玩家
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('channels.players.create');
    }

    /**
     * 檢查使用者是否可以為指定代理建立玩家
     */
    public function createForAgent(User $user, Agent $agent): bool
    {
        if (!$user->hasPermission('channels.players.create')) {
            return false;
        }

        return $this->accessControl->canCreatePlayer($agent, $user);
    }

    /**
     * 檢查使用者是否可以編輯玩家
     */
    public function update(User $user, Player $player): bool
    {
        return $user->hasPermission('channels.players.edit') && 
               $this->accessControl->canAccessPlayer($player, $user);
    }

    /**
     * 檢查使用者是否可以刪除玩家
     */
    public function delete(User $user, Player $player): bool
    {
        return $user->hasPermission('channels.players.delete') && 
               $this->accessControl->canAccessPlayer($player, $user);
    }

    /**
     * 檢查使用者是否可以變更玩家的隸屬代理
     */
    public function assignAgent(User $user, Player $player, Agent $newAgent): bool
    {
        if (!$user->hasPermission('channels.players.assign_agent')) {
            return false;
        }

        // 必須能存取玩家和新代理
        return $this->accessControl->canAccessPlayer($player, $user) && 
               $this->accessControl->canAccessAgent($newAgent, $user);
    }

    /**
     * 檢查使用者是否可以分配點數給玩家
     */
    public function allocatePoints(User $user, Player $player): bool
    {
        return $user->hasPermission('channels.points.allocate') && 
               $this->accessControl->canAccessPlayer($player, $user);
    }

    /**
     * 檢查使用者是否可以從玩家回收點數
     */
    public function recoverPoints(User $user, Player $player): bool
    {
        return $user->hasPermission('channels.points.recover') && 
               $this->accessControl->canAccessPlayer($player, $user);
    }

    /**
     * 檢查使用者是否可以檢視玩家的點數交易記錄
     */
    public function viewPointTransactions(User $user, Player $player): bool
    {
        return $user->hasPermission('channels.points.view') && 
               $this->accessControl->canAccessPlayer($player, $user);
    }

    /**
     * 檢查使用者是否可以批次管理玩家
     */
    public function bulkManage(User $user): bool
    {
        // 批次操作需要編輯權限，且通常限制給系統管理員
        return $user->hasPermission('channels.players.edit') && 
               $user->hasAnyRole(['super_admin', 'admin']);
    }

    /**
     * 檢查使用者是否可以匯出玩家資料
     */
    public function export(User $user): bool
    {
        return $user->hasPermission('channels.players.view') && 
               ($user->hasAnyRole(['super_admin', 'admin']) || 
                $user->hasPermission('channels.agents.self_manage'));
    }
}