<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Throwable;

/**
 * 增強型錯誤日誌服務
 * 
 * 提供詳細的錯誤日誌記錄，包含上下文資訊、使用者資訊、系統狀態等
 */
class EnhancedErrorLoggingService
{
    protected LoggingService $loggingService;
    protected array $sensitiveFields = [
        'password',
        'password_confirmation',
        'token',
        'api_key',
        'secret',
        'private_key',
    ];

    public function __construct(LoggingService $loggingService)
    {
        $this->loggingService = $loggingService;
    }

    /**
     * 記錄使用者管理相關錯誤
     *
     * @param Throwable $exception 例外
     * @param string $operation 操作名稱
     * @param array $context 上下文資料
     * @param string $severity 嚴重程度
     */
    public function logUserManagementError(
        Throwable $exception,
        string $operation,
        array $context = [],
        string $severity = 'medium'
    ): void {
        $errorId = $this->generateErrorId();
        
        $logData = [
            'error_id' => $errorId,
            'operation' => $operation,
            'exception' => [
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'code' => $exception->getCode(),
                'trace' => $this->formatStackTrace($exception),
            ],
            'user_context' => $this->getUserContext(),
            'request_context' => $this->getRequestContext(),
            'system_context' => $this->getSystemContext(),
            'application_context' => array_merge($context, [
                'component' => 'user_management',
                'module' => 'admin',
            ]),
            'timestamp' => now()->toISOString(),
        ];

        // 根據嚴重程度選擇日誌級別
        $logLevel = $this->getLogLevel($severity);
        
        Log::channel('user_management')->{$logLevel}(
            "使用者管理錯誤 [{$operation}]",
            $logData
        );

        // 如果是高嚴重程度錯誤，同時記錄到安全日誌
        if (in_array($severity, ['high', 'critical'])) {
            $this->loggingService->logSecurityEvent(
                'user_management_error',
                "使用者管理操作發生嚴重錯誤: {$operation}",
                [
                    'error_id' => $errorId,
                    'exception_class' => get_class($exception),
                    'operation' => $operation,
                ],
                $severity
            );
        }
    }

