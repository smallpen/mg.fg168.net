<?php

namespace App\Traits;

use App\Services\ErrorHandlerService;
use App\Services\NetworkRetryService;
use App\Services\UserFriendlyErrorService;
use App\Services\EnhancedErrorLoggingService;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Client\ConnectionException;
use Throwable;

/**
 * Livewire 錯誤處理 Trait
 * 
 * 為 Livewire 元件提供統一的錯誤處理機制
 */
trait HandlesLivewireErrors
{
    protected ?ErrorHandlerService $errorHandler = null;
    protected ?NetworkRetryService $networkRetry = null;
    protected ?UserFriendlyErrorService $friendlyError = null;
    protected ?EnhancedErrorLoggingService $enhancedLogger = null;

    /**
     * 初始化錯誤處理服務
     */
    protected function initializeErrorHandlers(): void
    {
        if (!$this->errorHandler) {
            $this->errorHandler = app(ErrorHandlerService::class);
            $this->networkRetry = app(NetworkRetryService::class);
            $this->friendlyError = app(UserFriendlyErrorService::class);
            $this->enhancedLogger = app(EnhancedErrorLoggingService::class);
        }
    }

    /**
     * 安全執行操作，包含錯誤處理
     *
     * @param callable $operation 要執行的操作
     * @param string $operationName 操作名稱
     * @param array $context 上下文資料
     * @param bool $withRetry 是否啟用重試機制
     * @return mixed 操作結果
     */
    protected function safeExecute(
        callable $operation,
        string $operationName,
        array $context = [],
        bool $withRetry = false
    ): mixed {
        $this->initializeErrorHandlers();

        try {
            if ($withRetry) {
                return $this->networkRetry->executeWithRetry($operation, array_merge($context, [
                    'component' => static::class,
                    'operation' => $operationName,
                ]));
            } else {
                return $operation();
            }

        } catch (Throwable $exception) {
            return $this->handleOperationError($exception, $operationName, $context);
        }
    }

    /**
     * 處理操作錯誤
     *
     * @param Throwable $exception 例外
     * @param string $operationName 操作名稱
     * @param array $context 上下文資料
     * @return mixed 錯誤處理結果
     */
    protected function handleOperationError(Throwable $exception, string $operationName, array $context = []): mixed
    {
        $this->initializeErrorHandlers();

        // 記錄詳細錯誤日誌
        $this->enhancedLogger->logUserManagementError(
            $exception,
            $operationName,
            array_merge($context, [
                'component' => static::class,
                'livewire_id' => $this->getId(),
            ])
        );

        // 處理不同類型的錯誤
        $errorData = $this->errorHandler->handleException($exception, array_merge($context, [
            'operation' => $operationName,
        ]));

        // 取得使用者友善的錯誤訊息
        $friendlyMessage = $this->friendlyError->getFriendlyMessage(
            $errorData['type'],
            $errorData['code'] ?? null,
            $context
        );

        // 發送錯誤通知到前端
        $this->dispatchErrorNotification($friendlyMessage, $errorData);

        // 根據錯誤類型決定返回值
        return $this->getErrorReturnValue($errorData, $operationName);
    }

    /**
     * 處理權限錯誤
     *
     * @param string $permission 權限名稱
     * @param string $action 動作名稱
     * @param array $context 上下文資料
     */
    protected function handlePermissionError(string $permission, string $action, array $context = []): void
    {
        $this->initializeErrorHandlers();

        // 記錄權限錯誤
        $this->enhancedLogger->logPermissionError($permission, $action, array_merge($context, [
            'component' => static::class,
            'livewire_id' => $this->getId(),
        ]));

        // 取得友善的錯誤訊息
        $friendlyMessage = $this->friendlyError->getFriendlyMessage(
            'permission_error',
            $permission,
            $context
        );

        // 發送錯誤通知
        $this->dispatch('show-error-modal', [
            'title' => $friendlyMessage['title'],
            'message' => $friendlyMessage['message'],
            'icon' => $friendlyMessage['icon'],
            'actions' => $friendlyMessage['actions'] ?? [],
        ]);
    }

    /**
     * 處理驗證錯誤
     *
     * @param ValidationException $exception 驗證例外
     * @param string $operationName 操作名稱
     * @param array $context 上下文資料
     */
    protected function handleValidationError(
        ValidationException $exception,
        string $operationName,
        array $context = []
    ): void {
        $this->initializeErrorHandlers();

        // 記錄驗證錯誤
        $this->enhancedLogger->logValidationError(
            $exception->errors(),
            request()->all(),
            $operationName
        );

        // 格式化驗證錯誤訊息
        $friendlyMessage = $this->friendlyError->formatValidationErrors($exception);

        // 發送驗證錯誤通知
        $this->dispatch('show-validation-errors', [
            'title' => $friendlyMessage['title'],
            'message' => $friendlyMessage['message'],
            'errors' => $friendlyMessage['errors'],
            'actions' => $friendlyMessage['actions'] ?? [],
        ]);
    }

