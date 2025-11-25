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
use Illuminate\Support\Facades\Log;
use Mockery;

class PointServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PointService $pointService;
    protected $activityLogger;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試使用者
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        
        // Mock ActivityLogger
        $this->activityLogger = Mockery::mock(ActivityLogger::class);
        $this->activityLogger->shouldReceive('log')->andReturn(true);
        
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
        $agent = Agent::factory()->create([
            'total_points' => 0,
            'remaining_points' => 0,
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
        $fromAgent = Agent::factory()->create([
            'total_points' => 2000,
            'remaining_points' => 2000,
            'allocated_points' => 0,
        ]);

        // 建立目標代理
        $toAgent = Agent::factory()->create([
            'total_points' => 0,
            'remaining_points' => 0,
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
        $fromAgent = Agent::factory()->create([
            'total_points' => 100,
            'remaining_points' => 100,
        ]);

        $toAgent = Agent::factory()->create();

        // 嘗試分配超過剩餘點數
        $this->expectException(InsufficientPointsException::class);
        $this->pointService->allocatePointsToAgent($toAgent, 200, $fromAgent);
    }

    /** @test */
    public function it_can_allocate_points_to_player()
    {
        // 建立代理
        $agent = Agent::factory()->create([
            'total_points' => 1000,
            'remaining_points' => 1000,
            'allocated_points' => 0,
        ]);

        // 建立玩家
        $player = Player::factory()->create([
            'agent_id' => $agent->id,
            'points' => 0,
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
    public function it_can_recover_points_from_agent()
    {
        // 建立上層代理
        $parentAgent = Agent::factory()->create([
            'total_points' => 1000,
            'remaining_points' => 500,
            'allocated_points' => 500,
        ]);

        // 建立下層代理
        $childAgent = Agent::factory()->create([
            'total_points' => 500,
            'remaining_points' => 200,
            'allocated_points' => 300,
        ]);

        // 回收點數
        $this->pointService->recoverPointsFromAgent($childAgent, 200, $parentAgent);

        // 驗證下層代理點數
        $childAgent->refresh();
        $this->assertEquals(300, $childAgent->total_points);
        $this->assertEquals(0, $childAgent->remaining_points);

        // 驗證上層代理點數
        $parentAgent->refresh();
        $this->assertEquals(1000, $parentAgent->total_points);
        $this->assertEquals(700, $parentAgent->remaining_points);
        $this->assertEquals(300, $parentAgent->allocated_points);

        // 驗證交易記錄
        $this->assertDatabaseHas('point_transactions', [
            'agent_id' => $childAgent->id,
            'type' => PointTransaction::TYPE_AGENT_RECOVERY,
            'amount' => -200,
        ]);

        $this->assertDatabaseHas('point_transactions', [
            'agent_id' => $parentAgent->id,
            'type' => PointTransaction::TYPE_AGENT_RECOVERY,
            'amount' => 200,
        ]);
    }

    /** @test */
    public function it_can_recover_points_from_player()
    {
        // 建立代理
        $agent = Agent::factory()->create([
            'total_points' => 1000,
            'remaining_points' => 500,
            'allocated_points' => 500,
        ]);

        // 建立玩家
        $player = Player::factory()->create([
            'agent_id' => $agent->id,
            'points' => 300,
        ]);

        // 回收點數
        $this->pointService->recoverPointsFromPlayer($player, 100, $agent);

        // 驗證玩家點數
        $player->refresh();
        $this->assertEquals(200, $player->points);

        // 驗證代理點數
        $agent->refresh();
        $this->assertEquals(1000, $agent->total_points);
        $this->assertEquals(600, $agent->remaining_points);
        $this->assertEquals(400, $agent->allocated_points);

        // 驗證交易記錄
        $this->assertDatabaseHas('point_transactions', [
            'player_id' => $player->id,
            'type' => PointTransaction::TYPE_PLAYER_RECOVERY,
            'amount' => -100,
        ]);

        $this->assertDatabaseHas('point_transactions', [
            'agent_id' => $agent->id,
            'type' => PointTransaction::TYPE_PLAYER_RECOVERY,
            'amount' => 100,
        ]);
    }

    /** @test */
    public function it_can_system_adjust_agent_points()
    {
        // 建立代理
        $agent = Agent::factory()->create([
            'total_points' => 1000,
            'remaining_points' => 500,
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
    public function it_can_system_adjust_player_points()
    {
        // 建立玩家
        $player = Player::factory()->create([
            'points' => 500,
        ]);

        // 系統減少點數
        $this->pointService->systemAdjustPoints($player, -100, '系統扣除');

        // 驗證點數更新
        $player->refresh();
        $this->assertEquals(400, $player->points);

        // 驗證交易記錄
        $this->assertDatabaseHas('point_transactions', [
            'player_id' => $player->id,
            'type' => PointTransaction::TYPE_SYSTEM_ADJUSTMENT,
            'amount' => -100,
            'description' => '系統扣除',
        ]);
    }

    /** @test */
    public function it_can_transfer_player_to_new_agent()
    {
        // 建立舊代理
        $oldAgent = Agent::factory()->create([
            'total_points' => 1000,
            'remaining_points' => 500,
            'allocated_points' => 500,
        ]);

        // 建立新代理
        $newAgent = Agent::factory()->create([
            'total_points' => 800,
            'remaining_points' => 800,
            'allocated_points' => 0,
        ]);

        // 建立玩家
        $player = Player::factory()->create([
            'agent_id' => $oldAgent->id,
            'points' => 300,
        ]);

        // 轉移玩家
        $this->pointService->transferPlayerToNewAgent($player, $newAgent);

        // 驗證玩家代理關聯更新
        $player->refresh();
        $this->assertEquals($newAgent->id, $player->agent_id);
        $this->assertEquals(300, $player->points); // 玩家點數不變

        // 驗證舊代理點數
        $oldAgent->refresh();
        $this->assertEquals(1000, $oldAgent->total_points);
        $this->assertEquals(800, $oldAgent->remaining_points); // 回收 300 點
        $this->assertEquals(200, $oldAgent->allocated_points);

        // 驗證新代理點數
        $newAgent->refresh();
        $this->assertEquals(800, $newAgent->total_points);
        $this->assertEquals(500, $newAgent->remaining_points); // 分配 300 點
        $this->assertEquals(300, $newAgent->allocated_points);

        // 驗證轉移交易記錄
        $this->assertDatabaseHas('point_transactions', [
            'player_id' => $player->id,
            'type' => PointTransaction::TYPE_PLAYER_TRANSFER,
            'amount' => 0,
        ]);
    }

    /** @test */
    public function it_can_batch_allocate_to_agents()
    {
        // 建立來源代理
        $fromAgent = Agent::factory()->create([
            'total_points' => 2000,
            'remaining_points' => 2000,
            'allocated_points' => 0,
        ]);

        // 建立目標代理
        $agent1 = Agent::factory()->create(['total_points' => 0, 'remaining_points' => 0]);
        $agent2 = Agent::factory()->create(['total_points' => 0, 'remaining_points' => 0]);

        // 批量分配
        $allocations = [
            ['agent_id' => $agent1->id, 'amount' => 500],
            ['agent_id' => $agent2->id, 'amount' => 300],
        ];

        $this->pointService->batchAllocateToAgents($allocations, $fromAgent);

        // 驗證來源代理點數
        $fromAgent->refresh();
        $this->assertEquals(2000, $fromAgent->total_points);
        $this->assertEquals(1200, $fromAgent->remaining_points);
        $this->assertEquals(800, $fromAgent->allocated_points);

        // 驗證目標代理點數
        $agent1->refresh();
        $this->assertEquals(500, $agent1->total_points);

        $agent2->refresh();
        $this->assertEquals(300, $agent2->total_points);
    }

    /** @test */
    public function it_can_batch_allocate_to_players()
    {
        // 建立代理
        $agent = Agent::factory()->create([
            'total_points' => 1000,
            'remaining_points' => 1000,
            'allocated_points' => 0,
        ]);

        // 建立玩家
        $player1 = Player::factory()->create(['agent_id' => $agent->id, 'points' => 0]);
        $player2 = Player::factory()->create(['agent_id' => $agent->id, 'points' => 0]);

        // 批量分配
        $allocations = [
            ['player_id' => $player1->id, 'amount' => 200],
            ['player_id' => $player2->id, 'amount' => 150],
        ];

        $this->pointService->batchAllocateToPlayers($allocations, $agent);

        // 驗證代理點數
        $agent->refresh();
        $this->assertEquals(1000, $agent->total_points);
        $this->assertEquals(650, $agent->remaining_points);
        $this->assertEquals(350, $agent->allocated_points);

        // 驗證玩家點數
        $player1->refresh();
        $this->assertEquals(200, $player1->points);

        $player2->refresh();
        $this->assertEquals(150, $player2->points);
    }

    /** @test */
    public function it_can_get_points_statistics()
    {
        // 建立測試資料
        $agent1 = Agent::factory()->create([
            'total_points' => 1000,
            'allocated_points' => 600,
            'remaining_points' => 400,
            'is_active' => true,
        ]);

        $agent2 = Agent::factory()->create([
            'total_points' => 500,
            'allocated_points' => 300,
            'remaining_points' => 200,
            'is_active' => false,
        ]);

        $player1 = Player::factory()->create(['agent_id' => $agent1->id, 'points' => 300, 'is_active' => true]);
        $player2 = Player::factory()->create(['agent_id' => $agent1->id, 'points' => 200, 'is_active' => true]);
        $player3 = Player::factory()->create(['agent_id' => $agent2->id, 'points' => 100, 'is_active' => false]);

        // 取得全系統統計
        $stats = $this->pointService->getPointsStatistics();

        $this->assertEquals(2, $stats['total_agents']);
        $this->assertEquals(1, $stats['active_agents']);
        $this->assertEquals(3, $stats['total_players']);
        $this->assertEquals(2, $stats['active_players']);
        $this->assertEquals(1500, $stats['total_agent_points']);
        $this->assertEquals(900, $stats['total_allocated_points']);
        $this->assertEquals(600, $stats['total_remaining_points']);
        $this->assertEquals(600, $stats['total_player_points']);
        $this->assertEquals(2100, $stats['total_system_points']);
    }

    /** @test */
    public function it_can_audit_points()
    {
        // 建立有問題的代理（手動設定錯誤的點數）
        $agent = Agent::factory()->create([
            'total_points' => 1000,
            'allocated_points' => 500, // 實際應該是 300
            'remaining_points' => 600, // 實際應該是 700
        ]);

        // 建立子代理和玩家
        $childAgent = Agent::factory()->create([
            'parent_id' => $agent->id,
            'total_points' => 200,
        ]);

        $player = Player::factory()->create([
            'agent_id' => $agent->id,
            'points' => 100,
        ]);

        // 執行稽核
        $auditResult = $this->pointService->auditPoints();

        // 驗證稽核結果
        $this->assertGreaterThan(0, $auditResult['total_issues']);
        $this->assertArrayHasKey('issues', $auditResult);
        $this->assertArrayHasKey('audit_time', $auditResult);
    }

    /** @test */
    public function it_can_fix_points_inconsistencies()
    {
        // 建立有問題的代理
        $agent = Agent::factory()->create([
            'total_points' => 1000,
            'allocated_points' => 500,
            'remaining_points' => 600, // 錯誤：應該是 500
        ]);

        // 模擬稽核問題
        $issues = [
            [
                'type' => 'agent_remaining_mismatch',
                'agent_id' => $agent->id,
                'agent_name' => $agent->name,
                'recorded_remaining' => 600,
                'calculated_remaining' => 500,
                'difference' => 100,
            ]
        ];

        // 修復問題
        $result = $this->pointService->fixPointsInconsistencies($issues);

        // 驗證修復結果
        $this->assertEquals(1, $result['fixed_count']);
        $this->assertEquals(0, $result['failed_count']);

        // 驗證代理點數已修復
        $agent->refresh();
        $this->assertEquals(500, $agent->remaining_points);
    }

    /** @test */
    public function it_can_get_transaction_history()
    {
        // 建立測試資料
        $agent = Agent::factory()->create();
        $player = Player::factory()->create();

        // 建立交易記錄
        PointTransaction::factory()->create([
            'agent_id' => $agent->id,
            'type' => PointTransaction::TYPE_AGENT_ALLOCATION,
            'amount' => 500,
        ]);

        PointTransaction::factory()->create([
            'player_id' => $player->id,
            'type' => PointTransaction::TYPE_PLAYER_ALLOCATION,
            'amount' => 200,
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

    /** @test */
    public function it_handles_database_transaction_rollback_on_error()
    {
        // 建立代理
        $agent = Agent::factory()->create([
            'total_points' => 1000,
            'remaining_points' => 1000,
        ]);

        // Mock 資料庫錯誤
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();
        
        // 模擬在交易過程中發生錯誤
        $this->expectException(\Exception::class);
        
        // 使用無效的代理 ID 觸發錯誤
        $invalidAgent = new Agent();
        $invalidAgent->id = 99999;
        
        $this->pointService->allocatePointsToAgent($invalidAgent, 500, $agent);
    }

    /** @test */
    public function it_logs_activities_for_point_operations()
    {
        // 重新建立真實的 ActivityLogger 來測試日誌記錄
        $realActivityLogger = app(ActivityLogger::class);
        $pointService = new PointService($realActivityLogger);

        // 建立測試資料
        $agent = Agent::factory()->create([
            'total_points' => 0,
            'remaining_points' => 0,
        ]);

        // 執行點數分配
        $pointService->allocatePointsToAgent($agent, 500);

        // 驗證活動日誌記錄
        $this->assertDatabaseHas('activities', [
            'type' => 'points_allocated_to_agent',
            'subject_id' => $agent->id,
            'subject_type' => Agent::class,
        ]);
    }
}