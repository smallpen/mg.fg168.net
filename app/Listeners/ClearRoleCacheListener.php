<?php

namespace App\Listeners;

use App\Services\RoleCacheService;
use Illuminate\Support\Facades\Log;

/**
 * 清除角色快取監聽器
 * 
 * 監聽角色相關的模型事件，自動清除相關快取
 */
class ClearRoleCacheListener
{

    private RoleCacheService $cacheService;

    /**
     * 建立事件監聽器
     */
    public function __construct(RoleCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * 處理角色相關事件
     */
    public function handle(object $event): void
    {
        try {
            $eventClass = get_class($event);
            
            switch ($eventClass) {
                case 'Illuminate\Database\Events\QueryExecuted':
                    $this->handleQueryExecuted($event);
                    break;
                    
                default:
                    // 處理 Eloquent 模型事件
                    $this->handleModelEvent($event);
                    break;
            }
        } catch (\Exception $e) {
            Log::error('清除角色快取時發生錯誤', [
                'event' => get_class($event),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * 處理資料庫查詢事件
     */
    private function handleQueryExecuted($event): void
    {
        $sql = $event->sql;
        $bindings = $event->bindings;

        // 檢查是否為角色相關的寫入操作
        if ($this->isRoleRelatedWriteQuery($sql)) {
            Log::info('檢測到角色相關的資料庫寫入操作，清除快取', [
                'sql' => $sql,
                'bindings' => $bindings
            ]);

            // 清除所有角色相關快取
            $this->cacheService->clearAllCache();
        }
    }

    /**
     * 處理模型事件
     */
    private function handleModelEvent($event): void
    {
        if (!isset($event->model)) {
            return;
        }

        $model = $event->model;
        $modelClass = get_class($model);

        switch ($modelClass) {
            case 'App\Models\Role':
                $this->handleRoleEvent($event, $model);
                break;
                
            case 'App\Models\Permission':
                $this->handlePermissionEvent($event, $model);
                break;
                
            case 'App\Models\User':
                $this->handleUserEvent($event, $model);
                break;
        }
    }

    /**
     * 處理角色模型事件
     */
    private function handleRoleEvent($event, $role): void
    {
        $eventName = class_basename(get_class($event));
        
        Log::info("角色事件: {$eventName}", [
            'role_id' => $role->id ?? null,
            'role_name' => $role->name ?? null
        ]);

        switch ($eventName) {
            case 'Created':
            case 'Updated':
            case 'Deleted':
                // 清除特定角色的快取
                if ($role->id) {
                    $this->cacheService->clearRoleCache($role->id);
                }
                
                // 如果涉及層級變更，清除層級快取
                if (isset($role->parent_id) || $role->isDirty('parent_id')) {
                    $this->cacheService->clearRoleCache(); // 清除所有角色快取
                }
                break;
        }
    }

    /**
     * 處理權限模型事件
     */
    private function handlePermissionEvent($event, $permission): void
    {
        $eventName = class_basename(get_class($event));
        
        Log::info("權限事件: {$eventName}", [
            'permission_id' => $permission->id ?? null,
            'permission_name' => $permission->name ?? null
        ]);

        switch ($eventName) {
            case 'Created':
            case 'Updated':
            case 'Deleted':
                // 權限變更會影響所有相關角色
                $this->cacheService->clearPermissionCache($permission->id ?? null);
                break;
        }
    }

    /**
     * 處理使用者模型事件
     */
    private function handleUserEvent($event, $user): void
    {
        $eventName = class_basename(get_class($event));
        
        // 只處理可能影響角色關聯的事件
        if (in_array($eventName, ['Updated', 'Deleted'])) {
            Log::info("使用者事件: {$eventName}", [
                'user_id' => $user->id ?? null,
                'username' => $user->username ?? null
            ]);

            // 清除使用者權限快取
            if ($user->id) {
                $this->cacheService->clearUserPermissionCache($user->id);
            }
        }
    }

    /**
     * 檢查是否為角色相關的寫入查詢
     */
    private function isRoleRelatedWriteQuery(string $sql): bool
    {
        $sql = strtolower($sql);
        
        // 檢查是否為寫入操作
        $writeOperations = ['insert', 'update', 'delete'];
        $isWriteOperation = false;
        
        foreach ($writeOperations as $operation) {
            if (str_starts_with($sql, $operation)) {
                $isWriteOperation = true;
                break;
            }
        }
        
        if (!$isWriteOperation) {
            return false;
        }
        
        // 檢查是否涉及角色相關資料表
        $roleTables = [
            'roles',
            'permissions', 
            'role_permissions',
            'user_roles',
            'permission_dependencies'
        ];
        
        foreach ($roleTables as $table) {
            if (str_contains($sql, $table)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * 處理失敗的任務
     */
    public function failed(object $event, \Throwable $exception): void
    {
        Log::error('角色快取清除任務失敗', [
            'event' => get_class($event),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
