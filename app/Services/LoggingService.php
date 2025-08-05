<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

/**
 * 日誌服務類別
 * 
 * 提供統一的日誌記錄介面，支援不同類型的日誌記錄
 */
class LoggingService
{
    /**
     * 記錄管理員活動
     *
     * @param string $action 執行的動作
     * @param string $resource 操作的資源
     * @param array $data 相關資料
     * @param string|null $userId 使用者ID（可選）
     */
    public function logAdminActivity(string $action, string $resource, array $data = [], ?string $userId = null): void
    {
        $userId = $userId ?? Auth::id();
        
        Log::channel('admin_activity')->info('管理員活動', [
            'user_id' => $userId,
            'username' => Auth::user()?->username ?? 'unknown',
            'action' => $action,
            'resource' => $resource,
            'data' => $data,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * 記錄安全事件
     *
     * @param string $event 安全事件類型
     * @param string $description 事件描述
     * @param array $context 上下文資料
     * @param string $severity 嚴重程度 (low, medium, high, critical)
     */
    public function logSecurityEvent(string $event, string $description, array $context = [], string $severity = 'medium'): void
    {
        $logLevel = match ($severity) {
            'low' => 'info',
            'medium' => 'warning',
            'high' => 'error',
            'critical' => 'critical',
            default => 'warning',
        };

        Log::channel('security')->{$logLevel}('安全事件', [
            'event_type' => $event,
            'description' => $description,
            'severity' => $severity,
            'user_id' => Auth::id(),
            'username' => Auth::user()?->username ?? 'guest',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'context' => $context,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * 記錄效能指標
     *
     * @param string $metric 指標名稱
     * @param float $value 指標值
     * @param string $unit 單位
     * @param array $tags 標籤
     */
    public function logPerformanceMetric(string $metric, float $value, string $unit = '', array $tags = []): void
    {
        Log::channel('performance')->info('效能指標', [
            'metric' => $metric,
            'value' => $value,
            'unit' => $unit,
            'tags' => $tags,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * 記錄系統健康狀態
     *
     * @param string $component 系統組件
     * @param string $status 狀態 (healthy, warning, critical)
     * @param array $details 詳細資訊
     */
    public function logHealthStatus(string $component, string $status, array $details = []): void
    {
        $logLevel = match ($status) {
            'healthy' => 'info',
            'warning' => 'warning',
            'critical' => 'error',
            default => 'info',
        };

        Log::channel('health')->{$logLevel}('系統健康檢查', [
            'component' => $component,
            'status' => $status,
            'details' => $details,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * 記錄備份操作
     *
     * @param string $type 備份類型
     * @param string $status 備份狀態
     * @param array $details 備份詳情
     */
    public function logBackupOperation(string $type, string $status, array $details = []): void
    {
        $logLevel = match ($status) {
            'started' => 'info',
            'completed' => 'info',
            'failed' => 'error',
            'warning' => 'warning',
            default => 'info',
        };

        Log::channel('backup')->{$logLevel}('備份操作', [
            'backup_type' => $type,
            'status' => $status,
            'details' => $details,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * 記錄登入嘗試
     *
     * @param string $username 使用者名稱
     * @param bool $successful 是否成功
     * @param string $reason 失敗原因（如果失敗）
     */
    public function logLoginAttempt(string $username, bool $successful, string $reason = ''): void
    {
        $this->logSecurityEvent(
            'login_attempt',
            $successful ? '登入成功' : '登入失敗',
            [
                'username' => $username,
                'successful' => $successful,
                'reason' => $reason,
            ],
            $successful ? 'low' : 'medium'
        );
    }

    /**
     * 記錄權限違規
     *
     * @param string $action 嘗試的動作
     * @param string $resource 資源
     * @param string $reason 拒絕原因
     */
    public function logPermissionViolation(string $action, string $resource, string $reason): void
    {
        $this->logSecurityEvent(
            'permission_violation',
            '權限違規嘗試',
            [
                'attempted_action' => $action,
                'resource' => $resource,
                'reason' => $reason,
            ],
            'high'
        );
    }

    /**
     * 記錄系統錯誤
     *
     * @param \Throwable $exception 例外物件
     * @param array $context 上下文資料
     */
    public function logSystemError(\Throwable $exception, array $context = []): void
    {
        Log::error('系統錯誤', [
            'exception' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'context' => $context,
            'user_id' => Auth::id(),
            'ip_address' => request()->ip(),
            'timestamp' => now()->toISOString(),
        ]);
    }
}