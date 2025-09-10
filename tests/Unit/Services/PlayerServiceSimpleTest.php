<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\PlayerService;
use App\Services\PointService;
use App\Services\ActivityLogger;
use App\Models\Player;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

/**
 * 玩家服務簡單測試
 * 
 * 基本功能測試，確保核心邏輯正常運作
 */
class PlayerServiceSimpleTest extends TestCase
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
     * 測試 PlayerService 實例化
     * 
     * @test
     */
    public function test_player_service_instantiation()
    {
        $this->assertInstanceOf(PlayerService::class, $this->playerService);
    }

    /**
     * 測試建立玩家的基本流程
     * 
     * @test
     */
    public function test_create_player_basic_flow()
    {
        // 建立測試使用者和代理
        $user = User::factory()->create([
            'username' => 'testuser',
            'name' => '測試使用者',
            'email' => 'test@example.com',
        ]);
        $this->actingAs($user);

        $agent = Agent::factory()->create([
            'name' => '測試代理',
            'username' => 'testagent',
            'account' => 'atestagent',
            'prefix' => 'a',
            'level' => 1,
            'remaining_points' => 1000.00,
        ]);

        $playerData = [
            'name' => '測試玩家',
            'username' => 'testplayer',
            'email' => 'test@example.com',
            'agent_id' => $agent->id,
            'initial_points' => 100.00,
        ];

        // 設定 Mock 期望
        $this->mockPointService
            ->shouldReceive('allocatePointsToPlayer')
            ->once();

        $this->mockActivityLogger
            ->shouldReceive('log')
            ->once();

        // 執行測試
        $player = $this->playerService->createPlayer($playerData);

        // 基本驗證
        $this->assertInstanceOf(Player::class, $player);
        $this->assertEquals('測試玩家', $player->name);
        $this->assertEquals('testplayer', $player->username);
        $this->assertEquals($agent->id, $player->agent_id);
    }

    /**
     * 測試玩家帳號前置符號繼承
     * 
     * @test
     */
    public function test_player_account_prefix_inheritance()
    {
        $user = User::factory()->create([
            'username' => 'prefixuser',
            'name' => '前置符號測試使用者',
            'email' => 'prefixuser@example.com',
        ]);
        $this->actingAs($user);

        $agent = Agent::factory()->create([
            'name' => '前置符號代理',
            'username' => 'prefixagent',
            'account' => 'bprefixagent',
            'prefix' => 'b',
            'level' => 1,
        ]);

        $playerData = [
            'name' => '前置符號測試玩家',
            'username' => 'prefixplayer',
            'email' => 'prefix@example.com',
            'agent_id' => $agent->id,
            'initial_points' => 0,
        ];

        // 設定 Mock（不分配點數）
        $this->mockActivityLogger
            ->shouldReceive('log')
            ->once();

        // 執行測試
        $player = $this->playerService->createPlayer($playerData);

        // 驗證前置符號繼承
        $this->assertEquals('bprefixplayer', $player->account);
    }

    /**
     * 測試更新玩家基本資料
     * 
     * @test
     */
    public function test_update_player_basic_data()
    {
        $user = User::factory()->create([
            'username' => 'updateuser',
            'name' => '更新測試使用者',
            'email' => 'updateuser@example.com',
        ]);
        $this->actingAs($user);

        $agent = Agent::factory()->create([
            'name' => '更新測試代理',
            'username' => 'updateagent',
            'account' => 'aupdateagent',
            'prefix' => 'a',
            'level' => 1,
        ]);
        
        $player = Player::factory()->create([
            'agent_id' => $agent->id,
            'name' => '原始名稱',
        ]);

        $updateData = [
            'name' => '更新後名稱',
            'notes' => '更新後備註',
        ];

        // 設定 Mock
        $this->mockActivityLogger
            ->shouldReceive('log')
            ->once();

        // 執行測試
        $updatedPlayer = $this->playerService->updatePlayer($player, $updateData);

        // 驗證更新
        $this->assertEquals('更新後名稱', $updatedPlayer->name);
        $this->assertEquals('更新後備註', $updatedPlayer->notes);
    }

    /**
     * 測試刪除玩家
     * 
     * @test
     */
    public function test_delete_player()
    {
        $user = User::factory()->create([
            'username' => 'deleteuser',
            'name' => '刪除測試使用者',
            'email' => 'deleteuser@example.com',
        ]);
        $this->actingAs($user);

        $agent = Agent::factory()->create([
            'name' => '刪除測試代理',
            'username' => 'deleteagent',
            'account' => 'adeleteagent',
            'prefix' => 'a',
            'level' => 1,
        ]);
        
        $player = Player::factory()->create([
            'agent_id' => $agent->id,
            'points' => 50.00,
        ]);

        // 設定 Mock 期望
        $this->mockPointService
            ->shouldReceive('recoverPointsFromPlayer')
            ->once()
            ->with($player, 50.00, $agent);

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
        $agent = Agent::factory()->create([
            'name' => '遊戲測試代理',
            'username' => 'gameagent',
            'account' => 'agameagent',
            'prefix' => 'a',
            'level' => 1,
        ]);

        // 啟用且有足夠點數的玩家
        $activePlayer = Player::factory()->create([
            'agent_id' => $agent->id,
            'is_active' => true,
            'points' => 100.00,
        ]);

        // 停用的玩家
        $inactivePlayer = Player::factory()->create([
            'agent_id' => $agent->id,
            'is_active' => false,
            'points' => 100.00,
        ]);

        // 執行測試
        $this->assertTrue($this->playerService->canPlay($activePlayer, 50.00));
        $this->assertFalse($this->playerService->canPlay($inactivePlayer, 50.00));
    }
}