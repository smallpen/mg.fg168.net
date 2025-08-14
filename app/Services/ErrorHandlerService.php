<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Client\ConnectionException;
use Throwable;

/**
 * 錯誤處理服務
 * 
 * 提供統一的錯誤處理機制，包含權限錯誤、驗證錯誤、網路錯誤等
 */
class ErrorHandlerService
{
    protected LoggingService $loggingService;
    protected AuditLogService $auditService;

    public function __construct(LoggingService $loggingService, AuditLogService $auditService)
    {
        $this->loggingService = $loggingService;
        $this->auditService = $auditService;
    }

    /**
     * 處理權限錯誤
     *
     * @param string $permission 權限名稱
     * @param string $action 嘗試執行的動作
     * @param array $context 上下文資料
     * @return array 錯誤回應資料
     */
    public function handlePermissionError(string $permission, string $action, array $context = []): array
    {
        // 記錄權限錯誤
        $this->auditService->logPermissionDenied($permission, $action, $context);
        
        // 記錄安全事件
        $this->loggingService->logSecurityEvent(
            'permission_denied',
            "權限不足：嘗試執行 {$action}",
            array_merge($context, [
                'required_permission' => $permission,
                'user_id' => Auth::id(),
                'user_roles' => Auth::user()?->roles->pluck('name')->toArray() ?? [],
            ]),
            'medium'
        );

        return [
            'type' => 'permission_error',
            'message' => $this->getPermissionErrorMessage($permission),
            'code' => 'PERMISSION_DENIED',
            'retry' => false,
        ];
    }

    /**
     * 處理驗證錯誤
     *
     * @param ValidationException $exception 驗證例外
     * @param array $context 上下文資料
     * @return array 錯誤回應資料
     */
    public function handleValidationError(ValidationException $exception, array $context = []): array
    {
        // 記錄驗證錯誤
        $this->loggingService->logSecurityEvent(
            'validation_failed',
            '資料驗證失敗',
            array_merge($context, [
                'errors' => $exception->errors(),
                'input' => request()->except(['password', 'password_confirmation']),
            ]),
            'low'
        );

        return [
            'type' => 'validation_error',
            'message' => '輸入資料格式不正確，請檢查後重試',
            'errors' => $exception->errors(),
            'code' => 'VALIDATION_FAILED',
            'retry' => true,
        ];
    }

    /**
     * 處理網路錯誤
     *
     * @param ConnectionException|Throwable $exception 網路例外
     * @param array $context 上下文資料
     * @return array 錯誤回應資料
     */
    public function handleNetworkError(Throwable $exception, array $context = []): array
    {
        // 記錄網路錯誤
        $this->loggingService->logSystemError($exception, array_merge($context, [
            'error_type' => 'network_error',
            'url' => request()->fullUrl(),
            'method' => request()->method(),
        ]));

        return [
            'type' => 'network_error',
            'message' => '網路連線異常，請檢查網路設定後重試',
            'code' => 'NETWORK_ERROR',
            'retry' => true,
            'retry_delay' => 3000, // 3秒後重試
        ];
    }

    /**
     * 處理資料庫錯誤
     *
     * @param QueryException $exception 資料庫例外
     * @param array $context 上下文資料
     * @return array 錯誤回應資料
     */
    public function handleDatabaseError(QueryException $exception, array $context = []): array
    {
        // 記錄資料庫錯誤
        $this->loggingService->logSystemError($exception, array_merge($context, [
            'error_type' => 'database_error',
            'sql_state' => $exception->errorInfo[0] ?? null,
            'error_code' => $exception->errorInfo[1] ?? null,
        ]));

        // 根據錯誤類型提供不同的使用者訊息
        $userMessage = $this->getDatabaseErrorMessage($exception);

        return [
            'type' => 'database_error',
            'message' => $userMessage,
            'code' => 'DATABASE_ERROR',
            'retry' => $this->isDatabaseErrorRetryable($exception),
        ];
    }

    /**
     * 處理一般系統錯誤
     *
     * @param Throwable $exception 例外
     * @param array $context 上下文資料
     * @return array 錯誤回應資料
     */
    public function handleSystemError(Throwable $exception, array $context = []): array
    {
        // 記錄系統錯誤
        $this->loggingService->logSystemError($exception, array_merge($context, [
            'error_type' => 'system_error',
        ]));

        return [
            'type' => 'system_error',
            'message' => '系統發生錯誤，請稍後再試或聯繫系統管理員',
            'code' => 'SYSTEM_ERROR',
            'retry' => false,
        ];
    }

