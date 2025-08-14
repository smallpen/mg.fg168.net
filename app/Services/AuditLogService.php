<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * 審計日誌服務
 * 
 * 記錄系統中的重要操作和安全事件
 */
class AuditLogService
{
    /**
     * 記錄使用者管理操作
     * 
     * @param string $action
     * @param array $data
     * @param User|null $targetUser
     * @param User|null $actor
     * @return void
     */
    public function logUserManagementAction(
        string $action, 
        array $data = [], 
        ?User $targetUser = null, 
        ?User $actor = null
    ): void {
        $actor = $actor ?? Auth::user();
        
        $logData = [
            'action' => $action,
            'actor_id' => $actor?->id,
            'actor_username' => $actor?->username,
            'target_user_id' => $targetUser?->id,
            'target_username' => $targetUser?->username,
            'data' => $data,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->url(),
            'method' => request()->method(),
            'timestamp' => now()->toISOString(),
        ];

        // 記錄到應用程式日誌
        Log::info("使用者管理操作: {$action}", $logData);

        // 如果有專門的審計日誌表，也可以記錄到資料庫
        $this->storeAuditLog('user_management', $action, $logData);
    }

    /**
     * 記錄權限檢查失敗
     * 
     * @param string $permission
     * @param string $resource
     * @param array $context
     * @param User|null $user
     * @return void
     */
    public function logPermissionDenied(
        string $permission, 
        string $resource = '', 
        array $context = [], 
        ?User $user = null
    ): void {
        $user = $user ?? Auth::user();
        
        $logData = [
            'action' => 'permission_denied',
            'permission' => $permission,
            'resource' => $resource,
            'user_id' => $user?->id,
            'username' => $user?->username,
            'context' => $context,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->url(),
            'method' => request()->method(),
            'timestamp' => now()->toISOString(),
        ];

        Log::warning("權限檢查失敗: {$permission}", $logData);
        $this->storeAuditLog('security', 'permission_denied', $logData);
    }

    /**
     * 記錄登入嘗試
     * 
     * @param string $username
     * @param bool $successful
     * @param string $reason
     * @return void
     */
    public function logLoginAttempt(string $username, bool $successful, string $reason = ''): void
    {
        $action = $successful ? 'login_success' : 'login_failed';
        
        $logData = [
            'action' => $action,
            'username' => $username,
            'successful' => $successful,
            'reason' => $reason,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ];

        if ($successful) {
            Log::info("登入成功: {$username}", $logData);
        } else {
            Log::warning("登入失敗: {$username}", $logData);
        }

        $this->storeAuditLog('authentication', $action, $logData);
    }

    /**
     * 記錄資料存取
     * 
     * @param string $resource
     * @param string $action
     * @param array $data
     * @param User|null $user
     * @return void
     */
    public function logDataAccess(string $resource, string $action, array $data = [], ?User $user = null): void
    {
        $user = $user ?? Auth::user();
        
        $logData = [
            'action' => "data_{$action}",
            'resource' => $resource,
            'user_id' => $user?->id,
            'username' => $user?->username,
            'data' => $data,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->url(),
            'method' => request()->method(),
            'timestamp' => now()->toISOString(),
        ];

        Log::info("資料存取: {$resource}.{$action}", $logData);
        $this->storeAuditLog('data_access', "data_{$action}", $logData);
    }

    /**
     * 記錄安全事件
     * 
     * @param string $event
     * @param string $severity
     * @param array $data
     * @param User|null $user
     * @return void
     */
    public function logSecurityEvent(string $event, string $severity = 'medium', array $data = [], ?User $user = null): void
    {
        $user = $user ?? Auth::user();
        
        $logData = [
            'action' => 'security_event',
            'event' => $event,
            'severity' => $severity,
            'user_id' => $user?->id,
            'username' => $user?->username,
            'data' => $data,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->url(),
            'method' => request()->method(),
            'timestamp' => now()->toISOString(),
        ];

        $logLevel = match($severity) {
            'low' => 'info',
            'medium' => 'warning',
            'high' => 'error',
            'critical' => 'critical',
            default => 'warning'
        };

        Log::log($logLevel, "安全事件: {$event}", $logData);
        $this->storeAuditLog('security', 'security_event', $logData);
    }

