<?php

namespace Tests\Integration\ChannelManagement;

use Tests\TestCase;
use App\Models\User;
use App\Models\Agent;
use App\Models\Player;
use App\Models\PointTransaction;
use App\Services\PointService;
use App\Exceptions\InsufficientPointsException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;

/**
 * 點數管理完整流程整合測試
 * 
 * 測試需求：
 * - 需求 11: 點數管理機制
 * - 需求 12: 點數分配與回收管理
 * - 需求 13: 點數限制與業務控制
 * - 需求 14: 玩家點數管理機制
 * - 需求 15: 玩家點數操作與控制
 * - 需求 16: 玩家點數限制與業務控制
 * - 需求 17: 資料完整性與驗證
 */
class PointManagementIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private PointService $pointService;
    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->pointService = app(PointService::class);
        
        // 建立測試管理員
        $this->adminUser = User::factory()->create([
            'username' => 'test_admin',
            'name' => '測試管理員',
            'email' => 'admin@test.com',
        ]);
        
        $this->actingAs($this->adminUser);
    }

    /**
     * 測試代理點數分配完整流程
     * 需求 11.1-11.10, 12.1-12.10
     */
    public function test_complete_agent_point_allocation_workflow()
    {
        // 建立測試代理結構
        $rootAgent = Agent::factory()->create([
            'name' => '根代理',
            'prefix' => 'a',
            'level' => 1,
            'total_points' => 100000.00,
            'remaining_points' => 100000.00,
            'allocated_points' => 0.00,
        ]);

        $level2Agent = Agent::factory()->create([
            'name' => '二級代理',
            'parent_id' => $rootAgent->id,
            'level' => 2,
            'total_points' => 0.00,
            'remaining_points' => 0.00,
            'allocated_points' => 0.00,
        ]);

        $level3Agent = Agent::factory()->create([
            'name' => '三級代理',
            'parent_id' => $level2Agent->id,
            'level' => 3,
            'total_points' => 0.00,
            'remaining_points' => 0.00,
            'allocated_points' => 0.00,
        ]);

        // 1. 測試根代理分配點數給二級代理
        $this->pointService->allocatePointsToAgent($level2Agent, 50000.00, $rootAgent);

        $rootAgent->refresh();
        $level2Agent->refresh();

        $this->assertEquals(50000.00, $rootAgent->remaining_points);
        $this->assertEquals(50000.00, $rootAgent->allocated_points);
        $this->assertEquals(50000.00, $level2Agent->total_points);
        $this->assertEquals(50000.00, $level2Agent->remaining_points);

        // 驗證點數交易記錄
        $this->assertDatabaseHas('point_transactions', [
            'agent_id' => $rootAgent->id,
            'type' => PointTransaction::TYPE_AGENT_ALLOCATION,
            'amount' => -50000.00,
            'description' => "分配點數給代理: {$level2Agent->name}",
        ]);

        $this->assertDatabaseHas('point_transactions', [
            'agent_id' => $level2Agent->id,
            'type' => PointTransaction::TYPE_AGENT_ALLOCATION,
            'amount' => 50000.00,
            'description' => "從代理 {$rootAgent->name} 獲得點數",
        ]);

        // 2. 測試二級代理分配點數給三級代理
        $this->pointService->allocatePointsToAgent($level3Agent, 20000.00, $level2Agent);

        $level2Agent->refresh();
        $level3Agent->refresh();

        $this->assertEquals(30000.00, $level2Agent->remaining_points);
        $this->assertEquals(20000.00, $level2Agent->allocated_points);
        $this->assertEquals(20000.00, $level3Agent->total_points);
        $this->assertEquals(20000.00, $level3Agent->remaining_points);

        // 3. 測試點數不足情況
        $this->expectException(InsufficientPointsException::class);
        $this->pointService->allocatePointsToAgent($level3Agent, 35000.00, $level2Agent);
    }

    /**
     * 測試代理點數回收完整流程
     * 需求 12.4-12.10
     */
    public function test_complete_agent_point_recovery_workflow()
    {
        // 建立測試代理結構
        $parentAgent = Agent::factory()->create([
            'name' => '父代理',
            'prefix' => 'b',
            'level' => 1,
            'total_points' => 50000.00,
            'remaining_points' => 20000.00,
            'allocated_points' => 30000.00,
        ]);

        $childAgent = Agent::factory()->create([
            'name' => '子代理',
            'parent_id' => $parentAgent->id,
            'level' => 2,
            'total_points' => 30000.00,
            'remaining_points' => 15000.00,
            'allocated_points' => 15000.00,
        ]);

        // 1. 測試部分點數回收
        $this->pointService->recoverPointsFromAgent($childAgent, 10000.00, $parentAgent);

        $parentAgent->refresh();
        $childAgent->refresh();

        $this->assertEquals(30000.00, $parentAgent->remaining_points);
        $this->assertEquals(20000.00, $parentAgent->allocated_points);
        $this->assertEquals(20000.00, $childAgent->total_points);
        $this->assertEquals(5000.00, $childAgent->remaining_points);

        // 驗證點數交易記錄
        $this->assertDatabaseHas('point_transactions', [
            'agent_id' => $childAgent->id,
            'type' => PointTransaction::TYPE_AGENT_RECOVERY,
            'amount' => -10000.00,
            'description' => "點數被代理 {$parentAgent->name} 回收",
        ]);

        $this->assertDatabaseHas('point_transactions', [
            'agent_id' => $parentAgent->id,
            'type' => PointTransaction::TYPE_AGENT_RECOVERY,
            'amount' => 10000.00,
            'description' => "從代理 {$childAgent->name} 回收點數",
        ]);

        // 2. 測試回收超過可用點數的情況
        $this->expectException(InsufficientPointsException::class);
        $this->pointService->recoverPointsFromAgent($childAgent, 6000.00, $parentAgent);
    }

    /**
     * 測試玩家點數管理完整流程
     * 需求 14.1-14.10, 15.1-15.10
     */
    public function test_complete_player_point_management_workflow()
    {
        // 建立測試代理
        $agent = Agent::factory()->create([
            'name' => '測試代理',
            'prefix' => 'c',
            'level' => 1,
            'total_points' => 50000.00,
            'remaining_points' => 50000.00,
            'allocated_points' => 0.00,
        ]);

        // 建立測試玩家
        $player1 = Player::factory()->create([
            'name' => '玩家1',
            'agent_id' => $agent->id,
            'points' => 0.00,
        ]);

        $player2 = Player::factory()->create([
            'name' => '玩家2',
            'agent_id' => $agent->id,
            'points' => 0.00,
        ]);

        // 1. 測試分配點數給玩家
        $this->pointService->allocatePointsToPlayer($player1, 10000.00, $agent);

        $agent->refresh();
        $player1->refresh();

        $this->assertEquals(40000.00, $agent->remaining_points);
        $this->assertEquals(10000.00, $agent->allocated_points);
        $this->assertEquals(10000.00, $player1->points);

        // 驗證點數交易記錄
        $this->assertDatabaseHas('point_transactions', [
            'agent_id' => $agent->id,
            'type' => PointTransaction::TYPE_PLAYER_ALLOCATION,
            'amount' => -10000.00,
            'description' => "分配點數給玩家: {$player1->name}",
        ]);

        $this->assertDatabaseHas('point_transactions', [
            'player_id' => $player1->id,
            'type' => PointTransaction::TYPE_PLAYER_ALLOCATION,
            'amount' => 10000.00,
            'description' => "從代理 {$agent->name} 獲得點數",
        ]);

        // 2. 測試分配點數給第二個玩家
        $this->pointService->allocatePointsToPlayer($player2, 15000.00, $agent);

        $agent->refresh();
        $player2->refresh();

        $this->assertEquals(25000.00, $agent->remaining_points);
        $this->assertEquals(25000.00, $agent->allocated_points);
        $this->assertEquals(15000.00, $player2->points);

        // 3. 測試代理點數不足情況
        $this->expectException(InsufficientPointsException::class);
        $this->pointService->allocatePointsToPlayer($player1, 30000.00, $agent);
    }

    /**
     * 測試玩家點數回收完整流程
     * 需求 15.4-15.10
     */
    public function test_complete_player_point_recovery_workflow()
    {
        // 建立測試代理和玩家
        $agent = Agent::factory()->create([
            'name' => '測試代理',
            'prefix' => 'd',
            'level' => 1,
            'total_points' => 50000.00,
            'remaining_points' => 30000.00,
            'allocated_points' => 20000.00,
        ]);

        $player = Player::factory()->create([
            'name' => '測試玩家',
            'agent_id' => $agent->id,
            'points' => 20000.00,
        ]);

        // 1. 測試部分點數回收
        $this->pointService->recoverPointsFromPlayer($player, 8000.00, $agent);

        $agent->refresh();
        $player->refresh();

        $this->assertEquals(38000.00, $agent->remaining_points);
        $this->assertEquals(12000.00, $agent->allocated_points);
        $this->assertEquals(12000.00, $player->points);

        // 驗證點數交易記錄
        $this->assertDatabaseHas('point_transactions', [
            'player_id' => $player->id,
            'type' => PointTransaction::TYPE_PLAYER_RECOVERY,
            'amount' => -8000.00,
            'description' => "點數被代理 {$agent->name} 回收",
        ]);

        $this->assertDatabaseHas('point_transactions', [
            'agent_id' => $agent->id,
            'type' => PointTransaction::TYPE_PLAYER_RECOVERY,
            'amount' => 8000.00,
            'description' => "從玩家 {$player->name} 回收點數",
        ]);

        // 2. 測試回收超過玩家擁有的點數
        $this->expectException(InsufficientPointsException::class);
        $this->pointService->recoverPointsFromPlayer($player, 15000.00, $agent);
    }

    /**
     * 測試點數限制與業務控制
     * 需求 13.1-13.10, 16.1-16.10
     */
    public function test_point_limits_and_business_control()
    {
        // 建立測試代理
        $agent = Agent::factory()->create([
            'name' => '限制測試代理',
            'prefix' => 'e',
            'level' => 1,
            'total_points' => 10000.00,
            'remaining_points' => 10000.00,
            'allocated_points' => 0.00,
        ]);

        $subAgent = Agent::factory()->create([
            'name' => '子代理',
            'parent_id' => $agent->id,
            'level' => 2,
            'total_points' => 0.00,
            'remaining_points' => 0.00,
            'allocated_points' => 0.00,
        ]);

        $player = Player::factory()->create([
            'name' => '限制測試玩家',
            'agent_id' => $agent->id,
            'points' => 0.00,
        ]);

        // 1. 測試代理點數用盡後無法建立下層代理
        $this->pointService->allocatePointsToAgent($subAgent, 10000.00, $agent);

        $agent->refresh();
        $this->assertEquals(0.00, $agent->remaining_points);

        // 嘗試再次分配點數應該失敗
        $this->expectException(InsufficientPointsException::class);
        $this->pointService->allocatePointsToAgent($subAgent, 1000.00, $agent);
    }

    /**
     * 測試複雜的多層級點數分配和回收
     * 需求 11.1-11.10, 12.1-12.10, 17.4-17.5
     */
    public function test_complex_multilevel_point_operations()
    {
        // 建立5層代理結構
        $agents = [];
        $agents[1] = Agent::factory()->create([
            'name' => '第1層代理',
            'prefix' => 'f',
            'level' => 1,
            'total_points' => 100000.00,
            'remaining_points' => 100000.00,
            'allocated_points' => 0.00,
        ]);

        for ($level = 2; $level <= 5; $level++) {
            $agents[$level] = Agent::factory()->create([
                'name' => "第{$level}層代理",
                'parent_id' => $agents[$level - 1]->id,
                'level' => $level,
                'total_points' => 0.00,
                'remaining_points' => 0.00,
                'allocated_points' => 0.00,
            ]);
        }

        // 建立玩家
        $players = [];
        for ($i = 1; $i <= 3; $i++) {
            $players[$i] = Player::factory()->create([
                'name' => "玩家{$i}",
                'agent_id' => $agents[5]->id, // 隸屬於最底層代理
                'points' => 0.00,
            ]);
        }

        // 1. 逐層分配點數
        $this->pointService->allocatePointsToAgent($agents[2], 50000.00, $agents[1]);
        $this->pointService->allocatePointsToAgent($agents[3], 25000.00, $agents[2]);
        $this->pointService->allocatePointsToAgent($agents[4], 12000.00, $agents[3]);
        $this->pointService->allocatePointsToAgent($agents[5], 6000.00, $agents[4]);

        // 2. 分配點數給玩家
        $this->pointService->allocatePointsToPlayer($players[1], 2000.00, $agents[5]);
        $this->pointService->allocatePointsToPlayer($players[2], 2000.00, $agents[5]);
        $this->pointService->allocatePointsToPlayer($players[3], 1000.00, $agents[5]);

        // 3. 驗證點數分佈
        foreach ($agents as $level => $agent) {
            $agent->refresh();
        }

        $this->assertEquals(50000.00, $agents[1]->remaining_points);
        $this->assertEquals(50000.00, $agents[1]->allocated_points);
        $this->assertEquals(25000.00, $agents[2]->remaining_points);
        $this->assertEquals(25000.00, $agents[2]->allocated_points);
        $this->assertEquals(1000.00, $agents[5]->remaining_points);
        $this->assertEquals(5000.00, $agents[5]->allocated_points);

        // 4. 驗證玩家點數
        foreach ($players as $player) {
            $player->refresh();
        }

        $this->assertEquals(2000.00, $players[1]->points);
        $this->assertEquals(2000.00, $players[2]->points);
        $this->assertEquals(1000.00, $players[3]->points);

        // 5. 測試逐層回收點數
        $this->pointService->recoverPointsFromPlayer($players[1], 1000.00, $agents[5]);
        $this->pointService->recoverPointsFromAgent($agents[5], 2000.00, $agents[4]);
        $this->pointService->recoverPointsFromAgent($agents[4], 5000.00, $agents[3]);

        // 6. 驗證回收後的點數分佈
        foreach ($agents as $agent) {
            $agent->refresh();
        }

        $this->assertEquals(3000.00, $agents[5]->remaining_points);
        $this->assertEquals(4000.00, $agents[5]->allocated_points);
        $this->assertEquals(9000.00, $agents[4]->remaining_points);
        $this->assertEquals(4000.00, $agents[4]->allocated_points);
        $this->assertEquals(18000.00, $agents[3]->remaining_points);
        $this->assertEquals(9000.00, $agents[3]->allocated_points);
    }

    /**
     * 測試點數稽核和資料完整性
     * 需求 17.4, 17.10
     */
    public function test_point_audit_and_data_integrity()
    {
        // 建立測試結構
        $rootAgent = Agent::factory()->create([
            'name' => '稽核測試根代理',
            'prefix' => 'g',
            'level' => 1,
            'total_points' => 50000.00,
            'remaining_points' => 50000.00,
            'allocated_points' => 0.00,
        ]);

        $childAgents = [];
        for ($i = 1; $i <= 3; $i++) {
            $childAgents[$i] = Agent::factory()->create([
                'name' => "子代理{$i}",
                'parent_id' => $rootAgent->id,
                'level' => 2,
                'total_points' => 0.00,
                'remaining_points' => 0.00,
                'allocated_points' => 0.00,
            ]);
        }

        $players = [];
        for ($i = 1; $i <= 5; $i++) {
            $players[$i] = Player::factory()->create([
                'name' => "稽核玩家{$i}",
                'agent_id' => $childAgents[($i % 3) + 1]->id,
                'points' => 0.00,
            ]);
        }

        // 執行複雜的點數操作
        $this->pointService->allocatePointsToAgent($childAgents[1], 20000.00, $rootAgent);
        $this->pointService->allocatePointsToAgent($childAgents[2], 15000.00, $rootAgent);
        $this->pointService->allocatePointsToAgent($childAgents[3], 10000.00, $rootAgent);

        $this->pointService->allocatePointsToPlayer($players[1], 5000.00, $childAgents[1]);
        $this->pointService->allocatePointsToPlayer($players[2], 3000.00, $childAgents[1]);
        $this->pointService->allocatePointsToPlayer($players[3], 4000.00, $childAgents[2]);
        $this->pointService->allocatePointsToPlayer($players[4], 2000.00, $childAgents[2]);
        $this->pointService->allocatePointsToPlayer($players[5], 1000.00, $childAgents[3]);

        // 稽核點數一致性
        $rootAgent->refresh();
        $totalAllocatedToChildren = 0;
        $totalPlayerPoints = 0;

        foreach ($childAgents as $child) {
            $child->refresh();
            $totalAllocatedToChildren += $child->total_points;
        }

        foreach ($players as $player) {
            $player->refresh();
            $totalPlayerPoints += $player->points;
        }

        // 驗證點數平衡
        $this->assertEquals($rootAgent->allocated_points, $totalAllocatedToChildren);
        $this->assertEquals($rootAgent->total_points, $rootAgent->remaining_points + $rootAgent->allocated_points);

        // 驗證交易記錄完整性
        $totalTransactions = PointTransaction::count();
        $expectedTransactions = 8 * 2; // 8次操作，每次2筆記錄（扣除和增加）
        $this->assertEquals($expectedTransactions, $totalTransactions);

        // 驗證交易金額總和
        $agentTransactionSum = PointTransaction::whereNotNull('agent_id')->sum('amount');
        $playerTransactionSum = PointTransaction::whereNotNull('player_id')->sum('amount');
        
        // 代理交易總和應該為0（分配出去的等於收到的）
        $this->assertEquals(0.00, $agentTransactionSum);
        // 玩家交易總和應該等於玩家總點數
        $this->assertEquals($totalPlayerPoints, $playerTransactionSum);
    }

    /**
     * 測試併發點數操作的資料一致性
     * 需求 17.4-17.5
     */
    public function test_concurrent_point_operations_data_consistency()
    {
        $agent = Agent::factory()->create([
            'name' => '併發測試代理',
            'prefix' => 'h',
            'level' => 1,
            'total_points' => 10000.00,
            'remaining_points' => 10000.00,
            'allocated_points' => 0.00,
        ]);

        $players = [];
        for ($i = 1; $i <= 5; $i++) {
            $players[$i] = Player::factory()->create([
                'name' => "併發玩家{$i}",
                'agent_id' => $agent->id,
                'points' => 0.00,
            ]);
        }

        // 模擬併發點數分配（使用資料庫交易確保一致性）
        DB::transaction(function () use ($agent, $players) {
            $this->pointService->allocatePointsToPlayer($players[1], 2000.00, $agent);
            $this->pointService->allocatePointsToPlayer($players[2], 2000.00, $agent);
            $this->pointService->allocatePointsToPlayer($players[3], 2000.00, $agent);
            $this->pointService->allocatePointsToPlayer($players[4], 2000.00, $agent);
            $this->pointService->allocatePointsToPlayer($players[5], 2000.00, $agent);
        });

        // 驗證交易後的資料一致性
        $agent->refresh();
        $this->assertEquals(0.00, $agent->remaining_points);
        $this->assertEquals(10000.00, $agent->allocated_points);

        $totalPlayerPoints = 0;
        foreach ($players as $player) {
            $player->refresh();
            $totalPlayerPoints += $player->points;
        }

        $this->assertEquals(10000.00, $totalPlayerPoints);

        // 驗證交易記錄數量
        $transactionCount = PointTransaction::count();
        $this->assertEquals(10, $transactionCount); // 5次操作 × 2筆記錄
    }
}