<?php

namespace Tests\Integration\ChannelManagement;

use Tests\TestCase;
use App\Models\User;
use App\Models\Agent;
use App\Models\Player;
use App\Models\PointTransaction;
use App\Services\PlayerService;
use App\Services\PointService;
use App\Exceptions\InsufficientPointsException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;

/**
 * 玩家管理完整流程整合測試
 * 
 * 測試需求：
 * - 需求 6: 玩家管理基礎功能
 * - 需求 7: 玩家代理關聯與點數分配
 * - 需求 8: 直覺化代理選擇介面
 * - 需求 14: 玩家點數管理機制
 * - 需求 15: 玩家點數操作與控制
 * - 需求 16: 玩家點數限制與業務控制
 * - 需求 17: 資料完整性與驗證
 */
class PlayerManagementIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private PlayerService $playerService;
    private PointService $pointService;
    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->playerService = app(PlayerService::class);
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
     * 測試完整的玩家建立流程
     * 需求 6.1-6.9, 7.1-7.10
     */
    public function test_complete_player_creation_workflow()
    {
        // 建立測試代理結構
        $rootAgent = Agent::factory()->create([
            'name' => '根代理',
            'username' => 'root',
            'account' => 'aroot',
            'prefix' => 'a',
            'level' => 1,
            'total_points' => 50000.00,
            'remaining_points' => 50000.00,
            'allocated_points' => 0.00,
        ]);

        $subAgent = Agent::factory()->create([
            'name' => '子代理',
            'username' => 'sub',
            'account' => 'asub',
            'parent_id' => $rootAgent->id,
            'level' => 2,
            'total_points' => 20000.00,
            'remaining_points' => 20000.00,
            'allocated_points' => 0.00,
        ]);

        // 1. 建立隸屬於根代理的玩家
        $playerData1 = [
            'name' => '測試玩家1',
            'username' => 'player001',
            'email' => 'player001@test.com',
            'phone' => '0912345678',
            'agent_id' => $rootAgent->id,
            'initial_points' => 5000.00,
            'is_active' => true,
            'notes' => '測試玩家1',
        ];

        $player1 = $this->playerService->createPlayer($playerData1);

        // 驗證玩家建立
        $this->assertDatabaseHas('players', [
            'name' => '測試玩家1',
            'username' => 'player001',
            'account' => 'aplayer001', // 繼承代理前置符號
            'agent_id' => $rootAgent->id,
            'points' => 5000.00,
            'is_active' => true,
        ]);

        // 驗證代理點數扣除
        $rootAgent->refresh();
        $this->assertEquals(45000.00, $rootAgent->remaining_points);
        $this->assertEquals(5000.00, $rootAgent->allocated_points);

        // 驗證點數交易記錄
        $this->assertDatabaseHas('point_transactions', [
            'agent_id' => $rootAgent->id,
            'type' => PointTransaction::TYPE_PLAYER_ALLOCATION,
            'amount' => -5000.00,
            'description' => "分配點數給玩家: {$player1->name}",
        ]);

        $this->assertDatabaseHas('point_transactions', [
            'player_id' => $player1->id,
            'type' => PointTransaction::TYPE_PLAYER_ALLOCATION,
            'amount' => 5000.00,
            'description' => "從代理 {$rootAgent->name} 獲得點數",
        ]);

        // 2. 建立隸屬於子代理的玩家
        $playerData2 = [
            'name' => '測試玩家2',
            'username' => 'player002',
            'email' => 'player002@test.com',
            'agent_id' => $subAgent->id,
            'initial_points' => 3000.00,
            'is_active' => true,
        ];

        $player2 = $this->playerService->createPlayer($playerData2);

        // 驗證玩家建立和前置符號繼承
        $this->assertDatabaseHas('players', [
            'name' => '測試玩家2',
            'account' => 'aplayer002', // 繼承相同前置符號
            'agent_id' => $subAgent->id,
            'points' => 3000.00,
        ]);

        // 驗證子代理點數扣除
        $subAgent->refresh();
        $this->assertEquals(17000.00, $subAgent->remaining_points);
        $this->assertEquals(3000.00, $subAgent->allocated_points);
    }

    /**
     * 測試玩家代理關聯和帳號管理
     * 需求 7.1-7.10, 3.4-3.8
     */
    public function test_player_agent_association_and_account_management()
    {
        // 建立多個代理
        $agentA = Agent::factory()->create([
            'name' => '代理A',
            'username' => 'agentA',
            'account' => 'bagentA',
            'prefix' => 'b',
            'level' => 1,
            'total_points' => 30000.00,
            'remaining_points' => 30000.00,
            'allocated_points' => 0.00,
        ]);

        $agentB = Agent::factory()->create([
            'name' => '代理B',
            'username' => 'agentB',
            'account' => 'cagentB',
            'prefix' => 'c',
            'level' => 1,
            'total_points' => 25000.00,
            'remaining_points' => 25000.00,
            'allocated_points' => 0.00,
        ]);

        // 建立玩家隸屬於代理A
        $player = Player::factory()->create([
            'name' => '轉移測試玩家',
            'username' => 'transferplayer',
            'account' => 'btransferplayer',
            'agent_id' => $agentA->id,
            'points' => 5000.00,
        ]);

        // 分配點數給玩家
        $this->pointService->allocatePointsToPlayer($player, 3000.00, $agentA);

        $player->refresh();
        $agentA->refresh();

        $this->assertEquals(8000.00, $player->points);
        $this->assertEquals(27000.00, $agentA->remaining_points);
        $this->assertEquals(8000.00, $agentA->allocated_points);

        // 測試玩家轉移到不同代理
        $updateData = [
            'agent_id' => $agentB->id,
        ];

        $updatedPlayer = $this->playerService->updatePlayer($player, $updateData);

        // 驗證玩家帳號前置符號更新
        $this->assertEquals('ctransferplayer', $updatedPlayer->account);
        $this->assertEquals($agentB->id, $updatedPlayer->agent_id);

        // 驗證點數轉移（這裡假設點數轉移邏輯已實作）
        $agentA->refresh();
        $agentB->refresh();

        // 原代理應該回收點數
        $this->assertEquals(30000.00, $agentA->remaining_points);
        $this->assertEquals(0.00, $agentA->allocated_points);

        // 新代理應該分配點數
        $this->assertEquals(17000.00, $agentB->remaining_points);
        $this->assertEquals(8000.00, $agentB->allocated_points);
    }

    /**
     * 測試玩家點數管理和限制
     * 需求 14.1-14.10, 15.1-15.10, 16.1-16.10
     */
    public function test_player_point_management_and_limits()
    {
        // 建立測試代理
        $agent = Agent::factory()->create([
            'name' => '點數測試代理',
            'prefix' => 'd',
            'level' => 1,
            'total_points' => 20000.00,
            'remaining_points' => 20000.00,
            'allocated_points' => 0.00,
        ]);

        // 建立多個玩家
        $players = [];
        for ($i = 1; $i <= 5; $i++) {
            $players[$i] = Player::factory()->create([
                'name' => "點數玩家{$i}",
                'username' => "pointplayer{$i}",
                'account' => "dpointplayer{$i}",
                'agent_id' => $agent->id,
                'points' => 0.00,
            ]);
        }

        // 1. 測試分配點數給多個玩家
        $this->pointService->allocatePointsToPlayer($players[1], 5000.00, $agent);
        $this->pointService->allocatePointsToPlayer($players[2], 4000.00, $agent);
        $this->pointService->allocatePointsToPlayer($players[3], 3000.00, $agent);
        $this->pointService->allocatePointsToPlayer($players[4], 2000.00, $agent);

        // 驗證點數分配
        $agent->refresh();
        $this->assertEquals(6000.00, $agent->remaining_points);
        $this->assertEquals(14000.00, $agent->allocated_points);

        foreach ([1 => 5000, 2 => 4000, 3 => 3000, 4 => 2000] as $index => $expectedPoints) {
            $players[$index]->refresh();
            $this->assertEquals($expectedPoints, $players[$index]->points);
        }

        // 2. 測試代理點數不足情況
        $this->expectException(InsufficientPointsException::class);
        $this->pointService->allocatePointsToPlayer($players[5], 7000.00, $agent);

        // 3. 測試點數回收
        $this->pointService->recoverPointsFromPlayer($players[1], 2000.00, $agent);
        $this->pointService->recoverPointsFromPlayer($players[2], 1000.00, $agent);

        $agent->refresh();
        $players[1]->refresh();
        $players[2]->refresh();

        $this->assertEquals(9000.00, $agent->remaining_points);
        $this->assertEquals(11000.00, $agent->allocated_points);
        $this->assertEquals(3000.00, $players[1]->points);
        $this->assertEquals(3000.00, $players[2]->points);

        // 4. 測試玩家點數不足回收
        $this->expectException(InsufficientPointsException::class);
        $this->pointService->recoverPointsFromPlayer($players[3], 4000.00, $agent);
    }

    /**
     * 測試玩家刪除和點數回收
     * 需求 6.8-6.9, 16.7
     */
    public function test_player_deletion_and_point_recovery()
    {
        // 建立測試代理
        $agent = Agent::factory()->create([
            'name' => '刪除測試代理',
            'prefix' => 'e',
            'level' => 1,
            'total_points' => 30000.00,
            'remaining_points' => 20000.00,
            'allocated_points' => 10000.00,
        ]);

        // 建立測試玩家
        $player = Player::factory()->create([
            'name' => '刪除測試玩家',
            'username' => 'deleteplayer',
            'account' => 'edeleteplayer',
            'agent_id' => $agent->id,
            'points' => 10000.00,
        ]);

        // 執行玩家刪除
        $result = $this->playerService->deletePlayer($player);
        $this->assertTrue($result);

        // 驗證軟刪除
        $this->assertSoftDeleted('players', ['id' => $player->id]);

        // 驗證點數回收到代理
        $agent->refresh();
        $this->assertEquals(30000.00, $agent->remaining_points);
        $this->assertEquals(0.00, $agent->allocated_points);

        // 驗證點數交易記錄
        $this->assertDatabaseHas('point_transactions', [
            'player_id' => $player->id,
            'type' => PointTransaction::TYPE_PLAYER_RECOVERY,
            'amount' => -10000.00,
            'description' => "點數被代理 {$agent->name} 回收",
        ]);

        $this->assertDatabaseHas('point_transactions', [
            'agent_id' => $agent->id,
            'type' => PointTransaction::TYPE_PLAYER_RECOVERY,
            'amount' => 10000.00,
            'description' => "從玩家 {$player->name} 回收點數",
        ]);
    }

    /**
     * 測試玩家帳號更新和驗證
     * 需求 6.5-6.7, 7.8
     */
    public function test_player_account_update_and_validation()
    {
        // 建立測試代理
        $agent = Agent::factory()->create([
            'name' => '更新測試代理',
            'username' => 'updateagent',
            'account' => 'fupdateagent',
            'prefix' => 'f',
            'level' => 1,
            'total_points' => 20000.00,
            'remaining_points' => 20000.00,
            'allocated_points' => 0.00,
        ]);

        // 建立測試玩家
        $player = Player::factory()->create([
            'name' => '更新測試玩家',
            'username' => 'updateplayer',
            'account' => 'fupdateplayer',
            'agent_id' => $agent->id,
            'points' => 5000.00,
        ]);

        // 1. 測試玩家基本資料更新
        $updateData = [
            'name' => '已更新玩家',
            'email' => 'updated@test.com',
            'phone' => '0987654321',
            'notes' => '已更新的玩家資料',
        ];

        $updatedPlayer = $this->playerService->updatePlayer($player, $updateData);

        $this->assertEquals('已更新玩家', $updatedPlayer->name);
        $this->assertEquals('updated@test.com', $updatedPlayer->email);
        $this->assertEquals('0987654321', $updatedPlayer->phone);
        $this->assertEquals('已更新的玩家資料', $updatedPlayer->notes);

        // 2. 測試玩家使用者名稱更新
        $updateData2 = [
            'username' => 'newusername',
        ];

        $updatedPlayer2 = $this->playerService->updatePlayer($updatedPlayer, $updateData2);

        // 驗證帳號前置符號保持一致
        $this->assertEquals('newusername', $updatedPlayer2->username);
        $this->assertEquals('fnewusername', $updatedPlayer2->account);

        // 3. 驗證修改歷史記錄（假設有活動日誌）
        $this->assertDatabaseHas('point_transactions', [
            'player_id' => $player->id,
        ]);
    }

    /**
     * 測試多層級代理結構中的玩家管理
     * 需求 7.1-7.10, 8.1-8.8
     */
    public function test_player_management_in_multilevel_structure()
    {
        // 建立多層級代理結構
        $level1Agent = Agent::factory()->create([
            'name' => '第1層代理',
            'prefix' => 'g',
            'level' => 1,
            'total_points' => 100000.00,
            'remaining_points' => 100000.00,
            'allocated_points' => 0.00,
        ]);

        $level2Agent = Agent::factory()->create([
            'name' => '第2層代理',
            'parent_id' => $level1Agent->id,
            'level' => 2,
            'total_points' => 50000.00,
            'remaining_points' => 50000.00,
            'allocated_points' => 0.00,
        ]);

        $level3Agent = Agent::factory()->create([
            'name' => '第3層代理',
            'parent_id' => $level2Agent->id,
            'level' => 3,
            'total_points' => 25000.00,
            'remaining_points' => 25000.00,
            'allocated_points' => 0.00,
        ]);

        // 在不同層級建立玩家
        $players = [];
        $players[1] = Player::factory()->create([
            'name' => '第1層玩家',
            'username' => 'level1player',
            'account' => 'glevel1player',
            'agent_id' => $level1Agent->id,
            'points' => 0.00,
        ]);

        $players[2] = Player::factory()->create([
            'name' => '第2層玩家',
            'username' => 'level2player',
            'account' => 'glevel2player',
            'agent_id' => $level2Agent->id,
            'points' => 0.00,
        ]);

        $players[3] = Player::factory()->create([
            'name' => '第3層玩家',
            'username' => 'level3player',
            'account' => 'glevel3player',
            'agent_id' => $level3Agent->id,
            'points' => 0.00,
        ]);

        // 分配點數給各層級玩家
        $this->pointService->allocatePointsToPlayer($players[1], 10000.00, $level1Agent);
        $this->pointService->allocatePointsToPlayer($players[2], 8000.00, $level2Agent);
        $this->pointService->allocatePointsToPlayer($players[3], 5000.00, $level3Agent);

        // 驗證所有玩家都有相同的前置符號
        foreach ($players as $player) {
            $player->refresh();
            $this->assertStringStartsWith('g', $player->account);
        }

        // 驗證各層級代理的點數分配
        $level1Agent->refresh();
        $level2Agent->refresh();
        $level3Agent->refresh();

        $this->assertEquals(90000.00, $level1Agent->remaining_points);
        $this->assertEquals(10000.00, $level1Agent->allocated_points);
        $this->assertEquals(42000.00, $level2Agent->remaining_points);
        $this->assertEquals(8000.00, $level2Agent->allocated_points);
        $this->assertEquals(20000.00, $level3Agent->remaining_points);
        $this->assertEquals(5000.00, $level3Agent->allocated_points);

        // 測試玩家代理路徑
        $agentPath = $players[3]->agent_path;
        $this->assertCount(3, $agentPath);
        $this->assertEquals($level1Agent->id, $agentPath[0]->id);
        $this->assertEquals($level2Agent->id, $agentPath[1]->id);
        $this->assertEquals($level3Agent->id, $agentPath[2]->id);
    }

    /**
     * 測試玩家資料完整性和驗證
     * 需求 17.1-17.10
     */
    public function test_player_data_integrity_and_validation()
    {
        // 建立測試代理
        $agent = Agent::factory()->create([
            'name' => '完整性測試代理',
            'prefix' => 'h',
            'level' => 1,
            'total_points' => 50000.00,
            'remaining_points' => 50000.00,
            'allocated_points' => 0.00,
        ]);

        // 1. 測試玩家帳號唯一性
        $player1 = Player::factory()->create([
            'name' => '玩家1',
            'username' => 'uniqueplayer',
            'account' => 'huniqueplayer',
            'agent_id' => $agent->id,
            'points' => 1000.00,
        ]);

        // 嘗試建立相同帳號的玩家應該失敗
        $this->expectException(\Illuminate\Database\QueryException::class);
        Player::factory()->create([
            'name' => '玩家2',
            'username' => 'uniqueplayer', // 相同使用者名稱
            'account' => 'huniqueplayer', // 相同完整帳號
            'agent_id' => $agent->id,
            'points' => 2000.00,
        ]);
    }

    /**
     * 測試批次玩家操作
     * 需求 15.7, 16.9
     */
    public function test_batch_player_operations()
    {
        // 建立測試代理
        $agent = Agent::factory()->create([
            'name' => '批次測試代理',
            'prefix' => 'i',
            'level' => 1,
            'total_points' => 100000.00,
            'remaining_points' => 100000.00,
            'allocated_points' => 0.00,
        ]);

        // 建立多個玩家
        $players = [];
        for ($i = 1; $i <= 10; $i++) {
            $players[$i] = Player::factory()->create([
                'name' => "批次玩家{$i}",
                'username' => "batchplayer{$i}",
                'account' => "ibatchplayer{$i}",
                'agent_id' => $agent->id,
                'points' => 0.00,
            ]);
        }

        // 批次分配點數
        DB::transaction(function () use ($players, $agent) {
            foreach ($players as $player) {
                $this->pointService->allocatePointsToPlayer($player, 5000.00, $agent);
            }
        });

        // 驗證批次操作結果
        $agent->refresh();
        $this->assertEquals(50000.00, $agent->remaining_points);
        $this->assertEquals(50000.00, $agent->allocated_points);

        $totalPlayerPoints = 0;
        foreach ($players as $player) {
            $player->refresh();
            $totalPlayerPoints += $player->points;
            $this->assertEquals(5000.00, $player->points);
        }

        $this->assertEquals(50000.00, $totalPlayerPoints);

        // 驗證交易記錄數量
        $transactionCount = PointTransaction::count();
        $this->assertEquals(20, $transactionCount); // 10次操作 × 2筆記錄

        // 批次回收部分點數
        DB::transaction(function () use ($players, $agent) {
            for ($i = 1; $i <= 5; $i++) {
                $this->pointService->recoverPointsFromPlayer($players[$i], 2000.00, $agent);
            }
        });

        // 驗證批次回收結果
        $agent->refresh();
        $this->assertEquals(60000.00, $agent->remaining_points);
        $this->assertEquals(40000.00, $agent->allocated_points);

        // 驗證部分玩家點數減少
        for ($i = 1; $i <= 5; $i++) {
            $players[$i]->refresh();
            $this->assertEquals(3000.00, $players[$i]->points);
        }

        for ($i = 6; $i <= 10; $i++) {
            $players[$i]->refresh();
            $this->assertEquals(5000.00, $players[$i]->points);
        }
    }
}