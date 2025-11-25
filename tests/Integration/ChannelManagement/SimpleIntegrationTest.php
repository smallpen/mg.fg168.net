<?php

namespace Tests\Integration\ChannelManagement;

use Tests\TestCase;
use App\Models\User;
use App\Models\Agent;
use App\Models\Player;
use App\Models\PointTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * 簡單的通路管理整合測試
 * 用於驗證基本功能是否正常運作
 */
class SimpleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試基本資料庫連接
     */
    public function test_database_connection()
    {
        $this->assertTrue(DB::connection()->getPdo() !== null);
    }

    /**
     * 測試模型類別存在
     */
    public function test_model_classes_exist()
    {
        $this->assertTrue(class_exists(Agent::class));
        $this->assertTrue(class_exists(Player::class));
        $this->assertTrue(class_exists(PointTransaction::class));
    }

    /**
     * 測試服務類別存在
     */
    public function test_service_classes_exist()
    {
        $this->assertTrue(class_exists(\App\Services\AgentService::class));
        $this->assertTrue(class_exists(\App\Services\PlayerService::class));
        $this->assertTrue(class_exists(\App\Services\PointService::class));
    }

    /**
     * 測試基本的代理建立
     */
    public function test_basic_agent_creation()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $agent = Agent::factory()->create([
            'name' => '測試代理',
            'username' => 'testagent',
            'account' => 'atestagent',
            'prefix' => 'a',
            'level' => 1,
            'total_points' => 10000.00,
            'remaining_points' => 10000.00,
            'allocated_points' => 0.00,
        ]);

        $this->assertDatabaseHas('agents', [
            'name' => '測試代理',
            'username' => 'testagent',
            'account' => 'atestagent',
            'prefix' => 'a',
            'level' => 1,
        ]);

        $this->assertEquals(10000.00, $agent->total_points);
        $this->assertEquals(10000.00, $agent->remaining_points);
        $this->assertEquals(0.00, $agent->allocated_points);
    }

    /**
     * 測試基本的玩家建立
     */
    public function test_basic_player_creation()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $agent = Agent::factory()->create([
            'prefix' => 'b',
            'level' => 1,
        ]);

        $player = Player::factory()->create([
            'name' => '測試玩家',
            'username' => 'testplayer',
            'account' => 'btestplayer',
            'agent_id' => $agent->id,
            'points' => 5000.00,
        ]);

        $this->assertDatabaseHas('players', [
            'name' => '測試玩家',
            'username' => 'testplayer',
            'account' => 'btestplayer',
            'agent_id' => $agent->id,
        ]);

        $this->assertEquals(5000.00, $player->points);
        $this->assertEquals($agent->id, $player->agent_id);
    }

    /**
     * 測試點數交易記錄建立
     */
    public function test_basic_point_transaction_creation()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $agent = Agent::factory()->create();

        $transaction = PointTransaction::factory()->create([
            'agent_id' => $agent->id,
            'type' => PointTransaction::TYPE_AGENT_ALLOCATION,
            'amount' => 1000.00,
            'balance_before' => 0.00,
            'balance_after' => 1000.00,
            'description' => '測試點數分配',
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('point_transactions', [
            'agent_id' => $agent->id,
            'type' => PointTransaction::TYPE_AGENT_ALLOCATION,
            'amount' => 1000.00,
            'description' => '測試點數分配',
        ]);

        $this->assertEquals($agent->id, $transaction->agent_id);
        $this->assertEquals(1000.00, $transaction->amount);
    }

    /**
     * 測試代理和玩家的關聯
     */
    public function test_agent_player_relationship()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $agent = Agent::factory()->create([
            'name' => '關聯測試代理',
            'prefix' => 'c',
            'level' => 1,
        ]);

        $player1 = Player::factory()->create([
            'name' => '玩家1',
            'agent_id' => $agent->id,
        ]);

        $player2 = Player::factory()->create([
            'name' => '玩家2',
            'agent_id' => $agent->id,
        ]);

        // 測試代理可以取得其玩家
        $this->assertCount(2, $agent->players);
        $this->assertTrue($agent->players->contains($player1));
        $this->assertTrue($agent->players->contains($player2));

        // 測試玩家可以取得其代理
        $this->assertEquals($agent->id, $player1->agent->id);
        $this->assertEquals($agent->id, $player2->agent->id);
    }

    /**
     * 測試多層級代理結構
     */
    public function test_multilevel_agent_structure()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 建立第一層代理
        $level1Agent = Agent::factory()->create([
            'name' => '第一層代理',
            'prefix' => 'd',
            'level' => 1,
            'parent_id' => null,
        ]);

        // 建立第二層代理
        $level2Agent = Agent::factory()->create([
            'name' => '第二層代理',
            'level' => 2,
            'parent_id' => $level1Agent->id,
        ]);

        // 建立第三層代理
        $level3Agent = Agent::factory()->create([
            'name' => '第三層代理',
            'level' => 3,
            'parent_id' => $level2Agent->id,
        ]);

        // 驗證層級關係
        $this->assertNull($level1Agent->parent_id);
        $this->assertEquals($level1Agent->id, $level2Agent->parent_id);
        $this->assertEquals($level2Agent->id, $level3Agent->parent_id);

        // 驗證層級數字
        $this->assertEquals(1, $level1Agent->level);
        $this->assertEquals(2, $level2Agent->level);
        $this->assertEquals(3, $level3Agent->level);

        // 測試關聯關係
        $this->assertCount(1, $level1Agent->children);
        $this->assertCount(1, $level2Agent->children);
        $this->assertCount(0, $level3Agent->children);

        $this->assertEquals($level1Agent->id, $level2Agent->parent->id);
        $this->assertEquals($level2Agent->id, $level3Agent->parent->id);
    }
}