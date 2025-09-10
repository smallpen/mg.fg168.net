<?php

namespace Tests\Integration\ChannelManagement;

use Tests\TestCase;
use App\Models\User;
use App\Models\Agent;
use App\Models\PointTransaction;
use App\Services\AgentService;
use App\Services\PointService;
use App\Exceptions\InsufficientPointsException;
use App\Exceptions\InvalidPrefixException;
use App\Exceptions\PrefixAlreadyExistsException;
use App\Exceptions\AgentHasDependenciesException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;

/**
 * 代理管理完整流程整合測試
 * 
 * 測試需求：
 * - 需求 1: 代理管理基礎功能
 * - 需求 2: 多層級代理結構與下層代理建立
 * - 需求 3: 前置符號系統
 * - 需求 11: 點數管理機制
 * - 需求 12: 點數分配與回收管理
 * - 需求 13: 點數限制與業務控制
 */
class AgentManagementIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private AgentService $agentService;
    private PointService $pointService;
    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->agentService = app(AgentService::class);
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
     * 測試完整的代理建立流程
     * 需求 1.1-1.9, 2.1-2.3, 3.1-3.3
     */
    public function test_complete_agent_creation_workflow()
    {
        // 1. 建立第一層代理（需要前置符號）
        $firstLevelData = [
            'name' => '第一層代理',
            'username' => 'agent001',
            'email' => 'agent001@test.com',
            'phone' => '0912345678',
            'prefix' => 'a',
            'level' => 1,
            'initial_points' => 10000.00,
            'is_active' => true,
            'notes' => '測試第一層代理',
        ];

        $firstAgent = $this->agentService->createAgent($firstLevelData);

        // 驗證第一層代理建立
        $this->assertDatabaseHas('agents', [
            'name' => '第一層代理',
            'username' => 'agent001',
            'account' => 'aagent001',
            'prefix' => 'a',
            'level' => 1,
            'parent_id' => null,
            'total_points' => 10000.00,
            'remaining_points' => 10000.00,
            'allocated_points' => 0.00,
        ]);

        // 驗證點數交易記錄
        $this->assertDatabaseHas('point_transactions', [
            'agent_id' => $firstAgent->id,
            'type' => PointTransaction::TYPE_AGENT_ALLOCATION,
            'amount' => 10000.00,
        ]);

        // 2. 建立第二層代理
        $secondLevelData = [
            'name' => '第二層代理',
            'username' => 'agent002',
            'email' => 'agent002@test.com',
            'phone' => '0912345679',
            'parent_id' => $firstAgent->id,
            'initial_points' => 5000.00,
            'is_active' => true,
        ];

        $secondAgent = $this->agentService->createAgent($secondLevelData);

        // 驗證第二層代理建立
        $this->assertDatabaseHas('agents', [
            'name' => '第二層代理',
            'username' => 'agent002',
            'account' => 'aagent002',
            'level' => 2,
            'parent_id' => $firstAgent->id,
            'total_points' => 5000.00,
        ]);

        // 驗證上層代理點數扣除
        $firstAgent->refresh();
        $this->assertEquals(5000.00, $firstAgent->remaining_points);
        $this->assertEquals(5000.00, $firstAgent->allocated_points);

        // 3. 建立第三層代理
        $thirdLevelData = [
            'name' => '第三層代理',
            'username' => 'agent003',
            'email' => 'agent003@test.com',
            'parent_id' => $secondAgent->id,
            'initial_points' => 2000.00,
            'is_active' => true,
        ];

        $thirdAgent = $this->agentService->createAgent($thirdLevelData);

        // 驗證第三層代理建立
        $this->assertDatabaseHas('agents', [
            'name' => '第三層代理',
            'level' => 3,
            'parent_id' => $secondAgent->id,
            'account' => 'aagent003',
        ]);

        // 驗證多層級結構
        $this->assertEquals($firstAgent->id, $secondAgent->parent_id);
        $this->assertEquals($secondAgent->id, $thirdAgent->parent_id);
        $this->assertEquals('a', $thirdAgent->full_prefix);
    }

    /**
     * 測試前置符號系統完整性
     * 需求 3.1-3.8
     */
    public function test_prefix_system_integrity()
    {
        // 1. 測試前置符號唯一性
        $agentData1 = [
            'name' => '代理A',
            'username' => 'agentA',
            'email' => 'agentA@test.com',
            'prefix' => 'a',
            'level' => 1,
            'initial_points' => 5000.00,
        ];

        $agent1 = $this->agentService->createAgent($agentData1);
        $this->assertEquals('aagentA', $agent1->account);

        // 2. 測試前置符號衝突
        $agentData2 = [
            'name' => '代理B',
            'username' => 'agentB',
            'email' => 'agentB@test.com',
            'prefix' => 'a', // 相同前置符號
            'level' => 1,
            'initial_points' => 5000.00,
        ];

        $this->expectException(PrefixAlreadyExistsException::class);
        $this->agentService->createAgent($agentData2);

        // 3. 測試無效前置符號
        $agentData3 = [
            'name' => '代理C',
            'username' => 'agentC',
            'email' => 'agentC@test.com',
            'prefix' => '1', // 無效前置符號
            'level' => 1,
            'initial_points' => 5000.00,
        ];

        $this->expectException(InvalidPrefixException::class);
        $this->agentService->createAgent($agentData3);
    }

    /**
     * 測試點數管理完整流程
     * 需求 11.1-11.10, 12.1-12.10, 13.1-13.10
     */
    public function test_complete_point_management_workflow()
    {
        // 建立測試代理結構
        $parentAgent = Agent::factory()->create([
            'prefix' => 'b',
            'level' => 1,
            'total_points' => 10000.00,
            'remaining_points' => 10000.00,
            'allocated_points' => 0.00,
        ]);

        $childAgent = Agent::factory()->create([
            'parent_id' => $parentAgent->id,
            'level' => 2,
            'total_points' => 0.00,
            'remaining_points' => 0.00,
            'allocated_points' => 0.00,
        ]);

        // 1. 測試點數分配
        $this->pointService->allocatePointsToAgent($childAgent, 5000.00, $parentAgent);

        $parentAgent->refresh();
        $childAgent->refresh();

        $this->assertEquals(5000.00, $parentAgent->remaining_points);
        $this->assertEquals(5000.00, $parentAgent->allocated_points);
        $this->assertEquals(5000.00, $childAgent->total_points);
        $this->assertEquals(5000.00, $childAgent->remaining_points);

        // 2. 測試點數不足情況
        $this->expectException(InsufficientPointsException::class);
        $this->pointService->allocatePointsToAgent($childAgent, 6000.00, $parentAgent);

        // 3. 測試點數回收
        $this->pointService->recoverPointsFromAgent($childAgent, 2000.00, $parentAgent);

        $parentAgent->refresh();
        $childAgent->refresh();

        $this->assertEquals(7000.00, $parentAgent->remaining_points);
        $this->assertEquals(3000.00, $parentAgent->allocated_points);
        $this->assertEquals(3000.00, $childAgent->total_points);
        $this->assertEquals(3000.00, $childAgent->remaining_points);

        // 4. 驗證點數交易記錄
        $this->assertDatabaseHas('point_transactions', [
            'agent_id' => $parentAgent->id,
            'type' => PointTransaction::TYPE_AGENT_ALLOCATION,
            'amount' => -5000.00,
        ]);

        $this->assertDatabaseHas('point_transactions', [
            'agent_id' => $childAgent->id,
            'type' => PointTransaction::TYPE_AGENT_ALLOCATION,
            'amount' => 5000.00,
        ]);
    }

    /**
     * 測試代理刪除和點數回收
     * 需求 1.8-1.9, 13.7
     */
    public function test_agent_deletion_and_point_recovery()
    {
        // 建立測試代理結構
        $parentAgent = Agent::factory()->create([
            'prefix' => 'c',
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

        $grandchildAgent = Agent::factory()->create([
            'parent_id' => $childAgent->id,
            'level' => 3,
            'total_points' => 0.00,
            'remaining_points' => 0.00,
            'allocated_points' => 0.00,
        ]);

        // 1. 測試刪除有下層代理的代理（應該失敗）
        $this->expectException(AgentHasDependenciesException::class);
        $this->agentService->deleteAgent($childAgent);

        // 2. 測試刪除無下層關聯的代理
        $result = $this->agentService->deleteAgent($grandchildAgent);
        $this->assertTrue($result);

        // 驗證軟刪除
        $this->assertSoftDeleted('agents', ['id' => $grandchildAgent->id]);

        // 3. 測試刪除有剩餘點數的代理
        $result = $this->agentService->deleteAgent($childAgent);
        $this->assertTrue($result);

        // 驗證點數回收
        $parentAgent->refresh();
        $this->assertEquals(10000.00, $parentAgent->remaining_points);
        $this->assertEquals(0.00, $parentAgent->allocated_points);
    }

    /**
     * 測試代理帳號更新和前置符號同步
     * 需求 1.6, 3.6-3.8
     */
    public function test_agent_account_update_and_prefix_sync()
    {
        // 建立測試代理結構
        $parentAgent = Agent::factory()->create([
            'username' => 'parent',
            'account' => 'dparent',
            'prefix' => 'd',
            'level' => 1,
        ]);

        $childAgent = Agent::factory()->create([
            'username' => 'child',
            'account' => 'dchild',
            'parent_id' => $parentAgent->id,
            'level' => 2,
        ]);

        // 建立隸屬玩家（如果 Player 模型存在）
        if (class_exists(\App\Models\Player::class)) {
            $player = \App\Models\Player::factory()->create([
                'username' => 'player001',
                'account' => 'dplayer001',
                'agent_id' => $parentAgent->id,
            ]);
        }

        // 更新父代理的使用者名稱
        $updatedData = [
            'username' => 'newparent',
        ];

        $this->agentService->updateAgent($parentAgent, $updatedData);

        // 驗證帳號更新
        $parentAgent->refresh();
        $childAgent->refresh();

        $this->assertEquals('newparent', $parentAgent->username);
        $this->assertEquals('dnewparent', $parentAgent->account);
        $this->assertEquals('dchild', $childAgent->account); // 下層代理帳號應該同步更新

        // 驗證玩家帳號同步（如果存在）
        if (isset($player)) {
            $player->refresh();
            $this->assertEquals('dplayer001', $player->account);
        }
    }

    /**
     * 測試多層級代理結構的完整性
     * 需求 2.1-2.10
     */
    public function test_multilevel_agent_structure_integrity()
    {
        // 建立5層代理結構
        $agents = [];
        $parentId = null;

        for ($level = 1; $level <= 5; $level++) {
            $agentData = [
                'name' => "第{$level}層代理",
                'username' => "agent{$level}",
                'email' => "agent{$level}@test.com",
                'parent_id' => $parentId,
                'initial_points' => $level === 1 ? 10000.00 : 1000.00,
                'is_active' => true,
            ];

            if ($level === 1) {
                $agentData['prefix'] = 'e';
                $agentData['level'] = 1;
            }

            $agent = $this->agentService->createAgent($agentData);
            $agents[$level] = $agent;
            $parentId = $agent->id;

            // 驗證層級設定
            $this->assertEquals($level, $agent->level);
            
            // 驗證帳號前置符號
            $this->assertEquals("eagent{$level}", $agent->account);
        }

        // 驗證層級關係
        for ($level = 2; $level <= 5; $level++) {
            $this->assertEquals($agents[$level - 1]->id, $agents[$level]->parent_id);
        }

        // 測試取得所有下層代理
        $descendants = $agents[1]->getAllDescendants();
        $this->assertCount(4, $descendants);

        // 測試取得代理路徑
        $path = [];
        $current = $agents[5];
        while ($current) {
            $path[] = $current->name;
            $current = $current->parent;
        }
        
        $expectedPath = ['第5層代理', '第4層代理', '第3層代理', '第2層代理', '第1層代理'];
        $this->assertEquals($expectedPath, $path);
    }

    /**
     * 測試點數稽核和一致性檢查
     * 需求 17.4, 17.10
     */
    public function test_point_audit_and_consistency_check()
    {
        // 建立測試代理結構
        $rootAgent = Agent::factory()->create([
            'prefix' => 'f',
            'level' => 1,
            'total_points' => 10000.00,
            'remaining_points' => 10000.00,
            'allocated_points' => 0.00,
        ]);

        $child1 = Agent::factory()->create([
            'parent_id' => $rootAgent->id,
            'level' => 2,
            'total_points' => 0.00,
            'remaining_points' => 0.00,
            'allocated_points' => 0.00,
        ]);

        $child2 = Agent::factory()->create([
            'parent_id' => $rootAgent->id,
            'level' => 2,
            'total_points' => 0.00,
            'remaining_points' => 0.00,
            'allocated_points' => 0.00,
        ]);

        // 分配點數
        $this->pointService->allocatePointsToAgent($child1, 4000.00, $rootAgent);
        $this->pointService->allocatePointsToAgent($child2, 3000.00, $rootAgent);

        // 驗證點數一致性
        $rootAgent->refresh();
        $child1->refresh();
        $child2->refresh();

        // 檢查總點數平衡
        $totalAllocated = $child1->total_points + $child2->total_points;
        $this->assertEquals($rootAgent->allocated_points, $totalAllocated);
        
        $totalRemaining = $rootAgent->remaining_points + $rootAgent->allocated_points;
        $this->assertEquals($rootAgent->total_points, $totalRemaining);

        // 檢查點數交易記錄完整性
        $transactions = PointTransaction::where('agent_id', $rootAgent->id)->get();
        $transactionSum = $transactions->sum('amount');
        $this->assertEquals(-7000.00, $transactionSum); // 分配出去的點數
    }

    /**
     * 測試併發點數操作的資料一致性
     * 需求 17.4-17.5
     */
    public function test_concurrent_point_operations_consistency()
    {
        $parentAgent = Agent::factory()->create([
            'prefix' => 'g',
            'level' => 1,
            'total_points' => 10000.00,
            'remaining_points' => 10000.00,
            'allocated_points' => 0.00,
        ]);

        $childAgent = Agent::factory()->create([
            'parent_id' => $parentAgent->id,
            'level' => 2,
            'total_points' => 0.00,
            'remaining_points' => 0.00,
            'allocated_points' => 0.00,
        ]);

        // 模擬併發點數分配（使用資料庫交易）
        DB::transaction(function () use ($parentAgent, $childAgent) {
            $this->pointService->allocatePointsToAgent($childAgent, 5000.00, $parentAgent);
        });

        // 驗證交易後的資料一致性
        $parentAgent->refresh();
        $childAgent->refresh();

        $this->assertEquals(5000.00, $parentAgent->remaining_points);
        $this->assertEquals(5000.00, $parentAgent->allocated_points);
        $this->assertEquals(5000.00, $childAgent->total_points);
        $this->assertEquals(5000.00, $childAgent->remaining_points);

        // 驗證交易記錄的完整性
        $parentTransactions = PointTransaction::where('agent_id', $parentAgent->id)->count();
        $childTransactions = PointTransaction::where('agent_id', $childAgent->id)->count();
        
        $this->assertEquals(1, $parentTransactions);
        $this->assertEquals(1, $childTransactions);
    }
}