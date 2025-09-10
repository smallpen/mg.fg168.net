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
use Illuminate\Support\Facades\Log;
use Mockery;

/**
 * 玩家服務單元測試
 * 
 * 測試玩家管理的核心業務邏輯，包括：
 * - 玩家建立與代理關聯
 * - 帳號生成和前置符號繼承
 * - 點數分配邏輯
 * - 玩家更新和刪除
 * - 代理轉移功能
 */
class PlayerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PlayerService $playerService;
    protected $mockPointService;
    protected $mockActivityLogger;
    protected User $testUser;
    protected Agent $testAgent;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立測試使用者
        $this->testUser = User::factory()->create([
            'username' => 'testuser',
            'name' => '測試使用者',
            'email' => 'test@example.com',
        ]);

        // 模擬認證使用者
        $this->actingAs($this->testUser);

        // 建立測試代理
        $this->testAgent = Agent::factory()->create([
            'name' => '測試代理',
            'username' => 'testagent',
            'account' => 'atestagent',
            'prefix' => 'a',
            'level' => 1,
            'total_points' => 1000.00,
            'remaining_points' => 800.00,
            'allocated_points' => 200.00,
        ]);

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
     * 測試建立玩家 - 基本功能
     * 
     * @test
     */
    public function test_create_player_basic_functionality()
    {
        // 準備測試資料
        $playerData = [
            'name' => '測試玩家',
            'username' => 'testplayer',
            'email' => 'player@example.com',
            'phone' => '0912345678',
            'agent_id' => $this->testAgent->id,
            'initial_points' => 100.00,
            'is_active' => true,
            'notes' => '測試玩家備註',
        ];

        // 設定 Mock 期望
        $this->mockPointService
            ->shouldReceive('allocatePointsToPlayer')
            ->once()
            ->with(
                Mockery::type(Player::class),
                100.00,
                $this->testAgent
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
        $this->assertEquals('測試玩家', $player->name);
        $this->assertEquals('testplayer', $player->username);
        $this->assertEquals('atestplayer', $player->account); // 繼承代理前置符號
        $this->assertEquals($this->testAgent->id, $player->agent_id);
        $this->assertEquals($this->testUser->id, $player->created_by);
        $this->assertTrue($player->is_active);

        // 驗證資料庫記錄
        $this->assertDatabaseHas('players', [
            'name' => '測試玩家',
            'username' => 'testplayer',
            'account' => 'atestplayer',
            'agent_id' => $this->testAgent->id,
        ]);
    }

    /**
     * 測試玩家帳號前置符號繼承
     * 
     * @test
     */
    public function test_player_account_inherits_agent_prefix()
    {
        // 建立多層級代理結構
        $parentAgent = Agent::factory()->create([
            'prefix' => 'b',
            'level' => 1,
            'username' => 'parent',
            'account' => 'bparent',
        ]);

        $childAgent = Agent::factory()->create([
            'parent_id' => $parentAgent->id,
            'level' => 2,
            'username' => 'child',
            'account' => 'bchild',
        ]);

        $playerData = [
            'name' => '子代理玩家',
            'username' => 'childplayer',
            'email' => 'child@example.com',
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
     * 測試建立玩家時點數不足的情況
     * 
     * @test
     */
    public function test_create_player_insufficient_points()
    {
        $playerData = [
            'name' => '點數不足玩家',
            'username' => 'nopointsplayer',
            'email' => 'nopoints@example.com',
            'agent_id' => $this->testAgent->id,
            'initial_points' => 500.00,
        ];

        // 設定 Mock 拋出例外
        $this->mockPointService
            ->shouldReceive('allocatePointsToPlayer')
            ->once()
            ->andThrow(new InsufficientPointsException('代理點數不足'));

        // 驗證例外拋出
        $this->expectException(InsufficientPointsException::class);
        $this->expectExceptionMessage('代理點數不足');

        $this->playerService->createPlayer($playerData);

        // 驗證資料庫沒有建立記錄（交易回滾）
        $this->assertDatabaseMissing('players', [
            'username' => 'nopointsplayer',
        ]);
    }

    /**
     * 測試更新玩家基本資料
     * 
     * @test
     */
    public function test_update_player_basic_data()
    {
        // 建立測試玩家
        $player = Player::factory()->create([
            'name' => '原始玩家',
            'username' => 'originalplayer',
            'account' => 'aoriginalplayer',
            'email' => 'original@example.com',
            'agent_id' => $this->testAgent->id,
            'points' => 50.00,
        ]);

        $updateData = [
            'name' => '更新後玩家',
            'email' => 'updated@example.com',
            'phone' => '0987654321',
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
        $this->assertEquals('0987654321', $updatedPlayer->phone);
        $this->assertEquals('更新後的備註', $updatedPlayer->notes);

        // 驗證帳號沒有改變
        $this->assertEquals('aoriginalplayer', $updatedPlayer->account);
    }

    /**
     * 測試更新玩家用戶名
     * 
     * @test
     */
    public function test_update_player_username()
    {
        $player = Player::factory()->create([
            'username' => 'oldname',
            'account' => 'aoldname',
            'agent_id' => $this->testAgent->id,
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
     * 測試轉移玩家到新代理
     * 
     * @test
     */
    public function test_transfer_player_to_new_agent()
    {
        // 建立新代理
        $newAgent = Agent::factory()->create([
            'prefix' => 'c',
            'level' => 1,
            'username' => 'newagent',
            'account' => 'cnewagent',
            'remaining_points' => 500.00,
        ]);

        // 建立玩家
        $player = Player::factory()->create([
            'username' => 'transferplayer',
            'account' => 'atransferplayer',
            'agent_id' => $this->testAgent->id,
            'points' => 100.00,
        ]);

        $updateData = [
            'agent_id' => $newAgent->id,
        ];

        // 設定 Mock 期望
        $this->mockPointService
            ->shouldReceive('recoverPointsFromPlayer')
            ->once()
            ->with($player, 100.00, $this->testAgent);

        $this->mockPointService
            ->shouldReceive('allocatePointsToPlayer')
            ->once()
            ->with($player, 100.00, $newAgent);

        $this->mockActivityLogger
            ->shouldReceive('log')
            ->once();

        // 執行測試
        $updatedPlayer = $this->playerService->updatePlayer($player, $updateData);

        // 驗證代理轉移
        $this->assertEquals($newAgent->id, $updatedPlayer->agent_id);
        $this->assertEquals('ctransferplayer', $updatedPlayer->account);
    }

    /**
     * 測試刪除玩家
     * 
     * @test
     */
    public function test_delete_player()
    {
        $player = Player::factory()->create([
            'name' => '待刪除玩家',
            'agent_id' => $this->testAgent->id,
            'points' => 75.00,
        ]);

        // 設定 Mock 期望
        $this->mockPointService
            ->shouldReceive('recoverPointsFromPlayer')
            ->once()
            ->with($player, 75.00, $this->testAgent);

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
        $player = Player::factory()->create([
            'agent_id' => $this->testAgent->id,
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
     * 測試取得玩家統計資訊
     * 
     * @test
     */
    public function test_get_player_statistics()
    {
        $player = Player::factory()->create([
            'agent_id' => $this->testAgent->id,
            'points' => 150.00,
        ]);

        // 建立點數交易記錄
        PointTransaction::factory()->create([
            'player_id' => $player->id,
            'type' => PointTransaction::TYPE_PLAYER_ALLOCATION,
            'amount' => 200.00,
        ]);

        PointTransaction::factory()->create([
            'player_id' => $player->id,
            'type' => PointTransaction::TYPE_PLAYER_CONSUMPTION,
            'amount' => -50.00,
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
     * 測試檢查玩家是否可以進行遊戲
     * 
     * @test
     */
    public function test_can_play()
    {
        // 啟用且有足夠點數的玩家
        $activePlayer = Player::factory()->create([
            'is_active' => true,
            'points' => 100.00,
        ]);

        // 停用的玩家
        $inactivePlayer = Player::factory()->create([
            'is_active' => false,
            'points' => 100.00,
        ]);

        // 點數不足的玩家
        $poorPlayer = Player::factory()->create([
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
        $player = Player::factory()->create([
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
        $player = Player::factory()->create([
            'points' => 30.00,
        ]);

        // 驗證例外拋出
        $this->expectException(InsufficientPointsException::class);

        $this->playerService->consumePoints($player, 50.00);
    }

    /**
     * 測試批次更新玩家
     * 
     * @test
     */
    public function test_batch_update_players()
    {
        // 建立多個玩家
        $players = Player::factory()->count(3)->create([
            'is_active' => true,
        ]);

        $playerIds = $players->pluck('id')->toArray();
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
        foreach ($players as $player) {
            $this->assertDatabaseHas('players', [
                'id' => $player->id,
                'is_active' => false,
            ]);
        }
    }

    /**
     * 測試取得代理玩家統計
     * 
     * @test
     */
    public function test_get_agent_players_statistics()
    {
        // 建立多個玩家
        Player::factory()->create([
            'agent_id' => $this->testAgent->id,
            'is_active' => true,
            'points' => 100.00,
        ]);

        Player::factory()->create([
            'agent_id' => $this->testAgent->id,
            'is_active' => false,
            'points' => 50.00,
        ]);

        Player::factory()->create([
            'agent_id' => $this->testAgent->id,
            'is_active' => true,
            'points' => 0.00,
        ]);

        // 執行測試
        $stats = $this->playerService->getAgentPlayersStatistics($this->testAgent);

        // 驗證統計資訊
        $this->assertEquals(3, $stats['total_players']);
        $this->assertEquals(2, $stats['active_players']);
        $this->assertEquals(1, $stats['inactive_players']);
        $this->assertEquals(150.00, $stats['total_player_points']);
        $this->assertEquals(50.00, $stats['average_player_points']);
        $this->assertEquals(2, $stats['players_with_points']);
        $this->assertEquals(1, $stats['players_without_points']);
    }

    /**
     * 測試資料庫交易回滾
     * 
     * @test
     */
    public function test_database_transaction_rollback_on_error()
    {
        $playerData = [
            'name' => '交易測試玩家',
            'username' => 'transactionplayer',
            'email' => 'transaction@example.com',
            'agent_id' => $this->testAgent->id,
            'initial_points' => 100.00,
        ];

        // 設定 Mock 拋出例外
        $this->mockPointService
            ->shouldReceive('allocatePointsToPlayer')
            ->once()
            ->andThrow(new \Exception('點數分配失敗'));

        // 驗證例外拋出
        $this->expectException(\Exception::class);

        try {
            $this->playerService->createPlayer($playerData);
        } catch (\Exception $e) {
            // 驗證資料庫沒有建立記錄（交易回滾）
            $this->assertDatabaseMissing('players', [
                'username' => 'transactionplayer',
            ]);
            
            throw $e;
        }
    }

    /**
     * 測試日誌記錄
     * 
     * @test
     */
    public function test_logging_functionality()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('玩家建立成功', Mockery::type('array'));

        $playerData = [
            'name' => '日誌測試玩家',
            'username' => 'logplayer',
            'email' => 'log@example.com',
            'agent_id' => $this->testAgent->id,
            'initial_points' => 0,
        ];

        // 設定 Mock
        $this->mockActivityLogger
            ->shouldReceive('log')
            ->once();

        // 執行測試
        $this->playerService->createPlayer($playerData);
    }
}