<?php

namespace App\Traits;

use App\Services\ChannelAccessControlService;
use Illuminate\Database\Eloquent\Builder;

/**
 * 通路管理存取控制特徵
 * 
 * 為模型提供基於使用者權限的查詢範圍限制
 */
trait HasChannelAccessControl
{
    /**
     * 根據使用者權限限制代理查詢範圍
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \App\Models\User|null  $user
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAccessibleByUser(Builder $query, $user = null): Builder
    {
        $accessControlService = app(ChannelAccessControlService::class);

        if ($this->getTable() === 'agents') {
            return $accessControlService->scopeAgentsForUser($query, $user);
        }

        if ($this->getTable() === 'players') {
            return $accessControlService->scopePlayersForUser($query, $user);
        }

        return $query;
    }

    /**
     * 檢查使用者是否可以存取此記錄
     *
     * @param  \App\Models\User|null  $user
     * @return bool
     */
    public function isAccessibleByUser($user = null): bool
    {
        $accessControlService = app(ChannelAccessControlService::class);

        if ($this instanceof \App\Models\Agent) {
            return $accessControlService->canAccessAgent($this, $user);
        }

        if ($this instanceof \App\Models\Player) {
            return $accessControlService->canAccessPlayer($this, $user);
        }

        return false;
    }

    /**
     * 檢查使用者是否可以編輯此記錄
     *
     * @param  \App\Models\User|null  $user
     * @return bool
     */
    public function isEditableByUser($user = null): bool
    {
        // 基本的存取檢查
        if (!$this->isAccessibleByUser($user)) {
            return false;
        }

        $user = $user ?? auth()->user();

        if (!$user) {
            return false;
        }

        // 系統管理員可以編輯所有記錄
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        // 檢查相應的編輯權限
        if ($this instanceof \App\Models\Agent) {
            return $user->hasPermission('channels.agents.edit');
        }

        if ($this instanceof \App\Models\Player) {
            return $user->hasPermission('channels.players.edit');
        }

        return false;
    }

    /**
     * 檢查使用者是否可以刪除此記錄
     *
     * @param  \App\Models\User|null  $user
     * @return bool
     */
    public function isDeletableByUser($user = null): bool
    {
        // 基本的存取檢查
        if (!$this->isAccessibleByUser($user)) {
            return false;
        }

        $user = $user ?? auth()->user();

        if (!$user) {
            return false;
        }

        // 系統管理員可以刪除所有記錄
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        // 檢查相應的刪除權限
        if ($this instanceof \App\Models\Agent) {
            return $user->hasPermission('channels.agents.delete');
        }

        if ($this instanceof \App\Models\Player) {
            return $user->hasPermission('channels.players.delete');
        }

        return false;
    }
}