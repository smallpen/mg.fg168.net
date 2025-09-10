<?php

namespace Tests\Integration\ChannelManagement;

use Tests\TestCase;
use App\Models\User;
use App\Models\Agent;
use App\Models\Player;
use App\Models\Role;
use App\Models\Permission;
use App\Models\PointTransaction;
use App\Services\AgentService;
use App\Services\PlayerService;
use App\Services\PointService;
use App\Exceptions\InsufficientPointsException;
use App\Exceptions\InvalidPrefixException;
use App\Exceptions\PrefixAlreadyExistsException;
use App\Exceptions\AgentHasDependenciesException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * 通路管理系統完整流程整合測試
 * 
 * 測試需求：
 * - 需求 1-17: 所有通路管理需求的整合驗證
 * - 多層級代理結構的建立和管理
 * - 權限控制和資料存取限制
 * - 完整的業務流程驗證
 */
class ChannelManagementIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private AgentService $agentService;
    private PlayerService $playerService;
    private PointService $pointService;
    private User $adminUser;
    private User $agentUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->agentService = app(AgentService::class);
        $this->playerService = app(PlayerService::class);
        $this->pointService = app(PointService::class);
        
        // 建立測試管理員
        $this->adminUser = User::factory()->create([
            'username' => 'test_admin',
            'name' => '測試管理員',
            'email' => 'admin@test.com',
        ]);

        // 建立測試代理使用者
        $this->agentUser = User::factory()->create([
            'username' => 'test_agent',
            'name' => '測試代理使用者',
            'email' => 'agent@test.com',
        ]);

        // 建立基本權限和角色
        $this->createBasicPermissionsAndRoles();
    }

    /**
     * 建立基本權限和角色
     */
    private function createBasicPermissionsAndRoles(): void
    {
        // 建立通路管理相關權限
        $permissions = [
            'channels.agents.view' => '檢視代理',
            'channels.agents.create' => '建立代理',
            'channels.agents.edit' => '編輯代理',
            'channels.agents.delete' => '刪除代理',
            'channels.players.view' => '檢視玩家',
            'channels.players.create' => '建立玩家',
            'channels.players.edit' => '編輯玩家',
            'channels.players.delete' => '刪除玩家',
            'channels.points.manage' => '管理點數',
            'channels.points.allocate' => '分配點數',
            'channels.points.recover' => '回收點數',
            'channels.reports.view' => '檢視報表',
        ];

        foreach ($permissions as $name => $displayName) {
            Permission::create([
                'name' => $name,
                'display_name' => $displayName,
                'module' => 'channels',
            ]);
        }

        // 建立管理員角色
        $adminRole = Role::create([
            'name' => 'channel_admin',
            'display_name' => '通路管理員',
        ]);

        // 建立代理角色
        $agentRole = Role::create([
            'name' => 'channel_agent',
            'display_name' => '通路代理',
        ]);

        // 分配權限
        $adminRole->permissions()->attach(Permission::whereIn('name', array_keys($permissions))->pluck('id'));
        $agentRole->permissions()->attach(Permission::whereIn('name', [
            'channels.agents.view',
            'channels.agents.create',
            'channels.players.view',
            'channels.players.create',
            'channels.points.manage',
        ])->pluck('id'));

        // 分配角色給使用者
        $this->adminUser->roles()->attach($adminRole);
        $this->agentUser->roles()->attach($agentRole);
    }

    /**
     * 測試完整的通路管理業務流程
     * 需求 1-17: 完整業務流程驗證
     */
    public function test_complete_channel_management_workflow()
    {
        $this->actingAs($this->adminUser);

        // 第一階段：建立多層級代理結構
        $agents = $this->createMultilevelAgentStructure();

        // 第二階段：建立玩家並分配到各層級代理
        $players = $this->createPlayersForAgents($agents);

        // 第三階段：執行複雜的點數分配和管理
        $this->executeComplexPointOperations($agents, $players);

        // 第四階段：測試權限控制和資料存取限制
        $this->testPermissionControlAndDataAccess($agents, $players);

        // 第五階段：測試業務規則和限制
        $this->testBusinessRulesAndConstraints($agents, $players);

        // 第六階段：驗證資料完整性和一致性
        $this->verifyDataIntegrityAndConsistency($agents, $players);
    }

    /**
     * 建立多層級代理結構
     * 需求 1, 2, 3: 代理管理、多層級結構、前置符號系統
     */
    private function createMultilevelAgentStructure(): array
    {
        $agents = [];

        // 建立第一層代理（使用不同前置符號）
        $prefixes = ['a', 'b', 'c'];
        foreach ($prefixes as $index => $prefix) {
            $agents["level1_{$prefix}"] = $this->agentService->createAgent([
                'name' => "第一層代理{$prefix}",
                'username' => "agent1{$prefix}",
                'email' => "agent1{$prefix}@test.com",
                'phone' => '091234567' . $index,
                'prefix' => $prefix,
                'level' => 1,
                'initial_points' => 100000.00,
                'is_active' => true,
                'notes' => "第一層代理{$prefix}",
            ]);
        }

        // 建立第二層代理
        foreach ($prefixes as $prefix) {
            $parentAgent = $agents["level1_{$prefix}"];
            for ($i = 1; $i <= 2; $i++) {
                $agents["level2_{$prefix}_{$i}"] = $this->agentService->createAgent([
                    'name' => "第二層代理{$prefix}{$i}",
                    'username' => "agent2{$prefix}{$i}",
                    'email' => "agent2{$prefix}{$i}@test.com",
                    'phone' => '092345678' . $i,
                    'parent_id' => $parentAgent->id,
                    'initial_points' => 30000.00,
                    'is_active' => true,
                ]);
            }
        }

        // 建立第三層代理
        foreach ($prefixes as $prefix) {
            for ($i = 1; $i <= 2; $i++) {
                $parentAgent = $agents["level2_{$prefix}_{$i}"];
                for ($j = 1; $j <= 2; $j++) {
                    $agents["level3_{$prefix}_{$i}_{$j}"] = $this->agentService->createAgent([
                        'name' => "第三層代理{$prefix}{$i}{$j}",
                        'username' => "agent3{$prefix}{$i}{$j}",
                        'email' => "agent3{$prefix}{$i}{$j}@test.com",
                        'parent_id' => $parentAgent->id,
                        'initial_points' => 10000.00,
                        'is_active' => true,
                    ]);
                }
            }
        }

        // 驗證代理結構
        $this->assertEquals(3, Agent::where('level', 1)->count());
        $this->assertEquals(6, Agent::where('level', 2)->count());
        $this->assertEquals(12, Agent::where('level', 3)->count());

        // 驗證前置符號繼承
        foreach ($agents as $key => $agent) {
            if (str_contains($key, 'level1_a') || str_contains($key, '_a_')) {
                $this->assertStringStartsWith('a', $agent->account);
            } elseif (str_contains($key, 'level1_b') || str_contains($key, '_b_')) {
                $this->assertStringStartsWith('b', $agent->account);
            } elseif (str_contains($key, 'level1_c') || str_contains($key, '_c_')) {
                $this->assertStringStartsWith('c', $agent->account);
            }
        }

        return $agents;
    }

    /**
     * 為各層級代理建立玩家
     * 需求 6, 7, 8: 玩家管理、代理關聯、直覺化選擇
     */
    private function createPlayersForAgents(array $agents): array
    {
        $players = [];

        // 為每個第三層代理建立玩家
        foreach ($agents as $key => $agent) {
            if (str_contains($key, 'level3_')) {
                for ($i = 1; $i <= 3; $i++) {
                    $playerKey = "{$key}_player{$i}";
                    $players[$playerKey] = $this->playerService->createPlayer([
                        'name' => "玩家{$key}{$i}",
                        'username' => "player{$key}{$i}",
                        'email' => "player{$key}{$i}@test.com",
                        'phone' => '093456789' . $i,
                        'agent_id' => $agent->id,
                        'initial_points' => 1000.00,
                        'is_active' => true,
                    ]);
                }
            }
        }

        // 為部分第二層代理建立玩家
        foreach ($agents as $key => $agent) {
            if (str_contains($key, 'level2_') && str_contains($key, '_1')) {
                for ($i = 1; $i <= 2; $i++) {
                    $playerKey = "{$key}_player{$i}";
                    $players[$playerKey] = $this->playerService->createPlayer([
                        'name' => "玩家{$key}{$i}",
                        'username' => "player{$key}{$i}",
                        'email' => "player{$key}{$i}@test.com",
                        'agent_id' => $agent->id,
                        'initial_points' => 2000.00,
                        'is_active' => true,
                    ]);
                }
            }
        }

        // 驗證玩家建立
        $totalPlayers = Player::count();
        $this->assertGreaterThan(30, $totalPlayers);

        // 驗證玩家帳號前置符號
        foreach ($players as $key => $player) {
            $agent = $player->agent;
            $this->assertEquals($agent->full_prefix, substr($player->account, 0, 1));
        }

        return $players;
    }

    /**
     * 執行複雜的點數分配和管理操作
     * 需求 11, 12, 13, 14, 15, 16: 點數管理相關需求
     */
    private function executeComplexPointOperations(array $agents, array $players): void
    {
        // 1. 測試多層級點數分配
        $rootAgent = $agents['level1_a'];
        $level2Agent = $agents['level2_a_1'];
        $level3Agent = $agents['level3_a_1_1'];

        // 額外分配點數給第二層代理
        $this->pointService->allocatePointsToAgent($level2Agent, 20000.00, $rootAgent);

        // 額外分配點數給第三層代理
        $this->pointService->allocatePointsToAgent($level3Agent, 15000.00, $level2Agent);

        // 2. 測試玩家點數管理
        $testPlayers = array_slice($players, 0, 5, true);
        foreach ($testPlayers as $player) {
            $this->pointService->allocatePointsToPlayer($player, 3000.00, $player->agent);
        }

        // 3. 測試點數回收
        $this->pointService->recoverPointsFromAgent($level3Agent, 5000.00, $level2Agent);
        $this->pointService->recoverPointsFromPlayer(array_values($testPlayers)[0], 1000.00, array_values($testPlayers)[0]->agent);

        // 4. 驗證點數一致性
        $rootAgent->refresh();
        $level2Agent->refresh();
        $level3Agent->refresh();

        $this->assertGreaterThan(0, $rootAgent->allocated_points);
        $this->assertGreaterThan(0, $level2Agent->allocated_points);
        $this->assertGreaterThan(0, $level3Agent->allocated_points);
    }

    /**
     * 測試權限控制和資料存取限制
     * 需求 9, 10: 代理自主管理、權限控制
     */
    private function testPermissionControlAndDataAccess(array $agents, array $players): void
    {
        // 1. 測試管理員權限
        $this->actingAs($this->adminUser);

        // 管理員應該能存取所有代理
        $allAgents = Agent::all();
        $this->assertGreaterThan(20, $allAgents->count());

        // 管理員應該能存取所有玩家
        $allPlayers = Player::all();
        $this->assertGreaterThan(30, $allPlayers->count());

        // 2. 測試代理使用者權限限制
        $this->actingAs($this->agentUser);

        // 建立代理使用者對應的代理記錄
        $agentRecord = $agents['level2_a_1'];
        $this->agentUser->update(['agent_id' => $agentRecord->id]);

        // 代理使用者應該只能存取其管轄範圍內的資料
        $accessibleAgents = Agent::where('parent_id', $agentRecord->id)
                                 ->orWhere('id', $agentRecord->id)
                                 ->get();

        $accessiblePlayers = Player::where('agent_id', $agentRecord->id)
                                  ->orWhereIn('agent_id', $accessibleAgents->pluck('id'))
                                  ->get();

        $this->assertLessThan($allAgents->count(), $accessibleAgents->count());
        $this->assertLessThan($allPlayers->count(), $accessiblePlayers->count());

        // 3. 測試操作權限
        // 代理應該能建立下層代理
        $newSubAgent = $this->agentService->createAgent([
            'name' => '權限測試子代理',
            'username' => 'permissiontest',
            'email' => 'permissiontest@test.com',
            'parent_id' => $agentRecord->id,
            'initial_points' => 5000.00,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('agents', [
            'name' => '權限測試子代理',
            'parent_id' => $agentRecord->id,
        ]);

        // 代理應該能建立隸屬玩家
        $newPlayer = $this->playerService->createPlayer([
            'name' => '權限測試玩家',
            'username' => 'permissionplayer',
            'email' => 'permissionplayer@test.com',
            'agent_id' => $agentRecord->id,
            'initial_points' => 2000.00,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('players', [
            'name' => '權限測試玩家',
            'agent_id' => $agentRecord->id,
        ]);
    }

    /**
     * 測試業務規則和限制
     * 需求 13, 16, 17: 點數限制、業務控制、資料驗證
     */
    private function testBusinessRulesAndConstraints(array $agents, array $players): void
    {
        $this->actingAs($this->adminUser);

        // 1. 測試前置符號唯一性
        $this->expectException(PrefixAlreadyExistsException::class);
        $this->agentService->createAgent([
            'name' => '重複前置符號代理',
            'username' => 'duplicate',
            'email' => 'duplicate@test.com',
            'prefix' => 'a', // 已存在的前置符號
            'level' => 1,
            'initial_points' => 10000.00,
        ]);
    }

    /**
     * 測試無效前置符號
     */
    public function test_invalid_prefix_constraint()
    {
        $this->actingAs($this->adminUser);

        $this->expectException(InvalidPrefixException::class);
        $this->agentService->createAgent([
            'name' => '無效前置符號代理',
            'username' => 'invalid',
            'email' => 'invalid@test.com',
            'prefix' => '1', // 無效前置符號
            'level' => 1,
            'initial_points' => 10000.00,
        ]);
    }

    /**
     * 測試代理刪除限制
     */
    public function test_agent_deletion_constraints()
    {
        $this->actingAs($this->adminUser);

        // 建立有下層關聯的代理
        $parentAgent = Agent::factory()->create([
            'prefix' => 'z',
            'level' => 1,
            'total_points' => 10000.00,
            'remaining_points' => 5000.00,
            'allocated_points' => 5000.00,
        ]);

        $childAgent = Agent::factory()->create([
            'parent_id' => $parentAgent->id,
            'level' => 2,
            'total_points' => 5000.00,
            'remaining_points' => 5000.00,
            'allocated_points' => 0.00,
        ]);

        // 嘗試刪除有下層代理的代理應該失敗
        $this->expectException(AgentHasDependenciesException::class);
        $this->agentService->deleteAgent($parentAgent);
    }

    /**
     * 驗證資料完整性和一致性
     * 需求 17: 資料完整性與驗證
     */
    private function verifyDataIntegrityAndConsistency(array $agents, array $players): void
    {
        // 1. 驗證代理層級結構完整性
        foreach ($agents as $agent) {
            $agent->refresh();
            
            if ($agent->level > 1) {
                $this->assertNotNull($agent->parent_id);
                $this->assertEquals($agent->level, $agent->parent->level + 1);
            } else {
                $this->assertNull($agent->parent_id);
                $this->assertNotNull($agent->prefix);
            }
        }

        // 2. 驗證點數平衡
        $totalSystemPoints = Agent::where('level', 1)->sum('total_points');
        $totalAllocatedPoints = Agent::sum('allocated_points');
        $totalPlayerPoints = Player::sum('points');

        // 系統總點數應該等於所有分配點數加上剩餘點數
        $totalRemainingPoints = Agent::sum('remaining_points');
        $this->assertEquals($totalSystemPoints, $totalAllocatedPoints + $totalRemainingPoints);

        // 3. 驗證交易記錄完整性
        $agentTransactions = PointTransaction::whereNotNull('agent_id')->get();
        $playerTransactions = PointTransaction::whereNotNull('player_id')->get();

        // 每筆點數操作都應該有對應的交易記錄
        $this->assertGreaterThan(0, $agentTransactions->count());
        $this->assertGreaterThan(0, $playerTransactions->count());

        // 4. 驗證帳號前置符號一致性
        foreach ($players as $player) {
            $player->refresh();
            $agent = $player->agent;
            $expectedPrefix = $agent->full_prefix;
            $this->assertStringStartsWith($expectedPrefix, $player->account);
        }

        // 5. 驗證軟刪除完整性
        $deletedAgents = Agent::onlyTrashed()->get();
        $deletedPlayers = Player::onlyTrashed()->get();

        // 軟刪除的記錄應該仍然保持資料關聯
        foreach ($deletedAgents as $deletedAgent) {
            if ($deletedAgent->parent_id) {
                $parent = Agent::withTrashed()->find($deletedAgent->parent_id);
                $this->assertNotNull($parent);
            }
        }
    }

    /**
     * 測試複雜的業務場景
     * 需求 1-17: 綜合業務場景測試
     */
    public function test_complex_business_scenarios()
    {
        $this->actingAs($this->adminUser);

        // 場景1：代理網絡重組
        $this->testAgentNetworkReorganization();

        // 場景2：大量點數操作
        $this->testMassPointOperations();

        // 場景3：異常情況處理
        $this->testExceptionHandling();
    }

    /**
     * 測試代理網絡重組
     */
    private function testAgentNetworkReorganization(): void
    {
        // 建立測試代理結構
        $rootAgent = Agent::factory()->create([
            'prefix' => 'x',
            'level' => 1,
            'total_points' => 50000.00,
            'remaining_points' => 50000.00,
            'allocated_points' => 0.00,
        ]);

        $agent1 = Agent::factory()->create([
            'parent_id' => $rootAgent->id,
            'level' => 2,
            'total_points' => 20000.00,
            'remaining_points' => 20000.00,
            'allocated_points' => 0.00,
        ]);

        $agent2 = Agent::factory()->create([
            'parent_id' => $rootAgent->id,
            'level' => 2,
            'total_points' => 15000.00,
            'remaining_points' => 15000.00,
            'allocated_points' => 0.00,
        ]);

        // 建立玩家
        $player1 = Player::factory()->create([
            'agent_id' => $agent1->id,
            'points' => 5000.00,
        ]);

        $player2 = Player::factory()->create([
            'agent_id' => $agent1->id,
            'points' => 3000.00,
        ]);

        // 測試玩家轉移到不同代理
        $this->playerService->updatePlayer($player1, ['agent_id' => $agent2->id]);

        $player1->refresh();
        $agent1->refresh();
        $agent2->refresh();

        // 驗證玩家轉移後的狀態
        $this->assertEquals($agent2->id, $player1->agent_id);
        $this->assertEquals($agent2->full_prefix . $player1->username, $player1->account);
    }

    /**
     * 測試大量點數操作
     */
    private function testMassPointOperations(): void
    {
        // 建立大量代理和玩家進行壓力測試
        $rootAgent = Agent::factory()->create([
            'prefix' => 'y',
            'level' => 1,
            'total_points' => 1000000.00,
            'remaining_points' => 1000000.00,
            'allocated_points' => 0.00,
        ]);

        $agents = [];
        $players = [];

        // 建立50個子代理
        for ($i = 1; $i <= 50; $i++) {
            $agents[$i] = Agent::factory()->create([
                'parent_id' => $rootAgent->id,
                'level' => 2,
                'total_points' => 0.00,
                'remaining_points' => 0.00,
                'allocated_points' => 0.00,
            ]);

            // 為每個代理建立5個玩家
            for ($j = 1; $j <= 5; $j++) {
                $players["{$i}_{$j}"] = Player::factory()->create([
                    'agent_id' => $agents[$i]->id,
                    'points' => 0.00,
                ]);
            }
        }

        // 批次分配點數
        DB::transaction(function () use ($rootAgent, $agents, $players) {
            // 分配點數給代理
            foreach ($agents as $agent) {
                $this->pointService->allocatePointsToAgent($agent, 10000.00, $rootAgent);
            }

            // 分配點數給玩家
            foreach ($players as $player) {
                $this->pointService->allocatePointsToPlayer($player, 1000.00, $player->agent);
            }
        });

        // 驗證大量操作結果
        $rootAgent->refresh();
        $this->assertEquals(500000.00, $rootAgent->allocated_points); // 50 * 10000
        $this->assertEquals(500000.00, $rootAgent->remaining_points);

        $totalPlayerPoints = Player::whereIn('id', collect($players)->pluck('id'))->sum('points');
        $this->assertEquals(250000.00, $totalPlayerPoints); // 250 * 1000
    }

    /**
     * 測試異常情況處理
     */
    private function testExceptionHandling(): void
    {
        $agent = Agent::factory()->create([
            'prefix' => 'w',
            'level' => 1,
            'total_points' => 1000.00,
            'remaining_points' => 1000.00,
            'allocated_points' => 0.00,
        ]);

        $player = Player::factory()->create([
            'agent_id' => $agent->id,
            'points' => 500.00,
        ]);

        // 測試點數不足異常
        try {
            $this->pointService->allocatePointsToPlayer($player, 2000.00, $agent);
            $this->fail('應該拋出 InsufficientPointsException');
        } catch (InsufficientPointsException $e) {
            $this->assertTrue(true);
        }

        // 測試回收超過擁有的點數
        try {
            $this->pointService->recoverPointsFromPlayer($player, 1000.00, $agent);
            $this->fail('應該拋出 InsufficientPointsException');
        } catch (InsufficientPointsException $e) {
            $this->assertTrue(true);
        }

        // 驗證異常後資料狀態未改變
        $agent->refresh();
        $player->refresh();

        $this->assertEquals(1000.00, $agent->remaining_points);
        $this->assertEquals(500.00, $player->points);
    }
}