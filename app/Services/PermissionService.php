<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * 權限服務類別
 * 
 * 提供統一的權限檢查和管理功能
 */
class PermissionService
{
    /**
     * 檢查當前使用者是否擁有指定權限
     * 
     * @param string $permission
     * @param User|null $user
     * @return bool
     */
    public function hasPermission(string $permission, ?User $user = null): bool
    {
        $user = $user ?? Auth::user();
        
        if (!$user) {
            return false;
        }

        // 檢查使用者是否啟用
        if (!$user->is_active) {
            return false;
        }

        return $user->hasPermission($permission);
    }

    /**
     * 檢查當前使用者是否擁有任一指定權限
     * 
     * @param array $permissions
     * @param User|null $user
     * @return bool
     */
    public function hasAnyPermission(array $permissions, ?User $user = null): bool
    {
        $user = $user ?? Auth::user();
        
        if (!$user || !$user->is_active) {
            return false;
        }

        return $user->hasAnyPermission($permissions);
    }

    /**
     * 檢查當前使用者是否擁有所有指定權限
     * 
     * @param array $permissions
     * @param User|null $user
     * @return bool
     */
    public function hasAllPermissions(array $permissions, ?User $user = null): bool
    {
        $user = $user ?? Auth::user();
        
        if (!$user || !$user->is_active) {
            return false;
        }

        return $user->hasAllPermissions($permissions);
    }

    /**
     * 檢查使用者是否可以對目標使用者執行操作
     * 
     * @param string $permission
     * @param User $targetUser
     * @param User|null $currentUser
     * @return bool
     */
    public function canPerformActionOnUser(string $permission, User $targetUser, ?User $currentUser = null): bool
    {
        $currentUser = $currentUser ?? Auth::user();
        
        if (!$currentUser || !$currentUser->is_active) {
            return false;
        }

        // 檢查基本權限
        if (!$currentUser->hasPermission($permission)) {
            return false;
        }

        // 不能對自己執行某些操作
        $restrictedSelfActions = ['users.delete', 'users.disable'];
        if (in_array($permission, $restrictedSelfActions) && $currentUser->id === $targetUser->id) {
            return false;
        }

        // 非超級管理員不能操作超級管理員
        if ($targetUser->isSuperAdmin() && !$currentUser->isSuperAdmin()) {
            return false;
        }

        return true;
    }

    /**
     * 記錄權限檢查失敗
     * 
     * @param string $permission
     * @param string $context
     * @param array $additionalData
     * @return void
     */
    public function logPermissionDenied(string $permission, string $context = '', array $additionalData = []): void
    {
        $user = Auth::user();
        
        Log::warning('權限檢查失敗', [
            'permission' => $permission,
            'context' => $context,
            'user_id' => $user?->id,
            'username' => $user?->username,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->url(),
            'timestamp' => now()->toISOString(),
            'additional_data' => $additionalData,
        ]);
    }

    /**
     * 取得使用者的權限快取鍵
     * 
     * @param int $userId
     * @return string
     */
    public function getPermissionCacheKey(int $userId): string
    {
        return "user_permissions_{$userId}";
    }

    /**
     * 清除使用者權限快取
     * 
     * @param int|null $userId
     * @return void
     */
    public function clearPermissionCache(?int $userId = null): void
    {
        if ($userId) {
            Cache::forget($this->getPermissionCacheKey($userId));
        } else {
            // 清除當前使用者的權限快取
            $user = Auth::user();
            if ($user) {
                Cache::forget($this->getPermissionCacheKey($user->id));
            }
        }
    }

    /**
     * 批量檢查權限
     * 
     * @param array $permissions
     * @param User|null $user
     * @return array
     */
    public function checkMultiplePermissions(array $permissions, ?User $user = null): array
    {
        $user = $user ?? Auth::user();
        $results = [];

        foreach ($permissions as $permission) {
            $results[$permission] = $this->hasPermission($permission, $user);
        }

        return $results;
    }

    /**
     * 取得使用者在特定模組的權限
     * 
     * @param string $module
     * @param User|null $user
     * @return array
     */
    public function getModulePermissions(string $module, ?User $user = null): array
    {
        $user = $user ?? Auth::user();
        
        if (!$user || !$user->is_active) {
            return [];
        }

        $permissions = $user->getPermissionsByModule($module);
        
        return $permissions->pluck('name')->toArray();
    }
}