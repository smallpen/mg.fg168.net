<?php

namespace Tests\Unit\Exceptions\ChannelManagement;

use App\Exceptions\ChannelManagement\InsufficientPointsException;
use PHPUnit\Framework\TestCase;

/**
 * 點數不足例外類別測試
 * 
 * 測試 InsufficientPointsException 的各種建立方法和錯誤訊息
 * 對應需求: 11.4, 12.5, 14.2, 15.5
 */
class InsufficientPointsExceptionTest extends TestCase
{
    /**
     * 測試預設例外建立
     */
    public function test_default_exception_creation(): void
    {
        $exception = new InsufficientPointsException();
        
        $this->assertEquals('點數不足，無法執行此操作', $exception->getMessage());
        $this->assertEquals(422, $exception->getCode());
    }

    /**
     * 測試自定義訊息例外建立
     */
    public function test_custom_message_exception_creation(): void
    {
        $customMessage = '自定義點數不足訊息';
        $exception = new InsufficientPointsException($customMessage);
        
        $this->assertEquals($customMessage, $exception->getMessage());
        $this->assertEquals(422, $exception->getCode());
    }

    /**
     * 測試自定義代碼例外建立
     */
    public function test_custom_code_exception_creation(): void
    {
        $customCode = 400;
        $exception = new InsufficientPointsException('測試訊息', $customCode);
        
        $this->assertEquals($customCode, $exception->getCode());
    }

    /**
     * 測試代理點數不足例外建立
     */
    public function test_for_agent_exception_creation(): void
    {
        $required = 1000.50;
        $available = 500.25;
        $agentName = '測試代理';
        
        $exception = InsufficientPointsException::forAgent($required, $available, $agentName);
        
        $expectedMessage = "代理「{$agentName}」點數不足。需要 {$required} 點，但只有 {$available} 點可用。";
        $this->assertEquals($expectedMessage, $exception->getMessage());
        $this->assertEquals(422, $exception->getCode());
    }

    /**
     * 測試玩家點數不足例外建立
     */
    public function test_for_player_exception_creation(): void
    {
        $required = 200.75;
        $available = 100.00;
        $playerName = '測試玩家';
        
        $exception = InsufficientPointsException::forPlayer($required, $available, $playerName);
        
        $expectedMessage = "玩家「{$playerName}」點數不足。需要 {$required} 點，但只有 {$available} 點可用。";
        $this->assertEquals($expectedMessage, $exception->getMessage());
        $this->assertEquals(422, $exception->getCode());
    }

    /**
     * 測試點數回收不足例外建立
     */
    public function test_for_recovery_exception_creation(): void
    {
        $requested = 300.00;
        $available = 150.50;
        $targetName = '目標對象';
        
        $exception = InsufficientPointsException::forRecovery($requested, $available, $targetName);
        
        $expectedMessage = "無法從「{$targetName}」回收 {$requested} 點，最多只能回收 {$available} 點。";
        $this->assertEquals($expectedMessage, $exception->getMessage());
        $this->assertEquals(422, $exception->getCode());
    }

    /**
     * 測試例外是否繼承自 Exception
     */
    public function test_exception_inheritance(): void
    {
        $exception = new InsufficientPointsException();
        
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    /**
     * 測試例外鏈
     */
    public function test_exception_chaining(): void
    {
        $previousException = new \RuntimeException('前一個例外');
        $exception = new InsufficientPointsException('點數不足', 422, $previousException);
        
        $this->assertSame($previousException, $exception->getPrevious());
    }

    /**
     * 測試零點數情況
     */
    public function test_zero_points_scenarios(): void
    {
        $exception = InsufficientPointsException::forAgent(100, 0, '零點數代理');
        
        $expectedMessage = "代理「零點數代理」點數不足。需要 100 點，但只有 0 點可用。";
        $this->assertEquals($expectedMessage, $exception->getMessage());
    }

    /**
     * 測試小數點數情況
     */
    public function test_decimal_points_scenarios(): void
    {
        $exception = InsufficientPointsException::forPlayer(99.99, 50.01, '小數點玩家');
        
        $expectedMessage = "玩家「小數點玩家」點數不足。需要 99.99 點，但只有 50.01 點可用。";
        $this->assertEquals($expectedMessage, $exception->getMessage());
    }

    /**
     * 測試特殊字元名稱
     */
    public function test_special_character_names(): void
    {
        $agentName = '測試代理@#$%';
        $exception = InsufficientPointsException::forAgent(100, 50, $agentName);
        
        $this->assertStringContainsString($agentName, $exception->getMessage());
    }

    /**
     * 測試空名稱情況
     */
    public function test_empty_name_scenarios(): void
    {
        $exception = InsufficientPointsException::forAgent(100, 50, '');
        
        $expectedMessage = "代理「」點數不足。需要 100 點，但只有 50 點可用。";
        $this->assertEquals($expectedMessage, $exception->getMessage());
    }
}