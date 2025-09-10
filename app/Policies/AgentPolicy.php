<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Agent;
use App\Services\ChannelAccessControlService;

/**
 * 代理管理權限策略
 * 
 * 定義代理相關操作的權限檢查邏輯
 */
class AgentPolicy
{
    protected ChannelAccessControlService $accessControl;

    public function __construct(ChannelAccessControlService $accessControl)
    {
        $this->accessControl = $accessControl;
    }

    /**
     * 檢查使用者是否可以檢視代理列表
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('channels.agents.view');
    }

    /**
     * 檢查使用者是否可以檢視特定代理
     */
    public function view(User $user, Agent $agent): bool
    {
        return $user->hasPermission('channels.agents.view') && 
               $this->accessControl->canAccessAgent($agent, $user);
    }

    /**
     * 檢查使用者是否可以建立代理
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('channels.agents.create');
    }

    /**
     * 檢查使用者是否可以建立下層代理
     */
    public function createSubAgent(User $user, ?Agent $parentAgent = null): bool
    {
        if (!$user->hasPermission('channels.agents.create')) {
            return false;
        }

        return $this->accessControl->canCreateSubAgent($parentAgent, $user);
    }

    /**
     * 檢查使用者是否可以編輯代理
     */
    public function update(User $user, Agent $agent): bool
    {
        return $user->hasPermission('channels.agents.edit') && 
               $this->accessControl->canAccessAgent($agent, $user);
    }

    /**
     * 檢查使用者是否可以刪除代理
     */
    public function delete(User $user, Agent $agent): bool
    {
        return $user->hasPermission('channels.agents.delete') && 
               $this->accessControl->canAccessAgent($agent, $user);
    }

    /**
     * 檢查使用者是否可以管理代理層級結構
     */
    public function manageHierarchy(User $user, Agent $agent): bool
    {
        return $user->hasPermission('channels.agents.manage_hierarchy') && 
               $this->accessControl->canAccessAgent($agent, $user);
    }

    /**
     * 檢查使用者是否可以分配點數給代理
     */
    public function allocatePoints(User $user, Agent $agent): bool
    {
        return $user->hasPermission('channels.points.allocate') && 
               $this->accessControl->canAccessAgent($agent, $user);
    }

    /**
     * 檢查使用者是否可以從代理回收點數
     */
    public function recoverPoints(User $user, Agent $agent): bool
    {
        return $user->hasPermission('channels.points.recover') && 
               $this->accessControl->canAccessAgent($agent, $user);
    }

    /**
     * 檢查使用者是否可以匯出代理資料
     */
    public function export(User $user): bool
    {
        return $user->hasPermission('channels.agents.export');
    }

    /**
     * 檢查使用者是否可以進行代理自主管理
     */
    public function selfManage(User $user, Agent $agent): bool
    {
        if (!$user->hasPermission('channels.agents.self_manage')) {
            return false;
        }

        $userAgent = $this->accessControl->getUserAgent($user);
        return $userAgent && $userAgent->id === $agent->id;
    }

    /**
     * 檢查使用者是否可以管理指定代理的下層
     */
    public function manageSubordinates(User $user, Agent $agent): bool
    {
        if (!$user->hasPermission('channels.agents.self_manage')) {
            return false;
        }

        return $this->accessControl->canAccessAgent($agent, $user);
    }
}