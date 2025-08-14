<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Throwable;

/**
 * 網路重試服務
 * 
 * 提供網路請求的重試機制，處理暫時性網路錯誤
 */
class NetworkRetryService
{
    protected LoggingService $loggingService;
    protected int $maxRetries;
    protected int $baseDelay;
    protected float $backoffMultiplier;

    public function __construct(LoggingService $loggingService)
    {
        $this->loggingService = $loggingService;
        $this->maxRetries = config('app.network_retry.max_retries', 3);
        $this->baseDelay = config('app.network_retry.base_delay', 1000); // 毫秒
        $this->backoffMultiplier = config('app.network_retry.backoff_multiplier', 2.0);
    }

    /**
     * 執行帶重試機制的操作
     *
     * @param callable $operation 要執行的操作
     * @param array $context 上下文資料
     * @param int|null $maxRetries 最大重試次數（可覆蓋預設值）
     * @return mixed 操作結果
     * @throws Throwable 最終失敗時拋出例外
     */
    public function executeWithRetry(callable $operation, array $context = [], ?int $maxRetries = null): mixed
    {
        $maxRetries = $maxRetries ?? $this->maxRetries;
        $attempt = 0;
        $lastException = null;

        while ($attempt <= $maxRetries) {
            try {
                // 記錄重試嘗試
                if ($attempt > 0) {
                    $this->loggingService->logSystemError(
                        new \Exception("重試操作，第 {$attempt} 次嘗試"),
                        array_merge($context, [
                            'retry_attempt' => $attempt,
                            'max_retries' => $maxRetries,
                        ])
                    );
                }

                // 執行操作
                $result = $operation();

                // 成功時記錄日誌
                if ($attempt > 0) {
                    $this->loggingService->logPerformanceMetric(
                        'network_retry_success',
                        $attempt,
                        'attempts',
                        array_merge($context, ['operation' => 'retry_success'])
                    );
                }

                return $result;

            } catch (Throwable $exception) {
                $lastException = $exception;
                $attempt++;

                // 檢查是否為可重試的錯誤
                if (!$this->isRetryableException($exception)) {
                    $this->loggingService->logSystemError($exception, array_merge($context, [
                        'retry_attempt' => $attempt,
                        'non_retryable' => true,
                    ]));
                    throw $exception;
                }

                // 如果已達到最大重試次數，拋出例外
                if ($attempt > $maxRetries) {
                    $this->loggingService->logSystemError($exception, array_merge($context, [
                        'retry_attempts' => $attempt - 1,
                        'max_retries_exceeded' => true,
                    ]));
                    break;
                }

                // 計算延遲時間並等待
                $delay = $this->calculateDelay($attempt);
                $this->loggingService->logPerformanceMetric(
                    'network_retry_delay',
                    $delay,
                    'milliseconds',
                    array_merge($context, [
                        'attempt' => $attempt,
                        'exception_type' => get_class($exception),
                    ])
                );

                usleep($delay * 1000); // 轉換為微秒
            }
        }

        // 記錄最終失敗
        $this->loggingService->logSecurityEvent(
            'network_retry_exhausted',
            '網路重試次數用盡',
            array_merge($context, [
                'total_attempts' => $attempt,
                'final_exception' => $lastException?->getMessage(),
            ]),
            'high'
        );

        throw $lastException;
    }