    /**
     * 處理網路錯誤
     *
     * @param ConnectionException $exception 網路例外
     * @param string $operationName 操作名稱
     * @param array $context 上下文資料
     */
    protected function handleNetworkError(
        ConnectionException $exception,
        string $operationName,
        array $context = []
    ): void {
        $this->initializeErrorHandlers();

        // 記錄網路錯誤
        $this->enhancedLogger->logNetworkError($exception, $operationName, array_merge($context, [
            'component' => static::class,
        ]));

        // 取得友善的錯誤訊息
        $friendlyMessage = $this->friendlyError->getFriendlyMessage(
            'network_error',
            'connection_timeout',
            $context
        );

        // 發送網路錯誤通知（包含重試選項）
        $this->dispatch('show-network-error', [
            'title' => $friendlyMessage['title'],
            'message' => $friendlyMessage['message'],
            'icon' => $friendlyMessage['icon'],
            'actions' => $friendlyMessage['actions'] ?? [],
            'retry_operation' => $operationName,
        ]);
    }

    /**
     * 處理資料庫錯誤
     *
     * @param QueryException $exception 資料庫例外
     * @param string $operationName 操作名稱
     * @param array $context 上下文資料
     */
    protected function handleDatabaseError(
        QueryException $exception,
        string $operationName,
        array $context = []
    ): void {
        $this->initializeErrorHandlers();

        // 記錄資料庫錯誤
        $this->enhancedLogger->logDatabaseError($exception, $operationName, array_merge($context, [
            'component' => static::class,
        ]));

        // 取得友善的錯誤訊息
        $errorCode = (string)($exception->errorInfo[1] ?? '0');
        $friendlyMessage = $this->friendlyError->getFriendlyMessage(
            'database_error',
            $errorCode,
            $context
        );

        // 發送資料庫錯誤通知
        $this->dispatch('show-database-error', [
            'title' => $friendlyMessage['title'],
            'message' => $friendlyMessage['message'],
            'icon' => $friendlyMessage['icon'],
            'actions' => $friendlyMessage['actions'] ?? [],
        ]);
    }

    /**
     * 發送錯誤通知到前端
     *
     * @param array $friendlyMessage 友善錯誤訊息
     * @param array $errorData 錯誤資料
     */
    protected function dispatchErrorNotification(array $friendlyMessage, array $errorData): void
    {
        $notificationType = match ($errorData['type']) {
            'permission_error' => 'permission-error',
            'validation_error' => 'validation-error',
            'network_error' => 'network-error',
            'database_error' => 'database-error',
            'user_operation_error' => 'operation-error',
            default => 'system-error',
        };

        $this->dispatch("show-{$notificationType}", array_merge($friendlyMessage, [
            'retry' => $errorData['retry'] ?? false,
            'retry_delay' => $errorData['retry_delay'] ?? null,
            'error_code' => $errorData['code'] ?? null,
        ]));
    }

    /**
     * 根據錯誤類型取得返回值
     *
     * @param array $errorData 錯誤資料
     * @param string $operationName 操作名稱
     * @return mixed 返回值
     */
    protected function getErrorReturnValue(array $errorData, string $operationName): mixed
    {
        // 對於需要返回布林值的操作
        if (str_contains($operationName, 'delete') || 
            str_contains($operationName, 'update') || 
            str_contains($operationName, 'create')) {
            return false;
        }

        // 對於需要返回陣列的操作
        if (str_contains($operationName, 'get') || 
            str_contains($operationName, 'fetch') || 
            str_contains($operationName, 'list')) {
            return [];
        }

        // 預設返回 null
        return null;
    }

    /**
     * 顯示成功訊息
     *
     * @param string $message 成功訊息
     * @param array $context 上下文資料
     */
    protected function showSuccessMessage(string $message, array $context = []): void
    {
        $this->dispatch('show-toast', [
            'type' => 'success',
            'message' => $message,
            'duration' => 5000,
        ]);
    }

    /**
     * 顯示警告訊息
     *
     * @param string $message 警告訊息
     * @param array $context 上下文資料
     */
    protected function showWarningMessage(string $message, array $context = []): void
    {
        $this->dispatch('show-toast', [
            'type' => 'warning',
            'message' => $message,
            'duration' => 7000,
        ]);
    }

    /**
     * 顯示資訊訊息
     *
     * @param string $message 資訊訊息
     * @param array $context 上下文資料
     */
    protected function showInfoMessage(string $message, array $context = []): void
    {
        $this->dispatch('show-toast', [
            'type' => 'info',
            'message' => $message,
            'duration' => 5000,
        ]);
    }

    /**
     * 檢查並處理權限
     *
     * @param string $permission 權限名稱
     * @param string $action 動作名稱
     * @param array $context 上下文資料
     * @return bool 是否有權限
     */
    protected function checkPermissionOrFail(string $permission, string $action, array $context = []): bool
    {
        if (!auth()->user()?->hasPermission($permission)) {
            $this->handlePermissionError($permission, $action, $context);
            return false;
        }

        return true;
    }

    /**
     * 執行帶權限檢查的操作
     *
     * @param string $permission 權限名稱
     * @param callable $operation 操作
     * @param string $operationName 操作名稱
     * @param array $context 上下文資料
     * @return mixed 操作結果
     */
    protected function executeWithPermission(
        string $permission,
        callable $operation,
        string $operationName,
        array $context = []
    ): mixed {
        if (!$this->checkPermissionOrFail($permission, $operationName, $context)) {
            return $this->getErrorReturnValue(['type' => 'permission_error'], $operationName);
        }

        return $this->safeExecute($operation, $operationName, $context);
    }
}