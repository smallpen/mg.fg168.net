<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\PlayerService;
use App\Services\PointService;
use App\Services\ActivityLogger;
use App\Models\Player;
use App\Models\Agent;
use App\Models\User;
use App\Models\PointTransaction;
use App\Exceptions\InsufficientPointsException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;

/**
 * 玩家服務最終測試
 * 
 * 專注於核心業務邏輯測試，避免 Faker 相關問題
 */
class PlayerServiceFinalTest extends TestCase
{
    use RefreshDatabase;

    protected PlayerService $playerService;
    protected $mockPointService;
    protected $mockActivityLogger;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立 Mock 服務
        $this->mockPointService = Mockery::mock(PointService::class);
        $this->mockActivityLogger = Mockery::mock(ActivityLogger::class);

        // 建立 PlayerService 實例
        $this->playerService = new PlayerService(
            $this->mockPointService,
            $this->mockActivityLogger
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * 建立測試使用者
     */
    private function createTestUser(string $username = 'testuser'): User
    {
        return User::create([
            'username' => $username,
            'name' => '測試使用者',
            'email' => $username . '@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
    }

    /**
     * 建立測試代理
     */
    private function createTestAgent(string $prefix = 'a', int $level = 1): Agent
    {
        return Agent::create([
            'name' => '測試代理',
            'username' => 'testagent',
            'account' => $prefix . 'testagent',
            'email' => 'agent@example.com',
            'prefix' => $prefix,
            'level' => $level,
            'total_points' => 1000.00,
            'allocated_points' => 200.00,
            'remaining_points' => 800.00,
            'is_active' => true,
            'created_by' => 1,
        ]);
    }

    /**
     * 建立測試玩家
     */
    private function createTestPlayer(Agent $agent, array $overrides = []): Player
    {
        $data = array_merge([
            'name' => '測試玩家',
            'username' => 'testplayer',
            'account' => $agent->full_prefix . 'testplayer',
            'email' => 'player@example.com',
            'agent_id' => $agent->id,
            'points' => 100.00,
            'is_active' => true,
            'created_by' => 1,
        ], $overrides);

        return Player::create($data);
    }

    /**
     * 測試建立玩家 - 基本功能
     * 
     * @test
     */
    public function test_create_player_basic_functionality()
    {
        $user = $this->createTestUser();
        $this->actingAs($user);

        $agent = $this->createTestAgent();

        $playerData = [
            'name' => '新玩家',
            'username' => 'newplayer',
            'email' => 'newplayer@example.com',
            'agent_id' => $agent->id,
            'initial_points' => 150.00,
            'is_active' => true,
        ];

        // 設定 Mock 期望
        $this->mockPointService
            ->shouldReceive('allocatePointsToPlayer')
            ->once()
            ->with(
                Mockery::type(Player::class),
                150.00,
                $agent
            );

        $this->mockActivityLogger
            ->shouldReceive('log')
            ->once()
            ->with(
                'player_created',
                Mockery::type(Player::class),
                Mockery::type('array')
            );

        // 執行測試
        $player = $this->playerService->createPlayer($playerData);

        // 驗證結果
        $this->assertInstanceOf(Player::class, $player);
        $this->assertEquals('新玩家', $player->name);
        $this->assertEquals('newplayer', $player->username);
        $this->assertEquals('anewplayer', $player->account); // 繼承代理前置符號
        $this->assertEquals($agent->id, $player->agent_id);
        $this->assertEquals($user->id, $player->created_by);
        $this->assertTrue($player->is_active);
    }

    /**
     * 測試玩家帳號前置符號繼承
     * 
     * @test
     */
    public function test_player_account_inherits_agent_prefix()
    {
        $user = $this->createTestUser('prefixuser');
        $this->actingAs($user);

        // 建立多層級代理結構
        $parentAgent = $this->createTestAgent('b', 1);
        
        $childAgent = Agent::create([
            'name' => '子代理',
            'username' => 'childagent',
            'account' => 'bchildagent',
            'email' => 'child@example.com',
            'parent_id' => $parentAgent->id,
            'level' => 2,
            'total_points' => 500.00,
            'allocated_points' => 100.00,
            'remaining_points' => 400.00,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $playerData = [
            'name' => '子代理玩家',
            'username' => 'childplayer',
            'email' => 'childplayer@example.com',
            'agent_id' => $childAgent->id,
            'initial_points' => 0,
        ];

        // 設定 Mock（不分配點數）
        $this->mockActivityLogger
            ->shouldReceive('log')
            ->once();

        // 執行測試
        $player = $this->playerService->createPlayer($playerData);

        // 驗證前置符號繼承
        $this->assertEquals('bchildplayer', $player->account);
        $this->assertEquals('b', $player->full_prefix);
    }

    /**
     * 測試更新玩家基本資料
     * 
     * @test
     */
    public function test_update_player_basic_data()
    {
        $user = $this->createTestUser('updateuser');
        $this->actingAs($user);

        $agent = $this->createTestAgent();
        $player = $this->createTestPlayer($agent, [
            'name' => '原始玩家',
            'username' => 'originalplayer',
            'account' => 'aoriginalplayer',
        ]);

        $updateData = [
            'name' => '更新後玩家',
            'email' => 'updated@example.com',
            'notes' => '更新後的備註',
        ];

        // 設定 Mock
        $this->mockActivityLogger
            ->shouldReceive('log')
            ->once()
            ->with(
                'player_updated',
                $player,
                Mockery::type('array')
            );

        // 執行測試
        $updatedPlayer = $this->playerService->updatePlayer($player, $updateData);

        // 驗證結果
        $this->assertEquals('更新後玩家', $updatedPlayer->name);
        $this->assertEquals('updated@example.com', $updatedPlayer->email);
        $this->assertEquals('更新後的備註', $updatedPlayer->notes);
        $this->assertEquals('aoriginalplayer', $updatedPlayer->account); // 帳號不變
    }

    /**
     * 測試更新玩家用戶名
     * 
     * @test
     */
    public function test_update_player_username()
    {
        $user = $this->createTestUser('usernameuser');
        $this->actingAs($user);

        $agent = $this->createTestAgent();
        $player = $this->createTestPlayer($agent, [
            'username' => 'oldname',
            'account' => 'aoldname',
        ]);

        $updateData = [
            'username' => 'newname',
        ];

        // 設定 Mock
        $this->mockActivityLogger
            ->shouldReceive('log')
            ->once();

        // 執行測試
        $updatedPlayer = $this->playerService->updatePlayer($player, $updateData);

        // 驗證帳號更新
        $this->assertEquals('newname', $updatedPlayer->username);
        $this->assertEquals('anewname', $updatedPlayer->account);
    }

    /**
     * 測試刪除玩家
     * 
     * @test
     */
    public function test_delete_player()
    {
        $user = $this->createTestUser('deleteuser');
        $this->actingAs($user);

        $agent = $this->createTestAgent();
        $player = $this->createTestPlayer($agent, [
            'points' => 75.00,
        ]);

        // 設定 Mock 期望
        $this->mockPointService
            ->shouldReceive('recoverPointsFromPlayer')
            ->once()
            ->with($player, 75.00, $agent);

        $this->mockActivityLogger
            ->shouldReceive('log')
            ->once()
            ->with(
                'player_deleted',
                $player,
                Mockery::type('array')
            );

        // 執行測試
        $result = $this->playerService->deletePlayer($player);

        // 驗證結果
        $this->assertTrue($result);
        $this->assertSoftDeleted('players', ['id' => $player->id]);
    }

    /**
     * 測試刪除沒有點數的玩家
     * 
     * @test
     */
    public function test_delete_player_with_no_points()
    {
        $user = $this->createTestUser('nopointsuser');
        $this->actingAs($user);

        $agent = $this->createTestAgent();
        $player = $this->createTestPlayer($agent, [
            'points' => 0.00,
        ]);

        // 設定 Mock（不應該調用點數回收）
        $this->mockPointService
            ->shouldNotReceive('recoverPointsFromPlayer');

        $this->mockActivityLogger
            ->shouldReceive('log')
            ->once();

        // 執行測試
        $result = $this->playerService->deletePlayer($player);

        // 驗證結果
        $this->assertTrue($result);
        $this->assertSoftDeleted('players', ['id' => $player->id]);
    }

    /**
     * 測試檢查玩家是否可以進行遊戲
     * 
     * @test
     */
    public function test_can_play()
    {
        $agent = $this->createTestAgent();

        // 啟用且有足夠點數的玩家
        $activePlayer = $this->createTestPlayer($agent, [
            'is_active' => true,
            'points' => 100.00,
        ]);

        // 停用的玩家
        $inactivePlayer = $this->createTestPlayer($agent, [
            'username' => 'inactiveplayer',
            'account' => 'ainactiveplayer',
            'is_active' => false,
            'points' => 100.00,
        ]);

        // 點數不足的玩家
        $poorPlayer = $this->createTestPlayer($agent, [
            'username' => 'poorplayer',
            'account' => 'apoorplayer',
            'is_active' => true,
            'points' => 10.00,
        ]);

        // 執行測試
        $this->assertTrue($this->playerService->canPlay($activePlayer, 50.00));
        $this->assertFalse($this->playerService->canPlay($inactivePlayer, 50.00));
        $this->assertFalse($this->playerService->canPlay($poorPlayer, 50.00));
        $this->assertTrue($this->playerService->canPlay($activePlayer, 0));
    }

    /**
     * 測試玩家點數消費
     * 
     * @test
     */
    public function test_consume_points()
    {
        $user = $this->createTestUser('consumeuser');
        $this->actingAs($user);

        $agent = $this->createTestAgent();
        $player = $this->createTestPlayer($agent, [
            'points' => 200.00,
        ]);

        // 設定 Mock
        $this->mockActivityLogger
            ->shouldReceive('log')
            ->once()
            ->with(
                'player_points_consumed',
                $player,
                Mockery::type('array')
            );

        // 執行測試
        $result = $this->playerService->consumePoints($player, 50.00, '遊戲消費');

        // 驗證結果
        $this->assertTrue($result);
        $player->refresh();
        $this->assertEquals(150.00, $player->points);

        // 驗證交易記錄
        $this->assertDatabaseHas('point_transactions', [
            'player_id' => $player->id,
            'type' => 'player_consumption',
            'amount' => -50.00,
            'description' => '遊戲消費',
        ]);
    }

    /**
     * 測試點數不足時的消費
     * 
     * @test
     */
    public function test_consume_points_insufficient_balance()
    {
        $agent = $this->createTestAgent();
        $player = $this->createTestPlayer($agent, [
            'points' => 30.00,
        ]);

        // 驗證例外拋出
        $this->expectException(InsufficientPointsException::class);

        $this->playerService->consumePoints($player, 50.00);
    }

    /**
     * 測試取得玩家統計資訊
     * 
     * @test
     */
    public function test_get_player_statistics()
    {
        $agent = $this->createTestAgent();
        $player = $this->createTestPlayer($agent, [
            'points' => 150.00,
        ]);

        // 建立點數交易記錄
        PointTransaction::create([
            'player_id' => $player->id,
            'type' => PointTransaction::TYPE_PLAYER_ALLOCATION,
            'amount' => 200.00,
            'balance_before' => 0.00,
            'balance_after' => 200.00,
            'description' => '初始分配',
            'created_by' => 1,
        ]);

        PointTransaction::create([
            'player_id' => $player->id,
            'type' => PointTransaction::TYPE_PLAYER_CONSUMPTION,
            'amount' => -50.00,
            'balance_before' => 200.00,
            'balance_after' => 150.00,
            'description' => '遊戲消費',
            'created_by' => 1,
        ]);

        // 執行測試
        $stats = $this->playerService->getPlayerStatistics($player);

        // 驗證統計資訊
        $this->assertEquals(150.00, $stats['current_points']);
        $this->assertEquals(2, $stats['total_transactions']);
        $this->assertEquals(200.00, $stats['total_received']);
        $this->assertEquals(50.00, $stats['total_spent']);
        $this->assertEquals(1, $stats['agent_level']);
        $this->assertIsInt($stats['account_age_days']);
    }

    /**
     * 測試批次更新玩家
     * 
     * @test
     */
    public function test_batch_update_players()
    {
        $user = $this->createTestUser('batchuser');
        $this->actingAs($user);

        $agent = $this->createTestAgent();

        // 建立多個玩家
        $player1 = $this->createTestPlayer($agent, [
            'username' => 'batchplayer1',
            'account' => 'abatchplayer1',
            'is_active' => true,
        ]);

        $player2 = $this->createTestPlayer($agent, [
            'username' => 'batchplayer2',
            'account' => 'abatchplayer2',
            'is_active' => true,
        ]);

        $player3 = $this->createTestPlayer($agent, [
            'username' => 'batchplayer3',
            'account' => 'abatchplayer3',
            'is_active' => true,
        ]);

        $playerIds = [$player1->id, $player2->id, $player3->id];
        $updateData = ['is_active' => false];

        // 設定 Mock
        $this->mockActivityLogger
            ->shouldReceive('log')
            ->once()
            ->with(
                'players_batch_updated',
                null,
                Mockery::type('array')
            );

        // 執行測試
        $updatedCount = $this->playerService->batchUpdatePlayers($playerIds, $updateData);

        // 驗證結果
        $this->assertEquals(3, $updatedCount);

        // 驗證資料庫更新
        $this->assertDatabaseHas('players', [
            'id' => $player1->id,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('players', [
            'id' => $player2->id,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('players', [
            'id' => $player3->id,
            'is_active' => false,
        ]);
    }

    /**
     * 測試取得代理玩家統計
     * 
     * @test
     */
    public function test_get_agent_players_statistics()
    {
        $agent = $this->createTestAgent();

        // 建立多個玩家
        $this->createTestPlayer($agent, [
            'username' => 'statsplayer1',
            'account' => 'astatsplayer1',
            'is_active' => true,
            'points' => 100.00,
        ]);

        $this->createTestPlayer($agent, [
            'username' => 'statsplayer2',
            'account' => 'astatsplayer2',
            'is_active' => false,
            'points' => 50.00,
        ]);

        $this->createTestPlayer($agent, [
            'username' => 'statsplayer3',
            'account' => 'astatsplayer3',
            'is_active' => true,
            'points' => 0.00,
        ]);

        // 執行測試
        $stats = $this->playerService->getAgentPlayersStatistics($agent);

        // 驗證統計資訊
        $this->assertEquals(3, $stats['total_players']);
        $this->assertEquals(2, $stats['active_players']);
        $this->assertEquals(1, $stats['inactive_players']);
        $this->assertEquals(150.00, $stats['total_player_points']);
        $this->assertEquals(50.00, $stats['average_player_points']);
        $this->assertEquals(2, $stats['players_with_points']);
        $this->assertEquals(1, $stats['players_without_points']);
    }
}