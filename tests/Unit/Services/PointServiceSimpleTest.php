<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\PointService;
use App\Services\ActivityLogger;
use App\Models\Agent;
use App\Models\Player;
use App\Models\PointTransaction;
use App\Models\User;
use App\Exceptions\InsufficientPointsException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;

class PointServiceSimpleTest extends TestCase
{
    use RefreshDatabase;

    protected PointService $pointService;
    protected $activityLogger;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試使用者
        $this->user = User::create([
            'username' => 'testuser',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $this->actingAs($this->user);
        
        // Mock ActivityLogger
        $this->activityLogger = Mockery::mock(ActivityLogger::class);
        $activity = new \App\Models\Activity([
            'id' => 1,
            'type' => 'test',
            'description' => 'test',
        ]);
        $this->activityLogger->shouldReceive('log')->andReturn($activity);
        $this->activityLogger->shouldReceive('logUserAction')->andReturn($activity);
        
        // 建立 PointService 實例
        $this->pointService = new PointService($this->activityLogger);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_allocate_points_to_agent_from_system()
    {
        // 建立測試代理
        $agent = Agent::create([
            'name' => '測試代理',
            'username' => 'testagent',
            'account' => 'testagent',
            'email' => 'agent@test.com',
            'level' => 1,
            'total_points' => 0,
            'allocated_points' => 0,
            'remaining_points' => 0,
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        // 系統分配點數
        $this->pointService->allocatePointsToAgent($agent, 1000);

        // 驗證代理點數更新
        $agent->refresh();
        $this->assertEquals(1000, $agent->total_points);
        $this->assertEquals(1000, $agent->remaining_points);

        // 驗證交易記錄
        $this->assertDatabaseHas('point_transactions', [
            'agent_id' => $agent->id,
            'type' => PointTransaction::TYPE_AGENT_ALLOCATION,
            'amount' => 1000,
            'balance_before' => 0,
            'balance_after' => 1000,
        ]);
    }

    /** @test */
    public function it_can_allocate_points_to_agent_from_another_agent()
    {
        // 建立來源代理
        $fromAgent = Agent::create([
            'name' => '來源代理',
            'username' => 'fromagent',
            'account' => 'fromagent',
            'email' => 'from@test.com',
            'level' => 1,
            'total_points' => 2000,
            'allocated_points' => 0,
            'remaining_points' => 2000,
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        // 建立目標代理
        $toAgent = Agent::create([
            'name' => '目標代理',
            'username' => 'toagent',
            'account' => 'toagent',
            'email' => 'to@test.com',
            'level' => 1,
            'total_points' => 0,
            'allocated_points' => 0,
            'remaining_points' => 0,
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        // 分配點數
        $this->pointService->allocatePointsToAgent($toAgent, 500, $fromAgent);

        // 驗證來源代理點數
        $fromAgent->refresh();
        $this->assertEquals(2000, $fromAgent->total_points);
        $this->assertEquals(1500, $fromAgent->remaining_points);
        $this->assertEquals(500, $fromAgent->allocated_points);

        // 驗證目標代理點數
        $toAgent->refresh();
        $this->assertEquals(500, $toAgent->total_points);
        $this->assertEquals(500, $toAgent->remaining_points);

        // 驗證交易記錄
        $this->assertDatabaseHas('point_transactions', [
            'agent_id' => $fromAgent->id,
            'type' => PointTransaction::TYPE_AGENT_ALLOCATION,
            'amount' => -500,
        ]);

        $this->assertDatabaseHas('point_transactions', [
            'agent_id' => $toAgent->id,
            'type' => PointTransaction::TYPE_AGENT_ALLOCATION,
            'amount' => 500,
        ]);
    }

    /** @test */
    public function it_throws_exception_when_agent_has_insufficient_points()
    {
        // 建立點數不足的代理
        $fromAgent = Agent::create([
            'name' => '點數不足代理',
            'username' => 'pooragent',
            'account' => 'pooragent',
            'email' => 'poor@test.com',
            'level' => 1,
            'total_points' => 100,
            'allocated_points' => 0,
            'remaining_points' => 100,
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        $toAgent = Agent::create([
            'name' => '目標代理',
            'username' => 'targetagent',
            'account' => 'targetagent',
            'email' => 'target@test.com',
            'level' => 1,
            'total_points' => 0,
            'allocated_points' => 0,
            'remaining_points' => 0,
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        // 嘗試分配超過剩餘點數
        $this->expectException(InsufficientPointsException::class);
        $this->pointService->allocatePointsToAgent($toAgent, 200, $fromAgent);
    }

    /** @test */
    public function it_can_allocate_points_to_player()
    {
        // 建立代理
        $agent = Agent::create([
            'name' => '代理',
            'username' => 'agent',
            'account' => 'agent',
            'email' => 'agent@test.com',
            'level' => 1,
            'total_points' => 1000,
            'allocated_points' => 0,
            'remaining_points' => 1000,
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        // 建立玩家
        $player = Player::create([
            'name' => '玩家',
            'username' => 'player',
            'account' => 'player',
            'email' => 'player@test.com',
            'agent_id' => $agent->id,
            'points' => 0,
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        // 分配點數給玩家
        $this->pointService->allocatePointsToPlayer($player, 300, $agent);

        // 驗證代理點數
        $agent->refresh();
        $this->assertEquals(1000, $agent->total_points);
        $this->assertEquals(700, $agent->remaining_points);
        $this->assertEquals(300, $agent->allocated_points);

        // 驗證玩家點數
        $player->refresh();
        $this->assertEquals(300, $player->points);

        // 驗證交易記錄
        $this->assertDatabaseHas('point_transactions', [
            'agent_id' => $agent->id,
            'type' => PointTransaction::TYPE_PLAYER_ALLOCATION,
            'amount' => -300,
        ]);

        $this->assertDatabaseHas('point_transactions', [
            'player_id' => $player->id,
            'type' => PointTransaction::TYPE_PLAYER_ALLOCATION,
            'amount' => 300,
        ]);
    }

    /** @test */
    public function it_can_system_adjust_agent_points()
    {
        // 建立代理
        $agent = Agent::create([
            'name' => '代理',
            'username' => 'agent',
            'account' => 'agent',
            'email' => 'agent@test.com',
            'level' => 1,
            'total_points' => 1000,
            'allocated_points' => 0,
            'remaining_points' => 500,
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        // 系統增加點數
        $this->pointService->systemAdjustPoints($agent, 200, '系統補償');

        // 驗證點數更新
        $agent->refresh();
        $this->assertEquals(1200, $agent->total_points);
        $this->assertEquals(700, $agent->remaining_points);

        // 驗證交易記錄
        $this->assertDatabaseHas('point_transactions', [
            'agent_id' => $agent->id,
            'type' => PointTransaction::TYPE_SYSTEM_ADJUSTMENT,
            'amount' => 200,
            'description' => '系統補償',
        ]);
    }

    /** @test */
    public function it_can_get_points_statistics()
    {
        // 建立測試資料
        $agent1 = Agent::create([
            'name' => '代理1',
            'username' => 'agent1',
            'account' => 'agent1',
            'email' => 'agent1@test.com',
            'level' => 1,
            'total_points' => 1000,
            'allocated_points' => 600,
            'remaining_points' => 400,
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        $agent2 = Agent::create([
            'name' => '代理2',
            'username' => 'agent2',
            'account' => 'agent2',
            'email' => 'agent2@test.com',
            'level' => 1,
            'total_points' => 500,
            'allocated_points' => 300,
            'remaining_points' => 200,
            'is_active' => false,
            'created_by' => $this->user->id,
        ]);

        $player1 = Player::create([
            'name' => '玩家1',
            'username' => 'player1',
            'account' => 'player1',
            'email' => 'player1@test.com',
            'agent_id' => $agent1->id,
            'points' => 300,
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        $player2 = Player::create([
            'name' => '玩家2',
            'username' => 'player2',
            'account' => 'player2',
            'email' => 'player2@test.com',
            'agent_id' => $agent1->id,
            'points' => 200,
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        // 取得全系統統計
        $stats = $this->pointService->getPointsStatistics();

        $this->assertEquals(2, $stats['total_agents']);
        $this->assertEquals(1, $stats['active_agents']);
        $this->assertEquals(2, $stats['total_players']);
        $this->assertEquals(2, $stats['active_players']);
        $this->assertEquals(1500, $stats['total_agent_points']);
        $this->assertEquals(900, $stats['total_allocated_points']);
        $this->assertEquals(600, $stats['total_remaining_points']);
        $this->assertEquals(500, $stats['total_player_points']);
        $this->assertEquals(2000, $stats['total_system_points']);
    }

    /** @test */
    public function it_can_audit_points()
    {
        // 建立有問題的代理（手動設定錯誤的點數）
        $agent = Agent::create([
            'name' => '問題代理',
            'username' => 'problemagent',
            'account' => 'problemagent',
            'email' => 'problem@test.com',
            'level' => 1,
            'total_points' => 1000,
            'allocated_points' => 500, // 實際應該是 300
            'remaining_points' => 600, // 實際應該是 700
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        // 建立子代理和玩家
        $childAgent = Agent::create([
            'name' => '子代理',
            'username' => 'childagent',
            'account' => 'childagent',
            'email' => 'child@test.com',
            'level' => 2,
            'parent_id' => $agent->id,
            'total_points' => 200,
            'allocated_points' => 0,
            'remaining_points' => 200,
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        $player = Player::create([
            'name' => '玩家',
            'username' => 'player',
            'account' => 'player',
            'email' => 'player@test.com',
            'agent_id' => $agent->id,
            'points' => 100,
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        // 執行稽核
        $auditResult = $this->pointService->auditPoints();

        // 驗證稽核結果
        $this->assertGreaterThan(0, $auditResult['total_issues']);
        $this->assertArrayHasKey('issues', $auditResult);
        $this->assertArrayHasKey('audit_time', $auditResult);
    }

    /** @test */
    public function it_can_get_transaction_history()
    {
        // 建立測試資料
        $agent = Agent::create([
            'name' => '代理',
            'username' => 'agent',
            'account' => 'agent',
            'email' => 'agent@test.com',
            'level' => 1,
            'total_points' => 1000,
            'allocated_points' => 0,
            'remaining_points' => 1000,
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        $player = Player::create([
            'name' => '玩家',
            'username' => 'player',
            'account' => 'player',
            'email' => 'player@test.com',
            'agent_id' => $agent->id,
            'points' => 500,
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        // 建立交易記錄
        PointTransaction::create([
            'agent_id' => $agent->id,
            'type' => PointTransaction::TYPE_AGENT_ALLOCATION,
            'amount' => 500,
            'balance_before' => 500,
            'balance_after' => 1000,
            'description' => '測試分配',
            'created_by' => $this->user->id,
        ]);

        PointTransaction::create([
            'player_id' => $player->id,
            'type' => PointTransaction::TYPE_PLAYER_ALLOCATION,
            'amount' => 200,
            'balance_before' => 300,
            'balance_after' => 500,
            'description' => '測試分配',
            'created_by' => $this->user->id,
        ]);

        // 取得全部交易歷史
        $allHistory = $this->pointService->getTransactionHistory();
        $this->assertEquals(2, $allHistory->total());

        // 取得代理交易歷史
        $agentHistory = $this->pointService->getTransactionHistory($agent);
        $this->assertEquals(1, $agentHistory->total());

        // 取得玩家交易歷史
        $playerHistory = $this->pointService->getTransactionHistory($player);
        $this->assertEquals(1, $playerHistory->total());
    }
}