    /**
     * 記錄權限相關錯誤
     *
     * @param string $permission 權限名稱
     * @param string $action 動作
     * @param array $context 上下文資料
     */
    public function logPermissionError(string $permission, string $action, array $context = []): void
    {
        $errorId = $this->generateErrorId();
        
        $logData = [
            'error_id' => $errorId,
            'error_type' => 'permission_denied',
            'permission' => $permission,
            'action' => $action,
            'user_context' => $this->getUserContext(),
            'request_context' => $this->getRequestContext(),
            'security_context' => [
                'user_roles' => Auth::user()?->roles->pluck('name')->toArray() ?? [],
                'user_permissions' => Auth::user()?->getAllPermissions()->pluck('name')->toArray() ?? [],
                'session_id' => session()->getId(),
                'csrf_token' => csrf_token(),
            ],
            'application_context' => array_merge($context, [
                'component' => 'permission_system',
                'module' => 'admin',
            ]),
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('security')->warning('權限拒絕', $logData);

        // 記錄到安全事件日誌
        $this->loggingService->logSecurityEvent(
            'permission_denied',
            "權限拒絕: 嘗試執行 {$action}，需要權限 {$permission}",
            [
                'error_id' => $errorId,
                'permission' => $permission,
                'action' => $action,
            ],
            'medium'
        );
    }

    /**
     * 記錄驗證錯誤
     *
     * @param array $errors 驗證錯誤
     * @param array $input 輸入資料
     * @param string $operation 操作名稱
     */
    public function logValidationError(array $errors, array $input, string $operation): void
    {
        $errorId = $this->generateErrorId();
        
        $logData = [
            'error_id' => $errorId,
            'error_type' => 'validation_failed',
            'operation' => $operation,
            'validation_errors' => $errors,
            'input_data' => $this->sanitizeData($input),
            'user_context' => $this->getUserContext(),
            'request_context' => $this->getRequestContext(),
            'validation_context' => [
                'failed_fields' => array_keys($errors),
                'error_count' => count($errors),
                'input_size' => count($input),
            ],
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('validation')->info('驗證失敗', $logData);

        // 如果驗證錯誤過多，可能是惡意攻擊
        if (count($errors) > 10) {
            $this->loggingService->logSecurityEvent(
                'suspicious_validation_failure',
                '大量驗證錯誤，可能的惡意攻擊',
                [
                    'error_id' => $errorId,
                    'error_count' => count($errors),
                    'operation' => $operation,
                ],
                'high'
            );
        }
    }

    /**
     * 記錄網路錯誤
     *
     * @param Throwable $exception 網路例外
     * @param string $operation 操作名稱
     * @param array $context 上下文資料
     */
    public function logNetworkError(Throwable $exception, string $operation, array $context = []): void
    {
        $errorId = $this->generateErrorId();
        
        $logData = [
            'error_id' => $errorId,
            'error_type' => 'network_error',
            'operation' => $operation,
            'exception' => [
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
            ],
            'network_context' => [
                'user_agent' => request()->userAgent(),
                'ip_address' => request()->ip(),
                'referer' => request()->header('referer'),
                'connection_type' => request()->header('connection'),
            ],
            'request_context' => $this->getRequestContext(),
            'system_context' => $this->getSystemContext(),
            'application_context' => array_merge($context, [
                'component' => 'network',
            ]),
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('network')->error('網路錯誤', $logData);
    }

    /**
     * 記錄資料庫錯誤
     *
     * @param Throwable $exception 資料庫例外
     * @param string $operation 操作名稱
     * @param array $context 上下文資料
     */
    public function logDatabaseError(Throwable $exception, string $operation, array $context = []): void
    {
        $errorId = $this->generateErrorId();
        
        $logData = [
            'error_id' => $errorId,
            'error_type' => 'database_error',
            'operation' => $operation,
            'exception' => [
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'sql_state' => $exception instanceof \PDOException ? $exception->errorInfo[0] ?? null : null,
                'error_code' => $exception instanceof \PDOException ? $exception->errorInfo[1] ?? null : null,
            ],
            'database_context' => [
                'connection' => config('database.default'),
                'driver' => config('database.connections.' . config('database.default') . '.driver'),
            ],
            'user_context' => $this->getUserContext(),
            'request_context' => $this->getRequestContext(),
            'application_context' => array_merge($context, [
                'component' => 'database',
            ]),
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('database')->error('資料庫錯誤', $logData);

        // 資料庫錯誤可能影響系統穩定性
        $this->loggingService->logHealthStatus(
            'database',
            'critical',
            [
                'error_id' => $errorId,
                'operation' => $operation,
                'exception_class' => get_class($exception),
            ]
        );
    }

    /**
     * 記錄系統錯誤
     *
     * @param Throwable $exception 系統例外
     * @param string $operation 操作名稱
     * @param array $context 上下文資料
     */
    public function logSystemError(Throwable $exception, string $operation, array $context = []): void
    {
        $errorId = $this->generateErrorId();
        
        $logData = [
            'error_id' => $errorId,
            'error_type' => 'system_error',
            'operation' => $operation,
            'exception' => [
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'code' => $exception->getCode(),
                'trace' => $this->formatStackTrace($exception),
            ],
            'user_context' => $this->getUserContext(),
            'request_context' => $this->getRequestContext(),
            'system_context' => $this->getSystemContext(),
            'application_context' => array_merge($context, [
                'component' => 'system',
            ]),
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('system')->critical('系統錯誤', $logData);

        // 系統錯誤需要立即關注
        $this->loggingService->logHealthStatus(
            'application',
            'critical',
            [
                'error_id' => $errorId,
                'operation' => $operation,
                'exception_class' => get_class($exception),
            ]
        );
    }

    /**
     * 取得使用者上下文資訊
     *
     * @return array 使用者上下文
     */
    private function getUserContext(): array
    {
        $user = Auth::user();
        
        if (!$user) {
            return [
                'authenticated' => false,
                'guest' => true,
            ];
        }

        return [
            'authenticated' => true,
            'user_id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'roles' => $user->roles->pluck('name')->toArray(),
            'last_login' => $user->last_login_at?->toISOString(),
            'session_id' => session()->getId(),
        ];
    }

    /**
     * 取得請求上下文資訊
     *
     * @return array 請求上下文
     */
    private function getRequestContext(): array
    {
        $request = request();
        
        return [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'query_params' => $request->query(),
            'headers' => $this->sanitizeHeaders($request->headers->all()),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referer' => $request->header('referer'),
            'is_ajax' => $request->ajax(),
            'is_json' => $request->expectsJson(),
        ];
    }

    /**
     * 取得系統上下文資訊
     *
     * @return array 系統上下文
     */
    private function getSystemContext(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'environment' => app()->environment(),
            'debug_mode' => config('app.debug'),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'execution_time' => microtime(true) - LARAVEL_START,
            'server_time' => now()->toISOString(),
        ];
    }

    /**
     * 生成唯一錯誤 ID
     *
     * @return string 錯誤 ID
     */
    private function generateErrorId(): string
    {
        return 'ERR_' . strtoupper(Str::random(8)) . '_' . time();
    }

    /**
     * 格式化堆疊追蹤
     *
     * @param Throwable $exception 例外
     * @return array 格式化的堆疊追蹤
     */
    private function formatStackTrace(Throwable $exception): array
    {
        $trace = [];
        $traceItems = $exception->getTrace();
        
        // 只保留前 10 層堆疊追蹤
        $traceItems = array_slice($traceItems, 0, 10);
        
        foreach ($traceItems as $index => $item) {
            $trace[] = [
                'index' => $index,
                'file' => $item['file'] ?? 'unknown',
                'line' => $item['line'] ?? 0,
                'function' => $item['function'] ?? 'unknown',
                'class' => $item['class'] ?? null,
                'type' => $item['type'] ?? null,
            ];
        }
        
        return $trace;
    }

    /**
     * 清理敏感資料
     *
     * @param array $data 原始資料
     * @return array 清理後的資料
     */
    private function sanitizeData(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $this->sensitiveFields)) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeData($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }

    /**
     * 清理請求標頭
     *
     * @param array $headers 原始標頭
     * @return array 清理後的標頭
     */
    private function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = [
            'authorization',
            'cookie',
            'x-api-key',
            'x-auth-token',
        ];
        
        $sanitized = [];
        
        foreach ($headers as $key => $value) {
            if (in_array(strtolower($key), $sensitiveHeaders)) {
                $sanitized[$key] = '[REDACTED]';
            } else {
                $sanitized[$key] = is_array($value) ? $value[0] : $value;
            }
        }
        
        return $sanitized;
    }

    /**
     * 根據嚴重程度取得日誌級別
     *
     * @param string $severity 嚴重程度
     * @return string 日誌級別
     */
    private function getLogLevel(string $severity): string
    {
        return match ($severity) {
            'low' => 'info',
            'medium' => 'warning',
            'high' => 'error',
            'critical' => 'critical',
            default => 'error',
        };
    }
}