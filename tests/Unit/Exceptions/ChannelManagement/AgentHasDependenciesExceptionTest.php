<?php

namespace Tests\Unit\Exceptions\ChannelManagement;

use App\Exceptions\ChannelManagement\AgentHasDependenciesException;
use PHPUnit\Framework\TestCase;

/**
 * 代理有依賴關係例外類別測試
 * 
 * 測試 AgentHasDependenciesException 的各種建立方法和錯誤訊息
 * 對應需求: 1.8, 2.9, 17.3
 */
class AgentHasDependenciesExceptionTest extends TestCase
{
    /**
     * 測試預設例外建立
     */
    public function test_default_exception_creation(): void
    {
        $exception = new AgentHasDependenciesException();
        
        $this->assertEquals('無法刪除代理，因為該代理還有下層代理或玩家', $exception->getMessage());
        $this->assertEquals(422, $exception->getCode());
    }

    /**
     * 測試自定義訊息例外建立
     */
    public function test_custom_message_exception_creation(): void
    {
        $customMessage = '自定義依賴關係錯誤訊息';
        $exception = new AgentHasDependenciesException($customMessage);
        
        $this->assertEquals($customMessage, $exception->getMessage());
        $this->assertEquals(422, $exception->getCode());
    }

    /**
     * 測試自定義代碼例外建立
     */
    public function test_custom_code_exception_creation(): void
    {
        $customCode = 400;
        $exception = new AgentHasDependenciesException('測試訊息', $customCode);
        
        $this->assertEquals($customCode, $exception->getCode());
    }

    /**
     * 測試有下層代理例外建立
     */
    public function test_has_children_exception_creation(): void
    {
        $agentName = '測試代理';
        $childrenCount = 3;
        
        $exception = AgentHasDependenciesException::hasChildren($agentName, $childrenCount);
        
        $expectedMessage = "無法刪除代理「{$agentName}」，因為該代理還有 {$childrenCount} 個下層代理。請先處理或重新指派這些下層代理。";
        $this->assertEquals($expectedMessage, $exception->getMessage());
        $this->assertEquals(422, $exception->getCode());
    }

    /**
     * 測試單一下層代理例外
     */
    public function test_single_child_exception(): void
    {
        $agentName = '單一下層代理';
        $childrenCount = 1;
        
        $exception = AgentHasDependenciesException::hasChildren($agentName, $childrenCount);
        
        $expectedMessage = "無法刪除代理「{$agentName}」，因為該代理還有 {$childrenCount} 個下層代理。請先處理或重新指派這些下層代理。";
        $this->assertEquals($expectedMessage, $exception->getMessage());
    }

    /**
     * 測試有隸屬玩家例外建立
     */
    public function test_has_players_exception_creation(): void
    {
        $agentName = '測試代理';
        $playersCount = 5;
        
        $exception = AgentHasDependenciesException::hasPlayers($agentName, $playersCount);
        
        $expectedMessage = "無法刪除代理「{$agentName}」，因為該代理還有 {$playersCount} 個隸屬玩家。請先處理或重新指派這些玩家。";
        $this->assertEquals($expectedMessage, $exception->getMessage());
        $this->assertEquals(422, $exception->getCode());
    }

    /**
     * 測試單一隸屬玩家例外
     */
    public function test_single_player_exception(): void
    {
        $agentName = '單一玩家代理';
        $playersCount = 1;
        
        $exception = AgentHasDependenciesException::hasPlayers($agentName, $playersCount);
        
        $expectedMessage = "無法刪除代理「{$agentName}」，因為該代理還有 {$playersCount} 個隸屬玩家。請先處理或重新指派這些玩家。";
        $this->assertEquals($expectedMessage, $exception->getMessage());
    }

    /**
     * 測試同時有下層代理和玩家例外建立
     */
    public function test_has_both_exception_creation(): void
    {
        $agentName = '複合依賴代理';
        $childrenCount = 2;
        $playersCount = 4;
        
        $exception = AgentHasDependenciesException::hasBoth($agentName, $childrenCount, $playersCount);
        
        $expectedMessage = "無法刪除代理「{$agentName}」，因為該代理還有 {$childrenCount} 個下層代理和 {$playersCount} 個隸屬玩家。請先處理或重新指派這些關聯。";
        $this->assertEquals($expectedMessage, $exception->getMessage());
        $this->assertEquals(422, $exception->getCode());
    }

    /**
     * 測試零數量情況
     */
    public function test_zero_count_scenarios(): void
    {
        $agentName = '零數量代理';
        
        // 測試零下層代理
        $exception = AgentHasDependenciesException::hasChildren($agentName, 0);
        $this->assertStringContainsString('0 個下層代理', $exception->getMessage());
        
        // 測試零玩家
        $exception = AgentHasDependenciesException::hasPlayers($agentName, 0);
        $this->assertStringContainsString('0 個隸屬玩家', $exception->getMessage());
        
        // 測試兩者都為零
        $exception = AgentHasDependenciesException::hasBoth($agentName, 0, 0);
        $this->assertStringContainsString('0 個下層代理和 0 個隸屬玩家', $exception->getMessage());
    }

    /**
     * 測試大數量情況
     */
    public function test_large_count_scenarios(): void
    {
        $agentName = '大數量代理';
        $largeChildrenCount = 999;
        $largePlayersCount = 1000;
        
        $exception = AgentHasDependenciesException::hasBoth($agentName, $largeChildrenCount, $largePlayersCount);
        
        $this->assertStringContainsString("{$largeChildrenCount} 個下層代理", $exception->getMessage());
        $this->assertStringContainsString("{$largePlayersCount} 個隸屬玩家", $exception->getMessage());
    }

    /**
     * 測試例外是否繼承自 Exception
     */
    public function test_exception_inheritance(): void
    {
        $exception = new AgentHasDependenciesException();
        
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    /**
     * 測試例外鏈
     */
    public function test_exception_chaining(): void
    {
        $previousException = new \RuntimeException('前一個例外');
        $exception = new AgentHasDependenciesException('依賴關係錯誤', 422, $previousException);
        
        $this->assertSame($previousException, $exception->getPrevious());
    }

    /**
     * 測試特殊字元代理名稱
     */
    public function test_special_character_agent_names(): void
    {
        $specialName = '特殊@#$%代理';
        $exception = AgentHasDependenciesException::hasChildren($specialName, 1);
        
        $this->assertStringContainsString($specialName, $exception->getMessage());
    }

    /**
     * 測試空代理名稱
     */
    public function test_empty_agent_name(): void
    {
        $emptyName = '';
        $exception = AgentHasDependenciesException::hasChildren($emptyName, 1);
        
        $expectedMessage = "無法刪除代理「」，因為該代理還有 1 個下層代理。請先處理或重新指派這些下層代理。";
        $this->assertEquals($expectedMessage, $exception->getMessage());
    }

    /**
     * 測試多位元組字元代理名稱
     */
    public function test_multibyte_character_agent_name(): void
    {
        $multibyteAgentName = '測試代理中文名稱';
        $exception = AgentHasDependenciesException::hasPlayers($multibyteAgentName, 2);
        
        $this->assertStringContainsString($multibyteAgentName, $exception->getMessage());
    }

    /**
     * 測試負數數量（邊界情況）
     */
    public function test_negative_count_scenarios(): void
    {
        $agentName = '負數測試代理';
        
        // 雖然在實際應用中不應該出現負數，但測試例外類別的健壯性
        $exception = AgentHasDependenciesException::hasChildren($agentName, -1);
        $this->assertStringContainsString('-1 個下層代理', $exception->getMessage());
    }
}