    /**
     * 執行 Livewire 操作的重試機制
     *
     * @param callable $operation Livewire 操作
     * @param string $operationName 操作名稱
     * @param array $context 上下文資料
     * @return array 操作結果
     */
    public function executeLivewireOperation(callable $operation, string $operationName, array $context = []): array
    {
        try {
            $result = $this->executeWithRetry($operation, array_merge($context, [
                'operation_type' => 'livewire',
                'operation_name' => $operationName,
            ]));

            return [
                'success' => true,
                'data' => $result,
                'message' => '操作成功完成',
            ];

        } catch (ConnectionException $e) {
            return [
                'success' => false,
                'error_type' => 'network_error',
                'message' => '網路連線異常，請檢查網路設定後重試',
                'retry' => true,
                'retry_delay' => $this->baseDelay,
            ];

        } catch (RequestException $e) {
            return [
                'success' => false,
                'error_type' => 'request_error',
                'message' => '請求處理失敗，請稍後再試',
                'retry' => $this->isRetryableHttpStatus($e->response?->status()),
            ];

        } catch (Throwable $e) {
            return [
                'success' => false,
                'error_type' => 'system_error',
                'message' => '系統發生錯誤，請稍後再試',
                'retry' => false,
            ];
        }
    }

    /**
     * 檢查例外是否可重試
     *
     * @param Throwable $exception 例外
     * @return bool 是否可重試
     */
    public function isRetryableException(Throwable $exception): bool
    {
        // 網路連線錯誤
        if ($exception instanceof ConnectionException) {
            return true;
        }

        // HTTP 請求錯誤
        if ($exception instanceof RequestException) {
            return $this->isRetryableHttpStatus($exception->response?->status());
        }

        // 資料庫連線錯誤
        if ($exception instanceof \PDOException) {
            $errorCode = $exception->getCode();
            return in_array($errorCode, [
                2002, // 連線失敗
                2006, // 連線中斷
                1205, // 鎖定等待超時
                1213, // 死鎖
            ]);
        }

        // 暫時性系統錯誤
        $retryableMessages = [
            'timeout',
            'connection reset',
            'connection refused',
            'temporary failure',
            'service unavailable',
        ];

        $message = strtolower($exception->getMessage());
        foreach ($retryableMessages as $retryableMessage) {
            if (str_contains($message, $retryableMessage)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 檢查 HTTP 狀態碼是否可重試
     *
     * @param int|null $statusCode HTTP 狀態碼
     * @return bool 是否可重試
     */
    public function isRetryableHttpStatus(?int $statusCode): bool
    {
        if ($statusCode === null) {
            return true; // 無狀態碼通常表示連線問題
        }

        // 可重試的 HTTP 狀態碼
        $retryableStatuses = [
            408, // Request Timeout
            429, // Too Many Requests
            500, // Internal Server Error
            502, // Bad Gateway
            503, // Service Unavailable
            504, // Gateway Timeout
        ];

        return in_array($statusCode, $retryableStatuses);
    }

    /**
     * 計算重試延遲時間（指數退避）
     *
     * @param int $attempt 重試次數
     * @return int 延遲時間（毫秒）
     */
    public function calculateDelay(int $attempt): int
    {
        // 指數退避算法：baseDelay * (backoffMultiplier ^ (attempt - 1))
        $delay = $this->baseDelay * pow($this->backoffMultiplier, $attempt - 1);
        
        // 加入隨機抖動，避免雷群效應
        $jitter = rand(0, (int)($delay * 0.1)); // 10% 的隨機抖動
        
        // 限制最大延遲時間為 30 秒
        return min((int)($delay + $jitter), 30000);
    }

    /**
     * 取得重試配置資訊
     *
     * @return array 配置資訊
     */
    public function getRetryConfig(): array
    {
        return [
            'max_retries' => $this->maxRetries,
            'base_delay' => $this->baseDelay,
            'backoff_multiplier' => $this->backoffMultiplier,
        ];
    }

    /**
     * 設定重試配置
     *
     * @param int $maxRetries 最大重試次數
     * @param int $baseDelay 基礎延遲時間（毫秒）
     * @param float $backoffMultiplier 退避倍數
     */
    public function setRetryConfig(int $maxRetries, int $baseDelay, float $backoffMultiplier): void
    {
        $this->maxRetries = max(0, $maxRetries);
        $this->baseDelay = max(100, $baseDelay); // 最小 100ms
        $this->backoffMultiplier = max(1.0, $backoffMultiplier);
    }
}