    /**
     * 記錄批量操作
     * 
     * @param string $operation
     * @param array $targetIds
     * @param array $results
     * @param User|null $user
     * @return void
     */
    public function logBulkOperation(string $operation, array $targetIds, array $results = [], ?User $user = null): void
    {
        $user = $user ?? Auth::user();
        
        $logData = [
            'action' => "bulk_{$operation}",
            'user_id' => $user?->id,
            'username' => $user?->username,
            'target_count' => count($targetIds),
            'target_ids' => $targetIds,
            'results' => $results,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->url(),
            'method' => request()->method(),
            'timestamp' => now()->toISOString(),
        ];

        Log::info("批量操作: {$operation}", $logData);
        $this->storeAuditLog('bulk_operations', "bulk_{$operation}", $logData);
    }

    /**
     * 記錄系統配置變更
     * 
     * @param string $setting
     * @param mixed $oldValue
     * @param mixed $newValue
     * @param User|null $user
     * @return void
     */
    public function logConfigurationChange(string $setting, $oldValue, $newValue, ?User $user = null): void
    {
        $user = $user ?? Auth::user();
        
        $logData = [
            'action' => 'configuration_change',
            'setting' => $setting,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'user_id' => $user?->id,
            'username' => $user?->username,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ];

        Log::info("配置變更: {$setting}", $logData);
        $this->storeAuditLog('configuration', 'configuration_change', $logData);
    }

    /**
     * 儲存審計日誌到資料庫
     * 
     * @param string $category
     * @param string $action
     * @param array $data
     * @return void
     */
    private function storeAuditLog(string $category, string $action, array $data): void
    {
        try {
            // 這裡可以實作將審計日誌儲存到專門的資料表
            // 目前先使用 Laravel 的日誌系統
            
            // 如果需要儲存到資料庫，可以建立 audit_logs 資料表
            /*
            DB::table('audit_logs')->insert([
                'category' => $category,
                'action' => $action,
                'user_id' => $data['user_id'] ?? null,
                'target_user_id' => $data['target_user_id'] ?? null,
                'ip_address' => $data['ip_address'] ?? null,
                'user_agent' => $data['user_agent'] ?? null,
                'url' => $data['url'] ?? null,
                'method' => $data['method'] ?? null,
                'data' => json_encode($data),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            */
        } catch (\Exception $e) {
            // 如果儲存審計日誌失敗，記錄錯誤但不影響主要功能
            Log::error('儲存審計日誌失敗', [
                'error' => $e->getMessage(),
                'category' => $category,
                'action' => $action,
            ]);
        }
    }

    /**
     * 取得使用者的操作歷史
     * 
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getUserActionHistory(int $userId, int $limit = 50): array
    {
        // 這裡可以實作從資料庫查詢使用者操作歷史
        // 目前回傳空陣列，實際實作時需要建立相應的資料表和查詢邏輯
        return [];
    }

    /**
     * 取得系統安全事件統計
     * 
     * @param int $days
     * @return array
     */
    public function getSecurityEventStats(int $days = 30): array
    {
        // 這裡可以實作安全事件統計
        // 目前回傳空陣列，實際實作時需要建立相應的查詢邏輯
        return [
            'login_failures' => 0,
            'permission_denials' => 0,
            'security_events' => 0,
            'bulk_operations' => 0,
        ];
    }

    /**
     * 清理舊的審計日誌
     * 
     * @param int $daysToKeep
     * @return int
     */
    public function cleanupOldLogs(int $daysToKeep = 90): int
    {
        // 這裡可以實作清理舊日誌的邏輯
        // 目前回傳 0，實際實作時需要建立相應的清理邏輯
        return 0;
    }
}