    /**
     * 處理使用者操作錯誤
     *
     * @param string $operation 操作名稱
     * @param string $reason 錯誤原因
     * @param array $context 上下文資料
     * @return array 錯誤回應資料
     */
    public function handleUserOperationError(string $operation, string $reason, array $context = []): array
    {
        // 記錄使用者操作錯誤
        $this->auditService->logUserManagementAction("operation_failed_{$operation}", array_merge($context, [
            'reason' => $reason,
            'status' => 'failed',
        ]));

        return [
            'type' => 'operation_error',
            'message' => $this->getOperationErrorMessage($operation, $reason),
            'code' => 'OPERATION_FAILED',
            'retry' => $this->isOperationRetryable($operation, $reason),
        ];
    }

    /**
     * 統一錯誤處理入口
     *
     * @param Throwable $exception 例外
     * @param array $context 上下文資料
     * @return array 錯誤回應資料
     */
    public function handleException(Throwable $exception, array $context = []): array
    {
        return match (true) {
            $exception instanceof AuthorizationException => $this->handlePermissionError(
                $context['permission'] ?? 'unknown',
                $context['action'] ?? 'unknown',
                $context
            ),
            $exception instanceof ValidationException => $this->handleValidationError($exception, $context),
            $exception instanceof ConnectionException => $this->handleNetworkError($exception, $context),
            $exception instanceof QueryException => $this->handleDatabaseError($exception, $context),
            default => $this->handleSystemError($exception, $context),
        };
    }

    /**
     * 取得權限錯誤訊息
     *
     * @param string $permission 權限名稱
     * @return string 錯誤訊息
     */
    private function getPermissionErrorMessage(string $permission): string
    {
        $messages = [
            'users.view' => '您沒有檢視使用者的權限',
            'users.create' => '您沒有建立使用者的權限',
            'users.edit' => '您沒有編輯使用者的權限',
            'users.delete' => '您沒有刪除使用者的權限',
            'users.export' => '您沒有匯出使用者資料的權限',
        ];

        return $messages[$permission] ?? '您沒有執行此操作的權限';
    }

    /**
     * 取得資料庫錯誤訊息
     *
     * @param QueryException $exception 資料庫例外
     * @return string 使用者友善的錯誤訊息
     */
    private function getDatabaseErrorMessage(QueryException $exception): string
    {
        $errorCode = $exception->errorInfo[1] ?? 0;

        return match ($errorCode) {
            1062 => '資料重複，請檢查輸入的資料是否已存在',
            1451 => '無法刪除，此資料正被其他資料引用',
            1452 => '資料關聯錯誤，請檢查相關資料是否存在',
            2002, 2006 => '資料庫連線中斷，請稍後再試',
            default => '資料庫操作失敗，請稍後再試',
        };
    }

    /**
     * 取得操作錯誤訊息
     *
     * @param string $operation 操作名稱
     * @param string $reason 錯誤原因
     * @return string 錯誤訊息
     */
    private function getOperationErrorMessage(string $operation, string $reason): string
    {
        $operationMessages = [
            'create_user' => '建立使用者失敗',
            'update_user' => '更新使用者失敗',
            'delete_user' => '刪除使用者失敗',
            'toggle_status' => '切換使用者狀態失敗',
            'bulk_activate' => '批量啟用失敗',
            'bulk_deactivate' => '批量停用失敗',
        ];

        $baseMessage = $operationMessages[$operation] ?? '操作失敗';

        $reasonMessages = [
            'user_not_found' => '找不到指定的使用者',
            'invalid_data' => '資料格式不正確',
            'constraint_violation' => '違反資料約束條件',
            'concurrent_modification' => '資料已被其他使用者修改',
        ];

        $reasonText = $reasonMessages[$reason] ?? $reason;

        return "{$baseMessage}：{$reasonText}";
    }

    /**
     * 判斷資料庫錯誤是否可重試
     *
     * @param QueryException $exception 資料庫例外
     * @return bool 是否可重試
     */
    private function isDatabaseErrorRetryable(QueryException $exception): bool
    {
        $errorCode = $exception->errorInfo[1] ?? 0;

        // 連線錯誤和暫時性錯誤可以重試
        return in_array($errorCode, [2002, 2006, 1205, 1213]);
    }

    /**
     * 判斷操作是否可重試
     *
     * @param string $operation 操作名稱
     * @param string $reason 錯誤原因
     * @return bool 是否可重試
     */
    private function isOperationRetryable(string $operation, string $reason): bool
    {
        // 網路錯誤和暫時性錯誤可以重試
        $retryableReasons = [
            'network_timeout',
            'connection_lost',
            'temporary_unavailable',
            'concurrent_modification',
        ];

        return in_array($reason, $retryableReasons);
    }
}