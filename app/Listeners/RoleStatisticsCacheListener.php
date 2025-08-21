<?php

namespace App\Listeners;

use App\Services\RoleStatisticsCacheManager;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Model;

/**
 * 角色統計快取監聽器
 * 
 * 監聽模型事件並自動清除相關的統計快取
 */
class RoleStatisticsCacheListener
{
    /**
     * 快取管理服務
     */
    public RoleStatisticsCacheManager $cacheManager;

    /**
     * 建立監聽器實例
     */
    public function __construct(RoleStatisticsCacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * 處理角色建立事件
     */
    public function handleRoleCreated(Role $role): void
    {
        $this->cacheManager->handleRoleCreated($role);
    }

    /**
     * 處理角色更新事件
     */
    public function handleRoleUpdated(Role $role): void
    {
        $this->cacheManager->handleRoleUpdated($role);
    }

    /**
     * 處理角色刪除事件
     */
    public function handleRoleDeleted(Role $role): void
    {
        $this->cacheManager->handleRoleDeleted($role);
    }

    /**
     * 處理權限更新事件
     */
    public function handlePermissionUpdated(Permission $permission): void
    {
        $this->cacheManager->handlePermissionUpdated($permission);
    }

    /**
     * 處理模型事件
     */
    public function handle(string $event, array $data): void
    {
        [$eventName, $model] = $data;

        if ($model instanceof Role) {
            match ($eventName) {
                'created' => $this->handleRoleCreated($model),
                'updated' => $this->handleRoleUpdated($model),
                'deleted' => $this->handleRoleDeleted($model),
                default => null,
            };
        } elseif ($model instanceof Permission) {
            match ($eventName) {
                'updated' => $this->handlePermissionUpdated($model),
                'created', 'deleted' => $this->cacheManager->clearSystemCache(),
                default => null,
            };
        }
    }
}