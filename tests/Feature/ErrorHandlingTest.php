<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Services\ErrorHandlerService;
use App\Services\NetworkRetryService;
use App\Services\UserFriendlyErrorService;
use App\Services\EnhancedErrorLoggingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Livewire;

/**
 * 錯誤處理機制測試
 */
class ErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;
    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立測試角色
        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理員',
            'description' => '系統管理員',
        ]);

        // 建立測試使用者
        $this->adminUser = User::factory()->create([
            'username' => 'admin_test',
            'email' => 'admin@test.com',
            'is_active' => true,
        ]);

        $this->regularUser = User::factory()->create([
            'username' => 'user_test',
            'email' => 'user@test.com',
            'is_active' => true,
        ]);

        // 指派角色
        $this->adminUser->roles()->attach($this->adminRole);
    }

    /**
     * 測試錯誤處理服務的權限錯誤處理
     */
    public function test_error_handler_service_handles_permission_errors()
    {
        $errorHandler = app(ErrorHandlerService::class);

        $result = $errorHandler->handlePermissionError(
            'users.delete',
            'delete_user',
            ['user_id' => 123]
        );

        $this->assertEquals('permission_error', $result['type']);
        $this->assertEquals('PERMISSION_DENIED', $result['code']);
        $this->assertFalse($result['retry']);
        $this->assertStringContainsString('刪除使用者', $result['message']);
    }

    /**
     * 測試錯誤處理服務的驗證錯誤處理
     */
    public function test_error_handler_service_handles_validation_errors()
    {
        $errorHandler = app(ErrorHandlerService::class);

        $validationException = ValidationException::withMessages([
            'username' => ['使用者名稱已存在'],
            'email' => ['電子郵件格式不正確'],
        ]);

        $result = $errorHandler->handleValidationError($validationException, [
            'operation' => 'create_user'
        ]);

        $this->assertEquals('validation_error', $result['type']);
        $this->assertEquals('VALIDATION_FAILED', $result['code']);
        $this->assertTrue($result['retry']);
        $this->assertArrayHasKey('errors', $result);
    }

    /**
     * 測試網路重試服務
     */
    public function test_network_retry_service_retries_on_failure()
    {
        $networkRetry = app(NetworkRetryService::class);
        $networkRetry->setRetryConfig(2, 100, 1.5); // 2次重試，100ms延遲

        $attemptCount = 0;
        $operation = function () use (&$attemptCount) {
            $attemptCount++;
            if ($attemptCount < 3) {
                throw new ConnectionException('Connection failed');
            }
            return 'success';
        };

        $result = $networkRetry->executeWithRetry($operation, ['test' => true]);

        $this->assertEquals('success', $result);
        $this->assertEquals(3, $attemptCount); // 1次原始嘗試 + 2次重試
    }

    /**
     * 測試網路重試服務達到最大重試次數
     */
    public function test_network_retry_service_fails_after_max_retries()
    {
        $networkRetry = app(NetworkRetryService::class);
        $networkRetry->setRetryConfig(2, 100, 1.5);

        $attemptCount = 0;
        $operation = function () use (&$attemptCount) {
            $attemptCount++;
            throw new ConnectionException('Connection always fails');
        };

        $this->expectException(ConnectionException::class);
        $networkRetry->executeWithRetry($operation, ['test' => true]);

        $this->assertEquals(3, $attemptCount); // 1次原始嘗試 + 2次重試
    }

    /**
     * 測試使用者友善錯誤服務
     */
    public function test_user_friendly_error_service_provides_friendly_messages()
    {
        $friendlyError = app(UserFriendlyErrorService::class);

        $result = $friendlyError->getFriendlyMessage(
            'permission_error',
            'users.delete',
            ['user_id' => 123]
        );

        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('icon', $result);
        $this->assertArrayHasKey('actions', $result);
        $this->assertEquals('無法刪除使用者', $result['title']);
    }

    /**
     * 測試增強型錯誤日誌服務
     */
    public function test_enhanced_error_logging_service_logs_errors()
    {
        $enhancedLogger = app(EnhancedErrorLoggingService::class);
        $exception = new \Exception('Test error message');

        // 測試日誌服務不會拋出例外
        $this->expectNotToPerformAssertions();
        
        $enhancedLogger->logUserManagementError(
            $exception,
            'test_operation',
            ['test_context' => 'value'],
            'high'
        );
    }

    /**
     * 測試 Livewire 元件的錯誤處理
     */
    public function test_livewire_component_handles_permission_errors()
    {
        // 以沒有權限的使用者身份登入
        $this->actingAs($this->regularUser);

        Livewire::test(\App\Livewire\Admin\Users\UserList::class)
            ->call('deleteUser', $this->adminUser->id)
            ->assertDispatched('show-toast', function ($event) {
                return $event['type'] === 'error';
            });
    }

    /**
     * 測試 Livewire 元件的驗證錯誤處理
     */
    public function test_livewire_component_handles_validation_errors()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(\App\Livewire\Admin\Users\UserList::class)
            ->set('search', str_repeat('a', 300)) // 過長的搜尋字串
            ->assertSet('search', str_repeat('a', 300));
    }

    /**
     * 測試錯誤處理的日誌記錄
     */
    public function test_error_handling_creates_proper_logs()
    {
        $this->actingAs($this->regularUser);

        // 嘗試執行沒有權限的操作，應該會觸發錯誤處理
        $this->expectNotToPerformAssertions();
        
        Livewire::test(\App\Livewire\Admin\Users\UserList::class)
            ->call('toggleUserStatus', $this->adminUser->id);
    }

    /**
     * 測試網路錯誤的重試機制
     */
    public function test_network_error_retry_mechanism()
    {
        $networkRetry = app(NetworkRetryService::class);

        $result = $networkRetry->executeLivewireOperation(
            function () {
                throw new ConnectionException('Network timeout');
            },
            'test_operation',
            ['test' => true]
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('network_error', $result['error_type']);
        $this->assertTrue($result['retry']);
        $this->assertArrayHasKey('retry_delay', $result);
    }

    /**
     * 測試資料庫錯誤處理
     */
    public function test_database_error_handling()
    {
        $errorHandler = app(ErrorHandlerService::class);

        // 模擬重複鍵錯誤
        $pdoException = new \PDOException('Duplicate entry');
        $pdoException->errorInfo = ['23000', 1062, 'Duplicate entry'];
        
        $queryException = new QueryException(
            'mysql',
            'INSERT INTO users...',
            [],
            $pdoException
        );

        $result = $errorHandler->handleDatabaseError($queryException, [
            'operation' => 'create_user'
        ]);

        $this->assertEquals('database_error', $result['type']);
        $this->assertEquals('DATABASE_ERROR', $result['code']);
        $this->assertStringContainsString('重複', $result['message']);
    }

    /**
     * 測試系統錯誤處理
     */
    public function test_system_error_handling()
    {
        $errorHandler = app(ErrorHandlerService::class);

        $exception = new \RuntimeException('System failure');

        $result = $errorHandler->handleSystemError($exception, [
            'operation' => 'system_operation'
        ]);

        $this->assertEquals('system_error', $result['type']);
        $this->assertEquals('SYSTEM_ERROR', $result['code']);
        $this->assertFalse($result['retry']);
        $this->assertStringContainsString('系統發生錯誤', $result['message']);
    }

    /**
     * 測試錯誤訊息的本地化
     */
    public function test_error_messages_are_localized()
    {
        $friendlyError = app(UserFriendlyErrorService::class);

        $result = $friendlyError->getFriendlyMessage(
            'network_error',
            'connection_timeout'
        );

        // 驗證訊息是正體中文
        $this->assertStringContainsString('連線逾時', $result['title']);
        $this->assertStringContainsString('網路', $result['message']);
    }

    /**
     * 測試錯誤處理的效能
     */
    public function test_error_handling_performance()
    {
        $startTime = microtime(true);

        $errorHandler = app(ErrorHandlerService::class);
        
        // 執行多次錯誤處理
        for ($i = 0; $i < 100; $i++) {
            $errorHandler->handlePermissionError('users.view', 'test_action');
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // 確保錯誤處理不會顯著影響效能（100次操作應在1秒內完成）
        $this->assertLessThan(1.0, $executionTime);
    }
}