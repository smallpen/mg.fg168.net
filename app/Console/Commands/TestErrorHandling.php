<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ErrorHandlerService;
use App\Services\NetworkRetryService;
use App\Services\UserFriendlyErrorService;
use App\Services\EnhancedErrorLoggingService;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Client\ConnectionException;

/**
 * 測試錯誤處理機制的命令
 */
class TestErrorHandling extends Command
{
    protected $signature = 'test:error-handling';
    protected $description = '測試錯誤處理機制';

    public function handle()
    {
        $this->info('開始測試錯誤處理機制...');

        $this->testErrorHandlerService();
        $this->testNetworkRetryService();
        $this->testUserFriendlyErrorService();
        $this->testEnhancedErrorLoggingService();

        $this->info('錯誤處理機制測試完成！');
    }

    private function testErrorHandlerService()
    {
        $this->info('測試 ErrorHandlerService...');
        
        $errorHandler = app(ErrorHandlerService::class);

        // 測試權限錯誤
        $result = $errorHandler->handlePermissionError('users.delete', 'delete_user');
        $this->line("權限錯誤處理結果: {$result['message']}");

        // 測試驗證錯誤
        $validationException = ValidationException::withMessages([
            'username' => ['使用者名稱已存在'],
        ]);
        $result = $errorHandler->handleValidationError($validationException);
        $this->line("驗證錯誤處理結果: {$result['message']}");

        $this->info('✓ ErrorHandlerService 測試完成');
    }

    private function testNetworkRetryService()
    {
        $this->info('測試 NetworkRetryService...');
        
        $networkRetry = app(NetworkRetryService::class);
        $networkRetry->setRetryConfig(2, 100, 1.5);

        // 測試成功的重試
        $attemptCount = 0;
        $result = $networkRetry->executeWithRetry(function () use (&$attemptCount) {
            $attemptCount++;
            if ($attemptCount < 2) {
                throw new ConnectionException('Connection failed');
            }
            return 'success';
        });

        $this->line("重試機制測試結果: {$result} (嘗試次數: {$attemptCount})");
        $this->info('✓ NetworkRetryService 測試完成');
    }

    private function testUserFriendlyErrorService()
    {
        $this->info('測試 UserFriendlyErrorService...');
        
        $friendlyError = app(UserFriendlyErrorService::class);

        $result = $friendlyError->getFriendlyMessage('permission_error', 'users.delete');
        $this->line("友善錯誤訊息: {$result['title']} - {$result['message']}");

        $this->info('✓ UserFriendlyErrorService 測試完成');
    }

    private function testEnhancedErrorLoggingService()
    {
        $this->info('測試 EnhancedErrorLoggingService...');
        
        $enhancedLogger = app(EnhancedErrorLoggingService::class);
        $exception = new \Exception('測試錯誤訊息');

        $enhancedLogger->logUserManagementError($exception, 'test_operation');
        $this->line('錯誤日誌已記錄');

        $this->info('✓ EnhancedErrorLoggingService 測試完成');
    }
}